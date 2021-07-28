<?php

if (! function_exists('fizzba_get_order_id')) {
    function fizzba_get_order_id($order) {
        if (is_numeric($order)) {
            $order = wc_get_order($order);

            if (! $order) {
                return 0;
            }
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