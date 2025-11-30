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
class Stringer
{

    /**
     * FormUUID Stand-alone use
     * @access public
     * @return string
     */
    static public function uuid()
    {
        $charid = md5(uniqid(mt_rand(), true));
        $hyphen = chr(45);// "-"
        $uuid = chr(123)// "{"
            . substr($charid, 0, 8) . $hyphen
            . substr($charid, 8, 4) . $hyphen
            . substr($charid, 12, 4) . $hyphen
            . substr($charid, 16, 4) . $hyphen
            . substr($charid, 20, 12)
            . chr(125);// "}"
        return $uuid;
    }

    /**
     * FormGuidPrimary key
     * @return Boolean
     */
    static public function keyGen()
    {
        return str_replace('-', '', substr(Stringer::uuid(), 1, -1));
    }

    /**
     * Check if the string isUTF8coding
     * @param string $string String
     * @return Boolean
     */
    static public function isUtf8($str)
    {
        $c = 0;
        $b = 0;
        $bits = 0;
        $len = strlen($str);
        for ($i = 0; $i < $len; $i++) {
            $c = ord($str[$i]);
            if ($c > 128) {
                if (($c >= 254)) return false;
                elseif ($c >= 252) $bits = 6;
                elseif ($c >= 248) $bits = 5;
                elseif ($c >= 240) $bits = 4;
                elseif ($c >= 224) $bits = 3;
                elseif ($c >= 192) $bits = 2;
                else return false;
                if (($i + $bits) > $len) return false;
                while ($bits > 1) {
                    $i++;
                    $b = ord($str[$i]);
                    if ($b < 128 || $b > 191) return false;
                    $bits--;
                }
            }
        }
        return true;
    }

    /**
     * StringInterception,Support Chineseand othercoding
     * @static
     * @access public
     * @param string $str Need to convert a string
     * @param string $start Starting position
     * @param string $length Intercept length
     * @param string $charset Encoding format
     * @param string $suffix Truncated display characters
     * @return string
     */
    static public function msubstr($str, $start = 0, $length, $charset = "utf-8", $suffix = true)
    {
        if (function_exists("mb_substr"))
            $slice = mb_substr($str, $start, $length, $charset);
        elseif (function_exists('iconv_substr')) {
            $slice = iconv_substr($str, $start, $length, $charset);
        } else {
            $re['utf-8'] = "/[\x01-\x7f]|[\xc2-\xdf][\x80-\xbf]|[\xe0-\xef][\x80-\xbf]{2}|[\xf0-\xff][\x80-\xbf]{3}/";
            $re['gb2312'] = "/[\x01-\x7f]|[\xb0-\xf7][\xa0-\xfe]/";
            $re['gbk'] = "/[\x01-\x7f]|[\x81-\xfe][\x40-\xfe]/";
            $re['big5'] = "/[\x01-\x7f]|[\x81-\xfe]([\x40-\x7e]|\xa1-\xfe])/";
            preg_match_all($re[$charset], $str, $match);
            $slice = join("", array_slice($match[0], $start, $length));
        }
        return $suffix ? $slice . '...' : $slice;
    }

    /**
     * Generates a random string,AvailableCome frommoveFormpassword
     * The default length6Place Letters and numbers mixed Support Chinese
     * @param string $len length
     * @param string $type String type
     * 0 letter 1 digital other mixing
     * @param string $addChars Additional character
     * @return string
     */
    static public function randString($len = 6, $type = '', $addChars = '')
    {
        $str = '';
        switch ($type) {
            case 0:
                $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz' . $addChars;
                break;
            case 1:
                $chars = str_repeat('0123456789', 3);
                break;
            case 2:
                $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ' . $addChars;
                break;
            case 3:
                $chars = 'abcdefghijklmnopqrstuvwxyz' . $addChars;
                break;
            case 4:
                $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz' . $addChars;
                break;
            default :
                // defaultRemoveThe confusingofcharacteroOLlwithdigital01,wantAdd topleaseuseaddCharsparameter
                $chars = 'ABCDEFGHIJKMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789' . $addChars;
                break;
        }
        if ($len > 10) {//DigitLive长重复Stringfor surefrequency
            $chars = $type == 1 ? str_repeat($chars, $len) : str_repeat($chars, 5);
        }
        if ($type != 4) {
            $chars = str_shuffle($chars);
            $str = substr($chars, 0, $len);
        } else {
            // ChineseRandom word
            for ($i = 0; $i < $len; $i++) {
                $str .= self::msubstr($chars, floor(mt_rand(0, mb_strlen($chars, 'utf-8') - 1)), 1, 'utf-8', false);
            }
        }
        return $str;
    }

