<?php

namespace Api\Controller;

use Think\Exception;
use Think\Page;

class TransferController extends CommonController
{
    
    public const allowed_types = ALLOWED_TYPES ;  // type of account balances
    public const ADVANCE_TRANSFER_FEES=1;
    public function _initialize()
    {

        parent::_initialize();
        

    }

    private function userSpotbalance($userid)
    {
        if (!check($userid, 'd')) {
            $this->error(L('INCORRECT_REQ'));
        }
        $user_balance = M('UserCoin')->where(['userid' => $userid])->find();
		$list=[];
        $coins = C('coin');
        foreach ($coins as $coin) {
            if ($coin['symbol']) {
                continue;
            }

            
            $list[$coin['name']]['balance'] = $user_balance[$coin['name']];
            $list[$coin['name']]['freeze'] = $user_balance[$coin['name'] . 'd'];
            if ($list[$coin['name']]['balance'] <= 0) {
                unset($list[$coin['name']]);
            }
        }
        return $list;
    }

    /*
    Type =>1 P2P 2 Other2 3 Other3
    */
    
    private function userOtherbalances($userid, $type = 1, $coin = 'all')
    {

        if (!check($userid, 'd')) {
            $this->error(L('INCORRECT_REQ'));
        }
        if (!check($type, 'd')) {
            $this->error(L('INCORRECT_REQ'));
        }
        $bal_type=$this->getBalanceTypeKey($type);

        if($bal_type<0){
          return ['status'=>0,'error'=>'invalid wallet selected'];
        }
        $where = [];
        if ($coin != 'all') {
            if (!check($coin, 'n')) {
                $this->error('Incorrect coin');
            }
            $where['coin'] = strtolower($coin);
        }
        $where['uid'] = $userid;
        $where['account'] = $type;
        $user_balance = M('user_assets')->where($where)->select();
        $coins = C('coin_safe');
        $list=[];
        foreach ($user_balance as $ub) {
            $list[$ub['coin']] = $coins[$ub['coin']['name']];
            $list[$ub['coin']]['balance'] = $ub['balance'];
            $list[$ub['coin']]['freeze'] = $ub['freeze'];
            if ($list[$ub['coin']]['balance'] <= 0 || !array_key_exists($ub['coin'], $coins)) {
                unset($list[$ub['coin']]);
            }
        }
        return $list;
    }

    public function index()
    {
		
		$uid=$this->userid();
		
        $from = I('request.from', '', 'text');
        $to = I('request.to', '', 'text');
        $baltypes = array_keys($this->giveBalanceTypes());
        
        
        $check['from'] = $this->getBalanceTypeValue($from);
        $check['to'] = $this->getBalanceTypeValue($to);
        
        if ($check['from'] < 0 || $check['to'] < 0 || ($from != 'spot' && $to != 'spot') || ($from == 'spot' && $to == 'spot')) {

            $from = 'spot';
            $to = $baltypes[1];
			$this->index($from,$to);
        }
        
        $list=[];
        
        if ($from !== 'spot' && $to == 'spot') {
            $list = $this->userOtherbalances($uid, $check['from']);
        }

        if ($from == 'spot' && $to != 'spot') {
            $list = $this->userSpotbalance($uid);
        }
        
        $data['baltypes']=$baltypes;
        $data['to']=$to;
        $data['from']=$from;
        $data['coins']=$list;
      
		$this->ajaxShow($data);
    }

