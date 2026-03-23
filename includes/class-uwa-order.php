<?php

/**
 * Class for order create
 * Uat_Auction_Orders Main class
 */

// Exit if accessed directly
if (!defined('ABSPATH')) exit;

class UWA_Auction_Orders{

	public function __construct(){
		
	}

	public function uwa_single_product_order($product_id){

		global $woocommerce;

		if($product_id > 0){
		
			$order_status =  get_post_meta($product_id, 'woo_ua_auto_order_status', true);
			
			if(!empty($order_status) && $order_status == 'created'){
				return;
			}


			/* $product_id = "26171"; */
			$product = wc_get_product($product_id);

			if (method_exists($product, 'get_type') && $product->get_type() == 'auction') {

				$winner_id = $product->get_uwa_auction_current_bider();
				$expired_auct = $product->get_uwa_auction_expired();			

				if($expired_auct == 2 && $winner_id > 0 ){

					$customer_id = $winner_id;
					$product_title = $product->get_title();


					$addons = uwa_enabled_addons();
					$_uwa_stripe_auto_debit_tax = "";
					
				
					$country = "";
					$first_name = get_user_meta($customer_id, 'billing_first_name', true);
					$last_name = get_user_meta($customer_id, 'billing_last_name', true);
					$company = get_user_meta($customer_id, 'billing_company', true);
					$email = get_user_meta($customer_id, 'billing_email', true);
					$add_1 = get_user_meta($customer_id, 'billing_address_1', true);
					$add_2 = get_user_meta($customer_id, 'billing_address_2', true);
					$city = get_user_meta($customer_id, 'billing_city', true);
					$state = get_user_meta($customer_id, 'billing_state', true);
					$postcode = get_user_meta($customer_id, 'billing_postcode', true);
					$phone = get_user_meta($customer_id, 'billing_phone', true);
					$cntry_code = get_user_meta($customer_id, 'billing_country', true);

					if($cntry_code){
						$country = WC()->countries->countries[$cntry_code].
						"  ($cntry_code)";
					}
					
					/* create new order */
				
					$address = array(
						'first_name' => $first_name,
						'last_name'  => $last_name,
						'company'    => $company,
						'email'      => $email,
						'phone'      => $phone,
						'address_1'  => $add_1,
						'address_2'  => $add_2,
						'city'       => $city,						
						'state'      => $state,
						'postcode'   => $postcode,
						'country'    => $cntry_code,
					);

					$shipping_address = $address;
					$billing_address = $address;
					
					$order = wc_create_order();
					$product_item_id = $order->add_product(wc_get_product($product_id), 1);
					$order->set_address($billing_address, 'billing');
					$order->set_address($shipping_address, 'shipping');
					$order->set_customer_id($customer_id);
					$order_id = $order->get_id();


					/* add buyers premium to product order meta */
					$buyers_premium = get_post_meta($product_id, '_uwa_buyer_premium_amt', true);
					if( !empty( $buyers_premium ) ) {
						wc_add_order_item_meta($product_item_id, '_uwa_buyer_premium_order_amt', 
							$buyers_premium, true);						
					}


					
					if (in_array('uwa_stripe_auto_debit_addon', $addons)) {					

						/**** added here *****/

						/* add tax amount to product order meta */
						$_uwa_stripe_auto_debit_tax = get_post_meta($product_id, '_uwa_stripe_auto_debit_tax', true);
						if ( !empty( $_uwa_stripe_auto_debit_tax ) ) {
							wc_add_order_item_meta($product_item_id, '_uwa_stripe_auto_debit_tax_amt', 
								$_uwa_stripe_auto_debit_tax, true);
						}					

						/**** added here *****/
					}

					/* add autodebit values to product order meta */
					$w_current_userid = $product->get_uwa_auction_current_bider();
					$stripe_auto_debit_amt = get_option(
						"_uwa_w_s_charge_".$product_id."_".$w_current_userid, false);
					$stripe_amt = get_post_meta($product_id, "_uwa_stripe_auto_debit_amt", true);
					$stripe_buyer_amt = get_post_meta($product_id, '_uwa_stripe_auto_debit_bpm_amt', 
						true);

					
					if(!empty($stripe_auto_debit_amt)) {						
						wc_update_order_item_meta($product_item_id, '_uwa_stripe_charge_order_total_amt', 
							$stripe_auto_debit_amt, true);
					}
					if(!empty($stripe_amt)) {
						wc_update_order_item_meta($product_item_id, '_uwa_stripe_charge_order_amt', 
							$stripe_amt, true);
					}					
					if(!empty( $stripe_buyer_amt)) {
						wc_update_order_item_meta($product_item_id, 
							'_uwa_stripe_charge_buyer_premium_order_amt', $stripe_buyer_amt, true);
					}
					
					//$addons = uwa_enabled_addons();
					//if(in_array('uwa_buyers_premium_addon', $addons)){

					/* add lineitems(buyer's premium and autodebit) to order */
					if(!empty($buyers_premium)){
						/* buyer's premium fee title */

						$uwa_pro_bp_text = "";		
						$uwa_buyers_fee_title = get_option('uwa_buyers_fee_title', "fee_product_title");

						$uwa_pro_sku = $product->get_sku();
						$uwa_pro_name = trim($product->get_name());
						$uwa_pro_name_len = strlen($uwa_pro_name);
						

						if($uwa_buyers_fee_title == "fee_sku"){						
							if(empty($uwa_pro_sku)){
								$uwa_pro_bp_text = $uwa_pro_name;
							}
							else{
								$uwa_pro_bp_text = $uwa_pro_sku;						
							}
						}
						else if($uwa_buyers_fee_title == "fee_product_title"){

								$uwa_pro_bp_text = $uwa_pro_name;

						}
						elseif($uwa_buyers_fee_title == "fee_trim_product_name"){
							if($uwa_pro_name_len > 60){
								$uwa_pro_trimmed = substr($uwa_pro_name, 0, 60);
								$uwa_pro_bp_text = $uwa_pro_trimmed. "...";
							}
							else{
								$uwa_pro_bp_text = $uwa_pro_name;
							}
						}	


						$bp_item_id = wc_add_order_item( $order_id, array(								
							'order_item_name' => __("Buyer's Premium Amount for - "). $uwa_pro_bp_text,
							'order_item_type' => 'fee',
						));
						wc_add_order_item_meta( $bp_item_id, '_fee_amount', $buyers_premium, true );
						wc_add_order_item_meta( $bp_item_id, '_line_total', $buyers_premium, true );

					}

					if (!empty($stripe_auto_debit_amt)) {

						$autodebit_item_id = wc_add_order_item( $order_id, array(
							'order_item_name' => "Auto Debit Via Stripe",
							'order_item_type' => 'fee',
						));			
						wc_add_order_item_meta( $autodebit_item_id, '_fee_amount', 
							- $stripe_auto_debit_amt, true );
						wc_add_order_item_meta( $autodebit_item_id, '_line_total', 
							- $stripe_auto_debit_amt, true );
					}

					if (in_array('uwa_stripe_auto_debit_addon', $addons)) {		
					
							if (!empty($_uwa_stripe_auto_debit_tax)) {

								/**** added here *****/

								$uwa_tax_item_id = wc_add_order_item( $order_id, array(								
									'order_item_name' => __("Tax - "). $uwa_pro_bp_text,
									'order_item_type' => 'fee',
								));						
								wc_add_order_item_meta( $uwa_tax_item_id, '_fee_amount',
								 	- $_uwa_stripe_auto_debit_tax, true );
								wc_add_order_item_meta( $uwa_tax_item_id, '_line_total', 
									- $_uwa_stripe_auto_debit_tax, true ); 

								/**** added here *****/
							}
					}


					$calculate_taxes_for = array(
						'country'  => !empty($shipping_address['country']) ? $shipping_address['country'] : $billing_address['country'],
						'state'    => !empty($shipping_address['state']) ? $shipping_address['state'] : 
							$billing_address['state'],
						'postcode' => !empty($shipping_address['postcode']) ? $shipping_address['postcode'] : $billing_address['postcode'],
						'city'     => !empty($shipping_address['city']) ? $shipping_address['city'] : 
							$billing_address['city'],
					);


					/* Add fixed shipping rate when shipping enabled */
					/*$shipping_val = get_option('woocommerce_ship_to_countries', false);
					if($shipping_val != 'disabled'){
 					
						$shipping_taxes = WC_Tax::calc_shipping_tax('10', 
							WC_Tax::get_shipping_tax_rates());
						$rate   = new WC_Shipping_Rate('flat_rate_shipping', 'Flat rate shipping', '10', 
							$shipping_taxes, 'flat_rate');

						$item   = new WC_Order_Item_Shipping();
						$item->set_props(array(
							'method_title' => $rate->label,
							'method_id'    => $rate->id,
							'total'        => wc_format_decimal($rate->cost),
							'taxes'        => $rate->taxes,
						));
						foreach ($rate->get_meta_data() as $key => $value) {
							$item->add_meta_data($key, $value, true);
						}					
						$order->add_item($item);

					} */ /* end of shipping */

					$order->calculate_totals($calculate_taxes_for);
					$order->update_status("wc-pending", "Pending ORDER", TRUE);

					$woo_tax_enabled = get_option("woocommerce_calc_taxes");
					if($woo_tax_enabled == "yes"){

							$tax_status = $product->get_tax_status();
							if($tax_status == "taxable" && !empty($stripe_auto_debit_amt)){

								/* add autodebit tax in total amount of order */

								/*$debit_tax_value = wc_get_order_item_meta($autodebit_item_id, '_line_tax', true);
								$debit_tax_value1 = abs($debit_tax_value);*/
								$linetaxdata = wc_get_order_item_meta($autodebit_item_id, '_line_tax_data');

								if(is_array($linetaxdata) && isset($linetaxdata['total'])){

										$i = 0;
										if ($linetaxdata['total'] >= 1){
											foreach($linetaxdata['total'] as $key => &$value){
												$value = abs($value);
												$all_tax[$i] = $value;
												$i++;
											}
										}						

										$j = 0;
										$add_in_total = 0;
										if(count($order->get_tax_totals()) >= 1){
											foreach ($order->get_tax_totals() as $key => $value){
												$tax_id = $value->id;
												$tax = wc_get_order_item_meta($tax_id, 'tax_amount');
												$new_tax = $tax + $all_tax[$j];
												wc_update_order_item_meta($tax_id,  'tax_amount',  
													$new_tax);
												$add_in_total += $all_tax[$j];
												$j++;
											}
										}
										
										/*$total =  get_post_meta($order_id, '_order_total', true);
										$new_total = $total + $add_in_total;						
										update_post_meta($order_id, '_order_total', $new_total);					
										$total =  get_post_meta($order_id, '_order_total', true);*/

										/* $total = $order->get_meta_data('_order_total');
										$new_total = $total + $add_in_total;
										$order->update_meta_data('_order_total', $new_total);
										$order->save(); */

										$total = $order->get_meta_data('_order_total');
										$total = is_numeric($total) ? (float)$total : 0;
										$add_in_total = is_numeric($add_in_total) ? (float)$add_in_total : 0;
										$new_total = $total + $add_in_total;

										$order->update_meta_data('_order_total', $new_total);
										$order->save();

								} /* end of if - linetaxdata */


							}


							if($tax_status == "none" || $tax_status == "shipping"){

								/* remove buyers tax from order */

								$linetaxdata = wc_get_order_item_meta($bp_item_id, '_line_tax_data');

								if(is_array($linetaxdata) && isset($linetaxdata['total'])){

										$i = 0;
										foreach($linetaxdata['total'] as $key => &$value){
												//$value = abs($value);
												$all_tax[$i] = $value;
												$i++;
										}

										$add_in_total  = 0;
										$j = 0;
										if (count($order->get_tax_totals()) > 1){
											foreach ($order->get_tax_totals() as $key => $value){
												
												$tax_id = $value->id;
												$tax = wc_get_order_item_meta($tax_id,  'tax_amount'); 
												$minus_from_total += $tax;
												$test = wc_update_order_item_meta($tax_id,  'tax_amount',  
													0);
											}			
										}
										
										/*$total =  get_post_meta($order_id, '_order_total', true);
										$new_total = $total - $minus_from_total; 
										update_post_meta($order_id, '_order_total', $new_total);
										$total =  get_post_meta($order_id, '_order_total', true);*/

										$total = $order->get_meta_data('_order_total');
										$new_total = $total - $minus_from_total;
										$order->update_meta_data('_order_total', $new_total);
										$order->save();

								} /* end of if - linetaxdata */

							}

					} /* end of if - woo_tax_enabled */	
			
					$order->save();

					update_post_meta($product_id, "woo_ua_order_id", $order_id);
					update_post_meta($product_id, "woo_ua_auto_order_status", "created");
					
					if($woo_tax_enabled == "yes"){

							if(!empty($stripe_auto_debit_amt)){

								/* deltete auto debit tax meta keys */
								wc_delete_order_item_meta($autodebit_item_id, '_line_tax');
								wc_delete_order_item_meta($autodebit_item_id, '_line_tax_data');

							}


							if (in_array('uwa_stripe_auto_debit_addon', $addons)) {	

								if (!empty($_uwa_stripe_auto_debit_tax)) {

									/* deltete tax for debited tax meta keys */
									wc_delete_order_item_meta($uwa_tax_item_id, '_line_tax');
									wc_delete_order_item_meta($uwa_tax_item_id, '_line_tax_data');

								}
							}
														

							if($tax_status == "none" || $tax_status == "shipping"){

								/* deltete buyers tax meta keys */
								wc_delete_order_item_meta($bp_item_id, '_line_tax');
								wc_delete_order_item_meta($bp_item_id, '_line_tax_data');

							}

					} /* end of if - woo_tax_enabled */	

								
				} /* end of if - winnerid */

			} /* end of if - get type */

		} /* end of if - productid > 0 */

	} /* end of function */


} /* end of class */


