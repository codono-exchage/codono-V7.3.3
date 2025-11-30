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
 * ThinkPHP Command Mode application class
 */
class App
{

    /**
     * Execution of the application
     * @access public
     * @return void
     */
    static public function run()
    {

        if (C('URL_MODEL') == 1) {// PATHINFO modeURLthe following use index.php module/action/id/4
            $depr = C('URL_PATHINFO_DEPR');
            $path = isset($_SERVER['argv'][1]) ? $_SERVER['argv'][1] : '';
            if (!empty($path)) {
                $params = explode($depr, trim($path, $depr));
            }
            // ObtainModulewithoperatingname
            define('MODULE_NAME', !empty($params) ? array_shift($params) : C('DEFAULT_MODULE'));
            define('ACTION_NAME', !empty($params) ? array_shift($params) : C('DEFAULT_ACTION'));
            if (count($params) > 1) {
                // Resolve剩余parameter anduseGETthe wayObtain
                preg_replace('@(\w+),([^,\/]+)@e', '$_GET[\'\\1\']="\\2";', implode(',', $params));
            }
        } else {// defaultURLmode use index.php module action id 4
            // ObtainModulewithoperatingname
            define('MODULE_NAME', isset($_SERVER['argv'][1]) ? $_SERVER['argv'][1] : C('DEFAULT_MODULE'));
            define('ACTION_NAME', isset($_SERVER['argv'][2]) ? $_SERVER['argv'][2] : C('DEFAULT_ACTION'));
            if ($_SERVER['argc'] > 3) {
                // Resolve剩余parameter anduseGETthe wayObtain
                preg_replace('@(\w+),([^,\/]+)@e', '$_GET[\'\\1\']="\\2";', implode(',', array_slice($_SERVER['argv'], 3)));
            }
        }

        // carried outoperating
        $module = A(MODULE_NAME);
        if (!$module) {
            // whetherdefinitionEmptyModule
            $module = A("Empty");
            if (!$module) {
                // Moduledoes not exist Throw an exception
                throw_exception(L('_MODULE_NOT_EXIST_') . MODULE_NAME);
            }
        }
        call_user_func(array(&$module, ACTION_NAME));
        // StorageJournalrecording
        if (C('LOG_RECORD')) Log::save();
        return;
    }

}

;