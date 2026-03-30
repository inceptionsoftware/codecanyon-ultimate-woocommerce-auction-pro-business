<?php
/**
 * License activator / deactivator for UWA License Server.
 *
 * Handles the full lifecycle of activating and deactivating a license on a
 * specific domain, including optional auto-creation via Envato API.
 *
 * @package UWA_License_Server
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class ULS_Activator
 */
class ULS_Activator {

	// -------------------------------------------------------------------------
	// Activation
	// -------------------------------------------------------------------------

	/**
	 * Activate a license for a given domain.
	 *
	 * @param string $purchase_code  UUID purchase code.
	 * @param string $domain         Raw domain or URL from the client.
	 * @param string $site_url       Full site URL (optional).
	 * @param string $plugin_version Plugin version string (optional).
	 * @param string $wp_version     WordPress version string (optional).
	 * @return array {
	 *     @type bool   $success  Whether activation succeeded.
	 *     @type string $status   Machine-readable status code.
	 *     @type string $message  Human-readable message.
	 *     @type array  $data     License / activation details on success.
	 * }
	 */
	public function activate( $purchase_code, $domain, $site_url = '', $plugin_version = '', $wp_version = '' ) {
		// 1. Sanitise all inputs.
		$purchase_code  = sanitize_text_field( trim( $purchase_code ) );
		$domain         = ULS_Security::sanitize_domain( $domain );
		$site_url       = esc_url_raw( $site_url );
		$plugin_version = sanitize_text_field( $plugin_version );
		$wp_version     = sanitize_text_field( $wp_version );
		$ip_address     = ULS_Security::get_client_ip();

		// 2. Validate purchase code format.
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
				__( 'A valid domain is required for activation.', 'uls' )
			);
		}

		// 3. Look up the license in the database.
		$license = ULS_Database::get_license_by_code( $purchase_code );

		// 4. If not found, attempt Envato auto-creation.
		if ( ! $license ) {
			$envato = new ULS_Envato();

			if ( $envato->is_configured() ) {
				$envato_result = $envato->verify_purchase( $purchase_code );

				if ( ! $envato_result['success'] ) {
					$this->log( null, 'activate_failed', $domain, $ip_address, compact( 'purchase_code', 'domain' ), $envato_result );
					return $this->build_response(
						false,
						'envato_verification_failed',
						$envato_result['error']
					);
				}

				// Verify the item ID matches (if configured).
				$expected_item = get_option( 'uls_item_id', defined( 'ULS_ITEM_ID' ) ? ULS_ITEM_ID : '' );
				if ( ! empty( $expected_item ) && $envato_result['item_id'] !== (string) $expected_item ) {
					$this->log( null, 'activate_failed', $domain, $ip_address, compact( 'purchase_code', 'domain' ), array( 'error' => 'item_id_mismatch' ) );
					return $this->build_response(
						false,
						'invalid_item',
						__( 'This purchase code is not for the correct product.', 'uls' )
					);
				}

				// Determine max_domains based on license type.
				$max_domains = ( 'extended' === $envato_result['license_type'] ) ? 999 : 1;

				$license_id = ULS_Database::create_license(
					array(
						'purchase_code'   => $purchase_code,
						'buyer_name'      => $envato_result['buyer_name'],
						'buyer_email'     => $envato_result['buyer_email'],
						'product_id'      => $envato_result['item_id'],
						'license_type'    => $envato_result['license_type'],
						'max_domains'     => $max_domains,
						'status'          => 'active',
						'support_until'   => $envato_result['support_until'] ?: null,
						'purchase_date'   => $envato_result['purchase_date'] ?: null,
						'envato_verified' => 1,
					)
				);

				if ( ! $license_id ) {
					return $this->build_response(
						false,
						'database_error',
						__( 'Failed to create license record. Please try again.', 'uls' )
					);
				}

				$license = ULS_Database::get_license_by_id( $license_id );
			} else {
				// Envato not configured – cannot auto-create.
				$this->log( null, 'activate_failed', $domain, $ip_address, compact( 'purchase_code', 'domain' ), array( 'error' => 'license_not_found' ) );
				return $this->build_response(
					false,
					'license_not_found',
					__( 'No license was found for this purchase code.', 'uls' )
				);
			}
		}

		// 5. Validate license status.
		if ( 'banned' === $license->status ) {
			$this->log( $license->id, 'activate_failed', $domain, $ip_address, compact( 'purchase_code', 'domain' ), array( 'error' => 'license_banned' ) );
			return $this->build_response(
				false,
				'license_banned',
				__( 'This license has been banned. Please contact support.', 'uls' )
			);
		}

		if ( 'expired' === $license->status ) {
			$this->log( $license->id, 'activate_failed', $domain, $ip_address, compact( 'purchase_code', 'domain' ), array( 'error' => 'license_expired' ) );
			return $this->build_response(
				false,
				'license_expired',
				__( 'This license has expired. Please renew your purchase.', 'uls' )
			);
		}

		if ( 'inactive' === $license->status ) {
			$this->log( $license->id, 'activate_failed', $domain, $ip_address, compact( 'purchase_code', 'domain' ), array( 'error' => 'license_inactive' ) );
			return $this->build_response(
				false,
				'license_inactive',
				__( 'This license is currently inactive. Please contact support.', 'uls' )
			);
		}

		// 6. Check for existing active activation on this domain.
		$existing = ULS_Database::get_active_activation_by_domain( $license->id, $domain );

		if ( $existing ) {
			// Already active – treat as success and update metadata.
			ULS_Database::update_activation(
				$existing->id,
				array(
					'ip_address'    => $ip_address,
					'wp_version'    => $wp_version,
					'plugin_version' => $plugin_version,
					'site_url'      => $site_url,
				)
			);
			ULS_Database::update_license( $license->id, array() ); // Touch updated_at.

			$result_data = $this->build_license_data( $license, $domain );
			$this->log( $license->id, 'activate_existing', $domain, $ip_address, compact( 'purchase_code', 'domain' ), $result_data );

			return $this->build_response(
				true,
				'already_active',
				__( 'License is already activated for this domain.', 'uls' ),
				$result_data
			);
		}

		// 7. Check activation slot count.
		$active_count = ULS_Database::get_active_activations( $license->id );
		$max_domains  = (int) $license->max_domains;

		if ( $active_count >= $max_domains ) {
			$error_data = array(
				'active_count' => $active_count,
				'max_domains'  => $max_domains,
			);
			$this->log( $license->id, 'activate_failed', $domain, $ip_address, compact( 'purchase_code', 'domain' ), array_merge( array( 'error' => 'activation_limit_reached' ), $error_data ) );
			return $this->build_response(
				false,
				'activation_limit_reached',
				sprintf(
					/* translators: 1: number of active sites, 2: maximum allowed */
					__( 'Activation limit reached (%1$d of %2$d domains used). Deactivate a domain to activate a new one.', 'uls' ),
					$active_count,
					$max_domains
				),
				$error_data
			);
		}

		// 8. Create the activation record.
		$activation_id = ULS_Database::create_activation(
			array(
				'license_id'     => $license->id,
				'domain'         => $domain,
				'site_url'       => $site_url,
				'ip_address'     => $ip_address,
				'wp_version'     => $wp_version,
				'plugin_version' => $plugin_version,
				'status'         => 'active',
			)
		);

		if ( ! $activation_id ) {
			return $this->build_response(
				false,
				'database_error',
				__( 'Failed to save activation. Please try again.', 'uls' )
			);
		}

		// 9. Update the license updated_at timestamp.
		ULS_Database::update_license( $license->id, array() );

		// 10. Log and return success.
		$result_data = $this->build_license_data( $license, $domain );
		$this->log( $license->id, 'activate', $domain, $ip_address, compact( 'purchase_code', 'domain', 'plugin_version', 'wp_version' ), $result_data );

		return $this->build_response(
			true,
			'activated',
			__( 'License activated successfully.', 'uls' ),
			$result_data
		);
	}

	// -------------------------------------------------------------------------
	// Deactivation
	// -------------------------------------------------------------------------

	/**
	 * Deactivate a license for a given domain.
	 *
	 * @param string $purchase_code UUID purchase code.
	 * @param string $domain        Raw domain or URL from the client.
	 * @return array Standard response array.
	 */
	public function deactivate( $purchase_code, $domain ) {
		// Sanitise.
		$purchase_code = sanitize_text_field( trim( $purchase_code ) );
		$domain        = ULS_Security::sanitize_domain( $domain );
		$ip_address    = ULS_Security::get_client_ip();

		// Format check.
		if ( ! ULS_Security::validate_purchase_code_format( $purchase_code ) ) {
			return $this->build_response(
				false,
				'invalid_purchase_code',
				__( 'The purchase code format is invalid.', 'uls' )
			);
		}

		// Look up license.
		$license = ULS_Database::get_license_by_code( $purchase_code );

		if ( ! $license ) {
			return $this->build_response(
				false,
				'license_not_found',
				__( 'No license was found for this purchase code.', 'uls' )
			);
		}

		// Find active activation.
		$activation = ULS_Database::get_active_activation_by_domain( $license->id, $domain );

		if ( ! $activation ) {
			$this->log( $license->id, 'deactivate_failed', $domain, $ip_address, compact( 'purchase_code', 'domain' ), array( 'error' => 'activation_not_found' ) );
			return $this->build_response(
				false,
				'activation_not_found',
				sprintf(
					/* translators: %s: domain name */
					__( 'No active activation found for domain %s.', 'uls' ),
					$domain
				)
			);
		}

		// Mark activation as inactive.
		ULS_Database::update_activation(
			$activation->id,
			array(
				'status'            => 'inactive',
				'deactivation_date' => current_time( 'mysql' ),
			)
		);

		// Update license timestamp.
		ULS_Database::update_license( $license->id, array() );

		$this->log( $license->id, 'deactivate', $domain, $ip_address, compact( 'purchase_code', 'domain' ), array( 'status' => 'deactivated' ) );

		return $this->build_response(
			true,
			'deactivated',
			__( 'License deactivated successfully.', 'uls' )
		);
	}

	// -------------------------------------------------------------------------
	// Helpers
	// -------------------------------------------------------------------------

	/**
	 * Build the license data payload returned to the client.
	 *
	 * @param object $license License database row.
	 * @param string $domain  Sanitised domain string.
	 * @return array
	 */
	private function build_license_data( $license, $domain ) {
		return array(
			'license_type'    => $license->license_type,
			'support_until'   => $license->support_until,
			'buyer_name'      => $license->buyer_name,
			'max_domains'     => (int) $license->max_domains,
			'domain'          => $domain,
			'envato_verified' => (bool) $license->envato_verified,
		);
	}

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

	/**
	 * Write a log entry.
	 *
	 * @param int|null $license_id    License ID (null if not found).
	 * @param string   $action        Action label.
	 * @param string   $domain        Domain string.
	 * @param string   $ip_address    Client IP.
	 * @param array    $request_data  Request payload to log.
	 * @param array    $response_data Response payload to log.
	 */
	private function log( $license_id, $action, $domain, $ip_address, $request_data, $response_data ) {
		ULS_Database::log_request(
			array(
				'license_id'    => $license_id,
				'action'        => $action,
				'domain'        => $domain,
				'ip_address'    => $ip_address,
				'request_data'  => wp_json_encode( $request_data ),
				'response_data' => wp_json_encode( $response_data ),
			)
		);
	}
}
