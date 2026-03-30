<?php
/**
 * Security utilities for UWA License Server.
 *
 * Handles HMAC signing, rate limiting, IP detection, domain sanitisation,
 * and purchase-code format validation.
 *
 * @package UWA_License_Server
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class ULS_Security
 */
class ULS_Security {

	// -------------------------------------------------------------------------
	// Secret-key management
	// -------------------------------------------------------------------------

	/**
	 * Return the shared HMAC secret, generating and persisting it on first use.
	 *
	 * @return string 64-character random secret key.
	 */
	public static function get_secret_key() {
		$key = get_option( 'uls_secret_key', '' );
		if ( empty( $key ) ) {
			$key = wp_generate_password( 64, true, true );
			update_option( 'uls_secret_key', $key, false );
		}
		return $key;
	}

	/**
	 * Overwrite the stored secret key with a new random value.
	 *
	 * @return string Newly generated key.
	 */
	public static function regenerate_secret_key() {
		$key = wp_generate_password( 64, true, true );
		update_option( 'uls_secret_key', $key, false );
		return $key;
	}

	// -------------------------------------------------------------------------
	// HMAC signatures
	// -------------------------------------------------------------------------

	/**
	 * Verify an incoming HMAC-SHA256 request signature.
	 *
	 * The canonical string is built by JSON-encoding $data (keys sorted) so
	 * both client and server produce the same representation regardless of
	 * key order in the request.
	 *
	 * @param array|string $data      The payload that was signed.
	 * @param string       $signature Hex-encoded HMAC supplied by the client.
	 * @return bool True if the signature is valid.
	 */
	public static function verify_request_signature( $data, $signature ) {
		if ( empty( $signature ) ) {
			return false;
		}
		$expected = self::generate_response_signature( $data );
		return hash_equals( $expected, strtolower( trim( $signature ) ) );
	}

	/**
	 * Generate an HMAC-SHA256 signature for outgoing response data.
	 *
	 * @param array|string $data Data to sign.
	 * @return string Lowercase hex-encoded HMAC.
	 */
	public static function generate_response_signature( $data ) {
		$key     = self::get_secret_key();
		$payload = is_array( $data ) ? self::canonical_json( $data ) : (string) $data;
		return hash_hmac( 'sha256', $payload, $key );
	}

	/**
	 * Build a canonical (sorted-key) JSON string from an array.
	 *
	 * @param array $data Input array.
	 * @return string JSON string.
	 */
	private static function canonical_json( array $data ) {
		ksort( $data );
		return wp_json_encode( $data );
	}

	// -------------------------------------------------------------------------
	// Payload encoding helpers
	// -------------------------------------------------------------------------

	/**
	 * Encode a response payload as JSON then base64.
	 *
	 * @param mixed $data Data to encode.
	 * @return string Base64-encoded JSON string.
	 */
	public static function encrypt_response( $data ) {
		return base64_encode( wp_json_encode( $data ) ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
	}

	/**
	 * Decode a base64 + JSON request payload.
	 *
	 * @param string $data Base64-encoded JSON string from client.
	 * @return mixed|null Decoded data or null on failure.
	 */
	public static function decrypt_request( $data ) {
		$decoded = base64_decode( trim( $data ), true ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
		if ( false === $decoded ) {
			return null;
		}
		return json_decode( $decoded, true );
	}

	// -------------------------------------------------------------------------
	// Client IP detection
	// -------------------------------------------------------------------------

	/**
	 * Determine the real client IP address, respecting common proxy headers.
	 *
	 * @return string IP address string (may be empty if undetectable).
	 */
	public static function get_client_ip() {
		$headers = array(
			'HTTP_CF_CONNECTING_IP',   // Cloudflare
			'HTTP_X_REAL_IP',          // Nginx proxy
			'HTTP_X_FORWARDED_FOR',    // Standard proxy chain
			'HTTP_X_FORWARDED',
			'HTTP_X_CLUSTER_CLIENT_IP',
			'HTTP_FORWARDED_FOR',
			'HTTP_FORWARDED',
			'REMOTE_ADDR',
		);

		foreach ( $headers as $header ) {
			if ( ! empty( $_SERVER[ $header ] ) ) {
				// X-Forwarded-For can be a comma-separated list; take the first.
				$ip = trim( explode( ',', sanitize_text_field( wp_unslash( $_SERVER[ $header ] ) ) )[0] );
				if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE ) ) {
					return $ip;
				}
				// Allow private IPs for local dev environments.
				if ( filter_var( $ip, FILTER_VALIDATE_IP ) ) {
					return $ip;
				}
			}
		}

		return '';
	}

