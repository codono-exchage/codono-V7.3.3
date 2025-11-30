<?php

namespace Api\Controller;

use Think\Page;


class BridgeController extends CommonController
{
    private int $internal_api_status = 0; //Disable by 0 [Keep it disabled if you do not know what is this]
    private string $system_APIKEY = "bf55fcff5d1cbeebf5f00e08e2" . '-' . ADMIN_KEY;
    private string $system_SECRET = "5f2b5cdbe5194f10b3241568fe" . '-' . ADMIN_KEY;

    private string $auth_type = "simple"; //use either simple or advance

    /*
    Advance auth will need you to calclate token using following formula
    $array : post data

    $token=hash_hmac ( 'sha256' , md5(serialize($array)) , $system_SECRET);

    Provide APIKEY and TOKEN in header
    */

    public function __construct()
    {


        exit(json_encode(array('msg'=>"Comment Line:".__LINE__.' to start this Internal api','status'=>0)));
        parent::__construct();
        if ($this->internal_api_status != 1) {
            exit(json_encode(array('msg' => 'Internal API\'s are disabled', 'status' => 0)));
        }
    }

    public function index()
    {
        $array = array('status' => 1, 'message' => 'Connected to Internal API');
        echo json_encode($array);
    }

    protected function Simpleauth()
    {

        if (!$_SERVER['HTTP_APIKEY'] || !$_SERVER['HTTP_TOKEN']) {
            $this->ajaxShow('Unauthorized E1', -99);
        }

        $input_id = trim($_SERVER['HTTP_APIKEY']);
        $input_token = trim($_SERVER['HTTP_TOKEN']);

        if ($input_id != $this->system_APIKEY || $input_token != $this->system_SECRET) {

            $this->ajaxShow('Unauthorized E2', -99);
        } else {
            return true;
        }

    }

    protected function Advancedauth($post)
    {

        if (!$_SERVER['HTTP_APIKEY'] || !$_SERVER['HTTP_TOKEN']) {
            $this->ajaxShow('Unauthorized E3', -99);
        }

        $generated_token = hash_hmac('sha256', md5(serialize($post)), $this->system_SECRET);
        $input_id = trim($_SERVER['HTTP_APIKEY']);
        $input_token = trim($_SERVER['HTTP_TOKEN']);

        if ($input_id != $this->system_APIKEY || $input_token != $generated_token) {
            $this->ajaxShow('Unauthorized E4', -99);
        } else {
            return true;
        }

    }

    protected function auth($post)
    {
        if ($this->auth_type == 'simple') {
            return $this->Simpleauth();
        } else {
            return $this->Advancedauth($post);
        }
    }


    public function addUser()
    {
        $input = I('post.');
        $this->auth($input);
        $form = $input;
        $pwd = md5(rand(1000, 999990) . ADMIN_KEY . tradeno() . CRON_KEY . time());
        $userid = $form['user_id'];
        $username = $form['user_id'];
        $password = $pwd;
        $paypassword = $pwd;
        $truename = $form['user_id'];
        $idcardauth = 1;
        $endtime = time();
        $email = $userid . '@local.local';


        if (M('User')->where(array('username' => $userid))->find()) {
            $action = "update";
            if ($username) {
                $update_array['username'] = $username;
            }
            if ($email) {
                $update_array['email'] = $email;
            }
            if ($idcardauth == 0 || $idcardauth == 1) {
                $update_array['idcardauth'] = $idcardauth;
            }
            if ($password) {
                $update_array['password'] = $password;
            }
            if ($paypassword) {
                $update_array['paypassword'] = $paypassword;
            }
            if ($truename) {
                $update_array['truename'] = $truename;
            }
            if ($endtime) {
                $update_array['endtime'] = $endtime;
            }

        } else {
            $action = "add";
            if (!isset($username)) {
                $this->error('Username needed');
            }
            if (!isset($password)) {
                $this->error('Password needed');
            }
            if (!isset($paypassword)) {
                $this->error('FundPwd needed');
            }
            if (!isset($truename)) {
                $this->error('Truename needed');
            }
            if (!isset($email)) {
                $this->error('Email needed');
            }

            if ($idcardauth != 0 && $idcardauth != 1) {
                $this->error('idcardauth should be 1 or 0 = KYC verified');
            }

        }

        if ($action == "add") {
            $invit_1 = 0;
            $invit_2 = 0;
            $invit_3 = 0;
            $token = md5(md5(rand(0, 10000) . md5(time()), md5(uniqid())));
            for (; true;) {
                $tradeno = tradenoa();

                if (!M('User')->where(array('invit' => $tradeno))->find()) {
                    break;
                }
            }

            $mo = M();
            $mo->startTrans();
            $rs = [];
            $rs[] = $uid = $mo->table('codono_user')->add(array('username' => $username, 'email' => $email, 'password' => $password, 'invit' => $tradeno, 'truename' => $truename, 'paypassword' => $paypassword, 'tpwdsetting' => 1, 'idcardauth' => $idcardauth, 'invit_1' => $invit_1, 'invit_2' => $invit_2, 'invit_3' => $invit_3, 'addtime' => time(), 'status' => 1, 'endtime' => $endtime, 'token' => $token));

            $rs[] = $mo->table('codono_user_coin')->add(array('userid' => $uid));

            if (check_arr($rs)) {
                $mo->commit();

                $send['status'] = 1;
                $send['last_update'] = $endtime;
                $send['data'] = $form;
                $send['msg'] = "registration success";
            } else {
                $mo->rollback();

                $send['status'] = 0;
                $send['action'] = $action;
                $send['last_update'] = $endtime;
                $send['data'] = $form;
                $send['msg'] = "registration failed";

            }
            echo json_encode($send);
            exit;
        } else {

            $send['status'] = 0;
            $send['action'] = 0;;
            $send['last_update'] = $endtime;
            $send['data'] = 0;
            $send['msg'] = "Username\user_id Exists, Could not add user to exchange";
            echo json_encode($send);
            exit;
        }

    }

