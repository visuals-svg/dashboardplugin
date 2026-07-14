<?php
/**
 * Chart Data Layer
 *
 * Har interactive chart ke liye real, prepared-SQL query — koi bhi
 * chart fake/sample data se render nahi hota. Har method ek fixed
 * JSON-ready array shape return karta hai jo `assets/js/pad-charts.js`
 * seedha ApexCharts ko de deta hai.
 *
 * @package Premium_Analytics_Dashboard_Pro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class PAD_Charts
 */
class PAD_Charts {

	/**
	 * Leads Over Time — daily/weekly/monthly/yearly granularity,
	 * missing periods bhi 0 ke saath fill kiye jaate hain taaki chart
	 * me gaps na dikhein.
	 *
	 * @param string $granularity daily|weekly|monthly|yearly.
	 * @return array{categories:string[],series:array}
	 */
	public static function get_leads_over_time( $granularity = 'daily' ) {
		global $wpdb;

		$table    = PAD_Database::table( 'leads' );
		$timezone = wp_timezone();
		$now      = new DateTime( 'now', $timezone );

		$buckets = array();

		switch ( $granularity ) {

			case 'yearly':
				$sql_expr = 'YEAR(created_at)';
				for ( $i = 4; $i >= 0; $i-- ) {
					$d                            = ( clone $now )->modify( "-{$i} years" );
					$buckets[ $d->format( 'Y' ) ] = 0;
				}
				break;

			case 'monthly':
				$sql_expr = "DATE_FORMAT(created_at, '%Y-%m')";
				for ( $i = 11; $i >= 0; $i-- ) {
					$d                              = ( clone $now )->modify( "-{$i} months" );
					$buckets[ $d->format( 'Y-m' ) ] = 0;
				}
				break;

			case 'weekly':
				$sql_expr = "DATE_FORMAT(created_at, '%x-W%v')";
				for ( $i = 11; $i >= 0; $i-- ) {
					$d                                = ( clone $now )->modify( "-{$i} weeks" );
					$buckets[ $d->format( 'o-\WW' ) ] = 0;
				}
				break;

			case 'daily':
			default:
				$sql_expr = 'DATE(created_at)';
				for ( $i = 13; $i >= 0; $i-- ) {
					$d                                = ( clone $now )->modify( "-{$i} days" );
					$buckets[ $d->format( 'Y-m-d' ) ] = 0;
				}
				break;
		}

		$rows = $wpdb->get_results( "SELECT {$sql_expr} AS period, COUNT(id) AS total FROM {$table} GROUP BY period" );

		foreach ( $rows as $row ) {
			if ( array_key_exists( $row->period, $buckets ) ) {
				$buckets[ $row->period ] = (int) $row->total;
			}
		}

		return array(
			'categories' => array_keys( $buckets ),
			'series'     => array(
				array(
					'name' => __( 'Leads', 'premium-analytics-dashboard-pro' ),
					'data' => array_values( $buckets ),
				),
			),
		);
	}

	/**
	 * Traffic Sources — donut chart shape (labels + flat series).
	 *
	 * @return array{labels:string[],series:int[]}
	 */
	public static function get_traffic_sources() {
		global $wpdb;

		$table = PAD_Database::table( 'visitors' );
		$rows  = $wpdb->get_results( "SELECT referral_source, COUNT(id) AS total FROM {$table} GROUP BY referral_source ORDER BY total DESC" );

		return self::to_label_series_shape( $rows, 'referral_source' );
	}

