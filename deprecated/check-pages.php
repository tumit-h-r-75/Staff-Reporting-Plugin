<?php
/**
 * Check Pages Functionality
 * Tests /my-reports, /report, /dashboard pages
 */

// Load WordPress
$wp_config_path = dirname(__FILE__) . '/../../../wp-config.php';
require_once($wp_config_path);

global $wpdb;

echo "=== Pages Functionality Check ===\n\n";

// Step 1: Check if pages exist
echo "1. Page Existence Check:\n";

$pages = array(
    '/my-reports' => 'My Reports Page',
    '/report' => 'Report Form Page', 
    '/dashboard' => 'Dashboard Page'
);

foreach ($pages as $url => $name) {
    $page = get_page_by_path(trim($url, '/'));
    if ($page) {
        echo "   $name: EXISTS (ID: " . $page->ID . ")\n";
        echo "     URL: " . get_permalink($page->ID) . "\n";
        echo "     Content: " . (has_shortcode($page->post_content) ? 'HAS SHORTCODE' : 'NO SHORTCODE') . "\n";
    } else {
        echo "   $name: NOT FOUND\n";
    }
}

// Step 2: Check shortcode registration
echo "\n2. Shortcode Registration Check:\n";

$shortcodes = array(
    'bassmah_my_reports' => 'My Reports Shortcode',
    'bassmah_report_form' => 'Report Form Shortcode',
    'bassmah_staff_dashboard' => 'Staff Dashboard Shortcode',
    'bassmah_role_dashboard' => 'Role Dashboard Shortcode'
);

foreach ($shortcodes as $tag => $name) {
    global $shortcode_tags;
    if (isset($shortcode_tags[$tag])) {
        echo "   $name: REGISTERED\n";
    } else {
        echo "   $name: NOT REGISTERED\n";
    }
}

// Step 3: Check public class methods
echo "\n3. Public Class Methods Check:\n";

if (class_exists('Bassmah_Staff_Reports_Public')) {
    $public_class = new Bassmah_Staff_Reports_Public();
    
    $methods = array(
        'render_my_reports' => 'My Reports',
        'render_report_form' => 'Report Form',
        'render_staff_dashboard' => 'Staff Dashboard',
        'render_role_dashboard' => 'Role Dashboard'
    );
    
    foreach ($methods as $method => $name) {
        if (method_exists($public_class, $method)) {
            echo "   $name: METHOD EXISTS\n";
        } else {
            echo "   $name: METHOD MISSING\n";
        }
    }
} else {
    echo "   Public Class: NOT FOUND\n";
}

// Step 4: Test shortcode rendering
echo "\n4. Shortcode Rendering Test:\n";

if (class_exists('Bassmah_Staff_Reports_Public')) {
    $public_class = new Bassmah_Staff_Reports_Public();
    
    $test_shortcodes = array(
        'bassmah_my_reports' => 'render_my_reports',
        'bassmah_report_form' => 'render_report_form',
        'bassmah_staff_dashboard' => 'render_staff_dashboard',
        'bassmah_role_dashboard' => 'render_role_dashboard'
    );
    
    foreach ($test_shortcodes as $shortcode => $method) {
        if (method_exists($public_class, $method)) {
            try {
                ob_start();
                $public_class->$method();
                $output = ob_get_clean();
                
                if (!empty($output)) {
                    echo "   $shortcode: RENDERS OK (" . strlen($output) . " chars)\n";
                } else {
                    echo "   $shortcode: EMPTY OUTPUT\n";
                }
            } catch (Exception $e) {
                echo "   $shortcode: ERROR - " . $e->getMessage() . "\n";
            }
        } else {
            echo "   $shortcode: METHOD MISSING\n";
        }
    }
}

// Step 5: Check WordPress rewrite rules
echo "\n5. WordPress Rewrite Rules Check:\n";

$rewrite_rules = get_option('rewrite_rules');
$staff_rules = array();

if ($rewrite_rules) {
    foreach ($rewrite_rules as $pattern => $rule) {
        if (strpos($pattern, 'my-reports') !== false || strpos($pattern, 'report') !== false || strpos($pattern, 'dashboard') !== false) {
            $staff_rules[] = $pattern;
        }
    }
    
    echo "   Staff-related rewrite rules: " . count($staff_rules) . "\n";
    
    if (empty($staff_rules)) {
        echo "   No specific rewrite rules found (using pages with shortcodes)\n";
    }
} else {
    echo "   Rewrite rules: NOT FOUND\n";
}

// Step 6: Check user capabilities
echo "\n6. User Capabilities Check:\n";

if (is_user_logged_in()) {
    $user = wp_get_current_user();
    echo "   Current User: " . $user->display_name . " (ID: " . $user->ID . ")\n";
    echo "   Roles: " . implode(', ', $user->roles) . "\n";
    
    $caps = array(
        'bassmah_submit_reports' => 'Submit Reports',
        'bassmah_view_own_reports' => 'View Own Reports',
        'bassmah_view_all_reports' => 'View All Reports',
        'bassmah_view_own_salary' => 'View Own Salary',
        'bassmah_view_all_salary' => 'View All Salary'
    );
    
    foreach ($caps as $cap => $name) {
        echo "   $name: " . (current_user_can($cap) ? 'YES' : 'NO') . "\n";
    }
} else {
    echo "   User: NOT LOGGED IN\n";
}

// Step 7: Test page URLs
echo "\n7. Page URL Test:\n";

$base_url = home_url();
$test_urls = array(
    'my-reports' => $base_url . '/my-reports/',
    'report' => $base_url . '/report/',
    'dashboard' => $base_url . '/dashboard/'
);

foreach ($test_urls as $page => $url) {
    echo "   $page: $url\n";
    
    // Check if URL is accessible
    $response = wp_remote_get($url);
    if (!is_wp_error($response)) {
        $code = wp_remote_retrieve_response_code($response);
        echo "     HTTP Status: $code\n";
        echo "     Accessible: " . ($code === 200 ? 'YES' : 'NO') . "\n";
    } else {
        echo "     Error: " . $response->get_error_message() . "\n";
    }
}

// Step 8: Check database tables for pages
echo "\n8. Database Tables Check:\n";

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
        echo "     Records: $count\n";
    }
}

echo "\n=== Recommendations ===\n";
echo "📋 If pages don't exist:\n";
echo "1. Create WordPress pages: /my-reports, /report, /dashboard\n";
echo "2. Add appropriate shortcodes:\n";
echo "   - [bassmah_my_reports] for /my-reports\n";
echo "   - [bassmah_report_form] for /report\n";
echo "   - [bassmah_staff_dashboard] or [bassmah_role_dashboard] for /dashboard\n";
echo "3. Set page templates to use Elementor or default theme\n";
echo "4. Check user permissions for each page\n";

echo "\n🚀 Next Steps:\n";
echo "1. Test each page URL directly\n";
echo "2. Verify shortcodes render correctly\n";
echo "3. Check for JavaScript errors on each page\n";
echo "4. Ensure database tables exist\n";

echo "\n✅ Run this script to identify page issues!\n";

// Self-destruct
if (file_exists(__FILE__)) {
    unlink(__FILE__);
}
?>
