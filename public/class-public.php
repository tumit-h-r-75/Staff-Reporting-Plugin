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
        
        // Localize script with nonce and other data
        wp_localize_script('bsr-public-js', 'bsrData', array(
            'nonce' => wp_create_nonce('wp_rest'),
            'apiUrl' => rest_url('bassmah/v1/'),
            'ajaxUrl' => admin_url('admin-ajax.php')
        ));
        
        // Enqueue React-style components
        wp_enqueue_style('bsr-react-components-css', BASMAH_STAFF_REPORTS_PLUGIN_URL . 'public/assets/react-components/index.css', array(), '1.0.0');
        wp_enqueue_script('bsr-react-components-js', BASMAH_STAFF_REPORTS_PLUGIN_URL . 'public/assets/react-components/index.js', array('jquery'), '1.0.0', true);
        
        // Pass WordPress data to JavaScript
        wp_localize_script('bsr-react-components-js', 'bsrData', array(
            'apiUrl' => rest_url('bsr/v1/'),
            'nonce' => wp_create_nonce('wp_rest'),
            'userId' => get_current_user_id(),
            'userName' => wp_get_current_user()->display_name,
            'userRole' => $this->get_user_role(),
            'pluginUrl' => BASMAH_STAFF_REPORTS_PLUGIN_URL
        ));
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

        $user = wp_get_current_user();
        $user_id = $user->ID;
        
        // Return React-style dashboard component
        return sprintf(
            '<div data-bsr-dashboard data-user-id="%d" data-user-name="%s" data-user-role="%s" data-nonce="%s" data-report-form-url="%s" data-my-reports-url="%s" data-salary-url="%s"></div>',
            esc_attr($user_id),
            esc_attr($user->display_name),
            esc_attr($this->get_user_role()),
            esc_attr(wp_create_nonce('wp_rest')),
            esc_attr(home_url('/report-form')),
            esc_attr(home_url('/my-reports')),
            esc_attr(home_url('/salary'))
        );
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

        // Return React-style report form component
        return sprintf(
            '<div data-bsr-report-form data-user-id="%d" data-user-name="%s" data-user-role="%s" data-nonce="%s" data-api-url="%s"></div>',
            esc_attr($user_id),
            esc_attr($current_user->display_name),
            esc_attr($this->get_user_role()),
            esc_attr(wp_create_nonce('wp_rest')),
            esc_attr(rest_url('bsr/v1/'))
        );
    }

    // Helper method to get user role
    private function get_user_role() {
        $user = wp_get_current_user();
        if (in_array('administrator', $user->roles)) {
            return 'Administrator';
        } elseif (in_array('basmah_manager', $user->roles)) {
            return 'Manager';
        } elseif (in_array('basmah_staff', $user->roles)) {
            return 'Staff Member';
        } else {
            return 'User';
        }
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
        $tasks = isset($_POST['tasks']) && is_array($_POST['tasks']) ? $_POST['tasks'] : array();
        
        $sanitized_tasks = array();
        foreach ($tasks as $task) {
            if (!is_array($task)) {
                continue;
            }

            $task_category = isset($task['task_category']) ? sanitize_text_field($task['task_category']) : '';
            $task_description = isset($task['task_description']) ? sanitize_textarea_field($task['task_description']) : '';
            $status = isset($task['status']) ? sanitize_text_field($task['status']) : '';
            $next_action = isset($task['next_action']) ? sanitize_textarea_field($task['next_action']) : '';

            if ($task_category === '' || $task_description === '' || $status === '' || $next_action === '') {
                continue;
            }

            $task_data = array(
                'task_category' => $task_category,
                'task_description' => $task_description,
                'status' => $status,
                'next_action' => $next_action
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

        if (empty($sanitized_tasks)) {
            wp_redirect(add_query_arg('report_error', '1'));
            exit;
        }

        $inserted = Basmah_Staff_Reports_Reports::create_report(array(
            'user_id' => $user_id,
            'report_date' => $report_date,
            'tasks' => $sanitized_tasks
        ));

        if ($inserted) {
            Basmah_Staff_Reports_Emails::notify_manager_on_report_submission($user_id, $report_date);
            wp_redirect(add_query_arg('report_submitted', '1'));
            exit;
        }

        if (Basmah_Staff_Reports_Reports::report_exists($user_id, $report_date)) {
            wp_redirect(add_query_arg('duplicate_report', '1'));
            exit;
        }

        wp_redirect(add_query_arg('report_error', '1'));
        exit;
    }
}
