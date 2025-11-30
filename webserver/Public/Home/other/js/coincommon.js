function $() {
    var elements = new Array();
    for (var i = 0; i < arguments.length; i++) {
        var element = arguments[i];
        if (typeof element == 'string')
            element = document.getElementById(element);
        if (arguments.length == 1)
            return element;
        elements.push(element);
    }
    return elements;
}


function ltrim(s){
    return s.replace( /^\s*/, "");
}

function rtrim(s){
    return s.replace( /\s*$/, "");
}

function trim(s){
    return rtrim(ltrim(s));
}

function checkUserName(username){
    filter=/^[a-zA-Z0-9\u0391-\uFFE5]{2,20}/;
    if(!filter.test(trim(username))){
        return false;
    }else{
        return true;
    }
}

function checkPassWord(username){
    filter=/^[a-zA-Z0-9\u0391-\uFFE5]{2,20}/;
    if(!filter.test(trim(username))){
        return false;
    }else{
        return true;
    }
}
function checkDate(dateStr){
    filter=/^\d{4}-((0[1-9]{1})|(1[0-2]{1}))-((0[1-9]{1})|([1-2]{1}[0-9]{1})|(3[0-1]{1}))$/;
    if(!filter.test(trim(dateStr))){
        return false;
    }else{
        return true;
    }

}
function checkNumber(num){
    filter=/^-?([1-9][0-9]*|0)(\.[0-9]+)?$/;
    if(!filter.test(trim(num))){
        return false;
    }else{
        return true;
    }
}
function checkNumberInt(num){
    filter=/^-?([1-9][0-9]*|0)$/;
    if(!filter.test(trim(num))){
        return false;
    }else{
        return true;
    }
}
function checkPositiveNumber(num){
    filter=/^([1-9][0-9]*|0)$/;
    if(!filter.test(trim(num))){
        return false;
    }else{
        return true;
    }
}

function openShop() {
    var url = "/shop/open?random="
        + Math.round(Math.random() * 100);
    jQuery.post(url,null,function(data){
        var callback={okBack:function(){window.location.href = "/";}};
        if (data == -1) {
            okcoinAlert("You have opened exchange network!", null, callback, "Ok");
        } else if (data == '-2') {
            okcoinAlert("HFC insufficient balance!", null, callback, "Ok");
        } else if (data == '-3') {
            okcoinAlert("network anomaly!", null, callback, "Ok");
        } else if (data == '0') {
            okcoinAlert("Opened successfully!", null, callback, "Ok");
        }
    },"json") ;
}

function checkNumber2(num){
    filter=/^-?([1-9][0-9]*|0)?(\.[0-9]{1,2})?$/;
    if(!filter.test(trim(num))){
        return false;
    }else{
        return true;
    }
}
function checkEmail(email){
    filter=/^([a-zA-Z0-9_\-\.\+]+)@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.)|(([a-zA-Z0-9\-]+\.)+))([a-zA-Z]{2,4}|[0-9]{1,3})(\]?)$/;
    if(!filter.test(trim(email))){
        return false;
    }else{
        return true;
    }
}
function checkMobile(mobile){
    filter=/^1[3|4|5|8|7][0-9]\d{8}$/;
    if(!filter.test(trim(mobile))){
        return false;
    }else{
        return true;
    }
}

function getLength(str)//
{
    count = 0;
    for (var i = 0; i < str.length; i++)
    {
        if (((str.charCodeAt(i) >= 0x3400) && (str.charCodeAt(i) < 0x9FFF)) || (str.charCodeAt(i) >= 0xF900))
        {
            count+=2;
        }else{
            count++;
        }
    }
    return count;
}

function getLeft(str,len){
    i=0;
    for(var i=0;i<len;i++){
        if (((str.charCodeAt(i) >= 0x3400) && (str.charCodeAt(i) < 0x9FFF)) || (str.charCodeAt(i) >= 0xF900))
        {
            len--;
        }

    }
    str=str.substr(0,i);
    str+="..";
    return str;
}

function left(str,len){

    if(getLength(str)>len){
        str=getLeft(str,len-2);
    }
    return str;
}
function checkNumberAndString(str){
    filter=/^[a-zA-Z0-9]{10,50}$/;
    if(!filter.test(trim(str))){
        return false;
    }else{
        return true;
    }
}
function getCurrentDate(c){
    d = new Date();
    s="";
    year=d.getFullYear();
    month=1+d.getMonth();
    date=d.getDate();
    if(month<10){
        month="0"+month;
    }
    if(date<10){
        date="0"+date;
    }
    s=year+c+month+c+date;
    return s;
}
function getCurrentTime(c){
    var d, s = "";
    d = new Date();
    s += d.getHours() + c;
    s += d.getMinutes() + c;
    s += d.getSeconds() + c;
    s += d.getMilliseconds();
    return s;
}
function getAbsoluteHeight(ob){
    return ob.offsetHeight;
}
function getAbsoluteTop(ob){
    var s_el=0;
    el=ob;
    while(el){
        s_el=s_el+el.offsetTop ;
        el=el.offsetParent;
    };
    return s_el;
}
function getAbsoluteLeft(ob){
    var s_el=0;el=ob;
    while(el){
        s_el=s_el+el.offsetLeft;
        el=el.offsetParent;
    };
    return s_el;
}





function hasClass(ele,cls) {
    return ele.className.match(new RegExp('(\\s|^)'+cls+'(\\s|$)'));
}

function addClass(ele,cls) {
    if (!this.hasClass(ele,cls)) ele.className += " "+cls;
}

function removeClass(ele,cls) {
    if (hasClass(ele,cls)) {
        var reg = new RegExp('(\\s|^)'+cls+'(\\s|$)');
        ele.className=ele.className.replace(reg,' ');
    }
}


function stopBubble(e) { // Stop bubbling
    if ( e && e.stopPropagation )
        e.stopPropagation();
    else
        window.event.cancelBubble = true;
}
function stopDefault( e ) {
    if ( e && e.preventDefault )
        e.preventDefault();
    else
        window.event.returnValue = false;
    return false;
}

function getStyle(o,n){
    return o.currentStyle?o.currentStyle[n]:(document.defaultView.getComputedStyle(o,"").getPropertyValue(n));
}

function getPosLeft(o) {
    var l = o.offsetLeft;
    return l = (o = o.offsetParent)?(l+o.offsetLeft+(!parseInt(getStyle(o,"borderLeftWidth"))?0:parseInt(getStyle(o,"borderLeftWidth")))):l;
}

function getPosTop(o) {
    var t = o.offsetTop;
    return t = (o = o.offsetParent)?(t+o.offsetTop+(!parseInt(getStyle(o,"borderTopWidth"))?0:parseInt(getStyle(o,"borderTopWidth")))):t;
}
function   getXYWH(o){
    var   nLt=0;
    var   nTp=0;
    var   offsetParent   =   o;
    while   (offsetParent!=null   &&   offsetParent!=document.body)   {
        nLt+=offsetParent.offsetLeft;
        nTp+=offsetParent.offsetTop;
        offsetParent=offsetParent.offsetParent;
    }
    this.showL=nLt;
    this.showT=nTp;
    this.showW=this.offsetWidth;
    this.showH=this.offsetHeight;
}

function addLoadEvent(func) {
    var oldonload = window.onload;
    if (typeof window.onload != 'function') {
        window.onload = func;
    } else {
        window.onload = function() {
            oldonload();
            func();
        };
    }
}

function addEV(C,B,A){
    if(window.attachEvent){
        C.attachEvent("on"+B,A);
    }else{
        if(window.addEventListener){
            C.addEventListener(B,A,false);
        }
    }
}
function removeEV(C,B,A){
    if(window.attachEvent){
        C.detachEvent("on"+B,A);
    }else{
        if(window.addEventListener){
            C.removeEventListener(B,A,false);
        }
    }
}


// dynamic include another js file
function include_js(path,reload)
{
    var scripts = document.getElementsByTagName("script");
    if (reload==null || !reload)
        for (var i=0;i<scripts.length;i++){
            if (scripts[i].src && scripts[i].src.toLowerCase() == path.toLowerCase() )
                return;
        }
    var sobj = document.createElement('script');
    sobj.type = "text/javascript";
    sobj.src = path;
    var headobj = document.getElementsByTagName('head')[0];
    headobj.appendChild(sobj);
}

// ----------Dynamic loading-------------//
function LoadJS(fileUrl,type)
{
    var oHead = document.getElementsByTagName('HEAD').item(0);
    var oScript= document.createElement("script");
    oScript.type = "text/javascript";
    if(!!type){
        oScript.charset="gb2312";
    }
    oScript.src=fileUrl ;
    oHead.appendChild(oScript);
}

function MM_swapImgRestore() { // v3.0
    var i,x,a=document.MM_sr; for(i=0;a&&i<a.length&&(x=a[i])&&x.oSrc;i++) x.src=x.oSrc;
}

function MM_preloadImages() { // v3.0
    var d=document; if(d.images){ if(!d.MM_p) d.MM_p=new Array();
        var i,j=d.MM_p.length,a=MM_preloadImages.arguments; for(i=0; i<a.length; i++)
            if (a[i].indexOf("#")!=0){ d.MM_p[j]=new Image; d.MM_p[j++].src=a[i];}}
}

function MM_findObj(n, d) { // v4.01
    var p,i,x;  if(!d) d=document; if((p=n.indexOf("?"))>0&&parent.frames.length) {
        d=parent.frames[n.substring(p+1)].htmlcument; n=n.substring(0,p);}
    if(!(x=d[n])&&d.all) x=d.all[n]; for (i=0;!x&&i<d.forms.length;i++) x=d.forms[i][n];
    for(i=0;!x&&d.layers&&i<d.layers.length;i++) x=MM_findObj(n,d.layers[i].htmlcument);
    if(!x && d.getElementById) x=d.getElementById(n); return x;
}

function MM_swapImage() { // v3.0
    var i,j=0,x,a=MM_swapImage.arguments; document.MM_sr=new Array; for(i=0;i<(a.length-2);i+=3)
        if ((x=MM_findObj(a[i]))!=null){document.MM_sr[j++]=x; if(!x.oSrc) x.oSrc=x.src; x.src=a[i+2];}
}


