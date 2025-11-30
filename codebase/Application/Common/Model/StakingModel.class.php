<?php

namespace Common\Model;
use Think\Exception;
class StakingModel extends \Think\Model
{
    protected $keyS = 'Staking';
    public const CONSUME_STAKING_BALANCE = USE_STAKING_BALANCE?:0;
	private function stakinginfo($id){

        return M('Staking')->where(['id'=>$id])->find();
	}
    public function withdraw($id = NULL)
    {
        if (!check($id, 'd')) {
            return ['0', 'Parameter error'];
        }
		$userid=userid();
		
        $ibl = M('StakingLog')->where(['id' => $id,'userid'=>$userid])->find();
		
        if (!$ibl || $ibl['status']!=1) {
            return ['0', 'Invalid status of investment !'];
        }
        
		$staking_info=$this->stakinginfo($ibl['staking_id']);
            
        if(!$staking_info)
            {
                return ['0', 'No such investment plan exists!'];
            }
        //todo check if penalty applicable
        

		$mo = M();
        $rs = [];
		$mo->startTrans();
        
        try {
            $refund = format_num($ibl['amount'], 8);

			$coinname= strtolower($staking_info['coinname']);
            $coin_check = strtolower($staking_info['coinname']);
            if (self::CONSUME_STAKING_BALANCE == 1) {
                $current_balance = $this->coinBalance($coin_check);
                $freeze_balance = 0;// find
                $require_wallet = 'staking';
            } else {
                $user_coin = M('UserCoin')->where(['userid' => $userid])->find();
                $current_balance = $user_coin[$coin_check];
                $freeze_balance = $user_coin[$coin_check . 'd'];
                $require_wallet = 'spot';
            }

             $penalty=$this->managePenalty($staking_info);
             if(is_array($penalty) && $penalty['penalty']>0 && $penalty['coin']){

                 $userbalance = M('UserCoin')->where(['userid' => $userid])->getField($penalty['coin']);
                 if($userbalance<$penalty['penalty_amount']){
                     $mo->rollback();
                     return ['0', 'You need ' .$penalty['coin'].' '.$penalty['penalty_amount']." ,Your spot balance is $userbalance"];
                 }
                 //need to deduct the penalty
                 $rs[] = $mo->table('codono_user_coin')->where(['userid' => $userid])->setDec($penalty['coin'], $penalty['penalty']);
                 $rs[]=  $mo->table('codono_wallet_income')->add(
                     [
                         'userid' => $userid,
                         'tid'=>$ibl['id'],
                         'amount'=>$penalty['penalty'],
                         'coin'=>$penalty['coin'],
                         'module'=>'staking_penalty',
                         'addtime' => time(),
                         'status' => 1
                     ]
                 );

             }
			$num_a=$current_balance;
			$num_b=$freeze_balance;
			$num=bcadd($num_a,$num_b,8);
			
			
            $mum_a=bcadd($num_a,$refund,8);
			$mum_b=$num_b;
			
			$mum=bcadd($mum_a,$mum_b,8);
			
            if (0 < $refund) {
               // $rs[] = $mo->table('codono_user_coin')->where(['userid' => $ibl['userid']])->setInc($coinname, $refund);
                if (self::CONSUME_STAKING_BALANCE == 1) {
                    $condition = ['uid' => $userid, 'coin' => $coinname,'account'=>4];
                    $found = $mo->table('codono_user_assets')->where($condition)->find();
                    if(!$found) {
                        $rs[]=$mo->table('codono_user_assets')->add($condition);
                        $rs[] = $mo->table('codono_user_assets')->where($condition)->setInc('balance', $refund);
                    } else {
                        $rs[] = $mo->table('codono_user_assets')->where($condition)->setInc('balance', $refund);
                    }
                }else {
                    $rs[] = $mo->table('codono_user_coin')->where(['userid' => $userid])->setInc($coinname, $refund);
                }
            }
            $move_stamp='0_'.$ibl['docid'];
            $rs[] = $mo->table('codono_staking_log')->where(['id' => $ibl['id']])->save(['status'=> 0,'withdrawn'=>time(),'credited'=>$refund]);
            $rs[] = $mo->table('codono_finance')->add(['userid' => $userid, 'coinname' => $coinname, 'num_a' => $num_a, 'num_b' => $num_b, 'num' => $num , 'fee' => $refund, 'type' => 1, 'name' => 'staking', 'nameid' => $ibl['id'], 'remark' => 'staking_create',  'move' => $move_stamp, 'addtime' => time(), 'status' => 1,'mum'=>$mum,'mum_a'=>$mum_a,'mum_b'=>$mum_b]);
        }  catch (Exception $exception) {
            // Rollback transaction in case of error
                clog('createStaking',$exception->getMessage());
                $mo->rollback();
                return ['1', 'Failed to Withdraw'];
        }

        if (strict_check_arr($rs)) {
            $mo->commit();
            return ['1', 'Staking has been withdrawn!'];
        } else {
            $mo->rollback();
            return ['0', 'Staking could not be withdrawn!|' . implode('|', $rs)];
        }
    }
    private function managePenalty($staking_info){
        if($staking_info['penalty_coin'] && $staking_info['penalty_amount']>0){
            $coin=$staking_info['penalty_coin'];
            $penalty=$staking_info['penalty_amount'];
           /* $userbalance = M('UserCoin')->where(['userid' => $userid])->getField($coin);
            if($userbalance<$penalty){
                $this->error("You need $coin $penalty ,Your spot balance is $userbalance");
            }
           */
            return ['coin'=>$coin,'penalty'=>$penalty];
        }else{
            return false;
        }
    }
    private function coinBalance($coinname)
    {
        $coinname = strtolower($coinname);

        if ($coinname != C('coin')[$coinname]['name']) {
            return 0;
        }

        if (self::CONSUME_STAKING_BALANCE == 1) {
            $mo=M('UserAssets');
            $info = $mo->where(['uid' => userid(), 'account'=>4,'coin' => $coinname])->find();
            return $info['balance'] ?: 0;
        } else {
            $Model = M('UserCoin');
            return $Model->where(['userid' => userid()])->getField($coinname);
        }

    }
}
