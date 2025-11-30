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
// $Id: RBAC.class.php 2947 2012-05-13 15:57:48Z liu21st@gmail.com $

/**
 * +------------------------------------------------------------------------------
 * Role-basedofDatabase wayverificationclass
 * +------------------------------------------------------------------------------
 * @category   ORG
 * @package  ORG
 * @subpackage  Util
 * @author    liu21st <liu21st@gmail.com>
 * @version   $Id: RBAC.class.php 2947 2012-05-13 15:57:48Z liu21st@gmail.com $
 * +------------------------------------------------------------------------------
 */
//  Configuration file increaseSet up
// USER_AUTH_ON Whether to authenticate
// USER_AUTH_TYPE Authentication Type
// USER_AUTH_KEY Certification identification number
// REQUIRE_AUTH_MODULE  It requires authentication module
// NOT_AUTH_MODULE No authentication module
// USER_AUTH_GATEWAY Authentication Gateway
// RBAC_DB_DSN  Database ConnectivityDSN
// RBAC_ROLE_TABLE Role table name
// RBAC_USER_TABLE User table name
// RBAC_ACCESS_TABLE Permissions table name
// RBAC_NODE_TABLE Node table name
/*
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `think_access` (
  `role_id` smallint(6) unsigned NOT NULL,
  `node_id` smallint(6) unsigned NOT NULL,
  `level` tinyint(1) NOT NULL,
  `module` varchar(50) DEFAULT NULL,
  KEY `groupId` (`role_id`),
  KEY `nodeId` (`node_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `think_node` (
  `id` smallint(6) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(20) NOT NULL,
  `title` varchar(50) DEFAULT NULL,
  `status` tinyint(1) DEFAULT '0',
  `remark` varchar(255) DEFAULT NULL,
  `sort` smallint(6) unsigned DEFAULT NULL,
  `pid` smallint(6) unsigned NOT NULL,
  `level` tinyint(1) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `level` (`level`),
  KEY `pid` (`pid`),
  KEY `status` (`status`),
  KEY `name` (`name`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `think_role` (
  `id` smallint(6) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(20) NOT NULL,
  `pid` smallint(6) DEFAULT NULL,
  `status` tinyint(1) unsigned DEFAULT NULL,
  `remark` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `pid` (`pid`),
  KEY `status` (`status`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 ;

CREATE TABLE IF NOT EXISTS `think_role_user` (
  `role_id` mediumint(9) unsigned DEFAULT NULL,
  `user_id` char(32) DEFAULT NULL,
  KEY `group_id` (`role_id`),
  KEY `user_id` (`user_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
*/

class RBAC
{
    // Authenticatemethod
    static public function authenticate($map, $model = '')
    {
        if (empty($model)) $model = C('USER_AUTH_MODEL');
        //usegivenofMapEnterRowAuthenticate
        return M($model)->where($map)->find();
    }

    //ForDetectuserCompetenceofmethod,andStorageToSessionin
    static function saveAccessList($authId = null)
    {
        if (null === $authId) $authId = $_SESSION[C('USER_AUTH_KEY')];
        // in caseuseordinaryCompetencemode,StoragecurrentuserofaccessCompetenceList
        // AdministratorDevelopallCompetence
        if (C('USER_AUTH_TYPE') != 2 && !$_SESSION[C('ADMIN_AUTH_KEY')])
            $_SESSION['_ACCESS_LIST'] = RBAC::getAccessList($authId);
        return;
    }

    // ObtainModuleofBelongsrecordingaccessCompetenceList returnHave permissionrecordingIDArray
    static function getRecordAccessList($authId = null, $module = '')
    {
        if (null === $authId) $authId = $_SESSION[C('USER_AUTH_KEY')];
        if (empty($module)) $module = MODULE_NAME;
        //ObtainCompetenceaccessList
        $accessList = RBAC::getModuleAccessList($authId, $module);
        return $accessList;
    }

