<?php

function ch_product_relations_order_items( $id )
{
    global $wpdb;

    $table_name = $wpdb->prefix.'ch_product_relations_order_items';
    if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name ) {
        return false;
    }

    $cur_product = get_the_ID();
    $relatonships = $wpdb->get_results(
        "
	SELECT related_id, count(related_id)
	FROM wp_va54ib_ch_product_relations_order_items
	WHERE product_id = $id
	GROUP BY related_id
	ORDER BY count(related_id) DESC, related_id DESC
	LIMIT 4 "
    );

    return $relatonships;
}
