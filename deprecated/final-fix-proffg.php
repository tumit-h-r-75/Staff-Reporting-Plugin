<?php
/**
 * Final Fix Script for proffg.pronizam.com
 * Run this to fix all remaining plugin issues
 */

// Load WordPress
$wp_config_path = dirname(__FILE__) . '/../../../wp-config.php';
require_once($wp_config_path);

global $wpdb;

echo "=== Final Fix for proffg.pronizam.com ===\n";

// 1. Fix database tables
echo "1. Fixing database tables...\n";
$tables = array(
    'staff_reports' => $wpdb->prefix . 'staff_reports',
    'staff_salary_settings' => $wpdb->prefix . 'staff_salary_settings',
    'staff_working_days' => $wpdb->prefix . 'staff_working_days'
);

foreach ($tables as $name => $table) {
    $wpdb->query("DROP TABLE IF EXISTS $table");
    echo "✓ Dropped $table\n";
}

// Recreate tables
require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

$charset = $wpdb->get_charset_collate();

// staff_reports
$sql1 = "CREATE TABLE {$tables['staff_reports']} (
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
) $charset;";

// staff_salary_settings  
$sql2 = "CREATE TABLE {$tables['staff_salary_settings']} (
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
) $charset;";

// staff_working_days
$sql3 = "CREATE TABLE {$tables['staff_working_days']} (
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
) $charset;";

dbDelta($sql1);
dbDelta($sql2);
dbDelta($sql3);

echo "✓ Recreated all tables\n";

// 2. Fix user roles
echo "2. Setting up user roles...\n";
add_role('bassmah_staff', 'Staff Member', array(
    'read' => true,
    'bassmah_submit_reports' => true,
    'bassmah_view_own_reports' => true,
    'bassmah_view_own_salary' => true,
));

add_role('bassmah_manager', 'Report Manager', array(
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

$admin = get_role('administrator');
if ($admin) {
    $caps = array('bassmah_submit_reports', 'bassmah_view_all_reports', 'bassmah_view_own_reports', 'bassmah_comment_reports', 'bassmah_export_reports', 'bassmah_view_all_salary', 'bassmah_view_own_salary', 'bassmah_manage_salary_settings', 'bassmah_manage_working_days', 'bassmah_manage_staff');
    foreach ($caps as $cap) $admin->add_cap($cap);
}

echo "✓ User roles fixed\n";

// 3. Clear all caches
echo "3. Clearing caches...\n";
wp_cache_flush();
$wpdb->query("DELETE FROM {$wpdb->prefix}options WHERE option_name LIKE '%_transient_%'");
wp_clear_auth_cookie();
echo "✓ Caches cleared\n";

// 4. Set site URL
echo "4. Setting site URL...\n";
update_option('siteurl', 'https://proffg.pronizam.com');
update_option('home', 'https://proffg.pronizam.com');
echo "✓ Site URL set\n";

// 5. Test report submission
echo "5. Testing report submission...\n";
require_once BASSMAH_STAFF_REPORTS_PLUGIN_DIR . 'includes/class-report.php';
$report = new Bassmah_Staff_Reports_Report();

$test_data = array(
    'user_id' => get_current_user_id() ?: 1,
    'report_date' => date('Y-m-d'),
    'tasks' => array(array('task_description' => 'Test report', 'task_category' => 'Testing', 'task_status' => 'completed')),
    'status' => 'submitted'
);

$result = $report->submit_report($test_data);
if (is_wp_error($result)) {
    echo "✗ Report test failed: " . $result->get_error_message() . "\n";
} else {
    echo "✓ Report test passed (ID: $result)\n";
    $wpdb->delete($tables['staff_reports'], array('id' => $result), array('%d'));
}

echo "\n=== Fix Complete! ===\n";
echo "Now test your dashboard and report submission.\n";
?>
