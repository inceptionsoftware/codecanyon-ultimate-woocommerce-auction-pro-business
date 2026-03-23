<?php

/**
 * Admin deleted user bid notification (plain)
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

/* ---------- LIVE & PREVIEW MODE SUPPORT ---------- */
$product            = null;
$auction_title      = '';
$auction_url        = '';
$deleted_bid_value  = '';
$thumb_image        = '';
$user_name          = '';

/* ---------- LIVE EMAIL ---------- */
if ( is_array( $email->object ) && isset( $email->object['product'] ) ) {

	$product            = $email->object['product'];
	$auction_title      = isset( $email->object['product_name'] ) ? $email->object['product_name'] : '';
	$auction_url        = isset( $email->object['url_product'] ) ? esc_url( $email->object['url_product'] ) : '';
	$deleted_bid_value  = isset( $email->object['deleted_bid'] ) ? $email->object['deleted_bid'] : 0;
	$thumb_image        = $product ? $product->get_image( 'thumbnail' ) : '';
	$user_name          = isset( $email->object['user_name'] ) ? esc_html( $email->object['user_name'] ) : '';

/* ---------- PREVIEW MODE ---------- */
} else {

	$order   = is_a( $email->object, 'WC_Order' ) ? $email->object : null;
	$item    = $order ? current( $order->get_items() ) : null;
	$product = $item ? $item->get_product() : null;

	$auction_title     = $product ? $product->get_title() : __( 'Auction Product', 'woo_ua' );
	$auction_url       = $product ? get_permalink( $product->get_id() ) : home_url();
	$deleted_bid_value = $item ? $item->get_total() : 0;
	$thumb_image       = $product ? $product->get_image( 'thumbnail' ) : '';
	$user_name         = $order ? $order->get_formatted_billing_full_name() : '';
}

/* ---------- EMAIL CONTENT ---------- */
echo "\t\t\t";
echo $email_heading; /*  email header */
echo "\n\n\n";

printf( __( "Hi %s,", "woo_ua" ), esc_html( $user_name ) );
echo "\n\t";

printf( __( "You are receiving this email as auction owner has deleted your bid. Kindly contact admin/owner for further discussion.", "woo_ua" ) );
echo "\n\n";

printf( __( "Here are the details : ", "woo_ua" ) );
echo "\n";

/* Uncomment if you want to show image in plain text email */
/*
echo __( 'Image :', 'woo_ua' );
echo "\n";
echo $thumb_image;
*/

printf( __( "Product : %s", "woo_ua" ), esc_html( $auction_title ) );
echo "\n";

printf( __( "Deleted Bid : %s", "woo_ua" ), wc_price( $deleted_bid_value ) );
echo "\n\n\n\t\t\t";

echo apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ) );  /*  email footer */
?>