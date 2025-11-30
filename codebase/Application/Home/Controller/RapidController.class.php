<?php

namespace Home\Controller;


class RapidController extends HomeController
{

    private $dexConfig;
    const allowed_method = "walletconnect";//  walletconnect or custom

    public function _initialize()
    {
        if (1 == 0) {
            $this->assign('type', 'Oops');
            $this->assign('error', 'Oops, Currently Rapid is disabled!');
            //$this->display('Content/error');
            $this->error('Rapid is currently disabled');
            exit();
        }

        parent::_initialize();


    }

    public function __construct()
    {
        $this->dexConfig = $this->dexConfig();
        parent::__construct();
    }

    private function returnEsmartCoins()
    {

        $esmart_coins = (APP_DEBUG ? null : S('esmart_coins'));
        if (!$esmart_coins) {
            $coins = C('coin');
            foreach ($coins as $coin) {
                if ($coin['type'] == 'esmart' && $coin['zr_jz'] == 1 &&  $coin['status'] == 1) {
                    $esmart_coins[] = array('name' => $coin['name'], 'main' => $coin['codono_coinaddress'], 'contract' => $coin['dj_yh'], 'decimal' => $coin['cs_qk'], 'tokenof' => $coin['tokenof'], 'symbol' => $coin['symbol'], 'img' => $coin['img']?:'default.png','explorer'=>$coin['js_wk']);
                }

            }
            S('esmart_coins', $esmart_coins);
        }
        //var_dump($esmart_coins);
        
        return $esmart_coins;
    }


    public function index()
    {


        $default_buy_coin = "";
        $token_info = array('name' => $this->dexConfig['token_name'], 'symbol' => $this->dexConfig['token_symbol'], 'icon' => 'Upload/Coin/ZTU.png', 'min' => $this->dexConfig['token_min'], 'max' => $this->dexConfig['token_max']);
        $buy_in_coins = $this->returnEsmartCoins();
        foreach ($buy_in_coins as $coin) {
            if ($coin['dj_yh'] == '') {
                $default_buy_coin = $coin;
            }
        }

        $this->assign('base_coins', $buy_in_coins);
        $this->assign('default_coin_base', $default_buy_coin);
        $this->assign('trade_coins', $buy_in_coins);
        $this->assign('default_coin_trade', $token_info);
        $this->display();
    }

    //amount of tokens needed to be paid
    public function getPrice($deposit_coin, $deposit_amount = 0)
    {
	

        $uid = userid();

        if (!$uid) {
            $this->error(L('PLEASE_LOGIN'));
        }
        if (!check($deposit_amount, 'double')) {
            $this->error("Incorrect Quantity provided !" . $deposit_amount);
        }

        $quote_id = $this->genQid($uid);
        $saveme = array('qid' => $quote_id, 'userid' => $uid, 'coin' => strtolower($deposit_coin),  'amount' => $deposit_amount, 'addtime' => time(),'status'=>0);
		
        $mo = M();
        $if_saved = $mo->table('codono_rapid_deposit')->add($saveme);
		
        if (isset($if_saved)) {
            $exit_data['status'] = 1;
            $exit_data['msg'] = 'You are being redirected';
            $exit_data['url'] = U('Rapid/order', array('qid' => $quote_id));
            echo json_encode($exit_data);
        } else {
            $this->error('We are currently facing issues with processing your order');
        }
    }

    private function genQid($uid): string
    {
        return $uid . '_' . md5($uid . time() . rand(100000, 999999));
    }

