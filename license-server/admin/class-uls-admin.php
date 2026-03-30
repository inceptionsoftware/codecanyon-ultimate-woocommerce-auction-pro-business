<?php
/**
 * Admin interface for UWA License Server.
 *
 * Registers menus, handles form submissions, and renders view files.
 *
 * @package UWA_License_Server
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class ULS_Admin
 */
class ULS_Admin {

	/**
	 * Singleton instance.
	 *
	 * @var self|null
	 */
	private static $instance = null;

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
	 * Hook into WordPress admin.
	 */
	public function init() {
		add_action( 'admin_menu',                             array( $this, 'register_menus' ) );
		add_action( 'admin_enqueue_scripts',                  array( $this, 'enqueue_assets' ) );
		add_action( 'admin_post_uls_add_license',             array( $this, 'handle_add_license' ) );
		add_action( 'admin_post_uls_save_settings',           array( $this, 'handle_save_settings' ) );
		add_action( 'wp_ajax_uls_delete_license',             array( $this, 'handle_delete_license' ) );
		add_action( 'wp_ajax_uls_ban_license',                array( $this, 'handle_ban_license' ) );
		add_action( 'wp_ajax_uls_deactivate_all',             array( $this, 'handle_deactivate_all' ) );
		add_action( 'wp_ajax_uls_verify_envato_purchase',     array( $this, 'handle_verify_envato_ajax' ) );
		add_action( 'wp_ajax_uls_test_envato_connection',     array( $this, 'handle_test_envato_ajax' ) );
		add_action( 'wp_ajax_uls_regenerate_secret_key',      array( $this, 'handle_regenerate_key_ajax' ) );
		add_action( 'wp_ajax_uls_clear_logs',                 array( $this, 'handle_clear_logs_ajax' ) );
		add_action( 'admin_post_uls_export_licenses',         array( $this, 'handle_export_licenses' ) );
	}

	// -------------------------------------------------------------------------
	// Menus
	// -------------------------------------------------------------------------

	/**
	 * Register admin menus and sub-menus.
	 */
	public function register_menus() {
		add_menu_page(
			__( 'License Server', 'uls' ),
			__( 'License Server', 'uls' ),
			'manage_options',
			'uls-dashboard',
			array( $this, 'render_dashboard' ),
			'dashicons-admin-network',
			75
		);

		add_submenu_page(
			'uls-dashboard',
			__( 'Dashboard', 'uls' ),
			__( 'Dashboard', 'uls' ),
			'manage_options',
			'uls-dashboard',
			array( $this, 'render_dashboard' )
		);

		add_submenu_page(
			'uls-dashboard',
			__( 'All Licenses', 'uls' ),
			__( 'All Licenses', 'uls' ),
			'manage_options',
			'uls-licenses',
			array( $this, 'render_licenses' )
		);

		add_submenu_page(
			'uls-dashboard',
			__( 'Add License', 'uls' ),
			__( 'Add License', 'uls' ),
			'manage_options',
			'uls-add-license',
			array( $this, 'render_add_license' )
		);

		add_submenu_page(
			'uls-dashboard',
			__( 'Settings', 'uls' ),
			__( 'Settings', 'uls' ),
			'manage_options',
			'uls-settings',
			array( $this, 'render_settings' )
		);
	}

	// -------------------------------------------------------------------------
	// Assets
	// -------------------------------------------------------------------------

