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
 * SqlsrvDatabase-driven
 * @category   Extend
 * @package  Extend
 * @subpackage  Driver.Db
 * @author    liu21st <liu21st@gmail.com>
 */
class DbSqlsrv extends Db
{
    protected $selectSql = 'SELECT T1.* FROM (SELECT thinkphp.*, ROW_NUMBER() OVER (%ORDER%) AS ROW_NUMBER FROM (SELECT %DISTINCT% %FIELD% FROM %TABLE%%JOIN%%WHERE%%GROUP%%HAVING%) AS thinkphp) AS T1 %LIMIT%%COMMENT%';

    /**
     * Architecturefunction ReadDatabase configuration information
     * @access public
     * @param array $config Database configuration array
     */
    public function __construct($config = '')
    {
        if (!function_exists('sqlsrv_connect')) {
            throw_exception(L('_NOT_SUPPERT_') . ':sqlsrv');
        }
        if (!empty($config)) {
            $this->config = $config;
        }
    }

    /**
     * Database connection method
     * @access public
     */
    public function connect($config = '', $linkNum = 0)
    {
        if (!isset($this->linkID[$linkNum])) {
            if (empty($config)) $config = $this->config;
            $host = $config['hostname'] . ($config['hostport'] ? ",{$config['hostport']}" : '');
            $connectInfo = array('Database' => $config['database'], 'UID' => $config['username'], 'PWD' => $config['password'], 'CharacterSet' => C('DEFAULT_CHARSET'));
            $this->linkID[$linkNum] = sqlsrv_connect($host, $connectInfo);
            if (!$this->linkID[$linkNum]) $this->error(false);
            // markconnectionsuccess
            $this->connected = true;
            //LogoutdatabaseSafetyinformation
            if (1 != C('DB_DEPLOY_TYPE')) unset($this->config);
        }
        return $this->linkID[$linkNum];
    }

    /**
     * Free result
     * @access public
     */
    public function free()
    {
        sqlsrv_free_stmt($this->queryID);
        $this->queryID = null;
    }

    /**
     * Execute the query  Return data sets
     * @access public
     * @param string $str sqlinstruction
     * @param array $bind Parameter binding
     * @return mixed
     */
    public function query($str, $bind = array())
    {
        $this->initConnect(false);
        if (!$this->_linkID) return false;
        //Previous releaseofsearch result
        if ($this->queryID) $this->free();
        N('db_query', 1);
        // recordingStartcarried outtime
        G('queryStartTime');
        $str = str_replace(array_keys($bind), '?', $str);
        $bind = array_values($bind);
        $this->queryStr = $str;
        $this->queryID = sqlsrv_query($this->_linkID, $str, $bind, array("Scrollable" => SQLSRV_CURSOR_KEYSET));
        $this->debug();
        if (false === $this->queryID) {
            $this->error();
            return false;
        } else {
            $this->numRows = sqlsrv_num_rows($this->queryID);
            return $this->getAll();
        }
    }

    /**
     * Execute the statement
     * @access public
     * @param string $str sqlinstruction
     * @param array $bind Parameter binding
     * @return integer
     */
    public function execute($str, $bind = array())
    {
        $this->initConnect(true);
        if (!$this->_linkID) return false;
        //Previous releaseofsearch result
        if ($this->queryID) $this->free();
        N('db_write', 1);
        // recordingStartcarried outtime
        G('queryStartTime');
        $str = str_replace(array_keys($bind), '?', $str);
        $bind = array_values($bind);
        $this->queryStr = $str;
        $this->queryID = sqlsrv_query($this->_linkID, $str, $bind);
        $this->debug();
        if (false === $this->queryID) {
            $this->error();
            return false;
        } else {
            $this->numRows = sqlsrv_rows_affected($this->queryID);
            $this->lastInsID = $this->mssql_insert_id();
            return $this->numRows;
        }
    }

    /**
     * ForGet the last insertedofID
     * @access public
     * @return integer
     */
    public function mssql_insert_id()
    {
        $query = "SELECT @@IDENTITY as last_insert_id";
        $result = sqlsrv_query($this->_linkID, $query);
        list($last_insert_id) = sqlsrv_fetch_array($result);
        sqlsrv_free_stmt($result);
        return $last_insert_id;
    }

    /**
     * Start affairs
     * @access public
     * @return void
     */
    public function startTrans()
    {
        $this->initConnect(true);
        if (!$this->_linkID) return false;
        //datarollback stand by
        if ($this->transTimes == 0) {
            sqlsrv_begin_transaction($this->_linkID);
        }
        $this->transTimes++;
        return;
    }

    /**
     * Fornon-Auto Commitstatusthe followingofInquiresubmit
     * @access public
     * @return boolen
     */
    public function commit()
    {
        if ($this->transTimes > 0) {
            $result = sqlsrv_commit($this->_linkID);
            $this->transTimes = 0;
            if (!$result) {
                $this->error();
                return false;
            }
        }
        return true;
    }

    /**
     * Transaction rollback
     * @access public
     * @return boolen
     */
    public function rollback()
    {
        if ($this->transTimes > 0) {
            $result = sqlsrv_rollback($this->_linkID);
            $this->transTimes = 0;
            if (!$result) {
                $this->error();
                return false;
            }
        }
        return true;
    }

