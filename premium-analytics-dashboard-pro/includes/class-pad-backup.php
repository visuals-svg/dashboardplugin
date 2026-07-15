<?php
/**
 * Backup & Restore
 *
 * Backup ek single JSON file download karta hai: Settings + Forms +
 * Tags + saare Leads (unki dynamic meta/notes/tags ke saath) — yaani
 * business-critical data. High-volume tracking tables (visitors,
 * sessions, pageviews, logs) jaanbhoojh kar exclude ki hain, kyunki
 * unka backup lena "site backup" ke bajaye "raw analytics event dump"
 * ban jaata, aur woh Reports/Analytics se dobara generate ho sakta hai.
 *
 * Restore hamesha "additive" hai — existing data ko kabhi overwrite/
 * wipe nahi karta. Forms aur Tags apne natural key (form_id / name) se
 * upsert hote hain; Leads hamesha nayi rows ke roop me insert hoti hain
 * (fresh IDs), taaki ek galti se dobara data loss na ho.
 *
 * @package Premium_Analytics_Dashboard_Pro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class PAD_Backup
 */
class PAD_Backup {

	/**
	 * Backup file ka JSON download deta hai.
	 *
	 * @return void
	 */
	public function export() {

		$this->guard();

		global $wpdb;

		$forms = $wpdb->get_results(
			'SELECT form_id, form_name, form_type, total_submissions, is_active FROM ' . PAD_Database::table( 'forms' ),
			ARRAY_A
		);

		$tags = $wpdb->get_results( 'SELECT name, color FROM ' . PAD_Database::table( 'tags' ), ARRAY_A );

		$leads_table = PAD_Database::table( 'leads' );
		$lead_rows   = $wpdb->get_results( "SELECT * FROM {$leads_table}", ARRAY_A );
		$leads       = array();

		foreach ( $lead_rows as $lead ) {

			$lead_id = (int) $lead['id'];

			$meta = $wpdb->get_results(
				$wpdb->prepare( 'SELECT meta_key, meta_value FROM ' . PAD_Database::table( 'lead_meta' ) . ' WHERE lead_id = %d', $lead_id ),
				ARRAY_A
			);

			$notes = $wpdb->get_results(
				$wpdb->prepare( 'SELECT user_id, note, created_at FROM ' . PAD_Database::table( 'lead_notes' ) . ' WHERE lead_id = %d', $lead_id ),
				ARRAY_A
			);

			$tag_names = $wpdb->get_col(
				$wpdb->prepare(
					'SELECT t.name FROM ' . PAD_Database::table( 'tags' ) . ' t INNER JOIN ' . PAD_Database::table( 'lead_tags' ) . ' lt ON lt.tag_id = t.id WHERE lt.lead_id = %d',
					$lead_id
				)
			);

			$leads[] = array_merge( $lead, array( 'meta' => $meta, 'notes' => $notes, 'tags' => $tag_names ) );
		}

		$payload = array(
			'plugin'         => 'premium-analytics-dashboard-pro',
			'schema_version' => PAD_DB_VERSION,
			'exported_at'    => current_time( 'mysql' ),
			'site_url'       => home_url(),
			'settings'       => PAD_Helper::get_settings(),
			'forms'          => $forms,
			'tags'           => $tags,
			'leads'          => $leads,
		);

		$json = wp_json_encode( $payload );

		nocache_headers();
		header( 'Content-Type: application/json; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="pad-backup-' . gmdate( 'Y-m-d-His' ) . '.json"' );
		header( 'Content-Length: ' . strlen( $json ) );

		echo $json; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- raw JSON file download, not HTML.
		exit;
	}

