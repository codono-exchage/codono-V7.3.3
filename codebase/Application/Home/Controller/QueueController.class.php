<?php

namespace Home\Controller;


use Think\Exception;

class QueueController extends HomeController
{
    public const CONSUME_STAKING_BALANCE = USE_STAKING_BALANCE?:0;

    public function __construct()
    {
        parent::__construct();
        checkcronkey();
    }


    private function stakinginfo($id)
    {
        $Model = M('Staking');
        $where['id'] = $id;
        $where['status'] = 1;
        return $Model->where($where)->find();
    }

    public function checkStaking()
    {
        cronLastRun(__FUNCTION__);
        $mo = M();
        $now = time();
        $map['endtime'] = ['lt', $now];
        $map['status'] = 1;
        $logs = M('StakingLog')->where($map)->order('id asc')->limit(500)->select();

        if (!is_array($logs)) {
            echo 'Ok No records to be proceeded';
            exit;
        } else {
            echo count($logs) . ' Record/s found to be processed<br/>';

        }
        foreach ($logs as $ibl) {

            if ($ibl['status'] != 1) {
                print_r($ibl);
                echo 'Status=' . $ibl['status'];
                continue;
            }
            $bonus=bcsub($ibl['maturity'],$ibl['amount'],8);
            $userid = $ibl['userid'];

            $credit = format_num($ibl['maturity'], 8);

            $staking_info = $this->stakinginfo($ibl['staking_id']);

            if ($staking_info == NULL || $staking_info['status']==0) {
                $mo->table('codono_staking_log')->where(['id' => $ibl['id']])->save(['status' => 0, 'withdrawn' => time(), 'credited' => $credit]);
                continue;
            }
            if (!$staking_info['coinname']) {
                echo 'Coin Name not found<br/>';
                continue;
            }
            $coinname =$coin_check = strtolower($staking_info['coinname']);
            $mo->startTrans();
            $rs = [];
            try{
                if (self::CONSUME_STAKING_BALANCE == 1) {
                    $current_balance = $this->coinBalance($coin_check);
                    $freeze_balance = 0;// find
                    $require_wallet = 'staking';
                } else {
                    $user_coin = M('UserCoin')->where(['userid' => $userid])->find();
                    $current_balance = $user_coin[$coin_check];
                    $freeze_balance = $user_coin[$coin_check . 'd'];
                    $require_wallet = 'spot';
                }

                $mum_a = bcadd($current_balance, $credit, 8);
                $mum_b = $freeze_balance;
                $num = bcadd($current_balance, $freeze_balance, 8);
                $mum = bcadd($num, $credit, 8);
                $tier_bonus=[];
                if (0 < $credit) {
                    if (self::CONSUME_STAKING_BALANCE == 1) {
                        $condition = ['uid' => $userid, 'coin' => $coinname, 'account' => 4];
                        $found = $mo->table('codono_user_assets')->where($condition)->find();
                        if (!$found) {
                            $rs[] = $mo->table('codono_user_assets')->add($condition);
                        }
                        $rs[] = $mo->table('codono_user_assets')->where($condition)->setInc('balance', $credit);
                         $tiers_users=$this->inviteTiers($userid);
                         print_r([$staking_info['invite_1'],$tiers_users]);
                        if($staking_info['invite_1'] > 0 && $staking_info['invite_1']<100 && $tiers_users['invite_1']){
                            
                            $tier_1_bonus=bcmul(bcdiv($bonus,100,8),$staking_info['invite_1']);
                            if($tier_1_bonus>0){
                                $tier_bonus['lvl1']=$tier_1_bonus;
                                $rs[] = $mo->table('codono_user_coin')->where(['userid' =>  $tiers_users['invite_1']])->setInc($coinname, $tier_1_bonus);
                                $rs[] = $mo->table('codono_invit')->add(['coin'=>$coinname,'userid' => $tiers_users['invite_1'], 'invit' => $userid, 'name' => $coinname, 'type' => 'Staking:1st Tier', 'num' => $tier_1_bonus, 'mum' =>$tier_1_bonus, 'fee' => $tier_1_bonus, 'addtime' => time(), 'status' => 1]);
                            }
                            
                        }
                        if($staking_info['invite_2'] > 0 && $staking_info['invite_2']<100 && $tiers_users['invite_2']){
                            $tier_2_bonus=bcmul(bcdiv($bonus,100,8),$staking_info['invite_2']);
                            if($tier_2_bonus>0){
                                $tier_bonus['lvl2']=$tier_2_bonus;
                                $rs[] = $mo->table('codono_user_coin')->where(['userid' =>  $tiers_users['invite_2']])->setInc($coinname, $tier_2_bonus);
                                $rs[] = $mo->table('codono_invit')->add(['coin'=>$coinname,'userid' => $tiers_users['invite_2'], 'invit' => $userid, 'name' => $coinname, 'type' => 'Staking:2nd Tier', 'num' => $tier_2_bonus, 'mum' =>$tier_2_bonus, 'fee' => $tier_2_bonus, 'addtime' => time(), 'status' => 1]);
                            }
                        }
                        if($staking_info['invite_3'] > 0 && $staking_info['invite_3']<100 && $tiers_users['invite_3']){
                            $tier_3_bonus=bcmul(bcdiv($bonus,100,8),$staking_info['invite_3']);
                            if($tier_3_bonus>0){
                                $tier_bonus['lvl3']=$tier_3_bonus;
                                $rs[] = $mo->table('codono_user_coin')->where(['userid' =>  $tiers_users['invite_3']])->setInc($coinname, $tier_3_bonus);
                                $rs[] = $mo->table('codono_invit')->add(['coin'=>$coinname,'userid' => $tiers_users['invite_3'], 'invit' => $userid, 'name' => $coinname, 'type' => 'Staking:3rd Tier', 'num' => $tier_3_bonus, 'mum' =>$tier_3_bonus, 'fee' => $tier_3_bonus, 'addtime' => time(), 'status' => 1]);
                            }
                        }


                    } else {
                        $rs[] = $mo->table('codono_user_coin')->where(['userid' => $userid])->setInc($coinname, $credit);
                    }
                }
                $move_stamp = '3_' . $ibl['docid'];
                $rs[] = $mo->table('codono_staking_log')->where(['id' => $ibl['id']])->save(['status' => 3, 'withdrawn' => time(), 'credited' => $credit]);
                $finance_array = ['userid' => $userid, 'coinname' => $coinname, 'num_a' => $current_balance, 'num_b' => $credit, 'num' => $num, 'fee' => $credit, 'type' => 1, 'name' => 'staking_reward', 'nameid' => $ibl['id'], 'remark' => 'staking_maturity', 'move' => $move_stamp, 'addtime' => time(), 'status' => 1, 'mum' => $mum, 'mum_a' => $mum_a, 'mum_b' => $mum_b];
                $rs[] = $mo->table('codono_finance')->add($finance_array);
            
            } catch(Exception $e) {
                $mo->rollback();
                clog('queue_checkStaking',$e->getMessage());
                echo 'Invalid status of investment !<br/>';
                continue;
            }

            if (strict_check_arr($rs)) {
                $mo->commit();
                echo "Bonus Distribution<br/>";
                print_r($tier_bonus);
                echo $ibl['docid'] . ' staking has been credited!<br/>';
            } else {
                clog('checkStaking',[$rs,$mo->getLastSql()]);
                $mo->rollback();
                echo 'Staking could not be credited!<br/>';
            }

        }
        echo 'End of checking';
    }

