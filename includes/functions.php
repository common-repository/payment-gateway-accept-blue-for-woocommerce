<?php
function enqueue_pgabfw_common_scripts (){
	if(is_checkout()) {
    wp_enqueue_script('pgabfw-credit-card-mask-lib', PGABFW_LIBS_FRONTEND . 'jquery.mask.min.js', array('jquery'), '1.0.0', true);
		//wp_enqueue_script( 'pgabfw-credit-card-mask-lib', PGABFW_LIBS_FRONTEND . 'jquery.maskedinput.min.js', array( 'jquery' ), '1.0.0', true );
		wp_enqueue_script( 'acceptblue-credit-card-type-lib', PGABFW_LIBS_FRONTEND . 'cleave.min.js', array( 'jquery' ), '1.0.0', true );
		wp_localize_script( 'acceptblue-credit-card-type-lib', 'acceptblue_logos', array(
			'visa' => PGABFW_IMAGES_FRONTEND . 'VISA.svg',
			'mastercard' => PGABFW_IMAGES_FRONTEND . 'MasterCard.svg',
			'amex' => PGABFW_IMAGES_FRONTEND . 'american-express.svg',
			'discover' => PGABFW_IMAGES_FRONTEND . 'discover.svg',
			'jcb' => PGABFW_IMAGES_FRONTEND . 'jcb.svg',
			'diners' => PGABFW_IMAGES_FRONTEND . 'diners-club.svg',
		) );
	}
}

function enqueue_pgabfw_common_styles(){
	if(is_checkout()) {
		wp_enqueue_style( 'acceptblue-card-check-styles', PGABFW_STYLES_FRONTEND . 'pgabfw_styles.css', array(), '1.0.0' );
	}
}

function enqueue_pgabfw_credit_card_scripts (){
	wp_enqueue_script('acceptblue-credit-card-mask', PGABFW_SCRIPTS_FRONTEND . 'credit-card-form.js', array('jquery', 'pgabfw-credit-card-mask-lib'), '1.0.0', true);
	wp_localize_script('acceptblue-credit-card-mask', 'pgabfwInfo', array(
		'customerIsLoggedIn' => is_user_logged_in()? 'true': 'false'
	));
}

function enqueue_pgabfw_check_scripts (){
	wp_enqueue_script('acceptblue-check-mask', PGABFW_SCRIPTS_FRONTEND . 'check-form.js', array('jquery', 'pgabfw-credit-card-mask-lib'), '1.0.0', true);
}

function enqueue_pgabfw_admin_scripts (){
	$screen = get_current_screen();
	if($screen && $screen->id === "woocommerce_page_wc-settings" && !empty($_GET['section'])) {
		if($_GET['section'] === 'acceptblue-cc' || $_GET['section'] === 'acceptblue-ach') {
			wp_enqueue_script( 'acceptblue-settings-script', PGABFW_SCRIPTS_BACKEND . 'settings.js', array( 'jquery' ), '1.0.0', true );
		}
	}

    if($screen && $screen->id === 'page' && isset($_GET['post']) && isset($_GET['action']) && $_GET['action'] === 'edit'){
        wp_enqueue_style('acceptblue-wc-blocks-style', PGABFW_STYLES_FRONTEND . 'pgabfw_styles.css', array(), '1.0.0');
    }

//    var_dump(PGABFW_BLOCKS . '/payment_card_block.js');
//    wp_enqueue_script( 'acceptblue-gutenberg-block', PGABFW_BLOCKS . '/payment_card_block.js', array(), '1.0.0', true );
}

function pgabfw_get_payment_method_from_order($order){
	if( version_compare( WC_VERSION, '3.0.0', '<' )) {
		return $order->payment_method;
	}else{
		return $order->get_payment_method();
	}
}

