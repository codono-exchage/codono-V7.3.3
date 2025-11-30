<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2009 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: support@codono.com
// +----------------------------------------------------------------------

/**
 * ThinkPHP AMFmodeModelModel Class
 * Only supportsCURDAnd coherent operation And common queries Remove the callback interface
 */
class Model
{
    // Current dataStorehouseoperatingObjects
    protected $db = null;
    // Primary keyname
    protected $pk = 'id';
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
    // datainformation
    protected $data = array();
    // Query Expressionparameter
    protected $options = array();
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
        import("Db");
        // ObtaindatabaseoperatingObjects
        $this->db = Db::getInstance(empty($this->connection) ? '' : $this->connection);
        // Set upTable Prefix
        $this->tablePrefix = $this->tablePrefix ? $this->tablePrefix : C('DB_PREFIX');
        // FieldDetect
        if (!empty($this->name)) $this->_checkTableInfo();
    }

    /**
     * automaticDetectdata sheetinformation
     * @access protected
     * @return void
     */
    protected function _checkTableInfo()
    {
        // if notModelclass automaticrecordingdata sheetinformation
        // Only the firstcarried outrecording
        if (empty($this->fields)) {
            // in casedata sheetFieldNodefinitionthenautomaticObtain
            if (C('DB_FIELDS_CACHE')) {
                $this->fields = F('_fields/' . $this->name);
                if (!$this->fields) $this->flush();
            } else {
                // Every timeReaddata sheetinformation
                $this->flush();
            }
        }
    }

    /**
     * ObtainFieldinformationandCache
     * @access public
     * @return void
     */
    public function flush()
    {
        // Cachedoes not existthenQuery datatableinformation
        $fields = $this->db->getFields($this->getTableName());
        $this->fields = array_keys($fields);
        $this->fields['_autoinc'] = false;
        foreach ($fields as $key => $val) {
            // recordingFieldTypes of
            $type[$key] = $val['type'];
            if ($val['primary']) {
                $this->fields['_pk'] = $key;
                if ($val['autoinc']) $this->fields['_autoinc'] = true;
            }
        }
        // recordingFieldTypes ofinformation
        if (C('DB_FIELDTYPE_CHECK')) $this->fields['_type'] = $type;

        // 2008-3-7 Increase the cache control switch
        if (C('DB_FIELDS_CACHE'))
            // permanentCachedata sheetinformation
            F('_fields/' . $this->name, $this->fields);
    }

    // Callbackmethod initializationmodel
    protected function _initialize()
    {
    }

    /**
     * Use__callWay to achieveSome specialofModelmethod (Magic method)
     * @access public
     * @param string $method Method name
     * @param array $args Call parameters
     * @return mixed
     */
    public function __call($method, $args)
    {
        if (in_array(strtolower($method), array('field', 'table', 'where', 'order', 'limit', 'page', 'having', 'group', 'lock', 'distinct'), true)) {
            // Coherent operationofachieve
            $this->options[strtolower($method)] = $args[0];
            return $this;
        } elseif (in_array(strtolower($method), array('count', 'sum', 'min', 'max', 'avg'), true)) {
            // statisticsInquireofachieve
            $field = isset($args[0]) ? $args[0] : '*';
            return $this->getField(strtoupper($method) . '(' . $field . ') AS tp_' . $method);
        } elseif (strtolower(substr($method, 0, 5)) == 'getby') {
            // according toAFieldObtainrecording
            $field = parse_name(substr($method, 5));
            $options['where'] = $field . '=\'' . $args[0] . '\'';
            return $this->find($options);
        } else {
            throw_exception(__CLASS__ . ':' . $method . L('_METHOD_NOT_EXIST_'));
            return;
        }
    }

    /**
     * Value of the data object (Magic method)
     * @access public
     * @param string $name name
     * @param mixed $value value
     * @return void
     */
    public function __set($name, $value)
    {
        // Set upData ObjectsAttributes
        $this->data[$name] = $value;
    }

    /**
     * Gets the value of the data object (Magic method)
     * @access public
     * @param string $name name
     * @return mixed
     */
    public function __get($name)
    {
        return isset($this->data[$name]) ? $this->data[$name] : null;
    }

    /**
     * New data
     * @access public
     * @param mixed $data data
     * @param array $options expression
     * @return mixed
     */
    public function add($data = '', $options = array())
    {
        if (empty($data)) {
            // Notransferdata,Get the currentData ObjectsThe value
            if (!empty($this->data)) {
                $data = $this->data;
            } else {
                $this->error = L('_DATA_TYPE_INVALID_');
                return false;
            }
        }
        // Analysis of expression
        $options = $this->_parseOptions($options);
        // data inputTodatabase
        $result = $this->db->insert($data, $options);
        $insertId = $this->getLastInsID();
        if ($insertId) {
            return $insertId;
        }
        //successRearreturninsertID
        return $result;
    }

    /**
     * save data
     * @access public
     * @param mixed $data data
     * @param array $options expression
     * @return boolean
     */
    public function save($data = '', $options = array())
    {
        if (empty($data)) {
            // Notransferdata,Get the currentData ObjectsThe value
            if (!empty($this->data)) {
                $data = $this->data;
            } else {
                $this->error = L('_DATA_TYPE_INVALID_');
                return false;
            }
        }
        // Analysis of expression
        $options = $this->_parseOptions($options);
        if (!isset($options['where'])) {
            // in caseexistPrimary keydata thenautomaticAs aUpdatecondition
            if (isset($data[$this->getPk()])) {
                $pk = $this->getPk();
                $options['where'] = $pk . '=\'' . $data[$pk] . '\'';
                $pkValue = $data[$pk];
                unset($data[$pk]);
            } else {
                // in caseNoany Update condition then not carrying out
                $this->error = L('_OPERATION_WRONG_');
                return false;
            }
        }
        return $this->db->update($data, $options);
    }

    /**
     * delete data
     * @access public
     * @param mixed $options expression
     * @return mixed
     */
    public function delete($options = array())
    {
        if (empty($options) && empty($this->options['where'])) {
            // in casedeleteconditionforair thendeleteCurrent dataObjectsThecorrespondingrecording
            if (!empty($this->data) && isset($this->data[$this->getPk()]))
                return $this->delete($this->data[$this->getPk()]);
            else
                return false;
        }
        if (is_numeric($options) || is_string($options)) {
            // according toPrimary keyDelete Record
            $pk = $this->getPk();
            $where = $pk . '=\'' . $options . '\'';
            $pkValue = $options;
            $options = array();
            $options['where'] = $where;
        }
        // Analysis of expression
        $options = $this->_parseOptions($options);
        return $this->db->delete($options);
    }

    /**
     * Query data sets
     * @access public
     * @param array $options Expression argument
     * @return mixed
     */
    public function select($options = array())
    {
        // Analysis of expression
        $options = $this->_parseOptions($options);
        $resultSet = $this->db->select($options);
        if (empty($resultSet)) { // search resultforair
            return false;
        }
        return $resultSet;
    }

    /**
     * Query data
     * @access public
     * @param mixed $options Expression argument
     * @return mixed
     */
    public function find($options = array())
    {
        if (is_numeric($options) || is_string($options)) {
            $where = $this->getPk() . '=\'' . $options . '\'';
            $options = array();
            $options['where'] = $where;
        }
        // alwaysSeek一Records
        $options['limit'] = 1;
        // Analysis of expression
        $options = $this->_parseOptions($options);
        $resultSet = $this->db->select($options);
        if (empty($resultSet)) {// search resultforair
            return false;
        }
        $this->data = $resultSet[0];
        return $this->data;
    }

    /**
     * Analysis of expression
     * @access private
     * @param array $options Expression argument
     * @return array
     */
    private function _parseOptions($options)
    {
        if (is_array($options))
            $options = array_merge($this->options, $options);
        // InquireAfter theClearsqlexpressionAssembly To avoid affecting the nextInquire
        $this->options = array();
        if (!isset($options['table']))
            // automaticObtainTable name
            $options['table'] = $this->getTableName();
        return $options;
    }

    /**
     * Create data objects But not saved to the database
     * @access public
     * @param mixed $data Creating a Data
     * @param string $type status
     * @return mixed
     */
    public function create($data = '', $type = '')
    {
        // in caseNoBy valuedefaulttakePOSTdata
        if (empty($data)) {
            $data = $_POST;
        } elseif (is_object($data)) {
            $data = get_object_vars($data);
        } elseif (!is_array($data)) {
            $this->error = L('_DATA_TYPE_INVALID_');
            return false;
        }
        // FormData Objects
        $vo = array();
        foreach ($this->fields as $key => $name) {
            if (substr($key, 0, 1) == '_') continue;
            $val = isset($data[$name]) ? $data[$name] : null;
            //Guarantee assignmenteffective
            if (!is_null($val)) {
                $vo[$name] = (MAGIC_QUOTES_GPC && is_string($val)) ? stripslashes($val) : $val;
                if (C('DB_FIELDTYPE_CHECK')) {
                    // FieldTypes ofan examination
                    $fieldType = strtolower($this->fields['_type'][$name]);
                    if (false !== strpos($fieldType, 'int')) {
                        $vo[$name] = intval($vo[$name]);
                    } elseif (false !== strpos($fieldType, 'float') || false !== strpos($fieldType, 'double')) {
                        $vo[$name] = floatval($vo[$name]);
                    }
                }
            }
        }
        // 赋valueCurrent dataObjects
        $this->data = $vo;
        // returncreateofdataFor othertransfer
        return $vo;
    }

    /**
     * SQLInquire
     * @access public
     * @param string $sql SQLinstruction
     * @return array
     */
    public function query($sql)
    {
        if (!empty($sql)) {
            if (strpos($sql, '__TABLE__'))
                $sql = str_replace('__TABLE__', $this->getTableName(), $sql);
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
            if (strpos($sql, '__TABLE__'))
                $sql = str_replace('__TABLE__', $this->getTableName(), $sql);
            return $this->db->execute($sql);
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
            if (!empty($this->dbName)) {
                $tableName = $this->dbName . '.' . $tableName;
            }
            $this->trueTableName = strtolower($tableName);
        }
        return $this->trueTableName;
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
     * Gets the name of the primary key
     * @access public
     * @return string
     */
    public function getPk()
    {
        return isset($this->fields['_pk']) ? $this->fields['_pk'] : $this->pk;
    }

    /**
     * Returns the last executionsqlStatement
     * @access public
     * @return string
     */
    public function getLastSql()
    {
        return $this->db->getLastSql();
    }

    /**
     * Returns the last insertedID
     * @access public
     * @return string
     */
    public function getLastInsID()
    {
        return $this->db->getLastInsID();
    }
}

;