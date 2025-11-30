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
 * System behavior extension：pageTraceDisplay output
 * @category   Think
 * @package  Think
 * @subpackage  Behavior
 * @author   liu21st <liu21st@gmail.com>
 */
class ShowPageTraceBehavior extends Behavior
{
    // Behavioral parametersdefinition
    protected $options = array(
        'SHOW_PAGE_TRACE' => false,   // displaypageTraceinformation
        'TRACE_PAGE_TABS' => array('BASE' => 'Basic', 'FILE' => 'file', 'INFO' => 'Process', 'ERR|NOTIC' => 'error', 'SQL' => 'SQL', 'DEBUG' => 'debugging'), // pageTraceCustomizable tab 
        'PAGE_TRACE_SAVE' => false,
    );

    // Behavior extensionofExecution entrymust berun
    public function run(&$params)
    {
        if (!IS_AJAX && C('SHOW_PAGE_TRACE')) {
            echo $this->showTrace();
        }
    }

    /**
     * displaypageTraceinformation
     * @access private
     */
    private function showTrace()
    {
        // systemdefaultdisplayinformation
        $files = get_included_files();
        $info = array();
        foreach ($files as $key => $file) {
            $info[] = $file . ' ( ' . number_format(filesize($file) / 1024, 2) . ' KB )';
        }
        $trace = array();
        $base = array(
            'Request information' => date('Y-m-d H:i:s', $_SERVER['REQUEST_TIME']) . ' ' . $_SERVER['SERVER_PROTOCOL'] . ' ' . $_SERVER['REQUEST_METHOD'] . ' : ' . __SELF__,
            'operation hours' => $this->showTime(),
            'Throughput' => number_format(1 / G('beginTime', 'viewEndTime'), 2) . 'req/s',
            'Memory overhead' => MEMORY_LIMIT_ON ? number_format((memory_get_usage() - $GLOBALS['_startUseMems']) / 1024, 2) . ' kb' : 'not support',
            'search information' => N('db_query') . ' queries ' . N('db_write') . ' writes ',
            'File is loaded' => count(get_included_files()),
            'Cache information' => N('cache_read') . ' gets ' . N('cache_write') . ' writes ',
            'Configuration loaded' => count(c()),
            'Session Information' => 'SESSION_ID=' . session_id(),
        );
        // ReadprojectdefinitionofTracefile
        $traceFile = CONF_PATH . 'trace.php';
        if (is_file($traceFile)) {
            $base = array_merge($base, include $traceFile);
        }
        $debug = trace();
        $tabs = C('TRACE_PAGE_TABS');
        foreach ($tabs as $name => $title) {
            switch (strtoupper($name)) {
                case 'BASE':// Basicinformation
                    $trace[$title] = $base;
                    break;
                case 'FILE': // File information
                    $trace[$title] = $info;
                    break;
                default:// debugginginformation
                    $name = strtoupper($name);
                    if (strpos($name, '|')) {// ainformation
                        $array = explode('|', $name);
                        $result = array();
                        foreach ($array as $name) {
                            $result += isset($debug[$name]) ? $debug[$name] : array();
                        }
                        $trace[$title] = $result;
                    } else {
                        $trace[$title] = isset($debug[$name]) ? $debug[$name] : '';
                    }
            }
        }
        if ($save = C('PAGE_TRACE_SAVE')) { // StoragepageTraceJournal
            if (is_array($save)) {// selectTabStorage
                $tabs = C('TRACE_PAGE_TABS');
                $array = array();
                foreach ($save as $tab) {
                    $array[] = $tabs[$tab];
                }
            }
            $content = date('[ c ]') . ' ' . get_client_ip() . ' ' . $_SERVER['REQUEST_URI'] . "\r\n";
            foreach ($trace as $key => $val) {
                if (!isset($array) || in_array($key, $array)) {
                    $content .= '[ ' . $key . " ]\r\n";
                    if (is_array($val)) {
                        foreach ($val as $k => $v) {
                            $content .= (!is_numeric($k) ? $k . ':' : '') . print_r($v, true) . "\r\n";
                        }
                    } else {
                        $content .= print_r($val, true) . "\r\n";
                    }
                    $content .= "\r\n";
                }
            }
            error_log(str_replace('<br/>', "\r\n", $content), Log::FILE, LOG_PATH . date('y_m_d') . '_trace.log');
        }
        unset($files, $info, $base);
        // transferTracepagetemplate
        ob_start();
        include C('TMPL_TRACE_FILE') ? C('TMPL_TRACE_FILE') : THINK_PATH . 'Tpl/page_trace.tpl';
        return ob_get_clean();
    }

    /**
     * Get run time
     */
    private function showTime()
    {
        // displayruntime
        G('beginTime', $GLOBALS['_beginTime']);
        G('viewEndTime');
        // displaydetailedruntime
        return G('beginTime', 'viewEndTime') . 's ( Load:' . G('beginTime', 'loadTime') . 's Init:' . G('loadTime', 'initTime') . 's Exec:' . G('initTime', 'viewStartTime') . 's Template:' . G('viewStartTime', 'viewEndTime') . 's )';
    }
}
