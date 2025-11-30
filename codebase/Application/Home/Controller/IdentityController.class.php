<?php

/*********************************************************************
 * PHP Controller for  KYC For Exchange
 * Author :Codono
 * Dated :20/08/2024
 * Basic Version 0.1
 * api_token

 * https://kyc-api.amlbot.com
 * Dashboard
 * https://kyc.amlbot.com/
 * https://kyc-docs.amlbot.com/ [Documentation]
 * KYC Type: DOCUMENT/ FACIAL
 * ENTITY TYPE: PERSON/COMPANY
 *********************************************************************/


namespace Home\Controller;

class IdentityController extends HomeController
{


    public function __construct()
    {

        parent::__construct();

        // Set keys and format passed to class
        $this->apiurl = 'https://kyc-api.amlbot.com/';
        $this->api_token = '25774950010ad54b7329c6f86c2f2abd14c0';
        $this->formid = '8656ebb308ad584c872a7e3072d9a45487e2';
        $this->business_form = '65ca91480a475549d4290b78d5d01b227c4f';
        $this->web_sdk_key = '33bb575a00b8804c9a09c9b07d34a4e90220';

        $this->callback_url = SITE_URL . 'Identity/hook';
        $this->debug = true;
    }
    public function index()
    {
        $uid = userid();
        if (!$uid) {
            $this->error('Please login first');
        }
        $allow = $this->ifAllowToKYC();

        $user_info = M('User')->where(['id' => $uid])->find();

        $user_show['uid'] = $uid;
        $user_show['kyc_status'] = $user_info['idcardauth'];
        $user_show['kyc_comment'] = $user_info['kyc_comment'];
        $user_show['truename'] = $user_info['truename'];
        $user_show['country'] = $user_info['country'];
        $user_show['color'] = $user_info['idcardauth'] == 1 ? 'success' : ($user_info['idcardauth'] == 2 ? 'danger' : 'warning');
        $user_show['progress'] = $user_info['idcardauth'] == 1 ? 'Approved' : ($user_info['idcardauth'] == 2 ? 'Rejected' : 'Pending');
        $user_show['level'] = $level_id_to_search = $user_info['usertype'];
        $info = $this->getLevelInfo($level_id_to_search, $user_info['accounttype']);
        $user_show['current_level'] = $info['current_level'];
        $user_show['next_level'] = $info['next_level'];
        $all_levels=M('UserLevel')->where(['group_type'=>$user_info['accounttype']])->select();
        $this->assign('all_levels', $all_levels);
        $this->assign('user_show', $user_show);
        $this->display();
    }

