<?php
/**
 * Database Fix Script
 * 
 * This script will manually create the required database tables
 * and fix any activation issues
 */

// Security check
if (!defined('ABSPATH')) {
    exit;
}

// Include WordPress
require_once(ABSPATH . 'wp-config.php');
require_once(ABSPATH . 'wp-load.php');

// Include database upgrade functions
require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

global $wpdb;

echo "=== Bassmah Staff Reports - Database Fix ===\n\n";

// Define table names with correct prefix
$table_reports = $wpdb->prefix . 'staff_reports';
$table_salary_settings = $wpdb->prefix . 'staff_salary_settings';
$table_working_days = $wpdb->prefix . 'staff_working_days';

// Set charset
$charset_collate = $wpdb->get_charset_collate();

// Create staff_reports table
$sql_reports = "CREATE TABLE IF NOT EXISTS $table_reports (
    id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id BIGINT(20) UNSIGNED NOT NULL,
    report_date DATE NOT NULL,
    submission_time DATETIME DEFAULT CURRENT_TIMESTAMP,
    status VARCHAR(20) NOT NULL DEFAULT 'submitted',
    tasks_json LONGTEXT NOT NULL,
    manager_comment TEXT NULL,
    ip_address VARCHAR(45) NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY unique_user_date (user_id, report_date),
    KEY idx_user_id (user_id),
    KEY idx_report_date (report_date),
    KEY idx_status (status)
) $charset_collate;";

// Create staff_salary_settings table
$sql_salary = "CREATE TABLE IF NOT EXISTS $table_salary_settings (
    id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id BIGINT(20) UNSIGNED NOT NULL,
    monthly_salary DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    working_days_per_month INT(11) NOT NULL DEFAULT 22,
    daily_rate DECIMAL(10,2) GENERATED ALWAYS AS (monthly_salary / working_days_per_month) STORED,
    currency VARCHAR(3) NOT NULL DEFAULT 'CAD',
    effective_from DATE NOT NULL,
    created_by BIGINT(20) UNSIGNED NOT NULL,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY unique_user_effective (user_id, effective_from),
    KEY idx_user_id (user_id),
    KEY idx_created_by (created_by)
) $charset_collate;";

// Create staff_working_days table
$sql_working_days = "CREATE TABLE IF NOT EXISTS $table_working_days (
    id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    work_date DATE NOT NULL,
    is_holiday TINYINT(1) NOT NULL DEFAULT 0,
    holiday_name VARCHAR(100) NULL,
    created_by BIGINT(20) UNSIGNED NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY unique_work_date (work_date),
    KEY idx_is_holiday (is_holiday),
    KEY idx_created_by (created_by)
) $charset_collate;";

echo "Creating database tables...\n";

// Create tables
$result1 = dbDelta($sql_reports);
$result2 = dbDelta($sql_salary);
$result3 = dbDelta($sql_working_days);

echo "Reports table creation: " . ($result1 ? "Success" : "No changes needed") . "\n";
echo "Salary settings table creation: " . ($result2 ? "Success" : "No changes needed") . "\n";
echo "Working days table creation: " . ($result3 ? "Success" : "No changes needed") . "\n\n";

// Verify tables exist
echo "Verifying tables...\n";
$tables_to_check = array(
    'staff_reports' => $table_reports,
    'staff_salary_settings' => $table_salary_settings,
    'staff_working_days' => $table_working_days
);

$all_exist = true;
foreach ($tables_to_check as $name => $table) {
    $exists = $wpdb->get_var("SHOW TABLES LIKE '$table'");
    if ($exists) {
        echo "✓ Table '$table' exists\n";
    } else {
        echo "✗ Table '$table' missing\n";
        $all_exist = false;
    }
}

// Create custom user roles if they don't exist
echo "\nCreating user roles...\n";
if (!get_role('bassmah_staff')) {
    add_role('bassmah_staff', __('Staff Member', 'bassmah-staff-reports'), array(
        'read' => true,
        'bassmah_submit_reports' => true,
        'bassmah_view_own_reports' => true,
        'bassmah_view_own_salary' => true,
    ));
    echo "✓ Created bassmah_staff role\n";
} else {
    echo "✓ bassmah_staff role already exists\n";
}

if (!get_role('bassmah_manager')) {
    add_role('bassmah_manager', __('Report Manager', 'bassmah-staff-reports'), array(
        'read' => true,
        'bassmah_submit_reports' => true,
        'bassmah_view_all_reports' => true,
        'bassmah_view_own_reports' => true,
        'bassmah_comment_reports' => true,
        'bassmah_export_reports' => true,
        'bassmah_view_all_salary' => true,
        'bassmah_view_own_salary' => true,
        'bassmah_manage_salary_settings' => true,
        'bassmah_manage_working_days' => true,
        'bassmah_manage_staff' => true,
    ));
    echo "✓ Created bassmah_manager role\n";
} else {
    echo "✓ bassmah_manager role already exists\n";
}

// Add capabilities to Administrator role
$admin = get_role('administrator');
if ($admin) {
    $admin->add_cap('bassmah_submit_reports');
    $admin->add_cap('bassmah_view_all_reports');
    $admin->add_cap('bassmah_view_own_reports');
    $admin->add_cap('bassmah_comment_reports');
    $admin->add_cap('bassmah_export_reports');
    $admin->add_cap('bassmah_view_all_salary');
    $admin->add_cap('bassmah_view_own_salary');
    $admin->add_cap('bassmah_manage_salary_settings');
    $admin->add_cap('bassmah_manage_working_days');
    $admin->add_cap('bassmah_manage_staff');
    echo "✓ Added capabilities to administrator role\n";
}

// Set default options
echo "\nSetting default options...\n";
if (!get_option('bassmah_task_categories')) {
    $default_categories = array(
        'Development',
        'Design',
        'Content',
        'Marketing',
        'Support',
        'Administration'
    );
    update_option('bassmah_task_categories', $default_categories);
    echo "✓ Set default task categories\n";
}

if (!get_option('bassmah_task_statuses')) {
    $default_statuses = array(
        'completed' => __('Completed', 'bassmah-staff-reports'),
        'in_progress' => __('In Progress', 'bassmah-staff-reports'),
        'not_completed' => __('Not Completed', 'bassmah-staff-reports')
    );
    update_option('bassmah_task_statuses', $default_statuses);
    echo "✓ Set default task statuses\n";
}

// Flush rewrite rules
flush_rewrite_rules();

echo "\n=== Database Fix Complete ===\n";
echo "Status: " . ($all_exist ? "SUCCESS" : "PARTIAL") . "\n";
echo "\nYou can now access your dashboard.\n";
echo "Delete this file after use.\n";
?>
