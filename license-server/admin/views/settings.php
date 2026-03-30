<?php
/**
 * Settings page view for UWA License Server.
 *
 * @package UWA_License_Server
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$envato_token = get_option( 'uls_envato_token', '' );
$item_id      = get_option( 'uls_item_id', defined( 'ULS_ITEM_ID' ) ? ULS_ITEM_ID : '' );
$secret_key   = ULS_Security::get_secret_key();
$rate_limit   = (int) get_option( 'uls_rate_limit', 20 );
$https_only   = (bool) get_option( 'uls_https_only', 0 );
$ip_whitelist = get_option( 'uls_ip_whitelist', '' );

$saved = isset( $_GET['saved'] ) && '1' === $_GET['saved']; // phpcs:ignore WordPress.Security.NonceVerification
?>
<div class="wrap uls-wrap">
	<h1 class="wp-heading-inline">
		<span class="dashicons dashicons-admin-settings"></span>
		<?php esc_html_e( 'License Server Settings', 'uls' ); ?>
	</h1>
	<hr class="wp-header-end">

	<?php if ( $saved ) : ?>
		<div class="notice notice-success is-dismissible">
			<p><?php esc_html_e( 'Settings saved successfully.', 'uls' ); ?></p>
		</div>
	<?php endif; ?>

	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" id="uls-settings-form">
		<input type="hidden" name="action" value="uls_save_settings">
		<?php wp_nonce_field( 'uls_save_settings', 'uls_settings_nonce' ); ?>

		<!-- Section 1: API Settings -->
		<div class="uls-card">
			<div class="uls-card-header">
				<h2><?php esc_html_e( 'API Settings', 'uls' ); ?></h2>
			</div>
			<div class="uls-card-body">

				<!-- Envato Token -->
				<div class="uls-field-row">
					<label for="uls_envato_token">
						<?php esc_html_e( 'Envato Personal Token', 'uls' ); ?>
					</label>
					<div class="uls-field-input">
						<div class="uls-input-group">
							<input
								type="password"
								id="uls_envato_token"
								name="uls_envato_token"
								class="regular-text"
								value="<?php echo esc_attr( $envato_token ); ?>"
								autocomplete="new-password"
								placeholder="<?php esc_attr_e( 'Enter your Envato Personal Token…', 'uls' ); ?>"
							>
							<button type="button" class="button uls-toggle-token" data-target="uls_envato_token" title="<?php esc_attr_e( 'Show/hide token', 'uls' ); ?>">
								<span class="dashicons dashicons-visibility"></span>
							</button>
						</div>
						<button type="button" id="uls-test-envato" class="button button-secondary">
							<span class="dashicons dashicons-cloud"></span>
							<?php esc_html_e( 'Test Envato Connection', 'uls' ); ?>
						</button>
						<span id="uls-envato-test-result"></span>
						<p class="description">
							<?php
							printf(
								/* translators: %s: link to Envato token page */
								esc_html__( 'Your Envato Personal Token. %s', 'uls' ),
								'<a href="https://build.envato.com/create-token/" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Generate one here', 'uls' ) . '</a>'
							);
							?>
						</p>
					</div>
				</div>

				<!-- CodeCanyon Item ID -->
				<div class="uls-field-row">
					<label for="uls_item_id">
						<?php esc_html_e( 'CodeCanyon Item ID', 'uls' ); ?>
					</label>
					<div class="uls-field-input">
						<input
							type="text"
							id="uls_item_id"
							name="uls_item_id"
							class="regular-text"
							value="<?php echo esc_attr( $item_id ); ?>"
							placeholder="12345678"
						>
						<p class="description">
							<?php esc_html_e( 'Your CodeCanyon item ID (numeric). Used to validate that purchase codes belong to your product.', 'uls' ); ?>
						</p>
					</div>
				</div>

				<!-- API Secret Key -->
				<div class="uls-field-row">
					<label for="uls_secret_key_display">
						<?php esc_html_e( 'API Secret Key', 'uls' ); ?>
					</label>
					<div class="uls-field-input">
						<div class="uls-input-group">
							<input
								type="text"
								id="uls_secret_key_display"
								class="regular-text code"
								value="<?php echo esc_attr( $secret_key ); ?>"
								readonly
							>
							<button type="button" id="uls-regenerate-key" class="button button-secondary">
								<span class="dashicons dashicons-update"></span>
								<?php esc_html_e( 'Regenerate', 'uls' ); ?>
							</button>
						</div>
						<span id="uls-regenerate-status"></span>
						<p class="description">
							<?php esc_html_e( 'This key is used to sign API requests from your client sites. Keep it secret. If you regenerate it, you must update all connected sites.', 'uls' ); ?>
						</p>
					</div>
				</div>

			</div><!-- /.uls-card-body -->
		</div><!-- /.uls-card -->

		<!-- Section 2: Security Settings -->
		<div class="uls-card">
			<div class="uls-card-header">
				<h2><?php esc_html_e( 'Security Settings', 'uls' ); ?></h2>
			</div>
			<div class="uls-card-body">

				<!-- Rate Limit -->
				<div class="uls-field-row">
					<label for="uls_rate_limit">
						<?php esc_html_e( 'Rate Limit', 'uls' ); ?>
					</label>
					<div class="uls-field-input">
						<input
							type="number"
							id="uls_rate_limit"
							name="uls_rate_limit"
							class="small-text"
							value="<?php echo esc_attr( $rate_limit ); ?>"
							min="1"
							max="1000"
						>
						<span class="uls-field-unit"><?php esc_html_e( 'requests per hour per IP', 'uls' ); ?></span>
						<p class="description">
							<?php esc_html_e( 'Maximum number of API requests allowed per IP address per hour. Default: 20.', 'uls' ); ?>
						</p>
					</div>
				</div>

				<!-- HTTPS Only -->
				<div class="uls-field-row">
					<label for="uls_https_only">
						<?php esc_html_e( 'HTTPS Only', 'uls' ); ?>
					</label>
					<div class="uls-field-input">
						<label class="uls-toggle-label">
							<input
								type="checkbox"
								id="uls_https_only"
								name="uls_https_only"
								value="1"
								<?php checked( $https_only, true ); ?>
							>
							<?php esc_html_e( 'Reject API requests from non-HTTPS origins', 'uls' ); ?>
						</label>
						<p class="description">
							<?php esc_html_e( 'When enabled, activation requests from sites not using HTTPS will be rejected.', 'uls' ); ?>
						</p>
					</div>
				</div>

				<!-- IP Whitelist -->
				<div class="uls-field-row">
					<label for="uls_ip_whitelist">
						<?php esc_html_e( 'IP Whitelist', 'uls' ); ?>
					</label>
					<div class="uls-field-input">
						<textarea
							id="uls_ip_whitelist"
							name="uls_ip_whitelist"
							rows="5"
							class="large-text code"
							placeholder="<?php esc_attr_e( "192.168.1.1\n10.0.0.0/24\nOne IP per line", 'uls' ); ?>"
						><?php echo esc_textarea( $ip_whitelist ); ?></textarea>
						<p class="description">
							<?php esc_html_e( 'IPs in this list bypass the rate limit. One IP address per line. Leave blank to apply rate limiting to all IPs.', 'uls' ); ?>
						</p>
					</div>
				</div>

			</div><!-- /.uls-card-body -->
		</div><!-- /.uls-card -->

		<!-- Save Button -->
		<div class="uls-form-actions">
			<input type="submit" class="button button-primary button-large" value="<?php esc_attr_e( 'Save Settings', 'uls' ); ?>">
		</div>

	</form>

	<!-- Section 3: Danger Zone -->
	<div class="uls-card uls-danger-zone">
		<div class="uls-card-header">
			<h2><?php esc_html_e( 'Danger Zone', 'uls' ); ?></h2>
		</div>
		<div class="uls-card-body">

			<div class="uls-danger-actions">

				<!-- Clear Logs -->
				<div class="uls-danger-item">
					<div class="uls-danger-desc">
						<strong><?php esc_html_e( 'Clear All Logs', 'uls' ); ?></strong>
						<p><?php esc_html_e( 'Permanently delete all API request log entries. License and activation data is preserved.', 'uls' ); ?></p>
					</div>
					<button
						type="button"
						id="uls-clear-logs"
						class="button button-secondary uls-btn-danger"
						data-nonce="<?php echo esc_attr( wp_create_nonce( 'uls_admin_nonce' ) ); ?>">
						<span class="dashicons dashicons-trash"></span>
						<?php esc_html_e( 'Clear Logs', 'uls' ); ?>
					</button>
					<span id="uls-clear-logs-result"></span>
				</div>

				<!-- Export CSV -->
				<div class="uls-danger-item">
					<div class="uls-danger-desc">
						<strong><?php esc_html_e( 'Export Licenses', 'uls' ); ?></strong>
						<p><?php esc_html_e( 'Download all license records as a CSV file.', 'uls' ); ?></p>
					</div>
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline;">
						<input type="hidden" name="action" value="uls_export_licenses">
						<?php wp_nonce_field( 'uls_export_licenses', 'uls_export_nonce' ); ?>
						<button type="submit" class="button button-secondary">
							<span class="dashicons dashicons-download"></span>
							<?php esc_html_e( 'Export CSV', 'uls' ); ?>
						</button>
					</form>
				</div>

			</div><!-- /.uls-danger-actions -->
		</div><!-- /.uls-card-body -->
	</div><!-- /.uls-card -->

</div><!-- /.wrap -->