function pgabfw_charge_payment( $order_id ) {
	global $wc_acceptblue_logger;
	$cc_options = WC()->payment_gateways->payment_gateways()['acceptblue-cc'];
	$order = wc_get_order( $order_id );
	$payment_id = pgabfw_get_payment_method_from_order($order);
	if ( 'acceptblue-cc' === $payment_id ) {
		$my_xrefnum   = get_post_meta( $order_id, '_acceptblue_xrefnum', true );

		$charged = get_post_meta( $order_id, '_acceptblue_transaction_charged', true );

		if ( $my_xrefnum && 'no' === $charged ) {
			if($cc_options->settings['charge_order'] === 'yes') {
				$result = WC_Acceptblue_Gateway_Credit_Card::charge_card_request( $my_xrefnum );
				if ( is_wp_error( $result ) ) {
					$order->add_order_note( __( 'Unable to charge transaction!', 'payment-gateway-accept-blue-for-woocommerce' ) . ' ' . $result->get_error_message() );
					pgabfw_log( 'error', $payment_id, 'Unable to charge transaction!' . ' #' . $order->get_order_number() );
				} else {
					$order->add_order_note( __( 'Accept.blue transaction charged', 'payment-gateway-accept-blue-for-woocommerce' ) );
					pgabfw_log( 'info', $payment_id, 'Transaction charged! ' . ' #' . $order->get_order_number() );
					update_post_meta( $order_id, '_acceptblue_transaction_charged', 'yes' );

					// Store other data such as fees
					update_post_meta( $order_id, 'Accept.blue Payment ID', $result['reference_number'] );
					update_post_meta( $order_id, '_transaction_id', $result['reference_number'] );
					$order->payment_complete( $result['reference_number'] );
				}
			}
		}
	}
}

function pgabfw_get_billing_shipping_info($post_data, $order){
	/**
	 * @var $order WC_Order
	 */
	$post_data['billing_info'] = array();
	$post_data['shipping_info'] = array();

	$post_data['billing_info']['first_name'] = version_compare( WC_VERSION, '3.0.0', '<' ) ? $order->billing_first_name : $order->get_billing_first_name();
	$post_data['billing_info']['last_name']  = version_compare( WC_VERSION, '3.0.0', '<' ) ? $order->billing_last_name : $order->get_billing_last_name();
	$post_data['billing_info']['street'] = version_compare( WC_VERSION, '3.0.0', '<' ) ? $order->billing_address_1 : $order->get_billing_address_1();
	$post_data['billing_info']['street2'] = version_compare( WC_VERSION, '3.0.0', '<' ) ? $order->billing_address_2 : $order->get_billing_address_2();
	$post_data['billing_info']['city'] = version_compare( WC_VERSION, '3.0.0', '<' ) ? $order->billing_city : $order->get_billing_city();
	$post_data['billing_info']['state'] = version_compare( WC_VERSION, '3.0.0', '<' ) ? $order->billing_state : $order->get_billing_state();
	$post_data['billing_info']['zip'] = version_compare( WC_VERSION, '3.0.0', '<' ) ? $order->billing_postcode : $order->get_billing_postcode();
	$post_data['billing_info']['country'] = version_compare( WC_VERSION, '3.0.0', '<' ) ? $order->billing_country : $order->get_billing_country();
	$post_data['billing_info']['phone'] = version_compare( WC_VERSION, '3.0.0', '<' ) ? $order->billing_phone : $order->get_billing_phone();
	$post_data['billing_info']['email'] = version_compare( WC_VERSION, '3.0.0', '<' ) ? $order->billing_email : $order->get_billing_email();

	$post_data['shipping_info']['first_name'] = version_compare( WC_VERSION, '3.0.0', '<' ) ? $order->shipping_first_name : $order->get_shipping_first_name();
	$post_data['shipping_info']['last_name'] = version_compare( WC_VERSION, '3.0.0', '<' ) ? $order->shipping_last_name : $order->get_shipping_last_name();
	$post_data['shipping_info']['street'] = version_compare( WC_VERSION, '3.0.0', '<' ) ? $order->shipping_address_1 : $order->get_shipping_address_1();
	$post_data['shipping_info']['street2'] = version_compare( WC_VERSION, '3.0.0', '<' ) ? $order->shipping_address_2 : $order->get_shipping_address_2();
	$post_data['shipping_info']['city'] = version_compare( WC_VERSION, '3.0.0', '<' ) ? $order->shipping_city : $order->get_shipping_city();
	$post_data['shipping_info']['state'] = version_compare( WC_VERSION, '3.0.0', '<' ) ? $order->shipping_state : $order->get_shipping_state();
	$post_data['shipping_info']['zip'] = version_compare( WC_VERSION, '3.0.0', '<' ) ? $order->shipping_postcode : $order->get_shipping_postcode();
	$post_data['shipping_info']['country'] = version_compare( WC_VERSION, '3.0.0', '<' ) ? $order->shipping_country : $order->get_shipping_country();
	return $post_data;
}

