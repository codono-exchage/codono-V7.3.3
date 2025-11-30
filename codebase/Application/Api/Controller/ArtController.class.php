<?php

namespace Api\Controller;

class ArtController extends CommonController
{
    public function __construct()
    {
        parent::__construct();
    }

    public function index()
    {
        $array = array('status' => 1, 'message' => 'connected to Article API');
        echo json_encode($array);
    }

    protected function getTopList()
    {
        $topList = [];
        $urlList = [];
        $i = 1;

        for (; $i <= 4; $i++) {
            $img_url = SITE_URL . '/Upload/Banner' . $i . '.jpg';
            $topList[] = array('id' => $i, 'istop' => 1, 'date' => '2015-7-15', 'img_url' => $img_url, 'title' => 'Sticky Content', 'mark' => 'Top content');
            $urlList[] = $img_url;
        }

        return array('toplist' => $topList, 'urlList' => $urlList);
    }

    public function ArtList()
    {
		$pid=I('post.pid'); 
        $res = M('Article')->where(array("status = 1 and id > $pid"))->field('id,title,addtime,content')->order('id desc')->limit(10)->select();

        if (!$res) {
            $res[0]['content'] = 'no content yet';
            $this->ajaxShow($res);
        }

        foreach ($res as $_key => $_art) {
            $res[$_key]['content'] = mb_substr(strip_tags($_art['content']), 0, 100, 'utf-8');

            if (!$res[$_key]['content']) {
                $res[$_key]['content'] = 'no content yet';
            }

            $res[$_key]['addtime'] = date('Y-m-d H:i', $_art['addtime']);
        }

        $this->ajaxShow($res);
    }
    public function appBanners()
    {
        $res = M('Article')->where(array("status = 1 and type= 'appbanner'"))->field('id,title,addtime,content,img')->order('id desc')->limit(10)->select();
        
        if (!$res) {
            $res[0]['content'] = 'no content yet';
            $this->ajaxShow($res);
        }

        foreach ($res as $_key => $_art) {
            
            $res[$_key]['content'] = mb_substr(strip_tags($_art['content']), 0, 100, 'utf-8');

            if (!$res[$_key]['content']) {
                $res[$_key]['content'] = 'no content yet';
            }
            $res[$_key]['img']=SITE_URL.'Upload/article/'.$_art['img'];
            $res[$_key]['addtime'] = date('Y-m-d H:i', $_art['addtime']);
        }
        header("Content-Type: application/json");
        $this->ajaxShow($res);
    }

	public function NewsList()
    {	
	$where['status']=1;
	$where['type']='blog';
        $res = M('Article')->where($where)->field('id,title,addtime,content,img')->order('id desc')->limit(10)->select();

        if (!$res) {
            $res[0]['content'] = 'no content yet';
            $this->ajaxShow($res);
        }

        foreach ($res as $_key => $_art) {
            $res[$_key]['content'] = mb_substr(strip_tags($_art['content']), 0, 100, 'utf-8');

            if (!$res[$_key]['content']) {
                $res[$_key]['content'] = 'no content yet';
            }
			
			
        if ($_art['img'] != null) {
            $res[$_key]['img'] = SITE_URL . 'Upload/article/' . $_art['img'];
        } else {
            $res[$_key]['img'] = null;
        }

            $res[$_key]['addtime'] = date('Y-m-d H:i', $_art['addtime']);
        }

        $this->ajaxShow($res);
    }
    public function annoucements()
    {	
	$where['status']=1;
	$where['type']='news';
    $res = M('Article')->where($where)->field('id,title,addtime,content,img')->order('id desc')->limit(10)->select();

    if (!$res) {
        $res[0]['content'] = 'no content yet';
        $this->ajaxShow($res);
    }

    foreach ($res as $_key => $_art) {
        $res[$_key]['content'] = mb_substr(strip_tags($_art['content']), 0, 100, 'utf-8');

        if (!$res[$_key]['content']) {
            $res[$_key]['content'] = 'no content yet';
        }
        
        
    if ($_art['img'] != null) {
        $res[$_key]['img'] = SITE_URL . 'Upload/article/' . $_art['img'];
    } else {
        $res[$_key]['img'] = null;
    }

        $res[$_key]['addtime'] = date('Y-m-d H:i', $_art['addtime']);
    }

    $this->ajaxShow($res);
    }

    public function ArtShow()
    {
        $id = I('post.id',0,'intval'); 
        $arr = M('Article')->where(array('id' => $id, 'status' => '1'))->find();

        if ($arr['img'] != null) {
            $image_path = SITE_URL . 'Upload/article/' . $arr['img'];
        } else {
            $image_path = null;
        }

        $ret = array('id' => $arr['id'], 'date' => date('Y-m-d H:i:s', time()), 'source' => 'Admin', 'title' => $arr['title'], 'content' => $arr['content'] ? $arr['content'] : 'No Contents', 'image' => $image_path);
        $this->ajaxShow($ret);
    }
	
	public function bannerList()
    {
		
        $indexLink = (APP_DEBUG ? null : F('index_indexLink'));

        if (!$indexLink) {
            $indexLink = M('Link')->where(['status' => 1])->order('sort asc ,id desc')->select();
			F('index_indexLink', $indexLink);
        }
        $banners=[];
        foreach($indexLink as $link){
            $banner['id']=$link['id'];
            $banner['img']=SITE_URL.'Upload/link/'.$link['img'];
            $banner['url']=$link['url'];
            $banner['name']=$link['name'];
            $banners[]=$banner;
        }
        $this->ajaxShow($banners);
    }
	
}