<?php

/*
* Plugin Name: Zepto Express Shipping Plugin
* Plugin URI: https://www.zeptoexpress.com/
* Description: This is the plugin to use ZeptoExpress.
* Version: 1.0
* Author: Techie
* Author URI: http://zeptoexpress.com/
*/

function zepto_active() {

	// Check woocommerce installed

    if ( !class_exists( 'WooCommerce' ) ) {
		echo '<p class="error">Please install Woocommerce!</p>';
		exit;
	}

	// Setup DB

	global $table_prefix, $wpdb;

	$option_table = $table_prefix . 'zeptodb';
	$track_table = $table_prefix . 'zeptodb_track';
    $charset_collate = $wpdb->get_charset_collate();

    // Check to see if the option table exists already, if not, then create it
    if($wpdb->get_var( "show tables like '$option_table'" ) != $option_table) {

        $sql = "CREATE TABLE `". $option_table . "` ( ";
        $sql .= "  `id`  int(11)   NOT NULL auto_increment, ";
		$sql .= "  `option`  varchar(150)   NOT NULL, ";
		$sql .= "  `value`  varchar(150)   NOT NULL, ";
        $sql .= "  PRIMARY KEY (`id`) "; 
        $sql .= ") $charset_collate;";
        require_once( ABSPATH . '/wp-admin/includes/upgrade.php' );
        dbDelta($sql);
	}

	$data = array('option' => 'setup', 'value' => '0');
	$format = array('%s','%s');
	$wpdb->insert($option_table, $data, $format);
	
	// Check to see if the track table exists already, if not, then create it
    if($wpdb->get_var( "show tables like '$track_table'" ) != $track_table) {

        $sql = "CREATE TABLE `". $track_table . "` ( ";
        $sql .= "  `id`  int(11)   NOT NULL auto_increment, ";
		$sql .= "  `order_id`  varchar(150)   NOT NULL, ";
		$sql .= "  `track_number`  varchar(150)   NOT NULL, ";
        $sql .= "  PRIMARY KEY (`id`) "; 
        $sql .= ") $charset_collate;";
        require_once( ABSPATH . '/wp-admin/includes/upgrade.php' );
        dbDelta($sql);
	}

	// Go to Admin UI
	$site_url = get_site_url();
	header("location: ", $site_url . "/wp-admin/admin.php?page=zepto-plugin");
}
register_activation_hook( __FILE__, 'zepto_active' );

// Admin
if ( is_admin() ) {
    // we are in admin mode
    require_once( dirname( __FILE__ ) . '/zeptoexpress-admin.php' );
}

/** Add Needed Scripts */
add_action( 'admin_enqueue_scripts', 'zepto_scripts' );
function zepto_scripts(){
	// Script
    wp_enqueue_script('zepto_custom_script', plugins_url( 'assets/js/script.js', __FILE__ ), '', '1.0' );
    
    // Styles
    wp_enqueue_style( 'zepto_custom_style', plugins_url( 'assets/css/custom.css', __FILE__ ), '', '1.0' );
}

function zepto_shipping_method_init() {

	// Include zepto shipping method class
	include_once( dirname( __FILE__ ) . '/inc/shipping_rate_3h.php' );
	include_once( dirname( __FILE__ ) . '/inc/shipping_rate_ndd.php' );
}
add_action( 'woocommerce_shipping_init', 'zepto_shipping_method_init' );

// Register Shipping Zone
add_filter( 'woocommerce_shipping_methods', 'register_zepto_method' );

function register_zepto_method( $methods ) {

	// $method contains available shipping methods
	$methods[ 'ZTS_3H' ] = 'WC_Shipping_Zepto_3H';
	$methods[ 'ZTS_NDD' ] = 'WC_Shipping_Zepto_NDD';
	return $methods;
}

// Send Request after order completed.
add_action( 'woocommerce_order_status_completed', 'zepto_book_order', 10, 2 );
add_action( 'woocommerce_order_status_processing', 'zepto_book_order', 10, 1 );
function zepto_book_order( $order_id ){

	// Process order to zeptoexpress
	include_once( dirname( __FILE__ ) . '/inc/booking_order.php' );
}

// Add tracking number to order detail page
add_filter( 'woocommerce_order_details_after_order_table' , 'display_admin_order_meta_tracking_number', 20, 1 ); // Front
add_action( 'woocommerce_admin_order_data_after_billing_address', 'display_admin_order_meta_tracking_number', 20, 1 ); // Admin
function display_admin_order_meta_tracking_number( $order ){
	
	global $table_prefix, $wpdb;
	$track_table = $table_prefix . 'zeptodb_track';

	$order_id = $order->get_id();

	if($wpdb->get_var( "SELECT `track_number` FROM $track_table WHERE `order_id` = '$order_id'" )) {
		$track_number = $wpdb->get_var( "SELECT `track_number` FROM $track_table WHERE `order_id` = '$order_id'" );
		echo "<h5>Tracking Number: <a href='https://www.zeptoexpress.com/my/live-tracker?deliveryid=PE1015A-" . $track_number . "' target='_blank'>PE1015A-$track_number</a></h5>";
	}
}