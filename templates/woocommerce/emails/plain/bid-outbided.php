<?php

/**
 * User placed a bid email notification (plain)
 * 
 * @package Ultimate WooCommerce Auction PRO
 * @author Nitesh Singh 
 * @since 1.0  
 *
 */

if (!defined('ABSPATH')) {
    exit;
}

global $woocommerce;

$product               = null;
$auction_url           = '';
$user_name             = '';
$auction_title         = '';
$auction_bid_value     = '';
$uwa_silent            = '';
$uwa_silent_last_bid   = 0;
$uwa_silent_outbid_email_cprice = get_option( 'uwa_silent_outbid_email_cprice', 'no' );

/* ---------- LIVE EMAIL ---------- */
if ( is_array( $email->object ) && isset( $email->object['product'] ) ) {

	$product           = $email->object['product'];
	$auction_url       = esc_url( $email->object['url_product'] );
	$user_name         = esc_html( $email->object['user_name'] );
	$auction_title     = $product ? $product->get_title() : '';
	$auction_bid_value = $product ? wc_price( $product->get_uwa_current_bid() ) : '';
	$uwa_silent        = $product ? $product->get_uwa_auction_silent() : '';

	// Handle silent auction price formatting
	if ( $uwa_silent === 'yes' ) {
		if ( $uwa_silent_outbid_email_cprice === 'no' ) {
			$auction_bid_value = $product ? $product->get_price_html() : '';
		} else {
			$last_bid = $product ? $product->get_uwa_last_bid() : 0;
			$auction_bid_value = wc_price( $last_bid );
		}
	}

/* ---------- PREVIEW MODE ---------- */
} else {
	$order   = is_a( $email->object, 'WC_Order' ) ? $email->object : null;
	$item    = $order ? current( $order->get_items() ) : null;
	$product = $item ? $item->get_product() : null;

	$auction_url       = $product ? get_permalink( $product->get_id() ) : home_url();
	$user_name         = $order ? $order->get_formatted_billing_full_name() : '';
	$auction_title     = $product ? $product->get_title() : __( 'Auction Product', 'woo_ua' );
	$uwa_silent        = $product ? get_post_meta( $product->get_id(), '_uwa_auction_silent', true ) : '';
	$auction_bid_value = wc_price( $item ? $item->get_total() : 0 );

	if ( $uwa_silent === 'yes' ) {
		if ( $uwa_silent_outbid_email_cprice === 'no' ) {
			$auction_bid_value = $product ? $product->get_price_html() : '';
		} else {
			$last_bid = get_post_meta( $product->get_id(), '_uwa_last_bid', true );
			$auction_bid_value = wc_price( $last_bid );
		}
	}
}

/* ---------- EMAIL OUTPUT ---------- */
echo esc_html( $email_heading ) . "<br>";

printf( __( "Hi %s,", 'woo_ua' ), esc_html( $user_name ) );
echo "<br>";

printf(
	__( 'You have been outbid on the product <a href="%s">%s</a>.', 'woo_ua' ),
	esc_url( $auction_url ),
	esc_html( $auction_title )
);
echo "<br>";

printf( __( "Bid Value %s.", 'woo_ua' ), $auction_bid_value );
echo "<br>";

printf(
	__( 'If you want to bid a new amount, click here <a href="%s">%s</a>.', 'woo_ua' ),
	esc_url( $auction_url ),
	esc_html( $auction_title )
);
echo "<br>";

echo apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ) );