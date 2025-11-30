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
$worker = new Worker('websocket://0.0.0.0:2346');


$worker->onMessage  = function($connection, $data) {

        $taskData = json_decode($data, true);

 if ($taskData && isset($taskData['market']) && isset($taskData['action'])) {
        switch ($taskData['action']) {
            case 'processTrade':
                // Dynamically include the TradeController

                    echo "Processing trade for market: ".$taskData['market']."\n";

                break;
            // Add more cases for other types of tasks
        }
    }

    // Send a response back to the sender, if necessary
    $connection->send('Task completed');
};

Worker::runAll();