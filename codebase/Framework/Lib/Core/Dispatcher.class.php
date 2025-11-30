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
 * ThinkPHPBuilt-inDispatcherclass
 * carry outURLResolution, routing and scheduling
 * @category   Think
 * @package  Think
 * @subpackage  Core
 * @author    liu21st <liu21st@gmail.com>
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
        $urlMode = C('URL_MODEL');
        if (isset($_GET[C('VAR_PATHINFO')])) { // judgmentURLinsidewhetherHaveCompatibility Modeparameter
            $_SERVER['PATH_INFO'] = $_GET[C('VAR_PATHINFO')];
            unset($_GET[C('VAR_PATHINFO')]);
        }
        if ($urlMode == URL_COMPAT) {
            // Compatibility Modejudgment
            define('PHP_FILE', _PHP_FILE_ . '?' . C('VAR_PATHINFO') . '=');
        } elseif ($urlMode == URL_REWRITE) {
            //currentprojectaddress
            $url = dirname(_PHP_FILE_);
            if ($url == '/' || $url == '\\')
                $url = '';
            define('PHP_FILE', $url);
        } else {
            //currentprojectaddress
            define('PHP_FILE', _PHP_FILE_);
        }

        // Openchildareanamedeploy
        if (C('APP_SUB_DOMAIN_DEPLOY')) {
            $rules = C('APP_SUB_DOMAIN_RULES');
            if (isset($rules[$_SERVER['HTTP_HOST']])) { // completeareanameorIPConfiguration
                $rule = $rules[$_SERVER['HTTP_HOST']];
            } else {
                $subDomain = strtolower(substr($_SERVER['HTTP_HOST'], 0, strpos($_SERVER['HTTP_HOST'], '.')));
                define('SUB_DOMAIN', $subDomain); // twolevelareanamedefinition
                if ($subDomain && isset($rules[$subDomain])) {
                    $rule = $rules[$subDomain];
                } elseif (isset($rules['*'])) { // Pan-domain support
                    if ('www' != $subDomain && !in_array($subDomain, C('APP_SUB_DOMAIN_DENY'))) {
                        $rule = $rules['*'];
                    }
                }
            }

            if (!empty($rule)) {
                // childareanamedeployrule 'Subdomain'=>array('Packet Name/[modulname]','var1=a&var2=b');
                $array = explode('/', $rule[0]);
                $module = array_pop($array);
                if (!empty($module)) {
                    $_GET[C('VAR_MODULE')] = $module;
                    $domainModule = true;
                }
                if (!empty($array)) {
                    $_GET[C('VAR_GROUP')] = array_pop($array);
                    $domainGroup = true;
                }
                if (isset($rule[1])) { // Incoming parameters
                    parse_str($rule[1], $parms);
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
        if (!empty($_SERVER['PATH_INFO'])) {
            tag('path_info');
            $part = pathinfo($_SERVER['PATH_INFO']);
            define('__EXT__', isset($part['extension']) ? strtolower($part['extension']) : '');
            if (__EXT__) {
                if (C('URL_DENY_SUFFIX') && preg_match('/\.(' . trim(C('URL_DENY_SUFFIX'), '.') . ')$/i', $_SERVER['PATH_INFO'])) {
                    send_http_status(404);
                    exit;
                }
                if (C('URL_HTML_SUFFIX')) {
                    $htmlSuffixPattern = '/\.(' . preg_quote(trim(C('URL_HTML_SUFFIX'), '.'), '/') . ')$/i';
                    $_SERVER['PATH_INFO'] = preg_replace($htmlSuffixPattern, '', $_SERVER['PATH_INFO']);
                } else {
                    $extensionPattern = '/\.' . preg_quote(__EXT__, '/') . '$/i';
                    $_SERVER['PATH_INFO'] = preg_replace($extensionPattern, '', $_SERVER['PATH_INFO']);
                }
            }

            if (!self::routerCheck()) {   // Detectroutingrule in caseNoPressdefaultruleDispatchURL
                $paths = explode($depr, trim($_SERVER['PATH_INFO'], '/'));
                
                if (C('VAR_URL_PARAMS')) {
                    // Directly through$_GET['_URL_'][1] $_GET['_URL_'][2] ObtainURLparameter Not easyroutingTimeparameterObtain
                    $_GET[C('VAR_URL_PARAMS')] = $paths;
                }
                $var = [];
                if (C('APP_GROUP_LIST') && !isset($_GET[C('VAR_GROUP')])) {
                    $var[C('VAR_GROUP')] = in_array(strtolower($paths[0]), explode(',', strtolower(C('APP_GROUP_LIST')))) ? array_shift($paths) : '';
                    if (C('APP_GROUP_DENY') && in_array(strtolower($var[C('VAR_GROUP')]), explode(',', strtolower(C('APP_GROUP_DENY'))))) {
                        // Prohibit direct access to the packet
                        exit;
                    }
                }
                if (!isset($_GET[C('VAR_MODULE')])) {// not yetHavedefinitionModulename
                    $var[C('VAR_MODULE')] = array_shift($paths);
                }
                $var[C('VAR_ACTION')] = array_shift($paths);
                // Resolve the remaining URL parameters
                //preg_replace('@(\w+)\/([^\/]+)@e', '$var[\'\\1\']=strip_tags(\'\\2\');', implode('/', $paths));
                preg_replace_callback('@(\w+)/([^/]+)@', function($matches) use (&$var) {
                    $var[$matches[1]] = strip_tags($matches[2]);
                }, implode('/', $paths));

                $_GET = array_merge($var, $_GET);
            }
            define('__INFO__', $_SERVER['PATH_INFO']);
        } else {
            define('__INFO__', '');
        }

        // URLconstant
        define('__SELF__', strip_tags($_SERVER['REQUEST_URI']));
        // currentprojectaddress
        define('__APP__', strip_tags(PHP_FILE));

        // ObtainPacket Modulewithoperatingname
        if (C('APP_GROUP_LIST')) {
            define('GROUP_NAME', self::getGroup(C('VAR_GROUP')));
            // PacketURLaddress
            define('__GROUP__', (!empty($domainGroup) || strtolower(GROUP_NAME) == strtolower(C('DEFAULT_GROUP'))) ? __APP__ : __APP__ . '/' . (C('URL_CASE_INSENSITIVE') ? strtolower(GROUP_NAME) : GROUP_NAME));
        }

        // definitionprojectbasisloadpath
        define('BASE_LIB_PATH', (defined('GROUP_NAME') && C('APP_GROUP_MODE') == 1) ? APP_PATH . C('APP_GROUP_PATH') . '/' . GROUP_NAME . '/' : LIB_PATH);
        if (defined('GROUP_NAME')) {
            C('CACHE_PATH', CACHE_PATH . GROUP_NAME . '/');
            if (1 == C('APP_GROUP_MODE')) { // independentPacketmode
                $config_path = BASE_LIB_PATH . 'Conf/';
                $common_path = BASE_LIB_PATH . 'Common/';
            } else { // Ordinary packet mode
                $config_path = CONF_PATH . GROUP_NAME . '/';
                $common_path = COMMON_PATH . GROUP_NAME . '/';
            }
            // Load grouped profile
            if (is_file($config_path . 'config.php'))
                C(include $config_path . 'config.php');
            // Load grouping alias definitions
            if (is_file($config_path . 'alias.php'))
                alias_import(include $config_path . 'alias.php');
            // Load groupstagsFile definition
            if (is_file($config_path . 'tags.php'))
                C('tags', include $config_path . 'tags.php');
            // Load grouping function file
            if (is_file($common_path . 'function.php'))
                include $common_path . 'function.php';
        } else {
            C('CACHE_PATH', CACHE_PATH);
        }
        define('MODULE_NAME', self::getModule(C('VAR_MODULE')));
        define('ACTION_NAME', self::getAction(C('VAR_ACTION')));

        // currentModulewithPacketaddress
        $moduleName = defined('MODULE_ALIAS') ? MODULE_ALIAS : MODULE_NAME;
        if (defined('GROUP_NAME')) {
            define('__URL__', !empty($domainModule) ? __GROUP__ . $depr : __GROUP__ . $depr . (C('URL_CASE_INSENSITIVE') ? strtolower($moduleName) : $moduleName));
        } else {
            define('__URL__', !empty($domainModule) ? __APP__ . '/' : __APP__ . '/' . (C('URL_CASE_INSENSITIVE') ? strtolower($moduleName) : $moduleName));
        }
        // currentoperatingaddress
        define('__ACTION__', __URL__ . $depr . (defined('ACTION_ALIAS') ? ACTION_ALIAS : ACTION_NAME));
        //Guarantee$_REQUESTnormalThe value
        $_REQUEST = array_merge($_POST, $_GET);
    }

    /**
     * Route detection
     * @access public
     * @return void
     */
    static public function routerCheck()
    {
        $return = false;
        // Route detectionlabel
        tag('route_check', $return);
        return $return;
    }

    /**
     * obtainThe actualModulename
     * @access private
     * @return string
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
                return $maps[MODULE_ALIAS];
            } elseif (array_search(strtolower($module), $maps)) {
                // No AccessoriginalModule
                return '';
            }
        }
        if (C('URL_CASE_INSENSITIVE')) {
            // URLAddresses are not case sensitive
            // Intelligent Recognitionthe way index.php/user_type/index/ Identified UserTypeAction Module
            $module = ucfirst(parse_name($module, 1));
        }
        return strip_tags($module);
    }

    /**
     * obtainThe actualoperatingname
     * @access private
     * @return string
     */
    static private function getAction($var)
    {
        $action = !empty($_POST[$var]) ?
            $_POST[$var] :
            (!empty($_GET[$var]) ? $_GET[$var] : C('DEFAULT_ACTION'));
        unset($_POST[$var], $_GET[$var]);
        if ($maps = C('URL_ACTION_MAP')) {
            if (isset($maps[strtolower(MODULE_NAME)])) {
                $maps = $maps[strtolower(MODULE_NAME)];
                if (isset($maps[strtolower($action)])) {
                    // The current recordSlug
                    define('ACTION_ALIAS', strtolower($action));
                    // ObtainThe actualoperating Name
                    return $maps[ACTION_ALIAS];
                } elseif (array_search(strtolower($action), $maps)) {
                    // No Accessoriginaloperating
                    return '';
                }
            }
        }
        return strip_tags(C('URL_CASE_INSENSITIVE') ? strtolower($action) : $action);
    }

    /**
     * obtainThe actualPacketname
     * @access private
     * @return string
     */
    static private function getGroup($var)
    {
        $group = (!empty($_GET[$var]) ? $_GET[$var] : C('DEFAULT_GROUP'));
        unset($_GET[$var]);
        return strip_tags(C('URL_CASE_INSENSITIVE') ? ucfirst(strtolower($group)) : $group);
    }

}
