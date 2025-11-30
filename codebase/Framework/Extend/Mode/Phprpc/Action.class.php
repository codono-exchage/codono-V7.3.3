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
 * ThinkPHP AMFmodeActionController base class
 */
abstract class Action
{

    /**
     * Magic Methods Havedoes not existofoperatingoftimecarried out
     * @access public
     * @param string $method methodname
     * @param array $parms parameter
     * @return mixed
     */
    public function __call($method, $parms)
    {
        // in casedefinitionThe_emptyoperating thentransfer
        if (method_exists($this, '_empty')) {
            $this->_empty($method, $parms);
        }
    }

}