<?php
namespace Common\Ext;
/*********************************************************************
PHP Class WavesPlatform For Codono Exchange
* Author :Codono
* Dated :11/18/2018
* Basic Version 0.1
* Username
* Password
Faucet for testnet :https://testnet.wavesexplorer.com/faucet
You would need to configure a Waves node with API enabled

# Setting Up a Waves Platform Full Node on Digital Ocean

Waves Full Node stores the whole blockchain and gives access to the REST API.
For more information follow the links:
 * [Full Node](../waves-full-node/what-is-a-full-node.md)
 * [REST API](/)


[![Setting Up a Waves Platform Full Node on Digital Ocean](http://img.youtube.com/vi/CDmMeZlzKbk/0.jpg)](http://www.youtube.com/watch?v=CDmMeZlzKbk "Setting Up a Waves Platform Full Node on Digital Ocean")

Video instruction how to set up your full node using Digital Ocean.

[The video](http://www.youtube.com/watch?v=CDmMeZlzKbk) 


Use 2 curl functions 
$this->pcurl($endpoint,$params='',$method='POST')
$this->gcurl($endpoint) //It will remain GET only

All results are in JSON 
*********************************************************************/

class WavesPlatform
{
    
    protected $waves_port;//waves port

	protected $apiurl; // Endpoint URL
	protected $api_key ; //Plaintext API KEY
	protected $global_fee=100000; //This is the minimum fees
	protected $token_decimal_precision;
	protected $assetid; //Asset ID
	/*----------------------Public Functions -------------------------------*/
	 public function __construct($address, $port, $password,$assetid='',$token_decimal_precision=8)
    {

        // Set the default format to json if a value was not passed
        if (empty($assetid)) {
            $assetid = null;
        }

        // Set keys and format passed to class
		$this->apiurl=$address;
        $this->waves_port = $port;
        $this->api_key = $password;
        $this->assetid = $assetid;
		$this->token_decimal_precision=$token_decimal_precision;

        // Throw an error if the keys are not both passed
        try {
            if (empty($this->apiurl) || empty($this->api_key)) {
                //throw new Exception("Your address and password are not both set!");
                systemexception('<b>[50002WP]</b>:We are upgrading the system! Please refresh in sometime',"Your address and password are not both set!",false);
            }
        } catch (\Exception $e) {
            clog('waves_platform',$e->getMessage());
          //  echo 'Error: ' . $e->getMessage();
        }
	}
        // Initiate a cURL request object
     
	//Check Node Status
	public function Status(){
		$endpoint='node/status';
		return $this->gcurl($endpoint);
	}
	public function ActivationStatus(){
		$endpoint='activation/status';
		return $this->gcurl($endpoint);
	}
	
	//Check Node Version
	public function Version(){
		$endpoint='node/version';
		return $this->gcurl($endpoint);
	}
	
	//Get address List
	public function GetAddresses(){
		$endpoint='addresses';
		return $this->gcurl($endpoint);
	}
	
	//Create New Address
	public function CreateAddress(){
		$endpoint='addresses';
		return $this->pcurl($endpoint,'POST');
	}
	
	/*Verify Address from Same node or not 
	Response
	{
	"address" : "3Muuv8fNeYBWhfZhtGhmNudnEUbuhH2NrrP",
	"valid" : true
	}
	*/
	public function IfAddressFromSameNode($address){
		$endpoint='addresses/validate/'.$address;
		return $this->gcurl($endpoint);
	}
	
	
	/*Verify Address from Same node or not 
	Response
	{
	"address" : "3Muuv8fNeYBWhfZhtGhmNudnEUbuhH2NrrP",
	"valid" : true
	}
	*/
	public function ValidateAddress($address){
		$endpoint='addresses/validate/'.$address;
		return $this->gcurl($endpoint);
	}
	//List of unconfirmed transactions
	public function UnconfirmedTransactions(){
		$endpoint='transactions/unconfirmed/';
		return $this->gcurl($endpoint);
	}
	//Balance of an account
	public function Balance($address,$assetid=null){
		if($assetid==null){
			return $this->WavesBalance($address);
		}else{
			return $this->AssetsBalance($address,$assetid);
		}
		
	}
	private function WavesBalance($address){
		$endpoint='addresses/balance/'.$address;
		return $this->gcurl($endpoint);
	}
	private function AssetsBalance($address,$assetid=null){
		$endpoint='assets/balance/'.$address.'/'.$assetid;
		return $this->gcurl($endpoint);
	}
	/* Assets Transactions Public/Private*/
	public function UnconfirmedSize(){
		$endpoint='addresses/size/';
		return $this->gcurl($endpoint);
	}
	
