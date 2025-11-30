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
 * PgsqlDatabase-driven
 * @category   Extend
 * @package  Extend
 * @subpackage  Driver.Db
 * @author    liu21st <liu21st@gmail.com>
 */
class DbPgsql extends Db
{

    /**
     * Architecturefunction ReadDatabase configuration information
     * @access public
     * @param array $config Database configuration array
     */
    public function __construct($config = '')
    {
        if (!extension_loaded('pgsql')) {
            throw_exception(L('_NOT_SUPPERT_') . ':pgsql');
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
            $pconnect = !empty($config['params']['persist']) ? $config['params']['persist'] : $this->pconnect;
            $conn = $pconnect ? 'pg_pconnect' : 'pg_connect';
            $this->linkID[$linkNum] = $conn('host=' . $config['hostname'] . ' port=' . $config['hostport'] . ' dbname=' . $config['database'] . ' user=' . $config['username'] . '  password=' . $config['password']);
            if (0 !== pg_connection_status($this->linkID[$linkNum])) {
                throw_exception($this->error(false));
            }
            //Setting coding
            pg_set_client_encoding($this->linkID[$linkNum], C('DB_CHARSET'));
            //$pgInfo = pg_version($this->linkID[$linkNum]);
            //$dbVersion = $pgInfo['server'];
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
        pg_free_result($this->queryID);
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
        $this->queryID = pg_query($this->_linkID, $str);
        $this->debug();
        if (false === $this->queryID) {
            $this->error();
            return false;
        } else {
            $this->numRows = pg_num_rows($this->queryID);
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
        $result = pg_query($this->_linkID, $str);
        $this->debug();
        if (false === $result) {
            $this->error();
            return false;
        } else {
            $this->numRows = pg_affected_rows($result);
            $this->lastInsID = $this->last_insert_id();
            return $this->numRows;
        }
    }

    /**
     * ForGet the last insertedofID
     * @access public
     * @return integer
     */
    public function last_insert_id()
    {
        $query = "SELECT LASTVAL() AS insert_id";
        $result = pg_query($this->_linkID, $query);
        list($last_insert_id) = pg_fetch_array($result, null, PGSQL_ASSOC);
        pg_free_result($result);
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
            pg_exec($this->_linkID, 'begin;');
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
            $result = pg_exec($this->_linkID, 'end;');
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
            $result = pg_exec($this->_linkID, 'abort;');
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
        $result = pg_fetch_all($this->queryID);
        pg_result_seek($this->queryID, 0);
        return $result;
    }

    /**
     * Obtaindata sheetofFieldinformation
     * @access public
     */
    public function getFields($tableName)
    {
        $result = $this->query("select a.attname as \"Field\",
            t.typname as \"Type\",
            a.attnotnull as \"Null\",
            i.indisprimary as \"Key\",
            d.adsrc as \"Default\"
            from pg_class c
            inner join pg_attribute a on a.attrelid = c.oid
            inner join pg_type t on a.atttypid = t.oid
            left join pg_attrdef d on a.attrelid=d.adrelid and d.adnum=a.attnum
            left join pg_index i on a.attnum=ANY(i.indkey) and c.oid = i.indrelid
            where (c.relname='{$tableName}' or c.relname = lower('{$tableName}'))   AND a.attnum > 0
                order by a.attnum asc;");
        $info = array();
        if ($result) {
            foreach ($result as $key => $val) {
                $info[$val['Field']] = array(
                    'name' => $val['Field'],
                    'type' => $val['Type'],
                    'notnull' => (bool)($val['Null'] == 't' ? 1 : 0), // 't' is 'not null'
                    'default' => $val['Default'],
                    'primary' => (strtolower($val['Key']) == 't'),
                    'autoinc' => (strtolower($val['Default']) == "nextval('{$tableName}_id_seq'::regclass)"),
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
        $result = $this->query("select tablename as Tables_in_test from pg_tables where  schemaname ='public'");
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
            pg_close($this->_linkID);
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
        $this->error = $result ? pg_result_error($this->queryID) : pg_last_error($this->_linkID);
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
        return pg_escape_string($str);
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