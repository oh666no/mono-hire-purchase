<?php

/*
Plugin Name: Monobank Hire Purchase Gateway
Description: The Mono Pay-Part plugin adds a WooCommerce payment gateway that integrates Mono Bank’s installment system, enabling customers to split payments while providing real-time payment status updates and order management.
Plugin URI: https://pkotula.com/
Author: pkotula
Version: 1.0
Text Domain: mono-hire-purchase
Domain Path: /languages
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

// Define constants
define( 'MONO_HIRE_PURCHASE_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'MONO_HIRE_PURCHASE_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// Load plugin text domain for translations
function mono_hire_purchase_load_textdomain() {
	load_plugin_textdomain( 'mono-hire-purchase', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
}
add_action( 'plugins_loaded', 'mono_hire_purchase_load_textdomain' );

// Enqueue frontend styles
function mono_hire_purchase_enqueue_frontend_styles() {
	if ( ! is_admin() ) {
		wp_enqueue_style( 'mono-hire-purchase-frontend', MONO_HIRE_PURCHASE_PLUGIN_URL . 'assets/css/style-frontend.css', array(), '1.0' );
	}
}
add_action( 'wp_enqueue_scripts', 'mono_hire_purchase_enqueue_frontend_styles' );

// Enqueue admin styles and scripts
function mono_hire_purchase_enqueue_admin_assets( $hook_suffix ) {
	if ( $hook_suffix == 'woocommerce_page_mono-hire-purchase' ) {
		wp_enqueue_media();
		wp_enqueue_style( 'mono-hire-purchase-settings', MONO_HIRE_PURCHASE_PLUGIN_URL . 'assets/css/style-settings-page.css', array(), '1.0' );
	}

	wp_enqueue_script( 'mono-heartbeat-script', MONO_HIRE_PURCHASE_PLUGIN_URL . 'assets/js/mono-heartbeat.js', array( 'heartbeat' ), '1.0', true );
	wp_enqueue_style( 'mono-hire-purchase-admin', MONO_HIRE_PURCHASE_PLUGIN_URL . 'assets/css/style-admin.css', array(), '1.0' );
	wp_enqueue_script( 'mono-hire-purchase-admin', MONO_HIRE_PURCHASE_PLUGIN_URL . 'assets/js/scripts-admin.js', array( 'jquery' ), '1.0', true );
	wp_localize_script( 'mono-hire-purchase-admin', 'adminScriptLocalizedText', array(
		'copySuccess' => __( 'Shortcode copied to clipboard!', 'mono-hire-purchase' )
	) );
}
add_action( 'admin_enqueue_scripts', 'mono_hire_purchase_enqueue_admin_assets' );

// Ensure WooCommerce is active
include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
if ( is_plugin_active( 'woocommerce/woocommerce.php' ) ) {

	// Include the settings class
	require_once MONO_HIRE_PURCHASE_PLUGIN_DIR . 'includes/class.settings.php';
	// Include the admin order class
	require_once MONO_HIRE_PURCHASE_PLUGIN_DIR . 'includes/class.admin-wc-order.php';
	// Include the Mono API class
	require_once MONO_HIRE_PURCHASE_PLUGIN_DIR . 'includes/class.mono-api.php';

	add_action( 'before_woocommerce_init', function () {
		if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
		}
		if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
			// Declare compatibility for 'cart_checkout_blocks'
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', __FILE__, true );
		}
	} );

	// Include the payment method class
	function mono_hire_purchase_gateway_init() {
		require_once MONO_HIRE_PURCHASE_PLUGIN_DIR . 'includes/class.hire-purchase-method.php';
	}
	add_action( 'plugins_loaded', 'mono_hire_purchase_gateway_init', 11 );

	// Register the gateway
	function add_mono_hire_purchase_gateway( $methods ) {
		if ( get_option( 'mono_hire_purchase_enable_payment_method' ) ) {
			$methods[] = 'WC_Gateway_Mono_Hire_Purchase';
		}
		return $methods;
	}
	add_filter( 'woocommerce_payment_gateways', 'add_mono_hire_purchase_gateway' );

} else {
	add_action( 'admin_notices', 'mono_hire_purchase_woocommerce_notice' );
	function mono_hire_purchase_woocommerce_notice() {
		?>
		<div class="notice notice-error">
			<p><?php _e( 'Restare Mono Pay-part requires WooCommerce to be active.', 'mono-hire-purchase' ); ?></p>
		</div>
		<?php
	}
}


add_action( 'woocommerce_blocks_loaded', 'mono_pay_register_block_support' );
function mono_pay_register_block_support() {
	// Check if the required class exists
	if ( ! class_exists( 'Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType' ) ) {
		return;
	}
	// Include the custom Blocks Checkout class
	require_once MONO_HIRE_PURCHASE_PLUGIN_DIR . 'includes/class.mono-block.php';
	// Hook the registration function to the 'woocommerce_blocks_payment_method_type_registration' action
	add_action(
		'woocommerce_blocks_payment_method_type_registration',
		function (Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $payment_method_registry) {
			// Register an instance of My_Custom_Gateway_Blocks
			$payment_method_registry->register( new WC_Gateway_Mono_Hire_Purchase_Blocks );
		}
	);
}