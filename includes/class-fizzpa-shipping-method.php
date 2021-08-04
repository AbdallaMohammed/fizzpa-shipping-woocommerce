<?php

class Fizzpa_Shipping_Method extends WC_Shipping_Method {
    /**
     * Fizzpa_Shipping_Method Constructor.
     */
    public function __construct() {
        $this->id = 'fizzpa';

        $this->method_title = __('Fizzpa', 'fizzpa');
        $this->method_description = __('Fizzpa Shipping Method', 'fizzpa');
        $this->title = __('Fizzpa Shipping', 'fizzpa');

        $this->enabled = isset($this->settings['enabled']) ? $this->settings['enabled'] : 'yes';

        $this->init();
    }

    public function init() {
        $this->init_form_fields();
        $this->init_settings();

        add_action('woocommerce_update_options_shipping_' . $this->id, [$this, 'process_admin_options']);
    }

    public function calculate_shipping($packages = []) {
        if (! empty($this->settings['shipping_rate'])) {
            $this->add_rate([
                'id' => $this->id,
                'title' => $this->title,
                'cost' => (int) $this->settings['shipping_rate'],
            ]);
        }
    }

    public function init_form_fields() {
        $data = fizzpa_get_pickup_addresses();

        $this->form_fields = [
            'enabled' => [
                'title' => __('Enables', 'fizzpa'),
                'type' => 'checkbox',
                'description' => __('Enable Fizzpa Shipping', 'fizzpa'),
                'default' => 'yes',
            ],
            'token' => [
                'title' => __('Auth Token', 'fizzpa'),
                'type' => 'textarea',
            ],
            'pickup_address_id' => [
                'title' => __('Pickup Address', 'fizzpa'),
                'type' => 'select',
                'options' => $data,
            ],
            'address_type' => [
                'title' => __('Address Type', 'fizzpa'),
                'type' => 'select',
                'default' => 'shipping',
                'options' => [
                    'shipping' => __('Shipping Address', 'fizzpa'),
                    'billing' => __('Billing Address', 'fizzpa'),
                ],
            ],
            'shipping_rate' => [
                'title' => __('Shipping Rate', 'fizzpa'),
                'type' => 'text',
            ],
            'referer' => [
                'title' => __('Referer', 'fizzpa'),
                'type' => 'text',
                'default' => 'http://localhost',
            ],
        ];
    }
}