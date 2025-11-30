<?php
namespace Common\Ext;

class XrpClient{

    private $host;
    private $port;
    private $account;
    private $secret;
    private $token;
	/*
	
	Test net address :r4r28F2rXSsHvGF3Yjouq2XBWGUhrDtpX2
	Secret:ssVQ93w5sUkdhzRgZEYniN5L4FpQp
	*/
    private string $network;
    private string $own_host;
    private string $pub_host1;
    private string $pub_host2;
    private string $test_host;

    public function __construct($host = 'http://127.0.0.1',$port = '15001',$address = 'XRPADDRESS',$secret = '',$token=''){
        $this->host = $host;
		$this->network='test'; //public , own or test;
		
		$this->own_host=$this->host . ':' . $this->port;
		
		$this->pub_host1="https://s1.ripple.com:51234/";
		$this->pub_host2="https://s2.ripple.com:51234/";
		
		
		//Receive Testnet address , secret and balance here https://xrpl.org/xrp-testnet-faucet.html 
		$this->test_host="https://s.altnet.rippletest.net:51234/";
		
        $this->port = $port;
        $this->account = $address;
        $this->secret = $secret; //xrp secret
        $this->token = $token;
    }

    //Get account information
    public function accountInfo()
    {
        $params = '{
            "method": "account_info",
            "params": [
                {
                    "account": "'.$this->account.'",
                    "strict": true,
                    "ledger_index": "current",
                    "queue": true
                }
            ]
        }';
        
        return $this->post($params);
    }

    public function sign($num=0,$destination='',$tag='')
    {
        $num = floor($num * 1000000);
        $tag = $tag ?: '888888888';
        $params = '{
                    "method": "sign",
                    "params": [
                        {
                            "offline": false,
                            "secret": "'.$this->secret.'",
                            "tx_json": {
                                "Account": "'.$this->account.'",
                                "Amount": '.$num.',
                                "Destination": "'.$destination.'",
                                "DestinationTag": "'.$tag.'",
                                "TransactionType": "Payment"
                            },
                            "fee_mult_max": 1000
                        }
                    ]
                }';


        return $this->post($params);
    }

    public function submit($tx_blob = '')
    {
        $params = '{
            "method": "submit",
            "params": [
                {
                    "tx_blob": "'.$tx_blob.'"
                }
            ]
        }';

        return $this->post($params);
    }

    //History
    public function history()
    {
        $params = '{
            "method": "account_tx",
            "params": [
                {
                    "account": "'.$this->account.'",
                    "binary": false,
                    "forward": false,
                    "ledger_index_max": -1,
                    "ledger_index_min": -1,
                    "limit": 100
                }
            ]
        }}';
        return $this->post($params);
    }

    //Verify transaction order
    public function tx($transaction = '')
    {

        if (!$transaction){
            return false;
        }
        $params = '{
            "method": "tx",
            "params": [
                {
                    "transaction": "'.$transaction.'",
                    "binary": false
                }
            ]
        }';

        return $this->post($params);

    }
	private function networkurl(): string
    {
		
		if($this->network=='public'){
			$random=rand(1,2);
			if($random==1)
			{$host=$this->pub_host1;}
			else
			{$host=$this->pub_host2;}
		}elseif($this->network=='test'){
			$host=$this->test_host;
		}else{
			$host=$this->own_host;
		}
		return $host;
	}
	
    //Transfer or post
    public function post($params = '')
    {
        if (!$params){
            return false;
        }
        $url = $this->networkurl();
		
        $ch = curl_init();
        curl_setopt($ch,CURLOPT_URL,$url);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        $data = curl_exec($ch);
		//echo "<pre>";print_r($data);echo "</pre>";
        curl_close($ch);
        return json_decode($data,true);
    }
}