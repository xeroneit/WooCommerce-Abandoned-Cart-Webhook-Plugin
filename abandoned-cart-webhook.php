<?php
/*
Plugin Name: Abandoned Cart Webhook
Plugin URI: https://botsailor.com/resource/woocommerce-abandoned-cart-recovery-webhook-plugin
Description: A plugin to send abandoned cart data from WooCommerce to a specified webhook URL. Supports BotSailor integration for WhatsApp notifications.
Version: 1.0
Author: M M Muraduzzaman Konok
Author URI: https://botsailor.com
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
*/


// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

 /* global $wpdb;

    $query = $wpdb->get_results("SELECT option_name, option_value FROM {$wpdb->options} WHERE option_name LIKE '%_transient_abandoned_cart_%'");

echo "<pre>";
    print_r($query); */


// Enqueue custom script for phone number change
add_action('wp_enqueue_scripts', 'enqueue_phone_number_script');

function enqueue_phone_number_script() {
    wp_enqueue_script('phone-number-update', plugin_dir_url(__FILE__) . 'js/phone-number-update.js', array('jquery'), null, true);
    wp_localize_script('phone-number-update', 'ajax_obj', array(
        'ajax_url' => admin_url('admin-ajax.php')
    ));
}

// AJAX handler for updating cart with phone number
add_action('wp_ajax_update_cart_with_phone', 'update_cart_with_phone');
add_action('wp_ajax_nopriv_update_cart_with_phone', 'update_cart_with_phone');

function update_cart_with_phone() {
    // Check if phone number is provided
    if (isset($_POST['billing_phone_number'])) {
        $phone_number = sanitize_text_field($_POST['billing_phone_number']);

        // Update customer's billing phone in session
        WC()->customer->set_billing_phone($phone_number);
        WC()->customer->save();
        // Call the capture_cart_data function to save updated cart info
        capture_cart_data(null, null, null, null, null, null);
        wp_send_json_success(array('message' => 'Cart updated with phone number.'));
    }

   else if (isset($_POST['shipping_phone_number'])) {
        $phone_number = sanitize_text_field($_POST['shipping_phone_number']);
        // Update customer's billing phone in session
        WC()->customer->set_shipping_phone($phone_number);
        WC()->customer->save();
        // Call the capture_cart_data function to save updated cart info
        capture_cart_data(null, null, null, null, null, null);
        wp_send_json_success(array('message' => 'Cart updated with phone number.'));
    }

     else {
        wp_send_json_error(array('message' => 'Phone number is missing.'));
    }

    wp_die(); // Stop the script
}


// Register the settings page for the webhook URL
add_action('admin_menu', 'wc_abandoned_cart_webhook_settings_menu');
add_action('admin_init', 'wc_abandoned_cart_webhook_settings_init');

// Hook to track when a product is added to the cart
add_action('woocommerce_add_to_cart', 'capture_cart_data', 10, 6);
//add_action('woocommerce_checkout_update_order_review', 'capture_cart_data');
//add_action('woocommerce_after_cart_update', 'capture_cart_data');

// Hook to monitor checkout form submission and clear the abandoned cart data
add_action('woocommerce_checkout_order_processed', 'remove_abandoned_cart_transient');

// Schedule a cron event to check for abandoned carts
add_action('wp', 'schedule_abandoned_cart_check_event');

// Hook to check abandoned carts
add_action('check_abandoned_carts', 'check_for_abandoned_carts');

// Activation hook to schedule cron
register_activation_hook(__FILE__, 'wc_abandoned_cart_webhook_activate');
register_deactivation_hook(__FILE__, 'wc_abandoned_cart_webhook_deactivate');

// Deactivation hook to clear scheduled events
function wc_abandoned_cart_webhook_deactivate() {
    wp_clear_scheduled_hook('check_abandoned_carts');
}

// Plugin activation function
function wc_abandoned_cart_webhook_activate() {
    if (!wp_next_scheduled('check_abandoned_carts')) {
        wp_schedule_event(time(), 'every_five_minutes', 'check_abandoned_carts');
    }
}

// Create admin menu for settings
function wc_abandoned_cart_webhook_settings_menu() {
    add_options_page(
        'Abandoned Cart Webhook Settings',
        'Abandoned Cart Webhook',
        'manage_options',
        'wc-abandoned-cart-webhook',
        'wc_abandoned_cart_webhook_settings_page'
    );
}

// Initialize plugin settings
function wc_abandoned_cart_webhook_settings_init() {
    register_setting('wc_abandoned_cart_webhook_group', 'wc_abandoned_cart_webhook_url');

    add_settings_section(
        'wc_abandoned_cart_webhook_section',
        'Webhook Settings',
        null,
        'wc-abandoned-cart-webhook'
    );

    add_settings_field(
        'wc_abandoned_cart_webhook_url_field',
        'Webhook URL',
        'wc_abandoned_cart_webhook_url_field_callback',
        'wc-abandoned-cart-webhook',
        'wc_abandoned_cart_webhook_section'
    );
}

// Callback for the webhook URL field
function wc_abandoned_cart_webhook_url_field_callback() {
    $webhook_url = get_option('wc_abandoned_cart_webhook_url', '');
    echo '<input type="text" id="wc_abandoned_cart_webhook_url" name="wc_abandoned_cart_webhook_url" value="' . esc_attr($webhook_url) . '" class="regular-text">';
}

// Display settings page
function wc_abandoned_cart_webhook_settings_page() {
    ?>
    <div class="wrap">
        <h1>Abandoned Cart Webhook Settings</h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('wc_abandoned_cart_webhook_group');
            do_settings_sections('wc-abandoned-cart-webhook');
            submit_button();
            ?>
        </form>
        <h2>Send Sample Webhook</h2>
        <form method="post" action="">
            <input type="hidden" name="send_sample_webhook" value="1">
            <?php submit_button('Send Sample Webhook'); ?>
        </form>
    </div>
    <?php

    // Handle the sample webhook send action
    if (isset($_POST['send_sample_webhook'])) {
        send_sample_webhook();
    }
}

