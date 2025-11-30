<?php

namespace Home\Controller;

use Think\Page;

/**
 * OTC Controller
 */
class OtcController extends HomeController
{
    //Coins in which users can buy or sell they need to be listed on your exchange as well as huobi
    private const DEFAULT_COIN_TRADE = ['symbol' => 'btc', 'image' => '/Upload/coin/BTC.png'];
    private const DEFAULT_COIN_BASE = ['symbol' => 'usd', 'image' => '/Upload/coin/USD.png'];
    private const BASE_COINS = ['usd', 'zar', 'usdt', 'ugx'];  // Payment method/ Frequency
    private const BASE_COINS_LIMIT = ['usd' => ['min' => 10, 'max' => 10000], 'zar' => ['min' => 100, 'max' => 100000], 'ugx' => ['min' => 1000, 'max' => 1000000], 'usdt' => ['min' => 1000, 'max' => 1000000]];
    private const ROUNDABOUT = 0;

    /**
     * @return void
     */
    public function _initialize()
    {
        if (OTC_ALLOWED == 0) {
            $this->assign('type', 'Oops');
            $this->assign('error', 'Oops, Currently OTC is disabled!');
            //$this->display('Content/error');
            $this->error('Otc is currently disabled');
            exit();
        }
        $this->ovex_api_key = OVEX_API_KEY;
        parent::_initialize();
    }

