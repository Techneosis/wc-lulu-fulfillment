<?php

/**
 * Define the internationalization functionality
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @link       www.peacefuldev.com
 * @since      1.0.0
 *
 * @package    PD_Lulu_Fulfillment
 * @subpackage PD_Lulu_Fulfillment/includes
 */

/**
 * Define the internationalization functionality.
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @since      1.0.0
 * @package    PD_Lulu_Fulfillment
 * @subpackage PD_Lulu_Fulfillment/includes
 * @author     Brett Parshall <Brettparshall@gmail.com>
 */
class PD_Lulu_Fulfillment_i18n {


	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since    1.0.0
	 */
	public function load_plugin_textdomain() {

		load_plugin_textdomain(
			'pd-lulu-fulfillment',
			false,
			dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages/'
		);

	}



}
