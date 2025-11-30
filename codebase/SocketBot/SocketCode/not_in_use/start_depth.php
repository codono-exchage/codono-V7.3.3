<?php
require_once dirname(dirname(dirname(__FILE__))).'/pure_config.php';
require_once dirname(dirname(__FILE__)).'/vendor/autoload.php';
use Workerman\Redis\Client;
use Workerman\Worker;
use Workerman\Timer;

$market=@$_GET['market'];

$context = array(
    'ssl' => array(
        //'local_cert'  => '/pathto/apache1.crt',
        //  'local_pk'    => '/pathto/apache1.key',
        'verify_peer' => false,
        'verify_peer_name' => false,
        // 'allow_self_signed' => true
    )
);
$worker = new Worker('websocket://0.0.0.0:7272',$context);
$worker->onWorkerStart = function() {
    global $redis;
    $redis = new Client('redis://127.0.0.1:6379');

};

$worker->onMessage = function($connection,$data) {

    global $redis;
    global $market;
    $pos=strpos($data,'market:');

    if(!isset($pos)){
        echo "no market yet";
        return false;
    }else{
        $whole=explode('market:',$data);
        if(!isset($whole[1])){
            return false;
        }
        $market=$whole[1];
    }
    $redis = new Client('redis://127.0.0.1:6379');
    $time_interval = 2.5;
    $args=array('redis'=>$redis,'market'=>$market);
    Timer::add($time_interval, function () use ($connection, $args) {
        $redis=$args['redis'];
        $market=$args['market'];
        $depth_key=CRON_KEY.'_getActiveDepth'.$market;
        $redis->get($depth_key, function ($result) use ($market, $connection) {
            //echo "Ran:".time();
            $info=json_decode($result,true);
			$sign1=rand(0,1);
			$sign2=rand(0,1);
			$sign3=rand(0,1);
			$sign4=rand(0,1);
			
			if($sign1>0){
			$r1=bcadd(1,bcdiv(rand(1000,9999),100000,8),8);
			}else{
			$r1=bcadd(1,bcdiv(rand(10000,99999),1000000,8),8);
			}
			
			if($sign2>0){
			$r2=bcadd(1,bcdiv(rand(1000,9999),100000,8),8);
			}else{
			$r2=bcadd(1,bcdiv(rand(10000,99999),1000000,8),8);
			}
			
			if($sign3>0){
			$r3=bcsub(1,bcdiv(rand(1000,9999),100000,8),8);
			}else{
			$r3=bcsub(1,bcdiv(rand(10000,99999),1000000,8),8);
			}
			
			if($sign4>0){
			$r4=bcsub(1,bcdiv(rand(1000,9999),100000,8),8);
			}else{
			$r4=bcsub(1,bcdiv(rand(10000,99999),1000000,8),8);
			}
			
			$the_sells=$info[$market][1]['depth']['sell'];
			$the_buys=$info[$market][1]['depth']['buy'];
			foreach($the_sells as $the_sell){
				$the_sell[0]=bcmul($the_sell[0],$r1,8);
				$the_sell[1]=bcmul($the_sell[1],$r2,8);
				$final_sells[]=$the_sell;
			}
			foreach($the_buys as $the_buy){
				$the_buy[0]=bcmul($the_buy[0],$r3,8);
				$the_buy[1]=bcmul($the_buy[1],$r4,8);
				$final_buys[]=$the_buy;
			}
			$info[$market][1]['depth']['sell']=$final_sells;
			$info[$market][1]['depth']['buy']=$final_buys;
            if(isset($info[$market][1]['depth'])) {
                $resp['sell'] = $info[$market][1]['depth']['sell'];
                $resp['buy'] = $info[$market][1]['depth']['buy'];
                $resp['buyvol'] = $info[$market][1]['buyvol'];
                $resp['sellvol'] = $info[$market][1]['sellvol'];
                $resp['type'] = 'depth';
                $resp['market'] = $market;
                $connection->send(json_encode($resp));
            }

        });
    });
return true;
};

Worker::runAll();