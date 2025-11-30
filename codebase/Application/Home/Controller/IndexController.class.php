<?php

namespace Home\Controller;

use Think\Page;

class IndexController extends HomeController
{
	public function test(){
		echo 'connected';
	}
	public function roadmap(){
		$roadmap = (APP_DEBUG ? null : S('roadmap'));
		
        if (!$roadmap) {
            $roadmap = M('Roadmap')->order('id asc')->select();
            S('roadmap', $roadmap);
        }
		$this->assign('roadmap', $roadmap);
		$this->display();	
	}
    public function index()
    {
      $indexAdver = (APP_DEBUG ? null : F('index_indexAdver'));
		
        if (!$indexAdver) {
            $indexAdver = M('Adver')->where(['status' => 1])->order('id asc')->select();
            F('index_indexAdver', $indexAdver);
        }
        
		$this->assign('coinCount',count(C('coin_safe')));	
        $this->assign('indexAdver', $indexAdver);
		
		
		/* Slider news */
		$index_news = (APP_DEBUG ? null : F('index_news'));
				if (!$index_news) {
                        $index_news = M('Article')->where(['type' => 'news', 'status' => 1, 'index' => 1])->order('id desc')->limit(15)->select();
                        
                    F('index_news', $index_news);
                }
				
        $this->assign('index_news', $index_news);		
		/* Slider Ends*/

		/* Guide Starts */
		$index_guide = (APP_DEBUG ? null : F('index_guide'));
				if (!$index_guide) {
                        $index_guide = M('Article')->where(['type' => 'guide', 'status' => 1, 'index' => 1])->order('id desc')->limit(15)->select();
                        
                    F('index_guide', $index_guide);
                }
				
        $this->assign('index_guide', $index_guide);		
        
		/* Guide Ends*/
		/* Blog Starts */
		$index_blog = (APP_DEBUG ? null : F('index_blog'));
				if (!$index_blog) {
                        $index_blog = M('Article')->where(['type' => 'blog', 'status' => 1, 'index' => 1])->order('id desc')->limit(15)->select();
                        
                    F('index_blog', $index_blog);
                }
				
        $this->assign('index_blog', $index_blog);		
		/* Blog Ends*/
		/*FAQ starts */
		
		$index_faqType = (APP_DEBUG ? null : F('index_faqType'));

                if (!$index_faqType) {
                    $index_faqType = M('ArticleType')->where(['status' => 1, 'index' => 0,'shang'=>'faq'])->order('sort asc ,id desc')->limit(3)->select();
                    
					F('index_indexArticleType', $index_faqType);
                }
                $index_faq = (APP_DEBUG ? null : F('index_faq'));

                 if (!$index_faq) {
                    $x=0;
                    foreach ($index_faqType as  $v) {
                        
                        $index_faq[$v['id']] = M('Article')->where(['type' => $v['name'], 'status' => 1, 'index' => 1])->order('id desc')->limit(15)->select();
                        $x++;
                    }

                    F('index_faq', $index_faq);
                }
    
		$this->assign('index_faqType', $index_faqType);
        $this->assign('index_faq', $index_faq);
		/* FAQ ENDS */
        $indexLink = (APP_DEBUG ? null : F('index_indexLink'));

        if (!$indexLink) {
            $indexLink = M('Link')->where(['status' => 1])->order('sort asc ,id desc')->select();
			F('index_indexLink', $indexLink);
        }
		
		/* ICO Widget Show*/
		$list = (APP_DEBUG ? null : F('ico_Widget'));
		
		
		if (!$list) {
		 $where['status'] = ['neq', 0];
		 $where['homepage']= ['eq',1];
        $Model = M('Issue');
        $count = $Model->where($where)->count();
        $Page = new Page($count, 3);
        $Page->show();
        $list = $Model->where($where)->order('tuijian asc,paixu desc,addtime desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();
		
		F('ico_Widget',$list);
		}
        $list_jinxing = [];//Running
		
		foreach ($list as $k => $v) {

            $list[$k]['bili'] = bcmul(bcdiv($v['deal'] , $v['num'],8) ,100, 2);
            $list[$k]['endtime'] = date('Y-m-d H:i:s', strtotime($v['time']) + $v['tian'] * 3600 * 24);
			if (isset(C('coin')[$v['coinname']])) {
					$list[$k]['coinname'] = C('coin')[$v['coinname']]['title'];
			} else {
				$list[$k]['coinname'] = '';
			}
			if (isset(C('coin')[$v['buycoin']])) {
					$list[$k]['buycoin'] = C('coin')[$v['buycoin']]['title'];
			} else {
				$list[$k]['buycoin'] = '';
			}

            
            
            $list[$k]['content'] = mb_substr(clear_html($v['content']), 0, 350, 'utf-8');


            $end_ms = strtotime($v['time']) + $v['tian'] * 3600 * 24;
            $begin_ms = strtotime($v['time']);


            $list[$k]['beginTime'] = date('Y-m-d H:i:s', $begin_ms);
            $list[$k]['endTime'] = date('Y-m-d H:i:s', $end_ms);

            $list[$k]['zhuangtai'] = L('RUNNING');

            if ($begin_ms > time()) {
                $list[$k]['zhuangtai'] = L('UPCOMING');//upcoming
            }


            if ($list[$k]['num'] <= $list[$k]['deal']) {
                $list[$k]['zhuangtai'] = L('ENDED');//ended
            }

            if ($end_ms < time()) {
                $list[$k]['zhuangtai'] = L('ENDED');//ended
            }

            $list_jinxing[] = $list[$k];


        }
		$stats['raised']=nice_number($list_jinxing[0]['deal']*$list_jinxing[0]['price']);
		$stats['total']=nice_number($list_jinxing[0]['num']*$list_jinxing[0]['price']);
		if($list_jinxing[0]['zhuangtai']=='Upcoming'){
		$stats['showdate']=date('Y/m/d', strtotime($list_jinxing[0]['beginTime']));
		$stats['starttext']='Token Sale Starts in';	
		}
		if($list_jinxing[0]['zhuangtai']=='Running'){
		$stats['starttext']='Token Sale Stage End In';	
		$stats['showdate']=date('Y/m/d', strtotime($list_jinxing[0]['endTime']));
		}
		if($list_jinxing[0]['zhuangtai']=='Ended'){
		$stats['starttext']='Token Sale Stage';	
		$stats['showdate']=date('Y/m/d', strtotime($list_jinxing[0]['beginTime']));
		}
		
		if(strtoupper($list_jinxing[0]['buycoin'])=='USD' || strtoupper($list_jinxing[0]['buycoin']) == 'USDT'){
			$stats['coinsymbol']='$';
		}else{
		$stats['coinsymbol']=strtoupper(substr($list_jinxing[0]['buycoin'],0,1));
		}
		$this->assign('singletoken',$list_jinxing[0]);
		$this->assign('stats',$stats);
        $this->assign('list_running', $list_jinxing);
		/* Issue widget Show end */
		
		
		
        $codono_getCoreConfig = codono_getCoreConfig();
        if (!$codono_getCoreConfig) {
            $this->error(L('Incorrect Core Config'));
        }

        $this->assign('codono_jiaoyiqu', $codono_getCoreConfig['codono_indexcat']);

        $this->assign('indexLink', $indexLink);
		

        $footerArticleType = (APP_DEBUG ? null : F('footer_indexArticleType'));

        if (!$footerArticleType) {
            $footerArticleType = M('ArticleType')->where(['status' => 1, 'footer' => 1, 'shang' => ''])->order('sort asc ,id desc')->limit(3)->select();
            F('footer_indexArticleType', $footerArticleType);
        }

        $this->assign('footerArticleType', $footerArticleType);
        $footerArticle = (APP_DEBUG ? null : F('footer_indexArticle'));

        if (!$footerArticle) {
            foreach ($footerArticleType as $k => $v) {
                $footerArticle[$v['name']] = M('ArticleType')->where(['shang' => $v['name'], 'footer' => 1, 'status' => 1])->order('id asc')->limit(4)->select();
            }

            F('footer_indexArticle', $footerArticle);
        }
	
    $this->assign('footerArticle', $footerArticle);
	$cryptoList=$this->marketListLoggedOut();
	$top5markets=$this->top5markets();
	$this->assign('debug',$this->debug());
	$this->assign('top5markets',$top5markets);
	$this->assign('cryptoList',$cryptoList);
    
	if('THEME_NAME'=='multiverse'){
			$this->display('index_mv');
	}else{
			$this->display();
	}
    
    }

	public function markets(){	
	    $this->index();
	}
    private  function debug(){
            $to_check = ['MOBILE_CODE' => MOBILE_CODE, 'APP_DEMO' => APP_DEMO, 'M_DEBUG' => M_DEBUG, 'ADMIN_DEBUG' => ADMIN_DEBUG, 'DEBUG_WINDOW' => DEBUG_WINDOW];

            $print=false;

            foreach ($to_check as $key=>$val) {
                if ($val === 1) {
                    $print.=$key.',' ;
                }
            }
            if($print){
                return check_mode($print);
            }else{
                return false;
            }
        }


	private function marketListLoggedOut(){
        
        $markets=C('market');
        
        $list=[];
        foreach($markets as $market){
            if(strtolower($market['rmb'])==strtolower(SYSTEMCURRENCY)){
                $list[$market['xnb']]['name']=$market['xnb'];
                $list[$market['xnb']]['title']=$market['navtitle'];
                $list[$market['xnb']]['img']=$market['xnbimg'];
                $list[$market['xnb']]['price']=$market['new_price'];
                $list[$market['xnb']]['change']=$market['change'];
                $list[$market['xnb']]['balance']=0;
                $list[$market['xnb']]['freeze']=0;
                if($market['change']>=0){
                    $list[$market['xnb']]['color']='green';
                }
                else{$list[$market['xnb']]['color']='red';}
                if($market['new_price']<=0){
                    unset( $list[$market['xnb']]);
                }
            }
        }

        return $list;
    }
	private function top5markets(){
        $numbermappings = ['one', 'two', 'three', 'four', 'five', 'six', 'seven'];
        $markets=C('Market');
		
		$vol = $out= [];
		foreach ($markets as $key => $row)
		{
			$vol[$key] = $row['volume'];
		}
		array_multisort($vol, SORT_DESC, $markets);
		
		$top_5_markets = array_slice($markets, 0, 6);
		$i=0;
        $out=[];
		foreach($top_5_markets as $mkts){
			
			$out[$i]['name']=$mkts['name'];
			$out[$i]['pairname']=$mkts['pairname'];
			$out[$i]['change']=$mkts['change'];
			$out[$i]['rmb']=$mkts['rmb'];
			$out[$i]['xnb']=$mkts['xnb'];
			$out[$i]['xnbimg']='Upload/coin/'.C('coin_safe')[$mkts['xnb']]['img'];
			$out[$i]['volume']=$mkts['volume'];
			$out[$i]['new_price']=bcadd(format_num($mkts['new_price']),0,10);
			$out[$i]['chart-class']= 'updating-chart-' .$numbermappings[$i];
			$out[$i]['string']=$this->randStringForGraph();
			if($mkts['change']<0)
			{
			$out[$i]['change-class']= 'red';
			}else{
			$out[$i]['change-class']= 'green';
			}
			$i++;
			
		}
		
        return $out;
    }
	private function randStringForGraph(){
		$string=rand(1,9);
		for($i=0;$i<20;$i++){
			$rand=rand(1,9);
			$string=$string.','.$rand;
		}
		return $string;
	}

    public function newPrice()
    {

        $data = $this->allCoinPrice();
        //var_dump($data);
        // exit;
        $last_data = S('ajax_all_coin_last');
        $_result = [];
        if (empty($last_data)) {
            foreach (C('market') as $k => $v) {
                $_result[$v['id'] . '-' . strtoupper($v['xnb'])] = $data[$k][1] . '-0.0';
            }
        } else {
            foreach (C('market') as $k => $v) {
                $_result[$v['id'] . '-' . strtoupper($v['xnb'])] = $data[$k][1] . '-' . ($data[$k][1] - $last_data[$k][1]);
            }
        }

        S('ajax_all_coin_last', $data);

        $data = json_encode(
            [
                'result' => $_result,
            ]
        );
        exit($data);

    }


    protected function allCoinPrice()
    {
        $data = (APP_DEBUG ? null : S('allCoinPrice'));
       if(!$data) {
           // market Transaction Record
           $marketLogs = [];
           foreach (C('market') as $k => $v) {
               $tradeLog = M('TradeLog')->where(['status' => 1, 'market' => $k])->order('id desc')->limit(50)->select();
               $_data = [];
               foreach ($tradeLog as $_k => $_v) {
                   $_data['tradelog'][$_k]['addtime'] = date('m-d H:i:s', $_v['addtime']);
                   $_data['tradelog'][$_k]['type'] = $_v['type'];
                   $_data['tradelog'][$_k]['price'] = $_v['price'] * 1;
                   $_data['tradelog'][$_k]['num'] = round($_v['num'], 6);
                   $_data['tradelog'][$_k]['mum'] = round($_v['mum'], 2);
               }
               $marketLogs[$k] = $_data;
           }

           $themarketLogs = [];
           if ($marketLogs) {
               $last24 = time() - 86400;
               $_date = date('m-d H:i:s', $last24);
               foreach (C('market') as $k => $v) {
                   $tradeLog = $marketLogs[$k]['tradelog'] ?? null;
                   if ($tradeLog) {
                       $sum = 0;
                       foreach ($tradeLog as $_k => $_v) {
                           if ($_v['addtime'] < $_date) {
                               continue;
                           }
                           $sum += $_v['mum'];
                       }
                       $themarketLogs[$k] = $sum;
                   }
               }
           }

           foreach (C('market') as $k => $v) {
               $data[$k][0] = $v['title'];
               $data[$k][1] = round($v['new_price'], $v['round']);
               $data[$k][2] = round($v['buy_price'], $v['round']);
               $data[$k][3] = round($v['sell_price'], $v['round']);
               $data[$k][4] = $themarketLogs[$k] ?? 0;//round($v['volume'] * $v['new_price'], 2) * 1;
               $data[$k][5] = '';
               $data[$k][6] = round($v['volume'], 2);
               $data[$k][7] = round($v['change'], 2);
               $data[$k][8] = $v['name'];
               $data[$k][9] = $v['xnbimg'];
               $data[$k][10] = '';
           }
           S('allCoinPrice', $data);
       }

        return $data;
    }

}