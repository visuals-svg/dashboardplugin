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
	 * Diye gaye period ke andar aaye leads ki count. 5 minute tak
	 * cache hoti hai — naya lead capture/delete hone par PAD_Cache
	 * version bump se turant invalidate ho jaati hai.
	 *
	 * @param string $period today|yesterday|week|month|year|all.
	 * @return int
	 */
	public static function get_leads_count( $period = 'all' ) {

		return (int) PAD_Cache::remember(
			'leads_count_' . $period,
			5 * MINUTE_IN_SECONDS,
			function () use ( $period ) {
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
		);
	}

	/**
	 * Ab tak track kiye gaye total unique visitors. Visitor tracking
	 * high-frequency hai, is liye version-bump se invalidate nahi
	 * karte — sirf ek chhoti fixed TTL (natural staleness window,
	 * jaisa zyada tar analytics dashboards me hota hai).
	 *
	 * @return int
	 */
	public static function get_total_visitors() {

		return (int) PAD_Cache::remember(
			'total_visitors',
			3 * MINUTE_IN_SECONDS,
			function () {
				global $wpdb;
				return (int) $wpdb->get_var( 'SELECT COUNT(id) FROM ' . PAD_Database::table( 'visitors' ) );
			}
		);
	}

	/**
	 * Pichhle 5 minutes me active (abhi "online") visitors — session
	 * ki `last_activity_at` (naa ki `started_at`) check karte hain,
	 * taaki lambi chal rahi sessions bhi sahi se "online" dikhein.
	 *
	 * @return int
	 */
	public static function get_visitors_online() {
		global $wpdb;

		$table     = PAD_Database::table( 'sessions' );
		$threshold = gmdate( 'Y-m-d H:i:s', current_time( 'timestamp', true ) - 5 * MINUTE_IN_SECONDS );

		return (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(DISTINCT visitor_id) FROM {$table} WHERE ended_at IS NULL AND last_activity_at >= %s",
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

		return (int) PAD_Cache::remember(
			'avg_session_duration',
			3 * MINUTE_IN_SECONDS,
			function () {
				global $wpdb;

				$average = $wpdb->get_var( 'SELECT AVG(duration) FROM ' . PAD_Database::table( 'sessions' ) . ' WHERE ended_at IS NOT NULL' );

				return null === $average ? 0 : (int) round( (float) $average );
			}
		);
	}

	/**
	 * Bounce rate % = single-pageview sessions / total sessions * 100.
	 *
	 * @return float
	 */
	public static function get_bounce_rate() {

		return (float) PAD_Cache::remember(
			'bounce_rate',
			3 * MINUTE_IN_SECONDS,
			function () {
				global $wpdb;

				$table = PAD_Database::table( 'sessions' );
				$total = (int) $wpdb->get_var( "SELECT COUNT(id) FROM {$table}" );

				if ( $total <= 0 ) {
					return 0.0;
				}

				$bounced = (int) $wpdb->get_var( "SELECT COUNT(id) FROM {$table} WHERE is_bounce = 1" );

				return round( ( $bounced / $total ) * 100, 2 );
			}
		);
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
