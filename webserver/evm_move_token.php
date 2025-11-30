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

require_once '../codebase/SocketBot/vendor/autoload.php';
include_once('../codebase/pure_config.php');
include_once('../codebase/other_config.php');

$task = new Worker();
$task->count = 1;
$task->onWorkerStart = function($task)
{
	 $jy_interval = 5;
    Timer::add($jy_interval, function()
    {
	//   echo "I am running ";
//		echo PHP_PATH . " index.php Coin/BinanceUpdate/securecode/cronkey";
		$run[0]=system(PHP_PATH . " index.php Home/Coin/esmart_move_all_tokens_to_main/securecode/".CRON_KEY);
		$run[1]= "Task1 ran";
		    //file_put_contents("./task.txt",var_export($run,true)."%%-----------\n",FILE_APPEND);
    });
};
 
// run worker
Worker::runAll();