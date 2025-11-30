<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2010 http://topthink.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: support@codono.com
// +----------------------------------------------------------------------

/**
 * MongoModelModel Class
 * AchievedODMwithActiveRecordsmode
 * @category   Extend
 * @package  Extend
 * @subpackage  Model
 * @author    liu21st <liu21st@gmail.com>
 */
class MongoModel extends Model
{
    // Primary keyTypes of
    const TYPE_OBJECT = 1;
    const TYPE_INT = 2;
    const TYPE_STRING = 3;

    // Primary keyname
    protected $pk = '_id';
    // _id Types of 1 Object useMongoIdObjects 2 Int Plastic Automatic support growth 3 String StringHash
    protected $_idType = self::TYPE_OBJECT;
    // Primary keywhetherautomaticincrease stand byIntType primary key
    protected $_autoInc = false;
    // MongoField detection is disabled by default Additional fields can be dynamically
    protected $autoCheckFields = false;
    // chainoperatingmethodList
    protected $methods = array('table', 'order', 'auto', 'filter', 'validate');

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
        } else {
            throw_exception(__CLASS__ . ':' . $method . L('_METHOD_NOT_EXIST_'));
            return;
        }
    }

    /**
     * ObtainFieldinformationandCache Primary keywithIncrementinformationdirectConfiguration
     * @access public
     * @return void
     */
    public function flush()
    {
        // Cachedoes not existthenQuery datatableinformation
        $fields = $this->db->getFields();
        if (!$fields) { // temporarilyNodataUnableObtainFieldinformation Next timeInquire
            return false;
        }
        $this->fields = array_keys($fields);
        $this->fields['_pk'] = $this->pk;
        $this->fields['_autoinc'] = $this->_autoInc;
        foreach ($fields as $key => $val) {
            // recordingFieldTypes of
            $type[$key] = $val['type'];
        }
        // recordingFieldTypes ofinformation
        if (C('DB_FIELDTYPE_CHECK')) $this->fields['_type'] = $type;

        // 2008-3-7 Increase the cache control switch
        if (C('DB_FIELDS_CACHE')) {
            // permanentCachedata sheetinformation
            $db = $this->dbName ? $this->dbName : C('DB_NAME');
            F('_fields/' . $db . '.' . $this->name, $this->fields);
        }
    }

    // data inputbeforeofCallbackmethod includeNew andUpdate
    protected function _before_write(&$data)
    {
        $pk = $this->getPk();
        // according toPrimary keyTypes ofdeal withPrimary keydata
        if (isset($data[$pk]) && $this->_idType == self::TYPE_OBJECT) {
            $data[$pk] = new MongoId($data[$pk]);
        }
    }

    /**
     * countstatistics CoordinatewhereCoherent operation
     * @access public
     * @return integer
     */
    public function count()
    {
        // Analysis of expression
        $options = $this->_parseOptions();
        return $this->db->count($options);
    }

    /**
     * Get the nextID For the automatic growth-oriented
     * @access public
     * @param string $pk Field Name The default primary key
     * @return mixed
     */
    public function getMongoNextId($pk = '')
    {
        if (empty($pk)) {
            $pk = $this->getPk();
        }
        return $this->db->mongo_next_id($pk);
    }

    // insertdatabeforeofCallbackmethod
    protected function _before_insert(&$data, $options)
    {
        // data inputTodatabase
        if ($this->_autoInc && $this->_idType == self::TYPE_INT) { // Primary keyautomaticincrease
            $pk = $this->getPk();
            if (!isset($data[$pk])) {
                $data[$pk] = $this->db->mongo_next_id($pk);
            }
        }
    }

    public function clear()
    {
        return $this->db->clear();
    }

    // InquiresuccessAfterCallbackmethod
    protected function _after_select(&$resultSet, $options)
    {
        array_walk($resultSet, array($this, 'checkMongoId'));
    }

    /**
     * ObtainMongoId
     * @access protected
     * @param array $result Return data
     * @return array
     */
    protected function checkMongoId(&$result)
    {
        if (is_object($result['_id'])) {
            $result['_id'] = $result['_id']->__toString();
        }
        return $result;
    }

    // expressionFilter callbackmethod
    protected function _options_filter(&$options)
    {
        $id = $this->getPk();
        if (isset($options['where'][$id]) && is_scalar($options['where'][$id]) && $this->_idType == self::TYPE_OBJECT) {
            $options['where'][$id] = new MongoId($options['where'][$id]);
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
            $id = $this->getPk();
            $where[$id] = $options;
            $options = array();
            $options['where'] = $where;
        }
        // Analysis of expression
        $options = $this->_parseOptions($options);
        $result = $this->db->find($options);
        if (false === $result) {
            return false;
        }
        if (empty($result)) {// search resultforair
            return null;
        } else {
            $this->checkMongoId($result);
        }
        $this->data = $result;
        $this->_after_find($this->data, $options);
        return $this->data;
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
        return $this->setField($field, array('inc', $step));
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
        return $this->setField($field, array('inc', '-' . $step));
    }

    /**
     * Obtain一RecordsofAField Values
     * @access public
     * @param string $field Field Name
     * @param string $spea Field data symbol interval
     * @return mixed
     */
    public function getField($field, $sepa = null)
    {
        $options['field'] = $field;
        $options = $this->_parseOptions($options);
        if (strpos($field, ',')) { // manyField
            if (is_numeric($sepa)) {// 限setQuantity
                $options['limit'] = $sepa;
                $sepa = null;// Resetfornull Returns an array
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
                        $cols[$name] = is_null($sepa) ? $result : implode($sepa, $result);
                    }
                }
                return $cols;
            }
        } else {
            // Return dataThe number of
            if (true !== $sepa) {// whensepaDesignationfortrueoftime returnalldata
                $options['limit'] = is_numeric($sepa) ? $sepa : 1;
            }            // Seek一Records
            $result = $this->db->find($options);
            if (!empty($result)) {
                if (1 == $options['limit']) return reset($result[0]);
                foreach ($result as $val) {
                    $array[] = $val[$field];
                }
                return $array;
            }
        }
        return null;
    }

    /**
     * carried outMongoinstruction
     * @access public
     * @param array $command instruction
     * @return mixed
     */
    public function command($command)
    {
        return $this->db->command($command);
    }

    /**
     * carried outMongoCode
     * @access public
     * @param string $code MongoCode
     * @param array $args parameter
     * @return mixed
     */
    public function mongoCode($code, $args = array())
    {
        return $this->db->execute($code, $args);
    }

    // databaseSwitching callbackmethod
    protected function _after_db()
    {
        // SwitchCollection
        $this->db->switchCollection($this->getTableName(), $this->dbName ? $this->dbName : C('db_name'));
    }

    /**
     * Get the completedata Sheet Name MongoTable withoutdbName
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
        return $this->trueTableName;
    }
}