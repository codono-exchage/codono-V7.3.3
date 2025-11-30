<?php

namespace Common\Model;

class P2pModel extends \Think\Model
{

    public function getP2pPaymentMethods()
    {
        $data = (APP_DEBUG ? null : S('getP2pPaymentMethods'));
        if (!isset($data)) {
            $data = D('P2pMethod')->where(array('status' => 1))->order('sort asc,name asc')->select();
        }
        return $data;
    }

}