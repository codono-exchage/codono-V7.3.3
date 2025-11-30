<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2014 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: support@codono.com
// +----------------------------------------------------------------------

namespace Think;
/**
 * ThinkPHP Bootstrap class
 */
class Think
{

    // Class mapping
    private static $_map = array();

    // Instantiate an object
    private static $_instance = array();

    /**
     * Application Initialization
     * @access public
     * @return void
     */
    static public function start()
    {
        // registeredAUTOLOADmethod
        spl_autoload_register('Think\Think::autoload');
        // set uperrorAnd abnormaldeal with
        register_shutdown_function('Think\Think::fatalError');
        set_error_handler('Think\Think::appError');
        set_exception_handler('Think\Think::appException');

        // Initialization file storage
        Storage::connect(STORAGE_TYPE);

        $runtimefile = RUNTIME_PATH . APP_MODE . '~runtime.php';
        //if(!APP_DEBUG && Storage::has($runtimefile)){
        //   Storage::load($runtimefile);
        // }else{
        if (Storage::has($runtimefile))
            Storage::unlink($runtimefile);
        $content = '';
        // ReadApplication Mode
        $mode = include is_file(CONF_PATH . 'core.php') ? CONF_PATH . 'core.php' : MODE_PATH . APP_MODE . '.php';
        // Load the core file
        foreach ($mode['core'] as $file) {
            if (is_file($file)) {
                include $file;
                if (!APP_DEBUG) $content .= compile($file);
            }
        }

        // loadApplication Mode Configuration file
        foreach ($mode['config'] as $key => $file) {
            is_numeric($key) ? C(load_config($file)) : C($key, load_config($file));
        }

        // ReadcurrentApplication Modecorrespond Configuration file
        if ('common' != APP_MODE && is_file(CONF_PATH . 'config_' . APP_MODE . CONF_EXT))
            C(load_config(CONF_PATH . 'config_' . APP_MODE . CONF_EXT));

        // loadmodeAlias definition
        if (isset($mode['alias'])) {
            self::addMap(is_array($mode['alias']) ? $mode['alias'] : include $mode['alias']);
        }

        // loadapplicationAlias definitionfile
        if (is_file(CONF_PATH . 'alias.php'))
            self::addMap(include CONF_PATH . 'alias.php');

        // Load mode behavior definition
        if (isset($mode['tags'])) {
            Hook::import(is_array($mode['tags']) ? $mode['tags'] : include $mode['tags']);
        }

        // loadapplicationbehaviordefinition
        if (is_file(CONF_PATH . 'tags.php'))
            // allowapplicationincreaseDevelopmodeConfigurationdefinition
            Hook::import(include CONF_PATH . 'tags.php');

        // loadframeUnderlying language pack
        L(include THINK_PATH . 'Lang/' . strtolower(C('DEFAULT_LANG')) . '.php');

        if (!APP_DEBUG) {
            $content .= "\nnamespace { Think\\Think::addMap(" . var_export(self::$_map, true) . ");";
            $content .= "\nL(" . var_export(L(), true) . ");\nC(" . var_export(C(), true) . ');Think\Hook::import(' . var_export(Hook::get(), true) . ');}';
            Storage::put($runtimefile, strip_whitespace('<?php ' . $content));
        } else {
            // debuggingmodeloadsystemdefault Configuration file 
            C(include THINK_PATH . 'Conf/debug.php');
            // ReadCommissioning the application configuration file
            if (is_file(CONF_PATH . 'debug' . CONF_EXT))
                C(include CONF_PATH . 'debug' . CONF_EXT);
        }
        // }

        // ReadcurrentApplication statecorrespond Configuration file
        if (APP_STATUS && is_file(CONF_PATH . APP_STATUS . CONF_EXT))
            C(include CONF_PATH . APP_STATUS . CONF_EXT);

        // Set upsystemTime zone
        date_default_timezone_set(C('DEFAULT_TIMEZONE'));

        // Check the application directory structure in casedoes not existthenautomaticcreate
        if (C('CHECK_APP_DIR')) {
            $module = defined('BIND_MODULE') ? BIND_MODULE : C('DEFAULT_MODULE');
            if (!is_dir(APP_PATH . $module) || !is_dir(LOG_PATH)) {
                // Detection Application Directory Structure
                Build::checkDir($module);
            }
        }

        // recordingLoad filetime
        G('loadTime');
        // runapplication
        App::run();

    }

    // registeredclassmap
    static public function addMap($class, $map = '')
    {
        if (is_array($class)) {
            self::$_map = array_merge(self::$_map, $class);
        } else {
            self::$_map[$class] = $map;
        }
    }

    // Obtainclassmap
    static public function getMap($class = '')
    {
        if ('' === $class) {
            return self::$_map;
        } elseif (isset(self::$_map[$class])) {
            return self::$_map[$class];
        } else {
            return null;
        }
    }

