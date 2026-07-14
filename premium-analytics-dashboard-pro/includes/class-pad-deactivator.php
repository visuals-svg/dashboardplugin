<?php
/**
 * Plugin Deactivation
 *
 * Deactivation par hum sirf scheduled cron hooks clear karte hain.
 * Data (leads, visitors, logs, settings) yahan par kabhi bhi delete
 * nahi hota — woh sirf uninstall.php me, aur wo bhi user ki explicit
 * "delete data on uninstall" setting on hone par hota hai.
 *
 * @package Premium_Analytics_Dashboard_Pro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class PAD_Deactivator
 */
class PAD_Deactivator {

	/**
	 * Deactivation par chalne wala method.
	 *
	 * @return void
	 */
	public static function deactivate() {

		$timestamp = wp_next_scheduled( 'pad_daily_cleanup' );

		if ( $timestamp ) {
			wp_unschedule_event( $timestamp, 'pad_daily_cleanup' );
		}

		delete_transient( 'pad_activation_redirect' );

		flush_rewrite_rules();
	}
}
