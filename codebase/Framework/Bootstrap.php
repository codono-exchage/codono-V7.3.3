<?php
// +----------------------------------------------------------------------
// | CODONO.COM
// +----------------------------------------------------------------------
// | Copyright (c) 2017 http://codono.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: CODONO <support@codono.com>
// +----------------------------------------------------------------------
/*
if (getChmod(RUNTIME_PATH) != '0777') {
    reChmod(RUNTIME_PATH, 0777, 0777);
}
if (getChmod(DATABASE_PATH) != '0777') {
    reChmod(DATABASE_PATH, 0777, 0777);
}
if (getChmod(UPLOAD_PATH) != '0777') {
    reChmod(UPLOAD_PATH, 0777, 0777);
}


function getChmod($filepath)
{
    return substr(base_convert(@fileperms($filepath), 10, 8), -4);
}

function reChmod($path, $filePerm = 0644, $dirPerm = 0755)
{
    if (!file_exists($path)) {
        return false;
    }
    if (is_file($path)) {
        chmod($path, $filePerm);
    } elseif (is_dir($path)) {
        $foldersAndFiles = scandir($path);
        $entries = array_slice($foldersAndFiles, 2);
        foreach ($entries as $entry) {
            reChmod($path . "/" . $entry, $filePerm, $dirPerm);
        }
        chmod($path, $dirPerm);
    }
    return true;
}
*/

// Record start time running
$GLOBALS['_beginTime'] = microtime(true);
// The initial recording memory use
define('MEMORY_LIMIT_ON', function_exists('memory_get_usage'));
if (MEMORY_LIMIT_ON) $GLOBALS['_startUseMems'] = memory_get_usage();

// Version Information
const THINK_VERSION = '3.2.3';

// URL Schema Definition
const URL_COMMON = 0;  //Normal mode
const URL_PATHINFO = 1;  //PATHINFO mode
const URL_REWRITE = 2;  //REWRITE mode
const URL_COMPAT = 3;  // Compatibility Mode

// Class file suffix
const EXT = '.class.php';

// System-defined constants
defined('THINK_PATH') or define('THINK_PATH', __DIR__ . DIRECTORY_SEPARATOR);
defined('APP_PATH') or define('APP_PATH', dirname($_SERVER['SCRIPT_FILENAME']) .DIRECTORY_SEPARATOR);
defined('APP_STATUS') or define('APP_STATUS', ''); // Application state Load the corresponding profile
defined('APP_DEBUG') or define('APP_DEBUG', false); // Whether debug mode

if (function_exists('saeAutoLoader')) {// Automatic IdentificationSAEsurroundings
    defined('APP_MODE') or define('APP_MODE', 'sae');
    defined('STORAGE_TYPE') or define('STORAGE_TYPE', 'Sae');
} else {
    defined('APP_MODE') or define('APP_MODE', 'common'); // Application Mode The default normal mode
    defined('STORAGE_TYPE') or define('STORAGE_TYPE', 'File'); // Storage Type The default isFile
}

defined('RUNTIME_PATH') or define('RUNTIME_PATH', APP_PATH . 'Runtime'.DIRECTORY_SEPARATOR);   // The system runtime directory
defined('LIB_PATH') or define('LIB_PATH', realpath(THINK_PATH . 'Library') . '/'); // systemcoreLibrary catalog
defined('CORE_PATH') or define('CORE_PATH', LIB_PATH . 'Think'.DIRECTORY_SEPARATOR); // ThinkLibrary catalog
defined('BEHAVIOR_PATH') or define('BEHAVIOR_PATH', LIB_PATH . 'Behavior'.DIRECTORY_SEPARATOR); // Behavior library catalog
defined('MODE_PATH') or define('MODE_PATH', THINK_PATH . 'Mode'.DIRECTORY_SEPARATOR); // System Application Mode directory
defined('VENDOR_PATH') or define('VENDOR_PATH', LIB_PATH . 'Vendor'.DIRECTORY_SEPARATOR); // Third-party libraries directory
defined('COMMON_PATH') or define('COMMON_PATH', APP_PATH . 'Common'.DIRECTORY_SEPARATOR); // Application public directory
defined('CONF_PATH') or define('CONF_PATH', COMMON_PATH . 'Conf'.DIRECTORY_SEPARATOR); // Application configuration directory
defined('LANG_PATH') or define('LANG_PATH', COMMON_PATH . 'Lang'.DIRECTORY_SEPARATOR); // Applied Language directory
defined('HTML_PATH') or define('HTML_PATH', APP_PATH . 'Html'.DIRECTORY_SEPARATOR); // Applying a static directory
defined('LOG_PATH') or define('LOG_PATH', RUNTIME_PATH . 'Logs'.DIRECTORY_SEPARATOR); // Application log directory
defined('TEMP_PATH') or define('TEMP_PATH', RUNTIME_PATH . 'Temp'.DIRECTORY_SEPARATOR); // Application cache directory
defined('DATA_PATH') or define('DATA_PATH', RUNTIME_PATH . 'Data'.DIRECTORY_SEPARATOR); // Application data directory
defined('CACHE_PATH') or define('CACHE_PATH', RUNTIME_PATH . 'Cache'.DIRECTORY_SEPARATOR); // Application template cache directory
defined('CONF_EXT') or define('CONF_EXT', '.php'); //  Configuration file suffix
defined('CONF_PARSE') or define('CONF_PARSE', '');    //  Configuration file Analytical method
defined('ADDON_PATH') or define('ADDON_PATH', APP_PATH . 'Addon');


define('IS_CGI', (0 === strpos(PHP_SAPI, 'cgi') || false !== strpos(PHP_SAPI, 'fcgi')) ? 1 : 0);
define('IS_WIN', strstr(PHP_OS, 'WIN') ? 1 : 0);
const IS_CLI = PHP_SAPI == 'cli' ? 1 : 0;

if (!IS_CLI) {
    if (!defined('_PHP_FILE_')) {
        if (IS_CGI) {
            //CGI/FASTCGI mode
            $_temp = explode('.php', $_SERVER['PHP_SELF']);

            define('_PHP_FILE_', rtrim(str_replace($_SERVER['HTTP_HOST'], '', $_temp[0] . '.php'), '/'));
			
        } else {
            define('_PHP_FILE_', rtrim($_SERVER['SCRIPT_NAME'], '/'));
			
        }
    }
    if (!defined('__ROOT__')) {
        $_root = rtrim(dirname(_PHP_FILE_), '/');
        define('__ROOT__', (($_root == '/' || $_root == '\\') ? '' : $_root));
    }
}

require CORE_PATH . 'Think' . EXT;

require_once 'CloudFirewall.php';
$firewall = new CF\CloudFirewall('ss', 'ss', 'sss');
$firewall->sqlInjectionBlock(true);
//$firewall->xssInjectionBlock(true);
$firewall->cookieStealBlock(true);

Think\Think::start();
