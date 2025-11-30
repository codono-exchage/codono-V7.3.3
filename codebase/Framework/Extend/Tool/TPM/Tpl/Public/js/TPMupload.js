//compatiblephonegap，computer，Cellular phoneofUploadPlug
//autor luofei614(http://weibo.com/luofei614)
;(function($){
    $.fn.extend({
        TPMupload:function(options){
            //Configuration Item Processing
            var defaults={
                "url":"",
                "name":"file",
                "sourceType":"Image", //againstCellular phoneeffective， Upload classtype，Image,Video,Audio,Libray Note that the first letter capitalized.  Libray ShowUploadPhone Albummiddleimage。 
                "dataUrl":true,
                "quality":20,//Picture quality
                "imgWidth":300,
                "imgHeight":300
            };
            if('string'==$.type(options))
        options={"url":options};
    var op=$.extend(defaults,options);
    //Computer to upload
    var desktop_upload=function(index){
        op.name=$(this).attr('name') || op.name
        //increaseUploadPush button
        var $uploadBtn=$('<input type="button" class="TPMupload_btn" value="Upload" />').insertBefore(this);
    //Add tostatusFloor
    var $status=$('<span class="TPMupload_status"></span>').insertBefore(this);
    //increasehidearea
    var $hiddenInput=$('<input type="hidden" name="'+op.name+'" value="" />').insertBefore(this);;
    //increaseresultdisplayFloor
    var $show=$('<div class="TPMupload_show"></div>').insertBefore(this);
    //Increased submit the form
    var $form=$('<form action="'+op.url+'" target="TPMupload_iframe_'+index+'"  method="post" enctype="multipart/form-data"> <input type="file" size="1" name="'+op.name+'" style="cursor:pointer;" />  </form>').css({"position":"absolute","opacity":"0"}).insertBefore(this);
    //Positioning the form is submitted
    $uploadBtn.hover(function(e){
        $form.offset({top:e.pageY-20,left:e.pageX-50});
    });
    var $uploadInput=$form.find('input:file');
    $uploadInput.change(function(){
        $status.html('uploading...');
        $form.submit();
    });
    $(this).remove();
    //increaseiframe
    var $iframe=$('<iframe id="TPMupload_iframe_'+index+'" name="TPMupload_iframe_'+index+'" style="display:none" src="about:blank"></iframe>').appendTo('body');
    //obtainiframeBack to Results
    var iframe=$iframe[0];
    $iframe.bind("load", function(){
        if (iframe.src == "javascript:'%3Chtml%3E%3C/html%3E';" || // For Safari
            iframe.src == "javascript:'<html></html>';") { // For FF, IE
                return;
            }

        var doc = iframe.contentDocument ? iframe.contentDocument : window.frames[iframe.id].document;

        // fixing Opera 9.26,10.00
        if (doc.readyState && doc.readyState != 'complete') return;
        // fixing Opera 9.64
        if (doc.body && doc.body.innerHTML == "false") return;

        var response;

        if (doc.XMLDocument) {
            // response is a xml document Internet Explorer property
            response = doc.XMLDocument;
        } else if (doc.body){
            try{
                response = $iframe.contents().find("body").html();
            } catch (e){ // response is html document or plain text
                response = doc.body.innerHTML;
            }
        } else {
            // response is a xml document
            response = doc;
        }
        if(''!=response){
            $status.html('');
           if(-1!=response.indexOf('<pre>')){
               //iframemiddlejsonformat，BrowseWillautomaticRender，加上preLabel, escapehtmlTag, so here removedprelabel,reductionhtmllabel.
                   var htmldecode=function(str)   
                   {   
                         var    s    =    "";   
                         if    (str.length    ==    0)    return    "";   
                         s    =    str.replace(/&amp;/g,    "&");   
                         s    =    s.replace(/&lt;/g,"<");   
                         s    =    s.replace(/&gt;/g,">");   
                         s    =    s.replace(/&nbsp;/g,"    ");   
                         s    =    s.replace(/'/g,"\'");   
                         s    =    s.replace(/&quot;/g, "\"");   
                         s    =    s.replace(/<br>/g,"\n");   
                         return    s;   
                   } 
                response=htmldecode($(response).html());
                console.log(response);
            }
            try{
                var ret=$.parseJSON(response);
                //displayimage
                if(ret.path) $hiddenInput.val(ret.path);
                if(ret.show) $show.html(ret.show);
                if(ret.error) $show.html(ret.error);
            }catch(e){
                console.log(response);
                alert('The server returned a malformed'); 
            } 
        }
    });

    };
    //Client upload
    var client_upload=function(index){
        op.name=$(this).attr('name') || op.name
        //increaseUploadPush button
        var $uploadBtn=$('<input type="button" class="TPMupload_btn" value="Upload" />').insertBefore(this);
        //Add tostatusFloor
        var $status=$('<span class="TPMupload_status"></span>').insertBefore(this);
        //increasehidearea
        var $hiddenInput=$('<input type="hidden" name="'+op.name+'" value="" />').insertBefore(this);;
        //increaseresultdisplayFloor
        var $show=$('<div class="TPMupload_show"></div>').insertBefore(this);
        $(this).remove();
        var upload=function(file,isbase64){
            isbase64=isbase64 || false;
            if('http'!=op.url.substr(0,4).toLowerCase()){
                        //in caseUploadaddressNot absoluteaddress， 加上TPMThe base path. 
                        op.url=TPM.op.api_base+op.url;
            }
            if(isbase64){
                //ifbase64The picture data
                var $imgshow=$('<div><img src="data:image/png;base64,'+file+'" /><br /><span>Click onimagecanAdjustmentimageangle</span></div>').appendTo($show);
                var $img=$imgshow.find('img');
                $imgshow.click(function(){
                    var c=document.createElement('canvas');
                    var ctx=c.getContext("2d");
                    var img=new Image();
                    img.onload = function(){
                         c.width=this.height;
                         c.height=this.width; 
                         ctx.rotate(90 * Math.PI / 180);
                         ctx.drawImage(img, 0,-this.height);
                        var dataURL = c.toDataURL("image/png");
                        $img.attr('src',dataURL);
                        $hiddenInput.val(dataURL);
                    };
                    img.src=$img.attr('src');
                });
                $hiddenInput.val('data:image/png;base64,'+file); 
            }else{
                $status.html('uploading...');
                //video，Voice, etc.fileUpload
                resolveLocalFileSystemURI(file,function(fileEntry){
                    fileEntry.file(function(info){
                         var options = new FileUploadOptions(); 
                            options.fileKey=op.name;
                            options.chunkedMode=false;
                            var ft = new FileTransfer();
                      
                            ft.upload(info.fullPath,op.url,function(r){
                                $status.html('');
                                try{
                                    var ret=$.parseJSON(r.response);
                                    //displayimage
                                    if(ret.path) $hiddenInput.val(ret.path);
                                    if(ret.show) $show.html(ret.show);
                                    if(ret.error) $show.html(ret.error);
                                }catch(e){
                                    console.log(r.response);
                                    alert('The server returned a malformed'); 
                                } 
                            },function(error){
                                $status.html('');
                                alert("File upload failed，errorcode： " + error.code);
                            },options);
                    });
                });
            }
   
        };
        //Capture objects
        $uploadBtn.click(function(){

            if('Libray'==op.sourceType || 'Image'==op.sourceType){
                var sourceType='Image'==op.sourceType?navigator.camera.PictureSourceType.CAMERA:navigator.camera.PictureSourceType.PHOTOLIBRARY;
                var destinationType=op.dataUrl?navigator.camera.DestinationType.DATA_URL:navigator.camera.DestinationType.FILE_URI;
                navigator.camera.getPicture(function(imageURI){
                    upload(imageURI,op.dataUrl);
                }, function(){
                }, {quality:op.quality,destinationType: destinationType,sourceType:sourceType,targetWidth:op.imgWidth,targetHeight:op.imgHeight});
            }else{
                var action='capture'+op.sourceType;
                navigator.device.capture[action](function(mediaFiles){
                    upload(mediaFiles[0].fullPath);
                },function(){
                }); 
            }
        });

    };

    $(this).each(function(index){
        //inSAEWindows Cloud DebuggerThe next may be delayedproblem，the first timeloadmeetingjudgmentwindow.cordovanotdefinition，At this momentneedClick ona bitpageotherlink，againClick onBack on it 
        if('cordova' in window){
            //Approach on the phone
            client_upload.call(this,index);
        }else{
            //The method of processing on the computer
            desktop_upload.call(this,index);
        }
    });


        }
    });
})(jQuery);
