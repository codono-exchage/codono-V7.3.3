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
namespace Think\Template\Driver;
/**
 * TemplateLiteTemplate engine driver
 */
class Lite
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
        vendor("TemplateLite.class#template");
        $templateFile = substr($templateFile, strlen(THEME_PATH));
        $tpl = new \Template_Lite();
        $tpl->template_dir = THEME_PATH;
        $tpl->compile_dir = CACHE_PATH;
        $tpl->cache_dir = TEMP_PATH;
        if (C('TMPL_ENGINE_CONFIG')) {
            $config = C('TMPL_ENGINE_CONFIG');
            foreach ($config as $key => $val) {
                $tpl->{$key} = $val;
            }
        }
        $tpl->assign($var);
        $tpl->display($templateFile);
    }
}