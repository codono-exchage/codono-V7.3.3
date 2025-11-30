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

define('CLIENT_MULTI_RESULTS', 131072);

/**
 * ThinkPHP Simple modedatabasemiddle layerImplementation class
 * Only supportsmysql
 */
class Db
{

    static private $_instance = null;
    // whetherautomaticFree result
    protected $autoFree = false;
    // whetherdisplaydebugginginformation in caseWill enableJournalfilerecordingsqlStatement
    public $debug = false;
    // use or notpermanentconnection
    protected $pconnect = false;
    // currentSQLinstruction
    protected $queryStr = '';
    // At lastinsertID
    protected $lastInsID = null;
    // returnorinfluencesrecordingnumber
    protected $numRows = 0;
    // returnFieldnumber
    protected $numCols = 0;
    // Affairsinstructionnumber
    protected $transTimes = 0;
    // Error Messages
    protected $error = '';
    // currentconnectionID
    protected $linkID = null;
    // currentInquireID
    protected $queryID = null;
    // whetheralreadyconnectiondatabase
    protected $connected = false;
    // Database ConnectivityparameterConfiguration
    protected $config = '';
    // databaseexpression
    protected $comparison = array('eq' => '=', 'neq' => '!=', 'gt' => '>', 'egt' => '>=', 'lt' => '<', 'elt' => '<=', 'notlike' => 'NOT LIKE', 'like' => 'LIKE');
    // Query Expression
    protected $selectSql = 'SELECT%DISTINCT% %FIELDS% FROM %TABLE%%JOIN%%WHERE%%GROUP%%HAVING%%ORDER%%LIMIT%';

    /**
     * Architecturefunction
     * @access public
     * @param array $config Database configuration array
     */
    public function __construct($config = '')
    {
        if (!extension_loaded('mysql')) {
            throw_exception(L('_NOT_SUPPERT_') . ':mysql');
        }
        $this->config = $this->parseConfig($config);
    }

    /**
     * Database connection method
     * @access public
     * @throws ThinkExecption
     */
    public function connect()
    {
        if (!$this->connected) {
            $config = $this->config;
            // deal withWithoutportnumberofsocketconnection情况
            $host = $config['hostname'] . ($config['hostport'] ? ":{$config['hostport']}" : '');
            if ($this->pconnect) {
                $this->linkID = mysql_pconnect($host, $config['username'], $config['password'], CLIENT_MULTI_RESULTS);
            } else {
                $this->linkID = mysql_connect($host, $config['username'], $config['password'], true, CLIENT_MULTI_RESULTS);
            }
            if (!$this->linkID || (!empty($config['database']) && !mysql_select_db($config['database'], $this->linkID))) {
                throw_exception(mysql_error());
            }
            $dbVersion = mysql_get_server_info($this->linkID);
            if ($dbVersion >= "4.1") {
                //useUTF8Access database needmysql 4.1.0With上stand by
                mysql_query("SET NAMES '" . C('DB_CHARSET') . "'", $this->linkID);
            }
            //Set up sql_model
            if ($dbVersion > '5.0.1') {
                mysql_query("SET sql_mode=''", $this->linkID);
            }
            // markconnectionsuccess
            $this->connected = true;
            // LogoutDatabase ConnectivityConfigurationinformation
            unset($this->config);
        }
    }

    /**
     * Free result
     * @access public
     */
    public function free()
    {
        mysql_free_result($this->queryID);
        $this->queryID = 0;
    }

    /**
     * Execute the query Mainly for SELECT, SHOW And other instructions
     * Return data sets
     * @access public
     * @param string $str sqlinstruction
     * @return mixed
     * @throws ThinkExecption
     */
    public function query($str = '')
    {
        $this->connect();
        if (!$this->linkID) return false;
        if ($str != '') $this->queryStr = $str;
        //Previous releaseofsearch result
        if ($this->queryID) {
            $this->free();
        }
        N('db_query', 1);
        // recordingStartcarried outtime
        G('queryStartTime');
        $this->queryID = mysql_query($this->queryStr, $this->linkID);
        $this->debug();
        if (!$this->queryID) {
            if ($this->debug)
                throw_exception($this->error());
            else
                return false;
        } else {
            $this->numRows = mysql_num_rows($this->queryID);
            return $this->getAll();
        }
    }

    /**
     * Execute the statement against INSERT, UPDATE as well asDELETE
     * @access public
     * @param string $str sqlinstruction
     * @return integer
     * @throws ThinkExecption
     */
    public function execute($str = '')
    {
        $this->connect();
        if (!$this->linkID) return false;
        if ($str != '') $this->queryStr = $str;
        //Previous releaseofsearch result
        if ($this->queryID) {
            $this->free();
        }
        N('db_write', 1);
        // recordingStartcarried outtime
        G('queryStartTime');
        $result = mysql_query($this->queryStr, $this->linkID);
        $this->debug();
        if (false === $result) {
            if ($this->debug)
                throw_exception($this->error());
            else
                return false;
        } else {
            $this->numRows = mysql_affected_rows($this->linkID);
            $this->lastInsID = mysql_insert_id($this->linkID);
            return $this->numRows;
        }
    }

