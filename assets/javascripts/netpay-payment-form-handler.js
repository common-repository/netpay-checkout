(function ($, undefined) {

    let $form = $('form.checkout, form#order_review');

    const callback = function (_this, referenceId) {
        $("#netpay_card_reference_id").val(referenceId);
        $("#netpay_card_httpBrowserColorDepth").val(screen.colorDepth);
        $("#netpay_card_httpBrowserJavaEnabled").val(navigator.javaEnabled() == true ? "TRUE" : "FALSE");
        $("#netpay_card_httpBrowserJavaScriptEnabled").val("TRUE");
        $("#netpay_card_httpBrowserLanguage").val(navigator.language || navigator.userLanguage);
        $("#netpay_card_httpBrowserScreenHeight").val(window.innerHeight);
        $("#netpay_card_httpBrowserScreenWidth").val(window.innerWidth);
        $("#netpay_card_httpBrowserTimeDifference").val(new Date().getTimezoneOffset());
        $("#netpay_card_deviceChannel").val("Browser"); 
    }

    function netpayFormHandler() {

        function showError(message) {
            if (!message) {
                return;
            }
            $(".woocommerce-error, input.netpay_token").remove();

            $ulError = $("<ul>").addClass("woocommerce-error");

            if ($.isArray(message)) {
                $.each(message, function (i, v) {
                    $ulError.append($("<li>" + v + "</li>"));
                })
            } else {
                $ulError.html("<li>" + message + "</li>");
            }

            $form.prepend($ulError);
            $("html, body").animate({
                scrollTop: 0
            }, "slow");
        }

        function hideError() {
            $(".woocommerce-error").remove();
        }

        function validSelection() {
            $card_list = $("input[name='card_id']");
            $selected_card_id = $("input[name='card_id']:checked");
            // there is some existing cards but nothing selected then warning
            if ($card_list.length > 0 && $selected_card_id.length === 0) {
                return false;
            }

            return true;
        }

        if ($('#payment_method_netpay').is(':checked')) {
            if (!validSelection()) {
                showError(netpay_params_card.no_card_selected);
                return false;
            }

            if (0 === $('input.netpay_token').length) {
                $form.block({
                    message: null,
                    overlayCSS: {
                        background: '#fff url(' + wc_checkout_params.ajax_loader_url + ') no-repeat center',
                        backgroundSize: '16px 16px',
                        opacity: 0.6
                    }
                });

                let errors = [],
                    netpay_card = {},
                    netpay_card_fields = {
                        'card': $('#netpay_card_value'),
                        'number': $('#netpay_card_number'),
                        'expiration_month': $('#netpay_card_expiration_month'),
                        'expiration_year': $('#netpay_card_expiration_year'),
                        'security_code': $('#netpay_card_security_code')
                    };

                $.each(netpay_card_fields, function (index, field) {
                    netpay_card[index] = field.val();
                    if ("" === netpay_card[index]) {
                        errors.push(netpay_params_card['required_card_' + index]);
                    }
                });

                if (errors.length > 0) {
                    showError(errors);
                    $form.unblock();
                    return false;
                }

                hideError();

                let netpay_card_reference_id = $("#netpay_card_reference_id").val();
                if (netpay_card_reference_id != "") {
                    $form.submit();
                } else
                    alert("El valor del reference ID esta vacio, espera unos segundos y da clic de nuevo en el botón de pagar.");

                return false;
            }

        }
    }

    function getCardType(cardNumber) {
        const Lookup = new NetPay();
        let binCard = cardNumber.replace(/\s/g, "").substring(0, 6);
        let data = Lookup.lookup(binCard);
        let json = JSON.parse(data);
        if (json.result == 'success') {
            //if (json.data.type == 'credit') {//scheme
            //  return true;
            //}
            return json.data;
        }
        return false;
    }

    let generateDeviceFingerprint = (function () {
        let executed = false;
        return function (org_id) {
            if (!executed) {
                executed = true;
                let _this = this;
                // TODO
                // netpay3ds.setUrl('url');
                // netpay3ds.setUrl('https://gateway.netpaydev.com');
                let switch_data = netpay_params_card.test_mode == 1 ? true : false; 
                netpay3ds.setSandboxMode(switch_data);
                netpay3ds.init(function () {
                    netpay3ds.config(_this, netpay_params_card.total, callback);
                });

                let session_id = doProfile(org_id);
                $("#netpay_card_devicefingerprint").val(session_id);
            }
        };
    })();

    function luhnCheck(value) {
        // Accept only digits, dashes or spaces
        if (/[^0-9-\s]+/.test(value)) return false;

        // The Luhn Algorithm. It's so pretty.
        let nCheck = 0, bEven = false;
        value = value.replace(/\D/g, "");

        for (let n = value.length - 1; n >= 0; n--) {
            let cDigit = value.charAt(n),
                nDigit = parseInt(cDigit, 10);

            if (bEven && (nDigit *= 2) > 9) nDigit -= 9;

            nCheck += nDigit;
            bEven = !bEven;
        }

        return (nCheck % 10) == 0;
    }

    function cardTypes() {
        return {
            visa: {
                name: "visa",
                title: "Visa",
                regx: /^4/,
                length: [16],
                accept: true
            },
            mastercard: {
                name: "mastercard",
                title: "MasterCard",
                regx: /^5[1-5]/,
                length: [16],
                accept: true
            },
            amex: {
                name: "amex",
                title: "American Express",
                regx: /^3[47]/,
                length: [15],
                accept: true
            },
        };
    }

    function getCardScheme(val) {
        let bookIDs;
        bookIDs = cardTypes();
        let bookIdIndex;
        for (bookIdIndex in bookIDs) {
            let _cardObj = bookIDs[bookIdIndex];
            if (_cardObj, val.match(_cardObj.regx)) {
                return _cardObj;
            }
        }
        return false;
    }

    function contains(obj, a) {
        let i = a.length;
        while (i--) {
            if (a[i] === obj) {
                return true;
            }
        }
        return false;
    }

    function isCardAccepted(cardType) {
        return (contains(cardType.name, netpay_params_card.card_types) ? true : false);
    }

    function isAllowedKey(event) {
        let allowed = false;
        if (event.keyCode === 8 || event.keyCode === 9 || event.keyCode === 37 || event.keyCode === 39) {
            allowed = true;
        }
        return allowed;
    }

    function limit(event, element, max_chars) {
        if (isTextSelected(element)) {																		//
            max_chars += 1;
        }
        if (element.value.length >= max_chars && !isAllowedKey(event)) {
            event.preventDefault();
        }
    }

    function isTextSelected(input) {
        let startPosition = input.selectionStart;
        let endPosition = input.selectionEnd;

        let selObj = document.getSelection();
        let selectedText = selObj.toString();

        if (selectedText.length != 0) {
            input.focus();
            input.setSelectionRange(startPosition, endPosition);
            return true;
        } else if (input.value.substring(startPosition, endPosition).length != 0) {
            input.focus();
            input.setSelectionRange(startPosition, endPosition);
            return true;
        }
        return false;
    }

    $(function () {
        $('body').on('checkout_error', function () {
            $('.netpay_token').remove();
        });

        $('form.checkout').unbind('checkout_place_order');
        $('form.checkout').on('checkout_place_order', function () {
            let payment_method = $( 'form.checkout input[name="payment_method"]:checked' ).val();
            if ( payment_method == 'netpay') {
                //netpayFormHandler();
                //return false;
            }
            //return true;
        });

        /* Pay Page Form */
        $('form#order_review').on('submit', function () {
            //return netpayFormHandler();
        });

        //$( "woocommerce_checkout_place_order" ).on( 'click', function () {
        //$('#place_order').click(function(){

        /* Both Forms */
        $('form.checkout, form#order_review').on('change', '#netpay_cc_form input', function () {
            $('.netpay_token').remove();
        });

        $('body').on('focus', 'input', function () {

            $("#netpay_card_value").keyup(function () {
                let cardNumber = $("#netpay_card_value").val().replace(/ /g, '');
                let creditCardLength = cardNumber.length;
                if (creditCardLength >= 6) {
                    let cardScheme = getCardScheme(cardNumber);
                    if (cardScheme.name == 'amex') {
                        $('#netpay_card_security_code').attr('placeholder', '****');
                    } else {
                        $('#netpay_card_security_code').attr('placeholder', '***');
                    }
                }
            });

            $("#netpay_card_number").keyup(function () {
                let cardNumber = $("#netpay_card_number").val().replace(/ /g, '');
                let creditCardLength = cardNumber.length;
                let isValidCardScheme = false;
                let isValidCard = false;

                if (creditCardLength >= 6) {
                    let cardScheme = getCardScheme(cardNumber);
                    if (!isCardAccepted(cardScheme)) {
                        if (!isCardAccepted(cardScheme)) {
                            $('#netpay_card_number').parent().find('#netpay_card_invalid_card_scheme').remove();
                            $("#netpay_card_number").parent().append('<span id="netpay_card_invalid_card_scheme" class="netpay-card-woocommerce-error"> - No. tarjeta inválido, sólo se aceptan tarjetas "' + netpay_params_card.card_types_title.toString() + '."<br/></span>');
                        } else {
                            $('#netpay_card_number').parent().find('#netpay_card_invalid_card_scheme').remove();
                        }
                    } else {
                        isValidCardScheme = true;
                    }

                    if (cardScheme.name == 'amex') {
                        $("#netpay_card_promotion option[value='18']").hide();
                        $("#netpay_card_promotion").val($("#netpay_card_promotion option:eq(1)").val());
                    } else {
                        $("#netpay_card_promotion option[value='18']").show();
                        $("#netpay_card_promotion").val($("#netpay_card_promotion option:eq(0)").val());
                    }

                    let cardType = getCardType(cardNumber);
                    if (cardType.type == 'credit' || cardScheme.name == 'amex') {
                        $('div#netpay_promotion_div').show();
                        $("#netpay_card_promotion_hidden").val('1');
                    } else {
                        $('div#netpay_promotion_div').hide();
                        $("#netpay_card_promotion_hidden").val('0');
                    }

                    if ((cardScheme.name == 'amex' && creditCardLength == 15) || (cardScheme.name != 'amex' && creditCardLength == 16)) {
                        let validateCard = luhnCheck(cardNumber);
                        if (!validateCard) {
                            $('#netpay_card_number').parent().find('#netpay_card_invalid_card').remove();
                            $("#netpay_card_number").parent().append('<span id="netpay_card_invalid_card"  class="netpay-card-woocommerce-error"> - No. tarjeta inválido. </span>');
                        } else {
                            isValidCard = true;
                            $('#netpay_card_number').parent().find('#netpay_card_invalid_card').remove();
                        }
                    } else {
                        $('#netpay_card_number').parent().find('#netpay_card_invalid_card').remove();
                    }

                    if (isValidCardScheme && isValidCard) {
                        $('#netpay_card_number').parent().find('#netpay_card_invalid_card_length').remove();
                        $('#netpay_card_number').parent().find('#netpay_card_invalid_card_scheme').remove();
                        $("#netpay_card_number").css("border-color", "gray");
                    } else {
                        $("#netpay_card_number").css("border-color", "red");
                    }
                } else {
                    $("#netpay_card_number").css("border-color", "red");
                }
                if (creditCardLength < 15) {
                    $('#netpay_card_number').parent().find('#netpay_card_invalid_card_length').remove();
                    $("#netpay_card_number").parent().append('<span id="netpay_card_invalid_card_length"  class="netpay-card-woocommerce-error"> - No. tarjeta inválido, deben ser 15-16 dígitos. <br /></span>');
                } else {
                    $('#netpay_card_number').parent().find('#netpay_card_invalid_card_length').remove();
                    $("#netpay_card_number").css("border-color", "gray");
                }
            });

            $("#netpay_card_security_code").keypress(function (e) {
                if (e.which != 8 && e.which != 0 && (e.which < 48 || e.which > 57)) {
                    return false;
                }
            });

            $('#netpay_card_expiration_card').keyup(function () {
                let expiration_card = $('#netpay_card_expiration_card').val();
                if (expiration_card.length == 5) {
                    let today = new Date();
                    let year = today.getFullYear();
                    let month = today.getMonth();

                    let fields = expiration_card.split('/');
                    let expiration_month = fields[0];
                    let expiration_year = fields[1];
                    if (parseInt(expiration_year) < year - 2000) {
                        $("#netpay_card_expiration_card").css("border-color", "red");
                        $('#netpay_card_expiration_card').parent().find('#netpay_card_expiry_card').remove();
                        $("#netpay_card_expiration_card").parent().append('<span id="netpay_card_expiry_card"  class="netpay-card-woocommerce-error"> - Fecha de vencimiento inválida, debe tener el formato mm/aa y debe ser posterior a la actual. </span>');
                    } else if (parseInt(expiration_year) == year - 2000 && parseInt(expiration_month) < month) {
                        $("#netpay_card_expiration_card").css("border-color", "red");
                        $('#netpay_card_expiration_card').parent().find('#netpay_card_expiry_card').remove();
                        $("#netpay_card_expiration_card").parent().append('<span id="netpay_card_expiry_card"  class="netpay-card-woocommerce-error"> - Fecha de vencimiento inválida, debe tener el formato mm/aa y debe ser posterior a la actual. </span>');
                    } else {
                        $('#netpay_card_expiration_card').parent().find('#netpay_card_expiry_card').remove();
                        $("#netpay_card_expiration_card").css("border-color", "gray");
                    }
                } else {
                    $("#netpay_card_expiration_card").css("border-color", "red");
                }
            });

            $('#netpay_card_security_code').keyup(function () {
                let cardNumber = $("#netpay_card_number").val().replace(/ /g, '');
                let cardScheme = getCardScheme(cardNumber);

                let security_code = $('#netpay_card_security_code').val();

                if (cardScheme.name == 'amex') {
                    $("#netpay_card_security_code").attr('maxlength', '4');
                    if (security_code.length == 4) {
                        $('#netpay_card_security_code').parent().find('#netpay_card_security_code_validation').remove();
                        $("#netpay_card_security_code").css("border-color", "gray");
                    } else {
                        $("#netpay_card_security_code").css("border-color", "red");
                        $('#netpay_card_security_code').parent().find('#netpay_card_security_code_validation').remove();
                        $("#netpay_card_security_code").parent().append('<span id="netpay_card_security_code_validation"  class="netpay-card-woocommerce-error"> - Código de seguridad inválido, deben ser 4 dígitos. </span>');
                    }
                } else {
                    $("#netpay_card_security_code").attr('maxlength', '3');
                    if (security_code.length == 3) {
                        $('#netpay_card_security_code').parent().find('#netpay_card_security_code_validation').remove();
                        $("#netpay_card_security_code").css("border-color", "gray");
                    } else {
                        $("#netpay_card_security_code").css("border-color", "red");
                        $('#netpay_card_security_code').parent().find('#netpay_card_security_code_validation').remove();
                        $("#netpay_card_security_code").parent().append('<span id="netpay_card_security_code_validation"  class="netpay-card-woocommerce-error"> - Código de seguridad inválido, deben ser 3 dígitos. </span>');
                    }
                }
            });

            $("#netpay_card_number").focus(function () {
                new Cleave('#netpay_card_number', {
                    creditCard: true
                });
            });

            $("#netpay_card_expiration_card").focus(function () {
                new Cleave('#netpay_card_expiration_card', {
                    date: true,
                    datePattern: ['m', 'y']
                });
            });

            $("#netpay_card_value").on("click", function () {
                generateDeviceFingerprint(netpay_params_card.org_id);
            });

            $("#netpay_card_value").focus(function () {
                new Cleave('#netpay_card_value', {
                    creditCard: true
                });
                document.getElementById("netpay_card_value").value = document.getElementById("netpay_card_number").value;
            });

        });
        /*
                $( 'form.checkout' ).on( 'checkout_place_order', function() {
                    debugger;
                    let payment_method = $( 'form.checkout input[name="payment_method"]:checked' ).val();
                    if ( payment_method == 'netpay') {
                        netpayFormHandler();
                        return false;
                    }
                    return true;
                });
        */
    })
})(jQuery)
