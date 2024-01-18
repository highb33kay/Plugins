<?php
/*
Plugin Name: Hide out of stock products
Description: A custom plugin to hide out of stock products on the product listing page.
Version: 1.0
Author: HighB33Kay
Author URI: https://highb33kay.me
License: GPL2
License URI: https://www.gnu.org/licenses/gpl-2.0.html
*/

/**
 * Hide out of stock products and exclude them from search results and the shop page 
 * Create a new category called "out-of-stock" and assign it to all out of stock products
 * Create a shortcode to display out of stock products
 * Restore the products and remove the actions, shortcodes, and filters when the plugin is deactivated
 */

/**
 * Register Deactivation Hook
 * 
 * @return void
 */
register_deactivation_hook(__FILE__, 'restore_products');

/**
 * Add Actions
 * 
 * @return void
 * 
 * @author 
 */
add_action('plugins_loaded', 'hide_out_of_stock_products');

/**
 * Hide out of stock products
 * 
 * @return void
 * 
 * @author Ibukun Alesinloye
 */
function hide_out_of_stock_products()
{
	add_action('woocommerce_product_set_stock_status', 'remove_from_outofstock_category', 10, 3);
	add_shortcode('out_of_stock_products', 'display_out_of_stock_products');
	add_action('init', 'create_outofstock_category');
	add_action('pre_get_posts', 'exclude_outofstock_from_search_results');
	add_filter('woocommerce_product_query_meta_query', 'shop_only_instock_products', 10, 2);
}

/**
 * Exclude out of stock products from the shop page
 * 
 * @param array $meta_query The meta query
 * @param object $query The WP_Query object
 * 
 * @return array The meta query
 * 
 * @author Ibukun Alesinloye
 */

function shop_only_instock_products($meta_query, $query)
{
	// Check if the current page is the out-of-stock category page
	$is_outofstock_category_page = is_product_category('out-of-stock');

	// If it's the out-of-stock category page, include out-of-stock products in the query
	if ($is_outofstock_category_page) {
		return $meta_query;
	}

	// Exclude out-of-stock products for other pages
	$meta_query[] = array(
		'key'     => '_stock_status',
		'value'   => 'outofstock',
		'compare' => '!='
	);

	return $meta_query;
}



/**
 * Exclude out of stock products from search results
 * 
 * @param object $query The WP_Query object
 * 
 * @return void
 * 
 * @author Ibukun Alesinloye
 */
function exclude_outofstock_from_search_results($query)
{
	// Check if the query is a search query
	if ($query->is_search() && !is_admin()) {
		// Get all out of stock product IDs
		$outofstock_ids = wc_get_products(array(
			'status' => 'publish',
			'limit' => -1,
			'stock_status' => 'outofstock',
			'return' => 'ids',
		));
		// Exclude out of stock products from search results
		$query->set('post__not_in', $outofstock_ids);
	}
}


/**
 * Create the "out-of-stock" category and assign it to all out of stock products
 * 
 * @return void
 * 
 * @author Ibukun Alesinloye
 */
function create_outofstock_category()
{
	// Check if the category exists
	$term = term_exists('out-of-stock', 'product_cat');
	// If not, create it
	if ($term === 0 || $term === null) {
		wp_insert_term(
			'Out of Stock',
			'product_cat',
			array(
				'description' => 'Products that are out of stock',
				'slug' => 'out-of-stock',
			)
		);
	}
	// Get the term ID
	$term_id = get_term_by('slug', 'out-of-stock', 'product_cat')->term_id;
	// Get all out of stock product IDs
	$outofstock_ids = wc_get_products(array(
		'status' => 'publish',
		'limit' => -1,
		'stock_status' => 'outofstock',
		'return' => 'ids',
	));
	// Loop through the products and assign the category
	foreach ($outofstock_ids as $product_id) {
		wp_set_object_terms($product_id, $term_id, 'product_cat', true);
	}
}

/**
 * Display out of stock products
 * 
 * @return string The shortcode output
 * 
 * @author Ibukun Alesinloye
 */
function display_out_of_stock_products()
{
	// Get all out of stock product IDs
	$outofstock_ids = wc_get_products(array(
		'status' => 'publish',
		'limit' => -1,
		'stock_status' => 'outofstock',
		'return' => 'ids',
	));
	// Convert the IDs to a comma-separated string
	$outofstock_ids = implode(',', $outofstock_ids);

	// Use the existing [products] shortcode with the "ids" parameter
	return do_shortcode("[products ids='$outofstock_ids']");
}

/**
 * Remove out of stock products from the "out-of-stock" category
 * 
 * @param int $product_id The ID of the product
 * @param string $stock_status The stock status of the product
 * @param object $product The product object
 * 
 * @return void
 * 
 * @author Ibukun Alesinloye
 */
function remove_from_outofstock_category($product_id, $stock_status, $product)
{
	// Check if the product is back in stock
	if ($stock_status == 'instock') {
		// Get the term ID of the "out-of-stock" category
		$term_id = get_term_by('slug', 'out-of-stock', 'product_cat')->term_id;

		// Remove the product from the category
		wp_remove_object_terms($product_id, $term_id, 'product_cat');
	}
}

/**
 * Restore the products and remove the actions, shortcodes, and filters
 * 
 * This function is executed when the plugin is deactivated
 * 
 * @author Ibukun Alesinloye
 */
function restore_products()
{
	// Remove the "out-of-stock" category
	$term_id = get_term_by('slug', 'out-of-stock', 'product_cat')->term_id;
	wp_delete_term($term_id, 'product_cat');

	// Get all out of stock product IDs
	$outofstock_ids = wc_get_products(array(
		'status' => 'publish',
		'limit' => -1,
		'stock_status' => 'outofstock',
		'return' => 'ids',
	));

	// Loop through the products and remove them from the "out-of-stock" category
	foreach ($outofstock_ids as $product_id) {
		wp_remove_object_terms($product_id, $term_id, 'product_cat');
	}

	// Remove actions, shortcodes, and filters
	remove_action('woocommerce_product_set_stock_status', 'remove_from_outofstock_category', 10);
	remove_shortcode('out_of_stock_products', 'display_out_of_stock_products');
	remove_action('init', 'create_outofstock_category');
	remove_action('pre_get_posts', 'exclude_outofstock_from_search_results');
	remove_filter('woocommerce_product_query_meta_query', 'shop_only_instock_products', 10);
}
