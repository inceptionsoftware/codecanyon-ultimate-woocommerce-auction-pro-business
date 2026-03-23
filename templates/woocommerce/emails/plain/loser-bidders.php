<?php

/**
 * Send Email to bidder when the bidder won the auction. (Plain)
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
<?php
$product     = null;
$product_id  = 0;

/* ---------- LIVE MODE ---------- */
if ( isset( $email->productid ) ) {
	$product_id = absint( $email->productid );
	$product    = wc_get_product( $product_id );

/* ---------- PREVIEW MODE ---------- */
} elseif ( is_a( $email->object, 'WC_Order' ) ) {
	$order   = $email->object;
	$item    = current( $order->get_items() );
	$product = $item ? $item->get_product() : null;
	$product_id = $product ? $product->get_id() : 0;
}
?>

<?php  echo $email_heading . "</br><Br>"; ?>

<p><?php _e( "Hi,", 'woo_ua' ); ?></p>

<p>
<?php
if ( $product ) {
	printf(
		__( 'We regret to inform you that you have lost <a href="%s">%s</a>', 'woo_ua' ),
		esc_url( get_permalink( $product_id ) ),
		esc_html( $product->get_title() )
	);
} else {
	_e( 'We regret to inform you that you have lost the auction.', 'woo_ua' );
}
?>
</p>

<?php echo apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ) ); ?>