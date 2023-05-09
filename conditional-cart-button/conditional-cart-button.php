<?php
/*
Plugin Name: Conditional Cart Button
Description: A plugin that displays a conditional cart button in WooCommerce.
Version: 1.0.0
Author: HighB33kay
Author URI: https://highb33kay.tech/
*/

// Add the shortcode function
function cart_button_shortcode()
{
    // Check if WooCommerce is active
    if (!class_exists('WooCommerce')) {
        return '<p>WooCommerce is not installed or activated.</p>';
    }

    if (function_exists('WC') && WC() instanceof WC_Cart) {
        $item_count = WC()->cart->get_cart_contents_count();
    } else {
        $item_count = 0;
    }


    // Check if the cart is empty
    if ($item_count == 0) {
        // If the cart is empty, display the "Cart Empty" button
        $button_text = 'Cart Empty';
    } else {
        // If the cart is not empty, display the "View Cart" button with item count
        $button_text = sprintf(_n('%d item', '%d items', $item_count, 'woocommerce'), $item_count);
    }

    // Build the button HTML
    $button_html = '<div class="cart-con"><a class="cart-button" href="' . esc_url(wc_get_cart_url()) . '"><img class="cart-logo" src="' . plugins_url('assets/img/cart.png', __FILE__) . '">' . $button_text . '</a></div>';

    // Return the button HTML
    return $button_html;
}


add_shortcode('cart_button', 'cart_button_shortcode');

// Enqueue the CSS styles
function cart_button_enqueue_styles()
{
    wp_enqueue_style('cart-button-styles', plugins_url('assets/css/cart-button.css', __FILE__), array(), '1.0.0', 'all');
}
add_action('wp_enqueue_scripts', 'cart_button_enqueue_styles');


function my_plugin_error_handler($errno, $errstr, $errfile, $errline)
{
    $message = "Error: [$errno] $errstr - $errfile:$errline";
    error_log($message);
}

// Set custom error handler
set_error_handler("my_plugin_error_handler");


register_activation_hook(__FILE__, 'cart_button_activate');
function cart_button_activate()
{
    // Check if WooCommerce is active
    if (!class_exists('WooCommerce')) {
        $error_message = 'Conditional Cart Button plugin requires WooCommerce to be activated.';
        error_log($error_message);
        deactivate_plugins(plugin_basename(__FILE__));
    }
}
