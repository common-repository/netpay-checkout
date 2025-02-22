<?php
defined( 'ABSPATH' ) or die( "No direct script access allowed." );

if ( ! class_exists( 'NetPay_Customer' ) ) {
    #[AllowDynamicProperties]
	class NetPay_Customer
    {
        private $customer;

        public function __construct()
        {
            $this->netpaySettings = NetPay()->settings();
        }

        public function get($customerId)
        {
            return NetPayCustomer::retrieve($customerId);
        }

        public function getOrCreate($customerId, array $createParams)
        {
            try {
                $this->customer = $this->get($customerId);
                $this->customer->update([
                    'card' => $createParams['customerData']['card']
                ]);
            } catch(\Exception $e) {
                $errors = $e->getNetPayError();

                if($errors['object'] === 'error' && strtolower($errors['code']) === 'not_found') {
                    return $this->create(
                        $createParams['userId'],
                        $createParams['orderId'],
                        $createParams['customerData']
                    );
                }

                throw $e;
            }
        }

        public function create($userId, $orderId, $customerData)
        {
            $this->customer = NetPayCustomer::create($customerData);

            if ( $this->customer['object'] == "error" ) {
                throw new Exception( $this->customer['message'] );
            }

            $customerIdMetaKey = $this->netpaySettings->is_test() ? 'test_netpay_customer_id' : 'live_netpay_customer_id';

            update_user_meta( $userId, $customerIdMetaKey, $this->customer['id'] );

            if ( 0 == sizeof( $this->customer['cards']['data'] ) ) {
                throw new Exception(
                    sprintf(
                        wp_kses(
                            __( 'Please note that you\'ve done nothing wrong - this is likely an issue with our store.<br/><br/>Feel free to try submitting your order again, or report this problem to our support team (Your temporary order id is \'%s\')', 'netpay' ),
                            array(
                                'br' => array()
                            )
                        ),
                        $orderId
                    )
                );
            }

            $cards = $this->customer->cards( array( 'order' => 'reverse_chronological' ) );

            return [
                'customer_id' => $this->customer['id'],
                'card_id' => $cards['data'][0]['id'] //use the latest card
            ];
        }

        public function getInstance()
        {
            return $this->customer;
        }
    }
}
