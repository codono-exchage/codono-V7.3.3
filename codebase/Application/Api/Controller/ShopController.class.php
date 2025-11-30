<?php

namespace Api\Controller;

use Think\Page;

class ShopController extends CommonController
{
    public function __construct()
    {
        if (SHOP_ALLOWED == 0) {
            die('Unauthorized!');
        }
        parent::__construct();
    }
    public function index()
    {
        $name = I('post.name', null, 'text');
        $type = I('post.type', null, 'text');
        $deal = I('post.deal', 0, 'intval');
        $price = I('post.price', 0.00, 'float');
        $ls = I('post.ls', 0, 'intval');
        $addtime = I('post.addtime', 0, 'intval');

        $order = [];

        if ($name) {
            $where['name'] = array('like', '%' . trim($name) . '%');
        }

        $shop_type_list = D('Shop')->shop_type_list();
        $shop_type_list = $shop_type_list ?: [];
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

        $data['name'] = $name;
        $data['type'] = $type;
        $data['deal'] = $deal;
        $data['addtime'] = $addtime;
        $data['price'] = $price;
        $where['status'] = 1;
        $shop = M('Shop');
        $count = $shop->where($where)->count();
        $Page = new Page($count, $ls);
        $Page->parameter = 'name=' . $name . '&type=' . $type . '&deal=' . $deal . '&price=' . $price . '&';
        $show = $Page->show();
        $list = $shop->where($where)->order($order)->limit($Page->firstRow . ',' . $Page->listRows)->select();


        foreach ($list as $k => $v) {
            $list[$k]['buycoin'] = C('coin')[$v['buycoin']]['title'];
        }

        $data['list'] = $list;
        $data['page'] = $show;
        $this->ajaxShow($data);
    }
    public function viewItem()
    {
        $uid = $this->userid();
        $id = I('get.id', 0, 'intval');
        $Shop = M('Shop')->where(array('id' => $id))->find();

        if (!$Shop) {
            $this->error(L('Item not found!'));
        } else {

            $Shop['buycoinname'] = C('coin')[$Shop['buycoin']]['title'];
            if ($Shop['codono_awardcoin']) {
                $Shop['codono_awardcoin'] = C('coin')[$Shop['codono_awardcoin']]['title'];
            }

            $data['data'] = $Shop;
        }
        $shop_type_list = D('Shop')->shop_type_list();
        $shop_type_list = $shop_type_list ?: [];

        $data['shop_type_list'] = $shop_type_list;
        $goods_list = D('Shop')->get_goods($uid);

        $data['goods_list'] = $goods_list;
        $this->ajaxShow($data);
    }


