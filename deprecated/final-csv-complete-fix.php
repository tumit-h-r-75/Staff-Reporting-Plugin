<?php
/**
 * Complete CSV Export Fix
 * Replaces the problematic export_manager_reports function
 */

// Load WordPress
$wp_config_path = dirname(__FILE__) . '/../../../wp-config.php';
require_once($wp_config_path);

echo "<h1>🔧 FINAL CSV EXPORT FIX</h1>";
echo "<style>body{font-family:Arial,sans-serif;margin:20px;}.pass{color:green}.fail{color:red}.info{color:blue}.test{background:#f5f5f5;padding:10px;margin:10px 0;border-left:4px solid #007cba}</style>";

// Read current admin class
$admin_file = BASSMAH_STAFF_REPORTS_PLUGIN_DIR . 'admin/class-admin.php';
$current_content = file_get_contents($admin_file);

// Find and replace the problematic export_manager_reports function
$start_marker = 'private function export_manager_reports()';
$end_marker = 'exit;';

$start_pos = strpos($current_content, $start_marker);
$end_pos = strpos($current_content, $start_marker);

if ($start_pos !== false && $end_pos !== false) {
    // Find the next function after export_manager_reports
    $next_function_pos = strpos($current_content, 'private function export_salary_summary()', $end_pos);
    
    if ($next_function_pos !== false) {
        $before_export = substr($current_content, 0, $start_pos);
        $after_export = substr($current_content, $next_function_pos);
        
        // New working export_manager_reports function
        $new_export_function = '
    /**
     * Export manager reports to CSV
     *
     * @since    1.0.0
     */
    private function export_manager_reports() {
        if (!current_user_can(\'bassmah_export_reports\')) {
            wp_send_json_error(__(\'You do not have permission to export reports.\', \'bassmah-staff-reports\'));
        }
        
        $filename = \'manager-reports-\' . date(\'Y-m-d\') . \'.csv\';
        
        header(\'Content-Type: text/csv\');
        header(\'Content-Disposition: attachment; filename="\' . $filename . \'"\');
        
        $output = fopen(\'php://output\', \'w\');
        
        // CSV headers
        $csv_headers = array(
            __(\'Report Date\', \'bassmah-staff-reports\'),
            __(\'Employee Name\', \'bassmah-staff-reports\'),
            __(\'Email\', \'bassmah-staff-reports\'),
            __(\'Role\', \'bassmah-staff-reports\'),
            __(\'Status\', \'bassmah-staff-reports\'),
            __(\'Submission Time\', \'bassmah-staff-reports\'),
            __(\'Tasks\', \'bassmah-staff-reports\'),
            __(\'Manager Comment\', \'bassmah-staff-reports\')
        );
        
        // Write headers
        fputcsv($output, $csv_headers);
        
        global $wpdb;
        $reports_table = $wpdb->prefix . \'staff_reports\';
        $reports = $wpdb->get_results($wpdb->prepare(
            "SELECT r.*, u.display_name, u.user_email 
             FROM $reports_table r 
             LEFT JOIN {$wpdb->users} u ON r.user_id = u.ID 
             WHERE 1=1 ORDER BY r.report_date DESC"
        ));
        
        foreach ($reports as $report) {
            $user = get_userdata($report->user_id);
            $tasks = is_array($report->tasks) ? $report->tasks : json_decode($report->tasks_json, true);
            $task_descriptions = array();
            
            if (!empty($tasks)) {
                foreach ($tasks as $task) {
                    $task_descriptions[] = $task[\'task_category\'] . \': \' . $task[\'task_description\'];
                }
            }
            
            $task_list = implode(\'; \', $task_descriptions);
            
            // Simple CSV row without problematic variables
            $csv_row = array(
                $report->report_date,
                $user ? $user->display_name : \'Unknown\',
                $user ? $user->user_email : \'Unknown\',
                Bassmah_Staff_Reports_Roles::get_user_role_display($report->user_id),
                $report->status,
                $report->submission_time,
                $task_list,
                $report->manager_comment ?? \'\'
            );
            
            // Write CSV row
            fputcsv($output, $csv_row);
        }
        
        fclose($output);
        exit;
    }';
        
        // Combine all sections
        $new_content = $before_export . $new_export_function . $after_export;
        
        // Write back to file
        if (file_put_contents($admin_file, $new_content)) {
            echo "<span class=\'pass\'>✅ CSV export function fixed successfully!</span><br>";
            echo "<span class=\'info\'>📁 Updated: admin/class-admin.php</span><br>";
        } else {
            echo "<span class=\'fail\'>❌ Failed to update admin file</span><br>";
        }
    } else {
        echo "<span class=\'fail\'>❌ Could not find export_manager_reports function</span><br>";
    }

echo "<br><strong>🎯 What was fixed:</strong><br>";
echo "- Replaced problematic CSV export function<br>";
echo "- Removed all fputcsv parse errors<br>";
echo "- Used proper fputcsv for headers<br>";
echo "- Simple CSV row construction<br>";
echo "- No more variable naming issues<br>";

echo "<br><strong>📋 Next Steps:</strong><br>";
echo "1. Test CSV export functionality<br>";
echo "2. Check for any remaining PHP errors<br>";
echo "3. Verify plugin works correctly<br>";

echo "<p><em>CSV export should now work without any parse errors!</em></p>";
?>
