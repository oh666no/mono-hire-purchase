=== Mono Hire Purchase ===
Contributors: pkotula
Donate link: https://pkotula.com/
Tags: woocommerce, payment gateway, monobank, installments, payments
Requires at least: 5.0
Tested up to: 6.3
Requires PHP: 7.2
Stable tag: 1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Integrate Mono Bank's installment payment system into WooCommerce, allowing customers to split payments and providing real-time payment status updates and order management.

== Description ==

The **Mono Hire Purchase** plugin adds a new payment gateway to your WooCommerce store, enabling customers to pay for their orders in installments using Mono Bank's installment system. Enhance your store by offering flexible payment options and manage orders effectively with real-time payment status updates.

**Key Features:**

- **Flexible Installment Payments:** Allow customers to split their payments into multiple installments, making higher-priced items more accessible.
- **Customizable Installment Options:** Configure available installment plans (e.g., 3, 4, 6, 9 payments) to suit your business needs.
- **Secure Transactions:** Communicate securely with Mono Bank's API using HMAC signatures and shared secret keys.
- **Real-time Payment Updates:** Receive instant payment status updates via callbacks and update orders accordingly.
- **Admin Order Management:** Perform custom actions on orders, such as checking payment status, confirming shipment, or rejecting an order.
- **High-Performance Order Storage (HPOS) Compatible:** Fully compatible with WooCommerce's HPOS feature for efficient order management.
- **Customizable Payment Method Logo and Banner:** Upload custom images for the payment method logo and promotional banners to enhance the user experience.
- **Internationalization (i18n):** Translation-ready, allowing you to localize the plugin to your preferred language.

**Note:** This plugin requires an active Mono Bank merchant account and WooCommerce installed and activated on your WordPress site.

== Installation ==

1. **Upload the Plugin Files:**
   - Upload the `mono-pay-part` directory to the `/wp-content/plugins/` directory, or install the plugin through the WordPress plugins screen directly.

2. **Activate the Plugin:**
   - Activate the plugin through the 'Plugins' screen in WordPress.

3. **Ensure WooCommerce is Installed and Activated:**
   - The plugin requires WooCommerce to function properly.

4. **Configure the Plugin:**
   - Navigate to **WooCommerce > Mono Hire Purchase** in the WordPress admin dashboard.
   - Enter your Mono Bank API credentials, configure available installments, and upload custom images if desired.
   - Enable the payment gateway by checking the "Enable Hire Purchase Method" option.

5. **Set Up Payment Gateway:**
   - Go to **WooCommerce > Settings > Payments**.
   - Ensure that "Mono Hire Purchase Method" is enabled and configured as desired.

6. **Test the Integration:**
   - Place a test order to ensure the payment gateway is working correctly.
   - Use the plugin's test mode to safely test transactions without affecting live data.

== Frequently Asked Questions ==

= Do I need a Mono Bank merchant account to use this plugin? =

Yes, you need an active Mono Bank merchant account to use this plugin. You'll receive API credentials from Mono Bank, which are required for the plugin to communicate with their system.

= Is the plugin compatible with the latest version of WooCommerce and WordPress? =

The plugin is tested up to WooCommerce 8.0 and WordPress 6.3. It's also compatible with WooCommerce's High-Performance Order Storage (HPOS) feature.

= How do I configure the available installment options? =

In the plugin settings under **WooCommerce > Mono Hire Purchase**, you can specify the available installments by entering comma-separated numbers (e.g., `3, 4, 6, 9`).

= Can I use the plugin in test mode? =

Yes, the plugin supports a test mode. You can enable test mode in the plugin settings and use test API credentials provided by Mono Bank to conduct safe testing.

= How do I display the promotional banner on my site? =

You can upload a promotional banner image in the plugin settings and display it anywhere on your site using the shortcode `[mono_pay_part_banner]`.

= Is the plugin translation-ready? =

Yes, the plugin is fully internationalized and ready for translation. You can translate it into your preferred language using standard WordPress translation methods.

== Screenshots ==

1. **Plugin Settings Page:** Configure API credentials, available installments, and upload custom images.
2. **Checkout Page Payment Method:** Customers can select the Mono Hire Purchase Method and choose the number of installments.
3. **Order Edit Screen:** Administrators can perform custom actions related to the payment method.
4. **Promotional Banner Displayed Using Shortcode:** Enhance your site with a customizable promotional banner.

== Changelog ==

= 1.0 =
* Initial release of the Mono Hire Purchase plugin.
* Added Mono Bank installment payment gateway integration.
* Included admin settings for configuration.
* Implemented secure API communication with Mono Bank.
* Provided custom order actions for administrators.
* Added shortcode for displaying promotional banners.
* Ensured compatibility with WooCommerce HPOS.
* Added Checkout Blocks compatibility

== Upgrade Notice ==

= 1.0 =
Initial release. Install and configure the plugin to start offering Mono Bank installment payments to your customers.

== License ==

This plugin is licensed under the GNU General Public License v2.0 or later.

== Other Notes ==

**Support:**

For support or questions, please visit [https://pkotula.com/](https://pkotula.com/) or contact the plugin author directly.

**Documentation:**

Refer to the official Mono Bank API documentation for detailed information on API endpoints and usage.

---

**Important Notes:**

- **Ensure Compliance:** Before submitting your plugin to the WordPress Plugin Directory, make sure it complies with all WordPress guidelines and policies.
- **Test Thoroughly:** Test your plugin extensively in different environments to ensure compatibility and stability.
- **Security:** Verify that all API communications are secure and that sensitive data is handled appropriately.
- **Update Tags and Tested Up To:** Keep the `Tested up to` and `Requires at least` fields updated with the latest WordPress versions as you maintain the plugin.

---

Feel free to modify any section of this `readme.txt` file to better suit your plugin's features or to add any additional information you deem necessary. If you have any questions or need further assistance, please let me know!