    private function getPriceOfCoin($spend_coin, $amount, $type): array
    {
        $coin_info = array();
        $coins = $this->dexCoins();
        foreach ($coins as $coin) {
            if (strtolower($coin['symbol']) == strtolower($spend_coin)) {
                $coin_info = $coin;
            }
        }

        $price = format_num($coin_info['price']);

        //$price = format_num(0.000001);
        $amount = format_num($amount);
        //amount is token amount
        if ($type == 1) {
            $quantity = $amount;
            $total = bcmul($price, $quantity, 8);
        } else {
            //type=2 means amount is total amount of bnb that user want to spend and buy token so we calculate token using iterator_apply

            $quantity = bcdiv($amount, $price, 8) * 1;
            $total = $amount;
        }


        return array('spend_coin' => $spend_coin, 'quantity' => $quantity, 'price' => $price, 'total' => $total);
    }

    public function cronCoinDeposits()
    {
        G('begin');
        $dexConfig = $this->dexConfig;

        $fromblock = $dexConfig['lastblock_coin'] ? $dexConfig['lastblock_coin'] + 1 : 0;
        $web3 = Web3Connect($this->dexConfig['coin'], $this->dexConfig['network']);
        $address = $dexConfig['receiver'];

        $toblock = 'latest';
        $coin_type = "coin"; //coin/token
        $type = 1;//1=deposit, 2 withdrawal
        echo "Starting to read from block  $fromblock to $toblock<br/>";
        $resp = $web3->findDeposits($address, $fromblock, $toblock, $type, $coin_type);

        $all_txs = array();
        $mo = M();
        $last_block_read = 0;
        if ($resp['count'] > 0) {
            foreach ($resp['txs'] as $txs) {

                $txs['value'] = bcdiv($txs['value'], pow(10, 18), 8);
                $all_txs[] = $txs;
                $last_block_read = $txs['blockNumber'];
                $txs['coinname'] = $dexConfig['coin'];
                $Transactions = M('DexTransactions');
                $if_found = $Transactions->getByHash($txs['hash']);

                if ($if_found) {
                    continue;
                }


                $mo->table('codono_dex_transactions')->add($txs);
            }

            if ($last_block_read > 0) {
                echo "last_block_read= $last_block_read";
                M('DexConfig')->where(array('id' => 1))->save(array('lastblock_coin' => $last_block_read));

            }

        }
        G('end');
        echo "<br/>Total Time taken " . G('begin', 'end') . 's';
    }

    public function cronTokenDeposits()
    {
        G('begin');
        $updated = 0;
        $dexConfig = $this->dexConfig();
        $fromblock = $dexConfig['lastblock_token'] ? $dexConfig['lastblock_token'] + 1 : 0;

        $web3 = Web3Connect($this->dexConfig['coin'], $this->dexConfig['network']);
        $address = $dexConfig['receiver'];

        $toblock = 'latest';
        $coin_type = "token"; //coin/token
        $type = 1;//1=deposit, 2 withdrawal
        $resp = $web3->findDeposits($address, $fromblock, $toblock, $type, $coin_type);
        $mo = M();
        $last_block_read = 0;
        if ($resp['count'] > 0) {
            foreach ($resp['txs'] as $txs) {
                $last_block_read = $txs['blockNumber'];
                $Transactions = M('DexTransactions');
                $if_found = $Transactions->getByHash($txs['hash']);

                if ($if_found) {
                    continue;
                }
                $mo->table('codono_dex_transactions')->add($txs);
            }
            if ($last_block_read > 0) {
                $updated = M('DexConfig')->where(array('id' => 1))->save(array('lastblock_token' => $last_block_read));
            }

        }
        var_dump("LastBlock Updated" . $updated);
        var_dump($resp);
        G('end');
        echo "<br/>Total Time taken " . G('begin', 'end') . 's';
    }

