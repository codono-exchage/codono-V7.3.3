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
 * Locale: ZH (Chinese; Chinese (Zhōngwén), 汉语, 漢語)
 * Region: TW (Taiwan)
 */
$.extend( $.validator.messages, {
	required: "必須填寫",
	remote: "請修正此欄位",
	email: "請輸入effectiveof電子郵件",
	url: "請輸入effectiveof網址",
	date: "請輸入effectiveofdate",
	dateISO: "請輸入effectiveofdate (YYYY-MM-DD)",
	number: "請輸入正確of數值",
	digits: "只可輸入數字",
	creditcard: "請輸入effectiveofcredit卡號碼",
	equalTo: "請重複輸入一次",
	extension: "請輸入effectiveof後綴",
	maxlength: $.validator.format( "最多 {0} 個字" ),
	minlength: $.validator.format( "最少 {0} 個字" ),
	rangelength: $.validator.format( "請輸入長度為 {0} 至 {1} 之間of字串" ),
	range: $.validator.format( "請輸入 {0} 至 {1} 之間of數值" ),
	max: $.validator.format( "請輸入不大於 {0} of數值" ),
	min: $.validator.format( "請輸入不小於 {0} of數值" )
} );
return $;
}));