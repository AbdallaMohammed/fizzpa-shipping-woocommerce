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

        $city = 'riyadh';
        $lang = ! preg_match('/[^A-Za-z0-9]/', $string) ? 'en' : 'ar';

        $response = wp_remote_get('https://fizzapi.anyitservice.com/api/locations/cities/' . $city . '/' . $lang, [
            'timeout' => 30,
            'redirection' => 5,
            'httpversion' => '1.0',
            'headers' => [
                'Authorization' => 'IVLC0PXQILMZ30OT8WGF8SUT3LFY65JUY3E6IBP755HER37GE5CYYIFYA0HOK8TLB1A8VRL6GIJDLQCAM8UA3T4PYP5Q2AX6M5CZ',
                'Referer' => 'http://localhost',
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