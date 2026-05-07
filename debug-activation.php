<?php
// Debug activation issues
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Starting plugin activation debug...\n";

// Test basic WordPress functions
if (function_exists('add_role')) {
    echo "✅ add_role function exists\n";
} else {
    echo "❌ add_role function missing\n";
}

if (function_exists('register_activation_hook')) {
    echo "✅ register_activation_hook function exists\n";
} else {
    echo "❌ register_activation_hook function missing\n";
}

if (function_exists('flush_rewrite_rules')) {
    echo "✅ flush_rewrite_rules function exists\n";
} else {
    echo "❌ flush_rewrite_rules function missing\n";
}

// Test class loading
try {
    require_once __DIR__ . '/includes/class-roles.php';
    echo "✅ class-roles.php loaded\n";
    
    if (class_exists('Basmah_Staff_Reports_Roles')) {
        echo "✅ Basmah_Staff_Reports_Roles class exists\n";
    } else {
        echo "❌ Basmah_Staff_Reports_Roles class missing\n";
    }
    
    if (method_exists('Basmah_Staff_Reports_Roles', 'add_roles')) {
        echo "✅ add_roles method exists\n";
    } else {
        echo "❌ add_roles method missing\n";
    }
    
} catch (Error $e) {
    echo "❌ Error loading roles: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "❌ Exception loading roles: " . $e->getMessage() . "\n";
}

// Test database table creation
try {
    require_once __DIR__ . '/database/tables.php';
    echo "✅ tables.php loaded\n";
    
    if (class_exists('Basmah_Staff_Reports_Tables')) {
        echo "✅ Basmah_Staff_Reports_Tables class exists\n";
    } else {
        echo "❌ Basmah_Staff_Reports_Tables class missing\n";
    }
    
    if (method_exists('Basmah_Staff_Reports_Tables', 'create_tables')) {
        echo "✅ create_tables method exists\n";
    } else {
        echo "❌ create_tables method missing\n";
    }
    
} catch (Error $e) {
    echo "❌ Error loading tables: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "❌ Exception loading tables: " . $e->getMessage() . "\n";
}

echo "Debug completed.\n";
?>
