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
 * System behavior extension：Template contentExportreplace
 * @category   Think
 * @package  Think
 * @subpackage  Behavior
 * @author   liu21st <liu21st@gmail.com>
 */
class ContentReplaceBehavior extends Behavior
{
    // Behavioral parametersdefinition
    protected $options = array(
        'TMPL_PARSE_STRING' => array(),
    );

    // Behavior extensionofExecution entrymust berun
    public function run(&$content)
    {
        $content = $this->templateContentReplace($content);
    }

    /**
     * Replace template content
     * @access protected
     * @param string $content Template content
     * @return string
     */
    protected function templateContentReplace($content)
    {
        // systemdefaultofspecialvariablereplace
        $replace = array(
            '__TMPL__' => APP_TMPL_PATH,  // projecttemplatetable of Contents
            '__ROOT__' => __ROOT__,       // currentwebsiteaddress
            '__APP__' => __APP__,        // currentprojectaddress
            '__GROUP__' => defined('GROUP_NAME') ? __GROUP__ : __APP__,
            '__ACTION__' => __ACTION__,     // currentoperatingaddress
            '__SELF__' => __SELF__,       // currentpageaddress
            '__URL__' => __URL__,
            '../Public' => APP_TMPL_PATH . 'Public',// Public project template directory
            '__PUBLIC__' => __ROOT__ . '/Public',// Sitespublictable of Contents
        );
        // allowuserfromdefinitiontemplateofStringreplace
        if (is_array(C('TMPL_PARSE_STRING')))
            $replace = array_merge($replace, C('TMPL_PARSE_STRING'));
        $content = str_replace(array_keys($replace), array_values($replace), $content);
        return $content;
    }

}