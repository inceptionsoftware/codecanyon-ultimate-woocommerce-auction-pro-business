<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 *
 * Handling Users bid.
 *
 * @class  UWA_User_Bid_Endpoint
 * @package Ultimate WooCommerce Auction PRO
 * @author Nitesh Singh
 * @since 1.0
 *
 */
class UWA_User_Bid_Endpoint {

	/**
	 * Custom endpoint name.
	 *
	 * @var string
	 *
	 */
	public static $endpoint = 'uwa-auctions';

	/**
	* Plugin actions
	*
	*/
    public function __construct() {

    	/* Actions used to insert a new endpoint in the WordPress. */
        add_action( 'init', array( $this, 'add_endpoints' ) );
        add_filter( 'query_vars', array( $this, 'add_query_vars' ), 0 );
		add_filter( 'query_vars', array( $this, 'add_query_vars_notifications' ), 0 );
        /* Change the My Accout page title. */
        add_filter( 'the_title', array( $this, 'endpoint_title' ) );
        /* Insering your new tab/page into the My Account page. */
        add_filter( 'woocommerce_account_menu_items', array( $this, 'new_menu_items' ) );
        add_action( 'woocommerce_account_' . self::$endpoint .  '_endpoint', array( $this, 'endpoint_content' ) );
		add_action( 'woocommerce_account_uwa-notifications_endpoint', array( $this, 'endpoint_content_notifications' ) );
		
		
    }

	/**
	 * Register new endpoint to use inside My Account page.
	 *
	 * @see https://developer.wordpress.org/reference/functions/add_rewrite_endpoint/
	 *
	 */
	public function add_endpoints() {
    	add_rewrite_endpoint( self::$endpoint, EP_ROOT | EP_PAGES );
		add_rewrite_endpoint( 'uwa-notifications', EP_ROOT | EP_PAGES );
    }

	/**
	 * Add new query var.
	 *
	 * @param array $vars
	 * @return array
	 *
	 */
	public function add_query_vars( $vars ) {
        $vars[] = self::$endpoint;
        return $vars;
    }
	public function add_query_vars_notifications( $vars ) {
        $vars[] = 'uwa-notifications';
        return $vars;
    }

	/**
	 * Set endpoint title.
	 *
	 * @param string $title
	 * @return string
	 *
	 */
	public function endpoint_title( $title ) {
		global $wp_query;
		$is_endpoint = isset( $wp_query->query_vars[ self::$endpoint ] );
		if ( $is_endpoint && ! is_admin() && is_main_query() && in_the_loop() && is_account_page() ) {
			/* New page title. */
			$title = __( 'Auctions', 'woo_ua' );
			remove_filter( 'the_title', array( $this, 'endpoint_title' ) );
		}
		return $title;
	}

	/**
	 * Insert the new endpoint into the My Account menu.
	 *
	 * @param array $items
	 * @return array
	 *
	 */
	public function new_menu_items( $items ) {

		$uwa_twilio_sms_addon = "off";
		$uwa_whatsapp_msg = "off";
		
		$uwa_enabled_addons_list = get_option('uwa_addons_options_enabled');
		if(!empty($uwa_enabled_addons_list)){
			if(in_array('uwa_twilio_sms_addon', $uwa_enabled_addons_list)) {
				$uwa_twilio_sms_addon = "on";
			}

			if(in_array('uwa_whatsapp_msg', $uwa_enabled_addons_list)) {
				$uwa_whatsapp_msg = "on";
			}			
		}
		
		

		$logout = "";

		if(isset($items['customer-logout'])){
			$logout = $items['customer-logout'];			
			unset( $items['customer-logout'] );
		}

		if($uwa_twilio_sms_addon == "on" || $uwa_whatsapp_msg == "on"){
			$items['uwa-notifications'] = __( 'Notifications', 'woo_ua' );
		}

		$items[ self::$endpoint ] = __( 'Auctions', 'woo_ua' );

		if(!isset($items['customer-logout']) && $logout){					
			$items['customer-logout'] = $logout;			
		}	
	
	
		return $items;
	}

	/**
	 * Endpoint HTML content.	 
	 *
	 */
	public function endpoint_content() {		
		wc_get_template( 'myaccount/uwa-users-bids.php' );
	}
	public function endpoint_content_notifications() {		
		wc_get_template( 'myaccount/uwa-notifications.php' );
	}
	
	/**
	 * Plugin install action.
	 * Flush rewrite rules to make our custom endpoint available.
	 *
	 */
	public static function install() {
        flush_rewrite_rules();
    }
}

new UWA_User_Bid_Endpoint();

/* Flush rewrite rules on plugin activation. */
register_activation_hook( __FILE__, array( 'UWA_User_Bid_Endpoint', 'install' ) );
