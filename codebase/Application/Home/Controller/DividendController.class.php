<?php

namespace Home\Controller;

use Think\Page;

class DividendController extends HomeController
{
    public function index()
    {
        if (!userid()) {
            redirect(U('Login/login'));
        }

        $this->assign('prompt_text', D('Text')->get_content('game_dividend'));
        $coin_list = D('Coin')->get_all_xnb_list();
        $list= [];
        foreach ($coin_list as $k => $v) {
            $list[$k]['img'] = D('Coin')->get_img($k);
            $list[$k]['title'] = $v;
            $list[$k]['quanbu'] = D('Coin')->get_sum_coin($k);
            $list[$k]['wodi'] = D('Coin')->get_sum_coin($k, userid());
            $list[$k]['bili'] = round(($list[$k]['wodi'] / $list[$k]['quanbu']) * 100, 2) . '%';
        }

        $this->assign('list', $list);
        $this->display();
    }

    public function log()
    {
        if (!userid()) {
            redirect(U('Login/login'));
        }

        $this->assign('prompt_text', D('Text')->get_content('game_dividend_log'));
        $where['userid'] = userid();
        $Model = M('DividendLog');
        $count = $Model->where($where)->count();
        $Page = new Page($count, 15);
        $show = $Page->show();
        $list = $Model->where($where)->order('id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->display();
    }

    public function uninstall()
    {

    }

}

