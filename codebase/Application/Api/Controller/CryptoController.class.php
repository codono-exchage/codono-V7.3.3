<?php

namespace Api\Controller;

use Think\Page;

class CryptoController extends CommonController
{
    public function index()
    {
        $array = array('status' => 1, 'message' => 'Connected to Crypto API');
        echo json_encode($array);
    }


    public function MyWithdrawals()
    {
        $coin = I('get.coin', NULL, 'text');

        $uid = $this->userid();

        if (C('coin')[$coin]['name'] != strtolower($coin)) {
            $flag = 0;
        } else {
            $flag = 1;
        }

        $where['userid'] = $uid;
        if ($flag == 1) {
            $where['coinname'] = $coin;
        }

        $Model = M('Myzc');
        $count = $Model->where($where)->count();
        $Page = new Page($count, 10);
        $show = $Page->show();
        $list = $Model->where($where)->order('id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();

        if ($list) {
            $send_data['status'] = 1;
            $send_data['data'] = $list;
        } else {
            $send_data['status'] = 0;
            $send_data['data'] = null;
        }

        $this->showResponse($send_data);
    }


    public function Mydeposits()
    {
        $coin = I('get.coin', NULL, 'text');
        $uid = $this->userid();


        if (C('coin')[$coin]['name'] != strtolower($coin)) {
            $flag = 0;
        } else {
            $flag = 1;
        }


        $where['userid'] = $uid;
        if ($flag == 1) {
            $where['coinname'] = $coin;
        }
        $Model = M('Myzr');
        $count = $Model->where($where)->count();
        $Page = new Page($count, 25);
        $show = $Page->show();
        $list = $Model->where($where)->order('id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();

        $this->assign('list', $list);
        $this->assign('page', $show);
        if ($list) {
            $send_data['status'] = 1;
            $send_data['data'] = $list;
        } else {
            $send_data['status'] = 0;
            $send_data['data'] = null;
        }

        header('Content-type: application/json');
        echo (json_encode($send_data));
        exit;
    }

    public function maincoin()
    {
        $coin = SYSTEMCURRENCY;
        $this->depositaddress($coin);
    }
    private function getCryptoCoins()
    {
        $coins = C('coin');

        $filteredCoins = array_filter($coins, function ($coin) {
            return $coin['status'] == 1 && $coin['name'] != 'usd';
        });

        return $filteredCoins;
    }
    private function getCoinsFromConfig($filters)
    {
        // Fetch the array of coins from the configuration
        $coins = C('coin');

        // Initialize an array to store the matching coins
        $matchingCoins = [];

        // Iterate through the coins to find those that match all criteria
        foreach ($coins as $coin) {
            $isMatch = true;  // Assume the coin matches until proven otherwise

            // Check each filter condition
            foreach ($filters as $key => $value) {

                if (!isset($coin[$key]) || (string)$coin[$key] !== (string)$value) {
                    $isMatch = false;  // If any condition fails, the coin does not match
                    break;
                }
            }

            // If all conditions were met, add to results
            if ($isMatch) {
                $matchingCoins[] = $coin;
            }
        }

        return $matchingCoins;  // Return the array of matching coins
    }
    public function depositaddress($coin = NULL)
    {
        $uid = $this->userid();
        $wallet = $address = '';
        $show_qr = 1;

        if (C('coin')[$coin]) {
            $coin = trim($coin);
        } else {
            $coin = C('xnb_mr');
        }

        $this->assign('xnb', $coin);


        $user_coin = M('UserCoin')->where(array('userid' => $uid))->find();
        $user_coin[$coin] = format_num($user_coin[$coin], 8);
        $this->assign('user_coin', $user_coin);
        $CoinInfo = $this->getCoinsFromConfig(['name' => $coin])[0];
        $tokenof = $CoinInfo['tokenof'];
        $tokenofb = $tokenof . 'b';
        $this->assign('zr_jz', $CoinInfo['zr_jz']);


        $codono_getCoreConfig = codono_getCoreConfig();
        if (!$codono_getCoreConfig) {
            $this->error(L('Incorrect Core Config'));
        }


        if ($codono_getCoreConfig['codono_opencoin'] == 1 && $CoinInfo['type'] != 'offline') {
            if (!$CoinInfo['zr_jz']) {
                $wallet = L('The current ban into the currency!');
            } else {
                $qbdz = $coin . 'b';

                if (!$user_coin[$qbdz]) {

                    if ($CoinInfo['type'] == 'rgb') {
                        $wallet = md5(username() . $coin);
                        $rs = M('UserCoin')->where(array('userid' => $uid))->save(array($qbdz => $wallet));
                        $user_exists = M('User')->where(array('id' => $uid))->getField('id');

                        if (!$rs && !$user_exists) {
                            $this->error(L('Generate wallet address wrong!'));
                        }
                        if (!$rs && $user_exists) {

                            $ucoin[$qbdz] = $wallet;
                            $ucoin['userid'] = $user_exists;
                            $new_rs = M('UserCoin')->add($ucoin);
                            exit;
                        }
                    }

                    if ($CoinInfo['type'] == 'xrp') {
                        $address = $wallet = $CoinInfo['codono_coinaddress']; //Contract Address
                        $dest_tag = $user_coin[$coin . '_tag'];

                        if (isset($address)) {
                            if (!$dest_tag) {

                                $xrp_len = 9 - strlen($uid);
                                $min = pow(10, ($xrp_len - 1));
                                $max = pow(10, $xrp_len) - 1;
                                $xrp_str = mt_rand($min, $max);

                                $saveme[$coin . '_tag'] = $dest_tag = $uid . $xrp_str;

                                //TO add xrp_tag field in user_coin table if not exits
                                $dest_tag_field = $coin . '_tag';
                                $tag_sql = "SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE table_name = 'codono_user_coin' AND column_name = '$dest_tag_field'";
                                $if_tag_exists = M()->execute($tag_sql);

                                //Create a destination tag
                                if (!$if_tag_exists) {

                                    M()->execute("ALTER TABLE `codono_user_coin` ADD $dest_tag_field VARCHAR(200) NULL DEFAULT NULL COMMENT 'Tag for $coin'");
                                }

                                $rs = M('user_coin')->where(array('userid' => $uid))->save(array($coin . '_tag' => $dest_tag));

                                if ($rs) {
                                    $wallet = $address;
                                    $show_qr = 1;
                                } else {
                                    $wallet = L('Wallet System is currently offline 2! ' . $coin);
                                    $show_qr = 0;
                                }
                            }
                        } else {
                            $wallet = L('Wallet System is currently offline 2! ' . $coin);
                            $show_qr = 0;
                        }
                    }

                    //XRP ENDS
                    //Tron starts
                    if ($CoinInfo['type'] == 'tron') {
                        $contract = $CoinInfo['dj_yh']; //Contract Address
                        $tokenof = $CoinInfo['tokenof'];
                        if (!$contract) {
                            //Call the interface to generate a new wallet address

                            $tron = A('Tron')->newAccount();

                            if (is_array($tron)) {
                                $saveme[$qbdz] = $wallet = $tron['address_base58'];
                                $tron_info = $tron;
                                $tron_info['uid'] = $uid;
                                $tron_info['private_key'] = cryptString($tron['private_key']);
                                $rs[] = M('Tron')->add($tron_info);
                                $rs[] = M('UserCoin')->where(array('userid' => $uid))->save($saveme);
                            } else {
                                $wallet = L('Wallet System is currently offline 2! ' . $coin);
                                $show_qr = 0;
                            }
                        } else {

                            $rs1 = $user_coin;
                            $tokenof = $CoinInfo['tokenof'];
                            $tokenofb = $tokenof . 'b';


                            if ($rs1[$tokenofb]) {
                                $wallet = $rs1[$tokenofb];
                                $show_qr = 1;
                            } else {
                                //Call the interface to generate a new wallet address
                                $tron = A('Tron')->newAccount();
                                if ($tron) {
                                    $saveme[$qbdz] = $wallet = $tron['address_base58']; //token address
                                    $saveme[$tokenofb] = $wallet; //token parent address

                                    $tron_info = $tron;
                                    $tron_info['uid'] = $uid;
                                    $tron_info['private_key'] = cryptString($tron['private_key']);
                                    $rs[] = M('Tron')->add($tron_info);
                                    $rs[] = M('UserCoin')->where(array('userid' => $uid))->save($saveme);
                                } else {
                                    $wallet = L('Wallet System is currently offline 1! ' . $coin);
                                    $show_qr = 0;
                                }
                            }
                        }
                    }
                    //Tron  Ends
                    //Cryptoapis starts
                    if ($CoinInfo['type'] == 'cryptoapis') {

                        $cryptoapi_config = array(
                            'api_key' => cryptString($CoinInfo['dj_mm'], 'd'),
                            'network' => $CoinInfo['network'],
                        );

                        $cryptoapi = CryptoApis($cryptoapi_config);
                        $supportedCryptoApisChains = $cryptoapi->allowedSymbols();
                        if (!in_array($coin, $supportedCryptoApisChains) && !in_array($tokenof, $supportedCryptoApisChains)) {
                            $wallet = L('Wallet System is currently offline 2! ' . $coin);
                            $show_qr = 0;
                        }
                        $contract = $CoinInfo['dj_yh']; //Contract Address
                        $blockchain = $coin;
                        $walletId = $CoinInfo['dj_zj'];
                        $context = $uid;
                        $main_address = $CoinInfo['codono_coinaddress'];
                        if (!$contract) {
                            if ($address) {
                                if ($tokenof) {
                                    $saveme[$qbdz . 'b'] = $address;
                                } else {
                                    $saveme[$qbdz] = $address;
                                }

                                $rs = M('UserCoin')->where(array('userid' => $uid))->save($saveme);
                            } else {

                                $wallet = L('Wallet System is currently offline 2! ' . $coin);
                                $show_qr = 0;
                            }
                        } else {
                            //cryptoapis contract
                            $rs1 = $user_coin;
                            $tokenof = $CoinInfo['tokenof'];
                            $tokenofb = $tokenof . 'b';
                            if ($rs1[$tokenofb]) {
                                $wallet = $address = $rs1[$tokenofb];
                                $saveme[$qbdz] = $address;
                                $cryptoapi->createTokenForwarding($blockchain, $main_address, $address, $contract, $context);
                                $rs = M('UserCoin')->where(array('userid' => $uid))->save($saveme);
                            } else {
                                //Call the interface to generate a new wallet address

                                $wallet = $address = $cryptoapi->createAddress($blockchain, $walletId, $context, $main_address);

                                if ($address) {

                                    if ($tokenof) {
                                        $saveme[$tokenof . 'b'] = $address;
                                    } else {
                                        $saveme[$qbdz] = $address;
                                    }

                                    $cryptoapi->createTokenForwarding($blockchain, $main_address, $address, $contract, $context);
                                    $rs = M('UserCoin')->where(array('userid' => $uid))->save($saveme);
                                } else {

                                    $wallet = L('Wallet System is currently offline 1! ' . $coin);
                                    $show_qr = 0;
                                }
                            }
                        }
                    }
                    //cryptoapis  Deposit ends
                    //Substrate Deposit starts
                    if ($CoinInfo['type'] == 'substrate') {
                        $substrate_config = [
                            'host' => $CoinInfo['dj_zj'],
                            'port' => $CoinInfo['dj_dk'],
                            'api_key' => cryptString($CoinInfo['dj_mm'], 'd'),
                            'decimals' => $CoinInfo['cs_qk'],
                        ];
                        $substrate = Substrate($substrate_config);

                        $rs1 = M('UserCoin')->where(array('userid' => $uid))->find();
                        $tokenof = $CoinInfo['tokenof'];
                        $tokenofb = $tokenof . 'b';
                        if ($rs1[$tokenofb]) {
                            $wallet = $rs1[$tokenofb];
                            $saveme[$qbdz] = $wallet;
                            $rs = M('UserCoin')->where(array('userid' => $uid))->save($saveme);
                        } else {
                            $newAdd = json_decode($substrate->createAddress($uid));
                            if ((!$newAdd) || $newAdd->uid != $uid) {
                                $wallet = L('Wallet System is currently offline 1! ' . $coin);
                                $show_qr = 0;
                            } else {
                                $wallet_addr = $newAdd->address;
                                $wallet = $address = $wallet_addr;

                                if (!$address) {
                                    $this->error('Generate Wallet address error2!');
                                }

                                $saveme[$qbdz] = $wallet; //token address
                                $saveme[$tokenofb] = $wallet; //token parent address 									
                                $rs = M('UserCoin')->where(array('userid' => $uid))->save($saveme);

                                if (!$rs) {
                                    $this->error('Add error address wallet3!');
                                }
                                $show_qr = 1;
                            }
                        }
                    }
                    //Substrate Deposit ends
                    //blockgum Deposit starts
                    if ($CoinInfo['type'] == 'blockgum') {
                        $pcoin = $CoinInfo['tokenof'] ?: $CoinInfo['name'];
                        $blockgum = blockgum($pcoin);

                        $rs1 = M('UserCoin')->where(array('userid' => $uid))->find();
                        $tokenof = $CoinInfo['tokenof'];
                        $tokenofb = $tokenof . 'b';
                        if ($rs1[$tokenofb]) {
                            $wallet = $rs1[$tokenofb];
                            $saveme[$qbdz] = $wallet;
                            $rs = M('UserCoin')->where(array('userid' => $uid))->save($saveme);
                        } else {
                            $newAdd = $blockgum->createAddress($uid);
                            if ($newAdd['uid'] != $uid) {
                                $wallet = L('Wallet System is currently offline 1! ' . $coin);
                                $show_qr = 0;
                            } else {
                                $wallet_addr = $newAdd['address'];
                                $wallet = $address = $wallet_addr;

                                if (!$address) {
                                    $this->error('Generate Wallet address error2!');
                                }

                                $saveme[$qbdz] = $wallet; //token address
                                $saveme[$tokenofb] = $wallet; //token parent address 									
                                $rs = M('UserCoin')->where(array('userid' => $uid))->save($saveme);

                                if (!$rs) {
                                    $this->error('Add error address wallet3!');
                                }
                                $show_qr = 1;
                            }
                        }
                    }
                    //Blockgum Deposit ends
                    //Esmart Deposit starts
                    if ($CoinInfo['type'] == 'esmart') {
                        //Contract Address
                        $heyue = $CoinInfo['dj_yh']; //Contract Address
                        $esmart_config = array(
                            'host' => $CoinInfo['dj_zj'],
                            'port' => $CoinInfo['dj_dk'],
                            'coinbase' => $CoinInfo['codono_coinaddress'],
                            'password' => cryptString($CoinInfo['dj_mm'], 'd'),
                            'contract' => $CoinInfo['dj_yh'],
                            'rpc_type' => $CoinInfo['rpc_type'],
                            'public_rpc' => $CoinInfo['public_rpc'],
                        );
                        $Esmart = ESmart($esmart_config);

                        if (!$heyue) {
                            //Esmart
                            //Call the interface to generate a new wallet address
                            $wall_pass = ETH_USER_PASS; //cryptString($user_coin[$coin.'_pass'],'d');
                            $wallet = $Esmart->personal_newAccount($wall_pass);
                            if ($wallet) {
                                $saveme[$qbdz] = $wallet;
                                $rs = M('UserCoin')->where(array('userid' => $uid))->save($saveme);
                            } else {
                                $wallet = L('Wallet System is currently offline 2! ' . $coin);
                                $show_qr = 0;
                            }
                        } else {
                            //Esmart contract
                            $rs1 = M('UserCoin')->where(array('userid' => $uid))->find();
                            $tokenof = $CoinInfo['tokenof'];
                            $tokenofb = $tokenof . 'b';
                            if ($rs1[$tokenofb]) {
                                $wallet = $rs1[$tokenofb];
                                $saveme[$qbdz] = $wallet;
                                $rs = M('UserCoin')->where(array('userid' => $uid))->save($saveme);
                            } else {
                                //Call the interface to generate a new wallet address
                                $wall_pass = ETH_USER_PASS;
                                $wallet = $Esmart->personal_newAccount($wall_pass);
                                if ($wallet) {
                                    $saveme[$qbdz] = $wallet; //token address
                                    $saveme[$tokenofb] = $wallet; //token parent address 									
                                    $rs = M('UserCoin')->where(array('userid' => $uid))->save($saveme);
                                } else {

                                    $wallet = L('Wallet System is currently offline 1! ' . $coin);
                                    $show_qr = 0;
                                }
                            }
                        }
                    }

                    //Esmart ends

                    //CoinPayments starts
                    if ($CoinInfo['type'] == 'coinpay') {

                        $dj_username = $CoinInfo['dj_yh'];
                        $dj_password = $CoinInfo['dj_mm'];
                        $dj_address = $CoinInfo['dj_zj'];
                        $dj_port = $CoinInfo['dj_dk'];

                        $cps_api = CoinPay($dj_username, $dj_password, $dj_address, $dj_port, 5, array(), 1);
                        $information = $cps_api->GetBasicInfo();
                        $coinpay_coin = strtoupper($coin);


                        if ($information['error'] != 'ok' || !isset($information['result']['username'])) {
                            $wallet = L('Wallet System is currently offline 1!');
                            $show_qr = 0;
                        } else {
                            $show_qr = 1;

                            $ipn_url = SITE_URL . 'IPN/confirm';
                            // Prevent coinpayments to send duplicate address
                            $need_new_address = false;
                            do {
                                $transaction_response = $cps_api->GetCallbackAddressWithIpn($coinpay_coin, $ipn_url);
                                $dest_tag = $transaction_response['result']['dest_tag'] ?: 0;
                                $wallet_addr = $transaction_response['result']['address'];

                                $user_condition = [];
                                $user_condition[$coin . 'b'] = $wallet_addr;
                                if ($dest_tag != NULL || $dest_tag != 0) {
                                    $user_condition[$coin . '_tag'] = $dest_tag;
                                }

                                if (($user = M('UserCoin')->where($user_condition)->find())) {
                                    $need_new_address = true;
                                }
                            } while ($need_new_address == true);

                            // Prevent coinpayments to send duplicate address ends
                            if (!is_array($wallet_addr)) {
                                $wallet_ad = $wallet_addr;
                                if (!$wallet_ad) {
                                    $wallet = $wallet_addr;
                                } else {
                                    $wallet = $wallet_ad;
                                }
                            } else {
                                $wallet = $wallet_addr[0];
                            }

                            if (!$wallet) {
                                $this->error('Generate Wallet address error2!');
                            }

                            $rs = M('UserCoin')->where(array('userid' => $uid))->save(array($qbdz => $wallet));

                            if (!$rs) {
                                $this->error('Add error address wallet3!');
                            }
                        }
                    }
                    //CoinPayments  Ends
                    //WavesPlatform Starts

                    if ($CoinInfo['type'] == 'waves') {

                        $qbdz = 'wavesb';
                        $dj_username = $CoinInfo['dj_yh'];
                        $dj_password = $CoinInfo['dj_mm'];
                        $dj_address = $CoinInfo['dj_zj'];
                        $dj_port = $CoinInfo['dj_dk'];
                        $dj_decimal = $CoinInfo['cs_qk'];
                        $waves = WavesClient($dj_username, $dj_password, $dj_address, $dj_port, $dj_decimal, 5, array(), 1);
                        $waves_coin = strtoupper($coin);
                        $information = json_decode($waves->Status(), true);

                        if ($information['blockchainHeight'] && $information['blockchainHeight'] <= 0) {
                            $wallet = L('Wallet System is currently offline 1!');
                            $show_qr = 0;
                        } else {
                            $show_qr = 1;
                            $rs1 = M('UserCoin')->where(array('userid' => $uid))->find();
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
                                    $wallet = $wallet_addr;
                                } else {
                                    $wallet = $wallet_ad;
                                }
                            } else {
                                $wallet = $wallet_addr[0];
                            }

                            if (!$wallet) {
                                $show_qr = 0;
                                $wallet = L('Wallet System is currently offline 2!');
                            }
                            if ($show_qr == 1) {
                                $rs = M('UserCoin')->where(array('userid' => $uid))->save(array($qbdz => $wallet));
                                if (!$rs && $waves_good == 1) {
                                    $wallet = L('Wallet System is currently offline 3!');
                                }
                            }
                        }
                    }
                    //WavesPlatform Ends
                    //blockio starts
                    if ($CoinInfo['type'] == 'blockio') {

                        $dj_username = $CoinInfo['dj_yh'];
                        $dj_password = $CoinInfo['dj_mm'];
                        $dj_address = $CoinInfo['dj_zj'];
                        $dj_port = $CoinInfo['dj_dk'];

                        $block_io = BlockIO($dj_username, $dj_password, $dj_address, $dj_port, 5, array(), 1);
                        $json = $block_io->get_balance();

                        if (!isset($json->status) || $json->status != 'success') {
                            //$this->error(L('Wallet link failure! 1'));
                            $wallet = L('Wallet System is currently offline 2!');
                            $show_qr = 0;
                        } else {
                            $show_qr = 1;
                            $wallet_addr = $block_io->get_address_by_label(array('label' => username()))->data->address;

                            if (!is_array($wallet_addr)) {
                                $getNewAddressInfo = $block_io->get_new_address(array('label' => username()));
                                $wallet_ad = $getNewAddressInfo->data->address;


                                if (!$wallet_ad) {
                                    //$this->error('Generate Wallet address error1!');
                                    //$this->error($wallet_addr.'ok');
                                    $wallet = $wallet_addr;
                                } else {
                                    $wallet = $wallet_ad;
                                }
                            } else {
                                $wallet = $wallet_addr[0];
                            }

                            if (!$wallet) {
                                $this->error('Generate Wallet address error2!');
                            }

                            $rs = M('UserCoin')->where(array('userid' => $uid))->save(array($qbdz => $wallet));

                            if (!$rs) {
                                $this->error('Add error address wallet3!');
                            }
                        }
                    }
                    //blockio  Ends

                    //cryptonote starts
                    if ($CoinInfo['type'] == 'cryptonote') {
                        $dj_username = $CoinInfo['dj_yh'];
                        $dj_password = $CoinInfo['dj_mm'];
                        $dj_address = $CoinInfo['dj_zj'];
                        $dj_port = $CoinInfo['dj_dk'];
                        $cryptonote = CryptoNote($dj_address, $dj_port);
                        $open_wallet = $cryptonote->open_wallet($dj_username, $dj_password);
                        $json = $cryptonote->get_height();
                        $json = $cryptonote->get_height();

                        if (!isset($json->height) || $json->error != 0) {
                            $wallet = L('Wallet System is currently offline 2!');
                            $show_qr = 0;
                        } else {
                            $show_qr = 1;
                            $cryptofields = $coin . 'b';


                            $wallet_addr = $CoinInfo['codono_coinaddress'];
                            if (!is_array($wallet_addr)) {
                                $getNewAddressInfo = $cryptonote->create_address(0, username());
                                $wallet_ad = $getNewAddressInfo->address;


                                if (!$wallet_ad) {
                                    $wallet = $wallet_addr;
                                } else {
                                    $wallet = $wallet_ad;
                                }
                            } else {
                                $wallet = $wallet_addr[0];
                            }

                            if (!$wallet) {
                                $this->error('Generate Wallet address error2!');
                                //$wallet=L('Can not generate '.$coin.' wallet at the moment');
                            }

                            $dest_tag = $cryptonote->genPaymentId();
                            $dest_tag_field = $coin . '_tag';
                            $cryptonote_update_array[$qbdz] = $wallet;

                            $tag_sql = "SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE table_name = 'codono_user_coin' AND column_name = '$dest_tag_field'";
                            $if_tag_exists = M()->execute($tag_sql);

                            //Create a destination tag
                            if (!$if_tag_exists) {

                                M()->execute("ALTER TABLE `codono_user_coin` ADD $dest_tag_field VARCHAR(200) NULL DEFAULT NULL COMMENT 'Tag for xrp,xmr'");
                            }

                            if ($dest_tag != 0 || $dest_tag != NULL) {
                                $cryptonote_update_array[$dest_tag_field] = strval($dest_tag);
                                $dtag_sql = 'UPDATE `codono_user_coin` SET `' . $dest_tag_field . '` = "' . $dest_tag . '" WHERE `codono_user_coin`.`userid` = ' . $uid;
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
                    if ($CoinInfo['type'] == 'qbb') {
                        $dj_username = $CoinInfo['dj_yh'];
                        $dj_password = $CoinInfo['dj_mm'];
                        $dj_address = $CoinInfo['dj_zj'];
                        $dj_port = $CoinInfo['dj_dk'];
                        $CoinClient = CoinClient($dj_username, $dj_password, $dj_address, $dj_port, 5, array(), 1);
                        $json = $CoinClient->getinfo();

                        if (!isset($json['version']) || !$json['version']) {
                            //$this->error(L('Wallet link failure! 1'));
                            $wallet = L('Wallet System is currently offline 3!');
                            $show_qr = 0;
                        } else {
                            $show_qr = 1;
                            $wallet_addr = $CoinClient->getaddressesbyaccount(username());

                            if (!is_array($wallet_addr)) {
                                $wallet_ad = $CoinClient->getnewaddress(username());

                                if (!$wallet_ad) {
                                    $this->error('Generate Wallet address error1!');
                                } else {
                                    $wallet = $wallet_ad;
                                }
                            } else {
                                $wallet = $wallet_addr[0];
                            }

                            if (!$wallet) {
                                $this->error('Generate Wallet address error2!');
                            }
                            if ($tokenof) {
                                $saveme[$tokenof . 'b'] = $address;
                            } else {
                                $coin_address = strtolower($CoinInfo['name']) . 'b';
                                $saveme[$coin_address] = $address;
                            }

                            $rs = M('UserCoin')->where(array('userid' => $uid))->save($saveme);


                            if (!$rs) {
                                $this->error('Add error address wallet3!');
                            }
                        }
                    }
                } else {

                    $wallet = $user_coin[$coin . 'b'];
                }
            }
        } else {

            if (!$CoinInfo['zr_jz']) {
                $wallet = L('The current ban into the currency!');
            } else {

                $wallet = $CoinInfo['codono_coinaddress'];

                $cellphone = M('User')->where(array('id' => $uid))->getField('cellphone');
                $email = M('User')->where(array('id' => $uid))->getField('email');

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

        if ($wallet) {
            $send_data['status'] = 1;
            $send_data['wallet'] = $wallet;
            $send_data['showqr'] = $show_qr;
        } else {
            $send_data['status'] = 0;
            $send_data['wallet'] = 'Not available';
            $send_data['showqr'] = 0;
        }

        $this->showResponse($send_data);
    }
    public function coinbalance($coin = 'btc', $ver = 1,$network = '')
    {
        $coin = strip_tags(htmlspecialchars($coin));
        $network = strip_tags(htmlspecialchars($network)); // Sanitize network input

        $uid = $this->userid();
        $ret = [];

        $user = M('User')->where(array('id' => $uid))->find();
        $CoinList = $this->getCoinsFromConfig(['status' => 1, 'name' => $coin]);
		
        // Apply network filter if provided
        if ($network) {
        
        $networkList=$this->getCoinsFromConfig(['status' => 1, 'name' => $network]);
		
        $network_flat_fees=$networkList[0]['zc_flat_fee']?:0;
		$network_percent_fees = $networkList[0]['zc_fee']?:0;
        }
        $UserCoin = M('UserCoin')->where(array('userid' => $uid))->find();
        $Market = M('Market')->where(array('status' => 1))->select();
        $conversion_coin = $user['fiat'] ?: SYSTEMCURRENCY;

        $cmcs = (APP_DEBUG ? null : S('cmcrates'));

        if (!$cmcs) {
            $cmcs = M('Coinmarketcap')->field(array('symbol', 'price_usd'))->select();
            S('cmcrates', $cmcs);
        }
        $multiplier = 1;
        $the_cms = [];
        foreach ($cmcs as $ckey => $cval) {
            if (strtolower($conversion_coin) != 'usd' && strtoupper($cval['symbol']) == strtoupper($conversion_coin)) {
                $multiplier = $cval['price_usd'];
            }
            $the_cms[strtolower($cval['symbol'])] = $cval['price_usd'];
        }

        $market_type = [];
        foreach ($Market as $k => $v) {
            $Market[$v['name']] = $v;
            $keykey = explode('_', $v['name'])[0];
            $market_type[$keykey] = $v['name'];
        }

        $usd['zj'] = 0;
        $jia = 1;
        $mux = 2;
        $coinList = [];
        foreach ($CoinList as $k => $v) {
            if ($v['name'] == strtolower($conversion_coin)) {
                $usd['ky'] = format_num($UserCoin[$v['name']], 2) * 1;
                $usd['dj'] = format_num($UserCoin[$v['name'] . 'd'], 2) * 1;
            }
            $jia = $before = $the_cms[$v['name']] ?: 1;
            $jia = $after = bcdiv((float)$jia, $multiplier, 8); {

                $curMarketType = $market_type[$v['name']];

                if (isset($Market[$curMarketType])) {
                    //$jia = $Market[$curMarketType]['new_price'];
                    $marketid = $Market[$curMarketType]['id'];
                } else {
                    //$jia = 1;
                    $marketid = 0;
                }
                if ($v['type'] == 'rmb') {
                    $type = 'fiat';
                    $mux = 2;
                } else {

                    $type = 'crypto';
                    $mux = 8;
                    if ($v['name'] == 'usdt') {
                        $mux = 2;
                    }
                }

                $coinList[] = array('id' => $marketid, 'sort' => (int)$v['sort'], 'name' => $v['title'],'network' => $network?:$coin, 'symbol' => $v['name'], 'ico' => SITE_URL . 'Upload/coin/' . $v['img'], 'title' => $v['title'] . '(' . strtoupper($v['name']) . ')', 'total' => format_num($UserCoin[$v['name']] * 1, $mux), 'xnb' => format_num($UserCoin[$v['name']] * 1, $mux), 'xnbd' => format_num($UserCoin[$v['name'] . 'd'] * 1, $mux), 'xnbz' => format_num($UserCoin[$v['name']] + $UserCoin[$v['name'] . 'd'], $mux), 'jia' => $jia * 1, 'zhehe' => format_num(($UserCoin[$v['name']] + $UserCoin[$v['name'] . 'd']) * $jia, $mux), 'fees_percent' => (float)$v['zc_fee'], 'fees_flat' => (float)$v['zc_flat_fee']);
                $usd['zj'] = $usd['zj'] + (format_num(($UserCoin[$v['name']] + $UserCoin[$v['name'] . 'd']) * $jia, $mux) * 1);
            }
        }
		if($network){
		$coinList[0]['fees_percent']=$network_percent_fees;
		$coinList[0]['fees_flat']=$network_flat_fees;
		}

        $ret['conversion_coin'] = $conversion_coin;
        $ret['fiat'] = format_num(($UserCoin[$v['name']] + $UserCoin[$v['name'] . 'd']) * $jia, $mux);
        $ret['coinList'] = $coinList[0];
        if ($ver == 1) {
            $this->ajaxShow($coinList[0]);
        } else {
            $this->ajaxShow($ret);
        }
    }
    public function v1_coinbalance($coin = 'btc', $ver = 1)
    {
        $coin = strip_tags(htmlspecialchars($coin));
        $uid = $this->userid();
        $ret = [];

        $user = M('User')->where(array('id' => $uid))->find();
        $CoinList = $this->getCoinsFromConfig(['status' => 1, 'name' => $coin]);
        $UserCoin = M('UserCoin')->where(array('userid' => $uid))->find();
        $Market = M('Market')->where(array('status' => 1))->select();
        $conversion_coin = $user['fiat'] ?: SYSTEMCURRENCY;

        $cmcs = (APP_DEBUG ? null : S('cmcrates'));

        if (!$cmcs) {
            $cmcs = M('Coinmarketcap')->field(array('symbol', 'price_usd'))->select();
            S('cmcrates', $cmcs);
        }
        $multiplier = 1;
        $the_cms = [];
        foreach ($cmcs as $ckey => $cval) {
            if (strtolower($conversion_coin) != 'usd' && strtoupper($cval['symbol']) == strtoupper($conversion_coin)) {
                $multiplier = $cval['price_usd'];
            }
            $the_cms[strtolower($cval['symbol'])] = $cval['price_usd'];
        }

        $market_type = [];
        foreach ($Market as $k => $v) {
            $Market[$v['name']] = $v;
            $keykey = explode('_', $v['name'])[0];
            $market_type[$keykey] = $v['name'];
        }

        $usd['zj'] = 0;
        $jia = 1;
        $mux = 2;
        $coinList = [];
        foreach ($CoinList as $k => $v) {
            if ($v['name'] == strtolower($conversion_coin)) {
                $usd['ky'] = format_num($UserCoin[$v['name']], 2) * 1;
                $usd['dj'] = format_num($UserCoin[$v['name'] . 'd'], 2) * 1;
            }
            $jia = $before = $the_cms[$v['name']] ?: 1;
            $jia = $after = bcdiv((float)$jia, $multiplier, 8); {

                $curMarketType = $market_type[$v['name']];

                if (isset($Market[$curMarketType])) {
                    //$jia = $Market[$curMarketType]['new_price'];
                    $marketid = $Market[$curMarketType]['id'];
                } else {
                    //$jia = 1;
                    $marketid = 0;
                }
                if ($v['type'] == 'rmb') {
                    $type = 'fiat';
                    $mux = 2;
                } else {

                    $type = 'crypto';
                    $mux = 8;
                    if ($v['name'] == 'usdt') {
                        $mux = 2;
                    }
                }

                $coinList[] = array('id' => $marketid, 'sort' => (int)$v['sort'], 'name' => $v['title'], 'symbol' => $v['name'], 'ico' => SITE_URL . 'Upload/coin/' . $v['img'], 'title' => $v['title'] . '(' . strtoupper($v['name']) . ')', 'total' => format_num($UserCoin[$v['name']] * 1, $mux), 'xnb' => format_num($UserCoin[$v['name']] * 1, $mux), 'xnbd' => format_num($UserCoin[$v['name'] . 'd'] * 1, $mux), 'xnbz' => format_num($UserCoin[$v['name']] + $UserCoin[$v['name'] . 'd'], $mux), 'jia' => $jia * 1, 'zhehe' => format_num(($UserCoin[$v['name']] + $UserCoin[$v['name'] . 'd']) * $jia, $mux), 'fees_percent' => (float)$v['zc_fee'], 'fees_flat' => (float)$v['zc_flat_fee']);
                $usd['zj'] = $usd['zj'] + (format_num(($UserCoin[$v['name']] + $UserCoin[$v['name'] . 'd']) * $jia, $mux) * 1);
            }
        }
        $ret['conversion_coin'] = $conversion_coin;
        $ret['fiat'] = format_num(($UserCoin[$v['name']] + $UserCoin[$v['name'] . 'd']) * $jia, $mux);
        $ret['coinList'] = $coinList[0];
        if ($ver == 1) {
            $this->ajaxShow($coinList[0]);
        } else {
            $this->ajaxShow($ret);
        }
    }


    public function doWithdraw()
    {
        // Get JSON input and decode it
        $jsonInput = file_get_contents('php://input');
        $input = json_decode($jsonInput, true);

        // Validate and sanitize input
        $coin = isset($input['coin']) ? htmlspecialchars(strip_tags($input['coin'])) : null;
        $num = isset($input['num']) ? abs(filter_var($input['num'], FILTER_SANITIZE_NUMBER_FLOAT)) : 0;
        $addr = isset($input['addr']) ? htmlspecialchars(strip_tags($input['addr'])) : null;
        $paypassword = isset($input['paypassword']) ? htmlspecialchars(strip_tags($input['paypassword'])) : null;
        $dest_tag = isset($input['dest_tag']) ? htmlspecialchars(strip_tags($input['dest_tag'])) : null;
        $network = isset($input['network']) ? htmlspecialchars(strip_tags($input['network'])) : null;
        // Check for required fields


        $uid = $this->userid();

        if (!$uid) {
            $this->error(L('YOU_NEED_TO_LOGIN'));
        }
        if (!kyced()) {
            $this->error(L('Complete KYC First!'));
        }

        $num = format_num($num);

        if (!check($num, 'double')) {
            $this->error(L('Number format error!'));
        }

        if (!check($addr, 'dw')) {
            $this->error(L('Wallet address format error!'));
        }

        if (!check($paypassword, 'password')) {
            $this->error(L('Fund Pwd format error!'));
        }

        $isValidCoin = $this->isValidCoin($coin);
        if (!$isValidCoin) {
            $this->error(L('Invalid Coin!'));
        }
        $isValidNetwork = $this->isValidCoin($network);
        if (!$isValidNetwork) {
            $this->error(L('Invalid network!'));
        }
        $CoinInfo = C('coin')[$network];

        if (!$CoinInfo) {
            $this->error(L('Currency wrong!'));
        }

        $auto_status = ($CoinInfo['zc_zd'] && ($num < $CoinInfo['zc_zd']) ? 1 : 0);
        $contract_address = $dj_username = $CoinInfo['dj_yh']; //Contract Address
        $dj_address = $CoinInfo['dj_zj'];
        $dj_port = $CoinInfo['dj_dk'];
        $dj_decimal = $CoinInfo['cs_qk'];
        $dj_password = $CoinInfo['dj_mm'];
        $main_address = $CoinInfo['codono_coinaddress'];
        $zc_user = $CoinInfo['zc_user'];
        $zc_coin = $CoinInfo['zc_coin'];
        $qbdz = $coin . 'b';
        $networkb = $network . 'b';
        $sendrs = false;
        $can_withdraw = 1;
        $myzc_min = ($CoinInfo['zc_min'] ? abs($CoinInfo['zc_min']) : 0.0001);
        $myzc_max = ($CoinInfo['zc_max'] ? abs($CoinInfo['zc_max']) : 10000000);
        $hash = $txid = null;
        $iszcCoinValid = $this->isValidCoin($zc_coin);
        if (!$iszcCoinValid) {
            $zc_coin = $coin;
        }
        if ($num < $myzc_min) {
            $this->error(L('Amount is less than Minimum Withdrawal Amount!'));
        }

        if ($myzc_max < $num) {
            $this->error(L('Amount Exceeds Maximum Withdrawal Limit!'));
        }

        $user = M('User')->where(array('id' => $uid))->find();


        if (md5($paypassword) != $user['paypassword']) {
            $this->error(L('Incorrect Funding password!'));
        }

        $user_coin = M('UserCoin')->where(['userid' => $uid])->find();
        $user_coin_address = $user_coin[$coin . 'b'] ?: null;


        if ($user_coin[$coin] < $num) {
            $this->error(L('Insufficient funds available'));
        }

        $fee_user['userid'] = M('User')->where(['id' => $zc_user])->getField('id');
        if ($fee_user['userid'] == 0 || $fee_user['userid'] == null || $fee_user['userid'] < 0) {
            $fee_user['userid'] = 0;
        }

        $flat_fee = $CoinInfo['zc_flat_fee'];
        $percent_fee = bcmul(bcdiv($num, 100, 8), $CoinInfo['zc_fee'], 8);
        $fee = bcadd($flat_fee, $percent_fee, 8);
        if ($zc_coin != $coin) {
            // user does not have enough withdrawal fees
            if ($user_coin[$zc_coin] < $fee) {
                $this->error("You need $zc_coin $fee for withdrawal in your spot wallet");
            }
            $mum = $num;
        } else {
            $mum = bcsub($num, $fee, 8);
        }

        $gen_tx_id = 'internal_' . md5($uid . $coin . $num . $addr . $networkb . time());

        if ($fee < 0) {
            $this->error(L('Incorrect withdrawal fee!'));
        }
        if ($mum < 0) {
            $this->error(L('Incorrect withdrawal amount!'));
        }
        $mo = M();
        $peer = $mo->table('codono_user_coin')->where([$networkb => $addr])->find();

        if ($CoinInfo['type'] == 'rgb' && !$peer) {
            $this->error(L('Withdrawal Address does not exist!'));
        }
        //Withdrawal address exists on Exchange thus this would be an internal transfer
        $paramData = [
            'uid' => $uid,
            'addr' => $addr,
            'coin' => $coin,
            'network' => $network,
            'num' => $num,
            'fee' => $fee,
            'mum' => $mum,
            'auto_status' => $auto_status,
            'fee_user_id' => $fee_user['userid'],
            'zc_user' => $zc_user,
            'dest_tag' => $dest_tag,
            'zc_coin' => $zc_coin
        ];

        if ($peer) {
            $paramData['user_coin_address'] = $user_coin_address;
            $paramData['txid'] = $gen_tx_id;
            $paramData['peerid'] = $peer['userid'];

            $withdrawalResult = $this->processWithdrawal($paramData);
            $aid = $withdrawalResult['aid']; // Get the auto-increment ID for further processing
            $rs = $withdrawalResult['rs'];
            if (check_arr($rs)) {
                session('myzc_verify', null);
                $this->success(L('Transfer success!'));
            } else {
                $this->error('Transfer Failed!');
            }
        }

        //tron Starts
        if ($CoinInfo['type'] == 'tron') {

            // Common withdrawal processing
            $withdrawalResult = $this->processWithdrawal($paramData);
            $aid = $withdrawalResult['aid']; // Get the auto-increment ID for further processing
            $rs = $withdrawalResult['rs'];

            if ($auto_status) {
                $ContractAddress = $contract_address;
                $tron = TronClient();
                $amount = (float)$mum;
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
                    M('Myzc')->where(['id' => $aid])->save(['txid' => $hash, 'hash' => $hash]);
                }
            }
            $this->success('Your request is being processed!');
        }
        //tron Ends

        //cryptoapis Starts
        if ($CoinInfo['type'] == 'cryptoapis') {
            // Common withdrawal processing
            $withdrawalResult = $this->processWithdrawal($paramData);
            $aid = $withdrawalResult['aid']; // Get the auto-increment ID for further processing
            $rs = $withdrawalResult['rs'];

            if ($auto_status) {
                $cryptoapi_config = [
                    'api_key' => cryptString($CoinInfo['dj_mm'], 'd'),
                    'network' => $CoinInfo['network'],
                ];
                $cryptoapi = CryptoApis($cryptoapi_config);
                $supportedCryptoApisChains = $cryptoapi->allowedSymbols();
                if (!in_array($network, $supportedCryptoApisChains)) {
                    $this->success('Withdrawal has been processed!');
                }

                $contract_address = $contract = $CoinInfo['dj_yh'] ?: null; //Contract Address
                $blockchain = $network;
                $walletId = $CoinInfo['dj_zj'];
                $context = $uid;
                $main_address = $CoinInfo['codono_coinaddress'];
                $amount = (float)$mum;
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
                    M('Myzc')->where(['id' => $aid])->save(['memo' => $memo]);
                }
            }
            $this->success('Your request is being processed!');
        }
        //cryptoapis Ends

        //substrate withdrawal starts
        if ($CoinInfo['type'] == 'substrate') {
            // Common withdrawal processing
            $withdrawalResult = $this->processWithdrawal($paramData);
            $aid = $withdrawalResult['aid']; // Get the auto-increment ID for further processing
            $rs = $withdrawalResult['rs'];

            if ($auto_status) {

                $substrate_config = [
                    'host' => $CoinInfo['dj_zj'],
                    'port' => $CoinInfo['dj_dk'],
                    'api_key' => cryptString($CoinInfo['dj_mm'], 'd'),
                    'decimals' => $CoinInfo['cs_qk'],
                ];

                $substrate = Substrate($substrate_config);
                $substrate_amount = $substrate->amount_encode($mum);

                $request_sent = json_decode($substrate->withdraw($addr, $substrate_amount, $aid), true);
            }
            $this->success('Your request is being processed!');
        }
        //substrate withdrawal ends

        //blockgum withdrawal starts
        if ($CoinInfo['type'] == 'blockgum') {
            // Common withdrawal processing
            $withdrawalResult = $this->processWithdrawal($paramData);
            $aid = $withdrawalResult['aid']; // Get the auto-increment ID for further processing
            $rs = $withdrawalResult['rs'];

            if ($auto_status) {

                $pcoin = $CoinInfo['tokenof'] ?: $CoinInfo['name'];
                $contract_address = $CoinInfo['dj_yh'] ?: null;
                $decimals = $CoinInfo['cs_qk'] ?: 18;
                $blockgum = blockgum($pcoin);
                $blockgum_amount = format_num($mum, $decimals);


                $request_sent = $blockgum->withdrawFromMain($addr, $blockgum_amount, $contract_address, $aid);

                clog('blockgum_withdrawal', [$request_sent, $addr, $blockgum_amount, $contract_address, $aid, $mum, $decimals]);
            }
            $this->success('Your request is being processed!');
        }
        //blockgum withdrawal ends

        //esmart Starts
        if ($CoinInfo['type'] == 'esmart') {

            //esmart Wallet Withdrawal
            $withdrawalResult = $this->processWithdrawal($paramData);
            $aid = $withdrawalResult['aid']; // Get the auto-increment ID for further processing
            $rs = $withdrawalResult['rs'];

            if ($auto_status) {
                $esmart_config = [
                    'host' => $CoinInfo['dj_zj'],
                    'port' => $CoinInfo['dj_dk'],
                    'coinbase' => $CoinInfo['codono_coinaddress'],
                    'password' => cryptString($CoinInfo['dj_mm'], 'd'),
                    'contract' => $CoinInfo['dj_yh'],
                    'rpc_type' => $CoinInfo['rpc_type'],
                    'public_rpc' => $CoinInfo['public_rpc'],
                ];
                $Esmart = ESmart($esmart_config);
                if ($contract_address) {
                    //Contract Address transfer out
                    $zhuan['fromaddress'] = $CoinInfo['codono_coinaddress'];
                    $zhuan['toaddress'] = $addr;
                    $zhuan['token'] = $contract_address;
                    $zhuan['type'] = $coin;
                    $zhuan['amount'] = (float)$mum;
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
                    M('Myzc')->where(['id' => $aid])->save(['txid' => $hash]);
                    if ($hash) M()->execute("UPDATE `codono_myzc` SET  `hash` =  '$hash' WHERE id = '$aid' ");
                }
                $this->success('You have the success of the coin, background audit will automatically go out!' . $mum);
            }
            $this->success('You have successfully raised the coins and will automatically transfer them out after the background review!');
        }
        //esmart Ends

        //xrp starts
        if ($CoinInfo['type'] == 'xrp') {
            if ($dest_tag == 0 || $dest_tag = null) {
                $this->error('Make sure correct dest_tag is defined');
            }
            // Wallet Withdrawal
            $withdrawalResult = $this->processWithdrawal($paramData);
            $aid = $withdrawalResult['aid'];
            $rs = $withdrawalResult['rs'];


            if ($auto_status) {
                $xrpData = C('coin')['xrp'];
                $xrpClient = XrpClient($xrpData['dj_zj'], $xrpData['dj_dk'], $xrpData['codono_coinaddress'], $xrpData['dj_mm']);

                $sign = $xrpClient->sign($mum, $addr, $dest_tag);

                if (strtolower($sign['result']['status']) == 'success') {
                    $submit = $xrpClient->submit($sign['result']['tx_blob']);
                    if (strtolower($submit['result']['status']) == 'success') {
                        $hash = $submit['result']['tx_json']['hash'];
                        M('Myzc')->where(['id' => $aid])->save(['txid' => $hash]);
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

            $withdrawalResult = $this->processWithdrawal($paramData);
            $aid = $withdrawalResult['aid'];
            $rs = $withdrawalResult['rs'];

            if (check_arr($rs)) {
                session('myzc_verify', null);
                $this->success(L('Transfer success!'));
            } else {
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

                //TODO :Find a valid way to validate coin address
                if (strlen($addr) > 8) {
                    $valid_res = 1;
                } else {
                    $valid_res = 0;
                }
                if (!$valid_res) {
                    $this->error($addr . L(' It is not a valid address wallet!'));
                }

                $withdrawalResult = $this->processWithdrawal($paramData);
                $aid = $withdrawalResult['aid'];
                $rs = $withdrawalResult['rs'];

                $the_status = false;
                if (check_arr($rs)) {

                    $cps_api = CoinPay($dj_username, $dj_password, $dj_address, $dj_port, 5, [], 1);
                    $information = $cps_api->GetBasicInfo();
                    $coinpay_coin = strtoupper($coin);

                    if ($information['error'] != 'ok' || !isset($information['result']['username'])) {
                        clog($coin, ' Wallet link failure! Coinpayments can not be connected at time:' . time() . '<br/>');
                        $can_withdraw = 0;
                    }


                    $balances = $cps_api->GetAllCoinBalances();

                    if ($balances['result'][$coinpay_coin]['balancef'] < $num) {
                        //$this->error(L('Can not be withdrawn due to system'));
                        clog($coin, ' Balance is lower than  ' . $num . ' at time:' . time() . '<br/>');
                        $can_withdraw = 0;
                    }
                    if ($auto_status && $can_withdraw == 1) {

                        $buyer_email = $this->userinfo['email']; //M('User')->where(array('id' => $uid))->getField('email');
                        $withdrawals = [
                            'amount' => $mum,
                            'add_tx_fee' => 0,
                            'auto_confirm' => 1, //Auto confirm 1 or 0
                            'currency' => $coinpay_coin,
                            'address' => $addr,
                            //'dest_tag'=>$dest_tag,
                            'ipn_url' => SITE_URL . '/IPN/confirm',
                            'note' => $buyer_email
                        ];
                        if ($dest_tag != 0 && $dest_tag != NULL) {
                            $withdrawals['dest_tag'] = $dest_tag;
                        }

                        $the_withdrawal = $cps_api->CreateWithdrawal($withdrawals);


                        if ($the_withdrawal['error'] != 'ok') {
                            //pending_status
                            M('Myzc')->where(['id' => $aid])->save(['status' => 0]);
                            $the_status = false;
                            $this->error('Your withdrawal request is sent to admin,' . $the_withdrawal['error']);
                        } else {
                            $the_status = true;
                            $cp_withdrawal_id = $the_withdrawal['result']['id'];
                            M('Myzc')->where(['id' => $aid])->save(['hash' => $cp_withdrawal_id]);
                            //$this->success('Successful Withdrawal!');
                        }
                    }


                    session('myzc_verify', null);
                    if ($auto_status && $the_status && $can_withdraw == 1) {
                        $this->success('Successful Withdrawal!');
                    } else {
                        $this->success('Being Reviewed!');
                    }
                } else {
                    $this->success('Withdrawal is being processed!');
                }
            }
        }
        //Coinpayments ends

        //WavesPlatform Starts
        if ($CoinInfo['type'] == 'waves') {


            //TODO :Find a valid way to validate coin address
            if (strlen($addr) > 30) {
                $valid_res = 1;
            } else {
                $valid_res = 0;
            }

            if (!$valid_res) {
                $this->error($addr . L(' It is not a valid address wallet!'));
            }


            $withdrawalResult = $this->processWithdrawal($paramData);
            $aid = $withdrawalResult['aid'];
            $rs = $withdrawalResult['rs'];

            $the_status = false;
            if (check_arr($rs)) {

                $waves = WavesClient($dj_username, $dj_password, $dj_address, $dj_port, $dj_decimal, 5, [], 1);
                $information = json_decode($waves->Status(), true);
                if ($information['blockchainHeight'] && $information['blockchainHeight'] <= 0) {
                    clog('waves_error', $coin . ' can not be connected at time:' . time() . '<br/>');
                    $can_withdraw = 0;
                }

                $balances = json_decode($waves->Balance($main_address, $dj_username), true);
                $dj_decimal = $dj_decimal ?: 8;
                $wave_main_balance = $waves->deAmount($balances['balance'], $dj_decimal);
                if ($wave_main_balance < $num) {

                    clog('waves_error', $coin . ' main_address ' . $main_address . ' Balance is ' . $wave_main_balance . ' is' . $dj_decimal . ' lower than  ' . $num . ' at time:' . time() . ' ' . $dj_username . '<br/>');
                    $can_withdraw = 0;
                }
                if ($auto_status && $can_withdraw == 1) {
                    $wavesend_response = $waves->Send($main_address, $addr, $mum, $dj_username);
                    $the_withdrawal = json_decode($wavesend_response, true);
                    if ($the_withdrawal['error']) {
                        $the_status = false;
                        clog('waves_error', json_encode($the_withdrawal));
                        //pending_status
                        M('Myzc')->where(['id' => $aid])->save(['status' => 0]);
                        $this->error('Your withdrawal request is sent to admin,' . $the_withdrawal['message']);
                    } else {
                        $the_status = true;
                        M('Myzc')->where(['id' => $aid])->save(['txid' => $the_withdrawal['id'], 'hash' => $the_withdrawal['signature']]);
                        //$this->success('Successful Withdrawal!');
                    }
                }

                session('myzc_verify', null);
                if ($auto_status && $the_status && $can_withdraw == 1) {
                    $this->success('Successful Withdrawal!');
                } else {
                    $this->success('Being Reviewed!');
                }
            } else {

                $this->success('Withdrawal is being processed!');
            }
        }
        //WavesPlatform Ends

        //BLOCKIO starts
        if ($CoinInfo['type'] == 'blockio') {

            $block_io = BlockIO($dj_username, $dj_password, $dj_address, $dj_port, 5, [], 1);
            $json = $block_io->get_balance();

            if (!isset($json->status) || $json->status != 'success') {
                clog('blockio', 'Blockio Could not be connected at ' . time() . '<br/>');
                $can_withdraw = 0;
            }

            $valid_res = $block_io->validateaddress($addr);

            if (!$valid_res) {
                $this->error($addr . ' :' . L('Not valid address!'));
            }


            if ($json->data->available_balance < $num) {
                clog('blockio', 'Blockio Balance is lower than  ' . $num . ' at time:' . time() . '<br/>');
                $can_withdraw = 0;
            }

            $withdrawalResult = $this->processWithdrawal($paramData);
            $aid = $withdrawalResult['aid'];
            $rs = $withdrawalResult['rs'];

            if (check_arr($rs)) {
                if ($auto_status && $can_withdraw == 1) {

                    $sendrs = $block_io->withdraw(['amounts' => $mum, 'to_addresses' => $addr]);
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

                session('myzc_verify', null);
                if ($auto_status && $can_withdraw == 1) {
                    $this->success('Successful Withdrawal!');
                } else {
                    $this->success('Application is successful Withdrawal,Please wait for the review!');
                }
            } else {
                $this->success('Withdrawal is being processed!');
            }
        }
        //BlockIO ends

        //cryptonote starts
        if ($CoinInfo['type'] == 'cryptonote') {

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

            $json = $cryptonote->get_height();

            if (!isset($json->height) || $json->error != 0 || !$open_wallet) {
                clog('CryptoNote', $coin . ' Could not be connected at ' . time() . '<br/>');
                $can_withdraw = 0;
            }

            $bal_info = $cryptonote->getbalance();
            $crypto_balance = $cryptonote->deAmount($bal_info->available_balance);

            if ($crypto_balance < $num) {
                clog('CryptoNote ', $coin . ' Balance is lower than  ' . $num . ' at time:' . time() . '<br/>');
                $can_withdraw = 0;
                $auto_status = 0;
            }

            $withdrawalResult = $this->processWithdrawal($paramData);
            $aid = $withdrawalResult['aid'];
            $rs = $withdrawalResult['rs'];
            if (check_arr($rs)) {
                if ($auto_status && $can_withdraw == 1) {

                    $send_amt = format_num($num, 8);
                    $transData = [
                        [
                            'amount' => $send_amt,
                            'address' => $addr
                        ]
                    ];
                    $sendrs = $cryptonote->transfer($transData, $dest_tag);
                    $flag = 0;

                    clog($coin . '_cryptno_error', json_encode($sendrs));
                    if ($sendrs->error == 0) {
                        if (isset($sendrs->tx_hash) && isset($sendrs->tx_key)) {
                            $hash = $sendrs->tx_key;
                            $txid = $sendrs->tx_hash;
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

                if ($hash && $txid) {
                    M('Myzc')->where(['id' => $aid])->save(['txid' => $txid, 'hash' => $hash, 'status' => 1]);
                }


                session('myzc_verify', null);
                if ($auto_status && $can_withdraw == 1) {

                    $this->success('Successful Withdrawal');
                } else {
                    $this->success('Application is successful Withdrawal,Please wait for the review!');
                }
            } else {

                $this->success('Withdrawal is being processed!');
            }
        }
        //CryptoNote Ends

        //Bitcoin Type Starts
        if ($CoinInfo['type'] == 'qbb') {
            $CoinClient = CoinClient($dj_username, $dj_password, $dj_address, $dj_port, 5, [], 1);
            $json = $CoinClient->getinfo();
            $valid_res = $CoinClient->validateaddress($addr);

            if (!is_array($valid_res) || !$valid_res['isvalid']) {
                $this->error($addr . ' ' . L('It is not a valid address wallet!'));
            }

            $withdrawalResult = $this->processWithdrawal($paramData);
            $aid = $withdrawalResult['aid'];
            $rs = $withdrawalResult['rs'];


            if (check_arr($rs)) {

                if (!isset($json['version']) || !$json['version']) {
                    clog($coin, "$coin Could not be connected at " . time() . '<br/>');
                    $can_withdraw = 0;
                }

                if ($can_withdraw == 1) {
                    $daemon_balance = $CoinClient->getbalance();
                    if ($daemon_balance < $num) {
                        clog($coin, " $coin:Low wallet balance: " . time() . '<br/>');
                        $can_withdraw = 0;
                    }
                }
                if ($auto_status && $can_withdraw == 1) {
                    if (strpos($dj_address, 'new') !== false) {
                        $send_amt = bcadd($mum, 0, 5);
                    } else {
                        $send_amt = (float)bcadd($mum, 0, 5);
                    }
                    $contract = $CoinInfo['contract'];
                    if ($contract) {
                        $sendrs = $CoinClient->token('send', $contract, $addr, (float)$send_amt);
                    } else {
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

                        $this->error('Wallet Server  Withdraw failure:' . ($sendrs));
                    } else {
                        M('Myzc')->where(['id' => $aid])->save(['txid' => $sendrs]);
                        $this->success('Successful Withdrawal!');
                    }
                }

                if ($auto_status && $can_withdraw == 1) {

                    session('myzc_verify', null);

                    $this->success('Successful Withdrawal!');
                } else {
                    //put it in pending_status status=0
                    M('Myzc')->where(['id' => $aid])->save(['status' => 0]);

                    session('myzc_verify', null);
                    $this->success('Withdrawal application is successful,Please wait for the review!');
                }
            } else {
                $this->success('Withdrawal is being processed!');
            }
        }
        //Bitcoin Type Ends


    }
    public function fiatDeposit($coinname = 'usd')
    {
        $uid = $this->userid();
        if (FIAT_ALLOWED == 0) {
            die('Unauthorized!');
        }


        if (!check($coinname, 'n')) {
            $this->error(L('Currency format error!'));
        }


        if (!C('coin')[$coinname]) {
            $this->error(L('Currency wrong!'));
        }

        if (!is_array(C('coin')[$coinname])) {
            $this->error(L('Invalid Coin!'));
        }

        $coin_name = strtolower($coinname);
        $coin_named = strtolower($coinname) . 'd';

        $myczType = M('MyczType')->where(array('status' => 1))->select();

        $coin_id = C('coin')[$coinname]['id'];

        $myczTypeList = [];
        foreach ($myczType as $k => $v) {
            //if($v['name']!='bank'){continue;}
            $show_coin = json_decode($v['show_coin']);
            if (in_array($coin_id, $show_coin)) {

                $v['img'] = SITE_URL . 'Upload/bank/' . $v['img'];
                $v['bankinfo'] = $v['kaihu'];
                $v['acnum'] = $v['username'];
                unset($v['password']);
                unset($v['kaihu']);
                unset($v['username']);
                $myczTypeList[] = $v;
            }
        }

        $out['gateways'] = $myczTypeList;

        $user_coin = M('UserCoin')->where(array('userid' => $uid))->find();

        $fiat['available'] = format_num($user_coin[$coin_name], 2);
        $fiat['trade'] = format_num($user_coin[$coin_named], 2);

        $out['balance'] = $fiat['available'];

        $coin_img = C('coin')[$coinname]['img'];

        $out['coinname'] = $coinname;
        $out['coin_img'] = $coin_img;


        $where['userid'] = $uid;
        $where['type'] = 'bank';
        $count = M('Mycz')->where($where)->count();
        $Page = new Page($count, 15);
        $show = $Page->show();
        $list = M('Mycz')->where($where)->order('id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();

        foreach ($list as $k => $v) {
            $list[$k]['type'] = M('MyczType')->where(array('name' => $v['type']))->getField('title');
            $list[$k]['typeEn'] = $v['type'];
            $list[$k]['num'] = (Num($v['num']) ? Num($v['num']) : '');
            $list[$k]['mum'] = (Num($v['mum']) ? Num($v['mum']) : '');
        }

        $out['list'] = $list;
        $this->success($out);
    }

    public function fiatDepositHistory($coinname = 'usd')
    {
        $uid = $this->userid();
        if (FIAT_ALLOWED == 0) {
            die('Unauthorized!');
        }


        if (!check($coinname, 'n')) {
            $this->error(L('Currency format error!'));
        }


        if (!C('coin')[$coinname]) {
            $this->error(L('Currency wrong!'));
        }

        if (!is_array(C('coin')[$coinname])) {
            $this->error(L('Invalid Coin!'));
        }

        $coin_name = strtolower($coinname);
        $coin_named = strtolower($coinname) . 'd';
        $where['coin'] = $coin_name;
        $where['userid'] = $uid;
        //$where['type'] ='bank';
        $count = M('Mycz')->where($where)->count();
        $Page = new Page($count, 15);
        $show = $Page->show();
        $list = M('Mycz')->where($where)->order('id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();

        foreach ($list as $k => $v) {
            $list[$k]['type'] = M('MyczType')->where(array('name' => $v['type']))->getField('title');
            $list[$k]['typeEn'] = $v['type'];
            $list[$k]['num'] = (Num($v['num']) ? Num($v['num']) : '');
            $list[$k]['mum'] = (Num($v['mum']) ? Num($v['mum']) : '');
        }
        $out['list'] = $list;
        $this->success($out);
    }

    public function fiatoutlog($coinname = 'usd')
    {

        $uid = $this->userid();
        if (FIAT_ALLOWED == 0) {
            die('Unauthorized!');
        }


        if (!check($coinname, 'n')) {
            $this->error(L('Currency format error!'));
        }


        if (!C('coin')[$coinname]) {
            $this->error(L('Currency wrong!'));
        }

        if (!is_array(C('coin')[$coinname])) {
            $this->error(L('Invalid Coin!'));
        }

        $coin_name = strtolower($coinname);
        $coin_named = strtolower($coinname) . 'd';

        //$out['prompt_text']= D('Text')->get_content('finance_mytx');


        $where['coin'] = $coin_name;
        $where['userid'] = $uid;
        $count = M('Mytx')->where($where)->count();
        $Page = new Page($count, 15);
        $show = $Page->show();
        $list = M('Mytx')->where($where)->order('id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();

        foreach ($list as $k => $v) {
            $list[$k]['num'] = (Num($v['num']) ? Num($v['num']) : '');
            $list[$k]['fee'] = (Num($v['fee']) ? Num($v['fee']) : '');
            $list[$k]['mum'] = (Num($v['mum']) ? Num($v['mum']) : '');
        }

        $out['list'] = $list;
        $this->success($out);
    }

    public function markAsPaid($id = NULL)
    {
        if (FIAT_ALLOWED == 0) {
            die('Unauthorized!');
        }
        $uid = $this->userid();


        if (!check($id, 'd')) {
            $this->error(L('INCORRECT_REQ'));
        }

        $mycz = M('Mycz')->where(array('id' => $id, 'userid' => $uid))->find();

        if (!$mycz) {
            $this->error(L('No such order found!'));
        }

        if ($mycz['userid'] != $uid) {
            $this->error(L('No such order found!'));
        }

        if ($mycz['status'] == 2) {
            $this->error(L('Order has been Deposited already!'));
        }
        if ($mycz['status'] == 3) {
            $this->error(L('Order under processing!'));
        }
        if ($mycz['status'] == 4) {
            $this->error(L('Order Cancelled!'));
        }

        $rs = M('Mycz')->where(array('id' => $id))->save(array('status' => 3));

        if ($rs) {
            $this->success(L('Successful operation'));
        } else {
            $this->error(L('OPERATION_FAILED'));
        }
    }

    public function cancelFiatOut($id)
    {
        $uid = $this->userid();
        if (FIAT_ALLOWED == 0) {
            $this->error('Fiat is currently offline!');
        }
        if (!$uid) {
            $this->error(L('PLEASE_LOGIN'));
        }

        if (!check($id, 'd')) {
            $this->error(L('INCORRECT_REQ'));
        }

        $mytx = M('Mytx')->where(array('id' => $id, 'userid' => $uid))->find();

        if (!$mytx) {
            $this->error(L('No such order found!'));
        }

        if ($mytx['userid'] != $uid) {
            $this->error(L('No such order found!'));
        }

        if ($mytx['status'] != 0) {
            $this->error(L('Orders can not be undone!'));
        }

        $mo = M();

        $mo->startTrans();
        $rs = [];
        $fiat = strtolower($mytx['coin']);
        $fiatd = strtolower($mytx['coin']) . 'd';
        $finance = $mo->table('codono_finance')->where(array('userid' => $mytx['userid']))->order('id desc')->find();
        $finance_num_user_coin = $mo->table('codono_user_coin')->where(array('userid' => $mytx['userid']))->find();
        $rs[] = $mo->table('codono_user_coin')->where(array('userid' => $mytx['userid']))->setInc($fiat, $mytx['num']);
        $rs[] = $mo->table('codono_mytx')->where(array('id' => $mytx['id']))->setField('status', 2);
        $finance_mum_user_coin = $mo->table('codono_user_coin')->where(array('userid' => $mytx['userid']))->find();
        $finance_hash = md5($mytx['userid'] . $finance_num_user_coin[$fiat] . $finance_num_user_coin[$fiatd] . $mytx['num'] . $finance_mum_user_coin[$fiat] . $finance_mum_user_coin[$fiatd] . CODONOLIC . 'auth.codono.com');
        $finance_num = $finance_num_user_coin[$fiat] + $finance_num_user_coin[$fiatd];

        if ($finance['mum'] < $finance_num) {
            $finance_status = (1 < ($finance_num - $finance['mum']) ? 0 : 1);
        } else {
            $finance_status = (1 < ($finance['mum'] - $finance_num) ? 0 : 1);
        }

        $rs[] = $mo->table('codono_finance')->add(array('userid' => $mytx['userid'], 'coinname' => $fiat, 'num_a' => $finance_num_user_coin[$fiat], 'num_b' => $finance_num_user_coin[$fiatd], 'num' => $finance_num_user_coin[$fiat] + $finance_num_user_coin[$fiatd], 'fee' => $mytx['num'], 'type' => 1, 'name' => 'mytx', 'nameid' => $mytx['id'], 'remark' => 'Fiat Withdrawal-Undo withdrawals', 'mum_a' => $finance_mum_user_coin[$fiat], 'mum_b' => $finance_mum_user_coin[$fiatd], 'mum' => $finance_mum_user_coin[$fiat] + $finance_mum_user_coin[$fiatd], 'move' => $finance_hash, 'addtime' => time(), 'status' => $finance_status));

        if (check_arr($rs)) {
            $mo->commit();

            $this->success(L('SUCCESSFULLY_DONE'));
        } else {
            $mo->rollback();
            $this->error(L('OPERATION_FAILED'));
        }
    }

    public function viewFiatOrder($id = null)
    {
        if (check($id, 'd')) {
            $mycz = M('Mycz')->where(array('id' => $id))->find();
            if (!$mycz) {
                $this->error('Error: No such order');
            }
            $myczType = M('MyczType')->where(array('name' => $mycz['type']))->find();
            $out['gateway'] = $myczType;
            $out['payinfo'] = $mycz;
            $this->success($out);
        } else {
            $this->error('Error: No such order');
        }
    }

    public function myczChakan($id = NULL)
    {
        if (FIAT_ALLOWED == 0) {
            die('Unauthorized!');
        }
        $uid = $this->userid();

        if (!check($id, 'd')) {
            $this->error(L('INCORRECT_REQ'));
        }

        $mycz = M('Mycz')->where(array('id' => $id))->find();

        if (!$mycz) {
            $this->error(L('Top-order does not exist!'));
        }

        if ($mycz['userid'] != $uid) {
            $this->error(L('Illegal operation!'));
        }

        if ($mycz['status'] != 0) {
            $this->error(L('Order has been processed!'));
        }

        $rs = M('Mycz')->where(array('id' => $id))->save(array('status' => 3));

        if ($rs) {
            $this->success('Done!');
        } else {
            $this->error(L('OPERATION_FAILED'));
        }
    }

    public function fiatDepositUp()
    {
        $type = I('post.type');
        $num = I('post.num');
        $coinname = I('post.coinname');
        if (FIAT_ALLOWED == 0) {
            die('Unauthorized!');
        }
        $uid = $this->userid();

        if (!check($type, 'n')) {
            $this->error(L('Incorrect gateway!'));
        }

        if (!check($num, 'usd')) {
            $this->error(L('Recharge amount malformed!'));
        }
        if (!check($coinname, 'n')) {
            $this->error(L('Currency format error!'));
        }

        if (!C('coin')[$coinname]) {
            $this->error(L('Currency wrong!'));
        }

        $Coin = C('coin')[$coinname];

        if (!$Coin) {
            $this->error(L('Currency wrong!'));
        }
        $coin_name = strtolower($coinname);

        $myczType = M('MyczType')->where(array('name' => $type))->find();

        if (!$myczType) {
            $this->error(L('There is no way to recharge!'));
        }

        if ($myczType['status'] != 1) {
            $this->error(L('Deposits are currently disabled!'));
        }

        $coin_id = C('coin')[$coinname]['id'];
        $show_coin = json_decode($myczType['show_coin']);

        if (!in_array($coin_id, $show_coin)) {
            $this->error($coinname . ' can not be recharged using ' . $myczType['title']);
        }


        $mycz_min = ($myczType['min'] ?: 1);
        $mycz_max = ($myczType['max'] ?: 100000);

        if ($num < $mycz_min) {
            $this->error(L('Recharge amount can not be less than') . $mycz_min . ' ' . strtoupper($coinname));
        }

        if ($mycz_max < $num) {
            $this->error(L('Recharge amount can not exceed') . $mycz_max . ' ' . strtoupper($coinname));
        }


        for (; true;) {
            $tradeno = tradeno();

            if (!M('Mycz')->where(array('tradeno' => $tradeno))->find()) {
                break;
            }
        }

        $id = M('Mycz')->add(array('userid' => $uid, 'coin' => $coin_name, 'num' => $num, 'type' => $type, 'tradeno' => $tradeno, 'addtime' => time(), 'status' => 0));

        if ($id) {

            $mycz = M('Mycz')->where(array('id' => $id))->find();

            $myczType = M('MyczType')->where(array('name' => $mycz['type']))->find();
            unset($myczType['password']);
            if ($mycz['type'] == 'bank') {
                $UserBankType = M('UserBankType')->where(array('status' => 1))->order('id desc')->select();
                //$out['UserBankType']= $UserBankType;
            }

            $out['gateway'] = $myczType;
            $out['payinfo'] = $mycz;
            $this->success($out);
        } else {
            $this->error(L('Recharge order creation failed!'));
        }
    }

    /* Bank Management */
    public function banklist()
    {
        $uid = $this->userid();


        $user = M('User')->where(array('id' => $uid))->find();

        if ($user['idcardauth'] == 0 && KYC_OPTIONAL == 0) {
            $this->error('Please complete KYC First!');
        }

        $truename = $user['truename'];
        $out['truename'] = $truename;

        $UserBank = M('UserBank')->where(array('userid' => $uid, 'status' => 1))->order('id desc')->select();
        $FiatList = M('Coin')->where(array('status' => 1, 'type' => 'rmb'))->field('name,title')->select();

        //$UserBankType = M('UserBankType')->where(array('status' => 1))->order('id desc')->select();
        //$out['UserBankType']= $UserBankType;
        $out['FiatList'] = $FiatList;
        $out['UserBank'] = $UserBank;
        $this->success($out);
    }

    public function addbank()
    {
        $uid = $this->userid();
        $FiatList = M('Coin')->where(array('status' => 1, 'type' => 'rmb'))->field('name,title')->select();
        $UserBankType = M('UserBankType')->where(array('status' => 1, 'type' => 'bank'))->order('id desc')->select();
        $out['UserBankType'] = $UserBankType;
        $out['FiatList'] = $FiatList;
        $this->success($out);
    }


    public function doAddBank($name, $bank, $bankprov, $bankcity, $bankaddr, $bankcard, $paypassword, $truename)
    {
        $uid = $this->userid();
        if (!check($name, 'a')) {
            $this->error(L('Title of Entry is incorrect format!'));
        }
        if (!check($truename, 'english')) {
            $this->error(L('Account name incorrect format!'));
        }

        if (!check($bank, 'a')) {
            $this->error(L('Bank malformed!'));
        }

        if (!check($bankprov, 'c')) {
            $this->error(L('Opening provinces format error!'));
        }

        if (!check($bankcity, 'c')) {
            $this->error('Format of the city is wrong!');
        }

        if (!check($bankaddr, 'a')) {
            $this->error(L('Bank address format error!'));
        }

        if (!check($bankcard, 'a')) {
            $this->error(L('Bank account number format error!'));
        }

        if (strlen($bankcard) < 4 || strlen($bankcard) > 50) {

            $this->error(L('Bank account number format error!'));
        }


        if (!check($paypassword, 'password')) {
            $this->error(L('Fund Pwd format error!'));
        }

        $user_paypassword = M('User')->where(array('id' => $uid))->getField('paypassword');

        if (md5($paypassword) != $user_paypassword) {
            $this->error(L('Trading password is wrong!'));
        }

        if (!M('UserBankType')->where(array('title' => $bank))->find()) {
            $this->error(L('Bank error!'));
        }

        $userBank = M('UserBank')->where(array('userid' => $uid))->select();

        foreach ($userBank as $k => $v) {
            if ($v['name'] == $name) {
                $this->error(L('Please do not use the same name Notes!'));
            }

            if ($v['bankcard'] == $bankcard) {
                $this->error(L('Bank card number already exists!'));
            }
        }

        if (10 <= count($userBank)) {
            $this->error('Each user can add upto 10 accounts only!');
        }

        if (M('UserBank')->add(array('userid' => $uid, 'name' => $name, 'bank' => $bank, 'bankprov' => $bankprov, 'bankcity' => $bankcity, 'bankaddr' => $bankaddr, 'bankcard' => $bankcard, 'truename' => $truename, 'addtime' => time(), 'status' => 1))) {
            $this->success(L('Banks added successfully!'));
        } else {
            $this->error(L('Bank Add Failed!'));
        }
    }

    public function delbank($id)
    {

        $uid = $this->userid();

        if (!check($id, 'd')) {
            $this->error(L('INCORRECT_REQ'));
        }


        if (!M('UserBank')->where(array('userid' => $uid, 'id' => $id))->find()) {
            $this->error(L('Unauthorized access!'));
        } else if (M('UserBank')->where(array('userid' => $uid, 'id' => $id))->delete()) {
            $this->success(L('Successfully deleted!'));
        } else {
            $this->error(L('failed to delete!'));
        }
    }


    /* Fiat Withdrawals */
    public function fiatWithdrawal($coin = 'usd', $status = NULL)
    {
        $fiatcoins = [];
        if (FIAT_ALLOWED == 0) {
            $this->error('Fiat withdrawals are currently disabled!');
        }

        $uid = $this->userid();


        $cellphone = M('User')->where(array('id' => $uid))->getField('cellphone');
        $email = M('User')->where(array('id' => $uid))->getField('email');

        if ($cellphone || $email) {
            $cellphone = substr_replace($cellphone, '****', 3, 4);
            $email = substr_replace($email, '****', 3, 4);
        } else {
            if (M_ONLY == 1) {
                $this->error(L('Please Verify your Phone!'));
            }
        }

        foreach (C('coin') as $coinlist) {
            if ($coinlist['type'] == 'rmb') {
                $_fiat_coin['name'] = $coinlist['name'];
                $_fiat_coin['img'] = $coinlist['img'];
                $fiatcoins[] = $_fiat_coin;
            }
        }
        if (C('coin')[$coin]['name'] != $coin || C('coin')[$coin]['type'] != 'rmb' || C('coin')[$coin]['status'] != 1) {
            $this->error(L('Incorrect coin selected!'));
        }
        $coin_img = C('coin')[$coin]['img'];
        $out['coin_img'] = $coin_img;
        $out['coin'] = $coin;
        $out['fiatcoins'] = $fiatcoins;
        $out['cellphone'] = $cellphone;
        $out['email'] = $email;
        $user_coin = M('UserCoin')->where(array('userid' => $uid))->find();
        $out['fiat'] = format_num($user_coin[$coin], 2);
        $userBankList = M('UserBank')->where(array('userid' => $uid, 'status' => 1))->order('id desc')->limit(10)->select();
        $out['userBankList'] = $userBankList;

        if (($status == 1) || ($status == 2) || ($status == 3) || ($status == 4)) {
            $where['status'] = $status - 1;
        }

        $this->assign('status', $status);
        $where['userid'] = $uid;
        $count = M('Mytx')->where($where)->count();
        $Page = new Page($count, 15);
        $show = $Page->show();
        $list = M('Mytx')->where($where)->order('id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();

        foreach ($list as $k => $v) {
            $list[$k]['num'] = (Num($v['num']) ? Num($v['num']) : '');
            $list[$k]['fee'] = (Num($v['fee']) ? Num($v['fee']) : '');
            $list[$k]['mum'] = (Num($v['mum']) ? Num($v['mum']) : '');
        }

        $out['list'] = $list;
        $out['page'] = $show;
        $this->success($out);
    }

    public function doFiatWithdrawal($num, $paypassword, $type, $coinname)
    {
        if (FIAT_ALLOWED == 0) {
            $this->error(L('Fiat System is currently offline!'));
        }

        $num = I('post.num', 0.00, 'float');
        $paypassword = I('post.paypassword', '', 'text');
        $type == I('post.type', 0, 'decimal');
        $coinname = I('post.coinname', '', 'text');
        $uid = $this->userid();

        if (!check($coinname, 'n')) {
            $this->error(L('Currency format error!'));
        }

        if (!C('coin')[$coinname]) {
            $this->error(L('Currency wrong!'));
        }

        $coin_name = strtolower($coinname);
        $coin_named = strtolower($coinname) . 'd';

        if (!check($num, 'd')) {
            $this->error(L('The amount of withdrawals format error!'));
        }

        if (!check($paypassword, 'password')) {
            $this->error(L('Fund Pwd format error!'));
        }

        if (!check($type, 'd')) {
            $this->error(L('Withdraw way malformed!'));
        }

        $userCoin = M('UserCoin')->where(array('userid' => $uid))->find();

        if ($userCoin[$coin_name] < $num) {
            $this->error(L('Lack of available Balance!'));
        }

        $user = M('User')->where(array('id' => $uid))->find();

        if (md5($paypassword) != $user['paypassword']) {
            $this->error(L('Trading password is wrong!'));
        }

        $userBank = M('UserBank')->where(array('id' => $type))->find();

        if (!$userBank) {
            $this->error(L('Withdraw wrong address!'));
        }

        $mytx_min = (C('mytx_min') ? C('mytx_min') : 1);
        $mytx_max = (C('mytx_max') ? C('mytx_max') : 1000000);
        $mytx_bei = C('mytx_bei'); //multiple of ie 100 or 10
        $mytx_fee = C('mytx_fee');

        if ($num < $mytx_min) {
            $this->error(L('Amount can not be less than') . ' ' . $mytx_min);
        }

        if ($mytx_max < $num) {
            $this->error(L('Amount can not exceed') . ' ' . $mytx_max);
        }

        if ($mytx_bei) {
            if ($num % $mytx_bei != 0) {
                $this->error(L('Amount must be multiple of') . ' ' . $mytx_bei);
            }
        }
        $truename = $userBank['truename'] ?: $user['truename'];
        $fee = format_num(($num / 100) * $mytx_fee, 2);
        $mum = format_num(($num / 100) * (100 - $mytx_fee), 2);
        $mo = M();

        $mo->startTrans();

        $userCoin = M('UserCoin')->where(array('userid' => $uid))->find();
        if ($userCoin[$coin_name] < $num) {
            $this->error(L('Lack of available Balance!'));
        }

        $rs = [];
        $finance = $mo->table('codono_finance')->where(array('userid' => $uid))->order('id desc')->find();
        $finance_num_user_coin = $mo->table('codono_user_coin')->where(array('userid' => $uid))->find();
        $rs[] = $mo->table('codono_user_coin')->where(array('userid' => $uid))->setDec($coin_name, $num);
        $rs[] = $finance_nameid = $mo->table('codono_mytx')->add(array('userid' => $uid, 'num' => $num, 'fee' => $fee, 'mum' => $mum, 'name' => $userBank['name'], 'truename' => $truename, 'bank' => $userBank['bank'], 'bankprov' => $userBank['bankprov'], 'bankcity' => $userBank['bankcity'], 'bankaddr' => $userBank['bankaddr'], 'bankcard' => $userBank['bankcard'], 'addtime' => time(), 'status' => 0, 'coin' => $coin_name));
        $finance_mum_user_coin = $mo->table('codono_user_coin')->where(array('userid' => $uid))->find();
        $finance_hash = md5($uid . $finance_num_user_coin[$coin_name] . $finance_num_user_coin[$coin_named] . $mum . $finance_mum_user_coin[$coin_name] . $finance_mum_user_coin[$coin_named] . CODONOLIC . 'auth.codono.com');
        $finance_num = $finance_num_user_coin[$coin_name] + $finance_num_user_coin[$coin_named];

        if ($finance['mum'] < $finance_num) {
            $finance_status = (1 < ($finance_num - $finance['mum']) ? 0 : 1);
        } else {
            $finance_status = (1 < ($finance['mum'] - $finance_num) ? 0 : 1);
        }

        $rs[] = $mo->table('codono_finance')->add(array('userid' => $uid, 'coinname' => $coin_name, 'num_a' => $finance_num_user_coin[$coin_name], 'num_b' => $finance_num_user_coin[$coin_named], 'num' => $finance_num_user_coin[$coin_name] + $finance_num_user_coin[$coin_named], 'fee' => $num, 'type' => 2, 'name' => 'mytx', 'nameid' => $finance_nameid, 'remark' => 'Fiat withdrawal', 'mum_a' => $finance_mum_user_coin[$coin_name], 'mum_b' => $finance_mum_user_coin[$coin_named], 'mum' => $finance_mum_user_coin[$coin_name] + $finance_mum_user_coin[$coin_named], 'move' => $finance_hash, 'addtime' => time(), 'status' => $finance_status));

        if (check_arr($rs)) {
            session('mytx_verify', null);
            $mo->commit();

            $this->success(L('Withdrawal Order Created!!'));
        } else {
            $mo->rollback();
            $this->error(L('Withdraw Order Failed!'));
        }
    }

    public function yocoprocess($id, $token, $amount)
    {
        $uid = $this->userid();

        if (YOCO_GATEWAY['status'] == 0 || !YOCO_GATEWAY['status']) {
            $this->error(L('Gateway is currently disabled'));
        }

        if (!check($id, 'd')) {
            $this->error(L('Invalid Attempt'));
        }
        if (!check($token, 'mostregex')) {
            $this->error(L('Invalid Token'));
        }
        $where = array('id' => $id, 'userid' => $uid, 'status' => 0);
        $mycz = M('Mycz')->where($where)->find();
        if (!$mycz) {

            $this->error(L('Deposit order does not exist!'));
        }

        $amountInCents = bcmul($amount, 100);
        /*
        if (YOCO_GATEWAY['mode'] == "sandbox") {
            //Do something
        } else {
            //this is live
        }
        */
        $data = [
            'token' => $token, // Your token for this transaction here
            'amountInCents' => $amountInCents, // payment in cents amount here
            'currency' => 'ZAR' // currency here
        ];
        $secret_key = YOCO_GATEWAY['secret_key'];

        // Setup curl
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://online.yoco.com/v1/charges/');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_USERPWD, $secret_key);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        // send to yoco
        $result = curl_exec($ch);
        $response_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        // close the connection
        curl_close($ch);
        $response = json_decode($result);
        if ($response_status != 201) {
            //echo '<div class="center">We could not process your payment, Please try again in sometime <br/> Close this window...</div>';
            $this->error('We could not process your payment, Please try again in sometime Error:NOT201');
        }

        // convert response to a usable object

        if ($response->status == 'successful' && $response->amountInCents == $amountInCents && $response->object = 'charge' && $response->currency = 'ZAR') {
            $save_array = array('remark' => $response->chargeId, 'status' => 4, 'ipn_response' => $response->fingerprint);
            $rs = M('Mycz')->where(array('userid' => $uid, 'id' => $mycz['id'], 'tradeno' => $mycz['tradeno']))->save($save_array);
            $this->success('Payment has been received, order is being processed');
        } else {
            $this->error('Payment could not be completed, please try again in sometime');
        }
    }
    public function getCoinNetworks()
    {
        $coin = strtolower(I('get.coin', null, 'string'));
        $coins_list = C('coin');
        if (!$coin || $coin != C('coin')[$coin]['name']) {
            $this->error('No Such coin Found');
        }
        $networks[] = ['name' => $coin, 'network' => C('coin')[$coin]['js_yw'], 'tokenof' => C('coin')[$coin]['tokenof'], 'parent' => $coin];
        foreach ($coins_list as $cl) {
            if ($cl['symbol'] == $coin) {
                $networks[] = ['name' => $cl['name'], 'network' => $cl['js_yw'], 'tokenof' => $cl['tokenof'], 'parent' => $coin];
            }
        }
        $this->ajaxShow($networks);
    }
    private function isValidCoin($coin): bool
    {
        $coins = C('coin');

        if (array_key_exists(strtolower($coin), $coins)) {
            return true;
        } else {
            return false;
        }
    }
    private function processWithdrawal(array $paramData)
    {
        // Extract parameters from $paramData array
        $uid = $paramData['uid'];
        $addr = $paramData['addr'];
        $coin = $paramData['coin'];
        $network = $paramData['network'];
        $num = $paramData['num'];
        $fee = $paramData['fee'];
        $mum = $paramData['mum'];
        $auto_status = $paramData['auto_status'];
        $fee_user_id = $paramData['fee_user_id'];
        $zc_user = $paramData['zc_user'];
        $dest_tag = $paramData['dest_tag'] ?? null;
        $txid = $paramData['txid'] ?? null;
        $zc_coin = $paramData['zc_coin'];
        $mo = M();
        $rs = [];
        $mo->startTrans();
        try {
            // Reduce the user's balance
            $rs[] = $mo->table('codono_user_coin')->where(['userid' => $uid])->setDec($coin, $num);
            //withdrawal coin is different
            if ($zc_coin != $coin && $fee > 0) {
                $rs[] = $mo->table('codono_user_coin')->where(['userid' => $uid])->setDec($zc_coin, $fee);
            }
            // Prepare the transaction data array
            $transactionData = [
                'userid' => $uid,
                'username' => $addr,
                'coinname' => $coin,
                'network' => $network,
                'num' => $num,
                'fee' => $fee,
                'mum' => $mum,
                'addtime' => time(),
                'status' => $auto_status,
                'zc_coin' => $zc_coin
            ];

            // Optionally add dest_tag to the transaction data if applicable
            if (!is_null($dest_tag)) {
                $transactionData['dest_tag'] = $dest_tag;
            }

            // Optionally adjust the txid based on status
            if ($txid != null) {
                $transactionData['txid'] = $txid;
            } else {
                $transactionData['txid'] = $auto_status ? 'processing' : null;
            }

            //make peer deposit
            if ($paramData['peerid']) {
                $rs[] = $this->processInternalTransfer($mo, $paramData);
            }


            // Record the withdrawal transaction
            $rs[] = $aid = $mo->table('codono_myzc')->add($transactionData);

            // Handle fees and add records for withdrawal fees and invitations if applicable
            if ($fee) {
                $rs[] = $mo->table('codono_myzc_fee')->add([
                    'userid' => $fee_user_id,
                    'txid' => $aid,
                    'username' => $zc_user,
                    'coinname' => $zc_coin,
                    'num' => $num,
                    'fee' => $fee,
                    'mum' => $mum,
                    'type' => 2,
                    'addtime' => time(),
                    'status' => 1
                ]);


                // Increment the zc_user's balance by the fee amount if they exist
                if ($zc_user != 0 && $mo->table('codono_user_coin')->where(['userid' => $zc_user])->find()) {
                    $rs[] = $mo->table('codono_user_coin')->where(['userid' => $zc_user])->setInc($zc_coin, $fee);
                    $rs[] = $mo->table('codono_invit')->add([
                        'coin' => $zc_coin,
                        'userid' => $fee_user_id,
                        'invit' => $uid,
                        'name' => 'Withdrawal Fees',
                        'type' => $coin . '_withdrawalFees',
                        'num' => $num,
                        'mum' => $mum,
                        'fee' => $fee,
                        'addtime' => time(),
                        'status' => 1
                    ]);
                }
            }
            if (check_arr($rs)) {
                $mo->commit();
                return ['aid' => $aid, 'rs' => $rs, 'success' => true];
            } else {
                $mo->rollback();
                return ['error' => 'EP1888', 'success' => false, 'rs' => false, 'aid' => false];
            }
        } catch (\Exception $e) {
            $mo->rollback();
            clog('processWithdrawal_issue', [$uid, $addr, $coin, $network, $num, $fee, $mum, $auto_status, $fee_user_id, $zc_user, $dest_tag, $txid, $e->getMessage()]);
            return ['error' => 'Error Processing', 'success' => false, 'rs' => false, 'aid' => false];
        }
    }
    private function processInternalTransfer($mo, array $paramData)
    {
        // Extract parameters from the $paramData array
        $peerUserId = $paramData['peerid'];
        $coin = $paramData['coin'];
        $mum = $paramData['mum'];
        $txid = $paramData['txid'];
        $num = $paramData['num'];
        $fee = $paramData['fee'];
        $userCoinAddress = $paramData['user_coin_address'];

        // Initialize an array to hold the results of database operations
        $rs = [];

        // Increment the coin balance for the peer user
        $rs[] = $mo->table('codono_user_coin')->where(['userid' => $peerUserId])->setInc($coin, $mum);

        // Add a new transaction record for the peer user
        $rs[] = $mo->table('codono_myzr')->add([
            'userid' => $peerUserId,
            'username' => $userCoinAddress, // Assuming $user_coin[$coin . 'b'] is passed as 'user_coin_address'
            'coinname' => $coin,
            'txid' => $txid,
            'num' => $num,
            'fee' => $fee,
            'mum' => $mum,
            'addtime' => time(),
            'status' => 1
        ]);

        // Return the results of the database operations
        return $rs;
    }
    public function trace()
    {
        $uid = $this->userid();

        $Coins = C('coin');
        foreach ($Coins as $k => $v) {
            if (($v['type'] == 'esmart' || $v['type'] == 'blockgum') && $v['dj_yh'] == '') {
                $coin_list[$v['name']] = ['type' => $v['type'], 'name' => $v['name'], 'title' => $v['title'], 'network' => $v['js_yw']];
            }
        }


        ksort($coin_list);
        $data['coin_list'] = $coin_list;
        $this->ajaxShow($data);
    }
    
    public function doTrace()
    {
        $uid = $this->userid();
        $chain = strtolower(I('request.chain', null, 'string'));
        $txid = I('request.txid', null, 'string');
        $func = $coin = '';
        if (!preg_match('/^(0x)?[\da-f]{64}$/i', $txid)) {
            $this->error('Invalid txid');
        }

        $isValidCoin = $this->isValidCoin($chain);
        if ($chain == null || !$isValidCoin) {
            $this->error('Invalid coin');
        }

        $amount = 0;
        $token_address_found = 0;
        $all_coinsList = C('coin');
        $tokens_list = $coinList = [];
        if (C('coin')[$chain]['type'] == 'blockgum') {
            $blockgum = blockgum($chain);
            $history = $blockgum->traceDeposit($txid);
            if ($history && $history['status'] == 1) {

                $found = null;
                //find internally
                $found = M('Myzr')->where(['txid' => $txid])->find();
                if (!$found) {
                    $found = $blockgum->markAsUnrecorded($txid);
                }
                $this->success('Your request for trace has been added, Server Resp:' . $history['message']);
            } else {
                clog('blockgum_' . $chain, $history);
                $this->error('Auto trace would not detect such deposit, Please contact support');
            }
        }
        foreach ($all_coinsList as $coinex) {
            if ($coinex['type'] == 'esmart' && $coinex['status'] == 1) {
                $coinList[] = $coinex;
                if ($coinex['tokenof'] == $chain && $coinex['dj_yh'] != '') {
                    $tokens_list[] = strtolower($coinex['dj_yh']);
                }
            }
        }


        $coin_esmart = C('coin')[$chain];

        $esmart_details = C('coin')[$coin_esmart['name']];

        $block = M()->query("SELECT * FROM `codono_coin` WHERE `name`='" . $coin_esmart['name'] . "'");

        $esmart_details = $block[0];

        $pcoin = $esmart_details['tokenof'] ?: $esmart_details['name'];

        $pcoinb = $pcoin . 'b';

        // Saving Userid's and their addresses  $pcoinb_array

        $coin_Query = 'SELECT count(' . $pcoinb . ') as count FROM `codono_user_coin` WHERE `' . $pcoinb . '` is not null';
        $pcoinb_count = M()->query($coin_Query)[0]['count'];
        $coin_Query = 'SELECT ' . $pcoinb . ' as addr , userid  FROM `codono_user_coin` WHERE `' . $pcoinb . '` is not null';
        $pcoinb_array = M()->query($coin_Query);


        $main_account = $esmart_details['codono_coinaddress'];

        $esmart_config = [
            'host' => $esmart_details['dj_zj'],
            'port' => $esmart_details['dj_dk'],
            'coinbase' => $esmart_details['codono_coinaddress'],
            'password' => cryptString($esmart_details['dj_mm'], 'd'),
            'contract' => $esmart_details['dj_yh'],
            'rpc_type' => $esmart_details['rpc_type'],
            'public_rpc' => $esmart_details['public_rpc'],
        ];
        $Esmart = Esmart($esmart_config);


        $decimals = $esmart_details['cs_qk'];


        $time_start = microtime(true);
        $infoTx = $Esmart->eth_getTransactionByHash($txid);

        $listtransactions = [$infoTx];

        foreach ($listtransactions as $ks => $trans) {

            if (isset($trans['from']) && strtolower($trans['from']) == strtolower($main_account)) {
                $this->error('This was a gas transfer from our exchange account only');
                continue;
            }

            if (!isset($trans['to'])) {
                $this->error('No payee');
                continue; //No payee.
            }

            if ($trans['value'] == '0x0' || $trans['value'] == '0x' || $trans['value'] == '0') {
                if (!$trans['input']) {
                    $this->error('Invalid tx');
                    continue;
                }

                //Find if its a token if input != 0x then its a token


                if ($trans['input'] != '0x') {
                    $func = '0x' . substr($trans['input'], 2, 8);
                    if (!in_array(strtolower($trans['to']), $tokens_list) && $func != '0x2228f3a4') {
                        continue;
                    }

                    $to_num = substr($trans['input'], 74); //Quantity
                    $tos = substr($trans['input'], 34, 40); //Reciever

                    if (!$tos) {
                        $this->error('No such sender');
                        continue;
                    }

                    $tos = '0x' . $tos;


                    $num = $Esmart->fromWei($to_num);
                } else {
                    //This is for ethereum it self
                    $tos = $trans['to'];
                    $num = $trans['value'];
                }
                if ($func == '0x2228f3a4') {
                    $batch_decodes = $Esmart->payoutERC20Batch_decode($trans['input']);

                    foreach ($batch_decodes as $batch_decode) {
                        //check if erc20 contract is listed on exchange or not
                        if (!in_array(strtolower($batch_decode['contract']), $tokens_list)) {
                            continue;
                        }
                        $batch_address = $batch_decode['address'];
                        $coin_Query = 'SELECT userid FROM `codono_user_coin` WHERE `' . $pcoinb . "` LIKE '" . $batch_address . "'";
                        $users = M()->query($coin_Query);
                        if (!$users) continue;
                        $batch_uid = $users[0]['userid'];
                        $batch_contract_info = $this->findTokenByContract($batch_decode['contract']);
                        $batch_decimal = $batch_contract_info['cs_qk'];
                        $batch_coin = $batch_contract_info['name'];
                        $batch_amount = hexdec($batch_decode['bal_hex']) / bcpow(10, $batch_decimal);
                        //Already recorded
                        if (M('Myzr')->where(['txid' => $trans['hash']])->find()) {
                            echo $batch_address . '=>' . $batch_amount . '=>tx for ' . $batch_coin . ' already credited Checking Next' . '<br>';
                            continue;
                        }
                        $data = ['userid' => $batch_uid, 'amount' => $batch_amount, 'coin' => $batch_coin, 'address' => $batch_address, 'hash' => $trans['hash']];
                        $b_info = D('Coin')->depositCoin($data);
                    }
                    continue;
                }
                if (count($listtransactions) < $pcoinb_count) {

                    $coin_Query = 'SELECT userid as userid FROM `codono_user_coin` WHERE `' . $pcoinb . "` LIKE '" . $tos . "'";
                    $users = M()->query($coin_Query);

                    if (!$users) {
                        $this->error('No such user');
                        continue;
                    }

                    $user = $users[0];
                } else {

                    $user = 0;
                    foreach ($pcoinb_array as $pcoinb_user) {
                        if (isset($pcoinb_user['addr']) && $pcoinb_user['addr'] == $tos) {
                            $coin_Query = 'SELECT userid as userid FROM `codono_user_coin` WHERE `' . $pcoinb . "` LIKE '" . $tos . "'";
                            $users = M()->query($coin_Query);

                            if (!$users) {
                                $this->error('No such user [2]');
                                continue;
                            }

                            $user = $users[0];

                            //$user=$pcoinb_user['userid'];


                        } else {
                            $this->error('We could not find it ');
                            continue;
                        }
                    }
                    if (!isset($user['userid'])) {
                        $this->error('no such user found ');
                        continue;
                    }
                }

                $hash_result = $Esmart->eth_getTransactionReceipt($trans['hash']);

                if ($hash_result['status'] != '0x1' && strtolower($hash_result['transactionHash']) != strtolower($trans['hash'])) {

                    $this->error($trans['hash'] . ' tx was failed or can not confirm it - Skipping it');
                    continue;
                }

                $func = '0x' . substr($trans['input'], 2, 8);

                $flag = false;
                if ($func == '0xa9059cbb') {
                    $token_address_found = $trans['to'];
                    $from = $trans['from'];
                    $to = '0x' . substr(substr($trans['input'], 10, 64), -40);

                    $coin_Query = "SELECT name,cs_qk FROM `codono_coin` WHERE `dj_yh` LIKE '%" . $token_address_found . "%'";

                    $coin_info = M()->query($coin_Query);
                    $decimals = $coin_info[0]['cs_qk'];
                    $amount = hexdec(substr($trans['input'], 74, 64)) / bcpow(10, $decimals);
                    $flag = true;
                } else if ($func == '0x23b872dd') {
                    $token_address_found = $trans['to'];
                    $from = '0x' . substr(substr($trans['input'], 10, 64), -40);
                    $to = '0x' . substr(substr($trans['input'], 74, 64), -40);
                    $amount = hexdec(substr($trans['input'], 138, 64));

                    $flag = true;
                }

                if ($flag) {
                    $coin_Query = "SELECT name,cs_qk FROM `codono_coin` WHERE `dj_yh` LIKE '%" . $token_address_found . "%'";
                    $coin_info = M()->query($coin_Query);
                    $coin = $coin_info[0]['name'];
                }
                if ($trans['input'] != '0x' && $coin == $pcoin) {
                    $this->error('Invalid tx [4] ');
                    continue;
                }

                if ($trans['input'] == '0x' && $coin != $pcoin) {
                    $this->error($pcoin . 'Invalid tx [3]' . $coin);
                    continue;
                }

                if ($trans['input'] != '0x' && $coin != $pcoin) {
                    $contract_Address_to_look = $trans['to'];
                    if ($token_address_found != $contract_Address_to_look) {
                        continue;
                    }
                    $token_query = "SELECT name,cs_qk as decimals FROM `codono_coin` WHERE `dj_yh` LIKE '%" . $contract_Address_to_look . "%'";

                    $resulto = M()->query($token_query);

                    if (!$resulto[0]['name']) {
                        $this->error('This token deposited is not registered on exchange');
                        continue;
                    }

                    $coin = $resulto[0]['name'];

                    $num = $amount;
                }
                if ($num <= 0 && $coin == $pcoin) {
                    $num = $Esmart->fromWei($trans['val']);
                }

                if ($num <= 0) continue;


                if (M('Myzr')->where(['txid' => $trans['hash']])->find()) {
                    //Already recorded
                    $this->error('Transaction was already deposited!');
                    continue;
                }

                $mo = M();
                $mo->startTrans();

                $num = format_num($num, 8);
                $coin = $this->findSymbol($coin);

                $rs[] = $mo->table('codono_myzr')->add(['userid' => $user['userid'], 'type' => 'esmart', 'username' => $tos, 'coinname' => $coin, 'fee' => 0, 'txid' => $trans['hash'], 'num' => $num, 'mum' => $num, 'addtime' => time(), 'status' => 1]);
                $rs[] = $mo->table('codono_user_coin')->where(['userid' => $user['userid']])->setInc($coin, $num);

                if (check_arr($rs)) {
                    $mo->commit();
                    //$this->deposit_notify($user['userid'], $tos, $coin, $trans['hash'], $num, time());

                    $this->success('Transaction was found and deposited');
                } else {
                    $mo->rollback();
                    $this->error('Deposit could not be processed');
                }
            } else {

                $tos = $trans['to'];
                if (count($listtransactions) < $pcoinb_count) {


                    $coin_Query = 'SELECT userid as userid FROM `codono_user_coin` WHERE `' . $pcoinb . "` LIKE '" . $tos . "'";
                    $users = M()->query($coin_Query);

                    if (!$users) continue;

                    $user = $users[0];
                } else {
                    $user = 0;
                    foreach ($pcoinb_array as $pcoinb_user) {
                        if ($pcoinb_user['addr'] == $tos) {
                            //$user=$pcoinb_user;

                            $coin_Query = 'SELECT userid as userid FROM `codono_user_coin` WHERE `' . $pcoinb . "` LIKE '" . $tos . "'";
                            $users = M()->query($coin_Query);

                            if (!$users) continue;

                            $user = $users[0];
                        } else {
                            continue;
                        }
                    }

                    if (!isset($user)) continue;
                }


                //esmart
                //$user = M('UserCoin')->where(array($pcoinb => $trans['to']))->find();
                if (!$user['userid']) continue;
                if (M('Myzr')->where(['txid' => $trans['hash']])->find()) {

                    $this->error('Transaction was already deposited!');
                    continue;
                }
                $addbalance = $Esmart->fromWei($trans['value']);

                M('myzr')->add(['userid' => $user['userid'], 'username' => $trans['to'], 'coinname' => $pcoin, 'fee' => 0, 'txid' => $trans['hash'], 'num' => $addbalance, 'mum' => $addbalance, 'addtime' => time(), 'status' => 1, 'type' => 'esmart']);
                $rs[] = M()->table('codono_user_coin')->where(['userid' => $user['userid']])->setInc($pcoin, $addbalance);
                deposit_notify($user['userid'], $tos, $pcoin, $trans['hash'], $addbalance, time());
                $this->success('Transaction was found and deposited');
            }
        }
    }
}//End of class
