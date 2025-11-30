<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2014 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: luofei614 <weibo.com/luofei614>
// +----------------------------------------------------------------------

/**
 * SAEModel convention Profile
 * ThatfilePlease do notmodify,in casewantcoverConventionConfigured value,Canapplication Configuration file Set and match practiceConfigurationitem
 * Configuration NameIn any case,systemmeetingUniteChangeLowercase
 * allConfiguration parametersCaninBecome effectiveBefore the dynamic change
 */
defined('THINK_PATH') or exit();
$st = new SaeStorage();
return array(
    //SAEFixedmysqlConfiguration
    'DB_TYPE' => 'mysql',     // databaseTypes of
    'DB_DEPLOY_TYPE' => 1,
    'DB_RW_SEPARATE' => true,
    'DB_HOST' => SAE_MYSQL_HOST_M . ',' . SAE_MYSQL_HOST_S, // serveraddress
    'DB_NAME' => SAE_MYSQL_DB,        // DatabaseName
    'DB_USER' => SAE_MYSQL_USER,    // username
    'DB_PWD' => SAE_MYSQL_PASS,         // password
    'DB_PORT' => SAE_MYSQL_PORT,        // port
    //changeTemplate substitution variables,LetordinaryAble toallplatform下display
    'TMPL_PARSE_STRING' => array(
        // __PUBLIC__/upload  -->  /Public/upload -->http://appname-public.stor.sinaapp.com/upload
        '/Public/upload' => $st->getUrl('public', 'upload')
    ),
    'LOG_TYPE' => 'Sae',
    'DATA_CACHE_TYPE' => 'Memcachesae',
    'CHECK_APP_DIR' => false,
    'FILE_UPLOAD_TYPE' => 'Sae',
);
