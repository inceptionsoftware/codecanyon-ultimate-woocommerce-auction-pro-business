<?php
/**
 * UWA License Client
 * Handles license activation, deactivation, and validation
 * communicating with the license server at codecanyon.auctionplugin.net
 *
 * @package Ultimate_WooCommerce_Auction_Pro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'UWA_License' ) ) {

	/**
	 * UWA_License class.
	 *
	 * Manages plugin license lifecycle: activation, deactivation, and periodic
	 * validation against the remote license server.
	 */
	class UWA_License {

		const LICENSE_SERVER    = 'https://codecanyon.auctionplugin.net/wp-json/uls/v1';
		const OPTION_KEY        = 'uwa_license_data';
		const CACHE_TRANSIENT   = 'uwa_license_status_cache';
		const CACHE_EXPIRY      = DAY_IN_SECONDS;        // 24 hours.
		const GRACE_PERIOD      = 259200;                // 3 * DAY_IN_SECONDS (3 days).
		const SECRET_KEY_OPTION = 'uwa_license_secret';

		/**
		 * Singleton instance.
		 *
		 * @var UWA_License|null
		 */
		private static $instance = null;

		/**
		 * In-memory cache of decrypted license data.
		 *
		 * @var array|null
		 */
		private $license_data = null;

		// -------------------------------------------------------------------------
		// Singleton & bootstrap
		// -------------------------------------------------------------------------

		/**
		 * Returns the singleton instance of this class.
		 *
		 * @return UWA_License
		 */
		public static function instance() {
			if ( null === self::$instance ) {
				self::$instance = new self();
			}
			return self::$instance;
		}

		/**
		 * Constructor: load persisted license data and register hooks.
		 */
		public function __construct() {
			// Pre-load license data into memory on construction.
			$this->license_data = $this->load_license_data();

			// Register daily WP-Cron hook.
			add_action( 'uwa_daily_license_check', array( $this, 'daily_validation_cron' ) );

			// Schedule daily cron event if not already scheduled.
			if ( ! wp_next_scheduled( 'uwa_daily_license_check' ) ) {
				wp_schedule_event( time(), 'daily', 'uwa_daily_license_check' );
			}

			// Show admin notices when license is invalid.
			add_action( 'admin_notices', array( $this, 'admin_notice_invalid_license' ) );
		}

		// -------------------------------------------------------------------------
		// Secret key
		// -------------------------------------------------------------------------

		/**
		 * Returns the site-specific secret key, auto-generating it on first call.
		 *
		 * @return string 32-character alphanumeric secret.
		 */
		public function get_secret_key() {
			$secret = get_option( self::SECRET_KEY_OPTION, '' );

			if ( empty( $secret ) ) {
				$secret = wp_generate_password( 32, false );
				update_option( self::SECRET_KEY_OPTION, $secret, false );
			}

			return $secret;
		}

		// -------------------------------------------------------------------------
		// Activation
		// -------------------------------------------------------------------------

		/**
		 * Activates a license purchase code for the current domain.
		 *
		 * @param string $purchase_code The Envato/CodeCanyon purchase code (UUID format).
		 * @return array {
		 *     @type bool   $success Whether activation succeeded.
		 *     @type string $message Human-readable result message.
		 *     @type array  $data    Full response data on success, empty array on failure.
		 * }
		 */
		public function activate( $purchase_code ) {
			// Validate purchase code format (UUID v4).
			$purchase_code = sanitize_text_field( $purchase_code );

			if ( ! $this->is_valid_purchase_code_format( $purchase_code ) ) {
				return array(
					'success' => false,
					'message' => __( 'Invalid purchase code format. Please enter a valid CodeCanyon purchase code (UUID format: xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx).', 'woo_ua' ),
					'data'    => array(),
				);
			}

			$domain = $this->get_current_domain();

			$body = array(
				'purchase_code'  => $purchase_code,
				'domain'         => $domain,
				'site_url'       => esc_url_raw( home_url() ),
				'plugin_version' => defined( 'UW_AUCTION_PRO_VERSION' ) ? UW_AUCTION_PRO_VERSION : '0.0.0',
				'wp_version'     => get_bloginfo( 'version' ),
			);

			$response = $this->make_request( '/activate', $body );

			if ( ! $response['success'] ) {
				return array(
					'success' => false,
					'message' => $response['message'],
					'data'    => array(),
				);
			}

			$data = $response['data'];

			// Persist license data.
			$license_data = array(
				'purchase_code'   => $purchase_code,
				'domain'          => $domain,
				'status'          => isset( $data['status'] ) ? sanitize_text_field( $data['status'] ) : 'active',
				'license_type'    => isset( $data['license_type'] ) ? sanitize_text_field( $data['license_type'] ) : '',
				'support_until'   => isset( $data['support_until'] ) ? sanitize_text_field( $data['support_until'] ) : '',
				'buyer'           => isset( $data['buyer'] ) ? sanitize_text_field( $data['buyer'] ) : '',
				'activated_at'    => time(),
				'last_validated'  => time(),
			);

			$this->store_license_data( $license_data );

			// Cache the valid status.
			$cache_data = array(
				'valid'   => true,
				'status'  => 'active',
				'message' => __( 'License is active.', 'woo_ua' ),
				'data'    => $license_data,
			);
			set_transient( self::CACHE_TRANSIENT, $cache_data, self::CACHE_EXPIRY );

			return array(
				'success' => true,
				'message' => __( 'License activated successfully. Thank you for your purchase!', 'woo_ua' ),
				'data'    => $license_data,
			);
		}

		// -------------------------------------------------------------------------
		// Deactivation
		// -------------------------------------------------------------------------

		/**
		 * Deactivates the license for the current domain.
		 *
		 * Even when the remote call fails, the local license data is cleared so the
		 * administrator can re-activate on a different domain.
		 *
		 * @return array {
		 *     @type bool   $success Whether the remote deactivation also succeeded.
		 *     @type string $message Human-readable result message.
		 * }
		 */
		public function deactivate() {
			$stored = $this->get_license_data();

			if ( empty( $stored['purchase_code'] ) ) {
				return array(
					'success' => false,
					'message' => __( 'No active license found to deactivate.', 'woo_ua' ),
				);
			}

			$body = array(
				'purchase_code' => $stored['purchase_code'],
				'domain'        => $this->get_current_domain(),
			);

			$response = $this->make_request( '/deactivate', $body );

			// Always clear local data regardless of remote result.
			delete_option( self::OPTION_KEY );
			delete_transient( self::CACHE_TRANSIENT );
			$this->license_data = null;

			if ( $response['success'] ) {
				return array(
					'success' => true,
					'message' => __( 'License deactivated successfully. You can now activate on a different domain.', 'woo_ua' ),
				);
			}

			// Remote call failed, but local data has been cleared.
			return array(
				'success' => true,
				'message' => __( 'License has been removed locally. Note: remote deactivation could not be completed — you may need to contact support if you cannot re-activate elsewhere.', 'woo_ua' ),
			);
		}

		// -------------------------------------------------------------------------
		// Validation
		// -------------------------------------------------------------------------

		/**
		 * Validates the license against the remote server.
		 *
		 * Uses a transient cache to avoid hammering the server on every page load.
		 * A grace period allows the plugin to remain functional when the license
		 * server is temporarily unreachable.
		 *
		 * @param bool $force When true, bypasses the transient cache.
		 * @return array {
		 *     @type bool   $valid   Whether the license is currently valid.
		 *     @type string $status  Machine-readable status string.
		 *     @type string $message Human-readable status message.
		 *     @type array  $data    Full license data array.
		 * }
		 */
		public function validate( $force = false ) {
			// Return cached result unless forced.
			if ( ! $force ) {
				$cached = get_transient( self::CACHE_TRANSIENT );
				if ( false !== $cached && is_array( $cached ) ) {
					return $cached;
				}
			}

			$stored = $this->get_license_data();

			if ( empty( $stored['purchase_code'] ) ) {
				$result = array(
					'valid'   => false,
					'status'  => 'not_activated',
					'message' => __( 'License is not activated.', 'woo_ua' ),
					'data'    => array(),
				);
				set_transient( self::CACHE_TRANSIENT, $result, self::CACHE_EXPIRY );
				return $result;
			}

			$body = array(
				'purchase_code'  => $stored['purchase_code'],
				'domain'         => $this->get_current_domain(),
				'plugin_version' => defined( 'UW_AUCTION_PRO_VERSION' ) ? UW_AUCTION_PRO_VERSION : '0.0.0',
			);

			$response = $this->make_request( '/validate', $body );

			if ( ! $response['success'] ) {
				// Server unreachable — apply grace period logic.
				$last_validated = isset( $stored['last_validated'] ) ? (int) $stored['last_validated'] : 0;
				$within_grace   = ( time() - $last_validated ) <= self::GRACE_PERIOD;
				$was_active     = isset( $stored['status'] ) && 'active' === $stored['status'];

				if ( $was_active && $within_grace ) {
					$result = array(
						'valid'   => true,
						'status'  => 'active_grace',
						'message' => __( 'License server is temporarily unreachable. Operating in grace period.', 'woo_ua' ),
						'data'    => $stored,
					);
				} else {
					$result = array(
						'valid'   => false,
						'status'  => 'server_error',
						'message' => $response['message'],
						'data'    => $stored,
					);
				}

				// Short cache to retry sooner when server is down.
				set_transient( self::CACHE_TRANSIENT, $result, HOUR_IN_SECONDS );
				return $result;
			}

			$data = $response['data'];

			$is_valid = isset( $data['valid'] ) ? (bool) $data['valid'] : false;
			$status   = isset( $data['status'] ) ? sanitize_text_field( $data['status'] ) : ( $is_valid ? 'active' : 'invalid' );
			$message  = isset( $data['message'] ) ? sanitize_text_field( $data['message'] ) : ( $is_valid ? __( 'License is active.', 'woo_ua' ) : __( 'License is invalid.', 'woo_ua' ) );

			// Update persisted last_validated timestamp and status.
			$updated_data = array_merge(
				$stored,
				array(
					'status'         => $status,
					'last_validated' => time(),
				)
			);

			if ( isset( $data['license_type'] ) ) {
				$updated_data['license_type'] = sanitize_text_field( $data['license_type'] );
			}
			if ( isset( $data['support_until'] ) ) {
				$updated_data['support_until'] = sanitize_text_field( $data['support_until'] );
			}
			if ( isset( $data['buyer'] ) ) {
				$updated_data['buyer'] = sanitize_text_field( $data['buyer'] );
			}

			$this->store_license_data( $updated_data );

			$result = array(
				'valid'   => $is_valid,
				'status'  => $status,
				'message' => $message,
				'data'    => $updated_data,
			);

			set_transient( self::CACHE_TRANSIENT, $result, self::CACHE_EXPIRY );

			return $result;
		}

		// -------------------------------------------------------------------------
		// Data accessors
		// -------------------------------------------------------------------------

		/**
		 * Returns the decrypted license data array, or null if not set.
		 *
		 * @return array|null
		 */
		public function get_license_data() {
			if ( null !== $this->license_data ) {
				return $this->license_data;
			}
			$this->license_data = $this->load_license_data();
			return $this->license_data;
		}

		/**
		 * Returns the masked purchase code (first 8 characters + "...").
		 *
		 * @return string Masked purchase code, or empty string if not set.
		 */
		public function get_purchase_code() {
			$data = $this->get_license_data();
			if ( empty( $data['purchase_code'] ) ) {
				return '';
			}
			return substr( $data['purchase_code'], 0, 8 ) . '...';
		}

		/**
		 * Fast check of local activation status without making a remote API call.
		 *
		 * @return bool True if a license data record exists and its status is 'active' or 'active_grace'.
		 */
		public function is_active() {
			$data = $this->get_license_data();
			if ( empty( $data['status'] ) ) {
				return false;
			}
			return in_array( $data['status'], array( 'active', 'active_grace' ), true );
		}

		/**
		 * Returns a human-readable, HTML-formatted license status label.
		 *
		 * @return string HTML span element with inline colour styling.
		 */
		public function get_status_label() {
			$data = $this->get_license_data();

			if ( empty( $data ) || empty( $data['status'] ) ) {
				return '<span style="color:#cc0000;font-weight:bold;">' . esc_html__( 'Not Activated', 'woo_ua' ) . '</span>';
			}

			$status = $data['status'];

			$labels = array(
				'active'        => array(
					'label' => __( 'Active', 'woo_ua' ),
					'color' => '#00a651',
				),
				'active_grace'  => array(
					'label' => __( 'Active (Grace Period)', 'woo_ua' ),
					'color' => '#f0ad00',
				),
				'expired'       => array(
					'label' => __( 'Expired', 'woo_ua' ),
					'color' => '#cc0000',
				),
				'invalid'       => array(
					'label' => __( 'Invalid', 'woo_ua' ),
					'color' => '#cc0000',
				),
				'suspended'     => array(
					'label' => __( 'Suspended', 'woo_ua' ),
					'color' => '#cc0000',
				),
				'not_activated' => array(
					'label' => __( 'Not Activated', 'woo_ua' ),
					'color' => '#cc0000',
				),
				'server_error'  => array(
					'label' => __( 'Server Unreachable', 'woo_ua' ),
					'color' => '#f0ad00',
				),
			);

			if ( isset( $labels[ $status ] ) ) {
				$info = $labels[ $status ];
			} else {
				$info = array(
					'label' => ucfirst( $status ),
					'color' => '#555555',
				);
			}

			return '<span style="color:' . esc_attr( $info['color'] ) . ';font-weight:bold;">'
				. esc_html( $info['label'] )
				. '</span>';
		}

		// -------------------------------------------------------------------------
		// Storage helpers
		// -------------------------------------------------------------------------

		/**
		 * Encrypts and stores license data in wp_options.
		 *
		 * @param array $data License data to persist.
		 */
		public function store_license_data( $data ) {
			$encrypted = $this->encrypt_data( $data );
			update_option( self::OPTION_KEY, $encrypted, false );
			$this->license_data = $data;
		}

		/**
		 * Loads and decrypts license data from wp_options.
		 *
		 * @return array|null Decrypted data array, or null if not set or decryption fails.
		 */
		private function load_license_data() {
			$encrypted = get_option( self::OPTION_KEY, '' );
			if ( empty( $encrypted ) ) {
				return null;
			}
			$data = $this->decrypt_data( $encrypted );
			if ( ! is_array( $data ) ) {
				return null;
			}
			return $data;
		}

		/**
		 * Encrypts an array to a storable string.
		 *
		 * Uses a simple XOR cipher keyed on the secret key, then base64-encodes the
		 * result. This provides obfuscation rather than strong cryptographic security;
		 * the primary goal is to prevent casual inspection of option values in the DB.
		 *
		 * @param array $data Data to encrypt.
		 * @return string Encrypted, base64-encoded string.
		 */
		public function encrypt_data( $data ) {
			$json       = wp_json_encode( $data );
			$secret     = $this->get_secret_key();
			$secret_len = strlen( $secret );
			$xored      = '';

			for ( $i = 0, $len = strlen( $json ); $i < $len; $i++ ) {
				$xored .= chr( ord( $json[ $i ] ) ^ ord( $secret[ $i % $secret_len ] ) );
			}

			return base64_encode( $xored ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
		}

		/**
		 * Decrypts a string previously produced by encrypt_data().
		 *
		 * @param string $encrypted Base64-encoded, XOR-encrypted string.
		 * @return array|null Decrypted data array, or null on failure.
		 */
		public function decrypt_data( $encrypted ) {
			$xored      = base64_decode( $encrypted ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
			if ( false === $xored ) {
				return null;
			}

			$secret     = $this->get_secret_key();
			$secret_len = strlen( $secret );
			$json       = '';

			for ( $i = 0, $len = strlen( $xored ); $i < $len; $i++ ) {
				$json .= chr( ord( $xored[ $i ] ) ^ ord( $secret[ $i % $secret_len ] ) );
			}

			$data = json_decode( $json, true );
			if ( JSON_ERROR_NONE !== json_last_error() ) {
				return null;
			}
			return $data;
		}

		// -------------------------------------------------------------------------
		// Cron & admin notices
		// -------------------------------------------------------------------------

		/**
		 * WP-Cron callback: performs a forced remote validation once per day.
		 *
		 * Sends an admin email if the license status has changed since the last run.
		 */
		public function daily_validation_cron() {
			$before = $this->get_license_data();
			$old_status = isset( $before['status'] ) ? $before['status'] : 'not_activated';

			$result = $this->validate( true );

			$new_status = isset( $result['status'] ) ? $result['status'] : 'not_activated';

			// Notify admin on status change.
			if ( $old_status !== $new_status ) {
				$admin_email = get_option( 'admin_email' );
				$site_name   = get_option( 'blogname' );

				$subject = sprintf(
					/* translators: 1: site name */
					__( '[%1$s] Ultimate WooCommerce Auction Pro — License Status Changed', 'woo_ua' ),
					$site_name
				);

				$message = sprintf(
					/* translators: 1: old status, 2: new status, 3: message */
					__( "Your license status has changed.\n\nPrevious status: %1\$s\nNew status: %2\$s\n\nMessage: %3\$s\n\nPlease log in to your site admin panel to review your license.", 'woo_ua' ),
					$old_status,
					$new_status,
					$result['message']
				);

				wp_mail( $admin_email, $subject, $message );
			}
		}

		/**
		 * Hooked to admin_notices: displays a dismissible error notice when the
		 * license is not activated or is invalid.
		 */
		public function admin_notice_invalid_license() {
			// Only show to users who can manage options.
			if ( ! current_user_can( 'manage_options' ) ) {
				return;
			}

			// Avoid showing notice on the license settings page itself.
			$screen = get_current_screen();
			if ( $screen && false !== strpos( $screen->id, 'uwa_general_setting' ) ) {
				// Check query string tab parameter.
				// phpcs:ignore WordPress.Security.NonceVerification.Recommended
				$active_tab = isset( $_GET['setting_section'] ) ? sanitize_text_field( wp_unslash( $_GET['setting_section'] ) ) : '';
				if ( 'uwa_license_setting' === $active_tab ) {
					return;
				}
			}

			$cached = get_transient( self::CACHE_TRANSIENT );

			// Build status from cache or local data.
			if ( false !== $cached && is_array( $cached ) ) {
				$valid  = ! empty( $cached['valid'] );
				$status = isset( $cached['status'] ) ? $cached['status'] : 'unknown';
			} else {
				$data   = $this->get_license_data();
				$valid  = ( ! empty( $data['status'] ) && 'active' === $data['status'] );
				$status = ! empty( $data['status'] ) ? $data['status'] : 'not_activated';
			}

			if ( $valid ) {
				return;
			}

			// Build appropriate notice message.
			$settings_url = admin_url( 'admin.php?page=uwa_general_setting&setting_section=uwa_license_setting' );

			if ( 'not_activated' === $status ) {
				$notice = sprintf(
					/* translators: 1: opening anchor tag, 2: closing anchor tag */
					__( '<strong>Ultimate WooCommerce Auction Pro</strong> is not activated. Please %1$senter your license key%2$s to unlock all features and receive updates.', 'woo_ua' ),
					'<a href="' . esc_url( $settings_url ) . '">',
					'</a>'
				);
				$class = 'notice notice-warning';
			} elseif ( 'active_grace' === $status ) {
				$notice = sprintf(
					/* translators: 1: opening anchor tag, 2: closing anchor tag */
					__( '<strong>Ultimate WooCommerce Auction Pro</strong> — License server is temporarily unreachable. The plugin is operating in a grace period. %1$sView license settings%2$s.', 'woo_ua' ),
					'<a href="' . esc_url( $settings_url ) . '">',
					'</a>'
				);
				$class = 'notice notice-warning';
			} else {
				$notice = sprintf(
					/* translators: 1: opening anchor tag, 2: closing anchor tag */
					__( '<strong>Ultimate WooCommerce Auction Pro</strong> — License is invalid or expired. %1$sPlease activate your license%2$s to continue receiving updates and support.', 'woo_ua' ),
					'<a href="' . esc_url( $settings_url ) . '">',
					'</a>'
				);
				$class = 'notice notice-error';
			}

			printf(
				'<div class="%1$s is-dismissible"><p>%2$s</p></div>',
				esc_attr( $class ),
				wp_kses(
					$notice,
					array(
						'a'      => array( 'href' => array() ),
						'strong' => array(),
					)
				)
			);
		}

		// -------------------------------------------------------------------------
		// Domain helper
		// -------------------------------------------------------------------------

		/**
		 * Returns the clean domain name for the current site.
		 *
		 * Strips protocol, "www." prefix, and trailing slashes. Returns lowercase.
		 *
		 * @return string Clean domain, e.g. "example.com".
		 */
		public function get_current_domain() {
			$site_url = home_url();

			// Remove protocol.
			$domain = preg_replace( '#^https?://#i', '', $site_url );

			// Remove path/query after domain.
			$domain = preg_replace( '#[/?#].*$#', '', $domain );

			// Remove leading www.
			$domain = preg_replace( '#^www\.#i', '', $domain );

			// Lowercase.
			$domain = strtolower( trim( $domain ) );

			return $domain;
		}

		// -------------------------------------------------------------------------
		// HTTP request helpers
		// -------------------------------------------------------------------------

		/**
		 * Performs a signed POST request to the license server.
		 *
		 * The signature is a HMAC-SHA256 over "purchase_code|domain" using the
		 * site's secret key. This allows the server to verify request authenticity.
		 *
		 * @param string $endpoint Relative endpoint path (e.g. '/activate').
		 * @param array  $body     Request body fields (not yet signed).
		 * @return array {
		 *     @type bool   $success Whether the HTTP request and server response indicated success.
		 *     @type string $message Human-readable result.
		 *     @type array  $data    Decoded response body on success, empty array on failure.
		 * }
		 */
		private function make_request( $endpoint, $body ) {
			$purchase_code = isset( $body['purchase_code'] ) ? $body['purchase_code'] : '';
			$domain        = isset( $body['domain'] ) ? $body['domain'] : $this->get_current_domain();
			$secret        = $this->get_secret_key();

			// Generate HMAC signature.
			$signature       = hash_hmac( 'sha256', $purchase_code . $domain, $secret );
			$body['sig']     = $signature;
			$body['site_url'] = isset( $body['site_url'] ) ? $body['site_url'] : esc_url_raw( home_url() );

			$url = self::LICENSE_SERVER . $endpoint;

			$args = array(
				'method'      => 'POST',
				'timeout'     => 15,
				'redirection' => 5,
				'httpversion' => '1.1',
				'sslverify'   => true,
				'headers'     => array(
					'Content-Type' => 'application/json',
					'User-Agent'   => 'UWA-License-Client/' . ( defined( 'UW_AUCTION_PRO_VERSION' ) ? UW_AUCTION_PRO_VERSION : '0.0.0' ),
				),
				'body'        => wp_json_encode( $body ),
			);

			$response = wp_remote_post( $url, $args );

			return $this->format_response( $response );
		}

		/**
		 * Parses a wp_remote_post() return value into a normalised result array.
		 *
		 * @param WP_Error|array $response Raw wp_remote_post() response.
		 * @return array {
		 *     @type bool   $success Whether the HTTP request and server response indicated success.
		 *     @type string $message Human-readable result.
		 *     @type array  $data    Decoded response body on success, empty array on failure.
		 * }
		 */
		private function format_response( $response ) {
			if ( is_wp_error( $response ) ) {
				return array(
					'success' => false,
					'message' => sprintf(
						/* translators: %s: error message */
						__( 'Could not connect to the license server: %s', 'woo_ua' ),
						$response->get_error_message()
					),
					'data'    => array(),
				);
			}

			$http_code = wp_remote_retrieve_response_code( $response );
			$body_raw  = wp_remote_retrieve_body( $response );
			$body      = json_decode( $body_raw, true );

			if ( JSON_ERROR_NONE !== json_last_error() ) {
				return array(
					'success' => false,
					'message' => __( 'Received an unexpected response from the license server. Please try again later.', 'woo_ua' ),
					'data'    => array(),
				);
			}

			if ( $http_code < 200 || $http_code >= 300 ) {
				$server_message = isset( $body['message'] ) ? sanitize_text_field( $body['message'] ) : '';
				return array(
					'success' => false,
					'message' => $server_message
						? $server_message
						: sprintf(
							/* translators: %d: HTTP status code */
							__( 'License server returned an error (HTTP %d). Please try again later.', 'woo_ua' ),
							(int) $http_code
						),
					'data'    => array(),
				);
			}

			// A successful HTTP response must include a "success" or "valid" key.
			$is_success = ( isset( $body['success'] ) && $body['success'] )
				|| ( isset( $body['valid'] ) && $body['valid'] )
				|| ( 200 === (int) $http_code && ! isset( $body['success'] ) && ! isset( $body['error'] ) );

			if ( ! $is_success ) {
				$server_message = isset( $body['message'] ) ? sanitize_text_field( $body['message'] ) : __( 'License server rejected the request.', 'woo_ua' );
				return array(
					'success' => false,
					'message' => $server_message,
					'data'    => array(),
				);
			}

			return array(
				'success' => true,
				'message' => isset( $body['message'] ) ? sanitize_text_field( $body['message'] ) : __( 'Request successful.', 'woo_ua' ),
				'data'    => is_array( $body ) ? $body : array(),
			);
		}

		// -------------------------------------------------------------------------
		// Utility
		// -------------------------------------------------------------------------

		/**
		 * Checks whether a string matches the UUID v4 format used by Envato.
		 *
		 * @param string $code Purchase code to validate.
		 * @return bool True if the format is valid.
		 */
		private function is_valid_purchase_code_format( $code ) {
			return (bool) preg_match(
				'/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i',
				$code
			);
		}
	}

} // end if class_exists
