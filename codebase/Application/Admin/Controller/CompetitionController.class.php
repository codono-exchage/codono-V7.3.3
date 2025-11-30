<?php

namespace Admin\Controller;

class CompetitionController extends AdminController
{
    public function index($p = 1, $r = 15, $str_addtime = '', $end_addtime = '', $order = '', $status = '', $type = '', $field = '', $name = '')
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

        $data = M('Competition')->where($map)->order($order_set)->page($p, $r)->select();
        $count = M('Competition')->where($map)->count();
        $parameter['p'] = $p;
        $parameter['status'] = $status;
        $parameter['order'] = $order;
        $parameter['type'] = $type;
        $parameter['name'] = $name;
        $builder = new BuilderList();
        $builder->title('Competition Record');
        $builder->titleList('Competition List', U('Competition/index'));
        $builder->button('delete', 'Delete', U('Competition/status', array('model' => 'Competition', 'status' => -1)));
        $builder->setSearchPostUrl(U('Competition/index'));
        $builder->search('order', 'select', array('id_desc' => 'ID desc', 'id_asc' => 'ID asc'));
        $builder->search('status', 'select', array('All Status', 'Disabled', 'Enabled'));
        $builder->search('field', 'select', array('username' => 'username'));
        $builder->search('name', 'text', 'Enter search content');
        $builder->keyId();
        $builder->keyUserid();
        $builder->keyText('voted_favor', 'Favor');
        $builder->keyText('voted_against', 'Against');
        
        $builder->keyType('type', 'Type', array(1 => 'Coin1', 2 => 'Coin2'));
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

        $data = M('CompetitionType')->where($map)->order($order_set)->page($p, $r)->select();
        $count = M('CompetitionType')->where($map)->count();

        foreach ($data as $k => $vv) {
            $data[$k]['votes_1'] = M('Competition')->where(array('coinname' => $vv['coinname'], 'type' => 1))->count() + $vv['votes_1'];
            $data[$k]['votes_2'] = M('Competition')->where(array('coinname' => $vv['coinname'], 'type' => 2))->count() + $vv['votes_2'];
            $data[$k]['total'] = $data[$k]['votes_1'] + $data[$k]['votes_2'];
            $data[$k]['bili'] = round(($data[$k]['votes_1'] / $data[$k]['total']) * 100, 2);
        }

        $parameter['p'] = $p;
        $parameter['status'] = $status;
        $parameter['order'] = $order;
        $parameter['type'] = $type;
        $parameter['name'] = $name;
        $builder = new BuilderList();
        $builder->title('Competition type');
        $builder->titleList('Competition type', U('Competition/type'));
        $builder->button('add', 'Add', U('Competition/edit'));
        $builder->button('delete', 'Delete', U('Competition/status', array('model' => 'CompetitionType', 'status' => -1)));
        $builder->setSearchPostUrl(U('Competition/index'));
        $builder->search('order', 'select', array('id_desc' => 'ID desc', 'id_asc' => 'ID asc'));
        $builder->search('status', 'select', array('All Status', 'Disabled', 'Enabled'));
        $builder->search('field', 'select', array('coinname' => 'Currencies'));
        $builder->search('name', 'text', 'Enter search content');
        $builder->keyId();
        $builder->keyText('coin_1', 'coin1');
        $builder->keyText('coin_2', 'coin2');
        $builder->keyText('featured', 'is_featured');
        $builder->keyText('fees', 'Total Amount');
        $builder->keyText('votes_1', 'Support');
        $builder->keyText('votes_2', 'Against');
        $builder->keyStatus();
        $builder->keyDoAction('Competition/edit?id=###', 'Edit', 'Option');
        $builder->data($data);
        $builder->pagination($count, $r, $parameter);
        $builder->display();
    }

    public function edit($id = NULL)
    {
		 $postData = I('post.');
        if (!empty($postData)) {
            if (check($postData['id'], 'd')) {
                $postData['status'] = 1;
                $rs = M('CompetitionType')->save($postData);
            } else {
                if (M('CompetitionType')->where(array('coin_1' => $postData['coinname']))->find()) {
                    $this->error('already exists');
                }

                $array = array(
                    'coin_1' => $postData['coin_1'],
					'coin_2' => $postData['coin_2'],
                    'votecoin' => $postData['votecoin'],
                    'fees' => $postData['fees'],
					'img_1' => $postData['img_1'],
					'img_2' => $postData['img_2'],
					'votes_1' => $postData['votes_1'],
					'votes_2' => $postData['votes_2'],
                    'status' => 1,
                );
                $rs = M('CompetitionType')->add($array);
            }

            if ($rs) {
                $this->success('Successful operation');
            } else {
				$this->error('No changes were made !!');
            }
        } else {
            $builder = new BuilderEdit();
            $builder->title('Competition Type Manager');
            $builder->titleList('Competition type list', U('Competition/type'));

            if ($id) {
                $builder->keyReadOnly('id', 'ID');
                $builder->keyHidden('id', 'ID');
                $data = M('CompetitionType')->where(array('id' => $id))->find();
                $builder->data($data);
            }

            $coin_list = D('Coin')->get_all_name_list();
            
            $builder->keyText('coin_1', 'Coin1 name', 'Symbol [etc, ltc,xrp]');
			$builder->keyText('coin_2', 'Coin2 name', 'Symbol [etc, ltc,xrp]');
            $builder->keyText('votes_1', 'Virtual Support Votes', 'Integer');
            $builder->keyText('votes_2', 'Virtual Against Votes', 'Integer');
			$builder->keyImage('img_1', 'Coin 1 Image', 'Coin 1 image', array('width' => 216, 'height' => 216, 'savePath' => 'Competition','url' => 'Competition/images'));
			$builder->keyImage('img_2', 'Coin 2 Image', 'Coin 2 image', array('width' => 216, 'height' => 216, 'savePath' => 'Competition','url' => 'Competition/images'));
            $builder->keySelect('votecoin', 'Competition currency', 'Votes need to deduct currency', $coin_list);
            $builder->keySelect('featured', 'Featured', 'Is it featured', [0=>'No',1=>'Yes' ]);
            $builder->keyTime('start_date', 'Start time');
            $builder->keyTime('end_date', 'End time');

            $builder->keyText('fees', 'Price', 'Integer,Price per vote');
            $builder->keySelect('status', 'Active', '1= active',[1=>'Yes',0=>'No']);
            $builder->savePostUrl(U('Competition/edit'));
            $builder->display();
        }
    }
	public function images()
    {
        $baseUrl = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
        $upload = new \Think\Upload();
        $upload->maxSize = 3145728;
        $upload->exts = array('jpg', 'gif', 'png', 'jpeg');
        
        $upload->rootPath = UPLOAD_PATH . 'Competition/';
        $upload->autoSub = false;
        $info = $upload->upload();

        if ($info) {
            if (!is_array($info['imgFile'])) {
                $info['imgFile'] = $info['file'];
            }

            $data = array('url' => str_replace('./', DS, $upload->rootPath) . $info['imgFile']['savename'], 'error' => 0);
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
