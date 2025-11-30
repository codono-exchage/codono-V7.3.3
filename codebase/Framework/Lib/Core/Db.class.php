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
 * ThinkPHP databasemiddle layerImplementation class
 * @category   Think
 * @package  Think
 * @subpackage  Core
 * @author    liu21st <liu21st@gmail.com>
 */
class Db
{
    // databaseTypes of
    protected $dbType = null;
    // whetherautomaticFree result
    protected $autoFree = false;
    // currentoperatingbelong tomodel Name
    protected $model = '_think_';
    // use or notpermanentconnection
    protected $pconnect = false;
    // currentSQLinstruction
    protected $queryStr = '';
    protected $modelSql = [];
    // At lastinsertID
    protected $lastInsID = null;
    // returnorinfluencesrecordingnumber
    protected $numRows = 0;
    // returnFieldnumber
    protected $numCols = 0;
    // Affairsinstructionnumber
    protected $transTimes = 0;
    // Error Messages
    protected $error = '';
    // Database ConnectivityID Support for multipleconnection
    protected $linkID = [];
    // currentconnectionID
    protected $_linkID = null;
    // currentInquireID
    protected $queryID = null;
    // whetheralreadyconnectiondatabase
    protected $connected = false;
    // Database ConnectivityparameterConfiguration
    protected $config = '';
    // databaseexpression
    protected $exp = ['eq' => '=', 'neq' => '<>', 'gt' => '>', 'egt' => '>=', 'lt' => '<', 'elt' => '<=', 'notlike' => 'NOT LIKE', 'like' => 'LIKE', 'in' => 'IN', 'notin' => 'NOT IN', 'not in' => 'NOT IN', 'between' => 'BETWEEN', 'notbetween' => 'NOT BETWEEN', 'not between' => 'NOT BETWEEN'];

    // Query Expression
    protected $selectSql = 'SELECT%DISTINCT% %FIELD% FROM %TABLE%%JOIN%%WHERE%%GROUP%%HAVING%%ORDER%%LIMIT% %UNION%%COMMENT%';
    // Parameter binding
    protected $bind = [];

    /**
     * Made database class instance
     * @static
     * @access public
     * @return mixed Returns the database driver class
     */
    public static function getInstance()
    {
        $args = func_get_args();
        return get_instance_of(__CLASS__, 'factory', $args);
    }

    /**
     * Load database Configuration file or DSN
     * @access public
     * @param mixed $db_config Database configuration information
     * @return string
     */
    public function factory($db_config = '')
    {
        // ReadDatabase Configuration
        $db_config = $this->parseConfig($db_config);
        if (empty($db_config['dbms']))
            throw_exception(L('_NO_DB_CONFIG_'));
        // databaseTypes of
        $this->dbType = ucwords(strtolower($db_config['dbms']));
        $class = 'Db' . $this->dbType;
        // Check the driver class
        if (class_exists($class)) {
            $db = new $class($db_config);
            // Get the currentofdatabaseTypes of
            if ('pdo' != strtolower($db_config['dbms']))
                $db->dbType = strtoupper($this->dbType);
            else
                $db->dbType = $this->_getDsnType($db_config['dsn']);
        } else {
            // classNodefinition
            throw_exception(L('_NO_DB_DRIVER_') . ': ' . $class);
        }
        return $db;
    }

    /**
     * according toDSNAccess to the database type Return capital
     * @access protected
     * @param string $dsn dsnString
     * @return string
     */
    protected function _getDsnType($dsn)
    {
        $match = explode(':', $dsn);
        $dbType = strtoupper(trim($match[0]));
        return $dbType;
    }

