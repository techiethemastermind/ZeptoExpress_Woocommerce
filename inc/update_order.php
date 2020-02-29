<?php

/**
 * Retrive this file from ZeptoExpress
 * @param:  order_id, delivery_status
 * @example: https://localhost/book_deal/wp-content/plugins/zeptoexpress/inc/update_order.php?order_id=257&delivery_status=completed&track_number=PE1015A-6278
 */


if(isset($_GET['order_id']) && isset($_GET['delivery_status'])){

    require('../../../../wp-load.php');

    $order_id = $_GET['order_id'];
    $track_number = $_GET['track_number'];
    $status = $_GET['delivery_status'];

    // Get Order object
    $order = wc_get_order(  $order_id );

    // The text for the note
    $note = __("Tracking Number : " . $track_number . "<br>Delivery Status: " . $status );

    // Add the note
    $order->add_order_note( $note );

}

