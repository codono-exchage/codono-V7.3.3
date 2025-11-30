///-------------------------------///
$(function(){
	$('.cj').luckDraw({
		width:160, //宽
		height:160, //high
		line:4, //A few lines
		list:4, //Columns
		click:".min_mubox_click" //Click onObjects
	});
});

$.fn.extend({
	luckDraw:function(data){
		var anc = $(this); //Grandfather element
		var list = anc.children("li");
		var click; //Click onObjects
		var lineNumber; //A few lines 3
		var	listNumber; //Columns 4
		var thisWidth;
		var thisHeight;
		if(data.line==null){return;}else{lineNumber=data.line;}
		if(data.list==null){return;}else{listNumber=data.list;}
		if(data.width==null){return;}else{thisWidth=data.width;}
		if(data.height==null){return;}else{thisHeight=data.height;}
		if(data.click==null){return;}else{click=data.click;}
		var all = 12 //You should have total
		if(all>list.length){ //in caseactualHow toPieceLess thanYou should have total
			for(var i=0;i<(all-list.length);i++){
				anc.append("<li>"+all+"</li>");
			}
		}
		list = anc.children("li");
		var ix = 0;
		var speed = 200;
		var Countdown = 1000; //Countdown
		var isRun = false;
		var dgTime = 200;
		
		$(click).click(function(){
			if(isRun){
				return;
			}else{
				$(".lottery_dian span").addClass("hover");
/* 				$.ajax({
					url:'/draw/draw',
					type:'get',
					dataType:'json',
					success:function (d) {
						if(!AJAX.ltcb(d)) return;
						if(d.status){
							drawTitle = d.data.title;
							var stime = parseInt(d.data.index);
							dgTime = stime*10 + 280;
							speedUp();
						}else{
							alert(d.msg);
						}
					}
				}); */
				
						//if(d.status){
							drawTitle = "Im a title";
							var stime = 2;
							dgTime = stime*10 + 280;
							speedUp();
						//}else{
						//	alert(d.msg);
						//}
			}
		});
		function speedUp(){ //accelerate
			isRun = true;
			list.removeClass("adcls");
			list.eq(ix).addClass("adcls");
			ix++;
			init(ix);
			speed -= 50;
			if(speed == 100){
				clearTimeout(stop);
				uniform();
			}else{
				var stop = setTimeout(speedUp,speed);
			}
		}
		function uniform(){ //Uniform
			list.removeClass("adcls");
			list.eq(ix).addClass("adcls");
			ix++;
			init(ix);
			Countdown -= 50 ;
			if(Countdown == 0){
				clearTimeout(stop);
				speedDown();
			}else{
				var stop = setTimeout(uniform,speed);
			}
		}
		function speedDown(){ //slow down
			list.removeClass("adcls");
			list.eq(ix).addClass("adcls");
			ix++;
			init(ix);
			speed += 10;
			if(speed == dgTime+20){
				clearTimeout(stop);
				end();
			}else{
				var stop = setTimeout(speedDown,speed);
			}
		}
		function end(){
			var result=new Array("Iphone7","air humidifier","VRglasses","Bluetooth earphone","Polaroid","Somatosensory car balance","custom madeUplate","make persistent efforts","Virtual currency","Phone holder","Driving instrument","TV Plus TV");
			//if(ix==8){
				setTimeout(lottery_no(),5);
			//}else{
				//setTimeout(lottery_get(ix,drawTitle),5);
			//}
			$(".lottery_dian span").removeClass("hover");
		}
		///--Return0
		function init(o){
			if(o == all){
				ix = 0;
			}
		}
		///
		function initB(){
			ix = 0;
			dgTime = 200;
			speed = 500;
			Countdown = 1000;
			isRun = false;
		}
	}

});



