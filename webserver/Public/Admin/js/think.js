/* SpreadCodonoObjects */
(function($){
	/**
	 * ObtainCodonoBasic Configuration
	 * @type {object}
	 */
	var Codono = window.Think;

	/* Base object detection */
	Codono || $.error("Config Issue!");

	/**
	 * ResolveURL
	 * @param  {string} url It is resolvedURL
	 * @return {object}     Parsed data
	 */
	Codono.parse_url = function(url){
		var parse = url.match(/^(?:([a-z]+):\/\/)?([\w-]+(?:\.[\w-]+)+)?(?::(\d+))?([\w-\/]+)?(?:\?((?:\w+=[^#&=\/]*)?(?:&\w+=[^#&=\/]*)*))?(?:#([\w-]+))?$/i);
		parse || $.error("urlFormat is not correct!");
		return {
			"scheme"   : parse[1],
			"host"     : parse[2],
			"port"     : parse[3],
			"path"     : parse[4],
			"query"    : parse[5],
			"fragment" : parse[6]
		};
	}

	Codono.parse_str = function(str){
		var value = str.split("&"), vars = {}, param;
		for(val in value){
			param = value[val].split("=");
			vars[param[0]] = param[1];
		}
		return vars;
	}

	Codono.parse_name = function(name, type){
		if(type){
			/* Underline turn hump */
			name.replace(/_([a-z])/g, function($0, $1){
				return $1.toUpperCase();
			});

			/* Capitalized */
			name.replace(/[a-z]/, function($0){
				return $0.toUpperCase();
			});
		} else {
			/* Capital to small letter */
			name = name.replace(/[A-Z]/g, function($0){
				return "_" + $0.toLowerCase();
			});

			/* RemovefirstcharacterofUnderline */
			if(0 === name.indexOf("_")){
				name = name.substr(1);
			}
		}
		return name;
	}

	//scheme://host:port/path?query#fragment
	Codono.U = function(url, vars, suffix){
		var info = this.parse_url(url), path = [], param = {}, reg;

		/* verificationinfo */
		info.path || $.error("urlwrong format!");
		url = info.path;

		/* AssemblyURL */
		if(0 === url.indexOf("/")){ //Routing mode
			this.MODEL[0] == 0 && $.error("ThatURLMode does not support the use of routing!(" + url + ")");

			/* Removing the right delimiter */
			if("/" == url.substr(-1)){
				url = url.substr(0, url.length -1)
			}
			url = ("/" == this.DEEP) ? url.substr(1) : url.substr(1).replace(/\//g, this.DEEP);
			url = "/" + url;
		} else { //Non-routing mode
			/* ResolveURL */
			path = url.split("/");
			path = [path.pop(), path.pop(), path.pop()].reverse();
			path[1] || $.error("Codono.U(" + url + ")Not specified controller");

			if(path[0]){
				param[this.VAR[0]] = this.MODEL[1] ? path[0].toLowerCase() : path[0];
			}

			param[this.VAR[1]] = this.MODEL[1] ? this.parse_name(path[1]) : path[1];
			param[this.VAR[2]] = path[2].toLowerCase();

			url = "?" + $.param(param);
		}

		/* Analytical parameters */
		if(typeof vars === "string"){
			vars = this.parse_str(vars);
		} else if(!$.isPlainObject(vars)){
			vars = {};
		}

		/* ResolveURLBuilt-in parameters */
		info.query && $.extend(vars, this.parse_str(info.query));

		if(vars){
			url += "&" + $.param(vars);
		}

		if(0 != this.MODEL[0]){
			url = url.replace("?" + (path[0] ? this.VAR[0] : this.VAR[1]) + "=", "/")
				     .replace("&" + this.VAR[1] + "=", this.DEEP)
				     .replace("&" + this.VAR[2] + "=", this.DEEP)
				     .replace(/(\w+=&)|(&?\w+=$)/g, "")
				     .replace(/[&=]/g, this.DEEP);

			/* Add pseudo-static suffix */
			if(false !== suffix){
				suffix = suffix || this.MODEL[2].split("|")[0];
				if(suffix){
					url += "." + suffix;
				}
			}
		}

		url = this.APP + url;
		return url;
	}

	/* Set the value of the form */
	Codono.setValue = function(name, value){
		var first = name.substr(0,1), input, i = 0, val;
		if(value === "") return;
		if("#" === first || "." === first){
			input = $(name);
		} else {
			input = $("[name='" + name + "']");
		}

		if(input.eq(0).is(":radio")) { //single button
			input.filter("[value='" + value + "']").each(function(){this.checked = true});
		} else if(input.eq(0).is(":checkbox")) { //Check box
			if(!$.isArray(value)){
				val = new Array();
				val[0] = value;
			} else {
				val = value;
			}
			for(i = 0, len = val.length; i < len; i++){
				input.filter("[value='" + val[i] + "']").each(function(){this.checked = true});
			}
		} else {  //otherFormsOptionsdirectSet upvalue
			input.val(value);
		}
	}

})(jQuery);
