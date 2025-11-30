<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2009 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: support@codono.com
// +----------------------------------------------------------------------
namespace Org\Net;
/**
 * Http Tools
 * It offers a range ofHttpmethod
 * @author    liu21st <liu21st@gmail.com>
 */
class Http
{

    /**
     * Remote file collection
     * @access public
     * @param string $remote 远程filename
     * @param string $local localsave documentname
     * @return mixed
     */
    static public function curlDownload($remote, $local)
    {
        $cp = curl_init($remote);
        $fp = fopen($local, "w");
        curl_setopt($cp, CURLOPT_FILE, $fp);
        curl_setopt($cp, CURLOPT_HEADER, 0);
        curl_exec($cp);
        curl_close($cp);
        fclose($fp);
    }

    /**
     * use fsockopen by HTTP Direct access agreement(collection)远程file
     * in caseHost orserverNoOpen CURL Extended use can be considered
     * fsockopen ratio CURL Slower,But stable performance
     * @static
     * @access public
     * @param string $url 远程URL
     * @param array $conf Other configuration information
     *        int   limit The number of characters read segment
     *        string post  postContent,String or array,key=value&form
     *        string cookie carrycookieaccess,This parameter iscookiecontent
     *        string ip    If this parameter is passed,$urlWill not be used,ipPriority access
     *        int    timeout Acquisition timeout
     *        bool   block Whether blocking access,The default istrue
     * @return mixed
     */
    static public function fsockopenDownload($url, $conf = array())
    {
        $return = '';
        if (!is_array($conf)) return $return;

        $matches = parse_url($url);
        !isset($matches['host']) && $matches['host'] = '';
        !isset($matches['path']) && $matches['path'] = '';
        !isset($matches['query']) && $matches['query'] = '';
        !isset($matches['port']) && $matches['port'] = '';
        $host = $matches['host'];
        $path = $matches['path'] ? $matches['path'] . ($matches['query'] ? '?' . $matches['query'] : '') : '/';
        $port = !empty($matches['port']) ? $matches['port'] : 80;

        $conf_arr = array(
            'limit' => 0,
            'post' => '',
            'cookie' => '',
            'ip' => '',
            'timeout' => 15,
            'block' => TRUE,
        );

        foreach (array_merge($conf_arr, $conf) as $k => $v) ${$k} = $v;

        if ($post) {
            if (is_array($post)) {
                $post = http_build_query($post);
            }
            $out = "POST $path HTTP/1.0\r\n";
            $out .= "Accept: */*\r\n";
            //$out .= "Referer: $boardurl\r\n";
            $out .= "Accept-Language: en-us\r\n";
            $out .= "Content-Type: application/x-www-form-urlencoded\r\n";
            $out .= "User-Agent: $_SERVER[HTTP_USER_AGENT]\r\n";
            $out .= "Host: $host\r\n";
            $out .= 'Content-Length: ' . strlen($post) . "\r\n";
            $out .= "Connection: Close\r\n";
            $out .= "Cache-Control: no-cache\r\n";
            $out .= "Cookie: $cookie\r\n\r\n";
            $out .= $post;
        } else {
            $out = "GET $path HTTP/1.0\r\n";
            $out .= "Accept: */*\r\n";
            //$out .= "Referer: $boardurl\r\n";
            $out .= "Accept-Language: en-us\r\n";
            $out .= "User-Agent: $_SERVER[HTTP_USER_AGENT]\r\n";
            $out .= "Host: $host\r\n";
            $out .= "Connection: Close\r\n";
            $out .= "Cookie: $cookie\r\n\r\n";
        }
        $fp = @fsockopen(($ip ? $ip : $host), $port, $errno, $errstr, $timeout);
        if (!$fp) {
            return '';
        } else {
            stream_set_blocking($fp, $block);
            stream_set_timeout($fp, $timeout);
            @fwrite($fp, $out);
            $status = stream_get_meta_data($fp);
            if (!$status['timed_out']) {
                while (!feof($fp)) {
                    if (($header = @fgets($fp)) && ($header == "\r\n" || $header == "\n")) {
                        break;
                    }
                }

                $stop = false;
                while (!feof($fp) && !$stop) {
                    $data = fread($fp, ($limit == 0 || $limit > 8192 ? 8192 : $limit));
                    $return .= $data;
                    if ($limit) {
                        $limit -= strlen($data);
                        $stop = $limit <= 0;
                    }
                }
            }
            @fclose($fp);
            return $return;
        }
    }

