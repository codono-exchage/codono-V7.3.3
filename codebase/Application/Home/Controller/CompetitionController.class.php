<?php

namespace Home\Controller;

use Think\Page;

class CompetitionController extends HomeController
{
    public const DOWN_VOTE_ALLOWED = 1;  // Maximum allowed investbox by a creator
	
    public function __construct()
    {
        if (VOTING_ALLOWED == 0) {
            die('Unauthorized!');
        }
        parent::__construct();

    }

    public function index()
    {
        $coin_list = M('CompetitionType')->select();
        $list = $featured=$previous=[];
        if (is_array($coin_list)) {
            foreach ($coin_list as $k => $v) {
                $vv = $v;
				if($vv['end_date']<time()){
					$previous[$vv['id']]=$vv;
					continue;
				}
				if($vv['featured']==1){
					$featured=$vv;					
					continue;
				}
                $list[$vv['id']]['name'] = $vv['coin_1'] .' vs '. $vv['coin_2'];
                $list[$vv['id']]['coin_1'] = $vv['coin_1'];
				$list[$vv['id']]['coin_2'] = $vv['coin_2'];
                $list[$vv['id']]['votes_1'] = M('Vote')->where(['coinname' => $vv['coinname'], 'type' => 1])->count() + $vv['votes_1'];
                $list[$vv['id']]['votes_2'] = M('Vote')->where(['coinname' => $vv['coinname'], 'type' => 2])->count() + $vv['votes_2'];
                $list[$vv['id']]['total'] = $list[$vv['id']]['votes_1'] - $list[$vv['id']]['votes_2'];
                $list[$vv['id']]['votecoin'] = C('coin')[$vv['votecoin']]['title'];
                $list[$vv['id']]['fees'] = $vv['fees'];
                $list[$vv['id']]['img_1'] = $vv['img_1'];
				$list[$vv['id']]['img_2'] = $vv['img_2'];
                $list[$vv['id']]['id'] = $vv['id'];
				$list[$vv['id']]['end_date'] = $vv['end_date'];
				$list[$vv['id']]['start_date'] = $vv['start_date'];
				
            }


            $sort = [
                'direction' => 'SORT_DESC',
                'field' => 'total',
            ];
            $arrSort = [];
            foreach ($list as $uniqid => $row) {
                foreach ($row as $key => $value) {
                    $arrSort[$key][$uniqid] = $value;
                }
            }


            if ($sort['direction'] && is_array($arrSort[$sort['field']])) {
                array_multisort($arrSort[$sort['field']], constant($sort['direction']), $list);
            }

			$this->assign('featured', $featured);
			$this->assign('previous', $previous);
            $this->assign('list', $list);
        }
        $this->assign('is_down_vote_allowed', self::DOWN_VOTE_ALLOWED);
        $this->assign('prompt_text', D('Text')->get_content('game_vote'));
        $this->display();
    }
	public function view()
    {
		$id=I('get.id',7,'int');
        $competition_info =  M('CompetitionType')->where(['id' => $id])->find();
		
		if(empty($competition_info)){
		
		redirect(U('Competition/index'));
		}
        $list = [];
        if (is_array($competition_info)) {
            
                $vv = $competition_info;
                
                $list['coin_1'] = $vv['coin_1'];
				$list['coin_2'] = $vv['coin_2'];
                $list['votes_1'] = M('Competition')->where(['type_id' => $vv['id'], 'type' => 1])->count() + $vv['votes_1'];
                $list['votes_2'] = M('Competition')->where(['type_id' => $vv['id'], 'type' => 2])->count() + $vv['votes_2'];
                $list['total'] = $list['votes_1'] - $list['votes_2'];
                if ($list['total'] != 0) {
                    $list['bili'] = bcmul(bcdiv($list['votes_1'], $list['total'], 8), 100, 2);
                } else {
                    $list['bili'] = 0;
                }
                $list['votecoin'] = C('coin')[$vv['votecoin']]['title'];
                $list['fees'] = $vv['fees'];
                $list['img_1'] = SITE_URL.DS.$vv['img_1'];
				$list['img_2'] = SITE_URL.DS.$vv['img_2'];
                $list['id'] = $vv['id'];
				$list['end_date'] = $vv['end_date'];
				$list['start_date'] = $vv['start_date'];
				$list['title'] = $vv['coin_1'].' vs '. $vv['coin_2'];
				$list['fire']=$list['votes_1']>$list['votes_2']?1:2;
				
            $this->assign('list', $list);
        }
		$this->assign('kyced',kyced());
        $this->assign('is_down_vote_allowed', self::DOWN_VOTE_ALLOWED);
        $this->assign('prompt_text', D('Text')->get_content('game_vote'));
        $this->display();
    }
    public function up()
    {
		$id=I('post.id',0,'int');
		$type=I('post.type',1,'int');
        if (!userid()) {
            $this->error(L('PLEASE_LOGIN'));
        }

        if (($type != 1) && ($type != 2)) {
            $this->error(L('INCORRECT_REQ'));
        }

        if (!is_array(D('Coin')->get_all_name_list())) {
            $this->error('Parameter error2!');
        }

        $curVote = M('CompetitionType')->where(['id' => $id])->find();
		if($curVote['end_date']<time()){
			$this->error('Competition already ended!');
		}
		if($type==1){
			$voted_favor=$curVote['coin_1'];
			$voted_against=$curVote['coin_2'];
		}else{
			$voted_favor=$curVote['coin_2'];
			$voted_against=$curVote['coin_1'];
		}
        if ($curVote) {
            $curUserB = M('UserCoin')->where(['userid' => userid()])->getField($curVote['votecoin']);

            if (floatval($curUserB) < floatval($curVote['fees'])) {
                $this->error('Insufficient balance for voting,needs ' . $curVote['fees'] . ' ' . $curVote['votecoin']);
            }

        } else {
            $this->error(L('Voting type does not exist'));
        }
        $mo=M();
        if ($mo->table('codono_competition')->add(['userid' => userid(),'voted_favor'=>$voted_favor,'voted_against'=>$voted_against, 'type_id' => $curVote['id'],  'type' => $type, 'addtime' => time(),'fees'=>$curVote['fees'],'votecoin'=>$curVote['votecoin'], 'status' => 1])) {
            $mo->table('codono_user_coin')->where(['userid' => userid()])->setDec($curVote['votecoin'], $curVote['fees']);
            $this->success(L('Voting success!'));
        } else {
            $this->error(L('Voting failed!'));
        }
    }

    public function log($ls = 15)
    {
        if (!userid()) {
            redirect(U('Login/login'));
        }

        $where['status'] = ['egt', 0];
        $where['userid'] = userid();
        $Competition = M('Competition');
        $count = $Competition->where($where)->count();
        $Page = new Page($count, $ls);
        $show = $Page->show();
        $list = $Competition->where($where)->order('id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();

        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->display();
    }
}