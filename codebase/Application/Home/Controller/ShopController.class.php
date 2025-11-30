<?php

namespace Home\Controller;

use Think\Page;

class ShopController extends HomeController
{
	public function __construct()
    {
		if(SHOP_ALLOWED==0){
		die('Unauthorized!');
		}
        parent::__construct();
    }
    public function index($name = NULL, $type = NULL, $deal = NULL, $addtime = NULL, $price = NULL, $ls = 20)
    {
        if (C('shop_login')) {
            if (!userid()) {
                redirect(U('Login/login'));
            }
        }

		$order=array();
        $this->assign('prompt_text', D('Text')->get_content('game_shop'));


        if ($name) {
            $where['name'] = array('like', '%' . trim($name) . '%');
        }

        $shop_type_list = D('Shop')->shop_type_list();
		$shop_type_list=$shop_type_list?:array();
        if ($type && $shop_type_list[$type]) {
            $where['type'] = trim($type);
        }

        $this->assign('shop_type_list', $shop_type_list);

        if (empty($deal)) {
        }

        if ($deal) {
            $deal_arr = explode('_', $deal);

            if (($deal_arr[1] == 'asc') || ($deal_arr[1] == 'desc')) {
                $order['deal'] = $deal_arr[1];
            } else {
                $order['deal'] = 'desc';
            }
        }

        if (empty($addtime)) {
        }

        if ($addtime) {
            $addtime_arr = explode('_', $addtime);

            if (($addtime_arr[1] == 'asc') || ($addtime_arr[1] == 'desc')) {
                $order['addtime'] = $addtime_arr[1];
            } else {
                $order['addtime'] = 'desc';
            }
        }

        if (empty($price)) {
        }

        if ($price) {
            $price_arr = explode('_', $price);

            if (($price_arr[1] == 'asc') || ($price_arr[1] == 'desc')) {
                $order['price'] = $price_arr[1];
            } else {
                $order['price'] = 'desc';
            }
        }

        $this->assign('name', $name);
        $this->assign('type', $type);
        $this->assign('deal', $deal);
        $this->assign('addtime', $addtime);
        $this->assign('price', $price);
        $where['status'] = 1;
        $shop = M('Shop');
        $count = $shop->where($where)->count();
        $Page = new Page($count, $ls);
        $Page->parameter = 'name=' . $name . '&type=' . $type . '&deal=' . $deal . '&addtime=' . $addtime . '&price=' . $price . '&';
        $show = $Page->show();
        $list = $shop->where($where)->order($order)->limit($Page->firstRow . ',' . $Page->listRows)->select();


        foreach ($list as $k => $v) {
            $list[$k]['buycoin'] = C('coin')[$v['buycoin']]['title'];
        }

        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->display();
    }
	 public function go($view)
    {
       $uid=userid();
       $this->assign('prompt_text', D('Text')->get_content('game_shop_view'));
         //$id = intval($id);
		$parts = explode('-', $view);
		$last = array_pop($parts);
		$parts = array(implode('-', $parts), $last);
		$id = intval($parts[1]);

        $Shop = M('Shop')->where(array('id' => $id))->find();

        if (!$Shop) {
            $this->error(L('Commodity mistake!'));
        } else {

            $Shop['buycoinname'] = C('coin')[$Shop['buycoin']]['title'];
			if($Shop['codono_awardcoin']){
            $Shop['codono_awardcoin'] = C('coin')[$Shop['codono_awardcoin']]['title'];
			}

            $this->assign('data', $Shop);
            //$shop_coin_list = D('Shop')->fangshi($Shop['id']);

            //foreach ($shop_coin_list as $k => $v) {
            //$coin_list[$k]['name'] = D('Coin')->get_title($k);
            //$coin_list[$k]['price'] = Num($v);
            //}

            //$this->assign('coin_list', $coin_list);
        }
		$shop_type_list = D('Shop')->shop_type_list();
		$shop_type_list=$shop_type_list?:array();
   
        $this->assign('shop_type_list', $shop_type_list);
        $goods_list = D('Shop')->get_goods(userid());
		
        $this->assign('goods_list', $goods_list);
        $this->display();
    }
    public function view($id)
    {
        if (!userid()) {
            redirect(U('Login/login'));
        }

        $this->assign('prompt_text', D('Text')->get_content('game_shop_view'));

        if (!check($id, 'd')) {
            $this->error(L('INCORRECT_REQ'));
        }

        $id = intval($id);

        $Shop = M('Shop')->where(array('id' => $id))->find();

        if (!$Shop) {
            $this->error(L('Commodity mistake!'));
        } else {


            $Shop['buycoinname'] = C('coin')[$Shop['buycoin']]['title'];

            $Shop['codono_awardcoin'] = C('coin')[$Shop['codono_awardcoin']]['title'];


            $this->assign('data', $Shop);
            //$shop_coin_list = D('Shop')->fangshi($Shop['id']);

            //foreach ($shop_coin_list as $k => $v) {
            //$coin_list[$k]['name'] = D('Coin')->get_title($k);
            //$coin_list[$k]['price'] = Num($v);
            //}

            //$this->assign('coin_list', $coin_list);
        }

        $goods_list = D('Shop')->get_goods(userid());
        $this->assign('goods_list', $goods_list);
        $this->display();
    }

