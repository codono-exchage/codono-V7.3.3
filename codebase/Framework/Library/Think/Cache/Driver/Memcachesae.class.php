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
namespace Think\Cache\Driver;

use Think\Cache;

defined('THINK_PATH') or exit();

/**
 * MemcacheCache Drives
 * @category   Extend
 * @package  Extend
 * @subpackage  Driver.Cache
 * @author    liu21st <liu21st@gmail.com>
 */
class Memcachesae extends Cache
{

    /**
     * Architecturefunction
     * @param array $options Cache parameters
     * @access public
     */
    function __construct($options = array())
    {
        $options = array_merge(array(
            'host' => C('MEMCACHE_HOST') ?: '127.0.0.1',
            'port' => C('MEMCACHE_PORT') ?: 11211,
            'timeout' => C('DATA_CACHE_TIMEOUT') ?: false,
            'persistent' => false,
        ), $options);

        $this->options = $options;
        $this->options['expire'] = isset($options['expire']) ? $options['expire'] : C('DATA_CACHE_TIME');
        $this->options['prefix'] = isset($options['prefix']) ? $options['prefix'] : C('DATA_CACHE_PREFIX');
        $this->options['length'] = isset($options['length']) ? $options['length'] : 0;
        $this->handler = memcache_init();//[sae] Instanced
        //[sae] Under nolink
        $this->connected = true;
    }

    /**
     * Is connected
     * @access private
     * @return boolean
     */
    private function isConnected()
    {
        return $this->connected;
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
        return $this->handler->get($_SERVER['HTTP_APPVERSION'] . '/' . $this->options['prefix'] . $name);
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
        if ($this->handler->set($_SERVER['HTTP_APPVERSION'] . '/' . $name, $value, 0, $expire)) {
            if ($this->options['length'] > 0) {
                // recordingCachequeue
                $this->queue($name);
            }
            return true;
        }
        return false;
    }

    /**
     * Delete Cache
     * @access public
     * @param string $name Cache variable name
     * @return boolean
     */
    public function rm($name, $ttl = false)
    {
        $name = $_SERVER['HTTP_APPVERSION'] . '/' . $this->options['prefix'] . $name;
        return $ttl === false ?
            $this->handler->delete($name) :
            $this->handler->delete($name, $ttl);
    }

    /**
     * clear cache
     * @access public
     * @return boolean
     */
    public function clear()
    {
        return $this->handler->flush();
    }

    /**
     * Sequence Caching
     * @access protected
     * @param string $key queue Name
     * @return mixed
     */
    //[sae] Lower rewritequequeSequence Cachingmethod
    protected function queue($key)
    {
        $queue_name = isset($this->options['queue_name']) ? $this->options['queue_name'] : 'think_queue';
        $value = F($queue_name);
        if (!$value) {
            $value = array();
        }
        // Enter row
        if (false === array_search($key, $value)) array_push($value, $key);
        if (count($value) > $this->options['length']) {
            // The column
            $key = array_shift($value);
            // Delete Cache
            $this->rm($key);
            if (APP_DEBUG) {
                //debugging mode recordingThe number of teams
                $counter = Think::instance('SaeCounter');
                if ($counter->exists($queue_name . '_out_times'))
                    $counter->incr($queue_name . '_out_times');
                else
                    $counter->create($queue_name . '_out_times', 1);
            }
        }
        return F($queue_name, $value);
    }

}
