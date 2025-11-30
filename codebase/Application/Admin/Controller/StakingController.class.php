<?php

namespace Admin\Controller;

class StakingController extends AdminController
{
    /**
     * @param $p
     * @param $r
     * @param $str_addtime
     * @param $end_addtime
     * @param $order
     * @param $status
     * @param $type
     * @param $field
     * @param $name
     * @param $coinname
     * @return void
     */
    public function index($p = 1, $r = 15, $str_addtime = '', $end_addtime = '', $order = '', $status = '', $type = '', $field = '', $name = '', $coinname = '')
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
        if ($coinname) {
            $map['coinname'] = $coinname;
        }
        $order_set = $order_arr[0] . ' ' . $order_arr[1];
    
        $data = M('Staking')->where($map)->order($order_set)->select();
        $count = M('Staking')->where($map)->order($order_set)->count();
    
        // Decode the percentage JSON for each staking plan
        foreach ($data as &$item) {
            if (isset($item['percentage']) && !empty($item['percentage'])) {
                $percentages = json_decode($item['percentage'], true);
                if (json_last_error() == JSON_ERROR_NONE) {
                    // Construct a string to display percentages
                    $percentageStr = [];
                    foreach ($percentages as $period => $percentage) {
                        $percentageStr[] = "{$period} days: {$percentage}%";
                    }
                    $item['percentage'] = implode(", ", $percentageStr);
                } else {
                    // In case of JSON decode error, fallback to the original string
                    $item['percentage'] = 'Error decoding JSON';
                }
            }
        }
        unset($item); // Break the reference with the last element
    
        $builder = new BuilderList();
        $builder->title('Staking Plans:');
        $builder->titleList('Staking logs', U('Staking/list'));
        $builder->button('add', 'Add', U('Staking/edit'));
        $builder->keyId();
        $builder->keyText('coinname', 'Coin');
        $builder->keyText('percentage', 'Days/Return  (%)');
        
