<?php
/**
 * Roles & Capabilities
 *
 * Plugin ke 4 permission levels define karta hai: Administrator,
 * Manager, Sales, Viewer. Har admin page in capabilities se hi
 * gate hoti hai — capability check ke bina koi page render nahi hota.
 *
 * @package Premium_Analytics_Dashboard_Pro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class PAD_Roles
 */
class PAD_Roles {

	/**
	 * Saari capabilities jo plugin define karta hai.
	 *
	 * @return string[]
	 */
	public static function get_all_capabilities() {
		return array(
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
	}

	/**
	 * Role => capabilities ka map. Yahi single source of truth hai.
	 *
	 * @return array<string, string[]>
	 */
	private static function get_role_map() {
		return array(
			'pad_manager' => array(
				'pad_view_dashboard',
				'pad_manage_leads',
				'pad_delete_leads',
				'pad_export_leads',
				'pad_manage_visitors',
				'pad_manage_forms',
				'pad_view_analytics',
				'pad_manage_reports',
				'pad_view_logs',
			),
			'pad_sales'   => array(
				'pad_view_dashboard',
				'pad_manage_leads',
				'pad_export_leads',
				'pad_view_analytics',
			),
			'pad_viewer'  => array(
				'pad_view_dashboard',
				'pad_view_analytics',
				'pad_manage_reports',
			),
		);
	}

	/**
	 * Activation ke waqt custom roles create karta hai aur
	 * Administrator ko saari plugin capabilities de deta hai.
	 *
	 * @return void
	 */
	public static function add_roles() {

		$labels = array(
			'pad_manager' => __( 'Analytics Manager', 'premium-analytics-dashboard-pro' ),
			'pad_sales'   => __( 'Analytics Sales', 'premium-analytics-dashboard-pro' ),
			'pad_viewer'  => __( 'Analytics Viewer', 'premium-analytics-dashboard-pro' ),
		);

		foreach ( self::get_role_map() as $role_key => $capabilities ) {

			$caps = array( 'read' => true );
			foreach ( $capabilities as $cap ) {
				$caps[ $cap ] = true;
			}

			// Agar role pehle se exist karta hai to remove karke fresh banao,
			// taaki capability changes bhi sync ho jaayein.
			remove_role( $role_key );
			add_role( $role_key, $labels[ $role_key ], $caps );
		}

		$administrator = get_role( 'administrator' );

		if ( $administrator ) {
			foreach ( self::get_all_capabilities() as $cap ) {
				$administrator->add_cap( $cap );
			}
		}
	}

	/**
	 * Uninstall ke waqt custom roles hata deta hai aur
	 * Administrator se plugin capabilities strip kar deta hai.
	 *
	 * @return void
	 */
	public static function remove_roles() {

		foreach ( array_keys( self::get_role_map() ) as $role_key ) {
			remove_role( $role_key );
		}

		$administrator = get_role( 'administrator' );

		if ( $administrator ) {
			foreach ( self::get_all_capabilities() as $cap ) {
				$administrator->remove_cap( $cap );
			}
		}
	}
}
