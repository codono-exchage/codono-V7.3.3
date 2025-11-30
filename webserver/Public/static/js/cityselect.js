var citys={"citylist":[{"p":"Afghanistan","c":[{"n":"CityName"}]}, {"p":"Albania"}, {"p":"Algeria"}, {"p":"American Samoa"}, {"p":"Angola"}, {"p":"Anguilla"}, {"p":"Antartica"}, {"p":"Antigua and Barbuda"}, {"p":"Argentina"}, {"p":"Armenia"}, {"p":"Aruba"}, {"p":"Ashmore and Cartier Island"}, {"p":"Australia"}, {"p":"Austria"}, {"p":"Azerbaijan"}, {"p":"Bahamas"}, {"p":"Bahrain"}, {"p":"Bangladesh"}, {"p":"Barbados"}, {"p":"Belarus"}, {"p":"Belgium"}, {"p":"Belize"}, {"p":"Benin"}, {"p":"Bermuda"}, {"p":"Bhutan"}, {"p":"Bolivia"}, {"p":"Bosnia and Herzegovina"}, {"p":"Botswana"}, {"p":"Brazil"}, {"p":"British Virgin Islands"}, {"p":"Brunei"}, {"p":"Bulgaria"}, {"p":"Burkina Faso"}, {"p":"Burma"}, {"p":"Burundi"}, {"p":"Cambodia"}, {"p":"Cameroon"}, {"p":"Canada"}, {"p":"Cape Verde"}, {"p":"Cayman Islands"}, {"p":"Central African Republic"}, {"p":"Chad"}, {"p":"Chile"}, {"p":"China"}, {"p":"Christmas Island"}, {"p":"Clipperton Island"}, {"p":"Cocos (Keeling) Islands"}, {"p":"Colombia"}, {"p":"Comoros"}, {"p":"Congo}, Democratic Republic of the"}, {"p":"Congo}, Republic of the"}, {"p":"Cook Islands"}, {"p":"Costa Rica"}, {"p":"Cote d'Ivoire"}, {"p":"Croatia"}, {"p":"Cuba"}, {"p":"Cyprus"}, {"p":"Czeck Republic"}, {"p":"Denmark"}, {"p":"Djibouti"}, {"p":"Dominica"}, {"p":"Dominican Republic"}, {"p":"Ecuador"}, {"p":"Egypt"}, {"p":"El Salvador"}, {"p":"Equatorial Guinea"}, {"p":"Eritrea"}, {"p":"Estonia"}, {"p":"Ethiopia"}, {"p":"Europa Island"}, {"p":"Falkland Islands (Islas Malvinas)"}, {"p":"Faroe Islands"}, {"p":"Fiji"}, {"p":"Finland"}, {"p":"France"}, {"p":"French Guiana"}, {"p":"French Polynesia"}, {"p":"French Southern and Antarctic Lands"}, {"p":"Gabon"}, {"p":"Gambia}, The"}, {"p":"Gaza Strip"}, {"p":"Georgia"}, {"p":"Germany"}, {"p":"Ghana"}, {"p":"Gibraltar"}, {"p":"Glorioso Islands"}, {"p":"Greece"}, {"p":"Greenland"}, {"p":"Grenada"}, {"p":"Guadeloupe"}, {"p":"Guam"}, {"p":"Guatemala"}, {"p":"Guernsey"}, {"p":"Guinea"}, {"p":"Guinea-Bissau"}, {"p":"Guyana"}, {"p":"Haiti"}, {"p":"Heard Island and McDonald Islands"}, {"p":"Holy See (Vatican City)"}, {"p":"Honduras"}, {"p":"Hong Kong"}, {"p":"Howland Island"}, {"p":"Hungary"}, {"p":"Iceland"}, {"p":"India"}, {"p":"Indonesia"}, {"p":"Iran"}, {"p":"Iraq"}, {"p":"Ireland"}, {"p":"Ireland}, Northern"}, {"p":"Israel"}, {"p":"Italy"}, {"p":"Jamaica"}, {"p":"Jan Mayen"}, {"p":"Japan"}, {"p":"Jarvis Island"}, {"p":"Jersey"}, {"p":"Johnston Atoll"}, {"p":"Jordan"}, {"p":"Juan de Nova Island"}, {"p":"Kazakhstan"}, {"p":"Kenya"}, {"p":"Kiribati"}, {"p":"Korea}, North"}, {"p":"Korea}, South"}, {"p":"Kuwait"}, {"p":"Kyrgyzstan"}, {"p":"Laos"}, {"p":"Latvia"}, {"p":"Lebanon"}, {"p":"Lesotho"}, {"p":"Liberia"}, {"p":"Libya"}, {"p":"Liechtenstein"}, {"p":"Lithuania"}, {"p":"Luxembourg"}, {"p":"Macau"}, {"p":"Macedonia}, Former Yugoslav Republic of"}, {"p":"Madagascar"}, {"p":"Malawi"}, {"p":"Malaysia"}, {"p":"Maldives"}, {"p":"Mali"}, {"p":"Malta"}, {"p":"Man}, Isle of"}, {"p":"Marshall Islands"}, {"p":"Martinique"}, {"p":"Mauritania"}, {"p":"Mauritius"}, {"p":"Mayotte"}, {"p":"Mexico"}, {"p":"Micronesia}, Federated States of"}, {"p":"Midway Islands"}, {"p":"Moldova"}, {"p":"Monaco"}, {"p":"Mongolia"}, {"p":"Montserrat"}, {"p":"Morocco"}, {"p":"Mozambique"}, {"p":"Namibia"}, {"p":"Nauru"}, {"p":"Nepal"}, {"p":"Netherlands"}, {"p":"Netherlands Antilles"}, {"p":"New Caledonia"}, {"p":"New Zealand"}, {"p":"Nicaragua"}, {"p":"Niger"}, {"p":"Nigeria"}, {"p":"Niue"}, {"p":"Norfolk Island"}, {"p":"Northern Mariana Islands"}, {"p":"Norway"}, {"p":"Oman"}, {"p":"Pakistan"}, {"p":"Palau"}, {"p":"Panama"}, {"p":"Papua New Guinea"}, {"p":"Paraguay"}, {"p":"Peru"}, {"p":"Philippines"}, {"p":"Pitcaim Islands"}, {"p":"Poland"}, {"p":"Portugal"}, {"p":"Puerto Rico"}, {"p":"Qatar"}, {"p":"Reunion"}, {"p":"Romainia"}, {"p":"Russia"}, {"p":"Rwanda"}, {"p":"Saint Helena"}, {"p":"Saint Kitts and Nevis"}, {"p":"Saint Lucia"}, {"p":"Saint Pierre and Miquelon"}, {"p":"Saint Vincent and the Grenadines"}, {"p":"Samoa"}, {"p":"San Marino"}, {"p":"Sao Tome and Principe"}, {"p":"Saudi Arabia"}, {"p":"Scotland"}, {"p":"Senegal"}, {"p":"Seychelles"}, {"p":"Sierra Leone"}, {"p":"Singapore"}, {"p":"Slovakia"}, {"p":"Slovenia"}, {"p":"Solomon Islands"}, {"p":"Somalia"}, {"p":"South Africa"}, {"p":"South Georgia and South Sandwich Islands"}, {"p":"Spain"}, {"p":"Spratly Islands"}, {"p":"Sri Lanka"}, {"p":"Sudan"}, {"p":"Suriname"}, {"p":"Svalbard"}, {"p":"Swaziland"}, {"p":"Sweden"}, {"p":"Switzerland"}, {"p":"Syria"}, {"p":"Taiwan"}, {"p":"Tajikistan"}, {"p":"Tanzania"}, {"p":"Thailand"}, {"p":"Tobago"}, {"p":"Toga"}, {"p":"Tokelau"}, {"p":"Tonga"}, {"p":"Trinidad"}, {"p":"Tunisia"}, {"p":"Turkey"}, {"p":"Turkmenistan"}, {"p":"Tuvalu"}, {"p":"Uganda"}, {"p":"Ukraine"}, {"p":"United Arab Emirates"}, {"p":"United Kingdom"}, {"p":"Uruguay"}, {"p":"USA"}, {"p":"Uzbekistan"}, {"p":"Vanuatu"}, {"p":"Venezuela"}, {"p":"Vietnam"}, {"p":"Virgin Islands"}, {"p":"Wales"}, {"p":"Wallis and Futuna"}, {"p":"West Bank"}, {"p":"Western Sahara"}, {"p":"Yemen"}, {"p":"Yugoslavia"}, {"p":"Zambia"}, {"p":"Zimbabwe"}]};
(function($){
	$.fn.citySelect=function(settings){
		if(this.length<1){return;};

		// Defaults
		settings=$.extend({
			url:"city.min.js",
			prov:null,
			city:null,
			dist:null,
			nodata:null,
			required:true
		},settings);

		var box_obj=this;
		var prov_obj=box_obj.find(".prov");
		var city_obj=box_obj.find(".city");
		var dist_obj=box_obj.find(".dist");
		var prov_val=settings.prov;
		var city_val=settings.city;
		var dist_val=settings.dist;
		var select_prehtml=(settings.required) ? "" : "<option value=''>--Choose--</option>";
		var city_json;

		// Municipal assignmentfunction
		var cityStart=function(){
			var prov_id=prov_obj.get(0).selectedIndex;
			if(!settings.required){
				prov_id--;
			};
			city_obj.empty();//.attr("disabled",true);
			dist_obj.empty();//.attr("disabled",true);

			if(prov_id<0||typeof(city_json.citylist[prov_id].c)=="undefined"){
				if(settings.nodata=="none"){
					//city_obj.css("display","none");
					dist_obj.css("display","none");
				}else if(settings.nodata=="hidden"){
					//city_obj.css("visibility","hidden");
					dist_obj.css("visibility","hidden");
				};
				return;
			};
			
			// Assignment municipal drop-down traversalList
			temp_html=select_prehtml;
			$.each(city_json.citylist[prov_id].c,function(i,city){
				temp_html+="<option value='"+city.n+"'>"+city.n+"</option>";
			});
			city_obj.html(temp_html).css({"display":"","visibility":""});
			distStart();
		};

		// Assignment area(county)function
		var distStart=function(){
			var prov_id=prov_obj.get(0).selectedIndex;
			var city_id=city_obj.get(0).selectedIndex;
			if(!settings.required){
				prov_id--;
				city_id--;
			};
			dist_obj.empty().attr("disabled",true);

			if(prov_id<0||city_id<0||typeof(city_json.citylist[prov_id].c[city_id].a)=="undefined"){
				if(settings.nodata=="none"){
					dist_obj.css("display","none");
				}else if(settings.nodata=="hidden"){
					dist_obj.css("visibility","hidden");
				};
				return;
			};
			
			// Assignment municipal drop-down traversalList
			temp_html=select_prehtml;
			$.each(city_json.citylist[prov_id].c[city_id].a,function(i,dist){
				temp_html+="<option value='"+dist.s+"'>"+dist.s+"</option>";
			});
			dist_obj.html(temp_html).attr("disabled",false).css({"display":"","visibility":""});
		};

		var init=function(){
			// Assignment drop-down traversal provincesList
			temp_html=select_prehtml;
			$.each(city_json.citylist,function(i,prov){
				temp_html+="<option value='"+prov.p+"'>"+prov.p+"</option>";
			});
			prov_obj.html(temp_html);

			// If passed in the provinces and municipalThe value，Then select。(setTimeoutforcompatibleIE6而Set up)
			setTimeout(function(){
				if(settings.prov!=null){
					prov_obj.val(settings.prov);
		//			cityStart();
					setTimeout(function(){
						if(settings.city!=null){
							city_obj.val(settings.city);
							distStart();
							setTimeout(function(){
								if(settings.dist!=null){
									dist_obj.val(settings.dist);
								};
							},1);
						};
					},1);
				};
			},1);

			// Select Province occurs whenevent
			prov_obj.bind("change",function(){
	//			cityStart();
			});

			// Occurred when selecting municipalevent
			city_obj.bind("change",function(){
				distStart();
			});
		};
city_json=citys;
		// Set upProvincesjsondata
init();
	};
})(jQuery);