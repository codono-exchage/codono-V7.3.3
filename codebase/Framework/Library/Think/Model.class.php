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
namespace Think;
/**
 * ThinkPHP ModelModel Class
 * AchievedORMwithActiveRecordsmode
 */
class Model
{
    // operatingstatus
    const MODEL_INSERT = 1;      //  Insert data model
    const MODEL_UPDATE = 2;      //  Update model data
    const MODEL_BOTH = 3;      //  It contains the above two ways
    const MUST_VALIDATE = 1;      // have toverification
    const EXISTS_VALIDATE = 0;      // FormsexistFieldthenverification
    const VALUE_VALIDATE = 2;      // Formsvaluenot nullthenverification

    // Current dataStorehouseoperatingObjects
    protected $db = null;
    // databaseObjectsPool
    private $_db = array();
    // Primary keyname
    protected $pk = 'id';
    // Primary keywhetherautomaticincrease
    protected $autoinc = false;
    // data sheetPrefix
    protected $tablePrefix = null;
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
    protected $methods = array('strict', 'order', 'alias', 'having', 'group', 'lock', 'distinct', 'auto', 'filter', 'validate', 'result', 'token', 'index', 'force');

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
        } elseif (!isset($this->tablePrefix)) {
            $this->tablePrefix = C('DB_PREFIX');
        }

        // databaseinitializationoperating
        // ObtaindatabaseoperatingObjects
        // currentModel independentofDatabase connection information
        $this->db(0, empty($this->connection) ? $connection : $this->connection, true);
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
                $db = $this->dbName ?: C('DB_NAME');
                $fields = F('_fields/' . strtolower($db . '.' . $this->tablePrefix . $this->name));
                if ($fields) {
                    $this->fields = $fields;
                    if (!empty($fields['_pk'])) {
                        $this->pk = $fields['_pk'];
                    }
                    return;
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
        unset($this->fields['_pk']);
        foreach ($fields as $key => $val) {
            // recordingFieldTypes of
            $type[$key] = $val['type'];
            if ($val['primary']) {
                // increasecomplexPrimary keystand by
                if (isset($this->fields['_pk']) && $this->fields['_pk'] != null) {
                    if (is_string($this->fields['_pk'])) {
                        $this->pk = array($this->fields['_pk']);
                        $this->fields['_pk'] = $this->pk;
                    }
                    $this->pk[] = $key;
                    $this->fields['_pk'][] = $key;
                } else {
                    $this->pk = $key;
                    $this->fields['_pk'] = $key;
                }
                if ($val['autoinc']) $this->autoinc = true;
            }
        }
        // recordingFieldTypes ofinformation
        $this->fields['_type'] = $type;

        // 2008-3-7 Increase the cache control switch
        if (C('DB_FIELDS_CACHE')) {
            // permanentCachedata sheetinformation
            $db = $this->dbName ?: C('DB_NAME');
            F('_fields/' . strtolower($db . '.' . $this->tablePrefix . $this->name), $this->fields);
        }
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
            E(__CLASS__ . ':' . $method . L('_METHOD_NOT_EXIST_'));
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

        // an examinationdataFieldlegitimate性
        if (!empty($this->fields)) {
            if (!empty($this->options['field'])) {
                $fields = $this->options['field'];
                unset($this->options['field']);
                if (is_string($fields)) {
                    $fields = explode(',', $fields);
                }
            } else {
                $fields = $this->fields;
            }
            foreach ($data as $key => $val) {
                if (!in_array($key, $fields, true)) {
                    if (!empty($this->options['strict'])) {
                        E(L('_DATA_TYPE_INVALID_') . ':[' . $key . '=>' . $val . ']');
                    }
                    unset($data[$key]);
                } elseif (is_scalar($val)) {
                    // FieldTypes ofan examination with Cast
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
        // datadeal with
        $data = $this->_facade($data);
        // Analysis of expression
        $options = $this->_parseOptions($options);
        if (false === $this->_before_insert($data, $options)) {
            return false;
        }
        // data inputTodatabase
        $result = $this->db->insert($data, $options, $replace);
        if (false !== $result && is_numeric($result)) {
            $pk = $this->getPk();
            // increasecomplexPrimary keystand by
            if (is_array($pk)) return $result;
            $insertId = $this->getLastInsID();
            if ($insertId) {
                // IncrementPrimary keyreturninsertID
                $data[$pk] = $insertId;
                if (false === $this->_after_insert($data, $options)) {
                    return false;
                }
                return $insertId;
            }
            if (false === $this->_after_insert($data, $options)) {
                return false;
            }
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
        // datadeal with
        foreach ($dataList as $key => $data) {
            $dataList[$key] = $this->_facade($data);
        }
        // Analysis of expression
        $options = $this->_parseOptions($options);
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
        if (false === $result = $this->db->selectInsert($fields ?: $options['field'], $table ?: $this->getTableName(), $options)) {
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
        			
		if (empty($data)) {
            // Nodatathen不carried out

            $this->error = L('_DATA_TYPE_INVALID_');
            return false;
        }
		
        // Analysis of expression
        $options = $this->_parseOptions($options);
        $pk = $this->getPk();

        if (!isset($options['where'])) {
            // in caseexistPrimary keydata thenautomaticAs aUpdatecondition
            if (is_string($pk) && isset($data[$pk])) {
                $where[$pk] = $data[$pk];
                unset($data[$pk]);
            } elseif (is_array($pk)) {
                // increasecomplexPrimary keystand by
                foreach ($pk as $field) {
                    if (isset($data[$field])) {
                        $where[$field] = $data[$field];
                    } else {
                        // in caseLack of complexPrimary keydatathen不carried out
                        $this->error = L('_OPERATION_WRONG_');
                        return false;
                    }
                    unset($data[$field]);
                }
            }

            if (!isset($where)) {
                // in caseNoany Update condition then not carrying out
                $this->error = L('_OPERATION_WRONG_');
                return false;
            } else {
                $options['where'] = $where;
            }
        }

        if (is_array($options['where']) && isset($options['where'][$pk])) {
            $pkValue = $options['where'][$pk];
        }
        if (false === $this->_before_update($data, $options)) {
            return false;
        }

        $result = $this->db->update($data, $options);
        if (false !== $result && is_numeric($result)) {
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
        $pk = $this->getPk();
        if (empty($options) && empty($this->options['where'])) {
            // in casedeleteconditionforair thendeleteCurrent dataObjectsThecorrespondingrecording
            if (!empty($this->data) && isset($this->data[$pk]))
                return $this->delete($this->data[$pk]);
            else
                return false;
        }
        if (is_numeric($options) || is_string($options)) {
            // according toPrimary keyDelete Record
            if (strpos($options, ',')) {
                $where[$pk] = array('IN', $options);
            } else {
                $where[$pk] = $options;
            }
            // $options = array();
            // $options['where'] = $where;
            $this->options['where'] = $where;
        }
        // according tocomplexPrimary keyDelete Record
        if (is_array($options) && (count($options) > 0) && is_array($pk)) {
            $count = 0;
            foreach (array_keys($options) as $key) {
                if (is_int($key)) $count++;
            }
            if ($count == count($pk)) {
                $i = 0;
                foreach ($pk as $field) {
                    $where[$field] = $options[$i];
                    unset($options[$i++]);
                }
                //$options['where'] = $where;
                $this->options['where'] = $where;
            } else {
                return false;
            }
        }
        // Analysis of expression
        //$options = $this->_parseOptions($options);
        $options = $this->_parseOptions();
        if (empty($options['where'])) {
            // in caseconditionforair Withoutdeleteoperating unlessSet up 1=1
            return false;
        }
        if (is_array($options['where']) && isset($options['where'][$pk])) {
            $pkValue = $options['where'][$pk];
        }

        if (false === $this->_before_delete($options)) {
            return false;
        }
        $result = $this->db->delete($options);
        if (false !== $result && is_numeric($result)) {
            $data = array();
            if (isset($pkValue)) $data[$pk] = $pkValue;
            $this->_after_delete($data, $options);
        }
        // returnDelete RecordThe number of
        return $result;
    }

    // delete databeforeofCallbackmethod
    protected function _before_delete($options)
    {
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
        $pk = $this->getPk();
        if (is_string($options) || is_numeric($options)) {
            // according toPrimary keyInquire
            if (strpos($options, ',')) {
                $where[$pk] = array('IN', $options);
            } else {
                $where[$pk] = $options;
            }
            // $options = array();
            // $options['where'] = $where;
            $this->options['where'] = $where;
        } elseif (is_array($options) && (count($options) > 0) && is_array($pk)) {
            // according tocomplexPrimary keyInquire
            $count = 0;
            foreach (array_keys($options) as $key) {
                if (is_int($key)) $count++;
            }
            if ($count == count($pk)) {
                $i = 0;
                foreach ($pk as $field) {
                    $where[$field] = $options[$i];
                    unset($options[$i++]);
                }
//                $options['where'] = $where;
$this->options['where'] = $where;

            } else {
                return false;
            }
        } elseif (false === $options) { // ForSubqueries 不InquireOnly returnSQL
            $options['fetch_sql'] = true;
        }
        // Analysis of expression
     //   $options = $this->_parseOptions($options);
     $options = $this->_parseOptions();
        // judgmentQuery Cache
        if (isset($options['cache'])) {
            $cache = $options['cache'];
            $key = is_string($cache['key']) ? $cache['key'] : md5(serialize($options));
            $data = S($key, '', $cache);
            if (false !== $data) {
                return $data;
            }
        }
        $resultSet = $this->db->select($options);
        if (false === $resultSet) {
            return false;
        }
        if (!empty($resultSet)) { // There results
            if (is_string($resultSet)) {
                return $resultSet;
            }

            $resultSet = array_map(array($this, '_read_data'), $resultSet);
            $this->_after_select($resultSet, $options);
            if (isset($options['index'])) { // Correctdata setEnterRowindex
                $index = explode(',', $options['index']);
                foreach ($resultSet as $result) {
                    $_key = $result[$index[0]];
                    if (isset($index[1]) && isset($result[$index[1]])) {
                        $cols[$_key] = $result[$index[1]];
                    } else {
                        $cols[$_key] = $result;
                    }
                }
                $resultSet = $cols;
            }
        }

        if (isset($cache)) {
            S($key, $resultSet, $cache);
        }
        return $resultSet;
    }

    // InquiresuccessAfterCallbackmethod
    protected function _after_select(&$resultSet, $options)
    {
    }

    /**
     * Generating a querySQL It can be used subqueries
     * @access public
     * @return string
     */
    public function buildSql()
    {
        return '( ' . $this->fetchSql(true)->select() . ' )';
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

        if (!isset($options['table'])) {
            // automaticObtainTable name
            $options['table'] = $this->getTableName();
            $fields = $this->fields;
        } else {
            // Designationdata sheet thenAgainObtainFieldList butnot supportTypes ofDetect
            $fields = $this->getDbFields();
        }

        // data sheetSlug
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
                    if (!empty($this->options['strict'])) {
                        E(L('_ERROR_QUERY_EXPRESS_') . ':[' . $key . '=>' . $val . ']');
                    }
                    unset($options['where'][$key]);
                }
            }
        }
        // InquireAfter theClearsqlexpressionAssembly To avoid affecting the nextInquire
        $this->options = array();
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
        if (!isset($this->options['bind'][':' . $key]) && isset($this->fields['_type'][$key])) {
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
     * After the data reading process
     * @access protected
     * @param array $data Current data
     * @return array
     */
    protected function _read_data($data)
    {
        // an examinationFieldMapping
        if (!empty($this->_map) && C('READ_DATA_MAP')) {
            foreach ($this->_map as $key => $val) {
                if (isset($data[$val])) {
                    $data[$key] = $data[$val];
                    unset($data[$val]);
                }
            }
        }
        return $data;
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
            // $options = array();
            // $options['where'] = $where;
            $this->options['where'] = $where;
        }
        // according tocomplexPrimary keyFind Record
        $pk = $this->getPk();
        if (is_array($options) && (count($options) > 0) && is_array($pk)) {
            // according tocomplexPrimary keyInquire
            $count = 0;
            foreach (array_keys($options) as $key) {
                if (is_int($key)) $count++;
            }
            if ($count == count($pk)) {
                $i = 0;
                foreach ($pk as $field) {
                    $where[$field] = $options[$i];
                    unset($options[$i++]);
                }
                // $options['where'] = $where;
                $this->options['where'] = $where;

            } else {
                return false;
            }
        }
        // alwaysSeek一Records
        // $options['limit'] = 1;
        $this->options['limit'] = 1;
        // Analysis of expression
        $options = $this->_parseOptions();
        // judgmentQuery Cache
        if (isset($options['cache'])) {
            $cache = $options['cache'];
            $key = is_string($cache['key']) ? $cache['key'] : md5(serialize($options));
            $data = S($key, '', $cache);
            if (false !== $data) {
                $this->data = $data;
                return $data;
            }
        }
        $resultSet = $this->db->select($options);
        if (false === $resultSet) {
            return false;
        }
        if (empty($resultSet)) {// search resultforair
            return null;
        }
        if (is_string($resultSet)) {
            return $resultSet;
        }

        // ReaddataAfterdeal with
        $data = $this->_read_data($resultSet[0]);
        $this->_after_find($data, $options);
        if (!empty($this->options['result'])) {
            return $this->returnResult($data, $this->options['result']);
        }
        $this->data = $data;
        if (isset($cache)) {
            S($key, $data, $cache);
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
     * @param integer $lazyTime Delay time(s)
     * @return boolean
     */
    public function setInc($field, $step = 1, $lazyTime = 0)
    {
        if ($lazyTime > 0) {// delayWrite
            $condition = $this->options['where'];
            $guid = md5($this->name . '_' . $field . '_' . serialize($condition));
            $step = $this->lazyWrite($guid, $step, $lazyTime);
            if (empty($step)) {
                return true; // Wait for the nextWrite
            } elseif ($step < 0) {
                $step = '-' . $step;
            }
        }
        return $this->setField($field, array('exp', $field . '+' . $step));
    }

    /**
     * Field values decrease
     * @access public
     * @param string $field Field Name
     * @param integer $step Reduce value
     * @param integer $lazyTime Delay time(s)
     * @return boolean
     */
    public function setDec($field, $step = 1, $lazyTime = 0)
    {
        if ($lazyTime > 0) {// delayWrite
            $condition = $this->options['where'];
            $guid = md5($this->name . '_' . $field . '_' . serialize($condition));
            $step = $this->lazyWrite($guid, -$step, $lazyTime);
            if (empty($step)) {
                return true; // Wait for the nextWrite
            } elseif ($step > 0) {
                $step = '-' . $step;
            }
        }
        return $this->setField($field, array('exp', $field . '-' . $step));
    }

    /**
     * Delay update check returnfalseHe expressed the need for delay
     * OtherwiseactualWriteofnumbervalue
     * @access public
     * @param string $guid Writing the identification
     * @param integer $step Writing step value
     * @param integer $lazyTime Delay time(s)
     * @return false|integer
     */
    protected function lazyWrite($guid, $step, $lazyTime)
    {
        if (false !== ($value = S($guid))) { // existCachedata input
            if (NOW_TIME > S($guid . '_time') + $lazyTime) {
                // DelayUpdatetimeArrived,Delete Cachedata And actuallydata inputStorehouse
                S($guid, NULL);
                S($guid . '_time', NULL);
                return $value + $step;
            } else {
                // add todataToCache
                S($guid, $value + $step);
                return false;
            }
        } else { // NoCachedata
            S($guid, $step);
            // TimingStart
            S($guid . '_time', NOW_TIME);
            return false;
        }
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
        // judgmentQuery Cache
        if (isset($options['cache'])) {
            $cache = $options['cache'];
            $key = is_string($cache['key']) ? $cache['key'] : md5($sepa . serialize($options));
            $data = S($key, '', $cache);
            if (false !== $data) {
                return $data;
            }
        }
        $field = trim($field);
        if (strpos($field, ',') && false !== $sepa) { // manyField
            if (!isset($options['limit'])) {
                $options['limit'] = is_numeric($sepa) ? $sepa : '';
            }
            $resultSet = $this->db->select($options);
            if (!empty($resultSet)) {
                if (is_string($resultSet)) {
                    return $resultSet;
                }
                $_field = explode(',', $field);
                $field = array_keys($resultSet[0]);
                $key1 = array_shift($field);
                $key2 = array_shift($field);
                $cols = array();
                $count = count($_field);
                foreach ($resultSet as $result) {
                    $name = $result[$key1];
                    if (2 == $count) {
                        $cols[$name] = $result[$key2];
                    } else {
                        $cols[$name] = is_string($sepa) ? implode($sepa, array_slice($result, 1)) : $result;
                    }
                }
                if (isset($cache)) {
                    S($key, $cols, $cache);
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
                if (is_string($result)) {
                    return $result;
                }
                if (true !== $sepa && 1 == $options['limit']) {
                    $data = reset($result[0]);
                    if (isset($cache)) {
                        S($key, $data, $cache);
                    }
                    return $data;
                }
                foreach ($result as $val) {
                    $array[] = $val[$field];
                }
                if (isset($cache)) {
                    S($key, $array, $cache);
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
            $data = I('post.');
        } elseif (is_object($data)) {
            $data = get_object_vars($data);
        }
        // verify the data
        if (empty($data) || !is_array($data)) {
            $this->error = L('_DATA_TYPE_INVALID_');
            return false;
        }

        // status
        $type = $type ?: (!empty($data[$this->getPk()]) ? self::MODEL_UPDATE : self::MODEL_INSERT);

        // an examinationFieldMapping
        $data = $this->parseFieldsMap($data, 0);

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
            if (C('TOKEN_ON')) $fields[] = C('TOKEN_NAME', null, '__hash__');
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
                } elseif (MAGIC_QUOTES_GPC && is_string($val)) {
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
            $name = C('TOKEN_NAME', null, '__hash__');
            if (!isset($data[$name]) || !isset($_SESSION[$name])) { // Tokendatainvalid
                return false;
            }

            // Tokenverification
            list($key, $value) = explode('_', $data[$name]);
            if (isset($_SESSION[$name][$key]) && $value && $_SESSION[$name][$key] === $value) { // prevent重复submit
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
            'require' => '/\S+/',
            'email' => '/^\w+([-+.]\w+)*@\w+([-.]\w+)*\.\w+([-.]\w+)*$/',
            'url' => '/^http(s?):\/\/(?:[A-za-z0-9-]+\.)+[A-za-z]{2,4}(:\d+)?(?:[\/\?#][\/=\?%\-&~`@[\]\':+!\.#\w]*)?$/',
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
        if (false === $this->options['auto']) {
            // Disable Auto Complete
            return $data;
        }
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
                    if (empty($auto[3])) $auto[3] = 'string';
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
                            if ($auto[1] === $data[$auto[0]])
                                unset($data[$auto[0]]);
                            break;
                        case 'string':
                        default: // defaultAs aStringfilling
                            $data[$auto[0]] = $auto[1];
                    }
                    if (isset($data[$auto[0]]) && false === $data[$auto[0]]) unset($data[$auto[0]]);
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
        if (false === $this->options['validate']) {
            // Turn off the automatic verification
            return true;
        }
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
                if (empty($val[5]) || ($val[5] == self::MODEL_BOTH && $type < 3) || $val[5] == $type) {
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
        if ($this->patchValidate && isset($this->error[$val[0]]))
            return; //currentFieldalreadyHaveruleverificationNoby
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
                $pk = $this->getPk();
                if (!empty($data[$pk]) && is_string($pk)) { // perfecteditoftimeverificationonly
                    $map[$pk] = array('neq', $data[$pk]);
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
     * storageLive程returnmanydata set
     * @access public
     * @param string $sql SQLinstruction
     * @param mixed $parse Need to resolveSQL
     * @return array
     */
    public function procedure($sql, $parse = false)
    {
        return $this->db->procedure($sql, $parse);
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
            $sql = strtr($sql, array('__TABLE__' => $this->getTableName(), '__PREFIX__' => $this->tablePrefix));
            $prefix = $this->tablePrefix;
            $sql = preg_replace_callback("/__([A-Z0-9_-]+)__/sU", function ($match) use ($prefix) {
                return $prefix . strtolower($match[1]);
            }, $sql);
        }
        $this->db->setModel($this->name);
        return $sql;
    }

    /**
     * SwitchcurrentofDatabase Connectivity
     * @access public
     * @param integer $linkNum connectionNo.
     * @param mixed $config Database connection information
     * @param boolean $force Forced to reconnect
     * @return Model
     */
    public function db($linkNum = '', $config = '', $force = false)
    {
        if ('' === $linkNum && $this->db) {
            return $this->db;
        }

        if (!isset($this->_db[$linkNum]) || $force) {
            // CreatenewofExamples
            if (!empty($config) && is_string($config) && false === strpos($config, '/')) { // stand byReadConfiguration parameters
                $config = C($config);
            }
            $this->_db[$linkNum] = Db::getInstance($config);
        } elseif (NULL === $config) {
            $this->_db[$linkNum]->close(); // Close the databaseconnection
            unset($this->_db[$linkNum]);
            return;
        }

        // SwitchDatabase Connectivity
        $this->db = $this->_db[$linkNum];
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
        if (empty($this->name)) {
            $name = substr(get_class($this), 0, -strlen(C('DEFAULT_M_LAYER')));
            if ($pos = strrpos($name, '\\')) {//HaveNamespaces
                $this->name = substr($name, $pos + 1);
            } else {
                $this->name = $name;
            }
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
        return $this->pk;
    }

    /**
     * Obtaindata sheetFieldinformation
     * @access public
     * @return array
     */
    public function getDbFields()
    {
        if (isset($this->options['table'])) {// dynamicDesignationTable name
            if (is_array($this->options['table'])) {
                $table = key($this->options['table']);
            } else {
                $table = $this->options['table'];
                if (strpos($table, ')')) {
                    // Subqueries
                    return false;
                }
            }
            $fields = $this->db->getFields($table);
            return $fields ? array_keys($fields) : false;
        }
        if ($this->fields) {
            $fields = $this->fields;
            unset($fields['_type'], $fields['_pk']);
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
            E(L('_DATA_TYPE_INVALID_'));
        }
        $this->data = $data;
        return $this;
    }

    /**
     * Specifies the current data table
     * @access public
     * @param mixed $table
     * @return Model
     */
    public function table($table)
    {
        $prefix = $this->tablePrefix;
        if (is_array($table)) {
            $this->options['table'] = $table;
        } elseif (!empty($table)) {
            //will__TABLE_NAME__replaceTo bringPrefixofTable name
            $table = preg_replace_callback("/__([A-Z0-9_-]+)__/sU", function ($match) use ($prefix) {
                return $prefix . strtolower($match[1]);
            }, $table);
            $this->options['table'] = $table;
        }
        return $this;
    }

    /**
     * USINGstand by For multi-table delete
     * @access public
     * @param mixed $using
     * @return Model
     */
    public function using($using)
    {
        $prefix = $this->tablePrefix;
        if (is_array($using)) {
            $this->options['using'] = $using;
        } elseif (!empty($using)) {
            //will__TABLE_NAME__replaceTo bringPrefixofTable name
            $using = preg_replace_callback("/__([A-Z0-9_-]+)__/sU", function ($match) use ($prefix) {
                return $prefix . strtolower($match[1]);
            }, $using);
            $this->options['using'] = $using;
        }
        return $this;
    }

    /**
     * InquireSQLAssembly join
     * @access public
     * @param mixed $join
     * @param string $type JOINTypes of
     * @return Model
     */
    public function join($join, $type = 'INNER')
    {
        $prefix = $this->tablePrefix;
        if (is_array($join)) {
            foreach ($join as $key => &$_join) {
                $_join = preg_replace_callback("/__([A-Z0-9_-]+)__/sU", function ($match) use ($prefix) {
                    return $prefix . strtolower($match[1]);
                }, $_join);
                $_join = false !== stripos($_join, 'JOIN') ? $_join : $type . ' JOIN ' . $_join;
            }
            $this->options['join'] = $join;
        } elseif (!empty($join)) {
            //will__TABLE_NAME__StringreplaceTo bringPrefixofTable name
            $join = preg_replace_callback("/__([A-Z0-9_-]+)__/sU", function ($match) use ($prefix) {
                return $prefix . strtolower($match[1]);
            }, $join);
            $this->options['join'][] = false !== stripos($join, 'JOIN') ? $join : $type . ' JOIN ' . $join;
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
            $prefix = $this->tablePrefix;
            //will__TABLE_NAME__StringreplaceTo bringPrefixofTable name
            $options = preg_replace_callback("/__([A-Z0-9_-]+)__/sU", function ($match) use ($prefix) {
                return $prefix . strtolower($match[1]);
            }, $union);
        } elseif (is_array($union)) {
            if (isset($union[0])) {
                $this->options['union'] = array_merge($this->options['union'], $union);
                return $this;
            } else {
                $options = $union;
            }
        } else {
            E(L('_DATA_TYPE_INVALID_'));
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
        // increaseShortcuttransferthe way cache(10) Equivalent to cache(true, 10)
        if (is_numeric($key) && is_null($expire)) {
            $expire = $key;
            $key = true;
        }
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
            $field = $fields ?: '*';
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
        if (is_null($length) && strpos($offset, ',')) {
            list($offset, $length) = explode(',', $offset);
        }
        $this->options['limit'] = intval($offset) . ($length ? ',' . intval($length) : '');
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
        if (is_null($listRows) && strpos($page, ',')) {
            list($page, $listRows) = explode(',', $page);
        }
        $this->options['page'] = array(intval($page), intval($listRows));
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
     * Get executedSQLStatement
     * @access public
     * @param boolean $fetch Whether to returnsql
     * @return Model
     */
    public function fetchSql($fetch = true)
    {
        $this->options['fetch_sql'] = $fetch;
        return $this;
    }

    /**
     * Parameter binding
     * @access public
     * @param string $key Param Name
     * @param mixed $value BindingofvariableAnd bindingparameter
     * @return Model
     */
    public function bind($key, $value = false)
    {
        if (is_array($key)) {
            $this->options['bind'] = $key;
        } else {
            $num = func_num_args();
            if ($num > 2) {
                $params = func_get_args();
                array_shift($params);
                $this->options['bind'][$key] = $params;
            } else {
                $this->options['bind'][$key] = $value;
            }
        }
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
