(function ( Cardinal, $ ) {
    try {
        jQuery( function($) {
              const paReq = decodeURIComponent(decodeURIComponent(getCookie('paReq')));
              const acsUrl = decodeURIComponent(decodeURIComponent(getCookie('acsUrl')));
              const authenticationTransactionID = getCookie('aTID');

              Cardinal.off('payments.setupComplete');

              Cardinal.continue('cca', {
                 "AcsUrl": acsUrl,
                 "Payload": paReq,
               }, {
                 "OrderDetails": {
                   "TransactionId": authenticationTransactionID
                 }
              });          
        } );
    } catch (ex) {
        try {
            permanent_error(ex.toString());
        } catch (e) {}
        throw ex;
    }
  })(window.Cardinal, window.jQuery)