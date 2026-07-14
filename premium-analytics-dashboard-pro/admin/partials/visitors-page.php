<?php
/**
 * Visitors Page
 *
 * Real-time visitor/session/pageview data `pad_visitors`, `pad_sessions`
 * aur `pad_pageviews` tables se — jo `assets/js/pad-tracker.js` aur
 * PAD_Visitor_Tracker AJAX endpoints se bharti hain.
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

$pad_visitors_table  = PAD_Database::table( 'visitors' );
$pad_sessions_table  = PAD_Database::table( 'sessions' );
$pad_pageviews_table = PAD_Database::table( 'pageviews' );

$pad_total_visitors  = (int) $wpdb->get_var( "SELECT COUNT(id) FROM {$pad_visitors_table}" );
$pad_returning       = (int) $wpdb->get_var( "SELECT COUNT(id) FROM {$pad_visitors_table} WHERE visits_count > 1" );
$pad_total_sessions  = (int) $wpdb->get_var( "SELECT COUNT(id) FROM {$pad_sessions_table}" );
$pad_total_pageviews = (int) $wpdb->get_var( "SELECT COUNT(id) FROM {$pad_pageviews_table}" );
$pad_avg_duration    = PAD_Stats::format_duration( PAD_Stats::get_average_session_duration() );
$pad_bounce_rate     = PAD_Stats::get_bounce_rate() . '%';
$pad_recent_visitors = $wpdb->get_results( "SELECT visitor_hash, country, city, browser, device, os, referral_source, last_seen FROM {$pad_visitors_table} ORDER BY last_seen DESC LIMIT 20" );
$pad_traffic_sources = $wpdb->get_results( "SELECT referral_source, COUNT(id) AS total FROM {$pad_visitors_table} GROUP BY referral_source ORDER BY total DESC" );
$pad_top_pages       = $wpdb->get_results( "SELECT url, COUNT(id) AS total FROM {$pad_pageviews_table} GROUP BY url ORDER BY total DESC LIMIT 10" );
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
	<div class="pad-card">
		<div class="pad-card-icon"><span class="dashicons dashicons-media-document"></span></div>
		<div class="pad-card-body">
			<span class="pad-card-value"><?php echo esc_html( $pad_total_pageviews ); ?></span>
			<span class="pad-card-label"><?php esc_html_e( 'Page Views', 'premium-analytics-dashboard-pro' ); ?></span>
		</div>
	</div>
	<div class="pad-card">
		<div class="pad-card-icon"><span class="dashicons dashicons-clock"></span></div>
		<div class="pad-card-body">
			<span class="pad-card-value"><?php echo esc_html( $pad_avg_duration ); ?></span>
			<span class="pad-card-label"><?php esc_html_e( 'Avg. Visit Duration', 'premium-analytics-dashboard-pro' ); ?></span>
		</div>
	</div>
	<div class="pad-card">
		<div class="pad-card-icon"><span class="dashicons dashicons-external"></span></div>
		<div class="pad-card-body">
			<span class="pad-card-value"><?php echo esc_html( $pad_bounce_rate ); ?></span>
			<span class="pad-card-label"><?php esc_html_e( 'Bounce Rate', 'premium-analytics-dashboard-pro' ); ?></span>
		</div>
	</div>
</section>

<section class="pad-charts-grid">
	<div class="pad-panel">
		<div class="pad-panel-head">
			<h2><?php esc_html_e( 'Traffic Sources', 'premium-analytics-dashboard-pro' ); ?></h2>
		</div>
		<?php if ( empty( $pad_traffic_sources ) ) : ?>
			<div class="pad-empty-state pad-empty-state-compact"><p><?php esc_html_e( 'Data available hote hi yahan breakdown dikhega.', 'premium-analytics-dashboard-pro' ); ?></p></div>
		<?php else : ?>
			<ul class="pad-bar-list">
				<?php foreach ( $pad_traffic_sources as $pad_source ) : ?>
					<li><span><?php echo esc_html( $pad_source->referral_source ); ?></span><strong><?php echo esc_html( $pad_source->total ); ?></strong></li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>
	</div>

	<div class="pad-panel">
		<div class="pad-panel-head">
			<h2><?php esc_html_e( 'Top Pages', 'premium-analytics-dashboard-pro' ); ?></h2>
		</div>
		<?php if ( empty( $pad_top_pages ) ) : ?>
			<div class="pad-empty-state pad-empty-state-compact"><p><?php esc_html_e( 'Data available hote hi yahan breakdown dikhega.', 'premium-analytics-dashboard-pro' ); ?></p></div>
		<?php else : ?>
			<ul class="pad-bar-list">
				<?php foreach ( $pad_top_pages as $pad_page ) : ?>
					<li><span><?php echo esc_html( wp_parse_url( $pad_page->url, PHP_URL_PATH ) ? wp_parse_url( $pad_page->url, PHP_URL_PATH ) : $pad_page->url ); ?></span><strong><?php echo esc_html( $pad_page->total ); ?></strong></li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>
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
					<th><?php esc_html_e( 'Referral', 'premium-analytics-dashboard-pro' ); ?></th>
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
						<td><span class="pad-badge pad-badge-new"><?php echo esc_html( $pad_visitor->referral_source ); ?></span></td>
						<td><?php echo esc_html( mysql2date( 'd M Y, H:i', $pad_visitor->last_seen ) ); ?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	<?php endif; ?>
</section>

<?php require __DIR__ . '/layout-footer.php'; ?>
