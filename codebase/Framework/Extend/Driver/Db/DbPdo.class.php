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
 * PDODatabase-driven
 * @category   Extend
 * @package  Extend
 * @subpackage  Driver.Db
 * @author    liu21st <liu21st@gmail.com>
 */
class DbPdo extends Db
{

    protected $PDOStatement = null;
    private $table = '';

    /**
     * Architecturefunction ReadDatabase configuration information
     * @access public
     * @param array $config Database configuration array
     */
    public function __construct($config = '')
    {
        if (!class_exists('PDO')) {
            throw_exception(L('_NOT_SUPPERT_') . ':PDO');
        }
        if (!empty($config)) {
            $this->config = $config;
            if (empty($this->config['params'])) {
                $this->config['params'] = array();
            }
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
            if ($this->pconnect) {
                $config['params'][PDO::ATTR_PERSISTENT] = true;
            }
            //$config['params'][PDO::ATTR_CASE] = C("DB_CASE_LOWER")?PDO::CASE_LOWER:PDO::CASE_UPPER;
            try {
                $this->linkID[$linkNum] = new PDO($config['dsn'], $config['username'], $config['password'], $config['params']);
            } catch (PDOException $e) {
                throw_exception($e->getMessage());
            }
            // becausePDOofconnectionSwitching may lead todatabaseTypes of Diferent ,thereforeAgainObtain下currentofdatabaseTypes of
            $this->dbType = $this->_getDsnType($config['dsn']);
            if (in_array($this->dbType, array('MSSQL', 'ORACLE', 'IBASE', 'OCI'))) {
                // due toPDOFor more thandatabasestand bynot enoughPerfect, so the shield If you still want to usePDO canNotethe followingRowCode
                throw_exception('Due to the currentPDOTemporarily unable perfect support' . $this->dbType . ' Please use the official' . $this->dbType . 'drive');
            }
            $this->linkID[$linkNum]->exec('SET NAMES ' . C('DB_CHARSET'));
            // markconnectionsuccess
            $this->connected = true;
            // LogoutDatabase ConnectivityConfigurationinformation
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
        $this->PDOStatement = null;
    }

    /**
     * Execute the query Return data sets
     * @access public
     * @param string $str sqlinstruction
     * @param array $bind Parameter binding
     * @return mixed
     */
    public function query($str, $bind = array())
    {
        $this->initConnect(false);
        if (!$this->_linkID) return false;
        $this->queryStr = $str;
        if (!empty($bind)) {
            $this->queryStr .= '[ ' . print_r($bind, true) . ' ]';
        }
        //Previous releaseofsearch result
        if (!empty($this->PDOStatement)) $this->free();
        N('db_query', 1);
        // recordingStartcarried outtime
        G('queryStartTime');
        $this->PDOStatement = $this->_linkID->prepare($str);
        if (false === $this->PDOStatement)
            throw_exception($this->error());
        $result = $this->PDOStatement->execute($bind);
        $this->debug();
        if (false === $result) {
            $this->error();
            return false;
        } else {
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
        $this->queryStr = $str;
        if (!empty($bind)) {
            $this->queryStr .= '[ ' . print_r($bind, true) . ' ]';
        }
        $flag = false;
        if ($this->dbType == 'OCI') {
            if (preg_match("/^\s*(INSERT\s+INTO)\s+(\w+)\s+/i", $this->queryStr, $match)) {
                $this->table = C("DB_SEQUENCE_PREFIX") . str_ireplace(C("DB_PREFIX"), "", $match[2]);
                $flag = (boolean)$this->query("SELECT * FROM user_sequences WHERE sequence_name='" . strtoupper($this->table) . "'");
            }
        }//modify by wyfeng at 2009.08.28
        //Previous releaseofsearch result
        if (!empty($this->PDOStatement)) $this->free();
        N('db_write', 1);
        // recordingStartcarried outtime
        G('queryStartTime');
        $this->PDOStatement = $this->_linkID->prepare($str);
        if (false === $this->PDOStatement) {
            throw_exception($this->error());
        }
        $result = $this->PDOStatement->execute($bind);
        $this->debug();
        if (false === $result) {
            $this->error();
            return false;
        } else {
            $this->numRows = $this->PDOStatement->rowCount();
            if ($flag || preg_match("/^\s*(INSERT\s+INTO|REPLACE\s+INTO)\s+/i", $str)) {
                $this->lastInsID = $this->getLastInsertId();
            }
            return $this->numRows;
        }
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
            $this->_linkID->beginTransaction();
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
            $result = $this->_linkID->commit();
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
            $result = $this->_linkID->rollback();
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
        $result = $this->PDOStatement->fetchAll(PDO::FETCH_ASSOC);
        $this->numRows = count($result);
        return $result;
    }

    /**
     * Obtaindata sheetofFieldinformation
     * @access public
     */
    public function getFields($tableName)
    {
        $this->initConnect(true);
        if (C('DB_DESCRIBE_TABLE_SQL')) {
            // Define special field queriesSQL
            $sql = str_replace('%table%', $tableName, C('DB_DESCRIBE_TABLE_SQL'));
        } else {
            switch ($this->dbType) {
                case 'MSSQL':
                case 'SQLSRV':
                    $sql = "SELECT   column_name as 'Name',   data_type as 'Type',   column_default as 'Default',   is_nullable as 'Null'
        FROM    information_schema.tables AS t
        JOIN    information_schema.columns AS c
        ON  t.table_catalog = c.table_catalog
        AND t.table_schema  = c.table_schema
        AND t.table_name    = c.table_name
        WHERE   t.table_name = '$tableName'";
                    break;
                case 'SQLITE':
                    $sql = 'PRAGMA table_info (' . $tableName . ') ';
                    break;
                case 'ORACLE':
                case 'OCI':
                    $sql = "SELECT a.column_name \"Name\",data_type \"Type\",decode(nullable,'Y',0,1) notnull,data_default \"Default\",decode(a.column_name,b.column_name,1,0) \"pk\" "
                        . "FROM user_tab_columns a,(SELECT column_name FROM user_constraints c,user_cons_columns col "
                        . "WHERE c.constraint_name=col.constraint_name AND c.constraint_type='P' and c.table_name='" . strtoupper($tableName)
                        . "') b where table_name='" . strtoupper($tableName) . "' and a.column_name=b.column_name(+)";
                    break;
                case 'PGSQL':
                    $sql = 'select fields_name as "Name",fields_type as "Type",fields_not_null as "Null",fields_key_name as "Key",fields_default as "Default",fields_default as "Extra" from table_msg(' . $tableName . ');';
                    break;
                case 'IBASE':
                    break;
                case 'MYSQL':
                default:
                    $sql = 'DESCRIBE ' . $tableName;//Remark: Not only for the driver classmysqlCan not add``
            }
        }
        $result = $this->query($sql);
        $info = array();
        if ($result) {
            foreach ($result as $key => $val) {
                $val = array_change_key_case($val);
                $val['name'] = isset($val['name']) ? $val['name'] : "";
                $val['type'] = isset($val['type']) ? $val['type'] : "";
                $name = isset($val['field']) ? $val['field'] : $val['name'];
                $info[$name] = array(
                    'name' => $name,
                    'type' => $val['type'],
                    'notnull' => (bool)(((isset($val['null'])) && ($val['null'] === '')) || ((isset($val['notnull'])) && ($val['notnull'] === ''))), // not null is empty, null is yes
                    'default' => isset($val['default']) ? $val['default'] : (isset($val['dflt_value']) ? $val['dflt_value'] : ""),
                    'primary' => isset($val['key']) ? strtolower($val['key']) == 'pri' : (isset($val['pk']) ? $val['pk'] : false),
                    'autoinc' => isset($val['extra']) ? strtolower($val['extra']) == 'auto_increment' : (isset($val['key']) ? $val['key'] : false),
                );
            }
        }
        return $info;
    }

    /**
     * ObtainDatabasetableinformation
     * @access public
     */
    public function getTables($dbName = '')
    {
        if (C('DB_FETCH_TABLES_SQL')) {
            // Define special table querySQL
            $sql = str_replace('%db%', $dbName, C('DB_FETCH_TABLES_SQL'));
        } else {
            switch ($this->dbType) {
                case 'ORACLE':
                case 'OCI':
                    $sql = 'SELECT table_name FROM user_tables';
                    break;
                case 'MSSQL':
                case 'SQLSRV':
                    $sql = "SELECT TABLE_NAME	FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_TYPE = 'BASE TABLE'";
                    break;
                case 'PGSQL':
                    $sql = "select tablename as Tables_in_test from pg_tables where  schemaname ='public'";
                    break;
                case 'IBASE':
                    // temporarilynot support
                    throw_exception(L('_NOT_SUPPORT_DB_') . ':IBASE');
                    break;
                case 'SQLITE':
                    $sql = "SELECT name FROM sqlite_master WHERE type='table' "
                        . "UNION ALL SELECT name FROM sqlite_temp_master "
                        . "WHERE type='table' ORDER BY name";
                    break;
                case 'MYSQL':
                default:
                    if (!empty($dbName)) {
                        $sql = 'SHOW TABLES FROM ' . $dbName;
                    } else {
                        $sql = 'SHOW TABLES ';
                    }
            }
        }
        $result = $this->query($sql);
        $info = array();
        foreach ($result as $key => $val) {
            $info[$key] = current($val);
        }
        return $info;
    }

    /**
     * limitanalysis
     * @access protected
     * @param mixed $lmit
     * @return string
     */
    protected function parseLimit($limit)
    {
        $limitStr = '';
        if (!empty($limit)) {
            switch ($this->dbType) {
                case 'PGSQL':
                case 'SQLITE':
                    $limit = explode(',', $limit);
                    if (count($limit) > 1) {
                        $limitStr .= ' LIMIT ' . $limit[1] . ' OFFSET ' . $limit[0] . ' ';
                    } else {
                        $limitStr .= ' LIMIT ' . $limit[0] . ' ';
                    }
                    break;
                case 'MSSQL':
                case 'SQLSRV':
                    break;
                case 'IBASE':
                    // temporarilynot support
                    break;
                case 'ORACLE':
                case 'OCI':
                    break;
                case 'MYSQL':
                default:
                    $limitStr .= ' LIMIT ' . $limit . ' ';
            }
        }
        return $limitStr;
    }

    /**
     * Processing field and table names
     * @access protected
     * @param string $key
     * @return string
     */
    protected function parseKey(&$key)
    {
        if ($this->dbType == 'MYSQL') {
            $key = trim($key);
            if (!preg_match('/[,\'\"\*\(\)`.\s]/', $key)) {
                $key = '`' . $key . '`';
            }
            return $key;
        } else {
            return parent::parseKey($key);
        }

    }

    /**
     * Close the database
     * @access public
     */
    public function close()
    {
        $this->_linkID = null;
    }

    /**
     * Database Error Messages
     * And displays the currentSQLStatement
     * @access public
     * @return string
     */
    public function error()
    {
        if ($this->PDOStatement) {
            $error = $this->PDOStatement->errorInfo();
            $this->error = $error[1] . ':' . $error[2];
        } else {
            $this->error = '';
        }
        if ('' != $this->queryStr) {
            $this->error .= "\n [ SQLStatement ] : " . $this->queryStr;
        }
        trace($this->error, '', 'ERR');
        return $this->error;
    }

    /**
     * SQLInstruction security filtering
     * @access public
     * @param string $str SQLinstruction
     * @return string
     */
    public function escapeString($str)
    {
        switch ($this->dbType) {
            case 'PGSQL':
            case 'MSSQL':
            case 'SQLSRV':
            case 'MYSQL':
                return addslashes($str);
            case 'IBASE':
            case 'SQLITE':
            case 'ORACLE':
            case 'OCI':
                return str_ireplace("'", "''", $str);
        }
    }

    /**
     * valueanalysis
     * @access protected
     * @param mixed $value
     * @return string
     */
    protected function parseValue($value)
    {
        if (is_string($value)) {
            $value = strpos($value, ':') === 0 ? $this->escapeString($value) : '\'' . $this->escapeString($value) . '\'';
        } elseif (isset($value[0]) && is_string($value[0]) && strtolower($value[0]) == 'exp') {
            $value = $this->escapeString($value[1]);
        } elseif (is_array($value)) {
            $value = array_map(array($this, 'parseValue'), $value);
        } elseif (is_bool($value)) {
            $value = $value ? '1' : '0';
        } elseif (is_null($value)) {
            $value = 'null';
        }
        return $value;
    }

    /**
     * Get the last insertedid
     * @access public
     * @return integer
     */
    public function getLastInsertId()
    {
        switch ($this->dbType) {
            case 'PGSQL':
            case 'SQLITE':
            case 'MSSQL':
            case 'SQLSRV':
            case 'IBASE':
            case 'MYSQL':
                return $this->_linkID->lastInsertId();
            case 'ORACLE':
            case 'OCI':
                $sequenceName = $this->table;
                $vo = $this->query("SELECT {$sequenceName}.currval currval FROM dual");
                return $vo ? $vo[0]["currval"] : 0;
        }
    }
}