    public function depositAddress()
    {
        $input = I('post.');
        $this->auth($input);
        $form = $input;
        $username = $form['user_id'];
        $user_info = M('User')->where(array('username' => $username))->getField('id');
        $uid = $user_info;
        $coin = $form['coin'];
        if (!$uid) {
            exit(json_encode(['status' => 0, 'msg' => 'username does not exist:' . $username]));
        }
        if (!$coin) {
            exit(json_encode(['status' => 0, 'msg' => 'Please enter coin as parameter:' . $coin]));
        }

        $dest_tag = $wallet = $show_qr = [];
        $coin = $coin ? trim($coin) : C('xnb_mr');


        $Coins = C('coin');
        $coin_list = [];
        foreach ($Coins as $k => $v) {
            if ($v['type'] != 'rmb') {
                if ($v['symbol'] != null && $v['symbol'] != $v['name']) {
                    continue;
                }
                $coin_list[$v['name']] = array('name' => $v['name'], 'type' => $v['type'], 'title' => $v['title'], 'img' => '/Upload/coin/' . $v['img'], 'deposit' => $v['zr_jz'], 'confirmations' => $v['zr_dz'], 'explorer' => $v['js_wk']);
            }
        }

        ksort($coin_list);

        $user_coin = M('UserCoin')->where(array('userid' => $uid))->find(); //$this->usercoins;
        $user_coin[$coin] = format_num($user_coin[$coin], 8);


        $CoinsBySymbol = (APP_DEBUG ? null : S('CoinsBySymbol'));


        if (!$CoinsBySymbol) {
            foreach ($Coins as $CLIST) {
                if ($CLIST['symbol'] != null) {
                    $CoinsBySymbol[$CLIST['symbol']][] = $CLIST;
                } else {
                    $CoinsBySymbol[$CLIST['name']][] = $CLIST;
                }
            }
            S('CoinsBySymbol', $CoinsBySymbol);
        }

        $Coinx = $CoinsBySymbol[$coin];

        foreach ($Coinx as $Coin) {
            $i = $internal_coin = $Coin['name'];
            $coin_address = strtolower($Coin['name']) . 'b';
            $tokenof = $Coin['tokenof'];


            $codono_getCoreConfig = codono_getCoreConfig();
            if (!$codono_getCoreConfig) {
                $this->error(L('Incorrect Core Config'));
            }


            if ($codono_getCoreConfig['codono_opencoin'] == 1 && $Coin['type'] != 'offline') {

                if (!$Coin['zr_jz']) {
                    $message[$i] = L('The current ban into the currency!');
                    $wallet[$i] = 0;
                } else {


                    if (!$user_coin[$coin_address] && !$user_coin[$tokenof . 'b']) {

                        if ($Coin['type'] == 'rgb') {
                            $wallet[$i] = $address = md5(username() . $coin_address);

                            $rs = M('UserCoin')->where(array('userid' => $uid))->save(array($coin_address => $address));
                            $user_exists = $this->userinfo['id'];//M('User')->where(array('id' => $uid))->getField('id');

                            if (!$rs && !$user_exists) {
                                $this->error(L('Generate wallet address wrong!'));
                            }
                            //die($coin_address);
                            if (!$rs && $user_exists) {
                                $ucoin[$coin_address] = $address;
                                $ucoin['userid'] = $user_exists;
                                M('UserCoin')->add($ucoin);
                            }

                        }

                        //XRP STARTS

                        if ($Coin['type'] == 'xrp') {

                            $wallet[$i] = $address = $Coin['codono_coinaddress'];//Contract Address
                            $the_dest_tag = $dest_tag[$i] = $user_coin[$internal_coin . '_tag'];

                            if (isset($address)) {
                                if (!$the_dest_tag) {

                                    $xrp_len = 9 - strlen($uid);
                                    $min = pow(10, ($xrp_len - 1));
                                    $max = pow(10, $xrp_len) - 1;
                                    $xrp_str = mt_rand($min, $max);

                                    $saveme[$internal_coin . '_tag'] = $dest_tag[$i] = $the_dest_tag = $uid . $xrp_str;

                                    //TO add xrp_tag field in user_coin table if not exits
                                    $dest_tag_field = $internal_coin . '_tag';
                                    $tag_sql = "SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE table_name = 'codono_user_coin' AND column_name = '$dest_tag_field'";
                                    $if_tag_exists = M()->execute($tag_sql);

                                    //Create a destination tag
                                    if (!$if_tag_exists) {

                                        M()->execute("ALTER TABLE `codono_user_coin` ADD $dest_tag_field VARCHAR(200) NULL DEFAULT NULL COMMENT 'Tag for $internal_coin'");
                                    }

                                    $rs = M('user_coin')->where(array('userid' => $uid))->save(array($internal_coin . '_tag' => $the_dest_tag));

                                    if ($rs) {
                                        $wallet[$i] = $address;
                                        $show_qr[$i] = 0;
                                    } else {
                                        $wallet[$i] = 0;
                                        $message[$i] = L('Wallet System is currently offline 2! ' . $coin);
                                        $show_qr[$i] = 0;
                                    }
                                }
                            } else {
                                $wallet[$i] = 0;
                                $message[$i] = L('Wallet System is currently offline 2! ' . $coin);
                                $show_qr[$i] = 0;

                            }
                        }

                        //XRP ENDS

                        //cryptoapis starts
                        if ($Coin['type'] == 'cryptoapis') {
                            $cryptoapi_config = array(
                                'api_key' => cryptString($Coin['dj_mm'], 'd'),
                                'network' => $Coin['network'],
                            );

                            $cryptoapi = CryptoApis($cryptoapi_config);
                            $supportedCryptoApisChains = $cryptoapi->allowedSymbols();
                            if (!in_array($coin, $supportedCryptoApisChains) && !in_array($tokenof, $supportedCryptoApisChains)) {
                                $wallet[$i] = 0;
                                $message[$i] = L('Wallet System is currently offline 2! ' . $coin);
                                $show_qr[$i] = 0;
                            }
                            $contract = $Coin['dj_yh'];//Contract Address
                            $blockchain = $coin;
                            $walletId = $Coin['dj_zj'];
                            $context = $uid;
                            $main_address = $Coin['codono_coinaddress'];

                            if (!$contract) {
                                //Call the interface to generate a new wallet address
                                $wallet[$i] = $address = $cryptoapi->createAddress($blockchain, $walletId, $context, $main_address);

                                if ($address) {
                                    if ($tokenof) {
                                        $saveme[$tokenof . 'b'] = $address;
                                    } else {
                                        $saveme[$coin_address] = $address;
                                    }

                                    $rs = M('UserCoin')->where(array('userid' => $uid))->save($saveme);

                                } else {
                                    $wallet[$i] = 0;
                                    $message[$i] = L('Wallet System is currently offline 2! ' . $coin);
                                    $show_qr[$i] = 0;
                                }
                            } else {

                                //cryptoapi contract
                                $rs1 = $user_coin;
                                $tokenof = $Coin['tokenof'];
                                $tokenofb = $tokenof . 'b';
                                if ($rs1[$tokenofb]) {
                                    $wallet[$i] = $address = $rs1[$tokenofb];
                                    $saveme[$coin_address] = $address;
                                    $cryptoapi->createTokenForwarding($blockchain, $main_address, $address, $contract, $context);
                                    $rs = M('UserCoin')->where(array('userid' => $uid))->save($saveme);

                                } else {
                                    //Call the interface to generate a new wallet address

                                    $wallet[$i] = $address = $cryptoapi->createAddress($blockchain, $walletId, $context, $main_address);

                                    if ($address) {

                                        if ($tokenof) {
                                            $saveme[$tokenof . 'b'] = $address;
                                        } else {
                                            $saveme[$coin_address] = $address;
                                        }

                                        $cryptoapi->createTokenForwarding($blockchain, $main_address, $address, $contract, $context);
                                        $rs = M('UserCoin')->where(array('userid' => $uid))->save($saveme);
                                    } else {
                                        $wallet[$i] = 0;
                                        $message[$i] = L('Wallet System is currently offline 1! ' . $coin);
                                        $show_qr[$i] = 0;
                                    }

                                }
                            }

                        }
                        //cryptoapis  Ends
                        //esmart starts
                        if ($Coin['type'] == 'esmart') {

                            $contract = $Coin['dj_yh'];//Contract Address
                            $dj_password = $Coin['dj_mm'];
                            $dj_address = $Coin['dj_zj'];
                            $dj_port = $Coin['dj_dk'];
                            $esmart_config = array(
                                'host' => $Coin['dj_zj'],
                                'port' => $Coin['dj_dk'],
                                'coinbase' => $Coin['codono_coinaddress'],
                                'password' => cryptString($Coin['dj_mm'], 'd'),
                                'contract' => $Coin['dj_yh'],
                                'rpc_type' => $Coin['rpc_type'],
                                'public_rpc' => $Coin['public_rpc'],
                            );
                            $Esmart = Esmart($esmart_config);


                            if (!$contract) {

                                //esmart
                                //Call the interface to generate a new wallet address
                                $wall_pass = ETH_USER_PASS;
                                $wallet[$i] = $address = $Esmart->personal_newAccount($wall_pass);

                                if ($address) {
                                    if ($tokenof) {
                                        $saveme[$tokenof . 'b'] = $address;
                                    } else {
                                        $saveme[$coin_address] = $address;
                                    }

                                    $rs = M('UserCoin')->where(array('userid' => $uid))->save($saveme);

                                } else {
                                    $wallet[$i] = 0;
                                    $message[$i] = L('Wallet System is currently offline 2! ' . $coin);
                                    $show_qr[$i] = 0;
                                }
                            } else {

                                //esmart contract
                                $rs1 = $user_coin;
                                $tokenof = $Coin['tokenof'];
                                $tokenofb = $tokenof . 'b';
                                if ($rs1[$tokenofb]) {
                                    $wallet[$i] = $address = $rs1[$tokenofb];
                                    $saveme[$coin_address] = $address;

                                    $rs = M('UserCoin')->where(array('userid' => $uid))->save($saveme);

                                } else {
                                    //Call the interface to generate a new wallet address
                                    $wall_pass = ETH_USER_PASS;
                                    $wallet[$i] = $address = $Esmart->personal_newAccount($wall_pass);
                                    if ($address) {

                                        if ($tokenof) {
                                            $saveme[$tokenof . 'b'] = $address;
                                        } else {
                                            $saveme[$coin_address] = $address;
                                        }


                                        $rs = M('UserCoin')->where(array('userid' => $uid))->save($saveme);
                                    } else {
                                        $wallet[$i] = 0;
                                        $message[$i] = L('Wallet System is currently offline 1! ' . $coin);
                                        $show_qr[$i] = 0;
                                    }

                                }
                            }

                        }
                        //esmart  Ends


                        //CoinPayments starts
                        if ($Coin['type'] == 'coinpay') {

                            $dj_username = $Coin['dj_yh'];
                            $dj_password = $Coin['dj_mm'];
                            $dj_address = $Coin['dj_zj'];
                            $dj_port = $Coin['dj_dk'];

                            $cps_api = CoinPay($dj_username, $dj_password, $dj_address, $dj_port, 5, array(), 1);
                            $information = $cps_api->GetBasicInfo();
                            $coinpay_coin = strtoupper($coin);


                            if ($information['error'] != 'ok' || !isset($information['result']['username'])) {
                                clog('coinpay_connection', $coin . ' can not be connectted at time: Error is ' . $information['error']);
                                $message[$i] = L('Wallet System is currently offline 1!');
                                $wallet[$i] = 0;
                                $show_qr[$i] = 0;
                            } else {
                                $show_qr[$i] = 1;

                                $ipn_url = SITE_URL . 'IPN/confirm';


                                // Prevent coinpayments to send duplicate address
                                $need_new_address = false;

                                $transaction_response = $cps_api->GetCallbackAddressWithIpn($coinpay_coin, $ipn_url);
                                $dest_tag[$i] = $the_dest_tag = $transaction_response['result']['dest_tag'] ?: 0;
                                $wallet_addr = $transaction_response['result']['address'];

                                $user_condition = [];
                                $user_condition[$coin . 'b'] = $wallet_addr;
                                if ($the_dest_tag != NULL || $the_dest_tag != 0) {
                                    $user_condition[$coin . '_tag'] = $the_dest_tag;
                                }

                                if (($user = M('UserCoin')->where($user_condition)->find())) {
                                    $need_new_address = true;
                                }


                                // Prevent coinpayments to send duplicate address ends


                                if (!is_array($wallet_addr)) {
                                    $wallet_ad = $wallet_addr;
                                    if (!$wallet_ad) {
                                        $wallet[$i] = $address = $wallet_addr;
                                    } else {
                                        $wallet[$i] = $address = $wallet_ad;
                                    }
                                } else {
                                    $wallet[$i] = $address = $wallet_addr[0];
                                }


                                if (!$address) {
                                    $this->error('Generate Wallet address error2!');
                                }
                                $dest_tag_field = $coin . '_tag';
                                $coinpay_update_array[$coin_address] = $address;

                                $tag_sql = "SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE table_name = 'codono_user_coin' AND column_name = '$dest_tag_field'";
                                $if_tag_exists = M()->execute($tag_sql);

                                //Create a destination tag
                                if (!$if_tag_exists) {

                                    M()->execute("ALTER TABLE `codono_user_coin` ADD $dest_tag_field VARCHAR(200) NULL DEFAULT NULL COMMENT 'Tag for xrp,xmr'");
                                }

                                if ($the_dest_tag != 0 || $the_dest_tag != NULL) {
                                    $coinpay_update_array[$dest_tag_field] = strval($the_dest_tag);
                                    //$dtag_sql='UPDATE `codono_user_coin` SET `'.$dest_tag_field.'` = '.$the_dest_tag.' WHERE `codono_user_coin`.`userid` = '.$uid;
                                    $dtag_sql = 'UPDATE `codono_user_coin` SET `' . $dest_tag_field . '` = "' . $the_dest_tag . '" WHERE `codono_user_coin`.`userid` = ' . $uid;
                                    $rs = M('UserCoin')->execute($dtag_sql);
                                }


                                $mo = M();
                                $rs = $mo->table('codono_user_coin')->where(array('userid' => $uid))->save($coinpay_update_array);

                                if (!$rs) {
                                    $wallet[$i] = 0;
                                    $message[$i] = L('Wallet System is currently offline 1!');
                                    $show_qr[$i] = 0;
                                }
                            }


                        }
                        //CoinPayments  Ends
                        //WavesPlatform Starts

                        if ($Coin['type'] == 'waves') {

                            $qbdz = 'wavesb';
                            $dj_username = $Coin['dj_yh'];
                            $dj_password = $Coin['dj_mm'];
                            $dj_address = $Coin['dj_zj'];
                            $dj_port = $Coin['dj_dk'];
                            $dj_decimal = $Coin['cs_qk'];
                            $waves = WavesClient($dj_username, $dj_password, $dj_address, $dj_port, $dj_decimal, 5, array(), 1);
                            $waves_coin = strtoupper($coin);
                            $information = json_decode($waves->status(), true);

                            if ($information['blockchainHeight'] && $information['blockchainHeight'] <= 0) {
                                $wallet[$i] = L('Wallet System is currently offline 1!');
                                $show_qr[$i] = 0;
                            } else {
                                $show_qr[$i] = 1;
                                $rs1 = $this->usercoins;//M('UserCoin')->where(array('userid' => $uid))->find();
                                if ($rs1['wavesb']) {
                                    $waves_good = 0;
                                    $wallet_addr = $rs1['wavesb'];
                                } else {
                                    $waves_good = 1;
                                    $transaction_response = $address = json_decode($waves->CreateAddress(), true);
                                    $wallet_addr = $transaction_response['address'];

                                }

                                if (!is_array($wallet_addr)) {
                                    $wallet_ad = $wallet_addr;
                                    if (!$wallet_ad) {
                                        $wallet[$i] = $address = $wallet_addr;
                                    } else {
                                        $wallet[$i] = $address = $wallet_ad;
                                    }
                                } else {
                                    $wallet[$i] = $address = $wallet_addr[0];
                                }

                                if (!$address) {
                                    $show_qr[$i] = 0;
                                    $message[$i] = L('Wallet System is currently offline 2!');
                                    $wallet[$i] = false;
                                }
                                if ($show_qr[$i] == 1) {
                                    $rs = M('UserCoin')->where(array('userid' => $uid))->save(array($qbdz => $address));
                                    if (!$rs && $waves_good == 1) {
                                        $message[$i] = L('Wallet System is currently offline 3!');
                                        $wallet[$i] = 0;
                                    }
                                }
                            }


                        }
                        //WavesPlatform Ends
                        //blockio starts
                        if ($Coin['type'] == 'blockio') {

                            $dj_username = $Coin['dj_yh'];
                            $dj_password = $Coin['dj_mm'];
                            $dj_address = $Coin['dj_zj'];
                            $dj_port = $Coin['dj_dk'];

                            $block_io = BlockIO($dj_username, $dj_password, $dj_address, $dj_port, 5, array(), 1);
                            $json = $block_io->get_balance();

                            if (!isset($json->status) || $json->status != 'success') {
                                //$this->error(L('Wallet link failure! 1'));
                                $message[$i] = L('Wallet System is currently offline 2!');
                                $show_qr[$i] = 0;
                                $wallet[$i] = 0;
                            } else {
                                $show_qr[$i] = 1;
                                $wallet_addr = $block_io->get_address_by_label(array('label' => username()))->data->address;

                                if (!is_array($wallet_addr)) {
                                    $getNewAddressInfo = $block_io->get_new_address(array('label' => username()));
                                    $wallet_ad = $getNewAddressInfo->data->address;


                                    if (!$wallet_ad) {
                                        $wallet[$i] = $address = $wallet_addr;
                                    } else {
                                        $wallet[$i] = $address = $wallet_ad;
                                    }
                                } else {
                                    $wallet[$i] = $address = $wallet_addr[0];
                                }

                                if (!$address) {
                                    $this->error('Generate Wallet address error2!');
                                }

                                $rs = M('UserCoin')->where(array('userid' => $uid))->save(array($coin_address => $address));

                                if (!$rs) {
                                    $this->error('Add error address wallet3!');
                                }
                            }


                        }
                        //blockio  Ends

                        //cryptonote starts
                        if ($Coin['type'] == 'cryptonote') {

                            $dj_username = $Coin['dj_yh'];
                            $dj_password = $Coin['dj_mm'];
                            $dj_address = $Coin['dj_zj'];
                            $dj_port = $Coin['dj_dk'];
                            $cryptonote = CryptoNote($dj_address, $dj_port);
                            $open_wallet = $cryptonote->open_wallet($dj_username, $dj_password);

                            $json = json_decode($cryptonote->get_height());

                            if (!isset($json->height) || $json->error != 0) {
                                $message[$i] = L('Wallet System is currently offline 2!');
                                $show_qr[$i] = 0;
                                $wallet[$i] = 0;
                            } else {
                                $show_qr[$i] = 1;
                                $cryptofields = $coin . 'b';


                                $wallet_addr = $Coin['codono_coinaddress'];
                                if (!is_array($wallet_addr)) {
                                    $getNewAddressInfo = json_decode($cryptonote->create_address(0, username()));
                                    $wallet_ad = $getNewAddressInfo->address;


                                    if (!$wallet_ad) {
                                        $wallet[$i] = $address = $wallet_addr;
                                    } else {
                                        $wallet[$i] = $address = $wallet_ad;
                                    }
                                } else {
                                    $wallet[$i] = $address = $wallet_addr[0];
                                }

                                if (!$address) {
                                    $this->error('Generate Wallet address error2!');
                                    //$wallet=L('Can not generate '.$coin.' wallet at the moment');
                                }

                                $dest_tag[$i] = $the_dest_tag = $cryptonote->genPaymentId();
                                $dest_tag_field = $coin . '_tag';
                                $cryptonote_update_array[$coin_address] = $wallet;

                                $tag_sql = "SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE table_name = 'codono_user_coin' AND column_name = '$dest_tag_field'";
                                $if_tag_exists = M()->execute($tag_sql);

                                //Create a destination tag
                                if (!$if_tag_exists) {

                                    M()->execute("ALTER TABLE `codono_user_coin` ADD $dest_tag_field VARCHAR(200) NULL DEFAULT NULL COMMENT 'Tag for xrp,xmr'");
                                }

                                if ($the_dest_tag != 0 || $the_dest_tag != NULL) {
                                    $cryptonote_update_array[$dest_tag_field] = $the_dest_tag;
                                    $dtag_sql = 'UPDATE `codono_user_coin` SET `' . $dest_tag_field . '` = "' . $the_dest_tag . '" WHERE `codono_user_coin`.`userid` = ' . $uid;
                                    $rs = M('UserCoin')->execute($dtag_sql);
                                }


                                $mo = M();
                                $rs = $mo->table('codono_user_coin')->where(array('userid' => $uid))->save($cryptonote_update_array);


                                if (!$rs) {
                                    $this->error('Add error address wallet3!');
                                }
                            }


                        }
                        //CryptoNote Ended
                        //Bitcoin starts
                        if ($Coin['type'] == 'qbb') {

                            $dj_username = $Coin['dj_yh'];
                            $dj_password = $Coin['dj_mm'];
                            $dj_address = $Coin['dj_zj'];
                            $dj_port = $Coin['dj_dk'];
                            $CoinClient = CoinClient($dj_username, $dj_password, $dj_address, $dj_port, 5, array(), 1);
                            $json = $CoinClient->getinfo();
                            $address = null;
                            if (!isset($json['version']) || !$json['version']) {
                                $wallet[$i] = false;
                                $message[$i] = L('Wallet System is currently offline 3!');
                                $show_qr[$i] = 0;
                            } else {
                                $show_qr[$i] = 1;
                                $wallet_addr = $CoinClient->getaddressesbyaccount(username());

                                if (!is_array($wallet_addr) || empty($wallet_addr)) {
                                    $wallet_ad = $CoinClient->getnewaddress(username());

                                    if (!$wallet_ad) {
                                        $this->error('Generate Wallet address error1!');
                                    } else {
                                        $wallet[$i] = $address = $wallet_ad;
                                    }
                                } else {
                                    $wallet[$i] = $address = $wallet_addr[0];
                                }


                                if (!$address) {
                                    $this->error('Generate Wallet address error2!');
                                }
                                if ($tokenof) {
                                    $saveme[$tokenof . 'b'] = $address;
                                } else {
                                    $saveme[$coin_address] = $address;
                                }

                                $rs = M('UserCoin')->where(array('userid' => $uid))->save($saveme); 
                                
                                if (!$rs) {
                                    $this->error('Add error address wallet3!');
                                }
                            }
                        }
                    } else {


                        //$wallet[$i] = $user_coin[$coin . 'b'];

                        if ($user_coin[$tokenof . 'b']) {
                            $wallet[$i] = $user_coin[$tokenof . 'b'];
                        } else {
                            $wallet[$i] = $user_coin[$coin . 'b'];
                        }


                        $dest_tag[$i] = false;
                        if (isset($user_coin[$coin . '_tag'])) {
                            $dest_tag[$i] = $user_coin[$coin . '_tag'];
                        }
                    }
                }
            } else {

                if (!$Coin['zr_jz']) {
                    $wallet = L('The current ban into the currency!');
                } else {

                    $wallet[$i] = $Coin['codono_coinaddress'];

                    $cellphone = $this->userinfo['cellphone'];//M('User')->where(array('id' => $uid))->getField('cellphone');
                    $email = $this->userinfo['email'];//M('User')->where(array('id' => $uid))->getField('email');

                    if ($cellphone || $email) {
                        $cellphone = substr_replace($cellphone, '****', 3, 4);
                        $email = substr_replace($email, '****', 3, 4);
                    } else {
                        if (M_ONLY == 1) {
                            redirect(U('Home/User/cellphone'), $time = 5, $msg = L('Please Verify your Phone!'));
                        }
                    }


                }

            }

            if (is_array($wallet)) {
                $show_wallet = $wallet[$i];
            } else {
                $show_wallet = $wallet;
            }

            if ($wallet[$i] == false) {
                $put_wallet = $message[$i];

            } else {
                $put_wallet = $wallet[$i];
            }

            $tokenof = $Coin['tokenof'];
            if ($tokenof) {

                $accurate_address = $user_coin[$tokenof . 'b'];

            } else {
                $accurate_address = $show_wallet;
            }


            $InfoCoin[$i] = array('name' => $Coin['name'], 'title' => $Coin['title'], 'img' => './Upload/coin/' . $Coin['img'], 'deposit' => $Coin['zr_jz'], 'confirmations' => $Coin['zr_dz'], 'explorer' => $Coin['js_wk'], 'wallet' => $accurate_address, 'message' => $message[$i], 'qr' => $show_qr[$i], 'dest_tag' => $dest_tag[$i]);
        }//foreach ends

        $out['status'] = 1;
        $out['coin'] = $coin;
        $out['wallet'] = $wallet;
        $out['dest_tag'] = $dest_tag;
        $out['message'] = $message;
        $out['show_qr'] = $show_qr;
        $out['infocoin'] = $InfoCoin;
        header("Content-Type: application/json");
        echo json_encode($out);
        exit;
    }

