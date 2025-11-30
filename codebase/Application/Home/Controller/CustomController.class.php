<?php

namespace Home\Controller;

class CustomController extends HomeController
{

    public function __construct()
    {
        parent::__construct();
        checkcronkey();
    }
    private function gcurl($endpoint, $method = 'GET')
    {
        if (!$endpoint) {
            return "{'error':'No URL'}";
        }
        $call_url = $endpoint;
        $curl = curl_init();
        curl_setopt_array($curl, array(

            CURLOPT_URL => $call_url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_SSL_VERIFYPEER => 0,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => $method,
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
            //return "cURL Error #:" . $err;
            return null;
        } else {
            return $response;
        }
    }
    private function isJson($string)
    {
        json_decode($string);
        return (json_last_error() === JSON_ERROR_NONE);
    }
    public function pancakeupdate()
    {

        $is_inverse_pair=0;
        $pairs=[
            ['pair_contract'=>'0xe2989b7563a2394087b76bfa9210a18b23c9cc87','exchange_market'=>'zb_usdt','is_inverse_pair'=>0],
            ['pair_contract'=>'0xde16a669ef98e6b796a449506a7d49c59e624fa2','exchange_market'=>'zbc_zb','is_inverse_pair'=>1],
        ];
         $resp='';   
        foreach($pairs as $pair){
            $resp[]= "Starting to update the market data for ".$pair['exchange_market']."\n";
            $resp[]= $this->doPanCakeUpdate($pair['pair_contract'],$pair['exchange_market'],$pair['is_inverse_pair']);    
        }    
        echo "<pre>";
        print_r($resp);
        echo "</pre>";
    }
    private function doPanCakeUpdate($pair_contract,$exchange_market,$is_inverse_pair=0)
    {

        // Assuming you have already fetched the API response and decoded it into a $response variable
        $api_url = 'https://explorer-api.pancakeswap.com/cached/pools/v2/bsc/'.$pair_contract;
        $result = $this->gcurl($api_url);
        if (!$this->isJson($result)) {
            return ['data'=>0,'msg'=> "Can not connect to pancake swap api for pair contract".$pair_contract];
        }
        $response = json_decode($result, true);

        if($is_inverse_pair==1){
            $tokenPrice = $response['token0Price'];
            $tokenVol = $response['tvlToken0'];
        }else{
            $tokenPrice = $response['token1Price'];
            $tokenVol = $response['tvlToken1'];
        }    
        // Fetch the orderbook_markup value from the codono_market table
        $market = M('Market')->where(['name' => "$exchange_market"])->find();
        $orderbook_markup = $market['orderbook_markup'];

        // Calculate the markup percentage
        $markup_percentage = bcdiv($orderbook_markup, '100', 8);

        // Calculate the api_max and api_min
        $markup_value = bcmul($tokenPrice, $markup_percentage, 8);
        $api_max = bcadd($tokenPrice, $markup_value, 8);
        $api_min = bcsub($tokenPrice, $markup_value, 8);

        // Format the tvlToken1 value to 8 decimal places
        $tokenVol = bcdiv($tokenVol, 20, 8);

        // Update the codono_market table
        $data = [
            'api_max' => $api_max,
            'api_min' => $api_min,
            'api_max_qty' => $tokenVol
        ];

        $result = M('Market')->where(['name' =>(string)$exchange_market])->save($data);

        if ($result !== false) {
            $msg= "Successfully updated the market data. for $exchange_market";
        } else {
            $msg= "Failed to update the market data for $exchange_market";
        }
        return ['data'=>$result,'msg'=>$msg];
    }
}
