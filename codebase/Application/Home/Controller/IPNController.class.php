<?php

namespace Home\Controller;

class IPNController extends HomeController
{
    private function errorAndDie($error_msg)
    {
        echo $error_msg;
        clog('IPN_'.__FUNCTION__,$error_msg);
        die();

    }

    private function FindWavesAssetID($assetid)
    {
        //	$assetid="4Zekj8nGpwzuEw1B7JFxLnDWmfcaFHyD8ZZKVWLwBbUC";
        $assetid = trim($assetid);
        $assetid = $assetid ?: "";
        $coin = M('Coin')->field('name')->where(array('type' => 'waves', 'status' => 1, 'dj_yh' => $assetid))->find();
        return ($coin['name'] ?: null);
    }

    private function ReverseAsset($assetid): array
    {
        $assetid = trim($assetid);
        $rsp = M('Coin')->where(array('status' => 1, 'type' => 'waves', 'dj_yh' => $assetid))->find();
        if ($rsp && $rsp['zr_jz'] == 1) {
            $coininfo['error'] = 0;
            $coininfo['decimal'] = $rsp['cs_qk'];
            $coininfo['name'] = $rsp['name'];
        } else {
            $coininfo['error'] = 1;
        }
        return $coininfo;
    }


    public function WavesDeposit()
    {
        $userid = userid();
        if (!$userid) {
            die('Please login');
        } else {
            $coinb = 'wavesb';
            $address = M('UserCoin')->where(array('userid' => $userid))->field($coinb)->find();
            echo "Your UserID is " . userid() . " and address is " . $address[$coinb] . "<br/>";
        }
        $coin = 'waves';
        $dj_username = C('coin')[$coin]['dj_yh'];  //Assetid
        $dj_password = C('coin')[$coin]['dj_mm'];
        $dj_address = C('coin')[$coin]['dj_zj'];
        $dj_port = C('coin')[$coin]['dj_dk'];
        $dj_decimal = (int)C('coin')[$coin]['cs_qk'];
        //   $main_address = C('coin')[$coin]['codono_coinaddress'];

        $waves = WavesClient($dj_username, $dj_password, $dj_address, $dj_port, $dj_decimal, 5, array(), 1);
        $information = json_decode($waves->status(), true);

        if ($information == NULL || ($information['blockchainHeight'] && $information['blockchainHeight'] <= 0)) {
            die('Either Waves is not available for sync Or we are not able to connect it');
        }
        $recipient = $address[$coinb];

        $txs[$coin] = json_decode($waves->AddressTxInfo($recipient, 30), true);

        $coinList = M('Coin')->where(array('status' => 1, 'type' => 'waves'))->select();

        foreach ($coinList as $k => $v) {

            $coin = $v['name'];
            echo "<br/>++++++++++Checking " . $coin . " and its assets++++++++++<br/>";

            if (!$coin) {
                $this->error('No Such Coins');
            }


            foreach ($txs['waves'][0] as $tx) {

                if ($tx['assetId']) {
                    $coininfo = $this->ReverseAsset($tx['assetId']);
                    $dj_decimal = $coininfo['decimal'];
                    $name = $coininfo['name'];

                    if ($coininfo['error'] == 1) {
                        echo $tx['assetId'] . " No Such Asset<br/>";
                        continue;
                    }
                } else {
                    $dj_decimal = 8;
                    $name = 'waves';
                    //checking if amount is lower than 0.002 then skip
                    if ($tx['amount'] < 200000) {
                        continue;
                    }

                }

                echo "<br/>Checking TXID" . $tx['id'] . "<br/>";

                if ($name != $coin) {
                    $checkname = $tx['assetId'] ?: 'waves';
                    echo "<br/><b>This Tx" . $tx['id'] . " belongs to " . $checkname . ' [' . $name . ']' . ' Skipping and Checking Next</b><br/>';
                    continue;
                }
                //Only transfer deposits
                if ($tx['type'] == 4 && $tx['recipient'] == $recipient) {
                    //All recipients
                    $record = json_decode($waves->ValidateAddress($tx['recipient']), true);
                    if ($record['valid'] == true && $record['address'] == $tx['recipient']) {

                        $already_processed = M('Myzr')->where(array('txid' => $tx['id']))->find();
                        if ($already_processed) {
                            echo $tx['recipient'] . '=>' . $tx['id'] . ' hash has already been added , Look for another TX' . "<br/>";
                            continue;
                        }

                        //We have found that This tx belongs to someone

                        //Now lets find if There is such an asset , and which is the coins , WAVES, MAIS or any token_get_all

                        $coin = $this->FindWavesAssetID($tx['assetId']);

                        if ($coin == NULL) {
                            // There was some transaction Where token was not listed by user, inform admin about this tx and check another tx
                            //    $this->InformAdmin('waves:' . $tx['id']);
                            clog('waves_ipn', $tx['id']);
                            continue;
                        }

                        $precision = pow(10, $dj_decimal);
                        $to_num = round((($tx['amount'] * $precision) / $precision) / $precision, 8);

                        if (M('Myzr')->where(array('txid' => $tx['id'], 'status' => '1', 'type' => 'waves'))->find()) {
                            echo 'txid had found continue' . "<br/>";
                            continue;
                        }

                        echo 'all check ok ' . "<br/>";

                        echo '<br/>start receive do:' . "<br/>";
                        $sfee = 0;
                        $true_amount = $to_num;

                        if (C('coin')[$coin]['zr_zs']) {
                            $song = round(($to_num / 100) * C('coin')[$coin]['zr_zs'], 8);

                            if ($song) {
                                $sfee = $song;
                                $true_amount = $true_amount + $song;
                            }
                        }

                        // ($trans['confirmations'] < C('coin')[$coin]['zr_dz'])
                        {
                            echo 'All transactions are confirmed' . "<br/>";
                        }

                        $mo = M();

                        $mo->startTrans();
                        $rs = array();
                        $user['userid'] = userid();
                        $rs[] = $mo->table('codono_user_coin')->where(array('userid' => $user['userid']))->setInc($coin, $true_amount);

                        if ($res = $mo->table('codono_myzr')->where(array('txid' => $tx['id']))->find()) {
                            echo '<br/>Deposit already Credited !! Check Next<br/>';
                            continue;
//                        $rs[] = $mo->table('codono_myzr')->save(array('id' => $res['id'], 'addtime' => time(), 'status' => 1));
                        } else {
                            echo 'New Deposit Add it for  ' . $tx['recipient'] . "<br/>";
                            $add_tx = array('userid' => $user['userid'], 'username' => $tx['recipient'], 'coinname' => $coin, 'fee' => $sfee, 'txid' => $tx['id'], 'num' => $true_amount, 'mum' => $true_amount, 'addtime' => time(), 'status' => 1);
                            $rs[] = $mo->table('codono_myzr')->add($add_tx);
                        }

                        if (check_arr($rs)) {
                            $mo->commit();
                            echo $true_amount . ' receive ok ' . $coin . ' ' . $true_amount;

                            echo 'commit ok' . "<br/>";
                        } else {
                            echo $true_amount . 'receive fail ' . $coin . ' ' . $true_amount;
                            $mo->rollback();

                            print_r($rs);
                            echo 'rollback ok' . "<br/>";
                        }
                    }
                }


            }
        }
    }


