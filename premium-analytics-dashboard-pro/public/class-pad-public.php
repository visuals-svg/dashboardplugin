<?php
/**
 * Public / Frontend Attribution Tracking
 *
 * Har visitor ki pehli visit par landing page, referrer aur UTM
 * parameters ek first-party cookie me capture karte hain. Yehi data
 * baad me Form Tracking (lead ke saath) aur Visitor Tracking
 * (Phase 3) dono use karenge — ek hi jagah se, kabhi bhi form ke
 * hidden fields modify karne ki zaroorat nahi.
 *
 * @package Premium_Analytics_Dashboard_Pro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class PAD_Public
 */
class PAD_Public {

	/**
	 * Attribution cookie ka naam.
	 *
	 * @var string
	 */
	const COOKIE_NAME = 'pad_attribution';

	/**
	 * Cookie kitne din tak valid rahegi.
	 *
	 * @var int
	 */
	const COOKIE_DAYS = 30;

	/**
	 * First page-load par attribution data capture karke cookie set karta hai.
	 * Agar cookie already set hai (yaani visitor pehle bhi aa chuka hai)
	 * to kuch nahi karte — yeh "first-touch attribution" hai.
	 *
	 * @return void
	 */
	public function capture_attribution() {

		if ( is_admin() || wp_doing_ajax() || wp_doing_cron() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
			return;
		}

		if ( headers_sent() ) {
			return;
		}

		if ( ! empty( $_COOKIE[ self::COOKIE_NAME ] ) ) {
			return;
		}

		$payload = array(
			'landing_page' => self::get_current_url(),
			'referrer'     => isset( $_SERVER['HTTP_REFERER'] ) ? esc_url_raw( wp_unslash( $_SERVER['HTTP_REFERER'] ) ) : '',
			'utm_source'   => self::get_query_param( 'utm_source' ),
			'utm_medium'   => self::get_query_param( 'utm_medium' ),
			'utm_campaign' => self::get_query_param( 'utm_campaign' ),
			'utm_term'     => self::get_query_param( 'utm_term' ),
			'utm_content'  => self::get_query_param( 'utm_content' ),
			'captured_at'  => time(),
		);

		$encoded = wp_json_encode( $payload );

		setcookie(
			self::COOKIE_NAME,
			$encoded,
			array(
				'expires'  => time() + ( self::COOKIE_DAYS * DAY_IN_SECONDS ),
				'path'     => defined( 'COOKIEPATH' ) && COOKIEPATH ? COOKIEPATH : '/',
				'domain'   => defined( 'COOKIE_DOMAIN' ) ? COOKIE_DOMAIN : '',
				'secure'   => is_ssl(),
				'httponly' => true,
				'samesite' => 'Lax',
			)
		);

		// Isi request ke andar bhi turant available rahe (agar CF7 form isi page par ho).
		$_COOKIE[ self::COOKIE_NAME ] = $encoded;
	}

	/**
	 * Current full URL (scheme + host + path + query) return karta hai.
	 *
	 * @return string
	 */
	public static function get_current_url() {

		$scheme = is_ssl() ? 'https://' : 'http://';
		$host   = isset( $_SERVER['HTTP_HOST'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_HOST'] ) ) : '';
		$uri    = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';

		return esc_url_raw( $scheme . $host . $uri );
	}

	/**
	 * $_GET se ek UTM parameter safely nikaalta hai.
	 *
	 * @param string $key Query parameter name.
	 * @return string
	 */
	private static function get_query_param( $key ) {

		if ( empty( $_GET[ $key ] ) ) {
			return '';
		}

		return sanitize_text_field( wp_unslash( $_GET[ $key ] ) );
	}

	/**
	 * Current visitor ki stored attribution data laut ata hai
	 * (lead capture module isi ko use karta hai).
	 *
	 * @return array
	 */
	public static function get_attribution() {

		if ( empty( $_COOKIE[ self::COOKIE_NAME ] ) ) {
			return array();
		}

		$decoded = json_decode( wp_unslash( $_COOKIE[ self::COOKIE_NAME ] ), true );

		return is_array( $decoded ) ? $decoded : array();
	}
}
