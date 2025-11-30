<?php

namespace Admin\Controller;

use Think\Page;

class FaucetController extends AdminController
{
    public function index($name = NULL, $field = NULL, $status = NULL)
    {
        $where = array();

        if ($field && $name) {
            if ($field == 'username') {
                $where['userid'] = M('User')->where(array('username' => $name))->getField('id');
            } else if ($field == 'name') {
                $where['name'] = array('like', '%' . $name . '%');
            } else {
                $where[$field] = $name;
            }
        }

        if ($status) {
            $where['status'] = $status - 1;
        }

        $count = M('Faucet')->where($where)->count();
        $Page = new Page($count, 15);
        $show = $Page->show();
        $list = M('Faucet')->where($where)->order('id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->display();
    }

    public function edit()
    {
        if (empty($_GET['id'])) {
            $this->data = false;
        } else {
            $this->data = M('Faucet')->where(array('id' => trim($_GET['id'])))->find();
        }

        $this->display();
    }

    public function save()
    {
		
        if (APP_DEMO) {
            $this->error(L('SYSTEM_IN_DEMO_MODE'));
        }
		$id = (int)$_GET['id'];
        $_POST['addtime'] = time();
		if (strtotime($_POST['endtime'])) {
            $_POST['endtime']=strtotime($_POST['endtime']);
        }
		
        if (strtotime($_POST['time']) != strtotime(addtime(strtotime($_POST['time'])))) {
            $this->error('On Time format error!');
        }
		$where=array('id'=>$id);
		if ($_POST['homepage'] == 1) {
            M('Faucet')->where($where)->setField('homepage', '1');
        }
		if ($_POST['homepage'] == 0) {
			M('Faucet')->where($where)->setField('homepage', '0');
		}


        if ($_POST['tuijian'] == 1) {
            //Recommended words FirstotherofrecommendmodifyTo not recommend
            M('Faucet')->where('tuijian=1')->setField('tuijian', '2');
        }


        if ($_POST['id']) {
            $rs = M('Faucet')->save($_POST);
        } else {
            $rs = M('Faucet')->add($_POST);
        }

        if ($rs) {
            $this->success(L('SUCCESSFULLY_DONE'));
        } else {
            $this->error(L('OPERATION_FAILED'));
        }
    }

    public function status()
    {
        if (APP_DEMO) {
            $this->error(L('SYSTEM_IN_DEMO_MODE'));
        }

        if (IS_POST) {
            $id = array();
            $id = implode(',', $_POST['id']);
        } else {
            $id = $_GET['id'];
        }

        if (empty($id)) {
            $this->error('please chooseData to be operated!');
        }

        $where['id'] = array('in', $id);
        $method = $_GET['method'];

        switch (strtolower($method)) {
            case 'forbid':
                $data = array('status' => 0);
                break;

            case 'resume':
                $data = array('status' => 1);
                break;

            case 'delete':
                if (M('Faucet')->where($where)->delete()) {
                    $this->success(L('SUCCESSFULLY_DONE'));
                } else {
                    $this->error(L('OPERATION_FAILED'));
                }

                break;

            default:
                $this->error('Invalid parameters');
        }

        if (M('Faucet')->where($where)->save($data)) {
            $this->success(L('SUCCESSFULLY_DONE'));
        } else {
            $this->error(L('OPERATION_FAILED'));
        }
    }

    public function log($name = NULL)
    {
        if ($name && check($name, 'username')) {
            $where['userid'] = M('User')->where(array('username' => $name))->getField('id');
        } else {
            $where = array();
        }

        $count = M('FaucetLog')->where($where)->count();
        $Page = new Page($count, 15);
        $show = $Page->show();
        $list = M('FaucetLog')->where($where)->order('id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();

        foreach ($list as $k => $v) {
            $list[$k]['username'] = M('User')->where(array('id' => $v['userid']))->getField('username');
        }

        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->display();
    }
}