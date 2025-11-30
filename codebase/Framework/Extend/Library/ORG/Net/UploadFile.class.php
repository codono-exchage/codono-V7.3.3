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

/**
 * File upload class
 * @category   ORG
 * @package  ORG
 * @subpackage  Net
 * @author    liu21st <liu21st@gmail.com>
 */
class UploadFile
{//Class definitions begin

    private $config = array(
        'maxSize' => -1,    // Maximum upload file
        'supportMulti' => true,    // Whether to support multi-file upload
        'allowExts' => array(),    // Allowed filessuffix Blank check without suffix
        'allowTypes' => array(),    // Allow file types to upload Not blank checks
        'thumb' => false,    // useCorrectUploadimageEnterRowThumbnailsdeal with
        'imageClassPath' => 'ORG.Util.Image',    // Class library package path
        'thumbMaxWidth' => '',// The maximum width of the thumbnail
        'thumbMaxHeight' => '',// The maximum height of the thumbnail
        'thumbPrefix' => 'thumb_',// Thumbnail prefix
        'thumbSuffix' => '',
        'thumbPath' => '',// Thumbnails save path
        'thumbFile' => '',// Thumbnailsfilename
        'thumbExt' => '',// ThumbnailsExtension        
        'thumbRemoveOrigin' => false,// Whether to remove the picture
        'thumbType' => 1, // Thumbnail generation mode 1 By setting the size of the taken 0 FIG isometric original thumbnail
        'zipImages' => false,// Compressed image files to upload
        'autoSub' => false,// Save the file to enable subdirectory
        'subType' => 'hash',// childtable of Contentscreatethe way can usehash date custom
        'subDir' => '', // Subdirectory name subTypeforcustomAfter effective way
        'dateFormat' => 'Ymd',
        'hashLevel' => 1, // hashDirectory hierarchy
        'savePath' => '',// Upload file path
        'autoCheck' => true, // Automatically check if accessory
        'uploadReplace' => false,// The same name exists Overwrite
        'saveRule' => 'uniqid',// upload Files namerule
        'hashType' => 'md5_file',// upload filesHashrulefunctionname
    );

    // Error Messages
    private $error = '';
    // Upload successfulFile information
    private $uploadFileInfo;

    public function __get($name)
    {
        if (isset($this->config[$name])) {
            return $this->config[$name];
        }
        return null;
    }

    public function __set($name, $value)
    {
        if (isset($this->config[$name])) {
            $this->config[$name] = $value;
        }
    }

    public function __isset($name)
    {
        return isset($this->config[$name]);
    }

    /**
     * Architecturefunction
     * @access public
     * @param array $config Upload parameters
     */
    public function __construct($config = array())
    {
        if (is_array($config)) {
            $this->config = array_merge($this->config, $config);
        }
    }

