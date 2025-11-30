<?php

namespace Home\Controller;

use Think\Page;
use Think\Exception;

/**
 * @property mixed $userinfo
 */
class TradeController extends HomeController
{
    //beta feature do not use keep 0
    private const BETA_MATCH = 0;

    public function __construct()
    {
        if (TRADING_ALLOWED == 0) {
            die('Unauthorized!');
        }
        parent::__construct();
    }
    public function time()
    {
        header('Content-Type: application/json');

        // Get the current time in seconds since the Unix epoch
        $current_time = time();

        // Create an associative array with the time key
        $response = array("time" => $current_time);

        // Output the JSON-encoded response
        echo json_encode($response);
    }
    public function tradepro()
    {
        $market = I('get.market', '', 'htmlspecialchars');

        $this->tradeUI($market);
        $this->display();
    }

    public function index()
    {
        $market = I('get.market', '', 'htmlspecialchars');

        $this->tradeUI($market);
        $this->display('Trade/tradepro');
    }
    public function classic()
    {
        $market = I('get.market', '', 'htmlspecialchars');

        $this->tradeUI($market);
        $this->display('Trade/index');
    }

    private function tradeUI($market)
    {
        $uid = userid();
        $market = strtolower($market);
        $user = [];
        $showPW = 1;

        check_server();
        if (!$market) {
            $market = C('market_mr');
        }

        if ($uid) {
            $user = $this->userinfo; //M('User')->where(array('id' => $uid))->find();

            if ($user['tpwdsetting'] == 3) {
                $showPW = 0;
            }

            if ($user['tpwdsetting'] == 1) {
                if (session($uid . 'tpwdsetting')) {
                    $showPW = 2;
                }
            }
        }


        if (!is_array(C('market')[$market]) || C('market')[$market]['status'] != 1) {
            $this->assign('type', '404');
            $this->assign('error', 'Oops, No Such Market Found');
            $this->display('Content/error');
            exit;
        }

        $market_time_codono = C('market')[$market]['begintrade'] . '-' . C('market')[$market]['endtrade'];
        $codono_getCoreConfig = codono_getCoreConfig();
        if ($uid > 0) {
            $user_coin = M('UserCoin')->where(['userid' => $uid])->find();
        }


        $xnb = explode('_', $market)[0];
        $rmb = explode('_', $market)[1];
        if ($user_coin) {
            $this->assign('user_coin', $user_coin[$xnb]);
            $this->assign('base_coin', $user_coin[$rmb]);
        } else {
            $this->assign('user_coin', 0);
            $this->assign('base_coin', 0);
        }

        $buy_fees = C('market')[$market]['fee_buy'];
        $sell_fees = C('market')[$market]['fee_buy'];
        if ($uid) {
            $buy_fees = discount(C('market')[$market]['fee_buy'], $uid, $user['usertype']);
            $sell_fees = discount(C('market')[$market]['fee_sell'], $uid, $user['usertype']);
        }
        $socket_pair = C('market')[$market]['socket_pair'] ? strtolower(C('market')[$market]['socket_pair']) : $xnb . $rmb;
        $conversion_price = $this->calculateCoinPrice($market);

        $conversion_coin = $this->findConversionCoin();

        $fiat_symbol_html = fiatSymbolHTML($conversion_coin);

        $this->assign('buy_fees', $buy_fees);
        $this->assign('sell_fees', $sell_fees);
        $this->assign('activated_base_markets', $codono_getCoreConfig['codono_indexcat']);
        $this->assign('market_time', $market_time_codono);
        $this->assign('showPW', $showPW);
        $this->assign('market', $market);
        $this->assign('xnb', $xnb);
        $this->assign('rmb', $rmb);
        $this->assign('socket_pair', $socket_pair);
        $this->assign('xnbimg', C('market')[$market]['xnbimg']);
        $this->assign('switch', C('market')[$market]['switch']);
        $this->assign('ext_orderbook', C('market')[$market]['ext_orderbook']);
        $this->assign('ext_charts', C('market')[$market]['ext_charts']);
        $this->assign('charts_symbol', C('market')[$market]['charts_symbol']);
        $this->assign('page_title', 'Trade ' . $xnb . ' & ' . $rmb . ' | ' . $market);

        $this->assign('conversion_price', $conversion_price);
        $this->assign('fiat_symbol', $fiat_symbol_html);
    }

    private function findConversionCoin()
    {
        $uid = userid();

        if ($uid) {
            $User_Selected_coin = $this->userinfo['fiat'];
            if (!isset($User_Selected_coin['fiat'])) {
                $conversion_coin = DEFAULT_FIAT;
            } else {
                $conversion_coin = $User_Selected_coin['fiat'];
            }
        } else {
            $conversion_coin = DEFAULT_FIAT;
        }
        return $conversion_coin;
    }

    private function calculateCoinPrice($market)
    {
        $uid = userid();
        $xnb = explode('_', $market)[0];
        $base = explode('_', $market)[1];
        $CoinList = C('Coin'); //M('Coin')->where(array('status' => 1))->order('name desc')->select();
        $UserCoin = M('UserCoin')->where(['userid' => $uid])->find();
        $Market = C('market');
        $print_coins = [];

        $conversion_coin = $this->findConversionCoin();


        foreach ($Market as $k => $v) {
            $Market[$v['name']] = $v;
        }

        $usd['zj'] = 0;

        $cmcs = (APP_DEBUG ? null : S('cmcrates'));

        if (!$cmcs) {
            $cmcs = M('Coinmarketcap')->field(['symbol', 'price_usd'])->select();
            S('cmcrates', $cmcs);
        }

        //Find


        $multiplier = 1;
        foreach ($cmcs as $ckey => $cval) {
            if (strtolower($conversion_coin) != 'usd' && $cval['symbol'] == strtoupper($conversion_coin)) {
                $multiplier = $cval['price_usd'];
            }
            $the_cms[strtolower($cval['symbol'])] = $cval['price_usd'];
        }
        if ($multiplier == 0) {
            $multiplier = 1;
        }
        foreach ($CoinList as $k => $v) { {

                $x_market = strtolower($v['name'] . '_' . $conversion_coin);

                if (isset($the_cms[$v['name']])) {
                    $jia = $before = $the_cms[$v['name']];
                } elseif (isset(c('market')[$x_market])) {
                    $jia = c('market')[$x_market]['new_price'];
                } else {
                    $jia = 1;
                }
                $jia = $after = bcdiv((float)$jia, $multiplier, 8);
                if ($UserCoin == null) {
                    $usd['zj'] = bcadd($usd['zj'], bcmul(0, $jia, 8), 2) * 1;
                } else {
                    $usd['zj'] = bcadd($usd['zj'], bcmul(bcadd($UserCoin[$v['name']], $UserCoin[$v['name'] . 'd'], 8), $jia, 8), 2) * 1;
                }
                $print_coins[$v['name']] = ['name' => $v['name'], 'img' => $v['img'], 'title' => strtoupper($v['name']) . ' [ ' . ucfirst($v['title']) . ' ]', 'price' => $jia, 'currency' => $conversion_coin, 'type' => $v['type'], 'deposit_status' => $v['zr_jz'], 'withdrawal_status' => $v['zc_jz']];
            }
        }
        return $print_coins[$base]['price'] ?: 0;
    }

    public function chart()
    {
        $market = I('get.market', '', 'htmlspecialchars');

        if (!$market) {
            $market = C('market_mr');
        }

        $xnb = explode('_', $market)[0];
        $rmb = explode('_', $market)[1];
        $this->assign('market', $market);
        $this->assign('xnb', $xnb);
        $this->assign('rmb', $rmb);
        $this->assign('page_title', 'Market Graph of ' . $xnb . ' vs ' . $rmb . ' | ' . $market);
        $this->display();
    }

    public function info()
    {
        $market = I('get.market', '', 'htmlspecialchars');

        if (!$market) {
            $market = C('market_mr');
        }
        $this->assign('market', $market);
        $this->assign('xnb', explode('_', $market)[0]);
        $this->assign('rmb', explode('_', $market)[1]);
        $this->display();
    }

