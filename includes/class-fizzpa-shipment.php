<?php

class Fizzpa_Shipment_Method {
    /**
     * Shipping template.
     * 
     * @since 1.0.0
     */
    public function shipment_template($order) {
        $get_userdata = get_userdata(get_current_user_id());
        if (
            ! $get_userdata->allcaps['edit_shop_order']
            || ! $get_userdata->allcaps['read_shop_order']
            || ! $get_userdata->allcaps['edit_shop_orders']
            || ! $get_userdata->allcaps['edit_others_shop_orders']
            || ! $get_userdata->allcaps['publish_shop_orders']
            || ! $get_userdata->allcaps['read_private_shop_orders']
            || ! $get_userdata->allcaps['edit_private_shop_orders']
            || ! $get_userdata->allcaps['edit_published_shop_orders']
        ) {
            return false;
        }

        $order_id = $order->get_id();

        remove_filter('comments_clauses', ['WC_Comments', 'exclude_order_comments']);

        $history = get_comments([
            'post_id' => $order_id,
            'orderby' => 'comment_ID',
            'order' => 'DESC',
            'approve' => 'approve',
            'type' => 'order_note',
        ]);
        add_filter('comments_clauses', ['WC_Comments', 'exclude_order_comments']);

        ob_start();
        require_once 'shipment-template.php';
        echo ob_get_clean();
    }
}