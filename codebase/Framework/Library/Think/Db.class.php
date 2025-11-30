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
 * ThinkPHP database middle layer implementation class
 */
class Db
{
    static private $instance = array(); // Instances of database connection
    static private $_instance = null;   // The current database connection instance

    /**
     * Create database class instance
     * @static
     * @access public
     * @param mixed $config Connection configuration
     * @return Object Returns the database driver class
     */
    static public function getInstance($config = array())
    {
        $md5 = md5(serialize($config));
        if (!isset(self::$instance[$md5])) {
            // Parse connection parameters. Support arrays and strings.
            $options = self::parseConfig($config);
            // Removed the conversion from 'mysqli' to 'mysql'
            $class = !empty($options['lite']) ? 'Think\Db\Lite' : 'Think\\Db\\Driver\\' . ucwords(strtolower($options['type']));
			
            if (class_exists($class)) {
                self::$instance[$md5] = new $class($options);
            } else {
                clog(__CLASS__.'/'.__METHOD__ ,  $class . L('_NO_DB_DRIVER_'));
                echo "We are upgrading our system :FLTCC02";
            }
        }
        self::$_instance = self::$instance[$md5];
        return self::$_instance;
    }

    /**
     * Database connection parameter parsing
     * @static
     * @access private
     * @param mixed $config
     * @return array
     */
    static private function parseConfig($config)
    {
        if (!empty($config)) {
            if (is_string($config)) {
                return self::parseDsn($config);
            }
            $config = array_change_key_case($config);
            $config = array(
                'type' => $config['db_type'],
                'username' => $config['db_user'],
                'password' => $config['db_pwd'],
                'hostname' => $config['db_host'],
                'hostport' => $config['db_port'],
                'database' => $config['db_name'],
                'dsn' => $config['db_dsn'] ?? null,
                'params' => $config['db_params'] ?? null,
                'charset' => $config['db_charset'] ?? 'utf8',
                'deploy' => $config['db_deploy_type'] ?? 0,
                'rw_separate' => $config['db_rw_separate'] ?? false,
                'master_num' => $config['db_master_num'] ?? 1,
                'slave_no' => $config['db_slave_no'] ?? '',
                'debug' => $config['db_debug'] ?? APP_DEBUG,
                'lite' => $config['db_lite'] ?? false,
            );
        } else {
            $config = array(
                'type' => C('DB_TYPE'),
                'username' => C('DB_USER'),
                'password' => C('DB_PWD'),
                'hostname' => C('DB_HOST'),
                'hostport' => C('DB_PORT'),
                'database' => C('DB_NAME'),
                'dsn' => C('DB_DSN'),
                'params' => C('DB_PARAMS'),
                'charset' => C('DB_CHARSET'),
                'deploy' => C('DB_DEPLOY_TYPE'),
                'rw_separate' => C('DB_RW_SEPARATE'),
                'master_num' => C('DB_MASTER_NUM'),
                'slave_no' => C('DB_SLAVE_NO'),
                'debug' => C('DB_DEBUG', null, APP_DEBUG),
                'lite' => C('DB_LITE'),
            );
        }
        return $config;
    }

    /**
     * DSN Resolution
     * @static
     * @access private
     * @param string $dsnStr
     * @return array
     */
    static private function parseDsn($dsnStr)
    {
        if (empty($dsnStr)) {
            return false;
        }
        $info = parse_url($dsnStr);
        if (!$info) {
            // Added error handling for invalid DSN string
            return false;
        }
        $dsn = array(
            'type' => $info['scheme'],
            'username' => $info['user'] ?? '',
            'password' => $info['pass'] ?? '',
            'hostname' => $info['host'] ?? '',
            'hostport' => $info['port'] ?? '',
            'database' => isset($info['path']) ? substr($info['path'], 1) : '',
            'charset' => $info['fragment'] ?? 'utf8',
        );

        if (isset($info['query'])) {
            parse_str($info['query'], $dsn['params']);
        } else {
            $dsn['params'] = array();
        }
        return $dsn;
    }

    // Method invocation on the driver class
    static public function __callStatic($method, $params)
    {
        if (!method_exists(self::$_instance, $method)) {
            // Handle the error if the method does not exist
            //throw new \BadMethodCallException("Method $method does not exist on the database driver class");
			E('E146 STATIC');
        }
        return call_user_func_array([self::$_instance, $method], $params);
    }
}
