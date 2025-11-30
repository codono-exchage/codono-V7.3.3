<?php

namespace Home\Controller;

use Think\Verify;

class VerifyController extends HomeController
{
    public function __construct()
    {
        parent::__construct();
    }

    public function code()
    {
        ob_clean();
        $config['useNoise'] = false;
        $config['length'] = 4;
        $config['codeSet'] = '0123456789';
        $verify = new Verify($config);
        $verify->entry(1);
    }


    public function real()
    {
        $cellphone=I('post.cellphone','','text');
        $verify=I('post.verify','','text');
        
        if (!userid()) {
            redirect(U('Login/login'));
        }

        if (!check_verify($verify)) {
            $this->error('Incorrect Captcha!');
        }

        if (!check($cellphone, 'cellphone')) {
            $this->error(L('INVALID_PHONE_FORMAT'));
        }

        if (M('User')->where(array('cellphone' => $cellphone))->find()) {
            $this->error(L('Phone number already exists!'));
        }

        $code = rand(111111, 999999);
        session('real_verify', $code);
        $content = L('Your Verification Code is ') . $code;

        if (send_cellphone($cellphone, $content)) {
            $this->success(L('SMS verification code sent to your phone, please find'));
        } else {
            $this->error(L('SMS verification code fails to send, click Send again'));
        }
    }


    public function send_code_to_mobile()
    {
        $cellphone=I('post.cellphone','','text');
        $cellphone = preg_replace('/[^0-9]/', '', $cellphone);
        $cellphone_new=I('post.cellphone_new','','text');
        $cellphone_new = preg_replace('/[^0-9]/', '', $cellphone_new);

        $country_code=I('post.cellphones_new','','text');
        //$country_code = preg_replace('/[^0-9]/', '', $country_code);
        if (!userid()) {
            $this->error(L('PLEASE_LOGIN'));
        }

        if (!check($cellphone, 'cellphone')) {
            $this->error(L('INVALID_PHONE_FORMAT'));
        }

        if (!check($cellphone_new, 'cellphone')) {
            $this->error(L('The new phone number format error!'));
        }
        if (!preg_match('/(\+\d\d{0,3})/', $country_code)) {
            $this->error(L('Incorrect country code') . $country_code);
        }    

        if (M('User')->where(array('cellphone' => $cellphone_new))->find()) {
            $this->error('This number already belongs to a member!');
        }

        $code = rand(111111, 999999);
        session('real_verify', $code);
        $content = L('Your phone operation in progress, your verification code is') . ': ' . $code;

        $new_number = $country_code . $cellphone_new;
        if (send_cellphone($new_number, $content)) {

            if (MOBILE_CODE == 1 && APP_DEMO == 1) {
                //$this->success(L('Use Demo SMS Code:') . $code);
                $this->error('App is in demo mode');
            } else {
                $this->success(L('SMS verification code sent to your phone, please find'.$code));
            }

        } else {
            
            $this->error(L('SMS verification code fails to send, click Send again'));
        }
    }

    public function verifyCode()
    {
        $cellphones_new=I('post.cellphones_new','','text');
        $cellphone_new=I('post.cellphone_new','','text');
        $cellphone_new = preg_replace('/[^0-9]/', '', $cellphone_new);
        $cellphone_verify_new=I('post.cellphone_verify_new','','text');   
        if (!userid()) {
            $this->error(L('YOU_NEED_TO_LOGIN'));
        }
        if (!ENABLE_MOBILE_VERIFY) {
            $this->error(L('No Access'));
        }

        if (!check($cellphone_new, 'cellphone')) {
            $this->error(L('INVALID_PHONE_FORMAT'));
        }

        if (!check($cellphone_verify_new, 'd')) {
            $this->error(L('INVALID_SMS_CODE'));
        }

        if ($cellphone_verify_new != session('real_verify')) {
            $this->error(L('INCORRECT_SMS_CODE'));
        }
        //Check country code
        if (!preg_match('/(\+\d\d{0,3})/', $cellphones_new)) {
            $this->error(L('Incorrect country code') . $cellphones_new);
        }


        if (M('User')->where(array('cellphone' => $cellphone_new))->find()) {
            $this->error(L('Phone number already exists!'));
        }

        $rs = M('User')->where(array('id' => userid()))->save(array('cellphone' => $cellphone_new, 'cellphones' => $cellphones_new, 'cellphonetime' => time()));

        if (!($rs === false)) {
            $this->success(L('Mobile Added!'));
        } else {
            $this->error(L('Mobile Update failed!'));
        }
    }


    public function cellphone()
    {
        $uid = userid();
        if (!$uid) {
            redirect(U('Login/login'));
        }

        if (session('real_cellphone')) {
            $this->success(L('SMS verification code sent to your phone, please note that check'));
        }
        $user_data = M('User')->where(array('id' => $uid))->field('cellphone, cellphones')->find();

        $cellphone = $user_data['cellphone'];
        $cellphones = $user_data['cellphones'];
        if (!$cellphone) {
            $this->error(L('The phone number is not bound!'), U('User/cellphone'));
        }

        $code = rand(111111, 999999);
        session('real_cellphone', $code);
        $content = L('Your phone verification code') .':'. $code;
        $new_number = $cellphones . $cellphone;
        if (send_cellphone($new_number, $content)) {
            $this->success(L('SMS verification code sent to your phone, please find'));
        } else {
            $this->error(L('SMS verification code fails to send, click Send again'));
        }
    }

