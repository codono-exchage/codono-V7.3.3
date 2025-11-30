<?php

/* Dragon excavator 2.0formal edition
 * textextract,analysis,canAutomatically determine thecoding,automaticturncode
 * principle：according toCodeWeighting blockofprinciple,FirstHTMLDivided into several pieces,ThenEachSmall score。
 * Take scores3MinuteAboveCodePiecemiddlecontentreturn
 * bonus 1 Contain punctuation
 *        2 contain<p>label
 *        3 contain<br>label
 * Less breakdown 1 containlilabel
 *        2 It does not contain any punctuation
 *        3 Keywords containedjavascript
 *        4 It does not contain anyChineseof,directdelete
 *        5 Have<li><asuchlabel
 * Example:
 * $he = new HtmlExtractor();
 * $str = $he->text($html);
 * among them$htmlIt is a pageHTMLCode,$strThe text is returned, the bodycodingYesutf-8of
 */

class HtmlExtractor
{

    /*
     * ObtainChinese characterofThe number of(Currently less accurate)
     */
    function chineseCount($str)
    {
        $count = preg_match_all("/[\xB0-\xF7][\xA1-\xFE]/", $str, $ff);
        return $count;
    }

    /*
     * Determining whether a text isUTF-8,in caseNo,Then to turn intoUTF-8
     */
    function getutf8($str)
    {
        if (!$this->is_utf8(substr(strip_tags($str), 0, 500))) {
            $str = $this->auto_charset($str, "gbk", "utf-8");
        }
        return $str;
    }

    function is_utf8($string)
    {
        if (preg_match("/^([" . chr(228) . "-" . chr(233) . "]{1}[" . chr(128) . "-" . chr(191) . "]{1}[" . chr(128) . "-" . chr(191) . "]{1}){1}/", $string) == true || preg_match("/([" . chr(228) . "-" . chr(233) . "]{1}[" . chr(128) . "-" . chr(191) . "]{1}[" . chr(128) . "-" . chr(191) . "]{1}){1}$/", $string) == true || preg_match("/([" . chr(228) . "-" . chr(233) . "]{1}[" . chr(128) . "-" . chr(191) . "]{1}[" . chr(128) . "-" . chr(191) . "]{1}){2,}/", $string) == true) {
            return true;
        } else {
            return false;
        }
    }

    /*
     * automaticChangecharacterCollection,Support arrays and strings
     */
    function auto_charset($fContents, $from, $to)
    {
        $from = strtoupper($from) == 'UTF8' ? 'utf-8' : $from;
        $to = strtoupper($to) == 'UTF8' ? 'utf-8' : $to;
        if (strtoupper($from) === strtoupper($to) || empty($fContents) || (is_scalar($fContents) && !is_string($fContents))) {
            //in casecoding相同ornon-StringScalar quantitythen不Change
            return $fContents;
        }
        if (is_string($fContents)) {
            if (function_exists('mb_convert_encoding')) {
                return mb_convert_encoding($fContents, $to, $from);
            } elseif (function_exists('iconv')) {
                return iconv($from, $to, $fContents);
            } else {
                return $fContents;
            }
        } elseif (is_array($fContents)) {
            foreach ($fContents as $key => $val) {
                $_key = $this->auto_charset($key, $from, $to);
                $fContents[$_key] = $this->auto_charset($val, $from, $to);
                if ($key != $_key)
                    unset($fContents[$key]);
            }
            return $fContents;
        } else {
            return $fContents;
        }
    }