    /**
     *Cron to check unknown deposits and pay them back
     */
    public function abandonedDeposits()
    {
        $txs = M('DexTransactions')->where(array('isdone' => 0, 'isError' => 0))->select();
        echo "Found " . count($txs) . " abandoned transactions, Processing them<br/>";
        foreach ($txs as $tx) {
            echo "abandonedDeposits starts<br/>";
            $record = M('DexDeposit');
            $if_found = $record->where(array('in_hash' => $tx['hash']))->find();

            if (isset($if_found)) {
                //so this is already processed mark it as isdone so it doesnt pop up next time
                M('DexTransactions')->where(array('hash' => $tx['hash']))->save(array('isdone' => 1));
            } else {
                echo $tx['hash'] . " is not found to be deposit entry<br/>";
                //@todo do the entry
                //validate the tx if its coin or token if valid then do the entry or  if not then mark as iserror
                //should be token see if its valid token
                if ($tx['coinname'] == '') {
                    $tx_coin = $this->coinInfo($tx['tokensymbol']);
                    if ($tx_coin == null || $tx_coin == '') {
                        $this->markTxError($tx['id']);
                    }
                }
                if ($tx['tokensymbol'] == '' || $tx['coin'] != null) {
                    echo "@todo do the deposit entry in dex_deposit";
                    $qid = 0;
                    $this->processAbandonedDeposits($qid, $tx);
                }
            }
            echo "<br/>Checking Next record" . __LINE__ . "<br/>";
        }

    }

    private function markTxError($id)
    {
        M('DexTransactions')->where(array('id' => $id))->save(array('isError' => 1));
    }

    public function order($qid = 0)
    {
        if (!isset($qid) || $qid == 0) {
            redirect(U('Rapid/index'));
        }
        $single_record = $this->findQID($qid);
        if (!isset($single_record) || $qid == 0 || empty($single_record)) {
            redirect(U('Rapid/index'));
        }
        $this->assign('record', $single_record);
        $this->assign('method', self::allowed_method);
        redirect(U('Rapid/walletconnect/qid/' . $qid));
    }

    /**
     * @param $qid
     * Shows pay that connects to wallets ie metamask and trustwallet or other web3
     */
    public function walletconnect($qid)
    {

        if (!isset($qid) || $qid == null) {
            redirect(U('Rapid/Index'));
        }
        $single_record = $this->findQID($qid);

        if (!isset($single_record) || $single_record == null) {
            redirect(U('Rapid/Index'));
        }

        if ($single_record['received'] == 1) {
            redirect(U('Rapid/completed/qid/' . $qid));
        }
        //$amount_of_tokens = $single_record['qty'];
        $spend_coin = $single_record['spend_coin'];
        $total = $single_record['total'];
        $spend_coin_info = $this->coinInfo($spend_coin);

        if ($spend_coin_info['is_token'] == 1) {
            $is_token = 1;
        } else {
            $is_token = 0;
        }
        $receiver_address = $this->dexConfig['receiver'];

        $web3 = Web3Connect($this->dexConfig['coin'], $this->dexConfig['network']);

        $script = $web3->do_sendtransaction($receiver_address, $total, $qid, $is_token, $spend_coin_info['contract_address'], $spend_coin_info['decimals'], self::allowed_method);
        $this->assign('record', $single_record);
        $this->assign('script', $script);
        $this->display();
    }

    /**
     * @param $qid
     * Shows pay that connects to wallets ie metamask and trustwallet or other web3
     */
    public function coreconnect($qid)
    {

        if (!isset($qid) || $qid == null) {
            redirect(U('Rapid/Index'));
        }
        $single_record = $this->findQID($qid);

        if (!isset($single_record) || $single_record == null) {
            redirect(U('Rapid/Index'));
        }

        if ($single_record['received'] == 1) {
            redirect(U('Rapid/completed/qid/' . $qid));
        }
        //$amount_of_tokens = $single_record['qty'];
        $spend_coin = $single_record['spend_coin'];
        $total = $single_record['total'];
        $spend_coin_info = $this->coinInfo($spend_coin);

        if ($spend_coin_info['is_token'] == 1) {
            $is_token = 1;
        } else {
            $is_token = 0;
        }
        $receiver_address = $this->dexConfig['receiver'];

        $web3 = Web3Connect($this->dexConfig['coin'], $this->dexConfig['network']);

        $script = $web3->do_sendtransaction($receiver_address, $total, $qid, $is_token, $spend_coin_info['contract_address'], $spend_coin_info['decimals'], self::allowed_method);
        $this->assign('page_title', "Dex Wallet Connect");
        $this->assign('record', $single_record);
        $this->assign('script', $script);
        $this->display();
    }

