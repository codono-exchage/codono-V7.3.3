<?php

namespace Admin\Controller;

class PoolController extends AdminController
{
     public function index($p = 1, $r = 15, $str_addtime = '', $end_addtime = '', $order = '', $status = '', $type = '', $field = '', $name = '',$coinname='')
    {
		$parameter['p'] = $p;
        $parameter['status'] = $status;
        $parameter['order'] = $order;
        $parameter['type'] = $type;
        $parameter['name'] = $name;
		
		$map = array();
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
		if ($coinname) {
                $map['coinname'] = $coinname;
        }
		if ($status) {
                $map['status'] = $status;
        }
        $order_set = $order_arr[0] . ' ' . $order_arr[1];

		$data = M('Pool')->where($map)->order($order_set)->select();
        $count = M('Pool')->where($map)->order($order_set)->count();
        $builder = new BuilderList();
        $builder->title('Mining Machines:');
        $builder->titleList('Mining Machines', U('Pool/index'));
        $builder->button('add', 'Add', U('Pool/edit'));
		$builder->button('userMachines', 'User Machines', U('Pool/userMachines'));
        $builder->keyId();
		$builder->keyText('name', 'Name');
        $builder->keyPrice('price', 'Price');
        $builder->keyText('coinname', 'Coin');		
        $builder->keyText('days', 'Active for days');
		$builder->keyText('stocks', 'Available machines');
		$builder->keyText('quantity', 'Total machines');
		$builder->keyText('user_limit', 'User Buy Limit');
		$builder->keyPrice('daily_profit', 'Reward per day');
		$builder->keyText('getcoin', 'Reward Coin');
        
		//$builder->keyImage('ico', 'Image','Image',array('width' => 240, 'height' => 40, 'savePath' => 'Upload/pool', 'url' => U('pool/images')));
		
		
        $builder->keyText('power', 'Power Ghz');
		
		$builder->keyText('sort', 'Sort');
		$builder->keyBool('is_popular', 'Popular');
        $builder->keyStatus('status', 'Status', array('Inactive', 'Active'));
		$coinname_arr = array('' => 'Coin');
        $coinname_arr = array_merge($coinname_arr, D('Coin')->get_coin_name_list());
        $builder->search('coinname', 'select', $coinname_arr);
        $builder->search('status', 'select',  array('Inactive', 'Active'));
        $builder->search('name', 'text', 'Enter search content');
        $builder->keyDoAction('Pool/edit?id=###', 'Edit', 'Options');
		$builder->keyDoAction('Pool/deletePool?id=###', 'Delete', 'Options');
        $builder->data($data);
        $builder->pagination($count, $r, $parameter);
        $builder->display();
    }
	public function deletePool($id=NULL){
		$where['id']=$id;
		if (M('Pool')->where($where)->delete()) {
                    $this->success(L('SUCCESSFULLY_DONE'));
                } else {
                    $this->error('Could not delete!');
                }
		
	}

	public function userMachines($p = 1, $r = 50, $str_addtime = '', $end_addtime = '', $order = '', $status = '', $type = '', $field = '', $name = '', $coinname = '', $userid = '', $poolid = '')

    {
		$parameter['p'] = $p;
        $parameter['status'] = $status;
        $parameter['order'] = $order;
        $parameter['type'] = $type;
        $parameter['name'] = $name;
		
		$map = array();
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
		if ($coinname) {
                $map['coinname'] = $coinname;
        }
		if ($status) {
                $map['status'] = $status;
        }
		    if ($userid) {
        $map['userid'] = $userid;
    }
    if ($poolid) {
        $map['poolid'] = $poolid;
    }

        $order_set = $order_arr[0] . ' ' . $order_arr[1];

		$data = M('PoolLog')->where($map)->order($order_set)->page($p, $r)->select();
		
		$count = M('PoolLog')->where($map)->count();
		$builder = new BuilderList();
        $builder->titleList('User Machines', U('Pool/userMachines'));
		$builder->button('Mining Machines', 'Mining Machines', U('Pool/index'));
        $builder->keyId();
		$builder->keyText('name', 'Pool');
		$builder->keyText('poolid', 'poolid');
		$builder->keyText('userid', 'UserId');
        $builder->keyText('num', 'Bought');
		$builder->keyPrice('price', 'Price');
        $builder->keyText('coinname', 'Coin');		
        
		$builder->keyText('days', 'Duration');
		$builder->keyText('collected', 'Days used');
		
		
		$builder->keyPrice('daily_profit', 'DailyReward');
		$builder->keyText('getcoin', 'Reward Coin');


        $builder->keyTime('addtime', 'Time');
        $builder->keyStatus('status', 'Status', array(0=>'Ready',1=> 'Mining',2=>'Released'));
		$coinname_arr = array('' => 'Buy Coin');
        $coinname_arr = array_merge($coinname_arr, D('Coin')->get_coin_name_list());
        $builder->search('coinname', 'select', $coinname_arr);
		
		$r_coinname_arr = array('' => 'RewardCoin');
        $r_coinname_arr = array_merge($r_coinname_arr, D('Coin')->get_coin_name_list());
		
        $builder->setSearchPostUrl(U('Pool/userMachines'));

        $builder->search('getcoin', 'select', $r_coinname_arr);
        $builder->search('status', 'select',  array('Inactive', 'Active'));
		
		$builder->search('userid', 'text', 'Enter User ID');
		$builder->search('poolid', 'text', 'Enter Pool ID');

        $builder->data($data);
        $builder->pagination($count, $r, $parameter);
        $builder->display();
    }