    /**
     * analysisDatabase configuration information,stand byArraywithDSN
     * @access private
     * @param mixed $db_config Database configuration information
     * @return string
     */
    private function parseConfig($db_config = '')
    {
        if (!empty($db_config) && is_string($db_config)) {
            // in caseDSNStringIs performedResolve
            $db_config = $this->parseDSN($db_config);
        } elseif (is_array($db_config)) { // ArrayConfiguration
            $db_config = array_change_key_case($db_config);
            $db_config = [
                'dbms' => $db_config['db_type'],
                'username' => $db_config['db_user'],
                'password' => $db_config['db_pwd'],
                'hostname' => $db_config['db_host'],
                'hostport' => $db_config['db_port'],
                'database' => $db_config['db_name'],
                'dsn' => $db_config['db_dsn'],
                'params' => $db_config['db_params'],
            ];
        } elseif (empty($db_config)) {
            // in caseConfigurationforair,Read Configuration file Set up
            if (C('DB_DSN') && 'pdo' != strtolower(C('DB_TYPE'))) { // If you setDB_DSN The priority
                $db_config = $this->parseDSN(C('DB_DSN'));
            } else {
                $db_config = [
                    'dbms' => C('DB_TYPE'),
                    'username' => C('DB_USER'),
                    'password' => C('DB_PWD'),
                    'hostname' => C('DB_HOST'),
                    'hostport' => C('DB_PORT'),
                    'database' => C('DB_NAME'),
                    'dsn' => C('DB_DSN'),
                    'params' => C('DB_PARAMS'),
                ];
            }
        }
        return $db_config;
    }

    /**
     * Initialize the database connection
     * @access protected
     * @param boolean $master Primary server
     * @return void
     */
    protected function initConnect($master = true)
    {
        if (1 == C('DB_DEPLOY_TYPE'))
            // usedistributeddatabase
            $this->_linkID = $this->multiConnect($master);
        else
            // defaultsingledatabase
            if (!$this->connected) $this->_linkID = $this->connect();
    }

    /**
     * Distributed server connection
     * @access protected
     * @param boolean $master Primary server
     * @return void
     */
    protected function multiConnect($master = false)
    {
        static $_config = [];
        if (empty($_config)) {
            // CachedistributedDatabase ConfigurationResolve
            foreach ($this->config as $key => $val) {
                $_config[$key] = explode(',', $val);
            }
        }
        // databaseRead and writewhetherSeparate
        if (C('DB_RW_SEPARATE')) {
            // Master-slaveformulauseSeparate read and write
            if ($master)
                // Primary serverWrite
                $r = floor(mt_rand(0, C('DB_MASTER_NUM') - 1));
            else {
                if (is_numeric(C('DB_SLAVE_NO'))) {// Designationserverread
                    $r = C('DB_SLAVE_NO');
                } else {
                    // readoperatingconnectionFromserver
                    $r = floor(mt_rand(C('DB_MASTER_NUM'), count($_config['hostname']) - 1));   // Eachrandomconnecteddatabase
                }
            }
        } else {
            // Read and writeoperatingDoes not distinguish betweenserver
            $r = floor(mt_rand(0, count($_config['hostname']) - 1));   // Eachrandomconnecteddatabase
        }
        $db_config = [
            'username' => $_config['username'][$r] ?? $_config['username'][0],
            'password' => $_config['password'][$r] ?? $_config['password'][0],
            'hostname' => $_config['hostname'][$r] ?? $_config['hostname'][0],
            'hostport' => $_config['hostport'][$r] ?? $_config['hostport'][0],
            'database' => $_config['database'][$r] ?? $_config['database'][0],
            'dsn' => $_config['dsn'][$r] ?? $_config['dsn'][0],
            'params' => $_config['params'][$r] ?? $_config['params'][0],
        ];
        return $this->connect($db_config, $r);
    }

