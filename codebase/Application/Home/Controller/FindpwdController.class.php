<?php

namespace Home\Controller;

class FindpwdController extends HomeController
{

    public function check_cellphone($cellphone = 0)
    {

        if (!check($cellphone, 'cellphone')) {
            $this->error(L('INVALID_PHONE_FORMAT'));
        }

        if (M('User')->where(array('cellphone' => $cellphone))->find()) {
            $this->error(L('Phone number already exists!'));
        }

        $this->success('');

    }


    public function check_pwdcellphone($cellphone = 0)
    {

        if (!check($cellphone, 'cellphone')) {
            $this->error(L('INVALID_PHONE_FORMAT'));
        }

        if (!M('User')->where(array('cellphone' => $cellphone))->find()) {
            $this->error(L('The phone number does not exist!'));
        }

        $this->success('');

    }


    public function real($cellphone, $verify)
    {

        if (!check_verify($verify)) {
            $this->error('Incorrect Captcha !');
        }

        if (!check($cellphone, 'cellphone')) {
            $this->error(L('INVALID_PHONE_FORMAT'));
        }

        if (M('User')->where(array('cellphone' => $cellphone))->find()) {
            $this->error(L('Phone number already exists!'));
        }

        $code = rand(111111, 999999);
        session('real_verify', $code);
        $content = L('Your phone operation in progress, your verification code is') . $code;

        if (send_cellphone($cellphone, $content)) {
            if (MOBILE_CODE == 1 && APP_DEMO==1 ) {
                $this->success(L('Use Demo SMS Code:') . $code);
            } else {
                $this->success(L('Verification code has been sent'));
            }
        } else {
            $this->error('Failed to send a verification code,Please resend');
        }
    }
	
	
    public function emailcode($verify)
    {
		
        if (!check_verify($verify)) {
            $this->error('Incorrect Captcha !');
        }

		if (!userid()) {
                redirect(U('Login/login'));
        }
		$user=$this->userinfo;//M('User')->where(array('id' => userid()))->find();
        
		$user_name=$user['username'];
		$user_email=$user['email'];

        $code = tradeno();
        session('real_verify', $code);
		$ip = get_client_ip();
		$login_date_time=date('Y-m-d H:i',time()).'('.date_default_timezone_get().')';
		$reset_content="Hi $user_name,<br/> We have received a new fundpassword reset request <br/><br/><br/><table style='border:2px solid black;width:100%'><tr style='border:1px solid black;width:100%'><td>Email</td><td>$user_email</td></tr><tr style='border:1px solid black;width:100%'><td>IP</td><td>$ip</td></tr><tr style='border:1px solid black;width:100%'><td>Time</td><td>$login_date_time</td></tr><tr><td>Code</td><td>$code</td></tr></table><br/><br/>";
		$subject ="Fund password reset request From IP ".$ip." - ".$login_date_time;
		addnotification($user_email,$subject,$reset_content);

		 $this->success(L('Verification code has been sent'));
    }


    public function paypassword()
    {
        if (!session('reguserId')) {
            redirect(U('Login/login'));
        }
        $this->display();
    }

    public function info()
    {

        if (!session('reguserId')) {
            redirect(U('Login/login'));
        }

        $user = M('User')->where(array('id' => session('reguserId')))->find();


        if (!$user) {
            $this->error(L('Please register'));
        }
        if ($user['regaward'] == 0) {
            if (C('reg_award') == 1 && C('reg_award_num') > 0) {
                M('UserCoin')->where(array('userid' => session('reguserId')))->setInc(C('reg_award_coin'), C('reg_award_num'));
                M('User')->where(array('id' => session('reguserId')))->save(array('regaward' => 1));
            }
        }

        session('userId', $user['id']);
        session('userName', $user['username']);
        $this->assign('user', $user);
        $this->display();
    }


    public function findpwd()
    {
		  if (!userid()) {
                redirect(U('Login/login'));
            }
		if (IS_POST) {
            $input = I('post.');

                if ($input['real_verify'] != session('real_verify')) {
                    $this->error('Incorrect OTP!, Please recheck or resend!');
                }
				if (!check($input['paypassword'], 'password')) {
					$this->error(L('Fund Pwd format error!'));
				}
                $user = $this->userinfo;//M('User')->where(array('id' => userid()))->find();


                if (!$user['username']) {
                    $this->error(L('Username does not exist!'));
                }

                $mo = M();
                
                $mo->startTrans();
                $rs = array();
                $rs[] = $mo->table('codono_user')->where(array('id' => $user['id']))->save(array('paypassword' => md5($input['paypassword'])));

                if (check_arr($rs)) {
                    $mo->commit();
                    
                    $this->success(L('Successfully modified'));
                } else {
                    $mo->rollback();
                    $this->error('No changes were made!');
                }

        } else {
            $this->display();
        }
    }
	
