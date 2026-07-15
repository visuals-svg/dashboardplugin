<?php
/**
 * Reports Data Layer
 *
 * Daily/Weekly/Monthly/Quarterly/Yearly/Custom range ke liye ek poora
 * business report banata hai — leads, visitors, conversion, traffic
 * sources, sab real prepared-SQL se. PAD_Leads_Table ke query engine
 * ko hi reuse karta hai taaki leads ki filtering logic ek hi jagah rahe.
 *
 * @package Premium_Analytics_Dashboard_Pro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class PAD_Reports
 */
class PAD_Reports {

	/**
	 * Valid period keys.
	 *
	 * @var string[]
	 */
	const PERIODS = array( 'daily', 'weekly', 'monthly', 'quarterly', 'yearly', 'custom' );

	/**
	 * Period key ko PAD_Helper::get_date_range() ke period name se map karta hai.
	 *
	 * @var array<string,string>
	 */
	const PERIOD_MAP = array(
		'daily'     => 'today',
		'weekly'    => 'week',
		'monthly'   => 'month',
		'quarterly' => 'quarter',
		'yearly'    => 'year',
	);

	/**
	 * Report generate karne ka AJAX endpoint.
	 *
	 * @return void
	 */
	public function ajax_generate() {

		check_ajax_referer( 'pad_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'pad_manage_reports' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'premium-analytics-dashboard-pro' ) ), 403 );
		}

		$period    = isset( $_GET['period'] ) ? sanitize_key( wp_unslash( $_GET['period'] ) ) : 'daily';
		$date_from = isset( $_GET['date_from'] ) ? sanitize_text_field( wp_unslash( $_GET['date_from'] ) ) : '';
		$date_to   = isset( $_GET['date_to'] ) ? sanitize_text_field( wp_unslash( $_GET['date_to'] ) ) : '';

		wp_send_json_success( self::generate( $period, $date_from, $date_to ) );
	}

	/**
	 * Poora report data banata hai.
	 *
	 * @param string $period    daily|weekly|monthly|quarterly|yearly|custom.
	 * @param string $date_from Custom period ke liye start date (Y-m-d).
	 * @param string $date_to   Custom period ke liye end date (Y-m-d).
	 * @return array
	 */
	public static function generate( $period, $date_from = '', $date_to = '' ) {

		if ( ! in_array( $period, self::PERIODS, true ) ) {
			$period = 'daily';
		}

		if ( 'custom' === $period ) {
			$date_from = self::sanitize_date( $date_from );
			$date_to   = self::sanitize_date( $date_to );

			if ( '' === $date_from || '' === $date_to ) {
				$range = PAD_Helper::get_date_range( 'today' );
			} else {
				$range = array(
					'start' => $date_from . ' 00:00:00',
					'end'   => $date_to . ' 23:59:59',
				);
			}
		} else {
			$range = PAD_Helper::get_date_range( self::PERIOD_MAP[ $period ] );
		}

		return array(
			'period'          => $period,
			'range_label'     => mysql2date( 'd M Y', $range['start'] ) . ' — ' . mysql2date( 'd M Y', $range['end'] ),
			'range_start'     => $range['start'],
			'range_end'       => $range['end'],
			'leads_total'     => self::count_leads( $range ),
			'leads_by_status' => self::leads_by_status( $range ),
			'leads_by_form'   => self::leads_by_form( $range ),
			'leads_by_country' => self::leads_by_country( $range ),
			'visitors_total'  => self::count_visitors( $range ),
			'sessions_total'  => self::count_sessions( $range ),
			'pageviews_total' => self::count_pageviews( $range ),
			'avg_session'     => PAD_Stats::format_duration( self::avg_session_duration( $range ) ),
			'bounce_rate'     => self::bounce_rate( $range ) . '%',
			'conversion_rate' => self::conversion_rate( $range ) . '%',
			'traffic_sources' => self::traffic_sources( $range ),
			'daily_trend'     => self::daily_trend( $range ),
		);
	}

	/**
	 * Simple Y-m-d date string sanitize karta hai.
	 *
	 * @param string $date Raw date string.
	 * @return string
	 */
	private static function sanitize_date( $date ) {

		$date = sanitize_text_field( (string) $date );

		if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ) {
			return '';
		}

