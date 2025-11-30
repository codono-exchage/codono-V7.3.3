<?php

namespace Admin\Controller;

class ShopController extends AdminController
{
    public function status($id, $status, $model)
    {
        $builder = new BuilderList();
        $builder->doSetStatus($model, $id, $status);
    }

    public function config($id = NULL)
    {

        if (!empty($_POST)) {
            if (APP_DEMO) {
                $this->error(L('SYSTEM_IN_DEMO_MODE'));
            }
            if (M('Config')->where(array('id' => 1))->save($_POST)) {
                $this->success(L('SUCCESS'));
            } else {
                $this->error(L('OPERATION_FAILED'));
            }
        } else {
            $data['shop_login'] = C('shop_login');
            $data['shop_logo'] = C('shop_logo');
            $data['shop_coin'] = C('shop_coin');
            $builder = new BuilderEdit();
            $builder->title('Store Config');
            $builder->keySelect('shop_login', 'Login', 'Do you want to login to access the store', array('Not Required', 'Login Required'));
            $builder->keyImage('shop_logo', 'Store Logo', 'Store Logo', array('width' => 240, 'height' => 40, 'savePath' => 'shop', 'url' => U('Shop/images')));
            $builder->data($data);
            $builder->savePostUrl(U('Shop/config'));
            $builder->display();
        }
    }

    public function index($p = 1, $r = 15, $str_addtime = '', $end_addtime = '', $order = '', $status = '', $type = '', $field = '', $name = '')
    {
        $Type_arr = D('Shop')->shop_type_list();
        $Type_arr[0] = 'All';
        $map = array();

        if ($str_addtime && $end_addtime) {
            $str_addtime = strtotime($str_addtime);
            $end_addtime = strtotime($end_addtime);

            if ((addtime($str_addtime) != '---') && (addtime($end_addtime) != '---')) {
                $map['addtime'] = array(
                    array('egt', $str_addtime),
                    array('elt', $end_addtime)
                );
            }
        }

        if (empty($order)) {
            $order = 'id_desc';
        }

        $order_arr = explode('_', $order);

        if (count($order_arr) != 2) {
            $order = 'id_desc';
            $order_arr = explode('_', $order);
        }

        $order_set = $order_arr[0] . ' ' . $order_arr[1];

        if (empty($status)) {
            $map['status'] = array('egt', 0);
        }

        if (($status == 1) || ($status == 2) || ($status == 3)) {
            $map['status'] = $status - 1;
        }

        if ($type && $Type_arr[$type]) {
            $map['type'] = $type;
        }

        if ($field && $name) {
            if ($field == 'name') {
                $map['name'] = array('like', '%' . $name . '%');
            } else {
                $map[$field] = $name;
            }
        }

        $data = M('Shop')->where($map)->order($order_set)->page($p, $r)->select();
        $count = M('Shop')->where($map)->count();
        $parameter['p'] = $p;
        $parameter['status'] = $status;
        $parameter['order'] = $order;
        $parameter['type'] = $type;
        $parameter['name'] = $name;
        $builder = new BuilderList();
        $builder->title('Products');
        $builder->titleList('Product List', U('Shop/index'));
        $builder->button('add', 'Add', U('Shop/edit'));
        $builder->button('resume', 'Enable', U('Shop/status', array('model' => 'Shop', 'status' => 1)));
        $builder->button('forbid', 'Disable', U('Shop/status', array('model' => 'Shop', 'status' => 0)));
        $builder->button('delete', 'Delete', U('Shop/status', array('model' => 'Shop', 'status' => -1)));
        $builder->setSearchPostUrl(U('Shop/index'));
        $builder->search('order', 'select', array('id_desc' => 'ID desc', 'id_asc' => 'ID asc'));
        $builder->search('status', 'select', array('All Status', 'Disabled', 'Enabled'));
        $builder->search('type', 'select', $Type_arr);
        $builder->search('field', 'select', array('name' => 'Product Name'));
        $builder->search('name', 'text', 'Enter search content');
        $builder->keyId();
        $builder->keyText('name', 'Product Name');
        $builder->keyType('type', 'Category', $Type_arr);

        $coin_list = D('Coin')->get_all_name_list();

        $builder->keyType('buycoin', 'Currency', $coin_list);

        $builder->keyPrice('price', 'Offer price');
        $builder->keyText('market_price', 'Price');
        $builder->keyText('num', 'Stock');
        $builder->keyText('deal', 'Sales');
        
        $builder->keyTime('addtime', 'Add time');
        $builder->keyStatus('status', 'Status', array('Disabled', 'Enabled'));
        $builder->keyDoAction('Shop/edit?id=###', 'Edit', 'Option');
        $builder->data($data);
        $builder->pagination($count, $r, $parameter);
        $builder->display();
    }

