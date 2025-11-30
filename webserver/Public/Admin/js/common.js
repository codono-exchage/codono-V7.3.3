//domUpon completion of the execution loadjs
;$(function(){

    //Select implementation
    $(".check-all").click(function(){
        $(".ids").prop("checked", this.checked);
    });
    $(".ids").click(function(){
        var option = $(".ids");
        option.each(function(i){
            if(!this.checked){
                $(".check-all").prop("checked", false);
                return false;
            }else{
                $(".check-all").prop("checked", true);
            }
        });
    });

    //ajax getrequest
    $('.ajax-get').click(function(){
        var confirm_msg = $(this).attr('confirm-msg') ? $(this).attr('confirm-msg') : 'To do this you confirm';
        var target;
        var that = this;
        if ( $(this).hasClass('confirm') ) {
            if(!confirm(confirm_msg)){
                return false;
            }
        }
        if ( (target = $(this).attr('href')) || (target = $(this).attr('url')) ) {
            $.get(target).success(function(data){
                if (data.status==1) {
                    if (data.url) {
                        updateAlert(data.info + ' The page is about to automatically jump~','alert-success');
                    }else{
                        updateAlert(data.info,'alert-success');
                    }
                    setTimeout(function(){
                        if (data.url) {
                            location.href=data.url;
                        }else if( $(that).hasClass('no-refresh')){
                            $('#top-alert').find('button').click();
                        }else{
                            location.reload();
                        }
                    },1500);
                }else{
                    updateAlert(data.info);
                    setTimeout(function(){
                        if (data.url) {
                            location.href=data.url;
                        }else{
                            $('#top-alert').find('button').click();
                        }
                    },1500);
                }
            });

        }
        return false;
    });

    //ajax post submitrequest
    $('.ajax-post').click(function(){
        var confirm_msg = $(this).attr('confirm-msg') ? $(this).attr('confirm-msg') : 'To do this you confirm';

        var target,query,form;
        var target_form = $(this).attr('target-form');
        var that = this;
        var nead_confirm=false;
        if( ($(this).attr('type')=='submit') || (target = $(this).attr('href')) || (target = $(this).attr('url')) ){

            form = $('.'+target_form);

            if ($(this).attr('hide-data') === 'true'){//Features that can also be used in countless sources

                form = $('.hide-data');
                query = form.serialize();
            }else if (form.get(0)==undefined){
                console.log('c1');
                confirm(confirm_msg);
                return false;
            }else if ( form.get(0).nodeName=='FORM' ){
                if ( $(this).hasClass('confirm') ) {
                    console.log('c2');
                    if(!confirm(confirm_msg)){
                        return false;
                    }
                }
                if($(this).attr('url') !== undefined){
                    target = $(this).attr('url');
                }else{
                    target = form.get(0).action;
                }
                query = form.serialize();
            }else if( form.get(0).nodeName=='INPUT' || form.get(0).nodeName=='SELECT' || form.get(0).nodeName=='TEXTAREA') {
                form.each(function(k,v){
                    if(v.type=='checkbox' && v.checked==true){
                        nead_confirm = true;
                    }
                })
                if ( nead_confirm && $(this).hasClass('confirm') ) {
                    console.log('c2');
                    if(!confirm(confirm_msg)){
                        return false;
                    }
                }
                query = form.serialize();
            }else{
                if ( $(this).hasClass('confirm') ) {
                    console.log('c3');
                    if(!confirm(confirm_msg)){
                        return false;
                    }
                }
                query = form.find('input,select,textarea').serialize();
            }
            $(that).addClass('disabled').attr('autocomplete','off').prop('disabled',true);
            $.post(target,query).success(function(data){
                if (data.status==1) {
                    if (data.url) {
                        updateAlert(data.info + ' The page is about to automatically jump~','alert-success');
                    }else{
                        updateAlert(data.info ,'alert-success');
                    }
                    setTimeout(function(){
                        if (data.url) {
                            location.href=data.url;
                        }else if( $(that).hasClass('no-refresh')){
                            $('#top-alert').find('button').click();
                            $(that).removeClass('disabled').prop('disabled',false);
                        }else{
                            location.reload();
                        }
                    },1500);
                }else{
                    updateAlert(data.info);
                    setTimeout(function(){
                        if (data.url) {
                            location.href=data.url;
                        }else{
                            $('#top-alert').find('button').click();
                            $(that).removeClass('disabled').prop('disabled',false);
                        }
                    },1500);
                }
            });
        }
        return false;
    });

    /**Top bar warning*/
    var content = $('#main');
    var top_alert = $('#top-alert');
    top_alert.find('.close').on('click', function () {
        top_alert.removeClass('block').slideUp(200);
        // content.animate({paddingTop:'-=55'},200);
    });

    window.updateAlert = function (text,c) {
        text = text||'default';
        c = c||false;
        if ( text!='default' ) {
            top_alert.find('.alert-content').text(text);
            if (top_alert.hasClass('block')) {
            } else {
                top_alert.addClass('block').slideDown(200);
                // content.animate({paddingTop:'+=55'},200);
            }
        } else {
            if (top_alert.hasClass('block')) {
                top_alert.removeClass('block').slideUp(200);
                // content.animate({paddingTop:'-=55'},200);
            }
        }
        if ( c!=false ) {
            top_alert.removeClass('alert-error alert-warn alert-info alert-success').addClass(c);
        }
    };
/*
    //Push buttongroup
    (function(){
        //Push buttongroup(Rollover display)
        $(".btn-group").mouseenter(function(){
            var userMenu = $(this).children(".dropdown ");
            var icon = $(this).find(".btn i");
            icon.addClass("btn-arrowup").removeClass("btn-arrowdown");
            userMenu.show();
            clearTimeout(userMenu.data("timeout"));
        }).mouseleave(function(){
            var userMenu = $(this).children(".dropdown");
            var icon = $(this).find(".btn i");
            icon.removeClass("btn-arrowup").addClass("btn-arrowdown");
            userMenu.data("timeout") && clearTimeout(userMenu.data("timeout"));
            userMenu.data("timeout", setTimeout(function(){userMenu.hide()}, 100));
        });

        //Push buttongroup(Mouse click display)
        // $(".btn-group-click .btn").click(function(){
        //     var userMenu = $(this).next(".dropdown ");
        //     var icon = $(this).find("i");
        //     icon.toggleClass("btn-arrowup");
        //     userMenu.toggleClass("block");
        // });
        $(".btn-group-click .btn").click(function(e){
            if ($(this).next(".dropdown").is(":hidden")) {
                $(this).next(".dropdown").show();
                $(this).find("i").addClass("btn-arrowup");
                e.stopPropagation();
            }else{
                $(this).find("i").removeClass("btn-arrowup");
            }
        })
        $(".dropdown").click(function(e) {
            e.stopPropagation();
        });
        $(document).click(function() {
            $(".dropdown").hide();
            $(".btn-group-click .btn").find("i").removeClass("btn-arrowup");
        });
    })();
*/
    
    
    // Independent domainFormsObtainfocusstyle
    $(".text").focus(function(){
        $(this).addClass("focus");
    }).blur(function(){
        $(this).removeClass('focus');
    });
    $("textarea").focus(function(){
        $(this).closest(".textarea").addClass("focus");
    }).blur(function(){
        $(this).closest(".textarea").removeClass("focus");
    });
});

