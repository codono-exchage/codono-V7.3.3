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
 * OracleDatabase-driven
 * @category   Extend
 * @package  Extend
 * @subpackage  Driver.Db
 * @author    ZhangXuehun <zhangxuehun@sohu.com>
 */
class DbOracle extends Db
{

    private $mode = OCI_COMMIT_ON_SUCCESS;
    private $table = '';
    protected $selectSql = 'SELECT * FROM (SELECT thinkphp.*, rownum AS numrow FROM (SELECT  %DISTINCT% %FIELD% FROM %TABLE%%JOIN%%WHERE%%GROUP%%HAVING%%ORDER%) thinkphp ) %LIMIT%%COMMENT%';

    /**
     * Architecturefunction ReadDatabase configuration information
     * @access public
     * @param array $config Database configuration array
     */
    public function __construct($config = '')
    {
        putenv("NLS_LANG=AMERICAN_AMERICA.UTF8");
        if (!extension_loaded('oci8')) {
            throw_exception(L('_NOT_SUPPERT_') . 'oracle');
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
            $conn = $pconnect ? 'oci_pconnect' : 'oci_new_connect';
            $this->linkID[$linkNum] = $conn($config['username'], $config['password'], $config['database']);//modify by wyfeng at 2008.12.19

            if (!$this->linkID[$linkNum]) {
                $this->error(false);
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
        oci_free_statement($this->queryID);
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
        //Change Transactionmode
        $this->mode = OCI_COMMIT_ON_SUCCESS;
        //Previous releaseofsearch result
        if ($this->queryID) $this->free();
        N('db_query', 1);
        // recordingStartcarried outtime
        G('queryStartTime');
        $this->queryID = oci_parse($this->_linkID, $str);
        $this->debug();
        if (false === oci_execute($this->queryID, $this->mode)) {
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
     * @return integer
     */
    public function execute($str)
    {
        $this->initConnect(true);
        if (!$this->_linkID) return false;
        $this->queryStr = $str;
        // Add Operation Analyzing
        $flag = false;
        if (preg_match("/^\s*(INSERT\s+INTO)\s+(\w+)\s+/i", $this->queryStr, $match)) {
            $this->table = C("DB_SEQUENCE_PREFIX") . str_ireplace(C("DB_PREFIX"), "", $match[2]);
            $flag = (boolean)$this->query("SELECT * FROM user_sequences WHERE sequence_name='" . strtoupper($this->table) . "'");
        }//modify by wyfeng at 2009.08.28

        //Change Transactionmode
        $this->mode = OCI_COMMIT_ON_SUCCESS;
        //Previous releaseofsearch result
        if ($this->queryID) $this->free();
        N('db_write', 1);
        // recordingStartcarried outtime
        G('queryStartTime');
        $stmt = oci_parse($this->_linkID, $str);
        $this->debug();
        if (false === oci_execute($stmt)) {
            $this->error();
            return false;
        } else {
            $this->numRows = oci_num_rows($stmt);
            $this->lastInsID = $flag ? $this->insertLastId() : 0;//modify by wyfeng at 2009.08.28
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
            $this->mode = OCI_DEFAULT;
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
            $result = oci_commit($this->_linkID);
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
            $result = oci_rollback($this->_linkID);
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
        $this->numRows = oci_fetch_all($this->queryID, $result, 0, -1, OCI_FETCHSTATEMENT_BY_ROW);
        //add by wyfeng at 2008-12-23 ForcesField NameConverted tolower case,WithCoordinateModelThe class functioncountWait
        if (C("DB_CASE_LOWER")) {
            foreach ($result as $k => $v) {
                $result[$k] = array_change_key_case($result[$k], CASE_LOWER);
            }
        }
        return $result;
    }


    /**
     * Obtaindata sheetofFieldinformation
     * @access public
     */
    public function getFields($tableName)
    {
        $result = $this->query("select a.column_name,data_type,decode(nullable,'Y',0,1) notnull,data_default,decode(a.column_name,b.column_name,1,0) pk "
            . "from user_tab_columns a,(select column_name from user_constraints c,user_cons_columns col "
            . "where c.constraint_name=col.constraint_name and c.constraint_type='P'and c.table_name='" . strtoupper($tableName)
            . "') b where table_name='" . strtoupper($tableName) . "' and a.column_name=b.column_name(+)");
        $info = array();
        if ($result) {
            foreach ($result as $key => $val) {
                $info[strtolower($val['column_name'])] = array(
                    'name' => strtolower($val['column_name']),
                    'type' => strtolower($val['data_type']),
                    'notnull' => $val['notnull'],
                    'default' => $val['data_default'],
                    'primary' => $val['pk'],
                    'autoinc' => $val['pk'],
                );
            }
        }
        return $info;
    }

    /**
     * ObtainDatabasetableinformation(temporarilyachieveObtainusertableinformation)
     * @access public
     */
    public function getTables($dbName = '')
    {
        $result = $this->query("select table_name from user_tables");
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
            oci_close($this->_linkID);
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
        if ($result) {
            $error = oci_error($this->queryID);
        } elseif (!$this->_linkID) {
            $error = oci_error();
        } else {
            $error = oci_error($this->_linkID);
        }
        if ('' != $this->queryStr) {
            $error['message'] .= "\n [ SQLStatement ] : " . $this->queryStr;
        }
        $result ? trace($error['message'], '', 'ERR') : throw_exception($error['message'], '', $error['code']);
        $this->error = $error['message'];
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
        return str_ireplace("'", "''", $str);
    }

    /**
     * Get the last insertedid ,Only for the sequence+Triggers combined generationIDThe way
     * inconfig.phpinDesignation
     * 'DB_TRIGGER_PREFIX'    =>    'tr_',
     * 'DB_SEQUENCE_PREFIX' =>    'ts_',
     * eg:table tb_user
     * relativelytb_userThe sequence is:
     * -- Create sequence
     * create sequence TS_USER
     * minvalue 1
     * maxvalue 999999999999999999999999999
     * start with 1
     * increment by 1
     * nocache;
     * relativelytb_user,ts_userThe trigger for:
     * create or replace trigger TR_USER
     * before insert on "TB_USER"
     * for each row
     * begin
     * select "TS_USER".nextval into :NEW.ID from dual;
     * end;
     * @access public
     * @return integer
     */
    public function insertLastId()
    {
        if (empty($this->table)) {
            return 0;
        }
        $sequenceName = $this->table;
        $vo = $this->query("SELECT {$sequenceName}.currval currval FROM dual");
        return $vo ? $vo[0]["currval"] : 0;
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
            if (count($limit) > 1)
                $limitStr = "(numrow>" . $limit[0] . ") AND (numrow<=" . ($limit[0] + $limit[1]) . ")";
            else
                $limitStr = "(numrow>0 AND numrow<=" . $limit[0] . ")";
        }
        return $limitStr ? ' WHERE ' . $limitStr : '';
    }
}