    /**
     * Upload a file
     * @access public
     * @param mixed $name data
     * @param string $value data Sheet Name
     * @return string
     */
    private function save($file)
    {
        $filename = $file['savepath'] . $file['savename'];
        if (!$this->uploadReplace && is_file($filename)) {
            // Do not overwrite files with the same name
            $this->error = 'The file already exists!' . $filename;
            return false;
        }
        // If the file is an image Detect file format
        if (in_array(strtolower($file['extension']), array('gif', 'jpg', 'jpeg', 'bmp', 'png', 'swf'))) {
            $info = getimagesize($file['tmp_name']);
            if (false === $info || ('gif' == strtolower($file['extension']) && empty($info['bits']))) {
                $this->error = 'Illegal image files';
                return false;
            }
        }
        if (!move_uploaded_file($file['tmp_name'], $this->autoCharset($filename, 'utf-8', 'gbk'))) {
            $this->error = 'Save the file upload error!';
            return false;
        }
        if ($this->thumb && in_array(strtolower($file['extension']), array('gif', 'jpg', 'jpeg', 'bmp', 'png'))) {
            $image = getimagesize($filename);
            if (false !== $image) {
                //YesimagefileGenerate thumbnails
                $thumbWidth = explode(',', $this->thumbMaxWidth);
                $thumbHeight = explode(',', $this->thumbMaxHeight);
                $thumbPrefix = explode(',', $this->thumbPrefix);
                $thumbSuffix = explode(',', $this->thumbSuffix);
                $thumbFile = explode(',', $this->thumbFile);
                $thumbPath = $this->thumbPath ? $this->thumbPath : dirname($filename) . '/';
                $thumbExt = $this->thumbExt ? $this->thumbExt : $file['extension']; //fromdefinitionThumbnailsExtension
                // Generates image thumbnails
                import($this->imageClassPath);
                for ($i = 0, $len = count($thumbWidth); $i < $len; $i++) {
                    if (!empty($thumbFile[$i])) {
                        $thumbname = $thumbFile[$i];
                    } else {
                        $prefix = isset($thumbPrefix[$i]) ? $thumbPrefix[$i] : $thumbPrefix[0];
                        $suffix = isset($thumbSuffix[$i]) ? $thumbSuffix[$i] : $thumbSuffix[0];
                        $thumbname = $prefix . basename($filename, '.' . $file['extension']) . $suffix;
                    }
                    if (1 == $this->thumbType) {
                        Image::thumb2($filename, $thumbPath . $thumbname . '.' . $thumbExt, '', $thumbWidth[$i], $thumbHeight[$i], true);
                    } else {
                        Image::thumb($filename, $thumbPath . $thumbname . '.' . $thumbExt, '', $thumbWidth[$i], $thumbHeight[$i], true);
                    }

                }
                if ($this->thumbRemoveOrigin) {
                    // Generate thumbnailsafter thatdeleteArtwork
                    unlink($filename);
                }
            }
        }
        if ($this->zipImags) {
            // TODO CorrectImage CompressionOnline-extracting package

        }
        return true;
    }

    /**
     * Upload all files
     * @access public
     * @param string $savePath Upload file path
     * @return string
     */
    public function upload($savePath = '')
    {
        //in case不Designationsave documentname,thenbysystemdefault
        if (empty($savePath))
            $savePath = $this->savePath;
        // Check the upload directory
        if (!is_dir($savePath)) {
            // an examinationtable of ContentswhethercodingAfter
            if (is_dir(base64_decode($savePath))) {
                $savePath = base64_decode($savePath);
            } else {
                // tryCreate a directory
                if (!mkdir($savePath)) {
                    $this->error = 'Upload directory' . $savePath . 'does not exist';
                    return false;
                }
            }
        } else {
            if (!is_writeable($savePath)) {
                $this->error = 'Upload directory' . $savePath . 'Not writable';
                return false;
            }
        }
        $fileInfo = array();
        $isUpload = false;

        // Get uploaded file information
        // Correct$_FILESArray Information Processing
        $files = $this->dealFiles($_FILES);
        foreach ($files as $key => $file) {
            //Filter invalidofUpload
            if (!empty($file['name'])) {
                //RegisterUpload fileSpreadinformation
                if (!isset($file['key'])) $file['key'] = $key;
                $file['extension'] = $this->getExt($file['name']);
                $file['savepath'] = $savePath;
                $file['savename'] = $this->getSaveName($file);

                // automatican examinationannex
                if ($this->autoCheck) {
                    if (!$this->check($file))
                        return false;
                }

                //Storageupload files
                if (!$this->save($file)) return false;
                if (function_exists($this->hashType)) {
                    $fun = $this->hashType;
                    $file['hash'] = $fun($this->autoCharset($file['savepath'] . $file['savename'], 'utf-8', 'gbk'));
                }
                //Upload successfulRearsave documentinformation,For the resttransfer
                unset($file['tmp_name'], $file['error']);
                $fileInfo[] = $file;
                $isUpload = true;
            }
        }
        if ($isUpload) {
            $this->uploadFileInfo = $fileInfo;
            return true;
        } else {
            $this->error = 'I did not choose to upload files';
            return false;
        }
    }

