<?php
/**
 * Performace check required
 * run with command 
 * php start.php start
 */

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

require_once __DIR__ . '/SocketBot/vendor/autoload.php';
include_once('pure_config.php');
include_once('other_config.php');
echo "Insant Mail Daemon";
$task = new Worker();
$task->count = 1;
$task->onWorkerStart = function($task)
{
	 $jy_interval = 3;
    Timer::add($jy_interval, function()
    {
		
		$run[0]=system(PHP_PATH . " index.php Home/Queue/send_notifications/securecode/".CRON_KEY);
	
    });
};
 
// run worker
Worker::runAll();