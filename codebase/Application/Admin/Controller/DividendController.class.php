<?php

namespace Admin\Controller;

use Think\Upload;

class DividendController extends AdminController
{
    private $Model;

    public function index($p = 1, $r = 15, $str_addtime = '', $end_addtime = '', $order = '', $status = '', $type = '', $field = '', $name = '')
    {
        $this->checkUpdata();
        $myczType = M('MyczType')->select();
        $myczTypeList = array();
        $myczTypeListArr[0] = 'All the way';

        foreach ($myczType as $k => $v) {
            $myczTypeList[$v['name']] = $v['title'];
            $myczTypeListArr[$v['name']] = $v['title'];
        }
		
		$msg="Fast Caching Not Working !!";
		
		
		F("FASTCACHING",$msg);
		if(!F("FASTCACHING"))
		{
			die($msg);
		}
		
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

        if (isset($type) && isset($myczTypeList[$type])) {
            $map['type'] = $type;
        }

        if ($field && $name) {
            if ($field == 'username') {
                $map['userid'] = userid($name);
            } else {
                $map[$field] = $name;
            }
        }

        $data = M('Dividend')->where($map)->order($order_set)->page($p, $r)->select();
		
        $count = M('Dividend')->where($map)->count();
        $parameter['p'] = $p;
        $parameter['status'] = $status;
        $parameter['order'] = $order;
        $parameter['type'] = $type;
        $parameter['name'] = $name;
        $builder = new BuilderList();
        $builder->title('Airdrop Management');
        $builder->titleList('Airdrop List', U('Dividend/index'));
        $builder->button('add', 'Add', U('Dividend/edit'));
        $builder->keyId();
        $builder->keyText('name', 'Name');
		$builder->keyText('coinname', 'Holding Coin');
        $builder->keyText('coinjian', 'Reward Coin');
        $builder->keyText('num', 'Reward Coins');        
		$builder->keyText('active', 'Enabled');
		$builder->keyText('status', 'Distributed');
        $builder->keyTime('addtime', 'Add time');
        $builder->keyTime('endtime', 'Ending time');
        $builder->keyDoAction('Dividend/airdrop?id=###', 'Airdrop start|---|airdrop|1', 'Option');
        $builder->keyDoAction('Dividend/edit?id=###', 'Edit', 'Option');
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
                $this->error('Airdrop name wrong format');
            }

            if (!check($_POST['coinname'], 'w')) {
                $this->error('Airdrop Holding malformed');
            }

