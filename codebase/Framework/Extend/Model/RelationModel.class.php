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
 * ThinkPHPExtended Relational Model
 * @category   Extend
 * @package  Extend
 * @subpackage  Model
 * @author    liu21st <liu21st@gmail.com>
 */
define('HAS_ONE', 1);
define('BELONGS_TO', 2);
define('HAS_MANY', 3);
define('MANY_TO_MANY', 4);

class RelationModel extends Model
{
    // Relateddefinition
    protected $_link = array();

    /**
     * Dynamic method implementation
     * @access public
     * @param string $method Method name
     * @param array $args Call parameters
     * @return mixed
     */
    public function __call($method, $args)
    {
        if (strtolower(substr($method, 0, 8)) == 'relation') {
            $type = strtoupper(substr($method, 8));
            if (in_array($type, array('ADD', 'SAVE', 'DEL'), true)) {
                array_unshift($args, $type);
                return call_user_func_array(array(&$this, 'opRelation'), $args);
            }
        } else {
            return parent::__call($method, $args);
        }
    }

    /**
     * Been associateddata Sheet Name
     * @access public
     * @return string
     */
    public function getRelationTableName($relation)
    {
        $relationTable = !empty($this->tablePrefix) ? $this->tablePrefix : '';
        $relationTable .= $this->tableName ? $this->tableName : $this->name;
        $relationTable .= '_' . $relation->getModelName();
        return strtolower($relationTable);
    }

    // InquiresuccessAfterCallbackmethod
    protected function _after_find(&$result, $options)
    {
        // ObtainRelateddata And attached toresultin
        if (!empty($options['link']))
            $this->getRelation($result, $options['link']);
    }

    // Query data setssuccessAfterCallbackmethod
    protected function _after_select(&$result, $options)
    {
        // ObtainRelateddata And attached toresultin
        if (!empty($options['link']))
            $this->getRelations($result, $options['link']);
    }

    // WritesuccessAfterCallbackmethod
    protected function _after_insert($data, $options)
    {
        // RelatedWrite
        if (!empty($options['link']))
            $this->opRelation('ADD', $data, $options['link']);
    }

    // UpdatesuccessAfterCallbackmethod
    protected function _after_update($data, $options)
    {
        // RelatedUpdate
        if (!empty($options['link']))
            $this->opRelation('SAVE', $data, $options['link']);
    }

    // deletesuccessAfterCallbackmethod
    protected function _after_delete($data, $options)
    {
        // Relateddelete
        if (!empty($options['link']))
            $this->opRelation('DEL', $data, $options['link']);
    }

    /**
     * CorrectStorageToDatabasedataEnterRowdeal with
     * @access protected
     * @param mixed $data Data to be operated
     * @return boolean
     */
    protected function _facade($data)
    {
        $this->_before_write($data);
        return $data;
    }

    /**
     * ObtainReturn data setsofRelatedrecording
     * @access protected
     * @param array $resultSet Return data
     * @param string|array $name Relevance Name
     * @return array
     */
    protected function getRelations(&$resultSet, $name = '')
    {
        // ObtainrecordingCollectionofPrimary keyList
        foreach ($resultSet as $key => $val) {
            $val = $this->getRelation($val, $name);
            $resultSet[$key] = $val;
        }
        return $resultSet;
    }

