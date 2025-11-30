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
 * ThinkPHP ModelModel Class
 * AchievedORMwithActiveRecordsmode
 * @category   Think
 * @package  Think
 * @subpackage  Core
 * @author    liu21st <liu21st@gmail.com>
 */
class Model
{
    // operatingstatus
    const MODEL_INSERT = 1;      //  Insert data model
    const MODEL_UPDATE = 2;      //  Update model data
    const MODEL_BOTH = 3;      //  It contains the above two ways
    const MUST_VALIDATE = 1;// have toverification
    const EXISTS_VALIDATE = 0;// FormsexistFieldthenverification
    const VALUE_VALIDATE = 2;// Formsvaluenot nullthenverification
    // Extension of the current model used
    private $_extModel = null;
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
    //Database Configuration
    protected $connection = '';
    // data Sheet Name(Does not containTable Prefix)
    protected $tableName = '';
    // actualdata Sheet Name(containTable Prefix)
    protected $trueTableName = '';
    // most近Error Messages
    protected $error = '';
    // Fieldinformation
    protected $fields = array();
    // datainformation
    protected $data = array();
    // Query Expressionparameter
    protected $options = array();
    protected $_validate = array();  // automaticverificationdefinition
    protected $_auto = array();  // automaticcarry outdefinition
    protected $_map = array();  // FieldMappingdefinition
    protected $_scope = array();  // Named rangedefinition
    // whetherautomaticDetectdata sheetFieldinformation
    protected $autoCheckFields = true;
    // whetherBatchdeal withverification
    protected $patchValidate = false;
    // chainoperatingmethodList
    protected $methods = array('table', 'order', 'alias', 'having', 'group', 'lock', 'distinct', 'auto', 'filter', 'validate', 'result', 'bind', 'token');

    /**
     * Architecturefunction
     * ObtainDBExamples of object classes Field inspection
     * @access public
     * @param string $name Model Name
     * @param string $tablePrefix Table Prefix
     * @param mixed $connection Database connection information
     */
    public function __construct($name = '', $tablePrefix = '', $connection = '')
    {
        // modelinitialization
        $this->_initialize();
        // ObtainModel Name
        if (!empty($name)) {
            if (strpos($name, '.')) { // stand by DatabaseName.model Nameof definition
                list($this->dbName, $this->name) = explode('.', $name);
            } else {
                $this->name = $name;
            }
        } elseif (empty($this->name)) {
            $this->name = $this->getModelName();
        }
        // Set upTable Prefix
        if (is_null($tablePrefix)) {// PrefixforNullShowNoPrefix
            $this->tablePrefix = '';
        } elseif ('' != $tablePrefix) {
            $this->tablePrefix = $tablePrefix;
        } else {
            $this->tablePrefix = $this->tablePrefix ? $this->tablePrefix : C('DB_PREFIX');
        }

        // databaseinitializationoperating
        // ObtaindatabaseoperatingObjects
        // currentModel independentofDatabase connection information
        $this->db(0, empty($this->connection) ? $connection : $this->connection);
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
                $db = $this->dbName ? $this->dbName : C('DB_NAME');
                $fields = F('_fields/' . strtolower($db . '.' . $this->name));
                if ($fields) {
                    $version = C('DB_FIELD_VERSION');
                    if (empty($version) || $fields['_version'] == $version) {
                        $this->fields = $fields;
                        return;
                    }
                }
            }
            // Every timeReaddata sheetinformation
            $this->flush();
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
        $this->db->setModel($this->name);
        $fields = $this->db->getFields($this->getTableName());
        if (!$fields) { // UnableObtainFieldinformation
            return false;
        }
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
        $this->fields['_type'] = $type;
        if (C('DB_FIELD_VERSION')) $this->fields['_version'] = C('DB_FIELD_VERSION');

