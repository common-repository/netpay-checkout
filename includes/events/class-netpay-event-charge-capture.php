<?php

defined( 'ABSPATH' ) || exit;

/**
 * There are several cases that can trigger the 'charge.capture' event.
 *
 * =-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=
 * Credit Card
 * charge data in payload will be:
 *     [status: 'successful'], [authorized: 'true'], [paid: 'true']
 *
 */
class NetPay_Event_Charge_Capture extends NetPay_Event {
	/**
	 * @var string  of an event name.
	 */
	const EVENT_NAME = 'charge.capture';

	/**
	 * @inheritdoc
	 */
	public function validate() {
		if ( 'charge' !== $this->data['object'] || ! isset( $this->data['metadata']['order_id'] ) ) {
			return false;
		}

		if ( ! $this->order = wc_get_order( $this->data['metadata']['order_id'] ) ) {
			return false;
		}

		// Making sure that an event's charge id is identical with an order transaction id.
		if ( $this->order->get_transaction_id() !== $this->data['id'] ) {
			return false;
		}

		return true;
	}

	/**
	 * This `charge.capture` event is only being used
	 * to catch a manual-capture action that happens on 'NetPay Dashboard'.
	 * For on-store capture, it will be handled by NetPay_Payment::process_capture.
	 */
	public function resolve() {
		$this->order->add_order_note( __( 'NetPay Payments: Received charge.capture webhook event.', 'netpay' ) );
		$this->order->delete_meta_data( 'is_awaiting_capture');
		$this->order->save();

		switch ( $this->data['status'] ) {
			case 'failed':
				if ( $this->order->has_status( 'failed' ) ) {
					return;
				}

				$message         = __( 'NetPay Payments: Payment failed.<br/>%s', 'netpay' );
				$failure_message = NetPay()->translate( $this->data['failure_message'] ) . ' (code: ' . $this->data['failure_code'] . ')';
				$this->order->add_order_note(
					sprintf(
						wp_kses( $message, array( 'br' => array() ) ),
						$failure_message
					)
				);
				$this->order->update_status( 'failed' );
				break;

			case 'successful':
				$message = __( 'NetPay Payments: Payment successful.<br/>An amount %1$s %2$s has been paid', 'netpay' );

				$this->order->add_order_note(
					sprintf(
						wp_kses( $message, array( 'br' => array() ) ),
						$this->order->get_total(),
						$this->order->get_currency()
					)
				);

				if ( ! $this->order->has_status( 'processing' ) ) {
					$this->order->update_status( 'processing' );
				}
				break;

			default:
				throw new Exception('invalid charge status');
				break;
		}

		return;
	}
}
