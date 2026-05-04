<?php
class Basmah_Staff_Reports_Public {
    public function __construct() {
        add_shortcode('bsr_staff_dashboard', array($this, 'render_staff_dashboard'));
        add_shortcode('bassmah_staff_dashboard', array($this, 'render_bassmah_staff_dashboard'));
        add_shortcode('bsr_report_form', array($this, 'render_report_form'));
        add_shortcode('bassmah_report_form', array($this, 'render_bassmah_report_form'));
        add_shortcode('bsr_my_reports', array($this, 'render_my_reports'));
        add_shortcode('bassmah_my_reports', array($this, 'render_bassmah_my_reports'));
        add_shortcode('bsr_salary_view', array($this, 'render_salary_view'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_public_assets'));
        add_action('init', array($this, 'handle_report_submission'));
    }
    
    public function enqueue_public_assets() {
        wp_enqueue_style('bsr-public-css', BASMAH_STAFF_REPORTS_PLUGIN_URL . 'public/assets/style.css');
        wp_enqueue_script('bsr-public-js', BASMAH_STAFF_REPORTS_PLUGIN_URL . 'public/assets/script.js', array('jquery'), null, true);
    }
    
    public function render_staff_dashboard() {
        ob_start();
        require_once BASMAH_STAFF_REPORTS_PLUGIN_DIR . 'public/views/dashboard.php';
        return ob_get_clean();
    }

    public function render_bassmah_staff_dashboard() {
        if (!is_user_logged_in()) {
            return '<p style="padding: 20px; background: #fff; border-radius: 8px; text-align: center;">Please log in to view your dashboard.</p>';
        }
        
        $is_admin = current_user_can('manage_options');
        $can_view = current_user_can('basmah_view_my_salary') || $is_admin;
        
        if (!$can_view) {
            return '<p style="padding: 20px; background: #fff; border-radius: 8px; text-align: center;">You do not have permission to view the dashboard.</p>';
        }

        $user_id = get_current_user_id();
        $today = current_time('Y-m-d');
        $current_month = date('m');
        $current_year = date('Y');

        global $wpdb;
        $first_of_month = sprintf('%04d-%02d-01', $current_year, $current_month);
        $last_of_month = date('Y-m-t');
        $monthly_reports = Basmah_Staff_Reports_Reports::get_reports(array(
            'user_id' => $user_id,
            'date_from' => $first_of_month,
            'date_to' => $last_of_month
        ));
        $calendar_status = array();
        foreach ($monthly_reports as $item) {
            $calendar_status[$item['report_date']] = $item['status'];
        }
        $table_name = $wpdb->prefix . 'staff_reports';

        $today_report = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table_name WHERE user_id = %d AND report_date = %s",
            $user_id,
            $today
        ));

        $salary_data = Basmah_Staff_Reports_Salary::calculate_salary($user_id, $current_month, $current_year);

        ob_start();
        ?>
        <div class="bsr-staff-dashboard">
            <h2>My Dashboard</h2>

            <div class="dashboard-section">
                <h3>Today's Report Status</h3>
                <div class="status-card <?php echo $today_report ? 'submitted' : 'missing'; ?>">
                    <?php if ($today_report): ?>
                        <p class="status-text submitted">Report Submitted</p>
                        <p class="status-date"><?php echo esc_html($today); ?></p>
                    <?php else: ?>
                        <p class="status-text missing">Report Missing</p>
                        <p class="status-date"><?php echo esc_html($today); ?></p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="dashboard-section">
                <h3>Monthly Summary</h3>
                <div class="summary-cards">
                    <div class="summary-card" style="background: linear-gradient(135deg, #ebf8ff 0%, #90cdf4 100%); border: 2px solid #4299e1;">
                        <p class="summary-number"><?php echo esc_html($salary_data['submitted_days']); ?></p>
                        <p class="summary-label">Days Submitted</p>
                    </div>
                    <div class="summary-card" style="background: linear-gradient(135deg, #c6f6d5 0%, #9ae6b4 100%); border: 2px solid #48bb78;">
                        <p class="summary-number"><?php echo esc_html($salary_data['approved_days']); ?></p>
                        <p class="summary-label">Days Approved</p>
                    </div>
                    <div class="summary-card" style="background: linear-gradient(135deg, #fed7d7 0%, #fc8181 100%); border: 2px solid #f56565;">
                        <p class="summary-number"><?php echo esc_html($salary_data['rejected_days']); ?></p>
                        <p class="summary-label">Days Rejected</p>
                    </div>
                    <div class="summary-card" style="background: linear-gradient(135deg, #e2e8f0 0%, #cbd5e0 100%); border: 2px solid #a0aec0;">
                        <p class="summary-number"><?php echo esc_html($salary_data['missing_days']); ?></p>
                        <p class="summary-label">Days Missing</p>
                    </div>
                    <div class="summary-card">
                        <p class="summary-number"><?php echo esc_html($salary_data['working_days']); ?></p>
                        <p class="summary-label">Total Working Days</p>
                    </div>
                </div>
            </div>

            <div class="dashboard-section">
                <h3>Salary Overview</h3>
                <div class="salary-card">
                    <p class="salary-label">Current Month Earnings</p>
                    <p class="salary-amount"><?php echo esc_html($salary_data['currency']); ?> <?php echo number_format($salary_data['net_salary'], 2); ?></p>
                    <div style="margin-top: 20px; opacity: 0.95;">
                        <p style="margin: 8px 0;">Monthly Salary: <?php echo esc_html($salary_data['currency']); ?> <?php echo number_format($salary_data['monthly_salary'], 2); ?></p>
                        <p style="margin: 8px 0;">Daily Rate: <?php echo esc_html($salary_data['currency']); ?> <?php echo number_format($salary_data['daily_rate'], 2); ?></p>
                        <p style="margin: 8px 0;">Total Deduction: <?php echo esc_html($salary_data['currency']); ?> <?php echo number_format($salary_data['total_deduction'], 2); ?></p>
                    </div>
                </div>
            </div>

            <div class="dashboard-section">
                <h3>Monthly Report Calendar</h3>
                <div class="calendar-grid">
                    <?php
                    $days_in_month = cal_days_in_month(CAL_GREGORIAN, $current_month, $current_year);
                    $weekdays = array('Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat');
                    ?>
                    <div class="calendar-header">
                        <?php foreach ($weekdays as $weekday): ?>
                            <div class="calendar-cell calendar-header-cell"><?php echo esc_html($weekday); ?></div>
                        <?php endforeach; ?>
                    </div>
                    <div class="calendar-body">
                        <?php
                        $first_weekday = date('w', strtotime($first_of_month));
                        for ($empty = 0; $empty < $first_weekday; $empty++): ?>
                            <div class="calendar-cell calendar-empty"></div>
                        <?php endfor; ?>

                        <?php for ($day = 1; $day <= $days_in_month; $day++):
                            $date = sprintf('%04d-%02d-%02d', $current_year, $current_month, $day);
                            $status = isset($calendar_status[$date]) ? $calendar_status[$date] : 'missing';
                            $status_class = $status === 'approved' ? 'approved' : ($status === 'pending' ? 'pending' : ($status === 'rejected' ? 'rejected' : 'missing'));
                            $is_working_day = Basmah_Staff_Reports_Working_Days::is_working_day($date);
                        ?>
                            <div class="calendar-cell calendar-day <?php echo esc_attr($status_class); ?><?php echo $is_working_day ? '' : ' holiday'; ?>">
                                <span class="day-number"><?php echo esc_html($day); ?></span>
                                <span class="day-status"><?php echo esc_html(ucfirst($status)); ?></span>
                            </div>
                        <?php endfor; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    public function render_report_form() {
        return $this->render_bassmah_report_form();
    }
    
    public function render_my_reports() {
        ob_start();
        require_once BASMAH_STAFF_REPORTS_PLUGIN_DIR . 'public/views/my-reports.php';
        return ob_get_clean();
    }

    public function render_bassmah_my_reports() {
        if (!is_user_logged_in()) {
            return '<p style="padding: 20px; background: #fff; border-radius: 8px; text-align: center;">Please log in to view your reports.</p>';
        }
        
        $is_admin = current_user_can('manage_options');
        $can_view = current_user_can('basmah_view_my_reports') || $is_admin;
        
        if (!$can_view) {
            return '<p style="padding: 20px; background: #fff; border-radius: 8px; text-align: center;">You do not have permission to view reports.</p>';
        }

        $user_id = get_current_user_id();
        $reports = Basmah_Staff_Reports_Reports::get_reports(array('user_id' => $user_id));

        ob_start();
        ?>
        <div class="bsr-my-reports">
            <h2>My Reports</h2>
            <?php if (empty($reports)): ?>
                <p>No reports found.</p>
            <?php else: ?>
                <table class="reports-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Status</th>
                            <th>Submitted At</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reports as $report): ?>
                            <tr>
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
        <?php
        return ob_get_clean();
    }
    
