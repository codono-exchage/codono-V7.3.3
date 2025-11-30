<?php

namespace Common\Design\Common;

class ModelHandle
{
	/**
     * Update the value of a field based on a condition.
     *
     * @param [string] $tableName Table Name
     * @param [array]  $where     [where condition]
     * @param [string] $field     [field name]
     * @param [float]  $num       [Numerical value]
     * @param bool     $self      [self-increment or self-decrease]
     */
    public static function UpdateBalance($userid, $coin, $amount, $type )
    {
		//Query user assets before update
		if($type!=1 && $type!=2){
			return false;
		}
		$where['userid']=(int)$userid;
		$field=(string)$coin;
		$amount=(float)$amount;
        //auto increment
        if ($type == 1) {
            return M('UserCoin')->where($where)->setInc($field, $amount);
        } else {
            //Decrement
            return M('UserCoin')->where($where)->setDec($field, $amount);
        }
    }
    /**
     * Update the value of a field based on a condition.
     *
     * @param [string] $tableName Table Name
     * @param [array]  $where     [where condition]
     * @param [string] $field     [field name]
     * @param [float]  $num       [Numerical value]
     * @param bool     $self      [self-increment or self-decrease]
     */
    public static function UpdateFieldNum($tableName, $where, $field, $num, $self = true)
    {
        //auto increment
        if ($self == true) {
            return M($tableName)->where($where)->setInc($field, $num);
        } else {
            //Decrement
            return M($tableName)->where($where)->setDec($field, $num);
        }
    }

    /**
     * Update table data based on ID.
     *
     * @param [type] $tableName [description]
     * @param [type] $id        [description]
     * @param array  $data      [description]
     */
    public static function UpdateDataByWhere($tableName, $where, $data = array())
    {
        return M($tableName)->where($where)->save($data);
    }

    /**
     * Add data to the model.
     *
     * @param [type] $tableName [description]
     * @param array  $data      [description]
     */
    public static function AddDataToTable($tableName, $data = array())
    {
        return M($tableName)->add($data);
    }

    /**
     * Notice: add a trade_log
     * @param $buyid
     * @param $sellid
     * @param $market
     * @param $price
     * @param $num
     * @param $type
     * @return mixed
     */
    public static function AddTradeLog($buyid, $sellid, $market, $price, $num, $type)
    {
        $data = array();
        $data['userid'] = $buyid;  //buyer id
        $data['peerid'] = $sellid; //seller id
        $data['market'] = $market; //market
        $data['price'] = $price; //price
        $data['num'] = $num; //amount
        $data['mum'] = bcmul($price, $num, 8); //total
        $data['type'] = $type; //trade type 1=buy 2=sell
        $data['fee_buy'] = 0;  //Buying fee
        $data['fee_sell'] = 0; //selling fee
        $data['addtime'] = time(); //add time
        $data['status'] = 1; //state
        $data['sort'] = 0;
        $data['endtime'] = time();
        return M('TradeLog')->add($data);
    }

    /**
     * Add a financial record.
     *
     * @param [type] $remark   [remark]
     * @param [type] $userid   [User ID]
     * @param [type] $coinname [coin name]
     * @param [type] $num      [amount]
     * @param bool   $type      [income or expense]
     */
    public static function AddFinance($userid, $coinname, $num,$type,$name="",$nameid=0, $remark)
    {
        //Query user assets before update
		if($type!=1 && $type!=2){
			return false;
		}
        $property = M('UserCoin')->where(array('userid' => $userid))->find();
        $data = array();
        $data['num_a'] = $property[$coinname];
        $data['num_b'] = $property[$coinname.'d'];
        $data['num'] = $property[$coinname] + $property[$coinname.'d'];
        $data['fee'] = $num;
        //income
        if ($type==1) {
            $data['type'] = 1;
            $data['remark'] = $remark;
            $data['mum_a'] = bcadd($property[$coinname], $num, 8);
            $data['mum_b'] = $property[$coinname.'d'];
            $data['mum'] = bcadd($data['num'], $num, 8);
        } else { //expenditure
            $data['type'] = 2;
            $data['remark'] = $remark;
            $data['mum_a'] = $property[$coinname];
            $data['mum_b'] = bcsub($property[$coinname.'d'], $num, 8);
            $data['mum'] = bcsub($data['num'], $num, 8);
        }
        $data['userid'] = $userid;
        $data['coinname'] = $coinname;
        $data['name'] = $name;
        $data['nameid'] = $nameid;
		$hash=md5($userid.$coinname.$num.$type.$remark.time());
        $data['move'] = $hash;
        $data['addtime'] = time();
        $data['status'] = 1;

        return M('Finance')->add($data);
    }

    /**
     * Update market price
     *
     * @param [type] $market [description]
     */
    public static function UpdateMarketPrice($market)
    {
        //Update currency price
        $new_price = round(M('TradeLog')->where(array('market' => $market))->order('id desc')->getField('price'), 6);
        $buy_price = round(M('Trade')->where(array('type' => 1, 'market' => $market, 'status' => 0))->max('price'), 6);
        $sell_price = round(M('Trade')->where(array('type' => 2, 'market' => $market, 'status' => 0))->min('price'), 6);
        $min_price = round(M('TradeLog')->where(array(
            'market' => $market,
            'addtime' => array('gt', time() - (60 * 60 * 24)),
        ))->min('price'), 6);
        $max_price = round(M('TradeLog')->where(array(
            'market' => $market,
            'addtime' => array('gt', time() - (60 * 60 * 24)),
        ))->max('price'), 6);
        /*$volume = round(M('TradeLog')->where(array(
            'market' => $market,
            'addtime' => array('gt', time() - (60 * 60 * 24)),
        ))->sum('num'), 6);*/

        $sta_time = time() - (60 * 60 * 24);
        $sta_price = round(M('TradeLog')->where(array(
            'market' => $market,
            'addtime' => array('gt', $sta_time),
        ))->order('id asc')->getField('price'), 6);

        $Cmarket = M('Market')->where(array('name' => $market))->find();

        if ($Cmarket['new_price'] != $new_price) {
            $upCoinData['new_price'] = $new_price;
        }

        if ($Cmarket['buy_price'] != $buy_price) {
            if ($buy_price > 0) {
                $upCoinData['buy_price'] = $buy_price;
            }
        }

        if ($Cmarket['sell_price'] != $sell_price) {
            if ($sell_price > 0) {
                $upCoinData['sell_price'] = $sell_price;
            }
        }

        if ($Cmarket['min_price'] != $min_price) {
            $upCoinData['min_price'] = $min_price;
        }

        if ($Cmarket['max_price'] != $max_price) {
            $upCoinData['max_price'] = $max_price;
        }

        /*if ($Cmarket['volume'] != $volume) {
            $upCoinData['volume'] = $volume;
        }*/

        $change = round((($new_price - $Cmarket['hou_price']) / $Cmarket['hou_price']) * 100, 2);
        $upCoinData['change'] = $change;

        if ($upCoinData) {
            S('home_market', null);
            return M('Market')->where(array('name' => $market))->save($upCoinData);
        }

        return false;
    }
}