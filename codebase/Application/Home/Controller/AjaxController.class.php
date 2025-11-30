<?php

namespace Home\Controller;

use Think\Page;
use Think\Upload;

class AjaxController extends HomeController
{

    public function imgUser()
    {

        if (!userid()) {
            echo 'nologin';
            exit;
        }
        $accountType = $this->userinfo['accounttype'];//M('User')->where(array('id' => userid()))->getField("accounttype");

        //2 means institutional user
        if ($accountType == 2) {
            $photo_required = 7;
        } else {
            //means accounttype =1
            $photo_required = 4;
        }
        $userimg = M('User')->where(['id' => userid()])->getField('idcardimg1');
        if ($userimg) {
            $img_arr = explode('_', $userimg);
            if (count($img_arr) >= $photo_required) {
                M('User')->where(['id' => userid()])->save(['idcardimg1' => '']);
            }
        }

        $upload = new Upload();
        $upload->maxSize = 8192000;
        $upload->exts = ['jpg', 'gif', 'png', 'jpeg', 'pdf'];
        $upload->rootPath = './Upload/idcard/';
        $upload->autoSub = false;
//		$upload->saveName = md5(userid().'_id_'.time().ADMIN_KEY);  //tough name ;)
        $info = $upload->upload();

        if (!$info) {
            //$this->error('Error Uploading');
            echo 'error';
            exit();

        }

        foreach ($info as $k => $v) {

            $img_arr = [];
            if ($userimg) {
                $img_arr = explode('_', $userimg);
                if (count($img_arr) >= $photo_required) {
                    //echo "Please delete your existing KYC and resubmit";
                    echo 'error2';
                    exit();
                }

                $path = $userimg . '_' . $v['savename'];
            } else {
                $path = $v['savename'];
            }
            if (count($img_arr) >= 2) {
                M('User')->where(['id' => userid()])->save(['idcardimg1' => $path, 'idcardinfo' => '']);
            } else {
                M('User')->where(['id' => userid()])->save(['idcardimg1' => $path]);
            }

            echo $v['savename'];
            exit();
        }
    }


    public function getJsonMenu($ajax = 'json')
    {
        $data = (APP_DEBUG ? null : S('getJsonMenu'));

        if (!$data) {
            foreach (C('market') as $k => $v) {
                $v['xnb'] = explode('_', $v['name'])[0];
                $v['rmb'] = explode('_', $v['name'])[1];
                $data[$k]['name'] = $v['name'];
                $data[$k]['img'] = $v['xnbimg'];
                $data[$k]['title'] = $v['title'];
            }

            S('getJsonMenu', $data);
        }

        if ($ajax) {
            exit(json_encode($data));
        } else {
            return $data;
        }
    }

    public function top_coin_menu($ajax = 'json')
    {

        $data = (APP_DEBUG ? null : S('codono_getTopCoinMenu'));


        $codono_getCoreConfig = codono_getCoreConfig();
        if (!$codono_getCoreConfig) {
            $this->error('Incorrect Core Config');
        }


        return $this->top_coin_sub($data, $codono_getCoreConfig['codono_indexcat'], $ajax);
    }

    public function xtop_coin_menu($ajax = 'json')
    {

        $data = (APP_DEBUG ? null : S('codono_getTopCoinMenu'));


        $codono_getCoreConfig = codono_getCoreConfig();
        if (!$codono_getCoreConfig) {
            $this->error(L('Incorrect Core Config'));
        }

        return $this->top_coin_sub($data, $codono_getCoreConfig['codono_indexcat'], $ajax);
    }

    public function allfinance($ajax = 'json')
    {
        if (!userid()) {
            return false;
        }
        $UserCoin = $this->usercoins;//M('UserCoin')->where(array('userid' => userid()))->find();
        $usd['zj'] = 0;

        foreach (C('coin') as $k => $v) {
            if ($v['name'] == 'usd') {
                $usd['ky'] = $UserCoin[$v['name']] * 1;
                $usd['dj'] = $UserCoin[$v['name'] . 'd'] * 1;
                $usd['zj'] = bcsum([$usd['zj'], $usd['ky'], $usd['dj']]);
            } else {
                if (isset(C('market_type')[$v['name']]) && C('market')[C('market_type')[$v['name']]]['new_price']) {
                    $jia = C('market')[C('market_type')[$v['name']]]['new_price'];
                } else {
                    $jia = 1;
                }

                $usd['zj'] = format_num($usd['zj'] + (($UserCoin[$v['name']] + $UserCoin[$v['name'] . 'd']) * $jia), 2) * 1;
            }
        }

        $data = format_num($usd['zj'], 8);
        $data = NumToStr($data);

        if ($ajax == 'json') {
            exit(json_encode($data));
        } else {
            return $data;
        }
    }

    public function allsum($ajax = 'json')
    {
        $data = (APP_DEBUG ? null : S('allsum'));

        if (!$data) {
            $data = M('TradeLog')->sum('mum');
            S('allsum', $data);
        }

        $data = format_num($data);
        $data = str_repeat('0', 12 - strlen($data)) . $data;

        if ($ajax) {
            exit(json_encode($data));
        } else {
            return $data;
        }
    }

