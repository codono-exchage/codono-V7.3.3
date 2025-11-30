<?php

namespace Home\Controller;

use Common\Ext\Exception;
use Think\Page;

class FinanceController extends HomeController
{
    public const allowed_types = ALLOWED_TYPES;  // type of account balances
    public const G2FA_REQUIRED_FOR_WITHDRAWAL = 1;  // IF Google2Fa required for withdrawal
    public const SHOW_SITE_DIVIDEND = 0;  // IF Google2Fa required for withdrawal
    public const DUST_MINIMUM = 0.5; //coin to be considered for dust conversion in SYSTEMCURRENCY

    public function _initialize()
    {
        $this->multichain = 1;
        $this->assign('show_dividend', self::SHOW_SITE_DIVIDEND);
        parent::_initialize();
    }

    private function calcEstimatedAssetvalue()
    {

        $uid=userid();
        $CoinList = C('coin_safe');
        $UserCoin = M('UserCoin')->where(['userid' => $uid])->find();
        $Market = C('market');
        $User_Selected_coin = $this->userinfo['fiat'];
        $print_coins = [];
        if (!isset($User_Selected_coin['fiat'])) {
            $conversion_coin = SYSTEMCURRENCY;
        } else {
            $conversion_coin = $User_Selected_coin['fiat'];
        }
        foreach ($Market as $k => $v) {
            $Market[$v['name']] = $v;
        }

        $usd['zj'] = 0;

        $cmcs = (APP_DEBUG ? null : S('cmcrates'));

        if (!$cmcs) {
            $cmcs = M('Coinmarketcap')->field(['symbol', 'price_usd'])->select();
            S('cmcrates', $cmcs);
        }

        //Find


        $multiplier = 1;

        foreach ($cmcs as $ckey => $cval) {
            if (strtolower($conversion_coin) != 'usd' && $cval['symbol'] == strtoupper($conversion_coin)) {
                $multiplier = $cval['price_usd'];
            }
            $the_cms[strtolower($cval['symbol'])] = $cval['price_usd'];
        }

        foreach ($CoinList as $k => $v) {
            {
                if ($v['symbol']) {
                    continue;
                }
                $x_market = strtolower($v['name'] . '_' . $conversion_coin);
                if ($v['name'] == strtolower($conversion_coin)) {
                    $usd['ky'] = format_num($UserCoin[$v['name']], 2) * 1;
                    $usd['dj'] = format_num($UserCoin[$v['name'] . 'd'], 2) * 1;
                }
                if (isset($the_cms[$v['name']])) {
                    $jia = $before = $the_cms[$v['name']];
                } else {
                    if (isset(c('market')[$x_market])) {
                        $jia = c('market')[$x_market]['new_price'];
                    } else {
                        $jia = 0;
                    }

                }
                $jia = $after = NumToStr($jia);//$jia = $after = bcdiv((double)$jia, $multiplier, 8);

                $print_coins[$v['name']] = ['name' => $v['name'], 'img' => $v['img'], 'title' => strtoupper($v['name']) . ' [ ' . ucfirst($v['title']) . ' ]', 'xnb' => format_num($UserCoin[$v['name']], 8) * 1, 'xnbd' => format_num($UserCoin[$v['name'] . 'd'], 8) * 1, 'xnbz' => bcadd($UserCoin[$v['name']], $UserCoin[$v['name'] . 'd'], 8), 'jia' => $jia, 'zhehe' => bcmul(bcadd($UserCoin[$v['name']], $UserCoin[$v['name'] . 'd'], 8), $jia, 4), 'type' => $v['type'], 'deposit_status' => $v['zr_jz'], 'withdrawal_status' => $v['zc_jz']];

                $usd['zj'] = bcadd($usd['zj'], bcmul(bcadd($UserCoin[$v['name']], $UserCoin[$v['name'] . 'd'], 8), $jia, 8), 2) * 1;
            }
        }
        return ['cc' => $conversion_coin, 'pc' => $print_coins, 'usd' => $usd];
    }

    public function index()
    {

        if (!userid()) {
            redirect(U('Login/login'));
        }


        $calc = $this->calcEstimatedAssetvalue();
        $conv_coin_img = C('coin')[strtolower($calc['cc'])]['img'] ?: 'default.png';
        $this->assign('conv_coin_img', $conv_coin_img);
        $this->assign('conversion_coin', $calc['cc']);
        $this->assign('usd', $calc['usd']);
        $this->assign('coinList', $calc['pc']);
        $this->assign('prompt_text', D('Text')->get_content('finance_index'));
        $this->display();
    }

    public function dust()
    {
        $uid = userid();
        if (!$uid) {
            redirect(U('Login/login'));
        }

        $CoinList = C('Coin');
        $UserCoin = M('UserCoin')->where(['userid' => $uid])->find();
        $Market = C('market');
        $User_Selected_coin = $this->userinfo['fiat'];//M('User')->where(array('id' => userid()))->field('fiat')->find();
        $conversion_coin = DUST_COIN;
        $print_coins = [];

        /*
        if (!isset($User_Selected_coin['fiat'])) {
            $conversion_coin = SYSTEMCURRENCY;
        } else {
            $conversion_coin = $User_Selected_coin['fiat'];
        }*/

        foreach ($Market as $k => $v) {
            $Market[$v['name']] = $v;
        }

        $usd['zj'] = 0;

        $cmcs = (APP_DEBUG ? null : S('cmcrates'));

        if (!$cmcs) {
            $cmcs = M('Coinmarketcap')->field(['symbol', 'price_usd'])->select();
            S('cmcrates', $cmcs);
        }

        //Find


        $multiplier = 1;

        foreach ($cmcs as $ckey => $cval) {
            if (strtolower($conversion_coin) != 'usd' && $cval['symbol'] == strtoupper($conversion_coin)) {
                $multiplier = $cval['price_usd'];
            }
            $the_cms[strtolower($cval['symbol'])] = $cval['price_usd'];
        }

        foreach ($CoinList as $k => $v) {
            {

                $x_market = strtolower($v['name'] . '_' . $conversion_coin);
                if ($v['name'] == strtolower($conversion_coin)) {
                    $usd['ky'] = format_num($UserCoin[$v['name']], 2) * 1;
                    $usd['dj'] = format_num($UserCoin[$v['name'] . 'd'], 2) * 1;
                }
                if (isset($the_cms[$v['name']])) {
                    $jia = $before = $the_cms[$v['name']];
                } else {
                    $jia = $before = 0;
                    //$jia=c('market')[$x_market]['new_price'];
                }
                $jia = $after = bcdiv((double)$jia, $multiplier, 8);
                $total = bcmul(bcadd($UserCoin[$v['name']], $UserCoin[$v['name'] . 'd'], 8), $jia, 8);
                $require_ment = bcmul($UserCoin[$v['name']], $jia, 8);
                if ($total <= self::DUST_MINIMUM && $require_ment > 0) {
                    $print_coins[$v['name']] = ['name' => $v['name'], 'img' => $v['img'], 'title' => strtoupper($v['name']) . ' [ ' . ucfirst($v['title']) . ' ]', 'xnb' => format_num($UserCoin[$v['name']], 8) * 1, 'xnbd' => format_num($UserCoin[$v['name'] . 'd'], 8) * 1, 'xnbz' => bcadd($UserCoin[$v['name']], $UserCoin[$v['name'] . 'd'], 8), 'jia' => $jia, 'zhehe' => $total, 'type' => $v['type'], 'deposit_status' => $v['zr_jz'], 'withdrawal_status' => $v['zc_jz']];
                    $usd['zj'] = bcadd($usd['zj'], bcmul(bcadd($UserCoin[$v['name']], $UserCoin[$v['name'] . 'd'], 8), $jia, 8), 2) * 1;
                }

            }
        }
        unset($print_coins[DUST_COIN]);
        $this->assign('conversion_coin', $conversion_coin);
        $this->assign('usd', $usd);
        $this->assign('coinList', $print_coins);
        $this->assign('prompt_text', D('Text')->get_content('finance_index'));
        $this->display();
    }

    public function doDustConvert()
    {
        $userid = userid();
        if (!$userid) {
            redirect(U('Login/login'));
        }


        $coin = strtolower(I('request.coin', 'usdt', 'string'));
        $coind = $coin . 'd';
        $isValidCoin = $this->isValidCoin($coin);
        if ($coin == null || !$isValidCoin) {
            $this->error('Invalid coin');
        }
        $UserCoin = M('UserCoin')->where(['userid' => userid()])->find();
        $dust_coin = strtolower(DUST_COIN);
        $min_require_dust = self::DUST_MINIMUM;
        $user_coin_bal = $UserCoin[$coin];

        if ($user_coin_bal <= 0) {
            $this->error(L('Insufficient funds available'));
        }
        $value_of_dust = 0;
        $conversion_coin = $dust_coin;


        $cmcs = (APP_DEBUG ? null : S('cmcrates'));

        if (!$cmcs) {
            $cmcs = M('Coinmarketcap')->field(['symbol', 'price_usd'])->select();
            S('cmcrates', $cmcs);
        }

        //Find


        $multiplier = 1;

        foreach ($cmcs as $ckey => $cval) {
            if (strtolower($conversion_coin) != 'usd' && $cval['symbol'] == strtoupper($conversion_coin)) {
                $multiplier = $cval['price_usd'];
            }
            $the_cms[strtolower($cval['symbol'])] = $cval['price_usd'];
        }


        $v = C('coin')[$coin];
        $x_market = strtolower($v['name'] . '_' . $conversion_coin);
        if ($v['name'] == strtolower($conversion_coin)) {
            $usd['ky'] = format_num($UserCoin[$v['name']], 2) * 1;
            $usd['dj'] = format_num($UserCoin[$v['name'] . 'd'], 2) * 1;
        }
        if (isset($the_cms[$v['name']])) {
            $jia = $before = $the_cms[$v['name']];
        } else {
            //$jia = $before = 1;
            $jia = c('market')[$x_market]['new_price'];
        }
        $jia = $after = bcdiv((double)$jia, $multiplier, 8);

        $receiveable_amount = bcmul($UserCoin[$v['name']], $jia, 8); //give $dust_coin $receiveable_amount and take $user_coin_bal $coin

        $mo = M();
        $mo->startTrans();
        $rs = [];
        $before_spot_balance = $mo->table('codono_user_coin')->where(['userid' => $userid])->field([$coin, $coind])->find();
        $dust_entry = ['uid' => $userid, 'from_coin' => $coin, 'from_amount' => $user_coin_bal, 'to_coin' => $dust_coin, 'to_amount' => $receiveable_amount, 'created_at' => time()];

        $rs[] = $did = $mo->table('codono_dust')->add($dust_entry);
        $rs[] = $mo->table('codono_user_coin')->where(['userid' => $userid])->setDec($coin, $user_coin_bal);
        $rs[] = $mo->table('codono_user_coin')->where(['userid' => $userid])->setInc($dust_coin, $receiveable_amount);
        //Check if user_assets table has the following row or not userid=$userid and coin=$coin if not then first insert then update


        if (check_arr($rs)) {
            $mo->commit();

            $this->success(L('Dust Conversion completed!!'));
        } else {
            $mo->rollback();
            $this->error(L('There were issues converting!'));
        }


    }