    private function calcQuantity($pay_in_coin, $paid_amount)
    {
        //for coin $pay_in_coin price is say 0.001
        $info = $this->coinInfo($pay_in_coin);
        if (!is_array($info)) {
            return false;
        }
        $price = $info['price'];
        return bcdiv($paid_amount, $price, 8);

    }

    private function dexConfig()
    {
        $DexConfig = M('DexConfig');
        return $DexConfig->find();
    }

    private function coinInfo($symbol)
    {
        $coin_info = array();
        $coins = $this->dexCoins();
        foreach ($coins as $coin) {
            if (strtolower($coin['symbol']) == strtolower($symbol)) {
                $coin_info = $coin;
            }
        }
        return $coin_info;
    }

    private function dexCoins()
    {
        $DexCoins = M('DexCoins');
        return $DexCoins->select();
    }

    private function findQID($qid)
    {
        if (!isset($qid)) {
            return false;
        }
        $where['qid'] = $qid;
        $DexOrder = M('RapidDeposit');
        return $DexOrder->where($where)->find();
    }

    private function findDepositByHash($hash)
    {
        if (!isset($hash)) {
            return false;
        }
        $where['in_hash'] = $hash;
        $DexDeposit = M('DexDeposit');
        return $DexDeposit->where($where)->find();
    }

    /**
     * @param $qid
     * @param $transaction
     * @return void
     * process the abandoned deposits insert them in dex_deposits table then send respective token to user
     */
    private function processAbandonedDeposits($qid, $transaction): void
    {
        $qid = $qid ?: 0;
        $uid = 0;
        $result = (object)$transaction;

        //this is token transaction
        if ($result->coinname == null && $result->tokenSymbol != null) {
            $token_resp = $this->processTokenDeposit($result);
            if (is_array($token_resp)) {
                $from = $token_resp['from'];
                $to = $token_resp['to'];
                $deposited_amount = $token_resp['amount'];
                $hash = $result->hash;
                $receive_coin = $token_resp['token'];
            } else {
                return;
            }

        } else {

            //this is main coin transaction
            $from = $result->from;
            $to = $result->to;
            $deposited_amount = $result->value;
            $hash = $result->hash;
            $receive_coin = $this->dexConfig['coin'];

        }

        if (strtolower($to) != strtolower($this->dexConfig['receiver'])) {

            return;

        }


        $entry['userid'] = $uid;
        $entry['qid'] = 0;
        $entry['in_hash'] = $hash;
        $entry['coin'] = $receive_coin;
        $entry['amount'] = $deposited_amount;
        $entry['in_address'] = $from;
        $entry['in_time'] = time();

        if ($deposited_amount > 0) {
            //enter the deposit entry
            $deposit_entry = $this->depositEntry($entry);
            //If entry is new Send Token
            if (isset($deposit_entry)) {
                M('DexDeposit')->where(array('id' => $deposit_entry))->save(array('payout_status' => 1));
                $payout_qty = $this->calcQuantity($entry['coin'], $deposited_amount);
                $payout_hash = $this->sendToken($entry['in_address'], $payout_qty);
                if (stripos($payout_hash, '0x') === 0) {
                    $payment_update_array = array('payout_hash' => $payout_hash, 'payout_qty' => $payout_qty, 'payout_time' => time());
                    M('DexDeposit')->where(array('id' => $deposit_entry))->save($payment_update_array);
                }
            }

        }
    }

