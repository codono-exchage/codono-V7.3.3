<?php
require_once dirname(dirname(dirname(__FILE__))) . '/pure_config.php';
require_once dirname(dirname(__FILE__)) . '/vendor/autoload.php';

use Workerman\Redis\Client;
use Workerman\Worker;
use Workerman\Timer;


$context = array(
    'ssl' => array(
        //'local_cert'  => '/path_to/apache1.crt',
        //  'local_pk'    => '/path_to/apache1.key',
        'verify_peer' => false,
        'verify_peer_name' => false,
        // 'allow_self_signed' => true
    )
);
$worker = new Worker('websocket://0.0.0.0:7272', $context);
$worker->onWorkerStart = function () {
    global $redis;
    $redis = new Client('redis://127.0.0.1:6379');

};

$worker->onMessage = function ($connection, $data) {

    global $redis;
    global $market;
    $pos = strpos($data, 'market:');

    if (!isset($pos)) {
        echo "no market yet";
        return false;
    } else {
        $whole = explode('market:', $data);
        if (!isset($whole[1])) {
            return false;
        }
        $market = $whole[1];
    }
    $redis = new Client('redis://127.0.0.1:6379');

    $time_interval = 2.5;
    $args = array('redis' => $redis, 'market' => $market);
    Timer::add($time_interval, function () use ($connection, $args) {

        $redis = $args['redis'];

        $market = $args['market'];
        $tradelog = CRON_KEY . '_getTradelog' . $market;

        $redis->get($tradelog, function ($result) use ($market, $connection) {
            //echo "Ran:".time();
            $info = json_decode($result, true);
            if (isset($info['tradelog'][0])) {
                $resp['tradelog'] = $info['tradelog'];
                $resp['type'] = 'tradelog';
                $resp['market'] = $market;
                $connection->send(json_encode($resp));
            }

        });
    });
    return true;
};

Worker::runAll();