<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2012 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: support@codono.com
// +----------------------------------------------------------------------

/**
 * Think Base library
 * @category   Think
 * @package  Common
 * @author   liu21st <liu21st@gmail.com>
 */

/**
 * Get the template file format project://Packet@theme/Module/operating
 * @param string $name Template Resource Locator
 * @param string $layer View layer(table of Contents)name
 * @return string
 */
function T($template = '', $layer = '')
{
    if (is_file($template)) {
        return $template;
    }
    // ResolveTemplate Resource Locator
    if (false === strpos($template, '://')) {
        $template = APP_NAME . '://' . str_replace(':', '/', $template);
    }
    $info = parse_url($template);
    $file = $info['host'] . ($info['path'] ?? '');
    $group = isset($info['user']) ? $info['user'] . '/' : (defined('GROUP_NAME') ? GROUP_NAME . '/' : '');
    $app = $info['scheme'];
    $layer = $layer ?: C('DEFAULT_V_LAYER');

    // Get the currentthemeofstencilpath
    if (($list = C('EXTEND_GROUP_LIST')) && isset($list[$app])) { // SpreadPacket
        $baseUrl = $list[$app] . '/' . $group . $layer . '/';
    } elseif (1 == C('APP_GROUP_MODE')) { // independentPacketmode
        $baseUrl = dirname(BASE_LIB_PATH) . '/' . $group . $layer . '/';
    } else {
        $baseUrl = TMPL_PATH . $group;
    }

    // analysisTemplate File Rules
    if ('' == $file) {
        // in caseTemplate filesnameforair according todefaultruleLocate
        $file = MODULE_NAME . C('TMPL_FILE_DEPR') . ACTION_NAME;
    } elseif (false === strpos($file, '/')) {
        $file = MODULE_NAME . C('TMPL_FILE_DEPR') . $file;
    }
    return $baseUrl . $file . C('TMPL_TEMPLATE_SUFFIX');
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
 * @return mixed
 */
function I($name, $default = '', $filter = null)
{
    
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
            if (C('VAR_URL_PARAMS') && isset($_GET[C('VAR_URL_PARAMS')])) {
                $input = array_merge($input, $_GET[C('VAR_URL_PARAMS')]);
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
        default:
            return NULL;
    }

    // Global Filtering
    if (C('VAR_FILTERS')) {
        $_filters = explode(',', C('VAR_FILTERS'));
        foreach ($_filters as $_filter) {
            // Overall situationparameterfilter
            array_walk_recursive($input, $_filter);
        }
    }
    if (empty($name)) { // ObtainCompletevariable
        $data = $input;
        $filters = $filter ?? C('DEFAULT_FILTER');
        if ($filters) {
            $filters = explode(',', $filters);
            foreach ($filters as $filter) {
                $data = array_map($filter, $data); // parameterfilter
            }
        }
    } elseif (isset($input[$name])) { // The valueoperating
        $data = $input[$name];
        $filters = $filter ?? C('DEFAULT_FILTER');
        if ($filters) {
            $filters = explode(',', $filters);
            foreach ($filters as $filter) {
                if (function_exists($filter)) {
                    $data = is_array($data) ? array_map($filter, $data) : $filter($data); // parameterfilter
                } else {
                    $data = filter_var($data, is_int($filter) ? $filter : filter_id($filter));
                    if (false === $data) {
                        return $default ?? NULL;
                    }
                }
            }
        }
    } else { // variableDefaults
        $data = $default ?? NULL;
    }
    is_array($data) && array_walk_recursive($data, 'think_filter');
    return $data;
}

/**
 * Recorded statistical time (microseconds) and memory usage
 * Instructions:
 * <code>
 * G('begin'); // recordingStartFlag
 * // ... Interval running code
 * G('end'); // recordingEnd tagPlace
 * echo G('begin','end',6); // Statistical information interval running time is accurate to six decimal places
 * echo G('begin','end','m'); // Statistics interval RAM usage
 * In case the end flag is not defined, it will automatically use the current as the flag
 * The statistical data RAM usage requires that the MEMORY_LIMIT_ON constant is true to be effective
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
    if (is_float($end)) { // recording time
        $_info[$start] = $end;
    } elseif (!empty($end)) { // statistics time and memory use
        if (!isset($_info[$end])) $_info[$end] = microtime(TRUE);
        if (MEMORY_LIMIT_ON && $dec == 'm') {
            if (!isset($_mem[$end])) $_mem[$end] = memory_get_usage();
            return number_format(($_mem[$end] - $_mem[$start]) / 1024);
        } else {
            return number_format(($_info[$end] - $_info[$start]), $dec);
        }

    } else { // recording time And memory used
        $_info[$start] = microtime(TRUE);
        if (MEMORY_LIMIT_ON) $_mem[$start] = memory_get_usage();
    }
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
 * @return mixed
 */
function N($key, $step = 0, $save = false)
{
    static $_num = array();
    if (!isset($_num[$key])) {
        $_num[$key] = (false !== $save) ? S('N_' . $key) : 0;
    }
    if (empty($step))
        return $_num[$key];
    else
        $_num[$key] = $_num[$key] + (int)$step;
    if (false !== $save) { // Storageresult
        S('N_' . $key, $_num[$key], $save);
    }
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
        return ucfirst(preg_replace("/_([a-zA-Z])/", "strtoupper('\\1')", $name));
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
 * Batch import file Success, returns
 * @param array $array An array of files
 * @param boolean $return Whether to return after a successful load
 * @return boolean
 */
function require_array($array, $return = false)
{
    foreach ($array as $file) {
        if (require_cache($file) && $return) return true;
    }
    if ($return) return false;
}

/**
 * Case sensitivedocumentexistjudgment
 * @param string $filename Address file
 * @return boolean
 */
function file_exists_case($filename)
{
    if (is_file($filename)) {
        if (IS_WIN && C('APP_FILE_CASE')) {
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
function import($class, $baseUrl = '', $ext = '.class.php')
{
    static $_file = array();
    $class = str_replace(array('.', '#'), array('/', '.'), $class);
    if ('' === $baseUrl && false === strpos($class, '/')) {
        // Check the Alias import
        return alias_import($class);
    }
    if (isset($_file[$class . $baseUrl]))
        return true;
    else
        $_file[$class . $baseUrl] = true;
    $class_strut = explode('/', $class);
    if (empty($baseUrl)) {
        $libPath = defined('BASE_LIB_PATH') ? BASE_LIB_PATH : LIB_PATH;
        if ('@' == $class_strut[0] || APP_NAME == $class_strut[0]) {
            //loadcurrentprojectapplicationClass Library
            $baseUrl = dirname($libPath);
            $class = substr_replace($class, basename($libPath) . '/', 0, strlen($class_strut[0]) + 1);
        } elseif ('think' == strtolower($class_strut[0])) { // think Official Base Class Library
            $baseUrl = CORE_PATH;
            $class = substr($class, 6);
        } elseif (in_array(strtolower($class_strut[0]), array('org', 'com'))) {
            // org Third-party public libraries com Enterprise Institute for Public Library
            $baseUrl = LIBRARY_PATH;
        } else { // loadotherprojectapplicationClass Library
            $class = substr_replace($class, '', 0, strlen($class_strut[0]) + 1);
            $baseUrl = APP_PATH . '../' . $class_strut[0] . '/' . basename($libPath) . '/';
        }
    }
    if (substr($baseUrl, -1) != '/')
        $baseUrl .= '/';
    $classfile = $baseUrl . $class . $ext;
    if (!class_exists(basename($class), false)) {
        // in caseclassdoes not exist thenImportingClass Libraryfile
        return require_cache($classfile);
    }
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
        if (0 === strpos($name, '@/')) {
            //Load current project library
            $baseUrl = COMMON_PATH;
            $name = substr($name, 2);
        } else {
            //loadThinkPHP System library
            $baseUrl = EXTEND_PATH . 'Function/';
        }
    }
    if (substr($baseUrl, -1) != '/')
        $baseUrl .= '/';
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
    if (empty($baseUrl))
        $baseUrl = VENDOR_PATH;
    return import($class, $baseUrl, $ext);
}

/**
 * fastdefinitionwithImportingSlug Support batch definitions
 * @param string|array $alias Class LibrarySlug
 * @param string $classfile correspondClass Library
 * @return boolean
 */
function alias_import($alias, $classfile = '')
{
    static $_alias = array();
    if (is_string($alias)) {
        if (isset($_alias[$alias])) {
            return require_cache($_alias[$alias]);
        } elseif ('' !== $classfile) {
            // Import alias definitions
            $_alias[$alias] = $classfile;
            return;
        }
    } elseif (is_array($alias)) {
        $_alias = array_merge($_alias, $alias);
        return;
    }
    return false;
}

/**
 * DFunction for instantiatingModel format project://Packet/Module
 * @param string $name ModelResource Locator
 * @param string $layer Business layer name
 * @return Model
 */
function D($name = '', $layer = '')
{
    if (empty($name)) return new Model;
    static $_model = array();
    $layer = $layer ?: C('DEFAULT_M_LAYER');
    if (strpos($name, '://')) {// Designationproject
        list($app) = explode('://', $name);
        $name = str_replace('://', '/' . $layer . '/', $name);
    } else {
        $app = C('DEFAULT_APP');
        $name = $app . '/' . $layer . '/' . $name;
    }
    if (isset($_model[$name])) return $_model[$name];
    $path = explode('/', $name);
    if ($list = C('EXTEND_GROUP_LIST') && isset($list[$app])) { // SpreadPacket
        $baseUrl = $list[$app];
        import($path[2] . '/' . $path[1] . '/' . $path[3] . $layer, $baseUrl);
    } elseif (count($path) > 3 && 1 == C('APP_GROUP_MODE')) { // independentPacket
        $baseUrl = $path[0] == '@' ? dirname(BASE_LIB_PATH) : APP_PATH . '../' . $path[0] . '/' . C('APP_GROUP_PATH') . '/';
        import($path[2] . '/' . $path[1] . '/' . $path[3] . $layer, $baseUrl);
    } else {
        import($name . $layer);
    }
    $class = basename($name . $layer);
    if (class_exists($class)) {
        $model = new $class(basename($name));
    } else {
        $model = new Model(basename($name));
    }
    $_model[$name] = $model;
    return $model;
}

/**
 * MFunction for instantiatingOneNomodelDocumentsModel
 * @param string $name Modelname Supports the specified base model E.g. MongoModel:User
 * @param string $tablePrefix Table Prefix
 * @param mixed $connection Database connection information
 * @return Model
 */
function M($name = '', $tablePrefix = '', $connection = '')
{
    static $_model = array();
    if (strpos($name, ':')) {
        list($class, $name) = explode(':', $name);
    } else {
        $class = 'Model';
    }
    $guid = $tablePrefix . $name . '_' . $class;
    if (!isset($_model[$guid]))
        $_model[$guid] = new $class($name, $tablePrefix, $connection);
    return $_model[$guid];
}

/**
 * AFunction for instantiatingAction format:[project://][Packet/]Module
 * @param string $name ActionResource Locator
 * @param string $layer Control layer name
 * @param boolean $common Whether public directory
 * @return Action|false
 */
function A($name, $layer = '', $common = false)
{
    static $_action = array();
    $layer = $layer ?: C('DEFAULT_C_LAYER');
    if (strpos($name, '://')) {// Designationproject
        list($app) = explode('://', $name);
        $name = str_replace('://', '/' . $layer . '/', $name);
    } else {
        $app = '@';
        $name = '@/' . $layer . '/' . $name;
    }
    if (isset($_action[$name])) return $_action[$name];
    $path = explode('/', $name);
    if ($list = C('EXTEND_GROUP_LIST') && isset($list[$app])) { // SpreadPacket
        $baseUrl = $list[$app];
        import($path[2] . '/' . $path[1] . '/' . $path[3] . $layer, $baseUrl);
    } elseif (count($path) > 3 && 1 == C('APP_GROUP_MODE')) { // independentPacket
        $baseUrl = $path[0] == '@' ? dirname(BASE_LIB_PATH) : APP_PATH . '../' . $path[0] . '/' . C('APP_GROUP_PATH') . '/';
        import($path[2] . '/' . $path[1] . '/' . $path[3] . $layer, $baseUrl);
    } elseif ($common) { // Load public library catalog
        import(str_replace('@/', '', $name) . $layer, LIB_PATH);
    } else {
        import($name . $layer);
    }
    $class = basename($name . $layer);
    if (class_exists($class, false)) {
        $action = new $class();
        $_action[$name] = $action;
        return $action;
    } else {
        return false;
    }
}

/**
 * 远程transferModuleofoperatingmethod URL Parameter format [project://][Packet/]Module/operating
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
 * ObtainwithSet upLanguagedefinition(not case sensitive)
 * @param string|array $name Linguistic variables
 * @param string $value Language value
 * @return mixed
 */
function L($name = null, $value = null)
{
    static $_lang = array();
    // airparameterreturnalldefinition
    if (empty($name))
        return $_lang;
    // judgmentLanguageObtain(orSet up)
    // If thedoes not exist,Direct returnALL CAPS$name
    if (is_string($name)) {
        $name = strtoupper($name);
        if (is_null($value))
            return $_lang[$name] ?? $name;
        $_lang[$name] = $value; // Languagedefinition
        return;
    }
    // batchdefinition
    if (is_array($name))
        $_lang = array_merge($_lang, array_change_key_case($name, CASE_UPPER));
    return;
}

/**
 * ObtainwithSet upConfiguration parameters Support batch definitions
 * @param string|array $name Configuration Variables
 * @param mixed $value Configuration values
 * @return mixed
 */
function C($name = null, $value = null)
{

    static $_config = array();
    // noparameterTimeObtainall
    if (empty($name)) {
        if (!empty($value) && $array = S('c_' . $value)) {
            $_config = array_merge($_config, array_change_key_case($array));
        }
        return $_config;
    }
    // prioritycarried outSet upObtainOr assignment
    if (is_string($name)) {
        if (!strpos($name, '.')) {
            $name = strtolower($name);
            if (is_null($value))
                return $_config[$name] ?? null;
            $_config[$name] = $value;
            return;
        }
        // Two-dimensionalArraySet upwithObtainstand by
        $name = explode('.', $name);
        $name[0] = strtolower($name[0]);
        if (is_null($value))
            return $_config[$name[0]][$name[1]] ?? null;
        $_config[$name[0]][$name[1]] = $value;
        return;
    }
    // batchSet up
    if (is_array($name)) {
        $_config = array_merge($_config, array_change_key_case($name));
        if (!empty($value)) {// Save the configuration values
            S('c_' . $value, $_config);
        }
        return;
    }
    return null; // Avoid Illegalparameter
}

/**
 * Label treatment extension
 * @param string $tag Label name
 * @param mixed $params Incoming parameters
 * @return mixed
 */
function tag($tag, &$params = NULL)
{
    // Label extension system
    $extends = C('extends.' . $tag);
    // Application label extension
    $tags = C('tags.' . $tag);
    if (!empty($tags)) {
        if (empty($tags['_overlay']) && !empty($extends)) { // The combined expansion
            $tags = array_unique(array_merge($extends, $tags));
        } elseif (isset($tags['_overlay'])) { // By setting '_overlay'=>1 Cover system label
            unset($tags['_overlay']);
        }
    } elseif (!empty($extends)) {
        $tags = $extends;
    }
    if ($tags) {
        if (APP_DEBUG) {
            G($tag . 'Start');
            trace('[ ' . $tag . ' ] --START--', '', 'INFO');
        }
        // Perform an extended
        foreach ($tags as $key => $name) {
            if (!is_int($key)) { // DesignationbehaviorCategorycompletepath For extended mode
                $name = $key;
            }
            B($name, $params);
        }
        if (APP_DEBUG) { // recordingbehaviorofcarried outJournal
            trace('[ ' . $tag . ' ] --END-- [ RunTime:' . G($tag . 'Start', $tag . 'End', 6) . 's ]', '', 'INFO');
        }
    } else { // Not to perform any act returnfalse
        return false;
    }
}

/**
 * dynamicAdd toBehavior extensionTo alabel
 * @param string $tag Label name
 * @param string $behavior Behavior name
 * @param string $path Path behavior
 * @return void
 */
function add_tag_behavior($tag, $behavior, $path = '')
{
    $array = C('tags.' . $tag);
    if (!$array) {
        $array = array();
    }
    if ($path) {
        $array[$behavior] = $path;
    } else {
        $array[] = $behavior;
    }
    C('tags.' . $tag, $array);
}

/**
 * The implementation of an action
 * @param string $name Behavior name
 * @param Mixed $params Incoming parameters
 * @return void
 */
function B($name, &$params = NULL)
{
    if (strpos($name, '/')) {
        list($name, $method) = explode('/', $name);
    } else {
        $method = 'run';
    }
    $class = $name . 'Behavior';
    if (APP_DEBUG) {
        G('behaviorStart');
    }
    $behavior = new $class();
    $behavior->$method($params);
    if (APP_DEBUG) { // recordingbehaviorofcarried outJournal
        G('behaviorEnd');
        trace($name . ' Behavior ::' . $method . ' [ RunTime:' . G('behaviorStart', 'behaviorEnd', 6) . 's ]', '', 'INFO');
    }
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

//[RUNTIME]
// Compile file
function compile($filename)
{
    $content = file_get_contents($filename);
    // replaceAdvanceCompileinstruction
    $content = preg_replace('/\/\/\[RUNTIME\](.*?)\/\/\[\/RUNTIME\]/s', '', $content);
    $content = substr(trim($content), 5);
    if ('?>' == substr($content, -2))
        $content = substr($content, 0, -2);
    return $content;
}

// according toArrayFormconstantdefinition
function array_define($array, $check = true)
{
    $content = "\n";
    foreach ($array as $key => $val) {
        $key = strtoupper($key);
        if ($check) $content .= 'defined(\'' . $key . '\') or ';
        if (is_int($val) || is_float($val)) {
            $content .= "define('" . $key . "'," . $val . ');';
        } elseif (is_bool($val)) {
            $val = ($val) ? 'true' : 'false';
            $content .= "define('" . $key . "'," . $val . ');';
        } elseif (is_string($val)) {
            $content .= "define('" . $key . "','" . addslashes($val) . "');";
        }
        $content .= "\n";
    }
    return $content;
}

//[/RUNTIME]

/**
 * Adding and get the pageTracerecording
 * @param string $value variable
 * @param string $label label
 * @param string $level Log Level
 * @param boolean $record Whether logging
 * @return void
 */
function trace($value = '[think]', $label = '', $level = 'DEBUG', $record = false)
{
    static $_trace = array();
    if ('[think]' === $value) { // Obtaintraceinformation
        return $_trace;
    } else {
        $info = ($label ? $label . ':' : '') . print_r($value, true);
        if ('ERR' == $level && C('TRACE_EXCEPTION')) {// Throw an exception
            throw_exception($info);
        }
        $level = strtoupper($level);
        if (!isset($_trace[$level])) {
            $_trace[$level] = array();
        }
        $_trace[$level][] = $info;
        if ((defined('IS_AJAX') && IS_AJAX) || !C('SHOW_PAGE_TRACE') || $record) {
            Log::record($info, $level, $record);
        }
    }
}
