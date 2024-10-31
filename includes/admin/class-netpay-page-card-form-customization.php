<?php

defined('ABSPATH') or die('No direct script access allowed.');

if (class_exists('NetPay_Page_Settings')) {
	return;
}

class NetPay_Page_Card_From_Customization extends NetPay_Admin_Page
{
	private static $instance;

	public static function get_instance()
	{
		if (!isset(self::$instance)) {
			self::$instance = new self;
		}

		return self::$instance;
	}

	const PAGE_NAME = 'netpay_card_form_customization_option';

	private function get_light_theme()
	{
		return [
			'font' => [
				'name' => 'Poppins',
				'size' => 16,
				'custom_name' => ''
			],
			'input' => [
				'height' => '44px',
				'border_radius' => '4px',
				'border_color' => '#ced3de',
				'active_border_color' => '#1451cc',
				'background_color' => '#ffffff',
				'label_color' => '#212121',
				'text_color' => '#212121',
				'placeholder_color' => '#98a1b2',
			],
			'checkbox' => [
				'text_color' => '#1c2433',
				'theme_color' => '#1451cc',
			]
		];
	}

	private function get_dark_theme()
	{
		return [
			'font' => [
				'name' => 'Poppins',
				'size' => 16,
				'custom_name' => ''
			],
			'input' => [
				'height' => '44px',
				'border_radius' => '4px',
				'border_color' => '#475266',
				'active_border_color' => '#475266',
				'background_color' => '#131926',
				'label_color' => '#E6EAF2',
				'text_color' => '#ffffff',
				'placeholder_color' => '#DBDBDB',
			],
			'checkbox' => [
				'text_color' => '#E6EAF2',
				'theme_color' => '#1451CC',
			]
		];
	}

	protected function get_default_design_setting()
	{
		$theme = (new NetPay_Payment_Creditcard())->get_option('card_form_theme');
		return (empty($theme) || $theme == 'light')
			? $this->get_light_theme()
			: $this->get_dark_theme();
	}

	/**
	 * get design setting
	 */
	public function get_design_setting()
	{
		$formDesign = get_option(self::PAGE_NAME);
		if (empty($formDesign)) {
			$formDesign = $this->get_default_design_setting();
		}

		// Old saved settings might not have the newer fields. Make sure
		// we add the missing field
		if (!in_array('custom_name', $formDesign['font'])) {
			$formDesign['font']['custom_name'] = '';
		}

		return $formDesign;
	}

	/**
	 * @param array $data
	 *
	 * @since  3.1
	 */
	protected function save($data)
	{
		if (!isset($data['netpay_setting_page_nonce']) || !wp_verify_nonce($data['netpay_setting_page_nonce'], 'netpay-setting')) {
			wp_die(__('You are not allowed to modify the settings from a suspicious source.', 'netpay'));
		}
		$options = [];
		$defaultValues = $this->get_default_design_setting();

		// Sanitize the field POST params
		// the fist loop get the component name. i.e input, checkout, font
		// and send loop get the styling key of the component. i.e name, size, border, color
		foreach ($defaultValues as $componentKey => $componentValue) {
			foreach ($componentValue as $key => $val) {
				$options[$componentKey][$key] = sanitize_text_field($data[$componentKey][$key]);
			}
		}

		update_option(self::PAGE_NAME, $options);
		$this->add_message('message', "Update has been saved!");
	}

	/**
	 * @param array $data
	 *
	 * @since  3.1
	 */
	protected function reset_default_setting($data)
	{
		if (
			!isset($data['netpay_setting_page_nonce']) ||
			!wp_verify_nonce($data['netpay_setting_page_nonce'], 'netpay-setting')
		) {
			wp_die(__('You are not allowed to modify the settings from a suspicious source.', 'netpay'));
		}

		update_option(self::PAGE_NAME, null);

		$this->add_message(
			'message',
			"Setting have been reset!"
		);
	}

	/**
	 * @since  3.1
	 */
	public static function render()
	{
		$page = self::get_instance();

		if (isset($_POST['netpay_customization_submit'])) {
			$page->save($_POST);
		}

		if (isset($_POST['netpay_customization_reset'])) {
			$page->reset_default_setting($_POST);
		}

		$formDesign = $page->get_design_setting();

		include_once __DIR__ . '/views/netpay-page-card-form-customization.php';
	}
}
