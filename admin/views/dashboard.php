<div class="wrap">
    <h1>Basmah Staff Reports Dashboard</h1>
    
    <?php
    global $wpdb;
    $table_name = $wpdb->prefix . 'staff_reports';
    $users_table = $wpdb->prefix . 'users';
    $today = current_time('Y-m-d');
    $current_month = date('m');
    $current_year = date('Y');
    
    $total_reports = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
    $today_reports = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table_name WHERE report_date = %s", $today));
    $monthly_reports = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table_name WHERE MONTH(report_date) = %d AND YEAR(report_date) = %d", $current_month, $current_year));
    
    $recent_reports = $wpdb->get_results("
        SELECT r.*, u.display_name
        FROM $table_name r
        JOIN $users_table u ON r.user_id = u.ID
        ORDER BY r.created_at DESC
        LIMIT 5
    ", ARRAY_A);
    ?>
    
    <div style="display: flex; gap: 20px; margin: 20px 0; flex-wrap: wrap;">
        <div style="flex: 1; min-width: 200px; background: #fff; padding: 25px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); text-align: center;">
            <div style="font-size: 2.5rem; font-weight: 700; color: #4299e1;"><?php echo esc_html($total_reports); ?></div>
            <div style="font-size: 1rem; color: #4a5568; margin-top: 8px; font-weight: 600;">Total Reports</div>
        </div>
        
        <div style="flex: 1; min-width: 200px; background: #fff; padding: 25px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); text-align: center;">
            <div style="font-size: 2.5rem; font-weight: 700; color: #48bb78;"><?php echo esc_html($today_reports); ?></div>
            <div style="font-size: 1rem; color: #4a5568; margin-top: 8px; font-weight: 600;">Today's Reports</div>
        </div>
        
        <div style="flex: 1; min-width: 200px; background: #fff; padding: 25px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); text-align: center;">
            <div style="font-size: 2.5rem; font-weight: 700; color: #ed8936;"><?php echo esc_html($monthly_reports); ?></div>
            <div style="font-size: 1rem; color: #4a5568; margin-top: 8px; font-weight: 600;">This Month</div>
        </div>
    </div>
    
    <div style="background: #fff; padding: 25px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-top: 20px;">
        <h2 style="margin-top: 0; margin-bottom: 20px; font-size: 1.3rem; color: #2d3748;">Recent Reports</h2>
        
        <?php if (empty($recent_reports)): ?>
            <p style="color: #4a5568;">No reports found.</p>
        <?php else: ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Employee</th>
                        <th>Date</th>
                        <th>Status</th>
                        <th>Submitted</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent_reports as $report): ?>
                        <?php 
                        $tasks = json_decode($report['tasks_json'], true);
                        $status = 'N/A';
                        if ($tasks && is_array($tasks)) {
                            $first_task = $tasks[0];
                            $status = isset($first_task['status']) ? $first_task['status'] : 'N/A';
                        }
                        ?>
                        <tr>
                            <td><?php echo esc_html($report['display_name']); ?></td>
                            <td><?php echo esc_html(date('F j, Y', strtotime($report['report_date']))); ?></td>
                            <td><span style="display: inline-block; padding: 4px 12px; border-radius: 12px; font-size: 0.85rem; font-weight: 500; background: #e2e8f0; color: #4a5568;"><?php echo esc_html($status); ?></span></td>
                            <td><?php echo esc_html(date('F j, Y g:i a', strtotime($report['created_at']))); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>