    private function getLevelInfo($current_level_id, $group_type)
    {
        $where = [
            'status' => 1,
            'group_type' => (int)$group_type
        ];
        $array = M('UserLevel')->where($where)->select();
		
        $current_level = [];
        $next_level = [];

        // Sort array by level_id to ensure levels are in ascending order
        usort($array, function ($a, $b) {
            return (int)$a['level_id'] - (int)$b['level_id'];
        });
		
        // Find the current level and the next level
        foreach ($array as $key => $level) {

            if ($level['level_id'] === $current_level_id) {
                $current_level = $level;
                if (isset($array[$key + 1])) {
                    $next_level = $array[$key + 1];
                }
                break;
            }elseif(0 == $current_level_id){
				$current_level =[];
				$next_level =$array[0];
			}
        }
        if ($current_level) {
            $response = [
                'current_level' => $current_level,
                'next_level' => $next_level ? $next_level : [],
            ];
        } else {
            $response = ['current_level' => [], 'next_level' => $next_level ? $next_level : []];
        }
        return $response;
    }
    private function countries()
    {

        $countries = [
            'AF' => 'Afghanistan',
            'AX' => 'Åland Islands',
            'AL' => 'Albania',
            'DZ' => 'Algeria',
            'AS' => 'American Samoa',
            'AD' => 'Andorra',
            'AO' => 'Angola',
            'AI' => 'Anguilla',
            'AQ' => 'Antarctica',
            'AG' => 'Antigua and Barbuda',
            'AR' => 'Argentina',
            'AM' => 'Armenia',
            'AW' => 'Aruba',
            'AU' => 'Australia',
            'AT' => 'Austria',
            'AZ' => 'Azerbaijan',
            'BS' => 'Bahamas',
            'BH' => 'Bahrain',
            'BD' => 'Bangladesh',
            'BB' => 'Barbados',
            'BY' => 'Belarus',
            'BE' => 'Belgium',
            'BZ' => 'Belize',
            'BJ' => 'Benin',
            'BM' => 'Bermuda',
            'BT' => 'Bhutan',
            'BO' => 'Bolivia, Plurinational State of',
            'BQ' => 'Bonaire, Sint Eustatius and Saba',
            'BA' => 'Bosnia and Herzegovina',
            'BW' => 'Botswana',
            'BV' => 'Bouvet Island',
            'BR' => 'Brazil',
            'IO' => 'British Indian Ocean Territory',
            'BN' => 'Brunei Darussalam',
            'BG' => 'Bulgaria',
            'BF' => 'Burkina Faso',
            'BI' => 'Burundi',
            'KH' => 'Cambodia',
            'CM' => 'Cameroon',
            'CA' => 'Canada',
            'CV' => 'Cape Verde',
            'KY' => 'Cayman Islands',
            'CF' => 'Central African Republic',
            'TD' => 'Chad',
            'CL' => 'Chile',
            'CN' => 'China',
            'CX' => 'Christmas Island',
            'CC' => 'Cocos (Keeling) Islands',
            'CO' => 'Colombia',
            'KM' => 'Comoros',
            'CG' => 'Congo',
            'CD' => 'Congo, the Democratic Republic of the',
            'CK' => 'Cook Islands',
            'CR' => 'Costa Rica',
            'CI' => 'Côte d\'Ivoire',
            'HR' => 'Croatia',
            'CU' => 'Cuba',
            'CW' => 'Curaçao',
            'CY' => 'Cyprus',
            'CZ' => 'Czech Republic',
            'DK' => 'Denmark',
            'DJ' => 'Djibouti',
            'DM' => 'Dominica',
            'DO' => 'Dominican Republic',
            'EC' => 'Ecuador',
            'EG' => 'Egypt',
            'SV' => 'El Salvador',
            'GQ' => 'Equatorial Guinea',
            'ER' => 'Eritrea',
            'EE' => 'Estonia',
            'ET' => 'Ethiopia',
            'FK' => 'Falkland Islands (Malvinas)',
            'FO' => 'Faroe Islands',
            'FJ' => 'Fiji',
            'FI' => 'Finland',
            'FR' => 'France',
            'GF' => 'French Guiana',
            'PF' => 'French Polynesia',
            'TF' => 'French Southern Territories',
            'GA' => 'Gabon',
            'GM' => 'Gambia',
            'GE' => 'Georgia',
            'DE' => 'Germany',
            'GH' => 'Ghana',
            'GI' => 'Gibraltar',
            'GR' => 'Greece',
            'GL' => 'Greenland',
            'GD' => 'Grenada',
            'GP' => 'Guadeloupe',
            'GU' => 'Guam',
            'GT' => 'Guatemala',
            'GG' => 'Guernsey',
            'GN' => 'Guinea',
            'GW' => 'Guinea-Bissau',
            'GY' => 'Guyana',
            'HT' => 'Haiti',
            'HM' => 'Heard Island and McDonald Islands',
            'VA' => 'Holy See (Vatican City State)',
            'HN' => 'Honduras',
            'HK' => 'Hong Kong',
            'HU' => 'Hungary',
            'IS' => 'Iceland',
            'IN' => 'India',
            'ID' => 'Indonesia',
            'IR' => 'Iran, Islamic Republic of',
            'IQ' => 'Iraq',
            'IE' => 'Ireland',
            'IM' => 'Isle of Man',
            'IL' => 'Israel',
            'IT' => 'Italy',
            'JM' => 'Jamaica',
            'JP' => 'Japan',
            'JE' => 'Jersey',
            'JO' => 'Jordan',
            'KZ' => 'Kazakhstan',
            'KE' => 'Kenya',
            'KI' => 'Kiribati',
            'KP' => 'Korea, Democratic People\'s Republic of',
            'KR' => 'Korea, Republic of',
            'KW' => 'Kuwait',
            'KG' => 'Kyrgyzstan',
            'LA' => 'Lao People\'s Democratic Republic',
            'LV' => 'Latvia',
            'LB' => 'Lebanon',
            'LS' => 'Lesotho',
            'LR' => 'Liberia',
            'LY' => 'Libya',
            'LI' => 'Liechtenstein',
            'LT' => 'Lithuania',
            'LU' => 'Luxembourg',
            'MO' => 'Macao',
            'MK' => 'Macedonia, the former Yugoslav Republic of',
            'MG' => 'Madagascar',
            'MW' => 'Malawi',
            'MY' => 'Malaysia',
            'MV' => 'Maldives',
            'ML' => 'Mali',
            'MT' => 'Malta',
            'MH' => 'Marshall Islands',
            'MQ' => 'Martinique',
            'MR' => 'Mauritania',
            'MU' => 'Mauritius',
            'YT' => 'Mayotte',
            'MX' => 'Mexico',
            'FM' => 'Micronesia, Federated States of',
            'MD' => 'Moldova, Republic of',
            'MC' => 'Monaco',
            'MN' => 'Mongolia',
            'ME' => 'Montenegro',
            'MS' => 'Montserrat',
            'MA' => 'Morocco',
            'MZ' => 'Mozambique',
            'MM' => 'Myanmar',
            'NA' => 'Namibia',
            'NR' => 'Nauru',
            'NP' => 'Nepal',
            'NL' => 'Netherlands',
            'NC' => 'New Caledonia',
            'NZ' => 'New Zealand',
            'NI' => 'Nicaragua',
            'NE' => 'Niger',
            'NG' => 'Nigeria',
            'NU' => 'Niue',
            'NF' => 'Norfolk Island',
            'MP' => 'Northern Mariana Islands',
            'NO' => 'Norway',
            'OM' => 'Oman',
            'PK' => 'Pakistan',
            'PW' => 'Palau',
            'PS' => 'Palestinian Territory, Occupied',
            'PA' => 'Panama',
            'PG' => 'Papua New Guinea',
            'PY' => 'Paraguay',
            'PE' => 'Peru',
            'PH' => 'Philippines',
            'PN' => 'Pitcairn',
            'PL' => 'Poland',
            'PT' => 'Portugal',
            'PR' => 'Puerto Rico',
            'QA' => 'Qatar',
            'RE' => 'Réunion',
            'RO' => 'Romania',
            'RU' => 'Russian Federation',
            'RW' => 'Rwanda',
            'BL' => 'Saint Barthélemy',
            'SH' => 'Saint Helena, Ascension and Tristan da Cunha',
            'KN' => 'Saint Kitts and Nevis',
            'LC' => 'Saint Lucia',
            'MF' => 'Saint Martin (French part)',
            'PM' => 'Saint Pierre and Miquelon',
            'VC' => 'Saint Vincent and the Grenadines',
            'WS' => 'Samoa',
            'SM' => 'San Marino',
            'ST' => 'Sao Tome and Principe',
            'SA' => 'Saudi Arabia',
            'SN' => 'Senegal',
            'RS' => 'Serbia',
            'SC' => 'Seychelles',
            'SL' => 'Sierra Leone',
            'SG' => 'Singapore',
            'SX' => 'Sint Maarten (Dutch part)',
            'SK' => 'Slovakia',
            'SI' => 'Slovenia',
            'SB' => 'Solomon Islands',
            'SO' => 'Somalia',
            'ZA' => 'South Africa',
            'GS' => 'South Georgia and the South Sandwich Islands',
            'SS' => 'South Sudan',
            'ES' => 'Spain',
            'LK' => 'Sri Lanka',
            'SD' => 'Sudan',
            'SR' => 'Suriname',
            'SJ' => 'Svalbard and Jan Mayen',
            'SZ' => 'Swaziland',
            'SE' => 'Sweden',
            'CH' => 'Switzerland',
            'SY' => 'Syrian Arab Republic',
            'TW' => 'Taiwan, Province of China',
            'TJ' => 'Tajikistan',
            'TZ' => 'Tanzania, United Republic of',
            'TH' => 'Thailand',
            'TL' => 'Timor-Leste',
            'TG' => 'Togo',
            'TK' => 'Tokelau',
            'TO' => 'Tonga',
            'TT' => 'Trinidad and Tobago',
            'TN' => 'Tunisia',
            'TR' => 'Turkey',
            'TM' => 'Turkmenistan',
            'TC' => 'Turks and Caicos Islands',
            'TV' => 'Tuvalu',
            'UG' => 'Uganda',
            'UA' => 'Ukraine',
            'AE' => 'United Arab Emirates',
            'GB' => 'United Kingdom',
            'US' => 'United States',
            'UM' => 'United States Minor Outlying Islands',
            'UY' => 'Uruguay',
            'UZ' => 'Uzbekistan',
            'VU' => 'Vanuatu',
            'VE' => 'Venezuela, Bolivarian Republic of',
            'VN' => 'Viet Nam',
            'VG' => 'Virgin Islands, British',
            'VI' => 'Virgin Islands, U.S.',
            'WF' => 'Wallis and Futuna',
            'EH' => 'Western Sahara',
            'YE' => 'Yemen',
            'ZM' => 'Zambia',
            'ZW' => 'Zimbabwe',
        ];

        return $countries;
    }
    public function start()
    {
        $uid = userid();
        if (!$uid) {
            $this->error('Please login first');
        }
        $allow = $this->ifAllowToKYC();

        if ($allow['status'] != 1) {
            $this->error($allow['message'], U('Identity/index'));
        }
        $user_info = M('User')->where(['id' => $uid])->find();

        $info = $this->getLevelInfo($user_info['usertype'], $user_info['accounttype']);

        $user_show['current_level'] = $info['current_level'];
        $user_show['next_level'] = $info['next_level'];
        $user_show['accounttype'] = $user_info['accounttype'];
        $user_show['firstname'] = $user_info['firstname'];
        $user_show['lastname'] = $user_info['lastname'];
        $user_show['truename'] = $user_info['truename'];
        $user_show['dob'] = $user_info['dob'];
        $user_show['country'] = $user_info['country'];
        $user_show['nationality'] = $user_info['country'];
        $user_show['email'] = $user_info['email'];
        $user_show['phone'] = $user_info['phone'];
        $dob='';
        if(strpos('/', $user_show['dob']) === false){
            $dob_parts = explode('-', $user_show['dob']); // Assuming the date is in 'Y-m-d' format
            $dob = $dob_parts[2] . '/' . $dob_parts[1] . '/' . $dob_parts[0]; // Re-arrange to 'd/m/Y' format
            
        }
        //var_dump([$user_show['dob'],$dob]);exit;
        $this->assign('dob', $dob);
        $this->assign('user_show', $user_show);
        $this->assign('countries', $this->countries());
        $this->assign('business_activities', $this->getBusinessActivities());
        $this->display();
    }


