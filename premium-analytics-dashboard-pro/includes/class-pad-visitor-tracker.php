<?php
/**
 * Visitor & Session Tracker
 *
 * Frontend ke `pad-tracker.js` se aane wale do AJAX calls yahan
 * handle hote hain: ek page-load par (session/pageview record karta
 * hai), aur ek page chhodte waqt (time-on-page aur session duration
 * update karta hai). Dono nopriv aur logged-in, dono context me
 * available hain kyunki zyada tar visitors logged-in nahi hote.
 *
 * @package Premium_Analytics_Dashboard_Pro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class PAD_Visitor_Tracker
 */
class PAD_Visitor_Tracker {

	/**
	 * Naya pageview record karta hai: visitor upsert, session upsert,
	 * pageview insert. Response me pageview_id deta hai taaki JS baad
	 * me is par time-on-page update kar sake.
	 *
	 * @return void
	 */
	public function ajax_track_pageview() {

		check_ajax_referer( 'pad_tracker_nonce', 'nonce' );

		$visitor_hash = isset( $_COOKIE[ PAD_Public::VISITOR_COOKIE ] ) ? sanitize_text_field( wp_unslash( $_COOKIE[ PAD_Public::VISITOR_COOKIE ] ) ) : '';
		$session_key  = isset( $_COOKIE[ PAD_Public::SESSION_COOKIE ] ) ? sanitize_text_field( wp_unslash( $_COOKIE[ PAD_Public::SESSION_COOKIE ] ) ) : '';

		if ( '' === $visitor_hash || '' === $session_key ) {
			wp_send_json_error( array( 'message' => 'missing_identity' ), 400 );
		}

		$url      = isset( $_POST['url'] ) ? esc_url_raw( wp_unslash( $_POST['url'] ) ) : '';
		$title    = isset( $_POST['title'] ) ? sanitize_text_field( wp_unslash( $_POST['title'] ) ) : '';
		$screen   = isset( $_POST['screen'] ) ? sanitize_text_field( wp_unslash( $_POST['screen'] ) ) : '';
		$language = isset( $_POST['language'] ) ? sanitize_text_field( wp_unslash( $_POST['language'] ) ) : '';
		$timezone = isset( $_POST['timezone'] ) ? sanitize_text_field( wp_unslash( $_POST['timezone'] ) ) : '';

		$user_agent = isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '';
		$geo        = PAD_GeoIP::lookup( PAD_Helper::get_client_ip() );

		$attribution     = PAD_Public::get_attribution();
		$referral_source = PAD_Helper::classify_referral_source(
			isset( $attribution['referrer'] ) ? $attribution['referrer'] : '',
			isset( $attribution['utm_source'] ) ? $attribution['utm_source'] : '',
			isset( $attribution['utm_medium'] ) ? $attribution['utm_medium'] : ''
		);

		$is_new_session = ! $this->session_exists( $session_key );

		$visitor_id  = $this->upsert_visitor( $visitor_hash, $geo, $user_agent, $screen, $language, $timezone, $referral_source, $is_new_session );
		$session_id  = $this->upsert_session( $session_key, $visitor_id, $url );
		$pageview_id = $this->insert_pageview( $session_id, $visitor_id, $url, $title );

		wp_send_json_success( array( 'pageview_id' => $pageview_id ) );
	}

	/**
	 * Visitor page chhod raha hai (tab close/navigate away) — us
	 * pageview ka time-on-page aur session ka duration/last-activity
	 * update karta hai.
	 *
	 * @return void
	 */
	public function ajax_track_pageview_end() {

		check_ajax_referer( 'pad_tracker_nonce', 'nonce' );

		global $wpdb;

		$pageview_id = isset( $_POST['pageview_id'] ) ? absint( $_POST['pageview_id'] ) : 0;
		$seconds     = isset( $_POST['seconds'] ) ? absint( $_POST['seconds'] ) : 0;

		if ( ! $pageview_id ) {
			wp_send_json_error( array( 'message' => 'missing_pageview' ), 400 );
		}

		$pageviews_table = PAD_Database::table( 'pageviews' );

		$wpdb->update(
			$pageviews_table,
			array( 'time_on_page' => $seconds ),
			array( 'id' => $pageview_id ),
			array( '%d' ),
			array( '%d' )
		);

		$session_key = isset( $_COOKIE[ PAD_Public::SESSION_COOKIE ] ) ? sanitize_text_field( wp_unslash( $_COOKIE[ PAD_Public::SESSION_COOKIE ] ) ) : '';

		if ( '' !== $session_key ) {

			$sessions_table = PAD_Database::table( 'sessions' );
			$now            = current_time( 'mysql' );

			$wpdb->query(
				$wpdb->prepare(
					"UPDATE {$sessions_table} SET ended_at = %s, last_activity_at = %s, duration = duration + %d WHERE session_key = %s",
					$now,
					$now,
					$seconds,
					$session_key
				)
			);
		}

		wp_send_json_success();
	}

	/**
	 * Kya diye gaye session_key ke saath ek session pehle se maujood hai.
	 *
	 * @param string $session_key Session cookie value.
	 * @return bool
	 */
	private function session_exists( $session_key ) {
		global $wpdb;

		$table = PAD_Database::table( 'sessions' );

		$id = $wpdb->get_var(
			$wpdb->prepare( "SELECT id FROM {$table} WHERE session_key = %s LIMIT 1", $session_key )
		);

		return (bool) $id;
	}

