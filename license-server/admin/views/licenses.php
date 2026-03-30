<?php
/**
 * All Licenses list view for UWA License Server.
 *
 * @package UWA_License_Server
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Build query args from URL params (nonce not required for GET search – capability check already done in render method).
$search  = isset( $_GET['s'] )      ? sanitize_text_field( wp_unslash( $_GET['s'] ) )      : ''; // phpcs:ignore WordPress.Security.NonceVerification
$status  = isset( $_GET['status'] ) ? sanitize_text_field( wp_unslash( $_GET['status'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
$paged   = isset( $_GET['paged'] )  ? absint( $_GET['paged'] ) : 1; // phpcs:ignore WordPress.Security.NonceVerification

$result   = ULS_Database::get_all_licenses(
	array(
		'page'     => $paged,
		'per_page' => 20,
		'search'   => $search,
		'status'   => $status,
	)
);

$licenses   = $result['licenses'];
$total      = $result['total'];
$total_pages = $result['pages'];

$status_labels = array(
	'active'   => __( 'Active', 'uls' ),
	'inactive' => __( 'Inactive', 'uls' ),
	'expired'  => __( 'Expired', 'uls' ),
	'banned'   => __( 'Banned', 'uls' ),
);
$status_badge_classes = array(
	'active'   => 'uls-badge-active',
	'inactive' => 'uls-badge-inactive',
	'expired'  => 'uls-badge-expired',
	'banned'   => 'uls-badge-banned',
);

/**
 * Helper: build a paginated URL preserving current filters.
 *
 * @param int $page Page number.
 * @return string Escaped URL.
 */