    public function doTransfer()
    {
        $uid=$userid=$this->userid();
      

        $from = strtolower(I('request.from', 'spot', 'text'));
        $to = strtolower(I('request.to', 'p2p', 'text'));
        $coin = strtolower(I('request.coin', 'USDT', 'string'));
        $amount = I('amount/f');
		$coin = $this->findSymbol($coin);
		
        if ($amount < 0 || !check($amount, 'decimal')) {
            $this->error('Incorrect Amount:' . $amount);
        }
        $baltypes = array_keys($this->giveBalanceTypes());
        
        if (!in_array($from, $baltypes) || !in_array($to, $baltypes) || $from == $to) {
            $this->error('Select Correct  accounts');
        }

        $isValidCoin = $this->isValidCoin($coin);
        if ($coin == null || !$isValidCoin) {
            $this->error('Invalid coin');
        }

		
        $fee_amount=0;
        $fees_coin=null;
        $coind = $coin . 'd';
        $mo = M();
        $before_spot_balance = $mo->table('codono_user_coin')->where(['userid' => $userid])->field([$coin, $coin . 'd'])->find();
			
        if ($from != 'spot' && $to == 'spot') {
            $temp=1;
            $temp_type=$this->getBalanceTypeValue($from);
            
            $list = $this->userOtherbalances($userid, $temp_type);
        }
        if ($from == 'spot' && $to != 'spot') {
            $temp=2;
            $list = $this->userSpotbalance($userid);
        }
        
        $before_user_balance = $list[$coin]['balance'];

        if ($before_user_balance < $amount) {
            $this->error('Insufficient balance'.$before_user_balance);
        }

        //Fees check if applicable
        //this section add advance rules configured in admin panel look at Admin/Fees area
        if(self::ADVANCE_TRANSFER_FEES==1){
            $wallet_verify=json_decode($this->verifyWalletFees($from,$to,$coin));
            if(!$wallet_verify->from_wallet->allowed){
                $this->error("$from wallet does not allow to send a transfer");
            }
            if(!$wallet_verify->to_wallet->allowed){
                $this->error("$to wallet does not allow to receive a transfer");
            }
            $fees_coin=$wallet_verify->from_wallet->fee_coin;
            $fees_percent=$wallet_verify->from_wallet->out_fee;

            if($fees_coin !=null || $fees_percent>0){
                $fee_amount=$this->calculatePercentage($amount,$fees_percent);
                $sufficient_bal=$this->checkIfSufficientSpotBalance($fees_coin,$fee_amount);

                if(!$sufficient_bal['status']){
                    $this->error("You need ".$sufficient_bal['coin']."  ".NumToStr($sufficient_bal['required'])." but have ".NumToStr($sufficient_bal['available'])." in spot balance");
                }
            }
        }
        
        $user_balance = $list[$coin];

        $rs = [];

        $mo->startTrans();
        try {
            if ($from == 'spot' && $to != 'spot') {

                $rs[] = $mo->table('codono_user_coin')->where(['userid' => $userid])->setDec($coin, $amount);
                //Check if user_assets table has following row or not userid=$userid and coin=$coin if not then first insert then update

                list($condition, $result, $condition_add) = $this->AssetEntryConfirm($userid, $coin, $to, $mo);
                if ($result['coin'] == $coin && $result['uid'] == $userid) {
                    //add balance
                    $rs[] = $mo->table('codono_user_assets')->where($condition)->setInc('balance', $amount);
                }

                $after_spot_balance = $mo->table('codono_user_coin')->where(['userid' => $userid])->field([$coin, $coin . 'd'])->find();
                $rs[] = $tid = $mo->table('codono_transfer')->add(['userid' => $userid, 'coin' => $coin, 'amount' => $amount, 'from_account' => $from, 'to_account' => $to, 'created_at' => time()]);
                $finance_hash = md5(ADMIN_KEY . 'transfer' . $userid . $tid . time());
                $rs[] = $mo->table('codono_finance')->add(['userid' => $uid, 'coinname' => $coin, 'num_a' => $before_spot_balance[$coin], 'num_b' => $before_spot_balance[$coind], 'num' => $before_spot_balance[$coin] + $before_spot_balance[$coind], 'fee' => $amount, 'type' => 2, 'name' => 'Fees/transfer', 'nameid' => $tid, 'remark' => 'Transfer', 'mum_a' => $after_spot_balance[$coin], 'mum_b' => $after_spot_balance[$coind], 'mum' => $after_spot_balance[$coin] + $after_spot_balance[$coind], 'move' => $finance_hash, 'addtime' => time(), 'status' => 1]);
            }
            if ($from != 'spot' && $to == 'spot') {

                $rs[] = $mo->table('codono_user_coin')->where(['userid' => $userid])->setInc($coin, $amount);
                //Check if user_assets table has following row or not userid=$userid and coin=$coin if not then first insert then update

                list($condition, $result, $condition_add) = $this->AssetEntryConfirm($userid, $coin, $from, $mo);

                $rs[] = $mo->table('codono_user_assets')->where($condition)->setDec('balance', $amount);

                $after_spot_balance = $mo->table('codono_user_coin')->where(['userid' => $userid])->field([$coin, $coind])->find();

                $rs[] = $tid = $mo->table('codono_transfer')->add(['userid' => $userid, 'amount' => $amount, 'coin' => $coin, 'from_account' => $from, 'to_account' => $to, 'created_at' => time()]);

                $finance_hash = md5(ADMIN_KEY . 'transfer' . $userid . $tid . time());
                $rs[] = $mo->table('codono_finance')->add(['userid' => $userid, 'coinname' => $coin, 'num_a' => $before_spot_balance[$coin], 'num_b' => $before_spot_balance[$coind], 'num' => $before_spot_balance[$coin] + $before_spot_balance[$coind], 'fee' => $amount, 'type' => 1, 'name' => 'Fees/transfer', 'nameid' => $tid, 'remark' => 'Transfer', 'mum_a' => $after_spot_balance[$coin], 'mum_b' => $after_spot_balance[$coind], 'mum' => $after_spot_balance[$coin] + $after_spot_balance[$coind], 'move' => $finance_hash, 'addtime' => time(), 'status' => 1]);
            }
            //do fee reduction as well
            if (self::ADVANCE_TRANSFER_FEES == 1 && $fee_amount > 0 && $fees_coin != null) {
                $rs[] = $mo->table('codono_user_coin')->where(['userid' => $userid])->setDec($fees_coin, $fee_amount);
                $rs[] = $mo->table('codono_wallet_income')->add(
                    [
                        'userid' => $userid,
                        'tid' => $tid,
                        'amount' => $fee_amount,
                        'coin' => $fees_coin,
                        'module' => 'transfer',
                        'addtime' => time(),
                        'status' => 1
                    ]
                );
                $last_sql = $mo->getLastSql();
            }
        }catch (Exception $exception){
            clog('transfer_dotransfer',$exception->getMessage());
            $mo->rollback();
            $this->error(L('There were issues transferring!'));
        }
        if (strict_check_arr($rs)) {
            $mo->commit();
            $this->success(L('Transfer completed!!'));
        } else {
            $mo->rollback();
            clog('fee_transfer',[$rs,$last_sql]);
            $this->error(L('There were issues transferring!'));
        }
    }

