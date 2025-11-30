<?php

use Common\Ext\EosClient;
use Common\Ext\TronClient;
use Common\Ext\Web3Connect;
use Common\Ext\Substrate;
use Common\Ext\GoogleLogin;
use Common\Ext\Blockgum;
use Common\Ext\paymentwall;
use Think\SuperEmail;
use Think\Cache;


const ONETHINK_ADDON_PATH = './Addons/'; // Addon Path [Optional]

if (!function_exists('array_column')) {
    function array_column(array $input, $columnKey, $indexKey = NULL)
    {
        $result = array();

        if (NULL === $indexKey) {
            if (NULL === $columnKey) {
                $result = array_values($input);
            } else {
                foreach ($input as $row) {
                    $result[] = $row[$columnKey];
                }
            }
        } else if (NULL === $columnKey) {
            foreach ($input as $row) {
                $result[$row[$indexKey]] = $row;
            }
        } else {
            foreach ($input as $row) {
                $result[$row[$indexKey]] = $row[$columnKey];
            }
        }

        return $result;
    }
}
if (!function_exists('str_contains')) {
    function str_contains(string $haystack, string $needle): bool
    {
        return '' === $needle || false !== strpos($haystack, $needle);
    }
}
function TrimTrailingZeroes($nbr)
{
    return strpos($nbr, '.') !== false ? rtrim(rtrim($nbr, '0'), '.') : $nbr;
}

function jsonReturn($status = 0, $msg = '', $data = [])
{
    return json_encode(['status' => $status, 'msg' => $msg, 'data' => $data]);
}

function isJson($str)
{
    $json = json_decode($str);
    return $json && $str != $json;
}

function arrayAsTable($data)
{
    $html = "<table>";
    foreach ($data as $key => $value) {
        $html .= "<tr><td>" . $key . "</td>";
        if (is_array($value) || is_object($value)) {
            $html .= "<td>" . arrayAsTable($value) . "     </td>";
        } else {
            $html .= "<td>" . $value . "</td></tr>";
        }
    }
    $html .= "</table>";
    return $html;
}

function getOptions($key = '')
{
    $array = C('home_options');
    if ($key == '') {
        return $array;
    }
    if (!array_key_exists($key, $array)) {
        return false;
    } else {
        if (isJson($array[$key])) {
            return json_decode($array[$key], true);
        } else {
            return $array[$key];
        }
    }
}

function actionconvert($json)
{
//$json='{"coin":{"name":"btc","value":"1.5"},"market":{"name":"btc_usd","buy":"5","sell":"6"}}';

    $res = json_decode($json);
    $verb = "";
    if ($res->coin->value > 0) {
        $verb .= $res->coin->value . ' ' . $res->coin->name . ', ';
    }
    if ($res->market->buy > 0) {
        $verb .= $res->market->buy . ' ' . $res->market->name . ' buy trades, ';
    }
    if ($res->market->sell > 0) {
        $verb .= $res->market->sell . ' ' . $res->market->name . ' sell trades ';
    }
    return rtrim($verb, ", ");

}

function addnotification($to_email, $subject, $content)
{
   return M('Notification')->add(array('to_email' => $to_email, 'subject' => $subject,'addtime'=>time(), 'content' => $content));
}
function addSms($country_code, $phone_number, $userid, $content)
{
    // Remove any non-numeric characters from country_code and phone_number
    $country_code = preg_replace('/\D/', '', $country_code);
    $phone_number = preg_replace('/\D/', '', $phone_number);

    // Check if userid is an integer
    if (!is_int($userid)) {
        return false; // Or handle the error in a way that fits your application
    }

    // Check if content is 160 characters or less
    if (strlen($content) > 160) {
        return false; // Or handle the error in a way that fits your application
    }

    // Add SMS to the database
    return M('Sms')->add(array(
        'country_code' => $country_code,
        'phone_number' => $phone_number,
        'userid' => $userid,
        'content' => $content,
        'addtime' => time(),
        'status' => 0  // Default status as unsent
    ));
}


function deposit_notify($userid, $deposit_address, $coinname, $txid, $deposited_amount, $time)
{
    $userid = $userid ?: 0;
    $user = M('User')->where(array('id' => $userid))->find();
    $to_email = $user['email'];
    $subject = "Deposit Success Alerts " . date('Y-m-d H:i', $time) . '(' . date_default_timezone_get() . ')';
    $content = "Hello,<br/>Your " . SHORT_NAME . " account has recharged " . $deposited_amount . " " . $coinname . "<br/>
		<i><small>If this activity is not your own operation, please contact us immediately. </small>";
    if(ifSMSNotify($user)){   
        $phoneNumber = $user['cellphone'];
        $countryCode = $user['cellphones'];
        $message = "Deposit of " . $deposited_amount . " " . $coinname . " confirmed. TXID: " . substr($txid, 0, 10) . "...";
        $data = array(
            'country_code' => $countryCode,
            'phone_number' => $phoneNumber,
            'userid' => $userid,
            'content' => $message,
            'addtime' => time(),  // Record the time when the entry is added
            'status' => 0  // Initial status is pending
        );
        M('Sms')->add($data);
    }
    if($to_email && $userid){
        return M('Notification')->add(array('to_email' => $to_email, 'subject' => $subject,'addtime'=>time(), 'content' => $content));
    }else{
        return false;
    }
    
}
function ifSMSNotify($user){
    $verified=0;
    if ($user['cellphone'] && $user['cellphones']) {
    $verified=1;
    }
    return $verified;
}


function sendSMS($recipient, $message) {
    // NetGSM API credentials
    $username = '---';
    $password = '---';
    $senderID = '---';  // This might be your registered phone number or a name
    //remove + sign or spaces from the phone number
    $recipient = preg_replace('/[^0-9]/', '', $recipient);
    $recipient='00'.$recipient;
    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => 'http://soap.netgsm.com.tr:8080/Sms_webservis/SMS?wsdl/',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => "<?xml version=\"1.0\"?>
            <SOAP-ENV:Envelope xmlns:SOAP-ENV=\"http://schemas.xmlsoap.org/soap/envelope/\"
                         xmlns:xsd=\"http://www.w3.org/2001/XMLSchema\"
              xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\">
                <SOAP-ENV:Body>
                    <ns3:smsGonder1NV2 xmlns:ns3=\"http://sms/\">
                        <username>{$username}</username>
                        <password>{$password}</password>
                        <header>{$senderID}</header>
                        <msg>{$message}</msg>
                        <gsm>{$recipient}</gsm>
                        <filter>0</filter>
                        <encoding>TR</encoding>
                        <appkey>xxx</appkey>
                    </ns3:smsGonder1NV2>
                </SOAP-ENV:Body>
            </SOAP-ENV:Envelope>",
        CURLOPT_HTTPHEADER => array(
            'Content-Type: text/xml'
        ),
    ));

    $response = curl_exec($curl);

    
    if (curl_errno($curl)) {
        $error_msg = curl_error($curl);
    }
    curl_close($curl);

    if (isset($error_msg)) {
        return false;//"cURL Error: {$error_msg}";
    }
    return $response;
}



//Template Based Email
function tmail($to_email, $subject, $content)
{
    if (!$to_email || !$subject || !$content) {
        $return = array('status' => 0, 'message' => "Ensure you have filled all fields, to,subject,content");
        return json_encode($return);
    }
    $logo = SITE_URL . '/Upload/public/' . C('web_logo');

    $template = file_get_contents(WEBSERVER_DIR . '/Public/email-content.html');

    $vars = array(
        '{$root}' => SITE_URL,
        '{$logo}' => $logo,
        '{$content}' => $content,
        '{$subject}' => $subject,
    );
    $body = strtr($template, $vars);
    SuperEmail::sendemail($to_email, $subject, $body);
    return true;
}

function format_num($num, $decimal = 8)
{
    return number_format($num, $decimal, '.', '');
}

//$action= e =encrypt or d =decrypt
function cryptString($string, $action = 'e')
{
    //You can change it once in lifetime. If you change it you need to change all passwords for ethereum nodes too
    $secret_key = 'G4356OJEGC';
    $secret_iv = 'I5GUCEB0IG';
    //Again Never change these keys , If you do your previous Passwords for blockchain accounts won't work.
    $output = false;
    $encrypt_method = "AES-256-CBC";
    $key = hash('sha256', $secret_key);
    $iv = substr(hash('sha256', $secret_iv), 0, 16);

    if ($action == 'e') {
        $output = base64_encode(openssl_encrypt($string, $encrypt_method, $key, 0, $iv));
    } else if ($action == 'd') {
        $output = openssl_decrypt(base64_decode($string), $encrypt_method, $key, 0, $iv);
    }

    return $output;
}

function authgame($name)
{
    if (!check($name, 'w')) {
        return 0;
    }

    if (M('VersionGame')->where(array('name' => $name, 'status' => 1))->find()) {
        return 1;
    } else {
        return 0;
    }
}

function getUrl($url)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HEADER, false);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 3);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, '');
    return curl_exec($ch);
}

function topup($cellphone = NULL, $num = NULL, $orderid = NULL)
{
    if (empty($cellphone)) {
        return NULL;
    }

    if (empty($num)) {
        return NULL;
    }

    if (empty($orderid)) {
        return NULL;
    }

    header('Content-type:text/html;charset=utf-8');
    $appkey = C('topup_appkey');
    $openid = C('topup_openid');
    $recharge = new \Common\Ext\Recharge($appkey, $openid);
    $telRechargeRes = $recharge->telcz($cellphone, $num, $orderid);

    if ($telRechargeRes['error_code'] == '0') {
        return 1;
    } else {
        return NULL;
    }
}

function mlog($text)
{
    if (M_DEBUG == 1) {
        $text = addtime(time()) . ' ' . $text . "\n";
        $filname = date('d-m-Y') . "_mlog.log";
        file_put_contents(DATABASE_PATH . '/Log/' . $filname, $text, FILE_APPEND);
    }
}

function clean($string)
{
    $string = str_replace(' ', '-', $string); // Replaces all spaces with hyphens.

    return preg_replace('/[^A-Za-z\d\-]/', '', $string); // Removes special chars.
}

function clog($filename, $text)
{

    if (is_array($text)) {
        $text = json_encode($text, true);
    }
    if (is_object($text)) {
        $text = json_encode($text);
    }
    $new_text = addtime(time()) . ' ' . $text . "\n";
    $new_filename = date('d-m-Y') . "_" . clean($filename);
    //	$back_Trace = json_encode(debug_backtrace()).'---';
    //    $text=$back_Trace.$text;
    file_put_contents(DATABASE_PATH . '/Log/' . $new_filename . '.log', $new_text, FILE_APPEND);


}

