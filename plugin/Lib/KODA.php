<?php
namespace KODA\TPay_Plugin\Lib;

if ( ! class_exists( 'KODA' ) ) {

	class KODA {


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
			$this->api_host = KODA_API_HOST . "/v1";
			$this->endpoint = '';
			$this->header = array();
			$this->data_type = 'json';
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
				'headers' => array_merge([
					'Content-Type' => 'application/json',
					'Authorization' => $this->header['Authorization'],
				]),
				'body'      => json_encode($this->payload),
				'method'    => $this->call_type
			);

			$result =  wp_remote_request( $this->endpoint, $args );
			return json_decode(wp_remote_retrieve_body($result), true);
		}

		public function getTokenAuth($force = false) {
			$token = get_option('tpay_widget_api_token');
			if ($token === false || $token === '' || $force === true) {
				$this->endpoint = $this->api_host . '/oauth/token';
				$this->payload = array(
					'grant_type' => 'client_credentials',
					'client_id' => get_option('tpay_widget_user_client_id'),
					'client_secret' => get_option('tpay_widget_user_client_secret'),
					'scope' => implode(' ', KODA_API_SCOPES)
				);
				$this->call_type = 'POST';
				$data = $this->call();
				if (isset($data['meta']) && isset($data['meta']['status']) && $data['meta']['status'] !== 200) {
					error_log('KODA API ERROR (generate token): ' . print_r(json_encode($data), true));
					return ['error' => true, 'response' => $data['response']];
				}
				$token = $data['response']['access_token'];
				update_option('tpay_widget_api_token', $token);
			};
			return $token;
		}

		public function createUser($email) {
			$this->endpoint = $this->api_host . '/shop/user';
			$this->header = [
				'Authorization'=> 'Bearer ' . KODA_API_TOKEN
			];
			$this->call_type = 'POST';
			$this->payload = [
				'email' => $email,
			];
			$data = $this->call();

			if (isset($data['meta']) && isset($data['meta']['status']) && $data['meta']['status'] !== 200) {
				error_log('KODA API ERROR 400 (create user): ' . print_r(json_encode($data), true));
				return ['error' => true, 'response' => $data];
			}

			if (!isset($data['response']) || !isset($data['response']['client_id'])) {
				error_log('KODA API ERROR 500 (create user): ' . print_r(json_encode($data), true));
				return ['error' => true, 'response' => $data];
			}

			return [
				"client_id" => $data['response']['client_id'],
				"client_secret" => $data['response']['client_secret']
			];

		}

		public function updateBot($new = false) {
			$token = $this->getTokenAuth();
			$this->endpoint = $this->api_host . '/shop/bot';
			$this->header = [
				'Authorization' => 'Bearer ' . $token
			];
			$this->call_type = $new ? 'POST' : 'PUT';
			$this->payload = array(
				"shop" => [
					"iban_number" => get_option('tpay_widget_settings_field_iban'),
					"contact_email" => get_option('tpay_widget_settings_field_contact_email'),
					"terms_url" => get_option('tpay_widget_settings_field_policy'),
					"delivery_method" => get_option('tpay_widget_settings_field_delivery'),
					"delivery_name" => get_option('tpay_widget_settings_field_delivery_name'),
					"delivery_cost" => explode("::", get_option('tpay_widget_settings_field_delivery_name'))[2],
					"name" => get_bloginfo('name')
				],
				"config" => [
					"shop_url" => get_rest_url(),
					"api_auth" => [
						"username" => get_option('tpay_widget_api_user'),
						"password" => wc_api_hash(get_option('tpay_widget_api_pass'))
					]
				],
			
				"version" => '0.0.2'
			);
			$data = $this->call();
			if (isset($data['meta']) && isset($data['meta']['status']) && $data['meta']['status'] !== 200) {
				error_log('KODA API ERROR 400 (update/create bot): ' . print_r(json_encode($data), true));
				return ['error' => true, 'response' => $data];
			}
			if (!isset($data['response']) || !isset($data['response']['bot_id'])) {
				error_log('KODA API ERROR 500 (update/create bot): ' . print_r(json_encode($data), true));
				return ['error' => true, 'response' => $data];
			}
			$response = $data['response'];


			return [
				'bot_id' => $response['bot_id'],
				'name' => $response['name'],
				'cover' => $response['cover'],
			];
		}

		public function paymentCallback($request) {
			$this->endpoint = $this->api_host . '/shop/callback';
			$token = $this->getTokenAuth();
			$this->header = [
				'Authorization' => 'Bearer ' . $token
			];
			$this->payload = $request;
			$this->call_type = 'POST';
			$data = $this->call();
		}
	}
}
?>
