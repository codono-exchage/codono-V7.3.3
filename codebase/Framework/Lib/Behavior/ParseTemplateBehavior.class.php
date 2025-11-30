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
 * System behavior extension：templateResolve
 * @category   Think
 * @package  Think
 * @subpackage  Behavior
 * @author   liu21st <liu21st@gmail.com>
 */
class ParseTemplateBehavior extends Behavior
{
    // Behavioral parametersdefinition(Defaults) canIn the projectConfigurationincover
    protected $options = array(
        // layoutSet up
        'TMPL_ENGINE_TYPE' => 'Think',     // defaultTemplate engine With下Set upOnlyuseThinkTemplate engineeffective
        'TMPL_CACHFILE_SUFFIX' => '.php',      // defaulttemplateCachesuffix
        'TMPL_DENY_FUNC_LIST' => 'echo,exit',    // Template engineDisablefunction
        'TMPL_DENY_PHP' => false, // defaultTemplate enginewhetherDisablePHPPrimevalCode
        'TMPL_L_DELIM' => '{',            // Template engineordinarylabelStartmark
        'TMPL_R_DELIM' => '}',            // Template engineordinarylabelEndmark
        'TMPL_VAR_IDENTIFY' => 'array',     // Template variablesRecognition。Leave blankAutomatically determine the,Parameters'obj'Said objects
        'TMPL_STRIP_SPACE' => true,       // whetherRemovalTemplate filesinsidehtmlSpaces and line breaks
        'TMPL_CACHE_ON' => true,        // whetherOpentemplateCompileCache,SetfalseThen every timeAgainCompile
        'TMPL_CACHE_PREFIX' => '',         // Prefix template cacheMark, Can be changed dynamically
        'TMPL_CACHE_TIME' => 0,         // templateCache Expiration 0 Permanent,(WithdigitalThe value,unit:second)
        'TMPL_LAYOUT_ITEM' => '{__CONTENT__}', // layouttemplateContentreplaceMark
        'LAYOUT_ON' => false, // whetherEnablelayout
        'LAYOUT_NAME' => 'layout', // currentLayout name The default islayout

        // ThinkTemplate engineTag LibraryRelatedset up
        'TAGLIB_BEGIN' => '<',  // Tag LibrarylabelStartmark
        'TAGLIB_END' => '>',  // Tag LibrarylabelEndmark
        'TAGLIB_LOAD' => true, // use or notInternalTag LibraryOutside theotherTag Library,defaultautomaticDetect
        'TAGLIB_BUILD_IN' => 'cx', // InternalName tag library(labeluseNeed notDesignationName tag library),By commasSeparated noteResolveorder
        'TAGLIB_PRE_LOAD' => '',   // needadditionalloads MarkStorehouse(MustDesignationName tag library),MoreBy commasSeparated
    );

    // Behavior extensionofExecution entrymust berun
    public function run(&$_data)
    {
        $engine = strtolower(C('TMPL_ENGINE_TYPE'));
        $_content = empty($_data['content']) ? $_data['file'] : $_data['content'];
        $_data['prefix'] = !empty($_data['prefix']) ? $_data['prefix'] : C('TMPL_CACHE_PREFIX');
        if ('think' == $engine) { // useThinkTemplate engine
            if ((!empty($_data['content']) && $this->checkContentCache($_data['content'], $_data['prefix']))
                || $this->checkCache($_data['file'], $_data['prefix'])) { // Cacheeffective
                // break downvariableAnd loadtemplateCache
                extract($_data['var'], EXTR_OVERWRITE);
                //LoadingstencilCachefile
                include C('CACHE_PATH') . $_data['prefix'] . md5($_content) . C('TMPL_CACHFILE_SUFFIX');
            } else {
                $tpl = Think::instance('ThinkTemplate');
                // CompileandLoad Templatefile
                $tpl->fetch($_content, $_data['var'], $_data['prefix']);
            }
        } else {
            // transferThird partyTemplate engineResolvewithExport
            $class = 'Template' . ucwords($engine);
            if (class_exists($class)) {
                $tpl = new $class;
                $tpl->fetch($_content, $_data['var']);
            } else {  // classNodefinition
                throw_exception(L('_NOT_SUPPERT_') . ': ' . $class);
            }
        }
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
        if (!is_file($tmplCacheFile)) {
            return false;
        } elseif (filemtime($tmplTemplateFile) > filemtime($tmplCacheFile)) {
            // Template filesIf there isUpdatethenCacheneedUpdate
            return false;
        } elseif (C('TMPL_CACHE_TIME') != 0 && time() > filemtime($tmplCacheFile) + C('TMPL_CACHE_TIME')) {
            // CachewhetherinValidity
            return false;
        }
        // Openlayouttemplate
        if (C('LAYOUT_ON')) {
            $layoutFile = THEME_PATH . C('LAYOUT_NAME') . C('TMPL_TEMPLATE_SUFFIX');
            if (filemtime($layoutFile) > filemtime($tmplCacheFile)) {
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
        if (is_file(C('CACHE_PATH') . $prefix . md5($tmplContent) . C('TMPL_CACHFILE_SUFFIX'))) {
            return true;
        } else {
            return false;
        }
    }
}