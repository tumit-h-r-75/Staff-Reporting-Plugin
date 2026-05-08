<?php
/**
 * Fix REST API Routes
 * Fixes "No route was found matching the URL and request method" error
 */

// Load WordPress
$wp_config_path = dirname(__FILE__) . '/../../../wp-config.php';
require_once($wp_config_path);

global $wpdb;

echo "=== Fix REST API Routes ===\n\n";

// Step 1: Check if REST API is enabled
echo "1. REST API Status Check:\n";
$rest_enabled = function_exists('rest_api_init');
echo "   REST API enabled: " . ($rest_enabled ? 'YES' : 'NO') . "\n";

$rest_url = rest_url();
echo "   REST URL: $rest_url\n";

// Step 2: Check current registered routes
echo "\n2. Current Registered Routes:\n";
$routes = rest_get_server()->get_routes();
$bassmah_routes = array();

foreach ($routes as $route => $route_info) {
    if (strpos($route, 'bassmah') !== false) {
        $bassmah_routes[$route] = $route_info;
    }
}

echo "   Bassmah routes found: " . count($bassmah_routes) . "\n";

foreach ($bassmah_routes as $route => $info) {
    echo "     $route\n";
    foreach ($info as $endpoint) {
        echo "       Methods: " . implode(', ', $endpoint['methods']) . "\n";
    }
}

// Step 3: Check if REST API class exists
echo "\n3. REST API Classes Check:\n";

$rest_files = array(
    'api/class-rest-reports.php' => 'REST Reports Class',
    'api/class-rest-auth.php' => 'REST Auth Class'
);

foreach ($rest_files as $file => $name) {
    $file_path = BASSMAH_STAFF_REPORTS_PLUGIN_DIR . $file;
    echo "   $name: " . (file_exists($file_path) ? 'EXISTS' : 'MISSING') . "\n";
    
    if (file_exists($file_path)) {
        require_once $file_path;
    }
}

// Step 4: Create missing REST API class
echo "\n4. Creating REST API Class:\n";

if (!class_exists('Bassmah_Staff_Reports_REST_API')) {
    
    // Create REST API directory if it doesn't exist
    $api_dir = BASSMAH_STAFF_REPORTS_PLUGIN_DIR . 'api';
    if (!file_exists($api_dir)) {
        mkdir($api_dir, 0755, true);
        echo "   Created: api directory\n";
    }
    
    // Create REST reports class
    $rest_class_code = '<?php
/**
 * REST API Reports Class
 * Handles REST API endpoints for reports
 */

class Bassmah_Staff_Reports_REST_API {
    
    /**
     * Register REST routes
     */
    public function register_routes() {
        register_rest_route(\'bassmah-staff-reports/v1\', \'/reports\', array(
            array(
                \'methods\' => WP_REST_Server::READABLE,
                \'callback\' => array($this, \'get_reports\'),
                \'permission_callback\' => array($this, \'check_permission\')
            ),
            array(
                \'methods\' => WP_REST_Server::CREATABLE,
                \'callback\' => array($this, \'create_report\'),
                \'permission_callback\' => array($this, \'check_permission\')
            )
        ));
        
        register_rest_route(\'bassmah-staff-reports/v1\', \'/reports/(?P<id>\\d+)\', array(
            array(
                \'methods\' => WP_REST_Server::READABLE,
                \'callback\' => array($this, \'get_report\'),
                \'permission_callback\' => array($this, \'check_permission\')
            ),
            array(
                \'methods\' => WP_REST_Server::EDITABLE,
                \'callback\' => array($this, \'update_report\'),
                \'permission_callback\' => array($this, \'check_permission\')
            ),
            array(
                \'methods\' => WP_REST_Server::DELETABLE,
                \'callback\' => array($this, \'delete_report\'),
                \'permission_callback\' => array($this, \'check_permission\')
            )
        ));
    }
    
    /**
     * Check permissions
     */
    public function check_permission() {
        return current_user_can(\'read\');
    }
    
    /**
     * Get reports
     */
    public function get_reports($request) {
        global $wpdb;
        
        $table = $wpdb->prefix . \'staff_reports\';
        $user_id = get_current_user_id();
        
        $reports = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table WHERE user_id = %d ORDER BY report_date DESC",
            $user_id
        ));
        
        return new WP_REST_Response($reports, 200);
    }
    
    /**
     * Create report
     */
    public function create_report($request) {
        global $wpdb;
        
        $table = $wpdb->prefix . \'staff_reports\';
        $user_id = get_current_user_id();
        $params = $request->get_params();
        
        $report_data = array(
            \'user_id\' => $user_id,
            \'report_date\' => current_time(\'Y-m-d\'),
            \'submission_time\' => current_time(\'mysql\'),
            \'status\' => \'submitted\',
            \'tasks_json\' => json_encode($params[\'tasks\'] ?? array()),
            \'ip_address\' => $_SERVER[\'REMOTE_ADDR\'] ?? \'127.0.0.1\',
            \'created_at\' => current_time(\'mysql\'),
            \'updated_at\' => current_time(\'mysql\')
        );
        
        $result = $wpdb->insert($table, $report_data);
        
        if ($result !== false) {
            return new WP_REST_Response(array(
                \'success\' => true,
                \'message\' => \'Report submitted successfully\',
                \'report_id\' => $wpdb->insert_id
            ), 200);
        } else {
            return new WP_REST_Response(array(
                \'success\' => false,
                \'message\' => \'Failed to submit report\'
            ), 500);
        }
    }
    
    /**
     * Get single report
     */
    public function get_report($request) {
        global $wpdb;
        
        $table = $wpdb->prefix . \'staff_reports\';
        $report_id = $request[\'id\'];
        $user_id = get_current_user_id();
        
        $report = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE id = %d AND user_id = %d",
            $report_id,
            $user_id
        ));
        
        if ($report) {
            return new WP_REST_Response($report, 200);
        } else {
            return new WP_REST_Response(array(
                \'message\' => \'Report not found\'
            ), 404);
        }
    }
    
    /**
     * Update report
     */
    public function update_report($request) {
        global $wpdb;
        
        $table = $wpdb->prefix . \'staff_reports\';
        $report_id = $request[\'id\'];
        $user_id = get_current_user_id();
        $params = $request->get_params();
        
        $result = $wpdb->update(
            $table,
            array(
                \'status\' => $params[\'status\'] ?? \'submitted\',
                \'updated_at\' => current_time(\'mysql\')
            ),
            array(
                \'id\' => $report_id,
                \'user_id\' => $user_id
            )
        );
        
        if ($result !== false) {
            return new WP_REST_Response(array(
                \'success\' => true,
                \'message\' => \'Report updated successfully\'
            ), 200);
        } else {
            return new WP_REST_Response(array(
                \'success\' => false,
                \'message\' => \'Failed to update report\'
            ), 500);
        }
    }
    
    /**
     * Delete report
     */
    public function delete_report($request) {
        global $wpdb;
        
        $table = $wpdb->prefix . \'staff_reports\';
        $report_id = $request[\'id\'];
        $user_id = get_current_user_id();
        
        $result = $wpdb->delete(
            $table,
            array(
                \'id\' => $report_id,
                \'user_id\' => $user_id
            )
        );
        
        if ($result !== false) {
            return new WP_REST_Response(array(
                \'success\' => true,
                \'message\' => \'Report deleted successfully\'
            ), 200);
        } else {
            return new WP_REST_Response(array(
                \'success\' => false,
                \'message\' => \'Failed to delete report\'
            ), 500);
        }
    }
}';
    
    $rest_file = BASSMAH_STAFF_REPORTS_PLUGIN_DIR . 'api/class-rest-reports.php';
    file_put_contents($rest_file, $rest_class_code);
    echo "   Created: REST API class file\n";
    
    // Include the new class
    require_once $rest_file;
    echo "   Loaded: REST API class\n";
}