    public function sendWithdrawal()
    {
        $input = I('post.');
        $this->auth($input);
        $form = $input;
        $username = $form['user_id'];
        $addr = $form['address'];
        $num = $form['amount'];
        $network = $form['internal_symbol'];
        $dest_tag = $form['dest_tag'];
        $coin = $form['coin'];

        if (!$coin) {
            exit(json_encode(['status' => 0, 'msg' => 'Please enter coin as parameter:' . $coin]));
        }

        $num = format_num($num);

        if (!check($num, 'double')) {
            exit(json_encode(['status' => 0, 'msg' => 'Please enter a valid amount as parameter:' . $num]));
        }

        if (!check($addr, 'dw')) {
            exit(json_encode(['status' => 0, 'msg' => 'Please enter a valid address as parameter:' . $addr]));
        }

        $isValidCoin = $this->isValidCoin($coin);
        if (!$isValidCoin) {
            exit(json_encode(['status' => 0, 'msg' => 'E:8:Please enter a valid coin as parameter:' . $coin]));
        }
        $isValidNetwork = $this->isValidCoin($network);
        if (!$isValidNetwork) {
            exit(json_encode(['status' => 0, 'msg' => 'E:3:Please enter a valid internal_coin as parameter:' . $network]));
        }
        $CoinInfo = C('coin')[$network];
        $pcoin = $CoinInfo['tokenof'] ?: $CoinInfo['name'];
        if (!$CoinInfo) {
            exit(json_encode(['status' => 0, 'msg' => 'E:9:Please enter a valid coin as parameter' . $coin]));
        }
        $user_info = M('User')->where(array('username' => $username))->getField('id');
        $uid = $user_info;
        if (!$uid) {
            exit(json_encode(['status' => 0, 'msg' => 'username does not exist:' . $username]));
        }
        $auto_status = ($CoinInfo['zc_zd'] && ($num < $CoinInfo['zc_zd']) ? 1 : 0);
        $contract_address = $CoinInfo['dj_yh'];//Contract Address
        $dj_username = $CoinInfo['dj_yh'];
        $dj_address = $CoinInfo['dj_zj'];
        $dj_port = $CoinInfo['dj_dk'];
        $dj_decimal = $CoinInfo['cs_qk'];
        $main_address = $CoinInfo['codono_coinaddress'];

        $myzc_min = ($CoinInfo['zc_min'] ? abs($CoinInfo['zc_min']) : 0.0001);
        $myzc_max = ($CoinInfo['zc_max'] ? abs($CoinInfo['zc_max']) : 10000000);

        if ($num < $myzc_min) {
            exit(json_encode(['status' => 0, 'msg' => 'Amount is less than Minimum Withdrawal Amount:' . $num]));
        }

        if ($myzc_max < $num) {
            exit(json_encode(['status' => 0, 'msg' => 'Amount Exceeds Maximum Withdrawal Limit:' . $num]));
        }

        $user = $this->userinfo;

        $user_coin = M('UserCoin')->where(array('userid' => $uid))->find();

        if ($user_coin[$coin] < $num) {
            exit(json_encode(['status' => 0, 'msg' => 'Insufficient funds available:' . $num]));
        }
        $zc_user = $CoinInfo['zc_user'];
        $qbdz = $coin . 'b';
        $networkb = $network . 'b';
        $fee_user['userid'] = M('User')->where(array('id' => $zc_user))->getField("id");
        if ($fee_user['userid'] == 0 || $fee_user['userid'] == null || $fee_user['userid'] < 0) {
            $fee_user['userid'] = 0;
        }

        $flat_fee = $CoinInfo['zc_flat_fee'];
        $percent_fee = bcmul(bcdiv($num, 100, 8), $CoinInfo['zc_fee'], 8);
        $fee = bcadd($flat_fee, $percent_fee, 8);
        $mum = bcsub($num, $fee, 8);

        if ($fee < 0) {
            $this->error(L('Incorrect withdrawal fee!'));
        }
        if ($mum < 0) {
            $this->error(L('Incorrect withdrawal amount!'));
        }
        $mo = M();
        $peer = $mo->table('codono_user_coin')->where(array($networkb => $addr))->find();

        if ($CoinInfo['type'] == 'rgb' && !$peer) {
            $this->error(L('Withdrawal Address does not exist!'));
        }
        //Withdrawal address exists on Exchange thus this would be an internal transfer

        if ($peer) {

            $rs = [];
            $rs[] = $mo->table('codono_user_coin')->where(array('userid' => $uid))->setDec($coin, $num);
            $rs[] = $mo->table('codono_user_coin')->where(array('userid' => $peer['userid']))->setInc($coin, $mum);
            $gen_tx_id = 'internal_' . md5($uid . $peer['userid'] . $addr . $networkb . time());
            $rs[] = $aid = $mo->table('codono_myzc')->add(array('userid' => $uid, 'username' => $addr, 'coinname' => $coin, 'network' => $network, 'txid' => $gen_tx_id, 'num' => $num, 'fee' => $fee, 'mum' => $mum, 'addtime' => time(), 'status' => 1));
            $rs[] = $mo->table('codono_myzr')->add(array('userid' => $peer['userid'], 'username' => $user_coin[$coin . 'b'], 'coinname' => $coin, 'txid' => $gen_tx_id, 'num' => $num, 'fee' => $fee, 'mum' => $mum, 'addtime' => time(), 'status' => 1));


            if ($fee && $fee_user['userid'] != 0) {

                $rs[] = $mo->table('codono_myzc_fee')->add(array('userid' => $fee_user['userid'], 'txid' => $aid, 'username' => $zc_user, 'coinname' => $coin, 'num' => $num, 'fee' => $fee, 'mum' => $mum, 'type' => 2, 'addtime' => time(), 'status' => 1));

                if ($mo->table('codono_user_coin')->where(array('userid' => $zc_user))->find()) {
                    $rs[] = $mo->table('codono_user_coin')->where(array('userid' => $zc_user))->setInc($coin, $fee);
                    $rs[] = $mo->table('codono_invit')->add(array('coin' =>$coin,'userid' => $fee_user['userid'], 'invit' => $uid, 'name' => 'Withdrawal Fees', 'type' => $coin . ' withdrawal', 'num' => $num, 'mum' => $mum, 'fee' => $fee, 'addtime' => time(), 'status' => 1));

                }
            }
            $this->success('Withdrawal has been processed!');
        }

        //tron Starts
        if ($CoinInfo['type'] == 'tron') {
            $tron = TronClient();
            $rs = [];
            $rs[] = $mo->table('codono_user_coin')->where(array('userid' => $uid))->setDec($coin, $num);
            $rs[] = $aid = $mo->table('codono_myzc')->add(array('userid' => $uid, 'username' => $addr, 'coinname' => $coin, 'network' => $network, 'num' => $num, 'fee' => $fee, 'mum' => $mum, 'addtime' => time(), 'status' => $auto_status));

            if ($fee && $fee_user['userid'] != 0) {

                $rs[] = $mo->table('codono_myzc_fee')->add(array('userid' => $fee_user['userid'], 'txid' => $aid, 'username' => $zc_user, 'coinname' => $coin, 'num' => $num, 'fee' => $fee, 'mum' => $mum, 'type' => 2, 'addtime' => time(), 'status' => 1));

                if ($mo->table('codono_user_coin')->where(array('userid' => $zc_user))->find()) {
                    $rs[] = $mo->table('codono_user_coin')->where(array('userid' => $zc_user))->setInc($coin, $fee);
                    $rs[] = $mo->table('codono_invit')->add(array('coin' =>$coin,'userid' => $fee_user['userid'], 'invit' => $uid, 'name' => 'Withdrawal Fees', 'type' => $coin . '_withdrawalFees', 'num' => $num, 'mum' => $mum, 'fee' => $fee, 'addtime' => time(), 'status' => 1));
                }
            }


            if ($auto_status) {
                $ContractAddress = $contract_address;

                $amount = (double)$mum;
                $priv = cryptString($CoinInfo['dj_mm'], 'd');
                $decimals = $CoinInfo['cs_qk'];
                if (strpos($ContractAddress, 'T') !== false) {
                    //todo for trc20
                    $abi = $tron->getAbi($CoinInfo['name']);
                    $sendrs = $tron->transferTrc20($abi, $ContractAddress, $addr, $amount, $main_address, $priv, $decimals);
                } else if ($ContractAddress) {

                    //todo for trc10 transfer
                    $sendrs = $tron->transferTrc10($addr, $amount, $ContractAddress, $main_address, $priv, $decimals);

                } else {
                    $sendrs = $tron->sendTransaction($addr, $amount, $main_address, $priv);
                }

                if ($sendrs['result'] && $sendrs['txid'] && $aid) {
                    $hash = $sendrs['txid'];
                    M('Myzc')->where(array('id' => $aid))->save(array('txid' => $hash, 'hash' => $hash));
                }
                $this->success('You have the success of the coin, background audit will automatically go out!' . $mum);
            }
            $this->success('You have successfully raised the coins and will automatically transfer them out after the background review!');
        }
        //tron Ends
        //cryptoapis Starts
        if ($CoinInfo['type'] == 'cryptoapis') {

            //cryptoapis Wallet Withdrawal
            $rs = [];
            $rs[] = $mo->table('codono_user_coin')->where(array('userid' => $uid))->setDec($coin, $num);
            $rs[] = $aid = $mo->table('codono_myzc')->add(array('userid' => $uid, 'username' => $addr, 'coinname' => $coin, 'network' => $network, 'num' => $num, 'fee' => $fee, 'mum' => $mum, 'addtime' => time(), 'status' => $auto_status));

            if ($fee && $fee_user['userid'] != 0) {

                $rs[] = $mo->table('codono_myzc_fee')->add(array('userid' => $fee_user['userid'], 'txid' => $aid, 'username' => $zc_user, 'coinname' => $coin, 'num' => $num, 'fee' => $fee, 'mum' => $mum, 'type' => 2, 'addtime' => time(), 'status' => 1));

                if ($mo->table('codono_user_coin')->where(array('userid' => $zc_user))->find()) {
                    $rs[] = $mo->table('codono_user_coin')->where(array('userid' => $zc_user))->setInc($coin, $fee);
                    $rs[] = $mo->table('codono_invit')->add(array('coin' =>$coin,'userid' => $fee_user['userid'], 'invit' => $uid, 'name' => 'Withdrawal Fees', 'type' => $coin . '_withdrawalFees', 'num' => $num, 'mum' => $mum, 'fee' => $fee, 'addtime' => time(), 'status' => 1));
                }
            }


            if ($auto_status) {
                $cryptoapi_config = array(
                    'api_key' => cryptString($CoinInfo['dj_mm'], 'd'),
                    'network' => $CoinInfo['network'],
                );
                $cryptoapi = CryptoApis($cryptoapi_config);
                $supportedCryptoApisChains = $cryptoapi->allowedSymbols();
                if (!in_array($network, $supportedCryptoApisChains)) {
                    $this->success('Withdrawal has been processed!');
                }

                $contract_address = $contract = $CoinInfo['dj_yh'] ?: null;//Contract Address
                $blockchain = $network;
                $walletId = $CoinInfo['dj_zj'];
                $context = $uid;
                $main_address = $CoinInfo['codono_coinaddress'];
                $amount = (double)$mum;
                $to_address = $addr;
                $tx_note = md5($uid . $coin . $network . $mum . $aid . $contract);
                if ($contract_address) {
                    //Contract Address transfer out
                    $sendrs = $cryptoapi->withdraw($blockchain, $walletId, $main_address, $to_address, $amount, $tx_note, $context, $contract_address);
                } else {

                    $sendrs = $cryptoapi->withdraw($blockchain, $walletId, $main_address, $to_address, $amount, $tx_note, $context);

                }

                if ($sendrs && $aid) {
                    $memo = $sendrs->transactionRequestId;
                    M('Myzc')->where(array('id' => $aid))->save(array('memo' => $memo));
                }

            }
            $this->success('Withdrawal request successful and being reviewed!');

        }
        //cryptoapis Ends
        //substrate withdrawal starts
        if ($CoinInfo['type'] == 'substrate') {

            $rs = [];
            $rs[] = $mo->table('codono_user_coin')->where(array('userid' => $uid))->setDec($coin, $num);
            $rs[] = $aid = $mo->table('codono_myzc')->add(array('userid' => $uid, 'username' => $addr, 'coinname' => $coin, 'network' => $network, 'num' => $num, 'fee' => $fee, 'mum' => $mum, 'addtime' => time(), 'status' => $auto_status,
                'txid'=>'processing'));

            if ($fee && $fee_user['userid'] != 0) {

                $rs[] = $mo->table('codono_myzc_fee')->add(array('userid' => $fee_user['userid'], 'txid' => $aid, 'username' => $zc_user, 'coinname' => $coin, 'num' => $num, 'fee' => $fee, 'mum' => $mum, 'type' => 2, 'addtime' => time(), 'status' => 1));

                if ($mo->table('codono_user_coin')->where(array('userid' => $zc_user))->find()) {
                    $rs[] = $mo->table('codono_user_coin')->where(array('userid' => $zc_user))->setInc($coin, $fee);
                    $rs[] = $mo->table('codono_invit')->add(array('coin' =>$coin,'userid' => $fee_user['userid'], 'invit' => $uid, 'name' => 'Withdrawal Fees', 'type' => $coin . '_withdrawalFees', 'num' => $num, 'mum' => $mum, 'fee' => $fee, 'addtime' => time(), 'status' => 1));
                }
            }


            if ($auto_status) {

                $substrate_config=[
                    'host'=>$CoinInfo['dj_zj'],
                    'port'=>$CoinInfo['dj_dk'],
                    'api_key'=>cryptString($CoinInfo['dj_mm'],'d'),
                    'decimals'=>$CoinInfo['cs_qk'],
                ];

                $substrate=Substrate($substrate_config);
                $substrate_amount=$substrate->amount_encode($mum);

                $request_sent=json_decode($substrate->withdraw($addr,$substrate_amount,$aid),true);

                $this->success('You have the success of the coin, background audit will automatically go out!' );
            }
            $this->success('You have successfully raised the coins and will automatically transfer them out after the background review!');

        }

        //substrate withdrawal ends
        //blockgum withdrawal starts
        if ($CoinInfo['type'] == 'blockgum') {

            $rs = [];
            $rs[] = $mo->table('codono_user_coin')->where(array('userid' => $uid))->setDec($coin, $num);
            $rs[] = $aid = $mo->table('codono_myzc')->add(array('userid' => $uid, 'username' => $addr, 'coinname' => $coin, 'network' => $network, 'num' => $num, 'fee' => $fee, 'mum' => $mum, 'addtime' => time(), 'status' => $auto_status,
                'txid'=>'processing'));

            if ($fee && $fee_user['userid'] != 0) {

                $rs[] = $mo->table('codono_myzc_fee')->add(array('userid' => $fee_user['userid'], 'txid' => $aid, 'username' => $zc_user, 'coinname' => $coin, 'num' => $num, 'fee' => $fee, 'mum' => $mum, 'type' => 2, 'addtime' => time(), 'status' => 1));

                if ($mo->table('codono_user_coin')->where(array('userid' => $zc_user))->find()) {
                    $rs[] = $mo->table('codono_user_coin')->where(array('userid' => $zc_user))->setInc($coin, $fee);
                    $rs[] = $mo->table('codono_invit')->add(array('coin' =>$coin,'userid' => $fee_user['userid'], 'invit' => $uid, 'name' => 'Withdrawal Fees', 'type' => $coin . '_withdrawalFees', 'num' => $num, 'mum' => $mum, 'fee' => $fee, 'addtime' => time(), 'status' => 1));
                }
            }


            if ($auto_status) {

                $pcoin = $CoinInfo['tokenof'] ?: $CoinInfo['name'];
                $contract_address=$CoinInfo['dj_yh']?:null;
                $decimals=$CoinInfo['cs_qk']?:18;
                $blockgum=blockgum($pcoin);
                $blockgum_amount=format_num($mum,$decimals);


                $request_sent=$blockgum->withdrawFromMain($addr,$blockgum_amount,$contract_address,$aid);

                clog('blockgum_withdrawal',[$request_sent,$addr,$blockgum_amount,$contract_address,$aid,$mum,$decimals]);

                $this->success('You have the success of the coin, background audit will automatically go out!' );
            }
            $this->success('You have successfully raised the coins and will automatically transfer them out after the background review!');

        }

        //blockgum withdrawal ends
        //esmart Starts
        if ($CoinInfo['type'] == 'esmart') {

            //esmart Wallet Withdrawal
            $rs = [];
            $rs[] = $mo->table('codono_user_coin')->where(array('userid' => $uid))->setDec($coin, $num);
            $rs[] = $aid = $mo->table('codono_myzc')->add(array('userid' => $uid, 'username' => $addr, 'coinname' => $coin, 'network' => $network, 'num' => $num, 'fee' => $fee, 'mum' => $mum, 'addtime' => time(), 'status' => $auto_status));

            if ($fee && $fee_user['userid'] != 0) {

                $rs[] = $mo->table('codono_myzc_fee')->add(array('userid' => $fee_user['userid'], 'txid' => $aid, 'username' => $zc_user, 'coinname' => $coin, 'num' => $num, 'fee' => $fee, 'mum' => $mum, 'type' => 2, 'addtime' => time(), 'status' => 1));

                if ($mo->table('codono_user_coin')->where(array('userid' => $zc_user))->find()) {
                    $rs[] = $mo->table('codono_user_coin')->where(array('userid' => $zc_user))->setInc($coin, $fee);
                    $rs[] = $mo->table('codono_invit')->add(array('coin' =>$coin,'userid' => $fee_user['userid'], 'invit' => $uid, 'name' => 'Withdrawal Fees', 'type' => $coin . '_withdrawalFees', 'num' => $num, 'mum' => $mum, 'fee' => $fee, 'addtime' => time(), 'status' => 1));
                }
            }


            if ($auto_status) {
                $esmart_config = array(
                    'host' => $CoinInfo['dj_zj'],
                    'port' => $CoinInfo['dj_dk'],
                    'coinbase' => $CoinInfo['codono_coinaddress'],
                    'password' => cryptString($CoinInfo['dj_mm'], 'd'),
                    'contract' => $CoinInfo['dj_yh'],
                    'rpc_type' => $CoinInfo['rpc_type'],
                    'public_rpc' => $CoinInfo['public_rpc'],
                );
                $Esmart = Esmart($esmart_config);
                if ($contract_address) {
                    //Contract Address transfer out
                    $zhuan['fromaddress'] = $CoinInfo['codono_coinaddress'];
                    $zhuan['toaddress'] = $addr;
                    $zhuan['token'] = $contract_address;
                    $zhuan['type'] = $coin;
                    $zhuan['amount'] = (double)$mum;
                    $zhuan['password'] = $CoinInfo['dj_mm'];
                    $sendrs = $Esmart->transferToken($zhuan['toaddress'], $zhuan['amount'], $zhuan['token'], $dj_decimal);
                } else {

                    $zhuan['amount'] = floatval($mum);
                    $zhuan['password'] = $CoinInfo['dj_mm'];
                    $sendrs = $Esmart->transferFromCoinbase($addr, floatval($mum));

                }

                if ($sendrs && $aid) {
                    $arr = json_decode($sendrs, true);
                    $hash = $arr['result'] ?: $arr['error']['message'];
                    M('Myzc')->where(array('id' => $aid))->save(array('txid' => $hash));
                    if ($hash) M()->execute("UPDATE `codono_myzc` SET  `hash` =  '$hash' WHERE id = '$aid' ");
                }
                $this->success('You have the success of the coin, background audit will automatically go out!' . $mum);
            }
            $this->success('You have successfully raised the coins and will automatically transfer them out after the background review!');

        }
        //esmart Ends


        //xrp starts
        if ($CoinInfo['type'] == 'xrp') {
            if ($dest_tag == 0) {
                $this->error('Make sure correct dest_tag is defined');
            }
            // Wallet Withdrawal
            $xrpData = C('coin')['xrp'];
            $xrpClient = XrpClient($xrpData['dj_zj'], $xrpData['dj_dk'], $xrpData['codono_coinaddress'], $xrpData['dj_mm']);

            $auto_status = ($CoinInfo['zc_zd'] && ($num < $CoinInfo['zc_zd']) ? 1 : 0);

            $rs = [];
            $rs[] = $mo->table('codono_user_coin')->where(array('userid' => $uid))->setDec($coin, $num);
            $rs[] = $aid = $mo->table('codono_myzc')->add(array('userid' => $uid, 'username' => $addr, 'dest_tag' => $dest_tag, 'coinname' => $coin, 'network' => $network, 'num' => $num, 'fee' => $fee, 'mum' => $mum, 'addtime' => time(), 'status' => 0));

            if ($fee && $fee_user['userid'] != 0) {

                $rs[] = $mo->table('codono_myzc_fee')->add(array('userid' => $fee_user['userid'], 'txid' => $aid, 'username' => $zc_user, 'coinname' => $coin, 'num' => $num, 'fee' => $fee, 'mum' => $mum, 'type' => 2, 'addtime' => time(), 'status' => 1));

                if ($mo->table('codono_user_coin')->where(array('userid' => $zc_user))->find()) {
                    $rs[] = $mo->table('codono_user_coin')->where(array('userid' => $zc_user))->setInc($coin, $fee);
                    $rs[] = $mo->table('codono_invit')->add(array('coin' =>$coin,'userid' => $fee_user['userid'], 'invit' => $uid, 'name' => 'Withdrawal Fees', 'type' => $coin . '_withdrawalFees', 'num' => $num, 'mum' => $mum, 'fee' => $fee, 'addtime' => time(), 'status' => 1));
                }
            }


            if ($auto_status) {
                $sendrs = false;
                $sign = $xrpClient->sign($mum, $addr, $dest_tag);

                if (strtolower($sign['result']['status']) == 'success') {
                    $submit = $xrpClient->submit($sign['result']['tx_blob']);
                    if (strtolower($submit['result']['status']) == 'success') {
                        $hash = $submit['result']['tx_json']['hash'];
                        M('Myzc')->where(array('id' => $aid))->save(array('txid' => $hash));
                        if ($hash) M()->execute("UPDATE `codono_myzc` SET  `hash` =  '$hash' WHERE id = '$aid' ");
                        $this->success('Successfully transferred out');
                    } else {
                        M()->execute("UPDATE `codono_myzc` SET  `status` =  0 WHERE id = '$aid' ");
                    }
                } else {
                    $this->success('Your request is being processed!');
                }

            }
            $this->success('Your request is being processed!');


        }
        //xrp ends


        /* Offline Coins Manual withdrawal */
        if ($CoinInfo['type'] == 'offline') {
            $mo->startTrans();
            $rs = [];
            $rs[] = $mo->table('codono_user_coin')->where(array('userid' => $uid))->setDec($coin, $num);

            $rs[] = $zcid = $mo->table('codono_myzc')->add(array('userid' => $uid, 'username' => $addr, 'coinname' => $coin, 'network' => $network, 'txid' => md5($addr . $user_coin[$coin . 'b'] . time()), 'num' => $num, 'fee' => $fee, 'mum' => $mum, 'addtime' => time(), 'status' => 0));


            if ($fee && $fee_user['userid'] != 0) {

                $rs[] = $mo->table('codono_myzc_fee')->add(array('userid' => $fee_user['userid'], 'txid' => $zcid, 'username' => $zc_user, 'coinname' => $coin, 'num' => $num, 'fee' => $fee, 'mum' => $mum, 'type' => 2, 'addtime' => time(), 'status' => 1));

                if ($mo->table('codono_user_coin')->where(array('userid' => $zc_user))->find()) {
                    $rs[] = $mo->table('codono_user_coin')->where(array('userid' => $zc_user))->setInc($coin, $fee);
                    $rs[] = $mo->table('codono_invit')->add(array('coin' =>$coin,'userid' => $fee_user['userid'], 'invit' => $uid, 'name' => 'Withdrawal Fees', 'type' => $coin . '_withdrawalFees', 'num' => $num, 'mum' => $mum, 'fee' => $fee, 'addtime' => time(), 'status' => 1));
                }
            }

            if (check_arr($rs)) {
                $mo->commit();

                session('myzc_verify', null);
                $this->success(L('Transfer success!'));
            } else {
                $mo->rollback();
                $this->error('Transfer Failed!');
            }
        }
        //Offline manual withdrawal ends
        //Coinpayments starts
        if ($CoinInfo['type'] == 'coinpay') {

            $coinpay_condition[$qbdz] = $addr;
            if ($dest_tag != NULL && $dest_tag != 0) {
                $coinpay_condition[$coin . '_tag'] = $dest_tag;
            }
            if (!$mo->table('codono_user_coin')->where($coinpay_condition)->find()) {
                $dj_password = $CoinInfo['dj_mm'];
                $cps_api = CoinPay($dj_username, $dj_password, $dj_address, $dj_port, 5, array(), 1);
                $information = $cps_api->GetBasicInfo();
                $coinpay_coin = strtoupper($coin);

                $can_withdraw = 1;
                if ($information['error'] != 'ok' || !isset($information['result']['username'])) {
                    //         $this->error(L('Wallet link failure! Coinpayments'));
                    clog($coin, ' Wallet link failure! Coinpayments can not be connected at time:' . time() . '<br/>');
                    $can_withdraw = 0;
                }

                //TODO :Find a valid way to validate coin address
                if (strlen($addr) > 8) {
                    $valid_res = 1;
                } else {
                    $valid_res = 0;
                }

                if (!$valid_res) {
                    $this->error($addr . L(' It is not a valid address wallet!'));
                }

                $balances = $cps_api->GetAllCoinBalances();

                if ($balances['result'][$coinpay_coin]['balancef'] < $num) {
                    //$this->error(L('Can not be withdrawn due to system'));
                    clog($coin, ' Balance is lower than  ' . $num . ' at time:' . time() . '<br/>');
                    $can_withdraw = 0;
                }


                $mo->startTrans();
                $rs = [];
                //Reduce Withdrawers balance
                $rs[] = $mo->table('codono_user_coin')->where(array('userid' => $uid))->setDec($coin, $num);
                //Add entry of withdraw [zc] in database
                $rs[] = $aid = $mo->table('codono_myzc')->add(array('userid' => $uid, 'username' => $addr, 'dest_tag' => $dest_tag, 'coinname' => $coin, 'network' => $network, 'num' => $num, 'fee' => $fee, 'mum' => $mum, 'addtime' => time(), 'status' => $auto_status));

                if ($fee && $auto_status && $fee_user['userid'] != 0) {

                    $rs[] = $mo->table('codono_myzc_fee')->add(array('userid' => $fee_user['userid'], 'txid' => $aid, 'username' => $zc_user, 'coinname' => $coin, 'num' => $num, 'fee' => $fee, 'mum' => $mum, 'type' => 2, 'addtime' => time(), 'status' => 1));

                    if ($mo->table('codono_user_coin')->where(array('userid' => $zc_user))->find()) {
                        $rs[] = $mo->table('codono_user_coin')->where(array('userid' => $zc_user))->setInc($coin, $fee);
                        $rs[] = $mo->table('codono_invit')->add(array('coin' =>$coin,'userid' => $fee_user['userid'], 'invit' => $uid, 'name' => 'Withdrawal Fees', 'type' => $coin . '_withdrawalFees', 'num' => $num, 'mum' => $mum, 'fee' => $fee, 'addtime' => time(), 'status' => 1));

                    }
                }
                $the_status = false;
                if (check_arr($rs)) {
                    if ($auto_status && $can_withdraw == 1) {
                        $mo->commit();

                        $buyer_email = $this->userinfo['email'];//M('User')->where(array('id' => $uid))->getField('email');
                        $withdrawals = ['amount' => $mum,
                            'add_tx_fee' => 0,
                            'auto_confirm' => 1, //Auto confirm 1 or 0
                            'currency' => $coinpay_coin,
                            'address' => $addr,
                            //'dest_tag'=>$dest_tag,
                            'ipn_url' => SITE_URL . '/IPN/confirm',
                            'note' => $buyer_email];
                        if ($dest_tag != 0 && $dest_tag != NULL) {
                            $withdrawals['dest_tag'] = $dest_tag;
                        }

                        $the_withdrawal = $cps_api->CreateWithdrawal($withdrawals);


                        if ($the_withdrawal['error'] != 'ok') {
                            //pending_status
                            M('Myzc')->where(array('id' => $aid))->save(array('status' => 0));
                            $the_status = false;
                            $this->error('Your withdrawal request is sent to admin,' . $the_withdrawal['error']);

                        } else {
                            $the_status = true;
                            $cp_withdrawal_id = $the_withdrawal['result']['id'];
                            M('Myzc')->where(array('id' => $aid))->save(array('hash' => $cp_withdrawal_id));
                            //$this->success('Successful Withdrawal!');
                        }
                    }

                    $mo->commit();

                    session('myzc_verify', null);
                    if ($auto_status && $the_status && $can_withdraw == 1) {
                        $this->success('Successful Withdrawal!');
                    } else {
                        $this->success('Being Reviewed!');
                    }
                } else {
                    $mo->rollback();
                    $this->error('Withdrawal failure!');
                }
            }
        }
        //Coinpayments ends
        //WavesPlatform Starts
        if ($CoinInfo['type'] == 'waves') {

            $dj_password = $CoinInfo['dj_mm'];
            $can_withdraw = 1;
            $waves = WavesClient($dj_username, $dj_password, $dj_address, $dj_port, $dj_decimal, 5, array(), 1);
            $information = json_decode($waves->status(), true);
            if ($information['blockchainHeight'] && $information['blockchainHeight'] <= 0) {
                clog('waves_error', $coin . ' can not be connectted at time:' . time() . '<br/>');
                $can_withdraw = 0;
            }

            //TODO :Find a valid way to validate coin address
            if (strlen($addr) > 30) {
                $valid_res = 1;
            } else {
                $valid_res = 0;
            }

            if (!$valid_res) {
                $this->error($addr . L(' It is not a valid address wallet!'));
            }

            $balances = json_decode($waves->Balance($main_address, $dj_username), true);
            $dj_decimal = $dj_decimal ?: 8;
            $wave_main_balance = $waves->deAmount($balances['balance'], $dj_decimal);
            if ($wave_main_balance < $num) {

                clog('waves_error', $coin . ' main_address ' . $main_address . ' Balance is ' . $wave_main_balance . ' is' . $dj_decimal . ' lower than  ' . $num . ' at time:' . time() . ' ' . $dj_username . '<br/>');
                $can_withdraw = 0;
            }


            $mo->startTrans();
            $rs = [];
            //Reduce Withdrawers balance
            $rs[] = $mo->table('codono_user_coin')->where(array('userid' => $uid))->setDec($coin, $num);
            //Add entry of withdraw [zc] in database
            $rs[] = $aid = $mo->table('codono_myzc')->add(array('userid' => $uid, 'username' => $addr, 'coinname' => $coin, 'network' => $network, 'num' => $num, 'fee' => $fee, 'mum' => $mum, 'addtime' => time(), 'status' => $auto_status));

            if ($fee && $auto_status && $fee_user['userid'] != 0) {

                $rs[] = $mo->table('codono_myzc_fee')->add(array('userid' => $fee_user['userid'], 'txid' => $aid, 'username' => $zc_user, 'coinname' => $coin, 'num' => $num, 'fee' => $fee, 'mum' => $mum, 'type' => 2, 'addtime' => time(), 'status' => 1));

                if ($mo->table('codono_user_coin')->where(array('userid' => $zc_user))->find()) {
                    $rs[] = $mo->table('codono_user_coin')->where(array('userid' => $zc_user))->setInc($coin, $fee);
                    $rs[] = $mo->table('codono_invit')->add(array('coin' =>$coin,'userid' => $fee_user['userid'], 'invit' => $uid, 'name' => 'Withdrawal Fees', 'type' => $coin . '_withdrawalFees', 'num' => $num, 'mum' => $mum, 'fee' => $fee, 'addtime' => time(), 'status' => 1));

                }
            }
            $the_status = false;
            if (check_arr($rs)) {
                if ($auto_status && $can_withdraw == 1) {
                    $mo->commit();


                    $wavesend_response = $waves->Send($main_address, $addr, $mum, $dj_username);
                    $the_withdrawal = json_decode($wavesend_response, true);
                    if ($the_withdrawal["error"]) {
                        $the_status = false;
                        clog('waves_error', json_encode($the_withdrawal));
                        //pending_status
                        M('Myzc')->where(array('id' => $aid))->save(array('status' => 0));
                        $this->error('Your withdrawal request is sent to admin,' . $the_withdrawal["message"]);

                    } else {
                        $the_status = true;
                        M('Myzc')->where(array('id' => $aid))->save(array('txid' => $the_withdrawal['id'], 'hash' => $the_withdrawal['signature']));
                        //$this->success('Successful Withdrawal!');
                    }
                }

                $mo->commit();

                session('myzc_verify', null);
                if ($auto_status && $the_status && $can_withdraw == 1) {
                    $this->success('Successful Withdrawal!');
                } else {
                    $this->success('Being Reviewed!');
                }
            } else {
                $mo->rollback();
                $this->error('Withdrawal failure!');
            }

        }
        //WavesPlatform Ends

        //BLOCKIO starts
        if ($CoinInfo['type'] == 'blockio') {
            $dj_password = $CoinInfo['dj_mm'];
            $block_io = BlockIO($dj_username, $dj_password, $dj_address, $dj_port, 5, array(), 1);
            $json = $block_io->get_balance();
            $can_withdraw = 1;
            if (!isset($json->status) || $json->status != 'success') {
                //$this->error(L('Wallet link failure! blockio'));
                clog('blockio', 'Blockio Could not be connected at ' . time() . '<br/>');
                $can_withdraw = 0;
            }

            $valid_res = $block_io->validateaddress($addr);

            if (!$valid_res) {
                $this->error($addr . ' :' . L('Not valid address!'));
            }


            if ($json->data->available_balance < $num) {
                //$this->error(L('Wallet balance of less than'));
                clog('blockio', 'Blockio Balance is lower than  ' . $num . ' at time:' . time() . '<br/>');
                $can_withdraw = 0;
            }

            $mo->startTrans();
            $rs = [];
            //Reduce Withdrawers balance
            $rs[] = $mo->table('codono_user_coin')->where(array('userid' => $uid))->setDec($coin, $num);
            //Add entry of withdraw [zc] in database
            $rs[] = $aid = $mo->table('codono_myzc')->add(array('userid' => $uid, 'username' => $addr, 'coinname' => $coin, 'network' => $network, 'num' => $num, 'fee' => $fee, 'mum' => $mum, 'addtime' => time(), 'status' => $auto_status));

            if ($fee && $auto_status && $fee_user['userid'] != 0) {

                $rs[] = $mo->table('codono_myzc_fee')->add(array('userid' => $fee_user['userid'], 'txid' => $aid, 'username' => $zc_user, 'coinname' => $coin, 'num' => $num, 'fee' => $fee, 'mum' => $mum, 'type' => 2, 'addtime' => time(), 'status' => 1));

                if ($mo->table('codono_user_coin')->where(array('userid' => $zc_user))->find()) {
                    $rs[] = $mo->table('codono_user_coin')->where(array('userid' => $zc_user))->setInc($coin, $fee);
                    $rs[] = $mo->table('codono_invit')->add(array('coin' =>$coin,'userid' => $fee_user['userid'], 'invit' => $uid, 'name' => 'Withdrawal Fees', 'type' => $coin . '_withdrawalFees', 'num' => $num, 'mum' => $mum, 'fee' => $fee, 'addtime' => time(), 'status' => 1));

                }
            }

            if (check_arr($rs)) {
                if ($auto_status && $can_withdraw == 1) {
                    $mo->commit();


                    $sendrs = $block_io->withdraw(array('amounts' => $mum, 'to_addresses' => $addr));
                    $flag = 0;
                    if ($sendrs) {
                        if (isset($sendrs->status) && ($sendrs->status == 'success')) {
                            $flag = 1;
                        }
                    } else {
                        $flag = 0;
                    }
                    if (!$flag) {
                        $this->error('wallet server  Withdraw currency failure,Manually turn out');
                    } else {
                        $this->success('Successful Withdrawal!');
                    }
                }

                $mo->commit();

                session('myzc_verify', null);
                if ($auto_status && $can_withdraw == 1) {
                    $this->success('Successful Withdrawal!');
                } else {
                    $this->success('Application is successful Withdrawal,Please wait for the review!');
                }
            } else {
                $mo->rollback();
                $this->error('Withdrawal failure!');
            }

        }
        //BlockIO ends
        //cryptonote starts
        if ($CoinInfo['type'] == 'cryptonote') {

            $dj_password = $CoinInfo['dj_mm'];
            $cryptonote = CryptoNote($dj_address, $dj_port);

            //check if withdrawal  addresss and payid are valid
            $valid_addr = $cryptonote->checkAddress($addr);
            $valid_payid = $cryptonote->checkPaymentId($dest_tag);
            if (!$valid_addr) {
                $this->error('Please check if your address ' . $addr . ' is valid');
            }

            if (!$valid_payid) {
                $this->error('Please check if your paymentId ' . $dest_tag . ' is valid');
            }

            $open_wallet = $cryptonote->open_wallet($dj_username, $dj_password);

            $json = json_decode($cryptonote->get_height());
            $can_withdraw = 1;
            if (!isset($json->height) || $json->error != 0 || !$open_wallet) {
                clog('CryptoNote', $coin . ' Could not be connected at ' . time() . '<br/>');
                $can_withdraw = 0;
            }

            $bal_info = json_decode($cryptonote->getBalance());
            $crypto_balance = $cryptonote->deAmount($bal_info->available_balance);

            if ($crypto_balance < $num) {
                clog('CryptoNote ', $coin . ' Balance is lower than  ' . $num . ' at time:' . time() . '<br/>');
                $can_withdraw = 0;
                $auto_status = 0;
            }


            $mo->startTrans();
            $rs = [];
            //Reduce Withdrawers balance
            $rs[] = $mo->table('codono_user_coin')->where(array('userid' => $uid))->setDec($coin, $num);
            //Add entry of withdraw [zc] in database
            $rs[] = $aid = $mo->table('codono_myzc')->add(array('userid' => $uid, 'username' => $addr, 'dest_tag' => $dest_tag, 'coinname' => $coin, 'network' => $network, 'num' => $num, 'fee' => $fee, 'mum' => $mum, 'addtime' => time(), 'status' => $auto_status));

            if ($fee && $auto_status && $fee_user['userid'] != 0) {

                $rs[] = $mo->table('codono_myzc_fee')->add(array('userid' => $fee_user['userid'], 'txid' => $aid, 'username' => $zc_user, 'coinname' => $coin, 'num' => $num, 'fee' => $fee, 'mum' => $mum, 'type' => 2, 'addtime' => time(), 'status' => 1));

                if ($mo->table('codono_user_coin')->where(array('userid' => $zc_user))->find()) {
                    $rs[] = $mo->table('codono_user_coin')->where(array('userid' => $zc_user))->setInc($coin, $fee);
                    $rs[] = $mo->table('codono_invit')->add(array('coin' =>$coin,'userid' => $fee_user['userid'], 'invit' => $uid, 'name' => 'Withdrawal Fees', 'type' => $coin . '_withdrawalFees', 'num' => $num, 'mum' => $mum, 'fee' => $fee, 'addtime' => time(), 'status' => 1));
                }
            }

            if (check_arr($rs)) {
                if ($auto_status && $can_withdraw == 1) {
                    $mo->commit();


                    $send_amt = format_num($num, 8);
                    $transData = [
                        [
                            "amount" => $send_amt,
                            "address" => $addr
                        ]
                    ];
                    $sendrs = json_decode($cryptonote->transfer($transData, $dest_tag));


                    $flag = 0;


                    clog($coin . '_cryptno_error', json_encode($sendrs));
                    if ($sendrs->error == 0) {
                        if (isset($sendrs->tx_hash) && isset($sendrs->tx_key)) {
                            $flag = 1;
                        }

                    } else {
                        $flag = 0;
                    }
                    if (!$flag) {
                        //We have sent your withdrawal request to admin
                        $can_withdraw = 0;

                    }
                }
                $hash = $sendrs->tx_key;
                $txid = $sendrs->tx_hash;
                if ($hash && $txid) {
                    M('Myzc')->where(array('id' => $aid))->save(array('txid' => $txid, 'hash' => $hash, 'status' => 1));
                }
                $mo->commit();

                session('myzc_verify', null);
                if ($auto_status && $can_withdraw == 1) {

                    $this->success('Successful Withdrawal');
                } else {
                    $this->success('Application is successful Withdrawal,Please wait for the review!');
                }
            } else {
                $mo->rollback();
                $this->error('Withdrawal failure!');
            }

        }
        //CryptoNote Ends
        //Bitcoin Type Starts
        if ($CoinInfo['type'] == 'qbb') {
            $dj_password = $CoinInfo['dj_mm'];
            $CoinClient = CoinClient($dj_username, $dj_password, $dj_address, $dj_port, 5, array(), 1);
            $can_withdraw = 1;

            $auto_status = ($CoinInfo['zc_zd'] && ($num < $CoinInfo['zc_zd']) ? 1 : 0);

            $json = $CoinClient->getinfo();

            if (!isset($json['version']) || !$json['version']) {

                clog($coin, "$coin Could not be connected at " . time() . "<br/>");
                $can_withdraw = 0;
            }

            if($can_withdraw==1){

                $valid_res = $CoinClient->validateaddress($addr);

                if (!$valid_res['isvalid']) {
                    $this->error($addr . ' ' . L('It is not a valid address wallet!'));
                }
                $daemon_balance = $CoinClient->getbalance();

                if ($daemon_balance < $num) {
                    //$this->error(L('Wallet balance of less than'));
                    clog($coin, " $coin:Low wallet balance: " . time() . "<br/>");
                    $can_withdraw = 0;
                }
            }



            $mo->startTrans();
            $rs = [];
            //Reduce Users balance
            $rs[] = $mo->table('codono_user_coin')->where(array('userid' => $uid))->setDec($coin, $num);
            //Add entry of withdraw [zc] in database
            $rs[] = $aid = $mo->table('codono_myzc')->add(array('userid' => $uid, 'username' => $addr, 'coinname' => $coin, 'network' => $network, 'num' => $num, 'fee' => $fee, 'mum' => $mum, 'addtime' => time(), 'status' => $auto_status));

            if ($fee && $auto_status && $fee_user['userid'] != 0) {

                $rs[] = $mo->table('codono_myzc_fee')->add(array('userid' => $fee_user['userid'], 'txid' => $aid, 'username' => $zc_user, 'coinname' => $coin, 'num' => $num, 'fee' => $fee, 'mum' => $mum, 'type' => 2, 'addtime' => time(), 'status' => 1));

                if ($mo->table('codono_user_coin')->where(array('userid' => $zc_user))->find()) {
                    $rs[] = $mo->table('codono_user_coin')->where(array('userid' => $zc_user))->setInc($coin, $fee);
                    $rs[] = $mo->table('codono_invit')->add(array('coin' =>$coin,'userid' => $fee_user['userid'], 'invit' => $uid, 'name' => 'Withdrawal Fees', 'type' => $coin . '_withdrawalFees', 'num' => $num, 'mum' => $mum, 'fee' => $fee, 'addtime' => time(), 'status' => 1));
                }
            }

            if (check_arr($rs)) {

                if ($auto_status && $can_withdraw == 1) {
                    $mo->commit();

                    if (strpos($dj_address, 'new') !== false) {
                        $send_amt = bcadd($mum, 0, 5);

                    } else {
                        $send_amt = (double)bcadd($mum, 0, 5);
                    }


                    $contract=$CoinInfo['contract'];
                    if($contract){
                        $sendrs = $CoinClient->token('send',$contract,$addr, (double)$send_amt);
                    }else{
                        $sendrs = $CoinClient->sendtoaddress($addr, $send_amt);
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
                        //Wallet Server  Withdraw failure:'.$sendrs
                        $mo->rollback();
                        $this->error('Wallet Server  Withdraw failure:' . ($sendrs));
                    } else {
                        M('Myzc')->where(array('id' => $aid))->save(array('txid' => $sendrs));
                        $this->success('Successful Withdrawal!');
                    }
                }

                if ($auto_status && $can_withdraw == 1) {
                    $mo->commit();

                    session('myzc_verify', null);

                    $this->success('Successful Withdrawal!');
                } else {
                    //put it in pending_status status=0
                    M('Myzc')->where(array('id' => $aid))->save(array('status' => 0));
                    $mo->commit();

                    session('myzc_verify', null);
                    $this->success('Withdrawal application is successful,Please wait for the review!');
                }
            } else {
                $mo->rollback();
                $this->error('Withdrawal failure!');
            }

        }
        //Bitcoin Type Ends
    }


