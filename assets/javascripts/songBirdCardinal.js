(function ( Cardinal, $ ) {
  try {
      jQuery( function($) {
         $("form.woocommerce-checkout").on('submit', function() {
          netpay_card_fields = {
             'card'             : $( '#netpay_card_value' ).val(),
             'cardNumber'           : $( '#netpay_card_number' ).val(),
             'expiration_date' : $( '#netpay_card_expiration_card' ).val(),
             'first_name'       : $( '#billing_first_name' ).val(),
             'last_name'        : $( '#billing_last_name' ).val()
          };
       
          function getCookie(name) {
           function escape(s) { return s.replace(/([.*+?\^$(){}|\[\]\/\\])/g, '\\$1'); }
           var match = document.cookie.match(RegExp('(?:^|;\\s*)' + escape(name) + '=([^;]*)'));
           return match ? match[1] : null;
          }
       
          // STEP 1: CHOOSE YOUR LOGGING LEVEL
              // It is recommended for you to use verbose logging
              // to facilitate troubleshooting. If you don't want
              // logging, omit the Cardinal.configure() block.
          // Cardinal 3d2 setup
          Cardinal.configure({
             logging: {
                 level: 'on'
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

          const expirationDate = netpay_card_fields.expiration_date.split('/');
          const expMonth = expirationDate[0];
          const expYear = expirationDate[1];

             Cardinal.configure({
              logging: {
                level: 'verbose'
              }
             });
             const paReq = decodeURIComponent(decodeURIComponent(getCookie('paReq')));
             const acsUrl = decodeURIComponent(decodeURIComponent(getCookie('acsUrl')));
             const authenticationTransactionID = getCookie('aTID');
            Cardinal.setup('init', {
              jwt: $('#CardinalCruiseJWT').val(),
              order: {
                Consumer: {
                  Account: {
                    AccountNumber: netpay_card_fields.cardNumber,
                    ExpirationMonth: expMonth,
                    ExpirationYear: expYear,
                    NameOnCard: `${netpay_card_fields.first_name} ${netpay_card_fields.last_name}`
                  }
                }
              }
            });
          
            Cardinal.on('payments.setupComplete', function(setupCompleteData) {
            });

          setTimeout(() => {
            Cardinal.off('payments.setupComplete');
             Cardinal.continue('cca', {
               "AcsUrl": acsUrl,
               "Payload": paReq,
             }, {
               "OrderDetails": {
                 "TransactionId": authenticationTransactionID
               }
            });

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
          }, 50000)
       
       });
      } );
  } catch (ex) {
      try {
          permanent_error(ex.toString());
      } catch (e) {}
      throw ex;
  }
})(window.Cardinal, window.jQuery)