    private function inviteTiers($uid){
        
         $userInfo = M('User')->where(array('id' => $uid))->find();
        return ['invite_1'=>userid($userInfo['invit_1'],'id'),'invite_2'=>userid($userInfo['invit_2'],'id'),'invite_3'=>userid($userInfo['invit_3'],'id')];
    }

    private function array_multi_unique($multiArray)
    {

        $uniqueArray = [];

        foreach ($multiArray as $subArray) {

            if (!in_array($subArray, $uniqueArray)) {
                $uniqueArray[] = $subArray;
            }
        }
        return $uniqueArray;
    }
    private function mapKucoinToBinanceFormat($kucoinData) {
        $mappedData = [];
        $ticker=json_decode($kucoinData,true);
        
        foreach ($ticker['data']['ticker'] as $data) {
        
            $symbol = str_replace('-', '', $data['symbol']);

            // Directly map and convert fields where applicable
            $priceChange = $data['changePrice'];
            $priceChangePercent = (float)$data['changeRate'] * 100; // Convert decimal to percentage
            $weightedAvgPrice = $data['averagePrice'] ?? 0; // Use null coalescing operator in case it's not provided
            $prevClosePrice = $data['last']; // Assuming 'last' as the previous close price
            $lastQty = 0; // Placeholder, as direct equivalent may not be available
            $openTime = 0; // Placeholder, as direct equivalent may not be available
            $closeTime = 0; // Placeholder, as direct equivalent may not be available
            $firstId = -1; // Placeholder, as direct equivalent may not be available
            $lastId = time().'000'; // Placeholder, as direct equivalent may not be available
            $count = 0; // Placeholder, as direct equivalent may not be available
    
            $mappedData[] = [
                'symbol' => $symbol,
                'priceChange' => $priceChange,
                'priceChangePercent' => $priceChangePercent,
                'weightedAvgPrice' => $weightedAvgPrice,
                'prevClosePrice' => $prevClosePrice,
                'lastPrice' => $data['last'],
                'lastQty' => $lastQty,
                'bidPrice' => $data['buy'],
                'bidQty' => $data['bestBidSize'],
                'askPrice' => $data['sell'],
                'askQty' => $data['bestAskSize'],
                'openPrice' => $data['open'], // Assuming 'open' is correctly provided
                'highPrice' => $data['high'],
                'lowPrice' => $data['low'],
                'volume' => $data['vol'],
                'quoteVolume' => $data['volValue'],
                'openTime' => $openTime,
                'closeTime' => $closeTime,
                'firstId' => $firstId,
                'lastId' => $lastId,
                'count' => $count,
            ];
    
        }
    
        return $mappedData;
    }
    
    /* Function to Get Market Prices from Binance and save it to codono_binance table */
    public function BinanceUpdate($engine='binance')
    {
        cronLastRun(__FUNCTION__);
        if (!timeToDoTask(__FUNCTION__, 60)) {
            //exit('This can be called once in a min , you have already called once');
        }
        $flag = 1;
        $data_convert=0;
        switch($engine){
            case 'binance':
                $exchange_url='https://api.binance.com/api/v3/ticker/24hr';
                break;
            case 'kucoin':     
                $exchange_url='https://api.kucoin.com/api/v1/market/allTickers';
                $data_convert=1;
                break;
            default:
            $exchange_url='https://api.binance.com/api/v3/ticker/24hr';
        }
        if($engine=='binance'){
                $exchange_url='https://api.binance.com/api/v3/ticker/24hr';
        }

        G('begin');
        try {
            $request = $this->gcurl($exchange_url);
        } catch (Exception $e) {
            var_dump($e->getMessage());
        }
        
        $mo = M();

        if (!isset($request) || $request != null) {
            if($data_convert==1){
             $response=   $this->mapKucoinToBinanceFormat($request);
            }
            else{
                $response = json_decode($request, true);  
            }
            

            if ($response) {

                $pairs = $this->array_multi_unique($this->limitBinanceResults($response));
                $bin_data = $mo->table('codono_binance')->order('id asc')->limit(300)->select();

                foreach ($pairs as $pair) {

                    //$info=$bin_data[$pair['symbol']];
                    foreach ($bin_data as $bin) {
                        if (strtolower($pair['symbol']) == strtolower($bin['symbol'])) {
                            $info = $bin;
                            break;
                        } else {
                            //	continue;
                            $info = false;
                        }

                    }

                    if (!isset($info) || strtolower($pair['symbol']) != strtolower($info['symbol'])) {
                        $saveData = $this->binanceConvert($pair);
                        $if_saved = $mo->table('codono_binance')->add($saveData);
                        echo "Saved status:$if_saved for market <strong>" . $pair['symbol'] . '</strong> to DB<br/>';
                        //Add pair required
                        continue;
                    }

                    if (strtolower($pair['symbol']) == strtolower($info['symbol'])) {
                        //Update required
                        $saveData = $this->binanceConvert($pair);
                        $if_updated = $mo->table('codono_binance')->where(['symbol' => $info['symbol']])->save($saveData);
                        echo "Update1 status:$if_updated for pair <strong>" . $pair['symbol'] . '</strong> to DB<br/>';
                    }

                }

            } else {
                //   die('Data,timestamp');
                echo "<br/><span style='color:red' >Improper Binance response</span>";
                echo '<pre>';
                print_r($response);
                print_r($request);
                echo '</pre>';
                $flag = 0;
            }
        } else {
            echo "<br/><span style='color:red' >Could not get Binance response</span>";
            echo '<pre>';
            print_r($request);
            echo '</pre>';
            $flag = 0;
        }
        G('end');
        echo '<br/>Total Time taken ' . G('begin', 'end') . 's';

    }

    public function ExchangeBinanceUpdate()
    {
        cronLastRun(__FUNCTION__);
        if (!timeToDoTask(__FUNCTION__, 60)) {
            //exit('This can be called once in a min , you have already called once');
        }
        echo '<br/> Updating Market Table now<br/>';
        G('1_begin');
        $this->Binance2Market();
        G('1_end');
        echo '<br/>Total Time taken ' . G('1_begin', '1_end') . 's';

    }