    /**
     * DSNResolve
     * format: mysql://username:passwd@localhost:3306/DbName
     * @static
     * @access public
     * @param string $dsnStr
     * @return array
     */
    public function parseDSN($dsnStr)
    {
        if (empty($dsnStr)) {
            return false;
        }
        $info = parse_url($dsnStr);
        if ($info['scheme']) {
            $dsn = [
                'dbms' => $info['scheme'],
                'username' => $info['user'] ?? '',
                'password' => $info['pass'] ?? '',
                'hostname' => $info['host'] ?? '',
                'hostport' => $info['port'] ?? '',
                'database' => isset($info['path']) ? substr($info['path'], 1) : ''
            ];
        } else {
            preg_match('/^(.*?)\:\/\/(.*?)\:(.*?)\@(.*?)\:([0-9]{1, 6})\/(.*?)$/', trim($dsnStr), $matches);
            $dsn = [
                'dbms' => $matches[1],
                'username' => $matches[2],
                'password' => $matches[3],
                'hostname' => $matches[4],
                'hostport' => $matches[5],
                'database' => $matches[6]
            ];
        }
        $dsn['dsn'] = ''; // compatibleConfigurationinformationArray
        return $dsn;
    }

    /**
     * Database Debugging The current recordSQL
     * @access protected
     */
    protected function debug()
    {
        $this->modelSql[$this->model] = $this->queryStr;
        $this->model = '_think_';
        // recordingoperatingEndtime
        if (C('DB_SQL_LOG')) {
            G('queryEndTime');
            trace($this->queryStr . ' [ RunTime:' . G('queryStartTime', 'queryEndTime', 6) . 's ]', '', 'SQL');
        }
    }

    /**
     * Set the lock mechanism
     * @access protected
     * @return string
     */
    protected function parseLock($lock = false)
    {
        if (!$lock) return '';
        if ('ORACLE' == $this->dbType) {
            return ' FOR UPDATE NOWAIT ';
        }
        return ' FOR UPDATE ';
    }

    /**
     * setanalysis
     * @access protected
     * @param array $data
     * @return string
     */
    protected function parseSet($data)
    {
        foreach ($data as $key => $val) {
            if (is_array($val) && 'exp' == $val[0]) {
                $set[] = $this->parseKey($key) . '=' . $val[1];
            } elseif (is_scalar($val) || is_null(($val))) { // Filter nonscalardata
                if (C('DB_BIND_PARAM') && 0 !== strpos($val, ':')) {
                    $name = md5($key);
                    $set[] = $this->parseKey($key) . '=:' . $name;
                    $this->bindParam($name, $val);
                } else {
                    $set[] = $this->parseKey($key) . '=' . $this->parseValue($val);
                }
            }
        }
        return ' SET ' . implode(',', $set);
    }

    /**
     * Parameter binding
     * @access protected
     * @param string $name BindingParam Name
     * @param mixed $value Bind values
     * @return void
     */
    protected function bindParam($name, $value)
    {
        $this->bind[':' . $name] = $value;
    }

    /**
     * Parameter binding analysis
     * @access protected
     * @param array $bind
     * @return array
     */
    protected function parseBind($bind)
    {
        $bind = array_merge($this->bind, $bind);
        $this->bind = [];
        return $bind;
    }

    /**
     * Analysis of field names
     * @access protected
     * @param string $key
     * @return string
     */
    protected function parseKey(&$key)
    {
        return $key;
    }

    /**
     * valueanalysis
     * @access protected
     * @param mixed $value
     * @return string
     */
    protected function parseValue($value)
    {
        if (is_string($value)) {
            $value = '\'' . $this->escapeString($value) . '\'';
        } elseif (isset($value[0]) && is_string($value[0]) && strtolower($value[0]) == 'exp') {
            $value = $this->escapeString($value[1]);
        } elseif (is_array($value)) {
            $value = array_map([$this, 'parseValue'], $value);
        } elseif (is_bool($value)) {
            $value = $value ? '1' : '0';
        } elseif (is_null($value)) {
            $value = 'null';
        }
        return $value;
    }

