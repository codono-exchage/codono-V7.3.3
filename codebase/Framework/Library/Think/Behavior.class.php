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
namespace Think;
/**
 * ThinkPHP BehaviorFoundation Classes
 */
abstract class Behavior
{
    /**
     * Execution behavior runthe way isBehaviorThe only interface
     * @access public
     * @param mixed $params Behavioral parameters
     * @return void
     */
    abstract public function run(&$params);

}