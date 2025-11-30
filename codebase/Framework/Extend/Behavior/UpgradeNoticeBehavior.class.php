<?php
// | ThinkPHP [ WE CAN DO IT JUST THINK IT ]
// | Copyright (c) 2006-2012 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: luofei614<www.3g4k.com>
// +----------------------------------------------------------------------
defined('THINK_PATH') or exit();

/**
 * upgradeSMS notification, If there isThinkPHPThe newupgrade,orimportantofUpdate,meetingsendSMS notification you。
 * Need to useSAEofSMSservice。Please findOneSAEofapplicationOpened SMSservice。
 * Use the following steps:
 * 1In the projectConfBuild directorytags.php Configuration file ,contentSuch as下：
 * <code>
 * <?php
 * return array(
 *   'app_init' =>  array('UpgradeNotice')
 * );
 * </code>
 *
 * 2,ThisfileputIn the projectofLib/BehaviorFolder.
 *Note:SAEOnuseWhen the above two steps may be omitted
 * 3,inconfig.phpConfigure:
 *  'UPGRADE_NOTICE_ON'=>true,//OpenSMSupgraderemindFeatures
 * 'UPGRADE_NOTICE_AKEY'=>'your akey',//SAEAppliedAKEYIf theSAEUse can not fill
 * 'UPGRADE_NOTICE_SKEY'=>'your skey',//SAEAppliedSKEYIf theSAEUse can not fill
 *'UPGRADE_NOTICE_MOBILE'=>'136456789',//Accept SMSofphone number
 *'UPGRADE_NOTICE_CHECK_INTERVAL' => 604800,//Detectfrequency,unitsecond,The default isOne week
 *'UPGRADE_CURRENT_VERSION'=>'0',//upgradeRearversion ofnumber,I will tell you in a text messagefill inwhat
 *UPGRADE_NOTICE_DEBUG=>true, //debuggingdefault, Iftrue,UPGRADE_NOTICE_CHECK_INTERVALConfigurationDoes not work,Each will beversionan examination,at this timeFordebugging,debuggingcompleteRearpleaseSet upSecondaryConfigurationforfalse
 *
 */
class UpgradeNoticeBehavior extends Behavior
{
    // Behavioral parametersdefinition(Defaults) canIn the projectConfigurationincover
    protected $options = array(
        'UPGRADE_NOTICE_ON' => false, // Whether to open upgrade reminder
        'UPGRADE_NOTICE_DEBUG' => false,
        'UPGRADE_NOTICE_QUEUE' => '',//queuename, inSAESet on the platform
        'UPGRADE_NOTICE_AKEY' => '', //SAEAppliedAKEY
        'UPGRADE_NOTICE_SKEY' => '', //SAEAppliedSKEY
        'UPGRADE_NOTICE_MOBILE' => '', //Accept SMSofphone number
        'UPGRADE_CURRENT_VERSION' => '0',
        'UPGRADE_NOTICE_CHECK_INTERVAL' => 604800, //Detectfrequency,unitsecond,The default isOne week
    );
    protected $header_ = '';
    protected $httpCode_;
    protected $httpDesc_;
    protected $accesskey_;
    protected $secretkey_;

    public function run(&$params)
    {
        if (C('UPGRADE_NOTICE_ON') && (!S('think_upgrade_interval') || C('UPGRADE_NOTICE_DEBUG'))) {
            if (IS_SAE && C('UPGRADE_NOTICE_QUEUE') && !isset($_POST['think_upgrade_queque'])) {
                $queue = new SaeTaskQueue(C('UPGRADE_NOTICE_QUEUE'));
                $queue->addTask('http://' . $_SERVER['HTTP_HOST'] . __APP__, 'think_upgrade_queque=1');
                if (!$queue->push()) {
                    trace('upgraderemindqueuecarried outfailure,wrong reason:' . $queue->errmsg(), 'Upgrade notification error', 'NOTIC', true);
                }
                return;
            }
            $akey = C('UPGRADE_NOTICE_AKEY');
            $skey = C('UPGRADE_NOTICE_SKEY');
            $this->accesskey_ = $akey ?: (defined('SAE_ACCESSKEY') ? SAE_ACCESSKEY : '');
            $this->secretkey_ = $skey ?: (defined('SAE_SECRETKEY') ? SAE_SECRETKEY : '');
            $current_version = C('UPGRADE_CURRENT_VERSION');
            //Readinterface
            $info = $this->send('http://sinaclouds.sinaapp.com/thinkapi/upgrade.php?v=' . $current_version);
            if ($info['version'] != $current_version) {
                if ($this->send_sms($info['msg'])) trace($info['msg'], 'Successful upgrade notification', 'NOTIC', true); //sendupgradeSMS
            }
            S('think_upgrade_interval', true, C('UPGRADE_NOTICE_CHECK_INTERVAL'));
        }
    }

    private function send_sms($msg)
    {
        $timestamp = time();
        $url = 'http://inno.smsinter.sina.com.cn/sae_sms_service/sendsms.php'; //sendSMSofinterfaceaddress
        $content = "FetchUrl" . $url . "TimeStamp" . $timestamp . "AccessKey" . $this->accesskey_;
        $signature = (base64_encode(hash_hmac('sha256', $content, $this->secretkey_, true)));
        $headers = array(
            "FetchUrl: $url",
            "AccessKey: " . $this->accesskey_,
            "TimeStamp: " . $timestamp,
            "Signature: $signature"
        );
        $data = array(
            'mobile' => C('UPGRADE_NOTICE_MOBILE'),
            'msg' => $msg,
            'encoding' => 'UTF-8'
        );
        if (!$ret = $this->send('http://g.apibus.io', $data, $headers)) {
            return false;
        }
        if (isset($ret['ApiBusError'])) {
            trace('errno:' . $ret['ApiBusError']['errcode'] . ',errmsg:' . $ret['ApiBusError']['errdesc'], 'Upgrade notification error', 'NOTIC', true);

            return false;
        }

        return true;
    }

    private function send($url, $params = array(), $headers = array())
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        if (!empty($params)) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        }
        if (!empty($headers)) curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $txt = curl_exec($ch);
        if (curl_errno($ch)) {
            trace(curl_error($ch), 'Upgrade notification error', 'NOTIC', true);

            return false;
        }
        curl_close($ch);
        $ret = json_decode($txt, true);
        if (!$ret) {
            trace('interface[' . $url . ']Return incorrect format', 'Upgrade notification error', 'NOTIC', true);

            return false;
        }

        return $ret;
    }
}
