<?php
/**
 * Database handler for UWA License Server.
 *
 * Creates and manages the three plugin tables:
 *   - {prefix}uls_licenses
 *   - {prefix}uls_activations
 *   - {prefix}uls_logs
 *
 * @package UWA_License_Server
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class ULS_Database
 */
class ULS_Database {

	/**
	 * Current schema version stored in option 'uls_db_version'.
	 *
	 * @var string
	 */
	const DB_VERSION = '1.0.0';

	// -------------------------------------------------------------------------
	// Schema installation / upgrade
	// -------------------------------------------------------------------------

	/**
	 * Create (or upgrade) the three plugin tables.
	 * Called on plugin activation via register_activation_hook().
	 */
	public static function create_tables() {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		// ------------------------------------------------------------------
		// Table: uls_licenses
		// ------------------------------------------------------------------
		$table_licenses = $wpdb->prefix . 'uls_licenses';
		$sql_licenses   = "CREATE TABLE {$table_licenses} (
			id              BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			purchase_code   VARCHAR(255)        NOT NULL,
			buyer_name      VARCHAR(255)        NOT NULL DEFAULT '',
			buyer_email     VARCHAR(255)        NOT NULL DEFAULT '',
			product_id      VARCHAR(50)         NOT NULL DEFAULT '',
			license_type    ENUM('regular','extended') NOT NULL DEFAULT 'regular',
			max_domains     INT(11)             NOT NULL DEFAULT 1,
			status          ENUM('active','inactive','expired','banned') NOT NULL DEFAULT 'active',
			support_until   DATE                NULL DEFAULT NULL,
			purchase_date   DATE                NULL DEFAULT NULL,
			envato_verified TINYINT(1)          NOT NULL DEFAULT 0,
			notes           TEXT                NULL DEFAULT NULL,
			created_at      DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at      DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			UNIQUE KEY purchase_code (purchase_code),
			KEY status (status),
			KEY buyer_email (buyer_email(191))
		) {$charset_collate};";

