var RcmLoginMgr = function(loginUrl) {

    var me = this;

    me.loginUrl = loginUrl;

    me.successCallback = null;

    me.doLogin = function(username, password, failCallback) {

        me.failCallback=failCallback;

        var data = {
            username : username,
            password :  password
        };

        $.ajax({
            type: 'POST',
            url : me.loginUrl,
            cache : false,
            data : data,
            dataType: "json",
            success : function(data){
                me.processResponse(data,failCallback)
            },
            error : function(){failCallback('systemFailure');}
        });
    };

    me.processResponse = function(data,failCallback) {
        if(!data['dataOk']) {
            me.processError(data['error'],failCallback);
            return;
        }

        window.location=data['redirectUrl'];
    };

    me.processError = function(error, failCallback) {
        if(error!='missing'&&error!='invalid'){
            error='systemFailure';
        }
        failCallback(error);
    };
};