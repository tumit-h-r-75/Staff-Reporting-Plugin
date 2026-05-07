<?php
/**
 * Plugin Name:       Bassmah Staff Reports Pro
 * Plugin URI:        https://bassmah.ca
 * Description:       Staff Daily Work Reporting System for Bassmah - Allows staff to submit daily work reports and managers to monitor them with salary deduction integration.
 * Version:           1.0.0
 * Author:            Tumit
 * Author URI:        https://bassmah.ca
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       bassmah-staff-reports
 * Domain Path:       /languages
 * Requires at least: 6.0
 * Requires PHP:      8.0
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Define plugin constants
define('BASSMAH_STAFF_REPORTS_VERSION', '1.0.0');
define('BASSMAH_STAFF_REPORTS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('BASSMAH_STAFF_REPORTS_PLUGIN_URL', plugin_dir_url(__FILE__));
define('BASSMAH_STAFF_REPORTS_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * The code that runs during plugin activation.
 */
function activate_bassmah_staff_reports() {
    require_once BASSMAH_STAFF_REPORTS_PLUGIN_DIR . 'includes/class-activator.php';
    Bassmah_Staff_Reports_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 */
function deactivate_bassmah_staff_reports() {
    require_once BASSMAH_STAFF_REPORTS_PLUGIN_DIR . 'includes/class-deactivator.php';
    Bassmah_Staff_Reports_Deactivator::deactivate();
}

register_activation_hook(__FILE__, 'activate_bassmah_staff_reports');
register_deactivation_hook(__FILE__, 'deactivate_bassmah_staff_reports');

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require BASSMAH_STAFF_REPORTS_PLUGIN_DIR . 'includes/class-bassmah-staff-reports.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_bassmah_staff_reports() {
    $plugin = new Bassmah_Staff_Reports();
    $plugin->run();
}

// Run the plugin
run_bassmah_staff_reports();
