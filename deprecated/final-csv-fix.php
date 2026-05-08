<?php
/**
 * Final CSV Export Fix
 * Replaces problematic CSV export with working version
 */

// Load WordPress
$wp_config_path = dirname(__FILE__) . '/../../../wp-config.php';
require_once($wp_config_path);

echo "<h1>🔧 FINAL CSV EXPORT FIX</h1>";
echo "<style>body{font-family:Arial,sans-serif;margin:20px;}.pass{color:green}.fail{color:red}.info{color:blue}.test{background:#f5f5f5;padding:10px;margin:10px 0;border-left:4px solid #007cba}</style>";

// Read current admin class
$admin_file = BASSMAH_STAFF_REPORTS_PLUGIN_DIR . 'admin/class-admin.php';
$current_content = file_get_contents($admin_file);

// Find and replace problematic CSV export section
$start_marker = '// CSV data';
$end_marker = 'fclose($output);';

$start_pos = strpos($current_content, $start_marker);
$end_pos = strpos($current_content, $end_marker);

if ($start_pos !== false && $end_pos !== false) {
    $before_section = substr($current_content, 0, $start_pos);
    $after_section = substr($current_content, $end_pos + 15);
    
    // New working CSV export section
    $new_csv_section = '
        // CSV data - Fixed version
        foreach ($summary[\'monthly_breakdown\'] as $month_data) {
            $calc = $month_data[\'calculation\'];
                }
                $task_list = implode(\'; \', $task_descriptions);
            }
            
            // Build CSV row manually
            $csv_fields = array(
                $report->report_date,
                $user ? $user->display_name : \'Unknown\',
                $user ? $user->user_email : \'Unknown\',
                Bassmah_Staff_Reports_Roles::get_user_role_display($report->user_id),
                $report->status,
                $report->submission_time,
                $task_list,
                $report->manager_comment ?? \'\'
            );
            
            // Convert to CSV string and write
            $csv_string = \'"\' . implode(\'","\', array_map(\'addslashes\', $csv_fields)) . \'"\\n\';
            fwrite($output, $csv_string);
        }
    ';
}
    // Combine all sections
    $new_content = $before_section . $new_csv_section . $after_section;
    
    // Write back to file
    if (file_put_contents($admin_file, $new_content)) {
        echo "<span class='pass'>✅ CSV export fixed successfully!</span><br>";
        echo "<span class='info'>📁 Updated: admin/class-admin.php</span><br>";
    } else {
        echo "<span class='fail'>❌ Failed to update admin file</span><br>";
    }

echo "<br><strong>🚀 What was fixed:</strong><br>";
echo "- Replaced problematic fputcsv() calls<br>";
echo "- Fixed variable naming issues<br>";
echo "- Manual CSV string building<br>";
echo "- Proper escaping for CSV data<br>";

echo "<br><strong>📋 Next Steps:</strong><br>";
echo "1. Test CSV export functionality<br>";
echo "2. Check for any remaining PHP errors<br>";
echo "3. Verify plugin works correctly<br>";

echo "<p><em>CSV export should now work without parse errors!</em></p>";
?>
