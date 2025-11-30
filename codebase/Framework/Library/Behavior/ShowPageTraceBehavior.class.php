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

use Think\Log;

/**
 * System behavior extension: PageTrace display output
 */
class ShowPageTraceBehavior
{
    protected $tracePageTabs = array('BASE' => 'Basic', 'FILE' => 'File', 'INFO' => 'Info', 'ERR|NOTIC' => 'Error', 'SQL' => 'SQL', 'DEBUG' => 'Debug');

    // Behavior extension: Entry point for execution
    public function run(&$params)
    {
        if (!IS_AJAX && !IS_CLI && C('SHOW_PAGE_TRACE')) {
            echo $this->showTrace();
        }
    }

    /**
     * Display PageTrace information
     * @access private
     */
    private function showTrace()
    {
        // System default display information
        $files = get_included_files();
        $info = array();
        foreach ($files as $key => $file) {
            $info[] = $file . ' ( ' . number_format(filesize($file) / 1024, 2) . ' KB )';
        }
        $trace = array();
        $base = array(
            'Request' => date('Y-m-d H:i:s', $_SERVER['REQUEST_TIME']) . ' ' . $_SERVER['SERVER_PROTOCOL'] . ' ' . $_SERVER['REQUEST_METHOD'] . ' : ' . __SELF__,
            'Time taken' => $this->showTime(),
            'Throughput' => number_format(1 / G('beginTime', 'viewEndTime'), 2) . ' req/s',
            'Memory overhead' => MEMORY_LIMIT_ON ? number_format((memory_get_usage() - $GLOBALS['_startUseMems']) / 1024, 2) . ' kb' : 'not supported',
            'SQL Count' => N('db_query') . ' queries ' . N('db_write') . ' writes ',
            'Files Count' => count(get_included_files()),
            'Caching Count' => N('cache_read') . ' gets ' . N('cache_write') . ' writes ',
            'Config Count' => count(C()),
            'Session' => 'SESSION_ID=' . session_id(),
        );
        // Read Application-defined Trace file
        $traceFile = COMMON_PATH . 'Conf/trace.php';
        if (is_file($traceFile)) {
            $base = array_merge($base, include $traceFile);
        }
        $debug = trace();

        $tabs = C('TRACE_PAGE_TABS', null, $this->tracePageTabs);

        foreach ($tabs as $name => $title) {
            switch (strtoupper($name)) {
                case 'BASE': // Basic information
                    $trace[$title] = $base;
                    break;
                case 'FILE': // File information
                    $trace[$title] = $info;
                    break;
                default: // Debugging information
                    $name = strtoupper($name);
                    if (strpos($name, '|')) { // Combine information
                        $names = explode('|', $name);
                        $result = array();
                        foreach ($names as $name) {
                            $result += isset($debug[$name]) ? $debug[$name] : array();
                        }
                        $trace[$title] = $result;
                    } else {
	  
                        $trace[$title] = isset($debug[$name]) ? $debug[$name] : '';
                    }
            }
        }

        if ($save = C('PAGE_TRACE_SAVE')) { // Store PageTrace Journal
            if (is_array($save)) { // Select tab for storage
                $tabs = C('TRACE_PAGE_TABS', null, $this->tracePageTabs);
                $array = array();
                foreach ($save as $tab) {
                    $array[] = $tabs[$tab];
                }
            }
            $content = date('[ c ]') . ' ' . get_client_ip() . ' ' . $_SERVER['REQUEST_URI'] . "\r\n";
            foreach ($trace as $key => $val) {
                if (!isset($array) || in_array_case($key, $array)) {
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
            error_log(str_replace('<br/>', "\r\n", $content), 3, C('LOG_PATH') . date('y_m_d') . '_trace.log');
        }
        unset($files, $info, $base);
        // Render the PageTrace template
        ob_start();
        include C('TMPL_TRACE_FILE') ? C('TMPL_TRACE_FILE') : THINK_PATH . 'Tpl/page_trace.tpl';
        return ob_get_clean();
    }

    /**
     * Get run time
     */
    private function showTime()
    {
        // Display runtime
        G('beginTime', $GLOBALS['_beginTime']);
        G('viewEndTime');
        // Display detailed runtime
        return G('beginTime', 'viewEndTime') . 's ( Load:' . G('beginTime', 'loadTime') . 's Init:' . G('loadTime', 'initTime') . 's Exec:' . G('initTime', 'viewStartTime') . 's Template:' . G('viewStartTime', 'viewEndTime') . 's )';
    }
}
