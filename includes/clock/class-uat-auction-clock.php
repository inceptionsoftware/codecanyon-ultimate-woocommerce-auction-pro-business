<?php

add_filter('script_loader_tag', 'add_type_attribute' , 10, 3);
function add_type_attribute($tag, $handle, $src) {
    
    if ( 'react_clock' == $handle ) {
        
		// $tag = '<script type="text/babel" src="' . esc_url( $src ) . '"></script>';
		return $tag;
    }
	else if ( 'react_development' == $handle ) {
        
		 $tag = '<script type="text/javascript" src="' . esc_url( $src ) . '"></script>';
		return $tag;
    }
	else if ( 'react_dom_development' == $handle ) {
        
		 $tag = '<script type="text/javascript" src="' . esc_url( $src ) . '"></script>';
		return $tag;
    }
	else if ( 'babel_min' == $handle ) {
        
		 $tag = '<script type="text/javascript" src="' . esc_url( $src ) . '"></script>';
		return $tag;
    }
	else{
		return $tag;
	}
    
   
}
 
 
function jsone_react_include($hook)
{
	
}
add_action('wp_enqueue_scripts', 'jsone_react_include');
 
 
function clock_react_include($hook)
{
	$my_js_ver = '';
 
 	$upload = wp_get_upload_dir();

	$baseurl = $upload['baseurl'];
	
	$clocktype = get_option('timer_type',"timer_jquery");
	 
	
	 
 
	 
	 
	 
		
		

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
		'settings' => array(
			'listpage' => get_option('uwa_listpage_sync_clock_enable'),
			 
		),
	);
	
	 if($clocktype=='timer_jquery'){
		 
		wp_enqueue_script('uwa-jquery-countdown', plugin_dir_url( __FILE__ ) . 'js/jquery.countdown.min.js', array('jquery'), UW_AUCTION_PRO_VERSION);
		wp_localize_script('uwa-jquery-countdown', 'multi_lang_data', $multi_lang_data);	
		wp_enqueue_script('uwa-jquery-countdown-multi-lang', plugin_dir_url( __FILE__ ) . 'js/jquery.countdown-multi-lang.js', array('jquery','uwa-jquery-countdown'), UW_AUCTION_PRO_VERSION);
		 
	 
		
	 }else{
		wp_enqueue_script('react_development',  plugin_dir_url( __FILE__ ) . 'js/react.development.js', array(), $my_js_ver);
		wp_enqueue_script('react_dom_development', plugin_dir_url( __FILE__ ) . 'js/react-dom.development.js', array(), $my_js_ver);
		wp_enqueue_script('react_clock',  plugin_dir_url( __FILE__ ) . 'js/clock.js', array(), $my_js_ver);
		
		
		
		wp_localize_script('react_clock', 'multi_lang_data', $multi_lang_data);		
		wp_enqueue_script('react_clock');
		
		wp_localize_script(
			'react_clock',
			'frontend_react_object',
			array(
				'expired' => __('Auction has Expired!', 'woo_ua'),
				'ajaxurl' => admin_url('admin-ajax.php'),
				'site_url' => site_url(),
				'react_uploadurl' => $baseurl,
				'reload_page' => get_option('uwa_product_detail_timer_page_reload'),
			)
		);
	 }
	 
   
	
	wp_localize_script(
		'text_babel',
		'frontend_react_object',
		array(
			'ajaxurl' => admin_url('admin-ajax.php'),
			'react_uploadurl' => $baseurl,
			'react_currency_symbol' => get_woocommerce_currency_symbol(),
		)
	);
}
add_action('wp_enqueue_scripts', 'clock_react_include');


function clock_change_type_of_javascript($tag, $handle, $src)
{

	if ('react_clock' == $handle) {
		//$tag = str_replace("<script type='text/javascript'", "<script type='text/babel'", $tag);
	}

	return $tag;
}
add_filter('script_loader_tag', 'clock_change_type_of_javascript', 10, 3);


add_filter('script_loader_src','add_id_to_script',10,2);
function add_id_to_script($src, $handle){
    if ($handle == 'clock.js') {
		 return $src."' type='text/babel' ";
	}else{
		 return $src;
	}
           
   
}



 

 
 function get_remaining_time_by_timezone($end_time){
	 
	 
	 
	$date = new DateTime($end_time,  wp_timezone() );
	$end_time_tz = $date->format('Y-m-d H:i:s');
	$now_time = new DateTime();
	$setnag="";
	
	$diff_time= $now_time->diff($date);
	$diff = $diff_time->format("%a days and %H hours and %i minutes and %s seconds");
	
	$days= $setnag.$diff_time->format("%a");
	$hours= $setnag.$diff_time->format("%H");
	$minute= $setnag.$diff_time->format("%i");
	$sec= $setnag.$diff_time->format("%s");
	
	$re_time=array("days"=>$days,"hours"=>$hours,"minute"=>$minute,"sec"=>$sec);
	if($now_time>$date ){
		 $re_time=array("days"=>0,"hours"=>0,"minute"=>0,"sec"=>0);
	}
	return 	$re_time;
 }
function get_auction_remaning_time(){
ob_clean();
    $_REQUEST['auctionid'];
	$product_id= $_REQUEST['auctionid'];
	$end_time=get_post_meta( $product_id, 'woo_ua_auction_end_date', true );
	$date = new DateTime($end_time,  wp_timezone() );
	$end_time_tz = $date->format('Y-m-d H:i:s');
	$now_time = new DateTime();
	$diff_time= $now_time->diff($date);
	$diff = $diff_time->format("%a days and %H hours and %i minutes and %s seconds");	
	$days= $diff_time->format("%a");
	$hours= $diff_time->format("%H");
	$minute= $diff_time->format("%i");
	$sec= $diff_time->format("%s");	
	$re_time=array("days"=>$days,"hours"=>$hours,"minute"=>$minute,"sec"=>$sec);	
	echo json_encode($re_time);
 wp_die(); 
 }
 
add_action( 'wp_ajax_nopriv_get_auction_remaning_time', 'get_auction_remaning_time' );
add_action( 'wp_ajax_get_auction_remaning_time', 'get_auction_remaning_time' );




 

 