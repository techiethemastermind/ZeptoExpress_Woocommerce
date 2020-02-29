<?php

/**
 * WC_Shipping_Zepto_3H class.
 *
 * @class 		WC_Shipping_Zepto_3H
 * @version		1.0.0
 * @package		Shipping-for-WooCommerce/Classes
 * @category	Class
 * @author 		Techie Softwares, techiethemastermind@gmail.com
 */
class WC_Shipping_Zepto_3H extends WC_Shipping_Method {

	/**
	 * Constructor. The instance ID is passed to this.
	 */
	public function __construct( $instance_id = 0 ) {
		$this->id                    = 'ZTS_3H';
		$this->instance_id           = absint( $instance_id );
		$this->method_title          = __( 'ZeptoExpress 3 Hours' );
		$this->method_description    = __( 'Zepto Shipping method in 3 hours.' );
		$this->supports              = array(
			'shipping-zones',
			'instance-settings',
		);
		$this->instance_form_fields = array(
			'enabled' => array(
				'title' 		=> __( 'Enable/Disable' ),
				'type' 			=> 'checkbox',
				'label' 		=> __( 'Enable this shipping method' ),
				'default' 		=> 'yes',
			),
			'title' => array(
				'title' 		=> __( 'ZeptoExpress (3 Hours Express)' ),
				'type' 			=> 'text',
				'description' 	=> __( 'This controls the title which the user sees during checkout.' ),
				'default'		=> __( 'ZeptoExpress (3 Hours Express)' ),
				'desc_tip'		=> true
			)
		);
		$this->enabled              = $this->get_option( 'enabled' );
		$this->title                = $this->get_option( 'title' );

		add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_admin_options' ) );
    }
    
    /**
     * calculate_shipping function.
     * @param array $package (default: array())
     */
    public function calculate_shipping( $package = array() ) {

        $api_url = "https://zeptoapi.com/api/rest/calculator/postcode/";

        // Get api id and token from DB
        global $table_prefix, $wpdb;
        $option_table = $table_prefix . 'zeptodb';

        $api_id = $wpdb->get_var( "SELECT `value` FROM $option_table WHERE `option` = 'api_id'" );
        $api_token = $wpdb->get_var( "SELECT `value` FROM $option_table WHERE `option` = 'api_token'" );
        $postcode_from = $wpdb->get_var( "SELECT `value` FROM $option_table WHERE `option` = 'postcode'" );

        $weight = 0;
        $quantity = 0;
        foreach($package['contents'] as $item) {
            $item_weight = ($item['data']->get_weight() == "") ? 0 : $item['data']->get_weight();
            $quantity += $item['quantity'];
            $weight += $item['quantity'] * $item_weight;
        }

        $vehicle = 1;                    // zeptobike
        if($weight > 15) $vehicle = 2;  // zeptocar

        $query = array(
            "app_id" => $api_id,
            "token" => $api_token,
            "pickup" => $postcode_from,
            "delivery" => $package['destination']['postcode'],
            "country" => $package['destination']['country'],
            "vehicle" => $vehicle,
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
            $zepto_cost = number_format($quantity * $result_array['result'][0]['price_myr'], 2);
            $this->add_rate(
                array(
                    'id'    => $this->id . $this->instance_id,
                    'label' => $this->title,
                    'cost'  => $zepto_cost,
                )
            );
        }
    }
}