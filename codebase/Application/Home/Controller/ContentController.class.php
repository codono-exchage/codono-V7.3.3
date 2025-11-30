<?php

namespace Home\Controller;


class ContentController extends HomeController
{
    public function index()
    {
		
	//
    }
    public function apidocs(){

       $this->display();
    }
    public function redoc(){
        $content=file_get_contents(WEBSERVER_DIR.'/Public/apidocs/backswagger.json');
        $to_find1="http://{{siteurl}}";
        $to_replace1=SITE_URL;
        $to_find2="{{siteurl}}";
        $to_replace2=SITE_URL;
        $to_find3="//Api";
        $to_replace3='/Api';
        $content=str_replace($to_find1,$to_replace1,$content);
        $content=str_replace($to_find2,$to_replace2,$content);
        $content=str_replace($to_find3,$to_replace3,$content);
        $new_content=str_replace('NAMEHERE',SHORT_NAME,$content);
        echo $new_content;
    }
	public function market()
	{
        $codono_getCoreConfig = codono_getCoreConfig();
        if (!$codono_getCoreConfig) {
            $this->error(L('Incorrect Core Config'));
        }

        $this->assign('codono_jiaoyiqu', $codono_getCoreConfig['codono_indexcat']);		
	    $this->display();
    }
	public function info($market = NULL)
    {
        if (!$market) {
            $market = C('market_mr');
        }
		$data['market']= $market;
		$data['xnb']= $xnb=explode('_', $market)[0];
		$data['rmb']= explode('_', $market)[1];
		$data['symbol']=C('coin')[$xnb]['js_yw'];
		$data['name']=C('coin')[$xnb]['title'];
		$data['releasedate']=C('coin')[$xnb]['cs_fb'];
		$data['reward']=C('coin')[$xnb]['cs_jl'];
		$data['supply']=C('coin')[$xnb]['cs_zl'];
		$data['withdrawal']=C('coin')[$xnb]['zc_jz'];
		$data['deposit']=C('coin')[$xnb]['zr_jz'];
		$data['link']=C('coin')[$xnb]['js_gw'];
		$data['description']=strip_tags(C('coin')[$xnb]['js_sm']);
		$data['tradelink']=U("Trade/index/market/".$market);
		$response['status']=1;
		$response['info']=$data;
		header('Content-type: application/json');
        exit(json_encode($response));
    }
	
	 public function referral()
    {
		 $data = (APP_DEBUG ? null : S('referralMarket'));
		 if (!$data) {
            foreach (C('market') as $k => $v) {
                $v['xnb'] = explode('_', $v['name'])[0];
                $v['rmb'] = explode('_', $v['name'])[1];
                $data[$k]['name'] = $v['name'];
                $data[$k]['invit_buy'] = $v['invit_buy'];
				$data[$k]['invit_sell'] = $v['invit_sell'];
				$data[$k]['invit_1'] = $v['invit_1']?:0;
				$data[$k]['invit_2'] = $v['invit_2']?:0;
				$data[$k]['invit_3'] = $v['invit_3']?:0;
				$data[$k]['img'] = "/Upload/coin/".$v['xnbimg'];
                $data[$k]['title'] = $v['title'];
            }

            S('referralMarket', $data);
        }
		$disp_class="showme";
		if(C('reg_award') ==0 && C('ref_award') ==0  ){$disp_class="hide";}
		$this->assign('is_reg_award',C('reg_award'));
		$this->assign('reg_award_num',format_num(C('reg_award_num'),2));
		$this->assign('reg_award_coin',strtoupper(C('reg_award_coin')));

		$this->assign('is_ref_award',C('ref_award'));
		$this->assign('ref_award_num',format_num(C('ref_award_num'),2));
		$this->assign('ref_award_coin',strtoupper(C('ref_award_coin')));		
		$this->assign('referralMarket', $data);
		$this->assign('disp_class',$disp_class);
	
	 $this->display();
    }
	public function fees()
    {

		
		 $data = (APP_DEBUG ? null : S('feesMarket'));
		 
		 if (!$data) {
            foreach (C('market') as $k => $v) {
				
				if($v['status']==1)
			{
			
                $v['xnb'] = explode('_', $v['name'])[0];
                $v['rmb'] = explode('_', $v['name'])[1];
                $data[$k]['name'] = $v['name'];
                $data[$k]['fee_buy'] = $v['fee_buy'];
				$data[$k]['fee_sell'] = $v['fee_sell'];
				$data[$k]['img'] = "/Upload/coin/".$v['xnbimg'];
                $data[$k]['title'] = $v['title'];
				$data[$k]['status'] = $v['status'];
				$data[$k]['xnb_fee']=C('coin')[$v['xnb']]['zc_fee'];
				$data[$k]['rmb_fee']=C('coin')[$v['rmb']]['zc_fee'];
				$data[$k]['xnb_name']=strtoupper($v['xnb']);
				$data[$k]['rmb_name']=strtoupper($v['xnb']);
				}
            }

            S('feesMarket', $data);
        }
		
		$this->assign('feesMarket', $data);
		
	 $this->display();
    }

