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
// $Id: Session.class.php 2702 2012-02-02 12:35:01Z liu21st $

const HTTP_SESSION_STARTED = 1;
const HTTP_SESSION_CONTINUED = 2;

/**
 * +------------------------------------------------------------------------------
 * SessionManagement
 * +------------------------------------------------------------------------------
 * @category   Think
 * @package  Think
 * @subpackage  Util
 * @author    liu21st <liu21st@gmail.com>
 * @version   $Id: Session.class.php 2702 2012-02-02 12:35:01Z liu21st $
 * +------------------------------------------------------------------------------
 */
class Session
{

    /**
     * +----------------------------------------------------------
     * start upSession
     * +----------------------------------------------------------
     * @static
     * @access public
     * +----------------------------------------------------------
     * @return void
    +----------------------------------------------------------
     */
    static function start()
    {
        session_start();
        if (!isset($_SESSION['__HTTP_Session_Info'])) {
            $_SESSION['__HTTP_Session_Info'] = HTTP_SESSION_STARTED;
        } else {
            $_SESSION['__HTTP_Session_Info'] = HTTP_SESSION_CONTINUED;
        }
        Session::setExpire(C('SESSION_EXPIRE'));
    }

    /**
     * +----------------------------------------------------------
     * time outSession
     * +----------------------------------------------------------
     * @static
     * @access public
     * +----------------------------------------------------------
     * @return void
    +----------------------------------------------------------
     */
    static function pause()
    {
        session_write_close();
    }

    /**
     * +----------------------------------------------------------
     * ClearSession
     * +----------------------------------------------------------
     * @static
     * @access public
     * +----------------------------------------------------------
     * @return void
    +----------------------------------------------------------
     */
    static function clearLocal()
    {
        $local = Session::localName();
        unset($_SESSION[$local]);
    }

    /**
     * +----------------------------------------------------------
     * ClearSession
     * +----------------------------------------------------------
     * @static
     * @access public
     * +----------------------------------------------------------
     * @return void
    +----------------------------------------------------------
     */
    static function clear()
    {
        $_SESSION = array();
    }

    /**
     * +----------------------------------------------------------
     * destroySession
     * +----------------------------------------------------------
     * @static
     * @access public
     * +----------------------------------------------------------
     * @return void
    +----------------------------------------------------------
     */
    static function destroy()
    {
        unset($_SESSION);
        session_destroy();
    }

    /**
     * +----------------------------------------------------------
     * DetectSessionID
     * +----------------------------------------------------------
     * @static
     * @access public
     * +----------------------------------------------------------
     * @return void
    +----------------------------------------------------------
     */
    static function detectID()
    {
        if (session_id() != '') {
            return session_id();
        }
        if (Session::useCookies()) {
            if (isset($_COOKIE[Session::name()])) {
                return $_COOKIE[Session::name()];
            }
        } else {
            if (isset($_GET[Session::name()])) {
                return $_GET[Session::name()];
            }
            if (isset($_POST[Session::name()])) {
                return $_POST[Session::name()];
            }
        }
        return null;
    }

    /**
     * +----------------------------------------------------------
     * Get or set the currentSession name
     * +----------------------------------------------------------
     * @param string $name sessionname
     * +----------------------------------------------------------
     * @static
     * @access public
     * +----------------------------------------------------------
     * @return string Before returnSession name
     * +----------------------------------------------------------
     */
    static function name($name = null)
    {
        return isset($name) ? session_name($name) : session_name();
    }

    /**
     * +----------------------------------------------------------
     * Get or set the currentSessionID
     * +----------------------------------------------------------
     * @param string $id sessionID
     * +----------------------------------------------------------
     * @static
     * @access public
     * +----------------------------------------------------------
     * @return void Before returnsessionID
     * +----------------------------------------------------------
     */
    static function id($id = null)
    {
        return isset($id) ? session_id($id) : session_id();
    }

    /**
     * +----------------------------------------------------------
     * Get or set the currentSessionsave route
     * +----------------------------------------------------------
     * @param string $path save route Name
     * +----------------------------------------------------------
     * @access public
     * +----------------------------------------------------------
     * @return string
    +----------------------------------------------------------
     */
    static function path($path = null)
    {
        return !empty($path) ? session_save_path($path) : session_save_path();
    }

