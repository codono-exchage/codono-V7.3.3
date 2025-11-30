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
 * ThinkPHPBuilt-inDispatcherclass
 * carry outURLResolution, routing and scheduling
 */
class Dispatcher
{

    /**
     * URLMapped to the controller
     * @access public
     * @return void
     * @throws Exception
     */
    static public function dispatch()
    {
        $varPath = C('VAR_PATHINFO');
        $varAddon = C('VAR_ADDON');
        $varModule = C('VAR_MODULE');
        $varController = C('VAR_CONTROLLER');
        $varAction = C('VAR_ACTION');
        $urlCase = C('URL_CASE_INSENSITIVE');
        if (isset($_GET[$varPath])) { // judgmentURLinsidewhetherHaveCompatibility Modeparameter
            $_SERVER['PATH_INFO'] = $_GET[$varPath];
            unset($_GET[$varPath]);
        } elseif (IS_CLI) { // CLI mode  index.php module/controller/action/params/...
            $_SERVER['PATH_INFO'] = $_SERVER['argv'][1] ?? '';
        }

        // Openchildareanamedeploy
        if (C('APP_SUB_DOMAIN_DEPLOY')) {
            $rules = C('APP_SUB_DOMAIN_RULES');
            if (isset($rules[$_SERVER['HTTP_HOST']])) { // completeareanameorIPConfiguration
                define('APP_DOMAIN', $_SERVER['HTTP_HOST']); // currentcompleteareaname
                $rule = $rules[APP_DOMAIN];
            } else {
                if (strpos(C('APP_DOMAIN_SUFFIX'), '.')) { // com.cn net.cn
                    $domain = array_slice(explode('.', $_SERVER['HTTP_HOST']), 0, -3);
                } else {
                    $domain = array_slice(explode('.', $_SERVER['HTTP_HOST']), 0, -2);
                }
                if (!empty($domain)) {
                    $subDomain = implode('.', $domain);
                    define('SUB_DOMAIN', $subDomain); // currentcompletechildareaname
                    $domain2 = array_pop($domain); // twolevelareaname
                    if ($domain) { // existthreelevelareaname
                        $domain3 = array_pop($domain);
                    }
                    if (isset($rules[$subDomain])) { // childareaname
                        $rule = $rules[$subDomain];
                    } elseif (isset($rules['*.' . $domain2]) && !empty($domain3)) { // pan-threelevelareaname
                        $rule = $rules['*.' . $domain2];
                        $panDomain = $domain3;
                    } elseif (isset($rules['*']) && !empty($domain2) && 'www' != $domain2) { // pan-twolevelareaname
                        $rule = $rules['*'];
                        $panDomain = $domain2;
                    }
                }
            }

            if (!empty($rule)) {
                // childareanamedeployrule 'Subdomain'=>array('modulname[/ControllerName]','var1=a&var2=b');
                if (is_array($rule)) {
                    list($rule, $vars) = $rule;
                }
                $array = explode('/', $rule);
                // ModuleBinding
                define('BIND_MODULE', array_shift($array));
                // ControllerBinding         
                if (!empty($array)) {
                    $controller = array_shift($array);
                    if ($controller) {
                        define('BIND_CONTROLLER', $controller);
                    }
                }
                if (isset($vars)) { // Incoming parameters
                    parse_str($vars, $parms);
                    if (isset($panDomain)) {
                        $pos = array_search('*', $parms);
                        if (false !== $pos) {
                            // pan-areanameAs aparameter
                            $parms[$pos] = $panDomain;
                        }
                    }
                    $_GET = array_merge($_GET, $parms);
                }
            }
        }
        // analysisPATHINFOinformation
        if (!isset($_SERVER['PATH_INFO'])) {
            $types = explode(',', C('URL_PATHINFO_FETCH'));
            foreach ($types as $type) {
                if (0 === strpos($type, ':')) {// stand byfunctionjudgment
                    $_SERVER['PATH_INFO'] = call_user_func(substr($type, 1));
                    break;
                } elseif (!empty($_SERVER[$type])) {
                    $_SERVER['PATH_INFO'] = (0 === strpos($_SERVER[$type], $_SERVER['SCRIPT_NAME'])) ?
                        substr($_SERVER[$type], strlen($_SERVER['SCRIPT_NAME'])) : $_SERVER[$type];
                    break;
                }
            }
        }

        $depr = C('URL_PATHINFO_DEPR');
        define('MODULE_PATHINFO_DEPR', $depr);

        if (empty($_SERVER['PATH_INFO'])) {
            $_SERVER['PATH_INFO'] = '';
            define('__INFO__', '');
            define('__EXT__', '');
        } else {
            define('__INFO__', trim($_SERVER['PATH_INFO'], '/'));
            // URLsuffix
            define('__EXT__', strtolower(pathinfo($_SERVER['PATH_INFO'], PATHINFO_EXTENSION)));
            $_SERVER['PATH_INFO'] = __INFO__;
            if (!defined('BIND_MODULE') && (!C('URL_ROUTER_ON') || !Route::check())) {
                if (__INFO__ && C('MULTI_MODULE')) { // Obtainmodulname
                    $paths = explode($depr, __INFO__, 2);
                    $allowList = C('MODULE_ALLOW_LIST'); // allowofModuleList
                    $module = preg_replace('/\.' . __EXT__ . '$/i', '', $paths[0]);
                    if (empty($allowList) || (is_array($allowList) && in_array_case($module, $allowList))) {
                        $_GET[$varModule] = $module;
                        $_SERVER['PATH_INFO'] = isset($paths[1]) ? $paths[1] : '';
                    }
                }
            }
        }

        // URLconstant
        define('__SELF__', strip_tags($_SERVER[C('URL_REQUEST_URI')]));

        // ObtainModulename
        define('MODULE_NAME', defined('BIND_MODULE') ? BIND_MODULE : self::getModule($varModule));

        // DetectModuledoes it exist
        if (MODULE_NAME && (defined('BIND_MODULE') || !in_array_case(MODULE_NAME, C('MODULE_DENY_LIST'))) && is_dir(APP_PATH . MODULE_NAME)) {
            // definitioncurrentModulepath
            define('MODULE_PATH', APP_PATH . MODULE_NAME . '/');
            // definitioncurrentModuleofstencilCache path
            C('CACHE_PATH', CACHE_PATH . MODULE_NAME . '/');
            // definitioncurrentModuleofJournaltable of Contents
            C('LOG_PATH', realpath(LOG_PATH) . '/' . MODULE_NAME . '/');

            // Detection module
            Hook::listen('module_check');

            // Load Module Configuration file 
            if (is_file(MODULE_PATH . 'Conf/config' . CONF_EXT))
                C(load_config(MODULE_PATH . 'Conf/config' . CONF_EXT));
            // loadApplication Modecorrespond Configuration file 
            if ('common' != APP_MODE && is_file(MODULE_PATH . 'Conf/config_' . APP_MODE . CONF_EXT))
                C(load_config(MODULE_PATH . 'Conf/config_' . APP_MODE . CONF_EXT));
            // currentApplication statecorrespond Configuration file 
            if (APP_STATUS && is_file(MODULE_PATH . 'Conf/' . APP_STATUS . CONF_EXT))
                C(load_config(MODULE_PATH . 'Conf/' . APP_STATUS . CONF_EXT));

            // Load ModuleAlias definition
            if (is_file(MODULE_PATH . 'Conf/alias.php'))
                Think::addMap(include MODULE_PATH . 'Conf/alias.php');
            // Load ModuletagsFile definition
            if (is_file(MODULE_PATH . 'Conf/tags.php'))
                Hook::import(include MODULE_PATH . 'Conf/tags.php');
            // Load Modulefunctionfile
            if (is_file(MODULE_PATH . 'Common/function.php'))
                include MODULE_PATH . 'Common/function.php';

            $urlCase = C('URL_CASE_INSENSITIVE');
            // Load ModuleofExtended  Configuration file 
            load_ext_file(MODULE_PATH);
        } else {
            E(L('_MODULE_NOT_EXIST_') . ':' . MODULE_NAME);
        }

        if (!defined('__APP__')) {
            $urlMode = C('URL_MODEL');
            if ($urlMode == URL_COMPAT) {// Compatibility Modejudgment
                define('PHP_FILE', _PHP_FILE_ . '?' . $varPath . '=');
            } elseif ($urlMode == URL_REWRITE) {
                $url = dirname(_PHP_FILE_);
                if ($url == '/' || $url == '\\')
                    $url = '';
                define('PHP_FILE', $url);
            } else {
                define('PHP_FILE', _PHP_FILE_);
            }
            // currentapplicationaddress
            define('__APP__', strip_tags(PHP_FILE));
        }
        // ModuleURLaddress
        $moduleName = defined('MODULE_ALIAS') ? MODULE_ALIAS : MODULE_NAME;
        define('__MODULE__', (defined('BIND_MODULE') || !C('MULTI_MODULE')) ? __APP__ : __APP__ . '/' . ($urlCase ? strtolower($moduleName) : $moduleName));

        if ('' != $_SERVER['PATH_INFO'] && (!C('URL_ROUTER_ON') || !Route::check())) {   // Detectroutingrule in caseNoPressdefaultruleDispatchURL
            Hook::listen('path_info');
            // an examinationNo AccessofURLsuffix
            if (C('URL_DENY_SUFFIX') && preg_match('/\.(' . trim(C('URL_DENY_SUFFIX'), '.') . ')$/i', $_SERVER['PATH_INFO'])) {
                send_http_status(404);
                exit;
            }

            // RemovalURLsuffix
            $_SERVER['PATH_INFO'] = preg_replace(C('URL_HTML_SUFFIX') ? '/\.(' . trim(C('URL_HTML_SUFFIX'), '.') . ')$/i' : '/\.' . __EXT__ . '$/i', '', $_SERVER['PATH_INFO']);

            $depr = C('URL_PATHINFO_DEPR');
            $paths = explode($depr, trim($_SERVER['PATH_INFO'], $depr));

            if (!defined('BIND_CONTROLLER')) {// ObtainController
                if (C('CONTROLLER_LEVEL') > 1) {// Controller level
                    $_GET[$varController] = implode('/', array_slice($paths, 0, C('CONTROLLER_LEVEL')));
                    $paths = array_slice($paths, C('CONTROLLER_LEVEL'));
                } else {
                    $_GET[$varController] = array_shift($paths);
                }
            }
            // Obtainoperating
            if (!defined('BIND_ACTION')) {
                $_GET[$varAction] = array_shift($paths);
            }
            // Resolve the remaining URL parameters
            $var = array();
            if (C('URL_PARAMS_BIND') && 1 == C('URL_PARAMS_BIND_TYPE')) {
                // URLparameterpressorderBindingvariable
                $var = $paths;
            } else {
                preg_replace_callback('/(\w+)\/([^\/]+)/', function ($match) use (&$var) {
                    $var[$match[1]] = strip_tags($match[2]);
                }, implode('/', $paths));
            }
            $_GET = array_merge($var, $_GET);
        }
        // ObtainControllerNamespace (path)
        define('CONTROLLER_PATH', self::getSpace($varAddon, $urlCase));
        // ObtainControllerwithoperating Name

        define('CONTROLLER_NAME', defined('BIND_CONTROLLER') ? BIND_CONTROLLER : self::getController($varController, $urlCase));
        define('ACTION_NAME', defined('BIND_ACTION') ? BIND_ACTION : self::getAction($varAction, $urlCase));

        // currentControllerofURaddress
        $controllerName = defined('CONTROLLER_ALIAS') ? CONTROLLER_ALIAS : CONTROLLER_NAME;
        define('__CONTROLLER__', __MODULE__ . $depr . (defined('BIND_CONTROLLER') ? '' : ($urlCase ? parse_name($controllerName) : $controllerName)));

        // currentoperatingofURLaddress
        define('__ACTION__', __CONTROLLER__ . $depr . (defined('ACTION_ALIAS') ? ACTION_ALIAS : ACTION_NAME));

        //Guarantee$_REQUESTnormalThe value
        $_POST=$_POST?:[];
		$_GET=$_GET?:[];
		$_COOKIE=$_COOKIE?:[];
        $_REQUEST = array_merge($_POST, $_GET, $_COOKIE);    // -- Added $_COOKIE.  Oh guarantee..
    }

