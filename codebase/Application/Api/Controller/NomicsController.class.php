<?php

namespace Api\Controller;

class NomicsController extends CommonController
{
    public function index()
    {
        $array = array('status' => 1, 'message' => 'Connected to Nomics Reporting API');
        echo json_encode($array);
    }
    /*
     * The /info endpoint returns information about the exchange as a whole, and is used by Nomics to display information about your exchange to users.

     */
    public function info(){

        $return= [
            'name' => SHORT_NAME,
            'description' => C('web_description').' Cryptocurrency exchange - We operate the worlds biggest bitcoin exchange and altcoin crypto exchange in the world by volume,Cryptocurrency exchange - We operate the worlds biggest bitcoin exchange and altcoin crypto exchange in the world by volume,Cryptocurrency exchange - We operate the worlds biggest bitcoin exchange and altcoin crypto exchange in the world by volume,Cryptocurrency exchange - We operate the worlds biggest bitcoin exchange and altcoin crypto exchange in the world by volume,Cryptocurrency exchange - We operate the worlds biggest bitcoin exchange and altcoin crypto exchange in the world by volume,Cryptocurrency exchange - We operate the worlds biggest bitcoin exchange and altcoin crypto exchange in the world by volume,Cryptocurrency exchange - We operate the worlds biggest bitcoin exchange and altcoin crypto exchange in the world by volume,Cryptocurrency exchange - We operate the worlds biggest bitcoin exchange and altcoin crypto exchange in the world by volume,Cryptocurrency exchange - We operate the worlds biggest bitcoin exchange and altcoin crypto exchange in the world by volume,Cryptocurrency exchange - We operate the worlds biggest bitcoin exchange and altcoin crypto exchange in the world by volume,Cryptocurrency exchange - We operate the worlds biggest bitcoin exchange and altcoin crypto exchange in the world by volume,Cryptocurrency exchange - We operate the worlds biggest bitcoin exchange and altcoin crypto exchange in the world by volume,Cryptocurrency exchange - We operate the worlds biggest bitcoin exchange and altcoin crypto exchange in the world by volume',
            'location' => "usa",
            'logo' => SITE_URL.'Upload/public/'.C('web_logo'),
            'website' => SITE_URL,
            'twitter' => C('contact_twitter'),
            'version' => '1.0',
            'capability' =>
                [
                    'markets' => true,
                    'trades' => true,
                    'ordersSnapshot' => true,
                    'candles' => false,
                    'ticker' => false,
                ],
        ];
        return $this->jsonResp($return,JSON_UNESCAPED_SLASHES);
    }
    public function markets(){
        $return=[];
        foreach(C('market') as $market){

            $market_name=strtoupper($market['name']);
            $pieces = explode("_", $market_name);
            $base = $pieces[0];
            $quote = $pieces[1];
            $return[]=([
                "id" => $market_name,
                "type" => "spot",
                "base" => $base,
                "quote" => $quote,
                "market_url"=> SITE_URL. "Trade/index/market/".$market['name'],
            ]);
        }
        return $this->jsonResp($return,JSON_UNESCAPED_SLASHES);
    }
    /*
     * trade_log
     * The /trades endpoint returns executed trades historically for a given market (provided via parameters). It allows Nomics to ingest all trades from your exchange for all time.
     */
    public function trades(){
        $market = strtolower(I('get.market', '', 'text')); //required
        $since = I('get.since', 0, 'intval'); //optional
        if(!$market || $market!=C('market')[$market]['name']){
            $error['status']=0;
            $error['message']='No Such market';
            return $this->jsonResp($error);
        }
        $where['market']=$market;
        $where['status']=1;
        $where_sql=" WHERE `market`='".$market."' AND `status`=1 ";
		if($since!=0) {
			$where_sql=$where_sql." AND `id` > $since";
        $where['id']=array('egt', $since);
        }
		$where_sql="SELECT * FROM `codono_trade_log` ".$where_sql. " order by id asc limit 30";
        //$tradeLogs = M('TradeLog')->where($where)->order('id desc')->limit(30)->select();
		$tradeLogs=M()->query($where_sql);
        $return=[];
		foreach($tradeLogs as $tl){

            $datetime = date("Y-m-d\TH:i:s\Z", @$tl['addtime']);

            if($tl['type']==1){
                $type='buy';
            }else{
                $type='sell';
            }
            $return[]=
                [
                    "id" => $tl['id'],
                    "timestamp" => $datetime,
                    "price" => $tl['price'],
                    "amount" => $tl['mum'],
                    "amount_quote" => $tl['num'],
                    "type" => "limit",
                    "side" => $type
            ];
        }
        $this->jsonResp($return);
    }
    public function orders_snapshot(){
        $market = strtolower(I('get.market', '', 'text')); //required
        if(!$market || $market!=C('market')[$market]['name']){
            $error['status']=0;
            $error['message']='No Such market';
            return $this->jsonResp($error);
        }
                $orders=A('Exchange')->activeorders($market,1,1);

        $buy_orders=$orders['depth']['buy'];
		
		$bo=$so=[];

        foreach ($buy_orders as $buy_order){

            $bo[]=[$buy_order['price'],$buy_order['nums']];
        }

        $sell_orders=$orders['depth']['sell'];
        foreach ($sell_orders as $sell_order){
            $so[]=[$sell_order['price'],$sell_order['nums']];
        }
        $return=['bids'=>$bo,'asks'=>$so,'timestamp'=>date("Y-m-d\TH:i:s\Z")];

        $this->jsonResp($return);
    }
	
	public function noticker(){
		http_response_code(404);
	}
    public function ticker()
    {
        $market = [];
        foreach (C('market') as $market) {
            $info = $this->formatMarket($market['name']);
            $markets[] =
                array(
                    "market" => $info['market'],
                    "quote" => $info['quote'],
                    "base" => $info['base'],
                    "price_quote" => format_num($market['new_price'], 8),
					"volume_base" => $this->getQuoteVol($market['volume'], $market['new_price']),
                );
        }
        $return = $markets;
        $this->jsonResp($return);
    }

    private function getQuoteVol($vol, $lastprice)
    {
       return format_num($vol,8);
    }

    private function formatMarket($market): array
    {

        $symbol = strtoupper($market);
        $base = explode('_', $market)[0];
        $quote = explode('_', $market)[1];
        $market_name = $base . '-' . $quote;
        return ['base' => strtoupper($base), 'quote' => strtoupper($quote), 'market' => strtoupper($market_name)];
    }

    private function jsonResp($resp,$param=NULL)
    {
        header('Content-type: application/json');
        if($param){
            echo json_encode($resp,$param);
        }else{
            echo json_encode($resp);
        }

        exit;
    }

}