    public function history()
    {
        $uid=$userid=$this->userid();
        

        $where = ['userid' => $userid];

        $Model = M('Transfer');
        $count = $Model->where($where)->count();
        $Page = new Page($count, 20);

        $show = $Page->show();

        $transfers = $Model->table('codono_transfer')->where($where)->order("id desc")->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $data['transfers']=$transfers;
        $data['page']=$show;
        $this->ajaxShow($data);
    }

    private function findCardInfo($id)
    {
        $giftcard_images = $this->giftcard_images();
        foreach ($giftcard_images as $giftcard_image) {
            if ($id == $giftcard_image['id']) {
                return $giftcard_image;
            }
        }
        return false;
    }

    public function giftcard()
    {
        $uid=$userid=$this->userid();
     
        $spentcards = $mycards = [];
        $mo = M();
        $cards = $mo->table('codono_giftcard')->where(['owner_id' => $userid])->select();
        $giftcard_images = $this->giftcard_images();

        foreach ($cards as $card) {

            $card_info = $this->findCardInfo($card['card_img']);
            $card['card_img'] = $card_info['image'];
            $card['gc_title'] = $card_info['title'];
            $card['secret_code'] = cryptString($card['secret_code'], 'd');
            if ($card['status'] == 1) {
                $mycards[] = $card;
            }
            if ($card['status'] == 2) {
                $spentcards[] = $card;
            }
        }
        $data["spentcards"]=$spentcards;
        $data["mycards"]=$mycards;
        $this->ajaxShow($data);
    }

    /**
     *Interface to create cards
     */
    public function giftcardTemplate()
    {
        
        $uid=$userid=$this->userid();
        $user_balance = M('UserCoin')->where(['userid' => $userid])->find();
        $coins = C("coin_safe");
		
        foreach ($coins as $coin) {
            if($user_balance[$coin['name']]>0){
                $list[$coin['name']] = $coin;
                $list[$coin['name']]['balance'] = $user_balance[$coin['name']];
            }
        }
        
		$nonce = time();
        $giftcard_images = $this->giftcard_images();
        $data["nonce"]=$nonce;
        $data["giftcard_images"]= $giftcard_images;
        $data["coins"]= $list;
        
        $this->ajaxShow($data);
    }