    /**
     * fieldanalysis
     * @access protected
     * @param mixed $fields
     * @return string
     */
    protected function parseField($fields)
    {
        if (is_string($fields) && strpos($fields, ',')) {
            $fields = explode(',', $fields);
        }
        if (is_array($fields)) {
            // perfectArraythe waypassField Nameofstand by
            // stand by 'field1'=>'field2' SuchFieldAlias definition
            $array = [];
            foreach ($fields as $key => $field) {
                if (!is_numeric($key))
                    $array[] = $this->parseKey($key) . ' AS ' . $this->parseKey($field);
                else
                    $array[] = $this->parseKey($field);
            }
            $fieldsStr = implode(',', $array);
        } elseif (is_string($fields) && !empty($fields)) {
            $fieldsStr = $this->parseKey($fields);
        } else {
            $fieldsStr = '*';
        }
        //TODO ifInquireCompleteField,And isjoinThe way,Then put to checkoftable加MoreSlug,so as not toFieldIscover
        return $fieldsStr;
    }

    /**
     * tableanalysis
     * @access protected
     * @param mixed $table
     * @return string
     */
    protected function parseTable($tables)
    {
        if (is_array($tables)) {// stand byAlias definition
            $array = [];
            foreach ($tables as $table => $alias) {
                if (!is_numeric($table))
                    $array[] = $this->parseKey($table) . ' ' . $this->parseKey($alias);
                else
                    $array[] = $this->parseKey($table);
            }
            $tables = $array;
        } elseif (is_string($tables)) {
            $tables = explode(',', $tables);
            array_walk($tables, [&$this, 'parseKey']);
        }
        return implode(',', $tables);
    }

    /**
     * whereanalysis
     * @access protected
     * @param mixed $where
     * @return string
     */
    protected function parseWhere($where)
    {
        $whereStr = '';
        if (is_string($where)) {
            // directuseStringcondition
            $whereStr = $where;
        } else { // useArrayexpression
            $operate = isset($where['_logic']) ? strtoupper($where['_logic']) : '';
            if (in_array($operate, ['AND', 'OR', 'XOR'])) {
                // definitionlogicOperationrule E.g OR XOR AND NOT
                $operate = ' ' . $operate . ' ';
                unset($where['_logic']);
            } else {
                // defaultEnterRow AND Operation
                $operate = ' AND ';
            }
            foreach ($where as $key => $val) {
                $whereStr .= '( ';
                if (is_numeric($key)) {
                    $key = '_complex';
                }
                if (0 === strpos($key, '_')) {
                    // ResolvespecialConditional expression
                    $whereStr .= $this->parseThinkWhere($key, $val);
                } else {
                    // InquireFieldofSecurity filtering
                    if (!preg_match('/^[A-Z_\|\&\-.a-z0-9\(\)\,]+$/', trim($key))) {
                        throw_exception(L('_EXPRESS_ERROR_') . ':' . $key);
                    }
                    // manyconditionstand by
                    $multi = is_array($val) && isset($val['_multi']);
                    $key = trim($key);
                    if (strpos($key, '|')) { // stand by name|title|nickname the waydefinitionInquireField
                        $array = explode('|', $key);
                        $str = [];
                        foreach ($array as $m => $k) {
                            $v = $multi ? $val[$m] : $val;
                            $str[] = '(' . $this->parseWhereItem($this->parseKey($k), $v) . ')';
                        }
                        $whereStr .= implode(' OR ', $str);
                    } elseif (strpos($key, '&')) {
                        $array = explode('&', $key);
                        $str = [];
                        foreach ($array as $m => $k) {
                            $v = $multi ? $val[$m] : $val;
                            $str[] = '(' . $this->parseWhereItem($this->parseKey($k), $v) . ')';
                        }
                        $whereStr .= implode(' AND ', $str);
                    } else {
                        $whereStr .= $this->parseWhereItem($this->parseKey($key), $val);
                    }
                }
                $whereStr .= ' )' . $operate;
            }
            $whereStr = substr($whereStr, 0, -strlen($operate));
        }
        return empty($whereStr) ? '' : ' WHERE ' . $whereStr;
    }

