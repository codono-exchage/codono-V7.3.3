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

class CodeSwitch
{
    // Error Messages
    static private $error = array();
    // Tips
    static private $info = array();

    // recordingerror
    static private function error($msg)
    {
        self::$error[] = $msg;
    }

    // recordinginformation
    static private function info($info)
    {
        self::$info[] = $info;
    }

    /**
     * Transcoding function,On the wholefileEnterRowcodingChange
     * It supports the following conversion
     * GB2312,UTF-8 WITH BOMConverted toUTF-8
     * UTF-8,UTF-8 WITH BOMConverted toGB2312
     * @access public
     * @param string $filename filename
     * @param string $out_charset After conversiondocumentcoding,versusiconvConsistent use of parameters
     * @return void
     */
    static function DetectAndSwitch($filename, $out_charset)
    {
        $fpr = fopen($filename, "r");
        $char1 = fread($fpr, 1);
        $char2 = fread($fpr, 1);
        $char3 = fread($fpr, 1);

        $originEncoding = "";

        if ($char1 == chr(239) && $char2 == chr(187) && $char3 == chr(191))//UTF-8 WITH BOM
            $originEncoding = "UTF-8 WITH BOM";
        elseif ($char1 == chr(255) && $char2 == chr(254))//UNICODE LE
        {
            self::error("Not supported fromUNICODE LEConversion toUTF-8orGBcoding");
            fclose($fpr);
            return;
        } elseif ($char1 == chr(254) && $char2 == chr(255)) {//UNICODE BE
            self::error("Not supported fromUNICODE BEConversion toUTF-8orGBcoding");
            fclose($fpr);
            return;
        } else {//Nofilehead,maybeGBorUTF-8
            if (rewind($fpr) === false) {//Back tofileStartsection,Preparation byte by byteReadjudgmentcoding
                self::error($filename . "After the file pointer moves fail");
                fclose($fpr);
                return;
            }

            while (!feof($fpr)) {
                $char = fread($fpr, 1);
                //forEnglishCulture,GBwithUTF-8They are single-byteofASCIICode less than128The value
                if (ord($char) < 128)
                    continue;

                //forChinese characterGBThe first byte is coded110*****The second byte is10******(There are exceptions,For example, with the word)
                //UTF-8The first byte is coded1110****The second byte is10******The third byte is10******
                //Bitwise and outresultKeep upOnnon-starnumber相同,So should firstjudgmentUTF-8
                //becauseuseGBofMask Bitwise AND,UTF-8of111Get outofAlso110,So the firstjudgmentUTF-8
                if ((ord($char) & 224) == 224) {
                    //The firstOnebytejudgmentby
                    $char = fread($fpr, 1);
                    if ((ord($char) & 128) == 128) {
                        //The second bytejudgmentby
                        $char = fread($fpr, 1);
                        if ((ord($char) & 128) == 128) {
                            $originEncoding = "UTF-8";
                            break;
                        }
                    }
                }
                if ((ord($char) & 192) == 192) {
                    //The firstOnebytejudgmentby
                    $char = fread($fpr, 1);
                    if ((ord($char) & 128) == 128) {
                        //The second bytejudgmentby
                        $originEncoding = "GB2312";
                        break;
                    }
                }
            }
        }

        if (strtoupper($out_charset) == $originEncoding) {
            self::info("file" . $filename . "Transcoding check is completed,Original file encoding" . $originEncoding);
            fclose($fpr);
        } else {
            //fileneedturncode
            $originContent = "";

            if ($originEncoding == "UTF-8 WITH BOM") {
                //jump overThree bytes,Behind theContentcopyGet overutf-8Content
                fseek($fpr, 3);
                $originContent = fread($fpr, filesize($filename) - 3);
                fclose($fpr);
            } elseif (rewind($fpr) != false) {//no matter howUTF-8still isGB2312,Back tofileStartsection,Readcontent
                $originContent = fread($fpr, filesize($filename));
                fclose($fpr);
            } else {
                self::error("filecodingIncorrect or shifting the pointerfailure");
                fclose($fpr);
                return;
            }

            //Transcoding andsave document
            $content = iconv(str_replace(" WITH BOM", "", $originEncoding), strtoupper($out_charset), $originContent);
            $fpw = fopen($filename, "w");
            fwrite($fpw, $content);
            fclose($fpw);

            if ($originEncoding != "")
                self::info("To file" . $filename . "Transcoding is complete,Original file encoding" . $originEncoding . ",Converted file encoding" . strtoupper($out_charset));
            elseif ($originEncoding == "")
                self::info("file" . $filename . "Does not appear in Chinese,But it can be concluded not withBOMofUTF-8coding,No transcoding,It does not affect the use of");
        }
    }

    /**
     * Directory traversal function
     * @access public
     * @param string $path To traversetable of Contents Name
     * @param string $mode Traversal patterns,General admissionFILES,suchOnly returnbandpathdocumentname
     * @param array $file_types File extension filter array
     * @param int $maxdepth Traversal depth,-1It represents traverse to the bottom
     * @return void
     */
    static function searchdir($path, $mode = "FULL", $file_types = array(".html", ".php"), $maxdepth = -1, $d = 0)
    {
        if (substr($path, strlen($path) - 1) != '/')
            $path .= '/';
        $dirlist = array();
        if ($mode != "FILES")
            $dirlist[] = $path;
        if ($handle = @opendir($path)) {
            while (false !== ($file = readdir($handle))) {
                if ($file != '.' && $file != '..') {
                    $file = $path . $file;
                    if (!is_dir($file)) {
                        if ($mode != "DIRS") {
                            $extension = "";
                            $extpos = strrpos($file, '.');
                            if ($extpos !== false)
                                $extension = substr($file, $extpos, strlen($file) - $extpos);
                            $extension = strtolower($extension);
                            if (in_array($extension, $file_types))
                                $dirlist[] = $file;
                        }
                    } elseif ($d >= 0 && ($d < $maxdepth || $maxdepth < 0)) {
                        $result = self::searchdir($file . '/', $mode, $file_types, $maxdepth, $d + 1);
                        $dirlist = array_merge($dirlist, $result);
                    }
                }
            }
            closedir($handle);
        }
        if ($d == 0)
            natcasesort($dirlist);

        return ($dirlist);
    }

    /**
     * On the wholeprojecttable of ContentsmiddlePHPwithHTMLTravel document encoding conversion
     * @access public
     * @param string $app To traverse the path of the project
     * @param string $mode Traversal patterns,General admissionFILES,suchOnly returnbandpathdocumentname
     * @param array $file_types File extension filter array
     * @return void
     */
    static function CodingSwitch($app = "./", $charset = 'UTF-8', $mode = "FILES", $file_types = array(".html", ".php"))
    {
        self::info("note: programusedocumentcodingDetectalgorithmSome may be specialcharacterNot applicable");
        $filearr = self::searchdir($app, $mode, $file_types);
        foreach ($filearr as $file)
            self::DetectAndSwitch($file, $charset);
    }

    static public function getError()
    {
        return self::$error;
    }

    static public function getInfo()
    {
        return self::$info;
    }
}