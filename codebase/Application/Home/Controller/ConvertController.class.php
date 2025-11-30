<?php

namespace Home\Controller;

class ConvertController extends HomeController
{
    public const base_coin = 'usdt'; //  enter crypto for easy buy /sell
    public function __construct()
    {
        parent::__construct();
        $market = I('get.market', '', 'htmlspecialchars');
        if ($market != '') {
            $this->base_coin = $market;
        } else {
            $this->base_coin = 'usdt';
        }
    }
    public function index()
    {
        $uid = userid() ?: 0;
        $codono_getCoreConfig = codono_getCoreConfig();

        if (!$uid) {
            $CoinList = $this->calcEstimatedAssetvalue($uid);
            $balance = 0.00;
        } else {
            $user_balance = M('UserCoin')->where(['userid' => $uid])->find();
            $CoinList =  $this->calcEstimatedAssetvalue($uid);
            $balance = $user_balance[strtolower($this->base_coin)];
        }

        $CoinList = $this->sortByConversion($CoinList);
        $reversedCoinList = array_reverse($CoinList, true);
        $loggedIn = $uid ? 1 : 0;
        $this->assign('balance', $balance);
        $this->assign('loggedIn', $loggedIn);
        $this->assign('base_coin', strtoupper($this->base_coin));
        $this->assign('CoinList', $CoinList);
        $this->assign('reversedCoinList', $reversedCoinList);

        $this->display();
    }
    /**
     * Sorts an array of currencies by their conversion value, prioritizing non-zero conversions.
     * Currencies with zero conversion are sorted by balance.
     * 
     * @param array $currencies The array of currencies to be sorted.
     * @return array The sorted array.
     */
    protected function sortByConversion($currencies)
    {
        uasort($currencies, function ($a, $b) {
            // Convert conversion values to float for comparison
            $conversionA = floatval($a['conversion']);
            $conversionB = floatval($b['conversion']);

            // Prioritize non-zero conversions
            if ($conversionA > 0 && $conversionB > 0) {
                return $conversionB <=> $conversionA;
            } else if ($conversionA > 0) {
                return -1;  // $a has a non-zero conversion, place it first
            } else if ($conversionB > 0) {
                return 1;   // $b has a non-zero conversion, place it first
            }

            // If both conversions are 0, sort by balance
            return $b['balance'] <=> $a['balance'];
        });

        return $currencies;
    }

    // Function to fetch the best order price for a given type and market
    protected function getBestOrderPrice($market, $type)
    {
        $typeCondition = $type == 'buy' ? '1' : '2'; // 1=Buy, 2=Sell
        $order = M('Trade')
            ->where(array(
                'market' => $market,
                'type'   => $typeCondition,
                'status' => 0   // Only open orders
            ))
            ->order('price DESC') // Ascending for buys, descending for sells
            ->find();


        return $order ? $order['price'] : false;
    }

    // Function to find all possible conversion routes through intermediary markets
    protected function findConversionRoutes($baseCurrency, $quoteCurrency, $availMarkets)
    {
        $routes = [];
        foreach ($availMarkets as $market) {
            list($part1, $part2) = explode('_', $market);
            if ($part1 === $baseCurrency || $part2 === $baseCurrency || $part1 === $quoteCurrency || $part2 === $quoteCurrency) {
                $routes[] = $market;
            }
        }
        return $routes;
    }

    // Function to attempt a conversion through possible routes
    private function autoConvertMarket($conversionMarket, $availMarkets, $amount)
    {
        list($baseCurrency, $quoteCurrency) = explode('_', $conversionMarket);

        $routes = $this->findConversionRoutes($baseCurrency, $quoteCurrency, $availMarkets);

        if (empty($routes)) {
            return [
                'error' => 'No conversion path available',
                'price' => 0
            ];
        }

        foreach ($routes as $route) {
            if (strpos($route, $baseCurrency) !== false && strpos($route, $quoteCurrency) !== false) {
                // Direct conversion route found
                $conversion = $this->convertMarket($route, $route, $amount);
                if ($conversion) {
                    return $conversion;
                }
            }
        }

        // If no direct route, attempt indirect conversion (if applicable)
        // This part can be expanded based on your specific market structure and needs
        return [
            'error' => 'No such market' . $conversionMarket,
            'price' => 0
        ];
    }