    private function IfExistsTX($address, $txn_id, $status)
    {
        //Check if $txn_id exists for $address with $status 100 or 2
        $findme = M('CoinpayIpn')->where(array('txn_id' => $txn_id, 'address' => $address))->find();
        if ($findme) {
            return true;
        } else {
            return 0;
        }
    }

    private function saveentry($aio_data_array)
    {
        $coinpayipn = M('CoinpayIpn');
        $query = $this->querymaker('codono_coinpay_ipn', $aio_data_array);
        return $coinpayipn->execute($query);

    }

    public function confirm()
    {
        $cp_merchant_id = COINPAY_MERCHANT_ID; //defined in pure_config
        $cp_ipn_secret = COINPAY_SECRET_PIN; //defined in pure_config
        $postdata = I('post.');
        //$hmac = hash_hmac("sha512", $postdata, trim($cp_ipn_secret));
        //These would normally be loaded from your database, the most common way is to pass the Order ID through the 'custom' POST field.


        if (!isset($postdata['ipn_mode']) || $postdata['ipn_mode'] != 'hmac') {
            $this->errorAndDie('IPN Mode is not HMAC');
        }

        if (!isset($_SERVER['HTTP_HMAC']) || empty($_SERVER['HTTP_HMAC'])) {
            $this->errorAndDie('No HMAC signature sent.');
        }

        $request = file_get_contents('php://input');
        if (empty($request)) {
            $this->errorAndDie('Error reading POST data');
        }

        if (!isset($postdata['merchant']) || $postdata['merchant'] != trim($cp_merchant_id)) {
            $this->errorAndDie('No or incorrect Merchant ID passed');
        }

        $hmac = hash_hmac("sha512", $request, trim($cp_ipn_secret));

        if (!hash_equals($hmac, $_SERVER['HTTP_HMAC'])) {
            $this->errorAndDie($hmac . 'HMAC signature does not match' . $_SERVER['HTTP_HMAC']);

        }
        // HMAC Signature verified at this point, load some variables.
        $cid = $postdata['id'] ?: NULL;
        $address = $postdata['address'];
        $txn_id = $postdata['txn_id'];
        $item_name = $postdata['item_name'];
        $item_number = $postdata['item_number'];
        $amount1 = floatval($postdata['amount1']);
        $amount2 = floatval($postdata['amount2']);
        $currency1 = $postdata['currency1'];
        $currency2 = $postdata['currency2'];
        $status = intval($postdata['status']);
        $status_text = $postdata['status_text'];
        $currency = $postdata['currency'];
        if ($postdata['currency'] == 'USDT.ERC20') {
            $currency = "USDT";
        }
        $aio_data_array = $postdata;
        $aio_data_array['cid'] = $cid;
        $aio_data_array['currency'] = $currency;
        unset($aio_data_array['id']); //saving id as coinpay id cid
        if ($this->IfExistsTX($address, $txn_id, $status) == 1) {
            $this->errorAndDie('Order Already exists!');
            $name = getcwd() . '/Public/Log/exists_coinpay_post.txt';
            $json_string = 'Order Already exists!';
			clog('exists_coinpay_post',$json_string);
        }
        //depending on the API of your system, you may want to check and see if the transaction ID $txn_id has already been handled before at this point


        if ($status >= 100 || $status == 2) {
            // payment is complete or queued for nightly payout, success
            $save_entry = $this->saveentry($aio_data_array);

        } else if ($status < 0) {
            //payment error, this is usually final but payments will sometimes be reopened if there was no exchange rate conversion or with seller consent
            $this->errorAndDie('Failed !Currently Payment Status is ' . $status);
        } else {
            //payment is pending, you can optionally add a note to the order page
            $this->errorAndDie('Failed !Currently Payment Status is ' . $status);
        }
        exit('IPN ENDS');
    }

