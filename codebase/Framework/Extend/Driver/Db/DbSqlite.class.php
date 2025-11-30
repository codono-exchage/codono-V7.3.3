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
 * SqliteDatabase-driven
 * @category   Extend
 * @package  Extend
 * @subpackage  Driver.Db
 * @author    liu21st <liu21st@gmail.com>
 */
class DbSqlite extends Db
{

    /**
     * Architecturefunction ReadDatabase configuration information
     * @access public
     * @param array $config Database configuration array
     */
    public function __construct($config = '')
    {
        if (!extension_loaded('sqlite')) {
            throw_exception(L('_NOT_SUPPERT_') . ':sqlite');
        }
        if (!empty($config)) {
            if (!isset($config['mode'])) {
                $config['mode'] = 0666;
            }
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
            $pconnect = !empty($config['params']['persist']) ? $config['params']['persist'] : $this->pconnect;
            $conn = $pconnect ? 'sqlite_popen' : 'sqlite_open';
            $this->linkID[$linkNum] = $conn($config['database'], $config['mode']);
            if (!$this->linkID[$linkNum]) {
                throw_exception(sqlite_error_string());
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
        $this->queryID = null;
    }

    /**
     * Execute the query Return data sets
     * @access public
     * @param string $str sqlinstruction
     * @return mixed
     */
    public function query($str)
    {
        $this->initConnect(false);
        if (!$this->_linkID) return false;
        $this->queryStr = $str;
        //Previous releaseofsearch result
        if ($this->queryID) $this->free();
        N('db_query', 1);
        // recordingStartcarried outtime
        G('queryStartTime');
        $this->queryID = sqlite_query($this->_linkID, $str);
        $this->debug();
        if (false === $this->queryID) {
            $this->error();
            return false;
        } else {
            $this->numRows = sqlite_num_rows($this->queryID);
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
        $result = sqlite_exec($this->_linkID, $str);
        $this->debug();
        if (false === $result) {
            $this->error();
            return false;
        } else {
            $this->numRows = sqlite_changes($this->_linkID);
            $this->lastInsID = sqlite_last_insert_rowid($this->_linkID);
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
            sqlite_query($this->_linkID, 'BEGIN TRANSACTION');
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
            $result = sqlite_query($this->_linkID, 'COMMIT TRANSACTION');
            if (!$result) {
                $this->error();
                return false;
            }
            $this->transTimes = 0;
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
            $result = sqlite_query($this->_linkID, 'ROLLBACK TRANSACTION');
            if (!$result) {
                $this->error();
                return false;
            }
            $this->transTimes = 0;
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
            for ($i = 0; $i < $this->numRows; $i++) {
                // Returns an array of set
                $result[$i] = sqlite_fetch_array($this->queryID, SQLITE_ASSOC);
            }
            sqlite_seek($this->queryID, 0);
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
        $result = $this->query('PRAGMA table_info( ' . $tableName . ' )');
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
     * Close the database
     * @access public
     */
    public function close()
    {
        if ($this->_linkID) {
            sqlite_close($this->_linkID);
        }
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
        $code = sqlite_last_error($this->_linkID);
        $this->error = $code . ':' . sqlite_error_string($code);
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
        return sqlite_escape_string($str);
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