<?php
/**
 * Complete Database Initialization Script
 * 
 * This script will create all required database tables and set up the plugin properly
 * Run this once to fix all database issues
 */

// Security check
if (!defined('ABSPATH')) {
    // If not in WordPress context, try to load WordPress
    $wp_config_path = dirname(__FILE__) . '/../../../wp-config.php';
    if (file_exists($wp_config_path)) {
        require_once($wp_config_path);
    } else {
        die("WordPress not found. Please run this script from within WordPress or provide correct path.");
    }
}

// Include WordPress database upgrade functions
require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

global $wpdb;

echo "=== Bassmah Staff Reports - Complete Database Initialization ===\n\n";

// Define table names with correct prefix
$table_reports = $wpdb->prefix . 'staff_reports';
$table_salary_settings = $wpdb->prefix . 'staff_salary_settings';
$table_working_days = $wpdb->prefix . 'staff_working_days';

// Set charset
$charset_collate = $wpdb->get_charset_collate();

echo "Current database prefix: " . $wpdb->prefix . "\n";
echo "Creating tables...\n\n";

// Drop existing tables if they exist (to start fresh)
echo "Dropping existing tables (if any)...\n";
$wpdb->query("DROP TABLE IF EXISTS $table_reports");
$wpdb->query("DROP TABLE IF EXISTS $table_salary_settings");
$wpdb->query("DROP TABLE IF EXISTS $table_working_days");

// Create staff_reports table
echo "Creating staff_reports table...\n";
$sql_reports = "CREATE TABLE $table_reports (
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

dbDelta($sql_reports);

// Create staff_salary_settings table
echo "Creating staff_salary_settings table...\n";
$sql_salary = "CREATE TABLE $table_salary_settings (
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

dbDelta($sql_salary);

// Create staff_working_days table
echo "Creating staff_working_days table...\n";
$sql_working_days = "CREATE TABLE $table_working_days (
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

dbDelta($sql_working_days);

// Verify tables exist
echo "\nVerifying table creation...\n";
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
        
        // Check table structure
        $columns = $wpdb->get_results("SHOW COLUMNS FROM $table");
        echo "  - Columns: " . count($columns) . "\n";
    } else {
        echo "✗ Table '$table' missing\n";
        $all_exist = false;
    }
}

// Insert sample data if tables are empty
if ($all_exist) {
    echo "\nInserting sample data...\n";
    
    // Check if working days table is empty
    $working_days_count = $wpdb->get_var("SELECT COUNT(*) FROM $table_working_days");
    if ($working_days_count == 0) {
        echo "Adding sample working days for current month...\n";
        $current_month = date('Y-m');
        $days_in_month = date('t');
        
        for ($day = 1; $day <= $days_in_month; $day++) {
            $work_date = date('Y-m-d', mktime(0, 0, 0, date('n'), $day, date('Y')));
            $is_holiday = (date('N', mktime(0, 0, 0, date('n'), $day, date('Y'))) >= 7) ? 1 : 0; // Weekends
            
            $wpdb->insert(
                $table_working_days,
                array(
                    'work_date' => $work_date,
                    'is_holiday' => $is_holiday,
                    'holiday_name' => $is_holiday ? 'Weekend' : null,
                    'created_by' => get_current_user_id() ?: 1
                ),
                array('%s', '%d', '%s', '%d')
            );
        }
        echo "✓ Added $days_in_month working days\n";
    }
    
    // Add sample salary settings for current user
    $current_user_id = get_current_user_id();
    if ($current_user_id) {
        $salary_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_salary_settings WHERE user_id = %d",
            $current_user_id
        ));
        
        if ($salary_count == 0) {
            echo "Adding sample salary settings for current user...\n";
            $wpdb->insert(
                $table_salary_settings,
                array(
                    'user_id' => $current_user_id,
                    'monthly_salary' => 3500.00,
                    'working_days_per_month' => 22,
                    'currency' => 'CAD',
                    'effective_from' => date('Y-m-01'),
                    'created_by' => $current_user_id
                ),
                array('%d', '%f', '%d', '%s', '%s', '%d')
            );
            echo "✓ Added sample salary settings\n";
        }
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
    $capabilities = array(
        'bassmah_submit_reports',
        'bassmah_view_all_reports',
        'bassmah_view_own_reports',
        'bassmah_comment_reports',
        'bassmah_export_reports',
        'bassmah_view_all_salary',
        'bassmah_view_own_salary',
        'bassmah_manage_salary_settings',
        'bassmah_manage_working_days',
        'bassmah_manage_staff'
    );
    
    foreach ($capabilities as $cap) {
        $admin->add_cap($cap);
    }
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

echo "\n=== Database Initialization Complete ===\n";
echo "Status: " . ($all_exist ? "SUCCESS" : "PARTIAL") . "\n";
echo "\nNext steps:\n";
echo "1. Delete this file for security\n";
echo "2. Test report submission\n";
echo "3. Check dashboard functionality\n";
echo "4. Verify all features are working\n";

if (!$all_exist) {
    echo "\n⚠️  Some tables failed to create. Check the error logs for details.\n";
}
?>
