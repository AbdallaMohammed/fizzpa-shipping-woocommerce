<?php

if (! function_exists('fizzba_get_order_id')) {
    function fizzba_get_order_id($order) {
        if (is_numeric($order)) {
            $order = wc_get_order($order);
        }

        if (! $order) {
            return 0;
        }

        $order_id = $order->get_id();
        $history = wc_get_order_notes([
            'post_id' => $order_id,
        ]);

        $history_list = [];
        foreach ($history as $shipment) {
            if ($shipment->customer_note) {
                continue;
            }

            $history_list[] = $shipment->content;
        }

        $last_track = 0;
        if (count($history_list)) {
            foreach ($history_list as $history) {
                $orderID = trim(str_replace(__('Fizzba Order No.', 'fizzba'), '', $history));
    
                if (isset($orderID)) {
                    if ((int) $orderID) {
                        $last_track = (int) $orderID;
                        break;
                    }
                }
            }
        }

        return $last_track;
    }
}

if (! function_exists('fizzpa_get_recipient_city_id')) {
    function fizzpa_get_recipient_city_id($order) {
        if (is_numeric($order)) {
            $order = wc_get_order($order);
        }

        if (! $order) {
            return 1;
        }

        $order_id = fizzba_get_order_id($order);

        $settings = get_option('woocommerce_fizzpa_settings');

        if (empty($settings)) {
            return 1;
        }

        $city = $order->get_shipping_city();
        $lang = ! preg_match('/[^A-Za-z0-9]/', $city) ? 'en' : 'ar';

        $response = wp_remote_get('https://fizzapi.anyitservice.com/api/locations/cities/' . $city . '/' . $lang, [
            'timeout' => 30,
            'redirection' => 5,
            'httpversion' => '1.0',
            'headers' => [
                'Authorization' => $settings['token'],
                'Referer' => $settings['referer'],
            ],
        ]);

        if (is_wp_error($response)) {
            return 1;
        }

        $body = json_decode($response['body']);

        if ($body) {
            return $body->CityId;
        }

        return 1;
    }
}

if (! function_exists('fizzpa_get_pickup_addresses')) {
    function fizzpa_get_pickup_addresses() {
        $settings = get_option('woocommerce_fizzpa_settings');

        if (empty($settings)) {
            return [];
        }

        $data = [];
        $addresses = wp_remote_get('https://fizzapi.anyitservice.com/api/locations/AgentAddress', [
            'timeout' => 30,
            'redirection' => 5,
            'httpversion' => '1.0',
            'headers' => [
                'Accept' => 'application/json',
                'Authorization' => $settings['token'],
                'Referer' => $settings['referer'],
            ],
        ]);

        if (! is_wp_error($addresses)) {
            $addresses = json_decode($addresses['body'], true);
            foreach ($addresses as $address) {
                $data[$address['AddressNumber']] = $address['Address'];
            }
        }

        return $data;
    }
}

if (! function_exists('fizzpa_get_order_collection_type')) {
    function fizzpa_get_order_collection_type($order) {
        $payment_gateway = wc_get_payment_gateway_by_order($order);

        if ($payment_gateway->id === 'cod') {
            return 3;
        }

        return 1;
    }
}

if (! function_exists('fizzpa_get_address_type')) {
    function fizzpa_get_address_type() {
        $settings = get_option('woocommerce_fizzpa_settings');

        return ! empty($settings['address_type']) ? $settings['address_type'] : 'shipping';
    }
}

if (! function_exists('fizzpa_get_order_city')) {
    function fizzpa_get_order_city($order) {
        if (is_numeric($order)) {
            $order = wc_get_order($order);
        }

        return fizzpa_get_address_type() == 'shipping' ? $order->get_shipping_city() : $order->get_billing_city();
    }
}

if (! function_exists('fizzpa_get_order_address_1')) {
    function fizzpa_get_order_address_1($order) {
        if (is_numeric($order)) {
            $order = wc_get_order($order);
        }

        return fizzpa_get_address_type() == 'shipping' ? $order->get_shipping_address_1() : $order->get_formatted_billing_address();
    }   
}

if (! function_exists('fizzpa_get_order_address_2')) {
    function fizzpa_get_order_address_2($order) {
        if (is_numeric($order)) {
            $order = wc_get_order($order);
        }

        return fizzpa_get_address_type() == 'shipping' ? $order->get_shipping_address_2() : $order->get_formatted_billing_address();
    }   
}

if (! function_exists('fizzpa_get_username')) {
    function fizzpa_get_username($order) {
        if (is_numeric($order)) {
            $order = wc_get_order($order);
        }

        if (fizzpa_get_address_type() == 'shipping') {
            return $order->get_shipping_first_name() . ' ' . $order->get_shipping_last_name();
        }

        return $order->get_billing_first_name() . ' ' . $order->get_billing_last_name();
    }
}