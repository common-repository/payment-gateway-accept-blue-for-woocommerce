<?php
class WC_Acceptblue_Gateway_Credit_Card extends WC_Payment_Gateway {

	public $enabled_debug_mode = false;
//	public $charge_prior_authorized_transactions = false;
//	public $force_charges_for_virtual_items = false;
	private $public_key = '';
	private $sandbox_public_key = '';
	private $source_key = '';
	private $sandbox_source_key = '';
	private $pin_code = '';
	private $sendbox_pin_code = '';
	/**
	 * @var $api WC_Acceptblue_API
	 */
	private $api;
	private $is_token_request = false;
	private $has_virtual_products = false;
	private $logger;
	private $is_logging;
	private $user_save_card_accept = false;
	private $transaction_type = 'authorize';
	private $charge_virtual = false;
	public $charge_order = true;

	const SOURCE_TYPE = 'tkn-';

	/**
	 * WC_Acceptblue_Gateway constructor.
	 */
	public function __construct() {
		global $wc_acceptblue_card_api;
		global $wc_acceptblue_logger;
		$this->api = $wc_acceptblue_card_api;
		$this->logger = $wc_acceptblue_logger;
		$this->id                   = 'acceptblue-cc';
		$this->method_title         = __( 'Accept.blue Credit Card', 'payment-gateway-accept-blue-for-woocommerce' );
		$this->method_description   = sprintf( __( 'Take payments via credit card with Accept.blue', 'payment-gateway-accept-blue-for-woocommerce' ), 'https://accept.blue');
		$this->has_fields           = true;

		$this->supports = array(
            'products',
			'tokenization',
			'refunds',
			'subscriptions',
			'subscription_cancellation',
			'subscription_suspension',
			'subscription_reactivation',
			'subscription_amount_changes',
			'subscription_date_changes',
			'multiple_subscriptions',
			'subscription_payment_method_change',
			'subscription_payment_method_change_customer',
			'subscription_payment_method_change_admin'
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
//		$this->charge_prior_authorized_transactions = 'yes' === $this->get_option( 'charge_prior_authorized_transactions', 'no');
		$this->transaction_type = $this->get_option('transaction_type', 'authorize');
		$this->charge_virtual = 'yes' === $this->get_option('charge_virtual', 'no');
		$this->charge_order = 'yes' === $this->get_option('charge_order', 'yes');

		$this->is_logging = 'yes' === $this->get_option( 'logging', 'no');

		$this->api->enable_debug_mode( $this->enabled_debug_mode );

		if($this->enabled_debug_mode) {
			$this->api->set_source_key( $this->sandbox_source_key );
			$this->api->set_pin_code( $this->sendbox_pin_code );
		}else{
			$this->api->set_source_key( $this->source_key );
			$this->api->set_pin_code( $this->pin_code );
		}

		if($this->enabled_debug_mode) {
			WC_Acceptblue_Recurring_API::get_instance()->set_api_mode(WC_Acceptblue_Recurring_API::DEV_MODE);
			WC_Acceptblue_Recurring_API::get_instance()->set_authorization_data($this->sandbox_source_key, $this->sendbox_pin_code);
		}else{
			WC_Acceptblue_Recurring_API::get_instance()->set_api_mode(WC_Acceptblue_Recurring_API::PROD_MODE);
			WC_Acceptblue_Recurring_API::get_instance()->set_authorization_data($this->source_key, $this->pin_code);
		}

		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
		add_action('woocommerce_scheduled_subscription_payment_' . $this->id, array($this, 'process_schedule_payment'), 10, 2);
		add_filter('woocommerce_subscription_payment_meta', array($this, 'add_subscription_payment_meta'), 10, 2);
		add_filter('pgabfw_save_subscription_payment_token', array($this, 'save_subscription_payment_token'), 10, 3);
        add_action ('woocommerce_before_order_object_save', array($this, 'update_amount_on_order_update'), 10, 1);
	}

	public function add_subscription_payment_meta($payment_meta, $subscription){
		$payment_meta[ $this->id ] = array(
			'post_meta' => array(
				'_' . $this->id . '_card_id' => array(
					'value' => get_post_meta( $subscription->get_id(), '_' . $this->id . '_card_id', true ),
					'label' => 'AcceptBlue Card ID',
				),
			),
		);

		return $payment_meta;
	}

	public function init_form_fields() {
		$this->form_fields = include "settings-acceptblue-credit-card.php";
	}

	public function payment_fields() {
		if(is_user_logged_in()){
			$this->saved_payment_methods();
		}
		ob_start();
		include 'card-fields-acceptblue.php';
		$template = ob_get_clean();
    $allowed_html = array(
      'div' => array(
        'class' => array(),
        'id' => array(),
      ),
      'br' => array(),
      'label' => array(
        'for' => array(),
      ),
      'input' => array(
        'type' => array(),
        'name' => array(),
        'placeholder' => array(),
        'value' => array(),
        'id' => array(),
        'autocomplete' => array(),
      ),
    );
		echo wp_kses( $template, $allowed_html );
	}

	public function validate_fields() {
		if(!empty($_POST['wc-acceptblue-cc-payment-token']) && $_POST['wc-acceptblue-cc-payment-token'] !== 'new'){
			$token_id = (int)$_POST['wc-acceptblue-cc-payment-token'];
			if($token_id > 0){
				return true;
			}else{
				wc_add_notice(  __('Payment method not found!', 'payment-gateway-accept-blue-for-woocommerce'), 'error' );
				return false;
			}
		}else {
            $card_result = \Freelancehunt\Validators\CreditCard::validCreditCard( $_POST['credit_card'] );
			if ( !$card_result['valid'] ) {
				wc_add_notice( __( 'Invalid credit card number!', 'payment-gateway-accept-blue-for-woocommerce' ), 'error' );

				return false;
			}
			if ( ! pgabfw_card_expire_is_valid( $_POST['date_expire'] ) ) {
				wc_add_notice( __( 'Invalid credit card expire date!', 'payment-gateway-accept-blue-for-woocommerce' ), 'error' );

				return false;
			}
			if ( ! pgabfw_card_cvv_is_valid( $_POST['cvv'], $_POST['credit_card'] ) ) {
				wc_add_notice( __( 'Invalid credit card cvv code!', 'payment-gateway-accept-blue-for-woocommerce' ), 'error' );

				return false;
			}
		}

		return true;
	}

	public function add_payment_method(){
		$user_save_card_accept = $_POST['save_payment_method'] === 'on';
		if ( ! $this->is_token_request && $user_save_card_accept ) {
			try {
				@list( $month, $year ) = explode( '/', $_POST['date_expire'] );
				$response = $this->api->card_verification(
					preg_replace( '/\s+/', '', $_POST['credit_card'] ),
					intval( $month ),
					intval( $year ),
					(string) $_POST['cvv'],
					true
				);
				if ( is_wp_error( $response ) ) {
					pgabfw_log('error', $this->id, 'Error saving payment method!. ' . $response->get_error_messages() . '.');
					wc_add_notice( 'Something went wrong!', 'error' );
					return array(
						'result'   => 'failure',
						'redirect' => wc_get_endpoint_url( 'payment-methods' ),
					);
				} else {
					$this->save_payment_method( $response, $_POST, $token );
					if(pgabfw_is_subscription_add_payment_method_page()){
						$order = wc_get_order($_POST['woocommerce_change_payment']);
						$result = apply_filters('pgabfw_save_subscription_payment_token', $order, $token);
						return $result;
					}
					return array(
						'result'   => 'success',
						'redirect' => wc_get_endpoint_url( 'payment-methods' ),
					);
				}
			} catch ( Exception $e ) {
				pgabfw_log('error', $this->id, 'Error saving payment method!. ' . $e->getMessage() . '.');
				wc_add_notice( 'Something went wrong!', 'error' );

				return array(
					'result'   => 'failure',
					'redirect' => wc_get_endpoint_url( 'payment-methods' ),
				);
			}
		}else{
			if(pgabfw_is_subscription_add_payment_method_page()){
				$order = wc_get_order(intval($_POST['woocommerce_change_payment']));
				$token = WC_Payment_Tokens::get(intval($_POST['wc-acceptblue-cc-payment-token']));
				if($token instanceof WC_Payment_Token) {
					$result = apply_filters( 'pgabfw_save_subscription_payment_token', $order, $token );
					return $result;
				}else{
					wc_add_notice( 'Something went wrong!', 'error' );

					return array(
						'result'   => 'failure',
						'redirect' => $_POST['_wp_http_referer'],
					);
				}
			}
			return array(
				'result'   => 'success',
				'redirect' => wc_get_endpoint_url( 'payment-methods' ),
			);
		}
	}

	public function save_subscription_payment_token($order, $token){
		$status = $this->maybe_save_scheduled_payment_method($order, $token);
		if($status === true) {
			return array(
				'result'   => 'success',
				'redirect' => $_POST['_wp_http_referer']
			);
		}else{
			wc_add_notice( 'Something went wrong!', 'error' );
			return array(
				'result'   => 'failure',
				'redirect' => $_POST['_wp_http_referer']
			);
		}
	}

	public function process_payment( $order_id ): array {
		if(pgabfw_is_subscription_add_payment_method_page()){
			if($_POST['wc-acceptblue-cc-payment-token'] == 'new') {
				$this->is_token_request       = false;
				$_POST['save_payment_method'] = 'on';
			}else{
				$this->is_token_request = true;
			}
			return $this->add_payment_method();
		}
		$order = wc_get_order( $order_id );
		try {
			$this->has_virtual_products = pgabfw_contain_virtual_products();

			$token = false;
			if(!empty($_POST['wc-acceptblue-cc-payment-token'])){
				$token_id = (int)$_POST['wc-acceptblue-cc-payment-token'];
				if($token_id > 0) {
					$token = WC_Payment_Tokens::get($token_id);
				}else{
					$token = false;
				}
			}

			if($token instanceof WC_Payment_Token_CC){
				$this->is_token_request = true;
				$post_data = array(
					'source' => $token->get_token(),
				);
			}else {
				$this->is_token_request = false;
				$post_data = array(
					'credit_card' => sanitize_text_field( $_POST['credit_card'] ),
					'date_expire' => sanitize_text_field( $_POST['date_expire'] ),
					'cvv2'         => sanitize_text_field( $_POST['cvv'] )
				);

				if(!empty($_POST['save_payment_method'])){
					$this->user_save_card_accept = 'on' === $_POST['save_payment_method'];
				}

				if(function_exists('wcs_order_contains_subscription') && wcs_order_contains_subscription($order)){
					$this->user_save_card_accept = true;
				}
			}

			$post_data = pgabfw_get_billing_shipping_info( $post_data, $order );

			$post_data['total_price'] = floatval($order->get_total());

			if(pgabfw_is_free_trial_subscription($order_id, $post_data) || (class_exists('WC_Subscription') && $order instanceof WC_Subscription && $post_data['total_price'] === 0.0)){
				$this->process_scheduled_free_trial($order, $post_data, $token);
			}else {
				$this->process_normal_payment($order, $post_data, $token);
			}

			WC()->cart->empty_cart();

			pgabfw_log( 'info', $this->id, 'The transaction success. #' . $order->get_order_number() . '.' );

			return array(
				'result'   => 'success',
				'redirect' => $this->get_return_url( $order ),
			);
		}catch(Exception $e){
			pgabfw_log('error', $this->id, 'The transaction error. ' . $e->getMessage() . '.');
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
                wc_add_notice( $e->getMessage(), 'error' );
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

	/**
	 * @throws Exception
	 */
	public function process_scheduled_free_trial($order, $post_data, $token){
		if ( ! $this->is_token_request && $this->user_save_card_accept ) {
			@list($month, $year) = explode( '/', $post_data['date_expire'] );
			$response = $this->api->card_verification(
				preg_replace( '/\s+/', '', $post_data['credit_card'] ),
				intval($month),
				intval($year),
				(string)$post_data['cvv2'],
				true
			);
			if ( is_wp_error( $response ) ) {
				$order->update_status( 'wc-failed' );
				$order->add_order_note( $response->get_error_message() );
				pgabfw_log( 'error', $this->id, $response->get_error_message() . '.' );
				throw new Exception( 'The transaction was declined. ' . $response->get_error_message() );
			}else {
				$this->save_payment_method( $response, $post_data, $token );
				if(!empty($token)) {
					$this->maybe_save_scheduled_payment_method( $order, $token );
				}

				$this->process_response( $response, $order );
			}
		}else{
			$response = ['result' => 'success', 'redirect' => $_POST['_wp_http_referer']];
			if(!empty($token)) {
				$this->maybe_save_scheduled_payment_method( $order, $token );
			}
			$this->process_response( $response, $order );
		}
	}

	public function process_normal_payment($order, $post_data, $token){
		$response = $this->authorize_card_request( $order, $post_data );

		if ( is_wp_error( $response ) ) {
			$order->update_status( 'wc-failed' );
			$order->add_order_note( $response->get_error_message() );
			pgabfw_log( 'error', $this->id, $response->get_error_message() . '.' );
			throw new Exception( 'The transaction was declined. ' . $response->get_error_message() );
		}

		$order->set_transaction_id( $response['reference_number'] );

		if ( ! $this->is_token_request && $this->user_save_card_accept ) {
			$this->save_payment_method( $response, $post_data, $token );
		}

		if(!empty($token)) {
			$this->maybe_save_scheduled_payment_method( $order, $token );
		}


		$this->process_response( $response, $order );
	}

	public function process_schedule_payment($total, $order){
		try {
			$subscriptions = wcs_get_subscriptions_for_order( $order->get_id(), array( 'order_type' => 'any' ) );
			if(count($subscriptions) > 0) {
				$subscription = array_shift($subscriptions);
				$token = $this->get_scheduled_payment_token( $subscription );

				if ( $token === null ) {
					throw new Exception( 'Renew subscription. The transaction was declined. Could not find payment method!' );
				}

				$post_data                = pgabfw_get_billing_shipping_info( [], $order );
				$post_data['total_price'] = floatval( $total );
				$post_data['source']      = $token->get_token();

				$response = $this->authorize_card_request( $order, $post_data );

				if ( is_wp_error( $response ) ) {
					$order->update_status( 'wc-failed' );
					$order->add_order_note( $response->get_error_message() );
					pgabfw_log( 'error', $this->id, $response->get_error_message() . '.' );
					throw new Exception( 'Renew subscription. ' . $response->get_error_message() );
				}

				$order->set_transaction_id( $response['reference_number'] );

				$this->process_response( $response, $order );

				pgabfw_log( 'info', $this->id, 'Renew subscription. The transaction success. #' . $order->get_order_number() . '.' );
			}else{
				throw new Exception( 'Renew subscription. The transaction was declined. Could not find payment method!' );
			}
		}catch(Exception $e){
			$order->update_status( 'wc-failed' );
			if(isset($response) && !empty($response)) {
				$order->add_order_note( $response->get_error_message() );
				pgabfw_log( 'error', $this->id, 'Renew subscription. The transaction was declined. Something went wrong! - ' . $response->get_error_message() . '.' );
			}else{
				$order->add_order_note( $e->getMessage() );
				pgabfw_log( 'error', $this->id, 'Renew subscription. The transaction was declined. Something went wrong! - ' . $e->getMessage() . '.' );
			}

		}

	}

	public function maybe_save_scheduled_payment_method($order, $token){
		if(isset($_POST['update_all_subscriptions_payment_method']) && $_POST['update_all_subscriptions_payment_method'] === "1"){
			return $this->maybe_save_payment_method_for_all_user_subscriptions($order, $token);
		}else {
			return $this->maybe_save_payment_method_for_current_subscription($order, $token);
		}
	}

	public function maybe_save_payment_method_for_current_subscription($order, $token){
		if ( function_exists( 'wcs_order_contains_subscription' ) && wcs_order_contains_subscription( $order ) ) {
			$subscriptions = wcs_get_subscriptions_for_order( $order->get_id() );
			if ( is_array( $subscriptions ) && count( $subscriptions ) > 0 ) {
				foreach ( $subscriptions as $subscription ) {
					update_post_meta( $subscription->get_id(), '_' . $this->id . '_card_id', $token->get_id() );
				}

				return true;
			} else {
				return false;
			}
		} elseif ( class_exists( 'WC_Subscription' ) && $order instanceof WC_Subscription ) {
			$status = update_post_meta( $order->get_id(), '_' . $this->id . '_card_id', $token->get_id() );
			return ! ( ( $status === false ) );
		} else {
			return false;
		}
	}

	public function maybe_save_payment_method_for_all_user_subscriptions($order, $token){
		if(!function_exists('wcs_get_users_subscriptions')) return false;
		$customer_id = $order->get_customer_id();
		$subscriptions = wcs_get_users_subscriptions($customer_id);
		if(is_array($subscriptions) && count($subscriptions) > 0) {
			foreach ($subscriptions as $id => $subscription) {
				update_post_meta( $id, '_' . $this->id . '_card_id', $token->get_id() );
			}
			return true;
		}else {
			return false;
		}
	}

	public function get_scheduled_payment_token($order){
		if(class_exists('WC_Subscription') && $order instanceof WC_Subscription){
			$token_id = get_post_meta($order->get_id(), '_' . $this->id . '_card_id', true);
			if(!empty($token_id)){
				$token = WC_Payment_Tokens::get( $token_id );
				if($token instanceof WC_Payment_Token_CC){
					return $token;
				}else{
					return null;
				}
			}else{
				return null;
			}
		}else{
			return null;
		}
	}

	public static function charge_card_request($ref_num){
		global $wc_acceptblue_card_api;
		$request_path = '/transactions/capture';

		$method = 'POST';

		$args = array(
			'reference_number' => (int)$ref_num
		);

		return $wc_acceptblue_card_api->request($request_path, $method, $args);
	}

	private function authorize_card_request($order, $post_data) {
		$request_path = '/transactions/charge';

		$method = 'POST';
    
    	$total_tax = 0;
		if(WC()->cart !== null) {
			foreach ( WC()->cart->get_tax_totals() as $code => $tax ) :
				$total_tax += $tax->amount;
			endforeach;
		}else{
			$total_tax = pgabfw_get_order_tax_totals($order);
		}

		$args = array(
			'amount' => '',
      		'amount_details' => new stdClass(),
			'name' => $post_data['billing_info']['first_name'] . ' ' . $post_data['billing_info']['last_name'],
			'billing_info' => new stdClass(),
			'shipping_info' => new stdClass(),
			'custom_fields' => new stdClass(),
			'capture' => $this->can_charge_transaction($order),
			'save_card' => true,
			'customer' => (object) array(
				'email' => $post_data['billing_info']['email'] ?? '',
			)
		);

		if($this->has_virtual_products){
			$args['capture'] = true;
		}

		if(array_key_exists('source', $post_data) && !empty($post_data['source'])){
			$args['source'] = self::SOURCE_TYPE . $post_data['source'];
		}else {
			$exp                  = explode( '/', sanitize_text_field( $post_data['date_expire'] ) );
			$args['card']         = preg_replace( '/\s+/', '', $post_data['credit_card'] );
			$args['expiry_month'] = (int) trim( $exp[0] );
			$args['expiry_year']  = (int) trim( $exp[1] );
            if ( ! empty( $post_data['cvv2'] ) ) {
                $args['cvv2'] = $post_data['cvv2'];
            }
        }
		$args['amount']       = floatval($order->get_total());
		$args['amount_details']  = (object) array(
			'tax' => $total_tax,
		);
		$args['billing_info'] = (object) $post_data['billing_info'];
		$args['shipping_info'] = (object) $post_data['shipping_info'];
		$args['transaction_details'] = (object) array(
			'order_number' => pgabfw_get_order_id($order)
		);

		return $this->api->request($request_path, $method, $args, true, $this->id);
	}

	private function print_error($message){
		wc_add_notice( __('Payment error:', 'payment-gateway-accept-blue-for-woocommerce') . print_r( $message, true ), 'error' );
	}

	private function can_charge_transaction($order){
		$can_charge = false;

		if($this->transaction_type === 'charge'){
			$can_charge = true;
		} elseif(function_exists('wcs_order_contains_subscription') && wcs_order_contains_subscription($order)) {
            $can_charge = true;
        } else {
			if ( $this->has_virtual_products === true && $this->charge_virtual === true ) {
				$can_charge = true;
			}
		}

		return $can_charge;
	}

	public function process_response( $response, $order ) {

		$order_id = version_compare( WC_VERSION, '3.0.0', '<' ) ? $order->id : $order->get_id();

		update_post_meta( $order_id, '_acceptblue_xrefnum', $response['reference_number'] );

		if($this->can_charge_transaction($order)){
			update_post_meta( $order_id, '_acceptblue_transaction_charged',  'yes');
			update_post_meta( $order_id, '_transaction_id', $response['reference_number'], true );
			$order->payment_complete($response['reference_number']);

			$message = sprintf( __( 'Accept.blue transaction charged (charge RefNum: %s)', 'payment-gateway-accept-blue-for-woocommerce' ), $response['reference_number'] );
			$order->add_order_note( $message );

			pgabfw_log('info', $this->id, 'Success: ' . $message . '.');

		}else{
			update_post_meta( $order_id, '_transaction_id', $response['reference_number'], true );
			update_post_meta( $order_id, '_acceptblue_transaction_charged',  'no');
			if ( $order->has_status( array( 'pending', 'failed' ) ) ) {
				version_compare( WC_VERSION, '3.0.0', '<' ) ? $order->reduce_order_stock() : wc_reduce_stock_levels( $order_id );
			}

			$order->update_status( 'on-hold', sprintf( __( 'Accept.blue authorized (ID: %s). Process order to take payment.', 'payment-gateway-accept-blue-for-woocommerce' ), $response['reference_number'] ) );
		}

		return $response;
	}

	public function save_payment_method($response, $post_data, &$_token){
		if(is_user_logged_in()) {
			$expire_arr = explode( '/', $post_data['date_expire'] );
			$token      = new WC_Payment_Token_CC();
			$token->set_token( $response['card_ref'] );
			$token->set_gateway_id( $this->id );
			$token->set_last4( $response['last_4'] );
			$token->set_expiry_year( trim( $expire_arr[1] ) );
			$token->set_expiry_month( trim( $expire_arr[0] ) );
			$token->set_card_type( $response['card_type'] );
			$token->set_user_id( get_current_user_id() );
			$token->save();

			$_token = $token;

			WC_Payment_Tokens::set_users_default( get_current_user_id(), $token->get_id() );
		}
	}

	public function saved_payment_methods() {
		$html = '<ul class="woocommerce-SavedPaymentMethods wc-saved-payment-methods" data-count="' . esc_attr( count( $this->get_tokens() ) ) . '">';

		foreach ( $this->get_tokens() as $token ) {
			$html .= $this->get_saved_payment_method_option_html( $token );
		}

		$html .= $this->get_new_payment_method_option_html();
		$html .= '</ul>';
    
    $saved_methods = apply_filters( 'wc_payment_gateway_form_saved_payment_methods_html', $html, $this );

    $allowed_html = array(
      'ul' => array(
        'class' => array(),
        'data-count' => array(),
      ),
      'li' => array(
        'class' => array(),
        
      ),
      'label' => array(
        'for' => array(),
      ),
      'input' => array(
        'type' => array(),
        'name' => array(),
        'value' => array(),
        'id' => array(),
        'style' => array(),
        'class' => array(),
        'checked' => array(),
      ),
    );
		echo wp_kses( $saved_methods, $allowed_html );
	}

	public function process_refund($order_id, $amount = null, $reason = '') {
		$order = wc_get_order( $order_id );

		if ( ! $this->can_refund_order( $order ) ) {
			pgabfw_log('error', $this->id, 'Refund failed. #' . $order->get_order_number());
			return new WP_Error( 'error', __( 'Refund failed.', 'payment-gateway-accept-blue-for-woocommerce' ) );
		}

		if( 0.0 === (float) $amount ){
			pgabfw_log('error', $this->id, 'Refund amount should be more then 0. #' . $order->get_order_number());
			return new WP_Error( 'error', __( 'Refund amount should be more then 0.', 'payment-gateway-accept-blue-for-woocommerce' ) );
		}

		$ref_num = (int) get_post_meta( $order_id, '_acceptblue_xrefnum', true );

		if($ref_num === 0){
			pgabfw_log('error', $this->id, 'Refund reference number not found. #' . $order->get_order_number());
			return new WP_Error('error', __( 'Reference number not found.', 'payment-gateway-accept-blue-for-woocommerce'));
		}

		$single_transaction = $this->api->get_single_transaction($ref_num);

		if(is_wp_error($single_transaction)) {
			pgabfw_log( 'error', $this->id, 'Refund failed. #' . $order->get_order_number() . '. ' . $single_transaction->get_error_message() );

			return new WP_Error( 'error', __( 'Refund failed.', 'payment-gateway-accept-blue-for-woocommerce' ) );
		}

		if(is_array($single_transaction) && array_key_exists('status_details', $single_transaction)){
			$total = (float) $order->get_total();

			$status = $single_transaction['status_details']['status'];

			$response = false;

			$wrong_status = array('error', 'reserve', 'originated', 'returned', 'cancelled', 'declined', 'voided', 'approved', 'blocked');
			$void_status = array('queued', 'captured', 'pending');

			if($amount < $total && in_array($status, $void_status)){
				return new WP_Error('error', __( 'Partial refund can be processed after the transaction status is marked as "Settled" in the AcceptBlue merchant panel.', 'payment-gateway-accept-blue-for-woocommerce'));
			}

			if($status === 'settled'){
				$response = $this->api->refund($ref_num, (float)$amount);
				if(is_wp_error($response)) {
					pgabfw_log( 'error', $this->id, 'Refund failed. #' . $order->get_order_number() . '. ' . $response->get_error_message() );

					return new WP_Error( 'error', __( 'Refund failed.', 'payment-gateway-accept-blue-for-woocommerce' ) );
				}
				if(is_array($response) && array_key_exists('error_message', $response) && !empty($response['error_message'])){
					pgabfw_log('error', $this->id, 'Refund: ' . $response['error_message'] . ' #' . $order->get_order_number());
					return new WP_Error('error', __( $response['error_message'], 'payment-gateway-accept-blue-for-woocommerce'));
				}
			}

			if(in_array($status, $void_status)){
				$response = $this->api->void($ref_num);
				if(is_wp_error($response)) {
					pgabfw_log( 'error', $this->id, 'Refund failed. #' . $order->get_order_number() . '. ' . $response->get_error_message() );

					return new WP_Error( 'error', __( 'Refund failed.', 'payment-gateway-accept-blue-for-woocommerce' ) );
				}
				if(is_array($response) && array_key_exists('error_message', $response) && !empty($response['error_message'])){
					pgabfw_log('error', $this->id, 'Refund: ' . $response['error_message'] . ' #' . $order->get_order_number());
					return new WP_Error('error', __( $response['error_message'], 'payment-gateway-accept-blue-for-woocommerce'));
				}
			}

			if($response === false && in_array($status, $wrong_status)){
				pgabfw_log('error', $this->id, sprintf('Refund: Transaction has wrong status "%s".', $status) . ' #' . $order->get_order_number());
				return new WP_Error('error', __( sprintf('Transaction has wrong status "%s".', $status), 'payment-gateway-accept-blue-for-woocommerce'));
			}

			if($response !== false && $response['status'] === 'Approved'){
				pgabfw_maybe_need_change_subscription_after_refund($order_id);
				return true;
			}else{
				pgabfw_log('error', $this->id, 'Refund: ' . sprintf('Transaction has wrong status "%s".', $status) . ' #' . $order->get_order_number());
				return new WP_Error('error', __( 'Error! Something wrong.', 'payment-gateway-accept-blue-for-woocommerce'));
			}
		}else{
			pgabfw_log('error', $this->id, 'Refund: ' . 'Transaction not found.' . ' #' . $order->get_order_number());
			return new WP_Error('error', __( 'Transaction not found.', 'payment-gateway-accept-blue-for-woocommerce'));
		}

	}

    public function update_amount_on_order_update($order): void
    {
        if(!is_admin()) return;

        $is_calc = $_POST['action'] === 'woocommerce_calc_line_taxes';

        try {
            $payment_method_id = $order->get_meta('_payment_method');
            if($payment_method_id !== $this->id) return;


            $original_order = wc_get_order($order->get_id());
            $original_total = $original_order->get_total();




            $total = $order->get_total();
            $transaction_id = intval($order->get_meta('_acceptblue_xrefnum'));

            if ($original_total === $total) {
                return;
            }

            if ($transaction_id === 0) return;

            $response = $this->api->update_transaction($transaction_id, $total);

            if (is_wp_error($response)) {
                $order->add_order_note(
                    sprintf(
                        __('Accept.blue error updating the transaction amount. Error message: %s', 'payment-gateway-accept-blue-for-woocommerce'),
                        $response->get_error_message()
                    )
                );
                if(!$is_calc) {
                    wp_send_json_error(array('error' => $response->get_error_message()));
                }
            } else {
                $currency = get_woocommerce_currency_symbol();
                $order->add_order_note(
                    sprintf(
                        __('Accept.blue amount of the transaction has been updated to %s', 'payment-gateway-accept-blue-for-woocommerce'),
                        $currency . $total
                    )
                );
                $order->add_order_note(
                    __('<b>It\'s not advisable to adjust the order again as it may lead to order error</b>', 'payment-gateway-accept-blue-for-woocommerce')
                );
            }
        }catch (Exception $e){
            if(!$is_calc) {
                wp_send_json_error(array('error' => $e->getMessage()));
            }
        }
    }
}