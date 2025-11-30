<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2014 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: support@codono.com
// +----------------------------------------------------------------------

/**
 * Think System library
 */

/**
 * ObtainwithSet upConfiguration parameters Support batch definitions
 * @param string|array $name Configuration Variables
 * @param mixed $value Configuration values
 * @param mixed $default Defaults
 * @return mixed
 */
function C($name = null, $value = null, $default = null)
{
    static $_config = array();
    // noparameterTimeObtainall
    if (empty($name)) {
        return $_config;
    }
    // prioritycarried outSet upObtainOr assignment
    if (is_string($name)) {
        if (!strpos($name, '.')) {
            $name = strtoupper($name);
            if (is_null($value))
                return isset($_config[$name]) ? $_config[$name] : $default;
            $_config[$name] = $value;
            return null;
        }
        // Two-dimensionalArraySet upwithObtainstand by
        $name = explode('.', $name);
        $name[0] = strtoupper($name[0]);
        if (is_null($value))
            return isset($_config[$name[0]][$name[1]]) ? $_config[$name[0]][$name[1]] : $default;
        $_config[$name[0]][$name[1]] = $value;
        return null;
    }
    // batchSet up
    if (is_array($name)) {
        $_config = array_merge($_config, array_change_key_case($name, CASE_UPPER));
        return null;
    }
    return null; // Avoid Illegalparameter
}

/**
 * Load Profile Support format conversion It supports only one configuration
 * @param string $file Configuration file name
 * @param string $parse Configuration analytical method someformatneeduserOwnResolve
 * @return array
 */
function load_config($file, $parse = CONF_PARSE)
{
    $ext = pathinfo($file, PATHINFO_EXTENSION);
    switch ($ext) {
        case 'php':
            return include $file;
        case 'ini':
            return parse_ini_file($file);
        case 'yaml':
            return yaml_parse_file($file);
        case 'xml':
            return (array)simplexml_load_file($file);
        case 'json':
            return json_decode(file_get_contents($file), true);
        default:
            if (function_exists($parse)) {
                return $parse($file);
            } else {
                E(L('_NOT_SUPPORT_') . ':' . $ext);
            }
    }
}

/**
 * ResolveyamlFile returns an array
 * @param string $file Configuration file name
 * @return array
 */
if (!function_exists('yaml_parse_file')) {
    function yaml_parse_file($file)
    {
        vendor('spyc.Spyc');
        return Spyc::YAMLLoad($file);
    }
}

/**
 * Throws Exception Handling
 * @param string $msg Exception Message
 * @param integer $code Exception code The default is0
 * @throws Think\Exception
 * @return void
 */
function E($msg, $code = 0)
{
    throw new Think\Exception($msg, $code);
}

/**
 * recordingwithstatisticstime(Microsecond)And Memoryuse happening
 * Instructions:
 * <code>
 * G('begin'); // recordingStartFlag
 * // ... Interval running code
 * G('end'); // recordingEnd tagPlace
 * echo G('begin','end',6); // statisticsIntervalruntime Accurate to one decimal place6Place
 * echo G('begin','end','m'); // statisticsIntervalRAMusehappening
 * in caseendFlagNodefinition,thenmeetingautomaticWithcurrentAs aFlag
 * among themstatisticsRAMuseneed MEMORY_LIMIT_ON Constant istrueTo be effective
 * </code>
 * @param string $start Start tag
 * @param string $end End tag
 * @param integer|string $dec Decimal places orm
 * @return mixed
 */
function G($start, $end = '', $dec = 4)
{
    static $_info = array();
    static $_mem = array();
    if (is_float($end)) { // recordingtime
        $_info[$start] = $end;
    } elseif (!empty($end)) { // statisticstimeAnd Memoryuse
        if (!isset($_info[$end])) {
            $_info[$end] = microtime(true);
        }
        if ($dec == 'm') {
            if (!isset($_mem[$end])) $_mem[$end] = memory_get_usage();
            return number_format(($_mem[$end] - $_mem[$start]) / 1024);
        } else {
            return number_format(($_info[$end] - $_info[$start]), $dec);
        }

    } else { // recordingtimeAnd Memoryuse
        $_info[$start] = microtime(true);
        $_mem[$start] = memory_get_usage();
    }
    return null;
}

/**
 * ObtainwithSet upLanguagedefinition(not case sensitive)
 * @param string|array $name Linguistic variables
 * @param mixed $value Language or variable values
 * @return mixed
 */
function L($name = null, $value = null)
{
    static $_lang = array();
    // airparameterreturnalldefinition
    if (empty($name)) {
        return $_lang;
    }
    // judgmentLanguageObtain(orSet up)
    // If thedoes not exist,Direct returnALL CAPS$name
    if (is_string($name)) {
        $name = strtoupper($name);
        if (is_null($value)) {
            return isset($_lang[$name]) ? $_lang[$name] : $name;
        } elseif (is_array($value)) {
            // Support variables
            $replace = array_keys($value);
            foreach ($replace as &$v) {
                $v = '{$' . $v . '}';
            }
            return str_replace($replace, $value, isset($_lang[$name]) ? $_lang[$name] : $name);
        }
        $_lang[$name] = $value; // Languagedefinition
        return null;
    }
    // batchdefinition
    if (is_array($name)) {
        $_lang = array_merge($_lang, array_change_key_case($name, CASE_UPPER));
    }
    return null;
}

/**
 * Adding and get the pageTracerecording
 * @param string $value variable
 * @param string $label label
 * @param string $level Log Level
 * @param boolean $record Whether logging
 * @return void|array
 */
function trace($value = '[think]', $label = '', $level = 'DEBUG', $record = false)
{
    return Think\Think::trace($value, $label, $level, $record);
}

