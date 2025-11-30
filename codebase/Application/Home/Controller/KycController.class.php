<?php
/*********************************************************************
 * PHP Controller for ShuftiPro.com KYC For Exchange
 * Author :Codono
 * Dated :05/05/2022
 * Basic Version 0.1
 * client_id
 * secret_key
 * https://api.shuftipro.com/
 * Dashboard
 * https://backoffice.shuftipro.com/dashboard
 * https://api.shuftipro.com/generate-sample-code [To generate sample code]
 * https://shuftipro.com/help-center/response-events/ [response events for callback decoding
 * request.pending=RequestPending,
 * request.invalid=RequestInvalid,
 * request.timeout=RequestTimeout,
 * request.unauthorized=RequestUnauthorized,
 * request.deleted=RequestDeleted,
 * request.received=RequestReceived,
 * verification.accepted=VerificationAccepted,
 * verification.declined=VerificationDeclined,
 * verification.cancelled=VerificationCancelled,
 * verification.status.changed=VerificationStatusChanged,
 *********************************************************************/


namespace Home\Controller;

class KycController extends HomeController
{

    protected string $apiurl; // API URL for sum sub
    protected string $client_id; // Client id for ShuftiPro
    protected string $secret_key; //Plaintext Login Username
    protected string $callback_url; //URL for call back
    protected string $debug;

    public function __construct()
    {


        parent::__construct();

        // Set keys and format passed to class
        $this->apiurl = 'https://api.shuftipro.com/';
        $this->client_id = '77dee2f0b6b3bc151ddf95f8237115e841f95ddcce31d9a768c11090c0cef782';
        $this->secret_key = 'UXlZh2vyRFgTXUC5hqKOLJZdl8h2XUkS'; //Your Shufti Pro account Secret Key
        $this->callback_url = SITE_URL . 'Kyc/callback';
        $this->debug = true;
    }

    private function ifAllowToKYC(): array
    {
        $userid = userid();
        if (!$userid) {
            return array('status' => 0, 'message' => L('PLEASE_LOGIN'));
        }
        $user = M('User')->where(array('id' => $userid))->find();
        if ($user['idcardauth'] == 1) {
            return array('status' => 0, 'message' => L('Account already verified'), 'url' => null);
        }
        if ($user['idcardauth'] == 2) {
            return array('status' => 0, 'message' => L('KYC being verified'), 'url' => null);
        }
        return array('status' => 1, 'message' => L('Complete KYC First!'), 'url' => null);
    }

    public function index()
    {
        $info['status'] = 0;
        $info['url'] = null;
        $info['message'] = null;
        $allow = $this->ifAllowToKYC();
        if ($allow['status'] != 1) {
            $info = $allow;
			redirect('User/index');
        } else {
            $info = $this->generateURL(userid());
        }
        if($info['url']){
            $this->assign('iframe',true);
            $this->assign('url',$info['url']);

        }else{
            $this->assign('iframe',false);
            $this->assign('url',$info['message']);
        }
        $this->display();
    }


    public function callback()
    {
        $json['data'] = file_get_contents('php://input');
        $json['header'] = getallheaders();
        $code = I('get.code', '', 'text');
        $decoded_code = cryptString($code, 'd');
        $info = explode('_', $decoded_code);
        $json['uid'] = $info[0];
        $json['email'] = $info[1];

        $this->kycDebug('kyc_callback_new', $json);

        $response_data = $this->decodeResponse($json);
        if ($response_data['reference']) {
            return M('User')->where(array('id' => $json['uid']))->save(array('applicantid' => $response_data['reference'], 'kyc_comment' => $response_data['message'], 'idcardauth' => $response_data['status']));
        } else {
            return false;
        }
    }

    private function responseSignature($header)
    {
        if ($header['Signature']) {
            return $header['Signature'];
        } else {
            return null;
        }
    }

