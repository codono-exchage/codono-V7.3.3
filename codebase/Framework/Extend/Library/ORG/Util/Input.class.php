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
// $Id: Input.class.php 2528 2012-01-03 14:58:50Z liu21st $

/** Input Data Management
 * Instructions
 *  $Input = Input::getInstance();
 *  $Input->get('name','md5','0');
 *  $Input->session('memberId','','0');
 *
 * the followingWe summarize some of the commonofdatadeal withmethod.With下methodRegardless ofmagic_quotes_gpcsetting.
 *
 * retrieve data:
 *    If the$_POSTor$_GETThe acquisition, useInput::getVar($_POST['field']);,Fromdatabaseorfileon不needThe。
 *    Or directly use Input::magicQuotesTo eliminate allmagic_quotes_gpcEscaped.
 *
 * Stored Procedures:
 *    afterInput::getVar($_POST['field'])obtainofdata,It is clean andofdata,candirectStorage。
 *    If you want to filter dangeroushtml,can use $html = Input::safeHtml($data);
 *
 * Page displays:
 *    Plain TextdisplayOn a web page,Such as articlestitle<title>$data</title>： $data = Input::forShow($field);
 *    HTML On a web pagedisplay,Such as articlescontent：No needdeal with。
 *    In the web page to the sourceCodethe waydisplayhtml：$vo = Input::forShow($html);
 *    Plain text orHTMLintextareaFor editing: $vo = Input::forTarea($value);
 *    htmlIn the label, such as<input value="data" /> ,use $vo = Input::forTag($value); or $vo = Input::hsc($value);
 *
 * Special usage:
 *    StringTodatabaseSearch： $data = Input::forSearch($field);
 */
class Input
{

    private $filter = null;   // Input filter
    private static $_input = array('get', 'post', 'request', 'env', 'server', 'cookie', 'session', 'globals', 'config', 'lang', 'call');
    //htmlLabel Settings
    public static $htmlTags = array(
        'allow' => 'table|td|th|tr|i|b|u|strong|img|p|br|div|strong|em|ul|ol|li|dl|dd|dt|a',
        'ban' => 'html|head|meta|link|base|basefont|body|bgsound|title|style|script|form|iframe|frame|frameset|applet|id|ilayer|layer|name|script|style|xml',
    );

    static public function getInstance()
    {
        return get_instance_of(__CLASS__);
    }

    /**
     * +----------------------------------------------------------
     * Magic Methods Havedoes not existofoperatingoftimecarried out
     * +----------------------------------------------------------
     * @access public
     * +----------------------------------------------------------
     * @param string $type Input data type
     * @param array $args parameter array(key,filter,default)
     * +----------------------------------------------------------
     * @return mixed
    +----------------------------------------------------------
     */
    public function __call($type, $args = array())
    {
        $type = strtolower(trim($type));
        if (in_array($type, self::$_input, true)) {
            switch ($type) {
                case 'get':
                    $input =& $_GET;
                    break;
                case 'post':
                    $input =& $_POST;
                    break;
                case 'request':
                    $input =& $_REQUEST;
                    break;
                case 'env':
                    $input =& $_ENV;
                    break;
                case 'server':
                    $input =& $_SERVER;
                    break;
                case 'cookie':
                    $input =& $_COOKIE;
                    break;
                case 'session':
                    $input =& $_SESSION;
                    break;
                case 'globals':
                    $input =& $GLOBALS;
                    break;
                case 'files':
                    $input =& $_FILES;
                    break;
                case 'call':
                    $input = 'call';
                    break;
                case 'config':
                    $input = C();
                    break;
                case 'lang':
                    $input = L();
                    break;
                default:
                    return NULL;
            }
            if ('call' === $input) {
                // Other callsthe wayofenterdata
                $callback = array_shift($args);
                $params = array_shift($args);
                $data = call_user_func_array($callback, $params);
                if (count($args) === 0) {
                    return $data;
                }
                $filter = isset($args[0]) ? $args[0] : $this->filter;
                if (!empty($filter)) {
                    $data = call_user_func_array($filter, $data);
                }
            } else {
                if (0 == count($args) || empty($args[0])) {
                    return $input;
                } elseif (array_key_exists($args[0], $input)) {
                    // System Variables
                    $data = $input[$args[0]];
                    $filter = isset($args[1]) ? $args[1] : $this->filter;
                    if (!empty($filter)) {
                        $data = call_user_func_array($filter, $data);
                    }
                } else {
                    // Specify the input does not exist
                    $data = isset($args[2]) ? $args[2] : NULL;
                }
            }
            return $data;
        }
    }

