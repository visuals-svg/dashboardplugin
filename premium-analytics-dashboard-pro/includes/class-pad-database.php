<?php
/**
 * Database Schema Manager
 *
 * Plugin ki saari custom tables ka schema aur naming yahan se
 * control hota hai. Poore plugin me kahin bhi table name
 * hardcode nahi karna — hamesha PAD_Database::table( 'leads' ) use karo.
 *
 * @package Premium_Analytics_Dashboard_Pro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class PAD_Database
 */
class PAD_Database {

	/**
	 * Plugin ke logical table names, jo table() method me
	 * actual prefixed table names me convert hote hain.
	 *
	 * @var string[]
	 */
	private static $tables = array(
		'leads',
		'lead_meta',
		'lead_notes',
		'tags',
		'lead_tags',
		'forms',
		'visitors',
		'sessions',
		'pageviews',
		'logs',
	);

	/**
	 * Logical table name (jaise 'leads') se poora prefixed table
	 * name (jaise wp_pad_leads) return karta hai.
	 *
	 * @param string $name Logical table name.
	 * @return string
	 */
	public static function table( $name ) {
		global $wpdb;

		if ( ! in_array( $name, self::$tables, true ) ) {
			return '';
		}

		return $wpdb->prefix . 'pad_' . $name;
	}

	/**
	 * Saari registered logical table names return karta hai.
	 *
	 * @return string[]
	 */
	public static function get_table_keys() {
		return self::$tables;
	}

	/**
	 * dbDelta ke through saari tables create/update karta hai.
	 * Yeh function activation par aur DB version badalne par chalta hai.
	 *
	 * @return void
	 */
	public static function create_tables() {
		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$charset_collate = $wpdb->get_charset_collate();

		foreach ( self::get_schema( $charset_collate ) as $sql ) {
			dbDelta( $sql );
		}

		update_option( 'pad_db_version', PAD_DB_VERSION );
	}

	/**
	 * Agar schema version badal gaya hai to tables ko turant re-sync
	 * karta hai (dbDelta khud detect kar leta hai ki kaunse columns
	 * add/modify karne hain). Plugin bootstrap ke shuru me hi call hota
	 * hai, taaki upgrade ke baad wait na karna pade agle daily cron tak.
	 *
	 * @return void
	 */
	public static function maybe_upgrade() {

		if ( get_option( 'pad_db_version' ) !== PAD_DB_VERSION ) {
			self::create_tables();
		}
	}

	/**
	 * Har table ka CREATE TABLE SQL statement laut ata hai.
	 * dbDelta ki strict formatting rules follow ki gayi hain
	 * (do spaces before KEY, backtick-free field definitions, etc.)
	 *
	 * @param string $charset_collate wpdb charset/collate string.
	 * @return string[]
	 */
	private static function get_schema( $charset_collate ) {

		$leads      = self::table( 'leads' );
		$lead_meta  = self::table( 'lead_meta' );
		$lead_notes = self::table( 'lead_notes' );
		$tags       = self::table( 'tags' );
		$lead_tags  = self::table( 'lead_tags' );
		$forms      = self::table( 'forms' );
		$visitors   = self::table( 'visitors' );
		$sessions   = self::table( 'sessions' );
		$pageviews  = self::table( 'pageviews' );
		$logs       = self::table( 'logs' );

		$sql = array();

		// Leads — har form submission ka core record.
		$sql[] = "CREATE TABLE {$leads} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			form_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
			form_name VARCHAR(191) NOT NULL DEFAULT '',
			first_name VARCHAR(191) NOT NULL DEFAULT '',
			last_name VARCHAR(191) NOT NULL DEFAULT '',
			email VARCHAR(191) NOT NULL DEFAULT '',
			phone VARCHAR(50) NOT NULL DEFAULT '',
			country VARCHAR(100) NOT NULL DEFAULT '',
			city VARCHAR(100) NOT NULL DEFAULT '',
			state VARCHAR(100) NOT NULL DEFAULT '',
			message LONGTEXT NULL,
			uploaded_files LONGTEXT NULL,
			ip_address VARCHAR(45) NOT NULL DEFAULT '',
			browser VARCHAR(100) NOT NULL DEFAULT '',
			os VARCHAR(100) NOT NULL DEFAULT '',
			device VARCHAR(50) NOT NULL DEFAULT '',
			referrer TEXT NULL,
			landing_page TEXT NULL,
			current_url TEXT NULL,
			utm_source VARCHAR(191) NOT NULL DEFAULT '',
			utm_medium VARCHAR(191) NOT NULL DEFAULT '',
			utm_campaign VARCHAR(191) NOT NULL DEFAULT '',
			utm_term VARCHAR(191) NOT NULL DEFAULT '',
			utm_content VARCHAR(191) NOT NULL DEFAULT '',
			status VARCHAR(50) NOT NULL DEFAULT 'new',
			submission_date DATE NULL,
			submission_time TIME NULL,
			created_at DATETIME NOT NULL,
			PRIMARY KEY  (id),
			KEY form_id (form_id),
			KEY email (email),
			KEY status (status),
			KEY country (country),
			KEY created_at (created_at)
		) {$charset_collate};";

