<?php

namespace Admin\Controller;

use Think\Page;

class InvitController extends AdminController
{
    private $Model;

    public function __construct()
    {
        parent::__construct();
        $this->Model = M('Invit');
        $this->Title = 'Promotion record';
    }

    public function index($name = NULL)
    {
        $where=[];
        if ($name) {
            $where['userid'] = M('User')->where(array('username' => $name))->getField('id');
        }

        $count = $this->Model->where($where)->count();
        $Page = new Page($count, 15);
        $show = $Page->show();
        $list = $this->Model->where($where)->order('id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();

        foreach ($list as $k => $v) {
            $list[$k]['username'] = M('User')->where(array('id' => $v['userid']))->getField('username');
            $list[$k]['invit'] = M('User')->where(array('id' => $v['invit']))->getField('username');
        }

        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->display();
    }
}
