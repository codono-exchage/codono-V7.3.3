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
 * ThinkPHPConvention Profile
 * ThatfilePlease do notmodify,in casewantcoverConventionConfigured value,Canapplication Configuration file Set and match practiceConfigurationitem
 * Configuration NameIn any case,systemmeetingUniteChangeLowercase
 * allConfiguration parametersCaninBecome effectiveBefore the dynamic change
 */
defined('THINK_PATH') or exit();
return array(
    /* Application settings */
    'APP_SUB_DOMAIN_DEPLOY' => false,   // whetherOpenchildareanamedeploy
    'APP_SUB_DOMAIN_RULES' => array(), // childareanamedeployrule
    'APP_DOMAIN_SUFFIX' => '', // areanamesuffix ifcom.cn net.cn ItCategorysuffixhave toSet up
    'ACTION_SUFFIX' => '', // operatingmethodsuffix
    'MULTI_MODULE' => true, // whetherallowMulti-module Iffalse thenhave toSet up DEFAULT_MODULE
    'MODULE_DENY_LIST' => array('Common', 'Runtime'),

    /* CookieSet up */
    'COOKIE_EXPIRE' => 0,       // CookieValidity
    'COOKIE_DOMAIN' => '',      // Cookieeffectiveareaname
    'COOKIE_PATH' => '/',     // Cookiepath
    'COOKIE_PREFIX' => '',      // CookiePrefix avoid confict
    'COOKIE_SECURE' => false,   // CookieSecure transmission
    'COOKIE_HTTPONLY' => '',      // Cookie httponlySet up

    /* Default settings */
    'DEFAULT_M_LAYER' => 'Model', // defaultofModel layer name
    'DEFAULT_C_LAYER' => 'Controller', // defaultofControllerFloorname
    'DEFAULT_V_LAYER' => 'View', // defaultofView layername
    'DEFAULT_LANG' => 'en-us', // defaultLanguage
    'DEFAULT_THEME' => '',    // defaultTemplate Themename
    'DEFAULT_MODULE' => 'Home',  // defaultModule
    'DEFAULT_CONTROLLER' => 'Index', // defaultControllername
    'DEFAULT_ACTION' => 'index', // defaultoperatingname
    'DEFAULT_CHARSET' => 'utf-8', // defaultOutput encoding
    'DEFAULT_TIMEZONE' => 'PRC',    // defaultTime zone
    'DEFAULT_AJAX_RETURN' => 'JSON',  // defaultAJAX datareturnformat,OptionalJSON XML ...
    'DEFAULT_JSONP_HANDLER' => 'jsonpReturn', // defaultJSONPformatreturndeal withmethod
    'DEFAULT_FILTER' => 'htmlspecialchars', // The default parametersFiltration method ForIfunction...

    /* Database Settings */
    'DB_TYPE' => '',     // databaseTypes of
    'DB_HOST' => '', // serveraddress
    'DB_NAME' => '',          // DatabaseName
    'DB_USER' => '',      // username
    'DB_PWD' => '',          // password
    'DB_PORT' => '',        // port
    'DB_PREFIX' => '',    // databaseTable Prefix
    'DB_PARAMS' => array(), // Database Connectivityparameter
    'DB_DEBUG' => TRUE, // Database Debuggingmode After opening can be recordedSQLJournal
    'DB_FIELDS_CACHE' => true,        // EnableFieldCache
    'DB_CHARSET' => 'utf8',      // databasecodingdefaultuseutf8
    'DB_DEPLOY_TYPE' => 0, // databasedeploythe way:0 centralized(singleserver),1 distributed(Master-slaveserver)
    'DB_RW_SEPARATE' => false,       // databaseRead and writewhetherSeparate Master-slave effective
    'DB_MASTER_NUM' => 1, // After the separate read and write Primary serverQuantity
    'DB_SLAVE_NO' => '', // DesignationFromserverNo.

    /* Data cache settings */
    'DATA_CACHE_TIME' => 0,      // dataCache Expiration 0Permanent representationCache
    'DATA_CACHE_COMPRESS' => false,   // dataCachewhethercompressionCache
    'DATA_CACHE_CHECK' => false,   // dataCachewhethercheckCache
    'DATA_CACHE_PREFIX' => '',     // CachePrefix
    'DATA_CACHE_TYPE' => 'File',  // dataCache type,stand by:File|Db|Apc|Memcache|Shmop|Sqlite|Xcache|Apachenote|Eaccelerator
    'DATA_CACHE_PATH' => TEMP_PATH,// Cache pathSet up (OnlyFilethe wayCacheeffective)
    'DATA_CACHE_KEY' => '',    // CachefileKEY (OnlyFilethe wayCacheeffective)
    'DATA_CACHE_SUBDIR' => false,    // usechildtable of ContentsCache (automaticaccording toCacheMarkof哈希createchildtable of Contents)
    'DATA_PATH_LEVEL' => 1,        // childtable of ContentsCachelevel

    /* Error settings */
    'ERROR_MESSAGE' => 'Page Fault! Please try again later ~',//errordisplayinformation,Non-debugmodeeffective
    'ERROR_PAGE' => '',    // errorsettopage
    'SHOW_ERROR_MSG' => false,    // displayError Messages
    'TRACE_MAX_RECORD' => 100,    // EachLevelsError Messages maximumrecordingnumber

    /* Log Settings */
    'LOG_RECORD' => false,   // Not recorded by default
    'LOG_TYPE' => 'File', // JournalrecordingTypes of The default isfilethe way
    'LOG_LEVEL' => 'EMERG,ALERT,CRIT,ERR',// allowrecordingofLog Level
    'LOG_FILE_SIZE' => 2097152,    // JournalFile sizelimit
    'LOG_EXCEPTION_RECORD' => false,    // whetherrecordingabnormalinformationJournal

    /* SESSIONSet up */
    'SESSION_AUTO_START' => false,    // whetherAutomatically openSession
    'SESSION_OPTIONS' => array(), // session ConfigurationArray stand bytype name id path expire domain Waitparameter
    'SESSION_TYPE' => '', // session handerTypes of No need to set default unlessSpreadThesession handerdrive
    'SESSION_PREFIX' => '', // session Prefix
    //'VAR_SESSION_ID'      =>  'session_id',     //sessionIDSubmission variables

    /* Template engine settings */
    'TMPL_CONTENT_TYPE' => 'text/html', // defaulttemplateOutput Type
    'TMPL_ACTION_ERROR' => THINK_PATH . 'Tpl'.DIRECTORY_SEPARATOR.'dispatch_jump.tpl', // defaulterrorJumpcorrespondingTemplate files
    'TMPL_ACTION_SUCCESS' => THINK_PATH . 'Tpl'.DIRECTORY_SEPARATOR.'dispatch_jump.tpl', // defaultsuccessJumpcorrespondingTemplate files
    'TMPL_EXCEPTION_FILE' => THINK_PATH . 'Tpl'.DIRECTORY_SEPARATOR.'think_exception.tpl',// abnormalpageofTemplate files
    'TMPL_DETECT_THEME' => false,       // automaticDetectionTemplate Theme
    'TMPL_TEMPLATE_SUFFIX' => '.html',     // defaultTemplate filessuffix
    'TMPL_FILE_DEPR' => '/', //Template filesCONTROLLER_NAMEversusACTION_NAMEbetweenofDelimiter
    // layoutSet up
    'TMPL_ENGINE_TYPE' => 'Think',     // defaultTemplate engine With下Set upOnlyuseThinkTemplate engineeffective
    'TMPL_CACHFILE_SUFFIX' => '.php',      // defaulttemplateCachesuffix
    'TMPL_DENY_FUNC_LIST' => 'echo,exit',    // Template engineDisablefunction
    'TMPL_DENY_PHP' => false, // defaultTemplate enginewhetherDisablePHPPrimevalCode
    'TMPL_L_DELIM' => '{',            // Template engineordinarylabelStartmark
    'TMPL_R_DELIM' => '}',            // Template engineordinarylabelEndmark
    'TMPL_VAR_IDENTIFY' => 'array',     // Template variablesRecognition。Leave blankAutomatically determine the,Parameters'obj'Said objects
    'TMPL_STRIP_SPACE' => true,       // whetherRemovalTemplate filesinsidehtmlSpaces and line breaks
    'TMPL_CACHE_ON' => true,        // whetherOpentemplateCompileCache,SetfalseThen every timeAgainCompile
    'TMPL_CACHE_PREFIX' => '',         // Prefix template cacheMark, Can be changed dynamically
    'TMPL_CACHE_TIME' => 0,         // templateCache Expiration 0 Permanent,(WithdigitalThe value,unit:second)
    'TMPL_LAYOUT_ITEM' => '{__CONTENT__}', // layouttemplateContentreplaceMark
    'LAYOUT_ON' => false, // whetherEnablelayout
    'LAYOUT_NAME' => 'layout', // currentLayout name The default islayout

    // ThinkTemplate engineTag LibraryRelatedset up
    'TAGLIB_BEGIN' => '<',  // Tag LibrarylabelStartmark
    'TAGLIB_END' => '>',  // Tag LibrarylabelEndmark
    'TAGLIB_LOAD' => true, // use or notInternalTag LibraryOutside theotherTag Library,defaultautomaticDetect
    'TAGLIB_BUILD_IN' => 'cx', // InternalName tag library(labeluseNeed notDesignationName tag library),By commasSeparated noteResolveorder
    'TAGLIB_PRE_LOAD' => '',   // needadditionalloads MarkStorehouse(MustDesignationName tag library),MoreBy commasSeparated

    /* URLSet up */
    'URL_CASE_INSENSITIVE' => true,   // defaultfalse ShowURLCase sensitive trueIt meansnot case sensitive
    'URL_MODEL' => 1,       // URLAccess mode,Optional parameters0,1,2,3,Representatives of the following fourmode：
    // 0 (Normal mode); 1 (PATHINFO mode); 2 (REWRITE  mode); 3 (Compatibility Mode)  The default isPATHINFO mode
    'URL_PATHINFO_DEPR' => '/',    // PATHINFO mode ,eachparameterbetweenofseparator
    'URL_PATHINFO_FETCH' => 'ORIG_PATH_INFO,REDIRECT_PATH_INFO,REDIRECT_URL', // ForcompatiblejudgmentPATH_INFO ParametersSERVERSubstitutevariableList
    'URL_REQUEST_URI' => 'REQUEST_URI', // Get the currentpageaddressofSystem Variables The default isREQUEST_URI
    'URL_HTML_SUFFIX' => 'html',  // URLPseudo-staticsuffixSet up
    'URL_DENY_SUFFIX' => 'ico|png|gif|jpg', // URLNo AccessofsuffixSet up
    'URL_PARAMS_BIND' => true, // URLVariable is bound toActionMethod parameters
    'URL_PARAMS_BIND_TYPE' => 0, // URLType of variable bindings 0 Bind variables by name 1 Bind by variable order
    'URL_PARAMS_FILTER' => false, // URLVariable binding filter
    'URL_PARAMS_FILTER_TYPE' => '', // URLVariable bindings filtration method If it is empty transferDEFAULT_FILTER
    'URL_ROUTER_ON' => false,   // whetherOpenURLrouting
    'URL_ROUTE_RULES' => array(), // defaultroutingrule againstModule
    'URL_MAP_RULES' => array(), // URLMapping definition rules

    /* The system variable name settings */
    'VAR_MODULE' => 'm',     // defaultModuleObtainvariable
    'VAR_ADDON' => 'addon',     // defaultPlug-inControllerNamespacesvariable
    'VAR_CONTROLLER' => 'c',    // defaultControllerObtainvariable
    'VAR_ACTION' => 'a',    // defaultoperatingObtainvariable
    'VAR_AJAX_SUBMIT' => 'ajax',  // defaultofAJAXsubmitvariable
    'VAR_JSONP_HANDLER' => 'callback',
    'VAR_PATHINFO' => 's',    // Compatibility ModePATHINFOGet variables such as ?s=/module/action/id/1 The latter parameter dependsURL_PATHINFO_DEPR
    'VAR_TEMPLATE' => 't',    // defaulttemplateSwitching variable
    'VAR_AUTO_STRING' => false,    // entervariablewhetherautomaticCastforString If you turnthenArrayvariableneedManuallyIncoming variablesModifiersObtainvariable

    'HTTP_CACHE_CONTROL' => 'private',  // Network page Cache control
    'CHECK_APP_DIR' => true,       // whetheran examinationApplication Directorywhethercreate
    'FILE_UPLOAD_TYPE' => 'Local',    // fileUploadthe way
    'DATA_CRYPT_TYPE' => 'Think',    // dataencryptionthe way

);
