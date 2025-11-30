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
if(!@$argv['1']){
	exit('Please pass first argv as command example Coin/esmart_deposit');
}
require_once __DIR__ . '/SocketBot/vendor/autoload.php';
include_once('pure_config.php');
include_once('other_config.php');

$url="Home/".$argv[1].'/securecode/'.CRON_KEY;
$interval=$argv[2]?:30;
$task = new Worker();
$task->count = 1;
$task->onWorkerStart = function($task) use ($url,$interval)
{

    Timer::add($interval, function() use($url)
    {
        exec_php_url(PHP_PATH . " index.php ".$url);
		/*
		$run[0] =$url;
		$run[1]=time();
		    file_put_contents("./deta000.txt",var_export($run,true)."%%-----------\n",FILE_APPEND);
		*/
    });
};
 
function exec_php_url($cmd) {
       if (substr(php_uname(), 0, 7) == "Windows"){
		   exec($cmd);
        //pclose(popen($cmd, "r")); 
    }
    else {
        exec($cmd . " > /dev/null &");  
    }
}
 
 
// run worker
Worker::runAll();