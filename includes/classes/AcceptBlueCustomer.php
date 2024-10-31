<?php

class AcceptBlueCustomer {
	private array $customer_data = array();

	public function __construct() {
	}

	/**
	 * @param $identifier
	 * @description (Required) Something that identifies the customer, e.g. the customer's name or company
	 * @return void
	 */
	public function set_identifier($identifier){
		$this->customer_data['identifier'] = $identifier;
	}


	/**
	 * @param $first_name
	 * @description (Optional)
	 * @return void
	 */
	public function set_customer_first_name($first_name){
		$this->customer_data['first_name'] = $first_name;
	}

	/**
	 * @param $last_name
	 * @description (Optional)
	 * @return void
	 */
	public function set_customer_last_name($last_name){
		$this->customer_data['last_name'] = $last_name;
	}

	/**
	 * @param $email
	 * @description (Optional)
	 * @return void
	 */
	public function set_customer_email($email){
		$this->customer_data['email'] = $email;
	}

	/**
	 * @param $phone
	 * @description (Optional)
	 * @return void
	 */
	public function set_customer_phone($phone){
		$this->customer_data['phone'] = $phone;
	}

	/**
	 * @param $website
	 * @description (Optional)
	 * @return void
	 */
	public function set_customer_website($website){
		$this->customer_data['website'] = $website;
	}

	/**
	 * @param array $billing_info
	 * @description (Optional)
	 * @return void
	 */
	public function set_customer_billing_info(array $billing_info){
		$this->customer_data['billing_info'] = $billing_info;
	}

	/**
	 * @param array $shipping_info
	 * @description (Optional)
	 * @return void
	 */
	public function set_customer_shipping_info(array $shipping_info){
		$this->customer_data['shipping_info'] = $shipping_info;
	}

	/**
	 * @return false|string
	 */
	public function get_customer_data(){
		return json_encode($this->customer_data);
	}
}