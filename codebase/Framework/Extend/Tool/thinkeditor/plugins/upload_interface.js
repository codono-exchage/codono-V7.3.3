function te_upload_interface() {
    //initializationparameter
    var _args = arguments,
    _fn   = _args.callee,
    _data = '';

    if( _args[0] == 'reg' ) {
        //Register callback
        _data = _args[1];
        _fn.curr = _data['callid'];
        _fn.data = _data;
        jQuery('#temaxsize').val(_data['maxsize']);
    } else if( _args[0] == 'get' ) {
        //Get Configuration
        return _fn.data || false;

    } else if( _args[0] == 'call' ) {
        //deal withCallbacksExamplesInconsistent
        if( _args[1] != _fn.curr ) {
            alert( 'Upload error,Please do notSimultaneouslyturn onMoreUploadPop-ups' );
            return false;
        }
        //Upload successful
        if( _args[2] == 'success' ) {
            _fn.data['callback']( _args[3] );
        }
        //upload failed
        else if( _args[2] == 'failure' ) {
            alert( '[upload failed]\nError Messages:'+_args[3] );
        }
        //File type detection error
        else if( _args[2] == 'filetype' ) {
            alert( '[upload failed]\nError Messages：youUploaddocumentTypes ofmistaken' );
        }
        //Processing status change
        else if( _args[2] == 'change' ) {
            // TODO More detailed callback implementation,Here to returntrueAuto Commit
            return true;
        }
    }
}
//When the user selects a file
function checkTypes(id){
    //Check file type
    var filename  = document.getElementById( 'teupload' ).value,
    filetype  = document.getElementById( 'tefiletype' ).value.split( ',' );

    currtype  = filename.split( '.' ).pop(),
    checktype = false;

    if( filetype[0] == '*' ) {
        checktype = true;
    } else {
        for(var i=0; i<filetype.length; i++) {
            if( currtype ==  filetype[i] ) {
                checktype = true;
                break;
            }
        }
    }
    if( !checktype ) {
        alert( '[upload failed]\nError Messages：youUploaddocumentTypes ofmistaken' );
        return false;
    } else {
        //checkby，submit
        jQuery('#'+id).submit()
    }
}