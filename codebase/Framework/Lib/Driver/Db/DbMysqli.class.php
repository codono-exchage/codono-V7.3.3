<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2007 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: support@codono.com
// +----------------------------------------------------------------------

defined('THINK_PATH') or exit();

/**
 * MysqliDatabase-driven class
 * @category   Think
 * @package  Think
 * @subpackage  Driver.Db
 * @author    liu21st <liu21st@gmail.com>
 */
class DbMysqli extends Db
{

    /**
     * Architecturefunction ReadDatabase configuration information
     * @access public
     * @param array $config Database configuration array
     */
    public function __construct($config = '')
    {
        if (!extension_loaded('mysqli')) {
            throw_exception(L('_NOT_SUPPERT_') . ':mysqli');
        }
        if (!empty($config)) {
            $this->config = $config;
            if (empty($this->config['params'])) {
                $this->config['params'] = '';
            }
        }
    }

    /**
     * Database connection method
     * @access public
     * @throws ThinkExecption
     */
    public function connect($config = '', $linkNum = 0)
    {
        if (!isset($this->linkID[$linkNum])) {
            if (empty($config)) $config = $this->config;
            $this->linkID[$linkNum] = new mysqli($config['hostname'], $config['username'], $config['password'], $config['database'], $config['hostport'] ? intval($config['hostport']) : 3306);
            if (mysqli_connect_errno()) throw_exception(mysqli_connect_error());
            $dbVersion = $this->linkID[$linkNum]->server_version;

            // Setting up a database coding
            $this->linkID[$linkNum]->query("SET NAMES '" . C('DB_CHARSET') . "'");
            //Set up sql_model
            if ($dbVersion > '5.0.1') {
                $this->linkID[$linkNum]->query("SET sql_mode=''");
            }
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
        $this->queryID->free_result();
        $this->queryID = null;
    }

    /**
     * Execute the query Return data sets
     * @access public
     * @param string $str sqlinstruction
     * @return mixed
     */
    public function query($str,$options=[])
    {
        $this->initConnect(false);
        if (!$this->_linkID) return false;
        $this->queryStr = $str;
        //Previous releaseofsearch result
        if ($this->queryID) $this->free();
        N('db_query', 1);
        // recordingStartcarried outtime
        G('queryStartTime');
        $this->queryID = $this->_linkID->query($str);
        // The storage process improvement
        if ($this->_linkID->more_results()) {
            while (($res = $this->_linkID->next_result()) != NULL) {
                $res->free_result();
            }
        }
        $this->debug();
        if (false === $this->queryID) {
            $this->error();
            return false;
        } else {
            $this->numRows = $this->queryID->num_rows;
            $this->numCols = $this->queryID->field_count;
            return $this->getAll();
        }
    }

    /**
     * Execute the statement
     * @access public
     * @param string $str sqlinstruction
     * @return integer
     */
    public function execute($str)
    {
        $this->initConnect(true);
        if (!$this->_linkID) return false;
        $this->queryStr = $str;
        //Previous releaseofsearch result
        if ($this->queryID) $this->free();
        N('db_write', 1);
        // recordingStartcarried outtime
        G('queryStartTime');
        $result = $this->_linkID->query($str);
        $this->debug();
        if (false === $result) {
            $this->error();
            return false;
        } else {
            $this->numRows = $this->_linkID->affected_rows;
            $this->lastInsID = $this->_linkID->insert_id;
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
        //datarollback stand by
        if ($this->transTimes == 0) {
            $this->_linkID->autocommit(false);
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
            $this->_linkID->autocommit(true);
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
     * @param string $sql sqlStatement
     * @return array
     */
    public function getAll(): array
    {
        //Return data sets
        $result = [];
        if ($this->numRows > 0) {
            //Return data sets
            for ($i = 0; $i < $this->numRows; $i++) {
                $result[$i] = $this->queryID->fetch_assoc();
            }
            $this->queryID->data_seek(0);
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
        $result = $this->query('SHOW COLUMNS FROM ' . $this->parseKey($tableName));
        $info = array();
        if ($result) {
            foreach ($result as $key => $val) {
                $info[$val['Field']] = array(
                    'name' => $val['Field'],
                    'type' => $val['Type'],
                    'notnull' => (bool)($val['Null'] === ''), // not null is empty, null is yes
                    'default' => $val['Default'],
                    'primary' => (strtolower($val['Key']) == 'pri'),
                    'autoinc' => (strtolower($val['Extra']) == 'auto_increment'),
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
        $sql = !empty($dbName) ? 'SHOW TABLES FROM ' . $dbName : 'SHOW TABLES ';
        $result = $this->query($sql);
        $info = array();
        if ($result) {
            foreach ($result as $key => $val) {
                $info[$key] = current($val);
            }
        }
        return $info;
    }

    /**
     * Replace Record
     * @access public
     * @param mixed $data data
     * @param array $options Parameter expression
     * @return false | integer
     */
    public function replace($data, $options = array())
    {
        foreach ($data as $key => $val) {
            $value = $this->parseValue($val);
            if (is_scalar($value)) { // Filter nonscalardata
                $values[] = $value;
                $fields[] = $this->parseKey($key);
            }
        }
        $sql = 'REPLACE INTO ' . $this->parseTable($options['table']) . ' (' . implode(',', $fields) . ') VALUES (' . implode(',', $values) . ')';
        return $this->execute($sql);
    }

    /**
     * Insert Record
     * @access public
     * @param mixed $datas data
     * @param array $options Parameter expression
     * @param boolean $replace whetherreplace
     * @return false | integer
     */
    public function insertAll($datas, $options = array(), $replace = false)
    {
        if (!is_array($datas[0])) return false;
        $fields = array_keys($datas[0]);
        array_walk($fields, array($this, 'parseKey'));
        $values = array();
        foreach ($datas as $data) {
            $value = array();
            foreach ($data as $key => $val) {
                $val = $this->parseValue($val);
                if (is_scalar($val)) { // Filter nonscalardata
                    $value[] = $val;
                }
            }
            $values[] = '(' . implode(',', $value) . ')';
        }
        $sql = ($replace ? 'REPLACE' : 'INSERT') . ' INTO ' . $this->parseTable($options['table']) . ' (' . implode(',', $fields) . ') VALUES ' . implode(',', $values);
        return $this->execute($sql);
    }

    /**
     * Close the database
     * @access public
     * @return volid
     */
    public function close()
    {
        if ($this->_linkID) {
            $this->_linkID->close();
        }
        $this->_linkID = null;
    }

    /**
     * Database Error Messages
     * And displays the currentSQLStatement
     * @static
     * @access public
     * @return string
     */
    public function error()
    {
        $this->error = $this->_linkID->errno . ':' . $this->_linkID->error;
        if ('' != $this->queryStr) {
            $this->error .= "\n [ SQLStatement ] : " . $this->queryStr;
        }
        trace($this->error, '', 'ERR');
        return $this->error;
    }

    /**
     * SQLInstruction security filtering
     * @static
     * @access public
     * @param string $str SQLinstruction
     * @return string
     */
    public function escapeString($str)
    {
        if ($this->_linkID) {
            return $this->_linkID->real_escape_string($str);
        } else {
            return addslashes($str);
        }
    }

    /**
     * Processing field and table namesAdd to`
     * @access protected
     * @param string $key
     * @return string
     */
    protected function parseKey(&$key)
    {
        $key = trim($key);
        if (!preg_match('/[,\'\"\*\(\)`.\s]/', $key)) {
            $key = '`' . $key . '`';
        }
        return $key;
    }
}