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

// AMFSchema definition file
return array(
    'core' => array(
        THINK_PATH . 'Common/functions.php',   // System library
        CORE_PATH . 'Core/Log.class.php',// Journaldeal with
        MODE_PATH . 'Amf/App.class.php', // Application class
        MODE_PATH . 'Amf/Action.class.php',// Controller class
    ),

    // projectAlias definitionfile [stand byArraydirectdefinitionorfilenamedefinition]
    'alias' => array(
        'Model' => MODE_PATH . 'Amf/Model.class.php',
        'Db' => MODE_PATH . 'Amf/Db.class.php',
    ),
    // systembehaviordefinitionfile [have to stand byArraydirectdefinitionorfilenamedefinition ]
    'extends' => array(),

    // projectapplicationbehaviordefinitionfile [stand byArraydirectdefinitionorfilenamedefinition]
    'tags' => array(),
);