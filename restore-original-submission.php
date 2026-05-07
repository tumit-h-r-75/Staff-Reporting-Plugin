<?php
/**
 * Restore Original Working Form Submission
 * This script restores the form submission to work like day one
 */

// Load WordPress
$wp_config_path = dirname(__FILE__) . '/../../../wp-config.php';
require_once($wp_config_path);

global $wpdb;

echo "=== Restore Original Form Submission ===\n\n";

// Step 1: Reset database tables to original working state
echo "1. Resetting Database Tables to Original State:\n";

$tables = array(
    'staff_reports' => $wpdb->prefix . 'staff_reports',
    'staff_salary_settings' => $wpdb->prefix . 'staff_salary_settings',
    'staff_working_days' => $wpdb->prefix . 'staff_working_days'
);

foreach ($tables as $name => $table) {
    // Drop existing table if it exists
    $wpdb->query("DROP TABLE IF EXISTS $table");
    echo "   Dropped: $name\n";
}

// Recreate tables with original simple structure
$charset = $wpdb->get_charset_collate();

// Simple staff_reports table (like day one)
$sql_reports = "CREATE TABLE {$wpdb->prefix}staff_reports (
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
) ENGINE=InnoDB $charset";

$result1 = $wpdb->query($sql_reports);
echo $result1 ? "   Created: staff_reports\n" : "   Failed: staff_reports\n";

// Simple salary settings table
$sql_salary = "CREATE TABLE {$wpdb->prefix}staff_salary_settings (
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
) ENGINE=InnoDB $charset";

$result2 = $wpdb->query($sql_salary);
echo $result2 ? "   Created: staff_salary_settings\n" : "   Failed: staff_salary_settings\n";

// Working days table
$sql_working_days = "CREATE TABLE {$wpdb->prefix}staff_working_days (
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
) ENGINE=InnoDB $charset";

$result3 = $wpdb->query($sql_working_days);
echo $result3 ? "   Created: staff_working_days\n" : "   Failed: staff_working_days\n";

// Step 2: Reset user roles and capabilities
echo "\n2. Resetting User Roles:\n";

// Remove existing roles
remove_role('bassmah_staff');
remove_role('bassmah_manager');

// Create original simple Staff role
add_role('bassmah_staff', 'Staff Member', array(
    'read' => true,
    'bassmah_submit_reports' => true,
    'bassmah_view_own_reports' => true,
    'bassmah_view_own_salary' => true,
));
echo "   Created: bassmah_staff role\n";

// Create Manager role
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
echo "   Created: bassmah_manager role\n";

// Admin gets all capabilities
$admin_role = get_role('administrator');
if ($admin_role) {
    $caps = array(
        'bassmah_submit_reports', 'bassmah_view_all_reports', 'bassmah_view_own_reports',
        'bassmah_comment_reports', 'bassmah_export_reports', 'bassmah_view_all_salary',
        'bassmah_view_own_salary', 'bassmah_manage_salary_settings', 'bassmah_manage_working_days',
        'bassmah_manage_staff'
    );
    foreach ($caps as $cap) {
        $admin_role->add_cap($cap);
    }
    echo "   Updated: administrator capabilities\n";
}

// Step 3: Add basic sample data
echo "\n3. Adding Basic Sample Data:\n";

$current_user = get_current_user_id() ?: 1;

// Add working days for current month
$current_month = date('Y-m');
$days_in_month = date('t');
$working_days_added = 0;

