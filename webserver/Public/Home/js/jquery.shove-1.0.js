var param = {};

/**   
*    
* @Description sendpostrequest When there interceptorsreturninformationEnterRowdeal with
* @param url Request address
* @param param Request parameter
* @param callBack After the success callback method
* @Author Yang Cheng
* @Date: 2012-2-17 18：00  
* @Version  1.0
*    
*/ 
$.shovePost = function(url,param,callBack){
	url = url+"?shoveDate"+new Date().getTime();
	$.post(url,param,function(data){
		if(data == "noLogin"){
			window.location.href="login";
			return;
		}
		if(data=="network"){
		   window.location.href="weihui.jsp";
		  return;
		}
		if(data=="virtual"){
		   window.location.href="noPermission.action";
		  return;
		}
		if(data == "pagejump"){
			window.location.href="adminMessage.action";
			return;
		}
		callBack(data);
	});
}

/**   
*    
* @Description Jump page methods
* @param i Jump Pages
* @Author Yang Cheng
* @Date: 2012-2-17 18：10
* @Version  1.0
*    
*/
function doJumpPage(i){
	//if(i==""){
	//	alert("Incorrect input format12!");
	//	return;
	//}
	if(isNaN(i)){
		alert("Incorrect input format!");
		return;
	}
	$("#pageNum").val(i);
	param["pageBean.pageNum"]=i;
	//Page callback method
	initListInfo(param);
}

function checkbox_All_Reverse(obj,itemName){
	$("input[name=" + itemName + "]").attr("checked",obj.checked);
}

//Interlaced color table
function trEvenColor(){
	$("#tableTr tr:even").css("background-color","#f9f9f9");
}

function setCookies(cookieName,value,days){
	$.cookie(cookieName, value, { expires: days });
}
function getCookies(cookieName){
	return $.cookie(cookieName);
}

 function getFlexObject(movieName) {   
    return document[movieName];   
}

$(function(){
	trEvenColor();
})

function hideAndShow(str){
	$(str).hide();
	$(str).show();
}