        $builder->keyPrice('minvest', 'Minvest');
        $builder->keyPrice('maxvest', 'Maxvest');
        $builder->keyText('creatorid', 'creatorid');
        $builder->keyStatus('status', 'Status', ['Submitted', 'Approved', 'Reject', 'Completed', 'Upcomings']);
        $coinname_arr = ['' => 'Coin'];
        $coinname_arr = array_merge($coinname_arr, D('Coin')->get_all_name_list());
        $builder->search('coinname', 'select', $coinname_arr);
        $builder->search('field', 'select', ['creatorid' => 'creatorid']);
        $builder->search('status', 'select', ['Submitted', 'Approved', 'Reject', 'Completed', 'Upcomings']);
        $builder->search('name', 'text', 'Enter search content');
        $builder->keyDoAction('Staking/edit?id=###', 'Edit', 'Option');
        $builder->keyDoAction('Staking/deletePlan?id=###', 'Delete', 'Option');
        $builder->data($data);
        $builder->pagination($count, $r, $parameter);
        $builder->display();
    }
    

    public function edit($id = NULL)
    {
        $plans = [1, 7, 30, 60, 90, 120, 180, 365];

        if (!empty($_POST)) {

            $action['coin'] = ['name' => $_POST['actionxcoinname'], 'value' => $_POST['actionxcoinvalue']];
            $action['market'] = ['name' => $_POST['actionxmarketname'], 'buy' => $_POST['actionxmarketbuy'], 'sell' => $_POST['actionxmarketsell']];
            if (!empty($_POST['periods']) && !empty($_POST['percentages'])) {
                $periods = $_POST['periods'];
                $percentages = $_POST['percentages'];
                $percentageData = [];

                foreach ($periods as $period) {
                    if (isset($percentages[$period]) && !empty($percentages[$period])) {
                        $percentageData[$period] = $percentages[$period];
                    }
                }

                // Now, $percentageData contains periods as keys and percentages as values
                $percentageJson = json_encode($percentageData); // Convert to JSON string to store in DB

                // Include $percentageJson in your $array for DB insertion/updation
                $array['percentage'] = $percentageJson;
            }
            if (!$_POST['id']) {

                $array = [
                    'title' => $_POST['title'],
                    'coinname' => $_POST['coinname'],
                    'percentage' => $array['percentage'],
                    'penalty_coin' => $_POST['penalty_coin'],
                    'penalty_amount' => $_POST['penalty_amount'],
//                    'period' => json_encode($_POST['period']),//
                    'minvest' => $_POST['minvest'],
                    'maxvest' => $_POST['maxvest'],
                    'creatorid' => $_POST['creatorid'],
                    'status' => $_POST['status'],
                    'allow_withdrawal' => $_POST['allow_withdrawal'],
                    'action' => (string)json_encode($action),
                ];

                $rs = M('Staking')->add($array);

            } else {

                $array = [
                    'id' => $_POST['id'],
                    'title' => $_POST['title'],
                    'coinname' => $_POST['coinname'],
                    'penalty_coin' => $_POST['penalty_coin'],
                    'penalty_amount' => $_POST['penalty_amount'],
                    'period' => json_encode($_POST['period']),//
                    'percentage' => $array['percentage'],
                    'minvest' => $_POST['minvest'],
                    'maxvest' => $_POST['maxvest'],
                    'creatorid' => $_POST['creatorid'],
                    'status' => $_POST['status'],
                    'allow_withdrawal' => $_POST['allow_withdrawal'],
                    'action' => (string)json_encode($action),
                ];


                $rs = M('Staking')->save($array);
            }

            if ($rs) {
                S('investbox_list', NULL);
                $this->success('Successful operation');
            } else {
                $this->error('No changes were made !!');
            }
        } else {

            if ($id) {
                $this->data = M('Staking')->where(['id' => trim($id)])->find();
            } else {
                $this->data = null;
            }
            
            $plans = [1, 7, 30, 60, 90, 120, 180, 365];

            // Decode the stored percentages JSON, handling the case where it might be empty
            $storedPercentages = !empty($this->data['percentage']) ? json_decode($this->data['percentage'], true) : [];
            
            $appc['period'] = [];
            foreach ($plans as $plan) {
                // Initialize percentage value to an empty string if not set
                $percentageValue = isset($storedPercentages[$plan]) ? $storedPercentages[$plan] : '';
                
                // Determine if the current plan has a stored percentage (flag is 1 if true, 0 otherwise)
                $flag = !empty($percentageValue) ? 1 : 0;
            
                $appc['period'][] = [
                    'value' => $plan, // The plan value (e.g., 1, 7, 30, etc.)
                    'flag' => $flag, // Indicates if this plan has a stored percentage
                    'percentage' => $percentageValue, // The actual percentage value if it exists
                ];
            }
            
            // Assign the prepared data for access in the view
            $this->assign('appCon', $appc);
            

            $this->assign('appCon', $appc);
            if ($id) {
                $data = M('Staking')->where(['id' => $id])->find();

                $this->assign($data);
                $action = json_decode($data['action']);
                $actionx['coin']['name'] = $action->coin->name;
                $actionx['coin']['value'] = $action->coin->value;
                $actionx['market']['name'] = $action->market->name;
                $actionx['market']['buy'] = $action->market->buy;
                $actionx['market']['sell'] = $action->market->sell;

                $this->assign('actionx', $actionx);
            }
            $coin_li = D('Coin')->get_coin_name_list();
            foreach($coin_li as $key=>$val){
                $coin_list[]=$key;
            }
            
            $status_array = ['0' => 'Submitted', '1' => 'Approved', '2' => 'Reject', '3' => 'Completed', '4' => 'Upcoming'];

            $this->assign('coin_list',$coin_list);
            $this->assign($status_array);
            $this->display();
        }
    }

    public function deletePlan($id = NULL)
    {
        $where['id'] = $id;
        if (M('Staking')->where($where)->delete()) {
            $this->success(L('SUCCESSFULLY_DONE'));
        } else {
            $this->error('No changes were made.');
        }

    }

    public function stakingLog($p = 1, $r = 15, $str_addtime = '', $end_addtime = '', $order = '', $status = '', $type = '', $field = '', $name = '')
    {
        $map = [];
        if (($status == 1) || ($status == 2) || ($status == 3)) {
            $map['status'] = $status - 1;
        }

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

        $order_set = $order_arr[0] . ' ' . $order_arr[1];
        $parameter['p'] = $p;
        $parameter['status'] = $status;
        $parameter['order'] = $order;
        $parameter['type'] = $type;
        $parameter['name'] = $name;

        $data = M('StakingLog')->where($map)->order($order_set)->select();
        $count = M('StakingLog')->where($map)->count();
        $builder = new BuilderList();
        $builder->title('Investments: Status');
        $builder->titleList('InvestBoxs', U('Staking/Index'));
        $builder->keyId();
        $builder->keyText('staking_id', 'staking_id');
        $builder->keyText('docid', 'Docid');
        $builder->keyPrice('amount', 'amount');
        $builder->keyTime('begintime', 'Begin');
        $builder->keyTime('endtime', 'End');
        $builder->keyTime('withdrawn', 'Withdrawn');
        $builder->keyPrice('maturity', 'Maturity');
        $builder->keyPrice('credited', 'Credited');
        $builder->keyText('userid', 'Userid');

        $builder->setSearchPostUrl(U('Invest/stakingLog'));
        $builder->search('order', 'select', ['id_desc' => 'ID desc', 'id_asc' => 'ID asc']);
        $builder->search('status', 'select', ['All Status', 'Premature Withdrawn', 'Active', 'Reject', 'Completed']);
        $builder->search('field', 'select', ['id' => 'ID', 'userid' => 'UserID', 'docid' => 'Docid', 'staking_id' => 'staking_id']);
        $builder->search('name', 'text', 'Enter text');
        $builder->button('add', 'Add', U('Staking/editLog'));
        $builder->button('delete', 'Delete', U('Invest/deleteInvesmentLog'));
        $builder->keyDoAction('Staking/editLog?id=###', 'Edit', 'Option');

        $builder->keyStatus('status', 'Status', ['Premature', 'Active', 'Reject', 'Completed']);
        $builder->data($data);
        $builder->pagination($count, $r, $parameter);
        $builder->display();
    }

    public function deleteLog($id = [])
    {

        $where['id'] = end($id);
        if (M('StakingLog')->where($where)->delete()) {
            $this->success(L('SUCCESSFULLY_DONE'));
        } else {
            $this->error('Could not delete!');
        }

    }

    public function editLog($id = NULL)
    {

        if (!empty($_POST)) {

            $userid = $_POST['userid'];


            $check_if_user = M('User')->where(['id' => $userid])->getField('id');
            if (!isset($check_if_user)) {
                $this->error('No such user found');
            }

            $staking_id = $_POST['staking_id'];
            $check_if_staking_id = M('Staking')->where(['id' => $staking_id])->getField('id');
            if (!isset($check_if_staking_id)) {
                $this->error('No such Invest box found');
            }

            if (!isset($_POST['id'])) {
                $userid = $_POST['userid'];
                $docid = $_POST['staking_id'] . 'IB' . $userid . tradeno();
                $array = [
                    'staking_id' => $_POST['staking_id'],
                    'docid' => $docid,
                    'period' => $_POST['period'],//
                    'amount' => $_POST['amount'],
                    'begintime' => strtotime($_POST['begintime']),
                    'endtime' => strtotime($_POST['endtime']),
                    'maturity' => $_POST['maturity'],
                    'userid' => $_POST['userid'],
                    'status' => $_POST['status'],
                ];

                $rs = M('StakingLog')->add($array);

            } else {

                $array = [
                    'id' => $_POST['id'],
                    'staking_id' => $_POST['staking_id'],
                    'period' => $_POST['period'],//
                    'amount' => $_POST['amount'],
                    'maturity' => $_POST['maturity'],
                    'begintime' => strtotime($_POST['begintime']),
                    'endtime' => strtotime($_POST['endtime']),
                    'userid' => $_POST['userid'],
                    'status' => $_POST['status'],
                ];

                $rs = M('StakingLog')->save($array);
            }

            if ($rs) {
                S('investbox_list', NULL);
                $this->success('Successful operation');
            } else {
                $this->error('No changes were made !!');
            }
        } else {
            if ($id) {
                $this->data = M('StakingLog')->where(['id' => trim($id)])->find();
            } else {
                $this->data = null;
            }
            $staking_id = $this->data['staking_id'];
            $period = $this->data['period'];
            $begintime = $this->data['begintime'];
            $endtime = $this->data['endtime'];
            $amount = $this->data['amount'];
            $maturity = $this->data['maturity'];
            $userid = $this->data['userid'];
            $status = $this->data['status'];
            if ($staking_id) {
                $this->assign('id', $id);
                $this->assign('staking_id', $staking_id);
                $this->assign('period', $period);
                $this->assign('begintime', $begintime);
                $this->assign('endtime', $endtime);
                $this->assign('amount', $amount);
                $this->assign('maturity', $maturity);
                $this->assign('userid', $userid);
                $this->assign('status', $status);
            }

            $status_array = ['0' => 'Premature', '1' => 'Active', '2' => 'Reject', '3' => 'Completed', '4' => 'Upcoming'];

            $this->assign($status_array);
            $this->display();
        }
    }

	    public function dicerolls($p = 1, $r = 15, $str_addtime = '', $end_addtime = '', $order = '', $status = '', $type = '', $field = '', $name = '', $coinname = '')
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
        $order_set = $order_arr[0] . ' ' . $order_arr[1];

        $data = M('Dice')->where($map)->order($order_set)->select();
        $count = M('Dice')->where($map)->count();
        $builder = new BuilderList();
        $builder->title('Dice Rolls : Status >>> 1=win,2=lost');
        $builder->titleList('DiceRolls', U('Invest/dicerolls'));
        $builder->keyId();
        $builder->keyText('coinname', 'Coin');
        $builder->keyText('call', 'call');
        $builder->keyText('number', 'Number');
        $builder->keyText('userid', 'Userid');

        $builder->keyStatus('result', 'result', array('NA', 'Won', 'Lost'));
        $builder->keyPrice('amount', 'Amount');
        $builder->keyPrice('winamount', 'winamount');
        $builder->keyText('addtime', 'addtime');
        $builder->setSearchPostUrl(U('Invest/dicerolls'));
        $builder->search('field', 'select', array('id' => 'id', 'userid' => 'userid'));
        $builder->search('name', 'text', 'Enter search content');
        $builder->data($data);
        $builder->pagination($count, $r, $parameter);
        $builder->display();
    }

}