btc_rmb_ajax = 10;
ybc_rmb_loan = 10;

var widthNumArr = {'goc':0.05, 'btc':10, 'ltc':0.1, 'lc': 0.005, 'lsk':0.005, 'eth':0.5, 'etc':0.2};

function badFloat(num, size){
    if(isNaN(num)) return true;
    num += '';
    if(-1 == num.indexOf('.')) return false;
    var f_arr = num.split('.');
    if(f_arr[1].length > size){
        return true;
    }
    return false;
}

//formatOf fractional
function formatfloat(f, size, add){
    f = parseFloat(f);
    var set=[];
    if(size == 2) set = [100,0.01];
    if(size == 3) set = [1000,0.001];
    if(size == 4) set = [10000,0.0001];
    if(size == 5) set = [100000,0.00001];
    if(size == 6) set = [1000000,0.000001];
    if(size == 7) set = [10000000,0.0000001];
    if(size == 8) set = [100000000,0.00000001];

    var ff = Math.floor(f * set[0]) / set[0];
    if(add && f > ff) ff += set[1];
    return ff;
}
//formatOf fractional
function loanformatfloat(f, size, add){
    f = parseFloat(f);
    var set;
    if(size == 2){
        set = [100, 0.01];
    }else if(size == 3){
        set = [1000, 0.001];
    }else if(size == 5){
        set = [100000, 0.00001];
    }else {
        return f;
    }
    var ff = Math.round(f * set[0]) / set[0];
    if(add && f > ff) ff += set[1];
    return ff;
}

