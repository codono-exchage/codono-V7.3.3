<?php

namespace Admin\Controller;

use Think\Page;

class TradeController extends AdminController
{
    public function index($field = NULL, $name = NULL, $market = NULL, $status = NULL, $type = 0)
    {
        //$this->checkUpdata();
        $where = [];
		$where['userid']= ['neq',0];
        $where = $this->sub_filter($field, $name, $where);

        $this->sub_index_filter($market, $where, $status, $type);
        $this->display();
    }
    public function special($field = NULL, $name = NULL, $market = NULL, $status = NULL, $type = 0)
    {
        //$this->checkUpdata();
        $where = [];
		$where['userid']= ['neq',0];
        $where = $this->sub_filter($field, $name, $where);

        $this->sub_index_filter($market, $where, '0', $type,true);
        $this->display();
    }
	public function stoporders ($field = NULL, $name = NULL, $market = NULL, $status = NULL, $type = 0)
	{
		        $this->title='Stop Orders';
        $where = [];
		$where['userid']= ['neq',0];
        $where = $this->sub_filter($field, $name, $where);

         if ($market) {
            $where['market'] = $market;
        }

        if ($status) {
            $where['status'] = $status;
        }

        if ($status == 0 && $status != null) {
            $where['status'] = 0;
        }
        if ($type == 1 || $type == 2) {
            $where['type'] = $type;
        }


        $count = M('Stop')->where($where)->count();

        $codono_getSum = M('Stop')->where($where)->sum('mum');

        $Page = new Page($count, 15);
        $show = $Page->show();
        $list = M('Stop')->where($where)->order('id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();

        foreach ($list as $k => $v) {
            $list[$k]['username'] = M('User')->where(['id' => $v['userid']])->getField('username');
        }
        $this->assign('list', $list);
        $this->assign('codono_count', $count);
        $this->assign('codono_getSum', $codono_getSum);
        $this->assign('page', $show);

        $this->display();
	}
	public function liqindex($field = NULL, $name = NULL, $market = NULL, $status = NULL, $type = 0)
    {
        //$this->checkUpdata();
        $where = [];

        $where = $this->sub_filter($field, $name, $where);
		$where['userid']= ['eq',0];
        $this->sub_index_filter($market, $where, $status, $type);
        $this->display('index');
    }
    public function fill($id = NULL)
    {
        if (!check($id, 'd')) {
            $this->error(L('Invalid trade id'));
        }
        $rs=M()->execute("UPDATE `codono_trade_log` SET  `fill` =  1 WHERE id = '$id' ");

        if ($rs) {
            $this->success('Marked as filled');
        } else {
            $this->error('Could not mark as filled');
        }
    }
	
	public function stopreject($id = NULL)
    {
        $rs = D('Trade')->adminstopreject($id);

        if ($rs[0]) {
            $this->success($rs[1]);
        } else {
            $this->error($rs[1]);
        }
    }

    public function forceStopToLimit($stopid=null,$match=0){
        $mo=M();
        $stopinfo = $mo->table('codono_stop')->where(['id' => (int)$stopid,'status'=>0])->find();

        $market = $stopinfo['market'];
        $price = $stopinfo['price'];
        $num = $stopinfo['num'];
        $type = $stopinfo['type'];
        $userid = $stopinfo['userid'];
        $mum=$stopinfo['mum'];
        $fee=$stopinfo['fee'];
        if($stopid != $stopinfo['id']){
            $this->error('No such stop order found'.$stopinfo['id']);
        }
        if ($type == 1) {

            $mo->table('codono_trade')->add(['userid' => $userid, 'market' => $market, 'price' => $price, 'num' => $num, 'mum' => $stopinfo['mum'], 'fee' =>  $stopinfo['fee'], 'type' => 1, 'addtime' => time(), 'status' => 0]);
            $stop_update = M('Stop')->where(['id' => $stopid])->save(['status' => 1]);


        } else if ($type == 2) {

            $mo->table('codono_trade')->add(['userid' => $userid, 'market' => $market, 'price' => $price, 'num' => $num, 'mum' => $mum, 'fee' => $fee, 'type' => 2, 'addtime' => time(), 'status' => 0]);
            $stop_update = M('Stop')->where(['id' => $stopid])->save(['status' => 1]);

        }else{
                return $this->error(L('Transaction type error'));
        }
        if($stop_update){
            if($match==1){
                $tc = new \Home\Controller\TradeController();

                exec($tc->matchingTrade($market));
            }
            return $this->success(L('Trading success!'));
        }
    }
    public function reject($id = NULL)
    {
        $rs = D('Trade')->adminreject($id);

        if ($rs[0]) {
            $this->success($rs[1]);
        } else {
            $this->error($rs[1]);
        }
    }
	public function forcematch($id = NULL,$price=null)
    {
        if($price>0){
            $special_price=$price;
            $rs = D('Trade')->forceMatchSpecial($id,$special_price);
        }else{
            $rs = D('Trade')->forcematch($id);
        }
        

        if ($rs[0]) {
            $this->success($rs[1]);
        } else {
            $this->error($rs[1]);
        }
    }

    public function log($field = NULL, $name = NULL, $market = NULL, $type = NULL)
    {
        $where = [];
		$where['userid']= ['neq', 0];
		$where['peerid']= ['neq', 0];
        if ($field && $name) {
            if ($field == 'username') {
                $where['userid'] = M('User')->where(['username' => $name])->getField('id');
            } else if ($field == 'peername') {
                $where['peerid'] = M('User')->where(['username' => $name])->getField('id');
            } else {
                $where[$field] = $name;
            }
        }
		

        if ($type == 1 || $type == 2) {
            $where['type'] = $type;
        }


        if ($market) {
            $where['market'] = $market;
        }

        $count = M('TradeLog')->where($where)->count();
        $codono_getSum = M('TradeLog')->where($where)->sum('mum');
        $Page = new Page($count, 15);
        $show = $Page->show();
        $list = M('TradeLog')->where($where)->order('id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();

        foreach ($list as $k => $v) {
            $list[$k]['username'] = M('User')->where(['id' => $v['userid']])->getField('username');
            $list[$k]['peername'] = M('User')->where(['id' => $v['peerid']])->getField('username');
        }


        $this->assign('codono_count', $count);
        $this->assign('codono_getSum', $codono_getSum);

        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->display();
    }
	
		public function liqlog($field = NULL, $name = NULL, $market = NULL, $type = NULL, $fill = NULL)
{
    $where = array();
    $UserModel = M('User');

    if ($field && $name) {
        if ($field == 'username') {
            $where['userid'] = $UserModel->where(array('username' => $name))->getField('id');
        } else if ($field == 'peername') {
            $where['peerid'] = $UserModel->where(array('username' => $name))->getField('id');
        } else {
            $where[$field] = $name;
        }
    }

    if ($fill !== null && ($fill == 0 || $fill == 1)) {
        $where['fill'] = $fill;
    }

    if ($type == 1 || $type == 2) {
        $where['type'] = $type;
    }

    if ($market) {
        $where['market'] = $market;
    }

    // Exclude trades where both userid and peerid are 0
 //   $where['_string'] = 'NOT (userid = 0 AND peerid = 0)';
      $where['_string'] = '((userid = 0 AND peerid != 0) OR (userid != 0 AND peerid = 0))';


    $TradeLogModel = M('TradeLog');
    $count = $TradeLogModel->where($where)->count();
	
	//var_dump(M()->getLastSql());exit;
    $codono_getSum = $TradeLogModel->where($where)->sum('mum');
    $Page = new \Think\Page($count, 15); // Adjusted to 15 to match initial setup; change if needed
    $show = $Page->show();
    $list = $TradeLogModel->where($where)->order('id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();

    foreach ($list as $k => &$v) {
        $marketParts = explode('_', $v['market']);
        $v['trade_coin'] = $marketParts[0];
        $v['base_coin'] = isset($marketParts[1]) ? $marketParts[1] : ''; // Ensure index exists
    }
    unset($v); // Break reference link

    session('liqrecords', $list);

    $this->assign('codono_count', $count);
    $this->assign('codono_getSum', $codono_getSum);
    $this->assign('list', $list);
    $this->assign('page', $show);
    $this->display();
}

	
	
	public function download_liqlog(){
		
		$list=session('liqrecords');
		
		$xlsName = 'LiqRecord';
        $xls = [];

        $xls[0][0] = "id";
		$xls[1][0] = "userid";
        $xls[2][0] = "peerid";
        $xls[3][0] = "market";
        $xls[4][0] = "price";
        $xls[5][0] = "num";
        $xls[6][0] = "mum";
        $xls[7][0] = "fee_buy";
        $xls[8][0] = "fee_sell";
        $xls[9][0] = "type";
        $xls[10][0] = "addtime";
		$xls[11][0] = "fill";
        
		$xls[0][2] = "id";
		$xls[1][2] = "userid";
        $xls[2][2] = "peerid";
        $xls[3][2] = "market";
        $xls[4][2] = "price";
        $xls[5][2] = "num";
        $xls[6][2] = "mum";
        $xls[7][2] = "fee_buy";
        $xls[8][2] = "fee_sell";
        $xls[9][2] = "type";
        $xls[10][2] = "addtime";
		$xls[11][2] = "fill";
        $this->exportExcel($xlsName, $xls, $list);
	}

	public function export_liqlog($field = NULL, $name = NULL, $market = NULL, $type = NULL,$fill=0)
    {
        $where = [];
		$where['fill']=0;
        if ($field && $name) {
            if ($field == 'username') {
                $where['userid'] = M('User')->where(['username' => $name])->getField('id');
            } else if ($field == 'peername') {
                $where['peerid'] = M('User')->where(['username' => $name])->getField('id');
            }else if ($field == 'fill') {
                $where['fill'] = M('User')->where(['fill' => $fill])->getField('id');
            }  else {
                $where[$field] = $name;
            }
        }
        if ($type == 1 || $type == 2) {
            $where['type'] = $type;
        }


        if ($market) {
            $where['market'] = $market;
        }
		

        $count = M('TradeLog')->where($where)->count();
        $codono_getSum = M('TradeLog')->where($where)->sum('mum');
 
        $list = M('TradeLog')->where($where)->order('id desc')->select();
		
        foreach ($list as $k => $v) {
			if($v['userid']==0 || $v['peerid']==0)
            $list[$k]['username'] = M('User')->where(['id' => $v['userid']])->getField('username');
            $list[$k]['peername'] = M('User')->where(['id' => $v['peerid']])->getField('username');
        }

        
        $xlsName = 'LiqRecord';
        $xls = [];

        $xls[0][0] = "id";
		$xls[1][0] = "userid";
        $xls[2][0] = "peerid";
        $xls[3][0] = "market";
        $xls[4][0] = "price";
        $xls[4][2] = "num";
        $xls[5][0] = "mum";
        $xls[6][0] = "fee_buy";
        $xls[7][0] = "fee_sell";
        $xls[8][0] = "type";
        $xls[9][0] = "addtime";
		$xls[11][0] = "fill";
        
		$xls[0][2] = "id";
		$xls[1][2] = "userid";
        $xls[2][2] = "peerid";
        $xls[3][2] = "market";
        $xls[4][2] = "price";
        $xls[4][2] = "num";
        $xls[5][2] = "mum";
        $xls[6][2] = "fee_buy";
        $xls[7][2] = "fee_sell";
        $xls[8][2] = "type";
        $xls[9][2] = "addtime";
		$xls[11][2] = "fill";
        $this->exportExcel($xlsName, $xls, $list);
    }
		


    public function chat($field = NULL, $name = NULL)
    {
        $where = [];

        if ($field && $name) {
            if ($field == 'username') {
                $where['userid'] = M('User')->where(['username' => $name])->getField('id');
            } else {
                $where[$field] = $name;
            }
        }

        $count = M('Chat')->where($where)->count();
        $Page = new Page($count, 15);
        $show = $Page->show();
        $list = M('Chat')->where($where)->order('id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();

        foreach ($list as $k => $v) {
            $list[$k]['username'] = M('User')->where(['id' => $v['userid']])->getField('username');
        }

        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->display();
    }

    public function chatStatus($id = NULL, $type = NULL, $model = 'Chat')
    {
        A('User')->sub_status($id,$type,$model);
    }

    public function comment($field = NULL, $name = NULL, $coinname = NULL)
    {
        $where = [];

        if ($field && $name) {
            if ($field == 'username') {
                $where['userid'] = M('User')->where(['username' => $name])->getField('id');
            } else {
                $where[$field] = $name;
            }
        }

        if ($coinname) {
            $where['coinname'] = $coinname;
        }

        $count = M('CoinComment')->where($where)->count();
        $Page = new Page($count, 15);
        $show = $Page->show();
        $list = M('CoinComment')->where($where)->order('id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();

        foreach ($list as $k => $v) {
            $list[$k]['username'] = M('User')->where(['id' => $v['userid']])->getField('username');
        }

        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->display();
    }

    public function commentStatus($id = NULL, $type = NULL, $model = 'CoinComment')
    {
        A('User')->sub_status($id,$type,$model);
    }

    public function market($field = NULL, $name = NULL)
    {
        $where = [];

        if ($field && $name) {
            if ($field == 'username') {
                $where['userid'] = M('User')->where(['username' => $name])->getField('id');
            } else {
                $where[$field] = $name;
            }
        }

        $count = M('Market')->where($where)->count();
        $Page = new Page($count, 15);
        $show = $Page->show();
        $list = M('Market')->where($where)->order('id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();

        foreach ($list as $k => $v) {
            if ($v['begintrade']) {
                $begintrade_codono_var = substr($v['begintrade'], 0, 5);
            } else {
                $begintrade_codono_var = "00:00";
            }
            if ($v['endtrade']) {
                $endtrade_codono_var = substr($v['endtrade'], 0, 5);
            } else {
                $endtrade_codono_var = "23:59";
            }


            $list[$k]['tradetimecodono'] = $begintrade_codono_var . "-" . $endtrade_codono_var;
        }

        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->display();
    }

    public function marketEdit($id = NULL)
    {


        $codono_getCoreConfig = codono_getCoreConfig();
        if (!$codono_getCoreConfig) {
            $this->error('Incorrect Core Config');
        }


        if (empty($_POST)) {
            if (empty($id)) {
                $this->data = [];

                $beginshi = "00";
                $beginfen = "00";
                $endshi = "23";
                $endfen = "59";
							$data= [];
			$data['api_min']=0.00;
			$data['api_max']=0.00;
			$data['api_max_qty']=0.00;
			$data['new_price']=0.00;	
			$data['buy_price']=0.00;	
			$data['sell_price']=0.00;	
			$data['min_price']=0.00;	
			$data['max_price']=0.00;	
			$data['volume']=0.00;	
			$data['change']=0.00;

			$this->assign('data',$data);
            } else {
                $market_codono = M('Market')->where(['id' => $id])->find();
                $this->data = $market_codono;

                if ($market_codono['begintrade']) {
                    $beginshi = explode(":", $market_codono['begintrade'])[0];
                    $beginfen = explode(":", $market_codono['begintrade'])[1];
                } else {
                    $beginshi = "00";
                    $beginfen = "00";
                }

                if ($market_codono['endtrade']) {
                    $endshi = explode(":", $market_codono['endtrade'])[0];
                    $endfen = explode(":", $market_codono['endtrade'])[1];
                } else {
                    $endshi = "23";
                    $endfen = "59";
                }

            }
            $coin_list=[];
            foreach(C('coin') as $coin_once){
                if($coin_once['symbol']=='' || $coin_once['symbol']==null){
                    $coin_list[]['name']=$coin_once['name'];
                }
            }
            usort($coin_list, function ($item1, $item2) {
                return $item1['name'] <=> $item2['name'];
            });


            $this->assign('coin_list', $coin_list);
            $this->assign('codono_getCoreConfig', $codono_getCoreConfig['codono_indexcat']);
            $this->assign('beginshi', $beginshi);
            $this->assign('beginfen', $beginfen);
            $this->assign('endshi', $endshi);
            $this->assign('endfen', $endfen);
            $this->display();
        } else {
            if (APP_DEMO) {
                $this->error(L('SYSTEM_IN_DEMO_MODE'));
            }

            $round = [0, 1, 2, 3, 4, 5, 6,7,8,9,10];

            if (!in_array($_POST['round'], $round)) {
                $this->error('Decimal format error!');
            }
            $validationErrors = $this->validateMarketData($_POST);
            if (!empty($validationErrors)) {
                foreach ($validationErrors as $error) {
                    $this->error($error);
                }
                
            }
			$save_data=$_POST;
			$save_data['orderbook_markup']=$_POST['orderbook_markup']?:0.00;
            if (isset($_POST['id'])) {
                $rs = M('Market')->save($save_data);
            } else {
                $save_data['name'] = $_POST['sellname'] . '_' . $_POST['buyname'];
                unset($save_data['buyname']);
                unset($save_data['sellname']);

                if (M('Market')->where(['name' => $save_data['name']])->find()) {
                    $this->error('Market exists!');
                }
                $rs = M('Market')->add($save_data);
            }

            if ($rs) {
                $this->success(L('SUCCESSFULLY_DONE'));
            } else {

                $this->error(L('OPERATION_FAILED'));
            }
        }
    }
    private function validateMarketData($data) {
        $errors = [];
        
        // Validate integer fields
        $integerFields = ['ownerid', 'trade', 'sort', 'addtime', 'endtime'];
        foreach ($integerFields as $field) {
            if (isset($data[$field]) && (!is_numeric($data[$field]) || intval($data[$field]) < 0)) {
                $errors[] = "Invalid value for $field. Must be a non-negative integer.";
            }
        }
        
        // Validate decimal fields
        $decimalFields = ['fee_buy', 'fee_sell', 'buy_min', 'sell_price', 'volume'];
        foreach ($decimalFields as $field) {
            if (isset($data[$field]) && !$this->is_valid_decimal($data[$field], 20, 8)) {
                $errors[] = "Invalid value for $field. Must be a decimal with up to 20 digits and 8 decimal places.";
            }
        }
        
        // Validate 'change' field separately to allow for negative values
        if (isset($data['change']) && !$this->is_valid_decimal($data['change'], 20, 8, true)) {
            $errors[] = "Invalid value for change. Must be a decimal with up to 20 digits and 8 decimal places, and can be negative.";
        }
    
        // Validate varchar fields
        if (isset($data['name']) && strlen($data['name']) > 25) {
            $errors[] = "Name is too long. Maximum 25 characters allowed.";
        }
        
        // Add more validation as needed for other fields...
        
        return $errors;
    }
    
    private function is_valid_decimal($value, $totalDigits, $decimalPlaces, $allowNegative = false) {
        $signPattern = $allowNegative ? '-?' : '';
        if (!preg_match("/^{$signPattern}\d{1,".($totalDigits - $decimalPlaces)."}(\.\d{0,$decimalPlaces})?$/", $value)) {
            return false;
        }
        return true;
    }
    
    public function marketStatus($id = NULL, $type = NULL, $model = 'Market')
    {
        A('User')->sub_status($id,$type,$model);
    }

    public function invit($field = NULL, $name = NULL)
    {
        $where = [];

        if ($field && $name) {
            if ($field == 'username') {
                $where['userid'] = M('User')->where(['username' => $name])->getField('id');
            } else {
                $where[$field] = $name;
            }
        }

        $count = M('Invit')->where($where)->count();
        $Page = new Page($count, 15);
        $show = $Page->show();
        $list = M('Invit')->where($where)->order('id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();

        foreach ($list as $k => $v) {
            $list[$k]['username'] = M('User')->where(['id' => $v['userid']])->getField('username');
            $list[$k]['invit'] = M('User')->where(['id' => $v['invit']])->getField('username');
        }

        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->display();
    }

    public function checkUpdata()
    {
        if (!S(MODULE_NAME . CONTROLLER_NAME . 'checkUpdata')) {
            $list = M('Menu')->where([
                'url' => 'Trade/index',
                'pid' => ['neq', 0]
            ])->select();

            if ($list[1]) {
                M('Menu')->where(['id' => $list[1]['id']])->delete();
            } else if (!$list) {
                M('Menu')->add(['url' => 'Trade/index', 'title' => 'Trades', 'pid' => 5, 'sort' => 1, 'hide' => 0, 'group' => 'transaction', 'ico_name' => 'stats']);
            } else {
                M('Menu')->where([
                    'url' => 'Trade/index',
                    'pid' => ['neq', 0]
                ])->save(['title' => 'Trades', 'pid' => 5, 'sort' => 1, 'hide' => 0, 'group' => 'transaction', 'ico_name' => 'stats']);
            }

            $list = M('Menu')->where([
                'url' => 'Trade/log',
                'pid' => ['neq', 0]
            ])->select();

            if ($list[1]) {
                M('Menu')->where(['id' => $list[1]['id']])->delete();
            } else if (!$list) {
                M('Menu')->add(['url' => 'Trade/log', 'title' => 'Transaction Record', 'pid' => 5, 'sort' => 2, 'hide' => 0, 'group' => 'transaction', 'ico_name' => 'stats']);
            } else {
                M('Menu')->where([
                    'url' => 'Trade/log',
                    'pid' => ['neq', 0]
                ])->save(['title' => 'Transaction Record', 'pid' => 5, 'sort' => 2, 'hide' => 0, 'group' => 'transaction', 'ico_name' => 'stats']);
            }

            $list = M('Menu')->where([
                'url' => 'Trade/chat',
                'pid' => ['neq', 0]
            ])->select();

            if ($list[1]) {
                M('Menu')->where(['id' => $list[1]['id']])->delete();
            } else if (!$list) {
                M('Menu')->add(['url' => 'Trade/chat', 'title' => 'Trading Chat', 'pid' => 5, 'sort' => 3, 'hide' => 0, 'group' => 'transaction', 'ico_name' => 'stats']);
            } else {
                M('Menu')->where([
                    'url' => 'Trade/chat',
                    'pid' => ['neq', 0]
                ])->save(['title' => 'Trading Chat', 'pid' => 5, 'sort' => 3, 'hide' => 0, 'group' => 'transaction', 'ico_name' => 'stats']);
            }

            $list = M('Menu')->where([
                'url' => 'Trade/comment',
                'pid' => ['neq', 0]
            ])->select();

            if ($list[1]) {
                M('Menu')->where(['id' => $list[1]['id']])->delete();
            } else if (!$list) {
                M('Menu')->add(['url' => 'Trade/comment', 'title' => 'Currency Comments', 'pid' => 5, 'sort' => 4, 'hide' => 0, 'group' => 'transaction', 'ico_name' => 'stats']);
            } else {
                M('Menu')->where([
                    'url' => 'Trade/comment',
                    'pid' => ['neq', 0]
                ])->save(['title' => 'Currency Comments', 'pid' => 5, 'sort' => 4, 'hide' => 0, 'group' => 'transaction', 'ico_name' => 'stats']);
            }

            $list = M('Menu')->where([
                'url' => 'Trade/market',
                'pid' => ['neq', 0]
            ])->select();

            if ($list[1]) {
                M('Menu')->where(['id' => $list[1]['id']])->delete();
            } else if (!$list) {
                M('Menu')->add(['url' => 'Trade/market', 'title' => 'market place', 'pid' => 5, 'sort' => 5, 'hide' => 0, 'group' => 'transaction', 'ico_name' => 'stats']);
            } else {
                M('Menu')->where([
                    'url' => 'Trade/market',
                    'pid' => ['neq', 0]
                ])->save(['title' => 'market place', 'pid' => 5, 'sort' => 5, 'hide' => 0, 'group' => 'transaction', 'ico_name' => 'stats']);
            }

            $list = M('Menu')->where([
                'url' => 'Trade/invit',
                'pid' => ['neq', 0]
            ])->select();

            if ($list[1]) {
                M('Menu')->where(['id' => $list[1]['id']])->delete();
            } else if (!$list) {
                M('Menu')->add(['url' => 'Trade/invit', 'title' => 'TRADING RECOMMENDATIONS', 'pid' => 5, 'sort' => 6, 'hide' => 0, 'group' => 'transaction', 'ico_name' => 'stats']);
            } else {
                M('Menu')->where([
                    'url' => 'Trade/invit',
                    'pid' => ['neq', 0]
                ])->save(['title' => 'TRADING RECOMMENDATIONS', 'pid' => 5, 'sort' => 6, 'hide' => 0, 'group' => 'transaction', 'ico_name' => 'stats']);
            }

            if (M('Menu')->where(['url' => 'Chat/index'])->delete()) {
                M('AuthRule')->where(['status' => 1])->delete();
            }

            if (M('Menu')->where(['url' => 'Tradelog/index'])->delete()) {
                M('AuthRule')->where(['status' => 1])->delete();
            }

            S(MODULE_NAME . CONTROLLER_NAME . 'checkUpdata', 1);
        }
    }

    public function getFillId($fill_id){
        if($fill_id==0){
            echo json_encode(['status' => 0, 'data' => '<table class="table table-bordered"><tr><td>Order Matched internally again ,  So it did not have to call Binance</td></tr></table>']);exit;
        }
        $data= M('BinanceTrade')->where(['id' => $fill_id])->find();
        if(is_array($data)) {
            echo json_encode(['status' => 1, 'data' => viewAsTable($data),'raw'=>$data]);exit;
        }else{
            echo json_encode(['status' => 0, 'data' => []]);exit;
        }

    }

    /**
     * @param $field
     * @param $name
     * @param array $where
     * @return array
     */
    private function sub_filter($field, $name, array $where): array
    {
        if ($field && $name) {
            if ($field == 'username') {
                $where['userid'] = M('User')->where(['username' => $name])->getField('id');
            } elseif ($field == 'liq') {
                $where['userid'] = 0;
            } else {
                $where[$field] = $name;
            }
        }
        return $where;
    }

    /**
     * @param $market
     * @param array $where
     * @param $status
     * @param $type
     * @return void
     */
    private function sub_index_filter($market, array $where, $status, $type,$special=false): void
    {

        if($special){
            $markets=[];
            foreach (C('market') as $mkt) {
                if((int)$mkt['auto_match']==0){
                    $markets[]=$mkt['name'];
                }
            }
    
            $where['market'] = array('IN', $markets);
        }
        if ($market) {
            $where['market'] = $market;
        }


        if ($status) {
            $where['status'] = $status;
        }

        if ($status == 0 && $status != null) {
            $where['status'] = 0;
        }
        if ($type == 1 || $type == 2) {
            $where['type'] = $type;
        }


        $count = M('Trade')->where($where)->count();

        $codono_getSum = M('Trade')->where($where)->sum('mum');

        $Page = new Page($count, 15);
        $show = $Page->show();
        $list = M('Trade')->where($where)->order('id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();

        foreach ($list as $k => $v) {
            $list[$k]['username'] = M('User')->where(['id' => $v['userid']])->getField('username');
        }
        $this->assign('list', $list);
        $this->assign('codono_count', $count);
        $this->assign('codono_getSum', $codono_getSum);
        $this->assign('page', $show);
    }
}