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
        Vendor('Zend.Amf.Server');
        //InstantiationAMF
        $server = new Zend_Amf_Server();
        $actions = explode(',', C('APP_AMF_ACTIONS'));
        foreach ($actions as $action)
            $server->setClass($action . 'Action');
        echo $server->handle();

        // StorageJournalrecording
        if (C('LOG_RECORD')) Log::save();
        return;
    }

}

;