    //an examinationcurrentoperatingWhether to authenticate
    static function checkAccess()
    {
        //in caseprojectClaimAuthenticate,andcurrentModuleneedAuthenticate,Is performedCompetenceAuthenticate
        if (C('USER_AUTH_ON')) {
            $_module = array();
            $_action = array();
            if ("" != C('REQUIRE_AUTH_MODULE')) {
                //needAuthenticateofModule
                $_module['yes'] = explode(',', strtoupper(C('REQUIRE_AUTH_MODULE')));
            } else {
                //No needAuthenticateofModule
                $_module['no'] = explode(',', strtoupper(C('NOT_AUTH_MODULE')));
            }
            //an examinationcurrentModuleWhether to authenticate
            if ((!empty($_module['no']) && !in_array(strtoupper(MODULE_NAME), $_module['no'])) || (!empty($_module['yes']) && in_array(strtoupper(MODULE_NAME), $_module['yes']))) {
                if ("" != C('REQUIRE_AUTH_ACTION')) {
                    //needAuthenticateofoperating
                    $_action['yes'] = explode(',', strtoupper(C('REQUIRE_AUTH_ACTION')));
                } else {
                    //No needAuthenticateofoperating
                    $_action['no'] = explode(',', strtoupper(C('NOT_AUTH_ACTION')));
                }
                //an examinationcurrentoperatingWhether to authenticate
                if ((!empty($_action['no']) && !in_array(strtoupper(ACTION_NAME), $_action['no'])) || (!empty($_action['yes']) && in_array(strtoupper(ACTION_NAME), $_action['yes']))) {
                    return true;
                } else {
                    return false;
                }
            } else {
                return false;
            }
        }
        return false;
    }

    // log inan examination
    static public function checkLogin()
    {
        //an examinationcurrentoperatingWhether to authenticate
        if (RBAC::checkAccess()) {
            //an examinationCertification identification number
            if (!$_SESSION[C('USER_AUTH_KEY')]) {
                if (C('GUEST_AUTH_ON')) {
                    // OpenAuthorized visitorsaccess
                    if (!isset($_SESSION['_ACCESS_LIST']))
                        // StorageTouristCompetence
                        RBAC::saveAccessList(C('GUEST_AUTH_ID'));
                } else {
                    // BanTouristaccessJumpToAuthentication Gateway
                    redirect(PHP_FILE . C('USER_AUTH_GATEWAY'));
                }
            }
        }
        return true;
    }

    //CompetenceAuthenticateoffiltermethod
    static public function AccessDecision($appName = APP_NAME)
    {
        //an examinationWhether to authenticate
        if (RBAC::checkAccess()) {
            //existCertification identification number,The furtherofaccessdecision making
            $accessGuid = md5($appName . MODULE_NAME . ACTION_NAME);
            if (empty($_SESSION[C('ADMIN_AUTH_KEY')])) {
                if (C('USER_AUTH_TYPE') == 2) {
                    //strengthenverificationAnd instantverificationmode safer BackstageCompetencemodifyInstantBecome effective
                    //bydatabaseEnterRowaccessan examination
                    $accessList = RBAC::getAccessList($_SESSION[C('USER_AUTH_KEY')]);
                } else {
                    // ifadministratororcurrentoperatingalreadyAuthenticateLive,No need to once againAuthenticate
                    if ($_SESSION[$accessGuid]) {
                        return true;
                    }
                    //log inverificationmode,Comparelog inRearStorageofCompetenceaccessList
                    $accessList = $_SESSION['_ACCESS_LIST'];
                }
                //Determine whetherComponentizationmode,in caseYes,verificationIts whollymodulname
                $module = defined('P_MODULE_NAME') ? P_MODULE_NAME : MODULE_NAME;
                if (!isset($accessList[strtoupper($appName)][strtoupper($module)][strtoupper(ACTION_NAME)])) {
                    $_SESSION[$accessGuid] = false;
                    return false;
                } else {
                    $_SESSION[$accessGuid] = true;
                }
            } else {
                //Administrators do not needAuthenticate
                return true;
            }
        }
        return true;
    }

