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
namespace Think\Model;

use Think\Model;

/**
 * Advanced model extensions
 */
class AdvModel extends Model
{
    protected $optimLock = 'lock_version';
    protected $returnType = 'array';
    protected $blobFields = array();
    protected $blobValues = null;
    protected $serializeField = array();
    protected $readonlyField = array();
    protected $_filter = array();
    protected $partition = array();

    public function __construct($name = '', $tablePrefix = '', $connection = '')
    {
        if ('' !== $name || is_subclass_of($this, 'AdvModel')) {
            // ifAdvModelSubclassorFor incomingModel NamethenObtainFieldCache
        } else {
            // airofmodel shut downFieldCache
            $this->autoCheckFields = false;
        }
        parent::__construct($name, $tablePrefix, $connection);
    }

    /**
     * Use__callMethod overloading Achieve some specialModelmethod (Magic method)
     * @access public
     * @param string $method Method name
     * @param mixed $args Call parameters
     * @return mixed
     */
    public function __call($method, $args)
    {
        if (strtolower(substr($method, 0, 3)) == 'top') {
            // ObtainbeforeNRecords
            $count = substr($method, 3);
            array_unshift($args, $count);
            return call_user_func_array(array(&$this, 'topN'), $args);
        } else {
            return parent::__call($method, $args);
        }
    }

    /**
     * CorrectStorageToDatabasedataEnterRowdeal with
     * @access protected
     * @param mixed $data Data to be operated
     * @return boolean
     */
    protected function _facade($data)
    {
        // an examinationSerializationField
        $data = $this->serializeField($data);
        return parent::_facade($data);
    }

    // InquiresuccessAfterCallbackmethod
    protected function _after_find(&$result, $options = '')
    {
        // an examinationSerializationField
        $this->checkSerializeField($result);
        // ObtaintextField
        $this->getBlobFields($result);
        // an examinationFieldfilter
        $result = $this->getFilterFields($result);
        // CacheOptimistic locking
        $this->cacheLockVersion($result);
    }

    // Query data setssuccessAfterCallbackmethod
    protected function _after_select(&$resultSet, $options = '')
    {
        // an examinationSerializationField
        $resultSet = $this->checkListSerializeField($resultSet);
        // ObtaintextField
        $resultSet = $this->getListBlobFields($resultSet);
        // an examinationListFieldfilter
        $resultSet = $this->getFilterListFields($resultSet);
    }

    // WritebeforeofCallbackmethod
    protected function _before_insert(&$data, $options = '')
    {
        // Record optimistic locking
        $data = $this->recordLockVersion($data);
        // an examinationtextField
        $data = $this->checkBlobFields($data);
        // an examinationFieldfilter
        $data = $this->setFilterFields($data);
    }

    protected function _after_insert($data, $options)
    {
        // StoragetextField
        $this->saveBlobFields($data);
    }

    // UpdatebeforeofCallbackmethod
    protected function _before_update(&$data, $options = '')
    {
        // Check the optimistic locking
        $pk = $this->getPK();
        if (isset($options['where'][$pk])) {
            $id = $options['where'][$pk];
            if (!$this->checkLockVersion($id, $data)) {
                return false;
            }
        }
        // an examinationtextField
        $data = $this->checkBlobFields($data);
        // Check the read-only field
        $data = $this->checkReadonlyField($data);
        // an examinationFieldfilter
        $data = $this->setFilterFields($data);
    }

    protected function _after_update($data, $options)
    {
        // StoragetextField
        $this->saveBlobFields($data);
    }

    protected function _after_delete($data, $options)
    {
        // deleteBlobdata
        $this->delBlobFields($data);
    }

    /**
     * Record optimistic locking
     * @access protected
     * @param array $data Data Objects
     * @return array
     */
    protected function recordLockVersion($data)
    {
        // Record optimistic locking
        if ($this->optimLock && !isset($data[$this->optimLock])) {
            if (in_array($this->optimLock, $this->fields, true)) {
                $data[$this->optimLock] = 0;
            }
        }
        return $data;
    }

