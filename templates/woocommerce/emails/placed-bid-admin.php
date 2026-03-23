<?php

/**
 * Bidder placed a bid email notification (HTML)
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
global $wpdb;

// ---------- LIVE & PREVIEW MODE SUPPORT ----------
$product       = null;
$user_type     = '';
$user_name     = '';
$auction_url   = '';
$auction_title = '';
$auction_bid_value = '';
$thumb_image   = '';
$product_id    = 0;
$user_id       = 0;

if ( is_array( $email->object ) && isset( $email->object['product'] ) ) {
	// LIVE EMAIL DATA
	$user_type = $email->object['user_type'];
	$product   = $email->object['product'];

	$product_base_currency = method_exists( $product, 'uwa_aelia_get_base_currency' ) ? $product->uwa_aelia_get_base_currency() : get_woocommerce_currency();
	$args = array("currency" => $product_base_currency);

	$auction_url   = $email->object['url_product'];
	$user_name     = $email->object['user_name'];
	$auction_title = $product->get_title();
	$thumb_image   = $product->get_image( 'thumbnail' );
	$uwa_silent    = $product->get_uwa_auction_silent();
	$uwa_proxy     = $product->get_uwa_auction_proxy();
	$product_id    = $product->get_id(); 
	$user_id       = $email->object['placebid_userid'];

	if ( $uwa_silent === 'yes' ) {
		$auction_bid_value = wc_price( $product->get_uwa_last_bid(), $args );
	} else {
		$auction_bid_value = wc_price( $product->get_uwa_current_bid(), $args );
	}

	if ( $uwa_proxy === 'yes' ) {
		$auction_type = $product->get_uwa_auction_type();

		$last_bid = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT bid FROM {$wpdb->prefix}woo_ua_auction_log WHERE auction_id = %d AND userid = %d ORDER BY id DESC LIMIT 1",
				$product_id,
				$user_id
			)
		);
		if ( $last_bid ) {
			$auction_bid_value = wc_price( $last_bid, $args );
		}
	}

} else {
	// PREVIEW MODE
	$order  = is_a( $email->object, 'WC_Order' ) ? $email->object : null;
	$item   = $order ? current( $order->get_items() ) : null;
	$product = $item ? $item->get_product() : null;

	$user_type     = 'admin';
	$product_id    = $product ? $product->get_id() : 0;
	$user_name     = $order ? $order->get_formatted_billing_full_name() : '';
	$auction_url   = $product ? get_permalink( $product_id ) : home_url();
	$auction_title = $product ? $product->get_title() : __( 'Auction Product', 'woo_ua' );
	$thumb_image   = $product ? $product->get_image( 'thumbnail' ) : '';
	$args          = array("currency" => get_woocommerce_currency());

	$auction_bid_value = get_post_meta( $product_id, '_auction_current_bid', true );
	if ( ! $auction_bid_value && $item ) {
		$auction_bid_value = $item->get_total();
	}
	if ( ! $auction_bid_value ) {
		$auction_bid_value = 100;
	}
	$auction_bid_value = wc_price( $auction_bid_value, $args );
}
	
?>

<?php if($user_type ==="admin"){ ?>
<p><?php printf( __( "Hi," ,'woo_ua' )); ?></p>
<p><?php printf( __( 'A bid was placed on <a href="%s">%s</a>.', 'woo_ua' ), $auction_url, $auction_title); ?></p>
<p><?php printf( __( "Here are the details : ", 'woo_ua' )); ?></p>
<table>
	<tr>
		<td><?php echo __( 'Image', 'woo_ua' ); ?></td>
		<td><?php echo __( 'Product', 'woo_ua' ); ?></td>
		<td><?php echo __( 'Bid Value', 'woo_ua' ); ?></td>	
	</tr>
    <tr>
		<td><?php echo $thumb_image;?></td>
		<td><a href="<?php echo $auction_url ;?>"><?php echo $auction_title; ?></a></td>
		<td><?php echo $auction_bid_value;  ?></td>
    </tr>
</table>
<?php } ?>


<?php do_action('woocommerce_email_footer', $email); ?>