    public function startKyb()
    {
        $uid = userid();
        if (!$uid) {
            $this->error('Please login first');
        }
        $allow = $this->ifAllowToKYC();
        

        if ($allow['status'] != 1) {
            $this->error($allow['message']);
        }
        if (IS_POST) {
            $business_activity_id = I('post.business_activity_id', '', 'htmlspecialchars');
            $registration_country = substr(I('post.registration_country', '', 'htmlspecialchars'), 0, 2);
            $company_name = I('post.company_name', '', 'htmlspecialchars');
            $userData = array(
                'business_activity_id' => $business_activity_id,
                'registration_country' => $registration_country,
                'company_name' => $company_name,
                'email' => getEmail($uid),
                'phone' => rand(10000, 99999) . rand(100, 999) . rand(100, 999)
            );
            $existing_applicant_id=M('User')->where(['id'=>$uid])->getField('applicantid');
            if($existing_applicant_id && strlen($existing_applicant_id) == 36){
                $applicantId=$existing_applicant_id;
            }else{
                $applicantId = $this->createApplicant($userData, $uid, 'COMPANY');
            }
            
            if ($applicantId && strlen($applicantId) == 36) {
                $code = cryptString($applicantId . '_c', 'e');
                $db_save = [
                    'applicantid' => $applicantId,
                    'truename' => $company_name,
                    'country' => $registration_country,
                    'accounttype' => 2
                ];
                M('User')->where(['id' => $uid])->save($db_save);
                $this->success('Now redirecting ', U('Identity/begin', ['code' => $code]));
            } else {
                $this->error('Failed to create applicant.');
            }
        } else {
            $this->error('Invalid Request');
        }
    }

