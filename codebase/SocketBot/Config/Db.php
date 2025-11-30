<?php
namespace Config;
/**
 * mysql configuration
 * @author walkor
 */
class Db
{
    /**
     * An instance configuration of the database, when used like this
     * $user_array = Db::instance('db1')->select('name,age')->from('users')->where('age>12')->query();
     * Equivalent to
     * $user_array = Db::instance('db1')->query('SELECT `name`,`age` FROM `users` WHERE `age`>12');
     * @var array
     */
    public static $db1 = array(
	'host'    => 'localhost',
        'port'    => 3306,
        'user'    => 'root',
        'password' => '',
        'dbname'  => 'vegas',
        'charset'    => 'utf8'

    );
}
