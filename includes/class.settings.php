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
			__( 'Mono Hire Purchase Settings', 'mono-hire-purchase' ),
			__( 'Mono Hire Purchase', 'mono-hire-purchase' ),
			'manage_options',
			'mono-hire-purchase',
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
			__( 'Settings', 'mono-hire-purchase' ),
			null,
			'mono-hire-purchase'
		);

		// Add Enable Payment Method checkbox (this is the missing field)
		add_settings_field(
			'mono_hire_purchase_enable_payment_method',
			__( 'Enable Part Pay Method', 'mono-hire-purchase' ),
			array( $this, 'checkbox_callback' ),
			'mono-hire-purchase',
			'mono_hire_purchase_section',
			array(
				'id' => 'mono_hire_purchase_enable_payment_method',
				'option_name' => 'mono_hire_purchase_enable_payment_method'
			)
		);

		// Add Enable Test Mode checkbox
		add_settings_field(
			'mono_hire_purchase_test_mode',
			__( 'Enable Test Mode', 'mono-hire-purchase' ),
			array( $this, 'checkbox_callback' ),
			'mono-hire-purchase',
			'mono_hire_purchase_section',
			array(
				'id' => 'mono_hire_purchase_test_mode',
				'option_name' => 'mono_hire_purchase_test_mode'
			)
		);

		// Add Test Store ID (this will be hidden if Test Mode is unchecked)
		add_settings_field(
			'mono_hire_purchase_test_store_id',
			__( 'Test Store ID', 'mono-hire-purchase' ),
			array( $this, 'text_input_callback' ),
			'mono-hire-purchase',
			'mono_hire_purchase_section',
			array(
				'id' => 'mono_hire_purchase_test_store_id',
				'option_name' => 'mono_hire_purchase_test_store_id',
				'class' => 'mono-hire-purchase-test-mode-field'
			)
		);

		// Add Test Sign Key (this will be hidden if Test Mode is unchecked)
		add_settings_field(
			'mono_hire_purchase_test_sign_key',
			__( 'Test Sign Key', 'mono-hire-purchase' ),
			array( $this, 'text_input_callback' ),
			'mono-hire-purchase',
			'mono_hire_purchase_section',
			array(
				'id' => 'mono_hire_purchase_test_sign_key',
				'option_name' => 'mono_hire_purchase_test_sign_key',
				'class' => 'mono-hire-purchase-test-mode-field'
			)
		);

		// Test API URL
		add_settings_field(
			'mono_hire_purchase_test_api_url',
			__( 'Test API URL', 'mono-hire-purchase' ),
			array( $this, 'text_input_callback' ),
			'mono-hire-purchase',
			'mono_hire_purchase_section',
			array(
				'id' => 'mono_hire_purchase_test_api_url',
				'option_name' => 'mono_hire_purchase_test_api_url'
			)
		);

		// Add Store ID (for production)
		add_settings_field(
			'mono_hire_purchase_store_id',
			__( 'Production Store ID', 'mono-hire-purchase' ),
			array( $this, 'text_input_callback' ),
			'mono-hire-purchase',
			'mono_hire_purchase_section',
			array(
				'id' => 'mono_hire_purchase_store_id',
				'option_name' => 'mono_hire_purchase_store_id',
				'class' => 'mono-hire-purchase-prod-mode-field'
			)
		);

		// Add Sign Key (for production)
		add_settings_field(
			'mono_hire_purchase_sign_key',
			__( 'Production Sign Key', 'mono-hire-purchase' ),
			array( $this, 'text_input_callback' ),
			'mono-hire-purchase',
			'mono_hire_purchase_section',
			array(
				'id' => 'mono_hire_purchase_sign_key',
				'option_name' => 'mono_hire_purchase_sign_key',
				'class' => 'mono-hire-purchase-prod-mode-field'
			)
		);

		// Production API URL
		add_settings_field(
			'mono_hire_purchase_api_url',
			__( 'Production API URL', 'mono-hire-purchase' ),
			array( $this, 'text_input_callback' ),
			'mono-hire-purchase',
			'mono_hire_purchase_section',
			array(
				'id' => 'mono_hire_purchase_api_url',
				'option_name' => 'mono_hire_purchase_api_url'
			)
		);

		// Available Parts Field
		add_settings_field(
			'mono_hire_purchase_available_parts',
			__( 'Available Parts', 'mono-hire-purchase' ),
			array( $this, 'available_parts_callback' ),
			'mono-hire-purchase',
			'mono_hire_purchase_section',
			array(
				'id' => 'mono_hire_purchase_available_parts',
				'option_name' => 'mono_hire_purchase_available_parts'
			)
		);

		// Add Payment Method Logo
		add_settings_field(
			'mono_hire_purchase_payment_logo',
			__( 'Payment Method Logo', 'mono-hire-purchase' ),
			array( $this, 'image_upload_callback' ),
			'mono-hire-purchase',
			'mono_hire_purchase_section',
			array(
				'id' => 'mono_hire_purchase_payment_logo',
				'option_name' => 'mono_hire_purchase_payment_logo',
				'description' => __( 'This logo is displayed on a checkout page.', 'mono-hire-purchase' )
			)
		);

		// Add Mono Part Pay Banner
		add_settings_field(
			'mono_hire_purchase_banner',
			__( 'Mono Part Pay Banner', 'mono-hire-purchase' ),
			array( $this, 'image_upload_callback' ),
			'mono-hire-purchase',
			'mono_hire_purchase_section',
			array(
				'id' => 'mono_hire_purchase_banner',
				'option_name' => 'mono_hire_purchase_banner',
				'description' => __( 'You can place this banner anywhere using <code>[mono_hire_purchase_banner]</code> shortcode', 'mono-hire-purchase' )
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
		<button class="button mono-pay-upload-button"><?php esc_html_e( 'Choose Image', 'mono-hire-purchase' ); ?></button>
		<button class="button mono-pay-remove-button"
			style="display:<?php echo ( ! empty( $value ) ) ? 'inline-block' : 'none'; ?>"><?php esc_html_e( 'Remove Image', 'mono-hire-purchase' ); ?></button>
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
				__( 'Only numbers between 3 and 25 are allowed. These values were rejected: ', 'mono-hire-purchase' ) . implode( ', ', $invalid_parts ),
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
			<h1><?php esc_html_e( 'Mono Hire Purchase Settings', 'mono-hire-purchase' ); ?></h1>
			<form method="post" action="options.php">
				<?php
				settings_errors();
				settings_fields( 'mono_hire_purchase_settings' );
				do_settings_sections( 'mono-hire-purchase' );
				submit_button();
				?>
			</form>
			<!-- Documentation Link Section -->
			<div class="documentation-section">
				<p><?php esc_html_e( 'For more information about the Mono Hire Purchase API, please refer to the official documentation.', 'mono-hire-purchase' ); ?>
				</p>
				<a href="https://u2-demo-ext.mono.st4g3.com/docs/index.html" target="_blank"
					class="button button-secondary"><?php esc_html_e( 'View Documentation', 'mono-hire-purchase' ); ?></a>
			</div>
		</div>

		<script>
			document.addEventListener('DOMContentLoaded', function () {
				const testModeCheckbox = document.querySelector('#mono_hire_purchase_test_mode');
				const testStoreId = document.querySelector('#mono_hire_purchase_test_store_id').closest('tr');
				const testSignKey = document.querySelector('#mono_hire_purchase_test_sign_key').closest('tr');
				const testApiUrl = document.querySelector('#mono_hire_purchase_test_api_url').closest('tr');

				const prodStoreId = document.querySelector('#mono_hire_purchase_store_id').closest('tr');
				const prodSignKey = document.querySelector('#mono_hire_purchase_sign_key').closest('tr');
				const prodApiUrl = document.querySelector('#mono_hire_purchase_api_url').closest('tr');

				function toggleFields() {
					if (testModeCheckbox.checked) {
						testStoreId.style.display = '';
						testSignKey.style.display = '';
						testApiUrl.style.display = '';

						prodStoreId.style.display = 'none';
						prodSignKey.style.display = 'none';
						prodApiUrl.style.display = 'none';
					} else {
						testStoreId.style.display = 'none';
						testSignKey.style.display = 'none';
						testApiUrl.style.display = 'none';

						prodStoreId.style.display = '';
						prodSignKey.style.display = '';
						prodApiUrl.style.display = '';
					}
				}

				// Initialize toggle state
				toggleFields();

				// Add event listener for changes in test mode checkbox
				testModeCheckbox.addEventListener('change', toggleFields);
			});
			jQuery(document).ready(function ($) {
				// Media uploader for image fields
				$('.mono-pay-upload-button').click(function (e) {
					e.preventDefault();
					var button = $(this);
					var id = button.prevAll('input[type="hidden"]').attr('id'); // Adjusted to find the input hidden field

					var custom_uploader = wp.media({
						title: '<?php esc_html_e( "Select Image", "mono-hire-purchase" ); ?>',
						button: {
							text: '<?php esc_html_e( "Use this image", "mono-hire-purchase" ); ?>'
						},
						multiple: false
					}).on('select', function () {
						var attachment = custom_uploader.state().get('selection').first().toJSON();
						$('#' + id).val(attachment.url); // Set the image URL in the hidden input
						button.prevAll('.mono-pay-image-preview').html('<img src="' + attachment.url + '" style="max-width:100%; height:auto;" />'); // Update preview
						button.siblings('.mono-pay-remove-button').show(); // Show remove button
					}).open();
				});

				// Remove image functionality
				$('.mono-pay-remove-button').click(function (e) {
					e.preventDefault();
					var button = $(this);
					var input = button.siblings('input[type="hidden"]'); // Adjusted to target the hidden input
					input.val(''); // Clear the input field value
					button.siblings('.mono-pay-image-preview').html(''); // Clear the preview image
					button.hide(); // Hide the remove button
				});

				// Update the preview image on page load if an image exists
				$('.mono-pay-image-preview').each(function () {
					var imgSrc = $(this).siblings('input[type="hidden"]').val(); // Adjusted to target the hidden input
					if (imgSrc) {
						$(this).html('<img src="' + imgSrc + '" style="max-width:100%; height:auto;" />');
						$(this).siblings('.mono-pay-remove-button').show(); // Show remove button if image exists
					}
				});
			});
		</script>
		<?php
	}

	public function available_parts_callback( $args ) {
		$option_name = $args['option_name'];
		$value = get_option( $option_name );
		echo '<input type="text" id="' . esc_attr( $args['id'] ) . '" name="' . esc_attr( $option_name ) . '" value="' . esc_attr( $value ) . '" />';
		echo '<p class="description">' . esc_html_e( 'Enter comma-separated numbers between 3 and 25.', 'mono-hire-purchase' ) . '</p>';
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
			return '<div class="mono-hire-purchase-banner"><img src="' . esc_url( $banner_url ) . '" alt="Mono Hire Purchase Banner" style="max-width:100%; height:auto;" /></div>';
		}

		// Return empty if no banner is set
		return '';
	}
}

new Mono_Hire_Purchase_Settings();