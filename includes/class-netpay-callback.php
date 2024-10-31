<?php

defined( 'ABSPATH' ) || exit;

/**
 * @since 4.0
 */
class NetPay_Callback {
	/**
	 * @var \WC_Abstract_Order
	 */
	protected $order;

	/**
	 * @var \NetPayCharge
	 */
	protected $charge;

	/**
	 * @param \WC_Abstract_Order $order
	 */
	public function __construct( $order ) {
		$this->order = $order;
		if ( ! $this->order || ! $this->order instanceof WC_Abstract_Order ) {
			$this->invalid_result();
		}
	}

	public static function execute()
	{
		$order_id = isset( $_GET['order_id'] ) ? sanitize_text_field( $_GET['order_id'] ) : null;
		$order = wc_get_order( $order_id );

		if(!RequestHelper::validate_request($order->get_meta('token'))) {
			return wp_redirect( wc_get_checkout_url() );
		}

		$callback = new self( $order );
		$callback->validate();
	}

	/**
	 * Sometimes cancelling a transaction does not updates the status on the NetPay backend
	 * which causes the status to be pending even thought the transaction was cancelled.
	 * To avoid this random issue of status being 'Pending` when it should have been 'Cancelled',
	 * we are adding a delay of half a second to avoid random
	 *
	 * @param string $transactionId
	 */
	private function fetchCharge($transactionId)
	{
		$retryNo = 1;
		$maxRetry = 5;

		do {
			$charge = NetPayCharge::retrieve($transactionId);

			if('pending' !== $charge['status']) {
				return $charge;
			}

			$retryNo++;
			usleep(500000);
		} while($retryNo <= $maxRetry);

		return $charge;
	}

	public function validate() {
		$this->order->add_order_note( __( 'NETPAY: Validating the payment result...', 'netpay' ) );

		try {
			$this->charge = $this->fetchCharge($this->order->get_transaction_id());

			if ($this->hasShopeepayFailed()) {
				$this->payment_failed();
				return;
			}

			switch ( strtolower( $this->charge['status'] ) ) {
				case 'successful':
				case 'failed':
				case 'pending':
					$resolving_method = strtolower( 'payment_' . $this->charge['status'] );
					$this->$resolving_method();
					break;

				default:
					throw new Exception( __( 'Unrecognized NetPay Payments Charge status.', 'netpay' ) );
					break;
			}
		} catch ( Exception $e ) {
			$this->order->add_order_note(
				sprintf(
					wp_kses( __( 'NETPAY: Unable to validate the result.<br/>%s', 'netpay' ), array( 'br' => array() ) ),
					$e->getMessage()
				)
			);

			$this->invalid_result();
		}
	}

	private function hasShopeepayFailed()
	{
		$isPaymentMethodShopeepay = 'shopeepay' === $this->charge['source']['type'];
		$isChargePending = 'pending' === $this->charge['status'];
		return $isPaymentMethodShopeepay && $isChargePending;
	}

	/**
	 * Resolving a case of undefined charge status
	 */
	protected function invalid_result() {
		$message = __(
			'<strong>We cannot validate your payment result:</strong><br/>
			Note that your payment may have already been processed.<br/>
			Please contact our support team if you have any questions.',
			'netpay'
		);

		wc_add_notice( wp_kses( $message, array( 'br' => array(), 'strong' => array() ) ), 'error' );
		wp_redirect( wc_get_checkout_url() );
		exit;
	}

	/**
	 * Resolving a case of charge status: successful.
	 */
	protected function payment_successful() {
		$message = __( 'NETPAY: Payment successful.<br/>An amount of %1$s %2$s has been paid', 'netpay' );

		$this->order->payment_complete();
		$this->order->add_order_note(
			sprintf(
				wp_kses( $message, array( 'br' => array() ) ),
				$this->order->get_total(),
				$this->order->get_currency()
			)
		);

		WC()->cart->empty_cart();
		$this->order->update_meta_data( 'is_netpay_payment_resolved', 'yes' );
		$this->order->save();

		wp_redirect( $this->order->get_checkout_order_received_url() );
		exit;
	}

	/**
	 * Resolving a case of charge status: pending.
	 */
	protected function payment_pending() {
		if ( ! $this->charge['capture'] && $this->charge['authorized'] ) {
			// Card authorized case.
			$message = __(
				'NetPay Payments: The payment is being processed.<br/>
				An amount %1$s %2$s has been authorized.',
				'netpay'
			);

			$this->order->add_order_note(
				sprintf(
					wp_kses( $message, array( 'br' => array() ) ),
					$this->order->get_total(),
					$this->order->get_currency()
				)
			);
			$this->order->payment_complete();

			// Remove cart
			WC()->cart->empty_cart();
			$this->order->update_meta_data( 'is_netpay_payment_resolved', 'yes' );
			$this->order->update_meta_data( 'is_awaiting_capture', 'yes' );
			$this->order->save();

			wp_redirect( $this->order->get_checkout_order_received_url() );
			exit;
		}

		// Offsite case.
		$message = __(
			'NetPay Payments: The payment is being processed.<br/>
			Depending on the payment provider, this may take some time to process.<br/>
			Please do a manual \'Sync Payment Status\' action from the <strong>Order Actions</strong> panel, or check the payment status directly at the NetPay Payments Dashboard later.',
			'netpay'
		);

		$this->order->add_order_note( wp_kses( $message, array( 'br' => array(), 'strong' => array() ) ) );
		$this->order->update_status( 'on-hold' );
		$this->order->update_meta_data( 'is_netpay_payment_resolved', 'yes' );
		$this->order->save();

		wp_redirect( $this->order->get_checkout_order_received_url() );
		exit;
	}

	/**
	 * Resolving a case of charge status: failed.
	 */
	protected function payment_failed() {
		$message = __( "It seems we've been unable to process your payment properly:<br/>%s", 'netpay' );
		$failure_message = NetPay()->translate( $this->charge['failure_message'] );
		$failure_message .= ($this->charge['failure_code']) ?
			' (code: ' . $this->charge['failure_code'] . ')'
			: ' (code: Payment failed)'; // for shopeepay

		$this->order->add_order_note( sprintf( wp_kses( __( 'NETPAY: Payment failed.<br/>%s', 'netpay' ), array( 'br' => array() ) ), $failure_message ) );
		$this->order->update_status( 'failed' );
		$this->order->update_meta_data( 'is_netpay_payment_resolved', 'yes' );
		$this->order->save();

		wc_add_notice( sprintf( wp_kses( $message, array( 'br' => array() ) ), $failure_message ), 'error' );
		wp_redirect( wc_get_checkout_url() );
		exit;
	}
}
