<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2014 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------

namespace Think;

class Page{
    public $firstRow; // 起始行数
    public $listRows; // 列表每页显示行数
    public $parameter; // 分页跳转时要带的参数
    public $totalRows; // 总行数
    public $totalPages; // 分页总页面数
    public $rollPage   = 11;// 分页栏每页显示的页数
    public $lastSuffix = true; // 最后一页是否显示总页数

    private $p       = 'p'; //Paging parameter name
    private $url     = ''; //Current link URL
    private $nowPage = 1;

    // 分页显示定制
    private $config  = array(
        'header' => '<span class="rows">Total %TOTAL_ROW% Records</span>',
        'prev'   => '<<',
        'next'   => '>>',
        'first'  => '1...',
        'last'   => '...%TOTAL_PAGE%',
        'theme'  => '%FIRST% %UP_PAGE% %LINK_PAGE% %DOWN_PAGE% %END%',
    );

    /**
     * Architecture function
     * @param int $totalRows  Total number of records
     * @param int $listRows  Number of records displayed per page
     * @param array $parameter  Parameters of page jump
     */
    public function __construct($totalRows, $listRows=20, $parameter = array()) {
        C('VAR_PAGE') && $this->p = C('VAR_PAGE'); //Set the name of the paging parameter
        /* Basic Settings */
        $this->totalRows  = $totalRows; //Set the total number of records
        $this->listRows   = $listRows;  //Set the number of rows displayed on each page
        $this->parameter  = empty($parameter) ? $_GET : $parameter;
        $this->nowPage    = empty($_GET[$this->p]) ? 1 : intval($_GET[$this->p]);
        $this->nowPage    = $this->nowPage>0 ? $this->nowPage : 1;
        $this->firstRow   = $this->listRows * ($this->nowPage - 1);
    }

    /**
     * Customize paging link settings
     * @param string $name  Set name
     * @param string $value settings
     */
    public function setConfig($name,$value) {
        if(isset($this->config[$name])) {
            $this->config[$name] = $value;
        }
    }

    /**
     * Generate link URL
     * @param  integer $page number
     * @return string
     */
    private function url($page){
        return str_replace(urlencode('[PAGE]'), $page, $this->url);
    }

    /**
     * Assemble the pagination link
     * @return string
     */
    public function show() {
        if (0 == $this->totalRows) return '';
    
        /* Generate URL */
        $this->parameter[$this->p] = '[PAGE]';
        $this->url = U(ACTION_NAME, $this->parameter);
        
        /* Calculate paging information */
        $division = (int)($this->totalRows) / ($this->listRows);
        $this->totalPages = ceil($division); //total pages
        if (!empty($this->totalPages) && $this->nowPage > $this->totalPages) {
            $this->nowPage = $this->totalPages;
        }
    
        /* Calculate paging temporary variables */
        $now_cool_page      = $this->rollPage / 2;
        $now_cool_page_ceil = ceil($now_cool_page);
        $this->lastSuffix && $this->config['last'] = $this->totalPages;
    
        // Previous page
        $up_row  = $this->nowPage - 1;
        $up_page = $up_row > 0 ? '<li class="page-item"><a class="page-link" aria-label="Previous" href="' . $this->url($up_row) . '">' . $this->config['prev'] . '</a></li>' : '<li class="page-item disabled"><span class="page-link">' . $this->config['prev'] . '</span></li>';
    
        // Next page
        $down_row  = $this->nowPage + 1;
        $down_page = ($down_row <= $this->totalPages) ? '<li class="page-item"><a class="page-link" aria-label="Next" href="' . $this->url($down_row) . '">' . $this->config['next'] . '</a></li>' : '<li class="page-item disabled"><span class="page-link">' . $this->config['next'] . '</span></li>';
    
        // First page
        $the_first = '';
        if ($this->totalPages > $this->rollPage && ($this->nowPage - $now_cool_page) >= 1) {
            $the_first = '<li class="page-item"><a class="page-link" href="' . $this->url(1) . '">' . $this->config['first'] . '</a></li>';
        }
    
        // Last page
        $the_end = '';
        if ($this->totalPages > $this->rollPage && ($this->nowPage + $now_cool_page) < $this->totalPages) {
            $the_end = '<li class="page-item"><a class="page-link" href="' . $this->url($this->totalPages) . '">' . $this->config['last'] . '</a></li>';
        }
    
        // Digital links
        $link_page = "";
        for ($i = 1; $i <= $this->rollPage; $i++) {
            if (($this->nowPage - $now_cool_page) <= 0) {
                $page = $i;
            } elseif (($this->nowPage + $now_cool_page - 1) >= $this->totalPages) {
                $page = $this->totalPages - $this->rollPage + $i;
            } else {
                $page = $this->nowPage - $now_cool_page_ceil + $i;
            }
            if ($page > 0 && $page != $this->nowPage) {
                if ($page <= $this->totalPages) {
                    $link_page .= '<li class="page-item"><a class="page-link" href="' . $this->url($page) . '">' . $page . '</a></li>';
                } else {
                    break;
                }
            } else {
                if ($page > 0 && $this->totalPages != 1) {
                    $link_page .= '<li class="page-item active"><span class="page-link">' . $page . '</span></li>';
                }
            }
        }
    
        // Replace page content
        $page_str = str_replace(
            array('%HEADER%', '%NOW_PAGE%', '%UP_PAGE%', '%DOWN_PAGE%', '%FIRST%', '%LINK_PAGE%', '%END%', '%TOTAL_ROW%', '%TOTAL_PAGE%'),
            array($this->config['header'], $this->nowPage, $up_page, $down_page, $the_first, $link_page, $the_end, $this->totalRows, $this->totalPages),
            $this->config['theme']);
    
        return "<nav><ul class='pagination'>{$page_str}</ul></nav>";
    }
    
