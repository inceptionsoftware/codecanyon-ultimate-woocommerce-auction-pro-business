<?php
/**
 * Auction Payment Button for winner
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


/* ------- Display contact details WHEN offline_dealing addon is active  ---- */

$addons = uwa_enabled_addons();
if(is_array($addons) && in_array('uwa_offline_dealing_addon', $addons)){

		$current_user_id = get_current_user_id();
		$post_author = $post->post_author;

		$display_contact_detail = get_option(
				"offline_dealing_display_contact_detail", "no");

		if($display_contact_detail == "yes"){

			/* when winner is logged in then display details of seller */

			if (( $current_user_id == $product->get_uwa_auction_current_bider() &&
				$product->get_uwa_auction_expired() == '2' &&
				!$product->get_uwa_auction_payed() )) {

					?>
					<p><?php esc_html_e( 'Congratulations! You have won this auction.', 'woo_ua' ); ?></p>
					<strong>
					<?php
					esc_html_e( 'Auction has expired and Seller details are :', 'woo_ua' );
					?> </strong> <?php


				/* seller(auction owner) can be of any role */
				$auction_owner_id = $post_author;
				$user_meta = get_userdata($auction_owner_id);
				$user_roles = $user_meta->roles;

				// when seller has store vendor role
				if (in_array( 'wcfm_vendor', $user_roles, true )) {

					// display vendor's store address - seller auction
					$vendor_profile = get_user_meta($auction_owner_id,
						"wcfmmp_profile_settings");

					if(is_array($vendor_profile)){

                if(isset($vendor_profile[0])){

                    $vdata = $vendor_profile[0];

                    $store_name  = '';
                    $store_email = '';
                    $add_1       = '';
                    $add_2       = '';
                    $city        = '';
                    $country     = '';
                    $postcode    = '';
                    $phone       = '';

                    if(isset($vdata['store_name']))
                       $store_name = $vdata['store_name'];

                    if(isset($vdata['store_email']))
                       $store_email = $vdata['store_email'];

                    if(isset($vdata['address']['street_1']))
                       $add_1 = $vdata['address']['street_1'];

                    if(isset($vdata['address']['street_2']))
                       $add_2 = $vdata['address']['street_2'];

                    if(isset($vdata['address']['city']))
                       $city = $vdata['address']['city'];

                    if(isset($vdata['address']['country']))
                       $country = $vdata['address']['country'];

                    if(isset($vdata['address']['zip']))
                       $postcode = $vdata['address']['zip'];

                    if(isset($vdata['phone']))
                       $phone = $vdata['phone'];


                    $display_contact = '<br><p><strong>' .
                       esc_html__( 'Store Address:', 'woo_ua' );

                    $display_contact .= '</strong><br>' . esc_html( $store_name ) . '<br>';

                    if($add_1)
                       $display_contact .= esc_html( $add_1 ) . '<br>';
                    if($add_2)
                       $display_contact .= esc_html( $add_2 ) . '<br>';
                    if($city)
                       $display_contact .= esc_html( $city ) . '<br>';
                    if($postcode)
                       $display_contact .= esc_html( $postcode ) . '<br>';
                    if($country)
                       $display_contact .= esc_html( $country ) . '<br>';

                    echo wp_kses_post( $display_contact );

                    if($phone){
                       esc_html_e( 'Contact : ', 'woo_ua' );
                       echo esc_html( $phone );
                    }
                       echo '<br>';

                    if($store_email){
                       esc_html_e( 'Email : ', 'woo_ua' );
                       echo esc_html( $store_email );
                    }

                } /* end of if - isset vendor profile */

					} /* end of if - isarray */

				}
				else{

					// display customer's billing details - admin auction

					$customer_id = $auction_owner_id;

					/******* REPLACE CODE OF billling FUNCTION HERE - START ********/

						$display_contact = '';
						$country = '';
						$first_name = get_user_meta( $customer_id, 'billing_first_name', true );
						$last_name  = get_user_meta( $customer_id, 'billing_last_name', true );
						$email      = get_user_meta( $customer_id, 'billing_email', true );
						$add_1      = get_user_meta( $customer_id, 'billing_address_1', true );
						$add_2      = get_user_meta( $customer_id, 'billing_address_2', true );
						$city       = get_user_meta( $customer_id, 'billing_city', true );
						$postcode   = get_user_meta( $customer_id, 'billing_postcode', true );
						$phone      = get_user_meta( $customer_id, 'billing_phone', true );
						$cntry_code = get_user_meta( $customer_id, 'billing_country', true );
						if($cntry_code){
							$country = WC()->countries->countries[$cntry_code] .
							"  ($cntry_code)";
						}

						echo '<p>';

						if($first_name || $last_name){
							$display_contact = esc_html( $first_name ) . ' ' . esc_html( $last_name ) . '<br>';
						}
						if($add_1)
							$display_contact .= esc_html( $add_1 ) . '<br>';
						if($add_2)
							$display_contact .= esc_html( $add_2 ) . '<br>';
						if($city)
							$display_contact .= esc_html( $city ) . '<br>';
						if($postcode)
							$display_contact .= esc_html( $postcode ) . '<br>';
						if($country)
							$display_contact .= esc_html( $country ) . '<br><br>';
						echo wp_kses_post( $display_contact );
						if($phone){
							esc_html_e( 'Contact : ', 'woo_ua' );
							echo esc_html( $phone );
						}
						echo '<br>';
						if($email){
							esc_html_e( 'Email : ', 'woo_ua' );
							echo esc_html( $email );
						}

						echo '</p>';

					/******* REPLACE CODE OF billling FUNCTION HERE - END ********/
				}

			} /* end of if - seller details */


			/* when seller(or any administrator) is logged in then display
				details of winner */

			// only administrator and auction owner can view info
			if(($current_user_id == $post_author || current_user_can('administrator'))
				&& $product->get_uwa_auction_expired() == '2' ){

				// display customer's billing details

					?> <strong> <?php
					esc_html_e( 'Auction has expired and Winner details are :', 'woo_ua' );
					?> </strong> <?php

				$winner_id   = $product->get_uwa_auction_current_bider();
				$customer_id = $winner_id;

				/******* REPLACE CODE OF billling FUNCTION HERE - START ********/

					$display_contact = '';
					$country    = '';
					$first_name = get_user_meta( $customer_id, 'billing_first_name', true );
					$last_name  = get_user_meta( $customer_id, 'billing_last_name', true );
					$email      = get_user_meta( $customer_id, 'billing_email', true );
					$add_1      = get_user_meta( $customer_id, 'billing_address_1', true );
					$add_2      = get_user_meta( $customer_id, 'billing_address_2', true );
					$city       = get_user_meta( $customer_id, 'billing_city', true );
					$postcode   = get_user_meta( $customer_id, 'billing_postcode', true );
					$phone      = get_user_meta( $customer_id, 'billing_phone', true );
					$cntry_code = get_user_meta( $customer_id, 'billing_country', true );
					if($cntry_code){
						$country = WC()->countries->countries[$cntry_code] .
						"  ($cntry_code)";
					}

					echo '<p>';

					if($first_name || $last_name){
						$display_contact = esc_html( $first_name ) . ' ' . esc_html( $last_name ) . '<br>';
					}
					if($add_1)
						$display_contact .= esc_html( $add_1 ) . '<br>';
					if($add_2)
						$display_contact .= esc_html( $add_2 ) . '<br>';
					if($city)
						$display_contact .= esc_html( $city ) . '<br>';
					if($postcode)
						$display_contact .= esc_html( $postcode ) . '<br>';
					if($country)
						$display_contact .= esc_html( $country ) . '<br><br>';
					echo wp_kses_post( $display_contact );
					if($phone){
						esc_html_e( 'Contact : ', 'woo_ua' );
						echo esc_html( $phone );
					}
					echo '<br>';
					if($email){
						esc_html_e( 'Email : ', 'woo_ua' );
						echo esc_html( $email );
					}

					echo '</p>';

				/******* REPLACE CODE OF billling FUNCTION HERE - END ********/

			} /* end of if - winner details */

		}
		elseif($display_contact_detail == "no"){

			/* only display congratulations text to winner only */

			if (( $current_user_id == $product->get_uwa_auction_current_bider() &&
				$product->get_uwa_auction_expired() == '2' &&
				!$product->get_uwa_auction_payed() )) {
					?>
						<p><?php esc_html_e( 'Congratulations! You have won this auction.', 'woo_ua' ); ?></p>
					<?php
			}


		} /* end of elseif */

} /* end of if - offline-dealing */


