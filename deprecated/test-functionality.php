<?php
/**
 * Functionality Test Script
 * 
 * This script tests all plugin functionality to ensure everything works
 */

// Security check
if (!defined('ABSPATH')) {
    $wp_config_path = dirname(__FILE__) . '/../../../wp-config.php';
    if (file_exists($wp_config_path)) {
        require_once($wp_config_path);
    } else {
        die("WordPress not found. Please run this script from within WordPress.");
    }
}

global $wpdb;

echo "=== Bassmah Staff Reports - Functionality Test ===\n\n";

// Test 1: Database Tables
echo "1. Testing Database Tables...\n";
$tables_to_test = array(
    'staff_reports' => $wpdb->prefix . 'staff_reports',
    'staff_salary_settings' => $wpdb->prefix . 'staff_salary_settings',
    'staff_working_days' => $wpdb->prefix . 'staff_working_days'
);

$all_tables_exist = true;
foreach ($tables_to_test as $name => $table) {
    $exists = $wpdb->get_var("SHOW TABLES LIKE '$table'");
    if ($exists) {
        echo "✓ Table '$table' exists\n";
        
        // Test table structure
        $columns = $wpdb->get_results("SHOW COLUMNS FROM $table");
        echo "  - Columns: " . count($columns) . "\n";
        
        // Test if table is accessible
        $count = $wpdb->get_var("SELECT COUNT(*) FROM $table");
        echo "  - Records: " . $count . "\n";
    } else {
        echo "✗ Table '$table' missing\n";
        $all_tables_exist = false;
    }
}

if (!$all_tables_exist) {
    echo "\n⚠️  Some tables are missing. Please run init-database-complete.php first.\n";
    exit(1);
}

// Test 2: Class Loading
echo "\n2. Testing Class Loading...\n";
try {
    require_once BASSMAH_STAFF_REPORTS_PLUGIN_DIR . 'includes/class-report.php';
    require_once BASSMAH_STAFF_REPORTS_PLUGIN_DIR . 'includes/class-salary-calculator.php';
    
    $report_class = new Bassmah_Staff_Reports_Report();
    echo "✓ Bassmah_Staff_Reports_Report class loaded\n";
    
    $salary_calculator = new Bassmah_Staff_Reports_Salary_Calculator();
    echo "✓ Bassmah_Staff_Reports_Salary_Calculator class loaded\n";
    
} catch (Exception $e) {
    echo "✗ Class loading error: " . $e->getMessage() . "\n";
}

// Test 3: User Roles
echo "\n3. Testing User Roles...\n";
$roles_to_test = array('bassmah_staff', 'bassmah_manager');
foreach ($roles_to_test as $role) {
    $role_obj = get_role($role);
    if ($role_obj) {
        echo "✓ Role '$role' exists\n";
    } else {
        echo "✗ Role '$role' missing\n";
    }
}

// Test 4: Current User Capabilities
echo "\n4. Testing Current User Capabilities...\n";
$current_user = wp_get_current_user();
if ($current_user->exists()) {
    echo "✓ Current user: " . $current_user->display_name . " (ID: " . $current_user->ID . ")\n";
    
    $capabilities_to_test = array(
        'bassmah_submit_reports',
        'bassmah_view_own_reports',
        'bassmah_view_own_salary',
        'bassmah_view_all_reports',
        'bassmah_comment_reports'
    );
    
    foreach ($capabilities_to_test as $cap) {
        if (current_user_can($cap)) {
            echo "✓ Has capability: $cap\n";
        } else {
            echo "✗ Missing capability: $cap\n";
        }
    }
} else {
    echo "✗ No user logged in\n";
}

