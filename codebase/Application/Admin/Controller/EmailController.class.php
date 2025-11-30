<?php

namespace Admin\Controller;
use Think\Page;

class EmailController extends AdminController
{
	public function __construct(){
		parent::__construct();
		$this->page_title=$this->title='All Emails';
		
		$controllerName = CONTROLLER_NAME;
    	$actionName = ACTION_NAME;

    // Combine them as you like
    $this->fullPath = $controllerName . '/' . $actionName;
		
    // Assign to view
    
      //  die('To use this demo feature for auto login , comment this line number '.__LINE__);
	}
	public function admin(){
		$this->page_title='Your Inbox';
		$admin_id=session('admin_id');
		$email=M('Admin')->where(array('id' => $admin_id))->getField('email');
		$where['to_email']=$email;
		$this->index($where);
		
	}
	public function users(){
		$this->page_title='User Emails';
		$admin_id=session('admin_id');
		$email=M('Admin')->where(array('id' => $admin_id))->getField('email');
		$where['to_email']=['neq',$email];
		$this->index($where);
	}
	public function unsent(){
		$this->page_title='Unsent Emails';
		
		$where['status']=['eq',0];
		$this->index($where);
	}

	public function status(){
		
		$input_post=I('post.');
		$input_get=I('get.');	
		if (IS_POST) {
            $id = array();
            $id = implode(',', $input_post['id']);
        } else {
            $id = $input_get['id'];
        }
		$where['id'] = array('in', $id);
		$method = $input_get['method']?:'delete';
		
		switch (strtolower($method)) {
            case 'delete':
        
		if (D('Notification')->where($where)->delete()) {
			$this->success(L('SUCCESSFULLY_DONE'));
		} else {
			$this->error('No changes were made.');
		}
		break;
		default:
		$this->error('Invalid parameters');
		}
	}


	public function index(){
		$keyword = I('get.search', '', 'trim'); // Get the search keyword from the GET request
		$status = I('get.status', null, 'intval'); // Get the status from the GET request, default to null if not provided

    	$where = array();
    	if (!empty($keyword)) {
        	$where['_string'] = "(to_email LIKE '%$keyword%' OR subject LIKE '%$keyword%' OR content LIKE '%$keyword%')";
    	}
		  // Add status condition if specified
		if ($status !== null && $status !== '' && ($status == 0 || $status == 2)) {
			$where['status'] = $status;
		}
        $mo = M('Notification');
        
        $count = $mo->where($where)->count();
		
        $Page = new \Think\Page($count, 15);
        $show = $Page->show();
        $list = $mo->where($where)->order('id desc')->limit($Page->firstRow . ',' . $Page->listRows)->order('id desc')->select();
		$this->assign('count',$count);
        $this->assign('list', $list);
        $this->assign('page', $show);
		$this->assign('controllerAction', $this->fullPath);
        $this->display('Email/index');
	}
	public function view(){
		$id=I('get.id');
         $mo = M('Notification');
         $where = ['id'=>$id];
         $content = $mo->where($where)->find();
		
         $this->assign('mail', $content);
        // $this->assign('page', $show);
        $this->display();
	}
	public function resendEmail() {
		$id = I('post.id', 0, 'intval');  // Get the email ID from the POST request
		if ($id) {
			$result = M('Notification')->where(array('id' => $id))->setField('status', 0);
			if ($result) {
				$this->ajaxReturn(array('status' => 'success', 'message' => 'Email marked for resend.'));
			} else {
				$this->ajaxReturn(array('status' => 'error', 'message' => 'Failed to update the status.'));
			}
		} else {
			$this->ajaxReturn(array('status' => 'error', 'message' => 'Invalid request.'));
		}
	}
}

