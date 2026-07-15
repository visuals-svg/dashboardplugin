<?php
/**
 * Contact Form 7 Integration
 *
 * Har Contact Form 7 form (unlimited forms, koi bhi) ka har successful
 * submission yahan capture hota hai. Field names kabhi hardcode nahi
 * kiye — har posted field `pad_lead_meta` me apne asli naam ke saath
 * store hota hai, aur field ke TYPE (email/tel/textarea) ke aadhaar
 * par structured columns (email/phone/message) me bhi map hota hai.
 *
 * Hook 'wpcf7_before_send_mail' par chalte hain kyunki yeh validation
 * pass hone ke baad, mail actually bhejne se pehle fire hota hai —
 * isse lead tab bhi capture hoti hai jab SMTP/email delivery fail ho jaaye.
 *
 * @package Premium_Analytics_Dashboard_Pro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class PAD_CF7_Integration
 */
class PAD_CF7_Integration {

	/**
	 * Ek CF7 submission ko poori tarah capture karta hai: structured
	 * fields, raw dynamic fields, uploaded files, attribution, device info.
	 *
	 * @param WPCF7_ContactForm $contact_form Submitted form instance.
	 * @return void
	 */
	public function capture_submission( $contact_form ) {

		if ( ! is_a( $contact_form, 'WPCF7_ContactForm' ) ) {
			return;
		}

		$submission = WPCF7_Submission::get_instance();

		if ( ! $submission ) {
			return;
		}

		$posted_data = $submission->get_posted_data();

		if ( empty( $posted_data ) ) {
			return;
		}

		global $wpdb;

		$structured  = $this->extract_structured_fields( $contact_form, $posted_data );
		$attribution = PAD_Public::get_attribution();
		$files       = $this->handle_uploaded_files( $submission );
		$user_agent  = isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '';
		$current_url = isset( $_SERVER['HTTP_REFERER'] ) ? esc_url_raw( wp_unslash( $_SERVER['HTTP_REFERER'] ) ) : '';

		$inserted = $wpdb->insert(
			PAD_Database::table( 'leads' ),
			array(
				'form_id'         => $contact_form->id(),
				'form_name'       => $contact_form->title(),
				'first_name'      => $structured['first_name'],
				'last_name'       => $structured['last_name'],
				'email'           => $structured['email'],
				'phone'           => $structured['phone'],
				'country'         => $structured['country'],
				'city'            => $structured['city'],
				'state'           => $structured['state'],
				'message'         => $structured['message'],
				'uploaded_files'  => wp_json_encode( $files ),
				'ip_address'      => PAD_Helper::get_client_ip(),
				'browser'         => PAD_Helper::detect_browser( $user_agent ),
				'os'              => PAD_Helper::detect_os( $user_agent ),
				'device'          => PAD_Helper::detect_device( $user_agent ),
				'referrer'        => isset( $attribution['referrer'] ) ? $attribution['referrer'] : '',
				'landing_page'    => isset( $attribution['landing_page'] ) ? $attribution['landing_page'] : '',
				'current_url'     => $current_url,
				'utm_source'      => isset( $attribution['utm_source'] ) ? $attribution['utm_source'] : '',
				'utm_medium'      => isset( $attribution['utm_medium'] ) ? $attribution['utm_medium'] : '',
				'utm_campaign'    => isset( $attribution['utm_campaign'] ) ? $attribution['utm_campaign'] : '',
				'utm_term'        => isset( $attribution['utm_term'] ) ? $attribution['utm_term'] : '',
				'utm_content'     => isset( $attribution['utm_content'] ) ? $attribution['utm_content'] : '',
				'status'          => 'new',
				'submission_date' => current_time( 'Y-m-d' ),
				'submission_time' => current_time( 'H:i:s' ),
				'created_at'      => current_time( 'mysql' ),
			),
			array(
				'%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s',
				'%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s',
				'%s', '%s', '%s', '%s', '%s', '%s',
			)
		);

		if ( ! $inserted ) {
			return;
		}

		$lead_id = (int) $wpdb->insert_id;

		$this->save_dynamic_fields( $lead_id, $posted_data );
		$this->upsert_form_registry( $contact_form );

		PAD_Helper::log(
			'lead_captured',
			'lead',
			$lead_id,
			sprintf(
				/* translators: %s: contact form title */
				__( 'Naya lead capture hua form "%s" se.', 'premium-analytics-dashboard-pro' ),
				$contact_form->title()
			)
		);

		PAD_Notifications::notify_new_lead( $lead_id, $contact_form->title(), trim( $structured['first_name'] . ' ' . $structured['last_name'] ) );
	}

