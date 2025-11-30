<?php

return array(
    //MariaDB/MYSQL
	'DB_TYPE' => DB_TYPE,
    'DB_HOST' => DB_HOST,
    'DB_NAME' => DB_NAME,
    'DB_USER' => DB_USER,
    'DB_PWD' => DB_PWD,
    'DB_PORT' => DB_PORT,
    'DB_PREFIX' => 'codono_', //do not change this

	//MONGODB Optional
	'MON_HOST' => '127.0.0.1',
    'MON_NAME' => 'codono',
    'MON_USER' => 'bf487fg48',
    'MON_PWD' => 'y4875g47fg478f',
    'MON_PORT' => '27017',
    'MON_PREFIX' => 'codono_', //Experimental only	

    'ACTION_SUFFIX' => '',
	'TOKEN_ON' => false,
    'MULTI_MODULE' => true,
    'MODULE_DENY_LIST' => array('Common', 'Runtime'),
    'MODULE_ALLOW_LIST' => array('Home', 'Admin','Api'), //we have disabled mapi
    'DEFAULT_MODULE' => 'Home',
    'URL_CASE_INSENSITIVE' => false,
    'URL_MODEL' => 2,
    'URL_HTML_SUFFIX' => '', //We suggest leave it blank .. Only change it to .html when you know how to handle server rewrites
	'MINIFY' => false,
	'OUTPUT_ENCODE'=>true,
    'URL_ROUTER_ON' => true,
    'URL_ROUTE_RULES' => array(
        'Market' => 'Trade/chart',
        'launchpad' => 'Issue/index',
		'Store' => 'Shop/index',
		'Blog' => 'Article/index',
		//'Bank' => 'Invest/index',
		'Bank' => 'Home/Invest/index',
		'Admin/Bank/Index' => 'Admin/Bank/Index',
		'Admin/Bank/edit' => 'Admin/Bank/edit',
		'Admin/Bank/deleteBank' => 'Admin/Bank/deleteBank',
		'Api/Nomics/orders/snapshot' => 'Api/nomics/orders_snapshot',
		'Invite'=>'Finance/mytj'
    ),
	'LOG_TYPE'=>'File',
    'UPDATE_PATH' => '',
    'CLOUD_PATH' => '',
	'AWS_BUCKET'    => 'userserver.somedomain.com',
	'AWS_BASE_PATH' => '/Public/Upload/',
	'IS_AWS_URL'     => false,
	
    'HOST_IP' => HOST_IP,
    'TMPL_CACHFILE_SUFFIX' => '.php',
    'DATA_CACHE_TYPE' => 'File',
    'URL_PARAMS_SAFE' => true,
    'DEFAULT_FILTER' => 'check_codono,htmlspecialchars,strip_tags,trim',
    'URL_PARAMS_FILTER_TYPE' => 'check_codono,htmlspecialchars,strip_tags',
	'CODONO_EMAIL' => array(
        'SMTP_HOST' => 'sandbox.smtp.mailtrap.io', //SMTP SERVER
        'SMTP_PORT' => '2525', //PORT
        'SMTP_USER' => 'c4bfdeeb527434', //EMAIL
        'SMTP_PASS' => '88a287419f83cf', //PASSWORD
        'FROM_EMAIL' => 'testmail@someemail.com', //FROM SENDER HEADER
        'FROM_NAME' => 'BTC TESTING', //FROM NAME HEADER
        'REPLY_EMAIL' => '', //Reply email (leave blank for sender EMAIL)
        'REPLY_NAME' => '', //Reply name (Leave blank for sender name)
    ),
	
	
);