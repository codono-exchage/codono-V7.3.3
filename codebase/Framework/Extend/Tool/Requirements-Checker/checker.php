<?php

/**
 * Requirements Checker: This script will check if your system meets
 * the requirements for running ThinkPHP Framework.
 *
 * This file is part of the ThinkPHP Framework Referencenetteframe(http://nette.org)
 *
 * For the full copyright and license information, please view
 * the file license.txt that was distributed with this source code.
 */


/**
 * Check PHP configuration.
 */
foreach (array('function_exists', 'version_compare', 'extension_loaded', 'ini_get') as $function) {
    if (!function_exists($function)) {
        die("Error: function '$function' is required by ThinkPHP Framework and this Requirements Checker.");
    }
}

/**
 * Check assetstable of Contents, Template file must be readable
 */
define('TEMPLATE_FILE', __DIR__ . '/assets/checker.phtml');
if (!is_readable(TEMPLATE_FILE)) {
    die("Error: Template files unreadable.an examinationassetstable of Contents(Release part),There should be,Readable and contain readableofTemplate files.");
}

/**
 * Check ThinkPHP Framework requirements.
 */
define('CHECKER_VERSION', '1.0');

$tests[] = array(
    'title' => 'Webserver',
    'message' => $_SERVER['SERVER_SOFTWARE'],
);

$tests[] = array(
    'title' => 'PHPversion',
    'required' => TRUE,
    'passed' => version_compare(PHP_VERSION, '5.2.0', '>='),
    'message' => PHP_VERSION,
    'description' => 'yourPHPtoo low.ThinkPHPFramework needs to be at leastPHP 5.2.0Or higher.',
);

$tests[] = array(
    'title' => 'Memory_limit',
    'message' => ini_get('memory_limit'),
);

$tests['hf'] = array(
    'title' => '.htaccess File Protection',
    'required' => FALSE,
    'description' => 'by<code>.htaccess</code>ofFileProtection is not supported.youhave toBe carefulofput into afileTodocument_roottable of Contents.',
    'script' => '<script src="assets/denied/checker.js"></script><script>displayResult("hf", typeof fileProtectionChecker == "undefined")</script>',
);

$tests['hr'] = array(
    'title' => '.htaccess mod_rewrite',
    'required' => FALSE,
    'description' => 'Mod_rewriteMay not support.You will not be able to useCool URL(URL_MODEL=2Action does not start,EntrancefileUnablehide).',
    'script' => '<script src="assets/rewrite/checker"></script><script>displayResult("hr", typeof modRewriteChecker == "boolean")</script>',
);

$tests[] = array(
    'title' => 'functionini_set()',
    'required' => FALSE,
    'passed' => function_exists('ini_set'),
    'description' => 'function<code>ini_set()</code>not support.sectionThinkPHPframeFeaturescancanjobs不normal.',
);

$tests[] = array(
    'title' => 'functionerror_reporting()',
    'required' => TRUE,
    'passed' => function_exists('error_reporting'),
    'description' => 'function<code>error_reporting()</code>not support. ThinkPHPThis framework needs to be enabled',
);

// $tests[] = array(
// 	'title' => 'Function flock()',
// 	'required' => TRUE,
// 	'passed' => flock(fopen(__FILE__, 'r'), LOCK_SH),
// 	'description' => 'Function <code>flock()</code> is not supported on this filesystem. ThinkPHP Framework requires this to process atomic file operations.',
// );
/*
$tests[] = array(
	'title' => 'Register_globals',
	'required' => TRUE,
	'passed' => iniFlag('register_globals'),
	'message' => 'Enabled',
	'errorMessage' => 'not support',
	'description' => 'ConfigurationConfigurationdisplay<code>register_globals</code>Disabled. ThinkPHPThis framework requires open.',
);
*/
// $tests[] = array(
// 	'title' => 'Variables_order',
// 	'required' => TRUE,
// 	'passed' => strpos(ini_get('variables_order'), 'G') !== FALSE && strpos(ini_get('variables_order'), 'P') !== FALSE && strpos(ini_get('variables_order'), 'C') !== FALSE,
// 	'description' => 'Configuration directive <code>variables_order</code> is missing. ThinkPHP Framework requires this to be set.',
// );