    /**
     * obtainallofQuery data
     * @access private
     * @return array
     */
    private function getAll()
    {
        //Return data sets
        $result = array();
        if ($this->numRows > 0) {
            while ($row = sqlsrv_fetch_array($this->queryID, SQLSRV_FETCH_ASSOC))
                $result[] = $row;
        }
        return $result;
    }

    /**
     * Obtaindata sheetofFieldinformation
     * @access public
     * @return array
     */
    public function getFields($tableName)
    {
        $result = $this->query("
            SELECT column_name,data_type,column_default,is_nullable
            FROM   information_schema.tables AS t
            JOIN   information_schema.columns AS c
            ON     t.table_catalog = c.table_catalog
            AND    t.table_schema  = c.table_schema
            AND    t.table_name    = c.table_name
            WHERE  t.table_name = '{$tableName}'");
        $pk = $this->query("SELECT * FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE WHERE TABLE_NAME='{$tableName}'");
        $info = array();
        if ($result) {
            foreach ($result as $key => $val) {
                $info[$val['column_name']] = array(
                    'name' => $val['column_name'],
                    'type' => $val['data_type'],
                    'notnull' => (bool)($val['is_nullable'] === ''), // not null is empty, null is yes
                    'default' => $val['column_default'],
                    'primary' => $val['column_name'] == $pk[0]['COLUMN_NAME'],
                    'autoinc' => false,
                );
            }
        }
        return $info;
    }

    /**
     * Obtaindata sheetofFieldinformation
     * @access public
     * @return array
     */
    public function getTables($dbName = '')
    {
        $result = $this->query("SELECT TABLE_NAME
            FROM INFORMATION_SCHEMA.TABLES
            WHERE TABLE_TYPE = 'BASE TABLE'
            ");
        $info = array();
        foreach ($result as $key => $val) {
            $info[$key] = current($val);
        }
        return $info;
    }

    /**
     * orderanalysis
     * @access protected
     * @param mixed $order
     * @return string
     */
    protected function parseOrder($order)
    {
        return !empty($order) ? ' ORDER BY ' . $order : ' ORDER BY rand()';
    }

    /**
     * Analysis of field names
     * @access protected
     * @param string $key
     * @return string
     */
    protected function parseKey(&$key)
    {
        $key = trim($key);
        if (!preg_match('/[,\'\"\*\(\)\[.\s]/', $key)) {
            $key = '[' . $key . ']';
        }
        return $key;
    }

    /**
     * limit
     * @access public
     * @param mixed $limit
     * @return string
     */
    public function parseLimit($limit)
    {
        if (empty($limit)) return '';
        $limit = explode(',', $limit);
        if (count($limit) > 1)
            $limitStr = '(T1.ROW_NUMBER BETWEEN ' . $limit[0] . ' + 1 AND ' . $limit[0] . ' + ' . $limit[1] . ')';
        else
            $limitStr = '(T1.ROW_NUMBER BETWEEN 1 AND ' . $limit[0] . ")";
        return 'WHERE ' . $limitStr;
    }

    /**
     * update record
     * @access public
     * @param mixed $data data
     * @param array $options expression
     * @return false | integer
     */
    public function update($data, $options)
    {
        $this->model = $options['model'];
        $sql = 'UPDATE '
            . $this->parseTable($options['table'])
            . $this->parseSet($data)
            . $this->parseWhere(!empty($options['where']) ? $options['where'] : '')
            . $this->parseLock(isset($options['lock']) ? $options['lock'] : false)
            . $this->parseComment(!empty($options['comment']) ? $options['comment'] : '');
        return $this->execute($sql, $this->parseBind(!empty($options['bind']) ? $options['bind'] : array()));
    }

    /**
     * Delete Record
     * @access public
     * @param array $options expression
     * @return false | integer
     */
    public function delete($options = array())
    {
        $this->model = $options['model'];
        $sql = 'DELETE FROM '
            . $this->parseTable($options['table'])
            . $this->parseWhere(!empty($options['where']) ? $options['where'] : '')
            . $this->parseLock(isset($options['lock']) ? $options['lock'] : false)
            . $this->parseComment(!empty($options['comment']) ? $options['comment'] : '');
        return $this->execute($sql, $this->parseBind(!empty($options['bind']) ? $options['bind'] : array()));
    }

    /**
     * Close the database
     * @access public
     */
    public function close()
    {
        if ($this->_linkID) {
            sqlsrv_close($this->_linkID);
        }
        $this->_linkID = null;
    }

    /**
     * Database Error Messages
     * And displays the currentSQLStatement
     * @access public
     * @return string
     */
    public function error($result = true)
    {
        $errors = sqlsrv_errors();
        $this->error = '';
        foreach ($errors as $error) {
            $this->error .= $error['code'] . ':' . $error['message'];
        }
        if ('' != $this->queryStr) {
            $this->error .= "\n [ SQLStatement ] : " . $this->queryStr;
        }
        $result ? trace($this->error, '', 'ERR') : throw_exception($this->error);
        return $this->error;
    }
}