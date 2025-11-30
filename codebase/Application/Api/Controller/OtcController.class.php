<?php

namespace Api\Controller;

use Think\Exception;
use Think\Page;

class OtcController extends CommonController
{
    //Coins in which users can buy or sell they need to be listed on your exchange as well as huobi
    const DEFAULT_COIN_TRADE = ['symbol' => 'btc', 'image' => SITE_URL . 'Upload/coin/BTC.png'];
    const DEFAULT_COIN_BASE = ['symbol' => 'usdt', 'image' => SITE_URL . 'Upload/coin/USDT.png'];
    const BASE_COINS = ['zar','usd','usdt'];  // Payment method/ Frequency
    const QUOTE_LIFE = 20;
	const ROUNDABOUT=0;
	public function _initialize(){
		if(OTC_ALLOWED==0){
		$this->assign('type', 'Oops');
        $this->assign('error', 'Oops, Currently OTC is disabled!');
        $this->display('Content/error_specific');
		exit;
		}
		parent::_initialize();
	}

    public function index()
    {
        $where['status'] = ['neq', 0];
        $Model = M('Otc');
        $count = $Model->where($where)->count();
        $Page = new Page($count, 120);
        $show = $Page->show();

        $list = $Model->where($where)->order('sort asc,addtime desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();

        $otc_list = [];
        if (is_array($list)) {
            foreach ($list as $k => $v) {
                if ($v['img'] == null || $v['img'] == '') {
                    $v['img'] = 'default.png';
                }
                $img = c('coin')[$v['coinname']]['img'];
                $otc_list[] = ['symbol' => $v['coinname'], 'image' => SITE_URL . 'Upload/coin/' . $img, 'min' => $v['min'], 'max' => $v['max']];

            }

            unset($k);
            unset($v);
        } else {
            $otc_list = null;
        }
        $base_coins = [];
        foreach (self::BASE_COINS as $base) {
            /*$base_coins[$base]['image']='/Upload/coin/'.c('coin')[$base]['img'];
            $base_coins[$base]['symbol']=$base;
            $base_coins[$base]['min']=1;
            $base_coins[$base]['max']=1000000;*/
			if($base=='usdt' || $base=='usd' ){
			$max_allowed_base=1000000;
			}else{
			$max_allowed_base=5000000;
			}
            $base_coins[] = ['image' => SITE_URL . 'Upload/coin/' . c('coin')[$base]['img'], 'symbol' => $base, 'min' => 1, 'max' => $max_allowed_base];

        }

        $return['base_coins'] = $base_coins;
        $return['default_coin_base'] = self::DEFAULT_COIN_BASE;
        $return['default_coin_trade'] = self::DEFAULT_COIN_TRADE;
        $return['trade_coins'] = $otc_list;
        $this->ajaxShow($return);
    }

    public function getquote($trade_coin, $base_coin, $input1 = 0, $input2 = 0, $tradetype = 'buy')
    {
        $uid = $this->userid();
        $trade_coin = strtolower($trade_coin);
        $base_coin = strtolower($base_coin);

        $bcoins = self::BASE_COINS;
        if (!in_array($base_coin, $bcoins)) {
            $this->error(L('Invalid currency1'));
        }
        if (!check($trade_coin, 'n')) {
            $this->error(L('Invalid currency2!'));
        }

        $tcoin = M('Otc')->where(['status' => 1, 'coinname' => $trade_coin])->getField('coinname');
        $buy_commission = M('Otc')->where(['status' => 1, 'coinname' => $trade_coin])->getField('buy_commission');
		$sell_commission = M('Otc')->where(['status' => 1, 'coinname' => $trade_coin])->getField('sell_commission');
        if ($tcoin != $trade_coin) {
            $this->error(L('Invalid currency3!'));
        }

        if (!in_array($base_coin, $bcoins)) {
            $this->error(L('Invalid currency'));
        }
        if ($input1 > 0) {
            $input2 = 0;
        }

        $expire = self::QUOTE_LIFE - 3;
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
            $this->error("Incorrect Quantity provided !" . $quantity);
        }


        $symbol = strtolower($trade_coin . $base_coin);
        /*
		//$endpoint="https://api.huobi.pro/market/depth?symbol=$symbol&type=step1";
        //$response=json_decode($this->gcurl($endpoint,'GET'));
        if ($base_coin == 'zar') {
            $exchange = "binance";
        } else {
            $exchange = "houbi";
        }
        $response = $this->depth($trade_coin, $base_coin, $exchange);
        $check_offers = [];
        if ($response->status == 'ok') {
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
                $offer_qty = bcadd($offer_qty,$check[1],8);
                if (format_num($quantity) < format_num($offer_qty)) {
                    $offer_price = bcadd($offer_price,$check[0],8);
                    continue;
                } else {
                    $offer_price = bcadd($offer_price,$check[0],8);
                }

            }

            $avg_price=bcdiv($offer_price, $i, 8);
			*/
			$avg_price=$this->calcAvgPrice($trade_coin,$base_coin,$tradetype,$quantity);
			if (!isset($avg_price) || $avg_price=='' || $avg_price<=0) {
            $this->error("Please try again in sometime !");
			}
			
            if ($tradetype == 'buy') {
            $comm=bcdiv($buy_commission,100,8);
			$mux=bcadd(1,$comm,8);
			$final_price=bcmul($avg_price,$mux,8);
            }
            if ($tradetype == 'sell') {
            $comm=bcdiv($sell_commission,100,8);
			$mux=bcsub(1,$comm,8);
			$final_price=bcmul($avg_price,$mux,8);
            }
            if ($inverse == true) {
				$quantity=bcdiv($quantity,$final_price,8);	
            }
            //$offer=array($avg_price,$final_price,$offer_qty,$i,$check_offers);
            
            //clog('Quote_',json_encode(array('profit'=>$profit,'inverse'=>$inverse,'avg_price'=>$avg_price,'commission'=>$commission,'quantity'=>$quantity,'total'=>$total,'final_total'=>$final_total,'final_price'=>$final_price)));

            if($tradetype=='buy'){
				$diff=bcsub($final_price,$avg_price,8);
			}
			if($tradetype=='sell'){	
				$diff=bcsub($avg_price,$final_price,8);
			}
			$profit=bcmul($diff,$quantity,8);
			$total=(double)bcmul($avg_price,$quantity,8);
			$final_total=(double)bcmul($final_price,$quantity,8);
			if(self::ROUNDABOUT==0){
				$final_total=round($final_total,self::ROUNDABOUT);
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

            $user_balance = M('UserCoin')->where(['userid' => $uid])->field("id,userid,$spend_coin")->find();
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
                $status = 0;
            } else {

                $status = 1;
                $quote_id = $uid . '_' . time();
            }

            $quote = ['qid' => $quote_id, 'trade_type' => $tradetype, 'trade' => $trade_coin, 'base' => $base_coin, 'symbol' => $symbol, 'qty' => $quantity, 'final_price' => $final_price, 'final_total' => $final_total, 'profit' => $profit, 'addtime' => time()];
            $client_response['status'] = $status;
            $client_response['msg'] = $msg;
            $client_response['data'] = ['qid' => $quote_id, 'trade_type' => $tradetype, 'symbol' => $symbol, 'qty' => $quantity, 'price' => $final_price, 'total' => $final_total, 'trade' => strtoupper($trade_coin), 'base' => strtoupper($base_coin), 'expire' => $expire];
            session('quote', $quote);
            $mo = M();
            $saveme = ['qid' => $quote_id, 'data' => json_encode($quote)];

            $if_saved = $mo->table('codono_otc_quotes')->add($saveme);
            exit(json_encode(($client_response)));
    }

