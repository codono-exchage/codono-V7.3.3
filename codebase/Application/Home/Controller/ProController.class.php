<?php

namespace Home\Controller;

use Think\Page;

class ProController extends HomeController
{
    public function index()
    {
        $data['status'] = 1;
        $data['info'] = "Connected to Pro";
        exit(json_encode($data));
    }

    public function getEntrustAndUsercoin($market = "btc_usd")
    {

        if (!userid()) {
            $data = array();
            exit(json_encode($data));
        }

        if (!C('market')[$market]) {
            $data = array();
            exit(json_encode($data));
        }
        $data = $data1 = $data2 = array();

        $where['userid'] = userid();
        $where['status'] = 1;
        $where['market'] = $market;
        $Model = M('Trade');
        $count = $Model->where($where)->count();
        $Page = new Page($count, 10);

        $show = $Page->show();

        $result = $Model->where($where)->order('id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();

        if ($result) {
            foreach ($result as $k => $v) {
                $data['tradelog'][$k]['addtime'] = date('m-d H:i:s', $v['addtime']);
                $data['tradelog'][$k]['type'] = $v['type'];
                $data['tradelog'][$k]['fee'] = format_num($v['fee'], 8);
                $data['tradelog'][$k]['price'] = format_num($v['price'], 8);
                $data['tradelog'][$k]['num'] = format_num($v['num'], 8);
                $data['tradelog'][$k]['id'] = $v['id'];
            }
        } else {
            $data['tradelog'] = null;
        }

        $result1 = M()->query('select id,price,num,deal,mum,type,fee,status,addtime from codono_trade where status=0 and market=\'' . $market . '\' and userid=' . userid() . ' order by id desc limit 10;');
        $result2 = M()->query('select id,price,stop,compare,num,deal,mum,type,fee,status,addtime from codono_stop where status=0 and market=\'' . $market . '\' and userid=' . userid() . ' order by id desc limit 10;');


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
                $data1['entrust'][$k]['tradetype'] = 'Limit';
            }
        } else {
            $data1['entrust'] = array();
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
            $data2['entrust'] = array();
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

        exit(json_encode($data));

    }

    public function coininfo($coinname)
    {
        $market = strtolower($coinname);

        $showPW = 1;
        check_server();
        if (!$market) {
            $market = C("market_mr");
        }
        $title = strtoupper($market);
        $data = array('round' => C('MARKET')[$market]['round'], 'tousdt' => 0, 'p_round' => C('MARKET')[$market]['round'], 'market' => $market, 'title' => $title, 'fee_buy' => C('MARKET')[$market]['fee_buy'], 'fee_sell' => C('MARKET')[$market]['fee_sell']);

        echo json_encode($data);
    }

    public function getJsonTop($market = NULL, $ajax = 'json')
    {

        $data = (APP_DEBUG ? null : S('getJsonTop' . $market));

        if (!$data) {
            if ($market) {
                $xnb = explode('_', $market)[0];
                $rmb = explode('_', $market)[1];

                foreach (C('market') as $k => $v) {
                    $main_coin = 1;
                    $v['xnb'] = explode('_', $v['name'])[0];
                    $v['rmb'] = explode('_', $v['name'])[1];
                    $data['list'][$k]['name'] = $v['name'];
                    $data['list'][$k]['img'] = $v['xnbimg'];
                    $data['list'][$k]['title'] = $v['title'];
                    $data['list'][$k]['new_price'] = $v['new_price'];
                    $data['list'][$k]['main_coin'] = $main_coin;
                    $data['list'][$k]['id'] = $v['id'];
                    $data['list'][$k]['round'] = $v['round'];
                    $data['list'][$k]['change'] = $v['change'];
                }

                $data['info']['img'] = C('market')[$market]['xnbimg'];
                $data['info']['title'] = C('market')[$market]['title'];
                $data['info']['new_price'] = C('market')[$market]['new_price'];

                if (C('market')[$market]['max_price']) {
                    $data['info']['max_price'] = format_num(C('market')[$market]['max_price'], C('market')[$market]['round']);
                } else {
                    $codono_tempprice = format_num((C('market')[$market]['market_ico_price'] / 100) * (100 + C('market')[$market]['zhang']), C('market')[$market]['round']);
                    $data['info']['max_price'] = $codono_tempprice;
                }

                if (C('market')[$market]['min_price']) {
                    $data['info']['min_price'] = format_num(C('market')[$market]['min_price'], C('market')[$market]['round']);
                } else {
                    $codono_tempprice = format_num((C('market')[$market]['market_ico_price'] / 100) * (100 - C('market')[$market]['die']), C('market')[$market]['round']);
                    $data['info']['min_price'] = $codono_tempprice;
                }


                $data['info']['buy_price'] = format_num(C('market')[$market]['buy_price'], C('market')[$market]['round']);
                $data['info']['sell_price'] = format_num(C('market')[$market]['sell_price'], C('market')[$market]['round']);
                $data['info']['volume'] = format_num(C('market')[$market]['volume'], C('market')[$market]['round']);
                $data['info']['change'] = C('market')[$market]['change'];
                S('getJsonTop' . $market, $data);
            }
        }

        exit(json_encode($data));

    }

