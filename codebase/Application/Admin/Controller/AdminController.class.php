<?php

namespace Admin\Controller;

use Common\Model\AuthRuleModel;
use PHPExcel;
use Think\Auth;

class AdminController extends \Think\Controller
{
    public function __construct()
    {
        parent::__construct();
        defined('APP_DEMO') || define('APP_DEMO', 0);

        if (!session('admin_id')) {
            ///$this->redirect('Admin/Login/index');
        }
		if (!defined('UID')) {
        define('UID', session('admin_id'));
		}
		$admin = M('AuthGroupAccess')->where(array('uid' => UID))->find();
		if($admin['group_id']==3 && $admin['uid']==UID){
			if (!defined('IS_ROOT')) {
				define('IS_ROOT',1);
			}
		}
			
        $config = M('Config')->where(array('id' => 1))->find();
        C($config);
        $coin = (APP_DEBUG ? null : S('home_coin'));

        if (!$coin) {
            $coin = M('Coin')->where(array('status' => 1))->select();
            S('home_coin', $coin);
        }

        $coinList = array();

        foreach ($coin as $k => $v) {
            $coinList['coin'][$v['name']] = $v;

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
			if ($v['type'] == 'blockio') {
                $coinList['blockio_list'][$v['name']] = $v;
            }
			if ($v['type'] == 'cryptonote') {
                $coinList['cryptonote_list'][$v['name']] = $v;
            }
			if ($v['type'] == 'coinpay') {
                $coinList['coinpay_list'][$v['name']] = $v;
            }
			if ($v['type'] == 'waves') {
                $coinList['waves_list'][$v['name']] = $v;
            }
			if ($v['type'] == 'esmart') {
                $coinList['esmart_list'][$v['name']] = $v;
            }
            if ($v['type'] == 'tron') {
                $coinList['tron_list'][$v['name']] = $v;
            }
			if ($v['type'] == 'cryptoapis') {
                $coinList['cryptoapis_list'][$v['name']] = $v;
            }

        }

        C($coinList);
        $market = (APP_DEBUG ? null : S('home_market'));

        if (!$market) {
            $market = M('Market')->where(array('status' => 1))->select();
            S('home_market', $market);
        }
        $marketList=[];
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
        $C = C();

        foreach ($C as $k => $v) {
            $C[strtolower($k)] = $v;
        }

        $this->assign('C', $C);

        if (session('admin_id') == 1) {
            $currentVersion = CODONO_VERSION;
            $nextVersion = M('Version')->where(array('status' => 0))->order('name desc')->getField('name');

            if ($nextVersion && ($currentVersion != $nextVersion)) {
                $this->assign('versionUp', 1);
            }
			if (!defined('IS_ROOT')) {

            define('IS_ROOT', 1);
			}
        } else {
            define('IS_ROOT', 0);
        }

        $access = $this->accessControl();


        if ($access === false) {
             $this->redirect('Home/Content/E404','Unauthorized access!');
        } else if ($access === null) {
            $dynamic = $this->checkDynamic();

            if ($dynamic === null) {
                $rule = strtolower(MODULE_NAME . '/' . CONTROLLER_NAME . '/' . ACTION_NAME);
                if (!$this->checkRule($rule, array('in', '1,2'))) {
                 $this->redirect('Home/Content/E403','Unauthorized access!');
                }
            } else if ($dynamic === false) {
                $this->error('Unauthorized access!');
            }
        }
//Only enable if you want to restrict backend . Even super admin may not open every area. Thus add a condition here to excludes checkaccess for super admin
//		if($this->checkAccess()==0){echo 'You dont have permission to this module';die();}
		//find pending withdrawals

        $todos['pending_crypto_withdrawals'] = M('Myzc')->where(array('status' => 0))->count();
        $todos['p2p_disputes'] = M('P2pOrders')->where(['status'=>3])->count();
        $todos['liq_pending_fill']= M('TradeLog')->where(['fill'=>0,'_string' => '((userid = 0 AND peerid != 0) OR (userid != 0 AND peerid = 0))'])->count();
        $todos['kyc_reviews']= M('User')->where(['status'=>6])->count();
        $todos['fiat_in_pending']= M('Mycz')->where(['status'=>3])->count();
        $todos['fiat_out_pending']= M('Mycz')->where(['status'=>1])->count();
        $count=0;
        foreach($todos as $todo){
            if($todo>0){
                $count++;
            }
        }
        $todos['count']=$count;
        $this->assign('title','');
        $this->assign('todos',$todos);
        $this->assign('__MENU__', $this->getMenus());
        $this->assign('__ALLNAV__', $this->getAllMenus());
	  
    }
	private function checkAccess(){
		$current_url=strtolower(U());
		if(strpos($current_url,'Login/')){return true;}
		
		$access_menus=$this->getMenus();
		$allow=false;
		foreach($access_menus['main'] as $access){
			$ex=explode('/',$access['url']);
			$allowed_controller=strtolower($ex[0]);
			$allowed_module=strtolower($ex[1]);
			$check=strtolower($allowed_controller);		
		if(strpos($current_url,'admin/'.$allowed_controller)){
			if($allowed_module=='index'){$allow= true;}
			else{
				if(strpos($current_url,$allowed_controller.'/'.$allowed_module)){
					$allow= true;
				}
			}
		}
		//continue;
		}
		return $allow;
	}
    public function index()
    {
        $this->redirect('Admin/Index/index');
    }

