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
namespace Think;
class Upload
{
    /**
     * defaultUpload configuration
     * @var array
     */
    private $config = array(
        'mimes' => array(), //Allowed filesMiMeTypes of
        'maxSize' => 0, //Upload file size limit (0-No limit)
        'autoSub' => true, //Auto-save files subdirectory
        'subName' => array('date', 'Y-m-d'), //childtable of Contentscreatethe way,[0]-functionname,[1]-parameter,MoreparameteruseArray
        'rootPath' => './Uploads/', //Save the root path
        'savePath' => '', //save route
        'saveName' => array('uniqid', ''), //upload Files namerule,[0]-functionname,[1]-parameter,MoreparameteruseArray
        'saveExt' => '', //fileStoragesuffix,Empty theuseoriginalsuffix
        'replace' => false, //The same name exists Overwrite
        'hash' => true, //Whether to generatehashcoding
        'callback' => false, //DetectIf a file existsCallback,in caseexistreturnFile information array
        'driver' => '', // File upload driver
        'exts' => array('jpg', 'jpeg', 'png', 'pdf'), // Allowed file extensions
        'driverConfig' => array(), // Upload drive configuration
    );

    /**
     * Upload error message
     * @var string
     */
    private $error = ''; //Upload error message

    /**
     * Upload drive examples
     * @var Object
     */
    private $uploader;

    /**
     * structuremethod,ForstructureUploadExamples
     * @param array $config Configuration
     * @param string $driver Upload the drive you want to use LOCAL-localUpload Driver,FTP-FTPUpload Driver
     */
    public function __construct($config = array(), $driver = '', $driverConfig = null)
    {
        /* Get Configuration */
        $this->config = array_merge($this->config, $config);

        /* Set upload driver */
        $this->setDriver($driver, $driverConfig);

        /* AdjustmentConfiguration,TheStringConfiguration parametersConverted toArray */
        if (!empty($this->config['mimes'])) {
            if (is_string($this->mimes)) {
                $this->config['mimes'] = explode(',', $this->mimes);
            }
            $this->config['mimes'] = array_map('strtolower', $this->mimes);
        }
        if (!empty($this->config['exts'])) {
            if (is_string($this->exts)) {
                $this->config['exts'] = explode(',', $this->exts);
            }
            $this->config['exts'] = array_map('strtolower', $this->exts);
        }
    }

    /**
     * use $this->name Get Configuration
     * @param  string $name Configuration Name
     * @return multitype    Configuration values
     */
    public function __get($name)
    {
        return $this->config[$name];
    }

    public function __set($name, $value)
    {
        if (isset($this->config[$name])) {
            $this->config[$name] = $value;
            if ($name == 'driverConfig') {
                //changeDrive configurationRearResetUpload Driver
                //note：have toChanges in electiondriveThen changeDrive configuration
                $this->setDriver();
            }
        }
    }