    protected function findBaseCoin($coin, $combination = 'usd_btc'): bool
    {
        $explo = explode('_', $combination);

        //$status = in_array($coin, $explo); //It now holds value as BTC
        if ($explo[1] == $coin)
            $status = true;
        else
            $status = false;

        return $status;
    }

    public function instrument($coin = 'btc', $ajax = 'json')
    {
        $data = (APP_DEBUG ? null : S('instrument'));
        //echo $coin;
        $senddata = [];
        // marketTransaction Record
        if (!$data) {
            $i = 0;
            foreach (C('market') as $k => $v) {
                
                //echo $this->findBaseCoin($v['name']);
                if ($this->findBaseCoin($coin, $v['name'])) {
                    $data[$i][0] = "<a href='/Trade/index/market/{$v['name']}'>{$v['name']}</a>";
                    $data[$i][1] = format_num($v['new_price'], $v['round']);
                    $data[$i][2] = format_num($v['volume'], 2) * 1;
                    $data[$i][3] = format_num($v['change'], 2) . '%';
                    $i++;
                }
            }
            $senddata['data'] = $data;
            S('instrument', $senddata);
        }

        if ($ajax) {
            exit(json_encode($senddata));
        } else {
            return $data;
        }
    }

    /** @noinspection DuplicatedCode */
    public function advanceinstrument($coin = 'btc', $ajax = 'json')
    {
        $data = [];
        //  $data = (APP_DEBUG ? null : S('advanceinstrument_'.$coin));
        if (!$data || !$data['data']) {
            $i = 0;
            $market = C('market');
            uasort($market, function ($a, $b) {
                return strcmp($b['sort'], $a['sort']);
            });
            foreach ($market as $k => $v) {

                if ($this->findBaseCoin($coin, $v['name'])) {

                    $data[$i][1] = $v['new_price'] > 1 ? NumToStr($v['new_price']) : NumToStr($v['new_price'] );
                    $data[$i][2] = format_num($v['volume'], 2) * 1;
                    $data[$i][3] = format_num($v['change'], 2) . '%';
                    $data[$i][4] = (int)$v['id'];
                    if (strpos($data[$i][3], '-') !== false) {
                        $data[$i][5] = 'crypt-down';
                    } else {
                        $data[$i][5] = 'crypt-up';
                    }
                    $data[$i][0] = "{$v['name']}";
                    $i++;
                }
            }
            $senddata['data'] = $data;
            S('advanceinstrument_' . $coin, $senddata);
            exit(json_encode($senddata));
        }
        exit(json_encode($data));

    }

    public function pairsBycat($cat = 'btc', $ajax = 'json')
    {
        $codono_getCoreConfig = codono_getCoreConfig();

        $cats = $codono_getCoreConfig['codono_indexcat'];
        $found_key = 100;
        foreach ($cats as $key => $value) {
            if (strtolower($value) == strtolower($cat)) {
                $found_key = $key;
            }
        }

        $data = [];
        //  $data = (APP_DEBUG ? null : S('advanceinstrument_'.$coin));
        if (!$data || !$data['data']) {
            $i = 0;
            $market = C('market');
            uasort($market, function ($a, $b) {
                return strcmp($b['sort'], $a['sort']);
            });
            foreach ($market as $k => $v) {

                if ($v['jiaoyiqu'] == $found_key) {

                    $data[$i][1] = $v['new_price'] > 1 ? $v['new_price'] : ($v['new_price'] + 0);
                    $data[$i][2] = format_num($v['volume'], 2) * 1;
                    $data[$i][3] = format_num($v['change'], 2) . '%';
                    $data[$i][4] = (int)$v['id'];
                    if (strpos($data[$i][3], '-') !== false) {
                        $data[$i][5] = 'crypt-up';
                    } else {
                        $data[$i][5] = 'crypt-down';
                    }
                    $data[$i][0] = "{$v['name']}";
                    $i++;
                }
            }
            $senddata['data'] = $data;
            //  S('advanceinstrument_' . $cat, $senddata);
            exit(json_encode($senddata));
        }
        exit(json_encode($data));

    }

    //New custom partition query 2017-06-05

    public function allcoin_a($id = 1, $ajax = 'json')
    {
        $codono_data = (APP_DEBUG ? null : S('codono_allcoin'));
        $codono_data = [];

        $codono_data['info'] = 'Data anomalies';
        $codono_data['status'] = 0;
        $codono_data['url'] = null;
        //$data=(APP_DEBUG ? null : S('codono_allcoin'));
        if (!isset($codono_data['url'])) {
            $codono_data['info'] = 'Normal data';
            $codono_data['status'] = 1;
            $codono_data['url'] = [];


            foreach (C('market') as $k => $v) {
                if ($v['jiaoyiqu'] == $id) {

                    $codono_data['url'][$k][0] = $v['title'];
                    $codono_data['url'][$k][1] = (float)$v['new_price'] > 1 ? $v['new_price'] : format_num($v['new_price']);
                    $codono_data['url'][$k][2] = (float)$v['buy_price'] > 1 ? $v['buy_price'] : format_num($v['buy_price']);
                    $codono_data['url'][$k][3] = (float)$v['sell_price'] > 1 ? $v['sell_price'] : format_num($v['sell_price']);
                    $codono_data['url'][$k][4] = (float)$v['new_price'] > 1 ? $v['new_price'] : format_num($v['new_price']);
                    $codono_data['url'][$k][5] = '';
                    $codono_data['url'][$k][6] = (float)format_num($v['volume'], 2);
                    $codono_data['url'][$k][7] = (float)format_num($v['change'], 2);
                    $codono_data['url'][$k][8] = $v['name'];
                    $codono_data['url'][$k][9] = $v['xnbimg'];
                    $codono_data['url'][$k][10] = 'blank';
                    $codono_data['url'][$k][11] = $v['navtitle'];
                    $codono_data['url'][$k][12] = str_replace(' ','',$v['pairname']);
                    $codono_data['url'][$k][13] = $v['xnb'] . $v['rmb'];
                }

            }

            S('codono_allcoin', $codono_data);
        }

        if ($ajax) {
            echo json_encode($codono_data);
            unset($codono_data);
            exit();
        } else {
            return $codono_data;
        }
    }