    final protected function checkRule($rule, $type = AuthRuleModel::RULE_URL, $mode = 'url')
    {
        if (IS_ROOT) {
            return true;
        }

        static $Auth;

        if (!$Auth) {
            $Auth = new Auth();
        }

        if (!$Auth->check($rule, UID, $type, $mode)) {
            return false;
        }

        return true;
    }

    final protected function editRow($model, $data, $where, $msg=[])
    {
        $id = array_unique((array)I('id', 0));
        $id = (is_array($id) ? implode(',', $id) : $id);
        $where = array_merge(array(
            'id' => array('in', $id)
        ), (array)$where);
        $msg = array_merge(array('success' => L('SUCCESSFULLY_DONE'), 'error' => L('OPERATION_FAILED'), 'url' => '', 'ajax' => IS_AJAX), (array)$msg);

        if (M($model)->where($where)->save($data) !== false) {
            $this->success($msg['success'], $msg['url'], $msg['ajax']);
        } else {
            $this->error($msg['error'], $msg['url'], $msg['ajax']);
        }
    }

    protected function forbid($model, $where = array(), $msg = array('success' => 'Status disabled successfully!', 'error' => 'State Disable failed!'))
    {
        $data = array('status' => 0);
        $this->editRow($model, $data, $where, $msg);
    }

    protected function resume($model, $where = array(), $msg = array('success' => 'Status restored successfully!', 'error' => 'State recovery failed!'))
    {
        $data = array('status' => 1);
        $this->editRow($model, $data, $where, $msg);
    }

    protected function restore($model, $where = array(), $msg = array('success' => 'State restore success!', 'error' => 'Status restore failed!'))
    {
        $data = array('status' => 1);
        $where = array_merge(array('status' => -1), $where);
        $this->editRow($model, $data, $where, $msg);
    }

    protected function delete($model, $where = array(), $msg = array('success' => 'successfully deleted!', 'error' => 'failed to delete!'))
    {
        $data['status'] = -1;
        $data['update_time'] = NOW_TIME;
        $this->editRow($model, $data, $where, $msg);
    }

