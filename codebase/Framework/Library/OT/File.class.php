<?php
// +----------------------------------------------------------------------
// Files and folders processing class
// +----------------------------------------------------------------------

namespace OT;

class File
{

    /**
     * Create a directory
     * @param $dir  table of Contents Name
     * @return boolean true success, false failure
     */
    static public function mk_dir($dir)
    {
        $dir = rtrim($dir, '/') . '/';
        if (!is_dir($dir)) {
            if (mkdir($dir, 0700) == false) {
                return false;
            }
            return true;
        }
        return true;
    }

    /**
     * Read the contents of the file
     * @param $filename  filename
     * @return string document content
     */
    static public function read_file($filename)
    {
        $content = '';
        if (function_exists('file_get_contents')) {
            @$content = file_get_contents($filename);
        } else {
            if (@$fp = fopen($filename, 'r')) {
                @$content = fread($fp, filesize($filename));
                @fclose($fp);
            }
        }
        return $content;
    }

    /**
     * Write file
     * @param $filename  filename
     * @param $writetext document content
     * @param $openmod    Open
     * @return boolean true success, false failure
     */
    static function write_file($filename, $writetext, $openmod = 'w')
    {
        if (@$fp = fopen($filename, $openmod)) {
            flock($fp, 2);
            fwrite($fp, $writetext);
            fclose($fp);
            return true;
        } else {
            return false;
        }
    }

    /**
     * Remove directory
     * @param $dirName    Original directory
     * @return boolean true success, false failure
     */
    static function del_dir($dirName)
    {
        if (!file_exists($dirName)) {
            return false;
        }

        $dir = opendir($dirName);
        while ($fileName = readdir($dir)) {
            $file = $dirName . '/' . $fileName;
            if ($fileName != '.' && $fileName != '..') {
                if (is_dir($file)) {
                    self::del_dir($file);
                } else {
                    unlink($file);
                }
            }
        }
        closedir($dir);
        return rmdir($dirName);
    }

    /**
     * Copy directory
     * @param $surDir    Original directory
     * @param $toDir    Destination directory
     * @return boolean true success, false failure
     */
    static function copy_dir($surDir, $toDir)
    {
        $surDir = rtrim($surDir, '/') . '/';
        $toDir = rtrim($toDir, '/') . '/';
        if (!file_exists($surDir)) {
            return false;
        }

        if (!file_exists($toDir)) {
            self::mk_dir($toDir);
        }
        $file = opendir($surDir);
        while ($fileName = readdir($file)) {
            $file1 = $surDir . '/' . $fileName;
            $file2 = $toDir . '/' . $fileName;
            if ($fileName != '.' && $fileName != '..') {
                if (is_dir($file1)) {
                    self::copy_dir($file1, $file2);
                } else {
                    copy($file1, $file2);
                }
            }
        }
        closedir($file);
        return true;
    }

    /**
     * List directory
     * @param $dir  table of Contents Name
     * @return table of ContentsArray。Listsfilefolder下content,returnArray $dirArray['dir']:Save folder;$dirArray['file']: Save File
     */
    static function get_dirs($dir)
    {
        $dir = rtrim($dir, '/') . '/';
        $dirArray [][] = NULL;
        if (false != ($handle = opendir($dir))) {
            $i = 0;
            $j = 0;
            while (false !== ($file = readdir($handle))) {
                if (is_dir($dir . $file)) { //Determine whether the folder
                    $dirArray ['dir'] [$i] = $file;
                    $i++;
                } else {
                    $dirArray ['file'] [$j] = $file;
                    $j++;
                }
            }
            closedir($handle);
        }
        return $dirArray;
    }

    /**
     * Statistical Folder Size
     * @param $dir  table of Contents Name
     * @return number fileFolder Size(unit B)
     */
    static function get_size($dir)
    {
        $dirlist = opendir($dir);
        $dirsize = 0;
        while (false !== ($folderorfile = readdir($dirlist))) {
            if ($folderorfile != "." && $folderorfile != "..") {
                if (is_dir("$dir/$folderorfile")) {
                    $dirsize += self::get_size("$dir/$folderorfile");
                } else {
                    $dirsize += filesize("$dir/$folderorfile");
                }
            }
        }
        closedir($dirlist);
        return $dirsize;
    }

    /**
     * Detecting whether an empty folder
     * @param $dir  table of Contents Name
     * @return boolean true air, fasle not null
     */
    static function empty_dir($dir)
    {
        return (($files = @scandir($dir)) && count($files) <= 2);
    }

    /**
     * File cache and file read
     * @param $name  filename
     * @param $value  document content,Empty the cache acquisition
     * @param $path   File directory,The default is the current applicationDATAtable of Contents
     * @param $cached  Whether the cached results,The default cache
     * @return Return the cached content
     */
    function cache($name, $value = '', $path = DATA_PATH, $cached = true)
    {
        static $_cache = array();
        $filename = $path . $name . '.php';
        if ('' !== $value) {
            if (is_null($value)) {
                // Delete Cache
                return false !== strpos($name, '*') ? array_map("unlink", glob($filename)) : unlink($filename);
            } else {
                // Cachedata
                $dir = dirname($filename);
                // table of Contentsdoes not existCreate
                if (!is_dir($dir))
                    mkdir($dir, 0755, true);
                $_cache[$name] = $value;
                return file_put_contents($filename, strip_whitespace("<?php\treturn " . var_export($value, true) . ";?>"));
            }
        }
        if (isset($_cache[$name]) && $cached == true) return $_cache[$name];
        // ObtainCachedata
        if (is_file($filename)) {
            $value = include $filename;
            $_cache[$name] = $value;
        } else {
            $value = false;
        }
        return $value;
    }

}