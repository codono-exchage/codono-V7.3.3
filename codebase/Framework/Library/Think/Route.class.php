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
 * ThinkPHPRouting resolution class
 */
class Route
{

    // Route detection
    public static function check()
    {
        $depr = C('URL_PATHINFO_DEPR');
        $regx = preg_replace('/\.' . __EXT__ . '$/i', '', trim($_SERVER['PATH_INFO'], $depr));
        // Separatedsymbolreplace make sureroutingdefinitionuseUniteofSeparatedsymbol
        if ('/' != $depr) {
            $regx = str_replace($depr, '/', $regx);
        }
        // URLMappingdefinition(Staticrouting)
        $maps = C('URL_MAP_RULES');
        if (isset($maps[$regx])) {
            $var = self::parseUrl($maps[$regx]);
            $_GET = array_merge($var, $_GET);
            return true;
        }
        // Dynamic routing process
        $routes = C('URL_ROUTE_RULES');
        if (!empty($routes)) {
            foreach ($routes as $rule => $route) {
                if (is_numeric($rule)) {
                    // stand by array('rule','adddress',...) Define the route
                    $rule = array_shift($route);
                }
                if (is_array($route) && isset($route[2])) {
                    // Routing parameters
                    $options = $route[2];
                    if (isset($options['ext']) && __EXT__ != $options['ext']) {
                        // URLSuffix detection
                        continue;
                    }
                    if (isset($options['method']) && REQUEST_METHOD != strtoupper($options['method'])) {
                        // Request type detection
                        continue;
                    }
                    // fromdefinitionDetect
                    if (!empty($options['callback']) && is_callable($options['callback'])) {
                        if (false === call_user_func($options['callback'])) {
                            continue;
                        }
                    }
                }
                if (0 === strpos($rule, '/') && preg_match($rule, $regx, $matches)) { // Regularrouting
                    if ($route instanceof \Closure) {
                        // carried outClosure
                        $result = self::invokeRegx($route, $matches);
                        // If the returnBoolean value Continuecarried out
                        return is_bool($result) ? $result : exit;
                    } else {
                        return self::parseRegex($matches, $route, $regx);
                    }
                } else { // rulerouting
                    $len1 = substr_count($regx, '/');
                    $len2 = substr_count($rule, '/');
                    if ($len1 >= $len2 || strpos($rule, '[')) {
                        if ('$' == substr($rule, -1, 1)) {// Exact hits
                            if ($len1 != $len2) {
                                continue;
                            } else {
                                $rule = substr($rule, 0, -1);
                            }
                        }
                        $match = self::checkUrlMatch($regx, $rule);
                        if (false !== $match) {
                            if ($route instanceof \Closure) {
                                // carried outClosure
                                $result = self::invokeRule($route, $match);
                                // If the returnBoolean value Continuecarried out
                                return is_bool($result) ? $result : exit;
                            } else {
                                return self::parseRule($rule, $route, $regx);
                            }
                        }
                    }
                }
            }
        }
        return false;
    }

    // DetectURLAnd rulesroutingwhethermatch
    private static function checkUrlMatch($regx, $rule)
    {
        $m1 = explode('/', $regx);
        $m2 = explode('/', $rule);
        $var = array();
        foreach ($m2 as $key => $val) {
            if (0 === strpos($val, '[:')) {
                $val = substr($val, 1, -1);
            }

            if (':' == substr($val, 0, 1)) {// dynamicvariable
                if ($pos = strpos($val, '|')) {
                    // Use a filter function
                    $val = substr($val, 1, $pos - 1);
                }
                if (strpos($val, '\\')) {
                    $type = substr($val, -1);
                    if ('d' == $type) {
                        if (isset($m1[$key]) && !is_numeric($m1[$key]))
                            return false;
                    }
                    $name = substr($val, 1, -2);
                } elseif ($pos = strpos($val, '^')) {
                    $array = explode('-', substr(strstr($val, '^'), 1));
                    if (in_array($m1[$key], $array)) {
                        return false;
                    }
                    $name = substr($val, 1, $pos - 1);
                } else {
                    $name = substr($val, 1);
                }
                $var[$name] = isset($m1[$key]) ? $m1[$key] : '';
            } elseif (0 !== strcasecmp($val, $m1[$key])) {
                return false;
            }
        }
        // After a successful return matchURLThe dynamic array of variables
        return $var;
    }

    // Resolvespecificationofroutingaddress
    // Address Format [Controller/operating?]parameter1=value1&parameter2=value2...
    private static function parseUrl($url)
    {
        $var = array();
        if (false !== strpos($url, '?')) { // [Controller/operating?]parameter1=value1&parameter2=value2...
            $info = parse_url($url);
            $path = explode('/', $info['path']);
            parse_str($info['query'], $var);
        } elseif (strpos($url, '/')) { // [Controller/operating]
            $path = explode('/', $url);
        } else { // parameter1=value1&parameter2=value2...
            parse_str($url, $var);
        }
        if (isset($path)) {
            $var[C('VAR_ACTION')] = array_pop($path);
            if (!empty($path)) {
                $var[C('VAR_CONTROLLER')] = array_pop($path);
            }
            if (!empty($path)) {
                $var[C('VAR_MODULE')] = array_pop($path);
            }
        }
        return $var;
    }