/**
 * Compile file
 * @param string $filename filename
 * @return string
 */
function compile($filename)
{
    $content = php_strip_whitespace($filename);
    $content = trim(substr($content, 5));
    // replaceAdvanceCompileinstruction
    $content = preg_replace('/\/\/\[RUNTIME\](.*?)\/\/\[\/RUNTIME\]/s', '', $content);
    if (0 === strpos($content, 'namespace')) {
        $content = preg_replace('/namespace\s(.*?);/', 'namespace \\1{', $content, 1);
    } else {
        $content = 'namespace {' . $content;
    }
    if ('?>' == substr($content, -2)) {
        $content = substr($content, 0, -2);
    }
    return $content . '}';
}

/**
 * Get the template file format Resources://Module@theme/Controller/operating
 * @param string $template Template Resource Locator
 * @param string $layer View layer(table of Contents)name
 * @return string
 */
function T($template = '', $layer = '')
{

    // ResolveTemplate Resource Locator
    if (false === strpos($template, '://')) {
        $template = 'http://' . str_replace(':', '/', $template);
    }
    $info = parse_url($template);
    $file = $info['host'] . (isset($info['path']) ? $info['path'] : '');
    $module = isset($info['user']) ? $info['user'] . '/' : MODULE_NAME . '/';
    $extend = $info['scheme'];
    $layer = $layer ? $layer : C('DEFAULT_V_LAYER');

    // Get the currentthemeofstencilpath
    $auto = C('AUTOLOAD_NAMESPACE');
    if ($auto && isset($auto[$extend])) { // SpreadResources
        $baseUrl = $auto[$extend] . $module . $layer . '/';
    } elseif (C('VIEW_PATH')) {
        // changeModuleviewtable of Contents
        $baseUrl = C('VIEW_PATH');
    } elseif (defined('TMPL_PATH')) {
        // DesignationOverall situationviewtable of Contents
        $baseUrl = TMPL_PATH . $module;
    } else {
        $baseUrl = APP_PATH . $module . $layer . '/';
    }

    // Obtaintheme
    $theme = substr_count($file, '/') < 2 ? C('DEFAULT_THEME') : '';

    // analysisTemplate File Rules
    $depr = C('TMPL_FILE_DEPR');
    if ('' == $file) {
        // in caseTemplate filesnameforair according todefaultruleLocate
        $file = CONTROLLER_NAME . $depr . ACTION_NAME;
    } elseif (false === strpos($file, '/')) {
        $file = CONTROLLER_NAME . $depr . $file;
    } elseif ('/' != $depr) {
        $file = substr_count($file, '/') > 1 ? substr_replace($file, $depr, strrpos($file, '/'), 1) : str_replace('/', $depr, $file);
    }
    return $baseUrl . ($theme ? $theme . '/' : '') . $file . C('TMPL_TEMPLATE_SUFFIX');
}

/**
 * Obtain input parameters Supports filtering and default values
 * Instructions:
 * <code>
 * I('id',0); Obtainidparameter Automatically determine thegetorpost
 * I('post.name','','htmlspecialchars'); Obtain$_POST['name']
 * I('get.'); Obtain$_GET
 * </code>
 * @param string $name The name of the variable Specify the type of support
 * @param mixed $default does not existoftimeDefaults
 * @param mixed $filter Parameter filtering method
 * @param mixed $datas wantObtainofadditionaldatasource
 * @return mixed
 */
function I($name, $default = '', $filter = null, $datas = null)
{
    if (strpos($name, '/')) { // DesignationModifiers
        list($name, $type) = explode('/', $name, 2);
    } elseif (C('VAR_AUTO_STRING')) { // defaultCastforString
        $type = 's';
    }
    if (strpos($name, '.')) { // Designationparametersource
        list($method, $name) = explode('.', $name, 2);
    } else { // The default isAutomatically determine the
        $method = 'param';
    }
    switch (strtolower($method)) {
        case 'get'     :
            $input =& $_GET;
            break;
        case 'post'    :
            $input =& $_POST;
            break;
        case 'put'     :
            parse_str(file_get_contents('php://input'), $input);
            break;
        case 'param'   :
            switch ($_SERVER['REQUEST_METHOD']) {
                case 'POST':
                    $input = $_POST;
                    break;
                case 'PUT':
                    parse_str(file_get_contents('php://input'), $input);
                    break;
                default:
                    $input = $_GET;
            }
            break;
        case 'path'    :
            $input = array();
            if (!empty($_SERVER['PATH_INFO'])) {
                $depr = C('URL_PATHINFO_DEPR');
                $input = explode($depr, trim($_SERVER['PATH_INFO'], $depr));
            }
            break;
        case 'request' :
            $input =& $_REQUEST;
            break;
        case 'session' :
            $input =& $_SESSION;
            break;
        case 'cookie'  :
            $input =& $_COOKIE;
            break;
        case 'server'  :
            $input =& $_SERVER;
            break;
        case 'globals' :
            $input =& $GLOBALS;
            break;
        case 'data'    :
            $input =& $datas;
            break;
        default:
            return NULL;
    }
    if ('' == $name) { // ObtainCompletevariable
        $data = $input;
        $filters = isset($filter) ? $filter : C('DEFAULT_FILTER');
        if ($filters) {
            if (is_string($filters)) {
                $filters = explode(',', $filters);
            }
            foreach ($filters as $filter) {
                $data = array_map_recursive($filter, $data); // parameterfilter
            }
        }
    } elseif (isset($input[$name])) { // The valueoperating
        $data = $input[$name];
        $filters = isset($filter) ? $filter : C('DEFAULT_FILTER');
        if ($filters) {
            if (is_string($filters)) {
                $filters = explode(',', $filters);
            } elseif (is_int($filters)) {
                $filters = array($filters);
            }

            foreach ($filters as $filter) {
                if (function_exists($filter)) {
                    $data = is_array($data) ? array_map_recursive($filter, $data) : $filter($data); // parameterfilter
                } elseif (0 === strpos($filter, '/')) {
                    // stand byRegularverification
                    if (1 !== preg_match($filter, (string)$data)) {
                        return isset($default) ? $default : NULL;
                    }
                } else {
                    $data = filter_var($data, is_int($filter) ? $filter : filter_id($filter));
                    if (false === $data) {
                        return isset($default) ? $default : NULL;
                    }
                }
            }
        }
        if (!empty($type)) {
            switch (strtolower($type)) {
                case 'a':    // Array
                    $data = (array)$data;
                    break;
                case 'd':    // digital
                    $data = (int)$data;
                    break;
                case 'f':    // floating point
                    $data = (float)$data;
                    break;
                case 'b':    // Boolean
                    $data = (boolean)$data;
                    break;
                case 's':   // String
                default:
                    $data = (string)$data;
            }
        }
    } else { // variableDefaults
        $data = isset($default) ? $default : NULL;
    }
    is_array($data) && array_walk_recursive($data, 'think_filter');
    return $data;
}

