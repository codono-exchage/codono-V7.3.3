<?php

namespace Api\Controller;

use Think\Page;

class P2pController extends CommonController
{
    public const allowed_cryptos = ['USDT', 'BTC', 'ETH', 'BNB'];  // all caps Coins to be buy and sold
    public const require_coin = 'USDT';  // caps
    public const require_balance = 100;
    public const release_time = ['15', '30', '60', '120', '240', '360', '720'];
    public const article_category = 'faq';  // category to show articles from

    //Set transaction currency price

    public function _initialize()
    {
        if (P2P_ALLOWED == 0) {
            $this->error('P2P is currently disabled');
            
        }
		
        //Judgment to log in, you cannot directly access the database without logging in
        parent::_initialize();
        
    }
    private function getLastSeen($uid,$onlineThresholdMin=60){
        $lastSeen=M('User')->where(['id'=>$uid])->getField('p2p_last_seen');
        if(!$lastSeen){
            $lastSeen=0;
        }
        $lastSeenMin=bcdiv(bcsub(time(), $lastSeen,0),60);
    
        if ($lastSeenMin > $onlineThresholdMin) {
            $onlineStatus = "offline";
        } else {
            $onlineStatus = "online";
        }
        $timeAgo =  $this->formatTimeAgo(time() - $lastSeen);
        return ['onlineStatus' => $onlineStatus, 'last_online_time' => $timeAgo];
    }
    private function formatTimeAgo($timeAgo) {
        if ($timeAgo < 60) {
            return $timeAgo . ' seconds ago';
        } elseif ($timeAgo < 3600) {
            $minutes = floor($timeAgo / 60);
            return $minutes . ' minutes ago';
        } elseif ($timeAgo < 86400) {
            $hours = floor($timeAgo / 3600);
            return $hours . ' hours ago';
        } elseif ($timeAgo < 604800) { // Less than 7 days
            $days = floor($timeAgo / 86400);
            return $days . ' days ago';
        } elseif ($timeAgo < 2592000) { // Less than 30 days
            $weeks = floor($timeAgo / 604800);
            return $weeks . ' weeks ago';
        } elseif ($timeAgo < 31536000) { // Less than 12 months
            $months = floor($timeAgo / 2592000);
            return $months . ' months ago';
        } else {
            return 'Long ago';
        }
    }
    
    private function updateLastSeen(){
        $uid = $this->userid();
        if($uid>0){
            $data['p2p_last_seen']=time();
            
            M('User')->where(['id'=>$uid])->save($data);
        }
    }
    public function index()
    {
        $uid = $this->userid();
		
        $type = I('request.type', 'buy', 'string'); //buy/sell
        $fiat = I('request.fiat', 'USD', 'string');
        $coin = I('request.coin', 'USDT', 'string');
        $pm = I('request.pm', 1, 'int'); //payment method

        $fiat_qty = I('request.fiat_qty', 0.00, 'float');
        $coin_qty = I('request.coin_qty', 0.00, 'float');

        if ($coin_qty > 0 || $fiat_qty > 0) {

            if ($coin_qty > 0 && $fiat_qty == 0) {
                $where['available'] = ['egt', $coin_qty];
            } else {
                $where['min_limit'] = ['elt', $fiat_qty];
                $where['max_limit'] = ['egt', $fiat_qty];
            }
        }

        
        $order = 'id desc';
        if ($type != 'buy' && $type != 'sell') {
            $type = 'buy';
        }
        if ($type == 'buy') {
            $where['ad_type'] = 2;
            $order = 'fixed_price asc';
        }
        if ($type == 'sell') {
            $where['ad_type'] = 1;
            $order = 'fixed_price desc';
        }
        $userBal['balance'] = 0;
        if ($uid > 0) {
            $userBal = M('UserAssets')->where(['uid' => $uid, 'coin' => strtolower($coin)])->find();
        }
        
        $UserBanks = M('UserBank')->where(['userid' => $uid, 'status' => 1])->order('id desc')->select();
        if ($uid == 0) {
        }
        $userBal['balance'] = $userBal['balance'] ?: 0;
        $where['fiat'] = $fiat;
        $where['coin'] = $coin;
        $where['status'] = 1;
        
        $Model = M('P2pAds');
        $count = $Model->where($where)->count();
        $Page = new Page($count,30);
        $show = $Page->show();
        $ads = $Model->where($where)->order('id desc')->limit($Page->firstRow . ',' . $Page->listRows)->order($order)->select();
        $FiatMethods = $this->getP2pPaymentMethods();
        $ads_data = [];
        
        foreach ($ads as $ad) {
			$remainingfiat = $ad['fixed_price'] * $ad['available'];													   
            if ($remainingfiat < $ad['min_limit']) {
                $Model->where(['id' => $ad['id']])->save(['online' => 0]);
                continue; // No need to unset $ad, just continue to the next iteration
            }
            $rateInfo=$this->rateInfo($ad['uid']);
            
            $ads_data[$ad['id']] = $ad;
            $ads_data[$ad['id']]['orders'] = $rateInfo['orders']?:0; // Example value, adjust as necessary
            $ads_data[$ad['id']]['completion'] = $rateInfo['completion']?:100; // Example value, adjust as necessary
            $ads_data[$ad['id']]['method'] = [];
			$ads_data[$ad['id']]['merchantStatus']=$this->getLastSeen($ad['uid'],60);
            // Check if 'ad_methods' exists, is not empty, and is a valid JSON
            if (!empty($ad['ad_methods']) && is_string($ad['ad_methods'])) {
                $decodedMethods = json_decode($ad['ad_methods'], true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decodedMethods)) {
                    foreach ($FiatMethods as $key => $method) {
                        foreach ($decodedMethods as $meth) {
                            if ($meth == $key) {
                                $ads_data[$ad['id']]['method'][] = $method['name'];
                            }
                        }
                    }
                }
            }
        }
        
        $FiatList = $this->FiatList();
        $coin_imgs = [];
        foreach (C('coin') as $coin) {
            $coin_imgs[strtoupper($coin['name'])] = SITE_URL.'Upload/coin/'.$coin['img'];
        }
        if($uid>0){
            $user_payment_methods = M('P2pUserMethods')->where(['status' => 1,'userid'=>$uid])->order('id desc')->select();
        }else{
            $user_payment_methods =[];
        }
        
        //Allowed crypto coins
        $allow_cryptos = self::allowed_cryptos;
        $data['user_banks']=$UserBanks;
        $data['user_balance']= $userBal['balance'];
        $data['allow_cryptos']=$allow_cryptos;
        $data['FiatMethods']=$FiatMethods;
        $data['user_payment_methods']=$user_payment_methods;
        $data['FiatList']=$FiatList;
        $data['coinimgs']=$coin_imgs;
        $data['ads']=$ads_data;
        
