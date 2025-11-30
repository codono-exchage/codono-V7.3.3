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
 * System behavior extension RESTRoute detection
 */
class CheckRestRouteBehavior extends Behavior
{
    // Behavioral parametersdefinition(Defaults) canIn the projectConfigurationincover
    protected $options = array(
        'URL_ROUTER_ON' => false,   // whetherOpenURLrouting
        'URL_ROUTE_RULES' => array(), // defaultroutingrule,注：PacketConfigurationUnableSubstitute
    );

    /**
     * Route detection
     * @access public
     * @return void
     */
    public function run(&$return)
    {
        $regx = trim($_SERVER['PATH_INFO'], '/');
        // whetherOpenroutinguse
        if (empty($regx) || !C('URL_ROUTER_ON')) $return = false;
        // routingdefinitionfilepriorityinconfigmiddleConfigurationdefinition
        $routes = C('URL_ROUTE_RULES');
        if (is_array(C('routes'))) $routes = C('routes');
        // routingdeal with
        if (!empty($routes)) {
            $depr = C('URL_PATHINFO_DEPR');
            foreach ($routes as $key => $route) {
                // Definition Format: array('Routing rules or regular','Routing address','Routing parameters','Submission Type','Resource Type')
                if (isset($route[3]) && strtolower($_SERVER['REQUEST_METHOD']) != strtolower($route[3])) {
                    continue; // If you setsubmitTypes ofThe filter
                }
                if (isset($route[4]) && !in_array(__EXT__, explode(',', $route[4]), true)) {
                    continue; // If you setSpreadThe name of the filter
                }
                if (0 === strpos($route[0], '/') && preg_match($route[0], $regx, $matches)) { // Regularrouting
                    return self::parseRegex($matches, $route, $regx);
                } else { // rulerouting
                    $len1 = substr_count($regx, '/');
                    $len2 = substr_count($route[0], '/');
                    if ($len1 >= $len2) {
                        if ('$' == substr($route[0], -1, 1)) {// Exact hits
                            if ($len1 != $len2) {
                                continue;
                            } else {
                                $route[0] = substr($route[0], 0, -1);
                            }
                        }
                        $match = self::checkUrlMatch($regx, $route[0]);
                        if ($match) return $return = self::parseRule($route, $regx);
                    }
                }
            }
        }
        $return = false;
    }

    // DetectURLAnd rulesroutingwhethermatch
    static private function checkUrlMatch($regx, $rule)
    {
        $m1 = explode('/', $regx);
        $m2 = explode('/', $rule);
        $match = true; // whethermatch
        foreach ($m2 as $key => $val) {
            if (':' == substr($val, 0, 1)) {// dynamicvariable
                if (strpos($val, '\\')) {
                    $type = substr($val, -1);
                    if ('d' == $type && !is_numeric($m1[$key])) {
                        $match = false;
                        break;
                    }
                } elseif (strpos($val, '^')) {
                    $array = explode('|', substr(strstr($val, '^'), 1));
                    if (in_array($m1[$key], $array)) {
                        $match = false;
                        break;
                    }
                }
            } elseif (0 !== strcasecmp($val, $m1[$key])) {
                $match = false;
                break;
            }
        }
        return $match;
    }

    static private function parseUrl($url)
    {
        $var = array();
        if (false !== strpos($url, '?')) { // [Packet/Module/operating?]parameter1=value1&parameter2=value2...
            $info = parse_url($url);
            $path = explode('/', $info['path']);
            parse_str($info['query'], $var);
        } elseif (strpos($url, '/')) { // [Packet/Module/operating]
            $path = explode('/', $url);
        } else { // parameter1=value1&parameter2=value2...
            parse_str($url, $var);
        }
        if (isset($path)) {
            $var[C('VAR_ACTION')] = array_pop($path);
            if (!empty($path)) {
                $var[C('VAR_MODULE')] = array_pop($path);
            }
            if (!empty($path)) {
                $var[C('VAR_GROUP')] = array_pop($path);
            }
        }
        return $var;
    }

