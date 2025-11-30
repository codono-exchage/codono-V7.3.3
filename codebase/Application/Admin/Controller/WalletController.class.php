<?php

namespace Admin\Controller;

class WalletController extends AdminController
{

    public function confirmPay()
    {
        $id = I('get.id', null, 'int');
        $flag=0;
        $myzc = M('Myzc')->where(array('id' => trim($id)))->find();
		
        if (!$myzc) {
            $this->error('Wrong ID!');
        }

        if ($myzc['status']) {
            $this->error('It has been already paid!');
        }

        $coin = $myzc['coinname'];
        $network = $myzc['network'];
		
        $isValidNetwork=$this->isValidCoin($network);
        if($isValidNetwork){
            $CoinInfo=C('coin')[$network];
        }else{
            $CoinInfo=C('coin')[$coin];
        }
		
        $dj_username = $CoinInfo['dj_yh'];
        $dj_password = $CoinInfo['dj_mm'];
        $dj_address = $CoinInfo['dj_zj'];
        $dj_port = $CoinInfo['dj_dk'];
        $dj_decimal = $CoinInfo['cs_qk'];
        $main_address = $CoinInfo['codono_coinaddress'];
        $mo = M();
        $peer = M('UserCoin')->where(array($coin . 'b' => $myzc['username']))->find();
        $user_coin = M('UserCoin')->where(array('userid' => $myzc['userid']))->find();
        $fee_user = M('UserCoin')->where(array($coin . 'b' => $CoinInfo['zc_user']))->find();

        /* Withdrawal exists on exchange so process it locally */
        if ($peer) {
            
            $mo->startTrans();

            $rs[] = $mo->table('codono_myzr')->add(array('userid' => $peer['userid'], 'username' => $myzc['username'], 'coinname' => $coin, 'txid' => md5($myzc['username'] . $user_coin[$coin . 'b'] . time()), 'num' => $myzc['num'], 'fee' => $myzc['fee'], 'mum' => $myzc['mum'], 'addtime' => time(), 'status' => 1));
            $rs[] = $mo->table('codono_user_coin')->where(array('userid' => $peer['userid']))->setInc($coin, $myzc['mum']);

            if (check_arr($rs)) {
                $mo->commit();
                // removed unlock/lock
                $this->success('Transfer success!');
            } else {
                $mo->rollback();
                // removed unlock/lock
                $this->error('There were some issues!');
            }

        }


        if ($CoinInfo['type'] == 'blockio') {
            $block_io = BlockIO($dj_username, $dj_password, $dj_address, $dj_port, 5, array(), 1);
            $json = $block_io->get_balance();

            if (!isset($json->status) || $json->status != 'success') {
                $this->error(L('Wallet link failure! blockio'));
            }

            $Coin = M('Coin')->where(array('name' => $myzc['coinname']))->find();

            $fee_user['userid'] = M('User')->where(array('id' => $Coin['zc_user']))->getField("id");

            
            $mo->startTrans();
            $rs = array();


            if (!$fee_user['userid']) {
                $fee_user['userid'] = 0;
            }

            if (0 < $myzc['fee']) {
                $rs[] = $mo->table('codono_myzc_fee')->add(array('userid' => $fee_user['userid'], 'username' => $Coin['zc_user'], 'coinname' => $coin, 'num' => $myzc['num'], 'fee' => $myzc['fee'], 'mum' => $myzc['mum'], 'type' => 2, 'addtime' => time(), 'status' => 1));

                if ($mo->table('codono_user_coin')->where(array($coin . 'b' => $Coin['zc_user']))->find()) {
                    $rs[] = $mo->table('codono_user_coin')->where(array('userid' => $Coin['zc_user']))->setInc($coin, $myzc['fee']);
                    //debug(array('lastsql' => $mo->table('codono_user_coin')->getLastSql()), 'Additional costs');
                } else {
                    $rs[] = $mo->table('codono_user_coin')->add(array($coin . 'b' => $Coin['zc_user'], $coin => $myzc['fee']));
                }
            }

            $rs[] = M('Myzc')->where(array('id' => trim($id)))->save(array('status' => 1));

            if (check_arr($rs)) {
                $mo->commit();
                // removed unlock/lock
                $valid_res = $block_io->validateaddress($myzc['username']);
                if (!$valid_res) {
                    $this->error($myzc['username'] . L(' It is not a valid address wallet!'));
                }


                if ($json->data->available_balance < (double)$myzc['mum']) {
                    $this->error(L('Wallet balance of less than'));
                }

                $send_amt = round($myzc['mum'], 8);
                $sendrs = $block_io->withdraw(array('amounts' => $send_amt, 'to_addresses' => $myzc['username']));

                if ($sendrs) {
                    if (isset($sendrs->status) && ($sendrs->status == 'success')) {
                        $flag = 1;
                    }
                } else {
                    $flag = 0;
                }

                if (!$flag) {
                    $mo->rollback();
                    // removed unlock/lock
                    $this->error('wallet server Withdraw currency failure!');
                } else {
                    $this->success('Transfer success!');
                }
            } else {
                $mo->rollback();
                // removed unlock/lock
                $this->error('Roll-out failure!' . implode('|', $rs) . $myzc['fee']);
            }

        }
        //substrate Withdrawal STARTS
        if ($CoinInfo['type'] == 'substrate') {

            $substrate_config = [
                'host' => $CoinInfo['dj_zj'],
                'port' => $CoinInfo['dj_dk'],
                'api_key' => cryptString($CoinInfo['dj_mm'], 'd'),
                'decimals' => $CoinInfo['cs_qk'],
            ];

            $substrate = Substrate($substrate_config);

            $Coin = M('Coin')->where(array('name' => $myzc['coinname']))->find();
            $fee_user = M('UserCoin')->where(array($coin . 'b' => $Coin['zc_user']))->find();

            $mo->startTrans();
            $rs = array();

            if (!$fee_user['userid']) {
                $fee_user['userid'] = 0;
            }

            if (0 < $myzc['fee']) {
                $rs[] = $mo->table('codono_myzc_fee')->add(array('userid' => $fee_user['userid'], 'username' => $Coin['zc_user'], 'coinname' => $coin, 'num' => $myzc['num'], 'fee' => $myzc['fee'], 'mum' => $myzc['mum'], 'type' => 2, 'addtime' => time(), 'status' => 1));

                if ($mo->table('codono_user_coin')->where(array($coin . 'b' => $Coin['zc_user']))->find()) {
                    $rs[] = $mo->table('codono_user_coin')->where(array('userid' => $Coin['zc_user']))->setInc($coin, $myzc['fee']);
                } else {
                    $rs[] = $mo->table('codono_user_coin')->add(array($coin . 'b' => $Coin['zc_user'], $coin => $myzc['fee']));
                }
            }


            $hash = null;
            if (check_arr($rs)) {

                $config['toaddress'] = strtolower($myzc['username']);
                $config['amount'] = (double)$myzc['mum'];

                $substrate_amount=$substrate->amount_encode($myzc['mum']);

                $request_sent=json_decode($substrate->withdraw($config['toaddress'],$substrate_amount,$myzc['id']),true);

                $flag = 1;
                $hash = 'pending';
                $mo->table('codono_myzc')->where(array('id' => $id))->save(array('txid' => $hash));
                if ($hash) $mo->execute("UPDATE `codono_myzc` SET  `hash` =  '$hash' WHERE id = '$id' ");
                $mo->table('codono_myzc')->where(array('id' => trim($id)))->save(array('status' => 1));
                $mo->commit();
                // removed unlock/lock
                $this->success('Transfer success!');
            } else {
                $mo->rollback();
                // removed unlock/lock
                $this->error('Roll-out failure!' . implode('|', $rs) . $myzc['fee']);
            }
        }
        //substrate withdrawal ENDS
          //blockgum Withdrawal STARTS
          if ($CoinInfo['type'] == 'blockgum') {

            $pcoin = $CoinInfo['tokenof'] ?: $CoinInfo['name'];
            $bg_decimals=$CoinInfo['cs_qk']?:18;
            $blockgum = blockgum($pcoin);

            $Coin = M('Coin')->where(array('name' => $myzc['coinname']))->find();
            $fee_user = M('UserCoin')->where(array($coin . 'b' => $Coin['zc_user']))->find();

            $mo->startTrans();
            $rs = array();

            if (!$fee_user['userid']) {
                $fee_user['userid'] = 0;
            }

            if (0 < $myzc['fee']) {
                $rs[] = $mo->table('codono_myzc_fee')->add(array('userid' => $fee_user['userid'], 'username' => $Coin['zc_user'], 'coinname' => $coin, 'num' => $myzc['num'], 'fee' => $myzc['fee'], 'mum' => $myzc['mum'], 'type' => 2, 'addtime' => time(), 'status' => 1));

                if ($mo->table('codono_user_coin')->where(array($coin . 'b' => $Coin['zc_user']))->find()) {
                    $rs[] = $mo->table('codono_user_coin')->where(array('userid' => $Coin['zc_user']))->setInc($coin, $myzc['fee']);
                } else {
                    $rs[] = $mo->table('codono_user_coin')->add(array($coin . 'b' => $Coin['zc_user'], $coin => $myzc['fee']));
                }
            }


            $hash = null;
            if (check_arr($rs)) {

                $addr = strtolower($myzc['username']);
                $config['amount'] = (double)$myzc['mum'];
                $aid=$myzc['id'];
                $pcoin = $CoinInfo['tokenof'] ?: $CoinInfo['name'];
                $contract_address=$CoinInfo['dj_yh']?:null;
                $decimals=$CoinInfo['cs_qk']?:18;
                $blockgum=blockgum($pcoin);
                $send_decimals=$decimals>8?6:$decimals;
                $blockgum_amount=format_num($myzc['mum'],$send_decimals);

                $request_sent=$blockgum->withdrawFromMain($addr,$blockgum_amount,$contract_address,$aid);
                $flag = 1;
                $hash = 'processing';
                $mo->table('codono_myzc')->where(array('id' => $id))->save(array('txid' => $hash));
                if ($hash) $mo->execute("UPDATE `codono_myzc` SET  `hash` =  '$hash' WHERE id = '$id' ");
                $mo->table('codono_myzc')->where(array('id' => trim($id)))->save(array('status' => 1));
                $mo->commit();
                // removed unlock/lock
                $this->success('Transfer success!');
            } else {
                $mo->rollback();
                // removed unlock/lock
                $this->error('Roll-out failure!' . implode('|', $rs) . $myzc['fee']);
            }
        }
        //blokgum withdrawal ENDS
        //Esmart STARTS
        if ($CoinInfo['type'] == 'esmart') {

            $contract_address = $dj_username; //Contract Address
            $dj_password = cryptString($CoinInfo['dj_mm'], 'd');
            $main_address = $CoinInfo['codono_coinaddress'];


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

            $Coin = M('Coin')->where(array('name' => $myzc['coinname']))->find();
            $fee_user = M('UserCoin')->where(array($coin . 'b' => $Coin['zc_user']))->find();

            
            

            $mo->startTrans();
            $rs = array();

            if (!$fee_user['userid']) {
                $fee_user['userid'] = 0;
            }

            if (0 < $myzc['fee']) {
                $rs[] = $mo->table('codono_myzc_fee')->add(array('userid' => $fee_user['userid'], 'username' => $Coin['zc_user'], 'coinname' => $coin, 'num' => $myzc['num'], 'fee' => $myzc['fee'], 'mum' => $myzc['mum'], 'type' => 2, 'addtime' => time(), 'status' => 1));

                if ($mo->table('codono_user_coin')->where(array($coin . 'b' => $Coin['zc_user']))->find()) {
                    $rs[] = $mo->table('codono_user_coin')->where(array('userid' => $Coin['zc_user']))->setInc($coin, $myzc['fee']);
                    //debug(array('lastsql' => $mo->table('codono_user_coin')->getLastSql()), 'Additional costs');
                } else {
                    $rs[] = $mo->table('codono_user_coin')->add(array($coin . 'b' => $Coin['zc_user'], $coin => $myzc['fee']));
                }
            }


            $hash = null;
            if (check_arr($rs)) {
                //Contract Address withdrawal
                if ($contract_address) {

                    $config['fromaddress'] = $main_address;
                    $config['toaddress'] = strtolower($myzc['username']);
                    $config['token'] = $contract_address;
                    $config['type'] = $coin;
                    $config['amount'] = (double)$myzc['mum'];
                    $config['password'] = $dj_password;

                    $sendrs = $Esmart->transferToken($config['toaddress'], $config['amount'], $config['token'], $dj_decimal);
                } else {
                    //esmart


                    $config['fromaddress'] = $main_address;
                    $config['toaddress'] = strtolower($myzc['username']);
                    $config['amount'] = (double)$myzc['mum'];
                    $config['password'] = $dj_password;
                    $sendrs = $Esmart->transferFromCoinbase($config['toaddress'], (double)$myzc['mum']);

                }

                if ($sendrs) {
                    $flag = 1;
                    $arr = json_decode($sendrs, true);
                    $hash = $arr['result'] ?: $arr['error']['message'];

                    if (isset($arr['status']) && ($arr['status'] == 0)) {
                        $flag = 0;
                    }
                } else {
                    $flag = 0;
                }

                if ($flag == 0) {
                    $mo->rollback();
                    // removed unlock/lock
                    $this->error('Esmart Server issues!:' . ($sendrs));
                } else {
                    $mo->table('codono_myzc')->where(array('id' => $id))->save(array('txid' => $hash));
                    if ($hash) $mo->execute("UPDATE `codono_myzc` SET  `hash` =  '$hash' WHERE id = '$id' ");
                    $mo->table('codono_myzc')->where(array('id' => trim($id)))->save(array('status' => 1));
                    $mo->commit();
                    // removed unlock/lock
                    $this->success('Transfer success!');
                }
            } else {
                $mo->rollback();
                // removed unlock/lock
                $this->error('Roll-out failure!' . implode('|', $rs) . $myzc['fee']);
            }
        }
        //Esmart ENDS
        //cryptoapis STARTS
        if ($CoinInfo['type'] == 'cryptoapis') {

            $contract_address = $contract = $CoinInfo['dj_yh'] ?: null;//Contract Address
            $dj_password = cryptString($CoinInfo['dj_mm'], 'd');


            $network=$coin;
            $cryptoapi_config = array(
                'api_key' => cryptString($CoinInfo['dj_mm'], 'd'),
                'network' => $CoinInfo['network'],
            );
            $cryptoapi = CryptoApis($cryptoapi_config);
            $supportedCryptoApisChains = $cryptoapi->allowedSymbols();
            if (!in_array($network, $supportedCryptoApisChains)) {
                $this->success('Withdrawal has been processed!');
            }

            $Coin = M('Coin')->where(array('name' => $myzc['coinname']))->find();
            $fee_user = M('UserCoin')->where(array($coin . 'b' => $Coin['zc_user']))->find();
            $user_coin = M('UserCoin')->where(array('userid' => $myzc['userid']))->find();
            $zhannei = M('UserCoin')->where(array($coin . 'b' => $myzc['username']))->find();
            $mo = M();



            $mo->startTrans();
            $rs = array();

            if ($zhannei) {
                $rs[] = $mo->table('codono_myzr')->add(array('userid' => $zhannei['userid'], 'username' => $myzc['username'], 'coinname' => $coin, 'txid' => md5($myzc['username'] . $user_coin[$coin . 'b'] . time()), 'num' => $myzc['num'], 'fee' => $myzc['fee'], 'mum' => $myzc['mum'], 'addtime' => time(), 'status' => 1));
                $rs[] = $r = $mo->table('codono_user_coin')->where(array('userid' => $zhannei['userid']))->setInc($coin, $myzc['mum']);
            }

            if (!$fee_user['userid']) {
                $fee_user['userid'] = 0;
            }

            if (0 < $myzc['fee']) {
                $rs[] = $mo->table('codono_myzc_fee')->add(array('userid' => $fee_user['userid'], 'username' => $Coin['zc_user'], 'coinname' => $coin, 'num' => $myzc['num'], 'fee' => $myzc['fee'], 'mum' => $myzc['mum'], 'type' => 2, 'addtime' => time(), 'status' => 1));

                if ($mo->table('codono_user_coin')->where(array($coin . 'b' => $Coin['zc_user']))->find()) {
                    //$rs[] = $mo->table('codono_user_coin')->where(array('userid' => $Coin['zc_user']))->setInc($coin, $myzc['fee']);
                    debug(array('lastsql' => $mo->table('codono_user_coin')->getLastSql()), 'Additional costs');
                } else {
                    $rs[] = $mo->table('codono_user_coin')->add(array($coin . 'b' => $Coin['zc_user'], $coin => $myzc['fee']));
                }
            }
            $hash = null;
            $contract_address = $contract = $CoinInfo['dj_yh'] ?: null;//Contract Address
            $blockchain = $coin;
            $walletId = $CoinInfo['dj_zj'];
            $context = $myzc['userid'];
            $main_address = $CoinInfo['codono_coinaddress'];
            $amount = (double)$myzc['mum'];
            $to_address = $myzc['username'];
            $aid=$myzc['id'];
            $tx_note = md5($blockchain . $context . $network . $amount  .$aid. $contract);
            if (check_arr($rs)) {

                if ($contract_address) {
                    //Contract Address transfer out
                    $sendrs = $cryptoapi->withdraw($blockchain, $walletId, $main_address, $to_address, $amount, $tx_note, $context, $contract_address);
                } else {

                    $sendrs = $cryptoapi->withdraw($blockchain, $walletId, $main_address, $to_address, $amount, $tx_note, $context);

                }

                if ($sendrs && $aid) {
                    $memo = $sendrs->transactionRequestId;
                    M('Myzc')->where(array('id' => $aid))->save(array('memo' => $memo));
                    $mo->commit();
                }else{
                    $mo->rollback();

                    $this->error('Withdrawal Failed!');
                }
            } else {
                $mo->rollback();
                // removed unlock/lock
                $this->error('Roll-out failure!' . implode('|', $rs) . $myzc['fee']);
            }
        }
        //CryptoApis ENDS
		//Tron STARTS
        if ($CoinInfo['type'] == 'tron') {

            $contract_address = $dj_username; //Contract Address
            $main_address = $CoinInfo['codono_coinaddress'];

            $ContractAddress = $contract_address;
            $tron = TronClient();

            $dj_password = cryptString($CoinInfo['dj_mm'], 'd');


            $Coin = M('Coin')->where(array('name' => $myzc['coinname']))->find();
            $fee_user = M('UserCoin')->where(array($coin . 'b' => $Coin['zc_user']))->find();

            
            

            $mo->startTrans();
            $rs = array();

            if (!$fee_user['userid']) {
                $fee_user['userid'] = 0;
            }

            if (0 < $myzc['fee']) {
                $rs[] = $mo->table('codono_myzc_fee')->add(array('userid' => $fee_user['userid'], 'username' => $Coin['zc_user'], 'coinname' => $coin, 'num' => $myzc['num'], 'fee' => $myzc['fee'], 'mum' => $myzc['mum'], 'type' => 2, 'addtime' => time(), 'status' => 1));

                if ($mo->table('codono_user_coin')->where(array($coin . 'b' => $Coin['zc_user']))->find()) {
                    $rs[] = $mo->table('codono_user_coin')->where(array('userid' => $Coin['zc_user']))->setInc($coin, $myzc['fee']);
                    //debug(array('lastsql' => $mo->table('codono_user_coin')->getLastSql()), 'Additional costs');
                } else {
                    $rs[] = $mo->table('codono_user_coin')->add(array($coin . 'b' => $Coin['zc_user'], $coin => $myzc['fee']));
                }
            }


            $hash = null;
            if (check_arr($rs)) {
                //Contract Address withdrawal
                $decimals = $CoinInfo['cs_qk'];
				$amount = (double)$myzc['mum'];
				$addr=$myzc['username'];
				$priv = cryptString($CoinInfo['dj_mm'], 'd');
                if (strpos($ContractAddress, 'T') !== false) {

                    $abi = $tron->getAbi($CoinInfo['name']);
                    $sendrs = $tron->transferTrc20($abi, $ContractAddress, $addr, $amount, $main_address, $priv, $decimals);
                } else if ($ContractAddress) {


                    $sendrs = $tron->transferTrc10($addr, $amount, $ContractAddress, $main_address, $priv, $decimals);

                } else {
                    $sendrs = $tron->sendTransaction($addr, $amount, $main_address, $priv);
                }
                if ($sendrs['result'] && $sendrs['txid'] ) {

                    $hash = $sendrs['txid'];

                    $flag = 1;
                } else {
                    $flag = 0;
                }

                if ($flag == 0) {
                    $mo->rollback();
                    $this->error('Tron  issues!:' . ($sendrs));
                } else {
                    $mo->table('codono_myzc')->where(array('id' => $id))->save(array('txid' => $hash));
                    if ($hash) $mo->execute("UPDATE `codono_myzc` SET  `hash` =  '$hash' WHERE id = '$id' ");
                    $mo->table('codono_myzc')->where(array('id' => trim($id)))->save(array('status' => 1));
                    $mo->commit();
                    // removed unlock/lock
                    $this->success('Transfer success!');
                }
            } else {
                $mo->rollback();
                // removed unlock/lock
                $this->error('Roll-out failure!' . implode('|', $rs) . $myzc['fee']);
            }
        }
        //Tron ENDS
		
        //xrp starts
        if ($CoinInfo['type'] == 'xrp') {
            if (!isset($myzc['dest_tag']) || $myzc['dest_tag'] == 0 || $myzc['dest_tag'] = "") {
                $this->error('Make sure correct dest_tag is defined');
            }
            $xrpData = C('coin')['xrp'];
            if ($xrpData) {

                $xrpClient = XrpClient($xrpData['dj_zj'], $xrpData['dj_dk'], $xrpData['codono_coinaddress'], $xrpData['dj_mm']);

                $sign = $xrpClient->sign((double)$myzc['mum'], $myzc['username'], $myzc['dest_tag']);
                if (strtolower($sign['result']['status']) == 'success') {
                    $submit = $xrpClient->submit($sign['result']['tx_blob']);
                    if (strtolower($submit['result']['status']) == 'success') {
                        $hash = $submit['result']['tx_json']['hash'];
                        M('Myzc')->where(array('id' => $id))->save(array('txid' => $hash));
                        if ($hash) M()->execute("UPDATE `codono_myzc` SET  `hash` =  '$hash' WHERE id = " . $id);
                        $this->success('Successfully transferred out');
                    } else {
                        $this->error('Server transfer failed');
                    }
                } else {
                    $this->error('Server transfer failed');
                }
            }
        }
        //xrp ends

        //CoinPayments  Starts
        if ($CoinInfo['type'] == 'coinpay') {

            $dj_password = $CoinInfo['dj_mm'];
            $cps_api = CoinPay($dj_username, $dj_password, $dj_address, $dj_port, 5, array(), 1);
            $information = $cps_api->GetBasicInfo();
            $coinpay_coin = strtoupper($coin);

            $can_withdraw = 1;
            if ($information['error'] != 'ok' || !isset($information['result']['username'])) {
                //         $this->error(L('Wallet link failure! Coinpayments'));
                //debug($coin . ' can not be connetcted at time:' . time() . '<br/>');
                $can_withdraw = 0;
            }

            //
            //TODO :Find a valid way to validate coin address
            if (strlen($myzc['username']) > 8) {
                $valid_res = 1;
            } else {
                $valid_res = 0;
            }

            if (!$valid_res) {
                $this->error($myzc['username'] . L(' It is not a valid address wallet!'));
            }

            $balances = $cps_api->GetAllCoinBalances();

            if ($balances['result'][$coinpay_coin]['balancef'] < $myzc['num']) {
                //$this->error(L('Can not be withdrawn due to system'));
                // debug($coin . ' Balance is lower than  ' . $myzc['num'] . ' at time:' . time() . '<br/>');
                $can_withdraw = 0;
            }

            $Coin = M('Coin')->where(array('name' => $myzc['coinname']))->find();
            $fee_user = M('UserCoin')->where(array($coin . 'b' => $Coin['zc_user']))->find();


            
            $mo->startTrans();
            $rs = array();

            if (!$fee_user['userid']) {
                $fee_user['userid'] = 0;
            }

            if (0 < $myzc['fee']) {
                $rs[] = $mo->table('codono_myzc_fee')->add(array('userid' => $fee_user['userid'], 'username' => $Coin['zc_user'], 'coinname' => $coin, 'num' => $myzc['num'], 'fee' => $myzc['fee'], 'mum' => $myzc['mum'], 'type' => 2, 'addtime' => time(), 'status' => 1));

                if ($mo->table('codono_user_coin')->where(array($coin . 'b' => $Coin['zc_user']))->find()) {
                    $rs[] = $mo->table('codono_user_coin')->where(array('userid' => $Coin['zc_user']))->setInc($coin, $myzc['fee']);
                } else {
                    $rs[] = $mo->table('codono_user_coin')->add(array($coin . 'b' => $Coin['zc_user'], $coin => $myzc['fee']));
                }
            }

            $rs[] = M('Myzc')->where(array('id' => trim($id)))->save(array('status' => 1));

            if (check_arr($rs)) {
                $mo->commit();
                // removed unlock/lock
                $buyer_email = M('User')->where(array('id' => $myzc['userid']))->getField('email');
                $dest_tag = $myzc['dest_tag'];
                $withdrawals = ['amount' => (double)$myzc['mum'],
                    'add_tx_fee' => 0,
                    'auto_confirm' => 1, //Auto confirm 1 or 0
                    'currency' => $coinpay_coin,
                    'address' => $myzc['username'],
                    'ipn_url' => SITE_URL . '/IPN/confirm',
                    'note' => $buyer_email];
                if ($dest_tag != 0 && $dest_tag != NULL) {
                    $withdrawals['dest_tag'] = $dest_tag;
                }

                $the_withdrawal = $cps_api->CreateWithdrawal($withdrawals);


                if ($the_withdrawal["error"] != "ok") {
                    $the_status = false;
                    $mo->rollback();
                    // removed unlock/lock
                    $this->error('Your withdrawal could not be done !' . $the_withdrawal["error"]);

                } else {
                    $the_status = true;
                    $cp_withdrawal_id = $the_withdrawal["result"]["id"];
                    M('Myzc')->where(array('id' => $id))->save(array('hash' => $cp_withdrawal_id));
                    $this->success('Successful Withdrawal!');
                }
            } else {
                $mo->rollback();
                // removed unlock/lock
                $this->error('Roll-out failure!' . implode('|', $rs) . $myzc['fee']);
            }
        }
        if ($CoinInfo['type'] == 'cryptonote') {
            $cryptonote = CryptoNote($dj_address, $dj_port);
            $open_wallet = $cryptonote->open_wallet($dj_username, $dj_password);

            $json = json_decode($cryptonote->get_height());
            if (!isset($json->height) || $json->error != 0 || !$open_wallet) {
                $the_status = 1;
                $this->error('CryptoNote Connection Failed ');
            }

            $Coin = C('coin')[$myzc['coinname']];

            $fee_user = M('UserCoin')->where(array($coin . 'b' => $Coin['zc_user']))->find();
            $user_coin = M('UserCoin')->where(array('userid' => $myzc['userid']))->find();

            $mo = M();
            
            $mo->startTrans();
            $rs = array();


            if (!$fee_user['userid']) {
                $fee_user['userid'] = 0;
            }

            if (0 < $myzc['fee']) {
                $rs[] = $mo->table('codono_myzc_fee')->add(array('userid' => $fee_user['userid'], 'username' => $Coin['zc_user'], 'coinname' => $coin, 'num' => $myzc['num'], 'fee' => $myzc['fee'], 'mum' => $myzc['mum'], 'type' => 2, 'addtime' => time(), 'status' => 1));

                if ($mo->table('codono_user_coin')->where(array($coin . 'b' => $Coin['zc_user']))->find()) {
                    $rs[] = $mo->table('codono_user_coin')->where(array('userid' => $Coin['zc_user']))->setInc($coin, $myzc['fee']);
                    // debug(array('lastsql' => $mo->table('codono_user_coin')->getLastSql()), 'Additional costs');
                } else {
                    $rs[] = $mo->table('codono_user_coin')->add(array($coin . 'b' => $Coin['zc_user'], $coin => $myzc['fee']));
                }
            }

            $rs[] = M('Myzc')->where(array('id' => trim($id)))->save(array('status' => 1));

            if (check_arr($rs)) {
                $mo->commit();
                // removed unlock/lock
                $bal_info = json_decode($cryptonote->getBalance());
                $crypto_balance = $cryptonote->deAmount($bal_info->available_balance);
                if ($crypto_balance < (double)$myzc['mum']) {
                    $this->error('Wallet balance is ' . $crypto_balance . ' of less than required ' . $myzc['mum']);
                }

                $send_amt = round($myzc['mum'], 8);
                $transData = [
                    [
                        "amount" => $send_amt,
                        "address" => $myzc['username']
                    ]
                ];
                $sendrs = json_decode($cryptonote->transfer($transData, $myzc['dest_tag']));

                if ($sendrs->error == 0) {
                    if (isset($sendrs->tx_hash) && isset($sendrs->tx_key)) {
                        $flag = 1;
                    }
                } else {
                    $flag = 0;
                }

                $hash = $sendrs->tx_key;
                $txid = $sendrs->tx_hash;
                if ($hash && $txid) {
                    M('Myzc')->where(array('id' => $id))->save(array('txid' => $sendrs->tx_hash, 'hash' => $sendrs->tx_key, 'status' => 1));
                }

                if (!$flag) {
                    $mo->rollback();
                    // removed unlock/lock
                    $this->error('wallet server Withdraw currency failure!');
                } else {
                    $mo->commit();
                    // removed unlock/lock
                    $this->success('Transfer success!');
                }
            } else {
                $mo->rollback();
                // removed unlock/lock
                $this->error('Roll-out failure!' . implode('|', $rs) . $myzc['fee']);
            }

        }
        if ($CoinInfo['type'] == 'waves') {
            $waves = WavesClient($dj_username, $dj_password, $dj_address, $dj_port, $dj_decimal, 5, array(), 1);
            $json = json_decode($waves->status(), true);

            if (!isset($json['blockchainHeight']) || $json['blockchainHeight'] <= 0) {
                $this->error($coin . 'Wallet link failure!');
            }


            $Coin = C('coin')[$myzc['coinname']];

            $fee_user = M('UserCoin')->where(array($coin . 'b' => $Coin['zc_user']))->find();
            $user_coin = M('UserCoin')->where(array('userid' => $myzc['userid']))->find();

            $mo = M();
            
            $mo->startTrans();
            $rs = array();

            if (!$fee_user['userid']) {
                $fee_user['userid'] = 0;
            }

            if (0 < $myzc['fee']) {
                $rs[] = $mo->table('codono_myzc_fee')->add(array('userid' => $fee_user['userid'], 'username' => $Coin['zc_user'], 'coinname' => $coin, 'num' => $myzc['num'], 'fee' => $myzc['fee'], 'mum' => $myzc['mum'], 'type' => 2, 'addtime' => time(), 'status' => 1));

                if ($mo->table('codono_user_coin')->where(array($coin . 'b' => $Coin['zc_user']))->find()) {
                    $rs[] = $mo->table('codono_user_coin')->where(array('userid' => $Coin['zc_user']))->setInc($coin, $myzc['fee']);
                    //debug(array('lastsql' => $mo->table('codono_user_coin')->getLastSql()), 'Additional costs');
                } else {
                    $rs[] = $mo->table('codono_user_coin')->add(array($coin . 'b' => $Coin['zc_user'], $coin => $myzc['fee']));
                }
            }

            $rs[] = M('Myzc')->where(array('id' => trim($id)))->save(array('status' => 1));

            if (check_arr($rs)) {
                $mo->commit();
                // removed unlock/lock
                if (strlen($myzc['username']) > 30) {
                    $valid_res = 1;
                } else {
                    $valid_res = 0;
                }

                if ($valid_res == 0) {
                    $this->error($myzc['username'] . L(' It is not a valid address wallet!'));
                }


                if ($json->data->available_balance < (double)$myzc['mum']) {
                    $this->error(L('Wallet balance of less than'));
                }

                $balances = json_decode($waves->Balance($main_address, $dj_username), true);
                $wave_main_balance = $waves->deAmount($balances['balance'], $dj_decimal);
                if ($wave_main_balance < $myzc['mum']) {
                    $this->error('Wallet balance is ' . $wave_main_balance . ' Needed: ' . $myzc['mum']);
                }


                $send_amt = round($myzc['mum'], 8);
                $wavesend_response = $waves->Send($main_address, $myzc['username'], $send_amt, $dj_username);
                $the_withdrawal = json_decode($wavesend_response, true);

                if ($the_withdrawal["error"]) {
                    $flag = 0;
                    $error_message = $the_withdrawal["message"];
                } else {
                    $error_message = array();
                    $flag = 1;
                }

                if ($flag != 1) {
                    $mo->rollback();
                    // removed unlock/lock
                    $this->error('Failed!' . $error_message);
                } else {
                    $mo->commit();
                    // removed unlock/lock
                    M('Myzc')->where(array('id' => $id))->save(array('txid' => $the_withdrawal['id']));
                    $this->success('Transfer success!');
                }
            } else {
                $mo->rollback();
                // removed unlock/lock
                $this->error('Roll-out failure!' . implode('|', $rs) . $myzc['fee']);
            }

        }
        if ($CoinInfo['type'] == 'qbb') {

            $CoinClient = CoinClient($dj_username, $dj_password, $dj_address, $dj_port, 5, array(), 1);
            $json = $CoinClient->getinfo();

            if (!isset($json['version']) || !$json['version']) {
                $this->error('Wallet link failure! 3');
            }

            $Coin = M('Coin')->where(array('name' => $myzc['coinname']))->find();
            $fee_user = M('UserCoin')->where(array($coin . 'b' => $Coin['zc_user']))->find();
            $user_coin = M('UserCoin')->where(array('userid' => $myzc['userid']))->find();

            $mo = M();
            
            $mo->startTrans();
            $rs = array();

            if (!$fee_user['userid']) {
                $fee_user['userid'] = 0;
            }

            if (0 < $myzc['fee']) {
                $rs[] = $mo->table('codono_myzc_fee')->add(array('userid' => $fee_user['userid'], 'username' => $Coin['zc_user'], 'coinname' => $coin, 'num' => $myzc['num'], 'fee' => $myzc['fee'], 'mum' => $myzc['mum'], 'type' => 2, 'addtime' => time(), 'status' => 1));

                if ($mo->table('codono_user_coin')->where(array($coin . 'b' => $Coin['zc_user']))->find()) {
                    $rs[] = $mo->table('codono_user_coin')->where(array('userid' => $Coin['zc_user']))->setInc($coin, $myzc['fee']);
                    //debug(array('lastsql' => $mo->table('codono_user_coin')->getLastSql()), 'Additional costs');
                } else {
                    $rs[] = $mo->table('codono_user_coin')->add(array($coin . 'b' => $Coin['zc_user'], $coin => $myzc['fee']));
                }
            }

            $rs[] = M('Myzc')->where(array('id' => trim($id)))->save(array('status' => 1));

            if (check_arr($rs)) {
                $mo->commit();
                // removed unlock/lock
                $contract=C('coin')[$coin]['contract'];
                if($contract){
                    $sendrs = $CoinClient->token('send',$contract,$myzc['username'], (double)$myzc['mum']);     
                }else{
                    $sendrs = $CoinClient->sendtoaddress($myzc['username'], (double)$myzc['mum']);     
                }

                if (is_array($sendrs)) {
                    $flag = 1;
                    $arr = json_decode($sendrs, true);
					$hash=$arr['result'];
                    if (isset($arr['status']) && ($arr['status'] == 0)) {
                        $flag = 0;
                    }
                } elseif(strlen($sendrs)==64){
					$flag = 1;
					$hash=$sendrs;
				}else{
					$hash='';
                    $flag = 0;
                }

                if (!$flag) {
                    $mo->rollback();
                    // removed unlock/lock
                    $this->error('wallet server Withdraw currency failure!');
                } else {
                    $mo->commit();
                    // removed unlock/lock
                    M('Myzc')->where(array('id' => $id))->save(array('txid' => $hash));
                    $this->success('Transfer success!');
                }
            } else {
                $mo->rollback();
                // removed unlock/lock
                $this->error('Roll-out failure!' . implode('|', $rs) . $myzc['fee']);
            }
        }
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
}
