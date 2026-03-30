<?php
/**
 * Add License form view for UWA License Server.
 *
 * @package UWA_License_Server
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$error_messages = array(
	'invalid_code' => __( 'The purchase code must be a valid UUID (xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx).', 'uls' ),
	'duplicate'    => __( 'A license with this purchase code already exists.', 'uls' ),
	'db_error'     => __( 'A database error occurred. Please try again.', 'uls' ),
);

$error = isset( $_GET['error'] ) ? sanitize_text_field( wp_unslash( $_GET['error'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
?>
<div class="wrap uls-wrap">
	<h1 class="wp-heading-inline">
		<span class="dashicons dashicons-plus-alt2"></span>
		<?php esc_html_e( 'Add License', 'uls' ); ?>
	</h1>
	<a href="<?php echo esc_url( admin_url( 'admin.php?page=uls-licenses' ) ); ?>" class="page-title-action">
		<?php esc_html_e( '&#8592; Back to Licenses', 'uls' ); ?>
	</a>
	<hr class="wp-header-end">

	<?php if ( ! empty( $error ) && isset( $error_messages[ $error ] ) ) : ?>
		<div class="notice notice-error is-dismissible">
			<p><?php echo esc_html( $error_messages[ $error ] ); ?></p>
		</div>
	<?php endif; ?>

	<div class="uls-form-wrap">
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" id="uls-add-license-form">
			<input type="hidden" name="action" value="uls_add_license">
			<?php wp_nonce_field( 'uls_add_license', 'uls_nonce' ); ?>

			<div class="uls-card">
				<div class="uls-card-header">
					<h2><?php esc_html_e( 'License Details', 'uls' ); ?></h2>
				</div>
				<div class="uls-card-body">

					<!-- Purchase Code -->
					<div class="uls-field-row">
						<label for="purchase_code">
							<?php esc_html_e( 'Purchase Code', 'uls' ); ?>
							<span class="required">*</span>
						</label>
						<div class="uls-field-input">
							<input
								type="text"
								id="purchase_code"
								name="purchase_code"
								class="regular-text code"
								placeholder="xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx"
								pattern="[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}"
								required
								autocomplete="off"
							>
							<button type="button" id="uls-verify-envato" class="button button-secondary">
								<span class="dashicons dashicons-cloud"></span>
								<?php esc_html_e( 'Verify with Envato', 'uls' ); ?>
							</button>
							<span id="uls-verify-status"></span>
							<p class="description">
								<?php esc_html_e( 'The Envato purchase code in UUID format. Click "Verify with Envato" to auto-fill buyer details.', 'uls' ); ?>
							</p>
							<p id="uls-code-format-error" class="uls-field-error" style="display:none;">
								<?php esc_html_e( 'Invalid format. Must be: xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx', 'uls' ); ?>
							</p>
						</div>
					</div>

					<!-- Buyer Name -->
					<div class="uls-field-row">
						<label for="buyer_name">
							<?php esc_html_e( 'Buyer Name', 'uls' ); ?>
						</label>
						<div class="uls-field-input">
							<input
								type="text"
								id="buyer_name"
								name="buyer_name"
								class="regular-text"
								placeholder="<?php esc_attr_e( 'Envato username or full name', 'uls' ); ?>"
							>
						</div>
					</div>

					<!-- Buyer Email -->
					<div class="uls-field-row">
						<label for="buyer_email">
							<?php esc_html_e( 'Buyer Email', 'uls' ); ?>
						</label>
						<div class="uls-field-input">
							<input
								type="email"
								id="buyer_email"
								name="buyer_email"
								class="regular-text"
								placeholder="buyer@example.com"
							>
						</div>
					</div>

					<!-- License Type -->
					<div class="uls-field-row">
						<label for="license_type">
							<?php esc_html_e( 'License Type', 'uls' ); ?>
							<span class="required">*</span>
						</label>
						<div class="uls-field-input">
							<select id="license_type" name="license_type">
								<option value="regular"><?php esc_html_e( 'Regular License (1 domain)', 'uls' ); ?></option>
								<option value="extended"><?php esc_html_e( 'Extended License (unlimited domains)', 'uls' ); ?></option>
							</select>
							<p class="description">
								<?php esc_html_e( 'Regular licenses allow activation on 1 domain. Extended licenses allow unlimited domains.', 'uls' ); ?>
							</p>
						</div>
					</div>

					<!-- Max Domains -->
					<div class="uls-field-row">
						<label for="max_domains">
							<?php esc_html_e( 'Max Domains', 'uls' ); ?>
						</label>
						<div class="uls-field-input">
							<input
								type="number"
								id="max_domains"
								name="max_domains"
								class="small-text"
								value="1"
								min="1"
								max="9999"
							>
							<p class="description">
								<?php esc_html_e( 'Maximum number of domains allowed. Auto-set to 1 (regular) or 999 (extended) when type changes.', 'uls' ); ?>
							</p>
						</div>
					</div>

					<!-- Support Until -->
					<div class="uls-field-row">
						<label for="support_until">
							<?php esc_html_e( 'Support Until', 'uls' ); ?>
						</label>
						<div class="uls-field-input">
							<input
								type="date"
								id="support_until"
								name="support_until"
								class="regular-text"
							>
							<p class="description">
								<?php esc_html_e( 'Date until which the buyer is entitled to plugin support (from Envato).', 'uls' ); ?>
							</p>
						</div>
					</div>

					<!-- Purchase Date -->
					<div class="uls-field-row">
						<label for="purchase_date">
							<?php esc_html_e( 'Purchase Date', 'uls' ); ?>
						</label>
						<div class="uls-field-input">
							<input
								type="date"
								id="purchase_date"
								name="purchase_date"
								class="regular-text"
							>
						</div>
					</div>

					<!-- Notes -->
					<div class="uls-field-row">
						<label for="notes">
							<?php esc_html_e( 'Notes', 'uls' ); ?>
						</label>
						<div class="uls-field-input">
							<textarea
								id="notes"
								name="notes"
								rows="3"
								class="large-text"
								placeholder="<?php esc_attr_e( 'Internal notes about this license…', 'uls' ); ?>"
							></textarea>
						</div>
					</div>

				</div><!-- /.uls-card-body -->
				<div class="uls-card-footer">
					<input type="submit" class="button button-primary button-large" value="<?php esc_attr_e( 'Add License', 'uls' ); ?>">
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=uls-licenses' ) ); ?>" class="button button-large">
						<?php esc_html_e( 'Cancel', 'uls' ); ?>
					</a>
				</div>
			</div><!-- /.uls-card -->

		</form>
	</div><!-- /.uls-form-wrap -->

</div><!-- /.wrap -->