	public function UnconfirmedTxInfo($id){
		$endpoint='transactions/unconfirmed/info/'.$id;
		return $this->gcurl($endpoint);
	}
	
	//Get list of transactions where specified address has been involved
	public function AddressTxInfo($address,$limit=50){
		$endpoint='transactions/address/'.$address.'/limit/'.$limit;
		return $this->gcurl($endpoint);
	}
	public function ValidateAsset($assetid){
		$assets=json_decode(file_get_contents(__DIR__.'/allowed_assets.json'),true);
		foreach($assets as $asset){
			if($asset['assetID']==$assetid){
				//Found the valid assetid
				return true;
		//		echo $asset['symbol'] .'is ' .$assetid;
		//		echo "<br/>";
			}
			else{
				//Do nothing here 
			//	echo $assetid .' neq ' .$asset['assetID']; 
			//	echo "<br/>";
			}
		}
		return false;
	}
	//https://docs.wavesplatform.com/en/development-and-api/waves-node-rest-api/asset-transactions/private-functions.html
	private function SendToken($sender,$recipient,$amount,$assetid){
	//	if(!$this->ValidateAsset($assetid))return false; //Dont use this function
		$data['sender']=$sender;
		$data['recipient']=$recipient;
		$data['amount']=$this->Amount($amount);
		$data['fee']=$this->global_fee;
		$data['assetId']=$assetid;
		$params=json_encode($data);
		
		
		$endpoint='assets/transfer';
		$resp_data=$this->pcurl($endpoint,$params);
		
		//Now Broadcasting to Blockchain
		$params2=$resp_data;
		$endpoint2='assets/broadcast/transfer';
		return $this->pcurl($endpoint2,$params2);		
	}
	public function Send($sender,$recipient,$amount,$assetid=null){
		if($assetid==null)
		{
			return $this->SendWaves($sender,$recipient,$amount);
		}
		else{
			return $this->SendToken($sender,$recipient,$amount,$assetid);
		}
		
	}
	private function base58($text){
		$data=$text;
		$params=json_encode($data);
		$endpoint='/utils/hash/fast';
		return $this->pcurl($endpoint,$params);	
	}
	public function calcfees($sender,$recipient,$amount,$assetid=''){
		//read here https://docs.wavesplatform.com/en/development-and-api/waves-node-rest-api/transactions.html 
		$data['type']=4;
		$data['sender']=$sender;
		$data['recipient']=$recipient;
		$data['amount']=$this->Amount($amount);
		$data['feeAssetId']=$assetid;
		$params=json_encode($data);
		$endpoint='transactions/calculateFee';
		return $this->pcurl($endpoint,$params);
		
	}
	public function SendWavesJustForMain($sender,$recipient,$amount){
		//read here https://docs.wavesplatform.com/en/development-and-api/waves-node-rest-api/transactions.html 
		
		$data['type']=4;
		$data['sender']=$sender;
		$data['recipient']=$recipient;
		$data['amount']=(int)($amount);
		$data['fee']=$this->global_fee;
		
		$params=json_encode($data);
		$endpoint='transactions/sign';
		$resp_data=$this->pcurl($endpoint,$params);
		//Now broadcasting to blockchain
		$params2=$resp_data;
		$endpoint2='transactions/broadcast';
		return $this->pcurl($endpoint2,$params2);
		
	}
	private function SendWaves($sender,$recipient,$amount){
		//read here https://docs.wavesplatform.com/en/development-and-api/waves-node-rest-api/transactions.html 
		$data['type']=4;
		$data['sender']=$sender;
		$data['recipient']=$recipient;
		$data['amount']=$this->Amount($amount,'waves');
		
		$data['fee']=$this->global_fee;
		
		$params=json_encode($data);
		$endpoint='transactions/sign';
		$resp_data=$this->pcurl($endpoint,$params);
		
		//Now broadcasting to blockchain
		$params2=$resp_data;
		$endpoint2='transactions/broadcast';
		return $this->pcurl($endpoint2,$params2);
		
	}
	//Not in use
	private function BroadcastTx($sender,$senderPublicKey,$timestamp,$signature){
		//read here https://docs.wavesplatform.com/en/development-and-api/waves-node-rest-api/transactions.html 
		$data['type']=4;
		$data['sender']=$sender;
		$data['senderPublicKey']=$senderPublicKey;
		$data['timestamp']=$timestamp;
		$data['signature']=$signature;
		$data['fee']=$this->global_fee;
		$params=json_encode($data);
		$endpoint='transactions/sign';
		return $this->pcurl($endpoint,$params);
		
	}
	
	public function TxInfo($id){
		$endpoint='transactions/info/'.$id;
		return $this->gcurl($endpoint);
	}
	