    // Same convertMarket function from previous example
    protected function convertMarket($fromMarket, $toMarket, $amount)
    {
        $fromBestPrice = $this->getBestOrderPrice($fromMarket, 'sell');
        $toBestPrice = $this->getBestOrderPrice($toMarket, 'buy');

        if (!$fromBestPrice || !$toBestPrice) {
            return false; // No available orders to fulfill the request
        }

        $intermediateAmount = $amount * $fromBestPrice; // Convert base to intermediary
        $finalAmount = $intermediateAmount / $toBestPrice; // Convert intermediary to quote

        return array(
            'fromAmount' => $amount,
            'toAmount' => $finalAmount,
            'intermediateAmount' => $intermediateAmount,
            'fromMarketRate' => $fromBestPrice,
            'toMarketRate' => $toBestPrice
        );
    }

    public function doTrade($from_coin, $to_coin, $amount)
    {
        $uid = userid();
        if (!$uid) {
            $this->error(L('Please login first!'));
        }
        $info = $this->marketFinder($from_coin, $to_coin);
        if (!$info || empty($info)) {
            $this->error('Conversion between:' . $from_coin . ' and ' . $to_coin . ' is not available');
        }
        foreach ($info as $market) {
            list($from, $to) = explode('_', $market['market']);
            $type = $market['type'];
            $resp = json_decode($this->V2doTrade($from, $to, $amount, $type), true);
            $amount= $resp['data']['total'];
        }
        
        if ($resp['status'] == 1) {
            $this->success( $resp['info']);
        } else {
            $this->error($resp['info']);
        }
    }
    private function V2doTrade($from_coin, $to_coin, $amount, $type)
    {

        $uid = userid();
        $buy_qty = 0;

        if (C('coin')[$to_coin]['name'] != strtolower($to_coin)) {
            return $this->jsonOut(['status' => 0, 'info' => C('coin')[$to_coin]['name'] . 'Please cross check coins selected' . $to_coin]);
        }

        $market = strtolower($from_coin . '_' . $to_coin);

        $buy_fees = C('market')[$market]['fee_buy'];
        $user_balance = M('UserCoin')->where(['userid' => $uid])->find();
        if ($type == 2) {
            if (!$user_balance[strtolower($from_coin)] || $user_balance[strtolower($from_coin)] < $amount) {
                return $this->jsonOut(['status' => 0, 'info' => L($from_coin . $amount . 'Insufficient funds available') . $user_balance[strtolower($from_coin)]]);
            }
        }
        if ($type == 1) {
            if (!$user_balance[strtolower($to_coin)] || $user_balance[strtolower($to_coin)] < $amount) {
                return $this->jsonOut(['status' => 0, 'info' => L('Insufficient funds available')]);
            }
        }


        $isValidCoin = $this->isValidCoin($from_coin);
        if ($type != 1 && $type != 2) {
            return $this->jsonOut(['status' => 0, 'info' => L('Invalid Type')]);
        }
        if (!$from_coin || !$isValidCoin) {
            return $this->jsonOut(['status' => 0, 'info' => L('Invalid Coin') . ' ' . $from_coin]);
        }
        if (!check($amount, 'decimal')) {
            return $this->jsonOut(['status' => 0, 'info' => L('Incorrect amount') . ' ' . $amount]);
        }

        $orderbook = json_decode($this->orderbook($from_coin, $to_coin), true);

        if ($type == 1) {
            $count = count($orderbook['depth']['sell']);

            if ($count < 1) {
                return $this->jsonOut(['status' => 0, 'info' => L('There are currently not sufficient orders')]);
            }
            $buyOrders = array_reverse($orderbook['depth']['sell']);
            $total = $flag = $buy_amount = $i = 0;

            foreach ($buyOrders as $bo) {
                $buy_amount = bcadd($buy_amount, $bo[1], 8);
                $total = bcadd($total, bcmul($bo[0], $bo[1], 8), 8);
                if ($total > $amount) {
                    $flag = 1;
                    $buy_qty = bcmul(bcdiv(1, $bo[0], 8), $amount, 8);
                    $price = $bo[0];
                    break;
                }
                $i++;
            }

            if (!$flag) {
                return $this->jsonOut(['status' => 0, 'info' => L("Maximum available to buy is " . $total)]);
            }
            $percent = bcsub(100, $buy_fees, 8);
            $amount = bcdiv(bcmul($buy_qty, $percent, 8), 100, 8);

            //$this->error($amount."=amount , price".$price);
        }
        if ($type == 2) {
            $count = count($orderbook['depth']['sell']);
            if ($count < 1) {
                return $this->jsonOut(['status' => 0, 'info' => L("There are currently not sufficient orders")]);
            }
            if ($orderbook['sellvol'] < $amount) {
                return $this->jsonOut(['status' => 0, 'info' => "Maximum available to sell is " . $orderbook['sellvol']]);
            }

            $price = end($orderbook['depth']['buy'])[0];
        }
        //action
        $json_input = base64_encode(json_encode(['price' => $price, 'amount' => $amount, 'type' => $type, 'tradeType' => 'limit', 'stop' => 0, 'paypassword' => NULL, 'market' => $market, 'userid' => $uid]));

        $command_path = PHP_PATH . " index.php /Home/Convert/callTrade/code/$json_input";
        exec($command_path);


        if ($type == 2) {
            $total = bcmul($amount, $price, 8);
        } elseif ($type == 1) {
            $total = bcdiv($amount, $price, 8);
        } else {
            $total = 0;
        }


        return $this->jsonOut(['status' => 1, 'info' => "Order Placed", 'data' => ['total' => $total, 'type' => $type]]);
    }

