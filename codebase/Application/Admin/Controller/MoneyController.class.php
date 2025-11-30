<?php

namespace Admin\Controller;

class MoneyController extends AdminController
{
    private $Model;

    public function __construct()
    {
        parent::__construct();
        $this->Model = M('Money');
        $this->Title = 'Conduct financial transactions';
    }

    public function index()
    {
        //$this->checkUpdata();
        $where = array(
            'status' => array('egt', 0)
        );
        $count = $this->Model->where($where)->count();
        $Page = new \Think\Page($count, 15);
        $show = $Page->show();
        $list = $this->Model->where($where)->order('id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();

        foreach ($list as $k => $v) {
            $list[$k]['tian'] = $list[$k]['tian'] . ' ' . $this->danweitostr($list[$k]['danwei']);
        }

        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->display();
    }

    public function edit()
    {
        if (empty($_GET['id'])) {
            $this->data = false;
        } else {
            $data = array();
            $data = $this->Model->where(array('id' => trim($_GET['id'])))->find();
            $data['deal'] = M('MoneyLog')->where(array('money_id' => $data['id']))->sum('num');
            $this->data = $data;
        }

        $this->display();
    }

    public function save()
    {
        if (APP_DEMO) {
            $this->error(L('SYSTEM_IN_DEMO_MODE'));
        }

        if (strtotime($_POST['endtime']) != strtotime(addtime(strtotime($_POST['endtime'])))) {
            $this->error('End Time format error!');
        }

        if (floatval($_POST['num']) < floatval($_POST['deal'])) {
            $this->error('Synthesis of no less than the total amount of the amount of the synthesized');
        }

        $_POST['addtime'] = strtotime($_POST['addtime']);
        $_POST['endtime'] = strtotime($_POST['endtime']);

        if (($_POST['fee'] < 0.01) || (100 < $_POST['fee'])) {
            $this->error('Interest for the period range0.01 -- 100 %!');
        }

        if (!floatval($_POST['tian'])) {
            $this->error('Financial period can not be empty');
        }

        switch ($_POST['danwei']) {
            case 'y':
                $_POST['step'] = $_POST['tian'] * 12 * 30 * 24 * 60 * 60;
                break;

            case 'm':
                $_POST['step'] = $_POST['tian'] * 30 * 24 * 60 * 60;
                break;

            case 'd':
                $_POST['step'] = $_POST['tian'] * 24 * 60 * 60;
                break;

            case 'h':
                $_POST['step'] = $_POST['tian'] * 60 * 60;
                break;

            default:

            case 'i':
                $_POST['step'] = $_POST['tian'] * 60;
                break;
        }

        if ($_POST['outfee'] && (($_POST['outfee'] < 0.01) || (100 < $_POST['outfee']))) {
            $this->error('Current cashFeesrangefor0.01 -- 100 %!');
        }

        if ($_POST['id']) {
            $rs = $this->Model->save($_POST);
        } else {
            $rs = $this->Model->add($_POST);
        }

        if ($rs) {
            $this->success(L('SUCCESSFULLY_DONE'));
        } else {
            debug($this->Model->getDbError(), 'lastSql');
            $this->error(L('OPERATION_FAILED'));
        }
    }

    public function log($money_id = NULL, $name = NULL)
    {
        if ($name && check($name, 'username')) {
            $where['userid'] = M('User')->where(array('money_id' => $money_id, 'username' => $name))->getField('id');
        } else {
            $where = array(
                array('money_id' => $money_id)
            );
        }

        $this->Model = M('MoneyLog');
        $count = $this->Model->where($where)->count();
        $Page = new \Think\Page($count, 15);
        $show = $Page->show();
        $list = $this->Model->where($where)->order('id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();

        foreach ($list as $k => $v) {
            $list[$k]['username'] = M('User')->where(array('id' => $v['userid']))->getField('username');
            $list[$k]['money'] = M('Money')->where(array('id' => $v['money_id']))->find();
            $list[$k]['money']['tian'] = $list[$k]['money']['tian'] . ' ' . $this->danweitostr($list[$k]['money']['danwei']);
        }

        $this->assign('money_id', $money_id);
        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->display();
    }

    public function fee($userid = NULL, $money_id = NULL)
    {
        $where['userid'] = $userid;
        $user = D('User')->where(array('id' => $userid))->find();

        if (!$user) {
        }

        $this->Model = M('MoneyFee');
        $count = $this->Model->where($where)->count();
        $Page = new \Think\Page($count, 15);
        $show = $Page->show();
        $list = $this->Model->where($where)->order('id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();

        foreach ($list as $k => $v) {
            $list[$k]['money'] = M('Money')->where(array('id' => $v['money_id']))->find();
        }

        debug($v, 'v');
        $this->assign('money_id', $money_id);
        $this->assign('user', $user);
        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->display();
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
                if ($this->Model->where($where)->delete()) {
                    $this->success(L('SUCCESSFULLY_DONE'));
                } else {
                    $this->error(L('OPERATION_FAILED'));
                }

                break;

            default:
                $this->error('Invalid parameters');
        }

        if ($this->Model->where($where)->save($data)) {
            $this->success(L('SUCCESSFULLY_DONE'));
        } else {
            $this->error(L('OPERATION_FAILED'));
        }
    }

    private function danweitostr($danwei)
    {
        switch ($danwei) {
            case 'y':
                return 'year';
                break;

            case 'm':
                return 'month';
                break;

            case 'd':
                return 'day';
                break;

            case 'h':
                return 'hour';
                break;

            default:

            case 'i':
                return 'minute';
                break;
        }
    }

    public function checkUpdata()
    {
    }
}