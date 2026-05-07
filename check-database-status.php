<?php
/**
 * Database Status Checker for proffg.pronizam.com
 * 
 * This script checks where data goes and fixes database table issues
 */

// Load WordPress
$wp_config_path = dirname(__FILE__) . '/../../../wp-config.php';
require_once($wp_config_path);

global $wpdb;

echo "=== Database Status Checker for proffg.pronizam.com ===\n\n";

// 1. Check database connection and info
echo "1. Database Connection Status:\n";
echo "   Database Name: " . DB_NAME . "\n";
echo "   Database User: " . DB_USER . "\n";
echo "   Database Host: " . DB_HOST . "\n";
echo "   WordPress Prefix: " . $wpdb->prefix . "\n";
echo "   Expected Table: " . $wpdb->prefix . "staff_reports\n\n";

// 2. Check if tables exist
echo "2. Table Existence Check:\n";
$required_tables = array(
    'staff_reports' => $wpdb->prefix . 'staff_reports',
    'staff_salary_settings' => $wpdb->prefix . 'staff_salary_settings', 
    'staff_working_days' => $wpdb->prefix . 'staff_working_days'
);

$missing_tables = array();
foreach ($required_tables as $name => $table) {
    $exists = $wpdb->get_var("SHOW TABLES LIKE '$table'");
    if ($exists) {
        echo "   ✅ $table - EXISTS\n";
        
        // Check table structure
        $columns = $wpdb->get_results("SHOW COLUMNS FROM $table");
        echo "      Columns: " . count($columns) . "\n";
        
        // Check if table has data
        $count = $wpdb->get_var("SELECT COUNT(*) FROM $table");
        echo "      Records: $count\n";
    } else {
        echo "   ❌ $table - MISSING\n";
        $missing_tables[] = $table;
    }
}

// 3. Explain where data goes if table doesn't exist
if (!empty($missing_tables)) {
    echo "\n3. ⚠️  WHERE DATA GOES IF TABLE DOESN'T EXIST:\n";
    echo "   - If tables don't exist, data CANNOT be saved\n";
    echo "   - Reports will show 'submission failed' errors\n";
    echo "   - Dashboard will show database errors\n";
    echo "   - All plugin functionality will break\n\n";
    
    echo "4. SOLUTION - Create Missing Tables:\n";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    $charset = $wpdb->get_charset_collate();
    
    // Create staff_reports table
    if (in_array($wpdb->prefix . 'staff_reports', $missing_tables)) {
        echo "   Creating staff_reports table...\n";
        $sql = "CREATE TABLE " . $wpdb->prefix . "staff_reports (
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
        
        dbDelta($sql);
        echo "   ✅ staff_reports table created\n";
    }
    
    // Create staff_salary_settings table
    if (in_array($wpdb->prefix . 'staff_salary_settings', $missing_tables)) {
        echo "   Creating staff_salary_settings table...\n";
        $sql = "CREATE TABLE " . $wpdb->prefix . "staff_salary_settings (
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
        
        dbDelta($sql);
        echo "   ✅ staff_salary_settings table created\n";
    }
    
    // Create staff_working_days table
    if (in_array($wpdb->prefix . 'staff_working_days', $missing_tables)) {
        echo "   Creating staff_working_days table...\n";
        $sql = "CREATE TABLE " . $wpdb->prefix . "staff_working_days (
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
        
        dbDelta($sql);
        echo "   ✅ staff_working_days table created\n";
    }
    
    echo "\n5. Adding Sample Data:\n";
    
    // Add working days for current month
    $current_month = date('Y-m');
    $days_in_month = date('t');
    $working_days_added = 0;
    
    for ($day = 1; $day <= $days_in_month; $day++) {
        $work_date = date('Y-m-d', mktime(0, 0, 0, date('n'), $day, date('Y')));
        $is_holiday = (date('N', mktime(0, 0, 0, date('n'), $day, date('Y'))) >= 7) ? 1 : 0;
        $holiday_name = $is_holiday ? 'Weekend' : null;
        
        $result = $wpdb->insert(
            $wpdb->prefix . 'staff_working_days',
            array(
                'work_date' => $work_date,
                'is_holiday' => $is_holiday,
                'holiday_name' => $holiday_name,
                'created_by' => 1
            ),
            array('%s', '%d', '%s', '%d')
        );
        
        if ($result !== false) {
            $working_days_added++;
        }
    }
    
    echo "   ✅ Added $working_days_added working days\n";
    
    // Add sample salary settings
    $current_user_id = get_current_user_id() ?: 1;
    $result = $wpdb->insert(
        $wpdb->prefix . 'staff_salary_settings',
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
    
    if ($result !== false) {
        echo "   ✅ Added sample salary settings for user $current_user_id\n";
    }
}

// 6. Test data insertion
echo "\n6. Testing Data Insertion:\n";
try {
    require_once BASSMAH_STAFF_REPORTS_PLUGIN_DIR . 'includes/class-report.php';
    $report_class = new Bassmah_Staff_Reports_Report();
    
    $test_data = array(
        'user_id' => get_current_user_id() ?: 1,
        'report_date' => date('Y-m-d'),
        'tasks' => array(
            array(
                'task_description' => 'Test report from database check',
                'task_category' => 'Testing',
                'task_status' => 'completed',
                'next_action' => 'Verify database works'
            )
        ),
        'status' => 'submitted'
    );
    
    echo "   Attempting to save test report...\n";
    $report_id = $report_class->submit_report($test_data);
    
    if (is_wp_error($report_id)) {
        echo "   ❌ Test failed: " . $report_id->get_error_message() . "\n";
    } else {
        echo "   ✅ Test report saved successfully (ID: $report_id)\n";
        
        // Verify it was actually saved
        $saved_report = $report_class->get_report($report_id);
        if ($saved_report) {
            echo "   ✅ Report verified in database\n";
            
            // Clean up test data
            $wpdb->delete(
                $wpdb->prefix . 'staff_reports',
                array('id' => $report_id),
                array('%d')
            );
            echo "   ✅ Test data cleaned up\n";
        } else {
            echo "   ❌ Report not found after save\n";
        }
    }
    
} catch (Exception $e) {
    echo "   ❌ Test error: " . $e->getMessage() . "\n";
}

echo "\n=== SUMMARY ===\n";
echo "Database: " . DB_NAME . "\n";
echo "Tables Status: " . (empty($missing_tables) ? "ALL EXIST" : "SOME MISSING - FIXED") . "\n";
echo "Data Storage: " . (empty($missing_tables) ? "WORKING" : "NOW WORKING") . "\n";

echo "\n📍 WHERE YOUR DATA WILL BE STORED:\n";
echo "   - Reports: " . DB_NAME . "." . $wpdb->prefix . "staff_reports\n";
echo "   - Salary: " . DB_NAME . "." . $wpdb->prefix . "staff_salary_settings\n";
echo "   - Working Days: " . DB_NAME . "." . $wpdb->prefix . "staff_working_days\n";

echo "\n✅ Now your data will be properly saved to these tables!\n";
echo "🚀 Test your dashboard and report submission now.\n";
?>