		return $date;
	}

	/**
	 * Range ke andar total leads.
	 *
	 * @param array $range Start/end.
	 * @return int
	 */
	private static function count_leads( $range ) {
		global $wpdb;

		return (int) $wpdb->get_var(
			$wpdb->prepare( 'SELECT COUNT(id) FROM ' . PAD_Database::table( 'leads' ) . ' WHERE created_at BETWEEN %s AND %s', $range['start'], $range['end'] )
		);
	}

	/**
	 * Status ke hisaab se leads breakdown.
	 *
	 * @param array $range Start/end.
	 * @return array
	 */
	private static function leads_by_status( $range ) {
		global $wpdb;

		return $wpdb->get_results(
			$wpdb->prepare(
				'SELECT status, COUNT(id) AS total FROM ' . PAD_Database::table( 'leads' ) . ' WHERE created_at BETWEEN %s AND %s GROUP BY status ORDER BY total DESC',
				$range['start'],
				$range['end']
			)
		);
	}

	/**
	 * Form ke hisaab se leads breakdown (top 10).
	 *
	 * @param array $range Start/end.
	 * @return array
	 */
	private static function leads_by_form( $range ) {
		global $wpdb;

		return $wpdb->get_results(
			$wpdb->prepare(
				'SELECT form_name, COUNT(id) AS total FROM ' . PAD_Database::table( 'leads' ) . ' WHERE created_at BETWEEN %s AND %s GROUP BY form_name ORDER BY total DESC LIMIT 10',
				$range['start'],
				$range['end']
			)
		);
	}

	/**
	 * Country ke hisaab se leads breakdown (top 5).
	 *
	 * @param array $range Start/end.
	 * @return array
	 */
	private static function leads_by_country( $range ) {
		global $wpdb;

		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT country, COUNT(id) AS total FROM " . PAD_Database::table( 'leads' ) . " WHERE created_at BETWEEN %s AND %s AND country != '' GROUP BY country ORDER BY total DESC LIMIT 5",
				$range['start'],
				$range['end']
			)
		);
	}

	/**
	 * Range ke andar naye track hue unique visitors.
	 *
	 * @param array $range Start/end.
	 * @return int
	 */
	private static function count_visitors( $range ) {
		global $wpdb;

		return (int) $wpdb->get_var(
			$wpdb->prepare( 'SELECT COUNT(id) FROM ' . PAD_Database::table( 'visitors' ) . ' WHERE first_seen BETWEEN %s AND %s', $range['start'], $range['end'] )
		);
	}

	/**
	 * Range ke andar shuru hui sessions.
	 *
	 * @param array $range Start/end.
	 * @return int
	 */
	private static function count_sessions( $range ) {
		global $wpdb;

		return (int) $wpdb->get_var(
			$wpdb->prepare( 'SELECT COUNT(id) FROM ' . PAD_Database::table( 'sessions' ) . ' WHERE started_at BETWEEN %s AND %s', $range['start'], $range['end'] )
		);
	}

	/**
	 * Range ke andar hue pageviews.
	 *
	 * @param array $range Start/end.
	 * @return int
	 */
	private static function count_pageviews( $range ) {
		global $wpdb;

		return (int) $wpdb->get_var(
			$wpdb->prepare( 'SELECT COUNT(id) FROM ' . PAD_Database::table( 'pageviews' ) . ' WHERE viewed_at BETWEEN %s AND %s', $range['start'], $range['end'] )
		);
	}

	/**
	 * Range ke andar complete hui sessions ka average duration (seconds).
	 *
	 * @param array $range Start/end.
	 * @return int
	 */
	private static function avg_session_duration( $range ) {
		global $wpdb;

		$average = $wpdb->get_var(
			$wpdb->prepare(
				'SELECT AVG(duration) FROM ' . PAD_Database::table( 'sessions' ) . ' WHERE started_at BETWEEN %s AND %s AND ended_at IS NOT NULL',
				$range['start'],
				$range['end']
			)
		);

		return null === $average ? 0 : (int) round( (float) $average );
	}

	/**
	 * Range ke andar bounce rate %.
	 *
	 * @param array $range Start/end.
	 * @return float
	 */
	private static function bounce_rate( $range ) {
		global $wpdb;

		$table = PAD_Database::table( 'sessions' );

		$total = (int) $wpdb->get_var(
			$wpdb->prepare( "SELECT COUNT(id) FROM {$table} WHERE started_at BETWEEN %s AND %s", $range['start'], $range['end'] )
		);

		if ( $total <= 0 ) {
			return 0.0;
		}

		$bounced = (int) $wpdb->get_var(
			$wpdb->prepare( "SELECT COUNT(id) FROM {$table} WHERE started_at BETWEEN %s AND %s AND is_bounce = 1", $range['start'], $range['end'] )
		);

		return round( ( $bounced / $total ) * 100, 2 );
	}

	/**
	 * Range ke andar conversion rate % (leads / visitors).
	 *
	 * @param array $range Start/end.
	 * @return float
	 */
	private static function conversion_rate( $range ) {

		$visitors = self::count_visitors( $range );

		if ( $visitors <= 0 ) {
			return 0.0;
		}

		return round( ( self::count_leads( $range ) / $visitors ) * 100, 2 );
	}

	/**
	 * Range ke andar traffic source breakdown.
	 *
	 * @param array $range Start/end.
	 * @return array
	 */
	private static function traffic_sources( $range ) {
		global $wpdb;

		return $wpdb->get_results(
			$wpdb->prepare(
				'SELECT referral_source, COUNT(id) AS total FROM ' . PAD_Database::table( 'visitors' ) . ' WHERE first_seen BETWEEN %s AND %s GROUP BY referral_source ORDER BY total DESC',
				$range['start'],
				$range['end']
			)
		);
	}

	/**
	 * Range ke andar din-wise leads trend (chart ke liye).
	 *
	 * @param array $range Start/end.
	 * @return array{categories:string[],series:array}
	 */
	private static function daily_trend( $range ) {
		global $wpdb;

		$table = PAD_Database::table( 'leads' );

		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT DATE(created_at) AS period, COUNT(id) AS total FROM {$table} WHERE created_at BETWEEN %s AND %s GROUP BY period ORDER BY period ASC",
				$range['start'],
				$range['end']
			)
		);

		$categories = array();
		$data       = array();

		foreach ( $rows as $row ) {
			$categories[] = $row->period;
			$data[]       = (int) $row->total;
		}

		return array(
			'categories' => $categories,
			'series'     => array(
				array(
					'name' => __( 'Leads', 'premium-analytics-dashboard-pro' ),
					'data' => $data,
				),
			),
		);
	}
}
