<?php
/*
Account ID: turndealer59186832
Account API Key: estbdlgtp7n9es47hll6o5tucm
*/
namespace Common\Ext;

class Xe{

    private $base_url = "https://xecdapi.xe.com/v1/";
    private $array_config = array();

    function __construct(array $array_config)
    {
        $this->array_config = $array_config;
        
        if(!isset($this->array_config['appid'])){
            //throw new Exception("invalid config data - appid", 1);
            clog(__CLASS__,"invalid config data - appid");
            return false;
        }
        if(!isset($this->array_config['appkey'])){
            //throw new Exception("invalid config data - appkey", 1);
            clog(__CLASS__,"invalid config data - appkey");
            return false;
        }
        
        if(!empty($this->array_config['appurl']))
            $this->base_url = $this->array_config['appurl'];
    }

    private function doCurl($url, $param, $method, $header = null)
    {
        $process = curl_init();
        
        $url = $this->base_url.$url;
        
        $SSL = substr($url, 0, 8) == "https://" ? true : false;  

        if($method == 'GET')
        {
            $queryStr = "";
            
            if($param == null){
                $param = array();
            }
            
            foreach ($param as $key => $value) {
                if($queryStr != ""){
                    $queryStr = $queryStr . "&";
                }
                $queryStr .= $key . "=" . $value;
            }
            if($queryStr != ""){
                $url = $url . "?" . $queryStr;
            }

            curl_setopt($process, CURLOPT_URL, $url);
            curl_setopt($process, CURLOPT_HTTPGET, TRUE);
        }
        else if($method == 'POST')
        {
            curl_setopt($process, CURLOPT_URL, $url);
            curl_setopt($process, CURLOPT_POSTFIELDS, $param);
            curl_setopt($process, CURLOPT_CUSTOMREQUEST, $method);
        }
        
        if($SSL){
            curl_setopt($process, CURLOPT_SSL_VERIFYPEER, FALSE);
        }  
        
        curl_setopt($process, CURLOPT_RETURNTRANSFER, TRUE);
        // curl_setopt($process, CURLOPT_HEADER, 0);
        curl_setopt($process, CURLOPT_DNS_USE_GLOBAL_CACHE, FALSE);
        curl_setopt($process, CURLOPT_DNS_CACHE_TIMEOUT, 2);


        // 'Authorization:Basic aWFwcHNwdGUubHRkLjg2ODU2ODg0MDpkbDExMmEzcWViaDdlbDd0aTFpYjJxZGZvdQ==',
        // 'Authorization:Basic ' . base64_encode("iappspte.ltd.868568840:dl112a3qebh7el7ti1ib2qdfou"),
        // 'Authorization:Basic ' . base64_encode("iappspte.ltd.868568840:dl112a3qebh7el7ti1ib2qdfou"),

        $headerArray = array(
            'Authorization:Basic '.base64_encode($this->array_config['appid'].":".$this->array_config['appkey']),
            'Cache-Control:no-cache'
        );

        if($header != null && is_array($header) && count($header) > 0)
        {
            foreach ($header as $key => $value) {
                $headerArray[$key] = $value;
            }
        }

        curl_setopt($process, CURLOPT_HTTPHEADER, $headerArray);

        $output = curl_exec($process);
        $httpcode = curl_getinfo($process, CURLINFO_HTTP_CODE);
        
        curl_close($process);

        $content = array();
        $content['output'] = $output;
        $content['http_status_code'] = $httpcode;

        return $content;
    }

    /**
     * Account information
     * @return 
     */
    public function accountInfo()
    {
        $url = "account_info.json";
        $response = $this->doCurl($url, null, "GET");
        if($response['http_status_code'] == 200)
        {
            return $response['output'];
        }
        return false;
    }

    /**
     * Currencies
     * @return type
     */
    public function currencies()
    {
        $url = "currencies.json/?obsolete=true&language=en";
        $response = $this->doCurl($url, null, "GET");
        
        return $response;
    }

    /**
     * Convert from a currency amount to multiple other currencies using the exchange rates appropriate to your purchased level of service (Daily or Live).
     * [Example: if you have $110.23 USD, how much EUR,CAD will that get you.]
     * @param type $fromCurrencyCode [Example: USD]
     * @param type $toCurrencyCodes [Example: EUR,CAD]
     * @param type $fromAmount [Example: 110.23]
     * @param type $dailyType [Daily,Live]
     * Daily � will return last rate at your preferred lock-in time.
     * Live � will return latest exchange rate.
     */
    public function convertFromExchangeRates($fromCurrencyCode, $toCurrencyCodes, $fromAmount)
    {
        $params = array();
        $params["from"] = $fromCurrencyCode;
        $params["to"] = $toCurrencyCodes;
        $params["amount"] = $fromAmount;

        $url = "convert_from.json/";
        $response = $this->doCurl($url, $params, 'GET');
        if($response['http_status_code'] == 200)
        {
            return $response['output'];
        }
        return false;
    }

