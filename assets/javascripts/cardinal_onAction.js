(function ( Cardinal, $ ) {
    try {
        jQuery( function($) {  
            Cardinal.on('payments.validated', function(data) {
              cardinalTimeOut = localStorage.getItem("cardinalTimeOut");
              if (data.ErrorDescription === "Success" && data.ErrorNumber === 0 && cardinalTimeOut != "true") {
                document.cookie = `processorTransactionId=${data.Payment.ProcessorTransactionId}`;
                data.Payment.ProcessorTransactionId !== null ? 
                 document.getElementById("processor_transaction_id").value = data.Payment.ProcessorTransactionId : null;
              } else {
                localStorage.setItem('cardinalTimeOut', "false");
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