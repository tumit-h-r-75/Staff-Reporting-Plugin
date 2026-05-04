<div class="wrap">
    <h1>All Reports</h1>
    
    <div class="bsr-admin-container">
        <div style="background: #fff; padding: 25px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-bottom: 20px;">
            <h2 style="margin-top: 0; margin-bottom: 20px; font-size: 1.3rem; color: #2d3748;">Filters</h2>
            
            <form method="get" action="" style="display: flex; gap: 15px; flex-wrap: wrap; align-items: end;">
                <input type="hidden" name="page" value="bsr-all-reports">
                
                <div style="flex: 1; min-width: 200px;">
                    <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #2d3748;">Employee</label>
                    <select name="user_id" style="width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 8px;">
                        <option value="">All Employees</option>
                        <?php foreach ($staff_users as $user): ?>
                            <option value="<?php echo esc_attr($user->ID); ?>" <?php echo isset($_GET['user_id']) && $_GET['user_id'] == $user->ID ? 'selected' : ''; ?>>
                                <?php echo esc_html($user->display_name); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div style="flex: 1; min-width: 150px;">
                    <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #2d3748;">Status</label>
                    <select name="status" style="width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 8px;">
                        <option value="">All Status</option>
                        <option value="pending" <?php echo isset($_GET['status']) && $_GET['status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="approved" <?php echo isset($_GET['status']) && $_GET['status'] == 'approved' ? 'selected' : ''; ?>>Approved</option>
                        <option value="rejected" <?php echo isset($_GET['status']) && $_GET['status'] == 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                    </select>
                </div>
                
                <div style="flex: 1; min-width: 150px;">
                    <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #2d3748;">From Date</label>
                    <input type="date" name="date_from" value="<?php echo isset($_GET['date_from']) ? esc_attr($_GET['date_from']) : ''; ?>" style="width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 8px;">
                </div>
                
                <div style="flex: 1; min-width: 150px;">
                    <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #2d3748;">To Date</label>
                    <input type="date" name="date_to" value="<?php echo isset($_GET['date_to']) ? esc_attr($_GET['date_to']) : ''; ?>" style="width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 8px;">
                </div>
                
                <button type="submit" class="button button-primary">Filter</button>
                <a href="<?php echo admin_url('admin.php?page=bsr-all-reports'); ?>" class="button">Reset</a>
                <a href="<?php echo add_query_arg('bsr_export', '1'); ?>" class="button button-secondary">Export CSV</a>
            </form>
        </div>
        
        <div style="background: #fff; padding: 25px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
            <?php if (empty($reports)): ?>
                <p style="color: #4a5568;">No reports found.</p>
            <?php else: ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Employee Name</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th>Submitted At</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reports as $report): ?>
                            <?php 
                            $view_url = admin_url('admin.php?page=bsr-single-report&report_id=' . $report['id']);
                            ?>
                            <tr>
                                <td><?php echo esc_html($report['id']); ?></td>
                                <td><strong><?php echo esc_html($report['display_name']); ?></strong></td>
                                <td><?php echo esc_html(date('F j, Y', strtotime($report['report_date']))); ?></td>
                                <td>
                                    <span class="status-badge <?php echo sanitize_title($report['status']); ?>">
                                        <?php echo esc_html(ucfirst($report['status'])); ?>
                                    </span>
                                </td>
                                <td><?php echo esc_html(date('F j, Y g:i a', strtotime($report['created_at']))); ?></td>
                                <td>
                                    <a href="<?php echo esc_url($view_url); ?>" class="button button-small">View</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</div>
