<?php
/**
 * Plugin Name:       Premium Analytics Dashboard Pro
 * Plugin URI:        https://k12onlineschools.com/premium-analytics-dashboard-pro
 * Description:       Enterprise-grade, standalone WordPress analytics dashboard. Tracks visitors, captures Contact Form 7 leads dynamically, and renders a premium admin dashboard with charts, reports and lead management — no external SaaS, no Node.js, no React.
 * Version:           1.2.0
 * Requires at least: 6.0
 * Requires PHP:      8.1
 * Author:            Premium Analytics Dashboard Pro Team
 * License:            GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       premium-analytics-dashboard-pro
 * Domain Path:       /languages
 * Network:           true
 *
 * @package Premium_Analytics_Dashboard_Pro
 */

// Agar koi is file ko directly access kare to rok do.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Plugin ke core constants — har jagah in hi ko use karenge, kahin bhi
 * hardcoded path ya version number nahi likhenge.
 */
define( 'PAD_VERSION', '1.2.0' );
define( 'PAD_DB_VERSION', '1.1.0' );
define( 'PAD_PLUGIN_FILE', __FILE__ );
define( 'PAD_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
define( 'PAD_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'PAD_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'PAD_TEXT_DOMAIN', 'premium-analytics-dashboard-pro' );

/**
 * Lightweight PSR-4 style autoloader.
 * Node.js build step ki koi zaroorat nahi — sirf class naming convention
 * follow karke file dhoondte hain.
 */
require_once PAD_PLUGIN_DIR . 'includes/class-pad-autoloader.php';
PAD_Autoloader::register();

/**
 * Activation aur Deactivation hooks. Yeh functions plugin load hote hi
 * register karne padte hain (class methods ko static call karke).
 */
register_activation_hook( PAD_PLUGIN_FILE, array( 'PAD_Activator', 'activate' ) );
register_deactivation_hook( PAD_PLUGIN_FILE, array( 'PAD_Deactivator', 'deactivate' ) );

/**
 * Multisite: agar network me naya site banaya jaaye to us site ke liye
 * bhi humari tables create honi chahiye.
 */
add_action( 'wp_initialize_site', array( 'PAD_Activator', 'activate_new_site' ), 10, 1 );

/**
 * Plugin ko bootstrap (start) karta hai. Sab kuch WordPress ke andar hi
 * chalta hai — koi bahar ka SaaS call nahi hota.
 *
 * @return void
 */
function pad_run_plugin() {
	$plugin = new PAD_Plugin();
	$plugin->run();
}
add_action( 'plugins_loaded', 'pad_run_plugin' );
