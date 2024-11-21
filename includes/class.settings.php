<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class Mono_Hire_Purchase_Settings {

	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_settings_page' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_filter( 'sanitize_option_mono_hire_purchase_available_parts', array( $this, 'sanitize_available_parts' ) );
		add_shortcode( 'mono_hire_purchase_banner', array( $this, 'mono_hire_purchase_banner_shortcode' ) );
	}

	public function add_settings_page() {
		add_submenu_page(
			'woocommerce',
			__( 'Mono Hire Purchase Settings', 'monobank-hire-purchase-gateway' ),
			__( 'Mono Hire Purchase', 'monobank-hire-purchase-gateway' ),
			'manage_options',
			'monobank-hire-purchase-gateway',
			array( $this, 'settings_page_content' )
		);
	}

	public function register_settings() {
		// Register settings
		register_setting( 'mono_hire_purchase_settings', 'mono_hire_purchase_enable_payment_method' );
		register_setting( 'mono_hire_purchase_settings', 'mono_hire_purchase_test_mode' );
		register_setting( 'mono_hire_purchase_settings', 'mono_hire_purchase_store_id' );
		register_setting( 'mono_hire_purchase_settings', 'mono_hire_purchase_sign_key' );
		register_setting( 'mono_hire_purchase_settings', 'mono_hire_purchase_test_store_id' );
		register_setting( 'mono_hire_purchase_settings', 'mono_hire_purchase_test_sign_key' );
		register_setting( 'mono_hire_purchase_settings', 'mono_hire_purchase_available_parts' );
		register_setting( 'mono_hire_purchase_settings', 'mono_hire_purchase_api_url', array( 'sanitize_callback' => array( $this, 'sanitize_url_field' ) ) );
		register_setting( 'mono_hire_purchase_settings', 'mono_hire_purchase_test_api_url', array( 'sanitize_callback' => array( $this, 'sanitize_url_field' ) ) );
		register_setting( 'mono_hire_purchase_settings', 'mono_hire_purchase_payment_logo', array( 'sanitize_callback' => 'esc_url_raw' ) );
		register_setting( 'mono_hire_purchase_settings', 'mono_hire_purchase_banner', array( 'sanitize_callback' => 'esc_url_raw' ) );

		// Add settings section
		add_settings_section(
			'mono_hire_purchase_section',
			__( 'Settings', 'monobank-hire-purchase-gateway' ),
			null,
			'monobank-hire-purchase-gateway'
		);

		// Add Enable Payment Method checkbox (this is the missing field)
		add_settings_field(
			'mono_hire_purchase_enable_payment_method',
			__( 'Enable Part Pay Method', 'monobank-hire-purchase-gateway' ),
			array( $this, 'checkbox_callback' ),
			'monobank-hire-purchase-gateway',
			'mono_hire_purchase_section',
			array(
				'id' => 'mono_hire_purchase_enable_payment_method',
				'option_name' => 'mono_hire_purchase_enable_payment_method'
			)
		);

		// Add Enable Test Mode checkbox
		add_settings_field(
			'mono_hire_purchase_test_mode',
			__( 'Enable Test Mode', 'monobank-hire-purchase-gateway' ),
			array( $this, 'checkbox_callback' ),
			'monobank-hire-purchase-gateway',
			'mono_hire_purchase_section',
			array(
				'id' => 'mono_hire_purchase_test_mode',
				'option_name' => 'mono_hire_purchase_test_mode'
			)
		);

		// Add Test Store ID (this will be hidden if Test Mode is unchecked)
		add_settings_field(
			'mono_hire_purchase_test_store_id',
			__( 'Test Store ID', 'monobank-hire-purchase-gateway' ),
			array( $this, 'text_input_callback' ),
			'monobank-hire-purchase-gateway',
			'mono_hire_purchase_section',
			array(
				'id' => 'mono_hire_purchase_test_store_id',
				'option_name' => 'mono_hire_purchase_test_store_id',
				'class' => 'monobank-hire-purchase-gateway-test-mode-field'
			)
		);

		// Add Test Sign Key (this will be hidden if Test Mode is unchecked)
		add_settings_field(
			'mono_hire_purchase_test_sign_key',
			__( 'Test Sign Key', 'monobank-hire-purchase-gateway' ),
			array( $this, 'text_input_callback' ),
			'monobank-hire-purchase-gateway',
			'mono_hire_purchase_section',
			array(
				'id' => 'mono_hire_purchase_test_sign_key',
				'option_name' => 'mono_hire_purchase_test_sign_key',
				'class' => 'monobank-hire-purchase-gateway-test-mode-field'
			)
		);

		// Test API URL
		add_settings_field(
			'mono_hire_purchase_test_api_url',
			__( 'Test API URL', 'monobank-hire-purchase-gateway' ),
			array( $this, 'text_input_callback' ),
			'monobank-hire-purchase-gateway',
			'mono_hire_purchase_section',
			array(
				'id' => 'mono_hire_purchase_test_api_url',
				'option_name' => 'mono_hire_purchase_test_api_url'
			)
		);

		// Add Store ID (for production)
		add_settings_field(
			'mono_hire_purchase_store_id',
			__( 'Production Store ID', 'monobank-hire-purchase-gateway' ),
			array( $this, 'text_input_callback' ),
			'monobank-hire-purchase-gateway',
			'mono_hire_purchase_section',
			array(
				'id' => 'mono_hire_purchase_store_id',
				'option_name' => 'mono_hire_purchase_store_id',
				'class' => 'monobank-hire-purchase-gateway-prod-mode-field'
			)
		);

		// Add Sign Key (for production)
		add_settings_field(
			'mono_hire_purchase_sign_key',
			__( 'Production Sign Key', 'monobank-hire-purchase-gateway' ),
			array( $this, 'text_input_callback' ),
			'monobank-hire-purchase-gateway',
			'mono_hire_purchase_section',
			array(
				'id' => 'mono_hire_purchase_sign_key',
				'option_name' => 'mono_hire_purchase_sign_key',
				'class' => 'monobank-hire-purchase-gateway-prod-mode-field'
			)
		);

		// Production API URL
		add_settings_field(
			'mono_hire_purchase_api_url',
			__( 'Production API URL', 'monobank-hire-purchase-gateway' ),
			array( $this, 'text_input_callback' ),
			'monobank-hire-purchase-gateway',
			'mono_hire_purchase_section',
			array(
				'id' => 'mono_hire_purchase_api_url',
				'option_name' => 'mono_hire_purchase_api_url'
			)
		);

		// Available Parts Field
		add_settings_field(
			'mono_hire_purchase_available_parts',
			__( 'Available Parts', 'monobank-hire-purchase-gateway' ),
			array( $this, 'available_parts_callback' ),
			'monobank-hire-purchase-gateway',
			'mono_hire_purchase_section',
			array(
				'id' => 'mono_hire_purchase_available_parts',
				'option_name' => 'mono_hire_purchase_available_parts'
			)
		);

		// Add Payment Method Logo
		add_settings_field(
			'mono_hire_purchase_payment_logo',
			__( 'Payment Method Logo', 'monobank-hire-purchase-gateway' ),
			array( $this, 'image_upload_callback' ),
			'monobank-hire-purchase-gateway',
			'mono_hire_purchase_section',
			array(
				'id' => 'mono_hire_purchase_payment_logo',
				'option_name' => 'mono_hire_purchase_payment_logo',
				'description' => __( 'This logo is displayed on a checkout page.', 'monobank-hire-purchase-gateway' )
			)
		);

		// Add Mono Part Pay Banner
		add_settings_field(
			'mono_hire_purchase_banner',
			__( 'Mono Part Pay Banner', 'monobank-hire-purchase-gateway' ),
			array( $this, 'image_upload_callback' ),
			'monobank-hire-purchase-gateway',
			'mono_hire_purchase_section',
			array(
				'id' => 'mono_hire_purchase_banner',
				'option_name' => 'mono_hire_purchase_banner',
				'description' => __( 'You can place this banner anywhere using <code>[mono_hire_purchase_banner]</code> shortcode', 'monobank-hire-purchase-gateway' )
			)
		);
	}

	public function image_upload_callback( $args ) {
		$option_name = $args['option_name'];
		$value = get_option( $option_name );
		?>
		<div class="mono-pay-image-preview">
			<?php if ( ! empty( $value ) ) : ?>
				<img src="<?php echo esc_url( $value ); ?>" alt="" style="max-width: 100%; height: auto;" />
			<?php endif; ?>
		</div>
		<input type="hidden" id="<?php echo esc_attr( $args['id'] ); ?>" name="<?php echo esc_attr( $option_name ); ?>"
			value="<?php echo esc_attr( $value ); ?>" />
		<button class="button mono-pay-upload-button"><?php esc_html_e( 'Choose Image', 'monobank-hire-purchase-gateway' ); ?></button>
		<button class="button mono-pay-remove-button"
			style="display:<?php echo ( ! empty( $value ) ) ? 'inline-block' : 'none'; ?>"><?php esc_html_e( 'Remove Image', 'monobank-hire-purchase-gateway' ); ?></button>
		<p class="description"><?php echo wp_kses_post( $args['description'] ); ?></p>

		<?php
	}
	public function sanitize_url_field( $url ) {
		// Use WordPress sanitize function to validate URL
		$sanitized_url = esc_url_raw( trim( $url ) );

		// Remove trailing slashes
		return rtrim( $sanitized_url, '/' );
	}
	public function sanitize_available_parts( $input ) {
		// Split the input into an array by commas
		$parts = explode( ',', $input );
		$valid_parts = [];
		$invalid_parts = [];

		// Loop through each part and sanitize
		foreach ( $parts as $part ) {
			$part = intval( trim( $part ) );
			if ( $part >= 3 && $part <= 25 ) {
				$valid_parts[] = $part;  // Valid parts
			} else {
				$invalid_parts[] = $part;  // Invalid parts
			}
		}

		// If there are invalid parts, show an error and return the unprocessed input
		if ( ! empty( $invalid_parts ) ) {
			add_settings_error(
				'mono_hire_purchase_available_parts',
				'invalid_available_parts',
				__( 'Only numbers between 3 and 25 are allowed. These values were rejected: ', 'monobank-hire-purchase-gateway' ) . implode( ', ', $invalid_parts ),
				'error'
			);
			return implode( ', ', $valid_parts );  // Return unprocessed input so the user can fix it
		}

		// If all parts are valid, remove duplicates, sort, and return the valid parts
		$valid_parts = array_unique( $valid_parts );
		sort( $valid_parts );

		return implode( ', ', $valid_parts );
	}

	public function settings_page_content() {
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Mono Hire Purchase Settings', 'monobank-hire-purchase-gateway' ); ?></h1>
			<form method="post" action="options.php">
				<?php
				settings_errors();
				settings_fields( 'mono_hire_purchase_settings' );
				do_settings_sections( 'monobank-hire-purchase-gateway' );
				submit_button();
				?>
			</form>
			<!-- Documentation Link Section -->
			<div class="documentation-section">
				<p><?php esc_html_e( 'For more information about the Mono Hire Purchase API, please refer to the official documentation.', 'monobank-hire-purchase-gateway' ); ?>
				</p>
				<a href="https://u2-demo-ext.mono.st4g3.com/docs/index.html" target="_blank"
					class="button button-secondary"><?php esc_html_e( 'View Documentation', 'monobank-hire-purchase-gateway' ); ?></a>
			</div>
		</div>
		<?php
	}

	public function available_parts_callback( $args ) {
		$option_name = $args['option_name'];
		$value = get_option( $option_name );
		echo '<input type="text" id="' . esc_attr( $args['id'] ) . '" name="' . esc_attr( $option_name ) . '" value="' . esc_attr( $value ) . '" />';
		echo '<p class="description">' . esc_html__( 'Enter comma-separated numbers between 3 and 25.', 'monobank-hire-purchase-gateway' ) . '</p>';
	}
	public function checkbox_callback( $args ) {
		$option_name = $args['option_name'];
		$checked = get_option( $option_name );
		echo '<input type="checkbox" id="' . esc_attr( $args['id'] ) . '" name="' . esc_attr( $option_name ) . '" value="1" ' . checked( 1, $checked, false ) . ' onchange="toggleTestModeFields()"/>';
	}

	public function text_input_callback( $args ) {
		$option_name = $args['option_name'];
		$value = get_option( $option_name );
		echo '<input type="text" id="' . esc_attr( $args['id'] ) . '" name="' . esc_attr( $option_name ) . '" value="' . esc_attr( $value ) . '" />';
	}

	public function mono_hire_purchase_banner_shortcode() {
		// Get the banner image URL from the settings
		$banner_url = get_option( 'mono_hire_purchase_banner' );

		// Check if the banner image is set
		if ( ! empty( $banner_url ) ) {
			// Return the HTML for displaying the banner image
			return '<div class="monobank-hire-purchase-gateway-banner"><img src="' . esc_url( $banner_url ) . '" alt="Mono Hire Purchase Banner" style="max-width:100%; height:auto;" /></div>';
		}

		// Return empty if no banner is set
		return '';
	}
}

new Mono_Hire_Purchase_Settings();