	public function redis_test(){
		
		S("is_redis_working","Congrats ! Your redis is working!");
		var_dump(S("is_redis_working"));
		if(!S("is_redis_working")){
			echo "Your redis on server isnt working";
		}
		else{
			echo S("is_redis_working");
		}
		if(REDIS_ENABLED!=1){
			echo "<br/>Also Please enable your REDIS_ENABLED to 1 in pure file<br/>";
		}

		
	}
	function setTradeJson($market)
    {
        $timearr = [1, 3, 5, 10, 15, 30, 60, 120, 240, 360, 720, 1440, 10080];

        foreach ($timearr as $k => $v) {
			echo "$market<br/>";
            $tradeJson = M('TradeJson')->where(['market' => $market, 'type' => $v])->order('id desc')->find();
            if ($tradeJson) {
                $addtime = $tradeJson['addtime'];
            } else {
                $addtime = M('TradeLog')->where(['market' => $market])->order('id asc')->getField('addtime');
            }
            $youtradelog=false;
            if ($addtime) {
                $youtradelog = M('TradeLog')->where('addtime >=' . $addtime . '  and market =\'' . $market . '\'')->sum('num');
            }

            if ($youtradelog) {
                if ($v == 1) {
                    $start_time = $addtime;
                } else {
                    $start_time = mktime(date('H', $addtime), floor(date('i', $addtime) / $v) * $v, 0, date('m', $addtime), date('d', $addtime), date('Y', $addtime));
                }

                $x = 0;

                for (; $x <= 20; $x++) {
                    $na = $start_time + (60 * $v * $x);
                    $nb = $start_time + (60 * $v * ($x + 1));

                    if (time() < $na) {
                        break;
                    }

                    $sum = M('TradeLog')->where('addtime >=' . $na . ' and addtime <' . $nb . ' and market =\'' . $market . '\'')->sum('num');

                    if ($sum) {
                        $sta = M('TradeLog')->where('addtime >=' . $na . ' and addtime <' . $nb . ' and market =\'' . $market . '\'')->order('id asc')->getField('price');
                        $max = M('TradeLog')->where('addtime >=' . $na . ' and addtime <' . $nb . ' and market =\'' . $market . '\'')->max('price');
                        $min = M('TradeLog')->where('addtime >=' . $na . ' and addtime <' . $nb . ' and market =\'' . $market . '\'')->min('price');
                        $end = M('TradeLog')->where('addtime >=' . $na . ' and addtime <' . $nb . ' and market =\'' . $market . '\'')->order('id desc')->getField('price');
                        $d = [$na, $sum, $sta, $max, $min, $end]; //date,qty,open,high,low,close

                        if (M('TradeJson')->where(['market' => $market, 'addtime' => $na, 'type' => $v])->find()) {
                            M('TradeJson')->where(['market' => $market, 'addtime' => $na, 'type' => $v])->save(['data' => json_encode($d)]);
                        } else {
                            M('TradeJson')->add(['market' => $market, 'data' => json_encode($d), 'addtime' => $na, 'type' => $v]);
                            M('TradeJson')->execute('commit');
                            M('TradeJson')->where(['market' => $market, 'data' => '', 'type' => $v])->delete();
                            M('TradeJson')->execute('commit');
                        }
                    } else {
                        M('TradeJson')->add(['market' => $market, 'data' => '', 'addtime' => $na, 'type' => $v]);
                        M('TradeJson')->execute('commit');
                    }
                }
            }
        }

        return 'Calculation successful!';
    }

    public function E403()
    {
        $this->assign('type', '403');
        $this->assign('error', 'Oops, an error has occurred. Forbidden!');
        $this->display('error');
    }

    public function E404()
    {
        $this->assign('type', '404');
        $this->assign('error', 'Oops, an error has occurred. Page not found!');
        $this->display('error');
    }

    public function E405()
    {
        $this->assign('type', '405');
        $this->assign('error', 'Oops, an error has occurred. Not allowed!');
        $this->display('error');
    }

    public function E500()
    {
        $this->assign('type', '500');
        $this->assign('error', 'Oops, an error has occurred. Internal server error!');
        $this->display('error');
    }

    public function E503()
    {
        $this->assign('page_title', 'Error 503');
        $this->assign('type', '503');
        $this->assign('error', 'Oops, an error has occurred. Service unavailable!');
        $this->display('error');
    }

    public function health()
    {
		
		 $data = M('Coin')->where(['status' => 1])->field('id,name,title,zr_jz,type,zr_dz,zc_fee,zc_flat_fee,zc_jz')->select();
        $this->assign('page_title', L("System Health"));
		$this->assign('data', $data);
        $this->display();
    }

	public function apps()
	{
	$this->display();
	}
}

