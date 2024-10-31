=== Payment gateway: accept.blue for WooCommerce ===
Contributors: Devurai
Donate link: https://accept.pay.devurai.com/donate/
Tags: accept.blue, payments, credit card, ACH
WordPress required: 4.4 or newer
Tested up to: 6.4
Requires PHP: 7.3
Stable tag: 1.4.9
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

This plugin was made for receiving Credit Cards and ACH payments on your store using the accept.blue  payment gateway.

== Description ==
Our plugin allows you to start accepting payments for your e-commerce business with the accept.blue payment gateway! 
Streamline processing and increase revenues with our built-for-success payment plugin.

### Features
* Works with all Major Credit Cards / Debit Cards and supports ACH transactions
* Process refunds and voids within WooCommerce
* Capture pre-authorized transactions from the WooCommerce edit order screen
* Force charge for orders with only virtual items instead of authorizing them
* Automatically capture transactions when order status changes to a paid status
* Updated payment form at checkout with retina icons
* Mobile-friendly checkout with numerical inputs for card type and security code
* Show detailed decline messages at checkout instead of generic errors
* WooCommerce block-based checkout support
* Customers never leave your site during a zero-friction checkout experience
* WooCommerce Subscription supported

### Gateway requirements
* You’ll need an accept.blue merchant account in order to use this plugin. For account access, reach out to your Merchant Service Provider or ISO.
* Create an API key and PIN, followed by a Tokenization key. Keys are created via Control Panel>Sources>Create key.
* Choose API from the dropdown and then enter a PIN. Enter any name to recognize these transactions as coming from the Woo plugin, e.g., “WooCommerce”. Click Save. The self-generated key will be visible on your Sources list.
* Repeat the step above but select Tokenization this time instead of API.
* You’ll need all 3 pieces of data to connect your e-commerce store to your merchant account.

### Technical Requirements
* WordPress version 3.5 or higher
* WooCommerce version 3.0 and above
* PHP version 7.3 or higher

== Installation ==
- In your WordPress admin dashboard, Visit Plugins > Add New
- Search for accept.blue Gateway
- Click Install Now, and then Activate
- Go to WooCommerce > Settings > Payments > Accept Blue Credit Card > Manage
- Enter the production API key, PIN and Hosted Tokenization if these were generated from a live merchant account for Credit Card and ACH/Check settings

== Frequently Asked Questions ==
= Does this require an SSL certificate? =
Yes, an SSL certificate must be installed on your site.
= Does this support sandbox mode for testing? =
Yes, it does – production and test (sandbox) mode is driven by the API keys you use with a checkbox in the admin settings to toggle between both.
= Where can I get support or talk to other users? =
If you get stuck, you can reach out to developers directly to info@devurai.com

== Screenshots ==
1. Checkout page
2. Installation
3. Credit Card sandbox mode
4. ACH/Check sandbox mode
5. Credit Card production mode
6. ACH/Check production mode

== Changelog ==
= 1.4.9 =
added the possibility of capturing adjusted transaction amounts
= 1.4.8 =
fixed the issue with the transactions not passing the CVV through to the payment gateway
= 1.4.7 =
fixed the issue with the double charge of WooCommerce subscription renewal orders
= 1.4.6 =
fix improve payment form layout responsiveness
= 1.4.5 =
Fix 500 error for WooCommerce block-based checkout
= 1.4.4 =
Add WooCommerce block-based checkout support
= 1.4.3 =
Recurring API bug fix
= 1.4.2 =
Fixed the display of the "Save payment method" checkbox
= 1.4.1 =
Tested up to: 6.4
= 1.4.0 =
Integrated Partial Refund with WooCommerce Subscriptions
= 1.3.9 =
Added the possibility of a partial refund
= 1.3.8 =
Added the ability to purchase subscriptions without registration.
= 1.3.7 =
fix refund
= 1.3.6 =
fix woocommerce subscriptions integration
= 1.3.5 =
Add Sequential Order Number support
= 1.3.4 =
Fix ACH Check Cron
= 1.3.3 =
Bug fixing
= 1.3.2 =
Bug fixing
= 1.3.1 =
Bug fixing
= 1.3.0 =
Updated plugin design
= 1.2.0 =
Bug fixing
Added expanded validation of the Card Number, Exp date fields
The tax amount has appeared in the gateway in the Tax field
Remove "Save card" option if a user is not logging
= 1.1.0 =
WooCommerce Subscription supported
= 1.0.0 =
Initial release version