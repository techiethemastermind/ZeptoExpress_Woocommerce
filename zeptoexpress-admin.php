<?php
/* Admin */

add_action('admin_menu', 'zepto_plugin_setup_menu');
function zepto_plugin_setup_menu() {
    add_menu_page( 'ZeptoExpress Page', 'ZeptoExpress', 'manage_options', 'zepto-plugin', 'zepto_init' );
}

function zepto_init() {

    // Check plugin set or not
    global $table_prefix, $wpdb;
    $option_table = $table_prefix . 'zeptodb';
    
    if($wpdb->get_var( "SELECT `value` FROM $option_table WHERE `option` = 'setup'" ) != '1') {
        echo '<div class="notice is-dismissible notice-info">
                <p><strong>Please Setup Zepto Plugin!</strong></p>
              </div>';
    }

    admin_ui();
}

function admin_ui() {

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

    $address = ( $store_address_2 ) ? $store_address . ', ' . $store_address_2 : $store_address;
    $whole_address = $address . ' ' . $store_city . ', ' . $store_state . ' ' . $store_country;

    // Get options from DB

    global $table_prefix, $wpdb;
    $option_table = $table_prefix . 'zeptodb';

    $api_id = '';
    $api_token = '';

    if($wpdb->get_var( "SELECT `value` FROM $option_table WHERE `option` = 'api_id'" )) {
        $api_id = $wpdb->get_var( "SELECT `value` FROM $option_table WHERE `option` = 'api_id'" );
    }

    if($wpdb->get_var( "SELECT `value` FROM $option_table WHERE `option` = 'api_token'" )) {
        $api_token = $wpdb->get_var( "SELECT `value` FROM $option_table WHERE `option` = 'api_token'" );
    }

?>

<div class="container">
    <h3>ZeptoExpress Plugin Setup</h3>
    <hr>
    <form id="zepto_form" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="POST">
        <table class="form-table" role="presentation">
            <tbody>
                <tr>
                    <th scope="row">Store Address: </th>
                    <td><input name="store_address" type="text" id="store_address" value="<?php echo $whole_address; ?>" class="regular-text" disabled></td>
                </tr>
                <tr>
                    <th scope="row">Postcode: </th>
                    <td><input name="store_postcode" type="text" id="store_postcode" value="<?php echo $store_postcode; ?>" class="regular-text"></td>
                </tr>
                <tr>
                    <th scope="row">ZeptoExpress API Id: </th>
                    <td><input name="zepto_id" type="text" id="zepto_id" value="<?php echo $api_id; ?>" class="regular-text" ></td>
                </tr>
                <tr>
                    <th scope="row">ZeptoExpress App Token: </th>
                    <td><input name="zepto_token" type="text" id="zepto_token" value="<?php echo $api_token; ?>" class="regular-text" ></td>
                </tr>
            </tbody>
        </table>
        <input type="hidden" name="action" value="zepto_form">
        <p>If you have ZeptoExpress Api account then find App Id and Token in here: <a href="https://zeptoapi.com/api/rest/login/" target="_blank" rel="noopener noreferrer">Login to Console</a></p>
        <p>If you have no account then Sing up in here: <a href="https://zeptoapi.com/api/rest/register/" target="_blank" rel="noopener noreferrer">Sign Up</a></p>
        <p class="submit"><input type="submit" name="submit" id="zepto_submit" class="button button-primary" value="Save Changes" disabled></p>
    </form>
</div>

<?php
}

add_action( 'admin_post_zepto_form', 'zepto_form_response');
function zepto_form_response() {

    // Check plugin set or not
    global $table_prefix, $wpdb;
    $option_table = $table_prefix . 'zeptodb';

    $data = array();

    // Add Data to DB
    $data[0] = array('option' => 'postcode', 'value' => $_POST['store_postcode']);
    $data[1] = array('option' => 'api_id', 'value' => $_POST['zepto_id']);
    $data[2] = array('option' => 'api_token', 'value' => $_POST['zepto_token']);
    $format = array('%s','%s');

    foreach($data as $row){
        // Check exist or not
        if(!$wpdb->get_var( "SELECT `value` FROM $option_table WHERE `option` = '" . $row['option'] ."'" )) {
            $wpdb->insert($option_table, $row, $format);
        } else {
            $wpdb->update($option_table, array('value' => $row['value']), array('option' => $row['option']));
        }
    }

    // set setup to 1
    $wpdb->update($option_table, array('value' => '1'), array('option' => 'setup'));

    wp_safe_redirect( admin_url( 'admin.php?page=zepto-plugin' ) );
}