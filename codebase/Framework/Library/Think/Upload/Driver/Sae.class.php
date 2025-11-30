<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2014 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: luofei614<weibo.com/luofei614>
// +----------------------------------------------------------------------

namespace Think\Upload\Driver;
class Sae
{
    /**
     * StorageofDomain
     * @var string
     */
    private $domain = '';

    private $rootPath = '';

    /**
     * localUpload error message
     * @var string
     */
    private $error = '';

    /**
     * The constructor, setstorageofdomain, If there pass configuration,domainforConfigurationitem,in caseNopassdomainforThe firstOnepathofDirectory name。
     * @param mixed $config Upload configuration
     */
    public function __construct($config = null)
    {
        if (is_array($config) && !empty($config['domain'])) {
            $this->domain = strtolower($config['domain']);
        }
    }

    /**
     * DetectUploadRoot directory
     * @param string $rootpath Root directory
     * @return boolean true-Detectby,false-Failure detection
     */
    public function checkRootPath($rootpath)
    {
        $rootpath = trim($rootpath, './');
        if (!$this->domain) {
            $rootpath = explode('/', $rootpath);
            $this->domain = strtolower(array_shift($rootpath));
            $rootpath = implode('/', $rootpath);
        }

        $this->rootPath = $rootpath;
        $st = new \SaeStorage();
        if (false === $st->getDomainCapacity($this->domain)) {
            $this->error = 'It looks like you did not buildStorageofdomain[' . $this->domain . ']';
            return false;
        }
        return true;
    }

    /**
     * Detection upload directory
     * @param  string $savepath Upload directory
     * @return boolean          Detectresult,true-by,false-failure
     */
    public function checkSavePath($savepath)
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
        $filename = ltrim($this->rootPath . '/' . $file['savepath'] . $file['savename'], '/');
        $st = new \SaeStorage();
        /* Do not overwrite files with the same name */
        if (!$replace && $st->fileExists($this->domain, $filename)) {
            $this->error = 'File exists' . $file['savename'];
            return false;
        }

        /* Moving Files */
        if (!$st->upload($this->domain, $filename, $file['tmp_name'])) {
            $this->error = 'Save the file upload error![' . $st->errno() . ']:' . $st->errmsg();
            return false;
        } else {
            $file['url'] = $st->getUrl($this->domain, $filename);
        }
        return true;
    }

    public function mkdir()
    {
        return true;
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
