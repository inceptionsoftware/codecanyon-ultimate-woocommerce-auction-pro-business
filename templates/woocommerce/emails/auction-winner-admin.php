<?php

/**
 * Send Email to admin when the bidder won the auction. (HTML)
 * 
 * @package Ultimate WooCommerce Auction PRO
 * @author Nitesh Singh 
 * @since 3.0.0
 *
 */

if (!defined('ABSPATH')) {
    exit;
}

?>
<?php do_action( 'woocommerce_email_header', $email_heading, $email ); ?>

<?php
// ---------- LIVE & PREVIEW MODE SUPPORT (only variable block changed) ----------
$product           = null;
$product_id        = 0;
$args              = array('currency' => get_woocommerce_currency());
$user_name         = '';
$auction_title     = '';
$auction_bid_value = '';
$thumb_image       = '';
$user_type         = '';
$winner_id         = 0;
$auction_owner_name= '';
$auction_owner_id  = '';
$auction_url_admin = '';

if (is_array($email->object) && isset($email->object['product_id'])) {
    // ---------- LIVE EMAIL DATA ----------
    $product_id = absint($email->object['product_id']);
    $product    = function_exists('Woo_UA') && method_exists(Woo_UA(), 'uwa_get_product')
        ? Woo_UA()->uwa_get_product($product_id)
        : wc_get_product($product_id);

    $product_base_currency = method_exists($product, 'uwa_aelia_get_base_currency')
        ? $product->uwa_aelia_get_base_currency()
        : get_woocommerce_currency();
    $args = array('currency' => $product_base_currency);

    $user_name          = esc_html($email->object['user_name']);
    $auction_title      = $product->get_title();
    $auction_bid_value  = wc_price(method_exists($product, 'get_uwa_current_bid') ? $product->get_uwa_current_bid() : 0, $args);
    $thumb_image        = $product->get_image('thumbnail');
    $user_type          = $email->object['user_type'];
    $winner_id          = absint($email->object['user_id']);
    $auction_owner_name = esc_html($email->object['owner_name']);
    $auction_owner_id   = absint($email->object['owner_id']);
    $auction_url_admin = isset( $email->object['edit_url_product'] ) ? esc_url( $email->object['edit_url_product'] ) : admin_url( 'edit.php?post_type=product' ); // fallback to product list
} else {
    // ---------- PREVIEW MODE ----------
    $order  = is_a($email->object, 'WC_Order') ? $email->object : null;
    $item   = $order ? current($order->get_items()) : null;
    $product = $item ? $item->get_product() : null;

    $product_id    = $product ? $product->get_id() : 0;
    $user_name     = $order ? $order->get_formatted_billing_full_name() : '';
    $auction_title = $product ? $product->get_title() : __('Auction Product', 'woo_ua');
    $thumb_image   = $product ? $product->get_image('thumbnail') : '';
    $user_type     = 'admin';
    $auction_bid_value = wc_price($item ? $item->get_total() : 0, $args);
    $auction_url_admin  = $product ? get_permalink($product_id) : home_url();
}
?>

<?php if ($user_type === "admin") {
    $userlink = add_query_arg('user_id', $winner_id, admin_url('user-edit.php'));
?>
    <p><?php printf(__('Hi admin,', 'woo_ua')); ?></p>
    <p><?php printf(__('The auction has expired and won by user. Auction url <a href="%s">%s</a>.', 'woo_ua'), $auction_url_admin, $auction_title); ?></p>
    <p><?php printf(__('Here are the details : ', 'woo_ua')); ?></p>
    <table>
        <tr>
            <td><?php echo __('Image', 'woo_ua'); ?></td>
            <td><?php echo __('Product', 'woo_ua'); ?></td>
            <td><?php echo __('Winning bid', 'woo_ua'); ?></td>
            <td><?php echo __('Winner', 'woo_ua'); ?></td>
        </tr>
        <tr>
            <td><?php echo $thumb_image; ?></td>
            <td><a href="<?php echo $auction_url_admin; ?>"><?php echo $auction_title; ?></a></td>
            <td><?php echo $auction_bid_value; ?></td>
            <td><a href="<?php echo $userlink; ?>"><?php echo $user_name; ?></a></td>
        </tr>
    </table>
<?php } ?>

<?php if ($user_type === "auction_owner") {
    $auction_url = esc_url($email->object['url_product']);
    $userlink = add_query_arg('user_id', $winner_id, admin_url('user-edit.php'));
?>
    <p><?php printf(__('Hi %s,', 'woo_ua'), $auction_owner_name); ?></p>
    <p><?php printf(__('The auction has expired and won by user. Auction url <a href="%s">%s</a>.', 'woo_ua'), $auction_url, $auction_title); ?></p>
    <p><?php printf(__('Here are the details : ', 'woo_ua')); ?></p>
    <table>
        <tr>
            <td><?php echo __('Image', 'woo_ua'); ?></td>
            <td><?php echo __('Product', 'woo_ua'); ?></td>
            <td><?php echo __('Winning bid', 'woo_ua'); ?></td>
            <td><?php echo __('Winner', 'woo_ua'); ?></td>
        </tr>
        <tr>
            <td><?php echo $thumb_image; ?></td>
            <td><a href="<?php echo $auction_url; ?>"><?php echo $auction_title; ?></a></td>
            <td><?php echo $auction_bid_value; ?></td>
            <td><a href="<?php echo $userlink; ?>"><?php echo $user_name; ?></a></td>
        </tr>
    </table>
<?php
    $addons = uwa_enabled_addons();
    if (is_array($addons) && in_array('uwa_offline_dealing_addon', $addons)) {
        $display_contact_detail = get_option("offline_dealing_display_in_mail", "no");

        if ($display_contact_detail === "yes") {
            echo "<br>";
            _e('Winner contact details :', 'woo_ua');

            $customer_id = $winner_id;
            $display_contact = "";
            $country = "";
            $first_name = get_user_meta($customer_id, 'billing_first_name', true);
            $last_name = get_user_meta($customer_id, 'billing_last_name', true);
            $email = get_user_meta($customer_id, 'billing_email', true);
            $add_1 = get_user_meta($customer_id, 'billing_address_1', true);
            $add_2 = get_user_meta($customer_id, 'billing_address_2', true);
            $city = get_user_meta($customer_id, 'billing_city', true);
            $postcode = get_user_meta($customer_id, 'billing_postcode', true);
            $phone = get_user_meta($customer_id, 'billing_phone', true);
            $cntry_code = get_user_meta($customer_id, 'billing_country', true);
            if ($cntry_code) {
                $country = WC()->countries->countries[$cntry_code] . "  ($cntry_code)";
            }

            echo "<p>";
            if ($first_name || $last_name) {
                $display_contact = $first_name . " " . $last_name . "<br>";
            }
            if ($add_1) $display_contact .= $add_1 . "<br>";
            if ($add_2) $display_contact .= $add_2 . "<br>";
            if ($city) $display_contact .= $city . "<br>";
            if ($postcode) $display_contact .= $postcode . "<br>";
            if ($country) $display_contact .= $country . "<br><br>";

            echo $display_contact;
            if ($phone) {
                _e('Contact : ', 'woo_ua');
                echo $phone;
            }
            echo "<br>";
            if ($email) {
                _e('Email : ', 'woo_ua');
                echo $email;
            }
            echo "</p>";
        }
    }
?>
<?php } ?>


<?php do_action( 'woocommerce_email_footer', $email ); ?>