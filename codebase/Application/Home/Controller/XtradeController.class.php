<?php
/*
 * API Key: RS1YyJOUmiXMzucf1HBiVmRG7bidNJx43jtDg8HRRV8tOhOVYjRv0oITcBi2RRXU
 * Secret Key: DSVP3dVIH8XaK8NVD5LH4Xj3ixd7t7Sb0D3CYIxmEeTVORXFKnANsiLUz1hYQd49
 *
 * */

namespace Home\Controller;

use Common\Ext\Binance;
use Exception;

/**
 * @property mixed api
 */
class XtradeController extends HomeController
{
    private Binance $binance;

    public function __construct()
    {
        $this->binance = binance();
        parent::__construct();
		checkcronkey();
    }



    public function index()
    {
        echo 'xTrade Connected';
    }


    private function internalMatching($market)
    {
        if (!isset($market)) {
            return;
        }
        $mo = M();
        $sell_map['market'] = $market;
        $sell_map['type'] = 2;
        $sell_map['fill'] = 0;
        $sell_map['peerid'] = ['gt', 0];
        $sell_map['status'] = 1;
        $sell_map['userid'] = 0;
        $sell_orders = $mo->table('codono_trade_log')->where($sell_map)->order('price asc,id asc')->select();


        $buy_map['market'] = $market;
        $buy_map['type'] = 1;
        $buy_map['fill'] = 0;
        $buy_map['peerid'] = 0;
        $buy_map['status'] = 1;
        $buy_map['userid'] = ['gt', 0];
        $buy_orders = $mo->table('codono_trade_log')->where($buy_map)->order('price desc,id asc')->select();
        $count = bcmul(count($buy_orders), count($sell_orders)) * 5;


        for ($i = 0; $i < $count; $i++) {

            $buy = $mo->table('codono_trade_log')->where($buy_map)->order('price desc,id asc')->find();
            $sell = $mo->table('codono_trade_log')->where($sell_map)->order('price asc,id asc')->find();

            if ($buy && $sell && (0 <= floatval($buy['price']) - floatval($sell['price']))) {

                $amount = min(bcsub($buy['num'], $buy['fill_qty'], 8), bcsub($sell['num'], $sell['fill_qty'], 8));

                $amount = format_num($amount, 8);
                if ($amount <= 0) {
                    M('TradeLog')->where(['id' => $buy['id']])->setField('fill', 1);
                    M('TradeLog')->where(['id' => $sell['id']])->setField('fill', 1);
                    break;
                }
                if ($sell['id'] < $buy['id']) {
                    $type = 1;
                } else {
                    $type = 2;
                }


                if ($type == 1) {
                    $price = $sell['price'];
                } else if ($type == 2) {
                    $price = $buy['price'];
                } else {
                    break;
                }

                if (!$price) {
                    break;
                } else {
                    $price = format_num($price, 8);
                }

                $mum = bcmul($price, $amount, 8);

                if (!$mum) {
                    break;
                }
                $rs[] = $mo->table('codono_trade_log')->where(['id' => $buy['id']])->setInc('fill_qty', $amount);
                $rs[] = $mo->table('codono_trade_log')->where(['id' => $sell['id']])->setInc('fill_qty', $amount);

                $rs = $this->sub_internalMatching($mo, $buy['id'], $rs);

                $rs = $this->sub_internalMatching($mo, $sell['id'], $rs);

            } else {
                break;
            }


        }
    }
    public function cronMe(){
        G('begin');
        cronLastRun(__FUNCTION__);
        $markets=C('market');
        foreach($markets as $market){
            if($market['xtrade']==1){
                
                $this->crossMarket($market['name']);
            }
        }
        G('end');
        echo '<br/>Total Time taken ' . G('begin', 'end') . 's';

    }
    private function crossMarket($market)
    {
        if(!isset($market)){
            $this->error('Please specify market');
        }
        //DO internal matching first
        $this->internalMatching($market);

        $mo = M();
        $sell_map['market'] = $market;
        $sell_map['type'] = 2;
        $sell_map['fill'] = 0;
        $sell_map['peerid'] = ['gt', 0];
        $sell_map['status'] = 1;
        $sell_map['userid'] = 0;
        $sell_orders = $mo->table('codono_trade_log')->where($sell_map)->order('price asc,id asc')->select();


        $buy_map['market'] = $market;
        $buy_map['type'] = 1;
        $buy_map['fill'] = 0;
        $buy_map['peerid'] = 0;
        $buy_map['status'] = 1;
        $buy_map['userid'] = ['gt', 0];
        $buy_orders = $mo->table('codono_trade_log')->where($buy_map)->order('price desc,id asc')->select();
        $orders=array_merge($buy_orders,$sell_orders);


        foreach ($orders as $order) {
            if($order['type']==2){
                $type='sell';
            }else if($order['type']==1){
                    $type='buy';
            }else{
                //invalid type check next record
                continue;
            }
            echo '<br/>Cross Ordering ' .$market['name'];

            $binance_resp = $this->MakeOrder($market, round($order['num'], 6), round($order['price'], 2), $type);
            if(empty($binance_resp)){
                echo '<br/>There were issues with Binance cross Ordering<br/>';
                continue;
            }
            echo '<pre>';
            print_r($binance_resp);
            $this->markOrder($binance_resp, $order['id']);
            echo '</pre>';
        }

    }

