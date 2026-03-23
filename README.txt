=== Ultimate WooCommerce Auction Pro ===
Contributors: nitesh_singh
Tags: woocommerce auction, woocommerce auction plugin, woocommerce auction theme, woocommerce bidding
Requires at least: 4.6
Tested up to: 6.8.2
Stable tag: 2.4.4
Requires PHP: 5.6
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Awesome plugin to host auctions on your WooCommerce powered site and sell your products as auctions.

== Description ==

Ultimate WooCommerces Auction plugin allows easy and quick way to add your products as auctions on your site.
Simple and flexible, Lots of features, very configurable.  Easy to setup.  Great support.

= Plugin Features =

   1. Registered User can place bids 
	2. Ajax Admin panel for better management.
   3. Add standard auctions for bidding
   4. Buy Now option    
   5. Show auctions in your timezone        
   6. Set Reserve price for your product
	7. Set Bid incremental value for auctions
	8. Ability to edit, delete & end live auctions
	9. Re-activate Expired auctions
	10. Email notifications to bidders for placing bids
   11. Email notification to Admin for all activity
   12. Email Sent for Payment Alerts
	13. Outbid Email sent to all bidders who has been outbid.
	14. Count Down Timer for auctions.	
	15. Ability to Cancel last bid 
    and Much more...

== Installation ==
= IMPORTANT = 

Please backup your WordPress database before you install/uninstall/activate/deactivate/upgrade Ultimate WooCommerce Auction Plugin

Pre-installation note: You need to install WooCommerce plugin before proceeding further. It is available here - https://wordpress.org/plugins/woocommerce/
Once you have installed kindly refer to their documentation here for full setup - https://docs.woocommerce.com/documentation/plugins/woocommerce/marketplace/

1. Upload the folder ultimate-woocommerce-auction with its contents to wp-content/plugins/

2. Activate the plugin.

3. After you have setup WooCommerce and activated this plugin, you should add a product. 

4. While adding product, choose "product data = Auction Product". Add data to relevant fields. 

