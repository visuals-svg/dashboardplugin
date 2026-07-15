<?php
/**
 * Admin Menu & Assets
 *
 * Plugin ka poora wp-admin footprint yahan se control hota hai:
 * 9 menu items, unki capability gating, aur sirf plugin ke apne
 * screens par CSS/JS load karna (performance ke liye zaroori).
 *
 * @package Premium_Analytics_Dashboard_Pro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class PAD_Admin
 */
class PAD_Admin {

	/**
	 * Parent menu slug.
	 *
	 * @var string
	 */
	const MENU_SLUG = 'premium-analytics-dashboard';

	/**
	 * Submenu definitions: slug => [label, capability, partial file, icon].
	 * Public taaki partial templates apni sidebar navigation isi se render karein
	 * — nav items kabhi do jagah alag-alag hardcode nahi hote.
	 *
	 * @return array
	 */
	public function get_pages() {
		return array(
			'premium-analytics-dashboard' => array(
				'label'      => __( 'Dashboard', 'premium-analytics-dashboard-pro' ),
				'capability' => 'pad_view_dashboard',
				'partial'    => 'dashboard-page.php',
				'icon'       => 'dashicons-chart-area',
			),
			'pad-leads'                   => array(
				'label'      => __( 'Leads', 'premium-analytics-dashboard-pro' ),
				'capability' => 'pad_manage_leads',
				'partial'    => 'leads-page.php',
				'icon'       => 'dashicons-groups',
			),
			'pad-visitors'                => array(
				'label'      => __( 'Visitors', 'premium-analytics-dashboard-pro' ),
				'capability' => 'pad_manage_visitors',
				'partial'    => 'visitors-page.php',
				'icon'       => 'dashicons-visibility',
			),
			'pad-forms'                   => array(
				'label'      => __( 'Forms', 'premium-analytics-dashboard-pro' ),
				'capability' => 'pad_manage_forms',
				'partial'    => 'forms-page.php',
				'icon'       => 'dashicons-feedback',
			),
			'pad-analytics'               => array(
				'label'      => __( 'Analytics', 'premium-analytics-dashboard-pro' ),
				'capability' => 'pad_view_analytics',
				'partial'    => 'analytics-page.php',
				'icon'       => 'dashicons-chart-pie',
			),
			'pad-reports'                 => array(
				'label'      => __( 'Reports', 'premium-analytics-dashboard-pro' ),
				'capability' => 'pad_manage_reports',
				'partial'    => 'reports-page.php',
				'icon'       => 'dashicons-media-document',
			),
			'pad-settings'                => array(
				'label'      => __( 'Settings', 'premium-analytics-dashboard-pro' ),
				'capability' => 'pad_manage_settings',
				'partial'    => 'settings-page.php',
				'icon'       => 'dashicons-admin-generic',
			),
			'pad-users'                   => array(
				'label'      => __( 'Users', 'premium-analytics-dashboard-pro' ),
				'capability' => 'pad_manage_users',
				'partial'    => 'users-page.php',
				'icon'       => 'dashicons-admin-users',
			),
			'pad-logs'                    => array(
				'label'      => __( 'Logs', 'premium-analytics-dashboard-pro' ),
				'capability' => 'pad_view_logs',
				'partial'    => 'logs-page.php',
				'icon'       => 'dashicons-list-view',
			),
		);
	}

	/**
	 * Top-level menu aur 9 submenus register karta hai. Har submenu
	 * apni capability se gate hota hai, is liye Viewer/Sales/Manager
	 * roles ko sirf unhi tabs dikhengi jinki unko permission hai.
	 *
	 * @return void
	 */
	public function register_admin_menu() {

		$pages = $this->get_pages();
		$icon  = 'data:image/svg+xml;base64,' . base64_encode(
			'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="#a7aaad"><path d="M4 20h2V10H4v10zm14 0h2V4h-2v16zm-7 0h2v-7h-2v7z"/></svg>'
		);

		add_menu_page(
			__( 'Premium Analytics Dashboard Pro', 'premium-analytics-dashboard-pro' ),
			__( 'Analytics Pro', 'premium-analytics-dashboard-pro' ),
			'pad_view_dashboard',
			self::MENU_SLUG,
			array( $this, 'render_page' ),
			$icon,
			26
		);

		foreach ( $pages as $slug => $page ) {
			add_submenu_page(
				self::MENU_SLUG,
				$page['label'],
				$page['label'],
				$page['capability'],
				$slug,
				array( $this, 'render_page' )
			);
		}
	}

