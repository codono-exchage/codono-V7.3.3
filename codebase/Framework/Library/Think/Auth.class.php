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
namespace Think;
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
    `type` tinyint(1) NOT NULL DEFAULT '1',    
    `status` tinyint(1) NOT NULL DEFAULT '1',  
    `condition` char(100) NOT NULL DEFAULT '',  # Regulations Appendix conditions,Meet the additional conditions of rule,Only considered valid rules
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
        'AUTH_ON' => true,                      // Certified switch
        'AUTH_TYPE' => 1,                         // Authenticatethe way,1Real-time certification;2forlog inCertification.
        'AUTH_GROUP' => 'auth_group',        // user groupdata Sheet Name
        'AUTH_GROUP_ACCESS' => 'auth_group_access', // user-Users of relational tables
        'AUTH_RULE' => 'auth_rule',         // Permission rules table
        'AUTH_USER' => 'member'             // User information table
    );

    public function __construct()
    {
        $prefix = C('DB_PREFIX');
        $this->_config['AUTH_GROUP'] = $prefix . $this->_config['AUTH_GROUP'];
        $this->_config['AUTH_RULE'] = $prefix . $this->_config['AUTH_RULE'];
        $this->_config['AUTH_USER'] = $prefix . $this->_config['AUTH_USER'];
        $this->_config['AUTH_GROUP_ACCESS'] = $prefix . $this->_config['AUTH_GROUP_ACCESS'];
        if (C('AUTH_CONFIG')) {
            //canSet upConfigurationitem AUTH_CONFIG, thisConfigurationitemforArray。
            $this->_config = array_merge($this->_config, C('AUTH_CONFIG'));
        }
    }


    /**
     * @param $name
     * @param $uid
     * @param $type
     * @param $mode
     * @param $relation
     * @return bool
     */
    public function check($name, $uid, $type = 1, $mode = 'url', $relation = 'or')
    {
        if (!$this->_config['AUTH_ON'])
            return true;
        $authList = $this->getAuthList($uid, $type); //ObtainuserneedverificationofalleffectiveruleList
        if (is_string($name)) {
            $name = strtolower($name);
            if (strpos($name, ',') !== false) {
                $name = explode(',', $name);
            } else {
                $name = array($name);
            }
        }
        $list = array(); //Storageverificationbyofrule Name
        if ($mode == 'url') {
            $REQUEST = unserialize(strtolower(serialize($_REQUEST)));
        }
        foreach ($authList as $auth) {
            $query = preg_replace('/^.+\?/U', '', $auth);
            if ($mode == 'url' && $query != $auth) {
                parse_str($query, $param); //Parsing rulesparam
                $intersect = array_intersect_assoc($REQUEST, $param);
                $auth = preg_replace('/\?.*$/U', '', $auth);
                if (in_array($auth, $name) && $intersect == $param) {  //If the node is consistent andurlParameters to meet
                    $list[] = $auth;
                }
            } else if (in_array($auth, $name)) {
                $list[] = $auth;
            }
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

    /**
     * According to useridObtain a user group,The return value is an array
     * @param  $uid int     userid
     * @return array       User group the user belongs array(
     *     array('uid'=>'userid','group_id'=>'user groupid','title'=>'User Group Name','rules'=>'User groups have rulesid,More,No. apart'),
     *     ...)
     */
    public function getGroups($uid)
    {
        static $groups = array();
        if (isset($groups[$uid]))
            return $groups[$uid];
        $user_groups = M()
            ->table($this->_config['AUTH_GROUP_ACCESS'] . ' a')
            ->where("a.uid='$uid' and g.status='1'")
            ->join($this->_config['AUTH_GROUP'] . " g on a.group_id=g.id")
            ->field('uid,group_id,title,rules')->select();
        $groups[$uid] = $user_groups ?: array();
        return $groups[$uid];
    }

    /**
     * Obtaining permissions list
     * @param integer $uid userid
     * @param integer $type
     */
    protected function getAuthList($uid, $type)
    {
        static $_authList = array(); //StorageuserverificationbyofCompetenceList
        $t = implode(',', (array)$type);
        if (isset($_authList[$uid . $t])) {
            return $_authList[$uid . $t];
        }
        if ($this->_config['AUTH_TYPE'] == 2 && isset($_SESSION['_AUTH_LIST_' . $uid . $t])) {
            return $_SESSION['_AUTH_LIST_' . $uid . $t];
        }

        //ReaduserBelongsuser group
        $groups = $this->getGroups($uid);
        $ids = array();//StorageuserBelongsuser groupSet upofallCompetenceruleid
        foreach ($groups as $g) {
            $ids = array_merge($ids, explode(',', trim($g['rules'], ',')));
        }
        $ids = array_unique($ids);
        if (empty($ids)) {
            $_authList[$uid . $t] = array();
            return array();
        }

        $map = array(
            'id' => array('in', $ids),
            'type' => $type,
            'status' => 1,
        );
        //Readuser groupallCompetencerule
        $rules = M()->table($this->_config['AUTH_RULE'])->where($map)->field('condition,name')->select();

        //cyclerule,judgmentresult。
        $authList = array();   //
        foreach ($rules as $rule) {
            if (!empty($rule['condition'])) { //according toconditionauthenticating
                $user = $this->getUserInfo($uid);//Obtaining user information,One-dimensional array
                $condition=false;
                $command = preg_replace('/\{(\w*?)}/', '$user[\'\\1\']', $rule['condition']);
                //dump($command);//debug
                @(eval('$condition=(' . $command . ');'));
                if ($condition) {
                    $authList[] = strtolower($rule['name']);
                }
            } else {
                //as long asexistonrecording
                $authList[] = strtolower($rule['name']);
            }
        }
        $_authList[$uid . $t] = $authList;
        if ($this->_config['AUTH_TYPE'] == 2) {
            //Save results to a list of rulessession
            $_SESSION['_AUTH_LIST_' . $uid . $t] = $authList;
        }
        return array_unique($authList);
    }

    /**
     * Obtaining user information,according toTheir situationReaddatabase
     */
    protected function getUserInfo($uid)
    {
        static $userinfo = array();
        if (!isset($userinfo[$uid])) {
            $userinfo[$uid] = M()->where(array('uid' => $uid))->table($this->_config['AUTH_USER'])->find();
        }
        return $userinfo[$uid];
    }

}