    public function index_b_trends($ajax = 'json')
    {
        return $this->trends_sub($ajax);
    }

    public function trends($ajax = 'json')
    {
        return $this->trends_sub($ajax);
    }

    public function getJsonTop($market = NULL, $ajax = 'json')
    {
        $market = I('get.market', '', 'text');
        $data = (APP_DEBUG ? null : S('getJsonTop' . $market));
        if ($data==null || $data==0) {
            $data=[];
            $marketInfo=C('market')[$market];
            if ($market && $marketInfo) {
                $xnb = explode('_', $market)[0];
                $rmb = explode('_', $market)[1] ;
                
                
                $data['info']['img'] = $marketInfo['xnbimg'];
                $data['info']['title'] = $marketInfo['title'];
                $data['info']['new_price'] = $marketInfo['new_price'] > 1 ? $marketInfo['new_price'] : format_num($marketInfo['new_price']);
                
                if ($marketInfo['max_price']) {
                    $data['info']['max_price'] = $marketInfo['max_price'] > 1 ? $marketInfo['max_price'] : format_num($marketInfo['max_price']);
                } else {
                    $codono_tempprice = format_num(($marketInfo['market_ico_price'] / 100) * (100 + $marketInfo['zhang']), $marketInfo['round']);
                    $data['info']['max_price'] = $codono_tempprice;
                }

                if ($marketInfo['min_price']) {
                    $data['info']['min_price'] = $marketInfo['min_price'] > 1 ? $marketInfo['min_price'] : format_num($marketInfo['min_price']);
                } else {
                    $codono_tempprice = bcmul(bcdiv($marketInfo['market_ico_price'], 100), bcsub(100, $marketInfo['die']));
                    $data['info']['min_price'] = $codono_tempprice > 1 ? $codono_tempprice : format_num($codono_tempprice);
                }


                $data['info']['buy_price'] = $marketInfo['buy_price'] > 1 ? $marketInfo['buy_price'] : format_num($marketInfo['buy_price']);
                $data['info']['sell_price'] = $marketInfo['sell_price'] > 1 ? $marketInfo['sell_price'] : format_num($marketInfo['sell_price']);
                $data['info']['volume'] = $marketInfo['volume'] > 1 ? $marketInfo['volume'] : format_num($marketInfo['volume']);
                $data['info']['change'] = format_num($marketInfo['change'], 2);
                S('getJsonTop' . $market, $data);
            }
        }

        if ($ajax) {
            exit(json_encode($data));
        } else {
            return $data;
        }
    }

    public function getTradelog($market = NULL, $ajax = 'json')
    {
        $market = I('get.market', '', 'text');
        if (is_array($market)) {
            $market = $market[0];
        }

        $data = (APP_DEBUG ? null : S('getTradelog' . $market));

        if (!$data) {

            $tradeLog = M('TradeLog')->where(['status' => 1, 'market' => $market])->order('id desc')->limit(25)->select();

            if ($tradeLog) {
                foreach ($tradeLog as $k => $v) {
                    $data['tradelog'][$k]['addtime'] = date('m-d H:i:s', $v['addtime']);
                    $data['tradelog'][$k]['type'] = $v['type'];
                    $data['tradelog'][$k]['price'] = NumToStr($v['price']);
                    $data['tradelog'][$k]['num'] = NumToStr($v['num']);
                    $data['tradelog'][$k]['mum'] = NumToStr($v['mum']);
                    $data['tradelog'][$k]['time'] = date('H:i:s', $v['addtime']);
                }

                S('getTradelog' . $market, $data);
            }
        }

        if ($ajax) {
            exit(json_encode($data));
        } else {
            return $data;
        }
    }


    public function getAwardInfo($ajax = 'json')
    {
        $data = (APP_DEBUG ? null : S('getAwardInfo'));
        if (!$data) {
            $awardInfo = M('UserAward')->order('id desc')->limit(50)->select();

            if ($awardInfo) {
                foreach ($awardInfo as $k => $v) {
                    $data['awardInfo'][$k]['addtime'] = date('m-d H:i:s', $v['addtime']);
                    $name_tmp = M('User')->where(['id' => $v['userid']])->getField('username');
                    $data['awardInfo'][$k]['username'] = substr_replace($name_tmp, '****', 2, strlen($name_tmp) - 4);
                    $data['awardInfo'][$k]['awardname'] = $v['awardname'];
                }

                S('getAwardInfo', $data, 300);
            }
        }

        if ($ajax) {
            exit(json_encode($data));
        } else {
            return $data;
        }
    }


