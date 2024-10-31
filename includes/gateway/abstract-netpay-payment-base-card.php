<?php
defined( 'ABSPATH' ) or die( 'No direct script access allowed.' );

require_once dirname( __FILE__ ) . '/class-netpay-payment.php';

/**
 * @since 4.22.0
 */
abstract class NetPay_Payment_Base_Card extends NetPay_Payment
{
	use Charge_Request_Builder;

	const PAYMENT_ACTION_AUTHORIZE         = 'manual_capture';
	const PAYMENT_ACTION_AUTHORIZE_CAPTURE = 'auto_capture';

    /**
	 * @inheritdoc
	 */
	public function charge($order_id, $order)
	{
		$token = isset( $_POST['netpay_token'] ) ? wc_clean( $_POST['netpay_token'] ) : '';
		$card_id = isset( $_POST['card_id'] ) ? wc_clean( $_POST['card_id'] ) : '';

		if (empty($token) && empty($card_id)) {
			throw new Exception(__( 'Please select an existing card or enter new card information.', 'netpay'));
		}

		$user = $order->get_user();
		$netpay_customer_id = $this->getNetPayCustomerId($user);

		// Saving card.
		$saveCustomerCard = $_POST['netpay_save_customer_card'];
		if (isset($saveCustomerCard) && !empty($saveCustomerCard) && empty($card_id)) {
			$cardDetails = $this->saveCard($netpay_customer_id, $token, $order_id, $user->ID);
			$netpay_customer_id = $cardDetails['customer_id'];
			$card_id = $cardDetails['card_id'];
		}

		$data = $this->prepareChargeData($order_id, $order, $netpay_customer_id, $card_id, $token);
		return NetPayCharge::create($data);
	}

	/**
	 * get netpay customer id from user
	 * @param object|null $user
	 */
	private function getNetPayCustomerId($user) {
		if(empty($user)) {
			return null;
		}
		return $this->is_test() ? $user->test_netpay_customer_id : $user->live_netpay_customer_id;
	}

	/**
	 * Prepare request data to create a charge
	 * @param string $order_id
	 * @param object $order
	 * @param string $netpay_customer_id
	 * @param string $card_id
	 * @param string $token
	 */
	private function prepareChargeData($order_id, $order, $netpay_customer_id, $card_id, $token)
	{
		$currency = $order->get_currency();
		$data = [
			'amount' => NetPay_Money::to_subunit($order->get_total(), $currency),
			'currency' => $currency,
			'description' => 'WooCommerce Order id ' . $order_id,
			'return_uri' => $this->get_redirect_url('netpay_callback', $order_id, $order),
			'metadata' => $this->get_metadata(
				$order_id,
				[ 'secure_form_enabled' => $this->getSecureFormState()]
			),
		];

		$netpay_settings = NetPay_Setting::instance();

		if ($netpay_settings->is_dynamic_webhook_enabled()) {
			$data = array_merge($data, [
				'webhook_endpoints' => [ NetPay_Util::get_webhook_url() ],
			]);
		}

		if (!empty($netpay_customer_id) && ! empty($card_id)) {
			$data['customer'] = $netpay_customer_id;
			$data['card'] = $card_id;
		} else {
			$data['card'] = $token;
		}

		// Set capture status (otherwise, use API's default behaviour)
		if (self::PAYMENT_ACTION_AUTHORIZE_CAPTURE === $this->payment_action) {
			$data['capture'] = true;
		} else if (self::PAYMENT_ACTION_AUTHORIZE === $this->payment_action) {
			$data['capture'] = false;
		}

		return $data;
	}

	/**
	 * Returns the the secure form state in yes/not format
	 */
	private function getSecureFormState()
	{
		// tracking the embedded form adoption
		$netpayCardGateway = new NetPay_Payment_Creditcard();
		$secureFormEnabled = $netpayCardGateway->get_option('secure_form_enabled');
		return (boolean)$secureFormEnabled ? 'yes' : 'no';
	}