    /**
     * downloadfile
     * canDesignationdownloadto showfilename,andautomaticsendcorrespondingofHeaderinformation
     * If you specifycontentparameter,thendownloadThatparameterContent
     * @static
     * @access public
     * @param string $filename downloadfilename
     * @param string $showname downloadto showfilename
     * @param string $content downloadContent
     * @param integer $expire downloadcontentBrowseDeviceCachetime
     * @return void
     */
    static public function download($filename, $showname = '', $content = '', $expire = 180)
    {
        if (is_file($filename)) {
            $length = filesize($filename);
        } elseif (is_file(UPLOAD_PATH . $filename)) {
            $filename = UPLOAD_PATH . $filename;
            $length = filesize($filename);
        } elseif ($content != '') {
            $length = strlen($content);
        } else {
            E($filename . L('Download the file does not exist!'));
        }
        if (empty($showname)) {
            $showname = $filename;
        }
        $showname = basename($showname);
        if (!empty($filename)) {
            $finfo = new \finfo(FILEINFO_MIME);
            $type = $finfo->file($filename);
        } else {
            $type = "application/octet-stream";
        }
        //sendHttp Headerinformation Startdownload
        header("Pragma: public");
        header("Cache-control: max-age=" . $expire);
        //header('Cache-Control: no-store, no-cache, must-revalidate');
        header("Expires: " . gmdate("D, d M Y H:i:s", time() + $expire) . "GMT");
        header("Last-Modified: " . gmdate("D, d M Y H:i:s", time()) . "GMT");
        header("Content-Disposition: attachment; filename=" . $showname);
        header("Content-Length: " . $length);
        header("Content-type: " . $type);
        header('Content-Encoding: none');
        header("Content-Transfer-Encoding: binary");
		// Empty file header information, resolve file download cannot open issue
        ob_clean(); // Empty buffer
        flush();  // Refreshing output buffers
        if ($content == '') {
            readfile($filename);
        } else {
            echo($content);
        }
        exit();
    }

    /**
     * displayHTTP Header information
     * @return string
     */
    static function getHeaderInfo($header = '', $echo = true)
    {
        ob_start();
        $headers = getallheaders();
        if (!empty($header)) {
            $info = $headers[$header];
            echo($header . ':' . $info . "\n");;
        } else {
            foreach ($headers as $key => $val) {
                echo("$key:$val\n");
            }
        }
        $output = ob_get_clean();
        if ($echo) {
            echo(nl2br($output));
        } else {
            return $output;
        }

    }

