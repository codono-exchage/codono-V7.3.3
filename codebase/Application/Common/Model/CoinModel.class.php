<?php

namespace Common\Model;

class CoinModel extends \Think\Model
{
	protected $keyS = 'Coin';

    public function findSymbol($coin): ?string
    {
        $coin = strtolower($coin);
        $coininfo = C('coin')[$coin];
        if (!is_Array($coininfo)) {
            return null;
        }
        $symbol = strtolower($coininfo['symbol']);

        if ($symbol == null) {
            return $coin;
        } else {
            return $symbol;
        }
    }
	public function depositCoin($data){

				$mo=M();
                $mo->startTrans();
                $num = format_num($data['amount'], 8);
                $coin = strtolower($data['coin']);
				$coin = $this->findSymbol($coin);
                $rs[] = $mo->table('codono_myzr')->add(array('userid' => $data['userid'], 'type' => 'esmart', 'username' => $data['address'], 'coinname' => $coin, 'fee' => 0, 'txid' => $data['hash'], 'num' => $data['num'], 'mum' => $num, 'addtime' => time(), 'status' => 1));
                $rs[] = $mo->table('codono_user_coin')->where(array('userid' => $data['userid']))->setInc($coin, $num);
                if (check_arr($rs)) {
					$mo->commit();
					$data['status']=1;
                    $data['msg']= $coin . '=>' . $data['address'] . '=>' . $num . ' commit ok';
                } else {
                    $mo->rollback();
					$data['status']=0;
					$data['msg']= 'Could not deposit';
                }
                return $data;
	}
    public function check_install()
    {
    }

    public function check_uninstall()
    {
    }

    public function check_server()
    {
    }

    public function check_authorization()
    {
    }

    public function check_database()
    {
    }

    public function check_update()
    {
    }

    public function check_file()
    {
    }

    public function get_all_name_list()
    {
        $list = M('Coin')->where(array())->order('sort asc,name asc')->select();

        if (is_array($list)) {
            foreach ($list as $k => $v) {
                $get_all_name_list[$v['name']] = strtoupper($v['name'])." [".$v['title']."]";
            }
        } else {
            $get_all_name_list = null;
        }

        return $get_all_name_list;
    }

    public function get_coin_name_list()
    {
        $list = M('Coin')->where(['_string' => '(symbol IS NULL OR symbol = "")'])->field('name,symbol,title,status')->order('name asc')->select();
        
         $get_coin_name_list=[];   
        if (is_array($list)) {
            foreach ($list as $k => $v) {
                if($v['symbol']==null || $v['symbol']=='')
                $get_coin_name_list[$v['name']] = strtoupper($v['name'])." [".$v['title']."]";
            }
        } else {
            $get_coin_name_list = null;
        }
         
        return $get_coin_name_list;
    }
    public function get_all_xnb_list()
    {
        return $this->sub_list();
    }


    public function get_all_xnb_list_allow()
    {
        return $this->sub_list();
    }
	
	 public function get_all_fiat()
    {
		$get_all_fiat = (APP_DEBUG ? null : S('get_all_fiat'));
		
		if (!$get_all_fiat) {
        $list = M('Coin')->where(array("status" => 1,'type'=>'rmb'))->order('sort asc,name asc')->select();

        if (is_array($list)) {
            foreach ($list as $k => $v) {
                if ($v['type'] == 'rmb') {
                    //$get_all_fiat[$v['title']] = $v['name'];
					$get_all_fiat[$v['name']] = $v['title'];
                }
            }
        } else {
            $get_all_fiat = null;
        }
		
		S('get_all_fiat', $get_all_fiat);
		
		}

        return $get_all_fiat;
    }


    public function get_title($name = NULL)
    {
        if (empty($name)) {
            return null;
        }

        $get_title = M('Coin')->where(array('name' => $name))->getField('title');
        return $get_title;
    }

    public function get_img($name = NULL)
    {
        if (empty($name)) {
            return null;
        }

        $get_img = M('Coin')->where(array('name' => $name))->getField('img');
        return $get_img;
    }

    public function get_sum_coin($name = NULL, $userid = NULL)
    {
        if (empty($name)) {
            return null;
        }

        if ($userid) {
            $a = M('UserCoin')->where(array('userid' => $userid))->sum($name);
            $b = M('UserCoin')->where(array('userid' => $userid))->sum($name . 'd');
        } else {
            $a = M('UserCoin')->sum($name);
            $b = M('UserCoin')->sum($name . 'd');
        }

        $c = $a + $b;
        return $c;
    }

    /**
     * @return array|null
     */
    private function sub_list(): ?array
    {
        $list = M('Coin')->where(array("status" => 1))->order('sort asc,name asc')->select();

        if (is_array($list)) {
            foreach ($list as $k => $v) {
                if ($v['type'] != 'rmb') {
                    $get_all_xnb_list[$v['name']] = $v['title'];
                }
            }
        } else {
            $get_all_xnb_list = null;
        }

        return $get_all_xnb_list;
    }
}