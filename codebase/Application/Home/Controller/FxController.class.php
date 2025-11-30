<?php

namespace Home\Controller;

use Think\Page;

class FxController extends HomeController
{
    //Coins in which users can buy or sell they need to be listed on your exchange as well as huobi
    const DEFAULT_COIN_TRADE = array('symbol' => 'USD', 'image' => '/Upload/coin/USD.png');
    const DEFAULT_COIN_BASE = array('symbol' => 'zar', 'image' => '/Upload/coin/ZAR.png');
    const BASE_COINS = array('usd', 'zar', 'usdt', 'ugx');  // Payment method/ Frequency
    const BASE_COINS_LIMIT = array('usd' => array('min' => 10, 'max' => 10000), 'zar' => array('min' => 100, 'max' => 100000), 'ugx' => array('min' => 1000, 'max' => 1000000), 'usdt' => array('min' => 1000, 'max' => 1000000));
    const QUOTE_LIFE = 200;
    const ROUNDABOUT = 0;

    public function _initialize()
    {
        if (FX_ALLOWED == 0) {
            $this->assign('type', 'Oops');
            $this->assign('error', 'Oops, Currently FX is disabled!');
            //$this->display('Content/error');
            $this->error('FX is currently disabled');
            exit();
        }

        parent::_initialize();


    }