    /**
     * Start affairs
     * @access public
     * @return void
     * @throws ThinkExecption
     */
    public function startTrans()
    {
        $this->connect(true);
        if (!$this->linkID) return false;
        //datarollback stand by
        if ($this->transTimes == 0) {
            mysql_query('START TRANSACTION', $this->linkID);
        }
        $this->transTimes++;
        return;
    }

    /**
     * Fornon-Auto Commitstatusthe followingofInquiresubmit
     * @access public
     * @return boolen
     * @throws ThinkExecption
     */
    public function commit()
    {
        if ($this->transTimes > 0) {
            $result = mysql_query('COMMIT', $this->linkID);
            $this->transTimes = 0;
            if (!$result) {
                throw_exception($this->error());
                return false;
            }
        }
        return true;
    }

    /**
     * Transaction rollback
     * @access public
     * @return boolen
     * @throws ThinkExecption
     */
    public function rollback()
    {
        if ($this->transTimes > 0) {
            $result = mysql_query('ROLLBACK', $this->linkID);
            $this->transTimes = 0;
            if (!$result) {
                throw_exception($this->error());
                return false;
            }
        }
        return true;
    }

    /**
     * obtainallofQuery data
     * @access public
     * @return array
     * @throws ThinkExecption
     */
    public function getAll()
    {
        if (!$this->queryID) {
            throw_exception($this->error());
            return false;
        }
        //Return data sets
        $result = array();
        if ($this->numRows > 0) {
            while ($row = mysql_fetch_assoc($this->queryID)) {
                $result[] = $row;
            }
            mysql_data_seek($this->queryID, 0);
        }
        return $result;
    }

    /**
     * Close the database
     * @access public
     * @throws ThinkExecption
     */
    public function close()
    {
        if (!empty($this->queryID))
            mysql_free_result($this->queryID);
        if ($this->linkID && !mysql_close($this->linkID)) {
            throw_exception($this->error());
        }
        $this->linkID = 0;
    }

    /**
     * Database Error Messages
     * And displays the currentSQLStatement
     * @access public
     * @return string
     */
    public function error()
    {
        $this->error = mysql_error($this->linkID);
        if ($this->queryStr != '') {
            $this->error .= "\n [ SQLStatement ] : " . $this->queryStr;
        }
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
        return mysql_escape_string($str);
    }

    /**
     * Destructor
     * @access public
     */
    public function __destruct()
    {
        // shut downconnection
        $this->close();
    }

    /**
     * Made database class instance
     * @static
     * @access public
     * @return mixed Returns the database driver class
     */
    public static function getInstance($db_config = '')
    {
        if (self::$_instance == null) {
            self::$_instance = new Db($db_config);
        }
        return self::$_instance;
    }

    /**
     * analysisDatabase configuration information,stand byArraywithDSN
     * @access private
     * @param mixed $db_config Database configuration information
     * @return string
     */
    private function parseConfig($db_config = '')
    {
        if (!empty($db_config) && is_string($db_config)) {
            // in caseDSNStringIs performedResolve
            $db_config = $this->parseDSN($db_config);
        } else if (empty($db_config)) {
            // in caseConfigurationforair,Read Configuration file Set up
            $db_config = array(
                'dbms' => C('DB_TYPE'),
                'username' => C('DB_USER'),
                'password' => C('DB_PWD'),
                'hostname' => C('DB_HOST'),
                'hostport' => C('DB_PORT'),
                'database' => C('DB_NAME'),
                'dsn' => C('DB_DSN'),
                'params' => C('DB_PARAMS'),
            );
        }
        return $db_config;
    }

    /**
     * DSNResolve
     * format: mysql://username:passwd@localhost:3306/DbName
     * @static
     * @access public
     * @param string $dsnStr
     * @return array
     */
    public function parseDSN($dsnStr)
    {
        if (empty($dsnStr)) {
            return false;
        }
        $info = parse_url($dsnStr);
        if ($info['scheme']) {
            $dsn = array(
                'dbms' => $info['scheme'],
                'username' => isset($info['user']) ? $info['user'] : '',
                'password' => isset($info['pass']) ? $info['pass'] : '',
                'hostname' => isset($info['host']) ? $info['host'] : '',
                'hostport' => isset($info['port']) ? $info['port'] : '',
                'database' => isset($info['path']) ? substr($info['path'], 1) : ''
            );
        } else {
            preg_match('/^(.*?)\:\/\/(.*?)\:(.*?)\@(.*?)\:([0-9]{1, 6})\/(.*?)$/', trim($dsnStr), $matches);
            $dsn = array(
                'dbms' => $matches[1],
                'username' => $matches[2],
                'password' => $matches[3],
                'hostname' => $matches[4],
                'hostport' => $matches[5],
                'database' => $matches[6]
            );
        }
        return $dsn;
    }

    /**
     * Database Debugging The current recordSQL
     * @access protected
     */
    protected function debug()
    {
        // recordingoperatingEndtime
        if ($this->debug) {
            G('queryEndTime');
            Log::record($this->queryStr . " [ RunTime:" . G('queryStartTime', 'queryEndTime', 6) . "s ]", Log::SQL);
        }
    }

    /**
     * ObtainLastInquireofsqlStatement
     * @access public
     * @return string
     */
    public function getLastSql()
    {
        return $this->queryStr;
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
}