$tests[] = array(
    'title' => 'Session auto-start',
    'required' => FALSE,
    'passed' => session_id() === '' && !defined('SID'),
    'description' => 'Session auto-startEnabled. ThinkPHP framework by default after initialization thatsystemmeetingautomaticstart upsession.',
);

$tests[] = array(
    'title' => 'ReflectionSpread',
    'required' => TRUE,
    'passed' => class_exists('ReflectionFunction'),
    'description' => 'ThinkPHPMust be turnedReflectionSpread.',
);

// $tests[] = array(
// 	'title' => 'SPL extension',
// 	'required' => TRUE,
// 	'passed' => extension_loaded('SPL'),
// 	'description' => 'SPL extension is required.',
// );

$tests[] = array(
    'title' => 'PCRESpread',
    'required' => TRUE,
    'passed' => extension_loaded('pcre') && @preg_match('/pcre/u', 'pcre'),
    'message' => 'Support and working properly',
    'errorMessage' => 'Disabled or not supportedUTF-8',
    'description' => 'PCREExtended recommendation to open and supportUTF-8.',
);

$tests[] = array(
    'title' => 'ICONVSpread',
    'required' => TRUE,
    'passed' => extension_loaded('iconv') && (ICONV_IMPL !== 'unknown') && @iconv('UTF-16', 'UTF-8//IGNORE', iconv('UTF-8', 'UTF-16//IGNORE', 'test')) === 'test',
    'message' => 'Support and working properly',
    'errorMessage' => 'Disabled or not working properly',
    'description' => 'ICONVAnd extension must work properly.',
);

// $tests[] = array(
// 	'title' => 'PHP tokenizer',
// 	'required' => TRUE,
// 	'passed' => extension_loaded('tokenizer'),
// 	'description' => 'PHP tokenizer is required.',
// );

$tests[] = array(
    'title' => 'PDOSpread',
    'required' => FALSE,
    'passed' => $pdo = extension_loaded('pdo') && PDO::getAvailableDrivers(),
    'message' => $pdo ? 'Drivers are availabledrivers: ' . implode(' ', PDO::getAvailableDrivers()) : NULL,
    'description' => 'PDOExtension orPDODriver does not support.You can not use<code>ThinkPHP\DbPdo</code>.',
);

$tests[] = array(
    'title' => 'Multi-byte string extension',
    'required' => FALSE,
    'passed' => extension_loaded('mbstring'),
    'description' => 'Multibyte StringExtension does not support.Some components may not be internationalizednormaljobs.',
);

$tests[] = array(
    'title' => 'Multibyte character stringoverloadingfunction',
    'required' => TRUE,
    'passed' => !extension_loaded('mbstring') || !(mb_get_info('func_overload') & 2),
    'message' => 'Disabled',
    'errorMessage' => 'Enabled',
    'description' => 'EnabledMultibyte character stringOverloadfunction. ThinkPHPThe framework requires disabled.in caseitOpenThe,someStringfunctionwillcancanjobs不normal.',
);

$tests[] = array(
    'title' => 'MemcacheSpread',
    'required' => FALSE,
    'passed' => extension_loaded('memcache'),
    'description' => 'MemcacheExtension does not support.You can not use<code>MemcacheAs aThinkPHPFashion cache</code>.',
);

$tests[] = array(
    'title' => 'GDSpread',
    'required' => TRUE,
    'passed' => extension_loaded('gd'),
    'description' => 'GDExtension does not support. You can not use<code>ThinkPHP\Image</code>class.',
);

$tests[] = array(
    'title' => 'ImagickSpread',
    'required' => FALSE,
    'passed' => extension_loaded('imagick'),
    'description' => 'ImagickExtension does not support. You can not useImagickImage processing efficient.',
);

