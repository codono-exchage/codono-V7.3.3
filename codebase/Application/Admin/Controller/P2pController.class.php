<?php

namespace Admin\Controller;

class P2pController extends AdminController
{
    public function index($p = 1, $r = 15, $str_addtime = '', $end_addtime = '', $order = '', $status = '', $type = '', $field = '', $name = '', $coin = '', $fiat = '')
    {
        $parameter['p'] = $p;
        $parameter['status'] = $status;
        $parameter['order'] = $order;
        $parameter['type'] = $type;
        $parameter['name'] = $name;

        $map = [];
        if (empty($order)) {
            $order = 'id_desc';
        }

        $order_arr = explode('_', $order);
        if ($status) {
            $map['status'] = $status;
        }
        if (count($order_arr) != 2) {
            $order = 'id_desc';
            $order_arr = explode('_', $order);
        }
        if ($field && $name) {
            $map[$field] = $name;
        }
        if ($coin) {
            $map['coin'] = $coin;
        }
        if ($fiat) {
            $map['fiat'] = $fiat;
        }
        if ($status) {
            $map['status'] = $status;
        }
        $order_set = $order_arr[0] . ' ' . $order_arr[1];
        $Mo = M('P2pAds');
        $data = $Mo->where($map)->order($order_set)->select();
        $count = $Mo->where($map)->order($order_set)->count();
        $builder = new BuilderList();
        $builder->title('P2p Ads:');
        $builder->titleList('P2p', U('P2p/Ads'));

        $builder->keyId();
        $builder->keyText('orderid', 'PublicID');

        $builder->keyUserid('uid', 'UID');
        $builder->keyText('coin', 'Coin');
        $builder->keyText('fiat', 'Fiat');
        $builder->keyPrice('fixed_price', 'Price');
        $builder->keyText('price_type', 'Pricetype');
        $builder->keyPrice('floating', 'Floating%');
        $builder->keyPrice('available', 'Available');
        $builder->keyPrice('freeze', 'Freeze');
        $builder->keyStatus('ad_type', 'Type', ['', 'Buy', 'Sell']);
        $builder->keyText('time_limit', 'TimeLimit');
        $builder->keyPrice('min_limit', 'Min');
        $builder->keyPrice('max_limit', 'Max');


        $builder->keyStatus('online', 'online', ['Offline', 'Online']);
        $builder->keyStatus('status', 'Status', ['Disabled', 'Enabled']);
        $coinname_arr = ['' => 'Coin'];
        $coinname_arr = array_merge($coinname_arr, D('Coin')->get_all_name_list());
        $fiat_arr = ['' => 'Fiat'];
        $fiat_arr = array_merge($fiat_arr, D('Coin')->get_all_fiat());
        $builder->search('fiat', 'select', $fiat_arr);
        $builder->search('coin', 'select', $coinname_arr);
        $builder->search('status', 'select', ['Disabled', 'Enabled']);
        $builder->search('online', 'select', ['Offline', 'Online']);
        $builder->search('field', 'select', ['uid' => 'Userid', 'merchant_id' => 'Merchantid', 'orderid' => 'PublicID']);
        $builder->search('name', 'text', 'Enter search content');
        $builder->keyDoAction('P2p/adStatusToggle?status=1&type=status&id=###', 'Enable', 'Option');
        $builder->keyDoAction('P2p/adStatusToggle?status=0&type=status&id=###', 'Disable', 'Option');
        $builder->keyDoAction('P2p/adStatusToggle?status=1&type=online&id=###', 'Online', 'Option');
        $builder->keyDoAction('P2p/adStatusToggle?status=0&type=online&id=###', 'Offline', 'Option');
        $builder->data($data);
        $builder->pagination($count, $r, $parameter);
        $builder->display();
    }

