<?php
/**
 * Plugin Name: Bassmah Staff Reports
 * Plugin URI: https://example.com/
 * Description: A comprehensive staff reporting and salary management plugin for WordPress.
 * Version: 1.0.0
 * Author: Tumit
 * Author URI: https://example.com/
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: bassmah-staff-reports
 * Domain Path: /languages
 */

if (!defined('ABSPATH')) {
    exit;
}

define('BASMAH_STAFF_REPORTS_VERSION', '1.0.0');
define('BASMAH_STAFF_REPORTS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('BASMAH_STAFF_REPORTS_PLUGIN_URL', plugin_dir_url(__FILE__));

require_once BASMAH_STAFF_REPORTS_PLUGIN_DIR . 'includes/class-activator.php';
require_once BASMAH_STAFF_REPORTS_PLUGIN_DIR . 'includes/class-deactivator.php';
require_once BASMAH_STAFF_REPORTS_PLUGIN_DIR . 'includes/class-roles.php';
require_once BASMAH_STAFF_REPORTS_PLUGIN_DIR . 'includes/class-reports.php';
require_once BASMAH_STAFF_REPORTS_PLUGIN_DIR . 'includes/class-salary.php';
require_once BASMAH_STAFF_REPORTS_PLUGIN_DIR . 'includes/class-working-days.php';
require_once BASMAH_STAFF_REPORTS_PLUGIN_DIR . 'includes/class-security.php';
require_once BASMAH_STAFF_REPORTS_PLUGIN_DIR . 'includes/class-export.php';
require_once BASMAH_STAFF_REPORTS_PLUGIN_DIR . 'includes/class-helper.php';

if (is_admin()) {
    require_once BASMAH_STAFF_REPORTS_PLUGIN_DIR . 'admin/class-admin.php';
}

require_once BASMAH_STAFF_REPORTS_PLUGIN_DIR . 'public/class-public.php';

require_once BASMAH_STAFF_REPORTS_PLUGIN_DIR . 'api/class-api-reports.php';
require_once BASMAH_STAFF_REPORTS_PLUGIN_DIR . 'api/class-api-salary.php';
require_once BASMAH_STAFF_REPORTS_PLUGIN_DIR . 'api/class-api-auth.php';

register_activation_hook(__FILE__, array('Basmah_Staff_Reports_Activator', 'activate'));
register_deactivation_hook(__FILE__, array('Basmah_Staff_Reports_Deactivator', 'deactivate'));

function bassmah_staff_reports_init() {
    load_plugin_textdomain('bassmah-staff-reports', false, dirname(plugin_basename(__FILE__)) . '/languages/');
}
add_action('plugins_loaded', 'bassmah_staff_reports_init');
