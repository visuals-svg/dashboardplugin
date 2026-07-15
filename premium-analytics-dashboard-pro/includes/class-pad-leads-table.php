<?php
/**
 * Leads Table — Query Engine, Notes, Tags, Delete
 *
 * Advanced Leads table ka poora backend yahan hai: search/sort/filter/
 * pagination ke liye ek hi shared WHERE-builder (jise list AJAX aur
 * saare exports dono use karte hain, taaki "jo table me dikh raha hai
 * wahi export bhi ho" hamesha guaranteed rahe), aur notes/tags/status/
 * delete ke CRUD AJAX handlers.
 *
 * @package Premium_Analytics_Dashboard_Pro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class PAD_Leads_Table
 */
class PAD_Leads_Table {

	/**
	 * Sort ke liye allow-listed columns — kabhi bhi raw user input
	 * seedha ORDER BY me interpolate nahi hota.
	 *
	 * @var array<string,string>
	 */
	const SORTABLE_COLUMNS = array(
		'created_at' => 'created_at',
		'first_name' => 'first_name',
		'email'      => 'email',
		'status'     => 'status',
		'form_name'  => 'form_name',
		'country'    => 'country',
	);

	/**
	 * Valid lead statuses.
	 *
	 * @var string[]
	 */
	const STATUSES = array( 'new', 'contacted', 'qualified', 'converted', 'lost' );

	/**
	 * Request (GET ya POST, jo bhi filter args bheje gaye ho) se
	 * sanitized filter array banata hai. List AJAX aur exports dono
	 * isi ek jagah se filters padhte hain.
	 *
	 * @param array $source Usually $_GET or $_POST (already unslashed by caller).
	 * @return array
	 */
	public static function sanitize_filters( $source ) {

		return array(
			'search'    => isset( $source['search'] ) ? sanitize_text_field( $source['search'] ) : '',
			'status'    => isset( $source['status'] ) && in_array( $source['status'], self::STATUSES, true ) ? $source['status'] : '',
			'country'   => isset( $source['country'] ) ? sanitize_text_field( $source['country'] ) : '',
			'form_id'   => isset( $source['form_id'] ) ? absint( $source['form_id'] ) : 0,
			'date_from' => isset( $source['date_from'] ) ? sanitize_text_field( $source['date_from'] ) : '',
			'date_to'   => isset( $source['date_to'] ) ? sanitize_text_field( $source['date_to'] ) : '',
			'orderby'   => isset( $source['orderby'] ) && isset( self::SORTABLE_COLUMNS[ $source['orderby'] ] ) ? $source['orderby'] : 'created_at',
			'order'     => isset( $source['order'] ) && 'asc' === strtolower( $source['order'] ) ? 'ASC' : 'DESC',
		);
	}

	/**
	 * Sanitized filters se WHERE clause aur uske prepared params banata hai.
	 *
	 * @param array $filters sanitize_filters() ka output.
	 * @return array{0:string,1:array}
	 */
	public static function build_where( $filters ) {

		$where  = array( '1=1' );
		$params = array();

		if ( '' !== $filters['search'] ) {
			$like     = '%' . $GLOBALS['wpdb']->esc_like( $filters['search'] ) . '%';
			$where[]  = '(first_name LIKE %s OR last_name LIKE %s OR email LIKE %s OR phone LIKE %s OR message LIKE %s)';
			$params[] = $like;
			$params[] = $like;
			$params[] = $like;
			$params[] = $like;
			$params[] = $like;
		}

		if ( '' !== $filters['status'] ) {
			$where[]  = 'status = %s';
			$params[] = $filters['status'];
		}

		if ( '' !== $filters['country'] ) {
			$where[]  = 'country = %s';
			$params[] = $filters['country'];
		}

		if ( $filters['form_id'] > 0 ) {
			$where[]  = 'form_id = %d';
			$params[] = $filters['form_id'];
		}

		if ( '' !== $filters['date_from'] ) {
			$where[]  = 'created_at >= %s';
			$params[] = $filters['date_from'] . ' 00:00:00';
		}

		if ( '' !== $filters['date_to'] ) {
			$where[]  = 'created_at <= %s';
			$params[] = $filters['date_to'] . ' 23:59:59';
		}

		return array( implode( ' AND ', $where ), $params );
	}

