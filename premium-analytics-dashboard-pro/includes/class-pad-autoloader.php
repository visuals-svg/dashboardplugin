<?php
/**
 * Class Autoloader
 *
 * Node.js/Composer ke bina hi PSR-4 jaisa autoloading. Class name se
 * file name predict karta hai: PAD_Some_Class -> class-pad-some-class.php
 *
 * @package Premium_Analytics_Dashboard_Pro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class PAD_Autoloader
 */
class PAD_Autoloader {

	/**
	 * Woh directories jahan hum class files dhoondenge, priority order me.
	 *
	 * @var string[]
	 */
	private static $directories = array(
		'includes/',
		'includes/helpers/',
		'admin/',
		'public/',
	);

	/**
	 * Autoloader ko PHP ke SPL stack me register karta hai.
	 *
	 * @return void
	 */
	public static function register() {
		spl_autoload_register( array( __CLASS__, 'autoload' ) );
	}

	/**
	 * Diye gaye class name ke liye matching file load karta hai.
	 *
	 * @param string $class_name Fully qualified class name.
	 * @return void
	 */
	public static function autoload( $class_name ) {

		if ( 0 !== strpos( $class_name, 'PAD_' ) ) {
			return;
		}

		$file_name = 'class-' . str_replace( '_', '-', strtolower( $class_name ) ) . '.php';

		foreach ( self::$directories as $directory ) {
			$path = PAD_PLUGIN_DIR . $directory . $file_name;

			if ( file_exists( $path ) ) {
				require_once $path;
				return;
			}
		}
	}
}
