<?php

namespace Api\Controller;

class CommonController extends \Think\Controller
{
    protected function _initialize()
    {
        /* Allow API Access*/
        header('Access-Control-Allow-Origin: *');
        header("Access-Control-Allow-Headers: token,Origin, X-Requested-With, Content-Type, Accept,ID,TOKEN");
        header('Access-Control-Allow-Methods: POST,GET');

        $config = (APP_DEBUG ? null : S('home_config'));

        if (!$config) {
            $config = M('Config')->where(array('id' => 1))->find();
            S('home_config', $config);
        }

        if (!$config['web_close']) {
            $this->ajaxShow($config['web_close_cause'], '-44');
            exit();
        }

        C($config);
        $coin = (APP_DEBUG ? null : S('home_coin'));

        if (!$coin) {
            $coin = M('Coin')->where(array('status' => 1))->select();
            S('home_coin', $coin);
        }

        $coinList = [];

        foreach ($coin as $k => $v) {
            $coinList['coin'][$v['name']] = $v;
			if ($v['status'] == 1 && ($v['symbol'] == NULL ||$v['symbol']=='')) {
                $coinList['coin_safe'][$v['name']] = array('id' => $v['id'], 'name' => $v['name'], 'title' => $v['title'], 'img' => $v['img'], 'type' => $v['type'], 'symbol' => $v['symbol'],'tokenof' => $v['tokenof'], 'zr_jz' => $v['zr_jz'], 'deposit' => $v['zr_jz'], 'zc_jz' => $v['zc_jz'], 'withdrawal' => $v['zc_jz'], 'confirmations' => $v['zr_dz'], 'explorer' => $v['js_wk'], 'zc_max' => $v['zc_max'], 'zc_min' => $v['zc_min'], 'zc_fee' => $v['zc_fee'], 'zc_flat_fee' => $v['zc_flat_fee'],'zc_coin' => $v['zc_coin']?:$v['name']);
            }
            if ($v['name'] != 'usd') {
                $coinList['coin_list'][$v['name']] = $v;
            }

            if ($v['type'] == 'rmb') {
                $coinList['rmb_list'][$v['name']] = $v;
            } else {
                $coinList['xnb_list'][$v['name']] = $v;
            }

            if ($v['type'] == 'rgb') {
                $coinList['rgb_list'][$v['name']] = $v;
            }

            if ($v['type'] == 'qbb') {
                $coinList['qbb_list'][$v['name']] = $v;
            }
            if ($v['type'] == 'tron') {
                $coinList['tron_list'][$v['name']] = $v;
            }
			if ($v['type'] == 'esmart') {
                $coinList['esmart_list'][$v['name']] = $v;
            }
			if ($v['type'] == 'cryptoapis') {
                $coinList['cryptoapis_list'][$v['name']] = $v;
            }
            if ($v['type'] == 'blockio') {
                $coinList['blockio_list'][$v['name']] = $v;
            }
        }

        C($coinList);
        $market = (APP_DEBUG ? null : S('home_market'));

        if (!$market) {
            $market = M('Market')->where(array('status' => 1))->select();
            S('home_market', $market);
        }

        foreach ($market as $k => $v) {
            $v['new_price'] = round($v['new_price'], $v['round']);
            $v['buy_price'] = round($v['buy_price'], $v['round']);
            $v['sell_price'] = round($v['sell_price'], $v['round']);
            $v['min_price'] = round($v['min_price'], $v['round']);
            $v['max_price'] = round($v['max_price'], $v['round']);
            $v['xnb'] = explode('_', $v['name'])[0];
            $v['rmb'] = explode('_', $v['name'])[1];
            $v['xnbimg'] = C('coin')[$v['xnb']]['img'];
            $v['rmbimg'] = C('coin')[$v['rmb']]['img'];
            $v['volume'] = $v['volume'] * 1;
            $v['change'] = $v['change'] * 1;
            $v['title'] = C('coin')[$v['xnb']]['title'] . '(' . strtoupper($v['xnb']) . '/' . strtoupper($v['rmb']) . ')';
            $marketList['market'][$v['name']] = $v;
        }

        C($marketList);
    }
	public function showResponse($json_data){
		header('Content-type: application/json');
        echo(json_encode($json_data));
        exit;
	}

