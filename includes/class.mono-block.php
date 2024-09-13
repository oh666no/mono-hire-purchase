<?php
use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;
final class WC_Gateway_Mono_Part_Pay_Blocks extends AbstractPaymentMethodType {
	private $gateway;
	protected $name = 'mono_part_pay';// your payment gateway name
	public function initialize() {
		$this->settings = get_option( 'woocommerce_mono_part_pay_settings', [] );
		$this->gateway = new WC_Gateway_Mono_Part_Pay();
	}
	public function is_active() {
		return $this->gateway->is_available();
	}
	public function get_payment_method_script_handles() {
		$asset_file = include( MONO_PAY_PART_PLUGIN_DIR . 'build/mono-pay-part-blocks.asset.php' );
		wp_register_script(
			'mono-pay-part-blocks-script',
			MONO_PAY_PART_PLUGIN_URL . 'build/mono-pay-part-blocks.js',
			$asset_file['dependencies'],
			$asset_file['version'],
			true
		);
		if ( function_exists( 'wp_set_script_translations' ) ) {
			wp_set_script_translations( 'mono-pay-part-blocks-script', 'mono-pay-part', MONO_PAY_PART_PLUGIN_DIR . 'languages' );

		}
		return [ 'mono-pay-part-blocks-script' ];
	}
	public function get_payment_method_data() {
		$available_parts_setting = get_option( 'mono_pay_part_available_parts', '3, 4, 6, 9' );
		$available_parts = explode( ',', $available_parts_setting );
		$available_parts = array_map( 'intval', array_unique( $available_parts ) );
		sort( $available_parts );
		return [ 
			'title' => $this->gateway->title,
			'description' => $this->gateway->description,
			'icon' => $this->gateway->icon,
			'available_parts' => $available_parts,
		];
	}
}
?>