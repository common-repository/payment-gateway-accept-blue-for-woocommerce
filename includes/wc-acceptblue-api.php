<?php

class WC_Acceptblue_API{
	private $prod_endpoint = 'https://api.accept.blue/api/v2';
	private $dev_endpoint = 'https://api.develop.accept.blue/api/v2';

	private $IS_APPROVED = 'A';

	private $is_debug_mode = false;

	private $source_key = '';

	private $pin_code = '';

	private function get_source_option_key(){
		if(!$this->is_debug_mode){
			return 'source_key';
		}else{
			return 'sandbox_source_key';
		}
	}

	public function set_source_key($key){
		$this->source_key = $key;
	}

	public function set_pin_code($code){
		$this->pin_code = $code;
	}

	public function enable_debug_mode($status = false){
		$this->is_debug_mode = $status;
	}

	public function get_source_key(){
		return $this->source_key;
	}

	public function get_pin_code(){
		return $this->pin_code;
	}

	public function get_endpoint(){
		if($this->is_debug_mode) {
			return $this->dev_endpoint;
		}else{
			return $this->prod_endpoint;
		}
	}

	public function request($request_path, $method, $request_data, $validation = true, $id = ''){
		$headers = array(
			'Content-Type' => 'application/json',
			'Authorization' => sprintf('Basic %s', base64_encode($this->get_source_key() . ':' . $this->get_pin_code()))
		);

		if ( $method === 'POST' ) {
			$response = wp_safe_remote_post(
				$this->get_endpoint() . $request_path,
				array(
					'headers' => $headers,
					'method'  => $method,
					'body'    => json_encode( apply_filters( 'woocommerce_acceptblue_request_body', $request_data ) ),
					'timeout' => 70
				)
			);
		} else {
			unset($headers['Content-Type']);

			$response = wp_safe_remote_get(
				$this->get_endpoint() . $request_path,
				array( 'headers' => $headers, 'timeout' => 70 )
			);
		}

		if(is_wp_error($response)){
			return new WP_Error( 'acceptblue_error', __( 'There was a problem connecting to the payment gateway.', 'payment-gateway-accept-blue-for-woocommerce' ) );
		}

		$parsed_response = json_decode($response['body'], true);

		if($validation) {
			$status_code = $parsed_response['status_code'] ?? $response['response']['code'];
			if ( $this->is_response_error($status_code) ) {
				$message = $parsed_response['error_message'];
				if(!empty($parsed_response['card_ref'])) {
					return new WP_Error( "acceptblue_error", "{$parsed_response['status']}: {$parsed_response['error_message']}", 'payment-gateway-accept-blue-for-woocommerce' );
				}else {
					return new WP_Error( "acceptblue_error", "{$message}", 'payment-gateway-accept-blue-for-woocommerce' );
				}
			} else {
				return $parsed_response;
			}
		}else{
			return $parsed_response;
		}
	}

	public function get_check_transactions($start_date, $end_date, $limit = 100, $offset = 0){
		$request_path = '/transactions?order=asc&limit=%s&date_from=%s&date_to=%s&offset=%s';

		$method = 'GET';

		$args = null;

		return $this->request(sprintf($request_path, $limit, $start_date, $end_date, $offset), $method, $args, false);
	}

    public function update_transaction($transaction_id, $amount)
    {
        $request_path = '/transactions/adjust';

        $method = 'POST';

        return $this->request(
            $request_path,
            $method,
            array(
                'reference_number' => intval($transaction_id),
                'amount' => floatval($amount)
            )
        );
    }

	public function refund($transaction_id, $amount = null){
		$request_path = '/transactions/refund';

		$method = 'POST';

		$args = array(
			'reference_number' => $transaction_id
		);

		if($amount !== null) {
			$args['amount'] = $amount;
		}

		return $this->request($request_path, $method, $args, false);
	}

	public function void($transaction_id){
		$request_path = '/transactions/void';

		$method = 'POST';

		$args = array(
			'reference_number' => $transaction_id
		);

		return $this->request($request_path, $method, $args, false);
	}

	public function get_single_transaction($transaction_id){
		$request_path = '/transactions/%s';

		$method = "GET";

		$args = null;

		return $this->request(sprintf($request_path, $transaction_id), $method, $args, false);
	}

	public function card_verification($card, $expire_month, $expire_year, $cvv, $save_card = false){
		$request_path = '/transactions/verify';

		$method = 'POST';

		$args = array(
			'card' => $card,
			'expiry_month' => $expire_month,
			'expiry_year' => $expire_year,
			'cvv2' => $cvv
		);

		if($save_card) {
			$args['save_card'] = true;
		}

		return $this->request($request_path, $method, $args, false);
	}

	public function is_response_error($status_code){
		if(is_numeric($status_code) && (int)$status_code > 200) return true;
		if(is_string($status_code) && $status_code !== $this->IS_APPROVED) return true;
		return false;
	}
}