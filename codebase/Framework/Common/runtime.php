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
 * ThinkPHP Runtime files After compilation is no longer loaded
 * @category   Think
 * @package  Common
 * @author   liu21st <liu21st@gmail.com>
 */
defined('THINK_PATH') or exit();
if (version_compare(PHP_VERSION, '5.2.0', '<')) die('require PHP > 5.2.0 !');

//  Version Information
define('THINK_VERSION', '3.2.3');

define('IS_CGI', substr(PHP_SAPI, 0, 3) == 'cgi' ? 1 : 0);
define('IS_WIN', strstr(PHP_OS, 'WIN') ? 1 : 0);
define('IS_CLI', PHP_SAPI == 'cli' ? 1 : 0);

// project name
defined('APP_NAME') or define('APP_NAME', basename(dirname($_SERVER['SCRIPT_FILENAME'])));

if (!IS_CLI) {
    // currentfilename
    if (!defined('_PHP_FILE_')) {
        if (IS_CGI) {
            //CGI/FASTCGI mode 
            $_temp = explode('.php', $_SERVER['PHP_SELF']);
            define('_PHP_FILE_', rtrim(str_replace($_SERVER['HTTP_HOST'], '', $_temp[0] . '.php'), '/'));
        } else {
            define('_PHP_FILE_', rtrim($_SERVER['SCRIPT_NAME'], '/'));
        }
    }
    if (!defined('__ROOT__')) {
        // websiteURLRoot directory
        if (strtoupper(APP_NAME) == strtoupper(basename(dirname(_PHP_FILE_)))) {
            $_root = dirname(dirname(_PHP_FILE_));
        } else {
            $_root = dirname(_PHP_FILE_);
        }
        define('__ROOT__', (($_root == '/' || $_root == '\\') ? '' : $_root));
    }

    //SupportedURLmode
    define('URL_COMMON', 0);   //Normal mode
    define('URL_PATHINFO', 1);   //PATHINFOmode
    define('URL_REWRITE', 2);   //REWRITEmode
    define('URL_COMPAT', 3);   // Compatibility Mode
}

// Path settings CanEntrancefileinAgaindefinition allpathconstantAllMust/ end
defined('CORE_PATH') or define('CORE_PATH', THINK_PATH . 'Lib'.DIRECTORY_SEPARATOR); // systemcoreLibrary catalog
defined('EXTEND_PATH') or define('EXTEND_PATH', THINK_PATH . 'Extend'.DIRECTORY_SEPARATOR); // System extensions directory
defined('MODE_PATH') or define('MODE_PATH', EXTEND_PATH . 'Mode'.DIRECTORY_SEPARATOR); // Directory schema extensions
defined('ENGINE_PATH') or define('ENGINE_PATH', EXTEND_PATH . 'Engine'.DIRECTORY_SEPARATOR); // Extended engine directory
defined('VENDOR_PATH') or define('VENDOR_PATH', EXTEND_PATH . 'Vendor'.DIRECTORY_SEPARATOR); // Third-party libraries directory
defined('LIBRARY_PATH') or define('LIBRARY_PATH', EXTEND_PATH . 'Library'.DIRECTORY_SEPARATOR); // Extended library catalog
defined('COMMON_PATH') or define('COMMON_PATH', APP_PATH . 'Common'.DIRECTORY_SEPARATOR); // Public directory project
defined('LIB_PATH') or define('LIB_PATH', APP_PATH . 'Lib'.DIRECTORY_SEPARATOR); // Project library catalog
defined('CONF_PATH') or define('CONF_PATH', APP_PATH . 'Conf'.DIRECTORY_SEPARATOR); // Project configuration directory
defined('LANG_PATH') or define('LANG_PATH', APP_PATH . 'Lang'.DIRECTORY_SEPARATOR); // Project Language Pack directory
defined('TMPL_PATH') or define('TMPL_PATH', APP_PATH . 'Tpl'.DIRECTORY_SEPARATOR); // projecttemplatetable of Contents
defined('HTML_PATH') or define('HTML_PATH', APP_PATH . 'Html'.DIRECTORY_SEPARATOR); // The static directory
defined('LOG_PATH') or define('LOG_PATH', RUNTIME_PATH . 'Logs'.DIRECTORY_SEPARATOR); // Log directory project
defined('TEMP_PATH') or define('TEMP_PATH', RUNTIME_PATH . 'Temp'.DIRECTORY_SEPARATOR); // Cache directory project
defined('DATA_PATH') or define('DATA_PATH', RUNTIME_PATH . 'Data'.DIRECTORY_SEPARATOR); // Project data directory
defined('CACHE_PATH') or define('CACHE_PATH', RUNTIME_PATH . 'Cache'.DIRECTORY_SEPARATOR); // projecttemplateCachetable of Contents

