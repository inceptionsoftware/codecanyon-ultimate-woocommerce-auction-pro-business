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

<div class="uwa-watchlist-button">
    <?php if ($product->is_uwa_user_watching()): ?>
    	<a href="javascript:void(0)" data-auction-id="<?php echo esc_attr( $product->get_id() ); ?>"
		class="remove-uwa uwa-watchlist-action"><?php esc_html_e( 'Remove from watchlist!', 'woo_ua' ); ?></a>
		<a href="<?php echo esc_url( get_permalink( wc_get_page_id( 'myaccount' ) ) . 'uwa-auctions/?display=watchlist' ); ?>" class="view_watchlist">
		<?php esc_html_e( 'View List', 'woo_ua' ); ?></a>

    <?php else : ?>

	<?php

		/* When removing auction from watchlist listing - my-account/uwa-auctions/?display=watchlist */
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Read-only display toggle, no data mutation.
		if ( isset( $_GET['uwa-ajax'] ) ) {
			if ( sanitize_text_field( wp_unslash( $_GET['uwa-ajax'] ) ) === 'watchlist' ) { ?>
			<script>
				location.reload(true);
			</script>

			<?php
			}
		} else {
		// phpcs:enable WordPress.Security.NonceVerification.Recommended
		?>

	    	<a href="javascript:void(0)" data-auction-id="<?php echo esc_attr( $product->get_id() ); ?>" class="add-uwa uwa-watchlist-action <?php if ( $user_id == 0 ) echo ' no-action '; ?>" title="<?php if ( $user_id == 0 ) echo esc_attr__( 'Please sign in to add auction to watchlist.', 'woo_ua' ); ?>"><?php esc_html_e( 'Add to watchlist!', 'woo_ua' ); ?></a>

		<?php
		} ?>


    <?php endif; ?>
</div>