    /**
     * +----------------------------------------------------------
     * Set upSession Expiration
     * +----------------------------------------------------------
     * @param integer $time Expiration
     * @param boolean $add Whether to increase the time
     * +----------------------------------------------------------
     * @static
     * @access public
     * +----------------------------------------------------------
     * @return void
    +----------------------------------------------------------
     */
    static function setExpire($time, $add = false)
    {
        if ($add) {
            if (!isset($_SESSION['__HTTP_Session_Expire_TS'])) {
                $_SESSION['__HTTP_Session_Expire_TS'] = time() + $time;
            }

            // update session.gc_maxlifetime
            $currentGcMaxLifetime = Session::setGcMaxLifetime(null);
            Session::setGcMaxLifetime($currentGcMaxLifetime + $time);

        } elseif (!isset($_SESSION['__HTTP_Session_Expire_TS'])) {
            $_SESSION['__HTTP_Session_Expire_TS'] = $time;
        }
    }

    /**
     * +----------------------------------------------------------
     * Set upSession Idle Time
     * +----------------------------------------------------------
     * @param integer $time Idle Time
     * @param boolean $add Whether to increase the time
     * +----------------------------------------------------------
     * @static
     * @access public
     * +----------------------------------------------------------
     * @return void
    +----------------------------------------------------------
     */
    static function setIdle($time, $add = false)
    {
        if ($add) {
            $_SESSION['__HTTP_Session_Idle'] = $time;
        } else {
            $_SESSION['__HTTP_Session_Idle'] = $time - time();
        }
    }

    /**
     * +----------------------------------------------------------
     * ObtainSession Effective time
     * +----------------------------------------------------------
     * @static
     * @access public
     * +----------------------------------------------------------
     * @return void
    +----------------------------------------------------------
     */
    static function sessionValidThru()
    {
        if (!isset($_SESSION['__HTTP_Session_Idle_TS']) || !isset($_SESSION['__HTTP_Session_Idle'])) {
            return 0;
        } else {
            return $_SESSION['__HTTP_Session_Idle_TS'] + $_SESSION['__HTTP_Session_Idle'];
        }
    }

