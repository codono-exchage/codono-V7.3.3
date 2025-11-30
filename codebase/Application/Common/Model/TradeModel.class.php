<?php

namespace Common\Model;
namespace Think\Exception;
use Think\Model;

class TradeModel extends Model
{
    protected $keyS = 'Trade';

    /**
     * @param $id
     * @return string[]
     */
    public function forceMatchSpecial($id = NULL,$special_price=NULL)
    {
        if (!check($id, 'd')) {
            return ['0', 'Incorrect ID``'];
        }
        if (!$special_price) {
            return ['0', 'Please enter a price'];
        }
        $trade = M('Trade')->where(['id' => $id])->find();
          
        if (!$trade) {
            return ['0', 'Order does not exist'];
        }


        if ($trade['status'] != 0) {
            return ['0', 'Orders can not be undone'];
        }
        $market = $trade['market'];
        $xnb = explode('_', $market)[0];
        $rmb = explode('_', $market)[1];

        $price = $special_price;
        $type = $trade['type'];

        $fee_buy = C('market')[$trade['market']]['fee_buy'];
        $fee_sell = C('market')[$trade['market']]['fee_sell'];

        if (!$xnb) {
            return ['0', 'Sell market error'];
        }

        if (!$rmb) {
            return ['0', 'Buy market error'];
        }
        if ($trade['userid'] == 0) {
            return ['0', 'You can undo this order as its Liquidity Order , not required to match'];
        }

        $mo = M();
        $user_coin = $mo->table('codono_user_coin')->where(['userid' => $trade['userid']])->find();
        $mo->startTrans();
        $rs = [];
        if ($type == 1) {
            $amount = bcsub($trade['num'], $trade['deal'], 8);
            $amount=bcdiv($trade['mum'],$special_price,8);
            $remaining_locked = bcmul($amount, $special_price, 8);
            $fee_for_remaining = bcmul(bcdiv($trade['fee'], $amount, 8), $amount, 8);

            $remove_from_freeze = bcadd($remaining_locked, $fee_for_remaining, 8);

            $rs[] = $mo->table('codono_user_coin')->where(['userid' => $trade['userid']])->setInc($xnb, $amount);
            $rs[] = $mo->table('codono_user_coin')->where(['userid' => $trade['userid']])->setDec($rmb . 'd', $remove_from_freeze);
            $rs[] = $mo->table('codono_trade')->where(['id' => $trade['id']])->setInc('deal', $amount);

            $rs[] = $mo->table('codono_trade')->where(['id' => $trade['id']])->save(['status'=> 1,'price'=>$special_price,'num'=>$amount]);
            $rs[] = $mo->table('codono_trade_log')->add(['userid' => $trade['userid'], 'peerid' => 0, 'market' => $market, 'price' => $price, 'num' => $amount, 'mum' => $remaining_locked, 'type' => $type, 'fee_buy' => $fee_for_remaining, 'fee_sell' => 0, 'addtime' => time(), 'status' => 1]);

        } else if ($type == 2) {
            $amount = bcsub($trade['num'], $trade['deal'], 8);
            $remaining_locked = bcsub($trade['num'], $trade['deal'], 8);

            $effective_price = bcdiv($trade['mum'], $trade['num'], 8);
            $credit_rmb = bcmul($amount, $special_price, 8);
            $sell_fee = bcdiv(bcmul($credit_rmb, $fee_sell, 8), 100, 8);
            $rs[] = $mo->table('codono_user_coin')->where(['userid' => $trade['userid']])->setInc($rmb, $credit_rmb);
            $rs[] = $mo->table('codono_user_coin')->where(['userid' => $trade['userid']])->setDec($xnb . 'd', $remaining_locked);
            $rs[] = $mo->table('codono_trade')->where(['id' => $trade['id']])->setInc('deal', $amount);
            $rs[] = $mo->table('codono_trade')->where(['id' => $trade['id']])->save(['status'=> 1,'price'=>$special_price,'mum'=>$credit_rmb]);
            $rs[] = $mo->table('codono_trade_log')->add(['userid' => 0, 'peerid' => $trade['userid'], 'market' => $market, 'price' => $price, 'num' => $amount, 'mum' => $credit_rmb, 'type' => $type, 'fee_buy' => 0, 'fee_sell' => $sell_fee, 'addtime' => time(), 'status' => 1]);

        }
        if (strict_check_arr($rs)) {
            $mo->commit();
            S('getDepth', null);
            S('getActiveDepth' . $market, null);
            S('getActiveDepth', null);
            S('getDepthNew', null);
            return ['1', 'Order has been Force Executed'];
        } else {
            $mo->rollback();
            return ['0', 'Execution failed|' . implode('|', $rs)];
        }
    }
    public function forcematch($id = NULL)
    {
        if (!check($id, 'd')) {
            return ['0', 'Incorrect ID``'];
        }

        $trade = M('Trade')->where(['id' => $id])->find();

        if (!$trade) {
            return ['0', 'Order does not exist'];
        }

        if ($trade['status'] != 0) {
            return ['0', 'Orders can not be undone'];
        }
        $market = $trade['market'];
        $xnb = explode('_', $market)[0];
        $rmb = explode('_', $market)[1];

        $price = $trade['price'];
        $type = $trade['type'];

        $fee_buy = C('market')[$trade['market']]['fee_buy'];
        $fee_sell = C('market')[$trade['market']]['fee_sell'];

        if (!$xnb) {
            return ['0', 'Sell market error'];
        }

        if (!$rmb) {
            return ['0', 'Buy market error'];
        }
        if ($trade['userid'] == 0) {
            return ['0', 'You can undo this order as its Liquidity Order , not required to match'];
        }

        $mo = M();
        $user_coin = $mo->table('codono_user_coin')->where(['userid' => $trade['userid']])->find();
        $mo->startTrans();
        $rs = [];
        if ($type == 1) {
            $amount = bcsub($trade['num'], $trade['deal'], 8);
            $remaining_locked = bcmul($amount, $trade['price'], 8);
            $fee_for_remaining = bcmul(bcdiv($trade['fee'], $trade['num'], 8), $amount, 8);

            $remove_from_freeze = bcadd($remaining_locked, $fee_for_remaining, 8);

            $rs[] = $mo->table('codono_user_coin')->where(['userid' => $trade['userid']])->setInc($xnb, $amount);
            $rs[] = $mo->table('codono_user_coin')->where(['userid' => $trade['userid']])->setDec($rmb . 'd', $remove_from_freeze);
            $rs[] = $mo->table('codono_trade')->where(['id' => $trade['id']])->setInc('deal', $amount);

            $rs[] = $mo->table('codono_trade')->where(['id' => $trade['id']])->setField('status', 1);
            $rs[] = $mo->table('codono_trade_log')->add(['userid' => $trade['userid'], 'peerid' => 0, 'market' => $market, 'price' => $price, 'num' => $amount, 'mum' => $remaining_locked, 'type' => $type, 'fee_buy' => $fee_for_remaining, 'fee_sell' => 0, 'addtime' => time(), 'status' => 1]);

        } else if ($type == 2) {
            $amount = bcsub($trade['num'], $trade['deal'], 8);
            $remaining_locked = bcsub($trade['num'], $trade['deal'], 8);

            $effective_price = bcdiv($trade['mum'], $trade['num'], 8);
            $credit_rmb = bcmul($amount, $effective_price, 8);
            $sell_fee = bcdiv(bcmul($credit_rmb, $fee_sell, 8), 100, 8);
            $rs[] = $mo->table('codono_user_coin')->where(['userid' => $trade['userid']])->setInc($rmb, $credit_rmb);
            $rs[] = $mo->table('codono_user_coin')->where(['userid' => $trade['userid']])->setDec($xnb . 'd', $remaining_locked);
            $rs[] = $mo->table('codono_trade')->where(['id' => $trade['id']])->setInc('deal', $amount);
            $rs[] = $mo->table('codono_trade')->where(['id' => $trade['id']])->setField('status', 1);
            $rs[] = $mo->table('codono_trade_log')->add(['userid' => 0, 'peerid' => $trade['userid'], 'market' => $market, 'price' => $price, 'num' => $amount, 'mum' => $credit_rmb, 'type' => $type, 'fee_buy' => 0, 'fee_sell' => $sell_fee, 'addtime' => time(), 'status' => 1]);

        }
        if (strict_check_arr($rs)) {
            $mo->commit();
            S('getDepth', null);
            S('getActiveDepth' . $market, null);
            S('getActiveDepth', null);
            S('getDepthNew', null);
            return ['1', 'Order has been Force Executed'];
        } else {
            $mo->rollback();
            return ['0', 'Execution failed|' . implode('|', $rs)];
        }
    }

