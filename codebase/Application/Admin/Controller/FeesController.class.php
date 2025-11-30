<?php

namespace Admin\Controller;

use Org\Util\Form;
use Think\Page;


class FeesController extends AdminController
{

	public function __construct(){
		parent::__construct();
     //   die('To use this demo feature for auto login , comment this line number '.__LINE__);
	}
    public function config() {
        $allowedTypes = ALLOWED_TYPES;
        
        // Fetch all existing types from the database
        $existingTypes = M('WalletFees')->getField('type', true); // Assuming M() is your model accessor in ThinkPHP 3.2
        
        foreach ($allowedTypes as $name => $type) {
            
            if (!in_array($type, $existingTypes)) {
                $add_array=[
                    'name'=>$name,
                    'type' => $type,
                    'in_let' => 1, // Default value
                    'out_let' => 1, // Default value
                    'wallet_enable' => 1, // Default value
                    'out_fee' => 0.00000000, // Default value
                ];
                // If the type does not exist, add it with default values
                M('WalletFees')->add($add_array);
            }
        }
        
        // Optionally, load the current configurations to pass to your view
        $configs = M('WalletFees')->where('type>0')->select();
        $coin_list=$this->getCoinList();
        $this->assign('coin_list', $coin_list);
        $this->assign('configs', $configs); // Assign to view
        $this->display(); // Render view
    }
    public function addFees() {
        $id = I('get.id', 0, 'intval'); // Fetch the 'id' parameter if present
        $model = M('WalletCoin'); // Use the correct model name
        if ($id) {
            // Editing an existing fee configuration
            $existingConfig = $model->where(['id' => $id])->find();
            if (!$existingConfig) {
                
            }else{
                $this->assign('config', $existingConfig); // Pass the existing configuration to the view
            }

        }
    
        $wallet_list = ALLOWED_TYPES;
        
        // Fetch all existing types from the database
        
        
 
        
  // Continue with your existing logic for addFees
  
  $coin_list = $this->getCoinList();
  //$configs = $model->where('type>0')->select();

  $this->assign('wallet_list', $wallet_list);
  $this->assign('coin_list', $coin_list);
  //$this->assign('configs', $configs); // Assign to view
  $this->display(); // Render view
    }
    public function index() {
        $model = M('WalletCoin'); // Use the correct model name
        $entries = $model->select(); // Fetch all entries from the table
        
        $this->assign('entries', $entries); // Pass the data to the view
        $this->display(); // Specify your view file name if it's not automatically determined
    }
    
    public function editFees() {
        if (IS_POST) {
            // Process form submission
            $data = I('post.'); // Get posted data
            
            foreach ($data as $key => $config) {
                
                // Assuming $key is the 'type' and $config contains the rest of the data
                $update=M('WalletFees')->where(['name' => $key])->save($config);
            }
            $this->success('Configuration updated successfully.');
        } else {
            redirect('Config/feeIndex');
            // Display form
            $configs = M('WalletFees')->select();
            $this->assign('configs', $configs);
            $this->display(); // Assuming you have a corresponding view file
        }
    }
    public function deleteFeesCoin() {
        $id = I('post.id', 0, 'intval'); // Make sure to use post.id to match the AJAX data

        if (!$id) {
            $this->ajaxReturn(['status' => 0, 'info' => 'Invalid request: No ID provided.']);
            return;
        }

        $model = M('WalletCoin');
        $entryExists = $model->where(['id' => $id])->count();

        if ($entryExists) {
            $result = $model->where(['id' => $id])->delete();
            if ($result) {
                $this->ajaxReturn(['status' => 1, 'info' => 'Entry successfully deleted.']);
            } else {
                $this->ajaxReturn(['status' => 0, 'info' => 'An error occurred while deleting the entry.']);
            }
        } else {
            $this->ajaxReturn(['status' => 0, 'info' => 'Entry not found.']);
        }
    }

