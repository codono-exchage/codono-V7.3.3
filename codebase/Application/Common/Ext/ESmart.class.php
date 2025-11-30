<?php

namespace Common\Ext;
class ESmart
{

    private $host;
    private $port;
    private $version = '2.0';
    private $coinbase; //main account
    private $coinbasePwd;
    private $transferGas = '0.0005';
    private $ContractAddress;
    public $base = 1000000000000000000;//1e18 wei  Base unit:
    private $explorer_api_key = "NHI6SZXEUIEAQ3EJ3N4UQXC4FNK277SXDX";
    private $node_type;  // Keep light if node has not downloaded states or synced properly
    private $public_rpc;

    function __construct($esmart_config)
    {
        $this->host = $esmart_config['host'];
        $this->port = $esmart_config['port'];
        $this->coinbase = $esmart_config['coinbase']; //main account
        $this->coinbasePwd = $esmart_config['password'];
        $this->ContractAddress = $esmart_config['contract'];
        $this->public_rpc = $esmart_config['public_rpc'];
        $this->node_type = $esmart_config['rpc_type'];
    }

    private function checkRpcResult($data)
    {
        if (empty($data['error']) && !empty($data['result'])) {
            $result = $data['result'];
        } else {
            $result = $data;
        }
        return $result;
    }

    public function eth_accounts()
    {
        return $this->request(__FUNCTION__);
    }

