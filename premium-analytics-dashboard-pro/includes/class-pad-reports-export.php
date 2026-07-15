<?php
/**
 * Reports Export — PDF & CSV
 *
 * Jo bhi report (period/custom range) admin ne generate kiya hai,
 * wahi PAD_Reports::generate() se dobara build karke PDF (multi-section,
 * PAD_PDF_Writer se) ya CSV me export karta hai.
 *
 * @package Premium_Analytics_Dashboard_Pro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class PAD_Reports_Export
 */
class PAD_Reports_Export {

	/**
	 * PDF export.
	 *
	 * @return void
	 */
	public function export_pdf() {

		$this->guard();

		$report   = $this->get_report_from_request();
		$sections = $this->build_sections( $report );

		$writer = new PAD_PDF_Writer();
		$writer->download_sections(
			sprintf(
				/* translators: %s: report date range label */
				__( 'Analytics Report — %s', 'premium-analytics-dashboard-pro' ),
				$report['range_label']
			),
			$sections,
			'report-' . $report['period'] . '-' . gmdate( 'Y-m-d' ) . '.pdf'
		);
	}

	/**
	 * CSV export.
	 *
	 * @return void
	 */
	public function export_csv() {

		$this->guard();

		$report = $this->get_report_from_request();

		nocache_headers();
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="report-' . $report['period'] . '-' . gmdate( 'Y-m-d' ) . '.csv"' );

		$out = fopen( 'php://output', 'w' );

		fputcsv( $out, array( __( 'Metric', 'premium-analytics-dashboard-pro' ), __( 'Value', 'premium-analytics-dashboard-pro' ) ) );
		fputcsv( $out, array( __( 'Period', 'premium-analytics-dashboard-pro' ), $report['range_label'] ) );
		fputcsv( $out, array( __( 'Total Leads', 'premium-analytics-dashboard-pro' ), $report['leads_total'] ) );
		fputcsv( $out, array( __( 'Total Visitors', 'premium-analytics-dashboard-pro' ), $report['visitors_total'] ) );
		fputcsv( $out, array( __( 'Total Sessions', 'premium-analytics-dashboard-pro' ), $report['sessions_total'] ) );
		fputcsv( $out, array( __( 'Total Page Views', 'premium-analytics-dashboard-pro' ), $report['pageviews_total'] ) );
		fputcsv( $out, array( __( 'Avg. Session Duration', 'premium-analytics-dashboard-pro' ), $report['avg_session'] ) );
		fputcsv( $out, array( __( 'Bounce Rate', 'premium-analytics-dashboard-pro' ), $report['bounce_rate'] ) );
		fputcsv( $out, array( __( 'Conversion Rate', 'premium-analytics-dashboard-pro' ), $report['conversion_rate'] ) );

		$this->write_csv_section( $out, __( 'Leads by Status', 'premium-analytics-dashboard-pro' ), array( __( 'Status', 'premium-analytics-dashboard-pro' ), __( 'Total', 'premium-analytics-dashboard-pro' ) ), $report['leads_by_status'], array( 'status', 'total' ) );
		$this->write_csv_section( $out, __( 'Leads by Form', 'premium-analytics-dashboard-pro' ), array( __( 'Form', 'premium-analytics-dashboard-pro' ), __( 'Total', 'premium-analytics-dashboard-pro' ) ), $report['leads_by_form'], array( 'form_name', 'total' ) );
		$this->write_csv_section( $out, __( 'Leads by Country', 'premium-analytics-dashboard-pro' ), array( __( 'Country', 'premium-analytics-dashboard-pro' ), __( 'Total', 'premium-analytics-dashboard-pro' ) ), $report['leads_by_country'], array( 'country', 'total' ) );
		$this->write_csv_section( $out, __( 'Traffic Sources', 'premium-analytics-dashboard-pro' ), array( __( 'Source', 'premium-analytics-dashboard-pro' ), __( 'Total', 'premium-analytics-dashboard-pro' ) ), $report['traffic_sources'], array( 'referral_source', 'total' ) );

		fclose( $out );
		exit;
	}

	/**
	 * CSV me ek labeled sub-section (blank line + heading + header row + data) likhta hai.
	 *
	 * @param resource $out        fopen('php://output') handle.
	 * @param string   $heading    Section heading.
	 * @param string[] $headers    Column headers.
	 * @param array    $rows       wpdb result rows.
	 * @param string[] $props      Row properties jo columns me jaani hain (order headers jaisa).
	 * @return void
	 */
	private function write_csv_section( $out, $heading, $headers, $rows, $props ) {

		fputcsv( $out, array() );
		fputcsv( $out, array( $heading ) );
		fputcsv( $out, $headers );

		foreach ( $rows as $row ) {
			$values = array();
			foreach ( $props as $prop ) {
				$values[] = $row->{$prop};
			}
			fputcsv( $out, $values );
		}
	}