    private function limitBinanceResults($binance_coins)
    {
        $markets = C('market');
        $processed = [];
        $coins_array = [];
        foreach ($markets as $market) {
            $first_coin = explode('_', $market['name'])[0];
            $last_coin = explode('_', $market['name'])[1];
            //Because binance does not have usd market but usdt market
            if (strtolower($last_coin) == 'usd') {
                $last_coin = 'usdt';
            }
            $bin_pair = strtoupper($first_coin . $last_coin);
            $found_entry = array_search($bin_pair, array_column($binance_coins, 'symbol'));
            $if_processed = in_array($bin_pair, $processed);
            if ($if_processed) {
                continue;
            }
            if ($found_entry >= 0) {
                $coins_array[] = $binance_coins[$found_entry];
            }
            $processed[] = $bin_pair;
        }

        return $coins_array;
    }

    private function binanceConvert($data)
    {
        $response = [
            'symbol' => $data['symbol'],
            'priceChange' => $data['priceChange'],
            'priceChangePercent' => $data['priceChangePercent'],
            'weightedAvgPrice' => $data['weightedAvgPrice'],
            'prevClosePrice' => $data['prevClosePrice'],
            'lastPrice' => $data['lastPrice'],
            'lastQty' => $data['lastQty'],
            'bidPrice' => $data['bidPrice'],
            'bidQty' => $data['bidQty'],
            'askPrice' => $data['askPrice'],
            'askQty' => $data['askQty'],
            'openPrice' => $data['openPrice'],
            'highPrice' => $data['highPrice'],
            'lowPrice' => $data['lowPrice'],
            'volume' => $data['volume'],
            'quoteVolume' => $data['quoteVolume'],
        ];
        if ($data['firstId']) {
            $response['firstId'] = $data['firstId'];
        }
        if ($data['lastId']) {
            $response['lastId'] = $data['lastId'];
        }

        return $response;
    }

    private function Binance2Market()
    {
        $markets = C('market');
        
        $binancePairs = M('Binance')->select();
        /* foreach ($binancePairs as $binancePair) {
             $bininfo[$binancePair['symbol']] = $binancePair;
         }
        */

        foreach ($markets as $market) {

            if ($market['ext_price_update'] == 1 && $market['ext_orderbook'] == 1) {
                $first_coin = explode('_', $market['name'])[0];
                $last_coin = explode('_', $market['name'])[1];
                //Because binance does not have usd market but usdt market
                if (strtolower($last_coin) == 'usd') {
                    $last_coin = 'usdt';
                }
                $bin_pair = strtoupper($first_coin . $last_coin);

                $bmarket = $this->bmarket($bin_pair);
                echo '<br/>Market=' . $bmarket;
                array_unshift($binancePairs, ['symbol' => '00000']);
                $if_found = array_search($bmarket, array_column($binancePairs, 'symbol'));
                
                if ($if_found) {
                    $binTableData = $binancePairs[$if_found];

                    $res = $this->MapBin2Market($market['name'], $binTableData);

                    echo '********************<br/>Updated ' . $market['name'] . '<br/>';
                }
                S('getJsonTop' . $market['name'], 0);


            }
        }
    }

    private function bmarket($market)
    {
        return strtoupper(str_replace('_', '', $market));
    }

    private function MapBin2Market($market, $binTableData)
    {
        $return['market_update'] = 0;
        $return['market_orderbook'] = 0;
        $first_coin = explode('_', $market)[0];
        $last_coin = explode('_', $market)[1];
        if (strtolower($last_coin) == 'usd') {
            $last_coin = 'usdt';
        }
        $search_pair = strtoupper($first_coin . $last_coin);

        $found_pair = $binTableData;//M('Binance')->where(array('symbol' => $search_pair))->find();


        if (is_array($found_pair) && $found_pair != null) {
            if ($found_pair['lastprice']) {
                $upCoinData['new_price'] = $found_pair['lastprice'];
            }

            if ($found_pair['askprice']) {
                $upCoinData['buy_price'] = $found_pair['askprice'];
            }

            if ($found_pair['bidprice']) {
                $upCoinData['sell_price'] = $found_pair['bidprice'];
            }

            if ($found_pair['lowprice']) {
                $upCoinData['min_price'] = $found_pair['lowprice'];
            }

            if ($found_pair['highprice']) {
                $upCoinData['max_price'] = $found_pair['highprice'];
            }

            if ($found_pair['volume']) {
                $upCoinData['volume'] = $found_pair['volume'];
            }

            if ($found_pair['pricechangepercent']) {
                $upCoinData['change'] = $found_pair['pricechangepercent'];
            }
            $market_info_selected = M('Trade')->query('SELECT max(`flag`) as last_flag FROM `codono_trade` WHERE `market`="' . $market . '" and `userid`=0');
            $old_market_flag = $market_info_selected[0]['last_flag'];

            if ($old_market_flag <= $found_pair['lastid'] || $old_market_flag == NULL) {
                $update_required = true;
            } else {
                $update_required = false;
            }
            echo "<br/>Update required for market $market is $update_required";

            if (is_array($upCoinData)) {
                $return['market_update'] = M('Market')->where(['name' => strtolower($market)])->save($upCoinData);
            }


            if (C('market')[strtolower($market)]['ext_orderbook'] == 1 && $update_required) {

                $commission = C('market')[strtolower($market)]['orderbook_markup'];

                $number_of_orders = 30;
                $this->OrderBookGen($market, $found_pair['bidprice'], $found_pair['bidqty'], $found_pair['askprice'], $found_pair['askqty'], $found_pair['lastid'], $commission, $number_of_orders);
                $return['market_orderbook'] = 1;

            }
            /*
            else {
                echo $old_market_flag." and ".$found_pair['lastid'];
            }
            */


        }

        return $return;
    }

    //Create Orderbook using a price
    private function OrderBookClean($market, $lastid)
    {
        if ($lastid <= 0) {
            $condition = ['userid' => 0, 'market' => $market];
        } else {
            $condition = ['userid' => 0, 'market' => $market, 'flag' => ['lt', $lastid]];
        }

        return M('Trade')->where($condition)->delete();
    }

