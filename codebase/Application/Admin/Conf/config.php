<?php

return array(
	'DEFAULT_LANG' => 'en', // 

    // Template parse strings configuration
    'TMPL_PARSE_STRING' => array(
        // Placeholder for the upload path
        '__UPLOAD__' => __ROOT__ . '/Upload', 
        
        // Placeholder for the public path
        '__PUBLIC__' => __ROOT__ . '/Public', 
        
        // Placeholder for the image path specific to the module
        '__IMG__' => __ROOT__ . '/Public/' . MODULE_NAME . '/images', 
        
        // Placeholder for the CSS path specific to the module
        '__CSS__' => __ROOT__ . '/Public/' . MODULE_NAME . '/css', 
        
        // Placeholder for the JavaScript path specific to the module
        '__JS__' => __ROOT__ . '/Public/' . MODULE_NAME . '/js',
		
		'__THEME__' => __ROOT__ . '/Public/template/manager'
		
    )
);
