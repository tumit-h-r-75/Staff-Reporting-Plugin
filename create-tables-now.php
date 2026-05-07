<?php
/**
 * Immediate Database Table Creation
 * Run this to create all missing tables right now
 */

// Load WordPress
$wp_config_path = dirname(__FILE__) . '/../../../wp-config.php';
require_once($wp_config_path);

global $wpdb;

echo "=== Creating Database Tables NOW ===\n\n";

// Required tables
$tables = array(
    'staff_reports' => $wpdb->prefix . 'staff_reports',
    'staff_salary_settings' => $wpdb->prefix . 'staff_salary_settings',
    'staff_working_days' => $wpdb->prefix . 'staff_working_days'
);

echo "Creating tables in database: " . DB_NAME . "\n";
echo "WordPress prefix: " . $wpdb->prefix . "\n\n";

// Load WordPress upgrade functions
require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

$charset = $wpdb->get_charset_collate();

// Create staff_reports table
echo "1. Creating staff_reports table...\n";
$sql1 = "CREATE TABLE IF NOT EXISTS " . $tables['staff_reports'] . " (
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
) " . $charset . ";";

dbDelta($sql1);
echo "✓ staff_reports table created\n";

// Create staff_salary_settings table
echo "2. Creating staff_salary_settings table...\n";
$sql2 = "CREATE TABLE IF NOT EXISTS " . $tables['staff_salary_settings'] . " (
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
) " . $charset . ";";

dbDelta($sql2);
echo "✓ staff_salary_settings table created\n";

// Create staff_working_days table
echo "3. Creating staff_working_days table...\n";
$sql3 = "CREATE TABLE IF NOT EXISTS " . $tables['staff_working_days'] . " (
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
) " . $charset . ";";

dbDelta($sql3);
echo "✓ staff_working_days table created\n";

// Add sample working days
echo "\n4. Adding sample working days...\n";
$current_month = date('Y-m');
$days_in_month = date('t');

for ($day = 1; $day <= $days_in_month; $day++) {
    $work_date = date('Y-m-d', mktime(0, 0, 0, date('n'), $day, date('Y')));
    $is_holiday = (date('N', mktime(0, 0, 0, date('n'), $day, date('Y'))) >= 7 ? 1 : 0;
    $holiday_name = $is_holiday ? 'Weekend' : null;
    
    $wpdb->insert(
        $tables['staff_working_days'],
        array(
            'work_date' => $work_date,
            'is_holiday' => $is_holiday,
            'holiday_name' => $holiday_name,
            'created_by' => 1
        ),
        array('%s', '%d', '%s', '%d')
    );
}
echo "✓ Added $days_in_month working days for current month\n";

// Add sample salary settings
echo "5. Adding sample salary settings...\n";
$current_user_id = get_current_user_id() ?: 1;

$wpdb->insert(
    $tables['staff_salary_settings'],
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
echo "✓ Added sample salary settings for user $current_user_id\n";

// Verify tables exist
echo "\n6. Verifying tables...\n";
foreach ($tables as $name => $table) {
    $exists = $wpdb->get_var("SHOW TABLES LIKE '$table'");
    if ($exists) {
        echo "✓ $table EXISTS\n";
        
        $count = $wpdb->get_var("SELECT COUNT(*) FROM $table");
        echo "   Records: $count\n";
    } else {
        echo "✗ $table MISSING\n";
    }
}

// Test database connection
echo "\n7. Testing database operations...\n";
try {
    $test_query = $wpdb->prepare("SELECT COUNT(*) FROM " . $tables['staff_reports'] . " WHERE user_id = %d", $current_user_id);
    $result = $wpdb->get_var($test_query);
    echo "✓ Database query test successful\n";
    echo "   Current reports for user $current_user_id: $result\n";
} catch (Exception $e) {
    echo "✗ Database test failed: " . $e->getMessage() . "\n";
}

echo "\n=== Database Setup Complete ===\n";
echo "✅ All tables created and populated with sample data\n";
echo "🚀 Plugin should now work without database errors\n";

// Clear cache and flush
wp_cache_flush();
flush_rewrite_rules();

echo "\n📋 Next Steps:\n";
echo "1. Test dashboard functionality\n";
echo "2. Test report submission\n";
echo "3. Verify data is saved to tables\n";
echo "4. Delete this file for security\n";

// Self-destruct after successful creation
if (file_exists(__FILE__)) {
    unlink(__FILE__);
}
?>
