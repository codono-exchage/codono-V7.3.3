<?php
// Display errors.
ini_set('display_errors', 'off');
// Reporting all.
error_reporting(1);
header("Access-Control-Allow-Origin: *");
// Defined encoding
header("Content-Type: text/html;charset=utf-8");
const DS = DIRECTORY_SEPARATOR;
defined('EXCHANGE_DIR') or define('EXCHANGE_DIR' , dirname(__FILE__, 2));
defined('CODEBASE_DIR') or define('CODEBASE_DIR', EXCHANGE_DIR . DS.'codebase');
defined('WEBSERVER_DIR') or define('WEBSERVER_DIR', getcwd());
const CODONO_VERSION = '7.7.0';
// Application path
const APP_PATH = CODEBASE_DIR.DS.'Application'.DS;
// DB Backup Path
const DATABASE_PATH = CODEBASE_DIR.DS.'Database'.DS;

// Cache path
const RUNTIME_PATH = CODEBASE_DIR.DS.'Runtime'.DS;
// Upload Images Path
const UPLOAD_PATH = '.'.DS.'Upload'.DS;


if (file_exists(CODEBASE_DIR.DS.'pure_config.php') && file_exists(CODEBASE_DIR.DS.'other_config.php')) {
    include_once(CODEBASE_DIR.DS.'pure_config.php');
	include_once(CODEBASE_DIR.DS.'other_config.php');
} else {
    die('Your Exchange is\'nt setup properly , Please look into config');
}

require CODEBASE_DIR.DS.'Framework'.DS.'codono.php';