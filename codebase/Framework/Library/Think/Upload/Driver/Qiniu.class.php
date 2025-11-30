<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2014 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: yangweijie <yangweijiester@gmail.com> <http://www.code-tech.diandian.com>
// +----------------------------------------------------------------------

namespace Think\Upload\Driver;

use Think\Upload\Driver\Qiniu\QiniuStorage;

class Qiniu
{
    /**
     * Upload files in the root directory
     * @var string
     */
    private $rootPath;

    /**
     * Upload error message
     * @var string
     */
    private $error = '';

    private $config = array(
        'secretKey' => '', //Seven cattle server
        'accessKey' => '', //Seven cattle users
        'domain' => '', //Seven cattle password
        'bucket' => '', //spacename
        'timeout' => 300, //time outtime
    );

    /**
     * Constructor,ForSet upUploadrootpath
     * @param array $config FTPConfiguration
     */
    public function __construct($config)
    {
        $this->config = array_merge($this->config, $config);
        /* Set the root directory */
        $this->qiniu = new QiniuStorage($config);
    }

    /**
     * DetectUploadRoot directory(Seven cattleUploadTimestand byautomaticCreate a directory,Direct return)
     * @param string $rootpath Root directory
     * @return boolean true-Detectby,false-Failure detection
     */
    public function checkRootPath($rootpath)
    {
        $this->rootPath = trim($rootpath, './') . '/';
        return true;
    }

    /**
     * Detection upload directory(Seven cattleUploadTimestand byautomaticCreate a directory,Direct return)
     * @param  string $savepath Upload directory
     * @return boolean          Detectresult,true-by,false-failure
     */
    public function checkSavePath($savepath)
    {
        return true;
    }

    /**
     * Create a folder (Seven cattleUploadTimestand byautomaticCreate a directory,Direct return)
     * @param  string $savepath Directory name
     * @return boolean          true-createsuccess,false-createfailure
     */
    public function mkdir($savepath)
    {
        return true;
    }

    /**
     * StorageDesignationfile
     * @param  array $file Save file information
     * @param  boolean $replace Whether the same file cover
     * @return boolean          Storagestatus,true-success,false-failure
     */
    public function save(&$file, $replace = true)
    {
        $file['name'] = $file['savepath'] . $file['savename'];
        $key = str_replace('/', '_', $file['name']);
        $upfile = array(
            'name' => 'file',
            'fileName' => $key,
            'fileBody' => file_get_contents($file['tmp_name'])
        );
        $config = array();
        $result = $this->qiniu->upload($config, $upfile);
        $url = $this->qiniu->downlink($key);
        $file['url'] = $url;
        return !(false === $result);
    }

    /**
     * Obtainthe last timeUpload error message
     * @return string Error Messages
     */
    public function getError()
    {
        return $this->qiniu->errorStr;
    }
}
