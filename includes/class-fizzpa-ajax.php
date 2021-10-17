<?php

class Fizzpa_Ajax {
    /**
     * Register ajax actions.
     * 
     * @since 1.0.0
     */
    public function init() {
        add_action('wp_ajax_fizzpa_get_order_settings', [$this, 'get_order_settings']);
        add_action('wp_ajax_fizzpa_shipment', [$this, 'create_shipment']);
        add_action('wp_ajax_fizzpa_get_shipment', [$this, 'get_shipment']);
        add_action('wp_ajax_fizzpa_tracking_order', [$this, 'tracking_order']);
        add_action('wp_ajax_fizzpa_print_order', [$this, 'print_order']);
    }

    public function get_order_settings() {
        check_admin_referer('fizzpa_nonce', 'nonce');
        
        $order_id = absint($_REQUEST['order_id']);
        $order = wc_get_order($order_id);

        if (! $order) {
            return wp_send_json_error();
        }

        $data = $order->get_data();

        $email = '';
        $phone = '';

        foreach ($data['meta_data'] as $item) {
            if ($item->key === '_shipping_phone') {
                $phone = $item->value;
            }
            if ($item->key === '_shipping_email') {
                $email = $item->value;
            }
        }

        $email = ! empty($email) ? $email : $order->billing_email;
        $phone = ! empty($phone) ? $phone : $order->billing_phone;

        $total_weight = 0;
        $items = $order->get_items();

        foreach ($items as $item) {
            $item = $item->get_data();
            if ($item['product_id'] > 0) {
                $product = wc_get_product($item['product_id']);
                if (! $product->is_virtual()) {
                    $product_data = $product->get_data();

                    if ($product->is_type('simple')) {
                        $weight = ! empty($product_data['weight']) ? $product_data['weight'] : 0;
                    } elseif ($product->is_type('variation')) {
                        if (empty($product_data['weight'])) {
                            $parent_weight = $product->get_parent_data();
                            $weight = $parent_weight['weight'];
                        } else {
                            $weight = $product_data['weight'];
                        }
                    }

                    $total_weight += $weight * $item['qty'];
                }
            }
        }

        $settings = get_option('woocommerce_fizzpa_settings');

        $data = [
            'SenderPhone' => $phone,
            'SenderName' => fizzpa_get_username($order),
            'SenderEmail' => $email,
            'RecipientCityId' => fizzpa_get_order_city($order),
            'RecipientName' => fizzpa_get_username($order),
            'RecipientPhone1' => $phone,
            'RecipientAddress' => fizzpa_get_order_address_1($order),
            'RecipientNeighborhood' => fizzpa_get_order_address_2($order),
            'OrderNote' => $order->get_customer_note(),
            'PickupAddressId' => ! empty($settings['pickup_address_id']) ? $settings['pickup_address_id'] : 1,
            'OrderCollectionTypeId' => $collection_type = fizzpa_get_order_collection_type($order),
            'OrderPiecesCount' => (int) $order->get_item_count(),
            'OrderTotalWeight' => $total_weight,
            'OrderRef' => $order->get_id(),
            'PickupAddresses' => fizzpa_get_pickup_addresses(),
            'CodAmount' => 0,
        ];

        if ($collection_type === 3) {
            $data['CodAmount'] = (int) $order->get_total();
        }

        return wp_send_json_success($data);
    }

    public function create_shipment() {
        check_admin_referer('fizzpa_nonce', 'nonce');

        $settings = get_option('woocommerce_fizzpa_settings');

        $_REQUEST['RecipientCityId'] = fizzpa_get_recipient_city_id($_REQUEST['OrderRef']);

        $response = wp_remote_post('https://fizzapi.anyitservice.com/api/orders', [
            'timeout' => 30,
            'redirection' => 5,
            'httpversion' => '1.0',
            'headers' => [
                'Accept' => 'application/json',
                'Authorization' => $settings['token'],
                'Referer' => $settings['referer'],
            ],
            'body' => $_REQUEST,
        ]);

        if (is_wp_error($response)) {
            return wp_send_json_error($response);
        }

        $body = json_decode($response['body']);

        if ($body->success) {
            $message = sprintf(esc_html__('Fizzba Order No. %s', 'fizzba'), $body->OrderId);
    
            wp_new_comment([
                'comment_post_ID' => $_REQUEST['OrderRef'],
                'comment_author' => '',
                'comment_author_email' => '',
                'comment_author_url' => '',
                'comment_content' => $message,
                'comment_type' => 'order_note',
                'user_id' => '0',
            ]);
    
            $order = wc_get_order($_REQUEST['OrderRef']);
            $order->add_order_note($message);
            $order->save(); 
    
            if (! empty($order)) {
                $order->update_status('on-hold', __('Fizzba shipment created.', 'fizzba'));
            }
        }

        return wp_send_json_success(json_decode($response['body'], true));
    }

    public function get_shipment() {
        check_admin_referer('fizzpa_nonce', 'nonce');

        $order_id = fizzba_get_order_id($_REQUEST['order_id']);

        if (! $order_id) {
            return wp_send_json_error();
        }

        $settings = get_option('woocommerce_fizzpa_settings');

        $response = wp_remote_get('https://fizzapi.anyitservice.com/api/orders/' . $order_id, [
            'timeout' => 30,
            'redirection' => 5,
            'httpversion' => '1.0',
            'headers' => [
                'Accept' => 'application/json',
                'Authorization' => $settings['token'],
                'Referer' => $settings['referer'],
            ],
        ]);

        if (is_wp_error($response)) {
            return wp_send_json_error($response);
        }

        return wp_send_json_success(json_decode($response['body']));
    }

    public function tracking_order() {
        check_admin_referer('fizzpa_nonce', 'nonce');

        $order_id = fizzba_get_order_id($_REQUEST['order_id']);

        if (! $order_id) {
            return wp_send_json_error();
        }

        $settings = get_option('woocommerce_fizzpa_settings');

        $response = wp_remote_get('https://fizzapi.anyitservice.com/api/Tracking/' . $order_id, [
            'timeout' => 30,
            'redirection' => 5,
            'httpversion' => '1.0',
            'headers' => [
                'Accept' => 'application/json',
                'Authorization' => $settings['token'],
                'Referer' => $settings['referer'],
            ],
        ]);

        if (is_wp_error($response)) {
            return wp_send_json_error($response);
        }

        return wp_send_json_success(json_decode($response['body']));
    }

    public function print_order() {
        check_admin_referer('fizzpa_nonce', 'nonce');

        $settings = get_option('woocommerce_fizzpa_settings');

        return wp_send_json_success([
            'headers' => [
                'Authorization' => $settings['token'],
            ],
            'order_id' => fizzba_get_order_id($_REQUEST['order_id']),
        ]);
    }
}