    private function querymaker($tablename, $insData): string
    {
        $count = 0;
        $fields = '';

        foreach ($insData as $col => $val) {
            if ($count++ != 0) $fields .= ', ';
            //$col = mysqli_real_escape_string($col);
            //$val = mysqli_real_escape_string($val);
            $fields .= "`$col` = '$val'";
        }

        return 'INSERT INTO ' . $tablename . ' SET ' . $fields;
    }

    public function YoUgandaFailedIPN()
    {

        $yoUganda = YoUganda();
        $response = $yoUganda->receive_payment_failure_notification();
        if ($response['is_verified']) {
            // Notification is from Yo! Uganda Limited
            // Update your transaction status in the db where the external_ref = $response['failed_transaction_reference']
            $mo = M();
            return $mo->table('codono_mycz')->where(array('tradeno' => $response['failed_transaction_reference'], 'type' => 'youganda'))->save(array('status' => 5, 'endtime' => time()));
        }
        return false;
    }

    public function YoUgandaSuccessIPN()
    {
        /*     $name = getcwd() . '/Public/Log/youganda_success.txt';
        $json_string =json_encode($postdata);
        file_put_contents($name, $json_string, FILE_APPEND);
        $yoUganda=YoUganda();
        $response = $yoUganda->receive_payment_notification();
        $resp='{"amount":"501.00","date_time":"2020-12-08 13:00:32","external_ref":"ZG473569","msisdn":"256787968947","narrative":"amber recharge for 501.00","network_ref":"124758565","signature":"hJjNnQIjSj1bemm\/06nvjCKkr2rZeJ+Tbdk1GYh40QA6fDWseyoFk64TjxBQb4saJaZDu5885FBmsngWUSan5\/Tnq5NSRzbz5C1Lk\/GXn0rgqxv7Jkrwts\/wRe1tdgw7808EV39ew4ioNDGGfhlL\/56F4qkB4enEVVhGbnlS1INDdOJDaVhIhOLj6vEtpHBMdUDKuEA08F0hVC7xfz1yhyStPkuzoGI8acfuc548DbRF8emowCgdo+CttHGLrP9xgSF9\/ShyR3LMHZpRujb15l+05e0peR6MfuqE7TjisT8qzs8gHKNGd53Iy9N\/WmDMT7NMlOha5XpyEW93BgrJyw=="}';
        $resp=json_decode($resp,true);
        //$resp1=json_decode($resp,true);
        */
        $postdata = I('post.');
        $ref_no = $postdata['external_ref'];
        if (!check($ref_no, 'idcard')) {
            $array = array('status' => 0, 'message' => 'Invalid Ref');
            exit(json_encode($array));
        }
        $yoUganda = YoUganda();

        $mycz = M('Mycz')->where(array('tradeno' => $ref_no, 'type' => 'youganda'))->find();
        $response = $yoUganda->ac_transaction_check_status($mycz['remark']);
        if ($response['Status'] == 'OK' && $response['TransactionStatus'] == 'SUCCEEDED') {
            // Notification is from Yo! Uganda Limited
            // Update your transaction status in the db where the external_ref = $response['failed_transaction_reference']
            $mo = M();

            if ($mycz['status'] == 1 || $mycz['status'] == 2) {
                $array = array('status' => 0, 'message' => 'Already credited');
                exit(json_encode($array));
            }


            $coin = strtolower($mycz['coin']);
            $coind = $coin . 'd';

            if (($mycz['status'] != 0) && ($mycz['status'] != 3) && ($mycz['status'] != 4)) {
                $array = array('status' => 0, 'message' => 'Status of this payment does not allow it to be processed!');
                exit(json_encode($array));
            }


            $fee_amt = 0;

            $myczType = M('MyczType')->where(array('name' => $mycz['type']))->find();

            $receivable_after_fees = $mycz['num'];

            if ($myczType['extra'] && $myczType['extra'] > 0) {
                $fees_percent = $myczType['extra'] ?: 0;
                $fee_amt = bcmul($receivable_after_fees, $fees_percent, 8);
                $just_per = bcdiv(bcsub(100, $fees_percent, 8), 100, 8);
                $receivable_after_fees = bcmul($receivable_after_fees, $just_per, 2);
            }


            $mo = M();

            $mo->startTrans();
            $rs = array();


            $finance = $mo->table('codono_finance')->where(array('userid' => $mycz['userid']))->order('id desc')->find();
            $finance_num_user_coin = $mo->table('codono_user_coin')->where(array('userid' => $mycz['userid']))->find();
            $rs[] = $mo->table('codono_user_coin')->where(array('userid' => $mycz['userid']))->setInc($coin, $receivable_after_fees);
            $rs[] = $mo->table('codono_mycz')->where(array('id' => $mycz['id']))->save(array('status' => 1, 'mum' => $receivable_after_fees, 'endtime' => time()));
            $finance_mum_user_coin = $mo->table('codono_user_coin')->where(array('userid' => $mycz['userid']))->find();
            $finance_hash = md5($mycz['userid'] . time());
            $finance_num = $finance_num_user_coin[$coin] + $finance_num_user_coin[$coind];

            if ($finance['mum'] < $finance_num) {
                $finance_status = (1 < ($finance_num - $finance['mum']) ? 0 : 1);
            } else {
                $finance_status = (1 < ($finance['mum'] - $finance_num) ? 0 : 1);
            }

            $rs[] = $mo->table('codono_finance')->add(array('userid' => $mycz['userid'], 'coinname' => $coin, 'num_a' => $finance_num_user_coin[$coin], 'num_b' => $finance_num_user_coin[$coind], 'num' => $finance_num_user_coin[$coin] + $finance_num_user_coin[$coind], 'fee' => $fee_amt, 'type' => 1, 'name' => 'mycz', 'nameid' => $mycz['id'], 'remark' => 'Fiat Recharge-Admin Approval', 'mum_a' => $finance_mum_user_coin[$coin], 'mum_b' => $finance_mum_user_coin[$coind], 'mum' => $finance_mum_user_coin[$coin] + $finance_mum_user_coin[$coind], 'move' => $finance_hash, 'addtime' => time(), 'status' => $finance_status));

            $cz_mes = "Success recharge[" . $receivable_after_fees . "].";

            $cur_user_info = $mo->table('codono_user')->where(array('id' => $mycz['userid']))->find();


            if (check_arr($rs)) {
                $deposit_time = date('Y-m-d H:i', time()) . '(' . date_default_timezone_get() . ')';
                $deposited_amount = $receivable_after_fees;
                $user = M('User')->where(array('id' => $mycz['userid']))->find();
                $to_email = $user['email'];
                $subject = "Deposit Success Alerts " . $deposit_time;
                $order_ref = $mycz['tradeno'];
                $upcoin = strtoupper($coin);
                $content = "<br/><strong>Congratulations your account has been just deposited with $upcoin $deposited_amount!!</strong><br/><br/><br/>Deposit details below ,<br/>
			<table>
			<tr style='border:2px solid black'><td>Email</td><td>$to_email</td></tr>
			<tr style='border:2px solid black'><td>Currency</td><td>$upcoin</td></tr>
			<tr style='border:2px solid black'><td>Amount</td><td>$deposited_amount</td></tr>
			<tr style='border:2px solid black'><td>Order reference</td><td>$order_ref</td></tr>
			<tr style='border:2px solid black'><td>Time</td><td>$deposit_time</td></tr>	
			</table>
			<br/><br/>
			
			<strong>You can start using your balance with immediate effect, Happy Trading!</strong>";
                addnotification($to_email, $subject, $content);
                $mo->commit();


                $array = array('status' => 1, 'message' => 'Success!');
            } else {
                $mo->rollback();
                $array = array('status' => 1, 'message' => 'Failed!');
            }

            exit(json_encode($array));


        }
        $array = array('status' => 1, 'message' => 'Transaction Not verified!');
        exit(json_encode($array));

    }

