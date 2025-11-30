<?php

namespace Home\Controller;

use Exception;

class CoinController extends HomeController
{

    public function __construct()
    {
        parent::__construct();
        checkcronkey();
        
    }

    public function index()
    {
        echo 'Coin controller for setting up all deposits, withdrawals and Moving to main account';
    }

    public function deposit_cryptoapis()
    {
        cronLastRun(__FUNCTION__);
        $cryptoapi_config = $cryptoapi_coins_on_exchange = array();
        $cryptoapi = CryptoApis($cryptoapi_config);
        $coins = C('Coin');
        foreach ($coins as $coin) {
            if ($coin['type'] == 'cryptoapis') {
                $supportedCryptoApisChains = $cryptoapi->allowedSymbols();
                if (in_array($coin['name'], $supportedCryptoApisChains) || in_array($coin['tokenof'], $supportedCryptoApisChains)) {
                    $cryptoapi_coins_on_exchange[] = $coin['name'];
                }

            }
        }
        unset($cryptoapi);
        unset($cryptoapi_config);

        foreach ($cryptoapi_coins_on_exchange as $cas) {

            echo "Reading Txs for $cas<br/>";
            $lc = C('coin')[$cas];
            $parent = $lc['symbol'];
            $offset = $lc['block'] ?: 0; //offset where it was read last;

            $cryptoapi_config = array(
                'api_key' => cryptString($lc['dj_mm'], 'd'),
                'network' => $lc['network'],
            );
            $blockchain = $cas;
            $walletId = $lc['dj_zj'];
            $context = $blockchain . time();
            $cryptoapi = CryptoApis($cryptoapi_config);
            $records = $cryptoapi->listTransactions($blockchain, $walletId, $context, $offset);

            foreach ($records as $record) {
                $deposit = $this->validateDepositCryptoApis($record, $cas);
                if (is_array($deposit)) {
                    $this->makeDeposit($deposit, $cas);
                }
            }
            //@todo update dj_dk for $offset+50;
        }

    }

    private function validateDepositCryptoApis($record, $coin)
    {

        if ($record->direction == 'incoming' && $record->status == 'confirmed') {
            if (in_array($coin, ['eth', 'bnb', 'etc'])) {
                // for eth type
                $main_address = C('coin')[$coin]['codono_coinaddress'];
                $recipient = $record->recipients[0];
                //because we have automation to send user deposits to main account
                $probable_account_holder = $record->sender[0];
                if (strtolower($main_address) != strtolower($probable_account_holder['address'])) {
                    return false;
                }
                $coin = $this->findSymbol($coin);
                $coinb = $coin . 'b';

                $user = M('UserCoin')->where(array($coinb => $probable_account_holder['address']))->find();
                if ($user['userid'] > 0) { //record found

                    $out['address'] = $probable_account_holder->address;
                    $out['amount'] = $probable_account_holder->amount;
                    $out['txid'] = $record->transactionId;
                    $out['type'] = 'cryptoapis';
                    return $out;
                }

            } else {
                //for btc type
                foreach ($record->recipients as $recipient) {
                    $coin = $this->findSymbol($coin);
                    $coinb = $coin . 'b';

                    $user = M('UserCoin')->where(array($coinb => $recipient->address))->find();
                    if ($user['userid'] > 0) { //record found
                        $out['label'] = $recipient->label;
                        $out['address'] = $recipient->address;
                        $out['amount'] = $recipient->amount;
                        $out['txid'] = $record->transactionId;
                        $out['type'] = 'cryptoapis';
                        return $out;
                    }
                }
            }
        } else {
            return false;
        }
        return false;
    }

    private function makeDeposit($record, $coin)
    {
        $pcoin=C('coin')[$coin]['tokenof']?:$coin;
        $symbol_coin=C('coin')[$coin]['symbol']?:$coin;
        $coinb = $pcoin . 'b';
		$mo = M();
    	
        $true_amount = $record['amount'];
        $already_processed = $mo->table('codono_myzr')->where(array('txid' => $record['txid']))->find();

        if ($already_processed) {
            return false;
        }
        //@todo if label == userid
        
		$condition = array($coinb => $record['address']);
		$user = $mo->table('codono_user_coin')->where($condition)->find();
        // Get the last executed SQL query
		if($user[$coinb]!=$record['address'] || strlen($record['address']) <5){
		$lastSql = $mo->getLastSql();
		//clog($coinb,[$coin,$condition,$lastSql,$record,$user,129]);
      //   dblog($coinb, [$coin,$condition,$lastSql,$record,$user,130]);	
			return false;
		}

        if (!$user) {
            return false;
        }
		

        $mo->startTrans();
        $rs = array();
        $chain = $pcoin;

        $add_tx = array('userid' => $user['userid'], 'type' => $record['type'], 'username' => $record['address'], 'coinname' => $symbol_coin, 'chain' => $chain, 'fee' => $record['fee'], 'txid' => $record['txid'], 'num' => $true_amount, 'mum' => $true_amount, 'addtime' => time(), 'status' => 1);
        $rs[] = $mo->table('codono_myzr')->add($add_tx);
        $rs[] = $mo->table('codono_user_coin')->where(array('userid' => $user['userid']))->setInc($symbol_coin, $true_amount);
        if (check_arr($rs)) {
            deposit_notify($user['userid'], $record['address'], $symbol_coin, $record['txid'], $true_amount, time());
            $mo->commit();
            echo $true_amount . ' receive ok ' . $symbol_coin . ' ' . $true_amount;

            echo 'commit ok, Notify customer' . '<br/>';

        } else {
            echo $true_amount . 'receive fail ' . $symbol_coin . ' ' . $true_amount;
            $mo->rollback();
            echo 'rollback ok' . '<br/>';
        }
        return true;
    }

    private function findTokenByContract($contact_address): array
    {
        $resp = array();
        $coins = C('Coin');
        foreach ($coins as $coin) {
            if ($coin['dj_yh'] == $contact_address) {
                $resp['name'] = $coin['name'];
                $resp['cs_qk'] = $coin['cs_qk'];
            }
        }
        return $resp;
    }

    public function findSymbol($coin): ?string
    {
        $coin = strtolower($coin);
        $coininfo = C('coin')[$coin];
        if (!is_Array($coininfo)) {
            return null;
        }
        $symbol = strtolower($coininfo['symbol']);

        if ($symbol == null) {
            return $coin;
        } else {
            return $symbol;
        }
    }

    //Uses BlockReading Technique :substrate
    public function substrate_deposit()
    {
        cronLastRun(__FUNCTION__);
        echo 'Start<br/>';
        $coinList = C('Coin')->where(array('status' => 1, 'type' => 'substrate', 'zr_jz' => 1))->select();
        foreach ($coinList as $k => $v) {
            if ($v['type'] != 'substrate') {
                continue;
            }

            $cc = $v;
            $coin = strtolower($cc['name']);

            $config = [
                'host' => $cc['dj_zj'],
                'port' => $cc['dj_dk'],
                'api_key' => cryptString($cc['dj_mm'], 'd'),
                'decimals' => $cc['cs_qk']
            ];

            if ($cc['cs_qk'] <= 0 || null) {
                exit('Decimal for chain is invalid:' . $cc['cs_qk']);
            }
            $substrate = Substrate($config);
            $deposits = json_decode($substrate->getDeposits(), true);

            foreach ($deposits as $deposit) {
                //check if user exists

                $amount = $substrate->amount_decode($deposit['amount']);
                if ($amount <= 0) {
                    echo "$amount is quite less";
                    $mark = $substrate->markDepositAsRecorded($deposit['tx_hash']);
                    continue;
                }
                $substrate_record = ['amount' => $amount, 'address' => $deposit['to_add'], 'txid' => $deposit['tx_hash'], 'type' => 'substrate'];

                $added = $this->makeDeposit($substrate_record, $coin);
                $mark = $substrate->markDepositAsRecorded($deposit['tx_hash']);
                echo "Transaction Added: $added and marked $mark";
            }
        }
    }

    //get  substrate withdrawals using their order_id
    public function getWithdrawalIdSubstrate()
    {
        cronLastRun(__FUNCTION__);
        echo 'Start<br/>';
        $coinList = C('coin');
        foreach ($coinList as $k => $v) {
            if ($v['type'] != 'substrate') {
                continue;
            }
            if ($v['cs_qk'] <= 0 || null) {
                exit('Decimal for chain is invalid:' . $v['cs_qk']);
            }
            $config = [
                'host' => $v['dj_zj'],
                'port' => $v['dj_dk'],
                'api_key' => cryptString($v['dj_mm'], 'd'),
                'decimals' => $v['cs_qk']
            ];

            $substrate = Substrate($config);

            $transactions = M('Myzc')->where(array('network' => $v['name'], 'txid' => 'processing'))->select();
            foreach ($transactions as $transaction) {
                $info = json_decode($substrate->getWithdrawalStatus($transaction['id']), true);
                $hash = $info['hashhex'];
                if (strstr($hash, '0x')) {
                    M('Myzc')->where(array('id' => $transaction['id']))->save(array('txid' => $hash, 'hash' => $hash));
                }
            }
        }
    }

    //Uses Blockgum Deposit check from BG DB
    public function blockgum_deposit()
    {
        cronLastRun(__FUNCTION__);
        echo 'Start<br/>';
        $coinList = C('Coin');

        foreach ($coinList as $k => $v) {
            if ($v['type'] != 'blockgum' || $v['dj_yh'] !='' || $v['zr_jz']==0) {
                continue;
            }


            $cc = $v;
            $coin = strtolower($cc['name']);

            if ($cc['cs_qk'] <= 0 || null) {
                exit('Decimal for chain is invalid:' . $cc['cs_qk']);
            }

            $pcoin = $cc['tokenof'] ?: $cc['name'];

            $blockgum = blockgum($pcoin);
            $deposits = $blockgum->getLatestDeposits();
            echo '<br/>=============Chain ' .$v['name']. ' has ' .count($deposits). ' Transactions Found to be checked ============= <br/>';

            foreach ($deposits as $deposit) {
                //check if user exists

                if($deposit['is_contract']==0){
                    $amount = $blockgum->amount_decode($deposit['value']);

                    if ($amount <= 0) {
                        echo "$amount is quite less";
                        $mark = $blockgum->markAsRecorded($deposit['hash']);
                        continue;
                    }
                    $blockgum_record = ['amount' => $amount, 'address' => $deposit['to_add'], 'txid' => $deposit['hash'], 'type' => 'blockgum'];

                    $added = $this->makeDeposit($blockgum_record, $coin);
                    $mark = $blockgum->markAsRecorded($deposit['hash']);
                    echo "Transaction Added: $added and marked ".json_encode($mark);
                }else{
                    //this may be erc20 token deposit
                    //check token_add matches dj_yh contract address of any coin if yes then move forward

                    $found_contract=strtolower($deposit['token_add']);

                    if($found_contract =='' || !$found_contract  ||$found_contract==null){
                        echo 'Contract not found';
                        continue;
                    }
                    $found=0;
                    $found_coin=[];
                    foreach($coinList as $coin_info){
                        if(strtolower($coin_info['dj_yh']) == $found_contract){
                            $contract_decimal=$coin_info['cs_qk'];
                            $found_coin=$coin_info;
                            $zr_fee=$coin_info['zr_fee']?:0;
                            $found=1;
                        }
                    }

                    if($found==0 || $found_coin['zr_jz']!=1){
                        continue;
                    }
                    $pcoin=$found_coin['symbol']?:$found_coin['name'];
                    $amount = $blockgum->amount_decode($deposit['value'],$contract_decimal);
                    }    
                    if ($amount <= 0) {
                        echo "$amount is quite less";
                        $mark = $blockgum->markAsRecorded($deposit['hash']);
                        continue;
                    }
                    if($zr_fee>0 && $amount>0){
                        $zr_fee_amt = bcmul($amount, bcdiv($zr_fee,100, 4),8); // Calculate 5% fee, with 2 decimal places
                        $amount = bcsub($amount, $zr_fee_amt, 8); // Subtract the fee from the amount
                    }  
                    $blockgum_record = ['amount' => $amount, 'address' => $deposit['to_add'], 'txid' => $deposit['hash'], 'type' => 'blockgum'];

                    $added = $this->makeDeposit($blockgum_record, $found_coin['name']);

                    $mark = $blockgum->markAsRecorded($deposit['hash']);
                    echo "Transaction Added: $added and marked ".json_encode($mark);
                }

            }
        }


    //get  blockgum withdrawals using their order_id
    public function getWithdrawalIdblockgum()
    {
        cronLastRun(__FUNCTION__);
        echo 'Start<br/>';
        $coinList = C('coin');
        foreach ($coinList as $k => $v) {
            if ($v['type'] != 'blockgum') {
                continue;
            }
            if ($v['cs_qk'] <= 0 || null) {
                exit('Decimal for chain is invalid:' . $v['cs_qk']);
            }
            $pcoin = $v['tokenof'] ?: $v['name'];
            $blockgum = blockgum($pcoin);


            $transactions = M('Myzc')->where(array('network' => $v['name'], 'txid' => 'processing','status'=>array('neq', 2)))->order('id desc')->select();
            foreach ($transactions as $transaction) {
                $info = $blockgum->getWithdrawalInfo($transaction['id']);


                if ($info && isset($info['hashhex']) && strstr($info['hashhex'], '0x')) {
                    $hash = $info['hashhex'];
                    $updated= M('Myzc')->where(array('id' => $transaction['id']))->save(array('txid' => $hash, 'hash' => $hash));
                    echo 'Update status :' .$updated;
                }else{
                    dump($info);
                }
            }
        }
    }


