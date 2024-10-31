(function ( Cardinal, $ ) {
    try {
        Cardinal.configure({
           logging: {
               level: 'on'
           }
        });

        Cardinal.configure({
            logging: {
              level: 'verbose'
            }
        });

        function cardinal_auth(body) {  
           Cardinal.continue('cca', {
               "AcsUrl": body.acsUrl,
               "Payload": body.payload,
           }, {
             "OrderDetails": {
               "TransactionId": body.transactionId
             }
           });
        }
    } catch (ex) {
        try {
            permanent_error(ex.toString());
        } catch (e) {}
        throw ex;
    }
})(window.Cardinal, window.jQuery)