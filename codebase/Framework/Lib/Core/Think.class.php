<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2012 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: support@codono.com
// +----------------------------------------------------------------------

/**
 * ThinkPHP Portalclass
 * @category   Think
 * @package  Think
 * @subpackage  Core
 * @author    liu21st <liu21st@gmail.com>
 */
class Think
{

    private static $_instance = array();

    /**
     * Application Initialization
     * @access public
     * @return void
     */
    static public function start()
    {
        // set uperrorAnd abnormaldeal with
        register_shutdown_function(array('Think', 'fatalError'));
        set_error_handler(array('Think', 'appError'));
        set_exception_handler(array('Think', 'appException'));
        // registeredAUTOLOADmethod
        spl_autoload_register(array('Think', 'autoload'));
        //[RUNTIME]
        Think::buildApp();         // Precompiled project
        //[/RUNTIME]
        // runapplication
        App::run();
        return;
    }

    //[RUNTIME]

    /**
     * Read configuration information Compile the project
     * @access private
     * @return string
     */
    static private function buildApp()
    {

        // ReadRun mode
        if (defined('MODE_NAME')) { // ReadSetting mode
            $mode = include MODE_PATH . strtolower(MODE_NAME) . '.php';
        } else {
            $mode = array();
        }
        // The core loadConvention Profile
        C(include THINK_PATH . 'Conf/convention.php');
        if (isset($mode['config'])) {// Load mode profile
            C(is_array($mode['config']) ? $mode['config'] : include $mode['config']);
        }

        // Load the project configuration file
        if (is_file(CONF_PATH . 'config.php'))
            C(include CONF_PATH . 'config.php');

        // loadframeUnderlying language pack
        L(include THINK_PATH . 'Lang/' . strtolower(C('DEFAULT_LANG')) . '.php');

        // loadmodesystembehaviordefinition
        if (C('APP_TAGS_ON')) {
            if (isset($mode['extends'])) {
                C('extends', is_array($mode['extends']) ? $mode['extends'] : include $mode['extends']);
            } else { // defaultloadSystem behavior extensiondefinition
                C('extends', include THINK_PATH . 'Conf/tags.php');
            }
        }

        // loadapplicationbehaviordefinition
        if (isset($mode['tags'])) {
            C('tags', is_array($mode['tags']) ? $mode['tags'] : include $mode['tags']);
        } elseif (is_file(CONF_PATH . 'tags.php')) {
            // defaultloadProject configuration directoryoftagsFile definition
            C('tags', include CONF_PATH . 'tags.php');
        }

        $compile = '';
        // ReadcoreCompile fileList
        if (isset($mode['core'])) {
            $list = $mode['core'];
        } else {
            $list = array(
                THINK_PATH . 'Common/functions.php', // standardModel library
                CORE_PATH . 'Core/Log.class.php',    // Log Processing category
                CORE_PATH . 'Core/Dispatcher.class.php', // URLScheduling classes
                CORE_PATH . 'Core/App.class.php',   // Application class
                CORE_PATH . 'Core/Action.class.php', // Controller class
                CORE_PATH . 'Core/View.class.php',  // View class
            );
        }
        // projectadd tocoreCompile a list of files
        if (is_file(CONF_PATH . 'core.php')) {
            $list = array_merge($list, include CONF_PATH . 'core.php');
        }
        foreach ($list as $file) {
            if (is_file($file)) {
                require_cache($file);
                if (!APP_DEBUG) $compile .= compile($file);
            }
        }

        // Load project files public
        if (is_file(COMMON_PATH . 'common.php')) {
            include COMMON_PATH . 'common.php';
            // Compile file
            if (!APP_DEBUG) $compile .= compile(COMMON_PATH . 'common.php');
        }

        // loadmodeAlias definition
        if (isset($mode['alias'])) {
            $alias = is_array($mode['alias']) ? $mode['alias'] : include $mode['alias'];
            alias_import($alias);
            if (!APP_DEBUG) $compile .= 'alias_import(' . var_export($alias, true) . ');';
        }

        // Load project alias definitions
        if (is_file(CONF_PATH . 'alias.php')) {
            $alias = include CONF_PATH . 'alias.php';
            alias_import($alias);
            if (!APP_DEBUG) $compile .= 'alias_import(' . var_export($alias, true) . ');';
        }

        if (APP_DEBUG) {
            // debuggingmodeloadsystemdefault Configuration file 
            C(include THINK_PATH . 'Conf/debug.php');
            // ReaddebuggingModeApplication state
            $status = C('APP_STATUS');
            // loadcorrespondingproject Configuration file 
            if (is_file(CONF_PATH . $status . '.php'))
                // allowprojectincreaseDevelopmodeConfigurationdefinition
                C(include CONF_PATH . $status . '.php');
        } else {
            // deploymodethe followingA compiled file
            build_runtime_cache($compile);
        }
        return;
    }
    //[/RUNTIME]