/* Upload image preview pop-up layer */
$(function(){
    $(window).resize(function(){
        var winW = $(window).width();
        var winH = $(window).height();
        $(".upload-img-box").click(function(){
            //in caseNoimagethenDo not show
            if($(this).find('img').attr('src') === undefined){
                return false;
            }
            // createpop upframeas well asObtainpop upimage
            var imgPopup = "<div id=\"uploadPop\" class=\"upload-img-popup\"></div>"
            var imgItem = $(this).find(".upload-pre-item").html();

            //in casePop-up layerexist，Can no longerpop up
            var popupLen = $(".upload-img-popup").length;
            if( popupLen < 1 ) {
                $(imgPopup).appendTo("body");
                $(".upload-img-popup").html(
                    imgItem + "<a class=\"close-pop\" href=\"javascript:;\" title=\"shut down\"></a>"
                );
            }

            // Positioning pop-up layer
            var uploadImg = $("#uploadPop").find("img");
            var popW = uploadImg.width();
            var popH = uploadImg.height();
            var left = (winW -popW)/2;
            var top = (winH - popH)/2 + 50;
            $(".upload-img-popup").css({
                "max-width" : winW * 0.9,
                "left": left,
                "top": top
            });
        });

        // Close the popup
        $("body").on("click", "#uploadPop .close-pop", function(){
            $(this).parent().remove();
        });
    }).resize();

    // Zoom Image
    function resizeImg(node,isSmall){
        if(!isSmall){
            $(node).height($(node).height()*1.2);
        } else {
            $(node).height($(node).height()*0.8);
        }
    }
})

//Tab switching(No Next)
function showTab() {
    $(".tab-nav li").click(function(){
        var self = $(this), target = self.data("tab");
        self.addClass("current").siblings(".current").removeClass("current");
        window.location.hash = "#" + target.substr(3);
        $(".tab-pane.in").removeClass("in");
        $("." + target).addClass("in");
    }).filter("[data-tab=tab" + window.location.hash.substr(1) + "]").click();
}

//Tab switching(Next there)
function nextTab() {
    $(".tab-nav li").click(function(){
        var self = $(this), target = self.data("tab");
        self.addClass("current").siblings(".current").removeClass("current");
        window.location.hash = "#" + target.substr(3);
        $(".tab-pane.in").removeClass("in");
        $("." + target).addClass("in");
        showBtn();
    }).filter("[data-tab=tab" + window.location.hash.substr(1) + "]").click();

    $("#submit-next").click(function(){
        $(".tab-nav li.current").next().click();
        showBtn();
    });
}

// Next button switch
function showBtn() {
    var lastTabItem = $(".tab-nav li:last");
    if( lastTabItem.hasClass("current") ) {
        $("#submit").removeClass("hidden");
        $("#submit-next").addClass("hidden");
    } else {
        $("#submit").addClass("hidden");
        $("#submit-next").removeClass("hidden");
    }
}

//Highlight navigation
function highlight_subnav(url){
    $('.side-sub-menu').find('a[href="'+url+'"]').closest('li').addClass('current');
}
