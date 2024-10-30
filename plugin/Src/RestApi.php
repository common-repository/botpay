<?php
namespace KODA\TPay_Plugin\Src;

use Automattic\WooCommerce\Admin\API\Data;
use DateTime;
use KODA\TPay_Plugin\Lib\KODA;
use KODA\TPay_Plugin\Lib\TPay;
use WC_Shipping_Rate;
use WP_REST_Controller as WP_REST_Controller;
use WP_REST_Request;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Extending REST API framework of WordPress
 *
 * @author     Nirjhar Lo
 * @package    wp-plugin-framework
 */
if ( ! class_exists( 'RestApi' ) ) {

	class RestApi extends WP_REST_Controller {

		private $products = [];

		public function __construct() {
			add_action( 'init', array( $this, 'get_woo_products' ) );
			header("Access-Control-Allow-Origin: *");
			$this->register_routes();
		}



		public function get_woo_products() {
			$cat = get_option('tpay_widget_settings_field_categories');
			$onPage = get_option('tpay_widget_settings_field_count_items');
			$args = array(
				'category' => array($cat),
				'orderby'  => 'date',
				'order' => 'DESC',
				'limit' => $onPage,
			);
			$this->products = wc_get_products( $args );
		}


		/**
		 * REST API routes
		 *
		 * @return Object
		 */
		public function register_routes() {

			$version = '1';
    		$namespace = 'tpay/v' . $version;

			//Available options for methods are CREATABLE, READABLE, EDITABLE, DELETABLE
			register_rest_route( $namespace, '/products', array(
        		'methods'             => \WP_REST_Server::READABLE,
        		'callback'            => array( $this, 'callbackGetProducts' ),
        		'permission_callback' => array( $this, 'authenticate' ),
        		'args'                => array()
      		));


			register_rest_route( $namespace, '/product', array(
        		'methods'             => \WP_REST_Server::READABLE,
        		'callback'            => array( $this, 'callbackGetProductVariant' ),
        		'permission_callback' => array( $this, 'authenticate' ),
        		'args'                => array()
      		));

			register_rest_route( $namespace, '/order', array(
        		'methods'             => \WP_REST_Server::CREATABLE,
        		'callback'            => array( $this, 'callbackPostOrder' ),
        		'permission_callback' => array( $this, 'authenticate' ),
        		'args'                => array()
      		));

			register_rest_route( $namespace, '/payment', array(
        		'methods'             => \WP_REST_Server::CREATABLE,
        		'callback'            => array( $this, 'callbackPaymant' ),
        		'permission_callback' => array( $this, 'authenticate' ),
        		'args'                => array()
      		));

			register_rest_route( $namespace, '/paymentCallback', array(
        		'methods'             => \WP_REST_Server::CREATABLE,
        		'callback'            => array( $this, 'callbackPaymentTpay' ),
        		'permission_callback' => array( $this, 'permissionCallback' ),
        		'args'                => array()
      		));
		}
		public function callbackGetProducts($request) {
			$cat = get_option('tpay_widget_settings_field_categories');
			$sort = get_option('tpay_widget_settings_field_show_items');
			$onPage = get_option('tpay_widget_settings_field_count_items');
			$page = $request->get_param('page') ?? '1';
			$args = array(
				'status' => 'publish',
				'category' => array($cat),
				'orderby'  => $sort === 'random' ? 'rand' : 'date',
				'order' => $sort === 'random' ? '' : 'DESC',
				'limit' => $onPage,
				'page' => $page
			);
			$items = wc_get_products( $args );
			$allProducts = wc_get_products( array( 'status' => 'publish', 'limit' => -1 ) );
			if (count($allProducts) == 0) {
				return new \WP_REST_Response( [
					'items' => [],
					'meta' => [
						'on_page' => $onPage,
						'page' => $page,
						'pages' => 1,
						'items' => 0
				]], 200);
			}
			$pages = ceil(sizeof($allProducts) / $onPage);
			$response = [
				'items' => $items,
				'meta' => [
					'on_page' => $onPage,
					'page' => $page,
					'pages' => $pages,
					'items' => sizeof($allProducts),
				]
			];
    		$data = $this->prepare_items_for_response( $response, $request );

    		if ( $data ) {
      			return new \WP_REST_Response( $data, 200 );
    		} else {
				return new \WP_Error( 'status_code', __( 'message', 'text-domain' ) );
			}
		
		}
		
		public function callbackGetProductVariant($request) {
			$product_id = $request->get_param('product_id');
			$onPage = get_option('tpay_widget_settings_field_count_items');
			$page = $request->get_param('page') ?? '1';
			if (!$product_id) {
				return new \WP_Error( 'status_code', __( 'message', 'text-domain' ) );
			}
			$item = wc_get_product( $product_id );
			
			$data = $this->prepare_item_for_response( $item, $request );
			$pages = ceil(sizeof($data) / $onPage);
			$response = [
				'items' => $data,
				'meta' => [
					'on_page' => $onPage,
					'page' => $page,
					'pages' => $pages,
					'items' => sizeof($data),
				]
			];

    		if ( $data ) {
      			return new \WP_REST_Response( $response, 200 );
    		} else {
				return new \WP_Error( 'status_code', __( 'message', 'text-domain' ) );
			}
		
		}
		
		public function callbackPostOrder(\WP_REST_Request $request) {
			$params = json_decode($request->get_body(), true);
			$data = [];
			$order_id = $params['product_id'];
			$order = $this->create_order($order_id, $params['client']);

			$ship = get_option('tpay_widget_settings_field_delivery_name');
			$ship = explode("::", $ship);
			
			$item = new \WC_Order_Item_Shipping();
			$zones = \WC_Shipping_Zones::get_zones();
			$method = '';
            foreach ($zones as $key => $zone) {
                foreach ($zone['shipping_methods'] as $key => $single_zone) {
					$method_id = $single_zone->id.':'.$single_zone->get_instance_id();
					if (($ship[0] . ':' . $ship[1]) === $method_id) {
						$method = $single_zone;
					}
                }
            }
			$item->set_method_title( $method->get_title() );
			$item->set_method_id( $method->id.':'.$method->get_instance_id());
			$item->set_total( $method->cost );

			$order->add_item( $item );
			$order->calculate_totals();

			$tpayApi = new TPay();
			$payData = $tpayApi->create_transaction($order);
			if (isset($payData['title']) && $payData['title'] !== '') {
				$order->add_meta_data('payment_title', $payData['title']);
				$order->save();
				$data['order_id'] = $order->get_id();
				$data['total'] = $order->get_total();
				$data['shipping'] = $method->cost;
      			return new \WP_REST_Response( $data, 200 );
    		} else {
				return new \WP_REST_Response( $payData, 400 );
			}
		}

		public function getCheckSum($body) {
			$merchant_id = get_option('tpay_widget_settings_field_merchant_id'); 
			$security_code = get_option('tpay_widget_settings_field_security_code'); 

			return md5($merchant_id . $body['tr_id'] . $body['tr_paid'] . $body['tr_crc'] . $security_code);
		}

		public function callbackPaymant(\WP_REST_Request $request) {
			$params = json_decode($request->get_body(), true);
			$data = [];

			$order = wc_get_order($params['order_id']);
			$tpayApi = new TPay();
			$payResult = $tpayApi->blik_payment($params['blik_code'], $order);

			if ($payResult['result'] == 1) {
				$order->payment_complete($order->get_meta('payment_title'));
				$order->set_status('processing');
				$order->save();
				return new \WP_REST_Response( TRUE, 200 );	
			} else {
				return new \WP_REST_Response( [
					'errors' => 'PAYMENT_NOT_SUCCEED',
					'data' => $payResult
				], 400 );
			}
		}
		
		public function callbackPaymentTpay(\WP_REST_Request $request) {
			$params = $request->get_params();
			$crcData = base64_decode($params['tr_crc']);
			$platformUserId = explode(":", $crcData)[0];
			$orderId = explode(":", $crcData)[1];

			$checkSum = $this->getCheckSum($params);
			
			if ($params['md5sum'] !== $checkSum) {
				return new \WP_REST_Response( "Wrong check sum", 400 );
			}

			$kodabotsApi = new KODA();
			$kodabotsApi->paymentCallback([
				"type" => "payment",
				"status" => "success",
				"timestamp" => time(),
				"payload" => [],
				"platform_user_id" => 'fb:'.$platformUserId
			]);
			return new \WP_REST_Response( TRUE, 200 );
		}

		private function create_order($order_id, $params) {
			$address = array(
				'first_name' => isset($params['first_name']) && !empty($params['first_name']) ? $params['first_name'] : 'Nieznajomy',
				'last_name'  => isset($params['last_name']) && !empty($params['last_name']) ? $params['last_name'] : 'Nieznajomy',
				'email'      => $params['email'],
				'phone'      => $params['phone_number'],
				'address_1'  => $params['address'],
			);

			if (isset($params['variation_id'])) {
				$variationId = $params['variation_id'];
			}
			$order = wc_create_order();

			$order->add_product( wc_get_product(isset($variationId) ? $variationId : $order_id), 1);
			$order->add_meta_data('platform_user_id', $params['platform_user_id']);

			$order->add_meta_data('shipping_method', get_option('tpay_widget_settings_field_delivery'));
			if (isset($params['delivery_details']['locker_id']) && $params['delivery_details']['locker_id'] !== '') {
				$order->add_meta_data('locker_id', $params['delivery_details']['locker_id']);
			} else {
				$order->set_address( $address, 'shipping' );
			}
			$order->set_address( $address, 'billing' );
			$order->calculate_totals();
			return $order;
		}

		public function authenticate( $user ) {
			try {
				$this->perform_authentication();
			} catch ( \Exception $e ) {
				$user = new \WP_REST_Response($e->getMessage(), $e->getCode() ?? 401);
			}
	
			return $user;
		}

		private function is_consumer_secret_valid( $password, $user_password ) {
			return hash_equals( $password, $user_password );
		}

		/**
		 * Prevent unauthorized access here
		 *
		 * @return Bool
		 */
		public function perform_authentication() {

			return true;
			if ( !empty( $_SERVER['PHP_AUTH_USER'] ) ) {
				$auth_user = $_SERVER['PHP_AUTH_USER'];
			} else {
				throw new \Exception( __( 'Username is missing.', 'woocommerce' ), 400 );
			}
	
			if ( !empty( $_SERVER['PHP_AUTH_PW'] ) ) {
				$user_password = $_SERVER['PHP_AUTH_PW'];
			} else {
				throw new \Exception( __( 'Password is missing.', 'woocommerce' ), 400 );
			}

			$username = get_option('tpay_widget_api_user');
			$password = wc_api_hash(get_option('tpay_widget_api_pass'));

			if ( $username !== $auth_user ) {
				throw new \Exception( __( 'Username is invalid.', 'woocommerce' ), 401 );
			}
			if ( !$this->is_consumer_secret_valid( $password, $user_password ) ) {
				throw new \Exception( __( 'Password is invalid.', 'woocommerce' ), 401 );
			}
			
			return true;
		}

		public function connectPermissionCallback() {
			return true;
		}

		public function permissionCallback() {
			return true;
		}


		/**
		 * Processing of request takes place here
		 *
		 * @param Array
		 * @param Object
		 *
		 * @return Array
		 */
		public function prepare_items_for_response($items, $request) {
			$res = [];
			foreach ($items['items'] as $key => $value) {
				$data = $value->get_data();
				$image = wp_get_attachment_image_src($data['image_id']);
				$data['variations'] = !is_null($value->get_children()) ? count($value->get_children()) : 0;
				$data['link'] = get_post_permalink($value->id);
				$data['img_src'] = isset($image) && $image !== false ? $image[0] : '';
				array_push($res, $data);
			}
			$data = [
				'items' => $res,
				'meta' => $items['meta']
			];
			return $data;
		}

		/**
		 * Processing of request takes place here
		 *
		 * @param Array
		 * @param Object
		 *
		 * @return Array
		 */
		public function prepare_item_for_response($item, $request) {
			$res = [];
			$variations = $item->get_children();
			foreach ($variations as $key => $variation) {
				$singleItem = $item->get_data();
				$single_variation = new \WC_Product_Variation($variation);
				$singleItem['name'] = $single_variation->get_name();
				$singleItem['id'] = $single_variation->get_id();
				$singleItem['price'] = $single_variation->get_price();
				$singleItem['availability'] = $single_variation->get_availability()['class'] === 'in-stock' ? 1 : 0;
				array_push($res, $singleItem);
			}

			return $res;
		}
	}
}
