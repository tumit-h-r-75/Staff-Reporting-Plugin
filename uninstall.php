<?php
/**
 * Uninstall plugin - Clean up database and options
 *
 * @package    Bassmah_Staff_Reports
 * @author     Tumit <tumit@bassmah.ca>
 */

// If uninstall is not called from WordPress, exit
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Security check
if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;

// Define table names
$table_reports = $wpdb->prefix . 'staff_reports';
$table_salary_settings = $wpdb->prefix . 'staff_salary_settings';
$table_working_days = $wpdb->prefix . 'staff_working_days';

// Option: Keep data or remove completely
$keep_data = get_option('bassmah_keep_data_on_uninstall', false);

if (!$keep_data) {
    // Drop custom tables
    $wpdb->query("DROP TABLE IF EXISTS $table_reports");
    $wpdb->query("DROP TABLE IF EXISTS $table_salary_settings");
    $wpdb->query("DROP TABLE IF EXISTS $table_working_days");
    
    // Remove plugin options
    $options = array(
        'bassmah_task_categories',
        'bassmah_task_statuses',
        'bassmah_email_notifications',
        'bassmah_default_currency',
        'bassmah_default_working_days',
        'bassmah_reminder_time',
        'bassmah_version',
        'bassmah_keep_data_on_uninstall'
    );
    
    foreach ($options as $option) {
        delete_option($option);
    }
    
    // Remove user meta related to plugin (if any)
    $wpdb->query("DELETE FROM {$wpdb->usermeta} WHERE meta_key LIKE 'bassmah_%'");
    
    // Remove post meta related to plugin (if any)
    $wpdb->query("DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE 'bassmah_%'");
}

// Clear scheduled cron jobs
wp_clear_scheduled_hook('bassmah_daily_reminder');
wp_clear_scheduled_hook('bassmah_monthly_salary_calculation');

// Remove custom user roles
remove_role('bassmah_staff');
remove_role('bassmah_manager');

// Flush rewrite rules
flush_rewrite_rules();

// Log uninstall (optional)
if (defined('WP_DEBUG') && WP_DEBUG) {
    error_log('Bassmah Staff Reports plugin uninstalled successfully.');
}
