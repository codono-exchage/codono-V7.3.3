<?php

namespace Api\Controller;

class IndexController extends CommonController
{
    public function index()
    {
        $array = 'connected to Index API';
        $this->success($array);
    }

    public function latestnews()
    {
        //codono_text name=index_info  get content
        $News = M('Text')->where(array('name' => 'game_news', 'status' => 1))->field(array('name', 'title', 'content'))->find();

        $this->ajaxShow($News);


    }

    public function up()
    {
        //Not required to run Can only be run once !
        $User = M('User')->getDbFields();
        if (!in_array('token', $User)) {
            echo 'add token field to user table;';
            $end = end($User);
            M()->execute('ALTER TABLE `codono_user` ADD COLUMN `token` VARCHAR(50) NULL AFTER `' . $end . '`');
        } else {
            echo 'Token Field Exists';
        }
    }

    public function initinfo()
    {
        $info = [];
        $info['WITHDRAW_NOTICE'] = L('YOUR_WITHDRAWAL_MESSAGE');
        $info['CHARGE_NOTICE'] = L('YOUR_RECHARGE_MESSAGE');
        $info['WEB_NAME'] = C('WEB_NAME');
        $info['WEB_TITLE'] = C('WEB_TITLE');
        $info['WEB_ICP'] = C('WEB_ICP');
        $info['INDEX_IMG'] = '';
        $News = M('Article')->where(array('type' => 'news'))->select();

        foreach ($News as $val) {
            $title = (50 < mb_strlen($val['title']) ? mb_substr($val['title'], 0, 50, 'utf-8') . '...' : $val['title']);
            $info['News'][] = array('id' => $val['id'], 'title' => $title);
        }

        $info['charge_account'] = array(
            'alipay' => array('bank' => 'Alipay', 'name' => "\t" . 'Account Name Here', 'card_num' => "\t" . '123456@alipay.com'),
            'bank' => array('bank' => 'Bank of China', 'name' => "\t" . 'Account Name Here', 'card_num' => '8888 8888 8888')
        );
        $myczType = M('MyczType')->where(array('status' => 1))->select();
        $myczTypeList=[];
        foreach ($myczType as $k => $v) {
            $myczTypeList[] = array('type' => $v['name'], 'title' => $v['title']);
        }

        $info['myczTypeList'] = $myczTypeList;
        $this->ajaxShow($info);
    }

    public function marketswithbase()
    {
        $market = strtoupper(I('get.market', '', 'text'));
        $codono_getCoreConfig = codono_getCoreConfig();
        $base_mkts = $codono_getCoreConfig['codono_indexcat'];
        if (!in_array($market, $base_mkts)) {
            $market = $base_mkts[0];
        }

        $info = [];

        foreach (C('market') as $val) {
            if ($base_mkts[$val['jiaoyiqu']] == $market) {
                $info['market'][] = array('id' => $val['id'], 'basemarket' => $base_mkts[$val['jiaoyiqu']], 'ticker' => $val['name'], 'fee_buy' => format_num($val['fee_buy']), 'fee_sell' => format_num($val['fee_sell']), 'name' => $val['title'], 'icon' => SITE_URL . 'Upload/coin/' . $val['xnbimg'], 'new_price' => $val['new_price'], 'buy_price' => format_num($val['buy_price']), 'sell_price' => format_num($val['sell_price']), 'min_price' => format_num($val['min_price']), 'max_price' => format_num($val['max_price']), 'change' => round($val['change'], 2), 'volume' => $val['volume'],'is_ws'=>($val['socket_type']==2)?1:0,'ws_pair'=>$this->wsPair($val),'ws_url'=>SOCKET_WS_URL);
            }
        }
        
        $this->ajaxShow($info);

    }

    public function marketInfo()
    {

        $info = [];
        foreach (C('market') as $val) {

            $info['market'][] = array('id' => $val['id'], 'ticker' => $val['name'], 'fee_buy' => format_num($val['fee_buy']), 'fee_sell' => format_num($val['fee_sell']), 'name' => $val['title'], 'icon' => SITE_URL . 'Upload/coin/' . $val['xnbimg'], 'new_price' => format_num($val['new_price']), 'buy_price' => format_num($val['buy_price']), 'sell_price' => format_num($val['sell_price']), 'min_price' => format_num($val['min_price']), 'max_price' => format_num($val['max_price']), 'change' => round($val['change'], 2), 'volume' => $val['volume']);
        }
        $this->ajaxShow($info);
    }

    public function filterMarket($type, $filter)
    {

        $info = [];
        
        $markets = C('market');
        
        if ($type == 'coin') {
            foreach ($markets as $val) {
                if ($val['xnb'] == strtolower($filter)) {
                    $info = $this->makeInfo($val, $info);
                }
            }
        }
        if ($type == 'base') {
            foreach ($markets as $val) {
                if ($val['rmb'] == strtolower($filter)) {
                    $info = $this->makeInfo($val, $info);
                }
            }
        }
        if ($type == 'change' || $type == 'new_price' || $type == 'volume') {
            $info=$this->sorter($markets, $type, $filter);
        }
        $this->ajaxShow($info);
    }

    private function sorter($array, $key, $direction)
    {

        switch ($direction) {
            case "asc":
                usort($array, function ($first, $second) use ($key) {

                    return (float)$first[$key] <=> (float)$second[$key];
                });
                break;
            case "desc":
               usort($array, function ($first, $second) use ($key) {
                    return (float)$second[$key] <=> (float)$first[$key];
                });
                break;
            default:
                break;
        }

        return $array;
    }

