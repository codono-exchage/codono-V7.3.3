<?php

namespace Api\Controller;

class InternalController extends CommonController
{
    private int $internal_api_status = 0; //Disable by 0 [Keep it disabled if you do not know what is this]
    private string $system_APIKEY = "bf55fcff5d1cbeebf5f00e08e2".ADMIN_KEY;
    private string $system_SECRET = "5f2b5cdbe5194f10b3241568fe".ADMIN_KEY;

    private string $auth_type = "simple"; //use either simple or advance

    /*
    Advance auth will need you to calclate token using following formula
    $array : post data

    $token=hash_hmac ( 'sha256' , md5(serialize($array)) , $system_SECRET);

    Provide APIKEY and TOKEN in header
    */

    public function __construct()
    {

        exit(json_encode(array('msg'=>"Comment Line:".__LINE__.' to start this Internal api','status'=>0)));
        parent::__construct();
        if ($this->internal_api_status != 1) {
            exit(json_encode(array('msg' => 'Internal API\'s are disabled', 'status' => 0)));
        }
    }

    public function index()
    {
        $array = array('status' => 1, 'message' => 'Connected to Internal API');
        echo json_encode($array);
    }

    protected function Simpleauth()
    {

        if (!$_SERVER['HTTP_APIKEY'] || !$_SERVER['HTTP_TOKEN']) {
            $this->ajaxShow('Unauthorized E1', -99);
        }
        $input_id = trim($_SERVER['HTTP_APIKEY']);
        $input_token = trim($_SERVER['HTTP_TOKEN']);

        if ($input_id != $this->system_APIKEY || $input_token != $this->system_SECRET) {

            $this->ajaxShow('Unauthorized E2', -99);
        } else {
            return true;
        }

    }

    protected function Advancedauth($post)
    {

        if (!$_SERVER['HTTP_APIKEY'] || !$_SERVER['HTTP_TOKEN']) {
            $this->ajaxShow('Unauthorized E3', -99);
        }

        $generated_token = hash_hmac('sha256', md5(serialize($post)), $this->system_SECRET);
        $input_id = trim($_SERVER['HTTP_APIKEY']);
        $input_token = trim($_SERVER['HTTP_TOKEN']);

        if ($input_id != $this->system_APIKEY || $input_token != $generated_token) {
            $this->ajaxShow('Unauthorized E4', -99);
        } else {
            return true;
        }

    }

