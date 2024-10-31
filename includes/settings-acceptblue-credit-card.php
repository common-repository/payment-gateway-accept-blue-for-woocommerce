<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

return apply_filters( 'wc_acceptblue_settings_credit_card', array(
	'enabled' => array(
		'title' => __('Enabled/Disabled', 'payment-gateway-accept-blue-for-woocommerce'),
		'label'       => __( 'Enable Accept.blue', 'payment-gateway-accept-blue-for-woocommerce' ),
		'type'        => 'checkbox',
		'description' => '',
		'default'     => 'no',
	),
	'title' => array(
		'title'       => __( 'Title', 'payment-gateway-accept-blue-for-woocommerce' ),
		'type'        => 'text',
		'description' => __( 'This controls the title which the user sees during checkout.', 'payment-gateway-accept-blue-for-woocommerce' ),
		'default'     => __( 'Credit Card', 'payment-gateway-accept-blue-for-woocommerce' ),
		'desc_tip'    => true,
	),
	'description' => array(
		'title'       => __( 'Description', 'payment-gateway-accept-blue-for-woocommerce' ),
		'type'        => 'text',
		'description' => __( 'This controls the description which the user sees during checkout.', 'payment-gateway-accept-blue-for-woocommerce' ),
		'default'     => __( 'Pay with your credit card.', 'payment-gateway-accept-blue-for-woocommerce' ),
		'desc_tip'    => true,
	),

	'transaction_type' => array(
		'title'       => __('Transaction type', 'payment-gateway-accept-blue-for-woocommerce' ),
		'type'        => 'select',
		'description' => 'If the Authorize transaction type is selected, the Charge type will be applied to an order with subscription',
		'default'     => 'charge',
		'options'     => array(
			'authorize' => 'Authorize',
			'charge' => 'Charge'
		)

	),
	'charge_order' => array(
		'title'     => __('Enabled/Disabled', 'payment-gateway-accept-blue-for-woocommerce' ),
		'label'     => __('Charge "Complete" or "Processing" orders', 'payment-gateway-accept-blue-for-woocommerce'),
		'type'      => 'checkbox',
		'description' => __('If this option is enabled, the transaction will be Charged after order status changes from "Hold on" to "Complete" or "Processing".', 'payment-gateway-accept-blue-for-woocommerce'),
		'default'     => 'yes',
	),
	'charge_virtual' => array(
		'title'    => __('Enabled/Disabled', 'payment-gateway-accept-blue-for-woocommerce' ),
		'label'     => __('Charge virtual-only orders', 'payment-gateway-accept-blue-for-woocommerce'),
		'type'      => 'checkbox',
		'description' => __('If enabled this option the transaction with virtual products will be charged.', 'payment-gateway-accept-blue-for-woocommerce'),
		'default'     => 'no',
	),
	'enabled_debug_mode' => array(
		'title' => __('Enabled/Disabled', 'payment-gateway-accept-blue-for-woocommerce'),
		'label'       => __( 'Enable debug mode', 'payment-gateway-accept-blue-for-woocommerce' ),
		'type'        => 'checkbox',
		'description' => '',
		'default'     => 'no',
	),
	'public_key' => array(
		'title'       => __( 'Tokenization key', 'payment-gateway-accept-blue-for-woocommerce' ),
		'type'        => 'text',
		'description' => __( 'Tokenization key for Accept.blue Payment API. To get the key follow next route in your Accept.blue account: control panel/sources/press "edit" for type "Tokenization" source.', 'payment-gateway-accept-blue-for-woocommerce' ),
		'default'     => '',
		'desc_tip'    => true,
	),
	'source_key' => array(
		'title'       => __( 'API key', 'payment-gateway-accept-blue-for-woocommerce' ),
		'type'        => 'text',
		'description' => __( 'API key for Accept.blue Payment API. To get the key follow next route in your Accept.blue account: control panel/sources/press "edit" for type "API" source.', 'payment-gateway-accept-blue-for-woocommerce' ),
		'default'     => '',
		'desc_tip'    => true,
	),
	'pin_code' => array(
		'title'       => __( 'Pin code', 'payment-gateway-accept-blue-for-woocommerce' ),
		'type'        => 'text',
		'description' => __( 'Pin code key for Accept.blue Payment API. To get the key follow next route in your Accept.blue account: control panel/sources/press "edit" for type "API" source.', 'payment-gateway-accept-blue-for-woocommerce' ),
		'default'     => '',
		'desc_tip'    => true,
	),
	'sandbox_public_key' => array(
		'title'       => __( 'Sandbox tokenization key', 'payment-gateway-accept-blue-for-woocommerce' ),
		'type'        => 'text',
		'description' => __( 'Tokenization key for Accept.blue Payment API. To get the key follow next route in your Accept.blue account: control panel/sources/press "edit" for type "Tokenization" source.', 'payment-gateway-accept-blue-for-woocommerce' ),
		'default'     => '',
		'desc_tip'    => true,
	),
	'sandbox_source_key' => array(
		'title'       => __( 'Sandbox API key', 'payment-gateway-accept-blue-for-woocommerce' ),
		'type'        => 'text',
		'description' => __( 'API key for Accept.blue Payment API. To get the key follow next route in your Accept.blue account: control panel/sources/press "edit" for type "API" source.', 'payment-gateway-accept-blue-for-woocommerce' ),
		'default'     => '',
		'desc_tip'    => true,
	),
	'sandbox_pin_code' => array(
		'title'       => __( 'Sandbox pin code', 'payment-gateway-accept-blue-for-woocommerce' ),
		'type'        => 'text',
		'description' => __( 'Pin code key for Accept.blue Payment API. To get the key follow next route in your Accept.blue account: control panel/sources/press "edit" for type "API" source.', 'payment-gateway-accept-blue-for-woocommerce' ),
		'default'     => '',
		'desc_tip'    => true,
	),
//	'charge_prior_authorized_transactions' => array(
//		'title' => __('Enabled/Disabled', 'payment-gateway-accept-blue-for-woocommerce'),
//		'label'       => __( 'Charge transactions immediately', 'payment-gateway-accept-blue-for-woocommerce' ),
//		'type'        => 'checkbox',
//		'description' => '',
//		'default'     => 'no',
//	),
	'logging' => array(
	'title' => __('Enabled/Disabled', 'payment-gateway-accept-blue-for-woocommerce'),
	'label'       => __( 'Logging', 'payment-gateway-accept-blue-for-woocommerce' ),
	'type'        => 'checkbox',
	'description' => '',
	'default'     => 'no',
)
));