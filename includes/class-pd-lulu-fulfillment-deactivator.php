<?php

/**
 * Fired during plugin deactivation
 *
 * @link       www.peacefuldev.com
 * @since      1.0.0
 *
 * @package    PD_Lulu_Fulfillment
 * @subpackage PD_Lulu_Fulfillment/includes
 */

/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 *
 * @since      1.0.0
 * @package    PD_Lulu_Fulfillment
 * @subpackage PD_Lulu_Fulfillment/includes
 * @author     Brett Parshall <Brettparshall@gmail.com>
 */
class PD_Lulu_Fulfillment_Deactivator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function deactivate() {
		// Clear out API Key transients
		delete_transient('PD_Lulu_Fulfillment_access_token');
		delete_transient('PD_Lulu_Fulfillment_refresh_token');
	}

}
