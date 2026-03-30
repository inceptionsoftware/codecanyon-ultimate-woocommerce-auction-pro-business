<?php
/**
 * Envato Market API integration for UWA License Server.
 *
 * Verifies purchase codes against the Envato v3 buyer API and caches
 * successful responses for 24 hours via WordPress transients.
 *
 * @package UWA_License_Server
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class ULS_Envato
 */
class ULS_Envato {

	/**
	 * Envato Personal Token (Bearer auth).
	 *
	 * @var string
	 */
	private $token;

	/**
	 * Envato API base URL.
	 *
	 * @var string
	 */
	const API_BASE = 'https://api.envato.com';

	/**
	 * Request timeout in seconds.
	 *
	 * @var int
	 */
	const TIMEOUT = 15;

	/**
	 * Transient cache lifetime in seconds (24 hours).
	 *
	 * @var int
	 */
	const CACHE_TTL = 86400;

	/**
	 * Constructor – load token from wp_options.
	 */
	public function __construct() {
		$this->token = get_option( 'uls_envato_token', '' );
	}

	// -------------------------------------------------------------------------
	// Public API
	// -------------------------------------------------------------------------

	/**
	 * Whether the Envato integration is ready to use (token configured).
	 *
	 * @return bool
	 */
	public function is_configured() {
		return ! empty( $this->token );
	}

	/**
	 * Verify a purchase code with the Envato Market API.
	 *
	 * Successful responses are cached in a transient for 24 hours so repeated
	 * activations from the same customer don't hammer the Envato API.
	 *
	 * @param string $purchase_code The UUID purchase code to verify.
	 * @return array {
	 *     @type bool   $success       True on successful verification.
	 *     @type string $buyer_name    Buyer's Envato username.
	 *     @type string $buyer_email   Buyer's email (may be empty if not exposed).
	 *     @type string $license_type  'regular' or 'extended'.
	 *     @type string $support_until ISO date string (Y-m-d) or empty.
	 *     @type string $purchase_date ISO date string (Y-m-d).
	 *     @type string $item_id       Numeric Envato item ID as string.
	 *     @type string $error         Human-readable error message (on failure).
	 * }
	 */
	public function verify_purchase( $purchase_code ) {
		if ( ! $this->is_configured() ) {
			return $this->error_result( __( 'Envato API token is not configured.', 'uls' ) );
		}

		$purchase_code = sanitize_text_field( $purchase_code );

		// Return cached result if available.
		$cache_key = 'uls_envato_' . md5( $purchase_code );
		$cached    = get_transient( $cache_key );
		if ( false !== $cached ) {
			return $cached;
		}

		$url      = self::API_BASE . '/v3/market/buyer/purchase?code=' . rawurlencode( $purchase_code );
		$response = wp_remote_get(
			$url,
			array(
				'timeout'    => self::TIMEOUT,
				'user-agent' => 'UWA-License-Server/' . ULS_VERSION . ' (+https://codecanyon.auctionplugin.net)',
				'headers'    => array(
					'Authorization' => 'Bearer ' . $this->token,
					'Accept'        => 'application/json',
				),
				'sslverify'  => true,
			)
		);

		if ( is_wp_error( $response ) ) {
			return $this->error_result(
				sprintf(
					/* translators: %s: WP_Error message */
					__( 'HTTP request failed: %s', 'uls' ),
					$response->get_error_message()
				)
			);
		}

		$http_code = wp_remote_retrieve_response_code( $response );
		$body      = wp_remote_retrieve_body( $response );
		$json      = json_decode( $body, true );

		// Handle common HTTP error codes.
		if ( 401 === $http_code ) {
			return $this->error_result( __( 'Envato API authentication failed. Check your Personal Token.', 'uls' ) );
		}

		if ( 404 === $http_code ) {
			return $this->error_result( __( 'Purchase code not found in Envato marketplace.', 'uls' ) );
		}

		if ( 429 === $http_code ) {
			return $this->error_result( __( 'Envato API rate limit exceeded. Please try again later.', 'uls' ) );
		}

		if ( $http_code < 200 || $http_code >= 300 ) {
			$api_error = isset( $json['error'] ) ? sanitize_text_field( $json['error'] ) : '';
			return $this->error_result(
				sprintf(
					/* translators: 1: HTTP code, 2: API error message */
					__( 'Envato API returned HTTP %1$d: %2$s', 'uls' ),
					$http_code,
					$api_error ? $api_error : __( 'Unknown error', 'uls' )
				)
			);
		}

		// Validate the JSON structure.
		if ( ! isset( $json['buyer'], $json['item'] ) ) {
			return $this->error_result( __( 'Invalid response received from Envato API.', 'uls' ) );
		}

		// Parse the result into our standard format.
		$result = $this->parse_envato_response( $json );

		// Cache successful result for 24 hours.
		set_transient( $cache_key, $result, self::CACHE_TTL );

		return $result;
	}

