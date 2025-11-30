<?php

namespace Home\Controller;

use Think\Page;

class ArticleController extends HomeController
{
    public function index($id = 19)
    {
        if (empty($id) || !check($id, 'd')) {
            $this->redirect(U('Article/detail'));
            return;
        }

        $cacheKey = "ArticleType_$id";
        $Articletype = S($cacheKey);
        if (!$Articletype) {
            $Articletype = M('ArticleType')->where(['id' => $id])->find();
            S($cacheKey, $Articletype, 3600); // Cache for 1 hour
        }

        $ArticleTypeList = $this->getArticleTypeList();
        $Articleaa = M('Article')->where(['status' => 1, 'id' => $ArticleTypeList[0]['id']])->find();
        $this->assignCommonData($Articletype, $ArticleTypeList, $Articleaa);

        $where = ['type' => $Articletype['name'], 'status' => 1];
        $this->assignArticles($where);

        $this->display();
    }
    public function detail($id = NULL)
    {
        if (empty($id)) {
            $id = 1;
        }

        if (!check($id, 'd')) {
            $id = 1;
        }

        $data = M('Article')->where(array('id' => $id))->find();
        $ArticleType = M('ArticleType')->where(array('status' => 1, 'index' => 1))->order('sort asc ,id desc')->select();
        $ArticleTypeList=array();
        foreach ($ArticleType as $k => $v) {
            $ArticleTypeList[$v['name']] = $v;
        }

        $this->assign('ArticleTypeList', $ArticleTypeList);
        $this->assign('data', $data);
        $this->assign('page_title', $data['title']);
        $this->assign('type', $data['type']);
        $this->display();
    }
    public function cat($name = 'blog')
    {
        if (!$name || !check($name, 'a')) {
            $this->redirect(U('Article/index'));
            return;
        }

        $name = $this->sanitizeName($name);
        
        $cacheKey = "ArticleType_$name";
        $Articletype = S($cacheKey);
        if (!$Articletype) {
            $Articletype = M('ArticleType')->where(['name' => $name])->find();
            S($cacheKey, $Articletype, 3600); // Cache for 1 hour
        }

        $ArticleTypeList = $this->getArticleTypeList(true);
        $Articleaa = M('Article')->where(['status' => 1, 'id' => $ArticleTypeList[0]['id']])->find();
        $this->assignCommonData($Articletype, $ArticleTypeList, $Articleaa);

        $where = ['type' => $Articletype['name'], 'status' => 1];
        $this->assignArticles($where);

        $this->display('Article/index');
    }

    private function getArticleTypeList($index = 0)
    {
        $cacheKey = "ArticleTypeList_$index";
        $ArticleTypeList = S($cacheKey);
        if (!$ArticleTypeList) {
            $ArticleTypeList = M('ArticleType')->where(['status' => 1, 'index' => $index])->order('sort ASC, id ASC')->select();
            S($cacheKey, $ArticleTypeList, 3600); // Cache for 1 hour
        }
        return $ArticleTypeList;
    }

    private function sanitizeName($name)
    {
        return preg_replace('/[^a-zA-Z0-9_-]/', '', str_replace(' ', '-', $name));
    }

    private function assignCommonData($Articletype, $ArticleTypeList, $Articleaa)
    {
        $ArticleTypeLista = array_column($ArticleTypeList, null, 'name');
        $this->assign('shang', $Articletype);
        $this->assign('page_title', $Articletype['title']);
        $this->assign('ArticleTypeListx', $ArticleTypeList);
        $this->assign('ArticleTypeList', $ArticleTypeLista);
        $this->assign('data', $Articleaa);
    }

    private function assignArticles($where)
    {
        $Model = M('Article');
        $count = $Model->where($where)->count();
        $Page = new Page($count, 5);
        $list = $Model->where($where)->order('id DESC')->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $this->assign('list', $list);
        $this->assign('page', $Page->show());
    }
}
