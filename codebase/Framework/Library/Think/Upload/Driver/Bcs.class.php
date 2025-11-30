<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2014 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: Jay <yangweijiester@gmail.com> <http://code-tech.diandian.com>
// +----------------------------------------------------------------------
namespace Think\Upload\Driver;

use Think\Upload\Driver\Bcs\BaiduBcs;

class Bcs
{

    /**
     * Upload files in the root directory
     * @var string
     */
    private $rootPath;
    const DEFAULT_URL = 'bcs.duapp.com';

    /**
     * Upload error message
     * @var string
     */
    private $error = '';

    public $config = array(
        'AccessKey' => '',
        'SecretKey' => '', //Baidu cloud server
        'bucket' => '', //spacename
        'rename' => false,
        'timeout' => 3600, //time outtime
    );

    public $bcs = null;
    private $apiurl;

    /**
     * Constructor,ForSet upUploadrootpath
     * @param array $config FTPConfiguration
     */
    public function __construct($config)
    {
        /* defaultFTPConfiguration */
        $this->config = array_merge($this->config, $config);

        $bcsClass = dirname(__FILE__) . "/Bcs/bcs.class.php";
        if (is_file($bcsClass))
            require_once($bcsClass);
        $this->bcs = new BaiduBCS ($this->config['AccessKey'], $this->config['SecretKey'], self:: DEFAULT_URL);
    }

    /**
     * DetectUploadRoot directory(BaiducloudUploadTimestand byautomaticCreate a directory,Direct return)
     * @param string $rootpath Root directory
     * @return boolean true-Detectby,false-Failure detection
     */
    public function checkRootPath($rootpath)
    {
        /* Set the root directory */
        $this->rootPath = str_replace('./', '/', $rootpath);
        return true;
    }

    /**
     * Detection upload directory(BaiducloudUploadTimestand byautomaticCreate a directory,Direct return)
     * @param  string $savepath Upload directory
     * @return boolean          Detectresult,true-by,false-failure
     */
    public function checkSavePath($savepath)
    {
        return true;
    }

    /**
     * Create a folder (BaiducloudUploadTimestand byautomaticCreate a directory,Direct return)
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
        $opt = array();
        $opt ['acl'] = BaiduBCS::BCS_SDK_ACL_TYPE_PUBLIC_WRITE;
        $opt ['curlopts'] = array(
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => 1800
        );
        $object = "/{$file['savepath']}{$file['savename']}";
        $response = $this->bcs->create_object($this->config['bucket'], $object, $file['tmp_name'], $opt);
        $url = $this->download($object);
        $file['url'] = $url;
        return $response->isOK() ? true : false;
    }

    public function download($file)
    {
        $file = str_replace('./', '/', $file);
        $opt = array();
        $opt['time'] = mktime('2049-12-31'); //This is the longest active time!--
        $response = $this->bcs->generate_get_object_url($this->config['bucket'], $file, $opt);
        return $response;
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
     * Request Baidu cloud server
     * @param  string $path RequestedPATH
     * @param  string $method Request method
     * @param  array $headers requestheader
     * @param  resource $body Upload resource file
     * @return boolean
     */
    private function request($path, $method, $headers = null, $body = null)
    {
        $ch = curl_init($path);

        $_headers = array('Expect:');
        if (is_array($headers)) {
            foreach ($headers as $k => $v) {
                $_headers[] = "{$k}: {$v}";
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

        // array_push($_headers, 'Authorization: ' . $this->sign($method, $uri, $date, $length));
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
        $items = json_decode($text, true);
        return $items;
    }

    /**
     * Formrequest Signature
     * @return string          request Signature
     */
    private function sign($method, $Bucket, $object = '/', $size = '')
    {
        if (!$size)
            $size = $this->config['size'];
        $param = array(
            'ak' => $this->config['AccessKey'],
            'sk' => $this->config['SecretKey'],
            'size' => $size,
            'bucket' => $Bucket,
            'host' => self::DEFAULT_URL,
            'date' => time() + $this->config['timeout'],
            'ip' => '',
            'object' => $object
        );
        $response = $this->request($this->apiurl . '?' . http_build_query($param), 'POST');
        if ($response)
            $response = json_decode($response, true);
        return $response['content'][$method];
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