function dblog($filename, $text)
{

    if (is_array($text)) {
        $text = json_encode($text, true);
    }
    if (is_object($text)) {
        $text = json_encode($text);
    }
    $new_text = addtime(time()) . ' ' . $text . "\n";
    $new_filename = date('d-m-Y') . "_" . $filename;
//	$back_Trace = json_encode(debug_backtrace()).'---';
    //    $text=$back_Trace.$text;

    $log = ['title' => $filename, 'code' => $text, 'addtime' => time()];
    M('Debug')->add($log);

}

function aaa($item, $pattern, $fun)
{
    $pattern = str_replace('###', $item['id'], $pattern);
    $view = new \Think\View();
    $view->assign($item);
    $pattern = $view->fetch('', $pattern);
    return $fun($pattern);
}

function userid($username = NULL, $type = 'username', $auto = 0)
{
    if ($auto) {//robot
        $userid = 0;
        return $userid;
    }
    if ($username && $type) {
        $userid = (APP_DEBUG ? NULL : S('userid' . $username . $type));

        if (!$userid) {
            $userid = M('User')->where(array($type => $username))->getField('id');
            S('userid' . $username . $type, $userid);
        }
    } else {
        $userid = session('userId');
    }

    return $userid ?: NULL;
}

function adminname($id = 0)
{

    if ($id) {
        $adminname = M('Admin')->where(array('id' => (int)$id))->getField('username');

    } else {
        $adminname = 'NO ADMIN';
    }

    return $adminname;
}

function usertype()
{

    $userid = userid();
    if ((int)$userid > 0) {

        $usertype = (APP_DEBUG ? NULL : S('usertype' . $userid));

        if (!$usertype) {

            $status = M('User')->where(array('id' => $userid))->getField('usertype');
            $usertype = $status;
            S('usertype' . $userid, $status);

        }
    } else {
        $usertype = 0;
    }
    return $usertype;

}

function kyced($userid = NULL)
{
    if (ENFORCE_KYC != 1) {
        return true;
    }
    if (!$userid) {
        $userid = userid();
    }

    return extracted($userid);
}

/**
 * @param $userid
 * @return int
 */
function extracted($userid): int
{
	
    if ((int)$userid > 0) {

        $kyced = (APP_DEBUG ? NULL : session('kyced_' . $userid));
		
        if (!$kyced) {

            $status = M('User')->where(array('id' => $userid))->getField('idcardauth');
            $kyced = $status;
            session('kyced_' . $userid, $status);
        }
    } else {
        $kyced = null;
    }
    if ($kyced == 1) {
        return 1;
    } else {
        return 0;
    }
}

function check_kyc()
{
    $userid = userid();
    return extracted($userid);
}

function username($id = NULL, $type = 'id')
{

    if ($id && $type) {

        $username = (APP_DEBUG ? NULL : S('username' . $id . $type));

        if (!$username) {

            $username = M('User')->where(array('id' => $id))->getField('username');

            S('username' . $id . $type, $username);
        }
    } else {
        $username = session('userName');
    }

    return $username ?: NULL;
}

function fullname($id = NULL, $type = 'id')
{

    if ($id && $type) {

        $fullname = (APP_DEBUG ? NULL : S('fullname' . $id . $type));

        if (!$fullname) {

            $info = M('User')->where(array('id' => $id))->field('truename,firstname,lastname')->find();
            $fullname = $info['firstname'] . ' ' . $info['lastname'];
            S('fullname' . $id . $type, $fullname);
        }
    } else {
        $fullname = session('fullname');
    }

    return $fullname ?: NULL;
}

function getEmail($id = NULL, $type = 'id')
{

    if ($id && $type) {

        $email = (APP_DEBUG ? NULL : S('email' . $id . $type));

        if (!$email) {

            $email = M('User')->where(array('id' => $id))->getField('email');

            S('email' . $id . $type, $email);
        }
    } else {
        $email = session('email');
    }

    return $email ?: NULL;
}


function op_t($text, $addslanshes = false)
{
    $text = nl2br($text);
    $text = real_strip_tags($text);

    if ($addslanshes) {
        $text = addslashes($text);
    }

    return trim($text);
}

function text($text, $addslanshes = false)
{
    return op_t($text, $addslanshes);
}

function html($text)
{
    return op_h($text);
}

function op_h($text, $type = 'html')
{
    $link_tags = '<a>';
    $image_tags = '<img>';
    $font_tags = '<i><b><u><s><em><strong><font><big><small><sup><sub><bdo><h1><h2><h3><h4><h5><h6>';
    $base_tags = $font_tags . '<p><br><hr><a><img><map><area><pre><code><q><blockquote><acronym><cite><ins><del><center><strike>';
    $form_tags = $base_tags . '<form><input><textarea><button><select><optgroup><option><label><fieldset><legend>';
    $html_tags = $base_tags . '<ul><ol><li><dl><dd><dt><table><caption><td><th><tr><thead><tbody><tfoot><col><colgroup><div><span><object><embed><param>';
    $all_tags = $form_tags . $html_tags . '<!DOCTYPE><meta><html><head><title><body><base><basefont><script><noscript><applet><object><param><style><frame><frameset><noframes><iframe>';
    $text = real_strip_tags($text, $type . '_tags');

    if ($type != 'all') {
        while (preg_match('/(<[^><]+)(ondblclick|onclick|onload|onerror|unload|onmouseover|onmouseup|onmouseout|onmousedown|onkeydown|onkeypress|onkeyup|onblur|onchange|onfocus|action|background[^-]|codebase|dynsrc|lowsrc)([^><]*)/i', $text, $mat)) {
            $text = str_ireplace($mat[0], $mat[1] . $mat[3], $text);
        }

        while (preg_match('/(<[^><]+)(window\\.|javascript:|js:|about:|file:|document\\.|vbs:|cookie)([^><]*)/i', $text, $mat)) {
            $text = str_ireplace($mat[0], $mat[1] . $mat[3], $text);
        }
    }

    return $text;
}

function real_strip_tags($str, $allowable_tags = '')
{
    return strip_tags($str, $allowable_tags);
}

function clean_cache()
{
    $dirname = RUNTIME_PATH;
    $dirs = array($dirname);

    foreach ($dirs as $value) {
        rmdirr($value);
    }

    @(mkdir($dirname, 511, true));
}

function getSubByKey($pArray, $pKey = '', $pCondition = '')
{
    $result = array();

    if (is_array($pArray)) {
        foreach ($pArray as $temp_array) {
            if (is_object($temp_array)) {
                $temp_array = (array)$temp_array;
            }

            if ((('' != $pCondition) && ($temp_array[$pCondition[0]] == $pCondition[1])) || ('' == $pCondition)) {
                $result[] = ('' == ($pKey ? $temp_array : isset($temp_array[$pKey])) ? $temp_array[$pKey] : '');
            }
        }

        return $result;
    } else {
        return false;
    }
}

function debug($value, $type = 'DEBUG', $verbose = false, $encoding = 'UTF-8')
{
    if (ADMIN_DEBUG || 1) {
        if (!IS_CLI) {
            Common\Ext\FirePHP::getInstance(true)->log($value, $type);
        }
    }
}

function lognow($message = "NO MESSAGE PROVIDED", $type = "WARN")
{
    \Think\Log::record($message, $type);
}

function Web3Connect($coin, $network)
{
    if ($coin == 'eth') {
        $api_key = ETHERSCAN_KEY;
    } else {
        $api_key = BSCSCAN_KEY;
    }
    return new Web3Connect($coin, $network, $api_key);
}

function Binance()
{
    $keySelection = rand(1, 2);
    if ($keySelection == 1) {
        return new \Common\Ext\Binance(BINANCE_API_KEY_1, BINANCE_API_SECRET_1, BINANCE_TESTNET);
    } else {
        return new \Common\Ext\Binance(BINANCE_API_KEY_2, BINANCE_API_SECRET_2, BINANCE_TESTNET);
    }
}

function XrpClient($host, $port, $address, $secret)
{
    $secret = cryptString($secret, 'd');
    return new \Common\Ext\XrpClient($host, $port, $address, $secret);
}

function TronClient($config = array())
{
    $host = null;
    $port = null;
    $address = null;
    $secret = null;
    $secret = cryptString($secret, 'd');
    return new TronClient($network = "main");
}

function MongoClient($config = array())
{
    $config['host'] = C('MON_HOST'); // MongoDB Server IP or host name
    $config['port'] = C('MON_PORT'); // Port for accessing MongoDB - Default Installation port is 27017
    $config['username'] = C('MON_USER'); //  MongoDB access username
    $config['password'] = C('MON_PWD'); // MongoDB access password
    $config['db'] = C('MON_NAME'); // MonogDB Database instance name
    return new \Common\Ext\MongoClient($config);
}

function EosClient($host, $port)
{
    return new EosClient($host, $port);
}


function ESmart($esmart_config)
{
    return new \Common\Ext\ESmart($esmart_config);
}

function Substrate($substrate_config)
{
    return new Substrate($substrate_config);
}

function CoinClient($username, $password, $ip, $port, $timeout = 3, $headers = array(), $suppress_errors = false)
{
    return new \Common\Ext\CoinClient($username, $password, $ip, $port, $timeout, $headers, $suppress_errors);
}

function WavesClient($username, $password, $ip, $port, $decimal, $timeout = 3, $headers = array(), $suppress_errors = false)
{
    return new \Common\Ext\WavesPlatform($ip, $port, $password, $username, $decimal);
}

function BlockIO($username, $password, $ip, $port, $timeout = 3, $headers = array(), $suppress_errors = false)
{
    $apiKey = $username;
    $pin = $password;
    $version = 2; // the API version
    return new \Common\Ext\BlockIo($apiKey, $pin, $version);
}

function CryptoNote($ip, $port, $type = 2)
{
    return new \Common\Ext\CryptoNote($ip, $port);
}

function YoUganda()
{
    $mode = YO_Uganda['mode'];
    if ($mode == 'sandbox') {
        $username = YO_Uganda['sandbox_USER'];
        $password = YO_Uganda['sandbox_PASS'];
    } else {
        $username = YO_Uganda['API_USER'];
        $password = YO_Uganda['API_PASS'];
    }

    return new \Common\Ext\YoUganda($username, $password, $mode);
}

function XeConvert()
{
    $array_config = array('appid' => 'turndealer59186832', 'appkey' => 'estbdlgtp7n9es47hll6o5tucm');
    return new \Common\Ext\Xe($array_config);
}

function paymentwall($userid, $email, $mycz)
{
    //require_once('../Ext/Paymentwall.class.php');
    Paymentwall_Config::getInstance()->set(array(
        'api_type' => Paymentwall_Config::API_VC,
        //'public_key' => 't_19e54454e95a03804c1bd4ff000624',
        //'private_key' => 't_7538e7e80881ae38006a3edca83978'
        'public_key' => 'X',
        'private_key' => 'X'
    ));
    $widget = new Paymentwall_Widget(
        "$userid",
        'p10_1',
        array(),
        array(
            'email' => $email,
            'history[registration_date]' => 'registered_date_of_user',
            'ps' => 'all' // Replace the value of 'ps' with specific payment system short code for Widget API uni
        )
    );
    return $widget->getUrl();
}

