<?php

use WPO\WC\PDF_Invoices\Documents\Bulk_Document;

if (!class_exists('CustomWCPDFBulk')) :

	class CustomWCPDFBulk extends Bulk_Document
	{

		private static $instance;

		public static function instance()
		{
			if (is_null(self::$instance)) {
				self::$instance = new self();
			}
			return self::$instance;
		}

		public function __construct($document_type = null, $order_ids = array())
		{
			parent::__construct($document_type, $order_ids);


			//add_action('wp_enqueue_scripts', 'dequeue_original_enqueue_custom_wpo_wcpdf_script');

			// add output_pdf action
			add_action('wpo_wcpdf_output_pdf', [$this, 'output_pdf'], 10, 1);


			if (defined('WC_VERSION') && version_compare(WC_VERSION, '3.3', '>=')) {
				add_filter('bulk_actions-edit-shop_order', array($this, 'bulk_actions'), 20);
				add_action('admin_footer', array($this, 'get_nonce'));
				add_filter('bulk_actions-woocommerce_page_wc-orders', array($this, 'bulk_actions'), 20); // WC 7.1+
			} else {
				add_action('admin_footer', array($this, 'bulk_actions_js'));
			}

			add_action('handle_bulk_actions-edit-shop_order', [$this, 'handle_bulk_action'], 10, 3);
			add_action('handle_bulk_actions-woocommerce_page_wc-orders', [$this, 'handle_bulk_action'], 10, 3);

			// Hook into admin_notices to display flash messages
			add_action('admin_notices', [$this, 'display_flash_message']);
		}

		/**
		 * Add actions to menu, WP3.5+
		 */
		public function bulk_actions($actions)
		{
			foreach (wcpdf_get_bulk_actions() as $action => $title) {
				$actions[$action] = $title;
			}
			unset($actions["invoice"]);
			unset($actions["packing-slip"]);
			$actions['pdf-invoice'] = "PDF Invoice";
			$actions['pdf-slip'] = "PDF Packing Slip";
			return $actions;
		}

		public function get_nonce()
		{
			$current_url = $_SERVER['REQUEST_URI'];

			set_transient('currentURL', $current_url, HOUR_IN_SECONDS);
			// Check if we are on the WooCommerce orders page
			$screen = get_current_screen();
			if ($screen && $screen->post_type === 'shop_order' && !isset($_GET['action'])) {
				//Embed a JavaScript function within the PHP file
?>
				<script type="text/javascript">
					function append_nonce() {
						//Get all button for individual pdf exports
						let noncebtn = document.querySelectorAll('.button.tips');

						//Get the access key form the href of the first single pdf export button 
						let access_key = noncebtn[0].getAttribute('href').split('&').pop().split('=').pop();

						let form = document.querySelector('#wc-orders-filter');
						let access_key_input = document.createElement("input");
						access_key_input.type = "hidden";
						access_key_input.name = "access_key";
						access_key_input.value = access_key;

						//Append the hidden input to the form
						form.appendChild(access_key_input);

						//console.log(access_key);

					}

					//Trigger the function once pages is fully loaded
					jQuery(document).ready(function() {
						append_nonce();
					});
				</script>


			<?php
			}
		}

		/**
		 * Add actions to menu, legacy method
		 */
		public function bulk_actions_js()
		{

			if ($this->is_order_page()) {
			?>
				<script type="text/javascript">
					jQuery(document).ready(function() {
						<?php foreach (wcpdf_get_bulk_actions() as $action => $title) { ?>
							jQuery('<option>').val('<?php echo esc_attr($action); ?>').html('<?php echo esc_attr($title); ?>').appendTo("select[name='action'], select[name='action2']");
						<?php }	?>
					});
				</script>
<?php
			}
		}



		public function handle_bulk_action($redirect_to, $doaction, $post_ids)
		{
			$current_url = get_transient('currentURL');
			if ($doaction === 'pdf-invoice' || $doaction === 'pdf-slip') {
				//Perform your custom action here
				$order_ids = $_REQUEST['id'] ?? $_REQUEST['post'];

				//check and retirn the ids that havent been downloaded more
				$order_ids =  $this->return_order_ids($order_ids, $doaction);

				$document_type = $doaction === 'pdf-invoice' ? 'invoice' : 'packing-slip';

				$valid = $order_ids['valid'];

				$invalid = $order_ids['invalid'];

				$suffix = count($order_ids) > 1 ? 's' : '';

				$validmessage = count($valid) . " of " . count($order_ids) . " PDF " . ucfirst($document_type) . $suffix . " exported";
				$invalidmessage = '';

				if (count($invalid) > 0) {
					foreach ($invalid as $value) {
						$invalidmessage .= "Order {$value['id']} with document type " . ucfirst($value['document_type']) . " has already been downloaded {$value['count']} times.<br>";
					}
				}

				$wp_nonce = wp_create_nonce('generate_wpo_wcpdf_extension') ?? $_REQUEST['access_key']; //='2d64af9e43';

				//$redirect_to = add_query_arg('custom_action_result', count($post_ids), $redirect_to);

				// Get the order IDs
				$post_ids = isset($valid) ? array_map('intval', $valid) : array();


				// Convert array of order IDs to a string separated by 'x'
				$order_ids_string = implode('x', $post_ids);


				//Build the URL to generate the PDF for each order
				$pdf_generation_url = admin_url("admin-ajax.php?action=generate_wpo_wcpdf_extension&document_type={$document_type}&bulk&_wpnonce={$wp_nonce}&order_ids={$order_ids_string}");

				if (count($invalid) > 0) {
					if (count($valid) > 0) {
						$this->set_flash_message($validmessage, 'success');
					}
					$this->set_flash_message($invalidmessage, 'error');
				}

				if (count($valid) === 0) {
					echo "<script>window.open('{$current_url}', '_self');</script>";
				}
				echo "<script>window.open('{$current_url}', '_self');window.open('$pdf_generation_url', '_blank');</script>";
				exit();
			}
		}

		public function return_order_ids($order_ids, $document_type)
		{
			// Check if order_ids and pdfType are set in the request
			if (!empty($order_ids) && isset($document_type)) {
				// Get the order IDs and pdfType from the request
				$order_ids_param = $order_ids; //sanitize_text_field($['order_ids']);
				$order_ids = $order_ids_param; //explode(',', $order_ids_param);
				$pdfType = explode('-', sanitize_text_field($document_type));

				$updated_order_ids = array(); // To store order IDs with less than 3 downloads

				$invalid_order_ids = array(); // To store order IDs with more than 3 downloads


				foreach ($order_ids as $order_id) {
					$current_pdfType = '';

					//Initialize the variable for current document type
					switch (strtolower(end($pdfType))) {
						case 'invoice':
							$current_pdfType = 'download_invoice_' . $order_id;
							break;

						default:
							$current_pdfType = 'download_slip_' . $order_id;
							break;
					}

					$process_order = $this->process_order($order_id, $current_pdfType, $updated_order_ids);

					if (!empty($process_order['valid'])) {
						$updated_order_ids[] = $process_order['valid'];
					} else {
						$invalid_order_ids[] = $process_order['invalid'];
					}
				}

				//Return an array of the order IDs that are both valid and invalid
				return array('valid' => $updated_order_ids, 'invalid' => $invalid_order_ids);
			}
		}

		// Function to set a flash message
		public function set_flash_message($message, $type = 'success')
		{
			$flash_messages = get_transient('flash_messages') ?: array();

			// Add the new message to the array
			$flash_messages[] = array('message' => $message, 'type' => $type);

			// Store the updated array
			set_transient('flash_messages', $flash_messages, 60); // Expires in 60 seconds

		}

		// Function to display and clear flash messages(notices)
		public function display_flash_message()
		{

			// Get existing flash messages or initialize an empty array
			$flash_messages = get_transient('flash_messages') ?: array();

			foreach ($flash_messages as $flash_message) {

				// Display the message
				echo '<div class="notice notice-' . esc_attr($flash_message['type']) . ' is-dismissible"><p>' . ($flash_message['message']) . '</p></div>';
			}

			//Clear the transient to avoid displaying the message again
			delete_transient('flash_message');
		}



		public function process_order($order_id, $pdfType, $updated_order_ids)
		{

			// Implement your logic to check download limits and track downloads here
			$download_limit = 3; // Set your desired download limit

			$current_count = get_post_meta($order_id, $pdfType, true);

			$update_order_ids = [];
			$invalid_order_ids = [];

			$userRoles = $this->my_get_current_user_roles();

			$can_multi_download = $this->can_multi_download();

			if ($can_multi_download) {
				// Track downloads for the user
				$download_count = empty($current_count) ? 1 : $current_count + 1;
				$updated_order_ids = $order_id;
				update_post_meta($order_id, $pdfType, $download_count);
				return ['valid' => $updated_order_ids];
			} else {

				if ($current_count >= $download_limit) {
					//echo "Order $order_id with pdfType $pdfType has already been downloaded $current_count times.";
					$invalid_order_ids['id'] = $order_id;
					$invalid_order_ids['count'] = $current_count;
					$type = explode('_', $pdfType);
					$invalid_order_ids['document_type'] = $pdfType[1] === "invoice" ? "invoice" : "packing-slip";

					return ['invalid' => $invalid_order_ids];
				} else {
					// Track downloads for the user
					$download_count = empty($current_count) ? 1 : $current_count + 1;

					// Store order_id with less than 3 downloads in the array
					if ($current_count <= $download_limit) {
						$updated_order_ids = $order_id;
						update_post_meta($order_id, $pdfType, $download_count);
					}
				}

				return ['valid' => $updated_order_ids];
			}
		}

		/**
		 * Return  if user can multi download 
		 */
		public function can_multi_download()
		{
			$userRoles = $this->my_get_current_user_roles();

			$rolesArray = ['administrator'];

			foreach ($rolesArray as $roleArray) {
				if (!empty(array_intersect($userRoles, $rolesArray))) {
					return true;
					break; // Stop checking once a match is found
				}
			}

			return false;
		}

		/**
		 * get current user roles
		 */
		private function my_get_current_user_roles()
		{
			if (is_user_logged_in()) {
				$user = wp_get_current_user();
				$roles = (array)$user->roles;
				return array_values($roles);
			} else {
				var_dump(array());
				return array();
			}
		}
	}

	CustomWCPDFBulk::instance();

endif;
