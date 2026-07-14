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
		$this->loader = new PAD_Loader();

		$this->set_locale();
		$this->define_admin_hooks();
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
