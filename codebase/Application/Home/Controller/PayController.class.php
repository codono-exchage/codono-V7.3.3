<?php

namespace Home\Controller;

use Omnipay\Omnipay;
use Think\Page;


class PayController extends HomeController
{

    public function index($coin = NULL)
    {
        $userid= userid();
        if (!$userid) {
            $this->error(L('PLEASE_LOGIN'));
        }
        $coin = $coin ?: C('xnb_mr');
        if (C('coin')[$coin]) {
            $explorer = C('coin')[$coin]['js_wk'];
            $coin = trim($coin);
        } else {
            $coin = C('xnb_mr');
        }

        $this->assign('xnb', $coin);
        $Coininfo = M('Coin')->where(array(
            'status' => 1,
            'name' => array('neq', 'usd')
        ))->select();
        $coin_list=[];
        foreach ($Coininfo as $k => $v) {
            if ($v['symbol']) {
                continue;
            }
            $coin_list[$v['name']] = $v;
        }
        $cmcs = (APP_DEBUG ? null : S('cmcRates'));

        if (!$cmcs) {
            $cmcs = M('Coinmarketcap')->field(['symbol', 'price_usd'])->select();
            S('cmcrates', $cmcs);
        }
        $conversion_coin = SYSTEMCURRENCY;
        
        $multiplier = 1;
        $the_cms = [];
        $cms = [];
        foreach ($cmcs as $ckey => $cval) {
            if (strtolower($conversion_coin) != 'usd' && $cval['symbol'] == strtoupper($conversion_coin)) {
                $multiplier = $cval['price_usd'];
            }
            $the_cms[strtolower($cval['symbol'])] = $cval['price_usd'];
        }

        foreach ($the_cms as $key => $usd_value) {
            $cms[$key] = bcdiv($usd_value, $multiplier, 8);
        }

        $this->assign('coin_list', $coin_list);
        $user_coin = M('UserCoin')->where(array('userid' => $userid))->find();
        $user_coin['converted'] = bcmul($user_coin[$coin], $cms[$coin], 8);
        $user_coin['conversion_coin']=SYSTEMCURRENCY;
        $user_coin[$coin] = format_num($user_coin[$coin], 6);
        $is_ga = ($this->userinfo['ga'] ? 1 : 0);
        $this->assign('is_ga', $is_ga);

        $this->assign('user_coin', $user_coin);

        $where['from_userid'] = $userid;
        $where['coinname'] = $coin;
        $where['status'] = 1;
        $Model = M('Paybyemail');
        $count = $Model->where($where)->count();
        $Page = new Page($count, 10);
        $show = $Page->show();
        $list = $Model->where($where)->order('id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->display();
    }
    public function receive(){
        $uid=userid();
        if (!$uid) {
            $this->error(L('PLEASE_LOGIN'));
        }
        $userinfo=$this->userinfo;
        
        $where['to_userid'] = $uid;
        
        $where['status'] = 1;
        $Model = M('Paybyemail');
        $count = $Model->where($where)->count();
        $Page = new Page($count, 10);
        $show = $Page->show();
        $list = $Model->where($where)->order('id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();
        

        $from_user_ids = array_column($list, 'from_userid');

        // Fetch invite codes from 'codono_user' table based on 'from_userid'.
        $UserModel = M('User');
        $usersWithInvite = $UserModel->where(['id' => ['in', $from_user_ids]])->getField('id, invit');
        // Add invitation code to each item in $list.
        foreach ($list as &$item) {
            $item['sender'] = isset($usersWithInvite[$item['from_userid']]) ? $usersWithInvite[$item['from_userid']] : null;
        }

        
        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->assign('email',$userinfo['email']);
        $this->assign('invit',$userinfo['invit']);
        $this->display();
    }
    private function paywall($mycz)
    {
        if (!userid()) {
            $this->error(L('PLEASE_LOGIN'));
        }
        $email = $this->userinfo['email'];//M('User')->where(array('id' => userid()))->getField("email");
        $paymentwall = paymentwall(userid(), $email, $mycz);
        echo "<iframe src='$paymentwall' width='100%' height='100%'/>";
    }

    public function pbe_requestOTP($coinname, $address, $amount,$paymethod)
    {
        $input=I('post.');
        $coinname=$input['coinname'];
        $address=$input['address'];
        $payid=$input['payid'];
        $amount=$input['amount'];

        $uid=userid();
        if (!$uid) {
            $this->error(L('Please login first!'));
        }
        $coinname=strtolower($coinname);
        if (!check($coinname, 'n') || !isValidCoin($coinname)){
            $this->error(L('Please Select valid coin!'));
        }
        if (!in_array($paymethod,['email','payid'])){
            $this->error(L('Please Select Valid receiver!'));
        }
        
         //M('User')->where(array('id' => userid()))->find();
        if (time() < (session('pbe_requestOTP_last_' . $uid) + 30)) {
            $this->error('Too fast wait for 30 seconds before you proceed again!');
        }
        $user = $this->userinfo;
        $receiver_info=strtolower($address);    
        if ($receiver_info!='' && !check($receiver_info, 'mostregex')) {
            $this->error(L('Invalid Receivers details!').$receiver_info);
        }
        

        if($paymethod=='email'){
            $condition=['email' => $receiver_info, 'status' => 1];
        }else{
            $condition=['invit' => $receiver_info, 'status' => 1];
        }
        $receiver_id = M('User')->where($condition)->getField('id');
        if ($receiver_id == $uid) {
            $this->error(L('You can not send to funds your self!'));
        }
        if (!$receiver_id) {
            $this->error(L('No such active user found with '.$paymethod.':'.$receiver_info));
        }
        $user_coin =  M('UserCoin')->where(array('userid' => $uid))->find();

        if ($user_coin[$coinname] < $amount) {
            $this->error(L('Insufficient funds available'));
        }
        $code = tradeno();
        session('pbe_requestOTP', $code);
        session('pbe_requestOTP_last_' . $uid, time());
        $email = $user['email'];
        $client_ip = get_client_ip();
        $requestTime = date('Y-m-d H:i', time()) . '(' . date_default_timezone_get() . ')';
        $subject = "Payment Request on " . SHORT_NAME;
        $content = "<br/><strong>DO NOT SHARE THIS CODE WITH ANYONE!!</strong><br/>To complete the transfer process,<br/><br/>You may be asked to enter this confirmation code:<strong>$code <strong><br/><br/><small><i>
			<table>
			<tr style='border:2px solid black'><td>Sender</td><td>$email</td></tr>
			<tr style='border:2px solid black'><td>IP</td><td>$client_ip</td></tr>
			<tr style='border:2px solid black'><td>Amount</td><td>$amount $coinname</td></tr>
			<tr style='border:2px solid black'><td>Receiver</td><td>$receiver_info</td></tr>
			<tr style='border:2px solid black'><td>Time</td><td>$requestTime</td></tr>	
			</table>
			<strong>If You did not request this Payment to above, immediately change passwords,and contact us</strong>";
        $ifSent=addnotification($email, $subject, $content);
        if($ifSent)
        {
            $this->success(L('Please check email for code'));
        }else{
            $this->error(L('Could not send OTP, Please try again later!'));
        }
        
    }

    public function doPaymentSend($otp , $coin, $num, $address, $paypassword,$paymethod)
    {
        $uid=userid();
        if (!$uid) {
            $this->error(L('YOU_NEED_TO_LOGIN'));
        }
        if (!kyced()) {
            $this->error(L('Complete KYC First!'));
        }
        if (!in_array($paymethod,['email','payid'])){
            $this->error(L('Please Select Valid receiver!'));
        }
        $amount = abs($num);
        
        if (!check($num, 'currency')) {
            $this->error(L('Number format error!'));
        }
        if (!$otp || $otp != session('pbe_requestOTP')) {
            $this->error('Incorrect OTP!');
        }

        if (!check($paypassword, 'password')) {
            $this->error(L('Fund Pwd format error!'));
        }

        if (!check($coin, 'n')) {
            $this->error(L('Currency format error!'));
        }

        if (!C('coin')[$coin]) {
            $this->error(L('Currency wrong!'));
        }

        $receiver_info=strtolower($address);    
        if ($receiver_info!='' && !check($receiver_info, 'mostregex')) {
            $this->error(L('Invalid Receivers details!'));
        }
        

        if($paymethod=='email'){
            $condition=['email' => $receiver_info, 'status' => 1];
        }else{
            $condition=['invit' => $receiver_info, 'status' => 1];
        }

        $CoinInfo = C('coin')[$coin];

        if (!$CoinInfo) {
            $this->error(L('Currency wrong!'));
        }

        $myzc_min = ($CoinInfo['zc_min'] ? abs($CoinInfo['zc_min']) : 0.0001);
        $myzc_max = ($CoinInfo['zc_max'] ? abs($CoinInfo['zc_max']) : 10000000);

        if ($amount < $myzc_min) {
            $this->error(L('Amount is less than Minimum Withdrawal Amount!'));
        }

        if ($myzc_max < $amount) {
            $this->error(L('Amount Exceeds Maximum Withdrawal Limit!'));
        }
        $flat_fee = $CoinInfo['zc_flat_fee'];
        $percent_fee = round(($amount / 100) * $CoinInfo['zc_fee'], 8);
        $fee = bcadd($flat_fee , $percent_fee,8);

        $to_be_sent = bcsub($amount, $fee, 8);

        if ($to_be_sent < 0) {
            $this->error(L('Incorrect withdrawal amount!'));
        }

        if ($fee < 0) {
            $this->error(L('Incorrect withdrawal fee!'));
        }

        $user = $this->userinfo;//M('User')->where(array('id' => userid()))->find();

        if (md5($paypassword) != $user['paypassword']) {
            $this->error(L('Trading password is wrong!'));
        }

        $user_coin = M('UserCoin')->where(array('userid' => $uid))->find();


        if ($user_coin[$coin] < $amount) {
            $this->error(L('Insufficient funds available'));
        }

        
        $mo = M();
        $peer_id = $mo->table('codono_user')->where($condition)->getField("id");
        $peer_email=$mo->table('codono_user')->where($condition)->getField("email");
        if ($peer_id > 0) {
            session('pbe_requestOTP',null);
            $peer_coin = M('UserCoin')->where(array('userid' => $peer_id))->find();
            $num_a = $user_coin[$coin];
            $num_b = $user_coin[$coin . 'd'];
            $num = bcadd($num_a, $num_b, 8);
            $mum_a = bcsub($num_a, $amount, 8);
            $mum_b = $num_b;
            $mum = bcadd($mum_a, $mum_b, 8);


            $peer_num_a = $peer_coin[$coin];
            $peer_num_b = $peer_coin[$coin . 'd'];
            $peer_num = bcadd($peer_num_a, $peer_num_b, 8);
            $peer_mum_a = bcadd($peer_num_a, $to_be_sent, 8);
            $peer_mum_b = $peer_num_b;
            $peer_mum = bcadd($peer_mum_a, $peer_mum_b, 8);


            $sender_email = $this->userinfo['email'];//M('User')->where(array('id' => userid()))->getField('email');
            
            $mo->startTrans();
            $rs = array();
            $txid = md5($address .$uid. $otp . time());
            
            $code = $otp;
            $rs[] = $mo->table('codono_user_coin')->where(array('userid' => $uid))->setDec($coin, $amount);
            $rs[] = $mo->table('codono_user_coin')->where(array('userid' => $peer_id))->setInc($coin, $to_be_sent);
            $add_array = array('from_userid' => $uid, 'to_userid' => $peer_id, 'code' => $code, 'email' => $address, 'coinname' => $coin, 'txid' => $txid, 'num' => $amount, 'fee' => $fee, 'mum' => $to_be_sent, 'addtime' => time(), 'status' => 1);
            $rs[] = $add_status = $mo->table('codono_paybyemail')->add($add_array);
            $rs[] = $mo->table('codono_myzr')->add(array('userid' => $peer_id, 'username' => $sender_email, 'coinname' => $coin, 'txid' => $txid, 'num' => $amount, 'fee' => $fee, 'mum' => $to_be_sent, 'addtime' => time(), 'status' => 1));
            //Peer entry
            $rs[] = $mo->table('codono_finance')->add(array('userid' => $peer_id, 'coinname' => $coin, 'num_a' => $peer_num_a, 'num_b' => $peer_num_b, 'num' => $peer_num, 'fee' => $to_be_sent, 'type' => 1, 'name' => 'emailpay', 'nameid' => $add_status, 'remark' => 'EmailPayments', 'move' => $txid, 'addtime' => time(), 'status' => 1, 'mum' => $peer_mum, 'mum_a' => $peer_mum_a, 'mum_b' => $peer_mum_b));
            //User entry
            $rs[] = $mo->table('codono_finance')->add(array('userid' => $uid, 'coinname' => $coin, 'num_a' => $num_a, 'num_b' => $num_b, 'num' => $num, 'fee' => $amount, 'type' => 2, 'name' => 'emailpay', 'nameid' => $add_status, 'remark' => 'EmailPayments', 'move' => $txid, 'addtime' => time(), 'status' => 1, 'mum' => $mum, 'mum_a' => $mum_a, 'mum_b' => $mum_b));

            //Withdrawal entry
            $rs[] = $aid = $mo->table('codono_myzc')->add(array('userid' => $uid, 'username' => $address, 'coinname' => $coin, 'txid' => $txid, 'num' => $amount, 'fee' => $fee, 'mum' => $to_be_sent, 'addtime' => time(), 'status' => 1));

            //Check Executed Withdrawal
            if (strict_check_arr($rs)) {

                $requestTime = date('Y-m-d H:i', time()) . '(' . date_default_timezone_get() . ')';
                $subject = "Payment Received on " . SHORT_NAME;
                $content = "<br/>You have just received a payment on your email,<br/>
			<table>
			<tr style='border:2px solid black'><td>From Email</td><td>$sender_email</td></tr>
			<tr style='border:2px solid black'><td>Coin</td><td>$coin</td></tr>
			<tr style='border:2px solid black'><td>Txid</td><td>$txid</td></tr>
			<tr style='border:2px solid black'><td>Sent Amount</td><td>$num</td></tr>
			<tr style='border:2px solid black'><td>Fee Amount</td><td>$fee</td></tr>
			<tr style='border:2px solid black'><td>Received Amount</td><td>$mum</td></tr>
			<tr style='border:2px solid black'><td>Time</td><td>$requestTime</td></tr>	
			</table>
			<strong>If You think this payment was sent in error contact us on support</strong>";
                addnotification($peer_email, $subject, $content);
                $mo->commit();
                $this->success('You have successfully sent the funds!');
            } else {
                $mo->rollback();
                $this->error(L('Due to some reasons payment could not be sent !'));
            }

        } else {
            $this->error('There is no such user, Please cross check with email address!');
        }
    }



    public function authorize($id)
    {
        if (!userid()) {
            redirect(U('Login/login'));
        }
        if (AUTHORIZE_NET['status'] == 0) {
            $this->error(L('Gateway is currently disabled'));
        }
        if (!check($id, 'idcard')) {
            $this->error(L('Invalid Attempt'));
        }
        $where = array('tradeno' => $id, 'userid' => userid(), 'status' => 0);
        $mycz = M('Mycz')->where($where)->find();
        if (!$mycz) {
            $this->error(L('Top-order does not exist!'));
        }
        $user_email = $this->userinfo['email'];//M('User')->where(array('id' => userid()))->getField('email');

        require_once(APP_PATH . CODEBASE_DIR.'/Framework/Library/Vendor/vendor/autoload.php');

        $dataDescriptor = I('post.dataDescriptor', '', 'text');
        $dataValue = I('post.dataValue', '', 'text');

        $gateway = Omnipay::create('AuthorizeNet_AIM');
        $gateway->setApiLoginId(AUTHORIZE_NET['loginid']);
        $gateway->setTransactionKey(AUTHORIZE_NET['transactionkey']);
        if (AUTHORIZE_NET['mode'] == "test") {
            //$gateway->setTestMode(true);
            $gateway->setDeveloperMode(true);
        } else {
            $gateway->setTestMode(false);
            $gateway->setDeveloperMode(false);
        }

        $request = $gateway->purchase(
            [
                'notifyUrl' => SITE_URL . 'Pay/webhook_authorize',
                'returnUrl' => SITE_URL . 'Pay/webhook_authorize',
                'invoiceNumber' => $mycz['tradeno'],
                'description' => 'UID_' . userid() . '_tradeno_' . $mycz['tradeno'],
                'customerId' => userid(),
                'email' => $user_email,
                'amount' => format_num($mycz['num'], 2),
                'opaqueDataDescriptor' => $dataDescriptor,
                'opaqueDataValue' => $dataValue]);

        $response = $request->send();

        // Payment was successful
        if ($response->isRedirect()) {
            // redirect to offsite payment gateway
            $response->redirect();
        } elseif ($response->isSuccessful()) {
            // payment was successful: update database

            $ipn_response = $response->getTransactionReference();
            $remark_obj = json_decode($response->getTransactionReference());
            $transid = $remark_obj->transId;
            $save_array = array('remark' => $transid, 'status' => 4, 'ipn_response' => $ipn_response);
            $rs = M('Mycz')->where(array('userid' => userid(), 'id' => $mycz['id'], 'tradeno' => $id))->save($save_array);
            echo '<div class="center"><h2>' . $response->getMessage() . '</h2> <br/> Close this window...</div>';

        } else {
            // payment failed: display message to customer
            echo $response->getMessage();
            echo '<div class="center">We could not process your payment, Please try again in sometime <br/> Close this window...</div>';

        }

    }

    public function CreatePayment_yoUganda($ref, $msisdn, $amount)
    {
        $msisdn = str_replace("+", "", $msisdn);
        $msisdn = str_replace(" ", "", $msisdn);
        $msisdn = str_replace("-", "", $msisdn);

        if (!preg_match("/^((\+*)((0[ -]+)*|(256)*)(\d{9}+))$/i", $msisdn)) {
            $this->error('Incorrect Phone number!' . $msisdn);
        }

        $narrative = username() . " recharge for " . $amount;
        $yoUganda = YoUganda();
        $yoUganda->set_external_reference($ref);
        $yoUganda->set_nonblocking("TRUE");
        $yoUganda->set_instant_notification_url(SITE_URL . 'IPN/YoUgandaSuccessIPN');
        $yoUganda->set_failure_notification_url(SITE_URL . 'IPN/YoUgandaFailedIPN');

        $response = $yoUganda->ac_deposit_funds($msisdn, $amount, $narrative);
        if ($response['Status'] == 'OK' && $response['StatusCode'] == '1' && $response['TransactionReference']) {
            $mo = M();
            $mycz = $mo->table('codono_mycz')->where(array('userid' => userid(), 'tradeno' => $ref))->find();
            $rs[] = $mo->table('codono_mycz')->where(array('id' => $mycz['id']))->save(array('status' => 3, 'remark' => $response['TransactionReference'], 'endtime' => time()));
            $this->success('Please complete payment and refresh the page.');
            redirect(U('Finance/mycz/coinname/ugx'));
        } else {
            $error = $response['StatusMessage'] ?: 'There were issues while making payment, Please try after sometime.';

            $this->error($error);
        }
        //Do something https://paymentsweb.yo.co.ug/
    }

    public function yoco($id, $token, $amount)
    {

        if (!userid()) {
            redirect('/Login/login');
        }
        if (YOCO_GATEWAY['status'] == 0 || !YOCO_GATEWAY['status']) {
            $this->error(L('Gateway is currently disabled'));
        }

        if (!check($id, 'idcard')) {
            $this->error(L('Invalid Attempt'));
        }
        $where = array('tradeno' => $id, 'userid' => userid(), 'status' => 0);
        $mycz = M('Mycz')->where($where)->find();
        if (!$mycz) {
            $this->error(L('Top-order does not exist!'));
        }
        //$user_email = M('User')->where(array('id' => userid()))->getField('email');


        $amountInCents = bcmul($amount, 100);

        if (YOCO_GATEWAY['mode'] == "sandbox") {
            //Do something
        } else {
            //this is live
        }
        $data = [
            'token' => $token, // Your token for this transaction here
            'amountInCents' => $amountInCents, // payment in cents amount here
            'currency' => 'ZAR' // currency here
        ];
        $secret_key = YOCO_GATEWAY['secret_key'];

        // Setup curl
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://online.yoco.com/v1/charges/");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_USERPWD, $secret_key);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        // send to yoco
        $result = curl_exec($ch);
        $response_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        // close the connection
        curl_close($ch);
        $response = json_decode($result);
        if ($response_status != 201) {
            echo '<div class="center">We could not process your payment, Please try again in sometime <br/> Close this window...</div>';
        }

        // convert response to a usable object

        if ($response->status == 'successful' && $response->amountInCents == $amountInCents && $response->object = 'charge' && $response->currency = 'ZAR') {
            $save_array = array('remark' => $response->chargeId, 'status' => 4, 'ipn_response' => $response->fingerprint);
            $rs = M('Mycz')->where(array('userid' => userid(), 'id' => $mycz['id'], 'tradeno' => $id))->save($save_array);
            //echo '<div class="center"><h2>Your transaction has been completed</h2> <br/> Close this window...</div>';
            redirect(U('Pay/mycz', array('id' => $mycz['id'])));
        } else {
            //echo '<div class="center"><h2>Please wait for sometime to let your transaction to be confirmed</h2> <br/> Close this window...</div>';
            redirect(U('Pay/mycz', array('id' => $mycz['id'])));
        }

    }


    public function webhook_authorize()
    {
		$postdata=I('post.');
        $content = json_encode($postdata);
        $filename = 'authorize_webhook_';
		clog($filename,$content);
        echo "Saved";
    }

    public function alipay()
    {
		$alipay=I('post.alipay',null,'string');
        if (IS_POST) {
            if (isset($alipay)) {
                $arr = explode('--', $alipay);

                if (md5('codono') != $arr[2]) {
                    echo -1;
                    exit();
                }

                $arr[0] = trim(str_replace(PHP_EOL, '', $arr[0]));
                $arr[1] = trim(str_replace(PHP_EOL, '', $arr[1]));

                if (strstr($arr[0], 'payment-')) {
                    $arr[0] = str_replace('payment-', '', $arr[0]);
                }

                $mycz = M('Mycz')->where(array('tradeno' => $arr[0]))->find();

                if (!$mycz) {
                    echo -3;
                    exit();
                }

                if (($mycz['status'] != 0) && ($mycz['status'] != 3)) {
                    echo -4;
                    exit();
                }

                if ($mycz['num'] != $arr[1]) {
                    echo -5;
                    exit();
                }

                $mo = M();
                
                $mo->startTrans();
                $rs = array();
                $finance = $mo->table('codono_finance')->where(array('userid' => $mycz['userid']))->order('id desc')->find();
                $finance_num_user_coin = $mo->table('codono_user_coin')->where(array('userid' => $mycz['userid']))->find();
                $rs[] = $mo->table('codono_user_coin')->where(array('userid' => $mycz['userid']))->setInc('usd', $mycz['num']);
                $rs[] = $mo->table('codono_mycz')->where(array('id' => $mycz['id']))->save(array('status' => 1, 'mum' => $mycz['num'], 'endtime' => time()));
                $finance_mum_user_coin = $mo->table('codono_user_coin')->where(array('userid' => $mycz['userid']))->find();
                $finance_hash = md5($mycz['userid'] . $finance_num_user_coin['usd'] . $finance_num_user_coin['usdd'] . $mycz['num'] . $finance_mum_user_coin['usd'] . $finance_mum_user_coin['usdd'] . CODONOLIC . 'auth.codono.com');
                $finance_num = $finance_num_user_coin['usd'] + $finance_num_user_coin['usdd'];

                if ($finance['mum'] < $finance_num) {
                    $finance_status = (1 < ($finance_num - $finance['mum']) ? 0 : 1);
                } else {
                    $finance_status = (1 < ($finance['mum'] - $finance_num) ? 0 : 1);
                }

                $rs[] = $mo->table('codono_finance')->add(array('userid' => $mycz['userid'], 'coinname' => 'usd', 'num_a' => $finance_num_user_coin['usd'], 'num_b' => $finance_num_user_coin['usdd'], 'num' => $finance_num_user_coin['usd'] + $finance_num_user_coin['usdd'], 'fee' => $mycz['num'], 'type' => 1, 'name' => 'mycz', 'nameid' => $mycz['id'], 'remark' => 'fiat_recharge_approved', 'mum_a' => $finance_mum_user_coin['usd'], 'mum_b' => $finance_mum_user_coin['usdd'], 'mum' => $finance_mum_user_coin['usd'] + $finance_mum_user_coin['usdd'], 'move' => $finance_hash, 'addtime' => time(), 'status' => $finance_status));

                if (check_arr($rs)) {
                    $mo->commit();
                    
                    echo 1;
                    exit();
                } else {
                    $mo->rollback();
                    $mo->query('rollback');
                    echo -6;
                    exit();
                }
            }
        }
    }

    public function mycz($id = NULL)
    {
        if (check($id, 'd')) {
            $mycz = M('Mycz')->where(array('id' => $id))->find();
            
            if (!$mycz) {
                $this->redirect('Finance/mycz');
            }

            $myczType = M('MyczType')->where(array('id' => $mycz['type']))->find();

            if ($myczType['name'] == 'paymentwall') {
                $this->paywall($mycz);
            }


            if ($mycz['type'] == 'bank') {
                $UserBankType = M('UserBankType')->where(array('status' => 1))->order('id desc')->select();
                $this->assign('UserBankType', $UserBankType);
            }

            $this->assign('myczType', $myczType);
            $this->assign('mycz', $mycz);

            if ($myczType['type'] == 'bank') {
                $this->display('bank');
            } else {
                $this->display($myczType['type']);
            }
        } else {
            $this->redirect('Finance/mycz');
        }
    }

    public function ugxWithdrawal($cellphone, $num, $paypassword)
    {
        $status = YO_Uganda['status'];
        if ($status != '1') {
            $this->error('Ugx Payments is currently not available');
        }
        $cellphone = str_replace("+", "", $cellphone);
        $cellphone = str_replace(" ", "", $cellphone);
        $cellphone = str_replace("-", "", $cellphone);

        if (!preg_match("/^((\+*)((0[ -]+)*|(256)*)(\d{9}+))$/i", $cellphone)) {
            $this->error('Incorrect Phone number!' . $cellphone);
        }
        if (FIAT_ALLOWED == 0) {
            die('Unauthorized!');
        }
        if (!userid()) {
            $this->error(L('PLEASE_LOGIN'));
        }
        $coinname = "ugx";

        if (!C('coin')[$coinname]) {
            $this->error(L('Currency wrong!'));
        }


        $coinname = strtolower($coinname);
        $coinnamed = strtolower($coinname) . 'd';


        if (!check($num, 'd')) {
            $this->error(L('The amount of withdrawals format error!'));
        }

        if (!check($paypassword, 'password')) {
            $this->error(L('Fund Pwd format error!'));
        }


        $userCoin = M('UserCoin')->where(array('userid' => userid()))->find();//$this->usercoins;//M('UserCoin')->where(array('userid' => userid()))->find();

        if ($userCoin[$coinname] < $num) {
            $this->error(L('Lack of available Balance!'));
        }

        $user = M('User')->where(array('id' => userid()))->find();

        if (md5($paypassword) != $user['paypassword']) {
            $this->error(L('Trading password is wrong!'));
        }


        $mytx_min = (C('mytx_min') ? C('mytx_min') : 1);
        $mytx_max = (C('mytx_max') ? C('mytx_max') : 1000000);
        $mytx_bei = C('mytx_bei');
        $mytx_fee = C('mytx_fee');

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

        $truename = $user['truename'] ?: $user['username'];
        $fee = bcmul(bcdiv($num, 100, 8), $mytx_fee, 2);
        $mum = bcmul(bcdiv($num, 100, 8), bcsub(100, $mytx_fee, 8), 2);
        $mo = M();
        
        $mo->startTrans();
        $rs = array();
        $finance = $mo->table('codono_finance')->where(array('userid' => userid()))->order('id desc')->find();
        $finance_num_user_coin = $mo->table('codono_user_coin')->where(array('userid' => userid()))->find();
        $rs[] = $mo->table('codono_user_coin')->where(array('userid' => userid()))->setDec($coinname, $num);
        $rs[] = $ugx_entry = $mo->table('codono_mytx')->add(array('userid' => userid(), 'num' => $num, 'fee' => $fee, 'mum' => $mum, 'name' => $truename, 'truename' => $truename, 'bank' => "Yo Payments", 'bankcard' => $cellphone, 'addtime' => time(), 'status' => 1, 'coin' => $coinname));
        $finance_mum_user_coin = $mo->table('codono_user_coin')->where(array('userid' => userid()))->find();
        $finance_hash = md5(userid() . $finance_num_user_coin[$coinname] . $finance_num_user_coin[$coinnamed] . $mum . $finance_mum_user_coin[$coinname] . $finance_mum_user_coin[$coinnamed] . CODONOLIC . 'auth.codono.com');
        $finance_num = $finance_num_user_coin[$coinname] + $finance_num_user_coin[$coinnamed];

        if ($finance['mum'] < $finance_num) {
            $finance_status = (1 < ($finance_num - $finance['mum']) ? 0 : 1);
        } else {
            $finance_status = (1 < ($finance['mum'] - $finance_num) ? 0 : 1);
        }

        $rs[] = $mo->table('codono_finance')->add(array('userid' => userid(), 'coinname' => $coinname, 'num_a' => $finance_num_user_coin[$coinname], 'num_b' => $finance_num_user_coin[$coinnamed], 'num' => $finance_num_user_coin[$coinname] + $finance_num_user_coin[$coinnamed], 'fee' => $num, 'type' => 2, 'name' => 'mytx', 'nameid' => $ugx_entry, 'remark' => 'fiat_withdrawal', 'mum_a' => $finance_mum_user_coin[$coinname], 'mum_b' => $finance_mum_user_coin[$coinnamed], 'mum' => $finance_mum_user_coin[$coinname] + $finance_mum_user_coin[$coinnamed], 'move' => $finance_hash, 'addtime' => time(), 'status' => $finance_status));

        if (check_arr($rs)) {
            session('mytx_verify', null);

            $txid = $this->doWithdrawYoUganda($finance_hash, $cellphone, $num);
            clog('test', $ugx_entry . $txid);
            if ($txid) {
                $happened = M('Mytx')->where(array('userid' => userid(), 'id' => $ugx_entry))->save(array('memo' => $txid));
                clog('hap', $happened);
            } else {
                M('Mytx')->where(array('userid' => userid(), 'id' => $ugx_entry))->save(array('status' => 0));
            }
            $mo->commit();
            
            $this->success(L('Withdrawal order to create success!'));
        } else {
            $mo->rollback();
            $this->error(L('Withdraw order creation failed!'));
        }
    }

    private function doWithdrawYoUganda($ref, $msisdn, $amount)
    {
        $msisdn = str_replace("+", "", $msisdn);
        $msisdn = str_replace(" ", "", $msisdn);
        $msisdn = str_replace("-", "", $msisdn);

        if (!preg_match("/^((\+*)((0[ -]+)*|(256)*)(\d{9}+))$/i", $msisdn)) {
            $this->error('Incorrect Phone number!' . $msisdn);
        }
        //todo check enough funds  + remove funds then process
        $narrative = username() . " withdrawal " . $ref;
        $yoUganda = YoUganda();
        $yoUganda->set_external_reference($ref);
        $yoUganda->set_nonblocking("TRUE");

        $response = $yoUganda->ac_withdraw_funds($msisdn, $amount, $narrative);
        clog('yoUganda', $response);
        if ($response['Status'] == 'OK' && $response['StatusCode'] == '1' && $response['TransactionReference']) {
            return $response['TransactionReference'];
        } else {
            return false;

        }
        //Do something https://paymentsweb.yo.co.ug/
    }
}

