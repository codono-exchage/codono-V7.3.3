<?php

namespace Common\Model;

class InvestboxModel extends \Think\Model
{
    protected $keyS = 'Invest';
	private function investinfo($id){

        return M('investbox')->where(array('id'=>$id))->find();
	}
    public function withdraw($id = NULL)
    {
        if (!check($id, 'd')) {
            return array('0', 'Parameter error');
        }
		$userid=userid();
		
        $ibl = M('InvestboxLog')->where(array('id' => $id,'userid'=>$userid))->find();
		
        if (!$ibl) {
            return array('0', 'Investment does not exist');
        }

		$mo = M();
        $rs = array();
	
         if ($ibl['status'] == 1) {
            $refund = format_num($ibl['amount'], 8);

			$invest_info=$this->investinfo($ibl['boxid']);
			 if(!$invest_info)
			 {
				 return array('0', 'No such investment plan exists!');
			 }
			$coinname= strtolower($invest_info['coinname']);
			$coinnamed= strtolower($invest_info['coinname'].'d');
			
             $query="SELECT `$coinname`,`$coinnamed` FROM `codono_user_coin` WHERE `userid` = $userid";
             $res_bal=$mo->query($query);
             $user_coin_bal = $res_bal[0];

			$num_a=$user_coin_bal[$coinname];
			$num_b=$user_coin_bal[$coinnamed];
			$num=bcadd($num_a,$num_b,8);
			
			
            $mum_a=bcadd($num_a,$refund,8);
			$mum_b=$num_b;
			
			$mum=bcadd($mum_a,$mum_b,8);
			
			$mo->startTrans();
            if (0 < $refund) {
                $rs[] = $mo->table('codono_user_coin')->where(array('userid' => $ibl['userid']))->setInc($coinname, $refund);
            }
            $move_stamp='0_'.$ibl['docid'];
            $rs[] = $mo->table('codono_investbox_log')->where(array('id' => $ibl['id']))->save(array('status'=> 0,'withdrawn'=>time(),'credited'=>$refund));
            $rs[] = $mo->table('codono_finance')->add(array('userid' => $userid, 'coinname' => $coinname, 'num_a' => $num_a, 'num_b' => $num_b, 'num' => $num , 'fee' => $refund, 'type' => 1, 'name' => 'investbox', 'nameid' => $ibl['id'], 'remark' => 'InvestBoxInvest',  'move' => $move_stamp, 'addtime' => time(), 'status' => 1,'mum'=>$mum,'mum_a'=>$mum_a,'mum_b'=>$mum_b));
        } else {
            $mo->rollback();
            return array('0', 'Invalid status of investment !');
        }

        if (strict_check_arr($rs)) {
            $mo->commit();
            
            return array('1', 'Investment has been withdrawn!');
        } else {
            $mo->rollback();
            
            return array('0', 'Investment coult not be withdrawn!|' . implode('|', $rs));
        }
    }

}
