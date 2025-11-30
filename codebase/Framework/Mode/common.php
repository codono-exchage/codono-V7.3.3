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

/**
 * ThinkPHP Normal mode is defined
 */
return array(
    //  Configuration file 
    'config' => array(
        THINK_PATH . 'Conf/convention.php',   // systemConventionConfiguration
        CONF_PATH . 'config' . CONF_EXT,      // applicationpublicConfiguration
    ),

    // Alias definition
    'alias' => array(
        'Think\Log' => CORE_PATH . 'Log' . EXT,
        'Think\Log\Driver\File' => CORE_PATH . 'Log/Driver/File' . EXT,
        'Think\Exception' => CORE_PATH . 'Exception' . EXT,
        'Think\Model' => CORE_PATH . 'Model' . EXT,
        'Think\Db' => CORE_PATH . 'Db' . EXT,
        'Think\Template' => CORE_PATH . 'Template' . EXT,
        'Think\Cache' => CORE_PATH . 'Cache' . EXT,
        'Think\Cache\Driver\File' => CORE_PATH . 'Cache/Driver/File' . EXT,
        'Think\Storage' => CORE_PATH . 'Storage' . EXT,
    ),

    // functionAnd classfile
    'core' => array(
        THINK_PATH . 'Common/function.php',
        COMMON_PATH . 'Common/function.php',
        CORE_PATH . 'Hook' . EXT,
        CORE_PATH . 'App' . EXT,
        CORE_PATH . 'Dispatcher' . EXT,
        //CORE_PATH . 'Log'.EXT,
        CORE_PATH . 'Route' . EXT,
        CORE_PATH . 'Controller' . EXT,
        CORE_PATH . 'View' . EXT,
        BEHAVIOR_PATH . 'BuildLiteBehavior' . EXT,
        BEHAVIOR_PATH . 'ParseTemplateBehavior' . EXT,
        BEHAVIOR_PATH . 'ContentReplaceBehavior' . EXT,
    ),
    // Behavior extensiondefinition
    'tags' => array(
        'app_init' => array(
            'Behavior\BuildLiteBehavior', // FormrunLitefile
        ),
        'app_begin' => array(
            'Behavior\ReadHtmlCacheBehavior', // ReadStatic stateCache
        ),
        'app_end' => array(
            'Behavior\ShowPageTraceBehavior', // pageTracedisplay Use following ChromeShowPageTrace , FireShowPageTrace ,ShowPageTraceBehavior
        ),
        'view_parse' => array(
            'Behavior\ParseTemplateBehavior', // templateResolve stand byPHP,InternalTemplate enginewithThird partyTemplate engine
        ),
        'template_filter' => array(
            'Behavior\ContentReplaceBehavior', // templateExportreplace
        ),
        'view_filter' => array(
            'Behavior\WriteHtmlCacheBehavior', // WriteStatic stateCache
        ),
    ),
);
