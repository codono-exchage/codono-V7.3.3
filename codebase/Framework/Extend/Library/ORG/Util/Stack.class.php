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
import("ORG.Util.ArrayList");

/**
 * StackImplementation class
 * @category   ORG
 * @package  ORG
 * @subpackage  Util
 * @author    liu21st <liu21st@gmail.com>
 */
class Stack extends ArrayList
{

    /**
     * Architecturefunction
     * @access public
     * @param array $values Initialize the array elements
     */
    public function __construct($values = array())
    {
        parent::__construct($values);
    }

    /**
     * The StackofinternalPointer points to the firstOneunit
     * @access public
     * @return mixed
     */
    public function peek()
    {
        return reset($this->toArray());
    }

    /**
     * The push element
     * @access public
     * @param mixed $value
     * @return mixed
     */
    public function push($value)
    {
        $this->add($value);
        return $value;
    }

}