    public function edit($id = NULL)
    {
        if (!empty($_POST)) {

            if (APP_DEMO) {
                $this->error(L('SYSTEM_IN_DEMO_MODE'));
            }

            if (!check($_POST['name'], 'a')) {
            }

            if (!check($_POST['type'], 'w')) {
                $this->error('Product Types malformed');
            }

            if (!check($_POST['price'], 'usd')) {
                $this->error('Commodity prices malformed');
            }

            if (!check($_POST['market_price'], 'usd')) {
                $this->error('Price malformed');
            }

            if (!check($_POST['num'], 'd')) {
                $this->error('Stock malformed');
            }

            if ($_POST['deal'] && !check($_POST['deal'], 'd')) {
                $this->error('Total sales malformed');
            }

            if (!check($_POST['sort'], 'd')) {
                $this->error('Sort malformed');
            }

            if ($_POST['addtime']) {
                if (addtime(strtotime($_POST['addtime'])) == '---') {
                    $this->error('Added malformed');
                } else {
                    $_POST['addtime'] = strtotime($_POST['addtime']);
                }
            } else {
                $_POST['addtime'] = time();
            }

            if ($_POST['endtime']) {
                if (addtime(strtotime($_POST['endtime'])) == '---') {
                    $this->error('Edit the time format error');
                } else {
                    $_POST['endtime'] = strtotime($_POST['endtime']);
                }
            } else {
                $_POST['endtime'] = time();
            }

            if (check($_POST['id'], 'd')) {
                $rs = M('Shop')->save($_POST);
            } else {
                $rs = M('Shop')->add($_POST);
            }

            if ($rs) {
                $this->success('Successful operation');
            } else {
                $this->error('operation failed');
            }
        } else {
            $builder = new BuilderEdit();
            $builder->title('Products');
            $builder->titleList('Product List', U('Shop/index'));

            if ($id) {
                $builder->keyReadOnly('id', 'ID');
                $builder->keyHidden('id', 'Product ID');
                $data = M('Shop')->where(array('id' => $id))->find();
                $data['addtime'] = addtime($data['addtime']);
                $data['endtime'] = addtime($data['endtime']);
                $builder->data($data);
            }

            $builder->keyText('name', 'Product Name', 'Product Name');
            $builder->keySelect('type', 'Product Category', 'Product Category', D('Shop')->shop_type_list());
            $builder->keyImage('img', 'Product Image', 'Product image', array('width' => 408, 'height' => 300, 'savePath' => 'shop', 'url' => U('Shop/images')));

            $coin_list = D('Coin')->get_all_name_list();

            $builder->keySelect('buycoin', 'Payment Currency', 'Payment Currency', $coin_list);
            $builder->keyText('market_price', 'Market Retail price', 'decimal 2 points');
            $builder->keyText('price', 'Discounted price', 'decimal 2 points');

            $builder->keyText('codono_awardcoinnum', 'Reward currency', 'Integer,Empty or fill 0 No reward');
            $builder->keySelect('codono_awardcoin', 'Reward currency', 'Reward currency', $coin_list);


            $builder->keyText('num', 'in stock', 'Integer');
            $builder->keyText('deal', 'Sales', 'Integer');
            $builder->keyText('sort', 'Sort', 'Integer');
            //$builder->keyEditor('content', 'Product desciption', U('Shop/images'));
            $builder->keyEditor('content', 'Product desciption', '');
            $builder->keyAddTime();
            $builder->keyEndTime();
            $builder->keyStatus();
			$builder->keySelect('shipping', 'Shipping Available', 'Shipping Available', [0=>'No',1=>'Yes' ]);
            $builder->savePostUrl(U('Shop/edit'));
            $builder->display();
        }
    }