// Function to capture cart data when items are added to the cart
function capture_cart_data($cart_item_key, $product_id, $quantity, $variation_id, $variation, $cart_item_data) {
    $cart = WC()->cart->get_cart();
    $session_cookie = WC()->session->get_session_cookie();
    if ($session_cookie && is_array($session_cookie)) {
        $session_id = $session_cookie[0];
    } else {
        $session_id = null;  // Handle the case when there's no session ID
    }

    $user_id = is_user_logged_in() ? get_current_user_id() : null;

    // Capture customer data
    $customer_email = WC()->customer->get_billing_email();
    $customer_phone = WC()->customer->get_billing_phone();
    $billing_address = WC()->customer->get_billing();
    $shipping_address = WC()->customer->get_shipping();

    // Store the cart in the transient for 1 hour
    set_transient("abandoned_cart_$session_id", [
        'user_id'          => $user_id,
        'email'            => $customer_email,
        'phone'            => $customer_phone,
        'cart'             => $cart,
        'billing_address'  => $billing_address,
        'shipping_address' => $shipping_address,
        'timestamp'        => time(),
    ], 3600);
}

// Function to remove abandoned cart transient after checkout
function remove_abandoned_cart_transient($order_id) {
    $session_id = WC()->session->get_session_cookie()[0];
    delete_transient("abandoned_cart_$session_id");
}

// Function to schedule abandoned cart checks
function schedule_abandoned_cart_check_event() {
    if (!wp_next_scheduled('check_abandoned_carts')) {
        wp_schedule_event(time(), 'every_five_minutes', 'check_abandoned_carts');
    }
}

// Add a new interval for every 5 minutes
add_filter('cron_schedules', 'add_five_minute_cron_schedule');

function add_five_minute_cron_schedule($schedules) {
    $schedules['every_five_minutes'] = array(
        'interval' => 300, // 5 minutes
        'display'  => __('Every 5 Minutes'),
    );
    return $schedules;
}

// Function to check for abandoned carts
function check_for_abandoned_carts() {
    global $wpdb;

    $query = $wpdb->get_results("SELECT option_name, option_value FROM {$wpdb->options} WHERE option_name LIKE '%_transient_abandoned_cart_%'");

    foreach ($query as $row) {
        $cart_data = maybe_unserialize($row->option_value);
        if (time() - $cart_data['timestamp'] > 3600) {  // If the cart is older than 1 hour, send the webhook
            $session_id=explode("_transient_abandoned_cart_", $row->option_name)[1] ?? "";
            if($session_id!="") send_abandoned_cart_webhook($cart_data);
            delete_transient(str_replace('_transient_', '', $row->option_name));
        }
    }
}

// Function to send the webhook
function send_abandoned_cart_webhook($cart_data) {
    $webhook_url = get_option('wc_abandoned_cart_webhook_url', '');

    if (!empty($webhook_url)) {
        $data = [
            'user_id'          => $cart_data['user_id'],
            'email'            => $cart_data['email'],
            'phone'            => $cart_data['phone'],
            'billing_address'  => $cart_data['billing_address'],
            'shipping_address' => $cart_data['shipping_address'],
            'cart_items'       => array_map(function($item) {
                $product = wc_get_product($item['product_id']); // Get the product object
                return [
                    'product_id'   => $item['product_id'],
                    'product_name' => $product ? $product->get_name() : '', // Get product name
                    'quantity'     => $item['quantity'],
                ];
            }, $cart_data['cart']),
        ];

        wp_remote_post($webhook_url, [
            'body' => wp_json_encode($data),
            'headers' => [
                'Content-Type' => 'application/json',
            ],
        ]);
    }
}

// Function to send a sample webhook
function send_sample_webhook() {
    $webhook_url = get_option('wc_abandoned_cart_webhook_url', '');
    if (!empty($webhook_url)) {
        $sample_data = [
            'user_id'          => 123,
            'email'            => 'sample@example.com',
            'phone'            => '+1234567890',
            'billing_address'  => [
                'first_name' => 'John',
                'last_name'  => 'Doe',
                'address_1'  => '123 Sample St',
                'address_2'  => 'Apt 4B',
                'city'       => 'Sample City',
                'state'      => 'CA',
                'postcode'   => '12345',
                'country'    => 'US',
                'phone'      => '+1234567890',
            ],
            'shipping_address' => [
                'first_name' => 'Jane',
                'last_name'  => 'Doe',
                'address_1'  => '456 Example St',
                'address_2'  => '',
                'city'       => 'Example City',
                'state'      => 'CA',
                'postcode'   => '67890',
                'country'    => 'US',
                'phone'      => '+1987654321',
            ],
            'cart_items' => [
                [
                    'product_id'   => 456,
                    'product_name' => 'Sample Product 1',
                    'quantity'     => 2,
                ],
                [
                    'product_id'   => 789,
                    'product_name' => 'Sample Product 2',
                    'quantity'     => 1,
                ]
            ],
        ];

        $response = wp_remote_post($webhook_url, [
            'body' => json_encode($sample_data),
            'headers' => [
                'Content-Type' => 'application/json',
            ],
        ]);

        if (is_wp_error($response)) {
            add_settings_error('wc_abandoned_cart_webhook', 'send_sample_webhook_error', 'Failed to send sample webhook.', 'error');
        } else {
            add_settings_error('wc_abandoned_cart_webhook', 'send_sample_webhook_success', 'Sample webhook sent successfully!', 'updated');
        }
    }
}