(function( factory ) {
	if ( typeof define === "function" && define.amd ) {
		define( ["jquery", "../jquery.validate"], factory );
	} else if (typeof module === "object" && module.exports) {
		module.exports = factory( require( "jquery" ) );
	} else {
		factory( jQuery );
	}
}(function( $ ) {

/*
 * Translated default messages for the jQuery validation plugin.
 * Locale: ZH (Chinese, Chinese (Zhōngwén), 汉语, 漢語)
 */
$.extend( $.validator.messages, {
	required: "这是必填Field",
	remote: "请修正此Field",
	email: "Please enter a valid电子邮件address",
	url: "Please enter a valid网址",
	date: "Please enter a validdate",
	dateISO: "Please enter a validdate (YYYY-MM-DD)",
	number: "Please enter a validdigital",
	digits: "只能enterdigital",
	creditcard: "Please enter a validcredit卡号码",
	equalTo: "yourenter不相同",
	extension: "Please enter a validsuffix",
	maxlength: $.validator.format( "最多可以enter {0} Characters" ),
	minlength: $.validator.format( "最少要enter {0} Characters" ),
	rangelength: $.validator.format( "please enterlength在 {0} 到 {1} betweenofString" ),
	range: $.validator.format( "please enter范围在 {0} 到 {1} betweenof数值" ),
	max: $.validator.format( "please enter不大于 {0} of数值" ),
	min: $.validator.format( "please enter不小于 {0} of数值" )
} );
return $;
}));