    public function updateBalance()
    {
        $input = I('post.');
        $this->auth($input);
        $form = $input;
        $username = $form['user_id'];
        $amount = $form['amount'];
        $coin = $form['coin'];
        $action = $form['action'];

        $amount = format_num($amount);
        if (!$coin) {
            exit(json_encode(['status' => 0, 'msg' => 'Please enter coin as parameter:' . $coin]));
        }
        if ($action != 'plus' && $action != 'minus') {
            exit(json_encode(['status' => 0, 'msg' => 'Please enter valid action plus or minus:' . $action]));
        }


        if (!check($amount, 'double')) {
            exit(json_encode(['status' => 0, 'msg' => 'Please enter a valid amount as parameter:' . $amount]));
        }
        $user_info = M('User')->where(array('username' => $username))->getField('id');
        $uid = $user_info;

        if (!$uid) {
            exit(json_encode(['status' => 0, 'msg' => 'username does not exist:' . $username]));
        }
        $isValidCoin = $this->isValidCoin($coin);
        if (!$isValidCoin) {
            exit(json_encode(['status' => 0, 'msg' => 'E:8:Please enter a valid coin as parameter:' . $coin]));
        }
        $auto_memo = $form['amount'] . '-' . $coin . '-' . $action . '-' . $uid . '-' . time();
        $memo = $form['memo'] ?: $auto_memo;
        //start process
        {
            if ($action == 'plus') {
                $type = 1;
            } else {
                $type = 2;
            }

            $userid = $_insert_Array['userid'] = $uid;
            $_insert_Array['adminid'] = 9999;
            $_insert_Array['type'] = $type;
            $coin = $_insert_Array['coin'] = $coin;
            $amount = $_insert_Array['amount'] = $amount;
            $memo = $_insert_Array['memo'] = $memo;
            $_insert_Array['addtime'] = time();
            $_insert_Array['internal_note'] = $auto_memo;
            $hash = $_insert_Array['internal_hash'] = md5(time() . $userid . $coin . 'admin_activity' . $this->generateRandomString());

            $if_already_hash = M('Activity')->where(array('internal_hash' => $hash))->find();
            if ($if_already_hash) {
                $this->error('Caution:Similar transaction already added');
            }

            $_insert_Array['txid'] = $txid = md5(time() . $hash . $coin . 'admin_activity');


            if ($coin != C('coin')[$coin]['name']) {
                $this->error('No such coin:' . $coin);
            }
            $user = M('User')->where(array('id' => $userid))->find();
            if ($userid != $user['id']) {
                $this->error('No such user found');
            }

            $tos = SHORT_NAME;
            $coind = $coin . 'd';
            $mo = M();
            $query = "SELECT `$coin`,`$coind` FROM `codono_user_coin` WHERE `userid` = $userid";
            $res_bal = $mo->query($query);
            $user_coin_bal = $res_bal[0];

            //Income
            if ($type == 1) {
                $num_a = $user_coin_bal[$coin];
                $num_b = $user_coin_bal[$coin . 'd'];
                $num = bcadd($num_a, $num_b, 8);
                $mum_a = bcadd($num_a, $amount, 8);
                $mum_b = $num_b;
                $mum = bcadd($mum_a, $mum_b, 8);

                $mo->startTrans();
                if ($amount > 0) {
                    $rs[] = $mo->table('codono_user_coin')->where(array('userid' => $userid))->setInc($coin, $amount);
                }
                $rs[] = $staff_credit = M('Activity')->add($_insert_Array);
                $rs[] = $zrid = M('myzr')->add(array('userid' => $userid, 'type' => 'admin', 'username' => SHORT_NAME, 'coinname' => $coin, 'fee' => 0, 'txid' => $txid, 'num' => $amount, 'mum' => $amount, 'addtime' => time(), 'status' => 1, 'memo' => $memo));

                // Finance Entry
                $finance_array = array('userid' => $userid, 'coinname' => $coin, 'num_a' => $user_coin_bal[$coin], 'num_b' => $amount, 'num' => $num, 'fee' => 0, 'type' => 1, 'name' => 'Staff Credit', 'nameid' => $zrid, 'remark' => 'admin_activity', 'move' => $txid, 'addtime' => time(), 'status' => 1, 'mum' => $mum, 'mum_a' => $mum_a, 'mum_b' => $mum_b);

                $rs[] = $mo->table('codono_finance')->add($finance_array);
                if (check_arr($rs)) {
					$mo->commit();
                    $this->success(L('Added!'));
                } else {
                    $mo->rollback();
                    $this->error(L('Sorry could not add!'));
                }


            }
            //Do spend
            if ($type == 2) {
                if ($user_coin_bal[$coin] < $amount) {
                    $this->error('User has less balance ' . $user_coin_bal[$coin] . ' < ' . $amount);

                }
                $num_a = $user_coin_bal[$coin];
                $num_b = $user_coin_bal[$coin . 'd'];
                $num = bcadd($num_a, $num_b, 8);
                $mum_a = bcsub($num_a, $amount, 8);
                $mum_b = $num_b;
                $mum = bcadd($mum_a, $mum_b, 8);

                $mo->startTrans();
                if ($amount > 0) {
                    $rs[] = $mo->table('codono_user_coin')->where(array('userid' => $userid))->setDec($coin, $amount);
                }

                $rs[] = $staff_spend = M('Activity')->add($_insert_Array);


                $rs[] = $zcid = M('myzc')->add(array('userid' => $userid, 'type' => 'admin', 'username' => $tos, 'coinname' => $coin, 'fee' => 0, 'txid' => $txid, 'num' => $amount, 'mum' => $amount, 'addtime' => time(), 'status' => 1, 'memo' => $memo));


                // Finance Entry
                $finance_array = array('userid' => $userid, 'coinname' => $coin, 'num_a' => $user_coin_bal[$coin], 'num_b' => $amount, 'num' => $num, 'fee' => 0, 'type' => 2, 'name' => 'Staff Spent', 'nameid' => $zcid, 'remark' => 'admin_activity', 'move' => $txid, 'addtime' => time(), 'status' => 1, 'mum' => $mum, 'mum_a' => $mum_a, 'mum_b' => $mum_b);
                $rs[] = $mo->table('codono_finance')->add($finance_array);

                if (check_arr($rs)) {
                    $mo->commit();

                    header("Content-Type: application/json");
                    exit(json_encode(['status' => 1, 'msg' => 'Action performed:' . $action, 'additional' => $auto_memo]));
                } else {
                    $mo->rollback();
                    header("Content-Type: application/json");
                    exit(json_encode(['status' => 0, 'msg' => 'Failed :' . $action, 'additional' => $auto_memo]));
                }

            }
        }

    }

