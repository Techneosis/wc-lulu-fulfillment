<?php

/**
 * The shipping-specific functionality of the plugin.
 *
 * @link       www.peacefuldev.com
 * @since      1.2.0
 *
 * @package    PD_Lulu_Fulfillment
 * @subpackage PD_Lulu_Fulfillment/shipping
 */

/**
 * The shipping-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the shipping-specific stylesheet and JavaScript.
 *
 * @package    PD_Lulu_Fulfillment
 * @subpackage PD_Lulu_Fulfillment/shipping
 * @author     Brett Parshall <Brettparshall@gmail.com>
 */
class PD_Lulu_Fulfillment_Shipping {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.2.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.2.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.2.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;
	}

	/**
	 * Include the Lulu Shipping Method class - extends Woocommerce Shipping Methods
	 * 
	 * @since	1.2.0
	 */
	public function woocommerce_shipping_init() {
		if(!class_exists('WC_Shipping_Lulu')) {
			require_once(plugin_dir_path(__FILE__) . 'classes/class-pd-lulu-fulfillment-shipping-method.php');
		}
	}

	/**
	 * Tie Lulu Shipping Method ID to Lulu Shipping Method class
	 * 
	 * @since 1.2.0
	 */
	public function add_lulu_shipping_method($methods) {

		$methods['pd_lulu_shipping'] = 'WC_Shipping_Lulu';
		return $methods;
	}

	/**
	 * Split the Lulu books out from the rest of the shipped items into their own package for shipping.
	 * 
	 * @since 1.2.0
	 */
	public function woocommerce_cart_shipping_packages($packages) {
		$luluShipping = new WC_Shipping_Lulu();
		$newPackages = array();
		$luluPackage = array(
			'contents'        => array(),
			// 'contents_cost'   => array_sum( wp_list_pluck( $this->get_items_needing_shipping(), 'line_total' ) ),
			// 'applied_coupons' => $this->get_applied_coupons(),
			'user'            => array(
				'ID' => get_current_user_id(),
			),
			// 'destination'     => array(
				// 'country'   => $this->get_customer()->get_shipping_country(),
				// 'state'     => $this->get_customer()->get_shipping_state(),
				// 'postcode'  => $this->get_customer()->get_shipping_postcode(),
				// 'city'      => $this->get_customer()->get_shipping_city(),
				// 'address'   => $this->get_customer()->get_shipping_address(),
				// 'address_1' => $this->get_customer()->get_shipping_address(), // Provide both address and address_1 for backwards compatibility.
				// 'address_2' => $this->get_customer()->get_shipping_address_2(),
			// ),
			// 'cart_subtotal'   => $this->get_displayed_subtotal(),
		);
		$luluPackage['ship_via'] = array($luluShipping->id);
		$luluPackage['destination'] = $packages[0]['destination'];
		$luluPackage['applied_coupons'] = $packages[0]['applied_coupons'];
		$luluPackage['package_name'] = 'My Awesomeness';

		foreach($packages as $package) {
			$newPackage = $package;
			$newPackage['contents'] = array();

			foreach($package['contents'] as $id => $lineItem) {
				$_product = $lineItem['data'];
				if('lulu4woocommerce' == $_product->get_type()) {
					$luluPackage['contents'][] = $lineItem;
				} else {
					$newPackage['contents'][] = $lineItem;
				}
			}
			if(sizeof($newPackage['contents'])) {
				$newPackages[] = $newPackage;
			}
		}
		if(sizeof($luluPackage['contents'])) {
			$luluPackage['contents_cost'] = array_sum(wp_list_pluck($luluPackage['contents'], 'line_total'));
			$newPackages[] = $luluPackage;
		}
		return $newPackages;
		// return $packages;
	}

	/**
	 * Update the Shipping Package Name for the Lulu shipping to match
	 * 
	 * @since 1.2.0
	 */
	public function woocommerce_shipping_package_name($packageName, $packageIndex, $package) {
		$luluShipping = new WC_Shipping_Lulu();
		// die(var_dump($package));
		$isLuluPackage = isset($package['ship_via']) && in_array($luluShipping->id, $package['ship_via']);
		if($isLuluPackage) {
			$options = get_option(PD_LULU_FULFILLMENT_OPTIONS);
			$packageLabel = isset($options['shipping_package_label']) && $options['shipping_package_label'] 
				? $options['shipping_package_label'] 
				: __('Print On Demand Shipping', PD_LULU_FULFILLMENT_DOMAIN);
			$packageName = $packageLabel;
		}
		return $packageName;
	}
}
