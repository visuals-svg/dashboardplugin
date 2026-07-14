<?php
/**
 * Core Plugin Bootstrap
 *
 * Yeh class puri plugin ko wire-up karti hai: i18n, cron hook,
 * aur admin-side functionality. Har naya module (Leads, Visitors,
 * REST API, AJAX) aage aane wale phases me isi class se register hoga.
 *
 * @package Premium_Analytics_Dashboard_Pro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class PAD_Plugin
 */
class PAD_Plugin {

	/**
	 * Hook loader instance.
	 *
	 * @var PAD_Loader
	 */
	protected $loader;

	/**
	 * Constructor — dependencies load karta hai aur hooks queue karta hai.
	 */
	public function __construct() {
		PAD_Database::maybe_upgrade();

		$this->loader = new PAD_Loader();

		$this->set_locale();
		$this->define_public_hooks();
		$this->define_admin_hooks();
		$this->define_form_tracking_hooks();
		$this->define_visitor_tracking_hooks();
		$this->define_cron_hooks();
	}

	/**
	 * Text-domain loading hook register karta hai.
	 *
	 * @return void
	 */
	private function set_locale() {
		$i18n = new PAD_i18n();
		$this->loader->add_action( 'init', $i18n, 'load_plugin_textdomain' );
	}

	/**
	 * Frontend attribution tracking (UTM/referrer/landing page) register karta hai.
	 *
	 * @return void
	 */
	private function define_public_hooks() {
		$public = new PAD_Public();
		$this->loader->add_action( 'init', $public, 'capture_attribution' );
		$this->loader->add_action( 'init', $public, 'ensure_session_identity' );
		$this->loader->add_action( 'wp_enqueue_scripts', $public, 'enqueue_tracker_script' );
	}

	/**
	 * Visitor/session/pageview tracking ke AJAX endpoints register karta hai.
	 * `nopriv` variant zaroori hai kyunki zyada tar visitors logged-in nahi hote.
	 *
	 * @return void
	 */
	private function define_visitor_tracking_hooks() {

		$tracker = new PAD_Visitor_Tracker();

		$this->loader->add_action( 'wp_ajax_pad_track_pageview', $tracker, 'ajax_track_pageview' );
		$this->loader->add_action( 'wp_ajax_nopriv_pad_track_pageview', $tracker, 'ajax_track_pageview' );

		$this->loader->add_action( 'wp_ajax_pad_track_pageview_end', $tracker, 'ajax_track_pageview_end' );
		$this->loader->add_action( 'wp_ajax_nopriv_pad_track_pageview_end', $tracker, 'ajax_track_pageview_end' );
	}

	/**
	 * Contact Form 7 lead capture hook register karta hai. Yeh hook
	 * safe hai chahe CF7 installed ho ya na ho — event bas tabhi
	 * fire hoga jab CF7 khud ise trigger karega.
	 *
	 * @return void
	 */
	private function define_form_tracking_hooks() {
		$cf7 = new PAD_CF7_Integration();
		$this->loader->add_action( 'wpcf7_before_send_mail', $cf7, 'capture_submission', 10, 1 );
	}

	/**
	 * Admin menu, assets, aur admin-ajax hooks register karta hai.
	 *
	 * @return void
	 */
	private function define_admin_hooks() {

		$admin = new PAD_Admin();

		$this->loader->add_action( 'admin_menu', $admin, 'register_admin_menu' );
		$this->loader->add_action( 'admin_enqueue_scripts', $admin, 'enqueue_assets' );
		$this->loader->add_action( 'admin_init', $admin, 'maybe_redirect_after_activation' );

		$this->loader->add_action( 'wp_ajax_pad_toggle_theme_mode', $admin, 'ajax_toggle_theme_mode' );
		$this->loader->add_action( 'admin_post_pad_save_settings', $admin, 'handle_save_settings' );
		$this->loader->add_action( 'wp_ajax_pad_get_chart_data', $admin, 'ajax_get_chart_data' );
	}

	/**
	 * Scheduled maintenance (daily cleanup) hook register karta hai.
	 * Actual cleanup logic aage ke phase me Visitors/Sessions module
	 * ke saath aayega; abhi hook safe no-op ke roop me register hai.
	 *
	 * @return void
	 */
	private function define_cron_hooks() {
		$this->loader->add_action( 'pad_daily_cleanup', $this, 'run_daily_cleanup' );
	}

	/**
	 * Daily cron callback — DB version sync check karta hai taaki
	 * upgrade ke baad schema silently repair ho jaaye.
	 *
	 * @return void
	 */
	public function run_daily_cleanup() {

		if ( get_option( 'pad_db_version' ) !== PAD_DB_VERSION ) {
			PAD_Database::create_tables();
		}
	}

	/**
	 * Saare queued hooks ko WordPress me register karke plugin start karta hai.
	 *
	 * @return void
	 */
	public function run() {
		$this->loader->run();
	}
}
