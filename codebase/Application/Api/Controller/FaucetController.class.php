<?php

namespace Api\Controller;

use Think\Page;

class FaucetController extends CommonController
{
    public function index()
    {
		$uid=$this->userid();
        
        $where['status'] = array('neq', 0);
        $Model = M('Faucet');
        $count = $Model->where($where)->count();
        $Page = new Page($count, 5);
        $show = $Page->show();
        $list = $Model->where($where)->order('tuijian asc,paixu desc,addtime desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();

        $recommended = $Model->where(array("tuijian" => 1))->order("addtime desc")->limit(1)->find();


        if ($recommended) {

            $recommended['coinname'] = C('coin')[$recommended['coinname']]['title'];
            $recommended['buycoin'] = C('coin')[$recommended['buycoin']]['title'];
            $recommended['bili'] = round(($recommended['deal'] / $recommended['num']) * 100, 2);
            $recommended['content'] = mb_substr(clear_html($recommended['content']), 0, 350, 'utf-8');


            $end_ms = strtotime($recommended['time']) + $recommended['tian'] * 3600 * 24;
            $begin_ms = strtotime($recommended['time']);

            $recommended['beginTime'] = date("Y-m-d H:i:s", $begin_ms);
            $recommended['endTime'] = date("Y-m-d H:i:s", $end_ms);

            $recommended['zhuangtai'] = "RUNNING";

            if ($begin_ms > time()) {
                $recommended['zhuangtai'] = "UPCOMING";//Not started
            }


            if ($recommended['num'] <= $recommended['deal']) {
                $recommended['zhuangtai'] = "ENDED";//Ended
            }


            if ($end_ms < time()) {
                $recommended['zhuangtai'] = "ENDED";//Ended
            }
			

            $recommended['rengou'] = "";
            if ($recommended['zhuangtai'] == "RUNNING") {
                $recommended['rengou'] = $recommended['id'];
            }
        }

        $list_jinxing = [];//Running
        $list_yure = [];//Upcoming
        $list_jieshu = []; //Ended

        foreach ($list as $k => $v) {

            $list[$k]['bili'] = bcmul(bcdiv($v['deal'] , $v['num'],8) , 100, 2);
            $list[$k]['endtime'] = date("Y-m-d H:i:s", strtotime($v['time']) + $v['tian'] * 3600 * 24);

            $list[$k]['coinname'] = C('coin')[$v['coinname']]['title'];
            $list[$k]['img'] = C('coin')[$v['coinname']]['img'];
            $list[$k]['content'] = mb_substr(clear_html($v['content']), 0, 350, 'utf-8');


            $end_ms = $v['endtime'];//strtotime($v['time']) + $v['tian'] * 24;
            $begin_ms = strtotime($v['time']);

            $list[$k]['beginTime'] = date("Y-m-d H:i:s", $begin_ms);
            $list[$k]['endTime'] = date("Y-m-d H:i:s", $end_ms);

            $list[$k]['zhuangtai'] = 'RUNNING';

            if ($begin_ms > time()) {
                $list[$k]['zhuangtai'] = 'UPCOMING';//upcoming
            }


            if ($list[$k]['num'] <= $list[$k]['deal']) {
                $list[$k]['zhuangtai'] = 'ENDED';//ended
            }

            if ($end_ms < time()) {
                $list[$k]['zhuangtai'] = 'ENDED';//ended
            }

            switch ($list[$k]['zhuangtai']) {
                case 'UPCOMING':
                    $list_yure[] = $list[$k];
                    break;
                case 'RUNNING':
                    $list_jinxing[] = $list[$k];
                    break;
                case 'ENDED':
                    $list_jieshu[] = $list[$k];
                    break;
            }


        }

        //var_dump($list_jinxing);exit;

        $data['recommended']=$recommended;
        $data['list_upcoming']=$list_yure;
        $data['list_running']=$list_jinxing;
        $data['list_ended']=$list_jieshu;
        $data['page']=$show;
        $this->ajaxShow($data);
    }
	public function recentUses($ls=10){
		
		$ajax='json';
		if (!check($ls, 'd') || $ls >100) {
            $this->error(L('Please enter proper limit of records!'));
        }
		$where['fid'] = array('egt', 0);
        $where['userid'] =  array('egt', 0);
        $FaucetLog = M('FaucetLog');
        $count = $FaucetLog->where($where)->count();
        $Page = new Page($count, $ls);
        $show = $Page->show();
        $list = $FaucetLog->where($where)->order('id desc')->field('id,fid,username,coinname,price,addtime')->limit($Page->firstRow . ',' . $Page->listRows)->select();
		$data['consumelog']=$list;
		$this->ajaxShow($data);
	}
    public function singleFaucetInfo($id = 1)
    {
        if (!check($id, 'd')) {
            $this->error(L('INCORRECT_REQ'));
        }

        $Faucet = M('Faucet')->where(array('id' => $id))->find();
		if(!is_array($Faucet)){
			$this->error('No Such Faucet Found');
		}
        $Faucet['bili'] = round(($Faucet['deal'] / $Faucet['num']) * 100, 2);

        $end_ms = strtotime($Faucet['time']) + $Faucet['tian'] * 3600 * 24;
        $begin_ms = strtotime($Faucet['time']);

        $Faucet['status'] = 1;

        if ($begin_ms > time()) {
            $Faucet['status'] = 2;//notStart
        }


        if ($Faucet['num'] == $Faucet['deal']) {
            $Faucet['status'] = 0;//AlreadyEnd
        }


        if ($end_ms < time()) {
            $Faucet['status'] = 0;//AlreadyEnd
        }


        $Faucet['endtime'] = date("Y-m-d H:i:s", strtotime($Faucet['time']) + $Faucet['tian'] * 3600 * 24);


        $user_coin = M('UserCoin')->where(array('userid' => $uid))->getField($Faucet['buycoin']);
        $data['user_coin']=$user_coin;

        if (!$Faucet) {
            $this->error(L('No such Faucet!'));
        }
        $Faucet['img'] =C('coin')[$Faucet['coinname']]['img'];
        $data['faucet']=$Faucet;
        $this->ajaxShow($data);
    }

    public function myLogs($ls = 15 )
    {
		$uid=$this->userid();
        if (!check($ls, 'd') || $ls >100) {
            $this->error(L('Incorrect limit of records'));
        }
        $where['status'] = array('egt', 0);
        $where['userid'] = $uid;
        
		  $FaucetLog = M('FaucetLog');
        $count = $FaucetLog->where($where)->count();
        $Page = new Page($count, $ls);
        $show = $Page->show();
        $list = $FaucetLog->where($where)->order('id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();

        foreach ($list as $k => $v) {
            $ci = $v['ci'] ?: 1;
            $list[$k]['shen'] = round((($ci - $v['unlock']) * $v['num']) / $ci, 6);
        }

        $data['list']=$list;
        $data['page']=$show;
        $this->ajaxShow($data);
    }


    /**
     * @param int $ls
     */
    public function claimLogs(int $ls = 15)
    {
		if (!check($ls, 'd') || $ls >100) {
            $this->error(L('Incorrect limit of records'));
        }	
        $where['status'] = array('egt', 0);
		$FaucetLog = M('FaucetLog');
        $count = $FaucetLog->where($where)->count();
        $Page = new Page($count, $ls);
        $show = $Page->show();
        $list = $FaucetLog->where($where)->order('id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();

        foreach ($list as $k => $v) {
            $ci = $v['ci'] ?: 1;
            $list[$k]['shen'] = round((($ci - $v['unlock']) * $v['num']) / $ci, 6);
        }

        $data['list']=$list;
        
        $this->ajaxShow($data);
    }
	private function ifFaucetClaimable($faucetinfo,$userid){
		$now=time();
		$resp['status']=0;
		$resp['message']=L('');
		if($faucetinfo['endtime'] < $now && $faucetinfo['endtime']!=NULL){
            $resp['status']=0;
		    $resp['message']=L('Faucet is ended already');
			return $resp;
		}
		$seconds=$faucetinfo['tian']*60*60;
		$where['fid'] = $faucetinfo['id'];
        $where['userid'] = $userid;
		$FaucetClaim = M('FaucetLog')->where($where)->order('id desc')->find();
		
		$lastclaimed=$FaucetClaim['addtime'];
		if($FaucetClaim==NULL){
		$resp['status']=1;
		$resp['message']=L('First Time claim');
			return $resp;
		}
		//Here we are checking if time has passed
		$wait_till=$lastclaimed+$seconds;
		
		if($wait_till<$now)
		{
				$resp['status']=1;
		$resp['message']=L($wait_till.'You can claim now'.$now);
        }
		else{
			$resp['status']=0;
			$wait_duration=date('m-d-Y H:i:s', $wait_till);
		$resp['message']=L('Please wait till ').$wait_duration;
        }
        return $resp;

        //return $FaucetClaim;
	}
	private function MaskUsername($username): string
    {
		
		$first=substr($username, 0,1);
		$last=substr($username, -1);
		return  $first.'****'.$last;
	}
    public function claim($id)
    {
		if (!check($id, 'd')) {
            $this->error(L('Incorrect Faucet Id'));
        }
        $uid=$this->userid();
		$num=1;
        $username =username($uid);
		$maskedusername=$this->MaskUsername($username);
        $Faucet = M('Faucet')->where(array('id' => $id))->find();
		
        if (!$Faucet) {
            $this->error(L('No Such Faucets!'));
        }
		$can_claim=$this->ifFaucetClaimable($Faucet,$uid);
		
		if($can_claim['status']==0){
		    $this->error($can_claim['message']);
		}
        if (time() < strtotime($Faucet['time'])) {
            $this->error(L('Faucet is yet to start!'));
        }

        if (!$Faucet['status']) {
            $this->error(L('This faucet is closed!'));
        }


        $end_ms = $Faucet['endtime'];//strtotime($Faucet['time']) + $Faucet['tian'] * 3600;
        /* 		$begin_ms = strtotime($Faucet['time']);
                if($begin_ms<time()){
                    $Faucet['status'] = 2;//notStart
                } */

        if ($end_ms < time()) {
			$end_time_show=date('m-d-Y H:i',$end_ms);
			
            $this->error(L('Faucet was expired on ').$end_time_show);
        }


        if (($Faucet['num'] - $Faucet['deal']) < $num) {
            $this->error(L('Faucet is exhausted!'));
        }

        $mum = round($Faucet['price'] * $num, 6);

        if (!$mum) {
            $this->error(L('Faucet Total Error'));
        }
		/*
        $buycoin = M('UserCoin')->where(array('userid' => $uid))->getField($Faucet['buycoin']);

        if ($buycoin < $mum) {
            $this->error(L('INSUFFICIENT') . C('coin')[$Faucet['buycoin']]['title']);
        }
		*/
        $faucetLog = M('FaucetLog')->where(array('userid' => $uid, 'coinname' => $Faucet['coinname']))->sum('num');

        if ($Faucet['ulimit'] < ($faucetLog + $num)) {
            $this->error(L('You have consumed more than limit from this faucet') . $Faucet['ulimit']);
        }

        if ($Faucet['price']) {
            $jd_num = round($Faucet['price'], 8);
        } else {
            $jd_num = $num;
        }

        if (!$jd_num) {
            $this->error(L('There is no coin quantity defined'));
        }

        $mo = M();
        
        $mo->startTrans();
        $rs = [];
        $finance = $mo->table('codono_finance')->where(array('userid' => $uid))->order('id desc')->find();
        $finance_num_user_coin = $mo->table('codono_user_coin')->where(array('userid' => $uid))->find();
        //$rs[] = $mo->table('codono_user_coin')->where(array('userid' => $uid))->setDec($Faucet['buycoin'], $mum); //We are giving it for free
        $rs[] = $finance_nameid = $mo->table('codono_faucet_log')->add(array('userid' => $uid,'username'=>$maskedusername, 'coinname' => $Faucet['coinname'], 'buycoin' => $Faucet['buycoin'], 'name' => $Faucet['name'], 'price' => $Faucet['price'], 'num' => $num,'fid'=>$id ,'mum' => $mum, 'ci' => $Faucet['ci'], 'jian' => $Faucet['jian'], 'unlock' => 1, 'addtime' => time(), 'endtime' => time(), 'status' => $Faucet['ci'] == 1 ? 1 : 0));
        $finance_mum_user_coin = $mo->table('codono_user_coin')->where(array('userid' => $uid))->find();
        $finance_hash = md5($uid . $finance_num_user_coin['usd'] . $finance_num_user_coin['usdd'] . $mum . $finance_mum_user_coin['usd'] . $finance_mum_user_coin['usdd'] . CODONOLIC . 'auth.codono.com');
        $rs[] = $mo->table('codono_finance')->add(array('userid' => $uid, 'coinname' => 'usd', 'num_a' => $finance_num_user_coin['usd'], 'num_b' => $finance_num_user_coin['usdd'], 'num' => $finance_num_user_coin['usd'] + $finance_num_user_coin['usdd'], 'fee' => $mum, 'type' => 2, 'name' => 'faucet', 'nameid' => $finance_nameid, 'remark' => 'FaucetClaim', 'mum_a' => $finance_mum_user_coin['usd'], 'mum_b' => $finance_mum_user_coin['usdd'], 'mum' => $finance_mum_user_coin['usd'] + $finance_mum_user_coin['usdd'], 'move' => $finance_hash, 'addtime' => time(), 'status' => $finance['mum'] != $finance_num_user_coin['usd'] + $finance_num_user_coin['usdd'] ? 0 : 1));
        $rs[] = $mo->table('codono_user_coin')->where(array('userid' => $uid))->setInc($Faucet['coinname'], $jd_num);
        $rs[] = $mo->table('codono_faucet')->where(array('id' => $id))->setInc('deal', $num);

        if ($Faucet['num'] <= $Faucet['deal']) {
            $rs[] = $mo->table('codono_faucet')->where(array('id' => $id))->setField('status', 0);
        }

        if (strict_check_arr($rs) && $mo->execute('commit') >= 0) {
            $this->success(L('Successfully Claimed!'));
        } else {
            $mo->rollback();
            $this->error('Could not Claimed!');
        }
    }

    public function unlock($id)
    {
        $uid=$this->userid();

        if (!check($id, 'd')) {
            $this->error(L('Please select thaw item!'));
        }

        $FaucetLog = M('FaucetLog')->where(array('id' => $id))->find();

        if (!$FaucetLog) {
            $this->error(L('INCORRECT_REQ'));
        }

        if ($FaucetLog['status']) {
            $this->error(L('The current thaw is complete!'));
        }

        if ($FaucetLog['ci'] <= $FaucetLog['unlock']) {
            $this->error(L('Unauthorized access!'));
        }

        $tm = $FaucetLog['endtime'] + (60 * 60 * $FaucetLog['jian']);

        if (time() < $tm) {
            $this->error('Thawing time has not arrived,please at<br>[' . addtime($tm) . ']<br>After the operation again');
        }

        if ($FaucetLog['userid'] != $uid) {
            $this->error(L('Unauthorized access'));
        }

        $jd_num = round($FaucetLog['num'] / $FaucetLog['ci'], 6);
        $mo = M();
        
        $mo->startTrans();
        $rs = [];
        $rs[] = $mo->table('codono_user_coin')->where(array('userid' => $uid))->setInc($FaucetLog['coinname'], $jd_num);
        $rs[] = $mo->table('codono_faucet_log')->where(array('id' => $FaucetLog['id']))->save(array('unlock' => $FaucetLog['unlock'] + 1, 'endtime' => time()));

        if ($FaucetLog['ci'] <= $FaucetLog['unlock'] + 1) {
            $rs[] = $mo->table('codono_faucet_log')->where(array('id' => $FaucetLog['id']))->save(array('status' => 1));
        }

        if (strict_check_arr($rs)) {
            $mo->commit();
            
            $this->success(L('Unlock success!'));
        } else {
            $mo->rollback();
            $this->error(L('Unlock Failed!'));
        }
    }

}