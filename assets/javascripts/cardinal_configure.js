(function ( Cardinal, $ ) {
  try {
      // STEP 1: CHOOSE YOUR LOGGING LEVEL
          // It is recommended for you to use verbose logging
          // to facilitate troubleshooting. If you don't want
          // logging, omit the Cardinal.configure() block.
      // Cardinal 3d2 setup
      function set_cruise_result(data, jwt) {
        $('#CardinalOneConnectResult').val(JSON.stringify({
            data: data,
            jwt: jwt
        }));
      }

      Cardinal.OneConnect = {};

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
       
          const expirationDate = netpay_card_fields.expiration_date.split('/');
          const expMonth = expirationDate[0];
          const expYear = expirationDate[1];
          let jwt =  $('#CardinalCruiseJWT').val();
          
          Cardinal.setup('init', {
            jwt: jwt,
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
             Cardinal.OneConnect.setupComplete = true;
          });

          set_cruise_result(null, $('#CardinalCruiseJWT').val());
          if (!Cardinal.OneConnect.setupComplete) {
              return;
          }
        });
      });
  } catch (ex) {
      try {
          permanent_error(ex.toString());
      } catch (e) {}
      throw ex;
  }
})(window.Cardinal, window.jQuery)