	/**
	 * Filters se poori tarah query kiye gaye leads laut ata hai —
	 * pagination ke saath ($limit=0 ka matlab "sab" — exports ke liye).
	 *
	 * @param array $filters   sanitize_filters() output.
	 * @param int   $page      1-indexed page number.
	 * @param int   $per_page  Prati-page rows (0 = koi limit nahi).
	 * @return array{rows:array,total:int}
	 */
	public static function query_leads( $filters, $page = 1, $per_page = 20 ) {
		global $wpdb;

		$table = PAD_Database::table( 'leads' );

		list( $where, $params ) = self::build_where( $filters );

		$orderby = self::SORTABLE_COLUMNS[ $filters['orderby'] ];
		$order   = $filters['order'];

		$count_sql = "SELECT COUNT(id) FROM {$table} WHERE {$where}";
		$total     = (int) ( empty( $params ) ? $wpdb->get_var( $count_sql ) : $wpdb->get_var( $wpdb->prepare( $count_sql, $params ) ) );

		$sql = "SELECT * FROM {$table} WHERE {$where} ORDER BY {$orderby} {$order}";

		if ( $per_page > 0 ) {
			$page      = max( 1, $page );
			$offset    = ( $page - 1 ) * $per_page;
			$sql      .= ' LIMIT %d OFFSET %d';
			$params[]  = $per_page;
			$params[]  = $offset;
		}

		$rows = empty( $params ) ? $wpdb->get_results( $sql ) : $wpdb->get_results( $wpdb->prepare( $sql, $params ) );

		return array(
			'rows'  => $rows,
			'total' => $total,
		);
	}

	/**
	 * Leads list — filters, sort, pagination sab AJAX se.
	 *
	 * @return void
	 */
	public function ajax_list() {

		check_ajax_referer( 'pad_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'pad_manage_leads' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'premium-analytics-dashboard-pro' ) ), 403 );
		}

		$filters  = self::sanitize_filters( wp_unslash( $_GET ) );
		$page     = isset( $_GET['page'] ) ? max( 1, absint( $_GET['page'] ) ) : 1;
		$per_page = isset( $_GET['per_page'] ) ? absint( $_GET['per_page'] ) : 20;
		$per_page = in_array( $per_page, array( 10, 20, 50, 100 ), true ) ? $per_page : 20;

		$result = self::query_leads( $filters, $page, $per_page );

		$rows = array();

		foreach ( $result['rows'] as $lead ) {
			$rows[] = self::format_lead_row( $lead );
		}

		wp_send_json_success(
			array(
				'rows'        => $rows,
				'total'       => $result['total'],
				'page'        => $page,
				'per_page'    => $per_page,
				'total_pages' => $per_page > 0 ? (int) ceil( $result['total'] / $per_page ) : 1,
			)
		);
	}

	/**
	 * DB row ko frontend-friendly, pehle se escape-ready array me convert karta hai.
	 *
	 * @param object $lead wpdb row object.
	 * @return array
	 */
	private static function format_lead_row( $lead ) {

		return array(
			'id'         => (int) $lead->id,
			'form_name'  => $lead->form_name,
			'name'       => trim( $lead->first_name . ' ' . $lead->last_name ),
			'email'      => $lead->email,
			'phone'      => $lead->phone,
			'country'    => $lead->country,
			'status'     => $lead->status,
			'date'       => mysql2date( 'd M Y, H:i', $lead->created_at ),
		);
	}

