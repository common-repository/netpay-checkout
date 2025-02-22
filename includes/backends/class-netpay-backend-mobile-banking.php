<?php
/**
 * @method public initiate
 * @method public get_available_providers
 */
class NetPay_Backend_Mobile_Banking extends NetPay_Backend {
	/**
	 * @var array  of known mobile banking providers.
	 */
	protected static $providers = array();

	public function initiate() {
		self::$providers = array(
			'mobile_banking_kbank' => array(
				'title'              => __( 'K PLUS', 'netpay' ),
				'logo'				 => 'kplus',
			),
			'mobile_banking_scb' => array(
				'title'              => __( 'SCB EASY', 'netpay' ),
				'logo'				 => 'scb',
			),
			'mobile_banking_bay' => array(
				'title'              => __( 'KMA', 'netpay' ),
				'logo'				 => 'bay',
			),
			'mobile_banking_bbl' => array(
				'title'              => __( 'Bualuang mBanking', 'netpay' ),
				'logo'				 => 'bbl',
			),
			'mobile_banking_ktb' => array(
				'title'              => __( 'Krungthai NEXT', 'netpay' ),
				'logo'				 => 'ktb',
			)
		);
	}

	/**
	 *
	 * @return array of an available mobile banking providers
	 */
	public function get_available_providers( $currency ) {
		$mobile_banking_providers = array();
		$capabilities = $this->capabilities();

		if ( $capabilities ){
			$providers = $capabilities->getBackends( $currency );

			foreach ( $providers as &$provider ) {
				if(isset(self::$providers[ $provider->_id ])){

					$provider_detail = self::$providers[ $provider->_id ];
					$provider->provider_name   = $provider_detail['title'];
					$provider->provider_logo   = $provider_detail['logo'];

					array_push($mobile_banking_providers, $provider);
				}
			}
		}

		return $mobile_banking_providers;
	}
}
