<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2009 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: support@codono.com
// +----------------------------------------------------------------------

defined('THINK_PATH') or exit();

/**
 * Anti-refresh browser detection
 * @category   Extend
 * @package  Extend
 * @subpackage  Behavior
 * @author   liu21st <liu21st@gmail.com>
 */
class BrowserCheckBehavior extends Behavior
{
    protected $options = array(
        // BrowseAnti-Refreshtimeinterval(second)
        'LIMIT_REFLESH_TIMES' => 10,
    );

    public function run(&$params)
    {
        if ($_SERVER['REQUEST_METHOD'] == 'GET') {
            //	EnablepageAntiRefreshmechanism
            $guid = md5($_SERVER['PHP_SELF']);
            // an examinationpageRefreshinterval
            if (cookie('_last_visit_time_' . $guid) && cookie('_last_visit_time_' . $guid) > time() - C('LIMIT_REFLESH_TIMES')) {
                // pageRefreshReadBrowseDeviceCache
                header('HTTP/1.1 304 Not Modified');
                exit;
            } else {
                // Cachecurrentaddressaccesstime
                cookie('_last_visit_time_' . $guid, $_SERVER['REQUEST_TIME']);
                //header('Last-Modified:'.(date('D,d M Y H:i:s',$_SERVER['REQUEST_TIME']-C('LIMIT_REFLESH_TIMES'))).' GMT');
            }
        }
    }
}