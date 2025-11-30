<?php

namespace Admin\Controller;

class IEOController extends AdminController
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

        $count = M('Ieo')->where($where)->count();
        $Page = new \Think\Page($count, 15);
        $show = $Page->show();
        $list = M('Ieo')->where($where)->order('id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->display();
    }


    public function ieoimage()
    {
        $upload = new \Think\Upload();
        $upload->maxSize = 3145728;
        $upload->exts = array('jpg', 'gif', 'png', 'jpeg');
        $upload->rootPath = './Upload/ieo/';
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
            $this->data = M('Ieo')->where(array('id' => trim($_GET['id'])))->find();
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

        if (strtotime($_POST['time']) != strtotime(addtime(strtotime($_POST['time'])))) {
            $this->error('On Time format error!');
        }
		$where=array('id'=>$id);
		if ($_POST['homepage'] == 1) {
            M('Ieo')->where($where)->setField('homepage', '1');
        }
		if ($_POST['homepage'] == 0) {
			M('Ieo')->where($where)->setField('homepage', '0');
		}


        if ($_POST['tuijian'] == 1) {
            //Recommended words FirstotherofrecommendmodifyTo not recommend
            M('Ieo')->where('tuijian=1')->setField('tuijian', '2');
        }


        if ($_POST['id']) {
            $rs = M('Ieo')->save($_POST);
        } else {

            $rs = M('Ieo')->add($_POST);
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
                if (M('Ieo')->where($where)->delete()) {
                    $this->success(L('SUCCESSFULLY_DONE'));
                } else {
                    $this->error(L('OPERATION_FAILED'));
                }

                break;

            default:
                $this->error('Invalid parameters');
        }

        if (M('Ieo')->where($where)->save($data)) {
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

        $count = M('IeoLog')->where($where)->count();
        $Page = new \Think\Page($count, 15);
        $show = $Page->show();
        $list = M('IeoLog')->where($where)->order('id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();

        foreach ($list as $k => $v) {
            $list[$k]['username'] = M('User')->where(array('id' => $v['userid']))->getField('username');
        }

        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->display();
    }


public function timeline($ieo_id = NULL, $field = NULL, $status = NULL)
    {

        $where = array();

        if ($ieo_id) {
                $where['ieo_id'] = $ieo_id;
        }

        if ($status) {
            $where['status'] = $status - 1;
        }

        $count = M('IeoTimeline')->where($where)->count();
        $Page = new \Think\Page($count, 15);
        $show = $Page->show();
        $list = M('IeoTimeline')->where($where)->order('id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $this->assign('ieo_id', $ieo_id);
		$this->assign('list', $list);
        
		$this->assign('page', $show);
        $this->display();
    }
	public function TimeLineedit()
    {
        if (empty($_GET['id'])) {
            $this->data = false;
        } else {
            $this->data = M('IeoTimeline')->where(array('id' => trim($_GET['id'])))->find();
        }
		if (!empty($_GET['ieo_id'])) {
			//$this->ieo_id=(int)$_GET['ieo_id'];
			$this->data['ieo_id']= (int)$_GET['ieo_id'];
		}
		//var_dump($this->ieo_id);

        $this->display();
    }
	public function TimeLinesave()
    {
        if (APP_DEMO) {
            $this->error(L('SYSTEM_IN_DEMO_MODE'));
        }
		$id = (int)$_GET['id'];
        
		$where=array('id'=>$id);
		if ($_POST['status'] == 1) {
            M('IeoTimeline')->where($where)->setField('status', '1');
        }
		if ($_POST['homepage'] == 0) {
			M('IeoTimeline')->where($where)->setField('status', '0');
		}


        if ($_POST['id']) {
            $rs = M('IeoTimeline')->save($_POST);
        } else {

            $rs = M('IeoTimeline')->add($_POST);
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
                if (M('IeoTimeline')->where($where)->delete()) {
                    $this->success(L('SUCCESSFULLY_DONE'));
                } else {
                    $this->error(L('OPERATION_FAILED'));
                }

                break;

            default:
                $this->error('Invalid parameters');
        }

        if (M('IeoTimeline')->where($where)->save($data)) {
            $this->success(L('SUCCESSFULLY_DONE'));
        } else {
            $this->error(L('OPERATION_FAILED'));
        }
    }
}