function array_map_recursive($filter, $data)
{
    $result = array();
    foreach ($data as $key => $val) {
        $result[$key] = is_array($val)
            ? array_map_recursive($filter, $val)
            : call_user_func($filter, $val);
    }
    return $result;
}

/**
 * Set upwithObtainstatisticsdata
 * Instructions:
 * <code>
 * N('db',1); // recordingdatabaseoperatingfrequency
 * N('read',1); // recordingReadfrequency
 * echo N('db'); // Get the currentpageDatabasealloperatingfrequency
 * echo N('read'); // Get the currentpageReadfrequency
 * </code>
 * @param string $key Identify the location
 * @param integer $step Stepping value
 * @param boolean $save Whether to save results
 * @return mixed
 */
function N($key, $step = 0, $save = false)
{
    static $_num = array();
    if (!isset($_num[$key])) {
        $_num[$key] = (false !== $save) ? S('N_' . $key) : 0;
    }
    if (empty($step)) {
        return $_num[$key];
    } else {
        $_num[$key] = $_num[$key] + (int)$step;
    }
    if (false !== $save) { // Storageresult
        S('N_' . $key, $_num[$key], $save);
    }
    return null;
}

/**
 * StringNaming StyleChange
 * type 0 willJavaStyle convertedCstyle of 1 willCStyle convertedJavastyle of
 * @param string $name String
 * @param integer $type Conversion Type
 * @return string
 */
function parse_name($name, $type = 0)
{
    if ($type) {
        return ucfirst(preg_replace_callback('/_([a-zA-Z])/', function ($match) {
            return strtoupper($match[1]);
        }, $name));
    } else {
        return strtolower(trim(preg_replace("/[A-Z]/", "_\\0", $name), "_"));
    }
}

/**
 * optimizedrequire_once
 * @param string $filename Address file
 * @return boolean
 */
function require_cache($filename)
{
    static $_importFiles = array();
    if (!isset($_importFiles[$filename])) {
        if (file_exists_case($filename)) {
            require $filename;
            $_importFiles[$filename] = true;
        } else {
            $_importFiles[$filename] = false;
        }
    }
    return $_importFiles[$filename];
}

/**
 * Case sensitivedocumentexistjudgment
 * @param string $filename Address file
 * @return boolean
 */
function file_exists_case($filename)
{
    if (is_file($filename)) {
        if (IS_WIN && APP_DEBUG) {
            if (basename(realpath($filename)) != basename($filename))
                return false;
        }
        return true;
    }
    return false;
}

/**
 * Import the required class libraries 同javaofImport This function has a cache function
 * @param string $class Class LibraryNamespacesString
 * @param string $baseUrl Starting path
 * @param string $ext ImportingdocumentExtension
 * @return boolean
 */
function import($class, $baseUrl = '', $ext = EXT)
{
    static $_file = array();
    $class = str_replace(array('.', '#'), array('/', '.'), $class);
    if (isset($_file[$class . $baseUrl]))
        return true;
    else
        $_file[$class . $baseUrl] = true;
    $class_strut = explode('/', $class);
    if (empty($baseUrl)) {
        if ('@' == $class_strut[0] || MODULE_NAME == $class_strut[0]) {
            //loadcurrentModuleofClass Library
            $baseUrl = MODULE_PATH;
            $class = substr_replace($class, '', 0, strlen($class_strut[0]) + 1);
        } elseif ('Common' == $class_strut[0]) {
            //loadpublicModuleofClass Library
            $baseUrl = COMMON_PATH;
            $class = substr($class, 7);
        } elseif (in_array($class_strut[0], array('Think', 'Org', 'Behavior', 'Com', 'Vendor')) || is_dir(LIB_PATH . $class_strut[0])) {
            // systemClass LibraryAnd third-party packagesClass Librarypackage
            $baseUrl = LIB_PATH;
        } else { // loadotherModuleofClass Library
            $baseUrl = APP_PATH;
        }
    }
    if (substr($baseUrl, -1) != '/')
        $baseUrl .= '/';
    $classfile = $baseUrl . $class . $ext;
    if (!class_exists(basename($class), false)) {
        // in caseclassdoes not exist thenImportingClass Libraryfile
        return require_cache($classfile);
    }
    return null;
}

