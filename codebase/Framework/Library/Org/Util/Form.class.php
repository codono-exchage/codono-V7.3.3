<?php

// +----------------------------------------------------------------------
// | CODONO
// +----------------------------------------------------------------------
// | Copyright (c) 2023 http://codono.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: support@codono.com
// +----------------------------------------------------------------------
namespace Org\Util;
/**
 * Class FormToken
 *
 * Form token generation class
 *
 */
class Form
{

    private static function createToken($type = '', $user_id = 0)
    {
        $_salt = C('FORM_TOKEN_SALT');
        return md5($_salt . time() . $user_id . $type);
    }

    /**
     * Create/update form token
     * @param string $type   Form type (REGISTER, INFO, ORDER)
     * @param int $user_id   User ID
     * @return string        Form token
     */
    public static function createTokenForUser($type = '', $user_id = 0)
    {
        session('user.form_token', self::createToken($type, $user_id), 1800);
        return session('user.form_token');
    }

    /**
     * Clear form token
     */
    public static function clearToken()
    {
        session('user.form_token', null);
        return true;
    }

//    /**
//     * Update token
//     * @param string $type
//     * @param int $user_id
//     */
//    public static function updateToken($type = '', $user_id = 0)
//    {
//        session('form_token', self::_crete_token($type = '', $user_id = 0), 1800);
//        return session('form_token');
//    }


    /**
     * Validate form token
     * @param string $token     Form token
     */
    public static function validateToken($token = '')
    {
        if (empty($token) || empty(session('user.form_token'))) {
            return false;
        } else {
            self::clearToken();
            return true;
        }
    }
}

?>
