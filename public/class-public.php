<?php
class Basmah_Staff_Reports_Public {
    public function __construct() {
        add_shortcode('bsr_staff_dashboard', array($this, 'render_staff_dashboard'));
        add_shortcode('bsr_report_form', array($this, 'render_report_form'));
        add_shortcode('bassmah_report_form', array($this, 'render_bassmah_report_form'));
        add_shortcode('bsr_my_reports', array($this, 'render_my_reports'));
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
    
    public function render_salary_view() {
        ob_start();
        require_once BASMAH_STAFF_REPORTS_PLUGIN_DIR . 'public/views/salary-view.php';
        return ob_get_clean();
    }

    public function render_bassmah_report_form() {
        if (!is_user_logged_in()) {
            return '<p>Please log in to submit a report.</p>';
        }

        ob_start();
        ?>
        <div class="bsr-report-form">
            <h2>Submit Daily Work Report</h2>
            <?php if (isset($_GET['report_submitted']) && $_GET['report_submitted'] == 1): ?>
                <p style="color: green; font-weight: bold;">Report submitted successfully!</p>
            <?php endif; ?>
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