    /**
     * obtainControllerofNamespacespath Plug-in mechanism to facilitate access
     */
    static private function getSpace($var, $urlCase)
    {
        $space = !empty($_GET[$var]) ? strip_tags($_GET[$var]) : '';
        unset($_GET[$var]);
        return $space;
    }

    /**
     * obtainThe actualControllername
     */
    static private function getController($var, $urlCase)
    {

        $controller = (!empty($_GET[$var]) ? $_GET[$var] : C('DEFAULT_CONTROLLER'));
        unset($_GET[$var]);
        if ($maps = C('URL_CONTROLLER_MAP')) {
            if (isset($maps[strtolower($controller)])) {
                // The current recordSlug
                define('CONTROLLER_ALIAS', strtolower($controller));
                // ObtainThe actualControllerName
                return ucfirst($maps[CONTROLLER_ALIAS]);
            } elseif (array_search(strtolower($controller), $maps)) {
                // Prohibit access to the original controller
                return '';
            }
        }
        if ($urlCase) {
            // URLAddresses are not case sensitive
            // Intelligent Recognitionthe way user_type Identified UserTypeController Controller
            $controller = parse_name($controller, 1);
        }
        return strip_tags(ucfirst($controller));
    }

    /**
     * obtainThe actualoperatingname
     */
    static private function getAction($var, $urlCase)
    {
        $action = !empty($_POST[$var]) ?
            $_POST[$var] :
            (!empty($_GET[$var]) ? $_GET[$var] : C('DEFAULT_ACTION'));
        unset($_POST[$var], $_GET[$var]);
        if ($maps = C('URL_ACTION_MAP')) {
            if (isset($maps[strtolower(CONTROLLER_NAME)])) {
                $maps = $maps[strtolower(CONTROLLER_NAME)];
                if (isset($maps[strtolower($action)])) {
                    // The current recordSlug
                    define('ACTION_ALIAS', strtolower($action));
                    // ObtainThe actualoperating Name
                    if (is_array($maps[ACTION_ALIAS])) {
                        parse_str($maps[ACTION_ALIAS][1], $vars);
                        $_GET = array_merge($_GET, $vars);
                        return $maps[ACTION_ALIAS][0];
                    } else {
                        return $maps[ACTION_ALIAS];
                    }

                } elseif (array_search(strtolower($action), $maps)) {
                    // No Accessoriginaloperating
                    return '';
                }
            }
        }
        return strip_tags($urlCase ? strtolower($action) : $action);
    }

    /**
     * obtainThe actualModulename
     */
    static private function getModule($var)
    {
        $module = (!empty($_GET[$var]) ? $_GET[$var] : C('DEFAULT_MODULE'));
        unset($_GET[$var]);
        if ($maps = C('URL_MODULE_MAP')) {
            if (isset($maps[strtolower($module)])) {
                // The current recordSlug
                define('MODULE_ALIAS', strtolower($module));
                // ObtainThe actualmodulname
                return ucfirst($maps[MODULE_ALIAS]);
            } elseif (array_search(strtolower($module), $maps)) {
                // No AccessoriginalModule
                return '';
            }
        }
        return strip_tags(ucfirst($module));
    }

}
