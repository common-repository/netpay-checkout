<?php

/**
 * Plugin Name: NetPay Checkout
 * Plugin URI:  https://docs.netpay.com.mx/docs/woocommerce
 * Description: NetPay WooCommerce Gateway Plugin is a WordPress plugin designed specifically for WooCommerce. The plugin adds support for NetPay Checkout payment method to WooCommerce.
 * Version:     1.59.38
 * Author:      NetPay
 * Author URI:  https://netpay.mx
 * Text Domain: netpay
 * License:     MIT
 * License URI: https://opensource.org/licenses/MIT
 * Requires Plugins: woocommerce
 * Requires at least: 6.2
 * Tested up to: 6.6.2
 * WC requires at least: 8.9
 * WC tested up to: 9.3.3
 * Requires PHP: 7.4.30
 */
defined('ABSPATH') or die('No direct script access allowed.');

#[AllowDynamicProperties]
class NetPay
{
	/**
	 * NetPay plugin version number.
	 *
	 * @var string
	 */
	public $version = '1.59.38';

	/**
	 * The NetPay Instance.
	 *
	 * @since 3.0
	 *
	 * @var   \NetPay
	 */
	protected static $the_instance = null;

	/**
	 * @since 3.3
	 *
	 * @var   boolean
	 */
	protected static $can_initiate = false;

	CONST NETPAY_JS_LINK = 'https://cdn.netpay.co/netpay.js';

	/**
	 * @since  3.0
	 */
	public function __construct()
	{
		add_action('before_woocommerce_init', [$this, 'enable_hpos']);
		add_action('plugins_loaded', array($this, 'check_dependencies'));
		add_action('woocommerce_init', array($this, 'init'));

        add_action( 'init', array( $this, 'netpay_cash_ipn_listener' ));
        add_action( 'netpay_process_ipn_cash', array( $this, 'process_ipn' ) );

		add_action( 'woocommerce_blocks_loaded', [ $this, 'block_init' ] );

        do_action('netpay_initiated');
	}

