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

// Force HTTPS for all assets to prevent mixed content issues
if (!function_exists('bassmah_force_https')) {
    function bassmah_force_https($url) {
        if (is_ssl() && strpos($url, 'http://') === 0) {
            return str_replace('http://', 'https://', $url);
        }
        return $url;
    }
}

// Hook into WordPress filters to force HTTPS for assets
if (!function_exists('bassmah_setup_https_filters')) {
    function bassmah_setup_https_filters() {
        if (is_ssl()) {
            add_filter('style_loader_src', 'bassmah_force_https');
            add_filter('script_loader_src', 'bassmah_force_https');
            add_filter('template_directory_uri', 'bassmah_force_https');
            add_filter('stylesheet_directory_uri', 'bassmah_force_https');
            add_filter('plugins_url', 'bassmah_force_https');
            add_filter('upload_dir', function($uploads) {
                if (isset($uploads['baseurl']) && strpos($uploads['baseurl'], 'http://') === 0) {
                    $uploads['baseurl'] = str_replace('http://', 'https://', $uploads['baseurl']);
                }
                if (isset($uploads['url']) && strpos($uploads['url'], 'http://') === 0) {
                    $uploads['url'] = str_replace('http://', 'https://', $uploads['url']);
                }
                return $uploads;
            });
        }
    }
    add_action('plugins_loaded', 'bassmah_setup_https_filters');
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

// REST API endpoints are loaded in the main plugin class
// See includes/class-bassmah-staff-reports.php lines 95-98


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

// REST API classes are loaded in the main plugin class
// See includes/class-bassmah-staff-reports.php lines 95-98

// Run the plugin
run_bassmah_staff_reports();
