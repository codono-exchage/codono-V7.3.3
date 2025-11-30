//ThinkTemplate usejsAchievedThinkPHPThe template engine.
//userallowableMobile client withThinkPHPThe template engine.
//@author luofei614<http://weibo.com/luofei614>
//
var ThinkTemplate={
    tags:['Include','Volist','Foreach','For','Empty','Notempty','Present','Notpresent','Compare','If','Elseif','Else','Swith','Case','Default','Var','Range'],
	parse:function(tplContent,vars){
	var render=function(){
		tplContent='<% var key,mod=0;%>'+tplContent;//definitiontemplateIn circulationNeed to useofTovariable	
        $.each(ThinkTemplate.tags,function(k,v){
            tplContent=ThinkTemplate['parse'+v](tplContent);
        });  
		return ThinkTemplate.template(tplContent,vars);
		};
		
		return render();
	},
	//Resolve <% %> label
	template:function(text,vars){
		var source="";
		var index=0;
		var escapes = {
			"'":      "'",
			'\\':     '\\',
			'\r':     'r',
			'\n':     'n',
			'\t':     't',
			'\u2028': 'u2028',
			'\u2029': 'u2029'
		};
		var escaper = /\\|'|\r|\n|\t|\u2028|\u2029/g;
		text.replace(/<%=([\s\S]+?)%>|<%([\s\S]+?)%>/g,function(match,interpolate,evaluate,offset){
			var p=text.slice(index,offset).replace(escaper,function(match){
				return '\\'+escapes[match];
			});
			if(''!=$.trim(p)){
				source+="__p+='"+p+"';\n";	
			}

			if(evaluate){
				source+=evaluate+"\n";
			}	
			if(interpolate){
				source+="if( 'undefined'!=typeof("+interpolate+") && (__t=(" + interpolate + "))!=null) __p+=__t;\n";
			}
			index=offset+match.length;
			return match;
		});
		source+="__p+='"+text.slice(index).replace(escaper,function(match){ return '\\'+escapes[match]; })+"';\n";//The remaining splice string

		source = "var __t,__p='',__j=Array.prototype.join," +
			"print=function(){__p+=__j.call(arguments,'');};\n" +
			"with(obj){\n"+
			source + 
			"}\n"+
			"return __p;\n";
		try {
			render = new Function('obj', source);

		} catch (e) {
			e.source = source;
			throw e;
		}
		return render(vars);
	},
	parseVar:function(tplContent){
		var matcher=/\{\$(.*?)\}/g
			return tplContent.replace(matcher,function(match,varname,offset){
				//Support define default values
				if(varname.indexOf('|')!=-1){
					var arr=varname.split('|');
					var name=arr[0];
					var defaultvalue='""';
					arr[1].replace(/default=(.*?)$/ig,function(m,v,o){
						defaultvalue=v;
					});
					return '<% '+name+'?print('+name+'):print('+defaultvalue+');  %>';
				}
				return '<%='+varname+'%>';
			});	
	},
    //includeTag resolution pathneedWrite all，writefor Action:method, It does not support variables. 
    parseInclude:function(tplContent){
		var include=/<include (.*?)\/?>/ig;
        tplContent=tplContent.replace(include,function(m,v,o){
            var $think=$('<think '+v+' />');
            var file=$think.attr('file').replace(':','/')+'.html';
            var content='';
            //Load Template
            $.ajax({
                dataType:'text',
                url:file,
                cache:false,
                async:false,//Synchronous request
                success:function(d,s,x){
                    content=d;
                },
                error:function(){
                    //pass
                }
            });
            return content;
        });
        tplContent=tplContent.replace('</include>','');//compatibleBrowseReactorelementautomaticclosureof情况
        return tplContent;
    },
	//volistTag resolution
	parseVolist:function(tplContent){
		var voliststart=/<volist (.*?)>/ig;
		var volistend=/<\/volist>/ig;
		//ResolvevolistStart tag
		tplContent=tplContent.replace(voliststart,function(m,v,o){
			//Attribute analysis
			var $think=$('<think '+v+' />');
			var name=$think.attr('name');
			var id=$think.attr('id');
			var empty=$think.attr('empty')||'';
			var key=$think.attr('key')||'i';	
			var mod=$think.attr('mod')||'2';
			//Replace the code
			return '<% if("undefined"==typeof('+name+') || ThinkTemplate.empty('+name+')){'+
				' print(\''+empty+'\');'+
			' }else{ '+
				key+'=0;'+
			' $.each('+name+',function(key,'+id+'){'+
				' mod='+key+'%'+mod+';'+
				' ++'+key+';'+
				' %>';
			});
		//ResolvevolistEnd tag
		tplContent=tplContent.replace(volistend,'<% }); } %>');
		return tplContent;
	},
	//Resolveforeachlabel
	parseForeach:function(tplContent){
		var foreachstart=/<foreach (.*?)>/ig;
		var foreachend=/<\/foreach>/i;	
		tplContent=tplContent.replace(foreachstart,function(m,v,o){
			var $think=$('<think '+v+' />');	
			var name=$think.attr('name');
			var item=$think.attr('item');
			var key=$think.attr('key')||'key';
			return '<% $.each('+name+',function('+key+','+item+'){  %>'
			});
			tplContent=tplContent.replace(foreachend,'<% }); %>');
		return tplContent;
	},
	parseFor:function(tplContent){
		var forstart=/<for (.*?)>/ig;
		var forend=/<\/for>/ig;
		tplContent=tplContent.replace(forstart,function(m,v,o){
			var $think=$('<think '+v+' />');	
			var name=$think.attr('name') || 'i';
			var comparison=$think.attr('comparison') || 'lt';
			var start=$think.attr('start') || '0';
			if('$'==start.substr(0,1)){
				start=start.substr(1);
			}
			var end=$think.attr('end') || '0';
			if('$'==end.substr(0,1)){
				end=end.substr(1);
			}
			var step=$think.attr('step') || '1';
			if('$'==step.substr(0,1)){
				step=step.substr(1);	
			}
			return '<% for(var '+name+'='+start+';'+ThinkTemplate.parseCondition(name+comparison+end)+';i=i+'+step+'){  %>'
			});
		tplContent=tplContent.replace(forend,'<% } %>');
		return tplContent;
	},
	//emptylabel
	parseEmpty:function(tplContent){
		var	emptystart=/<empty (.*?)>/ig;
		var emptyend=/<\/empty>/ig;
		tplContent=tplContent.replace(emptystart,function(m,v,o){
			var name=$('<think '+v+' />').attr('name');
			return '<% if("undefined"==typeof('+name+') || ThinkTemplate.empty('+name+')){ %>';
			});
		tplContent=tplContent.replace(emptyend,'<% } %>');
		return tplContent;
	},
	//notempty Tag resolution
	parseNotempty:function(tplContent){
		var	notemptystart=/<notempty (.*?)>/ig;
		var notemptyend=/<\/notempty>/ig;
		tplContent=tplContent.replace(notemptystart,function(m,v,o){
			var name=$('<think '+v+' />').attr('name');
			return '<% if("undefined"!=typeof('+name+') && !ThinkTemplate.empty('+name+')){ %>';
			});
		tplContent=tplContent.replace(notemptyend,'<% } %>');
		return tplContent;
	},
	//presentTag resolution
	parsePresent:function(tplContent){
		var	presentstart=/<present (.*?)>/ig;
		var presentend=/<\/present>/ig;
		tplContent=tplContent.replace(presentstart,function(m,v,o){
			var name=$('<think '+v+' />').attr('name');
			return '<% if("undefined"!=typeof('+name+')){ %>';
			});
		tplContent=tplContent.replace(presentend,'<% } %>');
		return tplContent;
	},
	//notpresent Tag resolution
	parseNotpresent:function(tplContent){
		var	notpresentstart=/<notpresent (.*?)>/ig;
		var notpresentend=/<\/notpresent>/ig;
		tplContent=tplContent.replace(notpresentstart,function(m,v,o){
			var name=$('<think '+v+' />').attr('name');
			return '<% if("undefined"==typeof('+name+')){ %>';
			});
		tplContent=tplContent.replace(notpresentend,'<% } %>');
		return tplContent;
	},
	parseCompare:function(tplContent){
		var compares={
			"compare":"==",
			"eq":"==",
			"neq":"!=",
			"heq":"===",
			"nheq":"!==",
			"egt":">=",
			"gt":">",
			"elt":"<=",
			"lt":"<"
		};	
		$.each(compares,function(type,sign){
			var start=new RegExp('<'+type+' (.*?)>','ig');
			var end=new RegExp('</'+type+'>','ig');
			tplContent=tplContent.replace(start,function(m,v,o){
				var	$think=$('<think '+v+' />');
				var name=$think.attr('name');
				var value=$think.attr('value');
				if("compare"==type && $think.attr('type')){
					sign=compares[$think.attr('type')];
				}
				if('$'==value.substr(0,1)){
					//valueSupport variables
					value=value.substr(1);	
				}else{
					value='"'+value+'"';
				}
				return '<% if('+name+sign+value+'){  %>';
				});
			tplContent=tplContent.replace(end,'<% } %>');

		});
		return tplContent;
	},
	//Resolveiflabel
	parseIf:function(tplContent){
		var ifstart=/<if (.*?)>/ig;
		var ifend=/<\/if>/ig;
		tplContent=tplContent.replace(ifstart,function(m,v,o){
			var condition=$('<think '+v+' />').attr('condition');	
			return '<% if('+ThinkTemplate.parseCondition(condition)+'){ %>';
			});
		tplContent=tplContent.replace(ifend,'<% } %>');
		return tplContent;
	},
	//Resolveelseif
	parseElseif:function(tplContent){
		var elseif=/<elseif (.*?)\/?>/ig;
		tplContent=tplContent.replace(elseif,function(m,v,o){
			var condition=$('<think '+v+'  />').attr('condition');
			return '<% }else if('+ThinkTemplate.parseCondition(condition)+'){ %>';
			});
        tplContent=tplContent.replace('</elseif>','');
		return tplContent;
	},
	//Resolveelselabel
	parseElse:function(tplContent){
		    var el=/<else\s*\/?>/ig	
			tplContent=tplContent.replace(el,'<% }else{ %>');
            tplContent=tplContent.replace('</else>','');
            return tplContent;
			},
	//Resolveswithlabel
	parseSwith:function(tplContent){
		var switchstart=/<switch (.*?)>(\s*)/ig;	
		var switchend=/<\/switch>/ig;
		tplContent=tplContent.replace(switchstart,function(m,v,s,o){
			var name=$('<think '+v+' >').attr('name');	
			return '<% switch('+name+'){ %>';
			});
		tplContent=tplContent.replace(switchend,'<% } %>');
		return tplContent;
	},
	//Resolvecaselabel
	parseCase:function(tplContent){
		var casestart=/<case (.*?)>/ig;	
		var caseend=/<\/case>/ig;
		var breakstr='';
		tplContent=tplContent.replace(casestart,function(m,v,o){
			var $think=$('<think '+v+'  />');
			var value=$think.attr('value');
			if('$'==value.substr(0,1)){
				value=value.substr(1);
			}else{
				value='"'+value+'"';
			}
			if('false'!=$think.attr('break')){
				breakstr='<% break; %> ';
			}
			return '<% case '+value+':  %>';
		});
		tplContent=tplContent.replace(caseend,breakstr);
		return tplContent;
	},
	//Resolvedefaultlabel
	parseDefault:function(tplContent){
		var defaulttag=/<default\s*\/?>/ig;	
		tplContent=tplContent.replace(defaulttag,'<% default: %>');
        tplContent=tplContent.replace('</default>','');
		return tplContent;
	},
	//Resolvein,notin,between,notbetween label
	parseRange:function(tplContent){
		var ranges=['in','notin','between','notbetween'];
		$.each(ranges,function(k,tag){
			var start=new RegExp('<'+tag+' (.*?)>','ig');
			var end=new RegExp('</'+tag+'>','ig');
			tplContent=tplContent.replace(start,function(m,v,o){
				var	$think=$('<think '+v+' />');
				var name=$think.attr('name');
				var value=$think.attr('value');
				if('$'==value.substr(0,1)){
					value=value.substr(1);
				}else{
					value='"'+value+'"';
				}
				switch(tag){
					case "in":
						var condition='ThinkTemplate.inArray('+name+','+value+')';	
							break;
							case "notin":
							var condition='!ThinkTemplate.inArray('+name+','+value+')';	
								break;
								case "between":
								var condition=name+'>='+value+'[0] && '+name+'<='+value+'[1]';
								break;
								case "notbetween":
								var condition=name+'<'+value+'[0] || '+name+'>'+value+'[1]';
								break;
								}
								return '<% if('+condition+'){ %>'
								});
							tplContent=tplContent.replace(end,'<% } %>')
							});
						return tplContent;
	},
    //Spread
    extend:function(name,cb){
        name=name.substr(0,1).toUpperCase()+name.substr(1);
        this.tags.push(name);
        this['parse'+name]=cb;
    },
	//Determine whetherinArrayin，stand byjudgmentobjectTypes of data
	inArray:function(name,value){
		if('string'==$.type(value)){
			value=value.split(',');
		}
		var ret=false;
		$.each(value,function(k,v){
			if(v==name){
				ret=true;
				return false;
			}	
		});
		return ret;
	},
	empty:function(data){
		if(!data)
			return true;
		if('array'==$.type(data) && 0==data.length)
			return true;
		if('object'==$.type(data) && 0==Object.keys(data).length)
			return true;
		return false;
	},
	parseCondition:function(condition){
		var conditions={
			"eq":"==",
			"neq":"!=",
			"heq":"===",
			"nheq":"!==",
			"egt":">=",
			"gt":">",
			"elt":"<=",
			"lt":"<",
			"or":"||",
			"and":"&&",
			"\\$":""
		};		
		$.each(conditions,function(k,v){
			var matcher=new RegExp(k,'ig');	
			condition=condition.replace(matcher,v);
		});
		return condition;
	}


};

