<?php

namespace Common\Model;

class TextModel extends \Think\Model
{
    protected $keyS = 'Text';

    public function get_content($name = NULL)
    {
        if (empty($name)) {
            return null;
        }
		
        $get_content = (APP_DEBUG ? null : S('get_content' . $this->keyS . $name));

        if (!$get_content) {
            $response=$this->check_field($name);
			if($response['status']==1){
            $get_content = $response['content'];//M('Text')->where(array('name' => $name, 'status' => 1))->getField('content');
			}
            S('get_content' . $this->keyS . $name, $get_content);
        }

        return $get_content;
    }
    public function get_title($name = NULL)
    {
        if (empty($name)) {
            return null;
        }
		
        $get_title = (APP_DEBUG ? null : S('get_title' . $this->keyS . $name));

        if (!$get_title) {
            $response=$this->check_field($name);
			if($response['status']==1){
            $get_title = $response['title'];//M('Text')->where(array('name' => $name, 'status' => 1))->getField('content');
			}
            S('get_title' . $this->keyS . $name, $get_title);
        }

        return $get_title;
    }
    public function check_field($name = NULL)
    {
		$info=M('Text')->where(array('name' => $name))->find();
        if (!$info) {
			$add=array('name' => $name, 'content' => 'Modify in backend', 'status' => 1, 'addtime' => time());
            M('Text')->add($add);
			$info=$add;
        }
		return $info;
    }
}