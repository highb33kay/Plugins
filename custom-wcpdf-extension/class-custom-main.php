<?php
//class-custom-main.php
//error_reporting(E_ALL);
//ini_set('display_errors', 1);

if (!class_exists('Custom_Main')) :

	class Custom_Main extends \WPO\WC\PDF_Invoices\Main
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

			// Add your custom code for the CustomMain class here

			/* Start Custom Script */
			add_action( 'wp_ajax_generate_wpo_wcpdf_extension', array( $this, 'generate_document_ajax' ) );
			add_action( 'wp_ajax_nopriv_generate_wpo_wcpdf_extension', array( $this, 'generate_document_ajax' ) );
			/* End Custom Script */
		}
		

		/**
		 * Load and generate the template output with ajax
		 */
		public function generate_document_ajax()
		{

			//die(var_dump($_REQUEST));
			
			$access_type  = WPO_WCPDF()->endpoint->get_document_link_access_type();
			$redirect_url = WPO_WCPDF()->endpoint->get_document_denied_frontend_redirect_url();

			//Need to rework this
			$_REQUEST['access_key'] =  wp_create_nonce('generate_wpo_wcpdf_extension');	

			// handle bulk actions access key (_wpnonce) and legacy access key (order_key)
			if (empty($_REQUEST['access_key'])) {
				foreach (array('_wpnonce', 'order_key') as $legacy_key) {
					if (!empty($_REQUEST[$legacy_key])) {
						$_REQUEST['access_key'] = sanitize_text_field($_REQUEST[$legacy_key]);
					}
				}
			}

			$valid_nonce = !empty($_REQUEST['access_key']) && !empty($_REQUEST['action']) && wp_verify_nonce($_REQUEST['access_key'], $_REQUEST['action']);

			// check if we have the access key set
			if (empty($_REQUEST['access_key'])) {
				$message = esc_attr__('You do not have sufficient permissions to access this page. Reason: empty access key', 'woocommerce-pdf-invoices-packing-slips');
				wcpdf_safe_redirect_or_die($redirect_url, $message);
			}

			// check if we have the action
			if (empty($_REQUEST['action'])) {
				$message = esc_attr__('You do not have sufficient permissions to access this page. Reason: empty action', 'woocommerce-pdf-invoices-packing-slips');
				wcpdf_safe_redirect_or_die($redirect_url, $message);
			}

			// Check the nonce - guest access can use nonce if user is logged in
			if (is_user_logged_in() && in_array($access_type, array('logged_in', 'guest')) && !$valid_nonce) {
				$message = esc_attr__('You do not have sufficient permissions to access this page. Reason: invalid nonce', 'woocommerce-pdf-invoices-packing-slips');
				wcpdf_safe_redirect_or_die($redirect_url, $message);
			}

			// Check if all parameters are set
			if (empty($_REQUEST['document_type']) && !empty($_REQUEST['template_type'])) {
				$_REQUEST['document_type'] = $_REQUEST['template_type'];
			}

			if (empty($_REQUEST['order_ids'])) {
				$message = esc_attr__("You haven't selected any orders", 'woocommerce-pdf-invoices-packing-slips');
				wcpdf_safe_redirect_or_die(null, $message);
			}

			if (empty($_REQUEST['document_type'])) {
				$message = esc_attr__('Some of the export parameters are missing.', 'woocommerce-pdf-invoices-packing-slips');
				wcpdf_safe_redirect_or_die(null, $message);
			}

			// debug enabled by URL
			if (isset($_REQUEST['debug']) && !(is_user_logged_in() || isset($_REQUEST['my-account']))) {
				$this->enable_debug();
			}

			$document_type = sanitize_text_field($_REQUEST['document_type']);
			$order_ids     = (array) array_map('absint', explode('x', $_REQUEST['order_ids']));
			$order         = false;

			// single order
			if (count($order_ids) === 1) {
				$order_id = reset($order_ids);
				$order    = wc_get_order($order_id);

				if ($order && $order->get_status() == 'auto-draft') {
					$message = esc_attr__('You have to save the order before generating a PDF document for it.', 'woocommerce-pdf-invoices-packing-slips');
					wcpdf_safe_redirect_or_die(null, $message);
				} elseif (!$order) {
					$message = sprintf(
						/* translators: %s: Order ID */
						esc_attr__('Could not find the order #%s.', 'woocommerce-pdf-invoices-packing-slips'),
						$order_id
					);
					wcpdf_safe_redirect_or_die(null, $message);
				}
			}

			// Process oldest first: reverse $order_ids array if required
			$sort_order         = apply_filters('wpo_wcpdf_bulk_document_sort_order', 'ASC');
			$current_sort_order = (count($order_ids) > 1 && end($order_ids) < reset($order_ids)) ? 'DESC' : 'ASC';
			if (in_array($sort_order, array('ASC', 'DESC')) && $sort_order != $current_sort_order) {
				$order_ids = array_reverse($order_ids);
			}

			// set default is allowed
			$allowed = true;

			// no order when it is a single order
			if (!$order && 1 === count($order_ids)) {
				$allowed = false;
			}

			// check the user privileges
			$full_permission = WPO_WCPDF()->admin->user_can_manage_document($document_type);

			// multi-order only allowed with permissions
			if (!$full_permission && 1 < count($order_ids)) {
				$allowed = false;
			}

			// 'guest' is hybrid, it can behave as 'logged_in' if the user is logged in, but if not, behaves as 'full'
			if ('guest' === $access_type) {
				$access_type = is_user_logged_in() ? 'logged_in' : 'full';
			}

			switch ($access_type) {
				case 'logged_in':
				if (!is_user_logged_in() || !$valid_nonce) {
					$allowed = false;
					break;
				}

				if (!$full_permission) {
					if (!isset($_REQUEST['my-account']) && !isset($_REQUEST['shortcode'])) {
						$allowed = false;
						break;
					}

						// check if current user is owner of order IMPORTANT!!!
					if (!current_user_can('view_order', $order_ids[0])) {
						$allowed = false;
						break;
					}
				}
				break;
				case 'full':
					// check if we have a valid access key only when it's not from bulk actions
				if (!isset($_REQUEST['bulk']) && $order && !hash_equals($order->get_order_key(), $_REQUEST['access_key'])) {
					$allowed = false;
					break;
				}
				break;
			}

			$allowed = apply_filters('wpo_wcpdf_check_privs', $allowed, $order_ids);

			if (!$allowed) {
				$message = esc_attr__('You do not have sufficient permissions to access this page.', 'woocommerce-pdf-invoices-packing-slips');
				wcpdf_safe_redirect_or_die($redirect_url, $message);
			}

			/* Start Custom Script */
			$alreadyPrintedOrders = '';
			if (
				isset($_GET['document_type']) &&
				$_GET['document_type'] == "packing-slip" &&
				!empty($order_ids) &&
				!current_user_can('administrator') &&
				!current_user_can('warehouseprinters')
			) {
				$this->getOrderPrintedStatusByIDsFunc($order_ids);
			}
			/* End Custom Script */

			// if we got here, we're safe to go!
			try {
				// log document creation to order notes
				if (count($order_ids) > 1 && isset($_REQUEST['bulk'])) {
					add_action('wpo_wcpdf_init_document', function ($document) {
						$this->log_document_creation_to_order_notes($document, 'bulk');
						$this->log_document_creation_trigger_to_order_meta($document, 'bulk');
						$this->mark_document_printed($document, 'bulk');
					});
				} elseif (isset($_REQUEST['my-account'])) {
					add_action('wpo_wcpdf_init_document', function ($document) {
						$this->log_document_creation_to_order_notes($document, 'my_account');
						$this->log_document_creation_trigger_to_order_meta($document, 'my_account');
						$this->mark_document_printed($document, 'my_account');
					});
				} else {
					add_action('wpo_wcpdf_init_document', function ($document) {
						$this->log_document_creation_to_order_notes($document, 'single');
						$this->log_document_creation_trigger_to_order_meta($document, 'single');
						$this->mark_document_printed($document, 'single');
					});
				}

				// get document
				$document = wcpdf_get_document($document_type, $order_ids, true);


				if ($document) {
					do_action('wpo_wcpdf_document_created_manually', $document, $order_ids); // note that $order_ids is filtered and may not be the same as the order IDs used for the document (which can be fetched from the document object itself with $document->order_ids)

					$output_format = WPO_WCPDF()->settings->get_output_format($document);

					switch ($output_format) {
						case 'ubl':
						$document->output_ubl();
						break;
						case 'html':
						add_filter('wpo_wcpdf_use_path', '__return_false');
						$document->output_html();
						break;
						case 'pdf':
						default:
						if (has_action('wpo_wcpdf_created_manually')) {
							do_action('wpo_wcpdf_created_manually', $document->get_pdf(), $document->get_filename());
						}
						$output_mode = WPO_WCPDF()->settings->get_output_mode($document_type);

						/* Start Custom Script */
						$userID = get_current_user_id();
						$userData = get_userdata($userID);
						$createdBy = isset($userData->data->ID) ? $userData->data->ID : "";

						if (isset($userData->first_name)) {
							$createdBy = $createdBy . "||" . $userData->first_name;
							if (isset($userData->last_name)) $createdBy .= " " . $userData->last_name;
						} else if (isset($userData->data->user_login)) {
							$createdBy = $createdBy . "||" . $userData->data->user_login;
						}

						foreach ($order_ids as $orderID) {
							$totalPrinted = get_post_meta($orderID, '_wcpdfSlipTotalCreated' . $userID, true);
							$totalPrinted = ($totalPrinted) ? ($totalPrinted + 1) : 1;

							update_post_meta($orderID, '_wcpdfSlipCreated', 1);
							update_post_meta($orderID, '_wcpdfSlipCreatedBy', $createdBy);
							update_post_meta($orderID, '_wcpdfSlipTotalCreated' . $userID, $totalPrinted);
						}
						/* End Custom Script */

						$document->output_pdf($output_mode);
						break;
					}
				} else {
					$message = sprintf(
						/* translators: document type */
						esc_html__("Document of type '%s' for the selected order(s) could not be generated", 'woocommerce-pdf-invoices-packing-slips'),
						$document_type
					);
					wcpdf_safe_redirect_or_die(null, $message);
				}
			} catch (\Dompdf\Exception $e) {
				$message = 'DOMPDF Exception: ' . $e->getMessage();
				wcpdf_log_error($message, 'critical', $e);
				wcpdf_output_error($message, 'critical', $e);
			} catch (\WPO\WC\UBL\Exceptions\FileWriteException $e) {
				$message = 'UBL FileWrite Exception: ' . $e->getMessage();
				wcpdf_log_error($message, 'critical', $e);
				wcpdf_output_error($message, 'critical', $e);
			} catch (\Exception $e) {
				$message = 'Exception: ' . $e->getMessage();
				wcpdf_log_error($message, 'critical', $e);
				wcpdf_output_error($message, 'critical', $e);
			} catch (\Error $e) {
				$message = 'Fatal error: ' . $e->getMessage();
				wcpdf_log_error($message, 'critical', $e);
				wcpdf_output_error($message, 'critical', $e);
			}
			exit;
		}
	}
	//Initialize the custom admin class
	Custom_Main::instance();
endif;