    public function setStatus($Model = CONTROLLER_NAME)
    {
        $ids = I('request.ids');
        $status = I('request.status');

        if (empty($ids)) {
            $this->error('Please select the data to be operated');
        }

        $map['id'] = array('in', $ids);

        switch ($status) {
            case -1:
                $this->delete($Model, $map, array('success' => 'successfully deleted', 'error' => 'failed to delete'));
                break;

            case 0:
                $this->forbid($Model, $map, array('success' => 'Disable success', 'error' => 'Disable fail'));
                break;

            case 1:
                $this->resume($Model, $map, array('success' => 'Enable Success', 'error' => 'Enable fail'));
                break;

            default:
                $this->error('Parameter error');
                break;
        }
    }

    protected function checkDynamic()
    {
        if (IS_ROOT) {
            return true;
        }

        return null;
    }

    final protected function accessControl()
    {
        if (IS_ROOT) {
            return true;
        }

        $allow = C('ALLOW_VISIT');
		
        $deny = C('DENY_VISIT');
		
        $check = strtolower(CONTROLLER_NAME . '/' . ACTION_NAME);

        if (!empty($deny) && in_array_case($check, $deny)) {
            return false;
        }

        if (!empty($allow) && in_array_case($check, $allow)) {
            return true;
        }

        return null;
    }

    final public function getMenus($controller = CONTROLLER_NAME)
    {
		$menus = array();//F('admin_menus'); //caching cant work because of sub menu would not show
		
        if (empty($menus)) {
            $where['pid'] = 0;
            $where['hide'] = 0;

            if (!C('DEVELOP_MODE')) {
                $where['is_dev'] = 0;
            }

            $menus['main'] = M('Menu')->where($where)->order('sort asc')->select();
            $menus['child'] = array();
            $current = M('Menu')->where('url like \'' . $controller . '/' . ACTION_NAME . '%\'')->field('id')->find();

            if (!$current) {
                $current = M('Menu')->where('url like \'' . $controller . '/%\'')->field('id')->find();
            }

            if ($current) {
                $nav = D('Menu')->getPath($current['id']);
                $nav_first_title = $nav[0]['title'];

                foreach ($menus['main'] as $key => $item) {
                    if (!is_array($item) || empty($item['title']) || empty($item['url'])) {
                        $this->error('Controller base class$menusProperty element is configured incorrectly');
                    }

                    if (stripos($item['url'], MODULE_NAME) !== 0) {
                        $item['url'] = MODULE_NAME . '/' . $item['url'];
                    }

                    if (!IS_ROOT && !$this->checkRule($item['url'], AuthRuleModel::RULE_MAIN, null)) {
                        unset($menus['main'][$key]);
                        continue;
                    }
					$menus['main'][$key]['class']="";
                    if ($item['title'] == $nav_first_title) {
                        $menus['main'][$key]['class'] = 'current';
                        $groups = M('Menu')->where('pid = ' . $item['id'])->distinct(true)->field('`group`')->select();

                        if ($groups) {
                            $groups = array_column($groups, 'group');
                        } else {
                            $groups = array();
                        }

                        $where = array();
                        $where['pid'] = $item['id'];
                        $where['hide'] = 0;

                        if (!C('DEVELOP_MODE')) {
                            $where['is_dev'] = 0;
                        }

                        $second_urls = M('Menu')->where($where)->getField('id,url');

                        if (!IS_ROOT) {
                            $to_check_urls = array();

                            foreach ($second_urls as $second_url => $to_check_url) {
                                if (stripos($to_check_url, MODULE_NAME) !== 0) {
                                    $rule = MODULE_NAME . '/' . $to_check_url;
                                } else {
                                    $rule = $to_check_url;
                                }

                                if ($this->checkRule($rule, AuthRuleModel::RULE_URL, null)) {
                                    $to_check_urls[] = $to_check_url;
                                }
                            }
                        }

                        foreach ($groups as $g) {
                            $map = array('group' => $g);

                            if (isset($to_check_urls)) {
                                if (empty($to_check_urls)) {
                                    continue;
                                } else {
                                    $map['url'] = array('in', $to_check_urls);
                                }
                            }

                            $map['pid'] = $item['id'];
                            $map['hide'] = 0;

                            if (!C('DEVELOP_MODE')) {
                                $map['is_dev'] = 0;
                            }

                            $menuList = M('Menu')->where($map)->field('id,pid,title,url,tip,ico_name')->order('sort asc')->select();
                            $menus['child'][$g] = list_to_tree($menuList, 'id', 'pid', 'operater', $item['id']);
                        }

                        if ($menus['child'] === array()) {
                        }
                    }
                }
            }
			F("admin_menus",$menus);
        }
         
        return $menus;
    }