    public function getuid()
    {
        if (!userid()) {
            $info = '{"userid":0,"token":0,"mobile":loginnow,"username":login,"nickName":login,"vip":{"usertype":0,"fee_discounts":"0%"},"usd":0,"usdd":0}';
            echo $info;
            exit;
        }

        $userinfo = array('id' => $this->userinfo['id'], 'token' => $this->userinfo['token'], 'cellphone' => $this->userinfo['cellphone'], 'username' => $this->userinfo['username'], 'usertype' => $this->userinfo['usertype']);//M('User')->where(array('id' => userid()))->field('id,token,cellphone,username,usertype')->find();
        $token = $userinfo['token'];
        if (!isset($token) || $token == null) {
            $token = md5(md5(rand(0, 10000) . md5(time()), md5(uniqid())));
            M('User')->where(array('id' => userid()))->setField('token', $token);
        }

        $arr["userid"] = $userinfo['id'];
        $arr["token"] = $token;
        $arr["mobile"] = $userinfo['email'];
        $arr["username"] = $userinfo['username'];
        $arr["nickName"] = $userinfo['username'];
        $arr['vip'] = array('usertype' => strval($userinfo['usertype']), 'fee_discounts' => discount(100, $userinfo['id']) . "%");
        $arr["usd"] = $this->usercoins['usd'];
        $arr["usdd"] = $this->usercoins['usdd'];

        $data = $arr;
        exit(json_encode($data));
    }

    public function assets($coinname = 'btc', $market_coin = 0)
    {
        if (!userid()) {
            $data[$coinname] = 0;
            $data[$market_coin] = 0;
            echo json_encode($data);
            exit;
        }


        $userCoin = $this->usercoins;// M('UserCoin')->where(array('userid' => userid()))->find();

        if ($userCoin) {
            $data[$coinname] = floatval($userCoin[$coinname]);
            $data[$market_coin] = floatval($userCoin[$market_coin]);

        } else {
            $data = null;
        }
        echo json_encode($data);
        exit;

    }

    public function navbar()
    {
        if (!S('navigation')) {
            $menus= M('Navigation')->where(array('status' => 1))->order('sort asc')->select();
			foreach($menus as $menu){
				if($menu['pid']==0){
					$navs[$menu['id']]=$menu;
				}
			}
			foreach($menus as $menu){
				if($menu['pid']>0){
					$navs[$menu['pid']]['submenu'][]=$menu;
				}
			}
			$this->navigation =$navs;
            S('navigation', $this->navigation);
        } else {
            $this->navigation = S('navigation');
        }
		
        echo json_encode($this->navigation, JSON_FORCE_OBJECT);
    }

