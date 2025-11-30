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
 * Language Detection And automatically loads the language pack
 * @category   Extend
 * @package  Extend
 * @subpackage  Behavior
 * @author   liu21st <liu21st@gmail.com>
 */
class CheckLangBehavior extends Behavior
{
    // Behavioral parametersdefinition(Defaults) canIn the projectConfigurationincover
    protected $options = array(
        'LANG_SWITCH_ON' => false,   // defaultshut downLanguage PacksFeatures
        'LANG_AUTO_DETECT' => true,   // Automatically detect language Open multi-language FeaturesAfter the effective
        'LANG_LIST' => 'en,nl', // Allow to switch the language list Separated by commas
        'VAR_LANGUAGE' => 'l',        // default Language Switching variable
    );

    // Behavior extensionofExecution entrymust berun
    public function run(&$params)
    {
        // OpenStatic stateCache
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
        if (!C('LANG_SWITCH_ON')) {
			return;
        }
        $langSet = C('DEFAULT_LANG');
        // EnabledLanguage PacksFeatures
        // according towhetherEnableautomaticDetectionSet upObtainlanguage selection
        if (C('LANG_AUTO_DETECT')) {
            if (isset($_GET[C('VAR_LANGUAGE')])) {
                $langSet = $_GET[C('VAR_LANGUAGE')];// urlSet the language variable
                cookie('think_language', $langSet, 3600);
            } elseif (cookie('think_language')) {// ObtainLast timeusers Choice
                $langSet = cookie('think_language');
            } elseif (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {// automaticDetectionBrowseLanguage
                preg_match('/^([a-z\d\-]+)/i', $_SERVER['HTTP_ACCEPT_LANGUAGE'], $matches);
                $langSet = $matches[1];
                cookie('think_language', $langSet, 3600);
            }
            if (false === stripos(C('LANG_LIST'), $langSet)) { // Illegal languageparameter
                $langSet = C('DEFAULT_LANG');
            }
        }
        // definitioncurrentLanguage
        define('LANG_SET', strtolower($langSet));

        $group = '';
        $path = (defined('GROUP_NAME') && C('APP_GROUP_MODE') == 1) ? BASE_LIB_PATH . 'Lang/' . LANG_SET . '/' : LANG_PATH . LANG_SET . '/';
        // ReadProject Common Language Pack
        if (is_file(LANG_PATH . LANG_SET . '/common.php'))
            L(include LANG_PATH . LANG_SET . '/common.php');
        // ReadPacketCommon Language Pack
        if (defined('GROUP_NAME')) {
            if (C('APP_GROUP_MODE') == 1) { // independentPacket
                $file = $path . 'common.php';
            } else { // Ordinary packet
                $file = $path . GROUP_NAME . '.php';
                $group = GROUP_NAME . C('TMPL_FILE_DEPR');
            }
            if (is_file($file))
                L(include $file);
        }
        // ReadThe current language pack module
        if (is_file($path . $group . strtolower(MODULE_NAME) . '.php'))
            L(include $path . $group . strtolower(MODULE_NAME) . '.php');
    }
}
