<?php

namespace Admin\Controller;

class ActivityController extends AdminController
{
	public const allowed_types = ALLOWED_TYPES ;  // type of account balances
    public function _empty()
    {
        send_http_status(404);
        $this->error();
        echo L('Module does not exist!');
        die();

    }
	public function index($type=0,$order='dsc',$name='',$field=''){

		$p = 1; $r = 15;
		$parameter=$map= [];
		$input=I('get.');
		
			
        if ($type) {
            $map['type'] = $type;
        }

        if ($field && $name) {
            if ($field == 'username') {
                $map['userid'] = userid($name);
            } else {
                $map[$field] = $name;
            }
        }
		
		
		$parameter['p'] = $p;
        $parameter['order'] = $order;
        $parameter['type'] = $type;
        $parameter['name'] = $name;
		 
	
		$Activities = M('Activity')->where($map)->order('id desc')->select();
		$builder = new BuilderList();

			$count = M('Activity')->where($map)->count();
			//$count = M('Activity')->count();
		 $builder->title('Activity History');
	$builder->titleList('Activities List', U('Activity/index'));
    $builder->button('assets', 'User Assets', U('User/assets'));
	$builder->button('add', 'Add Asset', U('Activity/add'));
	$builder->setSearchPostUrl(U('Activity/index'));
	$builder->search('order', 'select', ['id_desc' => 'ID desc', 'id_asc' => 'ID asc']);
    $flip_account_array=array_flip($this->giveBalanceTypes());

	$builder->search('type', 'select', [0=>'All',1 => 'Income', 2 => 'Spend']);
	$builder->search('field', 'select', ['userid'=>'userid']);
	$builder->search('name', 'text', 'Enter search content');
    $builder->keyText('id', 'id');
        $builder->keyText('userid', 'Userid');
        $builder->keyText('adminid', 'AdminId');
        $builder->keyType('type', 'Type', [1 => 'Income', 2 => 'Spend']);
        $builder->keyType('account', 'Wallet',$flip_account_array);
		$builder->keyText('coin', 'Coin');
		$builder->keyText('amount', 'Amount');
        $builder->keyText('memo', 'Memo');
		$builder->keyText('txid', 'Tx');
		$builder->keyTime('addtime', 'Time');

		
		$builder->keyText('internal_note', 'Internal Note');
        $builder->data($Activities);
        $builder->pagination($count, $r, $parameter);
        $builder->display();	
	}

