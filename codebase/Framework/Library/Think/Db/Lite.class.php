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

namespace Think\Db;

use Think\Config;
use Think\Debug;
use Think\Log;
use PDO;

class Lite
{
    // PDOOperation Example
    protected $PDOStatement = null;
    // currentoperatingbelong tomodel Name
    protected $model = '_think_';
    // currentSQLinstruction
    protected $queryStr = '';
    protected $modelSql = array();
    // At lastinsertID
    protected $lastInsID = null;
    // returnorinfluencesrecordingnumber
    protected $numRows = 0;
    // Affairsinstructionnumber
    protected $transTimes = 0;
    // Error Messages
    protected $error = '';
    // Database ConnectivityID Support for multipleconnection
    protected $linkID = array();
    // currentconnectionID
    protected $_linkID = null;
    // Database ConnectivityparameterConfiguration
    protected $config = array(
        'type' => '',     // databaseTypes of
        'hostname' => '127.0.0.1', // serveraddress
        'database' => '',          // DatabaseName
        'username' => '',      // username
        'password' => '',          // password
        'hostport' => '',        // port     
        'dsn' => '', //          
        'params' => array(), // Database Connectivityparameter        
        'charset' => 'utf8',      // databasecodingdefaultuseutf8  
        'prefix' => '',    // databaseTable Prefix
        'debug' => false, // Database Debuggingmode
        'deploy' => 0, // databasedeploythe way:0 centralized(singleserver),1 distributed(Master-slaveserver)
        'rw_separate' => false,       // databaseRead and writewhetherSeparate Master-slave effective
        'master_num' => 1, // After the separate read and write Primary serverQuantity
        'slave_no' => '', // DesignationFromserverNo.
    );
    // databaseexpression
    protected $comparison = array('eq' => '=', 'neq' => '<>', 'gt' => '>', 'egt' => '>=', 'lt' => '<', 'elt' => '<=', 'notlike' => 'NOT LIKE', 'like' => 'LIKE', 'in' => 'IN', 'notin' => 'NOT IN');
    // Query Expression
    protected $selectSql = 'SELECT%DISTINCT% %FIELD% FROM %TABLE%%JOIN%%WHERE%%GROUP%%HAVING%%ORDER%%LIMIT% %UNION%%COMMENT%';
    // Inquirefrequency
    protected $queryTimes = 0;
    // carried outfrequency
    protected $executeTimes = 0;
    // PDOConnection parameters
    protected $options = array(
        PDO::ATTR_CASE => PDO::CASE_LOWER,
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_ORACLE_NULLS => PDO::NULL_NATURAL,
        PDO::ATTR_STRINGIFY_FETCHES => false,
    );

