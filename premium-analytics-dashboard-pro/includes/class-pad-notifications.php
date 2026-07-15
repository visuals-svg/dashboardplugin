<?php
/**
 * Notifications — Dashboard, Email, WhatsApp, Telegram
 *
 * Ek hi entry point (notify_new_lead) se saare configured channels
 * fire hote hain. Har channel independently enable/disable hota hai
 * Settings se, aur kisi bhi channel ki failure baaki channels ko
 * kabhi block nahi karti.
 *
 * WhatsApp ke liye koi single free public API nahi hoti (Meta Business
 * API paid hai, alag-alag providers hain), isliye hum ek generic,
 * admin-configured webhook URL par POST karte hain — jo bhi provider
 * (Twilio, Gupshup, 360dialog, apna khud ka relay) admin use karna
 * chahe, wahi is se jud sakta hai. Telegram ke liye Bot API free aur
 * public hai, is liye seedha official endpoint use karte hain — dono
 * cases me call sirf tabhi hota hai jab admin khud apni credentials
 * Settings me daale, humari taraf se koi silent third-party call nahi.
 *
 * @package Premium_Analytics_Dashboard_Pro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class PAD_Notifications
 */
class PAD_Notifications {

	/**
	 * Naya lead capture hone par saare configured channels par notify karta hai.
	 *
	 * @param int    $lead_id   Naya lead ka ID.
	 * @param string $form_name Form ka naam.
	 * @param string $lead_name Lead ka naam (ho sakta hai empty ho).
	 * @return void
	 */
	public static function notify_new_lead( $lead_id, $form_name, $lead_name ) {

		$settings = PAD_Helper::get_settings();
		$display_name = '' !== trim( (string) $lead_name ) ? $lead_name : __( '(no name)', 'premium-analytics-dashboard-pro' );

		$title = sprintf(
			/* translators: %s: form name */
			__( 'New lead — %s', 'premium-analytics-dashboard-pro' ),
			$form_name
		);

		$message = sprintf(
			/* translators: 1: lead name, 2: form name */
			__( '%1$s ne "%2$s" form submit kiya.', 'premium-analytics-dashboard-pro' ),
			$display_name,
			$form_name
		);

		$link = admin_url( 'admin.php?page=pad-leads' );

		self::create( 'new_lead', $title, $message, $link );

		if ( ! empty( $settings['notify_email_enabled'] ) ) {
			self::send_email( $title, $message, $link, $settings );
		}

		if ( ! empty( $settings['notify_telegram_enabled'] ) ) {
			self::send_telegram( $title . "\n" . $message, $settings );
		}

		if ( ! empty( $settings['notify_whatsapp_enabled'] ) ) {
			self::send_whatsapp_webhook( $title, $message, $lead_id, $settings );
		}
	}

	/**
	 * Ek dashboard notification record banata hai.
	 *
	 * @param string $type    Notification type key, e.g. 'new_lead'.
	 * @param string $title   Short title.
	 * @param string $message Body text.
	 * @param string $link    Related admin URL (optional).
	 * @return void
	 */
	public static function create( $type, $title, $message, $link = '' ) {
		global $wpdb;

		$wpdb->insert(
			PAD_Database::table( 'notifications' ),
			array(
				'type'       => sanitize_key( $type ),
				'title'      => sanitize_text_field( $title ),
				'message'    => sanitize_textarea_field( $message ),
				'link'       => esc_url_raw( $link ),
				'is_read'    => 0,
				'created_at' => current_time( 'mysql' ),
			)
		);
	}

