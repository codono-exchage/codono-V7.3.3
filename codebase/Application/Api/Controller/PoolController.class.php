<?php



namespace Api\Controller;


class PoolController extends CommonController
{
    const ALLOW_DEPOSIT_ENTRY = 0; // allow to show deposit entry in deposit table[codono_myzr] of coin with hash

    public function __construct()
    {
        if (POOL_ALLOWED == 0) {
            die('Unauthorized!');
        }
        parent::__construct();

    }

    public function index()
    {
        $uid=$this->userid();
        $pools = $populars = [];
		$list = M('Pool')->where(['status' => 1])->select();
        
		$count = count($list);
        switch ($count) {
            case 1:
                $class = "col-md-12";
                break;
            case 2:
                $class = "col-md-6";
                break;
            case 3:
                $class = "col-md-4";
                break;
            case 4:
                $class = "col-md-3";
                break;

            default:
                $class = "col-md-3";
        }
        $user_balances =  M()->table('codono_user_coin')->where(['userid' => $uid])->find();
        foreach ($list as $pool) {
            $pool['ico'] = $pool['ico'] ?: 'default.png';

            $pool['coins_icon'][] = $this->getCoinImage($pool['coinname']);
            $pool['coins_icon'][] = $this->getCoinImage($pool['getcoin']);
            $pool['coins_icon'][] = $this->getCoinImage($pool['chargecoin']);
			$pool['balace_available']=$user_balances[$pool['coinname']];
			
            if ($pool['is_popular'] == 1) {
                $populars[] = $pool;
            }
            $pools[] = $pool;
        }

        
        $data['class']=$class;
        $data['list']=$pools;
        $data['populars']=$populars;
        $this->ajaxShow($data);

    }

    private function getCoinImage($name)
    {
        if ($name) {
            return C('Coin')[$name]['img'] ?: false;
        }
        return false;
    }

    /*
    *Renting Process
    *num:amount
    *id:poolid
    */
    public function rentMachine()
    {
		$uid=$this->userid();
		
        $input = I('post.');
        if (!check($input['num'], 'd')) {
            $this->error(L('Quantity wrong format!'));
        }

        if ($input['num'] < 1) {
            $this->error(L('Quantity wrong!'));
        }
        if (!check($input['num'], 'd')) {
            $this->error(L('Quantity wrong!'));
        }

        if (!check($input['id'], 'd')) {
            $this->error(L('Mining machine type format error!'));
        }
        $last_stock = S('pool_avail_' . $input['id']);


        $mo=M();
        $pool = $mo->table('codono_pool')->where(['id' => $input['id']])->find();
        $coinname = strtolower($pool['coinname']);
        $UserCoin = $mo->table('codono_user_coin')->where(['userid' => $uid])->find();
        $user_coin_balance = $UserCoin[$coinname];
        if (!$pool) {
            $this->error(L('No Mining Machine Found!'));
        }

        if ($pool['status'] != 1) {
            $this->error(L('Mining Machine is not currently active!'));
        }

        $total_required = format_num($pool['price'] * $input['num']);

        if ($user_coin_balance < $total_required) {
            $this->error(L('Not Enough balance available'));
        }

        $user_boughts = M('PoolLog')->where(['userid' => $uid, 'name' => $pool['name']])->sum('num');

        if ($pool['stocks'] < 1) {
            $this->error(L('We currently do not have Enough Mining Machines!'));
        }

        if ($pool['user_limit'] && ($pool['user_limit'] < ($user_boughts + $input['num']))) {
            $this->error("You have already bought $user_boughts machines of same !");
        }
        $num_a = $UserCoin[$coinname];
        $num_b = $UserCoin[$coinname . 'd'];
        $num = bcadd($num_a, $num_b, 8);
        $mum_a = bcsub($num_a, $total_required, 8);
        $mum_b = $num_b;
        $mum = bcadd($mum_a, $mum_b, 8);
        $hash = hash('sha1', md5(SHORT_NAME . $uid . time() . $pool['id'] . $coinname . $total_required . rand(10000, 99999)));


        $mo->startTrans();

        $rs = [];
        try{
        $rs[] = $mo->table('codono_pool')->where(['id' => $pool['id']])->setDec('stocks', $input['num']);

        $rs[] = $mo->table('codono_user_coin')->where(['userid' => $uid])->setDec($coinname, $total_required);


        $rs[] = $poollogid = $mo->table('codono_pool_log')->add(['poolid' => $pool['id'], 'userid' => $uid, 'coinname' => $pool['coinname'], 'getcoin' => $pool['getcoin'], 'name' => $pool['name'], 'ico' => $pool['ico'], 'price' => $pool['price'], 'num' => $input['num'], 'days' => $pool['days'], 'daily_profit' => $pool['daily_profit'], 'power' => $pool['power'], 'endtime' => time(), 'addtime' => time(), 'status' => 0, 'collected' => 0]);
        $rs[]=  $this->insertPoolFee($uid,$pool['id'], $poollogid, $total_required, $coinname, 1);

        // Finance Entry
        $finance_array = ['userid' => $uid, 'coinname' => $coinname, 'num_a' => $num_a, 'num_b' => $num_b, 'num' => $num, 'fee' => 0, 'type' => 2, 'name' => 'Mining Rent', 'nameid' => $poollogid, 'remark' => 'pool_rent', 'move' => $hash, 'addtime' => time(), 'status' => 1, 'mum' => $mum, 'mum_a' => $mum_a, 'mum_b' => $mum_b];
        $rs[] = $mo->table('codono_finance')->add($finance_array);

        }catch(\Exception $exception){
            clog('Pool_rentMachine',$exception->getMessage());
            $mo->rollback();
            $this->error(L('Could not rent the machine!'));
        }
        if (strict_check_arr($rs)) {
            $mo->commit();
            $last_stock = $last_stock - 1;
            S('pool_avail_' . $pool['id'], $last_stock);
            $this->success(L('Machine Rented!'));
        } else {
            $mo->rollback();
            $this->error(L('Could not rent the machine!'));
        }
    }