    public function queryBalance()
    {

        $input = I('post.');
        $this->auth($input);
        $form = $input;
        $username = $form['user_id'];

        $uid = M('User')->where(array('username' => $username))->getField('id');

        if (!$uid) {
            $this->error('No such user found');
        }

        $calc = $this->calcEstimatedAssetvalue($uid);
        $export = [];
        $export['conversion_coin'] = $calc['cc'];
        $export['networth'] = $calc['networth'];
        $export['coinList'] = $calc['pc'];
        header("Content-Type: application/json");
        exit(json_encode(['status' => 1, 'msg' => 'Balance for user:' . $username, 'data' => $export]));

    }

    public function queryWithdrawal()
    {
        $input = I('post.');
        $this->auth($input);
        $form = $input;
        $coin = $form['coin'];
        $coin = trim($coin);
        $where = [];
        $explorer = "";
        $username = $form['user_id'];
        if ($username) {
            $uid = M('User')->where(array('username' => $username))->getField('id');
            $where['userid'] = $uid;
        }

        if (isset($coin) && $coin != '') {
            $where['coinname'] = $export['coin'] = $coin;
        }
        $from_id = $form['from_id'];
        $Model = M('Myzc');
        $count = $Model->where($where)->count();
        $Page = new Page($count, 100);
        if (!$from_id || !check($from_id, 'd')) {

            $list = $Model->where($where)->order('id desc')->limit($Page->firstRow . ',' . $Page->listRows)->
            field('id,userid,username as address,addtime,coinname as coin,num as amount,txid,status')->select();
        } else {
            //order('sort asc,endtime desc,addtime desc')

            $list = $Model->where($where)->order('id asc')
                ->limit($from_id . ',' . 100)->
                field('id,userid,username as address,addtime,coinname as coin,num as amount,txid,status')->select();
        }
        foreach ($list as $txs) {
            $txs['username'] = username($txs['userid']);
            $export['transactions'][] = $txs;
        }


        header("Content-Type: application/json");
        exit(json_encode(['status' => 1, 'msg' => 'Withdrawal entries:', 'data' => $export]));
    }

