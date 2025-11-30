<?php

namespace Admin\Controller;

class AuthManagerController extends AdminController
{
    public function index()
    {
        $list = $this->lists('AuthGroup', array('module' => 'admin'), 'id asc');
        $list = int_to_string($list);
        $this->assign('_list', $list);
        $this->assign('_use_tip', true);
        $this->meta_title = 'Auth management';
        $this->display('index');
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
                $temp['type'] = Common\Model\AuthRuleModel::RULE_URL;
            } else {
                $temp['type'] = Common\Model\AuthRuleModel::RULE_MAIN;
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
            trace('[' . 'Admin\\Controller\\AuthManagerController::updateRules' . ']:' . $AuthRule->getDbError());
            return false;
        } else {
            return true;
        }
    }

    public function createGroup()
    {
        if (empty($this->auth_group)) {
            $this->assign('auth_group', array('title' => null, 'id' => null, 'description' => null, 'rules' => null));
        }

        $this->meta_title = 'New User Group';
        $this->display('editgroup');
    }

    public function editGroup()
    {
        $auth_group = M('AuthGroup')->where(array('module' => 'admin', 'type' => Common\Model\AuthGroupModel::TYPE_ADMIN))->find((int)$_GET['id']);
        $this->assign('auth_group', $auth_group);
        $this->meta_title = 'Edit User Groups';
        $this->display();
    }

    public function access()
    {
        $this->updateRules();
        $auth_group = M('AuthGroup')->where(array(
            'status' => array('egt', '0'),
            'module' => 'admin',
            'type' => Common\Model\AuthGroupModel::TYPE_ADMIN
        ))->getfield('id,id,title,rules');
        $node_list = $this->returnNodes();
        $map = array('module' => 'admin', 'type' => Common\Model\AuthRuleModel::RULE_MAIN, 'status' => 1);
        $main_rules = M('AuthRule')->where($map)->getField('name,id');
        $map = array('module' => 'admin', 'type' => Common\Model\AuthRuleModel::RULE_URL, 'status' => 1);
        $child_rules = M('AuthRule')->where($map)->getField('name,id');
        $this->assign('main_rules', $main_rules);
        $this->assign('auth_rules', $child_rules);
        $this->assign('node_list', $node_list);
        $this->assign('auth_group', $auth_group);
        $this->assign('this_group', $auth_group[(int)$_GET['group_id']]);
        $this->meta_title = 'Access authorization';
        $this->display('managergroup');
    }

    public function writeGroup()
    {
        if (isset($_POST['rules'])) {
            sort($_POST['rules']);
            $_POST['rules'] = implode(',', array_unique($_POST['rules']));
        }

        $_POST['module'] = 'admin';
        $_POST['type'] = Common\Model\AuthGroupModel::TYPE_ADMIN;
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
                $this->success('Successful operation!', U('index'));
            }
        } else {
            $this->error('operation failed' . $AuthGroup->getError());
        }
    }

    public function changeStatus($method = NULL)
    {
        if (empty($_REQUEST['id'])) {
            $this->error('please chooseData to be operated!');
        }

        switch (strtolower($method)) {
            case 'forbidgroup':
                $this->forbid('AuthGroup');
                $this->success('Successful operation', '/');
                break;

            case 'resumegroup':
                $this->resume('AuthGroup');
                $this->success('Successful operation', '/');
                break;

            case 'deletegroup':
                $this->delete('AuthGroup');
                $this->success('Successful operation', '/');
                break;

            default:
                $this->error($method . 'Invalid parameters');
        }
    }

    public function user($group_id)
    {
        if (empty($group_id)) {
            $this->error('Parameter error');
        }

        $auth_group = M('AuthGroup')->where(array(
            'status' => array('egt', '0'),
            'module' => 'admin',
            'type' => Common\Model\AuthGroupModel::TYPE_ADMIN
        ))->getfield('id,id,title,rules');
        $prefix = C('DB_PREFIX');
        $l_table = $prefix . Common\Model\AuthGroupModel::MEMBER;
        $r_table = $prefix . Common\Model\AuthGroupModel::AUTH_GROUP_ACCESS;
        $model = M()->table($l_table . ' m')->join($r_table . ' a ON m.id=a.uid');
        $_REQUEST = array();
        $list = $this->lists($model, array(
            'a.group_id' => $group_id,
            'm.status' => array('egt', 0)
        ), 'm.id asc', null, 'm.id,m.username,m.nickname,m.last_login_time,m.last_login_ip,m.status');
        int_to_string($list);
        $this->assign('_list', $list);
        $this->assign('auth_group', $auth_group);
        $this->assign('this_group', $auth_group[(int)$_GET['group_id']]);
        $this->meta_title = 'Members of the authorized';
        $this->display();
    }

    public function category()
    {
        $auth_group = M('AuthGroup')->where(array(
            'status' => array('egt', '0'),
            'module' => 'admin',
            'type' => Common\Model\AuthGroupModel::TYPE_ADMIN
        ))->getfield('id,id,title,rules');
        $group_list = D('Category')->getTree();
        $authed_group = Common\Model\AuthGroupModel::getCategoryOfGroup(I('group_id'));
        $this->assign('authed_group', implode(',', (array)$authed_group));
        $this->assign('group_list', $group_list);
        $this->assign('auth_group', $auth_group);
        $this->assign('this_group', $auth_group[(int)$_GET['group_id']]);
        $this->meta_title = 'Classification Authorization';
        $this->display();
    }

    public function tree($tree = NULL)
    {
        $this->assign('tree', $tree);
        $this->display('tree');
    }

    public function group()
    {
        $uid = I('uid');
        $auth_groups = D('AuthGroup')->getGroups();
        $user_groups = Common\Model\AuthGroupModel::getUserGroup($uid);
        $ids = array();

        foreach ($user_groups as $value) {
            $ids[] = $value['group_id'];
        }

        $nickname = D('Member')->getNickName($uid);
        $this->assign('nickname', $nickname);
        $this->assign('auth_groups', $auth_groups);
        $this->assign('user_groups', implode(',', $ids));
        $this->display();
    }

    public function addToGroup()
    {
        $uid = I('uid');

        if (empty($uid)) {
            $this->error('Please enter the backstage member information');
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
                $this->error('User does not exist(id username nickname or phone)');
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

                $this->error('already exists[' . $res['title'] . ']group,Add unrepeatable');
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

    public function removeFromGroup()
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

    public function addToCategory()
    {
        $cid = I('cid');
        $gid = I('group_id');

        if (empty($gid)) {
            $this->error('Parameter is incorrect');
        }

        $AuthGroup = D('AuthGroup');

        if (!$AuthGroup->find($gid)) {
            $this->error('User group does not exist');
        }

        if ($cid && !$AuthGroup->checkCategoryId($cid)) {
            $this->error($AuthGroup->error);
        }

        if ($AuthGroup->addToCategory($gid, $cid)) {
            $this->success('Successful operation');
        } else {
            $this->error('operation failed');
        }
    }

    public function addToModel()
    {
        $mid = I('id');
        $gid = I('get.group_id');

        if (empty($gid)) {
            $this->error('Parameter is incorrect');
        }

        $AuthGroup = D('AuthGroup');

        if (!$AuthGroup->find($gid)) {
            $this->error('User group does not exist');
        }

        if ($mid && !$AuthGroup->checkModelId($mid)) {
            $this->error($AuthGroup->error);
        }

        if ($AuthGroup->addToModel($gid, $mid)) {
            $this->success('Successful operation');
        } else {
            $this->error('operation failed');
        }
    }
}