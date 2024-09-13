<?php
use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;
final class WC_Gateway_Mono_Hire_Purchase_Blocks extends AbstractPaymentMethodType {
	private $gateway;
	protected $name = 'mono_hire_purchase';// your payment gateway name
	public function initialize() {
		$this->settings = get_option( 'woocommerce_mono_hire_purchase_settings', [] );
		$this->gateway = new WC_Gateway_Mono_Hire_Purchase();
	}
	public function is_active() {
		return $this->gateway->is_available();
	}
	public function get_payment_method_script_handles() {
		$asset_file = include( MONO_HIRE_PURCHASE_PLUGIN_DIR . 'build/mono-hire-purchase-blocks.asset.php' );
		wp_register_script(
			'mono-hire-purchase-blocks-script',
			MONO_HIRE_PURCHASE_PLUGIN_URL . 'build/mono-hire-purchase-blocks.js',
			$asset_file['dependencies'],
			$asset_file['version'],
			true
		);
		if ( function_exists( 'wp_set_script_translations' ) ) {
			wp_set_script_translations( 'mono-hire-purchase-blocks-script', 'mono-hire-purchase', MONO_HIRE_PURCHASE_PLUGIN_DIR . 'languages' );

		}
		return [ 'mono-hire-purchase-blocks-script' ];
	}
	public function get_payment_method_data() {
		$available_parts_setting = get_option( 'mono_hire_purchase_available_parts', '3, 4, 6, 9' );
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