<?php

namespace Home\Controller;

use Common\Ext\GoogleAuthenticator;
use Exception;
use Think\Page;

class UserController extends HomeController
{
    public function mystatus()
    {
        if (!kyced()) {
            $this->error(L('Your KYC needs to be completed first!'));
        }
    }

    public function kyc($truename, $idcard, $idcardinfo, $address = "NoAddress")
    {
        $validIdentifiers = ['others', 'drivers_license', 'passport', 'nationalid'];

        if (!userid()) {
            $this->error(L('YOU_NEED_TO_LOGIN!'));
        }
        if (!check($truename, 'truename')) {
            $this->error(L('Real name format error!'));
        }

        if (!check($idcard, 'idcard')) {
            $this->error(L('ID number format error!'));
        }
        if (!in_array($idcardinfo, $validIdentifiers)) {

            $this->error(L('Please enter correct type of ID!: '.$idcardinfo));
        }
        if (!check($address, 'address')) {
            $this->error(L('Please enter address!'));
        }
//idcardauth=2 =Pending for review
        if (M('User')->where(['id' => userid()])->save(['truename' => $truename, 'idcard' => $idcard, 'idcardinfo' => $idcardinfo, 'address' => addslashes($address), 'idcardauth' => 2])) {
            $this->success(L('success!'));
        } else {
            $this->error(L('failure!'));
        }
    }

    /**
     * @param $pass
     */
    public function deletekyc($pass)
    {
        if (!userid()) {
            $this->error(L('YOU_NEED_TO_LOGIN!'));
        }
        if (M('User')->where(['id' => userid()])->save(['truename' => '', 'idcard' => '', 'idcardinfo' => '', 'idcardimg1' => '', 'idcardimg2' => '', 'idcardauth' => 0])) {
            $this->success(L('Deleted!'));
        } else {
            $this->error(L('failure!'));
        }
    }

    public function index()
    {
        $uid=userid();
        if (!$uid) {
            redirect(U('Login/login'));
        }
        $user = $this->userinfo;
        
        $fiatcoins = [];
        if (!isset($user['apikey']) || $user['apikey'] == NULL) {
            $apikey = md5(md5(rand(0, 10000) . md5(time()), md5(uniqid())));
            M('User')->where(['id' => $uid])->setField('apikey', $apikey);
        }
        foreach (C('coin') as $coinlist) {
            if ($coinlist['type'] == 'rmb' && $coinlist['zr_jz'] == 1) {
                $_fiat_coin['name'] = strtoupper($coinlist['name']);
                $_fiat_coin['img'] = $coinlist['img'];
                $fiatcoins[] = $_fiat_coin;
            }

        }

        if (!$user['fiat']) {
            $selected_coin = SYSTEMCURRENCY;
        } else {
            $selected_coin = $user['fiat'];
        }
        $is_ga = ($user['ga'] ? 1 : 0);
        $token_object=json_encode(['ID'=>$user['id'],'TOKEN'=>$user['token'],'fundpass'=>1,'NAME'=>$user['truename']]);
        $token_object=json_encode(['code'=>base64_encode($token_object)]);
        $this->assign('token_object', $token_object);
        $this->assign('is_ga', $is_ga);
        $this->assign('selected_coin', $selected_coin);
        $this->assign('fiatcoins', $fiatcoins);
        $this->assign('user', $user);
        $this->display();
    }

    public function nameauth()
    {
        $uid = userid();
        if (!$uid) {
            redirect(U('Login/login'));
        }

        $user = M('User')->where(['id' => $uid])->find();
        if (($user['idcardauth'] == '0' || $user['idcardauth'] == '3') && DEFAULT_KYC == 3) {
            redirect(U('Kyc/index'));
        }
        if (SUMSUB_KYC['status'] == '1' && DEFAULT_KYC == 2) {
            redirect(U('Sumsub/index'));
        }
        if (DEFAULT_KYC == 4) {
            redirect(U('Identity/index'));
        }

        if ($user['idcard']) {
            $user['idcard'] = substr_replace($user['idcard'], '********', 6, 8);
        }

        $this->sub_nameauth($user);
        $this->display();
    }

