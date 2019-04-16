<?php

include './database.php';

// Create connection
$db = new mysqli($servername, $username, $password, $db_name, $port);

// Check connection
if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}

echo_it( "Connected successfully" );

// Helper functions
function log_it($log) {
    file_put_contents('./log/log_'.date("Y-m-d").'.log', $log . PHP_EOL, FILE_APPEND);
}

function echo_it( $text ) {
    echo $text . PHP_EOL;
}


function table_exists(&$db, $table)
{
    $result = $db->query("SHOW TABLES LIKE '{$table}'");
    if( $result->num_rows == 1 )
    {
            return TRUE;
    }
    else
    {
            return FALSE;
    }
    $result->free();
}



$table = 'wp_va54ib_product_relations_order_items';

$db->query("DELETE FROM $table");

if ( !table_exists( $db, $table) ) {
     // sql to create table
    $sql = "CREATE TABLE $table (
    id BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_id BIGINT(20) NOT NULL,
    related_id BIGINT(20) NOT NULL
    )";

    if ($db->query($sql) === TRUE) {
        echo "Table " . $table . " created successfully";
    } else {
        echo "Error creating table: " . $db->error;
    }
}

$sql = "SELECT ID FROM wp_va54ib_posts WHERE post_type = 'shop_order' AND (post_status = 'wc-completed' OR post_status = 'wc-processing')";

$result = $db->query($sql);

if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $orders[] = $row['ID'];
    }
}


foreach ($orders as $order) {
    $sql =   "SELECT * FROM wp_va54ib_woocommerce_order_items WHERE order_item_type = 'line_item' AND order_id = $order ";

    $result = $db->query($sql);

    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $orders_with_items[ $order ][] = $row['order_item_id'];
        }
    }
}

foreach($orders_with_items as $key => &$order_with_items) {
    if (count($order_with_items) > 1) {
        foreach ($order_with_items as &$item) {
            $sql =   "SELECT meta_value FROM wp_va54ib_woocommerce_order_itemmeta WHERE order_item_id = $item AND meta_key = '_product_id' ";

            $result = $db->query($sql);

            if ($result->num_rows > 0) {
                while($row = $result->fetch_assoc()) {
                    $item = $row['meta_value'];
                }
            }
        }
    }
}

foreach($orders_with_items as $key => $order_with_items) {
    if (count($order_with_items) > 1) {
        foreach ($order_with_items as $item) {
            foreach ($order_with_items as $item_again) {
                if ($item != $item_again) {
                    $final_result[] = array(
                        'product_id' => $item,
                        'related_id' => $item_again
                    );
                }
            }
        }
    }
}

foreach ($final_result as  $result) {

    $product_id = $result['product_id'];
    $related_id = $result['related_id'];

    $sql = "SELECT ID FROM wp_va54ib_posts WHERE ID = $related_id AND post_type = 'product' AND post_status = 'publish' ";
    $result = $db->query($sql);
    if ($result->num_rows > 0) {
        $sql = "INSERT INTO $table (product_id, related_id)
        VALUES ($product_id, $related_id)";

        if ($db->query($sql) === TRUE) {
            echo "New record created successfully";
        }
    }

}

$db->close();
