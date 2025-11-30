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
 * Database wayCache Drives
 *    CREATE TABLE think_cache (
 *      cachekey varchar(255) NOT NULL,
 *      expire int(11) NOT NULL,
 *      data blob,
 *      datacrc int(32),
 *      UNIQUE KEY `cachekey` (`cachekey`)
 *    );
 * @category   Extend
 * @package  Extend
 * @subpackage  Driver.Cache
 * @author    liu21st <liu21st@gmail.com>
 */
class CacheDb extends Cache
{

    /**
     * Architecturefunction
     * @param array $options Cache parameters
     * @access public
     */
    public function __construct($options = array())
    {
        if (empty($options)) {
            $options = array(
                'table' => C('DATA_CACHE_TABLE'),
            );
        }
        $this->options = $options;
        $this->options['prefix'] = isset($options['prefix']) ? $options['prefix'] : C('DATA_CACHE_PREFIX');
        $this->options['length'] = isset($options['length']) ? $options['length'] : 0;
        $this->options['expire'] = isset($options['expire']) ? $options['expire'] : C('DATA_CACHE_TIME');
        import('Db');
        $this->handler = DB::getInstance();
    }

    /**
     * Read Cache
     * @access public
     * @param string $name Cache variable name
     * @return mixed
     */
    public function get($name)
    {
        $name = $this->options['prefix'] . addslashes($name);
        N('cache_read', 1);
        $result = $this->handler->query('SELECT `data`,`datacrc` FROM `' . $this->options['table'] . '` WHERE `cachekey`=\'' . $name . '\' AND (`expire` =0 OR `expire`>' . time() . ') LIMIT 0,1');
        if (false !== $result) {
            $result = $result[0];
            if (C('DATA_CACHE_CHECK')) {//Opendatacheck
                if ($result['datacrc'] != md5($result['data'])) {//checkerror
                    return false;
                }
            }
            $content = $result['data'];
            if (C('DATA_CACHE_COMPRESS') && function_exists('gzcompress')) {
                //Enabledatacompression
                $content = gzuncompress($content);
            }
            $content = unserialize($content);
            return $content;
        } else {
            return false;
        }
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
        $data = serialize($value);
        $name = $this->options['prefix'] . addslashes($name);
        N('cache_write', 1);
        if (C('DATA_CACHE_COMPRESS') && function_exists('gzcompress')) {
            //datacompression
            $data = gzcompress($data, 3);
        }
        if (C('DATA_CACHE_CHECK')) {//Opendatacheck
            $crc = md5($data);
        } else {
            $crc = '';
        }
        if (is_null($expire)) {
            $expire = $this->options['expire'];
        }
        $expire = ($expire == 0) ? 0 : (time() + $expire);//Cache Expirationfor0Permanent representationCache
        $result = $this->handler->query('select `cachekey` from `' . $this->options['table'] . '` where `cachekey`=\'' . $name . '\' limit 0,1');
        if (!empty($result)) {
            //update record
            $result = $this->handler->execute('UPDATE ' . $this->options['table'] . ' SET data=\'' . $data . '\' ,datacrc=\'' . $crc . '\',expire=' . $expire . ' WHERE `cachekey`=\'' . $name . '\'');
        } else {
            //Newrecording
            $result = $this->handler->execute('INSERT INTO ' . $this->options['table'] . ' (`cachekey`,`data`,`datacrc`,`expire`) VALUES (\'' . $name . '\',\'' . $data . '\',\'' . $crc . '\',' . $expire . ')');
        }
        if ($result) {
            if ($this->options['length'] > 0) {
                // recordingCachequeue
                $this->queue($name);
            }
            return true;
        } else {
            return false;
        }
    }

    /**
     * Delete Cache
     * @access public
     * @param string $name Cache variable name
     * @return boolen
     */
    public function rm($name)
    {
        $name = $this->options['prefix'] . addslashes($name);
        return $this->handler->execute('DELETE FROM `' . $this->options['table'] . '` WHERE `cachekey`=\'' . $name . '\'');
    }

    /**
     * clear cache
     * @access public
     * @return boolen
     */
    public function clear()
    {
        return $this->handler->execute('TRUNCATE TABLE `' . $this->options['table'] . '`');
    }

}