<?php
/**
 * Internationalization
 *
 * Plugin ka text-domain load karta hai taaki translations kaam kare.
 *
 * @package Premium_Analytics_Dashboard_Pro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class PAD_i18n
 */
class PAD_i18n {

	/**
	 * `/languages` folder se translation files load karta hai.
	 *
	 * @return void
	 */
	public function load_plugin_textdomain() {
		load_plugin_textdomain(
			PAD_TEXT_DOMAIN,
			false,
			dirname( PAD_PLUGIN_BASENAME ) . '/languages/'
		);
	}
}
