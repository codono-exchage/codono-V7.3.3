<?php
namespace Common\Ext;
use IEXBase\TronAPI\Exception\TronException;
use IEXBase\TronAPI\Provider\HttpProvider;
use IEXBase\TronAPI\Tron;

class TronClient
{

    private string $use_node;
    private HttpProvider $fullNode;
    private HttpProvider $solidityNode;
    private HttpProvider $eventServer;
    private HttpProvider $explorerServer;
    private HttpProvider $signServer;

    public function __construct($network)
    {
        include('tron_vendor/vendor/autoload.php');
        $main = 'https://api.trongrid.io/';
        $test   ='https://api.shasta.trongrid.io/';
        //$private_node = "http://5.9.74.12:8091";
        if($network=='main'){
			$this->use_node = $main;
		}else{
			$this->use_node = $test;
		}
        
        $this->fullNode = new HttpProvider($this->use_node);
        $this->solidityNode = new HttpProvider($this->use_node);
        $this->eventServer = new HttpProvider($this->use_node);
        $this->explorerServer = new HttpProvider($this->use_node);
        $this->signServer = new HttpProvider($this->use_node);

    }
    private function connect($private_key=null){
        if($private_key==null){
            return new Tron($this->fullNode, $this->solidityNode, $this->eventServer,$this->explorerServer,$this->signServer);
        }else{
            return new Tron($this->fullNode, $this->solidityNode, $this->eventServer,$this->explorerServer,$this->signServer,$private_key);
        }

    }
    public function isConnected()
    {
        $tron= $this->connect();
        return $tron->isConnected();
    }
	
    public function sendTransaction(string $to, float $amount, string $from = null,string $private_key=null,string $message= null)
    {
		if($private_key!=null){
            $tron=$this->connect($private_key);
		}else{
            $tron=$this->connect();
        }


        try {
            return $tron->sendTransaction($to, $amount, $message, $from);
        } catch (TronException $e) {
            clog('tron-send',json_encode($e->getMessage()));
            return false;
        }
    }

    public function generateAddress()
    {
        $tron=$this->connect();

        $generateAddress = $tron->generateAddress(); // or createAddress()
        $rawData =$generateAddress->getRawData();
        if ($rawData) {
            return $rawData;
        } else {
            return false;
        }
    }
    public function getBalance($address): float
    {
        $tron=$this->connect();
        $tron->setAddress($address);
        try {
            return $tron->getBalance($address, true);
        } catch (TronException $e) {
            clog('tron_getBalance',$address.''.json_encode($e->getMessage()));
            return 0;
        }
    }
    public function getTrc10Balance($address,$tokenId,$decimals): float
    {
        $tron=$this->connect();
        $tron->setAddress($address);
        $bal= $tron->getTokenBalance($tokenId,$address);

        if($bal>0) {
            return (float)bcdiv($bal,bcpow(10,$decimals),$decimals);
        }
        else{
            return 0;
        }

    }
	public function transferTrc10($toAddress,$amount,$contract,$fromaddress,$private_key,$decimals)
    {
		
		$tron=$this->connect($private_key);
        $toAddress = $tron->address2HexString($toAddress);
		$fromaddress = $tron->address2HexString($fromaddress);
        
		$amount = bcmul($amount,bcpow(10,$decimals));//$tron->toTron($amount);
		
        //1. Create transaction
        $re = $tron->getTransactionBuilder()->sendToken($toAddress,$amount,$contract,$fromaddress);
        //2. Signature
        $signData = $tron->signTransaction($re);
        //3. Broadcast
        return $tron->sendRawTransaction($signData);
    }
	public function freezeBalance(string $address,float $amount = 0 ,$private_key=null){
				$tron=$this->connect($private_key);
				return $tron->freezeBalance($amount, 3, 'ENERGY', $address);
	}
	public function getAccountResources(string $address){
				$tron=$this->connect();
				$networkResources= $tron->getAccountResources($address);
				$resp['networkResources']=$networkResources;
				$resp['energyPerTrx'] = bcdiv($networkResources['TotalEnergyLimit'],$networkResources['TotalEnergyWeight'],6);
				$resp['trxPerEnergy']= bcdiv(1, $resp['energyPerTrx'], 6);
				$resp['bandwidthPerTrx'] = bcdiv($networkResources['TotalNetLimit'],$networkResources['TotalNetWeight'],6);
				$resp['trxPerBandwidth'] = bcdiv("1", $resp['bandwidthPerTrx'], 6);
				return $resp;
	}
	public function getAccount(string $address){
				$tron=$this->connect();
				$accountInfo= $tron->getAccount($address);
				return $accountInfo;
	}
	public function transferTrc20($abi,$contract,$toAddress,$amount,$from,$private_key,$decimals)
    {
		
		$tron=$this->connect($private_key);
		
        $func = "transfer";
        $toaddress = $tron->address2HexString($toAddress);
		$contract = $tron->address2HexString($contract);
		$from = $tron->address2HexString($from);
        $amount = bcmul($amount,bcpow(10,$decimals));//$tron->toTron($amount);
        $params = [$toaddress,$amount];
			
		
        //1. Create transaction
        $re = $tron->getTransactionBuilder()->triggerSmartContract($abi,$contract,$func,$params,100000000,$from);
        //2. Signature
        $signData = $tron->signTransaction($re);
        //3. Broadcast
        $res = $tron->sendRawTransaction($signData);
        return $res;
    }

