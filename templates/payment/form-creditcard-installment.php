<style>
#netpay-installment-woocommerce-warning {
    background-color: #FFF3CD;
    padding: 10px;
    color: black;
}
.netpay-installment-woocommerce-error {
    color: #FF0000;
}

.netpay-alert-info {
    padding: 10px;
    background-color: #CCE5FE;
    color: black;
}

.netpay-closebtn {
    margin-left: 15px;
    color: black;
    font-weight: bold;
    float: right;
    font-size: 22px;
    line-height: 20px;
    cursor: pointer;
    transition: 0.3s;
}

.netpay-closebtn:hover {
    color: white;
}

/*
ul.payment_methods li input {
    all: unset
}*/
</style>

<script>
    function hideInstallmentValue(val) {
        let check = val.includes('*');
        if(!check) {
            document.getElementById("netpay_installment_number").value = val;
        }
        let len = val.length;
        if (len >= 19) {
            const regex = /\d{4}(?= \d{1})/g;
            const substr = "****";
            document.getElementById("netpay_installment_value").value = val.replace(regex, substr);
        }
        else {
            let newVal = val.replace(/ /g, '');
            document.getElementById("netpay_installment_value").value = newVal.replace(/\d{12}(\d{3})/, "**** ******  **$1");
        }
    }
</script>

<?php if ( $viewData['count'] >= 1 ) : ?>
<div id="netpay-installment-woocommerce-message" style="display:none"></div>
<div id="netpay-installment-woocommerce-error" style="display:none"></div>

<p class="form-row form-row-wide netpay-required-field">
	<label for="netpay_installment_number">No. tarjeta débito o crédito</label>

    <input name="netpay_installment_value" id="netpay_installment_value"
           class="input-text" placeholder="•••• •••• •••• ••••"
           onpaste="return false" oncut="return false" maxlength="19"
           onblur="hideInstallmentValue(this.value)"
           autocomplete="off" type="text" required>

	<input id="netpay_installment_number" class="input-text" type="hidden"
		maxlength="19" autocomplete="off" placeholder="•••• •••• •••• ••••"
		name="netpay_installment_number" required>
</p>

<p class="form-row form-row-wide netpay-required-field">
	<label for="netpay_installment_expiration_card">Fecha de vencimiento</label>
	<input id="netpay_installment_expiration_card" class="input-text" type="text" maxlength="5"
		autocomplete="off" placeholder="<?php _e( 'MM/AA', 'netpay' ); ?>"
		name="netpay_installment_expiration_card" required>
</p>
<p class="form-row form-row-wide netpay-required-field">
	<label for="netpay_installment_security_code">Código de seguridad</label>
	<input id="netpay_installment_security_code"
		class="input-text" type="password" autocomplete="off" maxlength="4"
		placeholder="•••" name="netpay_installment_security_code" required>
</p>

<?php if ( $viewData['count'] >= 1 ) : ?>
<div id="netpay_promotion_div">
<p class="form-row form-row-wide netpay-required-field">
	<label for="netpay_installment_promotion">Promoción</label>
	<select style="width: 100%;" class="select2-container select2-container--default select2-container--open" id="netpay_installment_promotion" name="netpay_installment_promotion">
	<?php foreach ( $viewData['installment_promotions'] as $promotions ) : ?>
		<option value="<?php echo  $promotions['number']; ?>"><?php echo  $promotions['lang']; ?></option>
	<?php endforeach; ?>
	</select>
</p>
</div>
<?php endif; ?>

<input id="netpay_installment_devicefingerprint"
	class="input-text" type="hidden" name="netpay_installment_devicefingerprint">

<input id="netpay_card_installment_reference_id"
   class="input-text" type="hidden" name="netpay_card_installment_reference_id">

   <input id="netpay_card_installment_httpBrowserColorDepth"
    class="input-text" type="hidden" name="netpay_card_installment_httpBrowserColorDepth">

<input id="netpay_card_installment_httpBrowserJavaEnabled"
    class="input-text" type="hidden" name="netpay_card_installment_httpBrowserJavaEnabled">

<input id="netpay_card_installment_httpBrowserJavaScriptEnabled"
    class="input-text" type="hidden" name="netpay_card_installment_httpBrowserJavaScriptEnabled">

<input id="netpay_card_installment_httpBrowserLanguage"
    class="input-text" type="hidden" name="netpay_card_installment_httpBrowserLanguage">

<input id="netpay_card_installment_httpBrowserScreenHeight"
    class="input-text" type="hidden" name="netpay_card_installment_httpBrowserScreenHeight">

<input id="netpay_card_installment_httpBrowserScreenWidth"
    class="input-text" type="hidden" name="netpay_card_installment_httpBrowserScreenWidth">

<input id="netpay_card_installment_httpBrowserTimeDifference"
    class="input-text" type="hidden" name="netpay_card_installment_httpBrowserTimeDifference">

<input id="netpay_card_installment_deviceChannel"
    class="input-text" type="hidden" name="netpay_card_installment_deviceChannel">

<input id="netpay_installment_promotion_hidden"
	class="input-text" type="hidden" name="netpay_installment_promotion_hidden" value="1">

<?php else: ?>
<br>
	<div id="netpay-installment-woocommerce-warning">
		<?php if ( $viewData['minimum_amount'] > 1 ) : ?>
			La cantidad debe de ser mayor o igual a <?php echo $viewData['minimum_amount']; ?> para aplicar a meses sin intereses
		<?php else: ?>
			No hay promociones activas de Meses sin intereses.
		<?php endif; ?>
	</div>
<?php endif; ?>
