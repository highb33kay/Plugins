<?php


/**
 * Plugin Name: Product Upvote and Downvote
 * Description: This plugin adds an up vote and down vote button to product archive pages and product pages.
 * Author: Highb33kay
 * Author URI: www.github.com/highb33kay
 */

function product_voting_register_post_type()
{
    $labels = array(
        'name' => __('Products'),
        'singular_name' => __('Product')
    );

    $args = array(
        'labels' => $labels,
        'public' => true,
        'has_archive' => true,
        'supports' => array('title', 'editor', 'thumbnail')
    );

    register_post_type('product', $args);
}

add_action('init', 'product_voting_register_post_type');


function product_voting_enqueue_scripts()
{
    if (is_singular('product') || is_post_type_archive('product')) {
        wp_enqueue_script('product-voting-script', plugin_dir_url(__FILE__) . 'product-voting-script.js', array('jquery'), false, true);

        wp_localize_script('product-voting-script', 'productVoting', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('product-voting-nonce'),
        ));
    }
}



function product_voting_process_request()
{
    $postId = $_POST['post_id'];

    switch ($_POST['action']) {
        case 'product_upvote':
            $upvotes = get_post_meta($postId, 'product_upvotes', true);
            update_post_meta($postId, 'product_upvotes', $upvotes + 1);
            break;

        case 'product_downvote':
            $downvotes = get_post_meta($postId, 'product_downvotes', true);
            update_post_meta($postId, 'product_downvotes', $downvotes + 1);
            break;
    }

    die();
}

add_action('wp_ajax_product_upvote', 'product_voting_process_request');
add_action('wp_ajax_product_downvote', 'product_voting_process_request');
