<?php

use Workerman\Worker;
use Workerman\Connection\AsyncTcpConnection;
use Workerman\Redis\Client;

require_once dirname(__FILE__, 2) . '/vendor/autoload.php';
// Set the date-based log file


// Utility function to write messages to the log file
function writeToLog($message ) {
    $date_ws_log = dirname(__FILE__,3) . '/Database/Log/ws_log_' . date('Y-m-d') . '.log';
    $formattedMessage = "[" . date('Y-m-d H:i:s') . "] " . $message . PHP_EOL;
    file_put_contents($date_ws_log, $formattedMessage, FILE_APPEND);
}
function getWorker(): Worker
{
    // SSL Context (adjust if needed)
    $context = [
        'ssl' => [
            'verify_peer' => false,
            'verify_peer_name' => false
        ]
    ];

    // Initialize a WebSocket worker
    $worker = new Worker('websocket://0.0.0.0:7272', $context);
    $worker->transport = 'tcp';
    $worker->count = 4;
    $worker->name = 'Huobi_wssV2';
    return $worker;
}

$worker = getWorker();
$worker->onWorkerStart = function ($worker) {
    global $huobi_ws_feed, $redis;
    $huobi_ws_feed = 'ws://api.huobi.pro:443/ws';
    $redis = new Client('redis://127.0.0.1:6379');

    // Authenticate Redis connection
    $redis->auth(REDIS_PASSWORD, function ($result) {
        if ($result !== true) {
            writeToLog("Failed to authenticate with Redis server\n");
        } else {
            writeToLog("Successfully authenticated with Redis server\n");
        }
    });

    // Function to subscribe to market data
    function initializeMarketSubscriptions($connection)
    {
        global $redis;
        $redis_key = CRON_KEY . '_ext_socket';

        $redis->get($redis_key, function ($marks) use ($connection) {
            if (empty($marks)) {
                writeToLog("No markets found in Redis key ".$redis_key);
                return;
            }

            $markets = json_decode($marks);
            $klintime = [];

            if (!empty($markets)) {
                foreach ($markets as $mark) {
                    $value = str_replace('_', '', $mark);

                    // Market Depth
                    $depth = json_encode([
                        'sub' => 'market.' . $value . '.depth.step1',
                        'id' => $value . 'dep' . time()
                    ]);
                    $connection->send($depth);
                    writeToLog("Subscribed to market depth for $value");

                    // Market Trade Detail
                    $trade = json_encode([
                        'sub' => 'market.' . $value . '.trade.detail',
                        'id' => $value . 'trade' . time()
                    ]);
                    $connection->send($trade);
                    writeToLog("Subscribed to market trade detail for $value");

                    // Market 24H Detail
                    $detail = json_encode([
                        'sub' => 'market.' . $value . '.detail',
                        'id' => $value . 'detail' . time()
                    ]);
                    $connection->send($detail);
                    writeToLog("Subscribed to market 24H detail for $value");
                }
            } else {
                writeToLog("No markets available to subscribe.");
            }
        });
    }

    // Retry and Cooldown Configuration
    $retries = 0;
    $maxRetries = 5;

    function createConnection()
    {
        global $huobi_ws_feed, $worker, $retries, $maxRetries;
        $connection = new AsyncTcpConnection($huobi_ws_feed);
        $connection->transport = 'ssl';

        $connection->onConnect = function ($connection) {
            initializeMarketSubscriptions($connection);
        };

        $connection->onMessage = function ($connection, $data) use ($worker) {
            // Decode the incoming compressed data
            $data = gzdecode($data);
            $data = json_decode($data, true);
        
            // Check for the "ping" field and reply with "pong"
            if (isset($data['ping'])) {
                $connection->send(json_encode(['pong' => $data['ping']]));
           //     writeToLog("Responded to ping with pong.");
            } else {
                // Log the raw incoming data for debugging
            //    writeToLog("Received data: " . json_encode($data));
        
                // Check if the message contains a channel subscription message
                if (isset($data['ch'])) {
                    $msg = [];
                    $pieces = explode('.', $data['ch']);
                    switch ($pieces[2]) {
                        case 'kline': // Handle Kline data
                            $msg['type'] = 'tradingview';
                            $msg['market'] = $pieces[1];
                            $msg['open'] = $data['tick']['open'];
                            $msg['close'] = $data['tick']['close'];
                            $msg['low'] = $data['tick']['low'];
                            $msg['high'] = $data['tick']['high'];
                            $msg['vol'] = $data['tick']['vol'];
                            $msg['amount'] = $data['tick']['amount'];
                            $msg['time'] = $data['tick']['id'];
                            $msg['period'] = $pieces[3];
                            break;
        
                        case 'depth': // Handle Market Depth data
                            $msg['type'] = 'depth';
                            $msg['market'] = $pieces[1];
                            $msg['buy'] = array_map(fn($bid) => [$bid[0], $bid[1]], $data['tick']['bids']);
                            $msg['sell'] = array_reverse(array_map(fn($ask) => [$ask[0], $ask[1]], $data['tick']['asks']));
                            break;
        
                        case 'trade': // Handle Market Trade data
                            $msg['type'] = 'tradelog';
                            $msg['market'] = $pieces[1];
                            $msg['price'] = round($data['tick']['data'][0]['price'], 8);
                            $msg['num'] = $data['tick']['data'][0]['amount'];
                            $msg['trade_type'] = ($data['tick']['data'][0]['direction'] === 'sell') ? 2 : 1;
                            $msg['time'] = $data['tick']['data'][0]['ts'];
                            $msg['date'] = date('m-d H:i:s', $data['tick']['ts'] / 1000);
                            break;
        
                        case 'detail': // Handle 24-hour Market Details
                            $msg['type'] = 'newprice';
                            $msg['market'] = $pieces[1];
                            $msg['open'] = $data['tick']['open'];
                            $msg['close'] = $data['tick']['close'];
                            $msg['high'] = $data['tick']['high'];
                            $msg['low'] = $data['tick']['low'];
                            $msg['count'] = $data['tick']['count'];
                            $msg['amount'] = $data['tick']['amount'];
                            break;
                    }
        
                    // Broadcast the parsed message to all clients
                    foreach ($worker->connections as $conn) {
                        $conn->send(json_encode($msg));
                    }
                }
            }
        };
        
        $connection->onClose = function ($connection) {
            global $retries, $maxRetries;

            // Retry Logic with Cooldown
            if ($retries < $maxRetries) {
                $retries++;
                $retryDelay = pow(2, $retries);
                $connection->reConnect($retryDelay);
                writeToLog("Reconnecting in $retryDelay seconds...");
            } else {
                // Cooldown period of 30 seconds
                writeToLog("Max retries reached. Cooling down for 30 seconds...");
                sleep(30);
                $retries = 0; // Reset retry counter
                $connection->reConnect(1); // Retry connection after cooldown
            }
        };

        $connection->onError = function ($connection, $code, $msg) {
            writeToLog("Error ($code): $msg");
        };

        $connection->connect();
        return $connection;
    }

    // Create the WebSocket connection
    createConnection();
};

// Run worker
if (!defined('GLOBAL_START')) {
    Worker::runAll();
}