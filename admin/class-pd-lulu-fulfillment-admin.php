<?php

/**
 * The admin-specific functionality of the plugin.
 * 
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @link       www.peacefuldev.com
 * @since      1.0.0
 *
 * @package    PD_Lulu_Fulfillment
 * @subpackage PD_Lulu_Fulfillment/admin
 * @author     Brett Parshall <brett@peacefuldev.com>
 */
class PD_Lulu_Fulfillment_Admin
{
	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * The name for the wp cron
	 * @since	1.0.1
	 * @access	public
	 * @var		string		$cron_name	The name of the cron job that checks lulu order statuses
	 */
	public $cron_name = 'pd_lulu_order_status';

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct($plugin_name, $version)
	{

		$this->plugin_name = $plugin_name;
		$this->version = $version;
	}

	/**
	 * All Admin initalization options
	 * 
	 * @since 1.0.0
	 * @version 1.1.0 Added Cron and Lulu order Shipped actions
	 */
	public function init_admin()
	{
		// Register cron hooks!
		if (!wp_next_scheduled($this->cron_name)) {
			wp_clear_scheduled_hook($this->cron_name);
			wp_schedule_event(time(), 'hourly', $this->cron_name);
		}

		add_action($this->cron_name, [$this, 'check_all_order_statuses']);

		$options = get_option(PD_LULU_FULFILLMENT_OPTIONS);
		if ($options['auto_complete_orders'] == 'lulu_shipped') {
			add_action('pd_lulu_fulfillment_lulu_shipped', [$this, 'lulu_order_shipped_action']);
		}
	}

	/**
	 * When WooCommerce plugin is loaded, Include Lulu Product Class
	 * 
	 * @since 1.1.0
	 */
	public function wc_loaded()
	{
		require_once(plugin_dir_path(__FILE__) . 'classes/class-pd-lulu-fulfillment-woocommerce-product.php');
	}

	public function wc_load_lulu_product_class($phpClassName, $productType)
	{
		if ($productType == 'lulu4woocommerce') {
			$phpClassName = 'WC_Product_Lulu';
		}
		return $phpClassName;
	}

	public function wc_product_type_selector($productTypes)
	{
		if (!isset($productTypes['lulu4woocommerce'])) {
			$productTypes['lulu4woocommerce'] = 'Lulu Book';
		}
		return $productTypes;
	}

	public function wc_order_meta_box_actions($actions)
	{
		$actions['check_lulu_print_job_status'] = __('Check Lulu Print Job Status', PD_LULU_FULFILLMENT_DOMAIN);
		return $actions;
	}

	public function check_all_order_statuses()
	{
		$args = array(
			'limit' => 9999,
			'return' => 'ids',
			'status' => 'wc-processing'
		);
		$query = new WC_Order_Query($args);
		$orders = $query->get_orders();
		foreach ($orders as $order_id) {
			$order = wc_get_order($order_id);

			$printJobId = $order->get_meta('lulu_print_job_id');
			if (!$printJobId) {
				continue;
			}

			$this->wc_process_action_check_lulu_print_job_status($order);
		}
	}

	/**
	 * Check orders for Lulu print-job status, update if necessary
	 * 
	 * Check an order's print-job status. Manually triggerable from an action on the order edit screen. Also triggered automatically by WP cron
	 * 
	 * @param WC_ORDER $order The order to check for Lulu fulfillment status
	 * @since 1.1.0
	 * @version 1.3.0 Print-Job status in note made readable
	 */
	public function wc_process_action_check_lulu_print_job_status($order)
	{
		$printJobId = $order->get_meta('lulu_print_job_id');
		if ($printJobId) {
			$communicator = PD_Lulu_Fulfillment_Communicator::instance();
			$printJobStatusResponse = $communicator->getPrintJobStatus($printJobId);
			if ($printJobStatusResponse) {
				$printJobStatus = $printJobStatusResponse->name;
				$oldStatus = $order->get_meta('lulu_print_job_status');
				if ($oldStatus == $printJobStatus) {
					return;
				}
				$order->update_meta_data('lulu_print_job_status', $printJobStatus);
				$order->add_order_note('Lulu Fulfillment Status Updated: ' . PDLF()->getFulfillmentStatus($printJobStatus));
				$order->save();
				if ($printJobStatus == 'SHIPPED') {
					$trackingUrls = array();
					foreach ($printJobStatusResponse->line_item_statuses as $lineItemStatus) {
						if ($lineItemStatus->name == 'SHIPPED') {
							foreach ($lineItemStatus->messages->tracking_urls as $trackingUrl) {
								$trackingUrls[$lineItemStatus->messages->tracking_id] = $trackingUrl;
							}
						}
					}
					$order->update_meta_data('lulu_tracking_information', $trackingUrls);
					$order->save();
					do_action('pd_lulu_fulfillment_lulu_shipped', $order->ID);
				}
			}
		}
	}

