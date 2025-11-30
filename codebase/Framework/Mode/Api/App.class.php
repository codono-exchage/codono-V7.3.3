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
 * ThinkPHP APImode Application class
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
        // definitioncurrentRequestedsystemconstant
        define('NOW_TIME', $_SERVER['REQUEST_TIME']);
        define('REQUEST_METHOD', $_SERVER['REQUEST_METHOD']);
        define('IS_GET', REQUEST_METHOD == 'GET' ? true : false);
        define('IS_POST', REQUEST_METHOD == 'POST' ? true : false);
        define('IS_PUT', REQUEST_METHOD == 'PUT' ? true : false);
        define('IS_DELETE', REQUEST_METHOD == 'DELETE' ? true : false);
        define('IS_AJAX', ((isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') || !empty($_POST[C('VAR_AJAX_SUBMIT')]) || !empty($_GET[C('VAR_AJAX_SUBMIT')])) ? true : false);

        // URLDispatch
        Dispatcher::dispatch();

        if (C('REQUEST_VARS_FILTER')) {
            // Overall situationSecurity filtering
            array_walk_recursive($_GET, 'think_filter');
            array_walk_recursive($_POST, 'think_filter');
            array_walk_recursive($_REQUEST, 'think_filter');
        }

        // Journaltable of ContentsConverted to an absolute path
        C('LOG_PATH', realpath(LOG_PATH) . '/');
        // TMPL_EXCEPTION_FILE Instead absolute address
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

        if (!preg_match('/^[A-Za-z](\/|\w)*$/', CONTROLLER_NAME)) { // SafetyDetect
            $module = false;
        } else {
            //createControllerExamples
            $module = A(CONTROLLER_NAME);
        }

        if (!$module) {
            // whetherdefinitionEmptyController
            $module = A('Empty');
            if (!$module) {
                E(L('_CONTROLLER_NOT_EXIST_') . ':' . CONTROLLER_NAME);
            }
        }

        // Get the currentoperating Name stand bydynamicrouting
        $action = ACTION_NAME;

        try {
            if (!preg_match('/^[A-Za-z](\w)*$/', $action)) {
                // non-lawoperating
                throw new \ReflectionException();
            }
            //carried outcurrentoperating
            $method = new \ReflectionMethod($module, $action);
            if ($method->isPublic() && !$method->isStatic()) {
                $class = new \ReflectionClass($module);
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
                    $paramsBindType = C('URL_PARAMS_BIND_TYPE');
                    foreach ($params as $param) {
                        $name = $param->getName();
                        if (1 == $paramsBindType && !empty($vars)) {
                            $args[] = array_shift($vars);
                        } elseif (0 == $paramsBindType && isset($vars[$name])) {
                            $args[] = $vars[$name];
                        } elseif ($param->isDefaultValueAvailable()) {
                            $args[] = $param->getDefaultValue();
                        } else {
                            E(L('_PARAM_ERROR_') . ':' . $name);
                        }
                    }
                    array_walk_recursive($args, 'think_filter');
                    $method->invokeArgs($module, $args);
                } else {
                    $method->invoke($module);
                }
            } else {
                // operatingMethod is notPublic Throw an exception
                throw new \ReflectionException();
            }
        } catch (\ReflectionException $e) {
            // methodtransferAfter an exception occurs Directed to__callmethoddeal with
            $method = new \ReflectionMethod($module, '__call');
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
        App::init();
        // Session initialization
        if (!IS_CLI) {
            session(C('SESSION_OPTIONS'));
        }
        // App running Start Time
        G('initTime');
        App::exec();
        return;
    }

}