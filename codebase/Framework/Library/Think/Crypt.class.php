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
namespace Think;
/**
 * Encryption and decryption class
 */
class Crypt
{

    private static $handler = '';

    public static function init($type = '')
    {
        $type = $type ?: C('DATA_CRYPT_TYPE');
        $class = strpos($type, '\\') ? $type : 'Think\\Crypt\\Driver\\' . ucwords(strtolower($type));
        self::$handler = $class;
    }

    /**
     * Encrypted string
     * @param string $str String
     * @param string $key encryptionkey
     * @param integer $expire Period (s) 0 Is permanent
     * @return string
     */
    public static function encrypt($data, $key, $expire = 0)
    {
        if (empty(self::$handler)) {
            self::init();
        }
        $class = self::$handler;
        return $class::encrypt($data, $key, $expire);
    }

    /**
     * Decryption string
     * @param string $str String
     * @param string $key encryptionkey
     * @return string
     */
    public static function decrypt($data, $key)
    {
        if (empty(self::$handler)) {
            self::init();
        }
        $class = self::$handler;
        return $class::decrypt($data, $key);
    }
}