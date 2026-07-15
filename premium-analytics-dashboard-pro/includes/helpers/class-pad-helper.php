<?php
/**
 * Shared Helper Functions
 *
 * Chhote, reusable utility functions jo dashboard aur baaki modules
 * me baar-baar use honge (date ranges, client IP, UA parsing, etc.)
 *
 * @package Premium_Analytics_Dashboard_Pro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class PAD_Helper
 */
class PAD_Helper {

	/**
	 * Visitor ki real client IP nikaalta hai, proxy headers ko
	 * safely handle karte hue. Har value sanitize hoti hai.
	 *
	 * @return string
	 */
	public static function get_client_ip() {

		$headers = array( 'HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR' );

		foreach ( $headers as $header ) {

			if ( empty( $_SERVER[ $header ] ) ) {
				continue;
			}

			$raw = sanitize_text_field( wp_unslash( $_SERVER[ $header ] ) );
			$ip  = trim( explode( ',', $raw )[0] );

			if ( filter_var( $ip, FILTER_VALIDATE_IP ) ) {
				return $ip;
			}
		}

		return '';
	}

	/**
	 * Site ki configured timezone ke hisaab se ek named period
	 * (today, yesterday, week, month, quarter, year) ka start/end datetime deta hai.
	 *
	 * @param string $period today|yesterday|week|month|quarter|year.
	 * @return array{start: string, end: string}
	 */
	public static function get_date_range( $period ) {

		$timezone = wp_timezone();
		$now      = new DateTime( 'now', $timezone );

		$start = clone $now;
		$end   = clone $now;

		switch ( $period ) {
			case 'yesterday':
				$start->modify( '-1 day' )->setTime( 0, 0, 0 );
				$end->modify( '-1 day' )->setTime( 23, 59, 59 );
				break;

			case 'week':
				$start->modify( 'monday this week' )->setTime( 0, 0, 0 );
				$end->setTime( 23, 59, 59 );
				break;

			case 'month':
				$start->modify( 'first day of this month' )->setTime( 0, 0, 0 );
				$end->setTime( 23, 59, 59 );
				break;

			case 'year':
				$start->modify( 'first day of january this year' )->setTime( 0, 0, 0 );
				$end->setTime( 23, 59, 59 );
				break;

			case 'quarter':
				$current_quarter = (int) ceil( (int) $now->format( 'n' ) / 3 );
				$start_month      = ( ( $current_quarter - 1 ) * 3 ) + 1;
				$start->setDate( (int) $now->format( 'Y' ), $start_month, 1 )->setTime( 0, 0, 0 );
				$end->setTime( 23, 59, 59 );
				break;

			case 'today':
			default:
				$start->setTime( 0, 0, 0 );
				$end->setTime( 23, 59, 59 );
				break;
		}

		return array(
			'start' => $start->format( 'Y-m-d H:i:s' ),
			'end'   => $end->format( 'Y-m-d H:i:s' ),
		);
	}

	/**
	 * User-Agent string se browser name detect karta hai.
	 * Koi external library nahi — simple regex based detection.
	 *
	 * @param string $user_agent Raw user agent string.
	 * @return string
	 */
	public static function detect_browser( $user_agent ) {

		$user_agent = (string) $user_agent;

		$browsers = array(
			'Edg'     => 'Edge',
			'OPR'     => 'Opera',
			'Chrome'  => 'Chrome',
			'CriOS'   => 'Chrome',
			'Firefox' => 'Firefox',
			'FxiOS'   => 'Firefox',
			'Safari'  => 'Safari',
			'MSIE'    => 'Internet Explorer',
			'Trident' => 'Internet Explorer',
		);

		foreach ( $browsers as $needle => $label ) {
			if ( false !== strpos( $user_agent, $needle ) ) {
				return $label;
			}
		}

		return __( 'Unknown', 'premium-analytics-dashboard-pro' );
	}

	/**
	 * User-Agent string se operating system detect karta hai.
	 *
	 * @param string $user_agent Raw user agent string.
	 * @return string
	 */
	public static function detect_os( $user_agent ) {

		$user_agent = (string) $user_agent;

		$systems = array(
			'Windows NT 10.0' => 'Windows 10/11',
			'Windows NT 6.3'  => 'Windows 8.1',
			'Windows NT 6.2'  => 'Windows 8',
			'Windows NT 6.1'  => 'Windows 7',
			'Windows'         => 'Windows',
			'Mac OS X'        => 'macOS',
			'Android'         => 'Android',
			'iPhone'          => 'iOS',
			'iPad'            => 'iOS',
			'Linux'           => 'Linux',
		);

		foreach ( $systems as $needle => $label ) {
			if ( false !== strpos( $user_agent, $needle ) ) {
				return $label;
			}
		}

		return __( 'Unknown', 'premium-analytics-dashboard-pro' );
	}

