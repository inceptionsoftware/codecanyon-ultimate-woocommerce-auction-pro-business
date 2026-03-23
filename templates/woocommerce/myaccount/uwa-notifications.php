<?php

/**
 * My auctions tab list
 * 
 * @package Ultimate WooCommerce Auction PRO
 * @author Nitesh Singh 
 * @since 1.0  
 *
 */

if (!defined('ABSPATH')) {
    exit;
}

    $user_id  = get_current_user_id();  
    
    
    
?>
    <div class="uwa-notification-page-my-account">
        
        <h2 class="uwa-notification-title"><?php _e('Notifications', 'woo_ua'); ?></h2>
        
        
        <?php 
            $uwa_twilio_sms_addon = "off";
           
      
            $uwa_enabled_addons_list = get_option('uwa_addons_options_enabled');
            if(!empty($uwa_enabled_addons_list)){
                if(in_array('uwa_twilio_sms_addon', $uwa_enabled_addons_list)) {
                   $uwa_twilio_sms_addon = "on";
                }
            }

            /*SMS Notifications globle options variables start*/ 
            $uwa_twilio_sms_placed_bid_enabled = get_option('uwa_twilio_sms_placed_bid_enabled');
            $uwa_twilio_sms_outbid_enabled = get_option('uwa_twilio_sms_outbid_enabled');
            $uwa_twilio_sms_won_enabled = get_option('uwa_twilio_sms_won_enabled');
            $uwa_twilio_sms_ending_soon_enabled = get_option('uwa_twilio_sms_ending_soon_enabled');
            /*SMS Notifications globle options variables end*/ 

             /*SMS Notifications globle options variables start*/ 
             $uwa_whatsapp_msg_outbid_enabled = get_option('uwa_whatsapp_msg_outbid_enabled');
             $uwa_whatsapp_msg_won_enabled = get_option('uwa_whatsapp_msg_won_enabled');
             $uwa_whatsapp_msg_ending_soon_enabled = get_option('uwa_whatsapp_msg_ending_soon_enabled');
             /*SMS Notifications globle options variables end*/

                    if (isset($_POST['uwa-settings-submit']) == 'Save Changes') {
                        /*SMS Notifications save start*/ 
                        if($uwa_twilio_sms_addon == "on"){
                            if (isset($_POST['uwa_sms_placebid_user_enabled'])) {
                                update_user_meta( $user_id, 'uwa_sms_placebid_user_enabled', "yes" );
                            } else {
                                update_user_meta( $user_id, 'uwa_sms_placebid_user_enabled', "no");
                            }
                            if (isset($_POST['uwa_sms_outbid_user_enabled'])) {
                                update_user_meta( $user_id, 'uwa_sms_outbid_user_enabled', "yes" );
                            } else {
                                update_user_meta( $user_id, 'uwa_sms_outbid_user_enabled', "no");
                            }
                            if (isset($_POST['uwa_sms_won_user_enabled'])) {
                                update_user_meta( $user_id, 'uwa_sms_won_user_enabled', "yes" );
                            } else {
                                update_user_meta( $user_id, 'uwa_sms_won_user_enabled', "no");
                            }
                            if (isset($_POST['uwa_sms_ending_soon_user_enabled'])) {
                                update_user_meta( $user_id, 'uwa_sms_ending_soon_user_enabled', "yes" );
                            } else {
                                update_user_meta( $user_id, 'uwa_sms_ending_soon_user_enabled', "no");
                            }
                        }
                         /*SMS Notifications save end*/ 
                        
                    } /* end of if - save changes */

                    
                    
                    
                    /*SMS Notifications check box variables start*/ 
                    if($uwa_twilio_sms_addon == "on"){
                        $uwa_sms_placebid_user_enabled = get_user_meta($user_id,'uwa_sms_placebid_user_enabled', true);
                        $uwa_sms_outbid_user_enabled = get_user_meta($user_id,'uwa_sms_outbid_user_enabled', true);
                        $uwa_sms_won_user_enabled = get_user_meta($user_id,'uwa_sms_won_user_enabled', true);
                        $uwa_sms_ending_soon_user_enabled = get_user_meta($user_id,'uwa_sms_ending_soon_user_enabled', true);
                        
                        $uwa_sms_placebid_checked = "checked";
                        if($uwa_sms_placebid_user_enabled == "no"){
                            $uwa_sms_placebid_checked = "";
                        }

                        $uwa_sms_outbid_user_checked = "checked";
                        if($uwa_sms_outbid_user_enabled == "no"){
                            $uwa_sms_outbid_user_checked = "";
                        }
                        $uwa_sms_won_user_checked = "checked";
                        if($uwa_sms_won_user_enabled == "no"){
                            $uwa_sms_won_user_checked = "";
                        }
                        $uwa_sms_ending_soon_user_checked = "checked";
                        if($uwa_sms_ending_soon_user_enabled == "no"){
                            $uwa_sms_ending_soon_user_checked = "";
                        }


                        // $uwa_sms_placebid_user_enabled == "yes" ? $uwa_sms_placebid_checked = "checked" : $uwa_sms_placebid_checked = "";
                        // $uwa_sms_outbid_user_enabled == "yes" ? $uwa_sms_outbid_user_checked = "checked" : $uwa_sms_outbid_user_checked = "";
                        // $uwa_sms_won_user_enabled == "yes" ? $uwa_sms_won_user_checked = "checked" : $uwa_sms_won_user_checked = "";
                        // $uwa_sms_ending_soon_user_enabled == "yes" ? $uwa_sms_ending_soon_user_checked = "checked" : $uwa_sms_ending_soon_user_checked = "";
                    }
                    /*SMS Notifications check box variables end*/ 
                    
                ?>

                <form action="" method="post">
                
                      <?php 
                        $ctm_phone = get_user_meta( $user_id, 'billing_phone', true );
                        $billing_country = get_user_meta( $user_id, 'billing_country', true );
                        if( empty($ctm_phone) || empty($billing_country) )
                        {
                            $message =  sprintf(__("Please update your phone number and country <a href='%s'>here</a> to receive following notifications.", "ultimate-auction-theme"), get_permalink(wc_get_page_id('myaccount'))."edit-address/billing");
                            echo '<p>'.$message.'</p>';
                        }

                  
                         /*SMS Notifications check box start*/ 
                        if($uwa_twilio_sms_addon == "on"): ?>
                            <div class="uwa-title-sec SMS-Notifications"><?php _e('SMS Notifications', 'woo_ua'); ?></div>
                          
                        
                        <?php if($uwa_twilio_sms_placed_bid_enabled == "yes"): ?>
                            <div class="switch-main">
                                <label class="switch">
                                <input <?php echo $uwa_sms_placebid_checked; ?> class="checkbox" value="1" name="uwa_sms_placebid_user_enabled" type="checkbox" style="">
                                <span class="slider round"></span>
                                </label>
                                <span class="switch-title"><?php _e('When you place a bid', 'woo_ua'); ?></span>
                            </div>
                        <?php 
                            endif; 
                            if($uwa_twilio_sms_outbid_enabled == "yes"):
                        ?>
                            <div class="switch-main">
                                <label class="switch">
                                <input <?php echo $uwa_sms_outbid_user_checked; ?> class="checkbox" value="1" name="uwa_sms_outbid_user_enabled" type="checkbox" style="">
                                <span class="slider round"></span>
                                </label>
                                <span class="switch-title"><?php _e('When your bid is outbid', 'woo_ua'); ?></span>
                            </div>
                        <?php 
                            endif; 
                            if($uwa_twilio_sms_won_enabled == "yes"):
                        ?>
                            <div class="switch-main">
                                <label class="switch">
                                <input <?php echo $uwa_sms_won_user_checked; ?> class="checkbox" value="1" name="uwa_sms_won_user_enabled" type="checkbox" style="">
                                <span class="slider round"></span>
                                </label>
                                <span class="switch-title"><?php _e('When you win an auction product', 'woo_ua'); ?></span>
                                
                            </div>
                        <?php 
                            endif; 
                            if($uwa_twilio_sms_ending_soon_enabled == "yes"):
                        ?>
                            <div class="switch-main">
                                <label class="switch">
                                <input <?php echo $uwa_sms_ending_soon_user_checked; ?> class="checkbox" value="1" name="uwa_wp_sms_ending_soon_user_enabled" type="checkbox" style="">
                                <span class="slider round"></span>
                                </label>
                                <span class="switch-title"><?php _e('When the auction product is ending soon', 'woo_ua'); ?></span>
                            </div>
                    <?php endif; 
                         endif; 
                         /*SMS Notifications check box end*/ 
                    
                    ?>
                    <div class="save-btn">
                        <input type="submit" id="uwa-settings-submit" name="uwa-settings-submit" class="woocommerce-Button woocommerce-Button--alt button alt" value="<?php _e('Save Changes', "woo_ua"); ?>" />
                    </div>
                </form>
                
            
    
    </div>
    
