<?php

namespace Api\Controller;



class LoginController extends CommonController
{

    protected function _initialize()
    {
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Headers: token,Origin, X-Requested-With, Content-Type, Accept,ID,TOKEN');
        header('Access-Control-Allow-Methods: POST,GET');
    }

    public function index()
    {
        echo "Connected";
        exit;
    }

    /*
    $action=verify or add
    */
    private function verification_code($email, $code, $action = 'verify')
    {

        $mo = M();
        if ($action == 'add') {
            $verify = array('email' => $email, 'code' => $code);
            $rs[] = $mo->table('codono_verify')->add($verify);
            if (check_arr($rs)) {
                return '{"status":"1","data":"Code has been sent to your email"}';
            } else {
                $this->error('Code could not be added,Please try again in sometime!');
            }
        } else {
            $last_entry = $mo->table('codono_verify')->where(array('email' => $email))->order('id desc')->find();
            if ($last_entry['attempts'] >= 3) {
                $this->error('Too many attempts!');
            }
            $mo->table('codono_verify')->where(array('id' => $last_entry['id']))->setInc('attempts', 1);
            if ($last_entry) {
                if ($last_entry['code'] == $code) {
                    return '{"status":"1","data":"Code has been verfied please login"}';
                } else {
                    $this->error('Incorrect Code!');
                }
            } else {
                $this->error('Incorrect Code!');
            }
        }
    }

    public function emailcode()
    {

        $input = $_POST = json_decode(file_get_contents('php://input'), true);
        $email = $input['email'];

        if (!check($email, 'email')) {
            $this->error(L('INVALID_EMAIL_FORMAT'));
        }
        if (M('User')->where(array('email' => $email))->find()) {
            $this->error(L('Email already exists, Please login instead!'));
        }

        $code = rand(111111, 999999);

        $this->verification_code($email, $code, 'add');

        $subject = "Signup code for " . SHORT_NAME;
        $content = 'Hello There,<br/>
			You have just tried signing up using our app of ' . SHORT_NAME . '<br/>
			Your verification code is :' . $code . ' <br/>
			Please disregard if you didnt attempt to register.
			';
        
        $mail_Sent = addnotification($email, $subject, $content);
        if ($mail_Sent) {
            $this->success(L('Verification code has been sent'));
        } else {
            $this->error(L('Unable to send email Code, Retry in sometime'));
        }
    }

    public function confirmcode($email, $verify_code, $password)
    {

        if (!check($password, 'password')) {
            $this->error("Password Format: 5-15 chars");
        }

        if (!check($email, 'email')) {
            $this->error(L('INVALID_EMAIL_FORMAT'));
        }
        $user = M('User')->where(array('email' => $email))->find();
        if (!$user) {
            $this->error(L('Email Does not exist!'));
        }


        $resp = json_decode($this->verification_code($email, $verify_code, 'verify'), true);

        if ($resp['status'] != 1) {
            $this->error(L('Incorrect Email Code'));
        }

        $save_details = M('User')->where(array('id' => $user['id']))->save(array('password' => md5($password)));

        if ($save_details) {
            $this->success('Successfully modified,Go to landing page');
        } else {
            $this->error('No changes were made!');
        }
    }

    public function forgotcode($email)
    {

        if (!check($email, 'email')) {
            $this->error(L('INVALID_EMAIL_FORMAT'));
        }
        if (!M('User')->where(array('email' => $email))->find()) {
            $this->error(L('Email Does not exist!'));
        }

        $code = rand(111111, 999999);
        //session('real_verify', $code);
        $this->verification_code($email, $code, 'add');


        $subject = "Password Reset code for " . SHORT_NAME;
        $content = 'Hello There,<br/>
			You have just tried resetting password using our app of ' . SHORT_NAME . '<br/>
			Your verification code is :' . $code . ' <br/>
			Please disregard if you didnt attempt to reset.
			';
        
		$mail_Sent = addnotification($email, $subject, $content);
        if ($mail_Sent) {
            $this->success(L('Verification code has been sent'));
        } else {
            $this->error(L('Unable to send email Code, Retry in sometime'));
        }
    }

