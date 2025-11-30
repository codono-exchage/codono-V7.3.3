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

defined('THINK_PATH') or exit();

/**
 * System behavior extension：operatingRoute detection
 * @category   Think
 * @package  Think
 * @subpackage  Behavior
 * @author   liu21st <liu21st@gmail.com>
 */
class CheckActionRouteBehavior extends Behavior
{

    // Behavior extensionofExecution entrymust berun
    public function run(&$config)
    {
        // priorityDetecting whetherexistPATH_INFO
        $regx = trim($_SERVER['PATH_INFO'], '/');
        if (empty($regx)) return;
        // routingdefinitionfilepriorityinconfigmiddleConfigurationdefinition
        // routingdeal with
        $routes = $config['routes'];
        if (!empty($routes)) {
            $depr = C('URL_PATHINFO_DEPR');
            // Separatedsymbolreplace make sureroutingdefinitionuseUniteofSeparatedsymbol
            $regx = str_replace($depr, '/', $regx);
            $regx = substr_replace($regx, '', 0, strlen(__URL__));
            foreach ($routes as $rule => $route) {
                if (0 === strpos($rule, '/') && preg_match($rule, $regx, $matches)) { // Regularrouting
                    return C('ACTION_NAME', $this->parseRegex($matches, $route, $regx));
                } else { // rulerouting
                    $len1 = substr_count($regx, '/');
                    $len2 = substr_count($rule, '/');
                    if ($len1 >= $len2) {
                        if ('$' == substr($rule, -1, 1)) {// Exact hits
                            if ($len1 != $len2) {
                                continue;
                            } else {
                                $rule = substr($rule, 0, -1);
                            }
                        }
                        $match = $this->checkUrlMatch($regx, $rule);
                        if ($match) return C('ACTION_NAME', $this->parseRule($rule, $route, $regx));
                    }
                }
            }
        }
    }

    // DetectURLAnd rulesroutingwhethermatch
    private function checkUrlMatch($regx, $rule)
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

    // Resolvespecificationofroutingaddress
    // Address Format operating?parameter1=value1&parameter2=value2...
    private function parseUrl($url)
    {
        $var = array();
        if (false !== strpos($url, '?')) { // operating?parameter1=value1&parameter2=value2...
            $info = parse_url($url);
            $path = $info['path'];
            parse_str($info['query'], $var);
        } else { // operating
            $path = $url;
        }
        $var[C('VAR_ACTION')] = $path;
        return $var;
    }

    // Resolverulerouting
    // 'Routing rules'=>'operating?Additional parameters1=value1&Additional parameters2=value2...'
    // 'Routing rules'=>array('Option','Additional parameters1=value1&Additional parameters2=value2...')
    // 'Routing rules'=>'External address'
    // 'Routing rules'=>array('External address','Redirect code')
    // routingrulein :beginning Showdynamicvariable
    // OutsideaddressYou can use dynamicvariable use :1 :2 The way
    // 'news/:month/:day/:id'=>array('News/read?cate=1','status=1'),
    // 'new/:id'=>array('/new.php?id=:1',301), Redirect
    private function parseRule($rule, $route, $regx)
    {
        // Obtainroutingaddressrule
        $url = is_array($route) ? $route[0] : $route;
        // ObtainURLaddressmiddleparameter
        $paths = explode('/', $regx);
        // Resolveroutingrule
        $matches = array();
        $rule = explode('/', $rule);
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
                $url = preg_replace('/:(\d+)/', '$values[\\1-1]', $url);
            }
            header("Location: $url", true, (is_array($route) && isset($route[1])) ? $route[1] : 301);
            exit;
        } else {
            // Resolveroutingaddress
            $var = $this->parseUrl($url);
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
                preg_replace('@(\w+)\/([^\/]+)@', '$var[strtolower(\'\\1\')]=strip_tags(\'\\2\');', implode('/', $paths));
            }
            // Resolveroutingautomaticsuccessorparameter
            if (is_array($route) && isset($route[1])) {
                parse_str($route[1], $params);
                $var = array_merge($var, $params);
            }
            $action = $var[C('VAR_ACTION')];
            unset($var[C('VAR_ACTION')]);
            $_GET = array_merge($var, $_GET);
            return $action;
        }
    }

    // ResolveRegularrouting
    // 'Regular route'=>'[Packet/Module/operating]?parameter1=value1&parameter2=value2...'
    // 'Regular route'=>array('[Packet/Module/operating]?parameter1=value1&parameter2=value2...','Additional parameters1=value1&Additional parameters2=value2...')
    // 'Regular route'=>'External address'
    // 'Regular route'=>array('External address','Redirect code')
    // parameterValue andOutsideaddressYou can use dynamicvariable use :1 :2 The way
    // '/new\/(\d+)\/(\d+)/'=>array('News/read?id=:1&page=:2&cate=1','status=1'),
    // '/new\/(\d+)/'=>array('/new.php?id=:1&page=:2&status=1','301'), Redirect
    private function parseRegex($matches, $route, $regx)
    {
        // Obtainroutingaddressrule
        $url = is_array($route) ? $route[0] : $route;
        $url = preg_replace('/:(\d+)/', '$matches[\\1]', $url);
        if (0 === strpos($url, '/') || 0 === strpos($url, 'http')) { // routingRedirectJump
            header("Location: $url", true, (is_array($route) && isset($route[1])) ? $route[1] : 301);
            exit;
        } else {
            // Resolveroutingaddress
            $var = $this->parseUrl($url);
            // Resolve the remaining URL parameters
            $regx = substr_replace($regx, '', 0, strlen($matches[0]));
            if ($regx) {
                preg_replace('@(\w+)\/([^,\/]+)@', '$var[strtolower(\'\\1\')]=strip_tags(\'\\2\');', $regx);
            }
            // Resolve routing automatic success or parameter
            if (is_array($route) && isset($route[1])) {
                parse_str($route[1], $params);
                $var = array_merge($var, $params);
            }
            $action = $var[C('VAR_ACTION')];
            unset($var[C('VAR_ACTION')]);
            $_GET = array_merge($var, $_GET);
        }
        return $action;
    }
}