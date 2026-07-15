<?php
/**
 * Leads Export — CSV, Excel, PDF, Print
 *
 * Jo bhi filters (search/status/country/form/date) admin ne Leads
 * table par lagaye hain, wahi exports par bhi apply hote hain — PAD_Leads_Table
 * ka wahi shared query engine yahan reuse hota hai, taaki "jo screen
 * par dikh raha hai wahi export ho" hamesha guaranteed rahe.
 *
 * @package Premium_Analytics_Dashboard_Pro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class PAD_Leads_Export
 */
class PAD_Leads_Export {

	/**
	 * Ek export me zyada se zyada kitni rows include hongi — bahut
	 * bade datasets par bhi PHP memory/time safe rakhne ke liye.
	 *
	 * @var int
	 */
	const MAX_EXPORT_ROWS = 5000;

	/**
	 * CSV export — poore FORM TRACKING spec ke saare columns.
	 *
	 * @return void
	 */
	public function export_csv() {

		$this->guard();

		$rows = $this->get_filtered_leads();

		nocache_headers();
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="leads-export-' . gmdate( 'Y-m-d' ) . '.csv"' );

		$out = fopen( 'php://output', 'w' );
		fputcsv( $out, $this->get_columns() );

		foreach ( $rows as $lead ) {
			fputcsv( $out, $this->get_row_values( $lead ) );
		}

		fclose( $out );
		exit;
	}

	/**
	 * "Excel" export — ek HTML table jise .xls extension aur MS Excel
	 * MIME type ke saath serve karte hain. Yeh standard technique hai
	 * jo Excel/Numbers/Google Sheets sab me directly khulti hai —
	 * bina kisi PhpSpreadsheet/Composer library ke.
	 *
	 * @return void
	 */
	public function export_excel() {

		$this->guard();

		$rows = $this->get_filtered_leads();

		nocache_headers();
		header( 'Content-Type: application/vnd.ms-excel; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="leads-export-' . gmdate( 'Y-m-d' ) . '.xls"' );

		echo "<html><head><meta charset=\"utf-8\"></head><body><table border='1'>";
		echo '<tr>';
		foreach ( $this->get_columns() as $column ) {
			echo '<th>' . esc_html( $column ) . '</th>';
		}
		echo '</tr>';

		foreach ( $rows as $lead ) {
			echo '<tr>';
			foreach ( $this->get_row_values( $lead ) as $value ) {
				echo '<td>' . esc_html( $value ) . "</td>";
			}
			echo '</tr>';
		}

		echo '</table></body></html>';
		exit;
	}

	/**
	 * PDF export — PAD_PDF_Writer (hand-written, no library) se.
	 * Readability ke liye columns ka ek chhota, curated set use hota hai.
	 *
	 * @return void
	 */
	public function export_pdf() {

		$this->guard();

		$rows        = $this->get_filtered_leads();
		$pdf_headers = array(
			__( 'ID', 'premium-analytics-dashboard-pro' ),
			__( 'Form', 'premium-analytics-dashboard-pro' ),
			__( 'Name', 'premium-analytics-dashboard-pro' ),
			__( 'Email', 'premium-analytics-dashboard-pro' ),
			__( 'Phone', 'premium-analytics-dashboard-pro' ),
			__( 'Country', 'premium-analytics-dashboard-pro' ),
			__( 'Status', 'premium-analytics-dashboard-pro' ),
			__( 'Date', 'premium-analytics-dashboard-pro' ),
		);
		$col_widths  = array( 35, 100, 120, 170, 85, 90, 70, 100 );

		$pdf_rows = array();

		foreach ( $rows as $lead ) {
			$pdf_rows[] = array(
				$lead->id,
				$lead->form_name,
				trim( $lead->first_name . ' ' . $lead->last_name ),
				$lead->email,
				$lead->phone,
				$lead->country,
				ucfirst( $lead->status ),
				mysql2date( 'd M Y H:i', $lead->created_at ),
			);
		}

		$writer = new PAD_PDF_Writer();
		$writer->download(
			__( 'Leads Export', 'premium-analytics-dashboard-pro' ),
			$pdf_headers,
			$col_widths,
			$pdf_rows,
			'leads-export-' . gmdate( 'Y-m-d' ) . '.pdf'
		);
	}

