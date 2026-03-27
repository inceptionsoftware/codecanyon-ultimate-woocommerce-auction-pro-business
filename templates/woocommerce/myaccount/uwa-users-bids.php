<?php

/**
 * My auctions tab list
 *
 * @package Ultimate WooCommerce Auction PRO
 * @author Nitesh Singh
 * @since 1.0
 *
 */

if (!defined('ABSPATH')) {
	exit;
}

	$user_id  = get_current_user_id();

	// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Read-only tab display, no data mutation.
	if ( isset( $_GET['bid_status'] ) ) {
		$active_tab = sanitize_text_field( wp_unslash( $_GET['bid_status'] ) );

	} elseif ( isset( $_GET['display'] ) ) {
		$active_tab = sanitize_text_field( wp_unslash( $_GET['display'] ) );

	} else {
	 	$active_tab = 'active';
	}
	// phpcs:enable WordPress.Security.NonceVerification.Recommended

	$my_auction_page_url = wc_get_endpoint_url('uwa-auctions');
	$active_bid_url      = esc_url( add_query_arg( array( 'bid_status' => 'active' ), $my_auction_page_url ) );
	$active_won_url      = esc_url( add_query_arg( array( 'bid_status' => 'won' ), $my_auction_page_url ) );
	$active_lost_url     = esc_url( add_query_arg( array( 'bid_status' => 'lost' ), $my_auction_page_url ) );
	$active_watchlist_url = esc_url( add_query_arg( array( 'display' => 'watchlist' ), $my_auction_page_url ) );


?>

	<ul class="uwa-user-bid-counts subsubsub">

        <li class="<?php echo $active_tab === 'active' ? 'active' : ''; ?>">

            <a href="<?php echo $active_bid_url; ?>">
                	<?php esc_html_e( 'Active Bids', 'woo_ua' ); ?></a> (<?php echo absint( uwa_front_user_bids_count( $user_id, 'active' ) ); ?>) |
        </li>
		<li class="<?php echo $active_tab === 'won' ? 'active' : ''; ?>">
			<a href="<?php echo $active_won_url; ?>">
				<?php esc_html_e( 'Won Bids', 'woo_ua' ); ?></a> (<?php echo absint( uwa_front_user_bids_count( $user_id, 'won' ) ); ?>) |
        </li>
		<li class="<?php echo $active_tab === 'lost' ? 'active' : ''; ?>">
		   <a href="<?php echo $active_lost_url; ?>">
		   		<?php esc_html_e( 'Lost Bids', 'woo_ua' ); ?></a> (<?php echo absint( uwa_front_user_bids_count( $user_id, 'lost' ) ); ?>) |
		</li>
		<li class="<?php echo $active_tab === 'watchlist' ? 'active' : ''; ?>">
		   <a href="<?php echo $active_watchlist_url; ?>">
		   	<?php esc_html_e( 'Watchlist', 'woo_ua' ); ?></a> (<?php echo absint( uwa_front_user_watchlist_count( $user_id ) ); ?>)
		</li>
	</ul>

<?php

	if( $active_tab == 'active' ) {
		$bid_status = 'active';
		echo wp_kses_post( uwa_front_user_bid_list( $user_id, 'active' ) );
	}
	if( $active_tab == 'won' ) {
		$bid_status = 'won';

		$csv_export_url = esc_url(
			add_query_arg(
				array(
					'bid_status' => 'won',
					'action'     => 'uwa_user_download_csv',
					'_wpnonce'   => wp_create_nonce( 'uwa_user_download_csv' ),
				),
				get_permalink( get_option( 'woocommerce_myaccount_page_id' ) ) . 'uwa-auctions/'
			)
		);
		?>

		<div style="float:right;margin-right: 10px;">
			<div class="ex-csv-btn">
				<a style="border: 1px solid #2271b1;" href="<?php echo $csv_export_url; ?>" class="uwa-highlight-btn highlight-btn-disabled"><?php esc_html_e( 'Export Won Auctions CSV', 'woo_ua' ); ?></a>

			</div>
		</div>

		<?php

		echo wp_kses_post( uwa_front_user_bid_list( $user_id, 'won' ) );
	}
	if( $active_tab == 'lost' ) {
		$bid_status = 'lost';
		echo wp_kses_post( uwa_front_user_bid_list( $user_id, 'lost' ) );
	}

	if( $active_tab == 'watchlist' ) {
		echo wp_kses_post( uwa_front_user_watchlist( $user_id ) );
	}
	if( $active_tab == 'settings' ) {
		echo wp_kses_post( uwa_front_user_auction_settings( $user_id ) );
	}
?>
