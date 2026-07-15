<?php
/**
 * Reports Page — Report Generation
 *
 * Daily/Weekly/Monthly/Quarterly/Yearly/Custom Date Range — har period
 * ke liye ek poora business report AJAX se generate hota hai
 * (PAD_Reports), aur PDF/CSV export admin-post.php ke through.
 *
 * @package Premium_Analytics_Dashboard_Pro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$pad_current_slug = 'pad-reports';
$pad_page_title   = __( 'Reports', 'premium-analytics-dashboard-pro' );

require __DIR__ . '/layout-header.php';

$pad_export_pdf_url = wp_nonce_url(
	add_query_arg( array( 'action' => 'pad_export_reports_pdf' ), admin_url( 'admin-post.php' ) ),
	'pad_export_reports'
);

$pad_export_csv_url = wp_nonce_url(
	add_query_arg( array( 'action' => 'pad_export_reports_csv' ), admin_url( 'admin-post.php' ) ),
	'pad_export_reports'
);
?>

<div id="pad-reports-app" class="pad-leads-app" data-export-pdf-url="<?php echo esc_url( $pad_export_pdf_url ); ?>" data-export-csv-url="<?php echo esc_url( $pad_export_csv_url ); ?>">

	<section class="pad-panel">
		<div class="pad-panel-head">
			<h2><?php esc_html_e( 'Generate Report', 'premium-analytics-dashboard-pro' ); ?></h2>
		</div>

		<div class="pad-chart-tabs pad-period-tabs">
			<button type="button" class="is-active" data-period="daily"><?php esc_html_e( 'Daily', 'premium-analytics-dashboard-pro' ); ?></button>
			<button type="button" data-period="weekly"><?php esc_html_e( 'Weekly', 'premium-analytics-dashboard-pro' ); ?></button>
			<button type="button" data-period="monthly"><?php esc_html_e( 'Monthly', 'premium-analytics-dashboard-pro' ); ?></button>
			<button type="button" data-period="quarterly"><?php esc_html_e( 'Quarterly', 'premium-analytics-dashboard-pro' ); ?></button>
			<button type="button" data-period="yearly"><?php esc_html_e( 'Yearly', 'premium-analytics-dashboard-pro' ); ?></button>
			<button type="button" data-period="custom"><?php esc_html_e( 'Custom Range', 'premium-analytics-dashboard-pro' ); ?></button>
		</div>

		<div class="pad-form-row" id="pad-custom-range-row" hidden>
			<label for="pad-report-from"><?php esc_html_e( 'From', 'premium-analytics-dashboard-pro' ); ?></label>
			<input type="date" id="pad-report-from" />
			<label for="pad-report-to"><?php esc_html_e( 'To', 'premium-analytics-dashboard-pro' ); ?></label>
			<input type="date" id="pad-report-to" />
		</div>

		<div class="pad-form-actions">
			<button type="button" id="pad-generate-report" class="button button-primary button-hero"><?php esc_html_e( 'Generate Report', 'premium-analytics-dashboard-pro' ); ?></button>
			<a href="#" id="pad-report-export-pdf" class="button" target="_blank" rel="noopener" hidden><?php esc_html_e( 'Export PDF', 'premium-analytics-dashboard-pro' ); ?></a>
			<a href="#" id="pad-report-export-csv" class="button" target="_blank" rel="noopener" hidden><?php esc_html_e( 'Export CSV', 'premium-analytics-dashboard-pro' ); ?></a>
		</div>
	</section>

	<div id="pad-report-results"></div>

</div>

<?php require __DIR__ . '/layout-footer.php'; ?>