    /**
     * CacheOptimistic locking
     * @access protected
     * @param array $data Data Objects
     * @return void
     */
    protected function cacheLockVersion($data)
    {
        if ($this->optimLock) {
            if (isset($data[$this->optimLock]) && isset($data[$this->getPk()])) {
                // onlywhenexistOptimistic lockingFieldwithPrimary keyHave valueofWhen itRecord optimistic locking
                $_SESSION[$this->name . '_' . $data[$this->getPk()] . '_lock_version'] = $data[$this->optimLock];
            }
        }
    }

    /**
     * Check the optimistic locking
     * @access protected
     * @param inteter $id Current primary key
     * @param array $data Current data
     * @return mixed
     */
    protected function checkLockVersion($id, &$data)
    {
        // Check the optimistic locking
        $identify = $this->name . '_' . $id . '_lock_version';
        if ($this->optimLock && isset($_SESSION[$identify])) {
            $lock_version = $_SESSION[$identify];
            $vo = $this->field($this->optimLock)->find($id);
            $_SESSION[$identify] = $lock_version;
            $curr_version = $vo[$this->optimLock];
            if (isset($curr_version)) {
                if ($curr_version > 0 && $lock_version != $curr_version) {
                    // recordingalreadyUpdate
                    $this->error = L('_RECORD_HAS_UPDATE_');
                    return false;
                } else {
                    // UpdateOptimistic locking
                    $save_version = $data[$this->optimLock];
                    if ($save_version != $lock_version + 1) {
                        $data[$this->optimLock] = $lock_version + 1;
                    }
                    $_SESSION[$identify] = $lock_version + 1;
                }
            }
        }
        return true;
    }

    /**
     * Find agoNRecords
     * @access public
     * @param integer $count Number of records
     * @param array $options Query Expression
     * @return array
     */
    public function topN($count, $options = array())
    {
        $options['limit'] = $count;
        return $this->select($options);
    }

    /**
     * InquireThe first qualifyingNRecords
     * 0 It represents the first record -1 It represents the last record
     * @access public
     * @param integer $position Record position
     * @param array $options Query Expression
     * @return mixed
     */
    public function getN($position = 0, $options = array())
    {
        if ($position >= 0) { // ForwardSeek
            $options['limit'] = $position . ',1';
            $list = $this->select($options);
            return $list ? $list[0] : false;
        } else { // ReverseSeek
            $list = $this->select($options);
            return $list ? $list[count($list) - abs($position)] : false;
        }
    }

    /**
     * ObtainSatisfyconditionofArticlerecording
     * @access public
     * @param array $options Query Expression
     * @return mixed
     */
    public function first($options = array())
    {
        return $this->getN(0, $options);
    }

    /**
     * ObtainSatisfyconditionofThe last onerecording
     * @access public
     * @param array $options Query Expression
     * @return mixed
     */
    public function last($options = array())
    {
        return $this->getN(-1, $options);
    }

    /**
     * Return data
     * @access public
     * @param array $data data
     * @param string $type Return Type The default is an array
     * @return mixed
     */
    public function returnResult($data, $type = '')
    {
        if ('' === $type)
            $type = $this->returnType;
        switch ($type) {
            case 'array' :
                return $data;
            case 'object':
                return (object)$data;
            default:// allowuserfromdefinitionReturn Type
                if (class_exists($type))
                    return new $type($data);
                else
                    E(L('_CLASS_NOT_EXIST_') . ':' . $type);
        }
    }

    /**
     * ObtaindataofWhen the filterdataField
     * @access protected
     * @param mixed $result Query data
     * @return array
     */
    protected function getFilterFields(&$result)
    {
        if (!empty($this->_filter)) {
            foreach ($this->_filter as $field => $filter) {
                if (isset($result[$field])) {
                    $fun = $filter[1];
                    if (!empty($fun)) {
                        if (isset($filter[2]) && $filter[2]) {
                            // Pass the entireThe number ofaccording toObjectsAs aparameter
                            $result[$field] = call_user_func($fun, $result);
                        } else {
                            // transferAs a parameter value field
                            $result[$field] = call_user_func($fun, $result[$field]);
                        }
                    }
                }
            }
        }
        return $result;
    }

    protected function getFilterListFields(&$resultSet)
    {
        if (!empty($this->_filter)) {
            foreach ($resultSet as $key => $result)
                $resultSet[$key] = $this->getFilterFields($result);
        }
        return $resultSet;
    }

