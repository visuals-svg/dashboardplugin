<?php
/**
 * Analytics Page (Phase 1 shell)
 *
 * Country/City/Browser/Device/OS breakdown charts "Charts" phase me
 * ApexCharts se wire honge. Abhi structure aur real (empty) grouped
 * queries ready hain.
 *
 * @package Premium_Analytics_Dashboard_Pro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $wpdb;

$pad_current_slug = 'pad-analytics';
$pad_page_title   = __( 'Analytics', 'premium-analytics-dashboard-pro' );

require __DIR__ . '/layout-header.php';

$pad_visitors_table = PAD_Database::table( 'visitors' );

$pad_by_country = $wpdb->get_results( "SELECT country, COUNT(id) AS total FROM {$pad_visitors_table} WHERE country != '' GROUP BY country ORDER BY total DESC LIMIT 10" );
$pad_by_browser = $wpdb->get_results( "SELECT browser, COUNT(id) AS total FROM {$pad_visitors_table} WHERE browser != '' GROUP BY browser ORDER BY total DESC LIMIT 10" );
$pad_by_device  = $wpdb->get_results( "SELECT device, COUNT(id) AS total FROM {$pad_visitors_table} WHERE device != '' GROUP BY device ORDER BY total DESC LIMIT 10" );
$pad_by_os      = $wpdb->get_results( "SELECT os, COUNT(id) AS total FROM {$pad_visitors_table} WHERE os != '' GROUP BY os ORDER BY total DESC LIMIT 10" );
?>

<section class="pad-charts-grid">
	<div class="pad-panel">
		<div class="pad-panel-head"><h2><?php esc_html_e( 'Country Analytics', 'premium-analytics-dashboard-pro' ); ?></h2></div>
		<?php if ( empty( $pad_by_country ) ) : ?>
			<div class="pad-empty-state pad-empty-state-compact"><p><?php esc_html_e( 'Data available hote hi yahan breakdown dikhega.', 'premium-analytics-dashboard-pro' ); ?></p></div>
		<?php else : ?>
			<ul class="pad-bar-list">
				<?php foreach ( $pad_by_country as $pad_row ) : ?>
					<li><span><?php echo esc_html( $pad_row->country ); ?></span><strong><?php echo esc_html( $pad_row->total ); ?></strong></li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>
	</div>

	<div class="pad-panel">
		<div class="pad-panel-head"><h2><?php esc_html_e( 'Browser Analytics', 'premium-analytics-dashboard-pro' ); ?></h2></div>
		<?php if ( empty( $pad_by_browser ) ) : ?>
			<div class="pad-empty-state pad-empty-state-compact"><p><?php esc_html_e( 'Data available hote hi yahan breakdown dikhega.', 'premium-analytics-dashboard-pro' ); ?></p></div>
		<?php else : ?>
			<ul class="pad-bar-list">
				<?php foreach ( $pad_by_browser as $pad_row ) : ?>
					<li><span><?php echo esc_html( $pad_row->browser ); ?></span><strong><?php echo esc_html( $pad_row->total ); ?></strong></li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>
	</div>

	<div class="pad-panel">
		<div class="pad-panel-head"><h2><?php esc_html_e( 'Device Analytics', 'premium-analytics-dashboard-pro' ); ?></h2></div>
		<?php if ( empty( $pad_by_device ) ) : ?>
			<div class="pad-empty-state pad-empty-state-compact"><p><?php esc_html_e( 'Data available hote hi yahan breakdown dikhega.', 'premium-analytics-dashboard-pro' ); ?></p></div>
		<?php else : ?>
			<ul class="pad-bar-list">
				<?php foreach ( $pad_by_device as $pad_row ) : ?>
					<li><span><?php echo esc_html( $pad_row->device ); ?></span><strong><?php echo esc_html( $pad_row->total ); ?></strong></li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>
	</div>

	<div class="pad-panel">
		<div class="pad-panel-head"><h2><?php esc_html_e( 'Operating System Analytics', 'premium-analytics-dashboard-pro' ); ?></h2></div>
		<?php if ( empty( $pad_by_os ) ) : ?>
			<div class="pad-empty-state pad-empty-state-compact"><p><?php esc_html_e( 'Data available hote hi yahan breakdown dikhega.', 'premium-analytics-dashboard-pro' ); ?></p></div>
		<?php else : ?>
			<ul class="pad-bar-list">
				<?php foreach ( $pad_by_os as $pad_row ) : ?>
					<li><span><?php echo esc_html( $pad_row->os ); ?></span><strong><?php echo esc_html( $pad_row->total ); ?></strong></li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>
	</div>
</section>

<?php require __DIR__ . '/layout-footer.php'; ?>