    public function approvequote($qid)
    {
        $uid = $this->userid();
		
		$split=explode("_", $qid);
		if($split[0]!=$uid){
			   $this->error('Invalid Quote ERROR:Q1');
		}
		

		
		
        $now=time();
		$mo = M();
        $where = ['qid' => $qid];
        $response = $mo->table('codono_otc_quotes')->where($where)->find();



        $quote = json_decode($response['data'], true);
		$quote_id = $response['qid'];
		
		
		$profit=$quote['profit'];
		
		if($split[1]!=$quote['addtime'] && $quote['addtime']!=0){
			   $this->error('Invalid Quote ERROR:U1');
		}	
		
		
        if($qid!=$quote_id ||$qid==0 || $qid=='' || $quote['final_total'] <= 0 || $quote['qty'] <= 0 || $quote['final_price'] <= 0 || $quote['profit'] <= 0  ){
			$this->error('This is invalid quote request, Please refresh and try again');
		}



        $lived = $now - $quote['addtime'];
        if (self::QUOTE_LIFE < $lived) {
            $this->error('Quote is expired already, Please reject and try again');
        }

        $Otc = M('Otc')->where(['coinname' => $quote['trade'], 'status' => 1])->find();
		

        if (!$Otc) {
            $this->error('Please try again later');
        }
		$cur_user_info = $mo->table('codono_user')->where(['id' => $uid])->field('invit_1,invit_2,invit_3')->find();
		
		$tier_1_user=$cur_user_info['invit_1'];
		$tier_1_percent=bcdiv($Otc['tier_1'],100,8);
		$tier_1=bcmul($profit,$tier_1_percent,8);
		
		$tier_2_user=$cur_user_info['invit_2'];
		$tier_2_percent=bcdiv($Otc['tier_2'],100,8);
		$tier_2=bcmul($profit,$tier_2_percent,8);
		
		
		$tier_3_user=$cur_user_info['invit_3'];
		$tier_3_percent=bcdiv($Otc['tier_3'],100,8);
		$tier_3=bcmul($profit,$tier_3_percent,8);
			
		$tier_commission_part1=bcadd($tier_1,$tier_2,8);
		$tier_commission_total=bcadd($tier_commission_part1,$tier_3,8);
		$site_profit=bcsub($profit,$tier_commission_total,8);
		
        $fees_percent = 0;//$Otc['fees'];
		$fee_percent=bcdiv($fees_percent,100,8);
		
        $fees_paid=bcmul($fee_percent,$quote['final_total'],8);
        $final_total = bcsub($quote['final_total'],$fees_paid,8);

        $qty = format_num($quote['qty']);


        //check balance
        $otc_insert = [
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
        ];

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



        $user_balances = $mo->table('codono_user_coin')->where(['userid' => $uid])->find();
        if ($user_balances[$spend_coin] < $spend_total) {
            $this->error('You dont have enough balance');
        }
        $mo->startTrans();
        $rs = [];

        try {

            $rs[] = $otc_log_id = $mo->table('codono_otc_log')->add($otc_insert);
            $rs[] = $mo->table('codono_user_coin')->where(['userid' => $uid])->setDec($spend_coin, $spend_total);
            $rs[] = $mo->table('codono_user_coin')->where(['userid' => $uid])->setInc($receive_coin, $receive_total);

            $finance_mum_user_coin = $mo->table('codono_user_coin')->where(['userid' => $uid])->find();

            $finance_hash = md5($quote['qid']);

            $xnum = bcadd($user_balances[$receive_coin], $user_balances[$receive_coind], 8);
            $mum = bcadd($finance_mum_user_coin[$receive_coin], $finance_mum_user_coin[$receive_coind], 8);
            $rs[] = $mo->table('codono_finance')->add(['userid' => $uid, 'coinname' => $receive_coin, 'num_a' => $user_balances[$receive_coin], 'num_b' => $user_balances[$receive_coind], 'num' => $xnum, 'fee' => $fees_paid, 'type' => $type, 'name' => 'otc', 'nameid' => $otc_log_id, 'remark' => "OTC $order_type:" . $Otc['coinname'], 'mum_a' => $finance_mum_user_coin[$receive_coin], 'mum_b' => $finance_mum_user_coin[$receive_coind], 'mum' => $mum, 'move' => $finance_hash, 'addtime' => time(), 'status' => 1]);

            if ($type == 1) {
                $rs[] = $mo->table('codono_otc')->where(['id' => $Otc['id']])->setInc('deal_buy', $qty);
            }
            if ($type == 2) {
                $rs[] = $mo->table('codono_otc')->where(['id' => $Otc['id']])->setInc('deal_sell', $qty);
            }
            //Increase Token Owners Balance token_owners_keep
            if ($Otc['ownerid'] > 0 && $profit > 0 && $site_profit > 0) {
                $rs[] = $mo->table('codono_user_coin')->where(['userid' => $Otc['ownerid']])->setInc($quote['base'], $site_profit);
                $rs[] = $mo->table('codono_invit')->add(['coin' => $quote['base'], 'userid' => $Otc['ownerid'], 'invit' => $uid, 'name' => $quote['base'], 'type' => "OTC $order_type:" . $Otc['coinname'], 'num' => $qty, 'mum' => $final_total, 'fee' => $site_profit, 'addtime' => time(), 'status' => 1]);
            }
            //Tier Distribution
            if ($profit > 0) {
                if ($tier_1 > 0 && $tier_1_user > 0) {
                    $rs[] = $mo->table('codono_user_coin')->where(['userid' => $tier_1_user])->setInc($quote['base'], $tier_1);
                    $rs[] = $mo->table('codono_invit')->add(['coin' => $quote['base'], 'userid' => $tier_1_user, 'invit' => $uid, 'name' => $quote['base'], 'type' => "OTC $order_type:" . $Otc['coinname'], 'num' => $qty, 'mum' => $final_total, 'fee' => $tier_1, 'addtime' => time(), 'status' => 1]);
                }
                if ($tier_2 > 0 && $tier_2_user > 0) {
                    $rs[] = $mo->table('codono_user_coin')->where(['userid' => $tier_2_user])->setInc($quote['base'], $tier_2);
                    $rs[] = $mo->table('codono_invit')->add(['coin' => $quote['base'], 'userid' => $tier_2_user, 'invit' => $uid, 'name' => $quote['base'], 'type' => "OTC $order_type:" . $Otc['coinname'], 'num' => $qty, 'mum' => $final_total, 'fee' => $tier_2, 'addtime' => time(), 'status' => 1]);
                }
                if ($tier_3 > 0 && $tier_3_user > 0) {
                    $rs[] = $mo->table('codono_user_coin')->where(['userid' => $tier_3_user])->setInc($quote['base'], $tier_3);
                    $rs[] = $mo->table('codono_invit')->add(['coin' => $quote['base'], 'userid' => $tier_3_user, 'invit' => $uid, 'name' => $quote['base'], 'type' => "OTC $order_type:" . $Otc['coinname'], 'num' => $qty, 'mum' => $final_total, 'fee' => $tier_3, 'addtime' => time(), 'status' => 1]);
                }

            }
        }catch(Exception $exception){
            clog('otc_approvequote',$exception->getMessage());
            $mo->rollback();
            $this->error('For some reasons we could not do this trade,Please try again later!');
        }

        if (strict_check_arr($rs)) {
            $mo->execute('commit');
            $exit_data['status'] = 1;
            $exit_data['msg'] = 'Your trade was successful';
            $exit_data['data'] = $quote['qid'];
            $exit_data['url'] = 'log/id/' . $otc_log_id;
            exit(json_encode($exit_data));
        } else {
            $mo->rollback();
            $this->error('For some reasons we could not do this trade,Please try again later!');
        }

    }
	private function calcAvgPrice($c1,$c2,$tradetype,$quantity)
    {

        $c2=strtoupper($c2);
        if($c2=='ZAR')
        {$exchange="binance";}
        else{$exchange="houbi";}


        $conv_required=0;
        switch($c2){
            case 'USD':
            $concatSymbol=$c1 . 'USDT';
            $conv_required=0;
            break;

            case 'UGX':
            $concatSymbol=$c1 . 'USDT';
            $conv_required=1;
            break;

            default;
            case 'USDT';
            $conv_required=0;
            $concatSymbol=$c1 . $c2;
        }



        if ($exchange == 'huobi') {
            $symbol = strtolower($concatSymbol);
            $endpoint = "https://api.huobi.pro/market/depth?symbol=$symbol&type=step1";
            $response = json_decode($this->gcurl($endpoint, 'GET'));
            $return = (object)[];
            if ($response->status == 'ok') {
                $return->status = 'ok';
                $return->bids = $response->tick->bids;
                $return->asks = $response->tick->asks;
            } else {
                $return->status = 'error';
                $return->bids = "";
                $return->asks = "";
            }
        } else {
            $symbol = strtoupper($concatSymbol);
            $endpoint = "https://api.binance.com/api/v1/depth?symbol=$symbol&limit=20";
            $response = json_decode($this->gcurl($endpoint, 'GET'));
            $return = (object)[];
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

            $check_offers = [];

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
            $avg_price = bcdiv($offer_price, $i, 8);
            if($conv_required==1){
                $conversion = $this->getConversion($c2,1);
                $avg_price=bcdiv($avg_price,$conversion,8);
            }

            return $avg_price;

        }
    }


