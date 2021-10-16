<?php

/*
Plugin Name:  Fizzpa Shipping WooCommerce
Plugin URI:   https://fizzpa.net/
Description:  Fizzpa Shipping WooCommerce Plugin.
Version:      1.0.0
Author:       fizzpa.net
Author URI:   http://fizzpa.net/
License:      GPL2
License URI:  https://www.gnu.org/licenses/gpl-2.0.html
Text Domain:  fizzpa
Domain Path:  /i18n
*/

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Plugin activation.
 * 
 * @since 1.0.0
 * @return void
 */
function fizzpa_is_requirements_meet() {
    if (
        version_compare(phpversion(), '7.2', '<')
        ||
        version_compare(get_bloginfo('version'), '5.2', '<')
        ||
        ! is_plugin_active('woocommerce/woocommerce.php')
    ) {
        add_action('admin_init', 'fizzpa_auto_deactivate');
        add_action('admin_notices', 'fizzpa_activation_error');
    }
}
add_action('admin_init', 'fizzpa_is_requirements_meet');

/**
 * Auto deactivate plugin.
 * 
 * @return void
 */
function fizzpa_auto_deactivate() {
    deactivate_plugins(plugin_basename(__FILE__));
    if (isset($_GET['activate'])) {
        unset($_GET['activate']);
    }
}

/**
 * Display activation error.
 * 
 * @return void
 */
function fizzpa_activation_error() {
    $messages = [
        sprintf(esc_html__('You are using the outdated WordPress, please update it to version %s or higher.', 'fizzpa'), '5.2'),
        sprintf(esc_html__('Fizzpa requires PHP version %s or above. Please update PHP to run this plugin.', 'fizzpa' ), '7.2'),
        sprintf(esc_html__('Fizzpa requires %s. Please install that plugin to run this plugin'), 'WooCommerce')
    ];
    ?>
    <div class="notice fizzpa-notice notice-error">
        <p>
            <?php echo join('<br>', $messages) ?>
        </p>
    </div>
    <?php
}

function fizzpa_shipping_method() {
    require_once 'includes/class-fizzpa-shipping-method.php';
}
add_action('woocommerce_shipping_init', 'fizzpa_shipping_method');
add_action('woocommerce_product_meta_start', 'fizzpa_shipping_method');

/**
 * Add shipping method.
 * 
 * @since 1.0.0
 * @var array $methods
 * @return array
 */
function fizzpa_add_shipping_method($methods) {
    $methods[] = 'Fizzpa_Shipping_Method';

    return $methods;
}
add_filter('woocommerce_shipping_methods', 'fizzpa_add_shipping_method');

/**
 * Validate orders with fizzpa gateway.
 * 
 * @since 1.0.0
 * @param array $orders
 * @return void
 */
function fizzpa_validate_order($orders) {
    $packages = WC()->shipping->get_packages();
    $methods = WC()->session->get('chosen_shipping_methods');

    if (is_array($methods) && in_array('fizzpa', $methods)) {
        foreach ($packages as $key => $package) {
            if ($methods[$key] !== 'fizzpa') {
                continue;
            }

            $weight = 0;
            foreach ($package['contents'] as $id => $values) {
                $product = $values['data'];
                $weight = $weight + $product->get_weight() * $values['quantity'];
            }
            $weight = wc_get_weight($weight, 'g');
            if ($weight === 0) {
                $message = __('Order\'s weight must be greater that 0 g', 'fizzpa');
                if (! wc_has_notice($message, 'error')) {
                    wc_add_notice($message, 'error');
                }
            }
        }
    }
}
add_action('woocommerce_review_order_before_cart_contents', 'fizzpa_validate_order');
add_action('woocommerce_after_checkout_validation', 'fizzpa_validate_order');

function fizzpa_shipping_fields($fields) {
    $fields['shipping_phone'] = [
        'label' => __('Phone', 'fizzpa'),
        'required' => true,
        'clear' => false,
        'type' => 'tel',
        'class' => ['validate-phone'],
    ];

    $fields['shipping_email'] = [
        'label' => __('Email', 'fizzpa'),
        'required' => true,
        'clear' => false,
        'type' => 'email',
        'class' => ['validate-email'],
    ];

    return $fields;
}
add_filter('woocommerce_shipping_fields', 'fizzpa_shipping_fields');

function fizzpa_init() {
    require_once 'includes/functions.php';
    require_once 'includes/class-fizzpa-shipment.php';

    add_action('woocommerce_admin_order_data_after_shipping_address', [
        new Fizzpa_Shipment_Method(),
        'shipment_template',
    ]);
}
add_action('init', 'fizzpa_init');

require_once 'includes/class-fizzpa-ajax.php';
add_action('admin_init', [
    new Fizzpa_Ajax(),
    'init',
]);

function fizzpa_enqueue_scripts() {
    $screen = get_current_screen();

    if (is_admin() && $screen->id == 'shop_order') {
        wp_enqueue_style('fizzpa-app-css', plugin_dir_url(__FILE__) . 'public/css/app.css', [], '1.0.0');

        wp_enqueue_script('fizzpa-app-js', plugin_dir_url(__FILE__) . 'public/js/app.js', [], '1.0.0', true);
        wp_localize_script('fizzpa-app-js', 'fizzpa_i18n', [
            'admin_ajax' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('fizzpa_nonce'),
        ]);
    }
}
add_action('admin_enqueue_scripts', 'fizzpa_enqueue_scripts');