function IPNpaymentwall()
{
    require_once('Paymentwall.class.php');
    Paymentwall_Config::getInstance()->set(array(
        'api_type' => Paymentwall_Config::API_VC,
        'public_key' => '7x',
        'private_key' => 'x'
    ));
    $pingback = new Paymentwall_Pingback($_GET, $_SERVER['REMOTE_ADDR']);
    $data['status'] = 0;
    $data['message'] = 'Nothing Yet';
    $data['ipn_resp'] = $pingback;
    if ($pingback->validate()) {
        $virtualCurrency = $pingback->getVirtualCurrencyAmount();
        if ($pingback->isDeliverable()) {
            // deliver the virtual currency
        } else if ($pingback->isCancelable()) {
            // withdraw the virtual currency
        } else if ($pingback->isUnderReview()) {
            // set "pending" status to order
        }
        $data['status'] = 1;
        $data['message'] = 'OK';
        // Paymentwall expects response to be OK, otherwise the pingback will be resent
    } else {
        //echo $pingback->getErrorSummary();
        $data['status'] = 0;
        $data['message'] = $pingback->getErrorSummary();
    }
    return json_encode($data);
}

function CoinPay($username, $password, $ip, $port, $timeout = 3, $headers = array(), $suppress_errors = false)
{

    $version = 2; // the API version
    return new \Common\Ext\CoinpaymentsAPI($password, $username, 'json');
}

function CryptoApis($inputConfig)
{
    $config['api_key'] = $inputConfig['api_key'];
    if ($inputConfig['network'] == 1) {
        $config['network'] = 'mainnet';
    } else {
        $config['network'] = 'testnet';
    }

    return new \Common\Ext\CryptoApis($config);
}

function createQRcode($save_path, $qr_data = 'PHP QR Code :)', $qr_level = 'L', $qr_size = 4, $save_prefix = 'qrcode')
{
    if (!isset($save_path)) {
        return '';
    }

    $PNG_TEMP_DIR = &$save_path;
    vendor('PHPQRcode.class#phpqrcode');

    if (!file_exists($PNG_TEMP_DIR)) {
        mkdir($PNG_TEMP_DIR);
    }

    $filename = $PNG_TEMP_DIR . 'test.png';
    $errorCorrectionLevel = 'L';

    if (isset($qr_level) && in_array($qr_level, array('L', 'M', 'Q', 'H'))) {
        $errorCorrectionLevel = &$qr_level;
    }

    $matrixPointSize = 4;

    if (isset($qr_size)) {
        $mpo_size = min(max((int)$qr_size, 1), 10);
        $matrixPointSize = &$mpo_size;//&min(max((int)$qr_size, 1), 10);
    }

    if (isset($qr_data)) {
        if (trim($qr_data) == '') {
            exit('data cannot be empty!');
        }

        $filename = $PNG_TEMP_DIR . $save_prefix . md5($qr_data . '|' . $errorCorrectionLevel . '|' . $matrixPointSize) . '.png';
        QRcode::png($qr_data, $filename, $errorCorrectionLevel, $matrixPointSize, 2, true);
    } else {
        QRcode::png('PHP QR Code :)', $filename, $errorCorrectionLevel, $matrixPointSize, 2, true);
    }

    if (file_exists($PNG_TEMP_DIR . basename($filename))) {
        return basename($filename);
    } else {
        return false;
    }
}

function NumToStr($num)
{
    if (!$num) {
        return 0;
    }

    if ($num == 0) {
        return 0;
    }
    if ($num < 0) {
        return $num * 1;
    }

    $num = format_num($num, 8);
    return sub_num($num);
}

function Num($num)
{
    if (!$num) {
        return 0;
    }

    if ($num == 0) {
        return 0;
    }

    $num = round($num, 8);
    return sub_num($num);
}

/**
 * @param float $num
 * @return string
 */
function sub_num(float $num): string
{
    $min = 0.0001;

    if ($num <= $min) {
        $times = 0;

        while ($num <= $min) {
            $num *= 10;
            $times++;

            if (10 < $times) {
                break;
            }
        }

        $arr = explode('.', $num);
        $arr[1] = str_repeat('0', $times) . $arr[1];
        return $arr[0] . '.' . $arr[1];
    }

    return ($num * 1) . '';
}

function check_verify($code, $id = 1, $recap = 0)
{

    if (RECAPTCHA == 1 && $recap == 1) {
        $secret = RECAPTCHA_SECRET;
        $url = "https://www.google.com/recaptcha/api/siteverify?secret=$secret&response=$code";
        $ch = curl_init();
        // Disable SSL verification
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        // Will return the response, if false it will print the response
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        // Set the url
        curl_setopt($ch, CURLOPT_URL, $url);
        // Execute
        $result = json_decode(curl_exec($ch));
        return $result->success;
    } else {
        $code = strtoupper($code);
        $verify = new \Think\Verify();
        return $verify->check($code, $id);
    }

}

function check_recaptcha($code, $id = 1)
{


}


function get_city_ip($ip = NULL)
{
    if (empty($ip)) {
        $ip = get_client_ip();
    }
    /*
        $Ip = new Org\Net\IpLocation();
        $area = $Ip->getlocation($ip);
        $str = $area['country'] . $area['area'];
        $str = mb_convert_encoding($str, 'UTF-8', 'GBK');

        if (($ip == '127.0.0.1') || ($str == false) || ($str == 'IANA')) {
            $str = 'LocalIP';
        }
    */
//@todo implement own country IP2City
    return $ip;
}

function send_post($url, $post_data)
{
    $postdata = http_build_query($post_data);
    $options = array(
        'http' => array('method' => 'POST', 'header' => 'Content-type:application/x-www-form-urlencoded', 'content' => $postdata, 'timeout' => 15 * 60)
    );
    $context = stream_context_create($options);
    return file_get_contents($url, false, $context);
}

function request_by_curl($remote_server, $post_string)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $remote_server);
    curl_setopt($ch, CURLOPT_POSTFIELDS, 'mypost=' . $post_string);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERAGENT, '');
    $data = curl_exec($ch);
    curl_close($ch);
    return $data;
}

function tradeno()
{
    return substr(str_shuffle('ABCDEFGHIJKLMNPQRSTUVWXYZ'), 0, 2) . substr(str_shuffle(str_repeat('123456789', 4)), 0, 6);
}

function tradenoa()
{
    return substr(str_shuffle('ABCDEFGHIJKLMNPQRSTUVWXYZ'), 0, 6);
}

function tradenob()
{
    return substr(str_shuffle(str_repeat('123456789', 4)), 0, 2);
}

function cardGenSecret()
{
    return str_shuffle(substr(str_shuffle('ABCDEFGHIJKLMNPQRSTUVWXYZ'), 0, 10) . substr(str_shuffle(str_repeat('123456789', 4)), 0, 10));
}

function cardGenPublic($userid)
{
    $rev_string = $userid . time();
    return $rev_string . substr(str_shuffle(str_repeat('1234567890', 6)), 0, 2);
}

function get_user($id, $type = NULL, $field = 'id')
{
    $key = md5('get_user' . $id . $type . $field);
    $data = S($key);

    if (!$data) {
        $data = M('User')->where(array($field => $id))->find();
        S($key, $data);
    }

    if ($type) {
        $rs = $data[$type];
    } else {
        $rs = $data;
    }

    return $rs;
}

function ismobile()
{
    if (isset($_SERVER['HTTP_X_WAP_PROFILE'])) {
        return true;
    }

    if (isset($_SERVER['HTTP_CLIENT']) && ('PhoneClient' == $_SERVER['HTTP_CLIENT'])) {
        return true;
    }

    if (isset($_SERVER['HTTP_VIA'])) {
        return (bool)stristr($_SERVER['HTTP_VIA'], 'wap');
    }

    if (isset($_SERVER['HTTP_USER_AGENT'])) {
        $clientkeywords = array('nokia', 'sony', 'ericsson', 'mot', 'samsung', 'htc', 'sgh', 'lg', 'sharp', 'sie-', 'philips', 'panasonic', 'alcatel', 'lenovo', 'iphone', 'ipod', 'blackberry', 'meizu', 'android', 'netfront', 'symbian', 'ucweb', 'windowsce', 'palm', 'operamini', 'operamobi', 'openwave', 'nexusone', 'cldc', 'midp', 'wap', 'mobile');

        if (preg_match('/(' . implode('|', $clientkeywords) . ')/i', strtolower($_SERVER['HTTP_USER_AGENT']))) {
            return true;
        }
    }

    if (isset($_SERVER['HTTP_ACCEPT'])) {
        if ((strpos($_SERVER['HTTP_ACCEPT'], 'vnd.wap.wml') !== false) && ((strpos($_SERVER['HTTP_ACCEPT'], 'text/html') === false) || (strpos($_SERVER['HTTP_ACCEPT'], 'vnd.wap.wml') < strpos($_SERVER['HTTP_ACCEPT'], 'text/html')))) {
            return true;
        }
    }

    return false;
}

function send_cellphone($cellphone, $content)
{

    $resp= sendSms($cellphone, $content);
    return $resp;
    if (MOBILE_CODE == 1) {
        return 1;
    }

    session_start();
    $statusStr = array(
        0 => 'Delivered',
        1 => 'Unknown',
        2 => 'Absent Subscriber - Temporary',
        3 => 'Absent Subscriber - Permanent',
        4 => 'Call barred by user',
        5 => 'Portability Error',
        6 => 'Anti-Spam Rejection',
        7 => 'Handset Busy',
        8 => 'Network Error',
        9 => 'Illegal Number',
        10 => 'Invalid Message',
        11 => 'Unroutable',
        12 => 'Destination Un-Reachable',
        13 => 'Subscriber Age Restriction',
        14 => 'Number Blocked by Carrier',
        15 => 'Pre-Paid - Insufficent funds',
        99 => 'General Error',
    );
    $content = "[" . SHORT_NAME . "]" . $content;
    // $url = "https://rest.nexmo.com/sms/json?api_key=" . C('cellphone_user') . "&api_secret=" . C('cellphone_pwd') . "&from=".SHORT_NAME."&to=$cellphone&text=" . urlencode($content);
    //"from=x&text=ABG3778&to=x&api_key=x&api_secret=x"
    $url = "https://rest.nexmo.com/sms/json";
    $postfields = "api_key=" . C('cellphone_user') . "&api_secret=" . C('cellphone_pwd') . "&from=" . C('cellphone_url') . "&to=$cellphone&text=" . urlencode($content);

//  Initiate curl
    $ch = curl_init();
// Disable SSL verification
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    // Will return the response, if false it will print the response
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    // Set the url
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postfields);
    $headers = array();
    $headers[] = 'Content-Type: application/x-www-form-urlencoded';
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    // Execute
    $result = curl_exec($ch);
    // Closing
    curl_close($ch);

    // Will dump a beauty json :3
    $array_result = json_decode($result, true);

    $status = $array_result['messages'][0]['status'];

    if ($status != 0) {
        $data['info'] = $array_result['messages'][0]['error-text'];
        return 0;
    } else {
        return 1;
    }
}