    private function decodeResponse($response)
    {
        $sp_signature = $this->responseSignature($response['header']);
        $response_data = $response['data'];
        //calculate_signature internally
        $internal_signature = hash('sha256', $response_data . $this->secret_key);
        $decoded_response = json_decode($response_data, true);
        $event_name = $decoded_response['event'];
        $event_array = array('verification.accepted', 'verification.declined', 'request.received');
        if (in_array($event_name, $event_array)) {
            if ($sp_signature == $internal_signature) {
                //echo $event_name." :" . $response_data;
                if ($event_name == $event_array[0]) {
                    return ['status' => 1, 'message' => 'KYC Verified Successfully', 'reference' => $decoded_response['reference']];
                } elseif ($event_name == $event_array[1]) {
                    return ['status' => 0, 'message' => $decoded_response['declined_reason'], 'reference' => $decoded_response['reference']];
                } else {
                    return ['status' => 0, 'message' => 'request.received', 'reference' => $decoded_response['reference']];
                }
            } else {
                //			$debug_sign['header']=$response['header'];
                $debug_sign['sp_signature'] = $sp_signature;
                $debug_sign['internal_signature'] = $internal_signature;
                $debug_sign['response_data'] = $response_data;
                $this->kycDebug('kyc_callback_signature_issue', $debug_sign);
                return false;
            }
        } else {
            $this->kycDebug('kyc_callback_type', $response_data);
            return false;
        }
    }

    private function generateURL($uid): array
    {

        $result['status'] = 0;
        $result['url'] = null;


        if (!check($uid, 'd') || $uid == 0) {
            return $result;
        }

        $email = getEmail($uid);

        $secret = cryptString($uid . '_' . $email);
        $callback = $this->callback_url . '/code/' . $secret;

        $reference_number = 'kyc-' . $uid . '-' . time();

        $verification_request = [
            'reference' => $reference_number,
            'country' => '',
            'language' => '',
            'email' => $email,
            'callback_url' => $callback,
            'verification_mode' => 'any',
            'ttl' => 10
        ];

//Use this key if you want to perform document verification with OCR
        $verification_request['document'] = [
            'proof' => '',
            'additional_proof' => '',
            'name' => '',
            'dob' => '',
            'age' => '',
            'document_number' => '',
            'expiry_date' => '',
            'issue_date' => '',
            'allow_offline' => '1',
            'allow_online' => '1',
            'supported_types' => ['id_card', 'passport'],
            "gender" => ""
        ];
        /*
        //Use this key if you want to perform address verification with OCR
        $verification_request['address'] = [
            'proof' => '',
            'name' => '',
            'full_address'    => '',
            'address_fuzzy_match' => '1',
            'issue_date' => '',
            'supported_types' => ['utility_bill','passport','bank_statement']
        ];
        */
        $auth = $this->client_id . ":" . $this->secret_key; // remove this in case of Access Token
        $headers = ['Content-Type: application/json'];
        // if using Access Token then add it into headers as mentioned below otherwise remove access token
        // array_push($headers, 'Authorization: Bearer ' . $access_token);
        $post_data = json_encode($verification_request);

        //Calling Shufti Pro request API using curl
        $response = $this->send_curl($this->apiurl, $post_data, $headers, $auth); // remove $auth in case of Access Token

        //Get Shufti Pro API Response
        $response_data = $response['body'];
        //Get Shufti Pro Signature
        $exploded = explode("\n", $response['headers']);

        // Get Signature Key from Headers
        $sp_signature = null;
        foreach ($exploded as $value) {
            if (strpos($value, 'signature: ') !== false || strpos($value, 'Signature: ') !== false) {
                $sp_signature = trim(explode(':', $value)[1]);
                break;
            }
        }

        //Calculating signature for verification
        // calculated signature functionality cannot be implemented in case of access token
        $calculate_signature = hash('sha256', $response_data . $this->secret_key);
        $decoded_response = json_decode($response_data, true);
        $event_name = $decoded_response['event'];
        if ($event_name == 'request.pending') {
            if ($sp_signature == $calculate_signature) {
                $verification_url = $decoded_response['verification_url'];
                //        echo "Verification url :" . $verification_url;
                $result['status'] = 1;
                $result['url'] = $verification_url;
            } else {
                //echo "Invalid signature :" . $response_data;
                $this->kycDebug('shuftipro', $response_data);
            }
        } else {
            //echo "Error :" . $response_data;
            $this->kycDebug('shuftipro', $response_data);
        }
        return $result;
    }

    private function send_curl($url, $post_data, $headers, $auth): array
    { // remove $auth in case of Access Token
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_USERPWD, $auth); // remove this in case of Access Token
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_HEADER, 1);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC); // remove this in case of Access Token
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        $html_response = curl_exec($ch);
        $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $headers = substr($html_response, 0, $header_size);
        $body = substr($html_response, $header_size);
        curl_close($ch);
        return ['headers' => $headers, 'body' => $body];
    }

    private function kycDebug($name, $data)
    {
        if ($this->debug) {
            clog($name, $data);
        }
    }

}