function uls_page_url( $page ) {
	$args = array( 'page' => 'uls-licenses', 'paged' => $page );
	if ( ! empty( $_GET['s'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
		$args['s'] = sanitize_text_field( wp_unslash( $_GET['s'] ) ); // phpcs:ignore WordPress.Security.NonceVerification
	}
	if ( ! empty( $_GET['status'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
		$args['status'] = sanitize_text_field( wp_unslash( $_GET['status'] ) ); // phpcs:ignore WordPress.Security.NonceVerification
	}
	return esc_url( add_query_arg( $args, admin_url( 'admin.php' ) ) );
}
?>
<div class="wrap uls-wrap">
	<h1 class="wp-heading-inline">
		<span class="dashicons dashicons-id-alt"></span>
		<?php esc_html_e( 'All Licenses', 'uls' ); ?>
	</h1>
	<a href="<?php echo esc_url( admin_url( 'admin.php?page=uls-add-license' ) ); ?>" class="page-title-action">
		<?php esc_html_e( 'Add New', 'uls' ); ?>
	</a>
	<hr class="wp-header-end">

	<?php if ( isset( $_GET['added'] ) && '1' === $_GET['added'] ) : // phpcs:ignore WordPress.Security.NonceVerification ?>
		<div class="notice notice-success is-dismissible">
			<p><?php esc_html_e( 'License added successfully.', 'uls' ); ?></p>
		</div>
	<?php endif; ?>

	<!-- Status filter tabs -->
	<ul class="subsubsub">
		<li>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=uls-licenses' ) ); ?>"
				class="<?php echo '' === $status ? 'current' : ''; ?>">
				<?php esc_html_e( 'All', 'uls' ); ?>
				<span class="count">(<?php echo esc_html( number_format_i18n( $total ) ); ?>)</span>
			</a> |
		</li>
		<?php foreach ( $status_labels as $key => $label ) : ?>
			<li>
				<a href="<?php echo esc_url( add_query_arg( array( 'page' => 'uls-licenses', 'status' => $key ), admin_url( 'admin.php' ) ) ); ?>"
					class="<?php echo $status === $key ? 'current' : ''; ?>">
					<?php echo esc_html( $label ); ?>
				</a>
				<?php echo $key !== array_key_last( $status_labels ) ? ' | ' : ''; ?>
			</li>
		<?php endforeach; ?>
	</ul>

	<!-- Search form -->
	<form method="get" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>">
		<input type="hidden" name="page" value="uls-licenses">
		<?php if ( ! empty( $status ) ) : ?>
			<input type="hidden" name="status" value="<?php echo esc_attr( $status ); ?>">
		<?php endif; ?>
		<p class="search-box">
			<label class="screen-reader-text" for="uls-search-input">
				<?php esc_html_e( 'Search Licenses:', 'uls' ); ?>
			</label>
			<input type="search" id="uls-search-input" name="s"
				value="<?php echo esc_attr( $search ); ?>"
				placeholder="<?php esc_attr_e( 'Search by code, name or email…', 'uls' ); ?>">
			<input type="submit" class="button" value="<?php esc_attr_e( 'Search Licenses', 'uls' ); ?>">
		</p>
	</form>

	<p class="displaying-num">
		<?php
		/* translators: %s: number of licenses */
		printf( esc_html( _n( '%s license', '%s licenses', $total, 'uls' ) ), esc_html( number_format_i18n( $total ) ) );
		?>
	</p>

	<!-- Pagination (top) -->
	<?php if ( $total_pages > 1 ) : ?>
		<div class="tablenav top">
			<div class="tablenav-pages">
				<span class="displaying-num">
					<?php /* translators: 1: current page, 2: total pages */ printf( esc_html__( 'Page %1$d of %2$d', 'uls' ), $paged, $total_pages ); ?>
				</span>
				<?php if ( $paged > 1 ) : ?>
					<a class="prev-page button" href="<?php echo uls_page_url( $paged - 1 ); // phpcs:ignore WordPress.Security.EscapeOutput ?>">
						<span class="screen-reader-text"><?php esc_html_e( 'Previous page', 'uls' ); ?></span>
						<span aria-hidden="true">&#8249;</span>
					</a>
				<?php endif; ?>
				<?php if ( $paged < $total_pages ) : ?>
					<a class="next-page button" href="<?php echo uls_page_url( $paged + 1 ); // phpcs:ignore WordPress.Security.EscapeOutput ?>">
						<span class="screen-reader-text"><?php esc_html_e( 'Next page', 'uls' ); ?></span>
						<span aria-hidden="true">&#8250;</span>
					</a>
				<?php endif; ?>
			</div>
		</div>
	<?php endif; ?>

	<!-- Licenses table -->
	<table class="wp-list-table widefat fixed striped uls-licenses-table">
		<thead>
			<tr>
				<th class="column-id"><?php esc_html_e( 'ID', 'uls' ); ?></th>
				<th class="column-code"><?php esc_html_e( 'Purchase Code', 'uls' ); ?></th>
				<th class="column-buyer"><?php esc_html_e( 'Buyer', 'uls' ); ?></th>
				<th class="column-type"><?php esc_html_e( 'Type', 'uls' ); ?></th>
				<th class="column-domains"><?php esc_html_e( 'Domains', 'uls' ); ?></th>
				<th class="column-status"><?php esc_html_e( 'Status', 'uls' ); ?></th>
				<th class="column-purchase-date"><?php esc_html_e( 'Purchased', 'uls' ); ?></th>
				<th class="column-support"><?php esc_html_e( 'Support Until', 'uls' ); ?></th>
				<th class="column-actions"><?php esc_html_e( 'Actions', 'uls' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php if ( ! empty( $licenses ) ) : ?>
				<?php foreach ( $licenses as $license ) : ?>
					<?php
					$active_count = ULS_Database::get_active_activations( $license->id );
					$badge_class  = isset( $status_badge_classes[ $license->status ] ) ? $status_badge_classes[ $license->status ] : 'uls-badge-inactive';
					$status_label = isset( $status_labels[ $license->status ] ) ? $status_labels[ $license->status ] : $license->status;

					// Mask purchase code: show first 8 chars + ellipsis.
					$masked_code = esc_html( substr( $license->purchase_code, 0, 8 ) ) . '&hellip;';

					// Support expiry warning.
					$support_class = '';
					if ( ! empty( $license->support_until ) ) {
						$days_left = ( strtotime( $license->support_until ) - time() ) / DAY_IN_SECONDS;
						if ( $days_left < 0 ) {
							$support_class = 'uls-support-expired';
						} elseif ( $days_left < 30 ) {
							$support_class = 'uls-support-expiring';
						}
					}
					?>
					<tr id="license-row-<?php echo esc_attr( $license->id ); ?>">
						<td><?php echo esc_html( $license->id ); ?></td>
						<td>
							<code class="uls-code-masked" title="<?php echo esc_attr( $license->purchase_code ); ?>">
								<?php echo $masked_code; // phpcs:ignore WordPress.Security.EscapeOutput ?>
							</code>
							<?php if ( $license->envato_verified ) : ?>
								<span class="uls-verified-badge" title="<?php esc_attr_e( 'Verified via Envato', 'uls' ); ?>">&#10003;</span>
							<?php endif; ?>
						</td>
						<td>
							<strong><?php echo esc_html( $license->buyer_name ?: '—' ); ?></strong>
							<?php if ( ! empty( $license->buyer_email ) ) : ?>
								<br><small><?php echo esc_html( $license->buyer_email ); ?></small>
							<?php endif; ?>
						</td>
						<td>
							<span class="uls-license-type-<?php echo esc_attr( $license->license_type ); ?>">
								<?php echo esc_html( ucfirst( $license->license_type ) ); ?>
							</span>
						</td>
						<td>
							<span class="<?php echo $active_count >= (int) $license->max_domains ? 'uls-domains-full' : ''; ?>">
								<?php echo esc_html( $active_count ); ?> / <?php echo esc_html( $license->max_domains ); ?>
							</span>
						</td>
						<td>
							<span class="uls-badge <?php echo esc_attr( $badge_class ); ?>">
								<?php echo esc_html( $status_label ); ?>
							</span>
						</td>
						<td>
							<?php echo ! empty( $license->purchase_date ) ? esc_html( wp_date( get_option( 'date_format' ), strtotime( $license->purchase_date ) ) ) : '—'; ?>
						</td>
						<td class="<?php echo esc_attr( $support_class ); ?>">
							<?php echo ! empty( $license->support_until ) ? esc_html( wp_date( get_option( 'date_format' ), strtotime( $license->support_until ) ) ) : '—'; ?>
						</td>
						<td class="uls-row-actions">
							<div class="row-actions">
								<span class="deactivate-all">
									<a href="#"
										class="uls-action-link uls-deactivate-all"
										data-id="<?php echo esc_attr( $license->id ); ?>"
										data-nonce="<?php echo esc_attr( wp_create_nonce( 'uls_admin_nonce' ) ); ?>">
										<?php esc_html_e( 'Deactivate All', 'uls' ); ?>
									</a> |
								</span>
								<?php if ( 'banned' !== $license->status ) : ?>
									<span class="ban">
										<a href="#"
											class="uls-action-link uls-ban-license"
											data-id="<?php echo esc_attr( $license->id ); ?>"
											data-nonce="<?php echo esc_attr( wp_create_nonce( 'uls_admin_nonce' ) ); ?>">
											<?php esc_html_e( 'Ban', 'uls' ); ?>
										</a> |
									</span>
								<?php endif; ?>
								<span class="delete">
									<a href="#"
										class="uls-action-link uls-delete-license"
										data-id="<?php echo esc_attr( $license->id ); ?>"
										data-nonce="<?php echo esc_attr( wp_create_nonce( 'uls_admin_nonce' ) ); ?>">
										<?php esc_html_e( 'Delete', 'uls' ); ?>
									</a>
								</span>
							</div>
						</td>
					</tr>
				<?php endforeach; ?>
			<?php else : ?>
				<tr>
					<td colspan="9" class="uls-empty-state">
						<?php esc_html_e( 'No licenses found.', 'uls' ); ?>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=uls-add-license' ) ); ?>">
							<?php esc_html_e( 'Add the first one.', 'uls' ); ?>
						</a>
					</td>
				</tr>
			<?php endif; ?>
		</tbody>
		<tfoot>
			<tr>
				<th><?php esc_html_e( 'ID', 'uls' ); ?></th>
				<th><?php esc_html_e( 'Purchase Code', 'uls' ); ?></th>
				<th><?php esc_html_e( 'Buyer', 'uls' ); ?></th>
				<th><?php esc_html_e( 'Type', 'uls' ); ?></th>
				<th><?php esc_html_e( 'Domains', 'uls' ); ?></th>
				<th><?php esc_html_e( 'Status', 'uls' ); ?></th>
				<th><?php esc_html_e( 'Purchased', 'uls' ); ?></th>
				<th><?php esc_html_e( 'Support Until', 'uls' ); ?></th>
				<th><?php esc_html_e( 'Actions', 'uls' ); ?></th>
			</tr>
		</tfoot>
	</table>

	<!-- Pagination (bottom) -->
	<?php if ( $total_pages > 1 ) : ?>
		<div class="tablenav bottom">
			<div class="tablenav-pages">
				<?php if ( $paged > 1 ) : ?>
					<a class="prev-page button" href="<?php echo uls_page_url( $paged - 1 ); // phpcs:ignore WordPress.Security.EscapeOutput ?>">&#8249;</a>
				<?php endif; ?>
				<span class="paging-input">
					<?php
					/* translators: 1: current page, 2: total pages */
					printf( esc_html__( '%1$d of %2$d', 'uls' ), $paged, $total_pages );
					?>
				</span>
				<?php if ( $paged < $total_pages ) : ?>
					<a class="next-page button" href="<?php echo uls_page_url( $paged + 1 ); // phpcs:ignore WordPress.Security.EscapeOutput ?>">&#8250;</a>
				<?php endif; ?>
			</div>
		</div>
	<?php endif; ?>

</div><!-- /.wrap -->
