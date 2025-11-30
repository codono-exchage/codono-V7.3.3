<?php

use Workerman\Worker;

use Workerman\Connection\AsyncTcpConnection;
use Workerman\Redis\Client;

require_once dirname(__FILE__, 2) . '/vendor/autoload.php';

/**
 * @return Worker
 */
function getWorker(): Worker
{
    require_once __DIR__ . '/SocketRedis.class.php';
    //Replace correct crt and pk file path below to allow websocket run in wss format
    $context = [
        'ssl' => [
            //'local_cert'  => '/path_to/apache1.crt',
            //'local_pk'    => '/path_to/apache1.key',
            'verify_peer' => false,
            'verify_peer_name' => false,
            // 'allow_self_signed' => true
        ]
    ];



//Proxy listening on local 7272 port
    $worker = new Worker('websocket://0.0.0.0:7272', $context);
    $worker->transport = 'tcp';

    $worker->count = 4;
    $worker->name = 'Huobi_wssV2';
    return $worker;
}

$worker = getWorker();
$worker->onWorkerStart = function ($worker) {

    global $huobi_ws_feed;
    global $redis;
    $huobi_ws_feed = 'ws://api.huobi.pro:443/ws';
    $redis = new Client('redis://127.0.0.1:6379');
    $redis->auth(REDIS_PASSWORD, function ($result) use ($redis) {
        //var_dump($result);
        if ($result !== true) {
          //  echo "Failed to authenticate with Redis server\n";
        }
    });
    // Asynchronously establish a connection to the actual mysql server
    $con = new AsyncTcpConnection($huobi_ws_feed);
    $con->transport = 'ssl';


    $con->onConnect = function ($con) {
        global $redis;
        $redis_key = CRON_KEY . '_ext_socket';
        $redis->get($redis_key, function ($marks) use ($con) {

            $markets = json_decode($marks);

            //Market Chart + Depth
            //$markets = MARKETS_WS_SOCKET;
            //  $klintime=["1min","5min","15min","30min","60min","4hour","1day","1mon","1week","1year"];
            $klintime = [];
            if (!empty($markets)) {
                foreach ($markets as $mark) {
                    $value = str_replace('_', '', $mark);
                    //since klintime is empty thus this foreach would not execute
                    if (!empty($klintime)) {
                        foreach ($klintime as $v) {
                            $data = json_encode([                         //Kline Quotes
                                'sub' => 'market.' . $value . '.kline.' . $v,
                                'id' => 'id' . time(),
                                'freq-ms' => 5000
                            ]);
                            $con->send($data);
                        }
                    }

                    //Deoth
                    $depth = json_encode([
                        'sub' => 'market.' . $value . '.depth.step1',
                        'id' => $value . 'dep' . time()
                    ]);
                    $con->send($depth);

                    //Transaction Record
                    $trade = json_encode([
                        'sub' => 'market.' . $value . '.trade.detail',
                        'id' => $value . 'trade' . time()
                    ]);
                    $con->send($trade);

                    //24H head
                    $detail = json_encode([
                        'sub' => 'market.' . $value . '.detail',
                        'id' => $value . 'detail' . time()
                    ]);
                    $con->send($detail);
                }
            }
        });  //end redis loop
    };
    // When the mysql connection sends data, it forwards the connection to the corresponding client
    $con->onMessage = function ($con, $data) use ($worker) {


        $data = gzdecode($data);
        $data = json_decode($data, true);
        if (!is_array($data) || !isset($data['ch'])) {
            // Handle invalid data
         //   echo "Invalid data received\n";
            return;
        }
        if (isset($data['ping'])) {
            $con->send(json_encode([
                'pong' => $data['ping']
            ]));
        } else {
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

            $msg = [];
//                file_put_contents("./deta000.txt",var_export($data,true)."%%-----------\n",FILE_APPEND);


            if (isset($data['ch'])) {
                $hbrds = new SocketRedis('127.0.0.1', 6379, REDIS_PASSWORD);
                $pieces = explode('.', $data['ch']);
                switch ($pieces[2]) {
                    case 'kline':              //Market Graph
                        $msg['type'] = 'tradingview';
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


                        if ($rs != 1) {
                            echo $table . "\n";
                            //Update or insert other types
                            $hbrds->read($table);     //Read First
                            //then overwrite the original
                        }
                        $hbrds->write($table, $datarid);

                        break;
                    case 'depth' :   //Depth
                        $msg['type'] = 'depth';
                        $msg['market'] = $pieces[1];  //Huobi
                        $msg['buy'] = [];  //Buy
                        $msg['sell'] = [];  //sell
                        $bids = $data['tick']['bids'];
                        $asks = $data['tick']['asks'];
                        $msg['buyvol'] = 0;
                        $msg['sellvol'] = 0;
                        for ($i = 0; $i < count($bids); $i++) {  //Buy orders

                            $msg['buy'][$i] = [$bids[$i][0], $bids[$i][1]];
                            if ($i == 0) {
                                $the_total_bid = $bids[$i][1];
                            } else {
                                $the_total_bid = $bids[$i][1] + $bids[$i - 1][1];
                            }
                            $msg['buyvol'] = $msg['buyvol'] + $the_total_bid;
                        }

                        for ($i = 0; $i < count($asks); $i++) {  //Sell Orders
                            $msg['sell'][$i] = [$asks[$i][0], $asks[$i][1]];

                            if ($i == 0) {
                                $the_total_asks = $asks[$i][1];
                            } else {
                                $the_total_asks = $asks[$i][1] + $asks[$i - 1][1];
                            }

                            $msg['sellvol'] = $msg['sellvol'] + $the_total_asks;
                        }
                        $buysell['buy'] = $bids[0][0];
                        $buysell['sell'] = $asks[0][0];

                        $hbrds->write('buysell_' . $pieces[1], $buysell);
                        $msg['sell'] = array_reverse($msg['sell']);
                        break;
                    case 'trade':     //Real-time deals
                        $msg['type'] = 'tradelog';
                        $msg['market'] = $pieces[1];  //Currency pair
                        $msg['id'] = $data['tick']['ts'];
                        $msg['price'] = round($data['tick']['data'][0]['price'], 8);
                        $newprice[$pieces[1]] = round($data['tick']['data'][0]['price'], 8);
                        $hbrds->write('newprice', $newprice);
                        $msg['num'] = $data['tick']['data'][0]['amount'];
                        if ($data['tick']['data'][0]['direction'] == 'sell') {
                            $msg['trade_type'] = 2;
                        } else {
                            $msg['trade_type'] = 1;
                        }
                        $msg['time'] = $data['tick']['data'][0]['ts'];//date('Y-m-d H:i:s', $data['tick']['ts'] / 1000);
                        $msg['date'] = date('m-d H:i:s', $data['tick']['ts'] / 1000);
                        //substr($data['tick']['data'][0]['ts'], 0, 13);
                        break;

                    case 'detail':
                        $msg['type'] = 'newprice';
                        $msg['market'] = $pieces[1];
                        // Ensuring 'buy' and 'sell' indices exist before accessing them
						if ($hbrdsData = $hbrds->read('buysell_' . $pieces[1])) {
							// Use a default value or handle the case where 'buy' is not set
							$msg['buy_price'] = isset($hbrdsData['buy']) ? $hbrdsData['buy'] : null;  
							// Use a default value or handle the case where 'sell' is not set
							$msg['sell_price'] = isset($hbrdsData['sell']) ? $hbrdsData['sell'] : null;  
						} else {
							$msg['buy_price'] = null;  // Default value if $hbrdsData is not available
							$msg['sell_price'] = null;  // Default value if $hbrdsData is not available
						}
                        
                        $msg['new_price'] = $hbrds->read('newprice')[$pieces[1]];
                        $msg['change'] = round((($data['tick']['close'] - $data['tick']['open']) / $data['tick']['open']) * 100, 2);
                        $msg['max_price'] = $data['tick']['high'];  //High
                        $msg['min_price'] = $data['tick']['low'];  //low
                        $msg['open'] = $data['tick']['open'];       //open
                        $msg['close'] = $data['tick']['close'];     //close
                        $msg['id'] = $data['tick']['id'];             //id number
                        $msg['count'] = $data['tick']['count'];      //num of tx
                        $msg['amount'] = $data['tick']['amount'];     //vol
                        $msg['version'] = $data['tick']['version'];   //
                        $msg['volume'] = $data['tick']['vol'];         //24h turnover
                        //$read_market_prices[$msg['market']] = array('new_price' => $msg['new_price'], 'buy_price' => $msg['buy_price'], 'sell_price' => $msg['sell_price'], 'min_price' => $msg['min_price'], 'max_price' => $msg['max_price'], 'volume' => $msg['volume'], 'change' => $msg['change']);
                        break;
                }


            }

            foreach ($worker->connections as $conn)  //If it is the websock protocol, this can be sent to the client here.
            {
                $conn->send(json_encode($msg));

            }


        }
    };


    $con->onClose = function ($con) {
        //If disconnected, reconnect after 1 second
        $con->reConnect(1);
    };


    // Perform an asynchronous connection
    $con->connect();
};

// Run worker
//Worker::runAll();
if (!defined('GLOBAL_START')) {
    Worker::runAll();
}