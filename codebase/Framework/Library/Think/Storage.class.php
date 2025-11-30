<?php
// +----------------------------------------------------------------------
// | TOPThink [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013 http://topthink.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: support@codono.com
// +----------------------------------------------------------------------
namespace Think;
// Distributed File Storage
class Storage
{

    /**
     * Operation Handle
     * @var string
     * @access protected
     */
    static protected $handler;

    /**
     * connectiondistributedfilesystem
     * @access public
     * @param string $type file type
     * @param array $options ConfigurationArray
     * @return void
     */
    static public function connect($type = 'File', $options = array())
    {
        $class = 'Think\\Storage\\Driver\\' . ucwords($type);
        self::$handler = new $class($options);
    }

    static public function __callstatic($method, $args)
    {
        //transferCache Drivesofmethod
        if (method_exists(self::$handler, $method)) {
            return call_user_func_array(array(self::$handler, $method), $args);
        }
    }
}
