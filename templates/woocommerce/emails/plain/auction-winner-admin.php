<?php

/**
 * Bidder placed a bid email notification (plain)
 * 
 * @package Ultimate WooCommerce Auction PRO
 * @author Nitesh Singh 
 * @since 1.0  
 *
 */

if (!defined('ABSPATH')) {
    exit;
}

global $woocommerce;

$product_id = '';
$product = null;
$product_base_currency = get_woocommerce_currency(); // fallback
$args = array("currency" => $product_base_currency);
$user_name = '';
$auction_title = '';
$auction_bid_value = '';
$thumb_image = '';
$user_type = '';
$winner_id = '';
$auction_owner_name = '';
$auction_owner_id = '';
$auction_url = '';

if (is_array($email->object)) {
    // LIVE MODE
    $product_id = $email->object['product_id'];
    $product = wc_get_product($product_id);

    if ($product && method_exists($product, 'uwa_aelia_get_base_currency')) {
        $product_base_currency = $product->uwa_aelia_get_base_currency();
        $args = array("currency" => $product_base_currency);
    }

    $user_name = $email->object['user_name'];
    $auction_title = $product->get_title();
    $auction_bid_value = wc_price($product->get_uwa_current_bid(), $args);
    $thumb_image = $product->get_image('thumbnail');
    $user_type = $email->object['user_type'];
    $winner_id = $email->object['user_id'];
    $auction_owner_name = $email->object['owner_name'];
    $auction_owner_id = $email->object['owner_id'];
    $auction_url = $email->object['url_product'];

} elseif (is_object($email->object) && is_a($email->object, 'WC_Order')) {
    // PREVIEW MODE
    $order = $email->object;
    $item = current($order->get_items());
    if ($item) {
        $product = $item->get_product();
        if ($product) {
            $product_id = $product->get_id();
            $auction_title = $product->get_title();
            $auction_bid_value = wc_price($item->get_total(), $args);
            $thumb_image = $product->get_image('thumbnail');
            $auction_url = get_permalink($product_id);
            $user_type = 'auction_owner';
            $user_name = $order->get_billing_first_name() . ' ' . $order->get_billing_last_name();
            $winner_id = $order->get_customer_id();
            $auction_owner_id = get_current_user_id();
            $auction_owner_name = 'Admin';
        }
    }
}

echo $email_heading . "</br><br>";

if ($user_type === "admin") {
    $userlink = add_query_arg('user_id', $winner_id, admin_url('user-edit.php'));

    printf(__("Hi Admin,", 'woo_ua'));
    echo "</br>";
    printf(__("The auction has expired and won by user. Auction url <a href='%s'>%s</a>.", 'woo_ua'), $auction_url, $auction_title);
    echo "</br>";
    printf(__("<a href='%s'>%s</a>.", 'woo_ua'), $userlink, $user_name);
    echo "</br><br>";
    printf(__("Winning bid %s.", 'woo_ua'), $auction_bid_value);
    echo "</br>";
}

if ($user_type === "auction_owner") {
    $userlink = add_query_arg('user_id', $winner_id, admin_url('user-edit.php'));

    printf(__("Hi %s,", 'woo_ua'), $auction_owner_name);
    echo "</br>";
    printf(__("The auction has expired and won by user. Auction url <a href='%s'>%s</a>.", 'woo_ua'), $auction_url, $auction_title);
    echo "</br>";
    printf(__("<a href='%s'>%s</a>.", 'woo_ua'), $userlink, $user_name);
    echo "</br><br>";
    printf(__("Winning bid %s.", 'woo_ua'), $auction_bid_value);
    echo "</br>";

    $addons = function_exists('uwa_enabled_addons') ? uwa_enabled_addons() : array();
    if (is_array($addons) && in_array('uwa_offline_dealing_addon', $addons)) {
        $display_contact_detail = get_option("offline_dealing_display_in_mail", "no");

        if ($display_contact_detail === "yes") {
            echo "<br>";
            _e('Winner contact details :', 'woo_ua');

            $customer_id = $winner_id;

            $first_name = get_user_meta($customer_id, 'billing_first_name', true);
            $last_name = get_user_meta($customer_id, 'billing_last_name', true);
            $email = get_user_meta($customer_id, 'billing_email', true);
            $add_1 = get_user_meta($customer_id, 'billing_address_1', true);
            $add_2 = get_user_meta($customer_id, 'billing_address_2', true);
            $city = get_user_meta($customer_id, 'billing_city', true);
            $postcode = get_user_meta($customer_id, 'billing_postcode', true);
            $phone = get_user_meta($customer_id, 'billing_phone', true);
            $cntry_code = get_user_meta($customer_id, 'billing_country', true);
            $country = $cntry_code ? WC()->countries->countries[$cntry_code] . " ($cntry_code)" : '';

            echo "<p>";
            if ($first_name || $last_name) {
                echo $first_name . " " . $last_name . "<br>";
            }
            if ($add_1) echo $add_1 . "<br>";
            if ($add_2) echo $add_2 . "<br>";
            if ($city) echo $city . "<br>";
            if ($postcode) echo $postcode . "<br>";
            if ($country) echo $country . "<br><br>";
            if ($phone) {
                _e('Contact : ', 'woo_ua');
                echo $phone . "<br>";
            }
            if ($email) {
                _e('Email : ', 'woo_ua');
                echo $email;
            }
            echo "</p>";
        }
    }
}

echo apply_filters('woocommerce_email_footer_text', get_option('woocommerce_email_footer_text'));