<?php

namespace Admin\Controller;

use Think\Exception;

class OptionsController extends AdminController
{
    private $Model;
    private string $title;

    public function __construct()
    {
        parent::__construct();
        $this->Model = M('Options');
        $this->title = 'System Options [Config]';
    }

    public function index()
    {
        $config = $this->Model->select();
		$this->assign('list', $config);
        $this->display();
    }
	public function edit($id)
    {
		  if (!check($id, 'd')) {
			  $this->error('Incorrect option selected');
		  }
		$config = $this->Model->where(array('id'=>$id))->find();
        $this->assign('id',$config['id']);
		$this->assign('config',$config);
		$this->assign('name',$config['name']);

	
		if(isJson($config['value'])){
			$this->assign('value', json_decode($config['value'],true));
		}else{
			$this->assign('value', $config['value']);
		}
       $this->display();
    }
	public function save(){

		if (IS_POST) {
            $input=I('post.');
			$id=(int)$input['id'];
            unset($input['id']);
            $save=[];

            foreach($input as $key=>$value){
                if($key=='blockgum_jwt'){
                    $save['value']=base64_encode($value);
                }else{
                    $save['value']=($value);
                }

			}
            try {
                $status = $this->Model->where(array('id' => $id))->save($save);
                $this->success('saved'.$status);
            }catch (Exception $ex){
                if(M_DEBUG)
                {
                    clog('option_save',$ex->getMessage());
                } 
            }
		}
		
	}
}