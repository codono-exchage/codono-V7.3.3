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
namespace Behavior;

use Think\Storage;
use Think\Think;

/**
 * System behavior extension：templateResolve
 */
class ParseTemplateBehavior
{

    // Behavior extensionofExecution entrymust berun
    public function run(&$_data)
    {
        $engine = strtolower(C('TMPL_ENGINE_TYPE'));
        $_content = empty($_data['content']) ? $_data['file'] : $_data['content'];
        $_data['prefix'] = !empty($_data['prefix']) ? $_data['prefix'] : C('TMPL_CACHE_PREFIX');
        if ('think' == $engine) { // useThinkTemplate engine
            if ((!empty($_data['content']) && $this->checkContentCache($_data['content'], $_data['prefix']))
                || $this->checkCache($_data['file'], $_data['prefix'])) { // Cacheeffective
                //LoadingstencilCachefile
                Storage::load(C('CACHE_PATH') . $_data['prefix'] . md5($_content) . C('TMPL_CACHFILE_SUFFIX'), $_data['var']);
            } else {
                $tpl = Think::instance('Think\\Template');
                // CompileandLoad Templatefile
                $tpl->fetch($_content, $_data['var'], $_data['prefix']);
            }
        } else {
            // transferThird partyTemplate engineResolvewithExport
            if (strpos($engine, '\\')) {
                $class = $engine;
            } else {
                $class = 'Think\\Template\\Driver\\' . ucwords($engine);
            }
            if (class_exists($class)) {
                $tpl = new $class;
                $tpl->fetch($_content, $_data['var']);
            } else {  // classNodefinition
                E(L('_NOT_SUPPORT_') . ': ' . $class);
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
}