        // 2008-3-7 Increase the cache control switch
        if (C('DB_FIELDS_CACHE')) {
            // permanentCachedata sheetinformation
            $db = $this->dbName ? $this->dbName : C('DB_NAME');
            F('_fields/' . strtolower($db . '.' . $this->name), $this->fields);
        }
    }

    /**
     * Extended dynamic switching model
     * @access public
     * @param string $type Model Type Name
     * @param mixed $vars To passSpreadmodelofAttributesvariable
     * @return Model
     */
    public function switchModel($type, $vars = array())
    {
        $class = ucwords(strtolower($type)) . 'Model';
        if (!class_exists($class))
            throw_exception($class . L('_MODEL_NOT_EXIST_'));
        // Examples of the extended model
        $this->_extModel = new $class($this->name);
        if (!empty($vars)) {
            // AfferentcurrentmodelofAttributesToSpreadmodel
            foreach ($vars as $var)
                $this->_extModel->setProperty($var, $this->$var);
        }
        return $this->_extModel;
    }

    /**
     * Value of the data object
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
     * Gets the value of the data object
     * @access public
     * @param string $name name
     * @return mixed
     */
    public function __get($name)
    {
        return isset($this->data[$name]) ? $this->data[$name] : null;
    }

    /**
     * DetectData ObjectsThe value
     * @access public
     * @param string $name name
     * @return boolean
     */
    public function __isset($name)
    {
        return isset($this->data[$name]);
    }

    /**
     * The destruction of the value of the data object
     * @access public
     * @param string $name name
     * @return void
     */
    public function __unset($name)
    {
        unset($this->data[$name]);
    }

    /**
     * Use__callWay to achieveSome specialofModelmethod
     * @access public
     * @param string $method Method name
     * @param array $args Call parameters
     * @return mixed
     */
    public function __call($method, $args)
    {
        if (in_array(strtolower($method), $this->methods, true)) {
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
            $where[$field] = $args[0];
            return $this->where($where)->find();
        } elseif (strtolower(substr($method, 0, 10)) == 'getfieldby') {
            // according toAFieldObtainrecordingSome value
            $name = parse_name(substr($method, 10));
            $where[$name] = $args[0];
            return $this->where($where)->getField($args[1]);
        } elseif (isset($this->_scope[$method])) {// Named rangeofalonetransferstand by
            return $this->scope($method, $args[0]);
        } else {
            throw_exception(__CLASS__ . ':' . $method . L('_METHOD_NOT_EXIST_'));
            return;
        }
    }

    // Callbackmethod initializationmodel
    protected function _initialize()
    {
    }

    /**
     * CorrectStorageToDatabasedataEnterRowdeal with
     * @access protected
     * @param mixed $data Data to be operated
     * @return boolean
     */
    protected function _facade($data)
    {
        // an examinationnon-dataField
        if (!empty($this->fields)) {
            foreach ($data as $key => $val) {
                if (!in_array($key, $this->fields, true)) {
                    unset($data[$key]);
                } elseif (is_scalar($val)) {
                    // FieldTypes ofan examination
                    $this->_parseType($data, $key);
                }
            }
        }
        // Security filtering
        if (!empty($this->options['filter'])) {
            $data = array_map($this->options['filter'], $data);
            unset($this->options['filter']);
        }
        $this->_before_write($data);
        return $data;
    }

    // data inputbeforeofCallbackmethod includeNew andUpdate
    protected function _before_write(&$data)
    {
    }

    /**
     * New data
     * @access public
     * @param mixed $data data
     * @param array $options expression
     * @param boolean $replace whetherreplace
     * @return mixed
     */
    public function add($data = '', $options = array(), $replace = false)
    {
        if (empty($data)) {
            // Notransferdata,Get the currentData ObjectsThe value
            if (!empty($this->data)) {
                $data = $this->data;
                // Resetdata
                $this->data = array();
            } else {
                $this->error = L('_DATA_TYPE_INVALID_');
                return false;
            }
        }
        // Analysis of expression
        $options = $this->_parseOptions($options);
        // datadeal with
        $data = $this->_facade($data);
        if (false === $this->_before_insert($data, $options)) {
            return false;
        }
        // data inputTodatabase
        $result = $this->db->insert($data, $options, $replace);
        if (false !== $result) {
            $insertId = $this->getLastInsID();
            if ($insertId) {
                // IncrementPrimary keyreturninsertID
                $data[$this->getPk()] = $insertId;
                $this->_after_insert($data, $options);
                return $insertId;
            }
            $this->_after_insert($data, $options);
        }
        return $result;
    }

    // insertdatabeforeofCallbackmethod
    protected function _before_insert(&$data, $options)
    {
    }

    // insertsuccessAfterCallbackmethod
    protected function _after_insert($data, $options)
    {
    }

    public function addAll($dataList, $options = array(), $replace = false)
    {
        if (empty($dataList)) {
            $this->error = L('_DATA_TYPE_INVALID_');
            return false;
        }
        // Analysis of expression
        $options = $this->_parseOptions($options);
        // datadeal with
        foreach ($dataList as $key => $data) {
            $dataList[$key] = $this->_facade($data);
        }
        // data inputTodatabase
        $result = $this->db->insertAll($dataList, $options, $replace);
        if (false !== $result) {
            $insertId = $this->getLastInsID();
            if ($insertId) {
                return $insertId;
            }
        }
        return $result;
    }

    /**
     * bySelectAdd Record
     * @access public
     * @param string $fields wantinsertofdata sheetField Name
     * @param string $table wantinsertofdata Sheet Name
     * @param array $options expression
     * @return boolean
     */
    public function selectAdd($fields = '', $table = '', $options = array())
    {
        // Analysis of expression
        $options = $this->_parseOptions($options);
        // data inputTodatabase
        if (false === $result = $this->db->selectInsert($fields ? $fields : $options['field'], $table ? $table : $this->getTableName(), $options)) {
            // databaseinsertoperation failed
            $this->error = L('_OPERATION_WRONG_');
            return false;
        } else {
            // insertsuccess
            return $result;
        }
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
                // Resetdata
                $this->data = array();
            } else {
                $this->error = L('_DATA_TYPE_INVALID_');
                return false;
            }
        }
        // datadeal with
        $data = $this->_facade($data);
        // Analysis of expression
        $options = $this->_parseOptions($options);
        $pk = $this->getPk();
        if (!isset($options['where'])) {
            // in caseexistPrimary keydata thenautomaticAs aUpdatecondition
            if (isset($data[$pk])) {
                $where[$pk] = $data[$pk];
                $options['where'] = $where;
                unset($data[$pk]);
            } else {
                // in caseNoany Update condition then not carrying out
                $this->error = L('_OPERATION_WRONG_');
                return false;
            }
        }
        if (is_array($options['where']) && isset($options['where'][$pk])) {
            $pkValue = $options['where'][$pk];
        }
        if (false === $this->_before_update($data, $options)) {
            return false;
        }
        $result = $this->db->update($data, $options);
        if (false !== $result) {
            if (isset($pkValue)) $data[$pk] = $pkValue;
            $this->_after_update($data, $options);
        }
        return $result;
    }

    // UpdatedatabeforeofCallbackmethod
    protected function _before_update(&$data, $options)
    {
    }

    // UpdatesuccessAfterCallbackmethod
    protected function _after_update($data, $options)
    {
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
        $pk = $this->getPk();
        if (is_numeric($options) || is_string($options)) {
            // according toPrimary keyDelete Record
            if (strpos($options, ',')) {
                $where[$pk] = array('IN', $options);
            } else {
                $where[$pk] = $options;
            }
            $options = array();
            $options['where'] = $where;
        }
        // Analysis of expression
        $options = $this->_parseOptions($options);
        if (is_array($options['where']) && isset($options['where'][$pk])) {
            $pkValue = $options['where'][$pk];
        }
        $result = $this->db->delete($options);
        if (false !== $result) {
            $data = array();
            if (isset($pkValue)) $data[$pk] = $pkValue;
            $this->_after_delete($data, $options);
        }
        // returnDelete RecordThe number of
        return $result;
    }

    // deletesuccessAfterCallbackmethod
    protected function _after_delete($data, $options)
    {
    }

    /**
     * Query data sets
     * @access public
     * @param array $options Expression argument
     * @return mixed
     */
    public function select($options = array())
    {
        if (is_string($options) || is_numeric($options)) {
            // according toPrimary keyInquire
            $pk = $this->getPk();
            if (strpos($options, ',')) {
                $where[$pk] = array('IN', $options);
            } else {
                $where[$pk] = $options;
            }
            $options = array();
            $options['where'] = $where;
        } elseif (false === $options) { // ForSubqueries 不InquireOnly returnSQL
            $options = array();
            // Analysis of expression
            $options = $this->_parseOptions($options);
            return '( ' . $this->db->buildSelectSql($options) . ' )';
        }
        // Analysis of expression
        $options = $this->_parseOptions($options);
        $resultSet = $this->db->select($options);
        if (false === $resultSet) {
            return false;
        }
        if (empty($resultSet)) { // search resultforair
            return null;
        }
        $this->_after_select($resultSet, $options);
        return $resultSet;
    }

    // InquiresuccessAfterCallbackmethod
    protected function _after_select(&$resultSet, $options)
    {
    }

    /**
     * Generating a querySQL It can be used subqueries
     * @access public
     * @param array $options Expression argument
     * @return string
     */
    public function buildSql($options = array())
    {
        // Analysis of expression
        $options = $this->_parseOptions($options);
        return '( ' . $this->db->buildSelectSql($options) . ' )';
    }

    /**
     * Analysis of expression
     * @access protected
     * @param array $options Expression argument
     * @return array
     */
    protected function _parseOptions($options = array())
    {
        if (is_array($options))
            $options = array_merge($this->options, $options);
        // InquireAfter theClearsqlexpressionAssembly To avoid affecting the nextInquire
        $this->options = array();
        if (!isset($options['table'])) {
            // automaticObtainTable name
            $options['table'] = $this->getTableName();
            $fields = $this->fields;
        } else {
            // Designationdata sheet thenAgainObtainFieldList butnot supportTypes ofDetect
            $fields = $this->getDbFields();
        }

        if (!empty($options['alias'])) {
            $options['table'] .= ' ' . $options['alias'];
        }
        // recordingoperatingofModel Name
        $options['model'] = $this->name;

        // FieldTypes ofverification
        if (isset($options['where']) && is_array($options['where']) && !empty($fields) && !isset($options['join'])) {
            // CorrectArrayQuery conditionsEnterRowFieldTypes ofan examination
            foreach ($options['where'] as $key => $val) {
                $key = trim($key);
                if (in_array($key, $fields, true)) {
                    if (is_scalar($val)) {
                        $this->_parseType($options['where'], $key);
                    }
                } elseif (!is_numeric($key) && '_' != substr($key, 0, 1) && false === strpos($key, '.') && false === strpos($key, '(') && false === strpos($key, '|') && false === strpos($key, '&')) {
                    unset($options['where'][$key]);
                }
            }
        }

        // expressionfilter
        $this->_options_filter($options);
        return $options;
    }

    // expressionFilter callbackmethod
    protected function _options_filter(&$options)
    {
    }

    /**
     * Data type detection
     * @access protected
     * @param mixed $data data
     * @param string $key Field Name
     * @return void
     */
    protected function _parseType(&$data, $key)
    {
        if (empty($this->options['bind'][':' . $key])) {
            $fieldType = strtolower($this->fields['_type'][$key]);
            if (false !== strpos($fieldType, 'enum')) {
                // stand byENUMTypes ofpriorityDetect
            } elseif (false === strpos($fieldType, 'bigint') && false !== strpos($fieldType, 'int')) {
                $data[$key] = intval($data[$key]);
            } elseif (false !== strpos($fieldType, 'float') || false !== strpos($fieldType, 'double')) {
                $data[$key] = floatval($data[$key]);
            } elseif (false !== strpos($fieldType, 'bool')) {
                $data[$key] = (bool)$data[$key];
            }
        }
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
            $where[$this->getPk()] = $options;
            $options = array();
            $options['where'] = $where;
        }
        // alwaysSeek一Records
        $options['limit'] = 1;
        // Analysis of expression
        $options = $this->_parseOptions($options);
        $resultSet = $this->db->select($options);
        if (false === $resultSet) {
            return false;
        }
        if (empty($resultSet)) {// search resultforair
            return null;
        }
        $this->data = $resultSet[0];
        $this->_after_find($this->data, $options);
        if (!empty($this->options['result'])) {
            return $this->returnResult($this->data, $this->options['result']);
        }
        return $this->data;
    }

    // InquiresuccessofCallbackmethod
    protected function _after_find(&$result, $options)
    {
    }

    protected function returnResult($data, $type = '')
    {
        if ($type) {
            if (is_callable($type)) {
                return call_user_func($type, $data);
            }
            switch (strtolower($type)) {
                case 'json':
                    return json_encode($data);
                case 'xml':
                    return xml_encode($data);
            }
        }
        return $data;
    }

    /**
     * Field mapping process
     * @access public
     * @param array $data Current data
     * @param integer $type Types of 0 Write 1 Read
     * @return array
     */
    public function parseFieldsMap($data, $type = 1)
    {
        // an examinationFieldMapping
        if (!empty($this->_map)) {
            foreach ($this->_map as $key => $val) {
                if ($type == 1) { // Read
                    if (isset($data[$val])) {
                        $data[$key] = $data[$val];
                        unset($data[$val]);
                    }
                } else {
                    if (isset($data[$key])) {
                        $data[$val] = $data[$key];
                        unset($data[$key]);
                    }
                }
            }
        }
        return $data;
    }

    /**
     * Set uprecordingofAField Values
     * stand byusedatabaseFieldwithmethod
     * @access public
     * @param string|array $field Field Name
     * @param string $value Field Values
     * @return boolean
     */
    public function setField($field, $value = '')
    {
        if (is_array($field)) {
            $data = $field;
        } else {
            $data[$field] = $value;
        }
        return $this->save($data);
    }

    /**
     * Field value growth
     * @access public
     * @param string $field Field Name
     * @param integer $step Increase value
     * @return boolean
     */
    public function setInc($field, $step = 1)
    {
        return $this->setField($field, array('exp', $field . '+' . $step));
    }

    /**
     * Field values decrease
     * @access public
     * @param string $field Field Name
     * @param integer $step Reduce value
     * @return boolean
     */
    public function setDec($field, $step = 1)
    {
        return $this->setField($field, array('exp', $field . '-' . $step));
    }

    /**
     * Obtain一RecordsofAField Values
     * @access public
     * @param string $field Field Name
     * @param string $spea Field data symbol interval NULLReturns an array
     * @return mixed
     */
    public function getField($field, $sepa = null)
    {
        $options['field'] = $field;
        $options = $this->_parseOptions($options);
        $field = trim($field);
        if (strpos($field, ',')) { // manyField
            if (!isset($options['limit'])) {
                $options['limit'] = is_numeric($sepa) ? $sepa : '';
            }
            $resultSet = $this->db->select($options);
            if (!empty($resultSet)) {
                $_field = explode(',', $field);
                $field = array_keys($resultSet[0]);
                $key = array_shift($field);
                $key2 = array_shift($field);
                $cols = array();
                $count = count($_field);
                foreach ($resultSet as $result) {
                    $name = $result[$key];
                    if (2 == $count) {
                        $cols[$name] = $result[$key2];
                    } else {
                        $cols[$name] = is_string($sepa) ? implode($sepa, $result) : $result;
                    }
                }
                return $cols;
            }
        } else {   // Seek一Records
            // Return dataThe number of
            if (true !== $sepa) {// whensepaDesignationfortrueoftime returnalldata
                $options['limit'] = is_numeric($sepa) ? $sepa : 1;
            }
            $result = $this->db->select($options);
            if (!empty($result)) {
                if (true !== $sepa && 1 == $options['limit']) return reset($result[0]);
                foreach ($result as $val) {
                    $array[] = $val[$field];
                }
                return $array;
            }
        }
        return null;
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
        }
        // verify the data
        if (empty($data) || !is_array($data)) {
            $this->error = L('_DATA_TYPE_INVALID_');
            return false;
        }

        // an examinationFieldMapping
        $data = $this->parseFieldsMap($data, 0);

        // status
        $type = $type ? $type : (!empty($data[$this->getPk()]) ? self::MODEL_UPDATE : self::MODEL_INSERT);

        // DetectsubmitFieldoflegitimate性
        if (isset($this->options['field'])) { // $this->field('field1,field2...')->create()
            $fields = $this->options['field'];
            unset($this->options['field']);
        } elseif ($type == self::MODEL_INSERT && isset($this->insertFields)) {
            $fields = $this->insertFields;
        } elseif ($type == self::MODEL_UPDATE && isset($this->updateFields)) {
            $fields = $this->updateFields;
        }
        if (isset($fields)) {
            if (is_string($fields)) {
                $fields = explode(',', $fields);
            }
            // judgmentTokenverificationField
            if (C('TOKEN_ON')) $fields[] = C('TOKEN_NAME');
            foreach ($data as $key => $val) {
                if (!in_array($key, $fields)) {
                    unset($data[$key]);
                }
            }
        }

        // dataautomaticverification
        if (!$this->autoValidation($data, $type)) return false;

        // FormsTokenverification
        if (!$this->autoCheckToken($data)) {
            $this->error = L('_TOKEN_ERROR_');
            return false;
        }

        // verificationcarry outFormData Objects
        if ($this->autoCheckFields) { // OpenFieldDetect The filtering illegalFielddata
            $fields = $this->getDbFields();
            foreach ($data as $key => $val) {
                if (!in_array($key, $fields)) {
                    unset($data[$key]);
                } elseif (is_string($val) && (version_compare(PHP_VERSION, '7.0.0') < 0 || ini_get('magic_quotes_gpc'))) {
                    $data[$key] = stripslashes($val);
                }
            }
        }

        // createcarry outCorrectdataEnterRowautomaticdeal with
        $this->autoOperation($data, $type);
        // 赋valueCurrent dataObjects
        $this->data = $data;
        // returncreateofdataFor othertransfer
        return $data;
    }

    // automaticFormsTokenverification
    // TODO  ajaxnoRefreshrepeatedlysubmitTemporarily unable to meet
    public function autoCheckToken($data)
    {
        // stand byusetoken(false) shut downTokenverification
        if (isset($this->options['token']) && !$this->options['token']) return true;
        if (C('TOKEN_ON')) {
            $name = C('TOKEN_NAME');
            if (!isset($data[$name]) || !isset($_SESSION[$name])) { // Tokendatainvalid
                return false;
            }

            // Tokenverification
            list($key, $value) = explode('_', $data[$name]);
            if ($value && $_SESSION[$name][$key] === $value) { // prevent重复submit
                unset($_SESSION[$name][$key]); // verificationcarry outdestroysession
                return true;
            }
            // OpenTOKENReset
            if (C('TOKEN_RESET')) unset($_SESSION[$name][$key]);
            return false;
        }
        return true;
    }

    /**
     * Using regular authentication data
     * @access public
     * @param string $value To verify the data
     * @param string $rule Validation Rules
     * @return boolean
     */
    public function regex($value, $rule)
    {
        $validate = array(
            'require' => '/.+/',
            'email' => '/^\w+([-+.]\w+)*@\w+([-.]\w+)*\.\w+([-.]\w+)*$/',
            'url' => '/^http(s?):\/\/(?:[A-za-z0-9-]+\.)+[A-za-z]{2,4}(?:[\/\?#][\/=\?%\-&~`@[\]\':+!\.#\w]*)?$/',
            'currency' => '/^\d+(\.\d+)?$/',
            'number' => '/^\d+$/',
            'zip' => '/^\d{6}$/',
            'integer' => '/^[-\+]?\d+$/',
            'double' => '/^[-\+]?\d+(\.\d+)?$/',
            'english' => '/^[A-Za-z]+$/',
        );
        // an examinationwhetherHaveBuilt-inRegularexpression
        if (isset($validate[strtolower($rule)]))
            $rule = $validate[strtolower($rule)];
        return preg_match($rule, $value) === 1;
    }

    /**
     * Automatic form processing
     * @access public
     * @param array $data Creating a Data
     * @param string $type Creating type
     * @return mixed
     */
    private function autoOperation(&$data, $type)
    {
        if (!empty($this->options['auto'])) {
            $_auto = $this->options['auto'];
            unset($this->options['auto']);
        } elseif (!empty($this->_auto)) {
            $_auto = $this->_auto;
        }
        // automaticfilling
        if (isset($_auto)) {
            foreach ($_auto as $auto) {
                // fillingfactordefinitionformat
                // array('field','Filling content','Filling condition','Additional rules',[Additional parameters])
                if (empty($auto[2])) $auto[2] = self::MODEL_INSERT; // The default isNewoftimeautomaticfilling
                if ($type == $auto[2] || $auto[2] == self::MODEL_BOTH) {
                    switch (trim($auto[3])) {
                        case 'function':    //  Use the function to fill As a parameter value field
                        case 'callback': // useCallbackmethod
                            $args = isset($auto[4]) ? (array)$auto[4] : array();
                            if (isset($data[$auto[0]])) {
                                array_unshift($args, $data[$auto[0]]);
                            }
                            if ('function' == $auto[3]) {
                                $data[$auto[0]] = call_user_func_array($auto[1], $args);
                            } else {
                                $data[$auto[0]] = call_user_func_array(array(&$this, $auto[1]), $args);
                            }
                            break;
                        case 'field':    // useotherFieldThe valueEnterRowfilling
                            $data[$auto[0]] = $data[$auto[1]];
                            break;
                        case 'ignore': // forIgnore empty
                            if ('' === $data[$auto[0]])
                                unset($data[$auto[0]]);
                            break;
                        case 'string':
                        default: // defaultAs aStringfilling
                            $data[$auto[0]] = $auto[1];
                    }
                    if (false === $data[$auto[0]]) unset($data[$auto[0]]);
                }
            }
        }
        return $data;
    }

    /**
     * Automatic form validation
     * @access protected
     * @param array $data Creating a Data
     * @param string $type Creating type
     * @return boolean
     */
    protected function autoValidation($data, $type)
    {
        if (!empty($this->options['validate'])) {
            $_validate = $this->options['validate'];
            unset($this->options['validate']);
        } elseif (!empty($this->_validate)) {
            $_validate = $this->_validate;
        }
        // Attributesverification
        if (isset($_validate)) { // If you setdataautomaticverificationIs performeddataverification
            if ($this->patchValidate) { // ResetverificationError Messages
                $this->error = array();
            }
            foreach ($_validate as $key => $val) {
                // Verify factordefinitionformat
                // array(field,rule,message,condition,type,when,params)
                // Determine whetherneedcarried outverification
                if (empty($val[5]) || $val[5] == self::MODEL_BOTH || $val[5] == $type) {
                    if (0 == strpos($val[2], '{%') && strpos($val[2], '}'))
                        // stand byTipsofmulti-language use {%Languagedefinition} the way
                        $val[2] = L(substr($val[2], 2, -1));
                    $val[3] = isset($val[3]) ? $val[3] : self::EXISTS_VALIDATE;
                    $val[4] = isset($val[4]) ? $val[4] : 'regex';
                    // judgmentverificationcondition
                    switch ($val[3]) {
                        case self::MUST_VALIDATE:   // have toverification Regardless ofFormswhetherHaveSet upThatField
                            if (false === $this->_validationField($data, $val))
                                return false;
                            break;
                        case self::VALUE_VALIDATE:    // valuenot nullofWhen itverification
                            if ('' != trim($data[$val[0]]))
                                if (false === $this->_validationField($data, $val))
                                    return false;
                            break;
                        default:    // defaultFormsexistThatFieldonverification
                            if (isset($data[$val[0]]))
                                if (false === $this->_validationField($data, $val))
                                    return false;
                    }
                }
            }
            // batchverificationofThe last timereturnerror
            if (!empty($this->error)) return false;
        }
        return true;
    }

    /**
     * Validate form fields Support batch verification
     * in casebatchverificationreturnerrorArrayinformation
     * @access protected
     * @param array $data Creating a Data
     * @param array $val Verify factor
     * @return boolean
     */
    protected function _validationField($data, $val)
    {
        if (false === $this->_validationFieldItem($data, $val)) {
            if ($this->patchValidate) {
                $this->error[$val[0]] = $val[2];
            } else {
                $this->error = $val[2];
                return false;
            }
        }
        return;
    }

    /**
     * according toVerify factorverificationField
     * @access protected
     * @param array $data Creating a Data
     * @param array $val Verify factor
     * @return boolean
     */
    protected function _validationFieldItem($data, $val)
    {
        switch (strtolower(trim($val[4]))) {
            case 'function':// usefunctionauthenticating
            case 'callback':// transfermethodauthenticating
                $args = isset($val[6]) ? (array)$val[6] : array();
                if (is_string($val[0]) && strpos($val[0], ','))
                    $val[0] = explode(',', $val[0]);
                if (is_array($val[0])) {
                    // Support for multipleFieldverification
                    foreach ($val[0] as $field)
                        $_data[$field] = $data[$field];
                    array_unshift($args, $_data);
                } else {
                    array_unshift($args, $data[$val[0]]);
                }
                if ('function' == $val[4]) {
                    return call_user_func_array($val[1], $args);
                } else {
                    return call_user_func_array(array(&$this, $val[1]), $args);
                }
            case 'confirm': // verificationTwoFieldwhether相同
                return $data[$val[0]] == $data[$val[1]];
            case 'unique': // verificationA valuewhetheronly
                if (is_string($val[0]) && strpos($val[0], ','))
                    $val[0] = explode(',', $val[0]);
                $map = array();
                if (is_array($val[0])) {
                    // Support for multipleFieldverification
                    foreach ($val[0] as $field)
                        $map[$field] = $data[$field];
                } else {
                    $map[$val[0]] = $data[$val[0]];
                }
                if (!empty($data[$this->getPk()])) { // perfecteditoftimeverificationonly
                    $map[$this->getPk()] = array('neq', $data[$this->getPk()]);
                }
                if ($this->where($map)->find()) return false;
                return true;
            default:  // an examinationAdditionalrule
                return $this->check($data[$val[0]], $val[1], $val[4]);
        }
    }

    /**
     * verify the data stand by in between equal length regex expire ip_allow ip_deny
     * @access public
     * @param string $value verify the data
     * @param mixed $rule Validate Expression
     * @param string $type Ways of identifying The default is a regular verification
     * @return boolean
     */
    public function check($value, $rule, $type = 'regex')
    {
        $type = strtolower(trim($type));
        switch ($type) {
            case 'in': // verificationwhetherAt someDesignationrangeIt内 commaSeparatedStringorArray
            case 'notin':
                $range = is_array($rule) ? $rule : explode(',', $rule);
                return $type == 'in' ? in_array($value, $range) : !in_array($value, $range);
            case 'between': // verificationwhetherIn a range
            case 'notbetween': // verificationwhetherNot in a range            
                if (is_array($rule)) {
                    $min = $rule[0];
                    $max = $rule[1];
                } else {
                    list($min, $max) = explode(',', $rule);
                }
                return $type == 'between' ? $value >= $min && $value <= $max : $value < $min || $value > $max;
            case 'equal': // verificationwhetherEqual to a value
            case 'notequal': // verificationwhetherEqual to a value            
                return $type == 'equal' ? $value == $rule : $value != $rule;
            case 'length': // verificationlength
                $length = mb_strlen($value, 'utf-8'); // Current datalength
                if (strpos($rule, ',')) { // lengthInterval
                    list($min, $max) = explode(',', $rule);
                    return $length >= $min && $length <= $max;
                } else {// Designationlength
                    return $length == $rule;
                }
            case 'expire':
                list($start, $end) = explode(',', $rule);
                if (!is_numeric($start)) $start = strtotime($start);
                if (!is_numeric($end)) $end = strtotime($end);
                return NOW_TIME >= $start && NOW_TIME <= $end;
            case 'ip_allow': // IP Operating license verification
                return in_array(get_client_ip(), explode(',', $rule));
            case 'ip_deny': // IP Operating ban verification
                return !in_array(get_client_ip(), explode(',', $rule));
            case 'regex':
            default:    // defaultuseRegularverification can useverificationclassDefinedofverificationname
                // an examinationAdditionalrule
                return $this->regex($value, $rule);
        }
    }

    /**
     * SQLInquire
     * @access public
     * @param string $sql SQLinstruction
     * @param mixed $parse Need to resolveSQL
     * @return mixed
     */
    public function query($sql, $parse = false)
    {
        if (!is_bool($parse) && !is_array($parse)) {
            $parse = func_get_args();
            array_shift($parse);
        }
        $sql = $this->parseSql($sql, $parse);
        return $this->db->query($sql);
    }

    /**
     * carried outSQLStatement
     * @access public
     * @param string $sql SQLinstruction
     * @param mixed $parse Need to resolveSQL
     * @return false | integer
     */
    public function execute($sql, $parse = false)
    {
        if (!is_bool($parse) && !is_array($parse)) {
            $parse = func_get_args();
            array_shift($parse);
        }
        $sql = $this->parseSql($sql, $parse);
        return $this->db->execute($sql);
    }

    /**
     * ResolveSQLStatement
     * @access public
     * @param string $sql SQLinstruction
     * @param boolean $parse Need to resolveSQL
     * @return string
     */
    protected function parseSql($sql, $parse)
    {
        // Analysis of expression
        if (true === $parse) {
            $options = $this->_parseOptions();
            $sql = $this->db->parseSql($sql, $options);
        } elseif (is_array($parse)) { // SQLPretreatment
            $parse = array_map(array($this->db, 'escapeString'), $parse);
            $sql = vsprintf($sql, $parse);
        } else {
            $sql = strtr($sql, array('__TABLE__' => $this->getTableName(), '__PREFIX__' => C('DB_PREFIX')));
        }
        $this->db->setModel($this->name);
        return $sql;
    }

    /**
     * SwitchcurrentofDatabase Connectivity
     * @access public
     * @param integer $linkNum connectionNo.
     * @param mixed $config Database connection information
     * @param array $params Model parameters
     * @return Model
     */
    public function db($linkNum = '', $config = '', $params = array())
    {
        if ('' === $linkNum && $this->db) {
            return $this->db;
        }
        static $_linkNum = array();
        static $_db = array();
        if (!isset($_db[$linkNum]) || (isset($_db[$linkNum]) && $config && $_linkNum[$linkNum] != $config)) {
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
        if (!empty($params)) {
            if (is_string($params)) parse_str($params, $params);
            foreach ($params as $name => $value) {
                $this->setProperty($name, $value);
            }
        }
        // recordingConnection Information
        $_linkNum[$linkNum] = $config;
        // SwitchDatabase Connectivity
        $this->db = $_db[$linkNum];
        $this->_after_db();
        // FieldDetect
        if (!empty($this->name) && $this->autoCheckFields) $this->_checkTableInfo();
        return $this;
    }

    // databaseSwitching callbackmethod
    protected function _after_db()
    {
    }

    /**
     * getcurrentofData Objectsname
     * @access public
     * @return string
     */
    public function getModelName()
    {
        if (empty($this->name))
            $this->name = substr(get_class($this), 0, -5);
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
     * Return modelError Messages
     * @access public
     * @return string
     */
    public function getError()
    {
        return $this->error;
    }

    /**
     * Return dataStorehouseofError Messages
     * @access public
     * @return string
     */
    public function getDbError()
    {
        return $this->db->getError();
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

    /**
     * Returns the last executionsqlStatement
     * @access public
     * @return string
     */
    public function getLastSql()
    {
        return $this->db->getLastSql($this->name);
    }

    // In view ofgetLastSqlMore common increase_sql Slug
    public function _sql()
    {
        return $this->getLastSql();
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
     * Obtaindata sheetFieldinformation
     * @access public
     * @return array
     */
    public function getDbFields()
    {
        if (isset($this->options['table'])) {// dynamicDesignationTable name
            $fields = $this->db->getFields($this->options['table']);
            return $fields ? array_keys($fields) : false;
        }
        if ($this->fields) {
            $fields = $this->fields;
            unset($fields['_autoinc'], $fields['_pk'], $fields['_type'], $fields['_version']);
            return $fields;
        }
        return false;
    }

    /**
     * Set value data object
     * @access public
     * @param mixed $data data
     * @return Model
     */
    public function data($data = '')
    {
        if ('' === $data && !empty($this->data)) {
            return $this->data;
        }
        if (is_object($data)) {
            $data = get_object_vars($data);
        } elseif (is_string($data)) {
            parse_str($data, $data);
        } elseif (!is_array($data)) {
            throw_exception(L('_DATA_TYPE_INVALID_'));
        }
        $this->data = $data;
        return $this;
    }

    /**
     * InquireSQLAssembly join
     * @access public
     * @param mixed $join
     * @return Model
     */
    public function join($join)
    {
        if (is_array($join)) {
            $this->options['join'] = $join;
        } elseif (!empty($join)) {
            $this->options['join'][] = $join;
        }
        return $this;
    }

    /**
     * InquireSQLAssembly union
     * @access public
     * @param mixed $union
     * @param boolean $all
     * @return Model
     */
    public function union($union, $all = false)
    {
        if (empty($union)) return $this;
        if ($all) {
            $this->options['union']['_all'] = true;
        }
        if (is_object($union)) {
            $union = get_object_vars($union);
        }
        // Changeunionexpression
        if (is_string($union)) {
            $options = $union;
        } elseif (is_array($union)) {
            if (isset($union[0])) {
                $this->options['union'] = array_merge($this->options['union'], $union);
                return $this;
            } else {
                $options = $union;
            }
        } else {
            throw_exception(L('_DATA_TYPE_INVALID_'));
        }
        $this->options['union'][] = $options;
        return $this;
    }

    /**
     * Query Cache
     * @access public
     * @param mixed $key
     * @param integer $expire
     * @param string $type
     * @return Model
     */
    public function cache($key = true, $expire = null, $type = '')
    {
        if (false !== $key)
            $this->options['cache'] = array('key' => $key, 'expire' => $expire, 'type' => $type);
        return $this;
    }

    /**
     * Specify the query field Support field to exclude
     * @access public
     * @param mixed $field
     * @param boolean $except Whether to exclude
     * @return Model
     */
    public function field($field, $except = false)
    {
        if (true === $field) {// ObtainCompleteField
            $fields = $this->getDbFields();
            $field = $fields ? $fields : '*';
        } elseif ($except) {// Fieldexclude
            if (is_string($field)) {
                $field = explode(',', $field);
            }
            $fields = $this->getDbFields();
            $field = $fields ? array_diff($fields, $field) : $field;
        }
        $this->options['field'] = $field;
        return $this;
    }

    /**
     * Call a named range
     * @access public
     * @param mixed $scope Named range name Support for multiple And defined directly
     * @param array $args parameter
     * @return Model
     */
    public function scope($scope = '', $args = NULL)
    {
        if ('' === $scope) {
            if (isset($this->_scope['default'])) {
                // defaultofNamed range
                $options = $this->_scope['default'];
            } else {
                return $this;
            }
        } elseif (is_string($scope)) { // Support for multipleNamed rangetransfer usecommaMinute割
            $scopes = explode(',', $scope);
            $options = array();
            foreach ($scopes as $name) {
                if (!isset($this->_scope[$name])) continue;
                $options = array_merge($options, $this->_scope[$name]);
            }
            if (!empty($args) && is_array($args)) {
                $options = array_merge($options, $args);
            }
        } elseif (is_array($scope)) { // directIncoming named rangedefinition
            $options = $scope;
        }

        if (is_array($options) && !empty($options)) {
            $this->options = array_merge($this->options, array_change_key_case($options));
        }
        return $this;
    }

    /**
     * Specify the query criteria Support for security filtering
     * @access public
     * @param mixed $where Conditional expression
     * @param mixed $parse Pretreatment parameters
     * @return Model
     */
    public function where($where, $parse = null)
    {
        if (!is_null($parse) && is_string($where)) {
            if (!is_array($parse)) {
                $parse = func_get_args();
                array_shift($parse);
            }
            $parse = array_map(array($this->db, 'escapeString'), $parse);
            $where = vsprintf($where, $parse);
        } elseif (is_object($where)) {
            $where = get_object_vars($where);
        }
        if (is_string($where) && '' != $where) {
            $map = array();
            $map['_string'] = $where;
            $where = $map;
        }
        if (isset($this->options['where'])) {
            $this->options['where'] = array_merge($this->options['where'], $where);
        } else {
            $this->options['where'] = $where;
        }

        return $this;
    }

    /**
     * Specifies the number of queries
     * @access public
     * @param mixed $offset starting point
     * @param mixed $length The number of queries
     * @return Model
     */
    public function limit($offset, $length = null)
    {
        $this->options['limit'] = is_null($length) ? $offset : $offset . ',' . $length;
        return $this;
    }

    /**
     * DesignationPaging
     * @access public
     * @param mixed $page Pages
     * @param mixed $listRows Page number
     * @return Model
     */
    public function page($page, $listRows = null)
    {
        $this->options['page'] = is_null($listRows) ? $page : $page . ',' . $listRows;
        return $this;
    }

    /**
     * Query Notes
     * @access public
     * @param string $comment Note
     * @return Model
     */
    public function comment($comment)
    {
        $this->options['comment'] = $comment;
        return $this;
    }

    /**
     * Property value model
     * @access public
     * @param string $name name
     * @param mixed $value value
     * @return Model
     */
    public function setProperty($name, $value)
    {
        if (property_exists($this, $name))
            $this->$name = $value;
        return $this;
    }

}