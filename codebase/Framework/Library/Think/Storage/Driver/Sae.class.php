<?php
// +----------------------------------------------------------------------
// | TOPThink [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013 http://topthink.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: luofei614 <weibo.com/luofei614>
// +----------------------------------------------------------------------
namespace Think\Storage\Driver;

use Think\Storage;

// SAEEnvironment file is written to storage class
class Sae extends Storage
{

    /**
     * Architecturefunction
     * @access public
     */
    private $mc;
    private $kvs = array();
    private $htmls = array();
    private $contents = array();

    public function __construct()
    {
        if (!function_exists('memcache_init')) {
            header('Content-Type:text/html;charset=utf-8');
            exit('please atSAERun code on the platform.');
        }
        $this->mc = @memcache_init();
        if (!$this->mc) {
            header('Content-Type:text/html;charset=utf-8');
            exit('Youre not onMemcacheServices, pleaseSAEManagement Platform InitializationMemcacheservice');
        }
    }

    /**
     * obtainSaeKvObjects
     */
    private function getKv()
    {
        static $kv;
        if (!$kv) {
            $kv = new \SaeKV();
            if (!$kv->init())
                E('You do not initializeKVDB,please atSAEManagement Platform InitializationKVDBservice');
        }
        return $kv;
    }


    /**
     * Read the contents of the file
     * @access public
     * @param string $filename filename
     * @return string
     */
    public function read($filename, $type = '')
    {
        switch (strtolower($type)) {
            case 'f':
                $kv = $this->getKv();
                if (!isset($this->kvs[$filename])) {
                    $this->kvs[$filename] = $kv->get($filename);
                }
                return $this->kvs[$filename];
            default:
                return $this->get($filename, 'content', $type);
        }
    }

    /**
     * File Write
     * @access public
     * @param string $filename filename
     * @param string $content document content
     * @return boolean
     */
    public function put($filename, $content, $type = '')
    {
        switch (strtolower($type)) {
            case 'f':
                $kv = $this->getKv();
                $this->kvs[$filename] = $content;
                return $kv->set($filename, $content);
            case 'html':
                $kv = $this->getKv();
                $content = time() . $content;
                $this->htmls[$filename] = $content;
                return $kv->set($filename, $content);
            default:
                $content = time() . $content;
                if (!$this->mc->set($filename, $content, MEMCACHE_COMPRESSED, 0)) {
                    E(L('_STORAGE_WRITE_ERROR_') . ':' . $filename);
                } else {
                    $this->contents[$filename] = $content;
                    return true;
                }
        }
    }

    /**
     * File additional written
     * @access public
     * @param string $filename filename
     * @param string $content Additional content files
     * @return boolean
     */
    public function append($filename, $content, $type = '')
    {
        if ($old_content = $this->read($filename, $type)) {
            $content = $old_content . $content;
        }
        return $this->put($filename, $content, $type);
    }

    /**
     * Load file
     * @access public
     * @param string $_filename filename
     * @param array $vars Incoming variables
     * @return void
     */
    public function load($_filename, $vars = null)
    {
        if (!is_null($vars))
            extract($vars, EXTR_OVERWRITE);
        eval('?>' . $this->read($_filename));
    }

    /**
     * If a file exists
     * @access public
     * @param string $filename filename
     * @return boolean
     */
    public function has($filename, $type = '')
    {
        if ($this->read($filename, $type)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * File deletion
     * @access public
     * @param string $filename filename
     * @return boolean
     */
    public function unlink($filename, $type = '')
    {
        switch (strtolower($type)) {
            case 'f':
                $kv = $this->getKv();
                unset($this->kvs[$filename]);
                return $kv->delete($filename);
            case 'html':
                $kv = $this->getKv();
                unset($this->htmls[$filename]);
                return $kv->delete($filename);
            default:
                unset($this->contents[$filename]);
                return $this->mc->delete($filename);
        }
    }

    /**
     * Reads the file information
     * @access public
     * @param string $filename filename
     * @param string $name information Name mtimeorcontent
     * @return boolean
     */
    public function get($filename, $name, $type = '')
    {
        switch (strtolower($type)) {
            case 'html':
                if (!isset($this->htmls[$filename])) {
                    $kv = $this->getKv();
                    $this->htmls[$filename] = $kv->get($filename);
                }
                $content = $this->htmls[$filename];
                break;
            default:
                if (!isset($this->contents[$filename])) {
                    $this->contents[$filename] = $this->mc->get($filename);
                }
                $content = $this->contents[$filename];
        }
        if (false === $content) {
            return false;
        }
        $info = array(
            'mtime' => substr($content, 0, 10),
            'content' => substr($content, 10)
        );
        return $info[$name];
    }

}