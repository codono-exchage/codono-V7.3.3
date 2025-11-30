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
namespace Think;
/**
 * ThinkPHP View class
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
        // ResolveandObtainTemplate content
        $content = $this->fetch($templateFile, $content, $prefix);
        // Output template content
        $this->render($content, $charset, $contentType);
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
            if (!is_file($templateFile)) E(L('_TEMPLATE_NOT_EXIST_') . ':' . $templateFile);
        } else {
            defined('THEME_PATH') or define('THEME_PATH', $this->getThemePath());
        }
        // pageCache
        ob_start();
        ob_implicit_flush(0);
        if ('php' == strtolower(C('TMPL_ENGINE_TYPE'))) { // usePHPPrimeval Moban
            $_content = $content;
            // templateArrayvariableBroken down intoforindependentvariable
            extract($this->tVar, EXTR_OVERWRITE);
            // directLoadingPHPtemplate
            empty($_content) ? include $templateFile : eval('?>' . $_content);
        } else {
            // viewResolvelabel
            $params = array('var' => $this->tVar, 'file' => $templateFile, 'content' => $content, 'prefix' => $prefix);
            $_content = !empty($content) ?: $templateFile;
            if ((!empty($content) && $this->checkContentCache($content, $prefix)) || $this->checkCache($templateFile, $prefix)) { // Cacheeffective
                //LoadingstencilCachefile
                Storage::load(C('CACHE_PATH') . $prefix . md5($_content) . C('TMPL_CACHFILE_SUFFIX'), $this->tVar);
            } else {
                $tpl = Think::instance('Think\\Template');
                // CompileandLoad Templatefile
                $tpl->fetch($_content, $this->tVar, $prefix);
            }
        }
        // ObtainandClearCache
        $content = ob_get_clean();
        // contentfilterlabel
        // systemdefaultofspecialvariablereplace
        $replace = array(
            '__ROOT__' => __ROOT__,       // currentwebsiteaddress
            '__APP__' => __APP__,        // currentapplicationaddress
            '__MODULE__' => __MODULE__,
            '__ACTION__' => __ACTION__,     // currentoperatingaddress
            '__SELF__' => __SELF__,       // currentpageaddress
            '__CONTROLLER__' => __CONTROLLER__,
            '__URL__' => __CONTROLLER__,
            '__PUBLIC__' => __ROOT__ . '/Public',// Sitespublictable of Contents
        );
        // allowuserfromdefinitiontemplateofStringreplace
        if (is_array(C('TMPL_PARSE_STRING')))
            $replace = array_merge($replace, C('TMPL_PARSE_STRING'));
        $content = str_replace(array_keys($replace), array_values($replace), $content);
        // ExportTemplate files
        return $content;
    }

    /**
     * an examinationCacheThe file is valid
     * If there is noThe effectneedAgainCompile
     * @access public
     * @param string $tmplTemplateFile Template filesname
     * @return boolean
     */
    protected function checkCache($tmplTemplateFile, $prefix = '')
    {
        if (!C('TMPL_CACHE_ON')) // priorityCorrectConfigurationset upDetect
            return false;
        $tmplCacheFile = C('CACHE_PATH') . $prefix . md5($tmplTemplateFile) . C('TMPL_CACHFILE_SUFFIX');
        if (!Storage::has($tmplCacheFile)) {
            return false;
        } elseif (filemtime($tmplTemplateFile) > Storage::get($tmplCacheFile, 'mtime')) {
            // Template filesIf there isUpdatethenCacheneedUpdate
            return false;
        } elseif (C('TMPL_CACHE_TIME') != 0 && time() > Storage::get($tmplCacheFile, 'mtime') + C('TMPL_CACHE_TIME')) {
            // CachewhetherinValidity
            return false;
        }
        // Openlayouttemplate
        if (C('LAYOUT_ON')) {
            $layoutFile = THEME_PATH . C('LAYOUT_NAME') . C('TMPL_TEMPLATE_SUFFIX');
            if (filemtime($layoutFile) > Storage::get($tmplCacheFile, 'mtime')) {
                return false;
            }
        }
        // Cacheeffective
        return true;
    }

    /**
     * an examinationCachecontentwhethereffective
     * If there is noThe effectneedAgainCompile
     * @access public
     * @param string $tmplContent Template content
     * @return boolean
     */
    protected function checkContentCache($tmplContent, $prefix = '')
    {
        if (Storage::has(C('CACHE_PATH') . $prefix . md5($tmplContent) . C('TMPL_CACHFILE_SUFFIX'))) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Automatic positioning template file
     * @access protected
     * @param string $template Template File Rules
     * @return string
     */
    public function parseTemplate($template = '')
    {
        if (is_file($template)) {
            return $template;
        }
        $depr = C('TMPL_FILE_DEPR');
        $template = str_replace(':', $depr, $template);

        // Get the currentModule
        $module = MODULE_NAME;
        if (strpos($template, '@')) { // CrossModuletransferstencilfile
            list($module, $template) = explode('@', $template);
        }
        // Get the currentthemeofstencilpath
        defined('THEME_PATH') or define('THEME_PATH', $this->getThemePath($module));

        // analysisTemplate File Rules
        if ('' == $template) {
            // in caseTemplate filesnameforair according todefaultruleLocate
            $template = CONTROLLER_NAME . $depr . ACTION_NAME;
        } elseif (false === strpos($template, $depr)) {
            $template = CONTROLLER_NAME . $depr . $template;
        }
        $file = THEME_PATH . $template . C('TMPL_TEMPLATE_SUFFIX');
        if (C('TMPL_LOAD_DEFAULTTHEME') && THEME_NAME != C('DEFAULT_THEME') && !is_file($file)) {
            // Can not findcurrentthemetemplateofWhen positioningdefaultthememiddletemplate
            $file = dirname(THEME_PATH) . '/' . C('DEFAULT_THEME') . '/' . $template . C('TMPL_TEMPLATE_SUFFIX');
        }
        return $file;
    }

    /**
     * Get the currentoftemplatepath
     * @access protected
     * @param  string $module modulname
     * @return string
     */
    protected function getThemePath($module = MODULE_NAME)
    {
        // Get the currentTheme Name
        $theme = $this->getTemplateTheme();
        // Get the currentthemeofstencilpath
        $tmplPath = C('VIEW_PATH'); // ModuleSet upindependentofviewtable of Contents
        if (!$tmplPath) {
            // definitionTMPL_PATH The changeOverall situationofviewDirectory toModuleOutside the
            $tmplPath = defined('TMPL_PATH') ? TMPL_PATH . $module . '/' : APP_PATH . $module . '/' . C('DEFAULT_V_LAYER') . '/';
        }
        return $tmplPath . $theme;
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
        defined('THEME_NAME') || define('THEME_NAME', $theme);                  // currentTemplate Themename
        return $theme ? $theme . '/' : '';
    }

}