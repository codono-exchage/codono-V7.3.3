<?php
/**
 * Advertising Application
 */

namespace Api\Controller;

class AdsController extends CommonController
{
    public function __construct()
    {
        parent::__construct();
    }

    //Initialization rating
  

    //See advertising module
    public function showBlock()
    {
        $blocks = D('Appadsblock')->where(array('status' => 1))->order('sort desc')->select();
        $this->ajaxShow($blocks);
    }

}