    /**
     *query to check value of card value
     */
    public function check()
    {
        $uid=$userid=$this->userid();

        $public_code = I('request.public_code', null, 'string');
        if ($public_code == null) {
            $this->error('Invalid Giftcard');
        }
        $mo = M();
       // $secret_code = cryptString($secret);
        $card_details = $mo->table('codono_giftcard')->where(['public_code' => $public_code])->find();
        $cardid = $card_details['id'];
        if (!$card_details || $card_details['value'] <= 0 || !$card_details['id']) {
            $this->error("Invalid Gift card");
        }
        $coin = strtoupper($card_details['coin']);
        $coind = $coin . 'd';
        $amount = NumToStr($card_details['value']);
        $isValidCoin = $this->isValidCoin($coin);

        if (!$coin || !$isValidCoin) {
            $this->error('Invalid coin');
        }
        if ($card_details['status'] == 2) {
            $this->error('Card valued ' . $coin . ' ' . $amount . ' has been used on ' . addtime($card_details['usetime']));
        }
        $data['status']=$card_details['status'];
        $data['msg']='Card value is ' . $coin . ' ' . $amount;
        $data['coin']=$coin;
        $data['amount']=$amount;
        $this->ajaxReturn($data);
    }

    /**
     * redeem a card code via its cardcode parameter.
     * if its
     */

    public function redeem()
    {
        $uid=$userid=$this->userid();
        $public = I('request.public_code', null, 'string');
        $secret = I('request.secret_code', null, 'string');
        if ($secret == null) {
            $this->error('Invalid Giftcard');
        }
        $this->doredeemCard($public,$secret);
    }

    //This is authorized action
    private function doredeemCard($public,$secret)
    {
        
        $userid = $uid=$this->userid();
        $mo = M();
        $secret_code = cryptString($secret);
        $card_details = $mo->table('codono_giftcard')->where(['public_code' => $public,'secret_code' => $secret_code, 'status' => 1])->find();
        $cardid = $card_details['id'];
        if ($card_details['value'] <= 0 || !$card_details['id']) {
            $this->error("Invalid Gift card");
        }
        $coin = strtolower($card_details['coin']);
        $coind = $coin . 'd';
        $amount = $card_details['value'];
        $isValidCoin = $this->isValidCoin($coin);

        if ($coin == null || !$isValidCoin) {
            $this->error('Invalid coin');
        }

        $before_balance = $mo->table('codono_user_coin')->where(['userid' => $userid])->field([$coin, $coind])->find();

        $rs = [];


        $mo->startTrans();
        $rs[] = $mo->table('codono_user_coin')->where(['userid' => $uid])->setInc($coin, $amount);
        $rs[] = $gc_id = M('Giftcard')->where(['id' => $cardid])->save(['consumer_id' => $userid, 'status' => 2, 'usetime' => time()]);
        if (!$gc_id) {
            $mo->rollback();
            $this->error(L('There were issues redeeming giftcard!'));
        }
        $after_balance = $mo->table('codono_user_coin')->where(['userid' => $userid])->field([$coin, $coin . 'd'])->find();

        $finance_hash = md5(ADMIN_KEY . $uid . $cardid . time());
        $rs[] = $mo->table('codono_finance')->add(['userid' => $uid, 'coinname' => $coin, 'num_a' => $before_balance[$coin], 'num_b' => $before_balance[$coind], 'num' => $before_balance[$coin] + $before_balance[$coind], 'fee' => $amount, 'type' => 1, 'name' => 'giftcard', 'nameid' => $gc_id, 'remark' => 'GiftCard Redeem', 'mum_a' => $after_balance[$coin], 'mum_b' => $after_balance[$coind], 'mum' => $after_balance[$coin] + $after_balance[$coin], 'move' => $finance_hash, 'addtime' => time(), 'status' => 1]);
        if (strict_check_arr($rs)) {
            $mo->commit();

            $this->success(L('Giftcard Redeemed!!'));
        } else {
            $mo->rollback();
            $this->error(L('There were issues redeeming giftcard!'));
        }

    }