    /**
     * Convert to a currency amount from multiple other currencies using the exchange rates appropriate	to your purchased level of service (Daily or Live).
     * [Example: how much USD and EUR do you need to get $1000 CAD.]
     * @param type $toCurrencyCode [Example: USD]
     * @param type $fromCurrencyCodes [Example: CAD,EUR]
     * @param type $toAmount [Example: 1000]
     * @param type $dailyType [Daily,Live]
     * Daily � will return last rate at your preferred lock-in time.
     * Live � will return latest exchange rate.
     */
    public function convertToExchangeRates($toCurrencyCode, $fromCurrencyCodes, $toAmount)
    {
        $params = array();
        $params["to"] = $toCurrencyCode;
        $params["from"] = $fromCurrencyCodes;
        $params["amount"] = $toAmount;

        $url = "convert_to.json/";

        $response = $this->doCurl($url, $params, 'GET');

		if($response['http_status_code'] == 200)
        {
            return $response['output'];
        }
		clog('fx',$response);
        return false;
    }
    
    /**
     * 
     * @param type $fromCurrencyCode
     * @param type $toCurrencyCodes
     * @param type $fromAmount
     * @param type $date [yyyy-mm-dd]
     * @param type $time [hh:mm]
     * @return type
     */
    public function historicRate($fromCurrencyCode, $toCurrencyCodes, $fromAmount, $date, $time)
    {
        $params = array();
        $params["from"] = $fromCurrencyCode;
        $params["to"] = $toCurrencyCodes;
        $params["date"] = $date;
        $params["time"] = $time;
        $params["amount"] = $fromAmount;

        $url = "historic_rate.json/";
        $response = $this->doCurl($url, $params, 'GET');

        return $response;
    }
    
    /**
     * 
     * @param type $fromCurrencyCode
     * @param type $toCurrencyCodes
     * @param type $fromAmount
     * @param type $startTimestamp [YYYY-MM-DD]
     * @param type $endTimestamp [YYYY-MM-DD]
     * @param type $interval [daily][hourly]
     * @param type $page OPTIONAL � You can specify the page number you want to request.
     * @param type $per_page OPTIONAL � You can specify the number of results per page. The default is 30 results per page with a maximum of 100 results per page.
     * @return type
     */
    public function historicRatePeriod($fromCurrencyCode, $toCurrencyCodes, $fromAmount, $startTimestamp, $endTimestamp, $interval = 'hourly', $page = 1, $per_page = 30)
    {
        $params = array();
        $params["from"] = $fromCurrencyCode;
        $params["to"] = $toCurrencyCodes;
        $params["startTimestamp"] = $startTimestamp;
        $params["endTimestamp"] = $endTimestamp;
        $params['interval'] = $interval;
        $params["amount"] = $fromAmount;

        $url = "historic_rate/period.json/";
        $response = $this->doCurl($url, $params, 'GET');

        return $response;
    }
	 /**
     * 
     * @param type $fromCurrencyCode
     * @param type $toCurrencyCodes
     * @param type $fromAmount
     * @param type $year Number: This parameter specifies the year to calculate average monthly rates
     * @param type $month Number:This parameter specifies the month in the given year to return average monthly rates. This is a numeric value from 1 to 12 where 1 is for January and 12 is for December. If no month is provided, then all months for the given year are returned.
     * @param type $interval [daily][hourly]
     * @param type $decimal_places Number OPTIONAL � this parameter can be used to specify the number of decimal places included in the output. Example 1 USD to EUR = 0.874852 with decimal_places=3, the output returned will be EUR = 0.875
     * 
     * @return type
     */
	public function monthlyAverage($fromCurrencyCode, $toCurrencyCodes, $fromAmount, $year, $month,$decimal_places=3){
		$params = array();
        $params["from"] = $fromCurrencyCode;
        $params["to"] = $toCurrencyCodes;
        $params["amount"] = $fromAmount;
		$params["year"] = $year;
        $params["month"] = $month;
        $params['obsolete'] = true;
        $params['inverse'] = false;
		$params['decimal_places'] = $decimal_places;

        $url = "monthly_average.json/";
        $response = $this->doCurl($url, $params, 'GET');

        return $response;
		
//		  'https://xecdapi.xe.com/v1/monthly_average?from=usd&to=eur&amount=10000&year=2021&month=10&obsolete=true&inverse=false&decimal_places=8' \
	}
    
}