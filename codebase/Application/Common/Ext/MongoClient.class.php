<?php
namespace Common\Ext;
use MongoDB\BSON\ObjectID;
use MongoDB\BSON\UTCDateTime;
use MongoDB\Driver\BulkWrite;
use MongoDB\Driver\Command;
use MongoDB\Driver\Manager;
use MongoDB\Driver\Query;

class MongoClient
{
    private $host ; // MongoDB Server IP or host name
    private $port ; // Port for accessing MongoDB - Default Installation port is 27017
    private $username ; //  MongoDB access username
    private $password ; // MongoDB access password
    private $db; // MonogDB Database instance name
    public $collection = false;
    private Manager $connection;

    public function __construct($config) {
		include('vendor/autoload.php');

		$this->username=$config['username'];
		$this->host=$config['host'];
		$this->password=$config['password'];
		$this->db=$config['db'];
		$this->port=$config['port'];

        $manager = new Manager("mongodb://" . $this->username . ":" . $this->password . "@" . $this->host . ":" . $this->port . "/" . $this->db . "");
        $this->connection = $manager;
    }

    public function setCollection($collection)
    {
        $this->collection = $collection;
    }

    public function init()
    {

        if(!$this->connection)
        {
		$this->connection =new Manager("mongodb://" . $this->username . ":" . $this->password . "@" . $this->host . ":" . $this->port . "/" . $this->db . "");
		}
    }

    public function insert($collection, $data)
    {
        $this->init();
        $insRec       = new BulkWrite;
        $id = $insRec->insert($data);
        $result       = $this->connection->executeBulkWrite($this->db.'.'.$collection, $insRec);

        if($result)
        {
            return $id;
        }
        else
        {
            return false;
        }
    }

    public function save($collection, $data)
    {
        $this->insert($collection, $data);
    }

    public function rawFind($collection, $filter, $options = [])
    {
        /*
         * $filter = [
         *      'id'    => 1
         * ]
         * $options - [
         *      'sort' => ['_id' => -1],
         * ]
         */
        $this->init();
        $query  = new Query($filter, $options);
        $rows   = $this->connection->executeQuery($this->db.'.'.$collection, $query);
        return $rows->toArray();
    }

    public function count($array = [])
    {
        $filter = (@$array[0]) ? $array[0]: [];
        $options = [];
        $this->init();

        $Command = new Command(["count" => $this->collection, "query" => $filter]);
        $Result = $this->connection->executeCommand($this->db, $Command);
        return $Result->toArray()[0]->n;
    }

    public function find($array = [])
    {
        $filter = (@$array[0]) ? $array[0]: [];
        $options = [];
        if(isset($array["limit"]))
            $options["limit"]    = @$array["limit"];
        if(isset($array["sort"]))
            $options["sort"]    = @$array["sort"];
        if(isset($array["skip"]))
            $options["skip"]   = $array["skip"];
        $this->init();

        $query  = new Query($filter, $options);
        $rows   = $this->connection->executeQuery($this->db.'.'.$this->collection, $query);

        return $rows->toArray();
    }

    public function findById($id)
    {
        $filter["_id"] = $this->objectId($id);
        $this->init();
        $query  = new Query($filter, []);
        $rows   = $this->connection->executeQuery($this->db.'.'.$this->collection, $query);
        foreach($rows as $row)
            return $row;
        return false;
    }

    public function rawFindFirst($collection, $filter, $options = [])
    {
        $options["limit"] = 1;
        $this->init();
        $query  = new Query($filter, $options);
        $rows   = $this->connection->executeQuery($this->db.'.'.$collection, $query);
        if(count($rows) > 0)
            foreach($rows as $row)
                return $row;
        return false;
    }

    public function findFirst($array = [])
    {
        $filter = (@$array[0]) ? $array[0]: [];
        $options = [];
        $options["limit"]   = 1;
        if(isset($array["sort"]))
            $options["sort"]    = @$array["sort"];
        if(isset($array["skip"]))
            $options["skip"]   = $array["skip"];
        $this->init();
        $query  = new Query($filter, $options);
        $rows   = $this->connection->executeQuery($this->db.'.'.$this->collection, $query);
        foreach($rows as $row)
                return $row;
        return false;
    }

    public function update($collection, $filter, $data)
    {
        /*
         * ['_id'=>new \MongoDB\BSON\ObjectID($id)],
         *
         * $filter = [
         *      'id'    => 1
         * ]
         * $options - [
         *      'sort' => ['_id' => -1],
         * ]
         */
        $this->init();
        $options = ['multi' => false, 'upsert' => false];
        $insRec       = new BulkWrite;
        $insRec->update(
            $filter,
            ['$set' => $data],
            $options
        );
        $result       = $this->connection->executeBulkWrite($this->db.'.'.$collection, $insRec);

        if($result)
        {
            return true;
        }
        else
        {
            return false;
        }
    }

    public function increment($collection, $filter, $data)
    {
        $this->init();
        $options = ['multi' => false, 'upsert' => false];
        $insRec       = new BulkWrite;
        $insRec->update(
            $filter,
            ['$inc' => $data],
            $options
        );
        $result       = $this->connection->executeBulkWrite($this->db.'.'.$collection, $insRec);

        if($result)
        {
            return true;
        }
        else
        {
            return false;
        }
    }

    public function delete($collection, $filter)
    {
        $this->init();
        $bulk   = new BulkWrite;
        $bulk->delete($filter, ['limit' => 0]);
        $result = $this->connection->executeBulkWrite($this->db.'.'.$collection, $bulk);
        if($result)
        {
            return true;
        }
        else
        {
            return false;
        }
    }

    public function getDate($time=false)
    {
        if(!$time)
            $time=time();
        $time *= 1000;
        return new UTCDateTime($time);
    }

    public function dateTime($date)
    {
        $unixtime = 0;
        if($date && method_exists($date, "toDateTime"))
            $unixtime = strtotime(@$date->toDateTime()->format("Y-m-d H:i:s")) + 1*3600;
        return $unixtime;
    }

    public function dateFormat($date, $format = "Y-m-d H:i:s")
    {
        //var_dump($date);exit;
        if($date && method_exists($date, "toDateTime"))
            $unixtime = strtotime(@$date->toDateTime()->format("Y-m-d H:i:s")) + 1*3600;
        if(@$unixtime)
            return date($format, $unixtime);
        return 0;
    }

    public function toSeconds($date)
    {
        if($date && method_exists($date, "toDateTime"))
            return round(@$date->toDateTime()->format("U.u"), 0) + 1*3600;
        return 0;
    }

    public function objectId($id)
    {
        return new ObjectID($id);
    }

    public function getUnixtime()
    {
        return time() + 1*3600;
    }
}