function pgabfw_get_orders_for_cron(){
	$args = array(
		'payment_method' => 'acceptblue-ach',
		'post_status' => 'wc-on-hold',
		'numberposts' => 100,
		'order' => 'ASC',
	);

	return wc_get_orders($args);
}

function pgabfw_date_from_w3c_to_utc($date){
	return gmdate("Y-m-d\TH:i:s\Z", strtotime($date));
}

/**
 * @param $api WC_Acceptblue_API
 * @param bool $return
 *
 * @return WC_Acceptblue_API
 */
function pgabfw_set_api_options( WC_Acceptblue_API $api, bool $return = false){
	$ach_options = WC()->payment_gateways->payment_gateways()['acceptblue-ach'];
	$is_debug_mode = 'yes' === $ach_options->settings['enabled_debug_mode'];
	$source_key = false;

	if($is_debug_mode){
		$source_key = $ach_options->settings['sandbox_source_key'];
		$pin_code = $ach_options->settings['sandbox_pin_code'];
	}else{
		$source_key = $ach_options->settings['source_key'];
		$pin_code = $ach_options->settings['pin_code'];
	}

	$api->enable_debug_mode($is_debug_mode);
	$api->set_source_key($source_key);
	$api->set_pin_code($pin_code);
	if($return){
		return $api;
	}
}

function pgabfw_map_orders_and_dates($orders, &$orders_map, &$order_dates_map){
	$orders_map = array();
	foreach ($orders as $order) {
		$order_dates_map[] = $order->get_date_created();
		$my_xrefnum  = get_post_meta( $order->get_id(), '_acceptblue_xrefnum', true );
		$orders_map[$my_xrefnum] = $order;
	}
}

function pgabfw_get_cron_date_range($dates_map){
	if(!is_array($dates_map) || count($dates_map) == 0){
		return array();
	}
	usort($dates_map, function($a, $b) {
		$dateTimestamp1 = strtotime($a);
		$dateTimestamp2 = strtotime($b);

		return $dateTimestamp1 < $dateTimestamp2 ? -1: 1;
	});

	$min_date = pgabfw_date_from_w3c_to_utc($dates_map[0]->__toString());
	$max_date = pgabfw_date_from_w3c_to_utc($dates_map[count($dates_map) - 1]->__toString());
	return array($min_date, $max_date);
}

$pgabfw_cron_offset = 0;
$pgabfw_cron_limit  = 100;
$pgabfw_cron_page   = 1;
$pgabfw_cron_max_pages = 10;

function pgabfw_cron_check_transactions($min_date, $max_date, $orders_map){
	global $wc_acceptblue_ach_api;
	global $pgabfw_cron_limit;
	global $pgabfw_cron_offset;
	global $pgabfw_cron_page;
	global $pgabfw_cron_max_pages;

	$ach_options = WC()->payment_gateways->payment_gateways()['acceptblue-ach'];

	$is_debug_mode = 'yes' === $ach_options->settings['enabled_debug_mode'];
	$source_key = false;

	if($is_debug_mode){
		$source_key = $ach_options->settings['sandbox_source_key'];
		$pin_code = $ach_options->settings['sandbox_pin_code'];
	}else{
		$source_key = $ach_options->settings['source_key'];
		$pin_code = $ach_options->settings['pin_code'];
	}

	if(empty($source_key)) exit;

	$wc_acceptblue_ach_api->enable_debug_mode($is_debug_mode);
	$wc_acceptblue_ach_api->set_source_key($source_key);
	$wc_acceptblue_ach_api->set_pin_code($pin_code);

	$transactions = $wc_acceptblue_ach_api->get_check_transactions($min_date, $max_date, $pgabfw_cron_limit, $pgabfw_cron_offset);

	$checked = array();
	foreach ($transactions as $transaction){
		$status = $transaction['status_details']['status'];
		$ref_num = $transaction['id'];
		if(array_key_exists($ref_num, $orders_map)){
			if($status === 'settled'){
				$orders_map[$ref_num]->update_status('processing');
				update_post_meta( $orders_map[$ref_num]->get_id(), '_transaction_id', $ref_num, true );
				$message = sprintf( __( 'Accept.blue transaction charged (charge RefNum: %s)', 'payment-gateway-accept-blue-for-woocommerce' ), $ref_num );
				$orders_map[$ref_num]->add_order_note( $message );
				$checked[$ref_num] = '';
				pgabfw_log('info', 'acceptblue-ach', 'cron order complete - #' . $orders_map[$ref_num]->get_order_number());
			}
			if($status === 'error'){
				$orders_map[$ref_num]->update_status('failed', sprintf( __( 'Error: %s', 'payment-gateway-accept-blue-for-woocommerce' ), $transaction['status_details']['error_message']));
				$checked[$ref_num] = '';
				pgabfw_log('info', 'acceptblue-ach', 'cron order error - #' . $orders_map[$ref_num]->get_order_number() . '. Error message: ' . $transaction['status_details']['error_message']);
			}
		}
	}

	$stayed = array_diff_key($orders_map, $checked);
	if(count($stayed) > 0){
		$pgabfw_cron_offset = $pgabfw_cron_limit * $pgabfw_cron_page;
		$pgabfw_cron_page   += 1;
		if( $pgabfw_cron_page > $pgabfw_cron_max_pages) {
			return false;
		}else {
			pgabfw_cron_check_transactions( $min_date, $max_date, $orders_map );
		}
	}
}