function addtime($time = NULL, $type = NULL)
{
    if (empty($time)) {
        return '---';
    }

    if (($time < 2545545) || (1893430861 < $time)) {
        return '---';
    }

    if (empty($type)) {
        $type = 'Y-m-d H:i:s';
    }

    return date($type, $time);
}

function check($data, $rule = null, $ext = null)
{
    $data = trim(str_replace(PHP_EOL, '', $data));

    if (empty($data)) {
        return false;
    }

    $validate = [
        'require'  => '/.+/',
        'url'      => '/^https?:\/\/(?:[A-Za-z0-9-]+\.)+[A-Za-z]{2,4}(?:[\/\?#][\/=\?%&~`@\[\]\':+!#\w]*)?$/',
        'currency' => '/^\d+(\.\d+)?$/',
        'number'   => '/^\d+$/',
        'zip'      => '/^\d{6}$/',
        'usd'      => '/^(([1-9]{1}\d*)|([0]{1}))(\\.\d{1,2})?$/',
        'integer'  => '/^[+]?\d+$/',
        'double'   => '/^[+]?\d+(\.\d+)?$/',
        'english'  => '/^[A-Za-z ]+$/',
        'idcard'   => '/^[A-Za-z0-9- ]+$/',
        'truename' => '/^[\p{L} .\'-]+$/u',
        'username' => '/^[a-zA-Z][0-9a-zA-Z_]{4,30}$/',
        'email'    => '/^\w+([-+.]\w+)*@\w+([-.]\w+)*\.\w+([-.]\w+)*$/',
        'cellphone'=> '/^[0-9]{6,15}+$/',
        'mostregex'=> '/^[a-zA-Z0-9_ @#$%^&*()!,.\-+|=]{1,200}$/',
        'general'  => '/^[a-zA-Z0-9\\\/\-:\.#()\[\], ]{0,200}$/',
        'password' => '/^[a-zA-Z0-9_@#$%^&*()!,.\-+|=]{5,20}$/',
        'xnb'      => '/^[a-zA-Z]$/',
        'coin'     => '/^[a-z][a-z0-9]{0,7}$/',
        'market'   => '/^[a-zA-Z_0-9]{3,18}$/'
    ];

    $ruleLower = strtolower($rule);
    if (array_key_exists($ruleLower, $validate)) {
        return preg_match($validate[$ruleLower], $data);
    }

    $patterns = [
        'a' => '0-9a-zA-Z@#$%^&*()!,.\-+|=',
        'c' => '0-9a-zA-Z@#$%^&*()!,.\-+|=',
        'd' => '0-9',
        'w' => 'a-zA-Z',
        'n' => 'a-z',
        't' => '@#$%^&*()-+=',
        '_' => '_'
    ];

    $pattern = '/^[';
    $ruleArray = str_split($ruleLower);

    foreach ($ruleArray as $char) {
        if (isset($patterns[$char])) {
            $pattern .= $patterns[$char];
        }
    }

    if ($ext) {
        $pattern .= $ext;
    }

    $pattern .= ']+$/u';

    return preg_match($pattern, $data);
}


function check_arr($rs,$strict=0)
{
    if ($strict) {
        return strict_check_arr($rs);
    }
    if (!is_array($rs)) {
        return false;
    }
    return true;
}

function strict_check_arr($rs)
{
    if (!is_array($rs)) {
        return false;
    }
    foreach ($rs as $element) {
        if (!$element) {
            return false;
        }
    }
    return true;
}

function maxArrayKey($arr, $key)
{
    $a = 0;

    foreach ($arr as $k => $v) {
        $a = max($v[$key], $a);
    }

    return $a;
}

function arr2str($arr, $sep = ',')
{
    return implode($sep, $arr);
}

function str2arr($str, $sep = ',')
{
    return explode($sep, $str);
}

function url($link = '', $param = '', $default = '')
{
    return $default ?: U($link, $param);
}

function rmdirr($dirname)
{
    if (!file_exists($dirname)) {
        return false;
    }

    if (is_file($dirname) || is_link($dirname)) {
        return unlink($dirname);
    }

    $dir = dir($dirname);

    if ($dir) {
        while (false !== $entry = $dir->read()) {
            if (($entry == '.') || ($entry == '..')) {
                continue;
            }

            rmdirr($dirname . DIRECTORY_SEPARATOR . $entry);
        }
    }

    $dir->close();
    return rmdir($dirname);
}

function list_to_tree($list, $pk = 'id', $pid = 'pid', $child = '_child', $root = 0)
{
    $tree = array();

    if (is_array($list)) {
        $refer = array();

        foreach ($list as $key => $data) {
            $refer[$data[$pk]] = &$list[$key];
        }

        foreach ($list as $key => $data) {
            $parentId = $data[$pid];

            if ($root == $parentId) {
                $tree[] = &$list[$key];
            } else if (isset($refer[$parentId])) {
                $parent = &$refer[$parentId];
                $parent[$child][] = &$list[$key];
            }
        }
    }

    return $tree;
}

function tree_to_list($tree, $child = '_child', $order = 'id', &$list = array())
{
    if (is_array($tree)) {
        $refer = array();

        foreach ($tree as $key => $value) {
            $reffer = $value;

            if (isset($reffer[$child])) {
                unset($reffer[$child]);
                tree_to_list($value[$child], $child, $order, $list);
            }

            $list[] = $reffer;
        }

        $list = list_sort_by($list, $order, $sortby = 'asc');
    }

    return $list;
}

function list_sort_by($list, $field, $sortby = 'asc')
{
    if (is_array($list)) {
        $refer = $resultSet = array();

        foreach ($list as $i => $data) {
            $refer[$i] = &$data[$field];
        }

        switch ($sortby) {
            case 'asc':
                asort($refer);
                break;

            case 'desc':
                arsort($refer);
                break;

            case 'nat':
                natcasesort($refer);
        }

        foreach ($refer as $key => $val) {
            $resultSet[] = &$list[$key];
        }

        return $resultSet;
    }

    return false;
}

function list_search($list, $condition)
{
    if (is_string($condition)) {
        parse_str($condition, $condition);
    }

    $resultSet = array();

    foreach ($list as $key => $data) {
        $find = false;

        foreach ($condition as $field => $value) {
            if (isset($data[$field])) {
                if (0 === strpos($value, '/')) {
                    $find = preg_match($value, $data[$field]);
                } else if ($data[$field] == $value) {
                    $find = true;
                }
            }
        }

        if ($find) {
            $resultSet[] = &$list[$key];
        }
    }

    return $resultSet;
}

function d_f($name, $value, $path = DATA_PATH)
{
    if (APP_MODE == 'sae') {
        return false;
    }

    static $_cache = array();
    $filename = $path . $name . '.php';

    if ('' !== $value) {
        if (is_null($value)) {
        } else {
            $dir = dirname($filename);

            if (!is_dir($dir)) {
                mkdir($dir, 493, true);
            }

            $_cache[$name] = $value;
            $content = strip_whitespace('<?php' . "\t" . 'return ' . var_export($value, true) . ';?>') . PHP_EOL;
            return file_put_contents($filename, $content, FILE_APPEND);
        }
    }

    if (isset($_cache[$name])) {
        return $_cache[$name];
    }

    if (is_file($filename)) {
        $value = include $filename;
        $_cache[$name] = $value;
    } else {
        $value = false;
    }

    return $value;
}

function DownloadFile($fileName)
{
    ob_end_clean();
    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
    header('Content-Description: File Transfer');
    header('Content-Type: application/octet-stream');
    header('Content-Length: ' . filesize($fileName));
    header('Content-Disposition: attachment; filename=' . basename($fileName));
    readfile($fileName);
}

function download_file($file, $o_name = '')
{
    if (is_file($file)) {
        $length = filesize($file);
        $type = mime_content_type($file);
        $showname = ltrim(strrchr($file, '/'), '/');

        if ($o_name) {
            $showname = $o_name;
        }

        header('Content-Description: File Transfer');
        header('Content-type: ' . $type);
        header('Content-Length:' . $length);

        if (preg_match('/MSIE/', $_SERVER['HTTP_USER_AGENT'])) {
            header('Content-Disposition: attachment; filename="' . rawurlencode($showname) . '"');
        } else {
            header('Content-Disposition: attachment; filename="' . $showname . '"');
        }

        readfile($file);
        exit();
    } else {
        exit('file does not exist');
    }
}

function wb_substr($str, $len = 140, $dots = 1, $ext = '')
{
    $str = htmlspecialchars_decode(strip_tags(htmlspecialchars($str)));
    $strlenth = 0;
    $output = '';

    preg_match_all("/[\x01-\x7f]|[\xc2-\xdf][\x80-\xbf]|[\xe0-\xef][\x80-\xbf]{2}|[\xf0-\xff][\x80-\xbf]{3}/", $str, $match);


    foreach ($match[0] as $v) {
        preg_match('/[' . "\xe0" . '-' . "\xef" . '][' . "\x80" . '-' . "\xbf" . ']{2}/', $v, $matchs);

        if (!empty($matchs[0])) {
            $strlenth += 1;
        } else if (is_numeric($v)) {
            $strlenth += 0.54500000000000004;
        } else {
            $strlenth += 0.47499999999999998;
        }

        if ($len < $strlenth) {
            $output .= $ext;
            break;
        }

        $output .= $v;
    }

    if (($len < $strlenth) && $dots) {
        $output .= '...';
    }

    return $output;
}

function msubstr($str, $start = 0, $length, $charset = 'utf-8', $suffix = true)
{
    if (function_exists('mb_substr')) {
        $slice = mb_substr($str, $start, $length, $charset);
    } else if (function_exists('iconv_substr')) {
        $slice = iconv_substr($str, $start, $length, $charset);

        if (false === $slice) {
            $slice = '';
        }
    } else {
        $re['utf-8'] = '/[' . "\x1" . '-]|[' . "\xc2" . '-' . "\xdf" . '][' . "\x80" . '-' . "\xbf" . ']|[' . "\xe0" . '-' . "\xef" . '][' . "\x80" . '-' . "\xbf" . ']{2}|[' . "\xf0" . '-' . "\xff" . '][' . "\x80" . '-' . "\xbf" . ']{3}/';
        $re['gb2312'] = '/[' . "\x1" . '-]|[' . "\xb0" . '-' . "\xf7" . '][' . "\xa0" . '-' . "\xfe" . ']/';
        $re['gbk'] = '/[' . "\x1" . '-]|[' . "\x81" . '-' . "\xfe" . '][@-' . "\xfe" . ']/';
        $re['big5'] = '/[' . "\x1" . '-]|[' . "\x81" . '-' . "\xfe" . ']([@-~]|' . "\xa1" . '-' . "\xfe" . '])/';
        preg_match_all($re[$charset], $str, $match);
        $slice = join('', array_slice($match[0], $start, $length));
    }

    return $suffix ? $slice . '...' : $slice;
}