    /**
     * @return void
     */
    public function index()
    {
        $where['status'] = array('neq', 0);
        $mo = M('Otc');
        $count = $mo->where($where)->count();
        $Page = new Page($count, 120);
        $show = $Page->show();

        $list = $mo->where($where)->order('sort asc,addtime desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();

        $otc_list = array();
        if (is_array($list)) {
            foreach ($list as $k => $v) {
                //if($v['img']==null ||$v['img']==''){
                if (!isset($v['img'])) {
                    $v['img'] = 'default.png';
                }
                $img = c('coin')[$v['coinname']]['img'];
                $otc_list[$v['name']] = array('symbol' => $v['coinname'], 'image' => '/Upload/coin/' . $img, 'min' => $v['min'], 'max' => $v['max']);

            }

            unset($k);
            unset($v);
        } else {
            $otc_list = null;
        }
        $base_coins = array();
        foreach (self::BASE_COINS as $base) {
            $base_coins[$base]['image'] = '/Upload/coin/' . c('coin')[$base]['img'];
            $base_coins[$base]['symbol'] = $base;
            $base_coins[$base]['min'] = 1;
            if ($base == 'usdt' || $base == 'usd') {
                $max_allowed_base = 1000000;
            } else {
                $max_allowed_base = 5000000;
            }
            $base_coins[$base]['max'] = $max_allowed_base;
        }


        $this->assign('base_coins', $base_coins);
        $this->assign('default_coin_base', self::DEFAULT_COIN_BASE);
        $this->assign('default_coin_trade', self::DEFAULT_COIN_TRADE);
        $this->assign('trade_coins', $otc_list);
        $this->display();
    }

    /**
     * @param $trade_coin
     * @param $base_coin
     * @param $input1
     * @param $input2
     * @param string $tradetype
     * @return void
     */
    public function getquote($trade_coin, $base_coin, $input1 = 0, $input2 = 0, string $tradetype = 'buy')
    {
        $uid = userid();
        $trade_coin = strtolower($trade_coin);
        $base_coin = strtolower($base_coin);
        $exchange=$this->findExchangeByBase($base_coin);
        $expire=$this->getExpireByExchange($exchange);
        if (!$uid) {
            $this->error(L('PLEASE_LOGIN'));
        }
        $bcoins = self::BASE_COINS;

        if (!in_array($base_coin, $bcoins)) {
            $this->error(L('Invalid currency1') . $base_coin);
        }
        if (!check($trade_coin, 'n')) {
            $this->error(L('Invalid currency2!') . $trade_coin);
        }

        $tcoin = M('Otc')->where(array('status' => 1, 'coinname' => $trade_coin))->getField('coinname');
        $buy_commission = M('Otc')->where(array('status' => 1, 'coinname' => $trade_coin))->getField('buy_commission');
        $sell_commission = M('Otc')->where(array('status' => 1, 'coinname' => $trade_coin))->getField('sell_commission');
        if ($tcoin != $trade_coin) {
            $this->error(L('Invalid currency3!'));
        }

        if (!in_array($base_coin, $bcoins)) {
            $this->error(L('Invalid currency'));
        }


        $expire = $expire-1;
        if ($input2 == 0) {
            $inverse = false;
            $deal_coin = $trade_coin;
            $quantity = $input1;
        } else {
            $inverse = true;
            $deal_coin = $base_coin;
            $quantity = $input2;
        }
        if (!check($quantity, 'double')) {
            $this->error('Incorrect Quantity provided !' . $quantity);
        }

        if ($inverse) {
            $coin_limits = self::BASE_COINS_LIMIT[$base_coin];
            if ($quantity < $coin_limits['min']) {
                $this->error('Minimum order:' . $coin_limits['min'] . $base_coin);
            }
            if ($quantity > $coin_limits['max']) {
                $this->error('Maximum order:' . $coin_limits['max'] . $base_coin);
            }
        }


        $symbol = strtolower($trade_coin . $base_coin);

        $avg_price = $this->calcAvgPrice($trade_coin, $base_coin, $tradetype, $quantity);
        if (!isset($avg_price) || $avg_price == null || $avg_price <= 0) {
            $this->error('Please try again in sometime !');
        }

        if ($tradetype == 'buy') {
            $comm = bcdiv($buy_commission, 100, 8);
            $mux = bcadd(1, $comm, 8);
            $final_price = bcmul($avg_price, $mux, 8);
        }
        if ($tradetype == 'sell') {
            $comm = bcdiv($sell_commission, 100, 8);
            $mux = bcsub(1, $comm, 8);
            $final_price = bcmul($avg_price, $mux, 8);
        }

        if ($inverse) {

            $quantity = bcdiv($quantity, $final_price, 8);
        }
        //$offer=array($avg_price,$final_price,$offer_qty,$i,$check_offers);


        //clog('Quote_',json_encode(array('profit'=>$profit,'inverse'=>$inverse,'avg_price'=>$avg_price,'commission'=>$commission,'quantity'=>$quantity,'total'=>$total,'final_total'=>$final_total,'final_price'=>$final_price)));


        if ($tradetype == 'buy') {
            $diff = bcsub($final_price, $avg_price, 8);
        }
        if ($tradetype == 'sell') {
            $diff = bcsub($avg_price, $final_price, 8);
        }

        $profit = bcmul($diff, $quantity, 8);

        $total = (double)bcmul($avg_price, $quantity, 8);
        $final_total = (double)bcmul($final_price, $quantity, 8);


        if (self::ROUNDABOUT == 0) {
            $final_total = round($final_total, self::ROUNDABOUT);
        }

        //@TODO add user balance check if he has enough
        $coin = $base_coin;
        $coind = $base_coin . 'd';


        if ($tradetype == 'buy') {
            $type = 1;

            $spend_coin = strtolower($base_coin);
            $spend_total = $final_total;

            //receivable in case of buy , spendable in case of sell
            $receive_coin = strtolower($trade_coin);
            $receive_total = format_num($quantity);

        } //receivable in case of buy , spendable in case of sell
        else {
            $type = 2;
            $spend_coin = strtolower($trade_coin);
            $spend_total = format_num($quantity);


            $receive_coin = strtolower($base_coin);
            $receive_total = $final_total;
            $receive_coind = $receive_coin . 'd';
        }

        $user_balance = M('UserCoin')->where(array('userid' => $uid))->field("id,userid,$spend_coin")->find();
        if ($user_balance['id'] > 0 && $user_balance['userid'] == $uid) {
            $user_avail_balance = format_num($user_balance[$spend_coin], 8);
        } else {
            $this->error('Please logout and login, If this issue persist, please contact admin');
        }

        $msg = "Your Quote is ready and available for $expire secs!";
        if ($spend_total > $user_avail_balance) {
            //$this->error("You have low balance of $user_avail_balance , It needs $final_total");
            $msg = "You have low balance of $user_avail_balance , It needs $spend_total";
            $quote_id = 0;
        } else {
            $quote_id = $uid . '_' . time();
        }

        $quote = array('qid' => $quote_id, 'trade_type' => $tradetype, 'trade' => $trade_coin, 'base' => $base_coin, 'symbol' => $symbol, 'qty' => $quantity, 'final_price' => $final_price, 'final_total' => $final_total, 'profit' => $profit, 'addtime' => time());
        $client_response['status'] = 1;
        $client_response['msg'] = $msg;
        $client_response['data'] = array('qid' => $quote_id, 'trade_type' => $tradetype, 'symbol' => $symbol, 'qty' => $quantity, 'price' => $final_price, 'total' => $final_total, 'trade' => strtoupper($trade_coin), 'base' => strtoupper($base_coin), 'expire' => $expire);
        $mo = M();
        $saveme = array('qid' => $quote_id, 'data' => json_encode($quote));
        $mo->table('codono_otc_quotes')->add($saveme);
        exit(json_encode(($client_response)));
    }

    private function findExchangeByBase($baseCurrency): string
    {
        $c2 = strtoupper($baseCurrency);
        if ($c2 == 'ZAR') {
            $exchange = 'ovex';
        } else {
            $exchange = 'binance';
        }
        return $exchange;
    }
    private function getExpireByExchange($exchange): int
    {
        $exchange = strtolower($exchange);
        switch ($exchange){
            case 'ovex':
            $expire= 10;
            break;
            case 'huobi':
            case 'binance':
                $expire= 20;
                break;
            default:
                $expire=20;
        }
        return $expire;
    }
    /**
     * @param $c1
     * @param $c2
     * @param $tradetype
     * @param $quantity
     * @return float|int|string|null
     */
    private function calcAvgPrice($c1, $c2, $tradetype, $quantity)
    {
        

        $exchange=$this->findExchangeByBase($c2);
        
        switch ($c2) {
            case 'USD':
                $concatSymbol = $c1 . 'USDT';
                $conv_required = 0;
                break;

            case 'UGX':
                $concatSymbol = $c1 . 'USDT';
                $conv_required = 1;
                break;

            default;
            case 'USDT';
                $conv_required = 0;
                $concatSymbol = $c1 . $c2;
        }

        $avg_price = $this->getAvgPricing($exchange, $concatSymbol, $tradetype, $quantity);
        
        if ($conv_required == 1) {
            $conversion = $this->getConversion($c2, 1);
            $avg_price = bcdiv($avg_price, $conversion, 8);
        }

        return $avg_price;
    }

    /**
     * @param $exchange
     * @param $concatSymbol
     * @param $tradetype
     * @param $quantity
     * @return float|int|string|null
     */
    private function getAvgPricing($exchange, $concatSymbol, $tradetype, $quantity)
    {
        
        switch ($exchange) {
            case 'ovex':
                $params['market'] = strtolower($concatSymbol);
                $params['side'] = $tradetype;
                return $this->getOvexQuote($params);
            default:
                return $this->cexPricing($exchange, $concatSymbol, $tradetype, $quantity);
            //do nothing yet
        }
    }

    /**
     * @param $exchange
     * @param $concatSymbol
     * @param $tradetype
     * @param $quantity
     * @return int|string|null
     */
    private function cexPricing($exchange, $concatSymbol, $tradetype, $quantity)
    {
        
        $return =(object)[];
        if ($exchange == 'huobi') {
            $symbol = strtolower($concatSymbol);
            $endpoint = "https://api.huobi.pro/market/depth?symbol=$symbol&type=step1";
            $response = json_decode(static::gcurl($endpoint, 'GET'));
            
            if ($response->status == 'ok') {
                $return->status = 'ok';
                $return->bids = $response->tick->bids;
                $return->asks = $response->tick->asks;
            } else {
                $return->status = 'error';
                $return->bids = '';
                $return->asks = '';
            }
        } else {
            
            $symbol = strtoupper($concatSymbol);
            $endpoint = "https://api.binance.com/api/v1/depth?symbol=$symbol&limit=20";
            
            $response = json_decode(static::gcurl($endpoint, 'GET'));
            
            $return = '';
            if ($response->lastUpdateId > 0) {
                $return->status = 'ok';
                $return->bids = $response->bids;
                $return->asks = $response->asks;
            } else {
                $return->status = 'error';
                $return->bids = '';
                $return->asks = '';
            }
        }

        if ($return->status != 'ok') {
            return 0;
            //$this->error('Status error:Please check back in sometime');
        } else {

            $check_offers = array();

            switch ($tradetype) {
                case 'buy':
                    $check_offers = $response->asks;
                    break;
                case 'sell':
                    $check_offers = $response->bids;
                    break;
                default:
                    //inform admin that we couldnt fetch response
                    $this->error('Please check back in sometime1');
            }
            $i = 0;
            $offer_price = 0;
            $offer_qty = 0;
            foreach ($check_offers as $check) {
                $i++;
                $offer_qty = bcadd($offer_qty, $check[1], 8);
                $offer_price = bcadd($offer_price, $check[0], 8);
                if (format_num($quantity) < format_num($offer_qty)) {
                    continue;
                }

            }
            return bcdiv($offer_price, $i, 8);
        }
    }

    /**
     * @param $endpoint
     * @param string $method
     * @param array $header
     * @return bool|string
     */
    private static function gcurl($endpoint, string $method = 'GET', array $header = array())
    {

        if (!$endpoint) {
            return "{'error':'No URL'}";
        }
        $call_url = $endpoint;
        $curl = curl_init();
        curl_setopt_array($curl, array(

            CURLOPT_URL => $call_url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_SSL_VERIFYPEER => 0,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => $header
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
            return 'cURL Error #:' . $err;
        } else {
            return $response;
        }
    }

    /**
     * @param $qid
     * @return void
     */
    public function approvequote($qid)
    {
        $uid = userid();
        if (!$uid) {
            $this->error(L('PLEASE_LOGIN'));
        }
        $split = explode('_', $qid);
        if ($split[0] != $uid) {
            $this->error('Invalid Quote ERROR:Q1');
        }


        $now = time();
        $mo = M();
        $where = array('qid' => $qid);
        $response = $mo->table('codono_otc_quotes')->where($where)->find();


        $quote = json_decode($response['data'], true);
        $quote_id = $response['qid'];

        $profit = $quote['profit'];

        /*	{"qid":"50_1604341391","trade_type":"sell","trade":"btc","base":"usd","symbol":"btcusd","qty":"0.00007587","final_price":"13178.72342500","final_total":0.99986974,"profit":"0.02301490","addtime":1604341391}
        */

        $exchange=$this->findExchangeByBase($quote['base']);
        $expire=$this->getExpireByExchange($exchange);
        if ($split[1] != $quote['addtime'] && $quote['addtime'] != 0) {
            $this->error('Invalid Quote ERROR:U1');
        }

        if ($qid != $quote_id || $qid == 0 || $qid == '' || $quote['final_total'] <= 0 || $quote['qty'] <= 0 || $quote['final_price'] <= 0 || $quote['profit'] <= 0) {
            $this->error('This is invalid quote request, Please refresh and try again');
        }


        $lived = $now - $quote['addtime'];
        if ($expire < $lived) {
            $this->error('Quote is expired already, Please refresh and try again');
        }

        $Otc = M('Otc')->where(array('coinname' => $quote['trade'], 'status' => 1))->find();
        if (!$Otc) {
            $this->error('Please try again later');
        }
        $cur_user_info = $mo->table('codono_user')->where(array('id' => $uid))->field('invit_1,invit_2,invit_3')->find();

        $tier_1_user = $cur_user_info['invit_1'];
        $tier_1_percent = bcdiv($Otc['tier_1'], 100, 8);
        $tier_1 = bcmul($profit, $tier_1_percent, 8);

        $tier_2_user = $cur_user_info['invit_2'];
        $tier_2_percent = bcdiv($Otc['tier_2'], 100, 8);
        $tier_2 = bcmul($profit, $tier_2_percent, 8);


        $tier_3_user = $cur_user_info['invit_3'];
        $tier_3_percent = bcdiv($Otc['tier_3'], 100, 8);
        $tier_3 = bcmul($profit, $tier_3_percent, 8);

        $tier_commission_part1 = bcadd($tier_1, $tier_2, 8);
        $tier_commission_total = bcadd($tier_commission_part1, $tier_3, 8);
        $site_profit = bcsub($profit, $tier_commission_total, 8);


        $fees_percent = 0;//$Otc['fees'];
        $fee_percent = bcdiv($fees_percent, 100, 8);

        $fees_paid = bcmul($fee_percent, $quote['final_total'], 8);
        $final_total = bcsub($quote['final_total'], $fees_paid, 8);

        $qty = format_num($quote['qty']);


        //check balance
        $otc_insert = array(
            'qid' => $quote['qid'],
            'userid' => $uid,
            'type' => $quote['trade_type'],
            'trade_coin' => $quote['trade'],
            'base_coin' => $quote['base'],
            'final_price' => format_num($quote['final_price']),
            'profit' => format_num($quote['profit']),
            'fees_paid' => format_num($fees_paid),
            'qty' => format_num($quote['qty']),
            'final_total' => format_num($final_total),
            'addtime' => $quote['addtime'],
            'status' => 1
        );

        $order_type = $quote['trade_type'];

        //spendable in case of buy, receivable in case of sell
        if ($order_type == 'buy') {
            $type = 1;

            $spend_coin = strtolower($quote['base']);
            $spend_total = $final_total;

            //receivable in case of buy , spendable in case of sell
            $receive_coin = strtolower($quote['trade']);
            $receive_total = format_num($quote['qty']);

        } //receivable in case of buy , spendable in case of sell
        else {
            $type = 2;
            $spend_coin = strtolower($quote['trade']);
            $spend_total = format_num($quote['qty']);


            $receive_coin = strtolower($quote['base']);
            $receive_total = $final_total;
        }
        $receive_coind = $receive_coin . 'd';


        $mo = M();

        $user_balances = $mo->table('codono_user_coin')->where(array('userid' => $uid))->find();
        if ($user_balances[$spend_coin] < $spend_total) {
            $this->error('You dont have enough balance');
        }
        $mo->startTrans();
        $rs[] = $otc_log_id = $mo->table('codono_otc_log')->add($otc_insert);
        $rs[] = $mo->table('codono_user_coin')->where(array('userid' => $uid))->setDec($spend_coin, $spend_total);
        $rs[] = $mo->table('codono_user_coin')->where(array('userid' => $uid))->setInc($receive_coin, $receive_total);

        $finance_mum_user_coin = $mo->table('codono_user_coin')->where(array('userid' => $uid))->find();

        $finance_hash = md5($quote['qid']);

        $xnum = bcadd($user_balances[$receive_coin], $user_balances[$receive_coind], 8);
        $mum = bcadd($finance_mum_user_coin[$receive_coin], $finance_mum_user_coin[$receive_coind], 8);
        $rs[] = $mo->table('codono_finance')->add(array('userid' => $uid, 'coinname' => $receive_coin, 'num_a' => $user_balances[$receive_coin], 'num_b' => $user_balances[$receive_coind], 'num' => $xnum, 'fee' => $fees_paid, 'type' => $type, 'name' => 'otc', 'nameid' => $otc_log_id, 'remark' => "OTC $order_type:" . $Otc['coinname'], 'mum_a' => $finance_mum_user_coin[$receive_coin], 'mum_b' => $finance_mum_user_coin[$receive_coind], 'mum' => $mum, 'move' => $finance_hash, 'addtime' => time(), 'status' => 1));

        if ($type == 1) {
            $rs[] = $mo->table('codono_otc')->where(array('id' => $Otc['id']))->setInc('deal_buy', $qty);
        }
        if ($type == 2) {
            $rs[] = $mo->table('codono_otc')->where(array('id' => $Otc['id']))->setInc('deal_sell', $qty);
        }
        //Increase Token Owners Balance token_owners_keep
        if ($Otc['ownerid'] > 0 && $profit > 0 && $site_profit > 0) {
            $rs[] = $mo->table('codono_user_coin')->where(array('userid' => $Otc['ownerid']))->setInc($quote['base'], $site_profit);
            $rs[] = $mo->table('codono_invit')->add(array('coin' =>$quote['base'],'userid' => $Otc['ownerid'], 'invit' => $uid, 'name' => $quote['base'], 'type' => "OTC $order_type:" . $Otc['coinname'], 'num' => $qty, 'mum' => $final_total, 'fee' => $site_profit, 'addtime' => time(), 'status' => 1));
        }

        //Tier Distribution
        if ($profit > 0) {
            if ($tier_1 > 0 && $tier_1_user > 0) {
                $rs[] = $mo->table('codono_user_coin')->where(array('userid' => $tier_1_user))->setInc($quote['base'], $tier_1);
                $rs[] = $mo->table('codono_invit')->add(array('coin' =>$quote['base'],'userid' => $tier_1_user, 'invit' => $uid, 'name' => $quote['base'], 'type' => "OTC $order_type:" . $Otc['coinname'], 'num' => $qty, 'mum' => $final_total, 'fee' => $tier_1, 'addtime' => time(), 'status' => 1));
            }
            if ($tier_2 > 0 && $tier_2_user > 0) {
                $rs[] = $mo->table('codono_user_coin')->where(array('userid' => $tier_2_user))->setInc($quote['base'], $tier_2);
                $rs[] = $mo->table('codono_invit')->add(array('coin' =>$quote['base'],'userid' => $tier_2_user, 'invit' => $uid, 'name' => $quote['base'], 'type' => "OTC $order_type:" . $Otc['coinname'], 'num' => $qty, 'mum' => $final_total, 'fee' => $tier_2, 'addtime' => time(), 'status' => 1));
            }
            if ($tier_3 > 0 && $tier_3_user > 0) {
                $rs[] = $mo->table('codono_user_coin')->where(array('userid' => $tier_3_user))->setInc($quote['base'], $tier_3);
                $rs[] = $mo->table('codono_invit')->add(array('coin' =>$quote['base'],'userid' => $tier_3_user, 'invit' => $uid, 'name' => $quote['base'], 'type' => "OTC $order_type:" . $Otc['coinname'], 'num' => $qty, 'mum' => $final_total, 'fee' => $tier_3, 'addtime' => time(), 'status' => 1));
            }

        }
        if (check_arr($rs)) {
            $mo->commit();
            $exit_data['status'] = 1;
            $exit_data['msg'] = 'Your trade was successful';
            $exit_data['data'] = $otc_log_id;
            $exit_data['url'] = SITE_URL . 'Otc/log/id/' . $otc_log_id;
            exit(json_encode($exit_data));
        } else {
            $mo->rollback();
            $this->error('For some reasons we could not do this trade,Please try again later!');
        }

    }

    /**
     * @param $id
     * @return void
     */
    public function log($id = 0)
    {
        $uid = userid();
        if (!$uid) {
            redirect(U('Login/login'));
        }
        if (!check($id, 'd')) {
            redirect(U('Otc/records'));
        }
        $where['id'] = $id;
        $where['status'] = array('egt', 1);
        $where['userid'] = $uid;
        $otc_log = M('OtcLog');


        $single_record = $otc_log->where($where)->find();

        $this->assign('record', $single_record);
        $this->display();
    }

    /**
     * @param $ls
     * @return void
     */
    public function records($ls = 15)
    {
        $uid = userid();
        if (!$uid) {
            redirect(U('Login/login'));
        }

        $where['status'] = array('egt', 1);
        $where['userid'] = $uid;
        $otc_log = M('OtcLog');
        $count = $otc_log->where($where)->count();
        $Page = new Page($count, $ls);
        $show = $Page->show();
        $list = $otc_log->where($where)->order('id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->display();
    }

    private function getConversion($fromcoin = 'USD', $amount = 1, $tocoin = 'USD')
    {
        $fromcoin = strtoupper($fromcoin);
        $tocoin = strtoupper($tocoin);
        if ($tocoin != 'USD') {
            //Grab price from tocoin to USD
            $conv = $this->getConversion($tocoin);
            $mux = bcdiv(1, $conv, 8); //Multiplier for conversion
        } else {
            $mux = 1; //Multiplier for conversion
        }
        $price = M('Coinmarketcap')->where(['symbol' => $fromcoin])->field('price_usd')->find();
        if ($price) {
            $price = $price['price_usd'];
        } else {
            $price = null;
        }

        $new_amount = bcmul($price, $amount, 8);
        return bcmul($mux, $new_amount, 8);
    }

    private function getOvexQuote($params)
    {
        $query = '?start=1';
        foreach ($params as $key => $param) {
            if ($param) {
                $query .= "&$key=$param";
            }

        }
        if ($params['type'] == 'buy') {
            $query .= '&to_amount=1';
        } else {
            $query .= '&from_amount=1';
        }
        if ($this->ovex_prod = 1) {
            $header = array('Content-Type: application/json', 'charset: UTF - 8', 'Authorization: Bearer ' . $this->ovex_api_key);
        } else {
            $header = array();
        }

        $endpoint = "https://www.ovex.io/api/v2/rfq/get_quote?$query";
        $resp = json_decode(static::gcurl($endpoint, 'GET', $header));
        if ($resp->error || !$resp->rate > 0) {
            return 0.000000;
        } else {
            return $resp->rate;
        }
    }

    private function acceptOvexQuote($quote_token)
    {

        if ($this->ovex_prod = 1) {
            $header = array('Authorization: Bearer ' . $this->ovex_api_key);
        } else {
            $header = array();
        }

        $endpoint = "https://www.ovex.io/api/v2/rfq/accept_quote?quote_token=$quote_token";

        return json_decode(static::gcurl($endpoint, 'POST', $header));
    }

}
