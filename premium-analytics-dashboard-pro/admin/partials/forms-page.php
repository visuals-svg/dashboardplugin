<?php
/**
 * Forms Page (Phase 1 shell)
 *
 * Contact Form 7 ke saare detected forms yahan list hote hain
 * (wpcf7_contact_form post type se, real query — koi fake data nahi).
 *
 * @package Premium_Analytics_Dashboard_Pro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $wpdb;

$pad_current_slug = 'pad-forms';
$pad_page_title   = __( 'Forms', 'premium-analytics-dashboard-pro' );

require __DIR__ . '/layout-header.php';

$pad_forms_table   = PAD_Database::table( 'forms' );
$pad_registered    = $wpdb->get_results( "SELECT form_id, form_name, total_submissions, is_active, updated_at FROM {$pad_forms_table} ORDER BY updated_at DESC" );
$pad_cf7_available = post_type_exists( 'wpcf7_contact_form' );
$pad_cf7_forms     = $pad_cf7_available
	? get_posts(
		array(
			'post_type'      => 'wpcf7_contact_form',
			'posts_per_page' => -1,
			'post_status'    => 'publish',
		)
	)
	: array();
?>

<section class="pad-panel">
	<div class="pad-panel-head">
		<h2><?php esc_html_e( 'Contact Form 7 — Detected Forms', 'premium-analytics-dashboard-pro' ); ?></h2>
		<p><?php esc_html_e( 'Yeh list Contact Form 7 me maujood forms se live aati hai.', 'premium-analytics-dashboard-pro' ); ?></p>
	</div>

	<?php if ( ! $pad_cf7_available ) : ?>
		<div class="pad-empty-state">
			<span class="dashicons dashicons-warning"></span>
			<p><?php esc_html_e( 'Contact Form 7 plugin activate nahi hai, is liye koi form detect nahi ho saka.', 'premium-analytics-dashboard-pro' ); ?></p>
		</div>
	<?php elseif ( empty( $pad_cf7_forms ) ) : ?>
		<div class="pad-empty-state">
			<span class="dashicons dashicons-feedback"></span>
			<p><?php esc_html_e( 'Contact Form 7 active hai lekin abhi tak koi form nahi banaya gaya.', 'premium-analytics-dashboard-pro' ); ?></p>
		</div>
	<?php else : ?>
		<table class="pad-table">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Form ID', 'premium-analytics-dashboard-pro' ); ?></th>
					<th><?php esc_html_e( 'Form Name', 'premium-analytics-dashboard-pro' ); ?></th>
					<th><?php esc_html_e( 'Total Submissions', 'premium-analytics-dashboard-pro' ); ?></th>
					<th><?php esc_html_e( 'Status', 'premium-analytics-dashboard-pro' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $pad_cf7_forms as $pad_form ) : ?>
					<?php
					$pad_row = null;

					foreach ( $pad_registered as $pad_registered_row ) {
						if ( (int) $pad_registered_row->form_id === (int) $pad_form->ID ) {
							$pad_row = $pad_registered_row;
							break;
						}
					}
					?>
					<tr>
						<td>#<?php echo (int) $pad_form->ID; ?></td>
						<td><?php echo esc_html( $pad_form->post_title ); ?></td>
						<td><?php echo esc_html( $pad_row ? (int) $pad_row->total_submissions : 0 ); ?></td>
						<td><span class="pad-badge pad-badge-new"><?php esc_html_e( 'Tracked', 'premium-analytics-dashboard-pro' ); ?></span></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	<?php endif; ?>
</section>

<?php require __DIR__ . '/layout-footer.php'; ?>