    /**
     * Shows Users balances other than Spot
     * Could be p2p,margin,staking,stock
     * @param $type
     * @return void
     */
    public function assets()
    {
        $type = I('get.type', null, 'trim');
        $uid = userid();
        if (!$uid) {
            redirect(U('Login/login'));
        }
        $baltypes = array_keys($this->giveBalanceTypes());
        if ($type == null) {
            $type = $baltypes[1];
        }

        $bal_type = $this->getBalanceType($type);
        if ($type == 'spot') {
            redirect(U('Finance/index'));
        }
        if ($bal_type == 0) {
            A('Content')->redirection(U('Finance/index'), 5, L('Please select Correct Asset type'));
            exit;
        }

        $CoinList = C('coin_safe');
        $UserAssets = M('UserAssets')->where(['uid' => $uid, 'account' => $bal_type])->select();

        $UserCoin = $fiat = $crypto = [];
        foreach ($UserAssets as $UserAsset) {
            $UserCoin[$UserAsset['coin']] = $UserAsset['balance'];
            $UserCoin[$UserAsset['coin'] . 'd'] = $UserAsset['freeze'];
            $UserCoin['total'][$UserAsset['coin']] = bcadd($UserAsset['balance'], $UserAsset['freeze'], 8);
        }

        $Market = C('market');//M('Market')->where(array('status' => 1))->select();
        $User_Selected_coin = $this->userinfo['fiat'];//M('User')->where(array('id' => userid()))->field('fiat')->find();

        if (!isset($User_Selected_coin['fiat'])) {
            $conversion_coin = SYSTEMCURRENCY;
        } else {
            $conversion_coin = $User_Selected_coin['fiat'];
        }
        foreach ($Market as $k => $v) {
            $Market[$v['name']] = $v;
        }

        $usd['zj'] = 0;

        $cmcs = (APP_DEBUG ? null : S('cmcrates'));

        if (!$cmcs) {
            $cmcs = M('Coinmarketcap')->field(['symbol', 'price_usd'])->select();
            S('cmcrates', $cmcs);
        }

        //Find


        $multiplier = 1;

        foreach ($cmcs as $ckey => $cval) {
            if (strtolower($conversion_coin) != 'usd' && $cval['symbol'] == strtoupper($conversion_coin)) {
                $multiplier = $cval['price_usd'];
            }
            $the_cms[strtolower($cval['symbol'])] = $cval['price_usd'];
        }
        $conv_total = 0;
        foreach ($UserCoin['total'] as $key => $total) {

            if ($total > 0) {

                $x_market = strtolower($key . '_' . $conversion_coin);

                if (isset($the_cms[$key])) {
                    $jia = $the_cms[$key];
                } else {
                    $jia = $before = 1;
                    $jia = c('market')[$x_market]['new_price'];
                }
                $jia = $after = bcdiv((double)$jia, $multiplier, 8);
                $conv[$key] = bcmul($jia, $total, 8);
                $conv_total = bcadd($conv_total, bcmul($jia, $total, 8), 8);
            }
        }

        foreach ($CoinList as $k => $v) {
            {

                $x_market = strtolower($v['name'] . '_' . $conversion_coin);
                if ($v['name'] == strtolower($conversion_coin)) {
                    $usd['ky'] = format_num($UserCoin[$v['name']], 2) * 1;
                    $usd['dj'] = format_num($UserCoin[$v['name'] . 'd'], 2) * 1;
                }
                if (isset($the_cms[$v['name']])) {
                    $jia = $before = $the_cms[$v['name']];
                } else {
                    $jia = $before = 1;
                    //$jia=c('market')[$x_market]['new_price'];
                }
                $jia = $after = bcdiv((double)$jia, $multiplier, 8);

                if ($v['type'] == 'rmb') {

                    $fiat[$v['name']] = ['name' => $v['name'], 'img' => $v['img'], 'title' => strtoupper($v['name']) . ' [ ' . ucfirst($v['title']) . ' ]', 'xnb' => format_num($UserCoin[$v['name']], 8) * 1, 'xnbd' => format_num($UserCoin[$v['name'] . 'd'], 8) * 1, 'xnbz' => bcadd($UserCoin[$v['name']], $UserCoin[$v['name'] . 'd'], 8), 'jia' => $jia * 1, 'zhehe' => bcmul(bcadd($UserCoin[$v['name']], $UserCoin[$v['name'] . 'd'], 8), $jia, 4), 'type' => $v['type'], 'deposit_status' => $v['zr_jz'], 'withdrawal_status' => $v['zc_jz']];
                } else {
                    $crypto[$v['name']] = ['name' => $v['name'], 'img' => $v['img'], 'title' => strtoupper($v['name']) . ' [ ' . ucfirst($v['title']) . ' ]', 'xnb' => format_num($UserCoin[$v['name']], 8) * 1, 'xnbd' => format_num($UserCoin[$v['name'] . 'd'], 8) * 1, 'xnbz' => bcadd($UserCoin[$v['name']], $UserCoin[$v['name'] . 'd'], 8), 'jia' => $jia * 1, 'zhehe' => bcmul(bcadd($UserCoin[$v['name']], $UserCoin[$v['name'] . 'd'], 8), $jia, 4), 'type' => $v['type'], 'deposit_status' => $v['zr_jz'], 'withdrawal_status' => $v['zc_jz']];
                }
                $usd['zj'] = bcadd($usd['zj'], bcmul(bcadd($UserCoin[$v['name']], $UserCoin[$v['name'] . 'd'], 8), $jia, 8), 2) * 1;
            }
        }
        $calc = $this->calcEstimatedAssetvalue();

        $this->assign('baltypes', $baltypes);
        $this->assign('conversion_coin', $conversion_coin);
        $this->assign('usd', $calc['usd']);
        $this->assign('conv_total', $conv_total);
        $this->assign('fiatList', $fiat);
        $this->assign('cryptoList', $crypto);
        $this->assign('type', $type);
        $this->assign('prompt_text', D('Text')->get_content('finance_index'));
        $this->display();
    }

    public function holdings()
    {
        $this->assign('show_dividend', self::SHOW_SITE_DIVIDEND);
        if (self::SHOW_SITE_DIVIDEND == 1) {
            die('Turned Off');
        }
        if (!userid()) {
            redirect(U('Login/login'));
        }

        $this->assign('prompt_text', D('Text')->get_content('game_dividend'));
        $coin_list = D('Coin')->get_all_xnb_list_allow();

        $list = [];
        foreach ($coin_list as $k => $v) {
            if (C('coin')[$k]['symbol']) {
                continue;
            }
            $list[$k]['img'] = C('coin')[$k]['img'];//D('Coin')->get_img($k);
            $list[$k]['title'] = $v;
            $list[$k]['quanbu'] = D('Coin')->get_sum_coin($k);
            $list[$k]['quanbu'] = $list[$k]['quanbu'] ?: 1;
            $list[$k]['wodi'] = D('Coin')->get_sum_coin($k, userid());
            $list[$k]['bili'] = bcmul(bcdiv($list[$k]['wodi'], $list[$k]['quanbu'], 8), 100, 2) . '%';
        }

        $this->assign('list', $list);
        $this->display();
    }


    public function mybonus()
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


    public function bank()
    {
        if (!userid()) {
            redirect(U('Login/login'));
        }


        $UserBankType = M('UserBankType')->where(['status' => 1])->order('id desc')->select();
        $this->assign('UserBankType', $UserBankType);


        //$truename = M('User')->where(array('id' => userid()))->getField('truename');
        $user = $this->userinfo; //M('User')->where(array('id' => userid()))->find();

        if ($user['idcardauth'] == 0 && KYC_OPTIONAL == 0) {
            redirect('/user/nameauth');
        }

        $truename = $user['truename'];
        $this->assign('truename', $truename);
        $UserBank = M('UserBank')->where(['userid' => userid(), 'status' => 1])->order('id desc')->select();
        $FiatList = M('Coin')->where(['status' => 1, 'type' => 'rmb'])->field('name,title')->select();
        $this->assign('FiatList', $FiatList);
        $this->assign('UserBank', $UserBank);
        $this->assign('prompt_text', D('Text')->get_content('user_bank'));
        $this->display();
    }