    public function startKyc()
    {
        $uid = userid();
        if (!$uid) {
            $this->error('Please login first');
        }
        $allow = $this->ifAllowToKYC();


        if ($allow['status'] != 1) {
            $this->error($allow['message']);
        }

        if (IS_POST) {
            $first_name = I('post.first_name', '', 'htmlspecialchars');
            $last_name = I('post.last_name', '', 'htmlspecialchars');
            $dob = I('post.dob', '', 'htmlspecialchars');
            $residence_country = substr(I('post.residence_country', '', 'htmlspecialchars'), 0, 2);
            $nationality = substr(I('post.nationality', '', 'htmlspecialchars'), 0, 2);
            $userData = array(
                'first_name' => $first_name,
                'last_name' => $last_name,
                'dob' => $dob, // Format: YYYY-MM-DD
                'residence_country' => $residence_country,
                'nationality' => $nationality,
                'email' => getEmail($uid),
            );

            $existing_applicant_id=M('User')->where(['id'=>$uid])->getField('applicantid');
            if($existing_applicant_id && strlen($existing_applicant_id) == 36){
                $applicantId=$existing_applicant_id;
            }else{
                $applicantId = $this->createApplicant($userData, $uid, 'PERSON');
            }
            
            if ($applicantId && strlen($applicantId) == 36) {
                $code = cryptString($applicantId . '_p', 'e');
                $db_save = [
                    'applicantid' => $applicantId,
                    'truename' => $first_name . ' ' . $last_name,
                    'country' => $nationality,
                    'firstname' => $first_name,
                    'lastname' => $last_name,
                    'accounttype' => 1,
                    'dob' => $dob
                ];
                M('User')->where(['id' => $uid])->save($db_save);
                $this->success('Now redirecting ', U('Identity/begin', ['code' => $code]));
            } else {
                $this->error('Failed to create applicant.');
            }
        } else {
            $this->error('Invalid Request');
        }
    }

