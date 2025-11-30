<?php

namespace Home\Controller;

class ElectrumController extends HomeController
{
    /*electrum for read only wallets*/


    public function depositcheck()
    {
        checkcronkey();
        cronLastRun(__FUNCTION__);
        $coinList = C('Coin');

        foreach ($coinList as $k => $v) {
            if ($v['type'] != 'qbb') {
                continue;
            }
            if ($v['status'] != 1) {
                continue;
            }
            if ($v['zr_jz'] != 1) {
                continue;
            }
            $coin = $v['name'];

            if (!$coin) {
                echo 'MM';
                continue;
            }
            $dj_username = C('coin')[$coin]['dj_yh'];
            $dj_password = C('coin')[$coin]['dj_mm'];
            $dj_address = C('coin')[$coin]['dj_zj'];
            $dj_port = C('coin')[$coin]['dj_dk'];
            echo 'start ' . $coin . "\n";
            $CoinClient = CoinClient($dj_username, $dj_password, $dj_address, $dj_port, 5, array(), 1);
            $json = $CoinClient->getinfo();

            if (!isset($json['version']) || !$json['version']) {
                echo '###ERR#####***** ' . $coin . ' connect fail***** ####ERR####>' . "\n";
                continue;
            }

            echo 'Cmplx ' . $coin . ' start,connect ' . (empty($CoinClient) ? 'fail' : 'ok') . ' :' . "\n";
            $listtransactions = $CoinClient->listtransactions();
            echo 'listtransactions:' . count($listtransactions) . "\n";
            $txs = $listtransactions['transactions'];

            foreach ($txs as $tx) {
                /*                echo "<pre>";
                                var_dump($tx);
                                echo "</pre>";
                        */
                if ($tx['bc_value'] > 0) {

                } else {
                    continue;
                }

                foreach ($tx['outputs'] as $out) {

                    if (($found = M('UserCoin')->where(array($coin . 'b' => $out['address']))->find())) {
                        $trans = array('coin' => $coin, 'address' => $out['address'], 'userid' => $found['userid'], 'amount' => $out['value'], 'txid' => $tx['txid'], 'confirmations' => $tx['confirmations']);
                    }
                }
                if (!$trans['userid']) {
                    continue;
                }
                $user = M('User')->where(array('id' => $trans['userid']))->find();

                if (M('Myzr')->where(array('txid' => $trans['txid'], 'status' => '1'))->find()) {
                    echo 'txid had found continue' . "\n";
                    continue;
                }

                echo 'all check ok ' . "\n";

                if ($trans['amount'] > 0) {
                    echo "<pre>";
                    print_r($trans);
                    echo "</pre>";
                    echo '<br/>start receive do:' . "\n";
                    $sfee = 0;
                    $true_amount = $trans['amount'];

                    if (C('coin')[$coin]['zr_zs']) {
                        $song = round(($trans['amount'] / 100) * C('coin')[$coin]['zr_zs'], 8);

                        if ($song) {
                            $sfee = $song;
                            $trans['amount'] = $trans['amount'] + $song;
                        }
                    }

                    if ($trans['confirmations'] < C('coin')[$coin]['zr_dz']) {
                        echo $trans['address'] . ' confirmations ' . $trans['confirmations'] . ' not enough ' . C('coin')[$coin]['zr_dz'] . ' continue ' . "\n";
                        echo 'confirmations <  c_zr_dz continue' . "\n";

                        if ($res = M('myzr')->where(array('txid' => $trans['txid']))->find()) {
                            M('myzr')->save(array('id' => $res['id'], 'addtime' => time(), 'status' => intval($trans['confirmations'] - C('coin')[$coin]['zr_dz'])));
                        } else {
                            M('myzr')->add(array('userid' => $user['id'], 'type' => 'qbb', 'username' => $trans['address'], 'coinname' => $coin, 'fee' => $sfee, 'txid' => $trans['txid'], 'num' => $true_amount, 'mum' => $trans['amount'], 'addtime' => time(), 'status' => intval($trans['confirmations'] - C('coin')[$coin]['zr_dz'])));
                        }

                        continue;
                    } else {
                        echo 'confirmations full' . "\n";
                    }

                    $mo = M();
                    
                    $mo->startTrans();
                    $rs = array();
                    $rs[] = $mo->table('codono_user_coin')->where(array('userid' => $user['id']))->setInc($coin, $trans['amount']);

                    if ($res = $mo->table('codono_myzr')->where(array('txid' => $trans['txid']))->find()) {
                        echo 'codono_myzr find and set status 1';
                        $rs[] = $mo->table('codono_myzr')->save(array('id' => $res['id'], 'addtime' => time(), 'status' => 1));
                    } else {
                        echo 'codono_myzr not find and add a new codono_myzr' . "\n";
                        $rs[] = $mo->table('codono_myzr')->add(array('userid' => $user['id'], 'username' => $trans['address'], 'coinname' => $coin, 'fee' => $sfee, 'txid' => $trans['txid'], 'num' => $true_amount, 'mum' => $trans['amount'], 'addtime' => time(), 'status' => 1));

                    }

                    if (strict_check_arr($rs)) {
                        $mo->commit();
                        echo $trans['amount'] . ' receive ok ' . $coin . ' ' . $trans['amount'];
                        
                        echo 'commit ok Notify Customer' . "\n";
                        deposit_notify($user['id'], $trans['address'], $coin, $trans['txid'], $true_amount, time());
                    } else {
                        echo $trans['amount'] . 'receive fail ' . $coin . ' ' . $trans['amount'];
                        echo var_export($rs, true);
                        $mo->rollback();
                        
                        print_r($rs);
                        echo 'rollback ok' . "\n";
                    }
                }
            }


        }
    }

}