    // Resolverulerouting
    // 'Routing rules'=>'[Controller/operating]?Additional parameters1=value1&Additional parameters2=value2...'
    // 'Routing rules'=>array('[Controller/operating]','Additional parameters1=value1&Additional parameters2=value2...')
    // 'Routing rules'=>'External address'
    // 'Routing rules'=>array('External address','Redirect code')
    // routingrulein :beginning Showdynamicvariable
    // OutsideaddressYou can use dynamicvariable use :1 :2 The way
    // 'news/:month/:day/:id'=>array('News/read?cate=1','status=1'),
    // 'new/:id'=>array('/new.php?id=:1',301), Redirect
    private static function parseRule($rule, $route, $regx)
    {
        // Obtainroutingaddressrule
        $url = is_array($route) ? $route[0] : $route;
        // ObtainURLaddressmiddleparameter
        $paths = explode('/', $regx);
        // Resolveroutingrule
        $matches = array();
        $rule = explode('/', $rule);
        foreach ($rule as $item) {
            $fun = '';
            if (0 === strpos($item, '[:')) {
                $item = substr($item, 1, -1);
            }
            if (0 === strpos($item, ':')) { // dynamicvariableObtain
                if ($pos = strpos($item, '|')) {
                    // Support filtering function
                    $fun = substr($item, $pos + 1);
                    $item = substr($item, 0, $pos);
                }
                if ($pos = strpos($item, '^')) {
                    $var = substr($item, 1, $pos - 1);
                } elseif (strpos($item, '\\')) {
                    $var = substr($item, 1, -2);
                } else {
                    $var = substr($item, 1);
                }
                $matches[$var] = !empty($fun) ? $fun(array_shift($paths)) : array_shift($paths);
            } else { // filterURLmiddleStatic statevariable
                array_shift($paths);
            }
        }

        if (0 === strpos($url, '/') || 0 === strpos($url, 'http')) { // routingRedirectJump
            if (strpos($url, ':')) { // Pass dynamicparameter
                $values = array_values($matches);
                $url = preg_replace_callback('/:(\d+)/', function ($match) use ($values) {
                    return $values[$match[1] - 1];
                }, $url);
            }
            header("Location: $url", true, (is_array($route) && isset($route[1])) ? $route[1] : 301);
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
            if (!empty($paths)) {
                preg_replace_callback('/(\w+)\/([^\/]+)/', function ($match) use (&$var) {
                    $var[strtolower($match[1])] = strip_tags($match[2]);
                }, implode('/', $paths));
            }
            // ResolveroutingautomaticIncoming parameters
            if (is_array($route) && isset($route[1])) {
                if (is_array($route[1])) {
                    $params = $route[1];
                } else {
                    parse_str($route[1], $params);
                }
                $var = array_merge($var, $params);
            }
            $_GET = array_merge($var, $_GET);
        }
        return true;
    }

    // ResolveRegularrouting
    // 'Regular route'=>'[Controller/operating]?parameter1=value1&parameter2=value2...'
    // 'Regular route'=>array('[Controller/operating]?parameter1=value1&parameter2=value2...','Additional parameters1=value1&Additional parameters2=value2...')
    // 'Regular route'=>'External address'
    // 'Regular route'=>array('External address','Redirect code')
    // parameterValue andOutsideaddressYou can use dynamicvariable use :1 :2 The way
    // '/new\/(\d+)\/(\d+)/'=>array('News/read?id=:1&page=:2&cate=1','status=1'),
    // '/new\/(\d+)/'=>array('/new.php?id=:1&page=:2&status=1','301'), Redirect
    private static function parseRegex($matches, $route, $regx)
    {
        // Obtainroutingaddressrule
        $url = is_array($route) ? $route[0] : $route;
        $url = preg_replace_callback('/:(\d+)/', function ($match) use ($matches) {
            return $matches[$match[1]];
        }, $url);
        if (0 === strpos($url, '/') || 0 === strpos($url, 'http')) { // routingRedirectJump
            header("Location: $url", true, (is_array($route) && isset($route[1])) ? $route[1] : 301);
            exit;
        } else {
            // Resolveroutingaddress
            $var = self::parseUrl($url);
            // Handler
            foreach ($var as $key => $val) {
                if (strpos($val, '|')) {
                    list($val, $fun) = explode('|', $val);
                    $var[$key] = $fun($val);
                }
            }
            // Resolve the remaining URL parameters
            $regx = substr_replace($regx, '', 0, strlen($matches[0]));
            if ($regx) {
                preg_replace_callback('/(\w+)\/([^\/]+)/', function ($match) use (&$var) {
                    $var[strtolower($match[1])] = strip_tags($match[2]);
                }, $regx);
            }
            // ResolveroutingautomaticIncoming parameters
            if (is_array($route) && isset($route[1])) {
                if (is_array($route[1])) {
                    $params = $route[1];
                } else {
                    parse_str($route[1], $params);
                }
                $var = array_merge($var, $params);
            }
            $_GET = array_merge($var, $_GET);
        }
        return true;
    }

    // carried outRegularmatch下ofClosuremethod Support parameter called
    static private function invokeRegx($closure, $var = array())
    {
        $reflect = new \ReflectionFunction($closure);
        $params = $reflect->getParameters();
        $args = array();
        array_shift($var);
        foreach ($params as $param) {
            if (!empty($var)) {
                $args[] = array_shift($var);
            } elseif ($param->isDefaultValueAvailable()) {
                $args[] = $param->getDefaultValue();
            }
        }
        return $reflect->invokeArgs($args);
    }

    // carried outrulematch下ofClosuremethod Support parameter called
    static private function invokeRule($closure, $var = array())
    {
        $reflect = new \ReflectionFunction($closure);
        $params = $reflect->getParameters();
        $args = array();
        foreach ($params as $param) {
            $name = $param->getName();
            if (isset($var[$name])) {
                $args[] = $var[$name];
            } elseif ($param->isDefaultValueAvailable()) {
                $args[] = $param->getDefaultValue();
            }
        }
        return $reflect->invokeArgs($args);
    }

}