		// ------------------------------------------------------------------
		// Table: uls_activations
		// ------------------------------------------------------------------
		$table_activations = $wpdb->prefix . 'uls_activations';
		$sql_activations   = "CREATE TABLE {$table_activations} (
			id                 BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			license_id         BIGINT(20) UNSIGNED NOT NULL,
			domain             VARCHAR(500)        NOT NULL DEFAULT '',
			site_url           VARCHAR(500)        NOT NULL DEFAULT '',
			activation_date    DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
			deactivation_date  DATETIME            NULL DEFAULT NULL,
			ip_address         VARCHAR(45)         NOT NULL DEFAULT '',
			wp_version         VARCHAR(20)         NOT NULL DEFAULT '',
			plugin_version     VARCHAR(20)         NOT NULL DEFAULT '',
			status             ENUM('active','inactive') NOT NULL DEFAULT 'active',
			PRIMARY KEY  (id),
			KEY license_id (license_id),
			KEY domain (domain(191)),
			KEY status (status)
		) {$charset_collate};";

		// ------------------------------------------------------------------
		// Table: uls_logs
		// ------------------------------------------------------------------
		$table_logs = $wpdb->prefix . 'uls_logs';
		$sql_logs   = "CREATE TABLE {$table_logs} (
			id             BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			license_id     BIGINT(20) UNSIGNED NULL DEFAULT NULL,
			action         VARCHAR(50)         NOT NULL DEFAULT '',
			domain         VARCHAR(500)        NOT NULL DEFAULT '',
			ip_address     VARCHAR(45)         NOT NULL DEFAULT '',
			request_data   LONGTEXT            NULL DEFAULT NULL,
			response_data  LONGTEXT            NULL DEFAULT NULL,
			created_at     DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY license_id (license_id),
			KEY action (action),
			KEY created_at (created_at)
		) {$charset_collate};";

		dbDelta( $sql_licenses );
		dbDelta( $sql_activations );
		dbDelta( $sql_logs );

		update_option( 'uls_db_version', self::DB_VERSION );
	}

	/**
	 * Plugin deactivation hook – deliberately a no-op to preserve all data.
	 */
	public static function on_deactivation() {
		// Intentionally empty: data is preserved on deactivation.
	}

	// -------------------------------------------------------------------------
	// License CRUD
	// -------------------------------------------------------------------------

	/**
	 * Retrieve a license row by purchase code.
	 *
	 * @param string $purchase_code The UUID purchase code.
	 * @return object|null Database row object or null if not found.
	 */
	public static function get_license_by_code( $purchase_code ) {
		global $wpdb;
		$table = $wpdb->prefix . 'uls_licenses';
		return $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE purchase_code = %s LIMIT 1",
				$purchase_code
			)
		);
	}

	/**
	 * Retrieve a license row by its primary key.
	 *
	 * @param int $id License ID.
	 * @return object|null Database row object or null if not found.
	 */
	public static function get_license_by_id( $id ) {
		global $wpdb;
		$table = $wpdb->prefix . 'uls_licenses';
		return $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE id = %d LIMIT 1",
				absint( $id )
			)
		);
	}

	/**
	 * Insert a new license record.
	 *
	 * @param array $data Associative array of column => value pairs.
	 * @return int|false Inserted row ID or false on failure.
	 */
	public static function create_license( $data ) {
		global $wpdb;
		$table = $wpdb->prefix . 'uls_licenses';

		$defaults = array(
			'purchase_code'   => '',
			'buyer_name'      => '',
			'buyer_email'     => '',
			'product_id'      => defined( 'ULS_ITEM_ID' ) ? ULS_ITEM_ID : '',
			'license_type'    => 'regular',
			'max_domains'     => 1,
			'status'          => 'active',
			'support_until'   => null,
			'purchase_date'   => null,
			'envato_verified' => 0,
			'notes'           => '',
			'created_at'      => current_time( 'mysql' ),
			'updated_at'      => current_time( 'mysql' ),
		);

		$data   = wp_parse_args( $data, $defaults );
		$result = $wpdb->insert( $table, $data );

		return $result ? $wpdb->insert_id : false;
	}

	/**
	 * Update an existing license record.
	 *
	 * @param int   $id   License primary key.
	 * @param array $data Column => value pairs to update.
	 * @return bool True on success, false on failure.
	 */
	public static function update_license( $id, $data ) {
		global $wpdb;
		$table = $wpdb->prefix . 'uls_licenses';

		$data['updated_at'] = current_time( 'mysql' );

		$result = $wpdb->update(
			$table,
			$data,
			array( 'id' => absint( $id ) )
		);

		return false !== $result;
	}

	// -------------------------------------------------------------------------
	// Activation CRUD
	// -------------------------------------------------------------------------

	/**
	 * Count active activations for a license.
	 *
	 * @param int $license_id License ID.
	 * @return int Number of active activations.
	 */
	public static function get_active_activations( $license_id ) {
		global $wpdb;
		$table = $wpdb->prefix . 'uls_activations';
		return (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$table} WHERE license_id = %d AND status = 'active'",
				absint( $license_id )
			)
		);
	}

	/**
	 * Find an existing activation record for a license/domain pair.
	 *
	 * @param int    $license_id License ID.
	 * @param string $domain     Sanitised domain string.
	 * @return object|null Activation row or null.
	 */
	public static function get_activation_by_domain( $license_id, $domain ) {
		global $wpdb;
		$table = $wpdb->prefix . 'uls_activations';
		return $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE license_id = %d AND domain = %s ORDER BY activation_date DESC LIMIT 1",
				absint( $license_id ),
				$domain
			)
		);
	}

	/**
	 * Find the active activation record for a license/domain pair.
	 *
	 * @param int    $license_id License ID.
	 * @param string $domain     Sanitised domain string.
	 * @return object|null Active activation row or null.
	 */
	public static function get_active_activation_by_domain( $license_id, $domain ) {
		global $wpdb;
		$table = $wpdb->prefix . 'uls_activations';
		return $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE license_id = %d AND domain = %s AND status = 'active' LIMIT 1",
				absint( $license_id ),
				$domain
			)
		);
	}

	/**
	 * Insert a new activation record.
	 *
	 * @param array $data Column => value pairs.
	 * @return int|false Inserted row ID or false on failure.
	 */
	public static function create_activation( $data ) {
		global $wpdb;
		$table = $wpdb->prefix . 'uls_activations';

		$defaults = array(
			'license_id'      => 0,
			'domain'          => '',
			'site_url'        => '',
			'activation_date' => current_time( 'mysql' ),
			'ip_address'      => '',
			'wp_version'      => '',
			'plugin_version'  => '',
			'status'          => 'active',
		);

		$data   = wp_parse_args( $data, $defaults );
		$result = $wpdb->insert( $table, $data );

		return $result ? $wpdb->insert_id : false;
	}

	/**
	 * Update an activation record.
	 *
	 * @param int   $id   Activation primary key.
	 * @param array $data Column => value pairs to update.
	 * @return bool True on success, false on failure.
	 */
	public static function update_activation( $id, $data ) {
		global $wpdb;
		$table  = $wpdb->prefix . 'uls_activations';
		$result = $wpdb->update(
			$table,
			$data,
			array( 'id' => absint( $id ) )
		);
		return false !== $result;
	}

	/**
	 * Set all active activations for a license to inactive.
	 *
	 * @param int $license_id License ID.
	 * @return int|false Number of rows affected or false on failure.
	 */
	public static function deactivate_all_for_license( $license_id ) {
		global $wpdb;
		$table = $wpdb->prefix . 'uls_activations';
		return $wpdb->update(
			$table,
			array(
				'status'             => 'inactive',
				'deactivation_date'  => current_time( 'mysql' ),
			),
			array(
				'license_id' => absint( $license_id ),
				'status'     => 'active',
			)
		);
	}

	// -------------------------------------------------------------------------
	// Logging
	// -------------------------------------------------------------------------

	/**
	 * Insert a log entry.
	 *
	 * @param array $data Column => value pairs.
	 * @return int|false Inserted row ID or false on failure.
	 */
	public static function log_request( $data ) {
		global $wpdb;
		$table = $wpdb->prefix . 'uls_logs';

		$defaults = array(
			'license_id'    => null,
			'action'        => '',
			'domain'        => '',
			'ip_address'    => '',
			'request_data'  => '',
			'response_data' => '',
			'created_at'    => current_time( 'mysql' ),
		);

		$data   = wp_parse_args( $data, $defaults );
		$result = $wpdb->insert( $table, $data );

		return $result ? $wpdb->insert_id : false;
	}

	/**
	 * Delete all log entries.
	 *
	 * @return int|false Number of rows deleted or false on failure.
	 */
	public static function clear_logs() {
		global $wpdb;
		$table = $wpdb->prefix . 'uls_logs';
		return $wpdb->query( "TRUNCATE TABLE {$table}" );
	}

	// -------------------------------------------------------------------------
	// Listing / stats
	// -------------------------------------------------------------------------

	/**
	 * Get a paginated list of licenses, optionally filtered.
	 *
	 * @param array $args {
	 *     Optional. Query arguments.
	 *     @type int    $page      Current page (1-based). Default 1.
	 *     @type int    $per_page  Rows per page. Default 20.
	 *     @type string $search    Search term (purchase code / email / buyer).
	 *     @type string $status    Filter by status.
	 *     @type string $orderby   Column to sort by. Default 'created_at'.
	 *     @type string $order     ASC or DESC. Default 'DESC'.
	 * }
	 * @return array {
	 *     @type array $licenses   Array of license row objects.
	 *     @type int   $total      Total number of matching records.
	 *     @type int   $pages      Total number of pages.
	 * }
	 */
	public static function get_all_licenses( $args = array() ) {
		global $wpdb;
		$table = $wpdb->prefix . 'uls_licenses';

		$defaults = array(
			'page'     => 1,
			'per_page' => 20,
			'search'   => '',
			'status'   => '',
			'orderby'  => 'created_at',
			'order'    => 'DESC',
		);
		$args = wp_parse_args( $args, $defaults );

		// Whitelist orderby and order.
		$allowed_orderby = array( 'id', 'purchase_code', 'buyer_name', 'buyer_email', 'status', 'created_at', 'purchase_date', 'support_until' );
		$orderby         = in_array( $args['orderby'], $allowed_orderby, true ) ? $args['orderby'] : 'created_at';
		$order           = 'ASC' === strtoupper( $args['order'] ) ? 'ASC' : 'DESC';

		$where  = array( '1=1' );
		$params = array();

		if ( ! empty( $args['search'] ) ) {
			$like     = '%' . $wpdb->esc_like( $args['search'] ) . '%';
			$where[]  = '(purchase_code LIKE %s OR buyer_name LIKE %s OR buyer_email LIKE %s)';
			$params[] = $like;
			$params[] = $like;
			$params[] = $like;
		}

		if ( ! empty( $args['status'] ) ) {
			$where[]  = 'status = %s';
			$params[] = $args['status'];
		}

		$where_clause = implode( ' AND ', $where );

		// Total count.
		$count_sql = "SELECT COUNT(*) FROM {$table} WHERE {$where_clause}";
		$total     = ! empty( $params )
			? (int) $wpdb->get_var( $wpdb->prepare( $count_sql, $params ) )
			: (int) $wpdb->get_var( $count_sql );

		// Paginated rows.
		$offset  = ( absint( $args['page'] ) - 1 ) * absint( $args['per_page'] );
		$row_sql = "SELECT * FROM {$table} WHERE {$where_clause} ORDER BY {$orderby} {$order} LIMIT %d OFFSET %d";

		$row_params   = $params;
		$row_params[] = absint( $args['per_page'] );
		$row_params[] = $offset;

		$licenses = ! empty( $row_params )
			? $wpdb->get_results( $wpdb->prepare( $row_sql, $row_params ) )
			: $wpdb->get_results( $row_sql );

		return array(
			'licenses' => $licenses ? $licenses : array(),
			'total'    => $total,
			'pages'    => max( 1, (int) ceil( $total / absint( $args['per_page'] ) ) ),
		);
	}

	/**
	 * Retrieve recent activations with joined license info.
	 *
	 * @param int $limit Maximum number of rows to return.
	 * @return array Array of stdClass row objects.
	 */
	public static function get_recent_activations( $limit = 10 ) {
		global $wpdb;
		$act_table = $wpdb->prefix . 'uls_activations';
		$lic_table = $wpdb->prefix . 'uls_licenses';

		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT a.*, l.purchase_code, l.buyer_name, l.buyer_email
				 FROM {$act_table} a
				 LEFT JOIN {$lic_table} l ON a.license_id = l.id
				 ORDER BY a.activation_date DESC
				 LIMIT %d",
				absint( $limit )
			)
		);
	}

	/**
	 * Retrieve recent log entries.
	 *
	 * @param int $limit Maximum number of rows to return.
	 * @return array Array of stdClass row objects.
	 */
	public static function get_recent_logs( $limit = 10 ) {
		global $wpdb;
		$table = $wpdb->prefix . 'uls_logs';
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$table} ORDER BY created_at DESC LIMIT %d",
				absint( $limit )
			)
		);
	}

	/**
	 * Return aggregate statistics.
	 *
	 * @return array {
	 *     @type int $total_licenses      Total license records.
	 *     @type int $active_licenses     Licenses with status = 'active'.
	 *     @type int $banned_licenses     Licenses with status = 'banned'.
	 *     @type int $total_activations   Total activation records.
	 *     @type int $active_activations  Active activation records.
	 *     @type int $recent_activations  Activations in the last 7 days.
	 * }
	 */
	public static function get_stats() {
		global $wpdb;
		$lic_table = $wpdb->prefix . 'uls_licenses';
		$act_table = $wpdb->prefix . 'uls_activations';

		$total_licenses     = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$lic_table}" );
		$active_licenses    = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$lic_table} WHERE status = 'active'" );
		$banned_licenses    = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$lic_table} WHERE status = 'banned'" );
		$total_activations  = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$act_table}" );
		$active_activations = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$act_table} WHERE status = 'active'" );
		$recent_activations = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$act_table} WHERE activation_date >= %s",
				gmdate( 'Y-m-d H:i:s', strtotime( '-7 days' ) )
			)
		);

		return compact(
			'total_licenses',
			'active_licenses',
			'banned_licenses',
			'total_activations',
			'active_activations',
			'recent_activations'
		);
	}

	/**
	 * Export all licenses as an array suitable for CSV output.
	 *
	 * @return array Array of stdClass row objects.
	 */
	public static function export_licenses() {
		global $wpdb;
		$table = $wpdb->prefix . 'uls_licenses';
		return $wpdb->get_results( "SELECT * FROM {$table} ORDER BY created_at ASC" );
	}
}
