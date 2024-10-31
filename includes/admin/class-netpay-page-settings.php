<?php

defined( 'ABSPATH' ) or die( 'No direct script access allowed.' );

if ( class_exists( 'NetPay_Page_Settings' ) ) {
	return;
}

class NetPay_Page_Settings extends NetPay_Admin_Page {
	/**
	 * @param array $data
	 *
	 * @since  3.1
	 */
	protected function save( $data ) {
		if ( ! isset( $data['netpay_setting_page_nonce'] ) || ! wp_verify_nonce( $data['netpay_setting_page_nonce'], 'netpay-setting' ) ) {
			wp_die( __( 'You are not allowed to modify the settings from a suspicious source.', 'netpay' ) );
		}

		$secret_key = isset( $data['sandbox'] ) ? $data['test_private_key'] : $data['live_private_key'];
        $sandbox = false;
        if (array_keys($data, "sandbox")) {
            $sandbox = $data['sandbox'];
        }
		try {
			$this->update_settings( $data );
            $this->add_message(
                'message',
                'Se han guardado los cambios satisfactoriamente.'
            );

            \NetPay\NetPayConfig::init($sandbox);
            $get_webhook = \NetPay\Api\NetPayWebhook::get($secret_key);
            $webhook_url = get_site_url() . "/index.php?netpay-listener=cash";
            $data = array("webhook" => $webhook_url);
            if(isset($get_webhook["result"]["id"])) {
                \NetPay\Api\NetPayWebhook::put($secret_key, $data);
            }
            else {
                \NetPay\Api\NetPayWebhook::post($secret_key, $data);
            }
		} catch (Exception $e) {
			$this->add_message( 'error', $e->getMessage() );
		}
	}

	/**
	 * @since  3.1
	 */
	public static function render() {
		global $title;

		$page = new self;

		// Save settings if data has been posted
		if ( ! empty( $_POST ) ) {
			$page->save( $_POST );
		}

		$settings = $page->get_settings();

		/**
		 * Added later at NetPay-WooCommerce v3.11.
		 * To migrate all the users that haven been using NetPay-WooCommerce
		 * below the version v3.11.
		 */
        $settings['account_country'] = "MX";
		if ( ! $settings['account_country'] && ( $settings['test_private_key'] || $settings['live_private_key'] ) ) {
			$settings['netpay_setting_page_nonce'] = wp_create_nonce( 'netpay-setting' );
			$page->save( $settings );
			$settings = $page->get_settings();
		}

		include_once __DIR__ . '/views/netpay-page-settings.php';
	}
}