    public function Orders($p = 1, $r = 15, $str_addtime = '', $end_addtime = '', $order = '', $status = '', $type = '', $field = '', $name = '', $coin = '', $fiat = '')
    {
        $parameter['p'] = $p;
        $parameter['status'] = $status;
        $parameter['order'] = $order;
        $parameter['type'] = $type;
        $parameter['name'] = $name;

        $map = [];
        if (empty($order)) {
            $order = 'id_desc';
        }

        $order_arr = explode('_', $order);
        if ($status) {
            $map['status'] = $status;
        }
        if (count($order_arr) != 2) {
            $order = 'id_desc';
            $order_arr = explode('_', $order);
        }
        if ($field && $name) {
            $map[$field] = $name;
        }
        if ($coin) {
            $map['coin'] = $coin;
        }
        if ($fiat) {
            $map['fiat'] = $fiat;
        }
        if ($status) {
            $map['status'] = $status;
        }
        $order_set = $order_arr[0] . ' ' . $order_arr[1];
        $Mo = M('P2pOrders');
        $data = $Mo->where($map)->order($order_set)->select();
        $count = $Mo->where($map)->order($order_set)->count();
        $builder = new BuilderList();
        $builder->title('P2p Orders:');
        $builder->titleList('P2p', U('P2p/Orders'));

        $this->sub_builder($builder, $data, $count, $r, $parameter);
    }

    public function Disputes($p = 1, $r = 15, $str_addtime = '', $end_addtime = '', $order = '', $type = '', $field = '', $name = '', $coin = '', $fiat = '')
    {
        $parameter['p'] = $p;
        $parameter['status'] = 3;
        $parameter['order'] = $order;
        $parameter['type'] = $type;
        $parameter['name'] = $name;

        $map = [];
        if (empty($order)) {
            $order = 'id_desc';
        }

        $order_arr = explode('_', $order);


        if (count($order_arr) != 2) {
            $order = 'id_desc';
            $order_arr = explode('_', $order);
        }
        if ($field && $name) {
            $map[$field] = $name;
        }
        if ($coin) {
            $map['coin'] = $coin;
        }
        if ($fiat) {
            $map['fiat'] = $fiat;
        }

        $map['status'] = 3;

        $order_set = $order_arr[0] . ' ' . $order_arr[1];
        $Mo = M('P2pOrders');
        $data = $Mo->where($map)->order($order_set)->select();
        $count = $Mo->where($map)->order($order_set)->count();
        $builder = new BuilderList();
        $builder->title('P2p Disputed orders:');

        $this->sub_builder($builder, $data, $count, $r, $parameter);
    }

    /*
    orderid
    solution=refund/release
    */
    public function resolution($orderid = null, $solution = null)
    {

        if ($orderid == null) {
            $this->error("Please provide correct orderid");
        }
        if ($solution != 'refund' && $solution != 'release') {
            $this->error("Please provide correct solution release or refund");
        }

        if ($solution == 'refund') {
            return $this->refundOrder($orderid);
        }
        if ($solution == 'release') {
            return $this->releaseOrder($orderid);
        }
        return false;
    }

    public function getChat()
    {
        $id = I('request.id', 0, 'int');
        if (!$id) {
            $this->error(L("Incorrect Order id"));
        }

        $Mo = M('P2pChat');

        $data = $Mo->where(['orderid' => $id])->order('addtime inc')->select();
        $chats = [];
        foreach ($data as $_chat) {
            $_chat['timestamp'] = $this->time_elapsed_string($_chat['addtime']);
            $chats[] = $_chat;
        }

        $data = ['status' => 0, 'data' => $chats];
        exit(json_encode($data));
    }

    private function notify($userid, $subject, $message)
    {
        $email = getEmail($userid);
        addnotification($email, $subject, $message);
    }