    final protected function returnNodes($tree = true)
    {
        static $tree_nodes = array();

        if ($tree && !empty($tree_nodes[(int)$tree])) {
            return $tree_nodes[$tree];
        }

        if ((int)$tree) {
            $list = M('Menu')->field('id,pid,title,url,tip,hide')->where(array('hide' => 0))->order('sort asc')->select();

            foreach ($list as $key => $value) {
                if (stripos($value['url'], MODULE_NAME) !== 0) {
                    $list[$key]['url'] = MODULE_NAME . '/' . $value['url'];
                }
            }

            $nodes = list_to_tree($list, $pk = 'id', $pid = 'pid', $child = 'operator', $root = 0);

            foreach ($nodes as $key => $value) {
                if (!empty($value['operator'])) {
                    $nodes[$key]['child'] = $value['operator'];
                    unset($nodes[$key]['operator']);
                }
            }
        } else {
            $nodes = M('Menu')->field('title,url,tip,pid')->where(array('hide' => 0))->order('sort asc')->select();

            foreach ($nodes as $key => $value) {
                if (stripos($value['url'], MODULE_NAME) !== 0) {
                    $nodes[$key]['url'] = MODULE_NAME . '/' . $value['url'];
                }
            }
        }

        $tree_nodes[(int)$tree] = $nodes;
        return $nodes;
    }

    protected function lists($model, $where = array(), $order = '', $base = array(
        'status' => array('egt', 0)
    ), $field = true)
    {
        $options = array();
        $REQUEST = (array)I('request.');

        if (is_string($model)) {
            $model = M($model);
        }

        $OPT = new \ReflectionProperty($model, 'options');
        $OPT->setAccessible(true);
        $pk = $model->getPk();

        if ($order === null) {
        } else if (isset($REQUEST['_order']) && isset($REQUEST['_field']) && in_array(strtolower($REQUEST['_order']), array('desc', 'asc'))) {
            $options['order'] = '`' . $REQUEST['_field'] . '` ' . $REQUEST['_order'];
        } else if (($order === '') && empty($options['order']) && !empty($pk)) {
            $options['order'] = $pk . ' desc';
        } else if ($order) {
            $options['order'] = $order;
        }
        unset($REQUEST['_order']);
        unset($REQUEST['_field']);
        $options['where'] = array_filter(array_merge((array)$base, (array)$where), function ($val) {
            if (($val === '') || ($val === null)) {
                return false;
            } else {
                return true;
            }
        });

        if (empty($options['where'])) {
            unset($options['where']);
        }

        $options = array_merge((array)$OPT->getValue($model), $options);
        $total = $model->where($options['where'])->count();

        if (isset($REQUEST['r'])) {
            $listRows = (int)$REQUEST['r'];
        } else {
            $listRows = (0 < C('LIST_ROWS') ? C('LIST_ROWS') : 10);
        }

        $page = new \Think\Page($total, $listRows, $REQUEST);

        if ($listRows < $total) {
            $page->setConfig('theme', '%FIRST% %UP_PAGE% %LINK_PAGE% %DOWN_PAGE% %END% %HEADER%');
        }

        $p = $page->show();
        $this->assign('_page', $p ?: '');
        $this->assign('_total', $total);
        $options['limit'] = $page->firstRow . ',' . $page->listRows;
        $model->setProperty('options', $options);
        return $model->field($field)->select();
    }

