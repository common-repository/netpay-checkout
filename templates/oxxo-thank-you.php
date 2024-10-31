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
  font-weight: 600;
  text-align: center;
  line-height: 50px;
}

.has-text-align-right {
    display: none;
}

.wrap {
    float: none;
    margin: 5px;
    padding: 0 20px 20px 0;
}

</style>

    <div class="woocommerce-order" style="
    width: calc(60%);
    position: absolute;
    left: 20%;
    padding: 5px;
    text-align: center;
    z-index: 2;
">
        <br clear="all">
<form method="post" action="#" id="netpayJS-form">
<div class="form-row form-row-wide netpay-required-field">
    <img src = "<?php echo $plugin_dir;?>/cash/sucess_checkout.svg" alt = "Referencia creada" />
</div>

<div class="form-row form-row-second netpay-required-field" style="text-align: center; padding: 30px 0;">
<div class="estas-muy-cerca-de">
    <p>¡Estás muy cerca de <br>tener tu compra!</p>
</div>
<div class="para-terminar-solo-h">
    Paga $<?php echo $amount; ?> en cualquier tienda OXXO
</div>
</div>

<div class="form-row form-row-wide netpay-required-field">
<div class="realiza-tu-pago-en">
    Conoce tu número de referencia, paga y disfruta
</div>
</div>

<div class="form-row form-row-first netpay-required-field" style="border: 1px solid gray; text-align: center;width: 57%;">
    <div class="numero-de-referencia" style="padding: 10px;"> Número de referencia: </div>
    <div class="reference" style="padding: 10px;"> <?php echo $reference; ?> </div>
    <div class="expira-en-numero" style="padding: 10px;"> Expira el <?php echo $expire_in_days; ?></div>
</div>

<div class="form-row form-row-second netpay-required-field" style="
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    align-items: center;">
    <img src = "<?php echo $plugin_dir;?>/oxxopay/ic_oxxopay.svg" style = "width: 300px;height: 70px;" alt = "NetPay Oxxo Pay" />
</div>

<div class="form-row form-row-wide netpay-required-field" style="text-align: left">
    <ol class="steps">
        <li> Visita tu tienda OXXO más cercana y pide al cajero pagar en efectivo con OXXO Pay.</li>
        <li> Dale tu número de referencia y revisa que la información en pantalla sea correcta.</li>
        <li> Paga en efectivo*</li>
    </ol>
</div>

<div class="form-row form-row-wide netpay-required-field">
<div class="te-hemos-enviado-una" style="text-align: left; right:80px; padding: 5px;">
    También te enviamos esta información por correo.
</div>
</div>

<div class="form-row form-row-wide netpay-required-field">
<div class="al-pagar-en-el-co" style="text-align: left; right:80px; padding: 5px;">
  * Se te cobrará una comisión establecida por OXXO.
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
    </div>
    <div style='clear:both'></div>