	/**
	 * Configured recipients ko wp_mail() se email bhejta hai.
	 *
	 * @param string $title    Notification title.
	 * @param string $message  Notification body.
	 * @param string $link     Related admin URL.
	 * @param array  $settings PAD_Helper::get_settings() output.
	 * @return void
	 */
	private static function send_email( $title, $message, $link, $settings ) {

		$recipients = array_filter( array_map( 'trim', explode( ',', (string) $settings['notify_email_recipients'] ) ) );

		if ( empty( $recipients ) ) {
			return;
		}

		$body = $message . "\n\n" . sprintf(
			/* translators: %s: admin URL */
			__( 'Dekhein: %s', 'premium-analytics-dashboard-pro' ),
			$link
		);

		wp_mail(
			$recipients,
			sprintf(
				/* translators: 1: site name, 2: notification title */
				__( '[%1$s] %2$s', 'premium-analytics-dashboard-pro' ),
				wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ),
				$title
			),
			$body
		);
	}

	/**
	 * Telegram Bot API (free, public) ke through message bhejta hai —
	 * sirf tab jab admin ne apna Bot Token aur Chat ID configure kiya ho.
	 *
	 * @param string $text     Message text.
	 * @param array  $settings PAD_Helper::get_settings() output.
	 * @return void
	 */
	private static function send_telegram( $text, $settings ) {

		$bot_token = trim( (string) $settings['notify_telegram_bot_token'] );
		$chat_id   = trim( (string) $settings['notify_telegram_chat_id'] );

		if ( '' === $bot_token || '' === $chat_id ) {
			return;
		}

		wp_remote_post(
			'https://api.telegram.org/bot' . rawurlencode( $bot_token ) . '/sendMessage',
			array(
				'timeout' => 8,
				'body'    => array(
					'chat_id' => $chat_id,
					'text'    => $text,
				),
			)
		);
	}

	/**
	 * Admin ke apne configured webhook URL par WhatsApp notification
	 * payload POST karta hai — provider-agnostic, sirf tab jab admin
	 * ne webhook URL Settings me set kiya ho.
	 *
	 * @param string $title    Notification title.
	 * @param string $message  Notification body.
	 * @param int    $lead_id  Related lead ID.
	 * @param array  $settings PAD_Helper::get_settings() output.
	 * @return void
	 */
	private static function send_whatsapp_webhook( $title, $message, $lead_id, $settings ) {

		$webhook_url = trim( (string) $settings['notify_whatsapp_webhook_url'] );

		if ( '' === $webhook_url || ! wp_http_validate_url( $webhook_url ) ) {
			return;
		}

		wp_remote_post(
			$webhook_url,
			array(
				'timeout' => 8,
				'headers' => array( 'Content-Type' => 'application/json' ),
				'body'    => wp_json_encode(
					array(
						'title'   => $title,
						'message' => $message,
						'lead_id' => $lead_id,
						'site'    => home_url(),
					)
				),
			)
		);
	}

	/**
	 * Recent notifications aur unread count AJAX se laata hai.
	 *
	 * @return void
	 */
	public function ajax_list() {

		check_ajax_referer( 'pad_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'pad_view_dashboard' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'premium-analytics-dashboard-pro' ) ), 403 );
		}

		global $wpdb;

		$table = PAD_Database::table( 'notifications' );

		$rows = $wpdb->get_results( "SELECT id, type, title, message, link, is_read, created_at FROM {$table} ORDER BY created_at DESC LIMIT 15" );

		$unread_count = (int) $wpdb->get_var( "SELECT COUNT(id) FROM {$table} WHERE is_read = 0" );

		$items = array();

		foreach ( $rows as $row ) {
			$items[] = array(
				'id'         => (int) $row->id,
				'title'      => $row->title,
				'message'    => $row->message,
				'link'       => $row->link,
				'is_read'    => (bool) $row->is_read,
				'created_at' => mysql2date( 'd M Y, H:i', $row->created_at ),
			);
		}

		wp_send_json_success(
			array(
				'items'        => $items,
				'unread_count' => $unread_count,
			)
		);
	}

	/**
	 * Ek notification ko read maark karta hai.
	 *
	 * @return void
	 */
	public function ajax_mark_read() {

		check_ajax_referer( 'pad_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'pad_view_dashboard' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'premium-analytics-dashboard-pro' ) ), 403 );
		}

		$id = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;

		if ( ! $id ) {
			wp_send_json_error( array( 'message' => 'invalid_request' ), 400 );
		}

		global $wpdb;

		$wpdb->update(
			PAD_Database::table( 'notifications' ),
			array( 'is_read' => 1 ),
			array( 'id' => $id ),
			array( '%d' ),
			array( '%d' )
		);

		wp_send_json_success();
	}

	/**
	 * Saari notifications ko read maark karta hai.
	 *
	 * @return void
	 */
	public function ajax_mark_all_read() {

		check_ajax_referer( 'pad_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'pad_view_dashboard' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'premium-analytics-dashboard-pro' ) ), 403 );
		}

		global $wpdb;

		$wpdb->query( 'UPDATE ' . PAD_Database::table( 'notifications' ) . ' SET is_read = 1 WHERE is_read = 0' );

		wp_send_json_success();
	}

	/**
	 * Purani, already-read notifications ko clean karta hai (daily cron se).
	 *
	 * @return void
	 */
	public static function cleanup_old() {
		global $wpdb;

		$table     = PAD_Database::table( 'notifications' );
		$threshold = gmdate( 'Y-m-d H:i:s', current_time( 'timestamp', true ) - ( 30 * DAY_IN_SECONDS ) );

		$wpdb->query(
			$wpdb->prepare( "DELETE FROM {$table} WHERE is_read = 1 AND created_at < %s", $threshold )
		);
	}
}