    /**
     * ObtainReturn dataofRelatedrecording
     * @access protected
     * @param mixed $result Return data
     * @param string|array $name Relevance Name
     * @param boolean $return Whether to returnRelateddataitself
     * @return array
     */
    protected function getRelation(&$result, $name = '', $return = false)
    {
        if (!empty($this->_link)) {
            foreach ($this->_link as $key => $val) {
                $mappingName = !empty($val['mapping_name']) ? $val['mapping_name'] : $key; // Mappingname
                if (empty($name) || true === $name || $mappingName == $name || (is_array($name) && in_array($mappingName, $name))) {
                    $mappingType = !empty($val['mapping_type']) ? $val['mapping_type'] : $val;  //  Association type
                    $mappingClass = !empty($val['class_name']) ? $val['class_name'] : $key;            //  Relatedclassname
                    $mappingFields = !empty($val['mapping_fields']) ? $val['mapping_fields'] : '*';     // MappingField
                    $mappingCondition = !empty($val['condition']) ? $val['condition'] : '1=1';          // Relatedcondition
                    $mappingKey = !empty($val['mapping_key']) ? $val['mapping_key'] : $this->getPk(); // Relatedkey Name
                    if (strtoupper($mappingClass) == strtoupper($this->name)) {
                        // fromQuoteRelated Obtainfatherkey Name
                        $mappingFk = !empty($val['parent_key']) ? $val['parent_key'] : 'parent_id';
                    } else {
                        $mappingFk = !empty($val['foreign_key']) ? $val['foreign_key'] : strtolower($this->name) . '_id';     //  Foreign key
                    }
                    // ObtainRelational ModelObjects
                    $model = D($mappingClass);
                    switch ($mappingType) {
                        case HAS_ONE:
                            $pk = $result[$mappingKey];
                            $mappingCondition .= " AND {$mappingFk}='{$pk}'";
                            $relationData = $model->where($mappingCondition)->field($mappingFields)->find();
                            if (!empty($val['relation_deep'])) {
                                $model->getRelation($relationData, $val['relation_deep']);
                            }
                            break;
                        case BELONGS_TO:
                            if (strtoupper($mappingClass) == strtoupper($this->name)) {
                                // fromQuoteRelated Obtainfatherkey Name
                                $mappingFk = !empty($val['parent_key']) ? $val['parent_key'] : 'parent_id';
                            } else {
                                $mappingFk = !empty($val['foreign_key']) ? $val['foreign_key'] : strtolower($model->getModelName()) . '_id';     //  Foreign key
                            }
                            $fk = $result[$mappingFk];
                            $mappingCondition .= " AND {$model->getPk()}='{$fk}'";
                            $relationData = $model->where($mappingCondition)->field($mappingFields)->find();
                            if (!empty($val['relation_deep'])) {
                                $model->getRelation($relationData, $val['relation_deep']);
                            }
                            break;
                        case HAS_MANY:
                            $pk = $result[$mappingKey];
                            $mappingCondition .= " AND {$mappingFk}='{$pk}'";
                            $mappingOrder = !empty($val['mapping_order']) ? $val['mapping_order'] : '';
                            $mappingLimit = !empty($val['mapping_limit']) ? $val['mapping_limit'] : '';
                            // DelayObtainRelatedrecording
                            $relationData = $model->where($mappingCondition)->field($mappingFields)->order($mappingOrder)->limit($mappingLimit)->select();
                            if (!empty($val['relation_deep'])) {
                                foreach ($relationData as $key => $data) {
                                    $model->getRelation($data, $val['relation_deep']);
                                    $relationData[$key] = $data;
                                }
                            }
                            break;
                        case MANY_TO_MANY:
                            $pk = $result[$mappingKey];
                            $mappingCondition = " {$mappingFk}='{$pk}'";
                            $mappingOrder = $val['mapping_order'];
                            $mappingLimit = $val['mapping_limit'];
                            $mappingRelationFk = $val['relation_foreign_key'] ? $val['relation_foreign_key'] : $model->getModelName() . '_id';
                            $mappingRelationTable = $val['relation_table'] ? $val['relation_table'] : $this->getRelationTableName($model);
                            $sql = "SELECT b.{$mappingFields} FROM {$mappingRelationTable} AS a, " . $model->getTableName() . " AS b WHERE a.{$mappingRelationFk} = b.{$model->getPk()} AND a.{$mappingCondition}";
                            if (!empty($val['condition'])) {
                                $sql .= ' AND ' . $val['condition'];
                            }
                            if (!empty($mappingOrder)) {
                                $sql .= ' ORDER BY ' . $mappingOrder;
                            }
                            if (!empty($mappingLimit)) {
                                $sql .= ' LIMIT ' . $mappingLimit;
                            }
                            $relationData = $this->query($sql);
                            if (!empty($val['relation_deep'])) {
                                foreach ($relationData as $key => $data) {
                                    $model->getRelation($data, $val['relation_deep']);
                                    $relationData[$key] = $data;
                                }
                            }
                            break;
                    }
                    if (!$return) {
                        if (isset($val['as_fields']) && in_array($mappingType, array(HAS_ONE, BELONGS_TO))) {
                            // stand bydirectThe associationofField ValuesMapped toData ObjectsmiddleAField
                            // onlystand byHAS_ONE BELONGS_TO
                            $fields = explode(',', $val['as_fields']);
                            foreach ($fields as $field) {
                                if (strpos($field, ':')) {
                                    list($relationName, $nick) = explode(':', $field);
                                    $result[$nick] = $relationData[$relationName];
                                } else {
                                    $result[$field] = $relationData[$field];
                                }
                            }
                        } else {
                            $result[$mappingName] = $relationData;
                        }
                        unset($relationData);
                    } else {
                        return $relationData;
                    }
                }
            }
        }
        return $result;
    }

