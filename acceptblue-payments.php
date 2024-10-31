<?php

/*
Plugin Name: Payment gateway: accept.blue for WooCommerce
Plugin URI: https://accept.pay.devurai.com/
Description: The plugin was made for receiving Credit Cards and ACH payments on your store using the accept.blue  payment gateway.
Author: Devurai.
Author URI: https://devurai.com/
Version: 1.4.9
Requires at least: 4.4
Tested up to: 6.4
WC requires at least: 2.5
WC tested up to: 7.3
Text Domain: payment-gateway-accept-blue-for-woocommerce
Domain Path: /languages
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
require 'vendor/autoload.php';
include 'includes/functions.php';
if ( is_plugin_active( 'woocommerce/woocommerce.php') ) {

	define( 'PGABFW_PLUGIN_ROOT', plugin_dir_path( __FILE__ ) );
	define( 'PGABFW_STYLES_BACKEND', plugin_dir_url( __FILE__ ) . 'assets/css/backend/' );
	define( 'PGABFW_STYLES_FRONTEND', plugin_dir_url( __FILE__ ) . 'assets/css/frontend/' );
	define( 'PGABFW_SCRIPTS_BACKEND', plugin_dir_url( __FILE__ ) . 'assets/js/backend/' );
	define( 'PGABFW_SCRIPTS_FRONTEND', plugin_dir_url( __FILE__ ) . 'assets/js/frontend/' );
	define( 'PGABFW_SCRIPTS', plugin_dir_url( __FILE__ ) . 'assets/js/' );
	define( 'PGABFW_WC_BLOCKS', plugin_dir_url( __FILE__ ) . 'wc_blocks/' );
	define( 'PGABFW_IMAGES_FRONTEND', plugin_dir_url( __FILE__ ) . 'assets/images/' );
	define( 'PGABFW_LIBS_FRONTEND', plugin_dir_url( __FILE__ ) . 'assets/js/libs/' );
	define( 'PGABFW_LOG_PATH', __DIR__ . DIRECTORY_SEPARATOR );

	add_action( 'plugins_loaded', 'init_acceptblue_gateway' );

	/**
	 * @var $wc_acceptblue_card_api WC_Acceptblue_API
	 */
	$wc_acceptblue_card_api = false;

	/**
	 * @var $wc_acceptblue_ach_api WC_Acceptblue_API
	 */
	$wc_acceptblue_ach_api = false;

	/**
	 * @var $wc_acceptblue_logger WC_Logger
	 */
	$wc_acceptblue_logger = false;

	function init_acceptblue_gateway() {
		include 'includes/wc-acceptblue-api.php';
		include 'includes/wc-acceptblue-gateway-credit-card.php';
		include 'includes/wc-acceptblue-gateway-ach.php';
		include 'includes/wc-acceptblue-recurring-api.php';
		global $wc_acceptblue_card_api;
		global $wc_acceptblue_ach_api;
		global $wc_acceptblue_logger;
		$wc_acceptblue_logger   = new WC_Logger();
		$wc_acceptblue_card_api = new WC_Acceptblue_API();
		$wc_acceptblue_ach_api  = new WC_Acceptblue_API();
	}

	add_filter( 'woocommerce_payment_gateways', 'add_acceptblue_gateway_class' );

	function add_acceptblue_gateway_class( $methods ) {
		$methods[] = 'WC_Acceptblue_Gateway_Credit_Card';
		$methods[] = 'WC_Acceptblue_Gateway_ACH';

		return $methods;
	}

	add_action( 'woocommerce_order_status_on-hold_to_processing', 'pgabfw_charge_payment' );
	add_action( 'woocommerce_order_status_on-hold_to_completed', 'pgabfw_charge_payment' );
	add_action( 'woocommerce_order_status_processing_to_completed', 'pgabfw_charge_payment' );

	add_filter( 'cron_schedules', 'cron_add_for_hour' );
	function cron_add_for_hour( $schedules ) {
		$schedules['for_hour'] = array(
			'interval' => (60 * 60) * 4,
			'display'  => 'Once in four hour'
		);

		return $schedules;
	}

	register_activation_hook( __FILE__, 'my_activation' );
	function my_activation() {
		wp_clear_scheduled_hook( 'acceptblue_check_ach_status' );

		wp_schedule_event( time(), 'for_hour', 'acceptblue_check_ach_status' );
	}

	add_action( 'acceptblue_check_ach_status', 'pgabfw_check_ach_orders_status' );


	register_deactivation_hook( __FILE__, 'my_deactivation' );
	function my_deactivation() {
		wp_clear_scheduled_hook( 'acceptblue_check_ach_status' );
	}


//	add_action( 'woocommerce_order_refunded', 'acceptblue_refund_order', 10, 2 );

	add_filter( 'woocommerce_available_payment_gateways', 'remove_pgabfw_acceptblue_ach' );

	add_action( 'admin_notices', 'pgabfw_acceptblue_ssl_admin_notices', 15 );

	add_action( 'wp_enqueue_scripts', 'enqueue_pgabfw_common_scripts' );

	add_action('wp_enqueue_scripts', 'enqueue_pgabfw_common_styles');

	add_action( 'admin_enqueue_scripts', 'enqueue_pgabfw_admin_scripts' );

	add_action('acceptblue_after_credit_card_form', 'enqueue_pgabfw_credit_card_scripts');

	add_action('acceptblue_after_check_form', 'enqueue_pgabfw_check_scripts');


    /*----------------------------woocommerce blocks integration start-------------------------------------*/
    /**
     * Custom function to declare compatibility with cart_checkout_blocks feature
     */
    function pgabfw_declare_cart_checkout_blocks_compatibility() {
        if (class_exists('\Automattic\WooCommerce\Utilities\FeaturesUtil')) {
            \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('cart_checkout_blocks', __FILE__, true);
        }
    }
    add_action('before_woocommerce_init', 'pgabfw_declare_cart_checkout_blocks_compatibility');


    add_action( 'woocommerce_blocks_loaded', 'pgabfw_register_order_approval_payment_method_type' );
    function pgabfw_register_order_approval_payment_method_type() {
        if ( ! class_exists( 'Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType' ) ) {
            return;
        }
        require_once 'includes/WC_AcceptBlue_CC_Block.php';
        require_once 'includes/WC_AcceptBlue_ACH_Block.php';

        add_action(
            'woocommerce_blocks_payment_method_type_registration',
            function (Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $payment_method_registry) {
                $gateways = WC()->payment_gateways->payment_gateways();
                if(isset($gateways['acceptblue-cc'])){
                    $payment_method_registry->register(new WC_AcceptBlue_CC_Block($gateways['acceptblue-cc']));
                }
                if(isset($gateways['acceptblue-ach'])){
                    $payment_method_registry->register(new WC_AcceptBlue_ACH_Block($gateways['acceptblue-ach']));
                }
            }
        );
    }
}else{
	add_action( 'admin_notices', 'pgabfw_acceptblue_woocommerce_is_not_active_notice', 15 );
}