    /**
     * HTTP Protocol defined status codes
     * @param int $num
     */
    static function sendHttpStatus($code)
    {
        static $_status = array(
            // Informational 1xx
            100 => 'Continue',
            101 => 'Switching Protocols',

            // Success 2xx
            200 => 'OK',
            201 => 'Created',
            202 => 'Accepted',
            203 => 'Non-Authoritative Information',
            204 => 'No Content',
            205 => 'Reset Content',
            206 => 'Partial Content',

            // Redirection 3xx
            300 => 'Multiple Choices',
            301 => 'Moved Permanently',
            302 => 'Found',  // 1.1
            303 => 'See Other',
            304 => 'Not Modified',
            305 => 'Use Proxy',
            // 306 is deprecated but reserved
            307 => 'Temporary Redirect',

            // Client Error 4xx
            400 => 'Bad Request',
            401 => 'Unauthorized',
            402 => 'Payment Required',
            403 => 'Forbidden',
            404 => 'Not Found',
            405 => 'Method Not Allowed',
            406 => 'Not Acceptable',
            407 => 'Proxy Authentication Required',
            408 => 'Request Timeout',
            409 => 'Conflict',
            410 => 'Gone',
            411 => 'Length Required',
            412 => 'Precondition Failed',
            413 => 'Request Entity Too Large',
            414 => 'Request-URI Too Long',
            415 => 'Unsupported Media Type',
            416 => 'Requested Range Not Satisfiable',
            417 => 'Expectation Failed',

            // Server Error 5xx
            500 => 'Internal Server Error',
            501 => 'Not Implemented',
            502 => 'Bad Gateway',
            503 => 'Service Unavailable',
            504 => 'Gateway Timeout',
            505 => 'HTTP Version Not Supported',
            509 => 'Bandwidth Limit Exceeded'
        );
        if (isset($_status[$code])) {
            header('HTTP/1.1 ' . $code . ' ' . $_status[$code]);
        }
    }
	 /**
     * Post data
     *
     * @param string $url
     * @param array $data
     * @param array $cookie
     * @param int $timeout
     * @return array
     */
    public static function Post($url, $data = array(), $cookie = array(), $timeout = 3)
    {
        $info = parse_url($url);
        $host = $info['host'];
        $page = $info['path'] . ($info['query']?'?' . $info['query']:'');
        $port = $info['port']?$info['port']:80;
        return self::async('POST', $host, $page, $port, $data, $cookie, $timeout);
    }

    /**
     *Get data
     *
     * @param string $url
     * @param array $cookie
     * @param int $timeout
     * @return array
     */
    static public function Get($url, $cookie=null, $timeout = 3)
    {
        $info = parse_url($url);
        $host = $info['host'];
        $page = $info['path'] . ($info['query']?'?' . $info['query']:'');
        $port = $info['port']?$info['port']:80;
        return self::async('GET', $host, $page, $port, null, $cookie, $timeout);
    }

	
	 /**
     * Asynchronous connection
     *
     * @param string $type
     * @param string $host
     * @param string $page
     * @param int $port
     * @param array $data
     * @param array $cookie
     * @param int $timeout
     * @return array
     */
    private static function async($type, $host, $page, $port = 80, $data = array(), $cookie = array(), $timeout = 3)
    {
        $type = $type == 'POST'?'POST':'GET';
        $errno = $errstr = null;
        $Content = array();
        if($type == 'POST' && $data && is_array($data)){
            foreach ($data as $k=>$v)
                $Content[] = $k . "=" . rawurlencode($v);
            $Content = implode("&", $Content);
        }
        //
        $fp = fsockopen($host, $port, $errno, $errstr, $timeout);
        if(!$fp)
            return array(false, 'Tip: cant connect!');
        $stream = "{$type} /{$page} HTTP/1.1\r\n";
        $stream .= "Host: {$host}\r\n";
        if($Content && $type == 'POST'){
            $stream .= "Content-Type: application/x-www-form-urlencoded\r\n";
            $stream .= "Content-Length: " . strlen($Content) . "\r\n";
        }
        if($cookie && is_array($cookie)){
            $stream .= "Connection: Close\r\n";
            $stream .= 'Cookie:';
            $tmp = array();
            foreach ($cookie as $k=>$v)
                $tmp[] = "{$k}={$v}";
            $stream .= implode('; ', $tmp);
            $stream .= "\r\n\r\n";
        }else{
            $stream .= "Connection: Close\r\n\r\n";
        }
        fwrite($fp, $stream);
        if($Content){
            usleep(10);
            fwrite($fp, $Content);
        }
        stream_set_timeout($fp, $timeout);
        $res = stream_get_contents($fp);
        $info = stream_get_meta_data($fp);
        fclose($fp);
        if($info['timed_out']){
            return array(false, 'Tip: Connection timed out');
        }else{
            return array(true, substr(strstr($res, "\r\n\r\n"), 4));
        }
    }
}//classdefinitionEnd