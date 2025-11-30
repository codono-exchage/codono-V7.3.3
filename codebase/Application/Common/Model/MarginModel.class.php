<?php
namespace Common\Model;

class MarginModel extends \Think\Model
{
	protected $keyS = 'Margin';

	public function undo($id = NULL)
	{
		if (!check($id, 'd')) {
			return array('0', 'Parameter error');
		}

		$trade = M('TradeLever')->where(array('id' => $id))->find();

		if (!$trade) {
			return array('0', 'order does not exist');
		}

		if ($trade['status'] != 0) {
			return array('0', 'order cannot be canceled');
		}

		$xnb = explode('_', $trade['market'])[0];
		$rmb = explode('_', $trade['market'])[1];

		if (!$xnb) {
			return array('0', 'sell market error');
		}

		if (!$rmb) {
			return array('0', 'buy market error');
		}

		$fee_buy = C('market')[$trade['market']]['fee_buy'];
		$fee_sell = C('market')[$trade['market']]['fee_sell'];

		if ($fee_buy < 0) {
			return array('0', 'Buying fee error');
		}

		if ($fee_sell < 0) {
			return array('0', 'Selling fee error');
		}


		$mo = M();
		$mo->execute('set autocommit=0');
		$mo->execute('lock tables codono_lever_coin write  , codono_trade_lever write');
		$rs = array();
		$user_coin = $mo->table('codono_lever_coin')->where(array('userid' => $trade['userid'],'name'=>$xnb))->find();
		$rmbgg = 'p_yue';
		$xnbgg = 'yue';
		if ($trade['type'] == 1) {
			$mun = format_num(((($trade['num'] - $trade['deal']) * $trade['price']) / 100) * (100 + $fee_buy), 8);
			$user_buy = $mo->table('codono_lever_coin')->where(array('userid' => $trade['userid'],'name'=>$xnb))->find();
			$user_buy[$rmb . 'd'] = $user_buy['p_yued'];
			if ($mun <= format_num($user_buy[$rmb . 'd'], 8)) {
				$save_buy_rmb = $mun;
			}
			else if ($mun <= format_num($user_buy[$rmb . 'd'], 8) + 1) {
				
				$save_buy_rmb = $user_buy[$rmb . 'd'];
			}
			else {
				$mo->execute('rollback');
				$mo->execute('unlock tables');
				
				$mo->execute('commit');
				return array('0', 'Undo failed 1');
			}

			
			if (0 < $save_buy_rmb) {
			    $rs[] = $mo->table('codono_lever_coin')->where(array('userid' => $trade['userid'],'name'=>$xnb))->setInc($rmbgg, $save_buy_rmb);
			    $rs[] = $mo->table('codono_lever_coin')->where(array('userid' => $trade['userid'],'name'=>$xnb))->setDec($rmbgg . 'd', $save_buy_rmb);
			}
			
			$rs[] = $mo->table('codono_trade_lever')->where(array('id' => $trade['id']))->setField('status', 2);
			$you_buy = $mo->table('codono_trade_lever')->where(array(
			    'market' => array('like', $rmb . '%'),
			    'status' => 0,
			    'userid' => $trade['userid']
			))->find();
			
			if (!$you_buy) {
			    $you_user_buy = $mo->table('codono_lever_coin')->where(array('userid' => $trade['userid'],'name'=>$xnb))->find();
			
				$you_user_buy[$rmb . 'd'] = $you_user_buy['p_yued'];
			
			    if (0 < $you_user_buy[$rmb . 'd']) {
			        $mo->table('codono_lever_coin')->where(array('userid' => $trade['userid'],'name'=>$xnb))->setField($rmbgg . 'd', 0);
			    }
			}
		}
		else if ($trade['type'] == 2) {
			$mun = format_num($trade['num'] - $trade['deal'], 8);
			$user_sell = $mo->table('codono_lever_coin')->where(array('userid' => $trade['userid'],'name'=>$xnb))->find();

			$user_sell[$xnb . 'd'] = $user_sell['yued'];
			
			if ($mun <= format_num($user_sell[$xnb . 'd'], 8)) {
				$save_sell_xnb = $mun;
			}
			else if ($mun <= format_num($user_sell[$xnb . 'd'], 8) + 1) {
				$save_sell_xnb = $user_sell[$xnb . 'd'];
			}
			else {
				$mo->execute('rollback');
				$mo->execute('commit');
				return array('0', 'Undo failed 2');
			}

			if (0 < $save_sell_xnb) {
				$rs[] = $mo->table('codono_lever_coin')->where(array('userid' => $trade['userid'],'name'=>$xnb))->setInc($xnbgg, $save_sell_xnb);
				$rs[] = $mo->table('codono_lever_coin')->where(array('userid' => $trade['userid'],'name'=>$xnb))->setDec($xnbgg . 'd', $save_sell_xnb);
			}

			$rs[] = $mo->table('codono_trade_lever')->where(array('id' => $trade['id']))->setField('status', 2);
			$you_sell = $mo->table('codono_trade_lever')->where(array(
				'market' => array('like', $xnb . '%'),
				'status' => 0,
				'userid' => $trade['userid']
				))->find();

			if (!$you_sell) {
				$you_user_sell = $mo->table('codono_lever_coin')->where(array('userid' => $trade['userid'],'name'=>$xnb))->find();
				$you_user_sell[$xnb . 'd'] = $you_user_sell['yued'];
				if (0 < $you_user_sell[$xnb . 'd']) {
					$mo->table('codono_lever_coin')->where(array('userid' => $trade['userid'],'name'=>$xnb))->setField($xnbgg . 'd', 0);
				}
			}
			
		}
		else {
			$mo->execute('rollback');
			return array('0', 'Undo failed 3');
		}

		if (check_arr($rs)) {
			
			$mo->execute('commit');
			
			
			$mo->execute('unlock tables');
			return array('1', 'Canceled successfully');
		}
		else {
			$mo->execute('rollback');
			$mo->execute('unlock tables');
			return array('0', 'Undo failed 4|' . implode('|', $rs));
		}
	}
}