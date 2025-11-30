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
// $Id: Cookie.class.php 2702 2012-02-02 12:35:01Z liu21st $

/**
 * +------------------------------------------------------------------------------
 * CookieManagement
 * +------------------------------------------------------------------------------
 * @category   Think
 * @package  Think
 * @subpackage  Util
 * @author    liu21st <liu21st@gmail.com>
 * @version   $Id: Cookie.class.php 2702 2012-02-02 12:35:01Z liu21st $
 * +------------------------------------------------------------------------------
 */
class Cookie
{
    // judgmentCookiedoes it exist
    static function is_set($name)
    {
        return isset($_COOKIE[C('COOKIE_PREFIX') . $name]);
    }

    // Get aCookievalue
    static function get($name)
    {
        $value = $_COOKIE[C('COOKIE_PREFIX') . $name];
        $value = unserialize(base64_decode($value));
        return $value;
    }

    // Setting aCookievalue
    static function set($name, $value, $expire = '', $path = '', $domain = '')
    {
        if ($expire == '') {
            $expire = C('COOKIE_EXPIRE');
        }
        if (empty($path)) {
            $path = C('COOKIE_PATH');
        }
        if (empty($domain)) {
            $domain = C('COOKIE_DOMAIN');
        }
        $expire = !empty($expire) ? time() + $expire : 0;
        $value = base64_encode(serialize($value));
        setcookie(C('COOKIE_PREFIX') . $name, $value, $expire, $path, $domain);
        $_COOKIE[C('COOKIE_PREFIX') . $name] = $value;
    }

    // To remove aCookievalue
    static function delete($name)
    {
        Cookie::set($name, '', -3600);
        unset($_COOKIE[C('COOKIE_PREFIX') . $name]);
    }

    // ClearCookievalue
    static function clear()
    {
        unset($_COOKIE);
    }
}