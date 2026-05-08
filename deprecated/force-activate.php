<?php
/**
 * Force Plugin Activation Script
 * 
 * This script will force the plugin to activate and create database tables
 * Run this once to fix database issues
 */

// Include WordPress
require_once('../../../wp-config.php');

// Include WordPress core
require_once(ABSPATH . 'wp-load.php');

// Check if plugin is already active
if (!is_plugin_active('Staff Reporting Plugin/bassmah-staff-reports.php')) {
    // Activate the plugin
    activate_plugin('Staff Reporting Plugin/bassmah-staff-reports.php');
    echo "Plugin activated successfully!\n";
} else {
    // Force reactivation to create tables
    deactivate_plugins(array('Staff Reporting Plugin/bassmah-staff-reports.php'));
    activate_plugin('Staff Reporting Plugin/bassmah-staff-reports.php');
    echo "Plugin reactivated successfully!\n";
}

// Check if tables exist
global $wpdb;

$tables_to_check = array(
    $wpdb->prefix . 'staff_reports',
    $wpdb->prefix . 'staff_salary_settings', 
    $wpdb->prefix . 'staff_working_days'
);

echo "Checking database tables:\n";
foreach ($tables_to_check as $table) {
    $result = $wpdb->get_var("SHOW TABLES LIKE '$table'");
    if ($result) {
        echo "✓ Table '$table' exists\n";
    } else {
        echo "✗ Table '$table' missing\n";
    }
}

echo "\nDone! Check your WordPress error log for details.\n";
echo "You can now access your dashboard.\n";
?>