// in order toConvenienceImportingThird partyClass Library Set upVendorDirectory toinclude_path
set_include_path(get_include_path() . PATH_SEPARATOR . VENDOR_PATH);

// loadrunTimeRequiredwantdocument And is responsible for automatic directory generation
function load_runtime_file()
{
    // Loading system library foundation
    require THINK_PATH . 'Common/common.php';
    // ReadCore File List
    $list = array(
        CORE_PATH . 'Core/Think.class.php',
        CORE_PATH . 'Core/ThinkException.class.php',  // Exception class
        CORE_PATH . 'Core/Behavior.class.php',
    );
    // Loading pattern file list
    foreach ($list as $key => $file) {
        if (is_file($file)) require_cache($file);
    }
    // loadsystemClass LibraryAlias definition
    alias_import(include THINK_PATH . 'Conf/alias.php');

    // Check the project directory structure in casedoes not existthenautomaticcreate
    if (!is_dir(LIB_PATH)) {
        // createprojecttable of Contentsstructure
        build_app_dir();
    } elseif (!is_dir(CACHE_PATH)) {
        // an examinationCachetable of Contents
        check_runtime();
    } elseif (APP_DEBUG) {
        // debuggingmodeSwitchdeleteCompileCache
        if (is_file(RUNTIME_FILE)) unlink(RUNTIME_FILE);
    }
}

// an examinationCachetable of Contents(Runtime) in casedoes not existthenautomaticcreate
function check_runtime()
{
    if (!is_dir(RUNTIME_PATH)) {
        mkdir(RUNTIME_PATH);
    } elseif (!is_writeable(RUNTIME_PATH)) {
        header('Content-Type:text/html; charset=utf-8');
        exit('table of Contents [ ' . RUNTIME_PATH . ' ] Can not write!');
    }
    mkdir(CACHE_PATH);  // templateCachetable of Contents
    if (!is_dir(LOG_PATH)) mkdir(LOG_PATH);    // Journaltable of Contents
    if (!is_dir(TEMP_PATH)) mkdir(TEMP_PATH);   // dataCachetable of Contents
    if (!is_dir(DATA_PATH)) mkdir(DATA_PATH);   // dataFile Directory
    return true;
}

// Create a compilation cache
function build_runtime_cache($append = '')
{
    // A compiled file
    $defs = get_defined_constants(TRUE);
    $content = '$GLOBALS[\'_beginTime\'] = microtime(TRUE);';
    if (defined('RUNTIME_DEF_FILE')) { // CompileAfterconstantfileOutsideIntroduced
        file_put_contents(RUNTIME_DEF_FILE, '<?php ' . array_define($defs['user']));
        $content .= 'require \'' . RUNTIME_DEF_FILE . '\';';
    } else {
        $content .= array_define($defs['user']);
    }
    $content .= 'set_include_path(get_include_path() . PATH_SEPARATOR . VENDOR_PATH);';
    // ReadcoreCompile fileList
    $list = array(
        THINK_PATH . 'Common/common.php',
        CORE_PATH . 'Core/Think.class.php',
        CORE_PATH . 'Core/ThinkException.class.php',
        CORE_PATH . 'Core/Behavior.class.php',
    );
    foreach ($list as $file) {
        $content .= compile($file);
    }
    // System behavior extensionfileUniteCompile
    $content .= build_tags_cache();

    $alias = include THINK_PATH . 'Conf/alias.php';
    $content .= 'alias_import(' . var_export($alias, true) . ');';
    // CompileframedefaultAnd language packsConfiguration parameters
    $content .= $append . "\nL(" . var_export(L(), true) . ");C(" . var_export(C(), true) . ');G(\'loadTime\');Think::Start();';
    file_put_contents(RUNTIME_FILE, strip_whitespace('<?php ' . str_replace("defined('THINK_PATH') or exit();", ' ', $content)));
}

