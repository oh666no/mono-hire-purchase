<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

use Automattic\WooCommerce\Utilities\OrderUtil;

class Mono_Part_Pay_Admin_Order {

	public function __construct() {
		add_action( 'init', array( $this, 'initialize_hooks' ) );
	}

	public function initialize_hooks() {
		// Check if WooCommerce is active
		if ( class_exists( 'WooCommerce' ) ) {

			add_action( 'add_meta_boxes', array( $this, 'add_mono_part_pay_metabox' ) );
		}
	}

	public function add_mono_part_pay_metabox( $post ) {

		if ( class_exists( 'Automattic\WooCommerce\Utilities\OrderUtil' ) && OrderUtil::custom_orders_table_usage_is_enabled() ) {

			// For HPOS: Use the provided post object as the order object
			$order_id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
			$order = is_a( $post, 'WC_Order' ) ? $post : wc_get_order( $order_id );
			if ( $order && $order->get_payment_method() === 'mono_part_pay' ) {

				$screen = wc_get_page_screen_id( 'shop-order' );

				add_meta_box(
					'mono_part_pay_metabox',
					__( 'Mono Part Pay', 'mono-pay-part' ),
					array( $this, 'render_mono_part_pay_metabox' ),
					$screen,
					'normal',
					'high'
				);
			} else {
				return;
			}
		} else {

			global $post;
			// For legacy storage: Retrieve the order using the post ID
			$order_id = is_a( $post, 'WC_Order' ) ? $post->get_id() : $post->ID;
			$order = wc_get_order( $order_id );
			if ( $order && $order->get_payment_method() === 'mono_part_pay' ) {
				add_meta_box(
					'mono_part_pay_metabox',
					__( 'Mono Part Pay', 'mono-pay-part' ),
					array( $this, 'render_mono_part_pay_metabox' ),
					'shop_order',
					'normal',
					'high'
				);
			} else {
				return;
			}
		}

	}

