<?php
/**
 * Leads Page (Phase 1 shell)
 *
 * Full advanced table (search/sort/filters/export) agle "Lead
 * Management" phase me is par build hoga. Abhi yeh page real
 * PAD_Database table se live count aur empty-state dikhata hai.
 *
 * @package Premium_Analytics_Dashboard_Pro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $wpdb;

$pad_current_slug = 'pad-leads';
$pad_page_title   = __( 'Leads', 'premium-analytics-dashboard-pro' );

require __DIR__ . '/layout-header.php';

$pad_leads_table = PAD_Database::table( 'leads' );
$pad_total_leads = (int) $wpdb->get_var( "SELECT COUNT(id) FROM {$pad_leads_table}" );
$pad_recent      = $wpdb->get_results( "SELECT id, form_name, first_name, last_name, email, status, created_at FROM {$pad_leads_table} ORDER BY created_at DESC LIMIT 20" );
?>

<section class="pad-panel">
	<div class="pad-panel-head">
		<h2><?php esc_html_e( 'All Leads', 'premium-analytics-dashboard-pro' ); ?></h2>
		<p>
			<?php
			printf(
				/* translators: %d: total number of leads captured so far */
				esc_html__( 'Total captured so far: %d', 'premium-analytics-dashboard-pro' ),
				(int) $pad_total_leads
			);
			?>
		</p>
	</div>

	<?php if ( empty( $pad_recent ) ) : ?>
		<div class="pad-empty-state">
			<span class="dashicons dashicons-groups"></span>
			<p><?php esc_html_e( 'Abhi tak koi lead capture nahi hua. Jaise hi koi Contact Form 7 form submit hoga, wo yahan real-time me dikhega.', 'premium-analytics-dashboard-pro' ); ?></p>
		</div>
	<?php else : ?>
		<table class="pad-table">
			<thead>
				<tr>
					<th><?php esc_html_e( 'ID', 'premium-analytics-dashboard-pro' ); ?></th>
					<th><?php esc_html_e( 'Form', 'premium-analytics-dashboard-pro' ); ?></th>
					<th><?php esc_html_e( 'Name', 'premium-analytics-dashboard-pro' ); ?></th>
					<th><?php esc_html_e( 'Email', 'premium-analytics-dashboard-pro' ); ?></th>
					<th><?php esc_html_e( 'Status', 'premium-analytics-dashboard-pro' ); ?></th>
					<th><?php esc_html_e( 'Date', 'premium-analytics-dashboard-pro' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $pad_recent as $pad_lead ) : ?>
					<tr>
						<td>#<?php echo (int) $pad_lead->id; ?></td>
						<td><?php echo esc_html( $pad_lead->form_name ); ?></td>
						<td><?php echo esc_html( trim( $pad_lead->first_name . ' ' . $pad_lead->last_name ) ); ?></td>
						<td><?php echo esc_html( $pad_lead->email ); ?></td>
						<td><span class="pad-badge pad-badge-<?php echo esc_attr( $pad_lead->status ); ?>"><?php echo esc_html( ucfirst( $pad_lead->status ) ); ?></span></td>
						<td><?php echo esc_html( mysql2date( 'd M Y, H:i', $pad_lead->created_at ) ); ?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	<?php endif; ?>
</section>

<?php require __DIR__ . '/layout-footer.php'; ?>
