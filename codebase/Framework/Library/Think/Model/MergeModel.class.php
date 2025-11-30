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
 * ThinkPHP Aggregation model extension
 */
class MergeModel extends Model
{

    protected $modelList = array();    //  Models included in the list The firstOnemust beMain table model
    protected $masterModel = '';         //  the Lordmodel
    protected $joinType = 'INNER';    //  Aggregation model queriesJOINTypes of
    protected $fk = '';         //  Foreign key Name The default isthe LordTable name_id
    protected $mapFields = array();    //  needdeal withofModel MappingField,avoid confusion array( id => 'user.id'  )

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
        parent::__construct($name, $tablePrefix, $connection);
        // Aggregate field information model
        if (empty($this->fields) && !empty($this->modelList)) {
            $fields = array();
            foreach ($this->modelList as $model) {
                // Gets the field of Information Model
                $result = $this->db->getFields(M($model)->getTableName());
                $_fields = array_keys($result);
                // $this->mapFields  =   array_intersect($fields,$_fields);
                $fields = array_merge($fields, $_fields);
            }
            $this->fields = $fields;
        }

        // Set upThe firstOnemodelforMain table model
        if (empty($this->masterModel) && !empty($this->modelList)) {
            $this->masterModel = $this->modelList[0];
        }
        // Primary tablePrimary key Name
        $this->pk = M($this->masterModel)->getPk();