/**
 * based onNamespacesthe wayImportingfunctionStorehouse
 * load('@.Util.Array')
 * @param string $name functionStorehouseNamespacesString
 * @param string $baseUrl Starting path
 * @param string $ext ImportingdocumentExtension
 * @return void
 */
function load($name, $baseUrl = '', $ext = '.php')
{
    $name = str_replace(array('.', '#'), array('/', '.'), $name);
    if (empty($baseUrl)) {
        if (0 === strpos($name, '@/')) {//loadcurrentModulefunctionStorehouse
            $baseUrl = MODULE_PATH . 'Common/';
            $name = substr($name, 2);
        } else { //loadotherModulefunctionStorehouse
            $array = explode('/', $name);
            $baseUrl = APP_PATH . array_shift($array) . '/Common/';
            $name = implode('/', $array);
        }
    }
    if (substr($baseUrl, -1) != '/') {
        $baseUrl .= '/';
    }
    require_cache($baseUrl . $name . $ext);
}

/**
 * fastImportingThird partyframeClass Library allThird partyframeofClass LibraryfileUnitePut systematicVendorDirectory
 * @param string $class Class Library
 * @param string $baseUrl Base directory
 * @param string $ext Library suffix
 * @return boolean
 */
function vendor($class, $baseUrl = '', $ext = '.php')
{
    if (empty($baseUrl)) {
        $baseUrl = VENDOR_PATH;
    }
    return import($class, $baseUrl, $ext);
}

/**
 * Class instantiation model format [Resources://][Module/]model
 * @param string $name Resource Locator
 * @param string $layer Model layer name
 * @return Think\Model
 */
function D($name = '', $layer = '')
{
    if (empty($name)) return new Think\Model;
    static $_model = array();
    $layer = $layer ?: 'Model';
    if (isset($_model[$name . $layer])) {
        return $_model[$name . $layer];
    }
    $class = parse_res_name($name, $layer);
    if (class_exists($class)) {
        $model = new $class(basename($name));
    } elseif (false === strpos($name, '/')) {
        // Automatically loadpublicModulethe followingofmodel
        $class = '\\Common\\' . $layer . '\\' . $name . $layer;
        $model = class_exists($class) ? new $class($name) : new Think\Model($name);
    } else {
        $model = new Think\Model(basename($name));
    }
    $_model[$name . $layer] = $model;
    return $model;
}

/**
 * InstantiationOneNomodelDocumentsModel
 * @param string $name Modelname Supports the specified base model E.g MongoModel:User
 * @param string $tablePrefix Table Prefix
 * @param mixed $connection Database connection information
 * @return Think\Model
 */
function M($name = '', $tablePrefix = '', $connection = '')
{
    static $_model = array();
    if (strpos($name, ':')) {
        list($class, $name) = explode(':', $name);
    } else {
        $class = 'Think\\Model';
    }
    $guid = (is_array($connection) ? implode('', $connection) : $connection) . $tablePrefix . $name . '_' . $class;
    if (!isset($_model[$guid])) {
        $_model[$guid] = new $class($name, $tablePrefix, $connection);
    }
    return $_model[$guid];
}

/**
 * ResolveResource LocatorandImportingClass Libraryfile
 * E.g module/controller addon://module/behavior
 * @param string $name Resource Locator format:[Spread://][Module/]Resources Name
 * @param string $layer Hierarchical name
 * @return string
 */
function parse_res_name($name, $layer)
{
    if (strpos($name, '://')) {// DesignationSpreadResources
        list($extend, $name) = explode('://', $name);
    } else {
        $extend = '';
    }
    if (strpos($name, '/')) { // DesignationModule
        list($module, $name) = explode('/', $name, 2);
    } else {
        $module = defined('MODULE_NAME') ? MODULE_NAME : '';
    }
    $array = explode('/', $name);
    $class = $module . '\\' . $layer;
    foreach ($array as $name) {
        $class .= '\\' . parse_name($name, 1);
    }
    // ImportingResourcesClass Library
    if ($extend) { // SpreadResources
        $class = $extend . '\\' . $class;
    }

    return $class . $layer;
}

/**
 * ForInstantiationaccessController
 * @param string $name ControllerName
 * @return Think\Controller|false
 */
function controller($name)
{
    $layer  =   C('DEFAULT_C_LAYER');
    $class = MODULE_NAME . '\\Controller';
    $array = explode('/', $name);
    foreach ($array as $name) {
        $class .= '\\' . parse_name($name, 1);
    }
    $class .= $layer;

    if (class_exists($class)) {
        return new $class();
    } else {
        return false;
    }
}

/**
 * Examples of multilayer controller format:[Resources://][Module/]Controller
 * @param string $name Resource Locator
 * @param string $layer Control layer name
 * @return Think\Controller|false
 */
function A($name, $layer = '')
{
    static $_action = array();
    $layer = $layer ?: 'Controller';
    if (isset($_action[$name . $layer])) {
        return $_action[$name . $layer];
    }

    $class = parse_res_name($name, $layer);
    if (class_exists($class)) {
        $action = new $class();
        $_action[$name . $layer] = $action;
        return $action;
    } else {
        return false;
    }
}


/**
 * 远程transferControllerofoperatingmethod URL Parameter format [Resources://][Module/]Controller/operating
 * @param string $url Call address
 * @param string|array $vars Call parameters Support for strings and arrays
 * @param string $layer wanttransferofControl layer name
 * @return mixed
 */
function R($url, $vars = array(), $layer = '')
{
    $info = pathinfo($url);
    $action = $info['basename'];
    $module = $info['dirname'];
    $class = A($module, $layer);
    if ($class) {
        if (is_string($vars)) {
            parse_str($vars, $vars);
        }
        return call_user_func_array(array(&$class, $action . C('ACTION_SUFFIX')), $vars);
    } else {
        return false;
    }
}

