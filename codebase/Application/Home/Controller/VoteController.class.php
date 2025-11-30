<?php

namespace Home\Controller;

use Think\Page;

class VoteController extends HomeController
{
    const DOWN_VOTE_ALLOWED = 1;  // Maximum allowed investbox by a creator
	
    public function __construct()
    {
        if (VOTING_ALLOWED == 0) {
            die('Unauthorized!');
        }
        parent::__construct();

    }

    public function index()
    {
        $coin_list = M('VoteType')->select();
        $list = array();
        if (is_array($coin_list)) {
            foreach ($coin_list as $k => $v) {
                $vv = $v;
                $list[$vv['coinname']]['name'] = $vv['coinname'];
                $list[$vv['coinname']]['title'] = $vv['title'];
                $list[$vv['coinname']]['zhichi'] = M('Vote')->where(array('coinname' => $vv['coinname'], 'type' => 1))->count() + $vv['zhichi'];
                $list[$vv['coinname']]['fandui'] = M('Vote')->where(array('coinname' => $vv['coinname'], 'type' => 2))->count() + $vv['fandui'];
                $list[$vv['coinname']]['zongji'] = $list[$vv['coinname']]['zhichi'] - $list[$vv['coinname']]['fandui'];
                if ($list[$vv['coinname']]['zongji'] != 0) {
                    $list[$vv['coinname']]['bili'] = bcmul(bcdiv($list[$vv['coinname']]['zhichi'], $list[$vv['coinname']]['zongji'], 8), 100, 2);
                } else {
                    $list[$vv['coinname']]['bili'] = 0;
                }
                $list[$vv['coinname']]['votecoin'] = C('coin')[$vv['votecoin']]['title'];
                $list[$vv['coinname']]['assumnum'] = $vv['assumnum'];
                $list[$vv['coinname']]['img'] = $vv['img'];
                $list[$vv['coinname']]['id'] = $vv['id'];
            }


            $sort = array(
                'direction' => 'SORT_DESC',
                'field' => 'zongji',
            );
            $arrSort = array();
            foreach ($list as $uniqid => $row) {
                foreach ($row as $key => $value) {
                    $arrSort[$key][$uniqid] = $value;
                }
            }


            if ($sort['direction']) {
                array_multisort($arrSort[$sort['field']], constant($sort['direction']), $list);
            }


            $this->assign('list', $list);
        }
        $this->assign('is_down_vote_allowed', self::DOWN_VOTE_ALLOWED);
        $this->assign('prompt_text', D('Text')->get_content('game_vote'));
        $this->display();
    }
    public function competition()
    {
        $coin_list = M('VoteType')->select();
        $list = array();
        if (is_array($coin_list)) {
            foreach ($coin_list as $k => $v) {
                $vv = $v;
                $list[$vv['coinname']]['name'] = $vv['coinname'];
                $list[$vv['coinname']]['title'] = $vv['title'];
                $list[$vv['coinname']]['zhichi'] = M('Vote')->where(array('coinname' => $vv['coinname'], 'type' => 1))->count() + $vv['zhichi'];
                $list[$vv['coinname']]['fandui'] = M('Vote')->where(array('coinname' => $vv['coinname'], 'type' => 2))->count() + $vv['fandui'];
                $list[$vv['coinname']]['zongji'] = $list[$vv['coinname']]['zhichi'] - $list[$vv['coinname']]['fandui'];
                if ($list[$vv['coinname']]['zongji'] != 0) {
                    $list[$vv['coinname']]['bili'] = bcmul(bcdiv($list[$vv['coinname']]['zhichi'], $list[$vv['coinname']]['zongji'], 8), 100, 2);
                } else {
                    $list[$vv['coinname']]['bili'] = 0;
                }
                $list[$vv['coinname']]['votecoin'] = C('coin')[$vv['votecoin']]['title'];
                $list[$vv['coinname']]['assumnum'] = $vv['assumnum'];
                $list[$vv['coinname']]['img'] = $vv['img'];
                $list[$vv['coinname']]['id'] = $vv['id'];
            }


            $sort = array(
                'direction' => 'SORT_DESC',
                'field' => 'zongji',
            );
            $arrSort = array();
            foreach ($list as $uniqid => $row) {
                foreach ($row as $key => $value) {
                    $arrSort[$key][$uniqid] = $value;
                }
            }


            if ($sort['direction']) {
                array_multisort($arrSort[$sort['field']], constant($sort['direction']), $list);
            }


            $this->assign('list', $list);
        }
        $this->assign('is_down_vote_allowed', self::DOWN_VOTE_ALLOWED);
        $this->assign('prompt_text', D('Text')->get_content('game_vote'));
        $this->display();
    }

    public function up($type = 1, $id = 0)
    {
        $uid=userid();
        if (!$uid) {
            $this->error(L('PLEASE_LOGIN'));
        }
        if ($type == 2 && self::DOWN_VOTE_ALLOWED == 0) {
            $this->error(L('You can not down vote'));
        }
        if (($type != 1) && ($type != 2)) {
            $this->error(L('INCORRECT_REQ'));
        }

        if (!is_array(D('Coin')->get_all_name_list())) {
            $this->error('Parameter error2!');
        }

        $curVote = M('VoteType')->where(array('id' => $id))->find();

        if ($curVote) {
            $curUserB =M('UserCoin')->where(array('userid' => $uid))->getField($curVote['votecoin']);

            if (floatval($curUserB) < floatval($curVote['assumnum'])) {
                $this->error('Insufficient balance for voting,needs ' . $curVote['assumnum'] . ' ' . $curVote['votecoin']);
            }

        } else {
            $this->error(L('Voting type does not exist'));
        }
        $mo=M();
        $rs=[];
        $rs[]=$mo->table('codono_vote')->add(array('userid' => $uid, 'coinname' => $curVote['coinname'], 'title' => $curVote['title'], 'type' => $type, 'addtime' => time(), 'status' => 1));
        $rs[]=$mo->table('codono_user_coin')->where(array('userid' => $uid))->setDec($curVote['votecoin'], $curVote['assumnum']);
        
        if (strict_check_arr($rs)) {
            $this->success(L('Voting success!'));
        } else {
            $this->error(L('Voting failed!'));
        }
    }

    public function log()
    {
        if (!userid()) {
            redirect(U('Login/login'));
        }
        $ls=I('get.ls',15,'intval');
        $where['status'] = array('egt', 0);
        $where['userid'] = userid();
        $Vote = M('Vote');
        $count = $Vote->where($where)->count();
        $Page = new Page($count, $ls);
        $show = $Page->show();
        $list = $Vote->where($where)->order('id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();

        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->display();
    }

}