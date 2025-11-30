<?php
/*
Table:codono_staking
Status >>> 0=submitted,1=approved,2=reject,3=completed,4=upcoming
Period >>> 1=daily,7=weekly,30=monthly
Action >>> $json='{"coin":{"name":"btc","value":"1.5"},"market":{"name":"btc_usd","buy":"5","sell":"6"}}'; it is defined function is 
*/

namespace Api\Controller;

use Think\Page;

class StakingController extends CommonController
{
    // Enable disable staking controller
    const ENABLE_STAKING_CONTROLLER = 1;
    const CREATOR_MAX_ALLOWED = 3;  // Maximum allowed  to staking  by a creator
    const CONSUME_STAKING_BALANCE = USE_STAKING_BALANCE?:0;
    const DICE_ENABLE = 1;
    const DICE_COIN = 'ltc';  // Define a coin to be rolled
    const ALLOWED_BIDS = ['0.01', '0.2', '0.1', '0.2', '0.5', '1'];  // Define a coin to be rolled

    const DICE_PROFIT = '100';  // PERCENTAGE TO BE AWARDED IF USER WINS IN DICE ROLL
    const PROBABILITY = '35';  // winning probability index Do not keep below 10
	
	public function _initialize()
    {
		$data=[];
        if (INVEST_ALLOWED == 0) {
            die('Unauthorized!');
        }
        //Exit on Stakings are disable
        if (self::ENABLE_STAKING_CONTROLLER == 0) {
            $this->error('error', 'Oops, Currently Stakings are disabled!');
            exit;
        }
        parent::_initialize();
        $Market = C('market');
        $market_list = [];
        foreach ($Market as $k => $v) {
            $v['xnb'] = explode('_', $v['name'])[0];
            $v['rmb'] = explode('_', $v['name'])[1];
            $market_list[$v['name']] = $v;
        }

        $data['market_list']= $market_list;

        $coin_list = D('Coin')->get_all_name_list();
        $data['DICE_ENABLE']= self::DICE_ENABLE;
        $data['coin_list']= $coin_list;
        $data['basecoin']= self::DICE_COIN;
        $data['allowed_bids']= self::ALLOWED_BIDS;
    }