    //seller should get the funds
    private function refundOrder($orderid)
    {
        $id = $orderid;
        if (!$id) {
            $this->error(L("Incorrect Order id"));
        }

        $ad_info = $this->orderinfo($orderid);

        $uid = $ad_info['buyer'];
        $peerid = $ad_info['seller'];

        if (!$uid) {
            $this->error(L('Invalid Buyer'));
        }


        $coin = strtolower($ad_info['coin']);
        $total = $ad_info['coin_qty'];
        $orderid = $ad_info['id'];
        $ad_id = $ad_info['ad_id'];
        $merchant_id = $ad_info['merchant_id'];
        if ($uid == $merchant_id) {
            $is_merchant = 1;
        } else {
            $is_merchant = 0;
        }


        $mo = M();
        $order = $mo->table('codono_p2p_orders')->where(['id' => $id, 'status' => 3])->find();

        if (!$order) {
            $this->error(L("No such listing found"));
        }


        //@todo if merchant and cancelled then change the rating


        //This could be seller instead buyer [ Only buyer can cancle the order]


        $mo = M();
        $mo->startTrans();


        //changes for seller
        $condition_1 = ['uid' => $uid, 'coin' => $coin];
        $condition_2 = ['uid' => $peerid, 'coin' => $coin];


        if ($is_merchant) {
            $rs[] = $mo->table('codono_user_assets')->where($condition_2)->setDec('freeze', $total);
            $rs[] = $mo->table('codono_user_assets')->where($condition_2)->setInc('balance', $total);
        }


        $rs[] = $mo->table('codono_p2p_ads')->where(['id' => $ad_id])->setDec('freeze', $total);
        $rs[] = $mo->table('codono_p2p_ads')->where(['id' => $ad_id])->setInc('available', $total);

        $up_where['id'] = $orderid;
        $up_where['coin'] = $coin;
        $up_where['status'] = 3;

        //status 2 = cancelled
        $request = ['status' => 2];
        $rs[] = $mo->table('codono_p2p_orders')->where($up_where)->save($request);

        if (check_arr($rs)) {
            $mo->commit();
            // removed unlock/lock

            $subject = "Dispute resolved:P2p order has been cancelled";

            $message = "Order " . $order['orderid'] . " has been cancelled under dispute resolution,any funds frozen will be refunded back";
            $this->notify($peerid, $subject, $message);
            $chat_array = ['orderid' => $orderid, 'content' => $message, 'userid' => 0, 'addtime' => time()];
            $mo->table('codono_p2p_chat')->where(['orderid' => $orderid])->add($chat_array);
            $this->success(L("This order has been cancelled"));
        } else {
            $mo->rollback();
            // removed unlock/lock
            $this->error('There were issues updating the order!');
        }
    }

    private function releaseOrder($orderid)
    {

        $info = $this->orderinfo($orderid);

        //$this->error(json_encode($info));
        $uid = $info['seller'];

        if ($info['seller'] == $info['merchat_id']) {
            $is_merchant = 1;
        } else {
            $is_merchant = 0;
        }
        if (!$uid) {
            $this->error(L('Incorrect Seller'));
        }

        $id = $orderid = $info['id'];

        if (!$id) {
            $this->error(L("Incorrect Order id"));
        }
        $mo = M();
        $order = $mo->table('codono_p2p_orders')->where(['id' => $id, 'status' => 3])->find();

        if (!$order) {
            $this->error(L("No such listing found"));
        }


        $peerid = $info['buyer'];

        $coin = strtolower($info['coin']);
        $total = $info['coin_qty'];
        $orderid = $info['id'];
        $ad_id = $info['ad_id'];

        $mo->startTrans();

        //@todo if merchant confirmed then change the rating 5.0


        //changes for seller
        $condition_1 = ['uid' => $uid, 'coin' => $coin];
        $found = $mo->table('codono_user_assets')->where($condition_1)->find();
        if (!$found) {
            $rs[] = $mo->table('codono_user_assets')->add($condition_1);
        }


        //changes for buyer
        $condition_2 = ['uid' => $peerid, 'coin' => $coin];
        $found = $mo->table('codono_user_assets')->where($condition_2)->find();
        if (!$found) {
            $rs[] = $mo->table('codono_user_assets')->add($condition_2);
        }
        //user is seller but not merchant thus reduce his freeze
        if (!$is_merchant && $info['ad_type'] == 2) {
            $rs[] = $mo->table('codono_user_assets')->where($condition_1)->setDec('freeze', $total);
        }
        $rs[] = $mo->table('codono_user_assets')->where($condition_2)->setInc('balance', $total);


        $rs[] = $mo->table('codono_p2p_ads')->where(['id' => $ad_id])->setDec('freeze', $total);

        $up_where['id'] = $orderid;
        //$up_where['coin'] = $coin;
        $up_where['status'] = 3;

        $request = ['status' => 4];
        $rs[] = $mo->table('codono_p2p_orders')->where($up_where)->save($request);

        if (check_arr($rs)) {
            $mo->commit();
            // removed unlock/lock
            $subject = "P2p payment has been released as per resolution";
            $message = "System has resolved dispute and released the funds to seller on order " . $order['orderid'];
            $this->notify($peerid, $subject, $message);
            $chat_array = ['orderid' => $orderid, 'content' => $message, 'userid' => 0, 'addtime' => time()];
            $data['info'] = $mo->table('codono_p2p_chat')->where(['orderid' => $orderid])->add($chat_array);
            $this->success(L("Dispute resolved , Payment has been released"));
        } else {
            $mo->rollback();
            // removed unlock/lock
            $this->error('There were issues updating the order!');
        }


    }

