<?php
/* 
Substrate Node for Codono Code 
To be used only with Codono Custom Node
*/
namespace Common\Ext;
//@todo add address validation for withdrawal

class Substrate
{

  private $host;
  private $port;
  private $api_key;
  private $decimals;
 
  private $url;
  function __construct ($params)
  {


      if (array_key_exists('host', $params)) {
        $host = $params['host'];
      } else {
        $host = '127.0.0.1';
      }
      if (array_key_exists('port', $params)) {
        $port = $params['port'];
      } else {
        $port = 22547;
      }
      if (array_key_exists('api_key', $params)) {
        $api_key = $params['api_key'];
      }else{
		$api_key = 'apikeyexample';
	  }
	  if (array_key_exists('decimals', $params)) {
        $decimals = $params['decimals'];
      }else{
		$decimals = 12;
	  }
    
    
    $this->host = $host;
    $this->port = $port;
    $this->api_key = $api_key;
    $this->url = 'http://'.$host.':'.$port.'/';
    $this->decimals=$decimals;
  }
	public function createAddress($uid){


      if($uid<1 || $uid >=4294967295){
          return $this->issue('The value of "value" is out of range. It must be >= 0 and <= 4294967295. Received:'.$uid);
      }
		$endpoint='createAddress/id/'.$uid;
		$method='GET';
		return $this->request($endpoint,$method);
	}

    /**
     * returns List of last 50 deposits
     */
    public function getDeposits(){
        $endpoint='getLatestDeposits';
        $method='GET';
        return $this->request($endpoint,$method);
    }
    public function markDepositAsRecorded($hash){
        $endpoint='markAsRecorded';
        $method='POST';
        $array=['hash'=>$hash,'api_key'=>$this->api_key];
        $data=http_build_query($array);

        return $this->request($endpoint,$method,$data);
    }
    public function getWithdrawalStatus($order_id){
        if($order_id<1 || $order_id >=4294967295){
            return $this->issue('Please check if valid order_id is provided: order_id'.$order_id);
        }
        $endpoint='getWithdrawalInfo/order_id/'.$order_id;
        $method='GET';
        return $this->request($endpoint,$method);
    }
    public function amount_decode($amount){
        return bcdiv($amount,bcpow(10,$this->decimals,0),8);
    }
    public function amount_encode($amount){

        return bcmul($amount,bcpow(10,$this->decimals,0),0);
    }
    public function withdraw($address,$amount,$order_id=0){
        if($this->validateAddress($address)==false){
            return $this->issue('Please check your address again:'.$address);
        }
        $endpoint='withdrawFromMain';
        $method='POST';
        $data=http_build_query(['to_address'=>$address,'amount'=>$amount,'order_id'=>$order_id,'api_key'=>$this->api_key]);
        return $this->request($endpoint,$method,$data);
    }
    private function validateAddress($address): bool
    {
        $length=strlen($address);

      if(ctype_alnum($address) && ($length===48 || $length===47) ){
          return true;
      }else{
          return false;
      }
    }
    private function issue($message){
            return json_encode(['status'=>0, 'message'=>$message]);
    }
    private function request($endpoint, $method = 'POST' ,$data=[])
    {
		$url=$this->url.$endpoint;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        if ($method == 'POST') {
            curl_setopt($ch, CURLOPT_POST, TRUE);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
			curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded'));
        }else{
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');			
		}
        $ret = curl_exec($ch);
        curl_close($ch);
        return $ret;
    }
	
	
}

