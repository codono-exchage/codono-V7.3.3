<?php

namespace Home\Controller;

use Think\Page;

class AirdropController extends HomeController
{
    public function index()
    {
        $where['active'] = ['neq', 0];
        $Model = M('Dividend');
        $count = $Model->where($where)->count();
        $Page = new Page($count, 5);
        $show = $Page->show();

        $list = $Model->where($where)->order('sort asc,endtime desc,addtime desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $featured= [];
        $is_featureds = $Model->where(['is_featured' => 1])->order('addtime desc')->limit(3)->select();
		foreach($is_featureds as $is_featured){
			if(isset(C('coin')[$is_featured['coinname']]) && isset(C('coin')[$is_featured['coinjian']])){
			$index=$is_featured['id'];
			$featured[$index]['name'] = $is_featured['name'];

            $featured[$index]['coinname'] = C('coin')[$is_featured['coinname']]['name'];
            $featured[$index]['coinjian'] = C('coin')[$is_featured['coinjian']]['name'];
			$featured[$index]['image'] = $is_featured['image'];
			$featured[$index]['num'] = $is_featured['num'];
            $featured[$index]['content'] = mb_substr(clear_html($is_featured['content']), 0, 150, 'utf-8');


            $end_ms = $is_featured['endtime'] ;
            $begin_ms = $is_featured['addtime'];

            $featured[$index]['beginTime'] = date('Y-m-d H:i:s', $begin_ms);
            $featured[$index]['endTime'] = date('Y-m-d H:i:s', $end_ms);

            $featured[$index]['headtitle'] = 'Running';

            if ($begin_ms > time()) {
                $featured[$index]['headtitle'] = 'Upcoming';//Not started
            }

            if ($end_ms < time()) {
                $featured[$index]['headtitle'] = 'Ended';//Ended
            }
			}
		}
		
        $list_running = [];//Running
        $list_upcoming = [];//Upcoming
        $list_ended = []; //Ended


        foreach ($list as $k => $v) {
			if(isset(C('coin')[$v['coinname']])){
            $list[$k]['img'] = C('coin')[$v['coinname']]['img'];

            

            $list[$k]['coinname'] = C('coin')[$v['coinname']]['title'];
            $list[$k]['coinjian'] = C('coin')[$v['coinjian']]['title'];
            $list[$k]['num'] = format_num( $v['num']);
            $list[$k]['content'] = mb_substr(clear_html($v['content']), 0, 350, 'utf-8');
            $end_ms = $v['endtime'];
            $begin_ms = $v['addtime'];

            $list[$k]['beginTime'] = date('Y-m-d H:i:s', $begin_ms);
            $list[$k]['endTime'] = date('Y-m-d H:i:s', $end_ms);

            $list[$k]['headtitle'] = L('RUNNING');

            if ($begin_ms > time()) {
                $list[$k]['headtitle'] = L('UPCOMING');//upcoming
            }


            if ($end_ms < time()) {
                $list[$k]['headtitle'] = L('ENDED');//ended
            }

            switch ($list[$k]['headtitle']) {
                case L('UPCOMING'):
                    $list_upcoming[] = $list[$k];
                    break;
                case L('RUNNING'):
                    $list_running[] = $list[$k];
                    break;
                case L('ENDED'):
                    $list_ended[] = $list[$k];
                    break;
            }

		}
        }
		$page_title= L('Gift Rain');
        $this->assign('page_title', $page_title);
        $this->assign('featured', $featured);
        $this->assign('list_upcoming', $list_upcoming);
        $this->assign('list_running', $list_running);
        $this->assign('list_ended', $list_ended);
        $this->assign('page', $show);
        $this->display();
    }
}