    /**************************INVEST CODE START **************************/
    public function index()
    {
		$uid=$this->userid();
		$data=[];
        $Model = M('Staking');
        $where = [];
        $where['status'] = ['eq', 1]; //enable to show only active staking boxes
        $list = (APP_DEBUG ? null : S('staking_list'));
        if (!$list) {
            $count = $Model->where($where)->count();
            $Page = new Page($count, 10);
            $show = $Page->show();
            $lists = $Model->where($where)->order('id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();
        }
        foreach ($lists as $list) {
            $list['period'] = json_decode($list['period'], true);
            $list['percentage'] = json_decode($list['percentage'], true);
            $list['img'] = C('Coin')[$list['coinname']]['img'];
            $list['balance'] = $this->coinBalance($list['coinname'],$uid);

            $all_list[] = $list;
        }


        $data['list']= $all_list;
        $data['page']= $show;
        $this->ajaxShow($data);
    }

    private function coinBalance($coinname,$uid)
    {
        $coinname = strtolower($coinname);

        if ($coinname != C('coin')[$coinname]['name']) {
            return 0;
        }

        if (self::CONSUME_STAKING_BALANCE == 1) {
            $mo=M('UserAssets');
            $info = $mo->where(['uid' => $uid, 'account'=>4,'coin' => $coinname])->find();

            return $info['balance'] ?: 0;

        } else {
            $Model = M('UserCoin');
            return $Model->where(['userid' => $uid])->getField($coinname);
        }

    }

    public function logs()
    {
		$data=[];
        $userid = $this->userid();
        if (!$userid) {
            $this->error(L('PLEASE_LOGIN'));
        }
        $Model = M('StakingLog');
        $where['userid'] = ['eq', $userid];

        $count = $Model->where($where)->count();
        $Page = new Page($count, 10);
        $show = $Page->show();
        $lists = $Model->where($where)->order('id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $all_info = [];
        if (!empty($lists)) {

            foreach ($lists as $list) {

                $staking_info = $this->stakinginfo($list['staking_id']);
                $list['coinname'] = $staking_info['coinname'];
                $list['percentage'] = $list['percentage'];
                $list['minvest'] = $staking_info['minvest'];
                $list['maxvest'] = $staking_info['maxvest'];

                $list['allow_withdrawal'] = $staking_info['allow_withdrawal'];
                $list['penalty_coin'] = $staking_info['penalty_coin'];
                $list['penalty_amount'] = $staking_info['penalty_amount'];
                $list['boxstatus'] = $staking_info['status'];
                $all_info[] = $list;
            }
        }


        $data['list']= $all_info;
        $data['page']= $show;
        $this->ajaxShow($data);
    }

    private function stakinginfo($id)
    {
        $Model = M('Staking');
        $where['id'] = $id;
        return $Model->where($where)->find();
    }

    public function withdraw()
    {
        $userid = $this->userid();
        if (!$userid) {
            $this->error(L('PLEASE_LOGIN'));
        }
        $Model = M('StakingLog');
        $where['docid'] = $docid = I('get.docid', '', 'text');
        $where['userid'] = $userid;
        $staking_logs = $Model->where($where)->find();

        if (!is_array($staking_logs) || $staking_logs['docid'] != $docid || $staking_logs['userid'] != $userid || $staking_logs['status'] != 1 || format_num($staking_logs['credited'], 8) > 0) {
            $this->error('No such record found');

        }

        $staking_info = $this->stakinginfo($staking_logs['staking_id']);

        if ($staking_info['allow_withdrawal'] != 1) {

            $this->error('This record will be processed upon maturity!');
        }
        if ($staking_logs['id'] > 0) {
            $withdraw = D('Staking')->withdraw($staking_logs['id']);
            $this->stakingshow($withdraw);
        } else {
            $this->error('No such record');
        }

    }

    private function periodPercent($arr)
    {
        $info = [];
        $percentages = json_decode($arr, true);

        if (json_last_error() == JSON_ERROR_NONE) {
            // Construct a string to display percentages

            foreach ($percentages as $period => $percentage) {

                $info[$period] = $percentage;
            }

        }
        return $info;

    }

    public function createStaking()
    {
        $id = I('post.id', 0, 'intval');
        $amount = I('post.amount', 0.00, 'float');
        $period = I('post.period', 0, 'intval');

        $amount = (double)$amount;

        $userid = $uid=$this->userid();
        if (!$userid) {
            $this->error(L('PLEASE_LOGIN'));
        }
        if (!check($id, 'd') || $id <= 0) {
            $this->error('Incorrect Staking');
        }
        if (!check($amount, 'double') || $amount <= 0) {
            $this->error('Incorrect amount:' . $amount);
        }
        if (!check($period, 'd') || $period <= 0) {
            $this->error('Incorrect Period');
        }

        //@todo check period
        $Model = M('Staking');
        $where['id'] = $id;
        $stakings = $Model->where($where)->find();

        $in_period = $this->periodPercent($stakings['percentage']);

        if (!array_key_exists($period, $in_period)) {
            $this->error('Invalid Lock Period');
        }
        $percentage = $in_period[$period];
        if ($percentage <= 0) {
            $this->error('Invalid percentage for staking');
        }
        if ($amount > $stakings['maxvest'] || $amount < $stakings['minvest']) {
            $this->error('Keep amount between ' . ($stakings['minvest'] * 1) . ' and ' . ($stakings['maxvest'] * 1));
        }
        $coin_check = strtolower($stakings['coinname']);
        if (self::CONSUME_STAKING_BALANCE == 1) {
            $current_balance = $this->coinBalance($coin_check,$userid);
            $freeze_balance = 0;// find
            $require_wallet = 'staking';
        } else {
            $user_coin = M('UserCoin')->where(['userid' => $userid])->find();
            $current_balance = $user_coin[$coin_check];
            $freeze_balance = $user_coin[$coin_check . 'd'];
            $require_wallet = 'spot';
        }


        if ($current_balance < $amount) {
            //$this->error(L('INSUFFICIENT') . C('coin')[$stakings['coinname']]['title']);
            $this->error(" $amount $coin_check required in $require_wallet wallet ,You have " . NumToStr($current_balance));
        }
        $result = $this->ifactionFulfill($stakings['action'], $userid);
        if ($result['status'] == 0) {
            $this->error($result['message']);
        }
        //Check if there is active staking by user

        $where['staking_id'] = $id;
        $where['userid'] = $userid;
        $where['status'] = 1;

        $staking_logs = M('StakingLog')->where($where)->find();
        if ($staking_logs['userid'] == $userid) {
            $this->error('You have already an active staking with docid:' . $staking_logs['docid']);
        }
        //Check active staking ends

        //Staking area
        $coinname = strval($coin_check);
        $coinnamed = $coinname . 'd';
        $userid = (integer)$userid;

        $mo = M();

        $mo->startTrans();
        try {
            $rs = [];

            $docid = $id . 'IB' . $userid . tradeno();

            $begintime = time();
            $conv_period = '+' . $period . ' days';

            $endtime = strtotime($conv_period, $begintime);
            /*Calculating Maturity */
            $daily_interest_per = bcdiv($percentage, 365, 8);
            $total_percent_receivable = bcmul($daily_interest_per, $period, 8);
            $divisible = bcdiv($total_percent_receivable, 100, 8);
            $total_percent = bcadd(1, $divisible, 8);

            $maturity = bcmul($amount, $total_percent, 8);
            /*Calculating Maturity */


            $mum_a = bcsub($current_balance, $amount, 8);
            $mum_b = $freeze_balance;

            $num = bcadd($current_balance, $freeze_balance, 8);
            $mum = bcadd($mum_a, $freeze_balance, 8);

            $insert_array = ['staking_id' => $id, 'userid' => $userid, 'docid' => $docid, 'period' => $period, 'percentage' => $percentage, 'amount' => $amount, 'begintime' => $begintime, 'endtime' => $endtime, 'status' => 1, 'maturity' => $maturity];
            $rs[] = $staking_insert = $mo->table('codono_staking_log')->add($insert_array);
            //$sql[1] = $mo->getLastSql();

            if (self::CONSUME_STAKING_BALANCE == 1) {
                $condition = ['uid' => $userid, 'coin' => $coinname,'account'=>4];
                $rs[] = $mo->table('codono_user_assets')->where($condition)->setDec('balance', $amount);
            }else {
                $rs[] = $mo->table('codono_user_coin')->where(['userid' => $userid])->setDec($coinname, $amount);
            }
            //$sql[2] = $mo->getLastSql();
            $rs[] = $mo->table('codono_finance')->add(['userid' => $userid, 'coinname' => $coinname, 'num_a' => $current_balance, 'num_b' => $freeze_balance, 'num' => $num, 'fee' => $amount, 'type' => 2, 'name' => 'staking', 'nameid' => $staking_insert, 'remark' => 'create_staking:' . $staking_insert, 'move' => $staking_insert, 'addtime' => time(), 'status' => 1, 'mum' => $mum, 'mum_a' => $mum_a, 'mum_b' => $mum_b]);
            //$sql[3] = $mo->getLastSql();
        } catch (\Exception $e) {
            // Rollback transaction in case of error
            clog('createStaking', $e->getMessage());
            $mo->rollback();
            $this->error('Failed to stake ' . $e->getMessage());
        }
        if (strict_check_arr($rs)) {
            $mo->commit();
            $this->success(('Staking successful Docid is !' . $docid));

        } else {
            $mo->rollback();
            $this->error(L('We could not add your Staking!'));
        }

    }


    public function stakingshow($rs = [])
    {
        if ($rs[0]) {
            $this->success($rs[1]);
        } else {
            $this->error($rs[1]);
        }
    }

    private function ifactionFulfill($action_json, $userid)
    {
        $actionchecker = json_decode($action_json);
        $res['status'] = 1;
        if ((integer)$actionchecker->noaction == 1) {
            $res['status'] = 1;
            $res['message'] = 'There are no action required';
        }

        //Balance Check
        if ((integer)$actionchecker->coin->value > 0) {
            $user_coin = M('UserCoin')->where(['userid' => $userid])->getField(strval($actionchecker->coin->name));
            if ($user_coin > $actionchecker->coin->value) {
                $res['status'] = 1;
                $res['message'] = 'You have sufficient balance, Required is ' . $actionchecker->coin->value . $actionchecker->coin->name;
            } else {
                $res['status'] = 0;
                $res['message'] = 'You have dont sufficient balance, Required is ' . $actionchecker->coin->value . $actionchecker->coin->name;
                return $res;
            }
        }
        $market = $actionchecker->market->name ?: 'NONEMARKET';
        $buy_trade_count = M('TradeLog')->where(['userid' => $userid, 'status' => 1, 'type' => 1, 'market' => $market])->count();


        //Buy Trades Check
        if ((integer)$actionchecker->market->buy > $buy_trade_count) {
            $market = strval($actionchecker->market->name);

            if ($buy_trade_count > $actionchecker->market->buy) {
                $res['status'] = 1;
                $res['message'] = 'You have done sufficient buy trades, Required is ' . $actionchecker->market->buy . $actionchecker->market->name;
            } else {
                $res['status'] = 0;
                $res['message'] = "You dont not sufficient buy trades [$buy_trade_count], Required is " . $actionchecker->market->buy . ' ' . $actionchecker->market->name;
                return $res;
            }
        }
        $sell_trade_count = M('TradeLog')->where(['userid' => $userid, 'status' => 1, 'type' => 2, 'market' => $market])->count();
        //Sell Trades Check
        if ((integer)$actionchecker->market->sell > $sell_trade_count) {
            $market = strval($actionchecker->market->name);

            if ($sell_trade_count > $actionchecker->market->sell) {
                $res['status'] = 1;
                $res['message'] = 'You have done sufficient sell trades, Required is ' . $actionchecker->market->sell . $actionchecker->market->name;
            } else {
                $res['status'] = 0;
                $res['message'] = "You dont not sufficient sell trades [$sell_trade_count], Required is " . $actionchecker->market->sell . ' ' . $actionchecker->market->name;
                return $res;
            }
        }
        return $res;
    }
    /**************************INVEST CODE ENDS **************************/
    /**************************DICE CODE STARTS **************************/
    public function dicerolls()
    {	
	$uid=$this->userid();
		$data=[];
        if (self::DICE_ENABLE == 0) {
            die('ACCESS DENIED');
        }
        if ($uid) {
            $where = ['userid' => $uid];
            $Model = M('Dice');
            $count = $Model->where($where)->count();
            $Page = new Page($count, 10);
            $show = $Page->show();
            $list = $Model->where($where)->order('id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();
        } else {
            $list = [];
        }
		$dice_coin=strtolower(self::DICE_COIN);
        $user_bal =M('UserCoin')->where(array('userid' => $uid))->getField($dice_coin);
        $data['user_bal'] =$user_bal;
        $data['list']= $list;
        $data['page']= $show;
        $this->ajaxShow($data);
    }

    public function highlow($call, $amount, $time)
    {
		$userid=$uid = $this->userid();
        $result = 0;
        if (self::DICE_ENABLE == 0) {
            die('ACCESS DENIED');
        }
        $coinname = self::DICE_COIN;
        $coinnamed = $coinname . 'd';
        
        if (!check($call, 'a')) {
            $this->error('Incorrect call, Choose either High or Low');
        }
        if (!check($time, 'd')) {
            $this->error('Please refresh the page or try again later');
        }

        if (!in_array($amount, self::ALLOWED_BIDS)) {
            $this->error($amount . ' is not an allowed amount');
        }
        if ($call != 'low') {
            $call = 'high';
        }
        $this->ifAllowedToDice($coinname, $amount, $time,$uid);

        $number = $this->probability($call);

        if ($call == 'low' && $number < 48) {
            $result = 1;
        }
        if ($call == 'high' && $number > 52) {
            $result = 1;
        }
        if ($call == 'high' && $number < 53) {
            $result = 2;
        }
        if ($call == 'low' && $number > 47) {
            $result = 2;
        }
        $winamount = 0;
        if ($result == 1) {
            $winamount = $amount * (1 + (self::DICE_PROFIT / 100));
        }

        $mo = M();
        $mo->startTrans();
        $rs = [];
        
        $query = "SELECT `$coinname`,`$coinnamed` FROM `codono_user_coin` WHERE `userid` = $userid";
        $res_bal = $mo->query($query);
        $user_coin_bal = $res_bal[0];


        $insert_array = ['call' => $call, 'userid' => $uid, 'number' => $number, 'result' => $result, 'amount' => $amount, 'addtime' => $time, 'coinname' => $coinname, 'winamount' => $winamount];

        if ($result == 1) {
            $mum_a = bcadd($user_coin_bal[$coinname], $amount, 8);
        } else {
            $mum_a = bcsub($user_coin_bal[$coinname], $amount, 8);
        }

        $mum_b = $user_coin_bal[$coinnamed];
        $num = bcadd($user_coin_bal[$coinname], $user_coin_bal[$coinnamed], 8);
        $mum = bcadd($mum_a, $user_coin_bal[$coinnamed], 8);
        $rs[] = $dice_roll = M('Dice')->add($insert_array);

        if ($result == 1) {
            $rs[] = $mo->table('codono_user_coin')->where(['userid' => $uid])->setInc($coinname, $amount);
        } else {
            $rs[] = $mo->table('codono_user_coin')->where(['userid' => $uid])->setDec($coinname, $amount);
        }
        $finance_update_array = ['userid' => $uid, 'coinname' => $coinname, 'num_a' => $user_coin_bal[$coinname], 'num_b' => $user_coin_bal[$coinnamed], 'num' => $num, 'fee' => $amount, 'type' => $result, 'name' => 'DiceRoll', 'nameid' => $dice_roll, 'remark' => 'dice_roll:' . $dice_roll, 'move' => $dice_roll, 'addtime' => time(), 'status' => 1, 'mum' => $mum, 'mum_a' => $mum_a, 'mum_b' => $mum_b];
        $rs[] = $mo->table('codono_finance')->add($finance_update_array);

        if (check_arr($rs)) {
            $mo->commit();

            if ($result == 1) {
                $this->success("Call was $call , So You win:" . $number);
            } else {
                $this->error("Call was $call , So You loose:" . $number);
            }

        } else {
            $mo->rollback();
            $this->error(L('Sorry we could not roll the dice!'));
        }
    }

    private function ifAllowedToDice($coinname, $amount, $time,$uid)
    {
        if (self::DICE_ENABLE == 0) {
            die('ACCESS DENIED');
        }
        $balance_coin = M('UserCoin')->where(array('userid' => $uid))->getField($coinname);
        if ($balance_coin < $amount) {
            $this->error('You have low balance!');
        }
        $time = M('Dice')->where(['userid' => $uid, 'addtime' => $time])->getField('addtime');
        if ($time > 0) {
            $this->error('Too fast, Refresh the page!' . $time);
        }

    }

    private function probability($call): int
    {

        $probability = self::PROBABILITY;
        $num = $this->calc();
        if ($call == 'high') {
            $num = $num - (50 - $probability);
        } else {
            $num = $num + (50 - $probability);
        }
        return $this->trick($num);
    }

    private function trick($num)
    {

        if ($num > 50)
            $number = mt_rand(51, 100);
        else
            $number = mt_rand(1, 50);
        return $number;
    }

    private function calc()
    {
        $number = mt_rand(1, 100);
        if ($number > 47 && $number < 53) {
            $this->calc();
        }
        return $number;
    }


    //Display functions
    public function bet()
    {
		$data=[];
        if (self::DICE_ENABLE == 0) {
            die('ACCESS DENIED');
        }
        $this->ajaxShow($data);
    }

    public function history()
    {
        if (self::DICE_ENABLE == 0) {
            die('ACCESS DENIED');
        }
		$data=[];
        $this->ajaxShow($data);
    }
}