    /**
     * UploadsingleUploadFieldmiddlefile Support for multiple attachments
     * @access public
     * @param array $file Upload file information
     * @param string $savePath Upload file path
     * @return string
     */
    public function uploadOne($file, $savePath = '')
    {
        //in case not Designation save document name, then by system default
        if (empty($savePath))
            $savePath = $this->savePath;
        // Check the upload directory
        if (!is_dir($savePath)) {
            // tryCreate a directory
            if (!mkdir($savePath, 0777, true)) {
                $this->error = 'Upload directory' . $savePath . 'does not exist';
                return false;
            }
        } else {
            if (!is_writeable($savePath)) {
                $this->error = 'Upload directory' . $savePath . 'Not writable';
                return false;
            }
        }
        //Filter invalidofUpload
        if (!empty($file['name'])) {
			
            $fileArray = array();
            if (is_array($file['name'])) {
                $keys = array_keys($file);
                $count = count($file['name']);
                for ($i = 0; $i < $count; $i++) {
                    foreach ($keys as $key)
                        $fileArray[$i][$key] = $file[$key][$i];
                }
            } else {
                $fileArray[] = $file;
            }
            $info = array();
            foreach ($fileArray as $key => $file) {
                //RegisterUpload fileSpreadinformation
                $file['extension'] = $this->getExt($file['name']);
                $file['savepath'] = $savePath;
                $file['savename'] = $this->getSaveName($file);
                // automatican examinationannex
                if ($this->autoCheck) {
                    if (!$this->check($file))
                        return false;
                }
                //Storageupload files
                if (!$this->save($file)) return false;
                if (function_exists($this->hashType)) {
                    $fun = $this->hashType;
                    $file['hash'] = $fun($this->autoCharset($file['savepath'] . $file['savename'], 'utf-8', 'gbk'));
                }
                unset($file['tmp_name'], $file['error']);
                $info[] = $file;
            }
            // returnUpload file information
            return $info;
        } else {
            $this->error = 'I did not choose to upload files';
            return false;
        }
    }

    /**
     * Changeupload filesArrayvariableIs correctThe way
     * @access private
     * @param array $files Upload file variable
     * @return array
     */
    private function dealFiles($files)
    {
        $fileArray = array();
        $n = 0;
        foreach ($files as $key => $file) {
            if (is_array($file['name'])) {
                $keys = array_keys($file);
                $count = count($file['name']);
                for ($i = 0; $i < $count; $i++) {
                    $fileArray[$n]['key'] = $key;
                    foreach ($keys as $_key) {
                        $fileArray[$n][$_key] = $file[$_key][$i];
                    }
                    $n++;
                }
            } else {
                $fileArray[$key] = $file;
            }
        }
        return $fileArray;
    }

    /**
     * Gets the error code information
     * @access public
     * @param string $errorNo Wrong number
     * @return void
     */
    protected function error($errorNo)
    {
        switch ($errorNo) {
            case 1:
                $this->error = 'The uploaded file exceeds the php.ini in upload_max_filesize Options limit values';
                break;
            case 2:
                $this->error = 'Upload fileSize exceeds HTML Form MAX_FILE_SIZE Value of the option specified';
                break;
            case 3:
                $this->error = 'File was only partially uploaded';
                break;
            case 4:
                $this->error = 'No file was uploaded';
                break;
            case 6:
                $this->error = 'Missing a temporary folder';
                break;
            case 7:
                $this->error = 'File write failed';
                break;
            default:
                $this->error = 'Unknown upload error!';
        }
        return;
    }

    /**
     * according toupload Files nameruleObtainsave documentname
     * @access private
     * @param string $filename data
     * @return string
     */
    private function getSaveName($filename)
    {
        $rule = $this->saveRule;
        if (empty($rule)) {//Nodefinition命 Namerule,thenTo keep the same file name
            $saveName = $filename['name'];
        } else {
            if (function_exists($rule)) {
                //usefunctionFormOneonlyfileMarknumber
                $saveName = $rule() . "." . $filename['extension'];
            } else {
                //usegivendocumentnameAs aMarknumber
                $saveName = $rule . "." . $filename['extension'];
            }
        }
        if ($this->autoSub) {
            // usechildtable of Contentssave document
            $filename['savename'] = $saveName;
            $saveName = $this->getSubName($filename) . $saveName;
        }
        return $saveName;
    }

