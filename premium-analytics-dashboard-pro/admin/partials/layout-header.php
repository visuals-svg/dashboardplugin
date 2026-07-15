<?php
/**
 * Shared Admin Layout — Header + Sidebar (opening markup)
 *
 * Har page partial isse require karta hai. Expects two variables
 * set by the calling partial: $pad_current_slug aur $pad_page_title.
 *
 * @package Premium_Analytics_Dashboard_Pro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$pad_admin_menu = new PAD_Admin();
$pad_nav_items  = $pad_admin_menu->get_pages();
$pad_settings   = PAD_Helper::get_settings();
$pad_theme_mode = in_array( $pad_settings['theme_mode'], array( 'light', 'dark' ), true ) ? $pad_settings['theme_mode'] : 'light';
$pad_user       = wp_get_current_user();
?>
<div class="pad-wrap" data-theme="<?php echo esc_attr( $pad_theme_mode ); ?>" style="--pad-accent: <?php echo esc_attr( $pad_settings['theme_color'] ); ?>;">

	<header class="pad-header">
		<div class="pad-header-left">
			<span class="pad-logo-mark" aria-hidden="true"></span>
			<div class="pad-header-titles">
				<h1><?php echo esc_html( $pad_page_title ); ?></h1>
				<p><?php esc_html_e( 'Premium Analytics Dashboard Pro', 'premium-analytics-dashboard-pro' ); ?></p>
			</div>
		</div>

		<div class="pad-header-right">
			<span class="pad-header-clock" title="<?php esc_attr_e( 'Site timezone', 'premium-analytics-dashboard-pro' ); ?>">
				<?php echo esc_html( wp_date( 'D, d M Y — H:i' ) ); ?>
			</span>

			<div class="pad-notification-wrap">
				<button type="button" id="pad-notification-toggle" class="pad-icon-btn" aria-label="<?php esc_attr_e( 'Notifications', 'premium-analytics-dashboard-pro' ); ?>">
					<span class="dashicons dashicons-bell"></span>
					<span class="pad-notification-badge" id="pad-notification-badge" hidden>0</span>
				</button>
				<div class="pad-notification-dropdown" id="pad-notification-dropdown" hidden>
					<div class="pad-notification-dropdown-head">
						<strong><?php esc_html_e( 'Notifications', 'premium-analytics-dashboard-pro' ); ?></strong>
						<button type="button" id="pad-mark-all-read"><?php esc_html_e( 'Mark all read', 'premium-analytics-dashboard-pro' ); ?></button>
					</div>
					<div id="pad-notification-list">
						<p class="pad-table-loading"><?php esc_html_e( 'Loading…', 'premium-analytics-dashboard-pro' ); ?></p>
					</div>
				</div>
			</div>

			<button type="button" id="pad-theme-toggle" class="pad-icon-btn" aria-label="<?php esc_attr_e( 'Toggle dark mode', 'premium-analytics-dashboard-pro' ); ?>">
				<span class="dashicons dashicons-lightbulb"></span>
			</button>

			<div class="pad-user-chip">
				<?php echo get_avatar( $pad_user->ID, 32 ); ?>
				<span><?php echo esc_html( $pad_user->display_name ); ?></span>
			</div>
		</div>
	</header>

	<div class="pad-body">

		<nav class="pad-sidebar" aria-label="<?php esc_attr_e( 'Analytics Pro navigation', 'premium-analytics-dashboard-pro' ); ?>">
			<ul>
				<?php foreach ( $pad_nav_items as $pad_slug => $pad_item ) : ?>
					<?php if ( ! current_user_can( $pad_item['capability'] ) ) : ?>
						<?php continue; ?>
					<?php endif; ?>
					<li>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=' . $pad_slug ) ); ?>"
							class="<?php echo ( $pad_slug === $pad_current_slug ) ? 'is-active' : ''; ?>">
							<span class="dashicons <?php echo esc_attr( $pad_item['icon'] ); ?>"></span>
							<span><?php echo esc_html( $pad_item['label'] ); ?></span>
						</a>
					</li>
				<?php endforeach; ?>
			</ul>
		</nav>

		<main class="pad-content">