	public function findpwd_old()
    {

        if (IS_POST) {
            $input = I('post.');
            if (M_ONLY == 0) {
                if (!check_verify($input['verify'])) {
                    $this->error('Incorrect Captcha !');
                }

                if (!check($input['username'], 'username')) {
                    $this->error(L('INVALID_USERNAME'));
                }

                if (!check($input['cellphone'], 'cellphone')) {
                    $this->error(L('INVALID_PHONE_FORMAT'));
                }

                if (!check($input['cellphone_verify'], 'd')) {
                    $this->error(L('INVALID_SMS_CODE'));
                }

                if ($input['cellphone_verify'] != session('findpwd_verify')) {
                    $this->error(L('INCORRECT_SMS_CODE'));
                }

                $user = M('User')->where(array('username' => $input['username']))->find();


                if (!$user) {
                    $this->error(L('Username does not exist!'));
                }

                if ($user['cellphone'] != $input['cellphone']) {
                    $this->error(L('User name or phone number wrong!'));
                }

                if (!check($input['password'], 'password')) {
                    $this->error(L('The new password is malformed!'));
                }


                if ($input['password'] != $input['repassword']) {
                    $this->error(L('INCORRECT_NEW_PWD'));
                }


                $mo = M();
                
                $mo->startTrans();
                $rs = array();
                $rs[] = $mo->table('codono_user')->where(array('id' => $user['id']))->save(array('password' => md5($input['password'])));

                if (check_arr($rs)) {
                    $mo->commit();
                    
                    $this->success(L('Successfully modified'));
                } else {
                    $mo->rollback();
                    $this->error('No changes were made!');
                }

            } else {


                if (!check($input['cellphone'], 'cellphone')) {
                    $this->error(L('INVALID_PHONE_FORMAT'));
                }

                $user = M('User')->where(array('cellphone' => $input['cellphone']))->find();

                if (!$user) {
                    $this->error(L('The phone number does not exist'));
                }

                if (!check($input['cellphone_verify'], 'd')) {
                    $this->error(L('INVALID_SMS_CODE'));
                }

                if ($input['cellphone_verify'] != session('findpwd_verify')) {
                    $this->error(L('INCORRECT_SMS_CODE'));
                }
                session("findpaypwdcellphone", $user['cellphone']);
                $this->success(L('Verification success'));
            }

        } else {
            $this->display();
        }
    }


    public function findpwdconfirm()
    {

        if (empty(session('findpaypwdcellphone'))) {
            redirect('/');
        }

        $this->display();
    }

    public function password_up($password = "", $repassword = "")
    {


        if (empty(session('findpaypwdcellphone'))) {
            $this->error(L('Please return with the first step!'));
        }

        if (!check($password, 'password')) {
            $this->error(L('The new Fund Pwd format error!'));
        }

        if (!check($repassword, 'password')) {
            $this->error(L('Confirm password format error!'));
        }


        if ($password != $repassword) {
            $this->error(L('Confirm New Password wrong!'));
        }


        $user = M('User')->where(array('cellphone' => session('findpaypwdcellphone')))->find();

        if (!$user) {
            $this->error(L('The phone number does not exist'));
        }


        if ($user['password'] == md5($password)) {
            $this->error(L('Fund Pwd can not be the same password'));
        }

        $mo = M();
        
        $mo->startTrans();
        $rs = $mo->table('codono_user')->where(array('cellphone' => $user['cellphone']))->save(array('paypassword' => md5($password)));

        if (!($rs === false)) {
            $mo->commit();
            
            $this->success(L('Successful operation'));
        } else {
            $mo->rollback();
            $this->error(L('operation failed'));
        }

    }

    public function findpwdinfo()
    {

        if (empty(session('findpaypwdcellphone'))) {
            redirect('/');
        }
        session('findpaypwdcellphone', "");
        $this->display();
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
                $this->error(L('Username does not exist!'));
            }

            if ($user['cellphone'] != $input['cellphone']) {
                $this->error(L('User name or phone number wrong!'));
            }

            if (!check($input['password'], 'password')) {
                $this->error(L('The new Fund Pwd format error!'));
            }

            if ($input['password'] != $input['repassword']) {
                $this->error(L('Confirm the Fund Pwd is wrong!'));
            }

            $mo = M();
            
            $mo->startTrans();
            $rs = array();
            $rs[] = $mo->table('codono_user')->where(array('id' => $user['id']))->save(array('paypassword' => md5($input['password'])));

            if (check_arr($rs)) {
                $mo->commit();
                
                $this->success(L('Successful operation'));
            } else {
                $mo->rollback();
                $this->error(L('operation failed') . $mo->table('codono_user')->getLastSql());
            }
        } else {
            $this->display();
        }
    }

}