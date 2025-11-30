<?php

namespace Api\Controller;

class AffiliatesController extends CommonController
{
    public function index()
    {
        $array = array('status' => 1, 'message' => 'Connected to Affilites API');
        echo json_encode($array);
    }
	public function info()
    {
        $uid = $this->userid();

        
        check_server();
        $user = M('User')->where(array('id' => $uid))->find();

        if (!$user['invit']) {
            for (; true;) {
                $tradeno = tradenoa();

                if (!M('User')->where(array('invit' => $tradeno))->find()) {
                    break;
                }
            }

            M('User')->where(array('id' => $uid))->save(array('invit' => $tradeno));
            $user = M('User')->where(array('id' =>$uid))->find();
        }
		$data['username']=$user['username'];
		$data['invite']=$user['invit'];
		$data['inviteurl']=SITE_URL.'Login/register?invite='.$user['invit'];
		$data['truename']=$user['truename'];
		$this->ajaxShow($data);
        
    }
	
	public function invites()
    {
        $uid = $this->userid();

        
        $where['invit_1'] = $uid;
        $Model = M('User');
        $count = $Model->where($where)->count();
        $Page = new \Think\Page($count, 10);
        $show = $Page->show();
        $list = $Model->where($where)->order('id asc')->field('id,username,cellphone,addtime,invit_1')->limit($Page->firstRow . ',' . $Page->listRows)->select();

        foreach ($list as $k => $v) {
            $list[$k]['invits'] = M('User')->where(array('invit_1' => $v['id']))->order('id asc')->field('id,username,cellphone,addtime,invit_1')->select();
            $list[$k]['invitss'] = count($list[$k]['invits']);

            foreach ($list[$k]['invits'] as $kk => $vv) {
                $list[$k]['invits'][$kk]['invits'] = M('User')->where(array('invit_1' => $vv['id']))->order('id asc')->field('id,username,cellphone,addtime,invit_1')->select();
                $list[$k]['invits'][$kk]['invitss'] = count($list[$k]['invits'][$kk]['invits']);
            }
        }

		$this->ajaxShow($list);    
		}

}
