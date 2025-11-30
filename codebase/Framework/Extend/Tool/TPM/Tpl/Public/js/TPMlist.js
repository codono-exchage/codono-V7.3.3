//A list of plug-ins
//Element attributes: data-api,requestapiaddress ； data-datas Request parameter data-tpl Template Address data-tabletpagesize Per tabletdisplayArticle number, data-phonepagesize The number of mobile phones per page
//author : luofei614<http://weibo.com/luofei614>
;(function($){
$.fn.extend({
 'TPMlist':function(options){
     var defaults={
        "param_pagesize":"pagesize",
        "param_page":"page",
        "tabletpagesize":40,
        "phonepagesize":20
     };
    options=$.extend(defaults,options);
    $(this).each(function(){
       //obtainapi
       var api=$(this).data('api');
        //Get request parameters
        var datas=$(this).data('datas');
        //Get template
        var tpl=$(this).data('tpl');
        //Obtaining a data set name
       //obtainpagesize
       var type=$(window).height()>767?'tablet':'phone';
       var defaultpagesize='tablet'==type?options.tabletpagesize:options.phonepagesize;//The default number displayed per page
       var pagesize=$(this).data(type+'pagesize') || defaultpagesize;
       $children=$('<div><div class="list_content">Loading..</div></div>').appendTo(this).find('.list_content');
       //Pull down to refresh
       var sc=$(this).TPMpulltorefresh(function(){
         $children.TPMgetListData(api,datas,tpl,pagesize,1,this,options);
       });
       $children.TPMgetListData(api,datas,tpl,pagesize,1,sc,options);
       
    });
 },
  'TPMgetListData':function(api,datas,tpl,pagesize,page,sc,options){
   var params=datas?datas.split('&'):{};
   var datas_obj={};
   for(var i=0;i<params.length;i++){
        var p=params[i].split('=');
        datas_obj[p[0]]=p[1];
   }
   datas_obj[options.param_pagesize]=pagesize;
   datas_obj[options.param_page]=page;
   var $this=$(this);
   //requestapi
   TPM.sendAjax(api,datas_obj,'get',function(response){
       //Rendertemplate
       $.get(tpl,function(d,x,s){
           var html=TPM.parseTpl(d,response);
           //Determine whetherFirst page, IfFirst page，ClearbeforedatathenAgainload,in caseNot the firstPagesAccording to accumulate
           if(1==page){
                $this.empty(); 
           }
           $this.find('.getmore').remove();//Delete the previous load more
           $this.append(html);
           if(response.currentpage!=response.totalpages){
               //Load More button
               $more=$('<div class="getmore">load more</div>');
               $more.appendTo($this);
               $more.click(function(){
                   $(this).html('Loading...');//TODO Loading style
                    $this.TPMgetListData(api,datas,tpl,pagesize,parseInt($this.data('currentpage'))+1,sc,options); 
               });
           }
           sc.refresh();//iscroll refresh;
            //Record the current page
           $this.data('currentpage',response.currentpage);
       },'text')
   });
 },
 //Pull down to refresh
 'TPMpulltorefresh':function(cb){
       //increasePull down to refreshpromptFloor
       var $pulldown=$('<div class="pullDown"><span class="pullDownIcon"></span><span class="pullDownLabel">Pull down to refresh</span></div>')
       $pulldown.prependTo($(this).children());
       var offset=$pulldown.outerHeight(true);
       var  myScroll=new iScroll($(this)[0],{
           useTransition: true,
           topOffset:offset,
           hideScrollbar:true,
           onRefresh: function () {
               $pulldown.removeClass('loading');
               $pulldown.find('.pullDownLabel').html('Pull down to refresh');
            },
            onScrollMove: function () {
                if (this.y > 5 && !$pulldown.is('.flip')) {
                    $pulldown.addClass('flip');
                    $pulldown.find('.pullDownLabel').html('You can refresh release');
                    this.minScrollY = 0;
                } else if (this.y < 5 && $pulldown.is('.flip')) {
                    $pulldown.removeClass('flip');
                    $pulldown.find('.pullDownLabel').html('Pull down to refresh');
                    this.minScrollY = -offset;
                }
            },
            onScrollEnd: function () {
                if($pulldown.is('.flip')){
                    $pulldown.removeClass('flip');
                    $pulldown.addClass('loading');
                    $pulldown.find('.pullDownLabel').html('Loading...');
                    cb.call(this);//Trigger callback function
                }
           }
    });
       return myScroll;
 }

});
})(jQuery);

