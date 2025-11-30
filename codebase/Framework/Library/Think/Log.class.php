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
 * Log Processing category
 */
class Log
{

    // Log Level From Up to down,From low to high
    const EMERG = 'EMERG';  // seriouserror: resulting insystemUnable to collapseuse
    const ALERT = 'ALERT';  // Cautionaryerror: have toIt was immediatelymodifyoferror
    const CRIT = 'CRIT';  // The critical valueerror: Exceeds a critical valueoferror,E.gone day24hour,and enterofYes25hoursuch
    const ERR = 'ERR';  // generalerror: generalerror
    const WARN = 'WARN';  // Warningerror: needwarningoferror
    const NOTICE = 'NOTIC';  // Notice: Program canrunBut alsonot enoughperfectoferror
    const INFO = 'INFO';  // information: programExportinformation
    const DEBUG = 'DEBUG';  // debugging: debugginginformation
    const SQL = 'SQL';  // SQL：SQLStatement noteOnly in debuggingmodeOpenValid

    // Log information
    static protected $log = array();

    // Log storage
    static protected $storage = null;

    // INIT Starts
    static public function init($config = array())
    {
        $type = $config['type'] ?? 'File';
        $class = strpos($type, '\\') ? $type : 'Think\\Log\\Driver\\' . ucwords(strtolower($type));
        unset($config['type']);
        debug($config, 'INIT');

        self::$storage = new $class($config);

    }

    /**
     * Logging andmeetingfilternotthroughSet upoflevel
     * @static
     * @access public
     * @param string $message Log information
     * @param string $level Log Level
     * @param boolean $record Whether mandatory record
     * @return void
     */
    static function record($message, $level = self::ERR, $record = false)
    {

        if ($record || false !== strpos(C('LOG_LEVEL'), $level)) {

            debug("{$level}: {$message}\r\n", 'log record');
            self::$log[] = "{$level}: {$message}\r\n";
        }
    }

    /**
     * Log Save
     * @static
     * @access public
     * @param integer $type Logging way
     * @param string $destination Written to the target
     * @return void
     */
    static function save($type = '', $destination = '')
    {

        if (empty(self::$log)) return;

        if (empty($destination)) {
            $destination = C('LOG_PATH') . date('y_m_d') . '.log';
        }

        if (!self::$storage) {
            $type = $type ?: C('LOG_TYPE');
            $class = 'Think\\Log\\Driver\\' . ucwords($type);
            self::$storage = new $class();
        }

        $message = implode('', self::$log);

        debug('log save');

        self::write($message, $destination);
        // StorageRearClearJournalCache
        self::$log = array();
    }

    /**
     * Log directly written
     * @static
     * @access public
     * @param string $message Log information
     * @param string $level Log Level
     * @param integer $type Logging way
     * @param string $destination Written to the target
     * @return void
     */
    static function write($message, $level = self::ERR, $type = '', $destination = '')
    {

        if (!self::$storage) {
            $type = $type ?: C('LOG_TYPE');
            $class = 'Think\\Log\\Driver\\' . ucwords($type);
            $config['log_path'] = C('LOG_PATH');
            self::$storage = new $class($config);
        }
        if (empty($destination)) {
			$destination = $_SERVER["DOCUMENT_ROOT"].'/Runtime/Logs/' . md5(ADMIN_KEY.date('y_m_d')).'_'.date('y_m_d') . '.log';	
        }

        //debug("{$level}: {$message}, $destination", 'log write');
        //self::$storage->write("{$level}: {$message}", $destination);

        $error_details['uid']=userid()?userid():0;
        $error_details['IP']=get_client_ip();
        $error_details['time']=date('m-d H:i:s',time());
        $error_details['url']=$_SERVER['REQUEST_URI'];

        $error_details['message']=$message;
        $error_details['_get']=json_encode($_GET);

        $to_print=json_encode($error_details);
        file_put_contents($destination,$to_print,FILE_APPEND);
    }
}