function Dom(o){return document.getElementById(o)}
function ua_refresh(userPairs){
    ua_usd = Dom('ua_usd');

    Dom('ua_usd_over').innerHTML = formatfloat(user.usd_over,4)+' USD';
    Dom('ua_usd_lock').innerHTML = formatfloat(user.usd_lock,4)+' USD';

    var coinsRmb = {};	// Deposit-currencycurrentprice
    for(var i in userPairs){
        Dom('ua_'+userPairs[i]+'_over').innerHTML = formatfloat(user[userPairs[i]+'_over'], 5)+' '+userPairs[i].toUpperCase();
        Dom('ua_'+userPairs[i]+'_lock').innerHTML = formatfloat(user[userPairs[i]+'_lock'], 5)+' '+userPairs[i].toUpperCase();
    }

    if(btc_rmb_ajax == 10){
        $.get('/market/coinprice', function(data){
            var sumPrice = parseFloat(user.usd_lock) + parseFloat(user.usd_over);
            for(var i in userPairs){
                var name = userPairs[i]+'_usd_rmb';
                var price = parseFloat(data[name]);
                if(price){
                    sumPrice += ( parseFloat(user[userPairs[i]+'_lock']) + parseFloat(user[userPairs[i]+'_over']) ) * price;
                }
            }
            ua_usd.innerHTML = formatfloat(sumPrice, 5).toFixed(2);
        }, "json");
        btc_rmb_ajax = 0;
    }
    btc_rmb_ajax++;
}
function btcsum(limit,coin,price_float,number_float){
    var coArr = coin.split('_');
    $.get('/json/'+coin+'_sum', function(data){
        for(var type in data){
            var d = data[type];
            var html = '';
            var idhtml = '';
            /*if(limit==5 && type=='sale'){
             var dsort=[];
             var dmax = d.length>limit?limit:d.length;
             for(var j=dmax;j>0;j--) dsort[limit-j] = d[j-1];
             d=dsort;
             }*/
            for(var i in d){
                if(limit && i == limit) break;
                var num = parseFloat(d[i].n), width = num*widthNumArr[coArr[0]]>289 ? 289 : num*widthNumArr[coArr[0]];
                /*if(type=='buy'){
                 idhtml = '<td class="buy" style="width:50px;">buy('+(parseInt(i)+1)+')</td>';
                 } else {
                 idhtml = '<td class="sell" style="width:50px;">Sell('+(parseInt(i)+1)+')</td>';
                 }*/
                idhtml = '<td class="'+(type=='buy'?'buy':'sell')+'" style="width:50px;">'+(type=='buy'?'buy':'Sell')+'('+(parseInt(i)+1)+')</td>';
                html += '<tr style="position:relative;">'+idhtml+'<td style="padding-left:5px; text-align:left;">$'+parseFloat(d[i].p)+'</td><td style="padding-left:5px; text-align:left;">'+num+'</td><td style="width:'+width+'px" style="border:0;" class="'+(type=='buy'?'sell':'buy')+'Span"></td></tr>';
            }
            $('#'+type+'list').html(html);
            $('#'+coin+'_'+type+'one').html(typeof d[0] == 'undefined'? 0: Number(d[0].p).toFixed(price_float));
        }
        if ( data['sale'].length != 0) {
            $('#bpricenice').html(Number(data['sale'][0].p).toFixed(price_float));
            if(user.length != 0){
                $('#buy_max').html(formatfloat(user['usd_over']/Number(data['sale'][0].p) - Math.pow(10,-number_float), number_float, 0));
            }
        }
        if (data['buy'].length != 0) {
            $('#spricenice').html(Number(data['buy'][0].p).toFixed(price_float));
        }
        // setTimeout("btcsum("+limit+",'"+coin+"', "+price_float+", "+number_float+")", 6000);
    }, 'json');
}
function btcorder(limit,coin,display,price_float,number_float){
    $.get('/json/'+coin+'_order', function(d){
        var html = '';
        for(var i in d.d){
            if(limit && i > limit) break;
            if(30==limit){
                html += '<tr><td>'+d.d[i].t+'</td><!--<td class="'+d.d[i].s+'">'+(d.d[i].s=='buy'?'Buy':'Sell')+
                    '</td>--><td class="'+d.d[i].s+'" style="padding-left:5px; text-align:left;">$'+d.d[i].p+'</td><td style="padding-left:5px; text-align:left;">'+d.d[i].n+'</td></tr>';
            } else {
                html += '<tr><td>'+d.d[i].t+'</td><td class="'+d.d[i].s+'">$'+d.d[i].p+'</td><td class="tableEnd">Ф '+d.d[i].n+'</td></tr>';
            }
        }
        $('#orderlist').html(html);
        // Updateprice
        if(typeof d.d[0] == 'undefined'){
            d.d[0] = {"p":'0.0'}
        }
        d.d[0].p = d.d[0].p + '';
        var price = d.d[0].p.split('.');
        var btc_rmb = formatfloat(d.d[0].p);
        if(price.length == 1)price[1] = '00';
        $('#'+coin+'_rmb_box').html("$"+price[0]+"<b>."+(price[1].substring(0, price_float)) + "</b>");
        $('#'+coin+'_rmb_new').html(formatfloat(d.d[0].p, price_float));
        $('#'+coin+'_rmb_new').html(formatfloat(d.d[0].p, price_float));
        $('#'+coin+'_min_box').html(d.min);
        $('#'+coin+'_max_box').html(d.max);
        $('#'+coin+'_sum_box').html(formatfloat(d.sum, number_float))
        var zd_rmb = (parseFloat(d.min) + parseFloat(d.max)) / 2;
        zd_rmb = ((btc_rmb - zd_rmb) / zd_rmb * 100).toString().split('.');
        if(typeof zd_rmb[1] == 'undefined'){
            zd_rmb = ['0','0'];
        }
        $('#'+coin+'_zd_box').html(zd_rmb[0] + '.' + zd_rmb[1].substr(0, 2)+'%');

        document.title = display+d.d[0].p+" | Trading Center-Love currency net-Safetydigitalcurrencytransactionplatform";

        setTimeout("btcorder("+limit+",'"+coin+"', '"+display+"', "+price_float+", "+number_float+")", 6000);
    }, 'json');
}
function mytrust(type, coin_from, price_float, number_float) {
    $.get('/Ajax/mytrust/coin/'+coin_from+'/type/'+type, function(d){
        var html = "";
        var length  = d.data.length;
        if(length > 0){
            for (var i = 0; i < length; i++) {
                if (i % 2 == 0) {
                    html += "<tr bgcolor=\"#E6F0E6\" id='tru"+ d.data[i].id +"'> ";
                } else {
                    html += "<tr bgcolor=\"#F5F5F1\" id='tru"+ d.data[i].id +"'>";
                }
                if (d.data[i].flag == 'buy') html += "<td class=\"ttbtn\">buy</td>";
                else html += "<td class=\"trbtn\">Sell</td>";
                html += "<td>$" + formatfloat(d.data[i].price, price_float, 0) +"</td>";
                html += "<td>" + formatfloat(d.data[i].numberover, number_float, 0) + "</td>";
                html += "<td><a class=\"remove\" href=\"javascript:void(0);\" onclick=\"trustcancel(" + d.data[i].id + ","+type+", '"+coin_from+"',"+number_float+")\">Undo</a></td>";
                html += "</tr>";
            }
        } else {
            html = "<tr><td colspan=4>No transaction commission is not</td></tr>";
        }
        $('#mytrustlist').html(html);
        setTimeout('mytrust('+type+', "'+coin_from+'", '+price_float+', '+number_float+')', 6000);
    }, 'json');
}

