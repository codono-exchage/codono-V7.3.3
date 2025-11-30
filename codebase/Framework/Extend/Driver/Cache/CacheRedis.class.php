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

defined('THINK_PATH') or exit();

/**
 * RedisCache Drives
 * Installation RequirementsphpredisExtended:https://github.com/nicolasff/phpredis
 * @category   Extend
 * @package  Extend
 * @subpackage  Driver.Cache
 * @author    bonds of this world <130775@qq.com>
 */
class CacheRedis extends Cache
{
    /**
     * Architecturefunction
     * @param array $options Cache parameters
     * @access public
     */
    public function __construct($options = array())
    {
        if (!extension_loaded('redis')) {
            throw_exception(L('_NOT_SUPPERT_') . ':redis');
        }
        if (empty($options)) {
            $options = array(
                'host' => C('REDIS_HOST') ? C('REDIS_HOST') : '127.0.0.1',
                'port' => C('REDIS_PORT') ? C('REDIS_PORT') : 6379,
                'timeout' => C('DATA_CACHE_TIMEOUT') ? C('DATA_CACHE_TIMEOUT') : false,
                'persistent' => false,
            );
        }
        $this->options = $options;
        $this->options['expire'] = isset($options['expire']) ? $options['expire'] : C('DATA_CACHE_TIME');
        $this->options['prefix'] = isset($options['prefix']) ? $options['prefix'] : C('DATA_CACHE_PREFIX');
        $this->options['length'] = isset($options['length']) ? $options['length'] : 0;
        $func = $options['persistent'] ? 'pconnect' : 'connect';
        $this->handler = new Redis;
        $options['timeout'] === false ?
            $this->handler->$func($options['host'], $options['port']) :
            $this->handler->$func($options['host'], $options['port'], $options['timeout']);
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
     * @return boolen
     */
    public function set($name, $value, $expire = null)
    {
        N('cache_write', 1);
        if (is_null($expire)) {
            $expire = $this->options['expire'];
        }
        $name = $this->options['prefix'] . $name;
        //CorrectArray/ObjectsdataEnterRowCachedeal with,GuaranteedataIntegrity
        $value = (is_object($value) || is_array($value)) ? json_encode($value) : $value;
        if (is_int($expire)) {
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
     * @return boolen
     */
    public function rm($name)
    {
        return $this->handler->delete($this->options['prefix'] . $name);
    }

    public function zAdd($key,$timestamp,$value){
        return $this->handler->zAdd($key,$timestamp,$value);
    }

    /**
     * clear cache
     * @access public
     * @return boolen
     */
    public function clear()
    {
        return $this->handler->flushDB();
    }

}