function boxFloat(obj,elem){
    var nmove,mmove,
        d = document,
        o = d.getElementById(obj),
        s = d.getElementById(elem);
    if(!o){ return false;}
    if(!s){ return false;}

    s.onmouseover=function(){
        clearTimeout(nmove);
        s.style.display="block";
        s.style.cursor="pointer";
    };
    o.onmouseover=function(){
        clearTimeout(nmove);
        mmove=setTimeout(function(){

            s.style.display="block";
            if(obj.indexOf("ordersStatus_") != -1){
                var id = obj.substring(obj.indexOf("_")+1,obj.length);
                jQuery("#detailOrdersStatus_"+id).load("/orders/status?id="+id,function (data){
                });
            }
            if(obj=="orderStatusIndex"){
                var id = document.getElementById("orderStatusId").value;
                indexOrdersStatus(id);
            }

        },100);

    };
    o.onmouseout=function(){
        clearTimeout(mmove);
        nmove=setTimeout(function(){s.style.display="none";},500);
    };
    s.onmouseout=function(){
        nmove=setTimeout(function(){s.style.display="none";},500);
    };
    s.onmousedown=function(e){
        stopBubble(e);
    };
}
boxFloat("accountlink","accountpop");
boxFloat("personalNetAssetsExplain","personalNetAssetsExplainBlock");

function ShowMemo(obj,id)
{
    $("Memo"+id).style.display = "";
}

function HideMemo(id)
{
    $("Memo"+id).style.display = "none";
}

function dialogBoxHidden(){
    var d=document,
        o=d.getElementById("dialogBoxShadow");
    if(!o) return false;
    d.body.removeChild(o);
}

function dialogBoxShadow(f){
    dialogBoxShadowMove(f,true);
}

function dialogBoxShadowMove(f,canmove){
    var d = document,
        divs=d.createElement("div"),
        doc = d[d.compatMode == "CSS1Compat"?'documentElement':'body'],
        h = f?doc.clientHeight:Math.max(doc.clientHeight,doc.scrollHeight);
    divs.setAttribute("id","dialogBoxShadow");
    d.body.appendChild(divs);
    var o = d.getElementById('dialogBoxShadow');
    o.style.cssText +="	;position:absolute;top:0;left:0;z-index:100;background:#000;opacity:0.4;filter:Alpha(opacity=20);width:100%;height:"+h+"px";
    if(canmove) addMoveEvent("dialog_title","dialog_content");
}

function addMoveEvent(titleobj,contentobj){
    var titleobj = document.getElementById(titleobj);
    var contentobj=document.getElementById(contentobj);
    if(titleobj!=null&&contentobj!=null){
        var bDrag = false;
        var disX = disY = 0;
        titleobj.onmousedown = function (event)
        {
            var event = event || window.event;
            bDrag = true;
            disX = event.clientX - contentobj.offsetLeft;
            disY = event.clientY - contentobj.offsetTop;
            this.setCapture && this.setCapture();
            return false;
        };
        document.onmousemove = function (event)
        {
            if (!bDrag) return;
            var event = event || window.event;
            var iL = event.clientX - disX;
            var iT = event.clientY - disY;
            var maxL = document.documentElement.clientWidth - contentobj.offsetWidth;
            var maxT = document.documentElement.clientHeight - contentobj.offsetHeight;
            iL = iL < 0 ? 0 : iL;
            iL = iL > maxL ? maxL : iL;
            iT = iT < 0 ? 0 : iT;
            iT = iT > maxT ? maxT : iT;

            contentobj.style.marginTop = contentobj.style.marginLeft = 0;
            contentobj.style.left = iL + "px";
            contentobj.style.top = iT + "px";
            return false;
        };
        document.onmouseup = window.onblur = titleobj.onlosecapture = function ()
        {
            bDrag = false;
            titleobj.releaseCapture && titleobj.releaseCapture();
        };
    }

}

// -----------------Positioning pop-up layer--------------------//
function skillsPosition(obj,x){
    var o=$(obj),h,oh,w,oc;
    if(!o) return false;
    o.style.display="block";
    h=parseInt(getStyle(o,"height"));
    w=parseInt(getStyle(o,"width"));
    oh=";display:block;top:50%;margin-top:"+(-h/2)+"px";
    o.style.cssText=!x?oh:(oh+";left:50%;margin-left:"+(-w/2)+"px");
}


/* Absolutely located centrally pop-up layer */
function setObjCenter(id){
    var d=document;
    var obj = d.getElementById(id);
    var data={
        ow:obj.clientWidth,
        oh:obj.clientHeight,
        vw:(function(){
            if (d.compatMode == "BackCompat"){
                return d.body.clientWidth;
            } else {
                return d.documentElement.clientWidth;
            }
        })(),
        vh:(function(){
            if (d.compatMode == "BackCompat"){
                return d.body.clientHeight;
            } else {
                return d.documentElement.clientHeight;
            }
        })(),
        st:(d.body.scrollTop||d.documentElement.scrollTop)
    };
    // obj.style.display="block";
    obj.style.left=(data.vw-data.ow)/2+"px";
    obj.style.margin=0;
    if(!!window.XMLHttpRequest){
        obj.style.position="fixed";
        obj.style.top=(data.vh-data.oh)/2+"px";
    }else{
        obj.style.position="absolute";
        obj.style.top=(data.vh-data.oh)/2+data.st+"px";
        if(obj.style.backgroundAttachment)
            obj.style.backgroundAttachment="absolute !important";
        window.onscroll=function(){obj.style.top=(d.body.scrollTop||d.documentElement.scrollTop)+(data.vh-data.oh)/2+'px';};
    }
}
// id 0:Login Layer 1:Registration layer
function showlogin(id){
    document.getElementById("okcoinPop").style.display="block";
    jQuery("#okcoinPop").load("/user/login?type="+id+"&t="+new Date().getTime(),function (data){
        dialogBoxShadow();
        showDialog(id);
    });
}
function closelogin(){
    dialogBoxHidden();
    document.getElementById("okcoinPop").style.display="none";
    document.getElementById("okcoinPop").innerHTML="";
}

/* gobacktop */

if(document.body !=null && document.body.scrollHeight>1200){
    goBackTop("goBackTop");
}
function goBackTop(id){
    var oBtn=document.getElementById(id);
    if(oBtn==null){
        return;
    }

    addEV(window,"scroll",function(){
        var sT=document.documentElement.scrollTop||document.body.scrollTop;
        var sH=document.documentElement.clientHeight;
        if(sT>180){
            oBtn.style.display="block";
            if(-1!=window.navigator.userAgent.indexOf('MSIE 6.0') && -1==window.navigator.userAgent.indexOf('MSIE 7.0') && -1==window.navigator.userAgent.indexOf('MSIE 8.0'))// for
            // ie6
            {
                oBtn.style.bottom="auto";
                oBtn.style.top=sT+sH-oBtn.offsetHeight+"px";
            }
        }
        else{
            oBtn.style.display="none";
        }

    });
}

