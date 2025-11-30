// The system comes with plug-ins
( function ( $ ) {

//full screen
$.TE.plugin( "fullscreen", {
	fullscreen:function(e){
		var $btn = this.$btn,
			opt  = this.editor.opt;

		if($btn.is("."+opt.cssname.fulled)){
			//Cancel the full screen
			this.editor.$main.removeAttr("style");
			this.editor.$bottom.find("div").show();
			this.editor.resize(opt.width,opt.height);
			$("html,body").css("overflow","auto");
			$btn.removeClass(opt.cssname.fulled);
			$(window).scrollTop(this.scrolltop);
		}else{
			//full screen
			this.scrolltop=$(window).scrollTop();
			this.editor.$main.attr("style","z-index:900000;position:absolute;left:0;top:0px");
			$(window).scrollTop(0);
			$("html,body").css("overflow","hidden");//Hide get out Article
			this.editor.$bottom.find("div").hide();//hidebottomofAdjustmentSize Control Block
			this.editor.resize($(window).width(),$(window).height());
			$btn.addClass(opt.cssname.fulled);
		}
	}
} );
//Source switch
$.TE.plugin( "source", {
	source:function(e){
		var $btn   = this.$btn,
			$area  = this.editor.$area,
			$frame = this.editor.$frame,
			opt    = this.editor.opt,
			_self  = this;

		if($btn.is("."+opt.cssname.sourceMode)){
			//Switch to visualize
			_self.editor.core.updateFrame();
			$area.hide();
			$frame.show();
			$btn.removeClass(opt.cssname.sourceMode);
		}else{
			//Switch to the Source
			_self.editor.core.updateTextArea();
			$area.show();
			$frame.hide();
			$btn.addClass(opt.cssname.sourceMode);
		}

		setTimeout(function(){_self.editor.refreshBtn()},100);
	}
} );
//Shear
$.TE.plugin( 'cut', {
	click: function() {
		if( $.browser.mozilla ) {
			alert('youofBrowseSafetySet upnot supportThatoperating，pleaseuseCtrl/Cmd+XShortcuts to complete the operation.');
		} else {
			this.exec();
		}
	}
});
//copy
$.TE.plugin( 'copy', {
	click: function() {
		if( $.browser.mozilla ) {
			alert('youofBrowseSafetySet upnot supportThatoperating，pleaseuseCtrl/Cmd+CShortcuts to complete the operation.');
		} else {
			this.exec();
		}
	}
});
//Stick
$.TE.plugin( 'paste', {
	click: function() {
		if( $.browser.mozilla ) {
			alert('youofBrowseSafetySet upnot supportThatoperating，pleaseuseCtrl/Cmd+VShortcuts to complete the operation.');
		} else {
			this.exec();
		}
	}
});
//Create link
$.TE.plugin( "link", {
	click:function(e){
		var _self = this;
		var $html = $(
			'<div class="te_dialog_link Lovh">'+
			'	<div class="seltab">'+
			'		<div class="links Lovh">'+
			'			<a href="###" class="cstyle">Create link</a>'+
			'		</div>'+
			'		<div class="bdb">&nbsp;</div>'+
			'	</div>'+
			'	<div class="centbox">'+
			'		<div class="item Lovh">'+
			'			<span class="ltext Lfll">link address:</span>'+
			'			<div class="Lfll">'+
			'				<input id="te_dialog_url" name="" type="text" class="Lfll input1" />'+
			'			</div>'+
			'		</div>'+
			'	</div>'+
			'	<div class="btnarea">'+
			'		<input type="button" value="Ok" class="te_ok" />'+
			'		<input type="button" value="cancel" class="te_close" />'+
			'	</div>'+
			'</div>'
		);

		if( _self.isie6() ) {
			window.selectionCache = [
				/* Staging constituency objects */
				_self.editor.doc.selection.createRange(),
				/* Electoral districthtmlcontent */
				_self.editor.doc.selection.createRange().htmlText,
				/* Electoral districtText toComputeDifference */
				_self.editor.doc.selection.createRange().text
			];
		}

		this.createDialog({
			body:$html,
			ok:function(){
				_self.value=$html.find("#te_dialog_url").val();
				if( _self.isie6() ) {
					var _sCache = window.selectionCache,
						str1    = '<a href="'+_self.value+'">'+_sCache[1]+'</a>',
						str2    = '<a href="'+_self.value+'">'+_sCache[2]+'</a>';

					_sCache[0].pasteHTML( str1 );
					_sCache[0].moveStart( 'character', -_self.strlen( str2 ) + ( str2.length - _sCache[2].length ) );
					_sCache[0].moveEnd( 'character', -0 );
					_sCache[0].select();
					//Blank staging objects
					window.selectionCache = _sCache = null;

				} else {
					_self.exec();
				}
				_self.hideDialog();
			}
		});
	},
	strlen : function ( str ) {
		return window.ActiveXObject && str.indexOf("\n") != -1 ? str.replace(/\r?\n/g, "_").length : str.length;
	},
	isie6 : function () {
		return $.browser.msie && $.browser.version == '6.0' ? true : false;
	}
} );

$.TE.plugin( 'print', {
	click: function(e) {
		var _win = this.editor.core.$frame[0].contentWindow;
		if($.browser.msie) {
			this.exec();
		} else if(_win.print) {
			_win.print();
		} else {
			alert('Your system does not support printing interface');
		}
	}
} );

$.TE.plugin( 'pagebreak', {
	exec: function() {
		var _self = this;
		_self.editor.pasteHTML('<div style="page-break-after: always;zoom:1; height:0px; clear:both; display:block; overflow:hidden; border-top:2px dotted #CCC;">&nbsp;</div><p>&nbsp;</p>');
	}
} );

$.TE.plugin( 'pastetext', {
	exec: function() {
		var _self    = this,
			_html    = '';
			clipData = window.clipboardData ? window.clipboardData.getData('text') : false;

		if( clipData ) {
			_self.editor.pasteHTML( clipData.replace( /\r\n/g, '<br />' ) );
		} else {
			_html = $(
				'<div class="te_dialog_pasteText">'+
				'	<div class="seltab">'+
				'		<div class="links Lovh">'+
				'			<a href="###" class="cstyle">Sticky paste text</a>'+
				'		</div>'+
				'		<div class="bdb">&nbsp;</div>'+
				'	</div>'+
				'	<div class="centbox">'+
				'		<div class="pasteText">'+
				'			<span class="tips Lmt5">Please use the keyboard shortcut(Ctrl/Cmd+V)ThecontentStickTothe followingofThe box。</span>'+
				'			<textarea id="pasteText" name="" class="tarea1 Lmt5" rows="10" cols="30"></textarea>'+
				'		</div>'+
				'	</div>'+
				'	<div class="btnarea">'+
				'		<input type="button" value="Ok" class="te_ok" />'+
				'		<input type="button" value="cancel" class="te_close" />'+
				'	</div>'+
				'</div>'
			);
			this.createDialog({
				body : _html,
				ok   : function(){
					_self.editor.pasteHTML(_html.find('#pasteText').val().replace(/\n/g, '<br />'));
					_self.hideDialog();
				}
			});
		}
	}
} );

$.TE.plugin( 'table', {
	exec : function (e) {
		var _self = this,
			_html = '';

		_html = $(
			'<div class="te_dialog_table">'+
			'	<div class="seltab">'+
			'		<div class="links Lovh">'+
			'			<a href="###" class="cstyle">Insert Table</a>'+
			'		</div>'+
			'		<div class="bdb">&nbsp;</div>'+
			'	</div>'+
			'	<div class="centbox">'+
			'		<div class="insertTable">'+
			'			<div class="item Lovh">'+
			'				<span class="ltext Lfll">Rows:</span>'+
			'				<input type="text" id="te_tab_rows" class="input1 Lfll" value="3" />'+
			'				<span class="ltext Lfll">Number of columns:</span>'+
			'				<input type="text" id="te_tab_cols" class="input1 Lfll" value="2" />'+
			'			</div>'+
			'			<div class="item Lovh">'+
			'				<span class="ltext Lfll">width:</span>'+
			'				<input type="text" id="te_tab_width" class="input1 Lfll" value="500" />'+
			'				<span class="ltext Lfll">height:</span>'+
			'				<input type="text" id="te_tab_height" class="input1 Lfll" />'+
			'			</div>'+
			'			<div class="item Lovh">'+
			'				<span class="ltext Lfll">frame:</span>'+
			'				<input type="text" id="te_tab_border" class="input1 Lfll" value="1" />'+
			'			</div>'+
			'		</div>'+
			'	</div>'+
			'	<div class="btnarea">'+
			'		<input type="button" value="Ok" class="te_ok" />'+
			'		<input type="button" value="cancel" class="te_close" />'+
			'	</div>'+
			'</div>'
		);

		this.createDialog({
			body : _html,
			ok   : function () {
				//Acquisition parameters
				var rows     = parseInt(_html.find('#te_tab_rows').val()),
					cols     = parseInt(_html.find('#te_tab_cols').val()),
					width    = parseInt(_html.find('#te_tab_width').val()),
					height   = parseInt(_html.find('#te_tab_height').val()),
					border   = parseInt(_html.find('#te_tab_border').val()),
					tab_html = '<table width="'+width+'" border="'+border+'"';

					if(height) tab_html += ' height="'+height+'"';
					tab_html += '>';

				for(var i=0; i<rows; i++) {
					tab_html += '<tr>';
					for(var j=0; j<cols; j++) {
						tab_html += '<td>&nbsp;</td>';
					}
					tab_html += '</tr>';
				}

				tab_html += '</table>';
				_self.editor.pasteHTML( tab_html );
				_self.hideDialog();

			}
		});

	}
} );

$.TE.plugin( 'blockquote', {
	exec : function () {
		var _self   = this,
			_doc    = _self.editor.doc,
			elem    = '', //The current object
			isquote = false, //Whether it has been cited
			node    = '',
			child   = false; //Find the referenced object
		//Get the current object
		node = elem = this.getElement();
		//It has been cited to determine whether
		while( node !== _doc.body ) {
			if( node.nodeName.toLowerCase() == 'blockquote' ){
				isquote = true;
				break;
			}
			node = node.parentNode;
		}

		if( isquote ) {
			//in caseexistQuote，thenClear up
			if( node  === _doc.body ) {
				node.innerHTML = elem.parentNode.innerHTML;
			} else {
				while (child = node.firstChild) {
					node.parentNode.insertBefore(child, node);
				}
				node.parentNode.removeChild(node);
			}
		} else {
			//does not existQuote，createQuote
			if( $.browser.msie ) {
				if( elem === _doc.body ) {
					elem.innerHTML = '<blockquote>' + elem.innerHTML + '</blockquote>';
				} else {
					elem.outerHTML = '<blockquote>' + elem.outerHTML + '</blockquote>';
				}

			} else {
				_doc.execCommand( 'formatblock', false, '<blockquote>' )

			}

		}

	},
	getElement : function () {
		var ret = false;

		if( $.browser.msie ) {
			ret = this.editor.doc.selection.createRange().parentElement();
		} else {
			ret = this.editor.$frame.get( 0 ).contentWindow.getSelection().getRangeAt( 0 ).startContainer;
		}

		return ret;

	}
} );

$.TE.plugin( 'image', {

	upid    : 'te_image_upload',
	uptype  : [ 'jpg', 'jpeg', 'gif', 'png', 'bmp' ],
	//File size
	maxsize : 1024*1024*1024*2, // 2MB
	exec    : function() {
			var _self = this,
				//Uploadaddress
				updir = _self.editor.opt.uploadURL,
				//To passUpload页ofparameter
				parame = 'callback='+this.upid+this.editor.guid+'&rands='+(+new Date());
			if(updir && updir!='about:blank'){
				if( updir.indexOf('?') > -1 ) {
					updir += '&' + parame;
				} else {
					updir += '?' + parame;
				}
				//pop upwindowcontent
				var $html = $(
						'<div class="te_dialog_image">'+
						'	<div class="seltab">'+
						'		<div class="links Lovh">'+
						'			<a href="#" class="cstyle">Insert Picture</a>'+
						'		</div>'+
						'		<div class="bdb">&nbsp;</div>'+
						'	</div>'+
						'	<div class="centbox">'+
						'		<div class="insertimage Lmt20 Lpb10">'+
						'			<div class="item Lovh">'+
						'				<span class="ltext Lfll">The maps address:</span>'+
						'				<div class="Lfll Lovh">'+
						'					<input type="text" id="te_image_url" class="imageurl input1 Lfll Lmr5" />'+
						'				</div>'+
						'			</div>'+
						'			<div class="item Lovh">'+
						'				<span class="ltext Lfll">upload image:</span>'+
						'				<div class="Lfll Lovh">'+
						'               <form id="form_img" enctype="multipart/form-data" action="'+updir+'" method="POST" target="upifrem">'+
						'                   <div class="filebox">'+
						'                   <input id="teupload" name="teupload" size="1" onchange="checkTypes(\'form_img\');" class="upinput" type="file" hidefocus="true" />'+
						'                   <input id="tefiletype" name="tefiletype" type="hidden" value="'+this.uptype+'" />'+
						'                   <input id="temaxsize" name="temaxsize" type="hidden" value="'+this.maxsize+'" />'+
						'                   <span class="upbtn">Upload</span>'+
						'                   </div>'+
						'               </form>'+
						'				<iframe name="upifrem" id="upifrem" width="70" height="22" class="Lfll" scrolling="no" frameborder="0"></iframe>'+
						'				</div>'+
						'			</div>'+
						'			<div class="item Lovh">'+
						'				<span class="ltext Lfll">Image Width:</span>'+
						'				<div class="Lfll">'+
						'					<input id="te_image_width" name="" type="text" class="input2" />'+
						'				</div>'+
						'				<span class="ltext Lfll">Image Height:</span>'+
						'				<div class="Lfll">'+
						'					<input id="te_image_height" name="" type="text" class="input2" />'+
						'				</div>'+
						'			</div>'+
						'		</div>'+
						'	</div>'+
						'	<div class="btnarea">'+
						'		<input type="button" value="Ok" class="te_ok" />'+
						'		<input type="button" value="cancel" class="te_close" />'+
						'	</div>'+
						'</div>'
					),
					_upcall = function(path) {
						//ObtainUploadThe value
						$html.find( '#te_image_url' ).val(path);
						// RefreshiframeUpload页
						//var _url = $html.find( 'iframe' ).attr( 'src' );
						//_url = _url.replace( /rands=[^&]+/, 'rands=' + (+ new Date()) );
						$html.find( 'iframe' ).attr( 'src', 'about:blank' );
					}
				//registeredcommunication
				te_upload_interface( 'reg', {
					'callid'  : this.upid+this.editor.guid,
					'filetype': this.uptype,
					'maxsize' : this.maxsize,
					'callback': _upcall
				} );
				//createDialog
				this.createDialog( {
					body : $html,
					ok   : function() {
						var _src    = $html.find('#te_image_url').val(),
							_width  = parseInt($html.find('#te_image_width').val()),
							_height = parseInt($html.find('#te_image_height').val());
						_src = _APP+_src;
						var _insertHTML = '<img src="'+(_src||'http://www.baidu.com/img/baidu_sylogo1.gif')+'" ';
						if( _width ) _insertHTML += 'width="'+_width+'" ';
						if( _height ) _insertHTML += 'height="'+_height+'" ';

						_insertHTML += '/>';
						_self.editor.pasteHTML( _insertHTML );
						_self.hideDialog();
					}

				} );
		}else{
			alert('pleaseConfigurationUploaddeal withpath!');
		}
	}
} );

$.TE.plugin( 'flash', {
	upid    : 'te_flash_upload',
	uptype  : [ 'swf' ],
	//File size
	maxsize : 1024*1024*1024*2, // 2MB
	exec    : function() {
		var _self = this,
			//Uploadaddress
			updir = _self.editor.opt.uploadURL,
			//To passUpload页ofparameter
			parame = 'callback='+this.upid+this.editor.guid+'&rands='+(+new Date());
		if(updir && updir!='about:blank'){
			if( updir.indexOf('?') > -1 ) {
				updir += '&' + parame;
			} else {
				updir += '?' + parame;
			}
			//pop upwindowcontent
			var $html = $(
					'<div class="te_dialog_flash">'+
					'	<div class="seltab">'+
					'		<div class="links Lovh">'+
					'			<a href="###" class="cstyle">insertflashAnimation</a>'+
					'		</div>'+
					'		<div class="bdb">&nbsp;</div>'+
					'	</div>'+
					'		<div class="insertflash Lmt20 Lpb10">'+
					'			<div class="item Lovh">'+
					'				<span class="ltext Lfll">flashaddress:</span>'+
					'				<div class="Lfll Lovh">'+
					'					<input id="te_flash_url" type="text" class="imageurl input1 Lfll Lmr5" />'+
					'				</div>'+
					'			</div>'+
					'			<div class="item Lovh">'+
					'				<span class="ltext Lfll">Uploadflash：</span>'+
					'				<div class="Lfll Lovh">'+
					'               <form id="form_swf" enctype="multipart/form-data" action="'+updir+'" method="POST" target="upifrem">'+
					'                   <div class="filebox">'+
					'                   <input id="teupload" name="teupload" size="1" onchange="checkTypes(\'form_swf\');" class="upinput" type="file" hidefocus="true" />'+
					'                   <input id="tefiletype" name="tefiletype" type="hidden" value="'+this.uptype+'" />'+
					'                   <input id="temaxsize" name="temaxsize" type="hidden" value="'+this.maxsize+'" />'+
					'                   <span class="upbtn">Upload</span>'+
					'                   </div>'+
					'               </form>'+
					'				<iframe name="upifrem" id="upifrem" width="70" height="22" class="Lfll" scrolling="no" frameborder="0"></iframe>'+
					'				</div>'+
					'			</div>'+
					'			<div class="item Lovh">'+
					'				<span class="ltext Lfll">width:</span>'+
					'				<div class="Lfll">'+
					'					<input id="te_flash_width" name="" type="text" class="input2" value="200" />'+
					'				</div>'+
					'				<span class="ltext Lfll">height:</span>'+
					'				<div class="Lfll">'+
					'					<input id="te_flash_height" name="" type="text" class="input2" value="60" />'+
					'				</div>'+
					'			</div>'+
					'			<div class="item Lovh">'+
					'				<span class="ltext Lfll">&nbsp;</span>'+
					'				<div class="Lfll">'+
					'					<input id="te_flash_wmode" name="" type="checkbox" class="input3" />'+
					'					<label for="te_flash_wmode" class="labeltext">Open transparent background</label>'+
					'				</div>'+
					'			</div>'+
					'		</div>'+
					'	<div class="btnarea">'+
					'		<input type="button" value="Ok" class="te_ok" />'+
					'		<input type="button" value="cancel" class="te_close" />'+
					'	</div>'+
					'</div>'
				),
				_upcall = function(path) {
					//ObtainUploadThe value
					$html.find( '#te_flash_url' ).val(path);
					// RefreshiframeUpload页
					//var _url = $html.find( 'iframe' ).attr( 'src' );
					//_url = _url.replace( /rands=[^&]+/, 'rands=' + (+ new Date()) );
					$html.find( 'iframe' ).attr( 'src', 'about:blank' );
				}

			//registeredcommunication
			te_upload_interface( 'reg', {
				'callid'  : this.upid+this.editor.guid,
				'filetype': this.uptype,
				'maxsize' : this.maxsize,
				'callback': _upcall
			} );
			//createDialog
			this.createDialog( {
				body : $html,
				ok   : function() {
					var _src    = $html.find('#te_flash_url').val(),
						_width  = parseInt($html.find('#te_flash_width').val()),
						_height = parseInt($html.find('#te_flash_height').val());
						_wmode  = !!$html.find('#te_flash_wmode').attr('checked');

					if( _src == '' ) {
						alert('please enterflashaddress，orSelect from a localfileUpload');
						return true;
					}
					if( isNaN(_width) || isNaN(_height) ) {
						alert('Please enter the width and height');
						return true;
					}
					_src = _APP+_src;
					var _data = "{'src':'"+_src+"','width':'"+_width+"','height':'"+_height+"','wmode':"+(_wmode)+"}";
					var _insertHTML = '<img src="'+$.TE.basePath()+'skins/'+_self.editor.opt.skins+'/img/spacer.gif" class="_flash_position" style="';

					if( _width ) _insertHTML += 'width:'+_width+'px;';
					if( _height ) _insertHTML += 'height:'+_height+'px;';

					_insertHTML += 'border:1px solid #DDD; display:inline-block;text-align:center;line-height:'+_height+'px;" ';
					_insertHTML += '_data="'+_data+'"';
					_insertHTML += ' alt="flashPlaceholder" />';

					_self.editor.pasteHTML( _insertHTML );

					_self.hideDialog();
				}
			} )
		}else{
			alert('pleaseConfigurationUploaddeal withpath!');
		}
	}

} );

$.TE.plugin( 'face', {
	exec: function() {
		var _self = this,
		//Path expression
			_fp   = _self.editor.opt.face_path,
		//Pop-up window with the contents
			$html = $(
				'<div class="te_dialog_face Lovh">'+
				'	<div class="seltab">'+
				'		<div class="links Lovh">'+
				'			<a href="###" class="cstyle">Insert smiley</a>'+
				'		</div>'+
				'		<div class="bdb">&nbsp;</div>'+
				'	</div>'+
				'	<div class="centbox">'+
				'		<div class="insertFace" style="background-image:url('+$.TE.basePath()+'skins/'+_fp[1]+'/'+_fp[0]+'.gif'+');">'+
							'<span face_num="0">&nbsp;</span>'+
							'<span face_num="1">&nbsp;</span>'+
							'<span face_num="2">&nbsp;</span>'+
							'<span face_num="3">&nbsp;</span>'+
							'<span face_num="4">&nbsp;</span>'+
							'<span face_num="5">&nbsp;</span>'+
							'<span face_num="6">&nbsp;</span>'+
							'<span face_num="7">&nbsp;</span>'+
							'<span face_num="8">&nbsp;</span>'+
							'<span face_num="9">&nbsp;</span>'+
							'<span face_num="10">&nbsp;</span>'+
							'<span face_num="11">&nbsp;</span>'+
							'<span face_num="12">&nbsp;</span>'+
							'<span face_num="13">&nbsp;</span>'+
							'<span face_num="14">&nbsp;</span>'+
							'<span face_num="15">&nbsp;</span>'+
							'<span face_num="16">&nbsp;</span>'+
							'<span face_num="17">&nbsp;</span>'+
							'<span face_num="18">&nbsp;</span>'+
							'<span face_num="19">&nbsp;</span>'+
							'<span face_num="20">&nbsp;</span>'+
							'<span face_num="21">&nbsp;</span>'+
							'<span face_num="22">&nbsp;</span>'+
							'<span face_num="23">&nbsp;</span>'+
							'<span face_num="24">&nbsp;</span>'+
							'<span face_num="25">&nbsp;</span>'+
							'<span face_num="26">&nbsp;</span>'+
							'<span face_num="27">&nbsp;</span>'+
							'<span face_num="28">&nbsp;</span>'+
							'<span face_num="29">&nbsp;</span>'+
							'<span face_num="30">&nbsp;</span>'+
							'<span face_num="31">&nbsp;</span>'+
							'<span face_num="32">&nbsp;</span>'+
							'<span face_num="33">&nbsp;</span>'+
							'<span face_num="34">&nbsp;</span>'+
							'<span face_num="35">&nbsp;</span>'+
							'<span face_num="36">&nbsp;</span>'+
							'<span face_num="37">&nbsp;</span>'+
							'<span face_num="38">&nbsp;</span>'+
							'<span face_num="39">&nbsp;</span>'+
							'<span face_num="40">&nbsp;</span>'+
							'<span face_num="41">&nbsp;</span>'+
							'<span face_num="42">&nbsp;</span>'+
							'<span face_num="43">&nbsp;</span>'+
							'<span face_num="44">&nbsp;</span>'+
							'<span face_num="45">&nbsp;</span>'+
							'<span face_num="46">&nbsp;</span>'+
							'<span face_num="47">&nbsp;</span>'+
							'<span face_num="48">&nbsp;</span>'+
							'<span face_num="49">&nbsp;</span>'+
							'<span face_num="50">&nbsp;</span>'+
							'<span face_num="51">&nbsp;</span>'+
							'<span face_num="52">&nbsp;</span>'+
							'<span face_num="53">&nbsp;</span>'+
							'<span face_num="54">&nbsp;</span>'+
							'<span face_num="55">&nbsp;</span>'+
							'<span face_num="56">&nbsp;</span>'+
							'<span face_num="57">&nbsp;</span>'+
							'<span face_num="58">&nbsp;</span>'+
							'<span face_num="59">&nbsp;</span>'+
							'<span face_num="60">&nbsp;</span>'+
							'<span face_num="61">&nbsp;</span>'+
							'<span face_num="62">&nbsp;</span>'+
							'<span face_num="63">&nbsp;</span>'+
							'<span face_num="64">&nbsp;</span>'+
							'<span face_num="65">&nbsp;</span>'+
							'<span face_num="66">&nbsp;</span>'+
							'<span face_num="67">&nbsp;</span>'+
							'<span face_num="68">&nbsp;</span>'+
							'<span face_num="69">&nbsp;</span>'+
							'<span face_num="70">&nbsp;</span>'+
							'<span face_num="71">&nbsp;</span>'+
							'<span face_num="72">&nbsp;</span>'+
							'<span face_num="73">&nbsp;</span>'+
							'<span face_num="74">&nbsp;</span>'+
							'<span face_num="75">&nbsp;</span>'+
							'<span face_num="76">&nbsp;</span>'+
							'<span face_num="77">&nbsp;</span>'+
							'<span face_num="78">&nbsp;</span>'+
							'<span face_num="79">&nbsp;</span>'+
							'<span face_num="80">&nbsp;</span>'+
							'<span face_num="81">&nbsp;</span>'+
							'<span face_num="82">&nbsp;</span>'+
							'<span face_num="83">&nbsp;</span>'+
							'<span face_num="84">&nbsp;</span>'+
							'<span face_num="85">&nbsp;</span>'+
							'<span face_num="86">&nbsp;</span>'+
							'<span face_num="87">&nbsp;</span>'+
							'<span face_num="88">&nbsp;</span>'+
							'<span face_num="89">&nbsp;</span>'+
							'<span face_num="90">&nbsp;</span>'+
							'<span face_num="91">&nbsp;</span>'+
							'<span face_num="92">&nbsp;</span>'+
							'<span face_num="93">&nbsp;</span>'+
							'<span face_num="94">&nbsp;</span>'+
							'<span face_num="95">&nbsp;</span>'+
							'<span face_num="96">&nbsp;</span>'+
							'<span face_num="97">&nbsp;</span>'+
							'<span face_num="98">&nbsp;</span>'+
							'<span face_num="99">&nbsp;</span>'+
							'<span face_num="100">&nbsp;</span>'+
							'<span face_num="101">&nbsp;</span>'+
							'<span face_num="102">&nbsp;</span>'+
							'<span face_num="103">&nbsp;</span>'+
							'<span face_num="104">&nbsp;</span>'+
						'</div>'+
				'	</div>'+
				'	<div class="btnarea">'+
				'		<input type="button" value="cancel" class="te_close" />'+
				'	</div>'+
				'</div>'
			);

		$html.find('.insertFace span').click(function( e ) {
			var _url = $.TE.basePath()+'skins/'+_fp[1]+'/'+_fp[0]+'_'+$( this ).attr( 'face_num' )+'.gif',
				_insertHtml = '<img src="'+_url+'" />';
				
			_self.editor.pasteHTML( _insertHtml );
			_self.hideDialog();

		});

		this.createDialog( {
			body : $html
		} );

	}
} );

$.TE.plugin( 'code', {
	exec: function() {
		var _self = this,
			$html = $(
				'<div class="te_dialog_code Lovh">'+
				'	<div class="seltab">'+
				'		<div class="links Lovh">'+
				'			<a href="###" class="cstyle">Insert code</a>'+
				'		</div>'+
				'		<div class="bdb">&nbsp;</div>'+
				'	</div>'+
				'	<div class="centbox">'+
				'		<div class="Lmt10 Lml10">'+
				'			<span>Choose a language:</span>'+
				'			<select id="langType" name="">'+
				'				<option value="None">Other types</option>'+
				'				<option value="PHP">PHP</option>'+
				'				<option value="HTML">HTML</option>'+
				'				<option value="JS">JS</option>'+
				'				<option value="CSS">CSS</option>'+
				'				<option value="SQL">SQL</option>'+
				'				<option value="C#">C#</option>'+
				'				<option value="JAVA">JAVA</option>'+
				'				<option value="VBS">VBS</option>'+
				'				<option value="VB">VB</option>'+
				'				<option value="XML">XML</option>'+
				'			</select>'+
				'		</div>'+
				'		<textarea id="insertCode" name="" rows="10" class="tarea1 Lmt10" cols="30"></textarea>'+
				'	</div>'+
				'	<div class="btnarea">'+
				'		<input type="button" value="Ok" class="te_ok" />'+
				'		<input type="button" value="cancel" class="te_close" />'+
				'	</div>'+
				'</div>'
			);
		this.createDialog({
			body : $html,
			ok   : function(){
				var _code = $html.find('#insertCode').val(),
					_type = $html.find('#langType').val(),
					_html = '';

				_code = _code.replace( /</g, '&lt;' );
				_code = _code.replace( />/g, '&gt;' );
				_code = _code.split('\n');
				_html += '<pre style="overflow-x:auto; padding:10px; color:blue; border:1px dotted #2BC1FF; background-color:#EEFAFF;" _syntax="'+_type+'">'
				_html += 'Language Type:'+_type;
				_html += '<ol style="list-stype-type:decimal;">';
				for(var i=0; i<_code.length; i++) {
					_html += '<li>'+_code[i].replace( /^(\t+)/g, function( $1 ) {return $1.replace(/\t/g, '    ');} );+'</li>';
				}
				_html += '</ol></pre><p>&nbsp;</p>';
				_self.editor.pasteHTML( _html );
				_self.hideDialog();
			}
		});

	}
} );

$.TE.plugin( 'style', {
	click: function() {
		var _self = this,
			$html = $(
				'<div class="te_dialog_style Lovh">'+
				'	<div class="centbox">'+
				'		<h1 title="Set upcurrentcontentforA title"><a href="###">A title</a></h1>'+
				'		<h2 title="Set upcurrentcontentforSubheadings"><a href="###">Subheadings</a></h2>'+
				'		<h3 title="Set upcurrentcontentforThree title"><a href="###">Three title</a></h3>'+
				'		<h4 title="Set upcurrentcontentforFour title"><a href="###">Four title</a></h4>'+
				'		<h5 title="Set upcurrentcontentforFive title"><a href="###">Five title</a></h5>'+
				'		<h6 title="Set upcurrentcontentforSix title"><a href="###">Six title</a></h6>'+
				'	</div>'+
				'</div>'
			),
			_call = function(e) {
				var _value = this.nodeName;
				_self.value= _value;
				_self.exec();
				//_self.hideDialog();
			};

		$html.find( '>.centbox>*' ).click( _call );

		this.createDialog( {
			body: $html
		} );
	},
	exec: function() {
		var _self = this,
			_html = '<'+_self.value+'>'+_self.editor.selectedHTML()+'</'+_self.value+'>';

		_self.editor.pasteHTML( _html );
	}

} );

$.TE.plugin( 'font', {
	click: function() {
		var _self = this;
			$html = $(
				'<div class="te_dialog_font Lovh">'+
				'	<div class="centbox">'+
				'		<div><a href="###" style="font-family:\'Times New Roman\',\'SimSun\'">Times New Roman</a></div>'+
				'		<div><a href="###" style="font-family:\'Italics\',\'Italics_GB2312\',\'SimKai\'">Italics</a></div>'+
				'		<div><a href="###" style="font-family:\'Official script\',\'SimLi\'">Official script</a></div>'+
				'		<div><a href="###" style="font-family:\'Blackbody\',\'SimHei\'">Blackbody</a></div>'+
				'		<div><a href="###" style="font-family:\'Microsoft elegant black\'">Microsoft elegant black</a></div>'+
				'		<span>------</span>'+
				'		<div><a href="###" style="font-family:arial,helvetica,sans-serif;">Arial</a></div>'+
				'		<div><a href="###" style="font-family:comic sans ms,cursive;">Comic Sans Ms</a></div>'+
				'		<div><a href="###" style="font-family:courier new,courier,monospace;">Courier New</a></div>'+
				'		<div><a href="###" style="font-family:georgia,serif;">Georgia</a></div>'+
				'		<div><a href="###" style="font-family:lucida sans unicode,lucida grande,sans-serif;">Lucida Sans Unicode</a></div>'+
				'		<div><a href="###" style="font-family:tahoma,geneva,sans-serif;">Tahoma</a></div>'+
				'		<div><a href="###" style="font-family:times new roman,times,serif;">Times New Roman</a></div>'+
				'		<div><a href="###" style="font-family:trebuchet ms,helvetica,sans-serif;">Trebuchet Ms</a></div>'+
				'		<div><a href="###" style="font-family:verdana,geneva,sans-serif;">Verdana</a></div>'+
				'	</div>'+
				'</div>'
			),
			_call = function(e) {
				var _value = this.style.fontFamily;
				_self.value= _value;
				_self.exec();
			};

		$html.find( '>.centbox a' ).click( _call );

		this.createDialog( {
			body: $html
		} );
	}
} );

$.TE.plugin( 'fontsize', {
	click: function() {
		var _self = this,
			$html = $(
				'<div class="te_dialog_fontsize Lovh">'+
				'	<div class="centbox">'+
				'		<div><a href="#" style="font-size:10px">10</a></div>'+
				'		<div><a href="#" style="font-size:12px">12</a></div>'+
				'		<div><a href="#" style="font-size:14px">14</a></div>'+
				'		<div><a href="#" style="font-size:16px">16</a></div>'+
				'		<div><a href="#" style="font-size:18px">18</a></div>'+
				'		<div><a href="#" style="font-size:20px">20</a></div>'+
				'		<div><a href="#" style="font-size:22px">22</a></div>'+
				'		<div><a href="#" style="font-size:24px">24</a></div>'+
				'		<div><a href="#" style="font-size:36px">36</a></div>'+
				'		<div><a href="#" style="font-size:48px">48</a></div>'+
				'		<div><a href="#" style="font-size:60px">60</a></div>'+
				'		<div><a href="#" style="font-size:72px">72</a></div>'+
				'	</div>'+
				'</div>'
			),
			_call = function(e) {
				var _value = this.style.fontSize;
				_self.value= _value;
				_self.exec();
			};

		$html.find( '>.centbox a' ).click( _call );

		this.createDialog( {
			body: $html
		} );

	},
	exec: function() {
		var _self = this,
			_html = '<span style="font-size:'+_self.value+'">'+_self.editor.selectedText()+'</span>';

		_self.editor.pasteHTML( _html );
	}
} );

$.TE.plugin( 'fontcolor', {
	click: function() {
		var _self = this,
			$html = $(
				'<div class="te_dialog_fontcolor Lovh">'+
				'	<div class="colorsel">'+
				'		<!--first row-->'+
				'		<a href="###" style="background-color:#FF0000">&nbsp;</a>'+
				'		<a href="###" style="background-color:#FFA900">&nbsp;</a>'+
				'		<a href="###" style="background-color:#FFFF00">&nbsp;</a>'+
				'		<a href="###" style="background-color:#99E600">&nbsp;</a>'+
				'		<a href="###" style="background-color:#99E600">&nbsp;</a>'+
				'		<a href="###" style="background-color:#00FFFF">&nbsp;</a>'+
				'		<a href="###" style="background-color:#00AAFF">&nbsp;</a>'+
				'		<a href="###" style="background-color:#0055FF">&nbsp;</a>'+
				'		<a href="###" style="background-color:#5500FF">&nbsp;</a>'+
				'		<a href="###" style="background-color:#AA00FF">&nbsp;</a>'+
				'		<a href="###" style="background-color:#FF007F">&nbsp;</a>'+
				'		<a href="###" style="background-color:#FFFFFF">&nbsp;</a>'+
				'		<!--The second column-->'+
				'		<a href="###" style="background-color:#FFE5E5">&nbsp;</a>'+
				'		<a href="###" style="background-color:#FFF2D9">&nbsp;</a>'+
				'		<a href="###" style="background-color:#FFFFCC">&nbsp;</a>'+
				'		<a href="###" style="background-color:#EEFFCC">&nbsp;</a>'+
				'		<a href="###" style="background-color:#D9FFE0">&nbsp;</a>'+
				'		<a href="###" style="background-color:#D9FFFF">&nbsp;</a>'+
				'		<a href="###" style="background-color:#D9F2FF">&nbsp;</a>'+
				'		<a href="###" style="background-color:#D9E6FF">&nbsp;</a>'+
				'		<a href="###" style="background-color:#E6D9FF">&nbsp;</a>'+
				'		<a href="###" style="background-color:#F2D9FF">&nbsp;</a>'+
				'		<a href="###" style="background-color:#FFD9ED">&nbsp;</a>'+
				'		<a href="###" style="background-color:#D9D9D9">&nbsp;</a>'+
				'		<!--The third column-->'+
				'		<a href="###" style="background-color:#E68A8A">&nbsp;</a>'+
				'		<a href="###" style="background-color:#E6C78A">&nbsp;</a>'+
				'		<a href="###" style="background-color:#FFFF99">&nbsp;</a>'+
				'		<a href="###" style="background-color:#BFE673">&nbsp;</a>'+
				'		<a href="###" style="background-color:#99EEA0">&nbsp;</a>'+
				'		<a href="###" style="background-color:#A1E6E6">&nbsp;</a>'+
				'		<a href="###" style="background-color:#99DDFF">&nbsp;</a>'+
				'		<a href="###" style="background-color:#8AA8E6">&nbsp;</a>'+
				'		<a href="###" style="background-color:#998AE6">&nbsp;</a>'+
				'		<a href="###" style="background-color:#C78AE6">&nbsp;</a>'+
				'		<a href="###" style="background-color:#E68AB9">&nbsp;</a>'+
				'		<a href="###" style="background-color:#B3B3B3">&nbsp;</a>'+
				'		<!--The fourth column-->'+
				'		<a href="###" style="background-color:#CC5252">&nbsp;</a>'+
				'		<a href="###" style="background-color:#CCA352">&nbsp;</a>'+
				'		<a href="###" style="background-color:#D9D957">&nbsp;</a>'+
				'		<a href="###" style="background-color:#A7CC39">&nbsp;</a>'+
				'		<a href="###" style="background-color:#57CE6D">&nbsp;</a>'+
				'		<a href="###" style="background-color:#52CCCC">&nbsp;</a>'+
				'		<a href="###" style="background-color:#52A3CC">&nbsp;</a>'+
				'		<a href="###" style="background-color:#527ACC">&nbsp;</a>'+
				'		<a href="###" style="background-color:#6652CC">&nbsp;</a>'+
				'		<a href="###" style="background-color:#A352CC">&nbsp;</a>'+
				'		<a href="###" style="background-color:#CC5291">&nbsp;</a>'+
				'		<a href="###" style="background-color:#B3B3B3">&nbsp;</a>'+
				'		<!--Fifth column-->'+
				'		<a href="###" style="background-color:#991F1F">&nbsp;</a>'+
				'		<a href="###" style="background-color:#99701F">&nbsp;</a>'+
				'		<a href="###" style="background-color:#99991F">&nbsp;</a>'+
				'		<a href="###" style="background-color:#59800D">&nbsp;</a>'+
				'		<a href="###" style="background-color:#0F9932">&nbsp;</a>'+
				'		<a href="###" style="background-color:#1F9999">&nbsp;</a>'+
				'		<a href="###" style="background-color:#1F7099">&nbsp;</a>'+
				'		<a href="###" style="background-color:#1F4799">&nbsp;</a>'+
				'		<a href="###" style="background-color:#471F99">&nbsp;</a>'+
				'		<a href="###" style="background-color:#701F99">&nbsp;</a>'+
				'		<a href="###" style="background-color:#991F5E">&nbsp;</a>'+
				'		<a href="###" style="background-color:#404040">&nbsp;</a>'+
				'		<!--The sixth column-->'+
				'		<a href="###" style="background-color:#660000">&nbsp;</a>'+
				'		<a href="###" style="background-color:#664B14">&nbsp;</a>'+
				'		<a href="###" style="background-color:#666600">&nbsp;</a>'+
				'		<a href="###" style="background-color:#3B5900">&nbsp;</a>'+
				'		<a href="###" style="background-color:#005916">&nbsp;</a>'+
				'		<a href="###" style="background-color:#146666">&nbsp;</a>'+
				'		<a href="###" style="background-color:#144B66">&nbsp;</a>'+
				'		<a href="###" style="background-color:#143066">&nbsp;</a>'+
				'		<a href="###" style="background-color:#220066">&nbsp;</a>'+
				'		<a href="###" style="background-color:#301466">&nbsp;</a>'+
				'		<a href="###" style="background-color:#66143F">&nbsp;</a>'+
				'		<a href="###" style="background-color:#000000">&nbsp;</a>'+
				'	</div>'+
				'</div>'
			),
			_call = function(e) {
				var _value = this.style.backgroundColor;
				_self.value= _value;
				_self.exec();
			};

		$html.find( '>.colorsel a' ).click( _call );

		this.createDialog( {
			body: $html
		} );
	}
} );

$.TE.plugin( 'backcolor', {
	click: function() {
		var _self = this,
			$html = $(
				'<div class="te_dialog_fontcolor Lovh">'+
				'	<div class="colorsel">'+
				'		<!--first row-->'+
				'		<a href="###" style="background-color:#FF0000">&nbsp;</a>'+
				'		<a href="###" style="background-color:#FFA900">&nbsp;</a>'+
				'		<a href="###" style="background-color:#FFFF00">&nbsp;</a>'+
				'		<a href="###" style="background-color:#99E600">&nbsp;</a>'+
				'		<a href="###" style="background-color:#99E600">&nbsp;</a>'+
				'		<a href="###" style="background-color:#00FFFF">&nbsp;</a>'+
				'		<a href="###" style="background-color:#00AAFF">&nbsp;</a>'+
				'		<a href="###" style="background-color:#0055FF">&nbsp;</a>'+
				'		<a href="###" style="background-color:#5500FF">&nbsp;</a>'+
				'		<a href="###" style="background-color:#AA00FF">&nbsp;</a>'+
				'		<a href="###" style="background-color:#FF007F">&nbsp;</a>'+
				'		<a href="###" style="background-color:#FFFFFF">&nbsp;</a>'+
				'		<!--The second column-->'+
				'		<a href="###" style="background-color:#FFE5E5">&nbsp;</a>'+
				'		<a href="###" style="background-color:#FFF2D9">&nbsp;</a>'+
				'		<a href="###" style="background-color:#FFFFCC">&nbsp;</a>'+
				'		<a href="###" style="background-color:#EEFFCC">&nbsp;</a>'+
				'		<a href="###" style="background-color:#D9FFE0">&nbsp;</a>'+
				'		<a href="###" style="background-color:#D9FFFF">&nbsp;</a>'+
				'		<a href="###" style="background-color:#D9F2FF">&nbsp;</a>'+
				'		<a href="###" style="background-color:#D9E6FF">&nbsp;</a>'+
				'		<a href="###" style="background-color:#E6D9FF">&nbsp;</a>'+
				'		<a href="###" style="background-color:#F2D9FF">&nbsp;</a>'+
				'		<a href="###" style="background-color:#FFD9ED">&nbsp;</a>'+
				'		<a href="###" style="background-color:#D9D9D9">&nbsp;</a>'+
				'		<!--The third column-->'+
				'		<a href="###" style="background-color:#E68A8A">&nbsp;</a>'+
				'		<a href="###" style="background-color:#E6C78A">&nbsp;</a>'+
				'		<a href="###" style="background-color:#FFFF99">&nbsp;</a>'+
				'		<a href="###" style="background-color:#BFE673">&nbsp;</a>'+
				'		<a href="###" style="background-color:#99EEA0">&nbsp;</a>'+
				'		<a href="###" style="background-color:#A1E6E6">&nbsp;</a>'+
				'		<a href="###" style="background-color:#99DDFF">&nbsp;</a>'+
				'		<a href="###" style="background-color:#8AA8E6">&nbsp;</a>'+
				'		<a href="###" style="background-color:#998AE6">&nbsp;</a>'+
				'		<a href="###" style="background-color:#C78AE6">&nbsp;</a>'+
				'		<a href="###" style="background-color:#E68AB9">&nbsp;</a>'+
				'		<a href="###" style="background-color:#B3B3B3">&nbsp;</a>'+
				'		<!--The fourth column-->'+
				'		<a href="###" style="background-color:#CC5252">&nbsp;</a>'+
				'		<a href="###" style="background-color:#CCA352">&nbsp;</a>'+
				'		<a href="###" style="background-color:#D9D957">&nbsp;</a>'+
				'		<a href="###" style="background-color:#A7CC39">&nbsp;</a>'+
				'		<a href="###" style="background-color:#57CE6D">&nbsp;</a>'+
				'		<a href="###" style="background-color:#52CCCC">&nbsp;</a>'+
				'		<a href="###" style="background-color:#52A3CC">&nbsp;</a>'+
				'		<a href="###" style="background-color:#527ACC">&nbsp;</a>'+
				'		<a href="###" style="background-color:#6652CC">&nbsp;</a>'+
				'		<a href="###" style="background-color:#A352CC">&nbsp;</a>'+
				'		<a href="###" style="background-color:#CC5291">&nbsp;</a>'+
				'		<a href="###" style="background-color:#B3B3B3">&nbsp;</a>'+
				'		<!--Fifth column-->'+
				'		<a href="###" style="background-color:#991F1F">&nbsp;</a>'+
				'		<a href="###" style="background-color:#99701F">&nbsp;</a>'+
				'		<a href="###" style="background-color:#99991F">&nbsp;</a>'+
				'		<a href="###" style="background-color:#59800D">&nbsp;</a>'+
				'		<a href="###" style="background-color:#0F9932">&nbsp;</a>'+
				'		<a href="###" style="background-color:#1F9999">&nbsp;</a>'+
				'		<a href="###" style="background-color:#1F7099">&nbsp;</a>'+
				'		<a href="###" style="background-color:#1F4799">&nbsp;</a>'+
				'		<a href="###" style="background-color:#471F99">&nbsp;</a>'+
				'		<a href="###" style="background-color:#701F99">&nbsp;</a>'+
				'		<a href="###" style="background-color:#991F5E">&nbsp;</a>'+
				'		<a href="###" style="background-color:#404040">&nbsp;</a>'+
				'		<!--The sixth column-->'+
				'		<a href="###" style="background-color:#660000">&nbsp;</a>'+
				'		<a href="###" style="background-color:#664B14">&nbsp;</a>'+
				'		<a href="###" style="background-color:#666600">&nbsp;</a>'+
				'		<a href="###" style="background-color:#3B5900">&nbsp;</a>'+
				'		<a href="###" style="background-color:#005916">&nbsp;</a>'+
				'		<a href="###" style="background-color:#146666">&nbsp;</a>'+
				'		<a href="###" style="background-color:#144B66">&nbsp;</a>'+
				'		<a href="###" style="background-color:#143066">&nbsp;</a>'+
				'		<a href="###" style="background-color:#220066">&nbsp;</a>'+
				'		<a href="###" style="background-color:#301466">&nbsp;</a>'+
				'		<a href="###" style="background-color:#66143F">&nbsp;</a>'+
				'		<a href="###" style="background-color:#000000">&nbsp;</a>'+
				'	</div>'+
				'</div>'
			),
			_call = function(e) {
				var _value = this.style.backgroundColor;
				_self.value= _value;
				_self.exec();
			};

		$html.find( '>.colorsel a' ).click( _call );

		this.createDialog( {
			body: $html
		} );
	}
} );

$.TE.plugin( 'about', {
	'click': function() {
		var _self = this,
			$html = $(
				'<div class="te_dialog_about Lovh">'+
				'	<div class="seltab">'+
				'		<div class="links Lovh">'+
				'			<a href="###" class="cstyle">onThinkEditor</a>'+
				'		</div>'+
				'		<div class="bdb">&nbsp;</div>'+
				'	</div>'+
				'	<div class="centbox">'+
				'		<div class="aboutcontent">'+
				'			<p>ThinkPHPYesOneFree Open Sourceof，fast、simpleofFaceObjectsLightweightPHPDevelopment framework, followedApache2opensourceprotocolrelease,Yesin order toagileWEBapplicationDevelopAnd simplify enterpriseapplicationDevelop而Born.Has many outstandingFeatureswithcharacteristic, Has experienced more than three years of development at the same time,inCommunity TeamofActive participationversus下,inEase of use、SpreadAnd continue to optimize and improve performance，numerousof典typeCasemake surecanstableForbusinessas well asPortal levelofDevelop。</p>'+
				'			<p>ThinkPHPDrawsforeignMany outstandingofframewithmode,useFaceObjectsDevelopStructure andMVCmode，usesingleEntrancemodeWait，CombinesStrutsofActionIdeas andJSPofTagLib(Tag library),RoRofORMMapping andActiveRecordMode, encapsulatesCURDAnd some commonoperating,inprojectConfiguration、Class LibraryImporting、stencilengine、InquireLanguage、automaticverification、View model、projectCompile、Cachemechanism、SEOstand by、distributeddatabase、manyDatabase ConnectivityAnd switching、AuthenticateAnd mechanismsSpreadBoth in terms of uniqueofwhich performed。</p>'+
				'			<p>useThinkPHP，You can more easily and quicklyofDevelopwithdeployapplication。Of course, not just enterprise levelApplication, anyPHPApplication development are available fromThinkPHPofSimple andfastofcharacteristicBenefit。ThinkPHPItself has a lot ofofOriginalcharacteristic，And advocacyRoad to Jane，Developed by meofDevelopidea，With a minimumofCodecarry outMoreofFeatures，The purpose is to makeWEBapplicationDevelopsimpler、morefast。to this endThinkPHPWe will continue to absorb and integrate betterofTechnology to ensure its freshness and vitality，provideWEBapplicationDevelopofBest Practices!</p>'+
				'			<p>ThinkPHPfollowApache2Open Source Licenseprotocolrelease，It means that you are freeuseThinkPHP，evenallowBased on yourThinkPHPDevelopofapplicationOpen source or commercial product launches/Sales. </p>'+
				'		</div>'+
				'	</div>'+
				'	<div class="btnarea">'+
				'		<input type="button" value="shut down" class="te_close" />'+
				'	</div>'+
				'</div>'
			);

		_self.createDialog( {
			body: $html
		} );

	}
} );

//$.TE.plugin( 'eq', {
//    'click': function() {
//        var _self = this,
//        $html = $(
//            '<div class="te_dialog_about Lovh">'+
//            '	<div class="seltab">'+
//            '		<div class="links Lovh">'+
//            '			<a href="###" class="cstyle">onThinkEditor</a>'+
//            '		</div>'+
//            '		<div class="bdb">&nbsp;</div>'+
//            '	</div>'+
//            '	<div class="centbox">'+
//            '		<div class="eqcontent">'+
//            '           <label for="eq_name">name</label>'+
//            '           <input type="text" name="eq_name" id="eq_name"/></br>'+
//            '           <label for="eq_name">value</label>'+
//            '           <input type="text" name="eq_val" id="eq_val"/></br>'+
//            '           <textarea name="eq_content" id="eq_content" cols="30" rows="2"></textarea>'+
//            '		</div>'+
//            '	</div>'+
//            '	<div class="btnarea">'+
//            '		<input type="button" value="Ok" class="te_ok" />'+
//            '		<input type="button" value="shut down" class="te_close" />'+
//            '	</div>'+
//            '</div>'
//            );
//
//        _self.createDialog({
//            body: $html,
//            ok   : function(){
//				var _name = $html.find('#eq_name').val(),
//					_val = $html.find('#eq_val').val(),
//                    _content = $html.find('#eq_content').val(),
//					_html = '';
//                    _html += '<eq name="' + _name +'" value="' + _val +'">'+
//                                _content +
//                              '</eq>';  
//				_self.editor.pasteHTML( _html );
//				_self.hideDialog();
//			}
//        });
//
//    }
//});

} )( jQuery );
