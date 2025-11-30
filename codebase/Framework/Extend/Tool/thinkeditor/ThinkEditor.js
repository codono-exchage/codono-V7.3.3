(function ($) {

    var ie = $.browser.msie,
		iOS = /iphone|ipad|ipod/i.test(navigator.userAgent);

    $.TE = {
		version:'1.0', // version number
        debug: 1, //Debugging
        timeOut: 3000, //loadsinglefiletime outtime,unitformillisecond。
        defaults: {
            //The default parameterscontrols,noRigths,plugins,Defined load plugins
            controls: "source,|,undo,redo,|,cut,copy,paste,pastetext,selectAll,blockquote,|,image,flash,table,hr,pagebreak,face,code,|,link,unlink,|,print,fullscreen,|,eq,|,style,font,fontsize,|,fontcolor,backcolor,|,bold,italic,underline,strikethrough,unformat,|,leftalign,centeralign,rightalign,blockjustify,|,orderedlist,unorderedlist,indent,outdent,|,subscript,superscript",
            //noRights:"underline,strikethrough,superscript",
            width: 740,
            height: 500,
            skins: "default",
            resizeType: 2,
            face_path: ['qq_face', 'qq_face'],
            minHeight: 200,
            minWidth: 500,
            uploadURL: 'about:blank',
            theme: 'default'
        },
        buttons: {
            //Button Properties
            //eq: {title: 'equal',cmd: 'bold'},
            bold: { title: "Bold", cmd: "bold" },
            pastetext: { title: "Paste unformatted", cmd: "bold" },
            pastefromword: { title: "Stickwordformat", cmd: "bold" },
            selectAll: { title: "select all", cmd: "selectall" },
            blockquote: { title: "Quote" },
            find: { title: "Seek", cmd: "bold" },
            flash: { title: "insertflash", cmd: "bold" },
            media: { title: "Insert multimedia", cmd: "bold" },
            table: { title: "Insert Table" },
            pagebreak: { title: "Insert page breaks" },
            face: { title: "Insert smiley", cmd: "bold" },
            code: { title: "Insert source", cmd: "bold" },
            print: { title: "print", cmd: "print" },
            about: { title: "on", cmd: "bold" },
            fullscreen: { title: "full screen", cmd: "fullscreen" },
            source: { title: "HTMLCode", cmd: "source" },
            undo: { title: "Retreat", cmd: "undo" },
            redo: { title: "go ahead", cmd: "redo" },
            cut: { title: "Clip", cmd: "cut" },
            copy: { title: "copy", cmd: "copy" },
            paste: { title: "Stick", cmd: "paste" },
            hr: { title: "Horizontal line inserted", cmd: "inserthorizontalrule" },
            link: { title: "Create link", cmd: "createlink" },
            unlink: { title: "Remove Link", cmd: "unlink" },
            italic: { title: "Italic", cmd: "italic" },
            underline: { title: "Underline", cmd: "underline" },
            strikethrough: { title: "Strikethrough", cmd: "strikethrough" },
            unformat: { title: "Clear Formatting", cmd: "removeformat" },
            subscript: { title: "Subscript", cmd: "subscript" },
            superscript: { title: "Superscript", cmd: "superscript" },
            orderedlist: { title: "Ordered list", cmd: "insertorderedlist" },
            unorderedlist: { title: "Unordered list", cmd: "insertunorderedlist" },
            indent: { title: "Increase Indent", cmd: "indent" },
            outdent: { title: "Decrease Indent", cmd: "outdent" },
            leftalign: { title: "Left", cmd: "justifyleft" },
            centeralign: { title: "Align", cmd: "justifycenter" },
            rightalign: { title: "Align Right", cmd: "justifyright" },
            blockjustify: { title: "Justify", cmd: "justifyfull" },
            font: { title: "Fonts", cmd: "fontname", value: "Microsoft elegant black" },
            fontsize: { title: "Font size", cmd: "fontsize", value: "4" },
            style: { title: "Paragraph title", cmd: "formatblock", value: "" },
            fontcolor: { title: "Foreground Color", cmd: "forecolor", value: "#ff6600" },
            backcolor: { title: "background color", cmd: "hilitecolor", value: "#ff6600" },
            image: { title: "Insert Picture", cmd: "insertimage", value: "" }
        },
        defaultEvent: {
            event: "click mouseover mouseout",
            click: function (e) {
                this.exec(e);
            },
            mouseover: function (e) {
                var opt = this.editor.opt;
                this.$btn.addClass(opt.cssname.mouseover);
            },
            mouseout: function (e) { },
            noRight: function (e) { },
            init: function (e) { },
            exec: function () {
                this.editor.restoreRange();
                //Excuting an order
                if ($.isFunction(this[this.cmd])) {
                    this[this.cmd](); //If there is currentcmdThe name of the method is executed
                } else {
                    this.editor.doc.execCommand(this.cmd, 0, this.value || null);
                }
                this.editor.focus();
                this.editor.refreshBtn();
                this.editor.hideDialog();
            },
            createDialog: function (v) {
                //createDialog
                var editor = this.editor,
				opt = editor.opt,
				$btn = this.$btn,
				_self = this;
                var defaults = {
                    body: "", //Dialog content
                    closeBtn: opt.cssname.dialogCloseBtn,
                    okBtn: opt.cssname.dialogOkBtn,
                    ok: function () {
                        //Click onokAfter the execution of the function buttons
                    },
                    setDialog: function ($dialog) {
                        //Settings dialog box (position)
                        var y = $btn.offset().top + $btn.outerHeight();
                        var x = $btn.offset().left;
                        $dialog.offset({
                            top: y,
                            left: x
                        });
                    }
                };
                var options = $.extend(defaults, v);
                //initializationDialog
                editor.$dialog.empty();
                //Adding content
                $body = $.type(options.body) == "string" ? $(options.body) : options.body;
                $dialog = $body.appendTo(editor.$dialog);
                $dialog.find("." + options.closeBtn).click(function () { _self.hideDialog(); });
                $dialog.find("." + options.okBtn).click(options.ok);
                //Settings dialog box
                editor.$dialog.show();
                options.setDialog(editor.$dialog);
            },
            hideDialog: function () {
                this.editor.hideDialog();
            }
            //getEnable:function(){return false},
            //disable:function(e){alert('disable')}
        },
        plugin: function (name, v) {
            //Add or modify the plug.
            $.TE.buttons[name] = $.extend($.TE.buttons[name], v);
        },
        config: function (name, value) {
            var _fn = arguments.callee;
            if (!_fn.conf) _fn.conf = {};

            if (value) {
                _fn.conf[name] = value;
                return true;
            } else {
                return name == 'default' ? $.TE.defaults : _fn.conf[name];
            }
        },
        systemPlugins: ['system', 'upload_interface'], //The system comes with plug-ins
        basePath: function () {
            var jsFile = "ThinkEditor.js";
            var src = $("script[src$='" + jsFile + "']").attr("src");
            return src.substr(0, src.length - jsFile.length);
        }
    };

    $.fn.extend({
        //Calling plug
        ThinkEditor: function (v) {
            //Configurationdeal with
            var conf = '',
				temp = '';

            conf = v ? $.extend($.TE.config(v.theme ? v.theme : 'default'), v) : $.TE.config('default');

            v = conf;
            //Configurationdeal withcarry out

            //Loading skin
            var skins = v.skins || $.TE.defaults.skins; //Get skin parameters
            var skinsDir = $.TE.basePath() + "skins/" + skins + "/",
			jsFile = "@" + skinsDir + "config.js",
			cssFile = "@" + skinsDir + "style.css";

            var _self = this;
            //Load plugins
            if ($.defined(v.plugins)) {
                var myPlugins = $.type(v.plugins) == "string" ? [v.plugins] : v.plugins;
                var files = $.merge($.merge([], $.TE.systemPlugins), myPlugins);
            } else {
                var files = $.TE.systemPlugins;
            }
            $.each(files, function (i, v) {
                files[i] = v + ".js";
            })

            files.push(jsFile, cssFile);
            files.push("@" + skinsDir + "dialog/css/base.css");
            files.push("@" + skinsDir + "dialog/css/te_dialog.css");

            $.loadFile(files, function () {
                //Set upcssparameter
                v.cssname = $.extend({}, TECSS, v.cssname);
                //Creating Editor,Object storage
                $(_self).each(function (idx, elem) {
                    var data = $(elem).data("editorData");
                    if (!data) {
                        data = new ThinkEditor(elem, v);
                        $(elem).data("editorData", data);
                    }
                });

            });
        }

    });
    //Editor object.
    function ThinkEditor(area, v) {

        //Add toAnti-random sequence numberconflict
        var _fn = arguments.callee;
        this.guid = !_fn.guid ? _fn.guid = 1 : _fn.guid += 1;

        //Generation parameters
        var opt = this.opt = $.extend({}, $.TE.defaults, v);
        var _self = this;
        //structure：Main floor，Tool layer，PacketFloor，Push buttonFloor,bottom,dialogFloor
        var $main = this.$main = $("<div></div>").addClass(opt.cssname.main),
			$toolbar_box = $('<div></div>').addClass(opt.cssname.toolbar_box).appendTo($main),
			$toolbar = this.$toolbar = $("<div></div>").addClass(opt.cssname.toolbar).appendTo($toolbar_box),
        /*$toolbar=this.$toolbar=$("<div></div>").addClass(opt.cssname.toolbar).appendTo($main),*/
			$group = $("<div></div>").addClass(opt.cssname.group).appendTo($toolbar),
			$bottom = this.$bottom = $("<div></div>").addClass(opt.cssname.bottom),
			$dialog = this.$dialog = $("<div></div>").addClass(opt.cssname.dialog),
			$area = $(area).hide(),
			$frame = $('<iframe frameborder="0"></iframe>');

        opt.noRights = opt.noRights || "";
        var noRights = opt.noRights.split(",");
        //Restructuring
        $main.insertBefore($area)
		.append($area);
        //Joinframe
        $frame.appendTo($main);
        //Joinbottom
        if (opt.resizeType != 0) {
            //Drag to change the height of the editor
            $("<div></div>").addClass(opt.cssname.resizeCenter).mousedown(function (e) {
                var y = e.pageY,
				x = e.pageX,
				height = _self.$main.height(),
				width = _self.$main.width();
                $(document).add(_self.doc).mousemove(function (e) {
                    var mh = e.pageY - y;
                    _self.resize(width, height + mh);
                });
                $(document).add(_self.doc).mouseup(function (e) {
                    $(document).add(_self.doc).unbind("mousemove");
                    $(document).add(_self.doc).unbind("mousemup");
                });
            }).appendTo($bottom);
        }
        if (opt.resizeType == 2) {
            //Drag to change the height of the editorwithwidth
            $("<div></div>").addClass(opt.cssname.resizeLeft).mousedown(function (e) {
                var y = e.pageY,
				x = e.pageX,
				height = _self.$main.height(),
				width = _self.$main.width();
                $(document).add(_self.doc).mousemove(function (e) {
                    var mh = e.pageY - y,
					mw = e.pageX - x;
                    _self.resize(width + mw, height + mh);
                });
                $(document).add(_self.doc).mouseup(function (e) {
                    $(document).add(_self.doc).unbind("mousemove");
                    $(document).add(_self.doc).unbind("mousemup");
                });
            }).appendTo($bottom);
        }
        $bottom.appendTo($main);
        $dialog.appendTo($main);
        //Button loop processing.
        //TODO The default parameter processing
        $.each(opt.controls.split(","), function (idx, bname) {
            var _fn = arguments.callee;
            if (_fn.count == undefined) {
                _fn.count = 0;
            }

            //Packet processing
            if (bname == "|") {
                //Width setting packet
                if (_fn.count) {
                    $toolbar.find('.' + opt.cssname.group + ':last').css('width', (opt.cssname.btnWidth * _fn.count + opt.cssname.lineWidth) + 'px');
                    _fn.count = 0;
                }
                //Packet宽End
                $group = $("<div></div>").addClass(opt.cssname.group).appendTo($toolbar);
                $("<div>&nbsp;</div>").addClass(opt.cssname.line).appendTo($group);

            } else {
                //Updated statistics
                _fn.count += 1;
                //Get Button Properties
                var btn = $.extend({}, $.TE.defaultEvent, $.TE.buttons[bname]);
                //No permission mark
                var noRightCss = "", noRightTitle = "";
                if ($.inArray(bname, noRights) != -1) {
                    noRightCss = " " + opt.cssname.noRight;
                    noRightTitle = "(No permission)";
                }
                $btn = $("<div></div>").addClass(opt.cssname.btn + " " + opt.cssname.btnpre + bname + noRightCss)
				.data("bname", bname)
				.attr("title", btn.title + noRightTitle)
				.appendTo($group)
				.bind(btn.event, function (e) {
				    //Not Available triggers
				    if ($(this).is("." + opt.cssname.disable)) {
				        if ($.isFunction(btn.disable)) btn.disable.call(btn, e);
				        return false;
				    }
				    //Judgment and authority are available
				    if ($(this).is("." + opt.cssname.noRight)) {
				        //Click onWhen the trigger without permission Description
				        btn['noRight'].call(btn, e);
				        return false;
				    }
				    if ($.isFunction(btn[e.type])) {
				        //trigger event
				        btn[e.type].call(btn, e);
				        //TODO RefreshPush button
				    }
				});
                if ($.isFunction(btn.init)) btn.init.call(btn); //initialization
                if (ie) $btn.attr("unselectable", "on");
                btn.editor = _self;
                btn.$btn = $btn;
            }
        });
        //Call core
        this.core = new editorCore($frame, $area);
        this.doc = this.core.doc;
        this.$frame = this.core.$frame;
        this.$area = this.core.$area;
        this.restoreRange = this.core.restoreRange;
        this.selectedHTML = function () { return this.core.selectedHTML(); }
        this.selectedText = function () { return this.core.selectedText(); }
        this.pasteHTML = function (v) { this.core.pasteHTML(v); }
        this.sourceMode = this.core.sourceMode;
        this.focus = this.core.focus;
        //Monitor changes
        $(this.core.doc).click(function () {
            //hideDialog
            _self.hideDialog();
        }).bind("keyup mouseup", function () {
            _self.refreshBtn();
        })
        this.refreshBtn();
        //Adjustmentsize
        this.resize(opt.width, opt.height);

        //ObtainDOMLevels
        this.core.focus();
    }
    //end ThinkEditor
    ThinkEditor.prototype.resize = function (w, h) {
        //The minimum height and width
        var opt = this.opt,
		h = h < opt.minHeight ? opt.minHeight : h,
		w = w < opt.minWidth ? opt.minWidth : w;
        this.$main.width(w).height(h);
        var height = h - (this.$toolbar.parent().outerHeight() + this.$bottom.height());
        this.$frame.height(height).width("100%");
        this.$area.height(height).width("100%");
    };
    //hideDialog
    ThinkEditor.prototype.hideDialog = function () {
        var opt = this.opt;
        $("." + opt.cssname.dialog).hide();
    };
    //RefreshPush button
    ThinkEditor.prototype.refreshBtn = function () {
        var sourceMode = this.sourceMode(); // Mark status.
        var opt = this.opt;
        if (!iOS && $.browser.webkit && !this.focused) {
            this.$frame[0].contentWindow.focus();
            window.focus();
            this.focused = true;
        }
        var queryObj = this.doc;
        if (ie) queryObj = this.core.getRange();
        //Loop button
        //TODO undo,redoAnd other judgment
        this.$toolbar.find("." + opt.cssname.btn + ":not(." + opt.cssname.noRight + ")").each(function () {
            var enabled = true,
			btnName = $(this).data("bname"),
			btn = $.extend({}, $.TE.defaultEvent, $.TE.buttons[btnName]),
			command = btn.cmd;
            if (sourceMode && btnName != "source") {
                enabled = false;
            } else if ($.isFunction(btn.getEnable)) {
                enabled = btn.getEnable.call(btn);
            } else if ($.isFunction(btn[command])) {
                enabled = true; //in casecommandforfromdefinitioncommand,The default isAvailable
            } else {
                if (!ie || btn.cmd != "inserthtml") {
                    try {
                        enabled = queryObj.queryCommandEnabled(command);
                        $.debug(enabled.toString(), "command:" + command);
                    }
                    catch (err) {
                        enabled = false;
                    }
                }

                //judgmentThatFeatureswhetherHaveachieve @TODO Code stalemate
                if ($.TE.buttons[btnName]) enabled = true;
            }
            if (enabled) {
                $(this).removeClass(opt.cssname.disable);
            } else {
                $(this).addClass(opt.cssname.disable);
            }
        });
    };
    //core code start
    function editorCore($frame, $area, v) {
        //TODO Parameter to global.
        var defaults = {
            docType: '<!DOCTYPE HTML>',
            docCss: "",
            bodyStyle: "margin:4px; font:10pt Arial,Verdana; cursor:text",
            focusExt: function (editor) {
                //triggerEditor gets focusTimecarried out，such asRefreshPush button
            },
            //textareaUpdates toiframeHandler
            updateFrame: function (code) {
                //OverturnflashPlaceholders
                code = code.replace(/(<embed[^>]*?type="application\/x-shockwave-flash" [^>]*?>)/ig, function ($1) {
                    var ret = '<img class="_flash_position" src="' + $.TE.basePath() + 'skins/default/img/spacer.gif" style="',
						_width = $1.match(/width="(\d+)"/),
						_height = $1.match(/height="(\d+)"/),
						_src = $1.match(/src="([^"]+)"/),
						_wmode = $1.match(/wmode="(\w+)"/),
						_data = '';

                    _width = _width && _width[1] ? parseInt(_width[1]) : 0;
                    _height = _height && _height[1] ? parseInt(_height[1]) : 0;
                    _src = _src && _src[1] ? _src[1] : '';
                    _wmode = _wmode && _wmode[1] ? true : false;
                    _data = "{'src':'" + _src + "','width':'" + _width + "','height':'" + _height + "','wmode':" + (_wmode) + "}";


                    if (_width) ret += 'width:' + _width + 'px;';
                    if (_height) ret += 'height:' + _height + 'px;';

                    ret += 'border:1px solid #DDD; display:inline-block;text-align:center;line-height:' + _height + 'px;" ';
                    ret += '_data="' + _data + '"';
                    ret += ' alt="flashPlaceholder" />';

                    return ret;
                });

                return code;
            },
            //iframeUpdate totextof， TODO Remove
            updateTextArea: function (html) {
                //Flip placeholderflash
                html = html.replace(/(<img[^>]*?class=(?:"|)_flash_position(?:"|)[^>]*?>)/ig, function ($1) {
                    var ret = '',
						data = $1.match(/_data="([^"]*)"/);

                    if (data && data[1]) {
                        data = eval('(' + data + ')');
                    }

                    ret += '<embed type="application/x-shockwave-flash" pluginspage="http://www.macromedia.com/go/getflashplayer" ';
                    ret += 'src="' + data.src + '" ';
                    ret += 'width="' + data.width + '" ';
                    ret += 'height="' + data.height + '" ';
                    if (data.wmode) ret += 'wmode="transparent" ';
                    ret += '/>';

                    return ret;
                });

                return html;
            }
        };
        options = $.extend({}, defaults, v);
        //Store Properties
        this.opt = options;
        this.$frame = $frame;
        this.$area = $area;
        var contentWindow = $frame[0].contentWindow,
		doc = this.doc = contentWindow.document,
		$doc = $(doc);

        var _self = this;

        //initialization
        doc.open();
        doc.write(
			options.docType +
			'<html>' +
			((options.docCss === '') ? '' : '<head><link rel="stylesheet" type="text/css" href="' + options.docCss + '" /></head>') +
			'<body style="' + options.bodyStyle + '"></body></html>'
			);
        doc.close();
        //Set upframeEdit mode
        try {
            if (ie) {
                doc.body.contentEditable = true;
            }
            else {
                doc.designMode = "on";
            }
        } catch (err) {
            $.debug(err, "Creating the edit mode error");
        }

        //Unite IE FF , Etc. execCommand behavior
        try {
            this.e.execCommand("styleWithCSS", 0, 0)
        }
        catch (e) {
            try {
                this.e.execCommand("useCSS", 0, 1);
            } catch (e) { }
        }

        //monitor
        if (ie)
            $doc.click(function () {
                _self.focus();
            });
        this.updateFrame(); //Updates

        if (ie) {
            $doc.bind("beforedeactivate beforeactivate selectionchange keypress", function (e) {
                if (e.type == "beforedeactivate")
                    _self.inactive = true;

                else if (e.type == "beforeactivate") {
                    if (!_self.inactive && _self.range && _self.range.length > 1)
                        _self.range.shift();
                    delete _self.inactive;
                }

                else if (!_self.inactive) {
                    if (!_self.range)
                        _self.range = [];
                    _self.range.unshift(_self.getRange());

                    while (_self.range.length > 2)
                        _self.range.pop();
                }

            });

            // Restore the text range when the iframe gains focus
            $frame.focus(function () {
                _self.restoreRange();
            });
        }

        ($.browser.mozilla ? $doc : $(contentWindow)).blur(function () {
            _self.updateTextArea(true);
        });
        this.$area.blur(function () {
            // Update the iframe when the textarea loses focus
            _self.updateFrame(true);
        });

        /*
        * //Automatically addedplabel
        * $doc.keydown(function(e){
        * 	if(e.keyCode == 13){
        * 		//_self.pasteHTML('<p>&nbsp;</p>');
        * 		//this.execCommand( 'formatblock', false, '<p>' );
        * 	}
        * });
        */

    }
    //Whether the source mode
    editorCore.prototype.sourceMode = function () {
        return this.$area.is(":visible");
    };
    //Editor gets focus
    editorCore.prototype.focus = function () {
        var opt = this.opt;
        if (this.sourceMode()) {
            this.$area.focus();
        }
        else {
            this.$frame[0].contentWindow.focus();
        }
        if ($.isFunction(opt.focusExt)) opt.focusExt(this);
    };
    //textareaUpdates toiframe
    editorCore.prototype.updateFrame = function (checkForChange) {
        var code = this.$area.val(),
		options = this.opt,
		updateFrameCallback = options.updateFrame,
		$body = $(this.doc.body);
        //If judged to have been modified
        if (updateFrameCallback) {
            var sum = checksum(code);
            if (checkForChange && this.areaChecksum == sum)
                return;
            this.areaChecksum = sum;
        }

        //Callbackdeal with
        var html = updateFrameCallback ? updateFrameCallback(code) : code;

        // Banscriptlabel

        html = html.replace(/<(?=\/?script)/ig, "&lt;");

        // TODODetermine whether there is action
        if (options.updateTextArea)
            this.frameChecksum = checksum(html);

        if (html != $body.html()) {
            $body.html(html);
        }
    };
    editorCore.prototype.getRange = function () {
        if (ie) return this.getSelection().createRange();
        return this.getSelection().getRangeAt(0);
    };

    editorCore.prototype.getSelection = function () {
        if (ie) return this.doc.selection;
        return this.$frame[0].contentWindow.getSelection();
    };
    editorCore.prototype.restoreRange = function () {
        if (ie && this.range)
            this.range[0].select();
    };

    editorCore.prototype.selectedHTML = function () {
        this.restoreRange();
        var range = this.getRange();
        if (ie)
            return range.htmlText;
        var layer = $("<layer>")[0];
        layer.appendChild(range.cloneContents());
        var html = layer.innerHTML;
        layer = null;
        return html;
    };


    editorCore.prototype.selectedText = function () {
        this.restoreRange();
        if (ie) return this.getRange().text;
        return this.getSelection().toString();
    };

    editorCore.prototype.pasteHTML = function (value) {
        this.restoreRange();
        if (ie) {
            this.getRange().pasteHTML(value);
        } else {
            this.doc.execCommand("inserthtml", 0, value || null);
        }
        //Gets focus
        this.$frame[0].contentWindow.focus();
    }

    editorCore.prototype.updateTextArea = function (checkForChange) {
        var html = $(this.doc.body).html(),
		options = this.opt,
		updateTextAreaCallback = options.updateTextArea,
		$area = this.$area;


        if (updateTextAreaCallback) {
            var sum = checksum(html);
            if (checkForChange && this.frameChecksum == sum)
                return;
            this.frameChecksum = sum;
        }


        var code = updateTextAreaCallback ? updateTextAreaCallback(html) : html;
        // TODO Determine whether it is necessary
        if (options.updateFrame)
            this.areaChecksum = checksum(code);
        if (code != $area.val()) {
            $area.val(code);
        }

    };
    function checksum(text) {
        var a = 1, b = 0;
        for (var index = 0; index < text.length; ++index) {
            a = (a + text.charCodeAt(index)) % 65521;
            b = (b + a) % 65521;
        }
        return (b << 16) | a;
    }
    $.extend({
        teExt: {
        //Extended configuration
    },
    debug: function (msg, group) {
        //Determine whether thereconsoleObjects
        if ($.TE.debug && window.console !== undefined) {
            //PacketStart
            if (group) console.group(group);
            if ($.type(msg) == "string") {
                //Whether to perform special functions,Separated by a double colon
                if (msg.indexOf("::") != -1) {
                    var arr = msg.split("::");
                    eval("console." + arr[0] + "('" + arr[1] + "')");
                } else {
                    console.debug(msg);
                }
            } else {
                if ($(msg).html() == null) {
                    console.dir(msg); //Output object or array
                } else {
                    console.dirxml($(msg)[0]); //ExportdomObjects
                }

            }
            //recordingtraceinformation
            if ($.TE.debug == 2) {
                console.group("trace information:");
                console.trace();
                console.groupEnd();
            }
            //PacketEnd
            if (group) console.groupEnd();
        }
    },
    //end debug
    defined: function (variable) {
        return $.type(variable) == "undefined" ? false : true;
    },
    isTag: function (tn) {
        if (!tn) return false;
        return $(this)[0].tagName.toLowerCase() == tn ? true : false;
    },
    //end istag
    include: function (file) {
        if (!$.defined($.TE.loadUrl)) $.TE.loadUrl = {};
        //definitionskinpathAnd plug-inspath。
        var basePath = $.TE.basePath(),
			skinsDir = basePath + "skins/",
			pluginDir = basePath + "plugins/";
        var files = $.type(file) == "string" ? [file] : file;
        for (var i = 0; i < files.length; i++) {
            var loadurl = name = $.trim(files[i]);
            //Determine whether the load has been
            if ($.TE.loadUrl[loadurl]) {
                continue;
            }
            //Determine whether there@
            var at = false;
            if (name.indexOf("@") != -1) {
                at = true;
                name = name.substr(1);
            }
            var att = name.split('.');
            var ext = att[att.length - 1].toLowerCase();
            if (ext == "css") {
                //loadcss
                var filepath = at ? name : skinsDir + name;
                var newNode = document.createElement("link");
                newNode.setAttribute('type', 'text/css');
                newNode.setAttribute('rel', 'stylesheet');
                newNode.setAttribute('href', filepath);
                $.TE.loadUrl[loadurl] = 1;
            } else {
                var filepath = at ? name : pluginDir + name;
                //$("<scri"+"pt>"+"</scr"+"ipt>").attr({src:filepath,type:'text/javascript'}).appendTo('head');
                var newNode = document.createElement("script");
                newNode.type = "text/javascript";
                newNode.src = filepath;
                newNode.id = loadurl; //Achieve bulk load
                newNode.onload = function () {
                    $.TE.loadUrl[this.id] = 1;
                };
                newNode.onreadystatechange = function () {
                    //againstie
                    if ((newNode.readyState == 'loaded' || newNode.readyState == 'complete')) {
                        $.TE.loadUrl[this.id] = 1;
                    }
                };
            }
            $("head")[0].appendChild(newNode);
        }
    },
    //end include
    loadedFile: function (file) {
        //Determine whether to load
        if (!$.defined($.TE.loadUrl)) return false;
        var files = $.type(file) == "string" ? [file] : file,
			result = true;
        $.each(files, function (i, name) {
            if (!$.TE.loadUrl[name]) result = false;
			//alert(name+':'+result);
        });
		
        return result;		
    },
    //end loaded

    loadFile: function (file, fun) {
        //Load file，loadcompleteRearcarried outfunfunction.
        $.include(file);
		
        var time = 0;
        var check = function () {
			//alert($.loadedFile(file));
            if ($.loadedFile(file)) {
                if ($.isFunction(fun)) fun();
            } else {
				//alert(time);
                if (time >= $.TE.timeOut) {
                    // TODO What refinementFile failed to load。
                    $.debug(file, "File failed to load");
                } else {
					//alert('time:'+time);
                    setTimeout(check, 50);
                    time += 50;
                }
            }
        };
        check();
    }
    //end loadFile
});

})(jQuery);

jQuery.TE.config( 'mini', {
	'controls' : 'font,fontsize,fontcolor,backcolor,bold,italic,underline,unformat,leftalign,centeralign,rightalign,orderedlist,unorderedlist',
	'width':498,
	'height':400,
	'resizeType':1
} );