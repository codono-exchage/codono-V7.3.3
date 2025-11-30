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
 * Log Processing category
 * @category   Think
 * @package  Think
 * @subpackage  Core
 * @author    liu21st <liu21st@gmail.com>
 */
class Log
{

    // Log Level From Up To Down, From low to high
    const EMERG = 'EMERG';  // seriouserror: resulting insystemUnable to collapseuse
    const ALERT = 'ALERT';  // Cautionaryerror: have toIt was immediatelymodifyoferror
    const CRIT = 'CRIT';  // Threshold error: Errors that exceed the threshold, such as 24 hours a day, and 25 hours is entered
    const ERR = 'ERR';  // generalerror: generalerror
    const WARN = 'WARN';  // Warningerror: needwarningoferror
    const NOTICE = 'NOTIC';  // Notice: Program canrunBut alsonot enoughperfectoferror
    const INFO = 'INFO';  // information: programExportinformation
    const DEBUG = 'DEBUG';  // debugging: debugginginformation
    const SQL = 'SQL';  // SQL：SQLStatement noteOnly in debuggingmodeOpenValid

    // Logging way
    const SYSTEM = 0;
    const MAIL = 1;
    const FILE = 3;
    const SAPI = 4;

    // Log information
    static $log = array();

    // dateformat
    static $format = '[ c ]';

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
            self::$log[] = "{$level}: {$message}\r\n";
        }
    }

    /**
     * Log Save
     * @static
     * @access public
     * @param integer $type Logging way
     * @param string $destination Written to the target
     * @param string $extra Additional parameters
     * @return void
     */
    static function save($type = '', $destination = '', $extra = '')
    {
        if (empty(self::$log)) return;
        $type = $type ? $type : C('LOG_TYPE');
        if (self::FILE == $type) { // File log information
            if (empty($destination))
                $destination = C('LOG_PATH') . date('y_m_d') . '.log';
            //Check the size of the log file, backup the log file if it exceeds the configured size and regenerate it
            if (is_file($destination) && floor(C('LOG_FILE_SIZE')) <= filesize($destination))
                rename($destination, dirname($destination) . '/' . time() . '-' . basename($destination));
        } else {
            $destination = $destination ? $destination : C('LOG_DEST');
            $extra = $extra ? $extra : C('LOG_EXTRA');
        }
        $now = date(self::$format);
        error_log($now . ' ' . get_client_ip() . ' ' . $_SERVER['REQUEST_URI'] . "\r\n" . implode('', self::$log) . "\r\n", $type, $destination, $extra);
        // Clear log cache after saving
        self::$log = array();
        //clearstatcache();
    }

    /**
     * Log directly written
     * @static
     * @access public
     * @param string $message Log information
     * @param string $level Log Level
     * @param integer $type Logging way
     * @param string $destination Written to the target
     * @param string $extra Additional parameters
     * @return void
     */
    static function write($message, $level = self::ERR, $type = '', $destination = '', $extra = '')
    {
        $now = date(self::$format);
        $type = $type ? $type : C('LOG_TYPE');
        if (self::FILE == $type) { // File log
            if (empty($destination))
                $destination = C('LOG_PATH') . date('y_m_d') . '.log';
            //Check the size of the log file, backup the log file if it exceeds the configured size and regenerate it
            if (is_file($destination) && floor(C('LOG_FILE_SIZE')) <= filesize($destination))
                rename($destination, dirname($destination) . '/' . time() . '-' . basename($destination));
        } else {
            $destination = $destination ? $destination : C('LOG_DEST');
            $extra = $extra ? $extra : C('LOG_EXTRA');
        }
        error_log("{$now} {$level}: {$message}\r\n", $type, $destination, $extra);
        //clearstatcache();
    }
}