	/**
	 * Uploaded backup JSON se settings + forms + tags + leads restore karta hai.
	 *
	 * @return void
	 */
	public function restore() {

		$this->guard();

		if (
			empty( $_FILES['backup_file'] )
			|| UPLOAD_ERR_OK !== $_FILES['backup_file']['error']
			|| ! is_uploaded_file( $_FILES['backup_file']['tmp_name'] )
		) {
			$this->redirect_with_status( 'invalid_file' );
		}

		if ( $_FILES['backup_file']['size'] > 20 * MB_IN_BYTES ) {
			$this->redirect_with_status( 'too_large' );
		}

		$contents = file_get_contents( $_FILES['backup_file']['tmp_name'] ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- local temp upload, not a remote URL.
		$data     = json_decode( (string) $contents, true );

		if ( ! is_array( $data ) || empty( $data['plugin'] ) || 'premium-analytics-dashboard-pro' !== $data['plugin'] || ! isset( $data['settings'] ) ) {
			$this->redirect_with_status( 'invalid_backup' );
		}

		global $wpdb;

		$this->restore_settings( is_array( $data['settings'] ) ? $data['settings'] : array() );

		$forms_imported = $this->restore_forms( isset( $data['forms'] ) && is_array( $data['forms'] ) ? $data['forms'] : array() );

		$tag_map = $this->restore_tags( isset( $data['tags'] ) && is_array( $data['tags'] ) ? $data['tags'] : array() );

		$leads_imported = $this->restore_leads( isset( $data['leads'] ) && is_array( $data['leads'] ) ? $data['leads'] : array(), $tag_map );

		PAD_Cache::bump_version();

		PAD_Helper::log(
			'backup_restored',
			'settings',
			0,
			sprintf(
				/* translators: 1: forms count, 2: tags count, 3: leads count */
				__( 'Backup restore hui: %1$d forms, %2$d tags, %3$d leads.', 'premium-analytics-dashboard-pro' ),
				$forms_imported,
				count( $tag_map ),
				$leads_imported
			)
		);

		wp_safe_redirect(
			add_query_arg(
				array( 'page' => 'pad-settings', 'pad-restored' => '1', 'leads_imported' => $leads_imported ),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	/**
	 * Settings restore karta hai — sirf known keys, current settings ke
	 * type ke hisaab se sanitize karke.
	 *
	 * @param array $incoming Backup file ki 'settings' array.
	 * @return void
	 */
	protected function restore_settings( $incoming ) {

		$current  = PAD_Helper::get_settings();
		$restored = array();

		foreach ( $current as $key => $default_value ) {

			if ( ! array_key_exists( $key, $incoming ) ) {
				$restored[ $key ] = $default_value;
				continue;
			}

			$value = $incoming[ $key ];

			if ( is_bool( $default_value ) ) {
				$restored[ $key ] = (bool) $value;
			} elseif ( is_array( $default_value ) ) {
				$restored[ $key ] = is_array( $value ) ? array_map( 'sanitize_text_field', $value ) : $default_value;
			} else {
				$restored[ $key ] = sanitize_text_field( (string) $value );
			}
		}

		update_option( 'pad_settings', $restored );
	}

	/**
	 * Forms ko `form_id` se upsert karta hai.
	 *
	 * @param array $forms Backup file ki 'forms' array.
	 * @return int Imported count.
	 */
	protected function restore_forms( $forms ) {
		global $wpdb;

		$table    = PAD_Database::table( 'forms' );
		$imported = 0;

		foreach ( $forms as $form ) {

			if ( empty( $form['form_id'] ) ) {
				continue;
			}

			$form_id  = absint( $form['form_id'] );
			$existing = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$table} WHERE form_id = %d", $form_id ) );

			$row = array(
				'form_id'           => $form_id,
				'form_name'         => sanitize_text_field( isset( $form['form_name'] ) ? $form['form_name'] : '' ),
				'form_type'         => sanitize_key( isset( $form['form_type'] ) ? $form['form_type'] : 'cf7' ),
				'total_submissions' => absint( isset( $form['total_submissions'] ) ? $form['total_submissions'] : 0 ),
				'is_active'         => empty( $form['is_active'] ) ? 0 : 1,
				'updated_at'        => current_time( 'mysql' ),
			);

			if ( $existing ) {
				$wpdb->update( $table, $row, array( 'id' => $existing ) );
			} else {
				$row['created_at'] = current_time( 'mysql' );
				$wpdb->insert( $table, $row );
			}

			$imported++;
		}

		return $imported;
	}

	/**
	 * Tags ko `name` se upsert karta hai.
	 *
	 * @param array $tags Backup file ki 'tags' array.
	 * @return array<string,int> Tag name => tag ID map.
	 */
	protected function restore_tags( $tags ) {
		global $wpdb;

		$table = PAD_Database::table( 'tags' );
		$map   = array();

		foreach ( $tags as $tag ) {

			if ( empty( $tag['name'] ) ) {
				continue;
			}

			$name        = sanitize_text_field( $tag['name'] );
			$existing_id = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$table} WHERE name = %s", $name ) );

			if ( ! $existing_id ) {
				$color = ! empty( $tag['color'] ) ? sanitize_hex_color( $tag['color'] ) : '';
				$wpdb->insert( $table, array( 'name' => $name, 'color' => $color ? $color : '#6366f1', 'created_at' => current_time( 'mysql' ) ) );
				$existing_id = $wpdb->insert_id;
			}

			$map[ $name ] = (int) $existing_id;
		}

		return $map;
	}

	/**
	 * Leads ko naye rows ke roop me insert karta hai (fresh IDs),
	 * meta/notes/tags dono ko remapped IDs ke saath.
	 *
	 * @param array $leads   Backup file ki 'leads' array.
	 * @param array $tag_map restore_tags() se mila naam => ID map.
	 * @return int Imported count.
	 */
	protected function restore_leads( $leads, &$tag_map ) {
		global $wpdb;

		$leads_table = PAD_Database::table( 'leads' );

		$text_columns = array(
			'form_name', 'first_name', 'last_name', 'phone', 'country', 'city',
			'state', 'browser', 'os', 'device', 'utm_source', 'utm_medium',
			'utm_campaign', 'utm_term', 'utm_content',
		);

		$url_columns = array( 'referrer', 'landing_page', 'current_url' );

		$imported = 0;

		foreach ( $leads as $lead ) {

			if ( ! is_array( $lead ) ) {
				continue;
			}

			$row = array(
				'form_id'         => absint( isset( $lead['form_id'] ) ? $lead['form_id'] : 0 ),
				'message'         => sanitize_textarea_field( isset( $lead['message'] ) ? $lead['message'] : '' ),
				'uploaded_files'  => isset( $lead['uploaded_files'] ) ? sanitize_textarea_field( $lead['uploaded_files'] ) : '',
				'ip_address'      => sanitize_text_field( isset( $lead['ip_address'] ) ? $lead['ip_address'] : '' ),
				'email'           => sanitize_email( isset( $lead['email'] ) ? $lead['email'] : '' ),
				'status'          => in_array( isset( $lead['status'] ) ? $lead['status'] : '', PAD_Leads_Table::STATUSES, true ) ? $lead['status'] : 'new',
				'submission_date' => isset( $lead['submission_date'] ) ? sanitize_text_field( $lead['submission_date'] ) : current_time( 'Y-m-d' ),
				'submission_time' => isset( $lead['submission_time'] ) ? sanitize_text_field( $lead['submission_time'] ) : current_time( 'H:i:s' ),
				'created_at'      => ! empty( $lead['created_at'] ) ? sanitize_text_field( $lead['created_at'] ) : current_time( 'mysql' ),
			);

			foreach ( $text_columns as $column ) {
				$row[ $column ] = sanitize_text_field( isset( $lead[ $column ] ) ? $lead[ $column ] : '' );
			}

			foreach ( $url_columns as $column ) {
				$row[ $column ] = esc_url_raw( isset( $lead[ $column ] ) ? $lead[ $column ] : '' );
			}

			$wpdb->insert( $leads_table, $row );
			$new_lead_id = (int) $wpdb->insert_id;

			if ( ! $new_lead_id ) {
				continue;
			}

			$imported++;

			if ( ! empty( $lead['meta'] ) && is_array( $lead['meta'] ) ) {
				foreach ( $lead['meta'] as $meta ) {
					if ( empty( $meta['meta_key'] ) ) {
						continue;
					}
					$wpdb->insert(
						PAD_Database::table( 'lead_meta' ),
						array(
							'lead_id'    => $new_lead_id,
							'meta_key'   => sanitize_key( $meta['meta_key'] ),
							'meta_value' => sanitize_textarea_field( isset( $meta['meta_value'] ) ? $meta['meta_value'] : '' ),
						)
					);
				}
			}

			if ( ! empty( $lead['notes'] ) && is_array( $lead['notes'] ) ) {
				foreach ( $lead['notes'] as $note ) {
					if ( empty( $note['note'] ) ) {
						continue;
					}
					$wpdb->insert(
						PAD_Database::table( 'lead_notes' ),
						array(
							'lead_id'    => $new_lead_id,
							'user_id'    => absint( isset( $note['user_id'] ) ? $note['user_id'] : 0 ),
							'note'       => sanitize_textarea_field( $note['note'] ),
							'created_at' => ! empty( $note['created_at'] ) ? sanitize_text_field( $note['created_at'] ) : current_time( 'mysql' ),
						)
					);
				}
			}

			if ( ! empty( $lead['tags'] ) && is_array( $lead['tags'] ) ) {
				foreach ( $lead['tags'] as $tag_name ) {

					$tag_name = sanitize_text_field( $tag_name );

					if ( '' === $tag_name ) {
						continue;
					}

					if ( ! isset( $tag_map[ $tag_name ] ) ) {
						$wpdb->insert( PAD_Database::table( 'tags' ), array( 'name' => $tag_name, 'color' => '#6366f1', 'created_at' => current_time( 'mysql' ) ) );
						$tag_map[ $tag_name ] = (int) $wpdb->insert_id;
					}

					$wpdb->insert( PAD_Database::table( 'lead_tags' ), array( 'lead_id' => $new_lead_id, 'tag_id' => $tag_map[ $tag_name ] ) );
				}
			}
		}

		return $imported;
	}

	/**
	 * Settings page par ek error status ke saath redirect karke script terminate karta hai.
	 *
	 * @param string $status Error status key.
	 * @return void
	 */
	private function redirect_with_status( $status ) {
		wp_safe_redirect( add_query_arg( array( 'page' => 'pad-settings', 'pad-restore-error' => $status ), admin_url( 'admin.php' ) ) );
		exit;
	}

	/**
	 * Nonce aur capability verify karta hai.
	 *
	 * @return void
	 */
	private function guard() {

		check_admin_referer( 'pad_backup_restore' );

		if ( ! current_user_can( 'pad_manage_settings' ) ) {
			wp_die( esc_html__( 'Is action ke liye aapke paas permission nahi hai.', 'premium-analytics-dashboard-pro' ) );
		}
	}
}
