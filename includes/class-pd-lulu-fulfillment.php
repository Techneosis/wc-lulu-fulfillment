<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       www.peacefuldev.com
 * @since      1.0.0
 *
 * @package    PD_Lulu_Fulfillment
 * @subpackage PD_Lulu_Fulfillment/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    PD_Lulu_Fulfillment
 * @subpackage PD_Lulu_Fulfillment/includes
 * @author     Brett Parshall <brett@peacefuldev.com>
 */
class PD_Lulu_Fulfillment
{

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      PD_Lulu_Fulfillment_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function __construct()
	{
		if (defined('PD_LULU_FULFILLMENT_VERSION')) {
			$this->version = PD_LULU_FULFILLMENT_VERSION;
		} else {
			$this->version = '1.3.0';
		}
		$this->plugin_name = 'pd-lulu-fulfillment';

		$this->load_dependencies();
		$this->set_locale();

		$this->define_shipping_hooks();
		$this->define_admin_hooks();
		$this->define_public_hooks();
	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - PD_Lulu_Fulfillment_Loader. Orchestrates the hooks of the plugin.
	 * - PD_Lulu_Fulfillment_i18n. Defines internationalization functionality.
	 * - PD_Lulu_Fulfillment_Admin. Defines all hooks for the admin area.
	 * - PD_Lulu_Fulfillment_Public. Defines all hooks for the public side of the site.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @version	1.3.0 Added Master Lulu dependency
	 * @since   1.0.0
	 * @access  private
	 */
	private function load_dependencies()
	{

		/**
		 * Define Constants and stuff that the plugin uses.
		 */
		require_once plugin_dir_path(dirname(__FILE__)) . 'includes/config.php';

		/**
		 * Include Plugin Settings Manager
		 */
		require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-pd-lulu-fulfillment-master.php';

		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-pd-lulu-fulfillment-loader.php';

		/**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */
		require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-pd-lulu-fulfillment-i18n.php';

		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		require_once plugin_dir_path(dirname(__FILE__)) . 'admin/class-pd-lulu-fulfillment-admin.php';

		/**
		 * The class responsible for defining all actions that occur in the public-facing
		 * side of the site.
		 */
		require_once plugin_dir_path(dirname(__FILE__)) . 'public/class-pd-lulu-fulfillment-public.php';

		/**
		 * The class responsible for defining all actions that occur regarding Woocommerce shipping
		 */
		require_once plugin_dir_path(dirname(__FILE__)) . 'shipping/class-pd-lulu-fulfillment-shipping.php';

		/**
		 * The class responsible for all requests made to the Lulu api
		 */
		require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-pd-lulu-fulfillment-communicator.php';

		$this->loader = new PD_Lulu_Fulfillment_Loader();
	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the PD_Lulu_Fulfillment_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_locale()
	{

		$plugin_i18n = new PD_Lulu_Fulfillment_i18n();

		$this->loader->add_action('plugins_loaded', $plugin_i18n, 'load_plugin_textdomain');
	}

	/**
	 * Register all of the hooks related to the Woocommerce Shipping functionality
	 * of the plugin.
	 *
	 * @since    1.2.0
	 * @access   private
	 */
	private function define_shipping_hooks()
	{

		$options = get_option(PD_LULU_FULFILLMENT_OPTIONS);
		if(!isset($options['shipping_enable_lulu_method']) || $options['shipping_enable_lulu_method'] != 'enabled') {
			return; // Shipping Method disabled, using WooCommerce store's configured shipping options.
		}
		
		$plugin_shipping = new PD_Lulu_Fulfillment_Shipping($this->get_plugin_name(), $this->get_version());

		$this->loader->add_action('woocommerce_shipping_init', $plugin_shipping, 'woocommerce_shipping_init');
		$this->loader->add_action('woocommerce_shipping_methods', $plugin_shipping, 'add_lulu_shipping_method');

		$this->loader->add_filter('woocommerce_cart_shipping_packages', $plugin_shipping, 'woocommerce_cart_shipping_packages');
		$this->loader->add_filter('woocommerce_shipping_package_name', $plugin_shipping, 'woocommerce_shipping_package_name', 10, 3);
	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_admin_hooks()
	{

		$plugin_admin = new PD_Lulu_Fulfillment_Admin($this->get_plugin_name(), $this->get_version());

		$this->loader->add_action('init', $plugin_admin, 'init_admin');
		$this->loader->add_action('admin_init', $plugin_admin, 'register_admin_settings');
		$this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_styles');
		$this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts');


		$this->loader->add_action('admin_menu', $plugin_admin, 'add_menu_page');




		if (in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
			$this->loader->add_action('woocommerce_loaded', $plugin_admin, 'wc_loaded');
			$this->loader->add_action('woocommerce_order_payment_status_changed', $plugin_admin, 'wc_order_payment_status_changed', 10, 2);
			$this->loader->add_filter('woocommerce_product_data_tabs', $plugin_admin, 'wc_include_lulu_tab');
			$this->loader->add_filter('woocommerce_product_data_panels', $plugin_admin, 'wc_include_lulu_panel');
			$this->loader->add_filter('product_type_selector', $plugin_admin, 'wc_product_type_selector');
			$this->loader->add_action('woocommerce_product_class', $plugin_admin, 'wc_load_lulu_product_class', 10, 2);
			$this->loader->add_action('woocommerce_process_product_meta', $plugin_admin, 'wc_save_product_meta_fields');
			// $this->loader->add_action('woocommerce_product_options_general_product_data', $plugin_admin, 'wc_display_lulu_product_general_options');
			// TODO: Move to front-end
			$this->loader->add_action('woocommerce_lulu4woocommerce_add_to_cart', $plugin_admin, 'wc_lulu_add_to_cart');

			$this->loader->add_action('woocommerce_admin_order_data_after_order_details', $plugin_admin, 'wc_after_order_details');

			$this->loader->add_filter('woocommerce_cart_shipping_packages', $plugin_admin, 'wc_cart_shipping_packages');
			// Check Lulu Print Job Status Action
			$this->loader->add_action('woocommerce_order_actions', $plugin_admin, 'wc_order_meta_box_actions');
			$this->loader->add_action('woocommerce_order_action_check_lulu_print_job_status', $plugin_admin, 'wc_process_action_check_lulu_print_job_status');
			$this->loader->add_action('woocommerce_email_order_details', $plugin_admin, 'wc_email_order_details', 10, 4);
			
			$this->loader->add_filter('manage_edit-shop_order_columns', $plugin_admin, 'wc_add_order_lulu_column');
			$this->loader->add_action('manage_shop_order_posts_custom_column', $plugin_admin, 'wc_fill_lulu_order_column');
			$this->loader->add_action('add_meta_boxes_product', $plugin_admin, 'wc_add_product_meta_boxes');
			
			$this->loader->add_filter('admin_footer', $plugin_admin, 'l4w_admin_footer', 10, 0);
			$this->loader->add_action('woocommerce_order_item_lulu4woocommerce_html', $plugin_admin, 'woocommerce_order_item_lulu4woocommerce_html', 10, 3);
			$this->loader->add_action('woocommerce_admin_order_item_headers', $plugin_admin, 'woocommerce_admin_order_item_headers');
			$this->loader->add_action('woocommerce_admin_order_item_values', $plugin_admin, 'woocommerce_admin_order_item_values', 10, 3);
			$this->loader->add_action('woocommerce_order_item_add_action_buttons', $plugin_admin, 'wc_add_print_cost_button');
			$this->loader->add_action('wp_ajax_l4w_print_cost', $plugin_admin, 'order_print_cost');
			
			// $this->loader->add_action('plugins_loaded', PDLF(), 'update');
		} else {
			$this->loader->add_action('admin_notices', $plugin_admin, 'display_no_woocommerce_notice');
		}
	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_public_hooks()
	{

		$plugin_public = new PD_Lulu_Fulfillment_Public($this->get_plugin_name(), $this->get_version());

		$this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_styles');
		$this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_scripts');
	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function run()
	{
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     1.0.0
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name()
	{
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     1.0.0
	 * @return    PD_Lulu_Fulfillment_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader()
	{
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version()
	{
		return $this->version;
	}
}

function PDLF() {
	return PD_Lulu_Fulfillment_Master::instance();
}