function okcoinAlert(str,pro,callback,btnTitle) {
    /*
     * @str Incoming prompt content @pro Optionally, the Cancel button The return value is determined astrueCancel and close arefalse
     */
    if(btnTitle == "" || btnTitle == "undefined" || btnTitle==null){
        btnTitle = "Ok";
    }
    var d = document, obj , tempStr = [] , dEle = d.documentElement , ieSix = (!window.XMLHttpRequest);
    var callback=callback||{okBack:function(){return true;},noBack:function(){return false;}};
    function gid(id){return d.getElementById(id);}
    if(!!gid("okcoinAlert")){
        d.body.removeChild(gid("okcoinAlert"));
    }
    obj = d.createElement("div");
    obj.className="okcoinPop";
    obj.id="okcoinAlert";



    // tempStr.push('<iframe id="alertIframe" scrolling="no"
    // style="border:0;height:100%;_height:255px;width:100%;left:0;top:0;z-index:-1;position:absolute;"></iframe>');
    tempStr.push('<div class="small_content" id="alertBody">');
    tempStr.push('<div class="orderFloorTitle"><span >&nbsp;&nbsp;Tips</span><span style="float:right;"><a id="alertClose" href="javascript:void(0);" class="dialog_closed" title="shut down"></a></span></div>');
    tempStr.push('<div class="smallFloor">'+str+'</div>');
    tempStr.push('<div class="orderFloor-button center"><input class="okbutton" type="button" id="alertOk" value="'+btnTitle+'" title="'+btnTitle+'"/>');
    if(!!!pro){
        tempStr.push('</div>');
    }else{
        tempStr.push('&nbsp;&nbsp;<input id="alertNo" class="okbutton" type="button" value="cancel" title="cancel"/></div>');
    }

    tempStr.push('</div>');
    obj.innerHTML=tempStr.join("");
    d.body.appendChild(obj);
    dialogBoxShadow();
    var os = obj.style;
    os.display="block";
    var temptop = d.body.scrollTop+d.documentElement.scrollTop;
    os.left=(dEle.clientWidth-obj.clientWidth)/2+dEle.scrollLeft+"px";
    os.top=(dEle.clientHeight-obj.clientHeight)/2+dEle.scrollTop+d.body.scrollTop+"px";
    if(ieSix){os.top=(dEle.clientHeight-obj.clientHeight)/2+temptop+"px";}
    os.position ="absolute";
    os.zIndex="100000";
    function fixed(){
        os.top=(dEle.clientHeight-obj.clientHeight)/2+dEle.scrollTop+d.body.scrollTop+"px";
    }
    if(ieSix){
// gid("alertIframe").style.height=gid("alertBody").offsetHeight+"px";
// gid("alertIframe").style.width=gid("alertBody").offsetWidth+"px";
// gid("alertIframe").style.top = gid("alertBody").style.top+"px";
// gid("alertIframe").style.left = gid("alertBody").style.left+"px";

        addEV(window,"scroll",fixed);
    }else{
        addEV(window,"scroll",fixed);
    }
    function hideObj(){
        d.body.removeChild(obj);
        dialogBoxHidden();
        os.display="none";
        if(ieSix){
            window.detachEvent("onscroll",fixed);
        }
    }

    gid("alertClose").onclick=function(){
        hideObj();
        if(!!callback.noBack){
            callback.noBack();
        }
        return false;
    };
    gid("alertOk").onclick=function(){
        hideObj();
        if(!!callback.okBack){
            callback.okBack();
        }
        return true;
    };
    if(!!pro){
        gid("alertNo").onclick=function(){
            hideObj();
            if(!!callback.noBack){
                callback.noBack();
            }
            return false;
        };
    }
    return true;
}
function okcoinAlert2(str,pro,callback,btnTitle,bingo) {
    /*
     * @str Incoming prompt content @pro Optionally, the Cancel button The return value is determined astrueCancel and close arefalse
     */
    if(btnTitle == "" || btnTitle == "undefined" || btnTitle==null){
        btnTitle = "Ok";
    }
    var d = document, obj , tempStr = [] , dEle = d.documentElement , ieSix = (!window.XMLHttpRequest);
    var callback=callback||{okBack:function(){return true;},noBack:function(){return false;}};
    function gid(id){return d.getElementById(id);}
    obj = d.createElement("div");
    obj.className="okcoinPop";
    obj.id="okcoinAlert";




    tempStr.push('<div id="divEmptygift" class="gift_box empty_gift" style="width: 980px; height: 598px; margin: -308px 0 0 280px;">');
    tempStr.push('	<i class="'+bingo+'"></i>');
    tempStr.push('	<p id="p_empty_gift" class="space">'+str+'</p>');
    tempStr.push('                <div class="smashbtn">');
    tempStr.push('		<a class="btn btn1" id="alertOk" title="Again" href="javascript:void(0);">Again</a>');
    tempStr.push('	</div>');
    tempStr.push('</div>');

    tempStr.push('</div>');

    obj.innerHTML=tempStr.join("");
    d.body.appendChild(obj);
    dialogBoxShadow();
    var os = obj.style;
    os.display="block";
    var temptop = d.body.scrollTop+d.documentElement.scrollTop;
    os.left=(dEle.clientWidth-obj.clientWidth)/2+dEle.scrollLeft+"px";
    os.top=(dEle.clientHeight-obj.clientHeight)/2+dEle.scrollTop+d.body.scrollTop+"px";
    if(ieSix){os.top=(dEle.clientHeight-obj.clientHeight)/2+temptop+"px";}
    os.position ="absolute";
    os.zIndex="100000";
    function fixed(){
        os.top=(dEle.clientHeight-obj.clientHeight)/2+dEle.scrollTop+d.body.scrollTop+"px";
    }
    if(ieSix){
// gid("alertIframe").style.height=gid("alertBody").offsetHeight+"px";
// gid("alertIframe").style.width=gid("alertBody").offsetWidth+"px";
// gid("alertIframe").style.top = gid("alertBody").style.top+"px";
// gid("alertIframe").style.left = gid("alertBody").style.left+"px";

        addEV(window,"scroll",fixed);
    }else{
        addEV(window,"scroll",fixed);
    }
    function hideObj(){
        d.body.removeChild(obj);
        dialogBoxHidden();
        os.display="none";
        if(ieSix){
            window.detachEvent("onscroll",fixed);
        }
    }
    gid("alertOk").onclick=function(){
        hideObj();
        if(!!callback.okBack){
            callback.okBack();
        }
        return true;
    };
    return true;
}



// ----------------uuid file ------------------------//
/*
 * http://www.af-design.com/services/javascript/uuid/
 * 
 * uuid.js - Version 0.3 JavaScript Class to create a UUID like identifier
 * 
 * Copyright (C) 2006-2008, Erik Giberti (AF-Design), All rights reserved.
 * 
 * This program is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License as published by the Free Software
 * Foundation; either version 2 of the License, or (at your option) any later
 * version.
 * 
 * This program is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
 * FOR A PARTICULAR PURPOSE. See the GNU General Public License for more
 * details.
 * 
 * You should have received a copy of the GNU General Public License along with
 * this program; if not, write to the Free Software Foundation, Inc., 59 Temple
 * Place, Suite 330, Boston, MA 02111-1307 USA
 * 
 * The latest version of this file can be downloaded from
 * http://www.af-design.com/resources/javascript_uuid.php
 * 
 * HISTORY: 6/5/06 - Initial Release 5/22/08 - Updated code to run faster,
 * removed randrange(min,max) in favor of a simpler rand(max) function. Reduced
 * overhead by using getTime() method of date class (suggestion by James Hall).
 * 9/5/08 - Fixed a bug with rand(max) and additional efficiencies pointed out
 * by Robert Kieffer http://broofa.com/
 * 
 * KNOWN ISSUES: - Still no way to get MAC address in JavaScript - Research into
 * other versions of UUID show promising possibilities (more research needed) -
 * Documentation needs improvement
 * 
 */

// On creation of a UUID object, set it's initial value
function UUID(){
    this.id = this.createUUID();
}

// When asked what this Object is, lie and return it's value
UUID.prototype.valueOf = function(){ return this.id; };
UUID.prototype.toString = function(){ return this.id; };

//
// INSTANCE SPECIFIC METHODS
//

UUID.prototype.createUUID = function(){
    //
    // Loose interpretation of the specification DCE 1.1: Remote Procedure Call
    // described at
    // http://www.opengroup.org/onlinepubs/009629399/apdxa.html#tagtcjh_37
    // since JavaScript doesn't allow access to internal systems, the last 48
    // bits
    // of the node section is made up using a series of random numbers (6 octets
    // long).
    //
    var dg = new Date(1582, 10, 15, 0, 0, 0, 0);
    var dc = new Date();
    var t = dc.getTime() - dg.getTime();
    var h = '';
    var tl = UUID.getIntegerBits(t,0,31);
    var tm = UUID.getIntegerBits(t,32,47);
    var thv = UUID.getIntegerBits(t,48,59) + '1'; // version 1, security
    // version is 2
    var csar = UUID.getIntegerBits(UUID.rand(4095),0,7);
    var csl = UUID.getIntegerBits(UUID.rand(4095),0,7);

    // since detection of anything about the machine/browser is far to buggy,
    // include some more random numbers here
    // if NIC or an IP can be obtained reliably, that should be put in
    // here instead.
    var n = UUID.getIntegerBits(UUID.rand(8191),0,7) +
        UUID.getIntegerBits(UUID.rand(8191),8,15) +
        UUID.getIntegerBits(UUID.rand(8191),0,7) +
        UUID.getIntegerBits(UUID.rand(8191),8,15) +
        UUID.getIntegerBits(UUID.rand(8191),0,15); // this last number is
    // two octets long
    return tl + h + tm + h + thv + h + csar + csl + h + n;
};


//
// GENERAL METHODS (Not instance specific)
//


// Pull out only certain bits from a very large integer, used to get the time
// code information for the first part of a UUID. Will return zero's if there
// aren't enough bits to shift where it needs to.
UUID.getIntegerBits = function(val,start,end){
    var base16 = UUID.returnBase(val,16);
    var quadArray = new Array();
    var quadString = '';
    var i = 0;
    for(i=0;i<base16.length;i++){
        quadArray.push(base16.substring(i,i+1));
    }
    for(i=Math.floor(start/4);i<=Math.floor(end/4);i++){
        if(!quadArray[i] || quadArray[i] == '') quadString += '0';
        else quadString += quadArray[i];
    }
    return quadString;
};

// Replaced from the original function to leverage the built in methods in
// JavaScript. Thanks to Robert Kieffer for pointing this one out
UUID.returnBase = function(number, base){
    return (number).toString(base).toUpperCase();
};

// pick a random number within a range of numbers
// int b rand(int a); where 0 <= b <= a
UUID.rand = function(max){
    return Math.floor(Math.random() * (max + 1));
};

// end of UUID class file

// ----------------uuid file end-----------------------//

// -----------------cookies file -----------------------//
function CookieClass()
{
    this.expires = 60*24*7 ; // Effective time,In minutes
    this.path = ""; // Set the access path
    this.htmlmain = ""; // Set upaccessHost computer
    this.secure = false; // Set upsafety

    this.setCookie = function(name,value)
    {
        var str = name+"="+escape(value);
        if (this.expires>0)
        {
            // If you set an expiration time
            var date=new Date();
            var ms=this.expires * 60 * 1000; // There per minute60Second, per second1000millisecond
            date.setTime(date.getTime()+ms);
            str+="; expires="+date.toGMTString();
        }
        str+="; path=/";
        // if(this.path!="")str+="; path=/";//+this.path; //Set the access path
        if(this.htmlmain!="")str+="; domain="+this.htmlmain; // Set upaccessHost computer
        if(this.secure!="")str+="; true"; // Set upsafety
        document.cookie=str;
    };

    this.getCookie=function(name)
    {
        var cookieArray=document.cookie.split("; "); // Get splitcookieName-value pairs
        // var cookie=new Object();
        for(var i=0;i<cookieArray.length;i++)
        {
            var arr=cookieArray[i].split("="); // The names and values ​​are separated
            if(arr[0]==name)return unescape(arr[1]); // If it is specifiedcookieIts value is returned
        }
        return "";
    };

    this.deleteCookie=function(name)
    {
        var date=new Date();
        var ms= 1 * 1000;
        date.setTime(date.getTime() - ms);
        var str = name+"=no; expires=" + date.toGMTString(); // willExpirationSet asPast toTo delete acookie
        document.cookie=str;
    };

    this.showCookie=function()
    {
        alert(unescape(document.cookie));
    };
}

// Examples of use
// var cook = new CookieClass();
// cook.expires =1;//One minute effective
// cook.setCookie("01","5556666666666555");//write
// alert(cook.getCookie("01"));//read
// cook.showCookie();

// -----------------cookies file end-----------------------//




/*
 * //imgLoad("imgLoad"); //imgLoad("windowImg","li",1); parameter1A picture where the module
 * parameter2forimageWhere the cycleelement；Do not writeThe default is“li” parameter3forwindowscrolltrigger eventThis parameterexistTimeMandatoryparameter2；Do not writeThe default isModulescrolltrigger event //Mouse wheel events
 */