function pgabfw_check_ach_orders_status(){
	$orders = pgabfw_get_orders_for_cron();
	$orders_map = array();
	$order_dates_map = array();

	if(!is_array($orders) || count($orders) === 0) {
		pgabfw_abps_bq_log('cron ach orders empty');
		return;
	}

	pgabfw_map_orders_and_dates($orders, $orders_map, $order_dates_map);

	$min_max_dates = pgabfw_get_cron_date_range($order_dates_map);

	if(count($min_max_dates) > 0) {
		$min_date = $min_max_dates[0];
		$max_date = $min_max_dates[1];
	}else{
		pgabfw_abps_bq_log('cron min max dates empty');
		return;
	}

	if(is_array($orders_map) && count($orders_map) > 0) {
		pgabfw_cron_check_transactions( $min_date, $max_date, $orders_map );
	}else{
		pgabfw_abps_bq_log('cron orders map empty');
	}
}

function pgabfw_abps_bq_log($message){
	$log = date('Y-m-d H:i:s ') . $message;
	file_put_contents(PGABFW_LOG_PATH . 'log.txt', $log . PHP_EOL, FILE_APPEND);
}

function pgabfw_credit_card_is_valid($card){
	$pattern = '/^[\d]{4}\s?[\d]{4}\s?[\d]{4}\s?[\d]{3,4}\s?$/i';
	$pattern_amex = '/^[\d]{4}\s?[\d]{6}\s?[\d]{4,5}\s?$/i';
	$pattern_dc = '/^[\d]{4}\s?[\d]{4}\s?[\d]{4}\s?[\d]{2,4}\s?$/i';
	$pattern_dc2 = '/^[\d]{4}\s?[\d]{4}\s?[\d]{4}\s?[\d]{4}\s?[\d]{1,3}\s?$/i';
	$pattern_diners = '/^[\d]{4}\s?[\d]{6}\s?[\d]{4}\s?[\d]{1,5}\s?$/i';
  
  $result = preg_match($pattern, $card);
  
  if ( $result == 0 ) {
    $result = preg_match($pattern_amex, $card);
    
    if ( $result == 0 ) {
      $result = preg_match($pattern_dc, $card);
    }
    
    if ( $result == 0 ) {
      $result = preg_match($pattern_dc2, $card);
    }
    
    if ( $result == 0 ) {
      $result = preg_match($pattern_diners, $card);
    }    
  }
  
	return $result;
}

function pgabfw_card_expire_is_valid($expire){
	$pattern = '/^[\d]{2}\/[\d]+$/i';
	$regex_status = preg_match($pattern, $expire);
	if($regex_status){
		$date_arr = explode('/', $expire);
		$month = (int) trim($date_arr[0]);
		if($month > 0 && $month <= 12){
			return true;
		}else{
			return false;
		}
	}else{
		return false;
	}
}

function pgabfw_card_cvv_is_valid($cvv, $card){
  if ( substr($card, 0, 2) == 34 || substr($card, 0, 2) == 37  ) {
	 $pattern = '/^[\d]{4}$/i';
  } else {
	 $pattern = '/^[\d]{3}$/i';  
  }
	return preg_match($pattern, $cvv);
}