    private function OrderBookGen($market, $bidPrice, $bidQty, $askPrice, $askQty, $lastId, $commission, $number_of_orders)
    {

        if ($bidPrice <= 0 || $bidPrice == null || $askPrice < 0 || $askPrice == null) {
            return false;
        }
        $avgQty = bcdiv(bcadd($askQty, $bidQty, 8), 2, 8);
        $last['bidPrice'] = bcmul($bidPrice, bcsub(1, bcdiv($commission, 100, 8), 8), 8);
        $last['bidQty'] = $avgQty; //$bidQty;
        $last['askPrice'] = bcmul($askPrice, bcadd(1, bcdiv($commission, 100, 8), 8), 8);
        $last['askQty'] = $avgQty; //$askQty;

        $bid_stack = [];
        $ask_stack = [];
        for ($i = 0; $i < $number_of_orders; $i++) {
            $rand_sign1 = rand(1, 2);
            $rand_sign2 = rand(1, 2);
            $rand_bid_price = 1 - rand(10, 50) / 20000;
            $rand_ask_price = 1 + rand(10, 50) / 20000;

            if ($rand_sign1 == 1) {
                $rand_bid_qty = 1 + rand(10, 20) / 100;
            } else {
                $rand_bid_qty = 1 - rand(10, 20) / 100;
            }
            if ($rand_sign2 == 1) {
                $rand_ask_qty = 1 + rand(10, 20) / 100;
            } else {
                $rand_ask_qty = 1 - rand(10, 20) / 100;
            }


            $last['bidPrice'] = bcmul($last['bidPrice'], $rand_bid_price, 8);
            $last['bidQty'] = bcmul($last['bidQty'], $rand_bid_qty, 5);
            $last['askPrice'] = bcmul($last['askPrice'], $rand_ask_price, 8);
            $last['askQty'] = bcmul($last['askQty'], $rand_ask_qty, 5);

            if ($last['bidPrice'] <= 0 || $last['bidQty'] <= 0 || $last['askPrice'] <= 0 || $last['askQty'] <= 0) {
                break;
            }

            $bid_stack[$i]['market'] = $market;
            $bid_stack[$i]['price'] = $last['bidPrice'];
            $bid_stack[$i]['num'] = $last['bidQty'];
            $bid_stack[$i]['type'] = 1;
            $bid_stack[$i]['addtime'] = time();
            $bid_stack[$i]['flag'] = $lastId;

            $ask_stack[$i]['market'] = $market;
            $ask_stack[$i]['price'] = $last['askPrice'];
            $ask_stack[$i]['num'] = $last['askQty'];
            $ask_stack[$i]['type'] = 2;
            $ask_stack[$i]['addtime'] = time();
            $ask_stack[$i]['flag'] = $lastId;
        }


        $this->OrderBookClean($market, $lastId);
        // echo "<br/>Found $cleaned old records from $market orderbook , So deleted them, Now adding new Orderbook<br/>";
        $stacks = array_merge_recursive($bid_stack, $ask_stack);
        M('Trade')->addAll($stacks);

        $allow_trade_log = C('market')[$market]['ext_fake_trades'] ?: 0;

        if ($allow_trade_log == 1) {
            //  echo "<br/>Now adding Trade Logs<br/>";
            $this->SelfOrderLogGenerate($stacks);
            S('getTradelog' . $market, null);
        }
        //A('Trade')->callStopMatching($market,1);
        //A('Trade')->callStopMatching($market,2);
        A('Trade')->callMatchingTrade($market);

        //$ok=A('Ajax')->getActiveOrders($market,1,'json');
        S('getDepth', null);

        S('getActiveDepth' . $market, null);
        S('getActiveDepth', null);
        S('getDepthNew', null);
        S('getJsonTop' . $market, null);
        return true;
    }

    private function superQty($qty)
    {
        $rand_sign = rand(1, 2);

        if ($rand_sign == 1) {
            $rand_percent = 1 + rand(10, 20) / 100;
        } else {
            $rand_percent = 1 - rand(10, 20) / 100;
        }
        return bcmul($qty, $rand_percent, 5);
    }

    private function SelfOrderLogGenerate($_stacks)
    {

        shuffle($_stacks);
        shuffle($_stacks);

        $size = rand(0, 4);
        $stacks = [];
        for ($i = 0; $i <= $size; $i++) {
            $stacks[] = $_stacks[$i];
        }
        $stamp = time() - 60;

        $count = count($stacks);

        if (count($stacks) > 0) {
            foreach ($stacks as $stack) {

                $salt = $this->salt_stamp($size);
                $stamp = $stamp + $salt;
                $stack['userid'] = 0;
                $stack['peerid'] = 0;
                $stack['fee_buy'] = 0;
                $stack['fee_sell'] = 0;
                $stack['status'] = 1;
                $stack['addtime'] = $stamp;
                $stack['num'] = $this->superQty($stack['num']);//rand(bcmul($stack['num'],0.01,8),bcmul($stack['num'],0.25,8));
                $stack['mum'] = bcmul($stack['num'], $stack['price'], 8);

                unset($stack['flag']);
                unset($stack['deal']);
                unset($stack['fee']);
                M('TradeLog')->add($stack);
            }
        }
        echo "<br/>Added $count trade_log records";
    }

    /**
     * @param $size
     * @return int
     */
    private function salt_stamp($size): int
    {
        return (int)bcadd($size, rand(1, 10), 0);

    }
    //Binance code ends
    //Coincap.io pricing updates to codono_coinmarketcap table for 100 top coins
    public function cmcUpdate()
    {
        cronLastRun(__FUNCTION__);
        S('cmcrates', null);
        S('cmcupdate', null);
        S('home_coin', null);
        $timestamp = time();
        //Create table if it doesn't exist
        echo 'Starting to retrive at:' . $timestamp . '<br/>';
        $request = $this->gcurl('https://api.coincap.io/v2/assets');

        $mo = M();

        if (!isset($request) || $request != null) {

            $response = json_decode($request, true);

            if ($response['data'] && $response['timestamp']) {

                $coins = $response['data'];
                foreach ($coins as $find_coin) {
                    if ($find_coin['symbol'] == 'BTC') {
                        $btc = $find_coin;
                    }
                }

                foreach ($coins as $coin) {

                    $info = $mo->table('codono_coinmarketcap')->where(['symbol' => $coin['symbol']])->find();
                    if (!$info) {
                        $map = $this->mapCoinCap($coin, $response['timestamp'], $btc);
                        $if_saved = $mo->table('codono_coinmarketcap')->add($map);
                        echo "Saved status:$if_saved for coin <strong>" . $coin['name'] . '</strong> to DB<br/>';
                        //Add coin required
                        continue;
                    }
                    if ($coin['symbol'] == $info['symbol'] && $info['last_updated'] < $response['timestamp']) {
                        //Update required
                        $map = $this->mapCoinCap($coin, $response['timestamp'], $btc);
                        $if_updated = $mo->table('codono_coinmarketcap')->where(['symbol' => $info['symbol']])->save($map);
                        echo "Update status:$if_updated for coin <strong>" . $coin['symbol'] . '</strong> to DB<br/>';
                    }

                }

            } else {
                die('Data,timestamp');
            }
        } else {
            exit('Could not get response');
        }
    }