	public function render_mono_part_pay_metabox( $post ) {
		// Prepare the metabox content
		echo '<p>' . esc_html__( 'Mono Part Pay Order', 'mono-pay-part' ) . '</p>';

		// Initialize variables for meta values
		$selected_payments = $mono_pay_status = $mono_pay_order_id = $mono_order_state = $mono_order_sub_state = $shipment_status = '';

		// Check for HPOS compatibility and retrieve the order meta data
		if ( class_exists( 'Automattic\WooCommerce\Utilities\OrderUtil' ) && OrderUtil::custom_orders_table_usage_is_enabled() ) {
			$order_id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
			$order = is_a( $post, 'WC_Order' ) ? $post : wc_get_order( $order_id );
			$selected_payments = $order->get_meta( '_user_desired_payments_number', true );
			$mono_pay_status = $order->get_meta( '_mono_pay_part_status', true );
			$mono_pay_order_id = $order->get_meta( '_mono_pay_part_order_id', true );
			$mono_order_state = $order->get_meta( '_mono_order_state', true );
			$mono_order_sub_state = $order->get_meta( '_mono_order_sub_state', true );
			$shipment_status = $order->get_meta( '_mono_order_confirm_shipment_status', true );
		} else {
			// Legacy method
			$order = wc_get_order( $post->ID );
			$selected_payments = get_post_meta( $post->ID, '_user_desired_payments_number', true );
			$mono_pay_status = get_post_meta( $post->ID, '_mono_pay_part_status', true );
			$mono_pay_order_id = get_post_meta( $post->ID, '_mono_pay_part_order_id', true );
			$mono_order_state = get_post_meta( $post->ID, '_mono_order_state', true );
			$mono_order_sub_state = get_post_meta( $post->ID, '_mono_order_sub_state', true );
			$shipment_status = get_post_meta( $post->ID, '_mono_order_confirm_shipment_status', true );
		}

		// Add a div to display the Mono Part Pay data with placeholders for the values
		// todo: change classes for h3 depending on statuses
		echo '<div class="mono-part-pay-data">
        <div class="col-1">
			<h3>' . esc_html__( 'Application sumbission', 'mono-pay-part' ) . '</h3>
            <p class="selected-payments">' . esc_html__( 'Selected number of payments:', 'mono-pay-part' ) . ' 
                <span class="selected-payments-value">' . esc_html( $selected_payments ? $selected_payments : 'N/A' ) . '</span>
            </p>
            <p class="mono-pay-status">' . esc_html__( 'Mono Pay Status:', 'mono-pay-part' ) . ' 
                <span class="mono-pay-status-value">' . esc_html( $mono_pay_status ? $mono_pay_status : 'N/A' ) . '</span>
            </p>
            <p class="mono-pay-order-id">' . esc_html__( 'Mono Pay Order ID:', 'mono-pay-part' ) . ' 
                <span class="mono-pay-order-id-value">' . esc_html( $mono_pay_order_id ? $mono_pay_order_id : 'N/A' ) . '</span>
            </p>
        </div>
        <div class="col-2">
			<h3>' . esc_html__( 'Application status', 'mono-pay-part' ) . '</h3>
            <p class="mono-order-state">' . esc_html__( 'Mono Order State:', 'mono-pay-part' ) . ' 
                <span class="mono-order-state-value">' . esc_html( $mono_order_state ? $mono_order_state : 'N/A' ) . '</span>
            </p>
            <p class="mono-order-sub-state">' . esc_html__( 'Mono Order Sub-state:', 'mono-pay-part' ) . ' 
                <span class="mono-order-sub-state-value">' . esc_html( $mono_order_sub_state ? $mono_order_sub_state : 'N/A' ) . '</span>
            </p>
        </div>
        <div class="col-3">
			<h3>' . esc_html__( 'Shipment status', 'mono-pay-part' ) . '</h3>
            <p class="mono-order-shipment-status">' . esc_html__( 'Mono Order Shipment Status:', 'mono-pay-part' ) . ' 
                <span class="mono-order-shipment-status-value">' . esc_html( $shipment_status ? $shipment_status : 'N/A' ) . '</span>
            </p>
        </div>
      </div>';

		// Add buttons with conditional "hide" class based on the presence of _mono_pay_part_order_id
		echo '<div class="mono-pay-buttons">';

		// "Send Order to Mono Pay" button is shown if there is no Mono Pay Order ID
		echo '<button id="mono-pay-order-button" class="button button-primary" ' . ( ! empty( $mono_pay_order_id ) ? 'disabled' : '' ) . '>' . esc_html__( 'Send Order to Mono Pay', 'mono-pay-part' ) . '</button>';

		// "Check Mono Order Status" and "Reject Mono Order" buttons are shown if there is a Mono Pay Order ID
		echo '<button id="check-mono-order-status-button" class="button button-secondary" ' . ( empty( $mono_pay_order_id ) ? 'disabled' : '' ) . '>' . esc_html__( 'Check Mono Order Status', 'mono-pay-part' ) . '</button>';

		// "Reject Mono Order" button is shown only if there is a Mono Pay Order ID and $shipment_status is not 'SUCCESS'
		echo '<button id="reject-mono-order-button" class="button button-link-delete" '
			. ( ( ! empty( $mono_pay_order_id ) && $shipment_status !== 'SUCCESS' )
				? ''
				: 'disabled' )
			. '>'
			. esc_html__( 'Reject Mono Order', 'mono-pay-part' )
			. '</button>';

		// "Confirm Shipment" button is shown if mono_order_state === 'SUCCESS', mono_order_sub_state === 'ACTIVE',
		// and $shipment_status is not 'SUCCESS'
		echo '<button id="confirm-shipment-button" class="button button-primary mono-confirm" '
			. ( ( $mono_order_state === 'SUCCESS' && $mono_order_sub_state === 'ACTIVE' && $shipment_status !== 'SUCCESS' )
				? ''
				: 'disabled' )
			. '>'
			. esc_html__( 'Confirm Shipment', 'mono-pay-part' )
			. '</button>';
		echo '<div class="order_status_updated hide"><span class="dashicons dashicons-info"></span>' . __('Order status has changed. Please reload the page to see the most recent data', 'mono-pay-part') . '<button id="reload" class="button button-secondary" >' . __('Reload', 'mono-pay-part'). '</button></div>';
		echo '</div>';

		// Container for displaying the result
		echo '<hr>';
		echo '<pre id="mono-pay-order-result"></pre>';

		// Add nonce for security
		wp_nonce_field( 'mono_pay_order_nonce_action', 'mono_pay_order_nonce' );

		// Include order ID as a data attribute for Ajax
		echo '<input type="hidden" id="mono-pay-order-id" value="' . esc_attr( $post->ID ) . '">';
	}
}

new Mono_Part_Pay_Admin_Order();