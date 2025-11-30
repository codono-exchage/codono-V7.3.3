<?php
// +----------------------------------------------------------------------
// | TOPThink [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2010 http://topthink.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: support@codono.com
// +----------------------------------------------------------------------

defined('THINK_PATH') or exit();

/**
 * System behavior extension：FormsTokenForm
 * @category   Think
 * @package  Think
 * @subpackage  Behavior
 * @author   liu21st <liu21st@gmail.com>
 */
class TokenBuildBehavior extends Behavior
{
    // Behavioral parametersdefinition
    protected $options = array(
        'TOKEN_ON' => false,     // Open token verification
        'TOKEN_NAME' => '__hash__',    // TokenverificationThe hidden form field names
        'TOKEN_TYPE' => 'md5',   // TokenverificationHash rules
        'TOKEN_RESET' => true, // Whether to reset the error after the token
    );

    public function run(&$content)
    {
        if (C('TOKEN_ON')) {
            if (strpos($content, '{__TOKEN__}')) {
                // DesignationFormsTokenhideDomain location
                $content = str_replace('{__TOKEN__}', $this->buildToken(), $content);
            } elseif (preg_match('/<\/form(\s*)>/is', $content, $match)) {
                // intelligentFormFormsTokenhidearea
                $content = str_replace($match[0], $this->buildToken() . $match[0], $content);
            }
        } else {
            $content = str_replace('{__TOKEN__}', '', $content);
        }
    }

    // Create a form token
    private function buildToken()
    {
        $tokenName = C('TOKEN_NAME');
        $tokenType = C('TOKEN_TYPE');
        if (!isset($_SESSION[$tokenName])) {
            $_SESSION[$tokenName] = array();
        }
        // MarkcurrentpageUniqueness
        $tokenKey = md5($_SERVER['REQUEST_URI']);
        if (isset($_SESSION[$tokenName][$tokenKey])) {// 相同pagedoes not repeatFormsession
            $tokenValue = $_SESSION[$tokenName][$tokenKey];
        } else {
            $tokenValue = $tokenType(microtime(TRUE));
            $_SESSION[$tokenName][$tokenKey] = $tokenValue;
        }
        $token = '<input type="hidden" name="' . $tokenName . '" value="' . $tokenKey . '_' . $tokenValue . '" />';
        return $token;
    }
}