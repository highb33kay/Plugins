<?php
// class-custom-admin.php
//error_reporting(E_ALL);
//ini_set('display_errors', 1);

if (!class_exists('Custom_Admin')) :
	class Custom_Admin extends \WPO\WC\PDF_Invoices\Admin
	{

		private static $instance;

		public static function instance()
		{
			if (is_null(self::$instance)) {
				self::$instance = new self();
			}
			return self::$instance;
		}

		public function __construct()
		{
			parent::__construct(); // Call the parent constructor

			// Your custom code for the Admin class goes here
			// add_action('woocommerce_admin_order_actions_end', array($this, 'addExtraScriptForPackingSlipFunc'));

			// add a custom action button to all the 

			/* Start Custom Script */
			add_action('admin_head', [$this, 'addExtraScriptForPackingSlipFunc']);

			add_action('do_meta_box', [$this, 'pdf_actions_meta_box']);

			add_action('pdf_actions_meta_box', [$this, 'pdf_actions_meta_box']);

			

		//add_action('woocommerce_admin_order_actions_end', [$this, 'addExtraScriptForPackingSlipFunc']);



			add_action('wp_ajax_handle_download_action', [$this, 'handle_download']); // For logged-in users
			add_action('wp_ajax_nopriv_handle_download_action', [$this, 'handle_download']); // For non-logged-in users

			add_action('wp_head', [$this, 'my_get_current_user_roles']);

			/* End Custom Script */
		}

		/* Start Custom Script */
		public function addExtraScriptForPackingSlipFunc()
		{
			if (
				isset($_GET['page']) &&
				$_GET['page'] === "shop_orders"
				&&
				current_user_can('admin') && intval($_GET['id'])
			) {
				echo '
				<script type="text/javascript">
				jQuery(document).ready(function(){
					jQuery("body").append("<input type=\'hidden\' id=\'currentUserRole\' value=\'warehouseprinters\' />");
					});
					</script>
					';
				}
			}


			/* End Custom Script */

		/**
		 * Retrieve user names who downloaded a particular order
		 */
		public function get_users_who_downloaded_order($orderId)
		{
			$download_count = get_post_meta($orderId, "download_count_$orderId", true);

			// Check if any downloads have occurred
			if ($download_count > 0) {
				$user_names = array();

				// Loop through download count and retrieve user names
				for ($i = 1; $i <= $download_count; $i++) {
					$user_id = get_post_meta($orderId, "download_user_$orderId", true);

					// Check if user ID exists and is not already in the list
					if ($user_id) {
						$user_data = get_userdata($user_id);

						// Check if user data is valid
						if ($user_data && !in_array($user_data->user_login, $user_names)) {
							$user_names[] = $user_data->user_login;
						}
					}
				}

				return $user_names;
			} else {
				// No downloads for the order
				return array();
			}
		}

		/**
		 * get current user roles
		 */
		public function my_get_current_user_roles()
		{

			if (is_user_logged_in()) {

				$user = wp_get_current_user();

				$roles = (array) $user->roles;

				//return $roles; // This will returns an array
				return array_values($roles);
			} else {

				return array();
			}
		}


		/**
		 * Track orders downloads
		 */
		public function track_order_downloads($userId, $pdfType, $orderId)
		{
			$download_count = get_post_meta($orderId, $pdfType, true);
			$download_count = empty($download_count) ? 1 : $download_count + 1;
			update_post_meta($orderId, $pdfType, $download_count);
			update_post_meta($orderId, "download_user_$orderId", $userId);
			$response = array('message' => 'successful', 'id' => $pdfType, 'download_count' => $download_count);
			wp_send_json($response);
		}

		/**
		 * Handle download
		 */
		public function handle_download($userId = null, $pdfType = null, $orderId = null)
		{
			$userId = isset($_POST['userId']) ? $_POST['userId'] : $userId;
			$pdfType = isset($_POST['pdfType']) ? $_POST['pdfType'] : $pdfType;
			$orderId = isset($_POST['orderId']) ? $_POST['orderId'] : $orderId;


			$download_limit = 3; // Set your desired download limit
			$current_count = get_post_meta($orderId, $pdfType, true);
			$can_multi_download = $this->can_multi_download();

			if ($can_multi_download) {
				echo $this->track_order_downloads($userId, $pdfType, $orderId); // Track downloads for the user
				return true; // Return true to indicate successful download handling
				wp_die();
			}

			if ($current_count > $download_limit) {
				return false;
			} else {
				echo $this->track_order_downloads($userId, $pdfType, $orderId); // Track downloads for the user
				return true; // Return true to indicate successful download handling
				wp_die();
			}
		}

		/**
		 * Return  if user can multi download 
		 */
		public function can_multi_download(){
			$userRoles = $this->my_get_current_user_roles();

			$rolesArray = ['administrator', 'warehouseprinters'];

			foreach ($rolesArray as $roleArray) {
				if (!empty(array_intersect($userRoles, $rolesArray))) {
					return true;
					break; // Stop checking once a match is found
				}
			}

			return false;
		}



		/**
		 * Create the PDF meta box content on the single order page
		 */
		public function pdf_actions_meta_box($post_or_order_object)
		{
			$order = ($post_or_order_object instanceof \WP_Post) ? wc_get_order($post_or_order_object->ID) : $post_or_order_object;

			$orderId = $order->get_id();

			$userRoles = $this->my_get_current_user_roles();

			$can_multi_download = $this->can_multi_download();
			
			$download_limit = 3;
			// disable storing the document settings, we don't want to save the settings when viewing the order

			$this->disable_storing_document_settings();

			$meta_box_actions = array();
			$documents  = WPO_WCPDF()->documents->get_documents();

			foreach ($documents as $document) {
				$document_title = $document->get_title();
				switch (strtolower($document_title)) {
					case 'invoice':
					$download_title = 'download_invoice_' . $orderId;
					break;

					default:
					$download_title = 'download_slip_' . $orderId;
					break;
				}

				if ($document = wcpdf_get_document($document->get_type(), $order)) {
					$document_download_count = empty(get_post_meta($orderId, $download_title, true)) ? 0 : intval(get_post_meta($orderId, $download_title, true));
					$document_url     = $can_multi_download ?  WPO_WCPDF()->endpoint->get_document_link($order, $document->get_type()) : ( $document_download_count < $download_limit ? WPO_WCPDF()->endpoint->get_document_link($order, $document->get_type()) : '#');

					$document_title        = is_callable(array($document, 'get_title')) ? $document->get_title() : $document_title;
					$document_exists       = is_callable(array($document, 'exists')) ? $document->exists() : false;
					$document_printed      = $document_exists && is_callable(array($document, 'printed')) ? $document->printed() : false;
					$document_printed_data = $document_exists && $document_printed && is_callable(array($document, 'get_printed_data')) ? $document->get_printed_data() : [];
					$document_settings     = get_option('wpo_wcpdf_documents_settings_' . $document->get_type()); // $document-settings might be not updated with the last settings
					$unmark_printed_url    = !empty($document_printed_data) && isset($document_settings['unmark_printed']) ? WPO_WCPDF()->endpoint->get_document_printed_link('unmark', $order, $document->get_type()) : false;
					$manually_mark_printed = WPO_WCPDF()->main->document_can_be_manually_marked_printed($document);
					$mark_printed_url      = $manually_mark_printed ? WPO_WCPDF()->endpoint->get_document_printed_link('mark', $order, $document->get_type()) : false;
					$class                 = [$document->get_type()];

					if ($document_exists) {
						$class[] = 'exists';
					}
					if ($document_printed) {
						$class[] = 'printed';
					}

					$meta_box_actions[$document->get_type()] = array(
						'url'                   => esc_url($document_url),
						'alt'                   => "PDF " . $document_title,
						'title'                 => "PDF " . $document_title,
						'exists'                => $document_exists,
						'printed'               => $document_printed,
						'printed_data'          => $document_printed_data,
						'unmark_printed_url'    => $unmark_printed_url,
						'manually_mark_printed' => $manually_mark_printed,
						'mark_printed_url'      => $mark_printed_url,
						'class'                 => apply_filters('wpo_wcpdf_action_button_class', implode(' ', $class), $document),
						'id'					=> $download_title,
						'download_count'		=> $document_download_count,
					);
				}
			}

			$meta_box_actions = apply_filters('wpo_wcpdf_meta_box_actions', $meta_box_actions, $order->get_id());

			?>
			<ul class="wpo_wcpdf-actions">
				<?php

				$post = ($post_or_order_object instanceof \WP_Post) ? wc_get_order($post_or_order_object->ID) : $post_or_order_object;
				$orders = ($post_or_order_object instanceof \WP_Post) ? wc_get_order($post_or_order_object->ID) : $post_or_order_object;
				$orderId = $orders->get_id();
				/* Start Custom Script */
				$userID = get_current_user_id();

				//echo "Count is $count";

				//$printedStatus = get_post_meta($post, '_wcpdfSlipCreated', true);
				//$printedBy = get_post_meta($post, '_wcpdfSlipCreatedBy', true);
				//$totalPrinted = get_post_meta($post, '_wcpdfSlipTotalCreated' . $userID, true);

				$user_id = get_current_user_id();
				//$download_status = $this->handle_download($user_id, $orderId);
				//$current_count = get_post_meta($orderId, "download_count_$orderId", true);
				$downloaded_users = $this->get_users_who_downloaded_order($orderId);



				//var_dump(get_post_meta($orderId, "download_count_$orderId", true));
				/* End Custom Script */


				foreach ($meta_box_actions as $document_type => $data) {
					$url                   = isset($data['url']) ? esc_attr($data['url']) : '';
					$class                 = isset($data['class']) ? esc_attr($data['class']) : '';
					$alt                   = isset($data['alt']) ? esc_attr($data['alt']) : '';
					$title                 = isset($data['title']) ? esc_attr($data['title']) : '';
					$exists                = isset($data['exists']) && $data['exists'] ? '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path fill-rule="evenodd" d="M9,20.42L2.79,14.21L5.62,11.38L9,14.77L18.88,4.88L21.71,7.71L9,20.42Z"></path></svg>' : '';
					$manually_mark_printed = isset($data['manually_mark_printed']) && $data['manually_mark_printed'] && !empty($data['mark_printed_url']) ? '<p class="printed-data">&#x21b3; <a href="' . $data['mark_printed_url'] . '">' . __('Mark printed', 'woocommerce-pdf-invoices-packing-slips') . '</a></p>' : '';
					$printed               = isset($data['printed']) && $data['printed'] ? '<svg class="icon-printed" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path fill-rule="evenodd" clip-rule="evenodd" d="M8 4H16V6H8V4ZM18 6H22V18H18V22H6V18H2V6H6V2H18V6ZM20 16H18V14H6V16H4V8H20V16ZM8 16H16V20H8V16ZM8 10H6V12H8V10Z"></path></svg>' : '';
					$unmark_printed        = isset($data['unmark_printed_url']) && $data['unmark_printed_url'] ? '<a class="unmark_printed" href="' . $data['unmark_printed_url'] . '">' . __('Unmark', 'woocommerce-pdf-invoices-packing-slips') . '</a>' : '';
					$printed_data          = isset($data['printed']) && $data['printed'] && !empty($data['printed_data']['date']) ? '<p class="printed-data">&#x21b3; ' . $printed . '' . date_i18n('Y/m/d g:i:s a', strtotime($data['printed_data']['date'])) . '' . $unmark_printed . '</p>' : '';
					$button_id = isset($data['id']) ? esc_attr($data['id']) : 'download_button_' . $order->get_id();
					$download_count = isset($data['download_count']) ? esc_attr($data['download_count']) : 0;

					printf(
						'<li><a href="%1$s" class="button download_button %2$s" target="_blank" alt="%3$s" id="%8$s" hidden data-download_count="%9$s">%4$s%5$s <span id="count_display">%9$s</span></a>%6$s%7$s</li>',
						$url,
						$class,
						$alt,
						$title,
						$exists,
						$manually_mark_printed,
						$printed_data,
						$button_id,
						$download_count
					);
				}
				?>

			</ul>
			<script type="text/javascript">
				// Disable the button until the page is fully loaded
				document.addEventListener("DOMContentLoaded", function() {
					var downloadButtons = document.querySelectorAll(".download_button");
					downloadButtons.forEach(item => {
						item.removeAttribute("hidden");
					});
				});
				jQuery(document).ready(function() {
					// Add a click event listener to the download button
					jQuery(".download_button").on("click", function(e) {
						e.preventDefault(); // Prevent the default action (opening a new tab)

						// Your existing download handling logic here
						var pdfType = jQuery(this).attr('id'); //invoice / slip
						var countDisplay = jQuery(`#${pdfType}`).find('#count_display');
						var pdfURL = jQuery(this).attr('href'); //print pdf 
						var orderId = <?php echo $order->get_id(); ?>;
						var userId = <?php echo get_current_user_id(); ?>;
						var buttonId = document.querySelector(`#${pdfType}`);
						var currentCount = jQuery(this).attr('data-download_count');

						var downloadlimit = 3;

						// check the user role is administartor or warehouseprinters
						var userRole = <?php echo json_encode($this->my_get_current_user_roles()); ?>;


						// check if the user is admin or warehouseprinters
						if (userRole.includes('administrator') || userRole.includes('warehouseprinters')) {
							// Trigger the download manually if the conditions are met
							window.open(pdfURL, "_blank");

							// Your data to be sent to the server
							var data = {
								action: 'handle_download_action', // Action hook to be called in your PHP function
								nonce: '<?php echo wp_create_nonce("ajax-nonce"); ?>', // Nonce to verify the request
								userId: userId,
								pdfType: pdfType,
								orderId: orderId
							};

							// Perform AJAX request
							jQuery.post('<?php echo admin_url("admin-ajax.php"); ?>', data, function(response) {
								// Handle the response from the server
								buttonId.setAttribute('data-download_count', response.download_count)
								countDisplay.text(response.download_count);

								// if (response.download_count >= downloadlimit) {
								// 	buttonId.setAttribute('href', '#')

								// }
							});
						} else {
							if (currentCount < downloadlimit) {
								// Trigger the download manually if the conditions are met
								window.open(pdfURL, "_blank");

								// Your data to be sent to the server
								var data = {
									action: 'handle_download_action', // Action hook to be called in your PHP function
									nonce: '<?php echo wp_create_nonce("ajax-nonce"); ?>', // Nonce to verify the request
									userId: userId,
									pdfType: pdfType,
									orderId: orderId
								};

								// Perform AJAX request
								jQuery.post('<?php echo admin_url("admin-ajax.php"); ?>', data, function(response) {
									// Handle the response from the server
									buttonId.setAttribute('data-download_count', response.download_count)
									countDisplay.text(response.download_count);

									if (response.download_count >= downloadlimit) {
										buttonId.setAttribute('href', '#')

									}
								});

							} else {
								// Display a message if download limit is exceeded
								alert(`This has already been downloaded  ${currentCount} times by <?php echo implode(', ', $downloaded_users); ?>`);
							}
						}
					});
				});
			</script>
			<?php
		}
	}
	//Initialize the custom admin class
	Custom_Admin::instance();

endif;
