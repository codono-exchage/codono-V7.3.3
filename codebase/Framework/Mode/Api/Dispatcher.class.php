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
 * ThinkPHP APIModeDispatcherclass
 * carry outURLResolution, routing and scheduling
 */
class Dispatcher
{

    /**
     * URLMapped to the controller
     * @access public
     * @return void
     */
    static public function dispatch()
    {
        $varPath = C('VAR_PATHINFO');
        $varModule = C('VAR_MODULE');
        $varController = C('VAR_CONTROLLER');
        $varAction = C('VAR_ACTION');
        $urlCase = C('URL_CASE_INSENSITIVE');
        if (isset($_GET[$varPath])) { // judgmentURLinsidewhetherHaveCompatibility Modeparameter
            $_SERVER['PATH_INFO'] = $_GET[$varPath];
            unset($_GET[$varPath]);
        } elseif (IS_CLI) { // CLI mode  index.php module/controller/action/params/...
            $_SERVER['PATH_INFO'] = isset($_SERVER['argv'][1]) ? $_SERVER['argv'][1] : '';
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
                if (!empty($_SERVER[$type])) {
                    $_SERVER['PATH_INFO'] = (0 === strpos($_SERVER[$type], $_SERVER['SCRIPT_NAME'])) ?
                        substr($_SERVER[$type], strlen($_SERVER['SCRIPT_NAME'])) : $_SERVER[$type];
                    break;
                }
            }
        }
        if (empty($_SERVER['PATH_INFO'])) {
            $_SERVER['PATH_INFO'] = '';
        }
        $depr = C('URL_PATHINFO_DEPR');
        define('MODULE_PATHINFO_DEPR', $depr);
        define('__INFO__', trim($_SERVER['PATH_INFO'], '/'));
        // URLsuffix
        define('__EXT__', strtolower(pathinfo($_SERVER['PATH_INFO'], PATHINFO_EXTENSION)));

        $_SERVER['PATH_INFO'] = __INFO__;

        if (__INFO__ && C('MULTI_MODULE') && !defined('BIND_MODULE')) { // Obtainmodulname
            $paths = explode($depr, __INFO__, 2);
            $allowList = C('MODULE_ALLOW_LIST'); // allowofModuleList
            $module = preg_replace('/\.' . __EXT__ . '$/i', '', $paths[0]);
            if (empty($allowList) || (is_array($allowList) && in_array_case($module, $allowList))) {
                $_GET[$varModule] = $module;
                $_SERVER['PATH_INFO'] = isset($paths[1]) ? $paths[1] : '';
            }
        }

        // ObtainModulename
        define('MODULE_NAME', defined('BIND_MODULE') ? BIND_MODULE : self::getModule($varModule));

        // DetectModuledoes it exist
        if (MODULE_NAME && (defined('BIND_MODULE') || !in_array_case(MODULE_NAME, C('MODULE_DENY_LIST'))) && is_dir(APP_PATH . MODULE_NAME)) {
            // definitioncurrentModulepath
            define('MODULE_PATH', APP_PATH . MODULE_NAME . '/');
            // definitioncurrentModuleofstencilCache path
            C('CACHE_PATH', CACHE_PATH . MODULE_NAME . '/');

            // Load Module Configuration file 
            if (is_file(MODULE_PATH . 'Conf/config.php'))
                C(include MODULE_PATH . 'Conf/config.php');
            // Load ModuleAlias definition
            if (is_file(MODULE_PATH . 'Conf/alias.php'))
                Think::addMap(include MODULE_PATH . 'Conf/alias.php');
            // Load Modulefunctionfile
            if (is_file(MODULE_PATH . 'Common/function.php'))
                include MODULE_PATH . 'Common/function.php';
        } else {
            E(L('_MODULE_NOT_EXIST_') . ':' . MODULE_NAME);
        }

        if ('' != $_SERVER['PATH_INFO'] && (!C('URL_ROUTER_ON') || !Route::check())) {   // Detectroutingrule in caseNoPressdefaultruleDispatchURL
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
                $_GET[$varController] = array_shift($paths);
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
        // ObtainControllerwithoperating Name
        define('CONTROLLER_NAME', defined('BIND_CONTROLLER') ? BIND_CONTROLLER : self::getController($varController, $urlCase));
        define('ACTION_NAME', defined('BIND_ACTION') ? BIND_ACTION : self::getAction($varAction, $urlCase));
        //Guarantee$_REQUESTnormalThe value
        $_REQUEST = array_merge($_POST, $_GET);
    }

    /**
     * obtainThe actualControllername
     */
    static private function getController($var, $urlCase)
    {
        $controller = (!empty($_GET[$var]) ? $_GET[$var] : C('DEFAULT_CONTROLLER'));
        unset($_GET[$var]);
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
        return strip_tags(ucfirst(strtolower($module)));
    }

}