	/**
	 * Report data ko PAD_PDF_Writer::download_sections() ke liye
	 * sections array me convert karta hai.
	 *
	 * @param array $report PAD_Reports::generate() output.
	 * @return array
	 */
	private function build_sections( $report ) {

		$sections   = array();
		$sections[] = array(
			'heading' => __( 'Summary', 'premium-analytics-dashboard-pro' ),
			'rows'    => array(
				sprintf( __( 'Period: %s', 'premium-analytics-dashboard-pro' ), $report['range_label'] ),
				sprintf( __( 'Total Leads: %s', 'premium-analytics-dashboard-pro' ), $report['leads_total'] ),
				sprintf( __( 'Total Visitors: %s', 'premium-analytics-dashboard-pro' ), $report['visitors_total'] ),
				sprintf( __( 'Total Sessions: %s', 'premium-analytics-dashboard-pro' ), $report['sessions_total'] ),
				sprintf( __( 'Total Page Views: %s', 'premium-analytics-dashboard-pro' ), $report['pageviews_total'] ),
				sprintf( __( 'Avg. Session Duration: %s', 'premium-analytics-dashboard-pro' ), $report['avg_session'] ),
				sprintf( __( 'Bounce Rate: %s', 'premium-analytics-dashboard-pro' ), $report['bounce_rate'] ),
				sprintf( __( 'Conversion Rate: %s', 'premium-analytics-dashboard-pro' ), $report['conversion_rate'] ),
			),
		);

		$sections[] = array(
			'heading' => __( 'Leads by Status', 'premium-analytics-dashboard-pro' ),
			'headers' => array( __( 'Status', 'premium-analytics-dashboard-pro' ), __( 'Total', 'premium-analytics-dashboard-pro' ) ),
			'widths'  => array( 200, 100 ),
			'rows'    => $this->rows_from( $report['leads_by_status'], array( 'status', 'total' ) ),
		);

		$sections[] = array(
			'heading' => __( 'Leads by Form', 'premium-analytics-dashboard-pro' ),
			'headers' => array( __( 'Form', 'premium-analytics-dashboard-pro' ), __( 'Total', 'premium-analytics-dashboard-pro' ) ),
			'widths'  => array( 400, 100 ),
			'rows'    => $this->rows_from( $report['leads_by_form'], array( 'form_name', 'total' ) ),
		);

		$sections[] = array(
			'heading' => __( 'Leads by Country', 'premium-analytics-dashboard-pro' ),
			'headers' => array( __( 'Country', 'premium-analytics-dashboard-pro' ), __( 'Total', 'premium-analytics-dashboard-pro' ) ),
			'widths'  => array( 300, 100 ),
			'rows'    => $this->rows_from( $report['leads_by_country'], array( 'country', 'total' ) ),
		);

		$sections[] = array(
			'heading' => __( 'Traffic Sources', 'premium-analytics-dashboard-pro' ),
			'headers' => array( __( 'Source', 'premium-analytics-dashboard-pro' ), __( 'Total', 'premium-analytics-dashboard-pro' ) ),
			'widths'  => array( 300, 100 ),
			'rows'    => $this->rows_from( $report['traffic_sources'], array( 'referral_source', 'total' ) ),
		);

		return $sections;
	}

	/**
	 * wpdb result objects ko plain value-arrays me convert karta hai.
	 *
	 * @param array    $rows  wpdb result rows.
	 * @param string[] $props Row properties (order maintained).
	 * @return array
	 */
	private function rows_from( $rows, $props ) {

		$out = array();

		foreach ( $rows as $row ) {
			$values = array();
			foreach ( $props as $prop ) {
				$values[] = $row->{$prop};
			}
			$out[] = $values;
		}

		return $out;
	}

	/**
	 * Current $_GET se report dobara generate karta hai.
	 *
	 * @return array
	 */
	private function get_report_from_request() {

		$period    = isset( $_GET['period'] ) ? sanitize_key( wp_unslash( $_GET['period'] ) ) : 'daily';
		$date_from = isset( $_GET['date_from'] ) ? sanitize_text_field( wp_unslash( $_GET['date_from'] ) ) : '';
		$date_to   = isset( $_GET['date_to'] ) ? sanitize_text_field( wp_unslash( $_GET['date_to'] ) ) : '';

		return PAD_Reports::generate( $period, $date_from, $date_to );
	}

	/**
	 * Nonce aur capability verify karta hai.
	 *
	 * @return void
	 */
	private function guard() {

		check_admin_referer( 'pad_export_reports' );

		if ( ! current_user_can( 'pad_manage_reports' ) ) {
			wp_die( esc_html__( 'Report export karne ki aapke paas permission nahi hai.', 'premium-analytics-dashboard-pro' ) );
		}
	}
}