// ----------Picture delay loading-------------//
function imgLoad(o,tags,f){
    var d=document,
        doc = d[d.compatMode == "CSS1Compat"?'documentElement':'body'],
        o=d.getElementById(o),
        tags=tags?tags:"li";
    if(!o){return false;}
    var j,s=o.getElementsByTagName("img"),
        e=o.getElementsByTagName(tags),
        topnum = (navigator.userAgent.indexOf("WebKit")==-1)?d.documentElement:d.body,
        autoLength = o.getElementsByTagName(tags)[0].getElementsByTagName("img").length,
        autoMarL = (!-[1,])?(parseInt(getStyle(e[0],"marginLeft"))):(parseInt(getStyle(e[0],"margin-left"))),
        autoMarR = (!-[1,])?(parseInt(getStyle(e[0],"marginRight"))):(parseInt(getStyle(e[0],"margin-right"))),
        autoMarT = (!-[1,])?(parseInt(getStyle(e[0],"marginTop"))):(parseInt(getStyle(e[0],"margin-top"))),
        autoMarB = (!-[1,])?(parseInt(getStyle(e[0],"marginBottom"))):(parseInt(getStyle(e[0],"margin-bottom"))),
        autoHeight = e[0].offsetHeight + autoMarT + autoMarB,
        autoWidth = e[0].offsetWidth + autoMarL + autoMarR,
        maxHeight = o.offsetHeight -16,
        maxWidth = o.offsetWidth - 16;
    var autoLoad = function(){
        var maxWindow = doc.clientHeight,
            sObj=new getXYWH(o);
        j = f?Math.ceil((maxWindow - sObj.showT)/autoHeight)*Math.ceil(maxWidth/autoWidth)*autoLength:Math.ceil(maxHeight/autoHeight)*Math.ceil(maxWidth/autoWidth)*autoLength;
        j = (j < 0) ? 0 : j;
        j = (j < s.length) ? j : s.length;
        /* The default display picture */
        for(var i=0;i<j;i++){
            s[i].src = s[i].getAttribute("docsrc");
        }
    };
    /* Scroll */
    var scrollLoad = function(){
        var activeHeight = f?topnum.scrollTop:o.scrollTop,
            activeWidth = f?topnum.scrollLeft:o.scrollLeft,
            m= (Math.ceil(activeHeight/autoHeight)*Math.ceil(maxWidth/autoWidth) + Math.ceil(activeWidth/autoWidth)*Math.ceil(maxHeight/autoHeight))*autoLength,
            n=((m+j)>e.length)?e.length:(m+j);
        for(var i = j;i<n;i++){
            s[i].src = s[i].getAttribute("docsrc");
            if(s[(e.length-1)].src!==""){
                break;
            }
        }
    };
    (f?window:o).onscroll = function(){
        scrollLoad();
    };
    /* recalculate */
    window.onresize = function(){
        autoLoad();
        scrollLoad();
    };
    autoLoad();
}

var availableTagsDef = ["@qq.com","@163.com","@126.com","@sina.com","@gmail.com","@foxmail.com","@sohu.com","@vip.qq.com","@hotmail.com","@163.net","@sina.com.cn","@139.com","@189.cn"];
var availableTags = ["@qq.com","@163.com","@126.com","@sina.com","@gmail.com","@foxmail.com","@sohu.com","@vip.qq.com","@hotmail.com","@163.net","@sina.com.cn","@139.com","@189.cn"];
function emailOnkeyUp(obj){
    for ( var i = 0; i < availableTags.length; i++) {
        var reg = new RegExp(/^[a-zA-Z0-9_]{1,}$/);
        if(reg.test(obj.value)){
            availableTags[i] = obj.value+availableTagsDef[i];
        }
    }
}

function showDialog(id){
    if(id == 0){
        document.getElementById("loginDialog").style.display="block";
        document.getElementById("regDialog").style.display="none";
        document.getElementById("regLi").className="";
        document.getElementById("loginLi").className="cur";
        document.getElementById("phoneLi").className="";
        document.getElementById("loginUserName").focus();
        callbackEnter(loginSubmit);
    }else {
        document.getElementById("regDialog").style.display="block";
        document.getElementById("loginDialog").style.display="none";
        document.getElementById("regLi").className="cur";
        document.getElementById("phoneLi").className="";
        document.getElementById("loginLi").className="";
        callbackEnter(regSubmit);
        useRegType(0);
    }
}
function loginSubmit(){
    var space=""
    if(checkLoginUserName()  && checkLoginPassword()){
        var url = "/user/login/index?random="+Math.round(Math.random()*100);
        var uName = document.getElementById("loginUserName").value;
        var pWord = document.getElementById("loginPassword").value;
        var longLogin = 0;
        if(checkEmail(uName)){
            longLogin = 1;
        }
        var forwardUrl = "" ;
        if(document.getElementById("forwardUrl")!=null){
            forwardUrl = document.getElementById("forwardUrl").value ;
        }
        var param={loginName:uName,password:pWord,type:longLogin};
        jQuery.post(url,param,function(result){
            if(result!=null){
                var desc=""
                if(result.resultCode == -1){
                    desc="wrong user name or password";
                }else if(result.resultCode == -2){
                    desc="thisipLog in frequently,2After hours and try again";
                }else if(result.resultCode == -3){
                    if(result.errorNum == 0){
                        desc="thisipLog in frequently,2After hours and try again";
                    }else{
                        desc="wrong user name or password，youand also"+result.errorNum+"Chance";
                    }
                    document.getElementById("loginPassword").value="";
                }else if(result.resultCode == -4){
                    desc="youofBrowseIs not yetOpenCOOKIE,Set EnableCOOKIEFeatures";
                }else if(result.resultCode == 1){
                    if(forwardUrl.trim()==""){
                        window.location.href = document.getElementById("coinMainUrl").value;
                    }else{
                        window.location.href = forwardUrl;
                    }
                }else if(result.resultCode == 2){
                    desc="AccountAppears to be security risksfreeze，please尽快联系客服。";
                }else if(result.resultCode == -404){
                    desc="systemupgradein，time outlog in";
                }
                if(desc!=""){
                    showerrortips("loginTips", desc);
                }
            }
        },"json");
    }
}
/**
 * whetherlog incarry outRearJumppage
 */