    /**
     * data inputofWhen the filterdataField
     * @access protected
     * @param mixed $result Query data
     * @return array
     */
    protected function setFilterFields($data)
    {
        if (!empty($this->_filter)) {
            foreach ($this->_filter as $field => $filter) {
                if (isset($data[$field])) {
                    $fun = $filter[0];
                    if (!empty($fun)) {
                        if (isset($filter[2]) && $filter[2]) {
                            // Pass the entireThe number ofaccording toObjectsAs aparameter
                            $data[$field] = call_user_func($fun, $data);
                        } else {
                            // transferAs a parameter value field
                            $data[$field] = call_user_func($fun, $data[$field]);
                        }
                    }
                }
            }
        }
        return $data;
    }

    /**
     * List return data
     * @access protected
     * @param array $resultSet data
     * @param string $type Return Type The default is an array
     * @return void
     */
    protected function returnResultSet(&$resultSet, $type = '')
    {
        foreach ($resultSet as $key => $data)
            $resultSet[$key] = $this->returnResult($data, $type);
        return $resultSet;
    }

    protected function checkBlobFields(&$data)
    {
        // an examinationBlobfileStorageField
        if (!empty($this->blobFields)) {
            foreach ($this->blobFields as $field) {
                if (isset($data[$field])) {
                    if (isset($data[$this->getPk()]))
                        $this->blobValues[$this->name . '/' . $data[$this->getPk()] . '_' . $field] = $data[$field];
                    else
                        $this->blobValues[$this->name . '/@?id@_' . $field] = $data[$field];
                    unset($data[$field]);
                }
            }
        }
        return $data;
    }

    /**
     * Obtaining a data setoftextField
     * @access protected
     * @param mixed $resultSet Query data
     * @param string $field Field queries
     * @return void
     */
    protected function getListBlobFields(&$resultSet, $field = '')
    {
        if (!empty($this->blobFields)) {
            foreach ($resultSet as $key => $result) {
                $result = $this->getBlobFields($result, $field);
                $resultSet[$key] = $result;
            }
        }
        return $resultSet;
    }

    /**
     * ObtaindataoftextField
     * @access protected
     * @param mixed $data Query data
     * @param string $field Field queries
     * @return void
     */
    protected function getBlobFields(&$data, $field = '')
    {
        if (!empty($this->blobFields)) {
            $pk = $this->getPk();
            $id = $data[$pk];
            if (empty($field)) {
                foreach ($this->blobFields as $field) {
                    $identify = $this->name . '/' . $id . '_' . $field;
                    $data[$field] = F($identify);
                }
                return $data;
            } else {
                $identify = $this->name . '/' . $id . '_' . $field;
                return F($identify);
            }
        }
    }

    /**
     * StorageFileField mode
     * @access protected
     * @param mixed $data Saved data
     * @return void
     */
    protected function saveBlobFields(&$data)
    {
        if (!empty($this->blobFields)) {
            foreach ($this->blobValues as $key => $val) {
                if (strpos($key, '@?id@'))
                    $key = str_replace('@?id@', $data[$this->getPk()], $key);
                F($key, $val);
            }
        }
    }

    /**
     * deleteFileField mode
     * @access protected
     * @param mixed $data Saved data
     * @param string $field Field queries
     * @return void
     */
    protected function delBlobFields(&$data, $field = '')
    {
        if (!empty($this->blobFields)) {
            $pk = $this->getPk();
            $id = $data[$pk];
            if (empty($field)) {
                foreach ($this->blobFields as $field) {
                    $identify = $this->name . '/' . $id . '_' . $field;
                    F($identify, null);
                }
            } else {
                $identify = $this->name . '/' . $id . '_' . $field;
                F($identify, null);
            }
        }
    }

    /**
     * an examinationSerializationdataField
     * @access protected
     * @param array $data data
     * @return array
     */
    protected function serializeField(&$data)
    {
        // an examinationSerializationField
        if (!empty($this->serializeField)) {
            // definitionthe way  $this->serializeField = array('ser'=>array('name','email'));
            foreach ($this->serializeField as $key => $val) {
                if (empty($data[$key])) {
                    $serialize = array();
                    foreach ($val as $name) {
                        if (isset($data[$name])) {
                            $serialize[$name] = $data[$name];
                            unset($data[$name]);
                        }
                    }
                    if (!empty($serialize)) {
                        $data[$key] = serialize($serialize);
                    }
                }
            }
        }
        return $data;
    }