    public function type($p = 1, $r = 15, $str_addtime = '', $end_addtime = '', $order = '', $status = '', $type = '', $field = '', $name = '')
    {
        $map = array();

        if ($str_addtime && $end_addtime) {
            $str_addtime = strtotime($str_addtime);
            $end_addtime = strtotime($end_addtime);

            if ((addtime($str_addtime) != '---') && (addtime($end_addtime) != '---')) {
                $map['addtime'] = array(
                    array('egt', $str_addtime),
                    array('elt', $end_addtime)
                );
            }
        }

        if (empty($order)) {
            $order = 'id_desc';
        }

        $order_arr = explode('_', $order);

        if (count($order_arr) != 2) {
            $order = 'id_desc';
            $order_arr = explode('_', $order);
        }

        $order_set = $order_arr[0] . ' ' . $order_arr[1];

        if (empty($status)) {
            $map['status'] = array('egt', 0);
        }

        if (($status == 1) || ($status == 2) || ($status == 3)) {
            $map['status'] = $status - 1;
        }

        if ($field && $name) {
            $map[$field] = $name;
        }

        $data = M('ShopType')->where($map)->order($order_set)->page($p, $r)->select();
        $count = M('ShopType')->where($map)->count();
        $parameter['p'] = $p;
        $parameter['status'] = $status;
        $parameter['order'] = $order;
        $parameter['type'] = $type;
        $parameter['name'] = $name;
        $builder = new BuilderList();
        $builder->title('Product Category');
        $builder->titleList('Type list', U('Shop/type'));
        $builder->button('add', 'Add', U('Shop/edit_type'));
        $builder->button('resume', 'Enable', U('Shop/status', array('model' => 'ShopType', 'status' => 1)));
        $builder->button('forbid', 'Disable', U('Shop/status', array('model' => 'ShopType', 'status' => 0)));
        $builder->button('delete', 'Delete', U('Shop/status', array('model' => 'ShopType', 'status' => -1)));
        $builder->setSearchPostUrl(U('Shop/type'));
        $builder->search('order', 'select', array('id_desc' => 'ID desc', 'id_asc' => 'ID asc'));
        $builder->search('status', 'select', array('All Status', 'Disabled', 'Enabled'));
        $builder->search('field', 'select', array('name' => 'type name', 'title' => 'Type Title'));
        $builder->search('name', 'text', 'Enter search content');
        $builder->keyId();
        $builder->keyText('name', 'Category');
        $builder->keyText('title', 'Title');
        $builder->keyText('remark', 'Remark');
        $builder->keyText('sort', 'Sort');
        $builder->keyTime('addtime', 'Add time');
        $builder->keyStatus('status', 'Status', array('Disabled', 'Enabled'));
        $builder->keyDoAction('Shop/edit_type?id=###', 'Edit', 'Option');
        $builder->data($data);
        $builder->pagination($count, $r, $parameter);
        $builder->display();
    }

    public function edit_type($id = NULL)
    {
        if (!empty($_POST)) {
            if (APP_DEMO) {
                $this->error(L('SYSTEM_IN_DEMO_MODE'));
            }

            if (!check($_POST['name'], 'w')) {
                $this->error('Type the name of the wrong format');
            }

            if (!check($_POST['title'], 'a')) {
                $this->error('Type Title malformed');
            }

            if (!check($_POST['remark'], 'a')) {
                $this->error('Type Notes malformed');
            }

            if (!check($_POST['sort'], 'd')) {
                $this->error('Sort malformed');
            }

            if ($_POST['addtime']) {
                if (addtime(strtotime($_POST['addtime'])) == '---') {
                    $this->error('Addtime malformed');
                } else {
                    $_POST['addtime'] = strtotime($_POST['addtime']);
                }
            } else {
                $_POST['addtime'] = time();
            }

            if ($_POST['endtime']) {
                if (addtime(strtotime($_POST['endtime'])) == '---') {
                    $this->error('Edit the time format error');
                } else {
                    $_POST['endtime'] = strtotime($_POST['endtime']);
                }
            } else {
                $_POST['endtime'] = time();
            }

            if (check($_POST['id'], 'd')) {
                $rs = M('ShopType')->save($_POST);
            } else {
                $rs = M('ShopType')->add($_POST);
            }

            if ($rs) {
                $this->success('Successful operation');
            } else {
                $this->error('operation failed');
            }
        } else {
            $builder = new BuilderEdit();
            $builder->title('Product Category');
            $builder->titleList('Type list', U('Shop/type'));

            if ($id) {
                $builder->keyReadOnly('id', 'ID');
                $builder->keyHidden('id', 'ID');
                $data = M('ShopType')->where(array('id' => $id))->find();
                $data['addtime'] = addtime($data['addtime']);
                $data['endtime'] = addtime($data['endtime']);
                $builder->data($data);
            }

            $builder->keyText('name', 'Slug', 'Only chars');
            $builder->keyText('title', 'Type Title', 'Title');
            $builder->keyText('remark', 'Type Notes', 'Notes');
            $builder->keyText('sort', 'Sort Order', 'Only numbers');
            $builder->keyAddTime();
            $builder->keyEndTime();
            $builder->keyStatus();
            $builder->savePostUrl(U('Shop/edit_type'));
            $builder->display();
        }
    }

