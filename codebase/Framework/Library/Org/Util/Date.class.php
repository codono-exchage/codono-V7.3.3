<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2009 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: support@codono.com
// +----------------------------------------------------------------------

namespace Org\Util;
/**
 * Date Time Operation class
 * @category   ORG
 * @package  ORG
 * @subpackage  Date
 * @author    liu21st <liu21st@gmail.com>
 * @version   $Id: Date.class.php 2662 2012-01-26 06:32:50Z liu21st $
 */
class Date
{

    /**
     * Timestamp date
     * @var integer
     * @access protected
     */
    private $date;

    /**
     * Time zone
     * @var integer
     * @access protected
     */
    protected $timezone;

    /**
     * year
     * @var integer
     * @access protected
     */
    protected $year;

    /**
     * month
     * @var integer
     * @access protected
     */
    protected $month;

    /**
     * day
     * @var integer
     * @access protected
     */
    protected $day;

    /**
     * Time
     * @var integer
     * @access protected
     */
    protected $hour;

    /**
     * Minute
     * @var integer
     * @access protected
     */
    protected $minute;

    /**
     * second
     * @var integer
     * @access protected
     */
    protected $second;

    /**
     * The numbers indicate the week
     * @var integer
     * @access protected
     */
    protected $weekday;

    /**
     * Complete representation of the week
     * @var string
     * @access protected
     */
    protected $cWeekday;

    /**
     * The number of days in a year 0－365
     * @var integer
     * @access protected
     */
    protected $yDay;

    /**
     * Complete representation of the month
     * @var string
     * @access protected
     */
    protected $cMonth;

    /**
     * dateCDATEShow
     * @var string
     * @access protected
     */
    protected $CDATE;

    /**
     * Date ofYMDShow
     * @var string
     * @access protected
     */
    protected $YMD;

    /**
     * Represents the output time
     * @var string
     * @access protected
     */
    protected $CTIME;

    // weekofExport
    protected $Week = array("day", "一", "two", "three", "four", "Fives", "six");

    /**
     * Architecturefunction
     * CreateDateObjects
     * @param mixed $date date
     * @static
     * @access public
     */
    public function __construct($date = '')
    {
        //analysisdate
        $this->date = $this->parse($date);
        $this->setDate($this->date);
    }

    /**
     * Date of analysis
     * Returns the timestamp
     * @static
     * @access public
     * @param mixed $date date
     * @return string
     */
    public function parse($date)
    {
        if (is_string($date)) {
            if (($date == "") || strtotime($date) == -1) {
                //forairdefaultObtainCurrent timestamp
                $tmpdate = time();
            } else {
                //TheStringChangeto makeUNIXtimestamp
                $tmpdate = strtotime($date);
            }
        } elseif (is_null($date)) {
            //forairdefaultObtainCurrent timestamp
            $tmpdate = time();

        } elseif (is_numeric($date)) {
            //digitalformatdirectConverted totimestamp
            $tmpdate = $date;

        } else {
            if (get_class($date) == "Date") {
                //ifDateObjects
                $tmpdate = $date->date;
            } else {
                //defaulttakeCurrent timestamp
                $tmpdate = time();
            }
        }
        return $tmpdate;
    }

    /**
     * verificationDate datawhethereffective
     * @access public
     * @param mixed $date Date data
     * @return string
     */
    public function valid($date)
    {

    }

    /**
     * Date parameter settings
     * @static
     * @access public
     * @param integer $date Date time stamp
     * @return void
     */
    public function setDate($date)
    {
        $dateArray = getdate($date);
        $this->date = $dateArray[0];            //timestamp
        $this->second = $dateArray["seconds"];    //second
        $this->minute = $dateArray["minutes"];    //Minute
        $this->hour = $dateArray["hours"];      //Time
        $this->day = $dateArray["mday"];       //day
        $this->month = $dateArray["mon"];        //month
        $this->year = $dateArray["year"];       //year

        $this->weekday = $dateArray["wday"];       //week 0～6
        $this->cWeekday = 'week' . $this->Week[$this->weekday];//$dateArray["weekday"];    //Complete weekShow
        $this->yDay = $dateArray["yday"];       //The number of days in a year 0－365
        $this->cMonth = $dateArray["month"];      //Complete representation of the month

        $this->CDATE = $this->format("%Y-%m-%d");//dateShow
        $this->YMD = $this->format("%Y%m%d");  //simpledate
        $this->CTIME = $this->format("%H:%M:%S");//timeShow

        return;
    }

    /**
     * Date Format
     * The default return 1970-01-01 11:30:45 format
     * @access public
     * @param string $format Formatting parameters
     * @return string
     */
    public function format($format = "%Y-%m-%d %H:%M:%S")
    {
        return strftime($format, $this->date);
    }

    /**
     * It is a leap year
     * @static
     * @access public
     * @return string
     */
    public function isLeapYear($year = '')
    {
        if (empty($year)) {
            $year = $this->year;
        }
        return ((($year % 4) == 0) && (($year % 100) != 0) || (($year % 400) == 0));
    }

