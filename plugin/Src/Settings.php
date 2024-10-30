<?php

namespace KODA\TPay_Plugin\Src;

use KODA\TPay_Plugin\Lib\KODA;
use KODA\TPay_Plugin\Lib\Table as Table;
use KODA\TPay_Plugin\Src\Db as Db;
use KODA\TPay_Plugin\Src\WooCommerceCategories as WooCategories;

if (!defined('ABSPATH')) exit;

/**
 * Backend settings page class, can have settings fields or data table
 *
 * @author     Nirjhar Lo
 * @package    wp-plugin-framework
 */
if (!class_exists('Settings')) {

	class Settings
	{


		/**
		 * @var String
		 */
		public $capability;


		/**
		 * @var Array
		 */
		public $menu_page;


		/**
		 * @var Array
		 */
		public $sub_menu_page;


		/**
		 * @var Array
		 */
		public $help;


		/**
		 * @var String
		 */
		public $screen;


		/**
		 * @var Object
		 */
		public $table;

		/**
		 * @var Object
		 */
		public $categories;
		
		public $step;


		/**
		 * Add basic actions for menu and settings
		 *
		 * @return Void
		 */
		public function __construct()
		{

			$this->capability = 'manage_options';
			$this->menu_page = array('name' => 'BotPay - Ustawienia', 'heading' => 'BotPay', 'slug' => 'tpay-menu');
			$this->screen = ''; 

			
			add_action('init', array($this, 'init_func'));
			add_action('admin_menu', array($this, 'menu_page'));
			add_action('admin_init', array($this, 'add_settings'));
			add_filter('set-screen-option', array($this, 'set_screen'), 10, 3);
			
			add_filter('sanitize_option_tpay_widget_settings_field_fb_page_id', array($this, 'sanitize_option_tpay_widget_settings_field_fb_page_id'));
			add_filter('sanitize_option_tpay_widget_settings_field_fb_page', array($this, 'sanitize_option_tpay_widget_settings_field_fb_page'));

			$error = get_option('error_exist');
			if (isset($error) && $error != '') {
				add_action('admin_notices', array($this, 'sample_admin_notice__error'));
			}

			add_action('added_option', function( $option_name, $value ) {
				$this->generate_basic_auth($option_name, null, $value);
		   	}, 10, 3);
			add_action('updated_option', function( $option_name, $old_value, $value ) {
				$this->generate_basic_auth($option_name, $old_value, $value);
		   	}, 10, 3);

			//    $options = array(
			// 	'_tpay_plugin_db_exist',
			// 	'tpay_widget_bot_id',
			// 	'settings_form',
			// 	'settings_api',
			// 	'tpay_widget_settings_field_fb_page',
			// 	'tpay_widget_settings_field_fb_page_id',
			// 	'tpay_widget_settings_field_fb_access_token',
			// 	'tpay_widget_settings_field_policy',
			// 	'tpay_widget_settings_field_categories',
			// 	'tpay_widget_settings_field_delivery',
			// 	'tpay_widget_settings_field_delivery_name',
			// 	'tpay_widget_settings_field_count_items',
			// 	'tpay_widget_settings_field_show_items',
			// 	'tpay_widget_settings_field_api_id',
			// 	'tpay_widget_settings_field_api_password',
			// 	'tpay_widget_settings_field_merchant_id',
			// 	'tpay_widget_settings_field_security_code',
			// 	'tpay_widget_settings_field_user_email',
			// 	'tpay_widget_settings_field_iban',
			// 	'tpay_widget_settings_field_contact_email',
			// 	'tpay_widget_api_token',

			// 	'tpay_widget_user_client_id',
			// 	'tpay_widget_user_client_secret',
			// 	'tpay_widget_api_user',
			// 	'tpay_widget_api_pass'
			// );
			// foreach ( $options as $value ) {
			// 	delete_option( $value );
			// }
		}

		function enqueue_facebook_javascript_sdk(): void
		{
			wp_enqueue_script('facebook-javascript-sdk-initializer', get_template_directory_uri() . '/path/to/your/initializer.js');
			wp_enqueue_script('facebook-javascript-sdk', 'https://connect.facebook.net/en_US/sdk.js', array(), null);
			return;
		}

		public function init_func()
		{
			$cats = get_terms(array('taxonomy' => 'product_cat', 'hide_empty' => 0, 'orderby' => 'ASC',  'parent' => 0));
			$this->categories = $cats;
		}

		public function randomPassword() {
			$alphabet = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'.'0123456789-=~!@#$%^&*()_+,./<>?;:[]{}\|';
			$pass = array();
			$alphaLength = strlen($alphabet) - 1;
			for ($i = 0; $i < 16; $i++) {
				$n = rand(0, $alphaLength);
				$pass[] = $alphabet[$n];
			}
			return implode($pass);
		}

		function sample_admin_notice__error() {
			$error = get_option('error_exist');
			$class = 'notice notice-error';
			$message = __( 'Wystąpił błąd. ' . $error, 'botpay' );
			printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), esc_html( $message ) ); 
			delete_option('error_exist');

		}

		function sanitize_option_tpay_widget_settings_field_fb_page_id($value) {
			return intval($value);
		}

		function sanitize_option_tpay_widget_settings_field_fb_page($value) {
			return wp_kses_data($value);
		}

		public function generate_basic_auth($option_name, $old_value, $new_value)
		{
			$BOT_SETTINGS_FIELDS = [
				'tpay_widget_settings_field_policy', 
				'tpay_widget_settings_field_delivery', 
				'tpay_widget_settings_field_delivery_name', 
				'tpay_widget_settings_field_iban', 
				'tpay_widget_settings_field_contact_email'
			];
			$botId = get_option('tpay_widget_bot_id');

            if (class_exists('KODA\\TPay_Plugin\\Lib\\KODA')) {
                $kodabotsApi = new KODA();
                if ($option_name === 'tpay_widget_settings_field_user_email') {
                    $clientId = get_option('tpay_widget_user_client_id');
                    if ($clientId === false || $clientId === '') {
                        $userMail = get_option('tpay_widget_settings_field_user_email');

                        $response = $kodabotsApi->createUser($userMail);
                        if (isset($response['client_id'])) {
                            update_option('tpay_widget_user_client_id', $response['client_id']);
                        }
                        if (isset($response['client_secret'])) {
                            update_option('tpay_widget_user_client_secret', $response['client_secret']);
                        }
						if (isset($response['error']) && $response['error'] == TRUE) {
							update_option('error_exist', 'Nie udało się stworzyć konta.');
							delete_option('tpay_widget_settings_field_user_email');
							return false;
						} 
						$password = $this->randomPassword();
						update_option('tpay_widget_api_user', $userMail);
						update_option('tpay_widget_api_pass', $password);

                    }
                }

				$data = [];
				foreach ($BOT_SETTINGS_FIELDS as $key => $value) {
					$val = get_option($value);
					array_push($data, $val);
				}
                if (array_search($option_name, $BOT_SETTINGS_FIELDS) !== false && array_search(false, $data) === false) {
					if ($botId === '' || $botId === null || $botId === false) {
						$result = $kodabotsApi->updateBot(true);
						if (isset($result['error']) && $result['error'] == true) {
							update_option('error_exist', 'Nie udało się stworzyć chatbota.');
							return;
						}
                        update_option('tpay_widget_bot_id', $result['bot_id']);
                    } else {
                        $result = $kodabotsApi->updateBot();
                    }
                }
            }
		}


		/**
		 * Add a sample main menu page callback
		 *
		 * @return Void
		 */
		public function menu_page()
		{
			if ($this->menu_page) {
				add_menu_page(
					$this->menu_page['name'],
					$this->menu_page['heading'],
					$this->capability,
					$this->menu_page['slug'],
					array($this, 'menu_page_callback')
				);
			}
		}


		/**
		 * Add a sample Submenu page callback
		 *
		 * @return Void
		 */
		public function sub_menu_page()
		{

			if ($this->sub_menu_page) {
				foreach ($this->sub_menu_page as $page) {

					$hook = add_submenu_page(
						$page['parent_slug'],
						$page['name'],
						$page['heading'],
						$this->capability,
						$page['slug'],
						array($this, 'menu_page_callback')
					);
					if ($page['help']) {
						add_action('load-' . $hook, array($this, 'help_tabs'));
					}
					if ($page['screen']) {
						add_action('load-' . $hook, array($this, 'screen_option'));
					}
				}
			}
		}


		/**
		 * Set screen
		 *
		 * @param String $status
		 * @param String $option
		 * @param String $value
		 *
		 * @return String
		 */
		public function set_screen($status, $option, $value)
		{

			$user = get_current_user_id();

			switch ($option) {
				case 'option_name_per_page':
					update_user_meta($user, 'option_name_per_page', $value);
					$output = $value;
					break;
			}

			if ($output) return $output; // Related to PLUGIN_TABLE()
		}


		/**
		 * Set screen option for Items table
		 *
		 * @return Void
		 */
		public function screen_option()
		{

			$option = 'per_page';
			$args   = array(
				'label'   => __('Show per page', 'botpay'),
				'default' => 10,
				'option'  => 'option_name_per_page' // Related to PLUGIN_TABLE()
			);
			add_screen_option($option, $args);
			$this->table = new Table(); // Source /lib/table.php
		}


		/**
		 * Menu page callback
		 *
		 * @return Html
		 */
		public function menu_page_callback()
		{ 
			if (isset($_GET['page_id']) && isset($_GET['page_name'])) {
				$sanitized_page_id = sanitize_option('tpay_widget_settings_field_fb_page_id', $_GET['page_id']);
				$sanitized_page_name = sanitize_option('tpay_widget_settings_field_fb_page', $_GET['page_name']);
				update_option('tpay_widget_settings_field_fb_page_id', $sanitized_page_id);
				update_option('tpay_widget_settings_field_fb_page', $sanitized_page_name);
				wp_redirect(get_admin_url() . '?page=tpay-menu');
				exit;
			}
			
			?>

			<div class="wrap">
				<h1><?php echo get_admin_page_title(); ?></h1>
				<br class="clear">
				<?php 
				settings_errors();
				/**
				 * Following is the settings form
				 */ ?>
				<form method="post" action="options.php">
					<?php
					settings_fields("settings_form");
					do_settings_sections("settings_name");
					submit_button(__('Zapisz ustawienia', 'botpay'), 'primary', 'id'); ?>
				</form>


				<?php
				/**
				 * Following is the data table class
				 */ ?>

				<br class="clear">
			</div>
		<?php
		}


		/**
		 * Add help tabs using help data
		 *
		 * @return Void
		 */
		public function help_tabs()
		{

			foreach ($this->helpData as $value) {
				if ($_GET['page'] == $value['slug']) {
					$this->screen = get_current_screen();
					foreach ($value['info'] as $key) {
						$this->screen->add_help_tab($key);
					}
					$this->screen->set_help_sidebar($value['link']);
				}
			}
		}

		public function getStep($apiUser, $policy, $fbPage, $botId) {

			if (!$apiUser || $apiUser === '') {
				return [
					'index' => 1,
					'title' => __('Krok 1 - Konto', 'botpay'),
					'desc' => __('Na początek podaj swój adres e-mail', 'botpay')
				];
			}
			
			if (($apiUser && $apiUser !== '') && (!$botId || $botId === '')) {
				return [
					'index' => 2,
					'title' => __('Krok 2 - konfiguracja chatbota BotPay', 'botpay'),
					'desc' => __('Teraz podaj informacje, które będą pojawiały się w kolejnych krokach procesu zakupowego.', 'botpay')
				];
			}

			if (($apiUser && $apiUser !== '') && ($policy && $policy !== '') && ($botId && $botId !== '') &&  (!$fbPage || $fbPage === '')) {
				return [
					'index' => 3,
					'title' => __('Połącz wtyczkę Botpay ze stroną Twojego sklepu na facebooku', 'botpay'),
					'desc' => __('Żeby Twoi klienci mogli łatwo robić zakupy w Messengerze, połącz Botpay ze stroną swojego sklepu na Facebooku.
					<br>
					Kliknij w poniższy link, zaloguj się do swojego profilu w serwisie facebook.com i wybierz stronę, z która ma być powiązany Twój chatbot.', 'botpay')
				];
			}

			return [
				'index' => 4,
				'title' => __('Ustawienia - Chatbot', 'botpay'),
				'desc' => __('', 'botpay')
			];
		}


		/**
		 * Add different types of settings and corrosponding sections
		 *
		 * @return Void
		 */
		public function add_settings()
		{
			if (false == get_option('settings_form')) {
				add_option('settings_form');
			}
			if (false == get_option('settings_api')) {
				add_option('settings_api');
			}

			/* 
				Settings INFO
			*/
			add_settings_section('settings_info', __('Konfiguracja pluginu i aktywacja płatności TPay w Facebook Messenger', 'botpay'), array($this, 'section_info_cb'), 'settings_name');

			$apiUser = get_option('tpay_widget_api_user');
			$fb_page = get_option('tpay_widget_settings_field_fb_page');
			$policy = get_option('tpay_widget_settings_field_policy');
			$botId = get_option('tpay_widget_bot_id');
			/* 
				Settings FORM
			*/
			$this->step = $this->getStep($apiUser, $policy, $fb_page, $botId);
			add_settings_section('settings_form', $this->step['title'], function() {
					echo '<p>'.__($this->step['desc']).'</p>';
			}, 'settings_name');

			if ($this->step['index'] === 3 || $this->step['index'] === 4) {
				register_setting('settings_form', 'tpay_widget_settings_field_fb_page');
				add_settings_field(
					'tpay_widget_settings_field_fb_page',
					__('', 'botpay'),
					array($this, 'settings_input_fb_page_field_cb'),
					'settings_name',
					'settings_form'
				);
			}
			
            if ($this->step['index'] === 1 || $this->step['index'] === 4) {
                register_setting('settings_form', 'tpay_widget_settings_field_user_email');
                add_settings_field(
                    'tpay_widget_settings_field_user_email',
                    __('Adres email użytkownika', 'botpay'),
                    array($this, 'settings_input_user_email_field_cb'),
                    'settings_name',
                    'settings_form'
                );
            }
			
            if ($this->step['index'] !== 1 && $this->step['index'] !== 3) {

				register_setting('settings_form', 'tpay_widget_settings_field_categories');
                add_settings_field(
                    'tpay_widget_settings_field_categories',
                    __('Kategoria produktowa', 'botpay'),
                    array($this, 'settings_input_categories_field_cb'),
                    'settings_name',
                    'settings_form'
                );

				register_setting('settings_form', 'tpay_widget_settings_field_count_items');
				add_settings_field(
					'tpay_widget_settings_field_count_items',
					__('Liczba wyświetlonych produktów', 'botpay'),
					array($this, 'settings_input_count_items_field_cb'),
					'settings_name',
					'settings_form'
				);

				register_setting('settings_form', 'tpay_widget_settings_field_show_items');
				add_settings_field(
					'tpay_widget_settings_field_show_items',
					__('Kolejność wyświetlanych produktów', 'botpay'),
					array($this, 'settings_input_show_items_field_cb'),
					'settings_name',
					'settings_form'
				);

				register_setting('settings_form', 'tpay_widget_settings_field_delivery_name');
                add_settings_field(
                    'tpay_widget_settings_field_delivery_name',
                    __('Metoda dostawy w WooCommerce', 'botpay'),
                    array($this, 'settings_input_delivery_name_field_cb'),
                    'settings_name',
                    'settings_form'
                );

				register_setting('settings_form', 'tpay_widget_settings_field_delivery');
                add_settings_field(
                    'tpay_widget_settings_field_delivery',
                    __('Sposób dostawy', 'botpay'),
                    array($this, 'settings_input_delivery_field_cb'),
                    'settings_name',
                    'settings_form'
                );

                register_setting('settings_form', 'tpay_widget_settings_field_iban');
                add_settings_field(
                    'tpay_widget_settings_field_iban',
                    __('Numer konta', 'botpay'),
                    array($this, 'settings_input_iban_field_cb'),
                    'settings_name',
                    'settings_form'
                );
            
                register_setting('settings_form', 'tpay_widget_settings_field_contact_email');
                add_settings_field(
                    'tpay_widget_settings_field_contact_email',
                    __('E-mail do kontaktu', 'botpay'),
                    array($this, 'settings_input_contact_field_cb'),
                    'settings_name',
                    'settings_form'
                );

                register_setting('settings_form', 'tpay_widget_settings_field_policy');
                add_settings_field(
                    'tpay_widget_settings_field_policy',
                    __('Regulamin sklepu', 'botpay'),
                    array($this, 'settings_input_policy_field_cb'),
                    'settings_name',
                    'settings_form'
                );

				register_setting('settings_form', 'tpay_widget_settings_field');
				add_settings_field(
					'tpay_widget_settings_field',
					__('', 'botpay'),
					function() {
						echo '<h4>Teraz połącz chatbota ze swoją bramką płatności Tpay. Dane swojego konta zajdziesz <a target="_blank" href="https://register.tpay.com/">tutaj</></h4>';
					},
					'settings_name',
					'settings_form'
				);

				register_setting('settings_form', 'tpay_widget_settings_field_api_id');
				add_settings_field(
					'tpay_widget_settings_field_api_id',
					__('Twoje API ID:', 'botpay'),
					array($this, 'settings_input_field_api_id'),
					'settings_name',
					'settings_form'
				);

				register_setting('settings_form', 'tpay_widget_settings_field_api_password');
				add_settings_field(
					'tpay_widget_settings_field_api_password',
					__('Hasło do API:', 'botpay'),
					array($this, 'settings_input_field_api_password'),
					'settings_name',
					'settings_form'
				);

				register_setting('settings_form', 'tpay_widget_settings_field_merchant_id');
				add_settings_field(
					'tpay_widget_settings_field_merchant_id',
					__('Merchant ID:', 'botpay'),
					array($this, 'settings_input_tpay_merchant_id'),
					'settings_name',
					'settings_form'
				);

				register_setting('settings_form', 'tpay_widget_settings_field_security_code');
				add_settings_field(
					'tpay_widget_settings_field_security_code',
					__('Security Code:', 'botpay'),
					array($this, 'settings_input_tpay_security_code'),
					'settings_name',
					'settings_form'
				);
            }
		}

		public function section_info_cb()
		{
			if ($this->step['index'] === 1 || $this->step['index'] === 2 || $this->step['index'] === 3) { 
				echo '<p class="description">' . esc_html__('W celu aktywacji kanału płatności na Twoim kanale Facebook Messenger, podaj poniej wymagane dane w 3 krokach.', 'botpay') . '</p>';
			} else {
				echo '<p class="description">' . esc_html__('Gratulacje! Na podstawie podanych przez Ciebie informacji wygenerowaliśmy chatbota, który pozwoli Twoim klientom łatwo i szybko kupować w Messengerze.', 'botpay').'</p>';
				echo '<p class="description" style="margin: 20px 0">' .esc_html__('Kliknij') .' <a href="https://www.messenger.com/t/'.esc_html(get_option('tpay_widget_settings_field_fb_page_id')).'">tutaj</a> '.esc_html__('i zobacz jak wygląda Twój chatbot.');
				echo '<p class="description">' .esc_html__('Masz pytania lub uwagi? Skontaktuj się z nami pod adresem botpay@kodabots.com');
				echo '<p class="description" style="margin: 20px 0">' .esc_html__('Jeśli chcesz zmienić ustawienia swojego chatbota – możesz to zrobić poniżej.');
			}
			
		}

		public function settings_input_fb_page_field_cb()
		{
			$fb_page = get_option('tpay_widget_settings_field_fb_page');
			echo '<div>';
			if ($this->step['index'] === 4) {
				echo '<input type="text" class="medium-text" readonly name="tpay_widget_settings_field_fb_page" id="tpay_widget_settings_field_fb_page" value="' . esc_html($fb_page) . '" placeholder="' . __('', 'botpay') . '"  />';
				echo '<a style="margin-left:10px" href="'.esc_url(KODA_WEBVIEW_URL).'/?callback_url='.get_admin_url().'&access_token='.esc_html(get_option('tpay_widget_api_token')).'&connected_page_id='.esc_html($fb_page).'">Rozłącz</a>';
				echo '<a style="margin-left:10px" href="https://www.messenger.com/t/'.get_option('tpay_widget_settings_field_fb_page_id').'" target="_blank">Pokaż</a>';
			} elseif($this->step['index'] === 3) {
				echo '<a style="" href="'.esc_url(KODA_WEBVIEW_URL).'/?callback_url='.get_admin_url().'&access_token='.esc_html(get_option('tpay_widget_api_token')).'">Połącz Botpay ze stroną Twojego sklepu</a>';
			}
			echo '</div>';
		}

		public function settings_input_user_email_field_cb()
		{
			if ($this->step['index'] === 4) {
				echo '<div>
					<input type="email" readonly class="medium-text" name="tpay_widget_settings_field_user_email" id="tpay_widget_settings_field_user_email" value="' . esc_html(get_option('tpay_widget_settings_field_user_email')) . '" placeholder="' . esc_html__('', 'botpay') . '"  />
				</div>';
			} else {
                echo '<div>
					<input type="email" required class="medium-text" name="tpay_widget_settings_field_user_email" id="tpay_widget_settings_field_user_email" value="' . esc_html(get_option('tpay_widget_settings_field_user_email')) . '" placeholder="' . esc_html__('', 'botpay') . '"  />
				</div>';
                echo '<span>Użyjemy Twojego adresu do stworzenia konta w systemie KODA oraz bieżącej komunikacji związanej z działaniem wtyczki</span>';
            }
		}

		public function settings_input_iban_field_cb()
		{
			echo '<div>
				<input type="text" required class="medium-text" name="tpay_widget_settings_field_iban" id="tpay_widget_settings_field_iban" value="' . esc_html(get_option('tpay_widget_settings_field_iban')) . '" placeholder="' . esc_html__('', 'botpay') . '"  />
			</div>';
			echo '<p style="padding-top:2px"><i>Podaj numer konta swojego sklepu dla klientów, którzy nie wybiorą płatności BLIK</i></p>';
		}

		public function settings_input_contact_field_cb()
		{
			echo '<div>
				<input type="email" required class="medium-text" name="tpay_widget_settings_field_contact_email" id="tpay_widget_settings_field_contact_email" value="' . esc_html(get_option('tpay_widget_settings_field_contact_email')) . '" placeholder="' . esc_html__('', 'botpay') . '"  />
			</div>';
			echo '<p style="padding-top:2px"><i>Wpisz adres e-mail, który chatbot wyświetli klientom oczekującym kontaktu z Twoim sklepem.</i></p>';
			
		}
		
		public function settings_input_policy_field_cb()
		{
			echo '<div>
			<input type="url" pattern="https?://.+" title="Adres musi zaczynać się od https://" required class="medium-text" name="tpay_widget_settings_field_policy" id="tpay_widget_settings_field_policy" value="' . esc_html(get_option('tpay_widget_settings_field_policy')) . '" placeholder="' . esc_html__('', 'botpay') . '"  />
			</div>';
			echo '<p style="padding-top:2px"><i>Podaj link do regulaminu swojego sklepu, który musi zaakceptować każdy z Twoich klientów</i></p>';
		}

		public function settings_input_categories_field_cb()
		{
			$options = '';
			foreach ($this->categories as $key => $value) {
				$options = $options . '<option value="' . esc_html($value->slug) . '" ' . selected(esc_html($value->slug), esc_html(get_option('tpay_widget_settings_field_categories')), false) . '>' . esc_html($value->name) . '</option>';
			}
			echo '<select name="tpay_widget_settings_field_categories" id="tpay_widget_settings_field_categories">
			' . $options . '	
			</select>';
			echo '<p style="padding-top:2px"><i>Wybierz kategorię produktów, które zostaną wyświetlone w Twoim chatbocie</i></p>';
		}

		public function settings_input_delivery_field_cb()
		{
			echo '<select name="tpay_widget_settings_field_delivery" id="tpay_widget_settings_field_delivery">
			<option value="courier" ' . selected('courier', esc_html(get_option('tpay_widget_settings_field_delivery')), false) . '>Kurier</option>
			<option value="inpost-paczkomaty" ' . selected('inpost-paczkomaty', esc_html(get_option('tpay_widget_settings_field_delivery')), false) . '>Paczkomat</option>
			</select>';
			echo '<p style="padding-top:2px"><i>Wybierz sposób dostawy, który będzie obsługiwany przez chatbota</i></p>';

		}
		public function settings_input_delivery_name_field_cb()
		{
			$zones = \WC_Shipping_Zones::get_zones();
			$html = '';

			//$key.":".$single_zone->get_instance_id()
			foreach ($zones as $key => $zone) {
                foreach ($zone['shipping_methods'] as $key => $single_zone) {
					$cost = $single_zone->cost ?? 0;
					$html = $html . '
					<div style="margin:5px 0">
					<input type="radio" id="tpay_widget_settings_field_delivery_name'.$single_zone->get_instance_id().'" name="tpay_widget_settings_field_delivery_name" value="'.$single_zone->id.'::'.$single_zone->get_instance_id()."::".$cost.'" ' . checked($single_zone->id.'::'.$single_zone->get_instance_id()."::".$cost, get_option('tpay_widget_settings_field_delivery_name'), false) . '>
					<label for="tpay_widget_settings_field_delivery_name'.$single_zone->get_instance_id().'">'.$single_zone->get_title().'</label>
					<div>
					';
                }
			}

			echo $html;
			echo '<p style="padding-top:2px"><i>Wybierz metodę dostawy, która będzie przypisana do zamówień w Twoim sklepie. Jej koszt zostanie doliczony do zamówienia.</i></p>';

		}

		public function settings_input_count_items_field_cb()
		{
			echo '<div>
				<input type="number" required class="medium-text" defaut="10" min="1" name="tpay_widget_settings_field_count_items" id="tpay_widget_settings_field_count_items" value="' . esc_html(get_option('tpay_widget_settings_field_count_items')) . '" placeholder="' . esc_html__('', 'botpay') . '"  />
			</div>';
			echo '<p style="padding-top:2px"><i>Wpisz ile produktów z wybranej kategorii chcesz pokazać</i></p>';

		}
		
		public function settings_input_show_items_field_cb()
		{
			echo '<div>
			<select name="tpay_widget_settings_field_show_items" id="tpay_widget_settings_field_show_items">
				<option value="random" ' . selected('random', esc_html(get_option('tpay_widget_settings_field_show_items')), false) . '>Losowy</option>
				<option value="last" ' . selected('last', esc_html(get_option('tpay_widget_settings_field_show_items')), false) . '>Ostatnio dodane</option>
			</select>
			</div>';
			echo '<p style="padding-top:2px"><i>Zdecyduj w jakiej kolejności chatbot wyświetli produkty z wybranej przez Ciebie kategorii</i></p>';
		}

		public function settings_input_field_api_id()
		{
			echo '
			<div>
				<input type="text" required class="medium-text" name="tpay_widget_settings_field_api_id" id="tpay_widget_settings_field_api_id" value="' . esc_html(get_option('tpay_widget_settings_field_api_id')) . '" placeholder="' . esc_html__('', 'botpay') . '"  />
			</div>';
			echo '<p style="padding-top:2px"><i>Podaj klucz do API</i></p>';
		}

		public function settings_input_field_api_password()
		{
			echo '<div>
				<input type="password" required class="medium-text" name="tpay_widget_settings_field_api_password" id="tpay_widget_settings_field_api_password" value="' . esc_html(get_option('tpay_widget_settings_field_api_password')) . '" placeholder="' . esc_html__('', 'botpay') . '"  />
			</div>';
			echo '<p style="padding-top:2px"><i>Podaj swoje hasło</i></p>';

		}

		public function settings_input_tpay_merchant_id()
		{
			echo '<div>
				<input type="text" autocomplete="off" required class="medium-text" name="tpay_widget_settings_field_merchant_id" id="tpay_widget_settings_field_merchant_id" value="' . esc_html(get_option('tpay_widget_settings_field_merchant_id')) . '" placeholder="' . esc_html__('', 'botpay') . '"  />
			</div>';
			echo '<p style="padding-top:2px"><i>Podaj identyfikator Twojego sklepu</i></p>';

		}

		public function settings_input_tpay_security_code()
		{
			echo '<div>
				<input type="text" autocomplete="off" required class="medium-text" name="tpay_widget_settings_field_security_code" id="tpay_widget_settings_field_security_code" value="' . esc_html(get_option('tpay_widget_settings_field_security_code')) . '" placeholder="' . esc_html__('', 'botpay') . '"  />
			</div>';
			echo '<p style="padding-top:2px"><i>Podaj kod zabezpieczający</i></p>';

		}

		public function settings_field_cb()
		{
			echo '<select name="settings_field_name" id="settings_field_name"><option value="value" ' . selected('value', esc_html(get_option('settings_field_name')), false) . '>Value</option></select>';
		}

		public function sendKODACallback($status, $orderId)
		{
			$type = '';
			$apiStatus = '';
			// Enum: "shipping" "order" "payment"
			// Enum: "failed" "success"
			switch ($status) {
				case 'pending':
					$type = 'payment';
					$apiStatus = '';
					break;
				case 'failed':
					$type = 'payment';
					$apiStatus = 'failed';
					break;
				case 'hold':
					// $type = 'order';
					// $apiStatus = 'failed';
					break;
				case 'processing':
					# code...
					break;
				case 'completed':
					# code...
					break;
				case 'refunded':
					# code...
					break;
				case 'cancelled':
					# code...
					break;
				default:
					# code...
					break;
			}

			$kodabotsApi = new KODA();
			$order = wc_get_order($orderId);
			$platformUserId = $order->get_meta('platform_user_id');
			$kodabotsApi->paymentCallback([
				"type" => $type ?? "payment",
				"status" => $apiStatus ?? "success",
				"timestamp" => time(),
				"payload" => [],
				"platform_user_id" => $platformUserId
			]);
		}

	}
} ?>