<?php

namespace Home\Controller;

class PlansController extends HomeController
{
    const ENABLE_SUBS = ENABLE_SUBS;  // Enable Disable Subscriptions
    const ALLOWED_PLANS = SUBSCRIPTION_PLANS;
    const PREMIUM_PLUS_CONDITION = 10; //userid <PREMIUM_PLUS_CONDITION;

    public function _initialize()
    {
        if (self::ENABLE_SUBS == 0) {
            $this->assign('type', 'Oops');
            $this->assign('error', 'Oops, Currently Subscriptions are disabled!');
            $this->display('Content/error_specific');
            exit;
        }
        parent::_initialize();
    }

    public function index()
    {
        $current = 'basic';
		
        $this->assign('current', $current);
        $this->assign('plans', self::ALLOWED_PLANS);
        $this->display();
    }

    public function buy($id = 0)
    {
        if (!userid()) {
            $this->error(L('PLEASE_LOGIN'));
        }

        if ($id == 0 || self::ENABLE_SUBS == 0) {
            $this->error(L('You can not subscribe'));
        }
        $user_plan=[];
        if (usertype() > 0) {
            foreach (self::ALLOWED_PLANS as $splan) {
                if ($splan['id'] == usertype()) {
                    $user_plan = $splan;
                }
            }
            $this->error('You already have ' . $user_plan['name'] . ' subscription!');
        }
        if (!check_kyc()) {
            $this->error(L('You need to complete KYC first!'));
        }

        if (!is_array(D('Coin')->get_all_name_list())) {
            $this->error('Parameter error2!');
        }
        $condition = 0;
        $selected_plan = array();
        foreach (self::ALLOWED_PLANS as $plan) {
            if ($plan['id'] == $id) {
                $condition = 1;
                $selected_plan = $plan;
            }
        }
        $coin = $selected_plan['coin'];
        if (empty($selected_plan)) {
            $this->error('No such subscription plan!');
        }
        if ($selected_plan['price'] == 0 && userid() > self::PREMIUM_PLUS_CONDITION) {
            $this->error('This plan is for first ' . self::PREMIUM_PLUS_CONDITION . ' users only');
        }

        if ($condition) {
            $userbalance = M('UserCoin')->where(array('userid' => userid()))->getField($coin);
            if (floatval($userbalance) < floatval($selected_plan['price'])) {
                $this->error('Subscription requireds' . $selected_plan['coin'] . $selected_plan['price'] . ',  You have insufficient');
            }

        } else {
            $this->error(L('Subscription does not exist'));
        }
        $validity_months = $selected_plan['duration'];
        $ends = strtotime("+$validity_months months", time());

        $mo = M();
        $mo->startTrans();
        $rs = array();
        $subs_arr = array('uid' => userid(), 'subid' => $selected_plan['id'], 'addtime' => time(), 'endtime' => $ends, 'status' => 1);
        $finance = $mo->table('codono_finance')->where(array('userid' => userid()))->order('id desc')->find();
        $finance_num_user_coin = $mo->table('codono_user_coin')->where(array('userid' => userid()))->find();
        $rs[] = $mo->table('codono_user_coin')->where(array('userid' => userid()))->setDec($coin, $selected_plan['price']);
        $rs[] = $mo->table('codono_user_subscription')->add($subs_arr);
        $rs[] = $mo->table('codono_user')->where(array('userid' => userid()))->setField('usertype', $selected_plan['id']);
        $finance_mum_user_coin = $required_bal = $mo->table('codono_user_coin')->where(array('userid' => userid()))->find();
        $finance_hash = md5(userid() . $finance_num_user_coin[$coin] . $finance_num_user_coin[$coin . 'd'] . time() . rand(100000, 999999));
        $finance_num = $finance_num_user_coin[$coin] + $finance_num_user_coin[$coin . 'd'];

        if ($finance['mum'] < $finance_num) {
            $finance_status = (1 < ($finance_num - $finance['mum']) ? 0 : 1);
        } else {
            $finance_status = (1 < ($finance['mum'] - $finance_num) ? 0 : 1);
        }

        $fin_arr = array('userid' => userid(), 'coinname' => $coin, 'num_a' => $finance_num_user_coin[$coin], 'num_b' => $finance_num_user_coin[$coin . 'd'], 'num' => $finance_num_user_coin[$coin] + $finance_num_user_coin[$coin . 'd'], 'fee' => 0, 'type' => 2, 'name' => 'Subscription', 'nameid' => $selected_plan['id'], 'remark' => 'plan_purchase:' . $selected_plan['name'], 'mum_a' => $finance_mum_user_coin[$coin], 'mum_b' => $finance_mum_user_coin[$coin . 'd'], 'mum' => $finance_mum_user_coin[$coin] + $finance_mum_user_coin[$coin . 'd'], 'move' => $finance_hash, 'addtime' => time(), 'status' => $finance_status);
        clog('subs_arr', $subs_arr);
        clog('fin_arr', $fin_arr);
        clog('rs', $rs);
        $rs[] = $mo->table('codono_finance')->add($fin_arr);

        if (check_arr($rs)) {
            $mo->commit();
            
            $this->success('Successfully Subscribed!');
        } else {
            $mo->rollback();
            $this->error(L('OPERATION_FAILED'));
        }

    }

}
