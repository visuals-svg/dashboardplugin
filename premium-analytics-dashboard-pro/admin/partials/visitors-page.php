<?php
/**
 * Visitors Page (Phase 1 shell)
 *
 * Detailed visitor tracking UI "Visitor Tracking" phase me aayega.
 * Abhi real table se summary counts dikhaye jaate hain.
 *
 * @package Premium_Analytics_Dashboard_Pro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $wpdb;

$pad_current_slug = 'pad-visitors';
$pad_page_title   = __( 'Visitors', 'premium-analytics-dashboard-pro' );

require __DIR__ . '/layout-header.php';

$pad_visitors_table = PAD_Database::table( 'visitors' );
$pad_sessions_table = PAD_Database::table( 'sessions' );

$pad_total_visitors  = (int) $wpdb->get_var( "SELECT COUNT(id) FROM {$pad_visitors_table}" );
$pad_returning       = (int) $wpdb->get_var( "SELECT COUNT(id) FROM {$pad_visitors_table} WHERE visits_count > 1" );
$pad_total_sessions  = (int) $wpdb->get_var( "SELECT COUNT(id) FROM {$pad_sessions_table}" );
$pad_recent_visitors = $wpdb->get_results( "SELECT visitor_hash, country, city, browser, device, os, last_seen FROM {$pad_visitors_table} ORDER BY last_seen DESC LIMIT 20" );
?>

<section class="pad-cards-grid pad-cards-grid-compact">
	<div class="pad-card">
		<div class="pad-card-icon"><span class="dashicons dashicons-groups"></span></div>
		<div class="pad-card-body">
			<span class="pad-card-value"><?php echo esc_html( $pad_total_visitors ); ?></span>
			<span class="pad-card-label"><?php esc_html_e( 'Unique Visitors', 'premium-analytics-dashboard-pro' ); ?></span>
		</div>
	</div>
	<div class="pad-card">
		<div class="pad-card-icon"><span class="dashicons dashicons-update"></span></div>
		<div class="pad-card-body">
			<span class="pad-card-value"><?php echo esc_html( $pad_returning ); ?></span>
			<span class="pad-card-label"><?php esc_html_e( 'Returning Visitors', 'premium-analytics-dashboard-pro' ); ?></span>
		</div>
	</div>
	<div class="pad-card">
		<div class="pad-card-icon"><span class="dashicons dashicons-clock"></span></div>
		<div class="pad-card-body">
			<span class="pad-card-value"><?php echo esc_html( $pad_total_sessions ); ?></span>
			<span class="pad-card-label"><?php esc_html_e( 'Total Sessions', 'premium-analytics-dashboard-pro' ); ?></span>
		</div>
	</div>
</section>

<section class="pad-panel">
	<div class="pad-panel-head">
		<h2><?php esc_html_e( 'Recent Visitors', 'premium-analytics-dashboard-pro' ); ?></h2>
	</div>

	<?php if ( empty( $pad_recent_visitors ) ) : ?>
		<div class="pad-empty-state">
			<span class="dashicons dashicons-visibility"></span>
			<p><?php esc_html_e( 'Abhi tak koi visitor track nahi hua. Frontend tracking script activate hote hi yahan data aana shuru ho jaayega.', 'premium-analytics-dashboard-pro' ); ?></p>
		</div>
	<?php else : ?>
		<table class="pad-table">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Visitor', 'premium-analytics-dashboard-pro' ); ?></th>
					<th><?php esc_html_e( 'Country', 'premium-analytics-dashboard-pro' ); ?></th>
					<th><?php esc_html_e( 'City', 'premium-analytics-dashboard-pro' ); ?></th>
					<th><?php esc_html_e( 'Browser', 'premium-analytics-dashboard-pro' ); ?></th>
					<th><?php esc_html_e( 'Device', 'premium-analytics-dashboard-pro' ); ?></th>
					<th><?php esc_html_e( 'OS', 'premium-analytics-dashboard-pro' ); ?></th>
					<th><?php esc_html_e( 'Last Seen', 'premium-analytics-dashboard-pro' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $pad_recent_visitors as $pad_visitor ) : ?>
					<tr>
						<td><?php echo esc_html( substr( $pad_visitor->visitor_hash, 0, 10 ) ); ?>&hellip;</td>
						<td><?php echo esc_html( $pad_visitor->country ); ?></td>
						<td><?php echo esc_html( $pad_visitor->city ); ?></td>
						<td><?php echo esc_html( $pad_visitor->browser ); ?></td>
						<td><?php echo esc_html( $pad_visitor->device ); ?></td>
						<td><?php echo esc_html( $pad_visitor->os ); ?></td>
						<td><?php echo esc_html( mysql2date( 'd M Y, H:i', $pad_visitor->last_seen ) ); ?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	<?php endif; ?>
</section>

<?php require __DIR__ . '/layout-footer.php'; ?>
