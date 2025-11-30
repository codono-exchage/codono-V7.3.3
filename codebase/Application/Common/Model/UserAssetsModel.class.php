<?php

namespace Common\Model;

class UserAssetsModel extends \Think\Model
{
    /*
    userid:int
    coin:string coin symbol
    action:string 'inc'=+ dec=-
    type:balance 2=freeze
    account:int 1=p2p 2=spot
    */
    public function updateBalance($userid, $coin, $action, $amount, $type = 'balance', $account = 2)
    {
        $isValidCoin = isValidCoin($coin);
        if (!check($userid, 'd')) {
            $this->error(L('INCORRECT_REQ'));
        }
        if ($coin == null || !$isValidCoin) {
            return false;
            //print('Invalid coin');
        }
        if ($action == null || ($action != 'inc' && $action != 'dec')) {
            return false;
            //	print('Invalid action');
        }
        if ($type == null || ($type != 'balance' && $type != 'freeze')) {
            return false;
            //print('Invalid type');
        }
        if ($account == null && ($account != 1 || $account != 2)) {
            return false;
            //print('Invalid account'.$account);
        }
        if (!check($amount, 'decimal')) {
            return false;
            //    $this->error('Incorrect Amount:'.$amount);
        }
        $mo = M();
        $condition = array('account' => $account, 'uid' => $userid, 'coin' => $coin);
        //Check if Such user entry is there or not
        $result = $mo->table('codono_user_assets')->where($condition)->find();

        $mo->startTrans();
        //No user entry found lets add entry in user_assets table
        try {
            if ($result == 0 || $result == null) {
                $condition_add = $condition;
                $condition_add['created_at'] = time();
                $rs[] = $mo->table('codono_user_assets')->add($condition_add);
                $result = $mo->table('codono_user_assets')->where($condition)->find();
            }


            if ($result == 0 || $result == null) {
                $mo->rollback();
                return false;
                //print(L('There were some issues adding entry of user to user_assets table!'));
            }
            if ($result['coin'] == $coin && $result['uid'] == $userid) {
                //increase / decrease balance
                if ($action == 'inc') {
                    $rs[] = $act = $mo->table('codono_user_assets')->where($condition)->setInc($type, $amount);
                } else if ($action == 'dec') {
                    $rs[] = $act = $mo->table('codono_user_assets')->where($condition)->setDec($type, $amount);
                } else {
                    $rs[] = false;
                }
            }

            if (!check_arr($rs) || !$act) {
                $mo->rollback();
                //print(L('There were issues transferring!'));
                return false;
            }


            $mo->commit();
        } catch (Exception $e) {
            clog('userAssets', $e);
            $mo->rollback();
            return false;
        }
        return true;

    }

}