	/**
	 * Current requested submenu slug ke hisaab se sahi partial template
	 * include karta hai. Capability WordPress khud add_submenu_page
	 * level par check kar chuka hota hai, par hum defense-in-depth ke
	 * liye dobara bhi verify karte hain.
	 *
	 * @return void
	 */
	public function render_page() {

		$pages   = $this->get_pages();
		$current = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : self::MENU_SLUG;

		if ( ! isset( $pages[ $current ] ) ) {
			$current = self::MENU_SLUG;
		}

		$page = $pages[ $current ];

		if ( ! current_user_can( $page['capability'] ) ) {
			wp_die( esc_html__( 'Is page ko dekhne ki aapke paas permission nahi hai.', 'premium-analytics-dashboard-pro' ) );
		}

		$partial_path = PAD_PLUGIN_DIR . 'admin/partials/' . $page['partial'];

		if ( file_exists( $partial_path ) ) {
			require $partial_path;
		}
	}

	/**
	 * Plugin ke apne admin screens par hi CSS/JS load karta hai —
	 * baaki wp-admin pages par kuch bhi enqueue nahi hota.
	 *
	 * @param string $hook_suffix Current admin page hook suffix.
	 * @return void
	 */
	public function enqueue_assets( $hook_suffix ) {

		if ( false === strpos( $hook_suffix, self::MENU_SLUG ) && false === strpos( $hook_suffix, 'pad-' ) ) {
			return;
		}

		wp_enqueue_style(
			'pad-admin',
			PAD_PLUGIN_URL . 'assets/css/pad-admin.css',
			array(),
			PAD_VERSION
		);

		$script_deps = array( 'jquery' );

		if ( false !== strpos( $hook_suffix, 'pad-settings' ) ) {
			wp_enqueue_media();
			wp_enqueue_style( 'wp-color-picker' );
			wp_enqueue_script( 'wp-color-picker' );
			$script_deps[] = 'wp-color-picker';
		}

		wp_enqueue_script(
			'pad-admin',
			PAD_PLUGIN_URL . 'assets/js/pad-admin.js',
			$script_deps,
			PAD_VERSION,
			true
		);

		$settings = PAD_Helper::get_settings();

		wp_localize_script(
			'pad-admin',
			'padAdmin',
			array(
				'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
				'nonce'     => wp_create_nonce( 'pad_admin_nonce' ),
				'themeMode' => $settings['theme_mode'],
				'strings'   => array(
					'loading'          => __( 'Loading…', 'premium-analytics-dashboard-pro' ),
					'noLeads'          => __( 'Koi lead is filter ke saath nahi mila.', 'premium-analytics-dashboard-pro' ),
					'view'             => __( 'View', 'premium-analytics-dashboard-pro' ),
					'deleteLabel'      => __( 'Delete', 'premium-analytics-dashboard-pro' ),
					'page'             => __( 'Page', 'premium-analytics-dashboard-pro' ),
					'selected'         => __( 'selected', 'premium-analytics-dashboard-pro' ),
					'leadDetails'      => __( 'Lead Details', 'premium-analytics-dashboard-pro' ),
					'customFields'     => __( 'Custom Fields', 'premium-analytics-dashboard-pro' ),
					'tags'             => __( 'Tags', 'premium-analytics-dashboard-pro' ),
					'addTag'           => __( 'Add a tag…', 'premium-analytics-dashboard-pro' ),
					'notes'            => __( 'Notes', 'premium-analytics-dashboard-pro' ),
					'addNote'          => __( 'Write a note…', 'premium-analytics-dashboard-pro' ),
					'save'             => __( 'Save', 'premium-analytics-dashboard-pro' ),
					'noNotes'          => __( 'Abhi tak koi note nahi hai.', 'premium-analytics-dashboard-pro' ),
					'confirmDelete'    => __( 'Kya aap is lead ko delete karna chahte hain?', 'premium-analytics-dashboard-pro' ),
					'confirmBulkDelete' => __( 'Kya aap selected leads ko delete karna chahte hain?', 'premium-analytics-dashboard-pro' ),
				),
			)
		);

		// ApexCharts (vendored locally) sirf Dashboard aur Analytics par —
		// 500KB+ ki library baaki 7 pages par load karne ki zaroorat nahi.
		$is_chart_page = ( false !== strpos( $hook_suffix, 'toplevel_page_' . self::MENU_SLUG ) )
			|| ( false !== strpos( $hook_suffix, 'pad-analytics' ) );

		if ( $is_chart_page ) {

			wp_enqueue_script(
				'pad-apexcharts',
				PAD_PLUGIN_URL . 'assets/js/vendor/apexcharts.min.js',
				array(),
				'3.54.1',
				true
			);

			wp_enqueue_script(
				'pad-charts',
				PAD_PLUGIN_URL . 'assets/js/pad-charts.js',
				array( 'pad-admin', 'pad-apexcharts' ),
				PAD_VERSION,
				true
			);
		}

		if ( false !== strpos( $hook_suffix, 'pad-leads' ) ) {
			wp_enqueue_script(
				'pad-leads',
				PAD_PLUGIN_URL . 'assets/js/pad-leads.js',
				array( 'pad-admin' ),
				PAD_VERSION,
				true
			);
		}
	}

