/**
 +-------------------------------------------------------------------
 * jQuery thinkbox - The popup plugin - http://zjzit.cn/thinkbox
 +-------------------------------------------------------------------
 * @version    1.0.0 beta2
 * @since      2013.05.10
 * @author     When wheat seedlings child <zuojiazi@vip.qq.com> <http://www.zjzit.cn>
 * @github     https://github.com/Aoiujz/thinkbox.git
 +-------------------------------------------------------------------
 */
(function($){
var
    /* currentscriptfilename */
    __FILE__ = $("script").last().attr("src"),

    /* Pop-layer objects */
    ThinkBox,

    /* The default pop-up layer options */
    defaults = {
        "style"       : "default", //Pop-up layer styles
        "title"       : null,      // Pop-layer header
        "fixed"       : true,      // Whether to use fixed positioning(fixed)Rather than absolute positioning(absolute)，IE6not support.
        "center"      : true,      // Pop-up layerwhetherCenter of the screendisplay
        "display"     : true,      // Is displayed immediately after creation
        "x"           : 0,         // Pop-up layer x coordinate。 when center Property true When this property is invalid
        "y"           : 0,         // Pop-up layer y coordinate。 when center Property true When this property is invalid
        "modal"       : true,      // Pop-up layerwhetherSet asModal。Set as true The display mask background
        "modalClose"  : true,      // Click onModal BACKGROUNDWhether to close the popup
        "resize"      : true,      // whetherinwindowSize changedAgainLocatePop-up layerposition
        "unload"      : false,     // Are uninstall closed
        "escHide"     : true,      // pressESCWhether to close the popup
        "delayClose"  : 0,         // Delay off automatically the popup 0They said they did not shut down automatically
        "drag"        : false,     // Click ontitleframewhetherallowdrag
        "width"       : "",        // The popup content area width Adaptive air express
        "height"      : "",        // Pop-layer height of the content area Adaptive air express
        "dataEle"     : "",        // Pop-up layerBindingToelement，Set upthisAttributesofPop-up layerOnly allowedSimultaneouslyexistOne
        "locate"      : ["left", "top"],       //The popup location attribute
        "show"        : ["fadeIn", "normal"],  //displayeffect
        "hide"        : ["fadeOut", "normal"], //Close results
        "actions"     : ["minimize", "maximize", "close"], //Window operation buttons
        "tools"       : false,  //Whether to create a toolbar
        "buttons"     : {},     //The default toolbar buttons onlytoolsfortrueValid
        "beforeShow"  : $.noop, //Callback method before the display
        "afterShow"   : $.noop, //Callback method after the show
        "afterHide"   : $.noop, //hideAfterCallbackmethod
        "beforeUnload": $.noop, //Uninstall agoofCallbackmethod
        "afterDrag"   : $.noop  //dragstopAfterCallbackmethod
    },

    /* Pop-layer stack height */
    zIndex = 2013,

    /* Layer pop-up Language Pack */
    lang = {},

    /* Layer pop-up list */
    lists = {},

    /* Pop-layer container */
    wrapper = [
        "<div class=\"thinkbox\" style=\"position:fixed\">",
            //useform，You can do goodofWidth Heightfromsuitableshould，And easy lowversionBrowseIs doing filletstyle
            "<table cellspacing=\"0\" cellpadding=\"0\" border=\"0\">",
                "<tr>",
                    "<td class=\"thinkbox-top-left\"></td>",  //The upper left corner
                    "<td class=\"thinkbox-top\"></td>",       //On top of
                    "<td class=\"thinkbox-top-right\"></td>", //Upper right corner
                "</tr>",
                "<tr>",
                    "<td class=\"thinkbox-left\"></td>",       //left
                    "<td class=\"thinkbox-inner\">", //Pop-up layerinner
                        "<div class=\"thinkbox-title\"></div>", //The popup title bar
						"<div class=\"thinkbox-body\"></div>", //Pop-up layerbody
                        "<div class=\"thinkbox-tools\"></div>", //Layer pop-up toolbar
                    "</td>",
                    "<td class=\"thinkbox-right\"></td>",      //right
                "</tr>",
                "<tr>",
                    "<td class=\"thinkbox-bottom-left\"></td>",  //Lower left corner
                    "<td class=\"thinkbox-bottom\"></td>",       //below
                    "<td class=\"thinkbox-bottom-right\"></td>", //Lower right corner
                "</tr>",
            "</table>",
        "</div>"].join(""),

    /* documentwithwindowRespectively corresponding to the objectjQueryObjects */
    _doc = $(document), _win = $(window);

/* Loads the specifiedCSSfile */
function includeCss(css, onload){
    var path = __FILE__.slice(0, __FILE__.lastIndexOf("/"));
    if($("link[href='" + path + css + "']").length){
        fire(onload);
        return;
    };

    //loadCSSfile
    $("<link/>")
        .load(function(){fire(onload)})
        .attr({
            "href" : path + css + "?" + Math.random(),
            "type" : "text/css",
            "rel"  : "stylesheet"
        }).appendTo("head");
}

/* ObtainThe screen viewing areaofThe size and location */
function viewport(){
    return {
        "width"  : _win.width(),
        "height" : _win.height(),
        "left"   : _win.scrollLeft(),
        "top"    : _win.scrollTop()
    };
}

/* Callback function */
function fire(event, data){
    if($.isFunction(event))
        return event.call(this, data);
}

/* deleteoptionsUnnecessary parameters */
function del(keys, options){
    if($.isArray(keys)){ //To delete multiple
        for(i in keys){
            _(keys[i]);
        }
    } else { //To delete a
        _(keys);
    }

    //FromoptionsinTo delete aDesignationofelement
    function _(key){
        if(key in options) delete options[key];
    }
}

/* Prohibit selected text */
function unselect(){
    var element = $("body")[0];
    element.onselectstart = function() {return false}; //ie
    element.unselectable = "on"; // ie
    element.style.MozUserSelect = "none"; // firefox
    element.style.WebkitUserSelect = "none"; // chrome
}

/* Allowing the selected text */
function onselect(){
    var element = $("body")[0];
    element.onselectstart = function() {return true}; //ie
    element.unselectable = "off"; // ie
    element.style.MozUserSelect = ""; // firefox
    element.style.WebkitUserSelect = ""; // chrome
}

/* Set ascurrentselectedmiddlePop-layer objects */
function setCurrent(){
    var options = lists[this.key][0], box = lists[this.key][1];
    if(lists.current != this.key){
        lists.current = this.key;
        options.modal && box.data("ThinkBoxModal").css({"zIndex": zIndex-1})
        box.css({"zIndex": zIndex++});
    }
}

/* Uninstall pop-layer container */
function unload(){
    var options = lists[this.key][0], box = lists[this.key][1];
    fire.call(this, options.beforeUnload); //Uninstall agoofCallbackmethod
    options.modal && box.data("ThinkBoxModal").remove();
    box.remove();
    _win.off("resize." + this.key);
    delete lists[this.key];
    options.dataEle && $(options.dataEle).removeData("ThinkBox");
}

/* BACKGROUND installation mode */
function setupModal(){
    var self    = this,
        options = lists[this.key][0],
        box     = lists[this.key][1],
        modal   = box.data("ThinkBoxModal");

    //existhideofMaskThe layerdirectdisplay
    if(modal){
        modal.show();
        return;
    }

    modal = $("<div class=\"thinkbox-modal-blackout-" + options.style + "\"></div>")
        .css({
            "zIndex"   : zIndex++,
            "position" : "fixed",
            "left"     : 0,
            "top"      : 0,
            "right"    : 0,
            "bottom"   : 0
        })
        .click(function(event){
            options.modalClose && lists.current == self.key && self.hide();
            event.stopPropagation();
        })
        .mousedown(function(event){event.stopPropagation()})
        .appendTo($("body"));
    box.data("ThinkBoxModal", modal);
}

/* Installation title bar */
function setupTitleBar() {
    var title = $(".thinkbox-title", lists[this.key][1]), options = lists[this.key][0];
    if(options.title){
        //Drag the popup
        if (options.drag) {
            title.addClass("thinkbox-draging");
            drag.call(this, title);
        }
        this.setTitle(options.title);
        //Install window operation buttons
        setupWindowActions.call(this, title);
    } else {
        title.remove();
    }
}

/* Installation layer eject operation button */
function setupWindowActions(title){
    var actions, button, action, options = lists[this.key][0], self = this;
    if(options.actions && $.isArray(options.actions)){
        actions = $("<div/>").addClass("thinkbox-window-actions").appendTo(title)
            .on("click", "button", function(){
                if(!$(this).hasClass("disabled")){
                    switch(this.name){
                        case "minimize": //minimize
                            self.minimize(this);
                            break;
                        case "maximize": //maximize
                            self.maximize(this);
                            break;
                        case "close": //shut down
                            self.hide();
                            break;
                    }
                }
            })
            .on("mousedown mouseup", function(event){event.stopPropagation()});
        for(i in options.actions){
            button = options.actions[i];
            action = $("<button/>").appendTo(actions).addClass("thinkbox-actions-" + button)
                .attr("name", button) //Setting name
                .attr("title", button) //Set uptitle
                .text(lang[button] || button); //Set the display text
        }
    }
}

/* Drag the popup */
function drag(title){
    var draging = null, self = this, options = lists[this.key][0], box = lists[this.key][1];
    _doc.mousemove(function(event){
        draging &&
        box.css({left: event.pageX - draging[0], top: event.pageY - draging[1]});
    });
    title.mousedown(function(event) {
        var offset = box.offset();
        if(options.fixed){
            offset.left -= _win.scrollLeft();
            offset.top -= _win.scrollTop();
        }
        unselect(); //Prohibit selected text
        draging = [event.pageX - offset.left, event.pageY - offset.top];
    }).mouseup(function() {
        draging = null;
        onselect(); //Allowing the selected text
        fire.call(self, options.afterDrag); //After dragging the callback function
    });
}

/* Install the toolbar */
function setupToolsBar() {
    var tools = $(".thinkbox-tools", lists[this.key][1]),
        options = lists[this.key][0], button, self = this;
    if(options.tools){
        if(options.buttons && $.isPlainObject(options.buttons)){
            for(name in options.buttons){
                this.addToolsButton(name, options.buttons[name]);
            }

            /* Bind button click event */
            tools.on("click", "button", function(){
                if(!$(this).hasClass("disabled")){
                    if(false === options.buttons[this.name][2].call(self)){
                        return;
                    }

                    /* Implementation of the default event */
                    switch(this.name){
                        case "close":
                        case "cancel":
                            self.hide(false);
                            break;
                        case "submit":
                            self.find("form").submit();
                            break;
                    }
                }
            });
        }
    } else {
        tools.remove();
    }
}

/**
 * structuremethod，ForInstantiationOnenewofPop-layer objects
 +----------------------------------------------------------
 * element The popup content elements
 * options Pop-up layer options
 +----------------------------------------------------------
 */
ThinkBox = function(element, options){
    var self = this, options, box, boxLeft; //initializationvariable
    options  = $.extend({}, defaults, options || {}); //Consolidated configuration options

    /* Creating pop-layer container */
    box = $(wrapper).addClass("thinkbox-" + options.style).data("thinkbox", self);

    /* StoragePop-up layerBasicinformationToOverall situationvariable */
    this.key = "thinkbox_" + new Date().getTime() + (Math.random() + "").substr(2,12);
    lists[this.key] = [options, box];

    /* CachePop-up layer，preventpop upMore */
    options.dataEle && $(options.dataEle).data("thinkbox", self);

    /**
     * giveboxBinding events
     * mousepress下The current recordPop-layer objects
     * mouseClick onpreventeventbubble
     */
    box.on("click mousedown", function(event){
        setCurrent.call(self);
        event.stopPropagation();
    });

    /* Set the popup location attribute */
    options.fixed || box.css("position", "absolute");

    /* Mounting the popup related components */
    setupTitleBar.call(self); // Installation title bar
    setupToolsBar.call(self);// Install the toolbar

    /* Automatically loadcssFile and display the pop-up layer */
    includeCss("/skin/" + options.style + "/style.css", function(){
        box.hide().appendTo("body"); //put into abody

        /* solveDrag outBrowseDeviceTimeleftDo not showofBUG */
        boxLeft = $(".thinkbox-left", box).width();
        boxLeft && $(".thinkbox-left", box).append($("<div/>").css("width", boxLeft));

        self.setSize(options.width, options.height);
        self.setContent(element || "<div></div>"); //Set content
        options.display && self.show();
    });

}; //END ThinkBox

/**
 * registeredThinkBoxopenAPIinterface
 */
ThinkBox.prototype = {
    /* Display pop-up layer */
    "show" : function(){
        var self = this, options = lists[this.key][0], box = lists[this.key][1];
        if(box.is(":visible")) return this;
        options.modal && setupModal.call(this); // BACKGROUND installation mode
        fire.call(this, options.beforeShow); //transferdisplayprior toCallback
        //displayeffect
        switch(options.show[0]){
            case "slideDown":
                box.stop(true, true).slideDown(options.show[1], _);
                break;
            case "fadeIn":
                box.stop(true, true).fadeIn(options.show[1], _);
                break;
            default:
                box.show(options.show[1], _);
        }

        //windowAfter changing the size of the reset position and size
        options.resize && _win.on("resize." + self.key, function(){
            self.setSize(options.width, options.height);
            self.resetLocate();
        });
        setCurrent.call(this);
        return this;

        function _(){
            options.delayClose &&
            $.isNumeric(options.delayClose) &&
            setTimeout(function(){
                self.hide();
            }, options.delayClose);
            //transferCallback method after the show
            fire.call(self, options.afterShow);
        }
    },

    /* Close the popup data forPass toshut downRearCallbackofadditionaldata */
    "hide" : function(data){
        var self = this, options = lists[this.key][0], box = lists[this.key][1], modal;
        if(!box.is(":visible")) return this;

        //Hide mask layer
        modal = box.data("ThinkBoxModal");
        modal && modal.fadeOut();

        //Tibetan shadow effect
        switch(options.hide[0]){
            case "slideUp":
                box.stop(true, true).slideUp(options.hide[1], _);
                break;
            case "fadeOut":
                box.stop(true, true).fadeOut(options.hide[1], _);
                break;
            default:
                box.hide(options.hide[1], _);
        }
        return this;

        function _() {
            fire.call(self, options.afterHide, data); //hideAfterCallbackmethod
            options.unload && unload.call(self);
        }
    },

    /* Show or hide the popup */
    "toggle" : function(){
        return lists[this.key][1].is(":visible") ? self.hide() : self.show();
    },

    /* In the pop-layer content Find */
    "find" : function(selector){
        var content = $(".thinkbox-body", lists[this.key][1]);
        return selector ? $(selector, content) : content.children();
    },

    /* Pop-up content acquisition layer */
    "getContent" : function(){
        return $(".thinkbox-body", lists[this.key][1]).html()
    },

    /* Setting pop-up content layer */
    "setContent" : function(content){ //Setting pop-up content layer
        var options = lists[this.key][0];
        $(".thinkbox-body", lists[this.key][1]).empty().append($(content).show()); // Add tonewcontent
        this.resetLocate(); //Provided the position of the pop-up layer
        return this;
    },

    /* Setting pop-up content layerareasize */
    "setSize" : function(width, height){
        var width  = $.isFunction(width)  ? width.call(this)  : width,
            height = $.isFunction(height) ? height.call(this) : height;
        $(".thinkbox-body", lists[this.key][1]).css({"width" : width, "height" : height});
        return this;
    },

    /* shiftmovePop-up layerTo the middle of the screen */
    "moveToCenter" : function() {
        var size     = this.getSize(),
            view     = viewport(),
            overflow = lists[this.key][1].css("position") == "fixed" ? [0, 0] : [view.left, view.top],
            x        = overflow[0] + view.width / 2,
            y        = overflow[1] + view.height / 2;
        this.moveTo(x - size[0] / 2, y - size[1] / 2);
        return this;
    },

    /* shiftmovePop-up layerToDesignationcoordinate */
    "moveTo" : function (x, y) {
        var box = lists[this.key][1], options = lists[this.key][0];
        $.isNumeric(x) &&
            (options.locate[0] == "left" ? box.css({"left" : x}) : box.css({"right" : x}));
        $.isNumeric(y) &&
            (options.locate[1] == "top" ? box.css({"top" : y}) : box.css({"bottom" : y}));
        return this;
    },

    /* Get the popup size */
    "getSize" : function (){
        var size = [0, 0], box = lists[this.key][1];
        if(box.is(":visible")) //Obtainto showPop-up layersize
            size = [box.width(), box.height()];
        else { //ObtainhideofPop-up layersize
            box.css({"visibility" : "hidden", "display" : "block"});
            size = [box.width(), box.height()];
            box.css({"visibility" : "visible", "display" : "none"});
        }
        return size;
    },

    /* Set the popup title */
    "setTitle" : function(title){
        $(".thinkbox-title", lists[this.key][1]).empty().append("<span>" + title + "</span>");
        return this;
    },

    /* Reset position the popup */
    "resetLocate" : function(){
        var options = lists[this.key][0];
        options.center ?
        this.moveToCenter() :
        this.moveTo(
            $.isNumeric(options.x) ?
                options.x :
                ($.isFunction(options.x) ? options.x.call($(options.dataEle)) : 0),
            $.isNumeric(options.y) ?
                options.y :
                ($.isFunction(options.y) ? options.y.call($(options.dataEle)) : 0)
        );
        return this;
    },

    /* Set the status bar information */
    "setStatus" : function(content, name){
        var options = lists[this.key][0],
            box     = lists[this.key][1],
            name    = name ? "thinkbox-status-" + name : "", status;
        /* existThe Toolbardisplaystatusinformation */
        if(options.tools){
            $(".thinkbox-status", box).remove();
            status = $("<div class=\"thinkbox-status\">").addClass(name).html(content);
            $(".thinkbox-tools", box).prepend(status);
        }
        return this;
    },

    /* Add a button */
    "addToolsButton" : function(name, config){
        var options = lists[this.key][0],
            box     = lists[this.key][1], button;
        /* The presence of a toolbar is createdbutton */
        if(options.tools){
            button = $("<button/>").attr("name", name).text(config[0]);
            config[1] && button.addClass("thinkbox-button-" + config[1]);
            if(!$.isFunction(config[2])){config[2] = $.noop};
            $(".thinkbox-tools", box).append(button);
        }
        return this;
    },

    /* A reset button */
    "setToolsButton" : function(oldName, newName, config){
        var options = lists[this.key][0],
            box     = lists[this.key][1], button;
        button = $(".thinkbox-tools", box).find("button[name=" + oldName + "]", box)
            .attr("name", newName).text(config[0]);
        options.buttons[newName] = config;
        config[1] && button.removeClass().addClass("thinkbox-button-" + config[1]);
        if(!$.isFunction(config[2])){config[2] = $.noop};
        return this;
    },

    /* Uninstall a button */
    "removeToolsButton" : function(name){
        $(".thinkbox-tools", lists[this.key][1]).find("button[name='" + name + "']").remove();
        return this;
    },

    /* Disabling a button */
    "disableToolsButton" : function(name){
        $(".thinkbox-tools", lists[this.key][1]).find("button[name='" + name + "']")
            .addClass("disabled").attr("disabled", "disabled");
        return this;
    },

    /* Enabling a button */
    "enableToolsButton" : function(name){
        $(".thinkbox-tools", lists[this.key][1]).find("button[name='" + name + "']")
            .removeClass("disabled").removeAttr("disabled", "disabled");
        return this;
    },

    /* Minimize the popup */
    "minimize" : function(){
        return this;
    },

    /* Maximize the popup */
    "maximize" : function(){
        return this;
    }
}

/* pressESCClose the popup */
_doc.mousedown(function(){lists.current = null})
    .keydown(function(event){
        lists.current
        && lists[lists.current][0].escHide
        && event.keyCode == 27
        && lists[lists.current][1].data("thinkbox").hide();
    });

/**
 * CreatenewofPop-layer objects
 +----------------------------------------------------------
 * element The popup content elements
 * options Pop-up layer options
 +----------------------------------------------------------
 */
$.thinkbox = function(element, options){
    if($.isPlainObject(options) && options.dataEle){
        var data = $(options.dataEle).data("thinkbox");
        if(data) return options.display === false ? data : data.show();
    }
    return new ThinkBox(element, options);
}

/**
 +----------------------------------------------------------
 * Built-in pop-layer expansion
 +----------------------------------------------------------
 */
$.extend($.thinkbox, {
    /**
     * Set upPop-up layerThe default parameters
     * @param  {string} name  Configuration Name
     * @param  {string} value Configured value
     */
    "defaults" : function(name, value){
        if($.isPlainObject(name)){
            $.extend(defaults, name);
        } else {
            defaults[name] = value;
        }
    },

    // To aURLAnd to load contentThinBoxShow a pop-up layer
    "load" : function(url, opt){
        var options = {
            "clone"     : false,
            "loading"   : "Loading...",
            "type"      : "GET",
            "dataType"  : "text",
            "cache"     : false,
            "onload"    : undefined
        }, self, ajax, onload, loading, url = url.split(/\s+/);
        $.extend(options, opt || {}); //mergeConfigurationitem

        //Save some parameters
        onload    = options.onload;
        loading   = options.loading;

        //AssemblyAJAXRequest parameter
        ajax = {
            "data"     : options.data,
            "type"     : options.type,
            "dataType" : options.dataType,
            "cache"    : options.cache,
            "success"  : function(data) {
                url[1] && (data = $(data).find(url[1]));
                if($.isFunction(onload))
                    data = fire.call(self, onload, data); //transferonloadCallback
                self.setContent(data); //Set contentandDisplay pop-up layer
                loading || self.show(); //NoloadingstatusthendirectDisplay pop-up layer
            }
        };

        //deleteThinkBox不needofparameter
        del(["data", "type", "cache", "dataType", "onload", "loading"], options);

        self = loading ?
            //displayloadinginformation
            $.thinkbox("<div class=\"thinkbox-load-loading\">" + loading + "</div>", options) :
            //Do not showloadinginformationCreateRearDo not showPop-up layer
            $.thinkbox("<div/>", $.extend({}, options, {"display" : false}));

        $.ajax(url[0], ajax);
        return self;
    },

    // A popiframe
    "iframe" : function(url, opt){
        var options = {
            "width"     : 500,
            "height"    : 400,
            "scrolling" : "no",
            "onload"    : undefined
        }, self, iframe, onload;
        $.extend(options, opt || {}); //mergeConfigurationitem
        onload = options.onload; //Set uploadcarry outAfterCallbackmethod
        //createiframe
        iframe = $("<iframe/>").attr({
            "width"       : options.width,
            "height"      : options.height,
            "frameborder" : 0,
            "scrolling"   : options.scrolling,
            "src"         : url})
            .load(function(){fire.call(self, onload)});
        del(["width", "height", "scrolling", "onload"], options);//Remove unnecessary information
        self = $.thinkbox(iframe, options);
        return self;
    },

    // Tip box You can meetThinkPHPofajaxReturn
    "tips" : function(msg, type, opt){
        var options = {
            "modalClose" : false,
            "escHide"    : false,
            "unload"     : true,
            "close"      : false,
            "delayClose" : 1000
        }, html;

        //digitaltypeInto a stringtype
        switch(type){
            case 0: type = "error"; break;
            case 1: type = "success"; break;
        }
        html = "<div class=\"thinkbox-tips thinkbox-tips-" + type + "\">" + msg + "</div>";
        $.extend(options, opt || {});
        return $.thinkbox(html, options);
    },

    // Success Tip box
    "success" : function(msg, opt){
        return this.tips(msg, "success", opt);
    },

    // Error message box
    "error" : function(msg, opt){
        return this.tips(msg, "error", opt);
    },

    // Loading
    "loading" : function(msg, opt){
        var options = opt || {};
        options.delayClose = 0;
        return this.tips(msg, "loading", options);
    },

    //Message Box
    "msg" : function(msg, opt){
        var options = {
            "drag"       : false,
            "escHide"    : false,
            "delayClose" : 0,
            "center"     : false,
            "title"      : "news",
            "x"          : 0,
            "y"          : 0,
            "locate"     : ["right", "bottom"],
            "show"       : ["slideDown", "slow"],
            "hide"       : ["slideUp", "slow"]
        }, html;
        $.extend(options, opt || {});
        html = $("<div/>").addClass("thinkbox-msg").html(msg);
        return $.thinkbox(html, options);
    },

    //Tip box
    "alert" : function(msg, opt){
        var options = {
                "title"      : lang.alert || "Alert",
                "modal"      : true,
                "modalClose" : false,
                "unload"     : false,
                "tools"      : true,
                "actions"    : ["close"],
                "buttons"    : {"ok" : [lang.ok || "Ok", "blue", function(){this.hide()}]}
            };

        $.extend(options, opt || {});

        //deleteThinkBox不needofparameter
        del("ok", options);

        var html = $("<div/>").addClass("thinkbox-alert").html(msg);
        return $.thinkbox(html, options);
    },

    //Confirmation box
    "confirm" : function(msg, opt){
        var options = {"title" : "Confirm", "modal" : false, "modalClose" : false},
            button  = {"ok" : "Confirm", "cancel" : "Cancel"};
        $.extend(options, opt || {});
        options.ok && (button.ok = options.ok);
        options.cancel && (button.cancel = options.cancel);

        //deleteThinkBox不needofparameter
        del(["ok", "cancel"], options);

        options.buttons = button;
        var html = $("<div/>").addClass("thinkbox-confirm").html(msg);
        return $.thinkbox(html, options);
    },

    //Pop-up layerinternalObtainPop-layer objects
    "get" : function(selector){
        //TODO:byPop-upsinternalelementFind
        return $(selector).closest(".thinkbox").data("thinkbox");
    }
});

$.fn.thinkbox = function(opt){
    if(opt == "get") return $(this).data("thinkbox");
    return this.each(function(){
        var self = $(this), box = self.data("thinkbox"), options, event;
        switch(opt){
            case "show":
                box && box.show();
                break;
            case "hide":
                box && box.hide();
                break;
            case "toggle":
                box && box.toggle();
                break;
            default:
                options = {
                    "title"   : self.attr("title"),
                    "dataEle" : this,
                    "fixed"   : false,
                    "center"  : false,
                    "modal"   : false,
                    "drag"    : false
                };
                opt = $.isPlainObject(opt) ? opt : {};
                $.extend(options, {
                    "x" : function(){return $(this).offset().left},
                    "y" : function(){return $(this).offset().top + $(this).outerHeight()}
                }, opt);
                if(options.event){
                    self.on(event, function(){
                        _.call(self, options);
                        return false;
                    });
                } else {
                    _.call(self, options);
                }
        }
    });

    function _(options){
        var href = this.data("href") || this.attr("href");
        if(href.substr(0, 1) == "#"){
            $.thinkbox(href, options);
        } else if(href.substr(0, 7) == "http://" || href.substr(0, 8) == "https://"){
            $.thinkbox.iframe(href, options);
        } else {
            $.thinkbox.load(href, options);
        }
    }
}

})(jQuery);