    public function show_old() {
        if(0 == $this->totalRows) return '';

        /* Generate URL */
        $this->parameter[$this->p] = '[PAGE]';
        $this->url = U(ACTION_NAME, $this->parameter);
       
        /* Calculate paging information */
        $division=(int)($this->totalRows) / ($this->listRows);
        $this->totalPages = ceil($division); //total pages
        if(!empty($this->totalPages) && $this->nowPage > $this->totalPages) {
            $this->nowPage = $this->totalPages;
        }

        /* Calculate paging temporary variables */
        $now_cool_page      = $this->rollPage/2;
        $now_cool_page_ceil = ceil($now_cool_page);
        $this->lastSuffix && $this->config['last'] = $this->totalPages;

        //Previous page
        $up_row  = $this->nowPage - 1;
        $up_page = $up_row > 0 ? '<li><a aria-label="Previous" href="' . $this->url($up_row) . '">' . $this->config['prev'] . '</a></li>' : '';

        //Next page
        $down_row  = $this->nowPage + 1;
        $down_page = ($down_row <= $this->totalPages) ? '<li><a aria-label="Next" href="' . $this->url($down_row) . '">' . $this->config['next'] . '</a></li>' : '';

        //first page
        $the_first = '';
        if($this->totalPages > $this->rollPage && ($this->nowPage - $now_cool_page) >= 1){
            $the_first = '<li><a href="' . $this->url(1) . '">' . $this->config['first'] . '</a></li>';
        }

        //Last page
        $the_end = '';
        if($this->totalPages > $this->rollPage && ($this->nowPage + $now_cool_page) < $this->totalPages){
            $the_end = '<li><a href="' . $this->url($this->totalPages) . '">' . $this->config['last'] . '</a></li>';
        }

        //Digital connection
        $link_page = "";
        for($i = 1; $i <= $this->rollPage; $i++){
            if(($this->nowPage - $now_cool_page) <= 0 ){
                $page = $i;
            }elseif(($this->nowPage + $now_cool_page - 1) >= $this->totalPages){
                $page = $this->totalPages - $this->rollPage + $i;
            }else{
                $page = $this->nowPage - $now_cool_page_ceil + $i;
            }
            if($page > 0 && $page != $this->nowPage){

                if($page <= $this->totalPages){
                    $link_page .= '<li><a href="' . $this->url($page) . '">' . $page . '</a></li>';
                }else{
                    break;
                }
            }else{
                if($page > 0 && $this->totalPages != 1){
                    $link_page .= '<li class="active"><span>' . $page . '</span></li>';
                }
            }
        }

        //Replace page content
        $page_str = str_replace(
            array('%HEADER%', '%NOW_PAGE%', '%UP_PAGE%', '%DOWN_PAGE%', '%FIRST%', '%LINK_PAGE%', '%END%', '%TOTAL_ROW%', '%TOTAL_PAGE%'),
            array($this->config['header'], $this->nowPage, $up_page, $down_page, $the_first, $link_page, $the_end, $this->totalRows, $this->totalPages),
            $this->config['theme']);
        return "<nav><ul class='pagination pagination-lg'>{$page_str}</ul></nav>";
    }
}