    // whereSub-unit analysis
    protected function parseWhereItem($key, $val)
    {
        $whereStr = '';
        if (is_array($val)) {
            if (is_string($val[0])) {
                $exp = strtolower($val[0]);
                if (preg_match('/^(EQ|NEQ|GT|EGT|LT|ELT)$/i', $val[0])) { // CompareOperation
                    $whereStr .= $key . ' ' . $this->exp[$exp] . ' ' . $this->parseValue($val[1]);
                } elseif (preg_match('/^(NOTLIKE|LIKE)$/i', $val[0])) {// blurrySeek
                    if (is_array($val[1])) {
                        $likeLogic = isset($val[2]) ? strtoupper($val[2]) : 'OR';
                        if (in_array($likeLogic, ['AND', 'OR', 'XOR'])) {
                            $like = [];
                            foreach ($val[1] as $item) {
                                $like[] = $key . ' ' . $this->exp[$exp] . ' ' . $this->parseValue($item);
                            }
                            $whereStr .= '(' . implode(' ' . $likeLogic . ' ', $like) . ')';
                        }
                    } else {
                        $whereStr .= $key . ' ' . $this->exp[$exp] . ' ' . $this->parseValue($val[1]);
                    }
                } elseif ('exp' == $exp) { // useexpression
                    $whereStr .= ' (' . $key . ' ' . $val[1] . ') ';
                } elseif (preg_match('/^(NOTIN|NOT IN|IN)$/i', $val[0])) { // IN Operation
                    if (isset($val[2]) && 'exp' == $val[2]) {
                        $whereStr .= $key . ' ' . $this->exp[$exp] . ' ' . $val[1];
                    } else {
                        if (is_string($val[1])) {
                            $val[1] = explode(',', $val[1]);
                        }
                        $zone = implode(',', $this->parseValue($val[1]));
                        $whereStr .= $key . ' ' . $this->exp[$exp] . ' (' . $zone . ')';
                    }
                } elseif (preg_match('/^(NOTBETWEEN|NOT BETWEEN|BETWEEN)$/i', $val[0])) { // BETWEENOperation
                    $data = is_string($val[1]) ? explode(',', $val[1]) : $val[1];
                    $whereStr .= ' (' . $key . ' ' . $this->exp[$exp] . ' ' . $this->parseValue($data[0]) . ' AND ' . $this->parseValue($data[1]) . ' )';
                } else {
                    throw_exception(L('_EXPRESS_ERROR_') . ':' . $val[0]);
                }
            } else {
                $count = count($val);
                $rule = isset($val[$count - 1]) ? strtoupper($val[$count - 1]) : '';
                if (in_array($rule, ['AND', 'OR', 'XOR'])) {
                    $count = $count - 1;
                } else {
                    $rule = 'AND';
                }
                for ($i = 0; $i < $count; $i++) {
                    $data = is_array($val[$i]) ? $val[$i][1] : $val[$i];
                    if ('exp' == strtolower($val[$i][0])) {
                        $whereStr .= '(' . $key . ' ' . $data . ') ' . $rule . ' ';
                    } else {
                        $op = is_array($val[$i]) ? $this->exp[strtolower($val[$i][0])] : '=';
                        $whereStr .= '(' . $key . ' ' . $op . ' ' . $this->parseValue($data) . ') ' . $rule . ' ';
                    }
                }
                $whereStr = substr($whereStr, 0, -4);
            }
        } else {
            //CorrectStringTypes ofFielduseFuzzy matching
            if (C('DB_LIKE_FIELDS') && preg_match('/^(' . C('DB_LIKE_FIELDS') . ')$/i', $key)) {
                $val = '%' . $val . '%';
                $whereStr .= $key . ' LIKE ' . $this->parseValue($val);
            } else {
                $whereStr .= $key . ' = ' . $this->parseValue($val);
            }
        }
        return $whereStr;
    }

