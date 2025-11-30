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
 * ApcCache Drives
 * @category   Extend
 * @package  Extend
 * @subpackage  Driver.Cache
 * @author    liu21st <liu21st@gmail.com>
 */
class CacheApc extends Cache
{

    /**
     * Architecturefunction
     * @param array $options Cache parameters
     * @access public
     */
    public function __construct($options = array())
    {
        if (!function_exists('apc_cache_info')) {
            throw_exception(L('_NOT_SUPPERT_') . ':Apc');
        }
        $this->options['prefix'] = isset($options['prefix']) ? $options['prefix'] : C('DATA_CACHE_PREFIX');
        $this->options['length'] = isset($options['length']) ? $options['length'] : 0;
        $this->options['expire'] = isset($options['expire']) ? $options['expire'] : C('DATA_CACHE_TIME');
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
        return apc_fetch($this->options['prefix'] . $name);
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
        if ($result = apc_store($name, $value, $expire)) {
            if ($this->options['length'] > 0) {
                // recordingCachequeue
                $this->queue($name);
            }
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
        return apc_delete($this->options['prefix'] . $name);
    }

    /**
     * clear cache
     * @access public
     * @return boolen
     */
    public function clear()
    {
        return apc_clear_cache();
    }

}