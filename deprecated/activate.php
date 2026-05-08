<?php
/**
 * Quick activation script for Bassmah Staff Reports Pro
 * 
 * Usage: php activate.php
 */

// Check if WordPress is loaded
if (!defined('ABSPATH')) {
    // Try to find WordPress path
    $wp_path = dirname(__FILE__);
    while ($wp_path !== '/' && !file_exists($wp_path . '/wp-config.php')) {
        $wp_path = dirname($wp_path);
    }
    
    if (file_exists($wp_path . '/wp-config.php')) {
        require_once($wp_path . '/wp-config.php');
    } else {
        die("WordPress not found. Please run this script from within WordPress installation.");
    }
}

// Check if plugin exists
$plugin_path = dirname(__FILE__) . '/bassmah-staff-reports.php';
if (!file_exists($plugin_path)) {
    die("Plugin file not found: bassmah-staff-reports.php");
}

// Activate plugin
if (!function_exists('activate_plugin')) {
    require_once(ABSPATH . 'wp-admin/includes/plugin.php');
}

$result = activate_plugin('bassmah-staff-reports/bassmah-staff-reports.php');

if (is_wp_error($result)) {
    echo "Activation failed: " . $result->get_error_message() . "\n";
} else {
    echo "Bassmah Staff Reports Pro activated successfully!\n";
    echo "You can now configure it in WordPress admin.\n";
}
?>
