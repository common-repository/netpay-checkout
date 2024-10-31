<?php

use Automattic\WooCommerce\Blocks\Registry\Container;

class NetPay_Block_Config {

    // Automattic\WooCommerce\Blocks\Registry\Container
    private $container;

    public function __construct($container) {
        $this->container = $container;
        $this->register_payment_methods();
        $this->container->get( NetPay_Block_Payments::class );
    }

    private function register_payment_methods() {
        // register the payments API
        $this->container->register( NetPay_Block_Payments::class, function ( $container ) {
            return new NetPay_Block_Payments( $container );
        } );
    }
}