    protected function ajaxShow($data, $status = 1)
    {
        $arr['status'] = $status;
        $arr['data'] = $data;
        header("Content-type:application/json");
        echo json_encode($arr);
        exit();
    }

    protected function error($message = '', $jumpUrl = '', $ajax = false)
    {
        $this->ajaxShow($message, 0);
    }

    protected function success($msg='',$jumpurl='',$ajax=false)
    {
        $this->ajaxShow($msg);
    }

    protected function userid($type = 0)
    {
        $http_id = filter_var($_SERVER['HTTP_ID'], FILTER_SANITIZE_NUMBER_INT);
        $http_token = filter_var($_SERVER['HTTP_TOKEN'], FILTER_SANITIZE_STRING);
        
        if (!$http_id || !$http_token) {
            
            if ($uid = userid()) {
                return $uid;
            }
            if (!$type) {
                $this->ajaxShow('Please sign in', -99);
            } else {
                return null;
            }
        }
        
        $user_id = intval(trim($http_id));
        
        if (S('APP_AUTH_ID_' . $user_id) == trim($http_token)) {
            return $user_id;
        } else {
            if ($uid = userid()) {
                return $uid;
            }
            
		//If api_type is static , It means user has to send apikey and id in header
		$api_type='dynamic';
		
		if($api_type=='dynamic')
		{
			if ($res = M('User')->where(array('id' => $user_id,'status'=>1))->field('token')->find()) {
                if ($res['token'] == trim($http_token)) {
                    S('APP_AUTH_ID_' . $user_id, $res['token']);
                    return $user_id;
                }
            }
		}else{	
			if ($res = M('User')->where(array('id' => $user_id,'status'=>1))->field('apikey')->find()) {
                if ($res['apikey'] == trim($http_token)) {
                    S('APP_AUTH_ID_' . $user_id, $res['apikey']);
                    return $user_id;
                }
            }
		}
        
            if (!$type) {
                $this->ajaxShow('Authentication failed', -99);  
            } else {
                return null;
            }
        }
    }

    protected function tokenuserid($type = 0)
    {
		$input=I('post.');
        if (!$input['userId'] || !$input['token']) {
            if ($uid = userid()) {
                return $uid;
            }

            if (!$type) {
                $this->ajaxShow('Please sign in', -99);
            } else {
                return null;
            }
        }

        $user_id = intval(trim($input['userId']));

        if (S('APP_AUTH_ID_' . $user_id) == trim($input['token'])) {
            return $user_id;
        } else {
            if ($uid = userid()) {
                return $uid;
            }

            if ($res = M('User')->where(array('id' => $user_id))->field('token')->find()) {
                if ($res['token'] == trim($input['token'])) {
                    S('APP_AUTH_ID_' . $user_id, $res['token']);
                    return $user_id;
                }
            }

            if (!$type) {
                $this->ajaxShow('Please sign in', -99);
            } else {
                return null;
            }
        }
    }

    protected function mankRand($min = 1, $max = 100, $n = 2)
    {
        $int_n = rand($min, $max);
        $float_n = rand(0, 9) / pow(10, $n);
        return $int_n + $float_n;
    }

    protected function doNums($arr, $k = 'width')
    {
        if (!is_array($arr)) {
            return false;
        }

        $nums = $sums = 0;

        foreach ($arr as $val) {
            $sums += $val[$k];
            $nums++;
        }

        $pres = (2 * $sums) / $nums;

        foreach ($arr as $key => $val) {
            $arr[$key][$k] = ($pres < $val[$k] ? '100' : intval(($val[$k] * 100) / $pres));
        }

        return $arr;
    }

    protected function dataHash($var)
    {
        if (!is_array($var)) {
            return md5($var);
        } else {
            return md5(serialize($var));
        }
    }
}