    /**
     * +----------------------------------------------------------
     * an examinationSession Has expired
     * +----------------------------------------------------------
     * @static
     * @access public
     * +----------------------------------------------------------
     * @return boolean
    +----------------------------------------------------------
     */
    static function isExpired()
    {
        if (isset($_SESSION['__HTTP_Session_Expire_TS']) && $_SESSION['__HTTP_Session_Expire_TS'] < time()) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * +----------------------------------------------------------
     * an examinationSession Are Idle
     * +----------------------------------------------------------
     * @static
     * @access public
     * +----------------------------------------------------------
     * @return void
    +----------------------------------------------------------
     */
    static function isIdle()
    {
        if (isset($_SESSION['__HTTP_Session_Idle_TS']) && (($_SESSION['__HTTP_Session_Idle_TS'] + $_SESSION['__HTTP_Session_Idle']) < time())) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * +----------------------------------------------------------
     * UpdateSession Idle Time
     * +----------------------------------------------------------
     * @static
     * @access public
     * +----------------------------------------------------------
     * @return void
    +----------------------------------------------------------
     */
    static function updateIdle()
    {
        $_SESSION['__HTTP_Session_Idle_TS'] = time();
    }

    /**
     * +----------------------------------------------------------
     * Set upSession ObjectsDeserialization timeofCallback
     * Set before returning
     * +----------------------------------------------------------
     * @param string $callback Callbackmethodname
     * +----------------------------------------------------------
     * @static
     * @access public
     * +----------------------------------------------------------
     * @return boolean
    +----------------------------------------------------------
     */
    static function setCallback($callback = null)
    {
        $return = ini_get('unserialize_callback_func');
        if (!empty($callback)) {
            ini_set('unserialize_callback_func', $callback);
        }
        return $return;
    }

    /**
     * +----------------------------------------------------------
     * Set upSession use or notcookie
     * Set before returning
     * +----------------------------------------------------------
     * @param boolean $useCookies use or notcookie
     * +----------------------------------------------------------
     * @static
     * @access public
     * +----------------------------------------------------------
     * @return boolean
    +----------------------------------------------------------
     */
    static function useCookies($useCookies = null)
    {
        $return = (bool)ini_get('session.use_cookies');
        if (isset($useCookies)) {
            ini_set('session.use_cookies', $useCookies ? 1 : 0);
        }
        return $return;
    }

    /**
     * +----------------------------------------------------------
     * an examinationSession Are New
     * +----------------------------------------------------------
     * @param boolean $useCookies use or notcookie
     * +----------------------------------------------------------
     * @static
     * @access public
     * +----------------------------------------------------------
     * @return boolean
    +----------------------------------------------------------
     */
    static function isNew()
    {
        return !isset($_SESSION['__HTTP_Session_Info']) ||
            $_SESSION['__HTTP_Session_Info'] == HTTP_SESSION_STARTED;
    }


    /**
     * +----------------------------------------------------------
     * Get current projectSession value
     * Set before returning
     * +----------------------------------------------------------
     * @param string $name
    +----------------------------------------------------------
     * @static
     * @access public
     * +----------------------------------------------------------
     * @return boolean
    +----------------------------------------------------------
     */
    static function getLocal($name)
    {
        $local = Session::localName();
        if (!is_array($_SESSION[$local])) {
            $_SESSION[$local] = array();
        }
        return $_SESSION[$local][$name];
    }

    /**
     * +----------------------------------------------------------
     * Get current projectSession value
     * Set before returning
     * +----------------------------------------------------------
     * @param string $name
    +----------------------------------------------------------
     * @static
     * @access public
     * +----------------------------------------------------------
     * @return boolean
    +----------------------------------------------------------
     */
    static function get($name): ?bool
    {
        return $_SESSION[$name] ?? null;
    }

    /**
     * +----------------------------------------------------------
     * Setting the current projectSession value
     * Set before returning
     * +----------------------------------------------------------
     * @param string $name
     * @param mixed $value
    +----------------------------------------------------------
     * @static
     * @access public
     * +----------------------------------------------------------
     * @return boolean
    +----------------------------------------------------------
     */
    static function setLocal($name, $value)
    {
        $local = Session::localName();
        if (!is_array($_SESSION[$local])) {
            $_SESSION[$local] = array();
        }
        if (null === $value) {
            unset($_SESSION[$local][$name]);
        } else {
            $_SESSION[$local][$name] = $value;
        }
        return;
    }

    /**
     * +----------------------------------------------------------
     * Setting the current projectSession value
     * Set before returning
     * +----------------------------------------------------------
     * @param string $name
     * @param mixed $value
    +----------------------------------------------------------
     * @static
     * @access public
     * +----------------------------------------------------------
     * @return boolean
    +----------------------------------------------------------
     */
    static function set($name, $value)
    {
        if (null === $value) {
            unset($_SESSION[$name]);
        } else {
            $_SESSION[$name] = $value;
        }
        return;
    }

    /**
     * +----------------------------------------------------------
     * an examinationSession Whether the value has been set
     * +----------------------------------------------------------
     * @param string $name
    +----------------------------------------------------------
     * @static
     * @access public
     * +----------------------------------------------------------
     * @return boolean
    +----------------------------------------------------------
     */
    static function is_setLocal($name)
    {
        $local = Session::localName();
        return isset($_SESSION[$local][$name]);
    }

    /**
     * +----------------------------------------------------------
     * an examinationSession Whether the value has been set
     * +----------------------------------------------------------
     * @param string $name
    +----------------------------------------------------------
     * @static
     * @access public
     * +----------------------------------------------------------
     * @return boolean
    +----------------------------------------------------------
     */
    static function is_set($name)
    {
        return isset($_SESSION[$name]);
    }

    /**
     * +----------------------------------------------------------
     * Set uporObtain Session localname
     * +----------------------------------------------------------
     * @param string $name
    +----------------------------------------------------------
     * @static
     * @access public
     * +----------------------------------------------------------
     * @return string
    +----------------------------------------------------------
     */
    static function localName($name = null)
    {
        $return = (isset($GLOBALS['__HTTP_Session_Localname'])) ? $GLOBALS['__HTTP_Session_Localname'] : null;
        if (!empty($name)) {
            $GLOBALS['__HTTP_Session_Localname'] = md5($name);
        }
        return $return;
    }

    /**
     * +----------------------------------------------------------
     * Session initialization
     * +----------------------------------------------------------
     * @static
     * @access private
     * +----------------------------------------------------------
     * @return boolean
    +----------------------------------------------------------
     */
    static function _init()
    {
        ini_set('session.auto_start', 0);
        if (is_null(Session::detectID())) {
            Session::id(uniqid(dechex(mt_rand())));
        }
        // Set upSessioneffectiveareaname
        Session::setCookieDomain(C('COOKIE_DOMAIN'));
        //Set upcurrentprojectrunscriptAs aSessionlocal Name
        Session::localName(APP_NAME);
        Session::name(C('SESSION_NAME'));
        Session::path(C('SESSION_PATH'));
        Session::setCallback(C('SESSION_CALLBACK'));
    }

    /**
     * +----------------------------------------------------------
     * Set upSession use_trans_sid
     * Set before returning
     * +----------------------------------------------------------
     * @param string $useTransSID
    +----------------------------------------------------------
     * @static
     * @access public
     * +----------------------------------------------------------
     * @return string
    +----------------------------------------------------------
     */
    static function useTransSID($useTransSID = null)
    {
        $return = (bool)ini_get('session.use_trans_sid');
        if (isset($useTransSID)) {
            ini_set('session.use_trans_sid', $useTransSID ? 1 : 0);
        }
        return $return;
    }

    /**
     * +----------------------------------------------------------
     * Set upSession cookie_domain
     * Set before returning
     * +----------------------------------------------------------
     * @param string $sessionDomain
    +----------------------------------------------------------
     * @static
     * @access public
     * +----------------------------------------------------------
     * @return string
    +----------------------------------------------------------
     */
    static function setCookieDomain($sessionDomain = null)
    {
        $return = ini_get('session.cookie_domain');
        if (!empty($sessionDomain)) {
            ini_set('session.cookie_domain', $sessionDomain);//Cross-domain accessSession
        }
        return $return;
    }


    /**
     * +----------------------------------------------------------
     * Set upSession gc_maxlifetimevalue
     * Set before returning
     * +----------------------------------------------------------
     * @param string $gc_maxlifetime
    +----------------------------------------------------------
     * @static
     * @access public
     * +----------------------------------------------------------
     * @return string
    +----------------------------------------------------------
     */
    static function setGcMaxLifetime($gcMaxLifetime = null)
    {
        $return = ini_get('session.gc_maxlifetime');
        if (isset($gcMaxLifetime) && is_int($gcMaxLifetime) && $gcMaxLifetime >= 1) {
            ini_set('session.gc_maxlifetime', $gcMaxLifetime);
        }
        return $return;
    }

    /**
     * +----------------------------------------------------------
     * Set upSession gc_probability value
     * Set before returning
     * +----------------------------------------------------------
     * @param string $gc_maxlifetime
    +----------------------------------------------------------
     * @static
     * @access public
     * +----------------------------------------------------------
     * @return string
    +----------------------------------------------------------
     */
    static function setGcProbability($gcProbability = null)
    {
        $return = ini_get('session.gc_probability');
        if (isset($gcProbability) && is_int($gcProbability) && $gcProbability >= 1 && $gcProbability <= 100) {
            ini_set('session.gc_probability', $gcProbability);
        }
        return $return;
    }

    /**
     * +----------------------------------------------------------
     * currentSessionfilename
     * +----------------------------------------------------------
     * @access public
     * +----------------------------------------------------------
     * @return string
    +----------------------------------------------------------
     */
    static function getFilename()
    {
        return Session::path() . '/sess_' . session_id();
    }

}//classdefinitionEnd
Session::_init();