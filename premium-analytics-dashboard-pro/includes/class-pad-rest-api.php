<?php
/**
 * REST API
 *
 * External systems ke liye ek chhota, read-only REST API — `pad/v1`
 * namespace ke andar. Login-based nonce yahan kaam nahi karta (caller
 * WordPress session me nahi hota), is liye ek static API key use hoti
 * hai jo admin Settings se generate/regenerate kar sakta hai.
 *
 * @package Premium_Analytics_Dashboard_Pro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class PAD_REST_API
 */
class PAD_REST_API {

	/**
	 * REST namespace.
	 *
	 * @var string
	 */
	const NAMESPACE_NAME = 'pad/v1';

	/**
	 * Saare REST routes register karta hai.
	 *
	 * @return void
	 */
	public function register_routes() {

		register_rest_route(
			self::NAMESPACE_NAME,
			'/leads',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_leads' ),
				'permission_callback' => array( $this, 'check_api_key' ),
			)
		);

		register_rest_route(
			self::NAMESPACE_NAME,
			'/stats',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_stats' ),
				'permission_callback' => array( $this, 'check_api_key' ),
			)
		);
	}

	/**
	 * `X-PAD-API-Key` header (ya `api_key` query param) ko stored key
	 * se timing-safe compare karta hai.
	 *
	 * @param WP_REST_Request $request Current request.
	 * @return bool
	 */
	public function check_api_key( $request ) {

		$provided = $request->get_header( 'x-pad-api-key' );

		if ( empty( $provided ) ) {
			$provided = $request->get_param( 'api_key' );
		}

		if ( empty( $provided ) ) {
			return false;
		}

		$stored = self::get_api_key();

		if ( empty( $stored ) ) {
			return false;
		}

		return hash_equals( $stored, (string) $provided );
	}

	/**
	 * GET /pad/v1/leads — paginated, filtered lead listing.
	 *
	 * @param WP_REST_Request $request Current request.
	 * @return WP_REST_Response
	 */
	public function get_leads( $request ) {

		$params   = $request->get_params();
		$filters  = PAD_Leads_Table::sanitize_filters( $params );
		$page     = isset( $params['page'] ) ? max( 1, absint( $params['page'] ) ) : 1;
		$per_page = isset( $params['per_page'] ) ? absint( $params['per_page'] ) : 20;
		$per_page = in_array( $per_page, array( 10, 20, 50, 100 ), true ) ? $per_page : 20;

		$result = PAD_Leads_Table::query_leads( $filters, $page, $per_page );

		$leads = array_map(
			function ( $lead ) {
				return array(
					'id'         => (int) $lead->id,
					'form_name'  => $lead->form_name,
					'first_name' => $lead->first_name,
					'last_name'  => $lead->last_name,
					'email'      => $lead->email,
					'phone'      => $lead->phone,
					'country'    => $lead->country,
					'city'       => $lead->city,
					'status'     => $lead->status,
					'created_at' => $lead->created_at,
				);
			},
			$result['rows']
		);

		return new WP_REST_Response(
			array(
				'total'    => $result['total'],
				'page'     => $page,
				'per_page' => $per_page,
				'leads'    => $leads,
			),
			200
		);
	}

	/**
	 * GET /pad/v1/stats — dashboard summary numbers.
	 *
	 * @return WP_REST_Response
	 */
	public function get_stats() {

		return new WP_REST_Response(
			array(
				'leads_today'      => PAD_Stats::get_leads_count( 'today' ),
				'leads_this_week'  => PAD_Stats::get_leads_count( 'week' ),
				'leads_this_month' => PAD_Stats::get_leads_count( 'month' ),
				'leads_total'      => PAD_Stats::get_leads_count( 'all' ),
				'total_visitors'   => PAD_Stats::get_total_visitors(),
				'visitors_online'  => PAD_Stats::get_visitors_online(),
				'conversion_rate'  => PAD_Stats::get_conversion_rate(),
				'bounce_rate'      => PAD_Stats::get_bounce_rate(),
			),
			200
		);
	}

	/**
	 * Current API key laata hai — pehli baar call hone par ek naya
	 * random key generate karke store kar deta hai.
	 *
	 * @return string
	 */
	public static function get_api_key() {

		$key = get_option( 'pad_api_key' );

		if ( empty( $key ) ) {
			$key = self::generate_key();
			update_option( 'pad_api_key', $key );
		}

		return $key;
	}

	/**
	 * Ek naya random API key banata hai (40 chars, alphanumeric).
	 *
	 * @return string
	 */
	private static function generate_key() {
		return wp_generate_password( 40, false, false );
	}

	/**
	 * Settings se "Regenerate API Key" button ka handler.
	 *
	 * @return void
	 */
	public function handle_regenerate_key() {

		check_admin_referer( 'pad_regenerate_api_key' );

		if ( ! current_user_can( 'pad_manage_settings' ) ) {
			wp_die( esc_html__( 'Is action ke liye aapke paas permission nahi hai.', 'premium-analytics-dashboard-pro' ) );
		}

		update_option( 'pad_api_key', self::generate_key() );

		PAD_Helper::log( 'api_key_regenerated', 'settings', 0, __( 'API key regenerate ki gayi.', 'premium-analytics-dashboard-pro' ) );

		wp_safe_redirect( add_query_arg( array( 'page' => 'pad-settings', 'pad-api-regenerated' => '1' ), admin_url( 'admin.php' ) ) );
		exit;
	}
}
