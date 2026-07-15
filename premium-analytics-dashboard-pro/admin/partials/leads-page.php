<?php
/**
 * Leads Page — Advanced Lead Management
 *
 * Poori tarah AJAX-driven: search, sort, filters, pagination, status
 * change, notes, tags, delete/bulk-delete — sab `assets/js/pad-leads.js`
 * aur PAD_Leads_Table AJAX endpoints se chalte hain. Exports (CSV/
 * Excel/PDF/Print) admin-post.php ke through direct download hote hain.
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
$pad_forms_table = PAD_Database::table( 'forms' );

$pad_countries = $wpdb->get_col( "SELECT DISTINCT country FROM {$pad_leads_table} WHERE country != '' ORDER BY country ASC" );
$pad_forms     = $wpdb->get_results( "SELECT form_id, form_name FROM {$pad_forms_table} ORDER BY form_name ASC" );

$pad_can_delete = current_user_can( 'pad_delete_leads' );
$pad_can_export = current_user_can( 'pad_export_leads' );

$pad_export_base_args = array(
	'search'    => '',
	'status'    => '',
	'country'   => '',
	'form_id'   => '',
	'date_from' => '',
	'date_to'   => '',
);
?>

<div
	id="pad-leads-app"
	class="pad-leads-app"
	data-can-delete="<?php echo esc_attr( $pad_can_delete ? '1' : '0' ); ?>"
	data-export-csv-url="<?php echo esc_url( wp_nonce_url( add_query_arg( array_merge( array( 'action' => 'pad_export_leads_csv' ), $pad_export_base_args ), admin_url( 'admin-post.php' ) ), 'pad_export_leads' ) ); ?>"
	data-export-excel-url="<?php echo esc_url( wp_nonce_url( add_query_arg( array_merge( array( 'action' => 'pad_export_leads_excel' ), $pad_export_base_args ), admin_url( 'admin-post.php' ) ), 'pad_export_leads' ) ); ?>"
	data-export-pdf-url="<?php echo esc_url( wp_nonce_url( add_query_arg( array_merge( array( 'action' => 'pad_export_leads_pdf' ), $pad_export_base_args ), admin_url( 'admin-post.php' ) ), 'pad_export_leads' ) ); ?>"
	data-print-url="<?php echo esc_url( wp_nonce_url( add_query_arg( array_merge( array( 'action' => 'pad_print_leads' ), $pad_export_base_args ), admin_url( 'admin-post.php' ) ), 'pad_export_leads' ) ); ?>"
>

	<section class="pad-panel pad-leads-toolbar">
		<div class="pad-filter-row">
			<input type="search" id="pad-leads-search" placeholder="<?php esc_attr_e( 'Search name, email, phone, message…', 'premium-analytics-dashboard-pro' ); ?>" />

			<select id="pad-leads-filter-status">
				<option value=""><?php esc_html_e( 'All Statuses', 'premium-analytics-dashboard-pro' ); ?></option>
				<option value="new"><?php esc_html_e( 'New', 'premium-analytics-dashboard-pro' ); ?></option>
				<option value="contacted"><?php esc_html_e( 'Contacted', 'premium-analytics-dashboard-pro' ); ?></option>
				<option value="qualified"><?php esc_html_e( 'Qualified', 'premium-analytics-dashboard-pro' ); ?></option>
				<option value="converted"><?php esc_html_e( 'Converted', 'premium-analytics-dashboard-pro' ); ?></option>
				<option value="lost"><?php esc_html_e( 'Lost', 'premium-analytics-dashboard-pro' ); ?></option>
			</select>

			<select id="pad-leads-filter-country">
				<option value=""><?php esc_html_e( 'All Countries', 'premium-analytics-dashboard-pro' ); ?></option>
				<?php foreach ( $pad_countries as $pad_country ) : ?>
					<option value="<?php echo esc_attr( $pad_country ); ?>"><?php echo esc_html( $pad_country ); ?></option>
				<?php endforeach; ?>
			</select>

			<select id="pad-leads-filter-form">
				<option value=""><?php esc_html_e( 'All Forms', 'premium-analytics-dashboard-pro' ); ?></option>
				<?php foreach ( $pad_forms as $pad_form ) : ?>
					<option value="<?php echo esc_attr( $pad_form->form_id ); ?>"><?php echo esc_html( $pad_form->form_name ); ?></option>
				<?php endforeach; ?>
			</select>

			<input type="date" id="pad-leads-filter-date-from" title="<?php esc_attr_e( 'From date', 'premium-analytics-dashboard-pro' ); ?>" />
			<input type="date" id="pad-leads-filter-date-to" title="<?php esc_attr_e( 'To date', 'premium-analytics-dashboard-pro' ); ?>" />

			<button type="button" id="pad-leads-reset" class="button"><?php esc_html_e( 'Reset', 'premium-analytics-dashboard-pro' ); ?></button>
		</div>

		<?php if ( $pad_can_export ) : ?>
			<div class="pad-export-row">
				<a class="button pad-export-link" id="pad-export-csv" target="_blank" rel="noopener"><?php esc_html_e( 'Export CSV', 'premium-analytics-dashboard-pro' ); ?></a>
				<a class="button pad-export-link" id="pad-export-excel" target="_blank" rel="noopener"><?php esc_html_e( 'Export Excel', 'premium-analytics-dashboard-pro' ); ?></a>
				<a class="button pad-export-link" id="pad-export-pdf" target="_blank" rel="noopener"><?php esc_html_e( 'Export PDF', 'premium-analytics-dashboard-pro' ); ?></a>
				<a class="button pad-export-link" id="pad-print-leads" target="_blank" rel="noopener"><?php esc_html_e( 'Print', 'premium-analytics-dashboard-pro' ); ?></a>
			</div>
		<?php endif; ?>
	</section>

	<section class="pad-panel">

		<?php if ( $pad_can_delete ) : ?>
			<div class="pad-bulk-bar" id="pad-bulk-bar" hidden>
				<span id="pad-bulk-count"></span>
				<button type="button" id="pad-bulk-delete" class="button pad-button-danger"><?php esc_html_e( 'Delete Selected', 'premium-analytics-dashboard-pro' ); ?></button>
			</div>
		<?php endif; ?>

		<div class="pad-table-scroll">
			<table class="pad-table" id="pad-leads-table">
				<thead>
					<tr>
						<?php if ( $pad_can_delete ) : ?>
							<th><input type="checkbox" id="pad-select-all" /></th>
						<?php endif; ?>
						<th data-sort="form_name"><?php esc_html_e( 'Form', 'premium-analytics-dashboard-pro' ); ?></th>
						<th data-sort="first_name"><?php esc_html_e( 'Name', 'premium-analytics-dashboard-pro' ); ?></th>
						<th data-sort="email"><?php esc_html_e( 'Email', 'premium-analytics-dashboard-pro' ); ?></th>
						<th><?php esc_html_e( 'Phone', 'premium-analytics-dashboard-pro' ); ?></th>
						<th data-sort="country"><?php esc_html_e( 'Country', 'premium-analytics-dashboard-pro' ); ?></th>
						<th data-sort="status"><?php esc_html_e( 'Status', 'premium-analytics-dashboard-pro' ); ?></th>
						<th data-sort="created_at"><?php esc_html_e( 'Date', 'premium-analytics-dashboard-pro' ); ?></th>
						<th><?php esc_html_e( 'Actions', 'premium-analytics-dashboard-pro' ); ?></th>
					</tr>
				</thead>
				<tbody id="pad-leads-tbody">
					<tr><td colspan="9" class="pad-table-loading"><?php esc_html_e( 'Loading…', 'premium-analytics-dashboard-pro' ); ?></td></tr>
				</tbody>
			</table>
		</div>

		<div class="pad-pagination">
			<select id="pad-leads-per-page">
				<option value="10">10</option>
				<option value="20" selected>20</option>
				<option value="50">50</option>
				<option value="100">100</option>
			</select>
			<button type="button" id="pad-page-prev" class="button">&laquo; <?php esc_html_e( 'Prev', 'premium-analytics-dashboard-pro' ); ?></button>
			<span id="pad-page-info"></span>
			<button type="button" id="pad-page-next" class="button"><?php esc_html_e( 'Next', 'premium-analytics-dashboard-pro' ); ?> &raquo;</button>
		</div>
	</section>

	<div class="pad-slideover" id="pad-lead-detail" hidden>
		<div class="pad-slideover-backdrop" id="pad-lead-detail-backdrop"></div>
		<div class="pad-slideover-panel">
			<button type="button" class="pad-slideover-close" id="pad-lead-detail-close">&times;</button>
			<div id="pad-lead-detail-content">
				<p class="pad-table-loading"><?php esc_html_e( 'Loading…', 'premium-analytics-dashboard-pro' ); ?></p>
			</div>
		</div>
	</div>

</div>

<?php require __DIR__ . '/layout-footer.php'; ?>
