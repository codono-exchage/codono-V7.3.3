<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2014 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: When wheat seedlings child <zuojiazi@vip.qq.com> <http://www.zjzit.cn>
// +----------------------------------------------------------------------

namespace Think\Upload\Driver;
class Local
{
    /**
     * Upload files in the root directory
     * @var string
     */
    private $rootPath;

    /**
     * localUpload error message
     * @var string
     */
    private $error = ''; //Upload error message

    /**
     * Constructor,ForSet upUploadrootpath
     */
    public function __construct($config = null)
    {

    }

    /**
     * DetectUploadRoot directory
     * @param string $rootpath Root directory
     * @return boolean true-Detectby,false-Failure detection
     */
    public function checkRootPath($rootpath)
    {
        if (!(is_dir($rootpath) && is_writable($rootpath))) {
            $this->error = 'UploadRoot directorydoes not exist!pleasetryManuallycreate:' . $rootpath;
            return false;
        }
        $this->rootPath = $rootpath;
        return true;
    }

    /**
     * Detection upload directory
     * @param  string $savepath Upload directory
     * @return boolean          Detectresult,true-by,false-failure
     */
    public function checkSavePath($savepath)
    {
        /* Detect and create the directory */
        if (!$this->mkdir($savepath)) {
            return false;
        } else {
            /* Whether the test directory writable */
            if (!is_writable($this->rootPath . $savepath)) {
                $this->error = 'Upload directory ' . $savepath . ' Can not write!';
                return false;
            } else {
                return true;
            }
        }
    }

    /**
     * StorageDesignationfile
     * @param  array $file Save file information
     * @param  boolean $replace Whether the same file cover
     * @return boolean          Storagestatus,true-success,false-failure
     */
    public function save($file, $replace = true)
    {
        $filename = $this->rootPath . $file['savepath'] . $file['savename'];

        /* Do not overwrite files with the same name */
        if (!$replace && is_file($filename)) {
            $this->error = 'File exists' . $file['savename'];
            return false;
        }

        /* Moving Files */
        if (!move_uploaded_file($file['tmp_name'], $filename)) {
            $this->error = 'Save the file upload error!';
            return false;
        }

        return true;
    }

    /**
     * Create a directory
     * @param  string $savepath To create Jose
     * @return boolean          createstatus,true-success,false-failure
     */
    public function mkdir($savepath)
    {
        $dir = $this->rootPath . $savepath;
        if (is_dir($dir)) {
            return true;
        }

        if (mkdir($dir, 0777, true)) {
            return true;
        } else {
            $this->error = "table of Contents {$savepath} Creation Failed!";
            return false;
        }
    }

    /**
     * Obtainthe last timeUpload error message
     * @return string Error Messages
     */
    public function getError()
    {
        return $this->error;
    }

}