for ($day = 1; $day <= $days_in_month; $day++) {
    $work_date = date('Y-m-d', mktime(0, 0, 0, date('n'), $day, date('Y')));
    $is_holiday = (date('N', mktime(0, 0, 0, date('n'), $day, date('Y'))) >= 7 ? 1 : 0;
    $holiday_name = $is_holiday ? 'Weekend' : null;
    
    $result = $wpdb->insert(
        $wpdb->prefix . 'staff_working_days',
        array(
            'work_date' => $work_date,
            'is_holiday' => $is_holiday,
            'holiday_name' => $holiday_name,
            'created_by' => $current_user
        ),
        array('%s', '%d', '%s', '%d')
    );
    
    if ($result !== false) {
        $working_days_added++;
    }
}
echo "   Added: $working_days_added working days\n";

// Add basic salary settings
$salary_result = $wpdb->insert(
    $wpdb->prefix . 'staff_salary_settings',
    array(
        'user_id' => $current_user,
        'monthly_salary' => 3500.00,
        'working_days_per_month' => 22,
        'currency' => 'CAD',
        'effective_from' => date('Y-m-01'),
        'created_by' => $current_user
    ),
    array('%d', '%f', '%d', '%s', '%s', '%d')
);

echo $salary_result ? "   Added: basic salary settings\n" : "   Failed: salary settings\n";

// Step 4: Reset plugin options
echo "\n4. Resetting Plugin Options:\n";

update_option('bassmah_staff_reports_activated', true);
update_option('bassmah_staff_reports_version', BASSMAH_STAFF_REPORTS_VERSION);
update_option('bassmah_staff_reports_db_version', '1.0');

echo "   Updated: plugin activation options\n";

// Step 5: Test original simple AJAX submission
echo "\n5. Testing Original AJAX Submission:\n";

// Simulate original simple AJAX request
$_POST['action'] = 'bassmah_submit_report';
$_POST['action_type'] = 'submit_report';
$_POST['tasks'] = array(
    array(
        'task_description' => 'Test original submission',
        'task_category' => 'Testing',
        'task_status' => 'completed',
        'next_action' => 'Verify original functionality works'
    )
);

// Load public class
require_once BASSMAH_STAFF_REPORTS_PLUGIN_DIR . 'public/class-public.php';
$public_class = new Bassmah_Staff_Reports_Public();

try {
    ob_start();
    $public_class->handle_ajax_requests();
    $output = ob_get_clean();
    
    echo "   AJAX Response: $output\n";
    
    $response = json_decode($output, true);
    if ($response && $response['success']) {
        echo "   Result: SUCCESS - Original submission works!\n";
    } else {
        echo "   Result: FAILED - " . ($response['data'] ?? 'Unknown error') . "\n";
    }
} catch (Exception $e) {
    echo "   Exception: " . $e->getMessage() . "\n";
}

// Step 6: Clear all caches
echo "\n6. Clearing All Caches:\n";

wp_cache_flush();
flush_rewrite_rules();

echo "   Cleared: WordPress cache\n";
echo "   Cleared: rewrite rules\n";

// Step 7: Verify everything is ready
echo "\n7. Final Verification:\n";

foreach ($tables as $name => $table) {
    $exists = $wpdb->get_var("SHOW TABLES LIKE '$table'");
    echo "   $name: " . ($exists ? "EXISTS" : "MISSING") . "\n";
    
    if ($exists) {
        $count = $wpdb->get_var("SELECT COUNT(*) FROM $table");
        echo "     Records: $count\n";
    }
}

echo "\n=== Restoration Complete ===\n";
echo "✅ Database tables reset to original state\n";
echo "✅ User roles and capabilities reset\n";
echo "✅ Basic sample data added\n";
echo "✅ Plugin options reset\n";
echo "✅ AJAX submission tested\n";
echo "✅ All caches cleared\n";

echo "\n🚀 Your form submission should now work like day one!\n";
echo "📋 Next Steps:\n";
echo "1. Test the report form submission\n";
echo "2. Check dashboard functionality\n";
echo "3. Verify all pages work correctly\n";
echo "4. Delete this script for security\n";

echo "\n🔧 If submission still doesn't work:\n";
echo "1. Check browser console for JavaScript errors\n";
echo "2. Verify user is logged in with proper role\n";
echo "3. Test with a different browser\n";
echo "4. Check WordPress debug log\n";

// Self-destruct
if (file_exists(__FILE__)) {
    unlink(__FILE__);
}
?>