    public function giftcardcreate()
    {
        $uid=$userid=$this->userid();
        $banner_img = '';
        
        $coin = strtolower(I('request.coin', 'usdt', 'string'));
        $amount = I('request.amount', 0.00, 'float');
        $nonce = I('request.nonce', 0.00, 'float');
        $bannerid = I('request.bannerid', 0.00, 'float');
        $giftcard_images = $this->giftcard_images();
        foreach ($giftcard_images as $gcimg) {

            if ($gcimg['id'] == $bannerid) {
                $banner_img = $gcimg['image'];
            }
        }

        if (!$banner_img) {
            $banner_img = end($giftcard_images)['card_img'];
        }

        $isValidCoin = $this->isValidCoin($coin);

        if ($coin == null || !$isValidCoin) {
            $this->error('Invalid coin');
        }
        if ($amount == null || $amount <= 0) {
            $this->error('Invalid amount' . $amount);
        }
        $coind = $coin . 'd';

        $this->isUniqueBuy($nonce);

        $mo = M();

        $before_balance = $mo->table('codono_user_coin')->where(['userid' => $uid])->field([$coin, $coin . 'd'])->find();
        if ($before_balance[$coin] < $amount) {
            $this->error('Insufficient balance');
        }
        $rs = [];
        $mo->startTrans();

        $rs[] = $mo->table('codono_user_coin')->where(['userid' => $uid])->setDec($coin, $amount);
        $secret_code = cryptString(cardGenSecret());
        $public_code = cardGenPublic($uid);
        $gc_entry = ['owner_id' => $uid, 'coin' => $coin, 'card_img' => $bannerid, 'public_code' => $public_code, 'secret_code' => $secret_code, 'value' => $amount, 'nonce' => $nonce, 'addtime' => time(), 'status' => '1'];

        $rs[] = $gc_id = $mo->table('codono_giftcard')->add($gc_entry);
        if (!$gc_id) {
            $mo->rollback();
            $this->error(L('There were issues buying giftcard!'));
        }
        $after_balance = $mo->table('codono_user_coin')->where(['userid' => $uid])->field([$coin, $coin . 'd'])->find();

        $finance_hash = md5(ADMIN_KEY . $uid . $gc_id . time());
        $rs[] = $mo->table('codono_finance')->add(['userid' => $uid, 'coinname' => $coin, 'num_a' => $before_balance[$coin], 'num_b' => $before_balance[$coind], 'num' => $before_balance[$coin] + $before_balance[$coind], 'fee' => $amount, 'type' => 2, 'name' => 'giftcard', 'nameid' => $gc_id, 'remark' => 'GiftCard Buy', 'mum_a' => $after_balance[$coin], 'mum_b' => $after_balance[$coind], 'mum' => $after_balance[$coin] + $after_balance[$coin], 'move' => $finance_hash, 'addtime' => time(), 'status' => 1]);
        if (check_arr($rs)) {
            $mo->commit();

            $this->success(L('Giftcard purchased!!'));
        } else {
            $mo->rollback();
            $this->error(L('There were issues buying giftcard!'));
        }
    }

    private function isValidCoin($coin)
    {
        $coins = C('Coin');

        if (array_key_exists(strtolower($coin), $coins)) {
            return true;
        } else {
            return false;
        }
    }

    private function isUniqueBuy($nonce)
    {
        $userid = $uid=$this->userid();
        if (!check($nonce, 'd')) {
            $this->error(L('INCORRECT_REQ'));
        }
        $mo = M();
        $if_exists = $mo->table('codono_giftcard')->where(['owner_id' => $userid, 'nonce' => $nonce])->find();
        if ($if_exists) {
            $this->error("Already made a purchase, Or refresh the page");
        }
    }

    private function giftcard_images()
    {
        $data = (APP_DEBUG ? null : S('giftcard_images'));
        if (!$data) {
            $data = M('GiftcardImages')->where(['status' => 1])->order("id desc")->limit(100)->select();
        }
        return $data;
    }

    /**
     * @param $userid
     * @param string $coin
     * @param $mo
     * @return array
     */
    private function AssetEntryConfirm($userid, string $coin,$type, $mo): array
    {
        $account=$this->getBalanceTypeValue($type);
        if($account <0 ){
            $this->error('Invalid asset type');
        }

        $condition = ['account' => $account, 'uid' => $userid, 'coin' => $coin];
        $result = $mo->table('codono_user_assets')->where($condition)->find();
        $condition_add = [];
        if ($result == 0 || $result == null) {
            $condition_add = $condition;
            $condition_add['created_at'] = time();
            $mo->table('codono_user_assets')->add($condition_add);
            $result = $mo->table('codono_user_assets')->where($condition)->find();
        }
        if ($result == 0 || $result == null) {
            $mo->rollback();
            $this->error(L('There were some issues moving funds to p2p!'));
        }
        return [$condition, $result, $condition_add];
    }

