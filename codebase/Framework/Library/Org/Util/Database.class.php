<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2012 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: When wheat seedlings child <zuojiazi@vip.qq.com> <http://www.zjzit.cn>
// +----------------------------------------------------------------------

use Think\Db;

//dataExport Model
class Database
{
    /**
     * File pointer
     * @var resource
     */
    private $fp;

    /**
     * Backup file information part - Docket Number,name - filename
     * @var array
     */
    private $file;

    /**
     * File size is currently open
     * @var integer
     */
    private $size = 0;

    /**
     * Backup Configuration
     * @var integer
     */
    private $config;

    /**
     * databaseBackupstructuremethod
     * @param array $file BackupOr RestoreFile information
     * @param array $config Backup configuration information
     * @param string $type carried outTypes of,export - Backupdata, import - Restore data
     */
    public function __construct($file, $config, $type = 'export')
    {
        $this->file = $file;
        $this->config = $config;
    }

    /**
     * turn onOnevolume,Fordata input
     * @param  integer $size The size of the write data
     */
    private function open($size)
    {
        if ($this->fp) {
            $this->size += $size;
            if ($this->size > $this->config['part']) {
                $this->config['compress'] ? @gzclose($this->fp) : @fclose($this->fp);
                $this->fp = null;
                $this->file['part']++;
                session('backup_file', $this->file);
                $this->create();
            }
        } else {
            $backuppath = $this->config['path'];
            $filename = "{$backuppath}{$this->file['name']}-{$this->file['part']}.sql";
            if ($this->config['compress']) {
                $filename = "{$filename}.gz";
                $this->fp = @gzopen($filename, "a{$this->config['level']}");
            } else {
                $this->fp = @fopen($filename, 'a');
            }
            $this->size = filesize($filename) + $size;
        }
    }

    /**
     * Write initial data
     * @return boolean true - Writesuccess,false - Write failure
     */
    public function create()
    {
        $sql = "-- -----------------------------\n";
        $sql .= "-- Think MySQL Data Transfer \n";
        $sql .= "-- \n";
        $sql .= "-- Host     : " . C('DB_HOST') . "\n";
        $sql .= "-- Port     : " . C('DB_PORT') . "\n";
        $sql .= "-- Database : " . C('DB_NAME') . "\n";
        $sql .= "-- \n";
        $sql .= "-- Part : #{$this->file['part']}\n";
        $sql .= "-- Date : " . date("Y-m-d H:i:s") . "\n";
        $sql .= "-- -----------------------------\n\n";
        $sql .= "SET FOREIGN_KEY_CHECKS = 0;\n\n";
        return $this->write($sql);
    }

    /**
     * WriteSQLStatement
     * @param  string $sql To be writtenSQLStatement
     * @return boolean     true - Writesuccess,false - Write failure!
     */
    private function write($sql)
    {
        $size = strlen($sql);

        //due tocompressionthe reason,UnableComputeThe compressionAfterlength,HereAssuming that the compression ratiofor50%,
        //Typically the compression ratio will beHigher than50%；
        $size = $this->config['compress'] ? $size / 2 : $size;

        $this->open($size);
        return $this->config['compress'] ? @gzwrite($this->fp, $sql) : @fwrite($this->fp, $sql);
    }

    /**
     * Backup table structure
     * @param  string $table Table name
     * @param  integer $start Starting line number
     * @return boolean        false - Backup failed
     */
    public function backup($table, $start)
    {
        //createDBObjects
        $db = Db::getInstance();

        //Backup table structure
        if (0 == $start) {
            $result = $db->query("SHOW CREATE TABLE `{$table}`");
            $sql = "\n";
            $sql .= "-- -----------------------------\n";
            $sql .= "-- Table structure for `{$table}`\n";
            $sql .= "-- -----------------------------\n";
            $sql .= "DROP TABLE IF EXISTS `{$table}`;\n";
            $sql .= trim($result[0]['Create Table']) . ";\n\n";
            if (false === $this->write($sql)) {
                return false;
            }
        }

        //datatotal
        $result = $db->query("SELECT COUNT(*) AS count FROM `{$table}`");
        $count = $result['0']['count'];

        //Backuptabledata
        if ($count) {
            //data inputNote
            if (0 == $start) {
                $sql = "-- -----------------------------\n";
                $sql .= "-- Records of `{$table}`\n";
                $sql .= "-- -----------------------------\n";
                $this->write($sql);
            }

            //Backupdatarecording
            $result = $db->query("SELECT * FROM `{$table}` LIMIT {$start}, 1000");
            foreach ($result as $row) {
                $row = array_map('addslashes', $row);
                $sql = "INSERT INTO `{$table}` VALUES ('" . str_replace(array("\r", "\n"), array('\r', '\n'), implode("', '", $row)) . "');\n";
                if (false === $this->write($sql)) {
                    return false;
                }
            }

            //and alsoMoredata
            if ($count > $start + 1000) {
                return array($start + 1000, $count);
            }
        }

        //BackupThe next table
        return 0;
    }

    public function import($start)
    {


        //Restore data
        $db = Db::getInstance();

        if ($this->config['compress']) {
            $gz = gzopen($this->file[1], 'r');
            $size = 0;
        } else {
            $size = filesize($this->file[1]);
            $gz = fopen($this->file[1], 'r');
        }

        $sql = '';
        if ($start) {
            $this->config['compress'] ? gzseek($gz, $start) : fseek($gz, $start);
        }

        for ($i = 0; $i < 1000; $i++) {
            $sql .= $this->config['compress'] ? gzgets($gz) : fgets($gz);
            if (preg_match('/.*;$/', trim($sql))) {
                if (false !== $db->execute($sql)) {
                    $start += strlen($sql);
                } else {
                    return false;
                }
                $sql = '';
            } elseif ($this->config['compress'] ? gzeof($gz) : feof($gz)) {
                return 0;
            }
        }

        return array($start, $size);
    }

    /**
     * Destructor,Forshut downfileResources
     */
    public function __destruct()
    {
        $this->config['compress'] ? @gzclose($this->fp) : @fclose($this->fp);
    }
}