	/**
	 * Saving card
	 * 
	 * @param string $netpay_customer_id
	 * @param string $token
	 * @param string $order_id
	 * @param string $user_id
	*/
	public function saveCard($netpay_customer_id, $token, $order_id, $user_id)
	{
		if (empty($token)) {
			throw new Exception(__(
				'Unable to process the card. Please make sure that the information is correct, or contact our support team if you have any questions.', 'netpay'
			));
		}

		try {
			$customer = new NetPay_Customer;
			$customer_data = [
				"description" => "WooCommerce customer " . $user_id,
				"card" => $token
			];

			if (empty($netpay_customer_id)) {
				$customer_data = $customer->create($user_id, $order_id, $customer_data);

				return [
					'customer_id' => $customer_data['customer_id'],
					'card_id' => $customer_data['card_id']
				];
			}

			try {
				$customerCard = new NetPayCustomerCard;

				$card = $customerCard->create($netpay_customer_id, $token);

				return [
					'customer_id' => $netpay_customer_id,
					'card_id' => $card['id']
				];
			} catch(\Exception $e) {
				$errors = $e->getNetPayError();

				if($errors['object'] === 'error' && strtolower($errors['code']) !== 'not_found') {
					throw $e;
				}

				// Saved customer ID is not found so we create a new customer and save the customer ID
				$customer_data = $customer->create($user_id, $order_id, $customer_data);

				return [
					'customer_id' => $customer_data['customer_id'],
					'card_id' => $customer_data['card_id']
				];
			}
		} catch (Exception $e) {
			error_log($e->getMessage());
			throw new Exception($e->getMessage());
		}
	}

	/**
	 * @inheritdoc
	 */
	public function result( $order_id, $order, $charge ) {
		if ( NetPay_Charge::is_failed( $charge ) ) {
            $this->payment_failed( NetPay_Charge::get_error_message( $charge ) );
		}

		// If 3-D Secure feature is enabled, redirecting user out to a 3rd-party credit card authorization page.
		if ( self::STATUS_PENDING === $charge['status'] && ! $charge['authorized'] && ! $charge['paid'] && ! empty( $charge['authorize_uri'] ) ) {
			$order->add_order_note(
				sprintf(
					__( 'NetPay Payments: Processing a 3-D Secure payment, redirecting buyer to %s', 'netpay' ),
					esc_url( $charge['authorize_uri'] )
				)
			);

			return array(
				'result'   => 'success',
				'redirect' => $charge['authorize_uri'],
			);
		}

		switch ( $this->payment_action ) {
			case self::PAYMENT_ACTION_AUTHORIZE:
				$success = NetPay_Charge::is_authorized( $charge );
				if ( $success ) {
					$order->add_order_note(
						sprintf(
							wp_kses(
								__( 'NetPay Payments: Payment processing.<br/>An amount of %1$s %2$s has been authorized', 'netpay' ),
								array( 'br' => array() )
							),
							$order->get_total(),
							$order->get_currency()
						)
					);
					$this->order->update_meta_data( 'is_awaiting_capture', 'yes' );
					$order->payment_complete();
				}

				break;

			case self::PAYMENT_ACTION_AUTHORIZE_CAPTURE:
				$success = NetPay_Charge::is_paid( $charge );
				if ( $success ) {
					$order->add_order_note(
						sprintf(
							wp_kses(
								__( 'NetPay Payments: Payment successful.<br/>An amount of %1$s %2$s has been paid', 'netpay' ),
								array( 'br' => array() )
							),
							$order->get_total(),
							$order->get_currency()
						)
					);
					$order->payment_complete();
				}

				break;

			default:
				// Default behaviour is, check if it paid first.
				$success = NetPay_Charge::is_paid( $charge );

				// Then, check is authorized after if the first condition is false.
				if ( ! $success ) {
					$success = NetPay_Charge::is_authorized( $charge );
				}
					
				break;
		}

		if ( ! $success ) {
            $this->payment_failed(__(
				'Note that your payment may have already been processed. Please contact our support team if you have any questions.',
				'netpay'
			));
		}

		// Remove cart
		WC()->cart->empty_cart();
		return array (
			'result'   => 'success',
			'redirect' => $this->get_return_url( $order )
		);
	}