	/**
	 * Activation ke turant baad ek baar Dashboard page par redirect
	 * karta hai (bulk activation ya network activation par skip hota hai).
	 *
	 * @return void
	 */
	public function maybe_redirect_after_activation() {

		if ( ! get_transient( 'pad_activation_redirect' ) ) {
			return;
		}

		delete_transient( 'pad_activation_redirect' );

		if ( wp_doing_ajax() || is_network_admin() || isset( $_GET['activate-multi'] ) ) {
			return;
		}

		if ( ! current_user_can( 'pad_view_dashboard' ) ) {
			return;
		}

		wp_safe_redirect( admin_url( 'admin.php?page=' . self::MENU_SLUG ) );
		exit;
	}

	/**
	 * Dark/Light mode toggle ko user ke settings me persist karta hai.
	 * Nonce aur capability dono verify hote hain — CSRF-safe AJAX.
	 *
	 * @return void
	 */
	public function ajax_toggle_theme_mode() {

		check_ajax_referer( 'pad_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'pad_view_dashboard' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'premium-analytics-dashboard-pro' ) ), 403 );
		}

		$mode = isset( $_POST['mode'] ) ? sanitize_key( wp_unslash( $_POST['mode'] ) ) : 'light';

		if ( ! in_array( $mode, array( 'light', 'dark' ), true ) ) {
			$mode = 'light';
		}

		$settings               = PAD_Helper::get_settings();
		$settings['theme_mode'] = $mode;

		update_option( 'pad_settings', $settings );

		wp_send_json_success( array( 'mode' => $mode ) );
	}