    /**
     * The system automatically loadsThinkPHPClass Library
     * andstand byConfigurationAutomatically loadpath
     * @param string $class Objectsclassname
     * @return void
     */
    public static function autoload($class)
    {
        // an examinationdoes it existAlias definition
        if (alias_import($class)) return;
        $libPath = defined('BASE_LIB_PATH') ? BASE_LIB_PATH : LIB_PATH;
        $group = defined('GROUP_NAME') && C('APP_GROUP_MODE') == 0 ? GROUP_NAME . '/' : '';
        $file = $class . '.class.php';
        if (substr($class, -8) == 'Behavior') { // Load Behavior
            if (require_array(array(
                    CORE_PATH . 'Behavior/' . $file,
                    EXTEND_PATH . 'Behavior/' . $file,
                    LIB_PATH . 'Behavior/' . $file,
                    $libPath . 'Behavior/' . $file), true)
                || (defined('MODE_NAME') && require_cache(MODE_PATH . ucwords(MODE_NAME) . '/Behavior/' . $file))) {
                return;
            }
        } elseif (substr($class, -5) == 'Model') { // Load Model
            if (require_array(array(
                LIB_PATH . 'Model/' . $group . $file,
                $libPath . 'Model/' . $file,
                EXTEND_PATH . 'Model/' . $file), true)) {
                return;
            }
        } elseif (substr($class, -6) == 'Action') { // Load Controller
            if (require_array(array(
                LIB_PATH . 'Action/' . $group . $file,
                $libPath . 'Action/' . $file,
                EXTEND_PATH . 'Action/' . $file), true)) {
                return;
            }
        } elseif (substr($class, 0, 5) == 'Cache') { // Load the cache driver
            if (require_array(array(
                EXTEND_PATH . 'Driver/Cache/' . $file,
                CORE_PATH . 'Driver/Cache/' . $file), true)) {
                return;
            }
        } elseif (substr($class, 0, 2) == 'Db') { // Load the database driver
            if (require_array(array(
                EXTEND_PATH . 'Driver/Db/' . $file,
                CORE_PATH . 'Driver/Db/' . $file), true)) {
                return;
            }
        } elseif (substr($class, 0, 8) == 'Template') { // Load Template engine driver
            if (require_array(array(
                EXTEND_PATH . 'Driver/Template/' . $file,
                CORE_PATH . 'Driver/Template/' . $file), true)) {
                return;
            }
        } elseif (substr($class, 0, 6) == 'TagLib') { // Load tag library drive
            if (require_array(array(
                EXTEND_PATH . 'Driver/TagLib/' . $file,
                CORE_PATH . 'Driver/TagLib/' . $file), true)) {
                return;
            }
        }

        // according toAutomatically loadPath settingsTry searching
        $paths = explode(',', C('APP_AUTOLOAD_PATH'));
        foreach ($paths as $path) {
            if (import($path . '.' . $class))
                // in caseloadclassSuccess, returns
                return;
        }
    }

    /**
     * Made object instance stand bytransferCategoryStatic method
     * @param string $class Objectsclassname
     * @param string $method CategoryStatic methodname
     * @return object
     */
    static public function instance($class, $method = '')
    {
        $identify = $class . $method;
        if (!isset(self::$_instance[$identify])) {
            if (class_exists($class)) {
                $o = new $class();
                if (!empty($method) && method_exists($o, $method))
                    self::$_instance[$identify] = call_user_func_array(array(&$o, $method));
                else
                    self::$_instance[$identify] = $o;
            } else
                halt(L('_CLASS_NOT_EXIST_') . ':' . $class);
        }
        return self::$_instance[$identify];
    }

    /**
     * Custom exception handling
     * @access public
     * @param mixed $e Exception object
     */
    static public function appException($e)
    {
        $error = array();
        $error['message'] = $e->getMessage();
        $trace = $e->getTrace();
        if ('throw_exception' == $trace[0]['function']) {
            $error['file'] = $trace[0]['file'];
            $error['line'] = $trace[0]['line'];
        } else {
            $error['file'] = $e->getFile();
            $error['line'] = $e->getLine();
        }
        Log::record($error['message'], Log::ERR);
        halt($error);
    }

    /**
     * Custom Error Handling
     * @access public
     * @param int $errno Error type
     * @param string $errstr Error Messages
     * @param string $errfile Error file
     * @param int $errline Error number of lines
     * @return void
     */
    static public function appError($errno, $errstr, $errfile, $errline)
    {
        switch ($errno) {
            case E_ERROR:
            case E_PARSE:
            case E_CORE_ERROR:
            case E_COMPILE_ERROR:
            case E_USER_ERROR:
                ob_end_clean();
                // pagecompressionExportstand by
                if (C('OUTPUT_ENCODE')) {
                    $zlib = ini_get('zlib.output_compression');
                    if (empty($zlib)) ob_start('ob_gzhandler');
                }
                $errorStr = "$errstr " . $errfile . " The first $errline Row.";
                if (C('LOG_RECORD')) Log::write("[$errno] " . $errorStr, Log::ERR);
                function_exists('halt') ? halt($errorStr) : exit('ERROR:' . $errorStr);
                break;
            case E_STRICT:
            case E_USER_WARNING:
            case E_USER_NOTICE:
            default:
                $errorStr = "[$errno] $errstr " . $errfile . " The first $errline Row.";
                trace($errorStr, '', 'NOTIC');
                break;
        }
    }

    // fatalerrorcapture
    static public function fatalError()
    {
        // StorageJournalrecording
        if (C('LOG_RECORD')) Log::save();
        if ($e = error_get_last()) {
            switch ($e['type']) {
                case E_ERROR:
                case E_PARSE:
                case E_CORE_ERROR:
                case E_COMPILE_ERROR:
                case E_USER_ERROR:
                    ob_end_clean();
                    function_exists('halt') ? halt($e) : exit('ERROR:' . $e['message'] . ' in <b>' . $e['file'] . '</b> on line <b>' . $e['line'] . '</b>');
                    break;
            }
        }
    }

}
