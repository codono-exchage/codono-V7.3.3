<?php
use Workerman\Worker;

use Workerman\Connection\AsyncTcpConnection;
require_once __DIR__ . '/SocketRedis.class.php';
//Replace correct crt and pk file path below to allow websocket run in wss format
$context = array(
    'ssl' => array(
        //'local_cert'  => '/pathto/apache1.crt',
      //  'local_pk'    => '/pathto/apache1.key',
        'verify_peer' => false,
        'verify_peer_name' => false,	
       // 'allow_self_signed' => true
    )
);

//Keep this value 0 as it bricks the websocket and stops daemon
define('DB_UPDATE_PRICING',0);
define('ALLOW_KLINE',0);
$ws_data_source_url = 'ws://api.huobi.pro:443/ws';
//Proxy listening on local 7272 port
$worker = new Worker('websocket://0.0.0.0:7272',$context);
$worker->transport = 'tcp';

$worker->count = 4;
$worker->name = 'Huobi_wssv2';
$worker->onWorkerStart = function($worker)
{

    global $ws_data_source_url;

    // Asynchronously establish a connection to the actual mysql server
    $con = new AsyncTcpConnection($ws_data_source_url);
    $con->transport = 'ssl';

    $con->onConnect = function($con) {


$hbrds= new SocketRedis("127.0.0.1",6379);


        //Market Chart + Depth

		$markets=MARKETS_WS_SOCKET;
		
        $klintime=["1min","5min","15min","30min","60min","4hour","1day","1mon","1week","1year"];
		//  $klintime=[];
        foreach ($markets as $value) {
			if(ALLOW_KLINE==1){
				foreach ($klintime as $v) {
					$data = json_encode([                         //Kline Quotes
						'sub' => "market." . $value . ".kline." . $v,
						'id' => "id" . time(),
						'freq-ms' => 5000
					]);
					$con->send($data);
				}
			}

            //Depth
             $depth = json_encode([
                 'sub' => "market.".$value.".depth.step1",
                 'id' => $value."dep" . time()
             ]);
             $con->send($depth);
			
			
             //Transaction Record
             $trade = json_encode([
                 'sub' => "market.".$value.".trade.detail",
                 'id' => $value."trade" . time()
             ]);
             $con->send($trade);

            //24H head
             $detail = json_encode([
                 'sub' => "market.".$value.".detail",
                 'id' => $value."detail" . time()
             ]);
             $con->send($detail);
        };
		
    };
    // When the mysql connection sends data, it forwards the connection to the corresponding client
    $con->onMessage = function($con, $data)use($worker)
    {
		
        $data = gzdecode($data);
        $data = json_decode($data, true);
		
        if(isset($data['ping'])) {
            $con->send(json_encode([
                "pong" => $data['ping']
            ]));
        }else{


            /*  tick Description
             * "tick": {
                "id": Kline id,
                "amount": amount vol,
                "count": num of tx,
                "open": opening price,
                "close": closing price,when the K line is the latest one, it is the latest transaction price
                "low": lowest price,
                "high": highest price,
                "vol":The turnover, that is, sum (each transaction price * the transaction volume)
              }
             *
             *
             * */

            $msg=[];
               file_put_contents("./deta000.txt",var_export($data,true)."%%-----------\n",FILE_APPEND);

            
            if(isset($data['ch'])) {
				$hbrds= new SocketRedis("127.0.0.1",6379);
                $pieces = explode(".", $data['ch']);
                switch ($pieces[2]) {
                    case "kline":              //Market Graph
                        $msg['type'] = "tradingview";
                        $msg['market'] = $pieces[1];  //Huobi
                        $msg['open'] = $data['tick']['open'];
                        $msg['close'] = $data['tick']['close'];
                        $msg['low'] = $data['tick']['low'];
                        $msg['vol'] = $data['tick']['vol'];
                        $msg['high'] = $data['tick']['high'];
                        $msg['count'] = $data['tick']['count'];
                        $msg['amount'] = $data['tick']['amount'];
                        $msg['time'] = $data['tick']['id'];

                        //Insert data into redis

                        $table = $data['ch'];  //Setting up the hash table

                        $datarid = $msg;

                        $msg['period'] = $pieces[3];  //Staging

                        $datarid['type'] = $pieces[3];


                        //Check first
                        $rs = $hbrds->SeachId($table, $data['tick']['id']);


                        if ($rs == 1) {            //Update if equal
                            $hbrds->write($table, $datarid);
                        } else {
                            echo $table."\n";
                            //Update or insert other types
                            $insetinfo = $hbrds->read($table);     //Read First
                            $hbrds->write($table, $datarid);       //then overwrite the original
                            if(count($insetinfo)>1){               //Insert data into database
                        //        $insertmysql = $hbrds->insertmysql("lara_kline_" . $pieces[1], $insetinfo);//The data read is inserted into the data table

                            }


                        }

                        break;
                    case "depth" :   //Depth
                        $msg['type'] = "depth";
                        $msg['market'] = $pieces[1];  //Huobi
                        $msg['buy'] = [];  //Buy
                        $msg['sell'] = [];  //sell
                        $bids = $data['tick']['bids'];
                        $asks = $data['tick']['asks'];
						$msg['buyvol']=0;
						$msg['sellvol']=0;
                        for ($i = 0; $i < count($bids); $i++) {  //Buy orders
                            
                            $msg['buy'][$i]=array( $bids[$i][0],$bids[$i][1]);
                            if ($i == 0) {
                                $the_total_bid = $bids[$i][1];
                            } else {
                                $the_total_bid = $bids[$i][1] + $bids[$i - 1][1];
                            }
							$msg['buyvol']=$msg['buyvol']+ $the_total_bid;
                        }

                        for ($i = 0; $i < count($asks); $i++) {  //Sell Orders
                            $msg['sell'][$i] = array($asks[$i][0],$asks[$i][1]);
                            
                            if ($i == 0) {
                                $the_total_asks = $asks[$i][1];
                            } else {
                                $the_total_asks = $asks[$i][1] + $asks[$i - 1][1];
                            }
							
							$msg['sellvol']=$msg['sellvol']+ $the_total_asks;
                        }
						$buysell['buy']=$bids[0][0];
						$buysell['sell']=$asks[0][0];
						
						$hbrds->write('buysell_'.$pieces[1],$buysell );
						$msg['sell']=array_reverse($msg['sell']);
                        break;
                    case "trade":     //Real-time deals
                        $msg['type'] = "tradelog";
                        $msg['market'] = $pieces[1];  //Currency pair
                        $msg['id'] = $data['tick']['ts'];
                        $msg['price'] = round($data['tick']['data'][0]['price'],8);
						$newprice[$pieces[1]]=round($data['tick']['data'][0]['price'],8);
						$hbrds->write('newprice',$newprice);
                        $msg['num'] = $data['tick']['data'][0]['amount'];
                        if ($data['tick']['data'][0]['direction'] == "sell") {
                            $msg['trade_type'] = 2;
                        } else {
                            $msg['trade_type'] = 1;
                        }
                        $msg['time'] = substr($data['tick']['data'][0]['ts'], 0, 10);
                        break;

                        case "detail":
						$msg['type'] = "newprice";	
						$msg['market'] = $pieces[1];
						$msg['buy_price']=$hbrds->read('buysell_'.$pieces[1])['buy'];
						$msg['sell_price']=$hbrds->read('buysell_'.$pieces[1])['sell'];
                        $msg['new_price'] =$hbrds->read('newprice')[$pieces[1]];
                        $msg['change'] =round((($data['tick']['close']-$data['tick']['open'])/$data['tick']['open'])*100,2);
                        $msg['max_price'] =$data['tick']['high'];  //High
                        $msg['min_price'] =$data['tick']['low'];  //low
                        $msg['open'] =$data['tick']['open'];       //open
                        $msg['close'] =$data['tick']['close'];     //close
                        $msg['id'] =$data['tick']['id'];             //id number
                        $msg['count'] =$data['tick']['count'];      //num of tx
                        $msg['amount'] =$data['tick']['amount'];     //vol
                        $msg['version'] =$data['tick']['version'];   //
                        $msg['volume'] =$data['tick']['vol'];         //24h turnover
						$read_market_prices[$msg['market']]=array('new_price'=>$msg['new_price'],'buy_price'=>$msg['buy_price'],'sell_price'=>$msg['sell_price'],'min_price'=>$msg['min_price'],'max_price'=>$msg['max_price'],'volume'=>$msg['volume'],'change'=>$msg['change']);
						break;
                }
				
				

            }

            foreach($worker->connections as $conn)  //If it is the websock protocol, this can be sent to the client here.
            {
                $conn->send(json_encode($msg));
				
            }




        }
    };


    $con->onClose = function($con) {
        //If disconnected, reconnect after 1 second
        $con->reConnect(1);
    };


    // Perform an asynchronous connection
    $con->connect();
};

// Run worker
//Worker::runAll();
if(!defined('GLOBAL_START'))
{
    Worker::runAll();
}