	/**
	 * Print-friendly HTML view — browser ka print-to-PDF/printer dialog
	 * turant khul jaata hai.
	 *
	 * @return void
	 */
	public function print_view() {

		$this->guard();

		$rows    = $this->get_filtered_leads();
		$columns = $this->get_columns();
		?>
		<!DOCTYPE html>
		<html>
		<head>
			<meta charset="utf-8">
			<title><?php esc_html_e( 'Leads — Print', 'premium-analytics-dashboard-pro' ); ?></title>
			<style>
				body { font-family: Arial, sans-serif; font-size: 11px; color: #111; }
				table { width: 100%; border-collapse: collapse; }
				th, td { border: 1px solid #999; padding: 4px 6px; text-align: left; }
				th { background: #eee; }
				h1 { font-size: 16px; }
			</style>
		</head>
		<body>
			<h1><?php esc_html_e( 'Premium Analytics Dashboard Pro — Leads', 'premium-analytics-dashboard-pro' ); ?></h1>
			<table>
				<thead>
					<tr>
						<?php foreach ( $columns as $column ) : ?>
							<th><?php echo esc_html( $column ); ?></th>
						<?php endforeach; ?>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $rows as $lead ) : ?>
						<tr>
							<?php foreach ( $this->get_row_values( $lead ) as $value ) : ?>
								<td><?php echo esc_html( $value ); ?></td>
							<?php endforeach; ?>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
			<script>window.onload = function () { window.print(); };</script>
		</body>
		</html>
		<?php
		exit;
	}

	/**
	 * Nonce aur capability verify karta hai — saare export entry-points
	 * isi se guard hote hain.
	 *
	 * @return void
	 */
	private function guard() {

		check_admin_referer( 'pad_export_leads' );

		if ( ! current_user_can( 'pad_export_leads' ) ) {
			wp_die( esc_html__( 'Export karne ki aapke paas permission nahi hai.', 'premium-analytics-dashboard-pro' ) );
		}
	}

	/**
	 * Current $_GET filters se poore matching leads laata hai (capped).
	 *
	 * @return array
	 */
	private function get_filtered_leads() {

		$filters = PAD_Leads_Table::sanitize_filters( wp_unslash( $_GET ) );
		$result  = PAD_Leads_Table::query_leads( $filters, 1, self::MAX_EXPORT_ROWS );

		return $result['rows'];
	}

	/**
	 * CSV/Excel export ke column headers — FORM TRACKING spec ke
	 * saare captured fields.
	 *
	 * @return string[]
	 */
	private function get_columns() {

		return array(
			__( 'Submission ID', 'premium-analytics-dashboard-pro' ),
			__( 'Form ID', 'premium-analytics-dashboard-pro' ),
			__( 'Form Name', 'premium-analytics-dashboard-pro' ),
			__( 'Submission Date', 'premium-analytics-dashboard-pro' ),
			__( 'Submission Time', 'premium-analytics-dashboard-pro' ),
			__( 'First Name', 'premium-analytics-dashboard-pro' ),
			__( 'Last Name', 'premium-analytics-dashboard-pro' ),
			__( 'Email', 'premium-analytics-dashboard-pro' ),
			__( 'Phone', 'premium-analytics-dashboard-pro' ),
			__( 'Country', 'premium-analytics-dashboard-pro' ),
			__( 'City', 'premium-analytics-dashboard-pro' ),
			__( 'State', 'premium-analytics-dashboard-pro' ),
			__( 'Message', 'premium-analytics-dashboard-pro' ),
			__( 'Uploaded Files', 'premium-analytics-dashboard-pro' ),
			__( 'IP Address', 'premium-analytics-dashboard-pro' ),
			__( 'Browser', 'premium-analytics-dashboard-pro' ),
			__( 'Operating System', 'premium-analytics-dashboard-pro' ),
			__( 'Device', 'premium-analytics-dashboard-pro' ),
			__( 'Referrer', 'premium-analytics-dashboard-pro' ),
			__( 'Landing Page', 'premium-analytics-dashboard-pro' ),
			__( 'Current URL', 'premium-analytics-dashboard-pro' ),
			__( 'UTM Source', 'premium-analytics-dashboard-pro' ),
			__( 'UTM Medium', 'premium-analytics-dashboard-pro' ),
			__( 'UTM Campaign', 'premium-analytics-dashboard-pro' ),
			__( 'UTM Term', 'premium-analytics-dashboard-pro' ),
			__( 'UTM Content', 'premium-analytics-dashboard-pro' ),
			__( 'Status', 'premium-analytics-dashboard-pro' ),
			__( 'Custom Fields', 'premium-analytics-dashboard-pro' ),
		);
	}

	/**
	 * Ek lead row ko get_columns() ke exact order me values array me convert karta hai.
	 *
	 * @param object $lead wpdb row.
	 * @return array
	 */
	private function get_row_values( $lead ) {

		$uploaded_files = json_decode( (string) $lead->uploaded_files, true );
		$uploaded_files = is_array( $uploaded_files ) ? implode( ', ', $uploaded_files ) : '';

		$meta_pairs = array();

		foreach ( PAD_Leads_Table::get_meta( (int) $lead->id ) as $meta ) {
			$meta_pairs[] = $meta->meta_key . ': ' . $meta->meta_value;
		}

		return array(
			$lead->id,
			$lead->form_id,
			$lead->form_name,
			$lead->submission_date,
			$lead->submission_time,
			$lead->first_name,
			$lead->last_name,
			$lead->email,
			$lead->phone,
			$lead->country,
			$lead->city,
			$lead->state,
			$lead->message,
			$uploaded_files,
			$lead->ip_address,
			$lead->browser,
			$lead->os,
			$lead->device,
			$lead->referrer,
			$lead->landing_page,
			$lead->current_url,
			$lead->utm_source,
			$lead->utm_medium,
			$lead->utm_campaign,
			$lead->utm_term,
			$lead->utm_content,
			$lead->status,
			implode( '; ', $meta_pairs ),
		);
	}
}
