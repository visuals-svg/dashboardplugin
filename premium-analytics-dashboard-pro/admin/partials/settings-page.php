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

<?php if ( isset( $_GET['pad-api-regenerated'] ) ) : ?>
	<div class="pad-notice pad-notice-success">
		<span class="dashicons dashicons-yes-alt"></span>
		<p><?php esc_html_e( 'Nayi API key generate ho gayi.', 'premium-analytics-dashboard-pro' ); ?></p>
	</div>
<?php endif; ?>

<?php if ( isset( $_GET['pad-restored'] ) ) : ?>
	<div class="pad-notice pad-notice-success">
		<span class="dashicons dashicons-yes-alt"></span>
		<p>
			<?php
			printf(
				/* translators: %d: number of leads imported */
				esc_html__( 'Backup successfully restore ho gaya — %d leads import kiye gaye.', 'premium-analytics-dashboard-pro' ),
				isset( $_GET['leads_imported'] ) ? absint( $_GET['leads_imported'] ) : 0
			);
			?>
		</p>
	</div>
<?php endif; ?>

<?php if ( isset( $_GET['pad-restore-error'] ) ) : ?>
	<div class="pad-notice pad-notice-warning">
		<span class="dashicons dashicons-warning"></span>
		<p><?php esc_html_e( 'Backup restore nahi ho saka — file invalid ya bahut badi thi. Dobara try karein.', 'premium-analytics-dashboard-pro' ); ?></p>
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
		<h2><?php esc_html_e( 'Notifications', 'premium-analytics-dashboard-pro' ); ?></h2>
		<p><?php esc_html_e( 'Naya lead aane par kis channel se alert milega, control karein.', 'premium-analytics-dashboard-pro' ); ?></p>
	</div>

	<div class="pad-form-grid">
		<div class="pad-form-field pad-form-field-wide">
			<label class="pad-checkbox">
				<input type="checkbox" name="notify_email_enabled" value="1" <?php checked( ! empty( $pad_settings['notify_email_enabled'] ) ); ?> />
				<?php esc_html_e( 'Email notification (naya lead aane par)', 'premium-analytics-dashboard-pro' ); ?>
			</label>
			<input type="text" name="notify_email_recipients" value="<?php echo esc_attr( $pad_settings['notify_email_recipients'] ); ?>" placeholder="admin@example.com, sales@example.com" />
			<p class="pad-field-hint"><?php esc_html_e( 'Comma se separate multiple email addresses.', 'premium-analytics-dashboard-pro' ); ?></p>
		</div>

		<div class="pad-form-field pad-form-field-wide">
			<label class="pad-checkbox">
				<input type="checkbox" name="notify_telegram_enabled" value="1" <?php checked( ! empty( $pad_settings['notify_telegram_enabled'] ) ); ?> />
				<?php esc_html_e( 'Telegram notification (Bot API, free)', 'premium-analytics-dashboard-pro' ); ?>
			</label>
			<div class="pad-form-row">
				<input type="text" name="notify_telegram_bot_token" value="<?php echo esc_attr( $pad_settings['notify_telegram_bot_token'] ); ?>" placeholder="<?php esc_attr_e( 'Bot Token', 'premium-analytics-dashboard-pro' ); ?>" />
				<input type="text" name="notify_telegram_chat_id" value="<?php echo esc_attr( $pad_settings['notify_telegram_chat_id'] ); ?>" placeholder="<?php esc_attr_e( 'Chat ID', 'premium-analytics-dashboard-pro' ); ?>" />
			</div>
			<p class="pad-field-hint"><?php esc_html_e( '@BotFather se apna Bot Token banayein, aur apne group/channel ka Chat ID daalein.', 'premium-analytics-dashboard-pro' ); ?></p>
		</div>

		<div class="pad-form-field pad-form-field-wide">
			<label class="pad-checkbox">
				<input type="checkbox" name="notify_whatsapp_enabled" value="1" <?php checked( ! empty( $pad_settings['notify_whatsapp_enabled'] ) ); ?> />
				<?php esc_html_e( 'WhatsApp notification (apna webhook provider)', 'premium-analytics-dashboard-pro' ); ?>
			</label>
			<input type="text" name="notify_whatsapp_webhook_url" value="<?php echo esc_attr( $pad_settings['notify_whatsapp_webhook_url'] ); ?>" placeholder="https://your-whatsapp-provider.com/webhook" />
			<p class="pad-field-hint"><?php esc_html_e( 'Kisi bhi WhatsApp API provider (Twilio, Gupshup, 360dialog, apna relay) ka webhook URL daalein — naya lead aane par yahan JSON POST hoga.', 'premium-analytics-dashboard-pro' ); ?></p>
		</div>
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

