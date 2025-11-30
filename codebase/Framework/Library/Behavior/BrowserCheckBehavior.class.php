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
namespace Behavior;
/**
 * Anti-refresh browser detection
 */
class BrowserCheckBehavior
{
    public function run(&$params)
    {
        if ($_SERVER['REQUEST_METHOD'] == 'GET') {
            //	EnablepageAntiRefreshmechanism
            $guid = md5($_SERVER['PHP_SELF']);
            // BrowseAnti-Refreshtimeinterval(second) The default is10
            $refleshTime = C('LIMIT_REFLESH_TIMES', null, 10);
            // an examinationpageRefreshinterval
            if (cookie('_last_visit_time_' . $guid) && cookie('_last_visit_time_' . $guid) > time() - $refleshTime) {
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