function formatString($string) {
    // Convert the string to lowercase first
    $lowercaseString = strtolower($string);
    // Then capitalize the first letter of the string
    $formattedString = ucfirst($lowercaseString);
    return $formattedString;
}

function shortFirstLetter($str, $start = 0, $length = 1, $charset = 'utf-8', $suffix = true)
{
    if (function_exists('mb_substr')) {
        $slice = mb_substr($str, $start, $length, $charset);
    } else if (function_exists('iconv_substr')) {
        $slice = iconv_substr($str, $start, $length, $charset);

        if (false === $slice) {
            $slice = '';
        }
    } else {
        $re['utf-8'] = '/[' . "\x1" . '-]|[' . "\xc2" . '-' . "\xdf" . '][' . "\x80" . '-' . "\xbf" . ']|[' . "\xe0" . '-' . "\xef" . '][' . "\x80" . '-' . "\xbf" . ']{2}|[' . "\xf0" . '-' . "\xff" . '][' . "\x80" . '-' . "\xbf" . ']{3}/';
        $re['gb2312'] = '/[' . "\x1" . '-]|[' . "\xb0" . '-' . "\xf7" . '][' . "\xa0" . '-' . "\xfe" . ']/';
        $re['gbk'] = '/[' . "\x1" . '-]|[' . "\x81" . '-' . "\xfe" . '][@-' . "\xfe" . ']/';
        $re['big5'] = '/[' . "\x1" . '-]|[' . "\x81" . '-' . "\xfe" . ']([@-~]|' . "\xa1" . '-' . "\xfe" . '])/';
        preg_match_all($re[$charset], $str, $match);
        $slice = join('', array_slice($match[0], $start, $length));
    }

    return ucfirst($slice);
}

function highlight_map($str, $keyword)
{
    return str_replace($keyword, '<em class=\'keywords\'>' . $keyword . '</em>', $str);
}

function file_iconv($name)
{
    return iconv('GB2312', 'UTF-8', $name);
}

function del_file($file)
{
    $file = file_iconv($file);
    @(unlink($file));
}

function status_text($model, $key)
{
    $text = [];
    if ($model == 'Nav') {
        $text = array('invalid', 'effective');
    }

    return $text[$key];
}

function user_auth_sign($user)
{
    ksort($user);
    $code = http_build_query($user);
    return sha1($code);
}

function get_link($link_id = NULL, $field = 'url')
{
    $link = '';

    if (empty($link_id)) {
        return $link;
    }

    $link = D('Url')->getById($link_id);

    if (empty($field)) {
        return $link;
    } else {
        return $link[$field];
    }
}

function get_cover($cover_id, $field = NULL)
{
    if (empty($cover_id)) {
        return false;
    }

    $picture = D('Picture')->where(array('status' => 1))->getById($cover_id);

    if ($field == 'path') {
        if (!empty($picture['url'])) {
            $picture['path'] = $picture['url'];
        } else {
            $picture['path'] = __ROOT__ . $picture['path'];
        }
    }

    return empty($field) ? $picture : $picture[$field];
}

function get_admin_name()
{
    $user = session(C('USER_AUTH_KEY'));
    return $user['admin_name'];
}

function is_login()
{
    $user = session(C('USER_AUTH_KEY'));

    if (empty($user)) {
        return 0;
    } else {
        return session(C('USER_AUTH_SIGN_KEY')) == user_auth_sign($user) ? $user['admin_id'] : 0;
    }
}

function is_administrator($uid = NULL)
{
    $uid = (is_null($uid) ? is_login() : $uid);
    return $uid && (intval($uid) === C('USER_ADMINISTRATOR'));
}

function show_tree($tree, $template)
{
    $view = new View();
    $view->assign('tree', $tree);
    return $view->fetch($template);
}

function int_to_string(&$data, $map = array(
    'status' => array(1 => 'Normal', -1 => 'Delete', 0 => 'Disable', 2 => 'Unreviewed', 3 => 'Draft')
))
{
    if (($data === false) || ($data === NULL)) {
        return $data;
    }

    $data = (array)$data;

    foreach ($data as $key => $row) {
        foreach ($map as $col => $pair) {
            if (isset($row[$col]) && isset($pair[$row[$col]])) {
                $data[$key][$col . '_text'] = $pair[$row[$col]];
            }
        }
    }

    return $data;
}

function hook($hook, $params = array())
{
    return \Think\Hook::listen($hook, $params);
}

function get_addon_class($name)
{
    $type = (strpos($name, '_') !== false ? 'lower' : 'upper');

    if ('upper' == $type) {
        $dir = \Think\Loader::parseName(lcfirst($name));
        $name = ucfirst($name);
    } else {
        $dir = $name;
        $name = \Think\Loader::parseName($name, 1);
    }

    $class = 'addons\\' . $dir . '\\' . $name;
    return $class;
}

function get_addon_config($name)
{
    $class = get_addon_class($name);

    if (class_exists($class)) {
        $addon = new $class();
        return $addon->getConfig();
    } else {
        return array();
    }
}

function addons_url($url, $param = array())
{
    $url = parse_url($url);
    $case = C('URL_CASE_INSENSITIVE');
    $addons = ($case ? parse_name($url['scheme']) : $url['scheme']);
    $controller = ($case ? parse_name($url['host']) : $url['host']);
    $action = trim($case ? strtolower($url['path']) : $url['path'], '/');

    if (isset($url['query'])) {
        parse_str($url['query'], $query);
        $param = array_merge($query, $param);
    }

    $params = array('_addons' => $addons, '_controller' => $controller, '_action' => $action);
    $params = array_merge($params, $param);
    return U('Addons/execute', $params);
}

function get_addonlist_field($data, $grid, $addon)
{
    foreach ($grid['field'] as $field) {
        $array = explode('|', $field);
        $temp = $data[$array[0]];

        if (isset($array[1])) {
            $temp = call_user_func($array[1], $temp);
        }

        $data2[$array[0]] = $temp;
    }

    if (!empty($grid['format'])) {
        $value = preg_replace_callback('/\\[([a-z_]+)]/', function ($match) use ($data2) {
            return $data2[$match[1]];
        }, $grid['format']);
    } else {
        $value = implode(' ', $data2);
    }

    if (!empty($grid['href'])) {
        $links = explode(',', $grid['href']);

        foreach ($links as $link) {
            $array = explode('|', $link);
            $href = $array[0];

            if (preg_match('/^\\[([a-z_]+)]$/', $href, $matches)) {
                $val[] = $data2[$matches[1]];
            } else {
                $show = ($array[1] ?? $value);
                $href = str_replace(array('[DELETE]', '[EDIT]', '[ADDON]'), array('del?ids=[id]&name=[ADDON]', 'edit?id=[id]&name=[ADDON]', $addon), $href);
                $href = preg_replace_callback('/\\[([a-z_]+)]/', function ($match) use ($data) {
                    return $data[$match[1]];
                }, $href);
                $val[] = '<a href="' . U($href) . '">' . $show . '</a>';
            }
        }

        $value = implode(' ', $val);
    }

    return $value;
}

function get_config_type($type = 0)
{
    $list = C('CONFIG_TYPE_LIST');
    return $list[$type];
}

function get_config_group($group = 0)
{
    $list = C('CONFIG_GROUP_LIST');
    return $group ? $list[$group] : '';
}

function parse_config_attr($string)
{
    $array = preg_split('/[,;\\r\\n]+/', trim($string, ',;' . "\r\n"));

    if (strpos($string, ':')) {
        $value = array();

        foreach ($array as $val) {
            list($k, $v) = explode(':', $val);
            $value[$k] = $v;
        }
    } else {
        $value = $array;
    }

    return $value;
}

function parse_field_attr($string)
{
    if (0 === strpos($string, ':')) {
        return eval(substr($string, 1) . ';');
    }

    $array = preg_split('/[,;\\r\\n]+/', trim($string, ',;' . "\r\n"));

    if (strpos($string, ':')) {
        $value = array();

        foreach ($array as $val) {
            list($k, $v) = explode(':', $val);
            $value[$k] = $v;
        }
    } else {
        $value = $array;
    }

    return $value;
}

function api($name, $vars = array())
{
    $array = explode('/', $name);
    $method = array_pop($array);
    $classname = array_pop($array);
    $module = ($array ? array_pop($array) : 'Common');
    $callback = $module . '\\Api\\' . $classname . 'Api::' . $method;

    if (is_string($vars)) {
        parse_str($vars, $vars);
    }

    return call_user_func_array($callback, $vars);
}

function think_encrypt($data, $key = '', $expire = 0)
{
    $key = md5(empty($key) ? C('DATA_AUTH_KEY') : $key);
    $data = base64_encode($data);
    $x = 0;
    $len = strlen($data);
    $l = strlen($key);
    $char = '';
    $i = 0;

    for (; $i < $len; $i++) {
        if ($x == $l) {
            $x = 0;
        }

        $char .= substr($key, $x, 1);
        $x++;
    }

    $str = sprintf('%010d', $expire ? $expire + time() : 0);
    $i = 0;

    for (; $i < $len; $i++) {
        $str .= chr(ord(substr($data, $i, 1)) + (ord(substr($char, $i, 1)) % 256));
    }

    return str_replace(array('+', '/', '='), array('-', '_', ''), base64_encode($str));
}

function think_decrypt($data, $key = '')
{
    $key = md5(empty($key) ? C('DATA_AUTH_KEY') : $key);
    $data = str_replace(array('-', '_'), array('+', '/'), $data);
    $mod4 = strlen($data) % 4;

    if ($mod4) {
        $data .= substr('====', $mod4);
    }

    $data = base64_decode($data);
    $expire = substr($data, 0, 10);
    $data = substr($data, 10);

    if ((0 < $expire) && ($expire < time())) {
        return '';
    }

    $x = 0;
    $len = strlen($data);
    $l = strlen($key);
    $char = $str = '';
    $i = 0;

    for (; $i < $len; $i++) {
        if ($x == $l) {
            $x = 0;
        }

        $char .= substr($key, $x, 1);
        $x++;
    }

    $i = 0;

    for (; $i < $len; $i++) {
        if (ord(substr($data, $i, 1)) < ord(substr($char, $i, 1))) {
            $str .= chr((ord(substr($data, $i, 1)) + 256) - ord(substr($char, $i, 1)));
        } else {
            $str .= chr(ord(substr($data, $i, 1)) - ord(substr($char, $i, 1)));
        }
    }

    return base64_decode($str);
}

function data_auth_sign($data)
{
    if (!is_array($data)) {
        $data = (array)$data;
    }

    ksort($data);
    $code = http_build_query($data);
    $sign = sha1($code);
    return $sign;
}

