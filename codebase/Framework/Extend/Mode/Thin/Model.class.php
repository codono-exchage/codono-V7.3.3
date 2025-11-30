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

/**
 * ThinkPHP Simple modeModelModel Class
 * Only supports nativeSQLoperating stand bymanyDatabase ConnectivityAnd switching
 */
class Model
{
    // Current dataStorehouseoperatingObjects
    protected $db = null;
    // data sheetPrefix
    protected $tablePrefix = '';
    // Model Name
    protected $name = '';
    // databasename
    protected $dbName = '';
    // data Sheet Name(Does not containTable Prefix)
    protected $tableName = '';
    // actualdata Sheet Name(containTable Prefix)
    protected $trueTableName = '';
    // most近Error Messages
    protected $error = '';

    /**
     * Architecturefunction
     * ObtainDBExamples of object classes
     * @param string $name Model Name
     * @access public
     */
    public function __construct($name = '')
    {
        // modelinitialization
        $this->_initialize();
        // ObtainModel Name
        if (!empty($name)) {
            $this->name = $name;
        } elseif (empty($this->name)) {
            $this->name = $this->getModelName();
        }
        // databaseinitializationoperating
        // ObtaindatabaseoperatingObjects
        // currentModel independentofDatabase connection information
        $this->db(0, empty($this->connection) ? $connection : $this->connection);
        // Set upTable Prefix
        $this->tablePrefix = $this->tablePrefix ? $this->tablePrefix : C('DB_PREFIX');
    }

    // Callbackmethod initializationmodel
    protected function _initialize()
    {
    }

    /**
     * SQLInquire
     * @access public
     * @param mixed $sql SQLinstruction
     * @return array
     */
    public function query($sql)
    {
        if (is_array($sql)) {
            return $this->patchQuery($sql);
        }
        if (!empty($sql)) {
            if (strpos($sql, '__TABLE__')) {
                $sql = str_replace('__TABLE__', $this->getTableName(), $sql);
            }
            return $this->db->query($sql);
        } else {
            return false;
        }
    }

    /**
     * carried outSQLStatement
     * @access public
     * @param string $sql SQLinstruction
     * @return false | integer
     */
    public function execute($sql = '')
    {
        if (!empty($sql)) {
            if (strpos($sql, '__TABLE__')) {
                $sql = str_replace('__TABLE__', $this->getTableName(), $sql);
            }
            $result = $this->db->execute($sql);
            return $result;
        } else {
            return false;
        }
    }

    /**
     * getcurrentofData Objectsname
     * @access public
     * @return string
     */
    public function getModelName()
    {
        if (empty($this->name)) {
            $this->name = substr(get_class($this), 0, -5);
        }
        return $this->name;
    }

    /**
     * Get the completedata Sheet Name
     * @access public
     * @return string
     */
    public function getTableName()
    {
        if (empty($this->trueTableName)) {
            $tableName = !empty($this->tablePrefix) ? $this->tablePrefix : '';
            if (!empty($this->tableName)) {
                $tableName .= $this->tableName;
            } else {
                $tableName .= parse_name($this->name);
            }
            $this->trueTableName = strtolower($tableName);
        }
        return (!empty($this->dbName) ? $this->dbName . '.' : '') . $this->trueTableName;
    }

    /**
     * Start affairs
     * @access public
     * @return void
     */
    public function startTrans()
    {
        $this->commit();
        $this->db->startTrans();
        return;
    }

    /**
     * Commit the transaction
     * @access public
     * @return boolean
     */
    public function commit()
    {
        return $this->db->commit();
    }

    /**
     * Transaction rollback
     * @access public
     * @return boolean
     */
    public function rollback()
    {
        return $this->db->rollback();
    }

    /**
     * SwitchcurrentofDatabase Connectivity
     * @access public
     * @param integer $linkNum connectionNo.
     * @param mixed $config Database connection information
     * @return Model
     */
    public function db($linkNum = '', $config = '')
    {
        if ('' === $linkNum && $this->db) {
            return $this->db;
        }
        static $_linkNum = array();
        static $_db = array();
        if (!isset($_db[$linkNum]) || (isset($_db[$linkNum]) && $_linkNum[$linkNum] != $config)) {
            // CreatenewofExamples
            if (!empty($config) && is_string($config) && false === strpos($config, '/')) { // stand byReadConfiguration parameters
                $config = C($config);
            }
            $_db[$linkNum] = Db::getInstance($config);
        } elseif (NULL === $config) {
            $_db[$linkNum]->close(); // Close the databaseconnection
            unset($_db[$linkNum]);
            return;
        }
        // recordingConnection Information
        $_linkNum[$linkNum] = $config;
        // SwitchDatabase Connectivity
        $this->db = $_db[$linkNum];
        return $this;
    }
}