    public function authentication()
    {
        $uid=userid();
        if (!$uid) {
            redirect(U('Login/login'));
        }

        $user = M('User')->where(['id' => $uid])->find();
        /*
        if ($user['idcard']) {
            $user['idcard'] = substr_replace($user['idcard'], '********', 6, 8);
        }
        */
        $this->sub_nameauth($user);
        $this->display('User/nameauth');
    }

    public function doAuthentication()
    {
        $uid=userid();
        // Retrieve POST parameters
        $firstname = I('post.firstname','','text');
        $lastname = I('post.lastname','','text');
        $idcard = I('post.idcard','','text');
        $idcardinfo = I('post.idcardinfo','','text');
        $country = I('post.country','','text');
        $state = I('post.state','','text');
        $city = I('post.city','','text');
        $dob = I('post.dob','','text');
        if (!$uid) {
            $this->error(L('YOU_NEED_TO_LOGIN!'));
        }
        if (!check($firstname, 'truename')) {
            $this->error(L('Firstname format error!'));
        }
        if (!check($lastname, 'truename')) {
            $this->error(L('Lastname format error!'));
        }

        if (!check($idcard, 'idcard')) {
            $this->error(L('ID number format error!'));
        }
        if (!check($idcardinfo, 'truename')) {
            $this->error(L('Please enter correct type of ID!'));
        }
        if (!check($country, 'address')) {
            $this->error(L('Please enter Country!'));
        }
        if (!check($state, 'address')) {
            $this->error(L('Please enter state!'));
        }
        if (!check($city, 'address')) {
            $this->error(L('Please enter city!'));
        }
        if (!check($dob, 'address')) {
            $this->error(L('Please enter dob!'));
        }

        if (M('User')->where(['id' => userid()])->save(['firstname' => $firstname, 'lastname' => $lastname, 'idcard' => $idcard, 'idcardinfo' => "NationalID", 'country' => addslashes($country), 'state' => addslashes($state), 'city' => addslashes($city), 'dob' => addslashes($dob), 'idcardauth' => 2])) {
            $this->success(L('success!'));
        } else {
            $this->error(L('failure!'));
        }
    }

    public function password()
    {
        if (!userid()) {
            redirect(U('Login/login'));
        }

        $this->assign('prompt_text', D('Text')->get_content('user_password'));
        $this->display();
    }

    public function uppassword() {
        // Retrieve POST parameters
        $oldpassword = I('post.oldpassword');
        $newpassword = I('post.newpassword');
        $repassword = I('post.repassword');
        $cellphone_verify = I('post.cellphone_verify');

        $uid=userid();
        if (!$uid) {
            $this->error(L('PLEASE_LOGIN'));
        }

        if (!session('real_cellphone')) {
            $this->error(L('Verification code has expired!'));
        }

        if ($cellphone_verify != session('real_cellphone')) {
            $this->error(L('Phone code error!'));
        } else {
            session('real_cellphone', null);
        }

        $this->sub_uppassword($oldpassword, $newpassword, $repassword);

        $rs = M('User')->where(['id' => $uid])->save(['password' => md5($newpassword)]);

        if ($rs) {
            $this->success(L('Successfully modified'));
        } else {
            $this->error('No changes were made!');
        }
    }

    public function paypassword()
    {
        $uid=userid();
        if (!$uid) {
            redirect(U('Login/login'));
        }


        $user = M('User')->where(['id' => $uid])->find();
        if (!$user['paypassword']) {
            $this->assign('show_old', false);
        } else {
            $this->assign('show_old', true);
        }
        $this->assign('user', $user);

        $this->assign('prompt_text', D('Text')->get_content('user_paypassword'));
        $this->display();
    }


    public function uppaypassword_change()
    {
        // Retrieve POST parameters
        $oldpaypassword = I('post.oldpaypassword', null);
        $newpaypassword = I('post.newpaypassword');
        $repaypassword = I('post.repaypassword');
        if (!userid()) {
            $this->error(L('PLEASE_LOGIN'));
        }


        $rs = $this->sub_uppaypassword($oldpaypassword, $newpaypassword, $repaypassword);

        if (!($rs === false)) {
            $this->success(L('Successfully modified'));
        } else {
            $this->error('No changes were made!');
        }
    }


