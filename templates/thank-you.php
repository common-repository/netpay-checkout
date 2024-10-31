<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}
?>
<form method="post" action="#" id="netpayJS-form">
<style>
.reference {
  color: #0071FF;
  font-family: "Open Sans";
  font-size: 17px;
  font-weight: 600;
  letter-spacing: 0;
}
.numero-de-referencia {
  color: #22364D;
  font-family: "Open Sans";
  font-size: 17px;
  font-weight: 600;
  letter-spacing: 0;
}

.expira-en-numero {
  color: #435365;
  font-family: "Open Sans";
  font-size: 13px;
  letter-spacing: 0;
}

.steps {
  color: #435365;
  font-family: "Open Sans";
  font-size: 15px;
}

.te-hemos-enviado-una {
  color: #0071FF;
  font-family: "Open Sans";
  font-size: 15px;
  font-weight: bold;
}

.al-pagar-en-el-co {
  color: #435365;
  font-family: "Open Sans";
  font-size: 15px;
}

.estas-muy-cerca-de {
  color: #293441;
  font-family: "Open Sans";
  font-size: 30px;
  font-weight: 600;
  text-align: center;
  letter-spacing: 0;
}

.para-terminar-solo-h {
  color: #293441;
  font-family: "Open Sans";
  font-size: 20px;
  text-align: center;
  letter-spacing: 0;
}

.realiza-tu-pago-en {
  color: #293441;
  font-family: "Open Sans";
  font-size: 20px;
  text-align: center;
  line-height: 50px;
}

</style>

<form method="post" action="#" id="netpayJS-form">
<div class="form-row form-row-first netpay-required-field">
    <img src = "<?php echo $plugin_dir;?>/sucess_checkout.svg" alt = "Referencia creada" />
</div>

<div class="form-row form-row-second netpay-required-field" style="text-align: center; padding: 30px 0;">
<div class="estas-muy-cerca-de">
    ¡Estás muy cerca de completar tu compra!
</div>
<div class="para-terminar-solo-h">
    Para terminar solo hace falta que realices tu pago de $<?php echo $amount; ?>
</div>
</div>

<div class="form-row form-row-wide netpay-required-field">
<div class="realiza-tu-pago-en">
    ¡Realiza tu pago en cualquiera de los comercios participantes!
</div>
</div>

<div class="form-row form-row-first netpay-required-field" style="border: 1px solid gray; text-align: center;">
    <div class="numero-de-referencia"> Número de referencia: </div>
    <div class="reference"> <?php echo $reference; ?> </div>
    <div class="expira-en-numero"> Expira en <?php echo $expire_in_days; ?> días</div>
</div>

<div class="form-row form-row-second netpay-required-field" style="
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    align-items: center;">
    <img src = "<?php echo $plugin_dir;?>/cash_group.svg" alt = "NetPay Cash" />
</div>

<div class="form-row form-row-wide netpay-required-field">
    <ol class="steps">
        <li> Acude al comercio de tu preferencia y solicita realizar un <b>"pago de servicios"</b>.</li>
        <li> Solicita al cajero que elija "pago de servicios por Banco" o "corresponsales" seleccionando <b>BANORTE</b>.</li>
        <li> Menciona el número <b>002452</b> de Netpay <i>(Número facturador, proveedor o convenio)</i>*</li>
        <li> Dicta tu número de referencia de 19 dígitos y <b>monto exacto</b> a pagar en efectivo.**</li>
        <li> Al confirmar el pago el cajero te entregará un comprobante impreso, verifícalo y consérvalo para cualquier aclaración***</li>
    </ol>
</div>

<div class="form-row form-row-wide netpay-required-field">
<div class="te-hemos-enviado-una">
    Te hemos enviado una copia de esta información a tu correo.
</div>
</div>

<div class="form-row form-row-wide netpay-required-field">
<div class="al-pagar-en-el-co">
    * &nbsp;&nbsp;&nbsp; El número facturador, de proveedor o convenio varía dependiendo del establecimiento donde se realice el pago, puede ser <b>002452</b> o <b>2452</b>. <br>
    ** &nbsp; Se te cobrará una comisión establecida en el comercio. <br>
    *** Al pagar en el comercio autorizado, el pago se verá reflejado en un tiempo estimado de 24 horas hábiles.
</div>
</div>
</form>

<p>
<br>
<?php
wp_enqueue_script(
  'print.min.js',
  plugins_url( '/assets/javascripts/print.min.js', dirname( __FILE__ ) ),
  array(  ),
  WC_VERSION,
  true
);
wp_enqueue_style( 
  'print.min.css',
  plugins_url( '/assets/css/print.min.css', dirname( __FILE__ ) ),
  array(), 
  NETPAY_WOOCOMMERCE_PLUGIN_VERSION );
?>
<button type="button" onclick="printJS('netpayJS-form', 'html')">
    Imprimir
 </button>
</p>