    public function emailregister()
    {
        $username = I('post.username', '', 'text');
        $email = I('post.email', '', 'text');
        $verify = I('post.verify', '', 'text');
        $password = I('post.password', '', 'text');
        $invit = I('post.invit', '', 'text');

        if (!check($username, 'username')) {
            $this->error('Username length 4-15 and starts with a char');
        }


        if (!check($email, 'email')) {
            $this->error(L('INVALID_EMAIL_FORMAT'));
        }
        if (M('User')->where(array('username' => $username))->find()) {
            $this->error(L('Username already exists!'));
        }

        if (M('User')->where(array('email' => $email))->find()) {
            $this->error(L('Email already exists!'));
        }
        $resp = json_decode($this->verification_code($email, $verify, 'verify'), true);

        if ($resp['status'] != 1) {
            $this->error(L('Incorrect Email Code'));
        }

        if (!check($password, 'password')) {
            $this->error("Password Format: 5-15 chars");
        }


        if (!$invit) {
            $invit = session('invit');
        }

        $invituser = M('User')->where(array('invit' => $invit))->find();

        if (!$invituser) {
            $invituser = M('User')->where(array('id' => $invit))->find();
        }

        if (!$invituser) {
            $invituser = M('User')->where(array('email' => $email))->find();
        }

        if ($invituser) {
            $invit_1 = $invituser['id'];
            $invit_2 = $invituser['invit_1'];
            $invit_3 = $invituser['invit_2'];
        } else {
            $invit_1 = 0;
            $invit_2 = 0;
            $invit_3 = 0;
        }

        for (; true;) {
            $tradeno = tradenoa();

            if (!M('User')->where(array('invit' => $tradeno))->find()) {
                break;
            }
        }

        $mo = M();
        $mo->startTrans();
        $rs = [];
        $rs[] = $mo->table('codono_user')->add(array('email' => $email, 'username' => $username, 'password' => md5($password), 'invit' => $tradeno, 'tpwdsetting' => 1, 'invit_1' => $invit_1, 'invit_2' => $invit_2, 'invit_3' => $invit_3, 'addip' => get_client_ip(), 'addr' => get_city_ip(), 'addtime' => time(), 'status' => 1));
        $rs[] = $mo->table('codono_user_coin')->add(array('userid' => $rs[0]));

        if (check_arr($rs)) {
            $mo->commit();
            
            session('reguserId', $rs[0]);
            $this->success(L('SUCCESSFULLY_REGISTERED'));
        } else {
            $mo->rollback();
            $this->error(L('Registration failed!'));
        }
    }

    public function chkUser($username)
    {
        if (!check($username, 'username')) {
            $this->error(L('INVALID_USERNAME'));
        }

        if (M('User')->where(array('username' => $username))->find()) {
            $this->error('Username already exists');
        }

        $this->success('ok');
    }
    private function sanitizeInput($data) {
        // Trim whitespace from both sides
        $data = trim($data);
        // Remove slashes and convert special characters to HTML entities
        $data = stripslashes($data);
        $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
        return $data;
    }
    
