<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2014 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: luofei614<weibo.com/luofei614>
// +----------------------------------------------------------------------
namespace Think\Template\Driver;
/**
 * MobileTemplateTemplate engine driver
 */
class Mobile
{
    /**
     * Render the template output
     * @access public
     * @param string $templateFile Template filesname
     * @param array $var Template variables
     * @return void
     */
    public function fetch($templateFile, $var)
    {
        $templateFile = substr($templateFile, strlen(THEME_PATH));
        $var['_think_template_path'] = $templateFile;
        exit(json_encode($var));
    }
}
