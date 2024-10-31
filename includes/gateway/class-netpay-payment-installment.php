<?php
defined( 'ABSPATH' ) or die( 'No direct script access allowed.' );
// define( 'CARDINAL_ONECONNECT_PLUGIN_FILE', __FILE__ );

class NetPay_Payment_Installment extends NetPay_Payment {

    /**
     * @var string
     */
    private $password;
    private $payment_action;

    public function __construct() {
		parent::__construct();

		$this->id                 = 'netpay_installment';
		$this->has_fields         = true;
		$this->method_title       = __( 'Meses sin intereses', 'netpay' );
		$this->method_description = wp_kses(
			__( 'Aplica cobros a meses sin intereses rápido y fácil.', 'netpay' ),
			array( 'strong' => array() )
		);
		$this->supports           = array( 'products', 'refunds' );

		$this->password = 'W4Eyaz5YVkkBxebacFPny5PXnm';

		$this->init_form_fields();
		//$this->init_settings();

		$this->title                = $this->get_option( 'title' );
		$this->description          = $this->get_option( 'description' );
		$this->payment_action       = $this->get_option( 'payment_action' );
		$this->restricted_countries = array( 'MX');

		add_action( 'woocommerce_api_' . $this->id . '_callback', 'NetPay_Callback::execute' );
		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'netpay_scripts_installment' ) );
		add_action( 'woocommerce_order_action_' . $this->id . '_charge_capture', array( $this, 'process_capture' ) );
		add_action( 'woocommerce_order_action_' . $this->id . '_sync_payment', array( $this, 'sync_payment' ) );

		/** @deprecated 3.0 */
		add_action( 'woocommerce_api_wc_gateway_' . $this->id, 'NetPay_Callback::execute' );

		$this->init_actions();
	}

	/**
     * Hooks a function on to a specific woocommerce action.
     */
    private function init_actions() {
        add_action(
            'woocommerce_api_' . strtolower(get_class($this)),
            array($this, 'check_netpay_installment_response')
		);

		add_filter( 'woocommerce_default_address_fields' , array($this, 'custom_override_default_address_fields') );

		add_filter('woocommerce_billing_fields', array($this, 'custom_billing_fields'));

		add_filter( 'woocommerce_thankyou_order_received_text', array($this, 'netpay_installment_thank_you_title'), 20, 2 );
		
	}

	// Our hooked in function - $address_fields is passed via the filter!
    function custom_override_default_address_fields( $address_fields ) {
        $address_fields['first_name']['custom_attributes'] = array(
            'maxlength' => 35
        );
        $address_fields['last_name']['custom_attributes'] = array(
            'maxlength' => 35
        );
        $address_fields['address_1']['custom_attributes'] = array(
            'maxlength' => 50
        );
        $address_fields['address_2']['custom_attributes'] = array(
            'maxlength' => 50
        );
        $address_fields['city']['custom_attributes'] = array(
            'maxlength' => 90
        );
        $address_fields['state']['custom_attributes'] = array(
            'maxlength' => 30
        );
        $address_fields['postcode']['custom_attributes'] = array(
            'maxlength' => 20
        );

        return $address_fields;
    }

    function custom_billing_fields( $fields ) {
        $fields['billing_email']['custom_attributes'] = array(
            'maxlength' => 50
        );

        $fields['billing_address_2']['required'] = true;
        $fields['billing_phone']['custom_attributes'] = array(
            'maxlength' => 15
        );
    
        return $fields;
	}

	/**
	 * @see WC_Settings_API::init_form_fields()
	 * @see woocommerce/includes/abstracts/abstract-wc-settings-api.php
	 */
	function init_form_fields() {
		$this->form_fields = array_merge(
			array(
				'enabled' => array(
					'title'   => __( 'Enable/Disable', 'netpay' ),
					'type'    => 'checkbox',
					'label'   => __( '', 'netpay' ),
					'default' => 'no'
				),
	
				'title' => array(
					'title'       => __( 'Title', 'netpay' ),
					'type'        => 'text',
					'description' => __( 'Nombre del método de pago durante el cobro.', 'netpay' ),
					'default'     => __( 'Meses sin intereses', 'netpay' ),
				),
	
				'description' => array(
					'title'       => __( 'Description', 'netpay' ),
					'type'        => 'textarea',
					'description' => __( 'Descripción del método de pago.', 'netpay' )
				),
			),
			array(
				'advanced' => array(
					'title'       => __( 'Advance Settings', 'netpay' ),
					'type'        => 'title'
				),
				'accept_visa' => array(
					'title'       => __( 'Supported card icons', 'netpay' ),
					'type'        => 'checkbox',
					'label'       => NetPay_Card_Image::get_visa_image(),
					'css'         => NetPay_Card_Image::get_css(),
					'default'     => NetPay_Card_Image::get_visa_default_display()
				),
				'accept_mastercard' => array(
					'type'        => 'checkbox',
					'label'       => NetPay_Card_Image::get_mastercard_image(),
					'css'         => NetPay_Card_Image::get_css(),
					'default'     => NetPay_Card_Image::get_mastercard_default_display()
				),
				'accept_amex' => array(
					'type'        => 'checkbox',
					'label'       => NetPay_Card_Image::get_amex_image(),
					'css'         => NetPay_Card_Image::get_css(),
					'default'     => NetPay_Card_Image::get_amex_default_display(),
					'description' => wp_kses(
						__( 'This only controls the icons displayed on the checkout page.<br />It is not related to card processing on NetPay payment gateway.', 'netpay' ),
						array( 'br' => array() )
					)
				),
				'promotion' => array(
					'type' => 'checkbox',
					'title' => 'Meses sin intereses',
					'label' => 'Habilitar/Deshabilitar',
					'default' => 'no'
				),
				'promotion_products' => array(
					'title' => '',
					'type' => 'multiselect',
					'description' => 'Selecciona los productos a los cuales les aplicarás los meses sin intereses',
					'options'       => $this->get_products(),
					'default' => '',
					'css'     => 'height:150px;',
				),
				'promotion_3' => array(
					'type' => 'checkbox',
					'title' => '3 meses sin intereses',
					'label' => 'Habilitar/Deshabilitar',
					'default' => 'yes'
				),
				'promotion_6' => array(
					'type' => 'checkbox',
					'title' => '6 meses sin intereses',
					'label' => 'Habilitar/Deshabilitar',
					'default' => 'yes'
				),
				'promotion_9' => array(
					'type' => 'checkbox',
					'title' => '9 meses sin intereses',
					'label' => 'Habilitar/Deshabilitar',
					'default' => 'yes'
				),
				'promotion_12' => array(
					'type' => 'checkbox',
					'title' => '12 meses sin intereses',
					'label' => 'Habilitar/Deshabilitar',
					'default' => 'yes'
				),
				'promotion_18' => array(
					'type' => 'checkbox',
					'title' => '18 meses sin intereses',
					'label' => 'Habilitar/Deshabilitar',
					'default' => 'yes'
				),
			)
		);
	}

	/**
     * Get the products that already created.
     */
	 public function get_products()
	 {
		$products = array();
		$post_count = $this->wp_get_productcat_postcount();
		if($post_count <= \NetPay\NetPayConfig::$MAX_PRODUCTS) {
		 	$get_posts = json_decode(json_encode(get_posts( array( 'post_type' => 'product', 'posts_per_page' => -1, "post_status" => "publish" ))), true);
		 	$get_product_variation = json_decode(json_encode(get_posts( array( 'post_type' => 'product_variation', 'posts_per_page' => -1, "post_status" => "publish" ))), true);
 
		 	for($i=0;$i<count($get_product_variation);$i++) {
				$products[$get_product_variation[$i]['ID']] = $get_product_variation[$i]['post_title'];
		 	}
			for($i=0;$i<count($get_posts);$i++) {
				$exist = false;
			 	for($j=0;$j<count($get_product_variation);$j++) {
					if($get_posts[$i]['ID'] == $get_product_variation[$j]['post_parent']) {
						$exist = true;
					}
			 	}
			 	if(!$exist) {
					$products[$get_posts[$i]['ID']] = $get_posts[$i]['post_title'];
				}
		 	}
		}
		else {
			$products = array("-1"=>"Todos los productos.");
		}
		return $products;
	 }

	 function wp_get_productcat_postcount() {
		$args = array(
		  'post_type'     => 'product',
		  'post_status'   => 'publish',
		  'posts_per_page' => (\NetPay\NetPayConfig::$MAX_PRODUCTS + 1),  //show all -1
		);
	
		$query = new WP_Query( $args);
		return (int)$query->post_count;
	}

	function netpay_installment_form() {
    	$viewData['amount'] = WC()->cart->total;
        $lang_options = \NetPay\NetPayFunctions::get_lang_options();

		$total = (int)(WC()->cart->total);
        $meses_sin_interes_original = \NetPay\NetPayFunctions::promotion_options(
			$this->get_option('promotion'),
			$this->get_option('promotion_3'),
			$this->get_option('promotion_6'),
			$this->get_option('promotion_9'),
			$this->get_option('promotion_12'),
			$this->get_option('promotion_18')
		);
        $meses_sin_interes = array();
        $items = WC()->cart->get_cart();

        $products_to_apply = array();
        $counter = 0;
        $one_payment = array(
            "number" => 0,
            "lang" => $lang_options['form_fields']['promotion']['months_without_interest_0'],
            "amount" => 0
        );

		$products = $this->get_option('promotion_products');
        if($products != null && $this->get_option('promotion')  == 'yes') {
			if(is_array($products) && !in_array("-1", $products)) {
            	foreach($items as $item => $values) { 
					$_product_id = (string) $values['data']->get_id();
            	    if(is_array($products) && in_array($_product_id, $products)) {
            	        $products_to_apply[$counter] = $_product_id;
            	        $counter++;
            	    }
            	    else {
            	        $product = wc_get_product( $values['data']->get_id());
            	    ?>
            	    <div class="netpay-alert-info">
            	        <span class="netpay-closebtn" onclick="this.parentElement.style.display='none';">&times;</span>
            	        <?php _e('- El producto '.$product->get_title().' NO aplica a meses sin intereses.', 'netpay'); ?>
            	    </div>
            	    <?php
            	    }
            	}
			}
        }    

        for($i=0 ; $i<count($meses_sin_interes_original) ; $i++) {
			if($meses_sin_interes_original[$i]['amount'] <= $total ) {
				array_push($meses_sin_interes, $meses_sin_interes_original[$i]);
			}
		}

        $msi_options = array_reverse($meses_sin_interes, true);
        array_push($msi_options, $one_payment);

        if(count($products_to_apply) != count($items) && $this->get_option('promotion') == 'yes') {
            if(is_array($products) && !in_array("-1", $products)) {
			?>
            <div class="netpay-alert-info">
                <span class="netpay-closebtn" onclick="this.parentElement.style.display='none';">&times;</span>
                <?php _e('- El pago se realizará en una sola exhibición. Para comprar a meses sin intereses, todos los productos del carrito deben tener activa la promoción. Te sugerimos realizar compras separadas para aprovechar los MSI.', 'netpay'); ?>
            </div>
            <?php
            unset($msi_options);
            $msi_options = array("0" => $one_payment);
			}
        }

        $viewData['installment_promotions'] = $msi_options;
		$viewData['count'] = count($msi_options);

		if(count($meses_sin_interes_original) > 1) {
			$viewData['minimum_amount'] = $meses_sin_interes_original[0]["amount"];
		}

        if($this->is_test()) {
            ?>
            <div id="netpay-installment-woocommerce-warning">
                <?php _e('- MODO PRUEBAS ACTIVADO. Si realizas transacciones no se enviarán al banco emisor, utiliza la tarjeta 5200 0000 0000 0007, una fecha de expiración válida, cualquier cvv y la cuenta de correo accept@netpay.com.mx para una transacción aprobada.', 'netpay'); ?>
            </div>
            <?php
        }

		NetPay_Util::render_view( 'templates/payment/form-installment.php', $viewData );
	}

	/**
	 * @see WC_Payment_Gateway::payment_fields()
	 * @see woocommerce/includes/abstracts/abstract-wc-payment-gateway.php
	 */
	
	public function payment_fields() {
		wc_clear_notices();
		$this->netpay_installment_form();
	}	  


	/**
	 * @inheritdoc
	 */
	public function charge( $order_id, $order ) {
        \NetPay\NetPayFunctions::custom_field_update_order_meta($order_id, '_netpay_payment_method', 'netpay_installment');

        $netpay_installment_number   = isset( $_POST['netpay_installment_number'] ) ? wc_clean( $_POST['netpay_installment_number'] ) : '';
		$netpay_installment_expiration_card   = isset( $_POST['netpay_installment_expiration_card'] ) ? wc_clean( $_POST['netpay_installment_expiration_card'] ) : '';
		$netpay_installment_security_code   = isset( $_POST['netpay_installment_security_code'] ) ? wc_clean( $_POST['netpay_installment_security_code'] ) : '';
		$netpay_installment_devicefingerprint = isset( $_POST['netpay_installment_devicefingerprint'] ) ? wc_clean( $_POST['netpay_installment_devicefingerprint'] ) : '';
		$netpay_installment_promotion = isset( $_POST['netpay_installment_promotion'] ) ? wc_clean( $_POST['netpay_installment_promotion'] ) : '';
		$netpay_installment_promotion_hidden = isset( $_POST['netpay_installment_promotion_hidden'] ) ? wc_clean( $_POST['netpay_installment_promotion_hidden'] ) : '';
		$netpay_card_installment_reference_id = isset( $_POST['netpay_card_installment_reference_id'] ) ? wc_clean( $_POST['netpay_card_installment_reference_id'] ) : '';
		$netpay_card_installment_httpBrowserColorDepth = isset( $_POST['netpay_card_installment_httpBrowserColorDepth'] ) ? wc_clean( $_POST['netpay_card_installment_httpBrowserColorDepth'] ) : '';
		$netpay_card_installment_httpBrowserJavaEnabled = isset( $_POST['netpay_card_installment_httpBrowserJavaEnabled'] ) ? wc_clean( $_POST['netpay_card_installment_httpBrowserJavaEnabled'] ) : '';
		$netpay_card_installment_httpBrowserJavaScriptEnabled = isset( $_POST['netpay_card_installment_httpBrowserJavaScriptEnabled'] ) ? wc_clean( $_POST['netpay_card_installment_httpBrowserJavaScriptEnabled'] ) : '';
		$netpay_card_installment_httpBrowserLanguage = isset( $_POST['netpay_card_installment_httpBrowserLanguage'] ) ? wc_clean( $_POST['netpay_card_installment_httpBrowserLanguage'] ) : '';
		$netpay_card_installment_httpBrowserScreenHeight = isset( $_POST['netpay_card_installment_httpBrowserScreenHeight'] ) ? wc_clean( $_POST['netpay_card_installment_httpBrowserScreenHeight'] ) : '';
		$netpay_card_installment_httpBrowserScreenWidth = isset( $_POST['netpay_card_installment_httpBrowserScreenWidth'] ) ? wc_clean( $_POST['netpay_card_installment_httpBrowserScreenWidth'] ) : '';
		$netpay_card_installment_httpBrowserTimeDifference = isset( $_POST['netpay_card_installment_httpBrowserTimeDifference'] ) ? wc_clean( $_POST['netpay_card_installment_httpBrowserTimeDifference'] ) : '';
		$netpay_card_installment_deviceChannel = isset( $_POST['netpay_card_installment_deviceChannel'] ) ? wc_clean( $_POST['netpay_card_installment_deviceChannel'] ) : '';

		$cardTypes = $this->getCardTypes();
		$cardScheme = \NetPay\NetPayFunctions::getCardScheme(\NetPay\NetPayFunctions::replace_only_numbers($netpay_installment_number));
		if($cardScheme == 'amex' && strlen(\NetPay\NetPayFunctions::replace_only_numbers($netpay_installment_number) < 15)) {
			throw new Exception( __( 'No. tarjeta inválido, deben ser 15-16 dígitos.', 'netpay' ) );
		}
		if(($cardScheme == 'visa' || $cardScheme == 'mastercard') && strlen(\NetPay\NetPayFunctions::replace_only_numbers($netpay_installment_number) < 16)) {
			throw new Exception( __( 'No. tarjeta inválido, deben ser 15-16 dígitos.', 'netpay' ) );
		}

		if (!in_array($cardScheme, $cardTypes)) {
			throw new Exception( __( 'No. tarjeta inválido, sólo se aceptan tarjetas ' . implode(",", $this->getCardTypesTitle()), 'netpay' ) );
		}

		$lunhCheck = \NetPay\NetPayFunctions::isValidCard(\NetPay\NetPayFunctions::replace_only_numbers($netpay_installment_number));
		if(!$lunhCheck || empty($lunhCheck) || $lunhCheck == null) {
			throw new Exception( __( 'No. tarjeta inválido.', 'netpay' ) );
		}

		$expiry_card = explode("/", $netpay_installment_expiration_card);
		$netpay_installment_expiration_month = $expiry_card[0];
		$netpay_installment_expiration_year = $expiry_card[1];

		$month = date('m');
		$year = date("Y");

		if((int)$netpay_installment_expiration_year < (int)$year-2000) {
			throw new Exception( __( 'Fecha de vencimiento inválida, debe tener el formato mm/aa y debe ser posterior a la actual.', 'netpay' ) );
		}
		else if((int)$netpay_installment_expiration_year == (int)$year-2000 && (int)$netpay_installment_expiration_month < $month) {
			throw new Exception( __( 'Fecha de vencimiento inválida, debe tener el formato mm/aa y debe ser posterior a la actual.', 'netpay' ) );
		}
		
		if($cardScheme == 'amex' && strlen($netpay_installment_security_code) <> 4) {
			throw new Exception( __( 'Código de seguridad inválido, deben ser 4 dígitos.', 'netpay' ) );
		}
		if($cardScheme != 'amex' && strlen($netpay_installment_security_code) <> 3) {
			throw new Exception( __( 'Código de seguridad inválido, deben ser 3 dígitos.', 'netpay' ) );
		}


		if ( empty( $netpay_installment_number )
			&& empty( $netpay_installment_expiration_card )
			&& empty( $netpay_installment_security_code ) ) {
			throw new Exception( __( 'Por favor llena todos los valores de la tarjeta.', 'netpay' ) );
		}
		else if ( isset( $netpay_installment_number )
		&& isset( $netpay_installment_expiration_card )
		&& isset( $netpay_installment_security_code ) ) {
			try {
				$request_token = array(
            		"cardNumber" => \NetPay\NetPayFunctions::replace_only_numbers($netpay_installment_number),
            		"expMonth" => $netpay_installment_expiration_month,
            		"expYear" => $netpay_installment_expiration_year,
            		"cvv2" => $netpay_installment_security_code,
            		"cardHolderName" => 'NoReal Name',
            		"deviceFingerPrint" => $netpay_installment_devicefingerprint,
					"ipAddress" => $order->get_customer_ip_address()
				);

				\NetPay\NetPayConfig::init($this->is_test() );
				$get_token = \NetPay\Api\NetPayToken::post(NETPAY_PUBLIC_KEY, $request_token);
				$token = $get_token['result']['token'];

				if(isset($netpay_installment_promotion) && $netpay_installment_promotion > 0 && $netpay_installment_promotion_hidden == '1'){
					$installments = $netpay_installment_promotion; //null=no MSI, 3=3 MSI, 6=6 MSI, 9=9 MSI, 12=12 MSI, 18=18 MSI
				}
				else{
					$installments = null; //null=no MSI, 3=3 MSI, 6=6 MSI, 9=9 MSI, 12=12 MSI, 18=18 MSI
				}
				
				$order_data = $order->get_data();

				$country = version_compare( WC()->version, '3.0.0', '>=' ) ? $order_data['billing']['country'] : $order->billing_country;
            	if(empty($country)) {
                	$country = 'MX';
            	}

        		$billing = array(
            		'billing_city' => \NetPay\NetPayFunctions::replace_caracters(version_compare( WC()->version, '3.0.0', '>=' ) ? $order_data['billing']['city'] : $order->billing_city),
            		'billing_country' => $country,
            		'billing_first_name' => \NetPay\NetPayFunctions::replace_caracters(version_compare( WC()->version, '3.0.0', '>=' ) ? $order_data['billing']['first_name'] : $order->billing_first_name),
            		'billing_last_name' => \NetPay\NetPayFunctions::replace_caracters(version_compare( WC()->version, '3.0.0', '>=' ) ? $order_data['billing']['last_name'] : $order->billing_last_name),
            		'billing_email' => \NetPay\NetPayFunctions::replace_caracters(version_compare( WC()->version, '3.0.0', '>=' ) ? $order_data['billing']['email'] : $order->billing_email),
            		'billing_phone' => \NetPay\NetPayFunctions::replace_caracters(str_replace("+52", "", version_compare( WC()->version, '3.0.0', '>=' ) ? $order_data['billing']['phone'] : $order->billing_phone)),
            		'billing_postcode' => version_compare( WC()->version, '3.0.0', '>=' ) ? $order_data['billing']['postcode']: $order->billing_postcode,
            		'billing_state' => \NetPay\NetPayFunctions::replace_caracters(version_compare( WC()->version, '3.0.0', '>=' ) ? $order_data['billing']['state']: $order->billing_state),
            		'billing_address_1' => \NetPay\NetPayFunctions::replace_caracters(version_compare( WC()->version, '3.0.0', '>=' ) ? $order_data['billing']['address_1'] : $order->billing_address_1),
            		'billing_address_2' => \NetPay\NetPayFunctions::replace_caracters(version_compare( WC()->version, '3.0.0', '>=' ) ? $order_data['billing']['address_2']: $order->billing_address_2),
            		'reference' => \NetPay\NetPayFunctions::replace_caracters(version_compare( WC()->version, '3.0.0', '>=' ) ? $order->get_id() : $order->id),
				);
				
				$shipping_country = version_compare( WC()->version, '3.0.0', '>=' ) ? $order_data['shipping']['country']: $order->shipping_country;
				$shipping_phone = str_replace("+52", "", version_compare( WC()->version, '3.0.0', '>=' ) ? $order_data['billing']['phone'] : $order->billing_phone);
				if(empty($shipping_country)) {
					$shipping_phone = "";
				}

        		$shipping = array( //optional, for virtual products it must be empty
            		'shipping_city' => \NetPay\NetPayFunctions::replace_caracters(version_compare( WC()->version, '3.0.0', '>=' ) ? $order_data['shipping']['city']: $order->shipping_city),
            		'shipping_country' => $shipping_country,
            		'shipping_first_name' => \NetPay\NetPayFunctions::replace_caracters(version_compare( WC()->version, '3.0.0', '>=' ) ? $order_data['shipping']['first_name']: $order->shipping_first_name),
            		'shipping_last_name' => \NetPay\NetPayFunctions::replace_caracters(version_compare( WC()->version, '3.0.0', '>=' ) ? $order_data['shipping']['last_name']: $order->shipping_last_name),
            		'shipping_phone' => \NetPay\NetPayFunctions::replace_caracters($shipping_phone),
            		'shipping_postcode' => version_compare( WC()->version, '3.0.0', '>=' ) ? $order_data['shipping']['postcode']: $order->shipping_postcode,
            		'shipping_state' => \NetPay\NetPayFunctions::replace_caracters(version_compare( WC()->version, '3.0.0', '>=' ) ? $order_data['shipping']['state']: $order->shipping_state),
            		'shipping_address_1' => \NetPay\NetPayFunctions::replace_caracters(version_compare( WC()->version, '3.0.0', '>=' ) ? $order_data['shipping']['address_1']: $order->shipping_address_1),
            		'shipping_address_2' => \NetPay\NetPayFunctions::replace_caracters(version_compare( WC()->version, '3.0.0', '>=' ) ? $order_data['shipping']['address_2']: $order->shipping_address_2),
            		'shipping_method' => \NetPay\NetPayFunctions::replace_caracters($order->get_shipping_method()),
				);

				$zoneAware = array(
					'clientIPAdress' => null
				);

				\NetPay\NetPayConfig::init($this->is_test() );
				$zoneResponse = \NetPay\Api\NetPayZoneAware::get(NETPAY_PUBLIC_KEY);
				if ($zoneResponse['result']['ip'] != null) { 
					$zoneAware['clientIPAdress'] =  $zoneResponse['result']['ip'];
				} else {
					$zoneAware['clientIPAdress'] = '0.0.0.0';
				};

				$installment_deviceInformation = array(
					"httpBrowserColorDepth" => $netpay_card_installment_httpBrowserColorDepth, 
					"httpBrowserJavaEnabled" => $netpay_card_installment_httpBrowserJavaEnabled, 
					"httpBrowserJavaScriptEnabled" => $netpay_card_installment_httpBrowserJavaScriptEnabled, 
					"httpBrowserLanguage" => $netpay_card_installment_httpBrowserLanguage, 
					"httpBrowserScreenHeight" => $netpay_card_installment_httpBrowserScreenHeight, 
					"httpBrowserScreenWidth" => $netpay_card_installment_httpBrowserScreenWidth, 
					"httpBrowserTimeDifference" => $netpay_card_installment_httpBrowserTimeDifference, 
					"deviceChannel" => $netpay_card_installment_deviceChannel 
				);

				$request_checkout = array(
            		'description' => 'Cobro de la orden ' . version_compare( WC()->version, '3.0.0', '>=' ) ? $order->get_id() : $order->id,
            		'source' => $token,
            		'amount' => $order->get_total(),
            		"billing" => \NetPay\NetPayBill::format($billing),
            		"shipping" => \NetPay\NetPayShip::format($shipping),
                    'referenceID' => $netpay_card_installment_reference_id,
					'zoneAware' => $zoneAware,
					'deviceFingerPrint' => $netpay_installment_devicefingerprint,
					'sessionId' => $netpay_installment_devicefingerprint,
					'deviceInformation' => $installment_deviceInformation
        		);

				$user_agent = $_SERVER["HTTP_USER_AGENT"];
					
				\NetPay\NetPayConfig::init($this->is_test() );
				return \NetPay\Api\NetPayCheckout::post(NETPAY_SECRET_KEY, $request_checkout, $installments, $user_agent);
						
			} catch (Exception $e) {
        		$description = $e->getMessage();
        		echo json_encode(
            		array(
                		"status"=>"ERROR", 
                		"result"=>$description
            		));
    		}
		}
	}

	/**
	 * @inheritdoc
	 */
	public function result( $order_id, $order, $charge ) {
        if(isset($charge['result']['installments']) && $charge['result']['installments'] != null) {
            $order->add_order_note(
                sprintf(
                    __( 'NetPay: aplicaron %s MSI', 'netpay' ),
                    $charge['result']['installments']['plan']['count']
                )
            );
        }
        $order->add_order_note(
            sprintf(
                __( 'NetPay: TransactionTokenId: %s', 'netpay' ),
                $charge['result']['transactionTokenId']
            )
        );

        $order->add_order_note(
            sprintf(
                __( 'NetPay: cardPrefix: %s', 'netpay' ),
                $charge['result']['paymentSource']['card']['cardPrefix']
            )
        );

        $order->add_order_note(
            sprintf(
                __( 'NetPay: lastFourDigits: %s', 'netpay' ),
                $charge['result']['paymentSource']['card']['lastFourDigits']
            )
        );
		if($charge['result']['status'] == "success") {
			$order->add_order_note(
				sprintf(
					wp_kses(
						__( 'NetPay: Pago realizado.<br/>Por la cantidad de %1$s %2$s', 'netpay' ),
						array( 'br' => array() )
					),
					$order->get_total(),
					$order->get_currency()
				)
			);
			$order->payment_complete();

			\NetPay\NetPayFunctions::custom_field_update_order_meta($order->id, '_transaction_token_id', $charge['result']['transactionTokenId']);

			\NetPay\NetPayConfig::init($this->is_test() );
			$status_confirm = \NetPay\Api\NetPayTransaction::get(NETPAY_SECRET_KEY, $charge['result']['transactionTokenId']);
			if($status_confirm['result']['status'] == "DONE") {
				$order->add_order_note(
					sprintf(
						__( 'NetPay: authCode: %s', 'netpay' ),
						$status_confirm['result']['authCode']
					)
				);

				$order->add_order_note(
					sprintf(
						__( 'NetPay: bankName: %s', 'netpay' ),
						$status_confirm['result']['bankName']
					)
				);
			}
			// Remove cart
			WC()->cart->empty_cart();
			return array (
				'result'   => 'success',
				'redirect' => $this->get_return_url( $order )
			);
		}
		else if($charge['result']['status'] == "review") {

			$this->status = $charge['result']['status'];
        	$this->responseCode = $charge['result']['threeDSecureResponse']['responseCode'];
        	$this->acsUrl = $charge['result']['threeDSecureResponse']['acsUrl'];
        	$this->paReq = $charge['result']['threeDSecureResponse']['paReq'];
        	$this->authenticationTransactionID = $charge['result']['threeDSecureResponse']['authenticationTransactionID'];
			$this->transactionTokenId = $charge['result']['transactionTokenId'];

			if (isset($this->acsUrl)) {
				wp_enqueue_script('netpay3ds-confirm');
				$this->redirect = $this->get_return_url( $order );
				//$this->payment = $order->payment_complete();
                $this->payment = null;
				$url_site = get_site_url();
				$get_folder_url = plugins_url( '../../assets/javascripts/netpay3ds-confirm.js', __FILE__ );
				$messages = "<script src=".$get_folder_url." type='text/javascript'>
					</script> 
					<script>
						Cardinal.OneConnect.start('{$this->is_test()}', '{$this->status}', '{$this->responseCode}', '{$this->acsUrl}', '{$this->paReq}', '{$this->authenticationTransactionID}', '{$this->transactionTokenId}', '{$this->redirect}', '{$this->payment}', '{$order->id}', '{$url_site}');
					</script>";
				wp_send_json( array( 'messages' => $messages ) );
			} else {
				$order->add_order_note(
					sprintf(
						wp_kses(
							__( 'NetPay: Pago realizado.<br/>Por la cantidad de %1$s %2$s', 'netpay' ),
							array( 'br' => array() )
						),
						$order->get_total(),
						$order->get_currency()
					)
				);

				\NetPay\NetPayFunctions::custom_field_update_order_meta($order->id, '_transaction_token_id', $charge['result']['transactionTokenId']);
	
				\NetPay\NetPayConfig::init($this->is_test() );
				$status_confirm = \NetPay\Api\NetPayTransaction::get(NETPAY_SECRET_KEY, $charge['result']['transactionTokenId']);
				if($status_confirm['result']['status'] == "WAIT_THREEDS") {
						\NetPay\NetPayConfig::init($this->is_test() );
						$confirm_service = \NetPay\Api\NetPayConfirm::post(NETPAY_SECRET_KEY, $charge['result']['transactionTokenId'], null);
						if ($confirm_service['result']['status'] == 'success') {
							$order->add_order_note(
								sprintf(
									__( 'NetPay: authCode: %s', 'netpay' ),
									$status_confirm['result']['authCode']
								)
							);
	
							$order->add_order_note(
								sprintf(
									__( 'NetPay: bankName: %s', 'netpay' ),
									$status_confirm['result']['bankName']
								)
							);
	
							$order->payment_complete();
	
							// Remove cart
							WC()->cart->empty_cart();
							return array (
								'result'   => 'success',
								'redirect' => $this->get_return_url( $order )
							);
	
						} else {
							return $this->payment_failed( __( \NetPay\NetPayFunctions::friendly_response($charge['result']['error']), 'netpay' ) );
						}
				} else if($status_confirm['result']['status'] == "FAILED") {
					return $this->payment_failed( __( \NetPay\NetPayFunctions::friendly_response($charge['result']['error']), 'netpay' ) );
				}
			}
			
		}
		else if($charge['result']['status'] == "failed") {
			return $this->payment_failed( __( \NetPay\NetPayFunctions::friendly_response($charge['result']['error']), 'netpay' ) );
		}
		else if($charge['result']['status'] == "rejected") {
			return $this->payment_failed( __( \NetPay\NetPayFunctions::friendly_response($charge['result']['error']), 'netpay' ) );
		}
		else if($charge['result']['status'] == "insecure") {
			return $this->payment_failed( __( \NetPay\NetPayFunctions::friendly_response($charge['result']['error']), 'netpay' ) );
		}
		else {
			return $this->payment_failed( __( \NetPay\NetPayFunctions::friendly_response("Error al procesar el carrito"), 'netpay' ) );
		}

	}

	/**
     * Check for valid NetPay transaction server callback.
     */
    public function check_netpay_installment_response() {
		$order_id = filter_var ( $_GET['order_id'], FILTER_SANITIZE_NUMBER_INT);
		$redirect = filter_var ( $_GET['redirect'], FILTER_SANITIZE_STRING);

		$order = new WC_Order($order_id);
		$order->update_status( 'pending');
		\NetPay\NetPayConfig::init($this->is_test() );
		if(!empty($_GET['transaction_token'])) {
			$transaction_token = filter_var ( $_GET['transaction_token'], FILTER_SANITIZE_STRING);
    		if(!empty($transaction_token)) {
				$status = \NetPay\Api\NetPayTransaction::get(NETPAY_SECRET_KEY, $transaction_token);

				\NetPay\NetPayFunctions::custom_field_update_order_meta($order_id, '_transaction_token_id', $transaction_token);

        		if($status['result']['status'] == "CHARGEABLE") {
					\NetPay\NetPayConfig::init($this->is_test() );
					$procesorTransactionId = \NetPay\NetPayFunctions::custom_field_get_order_meta($order_id, '_procesor_transacrion_id');
					$status_confirm = \NetPay\Api\NetPayTransaction::get(NETPAY_SECRET_KEY, $transaction_token);
					if($status_confirm['result']['status'] == "DONE") {
						$order->add_order_note(
							sprintf(
								wp_kses(
									__( 'NetPay: Pago realizado.<br/>por la cantidad de %1$s %2$s', 'netpay' ),
									array( 'br' => array() )
								),
								$order->get_total(),
								$order->get_currency()
							)
						);

						$order->add_order_note(
							sprintf(
								__( 'NetPay: authCode: %s', 'netpay' ),
								$status_confirm['result']['authCode']
							)
						);

						$order->add_order_note(
							sprintf(
								__( 'NetPay: TransactionTokenId: %s', 'netpay' ),
								$status_confirm['result']['transactionTokenId']
							)
						);
			
						$order->add_order_note(
							sprintf(
								__( 'NetPay: lastFourDigits: %s', 'netpay' ),
								$status_confirm['result']['spanRouteNumber']
							)
						);
			
						$order->add_order_note(
							sprintf(
								__( 'NetPay: bankName: %s', 'netpay' ),
								$status_confirm['result']['bankName']
							)
						);
						
						$order->payment_complete();
					}
					else if($status_confirm['result']['status'] == "WAIT_THREEDS") {
						\NetPay\NetPayConfig::init($this->is_test() );
						$confirm_service = \NetPay\Api\NetPayConfirm::post(NETPAY_SECRET_KEY, $transaction_token, $procesorTransactionId);
						if ($confirm_service['result']['status'] == 'success') {
							$order->add_order_note(
								sprintf(
									wp_kses(
										__( 'NetPay: Pago realizado.<br/>por la cantidad de %1$s %2$s', 'netpay' ),
										array( 'br' => array() )
									),
									$order->get_total(),
									$order->get_currency()
								)
							);
	
							$order->add_order_note(
								sprintf(
									__( 'NetPay: authCode: %s', 'netpay' ),
									$status_confirm['result']['authCode']
								)
							);
	
							$order->add_order_note(
								sprintf(
									__( 'NetPay: TransactionTokenId: %s', 'netpay' ),
									$status_confirm['result']['transactionTokenId']
								)
							);
				
							$order->add_order_note(
								sprintf(
									__( 'NetPay: lastFourDigits: %s', 'netpay' ),
									$status_confirm['result']['spanRouteNumber']
								)
							);
				
							$order->add_order_note(
								sprintf(
									__( 'NetPay: bankName: %s', 'netpay' ),
									$status_confirm['result']['bankName']
								)
							);
							
							$order->payment_complete();
						}
					}
					else if($status_confirm['result']['status'] == "REJECT") {
						$this->payment_failed( __( 'Tu pago fué recahzado, favor de intentar nuevamente.', 'netpay' ) );
						//$order->update_status( 'failed');
					}
					else {
						$this->payment_failed( __( 'Por favor, póngase en contacto con nuestro equipo de soporte soporte@netpay.com.mx si tiene alguna pregunta.', 'netpay' ) );
						//$order->update_status( 'failed');
					}
        		}
        		else if($status['result']['status'] == "REJECT") {
					$this->payment_failed( __( 'Tu pago fué recahzado, favor de intentar nuevamente.', 'netpay' ) );
					//$order->update_status( 'failed');
				}
				else if($status['result']['status'] == "DONE") {
					$order->update_status( 'processing' );
            		$this->payment_failed( __( 'Tenga en cuenta que es posible que su pago ya se haya procesado. Por favor, póngase en contacto con nuestro equipo de soporte soporte@netpay.com.mx si tiene alguna pregunta.', 'netpay' ) );
        		}
        		else {
					//$order->update_status( 'failed' );
            		$this->payment_failed( __( 'Por favor, póngase en contacto con nuestro equipo de soporte soporte@netpay.com.mx si tiene alguna pregunta.', 'netpay' ) );
		        }
    		}
		}

		$decrypted_redirect = \NetPay\NetPayFunctions::decrypt($redirect, $this->password);
		wp_redirect($decrypted_redirect);
		exit();
			
		// Remove cart
		//WC()->cart->empty_cart();
		/*return array (
			'result'   => 'fail',
			'redirect' => $this->get_return_url( $order )
		);*/
	}

	function netpay_installment_thank_you_title( $thank_you_title, $order ) {
        if($order == null) {
            global $wp;
            $order_id = $wp->query_vars['order-received'];
        }
        else {
            $order_id = $order->get_id();
        }

        if($order_id > 0 &&  get_post_meta($order_id, '_netpay_payment_method', true) == 'netpay_installment') {
            if (!get_post_meta($order_id, '_netpay_thankyou_action_done', true) || get_post_meta($order_id, '_netpay_thankyou_action_done', true) == 'false') {
                \NetPay\NetPayFunctions::custom_field_update_order_meta($order_id, '_netpay_thankyou_action_done', 'true');

                $transaction_token_id = get_post_meta($order_id, '_transaction_token_id', true);
                \NetPay\NetPayConfig::init($this->is_test());
                $status = \NetPay\Api\NetPayTransaction::get(NETPAY_SECRET_KEY, $transaction_token_id);

                if ($status['result']['status'] == 'DONE') {
                    echo "<p style='padding: 20px; background-color: #04AA6D; color: white; margin-bottom: 15px;'>" . \NetPay\NetPayFunctions::friendly_response($status['result']['responseMsg']) . "</p>";
                } else if ($status['result']['status'] == 'WAIT_THREEDS') {
                    echo "<p style='padding: 20px; background-color: #04AA6D; color: white; margin-bottom: 15px;'>" . \NetPay\NetPayFunctions::friendly_response($status['result']['responseMsg']) . "</p>";
                } else {
                    echo "<p style='padding: 20px; background-color: #f44336; color: white; margin-bottom: 15px;'>" . \NetPay\NetPayFunctions::friendly_response($status['result']['responseMsg']) . "</p>";
                }
            }
            else {
                \NetPay\NetPayFunctions::custom_field_update_order_meta($order_id, '_netpay_thankyou_action_done', 'false');
            }
        }
	}

	/**
	 * Capture an authorized charge.
	 *
	 * @param  WC_Order $order WooCommerce's order object
	 *
	 * @return void
	 *
	 * @see    WC_Meta_Box_Order_Actions::save( $post_id, $post )
	 * @see    woocommerce/includes/admin/meta-boxes/class-wc-meta-box-order-actions.php
	 */
	public function process_capture( $order ) {
		$this->load_order( $order );

		try {
			$charge = NetPayCharge::retrieve( $this->get_charge_id_from_order() );
			$charge->capture();

			if ( ! NetPayPluginHelperCharge::isPaid( $charge ) ) {
				throw new Exception( NetPay()->translate( $charge['failure_message'] ) );
			}

			$this->order()->add_order_note(
				sprintf(
					wp_kses(
						__( 'NetPay: Payment successful (manual capture).<br/>An amount of %1$s %2$s has been paid', 'netpay' ),
						array( 'br' => array() )
					),
					$this->order()->get_total(),
					$this->order()->get_currency()
				)
			);
			$this->order()->payment_complete();
		} catch ( Exception $e ) {
			$this->order()->add_order_note(
				sprintf(
					wp_kses( __( 'NetPay: Payment failed (manual capture).<br/>%s', 'netpay' ), array( 'br' => array() ) ),
					$e->getMessage()
				)
			);
			$this->order()->update_status( 'failed' );
		}
	}

	/**
	 * Get icons of all supported card types
	 *
	 * @see WC_Payment_Gateway::get_icon()
	 */
	public function get_icon() {
		$icon = '';

		//       these options to check outside this class.
		$card_icons['accept_amex']       = $this->get_option( 'accept_amex' );
		$card_icons['accept_mastercard'] = $this->get_option( 'accept_mastercard' );
		$card_icons['accept_visa']       = $this->get_option( 'accept_visa' );

        $plugin_dir = NETPAY_PLUGIN_URL . 'assets/images';
        $icon .= "<img src = '$plugin_dir/secured_by_netpay.svg' width='68px' height='38px' alt = 'Secured by NetPay' />";

        if ( NetPay_Card_Image::is_amex_enabled( $card_icons ) ) {
            $icon .= NetPay_Card_Image::get_amex_image()." ";
        }

        if ( NetPay_Card_Image::is_mastercard_enabled( $card_icons ) ) {
            $icon .= NetPay_Card_Image::get_mastercard_image()." ";
        }

		if ( NetPay_Card_Image::is_visa_enabled( $card_icons ) ) {
			$icon .= NetPay_Card_Image::get_visa_image()." ";
		}

		return empty( $icon ) ? '' : apply_filters( 'woocommerce_gateway_icon', $icon, $this->id );
	}

	public function getCardTypes() {
		$cardType = array();
		$card_icons['accept_amex']       = $this->get_option( 'accept_amex' );
		$card_icons['accept_mastercard'] = $this->get_option( 'accept_mastercard' );
		$card_icons['accept_visa']       = $this->get_option( 'accept_visa' );

		if ( NetPay_Card_Image::is_visa_enabled( $card_icons ) ) {
			array_push($cardType, "visa");
		}

		if ( NetPay_Card_Image::is_mastercard_enabled( $card_icons ) ) {
			array_push($cardType, "mastercard");
		}

		if ( NetPay_Card_Image::is_amex_enabled( $card_icons ) ) {
			array_push($cardType, "amex");
		}

		return $cardType;
	}

	public function getCardTypesTitle() {
		$cardType = array();
		$card_icons['accept_amex']       = $this->get_option( 'accept_amex' );
		$card_icons['accept_mastercard'] = $this->get_option( 'accept_mastercard' );
		$card_icons['accept_visa']       = $this->get_option( 'accept_visa' );

		if ( NetPay_Card_Image::is_visa_enabled( $card_icons ) ) {
			array_push($cardType, "Visa");
		}

		if ( NetPay_Card_Image::is_mastercard_enabled( $card_icons ) ) {
			array_push($cardType, " MasterCard");
		}

		if ( NetPay_Card_Image::is_amex_enabled( $card_icons ) ) {
			array_push($cardType, " American Express");
		}

		return $cardType;
	}

	/**
	 * Register all required javascripts
	 */
	public function netpay_scripts_installment() {
		if ( is_checkout() && $this->is_available() ) {
			wp_enqueue_script( 'netpay-installment-form-handler', plugins_url( '../../assets/javascripts/netpay-installment-form-handler.js', __FILE__ ), array( ), NETPAY_WOOCOMMERCE_PLUGIN_VERSION, true );
			wp_enqueue_script( 'cleave', plugins_url( '../../assets/javascripts/cleave.js', __FILE__ ), array(  ), NETPAY_WOOCOMMERCE_PLUGIN_VERSION, true );
			wp_enqueue_script( 'netpay_devicefingerprint', plugins_url( '../../assets/javascripts/netpay_devicefingerprint.js', __FILE__ ), array(  ), NETPAY_WOOCOMMERCE_PLUGIN_VERSION, true );
			wp_enqueue_script( 'netpay_bin_lookup', plugins_url( '../../assets/javascripts/netpay_bin_lookup.js', __FILE__ ), array(  ), NETPAY_WOOCOMMERCE_PLUGIN_VERSION, true );

			wp_enqueue_script( 'netpay3ds', "https://cdn.netpay.mx/js/dev/netpay3ds.js", array(  ), NETPAY_WOOCOMMERCE_PLUGIN_VERSION, true );


			$netpay_params_installment = array(
				'test_mode'						 => $this->is_test(),
				'org_id'						 => ($this->is_test()) ? '45ssiuz3' : '9ozphlqx',
				'key'                            => $this->public_key(),
				'accept_visa'                    => $this->get_option( 'accept_visa' ),
				'accept_mastercard'              => $this->get_option( 'accept_mastercard' ),
				'accept_amex'                    => $this->get_option( 'accept_amex' ),
				'card_types'                     => $this->getCardTypes(),
				'amount'						 => WC()->cart->get_cart_total(),
				'card_types_title'               => $this->getCardTypesTitle(),
				'required_card_name'             => __( 'Cardholder\'s name is a required field', 'netpay' ),
				'required_card_number'           => __( 'Card number is a required field', 'netpay' ),
				'required_card_expiration_month' => __( 'Card expiry month is a required field', 'netpay' ),
				'required_card_expiration_year'  => __( 'Card expiry year is a required field', 'netpay' ),
				'required_card_security_code'    => __( 'Card security code is a required field', 'netpay' ),
				'invalid_card'                   => __( 'Invalid card.', 'netpay' ),
				'no_card_selected'               => __( 'Please select a card or enter a new one.', 'netpay' ),
				'cannot_create_token'            => __( 'Unable to proceed to the payment.', 'netpay' ),
				'cannot_connect_api'             => __( 'Currently, the payment provider server is undergoing maintenance.', 'netpay' ),
				'retry_checkout'                 => __( 'Please place your order again in a couple of seconds.', 'netpay' ),
				'cannot_load_netpayjs'           => __( 'Cannot connect to the payment provider.', 'netpay' ),
				'check_internet_connection'      => __( 'Please make sure that your internet connection is stable.', 'netpay' ),
			);

			wp_localize_script( 'netpay-installment-form-handler', 'netpay_params_installment', $netpay_params_installment );
		}
	}
}
