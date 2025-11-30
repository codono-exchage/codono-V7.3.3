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

// LiteSchema definition file
return array(
    'core' => array(
        THINK_PATH . 'Common/functions.php',   // System library
        CORE_PATH . 'Core/Log.class.php',// Journaldeal with
        MODE_PATH . 'Lite/App.class.php', // Application class
        MODE_PATH . 'Lite/Action.class.php',// Controller class
        MODE_PATH . 'Lite/Dispatcher.class.php',
    ),

    // projectAlias definitionfile [stand byArraydirectdefinitionorfilenamedefinition]
    'alias' => array(
        'Model' => MODE_PATH . 'Lite/Model.class.php',
        'Db' => MODE_PATH . 'Lite/Db.class.php',
        'ThinkTemplate' => CORE_PATH . 'Template/ThinkTemplate.class.php',
        'TagLib' => CORE_PATH . 'Template/TagLib.class.php',
        'Cache' => CORE_PATH . 'Core/Cache.class.php',
        'Debug' => CORE_PATH . 'Util/Debug.class.php',
        'Session' => CORE_PATH . 'Util/Session.class.php',
        'TagLibCx' => CORE_PATH . 'Driver/TagLib/TagLibCx.class.php',
    ),

    // systembehaviordefinitionfile [have to stand byArraydirectdefinitionorfilenamedefinition ]
    'extends' => MODE_PATH . 'Lite/tags.php',

);