    /*
    *Machines that I have rented

    */
    public function myMachines()
    {
        $uid = $this->userid();
		if (!$uid) {
            $this->error('PLEASE_LOGIN');
        }

        $mo=M();        
        import('ORG.Util.Page');
        $pool_ids=[];
        $pool_ids = M('Pool')->where(['status' => 1])->field('id')->select();
        $pool_ids = array_column($pool_ids, 'id');
        $where['userid'] = $uid;
        $where['poolid'] = array('in', $pool_ids);
        $PoolLog = M('PoolLog');
        $count = $PoolLog->where($where)->count();
        
        $list = $PoolLog->where($where)->order('id desc')->limit(20)->select();
		
        $pool_list = [];
        foreach ($list as $poolLog) {
            $poolLog['ready'] = 0;
            if ($poolLog['days'] <= $poolLog['collected']) {
                $poolLog['ready'] = 1;
            }
            $start_time = $poolLog['addtime'];
            $poolLog['server_time'] = gmdate("m-d-Y H:i:s", time());
            $now = time();
            //         $days = $poolLog['days'];
            $next_collection_day = $poolLog['collected']+1;
            $next_collection_stamp = bcmul($next_collection_day, 86400);
            $next_collection_day_stamp = bcadd($start_time, $next_collection_stamp);
            $diff = 0;
            if ($now < $next_collection_day_stamp) {

                $diff = bcsub($next_collection_day_stamp, $now);
                //    $time_left = gmdate("m-d-Y H:i:s", $next_collection_day_stamp);
            } else {
                $diff = bcsub($next_collection_day_stamp, $now);
                $poolLog['ready'] = 2;
            }
            $poolLog['pool'] = M('Pool')->where(['id' => $poolLog['poolid']])->find();
            $poolLog['wait_time'] = $diff;
            $pool_log_list[] = $poolLog;
        }

        $userBalance = $mo->table('codono_user_coin')->where(['userid' => $uid])->find();

        $data['list']=$pool_log_list;
        $data['userBalance']=$userBalance;
        
        $this->ajaxShow($data);
    }

