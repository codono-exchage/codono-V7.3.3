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
namespace Behavior;
/**
 * Language Detection And automatically loads the language pack
 */
class CheckLangBehavior
{

    // Behavior extensionofExecution entrymust berun
    public function run(&$params)
    {
        // Detect Language
        $this->checkLanguage();
    }

    /**
     * Language examination
     * an examinationBrowseDevicestand byLanguage,And automatically loads the language pack
     * @access private
     * @return void
     */
    private function checkLanguage()
    {
        // Do not open the language pack function, just load the box language file directly
        if (!C('LANG_SWITCH_ON', null, false)) {          
		  return;
        }
        $langSet = C('DEFAULT_LANG');
        $varLang = C('VAR_LANGUAGE', null, 'l');
        $langList = C('LANG_LIST', null, 'en');
        // EnabledLanguage PacksFeatures
        // according towhetherEnableautomaticDetectionSet upObtainlanguage selection
        if (C('LANG_AUTO_DETECT', null, true)) {
            if (isset($_GET[$varLang])) {
                $langSet = $_GET[$varLang];// urlSet the language variable
                cookie('think_language', $langSet, 3600);
            } elseif (cookie('think_language')) {// ObtainLast timeusers Choice
                $langSet = cookie('think_language');
            } elseif (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {// automaticDetectionBrowseLanguage
                preg_match('/^([a-z\d\-]+)/i', $_SERVER['HTTP_ACCEPT_LANGUAGE'], $matches);
                $langSet = $matches[1];
                cookie('think_language', $langSet, 3600);
            }
            if (false === stripos($langList, $langSet)) { // Illegal languageparameter
                $langSet = C('DEFAULT_LANG');
            }
        }
        // definitioncurrentLanguage
        define('LANG_SET', strtolower($langSet));

        // ReadFramework language pack
        $file = THINK_PATH . 'Lang/' . LANG_SET . '.php';
        if (LANG_SET != C('DEFAULT_LANG') && is_file($file))
            L(include $file);

        // ReadApplication of the Common Language Pack
        $file = LANG_PATH . LANG_SET . '.php';
        if (is_file($file))
            L(include $file);

        // ReadLanguage Pack module
        $file = MODULE_PATH . 'Lang/' . LANG_SET . '.php';
        if (is_file($file))
            L(include $file);

        // ReadThe current language pack controller
        $file = MODULE_PATH . 'Lang/' . LANG_SET . '/' . strtolower(CONTROLLER_NAME) . '.php';
        if (is_file($file))
            L(include $file);
    }
}