    /**
     * Formfor sureQuantityofrandom number,anddoes not repeat
     * @param integer $number Quantity
     * @param string $len length
     * @param string $type String type
     * 0 letter 1 digital other mixing
     * @return string
     */
    static public function buildCountRand($number, $length = 4, $mode = 1)
    {
        if ($mode == 1 && $length < strlen($number)) {
            //insufficientWithFormfor sureQuantityofdoes not repeatdigital
            return false;
        }
        $rand = [];
        for ($i = 0; $i < $number; $i++) {
            $rand[] = self::randString($length, $mode);
        }
        $unqiue = array_unique($rand);
        if (count($unqiue) == count($rand)) {
            return $rand;
        }
        $count = count($rand) - count($unqiue);
        for ($i = 0; $i < $count * 3; $i++) {
            $rand[] = self::randString($length, $mode);
        }
        $rand = array_slice(array_unique($rand), 0, $number);
        return $rand;
    }

    /**
     *  bandformatFormrandomcharacter Support batch generation
     *  butThat may exist重复
     * @param string $format Character format
     *     # Represent numbers * Represent letters and numbers $ Represent letters
     * @param integer $number Generate as many
     * @return string | array
     */
    static public function buildFormatRand($format, $number = 1)
    {
        $str = array();
        $length = strlen($format);
        for ($j = 0; $j < $number; $j++) {
            $strtemp = '';
            for ($i = 0; $i < $length; $i++) {
                $char = substr($format, $i, 1);
                switch ($char) {
                    case "*"://Letters and numbers mixed
                        $strtemp .= Stringer::randString(1);
                        break;
                    case "#"://digital
                        $strtemp .= Stringer::randString(1, 1);
                        break;
                    case "$"://capitalletter
                        $strtemp .= Stringer::randString(1, 2);
                        break;
                    default://otherformat does not change
                        $strtemp .= $char;
                        break;
                }
            }
            $str[] = $strtemp;
        }
        return $number == 1 ? $strtemp : $str;
    }

    /**
     * Obtainfor surerange内ofrandomdigital Digit if less than zero
     * @param integer $min Minimum
     * @param integer $max Maximum
     * @return string
     */
    static public function randNumber($min, $max)
    {
        return sprintf("%0" . strlen($max) . "d", mt_rand($min, $max));
    }

    // automaticChangecharacterCollection stand byArrayChange
    static public function autoCharset($string, $from = 'gbk', $to = 'utf-8')
    {
        $from = strtoupper($from) == 'UTF8' ? 'utf-8' : $from;
        $to = strtoupper($to) == 'UTF8' ? 'utf-8' : $to;
        if (strtoupper($from) === strtoupper($to) || empty($string) || (is_scalar($string) && !is_string($string))) {
            //in case coding same or non-String Scalar quantity then not Change
            return $string;
        }
        if (is_string($string)) {
            if (function_exists('mb_convert_encoding')) {
                return mb_convert_encoding($string, $to, $from);
            } elseif (function_exists('iconv')) {
                return iconv($from, $to, $string);
            } else {
                return $string;
            }
        } elseif (is_array($string)) {
            foreach ($string as $key => $val) {
                $_key = self::autoCharset($key, $from, $to);
                $string[$_key] = self::autoCharset($val, $from, $to);
                if ($key != $_key)
                    unset($string[$key]);
            }
            return $string;
        } else {
            return $string;
        }
    }
}