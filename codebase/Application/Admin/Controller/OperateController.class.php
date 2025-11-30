<?php

namespace Admin\Controller;

class OperateController extends AdminController
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
        //$this->checkUpdata();

        if ($name) {
            $where['userid'] = M('User')->where(array('username' => $name))->getField('id');
        }

        $count = $this->Model->where($where)->count();
        $Page = new \Think\Page($count, 15);
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

    public function checkUpdata()
    {
        if (!S(MODULE_NAME . CONTROLLER_NAME . 'checkUpdata')) {
            $list = M('Menu')->where(array(
                'url' => 'Operate/index',
                'pid' => array('neq', 0)
            ))->select();

            if ($list[1]) {
                M('Menu')->where(array('id' => $list[1]['id']))->delete();
            } else if (!$list) {
                M('Menu')->add(array('url' => 'Operate/index', 'title' => 'Promotion award', 'pid' => 8, 'sort' => 1, 'hide' => 0, 'group' => 'Operations', 'ico_name' => 'share'));
            } else {
                M('Menu')->where(array(
                    'url' => 'Operate/index',
                    'pid' => array('neq', 0)
                ))->save(array('title' => 'Promotion award', 'pid' => 8, 'sort' => 1, 'hide' => 0, 'group' => 'Operations', 'ico_name' => 'share'));
            }

            if (M('Menu')->where(array('url' => 'invit/index'))->delete()) {
                M('AuthRule')->where(array('status' => 1))->delete();
            }

            S(MODULE_NAME . CONTROLLER_NAME . 'checkUpdata', 1);
        }
    }
}