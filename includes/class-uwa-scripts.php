<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Scripts Class
 * Handles Scripts and Styles enqueues functionality.
 *
 * @class  UWA_Scripts
 * @package Ultimate WooCommerce Auction PRO
 * @author Nitesh Singh 
 * @since 1.0
 *
 */
class UWA_Scripts {
	
	private static $instance;

	/**
	 * Returns the *Singleton* instance of this class.
	 *
	 * @return Singleton The *Singleton* instance.
	 *
	 */
    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
	 * Plugin actions
	 *
	 */
	public function __construct() {
		
		/* Add admin side scripts */
		add_action( 'admin_footer', array( $this, 'uwa_register_admin_scripts') );
		
		/* Add admin side styles */
		add_action( 'admin_enqueue_scripts', array( $this, 'uwa_register_admin_styles') );
		
		/* front side scripts */
		add_action( 'wp_enqueue_scripts', array( $this, 'uwa_register_front_scripts') );
		
		/* Add front side styles */
		add_action( 'wp_enqueue_scripts', array( $this, 'uwa_register_front_styles') );
	}

	/**
	 * Manage admin side scripts
	 *
	 * @param.
	 *	
	 */
	public function uwa_register_admin_scripts( $hook_sufix ) {

		/* Register globally scripts */
		wp_register_script( 'uwa-admin', UW_AUCTION_PRO_ASSETS_URL . 'js/uwa-admin.js', array('jquery'), UW_AUCTION_PRO_VERSION );
		
		wp_register_script( 'uwa-datepicker', UW_AUCTION_PRO_ASSETS_URL . 'js/date-picker.js', array('jquery', 'jquery-ui-core', 'jquery-ui-datepicker'), '1.0' );

		/* localization script */
		wp_localize_script( 'uwa-admin', 'WpUat', array( 'calendar_icon' => '<i class="dashicons-calendar-alt"></i>'));
		wp_localize_script('uwa-admin', 'UWA_Auction', array('ajaxurl' => admin_url('admin-ajax.php'), 'uwa_nonce' => wp_create_nonce('UtAajax-nonce') ,'calendar_image'=> WC()->plugin_url() . '/assets/images/calendar.png'));
		wp_enqueue_script( 'uwa-admin' );
		
		wp_enqueue_script( 'uwa-datepicker' );
	}

	/**
	 * Manage admin side styles
	 *	
	 * @param.
	 * 
	 */
	public function uwa_register_admin_styles( $hook_sufix ) {
		
		/* Register styles */
		wp_register_style( 'uwa-admin-css', UW_AUCTION_PRO_ASSETS_URL.'css/uwa-admin.css', array(), UW_AUCTION_PRO_VERSION );
		
		/* Enqueue styles */
		wp_enqueue_style( 'uwa-admin-css' );
		
	}