    public function userRewards($p = 1, $r = 15, $str_addtime = '', $end_addtime = '', $order = '', $status = '', $type = '', $field = '', $name = '',$userid = '', $poolid = '',$hash='')
    {
		$map = array();
		if (($status == 1) || ($status == 2) || ($status == 3)) {
            $map['status'] = $status - 1;
        }
		$parameter['p'] = $p;
        $parameter['status'] = $status?:1;
        
   
		if (empty($order)) {
            $order = 'id_desc';
			$parameter['order'] = $order;
        }
	
        $order_arr = explode('_', $order);

        if (count($order_arr) != 2) {
            $order = 'id_desc';
            $order_arr = explode('_', $order);
        }
		if ($field && $name) {
                $map[$field] = $name;
				     $parameter['name'] = $name;
		$parameter['field'] = $field;
	
        }
		$parameter['type'] = $type;
         if ($userid) {
        $map['userid'] = $userid;
    }
    if ($poolid) {
        $map['poolid'] = $poolid;
    }
	if ($hash) {
        $map['hash'] = $hash;
    }
        $order_set = $order_arr[0] . ' ' . $order_arr[1];
		
		$data = M('PoolRewards')->where($map)->order($order_set)->page($p, $r)->select();
        $count = M('PoolRewards')->where($map)->count();
		$builder = new BuilderList();
        $builder->title('Mining User Rewards');
        $builder->titleList('Machines', U('Pool/Index'));
        $builder->keyId();
        $builder->keyText('poolid', 'poolid');
        $builder->keyText('userid', 'Userid');
        
        $builder->keyText('coinname', 'Coin');
		
        $builder->keyText('amount', 'amount');
		$builder->keyText('hash', 'Hash');
        $builder->keyTime('addtime', 'addtime');
		
		$builder->setSearchPostUrl(U('Pool/userRewards'));
		$builder->search('order', 'select', array('id_desc' => 'ID desc', 'id_asc' => 'ID asc'));
        $builder->search('userid', 'text', 'Enter userid');
		$builder->search('poolid', 'text', 'Enter PoolID');
		$builder->search('hash', 'text', 'Search Hash');

		$builder->button('Machines', 'Machines', U('Pool/Index'));
		$builder->button('User Machines', 'User Machines',U('Pool/userMachines'));
        $builder->data($data);
        $builder->pagination($count, $r, $parameter);
        $builder->display();
    }

