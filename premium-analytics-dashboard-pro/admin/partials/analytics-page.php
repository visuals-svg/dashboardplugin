<?php
/**
 * Analytics Page
 *
 * Deep-dive interactive charts (ApexCharts, locally vendored):
 * Country, City, Browser, Device, Operating System analytics aur
 * Lead Conversion trend. Har chart apna data `pad_get_chart_data`
 * AJAX endpoint (PAD_Charts) se real-time fetch karta hai.
 *
 * @package Premium_Analytics_Dashboard_Pro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$pad_current_slug = 'pad-analytics';
$pad_page_title   = __( 'Analytics', 'premium-analytics-dashboard-pro' );

require __DIR__ . '/layout-header.php';
?>

<section class="pad-charts-grid">
	<div class="pad-panel pad-panel-wide">
		<div class="pad-panel-head">
			<h2><?php esc_html_e( 'Lead Conversion', 'premium-analytics-dashboard-pro' ); ?></h2>
			<p><?php esc_html_e( 'Pichhle 14 din — Visitors vs Leads.', 'premium-analytics-dashboard-pro' ); ?></p>
		</div>
		<div id="pad-chart-conversion" class="pad-chart-placeholder" data-chart="lead-conversion"></div>
	</div>

	<div class="pad-panel">
		<div class="pad-panel-head"><h2><?php esc_html_e( 'Country Analytics', 'premium-analytics-dashboard-pro' ); ?></h2></div>
		<div id="pad-chart-country-full" class="pad-chart-placeholder" data-chart="country-analytics"></div>
	</div>

	<div class="pad-panel">
		<div class="pad-panel-head"><h2><?php esc_html_e( 'City Analytics', 'premium-analytics-dashboard-pro' ); ?></h2></div>
		<div id="pad-chart-city" class="pad-chart-placeholder" data-chart="city-analytics"></div>
	</div>

	<div class="pad-panel">
		<div class="pad-panel-head"><h2><?php esc_html_e( 'Browser Analytics', 'premium-analytics-dashboard-pro' ); ?></h2></div>
		<div id="pad-chart-browser" class="pad-chart-placeholder" data-chart="browser-analytics"></div>
	</div>

	<div class="pad-panel">
		<div class="pad-panel-head"><h2><?php esc_html_e( 'Device Analytics', 'premium-analytics-dashboard-pro' ); ?></h2></div>
		<div id="pad-chart-device-full" class="pad-chart-placeholder" data-chart="device-analytics"></div>
	</div>

	<div class="pad-panel">
		<div class="pad-panel-head"><h2><?php esc_html_e( 'Operating System Analytics', 'premium-analytics-dashboard-pro' ); ?></h2></div>
		<div id="pad-chart-os" class="pad-chart-placeholder" data-chart="os-analytics"></div>
	</div>
</section>

<?php require __DIR__ . '/layout-footer.php'; ?>