    private function processHash($qid, $hash): array
    {
        $uid = 0;
        if ($qid != 0) {
            $qid_info = $this->findQID($qid);
            if (isset($qid_info) && $qid_info['user'] > 0) {
                $uid = $qid_info['user'];
            } else {
                $uid = userid();
            }
        }

        $web3 = Web3Connect($this->dexConfig['coin'], $this->dexConfig['network']);

        $info = $web3->getTransactionByHash($hash);

        $configured_chain = $web3->findProvider();

        $result = $info->result;

        if ($configured_chain['chainid'] != $result->chainId) {
            //return array("status" => 0, "msg" => "Incorrect chainid selected from Wallet", "hash" => "");
        }
        if (strtolower($hash) != strtolower($result->hash)) {
            return array("status" => 0, "msg" => "Incorrect transaction hash", "hash" => $hash);
        }


        //this is token transaction
        if ($result->value === '0x0' && $result->input != '0x') {

            $token_resp = $this->processTokenDeposit($result);

            if (is_array($token_resp)) {
                $from = $token_resp['from'];
                $to = $token_resp['to'];
                $deposited_amount = $token_resp['amount'];
                $hash = $result->hash;
                $receive_coin = $token_resp['token'];
            } else {
                return array("status" => 0, "msg" => "Please check txid for correct information:E35", "hash" => "");

            }

        } else {

            //this is main coin transaction
            $from = $result->from;
            $to = $result->to;

            $deposited_amount = $web3->eth_Hex2Dec($result->value);
            $hash = $result->hash;
            $receive_coin = $this->dexConfig['coin'];
        }


        if (strtolower($to) != strtolower($this->dexConfig['receiver'])) {

            return array("status" => 0, "msg" => "Please check txid for correct information:E40", "hash" => "");

        }
        if ($receive_coin == null) {
            redirect(U('Rapid/index?error=invalid_hash2'));
        }

        $entry['userid'] = $uid ?: 0;
        $entry['qid'] = $qid ?: 0;
        $entry['in_hash'] = $hash;
        $entry['coin'] = $receive_coin;
        $entry['amount'] = $deposited_amount;
        $entry['in_address'] = $from;
        $entry['in_time'] = time();


        $payout_qty = $this->calcQuantity($entry['coin'], $deposited_amount);

        if ($deposited_amount > 0) {
            //enter the deposit entry
            $deposit_entry = $this->depositEntry($entry);

            //If entry is new Send Token
            if (isset($deposit_entry)) {
                M('DexDeposit')->where(array('id' => $deposit_entry))->save(array('payout_status' => 1));
                $payout_hash = $this->sendToken($entry['in_address'], $payout_qty);

                if (stripos($payout_hash, '0x') === 0) {

                    $payment_update_array = array('payout_hash' => $payout_hash, 'payout_qty' => $payout_qty, 'payout_time' => time());
                    M('DexDeposit')->where(array('id' => $deposit_entry))->save($payment_update_array);
                    M('DexOrder')->where(array('qid' => $qid))->save($payment_update_array);
                }
            }

        }
        return array("status" => 1, "msg" => "Please check txid for correct information:E40", "hash" => $hash);
    }

    public function successPage()
    {
        $qid = I('request.qid', 0, 'string');
        $hash = I('request.txhash', null, 'string');

        $txhash = $this->processHash($qid, $hash);
        if ($txhash['status'] == 1) {
            redirect(U('Rapid/paid/hash/' . $txhash['hash']));
        }
        redirect(U('Rapid/errorPage/msg' . $txhash['msg']));
    }