	/**
	 * Visitor row insert/update karta hai. Naya session shuru hone par
	 * hi `visits_count` badhta hai — pageviews ke saath nahi, taaki
	 * "Returning Visitors" ka number sahi rahe.
	 *
	 * @param string $visitor_hash    Persistent visitor cookie value.
	 * @param array  $geo             PAD_GeoIP::lookup() se mila data.
	 * @param string $user_agent      Raw user agent string.
	 * @param string $screen          Screen resolution, e.g. "1920x1080".
	 * @param string $language        Browser language, e.g. "en-US".
	 * @param string $timezone        Browser timezone, e.g. "Asia/Kolkata".
	 * @param string $referral_source Classified traffic source.
	 * @param bool   $is_new_session  Kya yeh is visitor ki nayi session hai.
	 * @return int Visitor row ID.
	 */
	private function upsert_visitor( $visitor_hash, $geo, $user_agent, $screen, $language, $timezone, $referral_source, $is_new_session ) {
		global $wpdb;

		$table = PAD_Database::table( 'visitors' );
		$now   = current_time( 'mysql' );

		$browser = PAD_Helper::detect_browser( $user_agent );
		$os      = PAD_Helper::detect_os( $user_agent );
		$device  = PAD_Helper::detect_device( $user_agent );

		$existing = $wpdb->get_row(
			$wpdb->prepare( "SELECT id, visits_count FROM {$table} WHERE visitor_hash = %s", $visitor_hash )
		);

		if ( $existing ) {

			$update = array(
				'last_seen'         => $now,
				'browser'           => $browser,
				'device'            => $device,
				'os'                => $os,
				'screen_resolution' => $screen,
				'language'          => $language,
				'timezone'          => $timezone,
			);

			if ( ! empty( $geo['country'] ) ) {
				$update['country'] = $geo['country'];
			}

			if ( ! empty( $geo['city'] ) ) {
				$update['city'] = $geo['city'];
			}

			if ( ! empty( $geo['region'] ) ) {
				$update['region'] = $geo['region'];
			}

			if ( ! empty( $geo['isp'] ) ) {
				$update['isp'] = $geo['isp'];
			}

			if ( $is_new_session ) {
				$update['visits_count'] = (int) $existing->visits_count + 1;
			}

			$wpdb->update( $table, $update, array( 'id' => $existing->id ) );

			return (int) $existing->id;
		}

		$wpdb->insert(
			$table,
			array(
				'visitor_hash'      => $visitor_hash,
				'first_seen'        => $now,
				'last_seen'         => $now,
				'visits_count'      => 1,
				'country'           => ! empty( $geo['country'] ) ? $geo['country'] : '',
				'city'              => ! empty( $geo['city'] ) ? $geo['city'] : '',
				'region'            => ! empty( $geo['region'] ) ? $geo['region'] : '',
				'timezone'          => $timezone,
				'language'          => $language,
				'isp'               => ! empty( $geo['isp'] ) ? $geo['isp'] : '',
				'browser'           => $browser,
				'device'            => $device,
				'os'                => $os,
				'screen_resolution' => $screen,
				'referral_source'   => $referral_source ? $referral_source : 'direct',
			)
		);

		return (int) $wpdb->insert_id;
	}

	/**
	 * Session row insert/update karta hai.
	 *
	 * @param string $session_key Session cookie value.
	 * @param int    $visitor_id  Related visitor row ID.
	 * @param string $current_url Is pageview ki URL.
	 * @return int Session row ID.
	 */
	private function upsert_session( $session_key, $visitor_id, $current_url ) {
		global $wpdb;

		$table = PAD_Database::table( 'sessions' );
		$now   = current_time( 'mysql' );

		$existing = $wpdb->get_row(
			$wpdb->prepare( "SELECT id, pages_visited FROM {$table} WHERE session_key = %s", $session_key )
		);

		if ( $existing ) {

			$pages_visited = (int) $existing->pages_visited + 1;

			$wpdb->update(
				$table,
				array(
					'pages_visited'    => $pages_visited,
					'exit_page'        => $current_url,
					'last_activity_at' => $now,
					'is_bounce'        => $pages_visited > 1 ? 0 : 1,
				),
				array( 'id' => $existing->id )
			);

			return (int) $existing->id;
		}

		$wpdb->insert(
			$table,
			array(
				'session_key'      => $session_key,
				'visitor_id'       => $visitor_id,
				'started_at'       => $now,
				'last_activity_at' => $now,
				'pages_visited'    => 1,
				'entry_page'       => $current_url,
				'exit_page'        => $current_url,
				'is_bounce'        => 1,
			)
		);

		return (int) $wpdb->insert_id;
	}

	/**
	 * Ek pageview record insert karta hai.
	 *
	 * @param int    $session_id Related session row ID.
	 * @param int    $visitor_id Related visitor row ID.
	 * @param string $url        Page URL.
	 * @param string $title      Page title.
	 * @return int Naya pageview row ID.
	 */
	private function insert_pageview( $session_id, $visitor_id, $url, $title ) {
		global $wpdb;

		$wpdb->insert(
			PAD_Database::table( 'pageviews' ),
			array(
				'session_id' => $session_id,
				'visitor_id' => $visitor_id,
				'url'        => $url,
				'title'      => $title,
				'viewed_at'  => current_time( 'mysql' ),
			)
		);

		return (int) $wpdb->insert_id;
	}
}