    public function mytx()
    {
        $uid = userid();
        if (!$uid) {
            $this->error(L('please log in first'));
        }

        $user_data = M('User')->where(array('id' => $uid))->field('cellphone, cellphones')->find();

        $cellphone = $user_data['cellphone'];
        $cellphones = $user_data['cellphones'];
        if (!$cellphone) {
            $this->error(L('Your phone is not certified'));
        }

        $code = rand(111111, 999999);
        session('mytx_verify', $code);
        $content = L('You have an ongoing application for withdrawal operation, your verification code is') . ':' . $code;
        $full_number = $cellphones . $cellphone;
        if (send_cellphone($full_number, $content)) {
            $this->success(L('SMS verification code sent to your phone, please find'));
        } else {
            $this->error(L('SMS verification code fails to send, click Send again'));
        }
    }


    public function cellphone_findpwd()
    {
        $this->otpGeneration('findpwd_verify');
    }

    private function otpGeneration($sessionName)
    {
        if (IS_POST) {
            $input = I('post.');

            if (!check_verify($input['verify'])) {
                $this->error('Incorrect Captcha !');
            }


            if (!check($input['cellphone'], 'cellphone')) {
                $this->error(L('INVALID_PHONE_FORMAT'));
            }

            $user = M('User')->where(array('cellphone' => $input['cellphone']))->find();

            if (!$user) {
                $this->error(L('The phone number does not exist!'));
            }


            $code = rand(111111, 999999);
            session($sessionName, $code);
            $content = L('You have an ongoing operation to recover the password, your verification code is') . $code;

            if (send_cellphone($input['cellphone'], $content)) {

                if (MOBILE_CODE == 1 && APP_DEMO == 1) {
                    $this->success(L('Use Demo SMS Code:') . $code);
                } else {
                    $this->success(L('SMS verification code sent to your phone, please find'));
                }
            } else {
                $this->error(L('SMS verification code fails to send, click Send again'));
            }
        }
    }

    public function email_findpwd()
    {
        if (IS_POST) {
            $input = I('post.');

            if (!check_verify($input['verify'])) {
                $this->error('Incorrect Captcha !');
            }


            if (!check($input['email'], 'email')) {
                $this->error(L('INVALID_EMAIL'));
            }

            $user = M('User')->where(array('email' => $input['email']))->find();

            if (!$user) {
                $this->error(L('Email does not exist!'));
            }

            safeLog($user['email'], 15, 'Verify/email_findpwd');   
            $code = rand(111111, 999999);
            session('findpwd_verify', $code);
            $content = 'Your email password reset code is: ' . $code . ' , We suggest you do not share this with no one.';

            addnotification($input['email'], 'Password Reset', $content);

            $this->success(L('Verification code has been sent'));

        }
    }


    public function findpaypwd()
    {
        $this->otpGeneration('findpaypwd_verify');
    }

    public function myzc()
    {
        $uid = userid();
        if (!$uid) {
            $this->error('Please Login!');
        }


        $user_data = M('User')->where(array('id' => $uid))->field('cellphone, cellphones')->find();

        $cellphone = $user_data['cellphone'];
        $cellphones = $user_data['cellphones'];
        if (!$cellphone) {
            $this->error(L('Your phone is not certified'));
        }

        $code = rand(111111, 999999);
        session('myzc_verify', $code);
        $content = L('You have an ongoing application Withdrawal operation, your verification code is') . ':' . $code;
        $full_number = $cellphones . $cellphone;
        if (send_cellphone($full_number, $content)) {
            if (MOBILE_CODE == 1 && APP_DEMO == 1) {
                $this->success(L('Use Demo SMS Code:') . $code);
            } else {
                $this->success(L('SMS verification code sent to your phone, please find'));
            }
        } else {
            $this->error(L('SMS verification code fails to send, click Send again'));
        }
    }


    public function myzr()
    {
        $uid = userid();
        if (!$uid) {
            $this->error('Please login!');
        }

        $user_data = M('User')->where(array('id' => $uid))->field('cellphone, cellphones')->find();

        $cellphone = $user_data['cellphone'];
        $cellphones = $user_data['cellphones'];

        if (!$cellphone) {
            $this->error(L('Your phone is not certified'));
        }

        $code = rand(111111, 999999);
        session('myzr_verify', $code);
        $content = L('You apply to transfer to ongoing operations, your verification code is') . $code;

        if (send_cellphone($cellphone, $content)) {
            if (MOBILE_CODE == 1 && APP_DEMO == 1) {
                $this->success(L('Use Demo SMS Code:') . $code);
            } else {
                $this->success(L('SMS verification code sent to your phone, please find'));
            }
        } else {
            $this->error(L('SMS verification code fails to send, click Send again'));
        }
    }

}