    /*
     * Perform text extraction operation
     */
    function text($str)
    {
        $str = $this->clear($str);
        $str = $this->getutf8($str);
        $divList = $this->divList($str);
        $content = array();
        foreach ($divList[0] as $k => $v) {
            //First of alljudgment,in caseThis onecontentPieceofChinese characterQuantityThe total stationQuantityofMore than half,ThendirectRetention
            //Alsojudgment,YesNoOneAlabelThe wholecontentAll扩上
            if ($this->chineseCount($v) / (strlen($v) / 3) >= 0.4 && $this->checkHref($v)) {
                array_push($content, strip_tags($v, "<p><br>"));
            } else if ($this->makeScore($v) >= 3) {
                //thenaccording tofractionjudgment,in casemore than the3Points, reserved
                array_push($content, strip_tags($v, "<p><br>"));
            } else {
                //These are excludedContentThe
            }
        }
        return implode("", $content);
    }

    /*
     * It is not a judgeAlabelThe wholecontentAll扩上
     * Analyzing method:AlabelAnd itContentAllRemoveRear,看whetheralsocontainChinese
     */
    private function checkHref($str)
    {
        if (!preg_match("'<a[^>]*?>(.*)</a>'si", $str)) {
            //If it does notAlabel,That do not have control,99%It is the text
            return true;
        }
        $clear_str = preg_replace("'<a[^>]*?>(.*)</a>'si", "", $str);
        if ($this->chineseCount($clear_str)) {
            return true;
        } else {
            return false;
        }
    }

    function makeScore($str)
    {
        $score = 0;
        //Punctuation Score
        $score += $this->score1($str);
        //Judgment containsPlabel
        $score += $this->score2($str);
        //To determine whether they containbrlabel
        $score += $this->score3($str);
        //To determine whether they containlilabel
        $score -= $this->score4($str);
        //Determine whetherIt does not contain any punctuation
        $score -= $this->score5($str);
        //judgmentjavascriptKeyword
        $score -= $this->score6($str);
        //judgment<li><aSuch labels
        $score -= $this->score7($str);
        return $score;
    }

    /*
     * Determine whether there is punctuation
     */
    private function score1($str)
    {
        //Get the number of punctuation
        $count = preg_match_all("/(,|。|!|(|)|“|”|；|《|》|,)/si", $str, $out);
        if ($count) {
            return $count * 2;
        } else {
            return 0;
        }
    }

    /*
     * To determine whether they containPlabel
     */
    private function score2($str)
    {
        $count = preg_match_all("'<p[^>]*?>.*?</p>'si", $str, $out);
        return $count * 2;
    }

    /*
     * To determine whether they containBRlabel
     */
    private function score3($str)
    {
        $count = preg_match_all("'<br/>'si", $str, $out) + preg_match_all("'<br>'si", $str, $out);
        return $count * 2;
    }

    /*
     * To determine whether they containlilabel
     */
    private function score4($str)
    {
        //How many, how many points reduction * 2
        $count = preg_match_all("'<li[^>]*?>.*?</li>'si", $str, $out);
        return $count * 2;
    }

    /*
     * Determine whetherIt does not contain any punctuation
     */
    private function score5($str)
    {
        if (!preg_match_all("/(,|。|!|(|)|“|”|；|《|》|,|[|])/si", $str, $out)) {
            return 2;
        } else {
            return 0;
        }
    }

    /*
     * Determine whether to includejavascriptKeyword,theres a few,Less somewhat
     */
    private function score6($str)
    {
        $count = preg_match_all("'javascript'si", $str, $out);
        return $count;
    }

    /*
     * judgment<li><aSuch labels,theres a few,Less somewhat
     */
    private function score7($str)
    {
        $count = preg_match_all("'<li[^>]*?>.*?<a'si", $str, $out);
        return $count * 2;
    }

    /*
     * De-noising
     */
    private function clear($str)
    {
        $str = preg_replace("'<script[^>]*?>.*?</script>'si", "", $str);
        $str = preg_replace("'<style[^>]*?>.*?</style>'si", "", $str);
        $str = preg_replace("'<!--.*?-->'si", "", $str);
        return $str;
    }

    /*
     * Obtain content block
     */
    private function divList($str)
    {
        preg_match_all("'<[^a][^>]*?>.*?</[^>]*?>'si", $str, $divlist);
        return $divlist;
    }
}