<?php
/**
 * Versioned Transient Cache
 *
 * Dashboard stats jaisi expensive aggregate queries baar-baar chalane
 * ki bajaye kuch der cache karte hain. Har cache key ek "version
 * number" ke saath bante hain — jab bhi underlying data badalta hai
 * (naya lead, delete, status change), hum sirf version number badha
 * dete hain, is se poori cache generation ek hi baar me automatically
 * invalidate ho jaati hai (individual transient keys dhoondh kar
 * delete karne ki zaroorat nahi padti).
 *
 * @package Premium_Analytics_Dashboard_Pro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class PAD_Cache
 */
class PAD_Cache {

	/**
	 * Cache version store karne wali option ka naam.
	 *
	 * @var string
	 */
	const VERSION_OPTION = 'pad_cache_version';

	/**
	 * Current cache version laata hai (na ho to 1 se initialize karta hai).
	 *
	 * @return int
	 */
	public static function get_version() {

		$version = get_option( self::VERSION_OPTION );

		if ( ! $version ) {
			$version = 1;
			update_option( self::VERSION_OPTION, $version );
		}

		return (int) $version;
	}

	/**
	 * Cache version badha kar saari purani cached values ko ek jhatke
	 * me invalidate kar deta hai. Lead capture/delete/status-change
	 * jaisi mutation hone par yeh call hota hai.
	 *
	 * @return void
	 */
	public static function bump_version() {
		update_option( self::VERSION_OPTION, self::get_version() + 1 );
	}

	/**
	 * Agar value cache me hai to wahi return karta hai, warna callback
	 * chala kar result cache karta hai.
	 *
	 * @param string   $key      Unique cache key (plugin ke andar).
	 * @param int      $ttl      Seconds — kitni der cache valid rahegi.
	 * @param callable $callback Value generate karne wala function.
	 * @return mixed
	 */
	public static function remember( $key, $ttl, $callback ) {

		$cache_key = self::build_key( $key );
		$cached    = get_transient( $cache_key );

		if ( false !== $cached ) {
			return $cached;
		}

		$value = call_user_func( $callback );

		set_transient( $cache_key, $value, $ttl );

		return $value;
	}

	/**
	 * Final transient key banata hai — version number shamil karke.
	 *
	 * @param string $key Logical cache key.
	 * @return string
	 */
	private static function build_key( $key ) {
		return 'pad_c_' . self::get_version() . '_' . md5( $key );
	}
}