Your auction product should now be ready and displayed under "Shop" page. If you have problems please visit the [support](http://docs.auctionplugin.net) for technical questions, documentation and FAQs.

== Screenshots ==

1. Admin: Create auction product
2. Admin: Create auction product with data
3. Admin: Plugin Settings
4. Frontend: Shop Page
5. Frontend: Single product page example


== Changelog ==

= 2.4.4 =

1. Fix:
	
	We have fixed the email preview functionality for all auction-related emails.

= 2.4.3 =

1. New Features:

  	We've introduced a new option to automatically deduct the tax amount using the Credit Card Automatic Debit add-on.
	Once enabled in the settings, the system will automatically debit the applicable tax based on the winning price amount.

2. Improvement:

	The bidder's username will now be shown when the display name is unavailable.

	The "You are winning" message will appear when the reserve price is met in auctions.

	Some texts in WCFM is now available for translation.

	Necessary updates have been made to the "see more" and "see less" AJAX call requests.

3. Fix:

	Resolved the fatal error: Uncaught TypeError - Unsupported operand types: float + string in variable bid increments and direct bidding options.

	Fixed the "Undefined array key 'customer-logout'" warning on the my-account page across all themes.

	Corrected the warning related to the "orderedby" attribute in the auction shortcode pagination.	

	We've resolved an issue where the automatic order was not being generated upon auction expiration 
	when the auction was relisted.


= 2.4.2 =

1. Improvement:
	
	We updated the hook from "woocommerce_single_product_summary" to "woocommerce_auction_add_to_cart" to show auction-related details on the product page.

	The columns in auction export CSVs are now translatable.

	We changed the format of username masking as "*******"

2. Fixes:

	We've resolved the issue where the page refreshes when an auction expires.

	We've fixed the issue where WooCommerce notices were not displaying when a user placed a bid.		

	The issue with the 'You are winning' text in the related products area has been fixed.

	We've resolved the error: 'Fatal error: Uncaught ValueError: str_repeat(): Argument #2 ($times) must be greater than or equal to 0.'

	The issue with outbid emails for proxy auctions has been fixed.

	There is error related to buyer's premium on order page, this issue is fixed.

	We've resolved the issue of currency symbol with direct bid option in aelia currency switcher.


= 2.4.1 =

1. Improvement:

	We've implemented support for PHP 8.2 compatibility.

= 2.4.0 =

1. Fixes:

	There is an issue with credit card fields on the my-account registration page
	which are resolved.
	

= 2.3.9 =

1. New Features:
	
	There is a new column "Maximum Bid" for proxy auctions added under 
	My-account > Auctions - Bids won, Bids lost and Bids active tabs.

2. Improvement:

	We have added support for High-Performance Order Storage(HPOS) functionality	

= 2.3.8 =

1. New Features:

	We have added the "Add to watchlist" functionality for future auction products.

	The new filters are added to change the "Check out All" button text and URL of button.
	and filter "ultimate_woocommerce_auction_all_pay_now_button_text" is deprecated.

2. Improvement:

	We have added the three new options for AJAX bid updates.
	1. Shop page
	2. Product category page
	3. Auction detail page

	Admin can disable the Ajax updates for any of the above pages.

3. Fixes:

	There is issue in search auction products with SKU which has been fixed.

	When place bid AJAX is enabled, there is issue with text "Maximum bid is.." in proxy auction products,
	we have fixed the issue.

	There is issue with proxy auction product when bid update AJAX is enabled, we have fixed this issue and other Ajax related errors are also resolved.

	When automatic order is enabled, there is an issue with the checkout URL which has been fixed.

	Some of the issues related to offline dealing addon are fixed.	


= 2.3.7 =

1. Improvement:

   When the auto-order functionality is enabled and the shipping address is required to be added before placing a bid, the validation is added for each field of the shipping address form. Now, we make optional the shipping address 2 and state.

   We have updated the third-party libraries into the auction plugin for Stripe and Twilio. 

2. Fixes:

   We have fixed the loader image issue in the private message section. The loader loads continuously even when the send button does not click. We have fixed this problem.

   When user masking is enabled and the auction ends, the unnecessary text is available on the product details page. We have fixed it.

   We have fixed the PHP warning for Undefined variable $notices. This warning is displayed when someone outbids. Now, when someone outbids, the "You have been outbid." notice is displayed at the top of the page.

   We have fixed the user masking issue. When the auction expires, the winner's name is not masked even if user masking is enabled. Now, it works according to the auction settings.

   There was an issue with the PayPal payment of the auction product on the checkout page due to the long product name. This issue happens when the buyer's premium add-on is enabled. We have provided extra options in the buyer's premium addon settings. So, the admin can display the buyer's premium title using the product's SKU or product name or trim the product's name if it is above 60 characters.

   We have fixed the PHP Warning: Undefined array key of "curentpageenddate".

= 2.3.6 =

1. Fixes:

   We have fixed the plugin activation Fatal error.

= 2.3.5 =

1. Fixes:

   We have fixed the auction plugin conflict issue with the latest version of the WooCommerce Stripe Payment Gateway plugin. When a user has added a payment method from the My-Account -> Payment Method menu, the default payment method does not save and auto-debit does not occur. Now, it works properly.

= 2.3.4 =

1. New Features:

   We have given a new option for "Export Won Auctions CSV" on the my-account page -> Auctions -> Bids Won tab. This option is for bidders who can download the winning auction product data from the bids won tab.

2. Improvement:

   Usually Ending Soon Email goes to admin and bidders. We have added a new user role for Ending Soon Email which is the auction owner. Now, the Ending Soon Email goes to the admin, auction owner, and bidders.

   We have fixed the Cookies issue in the auction plugin. When the global countdown timer is enabled, it will create too many cookies. We have fixed this issue. Now, cookies will expire in one day.

3. Fixes:

   We have fixed the vendor product duplicate issue in the auction plugin. When the vendor duplicates the expired auction product from the WCFM dashboard and publishes it, it displays the bid data and winning information of the auction product from which it is duplicated. We have fixed this issue.

   We have fixed the string translation issue in the auction plugin. There are extra characters added to the PO file. We have fixed it.

   We have fixed the error which occurred due to the activation of Ultimate WooCommerce Auction Pro and Ultimate Auction for WooCommerce plugins at a time.

= 2.3.3 =

1. Improvement:

	We have provided the auto-debit option for admin and vendor differently in the credit card auto debit addon for the stripe payment gateway. Now, we merge these options of Full Bid Amount, Partial Bid Amount, and No Auto Debit. Any of these options selected by the admin will be applicable to both the admin and the vendor.

2. Fixes:

	When any guest user does the checkout process for a non-auction product and clicks the option to create an account on the checkout page, it conflicts with the "Credit card auto debit" addon. We have fixed this issue. 

= 2.3.2 =

1. New Features:

   We have added a new option for "Disable the Buy It Now option once bidding has reached the reserve price" in the auction settings. It will disable the Buy Now button when the current bid is equal to or greater than the buy now button.

2. Improvement:

   When the auction product is live and any bid is placed after that, the admin can not change the auction bidding type by editing the product. 

3. Fixes:

   We have fixed the outbid email issue for the silent auction product. Outbid email to the second highest bidder only when the current bid is higher than the second last bid amount. 

   We have fixed the text translation issue for "Checkout All" and "You cannot place a bid on the product yet. Please contact the administrator of the website to get it unblocked."

   There was an issue with the auto-debit amount when zero decimal currencies were selected for the Woocommerce shop page. We have fixed this issue. Now, The correct auto-debit amount is displayed in the stripe dashboard when auto-debit happens with zero decimal currency. 

   We have fixed the issue of mandatory phone number option for SMS Notification Addon. Now, the option "Please check this box to disable mandatory phone number" is working.

   There was an error occured while changing the order status. We have fixed this issue.  

   We have fixed the conflict of flatsome theme with the auction plugin. When the flatsome theme is activate, the "Notifications" link is visible properly on my-account page.

   We have fixed the issue for the "Refresh page automatically once the timer ends" option. Now this option is working with both timer(Local based timer and React based timer). 

   We have fixed the related product issue. When the "Show Expired Auctions" option is disabled, the expired products do not show in the related product section.

   The "Buy now" price is not saved when auction products are imported using a CSV  file. We have fixed this issue.

   We fixed the issue of multiple purchases of the same auction product. Multiple users cannot purchase the same auction product using "Buy Now". Only the winner can checkout and purchase the auction product.

= 2.3.1 =

1. New Features:
	
	We have added a feature for copyright text in the auction plugin. When the setting is on for copyright text, it will display in footer.

2. Improvement:

	We have added a new language translation for the Hungarian language.

	We have added default pagination for all the shortcodes.

	We have added extra option for Anti-sniping setting. We have added "seconds" field for Anti-sniping time. Now, admin can set seconds also for Anti-sniping.

	We have added a new option for automatic page refresh. When this setting is enabled, the page is refreshed automatically on the product expiration.

	We have provided settings for SMS notifications on the My-Account page. Users can enable or disable it as per requirement. Only selected notifications will be sent to specific users.

3. Fixes:

	We have fixed an issue with the Pay Now button on the product page. The "Pay Now" button is displayed on the product page even if "Offline Dealing Addon" is enabled. We have fixed it.

    There is an issue with the winner's email notification. This email was sent to the wrong winner. This email contains the wrong winner for a specific auction product. We have fixed this issue. We have changed the email header text.

    The Flatsome theme conflicts with the auction plugin. When this theme is active, the Product detail page layout is not displayed properly. We have fixed this issue.

= 2.3.0 =

1. Improvement:

    We provide the Jquery-based timer and react-based timer in the auction plugin. User can enable it any one of that via auction setting: WP Admin > Auction > Settings > Auction > 

= 2.2.9 =

1. Fixes:

	There is a CSS conflict with some of the WordPress themes because of the new layout of the auction plugin. We have fixed it.

= 2.2.8 =

1. Improvement:

	Timer Update - In previous update, we had implemented countdown timer which runs on the exact server time. We have introduced a new improvement where in when a new page is loaded then the timer will automatically be synced so that there isnt any delay happening. Admin can enable and disable this syncing using a setting: WP Admin > Auction > Settings > Auction > 

	New Design for Auction Pages - We have introduced a new design layout for auction product detail page and auction list page. This will provide a cleaner look and would be more appealing to auction users.

	Addon - SMS Notification - When the "SMS Notification" Addon is enabled, the country field displays on the registration form for new users. The country which the admin has selected in the WooCommerce general setting will be selected by default in the country field on the my-account page.

2. Fixes:

	Timer - There was a delay observed inside a countdown timer when any browser page was minimized and then maximized. We have fixed this issue.
	
	Block User Setting - The setting was not applicable for new users who were registering after the settings were saved. We have now fixed the issue such that it would be applicable to all users. 

	We have fixed the Auction plugin conflict issue with the Divi theme. Now, the Divi theme editor loads properly when the auction plugin is active on the site.

	We have fixed the "Unsupported operand types" error in the products menu in the admin dashboard. When the admin tries to filter the expired auction product, this error comes. It is fixed.

	We have fixed the error for "Call to a member function get_type()" on the order thank you page.

	When the mask username is enabled, the highest bidder name is not displayed at "Username is winning" in the live auction and "username - won" in the expired auction product.

	We have fixed the string translation issue for "Buyer's Premium Amount for".

	We have fixed some issues regarding the auction plugin on the WCFM dashboard.
    	-> When the "auctions" menu is selected in the WCMF dashboard, it is highlighted with a different color background.
    	-> We have changed the icon and tooltip text on the auction listing page.

	There was a minor issue observed when a user was placing a bid then sometimes in peculiar cases, the bidding meta was not getting flushed. Now, the session flush is happening properly. 

= 2.2.7 =

1. Fixes:

	We have fixed the auction plugin conflict with the WooCommerce Payment plugin. Now, all auction emails are listed in the WooCommerce email settings and sent properly.

	We have removed the reference to the old countdown JS.

	We have fixed the timer difference issue between the shop page and product page. Now, the countdown displays the same time for an auction product on the shop page and product page.

= 2.2.6 =	

1. Fixes:

	We have fixed the translation problem of timer strings.

= 2.2.5 =	

1. New Feature:

	We have implemented a new feature of auto-order. When the auction expires, the order is created automatically.

2. Improvement:

	We have improved the timer functionality in the auction plugin. The timer works according to the server timezone.

3. Fixes:

	We have fixed the trash auction products issue on the my-account page. The products which are in the trash will not display on my-account → Auctions.

= 2.2.4 =	

1. Improvement:

	Cart page including auction product has been improved to show responsive design for the table heading.
	
	Relist - The product was relisting with 1 minute delay. We have improved the code to minimize this delay and to relist the item without delay.

2. Fixes:

	Credit Card Auto Debit - When the site is configured to include the tax within the product price then the automatic debit amount was not calculated properly. We have fixed this issue and now correct amount is calulated taking the tax into account.
	
	Disable the Buy It Now option once bidding has reached the reserve price" setting was not working. We have fixed this issue.ng enables and bidding has reached the reserve price, the buy now button disables.

= 2.2.3 = 

1. Fixes:
	
	-> We have fixed the issue of Soft Close / Avoid Sniping. The problem was that the product was not extended as per the auction setting or the product was suddenly expiring when the user places a bid. Now, The auction plugin extends properly according to the auction setting and does not end.

= 2.2.2 = 

1. Fixes:
	
	-> There were two critical issues that were being logged inside "WP Admin > Tools > Site Health" after previous update. Both have been resolved.

= 2.2.1 =

1. New Feature:

	-> We have added the functionality to auction products for immediate auto-relist. Now, the admin can automatically relist auction products with zero hours.

	-> We have added retain bid functionality for unlogged users. If an unlogged/unregistered user tried to place a bid with a specific value. "Login/Register" notice displayed on the product page. When the user login/registers on the product, the specific bid value is auto-filled in the bid input box of the auction product.

2. Improvement:

	-> We added a category parameter in the shortcode of the auction plugin. Now, the admin can display auction products with a specific category.

	-> We will add a new feature for Soft Close / Avoid Sniping setting. It is for the reset auction option. We will add an option to select "Extend Auction" or "Reset Auction".

	-> We will add cronjob for Ending Soon SMS. Admin needs to set a cronjob for Ending Soon SMS. It will send an Ending Soon SMS to bidders.

3. Fixes:

	-> We have fixed the issue of the malicious code pattern in the EDD_SL_Plugin_Updater.php file.

	-> We have fixed the multiple SMS issue in the auction plugin. We have changed the hook for the Won SMS Message.

	-> We have fixed the timing issue of the auto relist auction product. When the auction product is relisted, the timer displays the correct time.

	-> We have fixed the tax issue on the checkout page. If the full bid amount of the won auction product is auto-debited, tax and other charges will display on the checkout page to pay.

	-> We have fixed the issue with the email setting of the Payment Reminder email. The setting for payment reminder email is now working. An automatic payment reminder email is going to the winner according to the setting which is done by admin in woocommerce -> Email -> Ultimate Auction - Payment Reminder.

	-> There were two issues found when our plugin was working with the WPML plugin. This issue only occurs when the auction product is running with multiple languages on the site using the WPML plugin.
	
		We have fixed the WPML issue for bid extend functionality. When the user places a bid on an auction product, the bid extend functionality was only working in the default language of the auction product. It was not work in other languages. Now, It will work in all languages of auction product which is set up on-site.

		We have fixed the WPML issue for winning and Losing text on the auction product page. When some bidder lost the bid to another bidder, the text for losing was only displayed in the default language of the auction product. Now, It will work in all languages of auction product which is set up on-site.

	-> We have fixed warnings and errors with PHP8.

= 2.2.0 =

1. Improvement:

	Won Notification Email was under rare case was sent to wrong winner. This problem was extremely rare and we could not reproduce the scenario but we decided to have different email settings/templates for admin and bidder. This approach will elimniate any possibily to show different usernames and the auction product names in the email header and body. 

2. Fixes:
	
	Correct Float Value will now be displayed once "Auto Debit Via Stripe" is debited on checkout page. If the float value is auto debited by the auction plugin, the correct value will be displayed in "Auto Debit Via Stripe" on checkout page.
	
	We have fixed "Your payment is already done!! please place the order" notice issue on checkout page. This notice will be only displayed on checkout page when the value of the auction product has been auto debited.
		
	"Email Heading" was not modifying for "Ultimate Auction - Auction Won" email notification - When admin add custom text in "Email Heading" field from woocommerce -> settings -> Emails -> Ultimate Auction - Auction Won -> Manage, it will change the heading of bid won email notification.

	"Checkout All" button issue - When the "Checkout All" button is clicked from my-account -> Auctions -> Bids Won, it will checkout all winning auction product at a time.

	Timer issue on chrome browser with "Finland" language - The countdown timer will display correctly in the Chrome browser when the "Finland" language is selected on the site.

	CSS conflict issue with the user registration plugin - The data picker will display properly in the auction plugin when the user registration plugin is active on site.

	We have fixed the SKU filter conflict issue with auction plugin. Now, "In stock" and "Out of Stock" filter will work properly in product menu.

	Pagination issue in WP Admin > Auctions > Auction > future auctions - Now all future auction products will display with pagination under the auctions menu.


= 2.1.9 =

1. New Feature:

	Export Expired auctions CSV - Admin can export the CSV file for all expired auctions.

2. Improvement:

	Bidding Notification Email - Admin can set separate subject for admin email and bidder email.

	We have sanitized, validated, and escaped all functions using POST/GET/REQUEST/FILE calls for meeting security guidelines of WordPress.org.
	
	Proxy Bidding: Plugin now automatically proxy bids the "Reserve Price" on behalf of user if his max bid is above reserve price. 
	
	The stripe library has been updated for compatibility with php8.
	
	Credit Card Auto Debit Addon:
	
		Added more checks to ensure under no condition multiple debit can be possible when auction is expired.

	
3. Fixes:

	Added missing string translation for auction plugin in admin dashboard.

	Implemented AJAX functionality for "username is winning" text on shop page,product page and widgets.
	
	Credit Card Auto Debit Addon:

		Fixed Autodebit issue with relisted auction product.

		Fixed Autodebit issue with duplicate auction product.

	Fixed timer issue on shop page when auction product is extended. When the auction product is extended, the countdown timer will be updated on the product page and shop page without refresh.

	Fixed buyer's premium calculation issue - When the admin deletes the highest bid in the auction product, the auction plugin will calculate the buyer's premium according to the second highest bid.

	Fixed "Total Auto Debit Via Stripe" display issue on checkout page. Value of "Total Auto Debit Via Stripe" will be displayed correct on checkout page when the include tax is selected from the woocommerce setting.
	
	We have fixed below issue with PHP8.
	-> Fixed warning and errors with PHP8.
	-> Fixed AJAX bid place issue with PHP8.

= 2.1.8 =

1. Fix:
	
	There was a warning appearing, it has now been fixed. Warning: Trying to access array offset on value of type bool in /home/willimda/public_html/stage/wp-content/plugins/ultimate-woocommerce-auction-pro/includes/admin/class-uwa-admin.php on line 2403

	We had observed that plugin was not installing properly on PHP 8. This has been resolved now.


= 2.1.7 =

1. Fix: 

	NOTICE was being displayed inside WP Dashboard. We have attended and fixed it.
	
= 2.1.6 =

1. New Feature:
	Email: Watchlist Notification
		We have added a new email notification which will send email to the user who has added that product to his watchlist if any user places a bid on that product.

2. Improvement:
	Proxy Bid cases have been updated
		Plugin now automatically proxy bids when auction ends for user if user's "maximum bid price" is more than reserve price to make him a winner. Previously, the auction ended without winner if the actual bid is not more than reserve price no matter what maximum bid would be.
		Plugin will now automatically proxy bid and jump the bid of User 1 if User 2 matches "maximum bid" of User 1. It will place bid for User 1 of amount same as maximum bid.

3. Improvement:
	Separate Start and End Date columns have been added inside Manage Auction > Expired Auction and inside My Account page.

4. Fix:
	Error occured when admin was converting auction product to simple product. This has been fixed.


= 2.1.5 =

1. Fix

	Wordpress Errors were displayed while adding "Simple Products". This has been rectified.



= 2.1.4 = 
1. Fixes
 
	Countdown Timer - There was an issue of display on safari browser and for browsers on IPhone. This has been fixed.


= 2.1.3 =

1. New Feature

	Direct Bid displays a new drop down with bid values. These bid values are in accordance with the increment value. 
	
	Hide or Display Custom/Direct Bid portions - New settings have been introduced for hiding or displaying these fields.
	
	Bid Increment can be now set globally - New global setting has been introduced.
	
	Global setting for blocking/unblocking users for bidding.
	
2. Improvement

	Winner Information can be viewed in shop, detailpage and widgets
	
	Winning and Losing labels can be hidden/shown and set custom labels for auction products on shop page.
	

3. Fixes

	Auto Debit of Winning bid and Buyer's Premium was not working when admin was ending an auction product. This has been fixed.
	
	Related Product for Auction Products were showing wrong products. We have changed this query.


= 2.1.2 = 

1. Improvement

	Credit Card Auto Debit: We observed a case where when "Aajx Bidding" setting has been turned on then sometimes duplicate calls were made to Stripe API for automatic Debit. We have rectified this and addressed this issue.
	
2. Fixes

	Auction Entries inside Auctions > Live Auction or Expired Auction was sometimes not showing properly. This has been fixed. 

= 2.1.1 = 

1. Fixes

	Countdown Timer - There was an issue of display on safari browser and for browsers on IPhone. This has been fixed.

= 2.1.0 = 

1. New Feature:

	Buyer's Premium feature is now available at product level too.

2. Improvement

	Ending Soon Email - New option has been added in this email to send this email to users who have this product in their watchlist. 

3. Fixes:

	Countdown Timer will now show time based on the timezone selected on the website. Previously, it was showing local computer time of the user which was causing confusion.

	Winner email's "Pay Now" button was earlier redirecting to Wordpress default login Page. It has been rectified and is now redirecting to "My Account Page".

	We had found an issue with checkout process while using "WooCommerce Deposit" plugin. This has been fixed.
	
	Users can now search by SKU numbers for auction products too. 


= 2.0.9 = 

1. Fix:

	We have fixed following Notice which was appearing after previous version was released.
	
		Notice: Undefined index: ua-auction-cron in /home/runcloud/webapps/33-forever/wp-content/plugins/ultimate-woocommerce-auction-pro/ultimate-woocommerce-auction-pro.php on line 308

2. Improvement

	New plugin notification to set your server cron is now displayed instead of four notices. And we have documented the process to set server cron in this article - https://docs.auctionplugin.net/article/123-set-your-auction-cron-job


= 2.0.8 =

1. New Feature:
	
	Server Cron Job: We have developed four background cron settings using which auction status, email, sms will be triggered even when there are no traffic on the website. These background cron jobs will ensure that events associated with auction products happen at exact time. You can go through this article to see how to set this in your hosting panel - https://docs.auctionplugin.net/article/68-installing-ultimate-woocommerce-auction-pro-plugin
	
	New Addon: Currency Switcher With Aelia: We have developed a new addon which works with Aelia Currency Switcher plugin to show auction prices in multiple currencies.

2. Fix:

	Auction products were not being added in the checkout page when visitor was clicking "paynow" or "getitem" button in Winner Email. This issue has now been fixed.
	
	Addon: Credit Card Auto Debit: State Field was missing inside WooCommerce My Account Registration form. This has now been added.

3. Improvement:

	Error messages shown by our plugin were being displayed on top of screen which were going un-noticed by visitors. We have now added a slide feature which will slide the page automatically to the top so that visitor does not miss the error messages.


= 2.0.7 =

1. Improvement:

	Addon: Credit Card Auto debit
		We have improved the code and introduced a flag which would prevent to send multiple API requests to Stripe to auto debit winning amount if for any reason the API response was unsuccessful. Hence plugin would send this request only once whether the response is successful or unsuccessful. This will ensure that multiple amount is not detected.
	
= 2.0.6 = 

1. Fixes 

	Addon: Credit Card Auto debit
		For amounts having decimals, plugin was receiving "Invalid integer" error from Stripe API. We have now fixed this issue and it works for decimal values with two places.
		
	Multiple Email
		We had received an issue from customer that multiple winner emails, sms and "auto debit" requests were being sent out to winners. Though after multiple tests we were not able to reproduce the problem but we have implemented few code which will ensure that recursive calls to emails, Twilio and Stripe API are not made.
	
2.	Improvement:	

	Addon: Credit Card Auto debit
	
		Added Payment status and date inside "Edit Product" screen when debit has been successful.
	
	Logging
	
		WooCommerce Log - We have added Auction ID for logs generated for auction products.
		
= 2.0.5 = 

1. Fixes

	For "Credit Card Auto Debit" Addon, since we use base configuration of "WooCommerce Stripe Payment Gateway" plugin, this plugin has added prefix to Customer ID stored in DB due to which user's payment method was not stored properly and transaction for auto debit were not happening properly. We have now updated our plugin to match the prefix and fixed this issue.
	
	If anyone has installed Elementor plugin on their website and will make a WooCommerce product page (for auction) using it, then they can choose "add to cart" button to show bidding portion. This is a temporary fix until we develop full compatibility for Elementor.
	
	
= 2.0.4 =

1. New Features

	Plugin is now compatible with WooCommerce Product Table (WCPMT) plugin and we have added a new shortcode which you can use with WCPT plugin to show count-down timer on table's column. Here is the shortcode - [countdown id="%product_id%"] 
 
2. Improvement
	
	We have added a new bid button - "Directly Bid" on auction product detail page. You can enable this setting from "Auctions > Settings > Display > "Enable Specific Fields" > Direct Bid Button. If it is already active then save it so that its value can be saved to database. So now, if you enable it then your users will get two options to bid "Directly Bid" and "Custom Bid".
	
	We have changed name of "Place Bid" button to "Custom Bid". Please update your translation file accordingly.
	
	Auction sorting options are added to default product sorting options so user can set any auction option as default.
	
	Bid Increment for Fixed value now supports values less than 1 (e.g. 0.2, 0.7) but 0 is not allowed for both admin and vendor auctions

3. Fixes
	
	WooCommerce's Product Category Widget and shop page was showing wrong auction product count for categories. This has been fixed now.
	
	Auction Product Detail Page was not displaying properly for DIVI Themes. This has been fixed now.
	
	Shop manager User role can view bidder names when masking is on.
	
	There were few issues found when our plugin was working with WPML plugin. These have been fixed.
	
	
= 2.0.3 = 

1. Fixes

	Anti Sniping was not working properly after we had used new Wordpress Timezone function. This has been fixed.
	
	Ending Date displayed on auction product detail page was incorrect. This has been fixed. 
	
	
= 2.0.2 = 

1. New Features

	New Addon: Offline Dealing of Buyer & Seller - This Addon will share contact details of buyer and seller with each other so that they can do offline dealing.

2. Improvement

	Plugin now uses latest WP functions which were introduced in WP 5.3 - https://make.wordpress.org/core/2019/09/23/date-time-improvements-wp-5-3/
	
	SMS Notification Addon: We have added a provision for old users to update their phone numbers and country inside their My Account page. Old users will get a message at the time of placing bid - "Please enter Phone Number and Country details before placing the bid"
	
	Credit Card Auto Debit Addon: Credit Card form has been modified so that it clearly displays all the relevant fields of credit card. 
	
	New configuration to show reserve price on auction detail page. 
	
3. Fixes

	Categories were showing wrong "auction product" count for shop page. This has been fixed now.
	
	WCFM Front End Manager Dashboard > Add auction had few Javascript and CSS enhancements for Wordpress 2020 Theme.
	
	Add/Edit Auction Product was not showing "General" Tab option. This has been fixed now.
	
	Notices and Warning were being displayed in few screens and this has been fixed now.


= 2.0.1 = 

1. New Features

	Assign a new winner for an expired auction by deleting bid of the existing winner. Admin can now do this if they have not received payment from their existing winner. Once they delete existing winner, then highest (normal bidding) or lowest (reverse bidding) most bidder next in line will be the winner and email notification will be sent to that person. 
	
	Choose your own winner. Auction owners can now choose their own winners. They get an option to do this inside "Edit Product".
	
	New Email notification - "Ultimate Auction - Auction Lost" has been added for proxy and silent bidding. This email notification once enabled will send notification to all bidders who lost in bidding once auction expires. 
	
	New configuration has been added for admin to allow admin and "auction owners" to bid on their own auction. By default both can bid. This has been added inside "WP Admin > Auctions > Settings > Auction".
	   

2. Improvement

	Block/Un-block Users to place bid now can now be done in bulk. We have added this option.
	
	Resend button for sending winning email has been provided inside "Auctions" and "Bids" screens. 
	
	Email "Ultimate Auction - Auction Won" now has new checkbox to send this email notification to Admin and Auction Owners or Sellers. 	
	
	Under WP Admin > Auctions > Settings > Display > Auction Detail Page, we have provided many configuration to enable/disable various texts displayed on "Auction Product Detail Page". This will help admins to choose what they want to show and what to hide.
	
	We now display currency sign right before the bid text field on Auction Product Detail page.
	
	Silent Auction now has a new configuration to enable/disable outbid email notifications.
	
	When any auction product has only "buy now" price enabled and no "bidding" then we have shown buy now price on auction list page. Previously it showed "Place Bid" text.

3. Fixes

	Outbid email was being sent for users who were outbiding their own bids. This has been fixed now. 
	
	Javascript error occurred due to single quote which has ben fixed by adding addslashes function. 
	
	There was an issue caught while imputing variable bid increment field during Import. This has now been fixed.
	

= 2.0.0 =

1. New Addons added

	Collect Credit Card and automatically debit Bid Amount
	
	SMS Notification using Twilio
	
	Buyer's Premium
	
= 1.1.4 =

1. Fix

	My Account > Auctions page was throwing 404 Error when Permalink Setting was set to Plain and other values. this has been fixed.

2. Improvement

	Redirection to Auction Detail page - When any visitor (without logged in) used to visit auction product detail page, they were prompted to login/Register which upon click opens "My Account" page. And then after login or registration, it did not redirect back to Auction Product Detail page. We have now included that feature.

	HTTP POST request was being called with each page load to check expiration status. This was redundant and not required and thus have been removed.


= 1.1.3 =

1. New:

	PRO Version shortcodes will now have pagination option.

2. Fix:
	
	WooCommerce Product Search Widget was not displaying auction products. This has been fixed.

	Bids Menu was not displaying bids for "All" options. This has been fixed.

	When you will convert "Future Auction" to "Live Auction" then it would happen without any error.

3. Improvement

	When user or any auction is deleted then associated auction data for user or auction will also be deleted.

	English Sentences for few emails were edited.


= 1.1.2 = 

1. New:
	
	Bulk Import feature for auction products - https://docs.auctionplugin.net/article/91-5-how-to-bulk-import-auction-products

	WPML Compatible

2. Fix:
	
	Deleting auction was causing type E_ERROR. This has been fixed.
	
	WCFM Ultimate version had a conflict which we have resolved now.


= 1.1.1 = 

1. New:
	Automatic Relisting: This feature will enable auctioneers to relist their auctions automatically based on conditions they choose like
		If Winner has not paid
		If Auction expired without bids
		If Reserve Price was not met

	Variable Increment: This feature will allow auctioneers to mention different incremental value for different bid amounts. Previously, auctioneer were only able to mention a fixed increment value based on which subsequent bids were placed. This will help auctioneers to get good bidding amount for their products.

	Block/Un-Block User to place bid: Admin can now block any registered user to place their bid. If blocked then user will not be able to place bid and will get an alert message. This will help auctioneers to block users who win but dont pay final bid amount.

	Instant Bidding: Admin gets a configuration under Auction Settings which when enabled will place bid instantly using AJAX based requests without page refresh. 

	Show Countdown timer on shop page: Admin gets a new configuration to enable timer for auction products on shop page. Configuration is under: Auction->Settings->Display Settings Tab -> Shop Page Setting -> Enable/Disable countdown on Shop(Product loop) Page)

	Hide "Product Condition" field on Auction detail page: New configuration has been added under Settings > Display Settings to hide this field. 

