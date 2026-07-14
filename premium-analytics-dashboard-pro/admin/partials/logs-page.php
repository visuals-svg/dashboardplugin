<?php
/**
 * Logs Page (fully functional)
 *
 * Plugin ke andar hone wale actions ka audit trail — real
 * pad_logs table se, jo PAD_Helper::log() insert karta hai.
 *
 * @package Premium_Analytics_Dashboard_Pro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $wpdb;

$pad_current_slug = 'pad-logs';
$pad_page_title   = __( 'Logs', 'premium-analytics-dashboard-pro' );

require __DIR__ . '/layout-header.php';

$pad_logs_table = PAD_Database::table( 'logs' );
$pad_logs       = $wpdb->get_results( "SELECT id, user_id, action, object_type, description, ip_address, created_at FROM {$pad_logs_table} ORDER BY created_at DESC LIMIT 50" );
?>

<section class="pad-panel">
	<div class="pad-panel-head">
		<h2><?php esc_html_e( 'Activity Log', 'premium-analytics-dashboard-pro' ); ?></h2>
		<p><?php esc_html_e( 'Settings changes, deletions aur baaki important actions yahan track hote hain.', 'premium-analytics-dashboard-pro' ); ?></p>
	</div>

	<?php if ( empty( $pad_logs ) ) : ?>
		<div class="pad-empty-state">
			<span class="dashicons dashicons-list-view"></span>
			<p><?php esc_html_e( 'Abhi tak koi activity log record nahi hui.', 'premium-analytics-dashboard-pro' ); ?></p>
		</div>
	<?php else : ?>
		<table class="pad-table">
			<thead>
				<tr>
					<th><?php esc_html_e( 'User', 'premium-analytics-dashboard-pro' ); ?></th>
					<th><?php esc_html_e( 'Action', 'premium-analytics-dashboard-pro' ); ?></th>
					<th><?php esc_html_e( 'Description', 'premium-analytics-dashboard-pro' ); ?></th>
					<th><?php esc_html_e( 'IP Address', 'premium-analytics-dashboard-pro' ); ?></th>
					<th><?php esc_html_e( 'Date', 'premium-analytics-dashboard-pro' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $pad_logs as $pad_log ) : ?>
					<?php $pad_log_user = get_userdata( (int) $pad_log->user_id ); ?>
					<tr>
						<td><?php echo esc_html( $pad_log_user ? $pad_log_user->display_name : __( 'System', 'premium-analytics-dashboard-pro' ) ); ?></td>
						<td><span class="pad-badge pad-badge-new"><?php echo esc_html( $pad_log->action ); ?></span></td>
						<td><?php echo esc_html( $pad_log->description ); ?></td>
						<td><?php echo esc_html( $pad_log->ip_address ); ?></td>
						<td><?php echo esc_html( mysql2date( 'd M Y, H:i', $pad_log->created_at ) ); ?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	<?php endif; ?>
</section>

<?php require __DIR__ . '/layout-footer.php'; ?>
