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
class Upyun
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
        'host' => '', //Shoot the cloud server
        'username' => '', //Shoot cloud users
        'password' => '', //Shoot clouds password
        'bucket' => '', //spacename
        'timeout' => 90, //time outtime
    );

    /**
     * Constructor,ForSet upUploadrootpath
     * @param array $config FTPConfiguration
     */
    public function __construct($config)
    {
        /* defaultFTPConfiguration */
        $this->config = array_merge($this->config, $config);
        $this->config['password'] = md5($this->config['password']);
    }

    /**
     * DetectUploadRoot directory(Shoot the cloudUploadTimestand byautomaticCreate a directory,Direct return)
     * @param string $rootpath Root directory
     * @return boolean true-Detectby,false-Failure detection
     */
    public function checkRootPath($rootpath)
    {
        /* Set the root directory */
        $this->rootPath = trim($rootpath, './') . '/';
        return true;
    }

    /**
     * Detection upload directory(Shoot the cloudUploadTimestand byautomaticCreate a directory,Direct return)
     * @param  string $savepath Upload directory
     * @return boolean          Detectresult,true-by,false-failure
     */
    public function checkSavePath($savepath)
    {
        return true;
    }

    /**
     * Create a folder (Shoot the cloudUploadTimestand byautomaticCreate a directory,Direct return)
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
    public function save($file, $replace = true)
    {
        $header['Content-Type'] = $file['type'];
        $header['Content-MD5'] = $file['md5'];
        $header['Mkdir'] = 'true';
        $resource = fopen($file['tmp_name'], 'r');

        $save = $this->rootPath . $file['savepath'] . $file['savename'];
        $data = $this->request($save, 'PUT', $header, $resource);
        return false === $data ? false : true;
    }

    /**
     * Obtainthe last timeUpload error message
     * @return string Error Messages
     */
    public function getError()
    {
        return $this->error;
    }

    /**
     * Shoot a cloud server request
     * @param  string $path RequestedPATH
     * @param  string $method Request method
     * @param  array $headers requestheader
     * @param  resource $body Upload resource file
     * @return boolean
     */
    private function request($path, $method, $headers = null, $body = null)
    {
        $uri = "/{$this->config['bucket']}/{$path}";
        $ch = curl_init($this->config['host'] . $uri);

        $_headers = array('Expect:');
        if (!is_null($headers) && is_array($headers)) {
            foreach ($headers as $k => $v) {
                array_push($_headers, "{$k}: {$v}");
            }
        }

        $length = 0;
        $date = gmdate('D, d M Y H:i:s \G\M\T');

        if (!is_null($body)) {
            if (is_resource($body)) {
                fseek($body, 0, SEEK_END);
                $length = ftell($body);
                fseek($body, 0);

                array_push($_headers, "Content-Length: {$length}");
                curl_setopt($ch, CURLOPT_INFILE, $body);
                curl_setopt($ch, CURLOPT_INFILESIZE, $length);
            } else {
                $length = @strlen($body);
                array_push($_headers, "Content-Length: {$length}");
                curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
            }
        } else {
            array_push($_headers, "Content-Length: {$length}");
        }

        array_push($_headers, 'Authorization: ' . $this->sign($method, $uri, $date, $length));
        array_push($_headers, "Date: {$date}");

        curl_setopt($ch, CURLOPT_HTTPHEADER, $_headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->config['timeout']);
        curl_setopt($ch, CURLOPT_HEADER, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 0);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);

        if ($method == 'PUT' || $method == 'POST') {
            curl_setopt($ch, CURLOPT_POST, 1);
        } else {
            curl_setopt($ch, CURLOPT_POST, 0);
        }

        if ($method == 'HEAD') {
            curl_setopt($ch, CURLOPT_NOBODY, true);
        }

        $response = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        list($header, $body) = explode("\r\n\r\n", $response, 2);

        if ($status == 200) {
            if ($method == 'GET') {
                return $body;
            } else {
                $data = $this->response($header);
                return count($data) > 0 ? $data : true;
            }
        } else {
            $this->error($header);
            return false;
        }
    }

    /**
     * Fetch response data
     * @param  string $text Response header string
     * @return array        Response Data List
     */
    private function response($text)
    {
        $headers = explode("\r\n", $text);
        $items = array();
        foreach ($headers as $header) {
            $header = trim($header);
            if (strpos($header, 'x-upyun') !== False) {
                list($k, $v) = explode(':', $header);
                $items[trim($k)] = in_array(substr($k, 8, 5), array('width', 'heigh', 'frame')) ? intval($v) : trim($v);
            }
        }
        return $items;
    }

    /**
     * Formrequest Signature
     * @param  string $method Request method
     * @param  string $uri requestURI
     * @param  string $date Request Time
     * @param  integer $length Request content size
     * @return string          request Signature
     */
    private function sign($method, $uri, $date, $length)
    {
        $sign = "{$method}&{$uri}&{$date}&{$length}&{$this->config['password']}";
        return 'UpYun ' . $this->config['username'] . ':' . md5($sign);
    }

    /**
     * Error message acquisition request
     * @param  string $header Header information request returns
     */
    private function error($header)
    {
        list($status, $stash) = explode("\r\n", $header, 2);
        list($v, $code, $message) = explode(" ", $status, 3);
        $message = is_null($message) ? 'File Not Found' : "[{$status}]:{$message}";
        $this->error = $message;
    }

}
