<?php
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;

class WC_Acceptblue_Recurring_API{
	private $client;
	private $prod_endpoint = 'https://api.accept.blue/api/v2/';
	private $dev_endpoint = 'https://api.develop.accept.blue/api/v2/';
	private $api_mode = 'prod';
	private $source_key = '';
	private $pin_code = '';

	const PROD_MODE = 'prod';
	const DEV_MODE = 'dev';

	public function __construct() {
		$this->client = new Client();
	}
	public function __clone () {}
	public function __sleep () {}
	public function __wakeup () {}

	private function get_endpoint(){
		if($this->api_mode === self::DEV_MODE) {
			return $this->dev_endpoint;
		}else{
			return $this->prod_endpoint;
		}
	}

	private function get_authorization(){
		return sprintf('Basic %s', base64_encode($this->source_key . ':' . $this->pin_code));
	}

	private function get_headers($args = []){
		$default_headers = [
			'User-Agent' => $_SERVER['HTTP_USER_AGENT'],
			'Content-Type' => 'application/json',
			'Authorization' => $this->get_authorization()
		];

		return array_merge($default_headers, $args);
	}

	private function send_request($request){
		try {
			$response = $this->client->send($request);
			return json_decode( $response->getBody()->getContents() );
		} catch ( GuzzleHttp\Exception\GuzzleException $e ) {
			return $e;
		}
	}

	public static function get_instance() {
		static $instance = null;
		if ( null === $instance ) {
			$instance = new static();
		}
		return $instance;
	}

	public function set_api_mode($mode){
		$this->api_mode = $mode;
	}

	public function set_authorization_data($source_key, $pin_code){
		$this->source_key = $source_key;
		$this->pin_code = $pin_code;
	}

	public function create_customer($customer){
		$method = 'customers';
		$url = $this->get_endpoint() . $method;
		$headers = $this->get_headers();

		$request = new Request('POST', $url, $headers, $customer);
		return $this->send_request($request);
	}

	public function create_payment_method(string $name, string $card, int $expiry_month, int $expiry_year){
		$method = 'payment-methods';
		$url = $this->get_endpoint() . $method;
		$headers = $this->get_headers();

		$args = [
			"name"         => $name,
			"expiry_month" => $expiry_month,
			"expiry_year"  => $expiry_year,
			"card"         => $card
		];

		$request = new Request('POST', $url, $headers, json_encode($args));
		return $this->send_request($request);
	}

	public function create_schedule(int $customer_id, string $schedule){
		$method = 'customers/';
		$url = $this->get_endpoint() . $method . $customer_id . '/recurring-schedules';
		$headers = $this->get_headers();

		$request = new Request('POST', $url, $headers, $schedule);
		return $this->send_request($request);
	}

	public function update_payment_method(int $payment_method_id,  array $args){
		$method = 'payment-methods/';
		$url = $this->get_endpoint() . $method . $payment_method_id;
		$headers = $this->get_headers();

		$request = new Request('PATCH', $url, $headers, json_encode($args));
		return $this->send_request($request);
	}

	public function update_schedule(int $schedule_id, array $args){
		$method = 'recurring-schedules/';
		$url = $this->get_endpoint() . $method . $schedule_id;
		$headers = $this->get_headers();

		$request = new Request('PATCH', $url, $headers, json_encode($args));
		return $this->send_request($request);
	}

	public function delete_payment_method(int $payment_method_id){
		$method = 'payment-methods/';
		$url = $this->get_endpoint() . $method . $payment_method_id;
		$headers = $this->get_headers();

		$request = new Request('DELETE', $url, $headers);
		return $this->send_request($request);
	}

	public function delete_schedule(int $schedule_id){
		$method = 'recurring-schedules/';
		$url = $this->get_endpoint() . $method . $schedule_id;
		$headers = $this->get_headers();

		$request = new Request('DELETE', $url, $headers);
		return $this->send_request($request);
	}

	public function get_all_schedules(string $order, int $limit, int $offset){
		$method = 'recurring-schedules';
		$url = $this->get_endpoint() . $method . '?' . http_build_query(compact('order', 'limit', 'offset'));
		$headers = $this->get_headers();

		$request = new Request('GET', $url, $headers);
		return $this->send_request($request);
	}

	public function get_schedule(int $id){
		$method = 'recurring-schedules/';
		$url = $this->get_endpoint() . $method . $id;
		$headers = $this->get_headers();

		$request = new Request('GET', $url, $headers);
		return $this->send_request($request);
	}

	public function get_schedule_transactions(int $schedule_id, array $args = []){
		$methods = 'recurring-schedules/';
		$url = $this->get_endpoint() . $methods . $schedule_id . '/transactions';
		$headers = $this->get_headers();

		if(count($args) > 0) $url . '?' . http_build_query($args);

		$request = new Request('GET', $url, $headers);

		return $this->send_request($request);
	}

	public function get_payments_methods(string $order, int $limit, int $offset){
		$method = 'payment-methods/';
		$url = $this->get_endpoint() . $method;
		$headers = $this->get_headers();

		$request = new Request('GET', $url, $headers);

		return $this->send_request($request);
	}

	public function get_single_payment_method(int $id){
		$method = 'payment-methods/';
		$url = $this->get_endpoint() . $method . $id;
		$headers = $this->get_headers();

		$request = new Request('GET', $url, $headers);

		return $this->send_request($request);
	}

	public function get_schedules_for_payment_method(int $payment_id){
		$method = 'payment-methods/';
		$url = $this->get_endpoint() . $method . $payment_id . '/recurring-schedules';
		$headers = $this->get_headers();

		$request = new Request('GET', $url, $headers);

		return $this->send_request($request);
	}

	public function get_customer(int $id){
		$method = 'customers/';
		$url = $this->get_endpoint() . $method . $id;
		$headers = $this->get_headers();

		$request = new Request('GET', $url, $headers);

		return $this->send_request($request);
	}

	public function is_error($response): bool {
		return $response instanceof GuzzleHttp\Exception\GuzzleException;
	}
}