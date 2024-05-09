<?php

// Hook into WooCommerce order status changes.
add_action('woocommerce_order_status_changed', 'log_order_status_change', 10, 4);

/**
 * Log order status changes to the order notes including the user who made the change.
 *
 * @param int $order_id The order ID.
 * @param string $old_status Old status.
 * @param string $new_status New status.
 * @param object $order Order object.
 */
function log_order_status_change($order_id, $old_status, $new_status, $order)
{
	// Check if the order ID is valid.
	if (!$order_id) {
		return;
	}

	// Get the current user.
	$user = wp_get_current_user();
	$user_identity = $user->exists() ? $user->display_name : 'Guest';

	// Construct the note content.
	$note = sprintf('Order status changed from %s to %s by %s.', $old_status, $new_status, $user_identity);

	// Optionally, add more action details to the note.
	// For example, if you have specific actions to track, you can add them here.

	// Add the note to the order, marked as private.
	$order->add_order_note($note, true);
}

add_action('woocommerce_email', 'rwf_emails_order_notes', 10);
function rwf_emails_order_notes($email_class)
{
	remove_action('woocommerce_new_customer_note_notification', [$email_class->emails['WC_Email_Customer_Note'], 'trigger']);
}
