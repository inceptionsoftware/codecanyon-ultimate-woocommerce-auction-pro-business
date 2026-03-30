<?php
/**
 * Admin Dashboard view for UWA License Server.
 *
 * @package UWA_License_Server
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$stats              = ULS_Database::get_stats();
$recent_activations = ULS_Database::get_recent_activations( 10 );
$recent_logs        = ULS_Database::get_recent_logs( 10 );
?>
<div class="wrap uls-wrap">
	<h1 class="wp-heading-inline">
		<span class="dashicons dashicons-admin-network"></span>
		<?php esc_html_e( 'License Server Dashboard', 'uls' ); ?>
	</h1>
	<hr class="wp-header-end">

	<?php if ( isset( $_GET['added'] ) && '1' === $_GET['added'] ) : // phpcs:ignore WordPress.Security.NonceVerification ?>
		<div class="notice notice-success is-dismissible">
			<p><?php esc_html_e( 'License added successfully.', 'uls' ); ?></p>
		</div>
	<?php endif; ?>

	<!-- Stats Cards -->
	<div class="uls-stats-grid">
		<div class="uls-stat-card uls-stat-total">
			<div class="uls-stat-icon">
				<span class="dashicons dashicons-id-alt"></span>
			</div>
			<div class="uls-stat-content">
				<span class="uls-stat-number"><?php echo esc_html( number_format_i18n( $stats['total_licenses'] ) ); ?></span>
				<span class="uls-stat-label"><?php esc_html_e( 'Total Licenses', 'uls' ); ?></span>
			</div>
		</div>

		<div class="uls-stat-card uls-stat-active">
			<div class="uls-stat-icon">
				<span class="dashicons dashicons-yes-alt"></span>
			</div>
			<div class="uls-stat-content">
				<span class="uls-stat-number"><?php echo esc_html( number_format_i18n( $stats['active_licenses'] ) ); ?></span>
				<span class="uls-stat-label"><?php esc_html_e( 'Active Licenses', 'uls' ); ?></span>
			</div>
		</div>

		<div class="uls-stat-card uls-stat-activations">
			<div class="uls-stat-icon">
				<span class="dashicons dashicons-admin-site-alt3"></span>
			</div>
			<div class="uls-stat-content">
				<span class="uls-stat-number"><?php echo esc_html( number_format_i18n( $stats['active_activations'] ) ); ?></span>
				<span class="uls-stat-label"><?php esc_html_e( 'Active Sites', 'uls' ); ?></span>
			</div>
		</div>

		<div class="uls-stat-card uls-stat-banned">
			<div class="uls-stat-icon">
				<span class="dashicons dashicons-dismiss"></span>
			</div>
			<div class="uls-stat-content">
				<span class="uls-stat-number"><?php echo esc_html( number_format_i18n( $stats['banned_licenses'] ) ); ?></span>
				<span class="uls-stat-label"><?php esc_html_e( 'Banned Licenses', 'uls' ); ?></span>
			</div>
		</div>
	</div>

	<div class="uls-dashboard-columns">

		<!-- Recent Activations -->
		<div class="uls-dashboard-col">
			<div class="uls-card">
				<div class="uls-card-header">
					<h2><?php esc_html_e( 'Recent Activations', 'uls' ); ?></h2>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=uls-licenses' ) ); ?>" class="button button-small">
						<?php esc_html_e( 'View All', 'uls' ); ?>
					</a>
				</div>
				<div class="uls-card-body">
					<?php if ( ! empty( $recent_activations ) ) : ?>
						<table class="wp-list-table widefat fixed striped uls-table">
							<thead>
								<tr>
									<th><?php esc_html_e( 'Domain', 'uls' ); ?></th>
									<th><?php esc_html_e( 'Purchase Code', 'uls' ); ?></th>
									<th><?php esc_html_e( 'Date', 'uls' ); ?></th>
									<th><?php esc_html_e( 'Status', 'uls' ); ?></th>
									<th><?php esc_html_e( 'IP', 'uls' ); ?></th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ( $recent_activations as $activation ) : ?>
									<tr>
										<td>
											<strong><?php echo esc_html( $activation->domain ); ?></strong>
										</td>
										<td>
											<code class="uls-code-small">
												<?php
												if ( ! empty( $activation->purchase_code ) ) {
													echo esc_html( substr( $activation->purchase_code, 0, 8 ) . '…' );
												} else {
													echo '—';
												}
												?>
											</code>
										</td>
										<td>
											<?php
											echo esc_html(
												wp_date(
													get_option( 'date_format' ) . ' ' . get_option( 'time_format' ),
													strtotime( $activation->activation_date )
												)
											);
											?>
										</td>
										<td>
											<?php
											$act_status = esc_attr( $activation->status );
											$badge_map  = array(
												'active'   => 'uls-badge-active',
												'inactive' => 'uls-badge-inactive',
											);
											$badge_class = isset( $badge_map[ $activation->status ] ) ? $badge_map[ $activation->status ] : 'uls-badge-inactive';
											?>
											<span class="uls-badge <?php echo esc_attr( $badge_class ); ?>">
												<?php echo esc_html( ucfirst( $activation->status ) ); ?>
											</span>
										</td>
										<td><?php echo esc_html( $activation->ip_address ); ?></td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					<?php else : ?>
						<p class="uls-empty-state"><?php esc_html_e( 'No activations yet.', 'uls' ); ?></p>
					<?php endif; ?>
				</div>
			</div>
		</div>

		<!-- Recent Logs -->
		<div class="uls-dashboard-col">
			<div class="uls-card">
				<div class="uls-card-header">
					<h2><?php esc_html_e( 'Recent API Logs', 'uls' ); ?></h2>
				</div>
				<div class="uls-card-body">
					<?php if ( ! empty( $recent_logs ) ) : ?>
						<table class="wp-list-table widefat fixed striped uls-table">
							<thead>
								<tr>
									<th><?php esc_html_e( 'Action', 'uls' ); ?></th>
									<th><?php esc_html_e( 'Domain', 'uls' ); ?></th>
									<th><?php esc_html_e( 'Date', 'uls' ); ?></th>
									<th><?php esc_html_e( 'IP', 'uls' ); ?></th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ( $recent_logs as $log ) : ?>
									<tr>
										<td>
											<?php
											$log_badge_map = array(
												'activate'              => 'uls-badge-active',
												'activate_existing'     => 'uls-badge-active',
												'deactivate'            => 'uls-badge-inactive',
												'activate_failed'       => 'uls-badge-banned',
												'deactivate_failed'     => 'uls-badge-banned',
												'activate_sig_fail'     => 'uls-badge-banned',
											);
											$log_action      = esc_html( $log->action );
											$log_badge_class = isset( $log_badge_map[ $log->action ] ) ? $log_badge_map[ $log->action ] : 'uls-badge-expired';
											?>
											<span class="uls-badge <?php echo esc_attr( $log_badge_class ); ?>">
												<?php echo esc_html( str_replace( '_', ' ', $log->action ) ); ?>
											</span>
										</td>
										<td><?php echo esc_html( $log->domain ); ?></td>
										<td>
											<?php
											echo esc_html(
												wp_date(
													get_option( 'date_format' ) . ' ' . get_option( 'time_format' ),
													strtotime( $log->created_at )
												)
											);
											?>
										</td>
										<td><?php echo esc_html( $log->ip_address ); ?></td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					<?php else : ?>
						<p class="uls-empty-state"><?php esc_html_e( 'No logs yet.', 'uls' ); ?></p>
					<?php endif; ?>
				</div>
			</div>
		</div>

	</div><!-- /.uls-dashboard-columns -->

	<!-- Quick actions & info -->
	<div class="uls-card uls-quick-actions">
		<div class="uls-card-header">
			<h2><?php esc_html_e( 'Quick Actions', 'uls' ); ?></h2>
		</div>
		<div class="uls-card-body">
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=uls-add-license' ) ); ?>" class="button button-primary">
				<span class="dashicons dashicons-plus-alt2"></span>
				<?php esc_html_e( 'Add New License', 'uls' ); ?>
			</a>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=uls-settings' ) ); ?>" class="button">
				<span class="dashicons dashicons-admin-settings"></span>
				<?php esc_html_e( 'Settings', 'uls' ); ?>
			</a>

			<div class="uls-api-info">
				<strong><?php esc_html_e( 'API Endpoints:', 'uls' ); ?></strong>
				<ul>
					<li>
						<code><?php echo esc_html( rest_url( 'uls/v1/activate' ) ); ?></code>
						<span class="uls-method-badge">POST</span>
					</li>
					<li>
						<code><?php echo esc_html( rest_url( 'uls/v1/deactivate' ) ); ?></code>
						<span class="uls-method-badge">POST</span>
					</li>
					<li>
						<code><?php echo esc_html( rest_url( 'uls/v1/validate' ) ); ?></code>
						<span class="uls-method-badge">POST</span>
					</li>
				</ul>
			</div>
		</div>
	</div>

</div><!-- /.wrap -->
