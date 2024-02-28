<?php

/**
 * Plugin Name: Automatically Add Back in Stock Products to Category
 * Description: Automatically adds products to a "Back in Stock" category when their stock status changes from out of stock to in stock, given the product was created at least 30 days ago.
 * Version: 1.1
 * Author: Boma Dave
 */
// Hook into WooCommerce product stock change
add_action('woocommerce_product_set_stock', 'bave_check_and_add_back_in_stock_category');
function bave_check_and_add_back_in_stock_category($product)
{
	$product_id = $product->get_id();
	$product_created_at = strtotime($product->get_date_created());
	$thirty_days_ago = strtotime('-30 days');
	// Check if product was created at least 30 days ago
	if ($product_created_at > $thirty_days_ago) {
		return;
	}
	// Ensure we only act on products transitioning from 'out of stock' to 'in stock'.
	if (!$product->is_in_stock() || $product->get_stock_quantity() <= 0) {
		return;
	}
	// The slug of the "Back in Stock" category - replace 'back-in-stock' with your actual category slug.
	$back_in_stock_term = get_term_by('slug', 'back-in-stock', 'product_cat');
	if ($back_in_stock_term) {
		// Add the product to the "Back in Stock" category.
		wp_set_object_terms($product_id, $back_in_stock_term->term_id, 'product_cat', true);

		// Increment the counter for this product
		$last_count = get_option('last_back_in_stock_count', 0); // Get the last count
		$counter = $last_count + 1; // Increment the count for the current product
		update_post_meta($product_id, 'back_in_stock_counter', $counter); // Update the counter for the current product
		update_option('last_back_in_stock_count', $counter); // Update the last count for the next product
	}
}


// Add "Back in Stock Counter" setting to product sorting menu
function rey_addBackInStockCounterProductOrderSetting($sortby)
{
	$sortby['back_in_stock'] = 'Back in Stock';
	return $sortby;
}
add_filter('woocommerce_default_catalog_orderby_options', 'rey_addBackInStockCounterProductOrderSetting');
add_filter('woocommerce_catalog_orderby', 'rey_addBackInStockCounterProductOrderSetting');

// Sort products by back_in_stock when setting is used
// Sort products by back_in_stock_counter when setting is used
function rey_sortProductsByBackInStockCounter($args)
{
	$orderbySetting = isset($_GET['orderby']) ? wc_clean($_GET['orderby']) : apply_filters('woocommerce_default_catalog_orderby', get_option('woocommerce_default_catalog_orderby'));
	if ('back_in_stock' == $orderbySetting) {
		$args['orderby'] = 'meta_value_num';
		$args['order'] = 'DESC';
		$args['meta_key'] = 'back_in_stock_counter';
	}
	return $args;
}
add_filter('woocommerce_get_catalog_ordering_args', 'rey_sortProductsByBackInStockCounter');

// Function to check if the current page is the back-in-stock page or the back-in-stock category page
function is_back_in_stock_page()
{
	// Get the current page's URL
	$current_url = $_SERVER['REQUEST_URI'];

	// Check if the current page is the back-in-stock page by its slug
	if (is_page('back-in-stock-2') || strpos($current_url, '/product-category/back-in-stock/') !== false) {
		return true;
	}
	return false;
}


// Filter to modify the catalog ordering options
function rey_modifyCatalogOrderbyOptions($sortby)
{
	// Check if the current page is the back-in-stock page
	if (is_back_in_stock_page()) {
		// If it is the back-in-stock page, only include the back_in_stock_counter option
		$sortby = array(
			'back_in_stock' => 'Back in Stock'
		);
	} else {
		// If it is not the back-in-stock page, include other options
		$sortby = array(
			'menu_order' => 'Default sorting',
			'popularity' => 'Popularity',
			'rating'     => 'Average rating',
			'date'       => 'Newness',
			'price'      => 'Price: low to high',
			'price-desc' => 'Price: high to low',
		);
	}
	return $sortby;
}
add_filter('woocommerce_catalog_orderby', 'rey_modifyCatalogOrderbyOptions');

// Function to modify the catalog ordering args
function rey_modifyCatalogOrderbyArgs($args)
{
	// Check if the current page is the back-in-stock page
	if (is_back_in_stock_page()) {
		// If it is the back-in-stock page, set the sorting args to back_in_stock_counter
		$args['orderby'] = 'meta_value_num';
		$args['order'] = 'DESC';
		$args['meta_key'] = 'back_in_stock_counter';
	}
	return $args;
}
add_filter('woocommerce_get_catalog_ordering_args', 'rey_modifyCatalogOrderbyArgs');