    public function upbank($name, $bank, $bankprov, $bankcity, $bankaddr, $bankcard, $paypassword, $truename = 'NA')
    {
        if (!userid()) {
            redirect(U('Login/login'));
        }
        $name = I('post.name', '', 'trim');
        $bank = I('post.bank', '', 'trim');
        $bankprov = I('post.bankprov', '', 'trim');
        $bankcity = I('post.bankcity', '', 'trim');
        $bankaddr = I('post.bankaddr', '', 'trim');
        $bankcard = I('post.bankcard', '', 'trim');
        $paypassword = I('post.paypassword', '', 'trim');
        $truename = I('post.truename', 'NA', 'trim');
        if (!check($name, 'a')) {
            $this->error(L('Note name of the wrong format!'));
        }
        if (!check($truename, 'english')) {
            $this->error(L('Account name incorrect format!'));
        }

        if (!check($bank, 'a')) {
            $this->error(L('Bank malformed!'));
        }

        if (!check($bankprov, 'c')) {
            $this->error(L('Opening provinces format error!'));
        }

        if (!check($bankcity, 'c')) {
            $this->error('Format of the city is wrong!');
        }

        if (!check($bankaddr, 'a')) {
            $this->error(L('Bank address format error!'));
        }

        if (!check($bankcard, 'a')) {
            $this->error(L('Bank account number format error!'));
        }

        if (strlen($bankcard) < 4 || strlen($bankcard) > 50) {

            $this->error(L('Bank account number format error!'));

        }


        if (!check($paypassword, 'password')) {
            $this->error(L('Fund Pwd format error!'));
        }

        $user_paypassword = $this->userinfo['paypassword'];//M('User')->where(array('id' => userid()))->getField('paypassword');

        if (md5($paypassword) != $user_paypassword) {
            $this->error(L('Trading password is wrong!'));
        }

        if (!M('UserBankType')->where(['title' => $bank])->find()) {
            $this->error(L('Bank error!'));
        }

        $userBank = M('UserBank')->where(['userid' => userid()])->select();

        foreach ($userBank as $k => $v) {
            if ($v['name'] == $name) {
                $this->error(L('Please do not use the same name Notes!'));
            }

            if ($v['bankcard'] == $bankcard) {
                $this->error(L('Bank card number already exists!'));
            }
        }

        if (10 <= count($userBank)) {
            $this->error('Each user can add upto 10 accounts only!');
        }

        if (M('UserBank')->add(['userid' => userid(), 'name' => $name, 'bank' => $bank, 'bankprov' => $bankprov, 'bankcity' => $bankcity, 'bankaddr' => $bankaddr, 'bankcard' => $bankcard, 'truename' => $truename, 'addtime' => time(), 'status' => 1])) {
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

        if (!M('UserBank')->where(['userid' => userid(), 'id' => $id])->find()) {
            $this->error(L('Unauthorized access!'));
        } else if (M('UserBank')->where(['userid' => userid(), 'id' => $id])->delete()) {
            $this->success(L('successfully deleted!'));
        } else {
            $this->error(L('failed to delete!'));
        }
    }


    public function mycz($status = NULL, $coinname = '')
    {
        if (FIAT_ALLOWED == 0) {
            die('Unauthorized!');
        }

        if (!userid()) {
            redirect(U('Login/login'));
        }

        $this->assign('prompt_text', D('Text')->get_content('finance_mycz'));

        if (!check($coinname, 'n')) {
            $coinname = DEFAULT_FIAT;
            //$this->error(L('Currency format error!'));
        }


        if (!C('coin')[$coinname]) {
            $this->error(L('Currency wrong!'));
        }

        if (!is_array(C('coin')[$coinname])) {
            $this->error(L('Invalid Coin!'));
        }

        $coin_name = strtolower($coinname);
        $coin_named = strtolower($coinname) . 'd';

        $myczType = M('MyczType')->where(['status' => 1])->select();
        $coin_id = C('coin')[$coinname]['id'];
        $myczTypeList = $fiatcoins = [];
        foreach ($myczType as $k => $v) {
            $show_coin = json_decode($v['show_coin']);
            if (in_array($coin_id, $show_coin)) {
                $myczTypeList[$v['name']]['id'] = $v['id'];     
                $myczTypeList[$v['name']]['name'] = $v['title'];
                $myczTypeList[$v['name']]['img'] = $v['img'];
            }
        }
        $this->assign('myczTypeList', $myczTypeList);
        $user_coin = M('UserCoin')->where(['userid' => userid()])->find();
        $user_coin['fiat'] = format_num($user_coin[$coin_name], 2);
        $user_coin['fiat_trade'] = format_num($user_coin[$coin_named], 2);
        $this->assign('user_coin', $user_coin);
        $coin_img = C('coin')[$coinname]['img'];
        if (($status == 1) || ($status == 2) || ($status == 3) || ($status == 4)) {
            $where['status'] = $status - 1;
        }
        foreach (C('coin') as $coinlist) {
            if ($coinlist['type'] == 'rmb' && $coinlist['zr_jz'] == 1) {
                $_fiat_coin['name'] = $coinlist['name'];
                $_fiat_coin['img'] = $coinlist['img'];
                $fiatcoins[] = $_fiat_coin;
            }

        }
        $this->assign('fiatcoins', $fiatcoins);
        $this->assign('coinname', $coinname);
        $this->assign('coin_img', $coin_img);
        $this->assign('status', $status);
        $where['userid'] = userid();
        $where['state'] = ['neq', '5'];
        $count = M('Mycz')->where($where)->count();
        $Page = new Page($count, 15);
        $show = $Page->show();
        $list = M('Mycz')->where($where)->order('id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();

        foreach ($list as $k => $v) {
            $list[$k]['type'] = M('MyczType')->where(['id' => $v['type']])->getField('title');
            $list[$k]['typeEn'] = $v['type'];
            $list[$k]['num'] = (Num($v['num']) ? Num($v['num']) : '');
            $list[$k]['mum'] = (Num($v['mum']) ? Num($v['mum']) : '');
        }

        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->display();
    }

    public function myczRemittance($id = NULL)
    {
        if (FIAT_ALLOWED == 0) {
            die('Unauthorized!');
        }
        if (!userid()) {
            $this->error(L('PLEASE_LOGIN'));
        }

        if (!check($id, 'd')) {
            $this->error(L('INCORRECT_REQ'));
        }

        $mycz = M('Mycz')->where(['id' => $id])->find();

        if (!$mycz) {
            $this->error(L('Top-order does not exist!'));
        }

        if ($mycz['userid'] != userid()) {
            $this->error(L('Illegal operation!'));
        }

        if ($mycz['status'] != 0) {
            $this->error(L('You can not mark it as paid!'));
        }

        $rs = M('Mycz')->where(['id' => $id])->save(['status' => 3]);

        if ($rs) {
            $this->success(L('Successful operation'));
        } else {
            $this->error(L('OPERATION_FAILED'));
        }
    }

    public function myczChakan($id = NULL)
    {
        if (FIAT_ALLOWED == 0) {
            die('Unauthorized!');
        }
        if (!userid()) {
            $this->error(L('PLEASE_LOGIN'));
        }

        if (!check($id, 'd')) {
            $this->error(L('INCORRECT_REQ'));
        }

        $mycz = M('Mycz')->where(['id' => $id])->find();

        if (!$mycz) {
            $this->error(L('Top-order does not exist!'));
        }

        if ($mycz['userid'] != userid()) {
            $this->error(L('Illegal operation!'));
        }

        if ($mycz['status'] != 0) {
            $this->error(L('Order has been processed!'));
        }

        $rs = M('Mycz')->where(['id' => $id])->save(['status' => 3]);

        if ($rs) {
            $this->success('', ['id' => $id]);
        } else {
            $this->error(L('OPERATION_FAILED'));
        }
    }

    public function myczUp($type, $num, $coinname = 'usd')
    {
        if (FIAT_ALLOWED == 0) {
            die('Unauthorized!');
        }
        if (!userid()) {
            $this->error('Please Login!');
        }


        if (!check($type, 'd')) {
            $this->error(L('Recharge way malformed!'));
        }

        if (!check($num, 'usd')) {
            $this->error(L('Recharge amount malformed!'));
        }
        if (!check($coinname, 'n')) {
            $this->error(L('Currency format error!'));
        }

        if (!C('coin')[$coinname]) {
            $this->error(L('Currency wrong!'));
        }

        $Coin = C('coin')[$coinname]; //M('Coin')->where(array('name' => $coinname))->find();

        if (!$Coin) {
            $this->error(L('Currency wrong!'));
        }
        $coin_name = strtolower($coinname);

        $myczType = M('MyczType')->where(['id' => $type])->find();

        if (!$myczType) {
            $this->error(L('There is no way to recharge!'));
        }

        if ($myczType['status'] != 1) {
            $this->error(L('There is no way to recharge open!'));
        }

        $coin_id = C('coin')[$coinname]['id'];
        $show_coin = json_decode($myczType['show_coin']);

        if (!in_array($coin_id, $show_coin)) {
            $this->error($coinname . ' can not be recharged using ' . $myczType['title']);
        }


        $mycz_min = ($myczType['min'] ?: 1);
        $mycz_max = ($myczType['max'] ?: 100000);

        if ($num < $mycz_min) {
            $this->error(L('Recharge amount can not be less than') . $mycz_min . ' ' . strtoupper($coinname));
        }

        if ($mycz_max < $num) {
            $this->error(L('Recharge amount can not exceed') . $mycz_max . ' ' . strtoupper($coinname));
        }


        for (; true;) {
            $tradeno = tradeno();

            if (!M('Mycz')->where(['tradeno' => $tradeno])->find()) {
                break;
            }
        }

        $mycz = M('Mycz')->add(['userid' => userid(), 'coin' => $coin_name, 'num' => $num, 'type' => $type, 'tradeno' => $tradeno, 'addtime' => time(), 'status' => 0]);

        if ($mycz) {
            $this->success(L('Prepaid orders created successfully!'), ['id' => $mycz]);
        } else {
            $this->error(L('Recharge order creation failed!'));
        }
    }


    public function outlog($status = NULL)
    {

        if (!userid()) {
            redirect(U('Login/login'));
        }

        $this->assign('prompt_text', D('Text')->get_content('finance_mytx'));


        if (($status == 1) || ($status == 2) || ($status == 3) || ($status == 4)) {
            $where['status'] = $status - 1;
        }
        $where['userid'] = userid();
        $count = M('Mytx')->where($where)->count();
        $Page = new Page($count, 15);
        $show = $Page->show();
        $list = M('Mytx')->where($where)->order('id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();

        foreach ($list as $k => $v) {
            $list[$k]['num'] = (Num($v['num']) ? Num($v['num']) : '');
            $list[$k]['fee'] = (Num($v['fee']) ? Num($v['fee']) : '');
            $list[$k]['mum'] = (Num($v['mum']) ? Num($v['mum']) : '');
        }
        $this->assign('status', $status);
        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->display();

    }


    public function mytx($coin = DEFAULT_FIAT, $status = NULL)
    {
        if (FIAT_ALLOWED == 0) {
            die('Unauthorized!');
        }
        $fiatcoins = [];
        $userid=userid();
        if (!$userid) {
            redirect(U('Login/login'));
        }

        $this->assign('prompt_text', D('Text')->get_content('finance_mytx'));
        $cellphone = $this->userinfo['cellphone'];//M('User')->where(array('id' => userid()))->getField('cellphone');
        $email = $this->userinfo['email'];//M('User')->where(array('id' => userid()))->getField('email');

        if ($cellphone || $email) {
            $cellphone = substr_replace($cellphone, '****', 3, 4);
            $email = substr_replace($email, '****', 3, 4);
        } else {
            if (M_ONLY == 1) {
                redirect(U('Home/User/cellphone'), $time = 5, $msg = L('Please Verify your Phone!'));
            }
        }

        foreach (C('coin') as $coinlist) {
            if ($coinlist['type'] == 'rmb') {
                $_fiat_coin['name'] = $coinlist['name'];
                $_fiat_coin['img'] = $coinlist['img'];
                $fiatcoins[] = $_fiat_coin;
            }

        }

        if (C('coin')[$coin]['name'] != $coin || C('coin')[$coin]['type'] != 'rmb' || C('coin')[$coin]['status'] != 1) {
            //$this->error(L('Wrong Coin!'),U('Finance/mytx'));
            redirect(U('Finance/mytx/coin/usd'), $time = 1, $msg = L('Wrong Coin!'));

        }

        $coin_img = C('coin')[$coin]['img'];
        $this->assign('coin_img', $coin_img);
        $this->assign('coin', $coin);
        $this->assign('fiatcoins', $fiatcoins);
        $this->assign('cellphone', $cellphone);
        $this->assign('email', $email);

        $user_coin = M('UserCoin')->where(['userid' => $userid])->find();
        $user_coin['fiat'] = format_num($user_coin[$coin], 2);
        $user_coin['fiat_total'] = format_num($user_coin[$coin . 'd'], 2);
        $this->assign('user_coin', $user_coin);

        $userBankList = M('UserBank')->where(['userid' => userid(), 'status' => 1])->order('id desc')->limit(10)->select();
        $this->assign('userBankList', $userBankList);

        $this->assign('withdrawal_fee_percent', C('coin')[$coin]['zc_fee']);
        $this->assign('withdrawal_fee_flat', C('coin')[$coin]['zc_flat_fee']);
        $where=[];
        if (($status == 1) || ($status == 2) || ($status == 3) || ($status == 4)) {
            $where['status'] = $status - 1;
        }

        $this->assign('status', $status);
        $where['userid'] = $userid;

        $count = M('Mytx')->where($where)->count();
        $Page = new Page($count, 15);
        $show = $Page->show();
        $out_list = M('Mytx')->where($where)->order('id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();

//
//        foreach ($list as $k => $v) {
//            $list[$k]['num'] = (Num($v['num']) ? Num($v['num']) : '');
//            $list[$k]['fee'] = (Num($v['fee']) ? Num($v['fee']) : '');
//            $list[$k]['mum'] = (Num($v['mum']) ? Num($v['mum']) : '');
//        }

        $this->assign('out_list', $out_list);
        $this->assign('page', $show);

        $this->display();
    }

    public function mytxUp($num, $paypassword, $type, $coinname, $cellphone_verify = 0)
    {
        if (FIAT_ALLOWED == 0) {
            die('Unauthorized!');
        }
        if (!userid()) {
            $this->error(L('PLEASE_LOGIN'));
        }
        if (!check($coinname, 'n')) {
            $this->error(L('Currency format error!'));
        }

        if (!C('coin')[$coinname]) {
            $this->error(L('Currency wrong!'));
        }

        $Coin = C('coin')[$coinname];// M('Coin')->where(array('name' => $coinname))->find();

        if (!$Coin) {
            $this->error(L('Currency wrong!'));
        }
        $coin_name = strtolower($coinname);
        $coin_named = strtolower($coinname) . 'd';

        if (M_ONLY == 1) {
            if (!check($cellphone_verify, 'd')) {
                $this->error(L('INVALID_SMS_CODE'));
            }
        }

        if (!check($num, 'd')) {
            $this->error(L('The amount of withdrawals format error!'));
        }

        if (!check($paypassword, 'password')) {
            $this->error(L('Fund Pwd format error!'));
        }

        if (!check($type, 'd')) {
            $this->error(L('Withdraw way malformed!'));
        }
        if (M_ONLY == 1) {
            if ($cellphone_verify != session('mytx_verify')) {
                $this->error(L('INCORRECT_SMS_CODE'));
            }
        }
        $userCoin = M('UserCoin')->where(['userid' => userid()])->find();

        if ($userCoin[$coin_name] < $num) {
            $this->error(L('Lack of available Balance!'));
        }

        $user = $this->userinfo;//M('User')->where(array('id' => userid()))->find();

        if (md5($paypassword) != $user['paypassword']) {
            $this->error(L('Trading password is wrong!'));
        }

        $userBank = M('UserBank')->where(['id' => $type])->find();

        if (!$userBank) {
            $this->error(L('Withdraw wrong address!'));
        }
        $mytx_bei = C('mytx_bei'); //multiple of
        $mytx_min = C('coin')[$coin_name]['zc_min'] ?: 1;
        $mytx_max = C('coin')[$coin_name]['zc_max'] ?: 1000000;

        $mytx_fee_percent = C('coin')[$coin_name]['zc_fee'] ?: 0;
        $mytx_fee_flat = C('coin')[$coin_name]['zc_flat_fee'] ?: 0;

        if ($num < $mytx_min) {
            $this->error(L('Every withdrawal amount can not be less than') . $mytx_min);
        }

        if ($mytx_max < $num) {
            $this->error(L('Every withdrawal amount can not exceed') . $mytx_max);
        }

        if ($mytx_bei) {
            if ($num % $mytx_bei != 0) {
                $this->error(L('Every mention the amount of cash must be') . $mytx_bei . L('Integral multiples!'));
            }
        }
        $truename = $userBank['truename'] ?: $user['truename'];
        $percent_fee = bcmul(bcdiv($num, 100, 8), $mytx_fee_percent, 2);
        $flat_fee = $mytx_fee_flat;
        $remaining = bcsub($num, $flat_fee, 2);
        //$fees_total=bcadd($percent_fee,$flat_fee,8);
        $fee = bcmul(bcdiv($remaining, 100, 8), $percent_fee, 2);
        $fees_total = bcadd($fee, $flat_fee, 8);
        $mum = bcsub($remaining, $fee, 2);
        $mo = M();

        $mo->startTrans();
        $rs = [];
        $finance = $mo->table('codono_finance')->where(['userid' => userid()])->order('id desc')->find();
        $finance_num_user_coin = $mo->table('codono_user_coin')->where(['userid' => userid()])->find();
        $rs[] = $mo->table('codono_user_coin')->where(['userid' => userid()])->setDec($coin_name, $num);
        $rs[] = $finance_nameid = $mo->table('codono_mytx')->add(['userid' => userid(), 'num' => $num, 'fee' => $fees_total, 'mum' => $mum, 'name' => $userBank['name'], 'truename' => $truename, 'bank' => $userBank['bank'], 'bankprov' => $userBank['bankprov'], 'bankcity' => $userBank['bankcity'], 'bankaddr' => $userBank['bankaddr'], 'bankcard' => $userBank['bankcard'], 'addtime' => time(), 'status' => 0, 'coin' => $coin_name]);
        $finance_mum_user_coin = $mo->table('codono_user_coin')->where(['userid' => userid()])->find();
        $finance_hash = md5(userid() . $finance_num_user_coin[$coin_name] . $finance_num_user_coin[$coin_named] . $mum . $finance_mum_user_coin[$coin_name] . $finance_mum_user_coin[$coin_named] . CODONOLIC . 'auth.codono.com');
        $finance_num = $finance_num_user_coin[$coin_name] + $finance_num_user_coin[$coin_named];

        if ($finance['mum'] < $finance_num) {
            $finance_status = (1 < ($finance_num - $finance['mum']) ? 0 : 1);
        } else {
            $finance_status = (1 < ($finance['mum'] - $finance_num) ? 0 : 1);
        }

        $rs[] = $mo->table('codono_finance')->add(['userid' => userid(), 'coinname' => $coin_name, 'num_a' => $finance_num_user_coin[$coin_name], 'num_b' => $finance_num_user_coin[$coin_named], 'num' => $finance_num_user_coin[$coin_name] + $finance_num_user_coin[$coin_named], 'fee' => $num, 'type' => 2, 'name' => 'mytx', 'nameid' => $finance_nameid, 'remark' => 'Fiat withdrawal', 'mum_a' => $finance_mum_user_coin[$coin_name], 'mum_b' => $finance_mum_user_coin[$coin_named], 'mum' => $finance_mum_user_coin[$coin_name] + $finance_mum_user_coin[$coin_named], 'move' => $finance_hash, 'addtime' => time(), 'status' => $finance_status]);

        if (check_arr($rs)) {
            session('mytx_verify', null);
            $mo->commit();

            $this->success(L('Withdrawal order to create success!'));
        } else {
            $mo->rollback();
            $this->error(L('Withdraw order creation failed!'));
        }
    }

    public function mytxReject($id)
    {

        if (FIAT_ALLOWED == 0) {
            die('Unauthorized!');
        }
        if (!userid()) {
            $this->error(L('PLEASE_LOGIN'));
        }

        if (!check($id, 'd')) {
            $this->error(L('INCORRECT_REQ'));
        }

        $mytx = M('Mytx')->where(['id' => $id, 'userid' => userid()])->find();

        if (!$mytx) {
            $this->error(L('Withdraw order does not exist!'));
        }

        if ($mytx['userid'] != userid()) {
            $this->error(L('Illegal operation!'));
        }

        if ($mytx['status'] != 0) {
            $this->error(L('Orders can not be undone!'));
        }

        $mo = M();

        $mo->startTrans();
        $rs = [];
        $fiat = strtolower($mytx['coin']);
        $fiatd = strtolower($mytx['coin']) . 'd';
        $finance = $mo->table('codono_finance')->where(['userid' => $mytx['userid']])->order('id desc')->find();
        $finance_num_user_coin = $mo->table('codono_user_coin')->where(['userid' => $mytx['userid']])->find();
        $rs[] = $mo->table('codono_user_coin')->where(['userid' => $mytx['userid']])->setInc($fiat, $mytx['num']);
        $rs[] = $mo->table('codono_mytx')->where(['id' => $mytx['id']])->setField('status', 2);
        $finance_mum_user_coin = $mo->table('codono_user_coin')->where(['userid' => $mytx['userid']])->find();
        $finance_hash = md5($mytx['userid'] . $finance_num_user_coin[$fiat] . $finance_num_user_coin[$fiatd] . $mytx['num'] . $finance_mum_user_coin[$fiat] . $finance_mum_user_coin[$fiatd] . CODONOLIC . 'auth.codono.com');
        $finance_num = $finance_num_user_coin[$fiat] + $finance_num_user_coin[$fiatd];

        if ($finance['mum'] < $finance_num) {
            $finance_status = (1 < ($finance_num - $finance['mum']) ? 0 : 1);
        } else {
            $finance_status = (1 < ($finance['mum'] - $finance_num) ? 0 : 1);
        }

        $rs[] = $mo->table('codono_finance')->add(['userid' => $mytx['userid'], 'coinname' => $fiat, 'num_a' => $finance_num_user_coin[$fiat], 'num_b' => $finance_num_user_coin[$fiatd], 'num' => $finance_num_user_coin[$fiat] + $finance_num_user_coin[$fiatd], 'fee' => $mytx['num'], 'type' => 1, 'name' => 'mytx', 'nameid' => $mytx['id'], 'remark' => 'Fiat Withdrawal-Undo withdrawals', 'mum_a' => $finance_mum_user_coin[$fiat], 'mum_b' => $finance_mum_user_coin[$fiatd], 'mum' => $finance_mum_user_coin[$fiat] + $finance_mum_user_coin[$fiatd], 'move' => $finance_hash, 'addtime' => time(), 'status' => $finance_status]);

        if (check_arr($rs)) {
            $mo->commit();

            $this->success(L('SUCCESSFULLY_DONE'));
        } else {
            $mo->rollback();
            $this->error(L('OPERATION_FAILED'));
        }
    }


    public function myczReject($id)
    {

        if (FIAT_ALLOWED == 0) {
            die('Unauthorized!');
        }
        if (!userid()) {
            $this->error(L('PLEASE_LOGIN'));
        }

        if (!check($id, 'd')) {
            $this->error(L('INCORRECT_REQ'));
        }

        $mycz = M('Mycz')->where(['id' => $id, 'userid' => userid()])->find();

        if (!$mycz) {
            $this->error(L('Withdraw order does not exist!'));
        }

        if ($mycz['userid'] != userid()) {
            $this->error(L('Illegal operation!'));
        }

        if ($mycz['status'] != 0 && $mycz['status'] != 3) {
            $this->error(L('Order cant be cancelled!') . $mycz['status']);
        }

        $mo = M();

        $mo->startTrans();
        $rs = [];
        $rs[] = $mo->table('codono_mycz')->where(['id' => $mycz['id']])->setField('status', 5);
        if (check_arr($rs)) {
            $mo->commit();

            $this->success(L('SUCCESSFULLY_DONE'));
        } else {
            $mo->rollback();
            $this->error(L('OPERATION_FAILED'));
        }
    }

    /**
     * @throws Exception
     */
    public function myzr($coin = NULL)
    {

        $uid = userid();
        if (!$uid) {
            redirect(U('Login/login'));
        }
        redirect(U('Wallet/cryptodeposit', ['coin' => $coin]));
    }


    public function addnew($coin = NULL)
    {
        if (!userid()) {
            redirect(U('Login/login'));
        }
        $coin_list = [];
        $Coins = M('Coin')->where([
            'status' => 1,
            'type' => ['neq', 'rmb']
        ])->select();

        if (!$coin) {
            $coin = '';
        }

        $this->assign('xnb', $coin);

        foreach ($Coins as $k => $v) {
            $coin_list[$v['name']] = $v;
        }

        $this->assign('coin_list', $coin_list);

        $where['userid'] = userid();
        $where['status'] = 1;
        if (!empty($coin)) {
            $where['coinname'] = $coin;
        }


        $count = M('UserWallet')->where($where)->count();
        $Page = new Page($count, 6);
        $show = $Page->show();

        $userWalletList = M('UserWallet')->where($where)->order('id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();

        $this->assign('page', $show);
        $this->assign('userWalletList', $userWalletList);
        $this->assign('prompt_text', D('Text')->get_content('user_wallet'));
        $this->display();
    }

    public function upwallet($coin, $name, $addr, $paypassword)
    {
        if (!userid()) {
            redirect(U('Login/login'));
        }

        if (!check($name, 'a')) {
            $this->error(L('Note the name of the wrong format!'));
        }

        if (!check($addr, 'dw')) {
            $this->error(L('Wallet address format error!'));
        }

        if (!check($paypassword, 'password')) {
            $this->error(L('Fund Pwd format error!'));
        }

        $user_paypassword = $this->userinfo['paypassword'];//M('User')->where(array('id' => userid()))->getField('paypassword');

        if (md5($paypassword) != $user_paypassword) {
            $this->error(L('Trading password is wrong!'));
        }

        if (!M('Coin')->where(['name' => $coin])->find()) {
            $this->error(L('Currency wrong!'));
        }

        $userWallet = M('UserWallet')->where(['userid' => userid(), 'coinname' => $coin])->select();

        foreach ($userWallet as $k => $v) {
            if ($v['name'] == $name) {
                $this->error(L('Please do not use the same wallet logo!'));
            }

            if ($v['addr'] == $addr) {
                $this->error(L('Wallet address already exists!'));
            }
        }

        if (10 <= count($userWallet)) {
            $this->error('You are allowed to add till 10 addresses!');
        }

        if (M('UserWallet')->add(['userid' => userid(), 'name' => $name, 'addr' => $addr, 'coinname' => $coin, 'addtime' => time(), 'status' => 1])) {
            $this->success(L('ADDED_SUCCESSFULLY'));
        } else {
            $this->error(L('FAILED_TO_ADD'));
        }
    }

    public function delwallet($id, $paypassword)
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

        if (!M('UserWallet')->where(['userid' => userid(), 'id' => $id])->find()) {
            $this->error(L('Unauthorized access!'));
        } else if (M('UserWallet')->where(['userid' => userid(), 'id' => $id])->delete()) {
            $this->success(L('successfully deleted!'));
        } else {
            $this->error(L('failed to delete!'));
        }
    }


    public function coinoutLog($coin = NULL)
    {
        $this->page_title = l('Withdrawal History');

        if (!userid()) {
            redirect(U('Login/login'));
        }
        $coin_list = [];
        $this->assign('prompt_text', D('Text')->get_content('finance_myzc'));
        $explorer = '';
        $coin = trim($coin);
        if (isset(C('coin')[$coin]) && isset($coin)) {
            $explorer = C('coin')[$coin]['js_wk'];

        }
        $this->assign('xnb', $coin);
        $CoinInfo = C('coin');

        foreach ($CoinInfo as $k => $v) {
            if ($v['type'] != 'rmb') {
                $coin_list[$v['name']] = $v;
            }

        }

        $this->assign('coin_list', $coin_list);

        $where['userid'] = userid();
        if (isset($coin) && $coin != '') {
            $where['coinname'] = $coin;
        }

        $Model = M('Myzc');
        $count = $Model->where($where)->count();
        $Page = new Page($count, 10);
        $show = $Page->show();
        $list = $Model->where($where)->order('id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $this->assign('explorer', $explorer);
        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->display();
    }

    public function requestOTP($coinname, $address, $amount)
    {
        if (!userid()) {
            $this->error(L('Please login first!'));
        }
        if (!kyced()) {
            $this->error(L('Complete KYC First!'));
        }
        $user_coin = $this->usercoins;// M('UserCoin')->where(array('userid' => userid()))->find();
        $min_withdrawal_allow = C('coin')[$coinname]['zc_min'];
        $max_withdrawal_allow = C('coin')[$coinname]['zc_max'];

        if ($user_coin[$coinname] < $amount) {
            $this->error("Insufficient funds:  $user_coin[$coinname] $coinname");
        }

        if ($amount < $min_withdrawal_allow || $amount <= 0) {
            if ($min_withdrawal_allow <= 0) {
                $min_error_message = 'Please try higher amount for withdrawal';
            } else {
                $min_error_message = "Minimum withdrawal amount $min_withdrawal_allow $coinname";
            }
            $this->error($min_error_message);
        }

        if ($amount > $max_withdrawal_allow) {
            if ($max_withdrawal_allow < 0) {
                $max_error_message = 'Please try lower amount for withdrawal';
            } else {
                $max_error_message = "Max withdrawal amount $min_withdrawal_allow $coinname";
            }
            $this->error($max_error_message);
        }


        $user = $this->userinfo;
        $code = tradeno();

        session('requestOTP', $code);
        $email = $user['email'];
        $client_ip = get_client_ip();
        $requestTime = date('Y-m-d H:i', time()) . '(' . date_default_timezone_get() . ')';
        $subject = 'Withdrawal Request on ' . SHORT_NAME;
        $content = "<br/><strong>DO NOT SHARE THIS CODE WITH ANYONE!!</strong><br/>To complete the withdrawal process,<br/><br/>
        You may be asked to enter this confirmation code:<br/>
        <span style='font-size: 24px; font-weight: bold; background-color: #000;color: #fff; padding: 0px;'>$code</span><br/><br/>
        <small><i>
        <table>
        <tr style='border:2px solid black'><td>Email</td><td>$email</td></tr>
        <tr style='border:2px solid black'><td>IP</td><td>$client_ip</td></tr>
        <tr style='border:2px solid black'><td>Coin</td><td>$coinname</td></tr>
        <tr style='border:2px solid black'><td>Amount</td><td>$amount</td></tr>
        <tr style='border:2px solid black'><td>Address</td><td>$address</td></tr>
        <tr style='border:2px solid black'><td>Time</td><td>$requestTime</td></tr>	
        </table>
        <strong>If You didn't request this withdrawal, immediately change passwords, and contact us</strong>";
        addnotification($email, $subject, $content);
        $this->success(L('Please check email for code'));
    }


    public function myzc($coin = NULL)
    {

        if (!userid()) {
            redirect(U('Login/login'));
        }
        redirect(U('Wallet/cryptowithdrawal', ['coin' => $coin]));

    }

    public function mywt()

    {
        redirect(U('Finance/orders'));
        $input = I('get.');
        $coin_list=$market_list=[];
        $market = isset($input['market']) ? htmlspecialchars(trim($input['market'])) : NULL;
        $type = isset($input['type']) ? intval($input['type']) : NULL;
        $status = isset($input['status']) ? intval($input['status']) : NULL;


        if (!userid()) {
            redirect(U('Login/login'));
        }
        if (TRADING_ALLOWED == 0) {
            die('Unauthorized!');
        }

        $this->assign('prompt_text', D('Text')->get_content('finance_mywt'));
        check_server();
        $Coin = C('Coin');

        foreach ($Coin as $k => $v) {
            $coin_list[$v['name']] = $v;
        }

        $this->assign('coin_list', $coin_list);
        $Market = M('Market')->where(['status' => 1])->select();

        foreach ($Market as $k => $v) {
            $v['xnb'] = explode('_', $v['name'])[0];
            $v['rmb'] = explode('_', $v['name'])[1];
            $market_list[$v['name']] = $v;
        }

        $this->assign('market_list', $market_list);

        if (!isset($market_list[$market])) {
            $market = $Market[0]['name'];
        }

        $where['market'] = $market;

        if (($type == 1) || ($type == 2)) {
            $where['type'] = $type;
        }

        if (($status == 1) || ($status == 2) || ($status == 3)) {
            $where['status'] = $status - 1;
        }

        $where['userid'] = userid();
        $this->assign('market', $market);
        $this->assign('type', $type);
        $this->assign('status', $status);
        $Model = M('Trade');
        $count = $Model->where($where)->count();
        $Page = new Page($count, 10);
        //$Page->parameter .= 'type=' . $type . '&status=' . $status . '&market=' . $market . '&';
        $show = $Page->show();

        $list = $Model->where($where)->order('id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();

        foreach ($list as $k => $v) {
            $list[$k]['num'] = $v['num'] * 1;
            $list[$k]['price'] = $v['price'] * 1;
            $list[$k]['deal'] = $v['deal'] * 1;
        }

        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->display();
    }

    public function orders()
{
    $input = I('get.');
    $coin_list = $market_list = [];
    $market = isset($input['market']) ? htmlspecialchars(trim($input['market'])) : null;
    $type = isset($input['type']) ? intval($input['type']) : null;
    $status = isset($input['status']) ? intval($input['status']) : null;

    if (!userid()) {
        redirect(U('Login/login'));
    }
    if (TRADING_ALLOWED == 0) {
        die('Unauthorized!');
    }

    $this->assign('prompt_text', D('Text')->get_content('finance_mywt'));
    check_server();
    $Coin = C('Coin');

    foreach ($Coin as $k => $v) {
        $coin_list[$v['name']] = $v;
    }

    $this->assign('coin_list', $coin_list);
    $Market = M('Market')->where(['status' => 1])->select();

    foreach ($Market as $k => $v) {
        $v['xnb'] = explode('_', $v['name'])[0];
        $v['rmb'] = explode('_', $v['name'])[1];
        $market_list[$v['name']] = $v;
    }

    $this->assign('market_list', $market_list);

    // Here we change the logic to omit market condition if $market is not in $market_list
    if (isset($market_list[$market])) {
        $where['market'] = $market;
    }

    if (($type == 1) || ($type == 2)) {
        $where['type'] = $type;
    }

    if (($status == 1) || ($status == 2) || ($status == 3)) {
        $where['status'] = $status - 1;
    }

    $where['userid'] = userid();
    $this->assign('market', $market);
    $this->assign('type', $type);
    $this->assign('status', $status);
    $Model = M('Trade');
    $count = $Model->where($where)->count();
    $Page = new Page($count, 10);
    //$Page->parameter .= 'type=' . $type . '&status=' . $status . '&market=' . $market . '&';
    $show = $Page->show();

    $list = $Model->where($where)->order('id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();

    foreach ($list as $k => $v) {
        $list[$k]['num'] = $v['num'] * 1;
        $list[$k]['price'] = $v['price'] * 1;
        $list[$k]['deal'] = $v['deal'] * 1;
    }

    $this->assign('list', $list);
    $this->assign('page', $show);
    $this->display();
}


    public function mycj($market = NULL, $type = NULL)
    {
        $uid=userid();
        if (!$uid) {
            redirect(U('Login/login'));
        }
        $coin_list=$market_list=[];

        $this->assign('prompt_text', D('Text')->get_content('finance_mycj'));
        //check_server();
        $Coins = C('coin_safe');//M('Coin')->where(array('status' => 1))->select();

        foreach ($Coins as $k => $v) {
            $coin_list[$v['name']] = $v;
        }

        $this->assign('coin_list', $coin_list);
        $Markets = M('Market')->where(['status' => 1])->select();

        foreach ($Markets as $k => $v) {
            $v['xnb'] = explode('_', $v['name'])[0];
            $v['rmb'] = explode('_', $v['name'])[1];
            $market_list[$v['name']] = $v;
        }

        $this->assign('market_list', $market_list);

        if (!$market_list[$market]) {
            $market = $Markets[0]['name'];
        }

        if ($type == 1) {
            $where = 'userid=' . $uid . ' && market=\'' . $market . '\'';
        } else if ($type == 2) {
            $where = 'peerid=' . $uid . ' && market=\'' . $market . '\'';
        } else {
            $where = '((userid=' . $uid . ') || (peerid=' . $uid . ')) && market=\'' . $market . '\'';
        }

        $this->assign('market', $market);
        $this->assign('type', $type);
        $this->assign('userid', $uid);
        $Model = M('TradeLog');
        $count = $Model->where($where)->count();
        $Page = new Page($count, 15);
        $Page->parameter .= 'type=' . $type . '&market=' . $market . '&';
        $show = $Page->show();
        $list = $Model->where($where)->order('id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();

        foreach ($list as $k => $v) {
            $list[$k]['num'] = $v['num'] * 1;
            $list[$k]['price'] = $v['price'] * 1;
            $list[$k]['mum'] = $v['mum'] * 1;
            $list[$k]['fee_buy'] = $v['fee_buy'] * 1;
            $list[$k]['fee_sell'] = $v['fee_sell'] * 1;
        }

        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->display();
    }

    public function mytj()
    {
        if (!userid()) {
            redirect(U('Login/login'));
        }
        $this->title = D('Text')->get_title('finance_mytj');

        $uid = userid();

        $this->assign('prompt_text', D('Text')->get_content('finance_mytj'));
        $userinfo=$this->userinfo;
        
        $user['invit'] = $userinfo['invit'];//M('User')->where(array('id' => userid()))->find();
        
        if (!$user['invit']) {
            for (; true;) {
                $tradeno = tradenoa();

                if (!M('User')->where(['invit' => $tradeno])->find()) {
                    break;
                }
            }

            M('User')->where(['id' => $uid])->save(['invit' => $tradeno]);
            $user['invit'] = M('User')->where(array('id' => userid()))->find();
        }

        $stats['invit_1'] = M('User')->where(['invit_1' => $uid])->count();
        $stats['invit_2'] = M('User')->where(['invit_2' => $uid])->count();
        $stats['invit_3'] = M('User')->where(['invit_3' => $uid])->count();
        $stats['indirect'] = $stats['invit_2'] + $stats['invit_3'];
        $where['invit'] = $uid;
        $where['fee'] = ['gt', 0];
        $Model = M('Invit');
        $income_records = $Model->field(['sum(fee)' => 'total', 'coin'])->where($where)->group('coin')->order('total desc')->limit(10)->select();
        $query= $Model->getLastSql();
        
        $converted_list = [];
        foreach ($income_records as $income_record) {
            $converted_list[] = $this->getConversion($income_record['coin'], $income_record['total'], SYSTEMCURRENCY);
        }
        $stats['earning']['total'] = array_sum($converted_list);
        $stats['earning']['coin'] = SYSTEMCURRENCY;
      
        
        $count = $Model->where($where)->count();
        $Page = new Page($count, 15);
        $show = $Page->show();
        $where2['invit'] = $uid;
        $where2['fee'] = ['gt', 0];
        $list = $Model->where($where2)->order('id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();
        
        $this->assign('is_ref_award', C('ref_award'));
        $this->assign('ref_award_num', format_num(C('ref_award_num'), 2));
        $this->assign('ref_award_coin', strtoupper(C('ref_award_coin')));

        $this->assign('stats', $stats);
        $this->assign('records', $list);
        $this->assign('user', $user);
        $this->assign('page', $show);
        $this->display();
    }

    private function getConversion($fromcoin = 'USD', $amount = 1, $tocoin = 'USD')
    {
        $mux = 1; //Multiplier for conversion
        $fromcoin = strtoupper($fromcoin);
        $tocoin = strtoupper($tocoin);
        if ($tocoin != 'USD') {
            //Grab price from tocoin to USD

            $conv = $this->getConversion($tocoin);
            $mux = bcdiv(1, $conv, 8);

        } else {
            $mux = 1;
        }
        $price = M('Coinmarketcap')->where(['symbol' => $fromcoin])->field('price_usd')->find();
        if ($price) {
            $price = $price['price_usd'];
        } else {
            $price = null;
        }

        $new_amount = bcmul($price, $amount, 8);
        return bcmul($mux, $new_amount, 8);
    }

    public function referrals()
    {
        if (!userid()) {
            redirect(U('Login/login'));
        }

        $this->assign('prompt_text', D('Text')->get_content('finance_mywd'));
        check_server();
        $where['invit_1'] = userid();
        $Model = M('User');
        $count = $Model->where($where)->count();
        $Page = new Page($count, 10);
        $show = $Page->show();
        $list = $Model->where($where)->order('id asc')->field('id,username,idcardauth,addtime,invit_1')->limit($Page->firstRow . ',' . $Page->listRows)->select();


        foreach ($list as $k => $v) {
            $list[$k]['invits'] = M('User')->where(['invit_1' => $v['id']])->order('id asc')->field('id,username,idcardauth,addtime,invit_1')->select();
            $list[$k]['invitss'] = count($list[$k]['invits']);

            foreach ($list[$k]['invits'] as $kk => $vv) {
                $list[$k]['invits'][$kk]['invits'] = M('User')->where(['invit_1' => $vv['id']])->order('id asc')->field('id,username,idcardauth,addtime,invit_1')->select();
                $list[$k]['invits'][$kk]['invitss'] = count($list[$k]['invits'][$kk]['invits']);
            }
        }

        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->display();
    }

    public function myjp()
    {
        if (!userid()) {
            redirect(U('Login/login'));
        }

        $this->assign('prompt_text', D('Text')->get_content('finance_myjp'));
        check_server();
        $where['invit'] = userid();
        $Model = M('Invit');
        $count = $Model->where($where)->count();
        $Page = new Page($count, 10);
        $show = $Page->show();
        $list = $Model->where($where)->order('id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();

        foreach ($list as $k => $v) {
            $list[$k]['invit'] = M('User')->where(['id' => $v['invit']])->getField('username');
        }

        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->display();
    }

    public function myaward()
    {
        if (!userid()) {
            redirect(U('Login/login'));
        }

        $this->assign('prompt_text', D('Text')->get_content('finance_myaward'));
        //check_server();
        $where['userid'] = userid();
        $Model = M('UserAward');
        $count = $Model->where($where)->count();
        $Page = new Page($count, 10);
        $show = $Page->show();
        $list = $Model->where($where)->order('id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();

        foreach ($list as $k => $v) {
            $list[$k]['username'] = M('User')->where(['id' => $v['userid']])->getField('username');
        }

        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->display();
    }

    public function withdrawMobile()
    {
        if (!userid()) {
            redirect(U('Login/login'));
        }
        $userCoin = M('UserCoin')->where(['userid' => userid()])->find();
        $this->assign('balance', $userCoin['ugx']);
        $this->display();
    }

    public function trace()
    {
        if (!userid()) {
            redirect(U('Login/login'));
        }
        $func='';
        $Coins = C('coin');
        foreach ($Coins as $k => $v) {
            if (($v['type'] == 'esmart' || $v['type'] == 'blockgum') && $v['dj_yh'] == '') {
                $coin_list[$v['name']] = ['type' => $v['type'], 'name' => $v['name'], 'title' => $v['title'], 'network' => $v['js_yw']];
            }
        }


        ksort($coin_list);
        $this->assign('coin_list', $coin_list);
        $this->display();
    }
    private function findTokenByContract($contact_address): array
    {
        $resp = [];
        $coins = C('Coin');
        foreach ($coins as $coin) {
            if ($coin['dj_yh'] == $contact_address) {
                $resp['name'] = $coin['name'];
                $resp['cs_qk'] = $coin['cs_qk'];
            }
        }
        return $resp;
    }
    private function trackTx($txid, $chain)
    {
        $func=$coin='';
        if (!preg_match('/^(0x)?[\da-f]{64}$/i', $txid)) {
            $this->error('Invalid txid');
        }

        $isValidCoin = $this->isValidCoin($chain);
        if ($chain == null || !$isValidCoin) {
            $this->error('Invalid coin');
        }

        $amount = 0;
        $token_address_found = 0;
        $all_coinsList = C('coin');
        $tokens_list = $coinList = [];
        if (C('coin')[$chain]['type'] == 'blockgum') {
            $blockgum = blockgum($chain);
            $history = $blockgum->traceDeposit($txid);
            if ($history && $history['status'] == 1) {

                $found = null;
                //find internally
                $found = M('Myzr')->where(['txid' => $txid])->find();
                if (!$found) {
                    $found = $blockgum->markAsUnrecorded($txid);
                }
                $this->success('Your request for trace has been added, Server Resp:' . $history['message']);
            } else {
                clog('blockgum_' . $chain, $history);
                $this->error('Auto trace would not detect such deposit, Please contact support');
            }

        }
        foreach ($all_coinsList as $coinex) {
            if ($coinex['type'] == 'esmart' && $coinex['status'] == 1) {
                $coinList[] = $coinex;
                if ($coinex['tokenof'] == $chain && $coinex['dj_yh'] != '') {
                    $tokens_list[] = strtolower($coinex['dj_yh']);
                }

            }
        }


        $coin_esmart = C('coin')[$chain];

        $esmart_details = C('coin')[$coin_esmart['name']];

        $block = M()->query("SELECT * FROM `codono_coin` WHERE `name`='" . $coin_esmart['name'] . "'");

        $esmart_details = $block[0];

        $pcoin = $esmart_details['tokenof'] ?: $esmart_details['name'];

        $pcoinb = $pcoin . 'b';

        // Saving Userid's and their addresses  $pcoinb_array

        $coin_Query = 'SELECT count(' . $pcoinb . ') as count FROM `codono_user_coin` WHERE `' . $pcoinb . '` is not null';
        $pcoinb_count = M()->query($coin_Query)[0]['count'];
        $coin_Query = 'SELECT ' . $pcoinb . ' as addr , userid  FROM `codono_user_coin` WHERE `' . $pcoinb . '` is not null';
        $pcoinb_array = M()->query($coin_Query);


        $main_account = $esmart_details['codono_coinaddress'];

        $esmart_config = [
            'host' => $esmart_details['dj_zj'],
            'port' => $esmart_details['dj_dk'],
            'coinbase' => $esmart_details['codono_coinaddress'],
            'password' => cryptString($esmart_details['dj_mm'], 'd'),
            'contract' => $esmart_details['dj_yh'],
            'rpc_type' => $esmart_details['rpc_type'],
            'public_rpc' => $esmart_details['public_rpc'],
        ];
        $Esmart = Esmart($esmart_config);


        $decimals = $esmart_details['cs_qk'];


        $time_start = microtime(true);
        $infoTx = $Esmart->eth_getTransactionByHash($txid);

        $listtransactions = [$infoTx];

        foreach ($listtransactions as $ks => $trans) {

            if (isset($trans['from']) && strtolower($trans['from']) == strtolower($main_account)) {
                $this->error('This was a gas transfer from our exchange account only');
                continue;
            }

            if (!isset($trans['to'])) {
                $this->error('No payee');
                continue;//No payee.
            }

            if ($trans['value'] == '0x0' || $trans['value'] == '0x' || $trans['value'] == '0') {
                if (!$trans['input']) {
                    $this->error('Invalid tx');
                    continue;
                }

                //Find if its a token if input != 0x then its a token


                if ($trans['input'] != '0x') {
                    $func = '0x' . substr($trans['input'], 2, 8);
                    if (!in_array(strtolower($trans['to']), $tokens_list) && $func != '0x2228f3a4') {
                        continue;
                    }

                    $to_num = substr($trans['input'], 74);//Quantity
                    $tos = substr($trans['input'], 34, 40);//Reciever

                    if (!$tos) {
                        $this->error('No such sender');
                        continue;
                    }

                    $tos = '0x' . $tos;


                    $num = $Esmart->fromWei($to_num);

                } else {
                    //This is for ethereum it self
                    $tos = $trans['to'];
                    $num = $trans['value'];

                }
                if ($func == '0x2228f3a4') {
                    $batch_decodes = $Esmart->payoutERC20Batch_decode($trans['input']);

                    foreach ($batch_decodes as $batch_decode) {
                        //check if erc20 contract is listed on exchange or not
                        if (!in_array(strtolower($batch_decode['contract']), $tokens_list)) {
                            continue;
                        }
                        $batch_address = $batch_decode['address'];
                        $coin_Query = 'SELECT userid FROM `codono_user_coin` WHERE `' . $pcoinb . "` LIKE '" . $batch_address . "'";
                        $users = M()->query($coin_Query);
                        if (!$users) continue;
                        $batch_uid = $users[0]['userid'];
                        $batch_contract_info = $this->findTokenByContract($batch_decode['contract']);
                        $batch_decimal = $batch_contract_info['cs_qk'];
                        $batch_coin = $batch_contract_info['name'];
                        $batch_amount = hexdec($batch_decode['bal_hex']) / bcpow(10, $batch_decimal);
                        //Already recorded
                        if (M('Myzr')->where(['txid' => $trans['hash']])->find()) {
                            echo $batch_address . '=>' . $batch_amount . '=>tx for ' . $batch_coin . ' already credited Checking Next' . '<br>';
                            continue;
                        }
                        $data = ['userid' => $batch_uid, 'amount' => $batch_amount, 'coin' => $batch_coin, 'address' => $batch_address, 'hash' => $trans['hash']];
                        $b_info = D('Coin')->depositCoin($data);
                    }
                    continue;
                }
                if (count($listtransactions) < $pcoinb_count) {

                    $coin_Query = 'SELECT userid as userid FROM `codono_user_coin` WHERE `' . $pcoinb . "` LIKE '" . $tos . "'";
                    $users = M()->query($coin_Query);

                    if (!$users) {
                        $this->error('No such user');
                        continue;
                    }

                    $user = $users[0];
                } else {

                    $user = 0;
                    foreach ($pcoinb_array as $pcoinb_user) {
                        if (isset($pcoinb_user['addr']) && $pcoinb_user['addr'] == $tos) {
                            $coin_Query = 'SELECT userid as userid FROM `codono_user_coin` WHERE `' . $pcoinb . "` LIKE '" . $tos . "'";
                            $users = M()->query($coin_Query);

                            if (!$users) {
                                $this->error('No such user [2]');
                                continue;
                            }

                            $user = $users[0];

                            //$user=$pcoinb_user['userid'];


                        } else {
                            $this->error('We could not find it ');
                            continue;
                        }
                    }
                    if (!isset($user['userid'])) {
                        $this->error('no such user found ');
                        continue;
                    }
                }

                $hash_result = $Esmart->eth_getTransactionReceipt($trans['hash']);

                if ($hash_result['status'] != '0x1' && strtolower($hash_result['transactionHash']) != strtolower($trans['hash'])) {

                    $this->error($trans['hash'] . ' tx was failed or can not confirm it - Skipping it');
                    continue;
                }

                $func = '0x' . substr($trans['input'], 2, 8);

                $flag = false;
                if ($func == '0xa9059cbb') {
                    $token_address_found = $trans['to'];
                    $from = $trans['from'];
                    $to = '0x' . substr(substr($trans['input'], 10, 64), -40);

                    $coin_Query = "SELECT name,cs_qk FROM `codono_coin` WHERE `dj_yh` LIKE '%" . $token_address_found . "%'";

                    $coin_info = M()->query($coin_Query);
                    $decimals = $coin_info[0]['cs_qk'];
                    $amount = hexdec(substr($trans['input'], 74, 64)) / bcpow(10, $decimals);
                    $flag = true;

                } else if ($func == '0x23b872dd') {
                    $token_address_found = $trans['to'];
                    $from = '0x' . substr(substr($trans['input'], 10, 64), -40);
                    $to = '0x' . substr(substr($trans['input'], 74, 64), -40);
                    $amount = hexdec(substr($trans['input'], 138, 64));

                    $flag = true;

                }

                if ($flag) {
                    $coin_Query = "SELECT name,cs_qk FROM `codono_coin` WHERE `dj_yh` LIKE '%" . $token_address_found . "%'";
                    $coin_info = M()->query($coin_Query);
                    $coin = $coin_info[0]['name'];
                }
                if ($trans['input'] != '0x' && $coin == $pcoin) {
                    $this->error('Invalid tx [4] ');
                    continue;
                }

                if ($trans['input'] == '0x' && $coin != $pcoin) {
                    $this->error($pcoin . 'Invalid tx [3]' . $coin);
                    continue;
                }

                if ($trans['input'] != '0x' && $coin != $pcoin) {
                    $contract_Address_to_look = $trans['to'];
                    if ($token_address_found != $contract_Address_to_look) {
                        continue;
                    }
                    $token_query = "SELECT name,cs_qk as decimals FROM `codono_coin` WHERE `dj_yh` LIKE '%" . $contract_Address_to_look . "%'";

                    $resulto = M()->query($token_query);

                    if (!$resulto[0]['name']) {
                        $this->error('This token deposited is not registered on exchange');
                        continue;
                    }

                    $coin = $resulto[0]['name'];

                    $num = $amount;

                }
                if ($num <= 0 && $coin == $pcoin) {
                    $num = $Esmart->fromWei($trans['val']);
                }

                if ($num <= 0) continue;


                if (M('Myzr')->where(['txid' => $trans['hash']])->find()) {
                    //Already recorded
                    $this->error('Transaction was already deposited!');
                    continue;
                }

                $mo = M();
                $mo->startTrans();

                $num = format_num($num, 8);
                $coin = $this->findSymbol($coin);

                $rs[] = $mo->table('codono_myzr')->add(['userid' => $user['userid'], 'type' => 'esmart', 'username' => $tos, 'coinname' => $coin, 'fee' => 0, 'txid' => $trans['hash'], 'num' => $num, 'mum' => $num, 'addtime' => time(), 'status' => 1]);
                $rs[] = $mo->table('codono_user_coin')->where(['userid' => $user['userid']])->setInc($coin, $num);

                if (check_arr($rs)) {
                    $mo->commit();
                    //$this->deposit_notify($user['userid'], $tos, $coin, $trans['hash'], $num, time());

                    $this->success('Transaction was found and deposited');
                } else {
                    $mo->rollback();
                    $this->error('Deposit could not be processed');
                }


            } else {

                $tos = $trans['to'];
                if (count($listtransactions) < $pcoinb_count) {


                    $coin_Query = 'SELECT userid as userid FROM `codono_user_coin` WHERE `' . $pcoinb . "` LIKE '" . $tos . "'";
                    $users = M()->query($coin_Query);

                    if (!$users) continue;

                    $user = $users[0];
                } else {
                    $user = 0;
                    foreach ($pcoinb_array as $pcoinb_user) {
                        if ($pcoinb_user['addr'] == $tos) {
                            //$user=$pcoinb_user;

                            $coin_Query = 'SELECT userid as userid FROM `codono_user_coin` WHERE `' . $pcoinb . "` LIKE '" . $tos . "'";
                            $users = M()->query($coin_Query);

                            if (!$users) continue;

                            $user = $users[0];
                        } else {
                            continue;
                        }
                    }

                    if (!isset($user)) continue;
                }


                //esmart
                //$user = M('UserCoin')->where(array($pcoinb => $trans['to']))->find();
                if (!$user['userid']) continue;
                if (M('Myzr')->where(['txid' => $trans['hash']])->find()) {

                    $this->error('Transaction was already deposited!');
                    continue;
                }
                $addbalance = $Esmart->fromWei($trans['value']);

                M('myzr')->add(['userid' => $user['userid'], 'username' => $trans['to'], 'coinname' => $pcoin, 'fee' => 0, 'txid' => $trans['hash'], 'num' => $addbalance, 'mum' => $addbalance, 'addtime' => time(), 'status' => 1, 'type' => 'esmart']);
                $rs[] = M()->table('codono_user_coin')->where(['userid' => $user['userid']])->setInc($pcoin, $addbalance);
                deposit_notify($user['userid'], $tos, $pcoin, $trans['hash'], $addbalance, time());
                $this->success('Transaction was found and deposited');
            }

        }
    }

    public function doTrace($txid, $chain)
    {
        if (!userid()) {
            redirect(U('Login/login'));
        }
        $this->trackTx($txid, $chain);
    }

    public function findSymbol($coin): ?string
    {
        $coin = strtolower($coin);
        $coininfo = C('coin')[$coin];
        if (!is_array($coininfo)) {
            return null;
        }
        $symbol = strtolower($coininfo['symbol']);

        if ($symbol == null) {
            return $coin;
        } else {
            return $symbol;
        }
    }

    private function isValidCoin($coin): bool
    {
        $coins = C('coin_safe');

        if (array_key_exists(strtolower($coin), $coins)) {
            return true;
        } else {
            return false;
        }
    }

    private function checkIfColumnExists($tableName, $columnName)
    {
        // Sanitize the input to avoid SQL injection
        // This is a basic example; consider more robust methods depending on your context
        $tableName = preg_replace('/[^a-zA-Z0-9_]+/', '', $tableName);
        $columnName = preg_replace('/[^a-zA-Z0-9_]+/', '', $columnName);

        // Construct the SQL query
        $sql = "SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = '{$tableName}' AND COLUMN_NAME = '{$columnName}'";

        // Execute the query
        $result = M()->query($sql);

        // Check if any rows are returned
        return !empty($result);
    }

    private function getBalanceType($key)
    {
        // Check if the key exists in the array
        $allowed_types = $this->giveBalanceTypes();
        if (array_key_exists(strtolower($key), $allowed_types)) {
            return $allowed_types[$key];
        } else {
            return 0;
        }
    }

    private function giveBalanceTypes()
    {
        $types = self::allowed_types;

        if (MARGIN_ALLOWED != 1) {
            unset($types['margin']);
        }
        if (P2P_ALLOWED != 1) {
            unset($types['p2p']);
        }
        return $types;
    }
}