2. Improvement:

	Plugin Menu: We have organized plugin menu in better way so that it is more convenient for admin to access all settings and information in it.

	Anti-Sniping/Soft Close: We have added two options for this feature. One option will recursively extend expiration time if bids are placed during a specific time interval. Second option will extend expiration only once and will send email to all bidders intimating this extension. Admin can now choose what behavior they want.

	Add Auction Product form now has options to choose bidding, buy now or both.

	Multiple checkout option for users: If any user has won multiple auction products, he/she had to individually click "Pay Now" link under their My Accounts > Auctions > Won Bids to checkout that product and pay. Now, plugin offers a multiple check out option which will add all their won products to their WooCommerce's checkout page and they can pay for them together. 

	We have now renamed "Scheduled", "Pending" words to "Future" so that it is easier for admin to understand future auctions.

3. Fix:

	Shop page was not listing auctions if admin edited any auction. This bug appeared due to wrong query implementation but is now fixed.

	Multiple warnings with the plugin has been fixed.
	
	
= 1.1.0 =

1. New:

	PRO version will now let users add auction using a popular multi-vendor plugin - "WCFM Multi-Vendor Marketplace Plugin by WC Lovers". We proudly announce that PRO version now integrates with this plugin.
	
	Admins who want their users to add auction can refer this url - https://docs.auctionplugin.net/article/89-how-your-users-can-add-auction
	
	Article about WCFM Multi-Vendor Marketplace is here - https://docs.auctionplugin.net/article/90-wcfm-marketplace-by-wc-lovers