    private function IPNResponse($array)
    {
        header('Content-type: application/json');
        exit(json_encode($array));
    }

    public function callback_cryptoapis()
    {
        $data = file_get_contents('php://input');
        $header = getallheaders();
        dblog(__CLASS__ . '/' . __FUNCTION__ . '/' . __LINE__, [$data, $header]);
          $data='{"apiVersion":"2.0","referenceId":"628778a7b94c734786213f1f","idempotencyKey":"6ad84884bd7adc7f95fbf37a063e16c335f1743514cd1247cb1e59e750195fb1","data":{"product":"COINS_FORWARDING","event":"COINS_FORWARDING_SUCCESS","item":{"blockchain":"ethereum","network":"ropsten","fromAddress":"0x0501dcaa22ec95bde601d725b184d7abd8655c4d","toAddress":"0x91837eebadb15d4ee4dbe3ea72ba016567621611","forwardedAmount":"0.009894610379112","forwardedUnit":"ETH","spentFeesAmount":"0.000105389620888","spentFeesUnit":"ETH","triggerTransactionId":"0x3392d04c98a9a17669fc1484773207f38325c06a07ebff281c2ee64aa37b04ea","forwardingTransactionId":"0x7eb41f60d8fdd3b825a601f6b19a28c7d151de061b78ff0a26bc11bec2debbdb"}}}';
          $header='{"X-Signature":"ae6e9d7e720aca769ca97af46ea8101107d433b6e331d9c92422c3d657793b92","Content-Type":"application/json","Content-Length":"694","Connection":"close","X-Forwarded-Proto":"https","X-Real-Ip":"3.127.104.54","Host":"rapid.codono.com"}';
        $data = '{"apiVersion":"2.0","referenceId":"0842fde0-a654-4cb2-a2c8-49ae075ce281","idempotencyKey":"1afcec3368e56141276a804e48fca8053d2c750ba24fe6a4c516cb47538e1a32","data":{"product":"BLOCKCHAIN_EVENTS","event":"ADDRESS_COINS_TRANSACTION_CONFIRMED","item":{"blockchain":"ethereum","network":"ropsten","address":"0x7070cbc06bf3c0ac07e022fa74990f086109f8a0","minedInBlock":{"height":12286930,"hash":"0x22c4b625795c7679c7a31b4a91dbd64bb7a94f3300a7ca1dd8ccb3a00dd584dc","timestamp":1653193444},"transactionId":"0x05fad5ff279c196e850c7a018d4d7fae9b4abcabf23adb2b07ca4c91cddfdc28","amount":"0.0254","unit":"ETH","direction":"incoming"}}}';
        $header = '{"X-Signature":"ae2d9b5c91b43cca05e0cd4652027994239103360a662be270e770639a50346a","Content-Type":"application/json","Content-Length":"623","Connection":"close","X-Forwarded-Proto":"https","X-Real-Ip":"18.159.95.16","Host":"rapid.codono.com"}';
        $cryptoApis = CryptoApis([]);
        $out = $cryptoApis->decodeIncomingTx($data, $header);
        if ($out) {
            $reverse = $cryptoApis->reverseBlockchain($out['blockchain']);

            if (!is_array($reverse)) {
                $this->IPNResponse(['status' => 0, 'msg' => 'Invalid Coin or Network ']);
            }
            $input_coin = $reverse[0];
            if ($input_coin[1] == 'mainnet') {
                $network = '1';
            } else {
                $network = '0';
            }
            $system_coin_protected = C('coin')[$input_coin];
            if (!is_array($system_coin_protected)) {
                $this->IPNResponse(['status' => 0, 'msg' => 'Invalid Coin :' . $input_coin]);
            }

            if ($system_coin_protected['network'] != $network) {
                $this->IPNResponse(['status' => 0, 'msg' => 'Invalid Deposit Network:' . $network]);
            }

            $entry = $this->makeDeposit($out['address'], $input_coin, $out['txid'], $out['amount']);
            $this->IPNResponse($entry);
        } else {

            $this->IPNResponse(['status' => 0, 'msg' => 'it wasnt a valid deposit']);
        }
    }

