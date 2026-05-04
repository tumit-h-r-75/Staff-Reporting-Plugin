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
            return '<p>Please log in to view your dashboard.</p>';
        }

        $user_id = get_current_user_id();
        $today = current_time('Y-m-d');
        $current_month = date('m');
        $current_year = date('Y');
        $days_in_month = cal_days_in_month(CAL_GREGORIAN, $current_month, $current_year);

        global $wpdb;
        $table_name = $wpdb->prefix . 'staff_reports';

        $today_report = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table_name WHERE user_id = %d AND report_date = %s",
            $user_id,
            $today
        ));

        $monthly_reports = $wpdb->get_results($wpdb->prepare(
            "SELECT DISTINCT report_date FROM $table_name WHERE user_id = %d AND MONTH(report_date) = %d AND YEAR(report_date) = %d",
            $user_id,
            $current_month,
            $current_year
        ), ARRAY_A);

        $submitted_days = count($monthly_reports);
        $working_days = $days_in_month;
        $missing_days = $working_days - $submitted_days;

        $daily_rate = 200;
        $monthly_salary = $submitted_days * $daily_rate;

        ob_start();
        ?>
        <div class="bsr-staff-dashboard">
            <h2>Staff Dashboard</h2>

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
                    <div class="summary-card submitted">
                        <p class="summary-number"><?php echo esc_html($submitted_days); ?></p>
                        <p class="summary-label">Days Submitted</p>
                    </div>
                    <div class="summary-card missing">
                        <p class="summary-number"><?php echo esc_html($missing_days); ?></p>
                        <p class="summary-label">Days Missing</p>
                    </div>
                </div>
            </div>

            <div class="dashboard-section">
                <h3>Salary Overview</h3>
                <div class="salary-card">
                    <p class="salary-label">Current Month Earnings</p>
                    <p class="salary-amount">$<?php echo number_format($monthly_salary, 2); ?></p>
                    <p class="salary-details">Based on <?php echo esc_html($submitted_days); ?> working days at $<?php echo number_format($daily_rate, 2); ?>/day</p>
                </div>
            </div>

            <div class="dashboard-section">
                <h3>Quick Actions</h3>
                <div class="action-buttons">
                    <a href="#" class="btn btn-primary" onclick="document.querySelector('[data-shortcode=\"bassmah_report_form\"]').scrollIntoView({behavior: 'smooth'}); return false;">Submit Report</a>
                    <a href="#" class="btn btn-secondary" onclick="document.querySelector('[data-shortcode=\"bsr_my_reports\"]').scrollIntoView({behavior: 'smooth'}); return false;">My Reports</a>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    public function render_report_form() {
        ob_start();
        require_once BASMAH_STAFF_REPORTS_PLUGIN_DIR . 'public/views/report-form.php';
        return ob_get_clean();
    }
    
    public function render_my_reports() {
        ob_start();
        require_once BASMAH_STAFF_REPORTS_PLUGIN_DIR . 'public/views/my-reports.php';
        return ob_get_clean();
    }

    public function render_bassmah_my_reports() {
        if (!is_user_logged_in()) {
            return '<p>Please log in to view your reports.</p>';
        }

        $user_id = get_current_user_id();
        global $wpdb;
        $table_name = $wpdb->prefix . 'staff_reports';

        $reports = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_name WHERE user_id = %d ORDER BY report_date DESC",
            $user_id
        ), ARRAY_A);

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
                            <th>Task Summary</th>
                            <th>Status</th>
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
                                $task_summary = isset($first_task['task_description']) ? substr($first_task['task_description'], 0, 100) . '...' : '';
                                $status = isset($first_task['status']) ? $first_task['status'] : 'N/A';
                            }
                            ?>
                            <tr>
                                <td><?php echo esc_html(date('F j, Y', strtotime($report['report_date']))); ?></td>
                                <td><?php echo esc_html($task_summary); ?></td>
                                <td><span class="status-badge <?php echo sanitize_title($status); ?>"><?php echo esc_html($status); ?></span></td>
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
            return '<p>Please log in to submit a report.</p>';
        }

        $user_id = get_current_user_id();
        $report_date = current_time('Y-m-d');
        global $wpdb;
        $table_name = $wpdb->prefix . 'staff_reports';
        $existing_report = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table_name WHERE user_id = %d AND report_date = %s",
            $user_id,
            $report_date
        ));

        ob_start();
        ?>
        <div class="bsr-report-form">
            <h2>Submit Daily Work Report</h2>
            
            <?php if ($existing_report): ?>
                <div class="notification-popup warning">
                    <p><strong>⚠️ You have already submitted a report for today (<?php echo esc_html($report_date); ?>).</strong></p>
                </div>
            <?php else: ?>
                <form method="post" action="">
                    <?php wp_nonce_field('bassmah_report_submit', 'bassmah_report_nonce'); ?>
                    
                    <div class="form-group">
                        <label for="task_category">Task Category:</label>
                        <select id="task_category" name="task_category" required>
                            <option value="">Select Category</option>
                            <option value="Development">Development</option>
                            <option value="Design">Design</option>
                            <option value="Testing">Testing</option>
                            <option value="Documentation">Documentation</option>
                            <option value="Meeting">Meeting</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="task_description">Task Description:</label>
                        <textarea id="task_description" name="task_description" rows="4" required></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="status">Status:</label>
                        <select id="status" name="status" required>
                            <option value="">Select Status</option>
                            <option value="Not Started">Not Started</option>
                            <option value="In Progress">In Progress</option>
                            <option value="Completed">Completed</option>
                            <option value="On Hold">On Hold</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="next_action">Next Action:</label>
                        <textarea id="next_action" name="next_action" rows="3" required></textarea>
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" name="bassmah_submit_report">Submit Report</button>
                    </div>
                </form>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    public function handle_report_submission() {
        if (!isset($_POST['bassmah_submit_report'])) {
            return;
        }

        if (!is_user_logged_in()) {
            return;
        }

        if (!isset($_POST['bassmah_report_nonce']) || !wp_verify_nonce($_POST['bassmah_report_nonce'], 'bassmah_report_submit')) {
            return;
        }

        $user_id = get_current_user_id();
        $report_date = current_time('Y-m-d');
        $tasks = array(
            array(
                'task_category' => sanitize_text_field($_POST['task_category']),
                'task_description' => sanitize_textarea_field($_POST['task_description']),
                'status' => sanitize_text_field($_POST['status']),
                'next_action' => sanitize_textarea_field($_POST['next_action'])
            )
        );

        global $wpdb;
        $table_name = $wpdb->prefix . 'staff_reports';

        $existing_report = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table_name WHERE user_id = %d AND report_date = %s",
            $user_id,
            $report_date
        ));

        if ($existing_report) {
            wp_redirect(add_query_arg('duplicate_report', '1'));
            exit;
        }

        $wpdb->insert(
            $table_name,
            array(
                'user_id' => $user_id,
                'report_date' => $report_date,
                'tasks_json' => json_encode($tasks),
                'created_at' => current_time('mysql')
            ),
            array(
                '%d',
                '%s',
                '%s',
                '%s'
            )
        );

        wp_redirect(add_query_arg('report_submitted', '1'));
        exit;
    }
}

new Basmah_Staff_Reports_Public();