function pgabfw_routing_number_is_valid($routing_number){
	$pattern = '/^[\d]{9}$/i';
	return preg_match($pattern, $routing_number);
}

function pgabfw_account_number_is_valid($account_number){
	$pattern = '/^[\d]+$/i';
	return preg_match($pattern, $account_number);
}

function pgabfw_contain_virtual_products(){
	$cart = WC()->cart->get_cart();
	$contains = false;

	if(empty($cart) || is_array($cart) && count($cart) === 0){
		return $contains;
	}

	foreach ($cart as $key => $item){
		$product = $item['data'];
		if($product->is_virtual('yes') || $product->is_downloadable('yes')){
			$contains = true;
			break;
		}
	}

	return $contains;
}

function remove_pgabfw_acceptblue_ach($available_gateways) {
	if(!is_admin() && is_checkout()) {
		if(pgabfw_contain_virtual_products() && isset($available_gateways['acceptblue-ach'])){
			unset($available_gateways['acceptblue-ach']);
		}
	}

	return $available_gateways;
}

function pgabfw_can_send_void_request($transaction_date){
	try {
		$t_date = new DateTime($transaction_date);
		$only_date = $t_date->format('Y-m-d');
		$timestamp = strtotime('+1 day', strtotime($only_date));
		$date = new DateTime();
		$date->setTimestamp(strtotime('+1 hour', $timestamp));
		$void_expire = $date->getTimestamp();

		$current_date = new DateTime('America/New_York');
		return $void_expire > $current_date->getTimestamp();
	}catch(Exception $e){
		return false;
	}
}

function pgabfw_can_send_refund_request($transaction_date){
	try {
		$date = new DateTime();
		$date = $date->setTimestamp(strtotime('+5 weekdays', strtotime($transaction_date)));
		$refund_start = $date->getTimestamp();

		$current_date = new DateTime('America/New_York');
		return  $refund_start < $current_date->getTimestamp();
	}catch (Exception $e){
		return false;
	}
}

function pgabfw_can_create_refund($transaction_date, $status,  &$type){
	$statuses = array();
	$statuses[] = pgabfw_can_send_void_request($transaction_date);
	$statuses[] = pgabfw_can_send_refund_request( $transaction_date );

	if($statuses[0] === true) { $type = 'void'; }
	if($statuses[1] === true) { $type = 'refund'; }

	return in_array( true, $statuses );
}

function pgabfw_is_logging($id){
	$options = WC()->payment_gateways->payment_gateways()[$id];
	return 'yes' === $options->settings['logging'];
}

/**
 * @param $type
 * @param $payment_id
 * @param $message
 */
function pgabfw_log($type, $payment_id, $message){
	global $wc_acceptblue_logger;
	$message_template = '"Accept.blue" - %s';
	if(pgabfw_is_logging($payment_id)){
		$wc_acceptblue_logger->log($type, sprintf($message_template, $message));
	}
}

function pgabfw_is_enabled(){
	$payments_ids = array('acceptblue-cc', 'acceptblue-ach');
	$results = array();
	foreach ($payments_ids as $id){
		$options = WC()->payment_gateways->payment_gateways()[$id];
		$results[] = 'yes' === $options->settings['enabled'];
	}

	return in_array(true, $results);
}

function pgabfw_acceptblue_ssl_admin_notices() {
	if(!pgabfw_is_enabled()){ return false; }

	if ( ( function_exists( 'wc_site_is_https' ) && ! wc_site_is_https() ) && ( 'no' === get_option( 'woocommerce_force_ssl_checkout' ) && ! class_exists( 'WordPressHTTPS' ) ) ) {
		$message = '<div class="error acceptblue-ssl-message"><p>' . sprintf( __( 'Accept.blue payment is enabled, but the <a href="%1$s">force SSL option</a> is disabled; your checkout may not be secure! Please enable SSL and ensure your server has a valid <a href="%2$s" target="_blank">SSL certificate</a>', 'payment-gateway-accept-blue-for-woocommerce' ), admin_url( 'admin.php?page=wc-settings&tab=advanced#advanced_page_options-description' ), 'https://en.wikipedia.org/wiki/Transport_Layer_Security' ) . '</p></div>';
    $allowed_html = array(
      'div' => array(
        'class' => array(),
      ),
      'p' => array(),
      'a' => array(
        'href' => array(),
        'target' => array(),
      ),
    );
		echo wp_kses( $message, $allowed_html );
	}
}

