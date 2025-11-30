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
 * System behavior extension：Static stateCacheRead
 * @category   Think
 * @package  Think
 * @subpackage  Behavior
 * @author   liu21st <liu21st@gmail.com>
 */
class ReadHtmlCacheBehavior extends Behavior
{
    protected $options = array(
        'HTML_CACHE_ON' => false,
        'HTML_CACHE_TIME' => 60,
        'HTML_CACHE_RULES' => array(),
        'HTML_FILE_SUFFIX' => '.html',
    );

    // Behavior extensionofExecution entrymust berun
    public function run(&$params)
    {
        // OpenStatic stateCache
        if (C('HTML_CACHE_ON')) {
            $cacheTime = $this->requireHtmlCache();
            if (false !== $cacheTime && $this->checkHTMLCache(HTML_FILE_NAME, $cacheTime)) { //Static statepageeffective
                // ReadStatic statepageExport
                readfile(HTML_FILE_NAME);
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
            $moduleName = strtolower(MODULE_NAME);
            $actionName = strtolower(ACTION_NAME);
            if (isset($htmls[$moduleName . ':' . $actionName])) {
                $html = $htmls[$moduleName . ':' . $actionName];   // AModuleofoperatingStaticrule
            } elseif (isset($htmls[$moduleName . ':'])) {// A module of static rule
                $html = $htmls[$moduleName . ':'];
            } elseif (isset($htmls[$actionName])) {
                $html = $htmls[$actionName]; // alloperatingStaticrule
            } elseif (isset($htmls['*'])) {
                $html = $htmls['*']; // Overall situationStatic staterule
            } elseif (isset($htmls['empty:index']) && !class_exists(MODULE_NAME . 'Action')) {
                $html = $htmls['empty:index']; // Empty module static rule
            } elseif (isset($htmls[$moduleName . ':_empty']) && self::isEmptyAction(MODULE_NAME, ACTION_NAME)) {
                $html = $htmls[$moduleName . ':_empty']; // No operation static rule
            }
            if (!empty($html)) {
                // ReadingStatic staterule
                $rule = $html[0];
                // With$_The beginning ofSystem Variables
                $rule = preg_replace('/{\$(_\w+)\.(\w+)\|(\w+)}/e', "\\3(\$\\1['\\2'])", $rule);
                $rule = preg_replace('/{\$(_\w+)\.(\w+)}/e', "\$\\1['\\2']", $rule);
                // {ID|FUN} GETShorthand variables
                $rule = preg_replace('/{(\w+)\|(\w+)}/e', "\\2(\$_GET['\\1'])", $rule);
                $rule = preg_replace('/{(\w+)}/e', "\$_GET['\\1']", $rule);
                // specialSystem Variables
                $rule = str_ireplace(
                    array('{:app}', '{:module}', '{:action}', '{:group}'),
                    array(APP_NAME, MODULE_NAME, ACTION_NAME, defined('GROUP_NAME') ? GROUP_NAME : ''),
                    $rule);
                // {|FUN} Used alone function
                $rule = preg_replace('/{|(\w+)}/e', "\\1()", $rule);
                if (!empty($html[2])) $rule = $html[2]($rule); // applicationAdditionalfunction
                $cacheTime = isset($html[1]) ? $html[1] : C('HTML_CACHE_TIME'); // Cache Expiration
                // currentCachefile
                define('HTML_FILE_NAME', HTML_PATH . $rule . C('HTML_FILE_SUFFIX'));
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
        if (!is_file($cacheFile)) {
            return false;
        } elseif (filemtime(C('TEMPLATE_NAME')) > filemtime($cacheFile)) {
            // Template filesin caseUpdateStatic statefileneedUpdate
            return false;
        } elseif (!is_numeric($cacheTime) && function_exists($cacheTime)) {
            return $cacheTime($cacheFile);
        } elseif ($cacheTime != 0 && NOW_TIME > filemtime($cacheFile) + $cacheTime) {
            // filewhetherinValidity
            return false;
        }
        //Static statefileeffective
        return true;
    }

    //Detecting whether a non-operation
    static private function isEmptyAction($module, $action)
    {
        $className = $module . 'Action';
        $class = new $className;
        return !method_exists($class, $action);
    }

}