    public function __isset($name)
    {
        return isset($this->config[$name]);
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
     * Upload a single file
     * @param  array $file An array of files
     * @return array        Upload successfulRearFile information
     */
    public function uploadOne($file)
    {
        $info = $this->upload(array($file));
        return $info ? $info[0] : $info;
    }

    /**
     * upload files
     * @param File information array $files ,usually $_FILESArray
     */
    public function upload($files = '')
    {
        if ('' === $files) {
            $files = $_FILES;
        }
        if (empty($files)) {
            $this->error = 'Not uploaded files!';
            return false;
        }

        /* DetectUploadRoot directory */
        if (!$this->uploader->checkRootPath($this->rootPath)) {
            $this->error = $this->uploader->getError();
            return false;
        }

        /* Check the upload directory */
        if (!$this->uploader->checkSavePath($this->savePath)) {
            $this->error = $this->uploader->getError();
            return false;
        }

        /* one by oneDetectandupload files */
        $info = array();
        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
        }
        // Correctupload filesArray Information Processing
        $files = $this->dealFiles($files);
        foreach ($files as $key => $file) {
            $file['name'] = preg_replace('/[^A-Za-z0-9._-]/', '_', basename($file['name']));
            if (!isset($file['key'])) $file['key'] = $key;
            /* bySpreadGet FileTypes of,cansolveFLASHUpload$FILESArrayreturnfile typeerrorofproblem */
            if (isset($finfo)) {
                $file['type'] = finfo_file($finfo, $file['tmp_name']);
            }

            /* Obtainupload filessuffix,allowUploadnosuffixfile */
            $file['ext'] = pathinfo($file['name'], PATHINFO_EXTENSION);

            /* Upload file detection */
            if (!$this->check($file)) {
                continue;
            }

            /* Get Filehash */
            if ($this->hash) {
                $file['md5'] = md5_file($file['tmp_name']);
                $file['sha1'] = sha1_file($file['tmp_name']);
            }

            /* Callback functionDetectIf a file exists */
            $data = call_user_func($this->callback, $file);
            if ($this->callback && $data) {
                if (file_exists('.' . $data['path'])) {
                    $info[$key] = $data;
                    continue;
                } elseif ($this->removeTrash) {
                    call_user_func($this->removeTrash, $data);//According delete junk
                }
            }

            /* Formsave documentname */
            $savename = $this->getSaveName($file);
            if (false == $savename) {
                continue;
            } else {
                $file['savename'] = $savename;
            }

            /* Detection and create a subdirectory */
            $subpath = $this->getSubPath($file['name']);
            if (false === $subpath) {
                continue;
            } else {
                $file['savepath'] = $this->savePath . $subpath;
            }

            /* CorrectimagefileStrictDetect */
            $ext = strtolower($file['ext']);
            if (in_array($ext, array('gif', 'jpg', 'jpeg', 'bmp', 'png', 'swf'))) {
                $imginfo = getimagesize($file['tmp_name']);
                if (empty($imginfo) || ($ext == 'gif' && empty($imginfo['bits']))) {
                    $this->error = 'Illegal image files!';
                    continue;
                }
            }

            /* save document andrecordingStoragesuccessdocument */
            if ($this->uploader->save($file, $this->replace)) {
                unset($file['error'], $file['tmp_name']);
                $info[$key] = $file;
            } else {
                $this->error = $this->uploader->getError();
            }
        }
        if (isset($finfo)) {
            finfo_close($finfo);
        }
        $info = empty($info) ? false : $info;
        
        if($info && C('IS_AWS_URL')){

        	$acl = 'public-read';
        	$root_path = dirname(dirname(dirname(dirname(__FILE__))));       	        	
        	$aws_vendor_object = new \AwsVendor\AwsVendor(C('AWS_BUCKET'),C('AWS_BASE_PATH'));
        	$tmp_aws_base_path = $aws_base_path = $aws_vendor_object->get_name('aws_base_path');
        	if(substr($aws_base_path,0,1) === '/'){
        	
        		$tmp_aws_base_path = substr($aws_base_path,1);
        	}
        	foreach($info as $key=>$value){
        		
        		$file_name = $value['savename'];
        		$prefix_path = $value['savepath'];
        		if(substr($prefix_path,0,1) == '.'){
        			
        			$prefix_path = substr($prefix_path,1);
        			$file_path = $root_path.$prefix_path.$file_name;
        			
        		}else{
        			
        			$file_path = $prefix_path.$file_name;
        		}
        		$prefix_path = str_replace($tmp_aws_base_path,'',str_replace($aws_base_path,'',$prefix_path));        		        		
        		$pathToFile = $aws_vendor_object->get_aws_file_path($prefix_path,$file_name);       		
        		$check = $aws_vendor_object->upload_process($file_path,$pathToFile,$acl);
        		
        		if(!$check){
        				
        			$error_array = $aws_vendor_object->get_error_array();
        			$this->error = isset($error_array['result']) ? $error_array['result'] : 'Sync to Bucket failed';
        			$info = false;
        			break;
        		}
        	}       	
        }        
        return $info;
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
                $fileArray = $files;
                break;
            }
        }
        return $fileArray;
    }

    /**
     * Set upload driver
     * @param string $driver Driver Name
     * @param array $config Drive configuration
     */
    private function setDriver($driver = null, $config = null)
    {
        $driver = $driver ?: ($this->driver ?: C('FILE_UPLOAD_TYPE'));
        $config = $config ?: ($this->driverConfig ?: C('UPLOAD_TYPE_CONFIG'));
        $class = strpos($driver, '\\') ? $driver : 'Think\\Upload\\Driver\\' . ucfirst(strtolower($driver));
        $this->uploader = new $class($config);
        if (!$this->uploader) {
            E("Upload driver does not exist:{$class}");
        }
    }

    /**
     * Check the uploaded files
     * @param array $file File information
     */
    private function check($file)
    {
        /* File upload failed,Capture Error Codes */
        if ($file['error']) {
            $this->error($file['error']);
            return false;
        }

        /* Invalid upload */
        if (empty($file['name'])) {
            $this->error = 'Unknown upload error!';
        }

        /* Check the legality of uploading */
        if (!is_uploaded_file($file['tmp_name'])) {
            $this->error = 'Upload illegal files!';
            return false;
        }

        /* Check the file size */
        if (!$this->checkSize($file['size'])) {
            $this->error = 'Upload file does not match the size!';
            return false;
        }

        /* Check the fileMimeTypes of */
        //TODO:FLASHUploaddocumentObtainTomimeType areapplication/octet-stream
        if (!$this->checkMime($file['type'])) {
            $this->error = 'upload filesMIMEType is not allowed!';
            return false;
        }

        /* Check the file suffix */
        if (!$this->checkExt($file['ext'])) {
            $this->error = 'Upload file extension is not allowed';
            return false;
        }

        /* By detecting */
        return true;
    }


    /**
     * Gets the error code information
     * @param string $errorNo Error number
     */
    private function error($errorNo)
    {
        switch ($errorNo) {
            case 1:
                $this->error = 'The uploaded file exceeds the php.ini in upload_max_filesize Options limit value!';
                break;
            case 2:
                $this->error = 'Upload fileSize exceeds HTML Form MAX_FILE_SIZE Value of the option specified!';
                break;
            case 3:
                $this->error = 'File was only partially uploaded!';
                break;
            case 4:
                $this->error = 'No file was uploaded!';
                break;
            case 6:
                $this->error = 'Missing a temporary folder!';
                break;
            case 7:
                $this->error = 'File write failed!';
                break;
            default:
                $this->error = 'Unknown upload error!';
        }
    }

    /**
     * Check the file sizewhetherlegitimate
     * @param integer $size data
     */
    private function checkSize($size)
    {
        $maxSizeBytes = $this->config['maxSize'];
        return ($maxSizeBytes == 0 || $size <= $maxSizeBytes);
    }
    /**
     * Check the uploaded filesMIMEType the legality
     * @param string $mime data
     */
    private function checkMime($mime)
    {
    // Convert allowed MIME types to lowercase for comparison
    $allowedMimes = array_map('strtolower', $this->config['mimes']);
    return empty($allowedMimes) ? true : in_array(strtolower($mime), $allowedMimes);
    }
 

    /**
     * Check the uploaded filessuffixwhetherlegitimate
     * @param string $ext suffix
     */
    private function checkExt($ext)
    {
        return empty($this->config['exts']) ? true : in_array(strtolower($ext), $this->exts);
    }

    /**
     * according toupload Files nameruleObtainsave documentname
     * @param string $file File information
     */
    private function getSaveName($file)
{
    $rule = $this->saveName;
    if (empty($rule)) {
        $filename = substr(pathinfo("_{$file['name']}", PATHINFO_FILENAME), 1);
        $savename = $filename;
    } else {
        // Use a secure UUID generator for unique file names
        $savename = uniqid('', true);
        if (empty($savename)) {
            $this->error = 'File naming error!';
            return false;
        }
    }

    // Use the original extension if saveExt is not specified
    $ext = empty($this->config['saveExt']) ? $file['ext'] : $this->saveExt;
    return $savename . '.' . $ext;
}

    /**
     * Get the name of a subdirectory
     * @param array $file Upload file information
     */
    private function getSubPath($filename)
    {
    $subpath = '';
    $rule = $this->subName;
    if ($this->autoSub && !empty($rule)) {
        $subpath = $this->getName($rule, $filename) . '/';
        
        // Validate path to prevent directory traversal
        $safeSavePath = realpath($this->savePath);
        if (strpos(realpath($this->savePath . $subpath), $safeSavePath) !== 0) {
            $this->error = 'Invalid subdirectory path!';
            return false;
        }

        if (!empty($subpath) && !$this->uploader->mkdir($this->savePath . $subpath)) {
            $this->error = $this->uploader->getError();
            return false;
        }
    }
    return $subpath;
    }


    /**
     * according toDesignationofruleGet FileorDirectory name
     * @param  array $rule rule
     * @param  string $filename originalfilename
     * @return string           File or directory name
     */
    private function getName($rule, $filename)
    {
        $name = '';
        if (is_array($rule)) { //An array of rules
            $func = $rule[0];
            $param = (array)$rule[1];
            foreach ($param as &$value) {
                $value = str_replace('__FILE__', $filename, $value);
            }
            $name = call_user_func_array($func, $param);
        } elseif (is_string($rule)) { //String rule
            if (function_exists($rule)) {
                $name = call_user_func($rule);
            } else {
                $name = $rule;
            }
        }
        return $name;
    }

}