    /**
     * Special Conditions
     * @access protected
     * @param string $key
     * @param mixed $val
     * @return string
     */
    protected function parseThinkWhere($key, $val)
    {
        $whereStr = '';
        switch ($key) {
            case '_string':
                // StringmodeQuery conditions
                $whereStr = $val;
                break;
            case '_complex':
                // complexQuery conditions
                $whereStr = is_string($val) ? $val : substr($this->parseWhere($val), 6);
                break;
            case '_query':
                // StringmodeQuery conditions
                parse_str($val, $where);
                if (isset($where['_logic'])) {
                    $op = ' ' . strtoupper($where['_logic']) . ' ';
                    unset($where['_logic']);
                } else {
                    $op = ' AND ';
                }
                $array = [];
                foreach ($where as $field => $data)
                    $array[] = $this->parseKey($field) . ' = ' . $this->parseValue($data);
                $whereStr = implode($op, $array);
                break;
        }
        return $whereStr;
    }

    /**
     * limitanalysis
     * @access protected
     * @param mixed $lmit
     * @return string
     */
    protected function parseLimit($limit)
    {
        return !empty($limit) ? ' LIMIT ' . $limit . ' ' : '';
    }

    /**
     * joinanalysis
     * @access protected
     * @param mixed $join
     * @return string
     */
    protected function parseJoin($join)
    {
        $joinStr = '';
        if (!empty($join)) {
            if (is_array($join)) {
                foreach ($join as $key => $_join) {
                    if (false !== stripos($_join, 'JOIN'))
                        $joinStr .= ' ' . $_join;
                    else
                        $joinStr .= ' LEFT JOIN ' . $_join;
                }
            } else {
                $joinStr .= ' LEFT JOIN ' . $join;
            }
        }
        //will__TABLE_NAME__SuchStringreplaceTo regularofTable name,And bring the prefix and suffix
        $joinStr = preg_replace('/__([A-Z_-]+)__/eU', C('DB_PREFIX') . "strtolower('$1')", $joinStr);
        return $joinStr;
    }

    /**
     * orderanalysis
     * @access protected
     * @param mixed $order
     * @return string
     */
    protected function parseOrder($order)
    {
        if (is_array($order)) {
            $array = [];
            foreach ($order as $key => $val) {
                if (is_numeric($key)) {
                    $array[] = $this->parseKey($val);
                } else {
                    $array[] = $this->parseKey($key) . ' ' . $val;
                }
            }
            $order = implode(',', $array);
        }
        return !empty($order) ? ' ORDER BY ' . $order : '';
    }

    /**
     * groupanalysis
     * @access protected
     * @param mixed $group
     * @return string
     */
    protected function parseGroup($group)
    {
        return !empty($group) ? ' GROUP BY ' . $group : '';
    }

    /**
     * havinganalysis
     * @access protected
     * @param string $having
     * @return string
     */
    protected function parseHaving($having)
    {
        return !empty($having) ? ' HAVING ' . $having : '';
    }

    /**
     * commentanalysis
     * @access protected
     * @param string $comment
     * @return string
     */
    protected function parseComment($comment)
    {
        return !empty($comment) ? ' /* ' . $comment . ' */' : '';
    }

    /**
     * distinctanalysis
     * @access protected
     * @param mixed $distinct
     * @return string
     */
    protected function parseDistinct($distinct)
    {
        return !empty($distinct) ? ' DISTINCT ' : '';
    }

    /**
     * unionanalysis
     * @access protected
     * @param mixed $union
     * @return string
     */
    protected function parseUnion($union)
    {
        if (empty($union)) return '';
        if (isset($union['_all'])) {
            $str = 'UNION ALL ';
            unset($union['_all']);
        } else {
            $str = 'UNION ';
        }
        foreach ($union as $u) {
            $sql[] = $str . (is_array($u) ? $this->buildSelectSql($u) : $u);
        }
        return implode(' ', $sql);
    }

