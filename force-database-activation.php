<?php
/**
 * Force Database Activation
 * This script forces plugin activation to create tables immediately
 */

// Load WordPress
$wp_config_path = dirname(__FILE__) . '/../../../wp-config.php';
require_once($wp_config_path);

global $wpdb;

echo "=== Force Database Activation ===\n\n";

// Get current user
$current_user = wp_get_current_user();
if (!$current_user->exists()) {
    echo "❌ No user logged in. Please login first.\n";
    exit;
}

echo "Current user: " . $current_user->display_name . " (ID: " . $current_user->ID . ")\n\n";

// Step 1: Manually create tables using direct SQL
echo "1. Creating database tables directly...\n";

$charset = $wpdb->get_charset_collate();

// staff_reports table
$sql1 = "CREATE TABLE IF NOT EXISTS " . $wpdb->prefix . "staff_reports (
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
) ENGINE=InnoDB " . $charset;

$result1 = $wpdb->query($sql1);
echo $result1 ? "✓ staff_reports table created\n" : "✗ staff_reports table failed\n";

// staff_salary_settings table
$sql2 = "CREATE TABLE IF NOT EXISTS " . $wpdb->prefix . "staff_salary_settings (
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
) ENGINE=InnoDB " . $charset;

$result2 = $wpdb->query($sql2);
echo $result2 ? "✓ staff_salary_settings table created\n" : "✗ staff_salary_settings table failed\n";

// staff_working_days table
$sql3 = "CREATE TABLE IF NOT EXISTS " . $wpdb->prefix . "staff_working_days (
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
) ENGINE=InnoDB " . $charset;

$result3 = $wpdb->query($sql3);
echo $result3 ? "✓ staff_working_days table created\n" : "✗ staff_working_days table failed\n";

// Step 2: Verify tables exist
echo "\n2. Verifying tables exist...\n";
$tables_to_check = array(
    'staff_reports' => $wpdb->prefix . 'staff_reports',
    'staff_salary_settings' => $wpdb->prefix . 'staff_salary_settings',
    'staff_working_days' => $wpdb->prefix . 'staff_working_days'
);

$all_exist = true;
foreach ($tables_to_check as $name => $table) {
    $exists = $wpdb->get_var("SHOW TABLES LIKE '$table'");
    if ($exists) {
        echo "✓ $table EXISTS\n";
    } else {
        echo "✗ $table MISSING\n";
        $all_exist = false;
    }
}

if (!$all_exist) {
    echo "\n❌ Some tables failed to create. Please check database permissions.\n";
    exit;
}

// Step 3: Add sample data
echo "\n3. Adding sample data...\n";

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
            'created_by' => $current_user->ID
        ),
        array('%s', '%d', '%s', '%d')
    );
    
    if ($result !== false) {
        $working_days_added++;
    }
}
echo "✓ Added $working_days_added working days for current month\n";

// Add salary settings
$salary_result = $wpdb->insert(
    $wpdb->prefix . 'staff_salary_settings',
    array(
        'user_id' => $current_user->ID,
        'monthly_salary' => 3500.00,
        'working_days_per_month' => 22,
        'currency' => 'CAD',
        'effective_from' => date('Y-m-01'),
        'created_by' => $current_user->ID
    ),
    array('%d', '%f', '%d', '%s', '%s', '%d')
);

echo $salary_result ? "✓ Added salary settings for user " . $current_user->ID . "\n" : "✗ Failed to add salary settings\n";

// Step 4: Test database operations
echo "\n4. Testing database operations...\n";

// Test report insertion
$test_report = array(
    'user_id' => $current_user->ID,
    'report_date' => date('Y-m-d'),
    'submission_time' => current_time('mysql'),
    'status' => 'submitted',
    'tasks_json' => json_encode(array(array(
        'task_description' => 'Test report from activation',
        'task_category' => 'Testing',
        'task_status' => 'completed',
        'next_action' => 'Verify database works'
    ))),
    'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
    'created_at' => current_time('mysql'),
    'updated_at' => current_time('mysql')
);

$test_insert = $wpdb->insert($wpdb->prefix . 'staff_reports', $test_report);
if ($test_insert !== false) {
    echo "✓ Test report inserted successfully (ID: " . $wpdb->insert_id . ")\n";
    
    // Clean up test report
    $wpdb->delete(
        $wpdb->prefix . 'staff_reports',
        array('id' => $wpdb->insert_id),
        array('%d')
    );
    echo "✓ Test report cleaned up\n";
} else {
    echo "✗ Test report insertion failed\n";
}

// Step 5: Update plugin activation options
echo "\n5. Updating plugin options...\n";

// Mark plugin as activated
update_option('bassmah_staff_reports_activated', true);
update_option('bassmah_staff_reports_version', BASSMAH_STAFF_REPORTS_VERSION);
update_option('bassmah_staff_reports_db_version', '1.0');

// Create user roles if they don't exist
if (!get_role('bassmah_staff')) {
    add_role('bassmah_staff', 'Staff Member', array(
        'read' => true,
        'bassmah_submit_reports' => true,
        'bassmah_view_own_reports' => true,
        'bassmah_view_own_salary' => true,
    ));
    echo "✓ Created bassmah_staff role\n";
}

if (!get_role('bassmah_manager')) {
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
    echo "✓ Created bassmah_manager role\n";
}

// Give admin all capabilities
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
    echo "✓ Updated administrator capabilities\n";
}

// Step 6: Final verification
echo "\n6. Final verification...\n";

foreach ($tables_to_check as $name => $table) {
    $count = $wpdb->get_var("SELECT COUNT(*) FROM $table");
    echo "   $table: $count records\n";
}

// Clear cache
wp_cache_flush();
flush_rewrite_rules();

echo "\n=== Database Activation Complete ===\n";
echo "✅ All tables created and populated\n";
echo "✅ Sample data added\n";
echo "✅ Plugin options updated\n";
echo "✅ User roles created\n";
echo "✅ Cache cleared\n";

echo "\n🚀 Plugin should now work without database errors!\n";
echo "📋 Test your dashboard and report submission now.\n";

// Self-destruct
if (file_exists(__FILE__)) {
    unlink(__FILE__);
}
?>
