<?php
namespace KODA\TPay_Plugin\Src;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * DB installation class
 *
 * @author     Nirjhar Lo
 * @package    wp-plugin-framework
 */
if ( ! class_exists( 'Db' ) ) {

	class Db {

		/**
		 * @var String
		 */
		public $table;


		/**
		 * @var String
		 */
		public $sql;


		/**
		 * Instantiate the db class
		 *
		 * @return Void
		 */
		public function __construct() {
			$this->up_path = ABSPATH . 'wp-admin/includes/upgrade.php';
		}


		/**
		 * Define the necessary database tables
		 *
		 * @return Void
		 */
		public function build() {

			global $wpdb;
			$wpdb->hide_errors();
			$this->table_name = $wpdb->prefix . $this->table;
			update_option( '_tpay_tpay_plugin_db_exist', 0 );
			if ( $wpdb->get_var( "SHOW TABLES LIKE '$this->table_name'" ) != $this->table_name ) {
				$execute_sql = $this->execute( $this->table_name, $this->collate(), $this->sql );
				dbDelta( $execute_sql );
			}
		}


		/**
		 * Define the variables for db table creation
		 *
		 * @return String
		 */
		public function collate() {

			global $wpdb;
			$wpdb->hide_errors();
			$collate = "";
		    if ( $wpdb->has_cap( 'collation' ) ) {
				if( ! empty($wpdb->charset ) )
					$collate .= "DEFAULT CHARACTER SET $wpdb->charset";
				if( ! empty($wpdb->collate ) )
					$collate .= " COLLATE $wpdb->collate";
		    }
    		require_once( $this->up_path );
			return $collate;
		}


		/**
		 * SQL query to create the main plugin table.
		 *
		 * @param String $table_name
		 * @param String $collate
		 * @param String $sql
		 *
		 * @return String
		 */
		public function execute( $table_name, $collate, $sql ) {

			return "CREATE TABLE $table_name ( $sql ) $collate;";
		}


		/**
		 * Check options and tables and output the info to check if db install is successful
		 *
		 * @return String
		 */
		public function __destruct() {

			global $wpdb;

			$this->table_name = $wpdb->prefix . $this->table;
			if ( $wpdb->get_var( "SHOW TABLES LIKE '$this->table_name'" ) == $this->table_name ) {

				update_option( '_tpay_plugin_db_exist', 1 );
			}
		}

		public function get_basic_auth_credentials($username) {
			global $wpdb;
			$this->table_name = $wpdb->prefix . $this->table;
			$authData = $wpdb->get_row( $wpdb->prepare( "
				SELECT *
				FROM {$this->table_name}
				WHERE password = '%s'
			", $username ), ARRAY_A );
			return $authData;
		}

		public function save_basic_auth_credentials() {
			global $wpdb;
			$this->table_name = $wpdb->prefix . $this->table;

			$ret = $wpdb->insert(
				$this->table_name,
				array(
					'username' => wc_rand_hash(),
					'password' => wc_api_hash( wc_rand_hash() )
				),
				array(
					'%s',
					'%s',
					)
				);


		}
	}
} ?>
