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
// $Id: tags.php 2802 2012-03-06 06:19:07Z liu21st $

// Rest System behavior extensionListfile
return array(
    'app_begin' => array(
        'ReadHtmlCache', // ReadStatic stateCache
    ),
    'route_check' => array(
        'CheckRestRoute', // Route detection
    ),
    'view_end' => array(
        'ShowPageTrace', // pageTracedisplay
    ),
    'view_template' => array(
        'LocationTemplate', // Automatic positioning template file
    ),
    'view_parse' => array(
        'ParseTemplate', // templateResolve stand byPHP,InternalTemplate enginewithThird partyTemplate engine
    ),
    'view_filter' => array(
        'ContentReplace', // templateExportreplace
        'TokenBuild',   // FormsToken
        'WriteHtmlCache', // WriteStatic stateCache
        'ShowRuntime', // runtimedisplay
    ),
    'path_info' => array(
        'CheckUrlExt'
    ),
);