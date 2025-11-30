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
 * System debugging class
 * @category   Think
 * @package  Think
 * @subpackage  Util
 * @author    liu21st <liu21st@gmail.com>
 */
class Debug
{

    static private $marker = array();

    /**
     * Mark bit debugging
     * @access public
     * @param string $name To mark the name of the location
     * @return void
     */
    static public function mark($name)
    {
        self::$marker['time'][$name] = microtime(TRUE);
        if (MEMORY_LIMIT_ON) {
            self::$marker['mem'][$name] = memory_get_usage();
            self::$marker['peak'][$name] = function_exists('memory_get_peak_usage') ? memory_get_peak_usage() : self::$marker['mem'][$name];
        }
    }

    /**
     * Intervalusetime查看
     * @access public
     * @param string $start Name tag start
     * @param string $end End tag name
     * @param integer $decimals Decimal time
     * @return integer
     */
    static public function useTime($start, $end, $decimals = 6)
    {
        if (!isset(self::$marker['time'][$start]))
            return '';
        if (!isset(self::$marker['time'][$end]))
            self::$marker['time'][$end] = microtime(TRUE);
        return number_format(self::$marker['time'][$end] - self::$marker['time'][$start], $decimals);
    }

    /**
     * IntervaluseRAM查看
     * @access public
     * @param string $start Name tag start
     * @param string $end End tag name
     * @return integer
     */
    static public function useMemory($start, $end)
    {
        if (!MEMORY_LIMIT_ON)
            return '';
        if (!isset(self::$marker['mem'][$start]))
            return '';
        if (!isset(self::$marker['mem'][$end]))
            self::$marker['mem'][$end] = memory_get_usage();
        return number_format((self::$marker['mem'][$end] - self::$marker['mem'][$start]) / 1024);
    }

    /**
     * IntervaluseRAMpeakvalue查看
     * @access public
     * @param string $start Name tag start
     * @param string $end End tag name
     * @return integer
     */
    static function getMemPeak($start, $end)
    {
        if (!MEMORY_LIMIT_ON)
            return '';
        if (!isset(self::$marker['peak'][$start]))
            return '';
        if (!isset(self::$marker['peak'][$end]))
            self::$marker['peak'][$end] = function_exists('memory_get_peak_usage') ? memory_get_peak_usage() : memory_get_usage();
        return number_format(max(self::$marker['peak'][$start], self::$marker['peak'][$end]) / 1024);
    }
}