    public function myRewards()
    {
		$uid=$this->userid();
        if (!$uid) {
            $this->error('PLEASE_LOGIN');
        }
		
        $where['userid'] = $uid;
        import('ORG.Util.Page');
        $PoolRewards = M('PoolRewards');
        $count = $PoolRewards->where($where)->count();
        
        $list = $PoolRewards->where($where)->order('id desc')->limit(20)->select();
        $data['list']=$list;
        $this->ajaxShow($data);
    }

    /**
     *
     */
    public function startMachine()
    {
        $uid=$this->userid();
        if (!$uid) {
            $this->error(L('PLEASE_LOGIN'));
        }
        $input = I('post.');
        if (IS_POST) {
            
            if (!check($input['id'], 'd')) {
                $this->error(L('Please select mining machine to work!'));
            }
            $mo=M();
            
            $rs = [];
            $poolLog = M('PoolLog')->where(['id' => $input['id'],'userid'=>$uid])->find();
            
            $pool = $mo->table('codono_pool')->where(['id' => $poolLog['poolid']])->find();
            
            if(!$pool || empty($pool) || $pool['status']!=1 || $pool['id'] !=  $poolLog['poolid']){
                $this->error('Mining machine does not exist any more!');
            }
            if (!$poolLog) {
                $this->error(L('INCORRECT_REQ'));
            }

            if ($poolLog['status'] == 1) {
                $this->error(L('Access error!'));
            }


            if ($poolLog['userid'] != $uid) {
                $this->error(L('Unauthorized access'));
            }
            //@todo mum is not being used
            $mum = bcmul($poolLog['price'], $poolLog['num'], 8);
            
            $mo->startTrans();
            
            $rs[] = $mo->table('codono_pool_log')->where(['id' => $poolLog['id'],'userid'=>$uid])->save(['endtime' => time(), 'status' => 1]);

            if (check_arr($rs)) {
                $mo->commit();

                $this->success(L('Mining machine has started to work!'));
            } else {
                $mo->rollback();
                if (M_DEBUG == 1) {
                    clog("Mining", implode('|', $rs));
                }
                $this->error(L('Mining machine failed to work!'));
            }
        } else {
            $this->error(L('Please select mining machine to work!'));
        }

    }


// Helper function to validate and load the pool log
    private function validateAndLoadPoolLog($poolLogId, $uid)
    {
        $poolLog = M('PoolLog')->where(['id' => $poolLogId, 'userid' => $uid, 'status' => 1])->find();

        if (!$poolLog || $poolLog['userid'] != $uid) {
            $this->error(L('INCORRECT_REQ'));
        }

        return $poolLog;
    }