    public function getTrc20Balance($address, $contract, $decimals, $abi){
        $contract=$this->toAddress($contract,'e');

        $function = "balanceOf";
        $fromAddress_HEX=$this->toAddress($address,'e');

        $params = [ str_pad($fromAddress_HEX, 64, "0", STR_PAD_LEFT) ];

        $tron=$this->connect();

        try {
            $result = $tron->getTransactionBuilder()->triggerConstantContract($abi, $contract, $function, $params, $fromAddress_HEX);
        } catch (TronException $e) {
            clog("tron_getTrc20Balance",$contract.":".json_encode($e->getMessage()));
            return 0;
        }

        $token_balance = $result[0]->toString();
        if (!is_numeric($token_balance)) {

            return 0;
        }
        return bcdiv($token_balance, bcpow("10", $decimals), $decimals);
    }

    public function getAbi($tokenname){
         $abi_path=WEBSERVER_DIR.'/Public/tron/'.strtolower($tokenname.'.abi');
		 if(file_exists($abi_path)){
				$decoded=json_decode(file_get_contents($abi_path),true);
				return $decoded['entrys']?:$decoded;
         }else{
             return false;
         }
    }
    public function fromTronConvert($amount): float
    {
        $tron=$this->connect();
        $from=$tron->fromTron($amount);

        return $from;
    }

    public function toTronConvert($amount): int
    {
        $tron=$this->connect();
        return $tron->toTron($amount);
    }
    public function getAssetsbyname($name){
        $name== preg_replace("/&#?[a-z0-9]+;/i","",$name);
        $endpoint=$this->use_node."/v1/assets/".$name."/list";
        return $this->gcurl($endpoint);

    }

    public function getTransaction($txid)
    {
        $tron=$this->connect();
        try {
            return $tron->getTransaction($txid);
        } catch (TronException $e) {
            clog("tron_getTransaction",$txid.":".json_encode($e->getMessage()));
            return array();
        }
    }

    public function getBlockRange($from,$to)
    {
        $tron=$this->connect();
		try {
            return $tron->getBlockRange($from,$to);
        } catch (TronException $e) {
            clog("tron_client",":".json_encode($e->getMessage()));
            return array();
        }
    }
    public function getBlock($block_number): array
    {
        $tron=$this->connect();
        try {
            return $tron->getBlock($block_number);
        } catch (TronException $e) {
            clog("tron_client",":".json_encode($e->getMessage()));
            return array();
        }
    }

    /**
     * @param $txid
     * @return array|false
     */
    public function getTransactionSimple($txid)
    {
        $info=$this->getTransaction($txid);
        if(!$info) return false;
        return array('status'=>$info['ret'][0]['contractRet'],'txid'=>$info['txID'],'info'=>$info['raw_data']
        ['contract'][0]['parameter']['value']);
    }

    //https://api.shasta.trongrid.io/v1/accounts/TYDzsYUEpvnYmQk4zGP9sWWcTEd2MiAtW6/transactions/trc20?contract_address=TR7NHqjeKQxGTCi8q8ZY4pL8otSzgjLj6t
    public function getTrc20TxByAddress($address,$trc20){

        $trc20=$trc20?"?contract_address=".$trc20:"";
        $endpoint=$this->use_node."/v1/accounts/".$address."/transactions/trc20".$trc20;

        return $this->gcurl($endpoint);
        //return $tron->getContractTransaction($txid);
    }
    public function deposits(string $address = null) {
        $tron = $this->connect();
        $array=[];
        $payments = $tron->getManager()->request(sprintf('v1/accounts/%s/transactions', $address), [
            'only_confirmed' => 'true',
            'only_to' => 'true',
            'limit' => 200
        ], 'get')['data'];
        foreach($payments as $payment){
            $array[]=array(1,$address, $payment['txID'], $tron->fromTron($payment['raw_data']['contract'][0]['parameter']['value']['amount']));
        }
            return $array;
    }
    /*$address: hex [41BBC8C05F1B09839E72DB044A6AA57E2A5D414A10] or trx address [TT67rPNwgmpeimvHUMVzFfKsjL9GZ1wGw8]
     *$type:e encode, d decode from hex to address
     *
     */
 
    
    public function toAddress($address,$type="d"){
        $tron=$this->connect();

        if($type=="d"){
            return $tron->fromHex($address);
        }
        else{
            return $tron->toHex($address);
        }
    }
    private function gcurl($url){

        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER=>0,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_HTTPHEADER => [
                "Accept: application/json"
            ],
        ]);

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
            //echo "cURL Error #:" . $err;
            return false;
        } else {
            $json=json_decode($response,true);
            if($json['success'])
                return $json['data'];
            else
                return false;
        }
    }

}