// socket push
function socketConnection(limit, coin, display, price_float, number_float) {
    if(!window.WebSocket){//Supportwebsocket
        btcsum(limit, coin, price_float, number_float);
        btcorder(limit, coin, display, price_float, number_float);
        return false;
    }

    var coArr = coin.split('_');

    if( parseInt(isonline) == 1 ){
        var target = "wss://real.qqibtc.com:13442";
    }else if(parseInt(isonline) == 2){
        var target = "wss://real.abwang.com:13442";
    }else{
        var target = "ws://real.5ituya.com:13442";
    }

    var socket = io.connect(target);

    socket.on('connect', function() {
        // console.log("socket socketConnection");
    });

    socket.on('message'+coin, function(result) {
        // sum
        var data = result.sum;
        data = JSON.parse(data);
        for(var type in data){
            var d = data[type];
            var html = '';
            var idhtml = '';
            /*if(limit==5 && type=='sale'){
             var dsort=[];
             var dmax = d.length>limit?limit:d.length;
             for(var j=dmax;j>0;j--) dsort[limit-j] = d[j-1];
             d=dsort;
             }*/
            for(var i in d){
                if(limit && i == limit) break;
                var num = parseFloat(d[i].n), width = num*widthNumArr[coArr[0]]>289 ? 289 : num*widthNumArr[coArr[0]];
                /*if(type=='buy'){
                 idhtml = '<td class="buy" style="width:50px;">buy('+(parseInt(i)+1)+')</td>';
                 } else {
                 idhtml = '<td class="sell" style="width:50px;">Sell('+(limit==5? dmax--: parseInt(i)+1)+')</td>';
                 }*/
                idhtml = '<td class="'+(type=='buy'?'buy':'sell')+'" style="width:50px;">'+(type=='buy'?'buy':'Sell')+'('+(parseInt(i)+1)+')</td>';
                html += '<tr style="position:relative;">'+idhtml+'<td style="padding-left:5px; text-align:left;">$'+parseFloat(d[i].p)+'</td><td style="padding-left:5px; text-align:left;">'+num+'</td><td style="width:'+width+'px" style="border:0;" class="'+(type=='buy'?'sell':'buy')+'Span"></td></tr>';
            }
            $('#'+type+'list').html(html);
            $('#'+coin+'_'+type+'one').html(typeof d[0] == 'undefined'? 0: Number(d[0].p).toFixed(price_float));
        }
        if ( data['sale'].length != 0) {
            $('#bpricenice').html(Number(data['sale'][0].p).toFixed(price_float));
            if(user.length != 0){
                $('#buy_max').html(formatfloat(user['usd_over']/Number(data['sale'][0].p) - Math.pow(10,-number_float), number_float, 0));
            }
        }
        if (data['buy'].length != 0) {
            $('#spricenice').html(Number(data['buy'][0].p).toFixed(price_float));
        }


        // order
        var d = result.order;
        d = JSON.parse(d);
        var html = '';
        for(var i in d.d){
            if(limit && i > limit) break;
            if(30==limit){
                html += '<tr><td>'+d.d[i].t+'</td><!--<td class="'+d.d[i].s+'">'+(d.d[i].s=='buy'?'Buy':'Sell')+
                    '</td>--><td class="'+d.d[i].s+'" style="padding-left:5px; text-align:left;">$'+d.d[i].p+'</td><td style="padding-left:5px; text-align:left;">'+d.d[i].n+'</td></tr>';
            } else {
                html += '<tr><td>'+d.d[i].t+'</td><td class="'+d.d[i].s+'">$'+d.d[i].p+'</td><td class="tableEnd">Ф '+d.d[i].n+'</td></tr>';
            }
        }
        $('#orderlist').html(html);
        // Updateprice
        if(typeof d.d[0] == 'undefined'){
            d.d[0] = {"p":'0.0'}
        }
        d.d[0].p = d.d[0].p + '';
        var price = d.d[0].p.split('.');
        var btc_rmb = formatfloat(d.d[0].p);
        if(price.length == 1)price[1] = '00';
        $('#'+coin+'_rmb_box').html("$"+price[0]+"<b>."+(price[1].substring(0, price_float)) + "</b>");
        $('#'+coin+'_rmb_new').html(formatfloat(d.d[0].p, price_float));
        $('#'+coin+'_rmb_new').html(formatfloat(d.d[0].p, price_float));
        $('#'+coin+'_min_box').html(d.min);
        $('#'+coin+'_max_box').html(d.max);
        $('#'+coin+'_sum_box').html(formatfloat(d.sum, number_float))
        var zd_rmb = (parseFloat(d.min) + parseFloat(d.max)) / 2;
        zd_rmb = ((btc_rmb - zd_rmb) / zd_rmb * 100).toString().split('.');
        if(typeof zd_rmb[1] == 'undefined'){
            zd_rmb = ['0','0'];
        }
        $('#'+coin+'_zd_box').html(zd_rmb[0] + '.' + zd_rmb[1].substr(0, 2)+'%');

        document.title = display+d.d[0].p+" | Trading Center-Love currency net-Safetydigitalcurrencytransactionplatform";
    });

    socket.on('connect_failed', function() {});

    socket.on('error', function() {});

    socket.on('reconnecting', function () {});

    socket.on('reconnect', function () {});

    socket.on('disconnect', function () {
        console.log("disconnect");
    });
}




