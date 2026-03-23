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

$product = null;
$product_id = 0;
$auction_url = '';
$user_name = '';
$auction_title = '';
$auction_bid_value = '';
$uwa_silent = '';
$uwa_proxy = '';
$user_id = 0;
$user_type = '';

/* ---------- LIVE EMAIL ---------- */
if ( is_array( $email->object ) && isset( $email->object['product'] ) ) {

    $user_type   = $email->object['user_type'];
    $product     = $email->object['product'];
    $auction_url = $email->object['url_product'];
    $user_name   = $email->object['user_name'];
    $auction_title = $product->get_title();
    $auction_bid_value = wc_price( $product->get_uwa_current_bid() );
    $uwa_silent = $product->get_uwa_auction_silent();
    $uwa_proxy  = $product->get_uwa_auction_proxy();
    $product_id = $product->get_id();
    $user_id    = $email->object['placebid_userid'];

    if ( $uwa_silent == 'yes' ) {
        $auction_bid_value = wc_price( $product->get_uwa_last_bid() );
    }

    if ( $uwa_proxy == "yes" ) {
        $auction_type = $product->get_uwa_auction_type();
        if ( $auction_type == "normal" || $auction_type == "reverse" ) {
            $bid_sql = $wpdb->prepare(
                "SELECT bid FROM {$wpdb->prefix}woo_ua_auction_log 
                WHERE auction_id = %d AND userid = %d 
                ORDER BY id DESC LIMIT 1",
                $product_id,
                $user_id
            );
            $user_last_bid = $wpdb->get_var( $bid_sql );
            $auction_bid_value = wc_price( $user_last_bid );
        }
    }

/* ---------- PREVIEW MODE (WC_Order) ---------- */
} elseif ( is_a( $email->object, 'WC_Order' ) ) {

    $order = $email->object;
    $item = current( $order->get_items() );
    $product = $item ? $item->get_product() : null;
    $product_id = $product ? $product->get_id() : 0;
    $auction_url = get_permalink( $product_id );
    $user_name = $order->get_formatted_billing_full_name();
    $auction_title = $product ? $product->get_title() : __( 'Auction Product', 'woo_ua' );
    $auction_bid_value = wc_price( $item ? $item->get_total() : 0 );
    $user_id = $order->get_user_id();
    $user_type = 'bidder'; // or 'admin' if previewing admin email
    $uwa_silent = get_post_meta( $product_id, '_uwa_auction_silent', true );
    $uwa_proxy = get_post_meta( $product_id, '_uwa_auction_proxy', true );
}

echo $email_heading . "</br>";

if ( $user_type === "bidder" ) {

    printf( __( "Hi %s,", 'woo_ua' ), $user_name );
    echo "</br>";

    $cur_userid = get_current_user_id();
    $sentmail = get_user_meta( $cur_userid, "uwa_samemaxbid_sent_mail", true );

    if ( $sentmail == "yes" ) {
        printf(
            __( "An user has placed a bid which matched your maximum bid and due to this, we have placed a new bid on your behalf with an amount same as your 'Maximum Bid' and declined the other user's bid. on <a href='%s'>%s</a>.", "woo_ua" ),
            $auction_url,
            $auction_title
        );
        delete_user_meta( $cur_userid, "uwa_samemaxbid_sent_mail" );
    } else {
        printf(
            __( 'You recently placed a bid on <a href="%s">%s</a>.', 'woo_ua' ),
            $auction_url,
            $auction_title
        );
    }

    echo "</br>";
    printf( __( "Bid Value %s.", 'woo_ua' ), $auction_bid_value );
    echo "</br>";

    if ( $uwa_proxy == 'yes' && method_exists( $product, 'get_uwa_auction_max_current_bider' ) && get_current_user_id() == $product->get_uwa_auction_max_current_bider() ) {
        $max_bid_price = $product->get_uwa_auction_max_bid();
        $formatted_max_bid_price = $max_bid_price ? wc_price( $max_bid_price ) : " --- ";
        printf( __( "Your maximum bid is  %s.", 'woo_ua' ), $formatted_max_bid_price );
        echo "</br>";
    }
}

echo apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ) );