	//Returns an address associated with an Alias. Alias should be plain text without an 'alias' prefix and network code.
	public function GetAddressByAlias($alis){
		$endpoint='alias/by-alias'.$alis;
		return $this->gcurl($endpoint);
	}
	
	//Returns a collection of aliases associated with an Address
	public function GetAliasByAddress($address){
		$endpoint='alias/by-address'.$address;
		return $this->gcurl($endpoint);
	}
	
	//Create an alias
	public function CreateAlias($address,$alias){
		$post['sender']=$address;
		$post['alias']=$alias;
		$post['fees']=$this->global_fee;
		$endpoint='alias/create';
		return $this->pcurl($endpoint,json_encode($post));
	}
	//Get Block at certain Height
	public function GetAtHeight($id){
		$endpoint='blocks/at/'.$id;
		return $this->gcurl($endpoint);
	}	
	
	//Get Block Height
	public function BlockHeight(){
		$endpoint='blocks/height';
		return $this->gcurl($endpoint);
	}	
	//Get Last Block 
	public function LastBlock(){
		$endpoint='blocks/last';
		return $this->gcurl($endpoint);
	}
	//BETA :Get list of blocks generated by specified address
	public function BlockByAddress($address,$from,$to){
		//$endpoint='blocks/address/'.$address.'/'.$from.'/'.$to;
		$endpoint='blocks/address/'.$address.'/0/100000';
		return $this->gcurl($endpoint);
	}
	//Get Connected Peer List
	public function ConnectedPeers(){
		$endpoint='peers/connected';
		return $this->gcurl($endpoint);
	}
	public function Portfolio($address){
		$endpoint='debug/portfolios/'.$address.'?considerUnspent=true';
		return $this->gcurl($endpoint);
	}
	
	//Get Peer List
	public function PeerList(){
		$endpoint='peers/all';
		return $this->gcurl($endpoint);
	}
	public function ConnectPeer($ip,$port){
		$data['host']=$ip;
		$data['port']=$port;
		$endpoint='peers/connect';
		return $this->pcurl($endpoint,json_encode($data));
	}
	
	
	public function Amount($amt,$type='none'){
		$precision_waves=100000000; //10^8
		$amt=number_format($amt,8,'.','');
		if($type=='waves'){
			return $amt*$precision_waves;
		}
		if($this->assetid==null){
			return $amt*$precision_waves;
		}else{
			$precision_token=bcpow(10,$this->token_decimal_precision,$this->token_decimal_precision);
			return (bcmul($amt,$precision_token,8)*1);
		}
		
	}
	public function deAmount($amt,$dj_decimal=8){
		
		$amt=number_format($amt,8,'.','');
		$precision=pow(10,$dj_decimal); //10^8
		return number_format((($amt * $precision)/ $precision)/$precision,8,'.','');
		

	}
	/*----------------------Private Functions -------------------------------*/
	private function pcurl($endpoint,$params='',$method='POST')
    {
	if(!$endpoint){return "{'error':'No URL'}";}
	$call_url=$this->apiurl.'/'.$endpoint;

	$curl = curl_init();
	curl_setopt_array($curl, array(
	CURLOPT_PORT => $this->waves_port,
	CURLOPT_URL => $call_url,
	CURLOPT_RETURNTRANSFER => true,
	CURLOPT_ENCODING => "",
	CURLOPT_MAXREDIRS => 10,
	CURLOPT_TIMEOUT => 30,
	CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
	CURLOPT_CUSTOMREQUEST => $method,
	CURLOPT_POSTFIELDS => $params,
	CURLOPT_HTTPHEADER => array(
    "cache-control: no-cache",
	"Content-Type: application/json",
    "x-api-key: ".$this->api_key
	),
	));

	$response = curl_exec($curl);
	$err = curl_error($curl);

	curl_close($curl);

	if ($err) {
	return "cURL Error #:" . $err;
	} else {
    return $response;
	}
}
	
    private function gcurl($endpoint,$method='GET')
    {
	if(!$endpoint){return "{'error':'No URL'}";}
	$call_url=$this->apiurl.'/'.$endpoint;
	$curl = curl_init();
	curl_setopt_array($curl, array(
	CURLOPT_PORT => $this->waves_port,
	CURLOPT_URL => $call_url,
	CURLOPT_RETURNTRANSFER => true,
	CURLOPT_ENCODING => "",
	CURLOPT_MAXREDIRS => 10,
	CURLOPT_TIMEOUT => 30,
	CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
	CURLOPT_CUSTOMREQUEST => $method,
	));

	$response = curl_exec($curl);
	$err = curl_error($curl);

	curl_close($curl);

	if ($err) {
	return "cURL Error #:" . $err;
	} else {
    return $response;
	}
}
}