	public function otcTrade()
    {
        
        cronLastRun(__FUNCTION__);
        $mo = M();
        
        $sell_map['type'] = 'sell';
        $sell_map['fill'] = 0;
        $sell_map['userid'] = ['gt', 0];
        $sell_map['status'] = 1;
       
        $sell_orders = $mo->table('codono_otc_log')->where($sell_map)->order('id asc')->select();


		$buy_map['type'] = 'buy';
        $buy_map['fill'] = 0;
        $buy_map['userid'] = ['gt', 0];
        $buy_map['status'] = 1;
        $buy_orders = $mo->table('codono_otc_log')->where($buy_map)->order('id asc')->select();
        $orders=array_merge($buy_orders,$sell_orders);
		

        foreach ($orders as $order) {
            $type=$order['type'];
			
			$base_coin=$order['base_coin'];
			$qty=$order['qty'];
			$price=$order['final_price'];
			if($order['base_coin']=='usd'){
				$base_coin='usdt';
			}
			$market=strtoupper($order['trade_coin'].$base_coin);
            
			$binance_resp = $this->MakeOrder($market, floatval(round($qty, 6)), floatval(round($price, 2)), $type);
			
            if(empty($binance_resp)){
                echo '<br/>There were issues with Binance cross Ordering<br/>';
                continue;
            }
            echo '<pre>';
            print_r($binance_resp);
            $this->markOtcLog($binance_resp, $order['id']);
            echo '</pre>';
        }
    }

    private function markOrder($binance_resp, $tradelog_id)
    {
        $fill_id = $this->binTradeEntry($binance_resp);
        M('TradeLog')->where(['id' => $tradelog_id])->save(['fill' => 1, 'fill_id' => $fill_id]);
    }
	
	private function markOtcLog($binance_resp, $tradelog_id)
    {
        $fill_id = $this->binTradeEntry($binance_resp);
        M('OtcLog')->where(['id' => $tradelog_id])->save(['fill' => 1, 'fill_id' => $fill_id]);
    }

    /**
     * @param $market
     * @param $quantity
     * @param $price
     * @param string $type : buy or sell
     * @return array
     */
    private function MakeOrder($market, $quantity, $price, string $type = 'buy')
    {
        if (!isset($market)) {
            $this->error('Please provide market');
        }
        $bmarket = $this->bmarket($market);
        $api = $this->binance;

        if ($type == 'buy') {
            return $api->buy($bmarket, $quantity, $price);
        } elseif ($type == 'sell') {
            return $api->sell($bmarket, $quantity, $price);
        } else {
            return [];
        }

    }

    /**
     * Converts Exchange market to compatible binance pair name
     * example eth_usdt becomes ETHUSDT
     * @param $market
     * @return string
     */
    private function bmarket($market): string
    {
        return strtoupper(str_replace('_', '', $market));
    }


    /**
     * @throws Exception
     */
    private function BinInfo()
    {
        $api = $this->binance;
        $report = $api->exchangeInfo();
        echo '<pre>';
        print_r($report);
        echo '</pre>';
    }

    /**
     *
     */
    private function balances()
    {
        $report = [];
        $api = $this->binance;
        try {
            $report = $api->balances();
        } catch (Exception $e) {
            clog('binance', json_encode($e->getMessage()));
        }
        echo '<pre>';
        print_r($report);
        echo '</pre>';
    }

    /**
     * @throws Exception
     */
    private function account()
    {
        $account = $this->binance->account();
        print_r($account);
    }

    /**
     * @throws Exception
     */
    private function openOrders($market)
    {
        if($market){
            return false;
        }
        $api = $this->binance;
        $bmarket = $this->bmarket($market);
        return $api->openOrders($bmarket);
    }

    /**
     * @throws Exception
     */
    private function history($market)
    {
        if(!$market){
            return false;
        }
        $api = $this->binance;
        $bmarket = $this->bmarket($market);
        return $api->history($bmarket);
    }

    /**
     * @param $mo
     * @param $id
     * @param array $rs
     * @return array
     */
    private function sub_internalMatching($mo, $id, array $rs): array
    {
        $list = $mo->table('codono_trade_log')->where(['id' => $id, 'fill' => 0])->find();

        if ($list) {
            if ($list['num'] <= $list['fill_qty']) {
                $rs[] = $mo->table('codono_trade_log')->where(['id' => $id])->setField('fill', 1);
            }
        }
        return $rs;
    }

    /**
     * @param $binance_resp
     * @return false|float|int|mixed|string
     */
    private function binTradeEntry($binance_resp)
    {
        $BinanceTrade_entry = ['symbol' => $binance_resp['symbol'], 'orderId' => $binance_resp['orderId'], 'orderListId' => $binance_resp['orderListId'], 'clientOrderId' => $binance_resp['clientOrderId'], 'transactTime' => $binance_resp['transactTime'], 'price' => $binance_resp['price'], 'origQty' => $binance_resp['origQty'], 'executedQty' => $binance_resp['executedQty'], 'cummulativeQuoteQty' => $binance_resp['cummulativeQuoteQty'], 'status' => $binance_resp['status'], 'timeInForce' => $binance_resp['timeInForce'], 'type' => $binance_resp['type'], 'side' => $binance_resp['side'], 'fills' => json_encode($binance_resp['fills'])];
        $fill_id = M('BinanceTrade')->add($BinanceTrade_entry);
        return $fill_id;
    }


}