    private function depth($c1, $c2, $exchange = 'binance')
    {
		if(strtoupper($c2)=='USD'){
		$c2="USDT";
		}
        if ($exchange == 'huobi') {
            $symbol = strtolower($c1 . $c2);
            $endpoint = "https://api.huobi.pro/market/depth?symbol=$symbol&type=step1";
            $response = json_decode($this->gcurl($endpoint, 'GET'));
            $return = (object)[];;
            if ($response->status == 'ok') {
                $return->status = 'ok';
                $return->bids = $response->tick->bids;
                $return->asks = $response->tick->asks;
            } else {
                $return->status = 'error';
                $return->bids = "";
                $return->asks = "";
            }
        } else {
            $symbol = strtoupper($c1 . $c2);
            $endpoint = "https://api.binance.com/api/v1/depth?symbol=$symbol&limit=20";
            $response = json_decode($this->gcurl($endpoint, 'GET'));
            $return = (object)[];;
            if ($response->lastUpdateId > 0) {
                $return->status = 'ok';
                $return->bids = $response->bids;
                $return->asks = $response->asks;
            } else {
                $return->status = 'error';
                $return->bids = "";
                $return->asks = "";
            }
        }
        return $return;
    }

    public function log($qid = 0)
    {
        $uid = $this->userid();


        if (!preg_match('/\d+[_]\d+/', $qid)) {
            $this->error("Please check record!");
        }
        $qid = $qid ? $qid : 0;
        $where['qid'] = $qid;
        $where['status'] = ['egt', 1];
        $where['userid'] = $uid;
        $OtcLog = M('OtcLog');


        $single_record = $OtcLog->where($where)->find();

        if ($single_record) {
            $to_show['price'] = $single_record['final_price'];
            $to_show['base_coin'] = $single_record['base_coin'];
            $to_show['trade_coin'] = $single_record['trade_coin'];
            $to_show['fees'] = $single_record['fees_paid'];
            $to_show['total'] = $single_record['final_total'];
            $to_show['qty'] = $single_record['qty'];
            $to_show['type'] = $single_record['type'];
            $to_show['addtime'] = $single_record['addtime'];
            $exit_data['status'] = 1;
            $exit_data['msg'] = 'Opening Trade record';
            $exit_data['data'] = $to_show;
            exit(json_encode($exit_data));
        } else {
            $exit_data['status'] = 0;
            $exit_data['msg'] = 'No such record found';
            $exit_data['data'] = [];
            exit(json_encode($exit_data));
        }
    }