function pgabfw_acceptblue_woocommerce_is_not_active_notice(){
	$message = '<div class="error acceptblue-ssl-message"><p>' . __( 'Woocommerce is disabled! Please activate the Woocommerce plugin, then the Accept.blue payment plugin will work.' ) . '</p></div>';
  $allowed_html = array(
    'div' => array(
      'class' => array(),
    ),
    'p' => array(),
  );
  echo wp_kses( $message, $allowed_html );
}

function pgabfw_is_free_trial_subscription($order_id, $post_data){
	return (
		function_exists('wcs_order_contains_subscription') &&
		wcs_order_contains_subscription($order_id) &&
		$post_data['total_price'] === 0.0
	);
}

function pgabfw_is_subscription($order_id){
	if(!isset($_GET['change_payment_method'])){
	$post_id = intval($_GET['change_payment_method']);
	$post = get_post($post_id);
	return ($post_id > 0 && $post->get_type() === 'shop_subscription');
	}else{
		return false;
	}
}

function pgabfw_allow_to_show_credit_card_form(){
	$allow = true;
//	if(is_wc_endpoint_url('add-payment-method')){
//		$allow = true;
//	}

//	$is_subscription = false;
//
//	if(is_checkout() && isset($_GET['change_payment_method'])){
//		$post_id = intval($_GET['change_payment_method']);
//		$is_subscription = wcs_is_subscription($post_id);
//	}
//
//	if($is_subscription){
//		$allow = false;
//	}

	return $allow;
}

function pgabfw_is_add_payment_method_page() {
	return is_wc_endpoint_url( 'add-payment-method' );
}

function pgabfw_is_subscription_add_payment_method_page(){
	$subscription_add_payment_method_page = false;

	$is_subscription = false;

	if(is_checkout() && isset($_GET['change_payment_method'])){
		$post_id = intval($_GET['change_payment_method']);
		$is_subscription = wcs_is_subscription($post_id);
	}

	if(preg_match('/^\/checkout\/order-pay\/\d+\//', $_SERVER['REQUEST_URI']) && isset($_GET['change_payment_method'])){
		$post_id = intval($_GET['change_payment_method']);
		$is_subscription = wcs_is_subscription($post_id);
	}

	if($is_subscription){
		$subscription_add_payment_method_page = true;
	}

	return $subscription_add_payment_method_page;
}

function pgabfw_get_order_tax_totals($order){
	try {
		$total = 0;
		$taxes = $order->get_items( 'tax' );
		foreach ( $taxes as $tax ) {
			$total += $tax->get_tax_total();
		}

		return $total;
	}catch (Exception $e){
		return 0;
	}
}

function pgabfw_get_order_number($order){
	global $wpdb;
	$sql = $wpdb->prepare(
		"SELECT wpp.meta_value AS 'order_number' FROM {$wpdb->prefix}postmeta AS wpp WHERE wpp.post_id = %s AND wpp.meta_key = '_order_number_formatted'",
		$order->get_id()
	);
	$order_number = $wpdb->get_var($sql);
	return ($order_number !== null)? $order_number : $order->get_id();
}

function pgabfw_get_order_id($order){
	if (
		is_plugin_active( 'woocommerce-sequential-order-numbers-pro/woocommerce-sequential-order-numbers-pro.php' ) ||
		is_plugin_active( 'woocommerce-sequential-order-numbers/woocommerce-sequential-order-numbers.php' ) )
	{
		return '#' . pgabfw_get_order_number($order);
	}else{
		return '#' . version_compare( WC_VERSION, '3.0.0', '<' ) ? (string)$order->id : (string)$order->get_id();
	}
}

function pgabfw_order_has_subscriptions($order_id){
	$has_subscriptions = false;
	if(function_exists('wcs_order_contains_subscription')){
		$has_subscriptions = wcs_order_contains_subscription($order_id);
	}
	return $has_subscriptions;
}
function pgabfw_create_customer_if_not_exists($order, &$error = null){
	$customer_id = pgabfw_get_customer_by_email( $order->get_billing_email() );
	if ( $customer_id !== false ) {
		return $customer_id;
	}else {
		try {
			$customer_id = pgabfw_create_customer( $order );
		} catch ( Exception $e ) {
			$error = $e;
			return false;
		}
	}

	return false;
}

