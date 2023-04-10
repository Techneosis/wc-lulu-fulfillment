<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              www.peacefuldev.com
 * @since             1.0.0
 * @package           PD_Lulu_Fulfillment
 *
 * @wordpress-plugin
 * Plugin Name:       Peaceful Lulu Fulfillment
 * Plugin URI:        www.peacefuldev.com/lulu-fulfillment
 * Description:       Fulfill book sales with Lulu's print-on-demand services
 * Version:           1.4.1
 * Author:            Brett Parshall
 * Author URI:        www.peacefuldev.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       pd-lulu-fulfillment
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Current plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'PD_LULU_FULFILLMENT_VERSION', '1.1.0' );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-pd-lulu-fulfillment-activator.php
 */
function activate_pd_lulu_fulfillment() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-pd-lulu-fulfillment-activator.php';
	PD_Lulu_Fulfillment_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-pd-lulu-fulfillment-deactivator.php
 */
function deactivate_pd_lulu_fulfillment() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-pd-lulu-fulfillment-deactivator.php';
	PD_Lulu_Fulfillment_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_pd_lulu_fulfillment' );
register_deactivation_hook( __FILE__, 'deactivate_pd_lulu_fulfillment' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-pd-lulu-fulfillment.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_pd_lulu_fulfillment() {

	$plugin = new PD_Lulu_Fulfillment();
	$plugin->run();

}
run_pd_lulu_fulfillment();
