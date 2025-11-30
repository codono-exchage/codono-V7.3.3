<?php

namespace Think;
vendor('vendor.autoload');

use Omnimail\Email;
use Omnimail\AmazonSES;
use Omnimail\Mailjet;
use Omnimail\Mandrill;
use Omnimail\Postmark;



class SuperEmail
{
    public function __construct()
    {

//        parent::__construct();
    }

    /**
     * @throws \Omnimail\Exception\Exception
     */
    public static function sendemail($to, $subject, $content)
    {
        $super = new SuperEmail();
        if (empty($subject)) {
            $subject = 'Mail from ' . SHORT_NAME;
        }
        if (empty($content)) {
            $subject = 'No content was  from provided in email';
        }

        if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
            die('Invalid email');
        }
        if (APP_DEMO == 0) {
            $array = ['status' => 1, 'message' => 'Email Ran'];
            switch (DEFAULT_MAILER) {
                case 'sendgrid':
                    $super->sendgrid($to, $subject, $content);
                    break;
                case 'sendinblue':
                    $super->sendinblue($to, $subject, $content);
                    break;
                case 'mailjet':
                    $super->mailjet($to, $subject, $content);
                    break;
                case 'mailgun':
                    $super->mailgun($to, $subject, $content);
                    break;
                case 'mandrill':
                    $super->mandrill($to, $subject, $content);
                    break;
                case 'amazonses':
                    $super->amazonses($to, $subject, $content);
                    break;
                case 'postmark':
                    $super->postmark($to, $subject, $content);
                    break;
                case 'phpmail':
                    $super->phpmail($to, $subject, $content);
                    break;
                case 'smtpmail':
                    $super->smtpmail($to, $subject, $content);
                    break;
                default :
                    $super->phpmail($to, $subject, $content);

            }
        } else {
            $array = ['status' => 0, 'message' => 'App is in demo mode'];
        }
        return json_encode($array);


    }

    private static function phpmail($to, $subject, $content)
    {
        $headers = 'From: ' . SHORT_NAME . ' <' . GLOBAL_EMAIL_SENDER . ">\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=ISO-8859-1\r\n";
        $mailer = mail($to, $subject, $content, $headers);
        if (M_DEBUG == 1) {
            clog('phpmail', $mailer);
        }
    }

    private function smtpmail($to, $subject, $content)
    {
        $setFrom = GLOBAL_EMAIL_SENDER;
        $mailer = SMTPMail($to, $setFrom, $subject, $content);
        if (ADMIN_DEBUG == 1) {
            clog('smtpmail', $mailer);
        }
    }

    /**
     * @throws \Omnimail\Exception\Exception
     */
    private function amazonses($to, $subject, $content)
    {
        $mailer = new AmazonSES(AMAZONSES_accessKey, AMAZONSES_secretKey, AMAZONSES_region, AMAZONSES_verifyPeer, AMAZONSES_verifyHost);

        $setFrom = GLOBAL_EMAIL_SENDER;
        $email = (new Email())
            ->addTo($to)
            ->setFrom($setFrom)
            ->setSubject($subject)
            ->setTextBody($content);
        $mailer->send($email);
        if (ADMIN_DEBUG == 1) {
            clog('amazonses', $mailer);
        }
    }

    private function sendgrid($to, $subject, $content)
    {

        $params = [
            'to' => $to,
            'from' => GLOBAL_EMAIL_SENDER,
            'fromname' => SHORT_NAME,
            'subject' => $subject,
            'text' => $content,
            'html' => $content,

        ];
        $url = 'https://api.sendgrid.com/';
        $request = $url . 'api/mail.send.json';

        // Generate curl request
        $session = curl_init($request);
        // Tell PHP not to use SSLv3 (instead opting for TLS)
        curl_setopt($session, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1_2);
        curl_setopt($session, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . SENDGRID_API_KEY]);
        // Tell curl to use HTTP POST
        curl_setopt($session, CURLOPT_POST, true);
        // Tell curl that this is the body of the POST
        curl_setopt($session, CURLOPT_POSTFIELDS, $params);
        // Tell curl not to return headers, but do return the response
        curl_setopt($session, CURLOPT_HEADER, false);
        curl_setopt($session, CURLOPT_RETURNTRANSFER, true);

        // obtain response
        $response = curl_exec($session);
        curl_close($session);

        // print everything out
        if (ADMIN_DEBUG == 1) {
            clog('sendgrid', $response);
        }
    }


    private function mandrill($to, $subject, $content)
    {
        $mailer = new Mandrill(MANDRILL_API_KEY);
        $setFrom = GLOBAL_EMAIL_SENDER;
        $email = (new Email())
            ->addTo($to)
            ->setFrom($setFrom)
            ->setSubject($subject)
            ->setTextBody($content);
        $mailer->send($email);
        if (ADMIN_DEBUG == 1) {
            clog('mandrill', $mailer);
        }
    }


    private function postmark($to, $subject, $content)
    {
        $mailer = new Postmark(POSTMARK_serverApiToken);
        $setFrom = GLOBAL_EMAIL_SENDER;
        $email = (new Email())
            ->addTo($to)
            ->setFrom($setFrom)
            ->setSubject($subject)
            ->setTextBody($content);
        $mailer->send($email);
        if (ADMIN_DEBUG == 1) {
            clog('postmark', $mailer);
        }
    }

    private function sendinblue($to, $subject, $content)
    {

        $url = 'https://api.brevo.com/v3/smtp/email';

	  $headers = [];
      $headers[] = 'Accept: application/json';
      $headers[] = 'Api-Key: '.SENDINBLUE_API_KEY;
      $headers[] = 'Content-Type: application/json';
	  $curl = curl_init();

	$json="{\"sender\":{\"name\":\"".SHORT_NAME." \",\"email\":\"".GLOBAL_EMAIL_SENDER."\"},\"to\":[{\"email\":\"".$to."\"}],\"htmlContent\":\"".$this->escapeJsonString($content)."\",\"subject\":\"".$subject."\"}";

curl_setopt_array($curl, [
  CURLOPT_URL => $url,
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_ENCODING => '',
  CURLOPT_MAXREDIRS => 10,
  CURLOPT_TIMEOUT => 30,
  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  CURLOPT_CUSTOMREQUEST => 'POST',
  CURLOPT_SSL_VERIFYPEER=> false,
  CURLOPT_POSTFIELDS =>$json,
  
  CURLOPT_HTTPHEADER => $headers,
	]);

	$response = curl_exec($curl);

	$err = curl_error($curl);

	curl_close($curl); 
	  
        // print everything out
        if (M_DEBUG == 1) {
            clog('sendinblue', $response);
        }
		return true;
	}
	private function escapeJsonString($value) { # list from www.json.org: (\b backspace, \f formfeed)
		$escapers = ["\\", '/', "\"", "\n", "\r", "\t", "\x08", "\x0c"];
		$replacements = ["\\\\", "\\/", "\\\"", "\\n", "\\r", "\\t", "\\f", "\\b"];
		return str_replace($escapers, $replacements, $value);
	}

    private function mailjet($to, $subject, $content)
    {
        $mailjet_public_key = MAILJET_PUBLIC_KEY;
        $mailjet_private_secret = MAILJET_PRIVATE_SECRET;

        $mailer = new Mailjet($mailjet_public_key, $mailjet_private_secret);

        $setFrom = GLOBAL_EMAIL_SENDER;
        $email = (new Email())
            ->addTo($to)
            ->setFrom($setFrom)
            ->setSubject($subject)
            ->setHtmlBody($content);
        $mailer->send($email);
        if (ADMIN_DEBUG == 1) {
            clog('mailjet', $mailer);
        }
    }

    private function mailgun($to, $subject, $content)
    {

        $array_data = [
            'from' => GLOBAL_EMAIL_SENDER,
            'to' => $to,
            'subject' => $subject,
            'html' => $content,
            'text' => $content,
            'o:tracking' => 'yes',
            'o:tracking-clicks' => 'yes',
            'o:tracking-opens' => 'yes',
            'h:Reply-To' => GLOBAL_EMAIL_SENDER
        ];
        $session = curl_init(MAILGUN_DOMAIN . '/messages');
        curl_setopt($session, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($session, CURLOPT_USERPWD, 'api:' . MAILGUN_API_KEY);
        curl_setopt($session, CURLOPT_POST, true);
        curl_setopt($session, CURLOPT_POSTFIELDS, $array_data);
        curl_setopt($session, CURLOPT_HEADER, false);
        curl_setopt($session, CURLOPT_ENCODING, 'UTF-8');
        curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($session, CURLOPT_SSL_VERIFYPEER, false);
        $response = curl_exec($session);
        curl_close($session);
        $results = json_decode($response, true);

        if (ADMIN_DEBUG == 1) {
            clog('mailgun', $results);
        }
    }

}