    public function claimReward()
    {

        $uid = $this->userid();

        if (!$uid) {
            $this->error(L('PLEASE_LOGIN'));
        }

        if (!IS_POST) {
            $this->error(L('INVALID REQUEST'));
        }

        $input = I('post.');
        $poolLogId = intval($input['id']);

        if (!$poolLogId || $poolLogId <= 0) {
            $this->error(L('Please choose your mining machine correctly!'));
        }
            



        $poolLog = $this->validateAndLoadPoolLog($poolLogId, $uid);
        $mo = M();
        $pool = $mo->table('codono_pool')->where(['id' => $poolLog['poolid']])->find();
        if(!$pool || empty($pool) || $pool['status']!=1 || $pool['id'] !=  $poolLog['poolid']){
            $this->error('Mining machine does not exist any more!');
        }
        if (!$poolLog || $poolLog['userid'] != $uid) {
                $this->error(L('INCORRECT_REQ'));
            }

            
            

            $charge_coin = $pool['charge_coin'];
            $charge_price = bcmul($pool['charge_price'],$pool['daily_profit'],8);



            $userBalance = $mo->table('codono_user_coin')->where(['userid' => $uid])->find();
            
            $flag_charge = 0;

            //Mining duration completed  now change status =2 and release and free the mining machine
            if ($poolLog['days'] <= $poolLog['collected']) {
                $this->releaseMachine($poolLog['id']);
                $this->success(L('You have successfully used this mining machine!'));
            }
            /*
            //Logic of Checking when was last claimed
            $last_Reward= M('PoolRewards')->where(array('userid'=>$uid,'poolid'=>$poolLog['poolid']))->max('addtime');
            //Reward was previously used by miner check if  got anything in past 24 hours?
            if(isset($last_Reward) && $last_Reward>1 ){
                $remaining = bcsub(time() , $last_Reward);
                if($remaining<86400){
                    $diff=bcsub(86400,$remaining);
                    $time_left=gmdate("H:i:s", $diff);
                    $this->error("Please wait ".$time_left." before claiming rewards");
                }
            }
            */
            //Logic of when was it bought ,how many days passed and how many times it got collected

            $start_time = $poolLog['addtime'];
            $now = time();
            $days = $poolLog['days'];
            $next_collection_day = $poolLog['collected'];
            $next_collection_stamp = bcmul($next_collection_day, 86400);
            $next_collection_day_stamp = bcadd($start_time, $next_collection_stamp);
            if ($now < $next_collection_day_stamp) {

                $diff = bcsub($next_collection_day_stamp, $now);
                $time_left = gmdate("m-d-Y H:i:s", $next_collection_day_stamp);
                $this->error("Please wait " . $time_left . " before claiming rewards");
            }


            $mo->startTrans();

            $user_coin_bal = $mo->table('codono_user_coin')->where(['userid' => $uid])->find();
            if ($charge_coin && $charge_price > 0) {
                $flag_charge = 1;
            }
            if ($flag_charge && $charge_price > $userBalance[$charge_coin]) {
                $this->error(L('Not Enough Balance : Needs ') . NumToStr($charge_price ). ' ' . $charge_coin);
            }
            $getcoin = $poolLog['getcoin'];
            $daily_profit_num = bcmul($poolLog['daily_profit'], $poolLog['num'], 8);


            $num_a = $user_coin_bal[$getcoin];
            $num_b = $user_coin_bal[$getcoin . 'd'];
            $num = bcadd($num_a, $num_b, 8);
            $mum_a = bcadd($num_a, $daily_profit_num, 8);
            $mum_b = $num_b;
            $mum = bcadd($mum_a, $mum_b, 8);


            $coin_info = C('coin')[$getcoin];
            $coin_type = $coin_info['type'];
            $from_name = "mining-" . $getcoin . '-' . $poolLog['poolid'];
            $rs = [];

            $hash = hash('sha1', md5(SHORT_NAME . $uid . time() . $poolLog['id'] . $getcoin . $daily_profit_num . rand(10000, 99999)));
            $rs[] = $mo->table('codono_user_coin')->where(['userid' => $uid])->setInc($getcoin, $daily_profit_num);
            $rs[] = $mo->table('codono_pool_log')->where(['id' => $poolLog['id']])->save(['collected' => $poolLog['collected'] + 1, 'endtime' => time()]);

            //deposit entry
            if (self::ALLOW_DEPOSIT_ENTRY == 1) {
                $rs[] = M('myzr')->add(['userid' => $uid, 'type' => $coin_type, 'username' => $from_name, 'coinname' => $getcoin, 'fee' => 0, 'txid' => "mine_" . $hash, 'num' => $daily_profit_num, "mum" => $daily_profit_num, 'addtime' => time(), 'status' => 1]);
            }
            // Finance Entry
            $finance_array = ['userid' => $uid, 'coinname' => $getcoin, 'num_a' => $num_a, 'num_b' => $num_b, 'num' => $num, 'fee' => 0, 'type' => 1, 'name' => 'Mining Income', 'nameid' => $poolLog['id'], 'remark' => 'pool_reward', 'move' => $hash, 'addtime' => time(), 'status' => 1, 'mum' => $mum, 'mum_a' => $mum_a, 'mum_b' => $mum_b];
            $rs[] = $mo->table('codono_finance')->add($finance_array);
            if ($flag_charge) {
                $num_a = $user_coin_bal[$charge_coin];
                $num_b = $user_coin_bal[$charge_coin . 'd'];
                $num = bcadd($num_a, $num_b, 8);
                $mum_a = bcsub($num_a, $charge_price, 8);
                $mum_b = $num_b;
                $mum = bcadd($mum_a, $mum_b, 8);
                $hash = hash('sha1', md5(SHORT_NAME . $uid . time() . $charge_coin . $charge_price . rand(10000, 99999)));
                $finance_array = ['userid' => $uid, 'coinname' => $charge_coin, 'num_a' => $num_a, 'num_b' => $num_b, 'num' => $num, 'fee' => 0, 'type' => 2, 'name' => 'Mining Release', 'nameid' => $poolLog['id'], 'remark' => 'pool_reward', 'move' => $hash, 'addtime' => time(), 'status' => 1, 'mum' => $mum, 'mum_a' => $mum_a, 'mum_b' => $mum_b];
                $rs[] = $mo->table('codono_user_coin')->where(['userid' => $uid])->setDec($charge_coin, $charge_price);
                $rs[] = $mo->table('codono_finance')->add($finance_array);
                $rs[]=$this->insertPoolFee($uid,$poolLog['poolid'],$poolLog['id'], $charge_price, $charge_coin, 2);
            }
            //PoolRewards entry
            $reward_array = ['poolid' => $poolLog['poolid'], 'userid' => $poolLog['userid'], 'logid' => $poolLog['id'], 'coinname' => $getcoin, 'amount' => $daily_profit_num, 'addtime' => time(), 'hash' => $hash];

            $rs[] = $mo->table('codono_pool_rewards')->add($reward_array);

            if ($poolLog['days'] <= $poolLog['collected'] + 1) {
                //Pool completes now change status =2
                $rs[] = $mo->table('codono_pool_log')->where(['id' => $poolLog['id']])->save(['status' => 2]);

                // Release the resorce and Increase mine machine quantity by 1
                $rs[] = $mo->table('codono_pool')->where(['id' => $poolLog['poolid']])->setInc('stocks', 1);

            }

            if (check_arr($rs)) {
                $mo->commit();
                $this->success(L('Mining rewards succesfully sent to your spot account! '));
            } else {
                $mo->rollback();
                $this->error(L('There are difficulties gathering mining rewards!'));
            }

    }

