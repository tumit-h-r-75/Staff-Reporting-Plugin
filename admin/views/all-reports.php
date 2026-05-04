<div class="wrap">
    <h1>All Reports</h1>
    
    <?php if (empty($reports)): ?>
        <p>No reports found.</p>
    <?php else: ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>Employee Name</th>
                    <th>Date</th>
                    <th>Task Summary</th>
                    <th>Status</th>
                    <th>Submitted At</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($reports as $report): ?>
                    <?php 
                    $tasks = json_decode($report['tasks_json'], true);
                    $task_summary = '';
                    $status = 'N/A';
                    if ($tasks && is_array($tasks)) {
                        $first_task = $tasks[0];
                        $task_summary = isset($first_task['task_description']) ? substr($first_task['task_description'], 0, 80) . '...' : '';
                        $status = isset($first_task['status']) ? $first_task['status'] : 'N/A';
                    }
                    ?>
                    <tr>
                        <td><?php echo esc_html($report['display_name']); ?></td>
                        <td><?php echo esc_html(date('F j, Y', strtotime($report['report_date']))); ?></td>
                        <td><?php echo esc_html($task_summary); ?></td>
                        <td><span class="status-badge <?php echo sanitize_title($status); ?>"><?php echo esc_html($status); ?></span></td>
                        <td><?php echo esc_html(date('F j, Y g:i a', strtotime($report['created_at']))); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
