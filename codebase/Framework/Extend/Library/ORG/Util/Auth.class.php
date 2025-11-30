<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2011 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: luofei614 <weibo.com/luofei614>　
// +----------------------------------------------------------------------
/**
 * Class certification authority
 * Features:
 * 1,YesCorrectruleEnterRowAuthenticate,Not the nodesCertification.userCan be used as a noderulenameachieveThe nodesCertification.
 *      $auth=new Auth();  $auth->check('Rule Name','userid')
 * 2,Simultaneously on multipleruleEnterRowAuthenticate,andSet upmany linesruleofrelationship(ororand)
 *      $auth=new Auth();  $auth->check('rule1,rule2','userid','and')
 *      The third parameter isandWhen expressed,userneedAlso hasrule1And rules2permission. whenThe third parameter isorTime,Showuservalueneedhaveamong themOneconditionTo。The default isor
 * 3,OneuserYou can belongMoreuser group(think_auth_group_accesstable definitionTheuserBelongsuser group). weneedSet upEachuser groupWhat hasrule(think_auth_group It defines the user group permissions)
 *
 * 4,stand byruleexpression。
 *      inthink_auth_rule tableDefinedAruleTime,in casetypefor1, conditionFieldcandefinitionruleexpression。 As defined{score}>5  and {score}<100  It represents the users score5-100betweenWhen thisruleWillby。
 * @category ORG
 * @package ORG
 * @subpackage Util
 * @author luofei614<weibo.com/luofei614>
 */

//database
/*
-- ----------------------------
-- think_auth_rule,ruletable,
-- id:Primary key,name: Unique identification rules, title: Rule Chinese name status Status: is1Normal for the0Disable,condition：ruleexpression,forairShowexistonverification,not nullShowaccording toConditions verification
-- ----------------------------
 DROP TABLE IF EXISTS `think_auth_rule`;
CREATE TABLE `think_auth_rule` (  
    `id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,  
    `name` char(80) NOT NULL DEFAULT '',  
    `title` char(20) NOT NULL DEFAULT '',  
    `status` tinyint(1) NOT NULL DEFAULT '1',  
    `condition` char(100) NOT NULL DEFAULT '',  
    PRIMARY KEY (`id`),  
    UNIQUE KEY `name` (`name`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;
-- ----------------------------
-- think_auth_group user grouptable, 
-- id：Primary key, title:user groupChinesename, rules:user groupownruleid, More rules","Apart,status Status: is1Normal for the0Disable
-- ----------------------------
 DROP TABLE IF EXISTS `think_auth_group`;
CREATE TABLE `think_auth_group` ( 
    `id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT, 
    `title` char(100) NOT NULL DEFAULT '', 
    `status` tinyint(1) NOT NULL DEFAULT '1', 
    `rules` char(80) NOT NULL DEFAULT '', 
    PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;
-- ----------------------------
-- think_auth_group_access User group list
-- uid:userid,group_id:user groupid
-- ----------------------------
DROP TABLE IF EXISTS `think_auth_group_access`;
CREATE TABLE `think_auth_group_access` (  
    `uid` mediumint(8) unsigned NOT NULL,  
    `group_id` mediumint(8) unsigned NOT NULL, 
    UNIQUE KEY `uid_group_id` (`uid`,`group_id`),  
    KEY `uid` (`uid`), 
    KEY `group_id` (`group_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
 */

class Auth
{

    //defaultConfiguration
    protected $_config = array(
        'AUTH_ON' => true, //Certified switch
        'AUTH_TYPE' => 1, // Authenticatethe way,1Certification as always;2forlog inCertification.
        'AUTH_GROUP' => 'think_auth_group', //user groupdata Sheet Name
        'AUTH_GROUP_ACCESS' => 'think_auth_group_access', //User group list
        'AUTH_RULE' => 'think_auth_rule', //Permission rules table
        'AUTH_USER' => 'think_members'//User information table
    );

    public function __construct()
    {
        if (C('AUTH_CONFIG')) {
            //canSet upConfigurationitem AUTH_CONFIG, thisConfigurationitemforArray。
            $this->_config = array_merge($this->_config, C('AUTH_CONFIG'));
        }
    }

    //Get permission$name It can beString or arrayOr comma-delimited, uidfor Authenticated userid, $or WhetherorRelations,trueYes, nameforArray,as long asArrayThereOneconditionbythenby, IffalseneedCompleteconditionby。
    public function check($name, $uid, $relation = 'or')
    {
        if (!$this->_config['AUTH_ON'])
            return true;
        $authList = $this->getAuthList($uid);
        if (is_string($name)) {
            if (strpos($name, ',') !== false) {
                $name = explode(',', $name);
            } else {
                $name = array($name);
            }
        }
        $list = array(); //Have permissionname
        foreach ($authList as $val) {
            if (in_array($val, $name))
                $list[] = $val;
        }
        if ($relation == 'or' and !empty($list)) {
            return true;
        }
        $diff = array_diff($name, $list);
        if ($relation == 'and' and empty($diff)) {
            return true;
        }
        return false;
    }

    //obtainuser group,OutsideCantransfer
    public function getGroups($uid)
    {
        static $groups = array();
        if (isset($groups[$uid]))
            return $groups[$uid];
        $user_groups = M()->table($this->_config['AUTH_GROUP_ACCESS'] . ' a')->where("a.uid='$uid' and g.status='1'")->join($this->_config['AUTH_GROUP'] . " g on a.group_id=g.id")->select();
        $groups[$uid] = $user_groups ? $user_groups : array();
        return $groups[$uid];
    }

    //Obtaining permissions list
    protected function getAuthList($uid)
    {
        static $_authList = array();
        if (isset($_authList[$uid])) {
            return $_authList[$uid];
        }
        if (isset($_SESSION['_AUTH_LIST_' . $uid])) {
            return $_SESSION['_AUTH_LIST_' . $uid];
        }
        //ReaduserBelongsuser group
        $groups = $this->getGroups($uid);
        $ids = array();
        foreach ($groups as $g) {
            $ids = array_merge($ids, explode(',', trim($g['rules'], ',')));
        }
        $ids = array_unique($ids);
        if (empty($ids)) {
            $_authList[$uid] = array();
            return array();
        }
        //Readuser groupallCompetencerule
        $map = array(
            'id' => array('in', $ids),
            'status' => 1
        );
        $rules = M()->table($this->_config['AUTH_RULE'])->where($map)->select();
        //cyclerule,judgmentresult。
        $authList = array();
        foreach ($rules as $r) {
            if (!empty($r['condition'])) {
                //Conditions verification
                $user = $this->getUserInfo($uid);
                $command = preg_replace('/\{(\w*?)\}/', '$user[\'\\1\']', $r['condition']);
                //dump($command);//debug
                $condition='';
                @(eval('$condition=(' . $command . ');'));
                if ($condition) {
                    $authList[] = $r['name'];
                }
            } else {
                //Presence on the adoption
                $authList[] = $r['name'];
            }
        }
        $_authList[$uid] = $authList;
        if ($this->_config['AUTH_TYPE'] == 2) {
            //sessionresult
            $_SESSION['_AUTH_LIST_' . $uid] = $authList;
        }
        return $authList;
    }

    //Obtaining user information,according toTheir situationReaddatabase
    protected function getUserInfo($uid)
    {
        static $userinfo = array();
        if (!isset($userinfo[$uid])) {
            $userinfo[$uid] = M()->table($this->_config['AUTH_USER'])->find($uid);
        }
        return $userinfo[$uid];
    }

}