function isForward(){
    if(document.getElementById("forwardUrl")!=null){
        var forward = document.getElementById("forwardUrl").value;
        if(forward != ""){
            showlogin(0);
        }
    }

}
function loginNameOnblur(){
    var space=""
    var uName = document.getElementById("loginUserName").value;
    if(! checkEmail(uName) && !checkMobile(uName)){
        showerrortips("loginTips", "mailboxOr phone numberformatIncorrect");
    }else{
        hideerrortips("loginTips");
    }
}
function checkLoginUserName(){
    var space=""
    var uName = document.getElementById("loginUserName").value;
    if(uName == ""){
        showerrortips("loginTips", "mailboxOr phone numberCan not be empty");
        return false;
    }else if(! checkEmail(uName) && !checkMobile(uName)){
        showerrortips("loginTips", "mailboxOr phone numberformatIncorrect");
        return false;
    }
    hideerrortips("loginTips");
    return true;
}
function checkLoginPassword(){
    var space=""
    var password = document.getElementById("loginPassword").value;
    if(password == ""){
        showerrortips("loginTips", "password can not be blank");
        return false;
    }else if(password.length <6){
        showerrortips("loginTips", "passwordlengthNot less than6");
        return false;
    }
    hideerrortips("loginTips");
    return true;
}
function termsService(){
    if(!document.getElementById("agree").checked){
        document.getElementById("regBtn").disabled=true;
        document.getElementById("regBtn").className="falsebutton buttonfalse";
    }else{
        document.getElementById("regBtn").disabled=false;
        document.getElementById("regBtn").className="button-dialog";
    }
}
function useRegType(id){
    document.getElementById("regDialog").style.display="block";
    document.getElementById("loginDialog").style.display="none";
    hideerrortips("",true);
    callbackEnter(regSubmit);
    if(id == 0){
        document.getElementById("emialtips").style.display="none";
        document.getElementById("phonecode").style.display="block";
        document.getElementById("regLi").className="";
        document.getElementById("loginLi").className="";
        document.getElementById("phoneLi").className="cur";
        document.getElementById("phoneImgValCode").style.display="block";
        document.getElementById("emailImgValCode").style.display="none";
        document.getElementById("regUserName").className="phone";
        document.getElementById("regUserName").placeholder="Please enter the phone number"
        document.getElementById("regUserName").value="";
        document.getElementById("regUserName").focus();
    }else{
        document.getElementById("emialtips").style.display="block";
        document.getElementById("phonecode").style.display="none";
        document.getElementById("regLi").className="cur";
        document.getElementById("loginLi").className="";
        document.getElementById("phoneLi").className="";
        document.getElementById("phoneImgValCode").style.display="none";
        document.getElementById("emailImgValCode").style.display="block";
        document.getElementById("regUserName").className="email";
        document.getElementById("regUserName").placeholder="Please input the email address"

        document.getElementById("regUserName").value="";
        document.getElementById("regUserName").focus();
    }
    document.getElementById("regType").value=id;

}
// verificationregistered名
function checkRegUserName(){
    var space="";
    var regType = document.getElementById("regType").value;
    var regUserName = trim(document.getElementById("regUserName").value);
    var desc='';
    if(regType == 0){
        // Verify phonenumber
        if(regUserName.indexOf(" ")>-1){
            desc='Phone number contains spaces!';
        }else {
            if(regUserName==''){
                desc='Please enter your phone number!';
            }
            else if(!checkMobile(regUserName)){
                desc='Phone number format is incorrect';
            }
        }
    }else{
        // verificationmailbox
        if(regUserName.indexOf(" ")>-1){
            desc='E-mail can not contain spaces!';
        }else {
            if(regUserName==''){desc='pleaseyouentermailbox!'; }
            else if(!checkEmail(regUserName)){ desc='E-mail format is incorrect,please enter again';	}
            else if (new RegExp("[,]","g").test(regUserName)){ desc='It contains illegal characters'; }
            else if(regUserName.length>100){	desc='It should be less than the length of the mailbox100Characters';	}
            var regokcoin = /^([a-zA-Z0-9_-])+@okcoin+(.[a-zA-Z0-9_-])+/;
            if(regokcoin.test(regUserName.toLowerCase())){ desc='Please enter your real mailbox';	}
        }
    }
    if(desc!=""){
        showerrortips("regNameResult", space+desc);
        return ;
    }else{
        hideerrortips("regNameResult");
    }
    var url = "/user/reg/chcekregname?name=" + encodeURI(regUserName) +"&type="+regType+"&random="+Math.round(Math.random()*100);
    jQuery.get(url,null,function(data){
        if(data == 0){
            if(regType == 0){
                desc = "Phone number already exists";
            }else{
                desc = "E-mail already exists";
            }
            showerrortips("regNameResult", space+desc);
            return ;
        }
        else{
            hideerrortips("regNameResult");
        }
    });
}
function checkPassword(){
    var space="";
    var pwd = trim(document.getElementById("regPassword").value);
    var desc='';
    var c = new RegExp();
    c = /^([a-zA-Z].+)$/;
    if(pwd == ""){
        desc="Please enter your password!";
    }else if(pwd.length <6){
        desc="passwordlengthNot less than6!";
    }else if(pwd.length>16){
        desc="Password not be greater than16!";
    }else if(!c.test(pwd)){
        desc="passwordMustletterbeginning!";
    }
    if(desc!=""){
        showerrortips("regPwdResult", space+desc);
        return false;
    }else{
        hideerrortips("regPwdResult");
    }
    return true;
}
function checkRePassword(){
    var space="";
    var pwd = trim(document.getElementById("regPassword").value);
    var rePwd = trim(document.getElementById("regRePassword").value);
    var desc='';
    if(rePwd == ""){
        desc="Please enter the password again!";
    }else if(rePwd.length <6){
        desc="passwordlengthNot less than6!";
    }else if(pwd.length>16){
        desc="Password not be greater than16!";
    }else if(pwd != rePwd){
        desc="enterofpasswordInconsistent!";
    }
    if(desc!=""){
        showerrortips("regRePwdResult", space+desc);
        return false;
    }else{
        hideerrortips("regRePwdResult");
    }
    return true;
}
function checkValidateCode(type){
    var space="";
    var validateCode = "";
    if(type==1){
        validateCode=trim(document.getElementById("phoneValidateCode").value);
    }
    else{
        validateCode=trim(document.getElementById("emailValidateCode").value);
    }
    var desc='';
    if(!/^.{4}$/.test(validateCode)){
        desc="Verification code error!";
    }

    if(desc!=""){
        if(type==1){
            showerrortips("phoneValidateCodeResult", space+desc);
        }
        else{
            showerrortips("emailValidateCodeResult", space+desc);
        }
        return false;
    }else{
        var urlName = "/user/reg/chcekcode?code=" + encodeURI(validateCode) +"&random="+Math.round(Math.random()*100);
        jQuery.get(urlName,null,function(data){
            if(data == 0){
                desc="Verification code error!";
                if(type==1){
                    showerrortips("phoneValidateCodeResult", space+desc);
                }
                else{
                    showerrortips("emailValidateCodeResult", space+desc);
                }

                return false;
            }else{
                if(type==1){
                    hideerrortips("phoneValidateCodeResult");
                }
                else{
                    hideerrortips("emailValidateCodeResult");
                }
            }
        });
    }
    return true;
}
function checkRegUserNameNoJquery(){
    var space="";
    var regType = document.getElementById("regType").value;
    var regUserName = trim(document.getElementById("regUserName").value);
    var desc='';
    if(regType == 0){
        // Verify phonenumber
        if(regUserName.indexOf(" ")>-1){
            desc='Phone number contains spaces!';
        }else {
            if(regUserName==''){
                desc='Please enter your phone number!';
            }
            else if(!checkMobile(regUserName)){
                desc='Phone number format is incorrect';
            }
        }
    }else{
        // verificationmailbox
        if(regUserName.indexOf(" ")>-1){
            desc='E-mail can not contain spaces!';
        }else {
            if(regUserName==''){	desc='pleaseyouentermailbox!'; 	}
            else if(!checkEmail(regUserName)){ desc='E-mail format is incorrect,please enter again';	}
            else if (new RegExp("[,]","g").test(regUserName)){ desc='It contains illegal characters'; }
            else if(regUserName.length>100){	desc='It should be less than the length of the mailbox100Characters';	}
        }
    }
    if(desc!=""){
        showerrortips("regNameResult", space+desc);
        return false;
    }else{
        hideerrortips("regNameResult");
        return true;
    }
}

function regSubmit(){
    var space="";
    if(!document.getElementById("agree").checked){
        showerrortips("userAgreementCodeResult", space+"Please read and agreeuserprotocol");
        return;
    }
    var regType = document.getElementById("regType").value;
    var flag = checkRegUserNameNoJquery() && checkPassword() && checkRePassword() &&(regType==0||checkValidateCode()) ;
    if(flag==true){
        var regUserName = trim(document.getElementById("regUserName").value);
        var index = regUserName.lastIndexOf(".", 0);
        var end = regUserName.substring(index, regUserName.length);
        if(end.length == 2 && "cn" != end){
            if(!confirm(regUserName+"  This onemailboxName may not be correct，youdetermineWhat？")){
                return;
            }
        }

        var validateCode = "";
        if(regType==0){
            validateCode=document.getElementById("phoneValidateCode").value;
        }else{
            validateCode=document.getElementById("emailValidateCode").value;
        }

        var pwd = trim(document.getElementById("regPassword").value);

        var urlName = "/user/reg/chcekregname?name=" + encodeURI(regUserName) +"&type="+regType+"&random="+Math.round(Math.random()*100);
        jQuery.get(urlName,null,function(data){
            if(data == 0){
                if(regType == 0){
                    desc = "Phone number already exists";
                }else{
                    desc = "E-mail already exists";
                }
                showerrortips("regNameResult", space+desc);
                return ;
            }
            else{
                var regPhoneCode=document.getElementById("regPhoneCode").value;
                var url = "/user/reg/index?random="+Math.round(Math.random()*100);
                var param={regName:regUserName,password:pwd,regType:regType,vcode:validateCode,pcode:regPhoneCode,intro_user:document.getElementById("introUser").value};
                jQuery.post(url,param,function(data){
                    if(data < 0){
                        var desc = "";
                        // registration failed
                        if(data==-20){
                            if(regType==0){
                                showerrortips("phoneValidateCodeResult", space+"Verification code error");
                                validate(document.getElementById("phoneValidateImages"));
                            }else{
                                showerrortips("emailValidateCodeResult", space+"Verification code error");
                                validate(document.getElementById("emailValidateImages"));
                            }
                            // refresh validatecode
                        }else if(data == -2){
                            if(regType == 0){
                                showerrortips("regNameResult", space+"Phone number already exists");
                            }else{
                                showerrortips("regNameResult", space+"E-mail already exists");
                            }
                        }else if(data == -4){
                            if(regType == 0){
                                showerrortips("regNameResult", space+"Please fill out the real phone number");
                            }else{
                                showerrortips("regNameResult", space+"Please fill in the real mailbox");
                            }
                        }else if(data==-6){
                            showerrortips("regPwdResult", space+"Password malformed,MustletterbeginningAnd greater than6Place");

                        }else if(data == -200){
                            desc = "RecommendedIDdoes not exist";
                        }else if(data == -888){
                            desc = "Members registered yet open";
                        }else if(data==-10){
                            desc = "serviceenderror，Contact your administrator";
                        }else if(data == -5){
                            desc="youofBrowseIs not yetOpenCOOKIE,Set EnableCOOKIEFeatures";
                        }else if(data==-10001){
                            showerrortips("regPhoneVel", space+"Phone code error");
                        }
                        if(desc!=null){
                            showerrortips("userAgreementCodeResult", space+desc);
                        }
                    }else{
                        if(document.getElementById("forwardUrl")!=null && document.getElementById("forwardUrl").value != ""){
                            var forward = document.getElementById("forwardUrl").value;
                            forward = decodeURI(forward);
                            window.location.href=forward;
                        }
                        if(regType==0){
                            window.location.href="/user/userinfo";
                        }else{
                            //	document.getElementById("conten").style.display = "none";
                            document.getElementById("emailSucess").style.display = "block";
                            document.getElementById("emailSpan").innerHTML = regUserName;
                        }
                    }
                });
            }
        });

    }
}
//Send SMS type (1-WritingSMS，2-Voice SMS)
var sendMsgType=1;
function getPhoneCode(ele){
    var space="";
    var urlName = "/user/reg/sendmsg?random="+Math.round(Math.random()*100);
    var countdown=120;
    var countdowntime;

    var regUserName=document.getElementById("regUserName").value;
    if(!checkMobile(regUserName)){
        showerrortips("regNameResult", space+"Phone number error");
        return;
    }
    var imgVal=document.getElementById("phoneValidateCode").value;
    if(!checkValidateCode(1)){
        showerrortips("phoneValidateCodeResult", space+"Please enter the image verification code");
        return;
    }
    jQuery.get(urlName,{phone:regUserName,imgVal:imgVal,msgtype:sendMsgType},function(data){
        if(data=="-1"||data==-1 ){
            showerrortips("regPhoneVel", space+"Image verification code error");
            return;
        }
        if(data=="-2"||data==-2){
            showerrortips("regPhoneVel", space+"Failure to obtain SMS verification code");
            return;
        }
        ele.value="Resend("+countdown+"s)";
        ele.onclick=function(){};
        ele.style.background="#9A9999";
        countdowntime=setInterval(function(){
            --countdown;
            ele.value="Resend("+countdown+"s)";
            if(countdown<=0)
            {
                sendMsgType=2;
                ele.style.background="#ec5322";
                ele.onclick=function(){getPhoneCode(this)};
                clearInterval(countdowntime);
                ele.value="Get voice verification code";
            }
        },1000);

    });
}