    public function index()
    {
        $where['status'] = array('neq', 0);
        $Model = M('Fx');
        $count = $Model->where($where)->count();
        $Page = new Page($count, 120);
        $show = $Page->show();

        $list = $Model->where($where)->order('sort asc,addtime desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();

        $fx_list = array();
        if (is_array($list)) {
            foreach ($list as $k => $v) {
                //if($v['img']==null ||$v['img']==''){
                if (!isset($v['img'])) {
                    $v['img'] = 'default.png';
                }
                $img = c('coin')[$v['coinname']]['img'];
                $fx_list[$v['name']] = array('symbol' => $v['coinname'], 'image' => '/Upload/coin/' . $img, 'min' => $v['min'], 'max' => $v['max']);

            }

            unset($k);
            unset($v);
        } else {
            $fx_list = null;
        }
        $base_coins = array();
        foreach (self::BASE_COINS as $base) {
            $base_coins[$base]['image'] = '/Upload/coin/' . c('coin')[$base]['img'];
            $base_coins[$base]['symbol'] = $base;
            $base_coins[$base]['min'] = 1;
            if ($base == 'usdt' || $base == 'usd') {
                $max_allowed_base = 1000000;
            } else {
                $max_allowed_base = 5000000;
            }
            $base_coins[$base]['max'] = $max_allowed_base;
        }

        $UserBank = M('FxBank')->where(array('userid' => userid(), 'status' => 1))->order('id desc')->select();
        $this->assign('UserBank', $UserBank);
        $this->assign('base_coins', $base_coins);
        $this->assign('default_coin_base', self::DEFAULT_COIN_BASE);
        $this->assign('default_coin_trade', self::DEFAULT_COIN_TRADE);
        $this->assign('trade_coins', $fx_list);
        $this->display();
    }

    public function getquote()
    {
        $uid = userid();

        $trade_coin = strtolower(I('trade_coin/s'));
        $base_coin = strtolower(I('base_coin/s'));
        $input1 = I('request.input1', 0.00, 'float');
        $input2 = I('request.input2', 0.00, 'float');
        $tradetype = I('request.tradetype', 'buy', 'string');
        $bankid = I('bankid/d');

        if (!$uid) {
            $this->error(L('PLEASE_LOGIN'));
        }
        $bcoins = self::BASE_COINS;

        if (!in_array($base_coin, $bcoins)) {
            $this->error(L('Invalid currency1') . $base_coin);
        }
        if (!check($trade_coin, 'n')) {
            $this->error(L('Invalid currency2!') . $trade_coin);
        }

        $tcoin = M('Fx')->where(array('status' => 1, 'coinname' => $trade_coin))->getField('coinname');
        $buy_commission = M('Fx')->where(array('status' => 1, 'coinname' => $trade_coin))->getField('buy_commission');
        $sell_commission = M('Fx')->where(array('status' => 1, 'coinname' => $trade_coin))->getField('sell_commission');
        if ($tcoin != $trade_coin) {
            $this->error(L('Invalid currency3!'));
        }

        if (!in_array($base_coin, $bcoins)) {
            $this->error(L('Invalid currency'));
        }


        $expire = self::QUOTE_LIFE - 3;
        if ($input2 == 0) {
            $inverse = false;
            $deal_coin = $trade_coin;
            $quantity = $input1;
        } else {
            $inverse = true;
            $deal_coin = $base_coin;
            $quantity = $input2;
        }
        if (!check($quantity, 'double')) {
            $this->error("Incorrect Quantity provided !" . $quantity);
        }

        if ($inverse) {
            $coin_limits = self::BASE_COINS_LIMIT[$base_coin];
            if ($quantity < $coin_limits['min']) {
                $this->error('Minimum order:' . $coin_limits['min'] . $base_coin);
            }
            if ($quantity > $coin_limits['max']) {
                $this->error('Maximum order:' . $coin_limits['max'] . $base_coin);
            }
        }

        $bankinfo = $this->findBank($bankid, $uid);
        if ($bankinfo['id'] != $bankid) {
            $this->error("Please select bank account or add one!");
        }

        $symbol = strtolower($trade_coin . $base_coin);

        $avg_price = $this->getConversion($trade_coin, $base_coin, $tradetype, 1);
		
        if (!isset($avg_price) || $avg_price == null || $avg_price <= 0) {
			clog('fx',array($trade_coin, $base_coin, $tradetype, 1));
            $this->error("Please try again in sometime !");
        }

        if ($tradetype == 'buy') {
            $comm = bcdiv($buy_commission, 100, 8);
            $mux = bcadd(1, $comm, 8);
            $final_price = bcmul($avg_price, $mux, 8);
        }
        if ($tradetype == 'sell') {
            $comm = bcdiv($sell_commission, 100, 8);
            $mux = bcsub(1, $comm, 8);
            $final_price = bcmul($avg_price, $mux, 8);
        }

        if ($inverse) {

            $quantity = bcdiv($quantity, $final_price, 8);
        }

        //$this->error($final_price);
        //$offer=array($avg_price,$final_price,$offer_qty,$i,$check_offers);


        //clog('Quote_',json_encode(array('profit'=>$profit,'inverse'=>$inverse,'avg_price'=>$avg_price,'commission'=>$commission,'quantity'=>$quantity,'total'=>$total,'final_total'=>$final_total,'final_price'=>$final_price)));


        if ($tradetype == 'buy') {
            $diff = bcsub($final_price, $avg_price, 8);
        }
        if ($tradetype == 'sell') {
            $diff = bcsub($avg_price, $final_price, 8);
        }

        $profit = bcmul($diff, $quantity, 8);

        $total = (double)bcmul($avg_price, $quantity, 8);
        $final_total = (double)bcmul($final_price, $quantity, 8);


        if (self::ROUNDABOUT == 0) {
            $final_total = round($final_total, self::ROUNDABOUT);
        }

        //@TODO add user balance check if he has enough
        $coin = $base_coin;
        $coind = $base_coin . 'd';


        if ($tradetype == 'buy') {
            $type = 1;

            $spend_coin = strtolower($base_coin);
            $spend_total = $final_total;

            //receivable in case of buy , spendable in case of sell
            $receive_coin = strtolower($trade_coin);
            $receive_total = format_num($quantity, self::ROUNDABOUT);

        } //receivable in case of buy , spendable in case of sell
        else {
            $type = 2;
            $spend_coin = strtolower($trade_coin);
            $spend_total = format_num($quantity, self::ROUNDABOUT);


            $receive_coin = strtolower($base_coin);
            $receive_total = $final_total;
            $receive_coind = $receive_coin . 'd';
        }

        $user_balance = M('UserCoin')->where(array('userid' => $uid))->field("id,userid,$spend_coin")->find();
        if ($user_balance['id'] > 0 && $user_balance['userid'] == $uid) {
            $user_avail_balance = format_num($user_balance[$spend_coin], self::ROUNDABOUT);
        } else {
            $this->error('Please logout and login, If this issue persist, please contact admin');
        }

        $msg = "Your Quote is ready and available for $expire secs!";
        if ($spend_total > $user_avail_balance) {
            //$this->error("You have low balance of $user_avail_balance , It needs $final_total");
            $msg = "You have low balance of $user_avail_balance , It needs $spend_total";
            $quote_id = 0;
        } else {
            $quote_id = $uid . '_' . time();
        }

        $quote = array('qid' => $quote_id, 'trade_type' => $tradetype, 'trade' => $trade_coin, 'base' => $base_coin, 'symbol' => $symbol, 'qty' => $quantity, 'final_price' => $final_price, 'final_total' => $final_total, 'profit' => $profit, 'addtime' => time(), 'bankinfo' => $bankinfo);
        $client_response['status'] = 1;
        $client_response['msg'] = $msg;
        $client_response['data'] = array('qid' => $quote_id, 'trade_type' => $tradetype, 'symbol' => $symbol, 'qty' => $quantity, 'price' => $final_price, 'total' => $final_total, 'trade' => strtoupper($trade_coin), 'base' => strtoupper($base_coin), 'expire' => $expire);
        $mo = M();
        $saveme = array('qid' => $quote_id, 'data' => json_encode($quote));

        $if_saved = $mo->table('codono_fx_quotes')->add($saveme);
        exit(json_encode(($client_response)));


    }

    private function findBank($id, $userid)
    {
        $id = (int)$id;
        return M('FxBank')->where(array('userid' => $userid, 'id' => $id))->find();
    }

    private function getConversion($fromcoin = 'USD', $tocoin = 'USD', $tradetype = 'buy', $amount = 1)
    {
        $Xe = XeConvert();

        if ($tradetype == 'buy') {
            $resp = $Xe->convertToExchangeRates($fromcoin, $tocoin, $amount);
        } else {
            $resp = $Xe->convertFromExchangeRates($fromcoin, $tocoin, $amount);
        }
        return $this->processJSON($tradetype, $resp);

    }

    private function processJSON($type, $json)
    {
        if (isJson($json)) {
            return false;
        }
        $obj = json_decode($json);

        if ($type == 'buy') {
            return $obj->from[0]->mid;
        } else {
            return $obj->to[0]->mid;
        }

    }

    function isJson($str)
    {
        $json = json_decode($str);
        return $json && $str != $json;
    }

    public function approvequote($qid)
    {
        $uid = userid();
        if (!$uid) {
            $this->error(L('PLEASE_LOGIN'));
        }
        $split = explode("_", $qid);
        if ($split[0] != $uid) {
            $this->error('Invalid Quote ERROR:Q1');
        }


        $now = time();
        $mo = M();
        $where = array('qid' => $qid);
        $response = $mo->table('codono_fx_quotes')->where($where)->find();


        $quote = json_decode($response['data'], true);
        $quote_id = $response['qid'];

        $profit = $quote['profit'];

        /*	{"qid":"50_1604341391","trade_type":"sell","trade":"btc","base":"usd","symbol":"btcusd","qty":"0.00007587","final_price":"13178.72342500","final_total":0.99986974,"profit":"0.02301490","addtime":1604341391}
        */


        if ($split[1] != $quote['addtime'] && $quote['addtime'] != 0) {
            $this->error('Invalid Quote ERROR:U1');
        }

        if ($qid != $quote_id || $qid == 0 || $qid == '' || $quote['final_total'] <= 0 || $quote['qty'] <= 0 || $quote['final_price'] <= 0 || $quote['profit'] <= 0) {
            $this->error('This is invalid quote request, Please refresh and try again');
        }


        $lived = $now - $quote['addtime'];
        if (self::QUOTE_LIFE < $lived) {
            $this->error('Quote is expired already, Please refresh and try again');
        }

        $Fx = M('Fx')->where(array('coinname' => $quote['trade'], 'status' => 1))->find();
        if (!$Fx) {
            $this->error('Please try again later');
        }
        $cur_user_info = $mo->table('codono_user')->where(array('id' => $uid))->field('invit_1,invit_2,invit_3')->find();

        $tier_1_user = $cur_user_info['invit_1'];
        $tier_1_percent = bcdiv($Fx['tier_1'], 100, 8);
        $tier_1 = bcmul($profit, $tier_1_percent, 8);

        $tier_2_user = $cur_user_info['invit_2'];
        $tier_2_percent = bcdiv($Fx['tier_2'], 100, 8);
        $tier_2 = bcmul($profit, $tier_2_percent, 8);


        $tier_3_user = $cur_user_info['invit_3'];
        $tier_3_percent = bcdiv($Fx['tier_3'], 100, 8);
        $tier_3 = bcmul($profit, $tier_3_percent, 8);

        $tier_commission_part1 = bcadd($tier_1, $tier_2, 8);
        $tier_commission_total = bcadd($tier_commission_part1, $tier_3, 8);
        $site_profit = bcsub($profit, $tier_commission_total, 8);


        $fees_percent = 0;//$Fx['fees'];
        $fee_percent = bcdiv($fees_percent, 100, 8);

        $fees_paid = bcmul($fee_percent, $quote['final_total'], 8);
        $final_total = bcsub($quote['final_total'], $fees_paid, 8);

        $qty = format_num($quote['qty'], self::ROUNDABOUT);


        //check balance
        $fx_insert = array(
            'qid' => $quote['qid'],
            'userid' => $uid,
            'type' => $quote['trade_type'],
            'trade_coin' => $quote['trade'],
            'base_coin' => $quote['base'],
            'final_price' => format_num($quote['final_price'], self::ROUNDABOUT),
            'profit' => format_num($quote['profit'], self::ROUNDABOUT),
            'fees_paid' => format_num($fees_paid, self::ROUNDABOUT),
            'qty' => format_num($quote['qty'], self::ROUNDABOUT),
            'final_total' => format_num($final_total, self::ROUNDABOUT),
            'bank' => json_encode($quote['bankinfo']),
            'addtime' => $quote['addtime'],
            'status' => 1
        );

        $order_type = $quote['trade_type'];

        //spendable in case of buy, receivable in case of sell
        if ($order_type == 'buy') {
            $type = 1;

            $spend_coin = strtolower($quote['base']);
            $spend_total = $final_total;

            //receivable in case of buy , spendable in case of sell
            $receive_coin = strtolower($quote['trade']);
            $receive_total = format_num($quote['qty'], self::ROUNDABOUT);

        } //receivable in case of buy , spendable in case of sell
        else {
            $type = 2;
            $spend_coin = strtolower($quote['trade']);
            $spend_total = format_num($quote['qty'], self::ROUNDABOUT);


            $receive_coin = strtolower($quote['base']);
            $receive_total = $final_total;
        }
        $receive_coind = $receive_coin . 'd';


        $mo = M();

        $user_balances = $mo->table('codono_user_coin')->where(array('userid' => $uid))->find();
        if ($user_balances[$spend_coin] < $spend_total) {
            $this->error('You dont have enough balance');
        }
        

        $mo->startTrans();
        $rs = array();


        $rs[] = $fx_log_id = $mo->table('codono_fx_log')->add($fx_insert);
        $rs[] = $mo->table('codono_user_coin')->where(array('userid' => $uid))->setDec($spend_coin, $spend_total);

        //Since payment is settled out of exchange then no need to send  on exchange
        //$rs[] = $mo->table('codono_user_coin')->where(array('userid' => $uid))->setInc($receive_coin, $receive_total);

        $finance_mum_user_coin = $mo->table('codono_user_coin')->where(array('userid' => $uid))->find();

        $finance_hash = md5($quote['qid']);

        $xnum = bcadd($user_balances[$receive_coin], $user_balances[$receive_coind], 8);
        $mum = bcadd($finance_mum_user_coin[$receive_coin], $finance_mum_user_coin[$receive_coind], 8);
        $rs[] = $mo->table('codono_finance')->add(array('userid' => $uid, 'coinname' => $receive_coin, 'num_a' => $user_balances[$receive_coin], 'num_b' => $user_balances[$receive_coind], 'num' => $xnum, 'fee' => $fees_paid, 'type' => $type, 'name' => 'fx', 'nameid' => $fx_log_id, 'remark' => "FX  $order_type:" . $Fx['coinname'], 'mum_a' => $finance_mum_user_coin[$receive_coin], 'mum_b' => $finance_mum_user_coin[$receive_coind], 'mum' => $mum, 'move' => $finance_hash, 'addtime' => time(), 'status' => 1));

        if ($type == 1) {
            $rs[] = $mo->table('codono_fx')->where(array('id' => $Fx['id']))->setInc('deal_buy', $qty);
        }
        if ($type == 2) {
            $rs[] = $mo->table('codono_fx')->where(array('id' => $Fx['id']))->setInc('deal_sell', $qty);
        }
        //Increase Token Owners Balance token_owners_keep
        if ($Fx['ownerid'] > 0 && $profit > 0 && $site_profit > 0) {
            $rs[] = $mo->table('codono_user_coin')->where(array('userid' => $Fx['ownerid']))->setInc($quote['base'], $site_profit);
            $rs[] = $mo->table('codono_invit')->add(array('coin'=>$quote['base'],'userid' => $Fx['ownerid'], 'invit' => $uid, 'name' => $quote['base'], 'type' => "Fx $order_type:" . $Fx['coinname'], 'num' => $qty, 'mum' => $final_total, 'fee' => $site_profit, 'addtime' => time(), 'status' => 1));
        }

        //Tier Distribution
        if ($profit > 0) {
            if ($tier_1 > 0 && $tier_1_user > 0) {
                $rs[] = $mo->table('codono_user_coin')->where(array('userid' => $tier_1_user))->setInc($quote['base'], $tier_1);
                $rs[] = $mo->table('codono_invit')->add(array('coin'=>$quote['base'],'userid' => $tier_1_user, 'invit' => $uid, 'name' => $quote['base'], 'type' => "Fx $order_type:" . $Fx['coinname'], 'num' => $qty, 'mum' => $final_total, 'fee' => $tier_1, 'addtime' => time(), 'status' => 1));
            }
            if ($tier_2 > 0 && $tier_2_user > 0) {
                $rs[] = $mo->table('codono_user_coin')->where(array('userid' => $tier_2_user))->setInc($quote['base'], $tier_2);
                $rs[] = $mo->table('codono_invit')->add(array('coin'=>$quote['base'],'userid' => $tier_2_user, 'invit' => $uid, 'name' => $quote['base'], 'type' => "Fx $order_type:" . $Fx['coinname'], 'num' => $qty, 'mum' => $final_total, 'fee' => $tier_2, 'addtime' => time(), 'status' => 1));
            }
            if ($tier_3 > 0 && $tier_3_user > 0) {
                $rs[] = $mo->table('codono_user_coin')->where(array('userid' => $tier_3_user))->setInc($quote['base'], $tier_3);
                $rs[] = $mo->table('codono_invit')->add(array('coin'=>$quote['base'],'userid' => $tier_3_user, 'invit' => $uid, 'name' => $quote['base'], 'type' => "Fx $order_type:" . $Fx['coinname'], 'num' => $qty, 'mum' => $final_total, 'fee' => $tier_3, 'addtime' => time(), 'status' => 1));
            }

        }
        if (check_arr($rs)) {
            $mo->commit();
            $exit_data['status'] = 1;
            $exit_data['msg'] = 'Your trade was successful';
            $exit_data['data'] = $fx_log_id;
            $exit_data['url'] = SITE_URL . 'Fx/log/id/' . $fx_log_id;
            exit(json_encode($exit_data));
        } else {
            $mo->rollback();
            $this->error('For some reasons we could not do this trade,Please try again later!');
        }

    }

    public function log($id = 0)
    {
        $uid = userid();
        if (!$uid) {
            redirect(U('Login/login'));
        }
        if (!check($id, 'd')) {
            redirect(U('Fx/records'));
        }
        $where['id'] = $id;
        $where['status'] = array('egt', 1);
        $where['userid'] = $uid;
        $FxLog = M('FxLog');


        $single_record = $FxLog->where($where)->find();

        $this->assign('record', $single_record);
        $this->display();
    }

    public function bank()
    {
        if (!userid()) {
            redirect(U('Login/login'));
        }


        //$truename = M('User')->where(array('id' => userid()))->getField('truename');
        $user = $this->userinfo; //M('User')->where(array('id' => userid()))->find();
        if ($user['idcardauth'] == 0 && KYC_OPTIONAL == 0) {
            redirect('/user/nameauth');
        }
        $truename = $user['truename'];
        $this->assign('truename', $truename);
        $UserBank = M('FxBank')->where(array('userid' => userid(), 'status' => 1))->order('id desc')->select();
        $this->assign('UserBank', $UserBank);
        $this->display();
    }

    public function upbank($bank, $coin, $bankaddr, $bankcard, $truename = null)
    {
        if (!userid()) {
            redirect(U('Login/login'));
        }


        if (!check($bank, 'a')) {
            $this->error(L('Bank malformed!'));
        }
        if (!check($coin, 'n')) {
            $this->error('Select Proper coin');
        }

        if (!check($truename, 'english')) {
            $this->error(L('Account name incorrect format!'));
        }
        if (!check($bankaddr, 'a')) {
            $this->error(L('Bank address format error!'));
        }

        if (!check($bankcard, 'd')) {
            $this->error(L('Bank account number format error!'));
        }

        $userBank = M('FxBank')->where(array('userid' => userid()))->select();


        if (10 <= count($userBank)) {
            $this->error('Each user can add upto 10 accounts max!');
        }

        if (M('FxBank')->add(array('userid' => userid(), 'coin' => $coin, 'bank' => $bank, 'truename' => $truename, 'bankaddr' => $bankaddr, 'bankcard' => $bankcard, 'addtime' => time(), 'status' => 1))) {
            $this->success(L('Banks added successfully!'));
        } else {
            $this->error(L('Bank Add Failed!'));
        }
    }

    public function delbank($id, $paypassword)
    {

        if (!userid()) {
            redirect(U('Login/login'));
        }

        if (!check($paypassword, 'password')) {
            $this->error(L('Fund Pwd format error!'));
        }

        if (!check($id, 'd')) {
            $this->error(L('INCORRECT_REQ'));
        }

        $user_paypassword = $this->userinfo['paypassword'];//M('User')->where(array('id' => userid()))->getField('paypassword');

        if (md5($paypassword) != $user_paypassword) {
            $this->error(L('Trading password is wrong!'));
        }

        if (!M('FxBank')->where(array('userid' => userid(), 'id' => $id))->find()) {
            $this->error(L('Unauthorized access!'));
        } else if (M('FxBank')->where(array('userid' => userid(), 'id' => $id))->delete()) {
            $this->success(L('successfully deleted!'));
        } else {
            $this->error(L('failed to delete!'));
        }
    }

    public function records($ls = 15)
    {
        $uid = userid();
        if (!$uid) {
            redirect(U('Login/login'));
        }

        $where['status'] = array('egt', 1);
        $where['userid'] = $uid;
        $FxLog = M('FxLog');
        $count = $FxLog->where($where)->count();
        $Page = new Page($count, $ls);
        $show = $Page->show();
        $list = $FxLog->where($where)->order('id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();

        $this->assign('list', $list);
        $this->assign('page', $show);


        $this->display();
    }


}