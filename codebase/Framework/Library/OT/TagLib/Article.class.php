<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2013 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Author: When wheat seedlings child <zuojiazi.cn@gmail.com> <http://www.zjzit.cn>
// +----------------------------------------------------------------------
namespace OT\TagLib;

use Think\Template\TagLib;

/**
 * ThinkCMS Department document model tag library
 */
class Article extends TagLib
{
    /**
     * Custom label list
     * @var array
     */
    protected $tags = array(
        'partlist' => array('attr' => 'id,field,page,name', 'close' => 1), //List paragraph
        'partpage' => array('attr' => 'id,listrow', 'close' => 0), //Paragraph pagination
        'prev' => array('attr' => 'name,info', 'close' => 1), //ObtainPrevious articleinformation
        'next' => array('attr' => 'name,info', 'close' => 1), //Get the nextArticleinformation
        'page' => array('attr' => 'cate,listrow', 'close' => 0), //List Page
        'position' => array('attr' => 'pos,cate,limit,filed,name', 'close' => 1), //Get a list of recommended position
        'list' => array('attr' => 'name,category,child,page,row,field', 'close' => 1), //Gets the specified category list
    );

    public function _list($tag, $content)
    {
        $name = $tag['name'];
        $cate = $tag['category'];
        $child = empty($tag['child']) ? 'false' : $tag['child'];
        $row = empty($tag['row']) ? '10' : $tag['row'];
        $field = empty($tag['field']) ? 'true' : $tag['field'];

        $parse = '<?php ';
        $parse .= '$category=D(\'Category\')->getChildrenId(' . $cate . ');';
        $parse .= '$__LIST__ = D(\'Document\')->page(!empty($_GET["p"])?$_GET["p"]:1,' . $row . ')->lists(';
        $parse .= '$category, \'`level` DESC,`id` DESC\', 1,';
        $parse .= $field . ');';
        $parse .= ' ?>';
        $parse .= '<volist name="__LIST__" id="' . $name . '">';
        $parse .= $content;
        $parse .= '</volist>';
        return $parse;
    }

    /* A list of recommended position */
    public function _position($tag, $content)
    {
        $pos = $tag['pos'];
        $cate = $tag['cate'];
        $limit = empty($tag['limit']) ? 'null' : $tag['limit'];
        $field = empty($tag['field']) ? 'true' : $tag['field'];
        $name = $tag['name'];
        $parse = '<?php ';
        $parse .= '$__POSLIST__ = D(\'Document\')->position(';
        $parse .= $pos . ',';
        $parse .= $cate . ',';
        $parse .= $limit . ',';
        $parse .= $field . ');';
        $parse .= ' ?>';
        $parse .= '<volist name="__POSLIST__" id="' . $name . '">';
        $parse .= $content;
        $parse .= '</volist>';
        return $parse;
    }

    /* List data paging */
    public function _page($tag)
    {
        $cate = $tag['cate'];
        $listrow = $tag['listrow'];
        $parse = '<?php ';
        $parse .= '$__PAGE__ = new \Think\Page(get_list_count(' . $cate . '), ' . $listrow . ');';
        $parse .= 'echo $__PAGE__->show();';
        $parse .= ' ?>';
        return $parse;
    }

    /* Get the nextArticleinformation */
    public function _next($tag, $content)
    {
        $name = $tag['name'];
        $info = $tag['info'];
        $parse = '<?php ';
        $parse .= '$' . $name . ' = D(\'Document\')->next($' . $info . ');';
        $parse .= ' ?>';
        $parse .= '<notempty name="' . $name . '">';
        $parse .= $content;
        $parse .= '</notempty>';
        return $parse;
    }

    /* ObtainPrevious articleinformation */
    public function _prev($tag, $content)
    {
        $name = $tag['name'];
        $info = $tag['info'];
        $parse = '<?php ';
        $parse .= '$' . $name . ' = D(\'Document\')->prev($' . $info . ');';
        $parse .= ' ?>';
        $parse .= '<notempty name="' . $name . '">';
        $parse .= $content;
        $parse .= '</notempty>';
        return $parse;
    }

    /* Paragraph data paging */
    public function _partpage($tag)
    {
        $id = $tag['id'];
        if (isset($tag['listrow'])) {
            $listrow = $tag['listrow'];
        } else {
            $listrow = 10;
        }
        $parse = '<?php ';
        $parse .= '$__PAGE__ = new \Think\Page(get_part_count(' . $id . '), ' . $listrow . ');';
        $parse .= 'echo $__PAGE__->show();';
        $parse .= ' ?>';
        return $parse;
    }

    /* List paragraph */
    public function _partlist($tag, $content)
    {
        $id = $tag['id'];
        $field = $tag['field'];
        $name = $tag['name'];
        if (isset($tag['listrow'])) {
            $listrow = $tag['listrow'];
        } else {
            $listrow = 10;
        }
        $parse = '<?php ';
        $parse .= '$__PARTLIST__ = D(\'Document\')->part(' . $id . ',  !empty($_GET["p"])?$_GET["p"]:1, \'' . $field . '\',' . $listrow . ');';
        $parse .= ' ?>';
        $parse .= '<?php $page=(!empty($_GET["p"])?$_GET["p"]:1)-1; ?>';
        $parse .= '<volist name="__PARTLIST__" id="' . $name . '">';
        $parse .= $content;
        $parse .= '</volist>';
        return $parse;
    }
}