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
 * ThinkPHP Application class Execution management application process
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
        // loaddynamicapplicationpublicfilewithConfiguration
        load_ext_file(COMMON_PATH);

        // Journaltable of ContentsConverted to an absolute path defaultCase to storepublicModulethe following
        C('LOG_PATH', realpath(LOG_PATH) . '/Common/');

        // definitioncurrentRequestedsystemconstant
        define('NOW_TIME', $_SERVER['REQUEST_TIME']);
        define('REQUEST_METHOD', $_SERVER['REQUEST_METHOD']);
        define('IS_GET', REQUEST_METHOD == 'GET');
        define('IS_POST', REQUEST_METHOD == 'POST');
        define('IS_PUT', REQUEST_METHOD == 'PUT');
        define('IS_DELETE', REQUEST_METHOD == 'DELETE');

        // URLDispatch
        Dispatcher::dispatch();

        if (C('REQUEST_VARS_FILTER')) {
            // Overall situationSecurity filtering
            array_walk_recursive($_GET, 'think_filter');
            array_walk_recursive($_POST, 'think_filter');
            array_walk_recursive($_REQUEST, 'think_filter');
        }

        // URLScheduling an end tag
        Hook::listen('url_dispatch');

        define('IS_AJAX', (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') || !empty($_POST[C('VAR_AJAX_SUBMIT')]) || !empty($_GET[C('VAR_AJAX_SUBMIT')]));

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
        } elseif (C('ACTION_BIND_CLASS')) {
			
            // operatingBound to the class: module\Controller\Controller\operating
            $layer = C('DEFAULT_C_LAYER');
            if (is_dir(MODULE_PATH . $layer . '/' . CONTROLLER_NAME)) {
                $namespace = MODULE_NAME . '\\' . $layer . '\\' . CONTROLLER_NAME . '\\';
            } else {
                // Air Controller
                $namespace = MODULE_NAME . '\\' . $layer . '\\_empty\\';
            }
            $actionName = strtolower(ACTION_NAME);
            if (class_exists($namespace . $actionName)) {
                $class = $namespace . $actionName;
            } elseif (class_exists($namespace . '_empty')) {
                // No operation
                $class = $namespace . '_empty';
            } else {
                E(L('_ERROR_ACTION_') . ':' . ACTION_NAME);
            }
            $module = new $class;
            // operatingAfter binding to class Fixed executionrunEntrance
            $action = 'run';
        } else {
            //createControllerExamples
            $module = controller(CONTROLLER_NAME, CONTROLLER_PATH);
        }

        if (!$module) {

            // whetherdefinitionEmptyController
            $module = A('Empty');
            if (!$module) {
                E(L('_CONTROLLER_NOT_EXIST_') . ':' . CONTROLLER_NAME);
            }
        }

        // Get the currentoperating Name stand bydynamicrouting
        if (!isset($action)) {
            $action = ACTION_NAME . C('ACTION_SUFFIX');
        }
        try {
            self::invokeAction($module, $action);
        } catch (\ReflectionException $e) {
            // methodtransferAfter an exception occurs Directed to__callmethoddeal with
            $method = new \ReflectionMethod($module, '__call');
            $method->invokeArgs($module, array($action, ''));
        }
        return;
    }

    public static function invokeAction($module, $action)
    {
        if (!preg_match('/^[A-Za-z](\w)*$/', $action)) {
            // non-lawoperating
            throw new \ReflectionException();
        }
        //carried outcurrentoperating
        $method = new \ReflectionMethod($module, $action);
        if ($method->isPublic() && !$method->isStatic()) {
            $class = new \ReflectionClass($module);
            // Frontoperating
            if ($class->hasMethod('_before_' . $action)) {
                $before = $class->getMethod('_before_' . $action);
                if ($before->isPublic()) {
                    $before->invoke($module);
                }
            }
            // URLParameter binding detection
            if ($method->getNumberOfParameters() > 0 && C('URL_PARAMS_BIND')) {
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
                        //E("Parameter error" . ':' . $name);
                        $array = array("status" => 0, "error" => "Parameter error" . ':' . $name);
                        die(json_encode($array));
                    }
                }
                // OpenBindingparameterFiltering mechanism
                if (C('URL_PARAMS_FILTER')) {
                    $filters = C('URL_PARAMS_FILTER_TYPE') ?: C('DEFAULT_FILTER');

                    if ($filters) {
                        $filters = explode(',', $filters);

                        foreach ($filters as $filter) {
                            $args = array_map_recursive($filter, $args); // parameterfilter
                        }
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
            // operating Method is not Public Throw an exception
            throw new \ReflectionException();
        }
    }

    /**
     * Run application examples EntrancefileuseofShortcutmethod
     * @access public
     * @return void
     */
    static public function run()
    {
        // Application Initialization label
        Hook::listen('app_init');
        App::init();
        // Application start tag
        Hook::listen('app_begin');
        // Session initialization
        if (!IS_CLI) {
            session(C('SESSION_OPTIONS'));
        }
        // App running Start Time
        G('initTime');
        App::exec();

        // Application closing tag
        Hook::listen('app_end');
        return;
    }
}
