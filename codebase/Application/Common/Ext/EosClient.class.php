<?php
namespace Common\Ext;
class EosClient
{
    protected $host, $port;
    function __construct($host, $port, $version = "2.0")
    {
        $this->host = $host; //Test IP  api.eosnewyork.io

        $this->port = $port;
    }
    function request($url, $params = array())
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_URL, $url);
        //curl_setopt($ch, CURLOPT_PORT, $this->port);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json'
        ));
        curl_setopt($ch, CURLOPT_POST, TRUE);
        if (substr($url, -8) == 'get_info') {
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
        } else {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
        }
        $ret = curl_exec($ch);
        return @json_decode($ret);
    }
    //get
    public function get_request($method, $params = array())
    {
        $url = $this->host . ':' . $this->port . '/v1/chain/' . $method;
        $ret = $this->request($url, $params);
        return $ret;
    }
    //wallet
    public function wallet_request($method, $params = array())
    {
        $url = $this->host . ':' . '9999' . '/v1/wallet/' . $method;
        $ret = $this->request($url, $params);
        return $ret;
    }
    //history
    public function history_request($method, $params = array())
    {
        $url = $this->host . ':' . $this->port . '/v1/history/' . $method;
        $ret = $this->request($url, $params);
        return $ret;
    }
    //Wallet latest information
    function get_info()
    {
        return $this->get_request(__FUNCTION__);
    }
    //Block information
    function get_block($add_block, $name = '')
    {
        $block_number = $this->get_info();
        if ($add_block['block_num_or_id'] > $block_number->head_block_num) {
            return [];
        }
        if ($name) {
            $block_info = $this->get_request(__FUNCTION__, $add_block);
            $block_transactions = $block_info->transactions;
            foreach ($block_transactions as $k => $v) {
                $block_trx[] = $v->trx;
            }
//            M('coin')->where(array('name' => $name))->setInc('block_num', 1);
            return $block_trx;
        } else {
            return $this->get_request(__FUNCTION__,
                $add_block
            );
        }
    }
    //Get tx
    function get_block_transaction($block_trx)
    {
    }
	//search information
    function get_table_rows($add_block)
    {
        return $this->get_request(__FUNCTION__,
            $add_block
        );
    }
	//Query wallet information
    function get_account($account)
    {
        return $this->get_request(__FUNCTION__,
            $account
        );
    }
	//Query contract information
    function get_abi($abi)
    {
        return $this->get_request(__FUNCTION__,
            $abi
        );
    }
	//Get abi or code
    function get_raw_code_and_abi($account_token)
    {
        return $this->get_request(__FUNCTION__,
            $account_token
        );
    }

    function get_currency_balance($account_token)
    {
        return $this->get_request(__FUNCTION__,
            $account_token
        );
    }
	//Json converted to binary
    function abi_json_to_bin($account_token)
    {
        return $this->get_request(__FUNCTION__,
            $account_token
        );
    }
	//Convert binary to json
    function abi_bin_to_json($account_token)
    {
        return $this->get_request(__FUNCTION__,
            $account_token
        );
    }
    //Send transaction
    function push_transaction($wallet_account,$ealletp ,$wallet)
    {
        $this->open($wallet);
        $this->unlock($wallet_account, $ealletp);
        return $this->get_request(__FUNCTION__, $wallet);
    }   
    function get_required_keys($wallet)
    {
        return $this->get_request(__FUNCTION__, $wallet);
    }

    function get_transaction($account_token)
    {
        return $this->history_request(__FUNCTION__,
            $account_token
        );
    }
    //View transaction information
    function get_actions($account_token)
    {
        $actions = $this->history_request(__FUNCTION__, $account_token);
        return json_decode(json_encode($actions->actions, true), true);
    }
    //Unlock
    function unlock($wallet, $walletp)
    {
        return $this->wallet_request(__FUNCTION__, array(
            $wallet,
            $walletp
        ));
    }
    //Deployment signature
    function sign_transaction($wallet, $ealletp, $account_token)
    {
        $this->open($wallet);
        $this->unlock($wallet, $ealletp);
        return $this->wallet_request(__FUNCTION__, $account_token);
    }
    //Create a wallet
    function create($name)
    {
        return $this->wallet_request(__FUNCTION__, $name);
    }
    //Import key
    function import_key($name, $key)
    {
        return $this->wallet_request(__FUNCTION__, array(
            $name,
            $key
        ));
    }
    //List wallet accounts
    function list_wallets()
    {
        return $this->wallet_request(__FUNCTION__);
    }
    //Get Public keys
    function get_public_keys()
    {
        return $this->wallet_request(__FUNCTION__);
    }
    //Open Wallet
    function open($wallet_name)
    {
        return $this->wallet_request(__FUNCTION__, $wallet_name);
    }
    //Single wallet locked
    function lock($wallet_name)
    {
        return $this->wallet_request(__FUNCTION__, $wallet_name);
    }
    //Lock all wallets
    function lock_all()
    {
        return $this->wallet_request(__FUNCTION__);
    }
}