<?php

namespace Admin\Controller;

use Org\Util\Form;
use Think\Page;


class ConfigController extends AdminController
{

    public function index()
    {
        
        //$this->checkUpdata();
        $this->data = M('Config')->where(array('id' => 1))->find();
        $this->display();
    }

    public function Extras()
    {
        $setfile = "../extras.config.php";
        //$settingstr=file_get_contents($setfile);
        include($setfile);
        $this->assign('DEFAULT_MAILER', DEFAULT_MAILER);
        $this->display();
    }


    public function edit()
    {
        if (APP_DEMO) {
            $this->error(L('SYSTEM_IN_DEMO_MODE'));
        }
		 $postData = I('post.');
         
         $postData['web_waring']=urldecode($_POST['web_waring']); 
        //$postData['web_waring'] = html_entity_decode($postData['web_waring'], ENT_QUOTES, 'UTF-8');  
        
         
         if (M('Config')->where(array('id' => 1))->save($postData)) {
            $this->success('Changes Saved!');
        } else {
            $this->error('No changes were made!');
        }
    }



    public function cellphone()
    {
        $this->data = M('Config')->where(array('id' => 1))->find();
        $this->display();
    }

    public function cellphoneEdit()
    {
        if (APP_DEMO) {
            $this->error(L('SYSTEM_IN_DEMO_MODE'));
        }
		 $postData = I('post.');
        if (M('Config')->where(array('id' => 1))->save($postData)) {
            $this->success('Changes Saved!');
        } else {
            $this->error('No changes were made!');
        }
    }

    public function contact()
    {
        $this->data = M('Config')->where(array('id' => 1))->find();
        $this->display();
    }

    public function contactEdit()
    {
        if (APP_DEMO) {
            $this->error(L('SYSTEM_IN_DEMO_MODE'));
        }
		 $postData = I('post.');

        if (M('Config')->where(array('id' => 1))->save($postData)) {
            $this->success('Changes Saved!');
        } else {
            $this->error('No changes were made!');
        }
    }

    public function coinColdTransfer($coin = NULL)
    {
        $coldwallet_info = COLD_WALLET[strtoupper($coin)];

        if (!$coldwallet_info || substr_count($coldwallet_info, ':') != 2) {
            $this->assign('error', 'NO COLD WALLET DEFINED');
        } else {
            $this->assign('error', 0);
        }
        $info = explode(":", $coldwallet_info);

        $coldwallet_address = $info[0];
        $maxkeep = $info[1];
        $minsendrequired = $info[2];
        $this->assign('address', $coldwallet_address);
        $this->assign('maxkeep', $maxkeep);
        $this->assign('minsendrequired', $minsendrequired);
        $this->assign('coinname', $coin);
        $this->assign('coldwallet', COLD_WALLET[strtoupper($coin)]);
        $this->display();
    }

    public function upColdTransfer($coinname)
    {

        $coldwallet_info = COLD_WALLET[strtoupper($coinname)];
        if (!$coldwallet_info || substr_count($coldwallet_info, ':') != 2) {
            $this->assign('error', 'NO COLD WALLET DEFINED');
        } else {
            $this->assign('error', 0);
        }
        $info = explode(":", $coldwallet_info);

        $coldwallet_address = $info[0];
        $maxkeep = $info[1];
        $minsendrequired = $info[2];


        $tobesent = 1; //calculate balance-maxkeep
        //check if  tobesent> minsendrequired then only proceed

        $dj_username = C('coin')[$coinname]['dj_yh'];
        $dj_password = C('coin')[$coinname]['dj_mm'];
        $dj_address = C('coin')[$coinname]['dj_zj'];
        $dj_port = C('coin')[$coinname]['dj_dk'];
        $dj_decimal = C('coin')[$coinname]['cs_qk'];
        $main_address = C('coin')[$coinname]['codono_coinaddress'];


        //Bitcoin type starts qbb
        if (C('coin')[$coinname]['type'] == 'qbb') {

            $CoinClient = CoinClient($dj_username, $dj_password, $dj_address, $dj_port, 5, array(), 1);
            $json = $CoinClient->getinfo();

            if (!isset($json['version']) || !$json['version']) {
                $this->error('System can not connect with ' . $coinname . ' node');
            }

            $Coin = M('Coin')->where(array('name' => $coinname))->find();
            $daemon_balance = $CoinClient->getbalance();
            $tobesent = $daemon_balance - $maxkeep;
            if ($tobesent < $minsendrequired) {
                $this->error('You have ' . $daemon_balance . $coinname . ' but you want to send ' . $daemon_balance . ' minimum hotwallet balance should be ' . $minsendrequired);
            }

            $contract=C('coin')[$coinname]['contract'];
                if($contract){
                    $sendrs = $CoinClient->token('send',$contract,$coldwallet_address, (double)$tobesent);     
                }else{
                    $sendrs = $CoinClient->sendtoaddress($coldwallet_address, (double)$tobesent);
                }

            

            if ($sendrs) {
                $flag = 1;
                $arr = json_decode($sendrs, true);

                if (isset($arr['status']) && ($arr['status'] == 0)) {
                    $flag = 0;
                }
            } else {
                $flag = 0;
            }

            if (!$flag) {
                $this->error('wallet server Withdraw currency failure!');
            } else {
                $this->success('Transfer success!' . $sendrs);
            }
        } else {
            $this->error('Coin Type is not compatible for Cold wallet transfer!');
        }

    }

