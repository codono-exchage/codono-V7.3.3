<?php

namespace Home\Controller;

/*********************************************************************
 * PHP Controller for SumSub.com KYC For Codono Exchange
 * Author :Codono
 * Dated :20/12/2019
 * Basic Version 0.1
 * Username
 * Password
 * CLIENT ID required
 * Info Here
 * https://developers.sumsub.com/
 * Test here
 * https://test-api.sumsub.com/checkus#/applicants
 *********************************************************************/
class SumsubController extends HomeController
{
    protected $apiurl; // API URL for sum sub
    protected $clientid; // Clientid for sum sub
    protected $username; //Plaintext Login Username
    protected $password; //Plaintext password

    public function _initialize()
    {
        parent::_initialize();
        if (SUMSUB_KYC['status'] != '1') {
            $this->error(L('SumSub KYC is currently disabled'));
        }
        // Set keys and format passed to class
        if (SUMSUB_KYC['mode'] == 'test') {
            $this->apiurl = 'https://test-api.sumsub.com';
        } else {
            $this->apiurl = 'https://api.sumsub.com';
        }
        $this->username = SUMSUB_KYC['username'];
        $this->password = SUMSUB_KYC['password'];
        $this->clientid = SUMSUB_KYC['clientid'];
    }

    public function index()
    {
        /*
        $result = M('User')->query("SHOW COLUMNS FROM `codono_user` LIKE 'applicantid'");

        if($result == FALSE){
        $query="ALTER TABLE `codono_user` ADD `applicantid` VARCHAR(50) NOT NULL DEFAULT '0' COMMENT 'KYC applicantid for sumsub' AFTER `truename`";
        M()->query($query);
        }
    */
        if (!userid()) {
            redirect('/#login', $time = 1, $msg = L('Please Login First!'));
        }

        //check if sumsub applicantid is there
        $applicantid = $this->userinfo['applicantId'];//M('User')->where(array('id' => userid()))->getField("applicantid");
        $email = $this->userinfo['email'];//M('User')->where(array('id' => userid()))->getField("email");
        $externaluid = "UID_" . userid();

        if (!isset($applicantid) || $applicantid == '0' || $applicantid == null || $applicantid = "") {
            $applicantid = $this->createApplicant($email, $externaluid);
            //save_applicantid
            M('User')->where(array('id' => userid()))->save(array('applicantid' => $applicantid));
        } else {
            $applicantStatus = json_decode($this->applicationStatus($applicantid));
            if ($applicantStatus->code == 404 || $applicantStatus->code == 400) {
                //Create and update new applicant status
                $applicantid = $this->createApplicant($email, $externaluid);
                //save_applicantid
                M('User')->where(array('id' => userid()))->save(array('applicantid' => $applicantid));
            }
        }

        $js_url = $this->apiurl . "/idensic/static/sumsub-kyc.js";

        $accesstoken = $this->getAccessToken($externaluid);
        $this->assign('js_url', $js_url);
        $this->assign('externaluid', $externaluid);
        $this->assign('clientid', $this->clientid);
        $this->assign('accesstoken', $accesstoken);
        $this->display('Sumsub/index');
    }

