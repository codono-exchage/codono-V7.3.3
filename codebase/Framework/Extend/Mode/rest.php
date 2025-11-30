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

// RESTSchema definition file
return array(

    'core' => array(
        THINK_PATH . 'Common/functions.php', // standardModel library
        CORE_PATH . 'Core/Log.class.php',    // Log Processing category
        CORE_PATH . 'Core/Dispatcher.class.php', // URLScheduling classes
        CORE_PATH . 'Core/App.class.php',   // Application class
        CORE_PATH . 'Core/View.class.php',  // View class
        MODE_PATH . 'Rest/Action.class.php',// Controller class
    ),

    // systembehaviordefinitionfile [have to stand byArraydirectdefinitionorfilenamedefinition ]
    'extends' => MODE_PATH . 'Rest/tags.php',

    // Mode profile  [stand byArraydirectdefinitionorfilenamedefinition](IfThe samecoverproject Configuration file middleConfiguration)
    'config' => MODE_PATH . 'Rest/config.php',
);