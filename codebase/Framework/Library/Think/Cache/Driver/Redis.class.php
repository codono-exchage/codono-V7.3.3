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
namespace Think\Cache\Driver;


use Think\Cache;
use Exception;

defined('THINK_PATH') or exit();

/**
 * RedisCache Driver
 * Installation Requirements php redis Extended:https://github.com/phpredis/phpredis
 */
class Redis extends Cache
{
    /**
     * Architecture function
     * @param array $options Cache parameters
     * @access public
     */
    public function __construct($options = array())
    {
        
        if (!extension_loaded('redis')) {
            systemexception('<b>[10020RD]</b>:We are upgrading the system! , Please refresh in sometime','Error:extension_loaded redis false',false);
        }
                
        
        $options = array_merge(array(
            'host' => C('REDIS_HOST') ?: '127.0.0.1',
            'port' => C('REDIS_PORT') ?: 6379,
            'timeout' => C('DATA_CACHE_TIMEOUT') ?: false,
            'persistent' => false,
        ), $options);
		if(C('REDIS_PASSWORD') && ( C('REDIS_PASSWORD') !='' || C('REDIS_PASSWORD') !=null)){
			$options['password']=C('REDIS_PASSWORD') ? C('REDIS_PASSWORD') :'';
		}

        $this->options = $options;
        $this->options['expire'] = $options['expire'] ?? C('DATA_CACHE_TIME');
        $this->options['prefix'] = $options['prefix'] ?? C('DATA_CACHE_PREFIX');
        $this->options['length'] = $options['length'] ?? 0;
        $options['timeout']=2.5;
        $func = $options['persistent'] ? 'pconnect' : 'connect';
        $this->handler = new \Redis;
        try {

            $options['timeout'] === false ?
                $this->handler->$func($options['host'], $options['port']) :
                $this->handler->$func($options['host'], $options['port'], (int)$options['timeout']);

            if ('' != $options['password']) {
                $info = $this->handler->auth($options['password']);

            }
        }catch (Exception $exception){
            //echo $exception->getMessage();
            systemexception('<b>[10066RD]</b>:We are upgrading the system! , Please refresh in sometime','Error:Redis connection failed',false);
        }
        
    }

    /**
     * Read Cache
     * @access public
     * @param string $name Cache variable name
     * @return mixed
     */
    public function get($name)
    {
        N('cache_read', 1);
        $value = $this->handler->get($this->options['prefix'] . $name);
        $jsonData = json_decode($value, true);
        return ($jsonData === NULL) ? $value : $jsonData;    //Detecting whetherforJSONdata true returnJSONResolveArray, falsereturnsourcedata
    }

    /**
     * Write Cache
     * @access public
     * @param string $name Cache variable name
     * @param mixed $value Storing data
     * @param integer $expire Active Time (sec)
     * @return boolean
     */
    public function set($name, $value, $expire = null)
    {
        N('cache_write', 1);
        if (is_null($expire)) {
            $expire = $this->options['expire'];
        }
        $name = $this->options['prefix'] . $name;
        $value = (is_object($value) || is_array($value)) ? json_encode($value) : $value;
        if (is_int($expire) && $expire) {
            $result = $this->handler->setex($name, $expire, $value);
        } else {
            $result = $this->handler->set($name, $value);
        }
        if ($result && $this->options['length'] > 0) {
            // recordingCachequeue
            $this->queue($name);
        }
        return $result;
    }

    /**
     * Delete Cache
     * @access public
     * @param string $name Cache variable name
     * @return boolean
     */
    public function rm($name)
    {
        return $this->handler->del($this->options['prefix'] . $name);
    }

    /**
     * clear cache
     * @access public
     * @return boolean
     */
    public function clear()
    {
        return $this->handler->flushDB();
    }

}