    private function getBalanceTypeValue($key)
    {
        // Check if the key exists in the array
        $allowed_types = $this->giveBalanceTypes();

        if (array_key_exists(strtolower($key), $allowed_types)) {
            return $allowed_types[$key];
        } else {
            return -1;
        }
    }
    private function getBalanceTypeKey($val)
    {
        // Check if the key exists in the array
        $allowed_types = $this->giveBalanceTypes();
        $found=array_search (($val), $allowed_types);
        if ($val && $found) {
            return $found;
        } else {
            return -1;
        }
    }

    private function giveBalanceTypes()
    {
        $types = self::allowed_types;
        
        if (MARGIN_ALLOWED != 1) {
            unset($types['margin']);
        }
        if (P2P_ALLOWED != 1) {
            unset($types['p2p']);
        }
        return $types;
    }

public function tester(){
        $info=$this->verifyWalletFees('p2px','spot','ltc');
        var_dump($info);
}
private function verifyWalletFees($from_wallet,$to_wallet,$coin) {
    

    // Initialize the response array
    $response = [
        'from_wallet' => [
            'allowed' => false,
            'out_fee' => 0,
            'fee_coin' => null,
        ],
        'to_wallet' => [
            'allowed' => false,
        ],
    ];

    // Verify from_wallet
    $fromConfig = M('WalletFees')->where(['name' => $from_wallet])->find();

    if (!empty($fromConfig) && $fromConfig['wallet_enable']) {
        $out_let=$fromConfig['out_let']==1?true:false;

        if($fromConfig['out_fee']==0){
            $response['from_wallet']['allowed'] = $out_let;
            $response['from_wallet']['out_fee'] = 0;
            $response['from_wallet']['fee_coin'] = null;
        }else{
            $feeInfo = M('WalletCoin')->where(['name' => $from_wallet, 'coin' => $coin])->find();
            if ($feeInfo) {
                $response['from_wallet']['allowed'] = $out_let;
                $response['from_wallet']['out_fee'] = $feeInfo['out_fee']?:0;
                $response['from_wallet']['fee_coin'] = $feeInfo['fee_coin']?:null;
            }else{
                $response['from_wallet']['allowed'] = $out_let;
                $response['from_wallet']['out_fee'] = 0;
                $response['from_wallet']['fee_coin'] = null;
            }
        }
        // Find out_fee and fee_coin from codono_wallet_coin for the specified coin
        
    }

    // Verify to_wallet
    $toConfig = M('WalletFees')->where(['name' => $to_wallet, 'wallet_enable' => 1, 'in_let' => 1])->find();
    
    if (is_array($toConfig)) {
        $response['to_wallet']['allowed'] = true;
    }

   return json_encode($response); // Return the response as JSON
}
public function testme(){
    $info=$this->checkIfSufficientSpotBalance('btc','2.22');
    var_dump($info);
}
private function checkIfSufficientSpotBalance($coin,$amount){
    $userid=$uid=$this->userid();
    $bal = M()->table('codono_user_coin')->where(['userid' => $userid])->field([$coin])->find();
    
    $resp=[
        'coin'=>$coin,
        'available'=>$bal[$coin],
        'required'=>$amount,
        'status'=>$bal[$coin]>=$amount?true:false
    ];
        return $resp;
}
    /**
     * Calculate the percentage of an amount using bcmath.
     *
     * @param string|float|int $amount The amount from which to calculate the percentage.
     * @param string|float|int $percent The percentage to calculate.
     * @param int $scale The number of decimal digits to be used for the result.
     * @return string The result of the percentage calculation.
     */
    private function calculatePercentage($amount, $percent, $scale = 8) {
        // Convert the percentage into a decimal (e.g., 25% becomes 0.25)
        $decimalPercent = bcdiv($percent, '100', $scale +2); // Adding extra precision for intermediate calculation

        // Calculate the percentage of the amount
        $result = bcmul($amount, $decimalPercent, $scale);

        return $result;
    }
	
    public function findSymbol($coin): ?string
    {
        $coin = strtolower($coin);
        $coininfo = C('coin')[$coin];
        if (!is_array($coininfo)) {
            return null;
        }
        $symbol = strtolower($coininfo['symbol']);

        if ($symbol == null) {
            return $coin;
        } else {
            return $symbol;
        }
    }
}