        $this->ajaxShow($data);
    }
    public function express()
    {
        $uid = $this->userid();
		
        $type = I('request.type', 'buy', 'string'); //buy/sell
        $fiat = I('request.fiat', 'USD', 'string');
        $coin = I('request.coin', 'USDT', 'string');
        $pm = I('request.pm', 1, 'int'); //payment method

        $fiat_qty = I('request.fiat_qty', 0.00, 'float');
        $coin_qty = I('request.coin_qty', 0.00, 'float');

        if ($coin_qty > 0 || $fiat_qty > 0) {

            if ($coin_qty > 0 && $fiat_qty == 0) {
                $where['available'] = ['egt', $coin_qty];
            } else {
                $where['min_limit'] = ['elt', $fiat_qty];
                $where['max_limit'] = ['egt', $fiat_qty];
            }
        }

        
        $order = 'id desc';
        if ($type != 'buy' && $type != 'sell') {
            $type = 'buy';
        }
        if ($type == 'buy') {
            $where['ad_type'] = 2;
            $order = 'fixed_price asc';
        }
        if ($type == 'sell') {
            $where['ad_type'] = 1;
            $order = 'fixed_price desc';
        }
        $userBal['balance'] = 0;
        if ($uid > 0) {
            $userBal = M('UserAssets')->where(['uid' => $uid, 'coin' => strtolower($coin)])->find();
        }
        
        $UserBanks = M('UserBank')->where(['userid' => $uid, 'status' => 1])->order('id desc')->select();
        if ($uid == 0) {
        }
        $userBal['balance'] = $userBal['balance'] ?: 0;
        $where['fiat'] = $fiat;
        $where['coin'] = $coin;
        $where['status'] = 1;
        
        $Model = M('P2pAds');
        $count = $Model->where($where)->count();
        $Page = new Page($count, 30);
        $show = $Page->show();
        $ads = $Model->where($where)->order('id desc')->limit($Page->firstRow . ',' . $Page->listRows)->order($order)->select();
        $FiatMethods = $this->getP2pPaymentMethods();
        $ads_data = [];
        
        foreach ($ads as $ad) {
			$remainingfiat = $ad['fixed_price'] * $ad['available'];													   
            if ($remainingfiat < $ad['min_limit']) {
                $Model->where(['id' => $ad['id']])->save(['online' => 0]);
                continue; // No need to unset $ad, just continue to the next iteration
            }
            $rateInfo=$this->rateInfo($ad['uid']);
            
            $ads_data[$ad['id']] = $ad;
            $ads_data[$ad['id']]['orders'] = $rateInfo['orders']?:0; // Example value, adjust as necessary
            $ads_data[$ad['id']]['completion'] = $rateInfo['completion']?:100; // Example value, adjust as necessary
            $ads_data[$ad['id']]['method'] = [];
			$ads_data[$ad['id']]['merchantStatus']=$this->getLastSeen($ad['uid'],60);
            // Check if 'ad_methods' exists, is not empty, and is a valid JSON
            if (!empty($ad['ad_methods']) && is_string($ad['ad_methods'])) {
                $decodedMethods = json_decode($ad['ad_methods'], true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decodedMethods)) {
                    foreach ($FiatMethods as $key => $method) {
                        foreach ($decodedMethods as $meth) {
                            if ($meth == $key) {
                                $ads_data[$ad['id']]['method'][] = $method['name'];
                            }
                        }
                    }
                }
            }
        }
        
        $FiatList = $this->FiatList();
        $coin_imgs = [];
        foreach (C('coin') as $coin) {
            $coin_imgs[strtoupper($coin['name'])] = SITE_URL.'Upload/coin/'.$coin['img'];
        }
        if($uid>0){
            $user_payment_methods = M('P2pUserMethods')->where(['status' => 1,'userid'=>$uid])->order('id desc')->select();
        }else{
            $user_payment_methods =[];
        }
        
        //Allowed crypto coins
        $allow_cryptos = self::allowed_cryptos;
      //  $data['user_banks']=$UserBanks;
    //    $data['user_balance']= $userBal['balance'];
     //   $data['allow_cryptos']=$allow_cryptos;
    //    $data['FiatMethods']=$FiatMethods;
    //    $data['user_payment_methods']=$user_payment_methods;
     //   $data['FiatList']=$FiatList;
    //    $data['coinimgs']=$coin_imgs;
            $i=0;
            $found_ad=[];
        foreach($ads_data as $ads){
            $found_ad[$i]=$ads;
            $i++;
        }
        $data['found']=$found_ad[0];

        if($data['found']){
            $this->ajaxShow($data);
        }
        else{
            $this->error($data);
        }
        
    }
    private function FiatList()
    {
        $data = (APP_DEBUG ? null : S('FiatList'));

        if (!$data) {

            $data = M('Coin')->where(['status' => 1, 'type' => 'rmb'])->field('name,title,img')->select();
            S('FiatList', $data);
        }
        return $data;
    }

    

    public function vieworder()
    {
        $uid = $this->userid();
   
        $order_id = I('get.id', null, 'text');
        
        $order = M('P2pOrders')->where(['orderid' => $order_id, 'userid' => $uid])->find();

        if (!$order) {
            $this->error('No such order found');
        }

        $ad_info = $this->orderinfo($order, 'orders');

        $payinfo = $ad_info['payinfo'];

        $remain_time = $this->remainingTime($order['endtime']);
        $times_up = $this->iftimesUp($order['endtime']);
		if ($ad_info['seller']) {
            $user =  $user = M('User')->where(['id' => $ad_info['seller']])->find();
            $ad_info['seller'] = $user['truename'];
        }

        $order = $this->doRefundCancel($order, $times_up);
		$data['peerId']=$ad_info['peerid']; 
        $data['times_up']=$times_up;
        $data['remain_time']=$remain_time;
        $data['ad_info']=$ad_info;
        $data['order']=$order;
        $data['payinfo']=$payinfo;
        $data['truename']= $ad_info['seller'];
        $data['viewer_type']='userid';
        $this->ajaxShow($data);
    }
    public function getOrderStatusAjax()
    {
        $uid = $this->userid();
      

        $order_id = I('request.id', null, 'text');
        
        $order = M('P2pOrders')->where(['orderid' => $order_id, 'userid' => $uid])->find();
        


        if (!$order) {
            $this->error('No such order');
        }

        $ad_info = $this->orderinfo($order, 'orders');

        $payinfo = $ad_info['payinfo'];

        $remain_time = $this->remainingTime($order['endtime']);
        $times_up = $this->iftimesUp($order['endtime']);

        $order = $this->doRefundCancel($order, $times_up);

        $data['times_up']= $times_up;
		$data['peerStatus']= $this->getLastSeen($order['merchant_id'],60);
        $data['remain_time']= $remain_time;
        $data['ad_info']= $ad_info;
        $data['order']= $order;
        $data['status']= $order['status'];
        exit(json_encode($data));
    }

    public function receivedorder()
    {
        $uid = $this->userid();
      
        //  $id = I('id/d');
        $order_id = I('request.id', null, 'text');
        $order = M('P2pOrders')->where(['orderid' => $order_id, 'peerid' => $uid])->find();

        if (!$order) {
            $this->error('No such order found');
        }
        $ad_info = $this->orderinfo($order, 'orders');


        $payinfo = $ad_info['payinfo'];

        $remain_time = $this->remainingTime($order['endtime']);
        $times_up = $this->iftimesUp($order['endtime']);
        $order = $this->doRefundCancel($order, $times_up);

		$data['peerId']=$ad_info['peerid'];							
        $data['times_up']=$times_up;
        $data['remain_time']=$remain_time;
        $data['ad_info']=$ad_info;
        $data['order']=$order;
        $data['payinfo']=$payinfo;
        $data['truename']= $ad_info['seller'];
        $data['viewer_type']='peerid';
        $this->ajaxShow($data);
    }

    private function doRefundCancel($order, $times_up)
    {
        $orderid = $order['id'];
        $status = $order['status'];
        if ($times_up && $status == 0) {

            $order_info = M('P2pOrders')->where(['id' => $orderid, 'status' => 0])->find();
            $ad_info = $this->orderinfo($order_info);
            $buyer = $ad_info['buyer'];
            $seller = $ad_info['seller'];
            $merchant = $ad_info['seller'];
            $coin = strtolower($order_info['coin']);
            $total = $order_info['coin_qty'];
            $ad_id = $order_info['ad_id'];
            $peerid = $order_info['peerid'];
            //do refund
            if (!empty($order_info)) {
                $rs = [];
                $mo = M();
                $mo->startTrans();
                if ($seller == $merchant) {
                    //refund to p2p_ads, move freeze to balance for coin
                    $condition = ['id' => $ad_id, 'coin' => $coin];
					$conditionassets = ['uid' => $seller, 'coin' => $coin];
                    $found = $mo->table('codono_p2p_ads')->where($condition)->find();
                    if ($found) {
                        $rs[] = $mo->table('codono_p2p_ads')->where($condition)->setInc('available', $total);
                        $rs[] = $mo->table('codono_p2p_ads')->where($condition)->setDec('freeze', $total);
                    }
					$rs[] = $mo->table('codono_user_assets')->where($conditionassets)->setInc('balance', $total);
                    $rs[] = $mo->table('codono_user_assets')->where($conditionassets)->setDec('freeze', $total);
                } else {
                    //seller is not merchant then refund to user_assets , move freeze to available for coin
                    $condition = ['uid' => $seller, 'coin' => $coin];
                    $found = $mo->table('codono_user_assets')->where($condition)->find();
                    if (!$found) {
                        $rs[] = $mo->table('codono_user_assets')->add($condition);
                        $rs[] = $mo->table('codono_user_assets')->where($condition)->setInc('balance', $total);
                    } else {
                        $rs[] = $mo->table('codono_user_assets')->where($condition)->setInc('balance', $total);
                        $rs[] = $mo->table('codono_user_assets')->where($condition)->setDec('freeze', $total);
                    }

                }


                $up_where['id'] = $orderid;
                $up_where['coin'] = $coin;
                $up_where['status'] = 0;

                $request = ['status' => 2];
                $rs[] = $mo->table('codono_p2p_orders')->where($up_where)->save($request);
                if (check_arr($rs)) {
                    $mo->commit();

                    $subject = 'Timeout:P2p order has been cancelled';

                    $message = 'Due to timeout order ' . $order_info['orderid'] . ' has been cancelled ,any funds frozen will be refunded back';
                    $this->notify($peerid, $subject, $message);
                    $chat_array = ['orderid' => $orderid, 'content' => $message, 'userid' => 0, 'addtime' => time()];
                    $mo->table('codono_p2p_chat')->where(['orderid' => $orderid])->add($chat_array);
                    $order = $mo->table('codono_p2p_orders')->where(['id' => $orderid])->find();
                } else {
                    $mo->rollback();


                }

            }
            //change status to 2
        }
        return $order;
    }

    private function remainingTime($endtime): string
    {
        $now = time();
        $remain_time = bcsub($endtime, $now, 0);
        if ($remain_time <= 0) {
            $remain_time = 0;
        }

        $t = round($remain_time);
        return sprintf('%02d:%02d:%02d', ($t / 3600), ($t / 60 % 60), $t % 60);
    }

    private function iftimesUp($endtime): int
    {
        $now = time();
        $remain_time = bcsub($endtime, $now, 0);
        if ($remain_time <= 0) {
            $times_up = 1;
        } else {
            $times_up = 0;
        }
        return $times_up;

    }

    public function doTrade()
    {
        $search =[];
       // $this->error(json_encode($_POST));
        $uid = $this->userid();
        if (!$uid) {
            $this->error(L('PLEASE_LOGIN'),U('Login/login'));
        }
	      if (!kyced()) {
            $this->error(L('Complete KYC First!'));
        }
        $total = 0;
        $request['paywith'] = $paywith = I('request.paywith', 0.00, 'float');
        $request['amount'] = $amount = I('request.amount', 0.00, 'float');
        $request['type'] = $order_type = I('type/d');
        $request['id'] = $id = I('id/d');
        $request['paymethod'] = $paymethod = I('paymethod',null,'text');
        $request['method'] = $method = I('method','bank','text');
        if($paymethod==null){
            $this->error(L('No Payment Method Selected'));
        }else{
            
            if (strpos($paymethod, ':') !== false)
            {
                $explode=explode(':',$paymethod);
                $paymethod=$explode[1];
            }

            
        }
        if ($request['type'] != 1 && $request['type'] != 2) {
            $this->error(L('No such listing found:'.__LINE__));
        }

        //ones buying is selling for other
        if ($request['type'] == 1) {
            $ad_type = 2;
        } else {
            $ad_type = 1;
        }

        $mo = M();
        $where_condition = ['id' => $request['id'], 'status' => 1, 'ad_type' => $ad_type];
        //$this->error(json_encode($where_condition));
        //find info about ad
        $listing = $mo->table('codono_p2p_ads')->where($where_condition)->find();
		if (!$listing) {
            $this->error(L('No such listing found:325'));
        }
		if ($listing['online']!=1) {
            $this->error(L('Listing is currently offline'));
        }
        
        if ($listing['uid'] == $uid) {
            $this->error(L('You can not trade with own ad'));
        }
        $coin = $listing['coin'];
        //$fiat = $listing['fiat'];
        //@todo find user balance in crypto
        $balance_info = $this->userOtherbalances($uid, 1);

		$avail_amount = strtoupper($listing['fiat']) . ' ' . NumToStr($listing['available']);
        
        if ($amount > $listing['available']) {
            $this->error('Only available ' . $avail_amount);
        }

        // if type ==2
        //this ad is sell type
        if ($order_type == 2) {
            if (!$paymethod) {
                $this->error(L('Please select correct Payment Method:584'));
            }
		   $listing_pay_methods = json_decode($listing['ad_methods'], true);
            if($method=='bank'){
                $my_pay_method = M('UserBank')->where(['userid' => $uid, 'status' => 1, 'id' => $paymethod])->find();
            }else{
                $my_pay_method = M('P2pUserMethods')->where(['userid' => $uid, 'status' => 1, 'id' => $paymethod])->find();
            }

            if (!$my_pay_method || $my_pay_method['id'] != $paymethod) {
                $this->error(L('Please select correct Payment Method'));
            }
            $fiat_amount = 0;
            //calculate total_required
            if ($paywith > 0 && $amount == 0) {
                $fiat_amount = $paywith;
                $total = bcdiv((float)$paywith, (float)$listing['fixed_price'], 8);
                if ($fiat_amount < $listing['min_limit'] || $fiat_amount > $listing['max_limit']) {
                    $this->error('Keep Fiat amount between:' . NumToStr($listing['min_limit']) . ' and ' . NumToStr($listing['max_limit']));
                }
            }
            if ($paywith > 0 && $amount > 0) {
                $amount=0;
                $fiat_amount = $paywith;
                $total = bcdiv((float)$paywith, (float)$listing['fixed_price'], 8);
                if ($fiat_amount < $listing['min_limit'] || $fiat_amount > $listing['max_limit']) {
                    $this->error('Keep Fiat amount between:' . NumToStr($listing['min_limit']) . ' and ' . NumToStr($listing['max_limit']));
                }
            }
            if ($paywith == 0 && $amount > 0) {
                $fiat_amount = bcmul((float)$listing['fixed_price'], (float)$amount, 8);
                $min_crypto = bcdiv((float)$listing['min_limit'], (float)$listing['fixed_price'], 8);
                $max_crypto = bcdiv((float)$listing['max_limit'], (float)$listing['fixed_price'], 8);

                if ($fiat_amount < $listing['min_limit'] || $fiat_amount > $listing['max_limit']) {
                    $this->error('Crypto Limit Between:' . NumToStr($min_crypto) . ' and ' . NumToStr($max_crypto));
                }
                $total = $amount;
            }
            $avail_amount = strtoupper($listing['fiat']) . ' ' . NumToStr($listing['available']);
            if ($fiat_amount > $listing['available']) {
                $this->error('Only available ' . $avail_amount);
            }

            if ($total <= 0) {
                $this->error(L('Enter amount properly'));
            }
            $userinfo=M('User')->where(array('id' => $uid))->find();

            $my_p2p_bal = $balance_info[strtolower($coin)]['balance'];
            if ($my_p2p_bal < $total) {
                $this->error(L("Please transfer $coin to your P2P balance"),SITE_URL.U('Transfer/index'),1);
            }
            //@todo see if this user meets requirements
            if (!kyced()) {
                $this->error(L('Please complete KYC!'));
            }

            if ($listing['cond_reg'] == 1 && $listing['cond_reg_ago'] > 0) {
                $seconds = $listing['cond_reg_ago'] * 86400;
                $check_before = bcadd(time(), $seconds, 0);
                if ($userinfo['addtime'] > $check_before) {
                    $this->error('You need do not meet minimum days registration criteria');
                }
            }
            //@todo see if total = price * crypto amount and check if user has enough coin [Fiat]
            if ($listing['cond_balance'] == 1 && $listing['cond_min_bal'] > 0) {
                $req_p2p_bal = $balance_info[strtolower(self::require_coin)]['balance'];
                $req_user_balance = M('UserCoin')->where(['userid' => $uid])->getField(strtolower(self::require_coin));

                if ($listing['cond_min_bal'] > $req_p2p_bal && $listing['cond_min_bal'] > $req_user_balance) {
                    $this->error('Minimum balance criteria:' . NumToStr($listing['cond_min_bal']) . ' ' . self::require_coin);
                }
            }


            //@todo add merchant order count by 1 
            //Need to code  later if there is such payment method
            $lc_coin = strtolower($coin);
            $rs = [];
            $mo->startTrans();
            //$before_balance = $mo->table('codono_user_assets')->where(array('uid' => $uid, 'coin' => $lc_coin))->find();

            //move p2p_ads entry 'available' to 'freeze'
            $rs[] = $mo->table('codono_p2p_ads')->where(['id' => $id])->setDec('available', $total);
            $rs[] = $mo->table('codono_p2p_ads')->where(['id' => $id])->setInc('freeze', $total);

            //put my coin 'total' to 'freeze'
            $rs[] = $mo->table('codono_user_assets')->where(['uid' => $uid, 'coin' => $lc_coin])->setDec('balance', $total);
            $rs[] = $mo->table('codono_user_assets')->where(['uid' => $uid, 'coin' => $lc_coin])->setInc('freeze', $total);
            $seconds = $listing['time_limit'] * 60;
            $end_time = time() + $seconds;
            //do p2p_order entry
            $p2p_order = [
                'name' => $listing['name'],
                'orderid' => strtoupper(dechex(cardGenPublic($uid . $listing['id']))),//$listing['orderid'],
                'coin' => $lc_coin,
                'fixed_price' => $listing['fixed_price'],
                'coin_qty' => $total,
                'fiat' => $listing['fiat'],
                'fiat_qty' => $fiat_amount,
                'ad_id' => $listing['id'],
                'ad_type' => $order_type,
                'userid' => $uid,
                'merchant_id' => $listing['uid'],
                'payment_info' => json_encode([$my_pay_method]),
                'time_limit' => $listing['time_limit'],
                'addtime' => time(),
                'endtime' => $end_time,
                'status' => 0
            ];


            //do finance entry
			$listingIds = $listing['orderid'];

            //if success notify owner of ad
		   
          
            $subject = 'P2P Order Received';
           
            $content = "<br/><strong>Dear Trader, You have received a p2p Order.Please Ensure you release crypto only after getting payment. <strong><br>
			<strong><a href='{:SITE_URL}P2p/vieworder/id/$listingIds'>View Order</a></strong>";
          
            $this->notify($listing['uid'],$subject,$content);
            


        } else {
            //user is buyer and ordering on sell order
            $listing_pay_methods = json_decode($listing['pay_methods'], true);

            $search = implode(',', $listing_pay_methods);
            $peerid = $listing['merchant_id'];

            $my_pay_method = M('UserBank')->where(['userid' => $peerid])->select($search);
            

            if (empty($my_pay_method)) {
                $this->error(L('There were issues placing order!') . ':2');
            }
            $fiat_amount = 0;
            //calculate total_required
            if ($paywith > 0 && $amount == 0) {
                $fiat_amount = $paywith;
                $total = bcdiv((float)$paywith, (float)$listing['fixed_price'], 8);
                if ($fiat_amount < $listing['min_limit'] || $fiat_amount > $listing['max_limit']) {
                    $this->error('Keep Fiat amount between:' . NumToStr($listing['min_limit']) . ' and ' . NumToStr($listing['max_limit']));
                }
            }

            if ($paywith == 0 && $amount > 0) {
                $fiat_amount = bcmul((float)$listing['fixed_price'], (float)$amount, 8);
                $min_crypto = bcdiv((float)$listing['min_limit'], (float)$listing['fixed_price'], 8);
                $max_crypto = bcdiv((float)$listing['max_limit'], (float)$listing['fixed_price'], 8);

                if ($fiat_amount < $listing['min_limit'] || $fiat_amount > $listing['max_limit']) {
                    $this->error('Crypto Limit Between:' . NumToStr($min_crypto) . ' and ' . NumToStr($max_crypto));
                }
                $total = $amount;
            }
            $avail_amount = strtoupper($listing['fiat']) . ' ' . NumToStr($listing['available']);
            if ($fiat_amount > $listing['available']) {
                $this->error('Only available ' . $avail_amount);
            }
            if ($total <= 0) {
                $this->error(L('Enter amount properly'));
            }
            $userinfo=M('User')->where(array('id' => $uid))->find();
            //@todo see if this user meets requirements
            if (!kyced()) {
                $this->error(L('Please complete KYC!'));
            }

            if ($listing['cond_reg'] == 1 && $listing['cond_reg_ago'] > 0) {
                $seconds = $listing['cond_reg_ago'] * 86400;
                $check_before = bcadd(time(), $seconds, 0);
                if ($userinfo['addtime'] > $check_before) {
                    $this->error('You need do not meet minimum days registeration criteria');
                }
            }
            //@todo see if total = price * crypto amount and check if user has enough coin [Fiat]
            if ($listing['cond_balance'] == 1 && $listing['cond_min_bal'] > 0) {
                $req_p2p_bal = $balance_info[strtolower(self::require_coin)]['balance'];
                $req_user_balance = M('UserCoin')->where(['userid' => $uid])->getField(strtolower(self::require_coin));

                if ($listing['cond_min_bal'] > $req_p2p_bal && $listing['cond_min_bal'] > $req_user_balance) {
                    $this->error('Minimum balance criteria:' . NumToStr($listing['cond_min_bal']) . ' ' . self::require_coin);
                }
            }


            //@todo check payment method
            //Need to code  later if there is such payment method
            $lc_coin = strtolower($coin);
            $rs = [];
            $mo->startTrans();

            //move p2p_ads entry 'available' to 'freeze'
            $rs[] = $mo->table('codono_p2p_ads')->where(['id' => $id])->setDec('available', $total);
            $rs[] = $mo->table('codono_p2p_ads')->where(['id' => $id])->setInc('freeze', $total);

            $seconds = $listing['time_limit'] * 60;
            $end_time = time() + $seconds;
            //do p2p_order entry
            $p2p_order = [
                'name' => $listing['name'],
                'orderid' => strtoupper(dechex(cardGenPublic($uid . $listing['id']))),//$listing['orderid'],
                'coin' => $lc_coin,
                'fixed_price' => $listing['fixed_price'],
                'coin_qty' => $total,
                'fiat' => $listing['fiat'],
                'fiat_qty' => $fiat_amount,
                'ad_id' => $listing['id'],
                'ad_type' => $order_type,
                'userid' => $uid,
                'merchant_id' => $listing['uid'],
                'payment_info' => json_encode($my_pay_method),
                'time_limit' => $listing['time_limit'],
                'addtime' => time(),
                'endtime' => $end_time,
                'status' => 0
            ];


            //@todo if success notify owner of ad

			$listingIds = $listing['orderid'];

             //if success notify owner of ad
             
            
             $subject = 'P2P Order Received';
            
             $content = "<br/><strong>Dear Trader, You have received a p2p Order.Please Ensure you release crypto only after getting payment. <strong><br>
             <strong><a href='{:SITE_URL}/P2p/vieworder/id/$listingIds'></a>View Order</strong>";
            //$this->error($content);
            $this->notify($listing['uid'],$subject,$content);  
        }
        $rs[] = $p2p_adid = $mo->table('codono_p2p_orders')->add($p2p_order);
        if (check_arr($rs)) {
            $mo->commit();

            //push auto reply to p2p_chat
            if ($listing['autoreply']) {
                $add_array = ['orderid' => $p2p_adid, 'content' => $listing['autoreply'], 'userid' => $listing['uid'], 'addtime' => time()];
                $mo->table('codono_p2p_chat')->where(['orderid' => $p2p_adid])->add($add_array);
            }
            $this->success(L('Order placed!!'));
        } else {
            $mo->rollback();
            $this->error(L('There were issues placing order!'));
        }
        //sell logic ends

    }

    /*
    order is array information of order from p2p_orders table
    $types=orders, received
    */

    private function orderinfo($order, $type = 'orders')
    {

        if (!$order) {
            return false;
        }
        $uid = $this->userid();
        $user = M('User')->where(array('id' => $uid))->find();
        $ad_info = $order;
        $ad_info['myid'] = $uid;


        if ($order['userid'] == $uid) {
            $ad_info['is_merchant'] = 0;
            $ad_info['peerid'] = $order['merchant_id'];
            $ad_info['peername'] = username($ad_info['peerid']);

        } elseif ($order['merchant_id'] == $uid) {
            $ad_info['is_merchant'] = 1;
            $ad_info['peerid'] = $order['userid'];
            $ad_info['peername'] = username($ad_info['peerid']);
        } else {
            $this->error(L('No such listing found'));
        }

        if ($order['ad_type'] == 2) {
            if ($ad_info['is_merchant']) {
                $ad_info['mytype'] = 'buyer';
            } else {
                $ad_info['mytype'] = 'seller';
            }
        }


        if ($order['ad_type'] == 1) {
            if ($ad_info['is_merchant']) {
                $ad_info['mytype'] = 'seller';
            } else {
                $ad_info['mytype'] = 'buyer';
            }
        }


        if ($ad_info['mytype'] == 'buyer') {
            $ad_info['seller'] = fullname($ad_info['peerid']);
        } else {
            $ad_info['seller'] = $user['firstname'] . ' ' . $user['lastname'];
        }

        $ad_info['payinfo'] = json_decode($order['payment_info'], true);
        if ($order['ad_type'] == 1) {
            $ad_info['buyer'] = $order['userid'];
            $ad_info['seller'] = $order['merchant_id'];
        } else {
            $ad_info['buyer'] = $order['merchant_id'];
            $ad_info['seller'] = $order['userid'];
        }

        return $ad_info;

    }

    public function test()
    {
           $paymethods = array(16, 18);
           $uid=$this->userid();
           $search = implode(',', $paymethods);
          $my_pay_method = M('UserBank')->where(array('userid' => $uid))->select($search);
        $this->ajaxShow($my_pay_method);
    }

    private function userOtherbalances($userid, $type = 1, $coin = 'all')
    {
        $coin = strtolower($coin);
        if (!check($userid, 'd')) {
            $this->error(L('INCORRECT_REQ'));
        }
        if (!check($type, 'd')) {
            $this->error(L('INCORRECT_REQ'));
        }
        $where = [];
        if ($coin != 'all') {
            if (!check($coin, 'n')) {
                $this->error('Incorrect coin ' . $coin);
            }
            $where['coin'] = strtolower($coin);
        }
        $where['uid'] = $userid;
        $where['type'] = $type;
        $user_balance = M('user_assets')->where($where)->select();
        $coins = C('coin_safe');
        $list = [];
        foreach ($user_balance as $ub) {
            $list[$ub['coin']] = $coins[$ub['coin']];
            $list[$ub['coin']]['balance'] = $ub['balance'];
            $list[$ub['coin']]['freeze'] = $ub['freeze'];
            if ($list[$ub['coin']]['balance'] <= 0 || !array_key_exists($ub['coin'], $coins)) {
                unset($list[$ub['coin']]);
            }
        }


        return $list;
    }


    public function quick()
    {

        $FiatList = $this->FiatList();
        $allow_cryptos = $this->allowed_cryptos();
        $data['FiatList']=$FiatList;
        $data['allow_cryptos']=$allow_cryptos;
        $this->ajaxShow($data);
    }

    public function apply()
    {
        $uid = $this->userid();
    
        $require_coin = strtolower(self::require_coin);
        $require_balance = self::require_balance;
        $FiatList = $this->FiatList();
        $allowed_fiat = [];
        foreach ($FiatList as $fiat) {
            $allowed_fiat[] = strtoupper($fiat['name']);
        }
        $status = $this->checkApply();
		$is_merchant = M('p2p_merchants')->where(['uid' => $uid, 'status' => 1])->find();
		$status['is_merchant']=$is_merchant;
        $data['allowed_fiat']=$allowed_fiat;
        $data['require_balance']=$require_balance;
        $data['require_coin']=$require_coin;
        $data['status']=$status;
        $this->ajaxShow($data);
    }

    public function markAsPaid()
    {
        $uid = $this->userid();
        if (!$uid) {
            $this->error(L('PLEASE_LOGIN'));
        }
        $id = I('request.id', 0, 'int');
        if (!$id) {
            $this->error(L('Incorrect Order id'));
        }

        
        $mo = M();
        $order = $mo->table('codono_p2p_orders')->where(['id' => $id, 'status' => 0])->find();


        if (!$order) {
            $this->error('No such order found');
        }


        $ad_info = $this->orderinfo($order, 'orders');

        $times_up = $this->iftimesUp($order['endtime']);
        if ($ad_info['endtime'] < time()) {
            $this->doRefundCancel($order, $times_up);
            $this->error('Payment time has ended already!');
        }


        $ad_info = $this->orderinfo($order, 'orders');
        
        $peerid = $ad_info['peerid'];
        //This could be buyer instead of seller
        if ($ad_info['mytype'] != 'buyer') {
            $this->error(L('No such listing found'));
        }


        $subject = 'P2p order has been paid';
        $message = 'User has marked as paid your order ' . $order['orderid'] . ' please confirm';
        $updated = $mo->table('codono_p2p_orders')->where(['id' => $order['id']])->save(['has_paid' => 1, 'paidtime' => time(), 'status' => 1]);


        if ($updated) {
            $chat_array = ['orderid' => $order['id'], 'content' => $message, 'userid' => 0, 'addtime' => time()];
            $mo->table('codono_p2p_chat')->where(['orderid' => $order['id']])->add($chat_array);
            $this->notify($peerid, $subject, $message);
        }

        $this->success(L('This order has been markerd as paid'));

        //notify counter party that user has paid

    }

    public function timeoutCancelOrder()
    {
        $uid = $this->userid();
        if (!$uid) {
            $this->error(L('PLEASE_LOGIN'));
        }
        $id = I('request.id', 0, 'int');
        if (!$id) {
            $this->error(L('Incorrect Order id'));
        }

        $mo = M();
        $order = $mo->table('codono_p2p_orders')->where(['id' => $id, 'status' => 0])->find();

        if (!$order) {
            $this->error(L('No such listing found'));
        }

        $ad_info = $this->orderinfo($order, 'orders');

        $peerid = $ad_info['peerid'];


        $coin = strtolower($order['coin']);
        $total = $order['coin_qty'];
        $orderid = $order['id'];
        $is_merchant = $ad_info['is_merchant'];
        $ad_id = $order['ad_id'];
        $mo = M();
        $mo->startTrans();

        //changes for seller
        $condition_1 = ['uid' => $uid, 'coin' => $coin];
        $condition_2 = ['uid' => $peerid, 'coin' => $coin];


        if ($is_merchant) {
            $rs[] = $mo->table('codono_user_assets')->where($condition_2)->setDec('freeze', $total);
            $rs[] = $mo->table('codono_user_assets')->where($condition_2)->setInc('balance', $total);
        }
        $rs[] = $mo->table('codono_p2p_ads')->where(['id' => $ad_id])->setDec('freeze', $total);
        $rs[] = $mo->table('codono_p2p_ads')->where(['id' => $ad_id])->setInc('available', $total);

        $up_where['id'] = $orderid;
        $up_where['coin'] = $coin;
        $up_where['status'] = 0;

        //status 2 = cancelled
        $request = ['status' => 2];
        $rs[] = $mo->table('codono_p2p_orders')->where($up_where)->save($request);

        if (check_arr($rs)) {
            $mo->commit();


            $subject = 'P2p order has been cancelled';
            $merchantname = username($uid);
            $message ="Your P2P Order (".$order['orderid'].") has been cancelled Due to Timeout , funds frozen will be refunded back";
            $this->notify($peerid, $subject, $message);

            $add_array = ['orderid' => $orderid, 'content' => $message, 'userid' => 0, 'addtime' => time()];
            $mo->table('codono_p2p_chat')->where(['orderid' => $orderid])->add($add_array);
            $this->success(L('This order has been cancelled'));
        } else {
            $mo->rollback();

            $this->error('There were issues updating the order!');
        }
    }
    public function confirmCancel()
    {
        $uid = $this->userid();
        if (!$uid) {
            $this->error(L('PLEASE_LOGIN'));
        }
        $id = I('request.id', 0, 'int');
        if (!$id) {
            $this->error(L('Incorrect Order id'));
        }

        $mo = M();
        $order = $mo->table('codono_p2p_orders')->where(['id' => $id, 'status' => 0])->find();

        if (!$order) {
            $this->error(L('No such listing found'));
        }

        $ad_info = $this->orderinfo($order, 'orders');

        $peerid = $ad_info['peerid'];

        //@todo if merchant and cancelled then change the rating


        //This could be seller instead buyer [ Only buyer can cancle the order]
        if ($ad_info['mytype'] != 'buyer') {
            $this->error(L('No such listing found'));
        }


        $coin = strtolower($order['coin']);
        $total = $order['coin_qty'];
        $orderid = $order['id'];
        $is_merchant = $ad_info['is_merchant'];
        $ad_id = $order['ad_id'];
        $mo = M();
        $mo->startTrans();

        //changes for seller
        $condition_1 = ['uid' => $uid, 'coin' => $coin];
        $condition_2 = ['uid' => $peerid, 'coin' => $coin];


        if ($is_merchant) {
            $rs[] = $mo->table('codono_user_assets')->where($condition_2)->setDec('freeze', $total);
            $rs[] = $mo->table('codono_user_assets')->where($condition_2)->setInc('balance', $total);
        }


        $rs[] = $mo->table('codono_p2p_ads')->where(['id' => $ad_id])->setDec('freeze', $total);
        $rs[] = $mo->table('codono_p2p_ads')->where(['id' => $ad_id])->setInc('available', $total);

        $up_where['id'] = $orderid;
        $up_where['coin'] = $coin;
        $up_where['status'] = 0;

        //status 2 = cancelled
        $request = ['status' => 2];
        $rs[] = $mo->table('codono_p2p_orders')->where($up_where)->save($request);

        if (check_arr($rs)) {
            $mo->commit();


            $subject = 'P2p order has been cancelled';
            $merchantname = username($uid);
            $message = $merchantname . ' has cancelled this order ' . $order['orderid'] . ' any funds frozen will be refunded back';
            $this->notify($peerid, $subject, $message);

            $add_array = ['orderid' => $orderid, 'content' => $message, 'userid' => 0, 'addtime' => time()];
            $mo->table('codono_p2p_chat')->where(['orderid' => $orderid])->add($add_array);
            $this->success(L('This order has been cancelled'));
        } else {
            $mo->rollback();

            $this->error('There were issues updating the order!');
        }
    }

    public function releasePayment()
    {
        $uid = $this->userid();
        if (!$uid) {
            $this->error(L('PLEASE_LOGIN'));
        }

        $id = I('request.id', 0, 'int');
        if (!$id) {
            $this->error(L('Incorrect Order id'));
        }
        $mo = M();
        $order = $mo->table('codono_p2p_orders')->where(['id' => $id, 'status' => 1])->find();

        if (!$order) {
            $this->error(L('No such listing found'));
        }

        $ad_info = $this->orderinfo($order, 'orders');


        $peerid = $ad_info['peerid'];

        //This could be buyer instead of seller
        if ($ad_info['mytype'] != 'seller') {
            $this->error(L('No such listing found'));
        }
        $is_merchant = $ad_info['is_merchant'];

        $coin = strtolower($order['coin']);
        $total = $order['coin_qty'];
        $orderid = $order['id'];
        $ad_id = $order['ad_id'];
        $mo = M();
        $mo->startTrans();
        //@todo if merchant confirmed then change the rating 5.0


        //changes for seller
        $condition_1 = ['uid' => $uid, 'coin' => $coin];
        $found = $mo->table('codono_user_assets')->where($condition_1)->find();
        if (!$found) {
            $rs[] = $mo->table('codono_user_assets')->add($condition_1);
        }


        //changes for buyer
        $condition_2 = ['uid' => $peerid, 'coin' => $coin];
        $found = $mo->table('codono_user_assets')->where($condition_2)->find();
        if (!$found) {
            $rs[] = $mo->table('codono_user_assets')->add($condition_2);
        }
        //user is seller but not merchant thus reduce his freeze
        if (!$is_merchant) {
            $rs[] = $mo->table('codono_user_assets')->where($condition_1)->setDec('freeze', $total);
        }
        $rs[] = $mo->table('codono_user_assets')->where($condition_2)->setInc('balance', $total);


        $rs[] = $mo->table('codono_p2p_ads')->where(['id' => $ad_id])->setDec('freeze', $total);


        $up_where['id'] = $orderid;
        $up_where['coin'] = $coin;
        $up_where['status'] = 1;

        $request = ['status' => 4];
        $rs[] = $mo->table('codono_p2p_orders')->where($up_where)->save($request);

        if (check_arr($rs)) {
            $mo->commit();

            $subject = 'P2p payment has been released';
            $message = 'User has released the funds on your order ' . $order['orderid'] . ' Congrats!';
            $this->notify($peerid, $subject, $message);
            $this->success(L('This order has been markerd as received and Payment has been released'));
        } else {
            $mo->rollback();

            $this->error('There were issues updating the order!');
        }

    }

    public function dispute()
    {
        $uid = $this->userid();
        if (!$uid) {
            $this->error(L('PLEASE_LOGIN'));
        }

        $id = I('request.id', 0, 'int');
        if (!$id) {
            $this->error(L('Incorrect Order id'));
        }
        $mo = M();
        $order = $mo->table('codono_p2p_orders')->where(['id' => $id, 'status' => 1])->find();

        if (!$order) {
            $this->error(L('No such listing found'));
        }

        $ad_info = $this->orderinfo($order, 'orders');

        $peerid = $ad_info['peerid'];

        //This could be buyer instead of seller
        if ($ad_info['mytype'] != 'seller' && $ad_info['mytype'] != 'buyer') {
            $this->error(L('No such listing found'));
        }

        $is_merchant = $ad_info['is_merchant'];

        $coin = strtolower($order['coin']);
        $total = $order['coin_qty'];
        $orderid = $order['id'];
        $ad_id = $order['ad_id'];
        $mo = M();
        $mo->startTrans();


        //@todo if merchant confirmed then change the rating 5.0


        $up_where['id'] = $orderid;
        $up_where['coin'] = $coin;
        $up_where['status'] = 1;

        $request = ['status' => 3];
        $rs[] = $mo->table('codono_p2p_orders')->where($up_where)->save($request);

        if (check_arr($rs)) {
            $mo->commit();

            $subject = 'P2P order Disputed';
            $message = username($uid) . ' has created a dispute for order ' . $order['orderid'] . '!';
            $this->notify($peerid, $subject, $message);
            $chat_array = ['orderid' => $orderid, 'content' => $message, 'userid' => 0, 'addtime' => time()];
            $mo->table('codono_p2p_chat')->where(['orderid' => $orderid])->add($chat_array);


            $this->success(L('This order has been opened for dispute resolution'));
        } else {
            $mo->rollback();

            $this->error('There were issues updating the order!');
        }

    }

    public function sendchat()
    {
        $uid = $this->userid();
        $data = ['status' => 0, 'data' => []];
        if (!$uid) {
            exit(json_encode($data));
        }
        $orderid = I('request.orderid', 0, 'int');
        $content = I('request.content', null, 'text');

        if ($orderid != 0 && $content != null) {


            $mo = M();
            $order = $mo->table('codono_p2p_orders')->where(['id' => $orderid])->find();

            if ($order['userid'] == $uid) {
                //  $is_merchant = 0;
                // $peerid = $order['merchant_id'];
            } elseif ($order['merchant_id'] == $uid) {
                // $is_merchant = 1;
                // $peerid = $order['userid'];
            } else {
                exit(json_encode($data));
            }

            $add_array = ['orderid' => $orderid, 'content' => $content, 'userid' => $uid, 'addtime' => time()];
            $data['status'] = 1;
            $data['info'] = $mo->table('codono_p2p_chat')->where(['orderid' => $orderid])->add($add_array);
        }
        exit(json_encode($data));
    }

    public function getChat()
    {
        $id = I('request.id', 0, 'int');
        if (!$id) {
            $this->error(L('Incorrect Order id'));
        }

        $uid = $this->userid();

        $data = ['status' => 0, 'data' => []];
        $mo = M();
        $order = $mo->table('codono_p2p_orders')->where(['id' => $id])->find();

        if ($order['userid'] == $uid) {
              $is_merchant = 0;
              $peerid = $order['merchant_id'];
        } elseif ($order['merchant_id'] == $uid) {
             $is_merchant = 1;
             $peerid = $order['userid'];
        } else {
            exit(json_encode($data));
        }
        $d_chats = $mo->table('codono_p2p_chat')->where(['orderid' => $id])->select();
        $chats = $usernames=[];
        foreach ($d_chats as $_chat) {
            $_chat['timestamp'] = $this->time_elapsed_string($_chat['addtime']);
            if($_chat['userid']==0){
                $_chat['username'] = 'System';
            }elseif(!array_key_exists($_chat['userid'],$usernames)){
                $_chat['username']= username($_chat['userid']);    
            }else{
                $_chat['username'] = $usernames[$_chat['userid']];
            }
            
            $chats[] = $_chat;
        }
        $data = ['status' => 1, 'data' => $chats];
        exit(json_encode($data));
    }

    private function time_elapsed_string($ptime)
    {
        $etime = time() - $ptime;

        if ($etime < 1) {
            return '0 seconds';
        }

        $a = [365 * 24 * 60 * 60 => 'year',
            30 * 24 * 60 * 60 => 'month',
            24 * 60 * 60 => 'day',
            60 * 60 => 'hour',
            60 => 'minute',
            1 => 'second'
        ];
        $a_plural = ['year' => 'years',
            'month' => 'months',
            'day' => 'days',
            'hour' => 'hours',
            'minute' => 'minutes',
            'second' => 'seconds'
        ];

        foreach ($a as $secs => $str) {
            $d = $etime / $secs;
            if ($d >= 1) {
                $r = round($d);
                return $r . ' ' . ($r > 1 ? $a_plural[$str] : $str) . ' ago';
            }
        }
        return false;
    }

    private function notify($userid, $subject, $message)
    {
        $email = getEmail($userid);
        addnotification($email, $subject, $message);
    }

    private function checkApply()
    {
        $uid = $this->userid();
        $userinfo =M('User')->where(['id' => $uid])->find();
        $status = ['sms' => 1, 'email' => 0, 'kyc' => 0, 'merchant' => 0, 'balance' => 0];
        $require_coin = strtolower(self::require_coin);
        $require_balance = self::require_balance;
        $balance = $this->findFunds($require_coin,$uid);
        if ($userinfo['cellphone'] && $userinfo['cellphones'] && $userinfo['cellphonetime']) {
            $status['sms'] = 1;
        }
        if ($userinfo['email']) {
            $status['email'] = 1;
        }
        if ($userinfo['idcardauth']) {
            $status['kyc'] = 1;
        }
        if ($userinfo['is_merchant']) {
            $status['merchant'] = 1;
        }
        if ($balance && $balance >= $require_balance) {
            $status['balance'] = 1;
        }

        return $status;

    }

    private function findFunds($coin ,$uid)
    {
        $coin = strtolower($coin);
        $isValidCoin = isValidCoin($coin);
        if ($coin == null || !$isValidCoin) {
            return 0;
        }
        $require_coin = strtolower($coin);
        $userBal = M('UserAssets')->where(['uid' => $uid, 'coin' => strtolower($require_coin)])->find();

        return $userBal['balance'] ?: 0;
    }

    public function doApply()
    {
        $uid = $this->userid();
        if (!$uid) {
            $this->error(L('PLEASE_LOGIN'));
        }
        $require_coin = strtolower(self::require_coin);
        $require_balance = self::require_balance;
        $status = $this->checkApply();

        if (!$status['sms']) {
         //   $this->error(L('Please confirm you mobile number!'));
        }
        if (!$status['email']) {
            $this->error(L('Please confirm you email!'));
        }
        if (!$status['kyc']) {
            $this->error(L('Please complete KYC!'));
        }
        if (!$status['balance']) {
            $this->error(L('You need balance of ' . $require_coin . ' ' . $require_balance));
        }
        $if_merchant = M('p2p_merchants')->where(['uid' => $uid, 'status' => 1])->find();
        if ($status['sms'] && $status['email'] && $status['kyc']) {

            if (!$if_merchant) {
                M('p2p_merchants')->where(['id' => $uid])->add(['uid' => $uid, 'name' => username($uid), 'status' => 1, 'rating' => 0, 'orders' => 0]);
            }
            M('User')->where(['id' => $uid])->save(['is_merchant' => 1]);

            $this->success(L('Successfully upgraded for merchant!'),SITE_URL.U('P2p/newad'),1);
        }
        $this->error(L('There were some issues for your application'));
    }


    public function orders()
    {
        $userid = $uid = $this->userid();
        
        $allow_cryptos = $this->allowed_cryptos();
        $data['allow_cryptos']=$allow_cryptos;

        $where = ['userid' => $userid];

        $Model = M('P2pOrders');
        $count = $Model->where($where)->count();
        $Page = new Page($count, 10);
        $show = $Page->show();
        $p2pOrders = M('P2pOrders')->where($where)->order('id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $type = 'order';
        $data['p2pOrders']=$p2pOrders;
        $data['page']=$show;
        $data['type']=$type;
        $this->ajaxShow($data);
    }

    public function received()
    {
        $userid = $uid = $this->userid();
     
        $allow_cryptos = $this->allowed_cryptos();
        $data['allow_cryptos']=$allow_cryptos;

        $where = ['merchant_id' => $userid];

        $Model = M('P2pOrders');
        $count = $Model->where($where)->count();
        $Page = new Page($count, 10);
        $show = $Page->show();
        $p2pOrders = M('P2pOrders')->where($where)->order('id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $type = 'received';
        $data['p2pOrders']=$p2pOrders;
        $data['page']=$show;
        $data['type']=$type;
        $this->updateLastSeen();
        $this->ajaxShow($data);
    }

    public function paymentsettings()
    {
		$uid = $this->userid();

        $UserBankType = M('UserBankType')->where(['status' => 1])->order('id desc')->select();
        $data['UserBankType']=$UserBankType;


        //$truename = M('User')->where(array('id' => $uid))->getField('truename');
        $user = M('User')->where(array('id' => $uid))->find();

        if (!kyced()) {
            $this->error('Please complete your KYC');
        }

        $truename = $user['firstname'] . ' ' . $user['lastname'];

        $data['truename']=$truename;
        //$UserBank = M('UserBank')->where(array('userid' => $uid, 'status' => 1))->order('id desc')->limit(1)->select();
        $UserBank = M('UserBank')->where(['userid' => $uid, 'status' => 1])->order('id desc')->select();
        $FiatList = $this->FiatList();
        $payment_methods = M('P2pMethods')->where(['status' => 1])->order('id desc')->select();
        $user_methods = M('P2pUserMethods')->where(['status' => 1,'userid'=>$uid])->order('id desc')->select();
        
        $data['user_methods']=$user_methods;
        $data['payment_methods']=$payment_methods;
        $data['FiatList']=$FiatList;
        $data['UserBank']=$UserBank;


        $this->ajaxShow($data);
    }
    public function user_add_method()
    {
        $uid = $this->userid();
        if (!$uid) {
            $this->error(L('PLEASE_LOGIN'));
        }
        $method=I('post.method','','text');
        $information=htmlspecialchars(I('post.information','','text'), ENT_QUOTES, 'UTF-8');
        if (!check($method, 'a')) {
            $this->error(L('Method incorrect!'));
        }



        if (!$information) {
            $this->error(L('Account information missing!'));
        }


        if (!M('P2pMethods')->where(['unique_identifier' => $method])->find()) {
            $this->error(L('Incorrect Payment Method!'));
        }

            $p2p_user_methods = M('P2pUserMethods')->where(['userid' => $uid,'method'=>$method,'information'=>$information])->find();
            if ($p2p_user_methods['information'] == $information) {
                $this->error(L('Payment Method already exists!'));
            }
            $add_array=['userid' => $uid, 'method' => $method , 'information' => $information, 'addtime' => time(), 'status' => 1];
         $add_user_method=M('P2pUserMethods')->add($add_array);   
        if ($add_user_method) {
            $this->success(L('Method added successfully!'));
        } else {
            $this->error(L('Method Add Failed!'));
        }
    }
    
    public function doAddBank()
    {
        $uid = $this->userid();
        $bank=I('post.bank','','text');
        $bankaddr=htmlspecialchars(I('post.bic','','text'), ENT_QUOTES, 'UTF-8');
        $bankcard=I('post.account_number',0,'int');
        if (!check($bank, 'mostregex')) {
            $this->error(L('Bank malformed!'));
        }


        if (!check($bankaddr, 'mostregex')) {
            $this->error(L('Bank address format error!'));
        }

        if (!check($bankcard, 'd')) {
            $this->error(L('Bank account number format error!'));
        }

        if (!M('UserBankType')->where(['title' => $bank])->find()) {
            //$this->error(L('Bank error!'));
        }

        $userBank = M('UserBank')->where(['userid' => $uid])->select();

        foreach ($userBank as $k => $v) {
            if ($v['bankcard'] == $bankcard) {
                $this->error(L('Bank card number already exists!'));
            }
        }

        if (20 <= count($userBank)) {
            $this->error('Each user can add upto 20 accounts max!');
        }

        if (M('UserBank')->add(['userid' => $uid, 'name' => $uid . '_' . time(), 'bank' => $bank, 'bankprov' => 'NA', 'bankcity' => 'NA', 'bankaddr' => $bankaddr, 'bankcard' => $bankcard, 'addtime' => time(), 'status' => 1,'paytype'=>1])) {
            $this->success(L('Banks added successfully!'));
        } else {
            $this->error(L('Bank Add Failed!'));
        }
    }

    public function newad()
    {
        $uid = $this->userid();
        
        $userinfo = M('User')->where(['id' => $uid])->find();

        $merchant_info = M('p2p_merchants')->where(['uid' => $uid, 'status' => 1])->find();
        if ($userinfo['is_merchant'] == 0 || $merchant_info['status'] != 1) {
            $this->error('Please apply as merchant');
        }
        $allow_cryptos = self::allowed_cryptos;
        $FiatList = $this->FiatList();
        $release_times = self::release_time;
        $rt_go = [];
        foreach ($release_times as $rt) {

            $release_time['value'] = $rt;
            if ($rt < 60) {
                $release_time['type'] = 'm';
                $release_time['title'] = $rt;
            } else {
                $release_time['title'] = bcdiv($rt, 60, 1);
                $release_time['type'] = 'h';
            }
            $rt_go[] = $release_time;
        }
        if($uid>0){
            $user_payment_methods = M('P2pUserMethods')->where(['status' => 1,'userid'=>$uid])->order('id desc')->select();
        }else{
            $user_payment_methods =[];
        }
        $SellMethods = M('UserBank')->where(['userid' => $uid, 'status' => 1])->order('id desc')->select();
        $combine_sell['bank']=$SellMethods;
        $combine_sell['other']=$user_payment_methods;
        
        $BuyMethods = M('P2pMethods')->where(['status' => 1])->order('id desc')->select();
        $data['allow_cryptos']=$allow_cryptos;
        $data['release_time']=$rt_go;
        $data['SellMethods']=$combine_sell;
        $data['BuyMethods']=$BuyMethods;
        $data['FiatList']=$FiatList;

        $this->ajaxShow($data);
    }

    public function doNewAd()
    {
        
        $uid = $this->userid();
        if (!$uid) {
            $this->error(L('PLEASE_LOGIN'));
        }
		if (!kyced()) {
            $this->error(L('Please Verify Your Account before placing P2P Order'));
        }
        $request['fiat'] = I('post.fiat', '', 'text');
        $request['coin'] = I('post.coin', '', 'text');
        $request['price_type'] = I('post.price_type',0,'int');
        $request['fixed_price'] = I('fixed_price/f');
        $request['floating'] = I('request.floating', 0, 'int');
        $request['available'] = I('request.available', 0.00, 'float');
        $request['ad_type'] = I('ad_type/d');
        $request['time_limit'] = I('time_limit/d');
        $request['min_limit'] = I('min_limit', 0.00, 'float');
        $request['max_limit'] = I('max_limit', 0.00, 'float');
        $request['terms'] = I('terms', null, 'text');
        $request['autoreply'] = I('autoreply', null, 'text');
        $request['cond_kyc'] = I('cond_kyc/d');
        $request['cond_reg'] = I('cond_reg/d');
        $request['cond_reg_ago'] = I('cond_reg_ago/d'); //days
        $request['cond_balance'] = I('cond_balance/d');
        $request['cond_min_bal'] = I('cond_min_bal', 0.00, 'float'); //min balance required
        $request['online'] = I('online/d'); //is ad online or offline right now
        $request['uid'] = $uid;

        $bankList = I('bankList');
        $otherList = I('otherList');
        
        $isValidCoin = isValidCoin($request['coin']);
        
        if (!$request['coin'] || !$isValidCoin) {
            $this->error('Please choose correct asset'.$request['coin']);
        }
        $isValidFiat = isValidCoin($request['fiat']);
        if (!$request['fiat'] || !$isValidFiat) {
            $this->error('Please choose correct Fiat');
        }
        if ($request['price_type'] != 1 && $request['price_type'] != 2) {
            $this->error('Please choose correct type buy or sell');
        }
        if ($request['ad_type'] != 1 && $request['ad_type'] != 2) {
            
            $this->error('Please choose correct type fixed or floating');
        }
        if (!$request['floating'] && $request['ad_type'] != 1) {
            $request['floating'] = 0;
        }

        if (($request['floating'] < 50 || $request['floating'] > 200) && $request['ad_type'] != 1) {
            $this->error('Floating percentage can be between 50 to 200');
        }
        if ($request['available'] <= 0) {
            $this->error('Please enter total quantity!');
        }
        if ($request['cond_reg'] != 0 && $request['cond_reg'] != 1) {
            $this->error('Please choose correct condition for user registration duration');
        }
        if ($request['cond_kyc'] != 0 && $request['cond_kyc'] != 1) {
            $this->error('Please choose correct condition for kyc requirement');
        }
        if ($request['cond_balance'] != 0 && $request['cond_balance'] != 1) {
            $this->error('Please choose correct condition for balance requirement');
        }
        if ($request['online'] != 0 && $request['online'] != 1) {
            $this->error('Please choose either online or offline');
        }
        if ($request['cond_balance'] == 1 && $request['cond_min_bal'] < 0) {
            $this->error('Please choose correct amount for balance requirement');
        }
        if ($request['cond_reg'] == 1 && ($request['cond_reg_ago'] <= 0 || $request['cond_reg_ago'] >= 730)) {
            $this->error('Please choose correct number of days for user registration condition');
        }

		$totalamountfiat = $request['fixed_price'] * $request['available'];

        if ($request['min_limit'] > $totalamountfiat) {
            $this->error('Minimum Order Limit Should not exceed the total amount');
        }

        if (empty($bankList)) {
            $this->error('Please select atleast 1 payment method');
        }

        $userinfo = M('User')->where(['id' => $uid])->find();

        $merchant_info = M('p2p_merchants')->where(['uid' => $uid, 'status' => 1])->find();
        if ($userinfo['is_merchant'] == 0 || $merchant_info['status'] != 1) {
            $this->error('Please apply for merchant account');
        }
        $request['name'] = $merchant_info['name'] ?: username();
        if ((int)$request['ad_type'] == 2) {
            $funds = $this->findFunds($request['coin'],$uid);
            if ($funds < $request['available']) {
                $this->error('Your P2P account has only ' . $request['coin'] . ' ' . $funds);
            }
        }
            $request['pay_methods'] =json_encode([]);
            $request['ad_methods']= json_encode($bankList);

        //$this->error(json_encode($request));

        // $Usermethods = M('UserBank')->where(['userid' => $uid, 'status' => 1])->select();


        $request['orderid'] = strtoupper(dechex(cardGenPublic($uid)));
        $request['status'] = 1;
        $request['created_at'] = $request['updated_at'] = time();
        $mo = M();
        $mo->startTrans();
        if ((int)$request['ad_type'] == 2) {
            $coin = strtolower($request['coin']);
            $total = $request['available'];

            $before_balance = $mo->table('codono_user_assets')->where(['uid' => $uid, 'coin' => $coin])->find();

            $rs[] = $mo->table('codono_user_assets')->where(['uid' => $uid, 'coin' => $coin])->setDec('balance', $total);
			$rs[] = $mo->table('codono_user_assets')->where(['uid' => $uid, 'coin' => $coin])->setInc('freeze', $total);
            $rs[] = $p2p_adid = $mo->table('codono_p2p_ads')->add($request);

            $after_balance = $mo->table('codono_user_assets')->where(['uid' => $uid, 'coin' => $coin])->find();

            $finance_hash = md5($p2p_adid . 'p2p_' . $uid . time());

            $rs[] = $mo->table('codono_finance')->add(['userid' => $uid, 'coinname' => $coin, 'num_a' => $before_balance['balance'], 'num_b' => $before_balance['freeze'], 'num' => $before_balance['balance'] + $before_balance['freeze'], 'fee' => $total, 'type' => $request['ad_type'], 'name' => 'p2p', 'nameid' => $p2p_adid, 'remark' => 'P2P Ad post', 'mum_a' => $after_balance['balance'], 'mum_b' => $after_balance['freeze'], 'mum' => $after_balance['balance'] + $after_balance['freeze'], 'move' => $finance_hash, 'addtime' => time(), 'status' => 1]);
        } else {
            //Ad type is buy
            //$coin = strtolower($request['coin']);
            //$total = $request['available'];
           
            $rs[] = $mo->table('codono_p2p_ads')->add($request);
        }
        if (strict_check_arr($rs)) {
            $mo->commit();
            $this->success('P2P ad has been placed!');
        } else {
            $mo->rollback();
            clog('p2p_doNewAd',$rs);
            $this->error('There were issues placing the order!');
        }
    }


    public function doEdit()
    {
        $uid = $this->userid();
        if (!$uid) {
            $this->error(L('PLEASE_LOGIN'));
        }
        if (!kyced()) {
            $this->error(L('Please Verify Your Account before placing P2P Order'));
        }
        $id = I('post.id', 0, 'int');
        $where['id'] = $id;
        $where['uid'] = $uid;
        $Listing = $ad_Listing = M('P2pAds')->where($where)->find();
        if (!$Listing || empty($Listing)) {
            $this->error(L('No such listing found'));
        }
        $the_type = 1;
        $request['fiat'] = I('post.fiat', '', 'text');
        $request['coin'] = I('post.coin', '', 'text');
        $request['price_type'] = I('post.price_type',0,'int');
        $request['fixed_price'] = I('fixed_price/f');
        $request['floating'] = I('post.floating', 0, 'int');
        $request['available'] = I('post.available', 0.00, 'float');
        $request['ad_type'] = I('ad_type/d');
        $request['time_limit'] = I('time_limit/d');
        $request['min_limit'] = I('min_limit', 0.00, 'float');
        $request['max_limit'] = I('max_limit', 0.00, 'float');
        $request['terms'] = I('terms', null, 'text');
        $request['autoreply'] = I('autoreply', null, 'text');
        $request['cond_kyc'] = I('cond_kyc/d');
        $request['cond_reg'] = I('cond_reg/d');
        $request['cond_reg_ago'] = I('cond_reg_ago/d'); //days
        $request['cond_balance'] = I('cond_balance/d');
        $request['cond_min_bal'] = I('cond_min_bal', 0.00, 'float'); //min balance required
        $request['online'] = I('online/d'); //is ad online or offline right now
        $request['uid'] = $uid;
        $request['cond_kyc'] = $request['cond_kyc'] ? 1 : 0;
        $request['cond_reg'] = $request['cond_reg'] ? 1 : 0;
        $request['cond_balance'] = $request['cond_balance'] ? 1 : 0;


        $input = $request;

        $difference = 0;
        $action = null;
        if ($ad_Listing['available'] > $input['available']) {
            $action = 'small';
            $difference = bcsub($ad_Listing['available'], $input['available'], 8);
        }
        if ($ad_Listing['available'] < $input['available']) {
            $action = 'large';
            $difference = bcsub($input['available'], $ad_Listing['available'], 8);
        }

	 /*  
        foreach ($Listing as $key => $val) {
            if ($val == $request[$key]) {
                unset($request[$key]);
                unset($Listing[$key]);
            }
        }
           */
        if (empty($request)) {
            $this->success(L('No changes made'));
        }

        $bankList = I('bankList');
		$otherList = I('otherList');							

        if ($request['coin']) {
            $isValidCoin = isValidCoin($request['coin']);
            if (!$isValidCoin) {
                $this->error('Please choose correct asset');
            }
        }
        if ($request['fiat']) {
            $isValidFiat = isValidCoin($request['fiat']);
            if (!$isValidFiat) {
                $this->error('Please choose correct Fiat');
            }
        }

        if ($request['price_type'] && $request['price_type'] != 1 && $request['price_type'] != 2) {
            $this->error('Please choose correct type buy or sell');
        }
/*
        if ($request['ad_type'] && $request['ad_type'] != 1 && $request['ad_type'] != 2) {
            $this->error('Please choose correct type fixed or floating');
        }
        if ($request['floating'] && !$request['floating'] && $request['ad_type'] != 1) {
            $request['floating'] = 0;
        }
*/
        if ($request['floating'] && ($request['floating'] < 50 || $request['floating'] > 200)) {
            $this->error('Floating percentage can be between 50 to 200');
        }
        if ($request['available'] && $request['available'] <= 0) {
            $this->error('Please enter total quantity!');
        }
        if (isset($request['cond_reg'])) {
            if ($request['cond_reg'] != 0 && $request['cond_reg'] != 1) {
                $this->error('Please choose correct condition for user registration duration');
            }
        }
        $totalamountfiat = $request['fixed_price'] * $request['available'];

        if ($request['min_limit'] > $totalamountfiat) {
            $this->error('Minimum Order Limit Should not exceed the total amount');
        }

        if (empty($bankList)) {
            $this->error('Please select atleast 1 payment method');
        }

        if (isset($request['cond_kyc'])) {
            if ($request['cond_kyc'] != 0 && $request['cond_kyc'] != 1) {
                $this->error('Please choose correct condition for kyc requirement');
            }
        }
        if (isset($request['cond_balance'])) {
            if ($request['cond_balance'] != 0 && $request['cond_balance'] != 1) {
                $this->error('Please choose correct condition for balance requirement');
            }
        }
        if (isset($request['online'])) {
            if ($request['online'] != 0 && $request['online'] != 1) {
                $this->error('Please choose either online or offline');
            }
        }
        if (isset($request['cond_min_bal'])) {
            if ($request['cond_min_bal'] < 0) {
                $this->error('Please choose correct amount for balance requirement');
            }
        }
        if (isset($request['cond_reg'])) {
            if ($request['cond_reg'] == 1 && ($request['cond_reg_ago'] <= 0 || $request['cond_reg_ago'] >= 730)) {
                $this->error('Please choose correct number of days for user registration condition');
            }
        }
        $userinfo = M('User')->where(array('id' => $uid))->find();
        $merchant_info = M('p2p_merchants')->where(['uid' => $uid, 'status' => 1])->find();
        if ($userinfo['is_merchant'] == 0 || $merchant_info['status'] != 1) {
            $this->error('Please apply for merchant account');
        }
        $request['name'] = $merchant_info['name'] ?: username();
    /*
    if ($request['ad_type'] == 2) {
            $funds = $this->findFunds($request['coin'],$uid);
            if ($funds < $request['available']) {
                $this->error('Your P2P account has only ' . $request['coin'] . ' ' . $funds);
            }
        }
	*/
        //  $Usermethods = M('UserBank')->where(['userid' => $uid, 'status' => 1])->select();
        $request['pay_methods'] = json_encode($this->validateUserMethods($uid, $bankList));
            
        $request['status'] = 1;
        $request['updated_at'] = time();

        $coin = strtolower($request['coin']);
        $total = $request['available'];

        $mo = M();
        $mo->startTrans();
        if ($request['ad_type'] == 2) {

            $before_balance = $mo->table('codono_user_assets')->where(['uid' => $uid, 'coin' => $coin])->find();

            if ($difference > 0 && $action == 'small') {
                $the_type = 1;
                $rs[] = $mo->table('codono_user_assets')->where(['uid' => $uid, 'coin' => $coin])->setDec('freeze', $difference);
                $rs[] = $mo->table('codono_user_assets')->where(['uid' => $uid, 'coin' => $coin])->setInc('balance', $difference);
            }
            if ($difference > 0 && $action == 'large') {
                $the_type = 2;
                $rs[] = $mo->table('codono_user_assets')->where(['uid' => $uid, 'coin' => $coin])->setDec('balance', $difference);
                $rs[] = $mo->table('codono_user_assets')->where(['uid' => $uid, 'coin' => $coin])->setDec('freeze', $difference);
            }
            $up_where['id'] = $p2p_adid = $request['id'];
            $up_where['coin'] = $coin;
            $up_where['status'] = 1;

            $rs[] = $mo->table('codono_p2p_ads')->where($up_where)->save($request);

            $after_balance = $mo->table('codono_user_assets')->where(['uid' => $uid, 'coin' => $coin])->find();

            $finance_hash = md5($p2p_adid . 'p2p_update_' . $uid . time());

            $rs[] = $mo->table('codono_finance')->add(['userid' => $uid, 'coinname' => $coin, 'num_a' => $before_balance['balance'], 'num_b' => $before_balance['freeze'], 'num' => $before_balance['balance'] + $before_balance['freeze'], 'fee' => $total, 'type' => $the_type, 'name' => 'p2p', 'nameid' => $p2p_adid, 'remark' => 'P2P Edit post', 'mum_a' => $after_balance['balance'], 'mum_b' => $after_balance['freeze'], 'mum' => $after_balance['balance'] + $after_balance['freeze'], 'move' => $finance_hash, 'addtime' => time(), 'status' => 1]);

            $up_where['id'] = $request['id'];
            $up_where['coin'] = $coin;
            $up_where['status'] = 1;
            $request['ad_methods']= json_encode($bankList);
            $request['pay_methods'] =json_encode([]);
            $rs[] = $mo->table('codono_p2p_ads')->where($up_where)->save($request);														   
        } else {
            //Ad type is buy

            $up_where['id'] = $request['id'];
            $up_where['coin'] = $coin;
            $up_where['status'] = 1;
			$request['ad_methods']= json_encode($bankList);
            $request['pay_methods'] =json_encode([]);
			$rs[] = $mo->table('codono_p2p_ads')->where($up_where)->save($request);
        }
        if (check_arr($rs)) {
            $mo->commit();

            $this->success('P2P ad has been updated!');
        } else {
            $mo->rollback();

            $this->error('There were issues updating the order!');
        }
    }

    private function is_json($str)
    {
        return json_decode($str) != null;
    }

    private function validateUserMethods($userid, $selected): array
    {
        $Usermethods = M('UserBank')->where(['userid' => $userid, 'status' => 1])->select();
        $good_ids = [];
        foreach ($Usermethods as $key => $val) {
            foreach ($selected as $sel) {
                if ($val['id'] === strtoupper($sel)) {
                    $good_ids[] = $val['id'];
                }
            }
        }
        return $good_ids;
    }
    public function testme(){
        $p2p_methods = M('P2pMethods')->where(['status' => 1])->field('id,unique_identifier')->select();
        $find_identifier = ['paypal', 'upi'];

        // Initialize an array to hold the IDs of matching methods
        $matching_ids = [];

        // Loop through the $p2p_methods array
        foreach ($p2p_methods as $method) {
            // Check if the unique_identifier of the current method is in the $find_identifier array
            if (in_array($method['unique_identifier'], $find_identifier)) {
                // If a match is found, add the id of the method to the $matching_ids array
                $matching_ids[] = $method['id'];
            }
        }
        $merg=array_merge([1],$matching_ids);
        // Print the matching IDs
        print_r(array_unique($merg));
    }

    private function validateP2pPayMethods($userid, $selected_methods): array
    {
        $user_methods = M('P2pUserMethods')->where(['userid' => $userid, 'status' => 1])->select();
        $p2p_methods = M('P2pMethods')->where(['status' => 1])->field('id,unique_identifier')->select();

        $methods_array = [];
        foreach ($user_methods as $key => $val) {
            foreach ($selected_methods as $sel) {
                if ($val['id'] === $sel) {
                    $methods_array['ids'][] = $val['id'];
                    $methods_array['methods'][]= $val['method'];
                }
            }
        }
        // Initialize an array to hold the IDs of matching methods
        $matching_ids = [];

        // Loop through the $p2p_methods array
        foreach ($p2p_methods as $method) {
            // Check if the unique_identifier of the current method is in the $find_identifier array
            if (in_array($method['unique_identifier'], $methods_array['methods'])) {
                // If a match is found, add the id of the method to the $matching_ids array
                $matching_ids[] = $method['id'];
            }
        }
        return [$methods_array['ids'],array_unique($matching_ids)];
    }


    private function searchBySymbol($symbol, $array)
    {
        foreach ($array as $key => $val) {
            if ($val['symbol'] === strtoupper($symbol)) {
                return $val['price_usd'];
            }
        }
        return null;
    }

    public function grabPrice($crypto = 'btc', $fiat = 'usdt')
    {
        $uid = $this->userid();
        $cmcs = (APP_DEBUG ? null : S('cmcrates'));
        $uc_crypto = strtoupper($crypto);
        $lc_crypto = strtolower($crypto);
        $uc_fiat = strtoupper($fiat);
        $lc_fiat = strtolower($fiat);
        if (!$cmcs) {
            $cmcs = M('Coinmarketcap')->field(['symbol', 'price_usd'])->select();
            S('cmcrates', $cmcs);
        }


        $price_in_usd = $this->searchBySymbol($uc_crypto, $cmcs);
        if ($uc_fiat != 'USD') {
            $fiat_to_usd = $this->searchBySymbol($uc_fiat, $cmcs);
        } else {
            $fiat_to_usd = 1;
        }

        $crypto_to_fiat_price = bcdiv($price_in_usd, $fiat_to_usd, 2);
        $crypto_to_fiat_price = $crypto_to_fiat_price ?: 0;
        if (!$crypto_to_fiat_price) {
            $data['status'] = 0;
        } else {
            $data['status'] = 1;
        }
        $data['userid'] = $uid;
        $data['value'] = $crypto_to_fiat_price;
        $data['crypto'] = $uc_crypto;
        $data['fiat'] = $uc_fiat;
        $data['symbol'] = $this->fiatSymbol($uc_fiat);
        $data['balance'] = $this->findFunds($uc_crypto,$uid);
        $this->ajaxShow($data);
    }

    private function fiatSymbol($fiat)
    {
        $fiat = strtolower($fiat);
        $symbolList = ['btc' => '&#8383;', 'usd' => '&#36;', 'cent' => '&#162;', 'pound' => '&#163;', 'eur' => '&#8364;', 'yen' => '&#165;', 'inr' => '&#8377;', 'rub' => '&#8381;', 'yuan' => '&#20803;', 'ngn' => '&#8358;', 'pts' => '&#8359;', 'rup' => '&#8360;', 'won' => '&#8361;', 'sheq' => '&#8362;', 'dong' => '&#8363;', 'kip' => '&#8365;', 'mnt' => '&#8366;', 'php' => '&#8369;', 'try' => '&#8378;', 'azn' => '&#8380;', 'thb' => '&#3647;', 'irr' => '&#65020;'];
        $symbol = $symbolList[$fiat];
        if (!$symbol) {
            $symbol = strtoupper(substr($fiat, 0));
        }
        return $symbol;

    }

    public function myads()
    {
        $uid = $this->userid();

        $type = I('request.type', null, 'string'); //buy/sell
        $coin = I('request.coin', null, 'string');
        $status = I('request.status', null, 'string'); //online or offline


        if ($status && ($status == 'online' || $status == 'offline')) {
            if ($status == 'online') {
                $where['online'] = 1;
            }
            if ($status == 'offline') {
                $where['online'] = 0;
            }
        }
        if ($type == 'buy') {
            $where['ad_type'] = 1;
        }
        if ($type == 'sell') {
            $where['ad_type'] = 2;
        }
        $where['uid'] = $uid;
        if ($coin) {
            $where['coin'] = $coin;
        }
        $where['status'] = 1;

        $Model = M('P2pAds');
        $count = $Model->where($where)->count();
        $Page = new Page($count, 10);
        $show = $Page->show();


        $ads = $Model->where($where)->order('id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();
		$ads_data = [];
        $FiatMethods = $this->getP2pPaymentMethods();
        foreach ($ads as $ad) {
            $remainingfiat = $ad['fixed_price'] * $ad['available'];
            if ($remainingfiat < $ad['min_limit']) {
                $Model->where(['id' => $ad['id']])->save(['online' => 0]);
                continue;
            }
            $rateInfo=$this->rateInfo($ad['uid']);
           
            $ads_data[$ad['id']] = $ad;
            $ads_data[$ad['id']]['orders'] = $rateInfo['orders']?:0; // Example value, adjust as necessary
            $ads_data[$ad['id']]['completion'] = $rateInfo['completion']?:100; // Example value, adjust as necessary
            $ads_data[$ad['id']]['method'] = [];
            // Check if 'ad_methods' exists, is not empty, and is a valid JSON
            if (!empty($ad['ad_methods']) && is_string($ad['ad_methods'])) {
                $decodedMethods = json_decode($ad['ad_methods'], true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decodedMethods)) {
                    foreach ($FiatMethods as $key => $method) {
                        foreach ($decodedMethods as $meth) {
                            if ($meth == $key) {
                                $ads_data[$ad['id']]['method'][] = $method['name'];
                            }
                        }
                    }
                }
            }
        }

        $data['myads']=$ads_data;

        $allow_cryptos = $this->allowed_cryptos();
        $data['allow_cryptos']=$allow_cryptos;
        $data['page']=$show;
        $this->ajaxShow($data);
    }

    public function closelisting()
    {
        $uid = $this->userid();
        if (!$uid) {
            $this->error(L('PLEASE_LOGIN'));
        }
        
        $orderid = I('post.ad_number', 0, 'string');

        if (!$orderid || $orderid == 0) {
            $this->error(L('No such listing found'));
        }

        $where['uid'] = $uid;
        $where['orderid'] = $orderid;
        $where['status'] = 1;


        $ad = M('P2pAds')->where($where)->find();

        $coin = strtolower($ad['coin']);
        $total = $ad['available'];
        if (!$ad || empty($ad)) {
            $this->error(L('No such listing found'));
        }

        $rs = [];
        $mo = M();
        $mo->startTrans();
        $ad = M('P2pAds')->where($where)->find();

        $coin = strtolower($ad['coin']);
        $total = $ad['available'];
        $before_balance = $mo->table('codono_user_assets')->where(['uid' => $uid, 'coin' => $coin])->find();

        //move p2p_ads entry 'available' to 'freeze'
        //refund if the funds were frozen for sell order
        if ($ad['ad_type'] == 2) {
            $rs[] = $mo->table('codono_p2p_ads')->where(['id' => $ad])->setDec('available', $total);
            $rs[] = $mo->table('codono_user_assets')->where(['uid' => $uid, 'coin' => $coin])->setInc('balance', $total);
			$rs[] = $mo->table('codono_user_assets')->where(['uid' => $uid, 'coin' => $coin])->setDec('freeze', $total);
        }
        $rs[] = M('P2pAds')->where($where)->save(['online' => 0, 'status' => 0]);

        if (check_arr($rs)) {
            $mo->commit();

            $this->success(L('Success'));
        } else {
            $mo->rollback();

            $this->error('There were issues placing the order!');
        }

    }

    private function allowed_cryptos()
    {
        $allowed_cryptos = [];
        foreach (self::allowed_cryptos as $base) {
            $base = strtolower($base);
            $allowed_cryptos[$base]['image'] = '/Upload/coin/' . c('coin')[$base]['img'];
            $allowed_cryptos[$base]['symbol'] = $base;
            $allowed_cryptos[$base]['title'] = c('coin')[$base]['title'];
            $allowed_cryptos[$base]['min'] = 1;
            if ($base == 'usdt' || $base == 'usd') {
                $max_allowed_base = 1000000;
            } else {
                $max_allowed_base = 5000000;
            }
            $allowed_cryptos[$base]['max'] = $max_allowed_base;
        }
        return $allowed_cryptos;
    }

 

    public function faq()
    {
        $name = self::article_category;
        $Articletype = M('ArticleType')->where(['name' => $name])->find();
        $main_title = $Articletype['title'];
        $pname = $Articletype['name'];
        $SubArticletypes = M('ArticleType')->where(['shang' => $pname])->select();
        $i = 0;
        $list = [];
        foreach ($SubArticletypes as $sub) {

            $list[$i]['category'] = $sub['name'];
            $list[$i]['id'] = $sub['id'];

            $list[$i]['data'] = M('Article')->where(['status' => 1, 'type' => $sub['name']])->select();
            $i++;
        }
        $data['main_title']=$main_title;
        $data['pname']=$pname;
        $data['list']=$list;
        $this->ajaxShow($data);
    }

    public function del_user_method()
    {
        $id=I('get.id',0,'intval');
        $uid = $this->userid();
        if (!$uid) {
            $this->error(L('PLEASE_LOGIN'));
        }
        if (!check($id, 'd')) {
            $this->error(L('INCORRECT_REQ'));
        }

        if (!M('P2pUserMethods')->where(['userid' => $uid, 'id' => $id])->find()) {
            $this->error(L('Unauthorized access!'));
        } elseif (M('P2pUserMethods')->where(['userid' => $uid, 'id' => $id])->delete()) {
            $this->success(L('Successfully deleted!'));
        } else {
            $this->error(L('Failed to delete!'));
        }
    }
    public function delbank($id)
    {
        $uid = $this->userid();
        if (!$uid) {
            $this->error(L('PLEASE_LOGIN'));
        }

        if (!check($id, 'd')) {
            $this->error(L('INCORRECT_REQ'));
        }


        if (!M('UserBank')->where(['userid' => $uid, 'id' => $id])->find()) {
            $this->error(L('Unauthorized access!'));
        } elseif (M('UserBank')->where(['userid' => $uid, 'id' => $id])->delete()) {
            $this->success(L('successfully deleted!'));
        } else {
            $this->error(L('failed to delete!'));
        }
    }

    private function getP2pPaymentMethods()
    {
        $export = [];
        $data = (APP_DEBUG ? null : S('getP2pPaymentMethods'));
        if ($data == null) {
            $infos = M('P2pMethods')->where(['status' => 1])->order('sort asc,name asc')->select();
            foreach ($infos as $info) {
                $export[$info['id']] = $info;
            }
            $data = $export;
        }
        return $data;
    }
    private function rateInfo($uid){
        $orderCount = M('P2pOrders')->where([ 'merchant_id' => $uid])->count();
        $completedOrdersCount = M('P2pOrders')->where([ 'merchant_id' => $uid, 'status' => 4])->count();
        $completionRate = ($orderCount > 0) ? ($completedOrdersCount / $orderCount) * 100 : 0;
        return ['orders'=>$orderCount,'completion'=>round($completionRate,0)];
    }
    public function cryptosList()
    {
        $this->ajaxShow(self::allowed_cryptos);
    }
}