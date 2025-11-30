<?php

namespace Admin\Controller;

use Common\Ext\GoogleAuthenticator;

class LoginController extends \Think\Controller
{
    const MAX_2FA_ATTEMPTS = 10;

    public function Google2FA()
    {
        if (session('2fa_id') || session('2fa_id') != 0) {
            $this->display();
        } else {
            //die('2fa_id not found');
            $this->error(U('Admin/Login'));
        }
    }

    public function check2fa()
    {
        $admin_login_ip = get_client_ip();
        $logintime = time();
        $date_time = addtime($logintime);
        $attempt = session('2fa_attempt') ? session('2fa_attempt') : 0;
        $gacode = I('post.gacode', '', 'text');
        if (!$gacode) {
            $this->error(L('INVALID_CODE'));
        }
        if (strlen($gacode)!=6) {
            $this->error('2Fa code must be 6 digits!');
        }
        $admin_id = session('2fa_id');

        $admin = M('Admin')->where(['id' => $admin_id])->find();
        if ($admin_id != $admin['id']) {
            $this->error(L('Invalid attempt!'));
        }
        if ($admin['status'] == 0) {
            $this->error(L('Your Admin account is Frozen!'));
        }
        $ga_arr = explode('|', $admin['ga']);
        $secret = $ga_arr[0];
        $google_auth = new GoogleAuthenticator();
        $ga_verification = $google_auth->verifyCode($secret, $gacode, 1);
        $mo = M();
        if (!$ga_verification) {
            $attempt_count = session('2fa_attempt') + 1;
            session('2fa_attempt', $attempt_count);
            if ($attempt_count > self::MAX_2FA_ATTEMPTS) {

                $mo->table('codono_admin')->where(['id' => $admin['id']])->save(['status' => 0]);
                $subject = 'Multiple 2FA attempts From IP ' . $admin_login_ip . ' - ' . $date_time;

                $login_content = 'Hi ' . $admin['username'] . ",<br/> We have detected several failed 2fa ,  Your admin account has been blocked now !<br/><br/><br/><table style='border:2px solid black;width:100%'><tr style='border:1px solid black;width:100%'><td>Email</td><td>" . $admin['email'] . "</td></tr><tr style='border:1px solid black;width:100%'><td>IP</td><td>$admin_login_ip</td></tr><tr style='border:1px solid black;width:100%'><td>Time</td><td>" . $date_time . '</td></tr></table><br/><br/>';

                addnotification($admin['email'], $subject, $login_content);

                $this->error(L('Multiple 2FA attempts Account is frozen , Contact Admin!'));
            } else {
                $this->error(L('Verification failed attempt count:') . session('2fa_attempt'));
            }

        }


        if (check($admin['email'], 'email')) {
            $this->addLoginAttempt(1, 1, 1);
            $subject = 'New Admin Login From IP ' . $admin_login_ip . ' - ' . $date_time;

            $login_content = 'Hi ' . $admin['username'] . ",<br/> We have detected a new login <br/><br/><br/><table style='border:2px solid black;width:100%'><tr style='border:1px solid black;width:100%'><td>Email</td><td>" . $admin['email'] . "</td></tr><tr style='border:1px solid black;width:100%'><td>IP</td><td>" . $admin_login_ip . "</td></tr><tr style='border:1px solid black;width:100%'><td>Time</td><td>" . $date_time . '</td></tr></table><br/><br/>';
            addnotification($admin['email'], $subject, $login_content);
        }

        $mo->table('codono_admin')->where(['id' => $admin['id']])->save(['last_login_time' => $logintime, 'last_login_ip' => $admin_login_ip]);

        session('admin_id', $admin['id']);
        session('admin_username', $admin['username']);
        session('admin_password', $admin['password']);
        session('2fa_attempt', null);
        $this->success(L('login successful!'), U('Admin/Index'));

    }

    private function addLoginAttempt($passed_adminkey = 0, $passed_login = 0, $passed_2fa = 0): bool
    {
        $ip_address = get_client_ip();
        $browser_agent = getUserAgent();
        $insert_data = [
            'ip_address' => $ip_address,
            'access_time' => date('Y-m-d H:i:s'), // Current time
            'passed_adminkey' => $passed_adminkey,
            'passed_login' => $passed_login,
            'passed_2fa' => $passed_2fa,
            'browser_agent' => $browser_agent,
            // Assume these are determined via some function based on IP/Browser Agent
            'country_code' => '',
            'region_name' => '',
            'city_name' => '',
            'is_proxy_or_vpn' => 0,
            'is_cloudflare' => 0,
        ];
        $model = M('AdminUrlaccess');
        // Insert the record into the database
        if ($model->add($insert_data)) {
            return true; // Success
        } else {
            return false; // Fail
        }

    }

