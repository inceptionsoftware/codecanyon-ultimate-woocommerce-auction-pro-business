<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 *
 * Handles generic Backend functionality
 * 
 * @package Ultimate WooCommerce Auction PRO
 * @author Nitesh Singh
 * @since 1.0
 *
 */
 
function uwa_auctions_download_csv() {

	// Check for current user privileges
	if ( ! current_user_can( 'manage_options' ) ) {
		return false;
	}

	// Check if we are in WP-Admin
	if ( ! is_admin() ) {
		return false;
	}

	// Verify nonce
	if ( ! isset( $_REQUEST['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_REQUEST['_wpnonce'] ) ), 'uwa_download_csv' ) ) {
		wp_die( esc_html__( 'Security check failed.', 'woo_ua' ) );
	}

	if (ob_get_length() > 0) {
		ob_clean();
    }

	ob_start();

	$sitename = sanitize_key( get_bloginfo( 'name' ) );

	if ( ! empty( $sitename ) ) {
		$sitename .= '-';
	}
		
		$filename = $sitename . 'expiry-auctions-' . gmdate( 'Y-m-d-H-i-s' ) . '.csv';
		$header_row = array(						
						__('Auction Name', 'woo_ua'),
						__('Highest Bidder Name', 'woo_ua'),
						__('Highest Bidder Email', 'woo_ua'),
						__('Highest Bid', 'woo_ua'),
						__('Second Highest Bidder', 'woo_ua'),
						__('Second Highest Bidder Email', 'woo_ua'),
						__('Second Highest Bid', 'woo_ua'),
						__('Third Highest Bidder', 'woo_ua'),
						__('Third Highest Bidder Email', 'woo_ua'),
						__('Third Highest Bid', 'woo_ua'),
						__('Auction End Date', 'woo_ua'),
						__('Expiry Reason', 'woo_ua'),
					);

		global $wpdb;

		$data_rows = array();
		$curr_user_id = get_current_user_id();

		$args = array(
			'post_type'	=> 'product',
			'post_status' => 'publish',
			'posts_per_page' => '-1',
			'tax_query' => array(array('taxonomy' => 'product_type' , 'field' => 'slug', 'terms' => 'auction')),
			'auction_arhive' => TRUE,
		 );	

		if ( ! empty( $_REQUEST['users_auctions'] ) && sanitize_text_field( wp_unslash( $_REQUEST['users_auctions'] ) ) === 'true' ) {
			$args['author__not_in'] = $curr_user_id;
		} else {
			$args['author'] = $curr_user_id;
		}
		
	 	$args['meta_query']= array(
					'relation' => 'AND',
						array(			     
							'key' => 'woo_ua_auction_closed',
							'value' => array('1','2','3','4'),
							'compare' => 'IN',
						),							
					);
		
	
		$query = new WP_Query( $args );
		if ( $query->have_posts() ) {
			while ( $query->have_posts() ) {
				$query->the_post();
					$auction_ID = get_the_ID();
					$ending_date = get_post_meta(get_the_ID(), 'woo_ua_auction_end_date', true);
					$auction_name = get_the_title();
					$highest_bid = $wpdb->get_var( $wpdb->prepare( 'SELECT bid FROM %i WHERE auction_id = %d ORDER BY `bid` DESC LIMIT 1', $wpdb->prefix.'woo_ua_auction_log', $auction_ID ) );

					$currency = get_option('woocommerce_currency');
					$woocommerce_currency_symbol = get_woocommerce_currency_symbol($currency);
					$woocommerce_currency_symbol_value = html_entity_decode($woocommerce_currency_symbol);
					
					if ($highest_bid) {
						$highest_bid_value = $woocommerce_currency_symbol_value . $highest_bid;
					} else {
						$highest_bid_value = '';
					}

					$second_highest_bid = $wpdb->get_var( $wpdb->prepare( 'SELECT bid FROM %i WHERE auction_id = %d ORDER BY `bid` DESC LIMIT 1,2', $wpdb->prefix.'woo_ua_auction_log', $auction_ID ) );

					if ($second_highest_bid) {
						$second_highest_bid_value = $woocommerce_currency_symbol_value . $second_highest_bid;
					} else {
						$second_highest_bid_value = '';
					}


					$second_highest_bidder = $wpdb->get_var( $wpdb->prepare( 'SELECT userid FROM %i WHERE auction_id = %d ORDER BY `bid` DESC LIMIT 1,2', $wpdb->prefix.'woo_ua_auction_log', $auction_ID ) );

					$third_highest_bid = $wpdb->get_var( $wpdb->prepare( 'SELECT bid FROM %i WHERE auction_id = %d ORDER BY `bid` DESC LIMIT 2,3', $wpdb->prefix.'woo_ua_auction_log', $auction_ID ) );

					if ($third_highest_bid) {
						$third_highest_bid_value = $woocommerce_currency_symbol_value . $third_highest_bid;
					} else {
						$third_highest_bid_value = '';
					}


					$third_highest_bidder = $wpdb->get_var( $wpdb->prepare( 'SELECT userid FROM %i WHERE auction_id = %d ORDER BY `bid` DESC LIMIT 2,3', $wpdb->prefix.'woo_ua_auction_log', $auction_ID ) );

					$row['expiry_reason'] = "";
					$bidder_name = "";
					$bidder_email = "";
					$fail_reason = get_post_meta($auction_ID, 'woo_ua_auction_fail_reason', true); 
					$reason_closed = get_post_meta($auction_ID, 'woo_ua_auction_closed', true); 
					$current_bidder = get_post_meta($auction_ID, 'woo_ua_auction_current_bider', true);
					$order_id = get_post_meta($auction_ID, 'woo_ua_order_id', true);

					if ($current_bidder) {
						$obj_user = get_userdata($current_bidder);	
						$user_name = "";
						if ($obj_user) { 
							
							$bidder_name = $obj_user->display_name;
							if (empty($bidder_name)) {	
								$bidder_name = $obj_user->user_login;
							}

							$bidder_email = $obj_user->user_email;
						}

					}

					$second_bidder_email = "";
					$second_bidder_name = "";
					if ($second_highest_bidder) {		
						$obj_user = get_userdata($second_highest_bidder);	
						$user_name = "";
						if ($obj_user) {

							$second_bidder_name = $obj_user->display_name;
							if (empty($second_bidder_name)) {	
								$second_bidder_name = $obj_user->user_login;
							}

							$second_bidder_email = $obj_user->user_email;
						}

					}

					$third_bidder_email = "";
					$third_bidder_name = "";
					if ($third_highest_bidder) {		
						$obj_user = get_userdata($third_highest_bidder);	
						$user_name = "";
						if ($obj_user) {

							$third_bidder_name = $obj_user->display_name;
							if (empty($third_bidder_name)) {	
								$third_bidder_name = $obj_user->user_login;
							}							

							$third_bidder_email = $obj_user->user_email;
						}

					}

					if ($fail_reason == 1) {	
					
						$row['expiry_reason'] = __('No Bid', 'woo_ua');
						
					} elseif($fail_reason == 2) {
						
						$row['expiry_reason'] = __('Reserve Not Met', 'woo_ua');

					} elseif($reason_closed == 3) {

						$row['expiry_reason'] = __('Sold', 'woo_ua');

						if ( $order_id ) {							
							$row['expiry_reason'] .= ', '.__('Order ID:', 'woo_ua').' '.$order_id;
						}
					} else {

						$row['expiry_reason'] .= __('Won', 'woo_ua').' - ';
						$row['expiry_reason'] .= __('Highest bidder was', 'woo_ua').' ';
						$row['expiry_reason'] .= $bidder_name;					
						if ( $order_id ){							
							$row['expiry_reason'] .= ', '.__('Order ID:', 'woo_ua').' '.$order_id;
						}
					}

				// Restore original Post Data
				wp_reset_postdata();
				$row = array(
					$auction_name,
					$bidder_name,
					$bidder_email,
					$highest_bid_value,
					$second_bidder_name,
					$second_bidder_email,
					$second_highest_bid_value,
					$third_bidder_name,
					$third_bidder_email,
					$third_highest_bid_value,
					$ending_date,
					$row['expiry_reason'],
				);
				$data_rows[] = $row;
    		}

		} else {			
			echo esc_html__( 'No Posts Found', 'woo_ua' );
		}
		
	$__csvoutput = @fopen( 'php://output', 'w' );
	fprintf( $__csvoutput, chr(0xEF) . chr(0xBB) . chr(0xBF) );
	
	header('Content-Description: File Transfer');
	header('Content-Encoding: UTF-8');
    header('Content-Type: text/csv; charset=utf-8');
	header("Content-Disposition: attachment; filename={$filename}");
	header('Content-Transfer-Encoding: binary');
	header('Expires: 0');
	header('Cache-Control: no-cache, must-revalidate');
	header('Pragma: public');
	fputcsv( $__csvoutput, $header_row );
	foreach ( $data_rows as $data_row ) {
		fputcsv( $__csvoutput, $data_row );
	}
	fclose( $__csvoutput );
	ob_end_flush();
	die();
}
