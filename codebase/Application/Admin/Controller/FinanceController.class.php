<?php

namespace Admin\Controller;

use Think\Page;
use Think\Upload;

class FinanceController extends AdminController
{
    public function index($field = NULL, $name = NULL,$type='')
    {
        //$this->checkUpdata();
        $where = [];
		if (isset($type) && $type!=''){
				$where['type'] = $type;
		}
        if ($field && $name) {
            if ($field == 'username') {
                $where['userid'] = M('User')->where(['username' => $name])->getField('id');
            } else {
                $where[$field] = $name;
            }
        }

        $count = M('Finance')->where($where)->count();
        $Page = new Page($count, 15);
        $show = $Page->show();
        $list = M('Finance')->where($where)->order('id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();
        
        

        foreach ($list as $k => $v) {
            $list[$k]['username'] = M('User')->where(['id' => $v['userid']])->getField('username');
            $list[$k]['num_a'] = Num($v['num_a']);
            $list[$k]['num_b'] = Num($v['num_b']);
            $list[$k]['num'] = Num($v['num']);
            $list[$k]['fee'] = Num($v['fee']);
            $list[$k]['type'] = $v['type'];
            $list[$k]['name'] = $this->findNameList($v['name']);
            $list[$k]['nameid'] = ($this->findNameList($v['name']) ? $v['name'] . '/' . $v['nameid'] : '');
            $list[$k]['mum_a'] = Num($v['mum_a']);
            $list[$k]['mum_b'] = Num($v['mum_b']);
            $list[$k]['mum'] = Num($v['mum']);
            $list[$k]['addtime'] = addtime($v['addtime']);
        }

        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->display();
    }


    public function mycz($field = NULL, $name = NULL, $status = NULL)
    {
        $where = [];

        if ($field && $name) {
            if ($field == 'username') {
                $where['userid'] = M('User')->where(['username' => ['like', '%' . $name . '%']])->getField('id');
            } else {
                $where[$field] = $name;
            }
        }
		
		switch($status){
			case 1;
			$where['status']=0;
			break;
			case 2;
			$where['status']=2;
			break;
			case 3;
			$where['status']=3;
			break;
			case 4;
			$where['status']=4;
			break;
			case 5;
			$where['status']=5;
			break;
			case 6:
			$where['status']=6;//array('neq',5);
			break;
			default:
			$where['status']= ['neq',5];
			
		}
        $count = M('Mycz')->where($where)->count();
        $Page = new Page($count, 15);
        $show = $Page->show();
        $list = M('Mycz')->where($where)->order('id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();

        foreach ($list as $k => $v) {
            $list[$k]['username'] = M('User')->where(['id' => $v['userid']])->getField('username');
            $list[$k]['type'] = M('MyczType')->where(['id' => $v['type']])->getField('title');
        }

        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->display();
    }

    public function myczStatus($id = NULL, $type = NULL)
    {
        if (APP_DEMO) {
            $this->error(L('SYSTEM_IN_DEMO_MODE'));
        }
        $model = 'Mycz';
        $this->sub_status($id, $type, $model);
    }


    public function myzrConfirm()
    {
        $id = intval($_GET['id']);

        if (empty($id)) {
            $this->error('please chooseData to be operated!');
        }

        $myzr = M('Myzr')->where(['id' => $id])->find();

        if (($myzr['status'] == 1)) {
            $this->error('Has been processed, prohibit the operation again!');
        }

        $mo = M();
        $mo->startTrans();
        $rs = [];

        $rs[] = $mo->table('codono_user_coin')->where(['userid' => $myzr['userid']])->setInc($myzr['coinname'], $myzr['num']);
        $rs[] = $mo->table('codono_myzr')->where(['id' => $myzr['id']])->save(['status' => 1, 'mum' => $myzr['num'], 'endtime' => time()]);

        $cz_mes = "Treatment success";

        if (check_arr($rs)) {
            $mo->commit();
            $this->success($cz_mes);
        } else {
            $mo->rollback();
            $this->error(L('OPERATION_FAILED'));
        }
    }


        public function myczConfirm()
    {
        $id = intval($_GET['id']);

        if (empty($id)) {
            $this->error('Choose correct deposit request!');
        }

        $mycz = M('Mycz')->where(['id' => $id])->find();
        $coin = strtolower($mycz['coin']);
        $coind = $coin . 'd';
        if (($mycz['status'] != 0) && ($mycz['status'] != 3) && ($mycz['status'] != 4)) {
            $this->error('Status of this payment does not allow it to be processed!');
        }
		
		
		$fee_amt=0;
		
		$myczType=M('MyczType')->where(['id' => $mycz['type']])->find();
		
		$receivable_after_fees=$mycz['num'];
		
		if($myczType['extra'] && $myczType['extra']>0){
			$fees_percent=$myczType['extra']?$myczType['extra']:0;
			$fee_amt=bcmul($receivable_after_fees,$fees_percent,8);
			$just_per=bcdiv(bcsub(100,$fees_percent,8),100,8);
			$receivable_after_fees=bcmul($receivable_after_fees,$just_per,2);
		}
		
		
		
        $mo = M();
        $mo->startTrans();
        $rs = [];
		
		
		
        $finance = $mo->table('codono_finance')->where(['userid' => $mycz['userid']])->order('id desc')->find();
        $finance_num_user_coin = $mo->table('codono_user_coin')->where(['userid' => $mycz['userid']])->find();
        $rs[] = $mo->table('codono_user_coin')->where(['userid' => $mycz['userid']])->setInc($coin, $receivable_after_fees);
        $rs[] = $mo->table('codono_mycz')->where(['id' => $mycz['id']])->save(['status' => 2, 'mum' => $receivable_after_fees, 'endtime' => time()]);
        $finance_mum_user_coin = $mo->table('codono_user_coin')->where(['userid' => $mycz['userid']])->find();
        $finance_hash = md5($mycz['userid'] . time());
        $finance_num = $finance_num_user_coin[$coin] + $finance_num_user_coin[$coind];

        if ($finance['mum'] < $finance_num) {
            $finance_status = (1 < ($finance_num - $finance['mum']) ? 0 : 1);
        } else {
            $finance_status = (1 < ($finance['mum'] - $finance_num) ? 0 : 1);
        }

        $rs[] = $mo->table('codono_finance')->add(['userid' => $mycz['userid'], 'coinname' => $coin, 'num_a' => $finance_num_user_coin[$coin], 'num_b' => $finance_num_user_coin[$coind], 'num' => $finance_num_user_coin[$coin] + $finance_num_user_coin[$coind], 'fee' => $fee_amt, 'type' => 1, 'name' => 'mycz', 'nameid' => $mycz['id'], 'remark' => 'Fiat Recharge-Admin Approval', 'mum_a' => $finance_mum_user_coin[$coin], 'mum_b' => $finance_mum_user_coin[$coind], 'mum' => $finance_mum_user_coin[$coin] + $finance_mum_user_coin[$coind], 'move' => $finance_hash, 'addtime' => time(), 'status' => $finance_status]);

        $cz_mes = "Success recharge[" . $receivable_after_fees . "].";

        $cur_user_info = $mo->table('codono_user')->where(['id' => $mycz['userid']])->find();
        //invit_1  invit_2  invit_3  WithmumPrevail  The amount is credited into account
        //Promotion commission, a promotion, a lifetime on commission    Recharge amount of offline bonus0.6%Three dividends.    generation0.3%      II0.2%      3rd Tier0.1%
        $cz_jiner = $receivable_after_fees;
        if ($cur_user_info['invit_1'] && $cur_user_info['invit_1'] > 0 && 1 == 2) {
            //The presence of a promoter
            $invit_1_jiner = round(($cz_jiner / 100) * 0.3, 6);

            if ($invit_1_jiner) {
                //deal withbeforeinformation
                $finance_1 = $mo->table('codono_finance')->where(['userid' => $cur_user_info['invit_1']])->order('id desc')->find();
                $finance_num_user_coin_1 = $mo->table('codono_user_coin')->where(['userid' => $cur_user_info['invit_1']])->find();

                //Startdeal with
                $rs[] = $mo->table('codono_user_coin')->where(['userid' => $cur_user_info['invit_1']])->setInc($coin, $invit_1_jiner);
                $rs[] = $mo->table('codono_invit')->add(['coin'=>$coin,'userid' => $cur_user_info['invit_1'], 'invit' => $mycz['userid'], 'name' => $coin, 'type' => '1st Tier Bonus', 'num' => $cz_jiner, 'mum' => $cz_jiner, 'fee' => $invit_1_jiner, 'addtime' => time(), 'status' => 1]);

                //deal withRear
                $finance_mum_user_coin_1 = $mo->table('codono_user_coin')->where(['userid' => $cur_user_info['invit_1']])->find();
                $finance_hash_1 = md5($cur_user_info['invit_1'] . $finance_num_user_coin_1[$coin] . $finance_num_user_coin_1[$coind] . $invit_1_jiner . $finance_mum_user_coin_1[$coin] . $finance_mum_user_coin_1[$coind] . CODONOLIC . 'auth.codono.com');
                $finance_num_1 = $finance_num_user_coin_1[$coin] + $finance_num_user_coin_1[$coind];

                if ($finance_1['mum'] < $finance_num_1) {
                    $finance_status_1 = (1 < ($finance_num_1 - $finance_1['mum']) ? 0 : 1);
                } else {
                    $finance_status_1 = (1 < ($finance_1['mum'] - $finance_num_1) ? 0 : 1);
                }

                $rs[] = $mo->table('codono_finance')->add(['userid' => $cur_user_info['invit_1'], 'coinname' => $coin, 'num_a' => $finance_num_user_coin_1[$coin], 'num_b' => $finance_num_user_coin_1[$coind], 'num' => $finance_num_user_coin_1[$coin] + $finance_num_user_coin_1[$coind], 'fee' => $invit_1_jiner, 'type' => 1, 'name' => 'mycz', 'nameid' => $cur_user_info['invit_1'], 'remark' => 'Bonus from Fiat recharge of USERID:' . $mycz['userid'] . ',Order#' . $mycz['tradeno'] . ',Amount:' . $cz_jiner . ' ,Reward:' . $invit_1_jiner . ' ', 'mum_a' => $finance_mum_user_coin_1[$coin], 'mum_b' => $finance_mum_user_coin_1[$coind], 'mum' => $finance_mum_user_coin_1[$coin] + $finance_mum_user_coin_1[$coind], 'move' => $finance_hash_1, 'addtime' => time(), 'status' => $finance_status_1]);

                //deal with End Tips
                $cz_mes = $cz_mes . "Referral Rewards [" . $invit_1_jiner . "].";
            }


        }

        if ($cur_user_info['invit_2'] && $cur_user_info['invit_2'] > 0 && 1 == 2) {
            //The presence of two promoter
            $invit_2_jiner = round(($cz_jiner / 100) * 0.2, 6);
            if ($invit_2_jiner) {

                //deal withbeforeinformation
                $finance_2 = $mo->table('codono_finance')->where(['userid' => $cur_user_info['invit_2']])->order('id desc')->find();
                $finance_num_user_coin_2 = $mo->table('codono_user_coin')->where(['userid' => $cur_user_info['invit_2']])->find();

                //Startdeal with

                $rs[] = $mo->table('codono_user_coin')->where(['userid' => $cur_user_info['invit_2']])->setInc($coin, $invit_2_jiner);
                $rs[] = $mo->table('codono_invit')->add(['coin'=>$coin,'userid' => $cur_user_info['invit_2'], 'invit' => $mycz['userid'], 'name' => $coin, 'type' => 'II top award', 'num' => $cz_jiner, 'mum' => $cz_jiner, 'fee' => $invit_2_jiner, 'addtime' => time(), 'status' => 1]);

                //deal withRear
                $finance_mum_user_coin_2 = $mo->table('codono_user_coin')->where(['userid' => $cur_user_info['invit_2']])->find();
                $finance_hash_2 = md5($cur_user_info['invit_2'] . $finance_num_user_coin_2[$coin] . $finance_num_user_coin_2[$coind] . $invit_2_jiner . $finance_mum_user_coin_2[$coin] . $finance_mum_user_coin_2[$coind] . CODONOLIC . 'auth.codono.com');
                $finance_num_2 = $finance_num_user_coin_2[$coin] + $finance_num_user_coin_2[$coind];

                if ($finance_2['mum'] < $finance_num_2) {
                    $finance_status_2 = (1 < ($finance_num_2 - $finance_2['mum']) ? 0 : 1);
                } else {
                    $finance_status_2 = (1 < ($finance_2['mum'] - $finance_num_2) ? 0 : 1);
                }

                $rs[] = $mo->table('codono_finance')->add(['userid' => $cur_user_info['invit_2'], 'coinname' => $coin, 'num_a' => $finance_num_user_coin_2[$coin], 'num_b' => $finance_num_user_coin_2[$coind], 'num' => $finance_num_user_coin_2[$coin] + $finance_num_user_coin_2[$coind], 'fee' => $invit_2_jiner, 'type' => 1, 'name' => 'mycz', 'nameid' => $cur_user_info['invit_2'], 'remark' => 'Fiat Recharge-2nd Tier Bonus-RechargeID' . $mycz['userid'] . ',Order form' . $mycz['tradeno'] . ',Money' . $cz_jiner . ' fiat,reward' . $invit_2_jiner . ' fiat', 'mum_a' => $finance_mum_user_coin_2[$coin], 'mum_b' => $finance_mum_user_coin_2[$coind], 'mum' => $finance_mum_user_coin_2[$coin] + $finance_mum_user_coin_2[$coind], 'move' => $finance_hash_2, 'addtime' => time(), 'status' => $finance_status_2]);

                //deal withEndTips

                $cz_mes = $cz_mes . "2nd tier referral bonus [" . $invit_2_jiner . "] " . $coin;

            }

        }

        if ($cur_user_info['invit_3'] && $cur_user_info['invit_3'] > 0 && 1 == 2) {
            //The presence of three promoter
            $invit_3_jiner = round(($cz_jiner / 100) * 0.1, 6);
            if ($invit_3_jiner) {

                //deal withbeforeinformation
                $finance_3 = $mo->table('codono_finance')->where(['userid' => $cur_user_info['invit_3']])->order('id desc')->find();
                $finance_num_user_coin_3 = $mo->table('codono_user_coin')->where(['userid' => $cur_user_info['invit_3']])->find();

                //Startdeal with

                $rs[] = $mo->table('codono_user_coin')->where(['userid' => $cur_user_info['invit_3']])->setInc($coin, $invit_3_jiner);
                $rs[] = $mo->table('codono_invit')->add(['coin'=>$coin,'userid' => $cur_user_info['invit_3'], 'invit' => $mycz['userid'], 'name' => $coin, 'type' => '3rd Tier of top awards', 'num' => $cz_jiner, 'mum' => $cz_jiner, 'fee' => $invit_3_jiner, 'addtime' => time(), 'status' => 1]);

                //deal withRear
                $finance_mum_user_coin_3 = $mo->table('codono_user_coin')->where(['userid' => $cur_user_info['invit_3']])->find();
                $finance_hash_3 = md5($cur_user_info['invit_3'] . $finance_num_user_coin_3[$coin] . $finance_num_user_coin_3[$coind] . $invit_3_jiner . $finance_mum_user_coin_3[$coin] . $finance_mum_user_coin_3[$coind] . CODONOLIC . 'auth.codono.com');
                $finance_num_3 = $finance_num_user_coin_3[$coin] + $finance_num_user_coin_3[$coind];

                if ($finance_3['mum'] < $finance_num_3) {
                    $finance_status_3 = (1 < ($finance_num_3 - $finance_3['mum']) ? 0 : 1);
                } else {
                    $finance_status_3 = (1 < ($finance_3['mum'] - $finance_num_3) ? 0 : 1);
                }

                $rs[] = $mo->table('codono_finance')->add(['userid' => $cur_user_info['invit_3'], 'coinname' => $coin, 'num_a' => $finance_num_user_coin_3[$coin], 'num_b' => $finance_num_user_coin_3[$coind], 'num' => $finance_num_user_coin_3[$coin] + $finance_num_user_coin_3[$coind], 'fee' => $invit_3_jiner, 'type' => 1, 'name' => 'mycz', 'nameid' => $cur_user_info['invit_3'], 'remark' => 'Fiat Recharge-3rd Tier Referral Fiat Recharge Bonus-RechargeID' . $mycz['userid'] . ',Order form' . $mycz['tradeno'] . ',Money' . $cz_jiner . ' fiat,reward' . $invit_3_jiner . ' fiat', 'mum_a' => $finance_mum_user_coin_3[$coin], 'mum_b' => $finance_mum_user_coin_3[$coind], 'mum' => $finance_mum_user_coin_3[$coin] + $finance_mum_user_coin_3[$coind], 'move' => $finance_hash_3, 'addtime' => time(), 'status' => $finance_status_3]);

                //deal withEndTips
                $cz_mes = $cz_mes . "3rd Tier Referral Fiat Recharge Bonus [" . $invit_3_jiner . "] " . $coin;
            }

        }


        if (check_arr($rs)) {
			$deposit_time=date('Y-m-d H:i',time()).'('.date_default_timezone_get().')';
		$deposited_amount= $receivable_after_fees;
		$user = M('User')->where(['id' => $mycz['userid']])->find();
		$to_email=$user['email'];
		$subject="Deposit Success Alerts ".$deposit_time;
		$order_ref=$mycz['tradeno'];
		$upcoin=strtoupper($coin);
		
		$content = "<br/><strong>Congratulations your account has been just deposited with $upcoin $deposited_amount!!</strong><br/><br/><br/>Deposit details below ,<br/>
			<table>
			<tr style='border:2px solid black'><td>Email</td><td>$to_email</td></tr>
			<tr style='border:2px solid black'><td>Currency</td><td>$upcoin</td></tr>
			<tr style='border:2px solid black'><td>Amount</td><td>$deposited_amount</td></tr>
			<tr style='border:2px solid black'><td>Order reference</td><td>$order_ref</td></tr>
			<tr style='border:2px solid black'><td>Time</td><td>$deposit_time</td></tr>	
			</table>
			<br/><br/>
			
			<strong>You can start using your balance with immediate effect, Happy Trading!</strong>";
        addnotification($to_email, $subject, $content);
            $mo->commit();
            // removed unlock/lock
            $this->success($cz_mes);
        } else {
            $mo->rollback();
            $this->error(L('OPERATION_FAILED'));
        }
    }

    public function myczType()
    {
        $where = [];
        $count = M('MyczType')->where($where)->count();
        $Page = new Page($count, 15);
        $show = $Page->show();
        $list = M('MyczType')->where($where)->order('id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->display();
    }

    public function fiatwithdrawal($id = NULL)
    {
        $id = intval($_GET['id']);
        $note = "Please place memo and confirm!";
        if (empty($id)) {
            $note = "Select Correct Transaction!";
        }

        $mytx = M('Mytx')->where(['id' => $id])->find();

        if (!$mytx) {
            $note = "No such withdrawal request!";
        }


        if (($mytx['status'] == 1) && ($mytx['status'] == 2)) {
            $note = "Withdrawal has been processed already!";
        }
        $username = M('User')->where(['id' => $mytx['userid']])->getField('username');
        $kyc = M('User')->where(['id' => $mytx['userid']])->getField('idcardauth');
        $this->assign('username', $username);
        $this->assign('kyc', $kyc);
        $this->assign('info', $mytx);
        $this->assign('note', $note);
        $this->display();
    }

    public function fiatwithdrawalConfirm()
    {
        $id = intval($_POST['id']);
        $memo = trim($_POST['memo']);

        $note = "Please place memo and confirm!";
        if (empty($id)) {
            $this->error("No such withdrawal request!");
        }
        if (!$memo) {
            $this->error("Please Enter Memo!");
        }

        $myzc = M('Mytx')->where(['id' => $id])->find();
        $savetx = M('Mytx')->where(['id' => $id])->save(['memo' => $memo, 'status' => 1]);
        if (!$savetx) {
            $this->error("Could not update memo and Status!");
        } else {
            $this->success("Memo is saved and status is updated!");
            $this->redirect('Admin/Finance/mytx');
        }
    }

    public function manualwithdrawal($id = NULL)
    {
        $id = intval($_GET['id']);
        $note = "Please place txid and confirm!";
        if (empty($id)) {
            $note = "Please select the data to be operated.";
        }

        $myzc = M('Myzc')->where(['id' => $id])->find();

        if (!$myzc) {
            $note = "No such withdrawal request!";
        }


        if (($myzc['status'] != 0) && ($myzc['status'] != 3)) {
            $note = "Withdrawal has been processed already!";
        }
        $username = M('User')->where(['id' => $myzc['userid']])->getField('username');
        $this->assign('username', $username);
        $this->assign('info', $myzc);
        $this->assign('note', $note);
        $this->display();
    }

    public function manualwithdrawalConfirm()
    {
        $id = intval($_POST['id']);
        $txid = trim($_POST['txid']);
		$status = trim($_POST['status']);
		
        $note = "Please place txid and confirm!";
        if (empty($id)) {
            $this->error("Please select the data to be operated.");
        }
        if (!$txid) {
            $this->error("Please Enter Txid !");
        }
		if ($status!=1 && $status!=2 && $status!=0) {
            $this->error("Please Choose status between 0-2  !");
        }

        $myzc = M('Myzc')->where(['id' => $id])->find();
        $savetx = M('Myzc')->where(['id' => $id])->save(['txid' => $txid, 'status' => $status]);
        if (!$savetx) {
            $this->error("Could not update Txid and Status!");
        } else {
            $this->success("TXid is saved and status is updated!");
            $this->redirect('Admin/Finance/myzc');
        }
    }

    public function myczTypeEdit($id = NULL)
    {
        if (empty($_POST)) {
            if ($id) {
                $this->data = M('MyczType')->where(['id' => trim($id)])->find();
            } else {
                $this->data = null;
            }
            $show_coin=json_decode($this->data['show_coin']);
			
            $Coin = D('coin')->where('type in ("rmb") and status = 1')->select();
            $appc['show_coin'] = [];
			
            foreach ($Coin as $val) {
				
                $appc['show_coin'][] = ['id' => $val['id'], 'name' => $val['title'] . '(' . $val['name'] . ')', 'flag' =>in_array($val['id'], $show_coin) ? 1 : 0];
            }
			
            $this->assign('appCon', $appc);

            $this->display();
        } else {
            if (APP_DEMO) {
                $this->error(L('SYSTEM_IN_DEMO_MODE'));
            }
            $_POST['show_coin'] = json_encode($_POST['show_coin']);
            if ($_POST['id']) {
                $rs = M('MyczType')->save($_POST);
            } else {
                $rs = M('MyczType')->add($_POST);
            }

            if ($rs) {
                $this->success(L('SUCCESSFULLY_DONE'));
            } else {
                $this->error(L('OPERATION_FAILED'));
            }
        }
    }

    public function myczTypeImage()
    {
        $upload = new Upload();
        $upload->maxSize = 3145728;
        $upload->exts = ['jpg', 'gif', 'png', 'jpeg'];
        $upload->rootPath = './Upload/bank/';
        $upload->autoSub = false;
        $info = $upload->upload();

        foreach ($info as $k => $v) {
            $path = $v['savepath'] . $v['savename'];
            echo $path;
            exit();
        }
    }

    public function myczTypeStatus($id = NULL, $type = NULL, $model = 'MyczType')
    {
        if (APP_DEMO) {
            $this->error(L('SYSTEM_IN_DEMO_MODE'));
        }

        $this->sub_status($id, $type, $model);
    }


    public function mytx($field = NULL, $name = NULL, $status = NULL)
    {
        $where = [];

        if ($field && $name) {
            if ($field == 'username') {
                $where['userid'] = M('User')->where(['username' => $name])->getField('id');
            } else {
                $where[$field] = $name;
            }
        }

        if ($status) {
            $where['status'] = $status - 1;
        }

        $count = M('Mytx')->where($where)->count();
        $Page = new Page($count, 15);
        $show = $Page->show();
        $list = M('Mytx')->where($where)->order('id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();
		
        foreach ($list as $k => $v) {
            $list[$k]['username'] = M('User')->where(['id' => $v['userid']])->getField('username');
        }

        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->display();
    }


    public function mytxStatus($id = NULL, $type = NULL, $model = 'Mytx')
    {
        if (APP_DEMO) {
            $this->error(L('SYSTEM_IN_DEMO_MODE'));
        }

        $this->sub_status($id, $type, $model);
    }

    public function mytxChuli()
    {
        $id = $_GET['id'];

        if (empty($id)) {
            $this->error('Please select the data to be operated.');
        }

        if (M('Mytx')->where(['id' => $id])->save(['status' => 3])) {
            $this->success(L('SUCCESSFULLY_DONE'));
        } else {
            $this->error(L('OPERATION_FAILED'));
        }
    }

    public function mytxReject()
    {
        $id =  trim($_GET['id']);

        if (empty($id)) {
            $this->error('Please select the data to be operated.');
        }

        $mytx = M('Mytx')->where(['id' => $id,'status'=>0])->find();
		if($id!=$mytx['id']) {
            $this->error(L('NO SUCH RECORD'));
        }
		$coin=strtolower($mytx['coin']);
		$coind=$coin.'d';
		
        $mo = M();
        
        $mo->startTrans();
        $rs = [];
        $finance = $mo->table('codono_finance')->where(['userid' => $mytx['userid']])->order('id desc')->find();
        $finance_num_user_coin = $mo->table('codono_user_coin')->where(['userid' => $mytx['userid']])->find();
        $rs[] = $mo->table('codono_user_coin')->where(['userid' => $mytx['userid']])->setInc($coin, $mytx['num']);
        $rs[] = $mo->table('codono_mytx')->where(['id' => $mytx['id']])->setField('status', 2);
        $finance_mum_user_coin = $mo->table('codono_user_coin')->where(['userid' => $mytx['userid']])->find();
        $finance_hash = md5($mytx['userid'] . $finance_num_user_coin[$coin] . $finance_num_user_coin[$coind] . $mytx['num'] . $finance_mum_user_coin[$coin] . $finance_mum_user_coin[$coind] . CODONOLIC . 'auth.codono.com');
        $finance_num = $finance_num_user_coin[$coin] + $finance_num_user_coin[$coind];

        if ($finance['mum'] < $finance_num) {
            $finance_status = (1 < ($finance_num - $finance['mum']) ? 0 : 1);
        } else {
            $finance_status = (1 < ($finance['mum'] - $finance_num) ? 0 : 1);
        }

        $rs[] = $mo->table('codono_finance')->add(['userid' => $mytx['userid'], 'coinname' => $coin, 'num_a' => $finance_num_user_coin[$coin], 'num_b' => $finance_num_user_coin[$coind], 'num' => $finance_num_user_coin[$coin] + $finance_num_user_coin[$coind], 'fee' => $mytx['num'], 'type' => 1, 'name' => 'mytx', 'nameid' => $mytx['id'], 'remark' => 'Undo Fiat Withdrawal', 'mum_a' => $finance_mum_user_coin[$coin], 'mum_b' => $finance_mum_user_coin[$coind], 'mum' => $finance_mum_user_coin[$coin] + $finance_mum_user_coin[$coind], 'move' => $finance_hash, 'addtime' => time(), 'status' => $finance_status]);

        if (check_arr($rs)) {
            $mo->commit();
            // removed unlock/lock
            $this->success(L('SUCCESSFULLY_DONE'));
        } else {
            $mo->rollback();
            $this->error(L('OPERATION_FAILED'));
        }
    }

    public function mytxConfirm()
    {
        $id = $_GET['id'];

        if (empty($id)) {
            $this->error('Please select the data to be operated.');
        }
		$info=M('Mytx')->where(['id' => $id])->find();

        if (M('Mytx')->where(['id' => $id])->save(['status' => 1])) {
			$subject ="Withdrawal Request has been successful!";
				$email=getEmail($info['userid']);
				$withdraw_content="Hi ,<br/> We have processed your following withdrawal request.<br/><br/><br/>
				<table style='border:2px solid black;width:100%'><tr style='border:1px solid black;width:100%'><td>Account Name</td><td>".$info['truename']."</td></tr>
				<tr style='border:1px solid black;width:100%'><td>Account Number</td><td>".$info['bankcard']."</td></tr>
				<tr style='border:1px solid black;width:100%'><td>Bank Info</td><td>".$info['bankaddr'].','.$info['bank']."</td></tr>
				<tr style='border:1px solid black;width:100%'><td>Receivable Amount</td><td>".$info['mum'].' '.strtoupper($info['coin'])."</td></tr>";
				addnotification($email,$subject,$withdraw_content);
            $this->success(L('SUCCESSFULLY_DONE'));
        } else {
            $this->error(L('OPERATION_FAILED'));
        }
    }

    public function mytxExcel()
    {
        if (IS_POST) {
            $id = implode(',', $_POST['id']);
        } else {
            $id = $_GET['id'];
        }

        if (empty($id)) {
            $this->error('Please select the data to be operated.');
        }

        $where['id'] = ['in', $id];
        $list = M('Mytx')->where($where)->select();

        foreach ($list as $k => $v) {
            $list[$k]['userid'] = M('User')->where(['id' => $v['userid']])->getField('username');
            $list[$k]['addtime'] = addtime($v['addtime']);

            if ($list[$k]['status'] == 0) {
                $list[$k]['status'] = 'Untreated';
            } else if ($list[$k]['status'] == 1) {
                $list[$k]['status'] = 'Already transfer money';
            } else if ($list[$k]['status'] == 2) {
                $list[$k]['status'] = 'Revoked';
            } else {
                $list[$k]['status'] = 'error';
            }

            $list[$k]['bankcard'] = ' ' . $v['bankcard'] . ' ';
        }

        $zd = M('Mytx')->getDbFields();
        $xlsName = 'cade';
        $xls = [];

        foreach ($zd as $k => $v) {
            $xls[$k][0] = $v;
            $xls[$k][1] = $v;
        }

        $xls[0][2] = 'Numbering';
        $xls[1][2] = 'username';
        $xls[2][2] = 'Withdrawal Amount';
        $xls[3][2] = 'Fees';
        $xls[4][2] = 'Amount arrival';
        $xls[5][2] = 'Full name';
        $xls[6][2] = 'Bank Notes';
        $xls[7][2] = 'Bank name';
        $xls[8][2] = 'Opening provinces';
        $xls[9][2] = 'Cities account';
        $xls[10][2] = 'Opening address';
        $xls[11][2] = 'Bank card number';
        $xls[12][2] = ' ';
        $xls[13][2] = 'Withdraw Time';
        $xls[14][2] = 'Export Time';
        $xls[15][2] = 'Withdraw state';
        $this->exportExcel($xlsName, $xls, $list);
    }


    /**
     * @throws \PHPExcel_Reader_Exception
     * @throws \PHPExcel_Exception
     * @throws \PHPExcel_Writer_Exception
     */
    public function financeExcel()
    {
        if (IS_POST) {
            $id = implode(',', $_POST['id']);
        } else {
            $id = intval($_GET['id']);
        }

        if (empty($id)) {
            $this->error('Please select the data to be operated.');
        }

        $where['id'] = ['in', $id];
        $list = M('Finance')->where($where)->select();

        

        foreach ($list as $k => $v) {
            $list[$k]['userid'] = M('User')->where(['id' => $v['userid']])->getField('username');
            $list[$k]['addtime'] = addtime($v['addtime']);

            $list[$k]['caozuoqian'] = "normal : " . $v['num_a'] . "freeze : " . $v['num_b'] . "total : " . $v['num'];
            $list[$k]['caozuohou'] = "normal : " . $v['mum_a'] . "freeze : " . $v['mum_b'] . "total : " . $v['mum'];

            $list[$k]['name'] = $this->findNameList($v['name']);

            if ($list[$k]['type'] == 1) {
                $list[$k]['type'] = '<span style="color:green">Income</span>';
            } else if ($list[$k]['type'] == 2) {
                $list[$k]['type'] = '<span style="color:red">Spend</span>';
            }
            $list = $this->sub_excel($list, $k);

        }

        //$zd = M('Finance')->getDbFields();
        $xlsName = 'finance';
        $xls = [];

        $xls[0][0] = "id";
        $xls[0][2] = 'ID';
        $xls[1][0] = "userid";
        $xls[1][2] = 'username';
        $xls[2][0] = "coinname";
        $xls[2][2] = 'Currency operations';
        $xls[3][0] = "Fees";
        $xls[3][2] = 'The number of operations';
        $xls[4][0] = "type";
        $xls[4][2] = 'Action Type';
        $xls[5][0] = "name";
        $xls[5][2] = 'Instructions';
        $xls[6][0] = "addtime";
        $xls[6][2] = 'Operating time';
        $xls[7][0] = "caozuoqian";
        $xls[7][2] = 'Before operation';
        $xls[8][0] = "caozuohou";
        $xls[8][2] = 'After the operation';
        $xls[9][0] = "status";
        $xls[9][2] = 'Status';
        $this->exportExcel($xlsName, $xls, $list);
    }


    /**
     * @throws \PHPExcel_Reader_Exception
     * @throws \PHPExcel_Exception
     * @throws \PHPExcel_Writer_Exception
     */
    public function financeAllExcel()
    {
        $list = M('Finance')->select();

        

        foreach ($list as $k => $v) {
            $list[$k]['userid'] = M('User')->where(['id' => $v['userid']])->getField('username');
            $list[$k]['addtime'] = addtime($v['addtime']);

            $list[$k]['caozuoqian'] = "normal : " . $v['num_a'] . "freeze : " . $v['num_b'] . "total : " . $v['num'];
            $list[$k]['caozuohou'] = "normal : " . $v['mum_a'] . "freeze : " . $v['mum_b'] . "total : " . $v['mum'];

            $list[$k]['name'] = $this->findNameList($v['name']);

            if ($list[$k]['type'] == 1) {
                $list[$k]['type'] = 'income';
            } else if ($list[$k]['type'] == 2) {
                $list[$k]['type'] = 'spend';
            }
            $list = $this->sub_excel($list, $k);

        }

        //$zd = M('Finance')->getDbFields();
        $xlsName = 'finance';
        $xls = [];

        $xls[0][0] = "id";
        $xls[0][2] = 'Numbering';
        $xls[1][0] = "userid";
        $xls[1][2] = 'username';
        $xls[2][0] = "coinname";
        $xls[2][2] = 'Currency operations';
        $xls[3][0] = "fee";
        $xls[3][2] = 'The number of operations';
        $xls[4][0] = "type";
        $xls[4][2] = 'Action Type';
        $xls[5][0] = "name";
        $xls[5][2] = 'Instructions';
        $xls[6][0] = "addtime";
        $xls[6][2] = 'Operating time';
        $xls[7][0] = "caozuoqian";
        $xls[7][2] = 'Before operation';
        $xls[8][0] = "caozuohou";
        $xls[8][2] = 'After the operation';
        $xls[9][0] = "status";
        $xls[9][2] = 'Status';
        $this->exportExcel($xlsName, $xls, $list);
    }


    public function mytxConfig()
    {
        if (empty($_POST)) {
            $this->display();
        } else if (M('Config')->where(['id' => 1])->save($_POST)) {
            $this->success('Changes Saved!');
        } else {
            $this->error('No changes were made!');
        }
    }

    public function myzr($field = NULL, $name = NULL, $coinname = NULL)
    {
        $where = [];

        if ($field && $name) {
            if ($field == 'username') {
                $where['userid'] = M('User')->where(['username' => $name])->getField('id');
            } else {
                $where[$field] = $name;
            }
        }

        if ($coinname) {
            $where['coinname'] = $coinname;
        }

        $count = M('Myzr')->where($where)->count();
        $Page = new Page($count, 15);
        $show = $Page->show();
        $list = M('Myzr')->where($where)->order('id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();

        foreach ($list as $k => $v) {
            $list[$k]['usernamea'] = M('User')->where(['id' => $v['userid']])->getField('username');
        }

        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->display();
    }

    public function myzc($field = NULL, $name = NULL, $coinname = NULL)
    {
        $where = [];

        if ($field && $name!=NULL) {
            if ($field == 'username') {
                $where['userid'] = M('User')->where(['username' => $name])->getField('id');
            } else {
                $where[$field] = $name;
            }
        }

        if ($coinname) {
            $where['coinname'] = $coinname;
        }

        $count = M('Myzc')->where($where)->count();
        $Page = new Page($count, 15);
        $show = $Page->show();
        $list = M('Myzc')->where($where)->order('id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();

        foreach ($list as $k => $v) {
            $list[$k]['usernamea'] = M('User')->where(['id' => $v['userid']])->getField('username');
        }

        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->display();
    }

    public function myzcFee($field = NULL, $name = NULL, $coinname = NULL)
    {

        $where = [];

        if ($field && $name) {
            if ($field == 'username') {
                $where['userid'] = M('User')->where(['username' => $name])->getField('id');
            } else {
                $where[$field] = $name;
            }
        }

        if ($coinname) {
            $where['coinname'] = $coinname;
        }

        $count = M('MyzcFee')->where($where)->count();

        $Page = new Page($count, 15);
        $show = $Page->show();
        $list = M('MyzcFee')->where($where)->order('id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();

        foreach ($list as $k => $v) {
            $list[$k]['usernamea'] = M('User')->where(['id' => $v['userid']])->getField('username');
        }
        $this->assign('coinname', $coinname);    
        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->display();

    }

    public function myzcReject()
    {
        $id = $_GET['id'];

        if (empty($id)) {
            $this->error('Please select the data to be operated.');
        }

        $myzc = M('Myzc')->where(['id' => trim($_GET['id']), 'status' => '0'])->find();
        if (!$myzc) {
            $this->error('No such tx');
        }
        $mo = M();
        
        $mo->startTrans();
        $rs = [];
        $coin = strtolower($myzc['coinname']);
        $coind = $coin . 'd';
        $zc_coin=strtolower($myzc['zc_coin']);
        $refund_amount=$myzc['num'];
        if($coin!=$zc_coin){
            $refund_amount=$myzc['mum'];
            $refund_fee=$myzc['fee'];
            $rs[] = $mo->table('codono_user_coin')->where(['userid' => $myzc['userid']])->setInc($zc_coin, $refund_fee);
        }
        $finance = $mo->table('codono_finance')->where(['userid' => $myzc['userid']])->order('id desc')->find();
        $finance_num_user_coin = $mo->table('codono_user_coin')->where(['userid' => $myzc['userid']])->find();
        $rs[] = $mo->table('codono_user_coin')->where(['userid' => $myzc['userid']])->setInc($coin, $refund_amount);
        $rs[] = $mo->table('codono_myzc')->where(['id' => $myzc['id']])->setField('status', 2);
        $fee_cancel=$mo->table('codono_myzc_fee')->where(['txid' => $myzc['id']])->setField('status', 2);
        $finance_mum_user_coin = $mo->table('codono_user_coin')->where(['userid' => $myzc['userid']])->find();
        $finance_hash = md5($myzc['userid'] . $myzc['id']);
        $finance_num = $finance_num_user_coin[$coin] + $finance_num_user_coin[$coind];

        if ($finance['mum'] < $finance_num) {
            $finance_status = (1 < ($finance_num - $finance['mum']) ? 0 : 1);
        } else {
            $finance_status = (1 < ($finance['mum'] - $finance_num) ? 0 : 1);
        }

        $rs[] = $mo->table('codono_finance')->add(['userid' => $myzc['userid'], 'coinname' => $coin, 'num_a' => $finance_num_user_coin[$coin], 'num_b' => $finance_num_user_coin[$coind], 'num' => $finance_num_user_coin[$coin] + $finance_num_user_coin[$coind], 'fee' => $myzc['fee'], 'type' => 1, 'name' => 'myzc', 'nameid' => $myzc['id'], 'remark' => $coin . ' Withdrawal Undone', 'mum_a' => $finance_mum_user_coin[$coin], 'mum_b' => $finance_mum_user_coin[$coind], 'mum' => $finance_mum_user_coin[$coin] + $finance_mum_user_coin[$coind], 'move' => $finance_hash, 'addtime' => time(), 'status' => $finance_status]);

        if (check_arr($rs)) {
            $mo->commit();
            // removed unlock/lock
            $this->success(L('SUCCESSFULLY_DONE'));
        } else {
            $mo->rollback();
            $this->error(L('OPERATION_FAILED'));
        }
    }


    public function myzcConfirm($id = NULL)
    {
        $id = intval($_GET['id']);
        if (APP_DEMO) {
            $this->error(L('SYSTEM_IN_DEMO_MODE'));
        }

        $myzc = M('Myzc')->where(['id' => trim($id)])->find();

        if (!$myzc) {
            $this->error('Withdraw wrong!');
        }

        if ($myzc['status']) {
            $this->error('We have been treated!');
        }

        $username = M('User')->where(['id' => $myzc['userid']])->getField('username');

        $coin = $myzc['coinname'];
		$network = $myzc['coinname'];
        $dj_username = C('coin')[$coin]['dj_yh'];
        $dj_password = C('coin')[$coin]['dj_mm'];
        $dj_address = C('coin')[$coin]['dj_zj'];
        $dj_port = C('coin')[$coin]['dj_dk'];
        $dj_decimal = C('coin')[$coin]['cs_qk'];
        $main_address = C('coin')[$coin]['codono_coinaddress'];
        if (C('coin')[$coin]['type'] == 'blockio') {
            $block_io = BlockIO($dj_username, $dj_password, $dj_address, $dj_port, 5, [], 1);
            $json = $block_io->get_balance();

            if (!isset($json->status) || $json->status != 'success') {
                $this->error(L('Wallet link failure! blockio'));
            }

            $Coin = M('Coin')->where(['name' => $myzc['coinname']])->find();

            $fee_user['userid'] = M('User')->where(['id' => $Coin['zc_user']])->getField("id");
            $user_coin = M('UserCoin')->where(['userid' => $myzc['userid']])->find();
            $zhannei = M('UserCoin')->where([$coin . 'b' => $myzc['username']])->find();
            $mo = M();
            
            $mo->startTrans();
            $rs = [];

            if ($zhannei) {
                $rs[] = $mo->table('codono_myzr')->add(['userid' => $zhannei['userid'], 'username' => $myzc['username'], 'coinname' => $coin, 'txid' => md5($myzc['username'] . $user_coin[$coin . 'b'] . time()), 'num' => $myzc['num'], 'fee' => $myzc['fee'], 'mum' => $myzc['mum'], 'addtime' => time(), 'status' => 1]);
                $rs[] = $r = $mo->table('codono_user_coin')->where(['userid' => $zhannei['userid']])->setInc($coin, $myzc['mum']);
            }

            if (!$fee_user['userid']) {
                $fee_user['userid'] = 0;
            }

            if (0 < $myzc['fee']) {
                $rs[] = $mo->table('codono_myzc_fee')->add(['userid' => $fee_user['userid'], 'username' => $Coin['zc_user'], 'coinname' => $coin, 'num' => $myzc['num'], 'fee' => $myzc['fee'], 'mum' => $myzc['mum'], 'type' => 2, 'addtime' => time(), 'status' => 1]);

                if ($mo->table('codono_user_coin')->where([$coin . 'b' => $Coin['zc_user']])->find()) {
                    //$rs[] = $mo->table('codono_user_coin')->where(array('userid' => $Coin['zc_user']))->setInc($coin, $myzc['fee']);
                    debug(['lastsql' => $mo->table('codono_user_coin')->getLastSql()], 'Additional costs');
                } else {
                    $rs[] = $mo->table('codono_user_coin')->add([$coin . 'b' => $Coin['zc_user'], $coin => $myzc['fee']]);
                }
            }

            $rs[] = M('Myzc')->where(['id' => trim($id)])->save(['status' => 1]);

            if (check_arr($rs)) {
                $mo->commit();
                // removed unlock/lock
                $valid_res = $block_io->validateaddress($myzc['username']);
                if (!$valid_res) {
                    $this->error($myzc['username'] . L(' It is not a valid address wallet!'));
                }


                if ($json->data->available_balance < (double)$myzc['mum']) {
                    $this->error(L('Wallet balance of less than'));
                }

                $send_amt = round($myzc['mum'], 8);
                $sendrs = $block_io->withdraw(['amounts' => $send_amt, 'to_addresses' => $myzc['username']]);
                

                if ($sendrs) {
                    if (isset($sendrs->status) && ($sendrs->status == 'success')) {
                        $flag = 1;
                    }
                } else {
                    $flag = 0;
                }

                if (!$flag) {
                    $mo->rollback();
                    // removed unlock/lock
                    $this->error('wallet server Withdraw currency failure!');
                } else {
                    $this->success('Transfer success!');
                }
            } else {
                $mo->rollback();
                // removed unlock/lock
                $this->error('Roll-out failure!' . implode('|', $rs) . $myzc['fee']);
            }

        }

		//Esmart STARTS
        if (C('coin')[$coin]['type'] == 'esmart') {

            $heyue = $dj_username; //Contract Address
            $dj_password = cryptString(C('coin')[$coin]['dj_mm'], 'd');
            $main_address = C('coin')[$coin]['codono_coinaddress'];

            $ContractAddress = $heyue;
			
				$esmart_config= [
					'host'=>C('coin')[$coin]['dj_zj'],
					'port'=>C('coin')[$coin]['dj_dk'],
					'coinbase'=>C('coin')[$coin]['codono_coinaddress'],
					'password'=>cryptString(C('coin')[$coin]['dj_mm'],'d'),
					'contract'=>C('coin')[$coin]['dj_yh'],
					'rpc_type'=>C('coin')[$coin]['rpc_type'],
					'public_rpc'=>C('coin')[$coin]['public_rpc'],
                ];
				$Esmart = Esmart($esmart_config);

            $Coin = M('Coin')->where(['name' => $myzc['coinname']])->find();
            $fee_user = M('UserCoin')->where([$coin . 'b' => $Coin['zc_user']])->find();
            $user_coin = M('UserCoin')->where(['userid' => $myzc['userid']])->find();
            $zhannei = M('UserCoin')->where([$coin . 'b' => $myzc['username']])->find();
            $mo = M();
            
            

            $mo->startTrans();
            $rs = [];

            if ($zhannei) {
                $rs[] = $mo->table('codono_myzr')->add(['userid' => $zhannei['userid'], 'username' => $myzc['username'], 'coinname' => $coin, 'txid' => md5($myzc['username'] . $user_coin[$coin . 'b'] . time()), 'num' => $myzc['num'], 'fee' => $myzc['fee'], 'mum' => $myzc['mum'], 'addtime' => time(), 'status' => 1]);
                $rs[] = $r = $mo->table('codono_user_coin')->where(['userid' => $zhannei['userid']])->setInc($coin, $myzc['mum']);
            }

            if (!$fee_user['userid']) {
                $fee_user['userid'] = 0;
            }

            if (0 < $myzc['fee']) {
                $rs[] = $mo->table('codono_myzc_fee')->add(['userid' => $fee_user['userid'], 'username' => $Coin['zc_user'], 'coinname' => $coin, 'num' => $myzc['num'], 'fee' => $myzc['fee'], 'mum' => $myzc['mum'], 'type' => 2, 'addtime' => time(), 'status' => 1]);

                if ($mo->table('codono_user_coin')->where([$coin . 'b' => $Coin['zc_user']])->find()) {
                    //$rs[] = $mo->table('codono_user_coin')->where(array('userid' => $Coin['zc_user']))->setInc($coin, $myzc['fee']);
                    debug(['lastsql' => $mo->table('codono_user_coin')->getLastSql()], 'Additional costs');
                } else {
                    $rs[] = $mo->table('codono_user_coin')->add([$coin . 'b' => $Coin['zc_user'], $coin => $myzc['fee']]);
                }
            }


            $hash = null;
            if (check_arr($rs)) {
                //Contract Address withdrawal
                if ($heyue) {

                    $zhuan['fromaddress'] = $main_address;
                    $zhuan['toaddress'] = strtolower($myzc['username']);
                    $zhuan['token'] = $heyue;
                    $zhuan['type'] = $coin;
                    $zhuan['amount'] = (double)$myzc['mum'];
                    $zhuan['password'] = $dj_password;

                    $sendrs = $Esmart->transferToken($zhuan['toaddress'], $zhuan['amount'], $zhuan['token'], $dj_decimal);
                } else {
                    //esmart


                    $zhuan['fromaddress'] = $main_address;
                    $zhuan['toaddress'] = strtolower($myzc['username']);
                    $zhuan['amount'] = (double)$myzc['mum'];
                    $zhuan['password'] = $dj_password;
                    $sendrs = $Esmart->transferFromCoinbase($zhuan['toaddress'], (double)$myzc['mum']);

                }

                if ($sendrs) {
                    $flag = 1;
                    $arr = json_decode($sendrs, true);
                    $hash = $arr['result'] ?: $arr['error']['message'];

                    if (isset($arr['status']) && ($arr['status'] == 0)) {
                        $flag = 0;
                    }
                } else {
                    $flag = 0;
                }

                if ($flag == 0) {
                    $mo->rollback();
                    // removed unlock/lock
                    $this->error('Esmart Server issues!:' . ($sendrs));
                } else {
                    $mo->table('codono_myzc')->where(['id' => $id])->save(['txid' => $hash]);
                    if ($hash) $mo->execute("UPDATE `codono_myzc` SET  `hash` =  '$hash' WHERE id = '$id' ");
                    $mo->table('codono_myzc')->where(['id' => trim($id)])->save(['status' => 1]);
                    $mo->commit();
                    // removed unlock/lock
                    $this->success('Transfer success!');
                }
            } else {
                $mo->rollback();
                // removed unlock/lock
                $this->error('Roll-out failure!' . implode('|', $rs) . $myzc['fee']);
            }
        }
		//Esmart ENDS
        //cryptoapis STARTS
        if (C('coin')[$coin]['type'] == 'cryptoapis') {

            $heyue = $dj_username; //Contract Address
            $dj_password = cryptString(C('coin')[$coin]['dj_mm'], 'd');
            $main_address = C('coin')[$coin]['codono_coinaddress'];
            $CoinInfo=C('coin')[$coin];
            $network=$coin;
            $cryptoapi_config = [
                'api_key' => cryptString($CoinInfo['dj_mm'], 'd'),
                'network' => $CoinInfo['network'],
            ];
            $cryptoapi = CryptoApis($cryptoapi_config);
            $supportedCryptoApisChains = $cryptoapi->allowedSymbols();
            if (!in_array($network, $supportedCryptoApisChains)) {
                $this->success('Withdrawal has been processed!');
            }

            $Coin = M('Coin')->where(['name' => $myzc['coinname']])->find();
            $fee_user = M('UserCoin')->where([$coin . 'b' => $Coin['zc_user']])->find();
            $user_coin = M('UserCoin')->where(['userid' => $myzc['userid']])->find();
            $zhannei = M('UserCoin')->where([$coin . 'b' => $myzc['username']])->find();
            $mo = M();



            $mo->startTrans();
            $rs = [];

            if ($zhannei) {
                $rs[] = $mo->table('codono_myzr')->add(['userid' => $zhannei['userid'], 'username' => $myzc['username'], 'coinname' => $coin, 'txid' => md5($myzc['username'] . $user_coin[$coin . 'b'] . time()), 'num' => $myzc['num'], 'fee' => $myzc['fee'], 'mum' => $myzc['mum'], 'addtime' => time(), 'status' => 1]);
                $rs[] = $r = $mo->table('codono_user_coin')->where(['userid' => $zhannei['userid']])->setInc($coin, $myzc['mum']);
            }

            if (!$fee_user['userid']) {
                $fee_user['userid'] = 0;
            }

            if (0 < $myzc['fee']) {
                $rs[] = $mo->table('codono_myzc_fee')->add(['userid' => $fee_user['userid'], 'username' => $Coin['zc_user'], 'coinname' => $coin, 'num' => $myzc['num'], 'fee' => $myzc['fee'], 'mum' => $myzc['mum'], 'type' => 2, 'addtime' => time(), 'status' => 1]);

                if ($mo->table('codono_user_coin')->where([$coin . 'b' => $Coin['zc_user']])->find()) {
                    //$rs[] = $mo->table('codono_user_coin')->where(array('userid' => $Coin['zc_user']))->setInc($coin, $myzc['fee']);
                    debug(['lastsql' => $mo->table('codono_user_coin')->getLastSql()], 'Additional costs');
                } else {
                    $rs[] = $mo->table('codono_user_coin')->add([$coin . 'b' => $Coin['zc_user'], $coin => $myzc['fee']]);
                }
            }
            $hash = null;
            $contract_address = $contract = $CoinInfo['dj_yh'] ?: null;//Contract Address
            $blockchain = $coin;
            $walletId = $CoinInfo['dj_zj'];
            $context = $myzc['userid'];
            $main_address = $CoinInfo['codono_coinaddress'];
            $amount = (double)$myzc['mum'];
            $to_address = $myzc['username'];
            $aid=$myzc['id'];
            $tx_note = md5($blockchain . $context . $network . $amount  .$aid. $contract);
            if (check_arr($rs)) {

                if ($contract_address) {
                    //Contract Address transfer out
                    $sendrs = $cryptoapi->withdraw($blockchain, $walletId, $main_address, $to_address, $amount, $tx_note, $context, $contract_address);
                } else {

                    $sendrs = $cryptoapi->withdraw($blockchain, $walletId, $main_address, $to_address, $amount, $tx_note, $context);

                }

                if ($sendrs && $aid) {
                    $memo = $sendrs->transactionRequestId;
                    M('Myzc')->where(['id' => $aid])->save(['memo' => $memo]);
                    $mo->commit();
                }else{
                    $mo->rollback();

                    $this->error('Withdrawal Failed!');
                }
            } else {
                $mo->rollback();
                // removed unlock/lock
                $this->error('Roll-out failure!' . implode('|', $rs) . $myzc['fee']);
            }
        }
        //CryptoApis ENDS
				//xrp starts
		if (C('coin')[$coin]['type'] == 'xrp'){
			if(!isset($myzc['dest_tag']) || $myzc['dest_tag']==0 || $myzc['dest_tag']=""){
				$this->error('Make sure correct dest_tag is defined');
			}
				$xrpData = C('coin')['xrp'];
                    if ($xrpData){
						
                        $xrpClient = XrpClient($xrpData['dj_zj'], $xrpData['dj_dk'], $xrpData['codono_coinaddress'], $xrpData['dj_mm']);
                        
                        $sign = $xrpClient->sign((double)$myzc['mum'],$myzc['username'],$myzc['dest_tag']);
                        if (strtolower($sign['result']['status']) == 'success'){
                            $submit = $xrpClient->submit($sign['result']['tx_blob']);
                            if (strtolower($submit['result']['status']) == 'success'){
									$hash = $submit['result']['tx_json']['hash'];
									M('Myzc')->where(['id' => $id])->save(['txid' => $hash]);
									if ($hash) M()->execute("UPDATE `codono_myzc` SET  `hash` =  '$hash' WHERE id = ".$id);
									$this->success('Successfully transferred out');
                            }else{
                                $this->error('Server transfer failed');
                            }
                        }else{
                            $this->error('Server transfer failed');
                        }
					}	
		}
		//xrp ends
		
        //CoinPayments  Starts
        if (C('coin')[$coin]['type'] == 'coinpay') {

            $dj_password = C('coin')[$coin]['dj_mm'];
            $cps_api = CoinPay($dj_username, $dj_password, $dj_address, $dj_port, 5, [], 1);
            $information = $cps_api->GetBasicInfo();
            $coinpay_coin = strtoupper($coin);

            $can_withdraw = 1;
            if ($information['error'] != 'ok' || !isset($information['result']['username'])) {
                //         $this->error(L('Wallet link failure! Coinpayments'));
                debug($coin . ' can not be connetcted at time:' . time() . '<br/>');
                $can_withdraw = 0;
            }
            //TODO :Find a valid way to validate coin address
            if (strlen($myzc['username']) > 8) {
                $valid_res = 1;
            } else {
                $valid_res = 0;
            }

            if (!$valid_res) {
                $this->error($myzc['username'] . L(' It is not a valid address wallet!'));
            }

            $balances = $cps_api->GetAllCoinBalances();

            if ($balances['result'][$coinpay_coin]['balancef'] < $myzc['num']) {
                //$this->error(L('Can not be withdrawn due to system'));
                debug($coin . ' Balance is lower than  ' . $myzc['num'] . ' at time:' . time() . '<br/>');
                $can_withdraw = 0;
            }

            $Coin = C('coin')[$myzc['coinname']];
            $fee_user = M('UserCoin')->where([$coin . 'b' => $Coin['zc_user']])->find();
            $user_coin = M('UserCoin')->where(['userid' => $myzc['userid']])->find();
            $zhannei = M('UserCoin')->where([$coin . 'b' => $myzc['username']])->find();
            $mo = M();
            
            $mo->startTrans();
            $rs = [];

            if ($zhannei) {
                $rs[] = $mo->table('codono_myzr')->add(['userid' => $zhannei['userid'], 'username' => $myzc['username'], 'coinname' => $coin, 'txid' => md5($myzc['username'] . $user_coin[$coin . 'b'] . time()), 'num' => $myzc['num'], 'fee' => $myzc['fee'], 'mum' => $myzc['mum'], 'addtime' => time(), 'status' => 1]);
                $rs[] = $r = $mo->table('codono_user_coin')->where(['userid' => $zhannei['userid']])->setInc($coin, $myzc['mum']);
            }

            if (!$fee_user['userid']) {
                $fee_user['userid'] = 0;
            }

            if (0 < $myzc['fee']) {
                $rs[] = $mo->table('codono_myzc_fee')->add(['userid' => $fee_user['userid'], 'username' => $Coin['zc_user'], 'coinname' => $coin, 'num' => $myzc['num'], 'fee' => $myzc['fee'], 'mum' => $myzc['mum'], 'type' => 2, 'addtime' => time(), 'status' => 1]);

                if ($mo->table('codono_user_coin')->where([$coin . 'b' => $Coin['zc_user']])->find()) {
                    //$rs[] = $mo->table('codono_user_coin')->where(array('userid' => $Coin['zc_user']))->setInc($coin, $myzc['fee']);
                    debug(['lastsql' => $mo->table('codono_user_coin')->getLastSql()], 'Additional costs');
                } else {
                    $rs[] = $mo->table('codono_user_coin')->add([$coin . 'b' => $Coin['zc_user'], $coin => $myzc['fee']]);
                }
            }

            $rs[] = M('Myzc')->where(['id' => trim($id)])->save(['status' => 1]);

            if (check_arr($rs)) {
                $mo->commit();
                // removed unlock/lock
                
                $buyer_email = M('User')->where(['id' => $myzc['userid']])->getField('email');
                $dest_tag = $myzc['dest_tag'];
                $withdrawals = ['amount' => (double)$myzc['mum'],
                    'add_tx_fee' => 0,
                    'auto_confirm' => 1, //Auto confirm 1 or 0
                    'currency' => $coinpay_coin,
                    'address' => $myzc['username'],
                    'ipn_url' => SITE_URL . '/IPN/confirm',
                    'note' => $buyer_email];
                if ($dest_tag != 0 && $dest_tag != NULL) {
                    $withdrawals['dest_tag'] = $dest_tag;
                }

                $the_withdrawal = $cps_api->CreateWithdrawal($withdrawals);


                if ($the_withdrawal["error"] != "ok") {
                    $the_status = false;
                    $mo->rollback();
                    // removed unlock/lock
                    $this->error('Your withdrawal could not be done !' . $the_withdrawal["error"]);

                } else {
                    $the_status = true;
                    $cp_withdrawal_id = $the_withdrawal["result"]["id"];
                    M('Myzc')->where(['id' => $id])->save(['hash' => $cp_withdrawal_id]);
                    $this->success('Successful Withdrawal!');
                }
            } else {
                $mo->rollback();
                // removed unlock/lock
                $this->error('Roll-out failure!' . implode('|', $rs) . $myzc['fee']);
            }
        }
        if (C('coin')[$coin]['type'] == 'cryptonote') {
            $cryptonote = CryptoNote($dj_address, $dj_port);
            $open_wallet = $cryptonote->open_wallet($dj_username, $dj_password);
			
            $json = json_decode($cryptonote->get_height());
            if (!isset($json->height) || $json->error != 0) {
                $status = 1;
                $this->error('CryptoNote Connection Failed ');
            }

            $Coin = M('Coin')->where(['name' => $myzc['coinname']])->find();

            $fee_user = M('UserCoin')->where([$coin . 'b' => $Coin['zc_user']])->find();
            $user_coin = M('UserCoin')->where(['userid' => $myzc['userid']])->find();
            $zhannei = M('UserCoin')->where([$coin . 'b' => $myzc['username']])->find();
            $mo = M();
            
            $mo->startTrans();
            $rs = [];

            if ($zhannei) {
                $rs[] = $mo->table('codono_myzr')->add(['userid' => $zhannei['userid'], 'username' => $myzc['username'], 'coinname' => $coin, 'txid' => md5($myzc['username'] . $user_coin[$coin . 'b'] . time()), 'num' => $myzc['num'], 'fee' => $myzc['fee'], 'mum' => $myzc['mum'], 'addtime' => time(), 'status' => 1]);
                $rs[] = $r = $mo->table('codono_user_coin')->where(['userid' => $zhannei['userid']])->setInc($coin, $myzc['mum']);
            }

            if (!$fee_user['userid']) {
                $fee_user['userid'] = 0;
            }

            if (0 < $myzc['fee']) {
                $rs[] = $mo->table('codono_myzc_fee')->add(['userid' => $fee_user['userid'], 'username' => $Coin['zc_user'], 'coinname' => $coin, 'num' => $myzc['num'], 'fee' => $myzc['fee'], 'mum' => $myzc['mum'], 'type' => 2, 'addtime' => time(), 'status' => 1]);

                if ($mo->table('codono_user_coin')->where([$coin . 'b' => $Coin['zc_user']])->find()) {
                    //$rs[] = $mo->table('codono_user_coin')->where(array('userid' => $Coin['zc_user']))->setInc($coin, $myzc['fee']);
                    debug(['lastsql' => $mo->table('codono_user_coin')->getLastSql()], 'Additional costs');
                } else {
                    $rs[] = $mo->table('codono_user_coin')->add([$coin . 'b' => $Coin['zc_user'], $coin => $myzc['fee']]);
                }
            }

            $rs[] = M('Myzc')->where(['id' => trim($id)])->save(['status' => 1]);

            if (check_arr($rs)) {
                $mo->commit();
                // removed unlock/lock
                $bal_info = json_decode($cryptonote->getBalance());
                $crypto_balance = $cryptonote->deAmount($bal_info->available_balance);
                if ($crypto_balance < (double)$myzc['mum']) {
                    $this->error('Wallet balance is ' . $crypto_balance . ' of less than required ' . $myzc['mum']);
                }

                $send_amt = round($myzc['mum'], 8);
				$transData = [
				[
					"amount" => $send_amt,
					"address" => $myzc['username']
				]
				];
                $sendrs = json_decode($cryptonote->transfer($transData, $myzc['dest_tag']));

                if ($sendrs->error == 0) {
                    if (isset($sendrs->tx_hash) && isset($sendrs->tx_key)) {
                        $flag = 1;
                    }
                } else {
                    $flag = 0;
                }
				
				$hash=$sendrs->tx_key;
				$txid =$sendrs->tx_hash;
				if($hash && $txid){
							M('Myzc')->where(['id' => $id])->save(['txid' => $sendrs->tx_hash,'hash'=>$sendrs->tx_key,'status'=>1]);
				}

                if (!$flag) {
                    $mo->rollback();
                    // removed unlock/lock
                    $this->error('wallet server Withdraw currency failure!');
                } else {
                    $this->success('Transfer success!');
                }
            } else {
                $mo->rollback();
                // removed unlock/lock
                $this->error('Roll-out failure!' . implode('|', $rs) . $myzc['fee']);
            }

        }
        if (C('coin')[$coin]['type'] == 'waves') {
            $waves = WavesClient($dj_username, $dj_password, $dj_address, $dj_port, $dj_decimal, 5, [], 1);
            $json = json_decode($waves->status(), true);
            $coinpay_coin = strtoupper($coin);


            if (!isset($json['blockchainHeight']) || $json['blockchainHeight'] <= 0) {
                $this->error($coin . 'Wallet link failure!');
            }


            $Coin = M('Coin')->where(['name' => $myzc['coinname']])->find();

            $fee_user = M('UserCoin')->where([$coin . 'b' => $Coin['zc_user']])->find();
            $user_coin = M('UserCoin')->where(['userid' => $myzc['userid']])->find();
            $zhannei = M('UserCoin')->where([$coin . 'b' => $myzc['username']])->find();
            $mo = M();
            
            $mo->startTrans();
            $rs = [];

            if ($zhannei) {
                $rs[] = $mo->table('codono_myzr')->add(['userid' => $zhannei['userid'], 'username' => $myzc['username'], 'coinname' => $coin, 'txid' => md5($myzc['username'] . $user_coin[$coin . 'b'] . time()), 'num' => $myzc['num'], 'fee' => $myzc['fee'], 'mum' => $myzc['mum'], 'addtime' => time(), 'status' => 1]);
                $rs[] = $r = $mo->table('codono_user_coin')->where(['userid' => $zhannei['userid']])->setInc($coin, $myzc['mum']);
            }

            if (!$fee_user['userid']) {
                $fee_user['userid'] = 0;
            }

            if (0 < $myzc['fee']) {
                $rs[] = $mo->table('codono_myzc_fee')->add(['userid' => $fee_user['userid'], 'username' => $Coin['zc_user'], 'coinname' => $coin, 'num' => $myzc['num'], 'fee' => $myzc['fee'], 'mum' => $myzc['mum'], 'type' => 2, 'addtime' => time(), 'status' => 1]);

                if ($mo->table('codono_user_coin')->where([$coin . 'b' => $Coin['zc_user']])->find()) {
                    //$rs[] = $mo->table('codono_user_coin')->where(array('userid' => $Coin['zc_user']))->setInc($coin, $myzc['fee']);
                    debug(['lastsql' => $mo->table('codono_user_coin')->getLastSql()], 'Additional costs');
                } else {
                    $rs[] = $mo->table('codono_user_coin')->add([$coin . 'b' => $Coin['zc_user'], $coin => $myzc['fee']]);
                }
            }

            $rs[] = M('Myzc')->where(['id' => trim($id)])->save(['status' => 1]);

            if (check_arr($rs)) {
                $mo->commit();
                // removed unlock/lock
                if (strlen($myzc['username']) > 30) {
                    $valid_res = 1;
                } else {
                    $valid_res = 0;
                }

                if ($valid_res = 0) {
                    $this->error($myzc['username'] . L(' It is not a valid address wallet!'));
                }


                if ($json->data->available_balance < (double)$myzc['mum']) {
                    $this->error(L('Wallet balance of less than'));
                }

                $balances = json_decode($waves->Balance($main_address, $dj_username), true);
                $wave_main_balance = $waves->deAmount($balances['balance'], $dj_decimal);
                if ($wave_main_balance < $myzc['mum']) {
                    $this->error('Wallet balance is ' . $wave_main_balance . ' Needed: ' . $myzc['mum']);
                }


                $send_amt = round($myzc['mum'], 8);
                $wavesend_response = $waves->Send($main_address, $myzc['username'], $send_amt, $dj_username);
                $the_withdrawal = json_decode($wavesend_response, true);

                if ($the_withdrawal["error"]) {
                    $flag = 0;
                    $error_message = $the_withdrawal["message"];
                } else {

                    $flag = 1;
                }

                if ($flag != 1) {
                    $mo->rollback();
                    // removed unlock/lock
                    $this->error('Failed!' . $error_message);
                } else {
                    $mo->commit();
                    // removed unlock/lock
                    M('Myzc')->where(['id' => $id])->save(['txid' => $the_withdrawal['id']]);
                    $this->success('Transfer success!');
                }
            } else {
                $mo->rollback();
                // removed unlock/lock
                $this->error('Roll-out failure!' . implode('|', $rs) . $myzc['fee']);
            }

        }
        if (C('coin')[$coin]['type'] == 'qbb') {

            $CoinClient = CoinClient($dj_username, $dj_password, $dj_address, $dj_port, 5, [], 1);
            $json = $CoinClient->getinfo();

            if (!isset($json['version']) || !$json['version']) {
                $this->error('Wallet link failure! 3');
            }

            $Coin = M('Coin')->where(['name' => $myzc['coinname']])->find();
            $fee_user = M('UserCoin')->where([$coin . 'b' => $Coin['zc_user']])->find();
            $user_coin = M('UserCoin')->where(['userid' => $myzc['userid']])->find();
            $zhannei = M('UserCoin')->where([$coin . 'b' => $myzc['username']])->find();
            $mo = M();
            
            $mo->startTrans();
            $rs = [];

            if ($zhannei) {
                $rs[] = $mo->table('codono_myzr')->add(['userid' => $zhannei['userid'], 'username' => $myzc['username'], 'coinname' => $coin, 'txid' => md5($myzc['username'] . $user_coin[$coin . 'b'] . time()), 'num' => $myzc['num'], 'fee' => $myzc['fee'], 'mum' => $myzc['mum'], 'addtime' => time(), 'status' => 1]);
                $rs[] = $r = $mo->table('codono_user_coin')->where(['userid' => $zhannei['userid']])->setInc($coin, $myzc['mum']);
            }

            if (!$fee_user['userid']) {
                $fee_user['userid'] = 0;
            }

            if (0 < $myzc['fee']) {
                $rs[] = $mo->table('codono_myzc_fee')->add(['userid' => $fee_user['userid'], 'username' => $Coin['zc_user'], 'coinname' => $coin, 'num' => $myzc['num'], 'fee' => $myzc['fee'], 'mum' => $myzc['mum'], 'type' => 2, 'addtime' => time(), 'status' => 1]);

                if ($mo->table('codono_user_coin')->where([$coin . 'b' => $Coin['zc_user']])->find()) {
                    //$rs[] = $mo->table('codono_user_coin')->where(array('userid' => $Coin['zc_user']))->setInc($coin, $myzc['fee']);
                    debug(['lastsql' => $mo->table('codono_user_coin')->getLastSql()], 'Additional costs');
                } else {
                    $rs[] = $mo->table('codono_user_coin')->add([$coin . 'b' => $Coin['zc_user'], $coin => $myzc['fee']]);
                }
            }

            $rs[] = M('Myzc')->where(['id' => trim($id)])->save(['status' => 1]);

            if (check_arr($rs)) {
                $mo->commit();
                // removed unlock/lock
                //check if this is token under btc type chain
                $contract=C('coin')[$coin]['contract'];
                if($contract){
                    $sendrs = $CoinClient->token('send',$contract,$myzc['username'], (double)$myzc['mum']);     
                }else{
                    $sendrs = $CoinClient->sendtoaddress($myzc['username'], (double)$myzc['mum']);     
                }
                

                if ($sendrs) {
                    $flag = 1;
                    $arr = json_decode($sendrs, true);

                    if (isset($arr['status']) && ($arr['status'] == 0)) {
                        $flag = 0;
                    }
                } else {
                    $flag = 0;
                }

                if (!$flag) {
                    $mo->rollback();
                    // removed unlock/lock
                    $this->error('wallet server Withdraw currency failure!');
                } else {
					$arr = json_decode($sendrs, true);
                    M('Myzc')->where(['id' => $id])->save(['txid' => $arr['result']]);
                    $this->success('Transfer success!');
                }
            } else {
                $mo->rollback();
                // removed unlock/lock
                $this->error('Roll-out failure!' . implode('|', $rs) . $myzc['fee']);
            }
        }
    }

    /**
     * @param $id
     * @param $type
     * @param string $model
     * @return void
     */
    private function sub_status($id, $type, string $model): void
    {
        if (empty($id)) {
            $this->error(L('INCORRECT_REQ'));
        }

        if (empty($type)) {
            $this->error(L('INCORRECT_TYPE'));
        }

        if (strpos(',', $id)) {
            $id = implode(',', $id);
        }

        $where['id'] = ['in', $id];

        switch (strtolower($type)) {
            case 'forbid':
                $data = ['status' => 0];
                break;

            case 'resume':
                $data = ['status' => 1];
                break;

            case 'repeal':
                $data = ['status' => 2, 'endtime' => time()];
                break;

            case 'delete':
                $data = ['status' => -1];
                break;

            case 'del':
                if (M($model)->where($where)->delete()) {
                    $this->success(L('SUCCESSFULLY_DONE'));
                } else {
                    $this->error(L('OPERATION_FAILED'));
                }

                break;

            default:
                $this->error('operation failed1!');
        }

        if (M($model)->where($where)->save($data)) {
            $this->success(L('SUCCESSFULLY_DONE'));
        } else {
            $this->error('operation failed2!');
        }
    }

    /**
     * @param $list
     * @param $k
     * @return array
     */
    private function sub_excel($list, $k): array
    {
        if ($list[$k]['status'] == 0) {
            $list[$k]['status'] = '--';
        } else if ($list[$k]['status'] == 1) {
            $list[$k]['status'] = 'normal';
        }


        unset($list[$k]['remark']);
        unset($list[$k]['nameid']);
        unset($list[$k]['move']);
        unset($list[$k]['num_a']);
        unset($list[$k]['mum_a']);
        unset($list[$k]['num_b']);
        unset($list[$k]['mum_b']);
        unset($list[$k]['num']);
        unset($list[$k]['mum']);
        return $list;
    }

    private function findNameList($key){
        $tag_list = [
            'p2p' => 'P2P',
            'mycz' => 'Fiat In',
            'mytx' => 'Fiat Out',
            'issue' => 'ICO',
            'investbox' => 'InvestBox',
            'DiceRoll' => 'Dice Roll',
            'otc' => 'OTC',
            'Staff Credit' => 'Staff Credit',
            'Staff Spent' => '<span style="color:red">Staff Spent</span>',
            'trade' => 'Trade Placed',
            'tradelog' => 'Trade Matched',
            'issue' => 'ICO'
        ];
        $link_list = ['p2p' => U('/Admin/P2p/Order'),'mycz' => U('/Admin/Finance/mycz/field/id/name/'), 'mytx' => U('/Admin/Finance/mytx/field/id/name/'), 'trade' => U('/Admin/Trade/index/field/id/name/'), 'tradelog' => U('Admin/Trade/log/field/id/name/'), 'issue' => U('Admin/Issue/index/field/id/name/'),'otc' => U('/Admin/OTC/Log/field/id/name/'),'investbox' => U('/Admin/Invest/investlist/field/id/name/'),'DiceRoll'=>'/Admin/Invest/dicerolls/field/id/name/','Staff Credit'=>'/Admin/Activity/index/field/id/name/','Staff Spent'=>'/Admin/Activity/index/field/id/name/'];
        if(in_array($key,$tag_list)){
            return $tag_list[$key];
        }else{
            return $key;
        }
    }

}