    /**
     * +----------------------------------------------------------
     * Setting data filtering method
     * +----------------------------------------------------------
     * @access private
     * +----------------------------------------------------------
     * @param mixed $filter Filtration method
     * +----------------------------------------------------------
     * @return void
    +----------------------------------------------------------
     */
    public function filter($filter)
    {
        $this->filter = $filter;
        return $this;
    }

    /**
     * +----------------------------------------------------------
     * characterMagicQuoteEscape filter
     * +----------------------------------------------------------
     * @access public
     * +----------------------------------------------------------
     * @return void
    +----------------------------------------------------------
     */
    static public function noGPC()
    {
        $_POST = stripslashes_deep($_POST);
        $_GET = stripslashes_deep($_GET);
        $_COOKIE = stripslashes_deep($_COOKIE);
        $_REQUEST = stripslashes_deep($_REQUEST);
    }

    /**
     * +----------------------------------------------------------
     * deal withString,So you cannormalSearch
     * +----------------------------------------------------------
     * @access public
     * +----------------------------------------------------------
     * @param string $string The string to be processed
     * +----------------------------------------------------------
     * @return string
    +----------------------------------------------------------
     */
    static public function forSearch($string)
    {
        return str_replace(array('%', '_'), array('\%', '\_'), $string);
    }

    /**
     * +----------------------------------------------------------
     * @access public
     * +----------------------------------------------------------
     * @param string $string The string to be processed
     * +----------------------------------------------------------
     * @return string
    +----------------------------------------------------------
     */
    static public function forShow($string)
    {
        return self::nl2Br(self::hsc($string));
    }

    /**
     * +----------------------------------------------------------
     * deal withPlain Textdata,In order totextareaTag display
     * +----------------------------------------------------------
     * @access public
     * +----------------------------------------------------------
     * @param string $string The string to be processed
     * +----------------------------------------------------------
     * @return string
    +----------------------------------------------------------
     */
    static public function forTarea($string)
    {
        return str_ireplace(array('<textarea>', '</textarea>'), array('&lt;textarea>', '&lt;/textarea>'), $string);
    }

    /**
     * +----------------------------------------------------------
     * willdatamiddleSingle and double quotes escaped
     * +----------------------------------------------------------
     * @access public
     * +----------------------------------------------------------
     * @param string $text The string to be processed
     * +----------------------------------------------------------
     * @return string
    +----------------------------------------------------------
     */
    static public function forTag($string)
    {
        return str_replace(array('"', "'"), array('&quot;', '&#039;'), $string);
    }

    /**
     * +----------------------------------------------------------
     * ChangeWritingmiddle超linkforcanClick onconnection
     * +----------------------------------------------------------
     * @access public
     * +----------------------------------------------------------
     * @param string $string The string to be processed
     * +----------------------------------------------------------
     * @return string
    +----------------------------------------------------------
     */
    static public function makeLink($string)
    {
        $validChars = "a-z0-9\/\-_+=.~!%@?#&;:$\|";
        $patterns = array(
            "/(^|[^]_a-z0-9-=\"'\/])([a-z]+?):\/\/([{$validChars}]+)/ei",
            "/(^|[^]_a-z0-9-=\"'\/])www\.([a-z0-9\-]+)\.([{$validChars}]+)/ei",
            "/(^|[^]_a-z0-9-=\"'\/])ftp\.([a-z0-9\-]+)\.([{$validChars}]+)/ei",
            "/(^|[^]_a-z0-9-=\"'\/:\.])([a-z0-9\-_\.]+?)@([{$validChars}]+)/ei");
        $replacements = array(
            "'\\1<a href=\"\\2://\\3\" title=\"\\2://\\3\" rel=\"external\">\\2://'.Input::truncate( '\\3' ).'</a>'",
            "'\\1<a href=\"http://www.\\2.\\3\" title=\"www.\\2.\\3\" rel=\"external\">'.Input::truncate( 'www.\\2.\\3' ).'</a>'",
            "'\\1<a href=\"ftp://ftp.\\2.\\3\" title=\"ftp.\\2.\\3\" rel=\"external\">'.Input::truncate( 'ftp.\\2.\\3' ).'</a>'",
            "'\\1<a href=\"mailto:\\2@\\3\" title=\"\\2@\\3\">'.Input::truncate( '\\2@\\3' ).'</a>'");
        return preg_replace($patterns, $replacements, $string);
    }