    public function submit()
    {
        $request_body = file_get_contents('php://input');
        $data = json_decode($request_body, 1);

        $username = $this->sanitizeInput($data['username']);
        $password = $this->sanitizeInput($data['password']);
        
        if (check($username, 'email') || check($username, 'username')) {
            
            $user = M('User')->where(array('_logic' => 'OR', 'email' => $username, 'username' => $username))->find();
            if ($user) {
                $remark = check($username, 'email') ? 'API Email Login' : 'API Username Login';
            } else {
                $this->error('No user account found!');
            }
        } else {
            $this->error('Invalid username or email format!');
        }
        
        $check_time = 10;
        $check_times = 5;
        $username_msg = md5($username);
        $ltimes = (int)S('LOGIN_ERR_TIMES_' . $username_msg);

        if ($check_times <= $ltimes) {
            $ltime = S('LOGIN_ERR_TIME_' . $username_msg);
            $ltime = time() - $ltime;

            if ($check_time <= $ltime) {
                S('LOGIN_ERR_TIMES_' . $username_msg, 0);
                $this->error('Wrong password lock release,Please try again');
            }

            $min = $check_time - $ltime;
            $this->error('Wrong password too many times,please try after ' . $min . ' mins !');
        }

        if (!$user) {
            $this->error(L('USER_DOES_NOT_EXISTS'));
        }

        if (!check($password, 'password')) {
            $this->error(L('INVALID_PASSWORD'));
        }

        if (md5($password) != $user['password']) {
            S('LOGIN_ERR_TIMES_' . $username_msg, ++$ltimes);
            S('LOGIN_ERR_TIME_' . $username_msg, time());
            $this->error('Password wrong,' . (($check_times - $ltimes) + 1) . ' attempts left');
        }

        if ($user['status'] != 1) {
            $this->error('Your account has been frozen, please contact the administrator!');
        }
        //Here we check if 2fa is enabled if Yes Then
        $ga_status = $user['ga'] ? 1 : 0;
        //Google2FA IS ENABLED  For login
        if ($ga_status == 1) {
            $arr = explode('|', $user['ga']);
            $is_ga = $arr[1];
        } else {
            $is_ga = 0;
        }
		//@todo implement this when 2fa screen is there on mobile app
        if ($is_ga == 1) {
            session('uid', $user['id']);
            session('uname', $user['username']);
            session('invitecode', $user['invit']);
            session('remarks', $remark);
            $this->ajaxReturn(['status'=>2,'code'=>'sdsdsd','info'=>'2FA required , Take user to Login/check2fa !',]);

        }
        $mo = M();
        $mo->startTrans();
        $rs = [];
        $rs[] = $mo->table('codono_user')->where(array('id' => $user['id']))->setInc('logins', 1);
        $rs[] = $mo->table('codono_user_log')->add(array('userid' => $user['id'], 'type' => 'APP log in', 'remark' => $remark, 'addtime' => time(), 'addip' => get_client_ip(), 'addr' => get_city_ip(), 'status' => 1));

        if (check_arr($rs)) {
            if (!$token = $user['token']) {
                $token = md5(md5(rand(0, 10000) . md5(time()), md5(uniqid())));
                M('User')->where(array('id' => $user['id']))->setField('token', $token);
            }

            S('APP_AUTH_ID_' . $user['id'], $token);
            $mo->commit();
            

            if (!$user['invit']) {
                for (; true;) {
                    $tradeno = tradenoa();

                    if (!M('User')->where(array('invit' => $tradeno))->find()) {
                        break;
                    }
                }

                M('User')->where(array('id' => $user['id']))->setField('invit', $tradeno);
            }
			if($user['paypassword']==null){
				$fundpass=0;
			}else{
				$fundpass=1;
			}	 
			$name=$user['truename']?:$user['username'];
            $this->success(array('ID' => $user['id'], 'TOKEN' => $token, 'msg' => 'login successful!','fundpass'=>$fundpass,'NAME'=>$name));
        } else {
            $mo->rollback();
            $this->error(L('LOGIN_FAILED'));
        }
    }

