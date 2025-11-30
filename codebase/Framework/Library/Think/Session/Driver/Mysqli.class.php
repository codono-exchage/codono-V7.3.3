<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2014 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: hainuo<admin@hainuo.info> liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
// | change  mysql to mysqli  solvephp7NomysqlWhen the database storage expansionsessionProblem can not be operated
// +----------------------------------------------------------------------
namespace Think\Session\Driver;
/**
 * Database waySessiondrive
 *    CREATE TABLE think_session (
 *      session_id varchar(255) NOT NULL,
 *      session_expire int(11) NOT NULL,
 *      session_data blob,
 *      UNIQUE KEY `session_id` (`session_id`)
 *    );
 */
class Mysqli
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
                $hander = mysqli_connect(
                    $host[$w] . (isset($port[$w]) ? ':' . $port[$w] : ':' . $port[0]),
                    $user[$w] ?? $user[0],
                    $pwd[$w] ?? $pwd[0]
                );
                $dbSel = mysqli_select_db(
                    $hander,
                    $name[$w] ?? $name[0]
                );
                if (!$hander || !$dbSel)
                    return false;
                $this->hander[0] = $hander;
                //Fromdatabaselink
                $hander = mysqli_connect(
                    $host[$r] . (isset($port[$r]) ? ':' . $port[$r] : ':' . $port[0]),
                    $user[$r] ?? $user[0],
                    $pwd[$r] ?? $pwd[0]
                );
                $dbSel = mysqli_select_db(
                    $hander,
                    $name[$r] ?? $name[0]
                );
                if (!$hander || !$dbSel)
                    return false;
                $this->hander[1] = $hander;
                return true;
            }
        }
        //Fromdatabaselink
        $r = floor(mt_rand(0, count($host) - 1));
        $hander = mysqli_connect(
            $host[$r] . (isset($port[$r]) ? ':' . $port[$r] : ':' . $port[0]),
            $user[$r] ?? $user[0],
            $pwd[$r] ?? $pwd[0]
        );
        $dbSel = mysqli_select_db(
            $hander,
            $name[$r] ?? $name[0]
        );
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
            return (mysqli_close($this->hander[0]) && mysqli_close($this->hander[1]));
        }
        $this->gc($this->lifeTime);
        return mysqli_close($this->hander);
    }

    /**
     * ReadSession
     * @access public
     * @param string $sessID
     */
    public function read($sessID)
    {
        $sessID = mysqli_real_escape_string($this->hander, $sessID);
        $query = "SELECT session_data AS data FROM " . $this->sessionTable . " WHERE session_id = ? AND session_expire > ?";
        $stmt = mysqli_prepare($this->hander, $query);
        mysqli_stmt_bind_param($stmt, 'si', $sessID, time());
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);
        return $row ? $row['data'] : '';
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
        $expire = bcadd(time() , $this->lifeTime,0);
        mysqli_query($hander, "REPLACE INTO  " . $this->sessionTable . " (  session_id, session_expire, session_data)  VALUES( '$sessID', '$expire',  '$sessData')");
        if (mysqli_affected_rows($hander))
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
        mysqli_query($hander, "DELETE FROM " . $this->sessionTable . " WHERE session_id = '$sessID'");
        if (mysqli_affected_rows($hander))
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
        mysqli_query($hander, "DELETE FROM " . $this->sessionTable . " WHERE session_expire < " . time());
        return mysqli_affected_rows($hander);
    }

}