    public function index($username = NULL, $password = NULL, $verify = NULL)
    {
        defined('ADMIN_KEY') || define('ADMIN_KEY', '');
        $codono = trim($_GET['securecode']);
        if (ADMIN_KEY && ($codono != ADMIN_KEY)) {
            $this->addLoginAttempt(0, 0);
            if (ADMIN_DEBUG == 1) {

                echo 'Obtain key from your pure_config.php<br/>';
                die('and try to open domain.com/Admin/Login/index?securecode=keyhere');
            } else {
                $this->redirect('/Content/E403');
            }


        }
        if (IS_POST) {

            if (!check_verify($verify)) {
                $this->addLoginAttempt(1, 0);
                $this->error('Incorrect Captcha!');
            }

            $admin = M('Admin')->where(['username' => $username])->find();

            if ($admin['status'] != 1) {
                $this->addLoginAttempt(1, 0, 0);
                $this->error('Your account has been frozen , Please contact Exchange admin');
            }
            $is_ga = $admin['ga'] ? 1 : 0;

            if ($admin['password'] != md5($password)) {
                $this->addLoginAttempt(1, 0, 0);
                $this->error('User/Pass is Incorrect');
            } else {

                if ($is_ga == 1) {
                    session('2fa_id', $admin['id']);
                    $this->addLoginAttempt(1, 1, 0);
                    $this->success('Please verify 2FA!', U('Login/Google2FA'));
                } else {
                    $this->addLoginAttempt(1, 1, 1);
                    $admin_login_ip = get_client_ip();
                    $logintime = time();
                    if (check($admin['email'], 'email')) {

                        $subject = 'Admin Logged in From IP ' . $admin_login_ip . ' - ' . addtime($logintime);

                        $login_content = 'Hi ' . $admin['username'] . ",<br/> We have detetcted a new login <br/><br/><br/><table style='border:2px solid black;width:100%'><tr style='border:1px solid black;width:100%'><td>Email</td><td>" . $admin['email'] . "</td></tr><tr style='border:1px solid black;width:100%'><td>IP</td><td>" . $admin_login_ip . "</td></tr><tr style='border:1px solid black;width:100%'><td>Time</td><td>" . $logintime . '</td></tr></table><br/><br/>';
                        addnotification($admin['email'], $subject, $login_content);
                    }
                    $mo = M();
                    $mo->table('codono_admin')->where(['id' => $admin['id']])->save(['last_login_time' => $logintime, 'last_login_ip' => $admin_login_ip]);

                    session('admin_id', $admin['id']);
                    session('admin_username', $admin['username']);
                    session('admin_password', $admin['password']);
                    $this->success('Logged in successfully!', U('Index/index'));
                }
            }
        } else {

            if (session('admin_id')) {
                $this->redirect('Admin/Index/index');
            }

            $this->display();
        }
    }

    public function loginout()
    {
        session(null);
        $this->redirect('Login/index');
    }

    public function lockScreen()
    {
        if (!IS_POST) {
            $this->display();
        } else {
            $pass = trim(I('post.pass'));

            if ($pass) {
                session('LockScreen', $pass);
                session('LockScreenTime', 3);
                $this->success('Lock screen success,It is the jump...');
            } else {
                $this->error('Please enter a password lock screen');
            }
        }
    }

    public function unlock()
    {
        if (!session('admin_id')) {
            session(null);
            $this->error('Login has failed,please login again...', '/Admin/login');
        }

        if (session('LockScreenTime') < 0) {
            session(null);
            $this->error('Wrong password too many,please login again...', '/Admin/login');
        }

        $pass = trim(I('post.pass'));

        if ($pass == session('LockScreen')) {
            session('LockScreen', null);
            $this->success('Unlock Success', '/Admin/index');
        }

        $admin = M('Admin')->where(['id' => session('admin_id')])->find();

        if ($admin['password'] == md5($pass)) {
            session('LockScreen', null);
            $this->success('Unlock Success', '/Admin/index');
        }

        session('LockScreenTime', session('LockScreenTime') - 1);
        $this->error('wrong user name or password!');
    }
}