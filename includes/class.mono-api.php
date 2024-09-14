<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

use Automattic\WooCommerce\Utilities\OrderUtil;
class Mono_Hire_Purchase_API {
	private $log_file_path;
	public function __construct() {
		// Define the log file path
		$this->log_file_path = MONO_HIRE_PURCHASE_PLUGIN_DIR . 'logs/api-calls.log';

		// Initialize WP_Filesystem
		global $wp_filesystem;
		if ( ! function_exists( 'WP_Filesystem' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}

		WP_Filesystem();

		// Get the directory path
		$log_dir = dirname( $this->log_file_path );

		// Ensure the logs directory exists, if not create it
		if ( ! $wp_filesystem->is_dir( $log_dir ) ) {
			$wp_filesystem->mkdir( $log_dir, 0755 );
		}

		// Ensure the log file exists, if not create it
		if ( ! $wp_filesystem->exists( $this->log_file_path ) ) {
			$wp_filesystem->put_contents( $this->log_file_path, '', 0755 ); // Create an empty file with correct permissions
		}

		// Register WooCommerce API endpoint for payment callback
		add_action( 'woocommerce_api_pay-part-result', array( $this, 'handle_payment_callback' ) );

		// Register Ajax action for order processing
		add_action( 'wp_ajax_process_mono_pay_order', array( $this, 'process_mono_pay_order_ajax' ) );
		add_action( 'wp_ajax_nopriv_process_mono_pay_order', array( $this, 'process_mono_pay_order_ajax' ) );
		add_action( 'wp_ajax_check_mono_order_status', array( $this, 'check_mono_order_status_ajax' ) );
		add_action( 'wp_ajax_nopriv_check_mono_order_status', array( $this, 'check_mono_order_status_ajax' ) );
		add_action( 'wp_ajax_reject_mono_order', array( $this, 'reject_mono_order_ajax' ) );
		add_action( 'wp_ajax_nopriv_reject_mono_order', array( $this, 'reject_mono_order_ajax' ) );
		add_action( 'wp_ajax_confirm_mono_order_shipment', array( $this, 'confirm_mono_order_shipment_ajax' ) );
		add_action( 'wp_ajax_nopriv_confirm_mono_order_shipment', array( $this, 'confirm_mono_order_shipment_ajax' ) );
		add_action( 'admin_footer', array( $this, 'enable_heartbeat_on_orders_page' ) );
		add_action( 'heartbeat_received', array( $this, 'check_mono_order_status_heartbeat' ), 10, 2 );
	}
	 public function check_mono_order_status_heartbeat( $response, $data ) {
        if ( isset( $data['mono_order_status'] ) ) {
            $order_id = intval( $data['mono_order_status']['order_id'] );
            $order = wc_get_order( $order_id );

            if ( $order ) {
                // Retrieve the current state and sub-state of the order
                $state = $order->get_meta( '_mono_order_state', true );
                $sub_state = $order->get_meta( '_mono_order_sub_state', true );

                // Add the state and sub-state to the heartbeat response
                $response['mono_order_status'] = array(
                    'state' => $state ? $state : 'N/A',
                    'sub_state' => $sub_state ? $sub_state : 'N/A',
					'order_status_updated' => true
                );
            } else {
                $response['mono_order_status'] = array( 'error' => 'Invalid order ID' );
            }
        }

        return $response;
    }
	public function enable_heartbeat_on_orders_page() {
		global $post;

		if ( class_exists( 'Automattic\WooCommerce\Utilities\OrderUtil' ) && OrderUtil::custom_orders_table_usage_is_enabled() ) {
			// For HPOS: Get the order ID from the URL or elsewhere
			$order_id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
			if ( ! $order_id ) {
				// Early return if there's no valid order ID in the query parameters
				return;
			}
		} else {
			// For legacy storage: Use the global post object and retrieve the order ID
			if ( ! is_a( $post, 'WP_Post' ) || $post->post_type !== 'shop_order' ) {
				// If not a valid WP_Post object or not an order page, early return
				return;
			}
			$order_id = $post->ID;
		}

		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			// If the order is not found, return early
			return;
		}

		// Localize the script with the order ID and heartbeat settings
		wp_localize_script( 'mono-heartbeat-script', 'monoOrderData', array(
			'heartbeatInterval' => 15,  // Heartbeat interval in seconds
			'orderID' => $order_id
		) );
	}
	// Custom logging function
	private function log_message( $message ) {
		global $wp_filesystem;

		// Initialize the WP_Filesystem API if it's not already loaded
		if ( ! function_exists( 'WP_Filesystem' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}
		WP_Filesystem();
		
		// Determine the path relative to the plugin root
		$file = str_replace( MONO_HIRE_PURCHASE_PLUGIN_DIR, '', debug_backtrace()[0]['file'] );
		$function = debug_backtrace()[1]['function'];

		$context = $file . ' - ' . $function;

		// Format the log entry
		$log_entry = '[' . gmdate( 'Y-m-d H:i:s' ) . "][" . $context . "] " . $message . PHP_EOL;

		// Check if log file exists
		if ( file_exists( $this->log_file_path ) ) {
			// Get the last modified time of the log file
			$last_modified_time = filemtime( $this->log_file_path );

			// Calculate the age of the log file in days
			$file_age_in_days = ( time() - $last_modified_time ) / ( 60 * 60 * 24 );

			// Rotate log if it's older than 7 days
			if ( $file_age_in_days > 7 ) {
				// Rename the current log file by appending the date of rotation
				$new_log_file_name = $this->log_file_path . '-' . gmdate( 'Y-m-d-H-i-s', $last_modified_time ) . '.log';

				// Define the current log file and the new log file path
				$old_log_file = $this->log_file_path;
				$new_log_file = $new_log_file_name;

				// Check if the old log file exists before moving it
				if ( $wp_filesystem->exists( $old_log_file ) ) {
					// Use WP_Filesystem to rename (move) the file
					$wp_filesystem->move( $old_log_file, $new_log_file );
				}
			}
		}

		// Define the log entry and the file path
		$log_file = $this->log_file_path;

		// Ensure the log file exists or create it if necessary
		if ( $wp_filesystem->exists( $log_file ) ) {
			// Append the log entry to the file
			$existing_content = $wp_filesystem->get_contents( $log_file ); // Get existing file content
			$log_entry = $existing_content . $log_entry; // Append new log entry to existing content
		}

		$wp_filesystem->put_contents( $log_file, $log_entry, FS_CHMOD_FILE ); // Write the log entry to the file
	}

	private function update_order_meta( $order, $meta_data ) {
		foreach ( $meta_data as $meta_key => $meta_value ) {
			if ( class_exists( 'Automattic\WooCommerce\Utilities\OrderUtil' ) && OrderUtil::custom_orders_table_usage_is_enabled() ) {
				$order->update_meta_data( $meta_key, $meta_value );
				$order->save();
			} else {
				update_post_meta( $order->get_id(), $meta_key, $meta_value );
			}
		}
	}

	private function delete_order_meta( $order, $meta_keys ) {
		foreach ( $meta_keys as $meta_key ) {
			if ( class_exists( 'Automattic\WooCommerce\Utilities\OrderUtil' ) && OrderUtil::custom_orders_table_usage_is_enabled() ) {
				$order->delete_meta_data( $meta_key );
			} else {
				delete_post_meta( $order->get_id(), $meta_key );
			}
		}
	}
	/**
	 * Handle the payment gateway callback for Mono Part Pay.
	 *
	 * Callback URL: https://example.com/wc-api/pay-part-result/
	 */
	public function handle_payment_callback() {
		global $wpdb;

		// Check for missing signature
		if ( ! isset( $_SERVER['HTTP_SIGNATURE'] ) ) {
			$this->log_message( 'Missing HTTP_SIGNATURE' );
			header( 'HTTP/1.1 400 Bad Request' );
			echo wp_json_encode( [ 'message' => 'Missing signature' ] );
			return;
		}

		// Fetch secret key and signature
		$is_test_mode = get_option( 'mono_hire_purchase_test_mode', '0' ) === '1';
		$secret_key = $is_test_mode ? get_option( 'mono_hire_purchase_test_sign_key' ) : get_option( 'mono_hire_purchase_sign_key' );
		$received_signature = sanitize_text_field( wp_unslash( $_SERVER['HTTP_SIGNATURE'] ) );
		$request_body = file_get_contents( 'php://input' );
		$expected_signature = base64_encode( hash_hmac( 'sha256', $request_body, $secret_key, true ) );

		// Validate signature
		if ( ! hash_equals( $expected_signature, $received_signature ) ) {
			$this->log_message( 'Signature INVALID' );
			header( 'HTTP/1.1 401 Unauthorized' );
			return;
		}

		// Decode the request body
		$request_data = json_decode( $request_body, true );
		if ( json_last_error() !== JSON_ERROR_NONE ) {
			$this->log_message( 'Invalid JSON: ' . json_last_error_msg() );
			header( 'HTTP/1.1 400 Bad Request' );
			echo wp_json_encode( [ 'message' => 'Invalid JSON' ] );
			return;
		}

		// Query the database for the order
		$meta_key = '_mono_hire_purchase_order_id';
		$meta_value = $request_data['order_id'];

		if ( class_exists( 'Automattic\WooCommerce\Utilities\OrderUtil' ) && OrderUtil::custom_orders_table_usage_is_enabled() ) {
			// HPOS is enabled
			$order_id = wc_get_orders( array(
				'meta_key' => $meta_key,
				'meta_value' => $meta_value,
				'limit' => 1,
				'return' => 'ids',
			) );
			$order_id = ! empty( $order_id ) ? $order_id[0] : null;
		} else {
			// Traditional post meta storage
			$query = $wpdb->prepare(
				"SELECT post_id FROM $wpdb->postmeta WHERE meta_key = %s AND meta_value = %s LIMIT 1",
				$meta_key,
				$meta_value
			);
			$order_id = $wpdb->get_var( $query );
		}

		if ( $order_id ) {
			$order = wc_get_order( $order_id );
			if ( $order ) {
				// Update meta and handle status
				$this->update_order_meta( $order, [ 
					'_mono_order_state' => $request_data['state'],
					'_mono_order_sub_state' => $request_data['order_sub_state']
				] );

				if ( $request_data['state'] === 'SUCCESS' ) {
					$order->update_status( 'processing', esc_html__( 'Mono Part Pay payment Approved by Bank', 'mono-hire-purchase' ) );
				} elseif ( $request_data['state'] === 'FAIL' ) {
					$order->update_status( 'failed', esc_html__( 'Mono Part Pay payment Failed. Reason: ', 'mono-hire-purchase' ) . esc_html( $request_data['order_sub_state'] ) );
				} else {
					$order->update_status( 'on-hold', esc_html__( 'Unknown payment status via Mono Part Pay.', 'mono-hire-purchase' ) );
				}
				$order->save();
			}
		} else {
			$this->log_message( 'Order not found for Mono Pay Order ID: ' . esc_html( $meta_value ) );
		}

		// Send a response
		header( 'HTTP/1.1 200 OK' );
		echo wp_json_encode( [ 'message' => 'Payment callback processed successfully' ] );
	}

	/**
	 * Process Ajax request for creating Mono Part Pay order
	 */
	public function process_mono_pay_order_ajax() {

		if ( ! isset( $_POST['order_id'] ) ) {
			wp_send_json_error( array( 'message' => 'Missing order ID' ) );
			return;
		}

		$order_id = intval( $_POST['order_id'] );
		$order = wc_get_order( $order_id );

		if ( ! $order ) {
			wp_send_json_error( array( 'message' => 'Invalid order ID' ) );
			return;
		}

		$response = $this->create_mono_hire_purchase_order( $order );

		if ( is_wp_error( $response ) ) {
			wp_send_json_error( array( 'message' => 'API request failed', 'error' => $response->get_error_message() ) );
		} else {
			// Decode the response body
			$response_body = json_decode( $response['body'], true );

			// Get response message
			$response_message = $response['response']['message'];

			// Use the update_order_meta for updating status and sub-state
			$this->update_order_meta( $order, [ 
				'_mono_hire_purchase_status' => $response_message,
			] );

			// Use delete_order_meta for deleting unused meta fields
			$this->delete_order_meta( $order, [ 
				'_mono_order_state',
				'_mono_order_sub_state',
			] );
			// If response message is "Created", store the order ID from the response
			if ( $response_message == 'Created' && isset( $response_body['order_id'] ) ) {
				$mono_pay_order_id = $response_body['order_id'];

				if ( class_exists( 'Automattic\WooCommerce\Utilities\OrderUtil' ) && OrderUtil::custom_orders_table_usage_is_enabled() ) {
					// HPOS compatible method
					$order->update_meta_data( '_mono_hire_purchase_order_id', $mono_pay_order_id );
					$order->save();
				} else {
					// Legacy method
					update_post_meta( $order_id, '_mono_hire_purchase_order_id', $mono_pay_order_id );
				}
			}

			// Send success response back
			wp_send_json_success( array(
				'message' => 'Order successfully processed',
				'response' => $response,
				'mono_pay_status' => $response['response']['message'],
				'mono_pay_order_id' => $response_body['order_id']
			) );
		}
	}

	/**
	 * Make an API call to Mono Pay to create an order.
	 *
	 * @param WC_Order $order The WooCommerce order object.
	 */
	public function create_mono_hire_purchase_order( $order ) {

		$is_test_mode = get_option( 'mono_hire_purchase_test_mode', '0' ) === '1';
		// Use test or production credentials based on the mode
		if ( $is_test_mode ) {
			$api_url = get_option( 'mono_hire_purchase_test_api_url' ); // Test API URL
			$store_id = get_option( 'mono_hire_purchase_test_store_id' ); // Test Store ID
			$sign_key = get_option( 'mono_hire_purchase_test_sign_key' ); // Test Sign Key
		} else {
			$api_url = get_option( 'mono_hire_purchase_api_url' ); // Production API URL
			$store_id = get_option( 'mono_hire_purchase_store_id' ); // Production Store ID
			$sign_key = get_option( 'mono_hire_purchase_sign_key' ); // Production Sign Key
		}
		$order_id = $order->get_id();
		$order_total = $order->get_total();
		$billing_phone = $order->get_billing_phone();
		$products = $order->get_items();
		$order_date = $order->get_date_created()->date( 'Y-m-d' );
		$callback_url = home_url( '/wc-api/pay-part-result/' );

		if ( class_exists( 'Automattic\WooCommerce\Utilities\OrderUtil' ) && OrderUtil::custom_orders_table_usage_is_enabled() ) {
			// HPOS compatible method
			$selected_payments_qty = $order->get_meta( '_user_desired_payments_number', true );
		} else {
			// Legacy method
			$post_order = wc_get_order( $order_id );
			$selected_payments_qty = $post_order->get_meta( '_user_desired_payments_number', true );
		}
		$parsed_url = wp_parse_url( home_url() );
		$domain = preg_replace( '/^www\./', '', $parsed_url['host'] );
		// Construct the request string
		$request_data = [ 
			'store_order_id' => "pay_part_" . $order_id,
			'client_phone' => $billing_phone,
			'total_sum' => $order_total,
			'invoice' => [ 
				'date' => $order_date,
				'number' => $order_id,
				'point_id' => $domain,
				'source' => 'INTERNET'
			],
			'available_programs' => [ 
				[ 
					'available_parts_count' => [ $selected_payments_qty ],
					'type' => 'payment_installments'
				]
			],
			'products' => $this->get_order_products( $products ),
			'result_callback' => $callback_url
		];



		$request_string = wp_json_encode( $request_data );
		$signature = base64_encode( hash_hmac( 'sha256', $request_string, $sign_key, true ) );
		$this->log_message( 'REQUEST DATA: ' . print_r( $request_data, true ) );
		$this->log_message( 'store_id: ' . print_r( $store_id, true ) );
		$this->log_message( 'request_string: ' . print_r( $request_string, true ) );
		$response = wp_remote_post( $api_url . '/api/order/create', [ 
			'method' => 'POST',
			'body' => $request_string,
			'headers' => [ 
				'store-id' => $store_id,
				'signature' => $signature,
				'Content-Type' => 'application/json',
				'Accept' => 'application/json'
			]
		] );

		// Log the response for debugging
		$this->log_message( 'Mono Part Pay API Response: ' . print_r( $response, true ) );

		return $response;
	}

	/**
	 * Get products from the order in the required format.
	 *
	 * @param array $products Order products array.
	 * @return array Array of products for API call.
	 */
	private function get_order_products( $products ) {
		$product_data = [];

		foreach ( $products as $product_item ) {
			$product = wc_get_product( $product_item->get_product_id() );
			$product_data[] = [ 
				'name' => $product->get_name(),
				'count' => $product_item->get_quantity(),
				'sum' => $product_item->get_total()
			];
		}

		return $product_data;
	}

	/**
	 * Get the order object depending on the WooCommerce storage system (HPOS or Legacy).
	 *
	 * @param int $order_id The ID of the order.
	 * @return WC_Order|bool The order object, or false on failure.
	 */
	private function get_order_object( $order_id ) {
		if ( class_exists( 'Automattic\WooCommerce\Utilities\OrderUtil' ) && OrderUtil::custom_orders_table_usage_is_enabled() ) {
			// For HPOS: Use the provided order ID directly
			$order = wc_get_order( $order_id );
		} else {
			// For legacy storage: Get the order from the post ID
			$order = wc_get_order( $order_id );
		}

		return $order;
	}

	/**
	 * Function to check Mono order status via Ajax, save the response, and send the response back.
	 */
	public function check_mono_order_status_ajax() {
		if ( ! isset( $_POST['order_id'] ) ) {
			wp_send_json_error( array( 'message' => 'Missing order ID' ) );
			return;
		}

		$order_id = intval( $_POST['order_id'] );
		$order = wc_get_order( $order_id );

		if ( ! $order ) {
			wp_send_json_error( array( 'message' => 'Invalid order ID' ) );
			return;
		}

		// Retrieve the Mono Pay Order ID from the meta, considering HPOS and Legacy compatibility
		if ( class_exists( 'Automattic\WooCommerce\Utilities\OrderUtil' ) && OrderUtil::custom_orders_table_usage_is_enabled() ) {
			// HPOS compatible method
			$mono_pay_order_id = $order->get_meta( '_mono_hire_purchase_order_id', true );
			$shipment_state = $order->get_meta( '_mono_order_confirm_shipment_status', true ) ?: null;
		} else {
			// Legacy method
			$mono_pay_order_id = get_post_meta( $order_id, '_mono_hire_purchase_order_id', true );
			$shipment_state = get_post_meta( $order_id, '_mono_order_confirm_shipment_status', true ) ?: null;
		}

		if ( empty( $mono_pay_order_id ) ) {
			wp_send_json_error( array( 'message' => 'Mono Pay Order ID not found.' ) );
			return;
		}

		// Call the function to fetch the order status from Mono API
		$response = $this->get_mono_order_data( $mono_pay_order_id );

		if ( is_wp_error( $response ) ) {
			wp_send_json_error( array( 'message' => 'API request failed', 'error' => $response->get_error_message() ) );
		} else {
			// Decode the response body
			$response_body = json_decode( $response['body'], true );

			// Check if order_id, state, and order_sub_state are present in the response
			if ( isset( $response_body['order_id'], $response_body['state'], $response_body['order_sub_state'] ) ) {
				$state = sanitize_text_field( $response_body['state'] );
				$order_sub_state = sanitize_text_field( $response_body['order_sub_state'] );

				// Save the state and order_sub_state, considering HPOS and Legacy compatibility
				if ( class_exists( 'Automattic\WooCommerce\Utilities\OrderUtil' ) && OrderUtil::custom_orders_table_usage_is_enabled() ) {
					// HPOS compatible method
					$order->update_meta_data( '_mono_order_state', $state );
					$order->update_meta_data( '_mono_order_sub_state', $order_sub_state );
					$order->save();
				} else {
					// Legacy method
					update_post_meta( $order_id, '_mono_order_state', $state );
					update_post_meta( $order_id, '_mono_order_sub_state', $order_sub_state );
				}
			}

			// Send success response back to the Ajax call
			wp_send_json_success( array(
				'message' => 'Order status fetched successfully',
				'response' => $response,
				'sub_state' => $response_body['order_sub_state'],
				'state' => $response_body['state'],
				'shipment_state' => $shipment_state
			) );
		}
	}

	/**
	 * Function to fetch order data from Mono API.
	 *
	 * @param string $mono_pay_order_id The Mono Pay order ID.
	 * @return array|WP_Error The API response or an error.
	 */
	public function get_mono_order_data( $mono_pay_order_id ) {
		$is_test_mode = get_option( 'mono_hire_purchase_test_mode', '0' ) === '1';
		$api_url = $is_test_mode ? get_option( 'mono_hire_purchase_test_api_url' ) : get_option( 'mono_hire_purchase_api_url' );
		$store_id = $is_test_mode ? get_option( 'mono_hire_purchase_test_store_id' ) : get_option( 'mono_hire_purchase_store_id' );
		$sign_key = $is_test_mode ? get_option( 'mono_hire_purchase_test_sign_key' ) : get_option( 'mono_hire_purchase_sign_key' );

		// Construct request data
		$request_data = [ 
			'order_id' => $mono_pay_order_id
		];

		$request_string = wp_json_encode( $request_data );
		$signature = base64_encode( hash_hmac( 'sha256', $request_string, $sign_key, true ) );

		// Make the API call to fetch order data
		$response = wp_remote_post( $api_url . '/api/order/state', [ 
			'method' => 'POST',
			'body' => $request_string,
			'headers' => [ 
				'store-id' => $store_id,
				'signature' => $signature,
				'Content-Type' => 'application/json',
				'Accept' => 'application/json'
			]
		] );

		return $response;
	}

	public function reject_mono_order_ajax() {
		if ( ! isset( $_POST['order_id'] ) ) {
			wp_send_json_error( array( 'message' => 'Missing order ID' ) );
			return;
		}

		$order_id = intval( $_POST['order_id'] );
		$order = wc_get_order( $order_id );

		if ( ! $order ) {
			wp_send_json_error( array( 'message' => 'Invalid order ID' ) );
			return;
		}

		// Retrieve the Mono Pay Order ID from the meta, considering HPOS and Legacy compatibility
		if ( class_exists( 'Automattic\WooCommerce\Utilities\OrderUtil' ) && OrderUtil::custom_orders_table_usage_is_enabled() ) {
			// HPOS compatible method
			$mono_pay_order_id = $order->get_meta( '_mono_hire_purchase_order_id', true );
		} else {
			// Legacy method
			$mono_pay_order_id = get_post_meta( $order_id, '_mono_hire_purchase_order_id', true );
		}

		if ( empty( $mono_pay_order_id ) ) {
			wp_send_json_error( array( 'message' => 'Mono Pay Order ID not found.' ) );
			return;
		}

		// Call the function to reject the order via the Mono Pay API
		$response = $this->reject_mono_order( $mono_pay_order_id );

		if ( is_wp_error( $response ) ) {
			wp_send_json_error( array( 'message' => 'API request failed', 'error' => $response->get_error_message() ) );
		} else {
			$response_body = json_decode( $response['body'], true );

			// If the order was rejected by the store, remove meta fields
			if ( isset( $response_body['order_sub_state'] ) && $response_body['order_sub_state'] === 'REJECTED_BY_STORE' && isset( $response_body['state'] ) && $response_body['state'] === 'FAIL' ) {
				$state = sanitize_text_field( $response_body['state'] );
				$order_sub_state = sanitize_text_field( $response_body['order_sub_state'] );
				if ( class_exists( 'Automattic\WooCommerce\Utilities\OrderUtil' ) && OrderUtil::custom_orders_table_usage_is_enabled() ) {
					// HPOS compatible method
					$order->update_meta_data( '_mono_order_state', $state );
					$order->update_meta_data( '_mono_order_sub_state', $order_sub_state );
					$order->delete_meta_data( '_mono_hire_purchase_order_id' );
					$order->delete_meta_data( '_mono_hire_purchase_status' );
					$order->delete_meta_data( '_mono_order_confirm_shipment_status' );
					$order->save();  // Save the order
				} else {
					// Legacy method
					update_post_meta( $order_id, '_mono_order_state', $state );
					update_post_meta( $order_id, '_mono_order_sub_state', $order_sub_state );
					delete_post_meta( $order_id, '_mono_hire_purchase_order_id' );
					delete_post_meta( $order_id, '_mono_hire_purchase_status' );
					delete_post_meta( $order_id, '_mono_order_confirm_shipment_status' );
				}
				$order->update_status( 'cancelled', esc_html__( 'Mono Part Pay method cancelled by shop admin', 'mono-hire-purchase' ) );
				$order->save();

				// Return a success response after the meta fields are removed
				wp_send_json_success( array(
					'message' => 'Order rejected successfully, and meta fields removed.',
					'response' => $response_body,
					'sub_state' => $response_body['order_sub_state'],
					'state' => $response_body['state'],
					'order_status_updated' => true
				) );
			} else {
				// Handle other scenarios
				wp_send_json_error( array(
					'message' => 'Order was not rejected by the store.',
					'response' => $response_body
				) );
			}
		}
	}

	/**
	 * Function to reject an order via Mono Pay API.
	 *
	 * @param string $mono_pay_order_id The Mono Pay order ID.
	 * @return array|WP_Error The API response or an error.
	 */
	public function reject_mono_order( $mono_pay_order_id ) {
		$is_test_mode = get_option( 'mono_hire_purchase_test_mode', '0' ) === '1';
		$api_url = $is_test_mode ? get_option( 'mono_hire_purchase_test_api_url' ) : get_option( 'mono_hire_purchase_api_url' );
		$store_id = $is_test_mode ? get_option( 'mono_hire_purchase_test_store_id' ) : get_option( 'mono_hire_purchase_store_id' );
		$sign_key = $is_test_mode ? get_option( 'mono_hire_purchase_test_sign_key' ) : get_option( 'mono_hire_purchase_sign_key' );

		// Construct request data
		$request_data = [ 
			'order_id' => $mono_pay_order_id
		];

		$request_string = wp_json_encode( $request_data );
		$signature = base64_encode( hash_hmac( 'sha256', $request_string, $sign_key, true ) );

		// Make the API call to reject the order
		$response = wp_remote_post( $api_url . '/api/order/reject', [ 
			'method' => 'POST',
			'body' => $request_string,
			'headers' => [ 
				'store-id' => $store_id,
				'signature' => $signature,
				'Content-Type' => 'application/json',
				'Accept' => 'application/json'
			]
		] );

		return $response;
	}

	public function confirm_mono_order_shipment_ajax() {
		if ( ! isset( $_POST['order_id'] ) ) {
			wp_send_json_error( array( 'message' => 'Missing order ID' ) );
			return;
		}

		$order_id = intval( $_POST['order_id'] );
		$order = wc_get_order( $order_id );

		if ( ! $order ) {
			wp_send_json_error( array( 'message' => 'Invalid order ID' ) );
			return;
		}

		// Retrieve the Mono Pay Order ID from the meta
		if ( class_exists( 'Automattic\WooCommerce\Utilities\OrderUtil' ) && OrderUtil::custom_orders_table_usage_is_enabled() ) {
			$mono_pay_order_id = $order->get_meta( '_mono_hire_purchase_order_id', true );
		} else {
			$mono_pay_order_id = get_post_meta( $order_id, '_mono_hire_purchase_order_id', true );
		}

		if ( empty( $mono_pay_order_id ) ) {
			wp_send_json_error( array( 'message' => 'Mono Pay Order ID not found.' ) );
			return;
		}

		// Call the function to confirm shipment via Mono Pay API
		$response = $this->confirm_mono_order_shipment( $mono_pay_order_id );

		if ( is_wp_error( $response ) ) {
			wp_send_json_error( array( 'message' => 'API request failed', 'error' => $response->get_error_message() ) );
		} else {
			$response_body = json_decode( $response['body'], true );

			// Check if 'state' exists in the response
			if ( isset( $response_body['state'] ) ) {
				$shipment_status = sanitize_text_field( $response_body['state'] );

				// Update the meta field with the shipment status
				if ( class_exists( 'Automattic\WooCommerce\Utilities\OrderUtil' ) && OrderUtil::custom_orders_table_usage_is_enabled() ) {
					$order->update_meta_data( '_mono_order_confirm_shipment_status', $shipment_status );
					$order->save();
				} else {
					update_post_meta( $order_id, '_mono_order_confirm_shipment_status', $shipment_status );
				}

				$order->update_status( 'completed', esc_html__( 'Mono Part Pay finished. Shipment confirmed by shop admin', 'mono-hire-purchase' ) );
				$order->save();
			}

			// Send success response with updated state
			wp_send_json_success( array(
				'message' => 'Shipment confirmed successfully',
				'response' => $response_body,
				'state' => $response_body['state'],
				'order_status_updated' => true
			) );
		}
	}

	public function confirm_mono_order_shipment( $mono_pay_order_id ) {
		$is_test_mode = get_option( 'mono_hire_purchase_test_mode', '0' ) === '1';
		$api_url = $is_test_mode ? get_option( 'mono_hire_purchase_test_api_url' ) : get_option( 'mono_hire_purchase_api_url' );
		$store_id = $is_test_mode ? get_option( 'mono_hire_purchase_test_store_id' ) : get_option( 'mono_hire_purchase_store_id' );
		$sign_key = $is_test_mode ? get_option( 'mono_hire_purchase_test_sign_key' ) : get_option( 'mono_hire_purchase_sign_key' );

		// Construct the request data
		$request_data = [ 
			'order_id' => $mono_pay_order_id
		];

		$request_string = wp_json_encode( $request_data );
		$signature = base64_encode( hash_hmac( 'sha256', $request_string, $sign_key, true ) );

		// Make the API call to confirm shipment
		$response = wp_remote_post( $api_url . '/api/order/confirm', [ 
			'method' => 'POST',
			'body' => $request_string,
			'headers' => [ 
				'store-id' => $store_id,
				'signature' => $signature,
				'Content-Type' => 'application/json',
				'Accept' => 'application/json'
			]
		] );

		return $response;
	}
}

new Mono_Hire_Purchase_API();