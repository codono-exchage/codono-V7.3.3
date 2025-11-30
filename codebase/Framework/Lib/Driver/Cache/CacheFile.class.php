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
 * File Type cache class
 * @category   Think
 * @package  Think
 * @subpackage  Driver.Cache
 * @author    liu21st <liu21st@gmail.com>
 */
class CacheFile extends Cache
{

    /**
     * Architecturefunction
     * @access public
     */
    public function __construct($options = array())
    {
        if (!empty($options)) {
            $this->options = $options;
        }
        $this->options['temp'] = !empty($options['temp']) ? $options['temp'] : C('DATA_CACHE_PATH');
        $this->options['prefix'] = isset($options['prefix']) ? $options['prefix'] : C('DATA_CACHE_PREFIX');
        $this->options['expire'] = isset($options['expire']) ? $options['expire'] : C('DATA_CACHE_TIME');
        $this->options['length'] = isset($options['length']) ? $options['length'] : 0;
        if (substr($this->options['temp'], -1) != '/') $this->options['temp'] .= '/';
        $this->init();
    }

    /**
     * Check the initialization
     * @access private
     * @return boolen
     */
    private function init()
    {
        // Create a cache directory project
        if (!is_dir($this->options['temp'])) {
            mkdir($this->options['temp']);
        }
    }

    /**
     * Obtainvariableofstoragefilename
     * @access private
     * @param string $name Cache variable name
     * @return string
     */
    private function filename($name)
    {
        $name = md5($name);
        if (C('DATA_CACHE_SUBDIR')) {
            // usechildtable of Contents
            $dir = '';
            for ($i = 0; $i < C('DATA_PATH_LEVEL'); $i++) {
                $dir .= $name[$i] . '/';

            }
            if (!is_dir($this->options['temp'] . $dir)) {
                mkdir($this->options['temp'] . $dir, 0755, true);
            }
            $filename = $dir . $this->options['prefix'] . $name . '.php';
        } else {
            $filename = $this->options['prefix'] . $name . '.php';
        }
        return $this->options['temp'] . $filename;
    }

    /**
     * Read Cache
     * @access public
     * @param string $name Cache variable name
     * @return mixed
     */
    public function get($name)
    {
        $filename = $this->filename($name);
        if (!is_file($filename)) {
            return false;
        }
        N('cache_read', 1);
        $content = file_get_contents($filename);
        if (false !== $content) {
            $expire = (int)substr($content, 8, 12);
            if ($expire != 0 && time() > filemtime($filename) + $expire) {
                //CacheExpiredDelete Cachefile
                unlink($filename);
                return false;
            }
            if (C('DATA_CACHE_CHECK')) {//Opendatacheck
                $check = substr($content, 20, 32);
                $content = substr($content, 52, -3);
                if ($check != md5($content)) {//checkerror
                    return false;
                }
            } else {
                $content = substr($content, 20, -3);
            }
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
     * @param int $expire Effective time 0Permanent
     * @return boolen
     */
    public function set($name, $value, $expire = null)
    {
        N('cache_write', 1);
        if (is_null($expire)) {
            $expire = $this->options['expire'];
        }
        $filename = $this->filename($name);
        $data = serialize($value);
        if (C('DATA_CACHE_COMPRESS') && function_exists('gzcompress')) {
            //datacompression
            $data = gzcompress($data, 3);
        }
        if (C('DATA_CACHE_CHECK')) {//Opendatacheck
            $check = md5($data);
        } else {
            $check = '';
        }
        $data = "<?php\n//" . sprintf('%012d', $expire) . $check . $data . "\n?>";
        $result = file_put_contents($filename, $data);
        if ($result) {
            if ($this->options['length'] > 0) {
                // recordingCachequeue
                $this->queue($name);
            }
            clearstatcache();
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
        return unlink($this->filename($name));
    }

    /**
     * clear cache
     * @access public
     * @param string $name Cache variable name
     * @return boolen
     */
    public function clear()
    {
        $path = $this->options['temp'];
        $files = scandir($path);
        if ($files) {
            foreach ($files as $file) {
                if ($file != '.' && $file != '..' && is_dir($path . $file)) {
                    array_map('unlink', glob($path . $file . '/*.*'));
                } elseif (is_file($path . $file)) {
                    unlink($path . $file);
                }
            }
            return true;
        }
        return false;
    }
}