	/**
	 * Manage front side scripts
	 *
	 * @param.
	 * 	
	 */
	public function uwa_register_front_scripts( $hook_sufix ) {

		/* Register globally scripts */
		
		$clocktype = get_option('timer_type',"timer_jquery");
		 if($clocktype=='timer_jquery'){
			  
			  wp_register_script( 'uwa-front',  plugin_dir_url( __FILE__ ) . '/clock/js/uwa-front.js', array('jquery'), UW_AUCTION_PRO_VERSION );
		 }else{
			 wp_register_script( 'uwa-front', UW_AUCTION_PRO_ASSETS_URL . 'js/uwa-front.js', array('jquery'), UW_AUCTION_PRO_VERSION );
			  
		 }
		 
		 
		
		
		/*wp_register_script( 'uwa-jquery-countdown', UW_AUCTION_PRO_ASSETS_URL . 'js/jquery.countdown.min.js', array('jquery'), UW_AUCTION_PRO_VERSION ,FALSE);		
		wp_enqueue_script( 'uwa-jquery-countdown' );
		
		wp_register_script('uwa-jquery-countdown-multi-lang', UW_AUCTION_PRO_ASSETS_URL . 'js/jquery.countdown-multi-lang.js', array('jquery','uwa-jquery-countdown'), UW_AUCTION_PRO_VERSION,FALSE);*/

			/* localization custom data  */
		
		$multi_lang_data = array(
			'labels' => array(
				'Years' => __('Years', 'woo_ua'),
				'Months' => __('Months', 'woo_ua'),
				'Weeks' => __('Weeks', 'woo_ua'),
				'Days' => __('Day(s)', 'woo_ua'),
				'Hours' => __('Hour(s)', 'woo_ua'),
				'Minutes' => __('Min(s)', 'woo_ua'),
				'Seconds' => __('Sec(s)', 'woo_ua'),
			),
			'labels1' => array(
				'Year' => __('Year', 'woo_ua'),
				'Month' => __('Month', 'woo_ua'),
				'Week' => __('Week', 'woo_ua'),
				'Day' => __('Day', 'woo_ua'),
				'Hour' => __('Hour', 'woo_ua'),
				'Minute' => __('Min', 'woo_ua'),
				'Second' => __('Sec', 'woo_ua'),
			),
			'compactLabels' => array(
				'y' => __('y', 'woo_ua'),
				'm' => __('m', 'woo_ua'),
				'w' => __('w', 'woo_ua'),
				'd' => __('d', 'woo_ua'),
			),
		);
			
	
	  	/* localization custom data */
		$uwa_custom_data = array(
			'expired' => __('Auction has Expired!', 'woo_ua'),
			'gtm_offset' => get_option('gmt_offset'),
			'started' => __('Auction Started! Please refresh page.', 'woo_ua'),
			/*'outbid_message' =>  wc_get_template_html( "notices/error.php", 
				array('messages' => array( __("You have been outbid.", 'woo_ua')))),*/

			'outbid_message' =>  wc_get_template_html( "notices/error.php", 
				array('notices' => array(
					array('notice' => __("You have been outbid.", 'woo_ua'),
				)))),

			'hide_compact' =>get_option( 'uwa_hide_compact_enable', 'no' ), 
		);
		
		$bid_ajax_enable_check = get_option('woo_ua_auctions_bid_ajax_enable');

		/* check ajax call page type */ 

		$ajax_call_status = "yes";
		$bid_ajax_enable_shop_list_ajax_enable = get_option('uwa_shop_list_ajax_enable', "yes");
		$bid_ajax_enable_category_list_ajax_enable = get_option('uwa_category_list_ajax_enable', "yes");
		/*$bid_ajax_enable_shortcode_list_ajax_enable = get_option('uwa_shortcode_list_ajax_enable', "yes");*/
		$bid_ajax_enable_auction_detail_ajax_enable = get_option('uwa_auction_detail_ajax_enable', "yes");

		$bid_ajax_enable_check_interval = get_option('woo_ua_auctions_bid_ajax_interval');

		if ($bid_ajax_enable_check == 'yes') {	

			/* Enable on shop list page */
			if (function_exists('is_woocommerce') && is_woocommerce() && is_shop() && 
				$bid_ajax_enable_shop_list_ajax_enable!='yes') {
				$ajax_call_status = '';
			}

			/* Enable on a product category page */
			if (function_exists('is_woocommerce') && is_woocommerce() && is_product_category() && 
				$bid_ajax_enable_category_list_ajax_enable!='yes') {
				$ajax_call_status = '';
			}

			/* Enable on shortcode pages */
			// add code here.


			/* Enable on auction detail page */
			if (function_exists('is_woocommerce') && is_woocommerce() && is_product() &&
				$bid_ajax_enable_auction_detail_ajax_enable!='yes') {
				$ajax_call_status = '';
			}

			if($ajax_call_status == 'yes'){
				$uwa_custom_data['refresh_interval'] = isset($bid_ajax_enable_check_interval) && is_numeric($bid_ajax_enable_check_interval) ? $bid_ajax_enable_check_interval : '1';		
			}
		}
		
		
		$uwa_custom_data['anti_sniping_timer_update_noti'] =get_option('anti_sniping_timer_update_notification');
		$uwa_custom_data['anti_sniping_timer_update_noti_msg'] = get_option('anti_sniping_clock_msg');
		$uwa_custom_data['antisniping_check'] = antisniping_check();
		$uwa_custom_data['reload_page'] = get_option('uwa_product_detail_timer_page_reload');
		
		/* localization script */
		
		/*wp_localize_script('uwa-jquery-countdown-multi-lang', 'multi_lang_data', $multi_lang_data);		
		wp_enqueue_script('uwa-jquery-countdown-multi-lang');*/
		
		wp_localize_script('uwa-front', 'uwa_data', $uwa_custom_data);
		wp_localize_script( 'uwa-front', 'WpUat', array( 'calendar_icon' => '<i class="dashicons-calendar-alt"></i>'));		
		wp_localize_script('uwa-front', 'WooUa', array('ajaxurl' => admin_url('admin-ajax.php'), 'ua_nonce' => wp_create_nonce('UtAajax-nonce') ,'last_timestamp' => get_option('woo_ua_auction_last_activity','0'), 'calendar_image'=> WC()->plugin_url() . '/assets/images/calendar.png'));
		wp_localize_script('uwa-front', 'UWA_Ajax_Qry',array('ajaqry' => add_query_arg('uwa-ajax', '')));
		wp_enqueue_script( 'uwa-front' );
		
	}

	/**
	 * Manage Front side styles
	 *	
	 * @param.
	 *
	 */
	public function uwa_register_front_styles( $hook_sufix ) {
		
		/* Register styles */
		wp_register_style( 'uwa-front-css', UW_AUCTION_PRO_ASSETS_URL.'css/uwa-front.css', array('dashicons'), UW_AUCTION_PRO_VERSION , FALSE);
		
		/* Enqueue styles */
		wp_enqueue_style( 'uwa-front-css' );
	}
}

UWA_Scripts::get_instance();