    private function processTokenDeposit($result)
    {
        $from = "";
        $to = "";
        $if_token_found = false;
        $token_info = array();
        //Finding Function
        $amount = "0x0";
        $function = '0x' . substr($result->input, 2, 8);
        $token_address_found = $result->to;

        $dexCoins = $this->dexCoins();

        foreach ($dexCoins as $dexCoin) {
            if (strtolower($dexCoin['contract_address']) == strtolower($token_address_found)) {
                $token_info = $dexCoin;
            }
        }
        $decimals = $token_info['decimals'];
        if ($function == "0xa9059cbb") {
            $from = $result->from;
            $to = '0x' . substr(substr($result->input, 10, 64), -40);
            $amount = hexdec(substr($result->input, 74, 64)) / pow(10, $decimals);
            $if_token_found = true;
        } else if ($function == "0x23b872dd") {
            $from = '0x' . substr(substr($result->input, 10, 64), -40);
            $to = '0x' . substr(substr($result->input, 74, 64), -40);
            $amount = hexdec(substr($result->input, 138, 64));
            $if_token_found = true;
        }
        if ($if_token_found) {
            return array('from' => $from, 'to' => $to, 'amount' => $amount, 'token' => $token_info['symbol']);
        } else {
            return false;
        }
    }

    private function depositEntry($entry)
    {

        //@todo check if qid exists ?

        //checking if hash exists if not then add entry

        $hash_found = $this->findDepositByHash($entry['in_hash']);

        if (!isset($hash_found)) {
            $deposit_Entry = array('userid' => $entry['userid'], 'qid' => $entry['qid'], 'in_hash' => $entry['in_hash'], 'in_address' => $entry['in_address'], 'coin' => $entry['coin'], 'amount' => $entry['amount'], 'in_time' => $entry['in_time']);

            $mo = M();
            $if_saved = $mo->table('codono_dex_deposit')->add($deposit_Entry);
            if ($entry['qid'] != 0 || $entry['qid'] != null) {
                $mo->table('codono_dex_order')->where(array('qid' => $entry['qid']))->save(array('received' => 1, 'connected_address' => $entry['in_address']));
            }

            return $if_saved ?? 0;
        } else {
            return 0;
        }
    }

    private function sendToken($to_address, $amount)
    {
        $web3 = Web3Connect($this->dexConfig['coin'], $this->dexConfig['network']);
        $from = $this->dexConfig['receiver'];
        $token_address = $this->dexConfig['token_address'];
        $decimals = $this->dexConfig['token_decimals'];
        $privatekey = cryptString($this->dexConfig['receiver_priv'], 'd');

        return $web3->sendToken($from, $privatekey, $to_address, $amount, $token_address, $decimals);
    }

    public function completed($qid)
    {

        if (!isset($qid) || $qid == 0) {
            redirect(U('Rapid/index'));
        }
        $single_record = $this->findQID($qid);
        $this->assign('record', $single_record);
        $this->display();
    }

    private function txLink($tx = null): string
    {
        $web3 = Web3Connect($this->dexConfig['coin'], $this->dexConfig['network']);
        return $web3->txLink($tx);
    }

    public function paid($hash = 0)
    {
        if (stripos($hash, '0x') != 0) {
            redirect(U('Rapid/index?error=invalid_hash2'));
        }
        $hash_found = $this->findDepositByHash($hash);

        if (!isset($hash_found)) {
            redirect(U('Rapid/index'));
        }
        $this->assign('txLink', $this->txLink($hash));
        $this->assign('token', $this->dexConfig['token_symbol']);
        $this->assign('record', $hash_found);
        $this->assign('scanner_link', $this->txLink());
        $this->display();
    }

    public function errorPage()
    {
        $this->display();
    }
	public function test(){
		$config=C();
		
		echo $this->printmarraytable($config);
		
	}

	 
	function printArrayAsHTML($ckey,$resp){
		
		if(!is_array($resp)){
			$html= "<tr><td>$ckey</td><td>".$resp."</td></tr>";
		}else{
			$html= "<tr><td>$ckey</td>";
			foreach($resp as $key=>$res){
				if(is_array($resp) || is_object($res)){
					return "<tr><td>$ckey</td><td>".$this->printArrayAsHTML($key,$res)."</td></tr>";
				}else{
					return "<tr><li>$ckey</td><td>".$res."</td></tr>";
				}
			}
			$html.="</tr>";
		}
		return $html;
	}

}