<style>
.uwa-title-sec {
    font-weight: bold;
    margin-bottom: 15px;
    font-size: 17px;
    border-bottom: 2px solid #d9d2d2;
    padding-bottom: 9px;
}
.uwa-title-sec.WhatsApp-Notifications {
    margin-top: 40px;}

/* The switch - the box around the slider */
.uwa-notification-page-my-account .switch {
  position: relative;
  display: inline-block;
  width: 60px;
  height: 34px;
}

/* Hide default HTML checkbox */
.uwa-notification-page-my-account .switch input {
  opacity: 0;
  width: 0;
  height: 0;
}

/* The slider */
.uwa-notification-page-my-account .slider {
  position: absolute;
  cursor: pointer;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: #ccc;
  -webkit-transition: .4s;
  transition: .4s;
}
.save-btn {
    margin: 30px 0 20px 0;
}
.uwa-notification-page-my-account .slider:before {
  position: absolute;
  content: "";
  height: 26px;
  width: 26px;
  left: 4px;
  bottom: 4px;
  background-color: white;
  -webkit-transition: .4s;
  transition: .4s;
}

.uwa-notification-page-my-account input:checked + .slider {
  background-color: #2196F3;
}

.uwa-notification-page-my-account input:focus + .slider {
  box-shadow: 0 0 1px #2196F3;
}

.uwa-notification-page-my-account input:checked + .slider:before {
  -webkit-transform: translateX(26px);
  -ms-transform: translateX(26px);
  transform: translateX(26px);
    color: transparent;
    border-color: transparent;
}
h2.uwa-notification-title {
    margin-top: 0;
}
/* Rounded sliders */
.uwa-notification-page-my-account .slider.round {
  border-radius: 34px;
}

.uwa-notification-page-my-account .slider.round:before {
  border-radius: 50%;
}
    .uwa-notification-page-my-account .switch-main {
    display: flex;
    align-items: center;
        margin-bottom:10px;
}
    .uwa-notification-page-my-account .switch-main span.switch-title {
    display: inline-block;
    margin-left: 10px;
    font-weight: 600;
}
</style>        
<?php 
    
    
?>