    public function begin()
    {
        $uid = userid();
        if (!$uid) {
            $this->error('Please login first');
        }

        $code = I('get.code');
        $error = $formLink = false;
        $decoded = cryptString($code, 'd');
        $formid = $this->formid;
        if (strpos($decoded, '_')) {

            $applicant_id = explode('_', $decoded)[0];
            $type = explode('_', $decoded)[1];

            if ($type == 'p') {
                $formid = $this->formid;
            } elseif ($type == 'c') {
                $formid = $this->business_form;
            } else {
                $error = 'Invalid code';
            }
        } else {
            $error = 'Invalid code';
        }
        $user_info=M('User')->where(['id'=>$uid])->find();
        $level_id_to_search=$user_info['usertype'];
        
        $info = $this->getLevelInfo($level_id_to_search, $user_info['accounttype']);
		
        $current_level = $info['current_level'];
        $next_level = $info['next_level'];
        $next_form=$next_level['form_id'];
        clog('identity_getLevelInfo',[$level_id_to_search,$user_info['accounttype'], $info]);
		if (!$applicant_id) {
            $error = 'Invalid code';
        } else {
            $resp = $this->generateURL($applicant_id, $uid, $next_form);
            if (!$resp['status']) {
				clog('identity_begin_failed',[$resp,$applicant_id,$uid,$next_form]);
                $error = 'Failed:' . $resp['msg'];
            }
            $formLink = $resp['url'];
        }
        if($resp && $resp['msg'] == 'Applicant is not compatible with this form'){
            M('User')->where(['id'=>$uid])->save(['applicantid'=>'']);
        }

        $this->assign('error', $error);
        $this->assign('formLink', $formLink);
        $this->display('iframe'); // A view to display the form link
    }