var checkMsgFage=0;
var lastMsg=0;
function checkMsgCode(ele){
    var space="";
    var urlName = "/user/reg/verifyPhoneMsg?random="+Math.round(Math.random()*100);
    var codeStr=ele.value;
    var regName=document.getElementById("regUserName").value;
    if(lastMsg!=codeStr){
        lastMsg=ele.value;
        jQuery.get(urlName,{codeStr:codeStr,regName:regName,areacode:"86"},function(data){
            if(data=="0"||data==0){
                showerrortips("regPhoneVel", space+"SMS verification code error");
                checkMsgFage=false;
                return checkMsgFage;
            }
            else{
                hideerrortips("regPhoneVel");
                checkMsgFage=true;
                return checkMsgFage;
            }
        });
    }
}


function cart_add_animate(b){
    b=$(b);
    if(typeof b!="undefined"){
        var a=jQuery(window).height()-(jQuery(b).offset().top-jQuery(window).scrollTop())-100;
        if(a<200){a=200;}
        // alert(jQuery(b).offset().left);
        // alert(a);
        jQuery("#cart-add-effect").css({left:jQuery(b).offset().left,bottom:a});
        jQuery("#cart-add-effect").show().animate(
            {bottom:"10px",opacity:0},
            800,
            function(){
                jQuery("#cart-add-effect").css({bottom:"200px",opacity:1,display:"none"});
            }
        );

    }
}

/** *******BiHiroshilog in********************* */
function openss(url){
    if(url==null || url==''){
        url=window.location.href;
    }
    window.open('/link/weibo/call?url='+url,'new','height='+450+',,innerHeight='+450+',width='+550+',innerWidth='+550+',top='+200+',left='+200+',toolbar=no,menubar=no,scrollbars=auto,resizeable=no,location=no,status=no');
}
/** *******QQlog in****************************** */
function openqq(url){
    if(url==null||url==""){
        url=window.location.href;
    }
    window.open('/qqLogin?url='+url,'new','height='+550+',,innerHeight='+550+',width='+600+',innerWidth='+600+',top='+200+',left='+200+',toolbar=no,menubar=no,scrollbars=auto,resizeable=no,location=no,status=no');
}

/**
 * Refresh the latest market data
 */
function handleTicker(){

    var symbol = document.getElementById("symbol") ;
    var symbol_value = 0 ;
    if(symbol!=null){
        symbol_value = symbol.value ;
    }
    var url = "/real/ticker?symbol="+symbol_value+"&random="+Math.round(Math.random()*100);
    jQuery.post(url,null,function(data){
        if(data != null){
            var ticker = data;
            if(ticker!=null){

                // The topmost price
                jQuery.each(ticker,function(key,value){
                    var element = document.getElementById(key+'') ;
                    var img = jQuery("#"+key+'img') ;
                    if(element!=null){
                        if(parseFloat(element.innerHTML)>parseFloat(value)){
                            img.attr("src","/static/front/images/down.png") ;
                        }else if(parseFloat(element.innerHTML)<parseFloat(value)){
                            img.attr("src","/static/front/images/up.png") ;
                        }else{
                            img.attr("src","/static/front/images/blank.png") ;
                        }
                        element.innerHTML = ff(value)+'';
                    };
                }) ;


                // Quotes latest price update page
                if(document.getElementById("marketLast")!=null){
                    var last = ff(ticker.last)+"";
                    if(last != "" && last != 0){
                        document.getElementById("marketLast").innerHTML=last;
                    }
                    if(ticker.buy != "" && ticker.buy != 0){
                        document.getElementById("marketBuy").innerHTML=ff(ticker.buy);
                    }
                    if(ticker.sell != "" && ticker.sell != 0){
                        document.getElementById("marketSell").innerHTML=ff(ticker.sell);
                    }
                    if(ticker.high != "" && ticker.high != 0){
                        document.getElementById("marketHigh").innerHTML=ff(ticker.high);
                    }
                    if(ticker.low != "" && ticker.low != 0){
                        document.getElementById("marketLow").innerHTML=ff(ticker.low);
                    }
                    if(ticker.vol != "" && ticker.vol != 0){
                        document.getElementById("marketVol").innerHTML=ff(ticker.vol);
                    }
                    // Take the integer and fractional
                    var firstPrice = last;
                    var secondPrice = ".00";
                    var lastPrice = last.split(".");
                    if(lastPrice!=null && lastPrice.length==2){
                        firstPrice = lastPrice[0];
                        secondPrice = "."+lastPrice[1];
                    }
                    document.getElementById("marketLastInteger").innerHTML=firstPrice;
                    document.getElementById("marketLastPoint").innerHTML=secondPrice;
                }
            }
        }
    },"json");
}
/**
 * Refresh buying and selling five
 */
var entrustTime;
var secondNumber;
var updateTime;
var updateNumber;
function handleEntrust(speed){
    jQuery.ajaxSetup ({
        cache: false
    });
    var symbol = document.getElementById("symbol").value;
    var tradetype = document.getElementById("tradeType").value;
    var url = "/real/handleEntrust?symbol="+symbol+"&tradetype="+tradetype+"&random="+Math.round(Math.random()*100);
    jQuery("#coinBoxbuybtc").load(url,function (data){
        entrustTime = setTimeout("handleEntrust("+speed+")", speed);
        updateNumber = speed/1000-1;
        if(updateTime != null){
            clearInterval(updateTime);
        }
        updateTime = setInterval(updateNumberFun, 1000);
        if(typeof(entrustInfo)=="function"){
            entrustInfo();
        }

        // add by hank
        //jQuery("#entrustInfo .cur a").trigger("click") ;
        if(typeof(RefreshTotalUSD)=="function"){
            RefreshTotalUSD();
        }
    });
}

function updateNumberFun(){
    document.getElementById("secondNumber").innerHTML = updateNumber;
    if(updateNumber >0){
        updateNumber -- ;
    }
}
/**
 * Send the verification code
 *
 * @param func
 *            modify by hank
 */
var secs = 121;
function sendMsgCode(type,tipElement_id,button_id){
    var space=""
    var tipElement = document.getElementById(tipElement_id) ;
    var button = document.getElementById(button_id) ;
    var msgtype=document.getElementById(button_id+"Sign").value;
    var url = "/user/sendMsg?type="+type+"&random="+Math.round(Math.random()*100);
    jQuery.post(url,{"msgtype":msgtype},function(data){
        if(data != null ){
            if(data == -3){
                if(tipElement!=null){
                    tipElement.innerHTML = space+"SMS verification code errorrepeatedly，please2Hours and try again!";
                }
            }else if(data == -2){
                if(tipElement!=null){
                    tipElement.innerHTML = space+"younot yetHaveBound phone!";
                }
            }else if(data == 0){
                button.disabled = true;
                for(var num=1;num<=secs;num++) {
                    window.setTimeout("updateNumber(" + num + ",'"+button_id+"',2)", num * 1000);
                }
            }
        }
    },"json");
}

function sendFindPwdMsgCode(fid,ev_id,newuuid,tipElement_id,button_id){
    var tipElement = document.getElementById(tipElement_id) ;
    var button = document.getElementById(button_id) ;

    var url = "/validate/sendMsg?random="+Math.round(Math.random()*100);
    var param={fid:fid,ev_id:ev_id,newuuid:newuuid};
    jQuery.post(url,param,function(data){
        if(data != null ){
            if(data == -10){
                if(tipElement!=null){
                    tipElement.innerHTML = "This nextForgot your passwordHas failed, pleaseAgainApplication!";
                }
            }else if(data == -3){
                if(tipElement!=null){
                    tipElement.innerHTML = "SMS verification code errorrepeatedly，please2Hours and try again!";
                }
            }else if(data == -2){
                if(tipElement!=null){
                    tipElement.innerHTML = "younot yetHaveBound phone!";
                }
            }else if(data == 0){
                button.disabled = true;
                for(var num=1;num<=secs;num++) {
                    window.setTimeout("updateNumber(" + num + ",'"+button_id+"')", num * 1000);
                }
            }
        }
    });
}

function updateNumber(num,button_id,isVoice) {
    var button = document.getElementById(button_id) ;
    if (num == secs) {
        if(isVoice){
            document.getElementById(button_id+"Sign").value=2;//Send voice messages
            button.value="Send voice verification code";
        }
        button.disabled = false;
    } else {
        var printnr = secs - num;
        button.value= printnr +"After retransmission seconds";
    }
}
function updateNumberBindAuth(num) {
    if (num == secs) {
        document.getElementById("msgCodeBtn2").value="Send the verification code";
        document.getElementById("msgCodeBtn2").disabled = false;
    } else {
        var printnr = secs - num;
        document.getElementById("msgCodeBtn2").value= printnr +"After retransmission seconds";
    }
}
function changeMsgCode(num) {
    if (num == secs) {
        document.getElementById("changeMsgCodeBtn").value="Send the verification code";
        document.getElementById("changeMsgCodeBtn").disabled = false;
    } else {
        var printnr = secs - num;
        document.getElementById("changeMsgCodeBtn").value= printnr +"After retransmission seconds";
    }
}
function configureMsgCode(num) {
    if (num == secs) {
        document.getElementById("configureMsgCodeBtn").value="Send the verification code";
        document.getElementById("configureMsgCodeBtn").disabled = false;
    } else {
        var printnr = secs - num;
        document.getElementById("configureMsgCodeBtn").value= printnr +"After retransmission seconds";
    }
}

function updateNumberAddr(num) {
    if (num == secs) {
        document.getElementById("msgCodeAddrBtn").value="Send the verification code";
        document.getElementById("msgCodeAddrBtn").disabled = false;
    } else {
        var printnr = secs - num;
        document.getElementById("msgCodeAddrBtn").value= printnr +"After retransmission seconds";
    }
}
function updateNumberAuth(num) {
    if (num == secs) {
        document.getElementById("msgCodeAuthBtn").value="Send the verification code";
        document.getElementById("msgCodeAuthBtn").disabled = false;
    } else {
        var printnr = secs - num;
        document.getElementById("msgCodeAuthBtn").value= printnr +"After retransmission seconds";
    }
}
/**
 * Tip micro-channel layer
 */
function showWeixinPop(){
    dialogBoxShadow();
    document.getElementById("weixinPop").style.display="";
}
function closeWeixinPop(){
    dialogBoxHidden();
    document.getElementById("weixinPop").style.display="none";
}