    /**
     * @param $length
     * @return string
     */
    private function generateRandomString($length = 25): string
    {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
	$randomString=time().'_'.$randomString;
    return md5($randomString);
	}
	
	
	public function add()
    {
		$baltypes = array_keys($this->giveBalanceTypes());
		$this->assign('account_types',$baltypes);
		$input=I('post.');
		if (empty($input)) {
		$hash=md5(time().$this->generateRandomString());
		$this->assign('hash',$hash);
		$this->display();
		}
		else{
		$form=$input;
		
		$userid=$_insert_Array['userid']=$form['userid'];
		$_insert_Array['adminid']=session('admin_id');
		$type=$_insert_Array['type']=$form['type']; //1 deposit or 2 subtract
		$coin=$_insert_Array['coin']=$form['coin'];
		$amount=$_insert_Array['amount']=$form['amount'];
		$memo=$_insert_Array['memo']=$form['memo'];
		$_insert_Array['addtime']=time();
		$_insert_Array['internal_note']=$form['internal_note'];
		$hash=$_insert_Array['internal_hash']=$form['memo'];
		$account=$_insert_Array['account']=$form['account'];
		$wallet=$this->getBalanceTypeKey($account);
            if ($amount <0 || !check($amount, 'double')) {
                $this->error('Please enter a valid amount : ' . $amount);
            }
		if($coin!=C('coin')[$coin]['name']){
			$this->error('No such coin:'.$coin);
		}	
	
		if(!$hash){
				$txid=md5(time().$userid.$coin.$hash.'admin_activity'.$this->generateRandomString());
		}else{
			$txid=$hash;
		}
            $if_already_hash = M('Activity')->where(['internal_hash' => $hash])->find();
            if($if_already_hash){
                $this->error('Caution:Similar transaction already added: Refresh the page');
            }
		$_insert_Array['txid']=$txid;
		if($account >0 ){
			$this->changeOtherBalance($userid,$coin,$amount,$wallet,$_insert_Array,$type);
			exit;
		}

		
		$user = M('User')->where(['id' => $userid])->find();
		if($userid!=$user['id']){
			$this->error('No such user found');
		}
		$tos= SHORT_NAME;
		$email=$user['email'];
		$coind=$coin.'d';
		$mo = M();
		 $query = "SELECT `$coin`,`$coind` FROM `codono_user_coin` WHERE `userid` = $userid";
                $res_bal = $mo->query($query);
                $user_coin_bal = $res_bal[0];
		//Income
		if($form['type']==1){
			$num_a=$user_coin_bal[$coin];
			$num_b=$user_coin_bal[$coin.'d'];
			$num=bcadd($num_a,$num_b,8);
			$mum_a=bcadd($num_a,$amount,8);
			$mum_b=$num_b;
			$mum=bcadd($mum_a,$mum_b,8);

			$mo->startTrans();
                if ($amount>0) {
                    $rs[] = $mo->table('codono_user_coin')->where(['userid' => $userid])->setInc($coin, $amount);
                }
				
			$rs[]=	$staff_credit=M('Activity')->add($_insert_Array);	
            $rs[]=  $zrid= M('myzr')->add(['userid' => $userid, 'type' => 'admin', 'username' => SHORT_NAME, 'coinname' => $coin, 'fee' => 0, 'txid' => $txid, 'num' => $amount, 'mum' => $amount, 'addtime' => time(), 'status' => 1,'memo'=>$memo]);
			   
			// Finance Entry
			$finance_array = ['userid' => $userid, 'coinname' => $coin, 'num_a' => $user_coin_bal[$coin], 'num_b' => $amount, 'num' => $num, 'fee' => 0, 'type' => 1, 'name' => 'Staff Credit', 'nameid' => $zrid, 'remark' => 'admin_activity', 'move' => $txid, 'addtime' => time(), 'status' => 1, 'mum' => $mum, 'mum_a' => $mum_a, 'mum_b' => $mum_b];
			$rs[] = $mo->table('codono_finance')->add($finance_array);
		if (check_arr($rs)) {
            $mo->commit();
			deposit_notify($userid, $tos, $coin, $txid, $amount, time());
            $this->success(L('Added!'));
        } else {
			$mo->rollback();
            $this->error(L('Sorry could not add!'));
        }	
			
			
		}
		//Do spend
		if($form['type']==2){
			if($user_coin_bal[$coin]<$amount){
				$this->error('User has less balance '.$user_coin_bal[$coin].' < '.$amount );
				
			}
			$num_a=$user_coin_bal[$coin];
			$num_b=$user_coin_bal[$coin.'d'];
			$num=bcadd($num_a,$num_b,8);
			$mum_a=bcsub($num_a,$amount,8);
			$mum_b=$num_b;
			$mum=bcadd($mum_a,$mum_b,8);

			$mo->startTrans();
                if ($amount>0) {
                    $rs[] = $mo->table('codono_user_coin')->where(['userid' => $userid])->setDec($coin, $amount);
                }
				
				$rs[]=	$staff_spend=M('Activity')->add($_insert_Array);	
               
			   
			 
				$rs[] =$zcid= M('myzc')->add(['userid' => $userid, 'type' => 'admin', 'username' => $tos, 'coinname' => $coin, 'fee' => 0, 'txid' => $txid, 'num' => $amount, 'mum' => $amount, 'addtime' => time(), 'status' => 1,'memo'=>$memo]);
			    	
				
				
			   // Finance Entry
			   $finance_array = ['userid' => $userid, 'coinname' => $coin, 'num_a' => $user_coin_bal[$coin], 'num_b' => $amount, 'num' => $num, 'fee' => 0, 'type' => 2, 'name' => 'Staff Spent', 'nameid' => $zcid, 'remark' => 'admin_activity', 'move' => $txid, 'addtime' => time(), 'status' => 1, 'mum' => $mum, 'mum_a' => $mum_a, 'mum_b' => $mum_b];
               $rs[] = $mo->table('codono_finance')->add($finance_array);
			   
		if (check_arr($rs)) {
            $mo->commit();
            $this->success(L('Record Added!'));
        } else {
            $mo->rollback();
            $this->error(L('Sorry could not add!'));
        }		
			
		}
		}
		
	}

