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
 * ThinkPHP Application class Execution management application process
 * allowableExtended modeinAgaindefinition But you must haveRunMethod Interface
 * @category   Think
 * @package  Think
 * @subpackage  Core
 * @author    liu21st <liu21st@gmail.com>
 */
class App
{

    /**
     * Application Initialization
     * @access public
     * @return void
     */
    static public function init()
    {
        // pagecompressionExportstand by
        if (C('OUTPUT_ENCODE')) {
            $zlib = ini_get('zlib.output_compression');
            if (empty($zlib)) ob_start('ob_gzhandler');
        }
        // Set upsystemTime zone
        date_default_timezone_set(C('DEFAULT_TIMEZONE'));
        // loaddynamicprojectpublicfilewithConfiguration
        load_ext_file();
        // URLDispatch
        Dispatcher::dispatch();

        // definitioncurrentRequestedsystemconstant
        define('NOW_TIME', $_SERVER['REQUEST_TIME']);
        define('REQUEST_METHOD', $_SERVER['REQUEST_METHOD']);
        define('IS_GET', REQUEST_METHOD == 'GET' ? true : false);
        define('IS_POST', REQUEST_METHOD == 'POST' ? true : false);
        define('IS_PUT', REQUEST_METHOD == 'PUT' ? true : false);
        define('IS_DELETE', REQUEST_METHOD == 'DELETE' ? true : false);
        define('IS_AJAX', ((isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') || !empty($_POST[C('VAR_AJAX_SUBMIT')]) || !empty($_GET[C('VAR_AJAX_SUBMIT')])) ? true : false);

        // URLScheduling an end tag
        tag('url_dispatch');
        // Security system variable filter
        if (C('VAR_FILTERS')) {
            $filters = explode(',', C('VAR_FILTERS'));
            foreach ($filters as $filter) {
                // Overall situationparameterfilter
                array_walk_recursive($_POST, $filter);
                array_walk_recursive($_GET, $filter);
            }
        }

        if (C('REQUEST_VARS_FILTER')) {
            // Overall situationSecurity filtering
            array_walk_recursive($_GET, 'think_filter');
            array_walk_recursive($_POST, 'think_filter');
            array_walk_recursive($_REQUEST, 'think_filter');
        }

        C('LOG_PATH', realpath(LOG_PATH) . '/');
        //Dynamic Configuration TMPL_EXCEPTION_FILE,Instead absolute address
        C('TMPL_EXCEPTION_FILE', realpath(C('TMPL_EXCEPTION_FILE')));
        return;
    }

    /**
     * Execution of the application
     * @access public
     * @return void
     */
    static public function exec()
    {
        if (!preg_match('/^[A-Za-z](\w)*$/', MODULE_NAME)) { // SafetyDetect
            $module = false;
        } else {
            //createActionControllerExamples
            $group = defined('GROUP_NAME') && C('APP_GROUP_MODE') == 0 ? GROUP_NAME . '/' : '';
            $module = A($group . MODULE_NAME);
        }

        if (!$module) {
            if (function_exists('__hack_module')) {
                // hack Define Expansion Module returnActionObjects
                $module = __hack_module();
                if (!is_object($module)) {
                    // No longer continue Direct return
                    return;
                }
            } else {
                // whetherdefinitionEmptyModule
                $module = A($group . 'Empty');
                if (!$module) {
                    _404(L('_MODULE_NOT_EXIST_') . ':' . MODULE_NAME);
                }
            }
        }
        // Get the currentoperating Name stand bydynamicrouting
        $action = C('ACTION_NAME') ? C('ACTION_NAME') : ACTION_NAME;
        $action .= C('ACTION_SUFFIX');
        try {
            if (!preg_match('/^[A-Za-z](\w)*$/', $action)) {
                // non-lawoperating
                throw new ReflectionException();
            }
            //carried outcurrentoperating
            $method = new ReflectionMethod($module, $action);
            if ($method->isPublic()) {
                $class = new ReflectionClass($module);
                // Frontoperating
                if ($class->hasMethod('_before_' . $action)) {
                    $before = $class->getMethod('_before_' . $action);
                    if ($before->isPublic()) {
                        $before->invoke($module);
                    }
                }
                // URLParameter binding detection
                if (C('URL_PARAMS_BIND') && $method->getNumberOfParameters() > 0) {
                    switch ($_SERVER['REQUEST_METHOD']) {
                        case 'POST':
                            $vars = array_merge($_GET, $_POST);
                            break;
                        case 'PUT':
                            parse_str(file_get_contents('php://input'), $vars);
                            break;
                        default:
                            $vars = $_GET;
                    }
                    $params = $method->getParameters();
                    foreach ($params as $param) {
                        $name = $param->getName();
                        if (isset($vars[$name])) {
                            $args[] = $vars[$name];
                        } elseif ($param->isDefaultValueAvailable()) {
                            $args[] = $param->getDefaultValue();
                        } else {
                            throw_exception(L('_PARAM_ERROR_') . ':' . $name);
                        }
                    }
                    array_walk_recursive($args, 'think_filter');
                    $method->invokeArgs($module, $args);
                } else {
                    $method->invoke($module);
                }
                // Postpositionoperating
                if ($class->hasMethod('_after_' . $action)) {
                    $after = $class->getMethod('_after_' . $action);
                    if ($after->isPublic()) {
                        $after->invoke($module);
                    }
                }
            } else {
                // operatingMethod is notPublic Throw an exception
                throw new ReflectionException();
            }
        } catch (ReflectionException $e) {
            // methodtransferAfter an exception occurs Directed to__callmethoddeal with
            $method = new ReflectionMethod($module, '__call');
            $method->invokeArgs($module, array($action, ''));
        }
        return;
    }

    /**
     * Run application examples EntrancefileuseofShortcutmethod
     * @access public
     * @return void
     */
    static public function run()
    {
        // projectinitializationlabel
        tag('app_init');
        App::init();
        // projectStart tag
        tag('app_begin');
        // Session initialization
        session(C('SESSION_OPTIONS'));
        // App running Start Time
        G('initTime');
        App::exec();
        // projectEnd tag
        tag('app_end');
        return;
    }
}
