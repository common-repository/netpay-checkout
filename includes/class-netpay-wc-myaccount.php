<?php
defined( 'ABSPATH' ) or die( 'No direct script access allowed.' );

if ( ! class_exists( 'NetPay_MyAccount' ) ) {
	#[AllowDynamicProperties]
	class NetPay_MyAccount
	{
		private static $instance;
		private $netpay_customer_id;

		public static function get_instance() {
			if ( ! self::$instance) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		public function __construct() {
			// prevent running directly without wooCommerce
			if ( ! class_exists( 'WC_Payment_Gateway' ) ) {
				return;
			}

			if ( is_user_logged_in() ) {
				$current_user = wp_get_current_user();
				$this->netpay_customer_id = NetPay()->settings()->is_test() ? $current_user->test_netpay_customer_id : $current_user->live_netpay_customer_id;
			}

			$this->customerCard = new NetPayCustomerCard;
			$this->netpayCardGateway = new NetPay_Payment_Creditcard();

			add_action( 'woocommerce_after_my_account', array( $this, 'init_panel' ) );
			add_action( 'wp_ajax_netpay_delete_card', array( $this, 'netpay_delete_card' ) );
			add_action( 'wp_ajax_netpay_create_card', array( $this, 'netpay_create_card' ) );
			add_action( 'wp_ajax_nopriv_netpay_delete_card', array( $this, 'no_op' ) );
			add_action( 'wp_ajax_nopriv_netpay_create_card', array( $this, 'no_op' ) );
		}

		/**
		 * Append NetPay Settings panel to My Account page
		 */
		public function init_panel() {
			if ( ! empty( $this->netpay_customer_id ) ) {
				try {
					$viewData['existing_cards'] = $this->customerCard->get($this->netpay_customer_id)['data'];
					$viewData['cardFormTheme'] = $this->netpayCardGateway->get_option('card_form_theme');
					$viewData['secure_form_enabled'] = (boolean)$this->netpayCardGateway->get_option('secure_form_enabled');
					$viewData['formDesign'] = NetPay_Page_Card_From_Customization::get_instance()->get_design_setting();
					$viewData['cardIcons'] = $this->netpayCardGateway->get_card_icons();
					$this->register_netpay_my_account_scripts();

					NetPay_Util::render_view( 'templates/myaccount/my-card.php', $viewData );
				} catch (Exception $e) {
					// nothing.
				}
			}
		}

		/**
		 * Register all javascripts
		 */
		public function register_netpay_my_account_scripts() {
			wp_enqueue_script(
				'netpay-js',
				NetPay::NETPAY_JS_LINK,
				array( 'jquery' ),
				WC_VERSION,
				true
			);

			wp_enqueue_script(
				'embedded-js',
				plugins_url( '/assets/javascripts/netpay-embedded-card.js', dirname( __FILE__ ) ),
				[],
				NETPAY_WOOCOMMERCE_PLUGIN_VERSION,
				true
			);

			wp_enqueue_script(
				'netpay-myaccount-card-handler',
				plugins_url( '/assets/javascripts/netpay-myaccount-card-handler.js', dirname( __FILE__ ) ),
				array( 'netpay-js' ),
				WC_VERSION,
				true
			);

			wp_localize_script(
				'netpay-myaccount-card-handler',
				'netpay_params',
				$this->getParamsForJS()
			);
		}

		/**
		 * Parameters to be passed directly to the JavaScript file.
		 */
		public function	getParamsForJS()
		{
			return [
				'key'                            => NetPay()->settings()->public_key(),
				'ajax_url'                       => admin_url( 'admin-ajax.php' ),
				'ajax_loader_url'                => plugins_url( '/assets/images/ajax-loader@2x.gif', dirname( __FILE__ ) ),
				'required_card_name'             => __( "Cardholder's name is a required field", 'netpay' ),
				'required_card_number'           => __( 'Card number is a required field', 'netpay' ),
				'required_card_expiration_month' => __( 'Card expiry month is a required field', 'netpay' ),
				'required_card_expiration_year'  => __( 'Card expiry year is a required field', 'netpay' ),
				'required_card_security_code'    => __( 'Card security code is a required field', 'netpay' ),
				'cannot_create_card'             => __( 'Unable to add a new card.', 'netpay' ),
				'cannot_connect_api'             => __( 'Currently, the payment provider server is undergoing maintenance.', 'netpay' ),
				'cannot_load_netpayjs'            => __( 'Cannot connect to the payment provider.', 'netpay' ),
				'check_internet_connection'      => __( 'Please make sure that your internet connection is stable.', 'netpay' ),
				'retry_or_contact_support'       => wp_kses(
					__( 'This incident could occur either from the use of an invalid card, or the payment provider server is undergoing maintenance.<br/>You may retry again in a couple of seconds, or contact our support team if you have any questions.', 'netpay' ),
					[ 'br' => [] ]
				),
				'expiration date cannot be in the past' => __( 'expiration date cannot be in the past', 'netpay' ),
				'expiration date cannot be in the past and number is invalid' => __( 'expiration date cannot be in the past and number is invalid', 'netpay' ),
				'expiration date cannot be in the past, number is invalid, and brand not supported (unknown)' => __( 'expiration date cannot be in the past, number is invalid, and brand not supported (unknown)', 'netpay' ),
				'number is invalid and brand not supported (unknown)' => __( 'number is invalid and brand not supported (unknown)', 'netpay' ),
				'expiration year is invalid, expiration date cannot be in the past, number is invalid, and brand not supported (unknown)' => __( 'expiration year is invalid, expiration date cannot be in the past, number is invalid, and brand not supported (unknown)', 'netpay' ),
				'expiration month is not between 1 and 12, expiration date is invalid, number is invalid, and brand not supported (unknown)' => __('expiration month is not between 1 and 12, expiration date is invalid, number is invalid, and brand not supported (unknown)', 'netpay'),
				'secure_form_enabled'	=> (boolean)$this->netpayCardGateway->get_option('secure_form_enabled')
			];
		}

		/**
		 * Public netpay_delete_card ajax hook
		 */
		public function netpay_delete_card()
		{
			$cardId = isset( $_POST['card_id'] ) ? wc_clean( $_POST['card_id'] ) : '';

			if ( empty( $cardId ) ) {
				NetPay_Util::render_json_error( 'card_id is required' );
				die();
			}

			$nonce = 'netpay_delete_card_' . $_POST['card_id'];

			if ( ! wp_verify_nonce( $_POST['netpay_nonce'], $nonce ) ) {
				NetPay_Util::render_json_error( 'Nonce verification failure' );
				die();
			}

			$cardDeleted = $this->customerCard->delete($cardId, $this->netpay_customer_id);

			echo json_encode([ 'deleted' => $cardDeleted ]);
			die();
		}

		/**
		 * Public netpay_create_card ajax hook
		 */
		public function netpay_create_card()
		{
			$token = isset ( $_POST['netpay_token'] ) ? wc_clean ( $_POST['netpay_token'] ) : '';

			if ( empty( $token ) ) {
				NetPay_Util::render_json_error( 'netpay_token is required' );
				die();
			}

			if ( ! wp_verify_nonce($_POST['netpay_nonce'], 'netpay_add_card' ) ) {
				NetPay_Util::render_json_error( 'Nonce verification failure' );
				die();
			}

			try {
				$card = $this->customerCard->create($this->netpay_customer_id, $token);
				echo json_encode( $card );
			} catch( Exception $e ) {
				echo json_encode( array(
					'object'  => 'error',
					'message' => $e->getMessage()
				) );
			}

			die();
		}

		/**
		 * No operation on no-priv ajax requests
		 */
		public function no_op() {
			exit( 'Not permitted' );
		}
	}
}

function prepare_netpay_myaccount_panel() {
	$netpay_myaccount = NetPay_MyAccount::get_instance();
}
