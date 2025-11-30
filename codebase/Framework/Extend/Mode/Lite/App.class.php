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
 * ThinkPHP Application class Lite mode
 * @category   Think
 * @package  Think
 * @subpackage  Core
 * @author    liu21st <liu21st@gmail.com>
 */
class App
{

    /**
     * Run application examples EntrancefileuseofShortcutmethod
     * @access public
     * @return void
     */
    static public function run()
    {
        // Set upsystemTime zone
        date_default_timezone_set(C('DEFAULT_TIMEZONE'));
        // loaddynamicprojectpublicfilewithConfiguration
        load_ext_file();
        // projectinitializationlabel
        tag('app_init');
        // URLDispatch
        Dispatcher::dispatch();
        // projectStart tag
        tag('app_begin');
        // Session initialization Support for other clients
        if (isset($_REQUEST[C("VAR_SESSION_ID")]))
            session_id($_REQUEST[C("VAR_SESSION_ID")]);
        if (C('SESSION_AUTO_START')) session_start();
        // App running Start Time
        if (C('SHOW_RUN_TIME')) G('initTime');
        App::exec();
        // projectEnd tag
        tag('app_end');
        // StorageJournalrecording
        if (C('LOG_RECORD')) Log::save();
        return;
    }

    /**
     * Execution of the application
     * @access public
     * @return void
     * @throws ThinkExecption
     */
    static public function exec()
    {
        // SafetyDetect
        if (!preg_match('/^[A-Za-z_0-9]+$/', MODULE_NAME)) {
            throw_exception(L('_MODULE_NOT_EXIST_'));
        }
        //createActionControllerExamples
        $group = defined('GROUP_NAME') ? GROUP_NAME . '/' : '';
        $module = A($group . MODULE_NAME);
        if (!$module) {
            // whetherdefinitionEmptyModule
            $module = A("Empty");
            if (!$module)
                // Moduledoes not exist Throw an exception
                throw_exception(L('_MODULE_NOT_EXIST_') . MODULE_NAME);
        }
        //carried outcurrentoperating
        call_user_func(array(&$module, ACTION_NAME));
        return;
    }
}