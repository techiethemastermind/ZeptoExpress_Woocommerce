<?php
/**
 * Order completed Processing
 * Author:
 * Techie Softwares, techiethemastermind@gmail.com
 */

// Get api id and token from DB
global $table_prefix, $wpdb;
$option_table = $table_prefix . 'zeptodb';
$track_table = $table_prefix . 'zeptodb_track';

// Check already booked or not
$order_track_number = $wpdb->get_var( "SELECT `track_number` FROM $track_table WHERE `order_id` = '$order_id'" );

if(!is_null($order_track_number) && $order_track_number != "0") {
	return;
}

$api_id = $wpdb->get_var( "SELECT `value` FROM $option_table WHERE `option` = 'api_id'" );
$api_token = $wpdb->get_var( "SELECT `value` FROM $option_table WHERE `option` = 'api_token'" );

$order = wc_get_order( $order_id );
$order_data = $order->get_data();

// Billing information for Sender
$order_billing_first_name = $order_data['billing']['first_name'];
$order_billing_last_name = $order_data['billing']['last_name'];
$order_billing_full_name = $order_billing_first_name . " " . $order_billing_last_name;

$order_billing_email = $order_data['billing']['email'];
$order_billing_phone = $order_data['billing']['phone'];

$order_billing_address_1 = $order_data['billing']['address_1'];
$order_billing_address_2 = $order_data['billing']['address_2'];
$order_billing_city = $order_data['billing']['city'];
$order_billing_state = $order_data['billing']['state'];
$order_billing_postcode = $order_data['billing']['postcode'];
$order_billing_country = $order_data['billing']['country'];

$order_billing_full_address = '';
$order_billing_full_address = $order_billing_address_1;
$order_billing_full_address .=  ( substr($order_billing_full_address,-1)==',' ? substr($order_billing_full_address,0,-1) :'' );
$order_billing_full_address .= ($order_billing_full_address? ',':'').$order_billing_address_2;
$order_billing_full_address .=  ( substr($order_billing_full_address,-1)==',' ? substr($order_billing_full_address,0,-1) :'' );
$order_billing_full_address .= ($order_billing_postcode.$order_billing_city? ',':'').$order_billing_postcode.' '.$order_billing_city;
$order_billing_full_address .=  ( substr($order_billing_full_address,-1)==',' ? substr($order_billing_full_address,0,-1) :'' );
$order_billing_full_address .= ( $order_billing_state? ',':'').$order_billing_state;
$order_billing_full_address .=  ( substr($order_billing_full_address,-1)==',' ? substr($order_billing_full_address,0,-1) :'' );
$order_billing_full_address .= ( $order_billing_country? ',':'').$order_billing_country;
$order_billing_full_address .=  ( substr($order_billing_full_address,-1)==',' ? substr($order_billing_full_address,0,-1) :'' );

// Shipping information for Reciver
$order_shipping_first_name = ($order_data['shipping']['first_name'] == '') ? $order_billing_first_name : $order_data['shipping']['first_name'];
$order_shipping_last_name = ($order_data['shipping']['last_name'] == '') ? $order_billing_last_name : $order_data['shipping']['last_name'];
$order_shipping_full_name = $order_shipping_first_name . " " . $order_shipping_last_name;

$order_shipping_email = ($order_data['shipping']['email'] == '') ? $order_billing_email : $order_data['shipping']['email'];
$order_shipping_phone = ($order_data['shipping']['phone'] == '') ? $order_billing_phone : $order_data['shipping']['phone'];