    public function login($username, $password, $gacode = 0)
    {

        if (check($username, 'email')) {
            $user = M('User')->where(array('email' => $username))->find();
            $remark = 'API  Email Login:' . date('Y-m-d H:i:s');
        }

        if (!$user && check($username, 'username')) {
            $user = M('User')->where(array('username' => $username))->find();
            $remark = 'API User Login:' . date('Y-m-d H:i:s');
        }

        if (!$user) {
            $user = M('User')->where(array('username' => $username))->find();
            $remark = 'Login with username';
        }

        $check_time = 10;
        $check_times = 5;
        $username_msg = md5($username);
        $ltimes = (int)S('LOGIN_ERR_TIMES_' . $username_msg);

        if ($check_times <= $ltimes) {
            $ltime = S('LOGIN_ERR_TIME_' . $username_msg);
            $ltime = time() - $ltime;

            if ($check_time <= $ltime) {
                S('LOGIN_ERR_TIMES_' . $username_msg, 0);
                $this->error('Wrong password lock release,Please try again');
            }

            $min = $check_time - $ltime;
            $this->error('Wrong password too many times,please try after ' . $min . ' mins !');
        }

        if (!$user) {
            $this->error(L('USER_DOES_NOT_EXISTS'));
        }

        if (!check($password, 'password')) {
            $this->error(L('INVALID_PASSWORD'));
        }

        if (md5($password) != $user['password']) {
            S('LOGIN_ERR_TIMES_' . $username_msg, ++$ltimes);
            S('LOGIN_ERR_TIME_' . $username_msg, time());
            $this->error('Password wrong,' . (($check_times - $ltimes) + 1) . ' attempts left');
        }

        if ($user['status'] != 1) {
            $this->error('Your account has been frozen, please contact the administrator!');
        }
        //Here we check if 2fa is enabled if Yes Then
        $ga_status = $user['ga'] ? 1 : 0;
        //Google2FA IS ENABLED  For login
        if ($ga_status == 1) {
            $arr = explode('|', $user['ga']);
            $is_ga = $arr[1];
        } else {
            $is_ga = 0;
        }
        if ($is_ga == 1) {

            if ($gacode == 0) {
                $this->error(L("User/pass are good !This Account Needs 2Fa, resend gacode to same api"));
            }
            $secret = $arr[0];
            $ga = new \Common\Ext\GoogleAuthenticator();
            $ga_verification = $ga->verifyCode($secret, $gacode, 1);
            if (!$ga_verification) {
                $this->error(L('Verification failed'));
            }
        }
        $mo = M();
        $mo->startTrans();
        $rs = [];
        $rs[] = $mo->table('codono_user')->where(array('id' => $user['id']))->setInc('logins', 1);
        $rs[] = $mo->table('codono_user_log')->add(array('userid' => $user['id'], 'type' => 'APP log in', 'remark' => $remark, 'addtime' => time(), 'addip' => get_client_ip(), 'addr' => get_city_ip(), 'status' => 1));

        if (check_arr($rs)) {
            if (!$token = $user['token']) {
                $token = md5(md5(rand(0, 10000) . md5(time()), md5(uniqid())));
                M('User')->where(array('id' => $user['id']))->setField('token', $token);
            }

            S('APP_AUTH_ID_' . $user['id'], $token);
            $mo->commit();
            

            if (!$user['invit']) {
                for (; true;) {
                    $tradeno = tradenoa();

                    if (!M('User')->where(array('invit' => $tradeno))->find()) {
                        break;
                    }
                }

                M('User')->where(array('id' => $user['id']))->setField('invit', $tradeno);
            }

            $this->success(array('ID' => $user['id'], 'TOKEN' => $token, 'msg' => 'login successful!'));
        } else {
            $mo->rollback();
            $this->error(L('LOGIN_FAILED'));
        }
    }

    public function loginout()
    {
        $uid = $this->userid();
        M('User')->where(array('id' => $uid))->setField('token', '');
        S('APP_AUTH_ID_' . $uid, null);
        $this->ajaxShow('exit successfully');
    }


    public function findpaypwd()
    {
        if (IS_POST) {
            $input = I('post.');

            if (!check($input['username'], 'username')) {
                $this->error(L('INVALID_USERNAME'));
            }

            if (!check($input['cellphone'], 'cellphone')) {
                $this->error(L('INVALID_PHONE_FORMAT'));
            }

            if (!check($input['cellphone_verify'], 'd')) {
                $this->error(L('INVALID_SMS_CODE'));
            }

            if ($input['cellphone_verify'] != session('findpaypwd_verify')) {
                $this->error(L('INCORRECT_SMS_CODE'));
            }

            $user = M('User')->where(array('username' => $input['username']))->find();

            if (!$user) {
                $this->error('Username does not exists');
            }

            if ($user['cellphone'] != $input['cellphone']) {
                $this->error('User name or phone number wrong!');
            }

            if (!check($input['password'], 'password')) {
                $this->error('The new Fund Pwd format error!');
            }

            if ($input['password'] != $input['repassword']) {
                $this->error(L('INCORRECT_NEW_PWD'));
            }

            $mo = M();
            
            $mo->execute('lock tables codono_user write , codono_user_log write ');
            $rs = [];
            $rs[] = $mo->table('codono_user')->where(array('id' => $user['id']))->save(array('paypassword' => md5($input['password'])));

            if (check_arr($rs)) {
                $mo->commit();
                
                $this->success('Successfully modified');
            } else {
                $mo->rollback();
                $this->error('No changes were made!');
            }
        } else {
            $this->display();
        }
    }