    public function hook()
    {
        $code = I('get.code');
        $decrypt = cryptString($code);
        $applicantId = 'ca0b72d0057a99416a0beda98e66a3ffb465';
        $uid = 15;
        if (strpos($decrypt, '_')) {
            $info = explode('_', $decrypt);
            $applicantId = $info[0];
            $uid = $info[1];
        } else {
            //     $this->ajaxReturn(array('status' => 'error', 'message' => 'Invalid code'), 'json');
        }
        $postData = file_get_contents('php://input');

        $data = json_decode($postData, true);
        $req_data['applicant_id'] = $data['applicant_id'];
        $req_data['form_id'] = $data['form_id'];
        $req_data['verification_id'] = $data['verification_id'];
        $req_data['applicant_id'] = $data['applicant_id'];
        $req_data['verified'] = $data['verified'];
        $verifications = $data['verifications'];

        if (strtolower($applicantId) !== strtolower($req_data['applicant_id'])) {

            $this->ajaxReturn(array('status' => 'error', 'message' => 'Invalid applicant:' . $applicantId), 'json');
        }
        $kyc_status = M('User')->where(['id' => $uid])->getField('idcardauth');
        $user_passed = M('UserLevel')->where(['form_id' => $req_data['form_id']])->find();
        $level_to_update = $user_passed['level_id'];
        if ($req_data['verified'] == true) {
            M('User')->where(['id' => $uid])->save(['idcardauth' => 1, 'usertype' => $level_to_update]);
            addnotification(getEmail($uid), 'KYC Approved', 'Your KYC has been approved');
        } else {
            $plain_reason='';
            $reason = '<table border="1">';
            foreach ($verifications as $key => $verification) {
                if (strlen($verification['comment'])>0) {
                    $plain_reason.=$key.' '.$verification['comment'].' ';
                    $reason .= '<tr><td>'.$key . '</td><td>' . $verification['comment'] . '</td></tr>';
                }
            }
            $reason .= '</table>';
            if ($kyc_status != 1) {
                M('User')->where(['id' => $uid])->save(['idcardauth' => 3, 'kyc_comment' => $plain_reason]);
            }
            $retry_url=U('Identity/index');
            $subject = 'KYC Upgrade Rejected: Level Could not be updated';
            $content = 'Your KYC has been rejected.<br/> Your level could not be updated.<br/>' .
                'Reason:<br/>' . $reason.'<br/>'.
                "<a style='padding:3px;background:blue;color:#fff;' href='$retry_url' target='_blank'>Click here to retry</a>";
            addnotification(getEmail($uid),$subject,$content);
        }
        $this->ajaxReturn(['status' => 'success','message'=>'Call back data updated'], 'json');
        exit;
       
    }
    private function updateApplicant($applicantId, $updatedData)
    {
        $response = $this->sendRequest("applicants/{$applicantId}", 'PATCH', $updatedData);
        return $response;
    }

    private  function getVerificationStatus($verificationId)
    {
        $response = $this->sendRequest("verifications/{$verificationId}", 'GET', array());
        return $response;
    }

