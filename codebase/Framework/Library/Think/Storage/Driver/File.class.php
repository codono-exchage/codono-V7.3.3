<?php
// +----------------------------------------------------------------------
// | TOPThink [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013 http://topthink.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: support@codono.com
// +----------------------------------------------------------------------
namespace Think\Storage\Driver;

use Think\Storage;

// Local file storage class write
class File extends Storage
{

    private $contents = array();

    /**
     * Architecturefunction
     * @access public
     */
    public function __construct()
    {
    }

    /**
     * Read the contents of the file
     * @access public
     * @param string $filename filename
     * @return string
     */
    public function read($filename, $type = '')
    {
        return $this->get($filename, 'content', $type);
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
        $dir = dirname($filename);
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        if (false === file_put_contents($filename, $content)) {
            E(L('_STORAGE_WRITE_ERROR_') . ':' . $filename);
        } else {
            $this->contents[$filename] = $content;
            return true;
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
        if (is_file($filename)) {
            $content = $this->read($filename, $type) . $content;
        }
        return $this->put($filename, $content, $type);
    }

    /**
     * Load file
     * @access public
     * @param string $filename filename
     * @param array $vars Incoming variables
     * @return void
     */
    public function load($_filename, $vars = null)
    {
        if (!is_null($vars)) {
            extract($vars, EXTR_OVERWRITE);
        }
        include $_filename;
    }

    /**
     * If a file exists
     * @access public
     * @param string $filename filename
     * @return boolean
     */
    public function has($filename, $type = '')
    {
        return is_file($filename);
    }

    /**
     * File deletion
     * @access public
     * @param string $filename filename
     * @return boolean
     */
    public function unlink($filename, $type = '')
    {
        unset($this->contents[$filename]);
        return is_file($filename) && unlink($filename);
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
        if (!isset($this->contents[$filename])) {
            if (!is_file($filename)) return false;
            $this->contents[$filename] = file_get_contents($filename);
        }
        $content = $this->contents[$filename];
        $info = array(
            'mtime' => filemtime($filename),
            'content' => $content
        );
        return $info[$name];
    }
}
