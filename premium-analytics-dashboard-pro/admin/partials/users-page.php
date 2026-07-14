<?php
/**
 * Users Page (fully functional)
 *
 * Administrator, Analytics Manager, Analytics Sales, Analytics Viewer
 * roles wale saare users ki real listing — WP_User_Query se, koi
 * fake/demo data nahi.
 *
 * @package Premium_Analytics_Dashboard_Pro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$pad_current_slug = 'pad-users';
$pad_page_title   = __( 'Users', 'premium-analytics-dashboard-pro' );

require __DIR__ . '/layout-header.php';

$pad_role_labels = array(
	'administrator' => __( 'Administrator', 'premium-analytics-dashboard-pro' ),
	'pad_manager'   => __( 'Manager', 'premium-analytics-dashboard-pro' ),
	'pad_sales'     => __( 'Sales', 'premium-analytics-dashboard-pro' ),
	'pad_viewer'    => __( 'Viewer', 'premium-analytics-dashboard-pro' ),
);

$pad_users_query = new WP_User_Query(
	array(
		'role__in' => array_keys( $pad_role_labels ),
		'orderby'  => 'display_name',
		'order'    => 'ASC',
		'number'   => 100,
	)
);

$pad_users = $pad_users_query->get_results();
?>

<section class="pad-panel">
	<div class="pad-panel-head">
		<h2><?php esc_html_e( 'Dashboard Access', 'premium-analytics-dashboard-pro' ); ?></h2>
		<p><?php esc_html_e( 'Yeh wo users hain jinke paas Analytics Dashboard Pro ke roles assign hain. Naye users ke roles WordPress ke Users screen se assign karein.', 'premium-analytics-dashboard-pro' ); ?></p>
	</div>

	<?php if ( empty( $pad_users ) ) : ?>
		<div class="pad-empty-state">
			<span class="dashicons dashicons-admin-users"></span>
			<p><?php esc_html_e( 'Koi bhi user in roles ke saath maujood nahi hai.', 'premium-analytics-dashboard-pro' ); ?></p>
		</div>
	<?php else : ?>
		<table class="pad-table">
			<thead>
				<tr>
					<th><?php esc_html_e( 'User', 'premium-analytics-dashboard-pro' ); ?></th>
					<th><?php esc_html_e( 'Email', 'premium-analytics-dashboard-pro' ); ?></th>
					<th><?php esc_html_e( 'Role', 'premium-analytics-dashboard-pro' ); ?></th>
					<th><?php esc_html_e( 'Registered', 'premium-analytics-dashboard-pro' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $pad_users as $pad_user ) : ?>
					<?php
					$pad_role_key   = ! empty( $pad_user->roles ) ? $pad_user->roles[0] : '';
					$pad_role_label = isset( $pad_role_labels[ $pad_role_key ] ) ? $pad_role_labels[ $pad_role_key ] : $pad_role_key;
					?>
					<tr>
						<td>
							<div class="pad-user-chip">
								<?php echo get_avatar( $pad_user->ID, 28 ); ?>
								<span><?php echo esc_html( $pad_user->display_name ); ?></span>
							</div>
						</td>
						<td><?php echo esc_html( $pad_user->user_email ); ?></td>
						<td><span class="pad-badge pad-badge-new"><?php echo esc_html( $pad_role_label ); ?></span></td>
						<td><?php echo esc_html( mysql2date( 'd M Y', $pad_user->user_registered ) ); ?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	<?php endif; ?>
</section>

<?php require __DIR__ . '/layout-footer.php'; ?>
