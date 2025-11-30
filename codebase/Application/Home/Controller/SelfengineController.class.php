<?php

namespace Home\Controller;

class SelfengineController extends HomeController
{
    const day_to_process_invoice = "Mon";
    const number_of_orders = 20;

    public function __construct()
    {
        parent::__construct();
		checkcronkey();
    }

    public function index()
    {
        echo "run Connected";
    }

    /**
     *Creates Orderbook for Markets where SelfEngine is enabled using prices mentioned in backend
     */
    public function CreateOrderbook()
    {
        cronLastRun(__FUNCTION__);
        echo "<br/> Updating Market Table now<br/>";
        G('begin');
        $this->initSelfEngine();
        G('end');
        echo "<br/>Total Time taken " . G('begin', 'end') . 's';

    }

    private function initSelfEngine()
    {
        $markets = C('market');
        foreach ($markets as $market) {

            if ($market['ext_orderbook'] == 2) {
                echo "********************<br/>Starting " . $market['name'] . "<br/>";
                $this->MapSelfEngine($market['name']);
                echo "********************<br/>Completed " . $market['name'] . "<br/>";
            }
        }

    }

    /**
     * @param $market
     * @return void
     */
    private function MapSelfEngine($market): void
    {
        $found_pair = C('Market')[strtolower($market)];
        if ($found_pair['name'] != strtolower($market)) {
            return;
        }
        $commission = $found_pair['orderbook_markup'];
        $self_buy_price = $found_pair['api_min'];
        $self_sell_price = $found_pair['api_max'];
        $max_qty = $found_pair['api_max_qty'];
        $lastId = time() . rand(100000, 999999);
        $number_of_orders = self::number_of_orders;
        if ($self_buy_price <= 0 || $self_sell_price <= 0) {
            return;
        }
        if ($found_pair != null && is_array($found_pair)) {
            $this->SelfOrderBookGen($market, $self_buy_price, $max_qty, $self_sell_price, $max_qty, $lastId, $commission, $number_of_orders);
        }
    }


    //Create Orderbook using a price
    private function SelfOrderBookClean($market, $lastid): void
    {
        M('Trade')->where(array('userid' => 0, 'market' => $market, 'flag' => array('lt', $lastid)))->delete();
    }

    /**
     * @param $market
     * @param $bidPrice
     * @param $bidQty
     * @param $askPrice
     * @param $askQty
     * @param $lastId
     * @param $commission
     * @param $number_of_orders
     * @return void
     */
    private function SelfOrderBookGen($market, $bidPrice, $bidQty, $askPrice, $askQty, $lastId, $commission, $number_of_orders): void
    {

        $avgQty = bcdiv(bcadd($askQty, $bidQty, 8), 2, 8);
        $last['bidPrice'] = bcmul($bidPrice, (1 - $commission / 100), 8);
        $last['bidQty'] = $avgQty; //$bidQty;
        $last['askPrice'] = bcmul($askPrice, (1 + $commission / 100), 8);
        $last['askQty'] = $avgQty; //$askQty;
        $ask_stack = $bid_stack = array();
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
            $last['askPrice'] = bcmul($last['askPrice'], $rand_ask_price, 10);
            $last['askQty'] = bcmul($last['askQty'], $rand_ask_qty, 10);
			if($last['bidPrice']>0){
            $bid_stack[$i]['market'] = $market;
            $bid_stack[$i]['price'] = $last['bidPrice'];
            $bid_stack[$i]['num'] = $last['bidQty'];
            $bid_stack[$i]['type'] = 1;
            $bid_stack[$i]['addtime'] = time();
            $bid_stack[$i]['flag'] = $lastId;
			}
            $ask_stack[$i]['market'] = $market;
            $ask_stack[$i]['price'] = $last['askPrice'];
            $ask_stack[$i]['num'] = $last['askQty'];
            $ask_stack[$i]['type'] = 2;
            $ask_stack[$i]['addtime'] = time();
            $ask_stack[$i]['flag'] = $lastId;
        }