// CompileSystem behavior extensionClass Library
function build_tags_cache()
{
    $tags = C('extends');
    $content = '';
    foreach ($tags as $tag => $item) {
        foreach ($item as $key => $name) {
            $content .= is_int($key) ? compile(CORE_PATH . 'Behavior'.DIRECTORY_SEPARATOR . $name . 'Behavior.class.php') : compile($name);
        }
    }
    return $content;
}

// createprojecttable of Contentsstructure
function build_app_dir()
{
    // Nocreateprojecttable of Contentsofwordsautomaticcreate
    if (!is_dir(APP_PATH)) mkdir(APP_PATH, 0755, true);
    if (is_writeable(APP_PATH)) {
        $dirs = array(
            LIB_PATH,
            RUNTIME_PATH,
            CONF_PATH,
            COMMON_PATH,
            LANG_PATH,
            CACHE_PATH,
            TMPL_PATH,
            TMPL_PATH . C('DEFAULT_THEME') .DIRECTORY_SEPARATOR,
            LOG_PATH,
            TEMP_PATH,
            DATA_PATH,
            LIB_PATH . 'Model'.DIRECTORY_SEPARATOR,
            LIB_PATH . 'Action'.DIRECTORY_SEPARATOR,
            LIB_PATH . 'Behavior'.DIRECTORY_SEPARATOR,
            LIB_PATH . 'Widget'.DIRECTORY_SEPARATOR,
        );
        foreach ($dirs as $dir) {
            if (!is_dir($dir)) mkdir($dir, 0755, true);
        }
        // Writetable of ContentsSafetyfile
        build_dir_secure($dirs);
        // Write the initial configuration file
        if (!is_file(CONF_PATH . 'config.php'))
            file_put_contents(CONF_PATH . 'config.php', "<?php\nreturn array(\n\t//'Configuration Item'=>'Configuration values'\n);\n?>");
        // Written testAction
        if (!is_file(LIB_PATH . 'Action/IndexAction.class.php'))
            build_first_action();
    } else {
        header('Content-Type:text/html; charset=utf-8');
        exit('projecttable of ContentsCan notwrite,table of ContentsUnableAutomatic generated!<BR>pleaseuseprojectFormDeviceorManuallyFormprojecttable of Contents~');
    }
}

// createtestAction
function build_first_action()
{
    $content = file_get_contents(THINK_PATH . 'Tpl/default_index.tpl');
    file_put_contents(LIB_PATH . 'Action/IndexAction.class.php', $content);
}

// Formtable of ContentsSafetyfile
function build_dir_secure($dirs = array())
{
    // Directory Security write
    if (defined('BUILD_DIR_SECURE') && BUILD_DIR_SECURE) {
        defined('DIR_SECURE_FILENAME') or define('DIR_SECURE_FILENAME', 'index.html');
        defined('DIR_SECURE_CONTENT') or define('DIR_SECURE_CONTENT', ' ');
        // automaticWritetable of ContentsSafetyfile
        $content = DIR_SECURE_CONTENT;
        $files = explode(',', DIR_SECURE_FILENAME);
        foreach ($files as $filename) {
            foreach ($dirs as $dir)
                file_put_contents($dir . $filename, $content);
        }
    }
}

// When loading the required files to run
load_runtime_file();
// recordingLoad filetime
G('loadTime');
// Execution entry
Think::Start();