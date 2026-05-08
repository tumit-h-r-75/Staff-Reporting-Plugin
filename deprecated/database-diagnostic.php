<?php
/**
 * Database Diagnostic Script
 * Checks what's happening with database tables
 */

// Load WordPress
$wp_config_path = dirname(__FILE__) . '/../../../wp-config.php';
require_once($wp_config_path);

global $wpdb;

echo "=== Database Diagnostic ===\n\n";

// Check WordPress connection
echo "1. WordPress Database Connection:\n";
echo "   Database: " . DB_NAME . "\n";
echo "   User: " . DB_USER . "\n";
echo "   Host: " . DB_HOST . "\n";
echo "   Prefix: " . $wpdb->prefix . "\n";

// Check if tables exist
echo "\n2. Table Existence Check:\n";
$tables = array(
    'staff_reports' => $wpdb->prefix . 'staff_reports',
    'staff_salary_settings' => $wpdb->prefix . 'staff_salary_settings',
    'staff_working_days' => $wpdb->prefix . 'staff_working_days'
);

$missing_tables = array();
foreach ($tables as $name => $table) {
    $exists = $wpdb->get_var("SHOW TABLES LIKE '$table'");
    if ($exists) {
        echo "   ✅ $name: EXISTS\n";
        
        // Check table structure
        $columns = $wpdb->get_results("SHOW COLUMNS FROM $table");
        echo "      Columns: " . count($columns) . "\n";
        
        // Check if table has data
        $count = $wpdb->get_var("SELECT COUNT(*) FROM $table");
        echo "      Records: $count\n";
    } else {
        echo "   ❌ $name: MISSING\n";
        $missing_tables[] = $table;
    }
}

// If tables are missing, try to create them
if (!empty($missing_tables)) {
    echo "\n3. Attempting to Create Missing Tables:\n";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    $charset = $wpdb->get_charset_collate();
    
    foreach ($missing_tables as $table) {
        echo "   Creating: $table\n";
        
        if (strpos($table, 'staff_reports') !== false) {
            $sql = "CREATE TABLE $table (
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
            ) $charset";
        } elseif (strpos($table, 'staff_salary_settings') !== false) {
            $sql = "CREATE TABLE $table (
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
            ) $charset";
        } elseif (strpos($table, 'staff_working_days') !== false) {
            $sql = "CREATE TABLE $table (
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
            ) $charset";
        }
        
        $result = $wpdb->query($sql);
        echo $result ? "      ✅ SUCCESS\n" : "      ❌ FAILED\n";
    }
    
    echo "\n4. Verification After Creation:\n";
    foreach ($tables as $name => $table) {
        $exists = $wpdb->get_var("SHOW TABLES LIKE '$table'");
        echo "   $name: " . ($exists ? "EXISTS" : "MISSING") . "\n";
    }
}

// Check plugin activation status
echo "\n5. Plugin Activation Status:\n";
$activated = get_option('bassmah_staff_reports_activated');
echo "   Plugin Activated: " . ($activated ? "YES" : "NO") . "\n";

$db_version = get_option('bassmah_staff_reports_db_version');
echo "   DB Version: " . ($db_version ?: "NOT SET") . "\n";

// Test database permissions
echo "\n6. Database Permissions Test:\n";
try {
    $test_table = $wpdb->prefix . 'staff_reports';
    $test_query = $wpdb->prepare("SELECT COUNT(*) FROM $test_table");
    $result = $wpdb->get_var($test_query);
    echo "   Query Test: " . ($result !== null ? "SUCCESS" : "FAILED") . "\n";
} catch (Exception $e) {
    echo "   Query Test: FAILED - " . $e->getMessage() . "\n";
}

// Check if user has proper capabilities
echo "\n7. User Capabilities:\n";
if (is_user_logged_in()) {
    $user = wp_get_current_user();
    echo "   User: " . $user->display_name . "\n";
    echo "   Can Submit Reports: " . (current_user_can('bassmah_submit_reports') ? "YES" : "NO") . "\n";
    echo "   Can View Own Reports: " . (current_user_can('bassmah_view_own_reports') ? "YES" : "NO") . "\n";
    echo "   Can View Own Salary: " . (current_user_can('bassmah_view_own_salary') ? "YES" : "NO") . "\n";
} else {
    echo "   User: NOT LOGGED IN\n";
}

// Recommendations
echo "\n=== Recommendations ===\n";
if (!empty($missing_tables)) {
    echo "🔧 IMMEDIATE ACTION NEEDED:\n";
    echo "1. Run: https://proffg.pronizam.com/wp-content/plugins/Staff%20Reporting%20Plugin/force-database-activation.php\n";
    echo "2. Then test report submission\n";
    echo "3. Check dashboard functionality\n";
} else {
    echo "✅ All tables exist. Try these fixes:\n";
    echo "1. Clear browser cache completely\n";
    echo "2. Deactivate and reactivate plugin\n";
    echo "3. Check WordPress debug log\n";
    echo "4. Test with different user role\n";
}

echo "\n🚀 Run this diagnostic to get real-time status!\n";
?>
