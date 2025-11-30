<?php

return array(
	'DEFAULT_V_LAYER'=>'Epsilon',//Template folder default is view
    'TMPL_PARSE_STRING' => array(
        '__UPLOAD__' => __ROOT__ . '/Upload',
        '__PUBLIC__' => __ROOT__ . '/Public',
        '__IMG__' => __ROOT__ . '/Public/' . MODULE_NAME . '/images',
        '__CSS__' => __ROOT__ . '/Public/' . MODULE_NAME . '/css',
        '__JS__' => __ROOT__ . '/Public/' . MODULE_NAME . '/js',
		'__EPSILON__' => __ROOT__ . '/Public/template/epsilon'
    ),
);
