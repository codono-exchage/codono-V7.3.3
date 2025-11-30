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
 * SmartTemplate engine driver
 */
class Smart
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
        vendor('SmartTemplate.class#smarttemplate');
        $tpl = new \SmartTemplate($templateFile);
        $tpl->caching = C('TMPL_CACHE_ON');
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
        $tpl->output();
    }
}