<?php
/**
 * JWT Authentication Setup and Test Script
 * 
 * This script sets up JWT authentication and tests the system
 * Run this to implement and verify JWT-based authentication
 */

// Load WordPress
$wp_config_path = dirname(__FILE__) . '/../../../wp-config.php';
require_once($wp_config_path);

global $wpdb;

echo "=== JWT Authentication Setup for proffg.pronizam.com ===\n\n";

// Step 1: Include JWT class
echo "1. Loading JWT Authentication Class...\n";
require_once BASSMAH_STAFF_REPORTS_PLUGIN_DIR . 'includes/class-jwt-auth.php';
echo "✓ JWT class loaded\n";

// Step 2: Initialize JWT system
echo "2. Initializing JWT System...\n";
$jwt_auth = new Bassmah_Staff_Reports_JWT_Auth();
echo "✓ JWT system initialized\n";

// Step 3: Check current user
echo "3. Checking Current User Status...\n";
$current_user = wp_get_current_user();
if ($current_user->exists()) {
    echo "✓ User logged in: " . $current_user->display_name . " (ID: " . $current_user->ID . ")\n";
    
    // Step 4: Generate JWT token
    echo "4. Generating JWT Token...\n";
    $token = $jwt_auth->generate_token($current_user);
    echo "✓ JWT token generated\n";
    echo "   Token length: " . strlen($token) . " characters\n";
    
    // Store token
    update_user_meta($current_user->ID, 'bassmah_jwt_token', $token);
    update_user_meta($current_user->ID, 'bassmah_jwt_token_issued', time());
    echo "✓ Token stored in user meta\n";
    
    // Step 5: Validate token
    echo "5. Validating JWT Token...\n";
    $validation = $jwt_auth->validate_token($token);
    if (is_wp_error($validation)) {
        echo "✗ Token validation failed: " . $validation->get_error_message() . "\n";
    } else {
        echo "✓ Token validation successful\n";
        echo "   User ID: " . $validation['data']['user_id'] . "\n";
        echo "   Username: " . $validation['data']['username'] . "\n";
        echo "   Expires: " . date('Y-m-d H:i:s', $validation['exp']) . "\n";
    }
    
    // Step 6: Test AJAX endpoint
    echo "6. Testing JWT AJAX Endpoint...\n";
    
    // Simulate AJAX request
    $_POST['action'] = 'bassmah_get_jwt_token';
    
    ob_start();
    $jwt_auth->get_jwt_token();
    $ajax_response = ob_get_clean();
    
    $response_data = json_decode($ajax_response, true);
    if ($response_data && $response_data['success']) {
        echo "✓ AJAX endpoint working\n";
        echo "   Token received: " . substr($response_data['data']['token'], 0, 50) . "...\n";
        echo "   Expires in: " . $response_data['data']['expires_in'] . " seconds\n";
    } else {
        echo "✗ AJAX endpoint failed\n";
        echo "   Response: " . $ajax_response . "\n";
    }
    
    // Step 7: Test form submission with JWT
    echo "7. Testing Form Submission with JWT...\n";
    
    // Simulate form submission
    $_POST = array();
    $_POST['action_type'] = 'submit_report';
    $_POST['tasks'] = array(
        array(
            'task_description' => 'JWT Test Report',
            'task_category' => 'Testing',
            'task_status' => 'completed',
            'next_action' => 'Verify JWT works'
        )
    );
    
    // Set Authorization header (simulated)
    $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer ' . $token;
    
    // Load public class
    require_once BASSMAH_STAFF_REPORTS_PLUGIN_DIR . 'public/class-public.php';
    $public_class = new Bassmah_Staff_Reports_Public();
    
    try {
        ob_start();
        $public_class->handle_ajax_requests();
        $form_response = ob_get_clean();
        
        echo "✓ Form submission test completed\n";
        echo "   Response: " . $form_response . "\n";
    } catch (Exception $e) {
        echo "✗ Form submission test failed: " . $e->getMessage() . "\n";
    }
    
} else {
    echo "ℹ️  No user logged in. Creating test user...\n";
    
    // Create test user
    $username = 'jwt_test_user';
    $password = wp_generate_password(12);
    $email = 'jwttest@proffg.pronizam.com';
    
    $user_id = wp_create_user($username, $password, $email);
    if (!is_wp_error($user_id)) {
        echo "✓ Test user created: $username\n";
        echo "   Password: $password\n";
        echo "   Email: $email\n";
        
        // Assign staff role
        $user = new WP_User($user_id);
        $user->add_role('bassmah_staff');
        echo "✓ Staff role assigned\n";
        
        echo "\nPlease login with this test user and run the script again.\n";
    } else {
        echo "✗ Failed to create test user: " . $user_id->get_error_message() . "\n";
    }
}

// Step 8: Setup JavaScript integration
echo "\n8. Setting up JavaScript Integration...\n";

// Check if JS file exists
$js_file = BASSMAH_STAFF_REPORTS_PLUGIN_DIR . 'public/js/jwt-auth.js';
if (file_exists($js_file)) {
    echo "✓ JWT JavaScript file exists\n";
    echo "   Path: " . $js_file . "\n";
} else {
    echo "✗ JWT JavaScript file missing\n";
}

// Step 9: Security checks
echo "\n9. Security Configuration...\n";

// Check HTTPS
$is_https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $_SERVER['SERVER_PORT'] == 443;
echo "   HTTPS: " . ($is_https ? "✓ Enabled" : "⚠️  Disabled (recommended for production)") . "\n";

// Check secret key
$secret_key = get_option('bassmah_jwt_secret_key');
if ($secret_key) {
    echo "   Secret Key: ✓ Set (" . strlen($secret_key) . " characters)\n";
} else {
    echo "   Secret Key: ⚠️  Will be generated on first use\n";
}

// Step 10: Final setup
echo "\n10. Final JWT Setup...\n";

// Flush rewrite rules
flush_rewrite_rules();

// Clear cache
wp_cache_flush();

echo "✓ Rewrite rules flushed\n";
echo "✓ Cache cleared\n";

echo "\n=== JWT Authentication Setup Complete ===\n";

if ($current_user->exists()) {
    echo "\n🎯 JWT Token for Testing:\n";
    echo $token . "\n\n";
    
    echo "📋 Test Instructions:\n";
    echo "1. Use the token above in Authorization header: Bearer [token]\n";
    echo "2. Test form submission on your dashboard\n";
    echo "3. Check browser console for JWT messages\n";
    echo "4. Verify token is stored in localStorage\n";
} else {
    echo "\n📋 Next Steps:\n";
    echo "1. Login with the test user created above\n";
    echo "2. Run this script again to get JWT token\n";
    echo "3. Test form submission functionality\n";
}

echo "\n🔧 Benefits of JWT Authentication:\n";
echo "✓ No cookie/session dependencies\n";
echo "✓ Works across different domains\n";
echo "✓ Better security with token expiration\n";
echo "✓ Mobile app friendly\n";
echo "✓ Stateless authentication\n";

echo "\n🚀 Your form submission should now work without cookie issues!\n";
?>
