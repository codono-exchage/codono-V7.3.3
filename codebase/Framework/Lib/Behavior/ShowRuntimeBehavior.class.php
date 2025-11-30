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
 * System behavior extension：runtimeinformationdisplay
 * @category   Think
 * @package  Think
 * @subpackage  Behavior
 * @author   liu21st <liu21st@gmail.com>
 */
class ShowRuntimeBehavior extends Behavior
{
    // Behavioral parametersdefinition
    protected $options = array(
        'SHOW_RUN_TIME' => false,   // runtimedisplay
        'SHOW_ADV_TIME' => false,   // Show running time
        'SHOW_DB_TIMES' => false,   // displaydatabaseInquirewithWritefrequency
        'SHOW_CACHE_TIMES' => false,   // Displays the number of cache operation
        'SHOW_USE_MEM' => false,   // displayMemory overhead
        'SHOW_LOAD_FILE' => false,   // Load display the number of files
        'SHOW_FUN_TIMES' => false,  // Shows the number of function calls
    );

    // Behavior extensionofExecution entrymust berun
    public function run(&$content)
    {
        if (C('SHOW_RUN_TIME')) {
            if (false !== strpos($content, '{__NORUNTIME__}')) {
                $content = str_replace('{__NORUNTIME__}', '', $content);
            } else {
                $runtime = $this->showTime();
                if (strpos($content, '{__RUNTIME__}'))
                    $content = str_replace('{__RUNTIME__}', $runtime, $content);
                else
                    $content .= $runtime;
            }
        } else {
            $content = str_replace(array('{__NORUNTIME__}', '{__RUNTIME__}'), '', $content);
        }
    }

    /**
     * displayruntime,dataStorehouseoperating,Cachefrequency,RAMuseinformation
     * @access private
     * @return string
     */
    private function showTime()
    {
        // displayruntime
        G('beginTime', $GLOBALS['_beginTime']);
        G('viewEndTime');
        $showTime = 'Process: ' . G('beginTime', 'viewEndTime') . 's ';
        if (C('SHOW_ADV_TIME')) {
            // displaydetailedruntime
            $showTime .= '( Load:' . G('beginTime', 'loadTime') . 's Init:' . G('loadTime', 'initTime') . 's Exec:' . G('initTime', 'viewStartTime') . 's Template:' . G('viewStartTime', 'viewEndTime') . 's )';
        }
        if (C('SHOW_DB_TIMES') && class_exists('Db', false)) {
            // displaydatabaseoperatingfrequency
            $showTime .= ' | DB :' . N('db_query') . ' queries ' . N('db_write') . ' writes ';
        }
        if (C('SHOW_CACHE_TIMES') && class_exists('Cache', false)) {
            // displayCacheRead and write times
            $showTime .= ' | Cache :' . N('cache_read') . ' gets ' . N('cache_write') . ' writes ';
        }
        if (MEMORY_LIMIT_ON && C('SHOW_USE_MEM')) {
            // displayMemory overhead
            $showTime .= ' | UseMem:' . number_format((memory_get_usage() - $GLOBALS['_startUseMems']) / 1024) . ' kb';
        }
        if (C('SHOW_LOAD_FILE')) {
            $showTime .= ' | LoadFile:' . count(get_included_files());
        }
        if (C('SHOW_FUN_TIMES')) {
            $fun = get_defined_functions();
            $showTime .= ' | CallFun:' . count($fun['user']) . ',' . count($fun['internal']);
        }
        return $showTime;
    }
}