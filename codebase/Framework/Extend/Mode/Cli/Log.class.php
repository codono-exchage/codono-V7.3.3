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

    // Log Level From上To下,From low to high
    const EMERG = 'EMERG';  // seriouserror: resulting insystemUnable to collapseuse
    const ALERT = 'ALERT';  // Cautionaryerror: have toIt was immediatelymodifyoferror
    const CRIT = 'CRIT';  // Critical value error: Over critical value oferror,e.g day 24hour, and Enterof is 25hoursuch
    const ERR = 'ERR';  // generalerror: generalerror
    const WARN = 'WARN';  // Warningerror: needwarningoferror
    const NOTICE = 'NOTIC';  // Notice: Program canrunBut alsonot enoughperfectoferror
    const INFO = 'INFO';  // information: programExportinformation
    const DEBUG = 'DEBUG';  // debugging: debugginginformation
    const SQL = 'SQL';  // SQL：SQLStatement noteOnly in debuggingmodeOpenValid

    // Logging way
    const SYSTEM = 0;
    const MAIL = 1;
    const TCP = 2;
    const FILE = 3;

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
        if ($record || false !== strpos(C('LOG_RECORD_LEVEL'), $level)) {
            $now = date(self::$format);
            self::$log[] = "{$now} {$level}: {$message}\r\n";
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
    static function save($type = self::FILE, $destination = '', $extra = '')
    {
        return;
        if (empty($destination))
            $destination = LOG_PATH . date('y_m_d') . ".log";
        if (self::FILE == $type) { // filethe wayLogginginformation
            //DetectJournalFile size,exceedConfigurationThe size of theBackupJournalfileAgainForm
            if (is_file($destination) && floor(C('LOG_FILE_SIZE')) <= filesize($destination))
                rename($destination, dirname($destination) . '/' . time() . '-' . basename($destination));
        }
        error_log(implode("", self::$log), $type, $destination, $extra);
        // StorageRearClearJournalCache
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
    static function write($message, $level = self::ERR, $type = self::FILE, $destination = '', $extra = '')
    {
        $now = date(self::$format);
        if (empty($destination))
            $destination = LOG_PATH . date('y_m_d') . ".log";
        if (self::FILE == $type) { // filethe wayLogging
            //DetectJournalFile size,exceedConfigurationThe size of theBackupJournalfileAgainForm
            if (is_file($destination) && floor(C('LOG_FILE_SIZE')) <= filesize($destination))
                rename($destination, dirname($destination) . '/' . time() . '-' . basename($destination));
        }
        error_log("{$now} {$level}: {$message}\r\n", $type, $destination, $extra);
        //clearstatcache();
    }

}