    public function adminreject($id = NULL)
    {
        if (!check($id, 'd')) {
            return ['0', 'Parameter error'];
        }

        $trade = M('Trade')->where(['id' => $id])->find();

        if (!$trade) {
            return ['0', 'Order does not exist'];
        }

        if ($trade['status'] != 0) {
            return ['0', 'Orders can not be undone'];
        }

        $xnb = explode('_', $trade['market'])[0];
        $rmb = explode('_', $trade['market'])[1];

        if (!$xnb) {
            return ['0', 'Sell market error'];
        }

        if (!$rmb) {
            return ['0', 'Buy market error'];
        }
        if ($trade['userid'] == 0) {
            $mo = M();
            $info = $mo->table('codono_trade')->where(['id' => $trade['id']])->setField('status', 2);
            $market = $trade['market'];
            S('getActiveDepth' . $market, null);
            return ['1', 'Liquidity Order Undone'];
        }
        $fee_buy = C('market')[$trade['market']]['fee_buy'];
        $fee_sell = C('market')[$trade['market']]['fee_sell'];

        if ($fee_buy < 0) {
            return ['0', 'BUY fee error'];
        }

        if ($fee_sell < 0) {
            return ['0', 'Error handling sell'];
        }
        $market = $trade['market'];
        $user_coin = M('UserCoin')->where(['userid' => $trade['userid']])->find();
        $mo = M();
        $mo->startTrans();

        $rs = [];
        $user_coin = $mo->table('codono_user_coin')->where(['userid' => $trade['userid']])->find();

        if ($trade['type'] == 1) {
            $mun = format_num(bcmul(bcdiv(bcmul(bcsub($trade['num'], $trade['deal'], 8), $trade['price'], 8), 100, 8), bcadd(100, $fee_buy, 8), 8), 8);
            $user_buy = $mo->table('codono_user_coin')->where(['userid' => $trade['userid']])->find();

            if ($mun <= round($user_buy[$rmb . 'd'], 8)) {
                $save_buy_rmb = $mun;
            } else if ($mun <= round($user_buy[$rmb . 'd'], 8) + 1) {
                $save_buy_rmb = $user_buy[$rmb . 'd'];
            } else {
                $mo->rollback();
                //$mo->rollback();
                //
                M('Trade')->where(['id' => $id])->setField('status', 2);
                //$mo->commit();
                $mo->commit();
                return ['0', 'Undo failed1'];
            }

            $finance = $mo->table('codono_finance')->where(['userid' => $trade['userid']])->order('id desc')->find();
            $finance_num_user_coin = $mo->table('codono_user_coin')->where(['userid' => $trade['userid']])->find();
            $rs[] = $mo->table('codono_user_coin')->where(['userid' => $trade['userid']])->setInc($rmb, $save_buy_rmb);
            $rs[] = $mo->table('codono_user_coin')->where(['userid' => $trade['userid']])->setDec($rmb . 'd', $save_buy_rmb);
            $finance_nameid = $trade['id'];

            $finance_mum_user_coin = $mo->table('codono_user_coin')->where(['userid' => $trade['userid']])->find();
            $finance_hash = md5($trade['userid'] . $finance_num_user_coin[$rmb] . $finance_num_user_coin[$rmb . 'd'] . $save_buy_rmb . $finance_mum_user_coin[$rmb] . $finance_mum_user_coin[$rmb . 'd'] . CODONOLIC . 'auth.codono.com');
            $finance_num = $finance_num_user_coin[$rmb] + $finance_num_user_coin[$rmb . 'd'];

            if ($finance['mum'] < $finance_num) {
                $finance_status = (1 < ($finance_num - $finance['mum']) ? 0 : 1);
            } else {
                $finance_status = (1 < ($finance['mum'] - $finance_num) ? 0 : 1);
            }

            $rs[] = $mo->table('codono_finance')->add(['userid' => $trade['userid'], 'coinname' => $rmb, 'num_a' => $finance_num_user_coin[$rmb], 'num_b' => $finance_num_user_coin[$rmb . 'd'], 'num' => $finance_num_user_coin[$rmb] + $finance_num_user_coin[$rmb . 'd'], 'fee' => $save_buy_rmb, 'type' => 1, 'name' => 'trade', 'nameid' => $finance_nameid, 'remark' => 'Transaction Reversal ' . $trade['market'], 'mum_a' => $finance_mum_user_coin[$rmb], 'mum_b' => $finance_mum_user_coin[$rmb . 'd'], 'mum' => $finance_mum_user_coin[$rmb] + $finance_mum_user_coin[$rmb . 'd'], 'move' => $finance_hash, 'addtime' => time(), 'status' => $finance_status]);
            $rs[] = $mo->table('codono_trade')->where(['id' => $trade['id']])->setField('status', 2);
            $you_buy = $mo->table('codono_trade')->where([
                'market' => ['like', '%' . $rmb . '%'],
                'status' => 0,
                'userid' => $trade['userid']
            ])->find();

            if (!$you_buy) {
                $you_user_buy = $mo->table('codono_user_coin')->where(['userid' => $trade['userid']])->find();

                if (0 < $you_user_buy[$rmb . 'd']) {
                    $rs[] = $mo->table('codono_user_coin')->where(['userid' => $trade['userid']])->setField($rmb . 'd', 0);
                }
            }
        } else if ($trade['type'] == 2) {
            $mun = round($trade['num'] - $trade['deal'], 8);
            $user_sell = $mo->table('codono_user_coin')->where(['userid' => $trade['userid']])->find();
            if ($mun < 0) {
                $mo->rollback();
                M('Trade')->where(['id' => $id])->setField('status', 2);
                $mo->commit();
                return ['1', 'Undone!'];
            }
            if ($mun <= round($user_sell[$xnb . 'd'], 8)) {
                $save_sell_xnb = $mun;
            } else if ($mun <= round($user_sell[$xnb . 'd'], 8) + 1) {
                $save_sell_xnb = $user_sell[$xnb . 'd'];
            } else {
                $mo->rollback();
                //  $mo->rollback();
                M('Trade')->where(['id' => $trade['id']])->setField('status', 2);
                $mo->commit();
                //$mo->commit();
                return ['0', 'Undo failed2'];
            }

            if (0 < $save_sell_xnb) {
                $rs[] = $mo->table('codono_user_coin')->where(['userid' => $trade['userid']])->setInc($xnb, $save_sell_xnb);
                $rs[] = $mo->table('codono_user_coin')->where(['userid' => $trade['userid']])->setDec($xnb . 'd', $save_sell_xnb);
            }

            $rs[] = $mo->table('codono_trade')->where(['id' => $trade['id']])->setField('status', 2);
            $you_sell_where = [
                'market' => ['like', $xnb . '%'],
                'status' => 0,
                'userid' => $trade['userid']
            ];
            $you_sell = $mo->table('codono_trade')->where($you_sell_where)->find();

            if (!$you_sell) {
                $you_user_sell = $mo->table('codono_user_coin')->where(['userid' => $trade['userid']])->find();

                if (0 < $you_user_sell[$xnb . 'd']) {
                    $mo->table('codono_user_coin')->where(['userid' => $trade['userid']])->setField($xnb . 'd', 0);
                }
            }
        } else {
            $mo->rollback();

            return ['0', 'Undo failed3'];
        }

        if (strict_check_arr($rs)) {
            $mo->commit();
            S('getDepth', null);
            S('getActiveDepth' . $market, null);
            S('getActiveDepth', null);
            S('getDepthNew', null);
            return ['1', 'Order has been canceled'];
        } else {
            $mo->rollback();
            return ['0', 'Undo failed4|' . implode('|', $rs)];
        }
    }

