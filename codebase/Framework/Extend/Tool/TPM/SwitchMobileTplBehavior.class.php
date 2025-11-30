<?php

class SwitchMobileTplBehavior extends Behavior
{
    //Intelligent switching template engine
    public function run(&$params)
    {
        if (isset($_SERVER['HTTP_CLIENT']) && 'PhoneClient' == $_SERVER['HTTP_CLIENT']) {
            C('TMPL_ENGINE_TYPE', 'Mobile');
            define('IS_CLIENT', true);
        } else {
            define('IS_CLIENT', false);
            if ('./client/' == TMPL_PATH) {
                $find = APP_TMPL_PATH;
                $replace = __ROOT__ . '/client/';
                $parse_string = C('TMPL_PARSE_STRING');
                if (is_null($parse_string)) $parse_string = array();
                //automaticincreaseOneTemplate substitution variables,For修复SAEUnder the platform used in the template../Public It parses the wrong question.
                C('TMPL_PARSE_STRING', array_merge($parse_string, array($find => $replace)));
                //judgmentifWindows Cloud DebuggeraccessJumpaccessHomeToclienttable of Contents
                if (APP_DEBUG && '' == __INFO__ && preg_match('/android|iphone/i', $_SERVER['HTTP_USER_AGENT'])) {
                    redirect(__ROOT__ . '/client');
                    exit();
                }
            }
        }
    }
}