	/**
	 * Display Lulu tracking information when WooCommerce "Order Complete" email sent
	 * 
	 * @version 1.1.0 Moved from below Order Details to above. Formatted in table like Order Details
	 * @since 1.0.0
	 */
	public function wc_email_order_details($order, $sentToAdmin, $plainText, $email)
	{
		$luluTracking = $order->get_meta('lulu_tracking_information');
		if ($email->id == 'customer_completed_order' && $luluTracking) {
			if (!$plainText) {
				// HTML Email
				require(plugin_dir_path(__FILE__) . 'partials/pd-lulu-fulfillment-order-tracking.php');
			} else {
				// Non HTML Email
				echo "Tracking Information:\r\n";
				foreach ($luluTracking as $trackingId => $trackingUrl) {
					echo "$trackingUrl\r\n";
				}
			}
		}
	}

	public function wc_lulu_add_to_cart()
	{
		wc_get_template('single-product/add-to-cart/simple.php');
	}

	/**
	 * Display Order information after order details
	 * 
	 * @since 1.0.0
	 * @version 1.3.0 - Display Lulu print-job status in readable form 
	 */
	public function wc_after_order_details($order)
	{
		if ($order->get_meta('lulu_print_job_id')) {
?>
			<p class="form-field form-field-wide">
				<label for="lulu_print_job_id">Lulu Print Job ID:</label>
				<input type="text" disabled value="<?php echo $order->get_meta('lulu_print_job_id') ?>">
			</p>
		<?php
		}
		$printJobStatus = $order->get_meta('lulu_print_job_status');
		if ($printJobStatus) {
		?>
			<p class="form-field form-field-wide">
				<label for="lulu_print_job_status">Lulu Print Job Status:</label>
				<input type="text" disabled value="<?= PDLF()->getFulfillmentStatus($printJobStatus) ?>">
			</p>
		<?php
		}
		$luluTracking = $order->get_meta('lulu_tracking_information');
		if ($luluTracking) {
		?>
			<p class="form-field form-field-wide">
				<label for="lulu_tracking_urls">Lulu Shipment Tracking:</label>
				<?php
				foreach ($luluTracking as $trackingId => $trackingUrl) { ?>
					<a href="<?php echo $trackingUrl ?>"><?php echo $trackingId ?></a>
				<?php
				} ?>
			</p>
		<?php
		}
	}

	/**
	 * Add the lulu tab to the $tabs array
	 * @see     https://github.com/woocommerce/woocommerce/blob/e1a82a412773c932e76b855a97bd5ce9dedf9c44/includes/admin/meta-boxes/class-wc-meta-box-product-data.php
	 * @param   $tabs
	 * 
	 * @version	1.1.0 Lulu tab added, Lulu specific settings moved there from General
	 * @since   1.0.0
	 */
	public function wc_include_lulu_tab($tabs)
	{
		// die(print_r($tabs,true));
		if (isset($tabs['variations'])) {
			$tabs['variations']['class'][] = 'show_if_lulu4woocommerce';
		}
		if (isset($tabs['inventory'])) {
			$tabs['inventory']['class'][] = 'show_if_lulu4woocommerce';
		}
		$tabs['lulu'] = array(
			'label'         => __('Lulu Printing', PD_LULU_FULFILLMENT_DOMAIN), // The name of your panel
			'target'        => 'pd_lulu_printing_panel', // Will be used to create an anchor link so needs to be unique
			'class'         => array('lulu_tab', 'show_if_lulu4woocommerce'), // Class for your panel tab - helps hide/show depending on product type
			'priority'      => 15, // Where your panel will appear. By default, 70 is last item
		);
		return $tabs;
	}

	/**
	 * Display Lulu Printing product data panel, for setting binding and book content information.
	 * 
	 * @since 1.1.0
	 */
	public function wc_include_lulu_panel()
	{
		// global $post;
		// $product = wc_get_product($post->ID);
		// die(var_dump($product->get_meta_data()));
		require(plugin_dir_path(__FILE__) . 'partials/pd-lulu-fulfillment-product-settings-panel.php');
	}