    public function edit()
    {
        $id = I('get.id', 0, 'intval');
        $post_form=I('post.');
        if (!empty($post_form)) {
            
            if(!$post_form['id']){
			if (!check($post_form['days'], 'd') || !check($post_form['stocks'], 'd')  || !check($post_form['quantity'], 'd') || !check($post_form['user_limit'], 'd')) {
				$this->error("Days , Inventory, Quantity and User limit should be non decimal numbers");
			}
            
                $array = array(
					'name' => $post_form['name'],
                    'coinname' => $post_form['coinname'],
					'getcoin' => $post_form['getcoin'],
					'ico' => $post_form['ico'],
                    'price' => $post_form['price'],
                    'days' => $post_form['days'],
                    'stocks' => $post_form['stocks'],
					'quantity' => $post_form['quantity'],
                    'user_limit' => $post_form['user_limit'],
					'power' => $post_form['power'],
                    'daily_profit' => $post_form['daily_profit'],
                    'charge_coin' => $post_form['charge_coin'],
                    'charge_price' => $post_form['charge_price'],
                    'sort' => $post_form['sort'],
					'status' => $post_form['status'],
					'is_popular' => $post_form['is_popular'],
                );
                $rs = M('Pool')->add($array);

            }else {
			if (!check($post_form['days'], 'd') || !check($post_form['stocks'], 'd') || !check($post_form['quantity'], 'd') || !check($post_form['user_limit'], 'd')) {
				$this->error("Days , Inventory and User limit should be non decimal numbers");
			}
                $array = array(
					'name' => $post_form['name'],
                    'coinname' => $post_form['coinname'],
					'getcoin' => $post_form['getcoin'],
					'ico' => $post_form['ico'],
                    'price' => $post_form['price'],
                    'days' => $post_form['days'],
                    'stocks' => $post_form['stocks'],
					'quantity' => $post_form['quantity'],
                    'user_limit' => $post_form['user_limit'],
					'power' => $post_form['power'],
                    'daily_profit' => $post_form['daily_profit'],
                    'charge_coin' => $post_form['charge_coin'],
                    'charge_price' => $post_form['charge_price'],
                    'sort' => $post_form['sort'],
					'status' => $post_form['status'],
					'is_popular' => $post_form['is_popular'],
                );				

                $rs = M('Pool')->where(array('id'=>$post_form['id']))->save($array);
            }

            if ($rs) {
				S('investbox_list', NULL);
                $this->success('Successful operation');
            } else {
				$this->error('No changes were made !!');
            }
        } else {
			if ($id) {
                $this->data = M('Pool')->where(array('id' => trim($id)))->find();
            } else {
                $this->data = null;
            }
        
			
            if ($id) {
                $data = M('Pool')->where(array('id' => $id))->find();
				
				$this->assign($data);
            }
            $coin_list = D('Coin')->get_coin_name_list();
            
			$status_array=array('0'=>'Inactive','1'=>'Active');

			$this->assign('coin_list',$coin_list);
			$this->assign($status_array);
            $this->display();
        }
    }
	
	
	public function deleteInvesmentLog($id=array()){
		
		$where['id']=end($id);
		if (M('InvestboxLog')->where($where)->delete()) {
                    $this->success(L('SUCCESSFULLY_DONE'));
                } else {
                    $this->error('Could not delete!');
                }
		
	}	
	
	public function editInvesmentLog($id = NULL)
    {

        if (!empty($_POST)) {
			
			$userid=$_POST['userid'];
		
		
		$check_if_user=M('User')->where(array('id' => $userid))->getField('id');
		if(!isset($check_if_user)){
			$this->error('No such user found');
		}
		
		$boxid=$_POST['boxid'];
		$check_if_boxid=M('Investbox')->where(array('id' => $boxid))->getField('id');
		if(!isset($check_if_boxid)){
			$this->error('No such Invest box found');
		}
			
            if(!isset($_POST['id'])){
				$userid= $_POST['userid'];
				$docid=$_POST['boxid'].'IB'.$userid.tradeno();
                $array = array(
					'boxid' => $_POST['boxid'],
                    'docid' => $docid,
                    'period' =>$_POST['period'],//
                    'amount' => $_POST['amount'],
                    'begintime' => strtotime($_POST['begintime']),
                    'endtime' => strtotime($_POST['endtime']),
					'maturity' => $_POST['maturity'],
					'userid' => $_POST['userid'],
					'status' => $_POST['status'],
                );
				
                $rs = M('InvestboxLog')->add($array);

            }else {

				$array = array(
					'id' => $_POST['id'],
					'boxid' => $_POST['boxid'],
                    'period' =>$_POST['period'],//
                    'amount' => $_POST['amount'],
                    'maturity' => $_POST['maturity'],
					'begintime' => strtotime($_POST['begintime']),
                    'endtime' => strtotime($_POST['endtime']),
					'userid' => $_POST['userid'],
					'status' => $_POST['status'],
                );
				
                $rs = M('InvestboxLog')->save($array);
            }

            if ($rs) {
				S('investbox_list', NULL);
                $this->success('Successful operation');
            } else {
				$this->error('No changes were made !!');
            }
        } else {
           if ($id) {
                $this->data = M('InvestboxLog')->where(array('id' => trim($id)))->find();
            } else {
                $this->data = null;
            }
            $boxid=$this->data['boxid'];
			$period=$this->data['period'];
			$begintime=$this->data['begintime'];
			$endtime=$this->data['endtime'];
			$amount=$this->data['amount'];
			$maturity=$this->data['maturity'];
			$userid=$this->data['userid'];
			$status=$this->data['status'];
			if ($boxid) {
				$this->assign('id',$id);
				$this->assign('boxid',$boxid);
				$this->assign('period',$period);
				$this->assign('begintime',$begintime);
				$this->assign('endtime',$endtime);
				$this->assign('amount',$amount);
				$this->assign('maturity',$maturity);
				$this->assign('userid',$userid);
				$this->assign('status',$status);
            }
            
			$status_array=array('0'=>'Premature','1'=>'Active','2'=>'Reject','3'=>'Completed','4'=>'Upcoming');

			$this->assign($status_array);
            $this->display();
        }
    }
	