    private function orderinfo($id)
    {

        if (!$id) {
            return false;
        }
        $mo = M();
        $order = $mo->table('codono_p2p_orders')->where(['id' => $id])->find();
        if ($id != $order['id']) {
            return false;
        }
        $ad_info = $order;
        $ad_info['ad_type'] = $order['ad_type'];

        if ($order['ad_type'] == 1) {
            $ad_info['buyer'] = $ad_info['peerid'] = $order['userid'];
            $ad_info['seller'] = $order['merchant_id'];

        } else {
            $ad_info['buyer'] = $order['merchant_id'];
            $ad_info['seller'] = $ad_info['peerid'] = $order['userid'];
        }

        $ad_info['buyer_name'] = fullname($ad_info['buyer']);
        $ad_info['seller_name'] = fullname($ad_info['seller']);
        $ad_info['payinfo'] = json_decode($ad_info['payment_info'], true);
        return $ad_info;

    }

    public function Merchants($p = 1, $r = 15, $status = '', $field = '', $name = '', $uid = null)
    {
        $parameter['p'] = $p;
        $parameter['status'] = $status;
        $parameter['uid'] = $uid;


        $map = [];
        if (empty($order)) {
            $order = 'id_desc';
        }

        $order_arr = explode('_', $order);
        if ($status) {
            $map['status'] = $status;
        }
        if (count($order_arr) != 2) {
            $order = 'id_desc';
            $order_arr = explode('_', $order);
        }
        if ($field && $name) {
            $map[$field] = $name;
        }
        $order_set = $order_arr[0] . ' ' . $order_arr[1];
        $Mo = M('P2pMerchants');
        $data = $Mo->where($map)->order($order_set)->select();
        $count = $Mo->where($map)->order($order_set)->count();
        $builder = new BuilderList();
        $builder->title('P2p Merchants:');
        $builder->titleList('P2p', U('P2p/Merchants'));
        $builder->keyId();


        $builder->keyText('name', 'Name');

        $builder->keyText('uid', 'UserID');
        $builder->keyText('rating', 'Rating');
        $builder->keyText('orders', 'Orders');
        $builder->keyStatus('status', 'Status', ['Disabled', 'Enabled']);
        $builder->search('status', 'select', ['Disabled', 'Enabled']);
        $builder->search('name', 'text', 'Enter name');
        $builder->keyDoAction('P2p/merchantStatusToggle?status=0&id=###', 'Disable', 'Option');
        $builder->keyDoAction('P2p/merchantStatusToggle?status=1&id=###', 'Enable', 'Option');
        $builder->data($data);
        $builder->pagination($count, $r, $parameter);
        $builder->display();
    }


    public function vieworder($id)
    {
        $Mo = M('P2pChat');
        $data = $Mo->where(['orderid' => $id])->select();
        $chats = [];
        foreach ($data as $_chat) {

            $_chat['timestamp'] = $this->time_elapsed_string($_chat['addtime']);
            $chats[] = $_chat;
        }
        $orderinfo = $this->orderinfo($id);

        $merchant = $orderinfo['merchant_id'];
        $peerid = $orderinfo['peerid'];
        $this->assign('orderid', $id);
        $this->assign('orderinfo', $orderinfo);
        $this->assign('merchant', $merchant);
        $this->assign('peerid', $peerid);
        $this->assign('data', $chats);
        $this->display();

    }

    public function sendchat()
    {
        $uid = 0;
        $data = ['status' => 0, 'data' => []];

        $orderid = I('request.orderid', 0, 'int');
        $content = I('request.content', null, 'text');

        if ($orderid != 0 && $content != null) {


            $mo = M();
            $order = $mo->table('codono_p2p_orders')->where(['id' => $orderid])->find();


            $add_array = ['orderid' => $orderid, 'content' => $content, 'userid' => $uid, 'addtime' => time()];
            $data['status'] = 1;
            $data['info'] = $mo->table('codono_p2p_chat')->where(['orderid' => $orderid])->add($add_array);
        }
        exit(json_encode($data));
    }