function hourprice(t){
    $.get('/'+t+'_hour_price?t='+Math.random(), function(d){
        $('#hour_price').html(d)
        setTimeout("hourprice()", 30000);
    }, 'json');
}
function yestprice(t){
    var now = new Date();
    datenow = now.getFullYear()+((now.getMonth()+1)<10?"0":"")+(now.getMonth()+1)+(now.getDate()<10?"0":"")+now.getDate();
    $.get('/dayprice/'+t+'_'+datenow+'?t='+Math.random(), function(d){
        $('#hour_price').html(d)
    }, 'json');
}
function ajaxtrustcancel(id, type){
    $.get('/Ajax/trustcancel/id/'+id+'/type/'+type, function(d){
        alert(d.msg);
        if(d.status){
            $('#t_s_'+id).addClass('cancel');
            $('#t_s_'+id).html('Revoked');
            $('#t_opt_'+id).html('');
        }
    }, 'json');
}

function trustcancel(id, type, coin_from, number_float){
    $.get('/Ajax/trustcancel/id/'+id+'/type/'+type, function(d){
        $('#tErrorTips').html(d.msg).show();
        if(d.status){
            $('#tru'+id).remove();

            for(var i in d.data) user[i] = d.data[i];

            freshAsset(user, coin_from, number_float);

            setTimeout(function() {
                $('#tErrorTips').hide();
            }, 2000);
        }
    }, 'json');
}

// Update assets
function freshAsset(user, coin, number_float){
    var bprice = parseFloat($('#bpricenice').html());
    $('#buy_max').html(formatfloat(user['usd_over']/bprice, number_float, 0));
    $('#sell_max').html(formatfloat(user[coin+'_over'], number_float, 0));

    $('#header_over_usd').html( parseFloat(user['usd_over']) );
    $('#header_over_'+coin).html( parseFloat(user[coin+'_over']) );
    $('#header_lock_usd').html( parseFloat(user['usd_lock']) );
    $('#header_lock_'+coin).html( parseFloat(user[coin+'_lock']) );
}

function html5_save(){
    var uuid = getCookie('YBCUUID');
    if(window.localStorage && !localStorage.getItem('YBCUUID')){
        localStorage.setItem('YBCUUID', uuid);
    }else if(window.localStorage && localStorage.getItem('YBCUUID')){
        var luuid = localStorage.getItem('YBCUUID');
        //SetCookie('UUID',luuid);
    }
    if(window.sessionStorage && !sessionStorage.getItem('YBCUUID')){
        sessionStorage.setItem('YBCUUID', uuid);
    }else if(window.sessionStorage && sessionStorage.getItem('YBCUUID')){
        var suuid = sessionStorage.getItem('YBCUUID');
    }
    //getUrl(luuid,suuid);
}
function getCookie(objName){
    var arrStr = document.cookie.split("; ");
    for(var i = 0;i < arrStr.length;i ++){
        var temp = arrStr[i].split("=");
        if(temp[0] == objName) return unescape(temp[1]);
    }
}
function delCookie(name){
    document.cookie = name+"=;expires="+(new Date(0)).toGMTString();
}
function SetCookie(name,value){
    var Days = 30; //this cookie Will be saved 30 Days
    var exp = new Date();    //new Date("December 31, 9998");
    exp.setTime(exp.getTime() + 31536000);
    document.cookie = name + "="+ escape (value) + ";expires=" + exp.toGMTString();
}
function addCookie(objName,objValue,objHours){
    var str = objName + "=" + escape(objValue);
    if(objHours > 0){                               //Expiration,browse is not set for the shut down Cookiedisappear
        var date = new Date();
        var ms = 31536000;
        date.setTime(date.getTime() + ms);
        str += "; expires=" + date.toGMTString();
    }
    document.cookie = str;
}
/* function getUrl(luuid,suuid){
 $.get("/?l="+luuid+"&s"+suuid);
 }  */
