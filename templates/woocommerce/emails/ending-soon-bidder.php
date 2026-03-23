<?php

/**
 * Send Email to bidder when the bidder won the auction. (HTML)
 * 
 * @package Ultimate WooCommerce Auction PRO
 * @author Nitesh Singh 
 * @since 1.0  
 *
 */

if (!defined('ABSPATH')) {
    exit;
}

?>
<?php do_action( 'woocommerce_email_header', $email_heading, $email );

/* ------------------------------------------------------------------
   1)  GET THE PRODUCT OBJECT SAFELY  (live vs preview)
-------------------------------------------------------------------*/
$product             = null;
$product_id          = 0;
$product_base_currency = get_woocommerce_currency();
$args                = array( 'currency' => $product_base_currency );
$uwa_silent          = 'no';

# LIVE  (e‑mail triggered by plugin)
if ( isset( $email->productid ) && absint( $email->productid ) ) {

	$product_id = absint( $email->productid );

	// Load auction product correctly
	if ( function_exists( 'Woo_UA' ) && method_exists( Woo_UA(), 'uwa_get_product' ) ) {
		$product = Woo_UA()->uwa_get_product( $product_id );
	} else {
		$product = wc_get_product( $product_id );
	}

	if ( $product && method_exists( $product, 'uwa_aelia_get_base_currency' ) ) {
		$args['currency'] = $product->uwa_aelia_get_base_currency();
	}
	if ( $product && method_exists( $product, 'get_uwa_auction_silent' ) ) {
		$uwa_silent = $product->get_uwa_auction_silent();
	}

# PREVIEW  (WooCommerce e‑mail preview)
} elseif ( is_a( $email->object, 'WC_Order' ) ) {

	$order    = $email->object;
	$item     = current( $order->get_items() );
	$product  = $item ? $item->get_product() : null;
	$product_id = $product ? $product->get_id() : 0;

	if ( $product && method_exists( $product, 'get_uwa_auction_silent' ) ) {
		$uwa_silent = $product->get_uwa_auction_silent();
	}
}

/* ------------------------------------------------------------------
   2)  GET AUCTION END‑TIME SAFELY (avoid undefined‑method fatal)
-------------------------------------------------------------------*/
$end_raw = '';

if ( $product && method_exists( $product, 'get_uwa_auctions_end_time' ) ) {
	$end_raw = $product->get_uwa_auctions_end_time();
} else {
	// fallback: try meta key
	$end_raw = get_post_meta( $product_id, '_uwa_auctions_end_time', true );
}

// If still empty (preview with no meta), use +1 day as placeholder
if ( empty( $end_raw ) ) {
	$end_raw = gmdate( 'Y-m-d H:i:s', time() + DAY_IN_SECONDS );
}

$end_ts = strtotime( $end_raw );
$end_date = date_i18n( get_option( 'date_format' ), $end_ts );
$end_time = date_i18n( get_option( 'time_format' ), $end_ts );

/* ------------------------------------------------------------------
   3)  PRINT THE MESSAGE
-------------------------------------------------------------------*/
?>
<p>
<?php
	$link   = $product_id ? get_permalink( $product_id ) : home_url();
	$title  = $product ? $product->get_title() : __( 'Auction Product', 'woo_ua' );

	printf(
		/* translators: %1$s = product link, %2$s = product title, %3$s = end date/time */
		__( 'Auction <a href="%1$s">%2$s</a> is going to expire at %3$s.', 'woo_ua' ),
		esc_url( $link ),
		esc_html( $title ),
		esc_html( $end_date . ' ' . $end_time )
	);
?>
</p>
<?php do_action( 'woocommerce_email_footer', $email ); ?>