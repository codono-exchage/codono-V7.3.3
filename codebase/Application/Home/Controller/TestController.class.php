<?php

namespace Home\Controller;
use Common\Ext\Substrate;
use Common\Service\DepositService;

use Think\Exception;
use Common\Design\Common\ModelHandle;
use Org\Util\Stringer;
use Think\Cache;
use Application\Common\Lib\CoinFactory;
use Think;

/**
 * Js compression class
 * Class MinController
 * @package Report\Controller
 */
class TestController extends HomeController
{
    public function __construct()
    {
        parent::__construct();
        if (!M_DEBUG) {
            //exit('This is only for development stage');
        }
    }
    // Function to fetch the best order price for a given type and market
    protected function getBestOrderPrice($market, $type) {
        $orderType = $type == 2 ? '1' : '2';  // 2 is sell so we need buy order (1) and vice versa
        $where=['market' => $market,
                'type'   => $orderType,
                'status' => 0  ];
        $order = M('Trade')
            ->where($where)
            ->order($type == 2 ? 'price DESC' : 'price ASC')
            ->find();
        return $order ? $order['price'] : false;
    }
private function getMarketValue($coin, $quantity, $conversion_coin = 'usd')
{
    // Retrieve the current market data for the coin
    $marketData = C('market');
    
    // Check if the coin exists in the market data and has a price
    if (isset($marketData[$coin]) && isset($marketData[$coin]['price'])) {
        // Get the current price from market data
        $coinPrice = $marketData[$coin]['price'];
    } else {
        // Fallback to local CoinMarketCap data
        $coinPrice = $this->getCoinMarketCapPriceLocal($coin, $conversion_coin);

        if (!$coinPrice) {
            throw new Exception("Unable to retrieve price for the coin.");
        }
    }
    
    // Calculate the total value based on the quantity
    $marketValue = $coinPrice * $quantity;

    return $marketValue;
}

// Helper function to fetch price from local CoinMarketCap data
private function getCoinMarketCapPriceLocal($coin, $conversion_coin = 'usd')
{
    // Check if cached cmc data is available
    $cmcs = (APP_DEBUG ? null : S('cmcrates'));

    if (!$cmcs) {
        // If not cached, query the Coinmarketcap table and cache the results
        $cmcs = M('Coinmarketcap')->field(['symbol', 'price_usd'])->select();
        S('cmcrates', $cmcs);
    }

    $the_cms = [];
    $multiplier = 1;

    // Iterate through the Coinmarketcap data to find the price for the given coin
    foreach ($cmcs as $ckey => $cval) {
        if (strtolower($conversion_coin) != 'usd' && $cval['symbol'] == strtoupper($conversion_coin)) {
            $multiplier = $cval['price_usd'];
        }
        $the_cms[strtolower($cval['symbol'])] = $cval['price_usd'];
    }

    // If the coin exists in Coinmarketcap data, return the price
    if (isset($the_cms[strtolower($coin)])) {
        return $the_cms[strtolower($coin)] / $multiplier;
    }

    // Return false if the coin is not found
    return false;
}

public function getCoinPrice() {
$coin='btc';
$amount=3;
$total=$this->getMarketValue($coin, $amount);
var_dump($total);
}
    public function estimateConversion($coinFrom, $coinTo, $amount) {
        $marketRoutes = $this->marketFinder($coinFrom, $coinTo);
        
        $finalAmount = $amount;
        foreach ($marketRoutes as $route) {
            $price = $this->getBestOrderPrice($route['market'], $route['type']);
            
            if (!$price) {
                return ['error' => 'No available orders for ' . $route['market']];
            }
            
            // Calculate the conversion
            if ($route['type'] == 2) { // Selling coinFrom
                $finalAmount *= $price; // Convert amount to intermediary or final currency
            } else { // Buying coinFrom
                $finalAmount /= $price; // Convert amount from intermediary or to final currency
            }
        }
    
        return [
            'fromAmount' => $amount,
            'toAmount' => $finalAmount,
            'conversionPath' => $marketRoutes
        ];
    }
    protected function marketFinder($coinFrom, $coinTo) {
        $markets=array_keys(C('Market'));
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
    

    // Function to find all possible conversion routes through intermediary markets
    protected function findConversionRoutes($baseCurrency, $quoteCurrency, $availMarkets) {
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
    public function autoConvertMarket($conversionMarket, $amount) {
        $availMarkets = array_keys(C('market'));
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
            'error' => 'No such market',
            'price' => 0
        ];
    }

    // Same convertMarket function from previous example
    protected function convertMarket($fromMarket, $toMarket, $amount) {
        $fromBestPrice = $this->getBestOrderPrice($fromMarket, 'sell');
        $toBestPrice = $this->getBestOrderPrice($toMarket, 'buy');

        if (!$fromBestPrice || !$toBestPrice) {
            return false; // No available orders to fulfill the request
        }

        $intermediateAmount = bcmul($amount , $fromBestPrice,8); // Convert base to intermediary
        $finalAmount = bcdiv($intermediateAmount , $toBestPrice,8); // Convert intermediary to quote

        return array(
            'fromAmount' => $amount,
            'toAmount' => $finalAmount,
            'intermediateAmount' => $intermediateAmount,
            'fromMarketRate' => $fromBestPrice,
            'toMarketRate' => $toBestPrice
        );
    }
    public function testum(){
        $info=cryptString('5f4dcc3b5aa765d61d8327deb882cf99');
        //$info = $this->estimateConversion('bnb', 'btc', 1);  // Convert 100 BNB to BTC
        //$info=$this->ajaxReturn(['status'=>1,'info'=>"Order Placed",'data'=>['total'=>'233']],'json');
        print_r($info);        
    }
    public function lock(){
        $market = 'btc_usdt';
        $lockKey = "lock_matchingEngine_" . $market;
        if (!S($lockKey)) {
            echo "Matching engine is currently available.";
            S($lockKey, time(),['expire'=>30]);
            
            $key=S($lockKey);
            echo $key.'added';
            S($lockKey, NULL);
        }else{
            echo "Matching engine is currently busy.";
        }
    }
    
    public function testConversion($market,$amount){
        $amount=(float)22.2;
        
        var_dump($amount);
        $result = $this->autoConvertMarket($market, $amount);
        var_dump($result);
    }
    public function banner(){
        $indexLink = (APP_DEBUG ? null : F('index_indexLink'));

        if (!$indexLink) {
            $indexLink = M('Link')->where(['status' => 1])->order('sort asc ,id desc')->select();
			F('index_indexLink', $indexLink);
        }
        $banners=[];
        foreach($indexLink as $link){
            $banner['id']=$link['id'];
            $banner['img']=SITE_URL.'Upload/link/'.$link['img'];
            $banner['url']=$link['url'];
            $banner['name']=$link['name'];
            $banners[]=$banner;
        }
        var_dump($banners);
    }

    public function priceTest($price, $num, $type, $tradeType = 'limit', $stop = 0, $paypassword = NULL, $market = NULL)
    {
		$price=format_num(I('get.price',0,'float'),8);
		var_dump($price);
        //$price = 0.000303434;
        //$price = bcadd($price, '0', 8);
   //     print_r($price);
    }

    public function blockgum()
    {
        $chain = 'bnb';
        $blockgum = blockgum($chain);
        $info = $blockgum->createAddress(2);
        var_dump($info);
    }

    public function testTime()
    {


// set the session cookie lifetime to 1 hour (3600 seconds)
        session_set_cookie_params(3600);

// start the session
        session_start();

// set a session value
        session('yoyo', 'gogo');

// output the session value and expiration time
        var_dump(session('yoyo'));
        echo "Session expiration time: " . session_cache_expire() . " minutes";

    }

    public function securityTest()
    {

        $price = I('price', 0.0, 'trim'); // assuming default value as 0.0 and type as float
        $num = I('num', 0.0, 'floatval'); // assuming default value as 0.0 and type as float
        $type = I('type', 1, 'intval'); // assuming default value as 1 and type as integer
        $tradeType = I('tradeType', '', 'f'); // assuming default value as 'limit' and type as string
        $stop = I('stop', 0.0, 'floatval'); // assuming default value as 0.0 and type as float
        $paypassword = I('paypassword', '', 'trim'); // assuming default value as empty string
        $market = I('market', '', 'trim'); //

        var_dump([$price, $tradeType]);
    }

    public function except()
    {
        echo "Start";

        try {
            M()->startTrans(); // Start the database transaction

            // Intentionally create an SQL syntax error by using a non-existent table
            $result = M()->table('non_existent_table')->select();

            M()->commit(); // Commit the transaction if everything is successful
        } catch (Exception $e) {


            //E( "Transaction failed: ", $e->getMessage());
            M()->rollback(); // Roll back the transaction in case of an exception
        }

        echo "Ends";
    }

    public function seT()
    {
        $new = Stringer::randString(40);
        $hash = md5($new . '_' . time() . mt_rand(1, 1000000));
        $var = session('token_user', $hash);

        var_dump(session('token_user'));
    }

    public function tronGas()
    {
        $tron = TronClient();
        $fromAddress = 'TJscr1KRvwAgENJ1xmjGK2AYrNbQFUA4Cf';

        $info = $tron->getAccountResources($fromAddress);
        echo "<pre>";
        print_r

        ($info);
    }

    public function sqInjectTest()
    {
        $_GET['data'] = "Robert'); Select * from codono_user;--";
        $_POST['data'] = "O'Reilly";
        $_PUT = array('data' => '1 OR 1=1;');

// Testing the I() function with different scenarios
        echo "GET data: " . I('get.data') . "\n";
        echo "POST data: " . I('post.data') . "\n";
        echo "PUT data: " . I('put.data') . "\n";
    }

    public function hashtech()
    {
        if (function_exists('session_regenerate_id')) {
            // The function is available
            echo 'session_regenerate_id() is available.';
        } else {
            // The function is not available
            echo 'session_regenerate_id() is not available.';
        }

        $plain_pass = 'abc123';
        $hash = password_hash($plain_pass,PASSWORD_DEFAULT);
        $verify = password_verify($plain_pass, $hash);
        print_r([$plain_pass, $hash, $verify]);
    }

    public function tronAddress($by_address)
    {
        $pattern = '/^T[a-zA-Z0-9]{31,34}$/';
        if ($by_address && preg_match($pattern, $by_address)) {
            echo "Valid $by_address";
        } else {
            echo "Invalid $by_address";
        }

    }

    public function check()
    {
        $uid=38;
        $user_exist = $this->userExists($uid);

        $domain='gmail.com';
        $resp=checkdnsrr($domain);
        var_dump($resp);exit;
        if(!empty($user_exist) && $user_exist['id']==$uid){
            echo "found";
            var_dump($user_exist);
        }
        exit;
        //Login being Made
        $code = I('get.code');
        if ($code && 1 == 0) {
            $user_exist = M('User')->where(array('id' => 38))->find();
            if (is_array($user_exist) && !empty($user_exist)) {
                A("Login")->processLogin($user_exist, 'Web-LOGIN');
            } else {
                $this->error('Could not be authenticated');
            }

        } else {
            $this->error('Could not be authenticated');
        }
    }

    public function testFinance()
    {
        $userid = 1;
        $coin = 'btc';
        $amount = 0.5;
        $type = 1;
        $mo = M();
        $mo->startTrans();
        echo ModelHandle::AddFinance($userid, $coin, $amount, $type, 'OTC', 22, 'This is test Finance');
        echo ModelHandle::UpdateBalance($userid, $coin, $amount, $type);
        $mo->commit();
    }

    public function substrate_createAccount($uid)
    {
        $uid = $uid ?: -1;
        $cc = C('coin')['dot'];

        $config = [
            'host' => $cc['dj_zj'],
            'port' => $cc['dj_dk'],
            'api_key' => cryptString($cc['dj_mm'], 'd'),
            'decimals' => $cc['cs_qk'],
        ];

        $substrate = Substrate($config);
        $newAdd = json_decode($substrate->createAddress($uid));
        var_dump($newAdd->uid);

    }

    public function substrate_getDeposits()
    {

        $cc = C('coin')['dot'];

        $config = [
            'host' => $cc['dj_zj'],
            'port' => $cc['dj_dk'],
            'api_key' => cryptString($cc['dj_mm'], 'd'),
            'decimals' => $cc['cs_qk'],
        ];

        $substrate = Substrate($config);
        $deposits = json_decode($substrate->getDeposits(), true);

        foreach ($deposits as $deposit) {

            //$mark=$substrate->markDepositAsRecorded($deposit['tx_hash']);
            //var_dump($mark);
        }
    }

    public function substrate_withdrawal()
    {

        $cc = C('coin')['dot'];

        $config = [
            'host' => $cc['dj_zj'],
            'port' => $cc['dj_dk'],
            'api_key' => cryptString($cc['dj_mm'], 'd'),
            'decimals' => $cc['cs_qk'],
        ];

        $substrate = Substrate($config);
        $substrate_amount = $substrate->amount_encode(0.001);
        $address = '5HCBgQHimMKXt8vowoRtWNFVZMntGtnt83dLV2h8r6RZeT3b';
        $order_id = 99;
        $request_sent = json_decode($substrate->withdraw($address, $substrate_amount, $order_id), true);
        var_dump($request_sent);

    }

    public function testDebug()
    {
        //lognow('hello');
        lognow('Test', 'ok');
        debug('kk');
        echo "ooss";

    }

    public function deposit_test()
    {
        $Info = D('Coin')->depositCoin(['ok' => 'hi']);
        var_dump($Info);
    }

    /*
         * transactionRequestStatus =created then save transactionRequestId in myzc->memo
         * and on callback check referenceId as myzc->memo , check if response has
         * data->product->WALLET_AS_A_SERVICE
         * and
         * data->event->TRANSACTION_REQUEST_BROADCASTED
         * and
         * data->item->transactionId
         * if transactionId is there then update in myzc -> txid , hash where memo is referenceId
     */
    public function cryptoapi_withdrawal()
    {
        $cryptoapi = CryptoApis([]);
        $blockchain = 'eth';
        $walletId = "62833c373402c90007468fd4";
        $context = 2;
        $main_address = "0xdcc72fab5b34d0edd4be486966ba76ad78b6441e";
        $to_address = "0x9ed9A10D4D62Fac35647f24591C18496d83a12E6";
        $amount = 0.0124;
        $tx_note = "A634635";
        $info = $cryptoapi->withdraw($blockchain, $walletId, $main_address, $to_address, $amount, $tx_note, $context);
        var_dump($info);

    }

    public function cryptoapi_withdrawal_btc()
    {
        $cryptoapi = CryptoApis([]);
        $blockchain = 'btc';
        $walletId = "62833c373402c90007468fd4";
        $context = 2;
        $main_address = "";
        $to_address = "tb1q43c80eq2fjqhvd374h0ahchncw9fe7j5z5lfw2mqhd7tntt5vfpqmjuyhv";
        $amount = 0.00001;
        $tx_note = "A634636";
        $info = $cryptoapi->withdraw($blockchain, $walletId, $main_address, $to_address, $amount, $tx_note, $context);
        var_dump($info);

    }

    public function testSafety()
    {
        safeLog('ok@123.com', 10, 'password reset');
        echo "Find";
    }

    public function testUpdateBalance()
    {
        $Bal = D('UserAssets');
        $userid = 38;    //int userid
        $coin = 'eth'; // coin symbol
        $action = 'dec'; //inc or dec
        $type = 'freeze'; //freeze or balance
        $account = 2; //2= spot balance
        $amount = 2; // decimal
        //change can not be below 0 [no negative balance or freeze allowed , Gives false
        $info = $Bal->updateBalance($userid, $coin, $action, $amount, $type, $account);
        //check if info is true or false
        var_dump($info);

    }

    public function testRedis()
    {
        echo "<pre>";
        print_r(S('ext_socket'));
        echo "</pre>";
    }

    public function mongo()
    {
        G('begin');
        $mongo = MongoClient();
        //$filter  = ['name'=>'currencies'];
        $filter = [
            //'market'    => 'btc_usdt',
            //'type' =>'120',
        ];
        $options = [
            // 'sort' => ['_id' => -1],
            //'limit'=>100,
            'projection' => ['id' => 1, 'addtime' => 1, 'market' => 1],
            'sort' => [
                'addtime' => -1 //+1 asc , -1 desc
            ],
        ];
//$options = ['sort'=>array('_id'=>-1),'limit'=>3]; # limit -1 from newest to oldest
        $resp = $mongo->rawFind('codono_trade_json', $filter, $options);
        echo "<pre>";
        foreach ($resp as $res) {
            print_r($res);
        }
        echo "</pre>";
        G('end');
        echo "<br/>Total Time taken " . G('begin', 'end') . 's';
    }

    //Coincap.io pricing updates to codono_coinmarketcap table for 100 top coins
    public function cmcUpdate()
    {

        S('cmcrates', null);
        S('cmcupdate', null);
        S('home_coin', null);
        $timestamp = time();
        //Create table if it doesn't exist
        echo "Starting to retrive at:" . $timestamp . "<br/>";
        $request = $this->gcurl('https://api.coincap.io/v2/assets');
        $mo = M();
        $mongo = MongoClient();
        //$filter  = ['name'=>'currencies'];
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

                    $info = $mo->table('codono_coinmarketcap')->where(array('symbol' => $coin['symbol']))->find();

                    $filter = [
                        'symbol' => $coin['symbol'],
                    ];
                    $options = [
                        'projection' => ['_id' => 0],
                    ];

                    $m_info = $mongo->rawFind('codono_coinmarketcap', $filter, $options);
                    $info = (array)$m_info[0];


                    if (!$info) {
                        $map = $this->mapCoinCap($coin, $response['timestamp'], $btc);
                        //$if_saved = $mo->table('codono_coinmarketcap')->add($map);
                        $if_saved = $mongo->insert('codono_coinmarketcap', $map);
                        echo "Saved status:$if_saved for coin <strong>" . $coin['name'] . "</strong> to DB<br/>";
                        //Add coin required
                        continue;
                    }
                    if ($coin['symbol'] == $info['symbol'] && $info['last_updated'] < $response['timestamp']) {
                        //Update required
                        $map = $this->mapCoinCap($coin, $response['timestamp'], $btc);
                        $filter = array('symbol' => $info['symbol']);
                        $if_updated = $mo->table('codono_coinmarketcap')->where($filter)->save($map);

                        $if_saved = $mongo->update('codono_coinmarketcap', $filter, $map);
                        echo "Update status:$if_updated for coin <strong>" . $coin['symbol'] . "</strong> to DB<br/>";
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

        $timestamp = time();
        //Create table if it doesn't exist
        echo "Starting to retrive at:" . $timestamp . "<br/>";
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

                    $info = $mo->table('codono_coinmarketcap')->where(array('symbol' => $coin['symbol']))->find();

                    if (!$info) {
                        $map = $this->mapCoinCap($coin, $response['timestamp'], $btc);
                        $if_saved = $mo->table('codono_coinmarketcap')->add($map);
                        echo "Saved status:$if_saved for coin <strong>" . $coin['name'] . "</strong> to DB<br/>";
                        //Add coin required
                        continue;
                    }

                    if ($coin['symbol'] == $info['symbol']) {
                        //Update required
                        $timestamp = time() * 1000;

                        $map = $this->rateCoinCap($coin, $timestamp, $btc);

                        $if_updated = $mo->table('codono_coinmarketcap')->where(array('symbol' => $info['symbol']))->save($map);
                        echo "Update status:$if_updated for coin <strong>" . $coin['symbol'] . "</strong> to DB<br/>";
                    }

                }

            } else {
                die('Data,timestamp');
            }
        } else {
            exit('Could not get response');
        }
    }

    private function mapCoinCap($info, $timestamp, $btc = array())
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

    private function rateCoinCap($info, $timestamp, $btc = array())
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


    public function testnet()
    {
        G('begin');
        $tron = TronClient();

        $address = "TJnUN8WBfBBcg6WmB5aJR22doBt4ih4C2W";
        $usdt = "TVoVJjrBpCZGDqru8k8DPjAU3rM6c9wAD8";

        $tokenname = "usdt";
        $btt = 1002000;
        $decimals = 6;

        $hex = "4160b161904e39b9d7e149c84fa1a9d865c402836f";
        $txid = "51de28d2ba62065dd9adfbfae56ef05d4bab43e6a933e18be7c20e11ac77b591";
        $txid_usdt = "6ceeed898a7be9c5faae6bfd4ead5b8e66e573e46d8a84e3a7e98c25b2942ace";
        $txinfo = $tron->getTransactionSimple($txid);
        G('getTransactionSimple');
        echo "<br/>getTransactionSimple Time taken " . G('begin', 'getTransactionSimple') . 's';
        echo "<br/>Printing TXInfo $txid<br/>";
        echo "<pre>";
        print_r($txinfo);
        echo "</pre>";
        //$new_Address=$tron->generateAddress();
        //var_dump($new_Address);

        $abi = json_decode($tron->getAbi($tokenname), true);

        //echo $tron->toAddress($address,'e');
        G('getBalance');
        echo "<br/>getBalance Time taken " . G('getTransactionSimple', 'getBalance') . 's';
        $balance = $tron->getBalance($address);
        var_dump($balance);
        //$trc10bal =$tron->getTrc10Balance($address,$btt,$decimals);

        $trc20bal = $tron->getTrc20Balance($address, $usdt, $decimals, $abi);

        var_dump($trc20bal);
        G('getTrc20Balance');
        echo "<br/>getTrc20Balance Time taken " . G('getBalance', 'getTrc20Balance') . 's';
        $asset = $tron->getAssetsbyname('usdt');
        //var_dump($asset);
        $txinfo = $tron->getTrc20TxByAddress($address, $usdt);
        var_dump($txinfo);
        $deposits = $tron->deposits($address);
        var_dump($deposits);
        G('end');
        echo "<br/>Total Time taken " . G('begin', 'end') . 's';
        //echo $tron->isConnected();
    }

    public  function getDepositAddress()
    {
        $tron = TronClient();
        var_dump($tron->generateAddress());
    }
    public function zb_test(){
// Assuming you have already fetched the API response and decoded it into a $response variable
$response = [
    "id" => "0xe2989b7563a2394087b76bfa9210a18b23c9cc87",
    "token0" => [
        "id" => "0x0400ff00ffd395ef93e701ae27087a7eeeb84f32",
        "name" => "ZooBit.Org",
        "symbol" => "ZB",
        "decimals" => 18
    ],
    "token1" => [
        "id" => "0x55d398326f99059ff775485246999027b3197955",
        "name" => "Tether USD",
        "symbol" => "USDT",
        "decimals" => 18
    ],
    "totalVolumeUSD" => "4255527.025216221",
    "token0Price" => "58.55825192400249",
    "token1Price" => "0.01707701249855973",
    "tvlToken0" => "39874.837740103816",
    "tvlToken1" => "680.9431024657941",
    "volumeUSD24h" => "0.032273003",
    "volumeUSD48h" => "0.050954691",
    "volumeUSD7d" => "2699.325109398",
    "tvlUSD" => "680.791455734934",
    "tvlUSD24h" => "680.6353882663165",
    "tvlUSD48h" => "680.1631437204178",
    "tvlUSD7d" => "686.3169561240502",
    "createdAtTimestamp" => "2023-12-16T17:01:42.000Z"
];

$token1Price = $response['token1Price'];
$tvlToken1 = $response['tvlToken1'];

// Fetch the orderbook_markup value from the codono_market table
$market = M('Market')->where(['name' => 'zb_usdt'])->find();
$orderbook_markup = $market['orderbook_markup'];

// Calculate the markup percentage
$markup_percentage = bcdiv($orderbook_markup, '100', 8);

// Calculate the api_max and api_min
$markup_value = bcmul($token1Price, $markup_percentage, 8);
$api_max = bcadd($token1Price, $markup_value, 8);
$api_min = bcsub($token1Price, $markup_value, 8);

// Format the tvlToken1 value to 8 decimal places
$tvlToken1 = bcdiv($tvlToken1,20, 8);

// Update the codono_market table
$data = [
    'api_max' => $api_max,
    'api_min' => $api_min,
    'api_max_qty' => $tvlToken1
];

$result = M('Market')->where(['name' => 'zb_usdt'])->save($data);

if ($result !== false) {
    echo "Successfully updated the market data.";
} else {
    echo "Failed to update the market data.";
}

    }
    public function index()
    {

        $tron = TronClient();
        $address = "TYDzsYUEpvnYmQk4zGP9sWWcTEd2MiAtW6";
        $testnet_address = "TJnUN8WBfBBcg6WmB5aJR22doBt4ih4C2W";
        $testnet_usdt = "TVoVJjrBpCZGDqru8k8DPjAU3rM6c9wAD8";
        $usdt = "TR7NHqjeKQxGTCi8q8ZY4pL8otSzgjLj6t";
        $tokenname = "usdt";
        $btt = 1002000;
        $decimals = 6;

        $hex = "41BBC8C05F1B09839E72DB044A6AA57E2A5D414A10";
        $txid = "835b70f77afc6fe2889d7420faa46eb946d71846218e412c74461cb91b420593";
        $txid_usdt = "c642c0fcbc472a0e8a37c1b4797260f5cdc348af01f4b1247f4e859362c9ceb8";
        
        $address_info = $tron->getTransaction($txid);
        print_r($address_info);exit; 
        echo "Newaddress->";
        $new_Address = $tron->generateAddress();
        var_dump($new_Address);
        echo "<--Newaddress";
        $abi = json_decode($tron->getAbi($tokenname), true);

        echo $tron->toAddress($address, 'e');
        //$balance =$tron->getBalance($address);
        //$trc10bal =$tron->getTrc10Balance($address,$btt,$decimals);
        echo "here";
        $trc20bal = $tron->getTrc20Balance($address, $usdt, $decimals, $abi);

        var_dump($trc20bal);
        $asset = $tron->getAssetsbyname('usdt');
        //var_dump($asset);
        $txinfo = $tron->getTrc20TxByAddress($address, $usdt);
        //var_dump($txinfo);
        $deposits = $tron->deposits($address);
        var_dump($deposits);
        //echo $tron->isConnected();
    }

    public function readByBlock()
    {
        $tron = TronClient();
        $blocks = $tron->getBlockRange(15753300, 15753332);
        //$blocks=$tron->getBlock(15753332);

        foreach ($blocks as $block) {
            if (isset($block['transactions'])) {
                $transactions[] = $block['transactions'];
            }
        }
        echo "<pre>";
        var_dump($transactions);
        echo "</pre>";
    }

    public function convHash()
    {
        $tron = TronClient();
        echo $tron->toAddress('TFnm4oE8usgcvDdcCLwZhoe2Aua2MRRZBL', 'e');
        $inf = $tron->getTransaction('fd24b85e901c805553d189fd0c273d53452064a4c33957fd96c48415de66231e');
        echo "<pre>";
        print_R($inf);
        echo "</pre>";

    }

    private function getUserHexAddresses()
    {
        $user_addresses = array("413fd6f1f7dd43d4d4479959d2961dcb13c5ffbb5b", "41ecff55666d99db7065186a3d8e100d15c75b64d0", "410c22811755ec2eca943e8b0618b4010011940202");
        return $user_addresses;
    }

    public function testDeposits()
    {
        $this->findDeposits(20113310, 20113311);
    }

    private function findDeposits($block_from, $block_to)
    {
        if ($block_from > $block_to) {
            return false;
        }
        echo "Reading blocks from $block_from to $block_to, in total number of  " . ($block_to - $block_from) . "blocks<br/>";
        G('begin');
        $user_addresses = $this->getUserHexAddresses();
        $tron = TronClient();
        $blocks = $tron->getBlockRange($block_from, $block_to);
        $transactions = $this->parseBlocks($blocks);

        if (!empty($transactions)) {
            foreach ($transactions as $tx) {
                echo "<pre>";
                print_r($tx);
                echo "</pre>";
                if (in_array($tx['info']['to_address_hex'], $user_addresses)) {
                    echo "<br/> Found " . $tx['info']['contract_hex'] . " for user" . $tx['info']['contract_hex'] . " <br/>";
                    //todo add entry in db
                    $this->processTxDB($tx);
                }
            }
        }
        G('end');
        echo "<br/>Total Time taken " . G('begin', 'end') . 's';
        echo "<br/>Total Memory taken " . G('begin', 'end', 'm') . 's';
        return true;
    }

    private function processTxDB($tx)
    {
        $hash = $tx['hash'];
        if (strlen($hash) != 64) {
            return false;
        }
        $mo = M();
        $result = $mo->table('codono_tron_hash')->where(array('hash' => $hash))->find();
        if (!is_array($result)) {
            $saveData = array('hash' => $hash, 'contract_hex' => $tx['info']['contract_hex'], 'to_address_hex' => $tx['info']['to_address_hex'], 'type' => $tx['type'], 'amount' => $tx['info']['amount'], 'addtime' => time(), 'status' => $tx['status']);
            return $mo->table('codono_tron_hash')->add($saveData);
        }
        {
            return "Already found";
        }
    }

    public function processHash()
    {
        $mo = M();
        $result = $mo->table('codono_tron_hash')->where(array('confirmed' => 0))->select();
        $tron = TronClient();
        foreach ($result as $res) {
            $info = $tron->getTransaction($res['hash']);
            echo "<br/>*******************<br/><pre>";
            print_r($info);
            echo "</pre><br/>*******************<br/>";
        }

    }

    private function parseBlocks($blocks)
    {
        $transactions = array();

        foreach ($blocks as $block) {
            $txs = $block['transactions']; //multiple transactions

            if (is_array($txs)) {
                foreach ($txs as $tx) {

                    $info = $this->txDetails($tx);
                    $transactions[] = $info;
                    //if(is_string($to_address) && $tx['ret'][0]['contractRet']=='SUCCESS'){
                    /*
                    if(is_string($info['to_address'])){
                        $transactions[$tx['txID']]=$info['to_address'];
                    }
                    */
                }

            }
        }
        return $transactions;
    }

    private function removeZero($string)
    {
        $length = strlen($string);
        var_dump($length);
        for ($i = 0; $i < 64 - $length; $i++) {
            $string = ltrim($string, '0');
        }
        return $string;
    }

    function has_prefix($string, $prefix)
    {
        return substr($string, 0, strlen($prefix)) == $prefix;
    }

    private function trc20Tx($info)
    {
        $tron = TronClient();
        $value = $info['raw_data']['contract']['0']['parameter']['value'];
        if (strlen($value['data']) != 136) {
            return false;
        }
        if (!$this->has_prefix($value['data'], 'a9059cbb')) {
            return false;
        }

        $resp['contract_hex'] = $value['contract_address'];
        $resp['contract'] = $tron->toAddress($value['contract_address']);
        //$resp['owner_hex']=$value['owner_address'];
        //$resp['owner']=$tron->toAddress($value['owner_address']);
        //$resp['data']=$value['data'];
        $resp['to_address_hex'] = $this->addressParse($value['data']);
        $resp['to_address'] = $tron->toAddress($resp['to_address_hex'], 'd');
        $resp['amount'] = hexdec(ltrim(substr($value['data'], 72), 0));
        return $resp;
    }

    public function testme()
    {
        $tron = TronClient();
        echo $tron->toAddress('7e4d1a3549db571631a68951615c587277385aba', 'd');
    }

    public function testnow()
    {
        echo D('Chain')->generateNewAddress('tron');
    }

    function addressParse($data)
    {
        if (!$this->has_prefix(substr($data, 30, 42), '41')) {
            $hex = '41' . substr($data, 32, 40);
        } else {
            $hex = substr($data, 30, 42);
        }
        return $hex;
    }

    private function txDetails($tx)
    {
        $transaction = array('hash' => $tx['txID'], 'status' => $tx['ret'][0]['contractRet']);
        $type = $tx['raw_data']['contract']['0']['type'];
        switch ($type) {
            case 'TriggerSmartContract':
                $transaction['type'] = 'trc20';
                $transaction['data'] = $tx['raw_data']['contract'][0]['parameter']['value']['data'];
                $transaction['info'] = $this->trc20Tx($tx);
                break;
            case 'TransferAssetContract':
                $transaction['type'] = 'trc10';
                break;
            case 'TransferContract':
                $transaction['type'] = 'trx';
                $transaction['info']['to_address_hex'] = $tx['raw_data']['contract'][0]['parameter']['value']['to_address'];
                $transaction['info']['amount'] = $tx['raw_data']['contract'][0]['parameter']['value']['amount'];
                break;

            default:
                $transaction['type'] = 'other';
                $transaction['info'] = $type;

        }
        return $transaction;
    }

    public function exception()
    {
        try {
            // Deliberately causing an error by attempting to create an object of a non-existent class
            echo "EOs";
            $obj = new NonExistentClass();
            
        } catch (\Throwable $e) {
            // This block executes if an exception is thrown in the try block
            echo "Caught exception: " . $e->getMessage();
        }
        echo "EOF";
    }

    public function redistest()
    {


        S('OK', "Redis is Working");

        echo "Hello";
        echo S('OK');
    }

    public function redispush_test($clear = null)
    {
        $my_fifo_list = "banned_list";
        $cache = Cache::getInstance('Redis');
        $banIp = '192.168.1.' . rand(1, 255);

        if (isset($clear)) {
            // Optionally clear the entire list if $clear is set
            //   $this->clearBanList($cache, $my_fifo_list);
        }
        //unban if 10 seconds passed
        $unban_before = time() - 50;
        // $this->deleteBannedIPsBeforeTimestamp($unban_before,$cache,$my_fifo_list);
        $this->pushBanToList($banIp, time(), $cache, $my_fifo_list);

        $info = $this->getBannedIPsFromRedis($cache, $my_fifo_list);
        $am_i_banned = $this->isIPBanned('192.168.1.2222', $cache, $my_fifo_list);
        $cache->close();
        if (($am_i_banned)) {
            echo '192.168.1.224 is banned';
        } else {
            echo 'not banned';
        }
        print_r(['ok', $am_i_banned]);
        print_r($info);
        echo "LLx";
    }

    function isIPBanned($ip, $redis, $banListKey)
    {
        // Retrieve the entire list
        $banList = $redis->lrange($banListKey, 0, -1);

        // Iterate through the list to decode JSON and extract IP
        foreach ($banList as $banData) {
            [$bannedIP, $timestamp] = json_decode($banData);

            // Check if the provided IP matches any banned IP
            if ($ip === $bannedIP) {
                return true;
            }
        }

        return false;
    }

    function deleteBannedIPsBeforeTimestamp($timestamp, $redis, $banListKey)
    {
        // Retrieve the entire list
        $banList = $redis->lrange($banListKey, 0, -1);

        // Iterate through the list to decode JSON and extract IP and timestamp
        foreach ($banList as $index => $banData) {
            [$ip, $banTimestamp] = json_decode($banData);

            // Check if the ban timestamp is less than the specified timestamp
            if ($banTimestamp < $timestamp) {
                // Remove the element from the list
                //echo "found bantime is ".$banTimestamp. "current time is ".$timestamp."<br/>";
                $redis->lrem($banListKey, $banData, 1);
            }
        }
    }

    function clearBanList($redis, $banListKey)
    {
        // Remove the entire list
        $redis->del($banListKey);
    }

    function getBannedIPsFromRedis($redis, $banListKey)
    {
        // Retrieve the entire list
        $banList = $redis->lrange($banListKey, 0, -1);

        $bannedIPs = [];

        return $banList;
    }

    function pushBanToList($ip, $timestamp, $redis, $banListKey)
    {
        // Create a JSON-encoded array with the IP and timestamp
        $banData = json_encode([$ip, $timestamp]);

        // Use RPUSH to add the ban data to the list
        $redis->rpush($banListKey, $banData);
    }


    public function storedProcedure()
    {
        $mo = M();
        G('begin');
        $result = $mo->query("CALL getUsernames()");
        echo "ok";
        var_dump($result);
        G('end');
        echo "<br/>Total Time taken " . G('begin', 'end') . 's';
        echo "<br/>Total Memory taken " . G('begin', 'end', 'm') . 's';
    }


    public function BinanceTicker()
    {
        $api = binance();
        try {
            $ticker = $api->prices();
        } catch (\Exception $e) {
            echo "<pre>";
            print_r($e->getMessage());
            echo "</pre>";
        } // Make sure you have an updated ticker object for this to work

        var_dump($ticker);
    }

    public function FeeTest()
    {
        $userid = 38;
        $market = 'btc_usdt';
        echo $this->marketFees($market, $userid, 1);
    }

    /*
$type=1 buy 
$type=2 sell
*/
    private function marketFees($market, $userid, $type = "")
    {
        $TradeFees = M('TradeFees')->where(array('userid' => $userid, 'market' => $market))->find();
        if ($TradeFees['userid'] == $userid) {
            $buy_fees = $TradeFees['fee_buy'];
            $sell_fees = $TradeFees['fee_sell'];
        } else {

            $buy_fees = C('market')[$market]['fee_buy'];
            $sell_fees = C('market')[$market]['fee_buy'];
        }

        if ($type == 1) {
            return $buy_fees;
        } else {
            return $sell_fees;
        }
    }

    public function moveUsdt()
    {
        //$sendrs = $tron->transferTrc20($abi, $contract_address, $main_address, $token_balance, $tx['username'], $priv, $decimals);

        $tron = TronClient();

        $addresses = M('paradaks') // 'paradaks' is the name of your table
        ->where('usdt > 1.5 AND moved = 0')
            ->order('usdt asc')
            ->limit(20)
            ->select();


        $abi = $tron->getAbi('tusdt');

        $contract_address = 'TR7NHqjeKQxGTCi8q8ZY4pL8otSzgjLj6t';
        $decimals = 6;
        $main_address = $to_address = 'TJVtfWPV4T2kEyin8efocBFvh1KCJnJPud';

        foreach ($addresses as $address) {
            $from = $address['address'];
            $priv = $address['priv_key'];
            $token_balance = $address['usdt'];
            //transferTrc20($abi,$contract,$toAddress,$amount,$from,$private_key,$decimals)
            $sendrs = $tron->transferTrc20($abi, $contract_address, $main_address, $token_balance, $from, $priv, $decimals);
            var_dump($sendrs);
            echo "980";
            if ($sendrs) {
                $updateStatus = M('Paradaks')
                    ->where(array('id' => $address['id']))
                    ->save(array('moved' => 1));
                echo "ok $updateStatus Moved markerd<br/>";
            } else {
                var_dump([$sendrs['txid']]);
            }
            echo "989";
            sleep(4);
        }

        //validate hash


    }

    public function tronMove()
    {
        //

        $tron = TronClient();

        $addresses = M('paradaks') // 'paradaks' is the name of your table
        ->where('trx > 0.00001 AND moved = 0')
            ->order('trx DESC')
            ->limit(4000)
            ->select();


        $main_address = $to_address = 'TJVtfWPV4T2kEyin8efocBFvh1KCJnJPud';

        foreach ($addresses as $address) {
            $from = $address['address'];
            $priv = $address['priv_key'];
            $ubal = bcsub($tron->getBalance($from), 0, 8);
            sleep(0.1);
            //var_dump(array($main_address, $ubal, $from, $priv));
            if($ubal>0){

            }else{
                continue;
            }
            $sendrs = $tron->sendTransaction($main_address, $ubal, $from, $priv);
            var_dump($sendrs);

            if ($sendrs) {
                $updateStatus = M('Paradaks')
                    ->where(array('id' => $address['id']))
                    ->save(array('moved' => 1));
                echo "ok $updateStatus Moved markerd<br/>";
            } else {
                var_dump([$address['id'], $sendrs]);
                $updateStatus = M('Paradaks')
                    ->where(array('id' => $address['id']))
                    ->save(array('moved' => 2));
                echo "Failed<br/>";
            }

            sleep(0.1);
        }

        //validate hash


    }

    public function tome()
    {
        $info = M('Article')
            ->where(array('id' => '19428'))
            ->save(array('moved' => 0));
        //->find();
        print_r($info);
		echo "KK";
    }

    public function factoryHandle()
    {
        $resp = CoinFactory::getHandler('tron', 'scott');
        var_dump($resp);
    }
    public function filter($price, $num, $type, $tradeType = 'limit', $stop = 0, $paypassword = NULL, $market = NULL)
    {
        // Retrieve and sanitize input parameters using ThinkPHP's I() function
        $price = I('get.price/f');       // Retrieves 'price' from GET and filters as a float
        $num = I('get.num/f');           // Retrieves 'num' from GET and filters as a float
        $type = I('get.type/d');   
        var_dump([$price,$num,$type]);
    }
    public function exceptiond(){
        echo "ok";
     //   throw new Think() /Exception('invalid image type');
        try {
            // Assuming $thos is not defined or not an object, this will cause a fatal error
            $this->sdsd = 'sdsd';
            echo "heredd\n";
        } catch (\Exception $e) {
            // Catching the error and displaying information
            
        }
        
        echo "hhs";
    }
    public function tester(){
        $coins=C('Coin');
        //var_dump($coins);
        $markets=C('market');
        var_dump($markets);

    }
	public function mailtest(){
		exit();
		$info=addnotification('ok@info.com','hello','This is content');
		var_dump($info);
	}
    private function fetchBinanceData() {
        $curl = curl_init();
    
        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://api.binance.com/api/v3/ticker/24hr",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_SSL_VERIFYPEER => 0,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_HTTPHEADER => array(
                "cache-control: no-cache"
            ),
        ));
    
        $response = curl_exec($curl);
        $err = curl_error($curl);
    
        curl_close($curl);
    
        if ($err) {
            echo "cURL Error #:" . $err;
            return null;
        } else {
            return json_decode($response, true);
        }
    }

    private function checkPossibleMarkets() {
        $binanceData = $this->fetchBinanceData();
        $coins = C('Coin');
        $configuredMarkets = C('Market');

        $possibleMarkets = [];
        
    
                foreach ($coins as $coinKey => $coinValue) {    
                    
                        $marketCoinKey='btc';
                        $expectedMarket = $coinKey . '_' . $marketCoinKey;
                        $expectedSymbol = strtoupper($coinKey . $marketCoinKey) ;
                        
                        //if (strpos($symbol, $expectedSymbol) !== false && isset($markets[$expectedMarket])) {
                            $possibleMarkets[$expectedSymbol] = $expectedMarket;
                        //}
                    
                }
           $possibleMarkets = array_unique($possibleMarkets);
              
        foreach ($binanceData as $data) {
        $symbol = $data['symbol'];
        if (isset($possibleMarkets[$symbol])) {
            $validMarkets[] = $possibleMarkets[$symbol];
        }
    }
        // Filter out the markets that already exist in the configuration
        $newMarkets = array_filter($validMarkets, function($market) use ($configuredMarkets) {
            return !isset($configuredMarkets[$market]);
        });
    
        return array_values($newMarkets); // Reindex the array
    //return array_unique($validMarkets);
    }

    
    public function testxe(){
        $markets = $this->checkPossibleMarkets();
        echo "Possible Markets: \n";

// Default values for the fields
$ownerid = 0;
$round = 8;
$fee_buy = 0.10000000;
$fee_sell = 0.10000000;
$buy_min = 0.00000001;
$buy_max = 10000000.00000000;
$sell_min = 0.00000001;
$sell_max = 10000000.00000000;
$trade_min = 0.00000001;
$trade_max = 10000000000.00000000;
$invit_buy = 1;
$invit_sell = 1;
$invit_1 = $invit_2 = $invit_3 = 0.0000;
$zhang = $die = '';
$hou_price = 'NULL';
$tendency = NULL;
$trade = 1;
$new_price = $buy_price = $sell_price =$min_price =$max_price =$volume=$change=$market_ico_price=0;
$api_min = $api_max = $api_max_qty = 0.00000000;
$begintrade = '00:00:00';
$endtrade = '23:59:00';
$sort = 0;
$addtime = $endtime = 'NULL';
$status = 1;
$jiaoyiqu = 0;
$orderbook_markup = 1.522200;
$ext_price_update = 1;
$socket_type =2;
 $ext_orderbook =  $socket_pair  = $ext_charts  = $xtrade = 1;
 $ext_fake_trades =0;
foreach ($markets as $key=>$market) {
    $charts_symbol=$this->toBinMarket($market);
    echo "INSERT INTO `codono_market` (`name`, `ownerid`, `round`, `fee_buy`, `fee_sell`, `buy_min`, `buy_max`, `sell_min`, `sell_max`, `trade_min`, `trade_max`, `invit_buy`, `invit_sell`, `invit_1`, `invit_2`, `invit_3`, `zhang`, `die`, `hou_price`, `tendency`, `trade`, `new_price`, `buy_price`, `sell_price`, `min_price`, `max_price`, `volume`, `change`, `api_min`, `api_max`, `api_max_qty`, `begintrade`, `endtrade`, `sort`, `addtime`, `endtime`, `status`, `jiaoyiqu`, `market_ico_price`, `ext_price_update`, `ext_fake_trades`, `ext_orderbook`, `socket_type`, `socket_pair`, `orderbook_markup`, `ext_charts`, `charts_symbol`, `xtrade`) VALUES ('$market', $ownerid, $round, $fee_buy, $fee_sell, $buy_min, $buy_max, $sell_min, $sell_max, $trade_min, $trade_max, $invit_buy, $invit_sell, $invit_1, $invit_2, $invit_3, '$zhang', '$die', $hou_price, '$tendency', $trade, $new_price, $buy_price, $sell_price, $min_price, $max_price, $volume, $change, $api_min, $api_max, $api_max_qty, '$begintrade', '$endtrade', $sort, $addtime, $endtime, $status, $jiaoyiqu, $market_ico_price, $ext_price_update, $ext_fake_trades, $ext_orderbook, $socket_type, $socket_pair, $orderbook_markup, $ext_charts, '$charts_symbol', $xtrade);\n";
}

 }    
    private function toBinMarket($market){
        $noUnderscores = str_replace('_', '', $market); // Remove underscores
        return strtoupper($noUnderscores); 
    }
    public function testHandleDeposit()
    {
        $testTransaction = [
            'userid' => 1,
            'username' => 'testuser',
            'coinname' => 'btc',
            'chain' => 'bitcoin',
            'type' => 'btc',
            'txid' => 'testtxid1234567890',
            'memo' => 'Test deposit',
            'num' => '0.01',
            'mum' => '0.0095',
            'fee' => '0.0005',
        ];
        $depositService = new DepositService();

        $depositService->handleDeposit($testTransaction);

        echo 'Test deposit handled.';
    }
    public function checkPendingDeposits()
    {
        $pendingTransactions = M('Pending')->where(['aml_status' => 0])->select();
        
        if(count($pendingTransactions)<=0){
            var_dump(['status'=>0,'message'=>'No pending transactions']);
        }
        $depositService = new DepositService();
        foreach ($pendingTransactions as $transaction) {
            
                $info[]=$depositService->approveDeposit($transaction['id']);
           
        }
        print_r($info);
        echo "<br/>All records processed ";
    }

}