    public function getPendingOrders($market = NULL, $trade_mode = 1)
    {
        $market = strtolower($market);
        if (!C('market')[$market]) {
            return null;
        }
        $round = C('market')[$market]['round'] ? C('market')[$market]['round'] : 8;

        $codono_getCoreConfig = codono_getCoreConfig();
        if (!$codono_getCoreConfig) {
            $this->error(L('Incorrect Core Config'));
        } else {
            $codono_ordinary = $codono_getCoreConfig['codono_userTradeNum'];
            $codono_special = $codono_getCoreConfig['codono_specialUserTradeNum'];
        }


        $data_getDepth = (APP_DEBUG ? null : S('getActiveDepth' . $market));
        //$data_getDepth=0;
        if (!$data_getDepth[$market][$trade_mode]) {
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
            if (C('market')[$market]['ext_orderbook'] != 0) {
                $is_liquid = 1;
            } else {
                $is_liquid = 0;
            }
            if ($is_liquid) {
                $lastupdateid = $mo->query('select MAX(flag) as flag from codono_trade where market =\'' . $market . '\' AND status=0 AND userid=0');
                $flag = $lastupdateid[0]['flag'];
            } else {
                $flag = $lastupdateid = 0;
            }

            if ($trade_mode == 1 && $flag != 0) {
                $buy_query = 'select id,price,sum(num-deal)as nums from codono_trade where status=0 and type=1 and market =\'' . $market . '\' and flag =\'' . $flag . '\' group by price order by price desc limit ' . $limt;

                $buy = $mo->query($buy_query);

                $sell = ($mo->query('select id,price,sum(num-deal)as nums from codono_trade where status=0 and type=2 and market =\'' . $market . '\' and flag =\'' . $flag . '\' group by price order by price asc limit ' . $limt . ';'));

            }

            if ($trade_mode == 1) {
                $buy_query = 'select id,price,sum(num-deal)as nums from codono_trade where status=0 and type=1 and market =\'' . $market . '\' group by price order by price desc limit ' . $limt;

                $buy = $mo->query($buy_query);

                $sell = ($mo->query('select id,price,sum(num-deal)as nums from codono_trade where status=0 and type=2 and market =\'' . $market . '\' group by price order by price asc limit ' . $limt . ';'));

            }

            if ($trade_mode == 3) {
                $buy = $mo->query('select id,price,sum(num-deal)as nums from codono_trade where status=0 and type=1 and market =\'' . $market . '\' group by price order by price desc limit ' . $limt . ';');
                $sell = null;
            }

            if ($trade_mode == 4) {
                $buy = null;
                $sell = ($mo->query('select id,price,sum(num-deal)as nums from codono_trade where status=0 and type=2 and market =\'' . $market . '\' group by price order by price asc limit ' . $limt . ';'));
            }
            $data['buyvol'] = 0;
            $data['sellvol'] = 0;
            if ($buy) {

                foreach ($buy as $k => $v) {
                    $amount = bcmul($v['price'], $v['nums'], $round);

                    $data['depth']['buy'][$k] = array('price' => (double)format_num($v['price'], $round), 'volume' => (double)format_num($v['nums'], $round), 'amount' => format_num($amount));
                    //$data['buyvol'] = $data['buyvol']+format_num($v['nums']);
                    $lastprice = $v['price'];
                }

            } else {
                $data['depth']['buy'] = '';
            }

            if ($sell) {
                foreach ($sell as $k => $v) {
                    $amount = bcmul($v['price'], $v['nums'], $round);

                    $data['depth']['sell'][$k] = array('price' => (double)format_num($v['price'], $round), 'volume' => (double)format_num($v['nums'], $round), 'amount' => format_num($amount));
                    //$data['sellvol'] = $data['sellvol']+format_num($v['nums']);
                }
            } else {
                $data['depth']['sell'] = '';
            }
            $data['buy']['length'] = count($data['depth']['buy']);
            $data['sell']['length'] = count($data['depth']['sell']);
            $data_getDepth[$market][$trade_mode] = $data;
            S('getActiveDepth' . $market, $data_getDepth);
        } else {
            $data = $data_getDepth[$market][$trade_mode];
        }
        $data['price'] = (double)$lastprice;
        exit(json_encode($data));
    }

    public function getTradelog($market = NULL, $ajax = 'json')
    {
        $market = I('get.market', '', 'text');
        if (is_array($market)) {
            $market = $market[0];
        }

        $data = (APP_DEBUG ? null : S('getTradelog' . $market));

        if (!$data) {

            $tradeLog = M('TradeLog')->where(array('status' => 1, 'market' => $market))->order('id desc')->limit(35)->select();

            if ($tradeLog) {
                foreach ($tradeLog as $k => $v) {
                    $data['tradelog'][$k]['addtime'] = date('m-d H:i:s', $v['addtime']);
                    $data['tradelog'][$k]['type'] = $v['type'];
                    $data['tradelog'][$k]['price'] = format_num($v['price']);
                    $data['tradelog'][$k]['num'] = format_num($v['num'], 8);
                    $data['tradelog'][$k]['mum'] = format_num($v['mum'], $v['round']);
                    $data['tradelog'][$k]['time'] = date('m-d H:i:s', $v['addtime']);
                }
                $data['tradelog'] = array_reverse($data['tradelog']);
                $data['length'] = count($data['tradelog']);
                S('getTradelog' . $market, $data);
            }
        }

        if ($ajax) {
            exit(json_encode($data));
        } else {
            return $data;
        }
    }

}