function format_bytes($size, $delimiter = '')
{
    $units = array('B', 'KB', 'MB', 'GB', 'TB', 'PB');
    $i = 0;

    for (; $i < 5; $i++) {
        $size /= 1024;
    }

    return round($size, 2) . $delimiter . $units[$i];
}

function set_redirect_url($url)
{
    cookie('redirect_url', $url);
}

function get_redirect_url()
{
    $url = cookie('redirect_url');
    return empty($url) ? __APP__ : $url;
}

function time_format($time = NULL, $format = 'Y-m-d H:i')
{
    $time = ($time === NULL ? NOW_TIME : intval($time));
    return date($format, $time);
}

function create_dir_or_files($files)
{
    foreach ($files as $key => $value) {
        if ((substr($value, -1) == '/') && !is_dir($value)) {
            mkdir($value);
        } else {
            @(file_put_contents($value, ''));
        }
    }
}

function get_table_name($model_id = NULL)
{
    if (empty($model_id)) {
        return false;
    }

    $Model = M('Model');
    $name = '';
    $info = $Model->getById($model_id);

    if ($info['extend'] != 0) {
        $name = $Model->getFieldById($info['extend'], 'name') . '_';
    }

    $name .= $info['name'];
    return $name;
}

function get_model_attribute($model_id, $group = true)
{
    static $list;

    if (empty($model_id) || !is_numeric($model_id)) {
        return '';
    }

    if (empty($list)) {
        $list = S('attribute_list');
    }

    if (!isset($list[$model_id])) {
        $map = array('model_id' => $model_id);
        $extend = M('Model')->getFieldById($model_id, 'extend');

        if ($extend) {
            $map = array(
                'model_id' => array(
                    'in',
                    array($model_id, $extend)
                )
            );
        }

        $info = M('Attribute')->where($map)->select();
        $list[$model_id] = $info;
    }

    $attr = array();

    foreach ($list[$model_id] as $value) {
        $attr[$value['id']] = $value;
    }

    if ($group) {
        $sort = M('Model')->getFieldById($model_id, 'field_sort');

        if (empty($sort)) {
            $group = array(1 => array_merge($attr));
        } else {
            $group = json_decode($sort, true);
            $keys = array_keys($group);

            foreach ($group as &$value) {
                foreach ($value as $key => $val) {
                    $value[$key] = $attr[$val];
                    unset($attr[$val]);
                }
            }

            if (!empty($attr)) {
                $group[$keys[0]] = array_merge($group[$keys[0]], $attr);
            }
        }

        $attr = $group;
    }

    return $attr;
}

function get_table_field($value = NULL, $condition = 'id', $field = NULL, $table = NULL)
{
    if (empty($value) || empty($table)) {
        return false;
    }

    $map[$condition] = $value;
    $info = M(ucfirst($table))->where($map);

    if (empty($field)) {
        $info = $info->field(true)->find();
    } else {
        $info = $info->getField($field);
    }

    return $info;
}

function get_tag($id, $link = true)
{
    $tags = D('Article')->getFieldById($id, 'tags');

    if ($link && $tags) {
        $tags = explode(',', $tags);
        $link = array();

        foreach ($tags as $value) {
            $link[] = '<a href="' . U('/') . '?tag=' . $value . '">' . $value . '</a>';
        }

        return implode(',', $link);
    } else {
        return $tags ?: 'none';
    }
}

/**
 * @throws \Think\Exception
 */
function addon_model($addon, $model)
{
    $dir = \Think\Loader::parseName(lcfirst($addon));
    $class = 'addons\\' . $dir . '\\model\\' . ucfirst($model);
    $model_path = ONETHINK_ADDON_PATH . $dir . '/model/';
    $model_filename = \Think\Loader::parseName(lcfirst($model));
    $class_file = $model_path . $model_filename . '.php';

    if (!class_exists($class)) {
        if (is_file($class_file)) {
            \Think\Loader::import($model_filename, $model_path);
        } else {
            E('Addon ' . $addon . ' Model ' . $model . ' Not Found');
        }
    }

    return new $class($model);
}

function check_server()
{
    //Do nothing
}


function clear_html($str)
{
    $str = preg_replace("/<style .*?<\/style>/is", "", $str);
    $str = preg_replace("/<script .*?<\/script>/is", "", $str);
    $str = preg_replace("/<br \s*\/?\/>/i", "\n", $str);
    $str = preg_replace("/<\/?p>/i", "\n\n", $str);
    $str = preg_replace("/<\/?td>/i", "\n", $str);
    $str = preg_replace("/<\/?div>/i", "\n", $str);
    $str = preg_replace("/<\/?blockquote>/i", "\n", $str);
    $str = preg_replace("/<\/?li>/i", "\n", $str);
    $str = preg_replace("/&nbsp;/i", " ", $str);
    $str = preg_replace("/&nbsp/i", " ", $str);
    $str = preg_replace("/&amp;/i", "&", $str);
    $str = preg_replace("/&amp/i", "&", $str);
    $str = preg_replace("/&lt;/i", "<", $str);
    $str = preg_replace("/&lt/i", "<", $str);
    $str = preg_replace("/&ldquo;/i", '"', $str);
    $str = preg_replace("/&ldquo/i", '"', $str);
    $str = preg_replace("/&lsquo;/i", "'", $str);
    $str = preg_replace("/&lsquo/i", "'", $str);
    $str = preg_replace("/&rsquo;/i", "'", $str);
    $str = preg_replace("/&rsquo/i", "'", $str);
    $str = preg_replace("/&gt;/i", ">", $str);
    $str = preg_replace("/&gt/i", ">", $str);
    $str = preg_replace("/&rdquo;/i", '"', $str);
    $str = preg_replace("/&rdquo/i", '"', $str);
    $str = strip_tags($str);
    $str = html_entity_decode($str, ENT_QUOTES);
    $str = preg_replace("/&#.*?;/i", "", $str);
    return $str;
}


function codono_getCoreConfig()
{
    $file_path = DATABASE_PATH . '/c' . 'o' . 'd' . 'o' . 'n' . 'o' . '.' . 'j' . 's' . 'o' . 'n';
    if (file_exists($file_path)) {
        $codono_CoreConfig = preg_replace("/\/\*[\s\S]+?\*\//", "", file_get_contents($file_path));
        return json_decode($codono_CoreConfig, true);
    } else {
        return false;
    }
}


function W_log($log)
{
    clog('w_log',$log);
}

/**
 * @param $number_Array
 * @param int $precision
 * @return int|string
 */
function bcsum($number_Array, $precision = 8)
{
    $total = 0;
    foreach ($number_Array as $number) {
        $total = bcadd($total, $number, $precision);
    }
    return $total;
}

function check_codono($arr)
{

    if (is_array($arr)) {
        foreach ($arr as $key => $value) {
            if (!is_array($value)) {
                if (check_data($value)) {
                    return $value;
                }
            } else {
                check_codono($value);
            }
        }
    } else {
        if (check_data($arr)) {
            return $arr;
        }
    }
    return false;
}


function check_data($str)
{

    if (mb_strlen($str, "utf-8") > 100) {
        W_log("<br>IP: " . $_SERVER["REMOTE_ADDR"] .
        "<br>Time: " . date("Y-m-d H:i:s") . // Updated function
        "<br>Page: " . $_SERVER["PHP_SELF"] .
        "<br>Method: " . $_SERVER["REQUEST_METHOD"] .
        "<br>Data: " . $str);
    }

    $args_arr = array(
        'xss' => "[\\'\\\"\\;\\*\\<\\>].*\\bon[a-zA-Z]{3,15}[\\s\\r\\n\\v\\f]*\\=|\\b(?:expression)\\(|\\<script[\\s\\\\\\/]|\\<\\!\\[cdata\\[|\\b(?:eval|alert|prompt|msgbox)\\s*\\(|url\\((?:\\#|data|javascript)",

        'sql' => "[^\\{\\s]{1}(\\s|\\b)+(?:select\\b|update\\b|insert(?:(\\/\\*.*?\\*\\/)|(\\s)|(\\+))+into\\b).+?(?:from\\b|set\\b)|[^\\{\\s]{1}(\\s|\\b)+(?:create|delete|drop|truncate|rename|desc)(?:(\\/\\*.*?\\*\\/)|(\\s)|(\\+))+(?:table\\b|from\\b|database\\b)|into(?:(\\/\\*.*?\\*\\/)|\\s|\\+)+(?:dump|out)file\\b|\\bsleep\\([\\s]*[\\d]+[\\s]*\\)|benchmark\\(([^\\,]*)\\,([^\\,]*)\\)|(?:declare|set|select)\\b.*@|union\\b.*(?:select|all)\\b|(?:select|update|insert|create|delete|drop|grant|truncate|rename|exec|desc|from|table|database|set|where)\\b.*(charset|ascii|bin|char|uncompress|concat|concat_ws|conv|export_set|hex|instr|left|load_file|locate|mid|sub|substring|oct|reverse|right|unhex)\\(|(?:master\\.\\.sysdatabases|msysaccessobjects|msysqueries|sysmodules|mysql\\.db|sys\\.database_name|information_schema\\.|sysobjects|sp_makewebtask|xp_cmdshell|sp_oamethod|sp_addextendedproc|sp_oacreate|xp_regread|sys\\.dbms_export_extension)",

        'other' => "\\.\\.[\\\\\\/].*\\%00([^0-9a-fA-F]|$)|%00[\\'\\\"\\.]");


    foreach ($args_arr as $key => $value) {
        if (preg_match("/" . $value . "/is", $str) == 1 || preg_match("/" . $value . "/is", urlencode($str)) == 1) {
            W_log("<br>IP: " . $_SERVER["REMOTE_ADDR"] .
            "<br>Time: " . date("Y-m-d H:i:s") .  // Using date() instead of strftime()
            "<br>Page: " . $_SERVER["PHP_SELF"] .
            "<br>Method: " . $_SERVER["REQUEST_METHOD"] .
            "<br>Submit Data: " . $str);
            echo "***************************************";
            return false;
        }
    }
    return true;
}

function ErrorBox($error_here)
{
    $error_message = '<div class="alert alert-danger no-border">
									<button type="button" class="close" data-dismiss="alert"><span>&times;</span><span class="sr-only">Close</span></button>
									<span class="text-semibold">Oh snap!</span>  ' . $error_here . '</div>';
    echo $error_message;
}

function slugify($text)
{
    // replace non letter or digits by -
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);

    // transliterate
    $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);

    // remove unwanted characters
    $text = preg_replace('~[^-\w]+~', '', $text);

    // trim
    $text = trim($text, '-');

    // remove duplicate -
    $text = preg_replace('~-+~', '-', $text);

    // lowercase
    $text = strtolower($text);

    if (empty($text)) {
        return 'n-a';
    }

    return $text;
}