    public function uppaypassword($oldpaypassword, $newpaypassword, $repaypassword, $cellphone_verify)
    {
        if (!userid()) {
            $this->error(L('PLEASE_LOGIN'));
        }

        if (!session('real_cellphone')) {
            $this->error(L('Verification code has expired!'));
        }

        if ($cellphone_verify != session('real_cellphone')) {
            $this->error(L('Phone code error!'));
        } else {
            session('real_cellphone', null);
        }

        $rs = $this->sub_uppaypassword($oldpaypassword, $newpaypassword, $repaypassword);

        if ($rs) {
            $this->success(L('Successfully modified'));
        } else {
            $this->error('No changes were made!');
        }
    }

    public function ga()
    {
        if (empty($_POST)) {
            if (!userid()) {
                redirect(U('Login/login'));
            }

            $this->assign('prompt_text', D('Text')->get_content('user_ga'));
            $user = $this->userinfo;
            $is_ga = ($user['ga'] ? 1 : 0);
            $this->assign('is_ga', $is_ga);

            if (!$is_ga) {
                $ga = new GoogleAuthenticator();
                $secret = $ga->createSecret();
                session('secret', $secret);
                $this->assign('Asecret', $secret);
                $qrCodeUrl = $ga->getQRCodeGoogleUrl($user['username'] . '%20-%20' . SHORT_NAME, $secret);
                $this->assign('qrCodeUrl', $qrCodeUrl);
            } else {
                $arr = explode('|', $user['ga']);
                $this->assign('ga_login', $arr[1]);
                $this->assign('ga_transfer', $arr[2]);
            }
            $this->display();
        } else {
            if (!userid()) {
                $this->error('Login has failed,please login again!');
            }

            $delete = $secret = '';
            $gacode = trim(I('ga'));
            $type = trim(I('type'));
            $ga_login = (!I('ga_login') ? 0 : 1);
            $ga_transfer = (!I('ga_transfer') ? 0 : 1);

            if (!$gacode) {
                $this->error('Enter 2FA Code!');
            }

            if ($type == 'add') {
                $secret = session('secret');

                if (!$secret) {
                    $this->error('2FA has expired,Please refresh the page!');
                }
            } else if (($type == 'update') || ($type == 'delete')) {
                $user = $this->userinfo;

                if (!$user['ga']) {
                    $this->error('2FA setup isnt done yet!');
                }

                $arr = explode('|', $user['ga']);
                $secret = $arr[0];
                $delete = ($type == 'delete' ? 1 : 0);
            } else {
                $this->error(L('Type is undefined'));
            }

            $ga = new GoogleAuthenticator();


            if ($ga->verifyCode($secret, $gacode, 1)) {
                $ga_val = ($delete == '' ? $secret . '|' . $ga_login . '|' . $ga_transfer : '');
                M('User')->save(['id' => userid(), 'ga' => $ga_val]);
                $this->success(L('Successful operation'));
            } else {
                $this->error(L('Verification failed'));
            }
        }
    }

    public function cellphone()
    {
        if (!userid()) {
            redirect(U('Login/login'));
        }

        if (!ENABLE_MOBILE_VERIFY) {
            $this->error(L('No Access'));
        }

        $user = $this->userinfo;
        $this->assign('verified', $this->cellphone_verified());
        $this->assign('user', $user);
        $this->assign('prompt_text', D('Text')->get_content('user_cellphone'));
        $this->display();
    }

    private function cellphone_verified(){
        $user = $this->userinfo;
        $verified=0;
        if ($user['cellphone'] && $user['cellphones']) {
        $verified=1;
        }
        return $verified;
    }
    public function upcellphone($cellphone, $cellphone_verify)
    {
        if (!ENABLE_MOBILE_VERIFY) {
            $this->error(L('No Access'));
        }
        if (!userid()) {
            $this->error(L('YOU_NEED_TO_LOGIN'));
        }

        if (!check($cellphone, 'cellphone')) {
            $this->error(L('INVALID_PHONE_FORMAT'));
        }

        if (!check($cellphone_verify, 'd')) {
            $this->error(L('INVALID_SMS_CODE'));
        }

        if ($cellphone_verify != session('real_verify')) {
            $this->error(L('INCORRECT_SMS_CODE'));
        }
        $user_cellphone = $this->userinfo['cellphone'];
        if ($user_cellphone == $cellphone) {
            $this->error(L('Phone number already exists!'));
        }

        $rs = M('User')->where(['id' => userid()])->save(['cellphone' => $cellphone, 'cellphonetime' => time()]);

        if ($rs) {
            $this->success(L('Mobile phone authentication is successful!'));
        } else {
            $this->error(L('Mobile phone authentication failure!'));
        }
    }