    public function getActiveOrders($market = NULL, $trade_mode = 1, $ajax = 'json')
    {
        $market = strtolower($market);
        if (!C('market')[$market]) {
            return null;
        }
        $limt = 20;
        $round = C('market')[$market]['round'] ? C('market')[$market]['round'] : 8;

        $codono_getCoreConfig = codono_getCoreConfig();
        if (!$codono_getCoreConfig) {
            $this->error(L('Incorrect Core Config'));
        } else {
            $codono_ordinary = $codono_getCoreConfig['codono_userTradeNum'];
            $codono_special = $codono_getCoreConfig['codono_specialUserTradeNum'];
        }


        $data_getDepth = (APP_DEBUG ? null : S('getActiveDepth' . $market));

        if (!isset($data_getDepth[$market][$trade_mode])) {
            if ($trade_mode == 1) {
                $limt = 15;
            }

            if (($trade_mode == 3) || ($trade_mode == 4)) {

                if (userid()) {
                    $usertype = $this->userinfo['usertype'];//M('User')->where(array('id' => userid()))->getField('usertype');
                    if ($usertype == 1) {
                        $limt = $codono_special;
                    } else {
                        $limt = $codono_ordinary;
                    }
                } else {
                    $limt = $codono_ordinary;
                }
            }

            $trade_mode = intval($trade_mode);
            $mo = M();
            $flag = 0;

            if ($trade_mode == 1 && $flag != 0) {
                $buy_query = 'select price,sum(num-deal)as nums from codono_trade where status=0 and type=1 and market =\'' . $market . '\' and flag =\'' . $flag . '\' group by price order by price desc limit ' . $limt;
                $buy = $mo->query($buy_query);
                $sell = ($mo->query('select price,sum(num-deal)as nums from codono_trade where status=0 and type=2 and market =\'' . $market . '\' and flag =\'' . $flag . '\' group by price order by price asc limit ' . $limt . ';'));
            }

            if ($trade_mode == 1 && $flag == 0) {
                $buy_query = 'select price,sum(num-deal)as nums from codono_trade where status=0 and type=1 and market =\'' . $market . '\' group by price order by price desc limit ' . $limt;

                $buy = $mo->query($buy_query);

                $sell = ($mo->query('select price,sum(num-deal)as nums from codono_trade where status=0 and type=2 and market =\'' . $market . '\' group by price order by price asc limit ' . $limt . ';'));

            }

            if ($trade_mode == 3) {
                $buy = $mo->query('select price,sum(num-deal)as nums from codono_trade where status=0 and type=1 and market =\'' . $market . '\' group by price order by price desc limit ' . $limt . ';');
                $sell = null;
            }

            if ($trade_mode == 4) {
                $buy = null;
                $sell = array_reverse($mo->query('select price,sum(num-deal)as nums from codono_trade where status=0 and type=2 and market =\'' . $market . '\' group by price order by price asc limit ' . $limt . ';'));
            }
            $data['buyvol'] = 0;
            $data['sellvol'] = 0;
            if (isset($buy)) {
                $data['buyvol'] = 0;
                foreach ($buy as $k => $v) {
                    $data['buyvol'] = bcadd($data['buyvol'], $v['nums'], 8);

                    $data['depth']['buy'][$k] = [$v['price'] > 1 ? $v['price'] : format_num($v['price'], $round), (float)($v['nums'] + 0), $data['buyvol']];
                }

            } else {
                $data['depth']['buy'] = '';
            }

            if (isset($sell)) {
                $data['sellvol'] = 0;
                foreach ($sell as $k => $v) {
                    $data['sellvol'] = bcadd($data['sellvol'], $v['nums'], 8);
                    $data['depth']['sell'][$k] = [$v['price'] > 1 ? $v['price'] : format_num($v['price'], $round), (float)($v['nums'] + 0), $data['sellvol']];

                }
            } else {
                $data['depth']['sell'] = '';
            }

            $data_getDepth[$market][$trade_mode] = $data;
            S('getActiveDepth' . $market, $data_getDepth);
        } else {

            $data = $data_getDepth[$market][$trade_mode];
        }

        if ($ajax) {
            exit(json_encode($data));
        } else {
            return $data;
        }
    }