    private function time_elapsed_string($ptime)
    {
        $etime = time() - $ptime;

        if ($etime < 1) {
            return '0 seconds';
        }

        $a = [365 * 24 * 60 * 60 => 'year',
            30 * 24 * 60 * 60 => 'month',
            24 * 60 * 60 => 'day',
            60 * 60 => 'hour',
            60 => 'minute',
            1 => 'second'
        ];
        $a_plural = ['year' => 'years',
            'month' => 'months',
            'day' => 'days',
            'hour' => 'hours',
            'minute' => 'minutes',
            'second' => 'seconds'
        ];

        foreach ($a as $secs => $str) {
            $d = $etime / $secs;
            if ($d >= 1) {
                $r = round($d);
                return $r . ' ' . ($r > 1 ? $a_plural[$str] : $str) . ' ago';
            }
        }
    }

    public function deleteInvest($id = NULL)
    {
        $where['id'] = $id;
        if (M('Investbox')->where($where)->delete()) {
            $this->success(L('SUCCESSFULLY_DONE'));
        } else {
            $this->error('No changes were made.');
        }
    }

    public function merchantStatusToggle($status = 0, $id = 0)
    {
        $where['id'] = $id;
        $Mo = M('P2pMerchants');
        $data = $Mo->where($where)->find();
        if ($status != 0) {
            $status = 1;
        }
        if ($data) {
            $uid = $data['uid'];
            $Mo->where($where)->save(['status' => $status]);
            M('User')->where(['id' => $uid])->save(['is_merchant' => $status]);
            $this->success(L('SUCCESSFULLY_DONE'));
        } else {
            $this->error('No records found!');
        }
    }

    public function adStatusToggle($status = 0, $id = 0, $type = 'status')
    {
        $where['id'] = $id;
        $Mo = M('P2pAds');
        $data = $Mo->where($where)->find();
        if ($status != 0) {
            $status = 1;
        }
        if ($type != 'status') {
            $type = 'online';
        }
        if ($data) {
            $uid = $data['uid'];
            $Mo->where($where)->save([$type => $status]);
            $this->success(L('SUCCESSFULLY_DONE'));
        } else {
            $this->error('No records found!');
        }
    }

    /**
     * @param BuilderList $builder
     * @param $data
     * @param $count
     * @param $r
     * @param array $parameter
     * @return void
     */
    private function sub_builder(BuilderList $builder, $data, $count, $r, array $parameter): void
    {
        $builder->keyId();
        $builder->keyText('orderid', 'PublicID');
        $builder->keyText('ad_id', 'AdID');
        $builder->keyText('name', 'Name');

        $builder->keyUserid('userid', 'User');
        $builder->keyUserid('merchant_id', 'Merchant');
        $builder->keyText('coin', 'Coin');
        $builder->keyText('fiat', 'Fiat');
        $builder->keyPrice('fixed_price', 'Price');
        $builder->keyPrice('coin_qty', 'CoinQty');
        $builder->keyPrice('fiat_qty', 'FiatQty');

        $builder->keyStatus('ad_type', 'AdType', ['', 'Buy', 'Sell']);
        $builder->keyTime('addtime', 'OrderTime');
        $builder->keyTime('endtime', 'Ends');
        $builder->keyTime('paidtime', 'Paid');
        $builder->keyStatus('status', 'Status', ['Pending', 'Paid', 'Cancelled', 'Disputed', 'Completed']);
        $coinname_arr = ['' => 'Coin'];
        $coinname_arr = array_merge($coinname_arr, D('Coin')->get_all_name_list());
        $fiat_arr = ['' => 'Fiat'];
        $fiat_arr = array_merge($fiat_arr, D('Coin')->get_all_fiat());
        $builder->search('fiat', 'select', $fiat_arr);
        $builder->search('coin', 'select', $coinname_arr);
        $builder->search('field', 'select', ['uid' => 'uid']);
        $builder->search('status', 'select', ['Pending', 'Paid', 'Cancelled', 'Disputed', 'Completed']);
        $builder->search('name', 'text', 'Enter search content');
        $builder->keyDoAction('P2p/vieworder?id=###', 'View', 'Option');
        $builder->data($data);
        $builder->pagination($count, $r, $parameter);
        $builder->display();
    }

}