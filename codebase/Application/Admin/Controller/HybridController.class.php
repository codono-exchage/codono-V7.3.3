<?php

namespace Admin\Controller;

class HybridController extends AdminController
{
	
    public function configEdit()
    {
		
		foreach($_POST as $key=>$postdata){
			if($postdata!=''){
			$POST_ALL[$key]=$postdata;
                if($key=='receiver_priv'){
                    $POST_ALL[$key]=cryptString($postdata,'e');
                }
			}

		}
		
        if (!empty($POST_ALL)) {
            if (M('DexConfig')->where(array('id' => 1))->save($POST_ALL)) {
                $this->success(L('SUCCESS'));
            } else {
                $this->error(L('OPERATION_FAILED'));
            }
        } else {
            redirect(U('Admin/Hybrid/config'));
        }
    }
	public function coinImage()
    {
        $upload = new \Think\Upload();
        $upload->maxSize = 3145728;
        $upload->exts = array('jpg', 'gif', 'png', 'jpeg');
        $upload->rootPath = './Upload/coin/';
        $upload->autoSub = false;
        $info = $upload->upload();

        foreach ($info as $k => $v) {
            $path = $v['savepath'] . $v['savename'];
            echo $path;
            exit();
        }
    }
	public function coinEdit($id = NULL)
    {
		/*
		if (!check($id, 'd')) {
			$this->error('Select Proper Coin');
		}
			
        $DexCoins = M('DexCoins');
        $data = $DexCoins->where(array('id' =>$id))->find();
        $this->assign('data', $data);
        $this->display();
    */
		
        if (empty($_POST)) {
            if (empty($id)) {
                $this->data = array();
            } else {
				if (!check($id, 'd')) {
			$this->error('Select Proper Coin');
		}
			
        $DexCoins = M('DexCoins');
        $the_data = $DexCoins->where(array('id' =>$id))->find();
        
		        $this->data = $the_data;
            }

            $this->display();
        } else {
            
            if ($_POST['id']) {
                $where['id']=intval($_POST['id']);
                $save=$_POST;
                unset($save['id']);
                
                $rs = M('DexCoins')->where($where)->save($save);
            } else {
                if (!check($_POST['symbol'], 'n')) {
                    $this->error('Lowercase Letters!');
                }

                $_POST['name'] = strtolower($_POST['name']);

                $rs = M('DexCoins')->add($_POST);
            }
            if ($rs) {
                $this->success(L('SUCCESSFULLY_DONE'));
            } else {

                $this->error(L('No Changes'));
            }
        }
    

    }


    public function config($id = NULL)
    {
        $DexConfig = M('DexConfig');
        $data = $DexConfig->find();
        $this->assign('data', $data);
        $this->display();
    }
	
	public function coins($id = NULL)
    {
        $DexCoins = M('DexCoins');
        $data = $DexCoins->select();
        $this->assign('list', $data);
        $this->display();
    }

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
           
            $map[$field] = $name;
        }

        
        $data = M('DexDeposit')->where($map)->order($order_set)->page($p, $r)->select();

        foreach ($data as $k => $v) {
            $data[$k]['Pending'] = ($v['status'] == 0 ? 0 : 1);
            $data[$k]['Paid'] = ($v['status'] == 0 ? 0 : 1);
        }

        $count = M('DexDeposit')->where($map)->count();
        $parameter['p'] = $p;
        $parameter['status'] = $status;
        $parameter['order'] = $order;
        $parameter['type'] = $type;
        $parameter['name'] = $name;
        $builder = new BuilderList();
        $builder->title('Payment Orders');
        $builder->titleList('As List', U('Hybrid/index'));
        $builder->setSearchPostUrl(U('Hybrid/index'));
        $builder->search('order', 'select', array('id_desc' => 'ID desc', 'id_asc' => 'ID asc'));
        $builder->search('field', 'select', array('name' => 'Product Name'));
        $builder->search('name', 'text', 'Enter search content');
        $builder->keyId();
        $builder->keyUserid();
		$builder->keyText('qid', 'Qid');
        $builder->keyText('in_hash', 'in_hash');
		$builder->keyText('in_address', 'in_address');
		$builder->keyText('coin', 'Coin');
		$builder->keyText('amount', 'Amount');
        $builder->keyTime('in_time', 'In_Time');
		$builder->keyStatus('payout_status', 'Paid', array('Pending', 'Paid'));
		$builder->keyText('payout_hash', 'pay_hash');
				$builder->keyText('payout_qty', 'Paid Qty');
		$builder->keyTime('payout_time', 'Payment time');
        
        $builder->data($data);
        $builder->pagination($count, $r, $parameter);
        $builder->display();
    }
	public function quotes($p = 1, $r = 15, $str_addtime = '', $end_addtime = '', $order = '', $status = '', $type = '', $field = '', $name = '')
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

        
        $data = M('DexOrder')->where($map)->order($order_set)->page($p, $r)->select();

        foreach ($data as $k => $v) {
            $data[$k]['Pending'] = ($v['status'] == 0 ? 0 : 1);
            $data[$k]['Paid'] = ($v['status'] == 0 ? 0 : 1);
        }

        $count = M('DexOrder')->where($map)->count();
        $parameter['p'] = $p;
        $parameter['status'] = $status;
        $parameter['order'] = $order;
        $parameter['type'] = $type;
        $parameter['name'] = $name;
        $builder = new BuilderList();
        $builder->title('Payment Quotes');
        $builder->titleList('As List', U('Hybrid/quotes'));
        $builder->setSearchPostUrl(U('Hybrid/quotes'));
        $builder->search('order', 'select', array('id_desc' => 'ID desc', 'id_asc' => 'ID asc'));
        $builder->search('field', 'select', array('name' => 'Product Name'));
        $builder->search('name', 'text', 'Enter search content');
        $builder->keyId();
        $builder->keyUserid();
		$builder->keyText('qid', 'Qid');
        $builder->keyText('price', 'Price');
		$builder->keyText('qty', 'Qty');
		$builder->keyText('buy_coin', 'Bought');
        $builder->keyText('total', 'Total');
        $builder->keyText('spend_coin', 'Paid');
        $builder->keyTime('addtime', 'Time');
        $builder->keyStatus('received', 'Status', array('Pending', 'Received'));
        $builder->data($data);
        $builder->pagination($count, $r, $parameter);
        $builder->display();
    }
	
	   public function coinStatus()
    {
        if (APP_DEMO) {
            $this->error(L('SYSTEM_IN_DEMO_MODE'));
        }

        if (IS_POST) {
            $id = array();
            $id = implode(',', $_POST['id']);
        } else {
            $id = $_GET['id'];
        }

        if (empty($id)) {
            $this->error('please choose coin to be operated!');
        }

        $where['id'] = array('in', $id);
        $method = $_GET['type'];

        switch (strtolower($method)) {
            case 'forbid':
                $data = array('status' => 0);
                break;

            case 'resume':
                $data = array('status' => 1);
                break;

            case 'delete':
                $rs = M('DexCoins')->where($where)->select();

                if (M('DexCoins')->where($where)->delete()) {
                    $this->success(L('SUCCESSFULLY_DONE'));
                } else {
                    $this->error(L('OPERATION_FAILED'));
                }

                break;

            default:
                $this->error('Invalid parameters');
        }

        if (M('DexCoins')->where($where)->save($data)) {
            $this->success(L('SUCCESSFULLY_DONE'));
        } else {
            $this->error(L('OPERATION_FAILED'));
        }
    }

}
