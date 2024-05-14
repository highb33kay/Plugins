<?php

/**
 * Function to remove SKU from products in batches with safety measures.
 */
function remove_sku_from_all_products_in_batches($dry_run = true)
{
	$batch_size = 100; // Define the number of products to process per batch
	$paged = 1; // Start with the first page
	$log = []; // Log for keeping track of processed products
	$total_products_processed = 0; // Counter for processed products

	// Add a confirmation step if not a dry run
	if (!$dry_run) {
		if (!confirm_removal()) {
			echo 'Operation canceled by the user.';
			return;
		}
	}

	// Loop through products in batches
	while (true) {
		$args = array(
			'post_type' => 'product',
			'posts_per_page' => $batch_size,
			'paged' => $paged,
			'post_status' => 'publish',
			'fields' => 'ids', // Only get post IDs to save memory
		);

		$product_ids = get_posts($args);

		if (empty($product_ids)) {
			// No more products to process
			break;
		}

		foreach ($product_ids as $product_id) {
			$product = wc_get_product($product_id);

			if ($product && is_a($product, 'WC_Product')) {
				$log[] = array(
					'ID' => $product_id,
					'SKU' => $product->get_sku(),
				);

				if (!$dry_run) {
					// Remove the SKU
					$product->set_sku('');
					// Save the product
					$product->save();
				}

				$total_products_processed++;
			}
		}

		// Move to the next page
		$paged++;
	}

	// Store the log in a transient for displaying in the admin notice
	set_transient('sku_removal_log', $log, 60 * 10); // Store for 10 minutes
	set_transient('sku_removal_total', $total_products_processed, 60 * 10);

	// Output results
	if ($dry_run) {
		echo 'Dry run complete. The following products would have their SKUs removed:';
	} else {
		echo 'SKUs removed from the following products:';
	}
	echo '
<pre>' . print_r($log, true) . '</pre>';
	echo 'Total products processed: ' . $total_products_processed;
}

/**
 * Function to confirm the removal operation.
 */
function confirm_removal()
{
	// Replace this with actual confirmation logic, such as a prompt or form submission
	// For demonstration purposes, we assume the user confirmed the operation
	return true;
}

/**
 * Display an admin notice with the log of SKU removals.
 */
function display_sku_removal_notice()
{
	$log = get_transient('sku_removal_log');
	$total_products_processed = get_transient('sku_removal_total');

	if ($log !== false && $total_products_processed !== false) {
		delete_transient('sku_removal_log');
		delete_transient('sku_removal_total');

		echo '<div class="notice notice-success is-dismissible">';
		echo '<p><strong>SKU Removal Process Completed</strong></p>';
		echo '<p>Total products processed: ' . esc_html($total_products_processed) . '</p>';
		echo '
	<pre>' . esc_html(print_r($log, true)) . '</pre>';
		echo '
</div>';
	}
}

// Hook the function to an action or call it directly for testing
add_action('admin_init', function () {
	if (isset($_GET['remove_skus']) && $_GET['remove_skus'] == 'true') {
		remove_sku_from_all_products_in_batches(false); // Change to false to actually perform the removal
	} else {
		remove_sku_from_all_products_in_batches(true); // Dry run by default
	}
});

// Add admin notice for displaying log
add_action('admin_notices', 'display_sku_removal_notice');