//Pop collect their prizes
//Today, the prize has been robbed End
function lottery_over(){
	var html = '';
	html += '<div class="ui-dialog" id="lottery_over" style="width: 600px;background: none; box-shadow:none;"> ' +
		'<div class="lottery_tan po_re">' +
		'<div class="lottery_tan_cha po_ab" onclick="$(\'#lottery_over\').hide() && $(\'.ui-mask\').hide();"><img src="/Public/Home/award/images/lot_tan_cha.png" width="20"></div> ' +
		'<div class="lottery_tan_over"> ' +
		'<img src="/images/lot/lot_tanimg_over.png" width="400" alt="Today, the prize has been robbed End">' +
		'</div>' +
		'</div>' +
		'</div>';
	$('body').prepend(html);
	showDialog('lottery_over');
}
//make persistent efforts
function lottery_no(){
	var html = '';
	html += '<div class="ui-dialog" id="lottery_no" style="width: 600px;background: none; box-shadow:none;">' +
		'<div class="lottery_tan po_re">' +
		'<div class="lottery_tan_cha po_ab" onclick="window.location.reload()"><img src="/Public/Home/award/images/lot_tan_cha.png" width="20"></div> ' +
		'<div class="lottery_tan_no">' +
		'<img src="/Public/Home/award/images/lot_tanwen_no.png" width="192" class="lot_tanwen_no"> ' +
		'<img src="/Public/Home/award/images/lot_tanimg_no.png" width="176" class="lot_tanimg_no"> ' +
		'</div>' +
		'</div>' +
		'</div>';
	$('body').prepend(html);
	showDialog('lottery_no');
}
function lottery_get(id,txt){
	var html = '';
	var cs = '<p class="lottery_get_tishi"></p>';
	var imgUrl='';
	if(id == 9){
		if(txt.indexOf('Red Shell')>-1){
			imgUrl = '/images/lot/lot_get_img'+id+'-1.png';
		}else if(txt.indexOf('Asch currency')>-1){
			imgUrl = '/images/lot/lot_get_img'+id+'-2.png';
		}else {
			imgUrl = '/images/lot/lot_get_img'+id+'.png';
		}
	}else{
		cs = '<p class="lottery_get_tishi">Tips：Kind prizes，Please take the initiative<a href="http://wpa.b.qq.com/cgi/wpa.php?ln=1&key=XzkzODAyNDczN18xNjMyMjVfNDAwNjU3ODg4MF8yXw">联系客服</a>Provided shipping address.</p>';
		imgUrl = '/images/lot/lot_get_img'+id+'.png';
	}
	html += '<div class="ui-dialog" id="lottery_get" style="width: 600px;background: none; box-shadow:none;">' +
		'<div class="lottery_tan po_re">' +
		'<div class="lottery_tan_cha po_ab" onclick="window.location.reload()"><img src="/Public/Home/award/images/lot_tan_cha.png" width="20"></div> ' +
		'<div class="lottery_tan_get">' +
		'<img src="/Public/Home/award/images/lot_tanwen_get.png" width="167" class="lot_tanwen_get">' +
		'<div class="lottery_get">' +
		'<img src="'+imgUrl+'" width="224">' +
		'<p>' + txt + '</p>' +
		'<a href="/finance/prize"><img src="/Public/Home/award/images/lot_tanimg_click.png" width="136" alt="see details"> </a>' +
		'</div>' + cs +
		'</div>' +
		'</div>' +
		'</div>';
	$('#lottery_get').remove();
	$('body').prepend(html);
	showDialog('lottery_get');
}

function Dom(id){
	return $("#"+id);
}

//Pop-up layer
function showDialog(id,maskclick) {
    // Mask
    $('#'+id).removeClass('modal-out').addClass('styled-pane');
    var dialog = Dom(id);
    dialog.style.display = 'block';
    if (Dom('mask') == null) {
        $('body').prepend('<div class="ui-mask" id="mask" onselectstart="return false"></div>');
        if(!maskclick)
            $('#mask').bind('click',function(){hideDialog(id)})
    }
    var mask = Dom('mask');
    mask.style.display = 'inline-block';
    mask.style.width =  document.body.offsetWidth  + 'px';
    mask.style.height = document.body.scrollHeight + 'px';
    //Center
    var bodyW = document.documentElement.clientWidth;
    var bodyH = document.documentElement.clientHeight;
    var elW = dialog.offsetWidth;
    var elH = dialog.offsetHeight;
    dialog.style.left = (bodyW - elW) / 2 + 'px';
    dialog.style.top = (bodyH - elH) / 2 + 'px';
    dialog.style.position = 'fixed';
}
// Close pop-up box
function hideDialog(id, fn) {
    $('#'+id).removeClass('styled-pane').addClass('modal-out');
    $('#mask').addClass('out');
    setTimeout(function(){$('#'+id).hide();$('#mask').remove();},300);
    if (typeof fn == 'function') fn();
}