    /**
     * Calculating date difference
     *
     *  w - weeks
     *  d - days
     *  h - hours
     *  m - minutes
     *  s - seconds
     * @static
     * @access public
     * @param mixed $date Date to be compared
     * @param string $elaps Compare span
     * @return integer
     */
    public function dateDiff($date, $elaps = "d")
    {
        $__DAYS_PER_WEEK__ = (7);
        $__DAYS_PER_MONTH__ = (30);
        $__DAYS_PER_YEAR__ = (365);
        $__HOURS_IN_A_DAY__ = (24);
        $__MINUTES_IN_A_DAY__ = (1440);
        $__SECONDS_IN_A_DAY__ = (86400);
        //ComputeThe number of days difference
        $__DAYSELAPS = ($this->parse($date) - $this->date) / $__SECONDS_IN_A_DAY__;
        switch ($elaps) {
            case "y"://Changeadult
                $__DAYSELAPS = $__DAYSELAPS / $__DAYS_PER_YEAR__;
                break;
            case "M"://ChangeTo month
                $__DAYSELAPS = $__DAYSELAPS / $__DAYS_PER_MONTH__;
                break;
            case "w"://ChangeTo week
                $__DAYSELAPS = $__DAYSELAPS / $__DAYS_PER_WEEK__;
                break;
            case "h"://Changeto makehour
                $__DAYSELAPS = $__DAYSELAPS * $__HOURS_IN_A_DAY__;
                break;
            case "m"://Changeto makeminute
                $__DAYSELAPS = $__DAYSELAPS * $__MINUTES_IN_A_DAY__;
                break;
            case "s"://ChangeInto second
                $__DAYSELAPS = $__DAYSELAPS * $__SECONDS_IN_A_DAY__;
                break;
        }
        return $__DAYSELAPS;
    }

    /**
     * HumanizeofCalculating date difference
     * @static
     * @access public
     * @param mixed $time To compare the time
     * @param mixed $precision The accuracy of the return
     * @return string
     */
    public function timeDiff($time, $precision = false)
    {
        if (!is_numeric($precision) && !is_bool($precision)) {
            static $_diff = array('y' => 'year', 'M' => 'Months', 'd' => 'day', 'w' => 'week', 's' => 'second', 'h' => 'hour', 'm' => 'minute');
            return ceil($this->dateDiff($time, $precision)) . $_diff[$precision] . 'before';
        }
        $diff = abs($this->parse($time) - $this->date);
        static $chunks = array(array(31536000, 'year'), array(2592000, 'Months'), array(604800, 'week'), array(86400, 'day'), array(3600, 'hour'), array(60, 'minute'), array(1, 'second'));
        $count = 0;
        $since = '';
        for ($i = 0; $i < count($chunks); $i++) {
            if ($diff >= $chunks[$i][0]) {
                $num = floor($diff / $chunks[$i][0]);
                $since .= sprintf('%d' . $chunks[$i][1], $num);
                $diff = (int)($diff - $chunks[$i][0] * $num);
                $count++;
                if (!$precision || $count >= $precision) {
                    break;
                }
            }
        }
        return $since . 'before';
    }

    /**
     * Week one day return returnDateObjects
     * @access public
     * @return Date
     */
    public function getDayOfWeek($n)
    {
        $week = array(0 => 'sunday', 1 => 'monday', 2 => 'tuesday', 3 => 'wednesday', 4 => 'thursday', 5 => 'friday', 6 => 'saturday');
        return (new Date($week[$n]));
    }

    /**
     * Calculating a first day of week returnDateObjects
     * @access public
     * @return Date
     */
    public function firstDayOfWeek()
    {
        return $this->getDayOfWeek(1);
    }

    /**
     * Calculate the first day of the month returnDateObjects
     * @access public
     * @return Date
     */
    public function firstDayOfMonth()
    {
        return (new Date(mktime(0, 0, 0, $this->month, 1, $this->year)));
    }

    /**
     * Calculate the first day of the year returnDateObjects
     * @access public
     * @return Date
     */
    public function firstDayOfYear()
    {
        return (new Date(mktime(0, 0, 0, 1, 1, $this->year)));
    }

    /**
     * The last day of the week is calculated returnDateObjects
     * @access public
     * @return Date
     */
    public function lastDayOfWeek()
    {
        return $this->getDayOfWeek(0);
    }

    /**
     * ComputemonthofThe last day returnDateObjects
     * @access public
     * @return Date
     */
    public function lastDayOfMonth()
    {
        return (new Date(mktime(0, 0, 0, $this->month + 1, 0, $this->year)));
    }

    /**
     * ComputeyearsofThe last day returnDateObjects
     * @access public
     * @return Date
     */
    public function lastDayOfYear()
    {
        return (new Date(mktime(0, 0, 0, 1, 0, $this->year + 1)));
    }

    /**
     * ComputemonthofmaximumDays
     * @access public
     * @return integer
     */
    public function maxDayOfMonth()
    {
        $result = $this->dateDiff(strtotime($this->dateAdd(1, 'm')), 'd');
        return $result;
    }

