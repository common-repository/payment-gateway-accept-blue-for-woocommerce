<?php

class WC_Acceptblue_Gateway_ACH extends WC_Payment_Gateway{

	public $enabled_debug_mode = false;
//	public $charge_prior_authorized_transactions = false;
//	public $force_charges_for_virtual_items = false;
	private $public_key = '';
	private $sandbox_public_key = '';
	private $source_key = '';
	private $sandbox_source_key = '';
	private $pin_code = '';
	private $sendbox_pin_code = '';
	private $api;
	private $is_logging;

	public function __construct() {
		global $wc_acceptblue_ach_api;

		$this->api = $wc_acceptblue_ach_api;
		$this->id                   = 'acceptblue-ach';
		$this->method_title         = __( 'Accept.blue ACH/Check', 'payment-gateway-accept-blue-for-woocommerce' );
		$this->method_description   = sprintf( __( 'Take payments via ACH/Check with Accept.blue', 'payment-gateway-accept-blue-for-woocommerce' ), 'https://accept.blue');
		$this->has_fields           = true;

		$this->supports = array(
            'products',
			'refunds'
		);

		$this->init_form_fields();

		$this->init_settings();

		$this->title                                 = $this->get_option( 'title' );
		$this->description                           = $this->get_option( 'description' );
		$this->enabled                               = $this->get_option( 'enabled', 'yes' );
		$this->enabled_debug_mode                    = 'yes' === $this->get_option( 'enabled_debug_mode', 'no');
		$this->public_key                            = $this->get_option( 'public_key' );
		$this->sandbox_public_key                    = $this->get_option( 'sandbox_public_key' );
		$this->source_key                            = $this->get_option( 'source_key' );
		$this->sandbox_source_key                    = $this->get_option( 'sandbox_source_key' );
		$this->pin_code                              = $this->get_option( 'pin_code' );
		$this->sendbox_pin_code                      = $this->get_option( 'sandbox_pin_code' );

		$this->is_logging = 'yes' === $this->get_option( 'logging', 'no');

		$this->api->enable_debug_mode( $this->enabled_debug_mode );

		if($this->enabled_debug_mode) {
			$this->api->set_source_key( $this->sandbox_source_key );
			$this->api->set_pin_code( $this->sendbox_pin_code );
		}else{
			$this->api->set_source_key( $this->source_key );
			$this->api->set_pin_code( $this->pin_code );
		}

		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
		do_action('acceptblue_after_ach_gateway_init');
	}

	public function init_form_fields() {
		$this->form_fields = include "settings-acceptblue-ach.php";
	}

	public function payment_fields() {
		include 'ach-fields-acceptblue.php';
	}

	public function validate_fields() {
		if(!pgabfw_routing_number_is_valid($_POST['routing-number'])){
			wc_add_notice(  __('Invalid routing number!', 'payment-gateway-accept-blue-for-woocommerce'), 'error' );
			return false;
		}

		if(!pgabfw_account_number_is_valid((int)$_POST['account-number'])){
			wc_add_notice(  __('Invalid account number!', 'payment-gateway-accept-blue-for-woocommerce'), 'error' );
			return false;
		}

		return true;
	}

	public function process_payment( $order_id ) {
		$order = wc_get_order( $order_id );
		try {
            wc_clear_notices();
			$post_data = array(
				'routing-number' => sanitize_text_field( $_POST['routing-number'] ),
				'account-number' => (int)sanitize_text_field( $_POST['account-number'] )
			);

			$post_data = pgabfw_get_billing_shipping_info( $post_data, $order );

			$post_data['total_price'] = floatval(WC()->cart->total);

			$response = $this->authorize_check_request( $order, $post_data );

			if ( is_wp_error( $response ) ) {
				$order->update_status( 'wc-failed' );
				$order->add_order_note( $response->get_error_message() );
				pgabfw_log('error', $this->id, $response->get_error_message() . '.');
				throw new Exception( 'The transaction was declined. ' . $response->get_error_message() );
			}

			$order->set_transaction_id($response['reference_number']);

			$this->process_response( $response, $order );

			WC()->cart->empty_cart();

			return array(
				'result'   => 'success',
				'redirect' => $this->get_return_url( $order ),
			);

		}catch(Exception $e){
			pgabfw_log('error', $this->id, $e->getMessage() . '.');
            $message = $e->getMessage();
            if (defined('REST_REQUEST') && REST_REQUEST) {
                header('Content-Type: application/json; charset=utf-8', true, 409);
                $error = array(
                    'message' => $message,
                    'status' => false
                );

                echo json_encode($error);
                die();
            }else {
                wc_add_notice( $message, 'error' );
                return array(
                    'processingResponse' => array(
                        'message' => $message
                    ),
                    'result' => 'fail',
                    'redirect' => '',
                );
            }
		}
	}

	public function authorize_check_request($order, $post_data){
		$request_path = '/transactions/charge';

		$method = 'POST';
    
    	$total_tax = 0;
    	foreach ( WC()->cart->get_tax_totals() as $code => $tax ) :
			$total_tax += $tax->amount;
		endforeach;    

		$args = array(
			'name' => $post_data['billing_info']['first_name'] . ' ' . $post_data['billing_info']['last_name'],
			'ignore_duplicates' => true,
			'customer' => (object) array(
				'email' => $post_data['billing_info']['email'] ?? '',
			)
		);

		$args['amount'] = $post_data['total_price'];
		$args['amount_details']  = (object) array(
			'tax' => $total_tax,
		);    
		$args['routing_number'] = $post_data['routing-number'];
		$args['account_number'] = (string)$post_data['account-number'];

		$args['billing_info'] = (object) $post_data['billing_info'];
		$args['shipping_info'] = (object) $post_data['shipping_info'];

		$args['transaction_details'] = (object) array(
			'order_number' => "#" . version_compare( WC_VERSION, '3.0.0', '<' ) ? (string)$order->id : (string)$order->get_id()
		);

		return $this->api->request($request_path, $method, $args);
	}

