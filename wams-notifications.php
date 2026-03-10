<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://wams.aztify.com
 * @since             1.2.0
 * @package           wams_notifications
 *
 * @wordpress-plugin
 * Plugin Name:       WAMS Woo Notifier
 * Plugin URI:        https://wams.aztify.com
 * Description:       Automatically send WhatsApp notifications for WooCommerce purchases, ensuring your customers stay updated with instant order status alerts.
 * Version:           1.0
 * Author:            Zain Sardar
 * Author URI:        https://wa.me/923246270322
 * License:           GPLv3
 * License URI:       http://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain:       wams-notifications
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
	die;
}

if (is_readable(__DIR__ . '/vendor/autoload.php')) {
	require __DIR__ . '/vendor/autoload.php';
}

define('wams_NOTIFICATIONS_VERSION', '1.2.0');

global $wams_notifications_db_version;
$wams_notifications_db_version = '1.0';
global $wpdb;
$prefix = (isset($wpdb) && is_object($wpdb)) ? $wpdb->prefix : 'wp_';
$wams_notifications_table_name = $prefix . 'wams_notif_contents';

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-wams-notifications-activator.php
 */
function wams_notifications_activate()
{
	require_once plugin_dir_path(__FILE__) . 'includes/class-wams-notifications-activator.php';
	wams_Notifications_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-wams-notifications-deactivator.php
 */
function wams_notifications_deactivate()
{
	require_once plugin_dir_path(__FILE__) . 'includes/class-wams-notifications-deactivator.php';
	wams_Notifications_Deactivator::deactivate();
}

register_activation_hook(__FILE__, 'wams_notifications_activate');
register_deactivation_hook(__FILE__, 'wams_notifications_deactivate');

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path(__FILE__) . 'includes/class-wams-notifications.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function wams_notifications_run()
{

	$plugin = new wams_Notifications();
	$plugin->run();

}
wams_notifications_run();
