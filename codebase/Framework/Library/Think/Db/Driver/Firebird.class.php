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
 * FirebirdDatabase-driven
 */
class Firebird extends Driver
{
    protected $selectSql = 'SELECT %LIMIT% %DISTINCT% %FIELD% FROM %TABLE%%JOIN%%WHERE%%GROUP%%HAVING%%ORDER%';

    /**
     * Resolvepdoconnecteddsninformation
     * @access public
     * @param array $config Connection Information
     * @return string
     */
    protected function parseDsn($config)
    {
        $dsn = 'firebird:dbname=' . $config['hostname'] . '/' . ($config['hostport'] ?: 3050) . ':' . $config['database'];
        return $dsn;
    }

    /**
     * Execute the statement
     * @access public
     * @param string $str sqlinstruction
     * @param boolean $fetchSql Just do not get executedSQL
     * @return mixed
     */
    public function execute($str, $fetchSql = false)
    {
        $this->initConnect(true);
        if (!$this->_linkID) return false;
        $this->queryStr = $str;
        if (!empty($this->bind)) {
            $that = $this;
            $this->queryStr = strtr($this->queryStr, array_map(function ($val) use ($that) {
                return '\'' . $that->escapeString($val) . '\'';
            }, $this->bind));
        }
        if ($fetchSql) {
            return $this->queryStr;
        }
        //Previous releaseofsearch result
        if (!empty($this->PDOStatement)) $this->free();
        $this->executeTimes++;
        N('db_write', 1); // compatibleCode
        // recordingStartcarried outtime
        $this->debug(true);
        $this->PDOStatement = $this->_linkID->prepare($str);
        if (false === $this->PDOStatement) {
            E($this->error());
        }
        foreach ($this->bind as $key => $val) {
            if (is_array($val)) {
                $this->PDOStatement->bindValue($key, $val[0], $val[1]);
            } else {
                $this->PDOStatement->bindValue($key, $val);
            }
        }
        $this->bind = array();
        $result = $this->PDOStatement->execute();
        $this->debug(false);
        if (false === $result) {
            $this->error();
            return false;
        } else {
            $this->numRows = $this->PDOStatement->rowCount();
            return $this->numRows;
        }
    }

    /**
     * Obtaindata sheetofFieldinformation
     * @access public
     */
    public function getFields($tableName)
    {
        $this->initConnect(true);
        list($tableName) = explode(' ', $tableName);
        $sql = 'SELECT RF.RDB$FIELD_NAME AS FIELD,RF.RDB$DEFAULT_VALUE AS DEFAULT1,RF.RDB$NULL_FLAG AS NULL1,TRIM(T.RDB$TYPE_NAME) || \'(\' || F.RDB$FIELD_LENGTH || \')\' as TYPE FROM RDB$RELATION_FIELDS RF LEFT JOIN RDB$FIELDS F ON (F.RDB$FIELD_NAME = RF.RDB$FIELD_SOURCE) LEFT JOIN RDB$TYPES T ON (T.RDB$TYPE = F.RDB$FIELD_TYPE) WHERE RDB$RELATION_NAME=UPPER(\'' . $tableName . '\') AND T.RDB$FIELD_NAME = \'RDB$FIELD_TYPE\' ORDER By RDB$FIELD_POSITION';
        $result = $this->query($sql);
        $info = array();
        if ($result) {
            foreach ($result as $key => $val) {
                $info[trim($val['field'])] = array(
                    'name' => trim($val['field']),
                    'type' => $val['type'],
                    'notnull' => (bool)($val['null1'] == 1), // 1ShowIt is notNull
                    'default' => $val['default1'],
                    'primary' => false,
                    'autoinc' => false,
                );
            }
        }
        //Obtaining primary key
        $sql = 'select b.rdb$field_name as field_name from rdb$relation_constraints a join rdb$index_segments b on a.rdb$index_name=b.rdb$index_name where a.rdb$constraint_type=\'PRIMARY KEY\' and a.rdb$relation_name=UPPER(\'' . $tableName . '\')';
        $rs_temp = $this->query($sql);
        foreach ($rs_temp as $row) {
            $info[trim($row['field_name'])]['primary'] = true;
        }
        return $info;
    }

    /**
     * ObtainDatabasetableinformation
     * @access public
     */
    public function getTables($dbName = '')
    {
        $sql = 'SELECT DISTINCT RDB$RELATION_NAME FROM RDB$RELATION_FIELDS WHERE RDB$SYSTEM_FLAG=0';
        $result = $this->query($sql);
        $info = array();
        foreach ($result as $key => $val) {
            $info[$key] = trim(current($val));
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
        return str_replace("'", "''", $str);
    }

    /**
     * limit
     * @access public
     * @param $limit limitexpression
     * @return string
     */
    public function parseLimit($limit)
    {
        $limitStr = '';
        if (!empty($limit)) {
            $limit = explode(',', $limit);
            if (count($limit) > 1) {
                $limitStr = ' FIRST ' . $limit[1] . ' SKIP ' . $limit[0] . ' ';
            } else {
                $limitStr = ' FIRST ' . $limit[0] . ' ';
            }
        }
        return $limitStr;
    }
}
