<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'WC_Payment_Gateway' ) ) {
	return;
}
use Automattic\WooCommerce\Utilities\OrderUtil;
class WC_Gateway_Mono_Hire_Purchase extends WC_Payment_Gateway {

	public function __construct() {

		$this->id = 'mono_hire_purchase';
		$payment_logo_url = get_option( 'mono_hire_purchase_payment_logo' );
		$this->icon = ! empty( $payment_logo_url ) ? esc_url( $payment_logo_url ) : MONO_HIRE_PURCHASE_PLUGIN_URL . '/assets/images/default-logo.svg';
		$this->has_fields = true; // We are adding custom fields
		$this->method_title = __( 'Mono Hire Purchase Method', 'mono-pay-part' );
		$this->method_description = __( 'Allows customers to pay using Mono Hire Purchase Method.', 'mono-pay-part' );

		// Load the settings.
		$this->init_form_fields();
		$this->init_settings();

		// Define user settings
		$this->title = $this->get_option( 'title' );
		$this->description = $this->get_option( 'description' );

		// Actions
		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, [ $this, 'process_admin_options' ] );
		add_action( 'woocommerce_thankyou_' . $this->id, [ $this, 'thankyou_page' ] );

		// Save the desired parts as order meta
		add_action( 'woocommerce_checkout_create_order', [ $this, 'save_desired_parts_field' ], 10, 2 );
	}

	public function init_form_fields() {
		$this->form_fields = [ 
			'enabled' => [ 
				'title' => __( 'Enable/Disable', 'mono-pay-part' ),
				'type' => 'checkbox',
				'label' => __( 'Enable Mono Hire Purchase Method', 'mono-pay-part' ),
				'default' => 'yes',
			],
			'title' => [ 
				'title' => __( 'Title', 'mono-pay-part' ),
				'type' => 'text',
				'description' => __( 'This controls the title which the user sees during checkout.', 'mono-pay-part' ),
				'default' => __( 'Mono Hire Purchase Method', 'mono-pay-part' ),
				'desc_tip' => true,
			],
			'description' => [ 
				'title' => __( 'Description', 'mono-pay-part' ),
				'type' => 'textarea',
				'description' => __( 'This controls the description which the user sees during checkout.', 'mono-pay-part' ),
				'default' => __( 'Pay using Mono Hire Purchase Method.', 'mono-pay-part' ),
			],
		];
	}

	// Output custom fields for the payment method on the checkout page
	public function payment_fields() {

		// Get the available parts setting
		$available_parts_setting = get_option( 'mono_hire_purchase_available_parts', '3, 4, 6, 9' );
		$available_parts = explode( ',', $available_parts_setting );
		$available_parts = array_map( 'intval', array_unique( $available_parts ) );
		sort( $available_parts );

		echo '<div>';
		echo wp_kses_post( wpautop( esc_html( $this->description ) ) );

		// Create the dropdown for available parts
		echo '<label for="desired_parts">' . esc_html__( 'Desired payments number', 'mono-pay-part' ) . '</label>';
		echo '<select name="desired_parts" id="desired_parts">';
		foreach ( $available_parts as $part ) {
			echo sprintf(
				'<option value="%1$s">%2$s</option>',
				esc_attr( $part ),
				esc_html( sprintf(
					_nx( '%s payment', '%s payments', $part, 'Number of payments', 'mono-pay-part' ),
					number_format_i18n( $part )
				) )
			);
		}
		echo '</select>';
		echo '</div>';
	}

	public function process_payment( $order_id ) {
		$order = wc_get_order( $order_id );

		$desired_parts = isset( $_POST['desired_parts'] ) ? sanitize_text_field( wp_unslash( $_POST['desired_parts'] ) ) : '';

		if ( ! empty( $desired_parts ) ) {

			if ( class_exists( 'Automattic\WooCommerce\Utilities\OrderUtil' ) && OrderUtil::custom_orders_table_usage_is_enabled() ) {

				$order->update_meta_data( '_user_desired_payments_number', $desired_parts );
				$order->save();
			} else {
				update_post_meta( $order_id, '_user_desired_payments_number', $desired_parts );
			}
		}

		// Mark as on-hold (we're awaiting the payment)
		$order->update_status( 'pending', __( 'Awaiting Mono Hire Purchase payment acceptance', 'mono-pay-part' ) );

		// Reduce stock levels
		wc_reduce_stock_levels( $order_id );

		// Remove cart
		WC()->cart->empty_cart();

		// Return thankyou redirect
		return [ 
			'result' => 'success',
			'redirect' => $this->get_return_url( $order ),
		];
	}

	public function save_desired_parts_field( $order, $data ) {

		if ( isset( $_POST['desired_parts'] ) && ! empty( $_POST['desired_parts'] ) ) {

			$desired_parts = sanitize_text_field( wp_unslash( $_POST['desired_parts'] ) );
			if ( class_exists( 'Automattic\WooCommerce\Utilities\OrderUtil' ) && OrderUtil::custom_orders_table_usage_is_enabled() ) {
				// For HPOS
				$order->update_meta_data( '_user_desired_payments_number', $desired_parts );
			} else {
				// For legacy orders
				update_post_meta( $order->get_id(), '_user_desired_payments_number', $desired_parts );
			}
		}
	}
	public function thankyou_page() {
		// Thank you page content can be added here
	}
}