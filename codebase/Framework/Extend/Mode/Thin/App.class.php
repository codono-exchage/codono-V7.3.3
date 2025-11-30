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
 * ThinkPHP Lite mode application classes
 */
class App
{

    /**
     * Application Initialization
     * @access public
     * @return void
     */
    static public function run()
    {

        // ObtainModulewithoperatingname
        define('MODULE_NAME', App::getModule());       // Modulename
        define('ACTION_NAME', App::getAction());        // Actionoperating

        // App running Start Time
        if (C('SHOW_RUN_TIME')) $GLOBALS['_initTime'] = microtime(TRUE);
        // carried outoperating
        R(MODULE_NAME . '/' . ACTION_NAME);
        // StorageJournalrecording
        if (C('LOG_RECORD')) Log::save();
        return;
    }

    /**
     * obtainThe actualModulename
     * @access private
     * @return string
     */
    static private function getModule()
    {
        $var = C('VAR_MODULE');
        $module = !empty($_POST[$var]) ?
            $_POST[$var] :
            (!empty($_GET[$var]) ? $_GET[$var] : C('DEFAULT_MODULE'));
        if (C('URL_CASE_INSENSITIVE')) {
            // URLAddresses are not case sensitive
            define('P_MODULE_NAME', strtolower($module));
            // Intelligent Recognitionthe way index.php/user_type/index/ Identified UserTypeAction Module
            $module = ucfirst(parse_name(strtolower($module), 1));
        }
        unset($_POST[$var], $_GET[$var]);
        return $module;
    }

    /**
     * obtainThe actualoperatingname
     * @access private
     * @return string
     */
    static private function getAction()
    {
        $var = C('VAR_ACTION');
        $action = !empty($_POST[$var]) ?
            $_POST[$var] :
            (!empty($_GET[$var]) ? $_GET[$var] : C('DEFAULT_ACTION'));
        unset($_POST[$var], $_GET[$var]);
        return $action;
    }

}

;