    public function reject($id = NULL)
    {
        if (!check($id, 'd')) {
            return ['0', 'Parameter error'];
        }
        $where = ['id' => $id, 'status' => 0];
        $trade = M('Trade')->where($where)->find();

        if (!$trade) {
            return ['0', 'Order does not exist'];
        }

        if ($trade['status'] != 0) {
            return ['0', 'Orders can not be undone'];
        }
        $market = $trade['market'];
        list($xnb, $rmb) = explode('_', $trade['market']);
        if (!$xnb || !$rmb) {
            return ['0', 'Market error'];
        }

        $fee_buy = C('market')[$trade['market']]['fee_buy'];
        $fee_sell = C('market')[$trade['market']]['fee_sell'];

        if ($fee_buy < 0 || $fee_sell < 0) {
            return ['0', 'Fee configuration error'];
        }

        $mo = M();
        $mo->startTrans();
        $rs = [];
        try {

             $user_buy=$user_sell = $mo->table('codono_user_coin')->where(['userid' => $trade['userid']])->find();
            if ($trade['type'] == 1) {
                $remaining_amount = format_num(bcmul(bcdiv(bcmul(bcsub($trade['num'], $trade['deal'], 8), $trade['price'], 8), 100, 8), bcadd(100, $fee_buy, 8), 8), 8);

                if (!$remaining_amount || $remaining_amount <= 0.00000001 || $remaining_amount == NULL) {
                    M('Trade')->where(['id' => $id])->setField('status', 2);

                    $mo->commit();
                    return ['1', 'Undo Done'];
                }

                if ($remaining_amount <= format_num($user_buy[$rmb . 'd'], 8)) {
                    $save_buy_rmb = $remaining_amount;
                } elseif ($remaining_amount <= bcadd($user_buy[$rmb . 'd'] , 0.00000001,8)) {
                    $save_buy_rmb = $user_buy[$rmb . 'd'];
                } elseif($this->bcround($remaining_amount) <= $this->bcround($user_buy[$rmb . 'd'])) {
					$save_buy_rmb = $user_buy[$rmb . 'd'];
				}else{
                    $mo->rollback();
                    return ['0', 'Undo failed-2'];
                }

                $finance = $mo->table('codono_finance')->where(['userid' => $trade['userid']])->order('id desc')->find();
                $finance_num_user_coin = $mo->table('codono_user_coin')->where(['userid' => $trade['userid']])->find();
                $rs[] = $report[__LINE__] = $mo->table('codono_user_coin')->where(['userid' => $trade['userid']])->setInc($rmb, $save_buy_rmb);
                $report[__LINE__]['action'] = [$rmb, $save_buy_rmb];

                $rs[] = $report[__LINE__] = $mo->table('codono_user_coin')->where(['userid' => $trade['userid']])->setDec($rmb . 'd', $save_buy_rmb);
                $finance_nameid = $trade['id'];

                $finance_mum_user_coin = $mo->table('codono_user_coin')->where(['userid' => $trade['userid']])->find();
                $finance_hash = md5($trade['userid'] . $finance_num_user_coin[$rmb] . $finance_num_user_coin[$rmb . 'd'] . $save_buy_rmb . $finance_mum_user_coin[$rmb] . $finance_mum_user_coin[$rmb . 'd'] . CODONOLIC . 'auth.codono.com');
                $finance_num = $finance_num_user_coin[$rmb] + $finance_num_user_coin[$rmb . 'd'];

                if ($finance['mum'] < $finance_num) {
                    $finance_status = (1 < ($finance_num - $finance['mum']) ? 0 : 1);
                } else {
                    $finance_status = (1 < ($finance['mum'] - $finance_num) ? 0 : 1);
                }

                $rs[] = $report[__LINE__] = $mo->table('codono_finance')->add(['userid' => $trade['userid'], 'coinname' => $rmb, 'num_a' => $finance_num_user_coin[$rmb], 'num_b' => $finance_num_user_coin[$rmb . 'd'], 'num' => $finance_num_user_coin[$rmb] + $finance_num_user_coin[$rmb . 'd'], 'fee' => $save_buy_rmb, 'type' => 1, 'name' => 'trade', 'nameid' => $finance_nameid, 'remark' => 'Transaction Reversal ' . $trade['market'], 'mum_a' => $finance_mum_user_coin[$rmb], 'mum_b' => $finance_mum_user_coin[$rmb . 'd'], 'mum' => $finance_mum_user_coin[$rmb] + $finance_mum_user_coin[$rmb . 'd'], 'move' => $finance_hash, 'addtime' => time(), 'status' => $finance_status]);
                $rs[] = $report[__LINE__] = $mo->table('codono_trade')->where(['id' => $trade['id']])->setField('status', 2);
                $you_buy = $mo->table('codono_trade')->where([
                    'market' => ['like', '%' . $rmb . '%'],
                    'status' => 0,
                    'userid' => $trade['userid']
                ])->find();

                if (!$you_buy) {
                    $you_user_buy = $mo->table('codono_user_coin')->where(['userid' => $trade['userid']])->find();

                    if (0 < $you_user_buy[$rmb . 'd']) {
                        $rs[] = $report[__LINE__] = $mo->table('codono_user_coin')->where(['userid' => $trade['userid']])->setField($rmb . 'd', 0);
                    }
                }
            } else if ($trade['type'] == 2) {
                $mun = bcsub($trade['num'], $trade['deal'], 8);
                if ($mun < 0) {
                    $mo->rollback();
                    M('Trade')->where(['id' => $id])->setField('status', 2);
                    return ['1', 'Undone!'];
                }
                if ($mun <= format_num($user_sell[$xnb . 'd'], 8)) {
                    $save_sell_xnb = $mun;
                } else if ($mun <= bcadd($user_sell[$xnb . 'd'],  0.00000001,8)) {
                    $save_sell_xnb = $user_sell[$xnb . 'd'];
                } else {
                    $mo->rollback();
						//M('Trade')->where(['id' => $trade['id']])->setField('status', 2);
                    return ['0', 'Undo failed:358'];
                }

                if (0 < $save_sell_xnb) {
                    $rs[] = $mo->table('codono_user_coin')->where(['userid' => $trade['userid']])->setInc($xnb, $save_sell_xnb);
                    $rs[] = $mo->table('codono_user_coin')->where(['userid' => $trade['userid']])->setDec($xnb . 'd', $save_sell_xnb);
                }

                $rs[] = $mo->table('codono_trade')->where(['id' => $trade['id']])->setField('status', 2);
                $you_sell_where = [
                    'market' => ['like', $xnb . '%'],
                    'status' => 0,
                    'userid' => $trade['userid']
                ];
                $you_sell = $mo->table('codono_trade')->where($you_sell_where)->find();

                if (!$you_sell) {
                    $you_user_sell = $mo->table('codono_user_coin')->where(['userid' => $trade['userid']])->find();

                    if (0 < $you_user_sell[$xnb . 'd']) {
                       $rs[]= $mo->table('codono_user_coin')->where(['userid' => $trade['userid']])->setField($xnb . 'd', 0);
                    }
                }
            } else {
                $mo->rollback();
                return ['0', 'Undo failed3'];
            }

            if (strict_check_arr($rs)) {
                $mo->commit();
                S('getDepth', null);
                S('getActiveDepth' . $market, null);
                S('getActiveDepth', null);
                S('getDepthNew', null);
                return ['1', 'Order has been canceled'];
            } else {
                $mo->rollback();
                return ['0', 'Undo failed4|' . implode('|', $rs)];
            }

        }//end try
        catch (\Exception $e) {
			$mo->rollback();
            if (M_DEBUG) {
                clog('TradeModel-reject', $e->getMessage());
            }
            return ['0', 'Undo Exception!'];
        }
    }


