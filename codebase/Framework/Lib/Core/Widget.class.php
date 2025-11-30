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

/**
 * ThinkPHP Widgetclass Abstract class
 * @category   Think
 * @package  Think
 * @subpackage  Core
 * @author liu21st <liu21st@gmail.com>
 */
abstract class Widget
{

    // Use template engine EachWidgetYou can separateConfigurationNot subjectsysteminfluences
    protected $template = '';

    /**
     * Render Output renderthe way isWidgetThe only interface
     * Use string is returned There can be no output
     * @access public
     * @param mixed $data To render data
     * @return string
     */
    abstract public function render($data);

    /**
     * Render the template output forrenderInternal method calls
     * @access public
     * @param string $templateFile Template files
     * @param mixed $var Template variables
     * @return string
     */
    protected function renderFile($templateFile = '', $var = '')
    {
        ob_start();
        ob_implicit_flush(0);
        if (!file_exists_case($templateFile)) {
            // Automatic positioning template file
            $name = substr(get_class($this), 0, -6);
            $filename = empty($templateFile) ? $name : $templateFile;
            $templateFile = BASE_LIB_PATH . 'Widget/' . $name . '/' . $filename . C('TMPL_TEMPLATE_SUFFIX');
            if (!file_exists_case($templateFile))
                throw_exception(L('_TEMPLATE_NOT_EXIST_') . '[' . $templateFile . ']');
        }
        $template = strtolower($this->template ? $this->template : (C('TMPL_ENGINE_TYPE') ? C('TMPL_ENGINE_TYPE') : 'php'));
        if ('php' == $template) {
            // usePHPtemplate
            if (!empty($var)) extract($var, EXTR_OVERWRITE);
            // directLoadingPHPtemplate
            include $templateFile;
        } elseif ('think' == $template) { // useThinkTemplate engine
            if ($this->checkCache($templateFile)) { // Cacheeffective
                // break downvariableAnd loadtemplateCache
                extract($var, EXTR_OVERWRITE);
                //LoadingstencilCachefile
                include C('CACHE_PATH') . md5($templateFile) . C('TMPL_CACHFILE_SUFFIX');
            } else {
                $tpl = Think::instance('ThinkTemplate');
                // CompileandLoad Templatefile
                $tpl->fetch($templateFile, $var);
            }
        } else {
            $class = 'Template' . ucwords($template);
            if (is_file(CORE_PATH . 'Driver/Template/' . $class . '.class.php')) {
                // Internal Drive
                $path = CORE_PATH;
            } else { // Extended drive
                $path = EXTEND_PATH;
            }
            require_cache($path . 'Driver/Template/' . $class . '.class.php');
            $tpl = new $class;
            $tpl->fetch($templateFile, $var);
        }
        $content = ob_get_clean();
        return $content;
    }

    /**
     * an examinationCacheThe file is valid
     * If there is noThe effect need Again Compile
     * @access public
     * @param string $tmplTemplateFile Template filesname
     * @return boolean
     */
    protected function checkCache($tmplTemplateFile)
    {
        if (!C('TMPL_CACHE_ON')) // priorityCorrectConfigurationset upDetect
            return false;
        $tmplCacheFile = C('CACHE_PATH') . md5($tmplTemplateFile) . C('TMPL_CACHFILE_SUFFIX');
        if (!is_file($tmplCacheFile)) {
            return false;
        } elseif (filemtime($tmplTemplateFile) > filemtime($tmplCacheFile)) {
            // Template filesIf there isUpdatethenCacheneedUpdate
            return false;
        } elseif (C('TMPL_CACHE_TIME') != 0 && time() > filemtime($tmplCacheFile) + C('TMPL_CACHE_TIME')) {
            // CachewhetherinValidity
            return false;
        }
        // Cacheeffective
        return true;
    }
}