	// -------------------------------------------------------------------------
	// Rate limiting
	// -------------------------------------------------------------------------

	/**
	 * Check whether a request identifier has exceeded the rate limit.
	 *
	 * Uses WordPress transients (auto-expire after $window seconds).
	 *
	 * @param string $identifier Unique identifier (IP, domain, etc.).
	 * @param int    $limit      Maximum allowed requests in the window.
	 * @param int    $window     Time window in seconds (default 3600 = 1 hour).
	 * @return bool True if within limit, false if limit exceeded.
	 */
	public static function check_rate_limit( $identifier, $limit = 20, $window = 3600 ) {
		$transient_key = 'uls_rl_' . md5( $identifier );
		$count         = (int) get_transient( $transient_key );

		if ( $count >= $limit ) {
			return false;
		}

		if ( 0 === $count ) {
			set_transient( $transient_key, 1, $window );
		} else {
			// Increment without resetting the expiry window.
			set_transient( $transient_key, $count + 1, $window );
		}

		return true;
	}

	/**
	 * Reset the rate-limit counter for an identifier.
	 *
	 * @param string $identifier The same identifier used in check_rate_limit().
	 */
	public static function reset_rate_limit( $identifier ) {
		$transient_key = 'uls_rl_' . md5( $identifier );
		delete_transient( $transient_key );
	}

	// -------------------------------------------------------------------------
	// Domain / URL helpers
	// -------------------------------------------------------------------------

	/**
	 * Sanitise a domain string: strip protocol, www., trailing slash, lowercase.
	 *
	 * Examples:
	 *   https://www.example.com/path  →  example.com
	 *   HTTP://EXAMPLE.COM/           →  example.com
	 *
	 * @param string $domain Raw domain or URL from the client.
	 * @return string Normalised domain string.
	 */
	public static function sanitize_domain( $domain ) {
		$domain = strtolower( trim( $domain ) );

		// Remove protocol.
		$domain = preg_replace( '#^https?://#i', '', $domain );

		// Remove www. prefix.
		$domain = preg_replace( '#^www\.#i', '', $domain );

		// Remove path, query string, and fragment.
		$parts  = explode( '/', $domain, 2 );
		$domain = $parts[0];

		// Remove port number.
		$domain = preg_replace( '/:[\d]+$/', '', $domain );

		return rtrim( $domain, '/' );
	}

	// -------------------------------------------------------------------------
	// Purchase code validation
	// -------------------------------------------------------------------------

	/**
	 * Validate that a string matches the Envato purchase-code UUID format.
	 *
	 * Expected format: xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx  (all hex digits)
	 *
	 * @param string $code The purchase code to validate.
	 * @return bool True if format is valid.
	 */
	public static function validate_purchase_code_format( $code ) {
		if ( empty( $code ) ) {
			return false;
		}
		return (bool) preg_match(
			'/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i',
			trim( $code )
		);
	}

	// -------------------------------------------------------------------------
	// HTTPS check
	// -------------------------------------------------------------------------

	/**
	 * Determine whether the original request arrived over HTTPS.
	 *
	 * Handles Cloudflare, load-balancer, and reverse-proxy setups.
	 *
	 * @return bool True if the original request used HTTPS.
	 */
	public static function is_request_from_https() {
		// Direct HTTPS.
		if ( isset( $_SERVER['HTTPS'] ) && 'off' !== strtolower( $_SERVER['HTTPS'] ) ) {
			return true;
		}
		// Standard forwarded-proto header (load balancers, reverse proxies).
		if ( isset( $_SERVER['HTTP_X_FORWARDED_PROTO'] ) && 'https' === strtolower( sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_FORWARDED_PROTO'] ) ) ) ) {
			return true;
		}
		// Cloudflare visitor scheme.
		if ( isset( $_SERVER['HTTP_CF_VISITOR'] ) ) {
			$cf = json_decode( sanitize_text_field( wp_unslash( $_SERVER['HTTP_CF_VISITOR'] ) ), true );
			if ( isset( $cf['scheme'] ) && 'https' === $cf['scheme'] ) {
				return true;
			}
		}
		// Port 443.
		if ( isset( $_SERVER['SERVER_PORT'] ) && '443' === (string) $_SERVER['SERVER_PORT'] ) {
			return true;
		}
		return false;
	}
}