	/**
	 * Test the API connection by fetching basic buyer profile info.
	 *
	 * @return array {
	 *     @type bool   $success  True if connection succeeded.
	 *     @type string $username Authenticated buyer's username.
	 *     @type string $error    Error message on failure.
	 * }
	 */
	public function test_connection() {
		if ( ! $this->is_configured() ) {
			return array(
				'success' => false,
				'error'   => __( 'Envato Personal Token is not configured.', 'uls' ),
			);
		}

		$url      = self::API_BASE . '/v1/market/private/user/account.json';
		$response = wp_remote_get(
			$url,
			array(
				'timeout'    => self::TIMEOUT,
				'user-agent' => 'UWA-License-Server/' . ULS_VERSION,
				'headers'    => array(
					'Authorization' => 'Bearer ' . $this->token,
					'Accept'        => 'application/json',
				),
				'sslverify'  => true,
			)
		);

		if ( is_wp_error( $response ) ) {
			return array(
				'success' => false,
				'error'   => $response->get_error_message(),
			);
		}

		$http_code = wp_remote_retrieve_response_code( $response );
		$body      = wp_remote_retrieve_body( $response );
		$json      = json_decode( $body, true );

		if ( 200 !== $http_code ) {
			$msg = isset( $json['error'] ) ? sanitize_text_field( $json['error'] ) : __( 'Unknown error', 'uls' );
			return array(
				'success' => false,
				'error'   => sprintf( 'HTTP %d: %s', $http_code, $msg ),
			);
		}

		$username = isset( $json['account']['login'] ) ? sanitize_text_field( $json['account']['login'] ) : __( 'Unknown', 'uls' );

		return array(
			'success'  => true,
			'username' => $username,
		);
	}

	// -------------------------------------------------------------------------
	// Helpers
	// -------------------------------------------------------------------------

	/**
	 * Parse the Envato API response into our normalised format.
	 *
	 * @param array $json Decoded JSON response from Envato.
	 * @return array Normalised result array.
	 */
	private function parse_envato_response( $json ) {
		$buyer        = $json['buyer'];
		$item         = $json['item'];
		$purchase     = isset( $json['purchase'] ) ? $json['purchase'] : array();
		$supported_at = isset( $json['supported_until'] ) ? $json['supported_until'] : '';
		$sold_at      = isset( $json['sold_at'] ) ? $json['sold_at'] : '';

		// Normalise license type.
		$license_raw  = isset( $json['license'] ) ? strtolower( sanitize_text_field( $json['license'] ) ) : 'regular';
		$license_type = ( false !== strpos( $license_raw, 'extended' ) ) ? 'extended' : 'regular';

		// Normalise dates to Y-m-d.
		$support_until = '';
		if ( ! empty( $supported_at ) ) {
			$ts = strtotime( $supported_at );
			if ( false !== $ts ) {
				$support_until = gmdate( 'Y-m-d', $ts );
			}
		}

		$purchase_date = '';
		if ( ! empty( $sold_at ) ) {
			$ts = strtotime( $sold_at );
			if ( false !== $ts ) {
				$purchase_date = gmdate( 'Y-m-d', $ts );
			}
		}

		return array(
			'success'       => true,
			'buyer_name'    => isset( $buyer['username'] ) ? sanitize_text_field( $buyer['username'] ) : '',
			'buyer_email'   => isset( $buyer['email'] ) ? sanitize_email( $buyer['email'] ) : '',
			'license_type'  => $license_type,
			'support_until' => $support_until,
			'purchase_date' => $purchase_date,
			'item_id'       => isset( $item['id'] ) ? (string) absint( $item['id'] ) : '',
			'item_name'     => isset( $item['name'] ) ? sanitize_text_field( $item['name'] ) : '',
			'error'         => '',
		);
	}

	/**
	 * Build a standard error result array.
	 *
	 * @param string $message Human-readable error message.
	 * @return array
	 */
	private function error_result( $message ) {
		return array(
			'success'       => false,
			'buyer_name'    => '',
			'buyer_email'   => '',
			'license_type'  => '',
			'support_until' => '',
			'purchase_date' => '',
			'item_id'       => '',
			'item_name'     => '',
			'error'         => $message,
		);
	}
}