	/**
	 * Chart data ka single AJAX dispatcher — `chart` request param ke
	 * hisaab se sahi PAD_Charts method call karta hai. Capability check
	 * 'pad_view_analytics' se hota hai (Dashboard aur Analytics dono
	 * pages ke charts is capability ke peeche gated hain).
	 *
	 * @return void
	 */
	public function ajax_get_chart_data() {

		check_ajax_referer( 'pad_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'pad_view_analytics' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'premium-analytics-dashboard-pro' ) ), 403 );
		}

		$chart = isset( $_GET['chart'] ) ? sanitize_key( wp_unslash( $_GET['chart'] ) ) : '';

		switch ( $chart ) {

			case 'leads-over-time':
				$granularity = isset( $_GET['granularity'] ) ? sanitize_key( wp_unslash( $_GET['granularity'] ) ) : 'daily';
				$allowed     = array( 'daily', 'weekly', 'monthly', 'yearly' );
				$granularity = in_array( $granularity, $allowed, true ) ? $granularity : 'daily';
				wp_send_json_success( PAD_Charts::get_leads_over_time( $granularity ) );
				break;

			case 'traffic-sources':
				wp_send_json_success( PAD_Charts::get_traffic_sources() );
				break;

			case 'country-analytics':
				wp_send_json_success( PAD_Charts::get_visitor_breakdown( 'country' ) );
				break;

			case 'city-analytics':
				wp_send_json_success( PAD_Charts::get_visitor_breakdown( 'city' ) );
				break;

			case 'browser-analytics':
				wp_send_json_success( PAD_Charts::get_visitor_breakdown( 'browser' ) );
				break;

			case 'device-analytics':
				wp_send_json_success( PAD_Charts::get_visitor_breakdown( 'device' ) );
				break;

			case 'os-analytics':
				wp_send_json_success( PAD_Charts::get_visitor_breakdown( 'os' ) );
				break;

			case 'lead-conversion':
				wp_send_json_success( PAD_Charts::get_lead_conversion() );
				break;

			case 'hourly-heatmap':
				wp_send_json_success( PAD_Charts::get_hourly_heatmap() );
				break;

			default:
				wp_send_json_error( array( 'message' => __( 'Unknown chart.', 'premium-analytics-dashboard-pro' ) ), 400 );
		}
	}

	/**
	 * Settings form ka submission handle karta hai. Nonce + capability
	 * dono verify hote hain, har field sanitize hoti hai, phir sab
	 * ek hi 'pad_settings' option row me save ho jaata hai.
	 *
	 * @return void
	 */
	public function handle_save_settings() {

		if ( ! isset( $_POST['pad_settings_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['pad_settings_nonce'] ) ), 'pad_save_settings' ) ) {
			wp_die( esc_html__( 'Security check fail ho gayi, page reload karke dobara try karein.', 'premium-analytics-dashboard-pro' ) );
		}

		if ( ! current_user_can( 'pad_manage_settings' ) ) {
			wp_die( esc_html__( 'Is action ke liye aapke paas permission nahi hai.', 'premium-analytics-dashboard-pro' ) );
		}

		$allowed_widgets   = array( 'leads', 'visitors', 'conversion', 'charts' );
		$posted_widgets    = isset( $_POST['dashboard_widgets'] ) ? (array) wp_unslash( $_POST['dashboard_widgets'] ) : array();
		$current_settings  = PAD_Helper::get_settings();
		$posted_theme_hex  = isset( $_POST['theme_color'] ) ? sanitize_hex_color( wp_unslash( $_POST['theme_color'] ) ) : null;

		$settings = array(
			'timezone'            => isset( $_POST['timezone'] ) ? sanitize_text_field( wp_unslash( $_POST['timezone'] ) ) : wp_timezone_string(),
			'currency'            => isset( $_POST['currency'] ) ? sanitize_text_field( wp_unslash( $_POST['currency'] ) ) : 'USD',
			'company_logo'        => isset( $_POST['company_logo'] ) ? esc_url_raw( wp_unslash( $_POST['company_logo'] ) ) : '',
			'theme_color'         => $posted_theme_hex ? $posted_theme_hex : $current_settings['theme_color'],
			'theme_mode'          => $current_settings['theme_mode'],
			'dashboard_widgets'   => array_values( array_intersect( $allowed_widgets, $posted_widgets ) ),
			'delete_on_uninstall' => isset( $_POST['delete_on_uninstall'] ) ? true : false,
		);

		update_option( 'pad_settings', $settings );

		PAD_Helper::log( 'settings_updated', 'settings', 0, __( 'Plugin settings update ki gayi.', 'premium-analytics-dashboard-pro' ) );

		wp_safe_redirect( add_query_arg( array( 'page' => 'pad-settings', 'pad-updated' => '1' ), admin_url( 'admin.php' ) ) );
		exit;
	}
}
