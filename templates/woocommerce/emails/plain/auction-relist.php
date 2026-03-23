<?php

/**
 * Auction relist email. (Plain)
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

// ---------- LIVE & PREVIEW MODE SUPPORT ----------
$product_id   = 0;
$product_data = null;
$uwa_reason   = '';

if ( isset( $email->productid ) ) {
	// LIVE EMAIL
	$product_id   = absint( $email->productid );
	$product_data = wc_get_product( $product_id );
	$uwa_reason   = isset( $email->uwa_reason ) ? $email->uwa_reason : '';
} else {
	// PREVIEW MODE
	$order        = is_a( $email->object, 'WC_Order' ) ? $email->object : null;
	$item         = $order ? current( $order->get_items() ) : null;
	$product_data = $item ? $item->get_product() : null;
	$product_id   = $product_data ? $product_data->get_id() : 0;
	$uwa_reason   = __( 'Preview mode – manually relisted', 'woo_ua' );
}

?>

<?php  echo $email_heading . "</br><Br>"; ?>

<?php printf(
	__( 'The auction product for <a href="%s">%s</a> has been relisted. <br>Reason to Relist: %s ', 'woo_ua' ),
	get_permalink( $product_id ),
	$product_data ? $product_data->get_title() : __( 'Auction Product', 'woo_ua' ),
	esc_html( $uwa_reason )
); ?>

<?php echo apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ) ); ?>