/**
 * Label treatment extension
 * @param string $tag Label name
 * @param mixed $params Incoming parameters
 * @return void
 */
function tag($tag, &$params = NULL)
{
    \Think\Hook::listen($tag, $params);
}

/**
 * The implementation of an action
 * @param string $name Behavior name
 * @param string $tag Label name(behaviorNo need to pass class)
 * @param Mixed $params Incoming parameters
 * @return void
 */
function B($name, $tag = '', &$params = NULL)
{
    if ('' == $tag) {
        $name .= 'Behavior';
    }
    return \Think\Hook::exec($name, $tag, $params);
}

/**
 * RemovalCodemiddleBlank andNote
 * @param string $content Code Contents
 * @return string
 */
function strip_whitespace($content)
{
    $stripStr = '';
    //analysisphpSource
    $tokens = token_get_all($content);
    $last_space = false;
    for ($i = 0, $j = count($tokens); $i < $j; $i++) {
        if (is_string($tokens[$i])) {
            $last_space = false;
            $stripStr .= $tokens[$i];
        } else {
            switch ($tokens[$i][0]) {
                //Various filtersPHPNote
                case T_COMMENT:
                case T_DOC_COMMENT:
                    break;
                //Filtering space
                case T_WHITESPACE:
                    if (!$last_space) {
                        $stripStr .= ' ';
                        $last_space = true;
                    }
                    break;
                case T_START_HEREDOC:
                    $stripStr .= "<<<THINK\n";
                    break;
                case T_END_HEREDOC:
                    $stripStr .= "THINK;\n";
                    for ($k = $i + 1; $k < $j; $k++) {
                        if (is_string($tokens[$k]) && $tokens[$k] == ';') {
                            $i = $k;
                            break;
                        } else if ($tokens[$k][0] == T_CLOSE_TAG) {
                            break;
                        }
                    }
                    break;
                default:
                    $last_space = false;
                    $stripStr .= $tokens[$i][1];
            }
        }
    }
    return $stripStr;
}

/**
 * BrowseFRIENDLYofvariableExport
 * @param mixed $var variable
 * @param boolean $echo Whether output The default isTrue Iffalse Output string is returned
 * @param string $label label The default is empty
 * @param boolean $strict Whether rigorous The default istrue
 * @return void|string
 */
function dump($var, $echo = true, $label = null, $strict = true)
{
    $label = ($label === null) ? '' : rtrim($label) . ' ';
    if (!$strict) {
        if (ini_get('html_errors')) {
            $output = print_r($var, true);
            $output = '<pre>' . $label . htmlspecialchars($output, ENT_QUOTES) . '</pre>';
        } else {
            $output = $label . print_r($var, true);
        }
    } else {
        ob_start();
        var_dump($var);
        $output = ob_get_clean();
        if (!extension_loaded('xdebug')) {
            $output = preg_replace('/\]\=\>\n(\s+)/m', '] => ', $output);
            $output = '<pre>' . $label . htmlspecialchars($output, ENT_QUOTES) . '</pre>';
        }
    }
    if ($echo) {
        echo($output);
        return null;
    } else
        return $output;
}

/**
 * Set upcurrentpageoflayout
 * @param string|false $layout Layout name forfalseoftimeShowshut downlayout
 * @return void
 */
function layout($layout)
{
    if (false !== $layout) {
        // Openlayout
        C('LAYOUT_ON', true);
        if (is_string($layout)) { // Set upnewoflayouttemplate
            C('LAYOUT_NAME', $layout);
        }
    } else {// temporaryshut downlayout
        C('LAYOUT_ON', false);
    }
}

/**
 * URLAssembly stand by Diferent URLmode
 * @param string $url URLExpression, format:'[Module/Controller/operating#Anchor@areaname]?parameter1=value1&parameter2=value2...'
 * @param string|array $vars Incoming parameters,Support arrays and strings
 * @param string|boolean $suffix Pseudo-staticsuffix,The default istrueRetrieves configuration values
 * @param boolean $domain whetherdisplayareaname
 * @return string
 */