2. Improvement

	Negative bid values wont be accepted.
	
	Buy now can be disabled if user enters ZERO value for it.

3. Fix:

	Alert confirmation while placing bid had issues with page reload due to which "Cancel" button was not cancelling the bid. This has been fixed.		


= 1.0.4 =

1. Proxy Bidding: Outbid email is sent to bidder when bid from other users is higher or lower than  max/min bidding amount depending on normal or reverse auction type.
 

2. Silent Auction:Incorrect bidding value was being displayed to the logged in user when bid was being placed. This has now been fixed.
 

3. Private Message notifications: Recipients input field when had {seller} placeholder, then this email notification was not working. This has now been fixed.


= 1.0.3 =

1. Improvement:

		Private Message notifications: Email notification now has a new placeholder: {seller} that can be entered inside “Recipients” field for sellers to receive this notification from users.

2. Fix:

	Silent Auction
		Removed “Winning” and “Losing” labels as these were silent auctions.
		
	Wrong bidding value in email for subsequent bidders were shown. This has been fixed.
	
	
= 1.0.2 =

1. New:

Silent auction: New admin configuration added under Auctions > Settings > Auction Settings > Silent Auctions > “Restrict users to bid only one time”. This when activated will allow user to bid only one time.
 

New admin configuration under Auctions > Settings > Auction Settings > Extra Settings > Enable an alert box. This will show an alert box when user will place a bid.
 