    /**
     * Architecturefunction ReadDatabase configuration information
     * @access public
     * @param array $config Database configuration array
     */
    public function __construct($config = '')
    {
        if (!empty($config)) {
            $this->config = array_merge($this->config, $config);
            if (is_array($this->config['params'])) {
                $this->options += $this->config['params'];
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
            try {
                if (empty($config['dsn'])) {
                    $config['dsn'] = $this->parseDsn($config);
                }
                if (version_compare(PHP_VERSION, '5.3.6', '<=')) { //Disablemold拟PretreatmentStatement
                    $this->options[PDO::ATTR_EMULATE_PREPARES] = false;
                }
                $this->linkID[$linkNum] = new PDO($config['dsn'], $config['username'], $config['password'], $this->options);
            } catch (\PDOException $e) {
                E($e->getMessage());
            }
        }
        return $this->linkID[$linkNum];
    }

    /**
     * Resolvepdoconnecteddsninformation
     * @access public
     * @param array $config Connection Information
     * @return string
     */
    protected function parseDsn($config)
    {
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
            $that = $this;
            $this->queryStr = strtr($this->queryStr, array_map(function ($val) use ($that) {
                return '\'' . $that->escapeString($val) . '\'';
            }, $bind));
        }
        //Previous releaseofsearch result
        if (!empty($this->PDOStatement)) $this->free();
        $this->queryTimes++;
        N('db_query', 1); // compatibleCode        
        // debuggingStart
        $this->debug(true);
        $this->PDOStatement = $this->_linkID->prepare($str);
        if (false === $this->PDOStatement)
            E($this->error());
        foreach ($bind as $key => $val) {
            if (is_array($val)) {
                $this->PDOStatement->bindValue($key, $val[0], $val[1]);
            } else {
                $this->PDOStatement->bindValue($key, $val);
            }
        }
        $result = $this->PDOStatement->execute();
        // debuggingEnd
        $this->debug(false);
        if (false === $result) {
            $this->error();
            return false;
        } else {
            return $this->getResult();
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
            $that = $this;
            $this->queryStr = strtr($this->queryStr, array_map(function ($val) use ($that) {
                return '\'' . $that->escapeString($val) . '\'';
            }, $bind));
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
        foreach ($bind as $key => $val) {
            if (is_array($val)) {
                $this->PDOStatement->bindValue($key, $val[0], $val[1]);
            } else {
                $this->PDOStatement->bindValue($key, $val);
            }
        }
        $result = $this->PDOStatement->execute();
        $this->debug(false);
        if (false === $result) {
            $this->error();
            return false;
        } else {
            $this->numRows = $this->PDOStatement->rowCount();
            if (preg_match("/^\s*(INSERT\s+INTO|REPLACE\s+INTO)\s+/i", $str)) {
                $this->lastInsID = $this->_linkID->lastInsertId();
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
     * @return boolean
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
     * @return boolean
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
    private function getResult()
    {
        //Return data sets
        $result = $this->PDOStatement->fetchAll(PDO::FETCH_ASSOC);
        $this->numRows = count($result);
        return $result;
    }

    /**
     * Get queries
     * @access public
     * @param boolean $execute Are all of the query
     * @return integer
     */
    public function getQueryTimes($execute = false)
    {
        return $execute ? $this->queryTimes + $this->executeTimes : $this->queryTimes;
    }

    /**
     * Get the number of executions
     * @access public
     * @return integer
     */
    public function getExecuteTimes()
    {
        return $this->executeTimes;
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
        // recordingerrorJournal
        trace($this->error, '', 'ERR');
        if ($this->config['debug']) {// OpenDatabase Debuggingmode
            E($this->error);
        } else {
            return $this->error;
        }
    }

    /**
     * ObtainLastInquireofsqlStatement
     * @param string $model model Name
     * @access public
     * @return string
     */
    public function getLastSql($model = '')
    {
        return $model ? $this->modelSql[$model] : $this->queryStr;
    }

    /**
     * Acquired recently insertedID
     * @access public
     * @return string
     */
    public function getLastInsID()
    {
        return $this->lastInsID;
    }

    /**
     * Obtainmost近ofError Messages
     * @access public
     * @return string
     */
    public function getError()
    {
        return $this->error;
    }

    /**
     * SQLInstruction security filtering
     * @access public
     * @param string $str SQLString
     * @return string
     */
    public function escapeString($str)
    {
        return addslashes($str);
    }

    /**
     * Set the current operating model
     * @access public
     * @param string $model model Name
     * @return void
     */
    public function setModel($model)
    {
        $this->model = $model;
    }

    /**
     * Database Debugging The current recordSQL
     * @access protected
     * @param boolean $start Debugging start tag true Start false End
     */
    protected function debug($start)
    {
        if ($this->config['debug']) {// OpenDatabase Debuggingmode
            if ($start) {
                G('queryStartTime');
            } else {
                $this->modelSql[$this->model] = $this->queryStr;
                //$this->model  =   '_think_';
                // recordingoperatingEndtime
                G('queryEndTime');
                trace($this->queryStr . ' [ RunTime:' . G('queryStartTime', 'queryEndTime') . 's ]', '', 'SQL');
            }
        }
    }

    /**
     * Initialize the database connection
     * @access protected
     * @param boolean $master Primary server
     * @return void
     */
    protected function initConnect($master = true)
    {
        if (!empty($this->config['deploy']))
            // usedistributeddatabase
            $this->_linkID = $this->multiConnect($master);
        else
            // defaultsingledatabase
            if (!$this->_linkID) $this->_linkID = $this->connect();
    }

    /**
     * Distributed server connection
     * @access protected
     * @param boolean $master Primary server
     * @return void
     */
    protected function multiConnect($master = false)
    {
        // distributedDatabase ConfigurationResolve
        $_config['username'] = explode(',', $this->config['username']);
        $_config['password'] = explode(',', $this->config['password']);
        $_config['hostname'] = explode(',', $this->config['hostname']);
        $_config['hostport'] = explode(',', $this->config['hostport']);
        $_config['database'] = explode(',', $this->config['database']);
        $_config['dsn'] = explode(',', $this->config['dsn']);
        $_config['charset'] = explode(',', $this->config['charset']);

        // databaseRead and writewhetherSeparate
        if ($this->config['rw_separate']) {
            // Master-slaveformulauseSeparate read and write
            if ($master)
                // Primary serverWrite
                $r = floor(mt_rand(0, $this->config['master_num'] - 1));
            else {
                if (is_numeric($this->config['slave_no'])) {// Designationserverread
                    $r = $this->config['slave_no'];
                } else {
                    // readoperatingconnectionFromserver
                    $r = floor(mt_rand($this->config['master_num'], count($_config['hostname']) - 1));   // Eachrandomconnecteddatabase
                }
            }
        } else {
            // Read and writeoperatingDoes not distinguish betweenserver
            $r = floor(mt_rand(0, count($_config['hostname']) - 1));   // Eachrandomconnecteddatabase
        }
        $db_config = array(
            'username' => isset($_config['username'][$r]) ? $_config['username'][$r] : $_config['username'][0],
            'password' => isset($_config['password'][$r]) ? $_config['password'][$r] : $_config['password'][0],
            'hostname' => isset($_config['hostname'][$r]) ? $_config['hostname'][$r] : $_config['hostname'][0],
            'hostport' => isset($_config['hostport'][$r]) ? $_config['hostport'][$r] : $_config['hostport'][0],
            'database' => isset($_config['database'][$r]) ? $_config['database'][$r] : $_config['database'][0],
            'dsn' => isset($_config['dsn'][$r]) ? $_config['dsn'][$r] : $_config['dsn'][0],
            'charset' => isset($_config['charset'][$r]) ? $_config['charset'][$r] : $_config['charset'][0],
        );
        return $this->connect($db_config, $r);
    }

    /**
     * Destructor
     * @access public
     */
    public function __destruct()
    {
        // freedInquire
        if ($this->PDOStatement) {
            $this->free();
        }
        // shut downconnection
        $this->close();
    }
}