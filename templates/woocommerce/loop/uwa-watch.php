<?php
/**
 * Auction Watchlist Button
 *
 * @package Ultimate WooCommerce Auction PRO
 * @author Nitesh Singh
 * @since 1.0
 *
 */

if (!defined('ABSPATH')) {
	exit;
}

global $woocommerce, $product, $post;
if(!(method_exists( $product, 'get_type') && $product->get_type() == 'auction')){
	return;
}

$user_id = get_current_user_id();
?>

<div class="woo-ua-watchlist-button">
    <?php if ($product->is_uwa_user_watching()): ?>
    	<a href="javascript:void(0)" data-auction-id="<?php echo esc_attr( $product->get_id() ); ?>"
		class="remove-woo-ua ua-watchlist-action "><?php esc_html_e( 'Unwatch', 'woo_ua' ); ?></a>
		<a href="<?php echo esc_url( get_permalink( wc_get_page_id( 'myaccount' ) ) ); ?>" class="view_watchlist">
		<?php esc_html_e( 'View List', 'woo_ua' ); ?></a>

    <?php else : ?>
    	<a href="javascript:void(0)" data-auction-id="<?php echo esc_attr( $product->get_id() ); ?>" class="add-woo-ua ua-watchlist-action <?php if ( $user_id == 0 ) echo ' no-action '; ?>" title="<?php if ( $user_id == 0 ) echo esc_attr__( 'Please sign in to add auction to watchlist.', 'woo_ua' ); ?>"><?php esc_html_e( 'Watch!', 'woo_ua' ); ?></a>
    <?php endif; ?>
</div>
