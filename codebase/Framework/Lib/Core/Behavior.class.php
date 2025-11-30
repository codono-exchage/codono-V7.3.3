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
 * ThinkPHP BehaviorFoundation Classes
 * @category   Think
 * @package  Think
 * @subpackage  Core
 * @author liu21st <liu21st@gmail.com>
 */
abstract class Behavior
{

    // Behavioral parameters withConfiguration parametersSet up相同
    protected $options = array();

    /**
     * Architecturefunction
     * @access public
     */
    public function __construct()
    {
        if (!empty($this->options)) {
            foreach ($this->options as $name => $val) {
                if (NULL !== C($name)) { // Parameters have been set Overwrite behavior parameters
                    $this->options[$name] = C($name);
                } else { // Parameter is not set The default value is passed to the configuration
                    C($name, $val);
                }
            }
            array_change_key_case($this->options);
        }
    }

    // Acquisition activity parameters
    public function __get($name)
    {
        return $this->options[strtolower($name)];
    }

    /**
     * Execution behavior runthe way isBehaviorThe only interface
     * @access public
     * @param mixed $params Behavioral parameters
     * @return void
     */
    abstract public function run(&$params);

}