    public function coin($p = 1, $r = 15, $str_addtime = '', $end_addtime = '', $order = '', $status = '', $type = '', $field = '', $name = '')
    {
        $map = array();

        if ($str_addtime && $end_addtime) {
            $str_addtime = strtotime($str_addtime);
            $end_addtime = strtotime($end_addtime);

            if ((addtime($str_addtime) != '---') && (addtime($end_addtime) != '---')) {
                $map['addtime'] = array(
                    array('egt', $str_addtime),
                    array('elt', $end_addtime)
                );
            }
        }

        if (empty($order)) {
            $order = 'id_desc';
        }

        $order_arr = explode('_', $order);

        if (count($order_arr) != 2) {
            $order = 'id_desc';
            $order_arr = explode('_', $order);
        }

        $order_set = $order_arr[0] . ' ' . $order_arr[1];

        if (empty($status)) {
            $map['status'] = array('egt', 0);
        }

        if (($status == 1) || ($status == 2) || ($status == 3)) {
            $map['status'] = $status - 1;
        }

        if ($field && $name) {
            if ($field == 'name') {
                $map['shopid'] = D('Shop')->getShopId($name);
            }

            $map[$field] = $name;
        }

//D('Shop')->tongbu();
        $data = M('ShopCoin')->where($map)->order($order_set)->page($p, $r)->select();
        $count = M('ShopCoin')->where($map)->count();
        $parameter['p'] = $p;
        $parameter['status'] = $status;
        $parameter['order'] = $order;
        $parameter['type'] = $type;
        $parameter['name'] = $name;
        $builder = new BuilderList();
        $builder->title('payment method');
        $builder->titleList('As List', U('Shop/coin'));
        $builder->setSearchPostUrl(U('Shop/coin'));
        $builder->search('order', 'select', array('id_desc' => 'ID desc', 'id_asc' => 'ID asc'));
        $builder->search('field', 'select', array('name' => 'Product Name'));
        $builder->search('name', 'text', 'Enter search content');
        $builder->keyId();
        $builder->keyShopid();
        $coin_list = D('Coin')->get_all_name_list();

        if ($coin_list) {

        }

        $builder->keyDoAction('Shop/edit_coin?id=###', 'Edit', 'Option');
        $builder->data($data);
        $builder->pagination($count, $r, $parameter);
        $builder->display();
    }

    public function edit_coin($id = NULL)
    {
        if (!empty($_POST)) {

            if (APP_DEMO) {
                $this->error(L('SYSTEM_IN_DEMO_MODE'));
            }

            if (check($_POST['id'], 'd')) {
                $rs = M('ShopCoin')->save($_POST);
            } else {
                $this->error('operation failed1');
            }

            if ($rs) {
                $this->success('Successful operation');
            } else {
                $this->error('operation failed');
            }
        } else {
            $builder = new BuilderEdit();
            $builder->title('payment method');
            $builder->titleList('As List', U('Shop/coin'));

            if ($id) {
                $builder->keyReadOnly('id', 'the wayid');
                $builder->keyHidden('id', 'the wayid');
                $data = M('ShopCoin')->where(array('id' => $id))->find();
                $builder->data($data);
            }

            $builder->keyReadOnly('shopid', 'Productid', 'Can not be modified');
            $coin_list = D('Coin')->get_all_name_list();

            if ($coin_list) {
                foreach ($coin_list as $k => $v) {
                    $builder->keyText($k, $v, 'Keep int value: 1 is to enable 0 or Empty to disable');
                }
            }

            $builder->savePostUrl(U('Shop/edit_coin'));
            $builder->display();
        }
    }

