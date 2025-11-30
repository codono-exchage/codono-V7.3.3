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
 * ArrayListImplementation class
 * @category   Think
 * @package  Think
 * @subpackage  Util
 * @author    liu21st <liu21st@gmail.com>
 */
class ArrayList implements IteratorAggregate
{

    /**
     * Set element
     * @var array
     * @access protected
     */
    protected $_elements = array();

    /**
     * Architecturefunction
     * @access public
     * @param string $elements Initialize the array elements
     */
    public function __construct($elements = array())
    {
        if (!empty($elements)) {
            $this->_elements = $elements;
        }
    }

    /**
     * ToobtainIteration factor,bygetIteratorWay to achieve
     * @access public
     * @return ArrayObject
     */
    public function getIterator()
    {
        return new ArrayObject($this->_elements);
    }

    /**
     * Add elements
     * @access public
     * @param mixed $element The element to add
     * @return boolen
     */
    public function add($element)
    {
        return (array_push($this->_elements, $element)) ? true : false;
    }

    //
    public function unshift($element)
    {
        return (array_unshift($this->_elements, $element)) ? true : false;
    }

    //
    public function pop()
    {
        return array_pop($this->_elements);
    }

    /**
     * Add elements list
     * @access public
     * @param ArrayList $list List of elements
     * @return boolen
     */
    public function addAll($list)
    {
        $before = $this->size();
        foreach ($list as $element) {
            $this->add($element);
        }
        $after = $this->size();
        return ($before < $after);
    }

    /**
     * Clear all the elements
     * @access public
     */
    public function clear()
    {
        $this->_elements = array();
    }

    /**
     * whetherIt contains aelement
     * @access public
     * @param mixed $element Finding Elements
     * @return string
     */
    public function contains($element)
    {
        return (array_search($element, $this->_elements) !== false);
    }

    /**
     * Made elements according to the index
     * @access public
     * @param integer $index index
     * @return mixed
     */
    public function get($index)
    {
        return $this->_elements[$index];
    }

    /**
     * Seekmatchelement,andreturnThe firstOneelementlocation
     * note That may exist0The index position So use===FalseTo determine the lookup fails
     * @access public
     * @param mixed $element Finding Elements
     * @return integer
     */
    public function indexOf($element)
    {
        return array_search($element, $this->_elements);
    }

    /**
     * Determine whether the element is empty
     * @access public
     * @return boolen
     */
    public function isEmpty()
    {
        return empty($this->_elements);
    }

    /**
     * At lastOnematchofelementposition
     * @access public
     * @param mixed $element Finding Elements
     * @return integer
     */
    public function lastIndexOf($element)
    {
        for ($i = (count($this->_elements) - 1); $i > 0; $i--) {
            if ($element == $this->get($i)) {
                return $i;
            }
        }
    }

    public function toJson()
    {
        return json_encode($this->_elements);
    }

    /**
     * Removes the element according to the index
     * Returns the element to be removed
     * @access public
     * @param integer $index index
     * @return mixed
     */
    public function remove($index)
    {
        $element = $this->get($index);
        if (!is_null($element)) {
            array_splice($this->_elements, $index, 1);
        }
        return $element;
    }

    /**
     * Out offor surerangeArrayList
     * @access public
     * @param integer $offset Began to remove location
     * @param integer $length Length removed
     */
    public function removeRange($offset, $length)
    {
        array_splice($this->_elements, $offset, $length);
    }

    /**
     * Removal of duplicate values
     * @access public
     */
    public function unique()
    {
        $this->_elements = array_unique($this->_elements);
    }

    /**
     * Remove a range ofArrayList
     * @access public
     * @param integer $offset Starting position
     * @param integer $length length
     */
    public function range($offset, $length = null)
    {
        return array_slice($this->_elements, $offset, $length);
    }

    /**
     * Set list elements
     * Return value before modification
     * @access public
     * @param integer $index index
     * @param mixed $element element
     * @return mixed
     */
    public function set($index, $element)
    {
        $previous = $this->get($index);
        $this->_elements[$index] = $element;
        return $previous;
    }

    /**
     * Get a list length
     * @access public
     * @return integer
     */
    public function size()
    {
        return count($this->_elements);
    }

    /**
     * Into an array
     * @access public
     * @return array
     */
    public function toArray()
    {
        return $this->_elements;
    }

    // ListSequence
    public function ksort()
    {
        ksort($this->_elements);
    }

    // ListSequence
    public function asort()
    {
        asort($this->_elements);
    }

    // In reverse order
    public function rsort()
    {
        rsort($this->_elements);
    }

    // Natural order
    public function natsort()
    {
        natsort($this->_elements);
    }

}