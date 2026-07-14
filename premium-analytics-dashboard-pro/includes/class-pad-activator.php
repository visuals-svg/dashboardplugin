<?php
/**
 * Plugin Activation
 *
 * Jab plugin activate hota hai (single site ya har site multisite me)
 * to yahan tables banate hain, roles set karte hain, defaults save
 * karte hain aur cron schedule karte hain.
 *
 * @package Premium_Analytics_Dashboard_Pro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class PAD_Activator
 */
class PAD_Activator {

	/**
	 * Main activation entry point. Network-wide activation par
	 * har site ke liye alag se tables create karta hai.
	 *
	 * @param bool $network_wide Kya "Network Activate" se activate hua hai.
	 * @return void
	 */
	public static function activate( $network_wide = false ) {

		if ( is_multisite() && $network_wide ) {

			$site_ids = get_sites( array( 'fields' => 'ids' ) );

			foreach ( $site_ids as $site_id ) {
				switch_to_blog( $site_id );
				self::activate_single_site();
				restore_current_blog();
			}

			return;
		}

		self::activate_single_site();
	}

	/**
	 * Naya site multisite network me add hone par (wp_initialize_site)
	 * us site ke liye bhi plugin tables create karta hai, agar plugin
	 * network-active hai.
	 *
	 * @param WP_Site $new_site Newly created site object.
	 * @return void
	 */
	public static function activate_new_site( $new_site ) {

		if ( ! is_plugin_active_for_network( PAD_PLUGIN_BASENAME ) ) {
			return;
		}

		switch_to_blog( (int) $new_site->blog_id );
		self::activate_single_site();
		restore_current_blog();
	}

	/**
	 * Current site ke liye actual activation kaam karta hai:
	 * DB tables, roles, default settings, aur cron event.
	 *
	 * @return void
	 */
	private static function activate_single_site() {

		PAD_Database::create_tables();
		PAD_Roles::add_roles();

		if ( false === get_option( 'pad_settings', false ) ) {
			add_option( 'pad_settings', PAD_Helper::get_settings() );
		}

		if ( ! wp_next_scheduled( 'pad_daily_cleanup' ) ) {
			wp_schedule_event( time(), 'daily', 'pad_daily_cleanup' );
		}

		set_transient( 'pad_activation_redirect', true, 30 );
	}
}
