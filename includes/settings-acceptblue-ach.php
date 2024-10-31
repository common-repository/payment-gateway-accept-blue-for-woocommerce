<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

return apply_filters( 'wc_acceptblue_settings_ach', array(
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
		'default'     => __( 'ACH/Check', 'payment-gateway-accept-blue-for-woocommerce' ),
		'desc_tip'    => true,
	),
	'description' => array(
		'title'       => __( 'Description', 'payment-gateway-accept-blue-for-woocommerce' ),
		'type'        => 'text',
		'description' => __( 'This controls the description which the user sees during checkout.', 'payment-gateway-accept-blue-for-woocommerce' ),
		'default'     => __( 'Pay with your ACH/Check.', 'payment-gateway-accept-blue-for-woocommerce' ),
		'desc_tip'    => true,
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
	'logging' => array(
		'title' => __('Enabled/Disabled', 'payment-gateway-accept-blue-for-woocommerce'),
		'label'       => __( 'Logging', 'payment-gateway-accept-blue-for-woocommerce' ),
		'type'        => 'checkbox',
		'description' => '',
		'default'     => 'no',
	)
));