        // Set updefaultForeign key Name Support only a single foreign key
        if (empty($this->fk)) {
            $this->fk = strtolower($this->masterModel) . '_id';
        }

    }

    /**
     * Get the completedata Sheet Name
     * @access public
     * @return string
     */
    public function getTableName()
    {
        if (empty($this->trueTableName)) {
            $tableName = array();
            $models = $this->modelList;
            foreach ($models as $model) {
                $tableName[] = M($model)->getTableName() . ' ' . $model;
            }
            $this->trueTableName = implode(',', $tableName);
        }
        return $this->trueTableName;
    }

    /**
     * automaticDetectdata sheetinformation
     * @access protected
     * @return void
     */
    protected function _checkTableInfo()
    {
    }

    /**
     * Add aggregated data
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
        // Start affairs
        $this->startTrans();
        // Data written to the master table
        $result = M($this->masterModel)->strict(false)->add($data);
        if ($result) {
            // Foreign Key Data write
            $data[$this->fk] = $result;
            $models = $this->modelList;
            array_shift($models);
            // Schedule data is written
            foreach ($models as $model) {
                $res = M($model)->strict(false)->add($data);
                if (!$res) {
                    $this->rollback();
                    return false;
                }
            }
            // Commit the transaction
            $this->commit();
        } else {
            $this->rollback();
            return false;
        }
        return $result;
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
                    unset($data[$key]);
                } elseif (array_key_exists($key, $this->mapFields)) {
                    // needdeal withMappingField
                    $data[$this->mapFields[$key]] = $val;
                    unset($data[$key]);
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

    /**
     * StorageAggregation modeldata
     * @access public
     * @param mixed $data data
     * @param array $options expression
     * @return boolean
     */
    public function save($data = '', $options = array())
    {
        // The primary key of the master table updates
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
        if (empty($data)) {
            // Nodatathen不carried out
            $this->error = L('_DATA_TYPE_INVALID_');
            return false;
        }
        // in caseexistPrimary keydata thenautomaticAs aUpdatecondition
        $pk = $this->pk;
        if (isset($data[$pk])) {
            $where[$pk] = $data[$pk];
            $options['where'] = $where;
            unset($data[$pk]);
        }
        $options['join'] = '';
        $options = $this->_parseOptions($options);
        // Update operation does not useJOIN 
        $options['table'] = $this->getTableName();

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

    /**
     * Delete data aggregation model
     * @access public
     * @param mixed $options expression
     * @return mixed
     */
    public function delete($options = array())
    {
        $pk = $this->pk;
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
            $options = array();
            $options['where'] = $where;
        }
        // Analysis of expression
        $options['join'] = '';
        $options = $this->_parseOptions($options);
        if (empty($options['where'])) {
            // in caseconditionforair Withoutdeleteoperating unlessSet up 1=1
            return false;
        }
        if (is_array($options['where']) && isset($options['where'][$pk])) {
            $pkValue = $options['where'][$pk];
        }

        $options['table'] = implode(',', $this->modelList);
        $options['using'] = $this->getTableName();
        if (false === $this->_before_delete($options)) {
            return false;
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

    /**
     * Expressions filtration method
     * @access protected
     * @param string $options expression
     * @return void
     */
    protected function _options_filter(&$options)
    {
        if (!isset($options['join'])) {
            $models = $this->modelList;
            array_shift($models);
            foreach ($models as $model) {
                $options['join'][] = $this->joinType . ' JOIN ' . M($model)->getTableName() . ' ' . $model . ' ON ' . $this->masterModel . '.' . $this->pk . ' = ' . $model . '.' . $this->fk;
            }
        }
        $options['table'] = M($this->masterModel)->getTableName() . ' ' . $this->masterModel;
        $options['field'] = $this->checkFields(isset($options['field']) ? $options['field'] : '');
        if (isset($options['group']))
            $options['group'] = $this->checkGroup($options['group']);
        if (isset($options['where']))
            $options['where'] = $this->checkCondition($options['where']);
        if (isset($options['order']))
            $options['order'] = $this->checkOrder($options['order']);
    }

    /**
     * an examinationconditionmiddlepolymerizationField
     * @access protected
     * @param mixed $data Conditional expression
     * @return array
     */
    protected function checkCondition($where)
    {
        if (is_array($where)) {
            $view = array();
            foreach ($where as $name => $value) {
                if (array_key_exists($name, $this->mapFields)) {
                    // needdeal withMappingField
                    $view[$this->mapFields[$name]] = $value;
                    unset($where[$name]);
                }
            }
            $where = array_merge($where, $view);
        }
        return $where;
    }

    /**
     * an examinationOrderexpressionmiddlepolymerizationField
     * @access protected
     * @param string $order Field
     * @return string
     */
    protected function checkOrder($order = '')
    {
        if (is_string($order) && !empty($order)) {
            $orders = explode(',', $order);
            $_order = array();
            foreach ($orders as $order) {
                $array = explode(' ', trim($order));
                $field = $array[0];
                $sort = isset($array[1]) ? $array[1] : 'ASC';
                if (array_key_exists($field, $this->mapFields)) {
                    // needdeal withMappingField
                    $field = $this->mapFields[$field];
                }
                $_order[] = $field . ' ' . $sort;
            }
            $order = implode(',', $_order);
        }
        return $order;
    }

    /**
     * an examinationGroupexpressionmiddlepolymerizationField
     * @access protected
     * @param string $group Field
     * @return string
     */
    protected function checkGroup($group = '')
    {
        if (!empty($group)) {
            $groups = explode(',', $group);
            $_group = array();
            foreach ($groups as $field) {
                // Resolveto makepolymerizationField
                if (array_key_exists($field, $this->mapFields)) {
                    // needdeal withMappingField
                    $field = $this->mapFields[$field];
                }
                $_group[] = $field;
            }
            $group = implode(',', $_group);
        }
        return $group;
    }

    /**
     * an examinationfieldsexpressionmiddlepolymerizationField
     * @access protected
     * @param string $fields Field
     * @return string
     */
    protected function checkFields($fields = '')
    {
        if (empty($fields) || '*' == $fields) {
            // Get All aggregate field
            $fields = $this->fields;
        }
        if (!is_array($fields))
            $fields = explode(',', $fields);

        // Resolveto makepolymerizationField
        $array = array();
        foreach ($fields as $field) {
            if (array_key_exists($field, $this->mapFields)) {
                // needdeal withMappingField
                $array[] = $this->mapFields[$field] . ' AS ' . $field;
            } else {
                $array[] = $field;
            }
        }
        $fields = implode(',', $array);
        return $fields;
    }

    /**
     * Obtaindata sheetFieldinformation
     * @access public
     * @return array
     */
    public function getDbFields()
    {
        return $this->fields;
    }

}