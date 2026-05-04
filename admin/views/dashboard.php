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
    $pending_reports = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE status = 'pending'");
    
    $recent_reports = $wpdb->get_results("
        SELECT r.*, u.display_name
        FROM $table_name r
        JOIN $users_table u ON r.user_id = u.ID
        ORDER BY r.created_at DESC
        LIMIT 5
    ", ARRAY_A);
    ?>
    
    <div class="bsr-admin-container">
        <div style="display: flex; gap: 24px; margin: 24px 0; flex-wrap: wrap;">
            <div style="flex: 1; min-width: 220px; background: linear-gradient(135deg, #4299e1 0%, #3182ce 100%); padding: 32px; border-radius: 16px; box-shadow: 0 4px 16px rgba(66, 153, 225, 0.3); text-align: center;">
                <div style="font-size: 3rem; font-weight: 700; color: #fff;"><?php echo esc_html($total_reports); ?></div>
                <div style="font-size: 1.1rem; color: rgba(255,255,255,0.95); margin-top: 12px; font-weight: 600;">Total Reports</div>
            </div>
            
            <div style="flex: 1; min-width: 220px; background: linear-gradient(135deg, #48bb78 0%, #38a169 100%); padding: 32px; border-radius: 16px; box-shadow: 0 4px 16px rgba(72, 187, 120, 0.3); text-align: center;">
                <div style="font-size: 3rem; font-weight: 700; color: #fff;"><?php echo esc_html($today_reports); ?></div>
                <div style="font-size: 1.1rem; color: rgba(255,255,255,0.95); margin-top: 12px; font-weight: 600;">Today's Reports</div>
            </div>
            
            <div style="flex: 1; min-width: 220px; background: linear-gradient(135deg, #ed8936 0%, #dd6b20 100%); padding: 32px; border-radius: 16px; box-shadow: 0 4px 16px rgba(237, 137, 54, 0.3); text-align: center;">
                <div style="font-size: 3rem; font-weight: 700; color: #fff;"><?php echo esc_html($monthly_reports); ?></div>
                <div style="font-size: 1.1rem; color: rgba(255,255,255,0.95); margin-top: 12px; font-weight: 600;">This Month</div>
            </div>
            
            <div style="flex: 1; min-width: 220px; background: linear-gradient(135deg, #ed64a6 0%, #d53f8c 100%); padding: 32px; border-radius: 16px; box-shadow: 0 4px 16px rgba(237, 100, 166, 0.3); text-align: center;">
                <div style="font-size: 3rem; font-weight: 700; color: #fff;"><?php echo esc_html($pending_reports); ?></div>
                <div style="font-size: 1.1rem; color: rgba(255,255,255,0.95); margin-top: 12px; font-weight: 600;">Pending Review</div>
            </div>
        </div>
        
        <div class="report-info-card">
            <h2 style="margin-top: 0; margin-bottom: 24px; font-size: 1.5rem; color: #1a202c; border-bottom: 2px solid #e2e8f0; padding-bottom: 16px; font-weight: 700;">Recent Reports</h2>
            
            <?php if (empty($recent_reports)): ?>
                <p style="color: #4a5568; font-size: 1.1rem; padding: 20px 0;">No reports found.</p>
            <?php else: ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th style="font-weight: 700;">Employee</th>
                            <th style="font-weight: 700;">Date</th>
                            <th style="font-weight: 700;">Status</th>
                            <th style="font-weight: 700;">Submitted</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_reports as $report): ?>
                            <tr>
                                <td><strong><?php echo esc_html($report['display_name']); ?></strong></td>
                                <td><?php echo esc_html(date('F j, Y', strtotime($report['report_date']))); ?></td>
                                <td>
                                    <span class="status-badge <?php echo sanitize_title($report['status']); ?>">
                                        <?php echo esc_html(ucfirst($report['status'])); ?>
                                    </span>
                                </td>
                                <td><?php echo esc_html(date('F j, Y g:i a', strtotime($report['created_at']))); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</div>