    public function log($ls = 15)
    {
        if (!userid()) {
            redirect(U('Login/login'));
        }

        $this->assign('prompt_text', D('Text')->get_content('game_shop_log'));
        $where['status'] = array('egt', 0);
        $where['userid'] = userid();
        $ShopLog = M('ShopLog');
        $count = $ShopLog->where($where)->count();
        $Page = new Page($count, $ls);
        $show = $Page->show();
        $list = $ShopLog->where($where)->order('id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();

        foreach ($list as $k => $v) {
            $list[$k]['coinname'] = C('coin')[$v['coinname']]['title'];
        }


        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->display();
    }

    public function address()
    {
        if (!userid()) {
            redirect(U('Login/login'));
        }

        $ShopAddr = M('ShopAddr')->where(array('userid' => userid()))->find();
        $this->assign('ShopAddr', $ShopAddr);
        $this->display();
    }


    /**
     * @param $id
     * @param $num
     * @param $paypassword
     * @param $goods
     */
    public function buyShop($id, $num, $paypassword, $goods=null)
    {
        if (!userid()) {
            $this->error(L('PLEASE_LOGIN'));
        }

        if (!check($id, 'd')) {
            $this->error(L('INCORRECT_REQ'));
        }

        if (!check($num, 'd')) {
            $this->error(L('Quantity wrong format!'));
        }


        if (!check($paypassword, 'password')) {
            $this->error(L('Fund Pwd format error!'));
        }

        /* 		if (!check($type, 'w')) {
                    $this->error(L('Payment wrong format!'));
                } */

        $User = $this->userinfo;//M('User')->where(array('id' => userid()))->find();

        if (!$User['paypassword']) {
            $this->error(L('Illegal Fund Pwd!'));
        }

        if (md5($paypassword) != $User['paypassword']) {
            $this->error(L('Trading password is wrong!'));
        }

        $Shop = M('Shop')->where(array('id' => $id))->find();
		
        if (!check($goods, 'd') && $Shop['shipping']==1) {
            $this->error(L('Shipping address format error!'));
        }
		
        $type = $Shop['buycoin'];//Gets Payment Types


        if (!$Shop) {
            $this->error(L('Commodity mistake!'));
        }
		if( $Shop['shipping']==1)
		{
			$my_goods = M('UserGoods')->where(array('id' => $goods))->find();

			if (!$my_goods) {
				$this->error(L('Shipping address wrong!'));
			}

			if ($my_goods['userid'] != userid()) {
				$this->error(L('Shipping address illegal!'));
			}
		}else{
			$my_goods=['truename'=>username(),'cellphone'=>0,'addr'=>'NoAddressRequired'];
		}
        if (!$Shop['status']) {
            $this->error(L('Currently no product shelves!'));
        }

        if ($Shop['num'] <= $Shop['deal']) {
            $this->error(L('The current product has been sold out!'));
        }

        $shop_min = 1;
        $shop_max = 100000000;

        if ($num < $shop_min) {
            $this->error(L('The minimum purchase amount exceeds the limit system!'));
        }

        if ($shop_max < $num) {
            $this->error(L('Quantity exceed the maximum limit system!'));
        }

        if (($Shop['num'] - $Shop['deal']) < $num) {
            $this->error(L('To buy more than the current number of the remaining amount!'));
        }
        /* 
                if ($type != 'usd') {
                    $coin_price = D('Market')->get_new_price($type . '_usd');
        
                    if (!$coin_price) {
                        $this->error(L('The current currencies error!'));
                    }
                }
                else {
                    $coin_price = 1;
                } */

        $mum = round($Shop['price'] * $num, 8);

        $codono_awardcoinnum = $Shop['codono_awardcoinnum'];
        if ($codono_awardcoinnum > 0 ) {
            $codono_awardcoinnum = $codono_awardcoinnum * $num;
        }
        $codono_awardcointype = $Shop['codono_awardcoin'];


        if (!$mum) {
            $this->error(L('The total purchase error'));
        }

        //$xuyao = round($mum / $coin_price, 8);

        $xuyao = round($mum, 8);

        if (!$xuyao) {
            $this->error(L('The total payment error'));
        }

        $usercoin = $this->usercoins[$type];//M('UserCoin')->where(array('userid' => userid()))->getField($type);

        if ($usercoin < $xuyao) {
            $this->error(L('Available Balance:') . C('coin')[$type]['title'] . $usercoin . ' is insufficient,Required:' . C('coin')[$type]['title'].$xuyao );
        }

        $mo = M();
        
        $mo->startTrans();
        $rs = array();
        $rs[] = $mo->table('codono_user_coin')->where(array('userid' => userid()))->setDec($type, $xuyao);
		if(isset($codono_awardcointype) && $codono_awardcoinnum >0) {
        $rs[] = $mo->table('codono_user_coin')->where(array('userid' => userid()))->setInc($codono_awardcointype, $codono_awardcoinnum);
		}
        $rs[] = $mo->table('codono_shop')->where(array('id' => $Shop['id']))->save(array(
            'deal' => array('exp', 'deal+' . $num),
            'num' => array('exp', 'num-' . $num)
        ));

        if ($Shop['num'] - $num <= 0) {
            $rs[] = $mo->table('codono_shop')->where(array('id' => $Shop['id']))->save(array('status' => 0));
        }

        $rs[] = $mo->table('codono_shop_log')->add(array('userid' => userid(), 'shopid' => $Shop['id'], 'price' => $Shop['price'], 'coinname' => $type, 'xuyao' => $xuyao, 'num' => $num, 'mum' => $mum, 'addr' => $my_goods['truename'] . '|' . $my_goods['cellphone'] . '|' . $my_goods['addr'], 'addtime' => time(), 'status' => 0));

        if (check_arr($rs)) {
            $mo->commit();
            
            $this->success(L('Buy success!'));
        } else {
            $mo->rollback();
            $this->error(L('Failed purchase!'));
        }
    }

    public function reward($id = NULL)
    {
        if (!check($id, 'd')) {
            $this->error(L('INCORRECT_REQ'));
        }

        $shoplog = M('ShopLog')->where(array('id' => $id))->find();

        if (!$shoplog) {
            $this->error('operation failed1!');
        }

        if ($shoplog['userid'] != userid()) {
            $this->error(L('Illegal operation!'));
        }

        $rs = M('ShopLog')->where(array('id' => $id))->save(array('status' => 1));

        if ($rs) {
            $this->success(L('SUCCESSFULLY_DONE'));
        } else {
            $this->error(L('OPERATION_FAILED'));
        }
    }

    public function setaddress($truename, $cellphone, $name)
    {
        if (!userid()) {
            redirect(U('Login/login'));
        }

        if (!check($truename, 'truename')) {
            $this->error(L('Consignee name wrong format'));
        }

        if (!check($cellphone, 'cellphone')) {
            $this->error(L('Receiver Phone malformed'));
        }

        if (!check($name, 'a')) {
            $this->error(L('Shipping address format error'));
        }

        $ShopAddr = M('ShopAddr')->where(array('userid' => userid()))->find();

        if ($ShopAddr) {
            $rs = M('ShopAddr')->where(array('userid' => userid()))->save(array('truename' => $truename, 'cellphone' => $cellphone, 'name' => $name));
        } else {
            $rs = M('ShopAddr')->add(array('userid' => userid(), 'truename' => $truename, 'cellphone' => $cellphone, 'name' => $name));
        }

        if ($rs) {
            $this->success(L('Submitted successfully'));
        } else {
            $this->error(L('Submission Failed'));
        }
    }

    public function goods()
    {
		redirect(U('User/goods'));
    }

    public function upgoods($name, $truename, $idcard, $cellphone, $addr, $paypassword)
    {
        if (!userid()) {
            //redirect(U('Login/login'));
            $this->error(L('Please login!'));
        }

        if (!check($name, 'a')) {
            $this->error(L('Note the name of the wrong format!'));
        }

        if (!check($truename, 'truename')) {
            $this->error(L('Contact Name Format error!'));
        }

        if (!check($idcard, 'idcard')) {
            $this->error(L('ID number format error!'));
        }

        if (!check($cellphone, 'cellphone')) {
            $this->error(L('Tel format error!'));
        }

        if (!check($addr, 'a')) {
            $this->error(L('Contact address malformed!'));
        }

        $user_paypassword =$this->userinfo['paypassword'];// M('User')->where(array('id' => userid()))->getField('paypassword');

        if (md5($paypassword) != $user_paypassword) {
            $this->error(L('Trading password is wrong!'));
        }

        $userGoods = M('UserGoods')->where(array('userid' => userid()))->select();

        foreach ($userGoods as $k => $v) {
            if ($v['name'] == $name) {
                $this->error(L('Please do not use the same address identity!'));
            }
        }

        if (10 <= count($userGoods)) {
            $this->error('Max 10 addresses allowed per person!');
        }

        if (M('UserGoods')->add(array('userid' => userid(), 'name' => $name, 'addr' => $addr, 'idcard' => $idcard, 'truename' => $truename, 'cellphone' => $cellphone, 'addtime' => time(), 'status' => 1))) {
            $this->success(L('ADDED_SUCCESSFULLY'));
        } else {
            $this->error(L('FAILED_TO_ADD'));
        }
    }

    /**
     * @param $id
     * @param $paypassword
     */
    public function delgoods($id, $paypassword)
    {
        if (!userid()) {
            redirect(U('Login/login'));
        }

        if (!check($paypassword, 'password')) {
            $this->error(L('Fund Pwd format error!'));
        }

        if (!check($id, 'd')) {
            $this->error(L('INCORRECT_REQ'));
        }

        $user_paypassword = $this->userinfo['paypassword'];//M('User')->where(array('id' => userid()))->getField('paypassword');

        if (md5($paypassword) != $user_paypassword) {
            $this->error(L('Trading password is wrong!'));
        }

        if (!M('UserGoods')->where(array('userid' => userid(), 'id' => $id))->find()) {
            $this->error(L('Unauthorized access!'));
        } else if (M('UserGoods')->where(array('userid' => userid(), 'id' => $id))->delete()) {
            $this->success(L('successfully deleted!'));
        } else {
            $this->error(L('failed to delete!'));
        }
    }
}