    public function cmcUpdateRate()
    {
        cronLastRun(__FUNCTION__);
        $timestamp = time();
        //Create table if it doesn't exist
        echo 'Starting to retrive at:' . $timestamp . '<br/>';
        $request = $this->gcurl('https://api.coincap.io/v2/rates');
        $mo = M();

        if (!isset($request) || $request != null) {

            $response = json_decode($request, true);

            if ($response['data'] && $response['timestamp']) {

                $coins = $response['data'];

                foreach ($coins as $find_coin) {
                    if ($find_coin['symbol'] == 'BTC') {
                        $btc = $find_coin;
                    }
                }
                $all_coins = $mo->table('codono_coinmarketcap')->field('symbol,price_usd,price_btc')->select();
                F('cmcRates', $all_coins);
                foreach ($coins as $coin) {

                    $info = $mo->table('codono_coinmarketcap')->where(['symbol' => $coin['symbol']])->find();

                    if (!$info) {
                        $map = $this->mapCoinCap($coin, $response['timestamp'], $btc);
                        $if_saved = $mo->table('codono_coinmarketcap')->add($map);
                        echo "Saved status:$if_saved for coin <strong>" . $coin['name'] . '</strong> to DB<br/>';
                        //Add coin required
                        continue;
                    }

                    if ($coin['symbol'] == $info['symbol']) {
                        //Update required
                        $timestamp = time() * 1000;

                        $map = $this->rateCoinCap($coin, $timestamp, $btc);

                        $if_updated = $mo->table('codono_coinmarketcap')->where(['symbol' => $info['symbol']])->save($map);
                        echo "Update status:$if_updated for coin <strong>" . $coin['symbol'] . '</strong> to DB<br/>';
                    }

                }

            } else {
                die('Data,timestamp');
            }
        } else {
            exit('Could not get response');
        }
    }

    private function mapCoinCap($info, $timestamp, $btc = [])
    {

        $increament_percetage = 0;
        $coin['id'] = $info['id'];
        $coin['name'] = $info['name'];
        $coin['symbol'] = $info['symbol'];
        $coin['rank'] = $info['rank'];
        if ($btc['priceUsd'] > 0) {
            $coin['price_btc'] = format_num($btc['priceUsd'] / $info['priceUsd'], 8);
        }
        $coin['price_usd'] = format_num(($info['priceUsd'] * (100 + $increament_percetage)) / 100, 8);
        $coin['total_supply'] = format_num($info['supply'], 0);
        $coin['max_supply'] = format_num($info['maxSupply'], 0);
        $coin['available_supply'] = format_num($info['maxSupply'], 0);
        $coin['percent_change_24h'] = format_num($info['changePercent24Hr'], 2);
        $coin['24h_volume_usd'] = format_num($info['volumeUsd24Hr']);
        $coin['market_cap_usd'] = format_num($info['marketCapUsd'], 0);
        $coin['last_updated'] = $timestamp;
        return $coin;
    }

