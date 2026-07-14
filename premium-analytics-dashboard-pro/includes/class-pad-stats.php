<?php
/**
 * Dashboard Statistics
 *
 * Saare dashboard cards ke real numbers yahan se aate hain — prepared
 * SQL statements ke through. Data abhi empty hoga (Contact Form 7
 * capture aur visitor tracker aage ke phase me wire honge), lekin
 * queries pehle se hi production-ready hain.
 *
 * @package Premium_Analytics_Dashboard_Pro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class PAD_Stats
 */
class PAD_Stats {

	/**
	 * Diye gaye period ke andar aaye leads ki count.
	 *
	 * @param string $period today|yesterday|week|month|year|all.
	 * @return int
	 */
	public static function get_leads_count( $period = 'all' ) {
		global $wpdb;

		$table = PAD_Database::table( 'leads' );

		if ( 'all' === $period ) {
			return (int) $wpdb->get_var( "SELECT COUNT(id) FROM {$table}" );
		}

		$range = PAD_Helper::get_date_range( $period );

		return (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(id) FROM {$table} WHERE created_at BETWEEN %s AND %s",
				$range['start'],
				$range['end']
			)
		);
	}

	/**
	 * Ab tak track kiye gaye total unique visitors.
	 *
	 * @return int
	 */
	public static function get_total_visitors() {
		global $wpdb;

		$table = PAD_Database::table( 'visitors' );

		return (int) $wpdb->get_var( "SELECT COUNT(id) FROM {$table}" );
	}

	/**
	 * Pichhle 5 minutes me active (abhi "online") visitors.
	 *
	 * @return int
	 */
	public static function get_visitors_online() {
		global $wpdb;

		$table     = PAD_Database::table( 'sessions' );
		$threshold = gmdate( 'Y-m-d H:i:s', current_time( 'timestamp', true ) - 5 * MINUTE_IN_SECONDS );

		return (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(DISTINCT visitor_id) FROM {$table} WHERE ended_at IS NULL AND started_at >= %s",
				$threshold
			)
		);
	}

	/**
	 * Conversion rate % = (total leads / total visitors) * 100.
	 *
	 * @return float
	 */
	public static function get_conversion_rate() {

		$visitors = self::get_total_visitors();

		if ( $visitors <= 0 ) {
			return 0.0;
		}

		$leads = self::get_leads_count( 'all' );

		return round( ( $leads / $visitors ) * 100, 2 );
	}

	/**
	 * Average session duration seconds me — sirf complete (ended) sessions se.
	 *
	 * @return int
	 */
	public static function get_average_session_duration() {
		global $wpdb;

		$table = PAD_Database::table( 'sessions' );

		$average = $wpdb->get_var( "SELECT AVG(duration) FROM {$table} WHERE ended_at IS NOT NULL" );

		return null === $average ? 0 : (int) round( (float) $average );
	}

	/**
	 * Bounce rate % = single-pageview sessions / total sessions * 100.
	 *
	 * @return float
	 */
	public static function get_bounce_rate() {
		global $wpdb;

		$table = PAD_Database::table( 'sessions' );

		$total = (int) $wpdb->get_var( "SELECT COUNT(id) FROM {$table}" );

		if ( $total <= 0 ) {
			return 0.0;
		}

		$bounced = (int) $wpdb->get_var( "SELECT COUNT(id) FROM {$table} WHERE is_bounce = 1" );

		return round( ( $bounced / $total ) * 100, 2 );
	}

	/**
	 * Seconds ko "Xm Ys" jaise human readable format me convert karta hai.
	 *
	 * @param int $seconds Total seconds.
	 * @return string
	 */
	public static function format_duration( $seconds ) {

		$seconds = max( 0, (int) $seconds );
		$minutes = (int) floor( $seconds / 60 );
		$remain  = $seconds % 60;

		return sprintf(
			/* translators: 1: minutes, 2: seconds */
			__( '%1$dm %2$ds', 'premium-analytics-dashboard-pro' ),
			$minutes,
			$remain
		);
	}
}
