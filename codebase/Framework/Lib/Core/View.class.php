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
 * ThinkPHP View class
 * @category   Think
 * @package  Think
 * @subpackage  Core
 * @author liu21st <liu21st@gmail.com>
 */
class View
{
    /**
     * Template output variables
     * @var tVar
     * @access protected
     */
    protected $tVar = array();

    /**
     * Template Theme
     * @var theme
     * @access protected
     */
    protected $theme = '';

    /**
     * Template variable assignment
     * @access public
     * @param mixed $name
     * @param mixed $value
     */
    public function assign($name, $value = '')
    {
        if (is_array($name)) {
            $this->tVar = array_merge($this->tVar, $name);
        } else {
            $this->tVar[$name] = $value;
        }
    }

    /**
     * Get the value of a template variable
     * @access public
     * @param string $name
     * @return mixed
     */
    public function get($name = '')
    {
        if ('' === $name) {
            return $this->tVar;
        }
        return isset($this->tVar[$name]) ? $this->tVar[$name] : false;
    }

    /**
     * Load TemplatewithpageExport Content can return output
     * @access public
     * @param string $templateFile Template filesname
     * @param string $charset Template output character set
     * @param string $contentType Output Type
     * @param string $content Template output content
     * @param string $prefix Prefix template cache
     * @return mixed
     */
    public function display($templateFile = '', $charset = '', $contentType = '', $content = '', $prefix = '')
    {
        G('viewStartTime');
        // viewStart tag
        tag('view_begin', $templateFile);
        // ResolveandObtainTemplate content
        $content = $this->fetch($templateFile, $content, $prefix);
        // Output template content
        $this->render($content, $charset, $contentType);
        // viewEnd tag
        tag('view_end');
    }

    /**
     * Output contentText canincludeHtml
     * @access private
     * @param string $content Output content
     * @param string $charset Template output character set
     * @param string $contentType Output Type
     * @return mixed
     */
    private function render($content, $charset = '', $contentType = '')
    {
        if (empty($charset)) $charset = C('DEFAULT_CHARSET');
        if (empty($contentType)) $contentType = C('TMPL_CONTENT_TYPE');
        // network charactercoding
        header('Content-Type:' . $contentType . '; charset=' . $charset);
        header('Cache-control: ' . C('HTTP_CACHE_CONTROL'));  // pageCachecontrol
        header('X-Powered-By:Codono');
        // ExportTemplate files
        echo $content;
    }

    /**
     * ResolvewithObtainTemplate content For output
     * @access public
     * @param string $templateFile Template filesname
     * @param string $content Template output content
     * @param string $prefix Prefix template cache
     * @return string
     */
    public function fetch($templateFile = '', $content = '', $prefix = '')
    {
        if (empty($content)) {
            $templateFile = $this->parseTemplate($templateFile);
            // Template filesdoes not existDirect return
            if (!is_file($templateFile))
                throw_exception(L('_TEMPLATE_NOT_EXIST_') . '[' . $templateFile . ']');
        }
        // pageCache
        ob_start();
        ob_implicit_flush(0);
        if ('php' == strtolower(C('TMPL_ENGINE_TYPE'))) { // usePHPPrimeval Moban
            // templateArrayvariableBroken down intoforindependentvariable
            extract($this->tVar, EXTR_OVERWRITE);
            // directLoadingPHPtemplate
            empty($content) ? include $templateFile : eval('?>' . $content);
        } else {
            // viewResolvelabel
            $params = array('var' => $this->tVar, 'file' => $templateFile, 'content' => $content, 'prefix' => $prefix);
            tag('view_parse', $params);
        }
        // ObtainandClearCache
        $content = ob_get_clean();
        // contentfilterlabel
        tag('view_filter', $content);
        // ExportTemplate files
        return $content;
    }

    /**
     * Automatic positioning template file
     * @access protected
     * @param string $template Template File Rules
     * @return string
     */
    public function parseTemplate($template = '')
    {
        $app_name = APP_NAME == basename(dirname($_SERVER['SCRIPT_FILENAME'])) && '' == __APP__ ? '' : APP_NAME . '/';
        if (is_file($template)) {
            $group = defined('GROUP_NAME') ? GROUP_NAME . '/' : '';
            $theme = C('DEFAULT_THEME');
            // Get the currentthemeofstencilpath
            if (1 == C('APP_GROUP_MODE')) { // independentPacketmode
                define('THEME_PATH', dirname(BASE_LIB_PATH) . '/' . $group . basename(TMPL_PATH) . '/' . $theme);
                define('APP_TMPL_PATH', __ROOT__ . '/' . $app_name . C('APP_GROUP_PATH') . '/' . $group . basename(TMPL_PATH) . '/' . $theme);
            } else {
                define('THEME_PATH', TMPL_PATH . $group . $theme);
                define('APP_TMPL_PATH', __ROOT__ . '/' . $app_name . basename(TMPL_PATH) . '/' . $group . $theme);
            }
            return $template;
        }
        $depr = C('TMPL_FILE_DEPR');
        $template = str_replace(':', $depr, $template);
        // Get the currentTheme Name
        $theme = $this->getTemplateTheme();
        // Get the current template group
        $group = defined('GROUP_NAME') ? GROUP_NAME . '/' : '';
        if (defined('GROUP_NAME') && strpos($template, '@')) { // CrossPackettransferstencilfile
            list($group, $template) = explode('@', $template);
            $group .= '/';
        }
        // Get the currentthemeofstencilpath
        if (1 == C('APP_GROUP_MODE')) { // independentPacketmode
            define('THEME_PATH', dirname(BASE_LIB_PATH) . '/' . $group . basename(TMPL_PATH) . '/' . $theme);
            define('APP_TMPL_PATH', __ROOT__ . '/' . $app_name . C('APP_GROUP_PATH') . '/' . $group . basename(TMPL_PATH) . '/' . $theme);
        } else {
            define('THEME_PATH', TMPL_PATH . $group . $theme);
            define('APP_TMPL_PATH', __ROOT__ . '/' . $app_name . basename(TMPL_PATH) . '/' . $group . $theme);
        }

        // analysisTemplate File Rules
        if ('' == $template) {
            // in caseTemplate filesnameforair according todefaultruleLocate
            $template = MODULE_NAME . $depr . ACTION_NAME;
        } elseif (false === strpos($template, '/')) {
            $template = MODULE_NAME . $depr . $template;
        }
        return THEME_PATH . $template . C('TMPL_TEMPLATE_SUFFIX');
    }

    /**
     * Set upcurrentExportofTemplate Theme
     * @access public
     * @param  mixed $theme Theme Name
     * @return View
     */
    public function theme($theme)
    {
        $this->theme = $theme;
        return $this;
    }

    /**
     * Get the currentofTemplate Theme
     * @access private
     * @return string
     */
    private function getTemplateTheme()
    {
        if ($this->theme) { // DesignationTemplate Theme
            $theme = $this->theme;
        } else {
            /* Gets the name of the template theme */
            $theme = C('DEFAULT_THEME');
            if (C('TMPL_DETECT_THEME')) {// automaticDetectionTemplate Theme
                $t = C('VAR_TEMPLATE');
                if (isset($_GET[$t])) {
                    $theme = $_GET[$t];
                } elseif (cookie('think_template')) {
                    $theme = cookie('think_template');
                }
                if (!in_array($theme, explode(',', C('THEME_LIST')))) {
                    $theme = C('DEFAULT_THEME');
                }
                cookie('think_template', $theme, 864000);
            }
        }
        define('THEME_NAME', $theme);                  // currentTemplate Themename
        return $theme ? $theme . '/' : '';
    }

}