    /*
	Run this Cron manually or with extreme caution This may cost double or more gas , and moves tokens from user accounts to your main account
		This is manual url to run to move users BEP20 tokens to main account  [Run it always after before esmart_to_main
	 https://{YOURSITE}/Home/Coin/blockgum_token_to_main/coinname/{tokenname}/securecode/{CRONKEY}
	*/
    public function blockgum_token_to_main()
    {
        cronLastRun(__FUNCTION__);
        $coinname = I('get.coinname', '','trim');
        
        $coininfo=C('coin')[$coinname];
        if ($coininfo['type'] != 'blockgum' || !$coininfo['dj_yh']) {
            if ($coininfo['dj_yh']) {
                echo 'This token should be type of blockgum';
            }
            die("<span style='color:#ff0000'>Warning:This isnt blockgum type coin :<b>$coinname</b> or there is configuration issues. Please check");
        }

        $contract_address=$coininfo['dj_yh'] ;
        echo "<b>$coinname</b> has Contract Address: $contract_address <br/>";
        //@todo
        $pcoin = $coininfo['tokenof'] ?: $coininfo['name'];
        $tokenof=$coininfo['tokenof'] ?: $coininfo['name'];
        $mainaddress=C('coin')[$tokenof]['codono_coinaddress'];
        if(!$this->isValidEthereumAddress($mainaddress)){
            echo "<font color='red'>Invalid Main Address</font> :<u> $mainaddress </u><br/>Please goto Admin panel and set Proper Main address to <b>$tokenof</b><br/>";
            exit;
        } 
        $blockgum=blockgum($pcoin);
        $parent = $this->findSymbol($coinname);
        
        $condition['status'] = 1;
        //$condition['shifted_to_main'] = 0;
        $condition['type'] = 'blockgum';
        //$condition['tokenof']=C('coin')[$coinname]['tokenof'];
        $condition['coinname'] = $parent;
        
        if ($_GET['agree'] != 'yes') {
            $agree_url = $_SERVER['REQUEST_URI'] . '/agree/yes';
            die("<span style='color:red'>Warning:<b>$coinname</b>  on this system is token of $tokenof and this tool will move all your <b>$coinname</b> funds to main account  $mainaddress,  <a href='$agree_url'> Do you agree?</a></span>");
        }
        $transactions = M('Myzr')->where($condition)->order('id asc')->limit(50)->select();
        /*
		foreach($transactions as $tx){
            $addresses[]=$tx['username'];
        }

        $unique_addresses=array_unique($addresses);
        //dump($unique_addresses);
        foreach($unique_addresses as $u_add){
            $Bal=$blockgum->getBalance($u_add,$contract_address);
            if($Bal && isset($Bal['balance']) &&  $Bal['balance']>0){
                echo "Move token ".$Bal['balance']." from $u_add for $contract_address";
            }
        }
		*/	

        echo "<br/>$coinname is token of $pcoin and has contract address $contract_address<br/>";
        $blockgum = blockgum($pcoin);
        $resp=$blockgum->moveTokensToMainAccount($contract_address);
        var_dump($resp);
        echo 'Cron Ended';
        exit;
    }
   /*
	Run this Cron manually or with extreme caution This may cost double or more gas , and moves coin from user accounts to your main account
		This is manual url to run to move users main coin to main account  [Run it always after before blockgum_token_to_main
	 https://{YOURSITE}/Coin/blockgum_coin_to_main/coinname/{coinname}/securecode/{CRONKEY}
	*/																					
	public function blockgum_coin_to_main($coinname)
    {
        cronLastRun(__FUNCTION__);
        $coininfo=C('coin')[$coinname];
        if ($coininfo['type'] != 'blockgum' || $coininfo['dj_yh']) {
            if ($coininfo['dj_yh']) {
                echo 'This is a token use separate cron for it .<br/>';
            }
            die("<span style='color:#ff0000'>Warning:This isnt blockgum type coin :<b>$coinname</b> or there is configuration issues. Please check");
        }

        if ($_GET['agree'] != 'yes') {
            $agree_url = $_SERVER['REQUEST_URI'] . '/agree/yes';
            die("<span style='color:red'>Warning:This tool will move all your <b>$coinname</b> funds to main account  ,  <a href='$agree_url'> Do you agree?</a></span>");
        }
        
        $pcoin = $coininfo['tokenof'] ?: $coininfo['name'];
        $blockgum=blockgum($pcoin);
        $parent = $this->findSymbol($coinname);
        $condition['status'] = 1;
        //$condition['shifted_to_main'] = 0;
        $condition['type'] = 'blockgum';
        //$condition['tokenof']=C('coin')[$coinname]['tokenof'];
        $condition['coinname'] = $parent;
        $transactions = M('Myzr')->where($condition)->order('id asc')->limit(50)->select();
        foreach($transactions as $tx){
            $addresses[]=$tx['username'];
        }

        $unique_addresses=array_unique($addresses);
		$coun_Addr=count($unique_addresses);
        echo "Found $coun_Addr addresses with parent $parent<br/>";
        foreach($unique_addresses as $u_add){
            $Bal=$blockgum->getBalance($u_add);
            if($Bal && isset($Bal['balance']) &&  $Bal['balance']>0.01){
                echo "Move $coinname ".$Bal['balance']." from $u_add ";
                $this->markAsMovedAddress($u_add,$coinname);
            }
        }


        echo "<br/>$coinname is under  $pcoin <br/>";
        $blockgum = blockgum($pcoin);
        $resp=$blockgum->moveDepositsToMainAccount();
        var_dump($resp);
        echo 'Cron Ended';
        exit;
    }

