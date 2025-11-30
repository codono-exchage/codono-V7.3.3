<?php

namespace Admin\Controller;

class AppController extends AdminController
{
    public function __construct()
    {
        parent::__construct();
    }

    public function config()
    {
        if (empty($_POST)) {
			$postData = I('post.');
            $appc = D('Appc')->find();
            $appc['pay'] = json_decode($appc['pay'], true);
            $show_coin = json_decode($appc['show_coin'], true);
            $Coin = D('coin')->where('type in ("rgb","qbb" ,"xrp","esmart","blockio","cryptonote","cryptoapis","coinpay","waves") and status = 1')->select();
            $appc['show_coin'] = array();

            foreach ($Coin as $val) {
                $appc['show_coin'][] = array('id' => $val['id'], 'name' => $val['title'] . '(' . $val['name'] . ')', 'flag' => $show_coin ? (in_array($val['id'], $show_coin) ? 1 : 0) : 1);
            }

            $show_market = json_decode($appc['show_market'],true);
            $Market = D('Market')->where('status = 1')->select();
            $appc['show_market'] = array();

            foreach ($Market as $val) {
                $coin_name = explode('_', $val['name']);
                $xnb_name = D('Coin')->where(array('name' => $coin_name[0]))->find()['title'];
                $rmb_name = D('Coin')->where(array('name' => $coin_name[1]))->find()['title'];
                $appc['show_market'][] = array('id' => $val['id'], 'name' => $xnb_name . '/' . $rmb_name . '(' . $val['name'] . ')', 'flag' => $show_market ? (in_array($val['id'], $show_market) ? 1 : 0) : 1);
            }

            $this->assign('appCon', $appc);
            $this->display();
        } else {
			 $postData = I('post.');
            $postData['pay'] = json_encode($postData['pay']);
            $postData['show_coin'] = json_encode($postData['show_coin']);
            $postData['show_market'] = json_encode($postData['show_market']);

            if (D('Appc')->save($postData)) {
                $this->success('Saved successfully!');
            } else {
                $this->error('No changes were made.');
            }
        }
    }

  
    public function index()
    {
        $rankMap = array();
        $AppVip = D('AppVip')->where(array('status' => 1))->select();

        foreach ($AppVip as $val) {
            $rankMap[$val['id']] = $val['name'];
        }

        $this->assign('rankMap', $rankMap);
        $this->Model = D('Appadsblock');
        $where = array();
        $count = $this->Model->where($where)->count();
        $Page = new \Think\Page($count, 15);
        $show = $Page->show();
        $list = $this->Model->where($where)->order('id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->display();
    }

    public function banner_edit()
    {
		$input_post=I('post.');
		$input_get=I('get.');
        if (empty($input_post)) {
            $AppVip = D('AppVip')->where(array('status' => 1))->select();
            $this->assign('AppVip', $AppVip);

            if (isset($input_get['id'])) {
                $this->data = D('Appadsblock')->where(array('id' => trim($input_get['id'])))->find();
            } else {
                $this->data = null;
            }

            $this->display();
        } else {
            if (APP_DEMO) {
                $this->error(L('SYSTEM_IN_DEMO_MODE'));
            }

            if ($_POST['id']) {
                $rs = D('Appadsblock')->save($input_post);
            } else {
                $_POST['adminid'] = session('admin_id');
                $rs = D('Appadsblock')->add($input_post);
            }

            if ($rs) {
                $this->success(L('SUCCESSFULLY_DONE'));
            } else {
                $this->error('No changes were made.');
            }
        }
    }

    public function banner_edit_status()
    {
		$input_post=I('post.');
		$input_get=I('get.');
        if (APP_DEMO) {
            $this->error(L('SYSTEM_IN_DEMO_MODE'));
        }

        if (IS_POST) {
            $id = array();
            $id = implode(',', $input_post['id']);
        } else {
            $id = $input_get['id'];
        }

        if (empty($id)) {
            $this->error('Please select the data to be operated.');
        }

        $where['id'] = array('in', $id);
        $method = $input_get['method'];

        switch (strtolower($method)) {
            case 'forbid':
                $data = array('status' => 0);
                break;

            case 'resume':
                $data = array('status' => 1);
                break;

            case 'delete':
                if (D('Appadsblock')->where($where)->delete()) {
                    $this->success(L('SUCCESSFULLY_DONE'));
                } else {
                    $this->error('No changes were made.');
                }

                break;

            default:
                $this->error('Invalid parameters');
        }

        if (D('Appadsblock')->where($where)->save($data)) {
            $this->success(L('SUCCESSFULLY_DONE'));
        } else {
            $this->error('No changes were made.');
        }
    }


    public function upload()
    {
        $upload = new \Think\Upload();
        $upload->maxSize = 3145728;
        $upload->exts = array('jpg', 'gif', 'png', 'jpeg');
        $upload->rootPath = './Upload/app/';
        $upload->autoSub = false;
        $info = $upload->upload();

        foreach ($info as $k => $v) {
            $path = '/Upload/app/' . $v['savepath'] . $v['savename'];
            echo $path;
            exit();
        }
    }
}