function U($url = '', $vars = '', $suffix = true, $domain = false)
{
    // ResolveURL
    $info = parse_url($url);
    $url = !empty($info['path']) ? $info['path'] : ACTION_NAME;
    if (isset($info['fragment'])) { // ResolveAnchor
        $anchor = $info['fragment'];
        if (false !== strpos($anchor, '?')) { // Analytical parameters
            list($anchor, $info['query']) = explode('?', $anchor, 2);
        }
        if (false !== strpos($anchor, '@')) { // Resolveareaname
            list($anchor, $host) = explode('@', $anchor, 2);
        }
    } elseif (false !== strpos($url, '@')) { // Resolveareaname
        list($url, $host) = explode('@', $info['path'], 2);
    }
    // Resolvechildareaname
    if (isset($host)) {
        $domain = $host . (strpos($host, '.') ? '' : strstr($_SERVER['HTTP_HOST'], '.'));
    } elseif ($domain === true) {
        $domain = $_SERVER['HTTP_HOST'];
        if (C('APP_SUB_DOMAIN_DEPLOY')) { // Openchildareanamedeploy
            $domain = $domain == 'localhost' ? 'localhost' : 'www' . strstr($_SERVER['HTTP_HOST'], '.');
            // 'Subdomain'=>array('Module[/Controller]');
            foreach (C('APP_SUB_DOMAIN_RULES') as $key => $rule) {
                $rule = is_array($rule) ? $rule[0] : $rule;
                if (false === strpos($key, '*') && 0 === strpos($url, $rule)) {
                    $domain = $key . strstr($domain, '.'); // Formcorrespondchildareaname
                    $url = substr_replace($url, '', 0, strlen($rule));
                    break;
                }
            }
        }
    }

    // Analytical parameters
    if (is_string($vars)) { // aaa=1&bbb=2 Into an array
        parse_str($vars, $vars);
    } elseif (!is_array($vars)) {
        $vars = array();
    }
    if (isset($info['query'])) { // Resolveaddressinsideparameter Merge tovars
        parse_str($info['query'], $params);
        $vars = array_merge($params, $vars);
    }

    // URLAssembly
    $depr = C('URL_PATHINFO_DEPR');
    $urlCase = C('URL_CASE_INSENSITIVE');
    if ($url) {
        if (0 === strpos($url, '/')) {// Define the route
            $route = true;
            $url = substr($url, 1);
            if ('/' != $depr) {
                $url = str_replace('/', $depr, $url);
            }
        } else {
            if ('/' != $depr) { // Safetyreplace
                $url = str_replace('/', $depr, $url);
            }
            // ResolveModule,Controllerwithoperating
            $url = trim($url, $depr);
            $path = explode($depr, $url);
            $var = array();
            $varModule = C('VAR_MODULE');
            $varController = C('VAR_CONTROLLER');
            $varAction = C('VAR_ACTION');
            $var[$varAction] = !empty($path) ? array_pop($path) : ACTION_NAME;
            $var[$varController] = !empty($path) ? array_pop($path) : CONTROLLER_NAME;
            if ($urlCase) {
                $var[$varController] = parse_name($var[$varController]);
            }
            $module = '';

            if (!empty($path)) {
                $var[$varModule] = implode($depr, $path);
            } else {
                if (C('MULTI_MODULE')) {
                    if (MODULE_NAME != C('DEFAULT_MODULE') || !C('MODULE_ALLOW_LIST')) {
                        $var[$varModule] = MODULE_NAME;
                    }
                }
            }
            if ($maps = C('URL_MODULE_MAP')) {
                if ($_module = array_search(strtolower($var[$varModule]), $maps)) {
                    $var[$varModule] = $_module;
                }
            }
            if (isset($var[$varModule])) {
                $module = $var[$varModule];
                unset($var[$varModule]);
            }

        }
    }

    if (C('URL_MODEL') == 0) { // Normal modeURLChange
        $url = __APP__ . '?' . C('VAR_MODULE') . "={$module}&" . http_build_query(array_reverse($var));
        if ($urlCase) {
            $url = strtolower($url);
        }
        if (!empty($vars)) {
            $vars = http_build_query($vars);
            $url .= '&' . $vars;
        }
    } else { // PATHINFOMode or compatibleURLmode
        if (isset($route)) {
            $url = __APP__ . '/' . rtrim($url, $depr);
        } else {
            $module = (defined('BIND_MODULE') && BIND_MODULE == $module) ? '' : $module;
            $url = __APP__ . '/' . ($module ? $module . MODULE_PATHINFO_DEPR : '') . implode($depr, array_reverse($var));
        }
        if ($urlCase) {
            $url = strtolower($url);
        }
        if (!empty($vars)) { // Add toparameter
            foreach ($vars as $var => $val) {
                if ('' !== trim($val)) $url .= $depr . $var . $depr . urlencode($val);
            }
        }
        if ($suffix) {
            $suffix = $suffix === true ? C('URL_HTML_SUFFIX') : $suffix;
            if ($pos = strpos($suffix, '|')) {
                $suffix = substr($suffix, 0, $pos);
            }
            if ($suffix && '/' != substr($url, -1)) {
                $url .= '.' . ltrim($suffix, '.');
            }
        }
    }
    if (isset($anchor)) {
        $url .= '#' . $anchor;
    }
    if ($domain) {
        $url = (is_ssl() ? 'https://' : 'http://') . $domain . $url;
    }
    return $url;
}

/**
 * Render OutputWidget
 * @param string $name Widgetname
 * @param array $data Incoming parameters
 * @return void
 */
function W($name, $data = array())
{
    return R($name, $data, 'Widget');
}

/**
 * Determine whetherSSLprotocol
 * @return boolean
 */
function is_ssl()
{
    if (isset($_SERVER['HTTPS']) && ('1' == $_SERVER['HTTPS'] || 'on' == strtolower($_SERVER['HTTPS']))) {
        return true;
    } elseif (isset($_SERVER['SERVER_PORT']) && ('443' == $_SERVER['SERVER_PORT'])) {
        return true;
    }
    return false;
}

/**
 * URLRedirect
 * @param string $url RedirectedURLaddress
 * @param integer $time RedirectedWait待time(second)
 * @param string $msg RedirectbeforeofTips
 * @return void
 */
function redirect($url, $time = 0, $msg = '')
{
    //Multi-lineURLAddress Support
    $url = str_replace(array("\n", "\r"), '', $url);
    if (empty($msg))
        $msg = "System will{$time}After the second jump to automatically{$url}!";
    if (!headers_sent()) {
        // redirect
        if (0 === $time) {
            header('Location: ' . $url);
        } else {
            header("refresh:{$time};url={$url}");
            echo($msg);
        }
        exit();
    } else {
        $str = "<meta http-equiv='Refresh' content='{$time};URL={$url}'>";
        if ($time != 0)
            $str .= $msg;
        exit($str);
    }
}

/**
 * Cache Management
 * @param mixed $name Cache name, IfArrayShowEnterRowCacheSet up
 * @param mixed $value Cache value
 * @param mixed $options Cache parameters
 * @return mixed
 */
