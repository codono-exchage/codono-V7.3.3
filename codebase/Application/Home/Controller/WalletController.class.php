<?php

namespace Home\Controller;

use Common\Ext\GoogleAuthenticator;

use Think\Page;

class WalletController extends HomeController
{
    public const G2FA_REQUIRED_FOR_WITHDRAWAL = 1;  // IF Google2Fa required for withdrawal
    public const SHOW_SITE_DIVIDEND = 0;  // IF Google2Fa required for withdrawal

    public const GROUP_WITHDRAWAL_LIMIT = 1;  // If withdrawal is limited to KYC and User Group Levels
    public function index()
    {
        redirect(U('Finance/index'));
    }

    /*Process withdrawal*/
    public function upCryptoWithdrawal()
    {
        $uid = userid();
        if (!IS_POST) {
            $this->error(L('INVALID REQUEST'), U('Wallet/cryptowithdrawal'));
        }
        if (!$uid) {
            $this->error(L('YOU_NEED_TO_LOGIN'), U('Login/login'));
        }
        if (!kyced()) {
            $this->error(L('Complete KYC First!'), U('User/authentication'));
        }

        $coin = I('post.coin', null, 'text');
        $network = I('post.network', null, 'text');
        $num = I('post.num');
        $addr = I('post.addr', null, 'text');
        $paypassword = I('post.paypassword', null, 'text');
        $cellphone_verify = I('post.cellphone_verify', null, 'text');
        $dest_tag = I('post.dest_tag', null, 'text');
        $gacode = I('post.gacode', null, 'text');
        $otp = I('post.otp', 0, 'text');

        

        $this->validateSmsCodeIfNeeded($cellphone_verify);
        $this->userGroupLimit($uid, $coin, $num);
        $num = format_num($num);

        if (!check($num, 'double')) {
            $this->error(L('Number format error!'));
        }
        if ($otp != session('requestOTP')) {
            $this->error('Incorrect OTP!');
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

        $user = $this->userinfo;

        $ga_verification = $this->verify2FACode($user['ga'], $gacode);
        if (!$ga_verification) {
            $this->error(L('Incorrect Google 2FA Entered'));
        }

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

            $json = json_decode($cryptonote->get_height());

            if (!isset($json->height) || $json->error != 0 || !$open_wallet) {
                clog('CryptoNote', $coin . ' Could not be connected at ' . time() . '<br/>');
                $can_withdraw = 0;
            }

            $bal_info = json_decode($cryptonote->getbalance());
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
                    $sendrs = json_decode($cryptonote->transfer($transData, $dest_tag));
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

    public function cryptodeposit()
    {
        $uid = userid();
        if (!$uid) {
            redirect(U('Login/login'));
        }
        $message = $dest_tag = $wallet = $show_qr = [];
        $this->assign('prompt_text', D('Text')->get_content('finance_myzr'));
        $coin = I('get.coin', '', 'text');
        $coin = $coin ? trim($coin) : C('xnb_mr');

        $this->assign('xnb', $coin);
        $InfoCoin = [];

        $Coins = C('coin');
        $mo = M();
        foreach ($Coins as $k => $v) {
            if ($v['type'] != 'rmb') {
                if ($v['symbol'] != null && $v['symbol'] != $v['name']) {
                    continue;
                }
                $coin_list[$v['name']] = ['name' => $v['name'], 'type' => $v['type'], 'title' => $v['title'], 'tokenof' => $v['tokenof'], 'img' => '/Upload/coin/' . $v['img'], 'deposit' => $v['zr_jz'], 'confirmations' => $v['zr_dz'], 'explorer' => $v['js_wk']];
            }
        }

        ksort($coin_list);

        $this->assign('coin_list', $coin_list);
        $user_coin = M('UserCoin')->where(['userid' => $uid])->find(); //$this->usercoins;
        $user_coin[$coin] = format_num($user_coin[$coin], 8);
        $this->assign('user_coin', $user_coin);

        $CoinsBySymbol = (APP_DEBUG ? null : S('CoinsBySymbol_wallet'));


        if (!$CoinsBySymbol) {
            foreach ($Coins as $CLIST) {
                if ($CLIST['symbol'] != null) {
                    $CoinsBySymbol[$CLIST['symbol']][] = $CLIST;
                } else {
                    $CoinsBySymbol[$CLIST['name']][] = $CLIST;
                }
            }
            S('CoinsBySymbol_wallet', $CoinsBySymbol);
        }

        $Coinx = $CoinsBySymbol[$coin];

        foreach ($Coinx as $cinfo) {
            $i = $internal_coin = $cinfo['name'];
            $coin_address = strtolower($cinfo['name']) . 'b';
            $tokenof = $cinfo['tokenof'];

            $this->assign('zr_jz', $cinfo['zr_jz']);


            $codono_getCoreConfig = codono_getCoreConfig();
            if (!$codono_getCoreConfig) {
                $this->error(L('Incorrect Core Config'));
            }

            $this->assign('codono_opencoin', $codono_getCoreConfig['codono_opencoin']);

            if ($codono_getCoreConfig['codono_opencoin'] == 1 && $cinfo['type'] != 'offline') {

                if (!$cinfo['zr_jz']) {
                    $message[$i] = L('The current ban into the currency!');
                    $wallet[$i] = 0;
                } else {


                    if (!$user_coin[$coin_address] && !$user_coin[$tokenof . 'b']) {

                        if ($cinfo['type'] == 'rgb') {
                            $wallet[$i] = $address = md5(username() . $coin_address);

                            $rs = M('UserCoin')->where(['userid' => $uid])->save([$coin_address => $address]);
                            $user_exists = $this->userinfo['id']; //M('User')->where(array('id' => $uid))->getField('id');

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

                        if ($cinfo['type'] == 'xrp') {

                            $wallet[$i] = $address = $cinfo['codono_coinaddress']; //Contract Address
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

                                    $rs = M('user_coin')->where(['userid' => $uid])->save([$internal_coin . '_tag' => $the_dest_tag]);

                                    if ($rs) {
                                        $wallet[$i] = $address;
                                        $show_qr[$i] = 1;
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

                        //Tron starts
                        if ($cinfo['type'] == 'tron') {

                            $contract = $cinfo['dj_yh'];
                            $tokenof = $cinfo['tokenof'];

                            if (!$contract) {
                                //Call the interface to generate a new wallet address

                                $tron = A('Tron')->newAccount();

                                if (is_array($tron)) {

                                    $saveme[$coin_address] = $wallet = $tron['address_base58'];
                                    $tron_info = $tron;
                                    $tron_info['uid'] = $uid;
                                    $tron_info['private_key'] = cryptString($tron['private_key']);

                                    $rs[] = M('Tron')->add($tron_info);

                                    $rs[] =  $mo->table('codono_user_coin')->where(['userid' => $uid])->save($saveme);
                                } else {
                                    $wallet[$i] = L('Wallet System is currently offline 2! ' . $coin);
                                    $show_qr[$i] = 0;
                                }
                            } else {

                                $rs1 = $user_coin;
                                $tokenof = $cinfo['tokenof'];
                                $tokenofb = $tokenof . 'b';

                                if ($rs1[$tokenofb]) {
                                    $wallet[$i] = $rs1[$tokenofb];
                                    $show_qr[$i] = 1;
                                } else {
                                    //Call the interface to generate a new wallet address
                                    $tron = A('Tron')->newAccount();
                                    if ($tron) {
                                        $saveme[$coin_address] = $wallet = $tron['address_base58']; //token address
                                        $saveme[$tokenofb] = $wallet; //token parent address

                                        $tron_info = $tron;
                                        $tron_info['uid'] = $uid;
                                        $tron_info['private_key'] = cryptString($tron['private_key']);
                                        $rs[] = M('Tron')->add($tron_info);

                                        $rs[] =  $mo->table('codono_user_coin')->where(['userid' => $uid])->save($saveme);
                                    } else {
                                        $wallet[$i] = L('Wallet System is currently offline 1! ' . $coin);
                                        $show_qr[$i] = 0;
                                    }
                                }
                            }
                        }
                        //Tron  Ends
                        //cryptoapis starts
                        if ($cinfo['type'] == 'cryptoapis') {
                            $cryptoapi_config = [
                                'api_key' => cryptString($cinfo['dj_mm'], 'd'),
                                'network' => $cinfo['network'],
                            ];

                            $cryptoapi = CryptoApis($cryptoapi_config);
                            $supportedCryptoApisChains = $cryptoapi->allowedSymbols();
                            if (!in_array($coin, $supportedCryptoApisChains) && !in_array($tokenof, $supportedCryptoApisChains)) {
                                $wallet[$i] = 0;
                                $message[$i] = L('Wallet System is currently offline 2! ' . $coin);
                                $show_qr[$i] = 0;
                            }
                            $contract = $cinfo['dj_yh']; //Contract Address
                            $blockchain = $coin;
                            $walletId = $cinfo['dj_zj'];
                            $context = $uid;
                            $main_address = $cinfo['codono_coinaddress'];

                            if (!$contract) {
                                //Call the interface to generate a new wallet address
                                $wallet[$i] = $address = $cryptoapi->createAddress($blockchain, $walletId, $context, $main_address);

                                if ($address) {
                                    if ($tokenof) {
                                        $saveme[$tokenof . 'b'] = $address;
                                    } else {
                                        $saveme[$coin_address] = $address;
                                    }

                                    $rs =  $mo->table('codono_user_coin')->where(['userid' => $uid])->save($saveme);
                                } else {
                                    $wallet[$i] = 0;
                                    $message[$i] = L('Wallet System is currently offline 2! ' . $coin);
                                    $show_qr[$i] = 0;
                                }
                            } else {

                                //cryptoapi contract
                                $rs1 = $user_coin;
                                $tokenof = $cinfo['tokenof'];
                                $tokenofb = $tokenof . 'b';
                                if ($rs1[$tokenofb]) {
                                    $wallet[$i] = $address = $rs1[$tokenofb];
                                    $saveme[$coin_address] = $address;
                                    $cryptoapi->createTokenForwarding($blockchain, $main_address, $address, $contract, $context);

                                    $rs =  $mo->table('codono_user_coin')->where(['userid' => $uid])->save($saveme);
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
                                        $rs =  $mo->table('codono_user_coin')->where(['userid' => $uid])->save($saveme);
                                    } else {
                                        $wallet[$i] = 0;
                                        $message[$i] = L('Wallet System is currently offline 1! ' . $coin);
                                        $show_qr[$i] = 0;
                                    }
                                }
                            }
                        }
                        //cryptoapis  Ends
                        //substrate starts
                        if ($cinfo['type'] == 'substrate') {
                            $config = [
                                'host' => $cinfo['dj_zj'],
                                'port' => $cinfo['dj_dk'],
                                'api_key' => cryptString($cinfo['dj_mm'], 'd'),
                                'decimals' => $cinfo['cs_qk'],
                            ];

                            $substrate = Substrate($config);
                            $newAdd = json_decode($substrate->createAddress($uid));

                            if ((!$newAdd) || $newAdd->uid != $uid) {
                                $wallet[$i] = false;
                                $message[$i] = L('Wallet System is currently offline 3!');
                                $show_qr[$i] = 0;
                            } else {
                                $show_qr[$i] = 1;
                                $wallet_addr = $newAdd->address;
                                $wallet[$i] = $address = $wallet_addr;

                                if (!$address) {
                                    $this->error('Generate Wallet address error2!');
                                }

                                //$rs = M('UserCoin')->where(array('userid' => $uid))->save(array($coin_address => $address));
                                $rs = M()->execute("UPDATE `codono_user_coin` SET  `$coin_address` =  '$address' WHERE userid = '$uid' ");
                                if (!$rs) {
                                    $this->error('Add error address wallet3!');
                                }
                            }
                        }
                        //substrate ends
                        //blockgum starts
                        if ($cinfo['type'] == 'blockgum') {
                            $pcoin_bg = $cinfo['tokenof'] ?: $cinfo['name'];
                            $blockgum = blockgum($pcoin_bg);
                            $tokenof = $cinfo['tokenof'];
                            $tokenofb = $tokenof . 'b';
                            $contract = $cinfo['dj_yh'];

                            $newAdd = $blockgum->createAddress($uid);
                            //        debug($contract);


                            if (!isset($newAdd) || !isset($newAdd['uid']) || $newAdd['uid'] != $uid) {
                                $wallet[$i] = false;
                                $message[$i] = L('Wallet System is currently offline 3!');
                                $show_qr[$i] = 0;
                            } else {
                                $show_qr[$i] = 1;
                                $wallet_addr = $newAdd['address'];
                                $wallet[$i] = $address = $wallet_addr;

                                if (!$address) {
                                    $this->error('Generate Wallet address error2!');
                                }

                                if ($tokenof) {
                                    $saveme[$tokenof . 'b'] = $address;
                                } else {
                                    $saveme[$coin_address] = $address;
                                }


                                $rs =  $mo->table('codono_user_coin')->where(['userid' => $uid])->save($saveme);



                                if (!$rs) {
                                    $this->error('Add error address wallet3!');
                                }
                            }
                        }
                        //blockgum ends
                        //esmart starts
                        if ($cinfo['type'] == 'esmart') {

                            $contract = $cinfo['dj_yh']; //Contract Address
                            $dj_password = $cinfo['dj_mm'];
                            $dj_address = $cinfo['dj_zj'];
                            $dj_port = $cinfo['dj_dk'];
                            $esmart_config = [
                                'host' => $cinfo['dj_zj'],
                                'port' => $cinfo['dj_dk'],
                                'coinbase' => $cinfo['codono_coinaddress'],
                                'password' => cryptString($cinfo['dj_mm'], 'd'),
                                'contract' => $cinfo['dj_yh'],
                                'rpc_type' => $cinfo['rpc_type'],
                                'public_rpc' => $cinfo['public_rpc'],
                            ];
                            $Esmart = ESmart($esmart_config);


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

                                    $rs =  $mo->table('codono_user_coin')->where(['userid' => $uid])->save($saveme);
                                } else {
                                    $wallet[$i] = 0;
                                    $message[$i] = L('Wallet System is currently offline 2! ' . $coin);
                                    $show_qr[$i] = 0;
                                }
                            } else {

                                //esmart contract
                                $rs1 = $user_coin;
                                $tokenof = $cinfo['tokenof'];
                                $tokenofb = $tokenof . 'b';
                                if ($rs1[$tokenofb]) {
                                    $wallet[$i] = $address = $rs1[$tokenofb];
                                    $saveme[$coin_address] = $address;

                                    $rs =  $mo->table('codono_user_coin')->where(['userid' => $uid])->save($saveme);
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

                                        $rs =  $mo->table('codono_user_coin')->where(['userid' => $uid])->save($saveme);
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
                        if ($cinfo['type'] == 'coinpay') {

                            $dj_username = $cinfo['dj_yh'];
                            $dj_password = $cinfo['dj_mm'];
                            $dj_address = $cinfo['dj_zj'];
                            $dj_port = $cinfo['dj_dk'];

                            $cps_api = CoinPay($dj_username, $dj_password, $dj_address, $dj_port, 5, [], 1);
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
                                $rs = $mo->table('codono_user_coin')->where(['userid' => $uid])->save($coinpay_update_array);

                                if (!$rs) {
                                    $wallet[$i] = 0;
                                    $message[$i] = L('Wallet System is currently offline 1!');
                                    $show_qr[$i] = 0;
                                }
                            }
                        }
                        //CoinPayments  Ends
                        //WavesPlatform Starts

                        if ($cinfo['type'] == 'waves') {

                            $qbdz = 'wavesb';
                            $dj_username = $cinfo['dj_yh'];
                            $dj_password = $cinfo['dj_mm'];
                            $dj_address = $cinfo['dj_zj'];
                            $dj_port = $cinfo['dj_dk'];
                            $dj_decimal = $cinfo['cs_qk'];
                            $waves = WavesClient($dj_username, $dj_password, $dj_address, $dj_port, $dj_decimal, 5, [], 1);
                            $waves_coin = strtoupper($coin);
                            $information = json_decode($waves->Status(), true);

                            if ($information['blockchainHeight'] && $information['blockchainHeight'] <= 0) {
                                $wallet[$i] = L('Wallet System is currently offline 1!');
                                $show_qr[$i] = 0;
                            } else {
                                $show_qr[$i] = 1;
                                $rs1 = $this->usercoins; //M('UserCoin')->where(array('userid' => $uid))->find();
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
                                if ($show_qr == 1) {

                                    $rs =  $mo->table('codono_user_coin')->where(['userid' => $uid])->save([$qbdz => $address]);

                                    if (!$rs && $waves_good == 1) {
                                        $message[$i] = L('Wallet System is currently offline 3!');
                                        $wallet[$i] = 0;
                                    }
                                }
                            }
                        }
                        //WavesPlatform Ends
                        //blockio starts
                        if ($cinfo['type'] == 'blockio') {

                            $dj_username = $cinfo['dj_yh'];
                            $dj_password = $cinfo['dj_mm'];
                            $dj_address = $cinfo['dj_zj'];
                            $dj_port = $cinfo['dj_dk'];

                            $block_io = BlockIO($dj_username, $dj_password, $dj_address, $dj_port, 5, [], 1);
                            $json = $block_io->get_balance();

                            if (!isset($json->status) || $json->status != 'success') {
                                //$this->error(L('Wallet link failure! 1'));
                                $message[$i] = L('Wallet System is currently offline 2!');
                                $show_qr[$i] = 0;
                                $wallet[$i] = 0;
                            } else {
                                $show_qr[$i] = 1;
                                $wallet_addr = $block_io->get_address_by_label(['label' => username()])->data->address;

                                if (!is_array($wallet_addr)) {
                                    $getNewAddressInfo = $block_io->get_new_address(['label' => username()]);
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

                                $rs = M('UserCoin')->where(['userid' => $uid])->save([$coin_address => $address]);

                                if (!$rs) {
                                    $this->error('Add error address wallet3!');
                                }
                            }
                        }
                        //blockio  Ends

                        //cryptonote starts
                        if ($cinfo['type'] == 'cryptonote') {
                            $dj_username = $cinfo['dj_yh'];
                            $dj_password = $cinfo['dj_mm'];
                            $dj_address = $cinfo['dj_zj'];
                            $dj_port = $cinfo['dj_dk'];
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


                                $wallet_addr = $cinfo['codono_coinaddress'];
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
                                $rs = $mo->table('codono_user_coin')->where(['userid' => $uid])->save($cryptonote_update_array);


                                if (!$rs) {
                                    $this->error('Add error address wallet3!');
                                }
                            }
                        }
                        //CryptoNote Ended
                        //Bitcoin starts
                        if ($cinfo['type'] == 'qbb') {

                            $dj_username = $cinfo['dj_yh'];
                            $dj_password = $cinfo['dj_mm'];
                            $dj_address = $cinfo['dj_zj'];
                            $dj_port = $cinfo['dj_dk'];
                            $CoinClient = CoinClient($dj_username, $dj_password, $dj_address, $dj_port, 5, [], 1);
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


                                if ($tokenof) {
                                    $saveme[$tokenof . 'b'] = $address;
                                } else {
                                    $saveme[$coin_address] = $address;
                                }

                                if (!$address) {
                                    $this->error('Generate Wallet address error2!');
                                }

                                //$rs = M('UserCoin')->where(array('userid' => $uid))->save(array($coin_address => $address));
                                $rs = M('UserCoin')->where(['userid' => $uid])->save($saveme);
                                if (!$rs) {
                                    $this->error('Add error address wallet3!');
                                }
                            }
                        }
                    } else {

                        //$wallet[$i] = $user_coin[$coin . 'b'];

                        if (isset($user_coin[$tokenof . 'b'])) {
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

                if (!$cinfo['zr_jz']) {
                    $wallet = L('The current ban into the currency!');
                } else {

                    $wallet[$i] = $cinfo['codono_coinaddress'];

                    $cellphone = $this->userinfo['cellphone']; //M('User')->where(array('id' => $uid))->getField('cellphone');
                    $email = $this->userinfo['email']; //M('User')->where(array('id' => $uid))->getField('email');

                    if ($cellphone || $email) {
                        $cellphone = substr_replace($cellphone, '****', 3, 4);
                        $email = substr_replace($email, '****', 3, 4);
                    } else {
                        if (M_ONLY == 1) {
                            redirect(U('Home/User/cellphone'), $time = 5, $msg = L('Please Verify your Phone!'));
                        }
                    }

                    $this->assign('cellphone', $cellphone);
                    $this->assign('email', $email);
                }
            }
            if (is_array($wallet)) {
                $show_wallet = $wallet[$i];
            } else {
                $show_wallet = $wallet;
            }
            if (is_array($wallet) && isset($wallet[$i])) {
                $put_wallet = $wallet[$i];
            } else {
                $put_wallet = $message[$i] ?? null;
            }

            $tokenof = $cinfo['tokenof'];
            if ($tokenof && $user_coin[$tokenof . 'b']) {

                $accurate_address = $user_coin[$tokenof . 'b'];
            } else {
                $accurate_address = $show_wallet;
            }
            $network_info = $cinfo['js_yw'] ?: '';

            $InfoCoin[$i] = [
                'name' => $cinfo['name'],
                'title' => $cinfo['title'],
                'network' => $network_info,
                'tokenof' => $tokenof,
                'img' => './Upload/coin/' . $cinfo['img'],
                'deposit' => $cinfo['zr_jz'],
                'confirmations' => $cinfo['zr_dz'],
                'explorer' => $cinfo['js_wk'],
                'wallet' => $accurate_address,
                'message' => $message[$i] ?? null,
                'qr' => $show_qr[$i] ?? null,
                'dest_tag' => $dest_tag[$i] ?? null,
                'contract' => $cinfo['dj_yh']
            ];
        } //foreach ends`

        $where['userid'] = $uid;
        //$where['coinname'] = $coin;
        $Model = M('Myzr');
        $count = $Model->where($where)->count();
        $Page = new Page($count, 10);
        $show = $Page->show();
        $list = $Model->where($where)->order('id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $this->assign('show_dividend', self::SHOW_SITE_DIVIDEND);
        $this->assign('deposit_list', $list);
        $this->assign('coin', $coin);
        $this->assign('wallet', $wallet);
        $this->assign('dest_tag', $dest_tag);
        $this->assign('message', $message);
        $this->assign('show_qr', $show_qr);
        $this->assign('page', $show);
        $this->assign('infocoin', $InfoCoin);

        $this->display();
    }

    public function cryptowithdrawal()
    {
        $userid = userid();
        if (!$userid) {
            redirect(U('Login/login'));
        }
        $coin = I('get.coin', '', 'text');
        $network = I('get.network', '', 'text');


        $this->assign('prompt_text', D('Text')->get_content('finance_myzc'));

        $coin = $coin ? strtolower($coin) : C('xnb_mr');
        $isValidNetwork = $this->isValidCoin($network);
        if ($isValidNetwork) {
            $this->assign('selectednetwork', $network);
        }


        $this->assign('xnb', $coin);
        $Coins = C('coin_safe');
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
        foreach ($Coins as $k => $v) {
            if ($v['type'] != 'rmb') {
                if ($v['symbol'] != null && $v['symbol'] != $v['name']) {
                    continue;
                }
                $network_info = $v['js_yw'] ?: '';
                $tokenof = $v['tokenof'];
                $coin_list[$v['name']] = ['name' => $v['name'], 'title' => $v['title'], 'network' => $network_info, 'tokenof' => $tokenof, 'img' => '/Upload/coin/' . $v['img'], 'withdrawal' => $v['withdrawal'], 'confirmations' => $v['confirmations'], 'explorer' => $v['explorer'], 'zc_jz' => $v['zc_jz'], 'zc_fee' => $v['zc_fee'], 'zc_flat_fee' => $v['zc_flat_fee'], 'zc_coin' => $v['zc_coin'] ?: $v['name']];
                //$coin_list[$v['name']]['child'] =$CoinsBySymbol[$v['name']];
            }
        }


        ksort($coin_list);
        $this->assign('coin_list', $coin_list);
        $user_coin = M('UserCoin')->where(['userid' => $userid])->find();
        $user_coin[$coin] = format_num($user_coin[$coin], 8);
        $this->assign('user_coin', $user_coin);


        $Coinx = $CoinsBySymbol[$coin];

        $user = $this->userinfo;

        $is_ga = $user['ga'] ? 1 : 0;

        $cmcs = (APP_DEBUG ? null : S('cmcRates'));

        if (!$cmcs) {
            $cmcs = M('Coinmarketcap')->field(['symbol', 'price_usd'])->select();
            S('cmcrates', $cmcs);
        }

        if (!$user['fiat']) {
            $conversion_coin = SYSTEMCURRENCY;
        } else {
            $conversion_coin = $user['fiat'];
        }

        $multiplier = 1;
        $the_cms = [];
        $cms = [];
        foreach ($cmcs as $ckey => $cval) {
            if (strtolower($conversion_coin) != 'usd' && $cval['symbol'] == strtoupper($conversion_coin)) {
                $multiplier = $cval['price_usd'];
            }
            $the_cms[strtolower($cval['symbol'])] = $cval['price_usd'];
        }

        foreach ($the_cms as $key => $usd_value) {
            $cms[$key] = bcdiv($usd_value, $multiplier, 8);
        }

        $this->assign('is_ga', $is_ga);
        $this->assign('coin_list', $coin_list);
        $usercoins[$coin] = $this->usercoins[$coin];
        $user_coin = $usercoins;

        $user_coin[$coin] = format_num($user_coin[$coin], 8);
        $user_coin['converted'] = bcmul($user_coin[$coin], $cms[$coin], 8);
        $user_coin['conversion_coin'] = $conversion_coin;
        $this->assign('user_coin', $user_coin);
        $cellphone = $this->userinfo['cellphone'];
        $email = $this->userinfo['email'];
        $userWalletList = [];
        //var_dump([$coin,$network,$isValidNetwork,$coin_list[$coin]]);exit;

        if (!$coin_list[$coin]['withdrawal']) {
            $this->assign('zc_jz', L($coin . ': Withdrawals are temporarily disabled'));
        } else {


            if ($network != NULL && $isValidNetwork) {
                $userWalletList = M('UserWallet')->where(['userid' => $userid, 'status' => 1, 'coinname' => $network])->order('id desc')->select();
            }

            $this->assign('userWalletList', $userWalletList);


            if ($cellphone || $email) {
                $cellphone = substr_replace($cellphone, '****', 3, 4);
                $email = substr_replace($email, '****', 3, 4);
            }

            /*
             else {
                if (M_ONLY == 1) {
                    redirect(U('Home/User/cellphone'), $time = 5, $msg = L('Please Verify your Phone!'));
                }
            }
            */
        }
        $this->assign('cellphone', $cellphone);
        $this->assign('email', $email);
        $where['userid'] = $userid;
        $where['coinname'] = $coin;
        $Model = M('Myzc');
        $count = $Model->where($where)->count();
        $Page = new Page($count, 10);
        $show = $Page->show();
        $list = $Model->where($where)->order('id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $InfoCoin = [];
        foreach ($Coinx as $Coin) {
            $i = $Coin['name'];
            $network_info = $Coin['js_yw'] ?? '';
            $tokenof = $Coin['tokenof'];
            $InfoCoin[$i] = ['name' => $Coin['name'], 'title' => $Coin['title'], 'network' => $network_info, 'tokenof' => $tokenof, 'img' => './Upload/coin/' . $Coin['img'], 'deposit' => $Coin['zr_jz'], 'confirmations' => $Coin['confirmations'], 'explorer' => $Coin['explorer'], 'zc_jz' => (int)$Coin['zc_jz'], 'zc_fee' => $Coin['zc_fee'], 'zc_flat_fee' => $Coin['zc_flat_fee'], 'zc_min' => $Coin['zc_min'], 'zc_max' => $Coin['zc_max'], 'zc_coin' => $Coin['zc_coin'] ?: $Coin['coin']];
        }

        $this->assign('infocoin', $InfoCoin);
        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->display();
    }

    private function isValidCoin($coin): bool
    {
        $coins = C('coin_safe');

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


    /**
     * Verifies the Google Authenticator 2FA code provided by the user.
     *
     * @param array $user The user data array containing the Google Authenticator secret.
     * @param int $gacode The 2FA code entered by the user.
     * @return bool True if verification is successful, otherwise false.
     */
    private function verify2FACode($user_gacode, $input_gacode)
    {
        // Check if 2FA is required and the user has 2FA enabled
        if (self::G2FA_REQUIRED_FOR_WITHDRAWAL != 1 || !$user_gacode) {
            // 2FA is not required or the user does not have 2FA enabled
            return true;
        }

        // 2FA is required and the user has 2FA enabled
        if (!$input_gacode) {
            return false; // This line may not be reached if $this->error() halts execution
        }

        // Extract the 2FA secret from the user's data
        $arr = explode('|', $user_gacode);
        $secret = $arr[0];

        // Initialize the Google Authenticator class
        $ga = new GoogleAuthenticator();

        // Verify the 2FA code
        $ga_verification = $ga->verifyCode($secret, $input_gacode, 1); // The "1" here represents a 1-minute window for code verification

        if (!$ga_verification) {

            return false; // This line may not be reached if $this->error() halts execution
        }

        // 2FA code verification was successful
        return true;
    }

    /**
     * Validates the SMS code if required by the application settings.
     *
     * @param string $cellphone_verify The SMS code provided by the user.
     * @throws \Exception if the SMS code is invalid or incorrect.
     */
    private function validateSmsCodeIfNeeded($cellphone_verify)
    {
        if (M_ONLY == 1) {
            if (!check($cellphone_verify, 'd')) {
                $this->error(L('INVALID_SMS_CODE'));
            }

            if ($cellphone_verify != session('myzc_verify')) {
                $this->error(L('INCORRECT_SMS_CODE'));
            }
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
    private function userGroupLimit($uid, $coin, $num)
    {
        if (self::GROUP_WITHDRAWAL_LIMIT) {
            $usd_value = $this->getMarketValue($coin, $num);
            $last24HoursValue = $this->last24HourWithdrawalValue($uid);
            $uptoThisWithdrawal = bcadd($last24HoursValue, $usd_value, 8);

            $allowed_value = $this->getAllowedWithdrawalValue($uid);
            if ($uptoThisWithdrawal > $allowed_value) {
                $this->error('Maximum withdrawal limit reached, allowed is ' . $allowed_value . ' and you have used ' . $last24HoursValue);
            }
        }
    }
    private function getMarketValue($coin, $quantity, $conversion_coin = 'usd')
    {
        // Retrieve the current market data for the coin
        $marketData = C('market');
        $market = $coin . '_usdt';
        // Check if the coin exists in the market data and has a price
        if (isset($marketData[$market]) && isset($marketData[$market]['new_price'])) {
            // Get the current price from market data
            $coinPrice = $marketData[$market]['new_price'];
        } else {
            // Fallback to local CoinMarketCap data
            $coinPrice = $this->getCoinMarketCapPriceLocal($coin, $conversion_coin);

            if (!$coinPrice) {
                return 0;
            }
        }

        // Calculate the total value based on the quantity
        $marketValue = $coinPrice * $quantity;

        return $marketValue;
    }

    // Helper function to fetch price from local CoinMarketCap data
    private function getCoinMarketCapPriceLocal($coin, $conversion_coin = 'usd')
    {
        // Check if cached cmc data is available
        $cmcs = (APP_DEBUG ? null : S('cmcrates'));

        if (!$cmcs) {
            // If not cached, query the Coinmarketcap table and cache the results
            $cmcs = M('Coinmarketcap')->field(['symbol', 'price_usd'])->select();
            S('cmcrates', $cmcs);
        }

        $the_cms = [];
        $multiplier = 1;

        // Iterate through the Coinmarketcap data to find the price for the given coin
        foreach ($cmcs as $ckey => $cval) {
            if (strtolower($conversion_coin) != 'usd' && $cval['symbol'] == strtoupper($conversion_coin)) {
                $multiplier = $cval['price_usd'];
            }
            $the_cms[strtolower($cval['symbol'])] = $cval['price_usd'];
        }

        // If the coin exists in Coinmarketcap data, return the price
        if (isset($the_cms[strtolower($coin)])) {
            return $the_cms[strtolower($coin)] / $multiplier;
        }

        // Return false if the coin is not found
        return 0;
    }
    private function last24HourWithdrawalValue($uid)
    {
        $totalValue = 0;
        if ($uid == 0) {
            return $totalValue;
        }
        $last24Hours = time() - 86400;
        $where = ['userid' => $uid, 'addtime' => ['gt', $last24Hours]];
        $withdrawals = M('Myzc')->where($where)->select();

        foreach ($withdrawals as $withdrawal) {
            $totalValue += $this->getMarketValue($withdrawal['coin'], $withdrawal['mum']);
        }
        return $totalValue;
    }

    private function getAllowedWithdrawalValue($uid)
    {
        $userInfo = M('User')->where(['id' => $uid])->field('usertype,accounttype')->find();
        $level_id = $userInfo['usertype'];
        $group_type = $userInfo['accounttype'];
        $where = ['level_id' => $level_id, 'group_type' => $group_type];

        $groupAllowance = M('UserLevel')->where($where)->find();
        $allowedValue = 0;
        if ($groupAllowance) {
            $allowedValue = $groupAllowance['limit_to'];
        }
        return $allowedValue;
    }
}