    public function records($ls = 15)
    {
        $uid = $this->userid();

        $where['status'] = ['egt', 1];
        $where['userid'] = $uid;
        $OtcLog = M('OtcLog');
        $count = $OtcLog->where($where)->count();
        $Page = new Page($count, $ls);
        $show = $Page->show();
        $list = $OtcLog->where($where)->order('id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $return['list'] = $list;
        $return['page'] = $show;
        $this->assign('page', $show);
        $this->ajaxShow($return);
    }

    private function gcurl($endpoint, $method = 'GET')
    {
        if (!$endpoint) {
            return "{'error':'No URL'}";
        }
        $call_url = $endpoint;
        $curl = curl_init();
        curl_setopt_array($curl, [

            CURLOPT_URL => $call_url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_SSL_VERIFYPEER => 0,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => $method,
        ]);

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
            return "cURL Error #:" . $err;
        } else {
            return $response;
        }
    }
	
	private function getConversion($fromcoin='USD',$amount=1,$tocoin='USD')
    {
		$mux=1; //Multiplier for conversion
		$fromcoin=strtoupper($fromcoin);
		$tocoin=strtoupper($tocoin);
		if($tocoin!='USD'){
			//Grab price from tocoin to USD

			$conv=$this->getConversion($tocoin);
			$mux=bcdiv(1,$conv,8);
			
		}
		else{
			$mux=1;
		}
        $price = M('Coinmarketcap')->where(["symbol" => $fromcoin])->field('price_usd')->find();
		if ($price) {
			$price=$price['price_usd'];
        } else {
            $price = null;
        }
		
		$new_amount=bcmul($price ,$amount,8);
		$final=bcmul($mux ,$new_amount,8);
        return $final;
    }

}
