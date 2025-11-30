<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2012 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: support@codono.com
// +----------------------------------------------------------------------

defined('THINK_PATH') or exit();

/**
 * EaseTemplateTemplate engine driver
 * @category   Extend
 * @package  Extend
 * @subpackage  Driver.Template
 * @author    liu21st <liu21st@gmail.com>
 */
class TemplateEase
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
        $templateFile = substr($templateFile, strlen(THEME_PATH), -5);
        $CacheDir = substr(CACHE_PATH, 0, -1);
        $TemplateDir = substr(THEME_PATH, 0, -1);
        vendor('EaseTemplate.template#ease');
        $config = array(
            'CacheDir' => $CacheDir,
            'TemplateDir' => $TemplateDir,
            'TplType' => 'html'
        );
        if (C('TMPL_ENGINE_CONFIG')) {
            $config = array_merge($config, C('TMPL_ENGINE_CONFIG'));
        }
        $tpl = new EaseTemplate($config);
        $tpl->set_var($var);
        $tpl->set_file($templateFile);
        $tpl->p();
    }
}