<?php

/**
 * watchlist email
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
	
<?php do_action('woocommerce_email_header', $email_heading, $email);  ?>

<?php
/* ---------- LIVE & PREVIEW MODE SUPPORT ---------- */
$product            = null;
$auction_url        = '';
$user_name          = '';
$auction_title      = '';
$auction_bid_value  = '';
$thumb_image        = '';
$args               = array( 'currency' => get_woocommerce_currency() );

/* ---------- LIVE EMAIL (array data) ---------- */
if ( is_array( $email->object ) && isset( $email->object['product'] ) ) {

	$product = $email->object['product'];

	/* Currency */
	$product_base_currency = method_exists( $product, 'uwa_aelia_get_base_currency' )
		? $product->uwa_aelia_get_base_currency()
		: get_woocommerce_currency();
	$args = array( 'currency' => $product_base_currency );

	/* Core variables */
	$auction_url   = esc_url( $email->object['url_product'] );
	$user_name     = esc_html( $email->object['user_name'] );
	$auction_title = $email->object['product_name'];
	$thumb_image   = $product->get_image( 'thumbnail' );

	/* Bid value */
	$currentbid         = $product->get_uwa_current_bid();
	$auction_bid_value  = wc_price( $currentbid, $args );

/* ---------- PREVIEW MODE (WooCommerce passes WC_Order) ---------- */
} else {

	$order  = is_a( $email->object, 'WC_Order' ) ? $email->object : null;
	$item   = $order ? current( $order->get_items() ) : null;
	$product = $item ? $item->get_product() : null;

	/* Currency */
	$product_base_currency = $product ? get_post_meta( $product->get_id(), '_aelia_base_currency', true ) : '';
	$args = array( 'currency' => $product_base_currency ? $product_base_currency : get_woocommerce_currency() );

	/* Core variables */
	$auction_url   = $product ? get_permalink( $product->get_id() ) : home_url();
	$user_name     = $order ? $order->get_formatted_billing_full_name() : '';
	$auction_title = $product ? $product->get_title() : __( 'Auction Product', 'woo_ua' );
	$thumb_image   = $product ? $product->get_image( 'thumbnail' ) : '';

	/* Bid value: use line‑item total like WC default preview */
	$currentbid        = $item ? $item->get_total() : get_post_meta( $product ? $product->get_id() : 0, '_auction_current_bid', true );
	$auction_bid_value = wc_price( $currentbid, $args );
}
?>
<p><?php printf( __( "Hi %s,", 'woo_ua' ), $user_name); ?></p>
<p><?php printf( __( 'A bid was placed on an auction product which was in your watchlist.', 
	'woo_ua' ), $auction_url, $auction_title); ?></p>
<p><?php printf( __( "Here are the details : ", 'woo_ua' )); ?></p>
<table>
   	<tr>	 
		<td><?php echo __( 'Image', 'woo_ua' ); ?></td>
		<td><?php echo __( 'Product', 'woo_ua' ); ?></td>
		<td><?php echo __( 'Current bid', 'woo_ua' ); ?></td>	
	</tr>
    <tr>
		<td><?php echo $thumb_image;?></td>
		<td><a href="<?php echo $auction_url ;?>"><?php echo $auction_title; ?></a></td>
		<td><?php echo $auction_bid_value;  ?></td>
    </tr>
</table>
<?php do_action('woocommerce_email_footer', $email);?>