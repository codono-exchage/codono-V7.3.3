<?php

namespace Admin\Controller;

class VoteController extends AdminController
{
    public function index($p = 1, $r = 15, $str_addtime = '', $end_addtime = '', $order = '', $status = '', $type = '', $field = '', $name = '')
    {
        //$this->checkUpdata();
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
            if ($field == 'username') {
                $map['userid'] = userid($name);
            } else {
                $map[$field] = $name;
            }
        }

        $data = M('Vote')->where($map)->order($order_set)->page($p, $r)->select();
        $count = M('Vote')->where($map)->count();
        $parameter['p'] = $p;
        $parameter['status'] = $status;
        $parameter['order'] = $order;
        $parameter['type'] = $type;
        $parameter['name'] = $name;
        $builder = new BuilderList();
        $builder->title('Voting Record');
        $builder->titleList('Voting List', U('Vote/index'));
        $builder->button('delete', 'Delete', U('Vote/status', array('model' => 'Vote', 'status' => -1)));
        $builder->setSearchPostUrl(U('Vote/index'));
        $builder->search('order', 'select', array('id_desc' => 'ID desc', 'id_asc' => 'ID asc'));
        $builder->search('status', 'select', array('All Status', 'Disabled', 'Enabled'));
        $builder->search('field', 'select', array('username' => 'username'));
        $builder->search('name', 'text', 'Enter search content');
        $builder->keyId();
        $builder->keyUserid();
        $builder->keyText('coinname', 'Currencies');
        $builder->keyText('title', 'name');
        $builder->keyType('type', 'Type', array(1 => 'Favour', 2 => 'Against'));
        $builder->keyTime('addtime', 'add time');
        $builder->keyStatus();
        $builder->data($data);
        $builder->pagination($count, $r, $parameter);
        $builder->display();
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
            if ($field == 'username') {
                $map['userid'] = userid($name);
            } else {
                $map[$field] = $name;
            }
        }

        $data = M('VoteType')->where($map)->order($order_set)->page($p, $r)->select();
        $count = M('VoteType')->where($map)->count();

        foreach ($data as $k => $vv) {
            $data[$k]['zhichi'] = M('Vote')->where(array('coinname' => $vv['coinname'], 'type' => 1))->count() + $vv['zhichi'];
            $data[$k]['fandui'] = M('Vote')->where(array('coinname' => $vv['coinname'], 'type' => 2))->count() + $vv['fandui'];
            $data[$k]['zongji'] = $data[$k]['zhichi'] + $data[$k]['fandui'];
            $data[$k]['bili'] = round(($data[$k]['zhichi'] / $data[$k]['zongji']) * 100, 2);
        }

        $parameter['p'] = $p;
        $parameter['status'] = $status;
        $parameter['order'] = $order;
        $parameter['type'] = $type;
        $parameter['name'] = $name;
        $builder = new BuilderList();
        $builder->title('Voting type');
        $builder->titleList('Voting type', U('Vote/type'));
        $builder->button('add', 'Add', U('Vote/edit'));
        $builder->button('delete', 'Delete', U('Vote/status', array('model' => 'VoteType', 'status' => -1)));
        $builder->setSearchPostUrl(U('Vote/index'));
        $builder->search('order', 'select', array('id_desc' => 'ID desc', 'id_asc' => 'ID asc'));
        $builder->search('status', 'select', array('All Status', 'Disabled', 'Enabled'));
        $builder->search('field', 'select', array('coinname' => 'Currencies'));
        $builder->search('name', 'text', 'Enter search content');
        $builder->keyId();
        $builder->keyText('coinname', 'Currencies');
        $builder->keyText('title', 'name');
        $builder->keyText('votecoin', 'Payment currency');
        $builder->keyText('assumnum', 'Total Amount');
        $builder->keyText('zhichi', 'Support');
        $builder->keyText('fandui', 'Against');
        $builder->keyStatus();
        $builder->keyDoAction('Vote/edit?id=###', 'Edit', 'Option');
        $builder->data($data);
        $builder->pagination($count, $r, $parameter);
        $builder->display();
    }

    public function edit($id = NULL)
    {
        if (!empty($_POST)) {
            if (check($_POST['id'], 'd')) {
                $_POST['status'] = 1;
                $rs = M('VoteType')->save($_POST);
            } else {
                if (M('VoteType')->where(array('coinname' => $_POST['coinname']))->find()) {
                    $this->error('already exists');
                }

                $array = array(
                    'coinname' => $_POST['coinname'],
                    'title' => $_POST['title'],
                    'votecoin' => $_POST['votecoin'],
                    'assumnum' => $_POST['assumnum'],
					'img' => $_POST['img'],
                    'status' => 1,
                );
                $rs = M('VoteType')->add($array);
            }

            if ($rs) {
                $this->success('Successful operation');
            } else {
				$this->error('No changes were made !!');
            }
        } else {
            $builder = new BuilderEdit();
            $builder->title('Voting Type Manager');
            $builder->titleList('Voting type list', U('Vote/type'));

            if ($id) {
                $builder->keyReadOnly('id', 'ID');
                $builder->keyHidden('id', 'ID');
                $data = M('VoteType')->where(array('id' => $id))->find();
                $builder->data($data);
            }

            $coin_list = D('Coin')->get_all_name_list();
            //$builder->keySelect('coinname', 'Currencies', 'Currencies', $coin_list);
            $builder->keyText('coinname', 'Symbol', 'Symbol [etc, ltc,xrp]');
            $builder->keyText('title', 'Name', 'Name');
            $builder->keyText('zhichi', 'Virtual Support Votes', 'Integer');
            $builder->keyText('fandui', 'Virtual Against Votes', 'Integer');
			$builder->keyImage('img', 'Coin Image', 'Coin image', array('width' => 150, 'height' => 150, 'savePath' => 'vote','url' => 'images'));
            $builder->keySelect('votecoin', 'Voting currency', 'Votes need to deduct currency', $coin_list);
            $builder->keyText('assumnum', 'Price', 'Integer,Price per vote');


            $builder->savePostUrl(U('Vote/edit'));
            $builder->display();
        }
    }
	public function images()
    {
        $baseUrl = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
        $upload = new \Think\Upload();
        $upload->maxSize = 3145728;
        $upload->exts = array('jpg', 'gif', 'png', 'jpeg');
        $upload->rootPath = UPLOAD_PATH . 'vote/';
        $upload->autoSub = false;
        $info = $upload->upload();

        if ($info) {
            if (!is_array($info['imgFile'])) {
                $info['imgFile'] = $info['file'];
            }

            $data = array('url' => SITE_URL.$upload->rootPath . $info['imgFile']['savename'], 'error' => 0);
            exit(json_encode($data));
        } else {
            $error['error'] = 1;
            $error['message'] = $upload->getError();
            exit(json_encode($error));
        }
    }
    public function status($id, $status, $model)
    {
        $builder = new BuilderList();
        $builder->doSetStatus($model, $id, $status);
    }

    public function kaishi()
    {
        die();
        $id = $_GET['id'];

        if (empty($id)) {
            $this->error('please chooseData to be operated!');
        }

        $data = M('Dividend')->where(array('id' => $id))->find();

        if ($data['status'] != 0) {
            $this->error('Has been processed, prohibit the operation again!');
        }

        $a = M('UserCoin')->sum($data['coinname']);
        $b = M('UserCoin')->sum($data['coinname'] . 'd');
        $data['quanbu'] = $a + $b;
        $data['meige'] = round($data['num'] / $data['quanbu'], 8);
        $data['user'] = M('UserCoin')->where(array(
            $data['coinname'] => array('gt', 0),
            $data['coinname'] . 'd' => array('gt', 0),
            '_logic' => 'OR'
        ))->count();
        $this->assign('data', $data);
        $this->display();
    }

    public function fenfa($id = NULL, $fid = NULL, $dange = NULL)
    {
        die();
        if ($id === null) {
            echo json_encode(array('status' => -2, 'info' => 'Parameter error'));
            exit();
        }

        if ($fid === null) {
            echo json_encode(array('status' => -2, 'info' => 'Parameter error2'));
            exit();
        }

        if ($dange === null) {
            echo json_encode(array('status' => -2, 'info' => 'Parameter error3'));
            exit();
        }

        if ($id == -1) {
            S('dividend_fenfa_j', null);
            S('dividend_fenfa_c', null);
            S('dividend_fenfa', null);
            $dividend = M('Dividend')->where(array('id' => $fid))->find();

            if (!$dividend) {
                echo json_encode(array('status' => -2, 'info' => 'Dividend failed to initialize'));
                exit();
            }

            S('dividend_fenfa_j', $dividend);
            $usercoin = M('UserCoin')->where(array(
                $dividend['coinname'] => array('gt', 0),
                $dividend['coinname'] . 'd' => array('gt', 0),
                '_logic' => 'OR'
            ))->select();

            if (!$usercoin) {
                echo json_encode(array('status' => -2, 'info' => 'There are no user holds'));
                exit();
            }

            $a = 1;

            foreach ($usercoin as $k => $v) {
                $shiji[$a]['userid'] = $v['userid'];
                $shiji[$a]['chiyou'] = $v[$dividend['coinname']] + $v[$dividend['coinname'] . 'd'];
                $a++;
            }

            if (!$shiji) {
                echo json_encode(array('status' => -2, 'info' => 'Calculation error'));
                exit();
            }

            S('dividend_fenfa_c', count($usercoin));
            S('dividend_fenfa', $shiji);
            echo json_encode(array('status' => 1, 'info' => 'Dividend successful initialization'));
            exit();
        }

        if ($id == 0) {
            echo json_encode(array('status' => 1, 'info' => ''));
            exit();
        }

        if (S('dividend_fenfa_c') < $id) {
            echo json_encode(array('status' => 100, 'info' => 'Dividend completed'));
            exit();
        }

        if ((0 < $id) && ($id <= S('dividend_fenfa_c'))) {
            $dividend = S('dividend_fenfa_j');
            $fenfa = S('dividend_fenfa');
            $cha = M('DividendLog')->where(array('name' => $dividend['name'], 'coinname' => $dividend['coinname'], 'userid' => $fenfa[$id]['userid']))->find();

            if ($cha) {
                echo json_encode(array('status' => -2, 'info' => 'userid' . $fenfa[$id]['userid'] . 'The dividend has been issued'));
                exit();
            }

            $faduoshao = round($fenfa[$id]['chiyou'] * $dange, 8);

            if (!$faduoshao) {
                echo json_encode(array('status' => -2, 'info' => 'userid' . $fenfa[$id]['userid'] . 'The number is too small not made a dividend, the number of holdings' . $fenfa[$id]['chiyou']));
                exit();
            }

            $mo = M();
            
            $mo->startTrans();
            $rs = array();
            $rs[] = $mo->table('codono_user_coin')->where(array('userid' => $fenfa[$id]['userid']))->setInc($dividend['coinjian'], $faduoshao);
            $rs[] = $mo->table('codono_dividend_log')->add(array('name' => $dividend['name'], 'userid' => $fenfa[$id]['userid'], 'coinname' => $dividend['coinname'], 'coinjian' => $dividend['coinjian'], 'fenzong' => $dividend['num'], 'price' => $dange, 'num' => $fenfa[$id]['chiyou'], 'mum' => $faduoshao, 'addtime' => time(), 'status' => 1));

            if (check_arr($rs)) {
                $mo->commit();
                // removed unlock/lock
                echo json_encode(array('status' => 1, 'info' => 'userid' . $fenfa[$id]['userid'] . 'The number of holders' . $fenfa[$id]['chiyou'] . 'Success dividends' . $faduoshao));
                exit();
            } else {
                $mo->rollback();
                echo json_encode(array('status' => -2, 'info' => 'userid' . $fenfa[$id]['userid'] . 'The number of holders' . $fenfa[$id]['chiyou'] . 'Dividend failure'));
                exit();
            }
        }
    }

    public function log($p = 1, $r = 15, $str_addtime = '', $end_addtime = '', $order = '', $status = '', $type = '', $field = '', $name = '', $coinname = '', $coinjian = '')
    {
        die();
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
            if ($field == 'userid') {
                $map['userid'] = D('User')->get_userid($name);
            } else {
                $map[$field] = $name;
            }
        }

        if ($coinname) {
            $map['coinname'] = $coinname;
        }

        if ($coinjian) {
            $map['coinjian'] = $coinjian;
        }

        $data = M('DividendLog')->where($map)->order($order_set)->page($p, $r)->select();
        $count = M('DividendLog')->where($map)->count();
        $parameter['p'] = $p;
        $parameter['status'] = $status;
        $parameter['order'] = $order;
        $parameter['type'] = $type;
        $parameter['name'] = $name;
        $parameter['coinname'] = $coinname;
        $parameter['coinjian'] = $coinjian;
        $builder = new BuilderList();
        $builder->title('Dividend Record');
        $builder->titleList('Record List', U('Dividend/log'));
        $builder->setSearchPostUrl(U('Dividend/log'));
        $builder->search('order', 'select', array('id_desc' => 'ID desc', 'id_asc' => 'ID asc'));
        $coinname_arr = array('' => 'Dividend currency');
        $coinname_arr = array_merge($coinname_arr, D('Coin')->get_all_name_list());
        $builder->search('coinname', 'select', $coinname_arr);
        $coinjian_arr = array('' => 'Reward currency');
        $coinjian_arr = array_merge($coinjian_arr, D('Coin')->get_all_name_list());
        $builder->search('coinjian', 'select', $coinjian_arr);
        $builder->search('field', 'select', array('name' => 'Dividend Name', 'userid' => 'username'));
        $builder->search('name', 'text', 'Enter search content');
        $builder->keyId();
        $builder->keyText('name', 'Dividend Name');
        $builder->keyUserid();
        $builder->keyText('coinname', 'Dividend currency');
        $builder->keyText('coinjian', 'Reward currency');
        $builder->keyText('fenzong', 'Total number of dividend');
        $builder->keyText('price', 'Each award');
        $builder->keyText('num', 'Number of shares held');
        $builder->keyText('mum', 'Number of dividend');
        $builder->keyTime('addtime', 'Bonus time');
        $builder->data($data);
        $builder->pagination($count, $r, $parameter);
        $builder->display();
    }

    public function checkUpdata()
    {
    }
}