(function ( Cardinal, $ ) {
    'use strict';
    try {

        Cardinal.OneConnect = {};

        // CAN PROCEED VALIDATION 3DS
        Cardinal.OneConnect.start = function(test_mode, status, responseCode, acsUrl, paReq, authenticationTransactionID, transactionTokenId, redirect, payment, orderId, url_site) {                           
            let _this = this;
            
            _this.transactionTokenId = transactionTokenId;
            _this.redirect = redirect;
            _this.payment = payment;
            _this.orderId = orderId;
            _this.url_site = url_site;

            // TODO
            //netpay3ds.setUrl('url');
            // netpay3ds.setUrl('https://gateway.netpaydev.com');
            let switch_data = test_mode == 1 ? true : false; 
            netpay3ds.setSandboxMode(switch_data);

            let canProceed = netpay3ds.canProceed(status, responseCode, acsUrl);
            if (canProceed) {
                netpay3ds.proceed(_this, acsUrl , paReq, authenticationTransactionID, callbackProceed);
            } else {
                // Error
                callbackProceed(_this, null, 'success');
            }
        };
        
        // GET PROCESSOR TRANSACTION ID VALUE
        const callbackProceed = function (_this, processorTransactionId, status) {
            
            // URL TO REDIRECT TRANSACTION
            const url_listener = `${_this.url_site}/index.php?netpay-listener=cash`;

            if (status === 'success') {
                $.post( url_listener, JSON.stringify({ 
                    processorTransactionId: processorTransactionId, 
                    transactionTokenId: _this.transactionTokenId, 
                    redirect: _this.redirect, 
                    payment: _this.payment,
                    orderId: _this.orderId
                } ), function( data ) {
                    // Working fine
                    window.location = data;
                });
            }
        }

        
    } catch (ex) {
        try {
            permanent_error(ex.toString());
        } catch (e) {}
        throw ex;
    }

})(window.Cardinal, window.jQuery);