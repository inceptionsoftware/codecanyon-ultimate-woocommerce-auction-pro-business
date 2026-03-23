<?php

/**
 * Admin deleted user bid notification (HTML)
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
<?php do_action('woocommerce_email_header', $email_heading, $email); ?>

<?php
/* ---------- LIVE & PREVIEW MODE SUPPORT ---------- */
$product            = null;
$product_id         = 0;
$args               = array( 'currency' => get_woocommerce_currency() );
$auction_title      = '';
$auction_url        = '';
$deleted_bid_value  = '';
$thumb_image        = '';
$user_name          = '';

if ( is_array( $email->object ) && isset( $email->object['product'] ) ) {
	/* ---------- LIVE EMAIL DATA ---------- */
	$product    = $email->object['product'];
	$product_id = $product->get_id();

	/* Currency */
	$product_base_currency = method_exists( $product, 'uwa_aelia_get_base_currency' )
		? $product->uwa_aelia_get_base_currency()
		: get_woocommerce_currency();
	$args = array( 'currency' => $product_base_currency );

	/* Core values */
	$auction_title      = $email->object['product_name'];
	$auction_url        = esc_url( $email->object['url_product'] );
	$deleted_bid_value  = wc_price( $email->object['deleted_bid'], $args );
	$thumb_image        = $product->get_image( 'thumbnail' );
	$user_name          = esc_html( $email->object['user_name'] );

} else {
	/* ---------- PREVIEW MODE ---------- */
	$order  = is_a( $email->object, 'WC_Order' ) ? $email->object : null;
	$item   = $order ? current( $order->get_items() ) : null;
	$product = $item ? $item->get_product() : null;
	$product_id = $product ? $product->get_id() : 0;

	/* Core values with fallbacks */
	$auction_title = $product ? $product->get_title() : __( 'Auction Product', 'woo_ua' );
	$auction_url   = $product ? get_permalink( $product_id ) : home_url();
	$thumb_image   = $product ? $product->get_image( 'thumbnail' ) : '';
	$user_name     = $order ? $order->get_formatted_billing_full_name() : '';

	/* Use line‑item total (like WC preview) before falling back to meta */
	$deleted_raw = $item ? $item->get_total() : get_post_meta( $product_id, '_auction_current_bid', true );
	$deleted_bid_value = wc_price( $deleted_raw, $args );
}
?>

<p><?php printf( __( "Hi %s,", "woo_ua" ), $user_name); ?></p>
<p><?php printf( __( "You are receiving this email as auction owner has deleted your bid. Kindly contact admin/owner for further discussion.", "woo_ua" )); ?></p>

<p><?php printf( __( "Here are the details : ", "woo_ua" )); ?></p>

<table>
   	<tr>	 
		<td><?php echo __( 'Image', 'woo_ua' ); ?></td>
		<td><?php echo __( 'Product', 'woo_ua' ); ?></td>
		<td><?php echo __( 'Deleted Bid', 'woo_ua' ); ?></td>	
	</tr>
    <tr>
		<td><?php echo $thumb_image;?></td>
		<td><a href="<?php echo $auction_url ;?>"><?php echo $auction_title; ?></a></td>
		<td><?php echo $deleted_bid_value; ?></td>
    </tr>
</table>

<Br><Br>

<?php do_action('woocommerce_email_footer', $email); ?>