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

defined('THINK_PATH') or exit();

/**
 * Database waySessiondrive
 *    CREATE TABLE think_session (
 *      session_id varchar(255) NOT NULL,
 *      session_expire int(11) NOT NULL,
 *      session_data blob,
 *      UNIQUE KEY `session_id` (`session_id`)
 *    );
 * @category   Extend
 * @package  Extend
 * @subpackage  Driver.Session
 * @author    liu21st <liu21st@gmail.com>
 */
class SessionDb
{

    /**
     * SessionEffective time
     */
    protected $lifeTime = '';

    /**
     * sessionSaved dataStorehouse Name
     */
    protected $sessionTable = '';

    /**
     * Database handle
     */
    protected $hander = array();

    /**
     * turn onSession
     * @access public
     * @param string $savePath
     * @param mixed $sessName
     */
    public function open($savePath, $sessName)
    {
        $this->lifeTime = C('SESSION_EXPIRE') ? C('SESSION_EXPIRE') : ini_get('session.gc_maxlifetime');
        $this->sessionTable = C('SESSION_TABLE') ? C('SESSION_TABLE') : C("DB_PREFIX") . "session";
        //distributeddatabase
        $host = explode(',', C('DB_HOST'));
        $port = explode(',', C('DB_PORT'));
        $name = explode(',', C('DB_NAME'));
        $user = explode(',', C('DB_USER'));
        $pwd = explode(',', C('DB_PWD'));
        if (1 == C('DB_DEPLOY_TYPE')) {
            //Separate read and write
            if (C('DB_RW_SEPARATE')) {
                $w = floor(mt_rand(0, C('DB_MASTER_NUM') - 1));
                if (is_numeric(C('DB_SLAVE_NO'))) {//Designationserverread
                    $r = C('DB_SLAVE_NO');
                } else {
                    $r = floor(mt_rand(C('DB_MASTER_NUM'), count($host) - 1));
                }
                //the Lorddatabaselink
                $hander = mysql_connect(
                    $host[$w] . (isset($port[$w]) ? ':' . $port[$w] : ':' . $port[0]),
                    isset($user[$w]) ? $user[$w] : $user[0],
                    isset($pwd[$w]) ? $pwd[$w] : $pwd[0]
                );
                $dbSel = mysql_select_db(
                    isset($name[$w]) ? $name[$w] : $name[0]
                    , $hander);
                if (!$hander || !$dbSel)
                    return false;
                $this->hander[0] = $hander;
                //Fromdatabaselink
                $hander = mysql_connect(
                    $host[$r] . (isset($port[$r]) ? ':' . $port[$r] : ':' . $port[0]),
                    isset($user[$r]) ? $user[$r] : $user[0],
                    isset($pwd[$r]) ? $pwd[$r] : $pwd[0]
                );
                $dbSel = mysql_select_db(
                    isset($name[$r]) ? $name[$r] : $name[0]
                    , $hander);
                if (!$hander || !$dbSel)
                    return false;
                $this->hander[1] = $hander;
                return true;
            }
        }
        //Fromdatabaselink
        $r = floor(mt_rand(0, count($host) - 1));
        $hander = mysql_connect(
            $host[$r] . (isset($port[$r]) ? ':' . $port[$r] : ':' . $port[0]),
            isset($user[$r]) ? $user[$r] : $user[0],
            isset($pwd[$r]) ? $pwd[$r] : $pwd[0]
        );
        $dbSel = mysql_select_db(
            isset($name[$r]) ? $name[$r] : $name[0]
            , $hander);
        if (!$hander || !$dbSel)
            return false;
        $this->hander = $hander;
        return true;
    }

    /**
     * shut downSession
     * @access public
     */
    public function close()
    {
        if (is_array($this->hander)) {
            $this->gc($this->lifeTime);
            return (mysql_close($this->hander[0]) && mysql_close($this->hander[1]));
        }
        $this->gc($this->lifeTime);
        return mysql_close($this->hander);
    }

    /**
     * ReadSession
     * @access public
     * @param string $sessID
     */
    public function read($sessID)
    {
        $hander = is_array($this->hander) ? $this->hander[1] : $this->hander;
        $res = mysql_query("SELECT session_data AS data FROM " . $this->sessionTable . " WHERE session_id = '$sessID'   AND session_expire >" . time(), $hander);
        if ($res) {
            $row = mysql_fetch_assoc($res);
            return $row['data'];
        }
        return "";
    }

    /**
     * WriteSession
     * @access public
     * @param string $sessID
     * @param String $sessData
     */
    public function write($sessID, $sessData)
    {
        $hander = is_array($this->hander) ? $this->hander[0] : $this->hander;
        $expire = time() + $this->lifeTime;
        mysql_query("REPLACE INTO  " . $this->sessionTable . " (  session_id, session_expire, session_data)  VALUES( '$sessID', '$expire',  '$sessData')", $hander);
        if (mysql_affected_rows($hander))
            return true;
        return false;
    }

    /**
     * deleteSession
     * @access public
     * @param string $sessID
     */
    public function destroy($sessID)
    {
        $hander = is_array($this->hander) ? $this->hander[0] : $this->hander;
        mysql_query("DELETE FROM " . $this->sessionTable . " WHERE session_id = '$sessID'", $hander);
        if (mysql_affected_rows($hander))
            return true;
        return false;
    }

    /**
     * Session Garbage Collection
     * @access public
     * @param string $sessMaxLifeTime
     */
    public function gc($sessMaxLifeTime)
    {
        $hander = is_array($this->hander) ? $this->hander[0] : $this->hander;
        mysql_query("DELETE FROM " . $this->sessionTable . " WHERE session_expire < " . time(), $hander);
        return mysql_affected_rows($hander);
    }

    /**
     * turn onSession
     * @access public
     */
    public function execute()
    {
        session_set_save_handler(array(&$this, "open"),
            array(&$this, "close"),
            array(&$this, "read"),
            array(&$this, "write"),
            array(&$this, "destroy"),
            array(&$this, "gc"));
    }
}
