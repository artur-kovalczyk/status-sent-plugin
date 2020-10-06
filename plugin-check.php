<?php

/**
 *
 * @link              http://arturkowalczyk.com
 * @since             1.0.0
 * @package           Plugin_Check
 *
 * @wordpress-plugin
 * Plugin Name:       Plugin Check
 * Plugin URI:        http://arturkowalczyk.com
 * Description:       Plugin Check for scheduled send status of Wordpress Core files and all plugins
 * Version:           1.0.0
 * Author:            Artur Kowalczyk
 * Author URI:        http://arturkowalczyk.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       plugin-check
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'PLUGIN_CHECK_VERSION', '1.0.0' );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-plugin-check-activator.php
 */
function activate_plugin_check() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-plugin-check-activator.php';
	Plugin_Check_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-plugin-check-deactivator.php
 */
function deactivate_plugin_check() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-plugin-check-deactivator.php';
	Plugin_Check_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_plugin_check' );
register_deactivation_hook( __FILE__, 'deactivate_plugin_check' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-plugin-check.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_plugin_check() {

	$plugin = new Plugin_Check();
	$plugin->run();

    new Plugin_Check_Admin_Display();

}
run_plugin_check();