    public function stopreject($id = NULL)
    {
        if (!check($id, 'd')) {
            return ['0', 'Parameter error'];
        }

        $trade = M('Stop')->where(['id' => $id, 'userid' => userid()])->find();

        if (!$trade) {
            return ['0', 'Order does not exist'];
        }

        if ($trade['status'] != 0) {
            return ['0', 'Orders can not be undone'];
        }

        $xnb = explode('_', $trade['market'])[0];
        $rmb = explode('_', $trade['market'])[1];

        if (!$xnb) {
            return ['0', 'Sell market error'];
        }

        if (!$rmb) {
            return ['0', 'Buy market error'];
        }

        $fee_buy = C('market')[$trade['market']]['fee_buy'];
        $fee_sell = C('market')[$trade['market']]['fee_sell'];

        if ($fee_buy < 0) {
            return ['0', 'BUY fee error'];
        }

        if ($fee_sell < 0) {
            return ['0', 'Error handling sell'];
        }
        $market = $trade['market'];
        $user_coin = M('UserCoin')->where(['userid' => $trade['userid']])->find();
        $mo = M();

        $mo->startTrans();
        $rs = [];
        $user_coin = $mo->table('codono_user_coin')->where(['userid' => $trade['userid']])->find();

        if ($trade['type'] == 1) {
            $mun = round(((($trade['num'] - $trade['deal']) * $trade['price']) / 100) * (100 + $fee_buy), 8);
            $user_buy = $mo->table('codono_user_coin')->where(['userid' => $trade['userid']])->find();

            if ($mun <= round($user_buy[$rmb . 'd'], 8)) {
                $save_buy_rmb = $mun;
            } else if ($mun <= round($user_buy[$rmb . 'd'], 8) + 1) {
                $save_buy_rmb = $user_buy[$rmb . 'd'];
            } else {
                $mo->rollback();

                M('Stop')->where(['id' => $id])->setField('status', 2);
                $mo->commit();
                return ['0', 'Undo failed-3'];
            }

            $finance = $mo->table('codono_finance')->where(['userid' => $trade['userid']])->order('id desc')->find();
            $finance_num_user_coin = $mo->table('codono_user_coin')->where(['userid' => $trade['userid']])->find();
            $rs[] = $mo->table('codono_user_coin')->where(['userid' => $trade['userid']])->setInc($rmb, $save_buy_rmb);
            $rs[] = $mo->table('codono_user_coin')->where(['userid' => $trade['userid']])->setDec($rmb . 'd', $save_buy_rmb);
            $finance_nameid = $trade['id'];

            $finance_mum_user_coin = $mo->table('codono_user_coin')->where(['userid' => $trade['userid']])->find();
            $finance_hash = md5($trade['userid'] . $finance_num_user_coin[$rmb] . $finance_num_user_coin[$rmb . 'd'] . $save_buy_rmb . $finance_mum_user_coin[$rmb] . $finance_mum_user_coin[$rmb . 'd'] . CODONOLIC . 'auth.codono.com');
            $finance_num = $finance_num_user_coin[$rmb] + $finance_num_user_coin[$rmb . 'd'];

            if ($finance['mum'] < $finance_num) {
                $finance_status = (1 < ($finance_num - $finance['mum']) ? 0 : 1);
            } else {
                $finance_status = (1 < ($finance['mum'] - $finance_num) ? 0 : 1);
            }

            $rs[] = $mo->table('codono_finance')->add(['userid' => $trade['userid'], 'coinname' => $rmb, 'num_a' => $finance_num_user_coin[$rmb], 'num_b' => $finance_num_user_coin[$rmb . 'd'], 'num' => $finance_num_user_coin[$rmb] + $finance_num_user_coin[$rmb . 'd'], 'fee' => $save_buy_rmb, 'type' => 1, 'name' => 'trade', 'nameid' => $finance_nameid, 'remark' => 'Transaction Reversal ' . $trade['market'], 'mum_a' => $finance_mum_user_coin[$rmb], 'mum_b' => $finance_mum_user_coin[$rmb . 'd'], 'mum' => $finance_mum_user_coin[$rmb] + $finance_mum_user_coin[$rmb . 'd'], 'move' => $finance_hash, 'addtime' => time(), 'status' => $finance_status]);
            $rs[] = $mo->table('codono_stop')->where(['id' => $trade['id']])->setField('status', 2);
            $you_buy = $mo->table('codono_stop')->where([
                'market' => ['like', '%' . $rmb . '%'],
                'status' => 0,
                'userid' => $trade['userid']
            ])->find();

            if (!$you_buy) {
                $you_user_buy = $mo->table('codono_user_coin')->where(['userid' => $trade['userid']])->find();

                if (0 < $you_user_buy[$rmb . 'd']) {
                    $rs[] = $mo->table('codono_user_coin')->where(['userid' => $trade['userid']])->setField($rmb . 'd', 0);
                }
            }
        } else if ($trade['type'] == 2) {
            $mun = round($trade['num'] - $trade['deal'], 8);
            $user_sell = $mo->table('codono_user_coin')->where(['userid' => $trade['userid']])->find();

            if ($mun <= round($user_sell[$xnb . 'd'], 8)) {
                $save_sell_xnb = $mun;
            } else if ($mun <= round($user_sell[$xnb . 'd'], 8) + 1) {
                $save_sell_xnb = $user_sell[$xnb . 'd'];
            } else {
                $mo->rollback();
                M('Stop')->where(['id' => $trade['id']])->setField('status', 2);
                $mo->commit();
                return ['0', 'Undo failed2'];
            }

            if (0 < $save_sell_xnb) {
                $rs[] = $mo->table('codono_user_coin')->where(['userid' => $trade['userid']])->setInc($xnb, $save_sell_xnb);
                $rs[] = $mo->table('codono_user_coin')->where(['userid' => $trade['userid']])->setDec($xnb . 'd', $save_sell_xnb);
            }

            $rs[] = $mo->table('codono_stop')->where(['id' => $trade['id']])->setField('status', 2);
            $you_sell_where = [
                'market' => ['like', $xnb . '%'],
                'status' => 0,
                'userid' => $trade['userid']
            ];
            $you_sell = $mo->table('codono_stop')->where($you_sell_where)->find();

            if (!$you_sell) {
                $you_user_sell = $mo->table('codono_user_coin')->where(['userid' => $trade['userid']])->find();

                if (0 < $you_user_sell[$xnb . 'd']) {
                    $mo->table('codono_user_coin')->where(['userid' => $trade['userid']])->setField($xnb . 'd', 0);
                }
            }
        } else {
            $mo->rollback();
            return ['0', 'Undo failed3'];
        }

        if (check_arr($rs)) {
            S('getDepth', null);
            S('getActiveDepth' . $market, null);
            S('getActiveDepth', null);
            S('getDepthNew', null);

            $mo->commit();

            return ['1', 'Order has been canceled'];
        } else {
            $mo->rollback();

            return ['0', 'Undo failed4|' . implode('|', $rs)];
        }
    }


