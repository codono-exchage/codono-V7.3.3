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

/**
 * ThinkPHP AMFMode application classes
 */
class App
{

    /**
     * Application Initialization
     * @access public
     * @return void
     */
    static public function run()
    {

        //ImportingClass Library
        Vendor('phpRPC.phprpc_server');
        //Instantiationphprpc
        $server = new PHPRPC_Server();
        $actions = explode(',', C('APP_PHPRPC_ACTIONS'));
        foreach ($actions as $action) {
            //$server -> setClass($action.'Action');
            $temp = $action . 'Action';
            $methods = get_class_methods($temp);
            $server->add($methods, new $temp);
        }
        if (APP_DEBUG) {
            $server->setDebugMode(true);
        }
        $server->setEnableGZIP(true);
        $server->start();
        //C('PHPRPC_COMMENT',$server->comment());
        echo $server->comment();
        // StorageJournalrecording
        if (C('LOG_RECORD')) Log::save();
        return;
    }

}

;