function S($name, $value = '', $options = null)
{
	
    static $cache = '';
    if (is_array($options) && empty($cache)) {
        // CacheoperatingofSimultaneouslyinitialization
        $type = isset($options['type']) ? $options['type'] : '';
        $cache = Think\Cache::getInstance($type, $options);
    } elseif (is_array($name)) { // Cacheinitialization
        $type = isset($name['type']) ? $name['type'] : '';
        $cache = Think\Cache::getInstance($type, $name);
        return $cache;
    } elseif (empty($cache)) { // automaticinitialization
        $cache = Think\Cache::getInstance();
    }
    if ('' === $value) { // ObtainCache
        return $cache->get($name);
    } elseif (is_null($value)) { // Delete Cache
        return $cache->rm($name);
    } else { // Cachedata
        if (is_array($options)) {
            $expire = isset($options['expire']) ? $options['expire'] : NULL;
        } else {
            $expire = is_numeric($options) ? $options : NULL;
        }
        return $cache->set($name, $value, $expire);
    }
}

/**
 * fastfiledataReadwithStorage For simple data types Strings, arrays
 * @param string $name Cache name
 * @param mixed $value Cache value
 * @param string $path Cache path
 * @return mixed
 */
function F($name, $value = '', $path = DATA_PATH)
{
    static $_cache = array();
    $filename = $path . $name . '.php';
    if ('' !== $value) {
        if (is_null($value)) {
            // Delete Cache
            if (false !== strpos($name, '*')) {
                return false; // TODO 
            } else {
                unset($_cache[$name]);
                return Think\Storage::unlink($filename, 'F');
            }
        } else {
            Think\Storage::put($filename, serialize($value), 'F');
            // Cachedata
            $_cache[$name] = $value;
            return null;
        }
    }
    // ObtainCachedata
    if (isset($_cache[$name]))
        return $_cache[$name];
    if (Think\Storage::has($filename, 'F')) {
        $value = unserialize(Think\Storage::read($filename, 'F'));
        $_cache[$name] = $value;
    } else {
        $value = false;
    }
    return $value;
}

/**
 * according toPHPAll kindsTypes ofvariableFormonlyMarknumber
 * @param mixed $mix variable
 * @return string
 */
function to_guid_string($mix)
{
    if (is_object($mix)) {
        return spl_object_hash($mix);
    } elseif (is_resource($mix)) {
        $mix = get_resource_type($mix) . strval($mix);
    } else {
        $mix = serialize($mix);
    }
    return md5($mix);
}

/**
 * sessionManagement Functions
 * @param string|array $name sessionname IfArrayIt meansEnterRowsessionSet up
 * @param mixed $value sessionvalue
 * @return mixed
 */
function session($name = '', $value = '')
{
    $prefix = C('SESSION_PREFIX');
    if (is_array($name)) { // Session initialization insession_start Before calling
        if (isset($name['prefix'])) C('SESSION_PREFIX', $name['prefix']);
        if (C('VAR_SESSION_ID') && isset($_REQUEST[C('VAR_SESSION_ID')])) {
            session_id($_REQUEST[C('VAR_SESSION_ID')]);
        } elseif (isset($name['id'])) {
            session_id($name['id']);
        }
        if ('common' != APP_MODE) { // othermodeMay not support
            ini_set('session.auto_start', 0);
        }
        if (isset($name['name'])) session_name($name['name']);
        if (isset($name['path'])) session_save_path($name['path']);
        if (isset($name['domain'])) ini_set('session.cookie_domain', $name['domain']);
        if (isset($name['expire'])) ini_set('session.gc_maxlifetime', $name['expire']);
        if (isset($name['use_trans_sid'])) ini_set('session.use_trans_sid', $name['use_trans_sid'] ? 1 : 0);
        if (isset($name['use_cookies'])) ini_set('session.use_cookies', $name['use_cookies'] ? 1 : 0);
        if (isset($name['cache_limiter'])) session_cache_limiter($name['cache_limiter']);
        if (isset($name['cache_expire'])) session_cache_expire($name['cache_expire']);
        if (isset($name['type'])) C('SESSION_TYPE', $name['type']);
        if (C('SESSION_TYPE')) { // Readsessiondrive
            $type = C('SESSION_TYPE');
            $class = strpos($type, '\\') ? $type : 'Think\\Session\\Driver\\' . ucwords(strtolower($type));
            $hander = new $class();
            session_set_save_handler(
                array(&$hander, "open"),
                array(&$hander, "close"),
                array(&$hander, "read"),
                array(&$hander, "write"),
                array(&$hander, "destroy"),
                array(&$hander, "gc"));
        }
        // start upsession
        if (C('SESSION_AUTO_START')) session_start();
    } elseif ('' === $value) {
        if ('' === $name) {
            // ObtainCompleteofsession
            return $prefix ? $_SESSION[$prefix] : $_SESSION;
        } elseif (0 === strpos($name, '[')) { // session operating
            if ('[pause]' == $name) { // time outsession
                session_write_close();
            } elseif ('[start]' == $name) { // start upsession
                session_start();
            } elseif ('[destroy]' == $name) { // destroysession
                $_SESSION = array();
                session_unset();
                session_destroy();
            } elseif ('[regenerate]' == $name) { // AgainFormid
                session_regenerate_id();
            }
        } elseif (0 === strpos($name, '?')) { // an examinationsession
            $name = substr($name, 1);
            if (strpos($name, '.')) { // stand byArray
                list($name1, $name2) = explode('.', $name);
                return $prefix ? isset($_SESSION[$prefix][$name1][$name2]) : isset($_SESSION[$name1][$name2]);
            } else {
                return $prefix ? isset($_SESSION[$prefix][$name]) : isset($_SESSION[$name]);
            }
        } elseif (is_null($name)) { // Clearsession
            if ($prefix) {
                unset($_SESSION[$prefix]);
            } else {
                $_SESSION = array();
            }
        } elseif ($prefix) { // Obtainsession
            if (strpos($name, '.')) {
                list($name1, $name2) = explode('.', $name);
                return isset($_SESSION[$prefix][$name1][$name2]) ? $_SESSION[$prefix][$name1][$name2] : null;
            } else {
                return isset($_SESSION[$prefix][$name]) ? $_SESSION[$prefix][$name] : null;
            }
        } else {
            if (strpos($name, '.')) {
                list($name1, $name2) = explode('.', $name);
                return isset($_SESSION[$name1][$name2]) ? $_SESSION[$name1][$name2] : null;
            } else {
                return isset($_SESSION[$name]) ? $_SESSION[$name] : null;
            }
        }
    } elseif (is_null($value)) { // deletesession
        if (strpos($name, '.')) {
            list($name1, $name2) = explode('.', $name);
            if ($prefix) {
                unset($_SESSION[$prefix][$name1][$name2]);
            } else {
                unset($_SESSION[$name1][$name2]);
            }
        } else {
            if ($prefix) {
                unset($_SESSION[$prefix][$name]);
            } else {
                unset($_SESSION[$name]);
            }
        }
    } else { // Set upsession
        if (strpos($name, '.')) {
            list($name1, $name2) = explode('.', $name);
            if ($prefix) {
                $_SESSION[$prefix][$name1][$name2] = $value;
            } else {
                $_SESSION[$name1][$name2] = $value;
            }
        } else {
            if ($prefix) {
                $_SESSION[$prefix][$name] = $value;
            } else {
                $_SESSION[$name] = $value;
            }
        }
    }
    return null;
}

