<?php
/**
 * Ultimate WooCommerce Auction Pro — License Settings Tab
 *
 * Included by uwa_general_setting.php when the 'uwa_license_setting' tab is active.
 *
 * @package Ultimate_WooCommerce_Auction_Pro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! current_user_can( 'manage_options' ) ) {
	wp_die( esc_html__( 'You do not have permission to access this page.', 'woo_ua' ) );
}

// -------------------------------------------------------------------------
// Load the license class if not already available.
// -------------------------------------------------------------------------
if ( ! class_exists( 'UWA_License' ) ) {
	require_once UW_AUCTION_PRO_DIR . '/includes/class-uwa-license.php';
}

$license = UWA_License::instance();

// -------------------------------------------------------------------------
// Process form submissions.
// -------------------------------------------------------------------------
$action_result = null; // Will hold ['type' => 'success'|'error', 'message' => ''].

// --- Activate ---
if ( isset( $_POST['uwa_license_action'] ) && 'activate' === $_POST['uwa_license_action'] ) {
	if ( ! isset( $_POST['uwa_license_activate_nonce'] )
		|| ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['uwa_license_activate_nonce'] ) ), 'uwa_license_activate' )
	) {
		$action_result = array(
			'type'    => 'error',
			'message' => __( 'Security check failed. Please refresh the page and try again.', 'woo_ua' ),
		);
	} else {
		$purchase_code = isset( $_POST['uwa_purchase_code'] ) ? sanitize_text_field( wp_unslash( $_POST['uwa_purchase_code'] ) ) : '';

		if ( empty( $purchase_code ) ) {
			$action_result = array(
				'type'    => 'error',
				'message' => __( 'Please enter your purchase code.', 'woo_ua' ),
			);
		} else {
			$result = $license->activate( $purchase_code );
			$action_result = array(
				'type'    => $result['success'] ? 'success' : 'error',
				'message' => $result['message'],
			);
		}
	}
}

// --- Deactivate ---
if ( isset( $_POST['uwa_license_action'] ) && 'deactivate' === $_POST['uwa_license_action'] ) {
	if ( ! isset( $_POST['uwa_license_deactivate_nonce'] )
		|| ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['uwa_license_deactivate_nonce'] ) ), 'uwa_license_deactivate' )
	) {
		$action_result = array(
			'type'    => 'error',
			'message' => __( 'Security check failed. Please refresh the page and try again.', 'woo_ua' ),
		);
	} else {
		$result = $license->deactivate();
		$action_result = array(
			'type'    => $result['success'] ? 'success' : 'error',
			'message' => $result['message'],
		);
	}
}

// -------------------------------------------------------------------------
// Collect current license state for display.
// -------------------------------------------------------------------------
$is_active    = $license->is_active();
$license_data = $license->get_license_data();

$masked_code    = $license->get_purchase_code();
$licensed_domain = isset( $license_data['domain'] ) ? $license_data['domain'] : '';
$license_type   = isset( $license_data['license_type'] ) ? $license_data['license_type'] : '';
$support_until  = isset( $license_data['support_until'] ) ? $license_data['support_until'] : '';
$buyer          = isset( $license_data['buyer'] ) ? $license_data['buyer'] : '';
$status_label   = $license->get_status_label();

// Format support_until date if it looks like a date string or timestamp.
if ( ! empty( $support_until ) ) {
	$ts = is_numeric( $support_until ) ? (int) $support_until : strtotime( $support_until );
	if ( $ts && $ts > 0 ) {
		$support_until_display = date_i18n( get_option( 'date_format' ), $ts );
	} else {
		$support_until_display = esc_html( $support_until );
	}
} else {
	$support_until_display = '&mdash;';
}

$codecanyon_purchases_url = 'https://codecanyon.net/downloads';
$support_url              = 'https://auctionplugin.net/support/';

?>
<div class="uwa_main_setting_content">

	<?php if ( null !== $action_result ) : ?>
		<div class="notice notice-<?php echo ( 'success' === $action_result['type'] ) ? 'success' : 'error'; ?> is-dismissible uwa-license-notice">
			<p><?php echo esc_html( $action_result['message'] ); ?></p>
		</div>
	<?php endif; ?>

	<div class="uwa-license-wrap" style="max-width:800px;">

		<!-- ================================================================
		     Card: License Activation
		     ================================================================ -->
		<div class="uwa-license-card" style="background:#fff;border:1px solid #ccd0d4;border-radius:4px;margin-bottom:24px;box-shadow:0 1px 1px rgba(0,0,0,.04);">

			<!-- Card header -->
			<div class="uwa-license-card-header" style="padding:16px 20px;border-bottom:1px solid #ccd0d4;display:flex;align-items:center;gap:10px;">
				<span style="font-size:22px;line-height:1;" aria-hidden="true">&#x1F511;</span>
				<h2 style="margin:0;font-size:16px;font-weight:600;color:#1d2327;">
					<?php esc_html_e( 'License Activation', 'woo_ua' ); ?>
				</h2>
				<span style="margin-left:auto;"><?php echo wp_kses_post( $status_label ); ?></span>
			</div>

			<!-- Card body -->
			<div class="uwa-license-card-body" style="padding:20px;">

				<?php if ( ! $is_active ) : ?>
					<!-- ---- NOT ACTIVATED: show activation form ---- -->
					<p style="margin-top:0;color:#50575e;">
						<?php esc_html_e( 'Enter your CodeCanyon purchase code to activate your license and enable all plugin features, automatic updates, and support.', 'woo_ua' ); ?>
					</p>

					<form method="post" action="" id="uwa-license-activate-form">
						<?php wp_nonce_field( 'uwa_license_activate', 'uwa_license_activate_nonce' ); ?>
						<input type="hidden" name="uwa_license_action" value="activate" />

						<table class="form-table" role="presentation" style="margin-top:0;">
							<tbody>
								<tr>
									<th scope="row" style="width:180px;padding-left:0;">
										<label for="uwa_purchase_code">
											<?php esc_html_e( 'Purchase Code', 'woo_ua' ); ?>
											<span style="color:#cc0000;" aria-hidden="true">*</span>
										</label>
									</th>
									<td style="padding-left:0;">
										<input
											type="text"
											id="uwa_purchase_code"
											name="uwa_purchase_code"
											class="regular-text"
											placeholder="xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx"
											autocomplete="off"
											style="font-family:monospace;width:360px;max-width:100%;"
											required
										/>
										<p class="description" style="margin-top:6px;">
											<?php
											printf(
												/* translators: 1: opening anchor tag, 2: closing anchor tag */
												esc_html__( 'Not sure where to find it? %1$sView your CodeCanyon purchases%2$s.', 'woo_ua' ),
												'<a href="' . esc_url( $codecanyon_purchases_url ) . '" target="_blank" rel="noopener noreferrer">',
												'</a>'
											);
											?>
										</p>
										<p class="description uwa-purchase-code-error" style="color:#cc0000;display:none;margin-top:4px;" aria-live="polite">
											<?php esc_html_e( 'Please enter a valid purchase code in UUID format: xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx', 'woo_ua' ); ?>
										</p>
									</td>
								</tr>
							</tbody>
						</table>

						<p style="margin-top:16px;">
							<button type="submit" id="uwa-activate-btn" class="button button-primary button-large">
								<?php esc_html_e( 'Activate License', 'woo_ua' ); ?>
							</button>
						</p>
					</form>

				<?php else : ?>
					<!-- ---- ACTIVATED: show license details ---- -->
					<table class="form-table" role="presentation" style="margin-top:0;">
						<tbody>

							<tr>
								<th scope="row" style="width:180px;padding-left:0;color:#50575e;font-weight:400;">
									<?php esc_html_e( 'Purchase Code', 'woo_ua' ); ?>
								</th>
								<td style="padding-left:0;">
									<code style="font-size:14px;"><?php echo esc_html( $masked_code ); ?></code>
								</td>
							</tr>

							<?php if ( ! empty( $licensed_domain ) ) : ?>
							<tr>
								<th scope="row" style="padding-left:0;color:#50575e;font-weight:400;">
									<?php esc_html_e( 'Licensed Domain', 'woo_ua' ); ?>
								</th>
								<td style="padding-left:0;">
									<strong><?php echo esc_html( $licensed_domain ); ?></strong>
								</td>
							</tr>
							<?php endif; ?>

							<?php if ( ! empty( $license_type ) ) : ?>
							<tr>
								<th scope="row" style="padding-left:0;color:#50575e;font-weight:400;">
									<?php esc_html_e( 'License Type', 'woo_ua' ); ?>
								</th>
								<td style="padding-left:0;">
									<?php echo esc_html( $license_type ); ?>
								</td>
							</tr>
							<?php endif; ?>

							<tr>
								<th scope="row" style="padding-left:0;color:#50575e;font-weight:400;">
									<?php esc_html_e( 'Support Until', 'woo_ua' ); ?>
								</th>
								<td style="padding-left:0;">
									<?php echo wp_kses_post( $support_until_display ); ?>
								</td>
							</tr>

							<?php if ( ! empty( $buyer ) ) : ?>
							<tr>
								<th scope="row" style="padding-left:0;color:#50575e;font-weight:400;">
									<?php esc_html_e( 'Buyer', 'woo_ua' ); ?>
								</th>
								<td style="padding-left:0;">
									<?php echo esc_html( $buyer ); ?>
								</td>
							</tr>
							<?php endif; ?>

						</tbody>
					</table>

					<!-- Deactivate form -->
					<form method="post" action="" id="uwa-license-deactivate-form" style="margin-top:20px;">
						<?php wp_nonce_field( 'uwa_license_deactivate', 'uwa_license_deactivate_nonce' ); ?>
						<input type="hidden" name="uwa_license_action" value="deactivate" />

						<button
							type="submit"
							class="button button-secondary"
							id="uwa-deactivate-btn"
							onclick="return confirm('<?php echo esc_js( __( 'Are you sure you want to deactivate the license? You will need to re-activate it if you wish to use it again.', 'woo_ua' ) ); ?>')"
						>
							<?php esc_html_e( 'Deactivate License', 'woo_ua' ); ?>
						</button>
					</form>

				<?php endif; ?>

			</div><!-- /.uwa-license-card-body -->

		</div><!-- /.uwa-license-card -->

		<!-- ================================================================
		     Card: Important Notes
		     ================================================================ -->
		<div class="uwa-license-card" style="background:#fff;border:1px solid #ccd0d4;border-radius:4px;margin-bottom:24px;box-shadow:0 1px 1px rgba(0,0,0,.04);">

			<div class="uwa-license-card-header" style="padding:16px 20px;border-bottom:1px solid #ccd0d4;display:flex;align-items:center;gap:10px;">
				<span style="font-size:20px;line-height:1;" aria-hidden="true">&#x1F4CB;</span>
				<h2 style="margin:0;font-size:16px;font-weight:600;color:#1d2327;">
					<?php esc_html_e( 'Important Notes', 'woo_ua' ); ?>
				</h2>
			</div>

			<div class="uwa-license-card-body" style="padding:20px;">
				<ul style="margin:0;padding-left:20px;color:#50575e;line-height:1.8;">
					<li>
						<?php esc_html_e( 'One purchase code activates on one domain only (Regular License).', 'woo_ua' ); ?>
					</li>
					<li>
						<?php esc_html_e( 'To transfer to a new domain, deactivate the license on this domain first, then activate on the new domain.', 'woo_ua' ); ?>
					</li>
					<li>
						<?php esc_html_e( 'Your license is validated daily. If the license server is temporarily unreachable, a 3-day grace period applies.', 'woo_ua' ); ?>
					</li>
					<li>
						<?php esc_html_e( 'Staging or development environments (localhost, .local, .dev, .test) do not consume a license slot.', 'woo_ua' ); ?>
					</li>
					<li>
						<?php
						printf(
							/* translators: 1: opening anchor tag, 2: closing anchor tag */
							esc_html__( 'Need help? %1$sContact our support team%2$s.', 'woo_ua' ),
							'<a href="' . esc_url( $support_url ) . '" target="_blank" rel="noopener noreferrer">',
							'</a>'
						);
						?>
					</li>
					<li>
						<?php
						printf(
							/* translators: 1: opening anchor tag, 2: closing anchor tag */
							esc_html__( 'Lost your purchase code? %1$sFind it in your CodeCanyon purchases%2$s.', 'woo_ua' ),
							'<a href="' . esc_url( $codecanyon_purchases_url ) . '" target="_blank" rel="noopener noreferrer">',
							'</a>'
						);
						?>
					</li>
				</ul>
			</div>

		</div><!-- /.uwa-license-card -->

	</div><!-- /.uwa-license-wrap -->