    /**
     * @throws \PHPExcel_Exception
     * @throws \PHPExcel_Writer_Exception
     * @throws \PHPExcel_Reader_Exception
     */
    public function exportExcel($expTitle, $expCellName, $expTableData)
    {
		
        import("Org.Util.PHPExcel");
        import("Org.Util.PHPExcel.Writer.Excel5", '', '.php');
        import("Org.Util.PHPExcel.IOFactory", '', '.php');
		$expTableData=array_values($expTableData);

        $xlsTitle =  $expTitle;
        $fileName = $xlsTitle . date('_Y-m-d-H-i-s');
        $cellNum = count($expCellName);
        $dataNum = count($expTableData);
		
        $objPHPExcel = new PHPExcel();
        $cellName = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z', 'AA', 'AB', 'AC', 'AD', 'AE', 'AF', 'AG', 'AH', 'AI', 'AJ', 'AK', 'AL', 'AM', 'AN', 'AO', 'AP', 'AQ', 'AR', 'AS', 'AT', 'AU', 'AV', 'AW', 'AX', 'AY', 'AZ');
        $objPHPExcel->getActiveSheet()->mergeCells('A1:' . $cellName[$cellNum - 1] . '1');
      
        $i = 0;

        for ($i=0; $i < $cellNum; $i++) {
			
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue($cellName[$i] . '2', $expCellName[$i][2]);
        }

        for ($i=0; $i < $dataNum; $i++) {
            for ($j=0; $j < $cellNum; $j++) {
                $objPHPExcel->getActiveSheet()->setCellValue($cellName[$j] . ($i + 3), (string)$expTableData[$i][$expCellName[$j][0]]);
            }
        }

        ob_end_clean();
        header('pragma:public');
        header('Content-type:application/vnd.ms-excel;charset=utf-8;name="' . $xlsTitle . '.xls"');
        header('Content-Disposition:attachment;filename=' . $fileName . '.xls');
        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $objWriter->save('php://output');
        exit();
    }

    public function _empty()
    {
        send_http_status(404);
        $this->error();
        echo 'Module does not exist!';
        die();

    }
    final public function getAllMenus()
    {
        $menus = [];//F('admin_menus'); 
    
        if (empty($menus)) {
            $where['hide'] = 0;
            $where['pid']  =0; 
            if (!C('DEVELOP_MODE')) {
                $where['is_dev'] = 0;
            }
    
            $mainMenus = M('Menu')->where($where)->order('sort asc')->select();
            
            foreach ($mainMenus as &$mainMenu) {
                // Append child menus to each main menu item
                $controller=explode('/',$mainMenu['url']);   
                $mainMenu['child_menu'] = $this->getChildMenus($mainMenu['id']);
                //$mainMenu['child_menu'] =$this->getMenus($controller[0])['child'];
            }
    
            $menus['main'] = $mainMenus;
            F("admin_menus", $menus);
        }
        
        return $menus;
    }
   
    // Function to get child menus based on parent menu ID
    private function getChildMenus($parentId)
    {
        
        $childMenus = array();
        $where['pid'] = $parentId;
        $where['hide'] = 0;
        
        if (!C('DEVELOP_MODE')) {
            $where['is_dev'] = 0;
        }
    
        $menuList = M('Menu')->where($where)->field('id,pid,title,url,tip,ico_name,group')->order('sort asc')->select();
        // Group child menus by their "group" value
foreach ($menuList as $child) {
    $group = $child['group'] ?? ''; // Default group if no group specified
    
    // Create a group if it doesn't exist in the result array
    if (!isset($menu[$group])) {
        $menu[$group] = [];
    }
    
    // Add the child menu to the group
    $menu[$group][] = $child;
}

        // foreach ($menuList as &$menu) {
        //     // Recursively get child menus for each menu
        //     $menu['sub_menu'] = $this->getChildMenus($menu['id']);
        // }
    
        return $menu;
    }
    

}
