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

/**
 * ThinkPHP Simple modeActionController base class
 */
abstract class Action
{

    /**
     * Architecturefunction
     * @access public
     */
    public function __construct()
    {
        //Controllerinitialization
        if (method_exists($this, '_initialize')) {
            $this->_initialize();
        }
    }

    /**
     * Magic Methods Havedoes not existofoperatingoftimecarried out
     * @access public
     * @param string $method methodname
     * @param array $parms parameter
     * @return mixed
     */
    public function __call($method, $parms)
    {
        if (strtolower($method) == strtolower(ACTION_NAME)) {
            // in casedefinitionThe_emptyoperating thentransfer
            if (method_exists($this, '_empty')) {
                $this->_empty($method, $parms);
            } else {
                // Throw an exception
                throw_exception(L('_ERROR_ACTION_') . ACTION_NAME);
            }
        } else {
            throw_exception(__CLASS__ . ':' . $method . L('_METHOD_NOT_EXIST_'));
        }
    }

}