/* ---------------------------- PAY NOW Button  ---------------------------- */


$user_id      = get_current_user_id();
$checkout_url = esc_url( add_query_arg( 'pay-uwa-auction', $product->get_id(), uwa_auction_get_checkout_url() ) );

				/* change payment url for auto order */
				$uwa_auto_order_enable = get_option('uwa_auto_order_enable');
				if($uwa_auto_order_enable == "yes"){

					$productid    = $product->get_id();
				    $uwa_order_id = get_post_meta( $productid, 'woo_ua_order_id', true);
				    if ($uwa_order_id > 0){
				    	$order = wc_get_order($uwa_order_id);

				    	if(is_object($order)){
							$checkout_url = esc_url( $order->get_checkout_payment_url() );
						}

					}
				}

if ( ($user_id == $product->get_uwa_auction_current_bider() && $product->get_uwa_auction_expired() == '2' && !$product->get_uwa_auction_payed() ) ) {


		/* --------  when offline_dealing_addon is active ------- */

  		$addons = uwa_enabled_addons();
		if(is_array($addons) && in_array('uwa_offline_dealing_addon', $addons)){

				// buyers and stripe both deactive
			if(!in_array('uwa_buyers_premium_addon', $addons) &&
				!in_array('uwa_stripe_auto_debit_addon', $addons)){
				// nothing to output

			}	// buyers active only
			elseif(in_array('uwa_buyers_premium_addon', $addons) &&
				!in_array('uwa_stripe_auto_debit_addon', $addons)){

					do_action('ultimate_woocommerce_auction_before_pay_now_button', $product);

				?>
				<p>
					<a href="<?php echo esc_url( $checkout_url ); ?>" class="button alt uwa_pay_now">
					<?php echo esc_html( apply_filters( 'ultimate_woocommerce_auction_pay_now_button_text', __( "Pay Buyer's Premium", 'woo_ua' ), $product ) ); ?></a></p>
				<?php

			}  // buyers and stripe both active
			elseif(in_array('uwa_buyers_premium_addon', $addons) &&
				in_array('uwa_stripe_auto_debit_addon', $addons)){
				?>
                	<!-- Buyer's premium for auction product is auto debited via stripe -->

                <?php

               		//uwa_get_stripe_details($product);
                  /******* REPLACE CODE OF stripe FUNCTION HERE - START ********/


                  		$w_current_userid = $product->get_uwa_auction_current_bider();
						$w_product_price  = $product->get_uwa_auction_current_bid();

				        $get_charged_for_winner = get_option(
							"_uwa_w_s_charge_".$product->get_id()."_".$w_current_userid,
							false);

				      $uwa_stripe_auto_debit_tax = get_post_meta( $product->get_id(),
				      	'_uwa_stripe_auto_debit_tax', true);


						if( $get_charged_for_winner > 0 ){
						    echo '<p><strong>' . esc_html__( 'Auto Debit via Credit Card Details :', 'woo_ua' ) . '</strong>';

							$stripe_amt      = get_post_meta( $product->get_id(), '_uwa_stripe_auto_debit_amt', true);
							$stripe_buyer_amt = get_post_meta( $product->get_id(), '_uwa_stripe_auto_debit_bpm_amt', true);
							if(!empty($stripe_amt) && $stripe_amt > 0) {
								$by_pre_txt2   = esc_html__( 'Bid Amount : ', 'woo_ua' );
								$get_win_price2 = wc_price($stripe_amt);
								echo '<br><strong>' . $by_pre_txt2 . '</strong>' . wp_kses_post( $get_win_price2 );
							}

							if(!empty($stripe_buyer_amt) && $stripe_buyer_amt > 0) {
								$by_pre_txt1   = esc_html__( "Buyer's Premium : ", 'woo_ua' );
								$get_win_price1 = wc_price($stripe_buyer_amt);
								echo '<br><strong>' . $by_pre_txt1 . '</strong>' . wp_kses_post( $get_win_price1 );
							}

							if(!empty($uwa_stripe_auto_debit_tax) && $uwa_stripe_auto_debit_tax > 0) {
								$by_pre_txt1   = esc_html__( 'Tax: ', 'woo_ua' );
								$get_win_price1 = wc_price($uwa_stripe_auto_debit_tax);
								echo '<br><strong>' . $by_pre_txt1 . '</strong>' . esc_html( $uwa_stripe_auto_debit_tax );
							}

							if(!empty($get_charged_for_winner) && $get_charged_for_winner > 0) {
								$debit_txt      = esc_html__( 'Total : ', 'woo_ua' );
								$get_debit_price = wc_price($get_charged_for_winner);
								echo '<br><strong>' . $debit_txt . '</strong>' . wp_kses_post( $get_debit_price );
							}
							echo '</p>';

						} /* end of if */

				/******* REPLACE CODE OF stripe FUNCTION HERE - END ********/
            }
            elseif(!in_array('uwa_buyers_premium_addon', $addons) &&
				in_array('uwa_stripe_auto_debit_addon', $addons)){

            		//uwa_get_stripe_details($product);

            	/******* REPLACE CODE OF stripe FUNCTION HERE - START ********/


                  	$w_current_userid = $product->get_uwa_auction_current_bider();
					$w_product_price  = $product->get_uwa_auction_current_bid();

				    $get_charged_for_winner = get_option(
						"_uwa_w_s_charge_".$product->get_id()."_".$w_current_userid,
						false);

					if( $get_charged_for_winner > 0 ){
					    echo '<p><strong>' . esc_html__( 'Auto Debit via Credit Card Details :', 'woo_ua' ) . '</strong>';

						$stripe_amt      = get_post_meta( $product->get_id(), '_uwa_stripe_auto_debit_amt', true);
						$stripe_buyer_amt = get_post_meta( $product->get_id(), '_uwa_stripe_auto_debit_bpm_amt', true);
						if(!empty($stripe_amt) && $stripe_amt > 0) {
							$by_pre_txt2   = esc_html__( 'Bid Amount : ', 'woo_ua' );
							$get_win_price2 = wc_price($stripe_amt);
							echo '<br><strong>' . $by_pre_txt2 . '</strong>' . wp_kses_post( $get_win_price2 );
						}

						if(!empty($stripe_buyer_amt) && $stripe_buyer_amt > 0) {
							$by_pre_txt1   = esc_html__( "Buyer's Premium : ", 'woo_ua' );
							$get_win_price1 = wc_price($stripe_buyer_amt);
							echo '<br><strong>' . $by_pre_txt1 . '</strong>' . wp_kses_post( $get_win_price1 );
						}

						if(!empty($get_charged_for_winner) && $get_charged_for_winner > 0) {
							$debit_txt      = esc_html__( 'Total : ', 'woo_ua' );
							$get_debit_price = wc_price($get_charged_for_winner);
							echo '<br><strong>' . $debit_txt . '</strong>' . wp_kses_post( $get_debit_price );
						}
						echo '</p>';

					} /* end of if */

				/******* REPLACE CODE OF stripe FUNCTION HERE - END ********/
            }

		}
		else{

			/* general */

			$w_current_userid = $product->get_uwa_auction_current_bider();
			$w_product_price  = $product->get_uwa_auction_current_bid();

			$uwa_stripe_auto_debit_tax = get_post_meta( $product->get_id(), '_uwa_stripe_auto_debit_tax', true);

			?>
				<p>
					<?php esc_html_e( 'Congratulations! You have won this auction.', 'woo_ua' ); ?>
				</p>

			<?php
				$get_charged_for_winner = get_option(
					"_uwa_w_s_charge_".$product->get_id()."_".$w_current_userid,
					false);

				if(!empty($get_charged_for_winner) || $get_charged_for_winner > 0 ){

					$aelia_addon = '';
					if(is_array($addons) && in_array('uwa_currency_switcher', $addons)){
						if($product->uwa_aelia_is_configure() == TRUE){
							$aelia_addon = true;
						}
					}


				    echo '<p><strong>' . esc_html__( 'Auto Debit via Credit Card Details :', 'woo_ua' ) . '</strong>';

					$stripe_amt      = get_post_meta( $product->get_id(), '_uwa_stripe_auto_debit_amt', true);
					$stripe_buyer_amt = get_post_meta( $product->get_id(), '_uwa_stripe_auto_debit_bpm_amt', true);
					if(!empty($stripe_amt) && $stripe_amt > 0) {
						$by_pre_txt2 = esc_html__( 'Bid Amount : ', 'woo_ua' );

						if($aelia_addon == true){
        					$stripe_amt = $product->uwa_aelia_base_to_active($stripe_amt);
        				}

						$get_win_price2 = wc_price($stripe_amt);
						echo '<br><strong>' . $by_pre_txt2 . '</strong>' . wp_kses_post( $get_win_price2 );
					}

					if(!empty($stripe_buyer_amt) && $stripe_buyer_amt > 0) {
						$by_pre_txt1 = esc_html__( "Buyer's Premium : ", 'woo_ua' );

						if($aelia_addon == true){
        					$stripe_buyer_amt = $product->uwa_aelia_base_to_active($stripe_buyer_amt);
        				}

						$get_win_price1 = wc_price($stripe_buyer_amt);
						echo '<br><strong>' . $by_pre_txt1 . '</strong>' . wp_kses_post( $get_win_price1 );
					}

					if(!empty($uwa_stripe_auto_debit_tax) && $uwa_stripe_auto_debit_tax > 0) {
						$by_pre_txt1   = esc_html__( 'Tax : ', 'woo_ua' );
						$get_win_price1 = wc_price($uwa_stripe_auto_debit_tax);
						echo '<br><strong>' . $by_pre_txt1 . '</strong>' . wp_kses_post( $get_win_price1 );
					}


					if(!empty($get_charged_for_winner) && $get_charged_for_winner > 0) {
						$debit_txt = esc_html__( 'Total : ', 'woo_ua' );

						if($aelia_addon == true){
        					$get_charged_for_winner = $product->uwa_aelia_base_to_active($get_charged_for_winner);
        				}

						$get_debit_price = wc_price($get_charged_for_winner);
						echo '<br><strong>' . $debit_txt . '</strong>' . wp_kses_post( $get_debit_price );
					}
					echo '</p>';
				}

				if($get_charged_for_winner == $w_product_price ||
					$get_charged_for_winner > $w_product_price ){

						do_action('ultimate_woocommerce_auction_before_pay_now_button', $product);

						?>

			    	<a href="<?php echo esc_url( $checkout_url ); ?>" class="button alt uwa_pay_now">
						<?php echo esc_html( apply_filters( 'ultimate_woocommerce_auction_pay_now_button_text', __( 'Get Item', 'woo_ua' ), $product ) ); ?></a>

					<?php
				}
				else {

						do_action('ultimate_woocommerce_auction_before_pay_now_button', $product);

					?>
			    	<a href="<?php echo esc_url( $checkout_url ); ?>" class="button alt uwa_pay_now">
						<?php echo esc_html( apply_filters( 'ultimate_woocommerce_auction_pay_now_button_text', __( 'Pay Now', 'woo_ua' ), $product ) ); ?></a>
					<?php
				}


		} /* end of else - general */


} /* end of if - auction payed */