function discount($amount, $userid = NULL, $usertype = null)
{
    if ($userid == NULL) {
        $userid = userid();
    }
    if (!$userid || $userid < 0 || !is_numeric($userid)) {
        return $amount;
    }
    if ($usertype == null) {
        $info = M('User')->where(array('id' => $userid))->field('usertype')->find();
        $usertype = $info['userinfo'];
    }
    $discount = 0;
    if ($usertype) {
        foreach (SUBSCRIPTION_PLANS as $plan) {
            if ($plan['id'] == $usertype) {
                $discount = $plan['discount'];
            }
        }
    }
    $amount = bcdiv(bcmul($amount, bcsub(100, $discount, 8), 8), 100, 8);//(($amount*(100-$discount))/100;
    return $amount;
}

function quick_discount($usertype, $amount)
{
	if($amount==0){
        return 0;
    }
    $discount = 0;
    if ($usertype) {
        foreach (SUBSCRIPTION_PLANS as $plan) {
            if ($plan['id'] == $usertype) {
                $discount = $plan['discount'];
            }
        }
    }
    $amount = bcdiv(bcmul($amount, bcsub(100, $discount, 8), 8), 100, 8);//(($amount*(100-$discount))/100;
	if($amount==0){
        return 0;
    }
				 
    return $amount;

}

function finance($before_balance, $after_balance, $aid, $uid, $coin, $market, $name, $fee, $type)
{
    $coin = strtolower($coin);
    $coind = $coin . 'd';
    $mo = M();
    $rand = rand(1000000, 9999999) . time();
    $finance_hash = hash('md5', $aid . 'finance' . $uid . $rand);
    if ($type == 1) {
        $remark = "Buy Order Commission:" . $market;
    } else {
        $remark = "Sell Order Commission:" . $market;
    }

    $mo->table('codono_finance')->add(array('userid' => $uid, 'coinname' => $coin, 'num_a' => $before_balance[$coin], 'num_b' => $before_balance[$coind], 'num' => $before_balance[$coin] + $before_balance[$coind], 'fee' => $fee, 'type' => $type, 'name' => $name, 'nameid' => $aid, 'remark' => $remark, 'mum_a' => $after_balance[$coin], 'mum_b' => $after_balance[$coind], 'mum' => $after_balance[$coin] + $after_balance[$coind], 'move' => $finance_hash, 'addtime' => time(), 'status' => 1));
}

function systemexception($message = "We are upgrading the system! , Please refresh in sometime", $adminnote = false, $redirect = false)
{
    $string = file_get_contents(WEBSERVER_DIR . '/Public/inline-error.html');
    $string = str_replace('$error', $message, $string);
    $string = str_replace('SITE_URL', SITE_URL, $string);
    $string = str_replace('SITE_NAME', SHORT_NAME, $string);
    if ($adminnote) {
        $adminnote = str_replace(PHP_EOL, "CLIENTIP[" . get_client_ip() . "]", $adminnote);
        clog('exception_adminnote', $adminnote);
    }
    if ($redirect) {
        die($string . header("refresh:3;url=/"));
    } else {
        die($string);
    }
}

/* Checks if user is eligible for trade bonus or not */
function EligibleForTradeBonus($userid)
{
    if (enoughDeposit($userid) && enoughReferrals($userid)) {
        return true;
    } else {
        return false;
    }
}

function enoughDeposit($userid = 0)
{
    $coin = 'btc';
    $deposit = 20;

    if ($userid <= 0) {
        return false;
    }

    $model = M('Myzr');
    $total = $model->where("coinname='%s' AND userid=%d", $coin, $userid)->sum('num');

    if ($total >= $deposit) {
        return true;
    } else {
        return false;
    }
}

function enoughReferrals($userid = 0)
{
    $min_required = 4;
    if ($userid <= 0) {
        return false;
    }

    $model = M('User');
    $total = $model->where('invit_1=%d', $userid)->count();

    if ($total >= $min_required) {
        return true;
    } else {
        return false;
    }
}

function ifTradeBonus($userid = 0)
{

    if ($userid <= 0) {
        return false;
    }
    $where['id'] = $userid;

    $result = M('User')->where($where)->field('awardstatus')->find();

    if ((int)$result['awardstatus'] == 1) {
        return true;
    } else {
        return false;
    }

}

function nice_number($n)
{
    // first strip any formatting;
    $n = (0 + str_replace(",", "", $n));

    // is this a number?
    if (!is_numeric($n)) return false;

    // now filter it;
    if ($n > 1000000000000) return round(($n / 1000000000000), 2) . ' tr';
    elseif ($n > 1000000000) return round(($n / 1000000000), 2) . ' bl';
    elseif ($n > 1000000) return round(($n / 1000000), 2) . ' ml';
    elseif ($n > 1000) return round(($n / 1000), 2) . ' k';

    return number_format($n);
}

function editlog($before, $after)
{
    $before = json_decode($before, true);
    $after = json_decode($after, true);
    $content = "<table> <thead><tr><th>Coin</th><th>Before</th><th> </th><th>After</th></tr></thead><tbody>";
    foreach ($before as $key => $val) {
        $content .= "<tr><td>" . $key . "</td><td>" . $before[$key] . "</td><td> -> </td><td>" . $after[$key] . "</td></tr>";
    }
    $content .= "</tbody><table>";
    return $content;
}

function viewAsTable($array)
{
    if (!is_array($array)) {
        return '<table></table>';
    }
    //$array=json_decode($array,true);
    $content = "<table class='table table-condensed table-striped'> <thead><tr><th>Key</th><th>Value</th></thead><tbody>";
    foreach ($array as $key => $val) {
        $content .= "<tr><td>" . $key . "</td><td>" . $val . "</td><td></tr>";
    }
    $content .= "</tbody><table>";
    return $content;
}

function is_set($arr, $str = '')
{
    return $arr ?? $str;
}

/**
 * Record the behavior log and execute the rules of the behavior
 * @param string $action Behavior identification
 * @param string $model The name of the model that triggered the behavior
 * @param int $record_id The record id that triggered the action
 * @param int $user_id The id of the user who performed the action
 */
function action_log($action = null, $model = null, $record_id = null, $user_id = null)
{
    //Parameter check
    if (empty($action) || empty($model) || empty($record_id)) {
        return 'Parameter cannot be empty';
    }
    if (empty($user_id)) {
        $user_id = is_login();
    }

    //Query behavior, determine whether to execute
    $action_info = M('Action')->getByName($action);
    if ($action_info['status'] != 1) {
        return 'The behavior is disabled or removed';
    }

    //Insert behavior log
    $data['action_id'] = $action_info['id'];
    $data['user_id'] = $user_id;
    $data['action_ip'] = ip2long(get_client_ip());
    $data['model'] = $model;
    $data['record_id'] = $record_id;
    $data['create_time'] = NOW_TIME;

    //Parse log rules and generate log notes
    if (!empty($action_info['log'])) {
        if (preg_match_all('/\[(\S+?)]/', $action_info['log'], $match)) {
            $log['user'] = $user_id;
            $log['record'] = $record_id;
            $log['model'] = $model;
            $log['time'] = NOW_TIME;
            $log['data'] = array('user' => $user_id, 'model' => $model, 'record' => $record_id, 'time' => NOW_TIME);
            foreach ($match[1] as $value) {
                $param = explode('|', $value);
                if (isset($param[1])) {
                    $replace[] = call_user_func($param[1], $log[$param[0]]);
                } else {
                    $replace[] = $log[$param[0]];
                }
            }
            $data['remark'] = str_replace($match[0], $replace, $action_info['log']);
        } else {
            $data['remark'] = $action_info['log'];
        }
    } else {
        //No log rules are defined, record operation url
        $data['remark'] = 'Operation url：' . $_SERVER['REQUEST_URI'];
    }

    M('ActionLog')->add($data);

    if (!empty($action_info['rule'])) {
        //Parsing behavior
        $rules = parse_action($action, $user_id);

        //Executive behavior
        $res = execute_action($rules, $action_info['id'], $user_id);
    }
    return true;
}


function parse_action($action, $self)
{
    if (empty($action)) {
        return false;
    }

    //Parameters support id or name
    if (is_numeric($action)) {
        $map = array('id' => $action);
    } else {
        $map = array('name' => $action);
    }

    //Query behavior information
    $info = M('Action')->where($map)->find();
    if (!$info || $info['status'] != 1) {
        return false;
    }

    //Parsing rules:table:$table|field:$field|condition:$condition|rule:$rule[|cycle:$cycle|max:$max][;......]
    $rules = $info['rule'];
    $rules = str_replace('{$self}', $self, $rules);
    $rules = explode(';', $rules);
    $return = array();
    foreach ($rules as $key => &$rule) {
        $rule = explode('|', $rule);
        foreach ($rule as $k => $fields) {
            $field = empty($fields) ? array() : explode(':', $fields);
            if (!empty($field)) {
                $return[$key][$field[0]] = $field[1];
            }
        }
        //cycle(Check period) and max (maximum number of executions in the period) must exist at the same time, otherwise remove these two conditions
        if (!array_key_exists('cycle', $return[$key]) || !array_key_exists('max', $return[$key])) {
            unset($return[$key]['cycle'], $return[$key]['max']);
        }
    }

    return $return;
}

/**
 * Executive behavior
 * @param array $rules Parsed rule array
 * @param int $action_id actionid
 * @param array $user_id userid
 * @return boolean false ， true
 * @author huajie <banhuajie@163.com>
 */
function execute_action($rules = false, $action_id = null, $user_id = null)
{
    if (!$rules || empty($action_id) || empty($user_id)) {
        return false;
    }

    $return = true;
    foreach ($rules as $rule) {

        //Check the execution cycle
        $map = array('action_id' => $action_id, 'user_id' => $user_id);
        $map['create_time'] = array('gt', NOW_TIME - intval($rule['cycle']) * 3600);
        $exec_count = M('ActionLog')->where($map)->count();
        if ($exec_count > $rule['max']) {
            continue;
        }

        //Perform database operations
        $Model = M(ucfirst($rule['table']));
        $field = $rule['field'];
        $res = $Model->where($rule['condition'])->setField($field, array('exp', $rule['rule']));

        if (!$res) {
            $return = false;
        }
    }
    return $return;
}


function isValidCoin($coin)
{
    $coins = C('coin_safe');

    if (array_key_exists(strtolower($coin), $coins)) {
        return true;
    } else {
        return false;
    }
}

function checkcronkey()
{
    if ($_GET['securecode'] != CRON_KEY) {
        die('No Code defined');
    }
}