    /**
     * Get date specified interval
     *
     *    yyyy - year
     *    q    - Quarter
     *    m    - month
     *    y    - day of year
     *    d    - day
     *    w    - week
     *    ww   - week of year
     *    h    - hour
     *    n    - minute
     *    s    - second
     * @access public
     * @param integer $number Number of intervals
     * @param string $interval Comparison Type
     * @return Date
     */
    public function dateAdd($number = 0, $interval = "d")
    {
        $hours = $this->hour;
        $minutes = $this->minute;
        $seconds = $this->second;
        $month = $this->month;
        $day = $this->day;
        $year = $this->year;

        switch ($interval) {
            case "yyyy":
                //---Add $number to year
                $year += $number;
                break;

            case "q":
                //---Add $number to quarter
                $month += ($number * 3);
                break;

            case "m":
                //---Add $number to month
                $month += $number;
                break;

            case "y":
            case "d":
            case "w":
                //---Add $number to day of year, day, day of week
                $day += $number;
                break;

            case "ww":
                //---Add $number to week
                $day += ($number * 7);
                break;

            case "h":
                //---Add $number to hours
                $hours += $number;
                break;

            case "n":
                //---Add $number to minutes
                $minutes += $number;
                break;

            case "s":
                //---Add $number to seconds
                $seconds += $number;
                break;
        }

        return (new Date(mktime($hours,
            $minutes,
            $seconds,
            $month,
            $day,
            $year)));

    }

    /**
     * Chinese digital-to-Date
     * For days and months, weeks
     * @static
     * @access public
     * @param integer $number Date number
     * @return string
     */
    public function numberToCh($number)
    {
        $number = intval($number);
        $array = array('one', 'two', 'three', 'four', 'Fives', 'six', 'Seven', 'Eight', 'nine', 'ten');
        $str = '';
        if ($number == 0) {
            $str .= "ten";
        }
        if ($number < 10) {
            $str .= $array[$number - 1];
        } elseif ($number < 20) {
            $str .= "ten" . $array[$number - 11];
        } elseif ($number < 30) {
            $str .= "twenty" . $array[$number - 21];
        } else {
            $str .= "thirty" . $array[$number - 31];
        }
        return $str;
    }

    /**
     * Year digital switch to Chinese
     * @static
     * @access public
     * @param integer $yearStr Year digital
     * @param boolean $flag whetherdisplayA.D.
     * @return string
     */
    public function yearToCh($yearStr, $flag = false)
    {
        $array = array('zero', 'one', 'two', 'three', 'four', 'Fives', 'six', 'Seven', 'Eight', 'nine');
        $str = $flag ? 'A.D.' : '';
        for ($i = 0; $i < 4; $i++) {
            $str .= $array[substr($yearStr, $i, 1)];
        }
        return $str;
    }

    /**
     *  Date of judgment Belongs Zodiac Lunar New Year constellation
     *  type parameter：XZ constellation GZ Zodiac SX Lunar New Year
     *
     * @static
     * @access public
     * @param string $type The type of access to information
     * @return string
     */
    public function magicInfo($type)
    {
        $result = '';
        $m = $this->month;
        $y = $this->year;
        $d = $this->day;

        switch ($type) {
            case 'XZ'://constellation
                $XZDict = array('Capricorn', 'Aquarius', 'Pisces', 'Aries', 'Taurus', 'Gemini', 'Cancer', 'lion', 'virgin', 'Libra', 'Scorpio', 'Archer');
                $Zone = array(1222, 122, 222, 321, 421, 522, 622, 722, 822, 922, 1022, 1122, 1222);
                if ((100 * $m + $d) >= $Zone[0] || (100 * $m + $d) < $Zone[1])
                    $i = 0;
                else
                    for ($i = 1; $i < 12; $i++) {
                        if ((100 * $m + $d) >= $Zone[$i] && (100 * $m + $d) < $Zone[$i + 1])
                            break;
                    }
                $result = $XZDict[$i] . 'seat';
                break;

            case 'GZ'://Zodiac
                $GZDict = array(
                    array('Armor', 'B', 'Propionate', 'Ding', 'Amyl', 'already', 'Geng', 'Xin', 'Ren', 'Decanoate'),
                    array('child', 'ugly', 'Yin', 'D', 'Chen', 'Pat', 'Noon', 'not', 'Shen', 'unitary', 'Xu', 'Hai')
                );
                $i = $y - 1900 + 36;
                $result = $GZDict[0][$i % 10] . $GZDict[1][$i % 12];
                break;

            case 'SX'://Lunar New Year
                $SXDict = array('mouse', 'Cattle', 'tiger', 'rabbit', 'Dragon', 'snake', 'horse', 'sheep', 'monkey', 'chicken', 'dog', 'pig');
                $result = $SXDict[($y - 4) % 12];
                break;

        }
        return $result;
    }

    public function __toString()
    {
        return $this->format();
    }
}