	/**
	 * User-Agent string se device type detect karta hai
	 * (Desktop, Tablet, Mobile).
	 *
	 * @param string $user_agent Raw user agent string.
	 * @return string
	 */
	public static function detect_device( $user_agent ) {

		$user_agent = (string) $user_agent;

		if ( preg_match( '/iPad|Tablet/i', $user_agent ) ) {
			return __( 'Tablet', 'premium-analytics-dashboard-pro' );
		}

		if ( preg_match( '/Mobi|Android|iPhone/i', $user_agent ) ) {
			return __( 'Mobile', 'premium-analytics-dashboard-pro' );
		}

		return __( 'Desktop', 'premium-analytics-dashboard-pro' );
	}

	/**
	 * Referrer aur UTM parameters se traffic source ko ek fixed bucket
	 * me classify karta hai: Google Search, Facebook, Instagram,
	 * LinkedIn, Paid, Organic (dusre search engines), Referral (koi
	 * bhi aur website), ya Direct.
	 *
	 * @param string $referrer   Full referrer URL (attribution cookie se).
	 * @param string $utm_source utm_source query parameter.
	 * @param string $utm_medium utm_medium query parameter.
	 * @return string
	 */
	public static function classify_referral_source( $referrer, $utm_source = '', $utm_medium = '' ) {

		$utm_medium = strtolower( (string) $utm_medium );
		$utm_source = strtolower( (string) $utm_source );

		$paid_mediums = array( 'cpc', 'ppc', 'paid', 'ads', 'paidsocial', 'display' );

		if ( in_array( $utm_medium, $paid_mediums, true ) ) {
			return __( 'Paid', 'premium-analytics-dashboard-pro' );
		}

		$host = '';

		if ( ! empty( $referrer ) ) {
			$parsed = wp_parse_url( $referrer );
			$host   = isset( $parsed['host'] ) ? strtolower( $parsed['host'] ) : '';
		}

		if ( empty( $host ) && empty( $utm_source ) ) {
			return __( 'Direct', 'premium-analytics-dashboard-pro' );
		}

		if ( false !== strpos( $host, 'google.' ) || 'google' === $utm_source ) {
			return __( 'Google Search', 'premium-analytics-dashboard-pro' );
		}

		if ( false !== strpos( $host, 'facebook.com' ) || 'facebook' === $utm_source ) {
			return __( 'Facebook', 'premium-analytics-dashboard-pro' );
		}

		if ( false !== strpos( $host, 'instagram.com' ) || 'instagram' === $utm_source ) {
			return __( 'Instagram', 'premium-analytics-dashboard-pro' );
		}

		if ( false !== strpos( $host, 'linkedin.com' ) || 'linkedin' === $utm_source ) {
			return __( 'LinkedIn', 'premium-analytics-dashboard-pro' );
		}

		$organic_search_hosts = array( 'bing.com', 'yahoo.com', 'duckduckgo.com', 'yandex.com', 'baidu.com' );

		foreach ( $organic_search_hosts as $search_host ) {
			if ( false !== strpos( $host, $search_host ) ) {
				return __( 'Organic', 'premium-analytics-dashboard-pro' );
			}
		}

		if ( ! empty( $host ) ) {
			return __( 'Referral', 'premium-analytics-dashboard-pro' );
		}

		return __( 'Direct', 'premium-analytics-dashboard-pro' );
	}

	/**
	 * Plugin settings ko default values ke saath fetch karta hai.
	 * Settings ek hi option row me store hoti hain ('pad_settings').
	 *
	 * @return array
	 */
	public static function get_settings() {

		$defaults = array(
			'timezone'          => wp_timezone_string(),
			'currency'          => 'USD',
			'company_logo'      => '',
			'theme_color'       => '#6366f1',
			'theme_mode'        => 'light',
			'dashboard_widgets' => array( 'leads', 'visitors', 'conversion', 'charts' ),
			'delete_on_uninstall' => false,
		);

		$saved = get_option( 'pad_settings', array() );

		if ( ! is_array( $saved ) ) {
			$saved = array();
		}

		return wp_parse_args( $saved, $defaults );
	}

	/**
	 * Ek activity log entry insert karta hai, prepared statement ke through.
	 *
	 * @param string $action      Short action key, e.g. 'lead_created'.
	 * @param string $object_type Related object type, e.g. 'lead'.
	 * @param int    $object_id   Related object ID.
	 * @param string $description Human readable description.
	 * @return void
	 */
	public static function log( $action, $object_type = '', $object_id = 0, $description = '' ) {
		global $wpdb;

		$wpdb->insert(
			PAD_Database::table( 'logs' ),
			array(
				'user_id'     => get_current_user_id(),
				'action'      => sanitize_key( $action ),
				'object_type' => sanitize_key( $object_type ),
				'object_id'   => absint( $object_id ),
				'description' => sanitize_textarea_field( $description ),
				'ip_address'  => self::get_client_ip(),
				'created_at'  => current_time( 'mysql' ),
			),
			array( '%d', '%s', '%s', '%d', '%s', '%s', '%s' )
		);
	}
}
