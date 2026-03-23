<?php

/**
 * Bidder placed a bid email notification (plain)
 * 
 * @package Ultimate WooCommerce Auction PRO
 * @author Nitesh Singh 
 * @since 1.0  
 *
 */

if (!defined('ABSPATH')) {
    exit;
}

global $woocommerce, $wpdb;

$product            = null;
$product_id         = 0;
$user_name          = '';
$user_type          = '';
$auction_title      = '';
$auction_url        = '';
$auction_bid_value  = '';
$uwa_silent         = '';
$uwa_proxy          = '';
$user_id            = 0;

/* ---------- LIVE EMAIL MODE ---------- */
if ( is_array( $email->object ) && isset( $email->object['product'] ) ) {

	$product    = $email->object['product'];
	$product_id = $product->get_id();
	$user_name  = $email->object['user_name'];
	$user_type  = $email->object['user_type'];
	$auction_url = esc_url( $email->object['url_product'] );
	$auction_title = $product->get_title();
	$auction_bid_value = wc_price( $product->get_uwa_current_bid() );
	$uwa_silent = $product->get_uwa_auction_silent();
	$uwa_proxy  = $product->get_uwa_auction_proxy();
	$user_id    = absint( $email->object['placebid_userid'] );

	if ( $uwa_silent === 'yes' ) {
		$auction_bid_value = wc_price( $product->get_uwa_last_bid() );
	}

	if ( $uwa_proxy === "yes" ) {
		$auction_type = $product->get_uwa_auction_type();
		$last_bid = $wpdb->get_var( $wpdb->prepare(
			"SELECT bid FROM {$wpdb->prefix}woo_ua_auction_log WHERE auction_id = %d AND userid = %d ORDER BY id DESC LIMIT 1",
			$product_id, $user_id
		) );
		$auction_bid_value = wc_price( $last_bid );
	}

/* ---------- PREVIEW MODE (WC_Order) ---------- */
} elseif ( is_a( $email->object, 'WC_Order' ) ) {
	$order   = $email->object;
	$item    = current( $order->get_items() );
	$product = $item ? $item->get_product() : null;

	if ( $product ) {
		$product_id         = $product->get_id();
		$user_name          = $order->get_formatted_billing_full_name();
		$user_type          = 'admin'; // or 'bidder' if needed for testing
		$auction_url        = get_permalink( $product_id );
		$auction_title      = $product->get_title();
		$uwa_silent         = get_post_meta( $product_id, '_uwa_auction_silent', true );
		$uwa_proxy          = get_post_meta( $product_id, '_uwa_auction_proxy', true );
		$auction_bid_raw    = $item->get_total();

		if ( $uwa_silent === 'yes' ) {
			$auction_bid_raw = get_post_meta( $product_id, '_uwa_last_bid', true );
		}
		if ( $uwa_proxy === "yes" ) {
			$auction_bid_raw = get_post_meta( $product_id, '_uwa_last_bid', true );
		}

		$auction_bid_value = wc_price( $auction_bid_raw );
	}
}

/* ---------- EMAIL OUTPUT ---------- */
echo esc_html( $email_heading ) . "<br>";

if ( $user_type === "admin" && $product ) {
	printf( __( "Hi,", 'woo_ua' ) );
	echo "<br>";

	printf(
		__( 'A bid was placed on <a href="%s">%s</a>.', 'woo_ua' ),
		esc_url( $auction_url ),
		esc_html( $auction_title )
	);
	echo "<br>";

	printf( __( "Bid Value %s.", 'woo_ua' ), $auction_bid_value );
	echo "<br>";
}

echo apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ) );