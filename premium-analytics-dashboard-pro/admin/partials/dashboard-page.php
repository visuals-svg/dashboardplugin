<?php
/**
 * Dashboard Page
 *
 * Premium overview screen — stat cards real DB queries (PAD_Stats) se
 * bharti hain aur chart containers Phase (Charts) ke ApexCharts wiring
 * ke liye ready structure me render hote hain.
 *
 * @package Premium_Analytics_Dashboard_Pro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$pad_current_slug = 'premium-analytics-dashboard';
$pad_page_title   = __( 'Dashboard', 'premium-analytics-dashboard-pro' );

require __DIR__ . '/layout-header.php';

$pad_cards = array(
	array(
		'label' => __( "Today's Leads", 'premium-analytics-dashboard-pro' ),
		'value' => PAD_Stats::get_leads_count( 'today' ),
		'icon'  => 'dashicons-calendar-alt',
	),
	array(
		'label' => __( 'Yesterday Leads', 'premium-analytics-dashboard-pro' ),
		'value' => PAD_Stats::get_leads_count( 'yesterday' ),
		'icon'  => 'dashicons-backup',
	),
	array(
		'label' => __( 'Weekly Leads', 'premium-analytics-dashboard-pro' ),
		'value' => PAD_Stats::get_leads_count( 'week' ),
		'icon'  => 'dashicons-chart-bar',
	),
	array(
		'label' => __( 'Monthly Leads', 'premium-analytics-dashboard-pro' ),
		'value' => PAD_Stats::get_leads_count( 'month' ),
		'icon'  => 'dashicons-chart-line',
	),
	array(
		'label' => __( 'Yearly Leads', 'premium-analytics-dashboard-pro' ),
		'value' => PAD_Stats::get_leads_count( 'year' ),
		'icon'  => 'dashicons-chart-area',
	),
	array(
		'label' => __( 'Total Leads', 'premium-analytics-dashboard-pro' ),
		'value' => PAD_Stats::get_leads_count( 'all' ),
		'icon'  => 'dashicons-groups',
	),
	array(
		'label' => __( 'Total Visitors', 'premium-analytics-dashboard-pro' ),
		'value' => PAD_Stats::get_total_visitors(),
		'icon'  => 'dashicons-visibility',
	),
	array(
		'label' => __( 'Visitors Online', 'premium-analytics-dashboard-pro' ),
		'value' => PAD_Stats::get_visitors_online(),
		'icon'  => 'dashicons-admin-users',
		'live'  => true,
	),
	array(
		'label' => __( 'Conversion Rate', 'premium-analytics-dashboard-pro' ),
		'value' => PAD_Stats::get_conversion_rate() . '%',
		'icon'  => 'dashicons-chart-pie',
	),
	array(
		'label' => __( 'Average Session', 'premium-analytics-dashboard-pro' ),
		'value' => PAD_Stats::format_duration( PAD_Stats::get_average_session_duration() ),
		'icon'  => 'dashicons-clock',
	),
	array(
		'label' => __( 'Bounce Rate', 'premium-analytics-dashboard-pro' ),
		'value' => PAD_Stats::get_bounce_rate() . '%',
		'icon'  => 'dashicons-external',
	),
);

$pad_cf7_active = class_exists( 'WPCF7_ContactForm' );
?>

<?php if ( ! $pad_cf7_active ) : ?>
	<div class="pad-notice pad-notice-warning">
		<span class="dashicons dashicons-warning"></span>
		<p>
			<?php esc_html_e( 'Contact Form 7 detect nahi hua. Lead capture ke liye Contact Form 7 plugin install/activate karein — is dashboard ki baaki saari analytics features (visitors, dashboard cards) is ke bina bhi kaam karti rahengi.', 'premium-analytics-dashboard-pro' ); ?>
		</p>
	</div>
<?php else : ?>
	<div class="pad-notice pad-notice-success">
		<span class="dashicons dashicons-yes-alt"></span>
		<p><?php esc_html_e( 'Contact Form 7 detect ho gaya hai. Naye submissions is dashboard me automatically track honge.', 'premium-analytics-dashboard-pro' ); ?></p>
	</div>
<?php endif; ?>

<section class="pad-cards-grid">
	<?php foreach ( $pad_cards as $pad_index => $pad_card ) : ?>
		<div class="pad-card" style="animation-delay: <?php echo esc_attr( $pad_index * 0.04 ); ?>s;">
			<div class="pad-card-icon">
				<span class="dashicons <?php echo esc_attr( $pad_card['icon'] ); ?>"></span>
			</div>
			<div class="pad-card-body">
				<span class="pad-card-value">
					<?php echo esc_html( $pad_card['value'] ); ?>
					<?php if ( ! empty( $pad_card['live'] ) ) : ?>
						<i class="pad-live-dot" title="<?php esc_attr_e( 'Live', 'premium-analytics-dashboard-pro' ); ?>"></i>
					<?php endif; ?>
				</span>
				<span class="pad-card-label"><?php echo esc_html( $pad_card['label'] ); ?></span>
			</div>
		</div>
	<?php endforeach; ?>
</section>

<section class="pad-charts-grid">
	<div class="pad-panel pad-panel-wide">
		<div class="pad-panel-head">
			<h2><?php esc_html_e( 'Leads Over Time', 'premium-analytics-dashboard-pro' ); ?></h2>
			<p><?php esc_html_e( 'Daily / Weekly / Monthly / Yearly interactive chart yahan render hoga.', 'premium-analytics-dashboard-pro' ); ?></p>
		</div>
		<div id="pad-chart-leads" class="pad-chart-placeholder" data-chart="leads-over-time"></div>
	</div>

	<div class="pad-panel">
		<div class="pad-panel-head">
			<h2><?php esc_html_e( 'Traffic Sources', 'premium-analytics-dashboard-pro' ); ?></h2>
		</div>
		<div id="pad-chart-traffic" class="pad-chart-placeholder" data-chart="traffic-sources"></div>
	</div>

	<div class="pad-panel">
		<div class="pad-panel-head">
			<h2><?php esc_html_e( 'Country Analytics', 'premium-analytics-dashboard-pro' ); ?></h2>
		</div>
		<div id="pad-chart-country" class="pad-chart-placeholder" data-chart="country-analytics"></div>
	</div>

	<div class="pad-panel">
		<div class="pad-panel-head">
			<h2><?php esc_html_e( 'Device & Browser', 'premium-analytics-dashboard-pro' ); ?></h2>
		</div>
		<div id="pad-chart-device" class="pad-chart-placeholder" data-chart="device-browser"></div>
	</div>

	<div class="pad-panel pad-panel-wide">
		<div class="pad-panel-head">
			<h2><?php esc_html_e( 'Hourly Activity Heatmap', 'premium-analytics-dashboard-pro' ); ?></h2>
		</div>
		<div id="pad-chart-heatmap" class="pad-chart-placeholder" data-chart="hourly-heatmap"></div>
	</div>
</section>

<p class="pad-phase-note">
	<?php esc_html_e( 'Note: Contact Form 7 lead capture aur Visitor/Session tracking ab live hain. Chart rendering (ApexCharts) agle development phase me wire hoga — yeh containers ab hi se semantic aur data-ready hain.', 'premium-analytics-dashboard-pro' ); ?>
</p>

<?php require __DIR__ . '/layout-footer.php'; ?>
