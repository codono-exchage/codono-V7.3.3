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
 * Think commandRowmodepublicfunctionStorehouse
 * @category   Think
 * @package  Common
 * @author   liu21st <liu21st@gmail.com>
 */

// Error output
function halt($error)
{
    exit($error);
}

// Custom exception handling
function throw_exception($msg, $type = 'ThinkException', $code = 0)
{
    halt($msg);
}

// BrowseFRIENDLYofvariableExport
function dump($var, $echo = true, $label = null, $strict = true)
{
    $label = ($label === null) ? '' : rtrim($label) . ' ';
    if (!$strict) {
        if (ini_get('html_errors')) {
            $output = print_r($var, true);
            $output = "<pre>" . $label . htmlspecialchars($output, ENT_QUOTES) . "</pre>";
        } else {
            $output = $label . print_r($var, true);
        }
    } else {
        ob_start();
        var_dump($var);
        $output = ob_get_clean();
        if (!extension_loaded('xdebug')) {
            $output = preg_replace("/\]\=\>\n(\s+)/m", "] => ", $output);
            $output = '<pre>' . $label . htmlspecialchars($output, ENT_QUOTES) . '</pre>';
        }
    }
    if ($echo) {
        echo($output);
        return null;
    } else
        return $output;
}

// Interval start debugging
function debug_start($label = '')
{
    $GLOBALS[$label]['_beginTime'] = microtime(TRUE);
    if (MEMORY_LIMIT_ON)
        $GLOBALS[$label]['_beginMem'] = memory_get_usage();
}

// IntervaldebuggingEnd,displayDesignationTo markcurrentpositionofdebugging
function debug_end($label = '')
{
    $GLOBALS[$label]['_endTime'] = microtime(TRUE);
    echo '<div style="text-align:center;width:100%">Process ' . $label . ': Times ' . number_format($GLOBALS[$label]['_endTime'] - $GLOBALS[$label]['_beginTime'], 6) . 's ';
    if (MEMORY_LIMIT_ON) {
        $GLOBALS[$label]['_endMem'] = memory_get_usage();
        echo ' Memories ' . number_format(($GLOBALS[$label]['_endMem'] - $GLOBALS[$label]['_beginMem']) / 1024) . ' k';
    }
    echo '</div>';
}

// Global cache settings and read
function S($name, $value = '', $expire = '', $type = '', $options = null)
{
	
    static $_cache = array();
    alias_import('Cache');
    //Cache object instance to obtain
    $cache = Cache::getInstance($type, $options);
    if ('' !== $value) {
        if (is_null($value)) {
            // Delete Cache
            $result = $cache->rm($name);
            if ($result)
                unset($_cache[$type . '_' . $name]);
            return $result;
        } else {
            // Cachedata
            $cache->set($name, $value, $expire);
            $_cache[$type . '_' . $name] = $value;
        }
        return;
    }
    if (isset($_cache[$type . '_' . $name]))
        return $_cache[$type . '_' . $name];
    // ObtainCachedata
    $value = $cache->get($name);
    $_cache[$type . '_' . $name] = $value;
    return $value;
}

// fastfiledataReadwithStorage For simple data types Strings, arrays
function F($name, $value = '', $path = DATA_PATH)
{
    static $_cache = array();
    $filename = $path . $name . '.php';
    if ('' !== $value) {
        if (is_null($value)) {
            // Delete Cache
            return unlink($filename);
        } else {
            // Cachedata
            $dir = dirname($filename);
            // table of Contentsdoes not existCreate
            if (!is_dir($dir))
                mkdir($dir);
            return file_put_contents($filename, strip_whitespace("<?php\nreturn " . var_export($value, true) . ";\n?>"));
        }
    }
    if (isset($_cache[$name]))
        return $_cache[$name];
    // ObtainCachedata
    if (is_file($filename)) {
        $value = include $filename;
        $_cache[$name] = $value;
    } else {
        $value = false;
    }
    return $value;
}

// Made object instance stand bytransferCategoryStatic method
function get_instance_of($name, $method = '', $args = array())
{
    static $_instance = array();
    $identify = empty($args) ? $name . $method : $name . $method . to_guid_string($args);
    if (!isset($_instance[$identify])) {
        if (class_exists($name)) {
            $o = new $name();
            if (method_exists($o, $method)) {
                if (!empty($args)) {
                    $_instance[$identify] = call_user_func_array(array(&$o, $method), $args);
                } else {
                    $_instance[$identify] = $o->$method();
                }
            } else
                $_instance[$identify] = $o;
        } else
            halt(L('_CLASS_NOT_EXIST_') . ':' . $name);
    }
    return $_instance[$identify];
}

// according toPHPAll kindsTypes ofvariableFormonlyMarknumber
function to_guid_string($mix)
{
    if (is_object($mix) && function_exists('spl_object_hash')) {
        return spl_object_hash($mix);
    } elseif (is_resource($mix)) {
        $mix = get_resource_type($mix) . strval($mix);
    } else {
        $mix = serialize($mix);
    }
    return md5($mix);
}

// Load Extended Profiles
function load_ext_file()
{
    // loadfromdefinitionOutsidefile
    if (C('LOAD_EXT_FILE')) {
        $files = explode(',', C('LOAD_EXT_FILE'));
        foreach ($files as $file) {
            $file = COMMON_PATH . $file . '.php';
            if (is_file($file)) include $file;
        }
    }
    // loadfromdefinitionofDynamic  Configuration file 
    if (C('LOAD_EXT_CONFIG')) {
        $configs = C('LOAD_EXT_CONFIG');
        if (is_string($configs)) $configs = explode(',', $configs);
        foreach ($configs as $key => $config) {
            $file = CONF_PATH . $config . '.php';
            if (is_file($file)) {
                is_numeric($key) ? C(include $file) : C($key, include $file);
            }
        }
    }
}