    //Blockgum add token to watch list of blockgum
    public function blockgum_watch()
    {
        cronLastRun(__FUNCTION__);
        $Coins=C('coin');
        foreach($Coins as $coininfo){
            if ($coininfo['type'] != 'blockgum' || !$coininfo['dj_yh']) {
                continue;
                if ($coininfo['dj_yh']) {
                //    echo 'This token should be type of blockgum';
                }
                
            }
            $coinname=$coininfo['name'];
    
            $contract_address=$coininfo['dj_yh'] ;

            $pcoin = $coininfo['tokenof'] ?: $coininfo['name'];
            $blockgum=blockgum($pcoin);
            $parent = $this->findSymbol($coinname);
            
            echo "<br/>$coinname is token of $pcoin and has contract address $contract_address<br/>";
            
            $resp=$blockgum->watchToken($contract_address);
            var_dump($resp);
            }
        echo 'Cron Ended';
    }
    //Uses BlockReading Technique :CryptoNote
    public function wallet_cryptonote_deposit()
    {
        cronLastRun(__FUNCTION__);
        echo 'Start<br/>';
        $coinList = M('Coin')->where(array('status' => 1, 'type' => 'cryptonote', 'zr_jz' => 1))->select();

        foreach ($coinList as $k => $v) {
            if ($v['type'] != 'cryptonote') {
                continue;
            }

            $coin = $v['name'];

            if (!$coin) {
                echo 'MM';
                continue;
            }
            $dj_username = C('coin')[$coin]['dj_yh'];
            $dj_password = C('coin')[$coin]['dj_mm'];
            $dj_address = C('coin')[$coin]['dj_zj'];
            $dj_port = C('coin')[$coin]['dj_dk'];
            $dj_decimal = C('coin')[$coin]['cs_qk'];
            $main_address = C('coin')[$coin]['codono_coinaddress'];
            $block = $v['block'];

            $cryptonote = CryptoNote($dj_address, $dj_port);
            $open_wallet = $cryptonote->open_wallet($dj_username, $dj_password);

            $json = json_decode($cryptonote->get_height());

            if (!isset($json->height) || $json->error != 0) {
                echo '###ERR#####***** ' . $coin . ' connect fail***** ####ERR####>' . '<br/>';
                continue;
            }

            if ($block > ($json->height)) {
                $block = $json->height - 1;
            }
            $min_height = $block - 30;
            $max_height = $block + 10;
            $mo = M();

            echo '<br/>***************<b>start Reading ' . $coin . ' on block ' . $block . '</b>***************<br/>';
            echo 'Blockchain Height:' . $json->height . '<br/>';
            echo $coin . ' on CryptoNote,connect ' . (empty($cryptonote) ? 'fail' : 'ok') . ' :' . '<br/>';

            $txs = json_decode($cryptonote->get_transfers('all', 0, '', $min_height, $max_height), true);
            //echo count($txs) ." txs Found for your address , Lets see how many belongs to users";

            foreach ($txs['in'] as $tx) {
                //Only transfer deposits
                if ($tx['txid']) {

                    //All recipients

                    if ($tx['type'] == 'in') {

                        $coinb = $coin . 'b';
                        $user = M('UserCoin')->where(array($coinb => $tx['address']))->find();
                        if (!$user) continue;

                        $already_processed = M('Myzr')->where(array('txid' => $tx['txid']))->find();
                        if ($already_processed) {
                            echo $tx['address'] . '=>' . $tx['txid'] . ' hash has already been added , Look for another TX' . '<br/>';
                            continue;
                        }

                        //We have found that This tx belongs to someone

                        $to_num = $cryptonote->deAmount($tx['amount']);//Quantity

                        if (M('Myzr')->where(array('txid' => $tx['txid'], 'status' => '1', 'type' => 'cryptonote'))->find()) {
                            echo 'txid had found continue' . '<br/>';
                            continue;
                        }

                        echo 'all check ok ' . '<br/>';

                        echo '<br/>start receive do:' . '<br/>';
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
                            echo 'All transactions are confirmed' . '<br/>';
                        }


                        $mo->startTrans();
                        $rs = array();

                        $coin = $this->findSymbol($coin);

                        if ($res = $mo->table('codono_myzr')->where(array('txid' => $tx['txid']))->find()) {
                            echo 'codono_myzr find and set status 1';
                            $rs[] = $mo->table('codono_myzr')->save(array('id' => $res['id'], 'addtime' => time(), 'status' => 1));
                        } else {
                            echo 'codono_myzr not find and add a new codono_myzr for ' . $tx['address'] . '<br/>';

                            $add_tx = array('userid' => $user['userid'], 'username' => $tx['address'], 'coinname' => $coin, 'fee' => $sfee, 'txid' => $tx['txid'], 'num' => $true_amount, 'mum' => $true_amount, 'addtime' => time(), 'status' => 1);
                            $rs[] = $mo->table('codono_myzr')->add($add_tx);
                            $rs[] = $mo->table('codono_user_coin')->where(array('userid' => $user['userid']))->setInc($coin, $true_amount);
                        }

                        if (check_arr($rs)) {
                            $mo->commit();
                            echo $true_amount . ' receive ok ' . $coin . ' ' . $true_amount;

                            echo 'commit ok, Notify customer' . '<br/>';
                            deposit_notify($user['userid'], $tx['address'], $coin, $tx['txid'], $true_amount, time());
                        } else {
                            echo $true_amount . 'receive fail ' . $coin . ' ' . $true_amount;
                            $mo->rollback();

                            print_r($rs);
                            echo 'rollback ok' . '<br/>';
                        }
                    }
                }


            }
            //Saving Last read Block
            $block = $block + 1;
            $mo->execute("UPDATE `codono_coin` SET  `block` =  '" . $block . "' WHERE name='" . $coin . "' ");

        }
    }


    public function wallet_cryptonote2_deposit()
    {
        cronLastRun(__FUNCTION__);
        echo 'Start<br/>';
        $coinList = M('Coin')->where(array('status' => 1, 'type' => 'cryptonote', 'zr_jz' => 1))->select();

        foreach ($coinList as $k => $v) {
            if ($v['type'] != 'cryptonote') {
                continue;
            }

            $coin = $v['name'];

            if (!$coin) {
                echo 'MM';
                continue;
            }
            $dj_username = C('coin')[$coin]['dj_yh'];
            $dj_password = C('coin')[$coin]['dj_mm'];
            $dj_address = C('coin')[$coin]['dj_zj'];
            $dj_port = C('coin')[$coin]['dj_dk'];
            $dj_decimal = C('coin')[$coin]['cs_qk'];
            $main_address = C('coin')[$coin]['codono_coinaddress'];
            $block = C('coin')[$coin]['block'];

            $cryptonote = CryptoNote($dj_address, $dj_port);
            $open_wallet = $cryptonote->open_wallet($dj_username, $dj_password);

            $json = json_decode($cryptonote->get_height());

            if (!isset($json->height) || $json->error != 0) {
                echo '###ERR#####***** ' . $coin . ' connect fail***** ####ERR####>' . '<br/>';
                continue;
            }

            if ($block > ($json->height)) {
                $block = $json->height - 1;
            }
            $min_height = $block - 30;
            $max_height = $block + 10;


            echo '<br/>***************<b>start Reading ' . $coin . ' on block ' . $block . '</b>***************<br/>';
            echo 'Blockchain Height:' . $json->height . '<br/>';
            echo $coin . ' on CryptoNote,connect ' . (empty($cryptonote) ? 'fail' : 'ok') . ' :' . '<br/>';

            $txs = json_decode($cryptonote->get_transfers('all', 0, '', $min_height, $max_height), true);
            //echo count($txs) ." txs Found for your address , Lets see how many belongs to users";

            foreach ($txs['transfers'] as $tx) {
                //Only transfer deposits
                if ($tx['transactionHash']) {

                    //All recipients

                    if ($tx['paymentId']) {
                        $coinb = $coin . 'b';
                        $coin_tag = $coin . '_tag';
                        echo "<br/>$coin Found Payment at paymentid: <strong>" . $tx['paymentId'] . '</strong><br/>';
                        echo 'Next checking associated user with above payid';

                        $user = M('UserCoin')->where(array($coin_tag => $tx['paymentId']))->find();
                        if (!$user) continue;


                        $already_processed = M('Myzr')->where(array('txid' => $tx['transactionHash']))->find();
                        if ($already_processed) {
                            echo $tx['paymentId'] . '=>' . $tx['transactionHash'] . ' hash has already been added , Look for another TX' . '<br/>';
                            continue;
                        }

                        //We have found that This tx belongs to someone

                        $to_num = $cryptonote->deAmount($tx['amount']);//Quantity

                        if (M('Myzr')->where(array('txid' => $tx['transactionHash'], 'status' => '1', 'type' => 'cryptonote'))->find()) {
                            echo 'txid had found continue' . '<br/>';
                            continue;
                        }

                        echo 'all check ok ' . '<br/>';

                        echo '<br/>start receive do:' . '<br/>';
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
                            echo 'All transactions are confirmed' . '<br/>';
                        }

                        $mo = M();

                        $mo->startTrans();
                        $rs = array();


                        $coin = $this->findSymbol($coin);
                        if ($res = $mo->table('codono_myzr')->where(array('txid' => $tx['transactionHash']))->find()) {
                            echo 'codono_myzr find and set status 1';
                            $rs[] = $mo->table('codono_myzr')->save(array('id' => $res['id'], 'addtime' => time(), 'status' => 1));
                        } else {
                            echo 'codono_myzr not find and add a new codono_myzr for ' . $tx['paymentId'] . '<br/>';
                            $add_tx = array('userid' => $user['userid'], 'username' => $tx['paymentId'], 'coinname' => $coin, 'fee' => $sfee, 'txid' => $tx['transactionHash'], 'num' => $true_amount, 'mum' => $true_amount, 'addtime' => time(), 'status' => 1);
                            $rs[] = $mo->table('codono_myzr')->add($add_tx);
                            $rs[] = $mo->table('codono_user_coin')->where(array('userid' => $user['userid']))->setInc($coin, $true_amount);

                        }

                        if (check_arr($rs)) {
                            $mo->commit();

                            echo $true_amount . ' receive ok ' . $coin . ' ' . $true_amount;
                            echo 'commit ok, Notify customer' . '<br/>';
                            deposit_notify($user['userid'], $tx['paymentId'], $coin, $tx['transactionHash'], $true_amount, time());
                        } else {
                            echo $true_amount . 'receive fail ' . $coin . ' ' . $true_amount;
                            $mo->rollback();

                            print_r($rs);
                            echo 'rollback ok' . '<br/>';
                        }
                    }
                }


            }
            //Saving Last read Block
            $block = $block + 1;
            M()->execute("UPDATE `codono_coin` SET  `block` =  '" . $block . "' WHERE name='" . $coin . "' ");

        }
    }

    //blockio Starts Wallet Cron
    public function wallet_blockio_deposit()
    {
        cronLastRun(__FUNCTION__);
        $coinList = M('Coin')->where(array('status' => 1, 'zr_jz' => 1))->select();

        foreach ($coinList as $k => $v) {
            if ($v['type'] != 'blockio') {
                continue;
            }

            $coin = $v['name'];

            if (!$coin) {
                echo 'MM';
                continue;
            }
            $dj_username = C('coin')[$coin]['dj_yh'];
            $dj_password = C('coin')[$coin]['dj_mm'];
            $dj_address = C('coin')[$coin]['dj_zj'];
            $dj_port = C('coin')[$coin]['dj_dk'];
            echo 'start ' . $coin . "\n";
            $block_io = BlockIO($dj_username, $dj_password, $dj_address, $dj_port, 5, array(), 1);
            $json = $block_io->get_balance();

            if (!isset($json->status) || $json->status != 'success') {
                echo '###ERR#####***** ' . $coin . ' connect fail***** ####ERR####>' . "\n";
                continue;
            }

            echo 'Cmplx ' . $coin . ' start,connect ' . (empty($block_io) ? 'fail' : 'ok') . ' :' . "\n";

            $listtransactions = $block_io->get_transactions(array('type' => 'received', 'labels' => ''));
            echo 'listtransactions:' . count($listtransactions->data->txs) . "\n";
            krsort($listtransactions->data->txs);

            foreach ($listtransactions->data->txs as $tranx) {


                $addr = $tranx->amounts_received[0]->recipient;
                $addr_info = $block_io->get_address_balance(array('addresses' => $addr));
                $account = $addr_info->data->balances[0]->label;

                $trans['account'] = $account;
                $trans['category'] = 'receive';
                $trans['amount'] = $tranx->amounts_received[0]->amount;
                $trans['confirmations'] = $tranx->confirmations;
                $trans['address'] = $tranx->amounts_received[0]->recipient;
                $trans['txid'] = $tranx->txid;


                if (!$trans['account'] || $account == 'default') {
                    echo 'empty account continue' . "\n";
                    continue;
                }

                if (!($user = M('User')->where(array('username' => $trans['account']))->find())) {
                    echo 'no account find continue' . "\n";
                    continue;
                }

                if (M('Myzr')->where(array('txid' => $trans['txid'], 'status' => '1'))->find()) {
                    echo 'txid had found continue' . "\n";
                    continue;
                }

                echo 'all check ok ' . "\n";

                if ($trans['category'] == 'receive') {
                    echo '<pre>';
                    print_r($trans);
                    echo '</pre>';
                    echo '<br/>start receive do:' . "\n";
                    $sfee = 0;
                    $true_amount = $trans['amount'];

                    if (C('coin')[$coin]['zr_zs']) {
                        $song = round(($trans['amount'] / 100) * C('coin')[$coin]['zr_zs'], 8);

                        if ($song) {
                            $sfee = $song;
                            $trans['amount'] = $trans['amount'] + $song;
                        }
                    }

                    if ($trans['confirmations'] < C('coin')[$coin]['zr_dz']) {
                        echo $trans['account'] . ' confirmations ' . $trans['confirmations'] . ' not enough ' . C('coin')[$coin]['zr_dz'] . ' continue ' . "\n";
                        echo 'confirmations <  c_zr_dz continue' . "\n";

                        if ($res = M('myzr')->where(array('txid' => $trans['txid']))->find()) {
                            M('myzr')->save(array('id' => $res['id'], 'addtime' => time(), 'status' => intval($trans['confirmations'] - C('coin')[$coin]['zr_dz'])));
                        } else {
                            M('myzr')->add(array('userid' => $user['id'], 'username' => $trans['address'], 'coinname' => $coin, 'fee' => $sfee, 'txid' => $trans['txid'], 'num' => $true_amount, 'mum' => $trans['amount'], 'addtime' => time(), 'status' => intval($trans['confirmations'] - C('coin')[$coin]['zr_dz'])));

                        }

                        continue;
                    } else {
                        echo 'confirmations full' . "\n";
                    }

                    $mo = M();

                    $mo->startTrans();
                    $rs = array();
                    $coin = $this->findSymbol($coin);


                    if ($res = $mo->table('codono_myzr')->where(array('txid' => $trans['txid']))->find()) {
                        echo 'codono_myzr find and set status 1';
                        $rs[] = $mo->table('codono_myzr')->save(array('id' => $res['id'], 'addtime' => time(), 'status' => 1));
                    } else {
                        echo 'codono_myzr not find and add a new codono_myzr' . "\n";
                        $rs[] = $mo->table('codono_myzr')->add(array('userid' => $user['id'], 'username' => $trans['address'], 'coinname' => $coin, 'fee' => $sfee, 'txid' => $trans['txid'], 'num' => $true_amount, 'mum' => $trans['amount'], 'addtime' => time(), 'status' => 1));
                        $rs[] = $mo->table('codono_user_coin')->where(array('userid' => $user['id']))->setInc($coin, $trans['amount']);
                    }

                    if (check_arr($rs)) {
                        $mo->commit();
                        echo $trans['amount'] . ' receive ok ' . $coin . ' ' . $trans['amount'];

                        echo 'commit ok, Notify Customer' . "\n";
                        deposit_notify($user['id'], $trans['address'], $coin, $trans['txid'], $true_amount, time());
                    } else {
                        echo $trans['amount'] . 'receive fail ' . $coin . ' ' . $trans['amount'];
                        echo var_export($rs, true);
                        $mo->rollback();

                        print_r($rs);
                        echo 'rollback ok' . "\n";
                    }
                }
            }

            $this->sub_mark_withdrawn($trans, $coin);
        }
    }
    //blockio Wallet Cron Ends
    //blockio withdraw Starts Wallet Cron
    public function wallet_blockio_withdraw()
    {
        cronLastRun(__FUNCTION__);
        $coinList = C('coin');

        foreach ($coinList as $k => $v) {
            if ($v['type'] != 'blockio') {
                continue;
            }

            $coin = $v['name'];

            if (!$coin) {
                echo 'MM';
                continue;
            }
            $dj_username = C('coin')[$coin]['dj_yh'];
            $dj_password = C('coin')[$coin]['dj_mm'];
            $dj_address = C('coin')[$coin]['dj_zj'];
            $dj_port = C('coin')[$coin]['dj_dk'];
            echo 'start ' . $coin . "\n";
            $block_io = BlockIO($dj_username, $dj_password, $dj_address, $dj_port, 5, array(), 1);
            $json = $block_io->get_balance();

            if (!isset($json->status) || $json->status != 'success') {
                echo '###ERR#####***** ' . $coin . ' connect fail***** ####ERR####>' . "\n";
                continue;
            }

            echo 'Cmplx ' . $coin . ' start,connect ' . (empty($block_io) ? 'fail' : 'ok') . ' :' . "\n";

            $listtransactions = $block_io->get_transactions(array('type' => 'sent', 'labels' => ''));
            echo 'listtransactions:' . count($listtransactions->data->txs) . "\n";
            krsort($listtransactions->data->txs);

            foreach ($listtransactions->data->txs as $tranx) {

                $addr = $tranx->amounts_received[0]->recipient;
                $addr_info = $block_io->get_address_balance(array('addresses' => $addr));
                $account = $addr_info->data->balances[0]->label;

                $trans['account'] = $account;
                $trans['category'] = 'send';
                $trans['amount'] = $tranx->amounts_received[0]->amount;
                $trans['confirmations'] = $tranx->confirmations;
                $trans['address'] = $tranx->amounts_received[0]->recipient;
                $trans['txid'] = $tranx->txid;

                if (!$trans['account'] || $account == 'default') {
                    echo ' empty account continue' . "\n";
                    continue;
                }

                if (!($user = M('User')->where(array('username' => $trans['account']))->find())) {
                    echo 'no account find continue' . "\n";
                    continue;
                }

                if (M('Myzr')->where(array('txid' => $trans['txid'], 'status' => '1'))->find()) {
                    echo 'txid had found continue' . "\n";
                    continue;
                }

                echo 'all check ok ' . "\n";


                $this->sub_mark_withdrawn($trans, $coin);
            }
        }
    }
    //blockio withdraw  Wallet Cron Ends
    //Bitcoin QBB type wallets
    public function deposit_btctype()
    {
        cronLastRun(__FUNCTION__);
        $coinList = C('Coin');

        foreach ($coinList as $k => $v) {
			
            if ($v['type'] != 'qbb') {
                continue;
            }
			if ($v['tokenof'] ) {
                continue;
            }

            if ($v['status'] != 1) {
                continue;
            }
            if ($v['zr_jz'] != 1) {
                continue;
            }
            $coin = $v['name'];

            if (!$coin) {
                echo 'MM';
                continue;
            }
			$pcoin=$coin;
            $dj_username = C('coin')[$coin]['dj_yh'];
            $dj_password = C('coin')[$coin]['dj_mm'];
            $dj_address = C('coin')[$coin]['dj_zj'];
            $dj_port = C('coin')[$coin]['dj_dk'];
            echo 'start ' . $coin . "\n";
            $CoinClient = CoinClient($dj_username, $dj_password, $dj_address, $dj_port, 5, array(), 1);
            $json = $CoinClient->getinfo();

            if (!isset($json['version']) || !$json['version']) {
                echo '###ERR#####***** ' . $coin . ' connect fail***** ####ERR####>' . "\n";
                continue;
            }

            echo '===================<strong>BTCTYPE ' . $coin . ' start,connect ' . (empty($CoinClient) ? 'fail' : 'ok') . ' :' . C('coin')[$coin]['type'].C('coin')[$coin]['tokenof']. '</strong>===================<br/>';
            $listtransactions = $CoinClient->listtransactions('*', 100);
            echo 'listtransactions:' . count($listtransactions) . "\n";
            krsort($listtransactions);


            echo 'Start<br/>' .time();
            foreach ($listtransactions as $trans) {
			$true_amount = $trans['amount'];
                if ($trans['category'] != 'receive') {
                    echo '<br/>'.$trans['txid'] .'<strong>Transaction Type is not deposit found '.$trans['category'] . '</strong><br/>';
                    continue;
                }

                if (!isset($trans['address']) || !(strlen($trans['address'])>10)) {
                    echo 'empty address stop' . "\n";
                    continue;
                }
				
				
				//@todo check if trans consist of group and groupAmount then find group in contracts 
				$found_contract=0;
				if($trans['group'] && $trans['groupAmount'] >0){
							echo 'This is token deposits';
							
							foreach ($coinList as $key => $val) {
							//	echo "<br/>xxx ".$val['contract']." ==".$trans['group']."<br/>";
								if(strtolower($val['contract'])==strtolower($trans['group'])){
									echo 'It was ' .$val['name']. '<br/>';
									$true_amount = $trans['groupAmount'];	
									$coin=$val['name'];
									$found_contract=1;
								}
							}
							if($found_contract==0){
								echo 'This contract address is not on system right now ';continue;
							}
				}
                $mo=M();

                $userid = $mo->table('codono_user_coin')->where(array($pcoin . 'b' => $trans['address']))->find() ;
				$sql_query=$mo->getLastSql();
                if (!($userid) || !isset($userid['userid']) || !($userid['userid' ]> 0)){
                    echo 'no account find Check next' . '===========ENDED============<br/>';
                    continue;
                }else{
                    echo '<br/>Found Deposit<br/>';
                  
                }

                $uid=$userid['userid'];
                if($uid <=1){
                    //clog('BTCTYPE',$userid);
					  
                }
                if (M('Myzr')->where(array('txid' => $trans['txid'], 'status' => '1'))->find()) {
                    echo 'txid had found Already deposited' . "\n";
                    continue;
                }


                $user = M('User')->where(array('id' => $uid))->find();
                if($uid != $user['id'] || !($uid > 0) ){
                    echo "<br/>Seems UserID $uid is deleted <br/>";continue;
                }
                if($uid==1){
                    
                    dblog( 'btc_type',json_encode(['uid'=>$uid,'trans'=>$trans,'sql_query'=>$sql_query]));
                }
                echo 'all check ok ' . '<br/>';

                if ($trans['category'] == 'receive') {
                    echo '<pre>';
                    print_r($trans);
                    echo '</pre>';
                    echo '<br/>start receive do:' . "\n";
                    $sfee = 0;
                    

                    if (C('coin')[$coin]['zr_zs']) {
                        $song = round(($true_amount / 100) * C('coin')[$coin]['zr_zs'], 8);

                        if ($song) {
                            $sfee = $song;
                            $true_amount = $true_amount + $song;
                        }
                    }

                    if ($trans['confirmations'] < C('coin')[$coin]['zr_dz']) {
                        echo $trans['account'] . ' confirmations ' . $trans['confirmations'] . ' not elengh ' . C('coin')[$coin]['zr_dz'] . ' continue ' . "\n";
                        echo 'confirmations <  c_zr_dz continue' . "\n";

                        if ($res = M('myzr')->where(array('txid' => $trans['txid']))->find()) {
                            M('myzr')->save(array('id' => $res['id'], 'addtime' => time(), 'status' => intval($trans['confirmations'] - C('coin')[$coin]['zr_dz'])));
                        } else {
							dblog('x_type',['uid'=>$uid,'last'=>$sql_query,$coin . 'b' => $trans['address'] ]);
                            M('myzr')->add(array('userid' => $uid, 'username' => $trans['address'], 'coinname' => $coin, 'fee' => $sfee, 'txid' => $trans['txid'], 'num' => $true_amount, 'mum' => $true_amount, 'addtime' => time(), 'status' => intval($trans['confirmations'] - C('coin')[$coin]['zr_dz'])));
                        }
                        
						
                        continue;
                    } else {
                        echo 'confirmations full' . "\n";
                    }

                    $mo = M();

                    $mo->startTrans();
                    $rs = array();
                    $coin = $this->findSymbol($coin);
                    $rs[] = $mo->table('codono_user_coin')->where(array('userid' => $uid))->setInc($coin, $true_amount);

                    if ($res = $mo->table('codono_myzr')->where(array('txid' => $trans['txid']))->find()) {
                        echo 'codono_myzr find and set status 1';
                        $rs[] = $mo->table('codono_myzr')->save(array('id' => $res['id'], 'addtime' => time(), 'status' => 1));
                    } else {
                        echo 'codono_myzr not find and add a new codono_myzr' . "\n";
                        $rs[] = $mo->table('codono_myzr')->add(array('userid' => $uid, 'username' => $trans['address'], 'coinname' => $coin, 'fee' => $sfee, 'txid' => $trans['txid'], 'num' => $true_amount, 'mum' => $true_amount, 'addtime' => time(), 'status' => 1));

                    }

                    if (check_arr($rs)) {
                        $mo->commit();
                        echo $true_amount . ' receive ok ' . $coin . ' ' . $true_amount;

                        echo 'commit ok Notify Customer' . "\n";
                        deposit_notify($uid, $trans['address'], $coin, $trans['txid'], $true_amount, time());
                    } else {
                        echo $true_amount . 'receive fail ' . $coin . ' ' . $true_amount;
                        echo var_export($rs, true);
                        $mo->rollback();

                        print_r($rs);
                        echo 'rollback ok' . "\n";
                    }
                }
            }

            $this->sub_mark_withdrawn($trans, $coin);
        }
    }