    public function getDepth($market = NULL, $trade_mode = 1, $ajax = 'json')
    {
        if (!C('market')[$market]) {
            return null;
        }

        $limt = 20;
        $codono_getCoreConfig = codono_getCoreConfig();
        if (!$codono_getCoreConfig) {
            $this->error(L('Incorrect Core Config'));
        } else {
            $codono_ordinary = $codono_getCoreConfig['codono_userTradeNum'];
            $codono_special = $codono_getCoreConfig['codono_specialUserTradeNum'];
        }


        $data_getDepth = (APP_DEBUG ? null : S('getDepth'));

        if (!$data_getDepth[$market][$trade_mode]) {
            if ($trade_mode == 1) {
                $limt = 15;
            }

            if (($trade_mode == 3) || ($trade_mode == 4)) {
                if (userid()) {
                    $usertype = $this->userinfo['usertype'];//M('User')->where(array('id'=> userid()))->getField('usertype');
                    if ($usertype == 1) {
                        $limt = $codono_special;
                    } else {
                        $limt = $codono_ordinary;
                    }
                } else {
                    $limt = $codono_ordinary;
                }
            }

            $trade_mode = intval($trade_mode);


            $mo = M();
            if ($trade_mode == 1) {
                $buy = $mo->query('select id,price,sum(num-deal)as nums from codono_trade where status=0 and type=1 and market =\'' . $market . '\' group by price order by price desc limit ' . $limt . ';');
                $sell = array_reverse($mo->query('select id,price,sum(num-deal)as nums from codono_trade where status=0 and type=2 and market =\'' . $market . '\' group by price order by price asc limit ' . $limt . ';'));
            }

            if ($trade_mode == 3) {
                $buy = $mo->query('select id,price,sum(num-deal)as nums from codono_trade where status=0 and type=1 and market =\'' . $market . '\' group by price order by price desc limit ' . $limt . ';');
                $sell = null;
            }

            if ($trade_mode == 4) {
                $buy = null;
                $sell = array_reverse($mo->query('select id,price,sum(num-deal)as nums from codono_trade where status=0 and type=2 and market =\'' . $market . '\' group by price order by price asc limit ' . $limt . ';'));
            }

            if ($buy) {
                foreach ($buy as $k => $v) {
                    $data['depth']['buy'][$k] = [floatval($v['price'] * 1), floatval($v['nums'] * 1)];

                }
            } else {
                $data['depth']['buy'] = '';
            }

            if ($sell) {
                foreach ($sell as $k => $v) {

                    $data['depth']['sell'][$k] = [floatval($v['price'] * 1), floatval($v['nums'] * 1)];
                }
            } else {
                $data['depth']['sell'] = '';
            }

            $data_getDepth[$market][$trade_mode] = $data;
            S('getDepth', $data_getDepth);
        } else {
            $data = $data_getDepth[$market][$trade_mode];
        }

        if ($ajax) {
            exit(json_encode($data));
        } else {
            return $data;
        }
    }

    public function getDepthNew($market = NULL, $trade_mode = 1, $ajax = 'json')
    {
        if (!C('market')[$market]) {
            return null;
        }


        $codono_getCoreConfig = codono_getCoreConfig();
        if (!$codono_getCoreConfig) {
            $this->error(L('Incorrect Core Config'));
        } else {
            $codono_ordinary = $codono_getCoreConfig['codono_userTradeNum'];
            $codono_special = $codono_getCoreConfig['codono_specialUserTradeNum'];
        }
        $limt = 20;

        $data_getDepth = (APP_DEBUG ? null : S('getDepthNew'));

        if (!$data_getDepth[$market][$trade_mode]) {
            if ($trade_mode == 1) {
                $limt = 15;
            }

            if (($trade_mode == 3) || ($trade_mode == 4)) {
                //20170608 increase press user level transfer information 
                if (userid()) {
                    $usertype = $this->userinfo['usertype'];//M('User')->where(array('id' => userid()))->getField('usertype');
                    if ($usertype == 1) {
                        $limt = $codono_special;
                    } else {
                        $limt = $codono_ordinary;
                    }
                } else {
                    $limt = $codono_ordinary;
                }
            }

            $trade_mode = intval($trade_mode);


            $mo = M();
            if ($trade_mode == 1) {
                $buy = $mo->query('select price,sum(num-deal)as nums from codono_trade where status=0 and type=1 and market =\'' . $market . '\' group by price order by price desc limit ' . $limt . ';');
                $sell = array_reverse($mo->query('select price,sum(num-deal)as nums from codono_trade where status=0 and type=2 and market =\'' . $market . '\' group by price order by price asc limit ' . $limt . ';'));
            }

            if ($trade_mode == 3) {
                $buy = $mo->query('select price,sum(num-deal)as nums from codono_trade where status=0 and type=1 and market =\'' . $market . '\' group by price order by price desc limit ' . $limt . ';');
                $sell = null;
            }

            if ($trade_mode == 4) {
                $buy = null;
                $sell = array_reverse($mo->query('select price,sum(num-deal)as nums from codono_trade where status=0 and type=2 and market =\'' . $market . '\' group by price order by price asc limit ' . $limt . ';'));
            }

            if ($buy) {
                foreach ($buy as $k => $v) {
                    $data['depth']['bids'][$k] = [floatval($v['price'] * 1), floatval($v['nums'] * 1)];

                }
            } else {
                $data['depth']['bids'] = '';
            }

            if ($sell) {
                foreach ($sell as $k => $v) {

                    $data['depth']['asks'][$k] = [floatval($v['price'] * 1), floatval($v['nums'] * 1)];
                }
            } else {
                $data['depth']['asks'] = '';
            }

            $data_getDepth[$market][$trade_mode] = $data;
            S('getDepthNew', $data_getDepth);
        } else {
            $data = $data_getDepth[$market][$trade_mode];
        }
        if ($data['depth']['bids'] == null) {
            $data['depth']['bids'] = [0, 0];
        }
        if ($data['depth']['asks'] == null) {
            $data['depth']['asks'] = [0, 0];
        }

        if ($ajax) {
            exit(json_encode($data['depth']));
        } else {
            return $data['depth'];
        }
    }

