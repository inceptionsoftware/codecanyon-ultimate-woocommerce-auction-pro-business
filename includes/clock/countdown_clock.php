<?php
function countdown_clock(
    $end_date,
	$item_id,
	$item_class   
)
{
	$clocktype = get_option('timer_type',"timer_jquery");
	if($clocktype=='timer_jquery'){
		 $end_date;
		$product_id=$item_id;
		$uwa_countdown_format = get_option( 'woo_ua_auctions_countdown_format' );
		$uwa_time_zone =  (array)wp_timezone();
		$sinceday  =  wp_date('M j, Y H:i:s O',time(),get_uwa_wp_timezone());
		?>
		<script>
			var servertime='<?php echo $sinceday;?>';
		</script>
		<div class="uwa_auction_product_countdown <?php echo $item_class;?> clock_jquery " 
		data-time="<?php echo $end_date;?>" 
		data-auction-id="<?php echo esc_attr( $product_id ); ?>" 
		data-format="<?php echo $uwa_countdown_format; ?>"  
		data-timezonetype="<?php echo $uwa_time_zone['timezone_type']; ?>" 
		data-zone="<?php echo $uwa_time_zone['timezone']; ?>"  ></div>
		<?php
	}else{
		$product_id=$item_id;
		$rem_arr=get_remaining_time_by_timezone($end_date);
		$jscookie=json_encode($rem_arr);
		?>
		<script>
			setCookie("acution_antisniping_time_<?php echo $product_id;?>", JSON.stringify(<?php echo $jscookie;?>), '1');
			setCookie("acution_sync_time_<?php echo $product_id;?>", JSON.stringify(<?php echo $jscookie;?>), '1');
		</script>
		<div class="uwa_auction_product_countdown  <?php echo $item_class;?> clock_server " 			 
			data-auction-id="<?php echo esc_attr( $product_id ); ?>" 			
			data-days="<?php  echo $rem_arr['days'] ?>"
			data-hours="<?php echo $rem_arr['hours'] ?>"
			data-minute="<?php echo $rem_arr['minute'] ?>"
			data-sec="<?php  echo $rem_arr['sec'] ?>"
		>
		</div>		
		<?php
	}
}
function antisniping_check(){
	$uwa_auto_extend_when = get_option('uwa_auto_extend_when');
	$uwa_auto_extend_when_m = get_option('uwa_auto_extend_when_m');
	$uwa_auto_extend_when_s = get_option('uwa_auto_extend_when_s');
	$uwa_auto_extend_time = get_option('uwa_auto_extend_time');
	$uwa_auto_extend_time_m = get_option('uwa_auto_extend_time_m');	
	$uwa_auto_extend_time_s = get_option('uwa_auto_extend_time_s');	
	$re="no";
	if(!empty($uwa_auto_extend_when) || !empty($uwa_auto_extend_when_m) || !empty($uwa_auto_extend_when_s)){
		if(!empty($uwa_auto_extend_time) || !empty($uwa_auto_extend_time_m) || !empty($uwa_auto_extend_time_s)){
			$re="yes";
		}
	}
	return $re;
}
?>