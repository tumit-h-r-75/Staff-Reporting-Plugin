<?php
// Simple activation test
echo "Testing plugin activation...\n";

// Check if main plugin file exists
$plugin_file = __DIR__ . '/bassmah-staff-reports.php';
if (file_exists($plugin_file)) {
    echo "Main plugin file exists: YES\n";
} else {
    echo "Main plugin file exists: NO\n";
}

// Test basic PHP syntax
$content = file_get_contents($plugin_file);
if (function_exists('php_check_syntax')) {
    $syntax_check = php_check_syntax($content);
    if ($syntax_check === true) {
        echo "PHP Syntax: OK\n";
    } else {
        echo "PHP Syntax: ERROR - " . $syntax_check['message'] . "\n";
    }
} else {
    echo "Cannot check PHP syntax (php_check_syntax not available)\n";
}

// Test class loading
try {
    require_once $plugin_file;
    echo "Plugin loaded: YES\n";
} catch (Error $e) {
    echo "Plugin Error: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "Plugin Exception: " . $e->getMessage() . "\n";
}

echo "Test completed.\n";
?>
