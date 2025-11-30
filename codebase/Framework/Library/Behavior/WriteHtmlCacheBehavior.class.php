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
 * System behavior extension：Static stateCacheWrite
 */
class WriteHtmlCacheBehavior
{

    // Behavior extensionofExecution entrymust berun
    public function run(&$content)
    {
        //2014-11-28 modify If there isHTTP 4xx 3xx 5xx Head from being stored
        //2014-12-1 modify URL for injection Prevent the formation of, e.g. /game/lst/SortType/hot/-e8-90-8c-e5-85-94-e7-88-b1-e6-b6-88-e9-99-a4/-e8-bf-9b-e5-87-bb-e7-9a-84-e9-83-a8-e8-90-bd/-e9-a3-8e-e4-ba-91-e5-a4-a9-e4-b8-8b/index.shtml
        if (C('HTML_CACHE_ON') && defined('HTML_FILE_NAME')
            && !preg_match('/Status.*[345]{1}\d{2}/i', implode(' ', headers_list()))
            && !preg_match('/(-[a-z0-9]{2}){3,}/i', HTML_FILE_NAME)) {
            //Static stateFile Write
            Storage::put(HTML_FILE_NAME, $content, 'html');
        }
    }
}