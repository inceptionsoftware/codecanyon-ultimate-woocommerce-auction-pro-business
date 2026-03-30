<?php
/**
 * Plugin Name: UWA License Server
 * Plugin URI:  https://codecanyon.auctionplugin.net
 * Description: License management server for Ultimate WooCommerce Auction Pro (CodeCanyon).
 * Version:     1.0.0
 * Author:      Nitesh Singh
 * Text Domain: uls
 * Domain Path: /languages
 *
 * @package UWA_License_Server
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'ULS_VERSION',   '1.0.0' );
define( 'ULS_FILE',      __FILE__ );
define( 'ULS_DIR',       plugin_dir_path( __FILE__ ) );
define( 'ULS_URL',       plugin_dir_url( __FILE__ ) );
define( 'ULS_ADMIN_DIR', ULS_DIR . 'admin/' );
define( 'ULS_INC_DIR',   ULS_DIR . 'includes/' );
define( 'ULS_ITEM_ID',   '12345678' ); // Replace with your actual CodeCanyon item ID

require_once ULS_INC_DIR . 'class-uls-database.php';
require_once ULS_INC_DIR . 'class-uls-security.php';
require_once ULS_INC_DIR . 'class-uls-envato.php';
require_once ULS_INC_DIR . 'class-uls-validator.php';
require_once ULS_INC_DIR . 'class-uls-activator.php';
require_once ULS_INC_DIR . 'class-uls-api.php';
require_once ULS_ADMIN_DIR . 'class-uls-admin.php';

register_activation_hook( __FILE__, array( 'ULS_Database', 'create_tables' ) );
register_deactivation_hook( __FILE__, array( 'ULS_Database', 'on_deactivation' ) );

/**
 * Initialise the plugin.
 */
function uls_init() {
	// Load text domain.
	load_plugin_textdomain( 'uls', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );

	ULS_API::instance()->init();

	if ( is_admin() ) {
		ULS_Admin::instance()->init();
	}
}
add_action( 'plugins_loaded', 'uls_init' );