function showWeixinSubPop(){
    dialogBoxShadow();
    document.getElementById("weixinSubPop").style.display="";
}
function closeWeixinSubPop(){
    dialogBoxHidden();
    document.getElementById("weixinSubPop").style.display="none";
}
function bindAuth(){
    var callback={okBack:function(){window.location.href= document.getElementById("coinMainUrl").value+"/user/security";},noBack:function(){return false;}};
    okcoinAlert("in order toyouofAccountSafety，pleaseBound phoneorSet upGoogle identityverificationDevice!in caseYou are not bound，You losepasswordAfter that you may findofProperty causedNeed notwantofloss，We are not responsible。",null,callback,"Go to the Security Center");
    if(document.getElementById('riskAreaDiv') != null){
        document.getElementById('riskAreaDiv').style.display = "block";
    }
}
function showPhoneNotOpen(){
    if(document.getElementById("phoneNotOpenDiv") != null){
        document.getElementById("phoneNotOpenDiv").style.display = "block";
    }
}

function callbackEnter(callfun){
    document.onkeydown=function(event){// Enter
        var e = event || window.event || arguments.callee.caller.arguments[0];
        if(e && e.keyCode==13){
            return callfun();
        }
    };
}
/**
 * new
 */
function handleChart(){
    if(-1!=window.navigator.userAgent.indexOf('MSIE 6.0') || -1!=window.navigator.userAgent.indexOf('MSIE 7.0') ||-1!=window.navigator.userAgent.indexOf('MSIE 8.0')) {
        if(document.getElementById("handleChart").style.display=="none"){
            document.getElementById("handleChart").style.marginTop="0px";
            document.getElementById("handleChart").style.display="";
            showKLine(0,3);
        }else{
            document.getElementById("handleChart").style.display="none";
            document.getElementById("handleChart").style.marginTop="-400px";
        }
    }else{
        document.getElementById("handleChart").style.display="";
        if(document.getElementById("handleChart").style.opacity==0){
            jQuery("#handleChart").stop(true).animate({'margin-top':'0px','opacity':'1'},300,function(){
                if(document.getElementById("klineLoading")!=null){
                    document.getElementById("bannerLineOld").style.display="";
                    showKLine(0,3);
                }
            });
        }else{
            jQuery("#handleChart").stop(true).animate({'margin-top':'-400px','opacity':'0'},300);
        }
    }
}
function showKLineType(type){
    if(type==1){// 5minute
        document.getElementById("minuteTitle").className ="cur";
        document.getElementById("dayTitle").className ="";
        document.getElementById("weekTitle").className ="";
    }else if(type==3){// day
        document.getElementById("minuteTitle").className ="";
        document.getElementById("dayTitle").className ="cur";
        document.getElementById("weekTitle").className ="";
    }else if(type==4){// week
        document.getElementById("minuteTitle").className ="";
        document.getElementById("dayTitle").className ="";
        document.getElementById("weekTitle").className ="cur";
    }

    var marketFrom = document.getElementById("marketFromChart").value;
    showKLine(marketFrom,type);
}
function indexLoginOnblur(){
    var uName = document.getElementById("indexLoginName").value;
    if(! checkEmail(uName)&& !checkMobile(uName)){
        showerrortips("indexLoginTips", "mailboxOr phoneformatIncorrect");
    }else{
        hideerrortips("indexLoginTips");
    }
}
function indexLoginNameOnblur(){
    var uName = document.getElementById("indexLoginName").value;
    if(uName=="Please input the email address"){
        document.getElementById("indexLoginName").value="";
    }
}
function loginIndexSubmit(){
    hideerrortips("indexLoginTips");
    var url = "/user/login/index?random="+Math.round(Math.random()*100);
    var uName = document.getElementById("indexLoginName").value;
    var pWord = document.getElementById("indexLoginPwd").value;
    var longLogin=0
    if(checkEmail(uName)){
        longLogin = 1;
    }
    if(! checkEmail(uName) && !checkMobile(uName)){
        showerrortips("indexLoginTips", "mailboxOr phoneformatIncorrect");
        return
    }
    if(pWord == ""){
        showerrortips("indexLoginTips", "password can not be blank");
        return ;
    }else if(pWord.length <6){
        showerrortips("indexLoginTips", "passwordlengthNot less than6!");
        return ;
    }
    var param={loginName:uName,password:pWord,type:longLogin};
    jQuery.post(url,param,function(result){
        if(result!=null){
            if(result.resultCode == 0){
                if(document.getElementById("forwardUrl")!=null && document.getElementById("forwardUrl").value != ""){
                    var forward = document.getElementById("forwardUrl").value;
                    forward = decodeURI(forward);
                    window.location.href=forward;
                }else{
                    var whref = document.location.href;
                    if(whref.indexOf("#") != -1){
                        whref = whref.substring(0,whref.indexOf("#"));
                    }
                    if(whref.length < 30){
                        whref = document.getElementById("coinMainUrl").value+"/trade/tradeContent";
                    }
                    window.location.href=whref;
                }
            }else if(result.resultCode == -1){
                showerrortips("indexLoginTips", "wrong user name or password");
            }else if(result.resultCode == -2){
                showerrortips("indexLoginTips", "thisipLog in frequently,2After hours and try again!");
            }else if(result.resultCode == -3){
                if(result.errorNum == 0){
                    showerrortips("indexLoginTips", "thisipLog in frequently,2After hours and try again!");
                }else{
                    showerrortips("indexLoginTips", "wrong user name or password，youand also"+result.errorNum+"Chance");
                }
                document.getElementById("indexLoginPwd").value="";
            }else if(result.resultCode == -4){
                showerrortips("indexLoginTips", "Set EnableCOOKIEFeatures");
            }else if(result.resultCode == 1){
                window.location.href = document.getElementById("coinMainUrl").value;
            }else if(result.resultCode == 2){
                showerrortips("indexLoginTips", "AccountAppears to be security risksfreeze，please尽快联系客服。");
            }

        }
    },"json");
}
function indexDepthDiv(type){
    var url = "/real/indexDepth?symbol="+type+"&random="+Math.round(Math.random()*100);
    jQuery("#depthDiv").load(url,function (data){
    });
}
function trimValue(obj){
    var value = obj.value;
    value = value.replace(new RegExp("　","gm"),'');
    value = value.replace(/^\s+|\s+$/g,"");
    obj.value = value;
}

function submitTotpCode(){
    var totpCode = document.getElementById("totpCode").value;
    var regu = /^[0-9]{6}$/;
    var re = new RegExp(regu);
    if (!re.test(totpCode)) {
        document.getElementById("errorSpan").style.display = "block";
        document.getElementById("errorSpan").innerHTML = "Please correctenterGoogleVerification code。";
        return ;
    }
    var url = "/user/login/submitTotpCode?random="+Math.round(Math.random()*100);
    var param={totpCode:totpCode};
    jQuery.post(url,param,function(data){
        var result = eval('(' + data + ')');
        if(result!=null){
            if(result.resultCode == 0){
                var whref = document.location.href;
                if(whref.indexOf("#") != -1){
                    whref = whref.substring(0,whref.indexOf("#"));
                }
                if(whref.length < 30){
                    whref = document.getElementById("coinMainUrl").value+"/trade/tradeContent";
                }
                window.location.href=whref;
            }else if(result.resultCode == -1){
                document.getElementById("errorSpan").style.display = "block";
                document.getElementById("errorSpan").innerHTML = "Please Enter correct google code";
            }else if(result.resultCode == -2){
                document.getElementById("errorSpan").style.display = "block";
                if(result.errorNum == 0){
                    document.getElementById("errorSpan").innerHTML="log inverificationerrorrepeatedly，please2After hours and try again";
                }else{
                    document.getElementById("errorSpan").innerHTML="log inverificationerror，youand also"+result.errorNum+"Chance";
                }
            }
        }
    });
}

function controlDisplayQQGroup(){
    document.getElementById('QQRest').style.display="";
    document.getElementById('controlDisplayQQGroup').style.display="none";
    document.getElementById('controlHiddenQQGroup').style.display="";
}

function controlHiddenQQGroup(){
    document.getElementById('QQRest').style.display="none";
    document.getElementById('controlDisplayQQGroup').style.display="";
    document.getElementById('controlHiddenQQGroup').style.display="none";
}

function showBackLeft(){
    var okhelp = new CookieClass();
    if(okhelp.getCookie("okhelp") == "" || okhelp.getCookie("okhelp") == 1){
        okhelp.setCookie("okhelp", 0);
    }
    document.getElementById('backtop2').style.display="block";
    document.getElementById('okRight').style.display="block";
    var okleft = document.getElementById('okLeft');
    if(okleft!=null){
        okleft.style.display="none";
    }
}

function checkNumberByName2(name,decimal){
    var number = document.getElementById(name).value.split('.');
    if(number.length > 1){
        return number[0].replace(/\D/g, '') + '.' + number[1].replace(/\D/g, '').slice(0, decimal);
    }else{
        return number[0].replace(/\D/g,'');
    }
}