	/**
	 * CF7 form-tag TYPES (email/tel/textarea) aur tag-name heuristics
	 * ke through structured lead fields nikaalta hai. Koi bhi exact
	 * field name required nahi hai — sirf tag type ka use hota hai
	 * jahan mumkin ho, aur naam-based heuristic sirf fallback ke roop me.
	 *
	 * @param WPCF7_ContactForm $contact_form CF7 form instance.
	 * @param array              $posted_data  Submission ka posted data.
	 * @return array{first_name:string,last_name:string,email:string,phone:string,country:string,city:string,state:string,message:string}
	 */
	private function extract_structured_fields( $contact_form, $posted_data ) {

		$result = array(
			'first_name' => '',
			'last_name'  => '',
			'email'      => '',
			'phone'      => '',
			'country'    => '',
			'city'       => '',
			'state'      => '',
			'message'    => '',
		);

		$full_name_candidate = '';

		foreach ( $contact_form->scan_form_tags() as $tag ) {

			$name = $tag->name;

			if ( empty( $name ) || ! isset( $posted_data[ $name ] ) ) {
				continue;
			}

			$raw_value = $posted_data[ $name ];

			if ( is_array( $raw_value ) ) {
				$raw_value = implode( ', ', $raw_value );
			}

			$basetype  = $tag->basetype;
			$lower     = strtolower( $name );
			$sanitized = sanitize_text_field( $raw_value );

			if ( 'email' === $basetype && '' === $result['email'] ) {
				$result['email'] = sanitize_email( $raw_value );
				continue;
			}

			if ( 'tel' === $basetype && '' === $result['phone'] ) {
				$result['phone'] = $sanitized;
				continue;
			}

			if ( 'textarea' === $basetype ) {
				$message         = sanitize_textarea_field( $raw_value );
				$result['message'] = $result['message'] ? $result['message'] . "\n" . $message : $message;
				continue;
			}

			if ( false !== strpos( $lower, 'country' ) ) {
				$result['country'] = $sanitized;
				continue;
			}

			if ( false !== strpos( $lower, 'city' ) ) {
				$result['city'] = $sanitized;
				continue;
			}

			if ( false !== strpos( $lower, 'state' ) || false !== strpos( $lower, 'province' ) ) {
				$result['state'] = $sanitized;
				continue;
			}

			if ( false !== strpos( $lower, 'first' ) && false !== strpos( $lower, 'name' ) ) {
				$result['first_name'] = $sanitized;
				continue;
			}

			if ( false !== strpos( $lower, 'last' ) && false !== strpos( $lower, 'name' ) ) {
				$result['last_name'] = $sanitized;
				continue;
			}

			if ( 'text' === $basetype && false !== strpos( $lower, 'name' ) && '' === $full_name_candidate ) {
				$full_name_candidate = $sanitized;
			}
		}

		// Agar first/last alag se nahi mile lekin ek generic "name" field mila,
		// to use "First Last" maan kar split kar dete hain.
		if ( '' === $result['first_name'] && '' === $result['last_name'] && '' !== $full_name_candidate ) {
			$parts                 = preg_split( '/\s+/', trim( $full_name_candidate ), 2 );
			$result['first_name']  = isset( $parts[0] ) ? $parts[0] : '';
			$result['last_name']   = isset( $parts[1] ) ? $parts[1] : '';
		}

		return $result;
	}

