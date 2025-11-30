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
 * ThinkPHP HproseController class
 */
class HproseController
{

    protected $allowMethodList = '';
    protected $crossDomain = false;
    protected $P3P = false;
    protected $get = true;
    protected $debug = false;

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
        Vendor('Hprose.HproseHttpServer');
        //InstantiationHproseHttpServer
        $server = new \HproseHttpServer();
        if ($this->allowMethodList) {
            $methods = $this->allowMethodList;
        } else {
            $methods = get_class_methods($this);
            $methods = array_diff($methods, array('__construct', '__call', '_initialize'));
        }
        $server->addMethods($methods, $this);
        if (APP_DEBUG || $this->debug) {
            $server->setDebugEnabled(true);
        }
        // HproseSet up
        $server->setCrossDomainEnabled($this->crossDomain);
        $server->setP3PEnabled($this->P3P);
        $server->setGetEnabled($this->get);
        // start upserver
        $server->start();
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