    public function tpwdsetting()
    {
        if (userid()) {
            $tpwdsetting = $this->userinfo['tpwdsetting'];//M('User')->where(array('id' => userid()))->getField('tpwdsetting');
            exit($tpwdsetting);
        }
    }

    public function uptpwdsetting($paypassword, $tpwdsetting)
    {
        if (!userid()) {
            $this->error(L('PLEASE_LOGIN'));
        }


        if (($tpwdsetting != 1) && ($tpwdsetting != 2) && ($tpwdsetting != 3)) {
            $this->error(L('Options Error!') . $tpwdsetting);
        }

        $user_paypassword = $this->userinfo['paypassword'];//M('User')->where(array('id' => userid()))->getField('paypassword');
        if ($user_paypassword) {
            if (!check($paypassword, 'password')) {
                $this->error(L('Fund Pwd format error!'));
            }
        }
        if (md5($paypassword) != $user_paypassword) {
            $this->error(L('Trading password is wrong!'));
        }

        $rs = M('User')->where(['id' => userid()])->save(['tpwdsetting' => $tpwdsetting]);

        if (!($rs === false)) {
            $this->success(L('SUCCESSFULLY_DONE'));
        } else {
            $this->error(L('OPERATION_FAILED'));
        }
    }

    public function bank()
    {
        redirect(U('Finance/bank'));
    }

