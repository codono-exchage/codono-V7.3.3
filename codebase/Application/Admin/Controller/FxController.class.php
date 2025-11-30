<?php

namespace Admin\Controller;

class FxController extends AdminController
{
    public function index($name = NULL, $field = NULL, $status = NULL)
    {
        $where = array();

        if ($field && $name) {
            if ($field == 'username') {
                $where['userid'] = M('User')->where(array('username' => $name))->getField('id');
            } else if ($field == 'name') {
                $where['name'] = array('like', '%' . $name . '%');
            } else {
                $where[$field] = $name;
            }
        }

        if ($status) {
            $where['status'] = $status - 1;
        }

        $count = M('Fx')->where($where)->count();
        $Page = new \Think\Page($count, 15);
        $show = $Page->show();
        $list = M('Fx')->where($where)->order('id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->display();
    }
	
	public function download_log(){
		
		$list=session('fxrecords');
		
		$xlsName = 'LiqRecord';
        $xls = array();

        $xls[0][0] = "id";
		$xls[1][0] = "qid";
        $xls[2][0] = "userid";
        $xls[3][0] = "type";
        $xls[4][0] = "trade_coin";
        $xls[5][0] = "base_coin";
        $xls[6][0] = "final_price";
        $xls[7][0] = "profit";
        $xls[8][0] = "fees_paid";
        $xls[9][0] = "qty";
        $xls[10][0] = "final_total";
		$xls[11][0] = "addtime";
        
		$xls[12][0] = "status";
		$xls[13][0] = "fill";
		
		
		$xls[0][2] = "id";
		$xls[1][2] = "qid";
        $xls[2][2] = "userid";
        $xls[3][2] = "type";
        $xls[4][2] = "trade_coin";
        $xls[5][2] = "base_coin";
        $xls[6][2] = "final_price";
        $xls[7][2] = "profit";
        $xls[8][2] = "fees_paid";
        $xls[9][2] = "qty";
        $xls[10][2] = "final_total";
		$xls[11][2] = "addtime";
        
		$xls[12][2] = "status";
		$xls[13][2] = "fill";
		
        $this->exportExcel($xlsName, $xls, $list);
	}
	public function fill($id = NULL)
    {
        if (!check($id, 'd')) {
            $this->error(L('Invalid trade id'));
        }
        $rs=M()->execute("UPDATE `codono_fx_log` SET  `fill` =  1 WHERE id = '$id' ");

        if ($rs) {
            $this->success('Marked as filled');
        } else {
            $this->error('Could not mark as filled');
        }
    }
	
	public function filledit($id = NULL)
    {
        $id = intval($_GET['id']);
        $note = "Please place memo and confirm!";
        if (empty($id)) {
            $note = "Select Correct Transaction!";
        }

        $fxlog = M('FxLog')->where(array('id' => $id))->find();

        if (!$fxlog) {
            $note = "No such withdrawal request!";
        }


        if (($fxlog['fill'] == 0)) {
            $note = "Please mark it as filled first!";
        }
        $username = M('User')->where(array('id' => $fxlog['userid']))->getField('username');
        $kyc = M('User')->where(array('id' => $fxlog['userid']))->getField('idcardauth');
		$bank=json_decode($fxlog['bank'],true);
		$this->assign('bank', $bank);
        $this->assign('username', $username);
        $this->assign('kyc', $kyc);
        $this->assign('info', $fxlog);
        $this->assign('note', $note);
        $this->display();
    }
	
	  public function fillEditConfirm()
    {
        $id = intval($_POST['id']);
        $memo = trim($_POST['memo']);

        $note = "Please place memo and confirm!";
        if (empty($id)) {
            $this->error("No such withdrawal request!");
        }
        if (!$memo) {
            $this->error("Please Enter Memo!");
        }

        $myzc = M('FxLog')->where(array('id' => $id))->find();
        $savetx = M('FxLog')->where(array('id' => $id))->save(array('memo' => $memo, 'fill' => 1));
        if (!$savetx) {
            $this->error("Could not update memo and Status!");
        } else {
            $this->success("Memo is saved and status is updated!");
            $this->redirect('Admin/Fx/log');
        }
    }

    

    public function edit()
    {
        if (empty($_GET['id'])) {
            $this->data = false;
        } else {
            $this->data = M('Fx')->where(array('id' => trim($_GET['id'])))->find();
        }

        $this->display();
    }

    public function save()
    {
  
		$id = (int)$_GET['id'];
        $_POST['addtime'] = time();
		$where=array('id'=>$id);

        if ($_POST['id']) {
            $rs = M('Fx')->save($_POST);
        } else {

            $rs = M('Fx')->add($_POST);
        }

        if ($rs) {
            $this->success(L('SUCCESSFULLY_DONE'));
        } else {
            $this->error(L('OPERATION_FAILED'));
        }
    }

    public function status()
    {
        if (APP_DEMO) {
            $this->error(L('SYSTEM_IN_DEMO_MODE'));
        }

        if (IS_POST) {
            $id = array();
            $id = implode(',', $_POST['id']);
        } else {
            $id = $_GET['id'];
        }

        if (empty($id)) {
            $this->error('Please select the data to be operated.');
        }

        $where['id'] = array('in', $id);
        $method = $_GET['method'];

        switch (strtolower($method)) {
            case 'forbid':
                $data = array('status' => 0);
                break;

            case 'resume':
                $data = array('status' => 1);
                break;

            case 'delete':
                if (M('Fx')->where($where)->delete()) {
                    $this->success(L('SUCCESSFULLY_DONE'));
                } else {
                    $this->error(L('OPERATION_FAILED'));
                }

                break;

            default:
                $this->error('Invalid parameters');
        }

        if (M('Fx')->where($where)->save($data)) {
            $this->success(L('SUCCESSFULLY_DONE'));
        } else {
            $this->error(L('OPERATION_FAILED'));
        }
    }

    public function log($field = NULL, $name = NULL, $market = NULL, $type = NULL,$fill=NULL)
    {
        if ($name && check($name, 'username')) {
            $where['userid'] = M('User')->where(array('username' => $name))->getField('id');
        } else {
            $where = array();
        }
		if($fill!=null){
		if ($fill == 0 || $fill == 1 ) {
			$where['fill'] = $fill;
		} 
		}
        if ($type == 'buy' || $type == 'sell') {
            $where['type'] = $type;
        }

		$stmt="";
		foreach ($where as $key=>$val){
			$stmt.= "$key = '$val' AND ";
		}
		$stmt.="1=1";
		
		$query="select * from codono_fx_log where $stmt";
		
		
		$records = M()->query($query);
		$count=sizeof($records);
		
        
		
		

        $Page = new \Think\Page($count, 50);
        $show = $Page->show();
        $list = M('FxLog')->where($where)->order('id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();
		
		
        foreach ($list as $k => $v) {
			$bank=json_decode($v['bank'],true);
			$list[$k]['bankinfo']="<table><tr><td>Bank</td><td>".$bank['bank']."</td></tr><tr><td>Truename</td><td>".$bank['truename']."</td></tr><tr><td>Swift</td><td>".$bank['bankaddr']."</td></tr><tr><td>A/c No</td><td>".$bank['bankcard']."</td></tr>";
            $list[$k]['username'] = M('User')->where(array('id' => $v['userid']))->getField('username');
        }
		session('fxrecords',$list);
        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->display();
    }
	
}