Proxy Bidding: New admin configuration added under Auctions > Settings > Auction Settings > Proxy Auction > “Disable Proxy Bidding to happen for amount less than Reserve Price too.”
 

New email notification is sent to bidder when his/her bid is deleted.

2. Improvement:

Bid Sniping: We have implemented this logic to occur only once and send an ending soon email to all bidders when the auction is extended due to it.
 

Relist Email was missing reason for relisting. This has been added.
 

3. Fix:

Silent Auction

Bid notification on the auction detail page was showing the outbid value. Since it is a silent auction, this has been removed.
 
Ending Soon Email was arriving late. There was a timezone conversion issue due to which this email was sent at inappropriate time. This has been fixed.
 

Proxy Bidding

Outbid Email for same users were coming earlier. This issue has been fixed.

When highest bidder will place higher bid then his maximum bid will increase. Previously bid was being placed.

Few text were missing translation. They have now been included.

= 1.0.1 =

1. New Feature - We have added new configuration: "Disable the Buy It Now option once bidding has reached the reserve price". Enjoy this feature.

2. Fix - Few texts inside plugin were missing to be translated. We have tested and included all texts. You can now translate all using LocoTranslate.

3. Fix - Payment link in winning email had a query issue. This has now been fixed and you wont see "Empty Cart" message.

4. Fix - Bid value placed by automated bidding appearing inside Auction Detail page were missing currency format set by WooCommerce. This has now been fixed.

= 1.0 =
Initial Release