    public function queryDeposit()
    {
        $input = I('post.');
        $this->auth($input);
        $form = $input;
        $coin = $form['coin'];
        $coin = trim($coin);
        $where = [];
        $export = [];
        $username = $form['user_id'];
        if ($username) {
            $uid = M('User')->where(array('username' => $username))->getField('id');
            $where['userid'] = $uid;
        }

        if (isset($coin) && $coin != '') {
            $where['coinname'] = $export['coin'] = $coin;
        }
        $from_id = $form['from_id'];
        $Model = M('Myzr');
        $count = $Model->where($where)->count();
        $Page = new Page($count, 100);
        if (!$from_id || !check($from_id, 'd')) {
            $list = $Model->where($where)->order('id desc')->limit($Page->firstRow . ',' . $Page->listRows)->
            field('id,userid,username as address,addtime,coinname as coin,num as amount,txid,status')->select();
        } else {

            $list = $Model->where($where)->order('id asc')
                ->limit($from_id . ',' . 100)->
                field('id,userid,username as address,addtime,coinname as coin,num as amount,txid,status')->select();
        }
        foreach ($list as $txs) {
            $txs['username'] = username($txs['userid']);
            $export['transactions'][] = $txs;
        }


        header("Content-Type: application/json");
        exit(json_encode(['status' => 1, 'msg' => 'Deposit entries:', 'data' => $export]));
    }