    public function log($p = 1, $r = 15, $str_addtime = '', $end_addtime = '', $order = '', $status = '', $type = '', $field = '', $name = '')
    {
        $map = array();

        if ($str_addtime && $end_addtime) {
            $str_addtime = strtotime($str_addtime);
            $end_addtime = strtotime($end_addtime);

            if ((addtime($str_addtime) != '---') && (addtime($end_addtime) != '---')) {
                $map['addtime'] = array(
                    array('egt', $str_addtime),
                    array('elt', $end_addtime)
                );
            }
        }

        if (empty($order)) {
            $order = 'id_desc';
        }

        $order_arr = explode('_', $order);

        if (count($order_arr) != 2) {
            $order = 'id_desc';
            $order_arr = explode('_', $order);
        }

        $order_set = $order_arr[0] . ' ' . $order_arr[1];

        if (empty($status)) {
            $map['status'] = array('egt', 0);
        }

        if (($status == 1) || ($status == 2) || ($status == 3)) {
            $map['status'] = $status - 1;
        }

        if ($field && $name) {
            if ($field == 'name') {
                $map['shopid'] = D('Shop')->getShopId($name);
            }

            $map[$field] = $name;
        }
            
        //D('Shop')->tongbu();
        $data = M('ShopLog')->where($map)->order($order_set)->page($p, $r)->select();
        
        foreach ($data as $k => $v) {
            $data[$k]['shipping'] = ($v['status'] == 0 ? 0 : 1);
            $data[$k]['reject'] = ($v['status'] == 0 ? 0 : 1);
            $data[$k]['reward?'] = ($v['status'] == 3 ? 0 : 1);
        }

        $count = M('ShopLog')->where($map)->count();
        $parameter['p'] = $p;
        $parameter['status'] = $status;
        $parameter['order'] = $order;
        $parameter['type'] = $type;
        $parameter['name'] = $name;
        $builder = new BuilderList();
        $builder->title('Payment Method');
        $builder->titleList('As List', U('Shop/coin'));
        $builder->setSearchPostUrl(U('Shop/coin'));
        $builder->search('order', 'select', array('id_desc' => 'ID desc', 'id_asc' => 'ID asc'));
        $builder->search('field', 'select', array('name' => 'Product Name'));
        $builder->search('name', 'text', 'Enter search content');
        $builder->keyId();
        $builder->keyUserid();
        $builder->keyShopid();
        $builder->keyPrice('price', 'Offer price');
        $builder->keyPrice('num', 'Quantity');
        $builder->keyPrice('mum', 'Total');
        $builder->keyText('coinname', 'Coin');
        $builder->keyPrice('xuyao', 'Payments');
        $builder->keyHtml('addr', 'Shipping address');
        $builder->keyText('sort', 'Sort');
        $builder->keyTime('addtime', 'Added');

        $builder->keyStatus('status', 'Status', array('Delivery Awaited', 'Transaction complete', 'Revoked', 'Shipped'));
        $builder->keyDoAction('Shop/shipping?id=###', 'Mark as shipped|---|shipping', 'Option');
        $builder->keyDoAction('Shop/reject?id=###', 'Undo|---|reject', 'Option');
        $builder->keyDoAction('Shop/reward?id=###', 'Received|---|reward', 'Option');
        $builder->data($data);
        $builder->pagination($count, $r, $parameter);
        $builder->display();
    }

    public function shipping($id = NULL)
    {
        if (!check($id, 'd')) {
            $this->error(L('INCORRECT_REQ'));
        }

        $rs = M('ShopLog')->where(array('id' => $id))->save(array('status' => 3));

        if ($rs) {
            $this->success(L('SUCCESSFULLY_DONE'));
        } else {
            $this->error(L('OPERATION_FAILED'));
        }
    }

    public function reject($id = NULL)
    {

        if (!check($id, 'd')) {
            $this->error(L('INCORRECT_REQ'));
        }

        $shoplog = M('ShopLog')->where(array('id' => $id))->find();

        if (!$shoplog) {
            $this->error('operation failed1!');
        }

        if (!$shoplog['coinname']) {
            $this->error('operation failed2!');
        }

        if (!$shoplog['xuyao']) {
            $this->error('operation failed3!');
        }

        $mo = M();
        
        $mo->startTrans();
        $rs[] = $mo->table('codono_user_coin')->where(array('userid' => $shoplog['userid']))->setInc($shoplog['coinname'], $shoplog['xuyao']);
        $rs[] = $mo->table('codono_shop_log')->where(array('id' => $id))->save(array('status' => 2));

        if (check_arr($rs)) {
            $mo->commit();
            // removed unlock/lock
            $this->success(L('SUCCESSFULLY_DONE'));
        } else {
            $mo->rollback();
            $this->error(L('OPERATION_FAILED'));
        }
    }

