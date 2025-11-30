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

// Simple modecoredefinitionfileList
return array(

    'core' => array(
        THINK_PATH . 'Common/functions.php',   // System library
        CORE_PATH . 'Core/Log.class.php',// Journaldeal with
        MODE_PATH . 'Thin/App.class.php', // Application class
        MODE_PATH . 'Thin/Action.class.php',// Controller class
    ),

    // projectAlias definitionfile [stand byArraydirectdefinitionorfilenamedefinition]
    'alias' => array(
        'Model' => MODE_PATH . 'Thin/Model.class.php',
        'Db' => MODE_PATH . 'Thin/Db.class.php',
    ),

    // systembehaviordefinitionfile [have to stand byArraydirectdefinitionorfilenamedefinition ]
    'extends' => array(),

    // projectapplicationbehaviordefinitionfile [stand byArraydirectdefinitionorfilenamedefinition]
    'tags' => array(),

);