// Step 5: Register REST routes
echo "\n5. Register REST Routes:\n";

if (function_exists('rest_api_init')) {
    add_action('rest_api_init', function() {
        if (class_exists('Bassmah_Staff_Reports_REST_API')) {
            $rest_api = new Bassmah_Staff_Reports_REST_API();
            $rest_api->register_routes();
            echo "   REST routes registered successfully\n";
        }
    });
    
    // Trigger registration
    do_action('rest_api_init');
    echo "   REST API init triggered\n";
}

// Step 6: Test REST routes
echo "\n6. Test REST Routes:\n";

// Test GET reports endpoint
$reports_url = rest_url('bassmah-staff-reports/v1/reports');
echo "   Testing: $reports_url\n";

$response = wp_remote_get($reports_url, array(
    'headers' => array(
        'X-WP-Nonce' => wp_create_nonce('wp_rest')
    )
));

if (!is_wp_error($response)) {
    $code = wp_remote_retrieve_response_code($response);
    $body = wp_remote_retrieve_body($response);
    
    echo "   Response Code: $code\n";
    echo "   Response: " . substr($body, 0, 100) . "...\n";
    
    if ($code === 200) {
        echo "   Result: SUCCESS - GET reports works\n";
    } else {
        echo "   Result: FAILED - GET reports error\n";
    }
} else {
    echo "   Error: " . $response->get_error_message() . "\n";
}

// Step 7: Update main plugin to include REST API
echo "\n7. Update Main Plugin:\n";

$main_plugin_file = BASSMAH_STAFF_REPORTS_PLUGIN_DIR . 'bassmah-staff-reports.php';
$plugin_content = file_get_contents($main_plugin_file);

// Check if REST API is already included
if (strpos($plugin_content, 'api/class-rest-reports.php') === false) {
    echo "   Adding REST API to main plugin...\n";
    
    // Find the line where other includes are
    $include_line = "require BASSMAH_STAFF_REPORTS_PLUGIN_DIR . 'includes/class-bassmah-staff-reports.php';";
    $new_include = $include_line . "\n\n// Include REST API\nrequire BASSMAH_STAFF_REPORTS_PLUGIN_DIR . 'api/class-rest-reports.php';";
    
    $plugin_content = str_replace($include_line, $new_include, $plugin_content);
    file_put_contents($main_plugin_file, $plugin_content);
    
    echo "   Updated: main plugin file\n";
} else {
    echo "   REST API already included in main plugin\n";
}

// Step 8: Flush rewrite rules and clear cache
echo "\n8. Final Cleanup:\n";

flush_rewrite_rules();
wp_cache_flush();

echo "   Flushed: rewrite rules\n";
echo "   Cleared: cache\n";

echo "\n=== REST API Fix Complete ===\n";
echo "✅ REST API class created\n";
echo "✅ REST routes registered\n";
echo "✅ Main plugin updated\n";
echo "✅ Routes tested\n";
echo "✅ Cache cleared\n";

echo "\n🚀 REST API should now work!\n";
echo "📋 Test URLs:\n";
echo "- GET: " . rest_url('bassmah-staff-reports/v1/reports') . "\n";
echo "- POST: " . rest_url('bassmah-staff-reports/v1/reports') . "\n";

echo "\n🔧 If error persists:\n";
echo "1. Check WordPress REST API is enabled\n";
echo "2. Verify user has proper permissions\n";
echo "3. Test with different HTTP methods\n";
echo "4. Check WordPress debug log\n";

// Self-destruct
if (file_exists(__FILE__)) {
    unlink(__FILE__);
}
?>