function pgabfw_get_customer_by_email($email){
	$customer_id = email_exists($email);
	if($customer_id !== false) {
		return $customer_id;
	}else{
		return false;
	}
}

/**
 * @throws Exception
 */
function pgabfw_create_customer( $order ) {
	$customer_id = false;
	$customer = new WC_Customer();
	$customer_data = array(
		'first_name' => $order->get_billing_first_name(),
		'last_name'  => $order->get_billing_last_name(),
		'email'      => $order->get_billing_email(),
		'username'   => $order->get_billing_email(),
		'password'   => wp_generate_password(),
	);
	if ( ! empty( $customer_data['email'] ) ) {
		$customer->set_props( $customer_data );
		$customer_id = $customer->save();
	}

	return $customer_id;
}

function pgabfw_get_subscriptions_from_order($order_id){
	$subscriptions = array();
	if(function_exists('wcs_get_subscriptions_for_order')){;
		$subscriptions = wcs_get_subscriptions_for_order($order_id);
	}
	return $subscriptions;
}

function pgabfw_maybe_need_change_subscription_after_refund($order_id){
	$order = wc_get_order($order_id);
	if(function_exists('wcs_order_contains_subscription') && wcs_order_contains_subscription($order)){
		$subscriptions = wcs_get_subscriptions_for_order( $order_id, array( 'order_type' => 'any' ) );
		$refund_items = $order->get_refunds();
		$refunds_products_ids = [];
		$refund_qty = [];
		foreach ($refund_items as $refund){
			$items = $refund->get_items();
			foreach ($items as $item){
				$refund_qty[$item->get_product_id()] = $item->get_quantity();
				$refunds_products_ids[] = $item->get_product_id();
			}
		}

		$subscriptions_products_ids = [];
		$subscriptions_products_indexes = [];
		$subscriptions_ids = [];

		foreach ($subscriptions as $subscription){
			$items = $subscription->get_items();
			foreach ($items as $index => $item){
				$subscriptions_ids[wcs_get_canonical_product_id( $item )] = $subscription->get_id();
				$subscriptions_products_indexes[wcs_get_canonical_product_id( $item )] = $index;
				$subscriptions_products_ids[] = wcs_get_canonical_product_id( $item );
			}
		}

		$refund_subscriptions_ids = array_intersect($subscriptions_products_ids, $refunds_products_ids);
		if(count($refund_subscriptions_ids) > 0){
			foreach ($refund_subscriptions_ids as $product_id) {
				$product_index = $subscriptions_products_indexes[$product_id];
				$subscription = wcs_get_subscription($subscriptions_ids[$product_id]);
				$subscription_item = $subscription->get_items()[$product_index];
				$subscription_item_qty = $subscription_item->get_quantity();
				$refund_item_qty = $refund_qty[$product_id];
				if($subscription_item_qty === $refund_item_qty) {
					$subscription->set_status( 'wc-cancelled' );
					$subscription->save();
				}else{
					if($subscription_item instanceof WC_Order_Item_Product) {
						$cost = $subscription_item->get_total() / $subscription_item_qty;
						$new_qty = $subscription_item_qty + $refund_item_qty;
						$subscription_item->set_quantity($subscription_item_qty + $refund_item_qty);
						$subscription_item->set_subtotal($cost * $new_qty);
						$subscription_item->set_total($cost * $new_qty);
						$subscription_item->calculate_taxes();
						$subscription_item->save();
						$product = wc_get_product($product_id);
						$subscription->add_order_note('Refunded subscription <a href="/wp-admin/post.php?post=' . $product_id . '&action=edit" target="_blank">' . $product->get_name() . '</a> item in order <a href="/wp-admin/post.php?post=' . $order->get_id() . '&action=edit" target="_blank" >#' . $order->get_id() . '</a> . Old quantity: ' . ($subscription_item_qty) . '. New quantity: ' . $new_qty . ' .');
						if($new_qty <= 0){
							$subscription->set_status( 'wc-cancelled' );
							$subscription->add_order_note('Subscription canceled because it was fully refunded.');
						}
						$subscription->calculate_totals();
						$subscription->save();
					}
				}
			}
		}
	}
}