    /**
     * +----------------------------------------------------------
     * Thumbnail display string
     * +----------------------------------------------------------
     * @access public
     * +----------------------------------------------------------
     * @param string $string The string to be processed
     * @param int $length After the abbreviated length
     * +----------------------------------------------------------
     * @return string
    +----------------------------------------------------------
     */
    static public function truncate($string, $length = '50')
    {
        if (empty($string) || empty($length) || strlen($string) < $length) return $string;
        $len = floor($length / 2);
        $ret = substr($string, 0, $len) . " ... " . substr($string, 5 - $len);
        return $ret;
    }

    /**
     * +----------------------------------------------------------
     * The new line is converted to<br />label
     * +----------------------------------------------------------
     * @access public
     * +----------------------------------------------------------
     * @param string $string The string to be processed
     * +----------------------------------------------------------
     * @return string
    +----------------------------------------------------------
     */
    static public function nl2Br($string)
    {
        return preg_replace("/(\015\012)|(\015)|(\012)/", "<br />", $string);
    }

    /**
     * +----------------------------------------------------------
     * in case magic_quotes_gpc forshut downstatus,This onefunctioncanturnRighteousnessString
     * +----------------------------------------------------------
     * @access public
     * +----------------------------------------------------------
     * @param string $string The string to be processed
     * +----------------------------------------------------------
     * @return string
    +----------------------------------------------------------
     */
    static public function addSlashes($string)
    {
        if (PHP_VERSION_ID < 50400 && !get_magic_quotes_gpc()) {
            $string = addslashes($string);
        }
        return $string;
    }

    /**
     * +----------------------------------------------------------
     * From$_POST,$_GET,$_COOKIE,$_REQUESTOther data array obtained
     * +----------------------------------------------------------
     * @access public
     * +----------------------------------------------------------
     * @param string $string The string to be processed
     * +----------------------------------------------------------
     * @return string
    +----------------------------------------------------------
     */
    static public function getVar($string)
    {
        return Input::stripSlashes($string);
    }

    /**
     * +----------------------------------------------------------
     * in case magic_quotes_gpc forOpenstatus,This onefunctionCan unescapesString
     * +----------------------------------------------------------
     * @access public
     * +----------------------------------------------------------
     * @param string $string The string to be processed
     * +----------------------------------------------------------
     * @return string
    +----------------------------------------------------------
     */
    static public function stripSlashes($string)
    {
        if (ini_get('magic_quotes_gpc')) {
            $string = stripslashes($string);
        }
        return $string;
    }

    /**
     * +----------------------------------------------------------
     * FortextboxShow the formhtmlCode
     * +----------------------------------------------------------
     * @access public
     * +----------------------------------------------------------
     * @param string $string The string to be processed
     * +----------------------------------------------------------
     * @return string
    +----------------------------------------------------------
     */
    static function hsc($string)
    {
        return preg_replace(array("/&amp;/i", "/&nbsp;/i"), array('&', '&amp;nbsp;'), htmlspecialchars($string, ENT_QUOTES));
    }

    /**
     * +----------------------------------------------------------
     * Yeshsc()Inverse method of operation
     * +----------------------------------------------------------
     * @access public
     * +----------------------------------------------------------
     * @param string $text The string to be processed
     * +----------------------------------------------------------
     * @return string
    +----------------------------------------------------------
     */
    static function undoHsc($text)
    {
        return preg_replace(array("/&gt;/i", "/&lt;/i", "/&quot;/i", "/&#039;/i", '/&amp;nbsp;/i'), array(">", "<", "\"", "'", "&nbsp;"), $text);
    }