    public function callTrade($code)
    {
        // Extract the command-line arguments

        if (base64_encode(base64_decode($code, true)) === $code) {
            //    echo '$data is valid';
            $decoded = json_decode(base64_decode($code, true), true);
            $price = $decoded['price'];
            $amount = $decoded['amount'];
            $type = $decoded['type'];
            $market = $decoded['market'];


            $pass = M('User')->where(['id' => $decoded['userid']])->getField('password');
            $override = ['userid' => $decoded['userid'], 'code' => cryptString($pass)];
          
            A('Trade')->upTrade($price, $amount, $type, 'limit', 0 , NULL, $market,$override );
            
        } else {
            //  echo '$data is NOT valid';
        }

        //
    }
    public function jsonOut($data)
    {
        header('Content-Type:application/json; charset=utf-8');
        return json_encode($data, 0);
    }
    protected function marketFinder($coinFrom, $coinTo)
    {
        $markets = array_keys(C('Market'));
        $directMarket = strtolower($coinFrom) . '_' . strtolower($coinTo);
        $inverseMarket = strtolower($coinTo) . '_' . strtolower($coinFrom);
        $possibleMarkets = [];

        // Define type for direct and inverse transactions
        $directType = 2; // Selling coinFrom for coinTo
        $inverseType = 1; // Buying coinFrom with coinTo

        // Check for direct or inverse direct markets
        if (in_array($directMarket, $markets)) {
            $possibleMarkets[] = ['market' => $directMarket, 'type' => $directType];
        }
        if (in_array($inverseMarket, $markets)) {
            $possibleMarkets[] = ['market' => $inverseMarket, 'type' => $inverseType];
        }

        // If direct market is found, return it
        if (!empty($possibleMarkets)) {
            return $possibleMarkets;
        }

        // Search for indirect markets via a common currency
        $fromMarkets = [];
        $toMarkets = [];
        foreach ($markets as $market) {
            list($part1, $part2) = explode('_', $market);

            if ($part1 == strtolower($coinFrom) || $part2 == strtolower($coinFrom)) {
                $fromMarkets[] = $market;
            }
            if ($part1 == strtolower($coinTo) || $part2 == strtolower($coinTo)) {
                $toMarkets[] = $market;
            }
        }

        // Check for common intermediate currency
        foreach ($fromMarkets as $fromMarket) {
            list($fromPart1, $fromPart2) = explode('_', $fromMarket);
            $intermediate = ($fromPart1 == strtolower($coinFrom)) ? $fromPart2 : $fromPart1;

            foreach ($toMarkets as $toMarket) {
                list($toPart1, $toPart2) = explode('_', $toMarket);
                if ($intermediate == $toPart1 || $intermediate == $toPart2) {
                    $typeFrom = ($fromPart1 == strtolower($coinFrom)) ? $directType : $inverseType;
                    $typeTo = ($toPart2 == strtolower($coinTo)) ? $directType : $inverseType;
                    $possibleMarkets[] = ['market' => $fromMarket, 'type' => $typeFrom];
                    $possibleMarkets[] = ['market' => $toMarket, 'type' => $typeTo];
                }
            }
        }

        // Return unique markets with type
        return array_unique($possibleMarkets, SORT_REGULAR);
    }