	/**
	 * enable high performance order storage(HPOS) feature
	 */
	public function enable_hpos() {
		if (class_exists(\Automattic\WooCommerce\Utilities\FeaturesUtil::class)) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
				'custom_order_tables', 
				__FILE__, 
				true
			);

			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
				'cart_checkout_blocks',
				__FILE__,
				true
			);
		}
	}

    public static function process_ipn() {
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);

        \NetPay\NetPayConfig::init(NETPAY_TEST_MODE);
        if(array_key_exists('event', $data) && $data["event"] == "cep.paid") {
            NetPay\NetPayConfig::init(NetPay_Setting::instance()->is_test_mode() );
            $transaction = \NetPay\Api\NetPayTransaction::get(NETPAY_SECRET_KEY, $data["data"]["transactionId"]);
            if($transaction["result"]["status"] == "DONE"
                && $transaction["result"]["transactionTokenId"] == $data["data"]["transactionId"]
                && $transaction["result"]["amount"] == $data["data"]["amount"]) {
                $order_id = \NetPay\NetPayFunctions::get_post_id_by_transaction_id($transaction["result"]["transactionTokenId"]);
                $order = new WC_Order($order_id);
                $order->payment_complete();
            }
        } else if(array_key_exists('event', $data) &&  $data["event"] == "oxxopay.paid") {
            NetPay\NetPayConfig::init(NetPay_Setting::instance()->is_test_mode() );
            $transaction = \NetPay\Api\NetPayOxxoTransaction::get(NETPAY_SECRET_KEY, $data["data"]["transactionId"]);

            if($transaction["result"]["status"] == "C"
                && $transaction["result"]["transactionId"] == $data["data"]["transactionId"]
                && $transaction["result"]["amount"] == $data["data"]["amount"]) {

                $id_order = $data["data"]["merchantRefCode"];
                \NetPay\NetPayFunctions::custom_field_update_order_meta($id_order, '_transaction_token_id', $transaction["result"]["transactionId"]);
                $order = new WC_Order(intval($id_order));
                $order->add_order_note(
                    sprintf(
                        __( 'NetPay: TransactionTokenId: %s', 'netpay' ),
                        $transaction["result"]["transactionId"]
                    )
                );

                $order->payment_complete();
            }
        } else if (isset($data["processorTransactionId"])) {

            \NetPay\NetPayConfig::init(NetPay_Setting::instance()->is_test_mode() );
            $confirm_service = \NetPay\Api\NetPayConfirm::post(NETPAY_SECRET_KEY, $data['transactionTokenId'], $data["processorTransactionId"]);
            \NetPay\NetPayFunctions::custom_field_update_order_meta($data['orderId'], '_transaction_token_id', $data['transactionTokenId']);
            \NetPay\NetPayFunctions::custom_field_update_order_meta($data['orderId'], '_processor_transaction_id', $data["processorTransactionId"]);

            if ($confirm_service['result']['status'] == 'success') {
                $data['payment'];
                $order = new WC_Order($data['orderId']);
                $get_transaction = \NetPay\Api\NetPayTransaction::get(NETPAY_SECRET_KEY, $confirm_service['result']['transactionTokenId']);
                $order->add_order_note(
                    sprintf(
                        __( 'NetPay: authCode: %s', 'netpay' ),
                        $get_transaction['result']['authCode']
                    )
                );

                $order->add_order_note(
                    sprintf(
                        __( 'NetPay: bankName: %s', 'netpay' ),
                        $get_transaction['result']['bankName']
                    )
                );
                $order->payment_complete();
                // Remove cart
                WC()->cart->empty_cart();
                echo $data["redirect"];
                exit;
            } else {
                $message = wp_kses( __('Parece que no hemos podido procesar su pago correctamente: <br/>%s', 'netpay'), array( 'br' => array() ) );
                $order = new WC_Order($data['orderId']);
                if ( $order ) {
                    $order->update_status( 'failed' );
                    $reason = "Error al procesar el carrito";
                    $order->add_order_note( sprintf( __( 'NetPay: Payment failed, %s', 'netpay' ), $reason ) );
                    $order->save();
                }

                echo $data["redirect"];
                exit;
            }
        }
    }

    public function netpay_cash_ipn_listener() {
        if ( isset( $_GET['netpay-listener'] ) ) {

            $gateway = filter_var ( $_GET['netpay-listener'], FILTER_SANITIZE_STRING);

            /**
             * Handle a gateway's IPN.
             *
             * @since 1.0.0
             */
            do_action( 'netpay_process_ipn_' . $gateway );

            return true;
        }

        return false;
    }

	/**
	 * Notice for users informing about embedded form
	 */
    public function embedded_form_notice()
    {
        $this->netpayCardGateway = new NetPay_Payment_Creditcard();
        $secure_form_enabled = $this->netpayCardGateway->get_option('secure_form_enabled');

        // hide if user enables the embedded form.
        if (!(bool)$secure_form_enabled) {
            $translation = __('Mantén actualizado nuestro plugin. <a target="_blank" href="https://docs.netpay.com.mx/docs/woocommerce">Documentación</a>.', 'netpay');
            echo "<div class='notice notice-warning is-dismissible'><p><strong>NetPay Checkout:</strong> $translation</p></div>";
        }
    }

	/**
	 * get plugin assess url
	 */
	public static function get_assets_url() {
		return plugins_url('assets' , __FILE__);
	}

	/**
	 * Check if all dependencies are loaded
	 * properly before NetPay WooCommerce.
	 *
	 * @since  3.2
	 */
	public function check_dependencies()
	{
		if (!function_exists('WC')) {
			return;
		}

		static::$can_initiate = true;
	}

	/**
	 * @since  3.0
	 */
	public function init()
	{
		if (!static::$can_initiate) {
			add_action('admin_notices', array($this, 'init_error_messages'));
			return;
		}

		$this->load_plugin_textdomain();
		$this->include_classes();
		$this->define_constants();
		$this->register_post_types();
		$this->init_admin();
		$this->init_route();
		$this->register_payment_methods();
		$this->register_hooks();
		$this->register_ajax_actions();

		prepare_netpay_myaccount_panel();

		// adding action after all dependencies are loaded.
		if (static::$can_initiate) {
			// Moving here because the class used in the function could not be found on uninstall
			add_action('admin_notices', [$this, 'embedded_form_notice']);
			return;
		}
	}

	public function block_init()
	{
        require_once __DIR__ . '/includes/blocks/netpay-block.php';
		require_once __DIR__ . '/includes/blocks/netpay-block-config.php';
		require_once __DIR__ . '/includes/blocks/netpay-block-payments.php';
        require_once __DIR__ . '/includes/blocks/gateways/netpay-block-credit-card.php';
        require_once __DIR__ . '/includes/blocks/gateways/abstract-netpay-block-payment.php';
        require_once __DIR__ . '/includes/blocks/gateways/netpay-block-installment.php';

		NetPay_Block::init();
	}

	/**
	 * Callback to display message about activation error
	 *
	 * @since  3.2
	 */
	public function init_error_messages()
	{
?>
		<div class="error">
			<p><?php echo __('NetPay Checkout WooCommerce plugin requires <strong>WooCommerce</strong> to be activated.', 'netpay'); ?></p>
		</div>
<?php
	}

	/**
	 * Define NetPay necessary constants.
	 *
	 * @since 3.3
	 */
	private function define_constants()
	{
		global $wp_version;

		defined('NETPAY_WOOCOMMERCE_PLUGIN_VERSION') || define('NETPAY_WOOCOMMERCE_PLUGIN_VERSION', $this->version);
		defined('NETPAY_PUBLIC_KEY') || define('NETPAY_PUBLIC_KEY', $this->settings()->public_key());
		defined('NETPAY_SECRET_KEY') || define('NETPAY_SECRET_KEY', $this->settings()->secret_key());
        defined( 'NETPAY_TEST_MODE' ) || define( 'NETPAY_TEST_MODE', $this->settings()->is_test_mode() );
        defined( 'NETPAY_PLUGIN_DIR' ) || define( 'NETPAY_PLUGIN_DIR', dirname(__FILE__ ) . "/" );
        defined( 'NETPAY_PLUGIN_URL' ) || define( 'NETPAY_PLUGIN_URL', plugins_url( '', dirname( __FILE__ ) ) . "/netpay-checkout/" );
        defined( 'NETPAY_API_VERSION' ) || define( 'NETPAY_API_VERSION', '2020-11-24' );
        defined( 'NETPAY_USER_AGENT_SUFFIX' ) || define( 'NETPAY_USER_AGENT_SUFFIX', sprintf( 'NetPayWooCommerce/%s WordPress/%s WooCommerce/%s', NETPAY_WOOCOMMERCE_PLUGIN_VERSION, $wp_version, WC()->version ) );
	}

	/**
	 * @since 3.3
	 */
	private function include_classes()
	{
		defined('NETPAY_WOOCOMMERCE_PLUGIN_PATH') || define('NETPAY_WOOCOMMERCE_PLUGIN_PATH', __DIR__);

		require_once NETPAY_WOOCOMMERCE_PLUGIN_PATH . '/includes/class-netpay-queue-runner.php';
		require_once NETPAY_WOOCOMMERCE_PLUGIN_PATH . '/includes/class-netpay-queueable.php';
		require_once NETPAY_WOOCOMMERCE_PLUGIN_PATH . '/includes/backends/class-netpay-backend.php';
		require_once NETPAY_WOOCOMMERCE_PLUGIN_PATH . '/includes/backends/class-netpay-backend-installment.php';
		require_once NETPAY_WOOCOMMERCE_PLUGIN_PATH . '/includes/backends/class-netpay-backend-mobile-banking.php';
		require_once NETPAY_WOOCOMMERCE_PLUGIN_PATH . '/includes/backends/class-netpay-backend-fpx.php';
		require_once NETPAY_WOOCOMMERCE_PLUGIN_PATH . '/includes/classes/class-netpay-charge.php';
		require_once NETPAY_WOOCOMMERCE_PLUGIN_PATH . '/includes/classes/class-netpay-card-image.php';
		require_once NETPAY_WOOCOMMERCE_PLUGIN_PATH . '/includes/classes/class-netpay-image.php';
		require_once NETPAY_WOOCOMMERCE_PLUGIN_PATH . '/includes/classes/class-netpay-customer.php';
		require_once NETPAY_WOOCOMMERCE_PLUGIN_PATH . '/includes/classes/class-netpay-customer-card.php';
		require_once NETPAY_WOOCOMMERCE_PLUGIN_PATH . '/includes/events/class-netpay-event.php';
		require_once NETPAY_WOOCOMMERCE_PLUGIN_PATH . '/includes/events/class-netpay-event-charge-capture.php';
		require_once NETPAY_WOOCOMMERCE_PLUGIN_PATH . '/includes/events/class-netpay-event-charge-complete.php';
		require_once NETPAY_WOOCOMMERCE_PLUGIN_PATH . '/includes/events/class-netpay-event-charge-create.php';
		require_once NETPAY_WOOCOMMERCE_PLUGIN_PATH . '/includes/gateway/traits/sync-order-trait.php';
		require_once NETPAY_WOOCOMMERCE_PLUGIN_PATH . '/includes/gateway/traits/charge-request-builder-trait.php';

        require_once NETPAY_WOOCOMMERCE_PLUGIN_PATH . '/includes/gateway/class-netpay-payment.php';
		require_once NETPAY_WOOCOMMERCE_PLUGIN_PATH . '/includes/gateway/class-netpay-payment-creditcard.php';
		require_once NETPAY_WOOCOMMERCE_PLUGIN_PATH . '/includes/gateway/class-netpay-payment-installment.php';

		require_once NETPAY_WOOCOMMERCE_PLUGIN_PATH . '/includes/libraries/netpay-php/lib/NetPay.php';
		require_once NETPAY_WOOCOMMERCE_PLUGIN_PATH . '/includes/libraries/netpay-plugin/NetPay.php';
		require_once NETPAY_WOOCOMMERCE_PLUGIN_PATH . '/includes/class-netpay-ajax-actions.php';
		require_once NETPAY_WOOCOMMERCE_PLUGIN_PATH . '/includes/class-netpay-callback.php';
		require_once NETPAY_WOOCOMMERCE_PLUGIN_PATH . '/includes/class-netpay-capabilities.php';
		require_once NETPAY_WOOCOMMERCE_PLUGIN_PATH . '/includes/class-netpay-events.php';
		require_once NETPAY_WOOCOMMERCE_PLUGIN_PATH . '/includes/class-netpay-localization.php';
		require_once NETPAY_WOOCOMMERCE_PLUGIN_PATH . '/includes/class-netpay-money.php';
		require_once NETPAY_WOOCOMMERCE_PLUGIN_PATH . '/includes/class-netpay-payment-factory.php';
		require_once NETPAY_WOOCOMMERCE_PLUGIN_PATH . '/includes/class-netpay-rest-webhooks-controller.php';
		require_once NETPAY_WOOCOMMERCE_PLUGIN_PATH . '/includes/class-netpay-setting.php';
		require_once NETPAY_WOOCOMMERCE_PLUGIN_PATH . '/includes/class-netpay-wc-myaccount.php';
		require_once NETPAY_WOOCOMMERCE_PLUGIN_PATH . '/netpay-util.php';
		require_once NETPAY_WOOCOMMERCE_PLUGIN_PATH . '/includes/admin/class-netpay-admin-page.php';
		require_once NETPAY_WOOCOMMERCE_PLUGIN_PATH . '/includes/admin/class-netpay-page-card-form-customization.php';

        require_once NETPAY_WOOCOMMERCE_PLUGIN_PATH . '/includes/libraries/netpay-plugin/NetPay.php';
        require_once NETPAY_WOOCOMMERCE_PLUGIN_PATH . '/includes/libraries/netpay-php/lib/NetPay.php';
        require_once NETPAY_WOOCOMMERCE_PLUGIN_PATH . '/includes/libraries/netpay-php/init.php';
        require_once NETPAY_WOOCOMMERCE_PLUGIN_PATH . '/includes/gateway/class-netpay-payment-oxxo-pay.php';
	}

	/**
	 * @since  3.0
	 */
	protected function init_admin()
	{
		if (is_admin()) {
			require_once NETPAY_WOOCOMMERCE_PLUGIN_PATH . '/includes/class-netpay-admin.php';
			NetPay_Admin::get_instance()->init();
		}
	}

	/**
	 * @since  3.1
	 */
	protected function init_route()
	{
		add_action('rest_api_init', function () {
			$controllers = new NetPay_Rest_Webhooks_Controller;
			$controllers->register_routes();
		});
	}

	/**
	 * @since  3.0
	 */
	public function load_plugin_textdomain()
	{
		load_plugin_textdomain('netpay', false, plugin_basename(dirname(__FILE__)) . '/languages/');
	}

	/**
	 * @since  3.11
	 */
	public function register_payment_methods()
	{
		add_filter('woocommerce_payment_gateways', function ($methods) {
			return array_merge($methods, $this->payment_methods());
		});
	}

	/**
	 * @since  4.0
	 */
	public function register_hooks()
	{
		add_action('netpay_async_webhook_event_handler', 'NetPay_Queue_Runner::execute_webhook_event_handler', 10, 3);
	}

	/**
	 * @since  4.1
	 */
	public function register_ajax_actions()
	{
		add_action('wp_ajax_nopriv_fetch_order_status', 'NetPay_Ajax_Actions::fetch_order_status');
		add_action('wp_ajax_fetch_order_status', 'NetPay_Ajax_Actions::fetch_order_status');
	}

	/**
	 * Register necessary post-types
	 *
	 * @deprecated 3.0  NetPay-WooCommerce was once storing NetPay's charge id
	 *                  with WooCommerce's order id together in a
	 *                  customed-post-type, 'netpay_charge_items'.
	 *
	 *                  Since NetPay-WooCoomerce v3.0, now the plugin stores
	 *                  NetPay's charge id as a 'customed-post-meta' in the
	 *                  WooCommerce's 'order' post-type instead.
	 */
	public function register_post_types()
	{
		register_post_type(
			'netpay_charge_items',
			array(
				'supports' => array('title', 'custom-fields'),
				'label'    => 'NetPay Payments Charge Items',
				'labels'   => array(
					'name'          => 'NetPay Payments Charge Items',
					'singular_name' => 'NetPay Payments Charge Item'
				)
			)
		);
	}

	/**
	 * The NetPay Instance.
	 *
	 * @see    NetPay()
	 *
	 * @since  3.0
	 *
	 * @static
	 *
	 * @return \NetPay - The instance.
	 */
	public static function instance()
	{
		if (is_null(self::$the_instance)) {
			self::$the_instance = new self();
		}

		return self::$the_instance;
	}

	/**
	 * Get setting class.
	 *
	 * @since  3.4
	 *
	 * @return NetPay_Setting
	 */
	public function settings()
	{
		return NetPay_Setting::instance();
	}

	/**
	 * @since  4.0
	 *
	 * @return array of all the available payment methods
	 *               that NetPay WooCommerce supported.
	 */
	public function payment_methods()
	{
        \NetPay\NetPayConfig::init(NetPay_Setting::instance()->is_test_mode() );
        $config = \NetPay\Api\NetPayOxxoPayEnable::get($this->settings()->public_key());
        if(!is_null($config['result']) && array_key_exists('oxxoPay', $config['result']) && $config['result']['oxxoPay']["ennable"] === 1)
        {
            return NetPay_Payment_Factory::$payment_methods;
        }
        else {
            $payments =  NetPay_Payment_Factory::$payment_methods;
            return array_splice($payments, 0,2);
        }
	}

	/**
	 * L10n the given string.
	 *
	 * @since  4.1
	 *
	 * @return string
	 */
	public function translate($message)
	{
		return NetPay_Localization::translate($message);
	}
}

function NetPay()
{
	return NetPay::instance();
}

NetPay();