    public function check_reg($username, $mobile, $password, $verify, $invit)
    {
        if (!check($username, 'username')) {
            $this->error(L('INVALID_USERNAME'));
        }

        if (!check($mobile, 'cellphone')) {
            $this->error('Malformed phone number!');
        }

        if (!check($password, 'password')) {
            $this->error(L('INVALID_PASSWORD'));
        }

        $code = S('sendMobile_code_' . $mobile);

        if ($verify != $code) {
            $this->error(L('INCORRECT_SMS_CODE'));
        }

        if ($invit && !check($invit, 'username')) {
            $this->error('Invalid invite code!');
        }

        if (M('User')->where(array('username' => $username))->find()) {
            $this->error('Username already exists');
        }

        if (M('User')->where(array('cellphone' => $mobile))->find()) {
            $this->error('Phone number already exists');
        }

        $reg_code = md5(round(0, 100) . time());
        S($reg_code, $reg_code);
        $this->ajaxShow(array('reg_code' => $reg_code), 11);
    }

    public function reg($username, $mobile, $password, $invit, $truename, $idcard, $paypassword, $reg_code)
    {
        $this->error('Function is invalid!');
        die('Function is invalid');
        if (!check($username, 'username')) {
            $this->ajaxShow(L('INVALID_USERNAME'), -5);
        }

        if (!check($mobile, 'cellphone')) {
            $this->ajaxShow('Malformed phone number!', -5);
        }

        if (!check($password, 'password')) {
            $this->ajaxShow(L('INVALID_PASSWORD'), -5);
        }

        if ($reg_code != S($reg_code)) {
            $this->ajaxShow('Registration Timeout...', -5);
        }

        if ($invit && !check($invit, 'username')) {
            $this->ajaxShow('Invalid invite code!', -5);
        }

        if (M('User')->where(array('username' => $username))->find()) {
            $this->error('Username already exists');
        }

        if (M('User')->where(array('cellphone' => $mobile))->find()) {
            $this->error('Phone number already exists');
        }

        if (!check($paypassword, 'password')) {
            $this->error('Fund Pwd format error!');
        }

        if (!check($truename, 'truename')) {
            $this->error('Truename Format Error');
        }

        if (!check($idcard, 'idcard')) {
            $this->error('ID card wrong format!');
        }

        if (!$invit) {
            $invit = session('invit');
        }

        $invituser = M('User')->where(array('invit' => $invit))->find();

        if (!$invituser) {
            $invituser = M('User')->where(array('id' => $invit))->find();
        }

        if (!$invituser) {
            $invituser = M('User')->where(array('username' => $invit))->find();
        }

        if (!$invituser) {
            $invituser = M('User')->where(array('cellphone' => $invit))->find();
        }

        if ($invituser) {
            $invit_1 = $invituser['id'];
            $invit_2 = $invituser['invit_1'];
            $invit_3 = $invituser['invit_2'];
        } else {
            $invit_1 = 0;
            $invit_2 = 0;
            $invit_3 = 0;
        }

        for (; true;) {
            $tradeno = tradenoa();

            if (!M('User')->where(array('invit' => $tradeno))->find()) {
                break;
            }
        }

        $mo = M();
        $mo->startTrans();
        $rs = [];
        $rs[] = $mo->table('codono_user')->add(array('username' => $username, 'password' => md5($password), 'invit' => $tradeno, 'idcard' => $idcard, 'cellphone' => $mobile, 'truename' => $truename, 'paypassword' => $paypassword, 'tpwdsetting' => 1, 'invit_1' => $invit_1, 'invit_2' => $invit_2, 'invit_3' => $invit_3, 'addip' => get_client_ip(), 'addr' => get_city_ip(), 'addtime' => time(), 'status' => 1));
        $rs[] = $mo->table('codono_user_coin')->add(array('userid' => $rs[0]));

        if (check_arr($rs)) {
            $mo->commit();
            
            $this->success('registration success');
        } else {
            $this->error('registration failed!');
        }
    }

