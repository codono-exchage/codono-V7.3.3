<?php

namespace Admin\Controller;
use Common\Ext\GoogleAuthenticator;
use Think\Page;
use Think\Upload;

class UserController extends AdminController
{
    const allowed_types = ALLOWED_TYPES;  // type of account balances
    public function index($name = NULL, $field = NULL, $status = NULL)
    {
        $where = " 1 ";
        if ($field && $name) {

            if ($field == "email") {
                $where = "`email` LIKE '%" . $name . "%'";
            } elseif ($field == "truename") {
                $where = "`truename` LIKE '%" . $name . "%'";
            } else {
                $where = "`" . $field . "` = '" . $name . "'";
            }

        }

        if ($status) {
            if ($status > 2) {
                switch ($status) {
                    case "3":
                        $where = $where . " and `awardstatus`=1 ";
                        break;
                    case "4":
                        $where = $where . " and `awardstatus`=0 ";
                        break;
                    case "5":
                        $where = $where . " and `idcardauth`=1 ";
                        break;
                    case "6":
                        $where = $where . " and `idcardauth`=2 ";
                        break;
                    case "7": //Not submitted
                        $where = $where . " and `idcardauth`=0 ";
                        break;
                    case "8": //Rejected
                        $where = $where . " and `idcardauth`=3 ";
                        break;
                }

            } else {

                $where = $where . " and `status`=" . ($status - 1);
            }
        }

        $count = M('User')->where($where)->count();
        $Page = new Page($count, 15);
        $show = $Page->show();
        $list = M('User')->where($where)->order('id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();

        foreach ($list as $k => $v) {
            $list[$k]['invit_1'] = M('User')->where(array('id' => $v['invit_1']))->getField('username');
            $list[$k]['invit_2'] = M('User')->where(array('id' => $v['invit_2']))->getField('username');
            $list[$k]['invit_3'] = M('User')->where(array('id' => $v['invit_3']))->getField('username');
        }
       
        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->display();
    }

    public function edit($id = NULL)
    {
        $input=I('post.');
        $id=I('request.id');
        $level_info=$this->getLevelInfo($id);        
        if (empty($input)) {
            $user = [];
            $imgstr = "";
            if (empty($id)) {
                $this->data = null;
            } else {
                $user = M('User')->where(array('id' => trim($id)))->find();
                $this->data = $user;
            }


            if ($user['idcardimg1']) {
                $img_arr = array();
                $img_arr = explode("_", $user['idcardimg1']);

                foreach ($img_arr as $k => $v) {
                    $imgstr = $imgstr . '<a href="/Upload/idcard/' . $v . '" target="_blank"><span class="glyphicon glyphicon-link"></span><embed src="/Upload/idcard/' . $v . '"  style="width:200px;height:100px;" /></a>';
                }

                unset($img_arr);
            }

            $this->assign('userimg', $imgstr);
            $this->assign('level_info',$level_info);
            $this->display();
        } else {
            
            if (APP_DEMO) {
                $this->error(L('SYSTEM_IN_DEMO_MODE'));
            }
                
            if ($id > 0) {
                $google_2fa_disable=false;
                if($_POST['google_2fa']==0){
                    unset($_POST['google_2fa']);
                    $google_2fa_disable=true;
                }
                
                $user = M('User')->where(array('id' => trim($id)))->find();
                if ($user['idcardauth'] != $_POST['idcardauth']) {
                    $to_email = $user['email'];
                    if ($_POST['idcardauth'] == 1) {

                        //success
                        //@todo add bonus
                        $bonus_Added = false;
                        $type = 'kyc';
                        $received_yet = $this->BonusReceiveCount($user['id'], $type);

                        $result = $this->checkBonus($user['id'], $type);

                        if ($received_yet < 1) {

                            $receivable_bonus = $result['amount'] . $result['coin'];
                            $coin = $result['coin'];
                            $amount = $result['amount'];
                            //$bonus_Added=$this->sendBonus($user['id'],$coin,$amount,$type);

                        }

                        $subject = "Congrats your kyc has been approved!";
                        if (!$bonus_Added) {
                            $content = "Hello there<br/> Your KYC on " . SHORT_NAME . " has been approved ! <br/> Happy trading !";
                        } else {
                            $content = "Hello there<br/> Your KYC on " . SHORT_NAME . " has been approved !<br/> and you received " . $receivable_bonus . ". <br/> Happy trading !";
                        }
                        addnotification($to_email,$subject,$content);
                    }
                    if ($_POST['idcardauth'] == 3) {
                        //rejected
                        $subject = "KYC on " . SHORT_NAME . " needs your attention!";
                        $content = "Hello there<br/> Your KYC on " . SHORT_NAME . " has needs some attention and changes !<br/><strong>" . $_POST['kyc_comment'] . "</strong> <br/> !";
                        addnotification($to_email,$subject,$content);
                    }
                }

            }
            /*
            if($_POST['kyc_comment'] ){
            $kyc_comment=$_POST['kyc_comment'];
            $kyc_status=$_POST['idcardauth'];
            }
            */
            unset($_POST['password']);
            if ($_POST['passwd']) {

                $_POST['password'] = md5($_POST['passwd']);
                unset($_POST['passwd']);
            } else {
                unset($_POST['passwd']);
            }

            if ($_POST['paypassword']) {
                $_POST['paypassword'] = md5($_POST['paypassword']);
            } else {
                unset($_POST['paypassword']);
            }
            //	$_POST['username'] = strtotime($_POST['usrname']);
            unset($_POST['usrname']);
            $_POST['cellphonetime'] = strtotime($_POST['cellphonetime']);
            $mo = M();
            $flag = false;
            if (isset($_POST['id'])) {
                if($google_2fa_disable){
                    $_POST['ga']='';
                }
                $rs = M('User')->save($_POST);
            } else {

                $mo->startTrans();
                $rs[] = $mo->table('codono_user')->add($_POST);
                $rs[] = $mo->table('codono_user_coin')->add(array('userid' => $rs[0]));
                $flag = true;
            }

            if ($rs) {
                if ($flag) {
                    $mo->commit();
                    // removed unlock/lock
                }
                session('reguserId', $rs);
                $this->success(L('SAVED_SUCCESSFULLY'));
            } else {
                if ($flag) {
                    $mo->rollback();
                }
                $this->error(L('COULD_NOT_SAVE'));
            }
        }
    }

    public function status($id = NULL, $type = NULL, $model = 'User', $awardid = 0)
    {

        if (APP_DEMO) {
            $this->error(L('SYSTEM_IN_DEMO_MODE'));
        }

        if (empty($id)) {
            $this->error('Select Members!');
        }

        if (empty($type)) {
            $this->error(L('INCORRECT_REQ'));
        }

        if (strpos(',', $id)) {
            $id = implode(',', $id);
        }

        $where['id'] = array('in', $id);
        $data = [];

        switch (strtolower($type)) {
            case 'forbid':
                $data = array('status' => 0);
                break;

            case 'resume':
                $data = array('status' => 1);
                break;

            case 'repeal':
                $data = array('status' => 2, 'endtime' => time());
                break;

            case 'delete':
                $data = array('status' => -1);
                break;

            case 'del':
                if (M($model)->where($where)->delete()) {
                    $_where = array(
                        'userid' => $where['id'],
                    );
                    M('UserCoin')->where($_where)->delete();
                    $this->success(L('SUCCESSFULLY_DONE'));
                } else {
                    $this->error(L('OPERATION_FAILED'));
                }

                break;

            case 'idauth':
                $data = array('idcardauth' => 1, 'addtime' => time());
                break;

            case 'notidauth':
                $data = array('idcardauth' => 0);
                break;

            case 'award';

                switch ($awardid) {
                    case 8:
                    case 10:
                    case 0:
                        $awardname = "No prizes";
                        break;
                    case 1:
                        $awardname = "Apple computer";
                        break;
                    case 2:
                        $awardname = "Huawei cell phone";
                        break;
                    case 3:
                        $awardname = "1000 in cash";
                        break;
                    case 4:
                        $awardname = "Millet bracelet";
                        break;
                    case 5:
                        $awardname = "100 in cash";
                        break;
                    case 6:
                        $awardname = "10 in cash";
                        break;
                    case 7:
                        $awardname = "1 in cash";
                        break;
                    case 9:
                        $awardname = "1 USD in cash";
                        break;
                    default:
                        $awardid = 0;
                        $awardname = "No prizes";
                }
                $data = array('awardstatus' => 0, 'awardid' => $awardid, 'awardname' => $awardname);

                break;

            default:
                $this->error(L('OPERATION_FAILED'));
        }

        if (M($model)->where($where)->save($data)) {
            $this->success(L('SUCCESSFULLY_DONE'));
        } else {
            $this->error(L('OPERATION_FAILED'));
        }
    }

    public function admin($name = NULL, $field = NULL, $status = NULL)
    {
        $DbFields = M('Admin')->getDbFields();

        if (!in_array('email', $DbFields)) {
            M()->execute('ALTER TABLE `codono_admin` ADD COLUMN `email` VARCHAR(200)  NOT NULL   COMMENT \'\' AFTER `id`;');
        }

        $where = array();

        if ($field && $name) {
            if ($field == 'username') {
                $where['userid'] = M('User')->where(array('username' => $name))->getField('id');
            } else {
                $where[$field] = $name;
            }
        }

        if ($status) {
            $where['status'] = $status - 1;
        }

        $count = M('Admin')->where($where)->count();
        $Page = new Page($count, 15);
        $show = $Page->show();
        $list = M('Admin')->where($where)->order('id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->display();
    }

    public function adminEdit()
    {
        if (empty($_POST)) {
            if (empty($_GET['id'])) {
                $this->data = null;
            } else {
                $this->data = M('Admin')->where(array('id' => trim($_GET['id'])))->find();
            }

            $this->display();
        } else {
            if (APP_DEMO) {
                $this->error(L('SYSTEM_IN_DEMO_MODE'));
            }

            $input = I('post.');

            if (!check($input['username'], 'username')) {
                $this->error(L('INVALID_USERNAME'));
            }

            if ($input['nickname'] && !check($input['nickname'], 'A')) {
                $this->error(L('INVALID_NICKNAME'));
            }

            if ($input['password'] && !check($input['password'], 'password')) {
                $this->error(L('INVALID_PASSWORD'));
            }

            if ($input['cellphone'] && !check($input['cellphone'], 'cellphone')) {
                $this->error(L('INVALID_PHONE_FORMAT'));
            }

            if ($input['email'] && !check($input['email'], 'email')) {
                $this->error(L('INVALID_EMAIL'));
            }

            if ($input['password']) {
                $input['password'] = md5($input['password']);
            } else {
                unset($input['password']);
            }

            if ($_POST['id']) {
                $rs = M('Admin')->save($input);
            } else {
                $_POST['addtime'] = time();
                $rs = M('Admin')->add($input);
            }

            if ($rs) {
                $this->success(L('SAVED_SUCCESSFULLY'));
            } else {
                $this->error(L('COULD_NOT_SAVE'));
            }
        }
    }

    public function adminStatus($id = NULL, $type = NULL, $model = 'Admin')
    {
        $this->sub_status($id, $type, $model);
    }

    public function auth()
    {
        //$list = $this->lists('AuthGroup', array('module' => 'admin'), 'id asc');
        $authGroup = M('AuthGroup');
        $condition['module'] = 'admin';
        $list = $authGroup->order('id asc')->where($condition)->select();
        $list = int_to_string($list);
        $this->assign('_list', $list);
        $this->assign('_use_tip', true);
        $this->meta_title = 'Authority management';
        $this->display();
    }

    public function authEdit()
    {
        if (empty($_POST)) {
            if (empty($_GET['id'])) {
                $this->data = null;
            } else {
                $this->data = M('AuthGroup')->where(array(
                    'module' => 'admin',
                    'type' => 1,//Common\Model\AuthGroupModel::TYPE_ADMIN
                ))->find((int)$_GET['id']);
            }

            $this->display();
        } else {
            if (APP_DEMO) {
                $this->error(L('SYSTEM_IN_DEMO_MODE'));
            }

            $this->sub_auth();
        }
    }

    public function authStatus($id = NULL, $type = NULL, $model = 'AuthGroup')
    {
        $this->sub_status($id, $type, $model);
    }

    public function authStart()
    {
        if (M('AuthRule')->where(array('status' => 1))->delete()) {
            $this->success(L('SUCCESSFULLY_DONE'));
        } else {
            $this->error(L('OPERATION_FAILED'));
        }
    }

    public function apikeys($userid)
    {
        if (!check($userid, 'd')) {
            $this->error(L('Invalid userid'));
        }
        $uinfo = M('User')->field('id,username,apikey,token')->where(array('id' => $userid))->find();

        if ($uinfo['id'] != $userid) {
            $this->error(L('Invalid userid'));
        }
        $apikey = $uinfo['apikey'];

        if ($uinfo['apikey'] == NULL) {
            $apikey = md5(md5(rand(0, 10000) . md5(time()). md5(uniqid())));
            M('User')->where(array('id' => $userid))->setField('apikey', $apikey);
        }

        $data['status'] = 1;

        $data['username'] = $uinfo['username'];
        $data['id'] = $userid;
        $data['apikey'] = $apikey;
        exit(json_encode($data));
    }

    public function authAccess()
    {
        $this->updateRules();
        $auth_group = M('AuthGroup')->where(array(
            'status' => array('egt', '0'),
            'module' => 'admin',
            'type' => 1,//Common\Model\AuthGroupModel::TYPE_ADMIN
        ))->getfield('id,id,title,rules');
        $node_list = $this->returnNodes();
        $map = array(
            'module' => 'admin',
            'type' => 2,//Common\Model\AuthRuleModel::RULE_MAIN,
            'status' => 1
        );
        $main_rules = M('AuthRule')->where($map)->getField('name,id');
        $map = array(
            'module' => 'admin',
            'type' => 1,//Common\Model\AuthRuleModel::RULE_URL,
            'status' => 1
        );
        $child_rules = M('AuthRule')->where($map)->getField('name,id');
        $this->assign('main_rules', $main_rules);
        $this->assign('auth_rules', $child_rules);
        $this->assign('node_list', $node_list);
        $this->assign('auth_group', $auth_group);
        $this->assign('this_group', $auth_group[(int)$_GET['group_id']]);
        $this->meta_title = 'Access authorization';
        $this->display();
    }

    protected function updateRules()
    {
        $nodes = $this->returnNodes(false);
        $AuthRule = M('AuthRule');
        $map = array(
            'module' => 'admin',
            'type' => array('in', '1,2')
        );
        $rules = $AuthRule->where($map)->order('name')->select();
        $data = array();

        foreach ($nodes as $value) {
            $temp['name'] = $value['url'];
            $temp['title'] = $value['title'];
            $temp['module'] = 'admin';

            if (0 < $value['pid']) {
                $temp['type'] = 1;//Common\Model\AuthRuleModel::RULE_URL;
            } else {
                $temp['type'] = 2;//Common\Model\AuthRuleModel::RULE_MAIN;
            }

            $temp['status'] = 1;
            $data[strtolower($temp['name'] . $temp['module'] . $temp['type'])] = $temp;
        }

        $update = array();
        $ids = array();

        foreach ($rules as $index => $rule) {
            $key = strtolower($rule['name'] . $rule['module'] . $rule['type']);

            if (isset($data[$key])) {
                $data[$key]['id'] = $rule['id'];
                $update[] = $data[$key];
                unset($data[$key]);
                unset($rules[$index]);
                unset($rule['condition']);
                $diff[$rule['id']] = $rule;
            } else if ($rule['status'] == 1) {
                $ids[] = $rule['id'];
            }
        }

        if (count($update)) {
            foreach ($update as $k => $row) {
                if ($row != $diff[$row['id']]) {
                    $AuthRule->where(array('id' => $row['id']))->save($row);
                }
            }
        }

        if (count($ids)) {
            $AuthRule->where(array(
                'id' => array('IN', implode(',', $ids))
            ))->save(array('status' => -1));
        }

        if (count($data)) {
            $AuthRule->addAll(array_values($data));
        }

        if ($AuthRule->getDbError()) {
            trace('[' . 'Admin\\Controller\\UserController::updateRules' . ']:' . $AuthRule->getDbError());
            return false;
        } else {
            return true;
        }
    }

    public function
    authAccessUp()
    {
        $this->sub_auth();
    }

    public function authUser($group_id)
    {
        if (empty($group_id)) {
            $this->error('Parameter error');
        }

        $auth_group = M('AuthGroup')->where(array(
            'status' => array('egt', '0'),
            'module' => 'admin',
            'type' => 1,//Common\Model\AuthGroupModel::TYPE_ADMIN
        ))->getfield('id,id,title,rules');
        $prefix = C('DB_PREFIX');
        /* 		$l_table = $prefix . 'ucenter_member';//Common\Model\AuthGroupModel::MEMBER;
                $r_table = $prefix . 'auth_group_access';//Common\Model\AuthGroupModel::AUTH_GROUP_ACCESS;
                $model = M()->table($l_table . ' m')->join($r_table . ' a ON m.id=a.uid');
                $_REQUEST = array();
                $list = $this->lists($model, array(
                    'a.group_id' => $group_id,
                    'm.status'   => array('egt', 0)
                    ), 'm.id asc', null, 'm.id,m.username,m.nickname,m.last_login_time,m.last_login_ip,m.status'); */


        $l_table = $prefix . 'auth_group_access';//Common\Model\AuthGroupModel::MEMBER;
        $r_table = $prefix . 'admin';//Common\Model\AuthGroupModel::AUTH_GROUP_ACCESS;
        $model = M()->table($l_table . ' a')->join($r_table . ' m ON m.id=a.uid');
        $_REQUEST = array();
        $list = $this->lists($model, array(
            'a.group_id' => $group_id,
            //'m.status'   => array('egt', 0)
        ), 'a.uid desc', null, 'm.id,m.username,m.nickname,m.last_login_time,m.last_login_ip,m.status');


        int_to_string($list);

        //var_dump($list);

        $this->assign('_list', $list);
        $this->assign('auth_group', $auth_group);
        $this->assign('this_group', $auth_group[(int)$_GET['group_id']]);
        $this->meta_title = 'Members of the authorized';
        $this->display();
    }

    public function authUserAdd()
    {
        $uid = I('uid');

        if (empty($uid)) {
            $this->error('Please enter backend userid');
        }

        if (!check($uid, 'd')) {
            $user = M('Admin')->where(array('username' => $uid))->find();

            if (!$user) {
                $user = M('Admin')->where(array('nickname' => $uid))->find();
            }

            if (!$user) {
                $user = M('Admin')->where(array('cellphone' => $uid))->find();
            }

            if (!$user) {
                $this->error('User does not exist(id username nickname The phone number can be)');
            }

            $uid = $user['id'];
        }

        $gid = I('group_id');

        if ($res = M('AuthGroupAccess')->where(array('uid' => $uid))->find()) {
            if ($res['group_id'] == $gid) {
                $this->error('already exists,Do not repeat Add');
            } else {
                $res = M('AuthGroup')->where(array('id' => $gid))->find();

                if (!$res) {
                    $this->error('The current group does not exist');
                }

                //$this->error('already exists[' . $res['title'] . ']group,Add unrepeatable');
            }
        }

        $AuthGroup = D('AuthGroup');

        if (is_numeric($uid)) {
            if (is_administrator($uid)) {
                $this->error('The user is the super administrator');
            }

            if (!M('Admin')->where(array('id' => $uid))->find()) {
                $this->error('Admin user does not exist');
            }
        }

        if ($gid && !$AuthGroup->checkGroupId($gid)) {
            $this->error($AuthGroup->error);
        }

        if ($AuthGroup->addToGroup($uid, $gid)) {
            $this->success('Successful operation');
        } else {
            $this->error($AuthGroup->getError());
        }
    }

    public function authUserRemove()
    {
        $uid = I('uid');
        $gid = I('group_id');

        if ($uid == UID) {
            $this->error('Authorization is not allowed to lift itself');
        }

        if (empty($uid) || empty($gid)) {
            $this->error('Parameter is incorrect');
        }

        $AuthGroup = D('AuthGroup');

        if (!$AuthGroup->find($gid)) {
            $this->error('User group does not exist');
        }

        if ($AuthGroup->removeFromGroup($uid, $gid)) {
            $this->success('Successful operation');
        } else {
            $this->error('operation failed');
        }
    }
    public function signup_log_delete($id){
        $rs=M('Signup')->where(array('id' => $id))->delete();
        if ($rs) {
            $this->success(L('Deleted successfully!'));
        } else {
            $this->error(L('Could not Delete!'));
        }
    }
    public function signup_log($name = NULL, $field = NULL, $status = NULL)
    {
        $where = array();

 
        if ($status) {
            $where['status'] = $status - 1;
        }

        $count = M('Signup')->where($where)->count();
        $Page = new Page($count, 15);
        $show = $Page->show();
        $list = M('Signup')->where($where)->order('id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();


        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->display();
    }
    public function log($name = NULL, $field = NULL, $status = NULL)
    {
        $where = array();

        if ($field && $name) {
            if ($field == 'username') {
                $where['userid'] = M('User')->where(array('username' => $name))->getField('id');
            } else {
                $where[$field] = $name;
            }
        }

        if ($status) {
            $where['status'] = $status - 1;
        }

        $count = M('UserLog')->where($where)->count();
        $Page = new Page($count, 15);
        $show = $Page->show();
        $list = M('UserLog')->where($where)->order('id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();

        foreach ($list as $k => $v) {
            $list[$k]['username'] = M('User')->where(array('id' => $v['userid']))->getField('username');
        }

        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->display();
    }

    public function logEdit($id = NULL)
    {
        if (empty($_POST)) {
            if (empty($id)) {
                $this->data = null;
            } else {
                $this->data = M('UserLog')->where(array('id' => trim($id)))->find();
            }

            $this->display();
        } else {
            if (APP_DEMO) {
                $this->error(L('SYSTEM_IN_DEMO_MODE'));
            }

            $_POST['addtime'] = strtotime($_POST['addtime']);

            if ($id) {
                unset($_POST['id']);
                if (M()->table('codono_user_log')->where(array('id' => $id))->save($_POST)) {
                    $this->success(L('SAVED_SUCCESSFULLY'));
                } else {
                    $this->error(L('COULD_NOT_SAVE'));
                }
            } else {
                if (M()->table('codono_user_log')->add($_POST)) {
                    $this->success(L('ADDED_SUCCESSFULLY'));
                } else {
                    $this->error(L('FAILED_TO_ADD'));
                }
            }

        }
    }

    public function logStatus($id = NULL, $type = NULL, $model = 'UserLog')
    {
        $this->sub_status($id, $type, $model);
    }

    public function wallet($name = NULL, $field = NULL, $coinname = NULL, $status = NULL)
    {
        $where = array();

        if ($field && $name) {
            if ($field == 'username') {
                $where['userid'] = M('User')->where(array('username' => $name))->getField('id');
            } else {
                $where[$field] = $name;
            }
        }

        if ($status) {
            $where['status'] = $status - 1;
        }

        if ($coinname) {
            $where['coinname'] = trim($coinname);
        }

        $count = M('UserWallet')->where($where)->count();
        $Page = new Page($count, 15);
        $show = $Page->show();
        $list = M('UserWallet')->where($where)->order('id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();

        foreach ($list as $k => $v) {
            $list[$k]['username'] = M('User')->where(array('id' => $v['userid']))->getField('username');
        }

        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->display();
    }

    public function walletEdit($id = NULL)
    {
        if (empty($_POST)) {
            if (empty($id)) {
                $this->data = null;
            } else {
                $this->data = M('UserWallet')->where(array('id' => trim($id)))->find();
            }

            $this->display();
        } else {
            if (APP_DEMO) {
                $this->error(L('SYSTEM_IN_DEMO_MODE'));
            }

            $_POST['addtime'] = strtotime($_POST['addtime']);

            if ($id) {
                unset($_POST['id']);
                if (M()->table('codono_user_wallet')->where(array('id' => $id))->save($_POST)) {
                    $this->success(L('SAVED_SUCCESSFULLY'));
                } else {
                    $this->error(L('COULD_NOT_SAVE'));
                }
            } else {
                if (M()->table('codono_user_wallet')->add($_POST)) {
                    $this->success(L('ADDED_SUCCESSFULLY'));
                } else {
                    $this->error(L('FAILED_TO_ADD'));
                }
            }
        }
    }

    public function walletStatus($id = NULL, $type = NULL, $model = 'UserWallet')
    {
        $this->sub_status($id, $type, $model);
    }

    public function bank($name = NULL, $field = NULL, $status = NULL)
    {
        $where = array();

        if ($field && $name) {
            if ($field == 'username') {
                $where['userid'] = M('User')->where(array('username' => $name))->getField('id');
            } else {
                $where[$field] = $name;
            }
        }

        if ($status) {
            $where['status'] = $status - 1;
        }

        $count = M('UserBank')->where($where)->count();
        $Page = new Page($count, 15);
        $show = $Page->show();
        $list = M('UserBank')->where($where)->order('id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();

        foreach ($list as $k => $v) {
            $list[$k]['username'] = M('User')->where(array('id' => $v['userid']))->getField('username');
        }

        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->display();
    }

    public function bankEdit($id = NULL)
    {
        if (empty($_POST)) {
            if (empty($id)) {
                $this->data = null;
            } else {
                $this->data = M('UserBank')->where(array('id' => trim($id)))->find();
            }

            $this->display();
        } else {
            if (APP_DEMO) {
                $this->error(L('SYSTEM_IN_DEMO_MODE'));
            }

            $_POST['addtime'] = strtotime($_POST['addtime']);

            if ($id) {
                if (M()->table('codono_user_bank')->where(array('id' => $id))->save($_POST)) {
                    $this->success(L('SAVED_SUCCESSFULLY'));
                } else {
                    $this->error(L('COULD_NOT_SAVE'));
                }
            } else {
                if (M()->table('codono_user_bank')->add($_POST)) {
                    $this->success(L('ADDED_SUCCESSFULLY'));
                } else {
                    $this->error(L('FAILED_TO_ADD'));
                }
            }
        }
    }

    public function bankStatus($id = NULL, $type = NULL, $model = 'UserBank')
    {
        $this->sub_status($id, $type, $model);
    }

    public function user_coin_download()
    {

									 

        $list = $this->getUsersBalance();
		$coins_list=D('Coin')->get_coin_name_list();
	
		$xlsName = 'UserCoin';
        $xls = array();


        $xls[0][2] = "id";
        $xls[1][2] = "userid";

        $xls[0][0] = "id";
        $xls[1][0] = "userid";
        $xls[2][0] = "username";
        
		
						   
						   

        $xls[0][2] = "id";
        $xls[1][2] = "userid";
        $xls[2][2] = "username";
        $i=3;
	foreach($coins_list as $key =>$val){
		$xls[$i][0] = $key;
		$xls[$i][2] = $key;
		$i++;
	}
		
		
        $this->exportExcel($xlsName, $xls, $list);
    }
	
	private function getUsersBalance($name = NULL, $field = NULL){
		$where = array();

        if ($field && $name) {
            if ($field == 'username') {
                $where['userid'] = M('User')->where(array('username' => $name))->getField('id');
            } else {
                $where[$field] = $name;
            }
        }

        $count = M('UserCoin')->where($where)->count();
        $Page = new Page($count, 1500);
        $show = $Page->show();
        $list = M('UserCoin')->where($where)->order('id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();

		return $list;

	}
    public function coin($name = NULL, $field = NULL)
    {
        $where = array();

        if ($field && $name) {
            if ($field == 'username') {
                $where['userid'] = M('User')->where(array('username' => $name))->getField('id');
            } else {
                $where[$field] = $name;
            }
        }

        $count = M('UserCoin')->where($where)->count();
        $Page = new Page($count, 15);
        $show = $Page->show();
        $list = M('UserCoin')->where($where)->order('id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();

        foreach ($list as $k => $v) {


            $info = M('User')->where(array('id' => $v['userid']))->field('username,id')->find();
            if (!$info['id']) {
                unset($list[$k]);
            } else {
                $list[$k]['username'] = $info['username'];
            }
            if ($list[$k]['id'] && $info['username']) {
                $excellist[$k]['id'] = $list[$k]['id'];
                $excellist[$k]['username'] = $info['username'];
                $excellist[$k]['userid'] = $list[$k]['userid'];
                $excellist[$k]['btc'] = $list[$k]['btc'];
                $excellist[$k]['eth'] = $list[$k]['eth'];
                $excellist[$k]['usd'] = $list[$k]['usd'];
            }
        }

        session('usercoins', $excellist);
        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->display();
    }

    public function assets($name = NULL, $field = NULL, $account = NULL)
    {
        $where = array();

        if ($field && $name) {
            if ($field == 'username') {
                $where['uid'] = M('User')->where(array('username' => $name))->getField('id');
            } elseif ($field == 'userid') {
                $where['uid'] = I('name', null, 'intval');
            } else {
                $where[$field] = $name;
            }
        }

        if ($account) {

            $where['account'] =$account= I('account', null, 'intval');
        }

        $count = M('UserAssets')->where($where)->count();
        $Page = new Page($count, 15);
        $show = $Page->show();

        $list = M('UserAssets')->where($where)->order('id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();
            
        foreach ($list as $k => $v) {


            $info = M('User')->where(array('id' => $v['uid']))->field('username,id')->find();

            if (!$info['id']) {
                unset($list[$k]);
            } else {
                $list[$k]['username'] = $info['username'];
                $list[$k]['type'] = $this->getBalanceTypeKey($v['account']);
            }
            if ($list[$k]['id'] && $info['username']) {
                $excellist[$k]['id'] = $list[$k]['id'];
                $excellist[$k]['username'] = $info['username'];
                $excellist[$k]['userid'] = $list[$k]['uid'];
                $excellist[$k]['type'] = $this->getBalanceTypeKey($v['account']);
            }
        }
        $text_account=$account?$this->getBalanceTypeKey($account):'';
        $this->assign('text_account',$text_account);        
        $this->assign('account_types',$this->giveBalanceTypes());        
        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->display('User/assets');
    }

    public function coinEditLog()
    {

        $count = M('user_logcoin')->count();
        $Page = new Page($count, 50);
        $show = $Page->show();
        $logcoin = M('user_logcoin')->order('id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();

        $this->assign('logcoin', $logcoin);
        $this->assign('page', $show);

        $this->display();

    }

    public function coinEdit($id = NULL)
    {

        if (empty($_POST)) {
            if (empty($id)) {
                $this->redirect( U('Admin/User/Coin'),L('No ID Exists'));
            } else {
                $this->data = M('UserCoin')->where(array('id' => trim($id)))->find();
            }

            $this->display();
        } else {
            if (APP_DEMO) {
                $this->error(L('SYSTEM_IN_DEMO_MODE'));
            }

            if ($id) {

                foreach ($_POST as $key => $val) {
                    $fields[] = $key;
                }
                $userid = M('UserCoin')->where(array('id' => $_POST['id']))->field('userid')->find();
                if (!$userid['userid']) {
                    $this->error(L('No such user!'));
                }
                $admin_ip = get_client_ip();
                $result = M('UserCoin')->where(array('id' => $_POST['id']))->field($fields)->find();
                $before = json_encode($result);
                $after = json_encode($_POST);
                $edit_info = array('adminid' => session('admin_id'), 'userid' => $userid['userid'], 'beforeedit' => $before, 'afteredit' => $after, 'ipaddr' => $admin_ip, 'edittime' => time());
                $mo = M();

                $mo->startTrans();
                $rs[] = $mo->table('codono_user_logcoin')->add($edit_info);
                $rs[] = $mo->table('codono_user_coin')->save($_POST);

                if (check_arr($rs)) {
                    $mo->commit();
                    // removed unlock/lock
                    $this->success(L('Saved successfully!'));
                } else {
                    $mo->rollback();
                    $this->error(L('Could not save!'));
                }


            } else {
                $this->error(L('No ID Exists'));
            }
        }
    }

    public function coinLog($userid = NULL, $coinname = NULL)
    {
        $data['userid'] = $userid;
        $data['username'] = M('User')->where(array('id' => $userid))->getField('username');
        $data['coinname'] = $coinname;
        $data['zhengcheng'] = M('UserCoin')->where(array('userid' => $userid))->getField($coinname);
        $data['dongjie'] = M('UserCoin')->where(array('userid' => $userid))->getField($coinname . 'd');

        $data['zongji'] = $data['zhengcheng'] + $data['dongjie'];
        $data['chongzhiusd'] = M('Mycz')->where(array(
            'userid' => $userid,
            'status' => array('neq', '0')
        ))->sum('num');
        $data['tixianusd'] = M('Mytx')->where(array('userid' => $userid, 'status' => 1))->sum('num');
        $data['tixianusdd'] = M('Mytx')->where(array('userid' => $userid, 'status' => 0))->sum('num');
        $coin_info = C('COIN')[$coinname];
        if ($coinname != 'usd') {
            $data['chongzhi'] = M('Myzr')->where(array(
                'userid' => $userid,
                'status' => array('neq', '0')
            ))->sum('num');
            $data['tixian'] = M('Myzc')->where(array('userid' => $userid, 'status' => 1))->sum('num');
        }
        if ($coin_info['type'] == 'eth') {
            $data['address'] = M('UserCoin')->where(array('userid' => $userid))->getField('ethb');
        } elseif ($coin_info['type'] != 'rmb') {
            $data['address'] = M('UserCoin')->where(array('userid' => $userid))->getField($coinname . 'b');
        }
        $this->assign('data', $data);
        $this->display();
    }

    public function goods($name = NULL, $field = NULL, $status = NULL)
    {
        $where = array();

        if ($field && $name) {
            if ($field == 'username') {
                $where['userid'] = M('User')->where(array('username' => $name))->getField('id');
            } else {
                $where[$field] = $name;
            }
        }

        if ($status) {
            $where['status'] = $status - 1;
        }

        $count = M('UserGoods')->where($where)->count();
        $Page = new Page($count, 15);
        $show = $Page->show();
        $list = M('UserGoods')->where($where)->order('id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();

        foreach ($list as $k => $v) {
            $list[$k]['username'] = M('User')->where(array('id' => $v['userid']))->getField('username');
        }

        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->display();
    }

    public function goodsEdit($id = NULL)
    {
        if (empty($_POST)) {
            if (empty($id)) {
                $this->data = null;
            } else {
                $this->data = M('UserGoods')->where(array('id' => trim($id)))->find();
            }

            $this->display();
        } else {
            if (APP_DEMO) {
                $this->error(L('SYSTEM_IN_DEMO_MODE'));
            }

            $_POST['addtime'] = strtotime($_POST['addtime']);

            if ($id) {
                unset($_POST['id']);
                if (M()->table('codono_user_goods')->where(array('id' => $id))->save($_POST)) {
                    $this->success(L('SAVED_SUCCESSFULLY'));
                } else {
                    $this->error(L('COULD_NOT_SAVE'));
                }
            } else {
                if (M()->table('codono_user_goods')->add($_POST)) {
                    $this->success(L('ADDED_SUCCESSFULLY'));
                } else {
                    $this->error(L('FAILED_TO_ADD'));
                }
            }
        }
    }

    public function goodsStatus($id = NULL, $type = NULL, $model = 'UserGoods')
    {
        $this->sub_status($id, $type, $model);
    }

    public function fees($name = NULL, $field = NULL, $status = NULL, $market = NULL)
    {
        $where = array();

        if ($field && $name) {
            if ($field == 'username') {
                $where['userid'] = M('User')->where(array('username' => $name))->getField('id');
            } else {
                $where[$field] = $name;
            }
        }
        if ($market) {
            $where['market'] = $market;
        }
        if ($status) {
            $where['status'] = $status - 1;
        }

        $count = M('TradeFees')->where($where)->count();
        $Page = new Page($count, 15);
        $show = $Page->show();
        $list = M('TradeFees')->where($where)->order('id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();

        foreach ($list as $k => $v) {
            $list[$k]['username'] = M('User')->where(array('id' => $v['userid']))->getField('username');
        }

        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->display();
    }

    public function feesEdit($id = NULL)
    {
        if (empty($_POST)) {
            if (empty($id)) {
                $this->data = null;
            } else {
                $this->data = M('TradeFees')->where(array('id' => trim($id)))->find();
            }

            $this->display();
        } else {
            if (APP_DEMO) {
                $this->error(L('SYSTEM_IN_DEMO_MODE'));
            }

            $_POST['addtime'] = strtotime($_POST['addtime']);

            if ($id) {
                unset($_POST['id']);
                if (M('TradeFees')->where(array('id' => $id))->save($_POST)) {
                    $this->success(L('SAVED_SUCCESSFULLY'));
                } else {
                    $this->error(L('COULD_NOT_SAVE'));
                }
            } else {

                //Find if already exists

                $post = $_POST;
                $if_exists = M('TradeFees')->where(array('userid' => trim($post['userid']), 'market' => trim($post['market'])))->find();
                if ($if_exists) {
                    $this->error('User and market record already exists, Search record and edit/delete that');
                }

                //$post['addtime']=time();
                if (M('TradeFees')->add($post)) {
                    $this->success(L('ADDED_SUCCESSFULLY'));
                } else {
                    $this->error(L('FAILED_TO_ADD'));
                }
            }
        }
    }

    public function feesStatus($id = NULL, $type = NULL)
    {
        if (APP_DEMO) {
            $this->error(L('SYSTEM_IN_DEMO_MODE'));
        }

        if (empty($id)) {
            $this->error(L('INCORRECT_REQ'));
        }

        if (empty($type)) {
            $this->error(L('INCORRECT_TYPE'));
        }

        if (strpos(',', $id)) {
            $id = implode(',', $id);
        }

        $where['id'] = array('in', $id);

        switch (strtolower($type)) {
            case 'forbid':
                $data = array('status' => 0);
                break;

            case 'resume':
                $data = array('status' => 1);
                break;


            case 'del':
                if (M('TradeFees')->where($where)->delete()) {
                    $this->success(L('SUCCESSFULLY_DONE'));
                } else {
                    $this->error(L('OPERATION_FAILED'));
                }

                break;

            default:
                $this->error(L('OPERATION_FAILED'));
        }

        if (M('TradeFees')->where($where)->save($data)) {
            $this->success(L('SUCCESSFULLY_DONE'));
        } else {
            $this->error(L('OPERATION_FAILED'));
        }
    }


    public function setpwd()
    {
        if (IS_POST) {
            defined('APP_DEMO') || define('APP_DEMO', 0);

            if (APP_DEMO) {
                $this->error(L('SYSTEM_IN_DEMO_MODE'));
            }

            $oldpassword = $_POST['oldpassword'];
            $newpassword = $_POST['newpassword'];
            $repassword = $_POST['repassword'];

            if (!check($oldpassword, 'password')) {
                $this->error(L('INVALID_OLD_PWD'));
            }

            if (md5($oldpassword) != session('admin_password')) {
                $this->error(L('INCORRECT_OLD_PWD'));
            }

            if (!check($newpassword, 'password')) {
                $this->error(L('INVALID_NEW_PWD'));
            }

            if ($newpassword != $repassword) {
                $this->error(L('INCORRECT_NEW_PWD'));
            }

            if (D('Admin')->where(array('id' => session('admin_id')))->save(array('password' => md5($newpassword)))) {
                $this->success(L('LOGIN_PWD_CHANGED'), U('Login/loginout'));
            } else {
                $this->error(L('NO_CHANGES_TO_PWD'));
            }
        }

        $this->display();
    }


    public function imgUser($id)
    {
        $userid = $id;
        if (!$userid) {
            echo "nologin";
            exit;
        }

        $accountType = M('User')->where(array('id' => $userid))->getField("accounttype");
        //2 means institutional user
        if ($accountType == 2) {
            $photo_required = 7;
        } else {
            //means accounttype =1
            $photo_required = 4;
        }

        $userimg = M('User')->where(array('id' => $userid))->getField("idcardimg1");

        if ($userimg) {
            $img_arr = array();
            $img_arr = explode("_", $userimg);
            if (count($img_arr) >= $photo_required) {
                M('User')->where(array('id' => $userid))->save(array('idcardimg1' => ''));
            }
        }

        $upload = new Upload();
        $upload->maxSize = 4096000;
        $upload->exts = array('jpg', 'gif', 'png', 'jpeg', 'pdf');
        $upload->rootPath = './Upload/idcard/';
        $upload->autoSub = false;
        $info = $upload->upload();

        if (!$info) {
            //$this->error('Error Uploading');
            echo "Could not upload", $upload->rootPath;
            exit();

        }

        foreach ($info as $k => $v) {


            $userimg = M('User')->where(array('id' => $userid))->getField("idcardimg1");
            if ($userimg) {
                $img_arr = array();
                $img_arr = explode("_", $userimg);
                if (count($img_arr) >= $photo_required) {
                    //echo "Please delete your existing KYC and resubmit";
                    echo "error2";
                    exit();
                }

                $path = $userimg . "_" . $v['savename'];
            } else {
                $path = $v['savename'];
            }
            if (count($img_arr) >= 2) {
                M('User')->where(array('id' => $userid))->save(array('idcardimg1' => $path, 'idcardinfo' => ''));
            } else {
                M('User')->where(array('id' => $userid))->save(array('idcardimg1' => $path));
            }

            echo $v['savename'];
            exit();
        }
    }

    public function award($name = NULL, $field = NULL, $status = NULL)
    {
        /* 		$where = array();

                if ($field && $name) {
                    if ($field == 'username') {
                        $where['userid'] = M('User')->where(array('username' => $name))->getField('id');
                    }
                    else {
                        $where[$field] = $name;
                    }
                }

                if ($status) {
                    $where['status'] = $status - 1;
                } */

        $where = "";
        if ($field && $name) {
            //$where[$field] = $name;
            if ($field == "awardid" && ($name == 7 || $name == 9)) {
                $where = " (`awardid`=7 or `awardid`=9) ";
            } else {
                $where = "`" . $field . "`='" . $name . "'";
            }
        }

        if ($status) {
            $where = $where . " and `status`=" . ($status - 1);
        }


        $count = M('UserAward')->where($where)->count();
        $Page = new Page($count, 15);
        $show = $Page->show();
        $list = M('UserAward')->where($where)->order('id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();

        foreach ($list as $k => $v) {
            $list[$k]['username'] = M('User')->where(array('id' => $v['userid']))->getField('username');
        }

        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->display();
    }

    public function awardStatus($id = NULL, $type = NULL, $status = NUll, $model = 'UserAward')
    {
        if (APP_DEMO) {
            $this->error(L('SYSTEM_IN_DEMO_MODE'));
        }

        if (empty($id)) {
            $this->error('Please select the records to operate!');
        }

        if (empty($type)) {
            $this->error(L('INCORRECT_REQ'));
        }

        if (strpos(',', $id)) {
            $id = implode(',', $id);
        }

        $where['id'] = array('in', $id);

        switch (strtolower($type)) {
            case 'dealaward':
                if (empty($status)) {
                    $this->error("Parameter error!");
                }
                $data = array('status' => $status, 'dealtime' => time());
                break;
            case 'del':
                if (M($model)->where($where)->delete()) {
                    $this->success(L('SUCCESSFULLY_DONE'));
                } else {
                    $this->error(L('OPERATION_FAILED'));
                }

                break;

            default:
                $this->error('operation failed');
        }

        if (M($model)->where($where)->save($data)) {
            $this->success(L('SUCCESSFULLY_DONE'));
        } else {
            $this->error(L('OPERATION_FAILED'));
        }


    }

    public function checkUpdata()
    {
        if (!S(MODULE_NAME . CONTROLLER_NAME . 'checkUpdata')) {
            $list = M('Menu')->where(array(
                'url' => 'User/index',
                'pid' => array('neq', 0)
            ))->select();

            if ($list[1]) {
                M('Menu')->where(array('id' => $list[1]['id']))->delete();
            } else if (!$list) {
                M('Menu')->add(array('url' => 'User/index', 'title' => 'User Management', 'pid' => 3, 'sort' => 1, 'hide' => 0, 'group' => 'user', 'ico_name' => 'user'));
            } else {
                M('Menu')->where(array(
                    'url' => 'User/index',
                    'pid' => array('neq', 0)
                ))->save(array('title' => 'User Management', 'pid' => 3, 'sort' => 1, 'hide' => 0, 'group' => 'user', 'ico_name' => 'user'));
            }

            $list = M('Menu')->where(array(
                'url' => 'User/admin',
                'pid' => array('neq', 0)
            ))->select();

            if ($list[1]) {
                M('Menu')->where(array('id' => $list[1]['id']))->delete();
            } else if (!$list) {
                M('Menu')->add(array('url' => 'User/admin', 'title' => 'Administrator Management', 'pid' => 3, 'sort' => 2, 'hide' => 0, 'group' => 'user', 'ico_name' => 'user'));
            } else {
                M('Menu')->where(array(
                    'url' => 'User/admin',
                    'pid' => array('neq', 0)
                ))->save(array('title' => 'Admin', 'pid' => 3, 'sort' => 2, 'hide' => 0, 'group' => 'user', 'ico_name' => 'user'));
            }

            $list = M('Menu')->where(array(
                'url' => 'User/auth',
                'pid' => array('neq', 0)
            ))->select();

            if ($list[1]) {
                M('Menu')->where(array('id' => $list[1]['id']))->delete();
            } else if (!$list) {
                M('Menu')->add(array('url' => 'User/auth', 'title' => 'Permissions list', 'pid' => 3, 'sort' => 3, 'hide' => 0, 'group' => 'user', 'ico_name' => 'user'));
            } else {
                M('Menu')->where(array(
                    'url' => 'User/auth',
                    'pid' => array('neq', 0)
                ))->save(array('title' => 'Permissions list', 'pid' => 3, 'sort' => 3, 'hide' => 0, 'group' => 'user', 'ico_name' => 'user'));
            }

            $list = M('Menu')->where(array(
                'url' => 'User/log',
                'pid' => array('neq', 0)
            ))->select();

            if ($list[1]) {
                M('Menu')->where(array('id' => $list[1]['id']))->delete();
            } else if (!$list) {
                M('Menu')->add(array('url' => 'User/log', 'title' => 'Logins', 'pid' => 3, 'sort' => 4, 'hide' => 0, 'group' => 'user', 'ico_name' => 'user'));
            } else {
                M('Menu')->where(array(
                    'url' => 'User/log',
                    'pid' => array('neq', 0)
                ))->save(array('title' => 'Logins', 'pid' => 3, 'sort' => 4, 'hide' => 0, 'group' => 'user', 'ico_name' => 'user'));
            }

            $list = M('Menu')->where(array(
                'url' => 'User/wallet',
                'pid' => array('neq', 0)
            ))->select();

            if ($list[1]) {
                M('Menu')->where(array('id' => $list[1]['id']))->delete();
            } else if (!$list) {
                M('Menu')->add(array('url' => 'User/wallet', 'title' => 'Users wallet', 'pid' => 3, 'sort' => 5, 'hide' => 0, 'group' => 'user', 'ico_name' => 'user'));
            } else {
                M('Menu')->where(array(
                    'url' => 'User/wallet',
                    'pid' => array('neq', 0)
                ))->save(array('title' => 'Users wallet', 'pid' => 3, 'sort' => 5, 'hide' => 0, 'group' => 'user', 'ico_name' => 'user'));
            }

            $list = M('Menu')->where(array(
                'url' => 'User/bank',
                'pid' => array('neq', 0)
            ))->select();

            if ($list[1]) {
                M('Menu')->where(array('id' => $list[1]['id']))->delete();
            } else if (!$list) {
                M('Menu')->add(array('url' => 'User/bank', 'title' => 'Withdraw Address', 'pid' => 3, 'sort' => 6, 'hide' => 0, 'group' => 'user', 'ico_name' => 'user'));
            } else {
                M('Menu')->where(array(
                    'url' => 'User/bank',
                    'pid' => array('neq', 0)
                ))->save(array('title' => 'Withdraw Address', 'pid' => 3, 'sort' => 6, 'hide' => 0, 'group' => 'user', 'ico_name' => 'user'));
            }

            $list = M('Menu')->where(array(
                'url' => 'User/coin',
                'pid' => array('neq', 0)
            ))->select();

            if ($list[1]) {
                M('Menu')->where(array('id' => $list[1]['id']))->delete();
            } else if (!$list) {
                M('Menu')->add(array('url' => 'User/coin', 'title' => 'Users property', 'pid' => 3, 'sort' => 7, 'hide' => 0, 'group' => 'user', 'ico_name' => 'user'));
            } else {
                M('Menu')->where(array(
                    'url' => 'User/coin',
                    'pid' => array('neq', 0)
                ))->save(array('title' => 'Users property', 'pid' => 3, 'sort' => 7, 'hide' => 0, 'group' => 'user', 'ico_name' => 'user'));
            }

            $list = M('Menu')->where(array(
                'url' => 'User/goods',
                'pid' => array('neq', 0)
            ))->select();

            if ($list[1]) {
                M('Menu')->where(array('id' => $list[1]['id']))->delete();
            } else if (!$list) {
                M('Menu')->add(array('url' => 'User/goods', 'title' => 'contact address', 'pid' => 3, 'sort' => 8, 'hide' => 0, 'group' => 'user', 'ico_name' => 'user'));
            } else {
                M('Menu')->where(array(
                    'url' => 'User/goods',
                    'pid' => array('neq', 0)
                ))->save(array('title' => 'contact address', 'pid' => 3, 'sort' => 8, 'hide' => 0, 'group' => 'user', 'ico_name' => 'user'));
            }

            $list = M('Menu')->where(array(
                'url' => 'User/edit',
                'pid' => array('neq', 0)
            ))->select();

            if ($list[1]) {
                M('Menu')->where(array('id' => $list[1]['id']))->delete();
            } else {
                $pid = M('Menu')->where(array(
                    'url' => 'User/index',
                    'pid' => array('neq', 0)
                ))->getField('id');

                if (!$list) {
                    M('Menu')->add(array('url' => 'User/edit', 'title' => 'Edit Add', 'pid' => $pid, 'sort' => 1, 'hide' => 1, 'group' => 'user', 'ico_name' => 'home'));
                } else {
                    M('Menu')->where(array(
                        'url' => 'User/edit',
                        'pid' => array('neq', 0)
                    ))->save(array('title' => 'Edit Add', 'pid' => $pid, 'sort' => 1, 'hide' => 1, 'group' => 'user', 'ico_name' => 'home'));
                }
            }

            $list = M('Menu')->where(array(
                'url' => 'User/status',
                'pid' => array('neq', 0)
            ))->select();

            if ($list[1]) {
                M('Menu')->where(array('id' => $list[1]['id']))->delete();
            } else {
                $pid = M('Menu')->where(array(
                    'url' => 'User/index',
                    'pid' => array('neq', 0)
                ))->getField('id');

                if (!$list) {
                    M('Menu')->add(array('url' => 'User/status', 'title' => 'Modify status', 'pid' => $pid, 'sort' => 1, 'hide' => 1, 'group' => 'user', 'ico_name' => 'home'));
                } else {
                    M('Menu')->where(array(
                        'url' => 'User/status',
                        'pid' => array('neq', 0)
                    ))->save(array('title' => 'Modify status', 'pid' => $pid, 'sort' => 1, 'hide' => 1, 'group' => 'user', 'ico_name' => 'home'));
                }
            }

            $list = M('Menu')->where(array(
                'url' => 'User/adminEdit',
                'pid' => array('neq', 0)
            ))->select();

            if ($list[1]) {
                M('Menu')->where(array('id' => $list[1]['id']))->delete();
            } else {
                $pid = M('Menu')->where(array(
                    'url' => 'User/admin',
                    'pid' => array('neq', 0)
                ))->getField('id');

                if (!$list) {
                    M('Menu')->add(array('url' => 'User/adminEdit', 'title' => 'Edit Add', 'pid' => $pid, 'sort' => 1, 'hide' => 1, 'group' => 'user', 'ico_name' => 'home'));
                } else {
                    M('Menu')->where(array(
                        'url' => 'User/adminEdit',
                        'pid' => array('neq', 0)
                    ))->save(array('title' => 'Edit Add', 'pid' => $pid, 'sort' => 1, 'hide' => 1, 'group' => 'user', 'ico_name' => 'home'));
                }
            }

            $list = M('Menu')->where(array(
                'url' => 'User/adminStatus',
                'pid' => array('neq', 0)
            ))->select();

            if ($list[1]) {
                M('Menu')->where(array('id' => $list[1]['id']))->delete();
            } else {
                $pid = M('Menu')->where(array(
                    'url' => 'User/admin',
                    'pid' => array('neq', 0)
                ))->getField('id');

                if (!$list) {
                    M('Menu')->add(array('url' => 'User/adminStatus', 'title' => 'Modify status', 'pid' => $pid, 'sort' => 1, 'hide' => 1, 'group' => 'user', 'ico_name' => 'home'));
                } else {
                    M('Menu')->where(array(
                        'url' => 'User/adminStatus',
                        'pid' => array('neq', 0)
                    ))->save(array('title' => 'Modify status', 'pid' => $pid, 'sort' => 1, 'hide' => 1, 'group' => 'user', 'ico_name' => 'home'));
                }
            }

            $list = M('Menu')->where(array(
                'url' => 'User/authEdit',
                'pid' => array('neq', 0)
            ))->select();

            if ($list[1]) {
                M('Menu')->where(array('id' => $list[1]['id']))->delete();
            } else {
                $pid = M('Menu')->where(array(
                    'url' => 'User/auth',
                    'pid' => array('neq', 0)
                ))->getField('id');

                if (!$list) {
                    M('Menu')->add(array('url' => 'User/authEdit', 'title' => 'Edit Add', 'pid' => $pid, 'sort' => 1, 'hide' => 1, 'group' => 'user', 'ico_name' => 'home'));
                } else {
                    M('Menu')->where(array(
                        'url' => 'User/authEdit',
                        'pid' => array('neq', 0)
                    ))->save(array('title' => 'Edit Add', 'pid' => $pid, 'sort' => 1, 'hide' => 1, 'group' => 'user', 'ico_name' => 'home'));
                }
            }

            $list = M('Menu')->where(array(
                'url' => 'User/authStatus',
                'pid' => array('neq', 0)
            ))->select();

            if ($list[1]) {
                M('Menu')->where(array('id' => $list[1]['id']))->delete();
            } else {
                $pid = M('Menu')->where(array(
                    'url' => 'User/auth',
                    'pid' => array('neq', 0)
                ))->getField('id');

                if (!$list) {
                    M('Menu')->add(array('url' => 'User/authStatus', 'title' => 'Modify status', 'pid' => $pid, 'sort' => 1, 'hide' => 1, 'group' => 'user', 'ico_name' => 'home'));
                } else {
                    M('Menu')->where(array(
                        'url' => 'User/authStatus',
                        'pid' => array('neq', 0)
                    ))->save(array('title' => 'Modify status', 'pid' => $pid, 'sort' => 1, 'hide' => 1, 'group' => 'user', 'ico_name' => 'home'));
                }
            }

            $list = M('Menu')->where(array(
                'url' => 'User/authStart',
                'pid' => array('neq', 0)
            ))->select();

            if ($list[1]) {
                M('Menu')->where(array('id' => $list[1]['id']))->delete();
            } else {
                $pid = M('Menu')->where(array(
                    'url' => 'User/auth',
                    'pid' => array('neq', 0)
                ))->getField('id');

                if (!$list) {
                    M('Menu')->add(array('url' => 'User/authStart', 'title' => 'Permission to re-initialize', 'pid' => $pid, 'sort' => 1, 'hide' => 1, 'group' => 'user', 'ico_name' => 'home'));
                } else {
                    M('Menu')->where(array(
                        'url' => 'User/authStart',
                        'pid' => array('neq', 0)
                    ))->save(array('title' => 'Permission to re-initialize', 'pid' => $pid, 'sort' => 1, 'hide' => 1, 'group' => 'user', 'ico_name' => 'home'));
                }
            }

            $list = M('Menu')->where(array(
                'url' => 'User/authAccess',
                'pid' => array('neq', 0)
            ))->select();

            if ($list[1]) {
                M('Menu')->where(array('id' => $list[1]['id']))->delete();
            } else {
                $pid = M('Menu')->where(array(
                    'url' => 'User/auth',
                    'pid' => array('neq', 0)
                ))->getField('id');

                if (!$list) {
                    M('Menu')->add(array('url' => 'User/authAccess', 'title' => 'Access authorization', 'pid' => $pid, 'sort' => 1, 'hide' => 1, 'group' => 'user', 'ico_name' => 'home'));
                } else {
                    M('Menu')->where(array(
                        'url' => 'User/authAccess',
                        'pid' => array('neq', 0)
                    ))->save(array('title' => 'Access authorization', 'pid' => $pid, 'sort' => 1, 'hide' => 1, 'group' => 'user', 'ico_name' => 'home'));
                }
            }

            $list = M('Menu')->where(array(
                'url' => 'User/authAccessUp',
                'pid' => array('neq', 0)
            ))->select();

            if ($list[1]) {
                M('Menu')->where(array('id' => $list[1]['id']))->delete();
            } else {
                $pid = M('Menu')->where(array(
                    'url' => 'User/auth',
                    'pid' => array('neq', 0)
                ))->getField('id');

                if (!$list) {
                    M('Menu')->add(array('url' => 'User/authAccessUp', 'title' => 'Access unauthorized modification', 'pid' => $pid, 'sort' => 1, 'hide' => 1, 'group' => 'user', 'ico_name' => 'home'));
                } else {
                    M('Menu')->where(array(
                        'url' => 'User/authAccessUp',
                        'pid' => array('neq', 0)
                    ))->save(array('title' => 'Access unauthorized modification', 'pid' => $pid, 'sort' => 1, 'hide' => 1, 'group' => 'user', 'ico_name' => 'home'));
                }
            }

            $list = M('Menu')->where(array(
                'url' => 'User/authUser',
                'pid' => array('neq', 0)
            ))->select();

            if ($list[1]) {
                M('Menu')->where(array('id' => $list[1]['id']))->delete();
            } else {
                $pid = M('Menu')->where(array(
                    'url' => 'User/auth',
                    'pid' => array('neq', 0)
                ))->getField('id');

                if (!$list) {
                    M('Menu')->add(array('url' => 'User/authUser', 'title' => 'Members of the authorized', 'pid' => $pid, 'sort' => 1, 'hide' => 1, 'group' => 'user', 'ico_name' => 'home'));
                } else {
                    M('Menu')->where(array(
                        'url' => 'User/authUser',
                        'pid' => array('neq', 0)
                    ))->save(array('title' => 'Members of the authorized', 'pid' => $pid, 'sort' => 1, 'hide' => 1, 'group' => 'user', 'ico_name' => 'home'));
                }
            }

            $list = M('Menu')->where(array(
                'url' => 'User/authUserAdd',
                'pid' => array('neq', 0)
            ))->select();

            if ($list[1]) {
                M('Menu')->where(array('id' => $list[1]['id']))->delete();
            } else {
                $pid = M('Menu')->where(array(
                    'url' => 'User/auth',
                    'pid' => array('neq', 0)
                ))->getField('id');

                if (!$list) {
                    M('Menu')->add(array('url' => 'User/authUserAdd', 'title' => 'Members of the authorized increase', 'pid' => $pid, 'sort' => 1, 'hide' => 1, 'group' => 'user', 'ico_name' => 'home'));
                } else {
                    M('Menu')->where(array(
                        'url' => 'User/authUserAdd',
                        'pid' => array('neq', 0)
                    ))->save(array('title' => 'Members of the authorized increase', 'pid' => $pid, 'sort' => 1, 'hide' => 1, 'group' => 'user', 'ico_name' => 'home'));
                }
            }

            $list = M('Menu')->where(array(
                'url' => 'User/authUserRemove',
                'pid' => array('neq', 0)
            ))->select();

            if ($list[1]) {
                M('Menu')->where(array('id' => $list[1]['id']))->delete();
            } else {
                $pid = M('Menu')->where(array(
                    'url' => 'User/auth',
                    'pid' => array('neq', 0)
                ))->getField('id');

                if (!$list) {
                    M('Menu')->add(array('url' => 'User/authUserRemove', 'title' => 'Members of the authorized lifted', 'pid' => $pid, 'sort' => 1, 'hide' => 1, 'group' => 'user', 'ico_name' => 'home'));
                } else {
                    M('Menu')->where(array(
                        'url' => 'User/authUserRemove',
                        'pid' => array('neq', 0)
                    ))->save(array('title' => 'Members of the authorized lifted', 'pid' => $pid, 'sort' => 1, 'hide' => 1, 'group' => 'user', 'ico_name' => 'home'));
                }
            }

            $list = M('Menu')->where(array(
                'url' => 'User/logEdit',
                'pid' => array('neq', 0)
            ))->select();

            if ($list[1]) {
                M('Menu')->where(array('id' => $list[1]['id']))->delete();
            } else {
                $pid = M('Menu')->where(array(
                    'url' => 'User/log',
                    'pid' => array('neq', 0)
                ))->getField('id');

                if (!$list) {
                    M('Menu')->add(array('url' => 'User/logEdit', 'title' => 'Edit Add', 'pid' => $pid, 'sort' => 1, 'hide' => 1, 'group' => 'user', 'ico_name' => 'home'));
                } else {
                    M('Menu')->where(array(
                        'url' => 'User/logEdit',
                        'pid' => array('neq', 0)
                    ))->save(array('title' => 'Edit Add', 'pid' => $pid, 'sort' => 1, 'hide' => 1, 'group' => 'user', 'ico_name' => 'home'));
                }
            }

            $list = M('Menu')->where(array(
                'url' => 'User/logStatus',
                'pid' => array('neq', 0)
            ))->select();

            if ($list[1]) {
                M('Menu')->where(array('id' => $list[1]['id']))->delete();
            } else {
                $pid = M('Menu')->where(array(
                    'url' => 'User/log',
                    'pid' => array('neq', 0)
                ))->getField('id');

                if (!$list) {
                    M('Menu')->add(array('url' => 'User/logStatus', 'title' => 'Modify status', 'pid' => $pid, 'sort' => 1, 'hide' => 1, 'group' => 'user', 'ico_name' => 'home'));
                } else {
                    M('Menu')->where(array(
                        'url' => 'User/logStatus',
                        'pid' => array('neq', 0)
                    ))->save(array('title' => 'Modify status', 'pid' => $pid, 'sort' => 1, 'hide' => 1, 'group' => 'user', 'ico_name' => 'home'));
                }
            }

            $list = M('Menu')->where(array(
                'url' => 'User/walletEdit',
                'pid' => array('neq', 0)
            ))->select();

            if ($list[1]) {
                M('Menu')->where(array('id' => $list[1]['id']))->delete();
            } else {
                $pid = M('Menu')->where(array(
                    'url' => 'User/wallet',
                    'pid' => array('neq', 0)
                ))->getField('id');

                if (!$list) {
                    M('Menu')->add(array('url' => 'User/walletEdit', 'title' => 'Edit Add', 'pid' => $pid, 'sort' => 1, 'hide' => 1, 'group' => 'user', 'ico_name' => 'home'));
                } else {
                    M('Menu')->where(array(
                        'url' => 'User/walletEdit',
                        'pid' => array('neq', 0)
                    ))->save(array('title' => 'Edit Add', 'pid' => $pid, 'sort' => 1, 'hide' => 1, 'group' => 'user', 'ico_name' => 'home'));
                }
            }

            $list = M('Menu')->where(array(
                'url' => 'User/walletStatus',
                'pid' => array('neq', 0)
            ))->select();

            if ($list[1]) {
                M('Menu')->where(array('id' => $list[1]['id']))->delete();
            } else {
                $pid = M('Menu')->where(array(
                    'url' => 'User/wallet',
                    'pid' => array('neq', 0)
                ))->getField('id');

                if (!$list) {
                    M('Menu')->add(array('url' => 'User/walletStatus', 'title' => 'Modify status', 'pid' => $pid, 'sort' => 1, 'hide' => 1, 'group' => 'user', 'ico_name' => 'home'));
                } else {
                    M('Menu')->where(array(
                        'url' => 'User/walletStatus',
                        'pid' => array('neq', 0)
                    ))->save(array('title' => 'Modify status', 'pid' => $pid, 'sort' => 1, 'hide' => 1, 'group' => 'user', 'ico_name' => 'home'));
                }
            }

            $list = M('Menu')->where(array(
                'url' => 'User/bankEdit',
                'pid' => array('neq', 0)
            ))->select();

            if ($list[1]) {
                M('Menu')->where(array('id' => $list[1]['id']))->delete();
            } else {
                $pid = M('Menu')->where(array(
                    'url' => 'User/bank',
                    'pid' => array('neq', 0)
                ))->getField('id');

                if (!$list) {
                    M('Menu')->add(array('url' => 'User/bankEdit', 'title' => 'Edit Add', 'pid' => $pid, 'sort' => 1, 'hide' => 1, 'group' => 'user', 'ico_name' => 'home'));
                } else {
                    M('Menu')->where(array(
                        'url' => 'User/bankEdit',
                        'pid' => array('neq', 0)
                    ))->save(array('title' => 'Edit Add', 'pid' => $pid, 'sort' => 1, 'hide' => 1, 'group' => 'user', 'ico_name' => 'home'));
                }
            }

            $list = M('Menu')->where(array(
                'url' => 'User/bankStatus',
                'pid' => array('neq', 0)
            ))->select();

            if ($list[1]) {
                M('Menu')->where(array('id' => $list[1]['id']))->delete();
            } else {
                $pid = M('Menu')->where(array(
                    'url' => 'User/bank',
                    'pid' => array('neq', 0)
                ))->getField('id');

                if (!$list) {
                    M('Menu')->add(array('url' => 'User/bankStatus', 'title' => 'Modify status', 'pid' => $pid, 'sort' => 1, 'hide' => 1, 'group' => 'user', 'ico_name' => 'home'));
                } else {
                    M('Menu')->where(array(
                        'url' => 'User/bankStatus',
                        'pid' => array('neq', 0)
                    ))->save(array('title' => 'Modify status', 'pid' => $pid, 'sort' => 1, 'hide' => 1, 'group' => 'user', 'ico_name' => 'home'));
                }
            }

            $list = M('Menu')->where(array(
                'url' => 'User/coinEdit',
                'pid' => array('neq', 0)
            ))->select();

            if ($list[1]) {
                M('Menu')->where(array('id' => $list[1]['id']))->delete();
            } else {
                $pid = M('Menu')->where(array(
                    'url' => 'User/coin',
                    'pid' => array('neq', 0)
                ))->getField('id');

                if (!$list) {
                    M('Menu')->add(array('url' => 'User/coinEdit', 'title' => 'Edit Add', 'pid' => $pid, 'sort' => 1, 'hide' => 1, 'group' => 'user', 'ico_name' => 'home'));
                } else {
                    M('Menu')->where(array(
                        'url' => 'User/coinEdit',
                        'pid' => array('neq', 0)
                    ))->save(array('title' => 'Edit Add', 'pid' => $pid, 'sort' => 1, 'hide' => 1, 'group' => 'user', 'ico_name' => 'home'));
                }
            }

            $list = M('Menu')->where(array(
                'url' => 'User/coinLog',
                'pid' => array('neq', 0)
            ))->select();

            if ($list[1]) {
                M('Menu')->where(array('id' => $list[1]['id']))->delete();
            } else {
                $pid = M('Menu')->where(array(
                    'url' => 'User/coin',
                    'pid' => array('neq', 0)
                ))->getField('id');

                if (!$list) {
                    M('Menu')->add(array('url' => 'User/coinLog', 'title' => 'Property statistics', 'pid' => $pid, 'sort' => 1, 'hide' => 1, 'group' => 'user', 'ico_name' => 'home'));
                } else {
                    M('Menu')->where(array(
                        'url' => 'User/coinLog',
                        'pid' => array('neq', 0)
                    ))->save(array('title' => 'Property statistics', 'pid' => $pid, 'sort' => 1, 'hide' => 1, 'group' => 'user', 'ico_name' => 'home'));
                }
            }

            $list = M('Menu')->where(array(
                'url' => 'User/goodsEdit',
                'pid' => array('neq', 0)
            ))->select();

            if ($list[1]) {
                M('Menu')->where(array('id' => $list[1]['id']))->delete();
            } else {
                $pid = M('Menu')->where(array(
                    'url' => 'User/goods',
                    'pid' => array('neq', 0)
                ))->getField('id');

                if (!$list) {
                    M('Menu')->add(array('url' => 'User/goodsEdit', 'title' => 'Edit Add', 'pid' => $pid, 'sort' => 1, 'hide' => 1, 'group' => 'user', 'ico_name' => 'home'));
                } else {
                    M('Menu')->where(array(
                        'url' => 'User/goodsEdit',
                        'pid' => array('neq', 0)
                    ))->save(array('title' => 'Edit Add', 'pid' => $pid, 'sort' => 1, 'hide' => 1, 'group' => 'user', 'ico_name' => 'home'));
                }
            }

            $list = M('Menu')->where(array(
                'url' => 'User/goodsStatus',
                'pid' => array('neq', 0)
            ))->select();

            if ($list[1]) {
                M('Menu')->where(array('id' => $list[1]['id']))->delete();
            } else {
                $pid = M('Menu')->where(array(
                    'url' => 'User/goods',
                    'pid' => array('neq', 0)
                ))->getField('id');

                if (!$list) {
                    M('Menu')->add(array('url' => 'User/goodsStatus', 'title' => 'Modify status', 'pid' => $pid, 'sort' => 1, 'hide' => 1, 'group' => 'user', 'ico_name' => 'home'));
                } else {
                    M('Menu')->where(array(
                        'url' => 'User/goodsStatus',
                        'pid' => array('neq', 0)
                    ))->save(array('title' => 'Modify status', 'pid' => $pid, 'sort' => 1, 'hide' => 1, 'group' => 'user', 'ico_name' => 'home'));
                }
            }

            $list = M('Menu')->where(array(
                'url' => 'User/setpwd',
                'pid' => array('neq', 0)
            ))->select();

            if ($list[1]) {
                M('Menu')->where(array('id' => $list[1]['id']))->delete();
            } else if (!$list) {
                M('Menu')->add(array('url' => 'User/setpwd', 'title' => 'Change the administrator password', 'pid' => 3, 'sort' => 0, 'hide' => 1, 'group' => 'user', 'ico_name' => 'home'));
            } else {
                M('Menu')->where(array(
                    'url' => 'User/setpwd',
                    'pid' => array('neq', 0)
                ))->save(array('title' => 'Change the administrator password', 'pid' => 3, 'sort' => 0, 'hide' => 1, 'group' => 'user', 'ico_name' => 'home'));
            }

            if (M('Menu')->where(array('url' => 'AuthManager/index'))->delete()) {
                M('AuthRule')->where(array('status' => 1))->delete();
            }

            if (M('Menu')->where(array('url' => 'User/adminUser'))->delete()) {
                M('AuthRule')->where(array('status' => 1))->delete();
            }

            if (M('Menu')->where(array('url' => 'AdminUser/index'))->delete()) {
                M('AuthRule')->where(array('status' => 1))->delete();
            }

            if (M('Menu')->where(array('url' => 'Userlog/index'))->delete()) {
                M('AuthRule')->where(array('status' => 1))->delete();
            }

            if (M('Menu')->where(array('url' => 'Userwallet/index'))->delete()) {
                M('AuthRule')->where(array('status' => 1))->delete();
            }

            if (M('Menu')->where(array('url' => 'Userbank/index'))->delete()) {
                M('AuthRule')->where(array('status' => 1))->delete();
            }

            if (M('Menu')->where(array('url' => 'Usercoin/index'))->delete()) {
                M('AuthRule')->where(array('status' => 1))->delete();
            }

            if (M('Menu')->where(array('url' => 'Usergoods/index'))->delete()) {
                M('AuthRule')->where(array('status' => 1))->delete();
            }

            S(MODULE_NAME . CONTROLLER_NAME . 'checkUpdata', 1);
        }
    }

    private function testBonus()
    {
        exit;
        $userid = 62;
        $type = 'kyc';
        $result = $this->checkBonus($userid, $type);
        var_dump($this->BonusReceiveCount($userid, $type));
        exit;
        if (!empty($result)) {
            var_dump($result);
            echo "send " . $result['coin'] . $result['amount'];
            $coin = $result['coin'];
            $amount = $result['amount'];
            var_dump($this->sendBonus($userid, $coin, $amount, $type));
        } else {
            die('No such bonus');
        }
    }

    private function sendBonus($userid, $coin, $amount, $type)
    {

        $now = time();
        $mo = M();

        $mo->startTrans();

        $coind = $coin . 'd';

        $balance_before = $balance_before = $mo->table('codono_user_coin')->where(array('userid' => $userid))->find();

        $rs[] = $mo->table('codono_user_coin')->where(array('userid' => $userid))->setInc($coin, $amount);
        $rs[] = $bonus_added = $mo->table('codono_invit')->add(array('coin'=>$coin,'userid' => $userid, 'invit' => 0, 'name' => $coin, 'type' => $type, 'num' => $amount, 'mum' => $amount, 'fee' => $amount, 'addtime' => time(), 'status' => 1));


        $balance_after = $mo->table('codono_user_coin')->where(array('userid' => $userid))->find();
        $hash = md5($userid . '_' . $bonus_added . '_' . $now);
        $rs[] = $mo->table('codono_finance')->add(array('userid' => $userid, 'coinname' => $coin, 'num_a' => $balance_before[$coin], 'num_b' => $balance_before[$coind], 'num' => $balance_before[$coin] + $balance_before[$coind], 'fee' => $amount, 'type' => 1, 'name' => $type, 'nameid' => $bonus_added, 'remark' => $type, 'mum_a' => $balance_after[$coin], 'mum_b' => $balance_after[$coind], 'mum' => $balance_after[$coin] + $balance_after[$coind], 'move' => $hash, 'addtime' => time(), 'status' => 1));
        if (check_arr($rs)) {
            $mo->commit();
            // removed unlock/lock
            return true;
        } else {
            $mo->rollback();
            return false;
        }
    }

    private function BonusReceiveCount($userid, $type)
    {
        $count = M()->table('codono_invit')->where(array('userid' => $userid, 'type' => $type))->count();
        return $count;

    }

    private function checkBonus($userid, $type)
    {
        $now = time();
        $package = M('Bonus')->where(array(
            'status' => array('neq', 0),
            'addtime' => array('lt', $now),
            'endtime' => array('gt', $now),
            'uidstart' => array('lt', $userid),
            'uidend' => array('gt', $userid),
            'type' => $type))->find();
        $return = $package ?: array();
        return $return;
    }

    /**
     * @return void
     */
    private function sub_auth(): void
    {
        if (isset($_POST['rules'])) {
            sort($_POST['rules']);
            $_POST['rules'] = implode(',', array_unique($_POST['rules']));
        }

        $_POST['module'] = 'admin';
        $_POST['type'] = 1;//Common\Model\AuthGroupModel::TYPE_ADMIN;
        $AuthGroup = D('AuthGroup');
        $data = $AuthGroup->create();

        if ($data) {
            if (empty($data['id'])) {
                $r = $AuthGroup->add();
            } else {
                $r = $AuthGroup->save();
            }

            if ($r === false) {
                $this->error('operation failed' . $AuthGroup->getError());
            } else {
                $this->success('Successful operation!');
            }
        } else {
            $this->error('operation failed' . $AuthGroup->getError());
        }
    }


    /**
     * @param $id
     * @param $type
     * @param $model
     * @return void
     */
    public function sub_status($id, $type, $model): void
    {
        $data = [];
        if (APP_DEMO) {
            $this->error(L('SYSTEM_IN_DEMO_MODE'));
        }

        if (empty($id)) {
            $this->error(L('INCORRECT_REQ'));
        }

        if (empty($type)) {
            $this->error(L('INCORRECT_TYPE'));
        }

        if (strpos(',', $id)) {
            $id = implode(',', $id);
        }

        $where['id'] = array('in', $id);

        switch (strtolower($type)) {
            case 'forbid':
                $data = array('status' => 0);
                break;

            case 'resume':
                $data = array('status' => 1);
                break;

            case 'repeal':
                $data = array('status' => 2, 'endtime' => time());
                break;

            case 'delete':
                $data = array('status' => -1);
                break;

            case 'del':
                if (M($model)->where($where)->delete()) {
                    $this->success(L('SUCCESSFULLY_DONE'));
                } else {
                    $this->error(L('OPERATION_FAILED'));
                }

                break;

            default:
                $this->error(L('OPERATION_FAILED'));
        }
		

        if (M($model)->where($where)->save($data)) {
            $this->success(L('SUCCESSFULLY_DONE'));
        } else {
            $this->error(L('OPERATION_FAILED'));
        }
    }
    public function google_2fa()
    {
        $user = M('Admin')->where(array('id' => UID))->find();

        if (empty($_POST)) {
            if (!UID) {
                redirect(U('Login/login'));
            }
            
            $is_ga = ($user['ga'] ? 1 : 0);
            $this->assign('is_ga', $is_ga);

            if (!$is_ga) {
                $ga = new GoogleAuthenticator();
                $secret = $ga->createSecret();
                session('secret', $secret);
                $this->assign('Asecret', $secret);
                $qrCodeUrl = $ga->getQRCodeGoogleUrl($user['username'] . '%20-%20' . SHORT_NAME, $secret);
                $this->assign('qrCodeUrl', $qrCodeUrl);
            } else {
                $arr = explode('|', $user['ga']);
                $this->assign('ga_login', $arr[1]);
                $this->assign('ga_transfer', $arr[2]);
            }
            $this->display();
        } else {


            $delete = $secret = '';
            $gacode = trim(I('ga'));
            $type = trim(I('type'));
       
            if (!$gacode) {
                $this->error('Enter 2FA Code!');
            }

            if ($type == 'add') {
                $secret = session('secret');

                if (!$secret) {
                    $this->error('2FA has expired,Please refresh the page!');
                }
            } else if (($type == 'update') || ($type == 'delete')) {
             

                if (!$user['ga']) {
                    $this->error('2FA setup isnt done yet!');
                }

                
                $secret =  $user['ga'];
                $delete = ($type == 'delete' ? 1 : 0);
            } else {
                $this->error(L('Type is undefined'));
            }

            $ga = new GoogleAuthenticator();


            if ($ga->verifyCode($secret, $gacode, 1)) {
                $ga_val = ($delete == '' ? $secret : '');
                M('Admin')->save(array('id' => UID, 'ga' => $ga_val));
                $this->success(L('Successful operation'));
            } else {
                $this->error(L('Verification failed'));
            }
        }
    }
    private function getBalanceTypeKey($val)
    {

        // Check if the key exists in the array
        $allowed_types = $this->giveBalanceTypes();
        $found=array_search(($val), $allowed_types);
        
        if ($val && ($found)) {
            return $found;
        } else {
            return -1;
        }
    }

    private function giveBalanceTypes()
    {
        $types = self::allowed_types;
        unset($types['spot']);
        $newtypes['All Wallets']=0;
        $newtypes=array_merge($newtypes,$types);
        return ($newtypes);
    }
    private function getLevelInfo($uid)
    {
        $userInfo = M('User')->where(['id' => $uid])->field('usertype,accounttype')->find();
        $level_id = $userInfo['usertype'];
        $group_type = $userInfo['accounttype'];
        $where = ['group_type' => $group_type];

        $levels = M('UserLevel')->where($where)->select();
       
        return ['levels'=>$levels,'current_level'=>$level_id];
    }
}