    private function makeDeposit($address, $coin, $txid, $amount)
    {

        $coinb = $coin . 'b';
        $already_processed = M('Myzr')->where(array('txid' => $txid))->find();
        if ($already_processed) {
            return ['status' => 0, 'msg' => $txid . ' hash has already been added'];
        }
        $uinfo = M('UserCoin')->where(array($coinb => $address))->field('userid')->find();
        $userid = $uinfo['userid'];
        if (!is_array($uinfo)) { //no user found skip the transaction
            return ['status' => 0, 'msg' => 'no user found skip the transaction'];
        }
        $coin = A('Finance')->findSymbol($coin);
        $mo = M();
        $mo->startTrans();
        $add_tx = ['userid' => $userid, 'type' => 'cryptoapis', 'username' => $address, 'coinname' => $coin, 'txid' => $txid, 'num' => $amount, 'mum' => $amount, 'addtime' => time(), 'status' => true];

        $rs[] = $mo->table('codono_myzr')->add($add_tx);

        $rs[] = $mo->table('codono_user_coin')->where(array('userid' => $userid))->setInc($coin, $amount);
        if (check_arr($rs)) {
            $mo->commit();
            return ['status' => 1, 'msg' => 'Transaction Added Successfully'];
        } else {
            $mo->rollback();
            return ['status' => 0, 'msg' => 'Could not add db entry'];
        }

    }


}