    /**
	 * Register all required javascripts
	 */
	public function netpay_scripts() {
		if ( is_checkout() && $this->is_available() ) {
			wp_enqueue_script(
				'netpay-js',
				NetPay::NETPAY_JS_LINK,
				[ 'jquery' ],
				NETPAY_WOOCOMMERCE_PLUGIN_VERSION,
				true
			);

			wp_enqueue_script(
				'embedded-js',
				plugins_url( '../../assets/javascripts/netpay-embedded-card.js', __FILE__ ),
				[],
				NETPAY_WOOCOMMERCE_PLUGIN_VERSION,
				true
			);

			wp_enqueue_script(
				'netpay-payment-form-handler',
				plugins_url( '../../assets/javascripts/netpay-payment-form-handler.js', __FILE__ ),
				[ 'netpay-js' ],
				NETPAY_WOOCOMMERCE_PLUGIN_VERSION,
				true
			);

			wp_localize_script(
				'netpay-payment-form-handler',
				'netpay_params',
				$this->getParamsForJS()
			);
		}
	}

	/**
	 * Parameters to be passed directly to the JavaScript file.
	 */
	public function getParamsForJS()
	{
		$netpayCardGateway = new NetPay_Payment_Creditcard();

		return [
			'key'                            => $this->public_key(),
			'required_card_name'             => __(
				"Cardholder's name is a required field",
				'netpay'
			),
			'required_card_number'           => __(
				'Card number is a required field',
				'netpay'
			),
			'required_card_expiration_month' => __(
				'Card expiry month is a required field',
				'netpay'
			),
			'required_card_expiration_year'  => __(
				'Card expiry year is a required field',
				'netpay'
			),
			'required_card_security_code'    => __(
				'Card security code is a required field',
				'netpay'
			),
			'invalid_card'                   => __(
				'Invalid card.',
				'netpay'
			),
			'no_card_selected'               => __(
				'Please select a card or enter a new one.',
				'netpay'
			),
			'cannot_create_token'            => __(
				'Unable to proceed to the payment.',
				'netpay'
			),
			'cannot_connect_api'             => __(
				'Currently, the payment provider server is undergoing maintenance.',
				'netpay'
			),
			'retry_checkout'                 => __(
				'Please place your order again in a couple of seconds.',
				'netpay'
			),
			'cannot_load_netpayjs'            => __(
				'Cannot connect to the payment provider.',
				'netpay'
			),
			'check_internet_connection'      => __(
				'Please make sure that your internet connection is stable.',
				'netpay'
			),
			'expiration date cannot be in the past' => __(
				'expiration date cannot be in the past',
				'netpay'
			),
			'expiration date cannot be in the past and number is invalid' => __(
				'expiration date cannot be in the past and number is invalid',
				'netpay'
			),
			'expiration date cannot be in the past, number is invalid, and brand not supported (unknown)' => __(
				'expiration date cannot be in the past, number is invalid, and brand not supported (unknown)',
				'netpay'
			),
			'number is invalid and brand not supported (unknown)' => __(
				'number is invalid and brand not supported (unknown)',
				'netpay'
			),
			'expiration year is invalid, expiration date cannot be in the past, number is invalid, and brand not supported (unknown)' => __(
				'expiration year is invalid, expiration date cannot be in the past, number is invalid, and brand not supported (unknown)',
				'netpay'
			),
			'expiration month is not between 1 and 12, expiration date is invalid, number is invalid, and brand not supported (unknown)' => __(
				'expiration month is not between 1 and 12, expiration date is invalid, number is invalid, and brand not supported (unknown)',
				'netpay'
			),
			'secure_form_enabled'	=> (boolean)$netpayCardGateway->get_option('secure_form_enabled')
		];
	}
}