    /**
     * Get the name of a subdirectory
     * @access private
     * @param array $file Upload file information
     * @return string
     */
    private function getSubName($file)
    {
        switch ($this->subType) {
            case 'custom':
                $dir = $this->subDir;
                break;
            case 'date':
                $dir = date($this->dateFormat, time()) . '/';
                break;
            case 'hash':
            default:
                $name = md5($file['savename']);
                $dir = '';
                for ($i = 0; $i < $this->hashLevel; $i++) {
                    $dir .= $name[$i] . '/';
                }
                break;
        }
        if (!is_dir($file['savepath'] . $dir)) {
            mkdir($file['savepath'] . $dir, 0777, true);
        }
        return $dir;
    }


    /**
     * Check the uploaded files
     * @access private
     * @param array $file File information
     * @return boolean
     */
    private function check($file)
    {
        if ($file['error'] !== 0) {
            //File upload failed
            //Capture Error Codes
            $this->error($file['error']);
            return false;
        }
        //fileUpload successful, Selfdefinitionrulean examination
        //Check the file size
        if (!$this->checkSize($file['size'])) {
            $this->error = 'Upload file does not match the size!';
            return false;
        }

        //Check the fileMimeTypes of
        if (!$this->checkType($file['type'])) {
            $this->error = 'upload filesMIMEType is not allowed!';
            return false;
        }
        //Check the file type
        if (!$this->checkExt($file['extension'])) {
            $this->error = 'Upload file type is not allowed';
            return false;
        }

        //Check the legality of uploading
        if (!$this->checkUpload($file['tmp_name'])) {
            $this->error = 'Upload illegal files!';
            return false;
        }
        return true;
    }

    // automaticChangecharacterCollection stand byArrayChange
    private function autoCharset($fContents, $from = 'gbk', $to = 'utf-8')
    {
        $from = strtoupper($from) == 'UTF8' ? 'utf-8' : $from;
        $to = strtoupper($to) == 'UTF8' ? 'utf-8' : $to;
        if (strtoupper($from) === strtoupper($to) || empty($fContents) || (is_scalar($fContents) && !is_string($fContents))) {
            //in case coding same or non-String Scalar quantity then not Change
            return $fContents;
        }
        if (function_exists('mb_convert_encoding')) {
            return mb_convert_encoding($fContents, $to, $from);
        } elseif (function_exists('iconv')) {
            return iconv($from, $to, $fContents);
        } else {
            return $fContents;
        }
    }

    /**
     * Check the uploaded filesType the legality
     * @access private
     * @param string $type data
     * @return boolean
     */
    private function checkType($type)
    {
        if (!empty($this->allowTypes))
            return in_array(strtolower($type), $this->allowTypes);
        return true;
    }


    /**
     * Check the uploaded filessuffixwhetherlegitimate
     * @access private
     * @param string $ext Suffix Name
     * @return boolean
     */
    private function checkExt($ext)
    {
        if (!empty($this->allowExts))
            return in_array(strtolower($ext), $this->allowExts, true);
        return true;
    }

    /**
     * Check the file sizewhetherlegitimate
     * @access private
     * @param integer $size data
     * @return boolean
     */
    private function checkSize($size)
    {
        return !($size > $this->maxSize) || (-1 == $this->maxSize);
    }

    /**
     * Check the filewhethernon-lawsubmit
     * @access private
     * @param string $filename filename
     * @return boolean
     */
    private function checkUpload($filename)
    {
        return is_uploaded_file($filename);
    }

    /**
     * Obtain the suffix upload files
     * @access private
     * @param string $filename filename
     * @return boolean
     */
    private function getExt($filename)
    {
        $pathinfo = pathinfo($filename);
        return $pathinfo['extension'];
    }

    /**
     * Obtain information uploaded files
     * @access public
     * @return array
     */
    public function getUploadFileInfo()
    {
        return $this->uploadFileInfo;
    }

    /**
     * Obtainthe last timeError Messages
     * @access public
     * @return string
     */
    public function getErrorMsg()
    {
        return $this->error;
    }
}