    public function getEntrustAndUsercoin($market = NULL, $ajax = 'json')
    {
        if (!userid()) {
            return null;
        }

        if (!C('market')[$market]) {
            return null;
        }
        echo C('market')[$market]['auto_match'];exit;
        
        $result1 = M()->query('select id,price,num,deal,mum,type,fee,status,addtime from codono_trade where status=0 and market=\'' . $market . '\' and userid=' . userid() . ' order by id desc limit 10;');
        $result2 = M()->query('select id,price,stop,compare,num,deal,mum,type,fee,status,addtime from codono_stop where status=0 and market=\'' . $market . '\' and userid=' . userid() . ' order by id desc limit 10;');
        $data = $data1 = $data2 = [];

        if ($result1) {
            foreach ($result1 as $k => $v) {
                $data1['entrust'][$k]['addtime'] = date('m-d H:i:s', $v['addtime']);
                $data1['entrust'][$k]['condition'] = '-';
                $data1['entrust'][$k]['stop'] = '-';
                $data1['entrust'][$k]['type'] = $v['type'];
                $data1['entrust'][$k]['price'] = format_num($v['price'] * 1, 8);
                $data1['entrust'][$k]['num'] = format_num($v['num'], 8);
                $data1['entrust'][$k]['deal'] = format_num($v['deal'], 8);
                $data1['entrust'][$k]['id'] = $v['id'];
                $data1['entrust'][$k]['tradetype'] ='Limit';
            }
        } else {
            $data1['entrust'] = [];
        }
        if ($result2) {
            foreach ($result2 as $k => $v) {
                if ($v['compare'] == 'gt') {
                    $condition = '<' . $v['stop'];
                } else {
                    $condition = '>' . $v['stop'];
                }
                $data2['entrust'][$k]['addtime'] = date('m-d H:i:s', $v['addtime']);
                $data2['entrust'][$k]['condition'] = $condition;
                $data2['entrust'][$k]['stop'] = $v['stop'];
                $data2['entrust'][$k]['type'] = $v['type'];
                $data2['entrust'][$k]['price'] = format_num($v['price'] * 1, 8);
                $data2['entrust'][$k]['num'] = format_num($v['num'], 8);
                $data2['entrust'][$k]['deal'] = format_num($v['deal'], 8);
                $data2['entrust'][$k]['id'] = $v['id'];
                $data2['entrust'][$k]['tradetype'] = 'Stop-Limit';
            }
        } else {
            $data2['entrust'] = [];
        }
        $data['entrust'] = array_merge($data1['entrust'], $data2['entrust']);
        $userCoin = $this->usercoins;//M('UserCoin')->where(array('userid' => userid()))->find();

        if ($userCoin) {
            $xnb = explode('_', $market)[0];
            $rmb = explode('_', $market)[1];
            $data['usercoin']['xnb'] = format_num($userCoin[$xnb]);
            $data['usercoin']['xnbd'] = format_num($userCoin[$xnb . 'd']);
            $data['usercoin']['usd'] = format_num($userCoin[$rmb]);
            $data['usercoin']['usdd'] = format_num($userCoin[$rmb . 'd']);
        } else {
            $data['usercoin'] = null;
        }

        if ($ajax) {
            exit(json_encode($data));
        } else {
            return $data;
        }
    }
    public function getMarginUsercoin($market = NULL, $ajax = 'json')
	{
		if (!userid()) {
			return null;
		}

		if (!C('market')[$market]) {
			return null;
		}

		$result = M()->query('select id,price,num,deal,mum,type,fee,status,addtime from codono_trade_lever where status=0 and market=\'' . $market . '\' and userid=' . userid() . ' order by id desc limit 10;');
        //dump($result);die;
		if ($result) {
			foreach ($result as $k => $v) {
				$data['entrust'][$k]['addtime'] = date('m-d H:i:s', $v['addtime']);
				$data['entrust'][$k]['type'] = $v['type'];
				$data['entrust'][$k]['price'] = $v['price'] * 1;
				$data['entrust'][$k]['num'] = round($v['num'], 6);
				$data['entrust'][$k]['deal'] = round($v['deal'], 6);
				$data['entrust'][$k]['id'] = round($v['id']);
			}
		}
		else {
			$data['entrust'] = null;
		}

		$xnb = explode('_', $market)[0];
		$rmb = explode('_', $market)[1];
		$userCoin = M('LeverCoin')->where(['userid' => userid(),'name_en'=>$xnb])->find();

		if ($userCoin) {
			
			$data['usercoin']['xnb'] = floatval($userCoin['yue']);
			$data['usercoin']['xnbd'] = floatval($userCoin['yued']);
			$data['usercoin']['cny'] = floatval($userCoin['p_yue']);
			$data['usercoin']['cnyd'] = floatval($userCoin['p_yued']);
		}
		else {
			$data['usercoin'] = null;
		}

		if ($ajax) {
			exit(json_encode($data));
		}
		else {
			return $data;
		}
	}

