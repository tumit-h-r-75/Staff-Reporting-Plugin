<?php
/**
 * Report Submission Fix Script
 * 
 * This script specifically fixes report submission issues
 * Run this to ensure reports can be submitted properly
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

echo "=== Bassmah Staff Reports - Report Submission Fix ===\n\n";

// Step 1: Verify Database Tables
echo "1. Verifying database tables...\n";
$table_reports = $wpdb->prefix . 'staff_reports';
$table_salary = $wpdb->prefix . 'staff_salary_settings';
$table_working_days = $wpdb->prefix . 'staff_working_days';

$all_tables_ok = true;
$tables_to_check = array($table_reports, $table_salary, $table_working_days);

foreach ($tables_to_check as $table) {
    $exists = $wpdb->get_var("SHOW TABLES LIKE '$table'");
    if ($exists) {
        echo "✓ Table '$table' exists\n";
        
        // Test table structure
        $columns = $wpdb->get_results("SHOW COLUMNS FROM $table");
        echo "  - Columns: " . count($columns) . "\n";
        
        // Test table access
        $count = $wpdb->get_var("SELECT COUNT(*) FROM $table");
        echo "  - Records: " . $count . "\n";
        
        // Test table write access
        $test_insert = $wpdb->insert($table, array('test_field' => 'test_value'), array('%s'));
        if ($test_insert !== false) {
            $wpdb->delete($table, array('test_field' => 'test_value'), array('%s'));
            echo "  - Write access: OK\n";
        } else {
            echo "  - Write access: FAILED\n";
            $all_tables_ok = false;
        }
    } else {
        echo "✗ Table '$table' missing\n";
        $all_tables_ok = false;
    }
}

if (!$all_tables_ok) {
    echo "\n⚠️  Database tables have issues. Please run init-database-complete.php first.\n";
    exit(1);
}

// Step 2: Test Report Class
echo "\n2. Testing Report Class...\n";
try {
    require_once BASSMAH_STAFF_REPORTS_PLUGIN_DIR . 'includes/class-report.php';
    $report_class = new Bassmah_Staff_Reports_Report();
    echo "✓ Report class loaded\n";
    
    // Test get_report_by_date method
    $test_date = date('Y-m-d');
    $existing_report = $report_class->get_report_by_date(1, $test_date);
    echo "✓ get_report_by_date method working\n";
    
} catch (Exception $e) {
    echo "✗ Report class error: " . $e->getMessage() . "\n";
    exit(1);
}

// Step 3: Test Report Submission
echo "\n3. Testing Report Submission...\n";
try {
    // Get current user
    $current_user = wp_get_current_user();
    if (!$current_user->exists()) {
        echo "ℹ️  No user logged in. Creating test user...\n";
        
        // Create test user if needed
        $user_id = wp_create_user('test_staff_user', wp_generate_password(12), 'test@staff.com');
        if ($user_id && !is_wp_error($user_id)) {
            $current_user = get_userdata($user_id);
            wp_set_current_user($user_id);
            echo "✓ Test user created and logged in\n";
        } else {
            echo "✗ Failed to create test user\n";
            exit(1);
        }
    }
    
    // Test report data
    $test_report_data = array(
        'user_id' => $current_user->ID,
        'report_date' => date('Y-m-d'),
        'tasks' => array(
            array(
                'task_description' => 'Test task from report submission fix',
                'task_category' => 'Development',
                'task_status' => 'completed',
                'next_action' => 'Continue testing'
            )
        ),
        'status' => 'submitted'
    );
    
    echo "  - User ID: " . $current_user->ID . "\n";
    echo "  - Report date: " . $test_report_data['report_date'] . "\n";
    echo "  - Tasks count: " . count($test_report_data['tasks']) . "\n";
    
    // Check if report already exists for today
    $existing = $report_class->get_report_by_date($current_user->ID, $test_report_data['report_date']);
    if ($existing) {
        echo "ℹ️  Report already exists for today. Deleting existing report...\n";
        
        // Delete existing report
        $wpdb->delete($table_reports, array('user_id' => $current_user->ID, 'report_date' => $test_report_data['report_date']), array('%d', '%s'));
        echo "✓ Existing report deleted\n";
    }
    
    // Submit new report
    echo "  - Submitting new report...\n";
    $report_id = $report_class->submit_report($test_report_data);
    
    if (is_wp_error($report_id)) {
        echo "✗ Report submission failed: " . $report_id->get_error_message() . "\n";
        echo "  - Error code: " . $report_id->get_error_code() . "\n";
        echo "  - Error data: " . print_r($report_id->get_error_data(), true) . "\n";
    } else {
        echo "✓ Report submitted successfully with ID: " . $report_id . "\n";
        
        // Verify report was actually saved
        $saved_report = $report_class->get_report($report_id);
        if ($saved_report) {
            echo "✓ Report verified in database\n";
            echo "  - Report date: " . $saved_report->report_date . "\n";
            echo "  - Status: " . $saved_report->status . "\n";
            echo "  - Tasks saved: " . (count(json_decode($saved_report->tasks_json, true))) . "\n";
            
            // Clean up - delete test report
            $wpdb->delete($table_reports, array('id' => $report_id), array('%d'));
            echo "✓ Test report cleaned up\n";
        } else {
            echo "✗ Report not found in database after submission\n";
        }
    }
    
} catch (Exception $e) {
    echo "✗ Report submission test error: " . $e->getMessage() . "\n";
    echo "  - File: " . $e->getFile() . "\n";
    echo "  - Line: " . $e->getLine() . "\n";
}

// Step 4: Test AJAX Handler
echo "\n4. Testing AJAX Handler...\n";
try {
    // Simulate AJAX request
    $_POST['action_type'] = 'submit_report';
    $_POST['tasks'] = array(
        array(
            'task_description' => 'Test AJAX submission',
            'task_category' => 'Testing',
            'task_status' => 'completed',
            'next_action' => 'Verify AJAX works'
        )
    );
    $_POST['nonce'] = wp_create_nonce('bassmah_frontend_nonce');
    
    // Include public class
    require_once BASSMAH_STAFF_REPORTS_PLUGIN_DIR . 'public/class-public.php';
    $public_class = new Bassmah_Staff_Reports_Public();
    
    // Test AJAX handling
    ob_start();
    $public_class->handle_ajax_requests();
    $output = ob_get_clean();
    ob_end_clean();
    
    echo "✓ AJAX handler tested\n";
    
} catch (Exception $e) {
    echo "✗ AJAX handler error: " . $e->getMessage() . "\n";
}

// Step 5: Fix Common Issues
echo "\n5. Fixing Common Issues...\n";

// Fix 1: Ensure proper table permissions
echo "  - Checking table permissions...\n";
$wpdb->query("GRANT SELECT, INSERT, UPDATE, DELETE ON {$table_reports} TO CURRENT_USER");
$wpdb->query("GRANT SELECT, INSERT, UPDATE, DELETE ON {$table_salary} TO CURRENT_USER");
$wpdb->query("GRANT SELECT, INSERT, UPDATE, DELETE ON {$table_working_days} TO CURRENT_USER");
echo "  ✓ Table permissions updated\n";

// Fix 2: Clear any stuck locks
echo "  - Clearing any stuck locks...\n";
$wpdb->query("DELETE FROM {$wpdb->prefix}options WHERE option_name LIKE '%_transient_bassmah_%'");
echo "  ✓ Stuck locks cleared\n";

// Fix 3: Reset plugin cache
echo "  - Resetting plugin cache...\n";
wp_cache_delete('bassmah_reports_cache');
wp_cache_delete('bassmah_salary_cache');
echo "  ✓ Plugin cache cleared\n";

// Fix 4: Ensure proper user capabilities
echo "  - Verifying user capabilities...\n";
if ($current_user->exists()) {
    $user = new WP_User($current_user->ID);
    $user->add_cap('bassmah_submit_reports');
    $user->add_cap('bassmah_view_own_reports');
    $user->add_cap('bassmah_view_own_salary');
    echo "  ✓ User capabilities updated\n";
}

echo "\n=== Report Submission Fix Complete ===\n";
echo "\nNext steps:\n";
echo "1. Test report submission from frontend\n";
echo "2. Check WordPress error logs for any remaining issues\n";
echo "3. Verify reports appear in database\n";
echo "4. Test dashboard functionality\n";
echo "5. Delete test user if created (username: test_staff_user)\n";

echo "\nIf report submission still fails:\n";
echo "- Check WordPress debug log\n";
echo "- Verify database permissions\n";
echo "- Check PHP error log\n";
echo "- Ensure all plugin files are uploaded correctly\n";
?>