    private function verifySignature($payload, $headerSignature, $secretKey)
    {
        // Create the HMAC hash using the secret key and payload
        $calculatedSignature = hash_hmac('sha256', $payload, $secretKey);

        // Compare the calculated signature with the signature from the headers
        if (hash_equals($calculatedSignature, $headerSignature)) {
            return true; // Signatures match, data integrity verified
        } else {
            return false; // Signatures do not match, data integrity compromised
        }
    }
    private function sendRequest($endpoint, $method, $data)
    {

        $url = $this->apiurl . $endpoint;
        $headers = array(
            'Authorization: Token ' . $this->api_token,
            'Content-Type: application/json'
        );

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        if ($method == 'POST' || $method == 'PATCH') {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $result = curl_exec($ch);
        clog('Identity', [$endpoint, $method, $data, $result]);
        curl_close($ch);

        return json_decode($result, true);
    }
    private function genFormId()
    {
        $response = $this->sendRequest('forms', 'GET', []);
        if ($response && isset($response['form_id'])) {

            return $response['form_id'];
        }

        return null; // Handle failure to get or create form ID
    }
    private function getFormId()
    {
        // Check if a form ID already exists in your database
        $formId = M('Form')->where(['type' => 'DOCUMENT'])->getField('form_id');

        if ($formId) {
            return $formId; // Return existing form ID
        } else {
            // Create a new form using AMLBot API
            $data = [
                'type' => 'DOCUMENT',
                'callback_url' => $this->callback_url,
                'verification_type' => 'KYC'
            ];

            $response = $this->sendRequest('forms', 'POST', $data);
            if ($response && isset($response['form_id'])) {
                // Save the form ID in your database
                M('Form')->add(['type' => 'DOCUMENT', 'form_id' => $response['form_id']]);
                return $response['form_id'];
            }

            return null; // Handle failure to get or create form ID
        }
    }

    private function createVerification($applicantId, $formId)
    {
        $data = array(
            'applicant_id' => $applicantId,
            'form_id' => $formId, // Include the form_id
            'types' => ['DOCUMENT'],
            'verification_type' => 'KYC'
        );

        $response = $this->sendRequest('verifications', 'POST', $data);
        return $response;
    }
    public function test()
    {
        $response = $this->sendRequest('forms', 'GET', []);
        var_dump($response);
    }
    private function generateURL($applicant_id, $uid, $formid)
    {

        $data['applicant_id'] = $applicant_id;
        $data['external_applicant_id'] = $uid;
        $data['redirect_url'] = $this->callback_url . '/code/' . cryptString($uid . '_' . $applicant_id, 'e');
        $response = $this->sendRequest("forms/$formid/urls", 'POST', $data);
        
		if ($response && isset($response['form_url'])) {
            return ['status' => 1, 'url' => $response['form_url'], 'msg' => 'success'];
        } elseif ($response['errors']) {
			
            return ['status' => 0, 'url' => false, 'msg' => $response['errors'][0]['message']];
        }
		
        return false;
    }
    private function createApplicant($userData, $uid, $type = 'PERSON')
    {
        if ($type == 'PERSON') {
            $data = [
                'type' => 'PERSON',
                'first_name' => $userData['first_name'],
                'last_name' => $userData['last_name'],
                'dob' => $userData['dob'], // Format: YYYY-MM-DD
                'residence_country' => $userData['residence_country'],
                'nationality' => $userData['nationality'],
                'email' => $userData['email'],
                'external_applicant_id' => $uid
            ];
        } elseif ($type == 'COMPANY') {
            $data = [
                'type' => 'COMPANY',
                'business_activity_id' => $userData['business_activity_id'],
                'registration_country' => $userData['registration_country'],
                'email' => $userData['email'],
                'company_name' => $userData['company_name'],
                'external_applicant_id' => $uid
            ];
            if ($userData['phone']) {
                $data['phone'] = $userData['phone'];
            }
        } else {
            return false;
        }

        $response = $this->sendRequest('applicants', 'POST', $data);
        if ($response && isset($response['applicant_id'])) {
            return $response['applicant_id'];
        }

        return null;
    }

    private function ifAllowToKYC(): array
    {
        $userid = userid();
        if (!$userid) {
            return array('status' => 0, 'message' => L('PLEASE_LOGIN'));
        }
        $user = M('User')->where(array('id' => $userid))->find();
        if ($user['idcardauth'] == 1) {
            return array('status' => 1, 'message' => L('Account already verified'), 'url' => null);
        }
        if ($user['idcardauth'] == 2) {
            return array('status' => 0, 'message' => L('KYC being verified'), 'url' => null);
        }
        return array('status' => 1, 'message' => L('Complete KYC First!'), 'url' => null);
    }

    private function getBusinessActivities()
    {
        $filename = date('W') . '_business-activities';
        $content = file_get_contents(DATABASE_PATH . '/Log/' . $filename);
        //check if this content is valid 
        if ($content && strlen($content) > 100) {
            return json_decode($content, true);
        } else {
            $response = $this->sendRequest('business-activities', 'GET', []);
            $content = file_put_contents(DATABASE_PATH . '/Log/' . $filename, json_encode($response));
        }

        return $response;
    }
}