    public function getFullEntrustAndUsercoin($market = NULL, $ajax = 'json')
    {
        $uid=userid();
        if (!$uid) {
            return null;
        }
        
        $tradeType=C('market')[$market]['auto_match']?'Limit':'Market';
        $result1 = M()->query('select id,price,num,deal,mum,type,fee,status,addtime,market from codono_trade where status=0 and  userid= '.$uid.' and market="'.$market.'" order by id desc limit 20;');
        
        $result2 = M()->query('select id,price,stop,compare,num,deal,mum,type,fee,status,addtime,market from codono_stop where status=0 and  userid='.$uid.'  and market="'.$market.'"  order by id desc limit 10;');
        $data = $data1 = $data2 = [];

        if ($result1) {
            foreach ($result1 as $k => $v) {
                $data1['entrust'][$k]['market'] = $v['market'];
                $data1['entrust'][$k]['addtime'] = date('m-d H:i', $v['addtime']);
                $data1['entrust'][$k]['condition'] = '-';
                $data1['entrust'][$k]['stop'] = '-';
                $data1['entrust'][$k]['type'] = $v['type'];
                if($tradeType=='Market'){
                    $data1['entrust'][$k]['price'] = 0;
                }else{
                    $data1['entrust'][$k]['price'] = NumToStr($v['price']);
                }
                
                $data1['entrust'][$k]['num'] = NumToStr($v['num']);
                $data1['entrust'][$k]['deal'] = NumToStr($v['deal']);
                $data1['entrust'][$k]['id'] = $v['id'];
                $data1['entrust'][$k]['tradetype'] = $tradeType;
            }
        } else {
            $data1['entrust'] = [];
        }
        if ($result2) {
            foreach ($result2 as $k => $v) {
                if ($v['compare'] == 'gt') {
                    $condition = '<' . NumToStr($v['stop']);
                } else {
                    $condition = '>' . NumToStr($v['stop']);
                }
                $data2['entrust'][$k]['market'] = $v['market'];
                $data2['entrust'][$k]['addtime'] = date('m-d H:i:s', $v['addtime']);
                $data2['entrust'][$k]['condition'] = $condition;
                $data2['entrust'][$k]['stop'] = NumToStr($v['stop']);
                $data2['entrust'][$k]['type'] = $v['type'];
                $data2['entrust'][$k]['price'] = NumToStr($v['price']);
                $data2['entrust'][$k]['num'] = NumToStr($v['num']);
                $data2['entrust'][$k]['deal'] = NumToStr($v['deal']);
                $data2['entrust'][$k]['id'] = $v['id'];
                $data2['entrust'][$k]['tradetype'] = 'Stop-Limit';
            }
        } else {
            $data2['entrust'] = [];
        }
        $data['entrust'] = array_merge($data1['entrust'], $data2['entrust']);
        $userCoin = $this->usercoins;//M('UserCoin')->where(array('userid' => userid()))->find();

        if ($userCoin) {
            $xnb = explode('_', $market)[0];
            $rmb = explode('_', $market)[1];
            $data['usercoin']['xnb'] = floatval($userCoin[$xnb]);
            $data['usercoin']['xnbd'] = floatval($userCoin[$xnb . 'd']);
            $data['usercoin']['usd'] = floatval($userCoin[$rmb]);
            $data['usercoin']['usdd'] = floatval($userCoin[$rmb . 'd']);
        } else {
            $data['usercoin'] = null;
        }

        if ($ajax) {
            exit(json_encode($data));
        } else {
            return $data;
        }
    }

