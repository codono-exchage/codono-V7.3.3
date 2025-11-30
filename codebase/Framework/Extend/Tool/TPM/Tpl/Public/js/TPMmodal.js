function tpm_alert(msg,callback,title){
    title=title||'system message';   
    var $modal=$('<div><div class="tpm_modal_head"><h3>'+title+'</h3></div><div class="tpm_modal_body">'+msg+'</div><div class="tpm_modal_foot"><button class="tpm_modal_ok"  type="button">confirm</button></div></div>');
    $modal.find('.tpm_modal_foot>button').on('click',function(){
        tpm_close_float_box();
    });
   var id=Modernizr.mq("(max-width:767px)")?'tpm_modal_phone':'tpm_modal';
   tpm_show_float_box($modal,id);
   if($.isFunction(callback)){
        $('#'+id).on('end',function(){
            callback();
            $('#'+id).off('end'); 
        });
   }
}

function tpm_info(msg,callback){
   var id=Modernizr.mq("(max-width:767px)")?'tpm_info_phone':'tpm_info';
   if(0==$('#'+id).size()){
        $('<div id="'+id+'"></div>').appendTo('body').on('webkitTransitionEnd oTransitionEnd otransitionend transitionend',function(){
            if(!$(this).is('.in')){
                $(this).hide();
                if($.isFunction(callback)) callback();
            }
        });
   }
   //display
   $('#'+id).show();
   $('#'+id).html(msg);
   $('#'+id).offset();//Forced recirculation
   $('#'+id).addClass('in');
   //3After hiding seconds
   setTimeout(function(){
       $('#'+id).removeClass('in');
       if(!Modernizr.csstransitions){
            $('#'+id).hide();
            if($.isFunction(callback)) callback();
       }
   },1000)
}

function tpm_confirm(msg,callback,title){
    title=title||'please confirm';   
    var $modal=$('<div><div class="tpm_modal_head"><h3>'+title+'</h3></div><div class="tpm_modal_body">'+msg+'</div><div class="tpm_modal_foot"><button class="tpm_modal_cancel"  type="button">cancel</button><button class="tpm_modal_ok"  type="button">confirm</button></div></div>');
   var id=Modernizr.mq("(max-width:767px)")?'tpm_modal_phone':'tpm_modal';
    $modal.find('.tpm_modal_foot>button').on('click',function(){
        if($(this).is('.tpm_modal_ok')){
            $('#'+id).on('end',function(){
                if($.isFunction(callback)) callback();
                $('#'+id).off('end');
            })        
        }
        tpm_close_float_box();
    });
   tpm_show_float_box($modal,id);
}

function tpm_popurl(url,callback,title){
  var text='<div><div class="tpm_modal_head"><h3>'+title+'</h3>  <a class="tpm_modal_close" href="javascript:tpm_close_float_box();">×</a></div><div id="tpm_modal_body" class="tpm_modal_body">loading</div></div>';
  tpm_show_float_box(text,'tpm_modal');
  if($.isFunction(callback)){
      $('#tpm_modal').on('end',function(){
        callback();
        $('#tpm_modal').off('end');
      }); 
  }
  //Increase random number,Prevent caching
  url+=-1==url.indexOf('?')?'?tpm_r='+Math.random():'&tpm_r='+Math.random();
  //Determine whetherhttpbeginning. 
  if('http'==url.substr(0,4).toLowerCase()){
    $(window).off('message.tpm');
    //WithhttpBeginning withiframedisplay.
    $(window).on('message.tpm',function(e){
      if('tpm_close_float_box'==e.originalEvent.data){
            tpm_close_float_box(); 
      } 
    }); 
    $('#tpm_modal_body').html('<iframe src="'+url+'" id="tpm_modal_iframe" name="tpm_modal_iframe"></iframe>');
  }else{
    //Determine whether to loadtpmClass Library。
    if(!window.TPM){
        $('#tpm_modal_body').load(url);
    }else{
         window.TPMshowAjaxWait=false;
         TPM.http(url,'tpm_modal_body'); 
    }
  }
}

function tpm_show_float_box(text,id){
    tpm_show_backdrop();
    //createmodalFloor
    if(0==$('#'+id).size()){
        $('<div id="'+id+'"></div>').appendTo('body').on('webkitTransitionEnd oTransitionEnd otransitionend transitionend',function(){
            if(!$(this).is('.in')){
                $(this).trigger('end');
                $(this).hide();
             }
        });
    }
    $('#'+id).empty();
    //Join pop-up box contents
    $(text).appendTo('#'+id);
    //displaymodalFloor
    $('#'+id).show()
    $('#'+id).offset();
    //Add tomodalFloorinstyle
    $('#'+id).addClass('in');
}


function tpm_close_float_box(){
    //in caseiframeSentpostMessageTo the parent window
    if(parent!=window){
        parent.postMessage('tpm_close_float_box','*');
        return ;
    }
    tpm_hide_backdrop();
    //deletemodalFloorinstyle 
    $('#tpm_modal,#tpm_modal_phone').removeClass('in');
    if(!Modernizr.csstransitions){
        $('#tpm_modal,#tpm_modal_phone').hide();
        $('#tpm_modal,#tpm_modal_phone').trigger('end');
     }
}



//Display layer enveloped
function tpm_show_backdrop(){
    if(0==$('#tpm_backdrop').size()){
       $('<div id="tpm_backdrop"></div>').appendTo('body').on('webkitTransitionEnd oTransitionEnd otransitionend transitionend',function(){
          if(!$(this).is('.in')) $(this).hide();
       });
    }
    $('#tpm_backdrop').show();
    $('#tpm_backdrop').offset();//Forced recirculation
    $('#tpm_backdrop').addClass('in');
}

//Hide enveloped layer
function tpm_hide_backdrop(){
    $('#tpm_backdrop').removeClass('in');
    if(!Modernizr.csstransitions) $('#tpm_backdrop').hide();
}
