<?php

namespace Think\Db\Driver;

use mysqli;
use Think\Db\Driver;

class Mariadb  extends Driver
{
    public function connect($config = '', $linkNum = 0, $autoConnection = false)
    {
        if (!isset($this->linkID[$linkNum])) {
            if (empty($config)) {
                $config = $this->config;
            }

            $this->linkID[$linkNum] = new mysqli(
                $config['hostname'], 
                $config['username'], 
                $config['password'], 
                $config['database'],
                $config['hostport'] ?? 3306
            );

            if ($this->linkID[$linkNum]->connect_error) {
                throw new \Exception("Connection failed: " . $this->linkID[$linkNum]->connect_error);
            }

            if (!empty($config['charset'])) {
                $this->linkID[$linkNum]->set_charset($config['charset']);
            }
        }

        return $this->linkID[$linkNum];
    }

    public function query($str,$fetchSql = false, $bind = [])
	
    {
        $this->initConnect(false);
        if (!$this->_linkID) return false;

        $stmt = $this->_linkID->prepare($str);
        if ($stmt === false) {
            $this->error();
            return false;
        }
		   if ($fetchSql) {
            return $this->queryStr;
        }

        if (!empty($bind)) {
            $this->bindParam($stmt, $bind);
        }

        if (!$stmt->execute()) {
            $this->error();
            return false;
        }

        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function execute($sql, $bind = [])
    {
        $this->initConnect(true);
        if (!$this->_linkID) return false;

        $stmt = $this->_linkID->prepare($sql);
        if ($stmt === false) {
            $this->error();
            return false;
        }

        if (!empty($bind)) {
            $this->bindParam($stmt, $bind);
        }

        if (!$stmt->execute()) {
            $this->error();
            return false;
        }

        return $stmt->affected_rows;
    }

    protected function bindParam($stmt, $bind)
    {
        foreach ($bind as $key => $value) {
            $stmt->bind_param($key, $value);
        }
    }

    public function error()
    {
        $this->error = $this->_linkID->error;
        // Log or handle the error appropriately
    }

    public function getFields($tableName)
    {
        $result = $this->query('SHOW COLUMNS FROM ' . $this->parseKey($tableName));
        $info   = array();
        if ($result) {
            foreach ($result as $key => $val) {
                $info[$val['Field']] = array(
                    'name'    => $val['Field'],
                    'type'    => $val['Type'],
                    'notnull' => (bool) ($val['Null'] === ''), 
                    'default' => $val['Default'],
                    'primary' => (strtolower($val['Key']) == 'pri'),
                    'autoinc' => (strtolower($val['Extra']) == 'auto_increment'),
                );
            }
        }
        return $info;
    }

    public function getTables($dbName = '')
    {
        $sql    = !empty($dbName) ? 'SHOW TABLES FROM ' . $dbName : 'SHOW TABLES';
        $result = $this->query($sql);
        $info   = array();
        foreach ($result as $key => $val) {
            $info[$key] = current($val);
        }
        return $info;
    }

    public function parseKey($key, $strict = false)
    {
        if (is_int($key)) {
            return $key;
        }
        $key = trim($key);
        if ($strict && !preg_match('/^[\w.*]+$/', $key)) {
            throw new \InvalidArgumentException('Invalid key name: ' . $key);
        }
        if ('*' != $key && !preg_match('/[,\'\"*()`.\s]/', $key)) {
            $key = '`' . $key . '`';
        }
        return $key;
    }

    protected function parseRand()
    {
        return 'RAND()';
    }

    public function insertAll($dataSet, $options = array(), $replace = false)
    {
        $values = array();
        $this->model = $options['model'];
        if (!is_array($dataSet[0])) {
            return false;
        }

        $fields = array_map(array($this, 'parseKey'), array_keys($dataSet[0]));
        foreach ($dataSet as $data) {
            $value = array();
            foreach ($data as $key => $val) {
                $value[] = $this->parseValue($val);
            }
            $values[] = '(' . implode(',', $value) . ')';
        }
        $sql = ($replace ? 'REPLACE' : 'INSERT') . ' INTO ' . $this->parseTable($options['table']) . ' (' . implode(',', $fields) . ') VALUES ' . implode(',', $values);
        return $this->execute($sql);
    }

    
    protected function parseDuplicate($duplicate)
    {
        if (empty($duplicate)) {
            return '';
        }

        $updates = [];
        foreach ($duplicate as $key => $value) {
            if (is_numeric($key)) {
                $updates[] = $this->parseKey($value) . " = VALUES(" . $this->parseKey($value) . ")";
            } else {
                $val = $this->parseValue($value);
                $updates[] = $this->parseKey($key) . " = " . $val;
            }
        }
        return ' ON DUPLICATE KEY UPDATE ' . implode(', ', $updates);
    }

    protected function parseValue($value)
    {
        if (is_string($value)) {
            $value = '\'' . $this->_linkID->real_escape_string($value) . '\'';
        } elseif (is_bool($value)) {
            $value = $value ? '1' : '0';
        } elseif (is_null($value)) {
            $value = 'NULL';
        }
        return $value;
    }


    protected function parseTable($tables)
    {
        if (is_array($tables)) {
            $array = [];
            foreach ($tables as $key => $value) {
                if (is_numeric($key)) {
                    $array[] = $this->parseKey($value);
                } else {
                    $array[] = $this->parseKey($key) . ' AS ' . $this->parseKey($value);
                }
            }
            return implode(', ', $array);
        }

        return $this->parseKey($tables);
    }
	public function getLastInsID()
    {
        return $this->_linkID->insert_id;
    }

        /**
     * Execute a stored procedure and return multiple result sets.
     *
     * @param string $str SQL command or stored procedure call.
     * @param bool $fetchSql Whether to fetch SQL without executing.
     * @return mixed
     */
    public function procedure($str, $fetchSql = false)
    {
        $this->initConnect(false);
        if (!$this->_linkID) {
            return false;
        }

        $this->queryStr = $str;
        if ($fetchSql) {
            return $this->queryStr;
        }

        // Execute the stored procedure
        $querySuccess = $this->_linkID->multi_query($str);
        if (!$querySuccess) {
            $this->error();
            return false;
        }

        // Collect the results
        $resultArr = [];
        do {
            if ($result = $this->_linkID->store_result()) {
                $resultArr[] = $result->fetch_all(MYSQLI_ASSOC);
                $result->free();
            }
            if (!$this->_linkID->more_results()) {
                break;
            }
        } while ($this->_linkID->next_result());

        if ($this->_linkID->errno) {
            $this->error();
            return false;
        }

        return $resultArr;
    }
}
