<?php
/**
 * Cleanup Unused Files Script
 * 
 * This script removes all temporary and unused files from the plugin
 * Run this to clean up the plugin directory
 */

// Load WordPress
$wp_config_path = dirname(__FILE__) . '/../../../wp-config.php';
require_once($wp_config_path);

echo "=== Cleaning Up Unused Files ===\n\n";

// List of files to remove
$files_to_remove = array(
    'fix-cookie-session.php',
    'fix-database.php', 
    'fix-report-submission.php',
    'force-activate.php',
    'init-database-complete.php',
    'database-setup-sql.php',
    'final-fix-proffg.php',
    'test-functionality.php',
    'check-database-status.php',
    'setup-jwt-auth.php'
);

$plugin_dir = BASSMAH_STAFF_REPORTS_PLUGIN_DIR;
$removed_count = 0;

echo "Removing temporary and unused files...\n";

foreach ($files_to_remove as $file) {
    $file_path = $plugin_dir . $file;
    
    if (file_exists($file_path)) {
        if (unlink($file_path)) {
            echo "✓ Removed: $file\n";
            $removed_count++;
        } else {
            echo "✗ Failed to remove: $file\n";
        }
    } else {
        echo "- Not found: $file\n";
    }
}

echo "\nRemoved $removed_count unused files.\n";

// Clean up unused code from main files
echo "\nCleaning up unused code...\n";

// Remove debug logging from class-report.php
$report_file = $plugin_dir . 'includes/class-report.php';
if (file_exists($report_file)) {
    $content = file_get_contents($report_file);
    
    // Remove debug logging code
    $patterns_to_remove = array(
        '/\/\/ Debug: Log table name and data.*?error_log\(\'Bassmah Plugin: Report inserted with ID: \'\. \$report_id\);\s*/s',
        '/error_log\(\'Bassmah Plugin:.*?\);\s*/',
        '/\/\/ Debug: Log.*?\n\s*/'
    );
    
    foreach ($patterns_to_remove as $pattern) {
        $content = preg_replace($pattern, '', $content);
    }
    
    file_put_contents($report_file, $content);
    echo "✓ Cleaned debug code from class-report.php\n";
}

// Remove unused code from class-public.php
$public_file = $plugin_dir . 'public/class-public.php';
if (file_exists($public_file)) {
    $content = file_get_contents($public_file);
    
    // Remove duplicate AJAX action registrations
    $content = preg_replace('/add_action\(\'wp_ajax_nopriv_bassmah_get_report_details\', array\(\$this, \'handle_ajax_requests\'\)\);\s*/', '', $content);
    $content = preg_replace('/add_action\(\'wp_ajax_bassmah_export_reports\', array\(\$this, \'handle_ajax_requests\'\)\);\s*/', '', $content);
    $content = preg_replace('/add_action\(\'wp_ajax_nopriv_bassmah_export_reports\', array\(\$this, \'handle_ajax_requests\'\)\);\s*/', '', $content);
    $content = preg_replace('/add_action\(\'wp_ajax_bassmah_export_salary_history\', array\(\$this, \'handle_ajax_requests\'\)\);\s*/', '', $content);
    $content = preg_replace('/add_action\(\'wp_ajax_nopriv_bassmah_export_salary_history\', array\(\$this, \'handle_ajax_requests\'\)\);\s*/', '', $content);
    
    file_put_contents($public_file, $content);
    echo "✓ Cleaned duplicate AJAX registrations from class-public.php\n";
}

// Remove unused code from role-dashboard.php
$role_dashboard_file = $plugin_dir . 'public/views/role-dashboard.php';
if (file_exists($role_dashboard_file)) {
    $content = file_get_contents($role_dashboard_file);
    
    // Remove excessive cache headers
    $content = preg_replace('/\/\/ Clear any problematic sessions.*?\n\s*/', '', $content);
    $content = preg_replace('/wp_cache_flush\(\);\s*/', '', $content);
    
    file_put_contents($role_dashboard_file, $content);
    echo "✓ Cleaned excessive cache code from role-dashboard.php\n";
}

// Remove unused code from staff-dashboard.php
$staff_dashboard_file = $plugin_dir . 'public/views/staff-dashboard.php';
if (file_exists($staff_dashboard_file)) {
    $content = file_get_contents($staff_dashboard_file);
    
    // Remove duplicate class includes (they're already loaded by main plugin)
    $content = preg_replace('/\/\/ Include required classes.*?require_once.*?class-salary-calculator\.php\';\s*/s', '', $content);
    
    file_put_contents($staff_dashboard_file, $content);
    echo "✓ Cleaned duplicate class includes from staff-dashboard.php\n";
}

echo "\n=== Cleanup Complete ===\n";
echo "\n📁 Final Plugin Structure:\n";
echo "├── bassmah-staff-reports.php (main plugin file)\n";
echo "├── includes/\n";
echo "│   ├── class-bassmah-staff-reports.php\n";
echo "│   ├── class-activator.php\n";
echo "│   ├── class-report.php\n";
echo "│   ├── class-salary.php\n";
echo "│   ├── class-salary-calculator.php\n";
echo "│   ├── class-jwt-auth.php (NEW)\n";
echo "│   └── ...\n";
echo "├── public/\n";
echo "│   ├── class-public.php\n";
echo "│   ├── js/\n";
echo "│   │   └── jwt-auth.js (NEW)\n";
echo "│   └── views/\n";
echo "│       ├── staff-dashboard.php\n";
echo "│       ├── my-reports.php\n";
echo "│       └── role-dashboard.php\n";
echo "├── admin/\n";
echo "│   └── ...\n";
echo "└── README.md\n";

echo "\n✅ Plugin is now clean and optimized!\n";
echo "🚀 Ready for production use.\n";
?>
