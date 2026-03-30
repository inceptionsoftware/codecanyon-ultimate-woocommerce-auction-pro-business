<?php
/**
 * License validator for UWA License Server.
 *
 * Checks whether a given purchase code is already activated on the
 * requesting domain without performing a new activation.
 *
 * @package UWA_License_Server
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class ULS_Validator
 */
class ULS_Validator {

	/**
	 * Validate a license for a given domain.
	 *
	 * Steps:
	 *  1. Sanitise the domain.
	 *  2. Look up the license by purchase code.
	 *  3. Reject banned / expired / inactive licenses.
	 *  4. Check whether an active activation exists for this domain.
	 *  5. Return the result.
	 *
	 * @param string $purchase_code  UUID purchase code.
	 * @param string $domain         Raw domain or URL from the client.
	 * @param string $plugin_version Optional plugin version string.
	 * @return array {
	 *     @type bool   $success  Whether validation passed.
	 *     @type string $status   Machine-readable status code.
	 *     @type string $message  Human-readable message.
	 *     @type array  $data     Additional data on success.
	 * }
	 */
	public function validate( $purchase_code, $domain, $plugin_version = '' ) {
		// 1. Sanitise inputs.
		$purchase_code  = sanitize_text_field( trim( $purchase_code ) );
		$domain         = ULS_Security::sanitize_domain( $domain );
		$plugin_version = sanitize_text_field( $plugin_version );

		// Basic format check.
		if ( ! ULS_Security::validate_purchase_code_format( $purchase_code ) ) {
			return $this->build_response(
				false,
				'invalid_purchase_code',
				__( 'The purchase code format is invalid. It must be a valid UUID.', 'uls' )
			);
		}

		if ( empty( $domain ) ) {
			return $this->build_response(
				false,
				'invalid_domain',
				__( 'A valid domain is required for validation.', 'uls' )
			);
		}

		// 2. Look up the license.
		$license = ULS_Database::get_license_by_code( $purchase_code );

		if ( ! $license ) {
			return $this->build_response(
				false,
				'license_not_found',
				__( 'No license was found for this purchase code.', 'uls' )
			);
		}

		// 3. Status checks.
		if ( 'banned' === $license->status ) {
			return $this->build_response(
				false,
				'license_banned',
				__( 'This license has been banned. Please contact support.', 'uls' )
			);
		}

		if ( 'expired' === $license->status ) {
			return $this->build_response(
				false,
				'license_expired',
				__( 'This license has expired. Please renew your purchase.', 'uls' )
			);
		}

		if ( 'inactive' === $license->status ) {
			return $this->build_response(
				false,
				'license_inactive',
				__( 'This license is currently inactive. Please contact support.', 'uls' )
			);
		}

		// 4. Look for an active activation for this domain.
		$activation = ULS_Database::get_active_activation_by_domain( $license->id, $domain );

		if ( ! $activation ) {
			return $this->build_response(
				false,
				'domain_not_activated',
				sprintf(
					/* translators: %s: domain name */
					__( 'The domain %s is not activated for this license. Please activate it first.', 'uls' ),
					$domain
				)
			);
		}

		// 5. Success – build full response data.
		$data = array(
			'license_type'    => $license->license_type,
			'support_until'   => $license->support_until,
			'buyer_name'      => $license->buyer_name,
			'max_domains'     => (int) $license->max_domains,
			'domain'          => $domain,
			'activation_date' => $activation->activation_date,
			'envato_verified' => (bool) $license->envato_verified,
		);

		return $this->build_response(
			true,
			'valid',
			__( 'License is valid and active.', 'uls' ),
			$data
		);
	}

	// -------------------------------------------------------------------------
	// Helpers
	// -------------------------------------------------------------------------

	/**
	 * Build a standard response array.
	 *
	 * @param bool   $success Whether the operation succeeded.
	 * @param string $status  Machine-readable status string.
	 * @param string $message Human-readable message.
	 * @param array  $data    Optional extra data.
	 * @return array
	 */
	private function build_response( $success, $status, $message, $data = array() ) {
		return array(
			'success' => (bool) $success,
			'status'  => $status,
			'message' => $message,
			'data'    => $data,
		);
	}
}