    public function comment()
    {

        $this->error('Feature has been disabled');
        $market = I('get.market', '', 'text');
        if (!check($market, 'market')) {
            $market = C('market_mr');
        }

        if (!$market) {
            $market = C('market_mr');
        }

        if ($market != C('market')[$market]['name']) {
            $market = C('market_mr');
        }


        $this->assign('xnb', explode('_', $market)[0]);
        $this->assign('rmb', explode('_', $market)[1]);
        $where['coinname'] = explode('_', $market)[0];
        $Model = M('CoinComment');

        $count = $Model->where($where)->count();
        $Page = new Page($count, 15);
        $show = $Page->show();
        $list = $Model->where($where)->order('id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();

        foreach ($list as $k => $v) {
            $list[$k]['username'] = M('User')->where(['id' => $v['userid']])->getField('username');
        }
        $this->assign('market', $market);
        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->display();
    }

    public function ordinary()
    {
        $market = I('get.market', '', 'text');

        if (!$market) {
            $market = C('market_mr');
        }
        $this->assign('round', C('MARKET')[$market]['round']);
        $this->assign('market', $market);
        $this->display();
    }

    public function highstock()
    {
        $market = I('get.market', '', 'text');
        if (!$market) {
            $market = C('market_mr');
        }
        $this->assign('round', C('MARKET')[$market]['round']);
        $this->assign('market', $market);
        $this->display();
    }

    public function specialty()
    {
        $market = I('get.market', '', 'text');
        if (!$market) {
            $market = C('market_mr');
        }

        $this->assign('market', $market);
        $this->display();
    }

    public function tradingview()
    {
        $market = I('get.market', '', 'text');
        if (!$market) {
            $market = C('market_mr');
        }

        $skin = I('get.skin', 'black', 'string');
        if ($skin == 'white') {
            $skin = 'light';
        }
        if ($skin == 'black') {
            $skin = 'dark';
        }
        $this->assign('skin', $skin);
        $this->assign('charts_symbol', C('market')[$market]['charts_symbol']);
        $this->assign('ext_charts', C('market')[$market]['ext_charts']);
        $this->assign('market', $market);
        if (C('market')[$market]['ext_charts']) {
            $this->display('Trade/tv_box');
        } else {
            $this->display();
        }
    }
    public function tv_mobile()
    {
        $market = I('get.market', '', 'text');
        if (!$market) {
            $market = C('market_mr');
        }

        $skin = I('get.skin', 'black', 'string');
        if ($skin == 'white') {
            $skin = 'light';
        }
        if ($skin == 'black') {
            $skin = 'dark';
        }

        $this->assign('skin', $skin);
        $this->assign('charts_symbol', C('market')[$market]['charts_symbol']);
        $this->assign('ext_charts', C('market')[$market]['ext_charts']);
        $this->assign('market', $market);
        if (C('market')[$market]['ext_charts']) {
            $this->display('Trade/tv_boxmobile');
        } else {
            $this->display();
        }
    }

    public function depth()
    {
        $market = I('get.market', '', 'text');
        if (!$market) {
            $market = C('market_mr');
        }

        $this->assign('market', $market);
        $this->display();
    }

    public function upTrade($price = null, $num = null, $type = null, $tradeType = 'limit', $stop = 0, $paypassword = null, $market = null, $override = [])
    {
        // Explicitly validate and sanitize the parameters if they are directly passed
        $price = isset($price) ? format_num(filter_var($price, FILTER_VALIDATE_FLOAT), 8) : format_num(I('post.price', 0, 'float'), 8);
        $num = isset($num) ? format_num(filter_var($num, FILTER_VALIDATE_FLOAT), 8) : format_num(I('post.num', 0, 'float'), 8);
        $type = isset($type) ? (int)$type : I('post.type/d');
        $tradeType = $tradeType ?? I('post.tradeType', null, 'htmlspecialchars');
        $stop = isset($stop) ? format_num(filter_var($stop, FILTER_VALIDATE_FLOAT), 8) : format_num(I('post.stop', 0, 'float'), 8);
        $paypassword = $paypassword ?? I('post.paypassword', null, 'htmlspecialchars');
        $market = $market ?? I('post.market', null, 'htmlspecialchars');
        //$this->error(json_encode([(int)C('market')[$market]['switch'],$tradeType,$price,$num,$type,$tradeType,$market]));
        if (!kyced()) {
            $this->error(L('Complete KYC First!'));
        }

        if (!C('market')[$market]['trade']) {
            $this->error(strtoupper($market) . ' ' . L('trading is currently disabled for') . $market);
        }
        // Check if the limit/stop trading is disabled in market configuration
        //$this->error(json_encode([(int)C('market')[$market]['switch'],C('market')[$market]['switch'],$tradeType,$price,$num,$type,$tradeType,$stop,$paypassword,$market]));
        if (((int)C('market')[$market]['switch'] == 0) && ($tradeType == ! 'market')) {
            $this->error(strtoupper($market) . ' is disabled for Limit/Stop Trading--' . $tradeType);
        }

        if ($tradeType == 'market') {
            $this->V2MarketTrade($num, $type, $market, $paypassword);
        }
        if ($tradeType == 'limit') {

            $this->upTradeLimit($price, $num, $type, $paypassword, $market, $override);
        }
        if ($tradeType == 'stop') {

            $this->saveStop($price, $num, $type, $stop, $paypassword, $market);
        }
    }

    private function V2MarketTrade($num, $type, $market, $paypassword, $price = 0)
    {
        $uid = userid();
        if (!$uid) {
            $this->error(L('PLEASE_LOGIN'));
        }


        $buy_fees = C('market')[$market]['fee_buy'];
        //$user_balance = M('UserCoin')->where(['userid' => $uid])->find();


        $amount = $num;

        if (!check($amount, 'decimal')) {
            $this->error('Incorrect Amount' . $amount);
        }

        $orderbook = A('Ajax')->getActiveOrders($market, 1, false);

        if ($type == 1) {
            $count = count($orderbook['depth']['sell']);

            if ($count < 1) {
                $this->error('There are currently not sufficient orders');
            }
            $buyOrders = $orderbook['depth']['sell'];
            $total = $flag = $buy_amount = $i = 0;

            foreach ($buyOrders as $bo) {
                $buy_amount = bcadd($buy_amount, $bo[1], 8);
                $total = bcadd($total, bcmul($bo[0], $bo[1], 8), 8);
                if ($buy_amount > $amount) {
                    $flag = 1;
                    $buy_qty = bcmul(bcdiv(1, $bo[0], 8), $amount, 8);
                    $price = $bo[0];
                    break;
                }
            }

            if (!$flag) {
                $this->error('Maximum available to buy is ' . $total);
            }
            $percent = bcsub(100, $buy_fees, 8);
            //	$amount=bcdiv(bcmul($buy_qty,$percent,8),100,8);

            //$this->error($amount."=amount , price".$price);
        }
        if ($type == 2) {
            $count = count($orderbook['depth']['buy']);
            if ($count < 1) {
                $this->error(L('There are currently not sufficient orders'));
            }
            if ($orderbook['buyvol'] < $amount) {
                $this->error('Maximum available to sell is ' . $orderbook['sellvol']);
            }

            $price = end($orderbook['depth']['buy'])[0];
        }
        //When 100% is pressed then calc amount = amount - fees
        if ($type == 1) {
            $market_fees = C('market')[$market]['fee_buy'];
        } else {
            $market_fees = C('market')[$market]['fee_sell'];
        }
        //$amount=bcmul($amount,bcdiv(bcsub(100,$market_fees*10,3),100,8),8);

        //$this->error(json_encode([$price, $amount, $type, $tradeType = 'limit', $stop = 0, NULL, $market]));

        $this->upTrade($price, $amount, $type, 'limit', 0, NULL, $market);

        $this->success('Order Placed');
    }


    private function getPriceNumForMarket($market, $type)
    {
        if ($type == 2) {
            $query = "SELECT sum(num) as num,min(price) as price FROM `codono_trade` WHERE market='" . $market . "' and type='1' and status=0";
        } elseif ($type == 1) {
            $query = "SELECT sum(num) as num,max(price) as price FROM `codono_trade` WHERE market='" . $market . "' and type='2' and status=0";
        } else {
            // Handle unexpected $type value
            return [];
        }
        $response = M()->query($query);
        return $response[0];
    }

    public function matchingTrade($market = NULL)
    {
        //  clog(__FUNCTION__,[self::BETA_MATCH,$market,userid()]);

        if (self::BETA_MATCH == 1) {
            return $this->redisCallMatch($market);
        } else {
            return $this->domatchingTrade($market); //This market does not run on liquidity
        }
    }

    private function redisCallMatch($market)
    {

        $match_array = S('match_array');
        if (!$match_array) {
            $ma = [$market => 1];
            return S('match_array', $ma);
        } else {
            $match_array[$market] = 1;
            return S('match_array', $match_array);
        }
    }


    /**
     * @param $market string
     * @param $type 1 buy 2 sell
     * @return bool|int
     */
    public function stopcheck(string $market, $type)
    {
        $price = $this->getPriceForStop($market, $type);

        if ($price < 0) {
            return 0;
        }
        if ($type == 1) {
            $type = 1;
        } else {
            $type = 2;
        }

        $StopOrdersSQL = "SELECT id,userid,market,compare,price,stop,num,type,status FROM `codono_stop` where market='$market' and status=0 and type=$type and compare=IF(STOP >$price, 'gt', 'lt') order by id asc";

        $StopOrders = M()->query($StopOrdersSQL);
        $records = sizeof($StopOrders);
        if ($records > 0) {
            foreach ($StopOrders as $so) {
                $this->executeStop($so['id']);
            }
        }
        return true;
    }

    private function getPriceForStop($market, $type)
    {
        if ($type == 2) {
            $query = "SELECT min(price) as price FROM `codono_trade` WHERE market='" . $market . "' and type='2' and status=0";
        } elseif ($type == 1) {
            $query = "SELECT max(price) as price FROM `codono_trade` WHERE market='" . $market . "' and type='1' and status=0";
        } else {
            return false;
        }
        $response = M()->query($query);
        return $response[0]['price'] ?: 0;
    }

    private function executeStop($stopid = 0)
    {
        $stopinfo = M('Stop')->where(['id' => (int)$stopid])->find();
        $market = $stopinfo['market'];
        $price = $stopinfo['price'];
        $num = $stopinfo['num'];
        $type = $stopinfo['type'];
        $userid = $stopinfo['userid'];

        if (!$userid) {
            return $this->stop_error(L('PLEASE_LOGIN'));
        }

        if (C('market')[$market]['begintrade']) {
            $begintrade = C('market')[$market]['begintrade'];
        } else {
            $begintrade = '00:00:00';
        }

        if (C('market')[$market]['endtrade']) {
            $endtrade = C('market')[$market]['endtrade'];
        } else {
            $endtrade = '23:59:59';
        }


        $trade_begin_time = strtotime(date('Y-m-d') . ' ' . $begintrade);
        $trade_end_time = strtotime(date('Y-m-d') . ' ' . $endtrade);
        $cur_time = time();

        if ($cur_time < $trade_begin_time || $cur_time > $trade_end_time) {
            return $this->stop_error('The current market is prohibited transaction,Trading hours daily' . $begintrade . '-' . $endtrade);
        }


        if (!check($price, 'double')) {
            return $this->stop_error(L('The transaction price is malformed'));
        }

        if (!check($num, 'double')) {
            return $this->stop_error(L('The number of transactions is malformed'));
        }

        if (($type != 1) && ($type != 2)) {
            return $this->stop_error(L('Transaction type format error'));
        }

        $user = M('User')->where(['id' => $userid])->find();

        if (!C('market')[$market]) {
            return $this->stop_error(L('Error market'));
        } else {
            $xnb = explode('_', $market)[0];
            $rmb = explode('_', $market)[1];
        }

        // TODO: SEPARATE


        $price = bcadd($price, '0', 8);


        if (!$price) {
            return $this->stop_error(L('The transaction price error') . $price);
        }


        if (!check($num, 'double')) {
            return $this->stop_error(L('INCORRECT_QTY'));
        }

        if ($type == 1) {
            $min_price = (C('market')[$market]['buy_min'] ? C('market')[$market]['buy_min'] : 1.0E-8);
            $max_price = (C('market')[$market]['buy_max'] ? C('market')[$market]['buy_max'] : 10000000);
        } elseif ($type == 2) {
            $min_price = (C('market')[$market]['sell_min'] ? C('market')[$market]['sell_min'] : 1.0E-8);
            $max_price = (C('market')[$market]['sell_max'] ? C('market')[$market]['sell_max'] : 10000000);
        } else {
            return $this->stop_error(L('Transaction type error'));
        }

        if ($max_price < $price) {
            return $this->stop_error(L('Trading price exceeding the maximum limit!'));
        }

        if ($price < $min_price) {
            return $this->stop_error(L('Price is low,Keep it above') . ' ' . $min_price);
        }

        $hou_price = C('market')[$market]['hou_price'];

        if (!$hou_price) {
            $hou_price = C('market')[$market]['market_ico_price'];
        }

        if ($hou_price) {
            if (C('market')[$market]['zhang']) {
                $zhang_price = bcmul(bcdiv($hou_price, '100', 8), bcadd('100', C('market')[$market]['zhang'], 8), 8);

                if ($zhang_price < $price) {
                    return $this->stop_error(L('Transaction prices rose more than limit today!'));
                }
            }

            if (C('market')[$market]['die']) {

                $die_price = bcmul(bcdiv($hou_price, '100', C('market')[$market]['round']), bcsub('100', C('market')[$market]['die'], C('market')[$market]['round']), C('market')[$market]['round']);

                if ($price < $die_price) {
                    return $this->stop_error(L('Least sale bid suggested:') . $rmb . ' ' . $die_price);
                }
            }
        }

        $user_coin = M('UserCoin')->where(['userid' => $userid])->find();

        if ($type == 1) {
            $trade_fee = C('market')[$market]['fee_buy'];

            if ($trade_fee) {
                $fee = bcmul(bcdiv(bcmul($num, $price, 8), '100', 8), $trade_fee, 8);
                $mum = bcmul(bcdiv(bcmul($num, $price, 8), '100', 8), bcadd('100', $trade_fee, 8), 8);
            } else {
                $fee = 0;
                $mum = bcmul($num, $price, 8);
            }
        } elseif ($type == 2) {
            $trade_fee = C('market')[$market]['fee_sell'];

            if ($trade_fee) {
                $fee = bcmul(bcdiv(bcmul($num, $price, 8), '100', 8), $trade_fee, 8);
                $mum = bcmul(bcdiv(bcmul($num, $price, 8), '100', 8), bcadd('100', $trade_fee, 8), 8);
            } else {
                $fee = 0;
                $mum = bcmul($num, $price, 8);
            }
        } else {
            return $this->stop_error(L('Transaction type error'));
        }


        if (C('market')[$market]['trade_min']) {
            if ($mum < C('market')[$market]['trade_min']) {
                return $this->stop_error(L('THE_MINIMUM_AMOUNT_FOR_EACH_PU') . ' ' . C('coin')[$rmb]['title'] . C('market')[$market]['trade_min']);
            }
        }

        if (C('market')[$market]['trade_max']) {
            if (C('market')[$market]['trade_max'] < $mum) {
                return $this->stop_error(L('MAX_ORDER_QTY') . C('market')[$market]['trade_max']);
            }
        }

        if (!$rmb) {
            return $this->stop_error('data error1');
        }

        if (!$xnb) {
            return $this->stop_error('data error2');
        }

        if (!$market) {
            return $this->stop_error('data error3');
        }

        if (!$price) {
            return $this->stop_error('data error4');
        }

        if (!$num) {
            return $this->stop_error('data error5');
        }

        if (!$mum) {
            return $this->stop_error('data error6');
        }

        if (!$type) {
            return $this->stop_error('data error7');
        }

        $mo = M();
        $mo->startTrans();
        $rs = [];
        $user_coin = $mo->table('codono_user_coin')->where(['userid' => $userid])->find();
        $stop_update = 0;

        if ($type == 1) {
            if ($user_coin[$rmb] < $mum) {
                return $this->stop_error(L('INSUFFICIENT') . C('coin')[$rmb]['title']);
            }


            $rs[] = $mo->table('codono_trade')->add(['userid' => $userid, 'market' => $market, 'price' => $price, 'num' => $num, 'mum' => $mum, 'fee' => $fee, 'type' => 1, 'addtime' => time(), 'status' => 0]);
            $stop_update = M('Stop')->where(['id' => $stopid])->save(['status' => 1]);
        } elseif ($type == 2) {
            if ($user_coin[$xnb] < $num) {
                //return $this->stop_error(C('coin')[$xnb]['title'] . 'Insufficient balance2!');
            }

            $rs[] = $mo->table('codono_trade')->add(['userid' => $userid, 'market' => $market, 'price' => $price, 'num' => $num, 'mum' => $mum, 'fee' => $fee, 'type' => 2, 'addtime' => time(), 'status' => 0]);
            $stop_update = M('Stop')->where(['id' => $stopid])->save(['status' => 1]);
        } else {
            $mo->rollback();

            return $this->stop_error(L('Transaction type error'));
        }
        if (check_arr($rs) && $stop_update == 1) {
            $mo->commit();

            S('getDepth', null);
            S('getActiveDepth' . $market, null);
            S('getActiveDepth', null);
            S('getDepthNew', null);

            exec($this->matchingTrade($market), $out);
            //   clog(__FUNCTION__,[$out]);
            return $this->stop_success(L('Trading success!'));
        } else {

            $mo->rollback();

            return $this->stop_error(L('transaction failed!'));
        }
    }

    private function stop_error($message)
    {
        $arr['status'] = 0;
        $arr['message'] = $message;
        return json_encode($arr);
    }

    private function stop_success($message)
    {
        $arr['status'] = 1;
        $arr['message'] = $message;
        return json_encode($arr);
    }

    private function calculateAndApplyBonus($mo, $rmb, $userId, $fee, $invitLevel, $bonusPercentage, $market, $transactionType, $amount, $mum, &$resultSet, $buyUserId, $sellUserId)
    {
        if (!$userId || !$fee || !$bonusPercentage) {
            return 0;
        }

        $bonusAmount = bcmul(bcdiv($fee, 100, 8), $bonusPercentage, 8);
        if ($bonusAmount > 0) {
            $resultSet[] = $mo->table('codono_user_coin')->where(['userid' => $userId])->setInc($rmb, $bonusAmount);
            $invitUserId = $transactionType === 'buy' ? $buyUserId : $sellUserId;
            $resultSet[] = $mo->table('codono_invit')->add([
                'coin' => $rmb,
                'userid' => $userId,
                'invit' => $invitUserId,
                'name' => "$invitLevel Tier $transactionType Bonus",
                'type' => $market . ' ' . L($transactionType . ' Order'),
                'num' => $amount,
                'mum' => $mum,
                'fee' => $bonusAmount,
                'addtime' => time(),
                'status' => 1
            ]);
        }

        return $bonusAmount;
    }

    private function findMatchingTrades($market)
    {
        // Simplified for example purposes
        $buy = M('Trade')->where(['market' => $market, 'type' => 1, 'status' => 0])->order('price desc, id asc')->limit(1)->find();
        $sell = M('Trade')->where(['market' => $market, 'type' => 2, 'status' => 0])->order('price asc, id asc')->limit(1)->find();

        return [$buy, $sell];
    }

    private function domatchingTrade($market = NULL)
    {
        //  clog(__FUNCTION__, ['$market', time()]);
        if (M_DEBUG == 1) {
            G('begin');
        }
        clog('auto_match', 'Market Matching' . $market);
        if (C('market')[$market]['auto_match'] == 0) {
            clog('Market Matching Disabled', $market);
            return true;
        }
        if (!$market || !isset(C('market')[$market])) {
            return false;
        } else {
            $xnb = explode('_', $market)[0];
            $rmb = explode('_', $market)[1];
        }

        $fee_buy = C('market')[$market]['fee_buy'];
        $fee_sell = C('market')[$market]['fee_sell'];
        $invit_buy = C('market')[$market]['invit_buy'];
        $invit_sell = C('market')[$market]['invit_sell'];
        $invit_1 = C('market')[$market]['invit_1'];
        $invit_2 = C('market')[$market]['invit_2'];
        $invit_3 = C('market')[$market]['invit_3'];
        $mo = M();
        $new_trade_codono = 0;
        //One run at a time
        $fp = fopen(RUNTIME_PATH . '/MatchingEngine_' . $market . '.stay', 'w+');
        if (flock($fp, LOCK_EX | LOCK_NB)) {
            //Clear Matching Queue
            S('matching_queue_' . $market, 0);
            $save_buy_rmb = $diff_save_buy_rmb = $invit_buy_user = $invit_sell_user = 0;
            while (true) {

                //$buy = $mo->table('codono_trade')->where(array('market' => $market, 'type' => 1, 'status' => 0))->order('price desc,id asc')->limit(1)->find();
                //$sell = $mo->table('codono_trade')->where(array('market' => $market, 'type' => 2, 'status' => 0))->order('price asc,id asc')->limit(1)->find();
                [$buy, $sell] = $this->findMatchingTrades($market);


                if ($sell['id'] < $buy['id']) {
                    $type = 1;
                } else {
                    $type = 2;
                }

                if ($buy && $sell && (0 <= floatval($buy['price']) - floatval($sell['price']))) {


                    $rs = [];
                    $amount = min(bcsub($buy['num'], $buy['deal'], 8), bcsub($sell['num'], $sell['deal'], 8));
                    $amount = format_num($amount, 8);

                    if ($amount <= 0) {
                        M('Trade')->where(['id' => $buy['id']])->setField('status', 1);
                        M('Trade')->where(['id' => $sell['id']])->setField('status', 1);
                        break;
                    }

                    if ($type == 1) {
                        $price = $sell['price'];
                    } elseif ($type == 2) {
                        $price = $buy['price'];
                    } else {
                        break;
                    }

                    if (!$price) {
                        break;
                    } else {
                        // TODO: SEPARATE
                        $price = format_num($price, 8);
                    }

                    $mum = bcmul($price, $amount, 8);

                    if (!$mum) {
                        $log = 'error3 market:' . $market . 'Error: Buy OrderID:' . $buy['id'] . L(' Sell orderID: ') . $sell['id'] . L('TRANSACTION_TYPE') . $type . L('TRANSACTION_AMOUNT') . $amount . "\n";
                        $log .= 'ERR: The total turnover of the error, the total is' . $mum;
                        mlog($log);
                        break;
                    }
                    if ($fee_buy) {
                        $buy_fee = bcmul(bcdiv($mum, 100, 8), $fee_buy, 8);
                        $buy_save = bcmul(bcdiv($mum, 100, 8), bcadd(100, $fee_buy, 8), 8);
                    } else {
                        $buy_fee = 0;
                        $buy_save = $mum;
                    }


                    if (!$buy_save) {
                        $log = 'error4 market:' . $market . 'Error: Buy OrderID:' . $buy['id'] . L(' Sell orderID: ') . $sell['id'] . L('TRANSACTION_TYPE') . $type . L('TRANSACTION_AMOUNT') . $amount . L('deal price') . $price . L('Total turnover') . $mum . "\n";
                        $log .= 'ERR: Buyers Update Quantity Error,Update Quantity :' . $buy_save;
                        mlog($log);
                        break;
                    }
                    if ($fee_sell) {
                        $sell_fee = bcmul(bcdiv($mum, 100, 8), $fee_sell, 8);
                        $sell_save = bcmul(bcdiv($mum, 100, 8), bcsub(100, $fee_sell, 8), 8);
                    } else {
                        $sell_fee = 0;
                        $sell_save = $mum;
                    }

                    if (!$sell_save) {
                        $log = 'error5 market:' . $market . 'Error: Buy OrderID:' . $buy['id'] . L(' Sell orderID: ') . $sell['id'] . L('TRANSACTION_TYPE') . $type . L('TRANSACTION_AMOUNT') . $amount . L('deal price') . $price . L('Total turnover') . $mum . "\n";
                        $log .= 'ERR: Sellers Update Quantity Error,Update Quantity :' . $sell_save;
                        mlog($log);
                        break;
                    }

                    $user_buy = M('UserCoin')->where(['userid' => $buy['userid']])->find();
                    //If userid is 0 it means liquidty is coming from LiqEngine
                    if (!$user_buy[$rmb . 'd'] && $buy['userid'] != 0) {
                        $log = 'error6 market:' . $market . 'Error: Buy OrderID:' . $buy['id'] . L(' Sell orderID: ') . $sell['id'] . '  ' . L('TRANSACTION_TYPE') . $type . L('TRANSACTION_AMOUNT') . $amount . L('deal price') . $price . L('Total turnover') . $mum . "\n";
                        $log .= 'ERR: Property buyers error,freeze Property is ' . $rmb . 'd' . $user_buy[$rmb . 'd'];
                        mlog($log);
                        mlog(json_encode($user_buy));
                        break;
                    }

                    $user_sell = M('UserCoin')->where(['userid' => $sell['userid']])->find();
                    //If userid is 0 it means liquidty is coming from LiqEngine
                    if (!$user_sell[$xnb . 'd'] && $sell['userid'] != 0) {
                        $log = 'error7 market:' . $market . 'Error: Buy OrderID:' . $buy['id'] . L(' Sell orderID: ') . $sell['id'] . L('TRANSACTION_TYPE') . $type . L('TRANSACTION_AMOUNT') . $amount . L('deal price') . $price . L('Total turnover') . $mum . "\n";
                        $log .= 'ERR: Sellers of property error,freeze Property is' . $user_sell[$xnb . 'd'];
                        mlog($log);
                        mlog($sell['userid']);
                        mlog(json_encode($user_sell));
                        mlog($xnb . 'd');
                        break;
                    }
                    // This line indicates some issue with Freezing funds of user , freezing funds are less than 0.000000001 , Means no money in order
                    //If userid is 0 it means liquidty is coming from LiqEngine
                    if ($user_buy[$rmb . 'd'] < 1.0E-8 && $user_buy['userid'] != 0) {
                        $log = '/n/n error88 market:' . $market . 'Error: Buy OrderID:' . $buy['id'] . L(' Sell orderID: ') . $sell['id'] . L('TRANSACTION_TYPE') . $type . L('TRANSACTION_AMOUNT') . $amount . L('deal price') . $price . L('Total turnover') . $mum . "\n";
                        $log .= 'ERR: Buyers Update Freeze currency appear error,Should be updated' . $buy_save . L('Account Balance') . $user_buy[$rmb . 'd'] . L('Error handling');
                        mlog($log);
                        M('Trade')->where(['id' => $buy['id']])->setField('status', 1);
                        break;
                    }

                    if ($buy_save <= format_num($user_buy[$rmb . 'd'], 8) && $user_buy['userid'] != 0) {
                        $save_buy_rmb = $buy_save;
                    } elseif ($buy_save <= bcadd($user_buy[$rmb . 'd'], 1, 8) && $user_buy['userid'] != 0) {
                        $save_buy_rmb = $user_buy[$rmb . 'd'];
                    } elseif ($user_buy['userid'] != 0) {
                        M('Trade')->where(['id' => $buy['id']])->setField('status', 1);
                        break;
                    }
                    // TODO: SEPARATE
                    $save_sell_xnb = 0;
                    if ($amount <= bcadd($user_sell[$xnb . 'd'], '0', 8) && $user_sell['userid'] != 0) {
                        $save_sell_xnb = $amount;
                    } elseif ($amount <= bcadd($user_sell[$xnb . 'd'], '1', 8) && $user_sell['userid'] != 0) {
                        $save_sell_xnb = $user_sell[$xnb . 'd'];
                        $log = 'error10 market:' . $market . 'Error: Buy OrderID:' . $buy['id'] . L(' Sell orderID: ') . $sell['id'] . ' ' . L('TYPE') . $type . L('TRANSACTION_AMOUNT') . $amount . L('deal price') . $price . L('Total turnover') . $mum . "\n";
                        $log .= 'ERR: The seller updated the frozen token with an error and should update ' . $amount . ' account balance ' . $user_sell[$xnb . 'd'] . ' Actual update ' . $save_sell_xnb;

                        mlog($log);
                    } elseif ($user_sell['userid'] != 0 && $user_buy['userid'] != 0) {
                        $log = 'error11 market:' . $market . 'Error: Buy OrderID:' . $buy['id'] . L(' Sell orderID: ') . $sell['id'] . L('TRANSACTION_TYPE') . $type . L('TRANSACTION_AMOUNT') . $amount . L('deal price') . $price . L('Total turnover') . $mum . "\n";
                        $log .= 'ERR: Sellers Update freeze Virtual currency appear error,Should be updated' . $amount . L('Account Balance') . $user_sell[$xnb . 'd'] . L('Error handling');
                        mlog($log);
                        M('Trade')->where(['id' => $sell['id']])->setField('status', 1);
                        break;
                    }

                    if (!isset($save_buy_rmb) && $user_sell['userid'] != 0 && $user_buy['userid'] != 0) {
                        $log = 'error12 market:' . $market . 'Error: Buy OrderID:' . $buy['id'] . L(' Sell orderID: ') . $sell['id'] . L('TRANSACTION_TYPE') . $type . L('TRANSACTION_AMOUNT') . $amount . L('deal price') . $price . L('Total turnover') . $mum . "\n";
                        $log .= 'ERR: Buyers Update Quantity Error,Update Quantity:';
                        mlog($log);
                        M('Trade')->where(['id' => $buy['id']])->setField('status', 1);
                        break;
                    }

                    if (!$save_sell_xnb && $user_sell['userid'] != 0 && $user_buy['userid'] != 0) {
                        $log = 'error13 market:' . $market . 'Error: Buy OrderID:' . $buy['id'] . L(' Sell orderID: ') . $sell['id'] . L('TRANSACTION_TYPE') . $type . L('TRANSACTION_AMOUNT') . $amount . L('deal price') . $price . L('Total turnover') . $mum . "\n";
                        $log .= 'ERR: Sellers Update Quantity Error,Update Quantity:' . $save_sell_xnb;
                        mlog($log);
                        M('Trade')->where(['id' => $sell['id']])->setField('status', 1);
                        break;
                    }
                    if ($invit_buy == 1) {
                        $invit_buy_user = $mo->table('codono_user')->where(['id' => $buy['userid']])->find();
                    }
                    if ($invit_buy == 1) {
                        $invit_sell_user = $mo->table('codono_user')->where(['id' => $sell['userid']])->find();
                    }
                    // if ($buy['userid'] == 0 && $sell['userid'] == 0) {
                    //  //   clog('0user',[$buy,$sell]);
                    //     M('Trade')->where(array('id' => $buy['id']))->setField('status', 1);
                    //     M('Trade')->where(array('id' => $sell['id']))->setField('status', 1);
                    //     break;
                    // }
                    $mo->startTrans();
                    try {
                        $rs[] = $mo->table('codono_trade')->where(['id' => $buy['id']])->setInc('deal', $amount);
                        $rs[] = $mo->table('codono_trade')->where(['id' => $sell['id']])->setInc('deal', $amount);
                        $rs[] = $finance_nameid = $mo->table('codono_trade_log')->add(['userid' => $buy['userid'], 'peerid' => $sell['userid'], 'market' => $market, 'price' => $price, 'num' => $amount, 'mum' => $mum, 'type' => $type, 'fee_buy' => $buy_fee, 'fee_sell' => $sell_fee, 'addtime' => time(), 'status' => 1]);
                        //TODO: Check here
                        if ($buy['userid'] != 0) {
                            //Balance before
                            $finance_num_user_coin = $mo->table('codono_user_coin')->where(['userid' => $buy['userid']])->field([$rmb, $rmb . 'd', $xnb, $xnb . 'd'])->find();
                            $rs[] = $mo->table('codono_user_coin')->where(['userid' => $buy['userid']])->setInc($xnb, $amount);
                            $rs[] = $mo->table('codono_user_coin')->where(['userid' => $buy['userid']])->setDec($rmb . 'd', $save_buy_rmb);

                            //Balance after
                            $finance_mum_user_coin = $mo->table('codono_user_coin')->where(['userid' => $buy['userid']])->field([$rmb, $rmb . 'd', $xnb, $xnb . 'd'])->find();
                            $finance_hash = md5($buy['userid'] . $finance_num_user_coin[$rmb] . $finance_num_user_coin[$rmb . 'd'] . $mum . $finance_mum_user_coin[$rmb] . $finance_mum_user_coin[$rmb . 'd'] . CODONOLIC . 'auth.codono.com');
                            $finance_num = bcadd($finance_num_user_coin[$rmb], $finance_num_user_coin[$rmb . 'd'], 8);
                            $finance_mum = bcadd($finance_mum_user_coin[$rmb], $finance_mum_user_coin[$rmb . 'd'], 8);

                            if ($rmb) {
                                $rs[] = $mo->table('codono_finance')->add(['userid' => $buy['userid'], 'coinname' => $rmb, 'num_a' => $finance_num_user_coin[$rmb], 'num_b' => $finance_num_user_coin[$rmb . 'd'], 'num' => $finance_num, 'fee' => $save_buy_rmb, 'type' => 2, 'name' => 'tradelog', 'nameid' => $finance_nameid, 'remark' => 'spot_buy:' . $market, 'mum_a' => $finance_mum_user_coin[$rmb], 'mum_b' => $finance_mum_user_coin[$rmb . 'd'], 'mum' => $finance_mum, 'move' => $finance_hash, 'addtime' => time(), 'status' => 1]);
                            }
                        }
                        //TODO: Check here
                        if ($sell['userid'] != 0) {
                            //Balance before
                            $finance_num_user_coin = $mo->table('codono_user_coin')->where(['userid' => $sell['userid']])->field([$rmb, $rmb . 'd', $xnb, $xnb . 'd'])->find();
                            $rs[] = $mo->table('codono_user_coin')->where(['userid' => $sell['userid']])->setInc($rmb, $sell_save);
                            $rs[] = $mo->table('codono_user_coin')->where(['userid' => $sell['userid']])->setDec($xnb . 'd', $save_sell_xnb);

                            //Balance After
                            $finance_mum_user_coin = $mo->table('codono_user_coin')->where(['userid' => $sell['userid']])->field([$rmb, $rmb . 'd', $xnb, $xnb . 'd'])->find();
                            $finance_hash = md5($sell['userid'] . $finance_num_user_coin[$rmb] . $finance_num_user_coin[$rmb . 'd'] . $mum . $finance_mum_user_coin[$rmb] . $finance_mum_user_coin[$rmb . 'd'] . CODONOLIC . 'auth.codono.com');
                            $finance_num = bcadd($finance_num_user_coin[$rmb], $finance_num_user_coin[$rmb . 'd'], 8);
                            $finance_mum = bcadd($finance_mum_user_coin[$rmb], $finance_mum_user_coin[$rmb . 'd'], 8);

                            if ($rmb) {
                                $rs[] = $mo->table('codono_finance')->add(['userid' => $sell['userid'], 'coinname' => $rmb, 'num_a' => $finance_num_user_coin[$rmb], 'num_b' => $finance_num_user_coin[$rmb . 'd'], 'num' => $finance_num, 'fee' => $save_buy_rmb, 'type' => 1, 'name' => 'tradelog', 'nameid' => $finance_nameid, 'remark' => 'spot_sell:' . $market, 'mum_a' => $finance_mum_user_coin[$rmb], 'mum_b' => $finance_mum_user_coin[$rmb . 'd'], 'mum' => $finance_mum, 'move' => $finance_hash, 'addtime' => time(), 'status' => 1]);
                            }
                        }

                        $buy_list = $mo->table('codono_trade')->where(['id' => $buy['id'], 'status' => 0])->find();

                        if ($buy_list) {
                            if ($buy_list['num'] <= $buy_list['deal']) {
                                $rs[] = $mo->table('codono_trade')->where(['id' => $buy['id']])->setField('status', 1);
                            }
                        }

                        $sell_list = $mo->table('codono_trade')->where(['id' => $sell['id'], 'status' => 0])->find();

                        if ($sell_list) {
                            if ($sell_list['num'] <= $sell_list['deal']) {
                                $rs[] = $mo->table('codono_trade')->where(['id' => $sell['id']])->setField('status', 1);
                            }
                        }

                        if ($price < $buy['price']) {
                            $spread = bcmul(bcdiv(bcmul($amount, $buy['price'], 8), 100, 8), bcadd(100, $fee_buy, 8), 8);
                            $base_difference = bcmul(bcdiv(bcmul($amount, $price, 8), 100, 8), bcadd(100, $fee_buy, 8), 8);
                            $diff = bcsub($spread, $base_difference, 8);

                            if ($diff) {
                                $diff_user_buy = $mo->table('codono_user_coin')->where(['userid' => $buy['userid']])->find();
                                if ($sell['userid'] != 0 && $buy['userid'] != 0) {
                                    if ($diff <= format_num($diff_user_buy[$rmb . 'd'], 8)) {
                                        $diff_save_buy_rmb = $diff;
                                    } elseif ($diff <= bcadd($diff_user_buy[$rmb . 'd'], 1, 8)) {
                                        $diff_save_buy_rmb = $diff_user_buy[$rmb . 'd'];
                                        mlog('error91 market:' . $market . 'Error: Buy OrderID:' . $buy['id'] . L(' Sell orderID: ') . $sell['id'] . L('TRANSACTION_TYPE') . $type . L('TRANSACTION_AMOUNT') . $amount . L('deal price') . $price . L('Total turnover') . $mum . "\n");
                                        mlog(L('market place') . $market . 'Error: Buy OrderID:' . $buy['id'] . L(' Sell orderID: ') . $sell['id'] . L('TRANSACTION_AMOUNT') . $amount . L('TRANSACTION_TYPE') . $type . 'Sellers Update freeze coin Error occurs,Should be updated' . $diff . L('Account Balance') . $diff_user_buy[$rmb . 'd'] . L('The actual update') . $diff_save_buy_rmb);
                                    } else {
                                        mlog('error92 market:' . $market . 'Error: Buy OrderID:' . $buy['id'] . L(' Sell orderID: ') . $sell['id'] . L('TRANSACTION_TYPE') . $type . L('TRANSACTION_AMOUNT') . $amount . L('deal price') . $price . L('Total turnover') . $mum . "\n");
                                        mlog(L('market place') . $market . 'Error: Buy OrderID:' . $buy['id'] . L(' Sell orderID: ') . $sell['id'] . L('TRANSACTION_AMOUNT') . $amount . L('TRANSACTION_TYPE') . $type . 'Sellers Update freeze coin appear error,Should be updated' . $diff . L('Account Balance') . $diff_user_buy[$rmb . 'd'] . L('Error handling'));
                                        $mo->rollback();

                                        M('Trade')->where(['id' => $buy['id']])->setField('status', 1);
                                        M('Trade')->execute('commit');
                                        break;
                                    }
                                }
                                if ($diff_save_buy_rmb) {
                                    //TODO: Check here
                                    if ($buy['userid'] != 0) {
                                        $rs[] = $mo->table('codono_user_coin')->where(['userid' => $buy['userid']])->setDec($rmb . 'd', $diff_save_buy_rmb);
                                        $rs[] = $mo->table('codono_user_coin')->where(['userid' => $buy['userid']])->setInc($rmb, $diff_save_buy_rmb);
                                    }
                                }
                            }
                        }

                        $you_buy = $mo->table('codono_trade')->where([
                            'market' => ['like', '%' . $rmb . '%'],
                            'status' => 0,
                            'userid' => $buy['userid']
                        ])->find();
                        $you_sell = $mo->table('codono_trade')->where([
                            'market' => ['like', '%' . $xnb . '%'],
                            'status' => 0,
                            'userid' => $sell['userid']
                        ])->find();

                        if (!$you_buy) {
                            $you_user_buy = $mo->table('codono_user_coin')->where(['userid' => $buy['userid']])->find();

                            if (0 < $you_user_buy[$rmb . 'd'] && $buy['userid'] != 0) {
                                $rs[] = $mo->table('codono_user_coin')->where(['userid' => $buy['userid']])->setField($rmb . 'd', 0);
                                $rs[] = $mo->table('codono_user_coin')->where(['userid' => $buy['userid']])->setInc($rmb, $you_user_buy[$rmb . 'd']);
                            }
                        }

                        if (!$you_sell && $sell['userid'] != 0) {
                            $you_user_sell = $mo->table('codono_user_coin')->where(['userid' => $sell['userid']])->find();

                            if (0 < $you_user_sell[$xnb . 'd']) {
                                $rs[] = $mo->table('codono_user_coin')->where(['userid' => $sell['userid']])->setField($xnb . 'd', 0);
                                $rs[] = $mo->table('codono_user_coin')->where(['userid' => $sell['userid']])->setInc($rmb, $you_user_sell[$xnb . 'd']);
                            }
                        }


                        $invit_buy_save_1 = $invit_buy_save_2 = $invit_buy_save_3 = $invit_sell_save_1 = $invit_sell_save_2 = $invit_sell_save_3 = 0;

                        if ($invit_buy && $buy_fee) {
                            $invit_buy_save_1 = $this->calculateAndApplyBonus($mo, $rmb, $invit_buy_user['invit_1'], $buy_fee, '1st', $invit_1, $market, 'buy', $amount, $mum, $rs, $buy['userid'], $sell['userid']);
                            $invit_buy_save_2 = $this->calculateAndApplyBonus($mo, $rmb, $invit_buy_user['invit_2'], $buy_fee, '2nd', $invit_2, $market, 'buy', $amount, $mum, $rs, $buy['userid'], $sell['userid']);
                            $invit_buy_save_3 = $this->calculateAndApplyBonus($mo, $rmb, $invit_buy_user['invit_3'], $buy_fee, '3rd', $invit_3, $market, 'buy', $amount, $mum, $rs, $buy['userid'], $sell['userid']);
                        }

                        if ($invit_sell && $sell_fee) {
                            $invit_sell_save_1 = $this->calculateAndApplyBonus($mo, $rmb, $invit_sell_user['invit_1'], $sell_fee, '1st', $invit_1, $market, 'sell', $amount, $mum, $rs, $buy['userid'], $sell['userid']);
                            $invit_sell_save_2 = $this->calculateAndApplyBonus($mo, $rmb, $invit_sell_user['invit_2'], $sell_fee, '2nd', $invit_2, $market, 'sell', $amount, $mum, $rs, $buy['userid'], $sell['userid']);
                            $invit_sell_save_3 = $this->calculateAndApplyBonus($mo, $rmb, $invit_sell_user['invit_3'], $sell_fee, '3rd', $invit_3, $market, 'sell', $amount, $mum, $rs, $buy['userid'], $sell['userid']);
                        }


                        /* Code to shift fees to exchnage owner frontend account start*/
                        $invit_buy_total_paid = bcsum([$invit_buy_save_1, $invit_buy_save_2, $invit_buy_save_3], 8);
                        $invit_sell_total_paid = bcsum([$invit_sell_save_1, $invit_sell_save_2, $invit_sell_save_3], 8);

                        $ownerid = C('market')[$market]['ownerid'];

                        if ($ownerid > 0) {
                            $send_buy_fees_to_owner = bcsub($buy_fee, $invit_buy_total_paid, 8);
                            $send_sell_fees_to_owner = bcsub($sell_fee, $invit_sell_total_paid, 8);

                            $find_owner = $mo->table('codono_user')->where(['id' => $ownerid])->getField('id');
                            if ($find_owner == $ownerid) {

                                $rs[] = $mo->table('codono_user_coin')->where(['userid' => $ownerid])->setInc($rmb, $send_buy_fees_to_owner);
                                $rs[] = $mo->table('codono_invit')->add(['coin' => $rmb, 'userid' => $ownerid, 'invit' => $buy['userid'], 'name' => 'Trade Buy Fees', 'type' => $market . 'TradeBuyFees', 'num' => $amount, 'mum' => $mum, 'fee' => $send_buy_fees_to_owner, 'addtime' => time(), 'status' => 1]);

                                $rs[] = $mo->table('codono_user_coin')->where(['userid' => $ownerid])->setInc($rmb, $send_sell_fees_to_owner);
                                $rs[] = $mo->table('codono_invit')->add(['coin' => $rmb, 'userid' => $ownerid, 'invit' => $sell['userid'], 'name' => 'Trade Sell Fees', 'type' => $market . 'TradeSellFees', 'num' => $amount, 'mum' => $mum, 'fee' => $send_sell_fees_to_owner, 'addtime' => time(), 'status' => 1]);
                            }
                        }
                        if (check_arr($rs)) {
                            $mo->commit();
                            $new_trade_codono = 1;

                            S('allsum', null);
                            S('getJsonTop' . $market, null);
                            S('getTradelog' . $market, null);

                            S('getDepth' . $market . '1', null);
                            S('getDepth' . $market . '3', null);
                            S('getDepth' . $market . '4', null);
                            S('getActiveDepth' . $market, null);
                            S('getActiveDepth' . $market . '1', null);
                            S('getActiveDepth' . $market . '3', null);
                            S('getActiveDepth' . $market . '4', null);

                            S('getDepthNew' . $market . '1', null);
                            S('getDepthNew' . $market . '3', null);
                            S('getDepthNew' . $market . '4', null);

                            S('ChartgetJsonData' . $market, null);
                            S('allcoin', null);
                            S('allCoinPrice', null);
                            S('trends', null);
                        } else {
                            clog('doMatchingTrade', $rs);
                            $mo->rollback();
                        }
                    } catch (Exception $e) {
                        $mo->rollback();
                        clog('matching_engine_exception', $e->getMessage());
                        // Additional error handling if needed
                        return false;
                    }
                    /* Code to shift fees to exchnage owner frontend account start*/
                    if (strict_check_arr($rs)) {
                        $mo->commit();
                        $new_trade_codono = 1;

                        S('allsum', null);
                        S('getJsonTop' . $market, null);
                        S('getTradelog' . $market, null);

                        S('getDepth' . $market . '1', null);
                        S('getDepth' . $market . '3', null);
                        S('getDepth' . $market . '4', null);
                        S('getActiveDepth' . $market, null);
                        S('getActiveDepth' . $market . '1', null);
                        S('getActiveDepth' . $market . '3', null);
                        S('getActiveDepth' . $market . '4', null);

                        S('getDepthNew' . $market . '1', null);
                        S('getDepthNew' . $market . '3', null);
                        S('getDepthNew' . $market . '4', null);

                        S('ChartgetJsonData' . $market, null);
                        S('allcoin', null);
                        S('allCoinPrice', null);
                        S('trends', null);
                    } else {
                        $mo->rollback();
                    }
                } else {
                    break;
                }

                unset($rs);
            }

            if ($new_trade_codono && C('market')[$market]['ext_price_update'] == 0) {
                $new_price = format_num(M('TradeLog')->where(['market' => $market, 'status' => 1])->order('id desc')->getField('price'), 8);
                $buy_price = format_num(M('Trade')->where(['type' => 1, 'market' => $market, 'status' => 0])->max('price'), 8);
                $sell_price = format_num(M('Trade')->where(['type' => 2, 'market' => $market, 'status' => 0])->min('price'), 8);
                $min_price = format_num(M('TradeLog')->where([
                    'market' => $market,
                    'addtime' => ['gt', time() - (60 * 60 * 24)]
                ])->min('price'), 8);
                $max_price = format_num(M('TradeLog')->where([
                    'market' => $market,
                    'addtime' => ['gt', time() - (60 * 60 * 24)]
                ])->max('price'), 8);
                $volume = format_num(M('TradeLog')->where([
                    'market' => $market,
                    'addtime' => ['gt', time() - (60 * 60 * 24)]
                ])->sum('num'), 8);
                $sta_price = format_num(M('TradeLog')->where([
                    'market' => $market,
                    'status' => 1,
                    'addtime' => ['gt', time() - (60 * 60 * 24)]
                ])->order('id asc')->getField('price'), 8);
                $Cmarket = C('market')[$market]; //M('Market')->where(array('name' => $market))->find();

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
                //$hourly_price = $Cmarket['hou_price'] ? $Cmarket['hou_price'] : 1;
                //$change = bcmul(bcdiv(bcsub($new_price , $Cmarket['hou_price'],8), $hourly_price,8) , 100, 2);
                $old_price = NumToStr($Cmarket['new_price']);
                $change = bcmul(bcdiv(bcsub($new_price, $old_price, 8), $old_price, 8), 100, 2);
                $upCoinData['change'] = $change;

                if ($upCoinData) {
                    M('Market')->where(['name' => $market])->save($upCoinData);
                    M('Market')->execute('commit');
                    S('home_market', null);
                }
            }
            $this->callStopMatching($market, $type);

            //unlock the lock
            flock($fp, LOCK_UN);
            if (S('matching_queue_' . $market) == 1) {
                S('matching_queue_' . $market, 0);
                $this->domatchingTrade($market);
            }
        } else {
            S('matching_queue_' . $market, 1);
        }

        if (M_DEBUG == 1) {
            G('end');
            clog('matching_engine', '<br/>doMatchingTrade Time taken ' . G('begin', 'end') . ' s');
        }
        return true;
    }


    public function upTradeLimit($price, $num, $type, $paypassword = NULL, $market = NULL, $override = [])
    {
        if (is_array($override) && !empty($override) && $override['userid'] > 0) {
            $user_pass = cryptString($override['code'], 'd');
            $userid = $override['userid'];
            $find_uid = M('User')->where(['id' => $userid, 'password' => $user_pass])->getField('id');

            if ((int)$find_uid == (int)$userid) {
                $uid = (int)$find_uid;
            } else {
                exit;
            }
        } else {
            $uid = userid();
        }
        //
        //clog('uptx',[$uid,$price, $num, $type, $paypassword , $market ,$override]);

        if (!$uid) {
            $this->error(L('PLEASE_LOGIN'));
        }

        if (C('market')[$market]['begintrade']) {
            $begintrade = C('market')[$market]['begintrade'];
        } else {
            $begintrade = '00:00:00';
        }

        if (C('market')[$market]['endtrade']) {
            $endtrade = C('market')[$market]['endtrade'];
        } else {
            $endtrade = '23:59:59';
        }


        $trade_begin_time = strtotime(date('Y-m-d') . ' ' . $begintrade);
        $trade_end_time = strtotime(date('Y-m-d') . ' ' . $endtrade);
        $cur_time = time();

        if ($cur_time < $trade_begin_time || $cur_time > $trade_end_time) {
            $this->error('The current market is prohibited transaction,Trading hours daily' . $begintrade . '-' . $endtrade);
        }


        if (!check($price, 'double')) {
            $this->error(L('The transaction price is malformed'));
        }

        if (!check($num, 'double')) {
            $this->error(L('The number of transactions is malformed'));
        }

        if (($type != 1) && ($type != 2)) {
            $this->error(L('Transaction type format error'));
        }

        $user = $this->userinfo;
        if (IF_TRADING_PASS == 1) {

            if ($user['tpwdsetting'] == 2) {
                if (md5($paypassword) != $user['paypassword']) {
                    $this->error(L('Trading password is wrong!'));
                }
            }

            if ($user['tpwdsetting'] == 1) {
                if (!session($uid . 'tpwdsetting')) {
                    if (md5($paypassword) != $user['paypassword'] && $paypassword != $user['paypassword']) {
                        $this->error(L('Trading password is wrong!'));
                    } else {
                        session($uid . 'tpwdsetting', 1);
                    }
                }
            }
        }


        if (!C('market')[$market]) {
            $this->error(L('Error market'));
        } else {
            $xnb = explode('_', $market)[0];
            $rmb = explode('_', $market)[1];
        }

        // TODO: SEPARATE

        $price = format_num($price, 8);

        if (!$price) {
            $this->error(L('Transaction price error') . $price);
        }


        if (!check($num, 'double')) {
            $this->error(L('INCORRECT_QTY'));
        }
        $max_price = $min_price = 0;
        if ($type == 1) {
            $min_price = (C('market')[$market]['buy_min'] ? C('market')[$market]['buy_min'] : 1.0E-8);
            $max_price = (C('market')[$market]['buy_max'] ? C('market')[$market]['buy_max'] : 10000000);
        } elseif ($type == 2) {
            $min_price = (C('market')[$market]['sell_min'] ? C('market')[$market]['sell_min'] : 1.0E-8);
            $max_price = (C('market')[$market]['sell_max'] ? C('market')[$market]['sell_max'] : 10000000);
        } else {
            $this->error(L('Transaction type error'));
        }

        if ($max_price < $price) {
            $this->error(L('Trading price exceeding the maximum limit!'));
        }

        if ($price < $min_price) {
            $this->error(L('Price is low,Keep it above') . ' ' . $min_price);
        }

        $hou_price = C('market')[$market]['hou_price'];

        if (!$hou_price) {
            $hou_price = C('market')[$market]['market_ico_price'];
        }

        if ($hou_price) {
            if (C('market')[$market]['zhang']) {
                $zhang_price = bcmul(bcdiv($hou_price, '100', 8), bcadd('100', C('market')[$market]['zhang'], 8), 8);

                if ($zhang_price < $price) {
                    $this->error(L('Transaction prices rose more than limit today!'));
                }
            }

            if (C('market')[$market]['die']) {
                $die_price = bcmul(bcdiv($hou_price, '100', C('market')[$market]['round']), bcsub('100', C('market')[$market]['die'], C('market')[$market]['round']), C('market')[$market]['round']);

                if ($price < $die_price) {
                    $this->error(L('Least sale bid suggested:') . $rmb . ' ' . $die_price);
                }
            }
        }

        $user_coin = M('UserCoin')->where(['userid' => $uid])->find();

        if ($type == 1) {
            $trade_fee = quick_discount($user['usertype'], C('market')[$market]['fee_buy']);
            if ($trade_fee) {
                $fee = bcmul(bcdiv(bcmul($num, $price, 8), '100', 8), $trade_fee, 8);
                $mum = bcmul(bcdiv(bcmul($num, $price, 8), '100', 8), bcadd('100', $trade_fee, 8), 8);
            } else {
                $fee = 0;
                $mum = bcmul($num, $price, 8);
            }
            if ($user_coin[$rmb] < $mum) {
                $this->error(L('INSUFFICIENT') . ' ' . strtoupper($rmb));
            }
        } elseif ($type == 2) {
            $trade_fee = quick_discount($user['usertype'], C('market')[$market]['fee_sell']); //discount(C('market')[$market]['fee_sell'], $uid, $user['usertype']);

            if ($trade_fee) {
                $fee = bcmul(bcdiv(bcmul($num, $price, 8), '100', 8), $trade_fee, 8);
                $mum = bcmul(bcdiv(bcmul($num, $price, 8), '100', 8), bcadd('100', $trade_fee, 8), 8);
            } else {
                $fee = 0;
                $mum = bcmul($num, $price, 8);
            }

            if ($user_coin[$xnb] < $num) {
                $this->error(L('INSUFFICIENT') . ' ' . strtoupper($xnb));
            }
        } else {
            $this->error(L('Transaction type error'));
        }


        if (C('market')[$market]['trade_min']) {
            if ($mum < C('market')[$market]['trade_min']) {
                $this->error(L('THE_MINIMUM_AMOUNT_FOR_EACH_PU') . ' ' . C('coin')[$rmb]['title'] . C('market')[$market]['trade_min']);
            }
        }

        if (C('market')[$market]['trade_max']) {
            if (C('market')[$market]['trade_max'] < $mum) {
                $this->error(L('MAX_ORDER_QTY') . C('market')[$market]['trade_max']);
            }
        }

        if (!$rmb) {
            $this->error('data error1');
        }

        if (!$xnb) {
            $this->error('data error2');
        }

        if (!$market) {
            $this->error('data error3');
        }

        if (!$price) {
            $this->error('data error4');
        }

        if (!$num) {
            $this->error('data error5');
        }

        if (!$mum) {
            $this->error('data error6');
        }

        if (!$type) {
            $this->error('data error7');
        }

        $mo = M();
        $mo->startTrans();

        $rs = [];
        $user_coin = $mo->table('codono_user_coin')->where(['userid' => $uid])->field([$rmb, $rmb . 'd', $xnb, $xnb . 'd'])->find();
        $finance_num_user_coin = $user_coin;
        if ($type == 1) {
            if ($user_coin[$rmb] < $mum) {
                $this->error(L('INSUFFICIENT') . C('coin')[$rmb]['title']);
            }
            $rs[] = $mo->table('codono_user_coin')->where(['userid' => $uid])->setDec($rmb, $mum);
            $rs[] = $mo->table('codono_user_coin')->where(['userid' => $uid])->setInc($rmb . 'd', $mum);
            $rs[] = $finance_nameid = $mo->table('codono_trade')->add(['userid' => $uid, 'market' => $market, 'price' => $price, 'num' => $num, 'mum' => $mum, 'fee' => $fee, 'type' => 1, 'addtime' => time(), 'status' => 0]);

            //2nd part of pair
            if ($rmb) {
                $finance_mum_user_coin = $mo->table('codono_user_coin')->where(['userid' => $uid])->field([$rmb, $rmb . 'd', $xnb, $xnb . 'd'])->find();
                $finance_hash = md5($uid . time() . $finance_nameid);
                $rs[] = $mo->table('codono_finance')->add(['userid' => $uid, 'coinname' => $rmb, 'num_a' => $finance_num_user_coin[$rmb], 'num_b' => $finance_num_user_coin[$rmb . 'd'], 'num' => $finance_num_user_coin[$rmb] + $finance_num_user_coin[$rmb . 'd'], 'fee' => $mum, 'type' => 2, 'name' => 'trade', 'nameid' => $finance_nameid, 'remark' => 'spot_buy_commission:' . $market, 'mum_a' => $finance_mum_user_coin[$rmb], 'mum_b' => $finance_mum_user_coin[$rmb . 'd'], 'mum' => $finance_mum_user_coin[$rmb] + $finance_mum_user_coin[$rmb . 'd'], 'move' => $finance_hash, 'addtime' => time(), 'status' => 1]);
            }
        } elseif ($type == 2) {
            if ($user_coin[$xnb] < $num) {
                $this->error(C('coin')[$xnb]['title'] . 'Insufficient balance2!');
            }

            $rs[] = $mo->table('codono_user_coin')->where(['userid' => $uid])->setDec($xnb, $num);
            $rs[] = $mo->table('codono_user_coin')->where(['userid' => $uid])->setInc($xnb . 'd', $num);
            $rs[] = $finance_nameid = $mo->table('codono_trade')->add(['userid' => $uid, 'market' => $market, 'price' => $price, 'num' => $num, 'mum' => $mum, 'fee' => $fee, 'type' => 2, 'addtime' => time(), 'status' => 0]);
            if ($xnb) {
                $finance_mum_user_coin = $mo->table('codono_user_coin')->where(['userid' => $uid])->field([$xnb, $xnb . 'd', $xnb, $xnb . 'd'])->find();
                $finance_hash = md5($uid . time() . $finance_nameid);

                $rs[] = $mo->table('codono_finance')->add(['userid' => $uid, 'coinname' => $xnb, 'num_a' => $finance_num_user_coin[$xnb], 'num_b' => $finance_num_user_coin[$xnb . 'd'], 'num' => $finance_num_user_coin[$xnb] + $finance_num_user_coin[$xnb . 'd'], 'fee' => $mum, 'type' => 1, 'name' => 'trade', 'nameid' => $finance_nameid, 'remark' => 'spot_sell_commission:' . $market, 'mum_a' => $finance_mum_user_coin[$xnb], 'mum_b' => $finance_mum_user_coin[$xnb . 'd'], 'mum' => $finance_mum_user_coin[$xnb] + $finance_mum_user_coin[$xnb . 'd'], 'move' => $finance_hash, 'addtime' => time(), 'status' => 1]);
            }
        } else {
            $mo->rollback();
            $this->error(L('Transaction type error'));
        }
        if (strict_check_arr($rs)) {
            $mo->commit();
            S('getDepth', null);
            S('getActiveDepth', null);
            S('getActiveDepth' . $market, null);
            S('getDepthNew', null);

            //$this->matchingTrade($market) slow query //Matches the orders
            $this->callMatchingTrade($market);
            $this->success(L('Trading success!'));
        } else {
            $mo->rollback();
            $this->error(L('transaction failed!'));
        }
    }


    public function reject($id)
    {
        $uid = userid();
        if (!$uid) {
            $this->error(L('PLEASE_LOGIN'));
        }

        if (!check($id, 'd')) {
            $this->error(L('Please select Undo commission!'));
        }
        $where = ['userid' => $uid, 'id' => $id, 'status' => 0];
        $trade = M('Trade')->where($where)->find();

        if ($trade['userid'] != $uid) {
            $this->error(L('No such trade under your orders!'));
        }

        if (!$trade) {
            $this->error(L('No such trade for you !'));
        }
        $this->callStopMatching($trade['market'], $trade['type']);
        $this->tshow(D('Trade')->reject($id));
    }

    private function tshow($rs = [])
    {
        if ($rs[0]) {
            $this->success($rs[1]);
        } else {
            $this->error($rs[1]);
        }
    }


    public function config()
    {
        $marketname = trim(I('get.symbol', 0, 'htmlspecialchars'));
        if (!$marketname) {
            $market = C('market_mr');
        } else {
            $market = strtolower($marketname);
        }
        $markets = C('market');
        $data['exchanges'] = ['value' => 'Codono', 'name' => 'Codono', 'desc' => 'Codono'];
        $data['supported_resolutions'] = ['1', '3', '5', '15', '30', '60', '120', '240', '360', '720', '1D', '1W', '1M'];
        $data['supports_group_request'] = false;
        $data['supports_marks'] = false;
        $data['supports_timescale_marks'] = false;
        $data['supports_search'] = true;
        $data['has_daily'] = true;
        $data['supports_time'] = true;
        $data['max_bars'] = 10080;
        $data['type'] = 'bitcoin';
        foreach ($markets as $market) {
            $data['symbols_types'][] = ['name' => $market['name'], 'value' => $market['name']];
        }

        echo json_encode($data);
    }

    public function symbols()
    {
        $marketname = trim(I('get.symbol', 0, 'htmlspecialchars'));
        if (!$marketname) {
            $marketname = C('market_mr');
        } else {
            $market = C('market')[$marketname]; //D('Market')->where(["name" => $marketname])->find();
        }
        $market = C('market')[$marketname];

        if (!C('MARKET')[$marketname]['round']) {
            $round = 6;
        } else {
            $round = (int)C('MARKET')[$marketname]['round'];
        }
        $pricescale = pow(10, $round);

        $data = ['name' => $market['name'], 'full_name' => $market['name'], 'symbol' => $market['name'], 'exchange' => '', 'exchange-traded' => '', 'exchange-listed' => '', 'timezone' => 'UTC', 'pricescale' => 100000000, 'minmov' => 1, 'minmove2' => 0, 'has_intraday' => true, 'intraday_multipliers' =>  ["1", "3", "5", "15", "30", "60", "120", "240", "360", "720", "1D", "3D", "1W", "1M"], 'has_daily' => true, 'has_weekly_and_monthly' => false, 'has_empty_bars' => true, 'force_session_rebuild' => false, 'has_no_volume' => false, 'has_fractional_volume' => false, 'ticker' => $market['name'], 'description' => '', 'session' => '24x7', 'data_status' => 'streaming', 'supported_resolutions' => ['1', '3', '5', '15', '30', '60', '120', '180', '240', '360', '720', '1D', '3D', '1W', '1M'], 'type' => 'bitcoin'];
        echo json_encode($data);
    }
    public function history()
    {
        $type = trim(I('get.resolution', '', 'htmlspecialchars'));
        $str_time = intval(I('get.from', 0, 'htmlspecialchars'));
        $end_time = intval(I('get.to', 0, 'htmlspecialchars'));
        $market = trim(I('get.symbol', '', 'htmlspecialchars'));

        if (!$type || !$str_time || !$end_time || !$market) {
            $data = ['s' => 'error', 'errmsg' => 'Invalid parameters'];
            exit(json_encode($data));
        }

        if ($type == 'D') {
            $type = 1440;
        }

        $trade_json = M('TradeJson')->where([
            'market' => $market,
            'type' => $type,
            //'addtime' => ['between', [$str_time, $end_time]]
        ])->order('addtime desc')->limit(300)->select();

        $data = [];
        krsort($trade_json);

        if ($trade_json) {
            foreach ($trade_json as $v) {
                $tmp = json_decode($v['data'], true);
                if ($tmp[5] === NULL) continue;
                $data['c'][] = $tmp[5];
                $data['h'][] = $tmp[3];
                $data['l'][] = $tmp[4];
                $data['o'][] = $tmp[2];
                $data['v'][] = $tmp[1];
                $data['t'][] = $tmp[0];
            }
            $data['s'] = 'ok';
        } else {
            $data = ['s' => 'no_data'];
        }

        exit(json_encode($data, JSON_NUMERIC_CHECK));
    }

    public function history_v2()
    {
        //$data = (APP_DEBUG ? null : S('historyData'));
        $type = I('get.resolution');
        $str_time = I('get.from');
        $end_time = I('get.to');
        $market = I('get.symbol');
        //$trade_json = M("TradeJson")->where(["market" => $market, "type" => $type, "data#!=" => "","endtime"=> array('elt', $end_time)])->order("addtime desc")->limit(300)->select();
        //$trade_json = M("TradeJson")->where(["market" => $market, "type" => $type, "data#!=" => "","addtime"=> array('egt', $str_time),"endtime"=> array('elt', $end_time)])->order("addtime desc")->select();
        if ($type == 'D') {
            $type = 1440;
        }
        $trade_json = M('TradeJson')->where(['market' => $market, 'type' => $type])->order('addtime desc')->limit(300)->select();

        $data = [];
        krsort($trade_json);
        if ($trade_json) {
            foreach ($trade_json as $k => $v) {
                $tmp = json_decode($v['data'], true);
                if ($tmp[5] == NULL) continue;
                $data['c'][] = $tmp[5];
                $data['h'][] = $tmp[3];
                $data['l'][] = $tmp[4];
                $data['o'][] = $tmp[2];
                $data['v'][] = $tmp[1];
                $data['t'][] = $tmp[0];
            }
            $data['s'] = 'ok';
            //S('historyData', $data);
        }
        exit(json_encode($data, JSON_NUMERIC_CHECK));
    }


    /*Mark that stop order as executed to it never executes again*/

    public function statics($market)
    {
        if (!$market) {
            $marketall = D('Market')->where(['status' => 1])->find();
        } else {
            $marketall = D('Market')->where(['name' => $market])->find();
        }
        $this->assign('market', $marketall);
        $this->display();
    }

    //private function executeStop($market = NULL, $price, $num, $type,$userid)

    public function librarys($market = NULL, $ajax = 'json')
    {
        $data = (APP_DEBUG ? null : S('getJsonTop' . $market));

        if (!$data) {
            if ($market) {
                $xnb = explode('_', $market)[0];
                $rmb = explode('_', $market)[1];

                foreach (C('market') as $k => $v) {
                    $v['xnb'] = explode('_', $v['name'])[0];
                    $v['rmb'] = explode('_', $v['name'])[1];
                    $data['list'][$k]['name'] = $v['name'];
                    $data['list'][$k]['img'] = $v['xnbimg'];
                    $data['list'][$k]['title'] = $v['title'];
                    $data['list'][$k]['new_price'] = $v['new_price'];
                }

                $data['info']['img'] = C('market')[$market]['xnbimg'];
                $data['info']['title'] = C('market')[$market]['title'];
                $data['info']['new_price'] = C('market')[$market]['new_price'];

                if (C('market')[$market]['max_price']) {
                    $data['info']['max_price'] = C('market')[$market]['max_price'];
                } else {
                    $codono_tempprice = bcmul(bcdiv(C('market')[$market]['market_ico_price'], '100', C('market')[$market]['round']), bcadd('100', C('market')[$market]['zhang'], C('market')[$market]['round']), C('market')[$market]['round']);

                    $data['info']['max_price'] = $codono_tempprice;
                }

                if (C('market')[$market]['min_price']) {
                    $data['info']['min_price'] = C('market')[$market]['min_price'];
                } else {
                    $codono_tempprice = bcmul(bcdiv(C('market')[$market]['market_ico_price'], '100', C('market')[$market]['round']), bcsub('100', C('market')[$market]['die'], C('market')[$market]['round']), C('market')[$market]['round']);

                    $data['info']['min_price'] = $codono_tempprice;
                }


                $data['info']['buy_price'] = C('market')[$market]['buy_price'];
                $data['info']['sell_price'] = C('market')[$market]['sell_price'];
                $data['info']['volume'] = C('market')[$market]['volume'];
                $data['info']['change'] = C('market')[$market]['change'];
                S('getJsonTop' . $market, $data);
            }
        }

        if ($ajax) {
            exit(json_encode($data));
        } else {
            return $data;
        }
    }

    public function saveStop($price, $num, $type, $stop, $paypassword = NULL, $market = NULL)
    {
        $uid = userid();
        if (!$uid) {
            $this->error(L('PLEASE_LOGIN'));
        }

        if (C('market')[$market]['begintrade']) {
            $begintrade = C('market')[$market]['begintrade'];
        } else {
            $begintrade = '00:00:00';
        }

        if (C('market')[$market]['endtrade']) {
            $endtrade = C('market')[$market]['endtrade'];
        } else {
            $endtrade = '23:59:59';
        }


        $trade_begin_time = strtotime(date('Y-m-d') . ' ' . $begintrade);
        $trade_end_time = strtotime(date('Y-m-d') . ' ' . $endtrade);
        $cur_time = time();

        if ($cur_time < $trade_begin_time || $cur_time > $trade_end_time) {
            $this->error('The current market is prohibited transaction,Trading hours daily' . $begintrade . '-' . $endtrade);
        }


        if (!check($price, 'double')) {
            $this->error(L('Limit Price is incorrect'));
        }
        if (!check($stop, 'double')) {
            $this->error(L('Limit Price is incorrect'));
        }

        if (!check($num, 'double')) {
            $this->error(L('Quantity is incorrect'));
        }

        if (($type != 1) && ($type != 2)) {
            $this->error(L('Transaction type format error'));
        }
        $current = $this->getPriceForStop($market, $type); //best price
        if ($stop < $current) {
            $compare = 'gt';
        } else {
            $compare = 'lt';
        }
        $user = $this->userinfo;
        if (IF_TRADING_PASS == 1) {
            if ($user['tpwdsetting'] == 3) {
            }

            if ($user['tpwdsetting'] == 2) {
                if (md5($paypassword) != $user['paypassword']) {
                    $this->error(L('Trading password is wrong!'));
                }
            }

            if ($user['tpwdsetting'] == 1) {
                if (!session($uid . 'tpwdsetting')) {
                    if (md5($paypassword) != $user['paypassword'] && $paypassword != $user['paypassword']) {
                        $this->error(L('Trading password is wrong!'));
                    } else {
                        session($uid . 'tpwdsetting', 1);
                    }
                }
            }
        }


        if (!C('market')[$market]) {
            $this->error(L('Error market'));
        } else {
            $xnb = explode('_', $market)[0];
            $rmb = explode('_', $market)[1];
        }

        // TODO: SEPARATE

        $price = bcadd(floatval($price), '0', 8);
        $stop = bcadd(floatval($stop), '0', 8);
        if (!$price) {
            $this->error(L('Limit price error') . $price);
        }
        if (!$stop) {
            $this->error(L('Stop price error') . $price);
        }


        if (!check($num, 'double')) {
            $this->error(L('INCORRECT_QTY'));
        }

        if ($type == 1) {
            $min_price = (C('market')[$market]['buy_min'] ? C('market')[$market]['buy_min'] : 1.0E-8);
            $max_price = (C('market')[$market]['buy_max'] ? C('market')[$market]['buy_max'] : 10000000);
        } elseif ($type == 2) {
            $min_price = (C('market')[$market]['sell_min'] ? C('market')[$market]['sell_min'] : 1.0E-8);
            $max_price = (C('market')[$market]['sell_max'] ? C('market')[$market]['sell_max'] : 10000000);
        } else {
            $this->error(L('Transaction type error'));
        }

        if ($max_price < $price) {
            $this->error(L('Trading price exceeding the maximum limit!'));
        }

        if ($price < $min_price) {
            $this->error(L('Price is low,Keep it above') . ' ' . $min_price);
        }

        $hou_price = C('market')[$market]['hou_price'];

        if (!$hou_price) {
            $hou_price = C('market')[$market]['market_ico_price'];
        }

        if ($hou_price) {
            if (C('market')[$market]['zhang']) {
                $zhang_price = bcmul(bcdiv($hou_price, '100', 8), bcadd('100', C('market')[$market]['zhang'], 8), 8);

                if ($zhang_price < $price) {
                    $this->error(L('Transaction prices rose more than limit today!'));
                }
            }

            if (C('market')[$market]['die']) {
                $die_price = bcmul(bcdiv($hou_price, '100', C('market')[$market]['round']), bcsub('100', C('market')[$market]['die'], C('market')[$market]['round']), C('market')[$market]['round']);

                if ($price < $die_price) {
                    $this->error(L('Least sale bid suggested::') . $rmb . ' ' . $die_price);
                }
            }
        }

        $user_coin = M('UserCoin')->where(['userid' => $uid])->find();

        if ($type == 1) {
            $trade_fee = C('market')[$market]['fee_buy'];

            if ($trade_fee) {
                $fee = bcmul(bcdiv(bcmul($num, $price, 8), '100', 8), $trade_fee, 8);
                $mum = bcmul(bcdiv(bcmul($num, $price, 8), '100', 8), bcadd('100', $trade_fee, 8), 8);
            } else {
                $fee = 0;
                $mum = bcmul($num, $price, 8);
            }

            if ($user_coin[$rmb] < $mum) {
                $this->error(L('INSUFFICIENT') . C('coin')[$rmb]['title']);
            }
        } elseif ($type == 2) {
            $trade_fee = C('market')[$market]['fee_sell'];

            if ($trade_fee) {
                $fee = bcmul(bcdiv(bcmul($num, $price, 8), '100', 8), $trade_fee, 8);
                $mum = bcmul(bcdiv(bcmul($num, $price, 8), '100', 8), bcadd('100', $trade_fee, 8), 8);
            } else {
                $fee = 0;
                $mum = bcmul($num, $price, 8);
            }

            if ($user_coin[$xnb] < $num) {
                $this->error(L('INSUFFICIENT') . C('coin')[$xnb]['title']);
            }
        } else {
            $this->error(L('Transaction type error'));
        }

        if (C('market')[$market]['trade_min']) {
            if ($mum < C('market')[$market]['trade_min']) {
                $this->error(L('THE_MINIMUM_AMOUNT_FOR_EACH_PU') . ' ' . C('coin')[$rmb]['title'] . C('market')[$market]['trade_min']);
            }
        }

        if (C('market')[$market]['trade_max']) {
            if (C('market')[$market]['trade_max'] < $mum) {
                $this->error(L('MAX_ORDER_QTY') . C('market')[$market]['trade_max']);
            }
        }

        if (!$rmb) {
            $this->error('data error1');
        }

        if (!$xnb) {
            $this->error('data error2');
        }

        if (!$market) {
            $this->error('data error3');
        }

        if (!$price) {
            $this->error('data error4');
        }

        if (!$num) {
            $this->error('data error5');
        }

        if (!$mum) {
            $this->error('data error6');
        }

        if (!$type) {
            $this->error('data error7');
        }

        $mo = M();

        $mo->startTrans();
        $rs = [];
        $user_coin = $mo->table('codono_user_coin')->where(['userid' => $uid])->find();

        if ($type == 1) {
            if ($user_coin[$rmb] < $mum) {
                $this->error(L('INSUFFICIENT') . C('coin')[$rmb]['title']);
            }

            $finance = $mo->table('codono_finance')->where(['userid' => $uid])->order('id desc')->find();
            $finance_num_user_coin = $mo->table('codono_user_coin')->where(['userid' => $uid])->find();
            $rs[] = $mo->table('codono_user_coin')->where(['userid' => $uid])->setDec($rmb, $mum);
            $rs[] = $mo->table('codono_user_coin')->where(['userid' => $uid])->setInc($rmb . 'd', $mum);
            $rs[] = $finance_nameid = $mo->table('codono_stop')->add(['userid' => $uid, 'compare' => $compare, 'market' => $market, 'price' => $price, 'stop' => $stop, 'num' => $num, 'mum' => $mum, 'fee' => $fee, 'type' => 1, 'addtime' => time(), 'status' => 0]);


            if ($rmb) {
                $finance_mum_user_coin = $mo->table('codono_user_coin')->where(['userid' => $uid])->find();
                $finance_hash = md5($uid . $finance_num_user_coin[$rmb] . $finance_num_user_coin[$rmb . 'd'] . $mum . $finance_mum_user_coin[$rmb] . $finance_mum_user_coin[$rmb . 'd'] . CODONOLIC . 'auth.codono.com');
                $finance_num = $finance_num_user_coin[$rmb] + $finance_num_user_coin[$rmb . 'd'];

                if ($finance['mum'] < $finance_num) {
                    $finance_status = (1 < ($finance_num - $finance['mum']) ? 0 : 1);
                } else {
                    $finance_status = (1 < ($finance['mum'] - $finance_num) ? 0 : 1);
                }

                $rs[] = $mo->table('codono_finance')->add(['userid' => $uid, 'coinname' => $rmb, 'num_a' => $finance_num_user_coin[$rmb], 'num_b' => $finance_num_user_coin[$rmb . 'd'], 'num' => $finance_num_user_coin[$rmb] + $finance_num_user_coin[$rmb . 'd'], 'fee' => $mum, 'type' => 2, 'name' => 'trade', 'nameid' => $finance_nameid, 'remark' => 'spot_buy_commission:' . $market, 'mum_a' => $finance_mum_user_coin[$rmb], 'mum_b' => $finance_mum_user_coin[$rmb . 'd'], 'mum' => $finance_mum_user_coin[$rmb] + $finance_mum_user_coin[$rmb . 'd'], 'move' => $finance_hash, 'addtime' => time(), 'status' => $finance_status]);
            }
        } elseif ($type == 2) {
            if ($user_coin[$xnb] < $num) {
                $this->error(C('coin')[$xnb]['title'] . ' Insufficient balance for sell!');
            }

            $rs[] = $mo->table('codono_user_coin')->where(['userid' => $uid])->setDec($xnb, $num);
            $rs[] = $mo->table('codono_user_coin')->where(['userid' => $uid])->setInc($xnb . 'd', $num);
            $rs[] = $mo->table('codono_stop')->add(['userid' => $uid, 'compare' => $compare, 'market' => $market, 'price' => $price, 'stop' => $stop, 'num' => $num, 'mum' => $mum, 'fee' => $fee, 'type' => 2, 'addtime' => time(), 'status' => 0]);
        } else {
            $mo->rollback();

            $this->error(L('Transaction type error'));
        }
        if (check_arr($rs)) {
            $mo->commit();

            S('getDepth', null);
            S('getActiveDepth' . $market, null);
            S('getActiveDepth', null);
            S('getDepthNew', null);

            //$this->matchingTrade($market);
            $this->callMatchingTrade($market);

            $this->callStopMatching($market, $type);


            $this->success(L('Trading success!'));
        } else {
            $mo->rollback();

            $this->error(L('transaction failed!'));
        }
    }

    public function stopreject()
    {
        $uid = userid();
        $id = (int)I('id');
        if (!$uid) {
            $this->error(L('PLEASE_LOGIN'));
        }

        if (!check($id, 'd')) {
            $this->error(L('Please select Undo commission!'));
        }
        $where = ['userid' => $uid, 'id' => $id, 'status' => 0];
        $trade = M('Stop')->where($where)->find();


        if (!$trade) {
            $this->error(L('Undo delegate parameter error!'));
        }

        if ($trade['userid'] != $uid) {
            $this->error(L('Parameters illegal!'));
        }

        $this->tshow(D('Trade')->stopreject($id));
    }

    /*
Type=1 Buy
Type=2 Sell

Returns
If type==buy then Price [Higest Buy Price]
If type==sale then Price =[Lowest Sale Price]

*/

    /*
    Type=1 Buy
    Type=2 Sell

    Returns
    If type==buy

    1.Price [Highest Sale Price]
    2.Qty Total num of sale orders
    If type==sale

    1.Price [Lowest Buy Price]
    2.Qty Total num of buy orders
    */


    private function markStopAsExecuted($stopid)
    {
        $data['status'] = 1;
        //$data['endtime']=time();
        return M('Stop')->where(['id' => $stopid])->save($data);
    }

    public function callMatchingTrade($market)
    {

        $command_path = PHP_PATH . " index.php /Home/Trade/matchingTrade/market/$market";
        //   clog(__FUNCTION__,[$command_path]);
        exec($command_path);
    }

    private function callStopMatching($market, $type)
    {
        exec(PHP_PATH . " index.php /Home/Trade/stopcheck/market/$market/type/$type"); //StopLoss execution
    }

    public function star()
    {
        $input = I('request.');
        $action = I('request.action', 'get', 'htmlspecialchars');
        $market = I('request.market', '', 'htmlspecialchars');
        $ret = [];
        $uid = userid();
        if (!$uid) {
            echo json_encode(['status' => 1, 'data' => ['market' => []]]);
            return;
        }




        if ($action != 'find') {

            $market = strtolower($market);
            if (!check($market, 'market')) {
                $this->error('Invalid Coinpair! ' . $market);
            }
            if (C('market')[$market]['name'] != $market || C('market')[$market]['status'] != 1) {
                $this->error('No such pair!');
            }

            $exists = M('UserStar')->where(['userid' => $uid, 'pair' => $market])->find();

            $mo = M('UserStar');
            if ($exists['pair']) {
                $work_status = M('UserStar')->where(['userid' => $uid, 'pair' => $market])->delete();
            } else {
                $work_status = $mo->add(['userid' => $uid, 'pair' => $market]);
            }

            if ($work_status && !$exists) {
                $array = ['status' => 1, 'message' => 'success', 'is_fav' => 1];
            } else {
                $array = ['status' => 1, 'message' => 'success', 'is_fav' => 0];
            }
            echo json_encode($array);
            exit;
        } else {
            //Find star
            $pairs = M('UserStar')->where(['userid' => intval($uid)])->field(['pair' => 'name'])->select();

            $info['market'] = [];
            foreach ($pairs as $pair) {

                if ($pair['name'] == C('market')[$pair['name']]['name']) {
                    $market = C('market')[$pair['name']];
                    $basemarket = explode('_', $pair['name'])[1];
                    $info['market'][] = ['id' => $market['id'], 'basemarket' => $basemarket, 'ticker' => $market['name'], 'fee_buy' => format_num($market['fee_buy']), 'fee_sell' => format_num($market['fee_sell']), 'name' => $market['title'], 'icon' => SITE_URL . 'Upload/coin/' . $market['xnbimg'], 'new_price' => $market['new_price'], 'buy_price' => format_num($market['buy_price']), 'sell_price' => format_num($market['sell_price']), 'min_price' => format_num($market['min_price']), 'max_price' => format_num($market['max_price']), 'change' => round($market['change'], 2), 'volume' => $market['volume']];
                }
            }

            $arr['status'] = 1;
            $arr['data'] = $info;

            echo json_encode($arr);
        }
    }
}
