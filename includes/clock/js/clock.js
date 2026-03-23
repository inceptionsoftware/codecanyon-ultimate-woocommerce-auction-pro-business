"use strict";
const { useState } = React;

function convertDateToAnotherTimeZone(date, timezone) {
	const dateString = date.toLocaleString('en-US', {
		timeZone: timezone
	});
	return new Date(dateString);
}
function getKeyByValue(object, value) {
	var isfind = 0;
	for (var prop in object) {
		if (object.hasOwnProperty(prop)) {
			if (object[prop] === value)
				isfind = prop;
		}
	}
	return isfind;
}
/* this function is used to prevent module call if expired or run ajax call is not expired*/
function callAjax(element, check = false) {
	if (check) {
		return element.hasAttribute("hasExpiredAuction");
	} else {
		if (!element.hasAttribute("hasExpiredAuction")) {
			element.setAttribute("hasExpiredAuction", "yes");
			UatCheckExpired(element);
		}
	}
}
/*this function is used to call ajax on expire auction*/
function UatCheckExpired(element) {
	element = jQuery(element);
	/* Ajax query String */
	var auctionid = element.data('auction-id');
	var uwa_container = element.parent().next('.uwa_auction_product_ajax_change');
	uwa_container.empty().prepend('<div class="ajax-loading"></div>');
	uwa_container.parent().children('form.buy-now').remove();
	var scheduled = element.hasClass('scheduled') ? 'true' : 'false';
	if (!auctionid) return;
	jQuery.ajax({
		type: "post",
		url:  frontend_react_object.ajaxurl,
		cache: false,
		data: {
			action: "expired_auction",
			post_id: auctionid,
			ret: '1',
			scheduled: scheduled,
		},
		success: function (response) {
			if (response.length != 0) {
				uwa_container.children('.ajax-loading').remove();
				uwa_container.prepend(response);
				element.html("<div><span class='expired' data-time-up='ture'>" + frontend_react_object.expired + "</span></div><div><span class='expired' data-time-up='ture'>" + response + "</span></div>");
				if(frontend_react_object.reload_page == "yes"){
					location.reload();
				}
			}
		},
		async: false
	});
}
function Countdownapp(props) {
	var day = 0;
	var hours = 0;
	var minute = 0;
	var sec = 0;
	day = props.days;
	hours = props.hours;
	minute = props.minute;
	sec = props.sec;
	const [auctionEndTime, setAuctionEndTime] = useState(props.time);
	
	var clocksync=0;
	var callback_fun = setInterval(function () {
		
		sec--;
		var ttt_ = getCookie("acution_end_time_php_" + props.auctionId);
		var getattr_time = get_data_attr_clock(props.element, 'data-time');
		if (ttt_ != null && getattr_time != ttt_) {
						
			tabfocused=1;
			update_data_attr_clock(props.element, 'data-time', ttt_);
		}
		
		  
	  if(jQuery(props.element).hasClass('uwa-main-auction-product-loop')){
	  }else{
		if(tabfocused==1){	
		
		setTimeout(function(){
				var setauctionId = props.auctionId;
				get_auction_sync_time(setauctionId);
				var get_syncjson = getCookie("acution_sync_time_" + props.auctionId);
				var get_syncjson_val = JSON.parse(get_syncjson);
				if(  get_syncjson_val != null  ){
					day = get_syncjson_val.days;
					hours = get_syncjson_val.hours;
					minute = get_syncjson_val.minute;
					sec = get_syncjson_val.sec;	
					 
								
				}
				tabfocused=0;
			},2000);
			 
			
			
		}
		
	  }
		 
		 
		var isinloop=0;
		if(jQuery(props.element).hasClass('uwa-main-auction-product-loop')){ 
			if(multi_lang_data.settings.listpage=='yes'){
				isinloop=1;
				clocksync++;
				 
				if(clocksync==5){
					
					 
					var setauctionId = props.auctionId;
					get_auction_sync_time(setauctionId);
					var get_syncjson = getCookie("acution_sync_time_" + props.auctionId);
					var get_syncjson_val = JSON.parse(get_syncjson);
					if(  get_syncjson_val != null  ){
						day = get_syncjson_val.days;
						hours = get_syncjson_val.hours;
						minute = get_syncjson_val.minute;
						sec = get_syncjson_val.sec;				
						 
						update_data_attr_clock(props.element, 'data-time', ttt_);						
					}
					
				}
			}
		}else{
			clocksync++;
			if(clocksync==5){
				
				var setauctionId = props.auctionId;
				get_auction_sync_time(setauctionId);
				var get_syncjson = getCookie("acution_sync_time_" + props.auctionId);;
				var get_syncjson_val = JSON.parse(get_syncjson);
				if(  get_syncjson_val != null  ){
					day = get_syncjson_val.days;
					hours = get_syncjson_val.hours;
					minute = get_syncjson_val.minute;
					sec = get_syncjson_val.sec;				
					 
					update_data_attr_clock(props.element, 'data-time', ttt_);					
				}
				
			}
			
		}
	 
		
		
		
		if (sec == 0) {
			if (day == 0 && hours == 0 && minute == 0) {
				sec = 0;
			} else {
				sec = 60;
			}
			minute--;
			if (minute < 0) {
				minute = 0;
			}
			if (minute == 0) {
				hours--;
				if (hours < 0) {
					hours = 0;
				} else {
					minute = 59;
				}
				if (hours == 0) {
					day--;
					if (day < 0) {
						day = 0
					} else {
						hours = 23;
					}
				}
			}
		}
		if (sec < 0) {
			sec = 0;
			clearInterval(callback_fun);
		}
		day = parseInt(day, 10);
		hours = parseInt(hours, 10);
		minute = parseInt(minute, 10);
		sec = parseInt(sec, 10);
		day = day < 10 ? "0" + day : day;
		hours = hours < 10 ? "0" + hours : hours;
		minute = minute < 10 ? "0" + minute : minute;
		sec = sec < 10 ? "0" + sec : sec;
		var html = "";
		var dis_days = multi_lang_data.labels.Days;
		var dis_hrs = multi_lang_data.labels.Hours;
		var dis_mins = multi_lang_data.labels.Minutes;
		var dis_secs = multi_lang_data.labels.Seconds;
		html += '<span class="countdown_row countdown_show3">';
		html += '<span class="countdown_section">';
		html += '<span class="countdown_amount">' + day + '</span><br><span class="countdown_amount">'+ dis_days +'</span>';
		html += '</span>';
		html += '<span class="countdown_section">';
		html += '<span class="countdown_amount">' + hours + '</span><br><span class="countdown_amount">'+ dis_hrs +'</span>';
		html += '</span>';
		html += '<span class="countdown_section">';
		html += '<span class="countdown_amount">' + minute + '</span><br><span class="countdown_amount">'+ dis_mins +'</span>';
		html += '</span>';
		html += '<span class="countdown_section">';
		html += '<span class="countdown_amount">' + sec + '</span><br><span class="countdown_amount">'+ dis_secs +'</span>';
		html += '</span>';
		html += '</span>';
		if (day == '00' && hours == '00' && minute == '00' && sec == '00') {
			props.element.innerHTML = html;
			var cltemp=setTimeout(function(){callAjax(props.element); clearInterval(cltemp); },1000);
		}else{
			props.element.innerHTML = html;
		}
	}, 1000);
	return "";
}
jQuery(document).ready(function ($) {
	
	const ell = document.getElementsByClassName('uwa_auction_product_countdown');
	
	
	if(ell.length > 0){
		 
		for (var i = 0; i < ell.length; i++) {
			var elele = /*#__PURE__*/React.createElement(Countdownapp, {
			  days: ell[i].getAttribute('data-days'),
			  hours: ell[i].getAttribute('data-hours'),
			  minute: ell[i].getAttribute('data-minute'),
			  sec: ell[i].getAttribute('data-sec'),
			  time: ell[i].getAttribute('data-time'),
			  zone: ell[i].getAttribute('data-zone'),
			  auctionId: ell[i].getAttribute('data-auction-id'),
			  isExpired: ell[i].querySelector("div > span.expired") ? "yes" : "no",
			  element: ell[i],
			  hasExpiredAuction: ell[i].getAttribute('hasExpiredAuction')
			});
			ReactDOM.render(elele, ell[i]);
		}
	}
});


