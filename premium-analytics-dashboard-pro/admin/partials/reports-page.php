<?php
/**
 * Reports Page (Phase 1 shell)
 *
 * Daily/Weekly/Monthly/Quarterly/Yearly/Custom report generation
 * "Reports" phase me PDF/CSV export ke saath aayega. Abhi selector
 * UI aur real summary numbers (PAD_Stats) diye gaye hain.
 *
 * @package Premium_Analytics_Dashboard_Pro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$pad_current_slug = 'pad-reports';
$pad_page_title   = __( 'Reports', 'premium-analytics-dashboard-pro' );

require __DIR__ . '/layout-header.php';

$pad_report_periods = array(
	'today'     => __( 'Daily', 'premium-analytics-dashboard-pro' ),
	'week'      => __( 'Weekly', 'premium-analytics-dashboard-pro' ),
	'month'     => __( 'Monthly', 'premium-analytics-dashboard-pro' ),
	'year'      => __( 'Yearly', 'premium-analytics-dashboard-pro' ),
);
?>

<section class="pad-panel">
	<div class="pad-panel-head">
		<h2><?php esc_html_e( 'Generate Report', 'premium-analytics-dashboard-pro' ); ?></h2>
		<p><?php esc_html_e( 'Report period chunein — PDF/CSV export agle phase me is form se juda hoga.', 'premium-analytics-dashboard-pro' ); ?></p>
	</div>

	<div class="pad-report-selector">
		<?php foreach ( $pad_report_periods as $pad_key => $pad_label ) : ?>
			<div class="pad-report-card">
				<h3><?php echo esc_html( $pad_label ); ?></h3>
				<p class="pad-report-value"><?php echo esc_html( PAD_Stats::get_leads_count( $pad_key ) ); ?></p>
				<span><?php esc_html_e( 'Leads captured', 'premium-analytics-dashboard-pro' ); ?></span>
			</div>
		<?php endforeach; ?>
	</div>

	<div class="pad-report-custom">
		<h3><?php esc_html_e( 'Custom Date Range', 'premium-analytics-dashboard-pro' ); ?></h3>
		<div class="pad-form-row">
			<label for="pad-report-from"><?php esc_html_e( 'From', 'premium-analytics-dashboard-pro' ); ?></label>
			<input type="date" id="pad-report-from" name="pad_report_from" />
			<label for="pad-report-to"><?php esc_html_e( 'To', 'premium-analytics-dashboard-pro' ); ?></label>
			<input type="date" id="pad-report-to" name="pad_report_to" />
			<button type="button" class="button button-primary" disabled>
				<?php esc_html_e( 'Generate (available in Reports phase)', 'premium-analytics-dashboard-pro' ); ?>
			</button>
		</div>
	</div>
</section>

<?php require __DIR__ . '/layout-footer.php'; ?>
