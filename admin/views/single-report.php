<div class="wrap">
    <h1>Report Details</h1>
    
    <a href="<?php echo admin_url('admin.php?page=bsr-all-reports'); ?>" class="button">&larr; Back to All Reports</a>
    
    <?php if (isset($_GET['comment_saved']) && $_GET['comment_saved'] == 1): ?>
        <div class="notice notice-success is-dismissible">
            <p>Comment saved successfully!</p>
        </div>
    <?php endif; ?>
    
    <div class="bsr-single-report">
        <div class="report-info-card">
            <h2>Employee Information</h2>
            <p><strong>Name:</strong> <?php echo esc_html($report['display_name']); ?></p>
            <p><strong>Report Date:</strong> <?php echo esc_html(date('F j, Y', strtotime($report['report_date']))); ?></p>
            <p><strong>Submitted At:</strong> <?php echo esc_html(date('F j, Y g:i a', strtotime($report['created_at']))); ?></p>
        </div>
        
        <?php 
        $tasks = json_decode($report['tasks_json'], true);
        $status = 'N/A';
        if ($tasks && is_array($tasks)) {
            $first_task = $tasks[0];
            $status = isset($first_task['status']) ? $first_task['status'] : 'N/A';
        }
        ?>
        
        <div class="report-info-card">
            <h2>Status</h2>
            <span class="status-badge <?php echo sanitize_title($status); ?>"><?php echo esc_html($status); ?></span>
        </div>
        
        <div class="report-info-card">
            <h2>Tasks</h2>
            <?php if ($tasks && is_array($tasks)): ?>
                <ul class="tasks-list">
                    <?php foreach ($tasks as $task): ?>
                        <li class="task-item">
                            <div class="task-header">
                                <span class="task-category"><?php echo esc_html($task['task_category']); ?></span>
                                <span class="task-status status-badge <?php echo sanitize_title($task['status']); ?>"><?php echo esc_html($task['status']); ?></span>
                            </div>
                            <p class="task-description"><?php echo esc_html($task['task_description']); ?></p>
                            <p class="task-next-action"><strong>Next Action:</strong> <?php echo esc_html($task['next_action']); ?></p>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p>No tasks found.</p>
            <?php endif; ?>
        </div>
        
        <div class="report-info-card">
            <h2>Manager Comment</h2>
            <form method="post" action="">
                <?php wp_nonce_field('bassmah_save_comment', 'bassmah_comment_nonce'); ?>
                <input type="hidden" name="report_id" value="<?php echo esc_attr($report['id']); ?>">
                
                <div class="form-field">
                    <label for="manager_comment">Add Comment:</label>
                    <textarea id="manager_comment" name="manager_comment" rows="4" style="width: 100%;"></textarea>
                </div>
                
                <p class="submit">
                    <button type="submit" name="bassmah_save_comment" class="button button-primary">Save Comment</button>
                </p>
            </form>
        </div>
    </div>
</div>