    /**
     * +----------------------------------------------------------
     * ObtaincurrentAuthenticatenumberofallCompetenceList
     * +----------------------------------------------------------
     * @param integer $authId userID
     * +----------------------------------------------------------
     * @access public
     * +----------------------------------------------------------
     */
    static public function getAccessList($authId)
    {
        // DbWay data permissions
        $db = Db::getInstance(C('RBAC_DB_DSN'));
        $table = array('role' => C('RBAC_ROLE_TABLE'), 'user' => C('RBAC_USER_TABLE'), 'access' => C('RBAC_ACCESS_TABLE'), 'node' => C('RBAC_NODE_TABLE'));
        $sql = "select node.id,node.name from " .
            $table['role'] . " as role," .
            $table['user'] . " as user," .
            $table['access'] . " as access ," .
            $table['node'] . " as node " .
            "where user.user_id='{$authId}' and user.role_id=role.id and ( access.role_id=role.id  or (access.role_id=role.pid and role.pid!=0 ) ) and role.status=1 and access.node_id=node.id and node.level=1 and node.status=1";
        $apps = $db->query($sql);
        $access = array();
        foreach ($apps as $key => $app) {
            $appId = $app['id'];
            $appName = $app['name'];
            // ReadprojectofModuleCompetence
            $access[strtoupper($appName)] = array();
            $sql = "select node.id,node.name from " .
                $table['role'] . " as role," .
                $table['user'] . " as user," .
                $table['access'] . " as access ," .
                $table['node'] . " as node " .
                "where user.user_id='{$authId}' and user.role_id=role.id and ( access.role_id=role.id  or (access.role_id=role.pid and role.pid!=0 ) ) and role.status=1 and access.node_id=node.id and node.level=2 and node.pid={$appId} and node.status=1";
            $modules = $db->query($sql);
            // Determine whether therepublicModuleofCompetence
            $publicAction = array();
            foreach ($modules as $key => $module) {
                $moduleId = $module['id'];
                $moduleName = $module['name'];
                if ('PUBLIC' == strtoupper($moduleName)) {
                    $sql = "select node.id,node.name from " .
                        $table['role'] . " as role," .
                        $table['user'] . " as user," .
                        $table['access'] . " as access ," .
                        $table['node'] . " as node " .
                        "where user.user_id='{$authId}' and user.role_id=role.id and ( access.role_id=role.id  or (access.role_id=role.pid and role.pid!=0 ) ) and role.status=1 and access.node_id=node.id and node.level=3 and node.pid={$moduleId} and node.status=1";
                    $rs = $db->query($sql);
                    foreach ($rs as $a) {
                        $publicAction[$a['name']] = $a['id'];
                    }
                    unset($modules[$key]);
                    break;
                }
            }
            // SuccessivelyReadModuleofoperatingCompetence
            foreach ($modules as $key => $module) {
                $moduleId = $module['id'];
                $moduleName = $module['name'];
                $sql = "select node.id,node.name from " .
                    $table['role'] . " as role," .
                    $table['user'] . " as user," .
                    $table['access'] . " as access ," .
                    $table['node'] . " as node " .
                    "where user.user_id='{$authId}' and user.role_id=role.id and ( access.role_id=role.id  or (access.role_id=role.pid and role.pid!=0 ) ) and role.status=1 and access.node_id=node.id and node.level=3 and node.pid={$moduleId} and node.status=1";
                $rs = $db->query($sql);
                $action = array();
                foreach ($rs as $a) {
                    $action[$a['name']] = $a['id'];
                }
                // withpublicModuleofoperatingCompetencemerge
                $action += $publicAction;
                $access[strtoupper($appName)][strtoupper($moduleName)] = array_change_key_case($action, CASE_UPPER);
            }
        }
        return $access;
    }

    // ReadModulebelong torecordingaccessCompetence
    static public function getModuleAccessList($authId, $module)
    {
        // Dbthe way
        $db = Db::getInstance(C('RBAC_DB_DSN'));
        $table = array('role' => C('RBAC_ROLE_TABLE'), 'user' => C('RBAC_USER_TABLE'), 'access' => C('RBAC_ACCESS_TABLE'));
        $sql = "select access.node_id from " .
            $table['role'] . " as role," .
            $table['user'] . " as user," .
            $table['access'] . " as access " .
            "where user.user_id='{$authId}' and user.role_id=role.id and ( access.role_id=role.id  or (access.role_id=role.pid and role.pid!=0 ) ) and role.status=1 and  access.module='{$module}' and access.status=1";
        $rs = $db->query($sql);
        $access = array();
        foreach ($rs as $node) {
            $access[] = $node['node_id'];
        }
        return $access;
    }
}