    function calcEstimatedAssetvalue($uid)
    {
        $CoinList = C('Coin');
        $UserCoin = M('UserCoin')->where(array('userid' => $uid))->find();
        $Market = C('market');
        $User_Selected_coin = $this->userinfo['fiat'];

        if (!isset($User_Selected_coin['fiat'])) {
            $conversion_coin = SYSTEMCURRENCY;
        } else {
            $conversion_coin = $User_Selected_coin['fiat'];
        }
        foreach ($Market as $k => $v) {
            $Market[$v['name']] = $v;
        }

        $usd['zj'] = 0;

        $cmcs = (APP_DEBUG ? null : S('cmcrates'));

        if (!$cmcs) {
            $cmcs = M('Coinmarketcap')->field(array('symbol', 'price_usd'))->select();
            S('cmcrates', $cmcs);
        }

        //Find


        $multiplier = 1;

        foreach ($cmcs as $ckey => $cval) {
            if (strtolower($conversion_coin) != 'usd' && $cval['symbol'] == strtoupper($conversion_coin)) {
                $multiplier = $cval['price_usd'];
            }
            $the_cms[strtolower($cval['symbol'])] = $cval['price_usd'];
        }
        $estimated_conversion = 0;
        foreach ($CoinList as $k => $v) {
            {
                if ($v['symbol']) {
                    continue;
                }


                $x_market = strtolower($v['name'] . '_' . $conversion_coin);
                if ($v['name'] == strtolower($conversion_coin)) {
                    $usd['ky'] = format_num($UserCoin[$v['name']], 2) * 1;

                }
                if (isset($the_cms[$v['name']])) {
                    $jia = $before = $the_cms[$v['name']];
                } else {
                    $jia = c('market')[$x_market]['new_price'];
                }
                $jia = $after = bcdiv((double)$jia, $multiplier, 8);
                $estimated_val = bcmul($UserCoin[$v['name']], $jia, 8);
                $print_coins[$v['name']] = array('name' => $v['name'], 'img' => $v['img'], 'title' => strtoupper($v['name']) . ' [ ' . ucfirst($v['title']) . ' ]', 'balance' => format_num($UserCoin[$v['name']], 8) * 1, 'conversion' => $jia * 1, 'estimated_value' => $estimated_val, 'type' => $v['type'], 'deposit_status' => $v['zr_jz'], 'withdrawal_status' => $v['zc_jz']);

                $estimated_conversion = bcadd($estimated_conversion, $estimated_val, 8);
            }
        }
        return array('cc' => $conversion_coin, 'pc' => $print_coins, 'networth' => $estimated_conversion);
    }

