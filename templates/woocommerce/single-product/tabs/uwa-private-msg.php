<?php
/**
 * Private message tab
 *
 * @package Ultimate WooCommerce Auction PRO
 * @author Nitesh Singh
 * @since 1.0
 *
 */

if (!defined('ABSPATH')) {
    exit;
}

global $woocommerce, $post, $product;

$user_id    = get_current_user_id();
$user       = get_user_by( 'id', $user_id );
$user_email = isset( $user->data->user_email ) ? $user->data->user_email : '';
$user_login = isset( $user->data->user_login ) ? $user->data->user_login : '';
?>
<div class="private_msg_main">
	<h2><?php esc_html_e( 'Send Private Message', 'woo_ua' ); ?></h2>

	<form id="uwa_private_msg_form" method="post" action="">
		<?php wp_nonce_field( 'uwa_private_msg_send', 'uwa_private_msg_nonce' ); ?>
		<!-- hidden variables -->
		<input type="hidden" name="uwa_pri_product_id" class="uwa_pri_product_id" value="<?php echo absint( $product->get_id() ); ?>" />
		<div id="uwa_private_msg_success"></div>
		<img class="uwa_private_msg_ajax_loader"
			src="<?php echo esc_url( UW_AUCTION_PRO_ASSETS_URL . 'images/ajax_loader.gif' ); ?>" alt="<?php esc_attr_e( 'Loading...', 'woo_ua' ); ?>" style="display: none;" />
		<table id="auction-privatemsg-table-<?php echo absint( $product->get_id() ); ?>"
			class="auction-privatemsg-table">

		    <tbody>
				<?php if ( ! is_user_logged_in() ) { ?>
				<tr>
					<td><?php esc_html_e( 'Name:', 'woo_ua' ); ?></td>
					<td class="name">
					   <input type="text" placeholder="<?php esc_attr_e( 'Your Name', 'woo_ua' ); ?>" class="uwa_pri_name" id="uwa_pri_name" name="uwa_pri_name" value="<?php echo esc_attr( $user_login ); ?>" ><br>
					   <div id="error_fname" class="error_forms"></div>
					</td>
		        </tr>
		        <tr>
					<td><?php esc_html_e( 'Email:', 'woo_ua' ); ?></td>
					<td class="name">
					   <input type="email" placeholder="you@example.com" class="uwa_pri_email" id="uwa_pri_email" value="<?php echo esc_attr( $user_email ); ?>" name="uwa_pri_email" ><br>
					   <span id="error_email" class="error_forms"></span>
					</td>
		        </tr>
				<?php } else { ?>

					 <input type="hidden" placeholder="<?php esc_attr_e( 'Your Name', 'woo_ua' ); ?>" class="uwa_pri_name" id="uwa_pri_name" name="uwa_pri_name" value="<?php echo esc_attr( $user_login ); ?>" >

					<input type="hidden" placeholder="you@example.com" class="uwa_pri_email" id="uwa_pri_email" value="<?php echo esc_attr( $user_email ); ?>" name="uwa_pri_email" >
				<?php } ?>

				<tr>
					<td><?php esc_html_e( 'Message:', 'woo_ua' ); ?></td>
					<td class="name">
					 <textarea id="uwa_pri_message" placeholder="<?php esc_attr_e( 'Message Detail', 'woo_ua' ); ?>" class="uwa_pri_message"></textarea>
					 <br>
					 <span id="error_message" class="error_forms"></span>
					</td>
		        </tr>

				<tr>
					<td>
					<button id="uwa_private_send" class="button alt uwa_private_send">
					<?php esc_html_e( 'Send', 'woo_ua' ); ?>
					</button>
					</td>
					<td></td>
		        </tr>

		    </tbody>
			<tr class="start">

			</tr>
		</table>
	</form>
</div>
<!--- Private Message tab end-->
