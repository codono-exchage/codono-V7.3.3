$(function () {
    /*判断滚动条距离顶端的高度*/
    $(window).scroll(function(){
        if($(window).scrollTop() >= 200){
            $(".header-sliding").addClass("show"); // 开始淡入
        } else{
            $(".header-sliding").removeClass("show");// 如果小于等于 200 淡出
        }
    });


    /*鼠标放上去出现下载二维码*/
    $(".hoverEwm1").hover(
        function(){
            $(this).append("<img src='/Public/Home/image/index/Android.14c5c18.png'/>");
        },
        function(){
            $(this).find("img").remove();
        }
    );
    
    $(".hoverEwm2").hover(
        function(){
            $(this).append("<img src='/Public/Home/image/index/ios.14c5c18.png'/>");
        },
        function(){
            $(this).find("img").remove();
        }
    );

    /*产品模块鼠标放上去的效果*/
    $(".product-title a").hover(function(){
        var idx=$(this).index();
        $(".product-content>div.product-left").addClass("dis");
        $(".product-content>div.product-right a").addClass("dis");
        $(".product-title a").removeClass("bgStyle");

        $(this).addClass("bgStyle");
        $(".product-content>div.product-left").eq(idx).removeClass("dis");
        $(".product-content>div.product-right a").eq(idx).removeClass("dis");
    });


    /*添加自选*/
    $(".star ").click(function(e){
        e.stopPropagation();
        e.preventDefault();
        if($(this).hasClass("no-optional")){
            $(this).removeClass("no-optional");
            $(this).addClass("optional");
            $(".tips-status").addClass("show-tips");
            setTimeout(function(){
                $(".tips-status").removeClass("show-tips");
            },2000)
        }else if($(this).hasClass("optional")){
            $(this).removeClass("optional");
            $(this).addClass("no-optional");
            $(".tips-status").removeClass("show-tips");
        }
    });

    /*数据切换*/
    $(".dataList>.item").click(function(){
        $(".dataList>.item").removeClass("active");
        $(this).addClass("active");
    });


    /*返回顶部显示和隐藏*/
    $(window).scroll(function() {
        var topScroll = $(window).height()/2;
        if ($(window).scrollTop() > topScroll) {
            $(".down-load-content").append("<a class='back-to-top cn'><span>返回顶部</span></a>");
        }
        else {
            $(".down-load-content").find(".back-to-top").remove();
        }

    });
    // 点击返回顶部
    $(document).on("click",".back-to-top",function() {
        $("html,body").animate({scrollTop:0}, 500);
    });

    /*左右按钮切换*/
    $(".leftRight a").click(function(){
        $(this).css("opacity","0.5");
        $(this).siblings("a").css("opacity","1");
    });


    /*底部二维码*/
    $(".footer-left ul li").hover(
        function () {
            $(this).find("div").show();
        },
        function(){
            $(this).find("div").hide();
        }
    );


    /*轮播*/
    var  slideShow=$(".banner-inner");
    var  showNumber=slideShow.find(".bullet span");//获取点击按钮

    var timer=null; //定时器返回值，主要用于关闭定时器
    var iNow=0; //iNow为正在展示的图片索引值，当用户打开网页时首先显示第一张图，即索引值为0

    showNumber.on("click",function(){  //为每个按钮绑定一个点击事件
        $(this).addClass("active").siblings().removeClass("active"); //按钮点击时为这个按钮添加高亮状态，并且将其他按钮高亮状态去掉
        var index=$(this).index(); //获取哪个按钮被点击，也就是找到被点击按钮的索引值
        $(".banner-inner>div").eq(index).show().siblings(".carousel").hide();
    });

    timer=setInterval(function(){ //打开定时器
        iNow++;    //让图片的索引值次序加1，这样就可以实现顺序轮播图片
        if(iNow>showNumber.length-1){ //当到达最后一张图的时候，让iNow赋值为第一张图的索引值，轮播效果跳转到第一张图重新开始
            iNow=0;
        }
        showNumber.eq(iNow).trigger("click"); //模拟触发数字按钮的click
    },4000); //4000为轮播的时间


    /*跳转到币币交易*/
    $(".market>div").click(function () {
        window.location.href="index.html";
    })


});