    /**
     * +----------------------------------------------------------
     * Output safehtmlFor filtering dangerous code
     * +----------------------------------------------------------
     * @access public
     * +----------------------------------------------------------
     * @param string $text The string to be processed
     * @param mixed $allowTags Allowed labels list, such as table|td|th|td
     * +----------------------------------------------------------
     * @return string
    +----------------------------------------------------------
     */
    static public function safeHtml($text, $allowTags = null)
    {
        $text = trim($text);
        //Completely filterNote
        $text = preg_replace('/<!--?.*-->/', '', $text);
        //Fully dynamic filterCode
        $text = preg_replace('/<\?|\?' . '>/', '', $text);
        //Completely filterjs
        $text = preg_replace('/<script?.*\/script>/', '', $text);

        $text = str_replace('[', '&#091;', $text);
        $text = str_replace(']', '&#093;', $text);
        $text = str_replace('|', '&#124;', $text);
        //Filter newline
        $text = preg_replace('/\r?\n/', '', $text);
        //br
        $text = preg_replace('/<br(\s\/)?' . '>/i', '[br]', $text);
        $text = preg_replace('/(\[br\]\s*){10,}/i', '[br]', $text);
        //Filter dangerousofAttributes,Such as:filteroneventlang js
        while (preg_match('/(<[^><]+)(lang|on|action|background|codebase|dynsrc|lowsrc)[^><]+/i', $text, $mat)) {
            $text = str_replace($mat[0], $mat[1], $text);
        }
        while (preg_match('/(<[^><]+)(window\.|javascript:|js:|about:|file:|document\.|vbs:|cookie)([^><]*)/i', $text, $mat)) {
            $text = str_replace($mat[0], $mat[1] . $mat[3], $text);
        }
        if (empty($allowTags)) {
            $allowTags = self::$htmlTags['allow'];
        }
        //allowofHTMLlabel
        $text = preg_replace('/<(' . $allowTags . ')( [^><\[\]]*)>/i', '[\1\2]', $text);
        //Filter unwantedhtml
        if (empty($banTag)) {
            $banTag = self::$htmlTags['ban'];
        }
        $text = preg_replace('/<\/?(' . $banTag . ')[^><]*>/i', '', $text);
        //Filter legitimateofhtmllabel
        while (preg_match('/<([a-z]+)[^><\[\]]*>[^><]*<\/\1>/i', $text, $mat)) {
            $text = str_replace($mat[0], str_replace('>', ']', str_replace('<', '[', $mat[0])), $text);
        }
        //Changequotation marks
        while (preg_match('/(\[[^\[\]]*=\s*)(\"|\')([^\2=\[\]]+)\2([^\[\]]*\])/i', $text, $mat)) {
            $text = str_replace($mat[0], $mat[1] . '|' . $mat[3] . '|' . $mat[4], $text);
        }
        //Empty Property Conversion
        $text = str_replace('\'\'', '||', $text);
        $text = str_replace('""', '||', $text);
        //filtererrorofSingle quotes
        while (preg_match('/\[[^\[\]]*(\"|\')[^\[\]]*\]/i', $text, $mat)) {
            $text = str_replace($mat[0], str_replace($mat[1], '', $mat[0]), $text);
        }
        //Changeotherall不legitimateof < >
        $text = str_replace('<', '&lt;', $text);
        $text = str_replace('>', '&gt;', $text);
        $text = str_replace('"', '&quot;', $text);
        //anti-Change
        $text = str_replace('[', '<', $text);
        $text = str_replace(']', '>', $text);
        $text = str_replace('|', '"', $text);
        //Extra spaces filtration
        $text = str_replace('  ', ' ', $text);
        return $text;
    }

    /**
     * +----------------------------------------------------------
     * deletehtmllabel,Get plain text。candeal withNestings Mark
     * +----------------------------------------------------------
     * @access public
     * +----------------------------------------------------------
     * @param string $string To be processedhtml
     * +----------------------------------------------------------
     * @return string
    +----------------------------------------------------------
     */
    static public function deleteHtmlTags($string)
    {
        while (strstr($string, '>')) {
            $currentBeg = strpos($string, '<');
            $currentEnd = strpos($string, '>');
            $tmpStringBeg = @substr($string, 0, $currentBeg);
            $tmpStringEnd = @substr($string, $currentEnd + 1, strlen($string));
            $string = $tmpStringBeg . $tmpStringEnd;
        }
        return $string;
    }

    /**
     * +----------------------------------------------------------
     * Wrap text processing
     * +----------------------------------------------------------
     * @access public
     * +----------------------------------------------------------
     * @param string $string The string to be processed
     * @param mixed $br To wrapdeal with,
     *        false: Removing the wrap;true: Leave as is;string: Replacestring
     * +----------------------------------------------------------
     * @return string
    +----------------------------------------------------------
     */
    static public function nl2($string, $br = '<br />')
    {
        if ($br == false) {
            $string = preg_replace("/(\015\012)|(\015)|(\012)/", '', $string);
        } elseif ($br != true) {
            $string = preg_replace("/(\015\012)|(\015)|(\012)/", $br, $string);
        }
        return $string;
    }
}