    public function singlemarketInfo()
    {

       $ticker = I('get.market', '', 'text');
		  if (!$ticker) {
            $ticker = C('market_mr');
        }
		if (!C('market')[$ticker]) {
            $ticker=I('get.market','','htmlspecialchars');
        }
		if (!C('market')[$ticker]) {
            echo json_encode([]);exit;
        }
		$xtra['xnb']=$xnb=explode('_', $ticker)[0];		
        $xtra['rmb']=$rmb=explode('_', $ticker)[1];		
		
        $xtra['title']=C('coin')[$xnb]['title'];
		$xtra['symbol']=C('coin')[$xnb]['name'];
		$xtra['developer']=C('coin')[$xnb]['cs_yf'];
		$xtra['algorithm']=C('coin')[$xnb]['cs_sf'];
		$xtra['release_date']=C('coin')[$xnb]['cs_fb'];
		$xtra['decimals']=C('coin')[$xnb]['cs_qk'];
		$xtra['supply']=C('coin')[$xnb]['cs_zl'];
		$xtra['difficulty']=C('coin')[$xnb]['cs_nd'];
		$xtra['block_reward']=C('coin')[$xnb]['cs_jl'];
		$xtra['features']=C('coin')[$xnb]['cs_ts'];
		$xtra['short_comings']=C('coin')[$xnb]['cs_bz'];
		
		
		
		$val = C('market')[$ticker];
		
        $info = array('id' => $val['id'], 'ticker' => $val['name'], 'fee_buy' => $val['fee_buy'], 'fee_sell' => $val['fee_sell'], 'name' => $val['title'], 'icon' => SITE_URL . 'Upload/coin/' . $val['xnbimg'], 'new_price' => $val['new_price'], 'buy_price' => $val['buy_price'], 'sell_price' => $val['sell_price'], 'min_price' => $val['min_price'], 'max_price' => $val['max_price'], 'change' => format_num($val['change'], 2), 'volume' => $val['volume'],'additional'=>$xtra,'is_ws'=>($val['socket_type']==2)?1:0,'ws_pair'=>$this->wsPair($val),'ws_url'=>SOCKET_WS_URL);
        $this->ajaxShow($info);
    }
    private function wsPair($val)
    {
        return $val['socket_pair'] ? $val['socket_pair'] :str_replace('_', '', $val['name']);
    }

    public function singlecoin()
    {
        $id = I('post.id', '', 'text');
        $info = [];
        foreach (C('market') as $val) {
            
            if ($val['rmb'] != 'usd') {
                continue;
            }
            if ($val['id'] != $id) {
                continue;
            }
            $info =
                array(
                    'id' => $val['id'],
                    'name' => $val['xnb'],
                    'icon' => SITE_URL . 'Upload/coin/' . $val['xnbimg'],
                    'price_' . $val['rmb'] => (double)$val['new_price'],
                    'fee_buy' => $val['fee_buy'],
                    'fee_sell' => $val['fee_sell'],
                    'new_price' => (double)$val['new_price'],
                    'buy_price' => (double)$val['buy_price'],
                    'sell_price' => (double)$val['sell_price'],
                    'min_price' => (double)$val['min_price'],
                    'max_price' => (double)$val['max_price'],
                    'percent_change_24h' => round($val['change'], 2),
                    '24h_volume_' . $val['rmb'] => (double)$val['volume']);
        }
        $this->ajaxShow($info);

    }

    public function ticker()
    {
        $info = $this->subTicker();
        $this->ajaxShow($info);

    }

    public function plainticker()
    {
        $info = $this->subTicker();
        //$this->ajaxShow($info);
        header('Content-type: application/json');
        echo(json_encode($info));
        exit;

    }

    /**
     * @return array
     */
    private function subTicker(): array
    {
        $info = [];
        
        foreach (C('market') as $val) {
            if ($val['rmb'] != 'usd') {
                continue;
            }
            $info[] =
                array(
                    'id' => $val['id'],
                    'name' => $val['xnb'],
                    'icon' => SITE_URL . 'Upload/coin/' . $val['xnbimg'],
                    'price_' . $val['rmb'] => (double)$val['new_price'],
                    'new_price' => (double)$val['new_price'],
                    'buy_price' => (double)$val['buy_price'],
                    'sell_price' => (double)$val['sell_price'],
                    'min_price' => (double)$val['min_price'],
                    'max_price' => (double)$val['max_price'],
                    'percent_change_24h' => round($val['change'], 2),
                    '24h_volume_' . $val['rmb'] => (double)$val['volume']);
        }
        return $info;
    }

    /**
     * @param $val
     * @param array $info
     * @return array
     */
    private function makeInfo($val, array $info): array
    {
        $info[] = ['id' => $val['id'], 'ticker' => $val['name'], 'fee_buy' => format_num($val['fee_buy']), 'fee_sell' => format_num($val['fee_sell']), 'name' => $val['title'], 'icon' => SITE_URL . 'Upload/coin/' . $val['xnbimg'], 'new_price' => format_num($val['new_price']), 'buy_price' => format_num($val['buy_price']), 'sell_price' => format_num($val['sell_price']), 'min_price' => format_num($val['min_price']), 'max_price' => format_num($val['max_price']), 'change' => round($val['change'], 2), 'volume' => $val['volume'],'is_ws'=>($val['socket_type']==2)?1:0,'ws_pair'=>$this->wsPair($val),'ws_url'=>SOCKET_WS_URL];
        return $info;
    }
}
