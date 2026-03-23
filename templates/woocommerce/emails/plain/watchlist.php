<?php

/**
 * watchlist email (plain)
 * 
 * @package Ultimate WooCommerce Auction PRO
 * @author Nitesh Singh 
 * @since 1.0 
 *
 */

if (!defined('ABSPATH')) {
    exit;
}

/* ---------- LIVE & PREVIEW MODE SUPPORT ---------- */
global $woocommerce;

$product            = null;
$product_id         = 0;
$auction_url        = '';
$user_name          = '';
$auction_title      = '';
$auction_bid_value  = '';

/* ---------- LIVE EMAIL (array data) ---------- */
if ( is_array( $email->object ) && isset( $email->object['product'] ) ) {

	/* ───── YOUR ORIGINAL CODE – UNCHANGED ───── */
	$product      = $email->object['product'];
	$auction_url  = $email->object['url_product'];
	$user_name    = $email->object['user_name'];	
	$auction_title= $email->object['product_name'];

	$currentbid   = $product->get_uwa_current_bid();
	$auction_bid_value = wc_price( $currentbid );
	/* ─────────────────────────────────────────── */

/* ---------- PREVIEW MODE (WC_Order) ---------- */
} elseif ( is_a( $email->object, 'WC_Order' ) ) {

	$order   = $email->object;
	$item    = current( $order->get_items() );
	$product = $item ? $item->get_product() : null;

	if ( $product ) {
		$product_id        = $product->get_id();
		$auction_url       = get_permalink( $product_id );
		$user_name         = $order->get_formatted_billing_full_name();
		$auction_title     = $product->get_title();
		$currentbid        = $item ? $item->get_total() : 0;
		$auction_bid_value = wc_price( $currentbid );
	}
}

/* ---------- EMAIL OUTPUT ---------- */
echo $email_heading . "</br>";

printf( __( "Hi %s,", 'woo_ua' ), $user_name ); 
echo "</br>";

printf(
	__( 'A bid was placed on an auction product which was in your watchlist <a href="%s">%s</a>.', 'woo_ua' ),
	$auction_url,
	$auction_title
);
echo "</br>"; 

printf( __( "Bid Value %s.", 'woo_ua' ), $auction_bid_value );
echo "</br>";

echo apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ) );