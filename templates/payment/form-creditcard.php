<div style="display: none;">
<style type="text/css">
    #netpay-card-woocommerce-warning {
        background-color: #FFF3CD;
        padding: 10px;
        color: black;
    }
    .netpay-card-woocommerce-error {
        color: #FF0000;
    }
/*
    ul.payment_methods li input {
        all: unset
    }
*/
</style>
<script>
    function hideCardValue(val) {
        let check = val.includes('*');
        if(!check) {
            document.getElementById("netpay_card_number").value = val;
        }
        let len = val.length;
        if (len >= 19) {
            const regex = /\d{4}(?= \d{1})/g;
            const substr = "****";
            document.getElementById("netpay_card_value").value = val.replace(regex, substr);
        }
        else {
            let newVal = val.replace(/ /g, '');
            document.getElementById("netpay_card_value").value = newVal.replace(/\d{12}(\d{3})/, "**** ******  **$1");
        }
    }
</script>
</div>
<div id="netpay-card-woocommerce-message" style="display:none"></div>
<div id="netpay-card-woocommerce-error" style="display:none"></div>

<p class="form-row form-row-wide netpay-required-field">
    <label for="netpay_card_number">No. tarjeta débito o crédito</label>

    <input name="netpay_card_value" id="netpay_card_value"
           class="input-text" placeholder="•••• •••• •••• ••••"
           onpaste="return false" oncut="return false" maxlength="19"
           onblur="hideCardValue(this.value)"
           autocomplete="off" type="text" required>

    <input id="netpay_card_number" class="input-text" type="hidden"
        maxlength="19" autocomplete="off" placeholder="•••• •••• •••• ••••"
        name="netpay_card_number" required>
</p>

<p class="form-row form-row-wide netpay-required-field">
    <label for="netpay_card_expiration_card">Fecha de vencimiento</label>
    <input id="netpay_card_expiration_card" class="input-text" type="text" maxlength="5"
        autocomplete="off" placeholder="<?php _e( 'MM/AA', 'netpay' ); ?>"
        name="netpay_card_expiration_card" required>
</p>
<p class="form-row form-row-wide netpay-required-field">
    <label for="netpay_card_security_code">Código de seguridad</label>
    <input id="netpay_card_security_code"
        class="input-text" type="password" autocomplete="off" maxlength="4"
        placeholder="•••" name="netpay_card_security_code" required>
</p>

<input id="netpay_card_devicefingerprint"
    class="input-text" type="hidden" name="netpay_card_devicefingerprint">

<input id="netpay_card_reference_id"
    class="input-text" type="hidden" name="netpay_card_reference_id">

<input id="netpay_card_httpBrowserColorDepth"
    class="input-text" type="hidden" name="netpay_card_httpBrowserColorDepth">

<input id="netpay_card_httpBrowserJavaEnabled"
    class="input-text" type="hidden" name="netpay_card_httpBrowserJavaEnabled">

<input id="netpay_card_httpBrowserJavaScriptEnabled"
    class="input-text" type="hidden" name="netpay_card_httpBrowserJavaScriptEnabled">

<input id="netpay_card_httpBrowserLanguage"
    class="input-text" type="hidden" name="netpay_card_httpBrowserLanguage">

<input id="netpay_card_httpBrowserScreenHeight"
    class="input-text" type="hidden" name="netpay_card_httpBrowserScreenHeight">

<input id="netpay_card_httpBrowserScreenWidth"
    class="input-text" type="hidden" name="netpay_card_httpBrowserScreenWidth">

<input id="netpay_card_httpBrowserTimeDifference"
    class="input-text" type="hidden" name="netpay_card_httpBrowserTimeDifference">

<input id="netpay_card_deviceChannel"
    class="input-text" type="hidden" name="netpay_card_deviceChannel">