    public function render_salary_view() {
        ob_start();
        require_once BASMAH_STAFF_REPORTS_PLUGIN_DIR . 'public/views/salary-view.php';
        return ob_get_clean();
    }

    public function render_bassmah_report_form() {
        if (!is_user_logged_in()) {
            return '<p style="padding: 20px; background: #fff; border-radius: 8px; text-align: center;">Please log in to submit a report.</p>';
        }

        $current_user = wp_get_current_user();
        $user_id = get_current_user_id();
        
        // Allow admin (manage_options) to see all reports/forms, but restrict staff to submission only
        $is_admin = current_user_can('manage_options');
        $is_staff = current_user_can('basmah_submit_report');
        
        if (!$is_admin && !$is_staff) {
            return '<p style="padding: 20px; background: #fff; border-radius: 8px; text-align: center;">You do not have permission to submit reports.</p>';
        }
        $report_date = current_time('Y-m-d');
        $existing_report = Basmah_Staff_Reports_Reports::report_exists($user_id, $report_date);
        
        $report = null;
        if ($existing_report) {
            $report = Basmah_Staff_Reports_Reports::get_report($existing_report);
        }

        ob_start();
        ?>
        <div class="bsr-report-form">
            <h2>Submit Daily Work Report</h2>
            
            <?php if (isset($_GET['report_submitted']) && $_GET['report_submitted'] == 1 && !isset($_GET['duplicate_report'])): ?>
                <div class="notification-popup" style="border-left: 4px solid #48bb78; background: #c6f6d5; padding: 30px; border-radius: 10px; margin-bottom: 20px;">
                    <p style="margin: 0; font-size: 1.2rem; font-weight: 700; color: #276749; line-height: 1.6;">
                        ✅ Your report for today has been submitted successfully!
                    </p>
                    <p style="margin: 15px 0 0 0; font-size: 1.05rem; color: #22543d;">
                        Your task submission has been saved. You can submit only one report per day. To view your submission status, check back tomorrow or visit your reports history.
                    </p>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_GET['duplicate_report']) && $_GET['duplicate_report'] == 1): ?>
                <div class="notification-popup" style="border-left: 4px solid #f56565; background: #fed7d7; padding: 20px; border-radius: 10px; margin-bottom: 20px;">
                    <p style="margin: 0; font-size: 1.05rem; font-weight: 600; color: #c53030;">
                        ⚠️ You have already submitted a report for today!
                    </p>
                </div>
            <?php endif; ?>
            
            <?php if ($existing_report && $report): ?>
                <div class="submission-status-card" style="border-left: 5px solid <?php echo $report['status'] == 'approved' ? '#48bb78' : ($report['status'] == 'rejected' ? '#f56565' : '#ed8936'); ?>; background: <?php echo $report['status'] == 'approved' ? '#c6f6d5' : ($report['status'] == 'rejected' ? '#fed7d7' : '#feebc8'); ?>; padding: 30px; border-radius: 12px; margin-bottom: 20px;">
                    <h3 style="margin-top: 0; margin-bottom: 15px; color: <?php echo $report['status'] == 'approved' ? '#276749' : ($report['status'] == 'rejected' ? '#c53030' : '#c05621'); ?>; font-size: 1.2rem;">
                        <?php if ($report['status'] == 'pending'): ?>
                            ⏳ Your report for today is pending review
                        <?php elseif ($report['status'] == 'approved'): ?>
                            ✅ Your report for today has been approved!
                        <?php elseif ($report['status'] == 'rejected'): ?>
                            ❌ Your report for today was rejected
                        <?php endif; ?>
                    </h3>
                    <p style="margin: 10px 0; color: #2d3748; font-weight: 600;">
                        <strong>Submitted:</strong> <?php echo esc_html(date('F j, Y g:i a', strtotime($report['created_at']))); ?>
                    </p>
                    <?php if ($report['manager_comment']): ?>
                        <div style="margin-top: 15px; padding: 15px; background: rgba(255,255,255,0.5); border-radius: 8px;">
                            <p style="margin: 0 0 8px 0; color: #2d3748; font-weight: 600;">Manager Comment:</p>
                            <p style="margin: 0; color: #2d3748;"><?php echo esc_html($report['manager_comment']); ?></p>
                        </div>
                    <?php endif; ?>
                </div>
                <div style="background: #f0f4f8; padding: 20px; border-radius: 12px; text-align: center;">
                    <p style="margin: 0; font-size: 1.05rem; color: #2d3748;">
                        <strong>You can submit only one report per day.</strong> Your form is locked for today.
                    </p>
                </div>
            <?php elseif (!$existing_report && current_user_can('basmah_submit_report')): ?>
                <form method="post" action="">
                    <?php wp_nonce_field('bassmah_report_submit', 'bassmah_report_nonce'); ?>
                    
                    <div class="form-group">
                        <label>Employee Name</label>
                        <input type="text" value="<?php echo esc_attr($current_user->display_name); ?>" readonly>
                    </div>
                    
                    <div class="form-group">
                        <label>Role</label>
                        <input type="text" value="<?php echo esc_attr(implode(', ', array_map(function($role) { return ucwords(str_replace(array('_', '-'), ' ', $role)); }, $current_user->roles))); ?>" readonly>
                    </div>
                    
                    <div class="form-group">
                        <label>Date</label>
                        <input type="date" name="report_date" value="<?php echo esc_attr($report_date); ?>" readonly>
                    </div>
                    
                    <div id="tasks_container">
                        <div class="task-item-form" style="background: #f7fafc; padding: 20px; border-radius: 10px; margin-bottom: 20px;">
                            <h4 style="margin-top: 0; margin-bottom: 15px; color: #2d3748;">Task 1</h4>
                            
                            <div class="form-group">
                                <label for="task_category_1">Task Category:</label>
                                <select id="task_category_1" name="tasks[0][task_category]" required>
                                    <option value="">Select Category</option>
                                    <option value="Development">Development</option>
                                    <option value="Design">Design</option>
                                    <option value="Testing">Testing</option>
                                    <option value="Documentation">Documentation</option>
                                    <option value="Meeting">Meeting</option>
                                    <option value="Client Follow-up">Client Follow-up</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="task_description_1">Task Description:</label>
                                <textarea id="task_description_1" name="tasks[0][task_description]" rows="3" required></textarea>
                            </div>
                            
                            <div class="form-group">
                                <label for="status_1">Completion Status:</label>
                                <select id="status_1" name="tasks[0][status]" required>
                                    <option value="">Select Status</option>
                                    <option value="Completed">Completed</option>
                                    <option value="In Progress">In Progress</option>
                                    <option value="Not Completed">Not Completed</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="next_action_1">Next Action:</label>
                                <textarea id="next_action_1" name="tasks[0][next_action]" rows="2" required></textarea>
                            </div>
                            
                            <div class="form-group">
                                <label for="manager_assigned_task_1">Manager Assigned Task:</label>
                                <textarea id="manager_assigned_task_1" name="tasks[0][manager_assigned_task]" rows="2"></textarea>
                            </div>
                            
                            <div class="form-group">
                                <label for="additional_notes_1">Additional Notes:</label>
                                <textarea id="additional_notes_1" name="tasks[0][additional_notes]" rows="2"></textarea>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group" style="text-align: right;">
                        <button type="button" id="add_task_btn" class="button" style="margin-right: 10px;">Add Another Task</button>
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" name="bassmah_submit_report">Submit Report</button>
                    </div>
                </form>
            <?php elseif (!current_user_can('basmah_submit_report') && current_user_can('manage_options')): ?>
                <div style="padding: 25px; background: linear-gradient(135deg, #e6f7ff 0%, #bae7ff 100%); border-radius: 12px; border-left: 4px solid #4299e1;\">\n                    <p style=\"margin: 0; font-size: 1.15rem; color: #0050b3; font-weight: 700;\">\n                        👨‍💼 Admin Access Notice\n                    </p>\n                    <p style=\"margin: 12px 0 0 0; color: #0050b3; line-height: 1.6;\">\n                        The report form is designed for staff members only. As an administrator, you have full access to manage all reports, approvals, and settings from the admin panel.\n                    </p>\n                </div>\n            <?php endif; ?>\n        </div>
        
        <script>
        jQuery(document).ready(function($) {
            var taskCount = 1;
            
            $('#add_task_btn').click(function() {
                taskCount++;
                var newTask = $('.task-item-form:first').clone();
                newTask.find('h4').text('Task ' + taskCount);
                newTask.find('input, select, textarea').each(function() {
                    var name = $(this).attr('name');
                    if (name) {
                        name = name.replace(/\[0\]/, '[' + (taskCount - 1) + ']');
                        $(this).attr('name', name);
                    }
                    var id = $(this).attr('id');
                    if (id) {
                        id = id.replace(/_1$/, '_' + taskCount);
                        $(this).attr('id', id);
                    }
                    $(this).val('');
                });
                
                newTask.append('<button type="button" class="remove-task-btn" style="margin-top: 15px; padding: 8px 16px; background: #f56565; color: white; border: none; border-radius: 6px; cursor: pointer;">Remove Task</button>');
                $('#tasks_container').append(newTask);
            });
            
            $(document).on('click', '.remove-task-btn', function() {
                $(this).closest('.task-item-form').remove();
            });
        });
        </script>
        <?php
        return ob_get_clean();
    }

    public function handle_report_submission() {
        if (!isset($_POST['bassmah_submit_report'])) {
            return;
        }

        if (!is_user_logged_in() || !current_user_can('basmah_submit_report')) {
            return;
        }

        if (!isset($_POST['bassmah_report_nonce']) || !wp_verify_nonce($_POST['bassmah_report_nonce'], 'bassmah_report_submit')) {
            return;
        }

        $user_id = get_current_user_id();
        $report_date = current_time('Y-m-d');
        $tasks = isset($_POST['tasks']) ? $_POST['tasks'] : array();
        
        $sanitized_tasks = array();
        foreach ($tasks as $task) {
            $task_data = array(
                'task_category' => sanitize_text_field($task['task_category']),
                'task_description' => sanitize_textarea_field($task['task_description']),
                'status' => sanitize_text_field($task['status']),
                'next_action' => sanitize_textarea_field($task['next_action'])
            );
            
            if (!empty($task['manager_assigned_task'])) {
                $task_data['manager_assigned_task'] = sanitize_textarea_field($task['manager_assigned_task']);
            }
            if (!empty($task['additional_notes'])) {
                $task_data['additional_notes'] = sanitize_textarea_field($task['additional_notes']);
            }
            
            $sanitized_tasks[] = $task_data;
        }

        $existing_report = Basmah_Staff_Reports_Reports::report_exists($user_id, $report_date);

        if ($existing_report) {
            wp_redirect(add_query_arg('duplicate_report', '1'));
            exit;
        }

        $inserted = Basmah_Staff_Reports_Reports::create_report(array(
            'user_id' => $user_id,
            'report_date' => $report_date,
            'tasks' => $sanitized_tasks
        ));

        if ($inserted) {
            Basmah_Staff_Reports_Emails::notify_manager_on_report_submission($user_id, $report_date);
        }

        wp_redirect(add_query_arg('report_submitted', '1'));
        exit;
    }
}
