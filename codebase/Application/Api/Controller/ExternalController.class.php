<?php
/**
 * CoinGecko Submission API
 * Ref:https://docs.google.com/document/d/1v27QFoQq1SKT3Priq3aqPgB70Xd_PnDzbOCiuoCyixw/edit
 * 06-AUG-2020
 * 4 endpoints
 */

namespace Api\Controller;

class ExternalController extends CommonController
{
    public function index()
    {
        $info = "welcome!";
        $this->ajaxShow($info);
    }

    public function pairs()
    {

        $info = [];

        foreach (C('market') as $val) {
            $info['market'][] = array(
                "ticker_id" => strtoupper($val['name']),
                "base" => strtoupper(explode('_', $val['name'])[1]),
                "target" => strtoupper(explode('_', $val['name'])[0]));
        }
        $this->ajaxShow($info);
    }

    public function tickers()
    {
        $info = [];
        foreach (C('market') as $val) {
            $info['market'][] = array(
                "ticker_id" => strtoupper($val['name']),
                "base_currency" => strtoupper(explode('_', $val['name'])[1]),
                "target_currency" => strtoupper(explode('_', $val['name'])[0]),
                "last_price" => format_num($val['new_price']),
                "base_volume" => $val['volume'],
                "target_volume" => $val['volume'],
                "bid" => format_num($val['buy_price']),
                "ask" => format_num($val['sell_price']),
                "high" => format_num($val['max_price']),
                "low" => format_num($val['min_price']));
        }
        $this->ajaxShow($info);
    }

    public function orderbook($ticker_id = null, $depth = 100)
    {
        $market = strtolower($ticker_id);
        if (!C('market')[$market]) {
            $data = [];
            $this->ajaxShow($data);
        }
        $round_off = C('market')[$market]['round'];

        if ($depth > 100) {
            $depth = 100;
        }

        $data_getDepth = null;//(APP_DEBUG ? null : S('activeOrders'));
        $limt = $depth;
        if (!$data_getDepth[$market]) {

            $mo = M();

            $buy = $mo->query('select id,price,sum(num-deal)as nums from codono_trade where status=0 and type=1 and market =\'' . $market . '\' group by price order by price desc limit ' . $limt . ';');
            $sell = array_reverse($mo->query('select id,price,sum(num-deal)as nums from codono_trade where status=0 and type=2 and market =\'' . $market . '\' group by price order by price desc limit ' . $limt . ';'));


            if (isset($buy)) {
                foreach ($buy as $k => $v) {

                    $data['depth']['bids'][$k] = array(format_num($v['price'] * 1, $round_off), format_num($v['nums'] * 1, $round_off));  //When you need point $round_off precision
                }
            } else {
                $data['depth']['bids'] = '';
            }

            if (isset($sell)) {
                foreach ($sell as $k => $v) {
                    $data['depth']['asks'][$k] = array(format_num($v['price'] * 1, $round_off), format_num($v['nums'] * 1, $round_off)); //When you need point $round_off precision
                }
            } else {
                $data['depth']['asks'] = '';
            }

            $data_getDepth[$market] = $data;
            //S('activeOrders', $data_getDepth);
        } else {
            $data = $data_getDepth[$market];
        }

        $this->ajaxShow($data);
    }
	
    public function historical_trades($ticker_id = "BTC_USD", $limit = 100, $type = "both")
    {
        $market = strtolower($ticker_id);
        if (!C('market')[$market]) {
            $data = [];
            $this->ajaxShow($data);
        }
        if (!check($limit, 'integer') || $limit > 200) {
            $limit = 200;
        }
        //$data = (APP_DEBUG ? null : S('getTradelog' . $market));
        $data = null;
        if (!$data) {
            $tradeLog_buy = M('TradeLog')->where(array('status' => 1, 'market' => $market, 'type' => 1))->order('id desc')->limit($limit)->select();
            $tradeLog_sell = M('TradeLog')->where(array('status' => 1, 'market' => $market, 'type' => 2))->order('id desc')->limit($limit)->select();
            if ($tradeLog_buy && $type != 'sell') {
                $data['market'] = $market;
                foreach ($tradeLog_buy as $k => $v) {

                    //$buy[$k]['addtime'] = date('d-m H:i:s', $v['addtime']);
                    $buy[$k]['trade_id'] = $v['id'];
                    $buy[$k]['price'] = format_num($v['price'] * 1, 8);
                    $buy[$k]['base_volume'] = format_num($v['num'], 8);
                    $buy[$k]['target_volume'] = format_num($v['mum'], 8);
                    $buy[$k]['type'] = 'buy';
                    $buy[$k]['timestamp'] = number_format($v['addtime'] * 1000, 0, '.', '');
                }
                $data['buy'] = $buy;
                //S('getTradelog' . $market, $data);
            }
            if ($tradeLog_sell && $type != 'buy') {
                $data['market'] = $market;
                foreach ($tradeLog_sell as $k => $v) {

                    //$sell[$k]['addtime'] = date('d-m H:i:s', $v['addtime']);
                    $sell[$k]['trade_id'] = $v['id'];
                    $sell[$k]['price'] = format_num($v['price'] * 1, 8);
                    $sell[$k]['base_volume'] = format_num($v['num'], 8);
                    $sell[$k]['target_volume'] = format_num($v['mum'], 8);
                    $sell[$k]['type'] = 'sell';
                    $sell[$k]['timestamp'] = number_format($v['addtime'] * 1000, 0, '.', '');
                }
                //S('getTradelog' . $market, $data);
                $data['sell'] = $sell;
            }

        }


        $this->ajaxShow($data);
    }
	
	public function explorer(){
		$market="btc_usdt";
		$info = array(
   "total_supply" => "80000000", 
   "price_usd" => C('market')[$market]['new_price'], 
   "volume" => C('market')[$market]['volume'], 
   "usdt_price" => C('market')[$market]['new_price'],  
   "busd_price" => C('market')[$market]['new_price'], 
   "usdc_price" => C('market')[$market]['new_price']
); 
echo json_encode($info);
}
}