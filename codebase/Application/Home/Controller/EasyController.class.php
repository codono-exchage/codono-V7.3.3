<?php

namespace Home\Controller;

class EasyController extends HomeController
{
    public const base_coin = 'usdt';//  enter crypto for easy buy /sell
	public function __construct()
    {
        parent::__construct();
		$market=I('get.market','','htmlspecialchars');
		if($market!=''){
			$this->base_coin = $market;
		}else{
			$this->base_coin = 'usdt';
		}
	}
	public function index()
    {

		$codono_getCoreConfig = codono_getCoreConfig();
		$base_pairs_array=$codono_getCoreConfig['codono_indexcat'];
		if (!userid()) {
			$CoinList = $this->marketListLoggedOut();		
			$balance=0.00;
        }else{
			$user_balance=M('UserCoin')->where(['userid' => userid()])->find();
			$CoinList = $this->marketListLoggedIn(userid());
			$balance=$user_balance[strtolower($this->base_coin)];
		}
		$this->assign('base_pairs',$base_pairs_array);
		$this->assign('balance',$balance);
        $this->assign('base_coin',strtoupper($this->base_coin));
		$this->assign('CoinList',$CoinList);
        $this->display();

    }
	public function doTrade($coin,$amount,$type,$base_market=null){
		$uid=userid();
		if(!$uid){
			 $this->error(L('Please login first!'));
		}
		$this->V2doTrade($coin,$amount,$type,$base_market);
	}
	private function V2doTrade($coin,$amount,$type,$base_market){
        $uid=userid();
		$buy_qty=0;
		if($base_market==null){
            $base_market=$this->base_coin;
		}
        $base_market=strtoupper($base_market);
        if(!C('coin')[$base_market]['name']!=$base_market){
            $this->error('Please cross check coins selected');
        }
		$market=strtolower($coin.'_'.$base_market);
		$buy_fees = C('market')[$market]['fee_buy'];
        $user_balance=M('UserCoin')->where(['userid' => $uid])->find();
		if($type==2) {
            if (!$user_balance[strtolower($coin)] || $user_balance[strtolower($coin)] < $amount) {
                $this->error(L('Insufficient funds available'));
            }
        }
		if($type==1) {
            if (!$user_balance[strtolower($base_market)] || $user_balance[strtolower($base_market)] < $amount) {
                $this->error(L('Insufficient funds available'));
            }
        }
		
		$amount = I('amount/f');
		$coin = strtolower(I('request.coin', null, 'string'));
		$isValidCoin=$this->isValidCoin($coin);
		if($type!=1 && $type!=2){
			$this->error('Invalid Type');
		}
        if(!$coin || !$isValidCoin){
            $this->error('Invalid coin');
        }
		if (!check($amount, 'decimal')) {
               $this->error('Incorrect Amount'.$amount);
        }
		
		$orderbook=json_decode($this->orderbook($coin),true);
		
		if($type==1){
			$count=count($orderbook['depth']['sell']);
			
			if($count<1){
				$this->error('There are currently not sufficient orders');
			}
			$buyOrders=array_reverse($orderbook['depth']['sell']);
			$total=$flag=$buy_amount=$i=0;
			
			foreach($buyOrders as $bo){
				$buy_amount=bcadd($buy_amount,$bo[1],8);
				$total=bcadd($total,bcmul($bo[0],$bo[1],8),8);
				if($total>$amount){
					$flag=1;
					$buy_qty=bcmul(bcdiv(1,$bo[0],8),$amount,8);
					$price=$bo[0];
					break;
				}
				$i++;
			}
			 
			if(!$flag){
				$this->error("Maximum available to buy is ".$total);
			}
			$percent=bcsub(100,$buy_fees,8);
			$amount=bcdiv(bcmul($buy_qty,$percent,8),100,8);
			
			//$this->error($amount."=amount , price".$price);
		}
		if($type==2){
			$count=count($orderbook['depth']['sell']);
			if($count<1){
				$this->error(L("There are currently not sufficient orders"));
			}
			if($orderbook['sellvol']<$amount){
				$this->error("Maximum available to sell is ".$orderbook['sellvol']);
			}
			
			$price=end($orderbook['depth']['buy'])[0];
		}
        //action
		A('Trade')->upTrade($price, $amount, $type, 'limit', 0 , NULL, $market);
		
		$this->success('Order Placed');

	}
	private function V1doTrade($coin,$amount,$type){
        $uid=userid();
		$base=$this->base_coin;
		$market=strtolower($coin.'_'.$base);
        $user_balance=M('UserCoin')->where(['userid' => $uid])->find();
		if($type==2) {
            if (!$user_balance[strtolower($coin)] || $user_balance[strtolower($coin)] < $amount) {
                $this->error(L('Insufficient funds available'));
            }
        }
		$amount = I('amount/f');
		$coin = strtolower(I('request.coin', null, 'string'));
		$isValidCoin=$this->isValidCoin($coin);
		if($type!=1 && $type!=2){
			$this->error('Invalid Type');
		}
        if(!$coin || !$isValidCoin){
            $this->error('Invalid coin');
        }
		if (!check($amount, 'decimal')) {
               $this->error('Incorrect Amount'.$amount);
        }

		$orderbook=json_decode($this->orderbook($coin),true);

		if($type==1){
			$count=count($orderbook['depth']['sell']);
			if($count<1){
				$this->error("There are currently not sufficient orders");
			}
			if($orderbook['sellvol']<$amount){
				$this->error("Maximum available to buy is ".$orderbook['sellvol']);
			}
			$price=$orderbook['depth']['sell'][0][0];

		}
		if($type==2){
			$count=count($orderbook['depth']['buy']);
			if($count<1){
				$this->error("There are currently not sufficient orders");
			}
			if($orderbook['buyvol']<$amount){
				$this->error("Maximum available to sell is ".$orderbook['buyvol']);
			}

			$price=end($orderbook['depth']['buy'])[0];
		}

		$market=strtolower($coin.'_'.$base);
		//action
        A('Trade')->upTrade($price, $amount, $type,'limit',  0, NULL, $market );

		$this->success('Order Placed');

	}
	private function orderbook($coin){
		$base=$this->base_coin;
		$market=strtolower($coin.'_'.$base);
        return json_encode(A('Ajax')->getActiveOrders($market,1,false));
	}
	private function isValidCoin($coin): bool
    {
        $coins=C('coin_safe');
		
		if (array_key_exists(strtolower($coin),$coins))
		{
			return true;
		}
		else
		{
			return false;
		}
	}
    private function marketListLoggedIn($userid){
        if (!check($userid, 'd')) {
            $this->error(L('INCORRECT_REQ'));
        }
        $list= [];
        $user_balance=M('UserCoin')->where(['userid' => $userid])->find();
        $markets=C("market");
        foreach($markets as $market){
		
            if(strtolower($market['rmb'])==strtolower($this->base_coin)){
                $list[$market['xnb']]['name']=$market['xnb'];
                $list[$market['xnb']]['title']=$market['navtitle'];
                $list[$market['xnb']]['img']=$market['xnbimg'];
                $list[$market['xnb']]['price']=$market['new_price'];
                $list[$market['xnb']]['change']=$market['change'];
                $list[$market['xnb']]['balance']=$user_balance[$market['xnb']];
                $list[$market['xnb']]['freeze']=$user_balance[$market['xnb'].'d'];
                if($market['change']>=0){
                    $list[$market['xnb']]['color']='green';
                }
                else{$list[$market['xnb']]['color']='red';}
                if($market['new_price']<=0){
                    unset( $list[$market['xnb']]);
                }
            }
        }
        return $list;
    }
	public function marketListLoggedOut(){
        
        $markets=C("market");
        foreach($markets as $market){
			
            if(strtolower($market['rmb'])==strtolower($this->base_coin)){
                $list[$market['xnb']]['name']=$market['xnb'];
                $list[$market['xnb']]['title']=$market['navtitle'];
                $list[$market['xnb']]['img']=$market['xnbimg'];
                $list[$market['xnb']]['price']=$market['new_price'];
                $list[$market['xnb']]['change']=$market['change'];
                $list[$market['xnb']]['balance']=0;
                $list[$market['xnb']]['freeze']=0;
                if($market['change']>=0){
                    $list[$market['xnb']]['color']='green';
                }
                else{$list[$market['xnb']]['color']='red';}
                if($market['new_price']<=0){
                    unset( $list[$market['xnb']]);
                }
            }
        }
		
        return $list;
    }

    public function _empty()
    {
        send_http_status(404);
        $this->error();
        echo L('Module does not exist!');
        die();

    }
	


}