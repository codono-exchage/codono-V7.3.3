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
namespace Think\Controller;
/**
 * ThinkPHP JsonRPCController class
 */
class JsonRpcController
{

    /**
     * Architecturefunction
     * @access public
     */
    public function __construct()
    {
        //Controllerinitialization
        if (method_exists($this, '_initialize'))
            $this->_initialize();
        //ImportingClass Library
        Vendor('jsonRPC.jsonRPCServer');
        // start upserver
        \jsonRPCServer::handle($this);
    }

    /**
     * Magic Methods Havedoes not existofoperatingoftimecarried out
     * @access public
     * @param string $method methodname
     * @param array $args parameter
     * @return mixed
     */
    public function __call($method, $args)
    {
    }
}
