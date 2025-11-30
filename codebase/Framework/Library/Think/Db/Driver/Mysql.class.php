<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2014 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

namespace Think\Db\Driver;

use PDO;
use Think\Db\Driver;

/**
 * MySQL database driver with improvements for PHP 7.4
 */
class Mysql extends Driver
{


    /**
     * Parse the DSN (Data Source Name) for PDO connection
					 
     * @param array $config Connection information
     * @return string
     */
    protected function parseDsn($config): string
    {
        $dsn = 'mysql:dbname=' . $config['database'] . ';host=' . $config['hostname'];
        if (!empty($config['hostport'])) {
            $dsn .= ';port=' . $config['hostport'];
        } elseif (!empty($config['socket'])) {
            $dsn .= ';unix_socket=' . $config['socket'];
        }

        if (!empty($config['charset'])) {
            // Set character set in two ways to ensure compatibility
            $this->options[PDO::MYSQL_ATTR_INIT_COMMAND] = 'SET NAMES ' . $config['charset'];
            $dsn .= ';charset=' . $config['charset'];
        }
        return $dsn;
    }

    /**
     * Get the field information of a database table
     * @param string $tableName The name of the table
     * @return array
     */
    public function getFields(string $tableName): array
    {
        $this->initConnect(true);
        list($tableName) = explode(' ', $tableName);
        if (strpos($tableName, '.')) {
            list($dbName, $tableName) = explode('.', $tableName);
            $sql = 'SHOW COLUMNS FROM `' . $dbName . '`.`' . $tableName . '`';
        } else {
            $sql = 'SHOW COLUMNS FROM `' . $tableName . '`';
        }

        $result = $this->query($sql);
        $info = [];
        if ($result) {
            foreach ($result as $val) {
                if (PDO::CASE_LOWER != $this->_linkID->getAttribute(PDO::ATTR_CASE)) {
                    $val = array_change_key_case($val, CASE_LOWER);
                }
                $info[$val['field']] = [
                    'name' => $val['field'],
                    'type' => $val['type'],
                    'notnull' => (bool) ('' === $val['null']), // not null is empty, null is yes
                    'default' => $val['default'],
                    'primary' => (strtolower($val['key']) == 'pri'),
                    'autoinc' => (strtolower($val['extra']) == 'auto_increment'),
                ];
            }
        }
        return $info;
    }

    /**
     * Get the table information of a database
     * @param string $dbName The name of the database
     * @return array
     */
    public function getTables(string $dbName = ''): array
    {
        if (!empty($dbName)) {
            $dbName = $this->parseKey($dbName);
        }
        $sql = !empty($dbName) ? 'SHOW TABLES FROM ' . $dbName : 'SHOW TABLES';
        $result = $this->query($sql);
        $info = [];
        foreach ($result as $val) {
            $info[] = current($val);
        }
        return $info;
    }

    /**
     * Parse a database key (column or table name) safely
					 
     * @param string $key The key to parse
     * @param bool $strict Whether to strictly check the key format
     * @return string
     */
    public function parseKey($key, $strict = false): string
    {
        if (is_int($key)) {
            return (string)$key;
        }

        $key = trim($key);

        if ($strict && !preg_match('/^[\w.*]+$/', $key)) {
            throw new \InvalidArgumentException('Unsupported data: ' . $key);
        }

        if ('*' != $key && !preg_match('/[,\'\"*()`.\s]/', $key)) {
            $key = '`' . $key . '`';
        }
        return $key;
    }

    /**
     * Return a random order statement
						
     * @return string
     */
    protected function parseRand(): string
    {
        return 'rand()';
    }

    /**
     * Insert multiple records into the database
					 
     * @param mixed $dataSet The dataset to insert
     * @param array $options Options for the insert
     * @param bool $replace Whether to use REPLACE instead of INSERT
     * @return false|int
     */
    public function insertAll($dataSet,  $options = [],  $replace = false)
    {
							   
										 
        if (!is_array($dataSet[0])) {
            return false;
        }

        $values = [];
        $this->model = $options['model'];
        $this->parseBind(!empty($options['bind']) ? $options['bind'] : []);
        $fields = array_map([$this, 'parseKey'], array_keys($dataSet[0]));

																				
																			   
        foreach ($dataSet as $data) {
            $value = [];
            foreach ($data as $key => $val) {
                if (is_array($val) && 'exp' == $val[0]) {
                    $value[] = $val[1];
                } elseif (is_null($val)) {
                    $value[] = 'NULL';
                } elseif (is_scalar($val)) {
																							 
														   
							
                    $name = count($this->bind);
                    $value[] = ':' . $name;
                    $this->bindParam($name, $val);
					 
                }
            }
            $values[] = '(' . implode(',', $value) . ')';
        }

																			
        $sql = (true === $replace ? 'REPLACE' : 'INSERT') . ' INTO ' . $this->parseTable($options['table']) . ' (' . implode(',', $fields) . ') VALUES ' . implode(',', $values);
        $sql .= $this->parseDuplicate($replace);
        $sql .= $this->parseComment(!empty($options['comment']) ? $options['comment'] : '');
        return $this->execute($sql, !empty($options['fetch_sql']));
    }

    /**
     * Handle ON DUPLICATE KEY UPDATE clause
						
     * @param mixed $duplicate The duplicate key handling
     * @return string
     */
    protected function parseDuplicate($duplicate): string
    {
											   
        if (is_bool($duplicate) || empty($duplicate)) {
            return '';
        }

        if (is_string($duplicate)) {
									  
            $duplicate = explode(',', $duplicate);
        } elseif (is_object($duplicate)) {
							  
            $duplicate = get_class_vars($duplicate);
        }

        $updates = [];
        foreach ((array)$duplicate as $key => $val) {
            if (is_numeric($key)) {
																																							
                $updates[] = $this->parseKey($val) . "=VALUES(" . $this->parseKey($val) . ")";
            } else {
																
				 
												
				 

														   
							 
				 

								  
											
																		  
							  
										
							
                $name = count($this->bind);
                $updates[] = $this->parseKey($key) . "=:" . $name;
                $this->bindParam($name, $val);
							  
				 
            }
        }
							  
					  
		 

        return empty($updates) ? '' : " ON DUPLICATE KEY UPDATE " . implode(', ', $updates);
    }

    /**
     * Execute a stored procedure and return multiple datasets
					 
     * @param string $str SQL statement
     * @param bool $fetchSql Whether to return the SQL query string without executing it
     * @return mixed
     */
    public function procedure(string $str, bool $fetchSql = false)
    {
        $this->initConnect(false);
        $this->_linkID->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        if (!$this->_linkID) {
            return false;
        }

        $this->queryStr = $str;
        if ($fetchSql) {
            return $this->queryStr;
        }

        if (!empty($this->PDOStatement)) {
            $this->free();
        }

        $this->queryTimes++;
											
					  
        $this->debug(true);
															
											
						   
						 
		 
        try {
            $this->PDOStatement = $this->_linkID->prepare($str);
            $this->PDOStatement->execute();
            $resultArr = [];
								
            do {
                $result = $this->PDOStatement->fetchAll(PDO::FETCH_ASSOC);
                if ($result) {
                    $resultArr[] = $result;
                }
            } while ($this->PDOStatement->nextRowset());
																							   
            return $resultArr;
        } catch (\PDOException $e) {
            clog('mysql_',$e->getMessage());
            $this->error('Database Error: ' . $e->getMessage());
            return false;
        } finally {
            $this->_linkID->setAttribute(PDO::ATTR_ERRMODE, $this->options[PDO::ATTR_ERRMODE]);
						 
        }
    }
}