	private function process_response( $response, $order ) {
		$order_id = version_compare( WC_VERSION, '3.0.0', '<' ) ? $order->id : $order->get_id();

		update_post_meta( $order_id, '_acceptblue_xrefnum', $response['reference_number'] );

		update_post_meta( $order_id, '_transaction_id', $response['reference_number'], true );

		if ( $order->has_status( array( 'pending', 'failed' ) ) ) {
			version_compare( WC_VERSION, '3.0.0', '<' ) ? $order->reduce_order_stock() : wc_reduce_stock_levels( $order_id );
		}

		$order->update_status( 'on-hold', sprintf( __( 'Accept.blue authorized (ID: %s). Process order to take payment.', 'payment-gateway-accept-blue-for-woocommerce' ), $response['reference_number'] ) );

		return $response;
	}

	public function process_refund($order_id, $amount = null, $reason = '') {
		$order = wc_get_order( $order_id );

		if ( ! $this->can_refund_order( $order ) ) {
			pgabfw_log('error', $this->id, 'Refund failed.' . ' #' . $order->get_order_number());
			return new WP_Error( 'error', __( 'Refund failed.', 'payment-gateway-accept-blue-for-woocommerce' ) );
		}

		if( 0.0 === (float) $amount ){
			pgabfw_log('error', $this->id, 'Refund amount should be more then 0.' . ' #' . $order->get_order_number());
			return new WP_Error( 'error', __( 'Refund amount should be more then 0.', 'payment-gateway-accept-blue-for-woocommerce' ) );
		}

		$ref_num = (int) get_post_meta( $order_id, '_acceptblue_xrefnum', true );

		if($ref_num === 0){
			pgabfw_log('error', $this->id, 'Refund: reference number not found.' . ' #' . $order->get_order_number());
			return new WP_Error('error', __( 'Reference number not found.', 'payment-gateway-accept-blue-for-woocommerce'));
		}

		$single_transaction = $this->api->get_single_transaction($ref_num);

		if(array_key_exists('status_details', $single_transaction)){
			$total = (float) $order->get_total();

			$status = $single_transaction['status_details']['status'];

			$response = false;

			$wrong_status = array('error', 'reserve', 'originated', 'returned', 'cancelled', 'declined', 'voided', 'approved', 'blocked');
			$void_status = array('queued', 'captured', 'pending');

			if($amount < $total && !pgabfw_can_create_refund($single_transaction['created_at'], $status, $type)){
				return new WP_Error('error', __( 'A partial ACH refund is only possible after 5 business days since the transaction was processed.', 'payment-gateway-accept-blue-for-woocommerce'));
			}

			if(!pgabfw_can_create_refund($single_transaction['created_at'], $status, $type)){
				$message = 'You can make a refund until 01:00 of the following day or within 5 business days after the money is credited to your account.';
				pgabfw_log('error', $this->id, $message . ' #' . $order->get_order_number());
				return new WP_Error('error', __( $message, 'payment-gateway-accept-blue-for-woocommerce'));
			}

			if(in_array($status, $void_status) && $type === 'void'){
				$response = $this->api->void( $ref_num );
				if ( array_key_exists( 'error_message', $response ) && ! empty( $response['error_message'] ) ) {
					pgabfw_log('error', $this->id, 'Refund: ' . $response['error_message'] . ' #' . $order->get_order_number());
					return new WP_Error( 'error', __( $response['error_message'], 'payment-gateway-accept-blue-for-woocommerce' ) );
				}
			}

			if($status === 'settled' && $type === 'refund'){
				$response = $this->api->refund( $ref_num, (float)$amount);
				if ( array_key_exists( 'error_message', $response ) && ! empty( $response['error_message'] ) ) {
					pgabfw_log('error', $this->id, 'Refund: ' . $response['error_message'] . ' #' . $order->get_order_number());
					return new WP_Error( 'error', __( $response['error_message'], 'payment-gateway-accept-blue-for-woocommerce' ) );
				}
			}

			if($response === false && in_array($status, $wrong_status)){
				pgabfw_log('error', $this->id, 'Refund: ' . sprintf('Transaction has wrong status "%s".', $status) . ' #' . $order->get_order_number());
				return new WP_Error('error', __( sprintf('Transaction has wrong status "%s".', $status), 'payment-gateway-accept-blue-for-woocommerce'));
			}

			if($response !== false && $response['status_code'] === 'A'){
				return true;
			}else{
				pgabfw_log('error', $this->id, 'Refund: Error! Something wrong.' . ' #' . $order->get_order_number());
				return new WP_Error('error', __( 'Error! Something wrong.', 'payment-gateway-accept-blue-for-woocommerce'));
			}
		}else{
			pgabfw_log('error', $this->id, 'Refund: Transaction not found.' . ' #' . $order->get_order_number());
			return new WP_Error('error', __( 'Transaction not found.', 'payment-gateway-accept-blue-for-woocommerce'));
		}
	}

}