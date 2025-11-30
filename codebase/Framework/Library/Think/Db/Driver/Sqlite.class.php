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

namespace Think\Db\Driver;

use Think\Db\Driver;

/**
 * SqliteDatabase-driven
 */
class Sqlite extends Driver
{

    /**
     * Resolvepdoconnecteddsninformation
     * @access public
     * @param array $config Connection Information
     * @return string
     */
    protected function parseDsn($config)
    {
        $dsn = 'sqlite:' . $config['database'];
        return $dsn;
    }

    /**
     * Obtaindata sheetofFieldinformation
     * @access public
     * @return array
     */
    public function getFields($tableName)
    {
        list($tableName) = explode(' ', $tableName);
        $result = $this->query('PRAGMA table_info( ' . $tableName . ' )');
        $info = array();
        if ($result) {
            foreach ($result as $key => $val) {
                $info[$val['field']] = array(
                    'name' => $val['field'],
                    'type' => $val['type'],
                    'notnull' => (bool)($val['null'] === ''), // not null is empty, null is yes
                    'default' => $val['default'],
                    'primary' => (strtolower($val['dey']) == 'pri'),
                    'autoinc' => (strtolower($val['extra']) == 'auto_increment'),
                );
            }
        }
        return $info;
    }

    /**
     * ObtainDatabasetableinformation
     * @access public
     * @return array
     */
    public function getTables($dbName = '')
    {
        $result = $this->query("SELECT name FROM sqlite_master WHERE type='table' "
            . "UNION ALL SELECT name FROM sqlite_temp_master "
            . "WHERE type='table' ORDER BY name");
        $info = array();
        foreach ($result as $key => $val) {
            $info[$key] = current($val);
        }
        return $info;
    }

    /**
     * SQLInstruction security filtering
     * @access public
     * @param string $str SQLinstruction
     * @return string
     */
    public function escapeString($str)
    {
        return str_ireplace("'", "''", $str);
    }

    /**
     * limit
     * @access public
     * @return string
     */
    public function parseLimit($limit)
    {
        $limitStr = '';
        if (!empty($limit)) {
            $limit = explode(',', $limit);
            if (count($limit) > 1) {
                $limitStr .= ' LIMIT ' . $limit[1] . ' OFFSET ' . $limit[0] . ' ';
            } else {
                $limitStr .= ' LIMIT ' . $limit[0] . ' ';
            }
        }
        return $limitStr;
    }
}
