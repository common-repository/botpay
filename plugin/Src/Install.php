<?php
namespace KODA\TPay_Plugin\Src;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Implimentation of WordPress inbuilt functions for plugin activation.
 *
 * @author     Nirjhar Lo
 * @package    wp-plugin-framework
 */
if ( ! class_exists( 'Install' ) ) {

	final class Install {


		/**
		 * @var String
		 */
		public $text_domain;


		/**
		 * @var String
		 */
		public $php_ver_allowed;


		/**
		 * @var Array
		 */
		public $plugin_page_links;


		/**
		 * Execute plugin setup
		 *
		 * @return Void
		 */
		public function execute() {

			add_action( 'plugins_loaded', array( $this, 'text_domain_cb' ) );
			add_action( 'admin_notices', array( $this, 'php_ver_incompatible' ) );
			add_filter( 'plugin_action_links', array( $this, 'menu_page_link' ), 10, 2 );
		}


		/**
		 * Load plugin textdomain
		 *
		 * @return Void
		 */
		public function text_domain_cb() {

			$locale = is_admin() && function_exists('get_user_locale') ? get_user_locale() : get_locale();
			$locale = apply_filters('plugin_locale', $locale, $this->text_domain);

			unload_textdomain($this->text_domain);
			load_textdomain($this->text_domain, BOTPAY_PLUGIN_TRANSLATE . 'textdomain-' . $locale . '.mo');
			load_plugin_textdomain( $this->text_domain, false, BOTPAY_PLUGIN_TRANSLATE );
		}


		/**
		 * Define low php verson errors
		 *
		 * @return String
		 */
		public function php_ver_incompatible() {

			if ( version_compare( phpversion(), $this->php_ver_allowed, '<' ) ) :
				$text = __( 'The Plugin can\'t be activated because your PHP version', 'textdomain' );
				$text_last = __( 'is less than required '.$this->php_ver_allowed.'. See more information', 'textdomain' );
				$text_link = 'php.net/eol.php'; ?>

				<div id="message" class="updated notice notice-success is-dismissible">
					<p><?php echo esc_attr($text . ' ' . phpversion() . ' ' . $text_last . ': '); ?>
						<a href="http://php.net/eol.php/" target="_blank"><?php echo esc_attr($text_link); ?></a>
					</p>
				</div>

			<?php endif; return;
		}


		/**
		 * Add settings link to plugin page
		 *
		 * @param Array $links
		 * @param String $file
		 *
		 * @return Array
		 */
		public function menu_page_link( $links, $file ) {

			if ($this->plugin_page_links) {
				static $this_plugin;
				if ( ! $this_plugin ) {
					$this_plugin = BOTPAY_PLUGIN_FILE;
				}
				if ( $file == $this_plugin ) {
					$shift_link = array();
					foreach ($this->plugin_page_links as $value) {
						$shift_link[] = '<a href="'.$value['slug'].'">'.$value['label'].'</a>';
					}
					foreach( $shift_link as $val ) {
						array_unshift( $links, $val );
					}
				}
				return $links;
			}
		}
	}
} ?>