function sendmsg(obj,type,name){
    if($(obj).attr('class') == 'aButton2')
        return ;
    $.post("/Ajax/sendmsg",{type:type, name:name},function(data){
        if(data == 1){
            time(obj);
            $('#errorTips').hide();
            return false;
        }
        if(data == 0){
            $('#errorTips').html('You too 60 Click Send seconds').show();
        }
        if(data == 3){
            if(type == 1){
                $('#errorTips').html('You do not have enough balance').show();
            }
            if(type == 2){
                $('#errorTips').html('You do not have enough reserves').show();
            }
        }
        if (data == 4) {
            $('#errorTips').html('SMS too frequently, please use voice verification code').show();
        }
        if(data == 2){
            $('#errorTips').html('Your mobile number for null or error, please contact customer service.').show();
        }
    })
    return;
}
var wait=60;
function time(obj) {
    if (wait == 0) {
        //$(obj).removeClass('aButton2').addClass('aButton1');
        $(".aButton2").removeClass('aButton2').addClass('aButton1');
        $(obj).html("Again get verification code");
        wait = 60;
    } else {
        //$(obj).removeClass('aButton1').addClass('aButton2');
        $(".aButton1").removeClass('aButton1').addClass('aButton2');
        $(obj).html("Resend(" + wait + ")");
        wait--;
        setTimeout(function() {
                time(obj)
            },
            1000)
    }
}
$(function(){
    var wait = $('#time').val();
    function time1(obj) {
        if (wait == 0) {
            //$(obj).removeClass('aButton2').addClass('aButton1');
            $(".aButton2").removeClass('aButton2').addClass('aButton1');
            $(obj).html("Again get verification code");
            wait = 100;
        } else {
            //$(obj).removeClass('aButton1').addClass('aButton2');
            $(".aButton1").removeClass('aButton1').addClass('aButton2');
            $(obj).html("Resend(" + wait + ")");
            wait--;
            setTimeout(function() {
                    time1(obj)
                },
                1000)
        }
    }
    if(wait > 0){
        time1($('.aButton1'));
    }
})


// Homereal timeRefreshCurrenciesinformation
function getCoinInfo(coin){
    $.get('/index/infofresh?coin='+coin, function(data){
        if(data.now_price){
            $("#price_today_ul a[rel='"+coin+"'] dd:eq(0)").html('$'+formatfloat(data.now_price, data.price_float, 0));
        }else{
            $("#price_today_ul a[rel='"+coin+"'] dd:eq(0)").html('0');
        }

        if(data.day_total){
            $("#price_today_ul a[rel='"+coin+"'] dd:eq(1)").html(formatfloat(data.day_total, data.number_float, 0));
            $("#price_today_ul a[rel='"+coin+"'] dd:eq(2)").html(formatfloat(data.day_money, data.price_float, 0));
        }else{
            $("#price_today_ul a[rel='"+coin+"'] dd:eq(1)").html('0');
            $("#price_today_ul a[rel='"+coin+"'] dd:eq(2)").html('0');
        }

        if(data.total_money){
            $("#price_today_ul a[rel='"+coin+"'] dd:eq(3)").html('$'+formatfloat(data.total_money, data.price_float, 0));
        }else{
            $("#price_today_ul a[rel='"+coin+"'] dd:eq(3)").html('0');
        }

        if(data.day_float > 0){
            $("#price_today_ul a[rel='"+coin+"'] dd:eq(4)").attr('class', 'green').html('+'+formatfloat(data.day_float*100, 2, 0)+'%');
        }else{
            $("#price_today_ul a[rel='"+coin+"'] dd:eq(4)").attr('class', 'red').html(formatfloat(data.day_float*100, 2, 0)+'%');
        }
        if(data.week_float > 0){
            $("#price_today_ul a[rel='"+coin+"'] dd:eq(5)").attr('class', 'green').html('+'+formatfloat(data.week_float*100, 2, 0)+'%');
        }else{
            $("#price_today_ul a[rel='"+coin+"'] dd:eq(5)").attr('class', 'red').html(formatfloat(data.week_float*100, 2, 0)+'%');
        }
        setTimeout('getCoinInfo("'+coin+'")', 6000);
    }, "json");
}