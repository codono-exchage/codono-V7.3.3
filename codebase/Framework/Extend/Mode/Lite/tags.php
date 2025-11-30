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

// coreBehavior extensionListfile
return array(
    'app_begin' => array(
        'CheckTemplate', // - templates
    ),
    'route_check' => array('CheckRoute', // Route detection
    ),
    'app_end' => array(
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
        'ShowRuntime', // runtimedisplay
    ),
);