function showBackRight(){
    var okhelp = new CookieClass();
    if(okhelp.getCookie("okhelp") == "" || okhelp.getCookie("okhelp") == 0){
        okhelp.setCookie("okhelp", 1);
    }
    document.getElementById('backtop2').style.display="none";
    document.getElementById('okRight').style.display="none";
    document.getElementById('okLeft').style.display="block";
}
// topkline according tokWhen using the version line switching
function changeSymbol(type){
    if(type=="1"){
        document.getElementById("okcoinTitle").className ="cur";
        document.getElementById("mtgoxTitle").className ="";
        document.getElementById("bitstampTitle").className ="";
        document.getElementById("okcoinLtcTitle").className ="";
        if(document.getElementById("bannerLineOld").style.display!="none"){
            document.getElementById("bannerLineOld").style.display="none";
            document.getElementById("oldLineTime").style.display="none";
            document.getElementById("bannerLineNew").style.display="";
            document.getElementById("klineFullScreen").src="/kline/start?symbol="+type;
        }else{
            document.getElementById("bannerLineNew").style.display="";
            document.getElementById("oldLineTime").style.display="none";
            document.getElementById("klineFullScreen").src="/kline/start?symbol="+type;
        }
    }else if(type=="2"){
        document.getElementById("okcoinTitle").className ="";
        document.getElementById("mtgoxTitle").className ="";
        document.getElementById("bitstampTitle").className ="";
        document.getElementById("okcoinLtcTitle").className ="cur";
        if(document.getElementById("bannerLineOld").style.display!="none"){
            document.getElementById("bannerLineOld").style.display="none";
            document.getElementById("oldLineTime").style.display="none";
            document.getElementById("bannerLineNew").style.display="";
            document.getElementById("klineFullScreen").src="/kline/start?symbol="+type;
        }else{
            document.getElementById("bannerLineNew").style.display="";
            document.getElementById("oldLineTime").style.display="none";
            document.getElementById("klineFullScreen").src="/kline/start?symbol="+type;
        }
    }else if(type=="3"){
        if(document.getElementById("bannerLineNew").style.display!="none"){
            document.getElementById("bannerLineNew").style.display="none";
            document.getElementById("bannerLineOld").style.display="";
            document.getElementById("oldLineTime").style.display="";
            showKLine(1,3);
        }else{
            document.getElementById("bannerLineOld").style.display="";
            document.getElementById("oldLineTime").style.display="";
            showKLine(1,3);
        }
    }else if(type=="4"){
        if(document.getElementById("bannerLineNew").style.display!="none"){
            document.getElementById("bannerLineNew").style.display="none";
            document.getElementById("oldLineTime").style.display="";
            document.getElementById("bannerLineOld").style.display="";
            showKLine(4,3);
        }else{
            document.getElementById("bannerLineOld").style.display="";
            document.getElementById("oldLineTime").style.display="";
            showKLine(4,3);
        }
    }
}
function subPoint(value){
    var reg=/^(-?\d*)\.?\d{1,4}$/;
    if(value!=null && value.toString().split(".")!=null && value.toString().split(".")[1]!=null && value.toString().split(".")[1].length>4){
        if(!reg.test(value)){
            var end =  value.toString().split(".")[1];
            if(end.length>4){
                end = end.substring(0, 4);
            }
            value = value.toString().split(".")[0]+"."+end;
        }
    }
    return value;
}
function subPoint2(value){
    var reg=/^(-?\d*)\.?\d{1,2}$/;
    if(value!=null && value.toString().split(".")!=null && value.toString().split(".")[1]!=null && value.toString().split(".")[1].length>2){
        if(!reg.test(value)){
            var end =  value.toString().split(".")[1];
            if(end.length>2){
                end = end.substring(0, 2);
            }
            value = value.toString().split(".")[0]+"."+end;
        }
    }
    return value;
}

function changeBtn(id,isInter){
    document.getElementById(id).value="Send the verification code";
}



// Digital input filter
function checkNumberByName(name){
    var number = document.getElementById(name).value.split('.');
    if(number.length > 1){
        return number[0].replace(/\D/g, '') + '.' + number[1].replace(/\D/g, '').slice(0, 4);
    }else{
        return number[0].replace(/\D/g,'');
    }
}
function checkNumberByObj(obj){
    var number = obj.value.split('.');
    if(number.length > 1){
        return number[0].replace(/\D/g, '') + '.' + number[1].replace(/\D/g, '').slice(0, 4);
    }else{
        return number[0].replace(/\D/g,'');
    }
}

function checkNumberByObj(obj,lenth){
    var number = obj.value.split('.');
    if(number.length > 1){
        return number[0].replace(/\D/g, '') + '.' + number[1].replace(/\D/g, '').slice(0, lenth);
    }else{
        return number[0].replace(/\D/g,'');
    }
}

// To obtain cursor position
function getPositionForInput(ctrl){
    var CaretPos = 0;
    if (document.selection) { // IE Support
        ctrl.focus();
        var Sel = document.selection.createRange();
        Sel.moveStart('character', -ctrl.value.length);
        CaretPos = Sel.text.length;
    }else if(ctrl.selectionStart || ctrl.selectionStart == '0'){// Firefox
        // support
        CaretPos = ctrl.selectionStart;
    }
    return (CaretPos);
}
// Set the cursor position
function setCursorPosition(ctrl, pos){
    if(ctrl.setSelectionRange){
        ctrl.focus();
        ctrl.setSelectionRange(pos,pos);
    }
    else if (ctrl.createTextRange) {
        var range = ctrl.createTextRange();
        range.collapse(true);
        range.moveEnd('character', pos);
        range.moveStart('character', pos);
        range.select();
    }
}
// addition
function accAdd(arg1,arg2){
    var r1,r2,m;
    try{r1=arg1.toString().split(".")[1].length;}catch(e){r1=0;}
    try{r2=arg2.toString().split(".")[1].length;}catch(e){r2=0;}
    m=Math.pow(10,Math.max(r1,r2));
    return (arg1*m+arg2*m)/m;
}
// multiplication
function accMul(arg1,arg2) {
    var m=0,s1=arg1.toString(),s2=arg2.toString();
    try{m+=s1.split(".")[1].length;}catch(e){}
    try{m+=s2.split(".")[1].length;}catch(e){}
    return Number(s1.replace(".",""))*Number(s2.replace(".",""))/Math.pow(10,m);
}
// division
function accDiv(arg1,arg2){
    var t1=0,t2=0,r1,r2;
    try{t1=arg1.toString().split(".")[1].length;}catch(e){}
    try{t2=arg2.toString().split(".")[1].length;}catch(e){}
    with(Math){
        r1=Number(arg1.toString().replace(".",""));
        r2=Number(arg2.toString().replace(".",""));
        return (r1/r2)*pow(10,t2-t1);
    }
}
// compatiblectrlAAnd key combinationstabkey
function ctrlAorTab(event){
    if(event !=null && (event.keyCode==9 || event.keyCode==17)){
        return true;
    }
    if(event !=null && event.ctrlKey && event.keyCode==65){
        return true;
    }
}

function indexLoginNameOnfocus(){
    var uName = document.getElementById("indexLoginName").value;
    if(uName=="Please input the email address"){
        document.getElementById("indexLoginName").value="";
    }
}

var keycount = 1;
function clearTig(obj){
    if(keycount==1){
        obj.value="";
        keycount++;
    }
}

function submitTotpCode(){
    var totpCode = document.getElementById("totpCode").value;
    var regu = /^[0-9]{6}$/;
    var re = new RegExp(regu);
    if (!re.test(totpCode)) {
        document.getElementById("errorSpan").style.display = "block";
        document.getElementById("errorSpan").innerHTML = "Please correctenterGoogleVerification code。";
        return ;
    }
    var url = "/user/login/submitTotpCode?random="+Math.round(Math.random()*100);
    var param={totpCode:totpCode};
    jQuery.post(url,param,function(data){
        var result = eval('(' + data + ')');
        if(result!=null){
            if(result.resultCode == 0){
                window.location.href='/';
            }else if(result.resultCode == -1){
                document.getElementById("errorSpan").style.display = "block";
                document.getElementById("errorSpan").innerHTML = "Please correctenterGoogleVerification code。";
            }else if(result.resultCode == -2){
                document.getElementById("errorSpan").style.display = "block";
                if(result.errorNum == 0){
                    document.getElementById("errorSpan").innerHTML="log inverificationerrorrepeatedly，please2After hours and try again";
                }else{
                    document.getElementById("errorSpan").innerHTML="log inverificationerror，youand also"+result.errorNum+"Chance";
                }
            }
        }
    });
}


function postValidateMail(email){
    var url = "/validate/postValidateMail?random="+Math.round(Math.random()*100);
    jQuery("#sendValidate").attr("disabled","disabled") ;
    jQuery.post(url,{email:email},function(data){
        if(data!=null){
            if(data == 0){
                okcoinAlert("verificationmailAlreadysend，Pleaseverification!","",null);
            }else if(data == -1){
                okcoinAlert("5minute内Onlysend一Secondaryverificationmail!","",null);
            }else if(data == -2){
                okcoinAlert("mailboxaddressBound，Replacemailboxaddress!","",null);
            }
        }
    },"json");
}

function ff(src,count)
{
    if(!count)
    {
        count=4
    }
    return (Math.round(src*Math.pow(10, count))/Math.pow(10, count));
}

function totalAssetsBox(obj,elem,updown){
    var nmove,mmove,
        d = document,
        o = d.getElementById(obj),
        s = d.getElementById(elem);
    u = d.getElementById(updown);
    if(!o){ return false;}
    if(!s){ return false;}
    if(!u){ return false;}

    s.onmouseover=function(){
        clearTimeout(nmove);
        s.style.display="block";
        u.className = "controlUp";
    };

    o.onmouseover=function(){
        clearTimeout(nmove);
        mmove=setTimeout(function(){
            s.style.display="block";
            u.className = "controlUp";
        },100);
    }

    o.onmouseout=function(){
        clearTimeout(mmove);
        nmove=setTimeout(function(){s.style.display="none";u.className = "controlDown";},500);
    };
    s.onmouseout=function(){
        nmove=setTimeout(function(){s.style.display="none";u.className = "controlDown";},500);
    };
    s.onmousedown=function(e){
        stopBubble(e);
    };
}
totalAssetsBox("totalAssets","totalAssetsTable","controlUpDown");

!function () {
    Jquery("#trade").mouseover(function () {
        jQuery("#user_slide").addClass("optionactive");
        jQuery("#user_slide_box").slideDown(100);});
    Jquery("#trade").mouseleave(function () {
        jQuery("#user_slide").addClass("optionactive");
        jQuery("#user_slide_box").slideDown(100);});
}

/**
 * Displays an error message
 */
function showerrortips(id, value) {
    if (value != "") {
        document.getElementById(id).innerHTML = value;
        document.getElementById(id).parentNode.style.display = 'block';
    } else {
        document.getElementById(id).innerHTML = value;
        document.getElementById(id).parentNode.style.display = 'none';
    }
}
/**
 * Hide error message
 *
 * @param id
 * @param isall
 *            whetherhideallerrornews
 */
function hideerrortips(id, isall) {
    if (isall) {
        jQuery("span.errortips", ".coin_dialog").hide();
    } else {
        document.getElementById(id).parentNode.style.display = 'none';
    }
}