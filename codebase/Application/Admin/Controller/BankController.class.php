<?php

namespace Admin\Controller;

class BankController extends AdminController
{
    public function index()
    {

        $UserBankType = M('UserBankType')->order('id desc')->select();
		$builder = new BuilderList();
        
		
		 $builder->title('Banks');
		$builder->titleList('Banks List', U('Bank/index'));
		$builder->button('add', 'Add', U('Bank/edit'));
		$builder->setSearchPostUrl(U('Bank/index'));
		$builder->search('order', 'select', array('id_desc' => 'ID desc', 'id_asc' => 'ID asc'));
		$builder->search('status', 'select', array('All Status', 'Disabled', 'Enabled'));
		$builder->search('type', 'select', $UserBankType);
		$builder->search('field', 'select', array('name' => 'Name'));
		$builder->search('name', 'text', 'Enter search content');
		$builder->keyText('id', 'id');
        $builder->keyText('name', 'Symbol');
        $builder->keyText('title', 'Title');
		$builder->keyText('url', 'URL');
        $builder->keyText('sort', 'Sort');
		$builder->keyText('type', 'Type');
        $builder->keyStatus('status', 'Status', array('Disabled', 'Enabled'));
        $builder->keyDoAction('Bank/edit?id=###', 'Edit', 'Option');
		$builder->keyDoAction('Bank/deleteBank?id=###', 'Delete', 'Option');

        $builder->data($UserBankType);
        //$builder->pagination($count, $r, $parameter);
        $builder->display();
    }
	 public function deleteBank($id = NULL)
    {
        $where['id'] = $id;
        if (M('UserBankType')->where($where)->delete()) {
            $this->success(L('SUCCESSFULLY_DONE'));
        } else {
            $this->error('No changes were made.');
        }

    }
    public function edit($id = NULL)
    {
        header('Content-Type: text/plain; charset=UTF-8');

        $data = I('post.');
        
            $BankModel = M('UserBankType');
            if (IS_POST) {
                
                $data['title']=urldecode($data['title']);
                if ($data['id']) {
                    $BankModel->where(['id' => $data['id']])->save($data);
                } else {
                    $BankModel->add($data);
                }
                $this->success('Operation Successful', U('Bank/Index'));
            } else {
                $id = I('get.id', 0);
                if ($id) {
                    $data = $BankModel->where(['id' => $id])->find();
                    $this->assign('data', $data);
                }
                $this->display();
            }
    }

}