	/**
	 * Ek lead ki poori detail — structured fields + dynamic meta fields
	 * + notes + tags — sab ek response me.
	 *
	 * @return void
	 */
	public function ajax_detail() {

		check_ajax_referer( 'pad_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'pad_manage_leads' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'premium-analytics-dashboard-pro' ) ), 403 );
		}

		$lead_id = isset( $_GET['lead_id'] ) ? absint( $_GET['lead_id'] ) : 0;

		if ( ! $lead_id ) {
			wp_send_json_error( array( 'message' => 'invalid_lead' ), 400 );
		}

		global $wpdb;

		$lead = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . PAD_Database::table( 'leads' ) . ' WHERE id = %d', $lead_id ) );

		if ( ! $lead ) {
			wp_send_json_error( array( 'message' => 'not_found' ), 404 );
		}

		wp_send_json_success(
			array(
				'lead'  => $lead,
				'meta'  => self::get_meta( $lead_id ),
				'notes' => self::get_notes( $lead_id ),
				'tags'  => self::get_tags( $lead_id ),
			)
		);
	}

	/**
	 * Lead ki dynamic meta fields (jaisa CF7 form submit hua tha, verbatim).
	 * Public hai kyunki PAD_Leads_Export bhi isi ko reuse karta hai.
	 *
	 * @param int $lead_id Lead ID.
	 * @return array
	 */
	public static function get_meta( $lead_id ) {
		global $wpdb;

		return $wpdb->get_results(
			$wpdb->prepare( 'SELECT meta_key, meta_value FROM ' . PAD_Database::table( 'lead_meta' ) . ' WHERE lead_id = %d', $lead_id )
		);
	}

	/**
	 * Lead ke notes, sabse naya sabse upar.
	 *
	 * @param int $lead_id Lead ID.
	 * @return array
	 */
	private static function get_notes( $lead_id ) {
		global $wpdb;

		$rows = $wpdb->get_results(
			$wpdb->prepare( 'SELECT id, user_id, note, created_at FROM ' . PAD_Database::table( 'lead_notes' ) . ' WHERE lead_id = %d ORDER BY created_at DESC', $lead_id )
		);

		$notes = array();

		foreach ( $rows as $row ) {
			$user    = get_userdata( (int) $row->user_id );
			$notes[] = array(
				'id'        => (int) $row->id,
				'author'    => $user ? $user->display_name : __( 'Unknown', 'premium-analytics-dashboard-pro' ),
				'note'      => $row->note,
				'created_at' => mysql2date( 'd M Y, H:i', $row->created_at ),
			);
		}

		return $notes;
	}

	/**
	 * Lead ke tags.
	 *
	 * @param int $lead_id Lead ID.
	 * @return array
	 */
	private static function get_tags( $lead_id ) {
		global $wpdb;

		$tags_table     = PAD_Database::table( 'tags' );
		$lead_tags_table = PAD_Database::table( 'lead_tags' );

		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT t.id, t.name, t.color FROM {$tags_table} t INNER JOIN {$lead_tags_table} lt ON lt.tag_id = t.id WHERE lt.lead_id = %d ORDER BY t.name ASC",
				$lead_id
			)
		);
	}

	/**
	 * Lead ka status update karta hai.
	 *
	 * @return void
	 */
	public function ajax_update_status() {

		check_ajax_referer( 'pad_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'pad_manage_leads' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'premium-analytics-dashboard-pro' ) ), 403 );
		}

		$lead_id = isset( $_POST['lead_id'] ) ? absint( $_POST['lead_id'] ) : 0;
		$status  = isset( $_POST['status'] ) ? sanitize_key( wp_unslash( $_POST['status'] ) ) : '';

		if ( ! $lead_id || ! in_array( $status, self::STATUSES, true ) ) {
			wp_send_json_error( array( 'message' => 'invalid_request' ), 400 );
		}

		global $wpdb;

		$wpdb->update(
			PAD_Database::table( 'leads' ),
			array( 'status' => $status ),
			array( 'id' => $lead_id ),
			array( '%s' ),
			array( '%d' )
		);

		PAD_Helper::log( 'lead_status_updated', 'lead', $lead_id, sprintf(
			/* translators: %s: new status */
			__( 'Lead ka status "%s" me badla gaya.', 'premium-analytics-dashboard-pro' ),
			$status
		) );

		wp_send_json_success();
	}

	/**
	 * Lead me ek nayi note add karta hai.
	 *
	 * @return void
	 */
	public function ajax_add_note() {

		check_ajax_referer( 'pad_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'pad_manage_leads' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'premium-analytics-dashboard-pro' ) ), 403 );
		}

		$lead_id = isset( $_POST['lead_id'] ) ? absint( $_POST['lead_id'] ) : 0;
		$note    = isset( $_POST['note'] ) ? sanitize_textarea_field( wp_unslash( $_POST['note'] ) ) : '';

		if ( ! $lead_id || '' === trim( $note ) ) {
			wp_send_json_error( array( 'message' => 'invalid_request' ), 400 );
		}

		global $wpdb;

		$wpdb->insert(
			PAD_Database::table( 'lead_notes' ),
			array(
				'lead_id'    => $lead_id,
				'user_id'    => get_current_user_id(),
				'note'       => $note,
				'created_at' => current_time( 'mysql' ),
			)
		);

		wp_send_json_success( array( 'notes' => self::get_notes( $lead_id ) ) );
	}

	/**
	 * Lead me ek tag add karta hai — agar tag exist nahi karta to naya create ho jaata hai.
	 *
	 * @return void
	 */
	public function ajax_add_tag() {

		check_ajax_referer( 'pad_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'pad_manage_leads' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'premium-analytics-dashboard-pro' ) ), 403 );
		}

		$lead_id  = isset( $_POST['lead_id'] ) ? absint( $_POST['lead_id'] ) : 0;
		$tag_name = isset( $_POST['tag_name'] ) ? sanitize_text_field( wp_unslash( $_POST['tag_name'] ) ) : '';

		if ( ! $lead_id || '' === $tag_name ) {
			wp_send_json_error( array( 'message' => 'invalid_request' ), 400 );
		}

		global $wpdb;

		$tags_table = PAD_Database::table( 'tags' );

		$tag_id = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$tags_table} WHERE name = %s", $tag_name ) );

		if ( ! $tag_id ) {
			$wpdb->insert( $tags_table, array( 'name' => $tag_name, 'created_at' => current_time( 'mysql' ) ) );
			$tag_id = (int) $wpdb->insert_id;
		}

		$lead_tags_table = PAD_Database::table( 'lead_tags' );
		$exists          = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$lead_tags_table} WHERE lead_id = %d AND tag_id = %d", $lead_id, $tag_id ) );

		if ( ! $exists ) {
			$wpdb->insert( $lead_tags_table, array( 'lead_id' => $lead_id, 'tag_id' => $tag_id ) );
		}

		wp_send_json_success( array( 'tags' => self::get_tags( $lead_id ) ) );
	}

	/**
	 * Lead se ek tag remove karta hai.
	 *
	 * @return void
	 */
	public function ajax_remove_tag() {

		check_ajax_referer( 'pad_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'pad_manage_leads' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'premium-analytics-dashboard-pro' ) ), 403 );
		}

		$lead_id = isset( $_POST['lead_id'] ) ? absint( $_POST['lead_id'] ) : 0;
		$tag_id  = isset( $_POST['tag_id'] ) ? absint( $_POST['tag_id'] ) : 0;

		if ( ! $lead_id || ! $tag_id ) {
			wp_send_json_error( array( 'message' => 'invalid_request' ), 400 );
		}

		global $wpdb;

		$wpdb->delete(
			PAD_Database::table( 'lead_tags' ),
			array( 'lead_id' => $lead_id, 'tag_id' => $tag_id ),
			array( '%d', '%d' )
		);

		wp_send_json_success( array( 'tags' => self::get_tags( $lead_id ) ) );
	}

	/**
	 * Ek lead ko poori tarah delete karta hai (meta/notes/tags sahit).
	 *
	 * @return void
	 */
	public function ajax_delete() {

		check_ajax_referer( 'pad_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'pad_delete_leads' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'premium-analytics-dashboard-pro' ) ), 403 );
		}

		$lead_id = isset( $_POST['lead_id'] ) ? absint( $_POST['lead_id'] ) : 0;

		if ( ! $lead_id ) {
			wp_send_json_error( array( 'message' => 'invalid_request' ), 400 );
		}

		self::delete_leads( array( $lead_id ) );

		wp_send_json_success();
	}

	/**
	 * Multiple leads ek saath delete karta hai.
	 *
	 * @return void
	 */
	public function ajax_bulk_delete() {

		check_ajax_referer( 'pad_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'pad_delete_leads' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'premium-analytics-dashboard-pro' ) ), 403 );
		}

		$ids = isset( $_POST['lead_ids'] ) ? array_map( 'absint', (array) wp_unslash( $_POST['lead_ids'] ) ) : array();
		$ids = array_values( array_filter( $ids ) );

		if ( empty( $ids ) ) {
			wp_send_json_error( array( 'message' => 'invalid_request' ), 400 );
		}

		$deleted = self::delete_leads( $ids );

		wp_send_json_success( array( 'deleted' => $deleted ) );
	}

	/**
	 * Actual delete logic — lead(s) + unki meta/notes/tags rows.
	 *
	 * @param int[] $ids Lead IDs.
	 * @return int Deleted rows count.
	 */
	private static function delete_leads( $ids ) {
		global $wpdb;

		$ids = array_map( 'absint', $ids );

		if ( empty( $ids ) ) {
			return 0;
		}

		$placeholders = implode( ', ', array_fill( 0, count( $ids ), '%d' ) );

		$wpdb->query( $wpdb->prepare( 'DELETE FROM ' . PAD_Database::table( 'lead_meta' ) . " WHERE lead_id IN ({$placeholders})", $ids ) );
		$wpdb->query( $wpdb->prepare( 'DELETE FROM ' . PAD_Database::table( 'lead_notes' ) . " WHERE lead_id IN ({$placeholders})", $ids ) );
		$wpdb->query( $wpdb->prepare( 'DELETE FROM ' . PAD_Database::table( 'lead_tags' ) . " WHERE lead_id IN ({$placeholders})", $ids ) );

		$deleted = $wpdb->query( $wpdb->prepare( 'DELETE FROM ' . PAD_Database::table( 'leads' ) . " WHERE id IN ({$placeholders})", $ids ) );

		PAD_Helper::log( 'leads_deleted', 'lead', 0, sprintf(
			/* translators: %d: number of deleted leads */
			__( '%d lead(s) delete kiye gaye.', 'premium-analytics-dashboard-pro' ),
			count( $ids )
		) );

		PAD_Cache::bump_version();

		return (int) $deleted;
	}
}
