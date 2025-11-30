<?php
/**
 * Performace check required
 * run with command 
 * php start.php start
 */

require_once __DIR__ . '/SocketBot/vendor/autoload.php';
include_once('pure_config.php');
include_once('other_config.php');

ini_set('display_errors', 'on');
ini_set('serialize_precision',14); //Prevent json_encode precision problems with floating point numbers above php7.1
use Workerman\Worker;
use Workerman\Timer;


if(strpos(strtolower(PHP_OS), 'win') === 0)
{
  //  exit("start.php not support windows, please use start_for_win.bat\n");
}

// Check extensions
if(!extension_loaded('pcntl'))
{
//    exit("Please install pcntl extension. See http://doc3.workerman.net/appendices/install-extension.html\n");
}

if(!extension_loaded('posix'))
{
  //  exit("Please install posix extension. See http://doc3.workerman.net/appendices/install-extension.html\n");
}

// Flag is global startup
define('GLOBAL_START', 1);


// Load all Applications / * / start.php to start all services
foreach(glob(__DIR__.'/SocketBot/*/self_*.php') as $start_file)
{
    require_once $start_file;
}

// Print the screen to the file specified by Worker::$stdoutFile
Worker::$stdoutFile = getcwd().'huobiapi_stdout.log';

// Run all services
Worker::runAll();