$order_shipping_address_1 = ($order_data['shipping']['address_1'] == '') ? $order_billing_address_1 : $order_data['shipping']['address_1'];
$order_shipping_address_2 = ($order_data['shipping']['address_2'] == '') ? $order_billing_address_2 : $order_data['shipping']['address_2'];
$order_shipping_city = ($order_data['shipping']['city'] == '') ? $order_billing_city : $order_data['shipping']['city'];
$order_shipping_state = ($order_data['shipping']['state'] == '') ? $order_billing_state : $order_data['shipping']['state'];
$order_shipping_postcode = ($order_data['shipping']['postcode'] =='') ? $order_billing_postcode : $order_data['shipping']['postcode'];
$order_shipping_country = ($order_data['shipping']['country'] == '') ? $order_billing_country : $order_data['shipping']['country'];

$order_shipping_full_address = '';
$order_shipping_full_address .= $order_shipping_address_1;
$order_shipping_full_address .=  ( substr($order_shipping_full_address,-1)==',' ? substr($order_shipping_full_address,0,-1) :'' );
$order_shipping_full_address .= ($order_shipping_address_2? ',':'').$order_shipping_address_2;
$order_shipping_full_address .=  ( substr($order_shipping_full_address,-1)==',' ? substr($order_shipping_full_address,0,-1) :'');
$order_shipping_full_address .= ($order_shipping_postcode? ',':'').$order_shipping_postcode;
$order_shipping_full_address .=  ( substr($order_shipping_full_address,-1)==',' ? substr($order_shipping_full_address,0,-1) :'');
$order_shipping_full_address .= ' '.$order_shipping_city; 
$order_shipping_full_address .=  ( substr($order_shipping_full_address,-1)==',' ? substr($order_shipping_full_address,0,-1) :'');
$order_shipping_full_address .= ','.$order_shipping_state;
$order_shipping_full_address .=  ( substr($order_shipping_full_address,-1)==',' ? substr($order_shipping_full_address,0,-1) :'');
$order_shipping_full_address .= ','.$order_shipping_country;
$order_shipping_full_address .=  ( substr($order_shipping_full_address,-1)==',' ? substr($order_shipping_full_address,0,-1) :'');

// Get Product info
$quantity = 0;
$weight = 0;
foreach ($order->get_items() as $item_key => $item ){
    $quantity += $item->get_quantity();
    $_product = $order->get_product_from_item( $item );
    if ( ! $_product->is_virtual() ) {
        $weight += $_product->get_weight() * $quantity;
    }

    $product = $item->get_product(); // Get the WC_Product object

    $item_sku = $product->get_sku();
	$item_name = $item->get_name();
	$item_price = $product->get_price();

	$product_list .= $item_name."|";
	$product_quantity .= $quantity."|";
	$product_price .= $item_price."|"; 
}

$product_list = substr($product_list,0,-1);
$product_quantity = substr($product_quantity,0,-1);
$product_price = substr($product_price,0,-1);
$product_currency = $order_data['currency'];

// Store info
$store_address     = get_option( 'woocommerce_store_address' );
$store_address_2   = get_option( 'woocommerce_store_address_2' );
$store_city        = get_option( 'woocommerce_store_city' );
$store_postcode    = get_option( 'woocommerce_store_postcode' );

// The country/state
$store_raw_country = get_option( 'woocommerce_default_country' );

// Split the country/state
$split_country = explode( ":", $store_raw_country );

// Country and state separated:
$store_country = $split_country[0];
$store_state   = $split_country[1];

$address = ( $store_address_2 ) ? $store_address . $store_address_2 : $store_address;
$pickup_address = $address . ' ' . $store_city . ', ' . $store_state . ' ' . $store_country;



// Address Calculate
$api_url = "https://zeptoapi.com/api/rest/calculator/postcode";
$now = date("Y-m-d H:i:s");

$query = array(
    "token" => $api_token,
	"app_id" => $api_id,
	"pickup" => $store_postcode,
	"delivery" => $order_shipping_postcode,
	"country" => 'MY'
);

// Configure curl client and execute request
$ch = curl_init();
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_URL, $api_url);
curl_setopt($ch, CURLOPT_POST, count($query));
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($query));
$result = curl_exec($ch);
curl_close($ch);
$result_array = json_decode($result, true);
$status = $result_array['result'][0]['status'];