    private function releaseMachine($poolLog_id): void
    {
        if (!check($poolLog_id, 'd')) {
            return;
        }
        $poolLog = M('PoolLog')->where(['id' => $poolLog_id])->find();
        if (!$poolLog['id']) {
            return;
        }
        $rs = [];
        $mo = M();
        $mo->startTrans();
        //Pool completes now change status =2
        $rs[] = $mo->table('codono_pool_log')->where(['id' => $poolLog['id']])->save(['status' => 2]);

        // Release the resorce and Increase mine machine quantity by 1
        $rs[] = $mo->table('codono_pool')->where(['id' => $poolLog['poolid']])->setInc('stocks', 1);

        if (check_arr($rs)) {
            $mo->commit();

        } else {
            $mo->rollback();
        }
    }

    /**
     * Insert an entry into the codono_pool_fees table.
     *
     * @param int $poolId      The ID of the pool associated with the fee.
     * @param int $rentId      The ID of the rental associated with the fee.
     * @param float $amount    The amount of the fee.
     * @param string $coin     The coin associated with the fee.
     * @param int $type        The type of fee (0=unknown, 1=rent, 2=income).
     * @param int $addtime     The timestamp for when the fee is added (optional).
     * @return bool            True if the insertion is successful, false otherwise.
     */
    private function insertPoolFee($userid,$poolId, $rentId, $amount, $coin, $type, $addtime = null)
    {
        // Create a data array for insertion
        $data = [
            'userid'=>$userid,
            'pool_id' => $poolId,
            'rent_id' => $rentId,
            'amount' => $amount,
            'coin' => $coin,
            'type' => $type,
        ];

        // Set the addtime if provided, or use the current timestamp
        if ($addtime !== null) {
            $data['addtime'] = $addtime;
        } else {
            $data['addtime'] = time();
        }

        // Insert the data into the codono_pool_fees table
        $mo=M();
        $result = $mo->table('codono_pool_fees')->add($data);

        return $result !== false; // Return true if the insertion is successful
    }

}