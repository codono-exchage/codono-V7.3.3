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

// Command line mode definition file
return array(
    'core' => array(
        MODE_PATH . 'Cli/functions.php',   // Command line library system
        MODE_PATH . 'Cli/Log.class.php',
        MODE_PATH . 'Cli/App.class.php',
        MODE_PATH . 'Cli/Action.class.php',
    ),

    // projectAlias definitionfile [stand byArraydirectdefinitionorfilenamedefinition]
    'alias' => array(
        'Model' => MODE_PATH . 'Cli/Model.class.php',
        'Db' => MODE_PATH . 'Cli/Db.class.php',
        'Cache' => CORE_PATH . 'Core/Cache.class.php',
        'Debug' => CORE_PATH . 'Util/Debug.class.php',
    ),

    // systembehaviordefinitionfile [have to stand byArraydirectdefinitionorfilenamedefinition ]
    'extends' => array(),

    // projectapplicationbehaviordefinitionfile [stand byArraydirectdefinitionorfilenamedefinition]
    'tags' => array(),

);