if($status) {
    $pickup_latlng = $result_array['result'][0]['pickup_latlng'];
    $delivery_latlng = $result_array['result'][0]['delivery_latlng'];
    $distance_km = $result_array['result'][0]['distance_km'];
    $price_myr = $result_array['result'][0]['price_myr'];
}

$deliveryday_type = 'sd';
$shipping_method = $order->get_shipping_method();
if( strtolower(trim($shipping_method))==strtolower(trim('zts_ndd')) ) $deliveryday_type = 'nd';
if( strtolower(trim($shipping_method))==strtolower(trim('zts_3h')) ) $deliveryday_type = 'sd';

$vehicle = 1;
if($deliveryday_type == 'sd' && $weight>15 ) {
	$vehicle = 2;
}
if($deliveryday_type == 'nd') {
	$vehicle = 13;
}

$shop_name = get_bloginfo();

$api_url = "https://zeptoapi.com/api/rest/booking/new";

$query = array(
	"token" => $api_token,
	"app_id" => $api_id,
	"sender_fullname" => $order_billing_full_name,
	"sender_email" => $order_billing_email,
	"sender_phone" => $order_billing_phone,
	"recipient_fullname" => $order_shipping_full_name,
	"recipient_email" => $order_shipping_email,
	"recipient_phone" => $order_shipping_phone,
	"pickup_address" => $pickup_address,
	"pickup_latlng" => $pickup_latlng,
	"delivery_address" => $order_shipping_full_address,
	"delivery_latlng" => $delivery_latlng,
	"distance_km" => $distance_km,
	"price_myr" => $price_myr,
	"trip_type" => 1,
	"instruction_notes" => 'Please call me when you have reached.',
	"datetime_pickup" => 'NOW',
	"unit_no_pickup" => '',
	"unit_no_delivery" => ''
	,"deliveryday_type"=>$deliveryday_type
	,"vehicle" => $vehicle
	,"country" => 'MY'
	,"riderSelected"=>'714'
	,"shop_name"=>$shop_name
	,"product_list"=>$product_list
	,"product_quantity"=>$product_quantity
	,"product_price"=>$product_price
	,"product_currency"=>$product_currency
	,"product_order_id"=>$order_id
	,"weight_kg"=>$weight
);

$send_params = print_r( json_decode( json_encode($query) ,true ) ,true );

// Configure curl client and execute request
$ch = curl_init();
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_URL, $api_url);
curl_setopt($ch, CURLOPT_POST, count($query));
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($query));
$result = curl_exec($ch);
curl_close($ch);
$result_array = json_decode($result, true);
// var_dump($result_array); exit;
$status = $result_array['booking']['status'];

if($status) {
    $jobid = $result_array['booking']['jobid'];
    $secret_code_pickup = $result_array['result'][0]['secret_code_pickup'];
	$secret_code_delivery = $result_array['result'][0]['secret_code_delivery'];

	// Add tracking number to db
	$jobid = (!$jobid) ? "0" : $jobid;

	if(is_null($order_track_number)){
		$data = array('order_id' => $order_id, 'track_number' => $jobid);
		$format = array('%d','%s');
		$wpdb->insert($track_table, $data, $format);
	} else if ($order_track_number == "0") {
		$wpdb->update($track_table, array('track_number' => $jobid), array('order_id' => $order_id));
	}
} else {
	
	if($result_array['booking']['job_id']){
		$tracking_number = $result_array['booking']['job_id'];
	} else {
		$tracking_number = '0';
	}
	
	if(is_null($order_track_number)){
		$data = array('order_id' => $order_id, 'track_number' => $jobid);
		$format = array('%d','%s');
		$wpdb->insert($track_table, $data, $format);
	} else if ($order_track_number == "0") {
		$wpdb->update($track_table, array('track_number' => $jobid), array('order_id' => $order_id));
	}
}