	public function mineImagev1()
    {
        $upload = new \Think\Upload();
        $upload->maxSize = 3145728;
        $upload->exts = array('jpg', 'gif', 'png', 'jpeg');
        $upload->rootPath = './Upload/pool/';
        $upload->autoSub = false;
        $info = $upload->upload();

        foreach ($info as $k => $v) {
            $path = $v['savepath'] . $v['savename'];
            return $path;
        }
    }
    public function mineImage()
    {
        $baseUrl = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
        $upload = new \Think\Upload();
        $upload->maxSize = 3145728;
        $upload->exts = array('jpg', 'gif', 'png', 'jpeg');
        
        $upload->rootPath = UPLOAD_PATH . 'pool/';
        $upload->autoSub = false;
        $info = $upload->upload();
        
        if ($info) {
           
            foreach ($info as $k => $v) {
                $path = $v['savepath'] . $v['savename'];
            } 
            $data = array('url' => $path, 'error' => 0);
            exit(json_encode($data));
        } else {
            $error['error'] = 1;
            $error['message'] = $upload->getError();
            exit(json_encode($error));
        }
    }
    public function fees($p = 1, $r = 50, $str_addtime = '', $end_addtime = '', $order = '', $status = '', $type = '', $field = '', $name = '')
{
    $map = array();
    if (in_array($status, array(1, 2, 3))) {
        $map['status'] = $status - 1;
    }

    if (empty($order)) {
        $order = 'id_desc';
    }

    $order_set = $this->sanitizeOrder($order);
    if (!$order_set) {
        $order_set = 'id DESC';
    }

    if ($field && $name) {
        // Add validation for field and name if necessary
        $map[$field] = $name;
    }

    $parameter = array(
        'p' => $p,
        'status' => $status,
        'order' => $order,
        'type' => $type,
        'name' => $name,
    );

    $count = M('PoolFees')->where($map)->count();
    $offset = ($p - 1) * $r;
    $data = M('PoolFees')->where($map)->order($order_set)->limit($offset, $r)->select();
   $builder = new BuilderList();
        $builder->title('Mining Fees Earnings');
        $builder->titleList('Machines', U('Pool/Index'));
        $builder->keyId();
        $builder->keyText('pool_id', 'Pool ID');
        $builder->keyText('userid', 'User ID');
        $builder->keyText('rent_id', 'Rent ID');
        $builder->keyText('coin', 'Coin');
        $builder->keyPrice('amount', 'Amount');

        $builder->keyStatus('type', 'Type', array('Unknown','Rental', 'Release'));
        $builder->keyTime('addtime', 'Add Time');


        $builder->setSearchPostUrl(U('Pool/fees'));
        $builder->search('order', 'select', array('id_desc' => 'ID desc', 'id_asc' => 'ID asc'));
        $builder->search('field', 'select', array('id' => 'ID', 'userid' => 'UserID', 'pool_id' => 'Pool ID', 'rent_id' => 'Rent ID'));
        $builder->search('name', 'text', 'Enter text');
        $builder->button('Machines', 'Machines', U('Pool/Index'));
        $builder->button('User Machines', 'User Machines', U('Pool/userMachines'));
        $builder->data($data);
        $builder->pagination($count, $r, $parameter);
        $builder->display();
    }
									   
    private function sanitizeOrder($order){
         $allowedColumns=['id', 'userid', 'pool_id', 'rent_id', 'coin', 'amount', 'type', 'addtime']; 
         $allowedDirections=['asc', 'desc'];
         $orderParts=explode('_', strtolower($order));
          if (count($orderParts)===2 && in_array($orderParts[0], $allowedColumns) && in_array($orderParts[1], $allowedDirections))
          {
             return $orderParts[0] . ' ' . strtoupper($orderParts[1]);
          }
          return false;
    }
}