//TPMobiframe
//ImplementedThinkPHPDo mobile client
//@author luofei614<http://weibo.com/luofei614>
var TPM={
	op:{
		api_base:'',//interfacebaseaddress，Do not end with a slash
		api_index:'/Index/index',//HomeRequest address
		main:"main",//The main layerID
		routes:{}, //routing,Support parameters such as:id Support wildcard*
		error_handle:false,//To take over the function error
        _before:[],
		_ready:[],//UICallback collection
        single:true,//singleEntrancemode

		ajax_wait:".ajax_wait",//Loading layer selector
		ajax_timeout:15000,//ajaxRequest timeout
		ajax_data_type:'',//Request Interface Type Such asjson，jsonp
		ajax_jsonp_callback:'callback',//jsonp transferofCallbackparameter名词

		before_request_api:false,//Before the request interfacehook
        //After the request interfacehook,deal withTPofsuccesswitherror
		after_request_api:function(data,url){
            if(data.info){
                TPM.info(data.info,function(){
                  if(data.url){
                            TPM.http(data.url);
                        }else if(1==data.status){
                            //in casesuccess， Refresh Data  
                            TPM.reload(TPM.op.main);
                   }
                });
                return false;
            }
        },

        anchor_move_speed:500, //Moving speed of the anchor

		tpl_path_var:'_think_template_path',//Interface specified template

		tpl_parse_string:{
            '../Public':'./Public'
        },//Template substitution variables

        //Specified interface requestheader
		headers:{
            'client':'PhoneClient',
            //Cross-domainrequestTime，Will not takeX-Requested-with ofheader，Will leadservicerecognizeforNoajaxrequest，and sosuchManuallyAdd thisheader 。
            'X-Requested-With':'XMLHttpRequest'
        },

		tpl:ThinkTemplate.parse//Template engine

	},
	config:function(options){
		$.extend(this.op,options);
	},
	ready:function(fun){
		this.op._ready.push(fun);
	},
    before:function(fun){
        this.op._before.push(fun);
    },
	//Output error
	error:function(errno,msg){
        TPM.alert('error['+errno+']：'+msg);
	},
    info:function(msg,cb){
        if('undefined'==typeof(tpm_info)){
            alert(msg);
            if($.isFunction(cb)) cb();
        }else{
            tpm_info(msg,cb);
        }
     },
    alert:function(msg,cb,title){
        if('undefined'==typeof(tpm_alert)){
            alert(msg);
            if($.isFunction(cb)) cb();
        }else{
            tpm_alert(msg,cb,title);
        }    
    },
    //initializationrun
	run:function(options,vars){
		if(!this.defined(window.jQuery) && !this.defined(window.Zepto)){
			this.error('-1','Please loadjqueryorzepto');
			return ;
		}
        //If onlyapi_base You can pass onlyOneString。
        if('string'==$.type(options)){
            options={api_base:options};
        }
		//Configurationdeal with
		options=options||{};
		this.config(options);
		$.ajaxSetup({
			error:this.ajaxError,
			timeout:this.op.ajax_timeout || 5000,
			cache:false,
			headers:this.op.headers
		});
		var _self=this;
		//ajaxLoad state
        window.TPMshowAjaxWait=true;
		$(document).ajaxStart(function(){
            //In the program can be setTPMshowAjaxWaitforfalse,terminationdisplayWait layer. 
			if(window.TPMshowAjaxWait) $(_self.op.ajax_wait).show();
		}
		).ajaxStop(function(){
			$(_self.op.ajax_wait).hide();
		});
		$(document).ready(function(){
            //Tag resolution
            vars=vars||{};
            var render=function(vars){
                var tplcontent=$('body').html();
                tplcontent=tplcontent.replace(/&lt;%/g,'<%');
                tplcontent=tplcontent.replace(/%&gt;/g,'%>');
                var html=_self.parseTpl(tplcontent,vars);
                $('body').html(html);
                if(!_self.op.single){

                    $.each(_self.op._ready,function(k,fun){
                        fun($);
                    });
               }
            }
            if('string'==$.type(vars)){
                _self.sendAjax(vars,{},'get',function(response){
                     render(response);
                });
            }else{
                render(vars);
            }

                      
            if(_self.op.single){
                //singleEntrancemode
                _self.initUI(document);
                var api_url=''!=location.hash?location.hash.substr(1):_self.op.api_index;
                _self.op._old_hash=location.hash;
                _self.http(api_url);	
                //monitorhashVariety
                var listenHashChange=function(){
                    if(location.hash!=_self.op._old_hash){
                        var api_url=''!=location.hash?location.hash.substr(1):_self.op.api_index;
                        _self.http(api_url);
                    }
                    setTimeout(listenHashChange,50);
                }
                listenHashChange();
            }
		});
	},
	//initializationinterface
	initUI:function(_box){
       //transferfromdefinitionloadcarry outAfterUIHandler,fromdefinitioneventPrior to bindingsystemBinding，able to controlsystemBindingfunctionoftrigger。 
        var selector=function(obj){
                var $obj=$(obj,_box)
				return $obj.size()>0?$obj:$(obj);
			};
    
        $.each(this.op._before,function(k,fun){
			fun(selector);
		})

		var _self=this;
		//Alabel， SlashStartofaddressWillmonitor，Or willdirectturn on
		$('a[href^="/"],a[href^="./"]',_box).click(function(e){
            if(false===e.result)  return ; //in casefromdefinitioneventreturn falseThe， No longer pointrequestoperating
			e.preventDefault();
            //If there istplProperty, the light request template
            var url=$(this).attr('href');
            if(undefined!==$(this).attr('tpl')){
                url='.'+url+'.html';
            }
			//absoluteaddressoflinkbutfilter
			_self.http(url,$(this).attr('rel'));
		});
		//formProcessing tags
		$('form[action^="/"],form[action^="./"]',_box).submit(function(e){
            if(false===e.result)  return ; //in casefromdefinitioneventreturn falseThe， No longer pointrequestoperating
			e.preventDefault();
            var url=$(this).attr('action');
            if(undefined!==$(this).attr('tpl')){
                url='.'+url+'.html';
            }
			_self.http(url,$(this).attr('rel'),$(this).serializeArray(),$(this).attr('method'));
		});
		//Anchor handling
		$('a[href^="#"]',_box).click(function(e){
			e.preventDefault();
			var anchor=$(this).attr('href').substr(1);
			if($('#'+anchor).size()>0){
				_self.scrollTop($('#'+anchor),_self.op.anchor_move_speed);	
			}else if($('a[name="'+anchor+'"]').size()>0){
				_self.scrollTop($('a[name="'+anchor+'"]'),_self.op.anchor_move_speed);
			}else{
				_self.scrollTop(0,_self.op.anchor_move_speed);
			}
		});
       
        $.each(this.op._ready,function(k,fun){
			fun(selector);
		})

	},
	//requestinterface， Support for:1, requestinterfaceAt the same time renderingtemplate 2,onlyrequesttemplate不requestinterface 3,onlyrequestinterfaceNot renderingtemplate， If there isMore complexofLogic can seal themselvesfunction,ToneTPM.sendAjax, TPM.render。
	http:function(url,rel,data,type){
		rel=rel||this.op.main;
		type=type || 'get';
		//analysisurl,in case./To begin direct request template
		if('./'==url.substr(0,2)){
			this.render(url,rel);	
			$('#'+rel).data('url',url);

			if(this.op.main==rel && 'get'==type.toLowerCase()) this.changeHash(url);
			return ;
		}
		//analysisTemplate Address
		var tpl_path=this.route(url);
		//changehash
		if(tpl_path && this.op.main==rel && 'get'==type.toLowerCase()) this.changeHash(url);
		//ajaxrequest
		var _self=this;
		this.sendAjax(url,data,type,function(response){
			if(!tpl_path && _self.defined(response[_self.op.tpl_path_var])){
				tpl_path=response[_self.op.tpl_path_var]; //You can specify the interface template	
		        //changehash
		        if(tpl_path && _self.op.main==rel && 'get'==type.toLowerCase()) _self.changeHash(url);
			}
			if(!tpl_path){
				//in caseNotemplate，defaultonlyrequestajaxAfter the request to refreshrel
				if('false'!=rel.toLowerCase()) _self.reload(rel);
			}else{
				//Template Rendering
				_self.render(tpl_path,rel,response);
				$('#'+rel).data('url',url);	
			}
		});
	},
	sendAjax:function(url,data,type,cb,async,options){
		var _self=this;
		data=data||{};
		type=type||'get';
		options=options||{};

		api_options=$.extend({},_self.op,options);
        if(false!==async){
            async==true;
        }
		//Before interface requestshook(Can be used as a signature)
		if($.isFunction(api_options.before_request_api)) 
			data=api_options.before_request_api(data,url);
		//ajaxrequest
        //TODO ,WithhttpThe beginning ofurl,do not addapi_base
		var api_url=api_options.api_base+url;
		
		$.ajax(
				{
				type: type,
				url: api_url,
				data: data,
				dataType:api_options.ajax_data_type||'',
				jsonp:api_options.ajax_jsonp_callback|| 'callback',
                async:async,
				success: function(d,s,x){
                       if(redirect=x.getResponseHeader('redirect')){
                           //Jump
                           if(api_options.single) _self.http(redirect);
                           return ;
                        }
						//Interface Data Analysis
						try{
							var response='object'==$.type(d)?d:$.parseJSON(d);
						}catch(e){
							_self.error('-2','Interface Data format error is returned');
							return ;
						}
						//After the interface requestshook
						if($.isFunction(api_options.after_request_api)){
							var hook_result=api_options.after_request_api(response,url);
							if(undefined!=hook_result){
								response=hook_result;
							}
						}
						if(false!=response && $.isFunction(cb))
							cb(response); 
					}
				}
		);
	},
	changeHash:function(url){
		if(url!=this.op.api_index){
			this.op._old_hash='#'+url;
			location.hash=url;
		}else{
			if(''!=this.op._old_hash) this.op._old_hash=this.isIE()?'#':'';//IEIf tracing point# Get Value empty
			if(''!=location.hash) location.hash='';//赋valueforIn fact EmptyBrowseWill assignfor #
		}	
	},
	//Rendertemplate
	render:function(tpl_path,rel,vars){
		vars=vars||{};
		var _self=this;
		$.get(tpl_path,function(d,x,s){
			//templateResolve
			var content=_self.parseTpl(d,vars);
			//Parsing template substitution variables
			$.each(_self.op.tpl_parse_string,function(find,replace){
				var matcher=new RegExp(find.replace(/[-[\]{}()+?.,\\^$|#\s]/g,'\\$&'),'g');	
				content=content.replace(matcher,replace);
			});
			//Separatejs
			var ret=_self.stripScripts(content);
			var html=ret.text;
			var js=ret.scripts;
			$('#'+rel).empty().append(html);
			_self.initUI($('#'+rel));
			//Page executionjs
			_self.execScript(js,$('#'+rel));

		},'text');	
	},
	//Reload the zone content
	reload:function(rel){
		var url=$('#'+rel).data('url');
		if(url){
			this.http(url,rel);
		}
	},
	//Routing resolution
	route:function(url){
		var tpl_path=false;
		var _self=this;
		$.each(this.op.routes,function(route,path){
			if(_self._routeToRegExp(route).test(url)){
				tpl_path=path;
				return false;
			}
		});
		return tpl_path;	
	},
	_routeToRegExp: function(route) {
		var namedParam    = /:\w+/g;
		var splatParam    = /\*\w+/g;
		var escapeRegExp  = /[-[\]{}()+?.,\\^$|#\s]/g;
		route = route.replace(escapeRegExp, '\\$&')
			.replace(namedParam, '([^\/]+)')
			.replace(splatParam, '(.*?)');
		return new RegExp('^' + route + '$');
	},
	//templateResolve
	parseTpl:function(tplContent,vars){
		return this.op.tpl(tplContent,vars);
	},
	ajaxError: function(xhr, ajaxOptions, thrownError)
	{
        window.TPMshowAjaxWait=true;
        TPM.info('network anomaly');
	},

	
	//------Utilities
	//Determine whetherIE
	isIE:function(){
		return /msie [\w.]+/.exec(navigator.userAgent.toLowerCase());
	},
	//Determine whetherIE7The following browsers
	isOldIE:function(){
		return this.isIE() && (!docMode || docMode <= 7);
	},
	//Moving the scroll bar,nIt can bedigitaland alsoIt can beObjects
	scrollTop:function(n,t,obj){
		t=t||0;
        obj=obj ||'html,body'
		num=$.type(n)!="number"?n.offset().top:n;
		$(obj).animate( {
			scrollTop: num
		}, t );
	},
	//SeparatejsCode	
	stripScripts:function(codes){
		var scripts = '';
		//Remove the stringscriptlabel， And access toscriptContent tab.
		var text = codes.replace(/<script[^>]*>([\s\S]*?)<\/script>/gi, function(all, code){
			scripts += code + '\n';
			return '';
		});
		return {text:text,scripts:scripts}
	},
	//carried outjsCode
	execScript:function(scripts,_box){
		if(scripts!=''){
			//carried outjsCode, Performed in a closure. change$Selectors. 
			var e=new Function('$',scripts);
			var selector=function(obj){
                var $obj=$(obj,_box)
				return $obj.size()>0?$obj:$(obj);
			};
			e(selector);

		}
	},
	//Determine whether the variable definitions
	defined:function(variable){
		return $.type(variable) == "undefined" ? false : true;	
	},
    //obtaingetparameter
    get:function(name){
            if('undefined'==$.type(this._gets)){
                var querystring=window.location.search.substring(1);
                var gets={};
                var vars=querystring.split('&')
                var param;
                for(var i=0;i<vars.length;i++){
                    param=vars[i].split('=');
                    gets[param[0]]=param[1];
                } 
                this._gets=gets;
            }
            return this._gets[name];
    }

};