<section class="pad-panel">
	<div class="pad-panel-head">
		<h2><?php esc_html_e( 'API Settings', 'premium-analytics-dashboard-pro' ); ?></h2>
		<p><?php esc_html_e( 'External systems is API key ke saath read-only leads/stats access le sakte hain.', 'premium-analytics-dashboard-pro' ); ?></p>
	</div>

	<div class="pad-form-grid">
		<div class="pad-form-field pad-form-field-wide">
			<label><?php esc_html_e( 'API Key', 'premium-analytics-dashboard-pro' ); ?></label>
			<input type="text" readonly value="<?php echo esc_attr( PAD_REST_API::get_api_key() ); ?>" onclick="this.select();" />
			<p class="pad-field-hint">
				<code><?php echo esc_html( rest_url( 'pad/v1/leads' ) ); ?></code> &amp;
				<code><?php echo esc_html( rest_url( 'pad/v1/stats' ) ); ?></code>
				<?php esc_html_e( '— request header me "X-PAD-API-Key" bhejein, ya ?api_key= query param use karein.', 'premium-analytics-dashboard-pro' ); ?>
			</p>
		</div>
	</div>

	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" onsubmit="return confirm('<?php echo esc_js( __( 'Regenerate karne se purani API key turant kaam karna band kar degi. Continue?', 'premium-analytics-dashboard-pro' ) ); ?>');">
		<input type="hidden" name="action" value="pad_regenerate_api_key" />
		<?php wp_nonce_field( 'pad_regenerate_api_key' ); ?>
		<button type="submit" class="button"><?php esc_html_e( 'Regenerate API Key', 'premium-analytics-dashboard-pro' ); ?></button>
	</form>
</section>

<section class="pad-panel">
	<div class="pad-panel-head">
		<h2><?php esc_html_e( 'Backup & Restore', 'premium-analytics-dashboard-pro' ); ?></h2>
		<p><?php esc_html_e( 'Settings, Forms, Tags aur saare Leads (unki custom fields/notes/tags sahit) ek JSON file me backup karein. Restore hamesha additive hai — kabhi existing data delete/overwrite nahi karta.', 'premium-analytics-dashboard-pro' ); ?></p>
	</div>

	<div class="pad-form-row">
		<a class="button button-primary" href="<?php echo esc_url( wp_nonce_url( add_query_arg( array( 'action' => 'pad_download_backup' ), admin_url( 'admin-post.php' ) ), 'pad_backup_restore' ) ); ?>">
			<?php esc_html_e( 'Download Backup (JSON)', 'premium-analytics-dashboard-pro' ); ?>
		</a>
	</div>

	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" enctype="multipart/form-data" class="pad-form-row" style="margin-top: 16px;">
		<input type="hidden" name="action" value="pad_restore_backup" />
		<?php wp_nonce_field( 'pad_backup_restore' ); ?>
		<input type="file" name="backup_file" accept="application/json" required />
		<button type="submit" class="button" onclick="return confirm('<?php echo esc_js( __( 'Backup restore karein? Yeh Settings update karega aur Forms/Tags/Leads ko additively import karega.', 'premium-analytics-dashboard-pro' ) ); ?>');">
			<?php esc_html_e( 'Restore from Backup', 'premium-analytics-dashboard-pro' ); ?>
		</button>
	</form>
</section>

<?php require __DIR__ . '/layout-footer.php'; ?>
