<?php
/**
 * Debug Form Submission Script
 * Run this to test and fix form submission issues
 */

// Load WordPress
$wp_config_path = dirname(__FILE__) . '/../../../wp-config.php';
require_once($wp_config_path);

global $wpdb;

echo "=== Debug Form Submission ===\n\n";

// Step 1: Check user authentication
echo "1. User Authentication Check:\n";
if (is_user_logged_in()) {
    $user = wp_get_current_user();
    echo "   User: " . $user->display_name . " (ID: " . $user->ID . ")\n";
    echo "   Roles: " . implode(', ', $user->roles) . "\n";
    echo "   Can submit reports: " . (current_user_can('bassmah_submit_reports') ? 'YES' : 'NO') . "\n";
} else {
    echo "   Status: NOT LOGGED IN\n";
    echo "   This is the main issue - user must be logged in!\n";
}

// Step 2: Check database tables
echo "\n2. Database Tables Check:\n";
$tables = array(
    'staff_reports' => $wpdb->prefix . 'staff_reports',
    'staff_salary_settings' => $wpdb->prefix . 'staff_salary_settings',
    'staff_working_days' => $wpdb->prefix . 'staff_working_days'
);

foreach ($tables as $name => $table) {
    $exists = $wpdb->get_var("SHOW TABLES LIKE '$table'");
    echo "   $name: " . ($exists ? "EXISTS" : "MISSING") . "\n";
    
    if ($exists) {
        $count = $wpdb->get_var("SELECT COUNT(*) FROM $table");
        echo "      Records: $count\n";
    }
}

// Step 3: Test AJAX handler
echo "\n3. AJAX Handler Test:\n";

// Simulate AJAX request
$_POST['action'] = 'bassmah_submit_report';
$_POST['action_type'] = 'submit_report';
$_POST['tasks'] = array(
    array(
        'task_description' => 'Debug test submission',
        'task_category' => 'Testing',
        'task_status' => 'completed',
        'next_action' => 'Verify submission works'
    )
);

// Load public class
require_once BASSMAH_STAFF_REPORTS_PLUGIN_DIR . 'public/class-public.php';
$public_class = new Bassmah_Staff_Reports_Public();

echo "   Testing AJAX handler...\n";

try {
    // Capture output
    ob_start();
    $public_class->handle_ajax_requests();
    $output = ob_get_clean();
    
    echo "   AJAX Response: $output\n";
    
    // Parse response
    $response = json_decode($output, true);
    if ($response && isset($response['success'])) {
        echo "   Result: " . ($response['success'] ? 'SUCCESS' : 'FAILED') . "\n";
        if (!$response['success'] && isset($response['data'])) {
            echo "   Error: " . $response['data'] . "\n";
        }
    } else {
        echo "   Result: INVALID RESPONSE\n";
    }
} catch (Exception $e) {
    echo "   Exception: " . $e->getMessage() . "\n";
}

// Step 4: Test JWT authentication
echo "\n4. JWT Authentication Test:\n";

if (is_user_logged_in()) {
    // Check JWT class exists
    if (class_exists('Bassmah_Staff_Reports_JWT_Auth')) {
        $jwt_auth = new Bassmah_Staff_Reports_JWT_Auth();
        $token = $jwt_auth->generate_token(wp_get_current_user());
        
        if ($token) {
            echo "   JWT Token: GENERATED\n";
            echo "   Token length: " . strlen($token) . " chars\n";
            
            // Validate token
            $validation = $jwt_auth->validate_token($token);
            if (!is_wp_error($validation)) {
                echo "   JWT Validation: SUCCESS\n";
            } else {
                echo "   JWT Validation: FAILED - " . $validation->get_error_message() . "\n";
            }
        } else {
            echo "   JWT Token: FAILED TO GENERATE\n";
        }
    } else {
        echo "   JWT Class: NOT FOUND\n";
    }
}

// Step 5: Check JavaScript variables
echo "\n5. JavaScript Variables Check:\n";

// Check if scripts are enqueued
global $wp_scripts;
$scripts = array(
    'jquery' => isset($wp_scripts->registered['jquery']),
    'jquery-ui-datepicker' => isset($wp_scripts->registered['jquery-ui-datepicker']),
    'bassmah-jwt-auth' => isset($wp_scripts->registered['bassmah-jwt-auth']),
    'bassmah-staff-reports' => isset($wp_scripts->registered['bassmah-staff-reports'])
);

foreach ($scripts as $name => $registered) {
    echo "   $name: " . ($registered ? 'ENQUEUED' : 'NOT ENQUEUED') . "\n";
}

// Step 6: Check stylesheets
echo "\n6. Stylesheets Check:\n";
global $wp_styles;
$styles = array(
    'jquery-ui-css' => isset($wp_styles->registered['jquery-ui-css']),
    'bassmah-staff-reports' => isset($wp_styles->registered['bassmah-staff-reports'])
);

foreach ($styles as $name => $registered) {
    echo "   $name: " . ($registered ? 'ENQUEUED' : 'NOT ENQUEUED') . "\n";
}

// Step 7: Test direct database insert
echo "\n7. Direct Database Insert Test:\n";

if (is_user_logged_in() && isset($tables['staff_reports'])) {
    $test_data = array(
        'user_id' => get_current_user_id(),
        'report_date' => current_time('Y-m-d'),
        'submission_time' => current_time('mysql'),
        'status' => 'submitted',
        'tasks_json' => json_encode(array(
            array(
                'task_description' => 'Direct insert test',
                'task_category' => 'Testing',
                'task_status' => 'completed',
                'next_action' => 'Verify direct insert works'
            )
        )),
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
        'created_at' => current_time('mysql'),
        'updated_at' => current_time('mysql')
    );
    
    $result = $wpdb->insert($tables['staff_reports'], $test_data);
    
    if ($result !== false) {
        echo "   Direct insert: SUCCESS (ID: " . $wpdb->insert_id . ")\n";
        
        // Clean up test record
        $wpdb->delete(
            $tables['staff_reports'],
            array('id' => $wpdb->insert_id),
            array('%d')
        );
        echo "   Test record cleaned up\n";
    } else {
        echo "   Direct insert: FAILED\n";
        echo "   Error: " . $wpdb->last_error . "\n";
    }
}

echo "\n=== Debug Complete ===\n";
echo "\n📋 Next Steps:\n";
echo "1. Check if user is logged in\n";
echo "2. Verify all database tables exist\n";
echo "3. Ensure AJAX handler works\n";
echo "4. Confirm JWT authentication\n";
echo "5. Test form submission manually\n";

echo "\n🚀 If all checks pass, form submission should work!\n";

// Self-destruct
if (file_exists(__FILE__)) {
    unlink(__FILE__);
}
?>