//Coinpayments withdraw Starts Wallet Cron

    public function wallet_coinpay_withdraw()
    {
        cronLastRun(__FUNCTION__);
        $coinList = C('coin');

        foreach ($coinList as $k => $v) {
            if ($v['type'] != 'coinpay') {
                continue;
            }

            $coin = $v['name'];

            if (!$coin) {
                echo 'MM';
                continue;
            }
            echo '<br/>============******============<br/>start ' . $coin . '<br/>';

            $condition = array('ipn_type' => 'withdrawal', 'currency' => strtoupper($coin), 'funded' => array('neq', '1'));
            $CoinPayIPNresp = M('CoinpayIpn')->where($condition)->order('id asc')->limit(50)->select();
            $count_log = count($CoinPayIPNresp);
            echo 'Processing Records:' . $count_log;
            if ($count_log > 0) {
                $coinb = $coin . 'b';

                foreach ($CoinPayIPNresp as $trans) {
                    /*
                            if (!($user = M('UserCoin')->where(array($coinb => $trans['address']))->find())) {
                                     echo 'no user address found continue' . "<br/>";
                                        continue;
                                    }
                      */

                    echo '<br/>all check ok ' . '<br/>';

                    if ($trans['status'] == 2) {
                        if ($myzcid = M('Myzc')->where(array('hash' => $trans['cid'], 'status' => '1'))->find()) {

                            echo 'txid had found saving txid' . '<br/>';
                            M('Myzc')->where(array('id' => $myzcid['id']))->save(array('txid' => $trans['txn_id']));
                            M('CoinpayIpn')->where(array('id' => $trans['id']))->save(array('funded' => '1'));
                            continue;
                        }
                    }
                }// end tx forach
            }//countlog
        }//end coin foreach
    }
    //Coinpay Deposit
    /* this cron checks coinpay_ipn table and proccess records
    IPN code is situated in Home/Controller/IPNController.class.php

    */
    public function wallet_coinpay_deposit()
    {
        $coinList = M('Coin')->where(array('status' => 1, 'zr_jz' => 1))->select();

        foreach ($coinList as $k => $v) {
            if ($v['type'] != 'coinpay') {
                continue;
            }

            $coin = $v['name'];

            if (!$coin) {
                echo 'MM';
                continue;
            }
            echo '<br/>============******============<br/>start ' . $coin . '<br/>';

            $condition = array('ipn_type' => 'deposit', 'currency' => strtoupper($coin), 'funded' => array('neq', '1'));
            $CoinPayIPNresp = M('CoinpayIpn')->where($condition)->order('id asc')->limit(50)->select();
            $count_log = count($CoinPayIPNresp);
            echo 'Processing Records:' . $count_log;
            if ($count_log > 0) {
                $coinb = $coin . 'b';

                foreach ($CoinPayIPNresp as $trans) {
                    $user_condition = array();
                    $user_condition[$coinb] = $trans['address'];
                    if ($trans['dest_tag'] != NULL) {
                        $user_condition[$coin . '_tag'] = $trans['dest_tag'];
                    }

                    if (!($user = M('UserCoin')->where($user_condition)->find())) {

                        echo 'no account found continue' . '<br/>';
                        continue;
                    }
                    if (M('Myzr')->where(array('txid' => $trans['txn_id'], 'status' => '1'))->find()) {
                        echo 'txid had found continue' . '<br/>';
                        M('CoinpayIpn')->where(array('id' => $trans['id']))->save(array('funded' => '1'));
                        continue;
                    }

                    echo '<br/>all check ok ' . '<br/>';

                    if ($trans['status'] == 100) {
                        echo '<pre>';
                        print_r($trans);
                        echo '</pre>';
                        echo '<br/>start receive do:' . '<br/>';
                        $sfee = 0;
                        $true_amount = $trans['amount'];
                        $receivable = $trans['amount'] - $trans['fee']; //Depositable amount
                        if (C('coin')[$coin]['zr_zs']) {
                            $song = round(($trans['amount'] / 100) * C('coin')[$coin]['zr_zs'], 8);

                            if ($song) {
                                $sfee = $song;
                                $trans['amount'] = $trans['amount'] + $song;
                            }
                        }
                        $mo = M();

                        $mo->startTrans();
                        $rs = array();
                        //Add Balance
                        $coin = $this->findSymbol($coin);

                        $res = $mo->table('codono_myzr')->where(array('txid' => $trans['txn_id']))->find();

                        //continue;
                        if ($res) {
                            echo 'Coinpay myzr found and set status 1<br/>';
                            $rs[] = $mo->table('codono_myzr')->save(array('id' => $res['id'], 'addtime' => time(), 'status' => 1));
                        } else {
                            echo 'myzr entry not found and add new coinpay deposit record<br/>' . '<br/>';
                            $rs[] = $mo->table('codono_myzr')->add(array('userid' => $user['userid'], 'username' => $trans['address'], 'coinname' => $coin, 'fee' => $sfee, 'txid' => $trans['txn_id'], 'num' => $true_amount, 'mum' => $receivable, 'addtime' => time(), 'status' => 1));
                            $rs[] = $mo->table('codono_user_coin')->where(array('userid' => $user['userid']))->setInc($coin, $receivable);
                        }

                        if (check_arr($rs)) {
                            $mo->commit();

                            echo 'Processing CoinpayID:' . $trans['id'] . '<br/>';
                            echo $trans['amount'] . ' receive ok ' . $coin . ' ' . $trans['amount'];
                            echo 'commit ok' . '<br/>';
                            M('CoinpayIpn')->where(array('id' => $trans['id']))->save(array('funded' => '1'));
                            deposit_notify($user['userid'], $trans['address'], $coin, $trans['txn_id'], $true_amount, time());
                        } else {
                            echo $trans['amount'] . 'receive fail ' . $coin . ' ' . $trans['amount'];
                            $mo->rollback();

                            echo 'rollback ok' . '<br/>';
                        }
                    }
                }
            }
        }
    }


    private function FindWavesAssetID($assetid)
    {
        //	$assetid="4Zekj8nGpwzuEw1B7JFxLnDWmfcaFHyD8ZZKVWLwBbUC";
        $assetid = trim($assetid);
        $assetid = $assetid ?: '';
        $coin = M('Coin')->field('name')->where(array('type' => 'waves', 'status' => 1, 'dj_yh' => $assetid))->find();
        return ($coin['name'] ?: null);
    }

    //Moving Funds from some address to main address [This is for moving assets only  to main account ] Run me first
    public function MoveFundsToWaveMainAccount()
    {
        cronLastRun(__FUNCTION__);
        $name = I('get.name', '','trim');
        if ($name != '') {
            $cond['name'] = 'waves';
        }
        $min_asset_required = 1;
        $min_balance_required = 0.002;
        if (!check($name, 'a')) {
            die('invalidcoin');
        }
        $cond['name'] = (string)$name;
        $coinList = M('Coin')->where($cond)->select();

        foreach ($coinList as $k => $v) {
            if ($v['type'] != 'waves') {
                continue;
            }
            if ($v['name'] == 'waves') {
                continue;
            }
            $coin = $v;
            $coinname = $coin['name'];
            $dj_username = $coin['dj_yh'];
            $dj_password = $coin['dj_mm'];
            $dj_address = $coin['dj_zj'];
            $dj_port = $coin['dj_dk'];
            $main_address = $coin['codono_coinaddress'];
            $dj_decimal = $coin['cs_qk'];

            echo '<br/>*************Moving all <b>' . $coinname . '</b> funds to ' . $main_address . '*************<br/>';

            $waves = WavesClient($dj_username, $dj_password, $dj_address, $dj_port, $dj_decimal, 5, array(), 1);
            $record = json_decode($waves->ValidateAddress($main_address), true);
            if (!$record['valid']) {
                exit;
            } else {
                echo $main_address . ' is valid <br/>';
            }
            $condition['status'] = 1;
            //$condition['shifted_to_main']=0;
            $condition['coinname'] = $coinname;
            $transactions = M('Myzr')->where($condition)->distinct(true)->field('username')->order('id')->limit(50)->select();

            foreach ($transactions as $txs) {
                echo '<br/>Transferrring <pre>';
                print_r($txs);
                echo '</pre>';

                $addr = $txs['username'];
                if ($addr == $main_address) {
                    continue;
                }
                $info = json_decode($waves->Balance($addr, $dj_username), true);
                $dj_decimal = $dj_decimal ?: 8;
                $total_balance = $waves->deAmount($info['balance'], $dj_decimal);
                $waves_balance_of_customer = json_decode($waves->Balance($addr, ''), true);
                $waves_balance_of_customer_i = $waves->deAmount($waves_balance_of_customer['balance'], $dj_decimal);
                //Check if User address has enough coins , send them to main account.
                if ($dj_username) {
                    $min_balance_required = 1;
                }
                if ($total_balance > $min_balance_required) {
                    if ($dj_username) {
                        if ($waves_balance_of_customer_i < 0.001) {

                            //Transfer 0.001 wav to customer first

                            echo '<br/>Balance of user is low:' . $waves_balance_of_customer_i . 'Now sending 0.001 wave';
                            $tx_required_wave = $waves->Send($main_address, $addr, 0.001);
                            print_r($tx_required_wave);
                        } else {
                            print_r($waves_balance_of_customer);
                            echo '<br/> User Waves Balance is ' . $waves_balance_of_customer_i . '<br/>';
                        }
                    }
                    echo '<br/>Now moving ' . $addr . ' balance of ' . $total_balance . ' ' . $coinname . ' decimals' . $dj_decimal . '<br/>';

                    if ($coinname == 'waves') {
                        $total_balance = ($info['balance'] - 0.001);
                        $wavesend_response = $waves->Send($addr, $main_address, $total_balance);
                    } else {

                        $wavesend_response = $waves->Send($addr, $main_address, $total_balance, $dj_username);
                    }
                    echo '<pre>';
                    print_r($wavesend_response);
                    echo '</pre>';
                } else {
                    echo $total_balance . '<' . $min_balance_required;
                }
                $where_condition = array('username' => $txs['username']);
                echo 'Updating for ' . $txs['username'];
                M('Myzr')->where($where_condition)->save(array('shifted_to_main' => 1));
            }//end of txs loop
            //marktx as shifted_to_main

        }//end of coins loop
        echo 'End of cron';
        //return ($coin['name']?$coin['name']:null);
    }

    // [This is for moving waves to main account] run me 2nd
    public function MoveWaves2MainAccount()
    {
        cronLastRun(__FUNCTION__);
        $name = I('get.name', '','trim');
        if ($name != '') {
            $cond['name'] = 'waves';
        }


        $min_asset_required = 1;
        $min_balance_required = 0.002;
        $coinList = M('Coin')->where($cond)->select();

        foreach ($coinList as $k => $v) {
            if ($v['type'] != 'waves') {
                continue;
            }
            if ($v['name'] != 'waves') {
                continue;
            }
            $coin = $v;
            $coinname = $coin['name'];
            $dj_username = $coin['dj_yh'];
            $dj_password = $coin['dj_mm'];
            $dj_address = $coin['dj_zj'];
            $dj_port = $coin['dj_dk'];
            $main_address = $coin['codono_coinaddress'];
            $dj_decimal = $coin['cs_qk'];
            echo '<br/>*************Moving all <b>' . $coinname . '</b> funds to ' . $main_address . '*************<br/>';

            if ($_GET['agree'] != 'yes') {
                $agree_url = $_SERVER['REQUEST_URI'] . '/agree/yes';
                die("<span style='color:red'>Warning:This tool will move all your <b>$coinname</b> funds to $main_address ,  <a href='$agree_url'> Do you agree?</a></span>");
            }

            $waves = WavesClient($dj_username, $dj_password, $dj_address, $dj_port, $dj_decimal, 5, array(), 1);
            $record = json_decode($waves->ValidateAddress($main_address), true);
            if (!$record['valid']) {
                exit("Please check main account of $coinname" . $main_address);
            } else {
                echo $main_address . ' is valid All funds will be moved there<br/>';
            }
            $condition['status'] = 1;
            $condition['shifted_to_main'] = 1;
            $condition['coinname'] = $coinname;
            $transactions = M('Myzr')->where($condition)->order('id asc')->limit(50)->select();

            foreach ($transactions as $txs) {


                $addr = $txs['username'];
                if ($addr == $main_address) {
                    continue;
                }
                $info = json_decode($waves->Balance($addr), true);
                $the_bal = ($info['balance'] - 100000);
                $total_balance = $waves->deAmount($the_bal, 8);

                if ($total_balance < $min_balance_required) {
                    echo '<br/>' . $addr . ' has  Low balance:' . $total_balance . ' skipping to next address<br/>';
                    continue;
                }
                $total_balance = ($the_bal);
                echo '<br/>Now moving ' . $addr . ' balance of ' . $total_balance . ' ' . $coinname . ' decimals 8' . '<br/>';

                $wavesend_response = $waves->SendWavesJustForMain($addr, $main_address, $total_balance);


                echo '<pre>';
                print_r($wavesend_response);
                echo '</pre>';

            }//end of txs loop
            //marktx as shifted_to_main
            $status = M('Myzr')->where(array('id' => $txs['id']))->save(array('shifted_to_main' => 1));
        }//end of coins loop
        echo 'End of cron';
        //return ($coin['name']?$coin['name']:null);
    }

    //Uses BlockReading Technique :Good for big exchanges
    public function wallet_waves_deposit()
    {
        cronLastRun(__FUNCTION__);
        $coinList = M('Coin')->where(array('status' => 1, 'type' => 'waves', 'zr_jz' => 1))->select();

        foreach ($coinList as $k => $v) {
            if ($v['name'] != 'waves') {
                continue;
            }

            $coin = $v['name'];

            if (!$coin) {
                echo 'MM';
                continue;
            }
            $dj_username = C('coin')[$coin]['dj_yh'];
            $dj_password = C('coin')[$coin]['dj_mm'];
            $dj_address = C('coin')[$coin]['dj_zj'];
            $dj_port = C('coin')[$coin]['dj_dk'];
            $dj_decimal = C('coin')[$coin]['cs_qk'];
            $main_address = C('coin')[$coin]['codono_coinaddress'];
            $block = C('coin')[$coin]['block'];

            $waves = WavesClient($dj_username, $dj_password, $dj_address, $dj_port, $dj_decimal, 5, array(), 1);

            $information = json_decode($waves->status(), true);
            if ($information['blockchainHeight'] && $information['blockchainHeight'] <= 0) {
                echo '###ERR#####***** Waves connect fail***** ####ERR####>' . '<br/>';
                continue;
            }
            if ($block >= ($information['blockchainHeight'] - 1)) {
                $block = $information['blockchainHeight'] - 1;
            }

            echo 'start Reading ' . $coin . ' on block ' . $block . '<br/>';
            echo 'Cmplx Waves start,connect ' . (empty($waves) ? 'fail' : 'ok') . ' :' . '<br/>';

            $txs = json_decode($waves->GetAtHeight($block), true);

            foreach ($txs['transactions'] as $tx) {
                //Only transfer deposits
                if ($tx['type'] == 4) {

                    //All recipients
                    $record = json_decode($waves->ValidateAddress($tx['recipient']), true);
                    if ($record['valid'] && $record['address'] == $tx['recipient']) {


                        $user = M('UserCoin')->where(array('wavesb' => $tx['recipient']))->find();

                        if (!$user) continue;

                        $already_processed = M('Myzr')->where(array('txid' => $tx['id']))->find();
                        if ($already_processed) {
                            echo $tx['recipient'] . '=>' . $tx['id'] . ' hash has already been added , Look for another TX' . '<br/>';
                            continue;
                        }

                        //We have found that This tx belongs to someone

                        //Now lets find if There is such an asset , and which is the coins , WAVES, MAIS or any token_get_all

                        $coin = $this->FindWavesAssetID($tx['assetId']);

                        if ($coin == NULL) {
                            // There was some transaction Where token was not listed by user, inform admin about this tx and check another tx
                            $this->InformAdmin('waves:' . $tx['id']);
                            continue;
                        }

                        $to_num = $waves->deAmount($tx['amount'], $dj_decimal);//Quantity


                        if (M('Myzr')->where(array('txid' => $tx['id'], 'status' => '1', 'type' => 'waves'))->find()) {
                            echo 'txid had found continue' . '<br/>';
                            continue;
                        }

                        echo 'all check ok ' . '<br/>';

                        echo '<br/>start receive do:' . '<br/>';
                        $sfee = 0;
                        $true_amount = $to_num;

                        if (C('coin')[$coin]['zr_zs']) {
                            $song = bcmul(bcdiv($to_num, 100, 8), C('coin')[$coin]['zr_zs'], 8);

                            if ($song) {
                                $sfee = $song;
                                $true_amount = bcadd($true_amount, $song, 8);
                            }
                        }

                        // ($trans['confirmations'] < C('coin')[$coin]['zr_dz'])
                        {
                            echo 'All transactions are confirmed' . '<br/>';
                        }

                        $mo = M();

                        $mo->startTrans();
                        $rs = array();
                        $coin = $this->findSymbol($coin);


                        if ($res = $mo->table('codono_myzr')->where(array('txid' => $tx['id']))->find()) {
                            echo 'codono_myzr find and set status 1';
                            $rs[] = $mo->table('codono_myzr')->save(array('id' => $res['id'], 'addtime' => time(), 'status' => 1));
                        } else {
                            echo 'codono_myzr not find and add a new codono_myzr for ' . $tx['recipient'] . '<br/>';
                            $add_tx = array('userid' => $user['userid'], 'username' => $tx['recipient'], 'coinname' => $coin, 'fee' => $sfee, 'txid' => $tx['id'], 'num' => $true_amount, 'mum' => $true_amount, 'addtime' => time(), 'status' => 1);
                            $rs[] = $mo->table('codono_myzr')->add($add_tx);
                            $rs[] = $mo->table('codono_user_coin')->where(array('userid' => $user['userid']))->setInc($coin, $true_amount);
                        }

                        if (check_arr($rs)) {
                            $mo->commit();


                            echo $true_amount . ' receive ok ' . $coin . ' ' . $true_amount;
                            echo 'commit ok' . '<br/>';
                            deposit_notify($user['userid'], $tx['recipient'], $coin, $tx['id'], $true_amount, time());
                        } else {

                            $mo->rollback();

                            echo $true_amount . 'receive fail ' . $coin . ' ' . $true_amount;
                            print_r($rs);
                            echo 'rollback ok' . '<br/>';
                        }
                    }
                }


            }
            //Saving Last read Block
            $block = $block + 1;
            M()->execute("UPDATE `codono_coin` SET  `block` =  '" . $block . "' WHERE name='" . $coin . "' ");
            exit();
        }
    }

    //Waves Wallet Cron Ends
    private function InformAdmin($info)
    {
        //Do something , You can send a specific notification to admin , It can be  either email sms , or anything
    }


    public function move2cold()
    {
        cronLastRun(__FUNCTION__);
        $cold_Wallets_count = sizeof(COLD_WALLET);
        if ($cold_Wallets_count == 0) {
            die('NO Cold wallets defined in Config ');
        } else {
            echo '*****************************' . $cold_Wallets_count . ' wallet/s found*****************************<br/>';
        }

        $coldwallet = COLD_WALLET;
        foreach ($coldwallet as $coinname => $values) {
            echo "========================Now checking Coldwallet move for $coinname! wallet/s found!======================<br/>";
            $coldwallet_info = $coldwallet[strtoupper($coinname)];
            if (!$coldwallet_info || substr_count($coldwallet_info, ':') != 2) {
                echo 'error' . 'NO COLD WALLET DEFINED';
                continue;
            }


            $info = explode(':', $coldwallet_info);

            $coldwallet_address = $info[0];
            $maxkeep = $info[1];
            $minsendrequired = $info[2];


            if ($_GET['agree'] != 'yes') {
                $agree_url = $_SERVER['REQUEST_URI'] . '/agree/yes';
                die("<span style='color:red'>Warning:This tool will move all your <b>$coinname</b> funds to $coldwallet_address ,  <a href='$agree_url'> Do you agree?</a></span>");
            }

            $tobesent = 1; //calculate balance-maxkeep
            //check if  tobesent> minsendrequired then only proceed
            $coinname = strtolower($coinname);
            $dj_username = C('coin')[$coinname]['dj_yh'];
            $dj_password = C('coin')[$coinname]['dj_mm'];
            $dj_address = C('coin')[$coinname]['dj_zj'];
            $dj_port = C('coin')[$coinname]['dj_dk'];
            $dj_decimal = C('coin')[$coinname]['cs_qk'];
            $main_address = C('coin')[$coinname]['codono_coinaddress'];
            $explorer_link = C('coin')[$coinname]['js_wk'];
            echo "========================See <b>$coldwallet_address</b> on blockchain explorer for more details!======================<br/>";

            //Bitcoin type starts qbb

            if (C('coin')[$coinname]['type'] == 'qbb') {

                $CoinClient = CoinClient($dj_username, $dj_password, $dj_address, $dj_port, 5, array(), 1);
                $json = $CoinClient->getinfo();

                if (!isset($json['version']) || !$json['version']) {

                    echo "System can not connect to $coinname node<br/>";
                    continue;
                }
                $daemon_balance = $CoinClient->getbalance();
                $tobesent = $daemon_balance - $maxkeep;
                if ($tobesent < $minsendrequired) {

                    echo "<br/>You have $daemon_balance $coinname  but balance of $coinname is required to keep $maxkeep and minimum per tx is $minsendrequired !<br/>";
                    continue;
                }

				 $contract=C('coin')[$coinname]['contract'];    
                if($contract){
                    $sendrs = $CoinClient->token('send',$contract,$coldwallet_address, (double)$tobesent);     
                }else{  
                    $sendrs = $CoinClient->sendtoaddress($coldwallet_address, $tobesent);
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
                    echo 'wallet server Withdraw currency failure!<br/>';
                } else {
                    echo 'Transfer success!' . $sendrs . '<br/>';
                }
                continue;
            } else {
                echo $coinname . 'Coin Type is not compatible for Cold wallet transfer!<br/>';
                continue;
            }
        }
        echo '*****************************CRON ENDS*****************************<br/>';
    }

    /*
  Tokens based on Esmart chain [BNB or bep20]
  First Run
  To set a starting block run this function manually
  replace {BLOCKNUMBER} with first deposits blocknumber
  replace {CRONKEY} with your cronkey
  replace {YOURSITE} with your site url
  http://{YOURSITE}/Home/Queue2/esmart_deposit/b/{BLOCKNUMBER}/securecode/{CRONKEY}
  After this make sure this is runing every minute
   https://{YOURSITE}/Home/Queue2/esmart_deposit/securecode/{CRONKEY}
  */
    //esmart wallet cron starts
    public function esmart_deposit()
    {   
        cronLastRun(__FUNCTION__);
        G('begin');
        $chain = I('get.chain', '','trim');
        $b = I('get.b', NULL,'intval');
        $amount = 0;
        $token_address_found = 0;
        $all_coinsList = C('coin');
        $tokens_list = $coinList = array();
        foreach ($all_coinsList as $coinex) {
            if ($coinex['type'] == 'esmart' && $coinex['status'] == 1) {
                $coinList[] = $coinex;
                if ($coinex['tokenof'] == $chain && $coinex['dj_yh'] != '') {
                    $tokens_list[] = strtolower($coinex['dj_yh']);
                }

            }
        }

        echo '<br/>Found ' . count($tokens_list) . " tokens of $chain<br/>";
        if(count($tokens_list)==0){
         //   echo "Cron ENDED";exit;
        }
        $coin_esmart = C('coin')[$chain];

        $esmart_details = C('coin')[$coin_esmart['name']];

        $coinName_ce = $coin_esmart['name'];  // Assuming $coin_esmart['name'] is the value you want to query
        $block = M('Coin')->where(['name' => $coinName_ce])->select();

        $esmart_details = isset($block[0]) ? $block[0] : [];


        $pcoin = $esmart_details['tokenof'] ?: $esmart_details['name'];

        $pcoinb = $pcoin . 'b';

        // Saving Userid's and their addresses  $pcoinb_array

        $coin_Query = 'SELECT count(' . $pcoinb . ') as count FROM `codono_user_coin` WHERE `' . $pcoinb . '` is not null';
        $pcoinb_count = M()->query($coin_Query)[0]['count'];
        $coin_Query = 'SELECT ' . $pcoinb . ' as addr , userid  FROM `codono_user_coin` WHERE `' . $pcoinb . '` is not null';
        $pcoinb_array = M()->query($coin_Query);


        $main_account = $esmart_details['codono_coinaddress'];


        $esmart_config = array(
            'host' => $esmart_details['dj_zj'],
            'port' => $esmart_details['dj_dk'],
            'coinbase' => $esmart_details['codono_coinaddress'],
            'password' => cryptString($esmart_details['dj_mm'], 'd'),
            'contract' => $esmart_details['dj_yh'],
            'rpc_type' => $esmart_details['rpc_type'],
            'public_rpc' => $esmart_details['public_rpc'],
        );
        $Esmart = Esmart($esmart_config);


        $decimals = $esmart_details['cs_qk'] ?: 8;

        $higesthblock = $currentBlock = $Esmart->eth_blockNumber();
        if ($b) {

            $listtransactions = $Esmart->EsmartInCrontab($b);

            $next_block = $b;
            $the_sql = "UPDATE `codono_coin` SET  `block` =  '" . $next_block . "' WHERE name='" . $pcoin . "' AND type='esmart' ";
            M()->execute($the_sql);

            if (sizeof($listtransactions) > 1) {

                $the_sql = "UPDATE `codono_coin` SET  `block` =  '" . $next_block . "' WHERE name='" . $pcoin . "' AND type='esmart' ";
                M()->execute($the_sql);
            } else {
                echo 'Block cant be read or doesnt any transactions, So stopped here and will retry again in sometime <br/>';
                var_dump($listtransactions[0]);
                exit();
            }


        } else {
            echo 'You have not defined any Block, So taking a block value from DB which is ' . $esmart_details['block'] . '<br/>';

            $listtransactions = $Esmart->EsmartInCrontab($esmart_details['block']);
            //Check if block has been actually having txs
            if ($listtransactions[0]['current'] > $esmart_details['block']) {
                $next_block = $listtransactions[0]['block'] + 1;
                $the_sql = "UPDATE `codono_coin` SET  `block` =  '" . $next_block . "' WHERE name='" . $pcoin . "' AND type='esmart' ";
                if ($listtransactions[0]['block']) {
                    M()->execute($the_sql);
                }
            } else {
                echo 'Block cant be read or doesnt any transactions, So stopped here and will retry again in sometime <br/>';
                var_dump($listtransactions[0]);
                exit();
            }
        }

        $time_start = microtime(true);

        if ($higesthblock == '0x0' || $higesthblock == null) {
            $blockNumberInfo = $Esmart->eth_syncing();
            $higesthblock = $blockNumberInfo['highestBlock'];
            $currentBlock = $blockNumberInfo['currentBlock'];
        }

        echo '<br/>Your currentblock is ' . base_convert($currentBlock, 16, 10) . ' where is highest block is ' . base_convert($higesthblock, 16, 10);


        echo '<br/>There were ' . count($listtransactions) . ' txs in this block, Now checking how many belongs to your exchange<br/>';

        foreach ($listtransactions as $ks => $trans) {
            if (isset($trans['from']) && strtolower($trans['from']) == strtolower($main_account)) {
                //echo "This means , we send some ether to client address for gas do not consider it as deposit";
                continue;
            }
            if (!isset($trans['to'])) continue;//No payee.


            if ($trans['number'] <= 0) {

                if (!$trans['input']) continue;

                //Find if its a token if input != 0x then its a token


                if ($trans['input'] != '0x') {

                    $func = '0x' . substr($trans['input'], 2, 8);
                    if (!in_array(strtolower($trans['to']), $tokens_list) && $func != '0x2228f3a4') {
                        continue;
                    }


                    $to_num = substr($trans['input'], 74);//Quantity
                    $tos = substr($trans['input'], 34, 40);//Reciever

                    if (!$tos) continue;

                    $tos = '0x' . $tos;


                    $num = $Esmart->fromWei($to_num);

                } else {
                    //This is for ethereum it self
                    $tos = $trans['to'];
                    $num = $trans['number'];

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
                        if (M('Myzr')->where(array('txid' => $trans['hash']))->find()) {
                            echo $batch_address . '=>' . $batch_amount . '=>tx for ' . $batch_coin . ' already credited Checking Next' . '<br>';
                            continue;
                        }
                        $data = ['userid' => $batch_uid, 'amount' => $batch_amount, 'coin' => $batch_coin, 'address' => $batch_address, 'hash' => $trans['hash']];
                        $b_info = D('Coin')->depositCoin($data);
                        var_dump($b_info);
                    }
                    continue;
                }
                if (count($listtransactions) < $pcoinb_count) {
                    //exit(__LINE__);
                    $coin_Query = 'SELECT userid as userid FROM `codono_user_coin` WHERE `' . $pcoinb . "` LIKE '" . $tos . "'";
                    $users = M()->query($coin_Query);

                    if (!$users) continue;

                    $user = $users[0];
                } else {

                    $user = 0;
                    foreach ($pcoinb_array as $pcoinb_user) {
                        //var_dump($pcoinb_user);
                        //var_dump($tos);
                        if (isset($pcoinb_user['addr']) && strtolower($pcoinb_user['addr']) == strtolower($tos)) {
                            $coin_Query = 'SELECT userid as userid FROM `codono_user_coin` WHERE `' . $pcoinb . "` LIKE '" . $tos . "'";
                            $users = M()->query($coin_Query);

                            if (!$users) continue;

                            $user = $users[0];

                            //$user=$pcoinb_user['userid'];


                        } else {
                            continue;
                        }
                    }
                    if (!isset($user['userid'])) continue;
                }
                echo '<br/> UserID Found' . $user['userid'] . ' address is ' . $tos . '<br/>';

                $hash_result = $Esmart->eth_getTransactionReceipt($trans['hash']);

                if ($hash_result['status'] != '0x1' && strtolower($hash_result['transactionHash']) != strtolower($trans['hash'])) {
                    echo '<br/>' . $trans['hash'] . ' tx was failed or can not confirm it - adding it to pending <br/>';
                    $this->addPendingTx($trans['hash'], $chain, 'esmart');
                    continue;
                }

                $func = '0x' . substr($trans['input'], 2, 8);
                $flag = false;

                if ($func == '0xa9059cbb') {
                    $token_address_found = $trans['to'];
                    $from = $trans['from'];
                    $to = '0x' . substr(substr($trans['input'], 10, 64), -40);

                    $tokenAddress = '%' . $token_address_found . '%';
                    $coin_info = M('Coin')
                    ->where(array('dj_yh' => array('like', $tokenAddress)))
                    ->select();

                    $decimals = $coin_info[0]['cs_qk'];
                    $amount = hexdec(substr($trans['input'], 74, 64)) / bcpow(10, $decimals);
                    //$contract_value = hexdec(substr($trans['input'],-64));
                    $flag = true;
                    echo '<br/> Function is ' . $func . '<br>';
                } else if ($func == '0x23b872dd') {
                    $token_address_found = $trans['to'];
                    $from = '0x' . substr(substr($trans['input'], 10, 64), -40);
                    $to = '0x' . substr(substr($trans['input'], 74, 64), -40);
                    $amount = hexdec(substr($trans['input'], 138, 64));

                    $flag = true;
                    echo '<br/> Function is ' . $func . '<br>';
                }
                if ($flag) {
                    echo 'Token is ' . $token_address_found;
                    
                    $tokenAddress = '%' . $token_address_found . '%';
                $coin_info = M('Coin')
                ->where(array('dj_yh' => array('like', $tokenAddress)))
                ->select();
                    $coin = $coin_info[0]['name'];
                }

                echo '<br>=======================' . $coin . '=======================<br/>';
                if ($trans['input'] == '0x' && $coin != $pcoin) {
                    echo "<br/>This is $pcoin transaction not for " . $coin . '<br/>';
                    continue;
                }
                if ($trans['input'] != '0x' && $coin == $pcoin) {
                    echo "<br/>This is not for $pcoin<br/>";
                    continue;
                }

                if ($trans['input'] != '0x' && $coin != $pcoin) {
                    $contract_Address_to_look = $trans['to'];
                    if ($token_address_found != $contract_Address_to_look) {
                        echo "<br/>token != $contract_Address_to_look";
                        echo ' <br/>NO not this contract coin' . $coin . '<br/>';
                        continue;
                    }

                    $tokenAddress = '%' . $contract_Address_to_look . '%';
                    $resulto = M('Coin')
                    ->where(array('dj_yh' => array('like', $tokenAddress)))
                    ->select();


                    if (!$resulto[0]['name']) {
                        echo 'This token isnt registered on system';
                        continue;
                    }

                    $coin = $resulto[0]['name'];

                    echo '<br/>func is ' . $func . '</br>';
                    $num = $amount;
                    echo '<br/>Amount is ' . $num . '</br>';

                }
                if ($num <= 0 && $coin == $pcoin) {
                    $num = $Esmart->fromWei($trans['val']);
                }
                echo "amount=$num";

                if ($num <= 0) continue;
                echo 'Star=>';

                if (M('Myzr')->where(array('txid' => $trans['hash']))->find()) {
                    //Already recorded
                    echo $tos . '=>' . $num . '=>tx for ' . $coin . ' already credited Checking Next' . '<br>';
                    continue;
                }
                echo $coin . ' all check ok ' . '<br>';
                $mo = M();
                $mo->startTrans();
                $num = format_num($num, 8);
                $coin = strtolower($coin);
                $coin = $this->findSymbol($coin);
                $rs[] = $mo->table('codono_myzr')->add(array('userid' => $user['userid'], 'type' => 'esmart', 'username' => $tos, 'coinname' => $coin, 'chain' => $chain, 'fee' => 0, 'txid' => $trans['hash'], 'num' => $num, 'mum' => $num, 'addtime' => time(), 'status' => 1));
                $rs[] = $mo->table('codono_user_coin')->where(array('userid' => $user['userid']))->setInc($coin, $num);

                if (check_arr($rs)) {
                    $mo->commit();
                    //$this->deposit_notify($user['userid'], $tos, $coin, $trans['hash'], $num, time());
                    echo $coin . '=>' . $tos . '=>' . $num . ' commit ok' . '<br>';
                } else {
                    $mo->rollback();
                    echo 'Failed Deposit ok' . '<br/>';
                }


            } else {
                $tos = $trans['to'];
                if (count($listtransactions) < $pcoinb_count) {
                    //exit(__LINE__);

                    $coin_Query = 'SELECT userid as userid FROM `codono_user_coin` WHERE `' . $pcoinb . "` LIKE '" . $tos . "'";
                    $users = M()->query($coin_Query);

                    if (!$users) continue;

                    $user = $users[0];
                } else {
                    $user = 0;
                    foreach ($pcoinb_array as $pcoinb_user) {
                        if (strtolower($pcoinb_user['addr']) == strtolower($tos)) {
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
                $parent = $pcoin;
                $pcoin = $this->findSymbol($pcoin);
                if (M('Myzr')->where(array('txid' => $trans['hash']))->find()) {
                    echo $trans['to'] . '=>' . $trans['number'] . '=>' . $pcoin . ' hash had found ,already added!! Checking next!' . '<br>';
                    continue;
                }
                echo $pcoin . ' all check ok ' . '<br>';
                $rs[] = M('myzr')->add(array('userid' => $user['userid'], 'username' => $trans['to'], 'coinname' => $pcoin, 'chain' => $chain, 'fee' => 0, 'txid' => $trans['hash'], 'num' => $trans['number'], 'mum' => $trans['number'], 'addtime' => time(), 'status' => 1, 'type' => 'esmart'));
                $rs[] = M()->table('codono_user_coin')->where(array('userid' => $user['userid']))->setInc($pcoin, $trans['number']);
                //$this->deposit_notify($user['userid'], $tos,$pcoin, $trans['hash'], $trans['number'], time());
                echo $pcoin . '=>' . $trans['to'] . '=>' . $trans['number'] . ' commit ok' . '<br>';
            }

        }

        $pending_tx = $this->esmart_pending();
        print_r($pending_tx);
        //CoinList Loop ends
        G('end');
        echo '<br/>Total Time taken ' . G('begin', 'end') . 's';
    }

    private function esmart_pending()
    {
        $listofpending_txs = M('Unconfirmed')->where(['type' => 'esmart', 'status' => 0])->select();

        if (!empty($listofpending_txs)) {

            foreach ($listofpending_txs as $pending) {

                if ($pending['chain'] && $pending['txid']) {

                    $out = json_decode($this->esmartTrackTx($pending['txid'], $pending['chain']), true);
                    print_r($out);
                    if ($out['msg']) {
                        $unconfirm_update = M('Unconfirmed')->where(array('id' => $pending['id']))->save(array('status' => $out['data']['action'], 'msg' => $out['msg'], 'endtime' => time()));
                    }

                }
            }
        } else {
            echo jsonReturn(0, 'No Pending Records found', []);
        }
    }

    private function esmartTrackTx($txid, $chain)
    {

        if (!preg_match('/^(0x)?[\da-f]{64}$/i', $txid)) {
            return jsonReturn(0, 'Invalid txid');
            exit;
        }


        $isValidCoin = isValidCoin($chain);

        if ($chain == null || !$isValidCoin) {
            return jsonReturn(0, 'Invalid coin');
            exit;
        }

        $amount = 0;
        $token_address_found = 0;
        $all_coinsList = C('coin');
        $tokens_list = $coinList = array();

        foreach ($all_coinsList as $coinex) {
            if ($coinex['type'] == 'esmart' && $coinex['status'] == 1) {

                if ($coinex['tokenof'] == $chain && $coinex['dj_yh'] != '') {
                    $tokens_list[] = strtolower($coinex['dj_yh']);
                }

            }
        }


        $coin_esmart = C('coin')[$chain];

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

        $esmart_config = array(
            'host' => $esmart_details['dj_zj'],
            'port' => $esmart_details['dj_dk'],
            'coinbase' => $esmart_details['codono_coinaddress'],
            'password' => cryptString($esmart_details['dj_mm'], 'd'),
            'contract' => $esmart_details['dj_yh'],
            'rpc_type' => $esmart_details['rpc_type'],
            'public_rpc' => $esmart_details['public_rpc'],
        );
        $Esmart = Esmart($esmart_config);
        $infoTx = $Esmart->eth_getTransactionByHash($txid);

        $listtransactions = array($infoTx);

        foreach ($listtransactions as $ks => $trans) {

            if (isset($trans['from']) && strtolower($trans['from']) == strtolower($main_account)) {
                return jsonReturn(0, 'This was a gas transfer from our exchange account only');
                continue;
            }

            if (!isset($trans['to'])) {
                return jsonReturn(0, 'No payee [1863]', ['action' => 2, 'txid' => $txid]);
                continue;//No payee.
            }

            if ($trans['value'] == '0x0' || $trans['value'] == '0x' || $trans['value'] == '0') {

                if (!$trans['input']) {
                    return jsonReturn(0, 'Invalid tx', ['action' => 2]);
                    continue;
                }

                //Find if its a token if input != 0x then its a token


                if ($trans['input'] != '0x') {
                    $func = '0x' . substr($trans['input'], 2, 8);
                    if (!in_array(strtolower($trans['to']), $tokens_list) && $func != '0x2228f3a4') {
                        return jsonReturn(0, 'No token found', ['action' => 2]);
                        continue;
                    }


                    $to_num = substr($trans['input'], 74);//Quantity
                    $tos = substr($trans['input'], 34, 40);//Reciever

                    if (!$tos) {
                        return jsonReturn(0, 'No such sender', ['action' => 2]);
                        continue;
                    }

                    $tos = '0x' . $tos;


                    $num = $Esmart->fromWei($to_num);

                } else {
                    //This is for ethereum it self
                    $tos = $trans['to'];
                    $num = $trans['value'];

                }

                if ($trans['input'] != '0x' && $func == '0x2228f3a4') {
                    $batch_decodes = $Esmart->payoutERC20Batch_decode($trans['input']);

                    foreach ($batch_decodes as $batch_decode) {
                        //check if erc20 contract is listed on exchange or not
                        if (!in_array(strtolower($batch_decode['contract']), $tokens_list)) {
                            continue;
                        }
                        $batch_address = $batch_decode['address'];
                        $coin_Query = 'SELECT userid FROM `codono_user_coin` WHERE `' . $pcoinb . "` LIKE '" . $batch_address . "'";
                        $users = M()->query($coin_Query);
                        if (!$users) {
                            return jsonReturn(0, 'No user found', ['action' => 2]);
                            continue;
                        }
                        $batch_uid = $users[0]['userid'];
                        $batch_contract_info = $this->findTokenByContract($batch_decode['contract']);
                        $batch_decimal = $batch_contract_info['cs_qk'];
                        $batch_coin = $batch_contract_info['name'];
                        $batch_amount = hexdec($batch_decode['bal_hex']) / bcpow(10, $batch_decimal);
                        //Already recorded
                        if (M('Myzr')->where(array('txid' => $trans['hash']))->find()) {
                            return jsonReturn(1, 'already credited', ['action' => 1]);
                            continue;
                        }
                        $data = ['userid' => $batch_uid, 'amount' => $batch_amount, 'coin' => $batch_coin, 'address' => $batch_address, 'hash' => $trans['hash']];
                        $b_info = D('Coin')->depositCoin($data);
                        echo jsonReturn(1, 'deposited', ['action' => 1, 'info' => $b_info]);
                    }
                    continue;
                }

                if (count($listtransactions) < $pcoinb_count) {

                    $coin_Query = 'SELECT userid as userid FROM `codono_user_coin` WHERE `' . $pcoinb . "` LIKE '" . $tos . "'";
                    $users = M()->query($coin_Query);

                    if (!$users) {
                        return jsonReturn(0, 'no user found', ['action' => 2]);
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
                                return jsonReturn(0, 'no user found', ['action' => 2]);
                                continue;
                            }

                            $user = $users[0];

                            //$user=$pcoinb_user['userid'];


                        } else {
                            return jsonReturn(0, 'no tx found found', ['action' => 2]);
                            continue;
                        }
                    }
                    if (!isset($user['userid'])) {
                        return jsonReturn(0, 'no user found', ['action' => 2]);
                        continue;
                    }
                }

                $hash_result = $Esmart->eth_getTransactionReceipt($trans['hash']);

                if ($hash_result['status'] != '0x1' && strtolower($hash_result['transactionHash']) != strtolower($trans['hash'])) {
                    $this->addPendingTx($trans['hash'], $chain, 'esmart');
                    return jsonReturn(0, 'transaction is failed or still pending', ['action' => 0]);
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
                    return jsonReturn(0, 'Invalid tx [4]', ['action' => 2]);
                    continue;
                }

                if ($trans['input'] == '0x' && $coin != $pcoin) {
                    return jsonReturn(0, 'Invalid tx [5]', ['action' => 2]);
                    continue;
                }

                if ($trans['input'] != '0x' && $coin != $pcoin) {
                    $contract_Address_to_look = $trans['to'];
                    if ($token_address_found != $contract_Address_to_look) {
                        return jsonReturn(0, 'No token found [6]', ['action' => 2]);
                        continue;
                    }
                    $token_query = "SELECT name,cs_qk as decimals FROM `codono_coin` WHERE `dj_yh` LIKE '%" . $contract_Address_to_look . "%'";

                    $resulto = M()->query($token_query);

                    if (!$resulto[0]['name']) {

                        return jsonReturn(0, 'This token deposited is not registered on exchange [6]', ['action' => 2]);
                        continue;
                    }

                    $coin = $resulto[0]['name'];

                    $num = $amount;

                }
                if ($num <= 0 && $coin == $pcoin) {
                    $num = $Esmart->fromWei($trans['val']);
                }

                if ($num <= 0) continue;


                if (M('Myzr')->where(array('txid' => $trans['hash']))->find()) {
                    //Already recorded
                    return jsonReturn(0, 'Transaction was already deposited! [2054]', ['action' => 1]);
                    continue;
                }

                $mo = M();
                $mo->startTrans();

                $num = format_num($num, 8);
                $coin = $this->findSymbol($coin);

                $rs[] = $mo->table('codono_myzr')->add(array('userid' => $user['userid'], 'type' => 'esmart', 'username' => $tos, 'coinname' => $coin, 'chain' => $chain, 'fee' => 0, 'txid' => $trans['hash'], 'num' => $num, 'mum' => $num, 'addtime' => time(), 'status' => 1));
                $rs[] = $mo->table('codono_user_coin')->where(array('userid' => $user['userid']))->setInc($coin, $num);

                if (check_arr($rs)) {
                    $mo->commit();
                    //$this->deposit_notify($user['userid'], $tos, $coin, $trans['hash'], $num, time());

                    return jsonReturn(1, 'Transaction was found and deposited', ['action' => 1]);
                } else {
                    $mo->rollback();
                    return jsonReturn(1, 'Transaction was found but could not be deposited', ['action' => 0]);
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

                if (M('Myzr')->where(array('txid' => $trans['hash']))->find()) {

                    return jsonReturn(0, 'Already deposited [08]', ['action' => 1]);
                    continue;
                }
                $addbalance = $Esmart->fromWei($trans['value']);

                M('myzr')->add(array('userid' => $user['userid'], 'username' => $trans['to'], 'coinname' => $pcoin, 'chain' => $chain, 'fee' => 0, 'txid' => $trans['hash'], 'num' => $addbalance, 'mum' => $addbalance, 'addtime' => time(), 'status' => 1, 'type' => 'esmart'));
                $rs[] = M()->table('codono_user_coin')->where(array('userid' => $user['userid']))->setInc($pcoin, $addbalance);
                deposit_notify($user['userid'], $tos, $pcoin, $trans['hash'], $addbalance, time());
                return jsonReturn(1, 'Transaction was found and deposited', ['action' => 1]);
            }

        }
    }

    private function addPendingTx($txid, $chain, $type = '')
    {
        $tx = M('Unconfirmed')->where(['chain' => $chain, 'txid' => $txid])->find();
        if (!$tx) {
            return M('Unconfirmed')->add([
                'chain' => $chain,
                'txid' => $txid,
                'type' => $type,
                'addtime' => time(),
                'status' => 0
            ]);
            //do add tx to pending list
        } else {
            return false;
        }
    }

    /*
    This is manual url to run to move users BNB to main account  [Run it always after running esmart_token_to_main
     https://{YOURSITE}/Home/Queue2/esmart_to_main/securecode/{CRONKEY}
    */
    public function esmart_to_main($coin)
    {
        cronLastRun(__FUNCTION__);
        $coin = I('get.coin', '','trim');
        
        $coinname = strtolower($coin);

        $main_address = C('coin')[$coinname]['codono_coinaddress'];

        if (C('coin')[$coinname]['type'] != 'esmart') {
            die("<span style='color:red'>Warning:This isnt esmart type coinname :<b>$coinname</b> or there is configuration issues. Please check");
        }

        if (!$main_address) {
            die("<span style='color:red'>Warning:You have not defined Main Account in <b>$coinname</b> Please fix it first");
        }
        if (C('coin')[$coinname]['dj_yh']) {
            die("<span style='color:red'>Warning:This could be an Token Use different cron for moving");
        }
        if ($_GET['agree'] != 'yes') {
            $agree_url = $_SERVER['REQUEST_URI'] . '/agree/yes';
            die("<span style='color:red'>Warning:This tool will move all your <b>$coinname</b> funds to main account $main_address ,  <a href='$agree_url'> Do you agree?</a></span>");
        }

        $condition['status'] = 1;
        $condition['shifted_to_main'] = 0;
        $condition['type'] = 'esmart';
        $condition['coinname'] = $coinname;
        $transactions = M('Myzr')->where($condition)->order('id asc')->limit(50)->select();

        echo 'There would be ' . count($transactions) . ' attempts</br>';
        foreach ($transactions as $tx) {

            $from = $tx['username'];
            $password = ETH_USER_PASS;
            if (!$password) {
                echo $password . 'No pass found for ' . $from . '<br/>';
                continue;
            }
            echo 'Futher:' . $tx['username'];
            $CoinInfo = C('coin')[$coinname];
            $heyue = $ContractAddress = $CoinInfo['dj_yh'];//Contract Address
            $dj_password = $CoinInfo['dj_mm'];
            $dj_address = $CoinInfo['dj_zj'];
            $dj_port = $CoinInfo['dj_dk'];
            $decimals = $CoinInfo['cs_qk'];

            $esmart_config = array(
                'host' => $CoinInfo['dj_zj'],
                'port' => $CoinInfo['dj_dk'],
                'coinbase' => $CoinInfo['codono_coinaddress'],
                'password' => cryptString($CoinInfo['dj_mm'], 'd'),
                'contract' => false,
                'rpc_type' => $CoinInfo['rpc_type'],
                'public_rpc' => $CoinInfo['public_rpc'],
            );
            $Esmart = Esmart($esmart_config);


            $ubal = $this->floordec($Esmart->balanceOf($tx['username']), 8);
            echo "<br/>Moving $ubal to main account<br/>";
            $sendrs = $Esmart->emptyEthOfAccount($tx['username'], $ubal, $password);

            if (!$sendrs['error']) {
                echo '<pre>';
                var_export($sendrs);
                echo '</pre>';
                $this->markAsMoved($tx['id']);
            } else {
                echo '<br/>there were issues from: ' . $from . '<br/>';
                print_r($sendrs);
                continue;
            }

            //Save to myzr table that
        }
        echo 'Cron Ended';
        exit;
    }

    private function floordec($value, $precision = 2)
    {

        $mult = pow(10, $precision); // Can be cached in lookup table
        return floor($value * $mult) / $mult;
    }

    public function esmart_move_all_tokens_to_main()
    {
        cronLastRun(__FUNCTION__);
        $condition['status'] = 1;
        $condition['shifted_to_main'] = 0;
        $condition['type'] = 'esmart';
        $transactions = M('Myzr')->where($condition)->order('id desc')->limit(50)->select();
        $unique_address = array();
        $extra_info['total_tx'] = count($transactions);
        foreach ($transactions as $tx) {
            $coinname = $tx['coinname'];
            $main_address = C('coin')[$coinname]['codono_coinaddress'];
            if (C('coin')[$coinname]['type'] != 'esmart' || !C('coin')[$coinname]['dj_yh']) {
                $extra_info['tx'] = $tx['txid'];
                $extra_info['type'] = C('coin')[$coinname]['type'];
                $extra_info['contract'] = C('coin')[$coinname]['dj_yh'];
                //     echo jsonReturn(0,"This isnt erc20 type coin :$coinname or there is configuration issues. Please check",$extra_info);
                continue;
            }
            if (!$main_address) {
                $extra_info['tx'] = $tx['txid'];
                //   echo jsonReturn(0,"Warning:There are issues with your mainaccount config in $coinname Please fix it first",$extra_info);
                continue;
            }
            if (in_array($tx['username'], $unique_address)) {
                $this->markAsMoved($tx['id']);
                continue;
            }
            $unique_address[] = $tx['username'];
            $from = $tx['username'];
            $password = ETH_USER_PASS;//Defined in pure_config
            if (!$password) {
                $extra_info['tx'] = $tx['txid'];
                echo jsonReturn(0, "$password is wrong or No pass found for " . $from, $extra_info);
                continue;
            }
            echo 'Futher:<br/>Sweeping Tokens  from ' . $tx['username'] . '<br/>';
            $CoinInfo = M('Coin')->where(array('name' => $tx['coinname']))->find();
            $ContractAddress = $CoinInfo['dj_yh'];//Contract Address
            $dj_decimal = $CoinInfo['cs_qk'];

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

            $token_balance = NumToStr(format_num($Esmart->balanceOfToken($tx['username'], $ContractAddress, $dj_decimal), 8));


            if (format_num($token_balance) < 0.000000001) {
                $extra_info['tx'] = $tx['txid'];
                echo jsonReturn(0, $coinname . ': Balance is low, cant transfer:' . $token_balance, $extra_info);
                $this->markAsMoved($tx['id']);
                continue;
            }

            $last_status = $tx['username'] . ' has balance of ' . $token_balance . $coinname . ' moving to main account is ' . $CoinInfo['codono_coinaddress'] . '<br/>';

            $sendrs = $Esmart->transferTokentoCoinbase($tx['username'], $token_balance, $password, $ContractAddress, $dj_decimal);
            $extra_info['tx'] = $tx['txid'];
            $extra_info['info'] = json_decode($sendrs, true);
            if (!$sendrs['error']) {
                $this->markAsMoved($tx['id']);

                echo jsonReturn(1, 'Moved see output:', $extra_info);
            } else {
                echo jsonReturn(0, 'Failed! see output:', $extra_info);
            }

            //Save to myzr table that
        }
    }

    /*
	Run this Cron manually or with extreme caution This may cost double or more gas , and moves tokens from user accounts to your main account
		This is manual url to run to move users BEP20 tokens to main account  [Run it always after before esmart_to_main
	 https://{YOURSITE}/Home/Queue2/esmart_token_to_main/coinname/{tokenname}/securecode/{CRONKEY}
	*/
    public function esmart_token_to_main()
    {
        cronLastRun(__FUNCTION__);
        $coinname = I('get.coinname', '','trim');
        $bypass = I('get.bypass', 0,'intval');
		$parent_coin=C('coin')[$coinname]['tokenof'];
        $main_address = C('coin')[$parent_coin]['codono_coinaddress'];
        if (C('coin')[$coinname]['type'] != 'esmart' || !C('coin')[$coinname]['dj_yh']) {
            if (C('coin')[$coinname]['dj_yh']) {
                echo 'This coin should be type of esmart';
            }
            die("<span style='color:#ff0000'>Warning:This isnt erc20 type coin :<b>$coinname</b> or there is configuration issues. Please check");
        }
        if (!$main_address) {
            die("<span style='color:red'>Warning:There are issues with your mainaccount config in <b>$coinname</b> Please fix it first");
        }
        if ($_GET['agree'] != 'yes') {
            $agree_url = $_SERVER['REQUEST_URI'] . '/agree/yes';
            die("<span style='color:red'>Warning:This tool will move all your <b>$coinname</b> funds to main account $main_address ,  <a href='$agree_url'> Do you agree?</a></span>");
        }
		$og_coin=$coinname;			 
        $parent = $this->findSymbol($coinname);
        $condition['status'] = 1;
        if($bypass!=0){
			$condition['shifted_to_main'] = 0;
		}
        $condition['type'] = 'esmart';
        $condition['chain']=C('coin')[$coinname]['tokenof'];
        $condition['coinname'] = $parent;
		
        $transactions = M('Myzr')->where($condition)->order('id asc')->limit(50)->select();
        $unique_address = array();
        echo 'Contract Address:' . C('coin')[$coinname]['dj_yh'] . '<br/>';
        foreach ($transactions as $tx) {
            $coinname = $tx['coinname'];
            if (in_array($tx['username'], $unique_address)) {
                $this->markAsMoved($tx['id']);
                continue;
            }
            $unique_address[] = $tx['username'];
            $from = $tx['username'];
            $password = ETH_USER_PASS;//Defined in pure_config
            if (!$password) {
                echo $password . 'No pass found for ' . $from . '<br/>';
                continue;
            }
            echo 'Futher:<br/>Sweeping Tokens  from ' . $tx['username'] . '<br/>';
            $CoinInfo = M('Coin')->where(array('name' => $og_coin))->find();
            $ContractAddress = $CoinInfo['dj_yh'];//Contract Address
            $dj_decimal = $CoinInfo['cs_qk'];

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

            $token_balance = NumToStr(format_num($Esmart->balanceOfToken($tx['username'], $ContractAddress, $dj_decimal), 8));


            if (format_num($token_balance) < 0.000000001) {
                echo $coinname . 'Balance is low, cant transfer:' . $token_balance . '<br/>';
                $this->markAsMoved($tx['id']);
                continue;
            }

            echo $tx['username'] . ' has balance of ' . $token_balance . $coinname . ' moving to main account is ' . $CoinInfo['codono_coinaddress'] . '<br/>';

            $sendrs = $Esmart->transferTokentoCoinbase($tx['username'], $token_balance, $password, $ContractAddress, $dj_decimal);

            if ($sendrs) {
                echo "<br/>Moved see output below<br/><div style='color:green'> <pre>";
                print_r($sendrs);
                echo '</pre></div>';
                $this->markAsMoved($tx['id']);

            } else {
                echo "<br/>Failed: ourput below<br/><div style='color:red'> <pre>";
                print_r($sendrs);
                echo '</pre></div>';
            }

            //Save to myzr table that
        }
        echo 'Cron Ended';
        exit;
    }


    private function markAsMoved($id)
    {
        $status = M('Myzr')->where(array('id' => $id))->save(array('shifted_to_main' => 1));

        if ($status) {
            return 1;
        } else {
            return 0;
        }

    }

    /**
     * @param $address
     * @param $coin
     * @return int
     */
    private function markAsMovedAddress($address, $coin): int
    {
        $status = M('Myzr')->where(['username' => $address,'coinname'=>$coin])->save(['shifted_to_main' => 1]);

        if ($status) {
            return 1;
        } else {
            return 0;
        }

    }
    //esmart wallet cron Ends

    /*XRP QUEUE STARTS*/

    public function xrpdeposit()
    {
        cronLastRun(__FUNCTION__);
        $xrpData = C('coin')['xrp'];
        if ($xrpData) {
            $xrpClient = XrpClient($xrpData['dj_zj'], $xrpData['dj_dk'], $xrpData['codono_coinaddress'], $xrpData['dj_mm']);
            $history = $xrpClient->history();

            if (isset($history['result']) && $history['result']['status'] == 'success') {
                echo 'Recent Txs:' . count($history['result']['transactions']);
                if (count($history['result']['transactions']) >= 1) {
                    foreach ($history['result']['transactions'] as $k => $v) {

                        //If it is not the transfer-in address, exit
                        if ($v['tx']['Destination'] != $xrpData['codono_coinaddress']) {
                            continue;
                        }

                        //Don’t transfer if you don’t fill in the tag
                        if (!isset($v['tx']['DestinationTag'])) {
                            continue;
                        }

                        $xrpb = M('user_coin')->where(['xrp_tag' => $v['tx']['DestinationTag']])->find();
                        if ($xrpb) {
                            $hasIncome = M('myzr')->where(['userid' => $xrpb['userid'], 'username' => $v['tx']['Account'],
                                'coinname' => 'xrp', 'txid' => $v['tx']['hash']])->find();
                            if ($hasIncome) {
                                continue;
                            } else {

                                //Check whether the hash exists
                                $xrpTx = $xrpClient->tx($v['tx']['hash']);

                                if ($xrpTx['result']['status'] != 'success') {
                                    continue;
                                }

                                if (!$xrpTx['result']['validated']) {
                                    continue;
                                }

                                $mo = M();
                                $mo->startTrans();
                                $x_amt = bcdiv($v['tx']['Amount'], 1000000, 8);
                                $deposit_amount = $x_amt * 1;
                                $from = $v['tx']['Account'];
                                $hash = $v['tx']['hash'];
                                echo "hash found $hash<br/>";
                                try {
                                    M('user_coin')->where(['userid' => $xrpb['userid']])->setInc('xrp', $deposit_amount);
                                    $rs = M('myzr')->add([
                                        'userid' => $xrpb['userid'],
                                        'username' => $from,
                                        'coinname' => 'xrp',
                                        'txid' => $hash,
                                        'num' => $deposit_amount,
                                        'mum' => $deposit_amount,
                                        'addtime' => time(),
                                        'status' => 1
                                    ]);
                                    echo 'added';
                                    $mo->commit();
                                } catch (Exception $e) {
                                    $mo->rollback();
                                }
                            }
                        } else {
                            continue;
                        }
                    }
                } else {
                    return false;
                }
            } else {
                return false;
            }
        } else {
            return false;
        }
        return false;
    }
    /* XRP Deposit Cron Ends*/
    /**
     * @param array $trans
     * @param $coin
     * @return void
     */
    private function sub_mark_withdrawn(array $trans, $coin): void
    {
        if ($trans['category'] == 'send') {
            echo 'start send do:' . "\n";

            if (3 <= $trans['confirmations']) {
                $myzc = M('Myzc')->where(array('txid' => $trans['txid']))->find();

                if ($myzc) {
                    if ($myzc['status'] == 0) {
                        M('Myzc')->where(array('txid' => $trans['txid']))->save(array('status' => 1));
                        echo $trans['amount'] . L('Successful Withdrawal') . $coin . ' Coins OK';
                    }
                }
            }
        }
    }
    private function isValidEthereumAddress($address) {
        // Check if the address starts with '0x' and is 42 characters long
        if (preg_match('/^0x[a-fA-F0-9]{40}$/', $address)) {
            return true;
        }
        return false;
    }
    
}
	