		// Lead meta — dynamic form fields, kabhi bhi hardcode nahi karte.
		$sql[] = "CREATE TABLE {$lead_meta} (
			meta_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			lead_id BIGINT UNSIGNED NOT NULL,
			meta_key VARCHAR(191) NOT NULL DEFAULT '',
			meta_value LONGTEXT NULL,
			PRIMARY KEY  (meta_id),
			KEY lead_id (lead_id),
			KEY meta_key (meta_key)
		) {$charset_collate};";

		// Lead notes — team members lead par internal notes daal sakein.
		$sql[] = "CREATE TABLE {$lead_notes} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			lead_id BIGINT UNSIGNED NOT NULL,
			user_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
			note LONGTEXT NULL,
			created_at DATETIME NOT NULL,
			PRIMARY KEY  (id),
			KEY lead_id (lead_id)
		) {$charset_collate};";

		// Tags — leads ko categorize karne ke liye.
		$sql[] = "CREATE TABLE {$tags} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			name VARCHAR(100) NOT NULL,
			color VARCHAR(20) NOT NULL DEFAULT '#6366f1',
			created_at DATETIME NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY name (name)
		) {$charset_collate};";

		// Lead <-> Tag many-to-many mapping.
		$sql[] = "CREATE TABLE {$lead_tags} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			lead_id BIGINT UNSIGNED NOT NULL,
			tag_id BIGINT UNSIGNED NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY lead_tag (lead_id, tag_id)
		) {$charset_collate};";

		// Forms registry — auto-detected Contact Form 7 forms.
		$sql[] = "CREATE TABLE {$forms} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			form_id BIGINT UNSIGNED NOT NULL,
			form_name VARCHAR(191) NOT NULL DEFAULT '',
			form_type VARCHAR(50) NOT NULL DEFAULT 'cf7',
			total_submissions BIGINT UNSIGNED NOT NULL DEFAULT 0,
			is_active TINYINT(1) NOT NULL DEFAULT 1,
			created_at DATETIME NOT NULL,
			updated_at DATETIME NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY form_id (form_id)
		) {$charset_collate};";

		// Visitors — unique visitor fingerprint aur unke geo/device data.
		$sql[] = "CREATE TABLE {$visitors} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			visitor_hash VARCHAR(64) NOT NULL,
			first_seen DATETIME NOT NULL,
			last_seen DATETIME NOT NULL,
			visits_count INT UNSIGNED NOT NULL DEFAULT 1,
			country VARCHAR(100) NOT NULL DEFAULT '',
			city VARCHAR(100) NOT NULL DEFAULT '',
			region VARCHAR(100) NOT NULL DEFAULT '',
			timezone VARCHAR(100) NOT NULL DEFAULT '',
			language VARCHAR(20) NOT NULL DEFAULT '',
			isp VARCHAR(191) NOT NULL DEFAULT '',
			browser VARCHAR(100) NOT NULL DEFAULT '',
			device VARCHAR(50) NOT NULL DEFAULT '',
			os VARCHAR(100) NOT NULL DEFAULT '',
			screen_resolution VARCHAR(20) NOT NULL DEFAULT '',
			referral_source VARCHAR(100) NOT NULL DEFAULT 'direct',
			PRIMARY KEY  (id),
			UNIQUE KEY visitor_hash (visitor_hash),
			KEY country (country),
			KEY last_seen (last_seen)
		) {$charset_collate};";

		// Sessions — ek visitor ki ek visit (browsing session).
		$sql[] = "CREATE TABLE {$sessions} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			session_key VARCHAR(64) NOT NULL,
			visitor_id BIGINT UNSIGNED NOT NULL,
			started_at DATETIME NOT NULL,
			ended_at DATETIME NULL,
			last_activity_at DATETIME NULL,
			duration INT UNSIGNED NOT NULL DEFAULT 0,
			pages_visited INT UNSIGNED NOT NULL DEFAULT 0,
			entry_page TEXT NULL,
			exit_page TEXT NULL,
			is_bounce TINYINT(1) NOT NULL DEFAULT 1,
			PRIMARY KEY  (id),
			UNIQUE KEY session_key (session_key),
			KEY visitor_id (visitor_id),
			KEY started_at (started_at),
			KEY last_activity_at (last_activity_at)
		) {$charset_collate};";

		// Pageviews — session ke andar visit hui har page.
		$sql[] = "CREATE TABLE {$pageviews} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			session_id BIGINT UNSIGNED NOT NULL,
			visitor_id BIGINT UNSIGNED NOT NULL,
			url TEXT NULL,
			title VARCHAR(255) NOT NULL DEFAULT '',
			viewed_at DATETIME NOT NULL,
			time_on_page INT UNSIGNED NOT NULL DEFAULT 0,
			PRIMARY KEY  (id),
			KEY session_id (session_id),
			KEY visitor_id (visitor_id),
			KEY viewed_at (viewed_at)
		) {$charset_collate};";

		// Logs — plugin ke andar hone wale important actions ka audit trail.
		$sql[] = "CREATE TABLE {$logs} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			user_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
			action VARCHAR(191) NOT NULL DEFAULT '',
			object_type VARCHAR(100) NOT NULL DEFAULT '',
			object_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
			description TEXT NULL,
			ip_address VARCHAR(45) NOT NULL DEFAULT '',
			created_at DATETIME NOT NULL,
			PRIMARY KEY  (id),
			KEY user_id (user_id),
			KEY action (action),
			KEY created_at (created_at)
		) {$charset_collate};";

		return $sql;
	}
}
