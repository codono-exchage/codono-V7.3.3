<?php

namespace Home\Controller;

use Think\Controller;

class HomeController extends Controller
{
    protected function _initialize()
    {
        defined('APP_DEMO') || define('APP_DEMO', 0);
			
        $this->init_options();
        $pfoot=$pnavs=[];
        $invite = I('get.invite', null, 'string');
        $ext_socket = [];
        $self_socket = [];
        $user = array('invit' => '', 'firstname' => '', 'lastname' => '');
        if (!session('userId')) {
            session('userId', 0);
        } else if (CONTROLLER_NAME != 'Login') {
            $user = D('user')->where('id = ' . session('userId'))->find();

            $this->assign('emailaddress', $user['email']);
            $truenamefull = $user['truename'] ?: $user['username'];
            $this->assign('truenamefull', $truenamefull);

            if (TAWKTO_API_KEY != null && TAWK_TO_EMBED_URL != null) {
                $sha_mac = hash_hmac("sha256", $user['email'], TAWKTO_API_KEY);
                $this->assign('sha_mac', $sha_mac);
            }


            if ($user['token'] != session('token_user')) {
                //log in
                session(null);
                session('codono_already', 1);
                redirect('/');
            }

        }
        $this->assign('userinfo', $user);
        $syscoin = strtolower(SYSTEMCURRENCY);
        $syscoind = $syscoin . 'd';
        if (userid()) {
            $userCoin_top = M('UserCoin')->where(array('userid' => userid()))->find();
            $this->assign('usercoins', $userCoin_top);
            $userCoin_top['usd'] = round($userCoin_top[$syscoin], 2);
            $userCoin_top['usdd'] = round($userCoin_top[$syscoind], 2);
            $userCoin_top['allusd'] = round($userCoin_top[$syscoin] + $userCoin_top[$syscoind], 2);
            $this->assign('userCoin_top', $userCoin_top);
        }

        if (isset($invite)) {
            session('invit', $invite);
        }
		
        $config = (APP_DEBUG ? null : S('home_config'));

        if (!$config) {
            $config = M('Config')->where(array('id' => 1))->find();

            S('home_config', $config);
        }


        if (isset($_GET['codono'])) {
            if (ADMIN_KEY == $_GET['codono']) {
                session('web_close', 1);
            }
        }

        if (!session('web_close')) {
            if ($config['web_close'] != '1') {
                systemexception('<b>[Maintenance]</b> '.$config['web_close_cause'],'Maintenance:System has been put into maintenance',false);
                exit();
            }
        }

        C($config);
        C('contact_qq', explode('|', C('contact_qq')));

        $coin = (APP_DEBUG ? null : S('home_coin'));

        if (!$coin) {
            $coin = M('Coin')->where(array('status' => 1))->select();
            S('home_coin', $coin);
        }

        $coinList = array();

        foreach ($coin as $k => $v) {
            $coinList['coin'][$v['name']] = $v;
            if ($v['status'] == 1) {
                $coinList['coin_safe'][$v['name']] = array('id' => $v['id'], 'name' => $v['name'], 'title' => $v['title'], 'img' => $v['img'], 'type' => $v['type'], 'symbol' => $v['symbol'],'tokenof' => $v['tokenof'], 'zr_jz' => $v['zr_jz'], 'deposit' => $v['zr_jz'], 'zc_jz' => $v['zc_jz'], 'withdrawal' => $v['zc_jz'], 'confirmations' => $v['zr_dz'], 'explorer' => $v['js_wk'], 'zc_max' => $v['zc_max'], 'zc_min' => $v['zc_min'], 'zc_fee' => $v['zc_fee'], 'zc_flat_fee' => $v['zc_flat_fee'],'zc_coin' => $v['zc_coin']?:$v['name']);
            }
            if ($v['name'] != 'usd') {
                $coinList['coin_list'][$v['name']] = $v;
            }

            if ($v['type'] == 'rmb') {
                $coinList['rmb_list'][$v['name']] = $v;
            } else {
                $coinList['xnb_list'][$v['name']] = $v;
            }

            if ($v['type'] == 'rgb') {
                $coinList['rgb_list'][$v['name']] = $v;
            }

            if ($v['type'] == 'qbb') {
                $coinList['qbb_list'][$v['name']] = $v;
            }
            if ($v['type'] == 'eth') {
                $coinList['eth_list'][$v['name']] = $v;
            }
            if ($v['type'] == 'blockio') {
                $coinList['blockio_list'][$v['name']] = $v;
            }
            if ($v['type'] == 'cryptonote') {
                $coinList['cryptonote_list'][$v['name']] = $v;
            }
            if ($v['type'] == 'coinpay') {
                $coinList['coinpay_list'][$v['name']] = $v;
            }
            if ($v['type'] == 'waves') {
                $coinList['waves_list'][$v['name']] = $v;
            }
        }


        C($coinList);

        $market = (APP_DEBUG ? null : S('home_market'));


        $market_type = array();
        $coin_on = array();

        if (!$market) {
            $market = M('Market')->where(array('status' => 1))->select();
            S('home_market', $market);
        }

        $marketList = array();

        foreach ($market as $k => $v) {
            $v['new_price'] = round($v['new_price'], $v['round']);
            $v['buy_price'] = round($v['buy_price'], $v['round']);
            $v['sell_price'] = round($v['sell_price'], $v['round']);
            $v['min_price'] = round($v['min_price'], $v['round']);
            $v['max_price'] = round($v['max_price'], $v['round']);
            $v['xnb'] = explode('_', $v['name'])[0];
            $v['rmb'] = explode('_', $v['name'])[1];
            $v['xnbimg'] = C('coin')[$v['xnb']]['img']?:'default.png';
            $v['rmbimg'] = C('coin')[$v['rmb']]['img']?:'default.png';
            $v['volume'] = $v['volume'] * 1;
            $v['change'] = $v['change'] * 1;
            $v['title'] = C('coin')[$v['xnb']]['title'] . '(' . strtoupper($v['xnb']) . '/' . strtoupper($v['rmb']) . ')';
            $v['navtitle'] = C('coin')[$v['xnb']]['title'];
            $v['pairname'] = strtoupper($v['xnb']) . ' / ' . strtoupper($v['rmb']);

            if (!$v['begintrade']) {
                $v['begintrade'] = "00:00:00";
            }
            if (!$v['endtrade']) {
                $v['endtrade'] = "23:59:59";
            }


            $market_type[$v['xnb']] = $v['name'];
            $coin_on[] = $v['xnb'];
            $marketList['market'][$v['name']] = $v;
            if ($v['socket_type'] == 2) {
                $ext_socket[] = $v['name'];
            }
            if ($v['socket_type'] == 1) {
                $self_socket[] = $v['name'];
            }

        }

        C('market_type', $market_type);
        C('coin_on', $coin_on);
        S('ext_socket', json_encode($ext_socket));
        S('self_socket', $self_socket);

        C($marketList);
        $C = C();

        foreach ($C as $k => $v) {
            $C[strtolower($k)] = $v;
        }

        $this->assign('C', $C);


        $this->navigation = F('navigation');
        if (!$this->navigation) {

            $info_nav = M('Navigation')->where(array('status' => 1))->order('sort asc')->select();
            foreach ($info_nav as $nav) {
                if ($nav['pid'] == 0) {
                    $pnavs[$nav['id']] = $nav;
                    $pnavs[$nav['id']]['dropdown'] = 0;
                    $pnavs[$nav['id']]['submenu'] = array();
                }

            }
            foreach ($info_nav as $navi) {
                if ($navi['pid'] != 0) {
                    $pnavs[$navi['pid']]['dropdown'] = 1;
                    $pnavs[$navi['pid']]['submenu'][] = $navi;
                }


            }
            $this->navigation = $pnavs;
            F('navigation', $this->navigation);
        }
        
        $this->footer = F('footer');
        if (!$this->footer) {

            $info_nav = M('Footer')->where(array('status' => 1))->order('sort asc')->select();
            foreach ($info_nav as $foot) {
                if ($foot['pid'] == 0) {
                    $pfoot[$foot['id']] = $foot;
                    $pfoot[$foot['id']]['dropdown'] = 0;
                    $pfoot[$foot['id']]['submenu'] = array();
                }

            }
            foreach ($info_nav as $footi) {
                if ($footi['pid'] != 0) {
                    $pfoot[$footi['pid']]['dropdown'] = 1;
                    $pfoot[$footi['pid']]['submenu'][] = $footi;
                }


            }
            $this->footer = $pfoot;
            F('footer', $this->footer);
        }    
        $this->default_bank = (APP_DEBUG ? null : S('default_bank'));

        if (!$this->default_bank) {
            $this->default_bank = M('MyczType')->where(array('status' => 1, 'is_default' => 1))->select();
            S('default_bank', $this->default_bank);
        }

        if(cookie('themeMode')!='dark'){
            C('web_logo',C('footer_logo'));   
        }
        //makes default theme dark
        if(cookie('themeMode')!='light'){
            cookie('themeMode','dark');
        }
		$this->head_code_optional='';
		$this->assign('head_code_optional', $this->head_code_optional);
        $this->assign('default_bank', $this->default_bank);
        $this->assign('navigation', $this->navigation);
        $this->assign('footer', $this->footer);
        $this->assign('page_title', SHORT_NAME);
		$this->assign('title', SHORT_NAME);
    }

    public function _empty()
    {
        send_http_status(404);
        $this->error();
        echo L('Module does not exist!');
        die();

    }



    private function init_options()
    {
        $home_options = (APP_DEBUG ? null : S('home_options'));
        if (!$home_options) {
            $home_options = M('options')->getField('name,value', ':');
            S('home_options', $home_options);
        }
        C('home_options', $home_options);
        return $home_options;
    }
    protected function userExists($uid){
        $user_exist = M('User')->where(array('id' => $uid))->find();
        if(!empty($user_exist) && $user_exist['id']==$uid){
            return true;
        }else{
            return false;
        }
    }
}
