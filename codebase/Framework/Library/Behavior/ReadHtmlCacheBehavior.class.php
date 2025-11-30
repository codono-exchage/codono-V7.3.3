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
namespace Behavior;

use Think\Storage;

/**
 * System behavior extension：Static stateCacheRead
 */
class ReadHtmlCacheBehavior
{
    // Behavior extensionofExecution entrymust berun
    public function run(&$params)
    {
        // OpenStatic stateCache
        if (IS_GET && C('HTML_CACHE_ON')) {
            $cacheTime = $this->requireHtmlCache();
            if (false !== $cacheTime && $this->checkHTMLCache(HTML_FILE_NAME, $cacheTime)) { //Static statepageeffective
                // ReadStatic statepageExport
                echo Storage::read(HTML_FILE_NAME, 'html');
                exit();
            }
        }
    }

    // Determine whetherneedStatic stateCache
    static private function requireHtmlCache()
    {
        // analysiscurrentStaticrule
        $htmls = C('HTML_CACHE_RULES'); // ReadStatic staterule
        if (!empty($htmls)) {
            $htmls = array_change_key_case($htmls);
            // Static stateruleFile definitionformat actionName=>array('Static rules','Cache Time','Additional rules')
            // 'read'=>array('{id},{name}',60,'md5') have toGuaranteeStatic stateruleofUniqueness with Be judgmental
            // DetectStatic staterule
            $controllerName = strtolower(CONTROLLER_NAME);
            $actionName = strtolower(ACTION_NAME);
            if (isset($htmls[$controllerName . ':' . $actionName])) {
                $html = $htmls[$controllerName . ':' . $actionName];   // AControllerofoperatingStaticrule
            } elseif (isset($htmls[$controllerName . ':'])) {// AControllerStaticrule
                $html = $htmls[$controllerName . ':'];
            } elseif (isset($htmls[$actionName])) {
                $html = $htmls[$actionName]; // alloperatingStaticrule
            } elseif (isset($htmls['*'])) {
                $html = $htmls['*']; // Overall situationStatic staterule
            }
            if (!empty($html)) {
                // ReadingStatic staterule
                $rule = is_array($html) ? $html[0] : $html;
                // With$_The beginning ofSystem Variables
                $callback = function ($match) {
                    switch ($match[1]) {
                        case '_GET':
                            $var = $_GET[$match[2]];
                            break;
                        case '_POST':
                            $var = $_POST[$match[2]];
                            break;
                        case '_REQUEST':
                            $var = $_REQUEST[$match[2]];
                            break;
                        case '_SERVER':
                            $var = $_SERVER[$match[2]];
                            break;
                        case '_SESSION':
                            $var = $_SESSION[$match[2]];
                            break;
                        case '_COOKIE':
                            $var = $_COOKIE[$match[2]];
                            break;
                    }
                    return (count($match) == 4) ? $match[3]($var) : $var;
                };
                $rule = preg_replace_callback('/{\$(_\w+)\.(\w+)(?:\|(\w+))?}/', $callback, $rule);
                // {ID|FUN} GETShorthand variables
                $rule = preg_replace_callback('/{(\w+)\|(\w+)}/', function ($match) {
                    return $match[2]($_GET[$match[1]]);
                }, $rule);
                $rule = preg_replace_callback('/{(\w+)}/', function ($match) {
                    return $_GET[$match[1]];
                }, $rule);
                // specialSystem Variables
                $rule = str_ireplace(
                    array('{:controller}', '{:action}', '{:module}'),
                    array(CONTROLLER_NAME, ACTION_NAME, MODULE_NAME),
                    $rule);
                // {|FUN} Used alone function
                $rule = preg_replace_callback('/{|(\w+)}/', function ($match) {
                    return $match[1]();
                }, $rule);
                $cacheTime = C('HTML_CACHE_TIME', null, 60);
                if (is_array($html)) {
                    if (!empty($html[2])) $rule = $html[2]($rule); // applicationAdditionalfunction
                    $cacheTime = isset($html[1]) ? $html[1] : $cacheTime; // Cache Expiration
                } else {
                    $cacheTime = $cacheTime;
                }

                // currentCachefile
                define('HTML_FILE_NAME', HTML_PATH . $rule . C('HTML_FILE_SUFFIX', null, '.html'));
                return $cacheTime;
            }
        }
        // No needCache
        return false;
    }

    /**
     * Check the staticHTMLThe file is valid
     * If there is noeffectneedAgainUpdate
     * @access public
     * @param string $cacheFile Static statefilename
     * @param integer $cacheTime Cache Expiration
     * @return boolean
     */
    static public function checkHTMLCache($cacheFile = '', $cacheTime = '')
    {
        if (!is_file($cacheFile) && 'sae' != APP_MODE) {
            return false;
        } elseif (filemtime(\Think\Think::instance('Think\View')->parseTemplate()) > Storage::get($cacheFile, 'mtime', 'html')) {
            // Template filesin caseUpdateStatic statefileneedUpdate
            return false;
        } elseif (!is_numeric($cacheTime) && function_exists($cacheTime)) {
            return $cacheTime($cacheFile);
        } elseif ($cacheTime != 0 && NOW_TIME > Storage::get($cacheFile, 'mtime', 'html') + $cacheTime) {
            // filewhetherinValidity
            return false;
        }
        //Static statefileeffective
        return true;
    }

}