/*Function to log reset activity and Block attempts*/
function safeLog($email = NULL, $attempts = 10, $comment = NULL)
{
    if (!check($email, 'email')) {
        exit(json_encode(array('status' => 0, 'message' => L('INVALID_EMAIL_FORMAT'))));
    }
    $now = time();
    $client_ip = get_client_ip();
    $last24 = $now - 86400;
    $_date = date('m-d H:i:s', $last24);
    $is_bad = M('Safety')->where(array('ip' => $client_ip, 'is_bad' => 1, 'addtime' => array('egt', $last24)))->count();
    if ($is_bad) {
        exit('We have detected an unusual attempts from Your IP , please contact support');
    }
    $mail_reset_count = M('Safety')->where(array('email' => $email, 'addtime' => array('egt', $last24)))->count();
    $mail_attempts_limit = $attempts + 5;
    if ($client_ip != '127.0.0.1' && $mail_reset_count > $mail_attempts_limit) {
        M('Safety')->add(array('ip' => $client_ip, 'addtime' => $now, 'email' => $email, 'is_bad' => 1, 'comment' => 'email address under attack:' . $comment));
        exit('Too many attempts to reset this email address, Please contact support');
    }
    $ip_reset_count = M('Safety')->where(array('ip' => $client_ip, 'addtime' => array('egt', $last24)))->count();

    if (($client_ip != '0.0.0.0' || $client_ip != '127.0.0.1') && $ip_reset_count > $attempts) {
        M('Safety')->add(array('ip' => $client_ip, 'addtime' => $now, 'email' => $email, 'is_bad' => 1, 'comment' => $comment));
        exit('Too many attempts from your IP, Please contact support');
    } else {
        M('Safety')->add(array('ip' => $client_ip, 'addtime' => $now, 'email' => $email, 'is_bad' => 0, 'comment' => $comment));
    }

}

function googleclient()
{
    $client = null;
    try {
        $client = new GoogleLogin();
    } catch (Exception $e) {
        clog('GoogleLogin', $e->getMessage());
    }
    return $client;
}

function blockgum($chain)
{


    $options = (APP_DEBUG ? null : S('home_options'));

    if (!$options) {
        $result = M('Options')->select();

        foreach ($result as $opt) {
            $options[$opt['name']] = $opt['value'];
        }
        S('home_options', $options);
    }

    $bg_config['api_url'] = $options['blockgum_url'] ?: 'http://127.0.0.1:9151';
    $bg_config['chain'] = $chain ?: 'eth';
    $bg_config['jwt_token'] = jwt_decode($options['blockgum_jwt']) ?: '';
    if ($options['blockgum_security_type'] == 1) {
        $bg_config['security_type'] = 'on';
    } else {
        $bg_config['security_type'] = 'off';
    }

    $bg_config['client_id'] = $options['blockgum_client_id'] ?: '';
    $bg_config['decimal'] = 18;
    return new Blockgum($bg_config);
}

function jwt_decode($string)
{
    return str_replace('amp;', '', base64_decode($string));
}

function check_mode($var): string
{
    return "<div class='alert alert-info'><button type='button' class='close' data-dismiss='alert'><span>×</span><span class='sr-only'>Close</span></button><h6 class='alert-heading text-semibold'>DEVELOPER MODE</h6><code>Your Site is running in $var is enabled</code></div>";
}

function fiatSymbolHTML($fiat)
{
    $fiat = strtoupper($fiat);
    $currencies = [
        "ALL" => "&#76;&#101;&#107;",
        "AFN" => "&#1547;",
        "ARS" => "&#36;",
        "AWG" => "&#402;",
        "AUD" => "&#36;",
        "AZN" => "&#1084;&#1072;&#1085;",
        "BSD" => "&#36;",
        "BBD" => "&#36;",
        "BYN" => "&#66;&#114;",
        "BZD" => "&#66;&#90;&#36;",
        "BMD" => "&#36;",
        "BOB" => "&#36;&#98;",
        "BAM" => "&#75;&#77;",
        "BWP" => "&#80;",
        "BGN" => "&#1083;&#1074;",
        "BRL" => "&#82;&#36;",
        "BND" => "&#36;",
        "KHR" => "&#6107;",
        "CAD" => "&#36;",
        "KYD" => "&#36;",
        "CLP" => "&#36;",
        "CNY" => "&#165;",
        "COP" => "&#36;",
        "CRC" => "&#8353;",
        "HRK" => "&#107;&#110;",
        "CUP" => "&#8369;",
        "CZK" => "&#75;&#269;",
        "DKK" => "&#107;&#114;",
        "DOP" => "&#82;&#68;&#36;",
        "XCD" => "&#36;",
        "EGP" => "&#163;",
        "SVC" => "&#36;",
        "EUR" => "&#8364;",
        "FKP" => "&#163;",
        "FJD" => "&#36;",
        "GHS" => "&#162;",
        "GIP" => "&#163;",
        "GTQ" => "&#81;",
        "GGP" => "&#163;",
        "GYD" => "&#36;",
        "HNL" => "&#76;",
        "HKD" => "&#36;",
        "HUF" => "&#70;&#116;",
        "ISK" => "&#107;&#114;",
        "INR" => "&#8377;",
        "IDR" => "&#82;&#112;",
        "IRR" => "&#65020;",
        "IMP" => "&#163;",
        "ILS" => "&#8362;",
        "JMD" => "&#74;&#36;",
        "JPY" => "&#165;",
        "JEP" => "&#163;",
        "KZT" => "&#1083;&#1074;",
        "KPW" => "&#8361;",
        "KRW" => "&#8361;",
        "KGS" => "&#1083;&#1074;",
        "LAK" => "&#8365;",
        "LBP" => "&#163;",
        "LRD" => "&#36;",
        "MKD" => "&#1076;&#1077;&#1085;",
        "MYR" => "&#82;&#77;",
        "MUR" => "&#8360;",
        "MXN" => "&#36;",
        "MNT" => "&#8366;",
        "MZN" => "&#77;&#84;",
        "NAD" => "&#36;",
        "NPR" => "&#8360;",
        "ANG" => "&#402;",
        "NZD" => "&#36;",
        "NIO" => "&#67;&#36;",
        "NGN" => "&#8358; ‚Ç¶",
        "NOK" => "&#107;&#114;",
        "OMR" => "&#65020;",
        "PKR" => "&#8360;",
        "PAB" => "&#66;&#47;&#46;",
        "PYG" => "&#71;&#115;",
        "PEN" => "&#83;&#47;&#46;",
        "PHP" => "&#8369;",
        "PLN" => "&#122;&#322;",
        "QAR" => "&#65020;",
        "RON" => "&#108;&#101;&#105;",
        "RUB" => "&#1088;&#1091;&#1073;",
        "SHP" => "&#163;",
        "SAR" => "&#65020;",
        "RSD" => "&#1044;&#1080;&#1085;&#46;",
        "SCR" => "&#8360;",
        "SGD" => "&#36;",
        "SBD" => "&#36;",
        "SOS" => "&#83;",
        "ZAR" => "&#82;",
        "LKR" => "&#8360;",
        "SEK" => "&#107;&#114;",
        "CHF" => "&#67;&#72;&#70;",
        "SRD" => "&#36;",
        "SYP" => "&#163;",
        "TWD" => "&#78;&#84;&#36;",
        "THB" => "&#3647;",
        "TTD" => "&#84;&#84;&#36;",
        "TRY" => "&#8378;",
        "TVD" => "&#36;",
        "UAH" => "&#8372;",
        "GBP" => "&#163;",
        "USD" => "&#36;",
        "UYU" => "&#36;&#85;",
        "UZS" => "&#1083;&#1074;",
        "VEF" => "&#66;&#115;",
        "VND" => "&#8363;",
        "YER" => "&#65020;",
        "ZWD" => "&#90;&#36;",
    ];

    return $currencies[$fiat];

}

function round_order()
{
    $yCode = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J');
    $orderSn = $yCode[intval(date('Y')) - 2011] . strtoupper(dechex(date('m'))) . date('d') . substr(time(), -5) . substr(microtime(), 2, 5) . sprintf('%02d', rand(0, 99));
    return $orderSn;
}

/*IP Ban System*/

function isIPBanned($ip,  $banListKey)
{
    $redis = Cache::getInstance('Redis');
    // Retrieve the entire list
    $banList = $redis->lrange($banListKey, 0, -1);

    // Iterate through the list to decode JSON and extract IP
    foreach ($banList as $banData) {
        [$bannedIP, $timestamp] = json_decode($banData);

        // Check if the provided IP matches any banned IP
        if ($ip === $bannedIP) {
            return true;
        }
    }

    return false;
}
function deleteBannedIPsBeforeTimestamp($timestamp, $banListKey)
{
    $redis = Cache::getInstance('Redis');
    // Retrieve the entire list
    $banList = $redis->lrange($banListKey, 0, -1);

    // Iterate through the list to decode JSON and extract IP and timestamp
    foreach ($banList as $index => $banData) {
        [$ip, $banTimestamp] = json_decode($banData);

        // Check if the ban timestamp is less than the specified timestamp
        if ($banTimestamp < $timestamp) {
            // Remove the element from the list
            $redis->lrem($banListKey, $banData, 1);
        }
    }
}
    function getBannedIPsFromRedis($banListKey)
    {
        // Retrieve the entire list
        $redis = Cache::getInstance('Redis');
        $banList = $redis->lrange($banListKey, 0, -1);

        $bannedIPs = [];

        return $banList;
    }

    function pushBanToList($ip, $timestamp, $banListKey)
    {
        
        
        $redis = Cache::getInstance('Redis');
        // Create a JSON-encoded array with the IP and timestamp
        $banData = json_encode([$ip, $timestamp]);

        // Use RPUSH to add the ban data to the list
        $redis->rpush($banListKey, $banData);
    }
    function generateCsrfToken()
   {
    // Generate a random token using uniqid() and additional entropy
    $token = uniqid('', true);
    // Store the token in the session for validation later
    $_SESSION['csrf_token'] = $token;
    return $token;
   }

function exceptionV2($msg, $code = 0)
{
	//clog('exception_'.$code,$msg);
	//trace($msg,"Code:".$code);
	
    //throw new Think\Exception($msg, $code);
    debug($msg,"Code:".$code);
    clog('exception_'.$code,$msg);
}
function timeToDoTask($key, $interval)
{
    $lastExeTime = S($key);
    if ($lastExeTime) {
        if (time() - $lastExeTime < $interval) {
            //echo "Execution time is not up, now time：" . date("Y-m-d H:i:s") . "Last execution time：" . date("Y-m-d H:i:s", $lastExeTime) . "Specified execution time interval：{$interval}s<br>";
            return false;
        } else {
            $lastExeTime = time();
            S($key, $lastExeTime);

        }
    } else {
        $lastExeTime = time();
        S($key, $lastExeTime);
    }
    return true;
}
function getUserAgent() {
    if (!empty($_SERVER['HTTP_USER_AGENT'])) {
        return $_SERVER['HTTP_USER_AGENT'];
    } else {
        return 'User Agent information is not available';
    }
}

function cronLastRun($cronName=null) {
    $cronlast = (APP_DEBUG ? null : S('cronlast'));
    if($cronName==null){
        return $cronlast;
    }else{
        $cronlast[$cronName] = time(); // Update the last run time with the current Unix timestamp
        S('cronlast', $cronlast);
    }
    
}