	/**
	 * Generic Top-N breakdown (country/city/browser/os) — bar chart shape.
	 *
	 * @param string $column Column to group by.
	 * @param int    $limit  Kitne top results chahiye.
	 * @return array{categories:string[],series:array}
	 */
	public static function get_visitor_breakdown( $column, $limit = 8 ) {
		global $wpdb;

		$allowed = array( 'country', 'city', 'browser', 'device', 'os' );

		if ( ! in_array( $column, $allowed, true ) ) {
			return array( 'categories' => array(), 'series' => array() );
		}

		$table = PAD_Database::table( 'visitors' );

		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT {$column} AS label, COUNT(id) AS total FROM {$table} WHERE {$column} != '' GROUP BY {$column} ORDER BY total DESC LIMIT %d",
				$limit
			)
		);

		$categories = array();
		$data       = array();

		foreach ( $rows as $row ) {
			$categories[] = $row->label;
			$data[]       = (int) $row->total;
		}

		return array(
			'categories' => $categories,
			'series'     => array(
				array(
					'name' => __( 'Visitors', 'premium-analytics-dashboard-pro' ),
					'data' => $data,
				),
			),
		);
	}

	/**
	 * Lead Conversion — pichhle 14 din, Visitors (sessions) vs Leads
	 * dono ek hi timeline par, taaki conversion trend visually dikhe.
	 *
	 * @return array{categories:string[],series:array}
	 */
	public static function get_lead_conversion() {
		global $wpdb;

		$timezone   = wp_timezone();
		$now        = new DateTime( 'now', $timezone );
		$days       = array();

		for ( $i = 13; $i >= 0; $i-- ) {
			$d                          = ( clone $now )->modify( "-{$i} days" );
			$days[ $d->format( 'Y-m-d' ) ] = array(
				'visitors' => 0,
				'leads'    => 0,
			);
		}

		$leads_table    = PAD_Database::table( 'leads' );
		$sessions_table = PAD_Database::table( 'sessions' );

		$lead_rows = $wpdb->get_results( "SELECT DATE(created_at) AS period, COUNT(id) AS total FROM {$leads_table} GROUP BY period" );

		foreach ( $lead_rows as $row ) {
			if ( isset( $days[ $row->period ] ) ) {
				$days[ $row->period ]['leads'] = (int) $row->total;
			}
		}

		$visitor_rows = $wpdb->get_results( "SELECT DATE(started_at) AS period, COUNT(DISTINCT visitor_id) AS total FROM {$sessions_table} GROUP BY period" );

		foreach ( $visitor_rows as $row ) {
			if ( isset( $days[ $row->period ] ) ) {
				$days[ $row->period ]['visitors'] = (int) $row->total;
			}
		}

		$visitors_series = array();
		$leads_series     = array();

		foreach ( $days as $values ) {
			$visitors_series[] = $values['visitors'];
			$leads_series[]    = $values['leads'];
		}

		return array(
			'categories' => array_keys( $days ),
			'series'     => array(
				array(
					'name' => __( 'Visitors', 'premium-analytics-dashboard-pro' ),
					'data' => $visitors_series,
				),
				array(
					'name' => __( 'Leads', 'premium-analytics-dashboard-pro' ),
					'data' => $leads_series,
				),
			),
		);
	}

	/**
	 * Hourly Activity Heatmap — Sunday-Saturday x 0-23 hours, pageviews
	 * count se. Missing hours 0 se fill hote hain.
	 *
	 * @return array{series:array}
	 */
	public static function get_hourly_heatmap() {
		global $wpdb;

		$table = PAD_Database::table( 'pageviews' );

		$day_labels = array(
			1 => __( 'Sun', 'premium-analytics-dashboard-pro' ),
			2 => __( 'Mon', 'premium-analytics-dashboard-pro' ),
			3 => __( 'Tue', 'premium-analytics-dashboard-pro' ),
			4 => __( 'Wed', 'premium-analytics-dashboard-pro' ),
			5 => __( 'Thu', 'premium-analytics-dashboard-pro' ),
			6 => __( 'Fri', 'premium-analytics-dashboard-pro' ),
			7 => __( 'Sat', 'premium-analytics-dashboard-pro' ),
		);

		$matrix = array();

		foreach ( $day_labels as $day_index => $day_label ) {
			$matrix[ $day_index ] = array_fill( 0, 24, 0 );
		}

		$rows = $wpdb->get_results( "SELECT DAYOFWEEK(viewed_at) AS dow, HOUR(viewed_at) AS hr, COUNT(id) AS total FROM {$table} GROUP BY dow, hr" );

		foreach ( $rows as $row ) {
			$dow = (int) $row->dow;
			$hr  = (int) $row->hr;

			if ( isset( $matrix[ $dow ][ $hr ] ) ) {
				$matrix[ $dow ][ $hr ] = (int) $row->total;
			}
		}

		$series = array();

		foreach ( $day_labels as $day_index => $day_label ) {
			$points = array();

			foreach ( $matrix[ $day_index ] as $hour => $total ) {
				$points[] = array(
					'x' => sprintf( '%02d:00', $hour ),
					'y' => $total,
				);
			}

			$series[] = array(
				'name' => $day_label,
				'data' => $points,
			);
		}

		return array( 'series' => $series );
	}

	/**
	 * DB rows ko { referral_source/label, total } se ApexCharts
	 * pie/donut ke liye chahiye wale { labels, series } shape me convert karta hai.
	 *
	 * @param array  $rows       $wpdb->get_results() output.
	 * @param string $label_prop Row property jo label ke roop me use hogi.
	 * @return array{labels:string[],series:int[]}
	 */
	private static function to_label_series_shape( $rows, $label_prop ) {

		$labels = array();
		$series = array();

		foreach ( $rows as $row ) {
			$labels[] = $row->{$label_prop};
			$series[] = (int) $row->total;
		}

		return array(
			'labels' => $labels,
			'series' => $series,
		);
	}
}
