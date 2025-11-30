<?php

namespace Admin\Controller;

use Think\Page;
use Think\Upload;

class IssueController extends AdminController
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

        $count = M('Issue')->where($where)->count();
        $Page = new Page($count, 15);
        $show = $Page->show();
        $list = M('Issue')->where($where)->order('id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->display();
    }


    public function issueimage()
    {
        $upload = new Upload();
        $upload->maxSize = 3145728;
        $upload->exts = array('jpg', 'gif', 'png', 'jpeg');
        $upload->rootPath = './Upload/issue/';
        $upload->autoSub = false;
        $info = $upload->upload();

        foreach ($info as $k => $v) {
            $path = $v['savepath'] . $v['savename'];
            echo $path;
            exit();
        }
    }


    public function edit()
    {


        if (empty($_GET['id'])) {
            $this->data = false;
        } else {
            $this->data = M('Issue')->where(array('id' => trim($_GET['id'])))->find();
        }
        $show_coin = json_decode($this->data['show_coin']);
        $appc['show_coin'] = array();

        foreach (C('coin') as $val) {
			if($val['symbol']='' ||$val['symbol'] ==null){
					$appc['show_coin'][] = array('id' => $val['id'], 'name' => $val['name'], 'flag' => in_array($val['name'], $show_coin) ? 1 : 0);
			}
            
        }
        $this->assign('appCon', $appc);
        $this->display();
    }

    public function save()
    {
        $input = I('post.');
        if (empty($input)) {
            $this->error('Please add data');
        }
        $input['show_coin'] = json_encode($input['show_coin']);

        if (APP_DEMO) {
            $this->error(L('SYSTEM_IN_DEMO_MODE'));
        }
        $id = (int)$_GET['id'];
        $input['addtime'] = time();

        if (strtotime($input['time']) != strtotime(addtime(strtotime($input['time'])))) {
            $this->error('On Time format error!');
        }
        $where = array('id' => $id);
        if ($input['homepage'] == 1) {
            M('Issue')->where($where)->setField('homepage', '1');
        }
        if ($input['homepage'] == 0) {
            M('Issue')->where($where)->setField('homepage', '0');
        }


        if ($input['tuijian'] == 1) {
            //Recommended words FirstotherofrecommendmodifyTo not recommend
            M('Issue')->where('tuijian=1')->setField('tuijian', '2');
        }
		$input['content']=urldecode($input['content']); 
		$input['content'] = html_entity_decode($input['content'], ENT_QUOTES, 'UTF-8');
        if ($input['id']) {
            $rs = M('Issue')->save($input);
        } else {

            $rs = M('Issue')->add($input);
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
                if (M('Issue')->where($where)->delete()) {
                    $this->success(L('SUCCESSFULLY_DONE'));
                } else {
                    $this->error(L('OPERATION_FAILED'));
                }

                break;

            default:
                $this->error('Invalid parameters');
        }

        if (M('Issue')->where($where)->save($data)) {
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

        $count = M('IssueLog')->where($where)->count();
        $Page = new Page($count, 15);
        $show = $Page->show();
        $list = M('IssueLog')->where($where)->order('id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();

        foreach ($list as $k => $v) {
            $list[$k]['username'] = M('User')->where(array('id' => $v['userid']))->getField('username');
        }

        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->display();
    }


    public function timeline($issue_id = NULL, $field = NULL, $status = NULL)
    {

        $where = array();

        if ($issue_id) {
            $where['issue_id'] = $issue_id;
        }

        if ($status) {
            $where['status'] = $status - 1;
        }

        $count = M('IssueTimeline')->where($where)->count();
        $Page = new Page($count, 15);
        $show = $Page->show();
        $list = M('IssueTimeline')->where($where)->order('id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $this->assign('issue_id', $issue_id);
        $this->assign('list', $list);

        $this->assign('page', $show);
        $this->display();
    }

    public function TimeLineedit()
    {
        if (empty($_GET['id'])) {
            $this->data = false;
        } else {
            $this->data = M('IssueTimeline')->where(array('id' => trim($_GET['id'])))->find();
        }
        if (!empty($_GET['issue_id'])) {
            //$this->issue_id=(int)$_GET['issue_id'];
            $this->data['issue_id'] = (int)$_GET['issue_id'];
        }
        //var_dump($this->issue_id);

        $this->display();
    }

    public function TimeLinesave()
    {
        if (APP_DEMO) {
            $this->error(L('SYSTEM_IN_DEMO_MODE'));
        }
        $id = (int)$_GET['id'];

        $where = array('id' => $id);
        if ($_POST['status'] == 1) {
            M('IssueTimeline')->where($where)->setField('status', '1');
        }
        if ($_POST['homepage'] == 0) {
            M('IssueTimeline')->where($where)->setField('status', '0');
        }


        if ($_POST['id']) {
            $rs = M('IssueTimeline')->save($_POST);
        } else {

            $rs = M('IssueTimeline')->add($_POST);
        }

        if ($rs) {
            $this->success(L('SUCCESSFULLY_DONE'));
        } else {
            $this->error(L('OPERATION_FAILED'));
        }
    }

    public function TimeLinestatus()
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
            $this->error(L('Please choose a record!'));
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
                if (M('IssueTimeline')->where($where)->delete()) {
                    $this->success(L('SUCCESSFULLY_DONE'));
                } else {
                    $this->error(L('OPERATION_FAILED'));
                }

                break;

            default:
                $this->error('Invalid parameters');
        }

        if (M('IssueTimeline')->where($where)->save($data)) {
            $this->success(L('SUCCESSFULLY_DONE'));
        } else {
            $this->error(L('OPERATION_FAILED'));
        }
    }
}