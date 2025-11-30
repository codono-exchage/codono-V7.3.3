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
 * ThinkPHP LiteSchema Definition
 */
return array(
    //  Configuration file 
    'config' => array(
        MODE_PATH . 'Lite/convention.php', // systemConventionConfiguration
        CONF_PATH . 'config' . CONF_EXT,      // applicationpublicConfiguration
    ),

    // Alias definition
    'alias' => array(
        'Think\Exception' => CORE_PATH . 'Exception' . EXT,
        'Think\Model' => CORE_PATH . 'Model' . EXT,
        'Think\Db' => CORE_PATH . 'Db' . EXT,
        'Think\Cache' => CORE_PATH . 'Cache' . EXT,
        'Think\Cache\Driver\File' => CORE_PATH . 'Cache/Driver/File' . EXT,
        'Think\Storage' => CORE_PATH . 'Storage' . EXT,
    ),

    // functionAnd classfile
    'core' => array(
        MODE_PATH . 'Lite/functions.php',
        COMMON_PATH . 'Common/function.php',
        CORE_PATH . 'Hook' . EXT,
        CORE_PATH . 'App' . EXT,
        CORE_PATH . 'Dispatcher' . EXT,
        //CORE_PATH . 'Log'.EXT,
        CORE_PATH . 'Route' . EXT,
        CORE_PATH . 'Controller' . EXT,
        CORE_PATH . 'View' . EXT,
    ),
    // Behavior extensiondefinition
    'tags' => array(),
);