            if (!check($_POST['num'], 'double')) {
                $this->error('Reward Number incorrect');
            }
			if($_POST['sort']=='' || $_POST['sort']==null ){	
			$_POST['sort']=1;			     
			}
			if (!check($_POST['sort'], 'd')) {
					$this->error('Sort malformed'.$_POST['sort']);
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
                    $this->error('End time format error');
                } else {
                    $_POST['endtime'] = strtotime($_POST['endtime']);
                }
            } else {
                $_POST['endtime'] = $_POST['addtime'] +(86400*3); //3 days after addtime
            }

            if (check($_POST['id'], 'd')) {
                $rs = M('Dividend')->save($_POST);
            } else {
                $rs = M('Dividend')->add($_POST);
            }
			
            if ($rs) {
                $this->success('Successfully Saved');
            } else {
                $this->error('Could not be saved for some reasons');
            }
        } else {
            $coin_list = D('Coin')->get_all_name_list();

            foreach ($coin_list as $k => $v) {
                $coin_list[$k] = $v . '- [System Total ' . D('Coin')->get_sum_coin($k).']';
                $coin_lista[$k] = $v;
            }

            $builder = new BuilderEdit();
            $builder->title('Add/Edit Airdrop');
            $builder->titleList('Airdrops List', U('Dividend/index'));

            if ($id) {
                $builder->keyReadOnly('id', 'ID');
                $builder->keyHidden('id', 'ID');
                $data = M('Dividend')->where(array('id' => $id))->find();
                $data['addtime'] = addtime($data['addtime']);
                $data['endtime'] = addtime($data['endtime']);
                $builder->data($data);
            }

            $builder->keyText('name', 'AirDrop Title', 'Any language');
            $builder->keySelect('coinname', 'Holding currency', 'Currency User needs to hold', $coin_list);
            $builder->keySelect('coinjian', 'Reward currency', 'Reward currency that users get', $coin_lista);
            $builder->keyText('num', 'Quantity', 'Total Airdrop of reward currency');
            $builder->keyTextarea('content', 'Airdrop Introduction');
            $builder->keyText('sort', 'Sort', 'Integer');
			$builder->keyImage('image', 'Promo', 'Promo img', array('width' => 240, 'height' => 40, 'savePath' => 'airdrop', 'url' => U('Dividend/airdropimage')));
			$builder->keySelect('is_featured', 'Featured', '1= featured',array(0=>'No',1=>'Yes'));
			$builder->keySelect('active', 'Active', '1= active',[0=>'No',1=>'Yes']);
            $builder->keyAddTime();
            $builder->keyEndTime();
            $builder->savePostUrl(U('Dividend/edit'));
            $builder->display();
        }
    }

    public function status($id, $status, $model)
    {
        $builder = new BuilderList();
        $builder->doSetStatus($model, $id, $status);
    }

    public function airdrop()
    {
        $id = $_GET['id'];

        if (empty($id)) {
            $this->error('Choose correct airdrop!');
        }
		
        $data = M('Dividend')->where(array('id' => $id))->find();
		
        if (empty($data['id'])) {
            $this->error('Choose correct airdrop!');
        }
        if ($data['status'] != 0) {
            $this->error('It has been processed already, prohibit the operation again!');
        }

        $a = M('UserCoin')->sum($data['coinname']);
        $b = M('UserCoin')->sum($data['coinname'] . 'd');
        $data['quanbu'] = $a + $b; //Site total
		$data['meige']=0;
		if($data['quanbu']!=0){
        $data['meige'] = format_num($data['num'] / $data['quanbu'], 8);
		}
        $data['user'] = M('UserCoin')->where(array(
            $data['coinname'] => array('gt', 0),
            $data['coinname'] . 'd' => array('gt', 0),
            '_logic' => 'OR'
        ))->count();
		
        $this->assign('data', $data);
        $this->display();
    }

    public function distribute($id = NULL, $fid = NULL, $dange = NULL)
    {
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
            F('dividend_distribute_j', null);
            F('dividend_distribute_c', null);
            F('dividend_distribute', null);
            $dividend = M('Dividend')->where(array('id' => $fid))->find();

            if (!$dividend) {
                echo json_encode(array('status' => -2, 'info' => 'Airdrop failed to initialize'));
                exit();
            }

            F('dividend_distribute_j', $dividend);
            $usercoin = M('UserCoin')->where(array(
                $dividend['coinname'] => array('gt', 0),
                $dividend['coinname'] . 'd' => array('gt', 0),
                '_logic' => 'OR'
            ))->select();

            if (!$usercoin) {
                echo json_encode(array('status' => -2, 'info' => 'There are no user who holds'));
                exit();
            }

            $a = 1;

            foreach ($usercoin as $k => $v) {
                $shiji[$a]['userid'] = $v['userid'];
                $shiji[$a]['holdings'] = $v[$dividend['coinname']] + $v[$dividend['coinname'] . 'd'];
                $a++;
            }

            if (!$shiji) {
                echo json_encode(array('status' => -2, 'info' => 'Calculation error'));
                exit();
            }

            F('dividend_distribute_c', count($usercoin));
            F('dividend_distribute', $shiji);
            echo json_encode(array('status' => 1, 'info' => 'Airdrop successful initialization'));
            exit();
        }
		

        if ($id == 0) {
            echo json_encode(array('status' => 1, 'info' => ''));
            exit();
        }
		
        if (F('dividend_distribute_c') < $id) {
            echo json_encode(array('status' => 100, 'info' => 'Airdrop completed'));
            exit();
        }
        if ((0 < $id) && ($id <= F('dividend_distribute_c'))) {
            $dividend = F('dividend_distribute_j');
            $distribute = F('dividend_distribute');
            $cha = M('DividendLog')->where(array('name' => $dividend['name'], 'coinname' => $dividend['coinname'], 'userid' => $distribute[$id]['userid']))->find();

            if ($cha) {
                echo json_encode(array('status' => -2, 'info' => 'uid' . $distribute[$id]['userid'].' '.username($distribute[$id]['userid']) . ' already received airdrop'));
                exit();
            }

            $quantity = format_num($distribute[$id]['holdings'] * $dange, 8);

            if (!$quantity) {
                echo json_encode(array('status' => -2, 'info' => 'userid' . $distribute[$id]['userid'] . 'Number is too small so could not make airdrop, the number of holdings' . $distribute[$id]['holdings']));
                exit();
            }

            $mo = M();
			$mo->startTrans();
            $rs = array();
            $rs[] = $mo->table('codono_user_coin')->where(array('userid' => $distribute[$id]['userid']))->setInc($dividend['coinjian'], $quantity);
            $rs[] = $mo->table('codono_dividend_log')->add(array('name' => $dividend['name'], 'userid' => $distribute[$id]['userid'], 'coinname' => $dividend['coinname'], 'coinjian' => $dividend['coinjian'], 'fenzong' => $dividend['num'], 'price' => $dange, 'num' => $distribute[$id]['holdings'], 'mum' => $quantity, 'addtime' => time(), 'status' => 1));

            if (check_arr($rs)) {
               $mo->commit();
                echo json_encode(array('status' => 1, 'info' => 'UID[' . $distribute[$id]['userid'] . '] Holds ' . $distribute[$id]['holdings'] .$dividend['coinname']. ' received '.$dividend['coinjian'] . $quantity));
                exit();
            } else {
                $mo->rollback();
                echo json_encode(array('status' => -2, 'info' => 'userid ' . $distribute[$id]['userid'] . ',Number of holders:' . $distribute[$id]['holdings'] . ' Dividend failure'));
                exit();
            }
        }
    }

    public function log($p = 1, $r = 15, $str_addtime = '', $end_addtime = '', $order = '', $status = '', $type = '', $field = '', $name = '', $coinname = '', $coinjian = '')
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
        $builder->title('Airdrop Record');
        $builder->titleList('Record List', U('Dividend/log'));
        $builder->setSearchPostUrl(U('Dividend/log'));
        $builder->search('order', 'select', array('id_desc' => 'ID desc', 'id_asc' => 'ID asc'));
        $coinname_arr = array('' => 'Holding');
        $coinname_arr = array_merge($coinname_arr, D('Coin')->get_all_name_list());
        $builder->search('coinname', 'select', $coinname_arr);
        $coinjian_arr = array('' => 'Reward');
        $coinjian_arr = array_merge($coinjian_arr, D('Coin')->get_all_name_list());
        $builder->search('coinjian', 'select', $coinjian_arr);
        $builder->search('field', 'select', array('name' => 'Name', 'userid' => 'Username'));
        $builder->search('name', 'text', 'Name');
        $builder->keyId();
        $builder->keyText('name', 'Airdrop Name');
        $builder->keyUserid();
        $builder->keyText('coinname', 'Holding currency');
        $builder->keyText('coinjian', 'Reward');
        $builder->keyText('fenzong', 'Total number');
        $builder->keyText('price', 'Each award');
        $builder->keyText('num', 'Number of Coins held');
        $builder->keyText('mum', 'Airdrop Received');
        $builder->keyTime('addtime', 'Bonus time');
        $builder->data($data);
        $builder->pagination($count, $r, $parameter);
        $builder->display();
    }
	public function airdropimage()
    {
        $upload = new Upload();
        $upload->maxSize = 3145728;
        $upload->exts = array('jpg', 'gif', 'png', 'jpeg');
        $upload->rootPath = './Upload/airdrop/';
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

    public function checkUpdata()
    {
    }
}