<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2021 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------

namespace Think;
class PageAjax {

    public $rollPage = 5;
    public $parameter  ;
    public $listRows = 20;
    public $firstRow ;
    protected $totalPages  ;
    protected $totalRows  ;
    protected $nowPage    ;
    protected $coolPages   ;
    protected $varPage;
    protected $ajax_func;
    
    protected $config  = array(
        'header' => '<span class="rows">Total %TOTAL_ROW% Records</span>',
        'first'   => '1..',
        'prev'   => '<<',
        'next'   => '>>',
        'last'   => '...%TOTAL_PAGE%',
        'theme'  => '%FIRST% %UP_PAGE% %LINK_PAGE% %DOWN_PAGE% %END% %HEADER%',
    );



    public function __construct($totalRows,$listRows='',$ajax_func,$parameter='') {
        $this->totalRows = $totalRows;
        $this->ajax_func = $ajax_func;
        $this->parameter = $parameter;
        $this->varPage = C('VAR_PAGE') ? C('VAR_PAGE') : 'p' ;
        if(!empty($listRows)) {
            $this->listRows = intval($listRows);
        }
        $this->totalPages = ceil($this->totalRows/$this->listRows);     //总页数
        $this->coolPages  = ceil($this->totalPages/$this->rollPage);
        $this->nowPage  = !empty($_GET[$this->varPage])?intval($_GET[$this->varPage]):1;
        if(!empty($this->totalPages) && $this->nowPage>$this->totalPages) {
            $this->nowPage = $this->totalPages;
        }
        $this->firstRow = $this->listRows*($this->nowPage-1);
    }

    public function setConfig($name,$value) {
        if(isset($this->config[$name])) {
            $this->config[$name]    =   $value;
        }
    }


    public function show() {
        if(0 == $this->totalRows) return '';
        $p = $this->varPage;
        $nowCoolPage      = ceil($this->nowPage/$this->rollPage);
        $url  =  $_SERVER['REQUEST_URI'].(strpos($_SERVER['REQUEST_URI'],'?')?'':"?").$this->parameter;
        $parse = parse_url($url);
        if(isset($parse['query'])) {
            parse_str($parse['query'],$params);
            unset($params[$p]);
            $url   =  $parse['path'].'?'.http_build_query($params);
        }
        //上下翻页字符串
        $upRow   = $this->nowPage-1;
        $downRow = $this->nowPage+1;
        if ($upRow>0){
            $upPage="<a class='prev' id='big' href='javascript:".$this->ajax_func."(".$upRow.")'>".$this->config['prev']."</a>";
        }else{
            $upPage="";
        }

        if ($downRow <= $this->totalPages){
            $downPage="<a id='big' href='javascript:".$this->ajax_func."(".$downRow.")'>".$this->config['next']."</a>";
        }else{
            $downPage="";
        }
        // << < > >>
        if($nowCoolPage == 1){
            $theFirst = "";
            $prePage = "";
        }else{
            $preRow =  $this->nowPage-$this->rollPage;
            $prePage = "<a id='big' href='javascript:".$this->ajax_func."(".$preRow.")'>On ".$this->rollPage." Page</a>";
            $theFirst = "<a id='big' href='javascript:".$this->ajax_func."(1)' >".$this->config['first']."</a>";
        }
        if($nowCoolPage == $this->coolPages){
            $nextPage = "";
            $theEnd="";
        }else{
            $nextRow = $this->nowPage+$this->rollPage;
            $theEndRow = $this->totalPages;
            $nextPage = "<a id='big' href='javascript:".$this->ajax_func."(".$nextRow.")' >On ".$this->rollPage." Page</a>";
            $theEnd = "<a id='big' href='javascript:".$this->ajax_func."(".$theEndRow.")' >".$this->config['last']."</a>";
        }
        // 1 2 3 4 5
        $linkPage = "";
        for($i=1;$i<=$this->rollPage;$i++){
            $page=($nowCoolPage-1)*$this->rollPage+$i;
            if($page!=$this->nowPage){
                if($page<=$this->totalPages){
                    $linkPage .= "<a id='big' href='javascript:".$this->ajax_func."(".$page.")'>$page</a>";
                }else{
                    break;
                }
            }else{
                if($this->totalPages != 1){
                    $linkPage .= "&nbsp;<span class='current'>".$page."</span>";
                }
            }
        }

        $page_str = str_replace(
            array('%HEADER%', '%NOW_PAGE%', '%UP_PAGE%', '%DOWN_PAGE%', '%FIRST%', '%LINK_PAGE%', '%END%', '%TOTAL_ROW%', '%TOTAL_PAGE%'),
            array($this->config['header'], $this->nowPage, $upPage, $downPage, $theFirst, $linkPage, $theEnd, $this->totalRows, $this->totalPages),
            $this->config['theme']);
        return '<div class="page">'.$page_str.'</div>';
    }

}

