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

// system default of core Behavior extension List file
return array(
    'app_init' => array(),
    'app_begin' => array(
        'ReadHtmlCache', // Read static cache
        'CheckLang', //Adding Multilingual support
    ),
    'route_check' => array(
        'CheckRoute', // Route detection
    ),
    'app_end' => array(),
    'path_info' => array(),
    'action_begin' => array(),
    'action_end' => array(),
    'view_begin' => array(),
    'view_parse' => array(
        'ParseTemplate', // templateResolve stand byPHP,InternalTemplate enginewithThird partyTemplate engine
    ),
    'view_filter' => array(
        'ContentReplace', // templateExportreplace
        'TokenBuild',   // FormsToken
        'WriteHtmlCache', // WriteStatic stateCache
        'ShowRuntime', // runtimedisplay
    ),
    'view_end' => array(
        'ShowPageTrace', // pageTracedisplay
    ),
);