<?php
/**
 * REST API handler for UWA License Server.
 *
 * Registers and handles three public endpoints and one admin endpoint:
 *   POST  /uls/v1/activate
 *   POST  /uls/v1/deactivate
 *   POST  /uls/v1/validate
 *   GET   /uls/v1/info  (requires WP admin auth)
 *
 * @package UWA_License_Server
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class ULS_API
 */
class ULS_API {

	/**
	 * Singleton instance.
	 *
	 * @var self|null
	 */
	private static $instance = null;

	/**
	 * REST API namespace.
	 *
	 * @var string
	 */
	const NAMESPACE = 'uls/v1';

	/**
	 * Rate limit: requests per window per IP.
	 *
	 * @var int
	 */
	const RATE_LIMIT = 20;

	/**
	 * Rate limit window in seconds.
	 *
	 * @var int
	 */
	const RATE_WINDOW = 3600;

	/**
	 * Private constructor (singleton).
	 */
	private function __construct() {}

	/**
	 * Return (or create) the singleton instance.
	 *
	 * @return self
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	// -------------------------------------------------------------------------
	// Initialisation
	// -------------------------------------------------------------------------

	/**
	 * Register REST routes.
	 */
	public function init() {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	/**
	 * Register all REST API routes.
	 */
	public function register_routes() {
		register_rest_route(
			self::NAMESPACE,
			'/activate',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'handle_activate' ),
				'permission_callback' => '__return_true',
				'args'                => $this->get_activation_args(),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/deactivate',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'handle_deactivate' ),
				'permission_callback' => '__return_true',
				'args'                => $this->get_deactivation_args(),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/validate',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'handle_validate' ),
				'permission_callback' => '__return_true',
				'args'                => $this->get_validation_args(),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/info',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'handle_info' ),
				'permission_callback' => array( $this, 'admin_permission_check' ),
			)
		);
	}

	// -------------------------------------------------------------------------
	// Route handlers
	// -------------------------------------------------------------------------

	/**
	 * Handle POST /uls/v1/activate.
	 *
	 * @param WP_REST_Request $request Incoming REST request.
	 * @return WP_REST_Response
	 */
	public function handle_activate( WP_REST_Request $request ) {
		// Rate limit by client IP.
		$ip = ULS_Security::get_client_ip();
		if ( ! ULS_Security::check_rate_limit( 'activate_' . $ip, self::RATE_LIMIT, self::RATE_WINDOW ) ) {
			return $this->send_response( false, 429, __( 'Rate limit exceeded. Please wait before retrying.', 'uls' ) );
		}

		// Extract and sanitise params.
		$purchase_code  = sanitize_text_field( (string) $request->get_param( 'purchase_code' ) );
		$domain         = sanitize_text_field( (string) $request->get_param( 'domain' ) );
		$site_url       = esc_url_raw( (string) $request->get_param( 'site_url' ) );
		$plugin_version = sanitize_text_field( (string) $request->get_param( 'plugin_version' ) );
		$wp_version     = sanitize_text_field( (string) $request->get_param( 'wp_version' ) );
		$signature      = sanitize_text_field( (string) $request->get_param( 'signature' ) );

		// Verify HMAC signature.
		if ( ! $this->verify_signature( $purchase_code, $domain, $signature ) ) {
			ULS_Database::log_request(
				array(
					'license_id'    => null,
					'action'        => 'activate_sig_fail',
					'domain'        => ULS_Security::sanitize_domain( $domain ),
					'ip_address'    => $ip,
					'request_data'  => wp_json_encode( array( 'purchase_code' => $purchase_code, 'domain' => $domain ) ),
					'response_data' => wp_json_encode( array( 'error' => 'invalid_signature' ) ),
				)
			);
			return $this->send_response( false, 403, __( 'Request signature is invalid.', 'uls' ) );
		}

		// Delegate to activator.
		$activator = new ULS_Activator();
		$result    = $activator->activate( $purchase_code, $domain, $site_url, $plugin_version, $wp_version );

		$http_code = $this->result_to_http_code( $result );
		return $this->send_response( $result['success'], $http_code, $result['message'], $result['data'] );
	}

	/**
	 * Handle POST /uls/v1/deactivate.
	 *
	 * @param WP_REST_Request $request Incoming REST request.
	 * @return WP_REST_Response
	 */
	public function handle_deactivate( WP_REST_Request $request ) {
		$ip = ULS_Security::get_client_ip();
		if ( ! ULS_Security::check_rate_limit( 'deactivate_' . $ip, self::RATE_LIMIT, self::RATE_WINDOW ) ) {
			return $this->send_response( false, 429, __( 'Rate limit exceeded. Please wait before retrying.', 'uls' ) );
		}

		$purchase_code = sanitize_text_field( (string) $request->get_param( 'purchase_code' ) );
		$domain        = sanitize_text_field( (string) $request->get_param( 'domain' ) );
		$signature     = sanitize_text_field( (string) $request->get_param( 'signature' ) );

		if ( ! $this->verify_signature( $purchase_code, $domain, $signature ) ) {
			return $this->send_response( false, 403, __( 'Request signature is invalid.', 'uls' ) );
		}

		$activator = new ULS_Activator();
		$result    = $activator->deactivate( $purchase_code, $domain );

		$http_code = $this->result_to_http_code( $result );
		return $this->send_response( $result['success'], $http_code, $result['message'], $result['data'] );
	}

	/**
	 * Handle POST /uls/v1/validate.
	 *
	 * @param WP_REST_Request $request Incoming REST request.
	 * @return WP_REST_Response
	 */
	public function handle_validate( WP_REST_Request $request ) {
		$ip = ULS_Security::get_client_ip();
		if ( ! ULS_Security::check_rate_limit( 'validate_' . $ip, self::RATE_LIMIT * 3, self::RATE_WINDOW ) ) {
			return $this->send_response( false, 429, __( 'Rate limit exceeded. Please wait before retrying.', 'uls' ) );
		}

		$purchase_code  = sanitize_text_field( (string) $request->get_param( 'purchase_code' ) );
		$domain         = sanitize_text_field( (string) $request->get_param( 'domain' ) );
		$plugin_version = sanitize_text_field( (string) $request->get_param( 'plugin_version' ) );
		$signature      = sanitize_text_field( (string) $request->get_param( 'signature' ) );

		if ( ! $this->verify_signature( $purchase_code, $domain, $signature ) ) {
			return $this->send_response( false, 403, __( 'Request signature is invalid.', 'uls' ) );
		}

		$validator = new ULS_Validator();
		$result    = $validator->validate( $purchase_code, $domain, $plugin_version );

		$http_code = $this->result_to_http_code( $result );
		return $this->send_response( $result['success'], $http_code, $result['message'], $result['data'] );
	}

	/**
	 * Handle GET /uls/v1/info (admin only).
	 *
	 * @param WP_REST_Request $request Incoming REST request.
	 * @return WP_REST_Response
	 */
	public function handle_info( WP_REST_Request $request ) { // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
		$stats = ULS_Database::get_stats();
		return $this->send_response(
			true,
			200,
			__( 'License server info retrieved.', 'uls' ),
			array(
				'version' => ULS_VERSION,
				'stats'   => $stats,
			)
		);
	}

	// -------------------------------------------------------------------------
	// Permission callbacks
	// -------------------------------------------------------------------------

	/**
	 * Require the current user to be an administrator.
	 *
	 * @return bool|WP_Error
	 */
	public function admin_permission_check() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return new WP_Error(
				'rest_forbidden',
				__( 'You must be logged in as an administrator to access this endpoint.', 'uls' ),
				array( 'status' => 403 )
			);
		}
		return true;
	}

	// -------------------------------------------------------------------------
	// Response helpers
	// -------------------------------------------------------------------------

	/**
	 * Build and return a standardised WP_REST_Response.
	 *
	 * @param bool   $success     Whether the operation succeeded.
	 * @param int    $status_code HTTP status code.
	 * @param string $message     Human-readable message.
	 * @param array  $data        Optional payload data.
	 * @return WP_REST_Response
	 */
	public function send_response( $success, $status_code, $message, $data = array() ) {
		$body = array(
			'success'     => (bool) $success,
			'status_code' => (int) $status_code,
			'message'     => (string) $message,
			'data'        => $data,
			'timestamp'   => gmdate( 'c' ),
		);

		return new WP_REST_Response( $body, (int) $status_code );
	}

	/**
	 * Map an activator/validator result array to an HTTP status code.
	 *
	 * @param array $result Result from activator or validator.
	 * @return int HTTP status code.
	 */
	private function result_to_http_code( $result ) {
		if ( $result['success'] ) {
			return 200;
		}
		$status_map = array(
			'invalid_purchase_code'      => 400,
			'invalid_domain'             => 400,
			'database_error'             => 500,
			'license_not_found'          => 403,
			'license_banned'             => 403,
			'license_expired'            => 403,
			'license_inactive'           => 403,
			'domain_not_activated'       => 403,
			'activation_limit_reached'   => 403,
			'activation_not_found'       => 404,
			'envato_verification_failed' => 403,
			'invalid_item'               => 403,
		);
		return isset( $status_map[ $result['status'] ] ) ? $status_map[ $result['status'] ] : 400;
	}

	// -------------------------------------------------------------------------
	// Signature verification
	// -------------------------------------------------------------------------

	/**
	 * Verify the HMAC signature for a purchase_code + domain pair.
	 *
	 * The client generates: HMAC-SHA256(purchase_code + '|' + domain, secret_key)
	 *
	 * @param string $purchase_code Purchase code string.
	 * @param string $domain        Sanitised domain string.
	 * @param string $signature     Hex-encoded HMAC from client.
	 * @return bool True if valid.
	 */
	private function verify_signature( $purchase_code, $domain, $signature ) {
		if ( empty( $signature ) ) {
			return false;
		}
		$clean_domain = ULS_Security::sanitize_domain( $domain );
		$payload      = $purchase_code . '|' . $clean_domain;
		return ULS_Security::verify_request_signature( $payload, $signature );
	}

	// -------------------------------------------------------------------------
	// Route argument definitions
	// -------------------------------------------------------------------------

	/**
	 * REST args for the activate endpoint.
	 *
	 * @return array
	 */
	private function get_activation_args() {
		return array(
			'purchase_code'  => array(
				'required'          => true,
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'description'       => __( 'Envato purchase code (UUID format).', 'uls' ),
			),
			'domain'         => array(
				'required'          => true,
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'description'       => __( 'Domain to activate.', 'uls' ),
			),
			'site_url'       => array(
				'required'          => false,
				'type'              => 'string',
				'sanitize_callback' => 'esc_url_raw',
				'default'           => '',
			),
			'plugin_version' => array(
				'required'          => false,
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'default'           => '',
			),
			'wp_version'     => array(
				'required'          => false,
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'default'           => '',
			),
			'signature'      => array(
				'required'          => true,
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'description'       => __( 'HMAC-SHA256 signature of purchase_code|domain.', 'uls' ),
			),
		);
	}

	/**
	 * REST args for the deactivate endpoint.
	 *
	 * @return array
	 */
	private function get_deactivation_args() {
		return array(
			'purchase_code' => array(
				'required'          => true,
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'domain'        => array(
				'required'          => true,
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'signature'     => array(
				'required'          => true,
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			),
		);
	}

	/**
	 * REST args for the validate endpoint.
	 *
	 * @return array
	 */
	private function get_validation_args() {
		return array(
			'purchase_code'  => array(
				'required'          => true,
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'domain'         => array(
				'required'          => true,
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'plugin_version' => array(
				'required'          => false,
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'default'           => '',
			),
			'signature'      => array(
				'required'          => true,
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			),
		);
	}
}
