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
 * Behavior extension URLResource type detection
 */
class CheckUrlExtBehavior extends Behavior
{

    /**
     * DetectURLAddress resource extension
     * @access public
     * @return void
     */
    public function run(&$params)
    {
        // The type of access to resources
        if (!empty($_SERVER['PATH_INFO'])) {
            $part = pathinfo($_SERVER['PATH_INFO']);
            if (isset($part['extension'])) { // judgmentExtension
                define('__EXT__', strtolower($part['extension']));
                $_SERVER['PATH_INFO'] = preg_replace('/.' . __EXT__ . '$/i', '', $_SERVER['PATH_INFO']);
            }
        }
    }

}