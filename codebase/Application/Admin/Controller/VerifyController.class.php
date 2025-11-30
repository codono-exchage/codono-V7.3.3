<?php

namespace Admin\Controller;

class VerifyController extends \Think\Controller
{
    public function code()
    {
        ob_clean();
        $config['useNoise'] = false;
        $config['length'] = 4;
        $config['codeSet'] = '0123456789';
        $config['bg'] = array(85, 110, 230);
        $verify = new \Think\Verify($config);
        $verify->entry(1);
    }

    public function mobile()
    {
        if (IS_POST) {
            if (check($_POST['mobile'], 'mobile')) {
                $mobile = $_POST['mobile'];
            } else {
                $this->error('Wrong format of phone number!');
            }

            if (empty($_POST['type'])) {
                $this->error('SMS template name is wrong!');
            }

            $Configmobile = D('ConfigMobile')->where(array('id' => $_POST['type']))->find();

            if ($Configmobile) {
                $code = rand(111111, 999999);
                session('mobilecode', $code);
                $content = str_replace('[url]', $code, $Configmobile['content']);
            } else {
                $this->error('SMS template error!');
            }

            C('MOBILE_URL', $_POST['mobile_url']);
            C('MOBILE_USER', $_POST['mobile_user']);
            C('MOBILE_PASS', $_POST['mobile_pass']);
        }

        if (0 < send_cellphone($mobile, $content)) {
            $this->success('SMS sent successfully!');
        } else {
            $this->error('message failed to send!');
        }
    }

    public function email()
    {
        if (IS_POST) {
            if (check($_POST['email'], 'email')) {
                $email = $_POST['email'];
            } else {
                $this->error('Error Message Format!');
            }

            if (empty($_POST['type'])) {
                $this->error('Mail template name is wrong!');
            }

            $Configemail = D('ConfigEmail')->where(array('id' => $_POST['type']))->find();

            if ($Configemail) {
                $code = rand(111111, 999999);
                session('emailcode', $code);
                $content = str_replace('[url]', $code, $Configemail['content']);
                $title = $Configemail['title'];
            } else {
                $this->error('Mail templates error!');
            }

            C('SMTP_HOST', $_POST['smtp_host']);
            C('SMTP_PORT', $_POST['smtp_port']);
            C('SMTP_USER', $_POST['smtp_user']);
            C('SMTP_PASS', $_POST['smtp_pass']);
            C('SMTP_NAME', $_POST['smtp_name']);
            C('SMTP_EMAIL', $_POST['smtp_email']);
			
            addnotification($email, $title, $content);
            $this->success('Notification added!');
        }
		echo json_encode(['status'=>0]);
    }	
}