    protected function auth($post)
    {
        if ($this->auth_type == 'simple') {
            return $this->Simpleauth();
        } else {
            return $this->Advancedauth($post);
        }
    }
	/*
	Create Multiple orders, and if there are pending orders and amount isnt sufficient then it cancels also
	*/
	public function multipleOrders()
    {
		 G('begin');
		 $input=I('post.');
		$this->auth($input);
		$form = $input;
		   
        $market = strtolower($form['market']);
        if(!C('market')[$market]['trade']){
			$this->error(strtoupper($market).' '.L('trading is currently disabled!'));
		}

		$TXID = $form['type'];
		$Datetime=	I('post.Datetime',0,'Datetime');
        $uid = I('post.uid',0,'int');
		$type = I('post.type',0,'int');
		$market_check = I('post.market','','text');
		$market_info=C('market')[$market_check];
		$market=$market_info['name'];
		$first_coin=$market_info['xnb'];
		$first_coind=$first_coin.'d';
		$last_coin=$market_info['rmb'];
		$last_coind=$last_coin.'d';
		if(!$market){
			$this->error('Please check market name');
		}
		
		if($type!=1 && $type!=2){
			$this->error('Please check correct order type');
		}
		$orders = json_decode(I('post.orders_json','{}','text'),true);;
		//$orders=[['10000.445','0.12'],['10000.445','0.12'],['10000.445','0.12'],['10000.445','0.12']];
		if(!is_array($orders) || empty($orders)){
			$this->error('Please check orders_json should be correct json array');
		}
		$total_required=array('rmb'=>0,'xnb'=>0);
		foreach($orders as $order){
			 if (!check($order[0], 'currency')){
				 $this->error('Please make sure orders_json array is in correct format');
			 } 
			 $rmb_total=bcmul($order[0],$order[1],8);
			$total_required['rmb']=bcadd($total_required['rmb'],$rmb_total,8);// sum(price * qty )
			$total_required['xnb']=bcadd($total_required['xnb'],$order[1],8); //sum(quantity )total
		}
		//validate user and Find users balance
		$mo=M();
		$user_info=$mo->table('codono_user')->where(array('id' => $uid,'status'=>1))->find();
		if(!isset($user_info) ||$user_info['id']!=$uid){
			$this->error('Incorrect uid '.$uid);
		}
		
		$user_balance=$mo->table('codono_user_coin')->where(array('userid' => $uid))->find();
		//Buy
		if($type == 1){
			$user_total =array($user_balance[$last_coin],$user_balance[$last_coind],bcadd($user_balance[$last_coin],$user_balance[$last_coind],8));
			$required=$total_required['rmb'];
			$coin_needed=$last_coin;
			$order_check='mum';
		}else{
			$user_total =array($user_balance[$first_coin],$user_balance[$first_coind],bcadd($user_balance[$first_coin],$user_balance[$first_coind],8));
			$required=$total_required['xnb'];
			$coin_needed=$first_coin;
			$order_check='num';
		}
		$stats=array('user_total'=>$user_total,'required'=>$required,'coin_needed'=>$coin_needed,'order_check'=>$order_check);
		$short_on_money=0;
		if($required> $user_total[0] && $required > $user_total[2])
		{
		$this->error("User needs $required $coin_needed, he has $user_total[2] total");
		}
		if($required> $user_total[0])
		{
		$short_on_money=1;
		}
		// lock table , user_coin codono_trade
		if($short_on_money==1)
		{
			
			$count_pending_orders=$mo->table('codono_trade')->where(array('userid' => $uid,'market'=>$market,'status'=>0,'type'=>$type))->count();
			$pending_orders=$mo->table('codono_trade')->where(array('userid' => $uid,'market'=>$market,'status'=>0,'type'=>$type))->select();
			$pending_num=0;
			$pending_mum=0;
			foreach($pending_orders as $pending_order){
				$porders['num']=bcadd($pending_num,$pending_order['num'],8);
				$porders['mum']=bcadd($pending_mum,$pending_order['mum'],8);
				$this->cancel($pending_order['id']);
			}	
			$stats['order_num']=$porders['num'];
			$stats['order_mum']=$porders['mum'];
			
		}
		
		
		foreach($orders as $order){
			 $this->MakeTrade($market, $order[0], $order[1], $type,$uid);
			 $rmb_total=bcmul($order[0],$order[1],8);
			$total_required['rmb']=bcadd($total_required['rmb'],$rmb_total,8);// sum(price * qty )
			$total_required['xnb']=bcadd($total_required['xnb'],$order[1],8); //sum(quantity )total
		}
			
		    S('getDepth', null);
            S('getActiveDepth', null);
            S('getActiveDepth'.$market, null);
            S('getDepthNew', null);
            exec(PHP_PATH." index.php /Home/Trade/matchingTrade/market/$market");           //Matches the orders fast and in background
          G('end');
		        $send['status'] = 1;
                $send['Datetime'] = time();
                $send['market'] = $market;
                $send['TXID'] = $TXID;
                $send['msg'] = "Total Time taken " . G('begin', 'end') . 's';
                echo json_encode($send);
		
    }
	 private function MakeTrade($market = NULL, $price, $num, $type,$uid)
    {

        if (!check($price, 'double')) {
            $this->error(L('The transaction price is malformed'));
        }

        if (!check($num, 'double')) {
            $this->error(L('The number of transactions is malformed'));
        }

        if (($type != 1) && ($type != 2)) {
            $this->error(L('Transaction type format error'));
        }

        $user = M('User')->where(array('id' => $uid))->find();
		
        if (!C('market')[$market]) {
            $this->error(L('Error market'));
        } else {
            $xnb = explode('_', $market)[0];
            $rmb = explode('_', $market)[1];
        }


        $price = format_num($price, 8);

        if (!$price) {
            $this->error(L('Transaction price error') . $price);
        }


        $user_coin = M('UserCoin')->where(array('userid' => $uid))->find();

        if ($type == 1) {
            $trade_fee = discount(C('market')[$market]['fee_buy'],$uid,$user['usertype']);

            if (isset($trade_fee) && $trade_fee>0) {
                $fee = round((($num * $price) / 100) * $trade_fee, 8);
                $mum = round((($num * $price) / 100) * (100 + $trade_fee), 8);
            } else {
                $fee = 0;
                $mum = round($num * $price, 8);
            }

            if ($user_coin[$rmb] < $mum) {
                $this->error(L('INSUFFICIENT') . C('coin')[$rmb]['title']);
            }
        } else if ($type == 2) {
            $trade_fee = discount(C('market')[$market]['fee_sell'],$uid,$user['usertype']);

            if ($trade_fee) {
                $fee = round((($num * $price) / 100) * $trade_fee, 8);
                $mum = round((($num * $price) / 100) * (100 - $trade_fee), 8);
            } else {
                $fee = 0;
                $mum = round($num * $price, 8);
            }

            if ($user_coin[$xnb] < $num) {
                $this->error(L('INSUFFICIENT') . C('coin')[$xnb]['title']);
            }
        } else {
            $this->error(L('Transaction type error'));
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

		$user_coin=$mo->table('codono_user_coin')->where(array('userid' => $uid))->field(array($rmb,$rmb.'d',$xnb,$xnb.'d'))->find();
        if ($type == 1) {
            if ($user_coin[$rmb] < $mum) {
                $this->error(L('INSUFFICIENT') . C('coin')[$rmb]['title']);
            }

            $finance = $mo->table('codono_finance')->where(array('userid' => $uid))->order('id desc')->find();
            $finance_num_user_coin = $user_coin;
            $rs[] = $mo->table('codono_user_coin')->where(array('userid' => $uid))->setDec($rmb, $mum);
            $rs[] = $mo->table('codono_user_coin')->where(array('userid' => $uid))->setInc($rmb . 'd', $mum);
            $rs[] = $finance_nameid = $mo->table('codono_trade')->add(array('userid' => $uid, 'market' => $market, 'price' => $price, 'num' => $num, 'mum' => $mum, 'fee' => $fee, 'type' => 1, 'addtime' => time(), 'status' => 0));


        } else if ($type == 2) {
            if ($user_coin[$xnb] < $num) {
                $this->error(C('coin')[$xnb]['title'] . 'Insufficient balance2!');
            }

            $rs[] = $mo->table('codono_user_coin')->where(array('userid' => $uid))->setDec($xnb, $num);
            $rs[] = $mo->table('codono_user_coin')->where(array('userid' => $uid))->setInc($xnb . 'd', $num);
            $rs[] = $mo->table('codono_trade')->add(array('userid' => $uid, 'market' => $market, 'price' => $price, 'num' => $num, 'mum' => $mum, 'fee' => $fee, 'type' => 2, 'addtime' => time(), 'status' => 0));
        } else {
            $mo->rollback();
            
            $this->error(L('Transaction type error'));
        }
        if (check_arr($rs)) {
            $mo->commit();
            
            //$this->success(L('Trading success!'));
			return true;
	
        } else {
            $mo->rollback();
            
            //$this->error(L('transaction failed!'));
			return false;

        }
    }
	 private function cancel($id = NULL)
    {
        if (!check($id, 'd')) {
            return array('0', 'Parameter error');
        }

        $trade = M('Trade')->where(array('id' => $id))->find();

        if (!$trade) {
            return array('0', 'Order does not exist');
        }

        if ($trade['status'] != 0) {
            return array('0', 'Orders can not be undone');
        }

        $xnb = explode('_', $trade['market'])[0];
        $rmb = explode('_', $trade['market'])[1];

        if (!$xnb) {
            return array('0', 'Sell market error');
        }

        if (!$rmb) {
            return array('0', 'Buy market error');
        }

        $fee_buy = C('market')[$trade['market']]['fee_buy'];
        $fee_sell = C('market')[$trade['market']]['fee_sell'];

        if ($fee_buy < 0) {
            return array('0', 'BUY fee error');
        }

        if ($fee_sell < 0) {
            return array('0', 'Error handling sell');
        }
		$market=$trade['market'];
        $user_coin = M('UserCoin')->where(array('userid' => $trade['userid']))->find();
        $mo = M();
        $mo->startTrans();
        $rs = [];
        $user_coin = $mo->table('codono_user_coin')->where(array('userid' => $trade['userid']))->find();

        if ($trade['type'] == 1) {
            $mun = format_num(bcmul(bcdiv(bcmul(bcsub($trade['num'] , $trade['deal'],8) , $trade['price'],8) , 100,8) , bcadd(100 , $fee_buy,8), 8),8);
            $user_buy = $mo->table('codono_user_coin')->where(array('userid' => $trade['userid']))->find();

            if ($mun <= format_num($user_buy[$rmb . 'd'], 8)) {
                $save_buy_rmb = $mun;
            } else if ($mun <= bcadd($user_buy[$rmb . 'd'] , 1,8)) {
                $save_buy_rmb = $user_buy[$rmb . 'd'];
            } else {
                $mo->rollback();
                
                M('Trade')->where(array('id' => $id))->setField('status', 2);
                $mo->commit();
                return array('0', 'Undo failed1');
            }

            $finance = $mo->table('codono_finance')->where(array('userid' => $trade['userid']))->order('id desc')->find();
            $finance_num_user_coin = $mo->table('codono_user_coin')->where(array('userid' => $trade['userid']))->find();
            $rs[] = $mo->table('codono_user_coin')->where(array('userid' => $trade['userid']))->setInc($rmb, $save_buy_rmb);
            $rs[] = $mo->table('codono_user_coin')->where(array('userid' => $trade['userid']))->setDec($rmb . 'd', $save_buy_rmb);
            $finance_nameid = $trade['id'];

            $finance_mum_user_coin = $mo->table('codono_user_coin')->where(array('userid' => $trade['userid']))->find();
            $finance_hash = md5($trade['userid'] . $finance_num_user_coin[$rmb] . $finance_num_user_coin[$rmb.'d'] . $save_buy_rmb . $finance_mum_user_coin[$rmb] . $finance_mum_user_coin[$rmb.'d'] . CODONOLIC . 'auth.codono.com');
            $finance_num = $finance_num_user_coin[$rmb] + $finance_num_user_coin[$rmb.'d'];

            if ($finance['mum'] < $finance_num) {
                $finance_status = (1 < ($finance_num - $finance['mum']) ? 0 : 1);
            } else {
                $finance_status = (1 < ($finance['mum'] - $finance_num) ? 0 : 1);
            }

            $rs[] = $mo->table('codono_finance')->add(array('userid' => $trade['userid'], 'coinname' => $rmb, 'num_a' => $finance_num_user_coin[$rmb], 'num_b' => $finance_num_user_coin[$rmb.'d'], 'num' => $finance_num_user_coin[$rmb] + $finance_num_user_coin[$rmb.'d'], 'fee' => $save_buy_rmb, 'type' => 1, 'name' => 'trade', 'nameid' => $finance_nameid, 'remark' => 'Transaction Reversal ' . $trade['market'], 'mum_a' => $finance_mum_user_coin[$rmb], 'mum_b' => $finance_mum_user_coin[$rmb.'d'], 'mum' => $finance_mum_user_coin[$rmb] + $finance_mum_user_coin[$rmb.'d'], 'move' => $finance_hash, 'addtime' => time(), 'status' => $finance_status));
            $rs[] = $mo->table('codono_trade')->where(array('id' => $trade['id']))->setField('status', 2);
            $you_buy = $mo->table('codono_trade')->where(array(
                'market' => array('like', '%' . $rmb . '%'),
                'status' => 0,
                'userid' => $trade['userid']
            ))->find();

            if (!$you_buy) {
                $you_user_buy = $mo->table('codono_user_coin')->where(array('userid' => $trade['userid']))->find();

                if (0 < $you_user_buy[$rmb . 'd']) {
                    $rs[] = $mo->table('codono_user_coin')->where(array('userid' => $trade['userid']))->setField($rmb . 'd', 0);
                }
            }
        } else if ($trade['type'] == 2) {
            $mun = round($trade['num'] - $trade['deal'], 8);
            $user_sell = $mo->table('codono_user_coin')->where(array('userid' => $trade['userid']))->find();

            if ($mun <= round($user_sell[$xnb . 'd'], 8)) {
                $save_sell_xnb = $mun;
            } else if ($mun <= round($user_sell[$xnb . 'd'], 8) + 1) {
                $save_sell_xnb = $user_sell[$xnb . 'd'];
            } else {
                $mo->rollback();
                M('Trade')->where(array('id' => $trade['id']))->setField('status', 2);
                $mo->commit();
                return array('0', 'Undo failed2');
            }

            if (0 < $save_sell_xnb) {
                $rs[] = $mo->table('codono_user_coin')->where(array('userid' => $trade['userid']))->setInc($xnb, $save_sell_xnb);
                $rs[] = $mo->table('codono_user_coin')->where(array('userid' => $trade['userid']))->setDec($xnb . 'd', $save_sell_xnb);
            }

            $rs[] = $mo->table('codono_trade')->where(array('id' => $trade['id']))->setField('status', 2);
            $you_sell_where = array(
                'market' => array('like', $xnb . '%'),
                'status' => 0,
                'userid' => $trade['userid']
            );
            $you_sell = $mo->table('codono_trade')->where($you_sell_where)->find();

            if (!$you_sell) {
                $you_user_sell = $mo->table('codono_user_coin')->where(array('userid' => $trade['userid']))->find();

                if (0 < $you_user_sell[$xnb . 'd']) {
                    $mo->table('codono_user_coin')->where(array('userid' => $trade['userid']))->setField($xnb . 'd', 0);
                }
            }
        } else {
            $mo->rollback();
            return array('0', 'Undo failed3');
        }

        if (check_arr($rs)) {
			S('getDepth', null);
            S('getActiveDepth' . $market, null);
            S('getActiveDepth', null);
            S('getDepthNew', null);

            $mo->commit();
            
            return array('1', 'Order has been canceled');
        } else {
            $mo->rollback();
            
            return array('0', 'Undo failed4|' . implode('|', $rs));
        }
    }
	/*
	This function when called will create one trade for provided type buy or sell then creates similar trade for opposite type sell/buy after provided
	delay in microseconds
	*/
	public function simulateTrade()
    {
		$input=I('post.');
		$this->auth($input);
		$form = $input;
		   
        $market = strtolower($form['market']);
        if(!C('market')[$market]['trade']){
			$this->error(strtoupper($market).' '.L('trading is currently disabled!'));
		}
		$price = round($form['price'], C('market')[$market]['round']);
        $num = round($form['num'], 6);
        $type = $form['type'];
		
		$delay = $form['delay'];

        $uid = 0;
		

        if (!check($price, 'double')) {
            $this->error(L('Invalid price'));
        }

        if (!check($num, 'double')) {
            $this->error(L('Invalid quantity') . $num);
        }

        if (($type != 1) && ($type != 2)) {
            $this->error(L('Invalid type'));
        }


        if (!C('market')[$market]) {
            $this->error(L('Error market'));
        } else {
            $xnb = explode('_', $market)[0];
            $rmb = explode('_', $market)[1];
        }

        // TODO: SEPARATE

        $price = round(floatval($price), C('market')[$market]['round']);

        if (!$price) {
            $this->error(L('Incorrect price') . $price);
        }

        $num = round($num, 8);

        if (!check($num, 'double')) {
            $this->error(L('Incorrect Num'));
        }

        if ($type == 1) {
            $trade_fee = C('market')[$market]['fee_buy'];

            if ($trade_fee) {
                $fee = round((($num * $price) / 100) * $trade_fee, 8);
                $mum = round((($num * $price) / 100) * (100 + $trade_fee), 8);
            } else {
                $fee = 0;
                $mum = round($num * $price, 8);
            }

        } else if ($type == 2) {
            $trade_fee = C('market')[$market]['fee_sell'];

            if ($trade_fee) {
                $fee = round((($num * $price) / 100) * $trade_fee, 8);
                $mum = round((($num * $price) / 100) * (100 - $trade_fee), 8);
            } else {
                $fee = 0;
                $mum = round($num * $price, 8);
            }

        } else {
            $this->error(L('Transaction type error'));
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
        

        if ($type == 1) {
            

            $rs[] = $buy_order = $mo->table('codono_trade')->add(array('userid' => $uid, 'market' => $market, 'price' => $price, 'num' => $num, 'mum' => $mum, 'fee' => $fee, 'type' => 1, 'addtime' => time(), 'status' => 0));
			//usleep($delay);
			
			$rs[] = $sell_order = $mo->table('codono_trade')->add(array('userid' => $uid, 'market' => $market, 'price' => $price, 'num' => $num, 'mum' => $mum, 'fee' => $fee, 'type' => 2, 'addtime' => time(), 'status' => 0));
			

        } else if ($type == 2) {
         
            $rs[] = $mo->table('codono_trade')->add(array('userid' => $uid, 'market' => $market, 'price' => $price, 'num' => $num, 'mum' => $mum, 'fee' => $fee, 'type' => 2, 'addtime' => time(), 'status' => 0));
			usleep($delay);
			$rs[] = $mo->table('codono_trade')->add(array('userid' => $uid, 'market' => $market, 'price' => $price, 'num' => $num, 'mum' => $mum, 'fee' => $fee, 'type' => 1, 'addtime' => time(), 'status' => 0));
        } else {
            $mo->rollback();
            
            $this->error(L('Transaction type error'));
			$send_data['status'] = 0;
         $send_data['data'] = $form;
		 header('Content-type: application/json');
        echo(json_encode($send_data));
        exit;
        }
		
        if (check_arr($rs)) {
            $mo->commit();
            
            
			S('getDepth', null);
            S('getActiveDepth', null);
            S('getActiveDepth'.$market, null);
            S('getDepthNew', null);
			exec(PHP_PATH." index.php /Home/Trade/matchingTrade/market/$market"); 
            //$this->success(L('Trading success!'));
			$send_data['status'] = 1;
         $send_data['data'] = $form;
		 header('Content-type: application/json');
        echo(json_encode($send_data));
        exit;
        } else {
            $mo->rollback();
            
            //$this->error(L('transaction failed!'));
		$send_data['status'] = 0;
         $send_data['data'] = $form;
		 header('Content-type: application/json');
        echo(json_encode($send_data));
        exit;
        }
    }
    /*
    MY_ID
CODONO_TXID (where you will start looking for next rades)
Datetime (in unix(

Response

MY_ID
CODONO_TXID (exapmle : 1,2,3,4...)
userid —-> The user that executes the order ? (TAKER??)
  peerid —-> The user that serves the order on the other side? (MAKER??)
  market —-> OK
add-> coin1_id
add-> coin2_id
  price —-> OK
  num —-> The amount of the 1st coin?
  mum —-> The amount of 2nd coin?
  fee_buy —-> fee for buy (is this applicable to the TAKER ONLY??)
  fee_sell —-> fee for sell (is this applicable to the TAKER ONLY??)
  type —-> ?????
  addtime —-> ?????
  endtime  —-> ?????
  status  —-> ?????
    */
    public function tradelog()
    {
        $input=I('post.');
		$this->auth($input);
		$form = $input;

        
        $MY_ID = $form['MY_ID'];
		$searchid= $form['CODONO_TXID'];
        $Datetime= $form['Datetime'];
        $limit = 10;
        if (!check($searchid, 'integer') ) {
            $this->error('CODONO_TXID should be integer');
        }
        if (!check($limit, 'integer') || $limit > 200) {
            $this->error(L('limit should be 1-200'));
        }
        $where['id'] = array('egt', $searchid);
        $where['status'] = 1;
        $tradeLog = M('TradeLog')->where($where)->order('id desc')->limit($limit)->select();

			    foreach ($tradeLog as $k => $v) {

                     $coin1_name = explode('_', $v['market'])[0];
					 $coin2_name = explode('_', $v['market'])[1];
					 $coin1_id=C('coin')[$coin1_name]['id'];
					 $coin2_id=C('coin')[$coin2_name]['id'];
                    $LogRecord[$k]['MY_ID'] = $MY_ID;
					$LogRecord[$k]['CODONO_TXID'] = $searchid;
                    $LogRecord[$k]['Datetime']=$Datetime;
					$LogRecord[$k]['CODONO_id'] = $v['id'];
					$LogRecord[$k]['userid'] = $v['userid'];
					$LogRecord[$k]['peerid'] = $v['peerid'];
					$LogRecord[$k]['market'] = $v['market'];
					$LogRecord[$k]['coin1_id'] = $coin1_id;
					$LogRecord[$k]['coin2_id'] = $coin2_id;
					
                    $LogRecord[$k]['price'] = format_num($v['price'] * 1, 8);
                    $LogRecord[$k]['num'] = format_num($v['num'], 8);
                    $LogRecord[$k]['mum'] = format_num($v['mum'], 8);
                    $LogRecord[$k]['fee_buy'] = $v['fee_buy'];
					$LogRecord[$k]['fee_sell'] = $v['fee_sell'];
					$LogRecord[$k]['type'] = $v['type'];
                    $LogRecord[$k]['addtime'] = $v['addtime'];
					$LogRecord[$k]['status'] = $v['status'];
                }

		 $send_data['status'] = 1;
         $send_data['data'] = $LogRecord;
		 header('Content-type: application/json');
        echo(json_encode($send_data));
        exit;
    }

    /*
    user_id
username
password (inmd5)
paypassword(inmd5)
truename [fullname]
email
idcardauth [if value =1 means kyc completed]
last_update (datetime in linux)

and you will respond

request_status (1 success, 0 error)
CODONO_action (insert, update - depending on what action you made)
user_id
CODONO_USER_APIKEY
CODONNO_last_update (datetime in linux)
    */
    public function user()
    {
        $input=I('post.');
		$this->auth($input);
		$form = $input;
		
        $userid = $form['user_id'];
        $username = $form['username'];
        $password = $form['password'];
        $paypassword = $form['paypassword'] ? $form['paypassword'] : $form['password'];
        $truename = $form['truename'];
        $idcardauth = $form['idcardauth'];
        $endtime = $form['last_update'];
        $email = $form['email'];

        $update_array = [];
        if (M('User')->where(array('id' => $userid))->find()) {
            $action = "update";
            if ($username) {
                $update_array['username'] = $username;
            }
            if ($email) {
                $update_array['email'] = $email;
            }
            if ($idcardauth == 0 || $idcardauth == 1) {
                $update_array['idcardauth'] = $idcardauth;
            }
            if ($password) {
                $update_array['password'] = $password;
            }
            if ($paypassword) {
                $update_array['paypassword'] = $paypassword;
            }
            if ($truename) {
                $update_array['truename'] = $truename;
            }
            if ($endtime) {
                $update_array['endtime'] = $endtime;
            }

        } else {
            $action = "add";
            if (!isset($username)) {
                $this->error('Username needed');
            }
            if (!isset($password)) {
                $this->error('Password needed');
            }
            if (!isset($paypassword)) {
                $this->error('FundPwd needed');
            }
            if (!isset($truename)) {
                $this->error('Truename needed');
            }
            if (!isset($email)) {
                $this->error('Email needed');
            }

            if ($idcardauth != 0 && $idcardauth != 1) {
                $this->error('idcardauth should be 1 or 0 = KYC verified');
            }

        }


        if ($action == "add") {
            $invit_1 = 0;
            $invit_2 = 0;
            $invit_3 = 0;
            $token = md5(md5(rand(0, 10000) . md5(time()), md5(uniqid())));
            for (; true;) {
                $tradeno = tradenoa();

                if (!M('User')->where(array('invit' => $tradeno))->find()) {
                    break;
                }
            }

            $mo = M();
            $mo->startTrans();
            $rs = [];
            $rs[] = $mo->table('codono_user')->add(array('id' => $userid, 'username' => $username, 'email' => $email, 'password' => $password, 'invit' => $tradeno, 'truename' => $truename, 'paypassword' => $paypassword, 'tpwdsetting' => 1, 'idcardauth' => $idcardauth, 'invit_1' => $invit_1, 'invit_2' => $invit_2, 'invit_3' => $invit_3, 'addtime' => time(), 'status' => 1, 'endtime' => $endtime, 'token' => $token));
            $rs[] = $mo->table('codono_user_coin')->add(array('userid' => $userid));

            if (check_arr($rs)) {
                $mo->commit();
                
//            $this->success('registration success');
                $send['status'] = 1;
                $send['action'] = $action;
                $send['last_update'] = $endtime;
                $send['token'] = $token;
                $send['data'] = $form;
                $send['msg'] = "registration success";
                echo json_encode($send);
                exit;
            } else {
                $mo->rollback();
                
                $send['status'] = 0;
                $send['action'] = $action;
                $send['last_update'] = $endtime;
                $send['token'] = $token;
                $send['data'] = $form;
                $send['msg'] = "registration failed";
                echo json_encode($send);
                exit;
                //$this->error('registration failed!');
            }
        } else {
            //do update
            $mo = M();
            $user = M('User')->where(array('id' => $userid))->find();
            if ($user['id'] != $userid) {
                $this->error('No such user found' . __LINE__);
            }
            if (!isset($user['token'])) {
                $token = md5(md5(rand(0, 10000) . md5(time()), md5(uniqid())));
                $update_array['token'] = $token;
            }
            
            $mo->startTrans();
            $rs = [];

            $rs = M('User')->where(array('id' => $userid))->save($update_array);

            if ($rs) {
                $mo->commit();
                
                $send['status'] = 1;
                $send['action'] = $action;
                $send['last_update'] = $endtime;
                $send['token'] = $user['token'];
                $send['data'] = $update_array;
                $send['msg'] = "Record updated";
                echo json_encode($send);
                exit;
            } else {
                $mo->rollback();
                
                //$this->error('No changes could be made!');

                $send['status'] = 0;
                $send['action'] = $action;
                $send['last_update'] = $endtime;
                $send['token'] = $user['token'];
                $send['data'] = $update_array;
                $send['msg'] = "No changes could be made";
                echo json_encode($send);
                exit;
            }

        }


    }

    /*
    Deposit/ Withdrawal

    member_id	Member ids will be fully compatible between excelon and codono
wallet_item_id	This is the id of the coin. Coin IDs will be fully synched between excelon and codono
coin_amount	The amount to be deposited
Transcaction_type	Deposit/Withdrawal
TXID	This is the transcation ID from Excelon
TXID_datetime	This is the transcation datetime from Excelon  (in unixtime)

*/
    public function MakeUpdate()
    {
        $input=I('post.');
		$this->auth($input);
		$form = $input;
		
        $_generate_txid = 0;
        $_timeStamp = time();
        if (!check($form['member_id'], 'd')) {
            $this->ajaxShow('Incorrect member_id', 0);
        }

        $userid = $_insert_Array['userid'] = $form['member_id'];
        $_insert_Array['adminid'] = 1;


        if (strtolower($form['Transaction_type']) == 'deposit') {
            $_insert_Array['type'] = 1;
        } else if (strtolower($form['Transaction_type']) == 'withdraw') {
            $_insert_Array['type'] = 2;
        } else {
            $this->ajaxShow('Incorrect type either enter deposit or withdraw', 0);

        }

        if (!check($form['wallet_item_id'], 'd')) {
            $this->ajaxShow('Incorrect wallet_item_id', 0);
        }


        $found = 0;
        foreach (C('coin') as $_coin) {

            if ($_coin['id'] == intval($form['wallet_item_id'])) {
                $coinInfo = $_coin;
                $found = 1;
            }
        }

        if (!$found) {
            $this->ajaxShow('Incorrect wallet_item_id', 0);
        }


        if (!check($form['coin_amount'], 'currency')) {
            $this->ajaxshow('Incorrect coin_amount!', 0);
        }

        if (!$form['TXID']) {
            $this->ajaxshow('Incorrect TXID!', 0);
        }
        if (!isset($form['TXID_datetime']) || !$this->isValidTimeStamp($form['TXID_datetime'])) {
            $this->ajaxshow('Incorrect TXID_datetime!', 0);
        }


        $coin = $_insert_Array['coin'] = $coinInfo['name'];
        $amount = $_insert_Array['amount'] = $form['coin_amount'];
        $memo = $_insert_Array['memo'] = $form['memo'];
        $_insert_Array['addtime'] = $_timeStamp;
        $_insert_Array['internal_note'] = $form['internal_note'];
        $hash = $_insert_Array['internal_hash'] = $form['TXID'];
        $txid = $form['TXID'];
        $_timeStamp = $form['TXID_datetime'];


        $if_already_hash = M('Activity')->where(array('internal_hash' => $hash))->find();
        if ($if_already_hash) {
            $this->error('Caution:Similar TXID already added');
        }
        if ($_generate_txid) {
            $txid = md5(time() . $userid . $coin . $hash . 'admin_activity' . $this->generateRandomString());
        }
        $_insert_Array['txid'] = $txid;


        if ($coin != C('coin')[$coin]['name']) {
            $this->error('No such coin:' . $coin);
        }
        $user = M('User')->where(array('id' => $userid))->find();

        if ($userid != $user['id']) {
            $this->error('No such user found');
        }
        $tos = SHORT_NAME;
        $email = $user['email'];
        $coind = $coin . 'd';
        $mo = M();
        $query = "SELECT `$coin`,`$coind` FROM `codono_user_coin` WHERE `userid` = $userid";
        $res_bal = $mo->query($query);
        $user_coin_bal = $res_bal[0];
        //Income
        if ($_insert_Array['type'] == 1) {
            $num_a = $user_coin_bal[$coin];
            $num_b = $user_coin_bal[$coin . 'd'];
            $num = bcadd($num_a, $num_b, 8);
            $mum_a = bcadd($num_a, $amount, 8);
            $mum_b = $num_b;
            $mum = bcadd($mum_a, $mum_b, 8);

            
            $mo->startTrans();
			if ($amount > 0) {
                $rs[] = $mo->table('codono_user_coin')->where(array('userid' => $userid))->setInc($coin, $amount);
            }

            $rs[] = $staff_credit = M('Activity')->add($_insert_Array);
            $rs[] = $zrid = M('myzr')->add(array('userid' => $userid, 'type' => 'admin', 'username' => SHORT_NAME, 'coinname' => $coin, 'fee' => 0, 'txid' => $txid, 'num' => $amount, 'mum' => $amount, 'addtime' => $_timeStamp, 'status' => 1, 'memo' => $memo));

            // Finance Entry
            $finance_array = array('userid' => $userid, 'coinname' => $coin, 'num_a' => $user_coin_bal[$coin], 'num_b' => $amount, 'num' => $num, 'fee' => 0, 'type' => 1, 'name' => 'Staff Credit', 'nameid' => $zrid, 'remark' => 'admin_activity', 'move' => $txid, 'addtime' => $_timeStamp, 'status' => 1, 'mum' => $mum, 'mum_a' => $mum_a, 'mum_b' => $mum_b);

            $rs[] = $mo->table('codono_finance')->add($finance_array);
            $form['update_id'] = $zrid;
            if (check_arr($rs)) {
                $this->deposit_notify($tos, $tos, $coin, $txid, $amount, $_timeStamp);
                $mo->commit();
                
                //  $this->success(L('Added!'));
                $this->ajaxShow($form,1);
            } else {
                $mo->rollback();
                $this->error(L('Sorry could not add!'));
            }


        }
        //Do spend
        if ($_insert_Array['type'] == 2) {
            if ($user_coin_bal[$coin] < $amount) {
                $this->error('User has less balance ' . $user_coin_bal[$coin] . ' < ' . $amount);

            }
            $num_a = $user_coin_bal[$coin];
            $num_b = $user_coin_bal[$coin . 'd'];
            $num = bcadd($num_a, $num_b, 8);
            $mum_a = bcsub($num_a, $amount, 8);
            $mum_b = $num_b;
            $mum = bcadd($mum_a, $mum_b, 8);

			$mo->startTrans();
            if ($amount > 0) {
                $rs[] = $mo->table('codono_user_coin')->where(array('userid' => $userid))->setDec($coin, $amount);
            }

            $rs[] = $staff_spend = M('Activity')->add($_insert_Array);


            $rs[] = $zcid = M('myzc')->add(array('userid' => $userid, 'type' => 'admin', 'username' => $tos, 'coinname' => $coin, 'fee' => 0, 'txid' => $txid, 'num' => $amount, 'mum' => $amount, 'addtime' => $_timeStamp, 'status' => 1, 'memo' => $memo));


            // Finance Entry
            $finance_array = array('userid' => $userid, 'coinname' => $coin, 'num_a' => $user_coin_bal[$coin], 'num_b' => $amount, 'num' => $num, 'fee' => 0, 'type' => 2, 'name' => 'Staff Spent', 'nameid' => $zcid, 'remark' => 'admin_activity', 'move' => $txid, 'addtime' => $_timeStamp, 'status' => 1, 'mum' => $mum, 'mum_a' => $mum_a, 'mum_b' => $mum_b);
            $rs[] = $mo->table('codono_finance')->add($finance_array);
            $form['update_id'] = $zcid;
            if (check_arr($rs)) {
                $mo->commit();
                
                //$this->success(L('Record Added!'));
                $this->ajaxShow($form,1);
            } else {
                $mo->rollback();
                $this->error(L('Sorry could not add!'));
            }


        }

    }

    private function isValidTimeStamp($timestamp)
    {
        return ((string)(int)$timestamp === $timestamp)
            && ($timestamp <= PHP_INT_MAX)
            && ($timestamp >= ~PHP_INT_MAX);
    }

    private function deposit_notify($to_email, $deposit_address, $coinname, $txid, $deposited_amount, $time)
    {
        $deposit_time = date('Y-m-d H:i', $time) . '(' . date_default_timezone_get() . ')';
        $subject = "Deposit Success Alerts " . $deposit_time;
        $content = "Hello,<br/>Your " . SHORT_NAME . " acccount has recharged " . $deposited_amount . " " . $coinname . "<br/>
		<i><small>If this activity is not your own operation, please contact us immediately. </small>";

        addnotification($to_email,$subject,$content);
    }

}//End of class