    /**
     * Insert Record
     * @access public
     * @param mixed $data data
     * @param array $options Parameter expression
     * @param boolean $replace whetherreplace
     * @return false | integer
     */
    public function insert($data, $options = [], $replace = false)
    {
        $values = $fields = [];
        $this->model = $options['model'];
        foreach ($data as $key => $val) {
            if (is_array($val) && 'exp' == $val[0]) {
                $fields[] = $this->parseKey($key);
                $values[] = $val[1];
            } elseif (is_scalar($val) || is_null(($val))) { // Filter nonscalardata
                $fields[] = $this->parseKey($key);
                if (C('DB_BIND_PARAM') && 0 !== strpos($val, ':')) {
                    $name = md5($key);
                    $values[] = ':' . $name;
                    $this->bindParam($name, $val);
                } else {
                    $values[] = $this->parseValue($val);
                }
            }
        }
        $sql = ($replace ? 'REPLACE' : 'INSERT') . ' INTO ' . $this->parseTable($options['table']) . ' (' . implode(',', $fields) . ') VALUES (' . implode(',', $values) . ')';
        $sql .= $this->parseLock($options['lock'] ?? false);
        $sql .= $this->parseComment(!empty($options['comment']) ? $options['comment'] : '');
        return $this->execute($sql, $this->parseBind(!empty($options['bind']) ? $options['bind'] : []));
    }

    /**
     * bySelectRecord inserted
     * @access public
     * @param string $fields wantinsertofdata sheetField Name
     * @param string $table wantinsertofdata Sheet Name
     * @param array $option Query data parameters
     * @return false | integer
     */
    public function selectInsert($fields, $table, $options = [])
    {
        $this->model = $options['model'];
        if (is_string($fields)) $fields = explode(',', $fields);
        array_walk($fields, [$this, 'parseKey']);
        $sql = 'INSERT INTO ' . $this->parseTable($table) . ' (' . implode(',', $fields) . ') ';
        $sql .= $this->buildSelectSql($options);
        return $this->execute($sql, $this->parseBind(!empty($options['bind']) ? $options['bind'] : []));
    }

    /**
     * update record
     * @access public
     * @param mixed $data data
     * @param array $options expression
     * @return false | integer
     */
    public function update($data, $options)
    {
        $this->model = $options['model'];
        $sql = 'UPDATE '
            . $this->parseTable($options['table'])
            . $this->parseSet($data)
            . $this->parseWhere(!empty($options['where']) ? $options['where'] : '')
            . $this->parseOrder(!empty($options['order']) ? $options['order'] : '')
            . $this->parseLimit(!empty($options['limit']) ? $options['limit'] : '')
            . $this->parseLock($options['lock'] ?? false)
            . $this->parseComment(!empty($options['comment']) ? $options['comment'] : '');
        return $this->execute($sql, $this->parseBind(!empty($options['bind']) ? $options['bind'] : []));
    }

    /**
     * Delete Record
     * @access public
     * @param array $options expression
     * @return false | integer
     */
    public function delete($options = [])
    {
        $this->model = $options['model'];
        $sql = 'DELETE FROM '
            . $this->parseTable($options['table'])
            . $this->parseWhere(!empty($options['where']) ? $options['where'] : '')
            . $this->parseOrder(!empty($options['order']) ? $options['order'] : '')
            . $this->parseLimit(!empty($options['limit']) ? $options['limit'] : '')
            . $this->parseLock($options['lock'] ?? false)
            . $this->parseComment(!empty($options['comment']) ? $options['comment'] : '');
        return $this->execute($sql, $this->parseBind(!empty($options['bind']) ? $options['bind'] : []));
    }

    /**
     * Find Record
     * @access public
     * @param array $options expression
     * @return mixed
     */
    public function select($options = [])
    {
        $this->model = $options['model'];
        $sql = $this->buildSelectSql($options);
        $cache = $options['cache'] ?? false;
        if ($cache) { // Query CacheDetect
            $key = is_string($cache['key']) ? $cache['key'] : md5($sql);
            $value = S($key, '', $cache);
            if (false !== $value) {
                return $value;
            }
        }
        $result = $this->query($sql, $this->parseBind(!empty($options['bind']) ? $options['bind'] : []));
        if ($cache && false !== $result) { // Query CacheWrite
            S($key, $result, $cache);
        }
        return $result;
    }