    private function xSend($endpoint, $method = "POST", $data = null)
    {
        $curl = curl_init();
        $curl_array = array(
            CURLOPT_URL => $this->apiurl . $endpoint,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_SSL_VERIFYPEER => FALSE,

            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => array(
                "Accept: application/json",
                "Authorization: Bearer " . $this->getBearer(),
                "Content-Type: application/json"

            )
        );
        if ($method == 'POST') {
            $curl_array['CURLOPT_POSTFIELDS'] = $data;
        }

        curl_setopt_array($curl, $curl_array);

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
            return $err;
        } else {
            return $response;
        }
    }

    private function plainSend($endpoint, $method = "POST", $data = null)
    {
        $curl = curl_init();
        $curl_array = array(
            CURLOPT_URL => $this->apiurl . $endpoint,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_SSL_VERIFYPEER => FALSE,

            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => array(
                "Authorization: Bearer " . $this->getBearer(),
                "Content-Type: text/plain"

            )
        );
        if ($method == 'POST') {
            $curl_array['CURLOPT_POSTFIELDS'] = $data;
        }

        curl_setopt_array($curl, $curl_array);

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
            return false;
        } else {
            return $response;
        }
    }

    //Authorization: Bearer
    private function getBearer()
    {
        $auth_basic = base64_encode($this->username . ':' . $this->password);
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $this->apiurl . "/resources/auth/login",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_SSL_VERIFYPEER => FALSE,
            CURLOPT_HTTPHEADER => array(
                "Accept: application/json",
                "Authorization: Basic " . $auth_basic,
                "Content-Type: application/json"
            )
        ));

        $response = json_decode(curl_exec($curl));
        $err = curl_error($curl);

        curl_close($curl);
        if ($err) {
            return false;
        } else {
            return $response->payload;
        }
    }


    private function getAccessToken($External_userid = 'random0')
    {
        $ttl = 1800;
        $endpoint = "/resources/accessTokens?userId=" . $External_userid . "&ttlInSecs=" . $ttl;
        $method = "POST";
        $response = json_decode($this->xSend($endpoint, $method));

        if ($response) {
            return $response->token;
        } else {
            return false;
        }

    }

    private function checkIfApplicant($externaluid)
    {
        $endpoint = "/resources/applicants/-;externalUserId=" . $externaluid;
        $method = "GET";
        $response = json_decode($this->xSend($endpoint, $method));

        if ($response->list->totalItems > 0) {
            return $response->list->items[0]->id;
        }
        return false;
    }

    private function createApplicant($email, $externaluid)
    {

        $sumsub_aid = $this->checkIfApplicant($externaluid); //Check if this  externaluid has some applicant id already
        if ($sumsub_aid) {
            return $sumsub_aid;
        }

        $curl = curl_init();
        $bearer = $this->getBearer();
        $data = "{\r\n    \"applicant\": {\r\n        \"email\": \"$email\",\r\n        \"info\": {\r\n        \"firstName\": null,\r\n        \"lastName\": null\r\n    },\r\n        \"country\" : null,\r\n        \"requiredIdDocs\": {\r\n            \"docSets\": [{\r\n                \"idDocSetType\": \"IDENTITY\",\r\n                \"types\": [\"ID_CARD\", \"PASSPORT\", \"DRIVERS\",\"RESIDENCE_PERMIT\"],\r\n                 \"subTypes\" : [ \"FRONT_SIDE\", \"BACK_SIDE\" ]\r\n            }, {\r\n                \"idDocSetType\": \"SELFIE\",\r\n                \"types\": [\"SELFIE\"]\r\n            }]\r\n        },\r\n        \"externalUserId\": \"$externaluid\"\r\n    }\r\n}";
        curl_setopt_array($curl, array(
            CURLOPT_URL => $this->apiurl . "/resources/accounts/-/applicantRequests",
            CURLOPT_RETURNTRANSFER => true,

            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_SSL_VERIFYPEER => FALSE,
            CURLOPT_POSTFIELDS => $data,
            CURLOPT_HTTPHEADER => array(
                "Accept: application/json",
                "Authorization: Bearer " . $bearer,
                "Content-Type: application/json",

            ),
        ));

        $response = (curl_exec($curl));
        $err = curl_error($curl);
        curl_close($curl);
        if ($err) {
            $array['code'] = 400;
            $array['error'] = $err;
            return json_encode($array);
        } else {
            return $response;
        }
    }

    private function applicationStatus($application_id)
    {

        $endpoint = "/resources/applicants/" . $application_id . "/status";
        $method = "GET";

        $response = $this->xSend($endpoint, $method);
        if ($response) {
            return $response;
        } else {
            return false;
        }

    }

    private function findApplicant($application_id)
    {

        $endpoint = "/resources/applicants/" . $application_id;
        $method = "GET";

        $response = $this->xSend($endpoint, $method);
        if ($response) {
            return $response;
        } else {
            return false;
        }

    }

    private function test_findApplicant($application_id)
    {
        $endpoint = "/resources/applicants/" . $application_id;
        $method = "GET";

        $response = $this->xSend($endpoint, $method);

        $cross_Check = json_decode($response);
        $cross_final = $cross_Check->list->items[0];

        $final_result = $cross_final->review->reviewResult->reviewAnswer;
        $appeal = $cross_final->review->reviewResult->reviewRejectType;
        var_dump($appeal);
    }

    private function errorAndDie($error_msg)
    {
        echo $error_msg;
        $name = getcwd() . '/Public/Log/sum_sub_errors.txt';
        $json_string = $error_msg;
        file_put_contents($name, $json_string, FILE_APPEND);
        die();

    }

    private function digest()
    {
        $put_a_secret = SUMSUB_KYC['put_a_secret'];
        $endpoint = "/resources/inspectionCallbacks/testDigest?secretKey=" . $put_a_secret;
        $method = "POST";
        //$data="sometext";
        $response = json_decode($this->plainSend($endpoint, $method));
        return $response->digest;
    }

    private function markUserKYC($applicantid, $externalUserId, $status = 0)
    {

        $uid = substr($externalUserId, strpos($externalUserId, "_") + 1);

        return M('User')->where(array('id' => $uid))->save(array('applicantid' => $applicantid, 'idcardauth' => $status));
    }

    public function webhook()
    {

        $content = file_get_contents('php://input');
        $request = json_decode($content, true);
        $filename = 'input_' . time();
        //file_put_contents('Public/Log/' . $filename . '.log', $content);


        if (!$request['id'] || !is_array($request)) {
            //$this->errorAndDie('Error reading POST data');
        }

        if (!isset($request['id']) || !isset($request['applicantId'])) {
            //$this->errorAndDie('No or incorrect applicationid');
        }
        $cross = $this->findApplicant($request['applicantId']);
        $cross_Check = json_decode($cross);
        $cross_final = $cross_Check->list->items[0];

        $final_result = $cross_final->review->reviewResult->reviewAnswer;
        $appeal = $cross_final->review->reviewResult->reviewRejectType;
        if ($final_result == "GREEN") {
            $status = 1;
            $status = $this->markUserKYC($request['applicantId'], $cross_final->externalUserId, $status);
        } else if ($final_result == "RED" && $appeal == 'FINAL') {
            $status = 3;
            $status = $this->markUserKYC($request['applicantId'], $cross_final->externalUserId, $status);
        } else {
            $status = 0;
        }
        //$hmac = hash_hmac("sha512", $request, trim($cp_ipn_secret));
        echo '{"status":' . $status . '}';

    }

    //This function is for only Test environement
    public function Test_testCompleted($application_id, $grant = "GREEN")
    {
        if (SUMSUB_KYC['mode'] == 'live') {
            die('This is live environment');
        }

        if ($grant == 'GREEN') {
            $data = json_encode(array("reviewAnswer" => "GREEN", "rejectLabels" => array()));
        } else {
            $data = '{"reviewAnswer" : "RED",   "rejectLabels": ["ID_INVALID"],   "reviewRejectType": "RETRY"  }';
            //    $data=json_encode(array("reviewAnswer"=>"RED","rejectLabels"=>array()));
        }

        $endpoint = "/resources/applicants/" . $application_id . "/status/testCompleted";
        $method = "POST";
        var_dump($data);
        var_dump($endpoint);
        $response = $this->xSend($endpoint, $method, $data);
        var_dump($response);
        if ($response) {
            echo $response;
        } else {
            echo false;
        }

    }
}