// Test 5: Report Submission
echo "\n5. Testing Report Submission...\n";
if ($current_user->exists()) {
    try {
        $report_class = new Bassmah_Staff_Reports_Report();
        
        // Test data
        $test_report_data = array(
            'user_id' => $current_user->ID,
            'report_date' => date('Y-m-d'),
            'tasks' => array(
                array(
                    'task_description' => 'Test task from functionality test',
                    'task_category' => 'Development',
                    'task_status' => 'completed',
                    'next_action' => 'Continue with next phase'
                )
            ),
            'status' => 'submitted'
        );
        
        // Check if report already exists for today
        $existing = $report_class->get_report_by_date($current_user->ID, date('Y-m-d'));
        if ($existing) {
            echo "ℹ️  Report already exists for today. Testing update instead.\n";
            
            // Test update
            $update_result = $report_class->update_report($existing->id, array(
                'manager_comment' => 'Test comment from functionality test'
            ));
            
            if ($update_result) {
                echo "✓ Report update successful\n";
            } else {
                echo "✗ Report update failed\n";
            }
        } else {
            // Test insert
            $report_id = $report_class->submit_report($test_report_data);
            
            if (is_wp_error($report_id)) {
                echo "✗ Report submission failed: " . $report_id->get_error_message() . "\n";
            } else {
                echo "✓ Report submission successful (ID: $report_id)\n";
                
                // Verify the report was saved
                $saved_report = $report_class->get_report($report_id);
                if ($saved_report) {
                    echo "✓ Report verified in database\n";
                    
                    // Clean up - delete test report
                    $wpdb->delete(
                        $wpdb->prefix . 'staff_reports',
                        array('id' => $report_id),
                        array('%d')
                    );
                    echo "✓ Test report cleaned up\n";
                } else {
                    echo "✗ Report not found in database\n";
                }
            }
        }
        
    } catch (Exception $e) {
        echo "✗ Report submission test error: " . $e->getMessage() . "\n";
    }
} else {
    echo "ℹ️  Skipping report submission test - no user logged in\n";
}

// Test 6: Salary Calculator
echo "\n6. Testing Salary Calculator...\n";
if ($current_user->exists()) {
    try {
        $salary_calculator = new Bassmah_Staff_Reports_Salary_Calculator();
        
        // Test dashboard stats
        $dashboard_stats = $salary_calculator->get_dashboard_stats($current_user->ID);
        if ($dashboard_stats) {
            echo "✓ Dashboard stats retrieved\n";
        } else {
            echo "✗ Dashboard stats failed\n";
        }
        
        // Test salary history
        $salary_history = $salary_calculator->get_salary_history($current_user->ID);
        if (is_array($salary_history)) {
            echo "✓ Salary history retrieved\n";
        } else {
            echo "✗ Salary history failed\n";
        }
        
    } catch (Exception $e) {
        echo "✗ Salary calculator test error: " . $e->getMessage() . "\n";
    }
} else {
    echo "ℹ️  Skipping salary calculator test - no user logged in\n";
}

// Test 7: Shortcodes
echo "\n7. Testing Shortcodes...\n";
$shortcodes_to_test = array(
    'bassmah_report_form',
    'bassmah_staff_dashboard',
    'bassmah_my_reports',
    'bassmah_role_dashboard'
);

foreach ($shortcodes_to_test as $shortcode) {
    if (shortcode_exists($shortcode)) {
        echo "✓ Shortcode '$shortcode' registered\n";
    } else {
        echo "✗ Shortcode '$shortcode' not registered\n";
    }
}

// Test 8: Plugin Options
echo "\n8. Testing Plugin Options...\n";
$options_to_test = array(
    'bassmah_task_categories',
    'bassmah_task_statuses'
);

foreach ($options_to_test as $option) {
    $value = get_option($option);
    if ($value !== false) {
        echo "✓ Option '$option' exists\n";
    } else {
        echo "✗ Option '$option' missing\n";
    }
}

echo "\n=== Functionality Test Complete ===\n";
echo "All tests completed. Review the results above.\n";
echo "\nNext steps:\n";
echo "1. Fix any failed tests\n";
echo "2. Test report submission from frontend\n";
echo "3. Test dashboard functionality\n";
echo "4. Test role-based access\n";
?>
