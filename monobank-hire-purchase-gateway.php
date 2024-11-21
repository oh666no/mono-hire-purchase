<?php

/*
Plugin Name: Monobank Hire Purchase Gateway
Description: Adds a WooCommerce payment gateway for Monobank's installment system, enabling split payments with real-time status updates and order management.
Plugin URI: https://pkotula.com/
Author: pkotula
Version: 1.0
Text Domain: monobank-hire-purchase-gateway
Domain Path: /languages
License: GPLv2
License URI: https://www.gnu.org/licenses/gpl-2.0.html
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Prevent direct access to the file
}

// Define plugin constants
define( 'MONO_HIRE_PURCHASE_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'MONO_HIRE_PURCHASE_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// Load plugin translations
function mono_hire_purchase_load_textdomain() {
	load_plugin_textdomain( 'monobank-hire-purchase-gateway', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
}
add_action( 'plugins_loaded', 'mono_hire_purchase_load_textdomain' );

// Enqueue frontend styles
function mono_hire_purchase_enqueue_frontend_styles() {
	if ( ! is_admin() ) {
		wp_enqueue_style( 'monobank-hire-purchase-gateway-frontend', MONO_HIRE_PURCHASE_PLUGIN_URL . 'assets/css/style-frontend.css', array(), '1.0' );
	}
}
add_action( 'wp_enqueue_scripts', 'mono_hire_purchase_enqueue_frontend_styles' );

// Enqueue admin styles and scripts
function mono_hire_purchase_enqueue_admin_assets( $hook_suffix ) {
	error_log('Current hook suffix: ' . $hook_suffix);
	if ( $hook_suffix == 'woocommerce_page_monobank-hire-purchase-gateway' ) {
		wp_enqueue_media();
		wp_enqueue_style( 'monobank-hire-purchase-gateway-settings', MONO_HIRE_PURCHASE_PLUGIN_URL . 'assets/css/style-settings-page.css', array(), '1.0' );
	}

	wp_enqueue_script( 'mono-heartbeat-script', MONO_HIRE_PURCHASE_PLUGIN_URL . 'assets/js/mono-heartbeat.js', array( 'heartbeat' ), '1.0', true );
	wp_enqueue_style( 'monobank-hire-purchase-gateway-admin', MONO_HIRE_PURCHASE_PLUGIN_URL . 'assets/css/style-admin.css', array(), '1.0' );
	wp_enqueue_script( 'monobank-hire-purchase-gateway-admin', MONO_HIRE_PURCHASE_PLUGIN_URL . 'assets/js/scripts-admin.js', array( 'jquery' ), '1.0', true );
	wp_localize_script( 'monobank-hire-purchase-gateway-admin', 'adminScriptLocalizedText', array(
		'selectImage' => __('Select Image', 'monobank-hire-purchase-gateway'),
		'useImage' => __('Use this image', 'monobank-hire-purchase-gateway'),
		'copySuccess' => __( 'Shortcode copied to clipboard!', 'monobank-hire-purchase-gateway' )
	) );
}
add_action( 'admin_enqueue_scripts', 'mono_hire_purchase_enqueue_admin_assets' );

// Check if WooCommerce is active
include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
if ( is_plugin_active( 'woocommerce/woocommerce.php' ) ) {

	// Include required classes
	require_once MONO_HIRE_PURCHASE_PLUGIN_DIR . 'includes/class.settings.php';
	require_once MONO_HIRE_PURCHASE_PLUGIN_DIR . 'includes/class.admin-wc-order.php';
	require_once MONO_HIRE_PURCHASE_PLUGIN_DIR . 'includes/class.mono-api.php';

	// Declare compatibility with WooCommerce features
	add_action( 'before_woocommerce_init', function () {
		if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
		}
		if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', __FILE__, true );
		}
	} );

	// Initialize the payment gateway
	function mono_hire_purchase_gateway_init() {
		require_once MONO_HIRE_PURCHASE_PLUGIN_DIR . 'includes/class.hire-purchase-method.php';
	}
	add_action( 'plugins_loaded', 'mono_hire_purchase_gateway_init', 11 );

	// Register the payment gateway
	function add_mono_hire_purchase_gateway( $methods ) {
		if ( get_option( 'mono_hire_purchase_enable_payment_method' ) ) {
			$methods[] = 'WC_Gateway_Mono_Hire_Purchase';
		}
		return $methods;
	}
	add_filter( 'woocommerce_payment_gateways', 'add_mono_hire_purchase_gateway' );

} else {
	// Display notice if WooCommerce is not active
	add_action( 'admin_notices', 'mono_hire_purchase_woocommerce_notice' );
	function mono_hire_purchase_woocommerce_notice() {
		?>
		<div class="notice notice-error">
			<p><?php esc_html_e( 'Monobank Hire Purchase Gateway requires WooCommerce to be active.', 'monobank-hire-purchase-gateway' ); ?></p>
		</div>
		<?php
	}
}

// Register support for WooCommerce Blocks
add_action( 'woocommerce_blocks_loaded', 'mono_pay_register_block_support' );
function mono_pay_register_block_support() {
	if ( ! class_exists( 'Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType' ) ) {
		return;
	}
	require_once MONO_HIRE_PURCHASE_PLUGIN_DIR . 'includes/class.mono-block.php';
	add_action(
		'woocommerce_blocks_payment_method_type_registration',
		function (Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $payment_method_registry) {
			$payment_method_registry->register( new WC_Gateway_Mono_Hire_Purchase_Blocks );
		}
	);
}