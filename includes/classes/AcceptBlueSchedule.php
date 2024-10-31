<?php

class AcceptBlueSchedule {

	/**
	 * @description  every day
	 */
	const FREQUENCY_DAILY = 'daily';
	/**
	 * @description  every week
	 */
	const FREQUENCY_WEEKLY = 'weekly';
	/**
	 * @description every two weeks
	 */
	const FREQUENCY_BIWEEKLY = 'biweekly';
	/**
	 * @description every month
	 */
	const FREQUENCY_MONTHLY = 'monthly';
	/**
	 * @description every three month
	 */
	const FREQUENCY_QUARTERLY = 'quarterly';
	/**
	 * @description twice a year
	 */
	const FREQUENCY_BIANNUALLY = 'biannually';
	/**
	 * @description every year
	 */
	const FREQUENCY_ANNUALLY = 'annually';

	private array $scheduleData = [];

	public function set_schedule_customer_id(int $customer_id){
		$this->scheduleData['id'] = $customer_id;
		return $this;
	}

	public function set_schedule_title(string $title){
		$this->scheduleData['title'] = $title;
		return $this;
	}

	public function set_schedule_amount(float $amount){
		$this->scheduleData['amount'] = $amount;
		return $this;
	}

	public function set_schedule_payment_method_id(int $payment_method_id){
		$this->scheduleData['payment_method_id'] = $payment_method_id;
		return $this;
	}

	public function set_schedule_frequency(string $frequency){
		$this->scheduleData['frequency'] = $frequency;
		return $this;
	}

	public function set_schedule_next_run_date(string $date){
		$this->scheduleData['next_run_date'] = $date;
		return $this;
	}

	public function set_schedule_num_left(int $num_left){
		$this->scheduleData['num_left'] = $num_left;
		return $this;
	}

	public function set_schedule_active(bool $active){
		$this->scheduleData['active'] = $active;
		return $this;
	}

	public function set_schedule_receipt_email(string $receipt_email){
		$this->scheduleData['receipt_email'] = $receipt_email;
		return $this;
	}

	public function set_schedule_use_this_source_key(bool $use_this_source_key){
		$this->scheduleData['use_this_source_key'] = $use_this_source_key;
		return $this;
	}

	public function get_schedule_data(){
		return json_encode($this->scheduleData);
	}
}