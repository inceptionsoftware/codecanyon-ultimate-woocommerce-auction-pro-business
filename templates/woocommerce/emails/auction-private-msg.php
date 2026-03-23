<?php

/**
 * Admin - Private Message By User. (HTML)
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
// ---------- LIVE & PREVIEW MODE SUPPORT ----------
$product        = null;
$user_name      = '';
$user_email     = '';
$user_message   = '';
$auction_url    = '';
$product_id     = 0;
$auction_title  = '';

if ( is_array( $email->object ) && isset( $email->object['product_id'] ) ) {
    // LIVE EMAIL DATA
    $user_name    = $email->object['user_name'];
    $user_email   = $email->object['user_email'];
    $user_message = $email->object['user_message'];
    $auction_url  = $email->object['url_product'];
    $product_id   = absint( $email->object['product_id'] );
    $product      = wc_get_product( $product_id );
    $auction_title = $product ? $product->get_title() : __( 'Auction Product', 'woo_ua' );

} else {
    // PREVIEW MODE
    $order  = is_a( $email->object, 'WC_Order' ) ? $email->object : null;
    $item   = $order ? current( $order->get_items() ) : null;
    $product = $item ? $item->get_product() : null;
    $product_id = $product ? $product->get_id() : 0;

    $user_name    = __( 'John Doe', 'woo_ua' );
    $user_email   = 'john@example.com';
    $user_message = __( 'I would like to know more about this auction item.', 'woo_ua' );
    $auction_url  = $product ? get_permalink( $product_id ) : home_url();
    $auction_title = $product ? $product->get_title() : __( 'Auction Product', 'woo_ua' );
}
?>

<p><?php printf( __( "Hi,", 'woo_ua' )); ?> </p>
<p><?php printf( __( 'Bidder Sent Private Message for Auction <a href="%s">%s</a>.', 'woo_ua' ), 
		$auction_url, $auction_title); ?> </p>
<p><?php printf( __( "Here are the details : ", 'woo_ua' )); ?> </p>
<table>
	<tr>	 
		<td><?php echo __( 'Name:', 'woo_ua' ); ?></td>
		<td><?php echo $user_name;?></td>	 
	</tr>
	<tr>	 
		<td><?php echo __( 'Email:', 'woo_ua' ); ?></td>
		<td><?php echo $user_email;?></td>	 
		</tr>
	<tr>	 
		<td><?php echo __( 'Message:', 'woo_ua' ); ?></td>
		<td><?php echo $user_message;?></td>	 
	</tr>
</table>

<?php do_action('woocommerce_email_footer', $email); ?>