<?php
/**
 * Uninstall Handler
 *
 * Yeh file sirf tab chalti hai jab user WordPress ke Plugins screen se
 * "Delete" click karta hai (deactivate se alag). Data sirf tab delete
 * hota hai jab user ne Settings me "Delete data on uninstall" explicitly
 * on kiya ho — warna leads/visitors/logs surakshit rehte hain.
 *
 * @package Premium_Analytics_Dashboard_Pro
 */

// Security: WordPress ke bahar se yeh file kabhi execute nahi honi chahiye.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

/**
 * Ek site ke liye plugin data hataata hai.
 *
 * @return void
 */
function pad_uninstall_cleanup_site() {
	global $wpdb;

	$settings = get_option( 'pad_settings', array() );
	$should_delete_data = ! empty( $settings['delete_on_uninstall'] );

	if ( $should_delete_data ) {

		$tables = array(
			'pad_lead_tags',
			'pad_tags',
			'pad_lead_notes',
			'pad_lead_meta',
			'pad_leads',
			'pad_pageviews',
			'pad_sessions',
			'pad_visitors',
			'pad_forms',
			'pad_logs',
		);

		foreach ( $tables as $table ) {
			$full_table_name = $wpdb->prefix . $table;
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- table name is from a fixed internal allow-list, not user input.
			$wpdb->query( "DROP TABLE IF EXISTS `{$full_table_name}`" );
		}

		delete_option( 'pad_settings' );
		delete_option( 'pad_db_version' );
	}

	// Custom roles hamesha clean karte hain, data delete ho ya na ho.
	$roles = array( 'pad_manager', 'pad_sales', 'pad_viewer' );

	foreach ( $roles as $role ) {
		remove_role( $role );
	}

	$administrator = get_role( 'administrator' );

	if ( $administrator ) {
		$capabilities = array(
			'pad_view_dashboard',
			'pad_manage_leads',
			'pad_delete_leads',
			'pad_export_leads',
			'pad_manage_visitors',
			'pad_manage_forms',
			'pad_view_analytics',
			'pad_manage_reports',
			'pad_manage_settings',
			'pad_manage_users',
			'pad_view_logs',
		);

		foreach ( $capabilities as $cap ) {
			$administrator->remove_cap( $cap );
		}
	}

	wp_clear_scheduled_hook( 'pad_daily_cleanup' );
	delete_transient( 'pad_activation_redirect' );
}

if ( is_multisite() ) {

	$site_ids = get_sites( array( 'fields' => 'ids' ) );

	foreach ( $site_ids as $site_id ) {
		switch_to_blog( $site_id );
		pad_uninstall_cleanup_site();
		restore_current_blog();
	}
} else {
	pad_uninstall_cleanup_site();
}