    public function getClosedOrders($market = NULL, $ajax = 'json')
    {
        if (!userid()) {
            return null;
        }

        if (!C('market')[$market]) {
            return null;
        }
        $where['userid|peerid'] = userid();
        $where['status'] = 1;
        $where['market'] = $market;
        $Model = M('TradeLog');
        $count = $Model->where($where)->count();
        //$query='select id,price,num,mum,type,status,addtime from codono_trade_log where status=1 and market=\'' . $market . '\' and userid=' . userid() . '  order by id desc limit 15';
        $Page = new Page($count, 15);
        //$Page->parameter .= 'type=' . $type . '&status=' . $status . '&market=' . $market . '&';
        $show = $Page->show();

        $result = $Model->where($where)->order('id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();

        //    $result = M()->query($query);

        if ($result) {
            foreach ($result as $k => $v) {
                $data['entrust'][$k]['addtime'] = date('m-d H:i:s', $v['addtime']);
                $data['entrust'][$k]['type'] = $v['type'];
                $data['entrust'][$k]['price'] = format_num($v['price'] * 1, 8);
                $data['entrust'][$k]['num'] = format_num($v['num'], 8);
                $data['entrust'][$k]['id'] = $v['id'];
            }
        } else {
            $data['entrust'] = null;
        }

        //$userCoin = $this->usercoins;


        if ($ajax) {
            exit(json_encode($data));
        } else {
            return $data;
        }
    }

    public function getChat()
    {
        $market = I('get.market');
        $ajax = 'json';
        if (!check($market, 'market')) {
            $market = C('market_mr');
        }
        $chat = (APP_DEBUG ? null : S('getChat_' . $market));

        if (!$chat) {
            $chat = M('Chat')->where(['status' => 1, 'market' => $market])->order('id desc')->limit(CHAT_LIMIT_LINES)->select();
            S('getChat_' . $market, $chat);
        }

        asort($chat);

        if ($chat) {
            foreach ($chat as $k => $v) {
                $data[] = [(int)$v['id'], $v['username'], $v['content']];
            }
        } else {
            $data = '';
        }

        if ($ajax) {
            exit(json_encode($data));
        } else {
            return $data;
        }
    }

    public function upChat()
    {
        $content = I('post.content', null, 'text');
        $market = I('post.market', null, 'text');
        if (!userid()) {
            $this->error(L('PLEASE_LOGIN'));
        }
        if (!check($market, 'market')) {
            $market = C('market_mr');
        }
        $content = msubstr($content, 0, 80, 'utf-8', false);

        if (!$content) {
            $this->error(L('Please enter content'));
        }

        if (APP_DEMO) {
            $this->error(L('Site is in Demo mode, You can not chat!'));
        }

        if (time() < (session('chat' . userid()) + 10)) {
            $this->error(L('You can not send messages too quickly!'));
        }

        $id = M('Chat')->add(['userid' => userid(), 'username' => username(), 'market' => $market, 'content' => $content, 'addtime' => time() + 18000, 'endtime' => time(), 'sort' => '0', 'status' => 1]);

        if ($id) {
            S('getChat_' . $market, null);
            session('chat' . userid(), time());
            $this->success($id);
        } else {
            $this->error(L('Failed to send'));
        }
    }


    public function upcomment($msgaaa, $s1, $s2, $s3, $xnb)
    {
        exit('Feature has been disabled');
        if (empty($msgaaa)) {
            $this->error(L('Submission error'));
        }

        if (!check($s1, 'd')) {
            $this->error(L('Technical score error'));
        }

        if (!check($s2, 'd')) {
            $this->error(L('App Rating Error'));
        }

        if (!check($s3, 'd')) {
            $this->error(L('Ratings outlook error'));
        }

        if (!userid()) {
            $this->error(L('PLEASE_LOGIN'));
        }

        if (M('CoinComment')->where([
            'userid' => userid(),
            'coinname' => $xnb,
            'addtime' => ['gt', time() - 60]
        ])->find()) {
            $this->error(L('Please do not submit often!'));
        }

        if (M('Coin')->where(['name' => $xnb])->save([
            'tp_zs' => ['exp', 'tp_zs+1'],
            'tp_js' => ['exp', 'tp_js+' . $s1],
            'tp_yy' => ['exp', 'tp_yy+' . $s2],
            'tp_qj' => ['exp', 'tp_qj+' . $s3]
        ])) {
            if (M('CoinComment')->add(['userid' => userid(), 'coinname' => $xnb, 'content' => $msgaaa, 'addtime' => time(), 'status' => 1])) {
                $this->success(L('Submitted successfully'));
            } else {
                $this->error(L('Submission Failed!1'));
            }
        } else {
            $this->error(L('Submission Failed!2'));
        }
    }

    public function subcomment($id, $type)
    {
        if ($type != 1) {
            if ($type != 2) {
                if ($type != 3) {
                    $this->error(L('INCORRECT_REQ'));
                } else {
                    $type = 'xcd';
                }
            } else {
                $type = 'tzy';
            }
        } else {
            $type = 'cjz';
        }

        if (!check($id, 'd')) {
            $this->error('Parameter Error 1');
        }

        if (!userid()) {
            $this->error(L('PLEASE_LOGIN'));
        }

        if (S('subcomment' . userid() . $id)) {
            $this->error(L('Please do not submit often!'));
        }

        if (M('CoinComment')->where(['id' => $id])->setInc($type, 1)) {
            S('subcomment' . userid() . $id, 1);
            $this->success(L('Submitted successfully'));
        } else {
            $this->error(L('Submission Failed 3!'));
        }
    }

    /**
     * @param $data
     * @param $codono_indexcat
     * @param $ajax
     * @return array|void
     */
    public function top_coin_sub($data, $codono_indexcat, $ajax)
    {
        if (!$data) {
            $data = [];

            foreach ($codono_indexcat as $k => $v) {
                $data[$k]['title'] = $v;
            }
            foreach (C('market') as $k => $v) {

                $v['xnb'] = explode('_', $v['name'])[0];
                $v['rmb'] = explode('_', $v['name'])[1];
                $data_tmp['img'] = $v['xnbimg'];
                $data_tmp['title'] = $v['navtitle'];

                $data[$v['jiaoyiqu']]['data'][$k] = $data_tmp;
                unset($data_tmp);
            }
            S('codono_getTopCoinMenu', $data);
        }

        if ($ajax) {
            exit(json_encode($data));
        } else {
            return $data;
        }
    }

    /**
     * @param $ajax
     * @return array|mixed|object|void
     */
    public function trends_sub($ajax)
    {
        $data = (APP_DEBUG ? null : S('trends'));

        if (!$data) {
            foreach (C('market') as $k => $v) {
                $tendency = json_decode($v['tendency'], true);
                $data[$k]['data'] = $tendency;
                $data[$k]['yprice'] = $v['new_price'];
            }

            S('trends', $data);
        }

        if ($ajax) {
            exit(json_encode($data));
        } else {
            return $data;
        }
    }
}