	private function deposit_notify($to_email,$deposit_address,$coinname,$txid,$deposited_amount,$time){
		$deposit_time=date('Y-m-d H:i',$time).'('.date_default_timezone_get().')';
		$subject= 'Deposit Success Alerts ' .$deposit_time;
		$content= 'Hello,<br/>Your ' .SHORT_NAME. ' acccount has recharged ' .$deposited_amount. ' ' .$coinname . '<br/>
		<i><small>If this activity is not your own operation, please contact us immediately. </small>';
		addnotification($to_email,$subject,$content);
	}

    /**
     * @param $userid
     * @param $coin
     * @param $amount
     * @param $wallet
     * @param $_insert_Array
     * @param int $type
     * @return void
     */
    private function changeOtherBalance($userid, $coin, $amount, $wallet, $_insert_Array, int $type=1)
     {
		$rs = false;
		$mo=M();
        $mo->startTrans();
		//var_dump(array($userid,$coin,$amount,$wallet,$_insert_Array,$type));exit;
		list($condition, $result, $condition_add) = $this->AssetEntryConfirm($userid, $coin,$wallet, $mo);
		//var_dump(array($condition, $result, $condition_add,$result['coin'] == $coin && $result['uid'] == $userid));exit;
		if ($result['coin'] == $coin && $result['uid'] == $userid) {
			if($type==1){
				$rs[] = $mo->table('codono_user_assets')->where($condition)->setInc('balance', $amount);
			}else{
                if($result['balance']<$amount){
                    $this->error('You have insufficient balance '.$result['balance']);
                }
				$rs[] = $mo->table('codono_user_assets')->where($condition)->setDec('balance', $amount);
			}
			//add balance
			$rs[]=	$staff_credit=M('Activity')->add($_insert_Array);	
		}
		if (check_arr($rs)) {
            $mo->commit();

            $this->success(L('Transfer completed!!'));
        } else {
            $mo->rollback();
            $this->error(L('There were issues transferring!'));
        }
     }
    private function AssetEntryConfirm($userid, string $coin,$type, $mo): array
    {
        $account=$this->getBalanceTypeValue($type);
        if($account <0 ){
            $this->error('Invalid asset type');
        }

        $condition = ['account' => $account, 'uid' => $userid, 'coin' => $coin];
        $result = $mo->table('codono_user_assets')->where($condition)->find();
        $condition_add = [];
        if ($result == 0 || $result == null) {
            $condition_add = $condition;
            $condition_add['created_at'] = time();
            $mo->table('codono_user_assets')->add($condition_add);
            $result = $mo->table('codono_user_assets')->where($condition)->find();
        }
        if ($result == 0 || $result == null) {
            $mo->rollback();
            $this->error(L('There were some issues moving funds to p2p!'));
        }
        return [$condition, $result, $condition_add];
    }

    private function getBalanceTypeValue($key)
    {
        // Check if the key exists in the array
        $allowed_types = $this->giveBalanceTypes();

        if (array_key_exists(strtolower($key), $allowed_types)) {
            return $allowed_types[$key];
        } else {
            return -1;
        }
    }
    private function getBalanceTypeKey($val)
    {
        // Check if the key exists in the array
        $allowed_types = $this->giveBalanceTypes();
		$found=array_search($val, $allowed_types);
		
        if ($val && $found) {
            return $found;
        } else {
            return -1;
        }
    }

 	private function giveBalanceTypes(): array
    {
        return self::allowed_types;
    }

}