        $this->SelfOrderBookClean($market, $lastId);

        //echo "<br/><strong>Found $cleaned old records from $market orderbook , So deleted them, Now adding new Orderbook</strong><br/>";
        $stacks = array_merge_recursive($bid_stack, $ask_stack);

        M('Trade')->addAll($stacks);
        $allow_trade_log=C('market')[$market]['ext_fake_trades']?:0;
        A('Trade')->matchingTrade($market);
        if ($allow_trade_log == 1) {
            //  echo "<br/>Now adding Trade Logs<br/>";
            $this->SelfOrderLogGenerate($stacks);
			S('getTradelog' . $market, null);
        }
        S('getDepth', null);
        S('getActiveDepth' . $market, null);
        S('getActiveDepth', null);
        S('getDepthNew', null);
		S('getTradelog' . $market, null);
        S('getJsonTop' . $market, null);
		S('home_market', null);
		$this->updateMarketStats($market);
        A('Ajax')->getActiveOrders($market,1,0);
        
    }

    private function SelfOrderLogGenerate($_stacks)
    {
        //    var_dump($_stacks);
        shuffle($_stacks);
        shuffle($_stacks);

        $size = rand(0, 4);
        $stacks = array();
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
				$stack['num']=rand(bcmul($stack['num'],0.01,8),bcmul($stack['num'],0.25,8));
                $stack['mum'] = bcmul($stack['num'], $stack['price'], 8);

                unset($stack['flag']);
                unset($stack['deal']);
                unset($stack['fee']);
                M('TradeLog')->add($stack);
            }
        }
        echo "<br/>Added $count trade_log records";
    }

    private function updateMarketStats($market)
    {
        $new_price = format_num(M('TradeLog')->where(array('market' => $market, 'status' => 1))->order('id desc')->getField('price'));
        $buy_price = format_num(M('Trade')->where(array('type' => 1, 'market' => $market, 'status' => 0))->max('price'));
        $sell_price = format_num(M('Trade')->where(array('type' => 2, 'market' => $market, 'status' => 0))->min('price'));
        $min_price = format_num(M('TradeLog')->where(array(
            'market' => $market,
            'addtime' => array('gt', time() - (60 * 60 * 24))
        ))->min('price'));
        $max_price = format_num(M('TradeLog')->where(array(
            'market' => $market,
            'addtime' => array('gt', time() - (60 * 60 * 24))
        ))->max('price'));
        $volume = format_num(M('TradeLog')->where(array(
            'market' => $market,
            'addtime' => array('gt', time() - (60 * 60 * 24))
        ))->sum('num'));
        /*
            $sta_price = format_num(M('TradeLog')->where(array(
                'market' => $market,
                'status' => 1,
                'addtime' => array('gt', time() - (60 * 60 * 24))
            ))->order('id asc')->getField('price'), 8);
        */
        $Cmarket = C('market')[$market];

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
        //$hourly_price = $Cmarket['hou_price'] ?: 1;
        //$change = format_num((($new_price - $Cmarket['hou_price']) / $hourly_price) * 100, 2);
		$old_price= $Cmarket['new_price'];
		$change = bcmul(bcdiv(bcsub($new_price , $old_price,8), $old_price,8) , 100, 2);
        $upCoinData['change'] = $change;

        if ($upCoinData) {
            M('Market')->where(array('name' => $market))->save($upCoinData);
            M('Market')->execute('commit');
            S('home_market', null);
        }
    }


    /**
     * @param $size
     * @return int
     */
    private function salt_stamp($size): int
    {
        return (int)bcadd($size, rand(1, 10),0);

    }

    /**Invoice Module for codono_trade
     *Find Trades within a week, Find unique userid in them , Ignore if userid0
     */
    public function findWeeklyTrades()
    {
        G('begin');
        cronLastRun(__FUNCTION__);
        $markets = C('market');
        $day_today = date('D');
        $processing_day = self::day_to_process_invoice;
        if ($processing_day != $day_today) {
            die("Today is $day_today but this can be run on each $processing_day");
        }
        echo "Today is " . date('D');
        foreach ($markets as $market) {
            $this->weeklyTrades($market['name']);
        }
        G('end');
        echo "<br/>Total Time taken " . G('begin', 'end') . 's';
    }

    /**
     * if ran on Sunday , it will check from last sunday to Saturday for all trades
     * @param $market
     * @return void
     */
    private function weeklyTrades($market): void
    {

        $now = time();
        $hour = 24;
        $today = strtotime($hour . ':00:00');
        $yesterday = strtotime('-1 day', $today);

        $secs_in_week = 604800;
        $week_before_ts = bcsub($yesterday, $secs_in_week);

        $to_date = date("dmY", $yesterday);
        $from_date = date("dmY", $week_before_ts);
        $where['market'] = $market;
        $where['status'] = 1;
        $where['addtime'] = array(array('lt', (string)$yesterday), array('gt', $week_before_ts), 'and');

        $Model = M('Trade');
        $trades_total = $Model->where($where)->distinct(true)->field('userid')->count();//$Model->where($where)->count();
        echo "<br/>found $trades_total trades in $market between $from_date to $to_date <br/>";
        if ($trades_total < 1) {
            return;
        }
        $user_ids = $Model->where($where)->distinct(true)->field('userid')->select();
        $user_distinct = count($user_ids);
        $stats_array = array('from_date' => $from_date, 'to_date' => $to_date, 'user_distinct' => $user_distinct, 'trades' => $trades_total, 'market' => $market, 'addtime' => $yesterday);
        $mo = M();
        $check_if_exists = $mo->table('codono_tradeinvoice_stats')->where(array('from_date' => $from_date, 'to_date' => $to_date, 'market' => $market))->find();
        if (is_array($check_if_exists)) {
            echo "<br/>Already processed $market <br/>";
            return;
        }
        $fees = array();
        $i = 0;
        foreach ($user_ids as $userid) {
            if ($userid['userid'] == 0) {
                //This is liquidity user
                continue;
            }
            $check_if_exists_user = $mo->table('codono_tradeinvoice_queue')->where(array('from_date' => $from_date, 'to_date' => $to_date, 'market' => $market, 'userid' => $userid['userid']))->find();
            if (is_array($check_if_exists_user)) {
                echo "<br/>Already processed User<br/>";
                return;
            }
            $where['userid'] = $userid['userid'];


            $fees[$i]['userid'] = $userid['userid'];
            $fees[$i]['market'] = $market;
            $fees[$i]['trades'] = $Model->where($where)->count();
            $fees[$i]['fees'] = $Model->where($where)->sum('fee');
            $i++;

        }
        if (sizeof($fees) > 0) {
            echo "<br/><strong>entering entries for $market </strong>";
            $mo = M();
            $mo->startTrans();

            //Do something

            $rs[] = $mo->table('codono_tradeinvoice_stats')->add($stats_array);

            foreach ($fees as $fee) {
                $queue = array('from_date' => $from_date, 'to_date' => $to_date, 'userid' => $fee['userid'], 'fees' => $fee['fees'], 'trades' => $fee['trades'], 'market' => $market, 'addtime' => $now);
                $rs[] = $mo->table('codono_tradeinvoice_queue')->add($queue);
            }

           if (check_arr($rs)) {
				$mo->commit();
                echo ' Added Entries!';
            } else {

                $mo->rollback();
                echo ' Issues calculating !!';
            }
        } else {
            echo "<br/>Fee entries were 0 for $market <br/>";
        }

    }

    /**
     *  generateInvoice starts generating invoices for customers who traded in the last week
     */
    public function generateInvoice()
    {
        G('begin');
        cronLastRun(__FUNCTION__);
        $mo = M();
        $datas = $mo->table('codono_tradeinvoice_queue')->where(array('invoice_sent' => 0))->select();
        $xdatas = $outs = array();
        foreach ($datas as $data) {
            $xdatas[$data['userid']][] = $data;
        }
        $i = 0;

        foreach ($xdatas as $keys => $xdata) {
            $outs[$i]['userid'] = $keys;
            $outs[$i]['data'] = $xdata;
            $i++;
        }
        foreach ($outs as $out) {
            echo "Running ";
            $this->createInvoice($out);
        }

        G('end');
        echo "<br/>Total Time taken " . G('begin', 'end') . 's';
    }

    /**
     * Invoice creation method
     * @param $out
     * @return void
     */
    private function createInvoice($out): void
    {

        $uid = $out['userid'];
        $datas = $out['data'];
        $invoiceid = $datas[0]['id'];
        $description = "";
        foreach ($datas as $data) {
            $map['id'][] = array('eq', $data['id']);
            $rmb = explode('_', $data['market'])[1];
            $description .= '<tr class="item-row">
        <td class="item-name"><div class="delete-wpr"><span>' . $data['market'] . ' </span></div></td>
        <td class="description"><span>Trading Fees</span></td><td colspan="2"><span class="cost">' . $rmb . '</span></td><td><span class="price">' . $data['fees'] . '</span></td></tr>';
        }


        $userinfo = M('User')->where(array('id' => $uid))->field('email,truename')->find();
        if ($userinfo['id'] != $uid && $uid == 0) {
            return;
        }
        $to_email = $userinfo['email'];
        if (!$to_email) {
            return;
        }

        $addtime = date('Y-m-d ');
        $subject = "Your weekly invoice for " . SHORT_NAME . " on " . $addtime;

        if (!$subject) {
            $return = array('status' => 0, 'message' => "Ensure you have filled all fields, to,subject,content");
            json_encode($return);
            return;
        }
        $logo = SITE_URL . '/Upload/public/' . C('web_logo');

        $template = file_get_contents('./Public/invoice-content.html');
        $customer_info = $userinfo['truename'] . "<br/>" . $userinfo['email'];
        $addtime = date('Y-m-d ');

        $vars = array(
            '{$logo}' => $logo,
            '{$customer_info}' => $customer_info,
            '{$invoiceid}' => $invoiceid,
            '{$addtime}' => $addtime,
            '{$description}' => $description,
        );
        $body = strtr($template, $vars);
        addnotification($to_email, $subject, $body);

        $map['id'][] = array('or');
        $mo = M();
        $mo->table('codono_tradeinvoice_queue')->where($map)->save(array('invoice_sent' => 1));
    }
	/*Delete Trade_log where userid,peerid is 0 and older than 2 days*/
	public function cleanUp($days=2){
		
		G('begin');
        cronLastRun(__FUNCTION__);
		$now=time();
		$seconds_x_days_before=1*60*60*24*$days;
		$check_date=$now-$seconds_x_days_before;
		$where['userid']=0;
		$where['peerid']=0;
		$where['addtime']=array('elt',$check_date);
		$records=M('TradeLog')->where($where)->delete();
		G('end');
		echo "<br/>Records Deleted: $records and Total Time taken " . G('begin', 'end') . 's';
	}
    public function cleanUpBot(){
		G('begin');
        cronLastRun(__FUNCTION__);
        $market =  I('get.market', null, 'text');
        if (!check($market, 'market')) {
            exit('Invalid Market Error');
        }
		$where['userid']=0;
        $where['market']=$market;
		$records=M('Trade')->where($where)->delete();
		G('end');
		echo "<br/>Records Deleted: $records and Total Time taken " . G('begin', 'end') . 's';
	}
    public function test(){
        $url='https://api.xeggex.com/api/v2/market/getlist';
        $info=$this->gcurl($url);
        var_dump($info);
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
    //SelfEngine code ends
}