    public function sendCellphone($cellphone)
    {
        if (!check($cellphone, 'cellphone')) {
            $this->ajaxShow(L('INVALID_PHONE_FORMAT'), -1);
        }

        if (M('User')->where(array('cellphone' => $cellphone))->find()) {
            $this->error('Phone number already exists');
        }

        $reg_session_id = md5(round(0, 1000) . time());
        $code = rand(1000, 9999);
        S('sendMobile_code_' . $cellphone, $code);
        //$this->ajaxShow(array('session_id' => $reg_session_id, 'msg' => 'Verification code has been sent to :' . $cellphone . '(' . $code . ')'));

        if (APP_DEMO == 1 || ADMIN_DEBUG == 1) {
            //$this->ajaxShow(array('session_id' => $reg_session_id, 'msg' => 'Verification code has been sent to :' . $cellphone . '(' . $code . ')'));
            $this->ajaxShow(array('session_id' => $reg_session_id, 'msg' => 'Verification code has been sent to :' . $cellphone));
        } else {
            $this->ajaxShow(array('session_id' => $reg_session_id, 'msg' => 'Verification code has been sent to :' . $cellphone));
        }
    }




    /*2FA Authentication*/
    public function validate2fa()
    {
        $gacode = I('post.gacode', '', 'text');
        $username = I('post.username', '', 'text');
        $password = I('post.password', '', 'text');
        if (!$gacode) {
            $this->error(L('INVALID_CODE'));
        }
        if (check($username, 'email') || check($username, 'username')) {
            
            $user = M('User')->where(array('_logic' => 'OR', 'email' => $username, 'username' => $username))->find();
            if ($user) {
                $remark = check($username, 'email') ? 'API Email Login' : 'API Username Login';
            } else {
                $this->error('No user account found!');
            }
        } else {
            $this->error('Invalid username or email format!');
        }

        if (!$user) {
            $this->error(L('USER_DOES_NOT_EXISTS'));
        }

        if (!check($password, 'password')) {
            $this->error(L('INVALID_PASSWORD'));
        }

        if (md5($password) != $user['password']) {
            $this->error('Password is incorrect, Please retry to login');
        }
        
        $arr = explode('|', $user['ga']);
        $secret = $arr[0];
        $ga = new \Common\Ext\GoogleAuthenticator();
        $ga_verification = $ga->verifyCode($secret, $gacode, 1);
        if ($ga_verification) {
            //check  ga_login too
            //$this->success(L('Successful operation'));
            //Keep doing the login process now

        } else {
            $this->error(L('Verification failed'));
        }

        $ip = get_client_ip();
        $logintime = time();
        $token_user = md5($user['id'] . $logintime);
        session('token_user', $token_user);

        $mo = M();
        $mo->startTrans();
        $rs = [];
        $rs[] = $mo->table('codono_user')->where(array('id' => $user['id']))->setInc('logins', 1);
        $rs[] = $mo->table('codono_user_log')->add(array('userid' => $user['id'], 'type' => 'APP Login', 'remark' => '', 'addtime' => time(), 'addip' => get_client_ip(), 'addr' => get_city_ip(), 'status' => 1));

        if (strict_check_arr($rs)) {
            if (!$token = $user['token']) {
                $token = md5(md5(rand(0, 10000) . md5(time()), md5(uniqid())));
                M('User')->where(array('id' => $user['id']))->setField('token', $token);
            }

            S('APP_AUTH_ID_' . $user['id'], $token);
            $mo->commit();
            

            if (!$user['invit']) {
                for (; true;) {
                    $tradeno = tradenoa();

                    if (!M('User')->where(array('invit' => $tradeno))->find()) {
                        break;
                    }
                }

                M('User')->where(array('id' => $user['id']))->setField('invit', $tradeno);
            }

            $this->success(array('ID' => $user['id'], 'TOKEN' => $token, 'msg' => 'login successful!'));
        } else {
            $mo->rollback();
            $this->error(L('LOGIN_FAILED'));
        }
    }
    public function check2fa()
    {
        $gacode = I('post.ga', '', 'text');
        if (!$gacode) {
            $this->error(L('INVALID_CODE'));
        }
        $userx['id'] = $_SESSION['uid'];
        $userx['userName'] = $_SESSION['uname'];
        $userx['invit'] = $_SESSION['invitecode'];
        $userx['remarks'] = $_SESSION['remarks'];
        $user = M('User')->where(array('id' => $userx['id']))->find();
        $arr = explode('|', $user['ga']);
        $secret = $arr[0];
        $ga = new \Common\Ext\GoogleAuthenticator();
        $ga_verification = $ga->verifyCode($secret, $gacode, 1);
        if ($ga_verification) {
            //check  ga_login too
            //$this->success(L('Successful operation'));
            //Keep doing the login process now

        } else {
            $this->error(L('Verification failed'));
        }

        $ip = get_client_ip();
        $logintime = time();
        $token_user = md5($user['id'] . $logintime);
        session('token_user', $token_user);

        $mo = M();
        $mo->startTrans();
        $rs = [];
        $rs[] = $mo->table('codono_user')->where(array('id' => $user['id']))->setInc('logins', 1);
        $rs[] = $mo->table('codono_user_log')->add(array('userid' => $user['id'], 'type' => 'APP Login', 'remark' => '', 'addtime' => time(), 'addip' => get_client_ip(), 'addr' => get_city_ip(), 'status' => 1));

        if (strict_check_arr($rs)) {
            if (!$token = $user['token']) {
                $token = md5(md5(rand(0, 10000) . md5(time()), md5(uniqid())));
                M('User')->where(array('id' => $user['id']))->setField('token', $token);
            }

            S('APP_AUTH_ID_' . $user['id'], $token);
            $mo->commit();
            

            if (!$user['invit']) {
                for (; true;) {
                    $tradeno = tradenoa();

                    if (!M('User')->where(array('invit' => $tradeno))->find()) {
                        break;
                    }
                }

                M('User')->where(array('id' => $user['id']))->setField('invit', $tradeno);
            }

            $this->success(array('ID' => $user['id'], 'TOKEN' => $token, 'msg' => 'login successful!'));
        } else {
            $mo->rollback();
            $this->error(L('LOGIN_FAILED'));
        }
    }
	/* Fund password reset code Login required */
	public function fundpasscode()
    {
        $uid = $this->userid();
        $input =  json_decode(file_get_contents('php://input'), true);
		$user_all_info = M('User')->where(array('id' => $uid))->find();
        $email = $user_all_info['email'];

        if (!check($email, 'email')) {
            $this->error(L('INVALID_EMAIL_FORMAT'));
        }
        $code = rand(111111, 999999);

        $this->verification_code($email, $code, 'add');

        $subject = "Fund Password code for " . SHORT_NAME;
        $content = 'Hello There,<br/>
			This email is to reset your fund password on ' . SHORT_NAME . '<br/>
			Your verification code is :' . $code . ' <br/>
			Please disregard if you didnt attempt to reset.
			';
        $mail_Sent = addnotification($email, $subject, $content);
        if ($mail_Sent) {
            $this->success(L('Verification code has been sent'));
        } else {
            $this->error(L('Unable to send email Code, Retry in sometime'));
        }
    }
	
	public function confirmfundcode($verify_code, $password)
    {
		$uid = $this->userid();
		
        if (!check($password, 'password')) {
            $this->error("Password Format: 5-15 chars");
        }
		if (!$verify_code) {
            $this->error('Please enter verfication code');
        }
        $user = M('User')->where(array('id' => $uid))->find();
		$email=$user['email'];
		
		if (!check($email, 'email')) {
            $this->error(L('INVALID_EMAIL_FORMAT'));
        }
        

        $resp = json_decode($this->verification_code($email, $verify_code, 'verify'), true);

        if ($resp['status'] != 1) {
            $this->error(L('Incorrect Email Code'));
        }

        $save_details = M('User')->where(array('id' => $user['id']))->save(array('paypassword' => md5($password)));

        if ($save_details) {
            $this->success('Successfully updated!');
        } else {
            $this->error('Try another password!');
        }
    }

}