    private function orderbook($from_coin, $to_coin)
    {
        $market = strtolower($from_coin . '_' . $to_coin);
        return json_encode(A('Ajax')->getActiveOrders($market, 1, false));
    }
    private function isValidCoin($coin): bool
    {
        $coins = C('coin_safe');

        if (array_key_exists(strtolower($coin), $coins)) {
            return true;
        } else {
            return false;
        }
    }



    public function _empty()
    {
        send_http_status(404);
        $this->error();
        echo L('Module does not exist!');
        die();
    }

    private function calcEstimatedAssetvalue($uid = 0)
    {

        $UserCoin = $print_coins = $market_list = [];
        $CoinList = C('coin_safe');
        if ($uid > 0) {
            $UserCoin = M('UserCoin')->where(['userid' => $uid])->find();
        }

        $Markets = C('market');



        $conversion_coin = SYSTEMCURRENCY;

        foreach ($Markets as $k => $v) {
            $market_list[$v['name']] = $v;
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

        foreach ($CoinList as $k => $v) { {
                if ($v['symbol']) {
                    continue;
                }
                $x_market = strtolower($v['name'] . '_' . $conversion_coin);

                if (isset($the_cms[$v['name']])) {
                    $jia = $before = $the_cms[$v['name']];
                } else {
                    if (isset(c('market')[$x_market])) {
                        $jia = c('market')[$x_market]['new_price'];
                    } else {
                        $jia = 0;
                    }
                }
                $jia = $after = NumToStr($jia); //$jia = $after = bcdiv((double)$jia, $multiplier, 8);

                $print_coins[$v['name']] = ['name' => $v['name'], 'img' => $v['img'], 'title' => strtoupper($v['name']) . ' [ ' . ucfirst($v['title']) . ' ]', 'balance' => format_num($UserCoin[$v['name']], 8) * 1, 'price' => $jia, 'conversion' => bcmul(bcadd($UserCoin[$v['name']], $UserCoin[$v['name'] . 'd'], 8), $jia, 4), 'type' => $v['type']];

                $usd['zj'] = bcadd($usd['zj'], bcmul(bcadd($UserCoin[$v['name']], $UserCoin[$v['name'] . 'd'], 8), $jia, 8), 2) * 1;
            }
        }
        return  $print_coins;
    }
}