// $tests[] = array(
// 	'title' => 'Bundled GD extension',
// 	'required' => FALSE,
// 	'passed' => extension_loaded('gd') && GD_BUNDLED,
// 	'description' => 'Bundled GD extension is absent. You will not be able to use some functions such as <code>ThinkPHP\Image::filter()</code> or <code>ThinkPHP\Image::rotate()</code>.',
// );

$tests[] = array(
    'title' => 'FileinfoSpread or mime_content_type()',
    'required' => FALSE,
    'passed' => extension_loaded('fileinfo') || function_exists('mime_content_type'),
    'description' => 'Fileinfo Extension or function<code>mime_content_type()</code> not support.You will not be ableDetectUpload filemimeTypes of.',
);

// $tests[] = array(
// 	'title' => 'HTTP_HOST or SERVER_NAME',
// 	'required' => TRUE,
// 	'passed' => isset($_SERVER["HTTP_HOST"]) || isset($_SERVER["SERVER_NAME"]),
// 	'message' => 'Present',
// 	'errorMessage' => 'Absent',
// 	'description' => 'Either <code>$_SERVER["HTTP_HOST"]</code> or <code>$_SERVER["SERVER_NAME"]</code> must be available for resolving host name.',
// );

$tests[] = array(
    'title' => 'REQUEST_URI or ORIG_PATH_INFO',
    'required' => TRUE,
    'passed' => isset($_SERVER["REQUEST_URI"]) || isset($_SERVER["ORIG_PATH_INFO"]),
    'message' => 'stand by',
    'errorMessage' => 'not support',
    'description' => ' <code>$_SERVER["REQUEST_URI"]</code> or<code>$_SERVER["ORIG_PATH_INFO"]</code>You will be able to learnObtainToForbreak downRequestedURL.',
);

// $tests[] = array(
// 	'title' => 'DOCUMENT_ROOT & SCRIPT_FILENAME or SCRIPT_NAME',
// 	'required' => TRUE,
// 	'passed' => isset($_SERVER['DOCUMENT_ROOT'], $_SERVER['SCRIPT_FILENAME']) || isset($_SERVER['SCRIPT_NAME']),
// 	'message' => 'Present',
// 	'errorMessage' => 'Absent',
// 	'description' => '<code>$_SERVER["DOCUMENT_ROOT"]</code> and <code>$_SERVER["SCRIPT_FILENAME"]</code> or <code>$_SERVER["SCRIPT_NAME"]</code> must be available for resolving script file path.',
// );

// $tests[] = array(
// 	'title' => 'SERVER_ADDR or LOCAL_ADDR',
// 	'required' => TRUE,
// 	'passed' => isset($_SERVER["SERVER_ADDR"]) || isset($_SERVER["LOCAL_ADDR"]),
// 	'message' => 'Present',
// 	'errorMessage' => 'Absent',
// 	'description' => '<code>$_SERVER["SERVER_ADDR"]</code> or <code>$_SERVER["LOCAL_ADDR"]</code> must be available for detecting development / production mode.',
// );

paint($tests);
//+++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++//
/**
 * Paints checker.
 * @param  array
 * @return void
 */
function paint($requirements)
{
    $errors = $warnings = FALSE;

    foreach ($requirements as $id => $requirement) {
        $requirements[$id] = $requirement = (object)$requirement;
        if (isset($requirement->passed) && !$requirement->passed) {
            if ($requirement->required) {
                $errors = TRUE;
            } else {
                $warnings = TRUE;
            }
        }
    }

    require TEMPLATE_FILE;
}

/**
 * Gets a Boolean value configuration item.
 * @param  string  Configuration Items name
 * @return bool
 */
function iniFlag($var)
{
    $status = strtolower(ini_get($var));
    return $status === 'on' || $status === 'true' || $status === 'yes' || (int)$status;
}