    /**
     * Generating a querySQL
     * @access public
     * @param array $options expression
     * @return string
     */
    public function buildSelectSql($options = [])
    {
        if (isset($options['page'])) {
            // according toPagesComputelimit
            if (strpos($options['page'], ',')) {
                list($page, $listRows) = explode(',', $options['page']);
            } else {
                $page = $options['page'];
            }
            $page = $page ?: 1;
            $listRows = $listRows ?? (is_numeric($options['limit']) ? $options['limit'] : 20);
            $offset = $listRows * ((int)$page - 1);
            $options['limit'] = $offset . ',' . $listRows;
        }
        if (C('DB_SQL_BUILD_CACHE')) { // SQLCreating the cache
            $key = md5(serialize($options));
            $value = S($key);
            if (false !== $value) {
                return $value;
            }
        }
        $sql = $this->parseSql($this->selectSql, $options);
        $sql .= $this->parseLock($options['lock'] ?? false);
        if (isset($key)) { // WriteSQLCreating the cache
            S($key, $sql, ['expire' => 0, 'length' => C('DB_SQL_BUILD_LENGTH'), 'queue' => C('DB_SQL_BUILD_QUEUE')]);
        }
        return $sql;
    }

    /**
     * replaceSQLStatement expression
     * @access public
     * @param array $options expression
     * @return string
     */
    public function parseSql($sql, $options = [])
    {
        $sql = str_replace(
            ['%TABLE%', '%DISTINCT%', '%FIELD%', '%JOIN%', '%WHERE%', '%GROUP%', '%HAVING%', '%ORDER%', '%LIMIT%', '%UNION%', '%COMMENT%'],
            [
                $this->parseTable($options['table']),
                $this->parseDistinct($options['distinct'] ?? false),
                $this->parseField(!empty($options['field']) ? $options['field'] : '*'),
                $this->parseJoin(!empty($options['join']) ? $options['join'] : ''),
                $this->parseWhere(!empty($options['where']) ? $options['where'] : ''),
                $this->parseGroup(!empty($options['group']) ? $options['group'] : ''),
                $this->parseHaving(!empty($options['having']) ? $options['having'] : ''),
                $this->parseOrder(!empty($options['order']) ? $options['order'] : ''),
                $this->parseLimit(!empty($options['limit']) ? $options['limit'] : ''),
                $this->parseUnion(!empty($options['union']) ? $options['union'] : ''),
                $this->parseComment(!empty($options['comment']) ? $options['comment'] : '')
            ], $sql);
        return $sql;
    }

    /**
     * ObtainLastInquireofsqlStatement
     * @param string $model model Name
     * @access public
     * @return string
     */
    public function getLastSql($model = '')
    {
        return $model ? $this->modelSql[$model] : $this->queryStr;
    }

    /**
     * Acquired recently insertedID
     * @access public
     * @return string
     */
    public function getLastInsID()
    {
        return $this->lastInsID;
    }

    /**
     * Obtainmost近ofError Messages
     * @access public
     * @return string
     */
    public function getError()
    {
        return $this->error;
    }

    /**
     * SQLInstruction security filtering
     * @access public
     * @param string $str SQLString
     * @return string
     */
    public function escapeString($str)
    {
        return addslashes($str);
    }

    /**
     * Set the current operating model
     * @access public
     * @param string $model model Name
     * @return void
     */
    public function setModel($model)
    {
        $this->model = $model;
    }

    /**
     * Destructor
     * @access public
     */
    public function __destruct()
    {
        // freedInquire
        if ($this->queryID) {
            $this->free();
        }
        // shut downconnection
        $this->close();
    }

    // Close the database Driven by a class definition
    public function close()
    {
    }
}