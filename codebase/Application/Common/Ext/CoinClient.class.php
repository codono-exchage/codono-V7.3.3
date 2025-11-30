<?php

namespace Common\Ext;

class CoinClient
{
    private string $username;
    private string $password;
    private string $proto;
    private $host;
    private int $port;
    private string $url;
    private $CACertificate;

    // Debug
    public $status;
    public $error;
    public $raw_response;
    public $response;
    private int $id = 0;
    /**
     * @var mixed|string
     */
    private $version_type;
    /**
     * @var false
     */
    private bool $debug;

    /**
     * @param string $username
     * @param string $password
     * @param string $host
     * @param int $port
     * @param string $url
     */
    public function __construct($username, $password, $host = 'localhost', $port = 8332,$timeout = 3,$headers = array(), $jsonformat = false, $url = '')
    {
		

		$version_type='old';
		if(strpos($host,'$')){
		$array=explode('$',$host);
		$host=$array[0];
		$version_type=$array[1];
	   }
		
        $this->username = $username;
        $this->password = $password;
        $this->host = $host;
        $this->port = $port;
        $this->url = $url;
        $this->proto = "http";
		$this->CACertificate = null;
		$this->version_type=$version_type;
		$this->debug=false;
				
    }

    /**
     * @param string|null $certificate
     */
    function setSSL($certificate = null)
    {
        $this->proto = 'https'; // force HTTPS
        $this->CACertificate = $certificate;
    }
    function validateaddress($address) {
        // Regular expression to check the format of the address
        $regex = '/^[a-zA-Z0-9]{25,34}$/';
    
        // Check if the address matches the regular expression
        if (preg_match($regex, $address)) {
            // Decode the address using Base58Check encoding
            $decoded = base58_decode($address);
    
            // Get the checksum from the decoded address
            $checksum = substr($decoded, -4);
    
            // Get the address without the checksum
            $payload = substr($decoded, 0, -4);
    
            // Double SHA256 hash the payload
            $hash = hash('sha256', hash('sha256', $payload, true));
    
            // Calculate the checksum for the payload
            $calculated_checksum = substr($hash, 0, 8);
    
            // Check if the calculated checksum matches the address checksum
            if ($checksum == $calculated_checksum) {
                return $resp['isvalid']=1;
            } else {
                return $resp['isvalid']=0;
            }
        } else {
            return $resp['isvalid']=0;
        }
    }
    
    
    public function __call($method, $params)
    {
		if($method=='getinfo' && $this->version_type=='new'){$method='getnetworkinfo';}
		if($method=='listaccounts' && $this->version_type=='new'){$method='listaddressgroupings';}
		if($method=='getaddressesbyaccount' && $this->version_type=='electrum'){$method='getalias';}
		if($method=='getnewaddress' && $this->version_type=='electrum'){$method='createnewaddress';$params=[];}
		if($method=='listaddressgroupings' && $this->version_type=='electrum'){$method='listaddresses';}
		//if($method=='listtransactions' && $this->version_type=='electrum'){$method='onchain_history';$params=["show_addresses"];}
		
        $this->status = $this->error=$this->raw_response=$this->response=null;


        $params = array_values($params);

        $this->id++;

        $request = json_encode([
            'method' => $method,
            'params' => $params,
            'id' => $this->id
        ]);
//				    if($method=='listtransactions' && $this->version_type=='electrum'){$request='{"jsonrpc":"2.0","id":'.$this->id.',"method":"onchain_history","params":{"show_addresses":true}}';}

        $curl = curl_init("{$this->proto}://{$this->host}:{$this->port}/{$this->url}");
        $options = [
            CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
            CURLOPT_USERPWD => $this->username . ':' . $this->password,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 10,
			CURLOPT_TIMEOUT=>30,
            CURLOPT_HTTPHEADER => array('Content-type: application/json'),
            CURLOPT_POST => true,
			//CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_POSTFIELDS => $request,
			CURLOPT_VERBOSE=> $this->debug
        ];

        if (ini_get('open_basedir')) {
            unset($options[CURLOPT_FOLLOWLOCATION]);
        }

        if ($this->proto == 'https') {
            if (!empty($this->CACertificate)) {
                $options[CURLOPT_CAINFO] = $this->CACertificate;
                $options[CURLOPT_CAPATH] = DIRNAME($this->CACertificate);
            } else {
                $options[CURLOPT_SSL_VERIFYPEER] = false;
            }
        }

        curl_setopt_array($curl, $options);

        $this->raw_response = curl_exec($curl);
		
        $this->response = json_decode($this->raw_response, true);

        $this->status = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        $curl_error = curl_error($curl);

        curl_close($curl);

        if (!empty($curl_error)) {
            $this->error = $curl_error;
        }

        if (isset($this->response) && isset($this->response['error'])) {
            $this->error = $this->response['error']['message'];
        } elseif ($this->status != 200) {
            switch ($this->status) {
                case 400:
                    $this->error = 'HTTP_BAD_REQUEST';
                    break;
                case 401:
                    $this->error = 'HTTP_UNAUTHORIZED';
                    break;
                case 403:
                    $this->error = 'HTTP_FORBIDDEN';
                    break;
                case 404:
                    $this->error = 'HTTP_NOT_FOUND';
                    break;
            }
        }
        if ($this->error) {
            return false;
        }
        return $this->response['result'];
    }
	
}