	/**
	 * Enqueue admin CSS and JS only on our plugin pages.
	 *
	 * @param string $hook Current admin page hook.
	 */
	public function enqueue_assets( $hook ) {
		$uls_pages = array(
			'toplevel_page_uls-dashboard',
			'license-server_page_uls-licenses',
			'license-server_page_uls-add-license',
			'license-server_page_uls-settings',
		);

		if ( ! in_array( $hook, $uls_pages, true ) ) {
			return;
		}

		wp_enqueue_style(
			'uls-admin',
			ULS_URL . 'admin/assets/admin.css',
			array(),
			ULS_VERSION
		);

		wp_enqueue_script(
			'uls-admin',
			ULS_URL . 'admin/assets/admin.js',
			array( 'jquery' ),
			ULS_VERSION,
			true
		);

		wp_localize_script(
			'uls-admin',
			'ulsAdminData',
			array(
				'ajaxUrl'    => admin_url( 'admin-ajax.php' ),
				'nonce'      => wp_create_nonce( 'uls_admin_nonce' ),
				'confirmBan' => __( 'Are you sure you want to ban this license? Banned licenses cannot be activated.', 'uls' ),
				'confirmDel' => __( 'Are you sure you want to delete this license? This action cannot be undone.', 'uls' ),
				'confirmReg' => __( 'Are you sure you want to regenerate the secret key? All connected sites will need updating.', 'uls' ),
				'confirmLog' => __( 'Are you sure you want to clear all logs?', 'uls' ),
				'i18n'       => array(
					'verifying'   => __( 'Verifying with Envato...', 'uls' ),
					'testing'     => __( 'Testing connection...', 'uls' ),
					'regenerating'=> __( 'Regenerating...', 'uls' ),
				),
			)
		);
	}

	// -------------------------------------------------------------------------
	// Form handlers
	// -------------------------------------------------------------------------

