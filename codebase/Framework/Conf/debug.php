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
 * ThinkPHP default of debugging Mode profile
 */
defined('THINK_PATH') or exit();
// debugging mode the following default Set up allowable Application configuration directory under Again definition debug.php cover
return array(
    'LOG_RECORD' => true,  // Logging
    'LOG_EXCEPTION_RECORD' => true,    // whether recording abnormal information Journal
    'LOG_LEVEL' => 'EMERG,ALERT,CRIT,ERR,WARN,NOTIC,INFO,DEBUG,SQL',  // allow recording ofLog Level
    'DB_FIELDS_CACHE' => true, // Field cache information
    'DB_DEBUG' => true, // Open debugging mode recording SQL Journal
    'TMPL_CACHE_ON' => true,        // whether Open template Compile Cache,Set falseThen every timeAgainCompile
    'TMPL_STRIP_SPACE' => true,       // whether Removal Template files inside html Spaces and line breaks
    'SHOW_ERROR_MSG' => true,    // display Error Messages
    'URL_CASE_INSENSITIVE' => true,  // URL Case sensitive
    'SHOW_PAGE_TRACE' => true,
);