<?php

/**
 * Plugin Name: Simple Breadcrumbs
 * Description: A simple WordPress breadcrumb plugin that works with shortcode.
 * Version: 1.0.0
 * Author: HighB33kay
 * Author URI: https://github.com/highB33kay
 */

require_once ABSPATH . 'wp-includes/post.php';


add_action('init', function () {
    // Register the breadcrumb shortcode.
    add_shortcode('breadcrumb', function () {
        // Get the current post object.
        $post = get_queried_object();

        // Get the home page title.
        $home_title = get_option('blogname');

        // Get the breadcrumb trail.
        $breadcrumb = get_breadcrumb();

        // Build the breadcrumb HTML.
        $breadcrumb_html = '<ul class="breadcrumb">';

        // Check if the current page is the home page.
        if (is_home()) {
            $breadcrumb_html .= '<li class="breadcrumb-home">' . $home_title . '</li>';
        } else {
            // Get the current post's categories.
            // Get the current post's categories.
            $categories = get_the_category($post);

            $breadcrumb_html .= '<li class="breadcrumb-home">Home</li>';

            // Loop through the categories and add them to the breadcrumb.
            foreach ($categories as $category) {
                $breadcrumb_html .= '<li><a href="' . esc_url(get_category_link($category->term_id)) . '">' . esc_html($category->name) . '</a></li>';
            }

            // Add the current post title to the breadcrumb.
            $breadcrumb_html .= '<li class="breadcrumb-current">' . esc_html($post->post_title) . '</li>';
        }

        $breadcrumb_html .= '</ul>';

        // Return the breadcrumb HTML.
        return $breadcrumb_html;
    });
});

// enqueue style
add_action('wp_enqueue_scripts', function () {
    wp_enqueue_style('breadcrumb', plugin_dir_url(__FILE__) . 'style.css');
});

/**
 * Get the breadcrumb trail.
 *
 * @return array
 */
function get_breadcrumb()
{
    // Get the current post object.
    $post = get_queried_object();

    // Create an empty breadcrumb array.
    $breadcrumb = array();

    // Add the home page to the breadcrumb.
    $breadcrumb[] = array(
        'title' => $home_title,
        'url'   => home_url('/')
    );

    // Add the current post to the breadcrumb.
    if ($post) {
        $breadcrumb[] = array(
            'title' => $post->post_title,
            'url'   => get_permalink($post->ID)
        );
    }

    return $breadcrumb;
}

// Change the breadcrumb separator.

add_filter('breadcrumb_separator', function ($separator) {
    return '/';
});
