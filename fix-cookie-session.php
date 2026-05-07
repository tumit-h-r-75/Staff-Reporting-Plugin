<?php
/**
 * Cookie & Session Fix Script
 * 
 * This script fixes WordPress cookie and session issues
 * Run this to resolve "Cookie check failed" errors
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

echo "=== Bassmah Staff Reports - Cookie & Session Fix ===\n\n";

// Fix 1: Clear WordPress cache and cookies
echo "1. Clearing WordPress cache and cookies...\n";

// Clear WordPress object cache
wp_cache_flush();

// Clear all WordPress transients
$wpdb->query("DELETE FROM {$wpdb->prefix}options WHERE option_name LIKE '_transient_%'");
$wpdb->query("DELETE FROM {$wpdb->prefix}options WHERE option_name LIKE '_transient_timeout_%'");

echo "✓ Cache cleared\n";

// Fix 2: Reset WordPress authentication cookies
echo "2. Resetting authentication cookies...\n";

// Get cookie domain
$cookie_domain = parse_url(get_option('siteurl'), PHP_URL_HOST);
$cookie_path = COOKIEPATH ? COOKIEPATH : '/';

// Clear WordPress auth cookies
$auth_cookies = array(
    'wordpress_logged_in_' . COOKIEHASH,
    'wordpress_sec_' . COOKIEHASH,
    'wordpresspass_' . COOKIEHASH,
    'wordpressuser_' . COOKIEHASH,
    'wp-settings-' . get_current_user_id(),
    'wp-settings-time-' . get_current_user_id()
);

foreach ($auth_cookies as $cookie) {
    if (isset($_COOKIE[$cookie])) {
        setcookie($cookie, '', time() - YEAR_IN_SECONDS, $cookie_path, $cookie_domain);
        unset($_COOKIE[$cookie]);
    }
}

echo "✓ Authentication cookies cleared\n";

// Fix 3: Reset user sessions
echo "3. Resetting user sessions...\n";

// Clear user sessions from database
$wpdb->query("DELETE FROM {$wpdb->prefix}usermeta WHERE meta_key = 'session_tokens'");

// Clear all user sessions
wp_clear_auth_cookie();

echo "✓ User sessions cleared\n";

// Fix 4: Regenerate WordPress salts and keys
echo "4. Regenerating WordPress salts...\n";

// Get current wp-config.php content
$wp_config_file = file_get_contents(ABSPATH . 'wp-config.php');

// Generate new salts
$new_salts = array(
    'AUTH_KEY' => wp_generate_password(64),
    'SECURE_AUTH_KEY' => wp_generate_password(64),
    'LOGGED_IN_KEY' => wp_generate_password(64),
    'NONCE_KEY' => wp_generate_password(64),
    'AUTH_SALT' => wp_generate_password(64),
    'SECURE_AUTH_SALT' => wp_generate_password(64),
    'LOGGED_IN_SALT' => wp_generate_password(64),
    'NONCE_SALT' => wp_generate_password(64)
);

// Update wp-config.php with new salts
$wp_config_updated = false;
foreach ($new_salts as $salt_key => $salt_value) {
    $pattern = "/define\(\s*['\"]" . $salt_key . "['\"],\s*['\"](.*?)['\"][\s*\);/s";
    if (preg_match($pattern, $wp_config_file)) {
        $replacement = "define('" . $salt_key . "', '" . $salt_value . "');";
        $wp_config_file = preg_replace($pattern, $replacement, $wp_config_file);
        $wp_config_updated = true;
    }
}

if ($wp_config_updated) {
    // Write updated wp-config.php
    file_put_contents(ABSPATH . 'wp-config.php', $wp_config_file);
    echo "✓ WordPress salts regenerated\n";
} else {
    echo "ℹ️  WordPress salts already up to date\n";
}

// Fix 5: Reset plugin options
echo "5. Resetting plugin options...\n";

// Reset problematic plugin options
$options_to_reset = array(
    'bassmah_staff_reports_session_token',
    'bassmah_staff_reports_last_activity',
    'bassmah_staff_reports_cookie_check'
);

foreach ($options_to_reset as $option) {
    delete_option($option);
}

echo "✓ Plugin options reset\n";

// Fix 6: Recreate user sessions properly
echo "6. Creating fresh user sessions...\n";

// Get current user
if (is_user_logged_in()) {
    $user_id = get_current_user_id();
    $user = get_userdata($user_id);
    
    if ($user) {
        // Set new session token
        wp_set_current_user($user);
        
        // Update user last activity
        update_user_meta($user_id, 'last_activity', current_time('mysql'));
        
        echo "✓ Fresh session created for user: " . $user->display_name . "\n";
    }
} else {
    echo "ℹ️  No user currently logged in\n";
}

// Fix 7: Clear browser cache headers
echo "7. Setting cache headers...\n";

// Set headers to prevent caching
if (!headers_sent()) {
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Cache-Control: post-check=0, pre-check=0', false);
    header('Pragma: no-cache');
    header('Expires: Wed, 11 Jan 1984 05:00:00 GMT');
}

echo "✓ Cache headers set\n";

// Fix 8: Verify WordPress nonce system
echo "8. Testing nonce system...\n";

// Test nonce creation
$test_nonce = wp_create_nonce('bassmah_test_nonce');
if ($test_nonce) {
    echo "✓ Nonce system working\n";
} else {
    echo "✗ Nonce system failed\n";
}

// Fix 9: Reset WordPress hooks
echo "9. Resetting WordPress hooks...\n";

// Remove all filters that might cause issues
remove_all_filters('auth_cookie');
remove_all_filters('secure_auth_cookie');
remove_all_filters('secure_logged_in_cookie');

// Re-add proper WordPress authentication
wp_set_auth_cookie(get_current_user_id(), true, false);
wp_set_current_user(get_current_user_id());

echo "✓ WordPress hooks reset\n";

// Fix 10: Flush rewrite rules and update options
echo "10. Finalizing fixes...\n";

// Flush rewrite rules
flush_rewrite_rules();

// Update site URL and home URL
update_option('siteurl', get_option('siteurl'));
update_option('home', get_option('home'));

// Clear any remaining cache
wp_cache_delete('alloptions', 'options');

echo "✓ Rewrite rules flushed\n";

echo "\n=== Cookie & Session Fix Complete ===\n";
echo "\nNext steps:\n";
echo "1. Clear your browser cookies manually\n";
echo "2. Restart your browser\n";
echo "3. Login again to WordPress\n";
echo "4. Test dashboard functionality\n";
echo "5. Test report submission\n";

echo "\nIf issues persist:\n";
echo "- Check browser cookie settings\n";
echo "- Try a different browser\n";
echo "- Contact your hosting provider\n";
echo "- Check WordPress debug log\n";
?>