/**
 * Cookie Setting, get, delete
 * @param string $name cookiename
 * @param mixed $value cookievalue
 * @param mixed $option cookieparameter
 * @return mixed
 */
function cookie($name = '', $value = '', $option = null)
{
    // defaultSet up
    $config = array(
        'prefix' => C('COOKIE_PREFIX'), // cookie Name Prefix
        'expire' => C('COOKIE_EXPIRE'), // cookie Storagetime
        'path' => C('COOKIE_PATH'), // cookie save route
        'domain' => C('COOKIE_DOMAIN'), // cookie effectiveareaname
        'secure' => C('COOKIE_SECURE'), //  cookie Enable secure transmission
        'httponly' => C('COOKIE_HTTPONLY'), // httponlySet up
    );
    // parameterSet up(meetingcoverSummerside recognitionSet up)
    if (!is_null($option)) {
        if (is_numeric($option))
            $option = array('expire' => $option);
        elseif (is_string($option))
            parse_str($option, $option);
        $config = array_merge($config, array_change_key_case($option));
    }
    if (!empty($config['httponly'])) {
        ini_set("session.cookie_httponly", 1);
    }
    // RemoveDesignationPrefixofallcookie
    if (is_null($name)) {
        if (empty($_COOKIE))
            return null;
        // wantdeleteofcookiePrefix,不DesignationthendeleteconfigSet upofDesignationPrefix
        $prefix = empty($value) ? $config['prefix'] : $value;
        if (!empty($prefix)) {// in casePrefixforairStringWill not bedeal withDirect return
            foreach ($_COOKIE as $key => $val) {
                if (0 === stripos($key, $prefix)) {
                    setcookie($key, '', time() - 3600, $config['path'], $config['domain'], $config['secure'], $config['httponly']);
                    unset($_COOKIE[$key]);
                }
            }
        }
        return null;
    } elseif ('' === $name) {
        // ObtainCompleteofcookie
        return $_COOKIE;
    }
    $name = $config['prefix'] . str_replace('.', '_', $name);
    if ('' === $value) {
        if (isset($_COOKIE[$name])) {
            $value = $_COOKIE[$name];
            if (0 === strpos($value, 'think:')) {
                $value = substr($value, 6);
                return array_map('urldecode', json_decode(MAGIC_QUOTES_GPC ? stripslashes($value) : $value, true));
            } else {
                return $value;
            }
        } else {
            return null;
        }
    } else {
        if (is_null($value)) {
            setcookie($name, '', time() - 3600, $config['path'], $config['domain'], $config['secure'], $config['httponly']);
            unset($_COOKIE[$name]); // deleteDesignationcookie
        } else {
            // Set upcookie
            if (is_array($value)) {
                $value = 'think:' . json_encode(array_map('urlencode', $value));
            }
            $expire = !empty($config['expire']) ? time() + intval($config['expire']) : 0;
            setcookie($name, $value, $expire, $config['path'], $config['domain'], $config['secure'], $config['httponly']);
            $_COOKIE[$name] = $value;
        }
    }
    return null;
}

/**
 * sendHTTPstatus
 * @param integer $code status code
 * @return void
 */
function send_http_status($code)
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
        302 => 'Moved Temporarily ',  // 1.1
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
        // make sureFastCGI mode normal
        header('Status:' . $code . ' ' . $_status[$code]);
    }
}

function think_filter(&$value)
{
    // TODO Other security filtering

    // filterInquirespecialcharacter
    if (preg_match('/^(EXP|NEQ|GT|EGT|LT|ELT|OR|XOR|LIKE|NOTLIKE|NOT BETWEEN|NOTBETWEEN|BETWEEN|NOTIN|NOT IN|IN|BIND)$/i', $value)) {
        $value .= ' ';
    }
}

// not case sensitiveofin_arrayachieve
function in_array_case($value, $array)
{
    return in_array(strtolower($value), array_map('strtolower', $array));
}