    public function editFeesCoin() {
        if (IS_POST) {

    
            $data = I('post.'); // Get posted data
            $model = M('WalletCoin'); // Assuming 'WalletCoin' is the model name for the `codono_wallet_coin` table
             
            // Begin transaction
            $model->startTrans();
            try {
                    // Extract individual fields from $config
                    $wallet = $data['wallet'];
                    $coin = $data['coin'];
                    $feeCoin = $data['fee_coin'];
                    $outFeePercent = $data['out_fee'];
                    
                    // Prepare the data for updating/inserting
                    $addData = [
                        'name' => $wallet,
                        'coin' => $coin,
                        'fee_coin' => $feeCoin,
                        'out_fee' => $outFeePercent,
                    ];
                    // Prepare the data for updating/inserting
                    $updateData = [
                        'fee_coin' => $feeCoin,
                        'out_fee' => $outFeePercent,
                    ];
                    // Check if entry exists
                    $existingEntry = $model->where(['name' => $wallet, 'coin' => $coin])->find();
                    if ($existingEntry) {
                        // Update existing entry
                        $model->where(['name' => $wallet, 'coin' => $coin])->save($updateData);
                        $model->commit();
                        $this->success('Configuration updated successfully.');
                    } else {
                        // Insert new entry
                        $model->add($addData);
                        $model->commit();
                        $this->success('Configuration Added successfully.');
                    }
                
    
                // Commit transaction
                
            } catch (\Exception $e) {
                // Rollback transaction in case of error
                $model->rollback();
                $this->error('Failed to update configuration: ' . $e->getMessage());
            }
        } else {
            $this->error('Invalid request method.');
        }
    }
    public function transfers() {
        $model = M('Transfer'); // Assuming 'Transfer' is your model name for the `codono_transfer` table

        // Retrieve input for pagination and filtering
        $p = I('get.p', 1, 'intval');
        $r = 15; // Number of records per page
        $order = I('get.order', 'desc', 'text');
        $field = I('get.field', '', 'text');
        $name = I('get.name', '', 'text');
        $from_account=I('get.from_account', '', 'text');

        $to_account=I('get.to_account', '', 'text');
        // Initialize filtering conditions
        $map = [];
        if (!empty($field) && !empty($name)) {
            // Special handling for username to userid conversion
            if ($field == 'username') {
                // Assuming you have a function `userid` to convert username to userid
                $map['userid'] = userid($name);
            } else {
                $map[$field] = $name;
            }
        }

        if($from_account!='' && $from_account!=$to_account){
            $from_account=array_search($from_account,ALLOWED_TYPES);
            if($from_account){
                $map['from_account']=$from_account;
            }
        }
        if($to_account!='' && $from_account!=$to_account){
            $to_account=array_search($to_account,ALLOWED_TYPES);
            if($to_account){
                $map['to_account']=$to_account;
            }
        }

        // Count total records for pagination
        $count = $model->where($map)->count();

        // Fetch records for the current page
        $transfers = $model->where($map)->order("id $order")->page($p, $r)->select();

        // Prepare parameters for pagination


        $parameter['p'] = $p;
        $parameter['order'] = $order;

        $parameter['name'] = $name;

        // Initialize BuilderList
        $builder = new BuilderList();

        // Configure BuilderList
        $builder->title('Transfers');
        $builder->titleList('Wallet Transfers', U('Fees/transfers'));
        $builder->button('add', 'Fees', U('Fees/index'));
        $builder->setSearchPostUrl(U('Fees/transfers'));
        $builder->search('order', 'select', ['id_desc' => 'ID desc', 'id_asc' => 'ID asc']);
        $flip_account_array=array_flip($this->giveBalanceTypes());

        $builder->search('from_account', 'select', $flip_account_array);
        $builder->search('to_account', 'select', $flip_account_array);
        $builder->search('field', 'select', ['userid'=>'userid']);
        $builder->search('name', 'text', 'Enter search content');
        $builder->keyText('id', 'ID', 'text');
        $builder->keyText('userid', 'User ID', 'text');
        $builder->keyText('coin', 'Coin', 'text');
        $builder->keyPrice('amount', 'Amount', 'text');
        $builder->keyText('from_account', 'From Account', 'text');
        $builder->keyText('to_account', 'To Account', 'text');
        $builder->keyTime('created_at', 'Created At', 'addtime'); // Assuming you will format the timestamp
        $builder->keyText('status', 'Status', 'status');
        //$builder->button('add_new', ['title' => 'Add New']); // Example to add a button
        $builder->data($transfers); // Set the data to display
        $builder->pagination($count, $r, $parameter);
        $builder->display(); // Display the list
    }

    public function income() {
        $model = M('WalletIncome'); // Assuming 'WalletIncome' is your model name for the `codono_wallet_income` table

        $p = 1; $r = 15;
        $parameter=$map= [];
        $order=I('get.order','dsc','text');
        $field=I('get.field','','text');
        $name=I('get.name','','text');
        $coin=I('get.coin','','text');
        if ($field && $name) {
            if ($field == 'username') {
                $map['userid'] = userid($name);
            } else {
                $map[$field] = $name;
            }
        }
        $coin_list = D('Coin')->get_all_name_list();
        if($coin && $coin_list[$coin]){

            $map['coin']=$coin;
        }
        


        $parameter['p'] = $p;
        $parameter['order'] = $order;

        $parameter['name'] = $name;


        $Activities = M('WalletIncome')->where($map)->order('id desc')->select();
        $builder = new BuilderList();
        
        $count = M('WalletIncome')->where($map)->count();

        $builder->title('Wallet Income Logs');
        $builder->titleList('Wallet Fees', U('Fees/index'));
        $builder->button('addFees', 'addFees', U('Fees/addFees'));
        $builder->setSearchPostUrl(U('Fees/income'));
        $builder->search('order', 'select', ['id_desc' => 'ID desc', 'id_asc' => 'ID asc']);
        
        
        $builder->search('coin', 'select', $coin_list);

        $builder->search('field', 'select', ['userid'=>'userid','coin'=>'coin']);
        $builder->search('name', 'text', 'Enter search content');
        $builder->keyText('id', 'id');
        $builder->keyText('userid', 'Userid');
        $builder->keyText('coin', 'Coin');
        $builder->keyText('amount', 'Amount');
        $builder->keyText('module', 'Reason');
        $builder->keyText('tid', 'record');
        $builder->keyTime('addtime', 'Time');

        $builder->data($Activities);
        $builder->pagination($count, $r, $parameter);
        $builder->display();
    }
    
    
    private function getCoinList(){
        $Coins = C('coin');
        $coin_list=[];    
        foreach ($Coins as $k => $v) {
            if ($v['type'] != 'rmb') {
                if ($v['symbol'] != null && $v['symbol'] != $v['name'] && $v['status']!=0) {
                    continue;
                }
                $coin_list[] = $v['name'];
            }
        }
        return $coin_list;
    }
    private function giveBalanceTypes(): array
    {
        return ALLOWED_TYPES;
    }

}