    private function isValidCoin($coin): bool
    {
        $coins = $this->coin_safe();

        if (array_key_exists(strtolower($coin), $coins)) {
            return true;
        } else {
            return false;
        }
    }


    private function coin_safe()
    {
        $coins = C('coin');

        $coinList = $coinList['coin_safe'] = [];

        foreach ($coins as $k => $v) {
            $coinList['coin'][$v['name']] = $v;
            if ($v['status'] == 1) {
                $coinList['coin_safe'][$v['name']] = ['id' => $v['id'], 'name' => $v['name'], 'title' => $v['title'], 'img' => $v['img'], 'type' => $v['type'], 'symbol' => $v['symbol'], 'zr_jz' => $v['zr_jz'], 'deposit' => $v['zr_jz'], 'zc_jz' => $v['zc_jz'], 'withdrawal' => $v['zc_jz'], 'confirmations' => $v['zr_dz'], 'explorer' => $v['js_wk'], 'zc_max' => $v['zc_max'], 'zc_min' => $v['zc_min'], 'zc_fee' => $v['zc_fee'], 'zc_flat_fee' => $v['zc_flat_fee'],'zc_coin'=>$v['zc_coin']?:$v['name']];
            }
        }

        return $coinList['coin_safe'];


    }

    private function generateRandomString($length = 25): string
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        $randomString = time() . '_' . $randomString;
        return md5($randomString);
    }

}//End of class