	/**
	 * Har posted field (jo bhi uska naam ho) `pad_lead_meta` me save
	 * karta hai — is se koi bhi custom field kabhi miss nahi hota.
	 *
	 * @param int   $lead_id     Newly inserted lead ID.
	 * @param array $posted_data CF7 posted data.
	 * @return void
	 */
	private function save_dynamic_fields( $lead_id, $posted_data ) {
		global $wpdb;

		$table = PAD_Database::table( 'lead_meta' );

		foreach ( $posted_data as $key => $value ) {

			if ( is_array( $value ) ) {
				$value = implode( ', ', $value );
			}

			$wpdb->insert(
				$table,
				array(
					'lead_id'    => $lead_id,
					'meta_key'   => sanitize_key( $key ),
					'meta_value' => sanitize_textarea_field( $value ),
				),
				array( '%d', '%s', '%s' )
			);
		}
	}

	/**
	 * `pad_forms` registry ko update karta hai — naya form ho to insert,
	 * warna submission counter badha dete hain.
	 *
	 * @param WPCF7_ContactForm $contact_form CF7 form instance.
	 * @return void
	 */
	private function upsert_form_registry( $contact_form ) {
		global $wpdb;

		$table   = PAD_Database::table( 'forms' );
		$form_id = $contact_form->id();
		$now     = current_time( 'mysql' );

		$existing = $wpdb->get_row(
			$wpdb->prepare( "SELECT id, total_submissions FROM {$table} WHERE form_id = %d", $form_id )
		);

		if ( $existing ) {
			$wpdb->update(
				$table,
				array(
					'form_name'         => $contact_form->title(),
					'total_submissions' => (int) $existing->total_submissions + 1,
					'is_active'         => 1,
					'updated_at'        => $now,
				),
				array( 'id' => $existing->id ),
				array( '%s', '%d', '%d', '%s' ),
				array( '%d' )
			);

			return;
		}

		$wpdb->insert(
			$table,
			array(
				'form_id'            => $form_id,
				'form_name'          => $contact_form->title(),
				'form_type'          => 'cf7',
				'total_submissions'  => 1,
				'is_active'          => 1,
				'created_at'         => $now,
				'updated_at'         => $now,
			),
			array( '%d', '%s', '%s', '%d', '%d', '%s', '%s' )
		);
	}

	/**
	 * CF7 ke temporary uploaded files ko plugin ki apni permanent
	 * uploads directory me copy karta hai (CF7 apni temp files
	 * request khatam hote hi delete kar deta hai).
	 *
	 * @param WPCF7_Submission $submission Current submission instance.
	 * @return string[] Permanent file URLs.
	 */
	private function handle_uploaded_files( $submission ) {

		$uploaded = $submission->uploaded_files();

		if ( empty( $uploaded ) ) {
			return array();
		}

		$upload_dir = wp_upload_dir();
		$target_dir = trailingslashit( $upload_dir['basedir'] ) . 'pad-leads/' . gmdate( 'Y/m' );

		if ( ! file_exists( $target_dir ) ) {
			wp_mkdir_p( $target_dir );
		}

		$saved_urls = array();

		foreach ( $uploaded as $paths ) {

			foreach ( (array) $paths as $tmp_path ) {

				if ( ! is_string( $tmp_path ) || ! file_exists( $tmp_path ) ) {
					continue;
				}

				$filename    = wp_unique_filename( $target_dir, wp_basename( $tmp_path ) );
				$destination = trailingslashit( $target_dir ) . $filename;

				if ( copy( $tmp_path, $destination ) ) {
					$saved_urls[] = str_replace( $upload_dir['basedir'], $upload_dir['baseurl'], $destination );
				}
			}
		}

		return $saved_urls;
	}
}
