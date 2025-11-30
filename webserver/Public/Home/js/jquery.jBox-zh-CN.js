
/* jBox Global Settings */
var jBoxConfig = {};

jBoxConfig.defaults = {
    id: null, /* Unique in the pageid, IfnullAutomatically generating a randomid,OneidWill display ajBox */
    top: '15%', /* Distance from the top of the window,It can bePercentage or pixels(Such as '100px') */
    border: 5, /* windowofouterframePixel size,must be0An integer greater than */
    opacity: 0.1, /* windowIsolation layeroftransparency,If set to0,Spacer layer is not displayed */
    timeout: 0, /* windowdisplayHow manymillisecondRearautomaticshut down,If set to0,Not automatically shut down */
    showType: 'fade', /* Types of window display,The possible values ​​are:show、fade、slide */
    showSpeed: 'fast', /* Window display of speed,The possible values ​​are:'slow'、'fast', Represents an integer millisecond */
    showIcon: true, /* whetherdisplaywindowtitleoficon，truedisplay，falseDo not show, or customCSSstyleclassname(Withforiconforbackground) */
    showClose: true, /* whetherdisplaywindowUpper right cornerofshut downPush button */
    draggable: true, /* If you can drag the window */
    dragLimit: true, /* In can dragwindowof情况下，whetherlimitIn the visible range */
    dragClone: false, /* In can dragwindowof情况下，mousePressedwindowwhetherclonewindow */
    persistent: true, /* indisplayIsolation layerof情况下，Click onWhen the spacer layer，whetheradhere towindow不shut down */
    showScrolling: true, /* whetherdisplayBrowseofscrollArticle */
    ajaxData: {},  /* Use the window contentsget:orpost:PrefixMarkof情况下，ajax postData, for example:{ id: 1 } or "id=1" */
    iframeScrolling: 'auto', /* Use the window contentsiframe:PrefixMarkof情况下，iframeofscrollingProperty values, optional values ​​are:'auto'、'yes'、'no' */

    title: 'jBox', /* Window title */
    width: 350, /* Width of the window, the value of'auto'Or is an integer pixels */
    height: 'auto', /* Height of the window, the value of'auto'Or is an integer pixels */
    bottomText: '', /* Window buttonsleftContent, whenNoPush buttonWhen thisSetting invalid */
    buttons: { 'Ok': 'ok' }, /* Window buttons */
    buttonsFocus: 0, /* ShowThe first fewPush buttonfordefaultPush button，indexFrom0Start */
    loaded: function (h) { }, /* windowUpon completion of the execution loadfunction，neednoteofYes,in caseYesajaxoriframeBut also to wait for finished loadinghttprequestConsidered windowloadcarry out,parameterhExpressed window contentsjQueryObjects */
    submit: function (v, h, f) { return true; }, /* Click onwindowPush buttonAfterCallback,returntrueWhen expressedshut downwindow,parameterThere is three，vRepresenting the pointPush buttonofreturnvalue，hExpressed window contentsjQueryObjects，fExpressed in the contents of the windowformFormsThe key */
    closed: function () { } /* windowshut downRearcarried outThe function */
};

jBoxConfig.stateDefaults = {
    content: '', /* statusContent，not supportPrefixMark */
    buttons: { 'Ok': 'ok' }, /* State button */
    buttonsFocus: 0, /* ShowThe first fewPush buttonfordefaultPush button，indexFrom0Start */
    submit: function (v, h, f) { return true; } /* Click onstatusPush buttonAfterCallback,returntrueWhen expressedshut downwindow,parameterThere is three，vRepresenting the pointPush buttonofreturnvalue，hExpressed window contentsjQueryObjects，fExpressed in the contents of the windowformFormsThe key */
};

jBoxConfig.tipDefaults = {
    content: '', /* promptContent，not supportPrefixMark */
    icon: 'info', /* promptoficon，The possible values ​​are'info'、'success'、'warning'、'error'、'loading'The default value'info'When is'loading'Time，timeoutThe value is set to0，Showwill notautomaticshut down。 */
    top: '40%', /* Tip of the distance from the top,It can bePercentage or pixels(Such as '100px') */
    width: 'auto', /* Tip height value'auto'Or is an integer pixels */
    height: 'auto', /* Tip height value'auto'Or is an integer pixels */
    opacity: 0, /* windowIsolation layeroftransparency,If set to0,Spacer layer is not displayed */
    timeout: 3000, /* promptdisplayHow manymillisecondRearautomaticshut down,It must be greater than0Integer */
    closed: function () { } /* promptshut downRearcarried outThe function */
};

jBoxConfig.messagerDefaults = {
    content: '', /* informationContent，not supportPrefixMark */
    title: 'jBox', /* Header information */
    icon: 'none', /* Information icon, value'none'TimeforDo not showicon，The possible values ​​are'none'、'info'、'question'、'success'、'warning'、'error' */
    width: 350, /* Height information, the value of'auto'Or is an integer pixels */
    height: 'auto', /* Height information, the value of'auto'Or is an integer pixels */
    timeout: 3000, /* informationdisplayHow manymillisecondRearautomaticshut down,If set to0,Not automatically shut down */
    showType: 'slide', /* Type of information displayed,The possible values ​​are:show、fade、slide */
    showSpeed: 600, /* Speed ​​information displayed,The possible values ​​are:'slow'、'fast', Represents an integer millisecond */
    border: 0, /* informationofouterframePixel size,must be0An integer greater than */
    buttons: {}, /* Button information */
    buttonsFocus: 0, /* ShowThe first fewPush buttonfordefaultPush button，indexFrom0Start */
    loaded: function (h) { }, /* windowUpon completion of the execution loadfunction,parameterhExpressed window contentsjQueryObjects */
    submit: function (v, h, f) { return true; }, /* Click oninformationPush buttonAfterCallback,returntrueWhen expressedshut downwindow,parameterThere is three，vRepresenting the pointPush buttonofreturnvalue，hExpressed window contentsjQueryObjects，fExpressed in the contents of the windowformFormsThe key */
    closed: function () { } /* informationshut downRearcarried outThe function */
};

jBoxConfig.languageDefaults = {
    close: 'Close', /* windowUpper right cornershut downPush buttonprompt */
    ok: 'Ok', /* $.jBox.prompt() seriesmethodof“determine”Push buttonWriting */
    yes: 'Yes', /* $.jBox.warning() methodof“Yes”Push buttonWriting */
    no: 'No', /* $.jBox.warning() methodof“no”Push buttonWriting */
    cancel: 'Cancel' /* $.jBox.confirm() with $.jBox.warning() methodof“cancel”Push buttonWriting */
};

$.jBox.setDefaults(jBoxConfig);
