<?php
/*
Plugin Name: Custom WC PDF Extension
Description: Custom extension for WC PDF Invoices & Packing Slips
Version: 1.0
Author: HighB33Kay
*/

class CustomWCPDFExtension
{

	private static $instance;

	private function __construct()
	{
		// Add actions or hooks here
		add_action('plugins_loaded', array($this, 'include_main_plugin_file'));
		// Add other actions as needed
	}

	public static function instance()
	{
		if (is_null(self::$instance)) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function include_main_plugin_file()
	{
		// Check if the main plugin class exists
		if (class_exists('WPO\WC\PDF_Invoices\Main') && class_exists('WPO\WC\PDF_Invoices\Admin')) {

			// The main class is available, include your custom file
			require_once plugin_dir_path(__FILE__) . 'class-custom-main.php';

			// Include the custom admin class
			require_once plugin_dir_path(__FILE__) . 'class-custom-admin.php';

			// Include the custom bulk action class
			require_once plugin_dir_path(__FILE__) . 'class-custom-bulk.php';
		} else {
			// The main class is not available, handle accordingly
			// You may want to log an error or take another action

			error_log('Main and admin class of the WooCommerce PDF Invoices & Packing Slips plugin not found.');
		}
	}
}

// Initialize the custom plugin class
CustomWCPDFExtension::instance();
