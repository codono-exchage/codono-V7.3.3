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
/**
 * System behavior extension：Template contentExportreplace
 */
class ContentReplaceBehavior
{

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
            '__ROOT__' => __ROOT__,       // currentwebsiteaddress
            '__APP__' => __APP__,        // currentapplicationaddress
            '__MODULE__' => __MODULE__,
            '__ACTION__' => __ACTION__,     // currentoperatingaddress
            '__SELF__' => htmlentities(__SELF__),       // currentpageaddress
            '__CONTROLLER__' => __CONTROLLER__,
            '__URL__' => __CONTROLLER__,
            '__PUBLIC__' => __ROOT__ . '/Public',// Sitespublictable of Contents
        );
        // allowuserfromdefinitiontemplateofStringreplace
        if (is_array(C('TMPL_PARSE_STRING')))
            $replace = array_merge($replace, C('TMPL_PARSE_STRING'));
        $content = str_replace(array_keys($replace), array_values($replace), $content);
        return $content;
    }

}