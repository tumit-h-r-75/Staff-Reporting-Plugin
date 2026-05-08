<?php
/**
* Plugin Name:      Bassmah Staff Reports
* Plugin URI:       https://bassmah.ca
* Description:      Staff Daily Work Reporting System for Bassmah - Allows staff to submit daily work reports and managers to monitor them with salary deduction integration.
* Version:          1.0.1
* Author:           Tumit
* Author URI:       https://bassmah.ca
* License:          GPL v2 or later
* License URI:      https://www.gnu.org/licenses/gpl-2.0.html
* Text Domain:      bassmah-staff-reports
* Domain Path:      /languages
* Requires at least: 6.0
* Requires PHP:     8.0
*/

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Define plugin constants
if (!defined('BASSMAH_STAFF_REPORTS_VERSION')) {
    define('BASSMAH_STAFF_REPORTS_VERSION', '1.0.1');
}
if (!defined('BASSMAH_STAFF_REPORTS_PLUGIN_NAME')) {
    define('BASSMAH_STAFF_REPORTS_PLUGIN_NAME', 'Bassmah Staff Reports');
}
if (!defined('BASSMAH_STAFF_REPORTS_PLUGIN_DIR')) {
    define('BASSMAH_STAFF_REPORTS_PLUGIN_DIR', plugin_dir_path(__FILE__));
}
if (!defined('BASSMAH_STAFF_REPORTS_PLUGIN_URL')) {
    define('BASSMAH_STAFF_REPORTS_PLUGIN_URL', plugin_dir_url(__FILE__));
}
if (!defined('BASSMAH_STAFF_REPORTS_PLUGIN_BASENAME')) {
    define('BASSMAH_STAFF_REPORTS_PLUGIN_BASENAME', plugin_basename(__FILE__));
}

/**
 * The code that runs during plugin activation.
 */
if (!function_exists('activate_bassmah_staff_reports')) {
    function activate_bassmah_staff_reports() {
        require_once BASSMAH_STAFF_REPORTS_PLUGIN_DIR . 'includes/class-activator.php';
        Bassmah_Staff_Reports_Activator::activate();
    }
}

/**
 * The code that runs during plugin deactivation.
 */
if (!function_exists('deactivate_bassmah_staff_reports')) {
    function deactivate_bassmah_staff_reports() {
        require_once BASSMAH_STAFF_REPORTS_PLUGIN_DIR . 'includes/class-deactivator.php';
        Bassmah_Staff_Reports_Deactivator::deactivate();
    }
}

register_activation_hook(__FILE__, 'activate_bassmah_staff_reports');
register_deactivation_hook(__FILE__, 'deactivate_bassmah_staff_reports');

/**
 * Include necessary files for the plugin.
 */

// Main plugin class
require BASSMAH_STAFF_REPORTS_PLUGIN_DIR . 'includes/class-bassmah-staff-reports.php';

// Include files for REST API endpoints
// Make sure these file paths are correct based on your plugin structure
require BASSMAH_STAFF_REPORTS_PLUGIN_DIR . 'includes/class-bassmah-staff-reports-rest-api.php';
// Assuming Bassmah_Staff_Reports_REST_Report_Approvals is in this file or a file included by it.
// If it's in a separate file, include it here. For example:
// require BASSMAH_STAFF_REPORTS_PLUGIN_DIR . 'includes/class-bassmah-staff-reports-rest-report-approvals.php';
// Or if it's within class-bassmah-staff-reports-rest-api.php, make sure it's properly namespaced or defined.


/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
if (!function_exists('run_bassmah_staff_reports')) {
    function run_bassmah_staff_reports() {
        $plugin = new Bassmah_Staff_Reports();
        $plugin->run();
    }
}

// Include REST API classes directly
require_once BASSMAH_STAFF_REPORTS_PLUGIN_DIR . 'api/class-rest-reports.php';
require_once BASSMAH_STAFF_REPORTS_PLUGIN_DIR . 'api/class-rest-report-approvals.php';
require_once BASSMAH_STAFF_REPORTS_PLUGIN_DIR . 'api/class-rest-export.php';

// Register REST routes
add_action('rest_api_init', function() {
    $rest_reports = new Bassmah_Staff_Reports_REST_Reports();
    $rest_reports->register_routes();
    
    $rest_approvals = new Bassmah_Staff_Reports_REST_Report_Approvals();
    $rest_approvals->register_routes();
    
    $rest_export = new Bassmah_Staff_Reports_REST_Export();
    $rest_export->register_routes();
});

// Run the plugin
run_bassmah_staff_reports();