	/**
	 * Process the "Add License" form submission.
	 */
	public function handle_add_license() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to perform this action.', 'uls' ) );
		}

		check_admin_referer( 'uls_add_license', 'uls_nonce' );

		$purchase_code = sanitize_text_field( wp_unslash( $_POST['purchase_code'] ?? '' ) );
		$buyer_name    = sanitize_text_field( wp_unslash( $_POST['buyer_name'] ?? '' ) );
		$buyer_email   = sanitize_email( wp_unslash( $_POST['buyer_email'] ?? '' ) );
		$license_type  = sanitize_text_field( wp_unslash( $_POST['license_type'] ?? 'regular' ) );
		$max_domains   = absint( $_POST['max_domains'] ?? 1 );
		$support_until = sanitize_text_field( wp_unslash( $_POST['support_until'] ?? '' ) );
		$purchase_date = sanitize_text_field( wp_unslash( $_POST['purchase_date'] ?? '' ) );
		$notes         = sanitize_textarea_field( wp_unslash( $_POST['notes'] ?? '' ) );

		// Validate purchase code format.
		if ( ! ULS_Security::validate_purchase_code_format( $purchase_code ) ) {
			wp_safe_redirect(
				add_query_arg(
					array( 'page' => 'uls-add-license', 'error' => 'invalid_code' ),
					admin_url( 'admin.php' )
				)
			);
			exit;
		}

		// Check for duplicate.
		if ( ULS_Database::get_license_by_code( $purchase_code ) ) {
			wp_safe_redirect(
				add_query_arg(
					array( 'page' => 'uls-add-license', 'error' => 'duplicate' ),
					admin_url( 'admin.php' )
				)
			);
			exit;
		}

		// Normalise license type.
		$license_type = in_array( $license_type, array( 'regular', 'extended' ), true ) ? $license_type : 'regular';

		$license_id = ULS_Database::create_license(
			array(
				'purchase_code'   => $purchase_code,
				'buyer_name'      => $buyer_name,
				'buyer_email'     => $buyer_email,
				'product_id'      => get_option( 'uls_item_id', defined( 'ULS_ITEM_ID' ) ? ULS_ITEM_ID : '' ),
				'license_type'    => $license_type,
				'max_domains'     => $max_domains,
				'status'          => 'active',
				'support_until'   => ! empty( $support_until ) ? $support_until : null,
				'purchase_date'   => ! empty( $purchase_date ) ? $purchase_date : null,
				'envato_verified' => 0,
				'notes'           => $notes,
			)
		);

		if ( $license_id ) {
			wp_safe_redirect(
				add_query_arg(
					array( 'page' => 'uls-licenses', 'added' => '1' ),
					admin_url( 'admin.php' )
				)
			);
		} else {
			wp_safe_redirect(
				add_query_arg(
					array( 'page' => 'uls-add-license', 'error' => 'db_error' ),
					admin_url( 'admin.php' )
				)
			);
		}
		exit;
	}

	/**
	 * Process the Settings form submission.
	 */
	public function handle_save_settings() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to perform this action.', 'uls' ) );
		}

		check_admin_referer( 'uls_save_settings', 'uls_settings_nonce' );

		$envato_token   = sanitize_text_field( wp_unslash( $_POST['uls_envato_token'] ?? '' ) );
		$item_id        = sanitize_text_field( wp_unslash( $_POST['uls_item_id'] ?? '' ) );
		$rate_limit     = absint( $_POST['uls_rate_limit'] ?? 20 );
		$https_only     = ! empty( $_POST['uls_https_only'] ) ? 1 : 0;
		$ip_whitelist   = sanitize_textarea_field( wp_unslash( $_POST['uls_ip_whitelist'] ?? '' ) );

		// Only update the token if a non-empty value was submitted (prevent accidental blanking).
		if ( ! empty( $envato_token ) ) {
			update_option( 'uls_envato_token', $envato_token, false );
		}

		update_option( 'uls_item_id', $item_id );
		update_option( 'uls_rate_limit', max( 1, $rate_limit ) );
		update_option( 'uls_https_only', $https_only );
		update_option( 'uls_ip_whitelist', $ip_whitelist );

		wp_safe_redirect(
			add_query_arg(
				array( 'page' => 'uls-settings', 'saved' => '1' ),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	// -------------------------------------------------------------------------
	// AJAX handlers
	// -------------------------------------------------------------------------

	/**
	 * AJAX: Soft-delete (set status = inactive) a license.
	 */
	public function handle_delete_license() {
		check_ajax_referer( 'uls_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'uls' ) ) );
		}

		$license_id = absint( $_POST['license_id'] ?? 0 );
		if ( ! $license_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid license ID.', 'uls' ) ) );
		}

		ULS_Database::deactivate_all_for_license( $license_id );
		$result = ULS_Database::update_license( $license_id, array( 'status' => 'inactive' ) );

		if ( $result ) {
			wp_send_json_success( array( 'message' => __( 'License deleted (marked inactive).', 'uls' ) ) );
		} else {
			wp_send_json_error( array( 'message' => __( 'Failed to delete license.', 'uls' ) ) );
		}
	}

	/**
	 * AJAX: Ban a license.
	 */
	public function handle_ban_license() {
		check_ajax_referer( 'uls_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'uls' ) ) );
		}

		$license_id = absint( $_POST['license_id'] ?? 0 );
		if ( ! $license_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid license ID.', 'uls' ) ) );
		}

		ULS_Database::deactivate_all_for_license( $license_id );
		$result = ULS_Database::update_license( $license_id, array( 'status' => 'banned' ) );

		if ( $result ) {
			wp_send_json_success( array( 'message' => __( 'License banned.', 'uls' ) ) );
		} else {
			wp_send_json_error( array( 'message' => __( 'Failed to ban license.', 'uls' ) ) );
		}
	}

	/**
	 * AJAX: Deactivate all domains for a license.
	 */
	public function handle_deactivate_all() {
		check_ajax_referer( 'uls_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'uls' ) ) );
		}

		$license_id = absint( $_POST['license_id'] ?? 0 );
		if ( ! $license_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid license ID.', 'uls' ) ) );
		}

		ULS_Database::deactivate_all_for_license( $license_id );
		wp_send_json_success( array( 'message' => __( 'All activations have been deactivated.', 'uls' ) ) );
	}

	/**
	 * AJAX: Verify a purchase code against the Envato API and return buyer details.
	 */
	public function handle_verify_envato_ajax() {
		check_ajax_referer( 'uls_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'uls' ) ) );
		}

		$purchase_code = sanitize_text_field( wp_unslash( $_POST['purchase_code'] ?? '' ) );
		if ( ! ULS_Security::validate_purchase_code_format( $purchase_code ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid purchase code format.', 'uls' ) ) );
		}

		$envato = new ULS_Envato();
		if ( ! $envato->is_configured() ) {
			wp_send_json_error( array( 'message' => __( 'Envato Personal Token is not configured. Go to Settings first.', 'uls' ) ) );
		}

		$result = $envato->verify_purchase( $purchase_code );

		if ( $result['success'] ) {
			wp_send_json_success( $result );
		} else {
			wp_send_json_error( array( 'message' => $result['error'] ) );
		}
	}

	/**
	 * AJAX: Test the Envato API connection.
	 */
	public function handle_test_envato_ajax() {
		check_ajax_referer( 'uls_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'uls' ) ) );
		}

		$envato = new ULS_Envato();
		$result = $envato->test_connection();

		if ( $result['success'] ) {
			wp_send_json_success(
				array(
					'message'  => sprintf(
						/* translators: %s: Envato username */
						__( 'Connection successful! Authenticated as: %s', 'uls' ),
						esc_html( $result['username'] )
					),
				)
			);
		} else {
			wp_send_json_error( array( 'message' => $result['error'] ) );
		}
	}

	/**
	 * AJAX: Regenerate the HMAC secret key.
	 */
	public function handle_regenerate_key_ajax() {
		check_ajax_referer( 'uls_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'uls' ) ) );
		}

		$new_key = ULS_Security::regenerate_secret_key();
		wp_send_json_success(
			array(
				'key'     => $new_key,
				'message' => __( 'Secret key regenerated successfully. Update all client sites.', 'uls' ),
			)
		);
	}

	/**
	 * AJAX: Clear all log entries.
	 */
	public function handle_clear_logs_ajax() {
		check_ajax_referer( 'uls_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'uls' ) ) );
		}

		ULS_Database::clear_logs();
		wp_send_json_success( array( 'message' => __( 'All logs cleared.', 'uls' ) ) );
	}

	/**
	 * Export all licenses as a CSV file download.
	 */
	public function handle_export_licenses() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to perform this action.', 'uls' ) );
		}

		check_admin_referer( 'uls_export_licenses', 'uls_export_nonce' );

		$licenses = ULS_Database::export_licenses();

		header( 'Content-Type: text/csv; charset=UTF-8' );
		header( 'Content-Disposition: attachment; filename="uls-licenses-' . gmdate( 'Y-m-d' ) . '.csv"' );
		header( 'Pragma: no-cache' );
		header( 'Expires: 0' );

		$output = fopen( 'php://output', 'w' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fopen
		fputcsv(
			$output,
			array( 'ID', 'Purchase Code', 'Buyer Name', 'Buyer Email', 'License Type', 'Max Domains', 'Status', 'Support Until', 'Purchase Date', 'Envato Verified', 'Created At' )
		);

		foreach ( $licenses as $license ) {
			fputcsv(
				$output,
				array(
					$license->id,
					$license->purchase_code,
					$license->buyer_name,
					$license->buyer_email,
					$license->license_type,
					$license->max_domains,
					$license->status,
					$license->support_until,
					$license->purchase_date,
					$license->envato_verified ? 'Yes' : 'No',
					$license->created_at,
				)
			);
		}

		fclose( $output ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fclose
		exit;
	}

	// -------------------------------------------------------------------------
	// Page render methods
	// -------------------------------------------------------------------------

	/**
	 * Render the Dashboard page.
	 */
	public function render_dashboard() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to view this page.', 'uls' ) );
		}
		include ULS_ADMIN_DIR . 'views/dashboard.php';
	}

	/**
	 * Render the All Licenses page.
	 */
	public function render_licenses() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to view this page.', 'uls' ) );
		}
		include ULS_ADMIN_DIR . 'views/licenses.php';
	}

	/**
	 * Render the Add License page.
	 */
	public function render_add_license() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to view this page.', 'uls' ) );
		}
		include ULS_ADMIN_DIR . 'views/add-license.php';
	}

	/**
	 * Render the Settings page.
	 */
	public function render_settings() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to view this page.', 'uls' ) );
		}
		include ULS_ADMIN_DIR . 'views/settings.php';
	}
}