	/**
	 * Display fields for the lulu panel
	 * 
	 * @deprecated 1.1.0 Lulu specific options moved to the Lulu Printing tab instead of General
	 * @since   1.0.0
	 */
	public function wc_display_lulu_product_general_options($t)
	{
		// <div class="options_group show_if_lulu4woocommerce">
		// </div>
	}

	/**
	 * Save the custom lulu fields using CRUD method
	 * @param $post_id
	 * 
	 * @version	1.3.0 Run print cost calculation and store print cost in lulu_print_cost_[excl/incl]_tax meta fields
	 * @since 1.0.0
	 */
	public function wc_save_product_meta_fields($post_id)
	{
		$recalcPrintCost = false;
		$product = wc_get_product($post_id);

		$coverPdfId = isset($_POST['lulu_cover_pdf_attachment_id']) ? $_POST['lulu_cover_pdf_attachment_id'] : '';
		$product->update_meta_data('lulu_cover_pdf_attachment_id', $coverPdfId);

		$interiorPdfId = isset($_POST['lulu_interior_pdf_attachment_id']) ? $_POST['lulu_interior_pdf_attachment_id'] : '';
		$product->update_meta_data('lulu_interior_pdf_attachment_id', $interiorPdfId);

		$coverPdfUrl = isset($_POST['lulu_cover_pdf_url']) ? $_POST['lulu_cover_pdf_url'] : '';
		$product->update_meta_data('lulu_cover_pdf_url', esc_url_raw($coverPdfUrl));

		$contentPdfUrl = isset($_POST['lulu_interior_pdf_url']) ? $_POST['lulu_interior_pdf_url'] : '';
		$product->update_meta_data('lulu_interior_pdf_url', esc_url_raw($contentPdfUrl));

		$oldPageCount = $product->get_meta('lulu_page_count');
		$pageCount = isset($_POST['lulu_page_count']) ? $_POST['lulu_page_count'] : 0;
		$product->update_meta_data('lulu_page_count', (int)$pageCount);
		$recalcPrintCost = $recalcPrintCost || ($oldPageCount != $pageCount);

		$lulu_trim_sku = isset($_POST['lulu_trim_sku']) ? $_POST['lulu_trim_sku'] : '';
		$product->update_meta_data('lulu_trim_sku', sanitize_text_field($lulu_trim_sku));

		$lulu_color_sku = isset($_POST['lulu_color_sku']) ? $_POST['lulu_color_sku'] : '';
		$product->update_meta_data('lulu_color_sku', sanitize_text_field($lulu_color_sku));

		$lulu_print_sku = isset($_POST['lulu_print_sku']) ? $_POST['lulu_print_sku'] : '';
		$product->update_meta_data('lulu_print_sku', sanitize_text_field($lulu_print_sku));

		$lulu_bind_sku = isset($_POST['lulu_bind_sku']) ? $_POST['lulu_bind_sku'] : '';
		$product->update_meta_data('lulu_bind_sku', sanitize_text_field($lulu_bind_sku));

		$lulu_paper_sku = isset($_POST['lulu_paper_sku']) ? $_POST['lulu_paper_sku'] : '';
		$product->update_meta_data('lulu_paper_sku', sanitize_text_field($lulu_paper_sku));

		$lulu_finish_sku = isset($_POST['lulu_finish_sku']) ? $_POST['lulu_finish_sku'] : '';
		$product->update_meta_data('lulu_finish_sku', sanitize_text_field($lulu_finish_sku));

		$lulu_linen_sku = isset($_POST['lulu_linen_sku']) ? $_POST['lulu_linen_sku'] : '';
		$product->update_meta_data('lulu_linen_sku', sanitize_text_field($lulu_linen_sku));

		$lulu_foil_sku = isset($_POST['lulu_foil_sku']) ? $_POST['lulu_foil_sku'] : '';
		$product->update_meta_data('lulu_foil_sku', sanitize_text_field($lulu_foil_sku));

		$oldPodPackageId = $product->get_meta('lulu_pod_package_id');
		$podPackageId = $lulu_trim_sku . $lulu_color_sku . $lulu_print_sku . $lulu_bind_sku . $lulu_paper_sku . $lulu_finish_sku . $lulu_linen_sku . $lulu_foil_sku;
		$product->update_meta_data('lulu_pod_package_id', sanitize_text_field($podPackageId));
		$recalcPrintCost = $recalcPrintCost || ($oldPodPackageId != $podPackageId);

		$product->save();
		$hasPrices = $product->get_meta('lulu_print_cost_excl_tax');
		$recalcPrintCost = $recalcPrintCost || !$hasPrices;

		if ($recalcPrintCost) {
			$t = PD_Lulu_Fulfillment_Communicator::instance();
			$printJobCost = $t->getPrintJobCostCalculation(array(
				'contents' => array(
					array(
						'data' => WC_GET_PRODUCT($post_id),
						'quantity' => 1
					)
				),
				'destination' => array(
					'country'   => WC()->countries->get_base_country(),
					'state'     => WC()->countries->get_base_state(),
					'postcode'  => WC()->countries->get_base_postcode(),
					'city'      => WC()->countries->get_base_city(),
					// 'address'   => '',
					'address_1' => WC()->countries->get_base_address(),
					'address_2' => WC()->countries->get_base_address_2(),
				),
			));
			// die(var_dump($printJobCost));
			if ($printJobCost->is_success) {
				$product->update_meta_data('lulu_print_cost_excl_tax', $printJobCost->line_item_costs[0]->total_cost_excl_tax);
				$product->update_meta_data('lulu_print_cost_incl_tax', $printJobCost->line_item_costs[0]->total_cost_incl_tax);
				$product->delete_meta_data('lulu_errors');
			} else {
				$product->update_meta_data('lulu_errors', $printJobCost->errors);
				$product->delete_meta_data('lulu_print_cost_excl_tax');
				$product->delete_meta_data('lulu_print_cost_incl_tax');
			}
		}

		$product->save();
	}

