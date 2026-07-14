<?php
/**
 * GeoIP Lookup (no external SaaS)
 *
 * "No external SaaS" ki requirement ka matlab hai ki hum har visitor
 * ki IP kisi third-party API (ip-api.com, ipinfo.io, etc.) ko kabhi
 * bhi runtime par nahi bhejenge — na free na paid. Is liye Country/
 * City sirf tabhi milti hai jab:
 *
 *   1. Site Cloudflare (ya kisi CDN) ke peeche hai jo already
 *      geo-header inject karta hai (bilkul free, koi extra API call
 *      nahi) — sabse common real-world case.
 *   2. Site owner ne khud apne server par koi local/offline GeoIP
 *      database (jaise MaxMind GeoLite2 .mmdb) install kiya ho aur
 *      `pad_geoip_reader` filter se apna reader function register
 *      kiya ho.
 *
 * In dono ke bina Country/City/ISP blank rehte hain — koi fake data
 * kabhi generate nahi hota.
 *
 * @package Premium_Analytics_Dashboard_Pro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class PAD_GeoIP
 */
class PAD_GeoIP {

	/**
	 * Diye gaye IP address ke liye jo bhi geo data zero-cost/local
	 * signals se mil sake, wapas karta hai.
	 *
	 * @param string $ip Client IP address.
	 * @return array{country:string,region:string,city:string,isp:string}
	 */
	public static function lookup( $ip ) {

		$data = array(
			'country' => '',
			'region'  => '',
			'city'    => '',
			'isp'     => '',
		);

		$data = self::from_cdn_headers( $data );

		/**
		 * Agar site owner ne apna local GeoIP reader register kiya hai
		 * (jaise MaxMind GeoLite2 .mmdb file ke saath), to usse poori
		 * detail (country/region/city/isp) mil sakti hai.
		 *
		 * @param array  $data Ab tak mila geo data.
		 * @param string $ip   Client IP address.
		 */
		$data = apply_filters( 'pad_geoip_lookup', $data, $ip );

		return $data;
	}

	/**
	 * Common CDN/proxy headers se country code padhta hai — yeh
	 * bilkul free hai kyunki koi bhi extra HTTP request nahi hoti,
	 * data pehle se hi incoming request ke headers me maujood hota hai.
	 *
	 * @param array $data Existing geo data.
	 * @return array
	 */
	private static function from_cdn_headers( $data ) {

		$headers = array(
			'HTTP_CF_IPCOUNTRY',       // Cloudflare.
			'HTTP_X_COUNTRY_CODE',     // Kai hosting providers/WAFs.
			'HTTP_X_APPENGINE_COUNTRY', // Google App Engine / GCP proxies.
		);

		foreach ( $headers as $header ) {

			if ( empty( $_SERVER[ $header ] ) ) {
				continue;
			}

			$country = strtoupper( sanitize_text_field( wp_unslash( $_SERVER[ $header ] ) ) );

			if ( 2 === strlen( $country ) && 'XX' !== $country ) {
				$data['country'] = $country;
				break;
			}
		}

		return $data;
	}
}
