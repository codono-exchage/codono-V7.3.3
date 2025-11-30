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
 * ThinkPHP APISchema Definition
 */
return array(
    //  Configuration file 
    'config' => array(
        THINK_PATH . 'Conf/convention.php',   // systemConventionConfiguration
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
        MODE_PATH . 'Api/functions.php',
        COMMON_PATH . 'Common/function.php',
        MODE_PATH . 'Api/App' . EXT,
        MODE_PATH . 'Api/Dispatcher' . EXT,
        MODE_PATH . 'Api/Controller' . EXT,
        CORE_PATH . 'Behavior' . EXT,
    ),
    // Behavior extensiondefinition
    'tags' => array(),
);
