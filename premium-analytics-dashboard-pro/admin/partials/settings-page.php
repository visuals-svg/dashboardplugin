<?php
/**
 * Settings Page (fully functional)
 *
 * Timezone, Currency, Company Logo, Theme Color, Dashboard Widgets aur
 * "delete data on uninstall" — sab yahan se real admin-post handler
 * (PAD_Admin::handle_save_settings) ke through save hote hain.
 *
 * @package Premium_Analytics_Dashboard_Pro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$pad_current_slug = 'pad-settings';
$pad_page_title   = __( 'Settings', 'premium-analytics-dashboard-pro' );

require __DIR__ . '/layout-header.php';

$pad_settings = PAD_Helper::get_settings();

$pad_currencies = array(
	'USD' => __( 'US Dollar ($)', 'premium-analytics-dashboard-pro' ),
	'EUR' => __( 'Euro (€)', 'premium-analytics-dashboard-pro' ),
	'GBP' => __( 'British Pound (£)', 'premium-analytics-dashboard-pro' ),
	'INR' => __( 'Indian Rupee (₹)', 'premium-analytics-dashboard-pro' ),
	'AED' => __( 'UAE Dirham (د.إ)', 'premium-analytics-dashboard-pro' ),
);

$pad_widget_options = array(
	'leads'      => __( 'Leads Cards', 'premium-analytics-dashboard-pro' ),
	'visitors'   => __( 'Visitors Cards', 'premium-analytics-dashboard-pro' ),
	'conversion' => __( 'Conversion Rate Card', 'premium-analytics-dashboard-pro' ),
	'charts'     => __( 'Charts Section', 'premium-analytics-dashboard-pro' ),
);
?>

<?php if ( isset( $_GET['pad-updated'] ) ) : ?>
	<div class="pad-notice pad-notice-success">
		<span class="dashicons dashicons-yes-alt"></span>
		<p><?php esc_html_e( 'Settings successfully save ho gayi.', 'premium-analytics-dashboard-pro' ); ?></p>
	</div>
<?php endif; ?>

<form class="pad-panel pad-settings-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
	<input type="hidden" name="action" value="pad_save_settings" />
	<?php wp_nonce_field( 'pad_save_settings', 'pad_settings_nonce' ); ?>

	<div class="pad-panel-head">
		<h2><?php esc_html_e( 'General Settings', 'premium-analytics-dashboard-pro' ); ?></h2>
	</div>

	<div class="pad-form-grid">
		<div class="pad-form-field">
			<label for="pad-timezone"><?php esc_html_e( 'Timezone', 'premium-analytics-dashboard-pro' ); ?></label>
			<input type="text" id="pad-timezone" name="timezone" value="<?php echo esc_attr( $pad_settings['timezone'] ); ?>" readonly />
			<p class="pad-field-hint">
				<?php
				printf(
					/* translators: %s: link to Settings > General screen */
					wp_kses(
						__( 'Yeh site-wide timezone hai. Badalne ke liye <a href="%s">Settings &rarr; General</a> use karein.', 'premium-analytics-dashboard-pro' ),
						array( 'a' => array( 'href' => array() ) )
					),
					esc_url( admin_url( 'options-general.php' ) )
				);
				?>
			</p>
		</div>

		<div class="pad-form-field">
			<label for="pad-currency"><?php esc_html_e( 'Currency', 'premium-analytics-dashboard-pro' ); ?></label>
			<select id="pad-currency" name="currency">
				<?php foreach ( $pad_currencies as $pad_code => $pad_label ) : ?>
					<option value="<?php echo esc_attr( $pad_code ); ?>" <?php selected( $pad_settings['currency'], $pad_code ); ?>>
						<?php echo esc_html( $pad_label ); ?>
					</option>
				<?php endforeach; ?>
			</select>
		</div>

		<div class="pad-form-field">
			<label for="pad-theme-color"><?php esc_html_e( 'Theme Color', 'premium-analytics-dashboard-pro' ); ?></label>
			<input type="text" id="pad-theme-color" name="theme_color" class="pad-color-field" value="<?php echo esc_attr( $pad_settings['theme_color'] ); ?>" />
		</div>

		<div class="pad-form-field pad-form-field-wide">
			<label for="pad-company-logo"><?php esc_html_e( 'Company Logo', 'premium-analytics-dashboard-pro' ); ?></label>
			<div class="pad-logo-uploader">
				<input type="text" id="pad-company-logo" name="company_logo" value="<?php echo esc_attr( $pad_settings['company_logo'] ); ?>" readonly />
				<button type="button" class="button" id="pad-upload-logo-btn"><?php esc_html_e( 'Choose from Media Library', 'premium-analytics-dashboard-pro' ); ?></button>
			</div>
			<?php if ( ! empty( $pad_settings['company_logo'] ) ) : ?>
				<img src="<?php echo esc_url( $pad_settings['company_logo'] ); ?>" alt="" class="pad-logo-preview" />
			<?php endif; ?>
		</div>
	</div>

	<div class="pad-panel-head">
		<h2><?php esc_html_e( 'Dashboard Widgets', 'premium-analytics-dashboard-pro' ); ?></h2>
		<p><?php esc_html_e( 'Dashboard par kaunse sections dikhne chahiye, control karein.', 'premium-analytics-dashboard-pro' ); ?></p>
	</div>

	<div class="pad-checkbox-grid">
		<?php foreach ( $pad_widget_options as $pad_key => $pad_label ) : ?>
			<label class="pad-checkbox">
				<input type="checkbox" name="dashboard_widgets[]" value="<?php echo esc_attr( $pad_key ); ?>" <?php checked( in_array( $pad_key, $pad_settings['dashboard_widgets'], true ) ); ?> />
				<?php echo esc_html( $pad_label ); ?>
			</label>
		<?php endforeach; ?>
	</div>

	<div class="pad-panel-head">
		<h2><?php esc_html_e( 'Data & Uninstall', 'premium-analytics-dashboard-pro' ); ?></h2>
	</div>

	<label class="pad-checkbox">
		<input type="checkbox" name="delete_on_uninstall" value="1" <?php checked( ! empty( $pad_settings['delete_on_uninstall'] ) ); ?> />
		<?php esc_html_e( 'Plugin delete karte waqt saara data (leads, visitors, logs) bhi permanently delete kar dein.', 'premium-analytics-dashboard-pro' ); ?>
	</label>

	<div class="pad-form-actions">
		<button type="submit" class="button button-primary button-hero"><?php esc_html_e( 'Save Settings', 'premium-analytics-dashboard-pro' ); ?></button>
	</div>
</form>

<?php require __DIR__ . '/layout-footer.php'; ?>
