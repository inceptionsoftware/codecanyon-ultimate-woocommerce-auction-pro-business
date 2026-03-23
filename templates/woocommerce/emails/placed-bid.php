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
/* -----------------------------------------------------------------
 * LIVE & PREVIEW MODE SUPPORT  – ONLY the variable block is changed.
 * ----------------------------------------------------------------*/
global $wpdb;

$product            = null;
$user_type          = '';
$user_name          = '';
$auction_url        = '';
$auction_title      = '';
$auction_bid_value  = '';
$thumb_image        = '';
$product_id         = 0;
$user_id            = 0;
$args               = array( 'currency' => get_woocommerce_currency() );

if ( is_array( $email->object ) && isset( $email->object['product'] ) ) {
	/* ---------- LIVE EMAIL DATA ---------- */
	$user_type = $email->object['user_type'];
	$product   = $email->object['product'];

	$product_base_currency = method_exists( $product, 'uwa_aelia_get_base_currency' )
		? $product->uwa_aelia_get_base_currency()
		: get_woocommerce_currency();
	$args = array( 'currency' => $product_base_currency );

	$auction_url   = esc_url( $email->object['url_product'] );
	$user_name     = esc_html( $email->object['user_name'] );
	$auction_title = $product->get_title();
	$thumb_image   = $product->get_image( 'thumbnail' );
	$uwa_silent    = $product->get_uwa_auction_silent();
	$uwa_proxy     = $product->get_uwa_auction_proxy();
	$product_id    = $product->get_id();
	$user_id       = absint( $email->object['placebid_userid'] );

	/* current bid */
	$auction_bid_raw = ( $uwa_silent === 'yes' )
		? $product->get_uwa_last_bid()
		: $product->get_uwa_current_bid();

	/* proxy handling */
	if ( $uwa_proxy === 'yes' ) {
		$proxy_bid = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT bid FROM {$wpdb->prefix}woo_ua_auction_log
				 WHERE auction_id = %d AND userid = %d
				 ORDER BY id DESC LIMIT 1",
				$product_id,
				$user_id
			)
		);
		if ( $proxy_bid !== null ) {
			$auction_bid_raw = $proxy_bid;
		}
	}

	$auction_bid_value = wc_price( $auction_bid_raw, $args );

} else {
	/* ---------- PREVIEW MODE ---------- */
	$order  = is_a( $email->object, 'WC_Order' ) ? $email->object : null;
	$item   = $order ? current( $order->get_items() ) : null;
	$product = $item ? $item->get_product() : null;

	$user_type     = 'bidder';
	$user_name     = $order ? $order->get_formatted_billing_full_name() : '';
	$product_id    = $product ? $product->get_id() : 0;
	$auction_url   = $product ? get_permalink( $product_id ) : home_url();
	$auction_title = $product ? $product->get_title() : __( 'Auction Product', 'woo_ua' );
	$thumb_image   = $product ? $product->get_image( 'thumbnail' ) : '';

	/* WooCommerce preview total (or meta) as fallback bid */
	$auction_bid_raw = $item ? $item->get_total() : get_post_meta( $product_id, '_auction_current_bid', true );
	$auction_bid_value = wc_price( $auction_bid_raw, $args );

	/* flags for template logic */
	$uwa_silent = get_post_meta( $product_id, '_uwa_auction_silent', true );
	$uwa_proxy  = get_post_meta( $product_id, '_uwa_auction_proxy', true );
}
/* ----------------------------------------------------------------- */
?>

<?php if($user_type ==="bidder"){ 

	?>
	
	<p><?php printf( __( "Hi %s,", 'woo_ua' ), $user_name); ?></p>
	<p>
	<?php 
			
			$cur_userid = get_current_user_id();
			$sentmail = get_user_meta($cur_userid, "uwa_samemaxbid_sent_mail", true);
			if($sentmail == "yes"){
				printf( __( "An user has placed a bid which matched your maximum bid and due to this, we have placed a new bid on your behalf with an amount same as your 'Maximum Bid' and declined the other user's bid. on <a href='%s'>%s</a>.", "woo_ua" ), 
					$auction_url, $auction_title);

				delete_user_meta($cur_userid, "uwa_samemaxbid_sent_mail");

			}
			else{
				printf( __( 'You recently placed a bid on <a href="%s">%s</a>.', 'woo_ua' ), 
					$auction_url, $auction_title);
			}

	?>
	</p>

	<p><?php printf( __( "Here are the details : ", 'woo_ua' )); ?></p>
	<table>
		<tr>
			<td><?php echo __( 'Image', 'woo_ua' ); ?></td>
			<td><?php echo __( 'Product', 'woo_ua' ); ?></td>
			<td><?php echo __( 'Your bid', 'woo_ua' ); ?></td>	
			<?php
				if ($uwa_proxy == 'yes' &&  $product->get_uwa_auction_max_current_bider() && get_current_user_id() == $product->get_uwa_auction_max_current_bider()) {
					?>
					<td>
						<?php echo __( 'Auto', 'woo_ua' ); ?>
					</td>

					<?php
				}
			?>
		</tr>
	    <tr>
			<td><?php echo $thumb_image;?></td>
			<td><a href="<?php echo $auction_url; ?>"><?php echo $auction_title; ?></a></td>
			<td><?php echo $auction_bid_value; ?></td>
			<?php
				if ($uwa_proxy == 'yes' &&  $product->get_uwa_auction_max_current_bider() && get_current_user_id() == $product->get_uwa_auction_max_current_bider()) {
					?>
					<td>
						<?php 
							$max_bid_price = $product->get_uwa_auction_max_bid();
							if($max_bid_price){
								$formatted_max_bid_price = wc_price($max_bid_price, $args);
							}
							else{
								$formatted_max_bid_price = " --- ";
							}
							echo $formatted_max_bid_price; 
						?>
					</td>

					<?php
				}
			?>
	    </tr>
	</table>
<?php } ?>



<?php do_action('woocommerce_email_footer', $email); ?>