	/**
	 * Make Lulu calls when a WC_Order gets updated to a paid status
	 * @since 	1.0.0
	 */
	public function wc_order_payment_status_changed($orderId, $order)
	{
		// Need to determine if order has line items that get ordered via lulu
		$hasLuluFulfilledItems = false;
		foreach ($order->get_items() as $lineItem) {
			$lineItemData = $lineItem->get_data();
			if (!$lineItemData['product_id']) {
				continue;
			}

			$product = wc_get_product($lineItemData['product_id']);
			if ($product->is_type('lulu4woocommerce')) {
				$hasLuluFulfilledItems = true;
				continue;
			}
		}
		if ($hasLuluFulfilledItems) {
			$communicator = PD_Lulu_Fulfillment_Communicator::instance();
			$communicator->createPrintJob($order);
		}
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles()
	{

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in PD_Lulu_Fulfillment_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The PD_Lulu_Fulfillment_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style($this->plugin_name, plugin_dir_url(__FILE__) . 'css/pd-lulu-fulfillment-admin.css', array(), $this->version, 'all');
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts()
	{

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in PD_Lulu_Fulfillment_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The PD_Lulu_Fulfillment_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_script($this->plugin_name, plugin_dir_url(__FILE__) . 'js/pd-lulu-fulfillment-admin.js', array('jquery'), $this->version, false);
	}

	/**
	 * Register Admmin settings for the plugin
	 * 
	 * @version 1.2.0 Added Shipping options
	 * @since 1.0.0
	 */
	public function register_admin_settings()
	{
		register_setting(
			PD_LULU_FULFILLMENT_OPTIONS,
			PD_LULU_FULFILLMENT_OPTIONS,
			[
				'sanitize_callback' => [&$this, 'settings_validation']
			]
		);

		/**
		 * Settings Sections
		 * 1. General
		 * 2. API
		 * 3. Shipping
		 */
		add_settings_section(
			'general',
			'General Lulu Settings',
			array(&$this, 'general_section_text'),
			'pd_lulu_fulfillment_settings_main'
		);
		add_settings_section(
			'sandbox_api',
			'Lulu Sandbox API Settings',
			array(&$this, 'sandbox_section_text'),
			'pd_lulu_fulfillment_settings_main'
		);
		add_settings_section(
			'production_api',
			'Lulu Production API Settings',
			array(&$this, 'production_section_text'),
			'pd_lulu_fulfillment_settings_main'
		);
		add_settings_section(
			'shipping',
			'Lulu Fulfillment Shipping Settings',
			array(&$this, 'shipping_section_text'),
			'pd_lulu_fulfillment_settings_main'
		);

		add_settings_field(
			'lulu_api',
			'Lulu API to Use',
			array(&$this, 'general_lulu_api'),
			'pd_lulu_fulfillment_settings_main',
			'general',
			[
				'class' => 'l4w_input'
			]
		);
		add_settings_field(
			'contact_email',
			'Contact Email',
			array(&$this, 'general_contact_email'),
			'pd_lulu_fulfillment_settings_main',
			'general',
			[
				'class' => 'l4w_input'
			]
		);
		add_settings_field(
			'auto_complete_orders',
			'Auto Complete Orders',
			array(&$this, 'general_auto_complete_orders'),
			'pd_lulu_fulfillment_settings_main',
			'general',
			[
				'class' => 'l4w_input'
			]
		);
		add_settings_field(
			'sandbox_api_key',
			'API Key',
			array(&$this, 'sandbox_api_key'),
			'pd_lulu_fulfillment_settings_main',
			'sandbox_api',
			[
				'class' => 'l4w_input'
			]
		);
		add_settings_field(
			'production_api_key',
			'API Key',
			array(&$this, 'production_api_key'),
			'pd_lulu_fulfillment_settings_main',
			'production_api',
			[
				'class' => 'l4w_input'
			]
		);

		add_settings_field(
			'shipping_enable_lulu_method',
			'Lulu shipping Method',
			array(&$this, 'shipping_enable_lulu_method'),
			'pd_lulu_fulfillment_settings_main',
			'shipping',
			array(
				'class' => 'l4w_input',
			)
		);

		add_settings_field(
			'shipping_package_label',
			'Shipping Package Label',
			array(&$this, 'shipping_package_label'),
			'pd_lulu_fulfillment_settings_main',
			'shipping',
			array(
				'class' => 'l4w_input',
			)
		);

		add_settings_field(
			'shipping_fee_label',
			'Shipping Fee Label',
			array(&$this, 'shipping_fee_label'),
			'pd_lulu_fulfillment_settings_main',
			'shipping',
			array(
				'class' => 'l4w_input',
			)
		);
	}

	function general_lulu_api()
	{
		$options = get_option(PD_LULU_FULFILLMENT_OPTIONS);
		$luluAPI = isset($options['lulu_api']) ? $options['lulu_api'] : 'sandbox';
		?>
		<select id='general_lulu_api' name='<?= PD_LULU_FULFILLMENT_OPTIONS ?>[lulu_api]'>
			<option value="sandbox" <?php echo $luluAPI == 'sandbox' ? 'selected' : '' ?>>Sandbox</option>
			<option value="production" <?php echo $luluAPI == 'production' ? 'selected' : '' ?>>Production</option>
		</select>
	<?PHP
	}

	function general_contact_email()
	{
		$options = get_option(PD_LULU_FULFILLMENT_OPTIONS);
		$contactEmail = isset($options['contact_email']) ? $options['contact_email'] : '';
		echo "<input type='text' id='general_contact_email' name='" . PD_LULU_FULFILLMENT_OPTIONS . "[contact_email]' value='" . esc_attr($contactEmail) . "'/>";
	}

	/**
	 * Display Auto Complete Orders option
	 * 
	 * @since 1.1.0
	 */
	function general_auto_complete_orders()
	{
		$options = get_option(PD_LULU_FULFILLMENT_OPTIONS);
		$autoComplete = isset($options['auto_complete_orders']) ? $options['auto_complete_orders'] : 'sandbox';
	?>
		<select id='general_auto_complete_orders' name='<?= PD_LULU_FULFILLMENT_OPTIONS ?>[auto_complete_orders]'>
			<option value="never" <?= $autoComplete == 'never' ? 'selected' : '' ?>>Never</option>
			<option value="lulu_shipped" <?= $autoComplete == 'lulu_shipped' ? 'selected' : '' ?>>Lulu Shipped Order</option>
		</select>
	<?PHP
	}

	/**
	 * Display Enable Lulu Shipping Method option
	 * 
	 * @since 1.2.0
	 */
	function shipping_enable_lulu_method()
	{
		$options = get_option(PD_LULU_FULFILLMENT_OPTIONS);
		$luluMethodEnabled = isset($options['shipping_enable_lulu_method']) ? $options['shipping_enable_lulu_method'] : 'disabled';
	?>
		<select id="shipping_enable_lulu_method" name='<?= PD_LULU_FULFILLMENT_OPTIONS ?>[shipping_enable_lulu_method]'>
			<option value="disabled" <?= $luluMethodEnabled == 'disabled' ? 'selected' : '' ?>>Disabled</option>
			<option value="enabled" <?= $luluMethodEnabled == 'enabled' ? 'selected' : '' ?>>Enabled</option>
		</select>
	<?PHP
	}

	/**
	 * Display Shipping Package Label option
	 * 
	 * @since 1.2.0
	 */
	function shipping_package_label()
	{
		$options = get_option(PD_LULU_FULFILLMENT_OPTIONS);
		$packageLabel = isset($options['shipping_package_label']) ? $options['shipping_package_label'] : '';
		echo "<input type='text' id='shipping_package_label' name='" . PD_LULU_FULFILLMENT_OPTIONS . "[shipping_package_label]' value='" . esc_attr($packageLabel) . "' placeholder='" . __('Print On Demand Shipping', PD_LULU_FULFILLMENT_DOMAIN) . "'/>";
	}

	/**
	 * Display Shipping Fee Label
	 * 
	 * @since 1.2.0
	 */
	function shipping_fee_label()
	{
		$options = get_option(PD_LULU_FULFILLMENT_OPTIONS);
		$feeLabel = isset($options['shipping_fee_label']) ? $options['shipping_fee_label'] : '';
		echo "<input type='text' id='shipping_fee_label' name='" . PD_LULU_FULFILLMENT_OPTIONS . "[shipping_fee_label]' value='" . esc_attr($feeLabel) . "' placeholder='" . __('Standard', PD_LULU_FULFILLMENT_DOMAIN) . "' />";
	}

	function sandbox_api_key()
	{
		$options = get_option(PD_LULU_FULFILLMENT_OPTIONS);
		$apiKey = isset($options['sandbox_api_key']) ? $options['sandbox_api_key'] : '';
		echo "<textarea id='sandbox_api_key' name='" . PD_LULU_FULFILLMENT_OPTIONS . "[sandbox_api_key]' rows='1'>" . esc_attr($apiKey) . "</textarea>";
	}

	function production_api_key()
	{
		$options = get_option(PD_LULU_FULFILLMENT_OPTIONS);
		$apiKey = isset($options['production_api_key']) ? $options['production_api_key'] : '';
		echo "<textarea id='production_api_key' name='" . PD_LULU_FULFILLMENT_OPTIONS . "[production_api_key]' rows='1'>" . esc_attr($apiKey) . "</textarea>";
	}

	public function general_section_text()
	{
		echo "<p> General Lulu Settings</p>";
	}

	public function sandbox_section_text()
	{
		echo "<p>Settings for the Lulu Sandbox API</p>";
	}

	public function production_section_text()
	{
		echo "<p>Settings for the Lulu Production API</p>";
	}

	public function shipping_section_text()
	{
		echo "<p>Shipping Charge Settings</p>";
	}

	/**
	 * General Plugin Settings input validation
	 * 
	 * @version 1.3.0 Added authorization change detection - delete saved authorization tokens if potentially no longer relevant.
	 * @since 1.0.0
	 */
	public function settings_validation($input)
	{
		$options = get_option(PD_LULU_FULFILLMENT_OPTIONS);
		$authorizationChanged = false;

		$newinput['contact_email'] = trim($input['contact_email']);
		if (!is_email($newinput['contact_email'])) {
			add_settings_error('contact_email', 'contact_email_error', 'Contact Email must be a valid email address');
			$newinput['contact_email'] = '';
		}

		$newinput['lulu_api'] = trim($input['lulu_api']);
		if ($newinput['lulu_api'] != 'sandbox' && $newinput['lulu_api'] != 'production') {
			$newinput['lulu_api'] = 'sandbox';
		}
		$authorizationChanged = $authorizationChanged || $newinput['lulu_api'] != $options['lulu_api'];

		$newinput['auto_complete_orders'] = trim($input['auto_complete_orders']);
		if ($newinput['auto_complete_orders'] != 'never' && $newinput['auto_complete_orders'] != 'lulu_shipped') {
			$newinput['auto_complete_orders'] = 'never';
		}

		$newinput['sandbox_api_key'] = trim($input['sandbox_api_key']);
		if (!$newinput['sandbox_api_key']) {
			$newinput['sandbox_api_key'] = '';
		}
		$authorizationChanged = $authorizationChanged || $newinput['sandbox_api_key'] != $options['sandbox_api_key'];

		$newinput['production_api_key'] = trim($input['production_api_key']);
		if (!$newinput['production_api_key']) {
			$newinput['production_api_key'] = '';
		}
		$authorizationChanged = $authorizationChanged || $newinput['production_api_key'] != $options['production_api_key'];

		$newinput['shipping_enable_lulu_method'] = trim($input['shipping_enable_lulu_method']);
		if (!$newinput['shipping_enable_lulu_method']) {
			$newinput['shipping_enable_lulu_method'] = 'disabled';
		}

		$newinput['shipping_package_label'] = trim($input['shipping_package_label']);
		if (!$newinput['shipping_package_label']) {
			$newinput['shipping_package_label'] = '';
		}

		$newinput['shipping_fee_label'] = trim($input['shipping_fee_label']);
		if (!$newinput['shipping_fee_label']) {
			$newinput['shipping_fee_label'] = '';
		}

		if ($authorizationChanged) {
			delete_transient('PD_Lulu_Fulfillment_access_token');
			delete_transient('PD_Lulu_Fulfillment_refresh_token');
		}
		return $newinput;
	}

	public function add_menu_page()
	{
		add_menu_page('Lulu Fulfillment', 'Lulu Fulfillment', 'manage_options', 'pd_lulu_fulfillment_settings_main', array(&$this, 'draw_settings_page'), 'icon/url', 66);
	}

	public function draw_settings_page()
	{
	?>
		<h1>Lulu Fulfillment Settings</h1>
		<form action="options.php" method="post">
			<?php
			settings_errors();
			settings_fields(PD_LULU_FULFILLMENT_OPTIONS);
			do_settings_sections('pd_lulu_fulfillment_settings_main');
			?>
			<input name="submit" class="button button-primary" type="submit" value="Save" />
		</form>
<?php
	}

	public function wc_cart_shipping_packages($packages)
	{
		return $packages;
	}

	/**
	 * If there are only lulu products in the order, mark it completed. Activated via hook if Lulu Shipped set 
	 * 
	 * @see init_admin
	 * @since 1.1.0
	 */
	public function lulu_order_shipped_action($order_id)
	{
		$order = wc_get_order($order_id);
		$allItemsLulu = true;

		foreach ($order->get_items() as $lineItem) {
			$lineItemData = $lineItem->get_data();
			if (!$lineItemData['product_id']) {
				continue;
			}

			$product = wc_get_product($lineItemData['product_id']);
			if (!$product->is_type('lulu4woocommerce')) {
				$allItemsLulu = false;
				break;
			}
		}

		if ($allItemsLulu) {
			$order->update_status('completed');
		}
	}

	/**
	 * Display error notice when WooCommerce is not installed and active.
	 * 
	 * @since 1.2.0
	 */
	public function display_no_woocommerce_notice()
	{
		$screen = get_current_screen();
		if (!$screen) {
			return;
		}
		echo '<div class="error is-dismissible"><p><strong>';
		echo  __('Lulu Fulfillment requires WooCommerce to be installed and active. You can download <a href="https://woocommerce.com/" target="_blank">WooCommerce</a> here.');
		echo '</strong></p></div>';
	}

	/**
	 * Add Lulu Shipping Column to Woocommerce Orders list
	 * 
	 * @since 1.3.0
	 */
	public function wc_add_order_lulu_column($columns)
	{
		$newColumns = array();
		foreach ($columns as $columnName => $columnInfo) {
			if ($columnName == 'order_status') {
				$newColumns['lulu_details'] = __('Lulu Prints', PD_LULU_FULFILLMENT_DOMAIN);
			}
			$newColumns[$columnName] = $columnInfo;
		}

		return $newColumns;
	}

	/**
	 * Fill Lulu Shipping Column with Print Job Status
	 * 
	 * @since 1.3.0
	 */
	public function wc_fill_lulu_order_column($column)
	{
		global $post;
		if ($column == 'lulu_details') {
			$order = wc_get_order($post->ID);

			$status = $order->get_meta('lulu_print_job_status');
			$statusDisplay = PDLF()->getFulfillmentStatus($status);
			$statusDesc = PDLF()->getFulfillmentStatusDescription($status);

			if ($status) {
				echo "<mark class='order-status status-completed tips' data-tip='$statusDesc'><span>";
				echo $statusDisplay;
				echo "</span></mark>";
			}
		}
	}

	/**
	 * Add Lulu Specific Meta Box if product is lulu book
	 * 
	 * @since 1.3.0
	 * @uses output_product_meta_box
	 */
	public function wc_add_product_meta_boxes($post)
	{
		$product = wc_get_product($post->ID);
		if ($product->is_type('lulu4woocommerce')) {
			add_meta_box(
				'pd-lulu-fulfillment-product',
				__('Lulu Printing Information', PD_LULU_FULFILLMENT_DOMAIN),
				[$this, 'output_product_meta_box'],
				null,
				'side',
				'high'
			);
		}
	}

	/**
	 * Output Lulu specific meta box
	 * 
	 * @since 1.3.0
	 * @see wc_add_product_meta_boxes
	 */
	public function output_product_meta_box($post)
	{
		require(plugin_dir_path(__FILE__) . 'partials/pd-lulu-fulfillment-product-meta-box.php');
	}

	/**
	 * Output the "Print Cost" button on Admin Edit Order Screen if there's Lulu Products in order.
	 */
	public function wc_add_print_cost_button($order)
	{
		$hasProds = false;
		foreach ($order->get_items() as $lineItem) {
			$lineItemData = $lineItem->get_data();
			if (!$lineItemData['product_id']) {
				continue;
			}

			$product = wc_get_product($lineItemData['product_id']);
			$hasProds = $product->is_type('lulu4woocommerce');

			if($hasProds){
				break;
			}
		}

		if(!$hasProds){
			return;
		}

		?>
		<button type="button" class="button make-print-cost">Make Print Cost</button>
		<?php
	}

	/**
	 * Adds script to handle print-cost order functions to admin footer.
	 */
	public static function l4w_admin_footer() {
		?>
		<!-- WooCommerce Tracks -->
		<script type="text/javascript">
			jQuery(document).ready(function($) {
				$('.make-print-cost').on('click', () => {
					var data = {
						'action': 'l4w_print_cost',
						'post_id': <?= get_the_ID() || -1?>,
					};

					// since 2.8 ajaxurl is always defined in the admin header and points to admin-ajax.php
					jQuery.post(ajaxurl, data, function(response) {
						window.location.reload();
					});
				});
			});
		</script>
		<?php
	}

	public static function order_print_cost($data) {
		global $wpdb; // this is how you get access to the database
		$post_id = intval( $_POST['post_id'] );
	
		$order = wc_get_order($post_id);

		if(!$order) {
			self::ajax_output("Invalid request");
		}
		
		if(!self::orderHasLuluProducts($order)) {
			self::ajax_output("No print-on-demand items to make print-cost.");
		}

		$package = self::getLuluPackageFromOrder($order);

		$communicator = PD_Lulu_Fulfillment_Communicator::instance();
		$costCalculation = $communicator->getPrintJobCostCalculation($package);

		$order->set_total($costCalculation->total_cost_incl_tax);
		$order->save();
		self::ajax_output(json_encode($costCalculation));
	}

	private static function ajax_output($value) {
		ob_clean();
		echo $value;
		wp_die();
	}

	public static function woocommerce_order_item_lulu4woocommerce_html($item_id, $item, $order) {
		?>
		<h1>nice</h1>
		<?php
	}

	public static function woocommerce_admin_order_item_headers($order) {
		// if(!self::orderHasLuluProducts($order)) {
		// 	// No reason to add lulu item cost header then!
		// 	return;
		// }
		?>
			<th class="lulu_item_cost sortable" data-sort="float">Cost to Print</th>
		<?php
	}

	public static function woocommerce_admin_order_item_values($product, $item, $item_id) {
		$isLulu = $product && $product->is_type('lulu4woocommerce');
		$hasPrices = $product ? $product->get_meta('lulu_print_cost_excl_tax') : null;
		?>
		<td><?=$hasPrices ? $hasPrices : ''?></td>
		<?php
	}

	private static function orderHasLuluProducts($order) {
		$luluLineItems = self::getLuluLineItemsFromOrder($order);
		return count($luluLineItems) > 0;
	}

	private static function getLuluLineItemsFromOrder($order) {
		$ret = array();
		foreach ($order->get_items() as $lineItem) {
			$lineItemData = $lineItem->get_data();
			if(!$lineItemData['product_id']) {
				continue;
			}

			$product = wc_get_product($lineItemData['product_id']);
			if($product->is_type('lulu4woocommerce')) {
				$ret[] = $lineItem;
			}
		}
		return $ret;
	}

	private static function getLuluPackageFromOrder($order) {
		$lineItems = self::getLuluLineItemsFromOrder($order);
		if(count($lineItems) == 0) {
			return array();
		}

		$contents = array();
		foreach($lineItems as $lineItem) {
			$contents[] = array(
				'data' => WC_GET_PRODUCT($lineItem['product_id']),
				'quantity' => $lineItem['quantity'],
			);
		}
		return array(
			'contents' => $contents,
			'destination' => array(
				'country'   => $order->get_shipping_country(),
				'state'     => $order->shipping_state,
				'postcode'  => $order->shipping_postcode,
				'city'      => $order->shipping_city,
				'address_1' => $order->shipping_address_1,
				'address_2' => $order->shipping_address_2,
			),
		);
	}
}
