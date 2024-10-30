<?php
/*
	Plugin Name: 	BotPay
	Plugin URI: 	https://botpay.org/
	Description: 	Quick and fast chatbot for Messenger with TPay and Woocommerce integration
	Version: 		1.0.3
	Author: 		KODA
	Author URI: 	https://kodabots.com/
	License: 		GPLv2 or later
	License URI: 	https://www.gnu.org/licenses/gpl-2.0.html
	Text Domain: 	botpay
	Domain Path: 	/languages
*/

if ( !defined( 'ABSPATH' ) ) exit;


//Define basic names
//Edit the "_PLUGIN" in following namespaces for compatibility with your desired name.
defined( 'BOTPAY_PLUGIN_DEBUG' ) or define( 'BOTPAY_PLUGIN_DEBUG', true );

defined( 'BOTPAY_PLUGIN_PATH' ) or define( 'BOTPAY_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
defined( 'BOTPAY_PLUGIN_FILE' ) or define( 'BOTPAY_PLUGIN_FILE', plugin_basename( __FILE__ ) );

defined( 'BOTPAY_PLUGIN_TRANSLATE' ) or define( 'BOTPAY_PLUGIN_TRANSLATE', dirname(plugin_basename(__FILE__)).'/languages/');

defined( 'BOTPAY_PLUGIN_JS' ) or define( 'BOTPAY_PLUGIN_JS', plugins_url( '/asset/js/', __FILE__  ) );
defined( 'BOTPAY_PLUGIN_CSS' ) or define( 'BOTPAY_PLUGIN_CSS', plugins_url( '/asset/css/', __FILE__ ) );
defined( 'BOTPAY_PLUGIN_IMAGE' ) or define( 'BOTPAY_PLUGIN_IMAGE', plugins_url( '/asset/img/', __FILE__ ) );

define( 'KODA_API_SCOPES', ['shop:bot:create','shop:bot:update','shop:bot:connect:fb','shop:bot:disconnect:fb','shop:callback'] );
define( 'KODA_API_TOKEN', 'eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9.eyJhdWQiOiI1MTAiLCJqdGkiOiJhMmY4ZGVmZDMxMDkwODg5NDQzYzA2YzNkM2Y4ZDgyMTg3MzJiY2FhNWViNTc5Mjk5ZDAxNjg5MzViODZmMzJmMmQ3ZGVmMmIzZWMxOTA1MyIsImlhdCI6MTYyOTE4NzA1NywibmJmIjoxNjI5MTg3MDU3LCJleHAiOjE3ODY5NTM0NTcsInN1YiI6IiIsInNjb3BlcyI6WyJpbmJveC1yZWFkIl19.AjZhfjmOpM16M65rWlHwMeZyJz_byLFu46Q7ZIqB4i-CAqE2LqFRsUXgs7oKYkOS7MSkjlQagU4lhxzqeLSd8XANUMRggeOAoX8hGIM4Tc8zPlz6m8Bzqv0uf3zAduJ126CeVYd3jCG4f0EpWWLjwsco_-_ARW_SqWf-oQxi68ySuMwezx6CIhzzDIu_J8RaKCw5h5rYdNBwz7MDkoSpAcuRVI-ljXCi9bkbh9zyidKBhaOVhVOdAdDG3tofQtY-26FroZ9EcjZtmB5ZO8OTj9T9jPicWejuL-qlAZyDjVG01NyAILrxhEIC1wfOJab3uD_ycZpR91eXDqwMI1dyKCLfllm5PELpNQU70eUZz6FJfXgeCbq4ZikiEat5_h4vZRNt400WwqYRikltu_Y4f_GlG-Jp164l_yNpxBZhUCMAAgXTxd8jpz1J0xiQeulLzMWAYYjJakwXhwzKq1__Dr6bQ0gDfmPL0LBizB4S-2XDx2PJfSicC79179-Nz8KZS9HCbWtcLyxr0Fpg6AZXZ_k--4Qe_O4h-Tx6Yg3z8fbHgJmTg9-fOsukhuRJXEql1IVEu_V_fTbKxPmIDSjmd-NOd0tPss0HCWBrZKSPNXlzYlHYZJVyeQJC5iRUs6QoQQEs4y-6JUH1AhAwO0LMKGPRNvLTll2UyKJupDXzTh4' );
define( 'KODA_API_HOST', 'https://kodabots-api-europe.ey.r.appspot.com' );
define( 'KODA_WEBVIEW_URL', 'https://fb-connect.botpay.org' );

//The Plugin
require_once( 'vendor/autoload.php' );
function botpay_plugin() {
	if ( class_exists( 'KODA\\TPay_Plugin\\PluginLoader' ) ) {
		return KODA\TPay_Plugin\PluginLoader::instance();
	}
}

global $plugin;
$plugin = botpay_plugin(); 


?>
