<?php

/**
 * outbid email
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
$auction_url        = '';
$user_name          = '';
$auction_title      = '';
$auction_bid_value  = '';
$thumb_image        = '';
$args               = array( 'currency' => get_woocommerce_currency() );

/* ---------- LIVE DATA (array) ---------- */
if ( is_array( $email->object ) && isset( $email->object['product'] ) ) {

	$product   = $email->object['product'];                    // WC_Product
	$auction_url = esc_url( $email->object['url_product'] );
	$user_name   = esc_html( $email->object['user_name'] );

	$product_base_currency = method_exists( $product, 'uwa_aelia_get_base_currency' )
		? $product->uwa_aelia_get_base_currency()
		: get_woocommerce_currency();
	$args = array( 'currency' => $product_base_currency );

	$auction_title = $product->get_title();
	$thumb_image   = $product->get_image( 'thumbnail' );
	$auction_bid_raw = $product->get_uwa_current_bid();
	$uwa_silent   = $product->get_uwa_auction_silent();
	$show_cprice  = get_option( 'uwa_silent_outbid_email_cprice', 'no' );

	if ( $uwa_silent === 'yes' ) {
		$auction_bid_raw = ( $show_cprice === 'no' )
			? $product->get_price_html()
			: $product->get_uwa_last_bid();
	}

	$auction_bid_value = is_numeric( $auction_bid_raw )
		? wc_price( $auction_bid_raw, $args )
		: $auction_bid_raw; // already HTML if silent & cprice=no

/* ---------- PREVIEW MODE (WC_Order) ---------- */
} else {

	$order  = is_a( $email->object, 'WC_Order' ) ? $email->object : null;
	$item   = $order ? current( $order->get_items() ) : null;
	$product = $item ? $item->get_product() : null;

	$auction_url   = $product ? get_permalink( $product->get_id() ) : home_url();
	$user_name     = $order ? $order->get_formatted_billing_full_name() : '';
	$auction_title = $product ? $product->get_title() : __( 'Auction Product', 'woo_ua' );
	$thumb_image   = $product ? $product->get_image( 'thumbnail' ) : '';

	$auction_bid_raw = $item ? $item->get_total() : get_post_meta( $product ? $product->get_id() : 0, '_auction_current_bid', true );
	$auction_bid_value = wc_price( $auction_bid_raw, $args );
}
?>

<p><?php printf( __( "Hi %s,", 'woo_ua' ), $user_name); ?></p>
<p><?php printf( __( 'You have been outbid on the product <a href="%s">%s</a>.', 
	'woo_ua' ),
    $auction_url, $auction_title); ?></p>
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
<div>
	<p><?php _e( 'If you want to bid a new amount, click here', 'woo_ua' ); ?> 
	<a href="<?php echo $auction_url;?>"><?php echo $auction_title; ?></a> </p>
</div>

<?php do_action('woocommerce_email_footer', $email);?>