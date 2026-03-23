<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 *
 * Handles generic Front functionality
 *
 * @package Ultimate WooCommerce Auction PRO
 * @author Nitesh Singh
 * @since 1.0
 *
 */
 
function uwa_won_auctions_download_csv() {

	if (ob_get_length() > 0) {
        ob_clean();
    }
	ob_start();

	$sitename = sanitize_key( get_bloginfo( 'name' ) );	
	if (!empty( $sitename )) {
		$sitename .= '-';
	}		
		$filename = $sitename . 'won-auctions-' . date( 'Y-m-d-H-i-s' ) . '.csv';

		$header_row = array(
						__('Product title', 'woo_ua'),
						__('Winning bid', 'woo_ua'),
						__('End date', 'woo_ua'),
						__('Winner username', 'woo_ua'),
					);

		global $wpdb, $woocommerce, $product, $sitepress;

		$data_rows = array();
		$curr_user_id = get_current_user_id();
		$table = $wpdb->prefix . "woo_ua_auction_log";
	   	$query = $wpdb->prepare("SELECT auction_id, MAX(bid) as max_userbid FROM $table  WHERE userid = %d GROUP by auction_id ORDER by date DESC", $curr_user_id);
	   	$my_auctions = $wpdb->get_results( $query );

	   	foreach ( $my_auctions as $my_auction ) {

			   $product_id = $my_auction->auction_id;
			   $product = wc_get_product( $product_id );

			   if (is_object($product)) {

					if ( method_exists( $product, 'get_type') && $product->get_type() == 'auction' ) {

						if ( $curr_user_id == $product->get_uwa_auction_current_bider() && 
							$product->get_uwa_auction_expired() == '2') {

					        $product_name = get_the_title( $product_id );
					        $maxbid = $my_auction->max_userbid;

					   		/* $currency = get_option('woocommerce_currency');
							$woocommerce_currency = html_entity_decode($currency); */

							$currency = get_option('woocommerce_currency');
							$woocommerce_currency_symbol = get_woocommerce_currency_symbol($currency);
							$woocommerce_currency_symbol_value = html_entity_decode($woocommerce_currency_symbol);

							if ($maxbid) {
								$maxbid_value = $woocommerce_currency_symbol_value . $maxbid;
							} else {
								$maxbid_value = '';
							}

					        $end_date = $product->get_uwa_auction_end_dates();
					        $current_bidder = $product->get_uwa_auction_current_bider();

					        $bidder_username = "";
					        if ($current_bidder) {		
								$obj_user = get_userdata($current_bidder);	
								if ($obj_user) {
									$bidder_username = $obj_user->user_login;
								}
							}	
				    
				    		// Restore original Post Data
							wp_reset_postdata();
							$row = array(
								$product_name,
								$maxbid_value,
								$end_date,
								$bidder_username,
							);
							$data_rows[] = $row;

						}
						
					} /* end of if method exists  */
				}
				
			} /* end of foreach */


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
