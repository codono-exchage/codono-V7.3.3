<?php
// +----------------------------------------------------------------------
// | TOPThink [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2011 http://topthink.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: support@codono.com
// +----------------------------------------------------------------------
// $Id: config.php 2668 2012-01-26 13:07:16Z liu21st $

return array(
    'REST_METHOD_LIST' => 'get,post,put,delete', // allowofrequestTypes ofList
    'REST_DEFAULT_METHOD' => 'get', // The default request type
    'REST_CONTENT_TYPE_LIST' => 'html,xml,json,rss', // RESTallowRequestedResourcesTypes ofList
    'REST_DEFAULT_TYPE' => 'html', // defaultofResourcesTypes of
    'REST_OUTPUT_TYPE' => array(  // RESTallowExportofResourcesTypes ofList
        'xml' => 'application/xml',
        'json' => 'application/json',
        'html' => 'text/html',
    ),
);