</div><!-- /.uwa_main_setting_content -->

<!-- =========================================================================
     Inline JS: client-side purchase code format validation
     ========================================================================= -->
<script type="text/javascript">
( function () {
	'use strict';

	var form        = document.getElementById( 'uwa-license-activate-form' );
	var input       = document.getElementById( 'uwa_purchase_code' );
	var errorMsg    = document.querySelector( '.uwa-purchase-code-error' );
	var submitBtn   = document.getElementById( 'uwa-activate-btn' );

	// UUID v4 pattern: xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx
	var uuidRegex = /^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i;

	if ( ! form || ! input ) {
		return;
	}

	function validateCode( value ) {
		return uuidRegex.test( value.trim() );
	}

	function showError( show ) {
		if ( errorMsg ) {
			errorMsg.style.display = show ? 'block' : 'none';
		}
		if ( submitBtn ) {
			submitBtn.disabled = show;
		}
	}

	// Validate on input (real-time feedback only when the field has content).
	input.addEventListener( 'input', function () {
		var val = this.value.trim();
		if ( val.length === 0 ) {
			showError( false );
			return;
		}
		showError( ! validateCode( val ) );
	} );

	// Validate on blur.
	input.addEventListener( 'blur', function () {
		var val = this.value.trim();
		if ( val.length > 0 ) {
			showError( ! validateCode( val ) );
		}
	} );

	// Prevent form submission if code is invalid.
	form.addEventListener( 'submit', function ( e ) {
		var val = input.value.trim();
		if ( val.length === 0 ) {
			e.preventDefault();
			input.focus();
			return;
		}
		if ( ! validateCode( val ) ) {
			e.preventDefault();
			showError( true );
			input.focus();
		}
	} );
}() );
</script>