    public function adminstopreject($id = NULL)
    {
        if (!check($id, 'd')) {
            return ['0', 'Parameter error'];
        }

        $trade = M('Stop')->where(['id' => $id])->find();

        if (!$trade) {
            return ['0', 'Order does not exist'];
        }

        if ($trade['status'] != 0) {
            return ['0', 'Orders can not be undone'];
        }

        $xnb = explode('_', $trade['market'])[0];
        $rmb = explode('_', $trade['market'])[1];

        if (!$xnb) {
            return ['0', 'Sell market error'];
        }

        if (!$rmb) {
            return ['0', 'Buy market error'];
        }

        $fee_buy = C('market')[$trade['market']]['fee_buy'];
        $fee_sell = C('market')[$trade['market']]['fee_sell'];

        if ($fee_buy < 0) {
            return ['0', 'BUY fee error'];
        }

        if ($fee_sell < 0) {
            return ['0', 'Error handling sell'];
        }
        $market = $trade['market'];
        $user_coin = M('UserCoin')->where(['userid' => $trade['userid']])->find();
        $mo = M();

        $mo->startTrans();
        $rs = [];
        $user_coin = $mo->table('codono_user_coin')->where(['userid' => $trade['userid']])->find();

        if ($trade['type'] == 1) {
            $mun = round(((($trade['num'] - $trade['deal']) * $trade['price']) / 100) * (100 + $fee_buy), 8);
            $user_buy = $mo->table('codono_user_coin')->where(['userid' => $trade['userid']])->find();

            if ($mun <= round($user_buy[$rmb . 'd'], 8)) {
                $save_buy_rmb = $mun;
            } else if ($mun <= round($user_buy[$rmb . 'd'], 8) + 1) {
                $save_buy_rmb = $user_buy[$rmb . 'd'];
            } else {
                $mo->rollback();

                M('Stop')->where(['id' => $id])->setField('status', 2);
                $mo->commit();
                return ['0', 'Undo failed-4'];
            }

            $finance = $mo->table('codono_finance')->where(['userid' => $trade['userid']])->order('id desc')->find();
            $finance_num_user_coin = $mo->table('codono_user_coin')->where(['userid' => $trade['userid']])->find();
            $rs[] = $mo->table('codono_user_coin')->where(['userid' => $trade['userid']])->setInc($rmb, $save_buy_rmb);
            $rs[] = $mo->table('codono_user_coin')->where(['userid' => $trade['userid']])->setDec($rmb . 'd', $save_buy_rmb);
            $finance_nameid = $trade['id'];

            $finance_mum_user_coin = $mo->table('codono_user_coin')->where(['userid' => $trade['userid']])->find();
            $finance_hash = md5($trade['userid'] . $finance_num_user_coin[$rmb] . $finance_num_user_coin[$rmb . 'd'] . $save_buy_rmb . $finance_mum_user_coin[$rmb] . $finance_mum_user_coin[$rmb . 'd'] . CODONOLIC . 'auth.codono.com');
            $finance_num = $finance_num_user_coin[$rmb] + $finance_num_user_coin[$rmb . 'd'];

            if ($finance['mum'] < $finance_num) {
                $finance_status = (1 < ($finance_num - $finance['mum']) ? 0 : 1);
            } else {
                $finance_status = (1 < ($finance['mum'] - $finance_num) ? 0 : 1);
            }

            $rs[] = $mo->table('codono_finance')->add(['userid' => $trade['userid'], 'coinname' => $rmb, 'num_a' => $finance_num_user_coin[$rmb], 'num_b' => $finance_num_user_coin[$rmb . 'd'], 'num' => $finance_num_user_coin[$rmb] + $finance_num_user_coin[$rmb . 'd'], 'fee' => $save_buy_rmb, 'type' => 1, 'name' => 'trade', 'nameid' => $finance_nameid, 'remark' => 'Transaction Reversal ' . $trade['market'], 'mum_a' => $finance_mum_user_coin[$rmb], 'mum_b' => $finance_mum_user_coin[$rmb . 'd'], 'mum' => $finance_mum_user_coin[$rmb] + $finance_mum_user_coin[$rmb . 'd'], 'move' => $finance_hash, 'addtime' => time(), 'status' => $finance_status]);
            $rs[] = $mo->table('codono_stop')->where(['id' => $trade['id']])->setField('status', 2);
            $you_buy = $mo->table('codono_stop')->where([
                'market' => ['like', '%' . $rmb . '%'],
                'status' => 0,
                'userid' => $trade['userid']
            ])->find();

            if (!$you_buy) {
                $you_user_buy = $mo->table('codono_user_coin')->where(['userid' => $trade['userid']])->find();

                if (0 < $you_user_buy[$rmb . 'd']) {
                    $rs[] = $mo->table('codono_user_coin')->where(['userid' => $trade['userid']])->setField($rmb . 'd', 0);
                }
            }
        } else if ($trade['type'] == 2) {
            $mun = round($trade['num'] - $trade['deal'], 8);
            $user_sell = $mo->table('codono_user_coin')->where(['userid' => $trade['userid']])->find();

            if ($mun <= round($user_sell[$xnb . 'd'], 8)) {
                $save_sell_xnb = $mun;
            } else if ($mun <= round($user_sell[$xnb . 'd'], 8) + 1) {
                $save_sell_xnb = $user_sell[$xnb . 'd'];
            } else {
                $mo->rollback();
                M('Stop')->where(['id' => $trade['id']])->setField('status', 2);
                $mo->commit();
                return ['0', 'Undo failed2'];
            }

            if (0 < $save_sell_xnb) {
                $rs[] = $mo->table('codono_user_coin')->where(['userid' => $trade['userid']])->setInc($xnb, $save_sell_xnb);
                $rs[] = $mo->table('codono_user_coin')->where(['userid' => $trade['userid']])->setDec($xnb . 'd', $save_sell_xnb);
            }

            $rs[] = $mo->table('codono_stop')->where(['id' => $trade['id']])->setField('status', 2);
            $you_sell_where = [
                'market' => ['like', $xnb . '%'],
                'status' => 0,
                'userid' => $trade['userid']
            ];
            $you_sell = $mo->table('codono_stop')->where($you_sell_where)->find();

            if (!$you_sell) {
                $you_user_sell = $mo->table('codono_user_coin')->where(['userid' => $trade['userid']])->find();

                if (0 < $you_user_sell[$xnb . 'd']) {
                    $mo->table('codono_user_coin')->where(['userid' => $trade['userid']])->setField($xnb . 'd', 0);
                }
            }
        } else {
            $mo->rollback();
            return ['0', 'Undo failed3'];
        }

        if (check_arr($rs)) {
            S('getDepth', null);
            S('getActiveDepth' . $market, null);
            S('getActiveDepth', null);
            S('getDepthNew', null);

            $mo->commit();

            return ['1', 'Order has been canceled'];
        } else {
            $mo->rollback();

            return ['0', 'Undo failed4|' . implode('|', $rs)];
        }
    }
	function bcround($number, $precision = 7) {
    $factor = bcpow('10', (string) $precision);
    $tmpNumber = bcmul($number, $factor);
    $roundedNumber = bcadd($tmpNumber, '0.5', 0);
    return bcdiv($roundedNumber, $factor, $precision);
}
}