function get_auction_sync_time(getauctionId) {
	jQuery.ajax({
		url: frontend_react_object.ajaxurl,
		type: "post",
		dataType: "json",
		data: {
			action: "get_auction_remaning_time",
			auctionid: getauctionId,
		},
		success: function (data) {
			setCookie("acution_sync_time_" + getauctionId, JSON.stringify(data), '1');
			setCookie("acution_antisniping_time_" + getauctionId, JSON.stringify(data), '1');
		},
		error: function () {
			console.log('failure!');
		}
		 
	});
}

function get_data_attr_clock(acution, attrnm) {
	return jQuery(acution).attr('data-time');
}
function update_data_attr_clock(setthis, attrnm, attrval) {
	jQuery(setthis).attr(attrnm, attrval);
}
function setCookie(name, value, days) {
  var expires = "";
  if (days) {
    var date = new Date();
    date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
    expires = "; expires=" + date.toUTCString();
  }
  document.cookie = name + "=" + (value || "") + expires + "; path=/";
}
function getCookie(name) {
  var nameEQ = name + "=";
  var ca = document.cookie.split(';');
  for (var i = 0; i < ca.length; i++) {
    var c = ca[i];
    while (c.charAt(0) == ' ') c = c.substring(1, c.length);
    if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length, c.length);
  }
  return null;
}

/*if (document.hasFocus()){ alert('Tab is active');console.log('Tab is active')}*/

var tabfocused=0;
function checkTabFocused() {
  if (document.visibilityState === 'visible') {
   /* console.log('✅ browser tab has focus'); */
	tabfocused=1;
  } else {
   /* console.log('⛔️ browser tab does NOT have focus');*/

  }
}

 
document.addEventListener('visibilitychange', checkTabFocused);