<?php

namespace Admin\Controller;

class GiftcardController extends AdminController
{
    private $Model;

    public function __construct()
    {
        parent::__construct();
        $this->Model = M('Giftcard');
        $this->Title = 'Gift Cards';
    }

    public function index($publiccode = NULL)
    {
		$where=array();
        if ($publiccode) {
            $where['public_code'] = $publiccode;
        }
		
        $count = $this->Model->where($where)->count();
        $Page = new \Think\Page($count, 15);
        $show = $Page->show();
        $list = $this->Model->where($where)->order('id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();
		$giftcard_images=$this->giftcard_images();
		
		
        foreach ($list as $k => $v) {
            $list[$k]['owner_name'] = username($v['owner_id']);
            $list[$k]['consumer_name'] = username($v['consumer_id']);
			$list[$k]['secret']=cryptString($v['secret_code'],'d');
			
			foreach($giftcard_images as $gcimg){
			
				if($gcimg['id'] == $list[$k]['card_img']){
					$list[$k]['image']='/Upload/giftcard/'.$gcimg['image'];
				}
			}
			//$list[$k]['image']='./Upload/giftcard/'.$v['card_img'];
        }
		
		$this->assign("giftcard_images",$giftcard_images);
        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->display();
    }
	
	public function status($id = NULL, $type = NULL)
	{

		if (APP_DEMO) {
			$this->error(L('SYSTEM_IN_DEMO_MODE'));
		}

		if (empty($id)) {
			$this->error('Select Members!');
		}

		if (empty($type)) {
			$this->error(L('INCORRECT_REQ'));
		}

		if (strpos(',', $id)) {
			$id = implode(',', $id);
		}
	
		$where['id'] = array('in', $id);


		switch (strtolower($type)) {
		case 'inactive':
			$data = array('status' => 0);
			break;

		case 'active':
			$data = array('status' => 1);
			break;

		case 'consumed':
			$data = array('status' => 2, 'endtime' => time());
			break;

		case 'delete':
			if (M("Giftcard")->where($where)->delete()) {
				$this->success(L('SUCCESSFULLY_DONE'));
			}
			else {
				$this->error(L('OPERATION_FAILED'));
			}

			break;

		
		default:
			$this->error(L('OPERATION_FAILED'));
		}

		if (M('Giftcard')->where($where)->save($data)) {
			$this->success(L('SUCCESSFULLY_DONE'));
		}
		else {
			$this->error(L('OPERATION_FAILED'));
		}
	}
	
	public function banner($name = NULL, $field = NULL, $status = NULL)
    {
        $where = array();

        if ($field && $name) {
            if ($field == 'title') {
                $where['title'] = array('like', '%' . $name . '%');
            } else {
                $where[$field] = $name;
            }
        }

        if ($status) {
            $where['status'] = $status - 1;
        }
		$Mo=M('GiftcardImages');
        $count = $Mo->where($where)->count();
        $Page = new \Think\Page($count, 15);
        $show = $Page->show();
        $list = $Mo->where($where)->order('id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->display();
    }

    public function bannerEdit($id = NULL)
    {
		$Mo=M('GiftcardImages');
        if (empty($_POST)) {
            if ($id) {
                $this->data = $Mo->where(array('id' => trim($id)))->find();
            } else {
                $this->data = null;
            }

            $this->display();
        } else {
            if (APP_DEMO) {
                $this->error(L('SYSTEM_IN_DEMO_MODE'));
            }

            if ($_POST['addtime']) {
                if (addtime(strtotime($_POST['addtime'])) == '---') {
                    $_POST['addtime']=time();
                } else {
                    $_POST['addtime'] = strtotime($_POST['addtime']);
                }
            } else {
                $_POST['addtime'] = time();
            }



            if ($_POST['id']) {
                $rs = $Mo->save($_POST);
            } else {
                $rs = $Mo->add($_POST);
            }

            if ($rs) {
                $this->success(L('SAVED_SUCCESSFULLY'));
            } else {
                $this->error(L('COULD_NOT_SAVE'));
            }
        }
    }

    public function bannerStatus($id = NULL, $type = NULL)
    {
		$model = 'GiftcardImages';
		A('User')->sub_status($id,$type,$model);
    }


	public function bannerimage()
    {
        $upload = new \Think\Upload();
        $upload->maxSize = 3145728;
        $upload->exts = array('jpg', 'gif', 'png', 'jpeg');
        $upload->rootPath = './Upload/giftcard/';
        $upload->autoSub = false;
        $info = $upload->upload();

        foreach ($info as $k => $v) {
            $path = $v['savepath'] . $v['savename'];
            echo $path;
            exit();
        }
    }
	
	private function giftcard_images(){
        $data = (APP_DEBUG ? null : S('giftcard_images'));
		if (!$data) {	
		$data=M('GiftcardImages')->where(array('status' => 1))->order("id desc")->limit(100)->select();
		}
		return $data;
	}
}