    /**
     * Library is automatically loaded
     * @param string $class Objectsclassname
     * @return void
     */
    public static function autoload($class)
    {
        // Check for mapping
        if (isset(self::$_map[$class])) {
            include self::$_map[$class];
        } elseif (false !== strpos($class, '\\')) {
            $name = strstr($class, '\\', true);
            if (in_array($name, array('Think', 'Org', 'Behavior', 'Com', 'Vendor')) || is_dir(LIB_PATH . $name)) {
                // LibraryDirectoryofNamespacesautomaticLocate
                $path = LIB_PATH;
            } else {
                // Detection custom namespace otherwiseTakeModuleforNamespaces
                $namespace = C('AUTOLOAD_NAMESPACE');
                $path = isset($namespace[$name]) ? dirname($namespace[$name]) . '/' : APP_PATH;
            }
            $filename = $path . str_replace('\\', '/', $class) . EXT;
            if (is_file($filename)) {
                // Winsurroundingsthe followingstrictCase sensitive
                if (IS_WIN && false === strpos(str_replace('/', '\\', realpath($filename)), $class . EXT)) {
                    return;
                }
                include $filename;
            }
        } elseif (!C('APP_USE_NAMESPACE')) {
            // Autoloader library layer
            foreach (explode(',', C('APP_AUTOLOAD_LAYER')) as $layer) {
                if (substr($class, -strlen($layer)) == $layer) {
                    if (require_cache(MODULE_PATH . $layer . '/' . $class . EXT)) {
                        return;
                    }
                }
            }
            // according toAutomatically loadPath settingsTry searching
            foreach (explode(',', C('APP_AUTOLOAD_PATH')) as $path) {
                if (import($path . '.' . $class))
                    // in caseloadclassSuccess, returns
                    return;
            }
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
                    self::$_instance[$identify] = call_user_func(array(&$o, $method));
                else
                    self::$_instance[$identify] = $o;
            } else
                self::halt(L('_CLASS_NOT_EXIST_') . ':' . $class);
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
        if ('E' == $trace[0]['function']) {
            $error['file'] = $trace[0]['file'];
            $error['line'] = $trace[0]['line'];
        } else {
            $error['file'] = $e->getFile();
            $error['line'] = $e->getLine();
        }
        $error['trace'] = $e->getTraceAsString();
        //Log::record($error['message'], Log::ERR);
        $text=$error;

        if(is_array($text)){$text= json_encode($text, true);}
        $text = $text . "\n";
	    $filename=date('d-m-Y')."_".'internal';
        file_put_contents(APP_PATH . '/../Public/Log/' . $filename . '.log', $text, FILE_APPEND);
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
                $errorStr = "$errstr " . $errfile . " The first $errline Row.";
                if (C('LOG_RECORD')) Log::write("[$errno] " . $errorStr, Log::ERR);
                self::halt($errorStr);
                break;
            default:
                $errorStr = "[$errno] $errstr " . $errfile . " The first $errline Row.";
                self::trace($errorStr, '', 'NOTIC');
                break;
        }
    }

    // fatalerrorcapture
    static public function fatalError()
    {
        Log::save();
        if ($e = error_get_last()) {
            switch ($e['type']) {
                case E_ERROR:
                case E_PARSE:
                case E_CORE_ERROR:
                case E_COMPILE_ERROR:
                case E_USER_ERROR:
                    ob_end_clean();
                    self::halt($e);
                    break;
            }
        }
    }

    /**
     * Error output
     * @param mixed $error error
     * @return void
     */
    static public function halt($error)
    {
        $e = array();
        if (APP_DEBUG || IS_CLI) {
            //debugging mode Output errorinformation
            if (!is_array($error)) {
                $trace = debug_backtrace();
                $e['message'] = $error;
                $e['file'] = $trace[0]['file'];
                $e['line'] = $trace[0]['line'];
                ob_start();
                debug_print_backtrace();
                $e['trace'] = ob_get_clean();
            } else {
                $e = $error;
            }
            if (IS_CLI) {
                //exit(iconv('UTF-8', 'gbk', $e['message']) . PHP_EOL . 'FILE: ' . $e['file'] . '(' . $e['line'] . ')' . PHP_EOL . $e['trace']);
                exit( $e['message'] . PHP_EOL . 'FILE: ' . $e['file'] . '(' . $e['line'] . ')' . PHP_EOL . $e['trace']);
            }
        } else {

            //Otherwise directed to the wrong page
            $error_page = '';// C('ERROR_PAGE');
            if (!empty($error_page)) {
                redirect($error_page);
            } else {
                $message = is_array($error) ? $error['message'] : $error;
                $e['message'] =  $message;//C('ERROR_MESSAGE');
				$e['file'] =  $error['file']?:'';//C('ERROR_MESSAGE');
				$e['line'] =  $error['line']?:'';//C('ERROR_MESSAGE');
				$e['trace'] =  $error['trace']?:'';//C('ERROR_MESSAGE');
				
            }
        }
        // Abnormal page templates included
		if(M_DEBUG)
		{
		$exceptionFile = THINK_PATH . 'Tpl/think_exception.tpl';
        include $exceptionFile;
        }else{
			clog($error['file'],$error);
			echo "There are facing Difficulties:500064";
        }
        exit;

    }

    /**
     * Adding and get the pageTracerecording
     * @param string $value variable
     * @param string $label label
     * @param string $level Log Level(Or pageTraceTab)
     * @param boolean $record Whether logging
     * @return void|array
     */
    static public function trace($value = '[think]', $label = '', $level = 'DEBUG', $record = false)
    {
        static $_trace = array();
        if ('[think]' === $value) { // Obtain trace information
            return $_trace;
        } else {
            $info = ($label ? $label . ':' : '') . print_r($value, true);
            $level = strtoupper($level);

            if ((defined('IS_AJAX') && IS_AJAX) ||  $record) {
                Log::record($info, $level, $record);
            } else {
                if (!isset($_trace[$level])) {
                    $_trace[$level] = array();
                }
                $_trace[$level][] = $info;
            }
        }
    }
}
