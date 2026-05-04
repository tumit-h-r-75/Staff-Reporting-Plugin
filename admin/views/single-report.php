<div class="wrap">
    <h1>Report Details</h1>
    
    <a href="<?php echo admin_url('admin.php?page=bsr-all-reports'); ?>" class="button">&larr; Back to All Reports</a>
    
    <?php if (isset($_GET['comment_saved']) && $_GET['comment_saved'] == 1): ?>
        <div class="notice notice-success is-dismissible">
            <p>Comment saved successfully!</p>
        </div>
    <?php endif; ?>
    
    <?php if (isset($_GET['report_approved']) && $_GET['report_approved'] == 1): ?>
        <div class="notice notice-success is-dismissible">
            <p>Report approved successfully!</p>
        </div>
    <?php endif; ?>
    
    <?php if (isset($_GET['report_rejected']) && $_GET['report_rejected'] == 1): ?>
        <div class="notice notice-error is-dismissible">
            <p>Report rejected!</p>
        </div>
    <?php endif; ?>
    
    <?php if (isset($_GET['comment_required']) && $_GET['comment_required'] == 1): ?>
        <div class="notice notice-error is-dismissible">
            <p><strong>Error:</strong> A comment is required when rejecting a report!</p>
        </div>
    <?php endif; ?>
    
    <div class="bsr-single-report">
        <div class="report-info-card">
            <h2>Employee Information</h2>
            <p><strong>Name:</strong> <?php echo esc_html($report['display_name']); ?></p>
            <p><strong>Report Date:</strong> <?php echo esc_html(date('F j, Y', strtotime($report['report_date']))); ?></p>
            <p><strong>Submitted At:</strong> <?php echo esc_html(date('F j, Y g:i a', strtotime($report['created_at']))); ?></p>
            <p><strong>Current Status:</strong> 
                <span class="status-badge <?php echo sanitize_title($report['status']); ?>">
                    <?php echo esc_html(ucfirst($report['status'])); ?>
                </span>
            </p>
        </div>
        
        <?php 
        $tasks = json_decode($report['tasks_json'], true);
        ?>
        
        <div class="report-info-card">
            <h2>Tasks</h2>
            <?php if ($tasks && is_array($tasks)): ?>
                <ul class="tasks-list">
                    <?php foreach ($tasks as $task): ?>
                        <li class="task-item">
                            <div class="task-header">
                                <span class="task-category"><?php echo isset($task['task_category']) ? esc_html($task['task_category']) : 'N/A'; ?></span>
                                <span class="task-status status-badge <?php echo isset($task['status']) ? sanitize_title($task['status']) : 'n-a'; ?>">
                                    <?php echo isset($task['status']) ? esc_html($task['status']) : 'N/A'; ?>
                                </span>
                            </div>
                            <p class="task-description"><strong>Task:</strong> <?php echo isset($task['task_description']) ? esc_html($task['task_description']) : 'N/A'; ?></p>
                            <?php if (isset($task['next_action']) && !empty($task['next_action'])): ?>
                                <p class="task-next-action"><strong>Next Action:</strong> <?php echo esc_html($task['next_action']); ?></p>
                            <?php endif; ?>
                            <?php if (isset($task['manager_assigned_task']) && !empty($task['manager_assigned_task'])): ?>
                                <p class="task-next-action"><strong>Manager Assigned:</strong> <?php echo esc_html($task['manager_assigned_task']); ?></p>
                            <?php endif; ?>
                            <?php if (isset($task['additional_notes']) && !empty($task['additional_notes'])): ?>
                                <p class="task-next-action"><strong>Additional Notes:</strong> <?php echo esc_html($task['additional_notes']); ?></p>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p>No tasks found.</p>
            <?php endif; ?>
        </div>
        
        <?php if ($report['manager_comment']): ?>
            <div class="report-info-card">
                <h2>Manager Comment</h2>
                <p style="background: #f7fafc; padding: 15px; border-radius: 8px; border-left: 4px solid #4299e1; margin: 0;">
                    <?php echo esc_html($report['manager_comment']); ?>
                </p>
            </div>
        <?php endif; ?>
        
        <div class="report-info-card">
            <h2>Actions</h2>
            <div style="display: flex; gap: 15px; flex-wrap: wrap; margin-bottom: 25px;">
                <?php if ($report['status'] !== 'approved'): ?>
                    <form method="post" action="">
                        <?php wp_nonce_field('bsr_approve_report', 'bsr_approve_nonce'); ?>
                        <input type="hidden" name="report_id" value="<?php echo esc_attr($report['id']); ?>">
                        <button type="submit" name="bsr_approve_report" class="button button-primary" style="background: linear-gradient(135deg, #48bb78 0%, #38a169 100%); border-color: #38a169;">
                            Approve Report
                        </button>
                    </form>
                <?php endif; ?>
                
                <?php if ($report['status'] !== 'rejected'): ?>
                    <form method="post" action="">
                        <?php wp_nonce_field('bsr_reject_report', 'bsr_reject_nonce'); ?>
                        <input type="hidden" name="report_id" value="<?php echo esc_attr($report['id']); ?>">
                        <button type="submit" name="bsr_reject_report" class="button" style="background: linear-gradient(135deg, #f56565 0%, #c53030 100%); color: white; border-color: #c53030;">
                            Reject Report
                        </button>
                    </form>
                <?php endif; ?>
            </div>
            
            <h3 style="margin-bottom: 15px;">Add Comment</h3>
            <form method="post" action="">
                <?php wp_nonce_field('bsr_save_comment', 'bsr_comment_nonce'); ?>
                <input type="hidden" name="report_id" value="<?php echo esc_attr($report['id']); ?>">
                
                <div class="form-field">
                    <label for="manager_comment">Comment:</label>
                    <textarea id="manager_comment" name="manager_comment" rows="4" style="width: 100%;"><?php echo esc_textarea($report['manager_comment']); ?></textarea>
                </div>
                
                <p class="submit">
                    <button type="submit" name="bsr_save_comment" class="button button-primary">Save Comment</button>
                </p>
            </form>
        </div>
    </div>
</div>
