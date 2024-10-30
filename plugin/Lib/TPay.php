<?php
namespace KODA\TPay_Plugin\Lib;

if ( ! class_exists( 'TPpay' ) ) {

	class TPay {


		/**
		 * @var String
		 */
		public $endpoint;


		/**
		 * @var Array
		 */
		public $header;


		/**
		 * @var String
		 */
		public $data_type;


		/**
		 * @var String
		 */
		public $call_type;
		
		/**
		 * @var String
		 */
		public $payload;

		/**
		 * @var String
		 */
		public $api_host;


		/**
		 * Define the properties inside a instance
		 *
		 * @return Void
		 */
		 public function __construct() {
			$api_key = get_option('tpay_widget_settings_field_api_id'); 

			$this->api_host = 'https://secure.tpay.com/api/gw/'.$api_key;
			$this->endpoint  = '';
			$this->header    = array();
			$this->data_type = 'json'; //xml or json
			$this->call_type = '';
			$this->payload = [];
		 }

		/**
		 * Define the variables for db table creation
		 *
		 * @return Array
		 */
		public function call() {
			$args = array(
				'headers' => [
					'Content-Type'   => 'application/json',
				],
				'body'      => json_encode($this->payload),
				'method'    => $this->call_type
			);
			
			$result =  wp_remote_request( $this->endpoint, $args );
			return json_decode(wp_remote_retrieve_body($result), true);
		}

		public function create_transaction(\WC_Order $order) {

			$password = get_option('tpay_widget_settings_field_api_password'); 
			$merchant_id = get_option('tpay_widget_settings_field_merchant_id'); 
			$security_code = get_option('tpay_widget_settings_field_security_code'); 

			$orderData = $order->get_data();
			$platformUserId = $order->get_meta('platform_user_id');

			$crcDataBase64 = base64_encode($platformUserId . ":" . $order->get_id());

			// $checkSum = md5($merchant_id . $orderData['total'] . $crcDataBase64 . $security_code);

			$md5Params = [$merchant_id, $orderData['total'], $crcDataBase64, $security_code];
			$checkSum = md5(implode('&', $md5Params));

			$this->data_type = 'json';
			$this->endpoint = $this->api_host . '/transaction/create';

			$this->payload = [
				"id" => $merchant_id,
				"amount" => $orderData['total'],
				"description" => "Zamówienie nr " . $order->get_id(),
				"crc" => $crcDataBase64,
				"md5sum" => $checkSum,
				"group" => 150,
				"result_url" => get_rest_url()."tpay/v1/paymentCallback",
				"result_email" => get_option('tpay_widget_settings_field_user_email'),
				"language" => "pl",
				"email" => $orderData['billing']['email'],
				"name" => $orderData['billing']['first_name'],
				"accept_tos" => 1,
				"api_password" => $password,
			];
			$this->call_type = 'POST';

			$data = $this->call();

			return $data;
		}
		public function blik_payment($blik_code, \WC_Order $order) {

			$password = get_option('tpay_widget_settings_field_api_password'); 
			$merchant_id = get_option('tpay_widget_settings_field_merchant_id'); 
			$security_code = get_option('tpay_widget_settings_field_security_code'); 

			$orderData = $order->get_data();
			$platformUserId = $order->get_meta('platform_user_id');
			$title = $order->get_meta('payment_title');
			$crcDataBase64 = base64_encode($platformUserId);

			$checkSum = md5($merchant_id . $orderData['total'] . $crcDataBase64 . $security_code);

			$this->endpoint = $this->api_host . '/transaction/blik';
			$this->payload = [
				"code" => strval($blik_code),
				"title" => $title,
				"api_password" => $password,
			];
			$this->call_type = 'POST';

			$data = $this->call();

			return [
				'result' => $data['result']
			];
		}
	}
} ?>