    public function coin($name = NULL, $field = NULL, $status = NULL)
    {
        $this->page_title="Coin Management";
        $where = array();

        if ($field && $name) {
            if ($field == 'username') {
                $where['userid'] = M('User')->where(array('username' => $name))->getField('id');
            } else {
                $where[$field] = $name;
            }
        }

        if ($status) {
            $where['status'] = $status - 1;
        }

        $count = M('Coin')->where($where)->count();
        $Page = new Page($count, 15);
        $show = $Page->show();
        $list = M('Coin')->where($where)->order('id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->display();
    }

    public function coinEdit($id = NULL)
    {
     //   $coin_types = array("rgb" => "ICO Coin [No wallet]", "qbb", "xrp", "tron", "blockio", "cryptonote", "coinpay", "waves");
        $coin_types=['rgb'=>'ICO Coin [No wallet]','rmb'=>'Fiat Coin [USD,EUR,GBP]','qbb'=>'Wallet Coin [Bitcoin]','esmart'=>'Esmart EVM [ETH,BNB,FTM,MATIC,ONE,AND ALL EVM]',
        'substrate'=>'Subtrate Network [PolkaDot like]',
        'tron'=>'Tron Network & TRC20',    
        'cryptoapis'=>'CryptoApis',
        'xrp'=>'XRP Chain',
        'offline'=>'Offline Coin',
        'blockio'=>'Block.io',
        'coinpay'=>'CoinPayments.net',
        'waves'=>'WavesPlatform',
        'cryptonote'=>'CryptoNote[Monero]',
        'blockgum'=>'Blockgum EVM Wallets'
    ];
    $this->assign('coin_types',$coin_types);
        if (empty($_POST)) {
            if (empty($id)) {
                $this->data = array();
            } else {
                $the_data = M('Coin')->where(array('id' => trim($_GET['id'])))->find();
                if ($the_data['type'] == 'esmart' || $the_data['type'] == 'tron' || $the_data['type'] == 'xrp'|| $the_data['type'] == 'cryptoapis' || $the_data['type'] == 'substrate') {
                    $the_data['dj_mm'] = cryptString($the_data['dj_mm'], 'd');
                }
                $this->data = $the_data;
            }

            $codono_getCoreConfig = codono_getCoreConfig();
            if (!$codono_getCoreConfig) {
                $this->error('Incorrect Core Config');
            }

            $this->assign("codono_opencoin", $codono_getCoreConfig['codono_opencoin']);


            $this->display();
        } else {
            if (APP_DEMO) {
                $this->error(L('SYSTEM_IN_DEMO_MODE'));
            }

            $_POST['fee_bili'] = floatval($_POST['fee_bili']);

            $this->validatePercentage($_POST['fee_bili'], 'Fee Percentage');

            $_POST['zr_zs'] = floatval($_POST['zr_zs']);

            $this->validatePercentage($_POST['zr_zs'], 'Deposit Bonus');

            $_POST['zr_dz'] = intval($_POST['zr_dz']);
            $_POST['zc_fee'] = floatval($_POST['zc_fee']);


            $this->validatePercentage($_POST['zc_Fee'], 'Withdrawal Fees');

            if (!check($_POST['zc_flat_fee'], 'double') && $_POST['zc_flat_fee']) {
                $this->error('Invalid Withdrawal Flat fees, Please enter correct amount!');
            }
            $_POST['zc_flat_fee'] = ($_POST['zc_flat_fee']) ? $_POST['zc_flat_fee'] : 0;

            if ($_POST['zc_user']) {
                if (!check($_POST['zc_user'], 'dw')) {
                    $this->error('Fee User format is not correct!');
                }

                $ZcUser = M('User')->where(array('id' => $_POST['zc_user']))->find();

                if (!$ZcUser) {
                    $this->error('There is no such user account generated using this system');
                }
            }


            $_POST['zc_min'] = (double)($_POST['zc_min']);
            $_POST['zc_max'] = (double)($_POST['zc_max']);
            if (strlen($_POST['dj_mm']) < 1) {
                unset($_POST['dj_mm']);
            } else {
                if ( $_POST['type'] == 'esmart' || $_POST['type'] == 'tron' || $_POST['type'] == 'xrp' || $_POST['type'] == 'cryptoapis'|| $_POST['type'] == 'substrate') {
                    $_POST['dj_mm'] = cryptString($_POST['dj_mm']);
                }
            }
            if (isset($_POST['symbol'])) {
                $_POST['symbol'] = strtolower($_POST['symbol']);
            }
            if (!isset($_POST['endtime'])) {
                $_POST['endtime'] = time();
            }

            if ($_POST['id']) {
                $where['id'] = intval($_POST['id']);
                $save = $_POST;
                unset($save['id']);

                $rs = M('Coin')->where($where)->save($save);
            } else {
                if (!check($_POST['name'], 'n')) {
                    $this->error('Lowercase Letters!');
                }

                $_POST['name'] = strtolower($_POST['name']);

                if (!check($_POST['name'], 'coin')) {
                    $this->error($_POST['name'] . ' Currency name format is not correct!');
                }

                if (M('Coin')->where(array('name' => $_POST['name']))->find()) {
                    $this->error('Currency exist!');
                }
                $Model = new \Think\Model();
                $rea = $Model->execute('ALTER TABLE  `codono_user_coin` ADD COLUMN IF NOT EXISTS `' . I('post.name') . '` DECIMAL(20,8) UNSIGNED  NULL DEFAULT "0.00000000"');
                $reb = M()->execute('ALTER TABLE  `codono_user_coin` ADD COLUMN IF NOT EXISTS  `' . $_POST['name'] . 'd` DECIMAL(20,8) UNSIGNED NULL DEFAULT "0.00000000" ');
                if(in_array($_POST['name'] ,['xmr','iota'])){
                    $rs_long= M()->execute('ALTER TABLE  `codono_user_coin` ADD COLUMN IF NOT EXISTS  `' . $_POST['name'] . 'b` VARCHAR(95) DEFAULT NULL ');
                }else{
                    $rs_long= M()->execute('ALTER TABLE  `codono_user_coin` ADD COLUMN IF NOT EXISTS  `' . $_POST['name'] . 'b` VARCHAR(42) DEFAULT NULL ');
                }
                $rs = M('Coin')->add($_POST);
            }

            if ($rs) {
                $this->success(L('SUCCESSFULLY_DONE'));
            } else {

                $this->error(L('No Changes'));
            }
        }
    }
    private function validatePercentage($value, $fieldname) {
        if ($value && (($value < 0 ) || ($value > 100))) {
            $this->error("The $fieldname must be between 0 and 100!");
        }
    }
    public function coinStatus()
    {
        if (APP_DEMO) {
            $this->error(L('SYSTEM_IN_DEMO_MODE'));
        }

        if (IS_POST) {
            $id = array();
            $id = implode(',', $_POST['id']);
        } else {
            $id = $_GET['id'];
        }

        if (empty($id)) {
            $this->error('please choose coin to be operated!');
        }

        $where['id'] = array('in', $id);
        $method = $_GET['type'];

        switch (strtolower($method)) {
            case 'forbid':
                $data = array('status' => 0);
                break;

            case 'resume':
                $data = array('status' => 1);
                break;

            case 'delete':
                $rs = M('Coin')->where($where)->select();

                foreach ($rs as $k => $v) {
                    $rs[] = M()->execute('ALTER TABLE  `codono_user_coin` DROP COLUMN IF EXISTS ' . $v['name']);
                    $rs[] = M()->execute('ALTER TABLE  `codono_user_coin` DROP COLUMN IF EXISTS ' . $v['name'] . 'd');
                    $rs[] = M()->execute('ALTER TABLE  `codono_user_coin` DROP COLUMN IF EXISTS ' . $v['name'] . 'b');

                    $rs[] = M()->execute('ALTER TABLE  `codono_shop_coin` DROP COLUMN IF EXISTS ' . $v['name']);
                }

                if (M('Coin')->where($where)->delete()) {
                    $this->success(L('SUCCESSFULLY_DONE'));
                } else {
                    $this->error(L('OPERATION_FAILED'));
                }

                break;

            default:
                $this->error('Invalid parameters');
        }

        if (M('Coin')->where($where)->save($data)) {
            $this->success(L('SUCCESSFULLY_DONE'));
        } else {
            $this->error(L('OPERATION_FAILED'));
        }
    }

    public function coinInfo($coin)
    {
        
        if(!isValidCoin($coin)){

            echo "Coin is disabled";$this->display();exit;
        }
        $dj_username = C('coin')[$coin]['dj_yh'];
        $dj_password = C('coin')[$coin]['dj_mm'];
        $dj_address = C('coin')[$coin]['dj_zj'];
        $dj_port = C('coin')[$coin]['dj_dk'];
        $dj_decimal = C('coin')[$coin]['cs_qk'];
        $main_address = C('coin')[$coin]['codono_coinaddress'];
        $xrpData = C('coin')['xrp'];
        if (C('coin')[$coin]['type'] == 'xrp') {
            $xrpClient = XrpClient($xrpData['dj_zj'], $xrpData['dj_dk'], $xrpData['codono_coinaddress'], $xrpData['dj_mm']);
            $xrpInfo = $xrpClient->accountInfo();
            if (strtolower($xrpInfo['result']['status']) == 'success') {
                foreach ($xrpInfo['result']['account_data'] as $key => $value) {
                    if ($key == 'Balance') {
                        $value = $value / 1000000;
                    }
                    $info['b'][$key] = $value;

                }

                $info['b']['connection'] = $xrpInfo['result']['status'];
                $info['b']['name'] = $coin;
                //$info['b']['blockchain'] = $xrpInfo['result']['account_data']['Balance']/1000000;
                $info['b']['version'] = '1.0';
                $info['b']['address'] = $xrpData['codono_coinaddress'];
                //$info['b']['num'] = M('UserCoin')->sum($coin) + M('UserCoin')->sum($coin . 'd');

            } else {
                $this->error('Failed to get wallet data!');
            }

        }
        if (C('coin')[$coin]['type'] == 'waves') {
            $waves = WavesClient($dj_username, $dj_password, $dj_address, $dj_port, $dj_decimal, 5, array(), 1);
            $addresses = $waves->GetAddresses();
            $addr_array = json_decode($addresses);
            $waves_coin = strtoupper($coin);

            $info['b']['balance'] = 'Main Balance:' . $waves->Balance($main_address, $dj_username);
            $info['b']['paytxfee'] = 'Waves takes 0.001/tx It would be double in your case since you move from customer to main account.';
            $info['b']['connection'] = $addr_array['result'][$waves_coin]['coin_status'];

        }
        if (C('coin')[$coin]['type'] == 'coinpay') {
            $cps_api = CoinPay($dj_username, $dj_password, $dj_address, $dj_port, 5, array(), 1);
            $json = ($cps_api->GetAllCoinBalances());
            $coinpay_coin = strtoupper($coin);

            $info['b']['balance'] = $json['result'][$coinpay_coin]['balancef'];
            $info['b']['paytxfee'] = 'Please check coinpayments.net for fees';
            $info['b']['connection'] = $json['result'][$coinpay_coin]['coin_status'];

        }
        if (C('coin')[$coin]['type'] == 'blockio') {
            $block_io = BlockIO($dj_username, $dj_password, $dj_address, $dj_port, 5, array(), 1);
            $json = $block_io->get_balance();
            if (!isset($json->status) || $json->status != 'success') {
                $this->error('Wallet Docking failed!' . $coin);
            }
            $block_io->get_my_addresses();

            $info['b']['balance'] = 'Please use Block.io for more information';
            $info['b']['paytxfee'] = 'Please use Block.io for more information';
            $info['b']['connection'] = $json->status;
        }
        if (C('coin')[$coin]['type'] == 'cryptonote') {
            $cryptonote = CryptoNote($dj_address, $dj_port);

            $open_wallet = $cryptonote->open_wallet($dj_username, $dj_password);

            $json = json_decode($cryptonote->get_height());

            if (!isset($json->height) || $json->error != 0) {
                $status = 1;
                $this->error('Wallet Docking failed!' . $coin);
            }

            $all_info = $cryptonote->getAddress();

            $bal_info = json_decode($cryptonote->getBalance());

            $cryptonote_balance = $cryptonote->deAmount($bal_info->available_balance);
            //$info['b']['status']=$cryptonote->getStatus();
            $info['b']['balance'] = $cryptonote_balance;
            $info['b']['paytxfee'] = 'Please read documentation';
            $info['b']['connection'] = 'Block Height at ' . $json->height;
        }
        if (C('coin')[$coin]['type'] == 'qbb') {

            $CoinClient = CoinClient($dj_username, $dj_password, $dj_address, $dj_port);
            /*
            if (!$CoinClient) {
                   $this->error('Wallet Docking failed!'.$coin);
            }
            */
            $info['b'] = $CoinClient->getinfo();

            $info['wallet'] = $CoinClient->getwalletinfo();
            $info['blockchain'] = $CoinClient->getblockchaininfo();
            $info['txs'] = isset($CoinClient->listtransactions()['count']) ? $CoinClient->listtransactions()['count'] : 0;
            $info['b']['connection'] = isset($info['b']['version']) ? 1 : 'Failed';

        }


        if (C('coin')[$coin]['type'] == 'esmart') {
            $esmart_config = array(
                'host' => C('coin')[$coin]['dj_zj'],
                'port' => C('coin')[$coin]['dj_dk'],
                'coinbase' => C('coin')[$coin]['codono_coinaddress'],
                'password' => cryptString(C('coin')[$coin]['dj_mm'], 'd'),
                'contract' => C('coin')[$coin]['dj_yh'],
                'rpc_type' => C('coin')[$coin]['rpc_type'],
                'public_rpc' => C('coin')[$coin]['public_rpc'],
            );
            $esmart = Esmart($esmart_config);

            $info['b'] = $esmart->eth_accounts();
            if (!$esmart) {
                $this->error('Wallet Docking failed!' . $coin);
            }
        }
        if (C('coin')[$coin]['type'] == 'tron') {
            $info['b']['msg'] = "Open file ConfigController.class.php  and Edit Line" . __LINE__;
        }


        $info['num'] = M('UserCoin')->sum($coin) + M('UserCoin')->sum($coin . 'd');
        $info['coin'] = $coin;
        $this->assign('data', $info);
        $this->display();
    }

    public function coinUser($coin)
    {
        $dj_username = C('coin')[$coin]['dj_yh'];
        $dj_password = C('coin')[$coin]['dj_mm'];
        $dj_address = C('coin')[$coin]['dj_zj'];
        $dj_port = C('coin')[$coin]['dj_dk'];
        $dj_decimal = C('coin')[$coin]['cs_qk'];
        if (C('coin')[$coin]['type'] == 'waves') {
            $waves = WavesClient($dj_username, $dj_password, $dj_address, $dj_port, $dj_decimal, 5, array(), 1);
            $addresses = $waves->GetAddresses();
            $addr_array = json_decode($addresses);
            echo "<br/>Validating Addreses<br/>";
            foreach ($addr_array as $addr) {
                echo $waves->ValidateAddress($addr) . "<br/>";
            }
            echo "<br/>Waves Balances<br/>";
            foreach ($addr_array as $addr) {
                echo $waves->Balance($addr, $coin) . "<br/>";
            }


            die("Raw balance map");
        }

        if (C('coin')[$coin]['type'] == 'coinpay') {
            die("This feature is not required in case of coinpayments");
        }
        if (C('coin')[$coin]['type'] == 'cryptonote') {
            $cryptonote = CryptoNote($dj_address, $dj_port);
            $open_wallet = $cryptonote->open_wallet($dj_username, $dj_password);
            $json = json_decode($cryptonote->get_height());
            if (!isset($json->height) || $json->error != 0) {
                $this->error('Wallet System is currently offline 2!');
            }
            $address = $cryptonote->getAddress();
            $coinb = $coin . 'b';
            $coin_tag = $coin . "_tag";
            $list = M()->query("SELECT id ,userid as userid, concat($coinb,' <br/>dest_tag:',$coin_tag) as addr, $coin as xnb ,$coin as num  FROM `codono_user_coin` WHERE $coinb IS NOT NULL");
            //var_dump($list);

//exit;

        }
        if (C('coin')[$coin]['type'] == 'blockio') {
            $block_io = BlockIO($dj_username, $dj_password, $dj_address, $dj_port, 5, array(), 1);
            $json = $block_io->get_balance();
            if (!isset($json->status) || $json->status != 'success') {
                $this->error('Wallet Docking failed!' . $coin);
            }
            $addrlist = $block_io->get_my_addresses();
            $arr = $addrlist->data->addresses;
            foreach ($arr as $k) {
                $str = '';
                $addr = $k->address;//$CoinClient->getaddressesbyaccount($k);


                $str .= $addr . '<br>';
                $userid = M('User')->where(array('username' => $k->label))->getField('id');

                if ($userid > 0) {
                    $list[$userid]['num'] = $k->available_balance;
                    $list[$userid]['addr'] = $str;
                    $user_coin = M('UserCoin')->where(array('userid' => $userid))->find();
                    $list[$userid]['xnb'] = $user_coin[$coin];
                    $list[$userid]['xnbd'] = $user_coin[$coin . 'd'];
                    $list[$userid]['zj'] = $list[$k]['xnb'] + $list[$k]['xnbd'];
                    $list[$userid]['xnbb'] = $user_coin[$coin . 'b'];
                }
                unset($str);
            }
        }
		if (C('coin')[$coin]['type'] == 'cryptoapis') {
            die("Please use CryptoApis.io to check your accounts");
        }
        if (C('coin')[$coin]['type'] == 'esmart') {
            die("Please use $coin Node to list your accounts");
        }
        if (C('coin')[$coin]['type'] == 'tron') {
            die("Please use $coin Node to list your accounts");
        }
        if (C('coin')[$coin]['type'] == 'qbb') {
            $CoinClient = CoinClient($dj_username, $dj_password, $dj_address, $dj_port);

            if (!$CoinClient) {
                $this->error('Wallet Docking failed!');
            }
            $ags = $CoinClient->listaddressgroupings();
            $addressGroup = $ags[0];
            $arr = $CoinClient->listlabels();
            $index = 0;

            foreach ($addressGroup as $key) {

                if ($key) {

                    if (!$key[1]) {
                        $v = 0;
                    }

                    //$list[$key]['label'] = $label;

                    $str = '';
                    //$resp_addr = $CoinClient->getaddressesbylabel($label);

                    $str = $key[0];

                    $list[$index]['addr'] = $str;
                    //$userid = M('User')->where(array('username' => $k))->getField('id');
                    $user_coin = M('UserCoin')->where(array($coin . 'b' => $str))->find();
                    $list[$index]['balance'] = $key[1];
                    $list[$index]['label'] = $key[2] ? $key[2] : username($user_coin['userid']);
                    $list[$index]['userid'] = $user_coin['userid'];
                    $list[$index]['xnb'] = $user_coin[$coin];
                    $list[$index]['xnbd'] = $user_coin[$coin . 'd'];
                    $list[$index]['zj'] = $list[$k]['xnb'] + $list[$k]['xnbd'];
                    $list[$index]['xnbb'] = $user_coin[$coin . 'b'];
                    unset($str);
                    $index++;
                }
            }
            /*
            foreach ($arr as $key => $label) {

                if ($key) {
                    if (!$v) {
                        $v = 0;
                    }

                    $list[$key]['label'] = $label;

                    $str = '';
                    $resp_addr = $CoinClient->getaddressesbylabel($label);

                    foreach ($resp_addr as $address => $vv) {
    //                    $str .= $address . '<br>';
                        $str=$address;
                    }

                    $list[$key]['addr'] = $str;
                    //$userid = M('User')->where(array('username' => $k))->getField('id');
                    $user_coin = M('UserCoin')->where(array($coin.'b' => $str))->find();
                    $list[$key]['userid'] = $user_coin['userid'];
                    $list[$key]['xnb'] = $user_coin[$coin];
                    $list[$key]['xnbd'] = $user_coin[$coin . 'd'];
                    $list[$key]['zj'] = $list[$k]['xnb'] + $list[$k]['xnbd'];
                    $list[$key]['xnbb'] = $user_coin[$coin . 'b'];
                    unset($str);
                }
            }*/
        }


        $this->assign('list', $list);
        $this->display();
    }

    public function coinEmpty($coin)
    {

        if (!C('coin')[$coin]) {
            $this->error(L('INCORRECT_REQ'));
        }

        $info = M()->execute('UPDATE `codono_user_coin` SET `' . trim($coin) . 'b`=\'\' ;');

        if ($info) {
            $this->success(L('SUCCESSFULLY_DONE'));
        } else {
            $this->error(L('OPERATION_FAILED'));
        }
    }
    public function image()
    {
        $upload = new \Think\Upload();
        $upload->maxSize = 3145728;
        $upload->exts = array('jpg', 'gif', 'png', 'jpeg');
        $upload->rootPath = './Upload/public/';
        $upload->autoSub = false;
        $info = $upload->upload();

        foreach ($info as $k => $v) {
            $path = $v['savepath'] . $v['savename'];
            echo $path;
            exit();
        }
    }
    public function coinImage()
    {
        $upload = new \Think\Upload();
        $upload->maxSize = 3145728;
        $upload->exts = array('jpg', 'gif', 'png', 'jpeg');
        $upload->rootPath = './Upload/coin/';
        $upload->autoSub = false;
        $info = $upload->upload();

        foreach ($info as $k => $v) {
            $path = $v['savepath'] . $v['savename'];
            echo $path;
            exit();
        }
    }

    public function text($name = NULL, $field = NULL, $status = NULL)
    {
        $where = array();

        if ($field && $name) {
            if ($field == 'username') {
                $where['userid'] = M('User')->where(array('username' => $name))->getField('id');
            } else {
                $where[$field] = $name;
            }
        }

        if ($status) {
            $where['status'] = $status - 1;
        }

        $count = M('Text')->where($where)->count();
        $Page = new Page($count, 15);
        $show = $Page->show();
        $list = M('Text')->where($where)->order('id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();

        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->display();
    }

    public function textEdit($id = NULL)
    {
        if (empty($_POST)) {
            if ($id) {
                $this->data = M('Text')->where(array('id' => trim($id)))->find();
            } else {
                $this->data = null;
            }

            $this->display();
        } else {
            if (APP_DEMO) {
                $this->error(L('SYSTEM_IN_DEMO_MODE'));
            }

            if ($_POST['id']) {
                $rs = M('Text')->save($_POST);
            } else {
                $_POST['adminid'] = session('admin_id');
                $rs = M('Text')->add($_POST);
            }

            if ($rs) {
                $this->success(L('SAVED_SUCCESSFULLY'));
            } else {
                $this->error(L('COULD_NOT_SAVE'));
            }
        }
    }

    public function misc()
    {
        $this->data = M('Config')->where(array('id' => 1))->find();

        $this->display();
    }

    public function miscEdit()
    {
        if (APP_DEMO) {
            $this->error(L('SYSTEM_IN_DEMO_MODE'));
        }

        if (M('Config')->where(array('id' => 1))->save($_POST)) {
            $this->success('Changes Saved!');
        } else {
            $this->error('No changes were made!');
        }
    }
    
    
    public function footer($name = NULL, $field = NULL, $status = NULL)
    {
        $where = array();

        if ($field && $name) {
            if ($field == 'username') {
                $where['userid'] = M('User')->where(array('username' => $name))->getField('id');
            } else if ($field == 'title') {
                $where['title'] = array('like', '%' . $name . '%');
            } else {
                $where[$field] = $name;
            }
        }

        if ($status) {
            $where['status'] = $status - 1;
        } else {
            $where['status'] = array('neq', -1);
        }


        $count = M('Footer')->where($where)->count();
        $Page = new Page($count, 50);
        $show = $Page->show();
        $all_menu = M('Footer')->getField('id,title');
        $list = M('Footer')->where($where)->order('id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();
        if ($list) {
            foreach ($list as &$key) {
                if ($key['pid']) {
                    $key['up_title'] = $all_menu[$key['pid']];
                }
            }

            $this->assign('list', $list);
        }
        $this->assign('page', $show);
        $this->display();
    }

    public function footerAdd()
    {
        if (IS_POST) {
            $_POST['addtime'] = time();
            if (APP_DEMO) {
                $this->error(L('SYSTEM_IN_DEMO_MODE'));
            }

            $Menu = D('Footer');
            $data = $Menu->create();

            if ($data) {
                $id = $Menu->add();

                if ($id) {

                    $this->success('Added successfully');
                } else {
                    $this->error('Add failed');
                }
            } else {
                $this->error($Menu->getError());
            }
        } else {
            $this->assign('info', array('pid' => I('pid')));
            $menus = M('Footer')->field(true)->select();
            $menus = D('Tree')->toFormatTree($menus);
            $menus = array_merge(array(
                array('id' => 0, 'title_show' => 'Top Menu')
            ), $menus);
            $this->assign('Menus', $menus);
            $this->meta_title = 'New menu';
            $this->display('navadd');
        }
    }

    public function footerEdit($id = NULL)
    {

        if (empty($_POST)) {
            if ($id) {
                $info = array();
                $info = M('Footer')->field(true)->find($id);
                $menus = M('Footer')->field(true)->select();
                $menus = D('Tree')->toFormatTree($menus);
                $menus = array_merge(array(
                    array('id' => 0, 'title_show' => 'Top Menu')
                ), $menus);
                $this->assign('Menus', $menus);
                $this->assign('info', $info);
                if (false === $info) {
                    $this->error('Obtaining background information about the error menu');
                }

                $this->assign('data', $info);
                $this->meta_title = 'Edit menu background';
            } else {
                $this->data = null;
            }

            $this->display();
        } else {
            if (APP_DEMO) {
                $this->error(L('SYSTEM_IN_DEMO_MODE'));
            }
            if ($_POST['title'] && !$_POST['name']) {
                $_POST['name'] = $_POST['title'];
            }
            if ($_POST['id']) {

                $rs = M('Footer')->save($_POST);
              
            } else {

                $_POST['addtime'] = time();
                $rs = M('Footer')->add($_POST);
            }

            if ($rs) {
                $this->success(L('SAVED_SUCCESSFULLY'));
            } else {
                $this->error(L('COULD_NOT_SAVE'));
            }
        }
    }

    public function footerStatus($id = NULL, $type = NULL, $model = 'Footer')
    {
		A('User')->sub_status($id,$type,$model);
    }
    public function navigation($name = NULL, $field = NULL, $status = NULL)
    {
        $where = array();

        if ($field && $name) {
            if ($field == 'username') {
                $where['userid'] = M('User')->where(array('username' => $name))->getField('id');
            } else if ($field == 'title') {
                $where['title'] = array('like', '%' . $name . '%');
            } else {
                $where[$field] = $name;
            }
        }

        if ($status) {
            $where['status'] = $status - 1;
        } else {
            $where['status'] = array('neq', -1);
        }


        $count = M('Navigation')->where($where)->count();
        $Page = new Page($count, 50);
        $show = $Page->show();
        $all_menu = M('Navigation')->getField('id,title');
        $list = M('Navigation')->where($where)->order('id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();
        if ($list) {
            foreach ($list as &$key) {
                if ($key['pid']) {
                    $key['up_title'] = $all_menu[$key['pid']];
                }
            }

            $this->assign('list', $list);
        }
        $this->assign('page', $show);
        $this->display();
    }

    public function navadd()
    {
        if (IS_POST) {
            $_POST['addtime'] = time();
            if (APP_DEMO) {
                $this->error(L('SYSTEM_IN_DEMO_MODE'));
            }

            $Menu = D('Navigation');
            $data = $Menu->create();

            if ($data) {
                $id = $Menu->add();

                if ($id) {

                    $this->success('Added successfully');
                } else {
                    $this->error('Add failed');
                }
            } else {
                $this->error($Menu->getError());
            }
        } else {
            $this->assign('info', array('pid' => I('pid')));
            $menus = M('Navigation')->field(true)->select();
            $menus = D('Tree')->toFormatTree($menus);
            $menus = array_merge(array(
                array('id' => 0, 'title_show' => 'Top Menu')
            ), $menus);
            $this->assign('Menus', $menus);
            $this->meta_title = 'New menu';
            $this->display('navadd');
        }
    }

    public function navigationEdit($id = NULL)
    {

        if (empty($_POST)) {
            if ($id) {
                $info = array();
                $info = M('Navigation')->field(true)->find($id);
                $menus = M('Navigation')->field(true)->select();
                $menus = D('Tree')->toFormatTree($menus);
                $menus = array_merge(array(
                    array('id' => 0, 'title_show' => 'Top Menu')
                ), $menus);
                $this->assign('Menus', $menus);
                $this->assign('info', $info);
                if (false === $info) {
                    $this->error('Obtaining background information about the error menu');
                }

                $this->assign('data', $info);
                $this->meta_title = 'Edit menu background';
            } else {
                $this->data = null;
            }

            $this->display();
        } else {
            if (APP_DEMO) {
                $this->error(L('SYSTEM_IN_DEMO_MODE'));
            }
            if ($_POST['title'] && !$_POST['name']) {
                $_POST['name'] = $_POST['title'];
            }
            if ($_POST['id']) {

                $rs = M('Navigation')->save($_POST);
              
            } else {

                $_POST['addtime'] = time();
                $rs = M('Navigation')->add($_POST);
            }

            if ($rs) {
                $this->success(L('SAVED_SUCCESSFULLY'));
            } else {
                $this->error(L('COULD_NOT_SAVE'));
            }
        }
    }

    public function navigationStatus($id = NULL, $type = NULL, $model = 'Navigation')
    {
		A('User')->sub_status($id,$type,$model);
    }

    public function checkUpdata()
    {

        if (!S(MODULE_NAME . CONTROLLER_NAME . 'checkUpdata')) {
            $DbFields = M('Config')->getDbFields();

            if (!in_array('footer_logo', $DbFields)) {
                M()->execute('ALTER TABLE `codono_config` ADD COLUMN `footer_logo` VARCHAR(200)  NOT NULL   COMMENT \' \' AFTER `id`;');
            }

            if (in_array('mycz_invit_3', $DbFields)) {
                M()->execute('ALTER TABLE `codono_config` DROP COLUMN `mycz_invit_3`;');
            }

            if (in_array('mycz_invit_2', $DbFields)) {
                M()->execute('ALTER TABLE `codono_config` DROP COLUMN `mycz_invit_2`;');
            }

            if (in_array('mycz_invit_1', $DbFields)) {
                M()->execute('ALTER TABLE `codono_config` DROP COLUMN `mycz_invit_1`;');
            }

            if (in_array('mycz_invit_coin', $DbFields)) {
                M()->execute('ALTER TABLE `codono_config` DROP COLUMN `mycz_invit_coin`;');
            }

            if (in_array('mycz_fee', $DbFields)) {
                M()->execute('ALTER TABLE `codono_config` DROP COLUMN `mycz_fee`;');
            }

            if (in_array('mycz_min', $DbFields)) {
                M()->execute('ALTER TABLE `codono_config` DROP COLUMN `mycz_min`;');
            }

            if (in_array('mycz_max', $DbFields)) {
                M()->execute('ALTER TABLE `codono_config` DROP COLUMN `mycz_max`;');
            }

            if (in_array('mycz_text_index', $DbFields)) {
                M()->execute('ALTER TABLE `codono_config` DROP COLUMN `mycz_text_index`;');
            }

            if (in_array('mycz_text_log', $DbFields)) {
                M()->execute('ALTER TABLE `codono_config` DROP COLUMN `mycz_text_log`;');
            }

            if (in_array('mytx_text_index', $DbFields)) {
                M()->execute('ALTER TABLE `codono_config` DROP COLUMN `mytx_text_index`;');
            }

            if (in_array('mytx_text_log', $DbFields)) {
                M()->execute('ALTER TABLE `codono_config` DROP COLUMN `mytx_text_log`;');
            }

            if (in_array('trade_text_index', $DbFields)) {
                M()->execute('ALTER TABLE `codono_config` DROP COLUMN `trade_text_index`;');
            }

            if (in_array('trade_text_entrust', $DbFields)) {
                M()->execute('ALTER TABLE `codono_config` DROP COLUMN `trade_text_entrust`;');
            }

            if (in_array('issue_text_index', $DbFields)) {
                M()->execute('ALTER TABLE `codono_config` DROP COLUMN `issue_text_index`;');
            }

            if (in_array('issue_text_log', $DbFields)) {
                M()->execute('ALTER TABLE `codono_config` DROP COLUMN `issue_text_log`;');
            }

            if (in_array('issue_text_plan', $DbFields)) {
                M()->execute('ALTER TABLE `codono_config` DROP COLUMN `issue_text_plan`;');
            }

            if (in_array('invit_text_index', $DbFields)) {
                M()->execute('ALTER TABLE `codono_config` DROP COLUMN `invit_text_index`;');
            }

            if (in_array('invit_text_award', $DbFields)) {
                M()->execute('ALTER TABLE `codono_config` DROP COLUMN `invit_text_award`;');
            }

            $tables = M()->query('show tables');
            $tableMap = array();

            foreach ($tables as $table) {
                $tableMap[reset($table)] = 1;
            }

            if (!isset($tableMap['codono_navigation'])) {
                M()->execute("\r\n" . '                    CREATE TABLE `codono_navigation` (' . "\r\n" . '                        `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT \'Incrementid\',' . "\r\n" . '                        `name` VARCHAR(255) NOT NULL COMMENT \'name\',' . "\r\n" . '                          `title` VARCHAR(255) NOT NULL COMMENT \'name\',' . "\r\n" . '                        `url` VARCHAR(255) NOT NULL COMMENT \'url\',' . "\r\n" . '                        `sort` INT(11) UNSIGNED NOT NULL COMMENT \'Sequence\',' . "\r\n" . '                        `addtime` INT(11) UNSIGNED NOT NULL COMMENT \'add time\',' . "\r\n" . '                        `endtime` INT(11) UNSIGNED NOT NULL COMMENT \'Edit time\',' . "\r\n" . '                        `status` TINYINT(4)  NOT NULL COMMENT \'status\',' . "\r\n" . '                        PRIMARY KEY (`id`)' . "\r\n\r\n" . '                  )' . "\r\n" . 'COLLATE=\'gbk_chinese_ci\'' . "\r\n" . 'ENGINE=MyISAM' . "\r\n" . 'AUTO_INCREMENT=1' . "\r\n" . ';' . "\r\n\r\n\r\n" . 'INSERT INTO `codono_navigation` (`name`,`title`, `url`, `sort`, `status`) VALUES (\'finance\',\'Financial Center\', \'Finance/index\', 1, 1);' . "\r\n" . 'INSERT INTO `codono_navigation` (`name`,`title`, `url`, `sort`, `status`) VALUES (\'user\',\'Security center\', \'User/index\', 2, 1);' . "\r\n" . 'INSERT INTO `codono_navigation` (`name`, `title`,`url`, `sort`, `status`) VALUES (\'game\',\'application Center\', \'Game/index\', 3, 1);' . "\r\n" . 'INSERT INTO `codono_navigation` (`name`, `title`,`url`, `sort`, `status`) VALUES (\'article\',\'Help\', \'Article/index\', 4, 1);' . "\r\n\r\n\r\n" . '                ');
            }

            $list = M('Menu')->where(array(
                'url' => 'Config/index',
                'pid' => array('neq', 0)
            ))->select();

            if ($list[1]) {
                M('Menu')->where(array('id' => $list[1]['id']))->delete();
            } else if (!$list) {
                M('Menu')->add(array('url' => 'Config/index', 'title' => 'basic configuration', 'pid' => 7, 'sort' => 1, 'hide' => 0, 'group' => 'Set up', 'ico_name' => 'cog'));
            } else {
                M('Menu')->where(array(
                    'url' => 'Config/index',
                    'pid' => array('neq', 0)
                ))->save(array('title' => 'basic configuration', 'pid' => 7, 'sort' => 1, 'hide' => 0, 'group' => 'Set up', 'ico_name' => 'cog'));
            }

            $list = M('Menu')->where(array(
                'url' => 'Config/cellphone',
                'pid' => array('neq', 0)
            ))->select();

            if ($list[1]) {
                M('Menu')->where(array('id' => $list[1]['id']))->delete();
            } else if (!$list) {
                M('Menu')->add(array('url' => 'Config/cellphone', 'title' => 'SMS Configuration', 'pid' => 7, 'sort' => 2, 'hide' => 0, 'group' => 'Set up', 'ico_name' => 'cog'));
            } else {
                M('Menu')->where(array(
                    'url' => 'Config/cellphone',
                    'pid' => array('neq', 0)
                ))->save(array('title' => 'SMS Configuration', 'pid' => 7, 'sort' => 2, 'hide' => 0, 'group' => 'Set up', 'ico_name' => 'cog'));
            }

            $list = M('Menu')->where(array(
                'url' => 'Config/contact',
                'pid' => array('neq', 0)
            ))->select();

            if ($list[1]) {
                M('Menu')->where(array('id' => $list[1]['id']))->delete();
            } else if (!$list) {
                M('Menu')->add(array('url' => 'Config/contact', 'title' => 'Customer Service Configuration', 'pid' => 7, 'sort' => 3, 'hide' => 0, 'group' => 'Set up', 'ico_name' => 'cog'));
            } else {
                M('Menu')->where(array(
                    'url' => 'Config/contact',
                    'pid' => array('neq', 0)
                ))->save(array('title' => 'Customer Service Configuration', 'pid' => 7, 'sort' => 3, 'hide' => 0, 'group' => 'Set up', 'ico_name' => 'cog'));
            }

            $list = M('Menu')->where(array(
                'url' => 'Config/coin',
                'pid' => array('neq', 0)
            ))->select();

            if ($list[1]) {
                M('Menu')->where(array('id' => $list[1]['id']))->delete();
            } else if (!$list) {
                M('Menu')->add(array('url' => 'Config/coin', 'title' => 'The currency allocation', 'pid' => 7, 'sort' => 4, 'hide' => 0, 'group' => 'Set up', 'ico_name' => 'cog'));
            } else {
                M('Menu')->where(array(
                    'url' => 'Config/coin',
                    'pid' => array('neq', 0)
                ))->save(array('title' => 'The currency allocation', 'pid' => 7, 'sort' => 4, 'hide' => 0, 'group' => 'Set up', 'ico_name' => 'cog'));
            }

            $list = M('Menu')->where(array(
                'url' => 'Config/text',
                'pid' => array('neq', 0)
            ))->select();

            if ($list[1]) {
                M('Menu')->where(array('id' => $list[1]['id']))->delete();
            } else if (!$list) {
                M('Menu')->add(array('url' => 'Config/text', 'title' => 'Text Tips', 'pid' => 7, 'sort' => 5, 'hide' => 0, 'group' => 'Set up', 'ico_name' => 'cog'));
            } else {
                M('Menu')->where(array(
                    'url' => 'Config/text',
                    'pid' => array('neq', 0)
                ))->save(array('title' => 'Text Tips', 'pid' => 7, 'sort' => 5, 'hide' => 0, 'group' => 'Set up', 'ico_name' => 'cog'));
            }

            $list = M('Menu')->where(array(
                'url' => 'Config/misc',
                'pid' => array('neq', 0)
            ))->select();

            if ($list[1]) {
                M('Menu')->where(array('id' => $list[1]['id']))->delete();
            } else if (!$list) {
                M('Menu')->add(array('url' => 'Config/misc', 'title' => 'Other configurations', 'pid' => 7, 'sort' => 6, 'hide' => 0, 'group' => 'Set up', 'ico_name' => 'cog'));
            } else {
                M('Menu')->where(array(
                    'url' => 'Config/misc',
                    'pid' => array('neq', 0)
                ))->save(array('title' => 'Other configurations', 'pid' => 7, 'sort' => 6, 'hide' => 0, 'group' => 'Set up', 'ico_name' => 'cog'));
            }

            $list = M('Menu')->where(array(
                'url' => 'Config/navigation',
                'pid' => array('neq', 0)
            ))->select();

            if ($list[1]) {
                M('Menu')->where(array('id' => $list[1]['id']))->delete();
            } else if (!$list) {
                M('Menu')->add(array('url' => 'Config/navigation', 'title' => 'Navigation configuration', 'pid' => 7, 'sort' => 7, 'hide' => 0, 'group' => 'Set up', 'ico_name' => 'cog'));
            } else {
                M('Menu')->where(array(
                    'url' => 'Config/navigation',
                    'pid' => array('neq', 0)
                ))->save(array('title' => 'Navigation configuration', 'pid' => 7, 'sort' => 7, 'hide' => 0, 'group' => 'Set up', 'ico_name' => 'cog'));
            }

            if (M('Menu')->where(array('url' => 'Market/index'))->delete()) {
                M('AuthRule')->where(array('status' => 1))->delete();
            }

            if (M('Menu')->where(array('url' => 'Coin/index'))->delete()) {
                M('AuthRule')->where(array('status' => 1))->delete();
            }

            S(MODULE_NAME . CONTROLLER_NAME . 'checkUpdata', 1);
        }
    }

    public function roadmap()
    {

        $Roadmaps = M('Roadmap')->order('id desc')->select();
        $builder = new BuilderList();


        $builder->title('Roadmap');
        $builder->titleList('Roadmap List', U('Config/roadmap'));
        $builder->button('add', 'Add', U('Config/editRoadmap'));


        $builder->keyText('id', 'id');
        $builder->keyText('year', 'quarter');
        $builder->keyText('date', 'Date');
        $builder->keyText('text', 'Content');
        $builder->keyStatus('status', 'Status', array('Yet to come', 'Finished', 'Running'));
        $builder->keyDoAction('config/editRoadmap?id=###', 'Edit', 'Option');
        $builder->keyDoAction('config/deleteRoadmap?id=###', 'Delete', 'Option');
        $builder->data($Roadmaps);
        //$builder->pagination($count, $r, $parameter);
        $builder->display();
    }

    public function deleteRoadmap($id = NULL)
    {
        $where['id'] = $id;
        if (M('Roadmap')->where($where)->delete()) {
            $this->success(L('SUCCESSFULLY_DONE'));
        } else {
            $this->error('No changes were made.');
        }

    }

    public function editRoadmap($id = NULL)
    {
        if (!empty($_POST)) {

            if (APP_DEMO) {
                $this->error(L('SYSTEM_IN_DEMO_MODE'));
            }

            if (!check($_POST['year'], 'a')) {
            }
            if (!check($_POST['date'], 'a')) {
            }

            if (!check($_POST['text'], 'a')) {
                $this->error('Sort malformed');
            }

            if (!check($_POST['id'], 'd')) {
                $rs = M('Roadmap')->save($_POST);
            } else {
                $rs = M('Roadmap')->add($_POST);
            }

            if ($rs) {
                $this->success('Successful operation');
            } else {
                $this->error('Operation failed');
            }
        } else {
            $builder = new BuilderEdit();
            $builder->title('Roadmap');
            $builder->titleList('Roadmap List', U('Config/roadmap'));

            if ($id) {
                $builder->keyReadOnly('id', 'ID');
                $builder->keyHidden('id', 'Roadmap ID');
                $data = M('Roadmap')->where(array('id' => $id))->find();
                $builder->data($data);
            }

            $builder->keyText('year', 'Year', 'Q2 2020	');
            $builder->keyText('date', 'Date', 'Apr-Jun 2021');
            $builder->keyText('text', 'Content', 'Some Content');
            $builder->keyStatus('status', 'Status', 'Status', array('Yet to come', 'Finished', 'Running'));
            $builder->savePostUrl(U('Config/editRoadmap'));
            $builder->display();
        }
    }

    

    private function buildTable($data)
    {
        $data = json_decode($data, false);
        $table_row = '<table class="table">';
        foreach ($data as $key => $value) {

            $table_row .= "<tr class='controls'><td>" . $value->title . "</td><td>
            <input type='text' class='form-control input-10x' name='".$value->name."' value='".$this->valueBuilder($value->value)."'>
            </td></tr>";
        }
        $table_row .= "</table>";
        return $table_row;
    }

    private function valueBuilder($value)
    {
        return $value;
        if ($this->isJson($value)) {
            return json_decode($value);
        } else {
            return $value;
        }
    }

    function isJson($string)
    {
        json_decode($string);
        return json_last_error() === JSON_ERROR_NONE;
    }
    
}