    private function rateCoinCap($info, $timestamp, $btc = [])
    {

        $increament_percetage = 0;
        $coin['id'] = $info['id'];
        //$coin['name']=$info['name'];
        $coin['symbol'] = $info['symbol'];
        //$coin['rank']=$info['rank'];

        if ($btc['rateUsd'] > 0) {
            $coin['price_btc'] = format_num($btc['rateUsd'] / $info['rateUsd'], 8);
        }
        $coin['price_usd'] = format_num(($info['rateUsd'] * (100 + $increament_percetage)) / 100, 8);
        /*	$coin['total_supply']=format_num($info['supply'],0);
            $coin['max_supply']=format_num($info['maxSupply'],0);
            $coin['available_supply']=format_num($info['maxSupply'],0);
            $coin['percent_change_24h']=format_num($info['changePercent24Hr'],2);
            $coin['24h_volume_usd']=format_num($info['volumeUsd24Hr']);
            $coin['market_cap_usd']=format_num($info['marketCapUsd'],0);
            $coin['last_updated']=$timestamp;*/
        return $coin;
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
            CURLOPT_ENCODING => '',
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
            //return "cURL Error #:" . $err;
            return null;
        } else {
            return $response;
        }
    }

    public function send_notifications()
    {
        cronLastRun(__FUNCTION__);

        $notifications = M('Notification')->where(['status' => 0])->order('id desc')->select();

        foreach ($notifications as $note) {
            if (!check($note['to_email'], 'email')) {
                M('Notification')->where(['id' => $note['id']])->save(['status' => 2, 'sent_time' => time(),]);
                continue;
            }

            $status = tmail($note['to_email'], $note['subject'], $note['content']);

            if ($status) {
                $the_status = json_decode($status);
                if ($status == true || isset($the_status->status))// means email has been sent , Now mark this email as sent
                {
                    echo M('Notification')->where(['id' => $note['id']])->save(['status' => 1, 'sent_time' => time(),]);
                }
            }
        }
        echo 'End';
    }
    public function send_sms()
    {
        cronLastRun(__FUNCTION__);

        $messages = M('Sms')->where(['status' => 0])->order('id desc')->select();
        
        foreach ($messages as $note) {
            if (!check($note['phone_number'], 'cellphone') || !preg_match('/(\+\d\d{0,3})/', $note['country_code'])) {
                M('Sms')->where(['id' => $note['id']])->save(['status' => 2]);
                continue;
            }
            
            $mobile=$note['country_code'].$note['phone_number'];    
            
            $status = sendSms($mobile, $note['content']);
            
            if ($status) {
                    echo M('Sms')->where(['id' => $note['id']])->save(['status' => 1]);
            }else{
                    echo M('Sms')->where(['id' => $note['id']])->save(['status' => 2]);
            }
        }
        echo 'SMS Queue Ended';
    }

    public function index()
    {
        echo 'ok';
    }

    /*Adjustment of abnormal Trades if deal > num */
    public function fixTrades()
    {
        cronLastRun(__FUNCTION__);
        $mo = M();

        $Trade = M('Trade')->where('deal > num')->order('id desc')->find();

        if ($Trade) {
            $mo->startTrans();
            if ($Trade['status'] == 0) {
                $rs[] = $mo->table('codono_trade')->where(['id' => $Trade['id']])->save(['deal' => Num($Trade['num']), 'status' => 1]);
            } else {
                $rs[] = $mo->table('codono_trade')->where(['id' => $Trade['id']])->save(['deal' => Num($Trade['num'])]);
            }
            if (check_arr($rs)) {
                $mo->commit();
            } else {
                $mo->rollback();

            }
        }
        echo 'Cron Ended :)';
    }

    public function clearRedisForLiquidity()
    {

        cronLastRun(__FUNCTION__);
        foreach (C('market') as $market) {

            if ($market['ext_price_update'] == 1 && $market['ext_orderbook'] == 1) {

                S('allsum', null);
                S('getJsonTop' . $market['name'], null);
                S('getTradelog' . $market['name'], null);
                S('getDepth' . $market['name'] . '1', null);
                S('getDepth' . $market['name'] . '3', null);
                S('getDepth' . $market['name'] . '4', null);
                S('ChartgetJsonData' . $market['name'], null);
                S('allcoin', null);
                S('trends', null);
                S('getActiveDepth' . $market['name'], null);
            }
        }

    }

    public function matchOrdersManually()
    {
        cronLastRun(__FUNCTION__);
        foreach (C('market') as $k => $v) {
            A('Trade')->matchingTrade($v['name']);
            echo $v['name'] . '<br/>';
        }
    }


    public function setMarketCoinStats()
    {
        cronLastRun(__FUNCTION__);
        foreach (C('market') as $k => $v) {
            $this->setMarket($v['name']);
        }

        foreach (C('coin_list') as $k => $v) {
            $this->setcoin($v['name']);
        }

    }

    private function setMarket($market = NULL)
    {
        if (!$market) {
            return null;
        }

        $market_json = M('Market_json')->where(['name' => $market])->order('id desc')->find();

        if ($market_json) {
            $addtime = $market_json['addtime'] + 60;
        } else {
            $addtime = M('TradeLog')->where(['market' => $market])->order('addtime asc')->find()['addtime'];
        }

        $t = $addtime;
        $start = mktime(0, 0, 0, date('m', $t), date('d', $t), date('Y', $t));
        $end = mktime(23, 59, 59, date('m', $t), date('d', $t), date('Y', $t));
        $trade_num = M('TradeLog')->where([
            'market' => $market,
            'addtime' => [
                ['egt', $start],
                ['elt', $end]
            ]
        ])->sum('num');

        if ($trade_num) {
            $trade_mum = M('TradeLog')->where([
                'market' => $market,
                'addtime' => [
                    ['egt', $start],
                    ['elt', $end]
                ]
            ])->sum('mum');
            $trade_fee_buy = M('TradeLog')->where([
                'market' => $market,
                'addtime' => [
                    ['egt', $start],
                    ['elt', $end]
                ]
            ])->sum('fee_buy');
            $trade_fee_sell = M('TradeLog')->where([
                'market' => $market,
                'addtime' => [
                    ['egt', $start],
                    ['elt', $end]
                ]
            ])->sum('fee_sell');
            $d = [$trade_num, $trade_mum, $trade_fee_buy, $trade_fee_sell];

            if (M('Market_json')->where(['name' => $market, 'addtime' => $end])->find()) {
                M('Market_json')->where(['name' => $market, 'addtime' => $end])->save(['data' => json_encode($d)]);
            } else {
                M('Market_json')->add(['name' => $market, 'data' => json_encode($d), 'addtime' => $end]);
            }
        } else {
            //  $d = null;

            if (M('Market_json')->where(['name' => $market, 'data' => ''])->find()) {
                M('Market_json')->where(['name' => $market, 'data' => ''])->save(['addtime' => $end]);
            } else {
                M('Market_json')->add(['name' => $market, 'data' => '', 'addtime' => $end]);
            }
        }
    }

    private function setcoin($coinname = NULL)
    {
        //   echo "<br/>Start coin " . $coinname;
        if (!$coinname) {
            return null;
        }

        if (C('coin')[$coinname]['type'] == 'qbb') {
            $dj_username = C('coin')[$coinname]['dj_yh'];
            $dj_password = C('coin')[$coinname]['dj_mm'];
            $dj_address = C('coin')[$coinname]['dj_zj'];
            $dj_port = C('coin')[$coinname]['dj_dk'];
            $CoinClient = CoinClient($dj_username, $dj_password, $dj_address, $dj_port, 5, [], 1);
            $json = $CoinClient->getinfo();

            if (!isset($json['version']) || !$json['version']) {
                return null;
            }

            $data['trance_mum'] = $json['balance'];
            $bb = $json['balance'];
        } else {
            $data['trance_mum'] = 0;
            $bb = 0;
        }

        $market_json = M('CoinJson')->where(['name' => $coinname])->order('id desc')->find();

        if ($market_json) {
            $addtime = $market_json['addtime'] + 60;
        } else {
            $addtime = M('Myzr')->where(['name' => $coinname])->order('id asc')->find()['addtime'];
        }

        $t = $addtime;
        $start = mktime(0, 0, 0, date('m', $t), date('d', $t), date('Y', $t));
        $end = mktime(23, 59, 59, date('m', $t), date('d', $t), date('Y', $t));

        if ($addtime) {
            if ((time() + (60 * 60 * 24)) < $addtime) {
                return null;
            }

            $trade_num = M('UserCoin')->where([
                'addtime' => [
                    ['egt', $start],
                    ['elt', $end]
                ]
            ])->sum($coinname);
            $trade_mum = M('UserCoin')->where([
                'addtime' => [
                    ['egt', $start],
                    ['elt', $end]
                ]
            ])->sum($coinname . 'd');
            $aa = $trade_num + $trade_mum;


            $trade_fee_buy = M('Myzr')->where([
                'coinname' => $coinname,
                'addtime' => [
                    ['egt', $start],
                    ['elt', $end]
                ]
            ])->sum('fee');
            $trade_fee_sell = M('Myzc')->where([
                'coinname' => $coinname,
                'addtime' => [
                    ['egt', $start],
                    ['elt', $end]
                ]
            ])->sum('fee');
            $d = [$aa, $bb, $trade_fee_buy, $trade_fee_sell];

            if (M('CoinJson')->where(['name' => $coinname, 'addtime' => $end])->find()) {
                M('CoinJson')->where(['name' => $coinname, 'addtime' => $end])->save(['data' => json_encode($d)]);
            } else {
                M('CoinJson')->add(['name' => $coinname, 'data' => json_encode($d), 'addtime' => $end]);
            }
        }
    }


    public function setHourlyPrice()
    {
        cronLastRun(__FUNCTION__);
        foreach (C('market') as $k => $v) {
            echo "$k<br/>";

            $t = time();
            $start = mktime(0, 0, 0, date('m', $t), date('d', $t), date('Y', $t));
            echo "Start is :$start<br/>";
            $hou_price = M('TradeLog')->where([
                'market' => $v['name'],
                'addtime' => ['lt', $start]
            ])->order('id desc')->getField('price');

            if (!$hou_price) {
                $hou_price = M('TradeLog')->where(['market' => $v['name']])->order('id asc')->getField('price');
            }

            M('Market')->where(['name' => $v['name']])->setField('hou_price', $hou_price);
            S('home_market', null);

        }
    }

    /* Generates Tendency */
    public function tendency()
    {

        foreach (C('market') as $k => $v) {
            echo '----Computing trend----' . $v['name'] . '------------';
            $tendency_time = 4;
            $t = time();
            $tendency_str = $t - (24 * 60 * 60 * 3);
            $x = 0;

            for (; $x <= 18; $x++) {
                $na = $tendency_str + (60 * 60 * $tendency_time * $x);
                $nb = $tendency_str + (60 * 60 * $tendency_time * ($x + 1));
                $b = M('TradeLog')->where('addtime >=' . $na . ' and addtime <' . $nb . ' and market =\'' . $v['name'] . '\'')->max('price');

                if (!$b) {
                    $b = 0;
                }

                $rs[] = [$na, $b];
            }

            M('Market')->where(['name' => $v['name']])->setField('tendency', json_encode($rs));
            unset($rs);
            echo 'Computing success!';
            echo "\n";
        }

        echo 'Trend Calculation 0k ' . "\n";
    }


    private function liq_market_stats($market)
    {

        $round = C('market')[$market]['round'];
        $new_price = format_num(M('TradeLog')->where(['market' => $market, 'status' => 1, 'addtime' => ['gt', time() - (60 * 60 * 24)]])->order('id desc')->limit(100)->getField('price'), $round);
        $buy_price = format_num(M('Trade')->where(['type' => 1, 'market' => $market, 'status' => 0, 'addtime' => ['gt', time() - (60 * 60 * 24)]])->order('id desc')->limit(100)->max('price'), $round);
        $sell_price = format_num(M('Trade')->where(['type' => 2, 'market' => $market, 'status' => 0, 'addtime' => ['gt', time() - (60 * 60 * 24)]])->order('id desc')->limit(100)->min('price'), $round);
        $min_price = format_num(M('TradeLog')->where([
            'market' => $market,
            'addtime' => ['gt', time() - (60 * 60 * 24)]
        ])->order('id desc')->limit(100)->min('price'), $round);
        $max_price = format_num(M('TradeLog')->where([
            'market' => $market,
            'addtime' => ['gt', time() - (60 * 60 * 24)]
        ])->order('id desc')->limit(100)->max('price'), $round);
        $volume = format_num(M('TradeLog')->where([
            'market' => $market,
            'addtime' => ['gt', time() - (60 * 60 * 24)]
        ])->order('id desc')->limit(100)->sum('num'), $round);
        $sta_price = format_num(M('TradeLog')->where([
            'market' => $market,
            'status' => 1,
            'addtime' => ['gt', time() - (60 * 60 * 24)]
        ])->order('id desc')->limit(100)->getField('price'), $round);
        $Cmarket = M('Market')->where(['name' => $market])->find();

        if ($Cmarket['new_price'] != $new_price) {
            $upCoinData['new_price'] = $new_price;
        }

        if ($Cmarket['buy_price'] != $buy_price) {
            $upCoinData['buy_price'] = $buy_price;
        }

        if ($Cmarket['sell_price'] != $sell_price) {
            $upCoinData['sell_price'] = $sell_price;
        }

        if ($Cmarket['min_price'] != $min_price) {
            $upCoinData['min_price'] = $min_price;
        }

        if ($Cmarket['max_price'] != $max_price) {
            $upCoinData['max_price'] = $max_price;
        }

        if ($Cmarket['volume'] != $volume) {
            $upCoinData['volume'] = $volume;
        }

        $change = format_num((($new_price - $Cmarket['hou_price']) / $Cmarket['hou_price']) * 100, 4);
        $upCoinData['change'] = $change;

        if ($upCoinData) {
            M('Market')->where(['name' => $market])->save($upCoinData);
            M('Market')->execute('commit');
            S('home_market', null);
        }
    }

    public function genInternalCharts()
    {
        G('begin');
        cronLastRun(__FUNCTION__);
        foreach (C('market') as $marketConfig) {
            echo "<br/>{$marketConfig['name']} is system is showing external charts {$marketConfig['ext_charts']}<br/>";
            if ($marketConfig['ext_charts'] != 1) {
                $this->setChartMaker($marketConfig['name']);
            }
        }
        echo '<br/><b>Chartmaker Ends</b>';
        G('end');
        echo '<br/>Total Time taken ' . G('begin', 'end') . 's';
    }
    
    private function setChartMaker($market)
    {
        $timeFrames = [1, 3, 5, 15, 30, 60, 120, 240, 360, 720, 1440, 10080];
        $tl_addtime = M('TradeLog')->where(['market' => $market])->order('addtime asc')->getField('addtime');
    
        if (!$tl_addtime) {
            return;
        }
    
        foreach ($timeFrames as $timeFrame) {
            echo "<br/><strong>==================Generating for $timeFrame mins chart for $market==================</strong><br/>";
            $tradeJson = M('TradeJson')->where(['market' => $market, 'type' => $timeFrame])->order('addtime desc')->find();
            $addTime = $tradeJson ? $tradeJson['addtime'] : $tl_addtime;
            
            if (!$addTime) {
                continue;
            }
            $addWhere=['addtime' => ['egt', $addTime], 'market' => $market];
            $addTimeArray = M('TradeLog')->where($addWhere)
                                         ->order('addtime asc')
                                         ->limit(200)
                                         ->getField('addtime', true);
                                         
            $soAddTime = count($addTimeArray);
            $addTimeArray = array_unique($addTimeArray);
            
            if ($soAddTime == 0) {
                echo '<br/> No records found to be processed.<br/>';
                continue;
            }
    
            $this->processChartData($market, $timeFrame, $addTime, $addTimeArray, $soAddTime);
        }
    }
    
    private function processChartData($market, $timeFrame, $addTime, $addTimeArray, $soAddTime)
    {
        for ($x = 0; $x < $soAddTime; ++$x) {
            $startTime = $this->calculateStartTime($addTime, $timeFrame);
            $na = $addTimeArray[$x];
            $nb = isset($addTimeArray[$x + 1]) ? $addTimeArray[$x + 1] : time();
    
            if (time() < $na || !$na || !$nb) {
                continue;
            }
    
            $sum = $this->sum_market($na, $nb, $market);
            if ($sum > 0) {
                $allPrices = $this->getprice_market($na, $nb, $market);
                $sta = M('TradeLog')->where('addtime >=' . $na . ' and addtime <=' . $nb . ' and market =\'' . $market . '\'')->order('addtime asc')->getField('price');
                $data = [$na, (float)$sum,format_num($sta), format_num($allPrices['min']), format_num($allPrices['max']), format_num($allPrices['end'])];
    
                $existingEntry = M('TradeJson')->where(['market' => $market, 'addtime' => $na, 'type' => $timeFrame])->find();
                if ($existingEntry) {
                    if (json_decode($existingEntry['data']) != $data) {
                        M('TradeJson')->where(['id' => $existingEntry['id']])->save(['data' => json_encode($data)]);
                    }
                } else {
                    M('TradeJson')->add(['market' => $market, 'data' => json_encode($data), 'addtime' => $na, 'type' => $timeFrame]);
                }
            } else {
                echo "<br/>Sum is $sum. Checking next record.<br/>";
            }
        }
        echo "<br/>Chart for $market for $timeFrame mins done<br/>";
    }
    
    private function calculateStartTime($addTime, $timeFrame)
    {
        if ($timeFrame == 1) {
            return $addTime;
        }
        return mktime(date('H', $addTime), floor(date('i', $addTime) / $timeFrame) * $timeFrame, 0, date('m', $addTime), date('d', $addTime), date('Y', $addTime));
    }
    
    private function getprice_market($na, $nb, $market)
    {
        $prices = M('TradeLog')->where(['addtime' => ['between', [$na, $nb]], 'market' => $market])->getField('price', true);
        return [
            'min' => min($prices),
            'max' => max($prices),
            'end' => end($prices),
        ];
    }
    
    private function sum_market($na, $nb, $market)
    {
        $volumes = M('TradeLog')->where(['addtime' => ['between', [$na, $nb]], 'market' => $market])->getField('num', true);
        return array_sum($volumes);
    }
    
    private function setTradeJson($market)
    {
        $cron_time_start = microtime(true);
        $timearr = [1, 3, 5, 10, 15, 30, 60, 120, 240, 360, 720, 1440, 10080];
        echo '<br/>==========================';
        foreach ($timearr as $k => $v) {
            echo "<br/>$market for $v min charts";
            $tradeJson = M('TradeJson')->where([
                'market' => $market,
                'type' => $v
            ])->order('id desc')
                ->find();

            if ($tradeJson) {
                $addtime = $tradeJson['addtime'];
            } else {
                $addtime = M('TradeLog')->where([
                    'market' => $market
                ])->order('id asc')
                    ->getField('addtime');
            }
            $youtradelog = null;
            if ($addtime) {
                $youtradelog = M('TradeLog')->where('addtime >= %d and market =\'%s\'', $addtime, $market)->sum('num');
            }

            if ($youtradelog) {
                if ($v == 1) {
                    $start_time = $addtime;
                } else {
                    $start_time = mktime(date('H', $addtime), floor(date('i', $addtime) / $v) * $v, 0, date('m', $addtime), date('d', $addtime), date('Y', $addtime));
                }

                $x = 0;

                for (; $x <= 20; $x++) {
                    $na = $start_time + (60 * $v * $x);
                    $nb = $start_time + (60 * $v * ($x + 1));

                    if (time() < $na) {
                        break;
                    }

                    $sum = M('TradeLog')->where('addtime >= %d and addtime < %d and market =\'%s\'', $na, $nb, $market)->sum('num');

                    if ($sum) {
                        $sta = M('TradeLog')->where('addtime >= %d and addtime < %d and market =\'%s\'', $na, $nb, $market)->order('id asc')->getField('price');
                        $max = M('TradeLog')->where('addtime >= %d and addtime < %d and market =\'%s\'', $na, $nb, $market)->max('price');
                        $min = M('TradeLog')->where('addtime >= %d and addtime < %d and market =\'%s\'', $na, $nb, $market)->min('price');
                        $end = M('TradeLog')->where('addtime >= %d and addtime < %d and market =\'%s\'', $na, $nb, $market)->order('id desc')->getField('price');
                        $d = [$na, $sum, $sta, $max, $min, $end]; //date,qty,open,high,low,close
                        if (M('TradeJson')->where(['market' => $market, 'addtime' => $na, 'type' => $v])->find()) {
                            M('TradeJson')->where(['market' => $market, 'addtime' => $na, 'type' => $v])->save(['data' => json_encode($d)]);
                        } else {
                            M('TradeJson')->add(['market' => $market, 'data' => json_encode($d), 'addtime' => $na, 'type' => $v]);
                            M('TradeJson')->execute('commit');
                        }
                    }
                }
            }
        }

        $time_end = microtime(true);

        //dividing with 60 will give the execution time in minutes otherwise seconds
        $execution_time = ($time_end - $cron_time_start) / 60;

        //execution time of the script
        echo "<br/><b>$market Execution Time:</b>  $execution_time  Mins";
        echo '<br/>==========================';
    }


    private function InformAdmin($info)
    {
        //Do something , You can send a specific notification to admin , It can be  either email sms , or anything
    }

    /**
     *Creates columns in codono_user_coin table for missing columns [example xrp, xrpd,xrpb]
     */
    public function fix_user_coin()
    {

        if ($_GET['agree'] != 'yes') {
            $agree_url = $_SERVER['REQUEST_URI'] . '/agree/yes';
            die("<span style='color:red'>Warning this tool will modify you user_coin table , this is very powerful,  <a href='$agree_url'> Do you agree?</a></span>");
        }
        cronLastRun(__FUNCTION__);
        $sql = "SELECT (column_name) as name FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA='" . DB_NAME . "' AND TABLE_NAME='codono_user_coin'";
        $mo = M();
        $ucoins = $mo->query($sql);
        $cnames = [];
        foreach ($ucoins as $ucoin) {
            $cnames[] = $ucoin['name'];
        }

        foreach (C('coin') as $coin) {
            $name = $coin['name'];
            $address = $coin['name'] . 'b';
            $balance = $coin['name'] . 'd';
            $tokenof= $coin['tokenof'];
            $present = in_array($name, $cnames);
            if ($present == 0) {
                echo "$name is not present so creating it<br/>";
                echo M()->execute('ALTER TABLE  `codono_user_coin` ADD  `' . $name . '` DECIMAL(20,8) UNSIGNED  NULL DEFAULT "0.00000000"');
                echo M()->execute('ALTER TABLE  `codono_user_coin` ADD  `' . $balance . '` DECIMAL(20,8) UNSIGNED NULL DEFAULT "0.00000000" ');
                if (in_array($name, ['xmr', 'iota'])) {
                    echo M()->execute('ALTER TABLE  `codono_user_coin` ADD  `' . $address . '` VARCHAR(95) DEFAULT NULL ');
                } else {
                    if($tokenof==NULL || $tokenof=='0' || $tokenof==0 || $tokenof==''){
                        echo M()->execute('ALTER TABLE  `codono_user_coin` ADD  `' . $address . '` VARCHAR(42) DEFAULT NULL ');
                    }
                }

            } else {
                echo "$name is present Skipping to next<br/>";
            }
        }

        $resp=$mo->query('CALL InsertUserCoin()');
        if(!empty($resp)){
            echo '<br/>Insert Missing user balance rows in user_coin table';
            echo '<br/>Done and Inserted';
        }
        echo "Command Ran successfully";


    }

    private function coinBalance($coinname)
    {
        $coinname = strtolower($coinname);

        if ($coinname != C('coin')[$coinname]['name']) {
            return 0;
        }

        if (self::CONSUME_STAKING_BALANCE == 1) {
            $mo = M('UserAssets');
            $info = $mo->where(['uid' => userid(), 'account' => 4, 'coin' => $coinname])->find();
            return $info['balance'] ?: 0;
        } else {
            $Model = M('UserCoin');
            return $Model->where(['userid' => userid()])->getField($coinname);
        }

    }
    public function testme(){
        $nu=38;
        $info=$this->inviteTiers($nu);
        print_r($info);
    }
}