    // an examinationReturn dataofSerializationField
    protected function checkSerializeField(&$result)
    {
        // an examinationSerializationField
        if (!empty($this->serializeField)) {
            foreach ($this->serializeField as $key => $val) {
                if (isset($result[$key])) {
                    $serialize = unserialize($result[$key]);
                    foreach ($serialize as $name => $value)
                        $result[$name] = $value;
                    unset($serialize, $result[$key]);
                }
            }
        }
        return $result;
    }

    // an examinationdata setofSerializationField
    protected function checkListSerializeField(&$resultSet)
    {
        // an examinationSerializationField
        if (!empty($this->serializeField)) {
            foreach ($this->serializeField as $key => $val) {
                foreach ($resultSet as $k => $result) {
                    if (isset($result[$key])) {
                        $serialize = unserialize($result[$key]);
                        foreach ($serialize as $name => $value)
                            $result[$name] = $value;
                        unset($serialize, $result[$key]);
                        $resultSet[$k] = $result;
                    }
                }
            }
        }
        return $resultSet;
    }

    /**
     * Check the read-only field
     * @access protected
     * @param array $data data
     * @return array
     */
    protected function checkReadonlyField(&$data)
    {
        if (!empty($this->readonlyField)) {
            foreach ($this->readonlyField as $key => $field) {
                if (isset($data[$field]))
                    unset($data[$field]);
            }
        }
        return $data;
    }

    /**
     * Batch executionSQLStatement
     * Batchdeal withofinstructionAllrecognizeforYesexecuteoperating
     * @access public
     * @param array $sql SQLBatch command
     * @return boolean
     */
    public function patchQuery($sql = array())
    {
        if (!is_array($sql)) return false;
        // automaticStart affairsstand by
        $this->startTrans();
        try {
            foreach ($sql as $_sql) {
                $result = $this->execute($_sql);
                if (false === $result) {
                    // occurerrorautomaticRoll back the transaction
                    $this->rollback();
                    return false;
                }
            }
            // Commit the transaction
            $this->commit();
        } catch (ThinkException $e) {
            $this->rollback();
        }
        return true;
    }

    /**
     * To give the sub-tabledata Sheet Name
     * @access public
     * @param array $data Data manipulation
     * @return string
     */
    public function getPartitionTableName($data = array())
    {
        // Correctdata sheetPartitioning
        if (isset($data[$this->partition['field']])) {
            $field = $data[$this->partition['field']];
            switch ($this->partition['type']) {
                case 'id':
                    // according toidRange points table
                    $step = $this->partition['expr'];
                    $seq = floor($field / $step) + 1;
                    break;
                case 'year':
                    // according toYEARS table
                    if (!is_numeric($field)) {
                        $field = strtotime($field);
                    }
                    $seq = date('Y', $field) - $this->partition['expr'] + 1;
                    break;
                case 'mod':
                    // according toidofmoldnumberPoints table
                    $seq = ($field % $this->partition['num']) + 1;
                    break;
                case 'md5':
                    // according tomd5ofSequence points table
                    $seq = (ord(substr(md5($field), 0, 1)) % $this->partition['num']) + 1;
                    break;
                default :
                    if (function_exists($this->partition['type'])) {
                        // stand byDesignationfunction哈希
                        $fun = $this->partition['type'];
                        $seq = (ord(substr($fun($field), 0, 1)) % $this->partition['num']) + 1;
                    } else {
                        // according toFieldoffirstletterThe valuePoints table
                        $seq = (ord($field[0]) % $this->partition['num']) + 1;
                    }
            }
            return $this->getTableName() . '_' . $seq;
        } else {
            // whenSet upofPoints tableFieldOutQuery conditionsordatain
            // JointInquire,have toset up partition['num']
            $tableName = array();
            for ($i = 0; $i < $this->partition['num']; $i++)
                $tableName[] = 'SELECT * FROM ' . $this->getTableName() . '_' . ($i + 1);
            $tableName = '( ' . implode(" UNION ", $tableName) . ') AS ' . $this->name;
            return $tableName;
        }
    }
}