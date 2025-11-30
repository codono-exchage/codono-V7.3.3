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
 * ThinkPHPExtended view model
 */
class ViewModel extends Model
{

    protected $viewFields = array();

    /**
     * automaticDetectdata sheetinformation
     * @access protected
     * @return void
     */
    protected function _checkTableInfo()
    {
    }

    /**
     * Get the completedata Sheet Name
     * @access public
     * @return string
     */
    public function getTableName()
    {
        if (empty($this->trueTableName)) {
            $tableName = '';
            foreach ($this->viewFields as $key => $view) {
                // Obtaindata sheetname
                if (isset($view['_table'])) { // 2011/10/17 Add toactualTable namedefinitionstand by canachieve同Onetableofview
                    $tableName .= $view['_table'];
                    $prefix = $this->tablePrefix;
                    $tableName = preg_replace_callback("/__([A-Z_-]+)__/sU", function ($match) use ($prefix) {
                        return $prefix . strtolower($match[1]);
                    }, $tableName);
                } else {
                    $class = $key . 'Model';
                    $Model = class_exists($class) ? new $class() : M($key);
                    $tableName .= $Model->getTableName();
                }
                // tableAlias definition
                $tableName .= !empty($view['_as']) ? ' ' . $view['_as'] : ' ' . $key;
                // stand byON conditiondefinition
                $tableName .= !empty($view['_on']) ? ' ON ' . $view['_on'] : '';
                // DesignationJOINTypes of E.g RIGHT INNER LEFT 下Onetableeffective
                $type = !empty($view['_type']) ? $view['_type'] : '';
                $tableName .= ' ' . strtoupper($type) . ' JOIN ';
                $len = strlen($type . '_JOIN ');
            }
            $tableName = substr($tableName, 0, -$len);
            $this->trueTableName = $tableName;
        }
        return $this->trueTableName;
    }

    /**
     * Expressions filtration method
     * @access protected
     * @param string $options expression
     * @return void
     */
    protected function _options_filter(&$options)
    {
        if (isset($options['field']))
            $options['field'] = $this->checkFields($options['field']);
        else
            $options['field'] = $this->checkFields();
        if (isset($options['group']))
            $options['group'] = $this->checkGroup($options['group']);
        if (isset($options['where']))
            $options['where'] = $this->checkCondition($options['where']);
        if (isset($options['order']))
            $options['order'] = $this->checkOrder($options['order']);
    }

    /**
     * an examinationwhetherdefinitionTheallField
     * @access protected
     * @param string $name Model Name
     * @param array $fields Field Array
     * @return array
     */
    private function _checkFields($name, $fields)
    {
        if (false !== $pos = array_search('*', $fields)) {// definitionallField
            $fields = array_merge($fields, M($name)->getDbFields());
            unset($fields[$pos]);
        }
        return $fields;
    }

    /**
     * an examinationconditionmiddleviewField
     * @access protected
     * @param mixed $data Conditional expression
     * @return array
     */
    protected function checkCondition($where)
    {
        if (is_array($where)) {
            $view = array();
            // an examinationviewField
            foreach ($this->viewFields as $key => $val) {
                $k = isset($val['_as']) ? $val['_as'] : $key;
                $val = $this->_checkFields($key, $val);
                foreach ($where as $name => $value) {
                    if (false !== $field = array_search($name, $val, true)) {
                        // existviewField
                        $_key = is_numeric($field) ? $k . '.' . $name : $k . '.' . $field;
                        $view[$_key] = $value;
                        unset($where[$name]);
                    }
                }
            }
            $where = array_merge($where, $view);
        }
        return $where;
    }

    /**
     * an examinationOrderexpressionmiddleviewField
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
                // ResolveInto viewField
                foreach ($this->viewFields as $name => $val) {
                    $k = isset($val['_as']) ? $val['_as'] : $name;
                    $val = $this->_checkFields($name, $val);
                    if (false !== $_field = array_search($field, $val, true)) {
                        // existviewField
                        $field = is_numeric($_field) ? $k . '.' . $field : $k . '.' . $_field;
                        break;
                    }
                }
                $_order[] = $field . ' ' . $sort;
            }
            $order = implode(',', $_order);
        }
        return $order;
    }

    /**
     * an examinationGroupexpressionmiddleviewField
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
                // ResolveInto viewField
                foreach ($this->viewFields as $name => $val) {
                    $k = isset($val['_as']) ? $val['_as'] : $name;
                    $val = $this->_checkFields($name, $val);
                    if (false !== $_field = array_search($field, $val, true)) {
                        // existviewField
                        $field = is_numeric($_field) ? $k . '.' . $field : $k . '.' . $_field;
                        break;
                    }
                }
                $_group[] = $field;
            }
            $group = implode(',', $_group);
        }
        return $group;
    }

    /**
     * an examinationfieldsexpressionmiddleviewField
     * @access protected
     * @param string $fields Field
     * @return string
     */
    protected function checkFields($fields = '')
    {
        if (empty($fields) || '*' == $fields) {
            // ObtainView AllField
            $fields = array();
            foreach ($this->viewFields as $name => $val) {
                $k = isset($val['_as']) ? $val['_as'] : $name;
                $val = $this->_checkFields($name, $val);
                foreach ($val as $key => $field) {
                    if (is_numeric($key)) {
                        $fields[] = $k . '.' . $field . ' AS ' . $field;
                    } elseif ('_' != substr($key, 0, 1)) {
                        // With_The beginning offorspecialdefinition
                        if (false !== strpos($key, '*') || false !== strpos($key, '(') || false !== strpos($key, '.')) {
                            //in casecontain* or useThesqlmethod Is no longerAdd tofrontofTable name
                            $fields[] = $key . ' AS ' . $field;
                        } else {
                            $fields[] = $k . '.' . $key . ' AS ' . $field;
                        }
                    }
                }
            }
            $fields = implode(',', $fields);
        } else {
            if (!is_array($fields))
                $fields = explode(',', $fields);
            // ResolveInto viewField
            $array = array();
            foreach ($fields as $key => $field) {
                if (strpos($field, '(') || strpos(strtolower($field), ' as ')) {
                    // useThefunctionorSlug
                    $array[] = $field;
                    unset($fields[$key]);
                }
            }
            foreach ($this->viewFields as $name => $val) {
                $k = isset($val['_as']) ? $val['_as'] : $name;
                $val = $this->_checkFields($name, $val);
                foreach ($fields as $key => $field) {
                    if (false !== $_field = array_search($field, $val, true)) {
                        // existviewField
                        if (is_numeric($_field)) {
                            $array[] = $k . '.' . $field . ' AS ' . $field;
                        } elseif ('_' != substr($_field, 0, 1)) {
                            if (false !== strpos($_field, '*') || false !== strpos($_field, '(') || false !== strpos($_field, '.'))
                                //in casecontain* or useThesqlmethod Is no longerAdd tofrontofTable name
                                $array[] = $_field . ' AS ' . $field;
                            else
                                $array[] = $k . '.' . $_field . ' AS ' . $field;
                        }
                    }
                }
            }
            $fields = implode(',', $array);
        }
        return $fields;
    }
}