    /**
     * Data associated with the operation
     * @access protected
     * @param string $opType Operation method ADD SAVE DEL
     * @param mixed $data Data Objects
     * @param string $name Relevance Name
     * @return mixed
     */
    protected function opRelation($opType, $data = '', $name = '')
    {
        $result = false;
        if (empty($data) && !empty($this->data)) {
            $data = $this->data;
        } elseif (!is_array($data)) {
            // datainvalidreturn
            return false;
        }
        if (!empty($this->_link)) {
            // Traverse associationsdefinition
            foreach ($this->_link as $key => $val) {
                // operatingFormulateAssociation type
                $mappingName = $val['mapping_name'] ? $val['mapping_name'] : $key; // Mappingname
                if (empty($name) || true === $name || $mappingName == $name || (is_array($name) && in_array($mappingName, $name))) {
                    // operatingFormulateofRelated
                    $mappingType = !empty($val['mapping_type']) ? $val['mapping_type'] : $val;  //  Association type
                    $mappingClass = !empty($val['class_name']) ? $val['class_name'] : $key;            //  Relatedclassname
                    $mappingKey = !empty($val['mapping_key']) ? $val['mapping_key'] : $this->getPk(); // Relatedkey Name
                    // Current dataObjectsPrimary keyvalue
                    $pk = $data[$mappingKey];
                    if (strtoupper($mappingClass) == strtoupper($this->name)) {
                        // fromQuoteRelated Obtainfatherkey Name
                        $mappingFk = !empty($val['parent_key']) ? $val['parent_key'] : 'parent_id';
                    } else {
                        $mappingFk = !empty($val['foreign_key']) ? $val['foreign_key'] : strtolower($this->name) . '_id';     //  Foreign key
                    }
                    if (!empty($val['condition'])) {
                        $mappingCondition = $val['condition'];
                    } else {
                        $mappingCondition = array();
                        $mappingCondition[$mappingFk] = $pk;
                    }
                    // ObtainRelatedmodelObjects
                    $model = D($mappingClass);
                    $mappingData = isset($data[$mappingName]) ? $data[$mappingName] : false;
                    if (!empty($mappingData) || $opType == 'DEL') {
                        switch ($mappingType) {
                            case HAS_ONE:
                                switch (strtoupper($opType)) {
                                    case 'ADD': // increaseRelateddata
                                        $mappingData[$mappingFk] = $pk;
                                        $result = $model->add($mappingData);
                                        break;
                                    case 'SAVE':    // UpdateRelateddata
                                        $result = $model->where($mappingCondition)->save($mappingData);
                                        break;
                                    case 'DEL': // according toForeign keydeleteRelateddata
                                        $result = $model->where($mappingCondition)->delete();
                                        break;
                                }
                                break;
                            case BELONGS_TO:
                                break;
                            case HAS_MANY:
                                switch (strtoupper($opType)) {
                                    case 'ADD'   :  // increaseRelateddata
                                        $model->startTrans();
                                        foreach ($mappingData as $val) {
                                            $val[$mappingFk] = $pk;
                                            $result = $model->add($val);
                                        }
                                        $model->commit();
                                        break;
                                    case 'SAVE' :   // UpdateRelateddata
                                        $model->startTrans();
                                        $pk = $model->getPk();
                                        foreach ($mappingData as $vo) {
                                            if (isset($vo[$pk])) {// Updatedata
                                                $mappingCondition = "$pk ={$vo[$pk]}";
                                                $result = $model->where($mappingCondition)->save($vo);
                                            } else { // New data
                                                $vo[$mappingFk] = $data[$mappingKey];
                                                $result = $model->add($vo);
                                            }
                                        }
                                        $model->commit();
                                        break;
                                    case 'DEL' :    // deleteRelateddata
                                        $result = $model->where($mappingCondition)->delete();
                                        break;
                                }
                                break;
                            case MANY_TO_MANY:
                                $mappingRelationFk = $val['relation_foreign_key'] ? $val['relation_foreign_key'] : $model->getModelName() . '_id';// Related
                                $mappingRelationTable = $val['relation_table'] ? $val['relation_table'] : $this->getRelationTableName($model);
                                if (is_array($mappingData)) {
                                    $ids = array();
                                    foreach ($mappingData as $vo)
                                        $ids[] = $vo[$mappingKey];
                                    $relationId = implode(',', $ids);
                                }
                                switch (strtoupper($opType)) {
                                    case 'ADD': // increaseRelateddata
                                    case 'SAVE':    // UpdateRelateddata
                                        if (isset($relationId)) {
                                            $this->startTrans();
                                            // deleteAssociation Tabledata
                                            $this->table($mappingRelationTable)->where($mappingCondition)->delete();
                                            // insertAssociation Tabledata
                                            $sql = 'INSERT INTO ' . $mappingRelationTable . ' (' . $mappingFk . ',' . $mappingRelationFk . ') SELECT a.' . $this->getPk() . ',b.' . $model->getPk() . ' FROM ' . $this->getTableName() . ' AS a ,' . $model->getTableName() . " AS b where a." . $this->getPk() . ' =' . $pk . ' AND  b.' . $model->getPk() . ' IN (' . $relationId . ") ";
                                            $result = $model->execute($sql);
                                            if (false !== $result)
                                                // Commit the transaction
                                                $this->commit();
                                            else
                                                // Transaction rollback
                                                $this->rollback();
                                        }
                                        break;
                                    case 'DEL': // according toForeign keydeleteIntermediate association tabledata
                                        $result = $this->table($mappingRelationTable)->where($mappingCondition)->delete();
                                        break;
                                }
                                break;
                        }
                        if (!empty($val['relation_deep'])) {
                            $model->opRelation($opType, $mappingData, $val['relation_deep']);
                        }
                    }
                }
            }
        }
        return $result;
    }

    /**
     * AssociateInquire
     * @access public
     * @param mixed $name Relevance Name
     * @return Model
     */
    public function relation($name)
    {
        $this->options['link'] = $name;
        return $this;
    }

    /**
     * Associated data acquisition After only for queries
     * @access public
     * @param string $name Relevance Name
     * @return array
     */
    public function relationGet($name)
    {
        if (empty($this->data))
            return false;
        return $this->getRelation($this->data, $name, true);
    }
}