    public function orders()
    {
        $uid = $this->userid();
        $items = I('get.items', 15, 'intval');
        $where['status'] = array('egt', 0);
        $where['userid'] = $uid;
        $ShopLog = M('ShopLog');
        $count = $ShopLog->where($where)->count();
        $Page = new Page($count, $items);
        $show = $Page->show();
        $list = $ShopLog->where($where)->order('id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();

        foreach ($list as $k => $v) {
            $list[$k]['coinname'] = C('coin')[$v['coinname']]['title'];
        }


        $data['list'] = $list;

        $this->ajaxShow($data);
    }
    public function addAddress()
    {
        $truename = I('post.truename', null, 'text');
        $cellphone = I('post.cellphone', null, 'text');
        $addr = I('post.address', null, 'text');
        $city = I('post.city', null, 'text');
        $uid = $this->userid();
        if (!check($truename, 'truename')) {
            $this->error(L('Consignee name wrong format'));
        }

        if (!check($cellphone, 'cellphone')) {
            $this->error(L('Receiver Phone malformed'));
        }

        if (!check($addr, 'mostregex')) {
            $this->error(L('Shipping address format error'));
        }

        $ShopAddr = M('UserGoods')->where(array('userid' => $uid, 'truename' => $truename, 'cellphone' => $cellphone, 'addr' => $addr, 'city' => $city))->find();

        if (!$ShopAddr) {

            $rs = M('UserGoods')->add(array('userid' => $uid, 'truename' => $truename, 'cellphone' => $cellphone, 'addr' => $addr, 'city' => $city));
        }

        if ($rs) {
            $this->success(L('Submitted successfully'));
        } else {
            $this->error(L('Check if already exists'));
        }
    }


    public function address()
    {
        $uid = $this->userid();

        $ShopAddr = M('UserGoods')->where(array('userid' => $uid))->select();
        $data['addresses'] = $ShopAddr;
        $this->ajaxShow($data);
    }


    /**
     * @param $id
     * @param $num
     * @param $paypassword
     * @param $goods
     */
    public function buyItem()
    {

        $uid = $this->userid();
        $id = I('post.id', 0, 'intval');
        $num = I('post.num', 0, 'intval');

        $goods = I('post.address_id', 0, 'intval');

        if (!check($id, 'd')) {
            $this->error(L('INCORRECT_REQ'));
        }

        if (!check($num, 'd')) {
            $this->error(L('Quantity wrong format!'));
        }




        $Shop = M('Shop')->where(array('id' => $id))->find();

        if (!check($goods, 'd') && $Shop['shipping'] == 1) {
            $this->error(L('Please add choose correct Shipping address!'));
        }

        $type = $Shop['buycoin']; //Gets Payment Types


        if (!$Shop) {
            $this->error(L('Commodity mistake!'));
        }
        if ($Shop['shipping'] == 1) {
            $my_goods = M('UserGoods')->where(array('id' => $goods, 'userid' => $uid))->find();

            if (!$my_goods) {
                $this->error(L($uid . 'Please enter correct Shipping address!' . $goods));
            }
        } else {
            $my_goods = ['truename' => username($uid), 'cellphone' => 0, 'addr' => 'NoAddressRequired'];
        }
        if (!$Shop['status']) {
            $this->error(L('Currently no product!'));
        }

        if ($Shop['num'] <= $Shop['deal']) {
            $this->error(L('Product has been sold out!'));
        }

        $shop_min = 1;
        $shop_max = 100000000;

        if ($num < $shop_min) {
            $this->error("Please buy atleast $shop_min items!");
        }
        $remaining_stock = ($Shop['num'] - $Shop['deal']);
        if ($remaining_stock < $num) {
            $this->error("Please buy between $shop_min and $remaining_stock items!");
        }

        $mum = round($Shop['price'] * $num, 8);

        $codono_awardcoinnum = $Shop['codono_awardcoinnum'];
        if ($codono_awardcoinnum > 0) {
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

        $usercoin = M('UserCoin')->where(array('userid' => $uid))->getField($type);

        if ($usercoin < $xuyao) {
            $this->error(L('Available Balance:') . C('coin')[$type]['title'] . $usercoin . ' is insufficient,Required:' . C('coin')[$type]['title'] . $xuyao);
        }

        $mo = M();

        $mo->startTrans();
        try {
            $rs = [];
            $rs[] = $mo->table('codono_user_coin')->where(array('userid' => $uid))->setDec($type, $xuyao);
            if (isset($codono_awardcointype) && $codono_awardcoinnum > 0) {
                $rs[] = $mo->table('codono_user_coin')->where(array('userid' => $uid))->setInc($codono_awardcointype, $codono_awardcoinnum);
            }
            $rs[] = $mo->table('codono_shop')->where(array('id' => $Shop['id']))->save(array(
                'deal' => array('exp', 'deal+' . $num)
            ));

            if ($Shop['num'] - $num <= 0) {
                $rs[] = $mo->table('codono_shop')->where(array('id' => $Shop['id']))->save(array('status' => 0));
            }
            $add_arr = ['userid' => $uid, 'shopid' => $Shop['id'], 'price' => $Shop['price'], 'coinname' => $type, 'xuyao' => $xuyao, 'num' => $num, 'mum' => $mum, 'addr' => $my_goods['truename'] . '|' . $my_goods['cellphone'] . '|' . $my_goods['addr'], 'addtime' => time(), 'status' => 0];
            $rs[] = $mo->table('codono_shop_log')->add($add_arr);

            if (strict_check_arr($rs)) {
                $mo->commit();

                $this->success(L('Buy success!'));
            } else {
                $mo->rollback();
                $this->error(L('Failed purchase!') . $mo->getLastSql() . json_encode($add_arr));
            }
        } catch (\Think\Exception $e) {
            $mo->rollback();
            $this->error(L('Failed purchase!'));
        }
    }   
    public function cancelOrder(){
        $id=I('post.id',0,'intval');

        $uid = $this->userid();
        if (!check($id, 'd')) {
            $this->error(L('INCORRECT_REQ'));
        }
        $id = intval($id);
        $ShopLog = M('ShopLog')->where(array('id' => $id, 'userid' => $uid))->find();
        if (!$ShopLog) {
            $this->error(L('Unauthorized access!'));
        }
        if ($ShopLog['status'] != 0) {
            $this->error(L('Order can not be cancelled!'));
        }
        $mo = M();
        $mo->startTrans();
        $rs = [];
        try {
            
            $rs[] = $mo->table('codono_user_coin')->where(array('userid' => $uid))->setInc($ShopLog['coinname'], $ShopLog['xuyao']);
            $rs[] = $mo->table('codono_shop')->where(array('id' => $ShopLog['shopid']))->setDec('deal', $ShopLog['num']);
            $rs[] = $mo->table('codono_shop_log')->where(array('id' => $id))->save(array('status' => 2));
        } catch (\Think\Exception $e) {
            $mo->rollback();
            $this->error(L('Failed to cancel!'));
        }
    }


    /**
     * @param $id
     * @param $paypassword
     */
    public function delAddress()
    {
        $uid = $this->userid();

        $id=I('get.id',0,'intval');

        if (!check($id, 'd')) {
            $this->error(L('INCORRECT_REQ'));
        }
    
        if (!M('UserGoods')->where(array('userid' => $uid, 'id' => $id))->find()) {
            $this->error(L('Unauthorized access!'));
        } else if (M('UserGoods')->where(array('userid' => $uid, 'id' => $id))->delete()) {
            $this->success(L('Successfully deleted!'));
        } else {
            $this->error(L('Failed to delete!'));
        }
    }
}