    private function request($method, $params = array())
    {
        $data = array();
        $data['jsonrpc'] = $this->version;
        $data['id'] = 999999;
        // $data['id'] = $this->rpcId + 1;
        $data['method'] = $method;
        $data['params'] = $params;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->host);
        curl_setopt($ch, CURLOPT_PORT, $this->port);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        $ret = curl_exec($ch);
        curl_close($ch);
        //Return result
        if ($ret) {
            return $this->checkRpcResult(json_decode($ret, true));
        } else {
            return false;
        }
    }

    private function requestPublic($method, $params = array())
    {


        $data = array();
        $data['jsonrpc'] = $this->version;
        $data['id'] = 99999;
        //$data['id'] = $this->rpcId + 1;
        $data['method'] = $method;
        $data['params'] = $params;

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $this->public_rpc,
            CURLOPT_RETURNTRANSFER => true,
            // CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_POST => true,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json'
            ),
        ));

        $ret = curl_exec($curl);

        curl_close($curl);
        //Return result
        if ($ret) {
            return $this->checkRpcResult(json_decode($ret, true));
        } else {
            return false;
        }
    }

    private function eth_gasPrice()
    {
        if ($this->node_type == 'light') {
            return $this->requestPublic(__FUNCTION__);
        } else {
            return $this->request(__FUNCTION__);
        }
    }

    public function eth_getBalance($address)
    {
        if ($this->node_type == 'light') {
            return $this->requestPublic(__FUNCTION__, array($address, 'latest'));
        } else {
            return $this->request(__FUNCTION__, array($address, 'latest'));
        }
    }


    public function eth_getTransactionByHash($txHash)
    {
        if ($this->node_type == 'light') {
            return $this->requestPublic(__FUNCTION__, array($txHash));
        } else {
            return $this->request(__FUNCTION__, array($txHash));
        }
    }

    private function eth_sendTransaction($from, $to, $gas, $gasPrice, $value, $data)
    {
        $nonce = $this->eth_getTransactionCount($from);
        $transVue = array();
        $transVue['from'] = $from;
        $transVue['to'] = $to;
        $transVue['gas'] = $gas;
        $transVue['gasPrice'] = $gasPrice;
        $transVue['value'] = $value;
        $transVue['data'] = $data;
        $transVue['nonce'] = $nonce;
        $signed = $this->eth_signTransaction($transVue);
        if ($signed['error']['code']) {

            return $signed;
        }
        return $this->eth_sendRawTransaction($signed['raw']);
    }

    private function eth_signTransaction($data)
    {
        return $this->request(__FUNCTION__, array($data));
    }

    private function eth_getTransactionCount($address)
    {
        if ($this->node_type == 'light') {
            return $this->requestPublic(__FUNCTION__, array($address, 'latest'));
        } else {
            return $this->request(__FUNCTION__, array($address, 'latest'));
        }
    }

    private function eth_sendRawTransaction($data)
    {
        if ($this->node_type == 'light') {
            return $this->requestPublic(__FUNCTION__, array($data));
        } else {
            return $this->request(__FUNCTION__, array($data));
        }
    }

    private function eth_call($from, $to, $gas, $gasPrice, $value, $data)
    {
        $callVue = array();
        $callVue['from'] = $from;
        $callVue['to'] = $to;
        if ($gas != '0x0') {
            $callVue['gas'] = $gas;
        }
        if ($gasPrice != '0x0') {
            $callVue['gasPrice'] = $gasPrice;
        }
        if ($value != '0x0') {
            $callVue['value'] = $value;
        }
        $callVue['data'] = $data;
        if ($this->node_type == 'light') {
            return $this->requestPublic(__FUNCTION__, array($callVue, 'latest'));
        } else {
            return $this->request(__FUNCTION__, array($callVue, 'latest'));
        }
    }

    private function eth_estimateGas($from, $to, $gas, $gasPrice, $value, $data)
    {

        $callVue = array();
        $callVue['from'] = $from;
        $callVue['to'] = $to;
        $callVue['gas'] = $gas;
        $callVue['value'] = $value;
        $callVue['data'] = $data;
        if ($this->node_type == 'light') {
            return $this->requestPublic(__FUNCTION__, array($callVue));
        } else {
            return $this->request(__FUNCTION__, array($callVue));
        }
    }

    private function eth_estimateGas2($from, $to, $gas, $gasPrice, $value, $data)
    {

        $callVue = array();
        $callVue['from'] = $from;
        $callVue['to'] = $to;
        $callVue['gas'] = $gas;
        //$callVue['gasPrice'] = $gasPrice;
        $callVue['value'] = $value;
        $callVue['data'] = $data;
        if ($this->node_type == 'light') {
            return $this->requestPublic('eth_estimateGas', array($callVue));
        } else {
            return $this->request('eth_estimateGas', array($callVue));
        }
    }

    private function personal_unlockAccount($account, $password, $duration = 20)
    {
        $params = array(
            $account,
            $password,
            $duration
        );
        return $this->request(__FUNCTION__, $params);
    }

    public function personal_newAccount($password)
    {
        if (is_string($password) && strlen($password) > 0) {
            return $this->request(__FUNCTION__, array($password));
        }
        return false;
    }


    public function checkAddress($address): bool
    {
        $result = false;
        if (ctype_alnum($address)) {

            if (strlen($address) == 42 && substr($address, 0, 2) == '0x') {

                $result = true;
            }
        }
        return $result;
    }

    //New account
    public function createWallet($password)
    {
        $result = false;
        $wallet = null;
        $wallet['password'] = $password();
        $wallet['address'] = $this->personal_newAccount($wallet['password']);
        if ($wallet['address'] && is_string($wallet['address'])) {

            if (strlen($wallet['address']) == 42) {

                $result = $wallet;

            }
        }
        return $result;
    }

    /* Coinbase Token Balance*/

    public function balanceofCoinBase()
    {
        return $this->balanceOf($this->coinbase);
    }

    public function balanceOfToken($address, $contractAddress = "", $decimal = 0)
    {
        $result = 0;
        if ($this->checkAddress($address)) {
            $dataCode = '0x70a08231000000000000000000000000' . substr($address, 2, 40);
            $data = $this->eth_call($address, $contractAddress, '0x0', '0x0', '0x0', $dataCode);
            if ($data && !is_array($data)) {
                $result = bcdiv(number_format(hexdec($data), 0, '.', ''), number_format(pow(10, $decimal), 0, '.', ''), 2);
            }
        }
        return $result;
    }

    public function baloftoken($address, $contractAddress = "")
    {

        $callVue['from'] = $address;
        $callVue['to'] = $contractAddress;
        $short = substr($contractAddress, 2);
        $callVue['data'] = '0x70a08231000000000000000000000000' . $short;
        if ($this->node_type == 'light') {
            return $this->requestPublic(__FUNCTION__, array($callVue, 'latest'));
        } else {
            return $this->request(__FUNCTION__, array($callVue, 'latest'));
        }
    }

    public function balanceOf($address)
    {
        $result = 0;
        if ($this->checkAddress($address)) {
            $data = $this->eth_getBalance($address);
            if ($data && !is_array($data)) {
                $result = bcdiv(number_format(hexdec($data), 0, '.', ''), number_format(1000000000000000000, 0, '.', ''), 18);
            }
        }
        return $result;
    }


    public function transferToken($toAddress, $value, $contractAddress = "", $decimal = 8)
    {
        $result = false;
        if ($this->checkAddress($toAddress) && is_numeric($value)) {

            $ethBalance = $this->balanceOf($this->coinbase);

            $gasPriceHex = $this->eth_gasPrice();


            $data = array();
            $tokenBalance = $this->balanceOfToken($this->coinbase, $contractAddress, $decimal);

            $tokenEnough = bcsub($tokenBalance, $value, 2) >= 0;

            if ($tokenEnough) {

                $data['to'] = !$contractAddress ? $this->ContractAddress : $contractAddress;
                $data['value'] = '0x0';
                $data['data'] = '0xa9059cbb000000000000000000000000' . substr($toAddress, 2, 40);
                $new_val = bcmul($value, bcpow("10", strval($decimal), 0), 0);
                $valueHex = base_convert($new_val, 10, 16);

                $zeroStr = '';
                for ($i = 1; $i <= (64 - strlen($valueHex)); $i++) {
                    $zeroStr .= '0';

                }
                $data['data'] = $data['data'] . $zeroStr . $valueHex;

                $gasLimitHex = $this->eth_estimateGas($this->coinbase, $data['to'], '0x0', '0x0', $data['value'], $data['data']);

                $result = $this->sub_transferFrom($ethBalance, $gasPriceHex, $gasLimitHex, $data, $result);
            }
        }
        return json_encode($result);
    }

    public function transferTokentoCoinbase($fromAddress, $value, $password, $contractAddress, $decimal = 8)
    {

        $result = false;
        if ($this->checkAddress($fromAddress) && is_numeric($value)) {
            $ethBalance = $this->balanceOf($fromAddress);

            $gasPriceHex = $this->eth_gasPrice();

            $data = array();
            //$tokenBalance = $this->balanceOfToken($fromAddress);
            //$tokenEnough = bcsub($tokenBalance, $value, 2) >= 0;

            if ($value) {

                $data['to'] = $contractAddress;
                //  $data['value'] = '0x' . base_convert(bcmul($amount, number_format(100, 0, '.', ''), 0), 10, 16);//'0x0';
                $data['value'] = '0x0';
                //Encode the transaction to send to the proxy contract

                $data['data'] = '0xa9059cbb000000000000000000000000' . substr($this->coinbase, 2, 40);
                $new_val = bcmul($value, bcpow("10", strval($decimal), 0), 0);
                //$valueHex = base_convert(bcmul($value, number_format(100, 0, '.', ''), 0), 10, 16);
                $valueHex = base_convert($new_val, 10, 16);
                $zeroStr = '';
                for ($i = 1; $i <= (64 - strlen($valueHex)); $i++) {
                    $zeroStr .= '0';
                }
                $data['data'] = $data['data'] . $zeroStr . $valueHex;

                $gasLimitHex = $this->eth_estimateGas2($fromAddress, $data['to'], '0x0', $gasPriceHex, $data['value'], $data['data']);

                $eth_gas_required = bcsub(bcdiv(bcmul(hexdec($gasPriceHex), hexdec($gasLimitHex)), bcpow(10, 18), 18), 0, 18);

                $check_Condition = bcsub(format_num($ethBalance), bcdiv(bcmul(format_num(hexdec($gasPriceHex)), format_num(hexdec($gasLimitHex))), bcpow(10, 18), 18), 18);

                $new_condition = bcsub(format_num($ethBalance), format_num($eth_gas_required), 18);
                $gas_xfer = false;
                if ($check_Condition < 0) {

                    $gas_xfer = $this->transferFromCoinbase($fromAddress, floatval($eth_gas_required));

                }
                if (is_array($gasLimitHex)) {
                    echo "<br/>There are issues with gasLimitHex<br/>";
                    print_r($gasLimitHex);

                    return false;
                }
                if ($new_condition >= 0) {

                    $unlockStatus = $this->personal_unlockAccount($fromAddress, $password);

                    if ($unlockStatus) {
                        echo "<br/>unlocked<br/>";
                        $result = $this->eth_sendTransaction($fromAddress, $data['to'], $gasLimitHex, $gasPriceHex, $data['value'], $data['data']);
                        echo "Tried to send<br/>";
                        print_r(array($fromAddress, $data['to'], $gasLimitHex, $gasPriceHex, $data['value'], $data['data']));
                    } else {
                        echo "<br/>Could not be unlocked";

                    }
                } else {
                    $resp['error'] = 'Low ETH Balance:' . $ethBalance . 'Minimum required for this tx is ' . $eth_gas_required;
                    $resp['action'] = $gas_xfer;
                    return json_encode($resp);
                }
            }
        }
        return $result;
    }

    public function transferGas($toAddress)
    {
        $result = false;
        if ($this->checkAddress($toAddress)) {
            $ethBalance = $this->balanceOf($this->coinbase);
            $gasPriceHex = $this->eth_gasPrice();

            $data = array();
            $ethBalance = bcsub($ethBalance, $this->transferGas, 18);
            $tokenEnough = $ethBalance >= 0;
            if ($tokenEnough) {

                $data['to'] = $toAddress;
                $data['value'] = '0x' . base_convert(bcmul($this->transferGas, number_format(1000000000000000000, 0, '.', ''), 0), 10, 16);
                $data['data'] = '0x';

                $gasLimitHex = $this->eth_estimateGas($this->coinbase, $data['to'], '0x0', $gasPriceHex, $data['value'], $data['data']);
                if (bcsub($ethBalance, bcdiv(bcmul(hexdec($gasPriceHex), hexdec($gasLimitHex)), number_format(1000000000000000000, 0, '.', ''), 18), 18) >= 0) {

                    $unlockStatus = $this->personal_unlockAccount($this->coinbase, $this->coinbasePwd);
                    if ($unlockStatus) {

                        $result = $this->eth_sendTransaction($this->coinbase, $data['to'], $gasLimitHex, $gasPriceHex, $data['value'], $data['data']);
                    }
                }
            }
        }
        return $result;
    }

    public function transferFromCoinbase($toAddress, $amount)
    {
        $amount = format_num($amount);
        $result = false;
        if ($this->checkAddress($toAddress) && is_numeric($amount)) {
            $ethBalance = $this->balanceOf($this->coinbase);


            $gasPriceHex = $this->eth_gasPrice();

            $data = array();
            $ethBalance = bcsub($ethBalance, $amount, 18);
            $tokenEnough = $ethBalance >= 0;

            if ($tokenEnough) {

                $data['to'] = $toAddress;
                $data['value'] = '0x' . base_convert(bcmul($amount, number_format(1000000000000000000, 0, '.', ''), 0), 10, 16);
                $data['data'] = '0x';

                $gasLimitHex = $this->eth_estimateGas($this->coinbase, $data['to'], '0x0', $gasPriceHex, $data['value'], $data['data']);

                $result = $this->sub_transferFrom($ethBalance, $gasPriceHex, $gasLimitHex, $data, $result);
            }
        }
        return json_encode($result);
    }

    public function emptyEthOfAccount($from, $amount, $pwd)
    {
        $result = false;
        if ($this->checkAddress($from) && is_numeric($amount)) {

            $ethBalance = $this->balanceOf($from);
            $gasPriceHex = $this->eth_gasPrice();

            $data = array();
//            $ethBalance = bcsub($ethBalance, $amount, 8);
            $tokenEnough = $ethBalance >= 0;
            $data['to'] = $this->coinbase;
            $data['value'] = '0x' . base_convert(bcmul($amount, number_format(1000000000000000000, 0, '.', ''), 0), 10, 16);
            $data['data'] = '0x';

            $gasLimitHex = $this->eth_estimateGas($from, $data['to'], '0x0', $gasPriceHex, $data['value'], $data['data']);

            $the_fees = bcdiv(bcmul(hexdec($gasPriceHex), hexdec($gasLimitHex)), number_format(1000000000000000000, 0, '.', ''), 18);

            if ($the_fees['error']) {
                return ['status' => 0, 'error' => $the_fees['message']];
            }
            //Now calc amount to be sent without fees
            $ethBalance = bcsub($ethBalance, $the_fees, 8);
            $data['to'] = $this->coinbase;
            $data['value'] = '0x' . base_convert(bcmul($ethBalance, number_format(1000000000000000000, 0, '.', ''), 0), 10, 16);
            $data['data'] = '0x';
            $gasLimitHex = $this->eth_estimateGas($from, $data['to'], '0x0', $gasPriceHex, $data['value'], $data['data']);
            $the_fees = bcdiv(bcmul(hexdec($gasPriceHex), hexdec($gasLimitHex)), number_format(1000000000000000000, 0, '.', ''), 18);


            if ($tokenEnough) {

                if (bcsub($ethBalance, $the_fees, 18) < 0) {
                    $send = bcsub($ethBalance, $the_fees, 18);
                    $data['value'] = '0x' . base_convert(bcmul($send, number_format(1000000000000000000, 0, '.', ''), 0), 10, 16);
                }
                $unlockStatus = $this->personal_unlockAccount($from, $pwd);
                if ($unlockStatus) {
                    $result = $this->eth_sendTransaction($from, $data['to'], $gasLimitHex, $gasPriceHex, $data['value'], $data['data']);
                }
            }
        }

        return $result;
    }


    function baseconvert($weiNumber)
    {
        return base_convert($weiNumber, 16, 10);
    }

    public function fromWei($weiNumber)
    {

        $tenNumber = base_convert($weiNumber, 16, 10);
        return bcdiv($tenNumber, $this->base, 8);
    }

    /*
    BSC is having 12 blocks/min in avg so atleast check 20 blocks/min call
    */
    public function EsmartInCrontab($oldnum = 0, $num_of_blocks_to_read = 20)
    {

        $params = array();

        if ($this->node_type == 'light') {
            $number = $this->requestPublic("eth_blockNumber", $params);
        } else {
            $number = $this->request("eth_blockNumber", $params);
        }

        if ($number == '0x0' || $number == null) {
            if ($this->node_type == 'light') {
                $blockNumberInfo = $this->requestPublic("eth_syncing", $params);
            } else {
                $blockNumberInfo = $this->request("eth_syncing", $params);
            }
            $number = $blockNumberInfo['currentBlock'];
        }


        $nums = $currentBlock = base_convert($number, 16, 10);

        $isk = 1;
        $ucinsert = array();

        $blockpermin = $num_of_blocks_to_read;
        $plusone = $blockpermin + 1;
        if ($oldnum > 0 && $nums >= 0) {
            $ps = $nums - $oldnum;
            if ($ps > $plusone) {
                $ps = $plusone;
                $nums = $oldnum + $blockpermin;
            } else {
                $ps = $blockpermin;
            }
        } else {
            die($number);
        }
        for ($i = 1; $i < $ps; $i++) {

            $np = '0x' . base_convert($nums - $i, 10, 16);
            $params = array(
                $np,
                true
            );
            if ($this->node_type == 'light') {
                $data = $this->requestPublic("eth_getBlockByNumber", $params);
            } else {
                $data = $this->request("eth_getBlockByNumber", $params);
            }
            if (isset($data["hash"]) && isset($data["transactions"])) {
                foreach ($data["transactions"] as $k => $t) {
                    $bs = base_convert($t["value"], 16, 10);
                    $b = bcdiv($bs, pow(10, 18), 8);
                    $ucinsert[$isk]["number"] = $b;
                    $ucinsert[$isk]["hash"] = $t["hash"];
                    $ucinsert[$isk]["from"] = $t["from"];
                    $ucinsert[$isk]["to"] = $t["to"];
                    $ucinsert[$isk]["input"] = $t["input"];
                    $isk++;
                }

            }
        }
        $ucinsert[0]["block"] = $nums;
        $ucinsert[0]["num"] = $ps;
        $ucinsert[0]["current"] = $currentBlock;

        return $ucinsert;
    }

    public function eth_syncing()
    {
        $params = array();
        //return $this->request("eth_syncing", $params);
        if ($this->node_type == 'light') {
            return $this->requestPublic(__FUNCTION__, $params);
        } else {
            return $this->request(__FUNCTION__, $params);
        }
    }

    public function eth_blockNumber()
    {
        $params = array();
        if ($this->node_type == 'light') {
            return $this->requestPublic("eth_blockNumber", $params);
        } else {
            return $this->request("eth_blockNumber", $params);
        }

    }

    public function payoutERC20Batch_decode($input): array
    {
        $func = '0x' . substr($input, 2, 8);
        if ($func != '0x2228f3a4') {
            return [];
        }
        $input_lenght = strlen($input);
        $token_splitter = "000000000000000000000000"; //24 bits
        $splited = explode($token_splitter, $input);

        $check = [];
        foreach ($splited as $split) {
            if (!empty($split)) {
                $check[] = $split;
            }
        }


        $nums = hexdec($check[4]);

        $begin_code = substr($input, 290, $input_lenght);

        do {

            $local_length = strlen($begin_code);
            $useful_array[] = substr($begin_code, 0, 40);

            $begin_code = substr($begin_code, 64, $local_length);

            $local_length = strlen($begin_code);
        } while ($local_length > 24);
        //entries to remove code
        $entry_1 = $nums;
        $entry_2 = ($nums * 2) + 1;
        unset($useful_array[$entry_2]);
        unset($useful_array[$entry_1]);
        foreach ($useful_array as $useme) {
            $sorted[] = $useme;
        }

        $final = [];
        for ($i = 0; $i < $nums; $i++) {
            $final[$i]['contract'] = '0x'.$sorted[$i];
            $final[$i]['address'] = '0x'.$sorted[$i + $nums];
            $final[$i]['bal_hex'] = '0x'.$sorted[$i + ($nums * 2)];
        }
        return $final;
    }

    public function txstatus($txhash)
    {
        $result = file_get_contents("https://api.bscscan.com/api?module=transaction&action=gettxreceiptstatus&txhash=" . $txhash . "&apikey=" . $this->explorer_api_key);
        if ($result) {
            $json = json_decode($result);
            $resp = $json->result->status;
            if ($json->status == 1 && $resp == 1) {
                return 1;
            } else {
                return 0;
            }
        } else {
            return 0;
        }
    }

    public function eth_getTransactionReceipt($transactionHash)
    {
        //Transaction Hash
        $params = array(
            $transactionHash,
        );

        if ($this->node_type == 'light') {
            $data = $this->requestPublic(__FUNCTION__, $params);
        } else {
            $data = $this->request(__FUNCTION__, $params);
        }
        return $data;
    }


    /* this function was later replaced with native functional So not in use */
    public function es_TokenBalance($address, $contract, $decimal = 8)
    {
        $url = "https://api.bscscan.com/api?module=account&action=tokenbalance&contractaddress=" . $contract . "&address=" . $address . "&tag=latest&apikey=" . $this->explorer_api_key;
        $ret = file_get_contents($url); //JSON results Something Like {"status":"1","message":"OK","result":"100000000"}
        $result = json_decode($ret); //JSON DECODE
        // $balance=($result->result)/pow(10,$decimal); //Now converting result to actual number by dividing 10^decimals example 10^8
        $balance = ($result->result); //
        if ($ret && $result->status == 1) {
            return $balance;
        } else {
            return false;
        }
    }

    private function eth_getTransactionCount_Explorer($address)
    {
        $url = "https://api.bscscan.com/api?module=proxy&action=eth_getTransactionCount&address=" . $address . "&apikey=" . $this->explorer_api_key;

        $result = file_get_contents($url);
        if ($result) {
            $json = json_decode($result);

            if ($json->result) {
                return $json->result;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    private function eth_sendRawTransaction_Explorer($data)
    {
        $url = "https://api.bscscan.com/api?module=proxy&action=eth_sendRawTransaction&hex=" . $data . "&apikey=" . $this->explorer_api_key;

        $result = file_get_contents($url);
        if ($result) {
            $json = json_decode($result);

            if ($json->result) {
                return $json->result;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    private function eth_estimateGas_Explorer($from, $to, $gas, $gasPrice, $value, $data)
    {
        $url = "https://api.bscscan.com/api?module=proxy&action=eth_estimateGas&from=" . $from . "&data=" . $data . "&to=" . $to . "&value=" . $value . "&gasPrice=" . $gasPrice . "&gas=" . $gas . "&apikey=" . $this->explorer_api_key;

        $result = file_get_contents($url);

        if ($result) {
            $json = json_decode($result);

            if ($json->result) {
                return $json->result;
            } else {
                return 0;
            }
        } else {
            return 0;
        }
    }

    private function gasPrice_Explorer()
    {


        $url = "https://api.bscscan.com/api?module=proxy&action=eth_gasPrice&apikey=" . $this->explorer_api_key;
        $result = file_get_contents($url);
        if ($result) {
            $json = json_decode($result);

            if ($json->result) {
                return $json->result;
            } else {
                return 0;
            }
        } else {
            return 0;
        }
    }

    public function TokenbalanceUsingExplorer($address, $contract)
    {
        $url = "https://api.bscscan.com/api?module=account&action=tokenbalance&address=" . $address . "&tag=latest&contractaddress=" . $contract . "&apikey=" . $this->explorer_api_key;
        return $this->sub_bal_explorer($url);
    }

    //Only supports BSC using explorer
    public function balanceUsingExplorer($address)
    {
        $url = "https://api.bscscan.com/api?module=account&action=balance&address=" . $address . "&tag=latest&apikey=" . $this->explorer_api_key;
        return $this->sub_bal_explorer($url);
    }

    /**
     * @param string $ethBalance
     * @param $gasPriceHex
     * @param $gasLimitHex
     * @param array $data
     * @param $result
     * @return false|mixed
     */
    private function sub_transferFrom(string $ethBalance, $gasPriceHex, $gasLimitHex, array $data, $result)
    {
        if (bcsub($ethBalance, bcdiv(bcmul(hexdec($gasPriceHex), hexdec($gasLimitHex)), number_format(1000000000000000000, 0, '.', ''), 18), 18) >= 0) {

            $unlockStatus = $this->personal_unlockAccount($this->coinbase, $this->coinbasePwd);

            if ($unlockStatus) {

                $res = $this->eth_sendTransaction($this->coinbase, $data['to'], $gasLimitHex, $gasPriceHex, $data['value'], $data['data']);

                if ($res['error']['code']) {
                    clog('geth_', json_encode($res));
                    $result = $res;
                    $result['status'] = 0;
                } elseif ($res) {
                    $result['result'] = $res;
                    $result['status'] = 1;
                } else {
                    $result = $res;
                }


            }
        }
        return $result;
    }

    /**
     * @param string $url
     * @return int|string|null
     */
    private function sub_bal_explorer(string $url)
    {
        $result = file_get_contents($url);

        if ($result) {
            $json = json_decode($result);

            if ($json->status == 1 && $json->result) {
                return bcdiv($json->result, number_format(pow(10, 18), 0, '.', ''), 2);
            } else {
                return 0;
            }
        } else {
            return 0;
        }
    }
}
