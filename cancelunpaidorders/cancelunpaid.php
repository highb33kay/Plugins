<?php
// Schedule the cron event if it's not already scheduled
if (!wp_next_scheduled('woocommerce_cancel_unpaid_orders')) {
	wp_schedule_event(time(), 'daily', 'woocommerce_cancel_unpaid_orders');
}

add_action('woocommerce_cancel_unpaid_orders', 'custom_wc_cancel_unpaid_orders');


function custom_wc_cancel_unpaid_orders()
{
	$held_duration = get_option('woocommerce_hold_stock_minutes');
	if ($held_duration < 1 || 'yes' !== get_option('woocommerce_manage_stock')) {
		set_flash_message('Stock management is disabled or hold duration is set to less than 1 minute.', 'error');
		return;
	}
	$data_store = WC_Data_Store::load('order');
	$unpaid_orders = $data_store->get_unpaid_orders(strtotime('-' . absint($held_duration) . ' MINUTES', current_time('timestamp')));

	if ($unpaid_orders) {
		foreach ($unpaid_orders as $unpaid_order) {
			$order = wc_get_order($unpaid_order);
			if (apply_filters('woocommerce_cancel_unpaid_custom_order', 'checkout' === $order->get_created_via(), $order)) {
				$order->update_status('cancelled', __('Unpaid order cancelled - custom time limit reached.', 'woocommerce'));
			}
		}
		set_flash_message('Unpaid orders cancelled successfully.', 'success');
	} else {
		set_flash_message('No unpaid orders to cancel.', 'info');
	}
}


// Function to manually trigger order cancellation for testing
add_action('init', function () {
	if (isset($_GET['test_cancel_orders']) && current_user_can('manage_options')) {
		custom_wc_cancel_unpaid_orders();
		set_flash_message('Unpaid orders cancelled successfully.', 'success');
		wp_die('Unpaid orders cancellation script executed.');
	}
});


// Function to add flash messages as admin notices
function set_flash_message($message, $type = 'success')
{
	$flash_messages = get_transient('flash_messages') ?: array();
	$flash_messages[] = array('message' => $message, 'type' => $type);
	set_transient('flash_messages', $flash_messages, 60); // 60 seconds expiration
}

// Function to display and clear flash messages
function display_flash_message()
{
	$flash_messages = get_transient('flash_messages') ?: array();
	if (!empty($flash_messages)) {
		foreach ($flash_messages as $flash_message) {
			echo '<div class="notice notice-' . esc_attr($flash_message['type']) . ' is-dismissible">
		<p>' . esc_html($flash_message['message']) . '</p>
	</div>';
		}
		delete_transient('flash_messages'); // Clear the messages after displaying
	}
}

// Hook to display admin notices
add_action('admin_notices', 'display_flash_message');