    public function upbank() {
        // Retrieve POST parameters
        $name = I('post.name', '','text');
        $bank = I('post.bank', '','text');
        $bankprov = I('post.bankprov', '','text');
        $bankcity = I('post.bankcity', '','text');
        $bankaddr = I('post.bankaddr', '','text');
        $bankcard = I('post.bankcard', '','text');
        $paypassword = I('post.paypassword', '','text');
        if (!userid()) {
            redirect(U('Login/login'));
        }

        if (!check($name, 'a')) {
            $this->error(L('Note the name of the wrong format!'));
        }

        if (!check($bank, 'a')) {
            $this->error(L('Bank malformed!'));
        }

        if (!check($bankprov, 'c')) {
            $this->error(L('Opening provinces format error!'));
        }

        if (!check($bankcity, 'c')) {
            $this->error('Opening provinces malformed2!');
        }

        if (!check($bankaddr, 'a')) {
            $this->error(L('Bank address format error!'));
        }

        if (!check($bankcard, 'd')) {
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
            $this->error('Each user can add upto 10 accounts max!');
        }

        if (M('UserBank')->add(['userid' => userid(), 'name' => $name, 'bank' => $bank, 'bankprov' => $bankprov, 'bankcity' => $bankcity, 'bankaddr' => $bankaddr, 'bankcard' => $bankcard, 'addtime' => time(), 'status' => 1])) {
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

    public function wallet($coin = NULL)
    {
        redirect(U('Finance/myzr'));
    }


    /**
     * @param $coin
     * @param $name
     * @param $addr
     * @param $paypassword
     * @param string $dest_tag
     */
    public function upwallet($coin, $name, $addr, $paypassword, string $dest_tag = '')
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
        /*
		if (!check($dest_tag, 'dw')) {
            $this->error(L('Destination tag incorrect format!'));
        }
        */
        if (!check($paypassword, 'password')) {
            $this->error(L('Fund Pwd format error!'));
        }

        $user_paypassword = M('User')->where(['id' => userid()])->getField('paypassword');

        if (md5($paypassword) != $user_paypassword) {
            $this->error(L('Trading password is wrong!'));
        }
        $isValidCoin = isValidCoin($coin);
        if ($coin == null || !$isValidCoin) {
            $this->error(L('Currency wrong!'));
        }
        $addr = preg_replace('/[^A-Za-z0-9\-]/', '', $addr);
        $dest_tag=preg_replace('/[^A-Za-z0-9\-]/', '', $dest_tag);
        $userWallet = M('UserWallet')->where(['userid' => userid(), 'coinname' => $coin])->select();

        foreach ($userWallet as $k => $v) {
            if ($v['name'] == $name) {
                $this->error(L('Please do not use the same wallet name!'));
            }

            if ($v['addr'] == $addr) {
                $this->error(L('Wallet address already exists!'));
            }
        }

        if (20 <= count($userWallet)) {
            $this->error('Maximum 20 addresses allowed per coin!');
        }

        if (M('UserWallet')->add(['userid' => userid(), 'name' => $name, 'addr' => $addr, 'dest_tag' => $dest_tag, 'coinname' => $coin, 'addtime' => time(), 'status' => 1])) {
            $this->success(L('ADDED_SUCCESSFULLY'));
        } else {
            $this->error(L('FAILED_TO_ADD'));
        }
    }

    public function delwallet()
    {
        $paypassword = I('post.paypassword', '', 'text');
        $id=I('post.id', null, 'text');
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

    public function goods()
    {
        if (!userid()) {
            redirect(U('Login/login'));
        }

        $userGoodsList = M('UserGoods')->where(['userid' => userid(), 'status' => 1])->order('id desc')->select();

        foreach ($userGoodsList as $k => $v) {
            $userGoodsList[$k]['cellphone'] = substr_replace($v['cellphone'], '****', 3, 4);
            $userGoodsList[$k]['idcard'] = substr_replace($v['idcard'], '********', 6, 8);
        }

        $this->assign('userGoodsList', $userGoodsList);
        $this->assign('prompt_text', D('Text')->get_content('user_goods'));
        $this->display();
    }

    public function upgoods() {
        // Retrieve POST parameters
        $name = I('post.name');
        $truename = I('post.truename');
        $idcard = I('post.idcard');
        $cellphone = I('post.cellphone');
        $addr = I('post.addr');
        $paypassword = I('post.paypassword');
        if (!userid()) {
            redirect(U('Login/login'));
        }

        if (!check($name, 'a')) {
            $this->error(L('Note the name of the wrong format!'));
        }

        if (!check($truename, 'truename')) {
            $this->error(L('Contact Name Format error!'));
        }

        if (!check($idcard, 'idcard')) {
            $this->error(L('ID number format error!'));
        }

        if (!check($cellphone, 'cellphone')) {
            $this->error(L('Tel format error!'));
        }

        if (!check($addr, 'a')) {
            $this->error(L('Contact address malformed!'));
        }

        $user_paypassword = $this->userinfo['paypassword'];//M('User')->where(array('id' => userid()))->getField('paypassword');

        if (md5($paypassword) != $user_paypassword) {
            $this->error(L('Trading password is wrong!'));
        }

        $userGoods = M('UserGoods')->where(['userid' => userid()])->select();

        foreach ($userGoods as $k => $v) {
            if ($v['name'] == $name) {
                $this->error(L('Please do not use the same address identity!'));
            }
        }

        if (10 <= count($userGoods)) {
            $this->error('Max 10 addresses allowed per person!!');
        }

        if (M('UserGoods')->add(['userid' => userid(), 'name' => $name, 'addr' => $addr, 'idcard' => $idcard, 'truename' => $truename, 'cellphone' => $cellphone, 'addtime' => time(), 'status' => 1])) {
            $this->success(L('ADDED_SUCCESSFULLY'));
        } else {
            $this->error(L('FAILED_TO_ADD'));
        }
    }


    public function upgoods_codono() {
        // Retrieve POST parameters
        $name = I('post.name', '','text');
        $truename = I('post.truename', '','text');
        $idcard = I('post.idcard', '','text');
        $cellphone = I('post.cellphone', '','text');
        $addr = I('post.addr', '','text');
        $paypassword = I('post.paypassword', '','text');
        $prov = I('post.prov', '','text');
        $city = I('post.city', '','text');
        $uid=userid();
        if (!$uid) {
            redirect(U('Login/login'));
        }

        if (!check($name, 'idcard')) {
            $this->error(L('ID card format incorrect!'));
        }

        if (!check($truename, 'truename')) {
            $this->error(L('Contact Name format incorrect!'));
        }

        if (!check($cellphone, 'cellphone')) {
            $this->error(L('Mobile format incorrect!'));
        }

        if (!check($addr, 'mostregex')) {
            $this->error(L('Address format incorrect!'));
        }

        if (!check($prov, 'mostregex')) {
            $this->error(L('Provinces format incorrect!'));
        }
        if (!check($city, 'mostregex')) {
            $this->error(L('City format incorrect!'));
        }

        $user_paypassword = M('User')->where(['id' => $uid])->getField('paypassword');

        if (md5($paypassword) != $user_paypassword) {
            $this->error(L('Incorrect Trading password!'));
        }

        $userGoods = M('UserGoods')->where(['userid' => $uid])->select();

        foreach ($userGoods as $k => $v) {
            if ($v['name'] == $name) {
                $this->error(L('Please do not use the same address identity!'));
            }
        }

        if (10 <= count($userGoods)) {
            $this->error('Each person can add upto 10 addresses!');
        }

        if (M('UserGoods')->add(['userid' => $uid, 'name' => $name, 'addr' => $addr, 'prov' => $prov, 'city' => $city, 'truename' => $truename, 'cellphone' => $cellphone, 'addtime' => time(), 'status' => 1])) {
            $this->success(L('ADDED_SUCCESSFULLY'));
        } else {
            $this->error(L('FAILED_TO_ADD'));
        }
    }


    public function delgoods($id, $paypassword)
    {
        $id = I('post.id', '','intval');
        $paypassword = I('post.paypassword', '','text');
        $uid=userid();
        if (!$uid) {
            redirect(U('Login/login'));
        }

        if (!check($paypassword, 'password')) {
            $this->error(L('Fund Pwd format error!'));
        }

        if (!check($id, 'd')) {
            $this->error(L('INCORRECT_REQ'));
        }

        $user_paypassword = M('User')->where(['id' => $uid])->getField('paypassword');

        if (md5($paypassword) != $user_paypassword) {
            $this->error(L('Trading password is wrong!'));
        }

        if (!M('UserGoods')->where(['userid' => $uid, 'id' => $id])->find()) {
            $this->error(L('Unauthorized access!'));
        } else if (M('UserGoods')->where(['userid' => $uid, 'id' => $id])->delete()) {
            $this->success(L('successfully deleted!'));
        } else {
            $this->error(L('failed to delete!'));
        }
    }

    public function log()
    {
        if (!userid()) {
            redirect(U('Login/login'));
        }

        $where['status'] = ['egt', 0];
        $where['userid'] = userid();
        $Model = M('UserLog');
        $count = $Model->where($where)->count();
        $Page = new Page($count, 10);
        $show = $Page->show();
        $list = $Model->where($where)->order('id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->assign('prompt_text', D('Text')->get_content('user_log'));
        $this->display();
    }

    
    public function updateFiat($fiat)
    {
        $uid = userid();
        if (strtolower(C('coin')[strtolower($fiat)]['name']) != strtolower($fiat) || C('coin')[strtolower($fiat)]['type'] != 'rmb' || !$fiat) {
            $this->error('Please select correct currency');
        }

        $fiat_already = M('User')->where(['id' => $uid])->field('fiat')->find();
        if (strtoupper($fiat_already['fiat']) == strtoupper($fiat)) {
            $this->success($fiat . ' is already your default currency!');
        }
        $mo = M();
        $rs = $mo->table('codono_user')->where(['id' => $uid])->save(['fiat' => strtoupper($fiat)]);
        if ($rs) {
            $this->success('Currency has been updated!');
        } else {
            $this->error('Currency could not be updated!');
        }
    }

    public function security()
    {
        if (!userid()) {
            redirect(U('Login/login'));
        }
        $paypassword=M('User')->where(['id' => userid()])->getField('paypassword');
        $pp_exists=true;
        if($paypassword==null){
            $pp_exists=false;
        }
        $this->assign('pp_exists',$pp_exists);
        $this->assign('cellphone_verified',$this->cellphone_verified());
        $this->display();

    }

    public function preference()
    {
        if (!userid()) {
            redirect(U('Login/login'));
        }
        $this->display();
    }

    public function freeze()
    {
        if (!userid()) {
            redirect(U('Login/login'));
        }
        $this->display();
    }

    public function doFreeze()
    {
        $uid=userid();
        if (!$uid) {
           $this->error('Please login');
        }
        $code = trim(I('post.code','','text'));
        $reason = trim(I('post.reason','','text'));
        
        if ($code != 'freeze') {
            $this->error("Please type freeze");
        }
        
        if (!$reason && !check($reason,'mostregex')) {
            $this->error("Please select a reason");
        }
        $new_token=md5(md5(rand(10000,9999999).ADMIN_KEY.time()).time());
        $action=M('User')->where(['id' => $uid,'status'=>1])->save(['status' => 0,'token'=>$new_token,'apikey'=>md5($new_token.time()), 'freeze_reason' => $reason]);
        if(!$action){
            $this->error("Account could not be freezed, or already frozen");
        }
        $user_email=getEmail($uid);
        if($user_email){
            safeLog($user_email, 15, 'Account Freezed by User for '.$reason);
        }
        $client_ip = get_client_ip();
        $subject=SHORT_NAME.' Account has been freezed';
        $content=$this->getFreezeContent($client_ip, $user_email, $reason);
        addnotification($user_email, $subject, $content);
        session(null);
        S('APP_AUTH_ID_' . $uid, null);
        $this->success("Your account has been frozen, Please contact support");
    }
    private function getFreezeContent($ip, $email, $reason)
    {
        $time=date('Y-m-d H:i:s');
        return " <style>.header { background-color: #4CAF50; color: white; text-align: center; padding: 10px; }
                .content { margin-top: 20px; }
                .footer { margin-top: 40px; font-size: 0.8em; text-align: center; }
            </style>
        
                <div class='content'>
                    <p>Hello there,</p>
                    <p>Your account on " . SHORT_NAME . " has been freezed due to following reason</p>
                    
                    <h3>Account Details:</h3>
                    <ul>
                        <li>Email: $email</li>
                        <li>Reason: $reason</li>
                        
                    </ul>
                    <h3>Login Information:</h3>
                    <ul>
                        <li>IP Address: $ip</li>
                        <li>Time of Freeze: $time</li>
                    </ul>
                    <h3>Next Steps:</h3>
                    <ol>
                        <li>Please contact Admin to get your account reinstated.</li>
                        <li>Set up Strong Password and Funding password!</li>
                        <li>Set up Two-Factor Authentication (2FA) for enhanced security.</li>
                    </ol>
                    <p>If you did not freeze  " . SHORT_NAME . ' account, please contact us immediately to secure your information.</p>
                    <p>Thank you for choosing ' . SHORT_NAME . ". We are committed to providing you with a secure and efficient experience.</p>
                </div>
                <div class='footer'>
                    Best regards,<br>
                    The " . SHORT_NAME . ' Team
                </div>';
    }

    public function antiphishing()
    {
        $userid = userid();
        if (!$userid) {
            redirect(U('Login/login'));
        }
        $data = M('User')->where(['status' => 1, 'id' => $userid])->find();
        $this->assign('antiphishing', $data['antiphishing']);
        $this->display();
    }

    public function saveAntiPhishing()
    {
        $uid=userid();
        if (!$uid) {
            $this->error(L('YOU_NEED_TO_LOGIN'));
        }
        $antiphishing = I('post.antiphishing', '', 'text');
        if (!check($antiphishing, 'username')) {
            $this->error(L('Enter antiphising code from 4-15 letters'));
        }


        $rs = M('User')->where(['id' =>$uid])->save(['antiphishing' => $antiphishing]);
        if ($rs) {
            $this->success(L('Successfully updated'));
        } else {
            $this->error(L('There were issues updating your antiphishing code.'));
        }
    }

    /**
     * @param $user
     * @return void
     */
    private function sub_nameauth($user): void
    {
        $imgstr = "";
        $imgnum = 0;

        if ($user['idcardimg1']) {

            $img_arr = explode("_", $user['idcardimg1']);

            foreach ($img_arr as $k => $v) {
                $imgstr = $imgstr . '<li style="height:100px;"><embed style="width:300px;height:100px;" src="/Upload/idcard/' . $v . '" /></li>';
                $imgnum++;
            }

            unset($img_arr);
        }
        $allowImg = false;

        if ($user['accounttype'] == 1) {
            if (($user['idcardauth'] != 1 && $imgnum < 4) || ($user['idcardauth'] != 1 && $imgnum == 4 && !empty($user['idcardinfo']))) {
                $allowImg = true;
            }
        } else {
            if (($user['idcardauth'] != 1 && $imgnum < 7) || ($user['idcardauth'] != 1 && $imgnum == 7 && !empty($user['idcardinfo']))) {
                $allowImg = true;
            }
        }

        $this->assign('user', $user);
        $this->assign('userimg', $imgstr);
        $this->assign('imgnum', $imgnum);
        $this->assign('allowImg', $allowImg);

        $this->assign('prompt_text', D('Text')->get_content('user_nameauth'));
    }

    /**
     * @param $oldpassword
     * @param $newpassword
     * @param $repassword
     * @return void
     */
    private function sub_uppassword($oldpassword, $newpassword, $repassword): void
    {
        if (!check($oldpassword, 'password')) {
            $this->error(L('Old password format error!'));
        }

        if (!check($newpassword, 'password')) {
            $this->error(L('The new password is malformed!'));
        }

        if ($newpassword != $repassword) {
            $this->error(L('Confirm New Password wrong!'));
        }

        $password = $this->userinfo['password']; //M('User')->where(array('id' => userid()))->getField('password');

        if (md5($oldpassword) != $password) {
            $this->error(L('Old login password is incorrect!'));
        }
    }

    /**
     * @param $oldpaypassword
     * @param $newpaypassword
     * @param $repaypassword
     * @return bool|float|int|string
     */
    private function sub_uppaypassword($oldpaypassword, $newpaypassword, $repaypassword)
    {
        $uid = userid();
        if (!check($newpaypassword, 'password')) {
            $this->error(L('The new Fund Pwd format error!'));
        }

        if ($newpaypassword != $repaypassword) {
            $this->error(L('Confirm New Password wrong!'));
        }

        $user = M('User')->where(['id' => $uid])->find();

        if ($user['paypassword']) {
            if (!check($oldpaypassword, 'password')) {
                $this->error(L('Old Fund Pwd format error!'));
            }

            if (md5($oldpaypassword) != $user['paypassword']) {
                $this->error(L('Old Fund Pwd is wrong!'));
            }

        }

        if (md5($newpaypassword) == $user['password']) {
            $this->error(L('Fund Pwd and login password can not be the same!'));
        }

        return M('User')->where(['id' => userid()])->save(['paypassword' => md5($newpaypassword)]);
    }
    public function edit(){
        $this->display();
    }
    public function doEdit(){
        $uid = userid();
        if (!$uid) {
            $this->error(L('YOU_NEED_TO_LOGIN'));
        }
        $username=I('post.username','','text');
         
        $validation=$this->isUsernameValid($username);
        if (!$validation['status'] || $validation['status']==0 ) {
            $this->error($validation['msg']);
        } 
        $myinfo=M('User')->where(['id' => $uid])->find();

        if ($myinfo['allow_username_change'] == 0) {
            $this->error(L('You can not change username!'));
        }  
        if (M('User')->where(['username' => $username])->find()) {
            $this->error(L('Username already exists!'));
        }

        $info=M('User')->where(['id' => $uid])->save(['username' => $username,'allow_username_change'=>0]);
        if ($info) {
            $this->success(L('Successfully updated'));
        } else {
            $this->error(L('There were issues updating your username.'));
        }   
        // check in database table codono_user to see if this username exists
    }
    private function isUsernameValid($username) {
        // Expanded list of banned usernames
        $bannedUsernames = ['admin', 'system', 'root', 'administrator', 'server', 'null', 'mysql', 'config'];
    
        // Check if the username is in the banned list
        if (in_array(strtolower($username), $bannedUsernames)) {
            return ['status' => 0, 'msg' => "Username is reserved and cannot be used."];
        }
    
        // Check for minimum length
        $minLength = 5;
        if (strlen($username) < $minLength) {
            return ['status' => 0, 'msg' => "Username must be at least $minLength characters long."];
        }
    
        // Check for maximum length
        $maxLength = 20;
        if (strlen($username) > $maxLength) {
            return ['status' => 0, 'msg' => "Username cannot be longer than $maxLength characters."];
        }
    
        // Check if the username contains only letters, numbers, and underscores
        if (!preg_match('/^\w+$/', $username)) {
            return ['status' => 0, 'msg' => "Username can only contain letters, numbers, and underscores."];
        }
    
        // If all checks are passed, return a valid status
        return ['status' => 1, 'msg' => "Valid username."];
    }

}