    // Resolverulerouting
    // array('Routing rules','[Packet/Module/operating]','Additional parameters1=value1&Additional parameters2=value2...','Request type','Resource Type')
    // array('Routing rules','External address','Redirect code','Request type','Resource Type')
    // routingrulein :beginning Showdynamicvariable
    // OutsideaddressYou can use dynamicvariable use :1 :2 The way
    // array('news/:month/:day/:id','News/read?cate=1','status=1','post','html,xml'), 
    // array('new/:id','/new.php?id=:1',301,'get','xml'), Redirect
    static private function parseRule($route, $regx)
    {
        // Obtainroutingaddressrule
        $url = $route[1];
        // ObtainURLaddressmiddleparameter
        $paths = explode('/', $regx);
        // Resolveroutingrule
        $matches = array();
        $rule = explode('/', $route[0]);
        foreach ($rule as $item) {
            if (0 === strpos($item, ':')) { // dynamicvariableObtain
                if ($pos = strpos($item, '^')) {
                    $var = substr($item, 1, $pos - 1);
                } elseif (strpos($item, '\\')) {
                    $var = substr($item, 1, -2);
                } else {
                    $var = substr($item, 1);
                }
                $matches[$var] = array_shift($paths);
            } else { // filterURLmiddleStatic statevariable
                array_shift($paths);
            }
        }
        if (0 === strpos($url, '/') || 0 === strpos($url, 'http')) { // routingRedirectJump
            if (strpos($url, ':')) { // Pass dynamicparameter
                $values = array_values($matches);
                $url = preg_replace('/:(\d)/e', '$values[\\1-1]', $url);
            }
            header("Location: $url", true, isset($route[2]) ? $route[2] : 301);
            exit;
        } else {
            // Resolveroutingaddress
            $var = self::parseUrl($url);
            // ResolveroutingaddressInside the dynamic parameters
            $values = array_values($matches);
            foreach ($var as $key => $val) {
                if (0 === strpos($val, ':')) {
                    $var[$key] = $values[substr($val, 1) - 1];
                }
            }
            $var = array_merge($matches, $var);
            // Resolve the remaining URL parameters
            if ($paths) {
                preg_replace('@(\w+)\/([^,\/]+)@e', '$var[strtolower(\'\\1\')]="\\2";', implode('/', $paths));
            }
            // Resolveroutingautomaticsuccessorparameter
            if (isset($route[2])) {
                parse_str($route[2], $params);
                $var = array_merge($var, $params);
            }
            $_GET = array_merge($var, $_GET);
        }
        return true;
    }

    // ResolveRegularrouting
    // array('Regular route','[Packet/Module/operating]?parameter1=value1&parameter2=value2...','Additional parameters','Request type','Resource Type')
    // array('Regular route','External address','Redirect code','Request type','Resource Type')
    // parameterValue andOutsideaddressYou can use dynamicvariable use :1 :2 The way
    // array('/new\/(\d+)\/(\d+)/','News/read?id=:1&page=:2&cate=1','status=1','post','html,xml'),
    // array('/new\/(\d+)/','/new.php?id=:1&page=:2&status=1','301','get','html,xml'), Redirect
    static private function parseRegex($matches, $route, $regx)
    {
        // Obtainroutingaddressrule
        $url = preg_replace('/:(\d)/e', '$matches[\\1]', $route[1]);
        if (0 === strpos($url, '/') || 0 === strpos($url, 'http')) { // routingRedirectJump
            header("Location: $url", true, isset($route[1]) ? $route[2] : 301);
            exit;
        } else {
            // Resolveroutingaddress
            $var = self::parseUrl($url);
            // Resolve the remaining URL parameters
            $regx = substr_replace($regx, '', 0, strlen($matches[0]));
            if ($regx) {
                preg_replace('@(\w+)\/([^,\/]+)@e', '$var[strtolower(\'\\1\')]="\\2";', $regx);
            }
            // Resolveroutingautomaticsuccessorparameter
            if (isset($route[2])) {
                parse_str($route[2], $params);
                $var = array_merge($var, $params);
            }
            $_GET = array_merge($var, $_GET);
        }
        return true;
    }
}