    public function reward($id = NULL)
    {
        if (!check($id, 'd')) {
            $this->error(L('INCORRECT_REQ'));
        }

        $rs = M('ShopLog')->where(array('id' => $id))->save(array('status' => 1));

        if ($rs) {
            $this->success(L('SUCCESSFULLY_DONE'));
        } else {
            $this->error(L('OPERATION_FAILED'));
        }
    }

    public function goods($p = 1, $r = 15, $str_addtime = '', $end_addtime = '', $order = '', $status = '', $type = '', $field = '', $name = '')
    {
        $Type_arr = D('Shop')->shop_type_list();
        $Type_arr[0] = 'Complete';
        $map = array();

        if ($str_addtime && $end_addtime) {
            $str_addtime = strtotime($str_addtime);
            $end_addtime = strtotime($end_addtime);

            if ((addtime($str_addtime) != '---') && (addtime($end_addtime) != '---')) {
                $map['addtime'] = array(
                    array('egt', $str_addtime),
                    array('elt', $end_addtime)
                );
            }
        }

        if (empty($order)) {
            $order = 'id_desc';
        }

        $order_arr = explode('_', $order);

        if (count($order_arr) != 2) {
            $order = 'id_desc';
            $order_arr = explode('_', $order);
        }

        $order_set = $order_arr[0] . ' ' . $order_arr[1];

        if (empty($status)) {
            $map['status'] = array('egt', 0);
        }

        if (($status == 1) || ($status == 2) || ($status == 3)) {
            $map['status'] = $status - 1;
        }

        if ($type && $Type_arr[$type]) {
            $map['type'] = $type;
        }

        if ($field && $name) {
            if ($field == 'name') {
                $userid = D('User')->get_userid();

                if ($userid) {
                    $map['userid'] = $userid;
                } else {
                    $map[$field] = $name;
                }
            } else {
                $map[$field] = $name;
            }
        }

        $data = M('UserGoods')->where($map)->order($order_set)->page($p, $r)->select();
        $count = M('UserGoods')->where($map)->count();
        $parameter['p'] = $p;
        $parameter['status'] = $status;
        $parameter['order'] = $order;
        $parameter['type'] = $type;
        $parameter['name'] = $name;
        $builder = new BuilderList();
        $builder->title('Shipping address');
        $builder->titleList('Shipping address', U('Shop/index'));
        $builder->setSearchPostUrl(U('Shop/goods'));
        $builder->search('order', 'select', array('id_desc' => 'ID desc', 'id_asc' => 'ID asc'));
        $builder->search('field', 'select', array('name' => 'username'));
        $builder->search('name', 'text', 'Enter search content');
        $builder->keyId();
        $builder->keyUserId();
        $builder->keyText('name', 'name');
        $builder->keyText('truename', 'Truename');
        $builder->keyText('idcard', 'ID card');
        $builder->keyText('cellphone', 'Mobile');
        $builder->keyText('addr', 'contact address');
        $builder->keyText('sort', 'Sort');
        $builder->keyTime('addtime', 'add time');
        $builder->data($data);
        $builder->pagination($count, $r, $parameter);
        $builder->display();
    }

    public function images()
    {
        $baseUrl = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
        $upload = new \Think\Upload();
        $upload->maxSize = 3145728;
        $upload->exts = array('jpg', 'gif', 'png', 'jpeg');
        $upload->rootPath = UPLOAD_PATH . 'shop/';
        $upload->autoSub = false;
        $info = $upload->upload();

        if ($info) {
            if (!is_array($info['imgFile'])) {
                $info['imgFile'] = $info['file'];
            }

            $data = array('url' => str_replace('./', '/', $upload->rootPath) . $info['imgFile']['savename'], 'error' => 0);
            exit(json_encode($data));
        } else {
            $error['error'] = 1;
            $error['message'] = $upload->getError();
            exit(json_encode($error));
        }
    }
}
