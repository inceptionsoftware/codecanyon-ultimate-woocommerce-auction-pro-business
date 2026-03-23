<?php
/**
 * main functions for plugin
 *
 * @package Ultimate WooCommerce Auction PRO
 * @author Nitesh Singh 
 * @since 1.0
 *	
 */
 
	/* Callback to change the auction status */
	add_action('scheduled_process_auction', 'woo_ua_process_auction', 10);
	add_action('scheduled_ending_soon_email', 'woo_ua_ending_soon_email', 10);
	add_action('scheduled_auto_relist', 'woo_ua_auto_relist', 10);
	add_action('scheduled_payment_reminder_email', 'woo_ua_payment_reminder_email', 10);
	add_action('scheduled_ending_soon_sms', 'woo_ua_ending_soon_sms', 10);

		function woo_ua_process_auction(){
			update_option( 'uwa_process_auction_cron', 'yes' );
			$meta_query= array(	array('key'  => 'woo_ua_auction_closed',	'compare' => 'NOT EXISTS'),
				array('key' => 'woo_ua_auction_has_started','compare' =>'==', 'value'=>'1'),);
				
				$args = array(
					'post_type' => 'product',
					'posts_per_page' => -1,
					'meta_query'=> $meta_query,
					'meta_key' => 'woo_ua_auction_end_date',
					'orderby' => 'meta_value',
					'order' => 'ASC',
					'tax_query' => array(array('taxonomy' => 'product_type', 'field' => 'slug', 'terms' => 'auction')),
					'auction_arhive' => TRUE,
					'show_past_auctions' => TRUE,
					'show_future_auctions' => TRUE,
				);

				$the_query = new WP_Query($args);						
				if ($the_query->have_posts()) {
					while ($the_query->have_posts()): $the_query->the_post();
						$product_data = wc_get_product($the_query->post->ID);
						if (method_exists( $product_data, 'get_type') && $product_data->get_type() == 'auction' ) {
							$product_data->is_uwa_expired(); // this goes to is_uwa_expired function make change as per this function.
						}
					endwhile;
				}	
			
		}
		
		/*
			End Ending soon mail Hook 
		*/
			
		function woo_ua_ending_soon_email(){
			
			update_option( 'uwa_ending_soon_email_cron', 'yes' );
			$uwa_ending_soon = get_option( 'woocommerce_woo_ua_email_auction_ending_bidders_settings' );    

			if ( $uwa_ending_soon['enabled'] === 'yes' ) {
				$uwa_interval = $uwa_ending_soon['uwa_interval'];
				$uwa_interval_time = date( 'Y-m-d H:i', current_time( 'timestamp' ) + ( $uwa_interval * HOUR_IN_SECONDS ) );						
				$args = array(
							'post_type'          => 'product',
							'posts_per_page'     => '100', 
							'tax_query'          => array(
								array(
									'taxonomy' => 'product_type',
									'field'    => 'slug',
									'terms'    => 'auction',
								),
							),
							'meta_query'         => array(
								'relation' => 'AND',        
								array(
									'key'     => 'woo_ua_auction_has_started',
									'value' => '1',
								),                            
								array(
									'key'     => 'woo_ua_auction_closed',
									'compare' => 'NOT EXISTS',
								),
								array(
										'key'     => 'uwa_auction_sent_ending_soon',									
										'compare' => 'NOT EXISTS',
								),
								array(
									'key'     => 'woo_ua_auction_end_date',
									'compare' => '<',
									'value'   => $uwa_interval_time,
									'type '   => 'DATETIME',
								),
								
							),                        
						);

				$the_query = new WP_Query( $args );           
				if ( $the_query->have_posts() ) {
					while ( $the_query->have_posts() ) :
						$the_query->the_post();	
						$product_data = wc_get_product( $the_query->post->ID );
						$now_timestamp = current_time( "timestamp");
						WC()->mailer();
						add_post_meta( $the_query->post->ID, 'uwa_auction_sent_ending_soon', $now_timestamp, true );
						do_action( 'woo_ua_auctions_ending_soon_email_bidders', $the_query->post->ID);	
						
					endwhile;
					wp_reset_postdata();
				}
							
					   
				
			} /* end of if - uwa_enabled_bidders */
						
		} /* End Ending soon mail  */
				
		/* 
		Payment Reminder Hook
		*/
		function woo_ua_payment_reminder_email(){
			update_option( 'uwa_payment_reminder_email_cron', 'yes' );
			$remind_to_payment = get_option( 'woocommerce_woo_ua_email_auction_remind_to_pay_settings' );

			if ( $remind_to_payment['enabled'] === 'yes' ) {
					
			$uwa_interval    = ( ! empty( $remind_to_payment['uwa_interval'] ) ) ? (int) $remind_to_payment['uwa_interval'] : 5;
			$uwa_stopsending = ( ! empty( $remind_to_payment['uwa_stopsending'] ) ) ? (int) $remind_to_payment['uwa_stopsending'] : 4;
			$args        = array(
							'post_type'          => 'product',
							'posts_per_page'     => '-1',
							'show_past_auctions' => true,
							'tax_query'          => array(
								array(
									'taxonomy' => 'product_type',
									'field'    => 'slug',
									'terms'    => 'auction',
								),
							),
							'meta_query'         => array(
								'relation' => 'AND',
								array(
									'key'   => 'woo_ua_auction_closed',
									'value' => '2',
								),
								array(
									'key'     => 'woo_ua_auction_payed',
									'compare' => 'NOT EXISTS',
								),
								array(
									'key'     => 'uwa_auction_stop_mails',
									'compare' => 'NOT EXISTS',
								),
							),
							'auction_arhive'     => true,
							'show_past_auctions' => true,
						);


						$the_query = new WP_Query( $args );

						if ( $the_query->have_posts() ) {

							while ( $the_query->have_posts() ) :
								$the_query->the_post();
								$no_of_sent_mail = get_post_meta( $the_query->post->ID, 'uwa_number_of_sent_mails', true );
								$sent_mail_dates  = get_post_meta( $the_query->post->ID, 'uwa_dates_of_sent_mails', false );
								$no_days              = (int) $remind_to_payment['uwa_interval'];

								$product_data = wc_get_product( $the_query->post->ID );

								if ( (int) $no_of_sent_mail >= $uwa_stopsending ) {
									update_post_meta( $the_query->post->ID, 'uwa_auction_stop_mails', '1' );

								} elseif ( ( ! $sent_mail_dates or ( (int) end( $sent_mail_dates ) > strtotime( '-' . $uwa_interval . ' days' ) ) ) or ( strtotime( $product_data->get_uwa_auction_end_dates() ) > strtotime( '-' . $uwa_interval . ' days' ) ) ) {

									update_post_meta( $the_query->post->ID, 'uwa_number_of_sent_mails', (int)$no_of_sent_mail + 1 );
									add_post_meta( $the_query->post->ID, 'uwa_dates_of_sent_mails', time(), false );											

									WC()->mailer();
									do_action( 'uwa_email_remind_to_pay_notification', $the_query->post->ID );
								}

							endwhile;
							wp_reset_postdata();
						}		
				} 
			
		}/* End Payment Reminder cron*/
		
		/* 
		 Auto Relist Hook
		*/
		function woo_ua_auto_relist(){		
			update_option( 'uwa_auto_relist_cron', 'yes' );
			$args = array(
				'post_type'          => 'product',
				'posts_per_page'     => '200',												
				'tax_query'          => array(
					array(
						'taxonomy' => 'product_type',
						'field'    => 'slug',
						'terms'    => 'auction',
					),
				),
				'meta_query'         => array(
					'relation' => 'AND',

					array(
						'key'     => 'woo_ua_auction_closed',
						'compare' => 'EXISTS',
					),
					array(
						'key'     => 'woo_ua_auction_payed',
						'compare' => 'NOT EXISTS',
					),
					array(
						'key'   => 'uwa_auto_renew_enable',
						'value' => 'yes',
					),
				),
				
			);

			$the_query = new WP_Query( $args );

			if ( $the_query->have_posts() ) {

				while ( $the_query->have_posts() ) {

					$the_query->the_post();
					/*$this->uwa_auto_renew_auction( $the_query->post->ID );*/
					$UWA_relist = new UWA_Admin;
					$UWA_relist->uwa_auto_renew_auction( $the_query->post->ID );

				}

				wp_reset_postdata();
			}
			
		}/* End Auto relist cron*/

		function woo_ua_ending_soon_sms() {
			update_option('uwa_sms_ending_soon_cron', 'yes');

			$addons = uwa_enabled_addons();
			if(in_array('uwa_twilio_sms_addon', $addons)){

				$uwa_twilio_sms_ending_soon_enabled = get_option('uwa_twilio_sms_ending_soon_enabled');
				if($uwa_twilio_sms_ending_soon_enabled == "yes"){

					global $woocommerce, $wpdb, $post;

					$uwa_interval =  get_option('uwa_twilio_sms_ending_soon_time', 1);				
					$uwa_interval_time = date( 'Y-m-d H:i', current_time('timestamp') + 
						($uwa_interval * HOUR_IN_SECONDS));

					// get auction which are live, and then matched interval with end date
					$args = array(
							'post_type'          => 'product',
							'posts_per_page'     => '100',                        
							//'posts_per_page'     => '-1',                        
							'tax_query'          => array(
								array(
									'taxonomy' => 'product_type',
									'field'    => 'slug',
									'terms'    => 'auction',
								),
							),
							'meta_query'         => array(
								'relation' => 'AND',        
								array(
									'key'     => 'woo_ua_auction_has_started',
									'value' => '1',
								),                            
								array(
									'key'     => 'woo_ua_auction_closed',
									'compare' => 'NOT EXISTS',
								),
								array(
										'key'     => 'uwa_auction_sent_ending_soon_sms',
										'compare' => 'NOT EXISTS',
								),
								array(
									'key'     => 'woo_ua_auction_end_date',
									'compare' => '<',
									'value'   => $uwa_interval_time,
									'type '   => 'DATETIME',
								),
								
							),                        
						);

						$the_query = new WP_Query( $args );           
						if ( $the_query->have_posts() ) {
							while ( $the_query->have_posts() ) :
								$the_query->the_post();	
								$product_data = wc_get_product( $the_query->post->ID );
								$now_timestamp = current_time( "timestamp");			
								add_post_meta( $the_query->post->ID, 'uwa_auction_sent_ending_soon_sms', $now_timestamp, true );
								/*uwa_twilio_send_sms_to_ending_soon( $the_query->post->ID );*/

								$product_id = $the_query->post->ID;

								/* ------------------------------------------------ */

								if ($product_id) {								
										$message = "";
										 //Get all participates 
										$final_userlist = array();	
										$ending_auction_users = $wpdb->get_results("SELECT DISTINCT userid  FROM ". 
											$wpdb->prefix ."woo_ua_auction_log WHERE auction_id = ". $product_id, OBJECT_K); //ARRAY_A

										if(count($ending_auction_users) > 0){
											$arr_ending_auction_users = array_keys($ending_auction_users);
											$final_auction_users[$product_id] =  $arr_ending_auction_users;
											$final_userlist = $arr_ending_auction_users;
										}
										
										$total_users = count($final_userlist);
										if ( $total_users > 0 ) {
											$billing_country = "";
											$uwa_twilio_sms_sid = get_option('uwa_twilio_sms_sid');
											$uwa_twilio_sms_token = get_option('uwa_twilio_sms_token');
											$uwa_twilio_sms_from_number = get_option('uwa_twilio_sms_from_number');	
												
											$product = wc_get_product($product_id);
											$product_id =  $product->get_id();
											$auction_title = $product->get_title();
											$link = get_permalink($product->get_id()); 
											
											$uwa_message_pp = get_option('uwa_twilio_sms_ending_soon_template',"Auction id {product_id}, title {product_name} will be expiring soon. Place your highest bid to win it.");
											
											$uwa_message_pp = str_replace('{product_id}', $product_id, $uwa_message_pp);
											$uwa_message_pp = str_replace('{product_name}', $auction_title, $uwa_message_pp);
											$uwa_message_pp = str_replace('{link}', $link, $uwa_message_pp);	
											$message .= $uwa_message_pp; 
											 
											foreach ( $final_userlist as $key => $value) {
												$customer_id = $value;
												$uwa_sms_ending_soon_user_enabled = get_user_meta($customer_id,'uwa_sms_ending_soon_user_enabled', true);
												if($uwa_sms_ending_soon_user_enabled == "no" )
												{
													continue;
												}
												$ctm_phone = get_user_meta( $customer_id, 'billing_phone', true );
												$billing_country = get_user_meta( $customer_id, 'billing_country', true );	
												$to = uwa_twilio_sm_format_e164( $ctm_phone, $billing_country );

													require_once ( UW_AUCTION_PRO_ADDONS .
														'twilio_sms/lib/Twilio/autoload.php' );
													$client = new Twilio\Rest\Client( $uwa_twilio_sms_sid, 
														$uwa_twilio_sms_token);

													try {
														$fmessage = $client->messages->create( $to, array( 
															'from' => $uwa_twilio_sms_from_number, 
															'body' => $message ) );
													} 
													catch( \Exception $e ) {
														$response['message'] =  $e->getMessage();
														
													uwa_create_log("SMS Sent Ending Soon Error: " . $e->getMessage()." Auction ID=".$product_id);	
													}
												
											} /* end of foreach */
										} /* end of if total users */		
								}

								/* ------------------------------------------------ */

								
							endwhile;
							wp_reset_postdata();
						}						


				} /* end of if - sms enabled */
			} /* end of if - sms addon enabled */
					
		} /* End Ending soon SMS cron */
				