<?php
// Simple plugin activation test
echo "Testing basic plugin functions...\n";

// Test 1: WordPress functions
if (function_exists('register_activation_hook')) {
    echo "✅ register_activation_hook: OK\n";
} else {
    echo "❌ register_activation_hook: MISSING\n";
}

if (function_exists('add_role')) {
    echo "✅ add_role: OK\n";
} else {
    echo "❌ add_role: MISSING\n";
}

// Test 2: File includes
try {
    require_once __DIR__ . '/includes/class-roles.php';
    echo "✅ class-roles.php: LOADED\n";
} catch (Error $e) {
    echo "❌ class-roles.php ERROR: " . $e->getMessage() . "\n";
}

// Test 3: Class existence
if (class_exists('Basmah_Staff_Reports_Roles')) {
    echo "✅ Basmah_Staff_Reports_Roles: EXISTS\n";
} else {
    echo "❌ Basmah_Staff_Reports_Roles: MISSING\n";
}

// Test 4: Method existence
if (method_exists('Basmah_Staff_Reports_Roles', 'add_roles')) {
    echo "✅ add_roles method: EXISTS\n";
} else {
    echo "❌ add_roles method: MISSING\n";
}

echo "Basic test completed.\n";
?>
