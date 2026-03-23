<?php

/**
 * Auction relist email. (HTML)
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
/* ---------- LIVE & PREVIEW MODE SUPPORT (only variable block updated) ---------- */
if ( isset( $email->productid ) ) {
	// LIVE EMAIL
	$product_id   = absint( $email->productid );
	$product_data = wc_get_product( $product_id );
	$uwa_reason   = $email->uwa_reason;
} else {
	// PREVIEW MODE
	$order  = is_a( $email->object, 'WC_Order' ) ? $email->object : null;
	$item   = $order ? current( $order->get_items() ) : null;
	$product_data = $item ? $item->get_product() : null;
	$product_id   = $product_data ? $product_data->get_id() : 0;
	$uwa_reason   = __( 'Preview mode (relisted manually)', 'woo_ua' );
}
?>

<p>
<?php
printf(
	__( 'The auction product for <a href="%s">%s</a> has been relisted.<br>Reason to Relist: %s', 'woo_ua' ),
	get_permalink( $product_id ),
	$product_data ? $product_data->get_title() : __( 'Auction Product', 'woo_ua' ),
	esc_html( $uwa_reason )
);
?>
</p>

<?php do_action('woocommerce_email_footer', $email); ?>