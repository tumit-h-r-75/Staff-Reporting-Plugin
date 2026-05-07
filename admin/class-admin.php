<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * @since      1.0.0
 * @package    Bassmah_Staff_Reports
 * @author     Tumit <tumit@bassmah.ca>
 */
class Bassmah_Staff_Reports_Admin {

    /**
     * The ID of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $plugin_name    The ID of this plugin.
     */
    private $plugin_name;

    /**
     * The version of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $version    The current version of this plugin.
     */
    private $version;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param    string    $plugin_name       The name of this plugin.
     * @param    string    $version    The version of this plugin.
     */
    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }

    /**
     * Register the stylesheets for the admin area.
     *
     * @since    1.0.0
     */
    public function enqueue_styles() {
        wp_enqueue_style(
            $this->plugin_name,
            plugin_dir_url(__FILE__) . 'css/admin-style.css',
            array(),
            $this->version,
            'all'
        );

        // Add date picker CSS
        wp_enqueue_style('jquery-ui-datepicker');
    }

    /**
     * Register the JavaScript for the admin area.
     *
     * @since    1.0.0
     */
    public function enqueue_scripts() {
        wp_enqueue_script(
            $this->plugin_name,
            plugin_dir_url(__FILE__) . 'js/admin-scripts.js',
            array('jquery', 'jquery-ui-datepicker'),
            $this->version,
            false
        );

        // Localize script
        wp_localize_script($this->plugin_name, 'bassmah_admin', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('bassmah_admin_nonce'),
            'strings' => array(
                'confirm_delete' => __('Are you sure you want to delete this item?', 'bassmah-staff-reports'),
                'no_reports_found' => __('No reports found for the selected criteria.', 'bassmah-staff-reports'),
                'loading' => __('Loading...', 'bassmah-staff-reports')
            )
        ));
    }

    /**
     * Add plugin admin menu items
     *
     * @since    1.0.0
     */
    public function add_plugin_admin_menu() {
        // Main menu
        add_menu_page(
            __('Bassmah Reports', 'bassmah-staff-reports'),
            __('Bassmah Reports', 'bassmah-staff-reports'),
            'bassmah_view_all_reports',
            'bassmah-reports',
            array($this, 'display_dashboard_page'),
            'dashicons-clipboard',
            25
        );

        // Dashboard submenu
        add_submenu_page(
            'bassmah-reports',
            __('Dashboard', 'bassmah-staff-reports'),
            __('Dashboard', 'bassmah-staff-reports'),
            'bassmah_view_all_reports',
            'bassmah-reports',
            array($this, 'display_dashboard_page')
        );

        // All Reports submenu
        add_submenu_page(
            'bassmah-reports',
            __('All Reports', 'bassmah-staff-reports'),
            __('All Reports', 'bassmah-staff-reports'),
            'bassmah_view_all_reports',
            'bassmah-all-reports',
            array($this, 'display_all_reports_page')
        );

        // Salary Settings submenu
        add_submenu_page(
            'bassmah-reports',
            __('Salary Settings', 'bassmah-staff-reports'),
            __('Salary Settings', 'bassmah-staff-reports'),
            'bassmah_manage_salary_settings',
            'bassmah-salary-settings',
            array($this, 'display_salary_settings_page')
        );

        // Working Days submenu
        add_submenu_page(
            'bassmah-reports',
            __('Working Days', 'bassmah-staff-reports'),
            __('Working Days', 'bassmah-staff-reports'),
            'bassmah_manage_working_days',
            'bassmah-working-days',
            array($this, 'display_working_days_page')
        );

        // Staff Management submenu
        add_submenu_page(
            'bassmah-reports',
            __('Staff Management', 'bassmah-staff-reports'),
            __('Staff Management', 'bassmah-staff-reports'),
            'bassmah_manage_staff',
            'bassmah-staff',
            array($this, 'display_staff_management_page')
        );

        // Settings submenu
        add_submenu_page(
            'bassmah-reports',
            __('Settings', 'bassmah-staff-reports'),
            __('Settings', 'bassmah-staff-reports'),
            'manage_options',
            'bassmah-settings',
            array($this, 'display_settings_page')
        );

        // Add "My Reports" for staff users
        if (Bassmah_Staff_Reports_Roles::is_staff() && !Bassmah_Staff_Reports_Roles::is_manager()) {
            add_submenu_page(
                'bassmah-reports',
                __('My Reports', 'bassmah-staff-reports'),
                __('My Reports', 'bassmah-staff-reports'),
                'bassmah_view_own_reports',
                'bassmah-my-reports',
                array($this, 'display_my_reports_page')
            );
        }
    }

    /**
     * Register plugin settings
     *
     * @since    1.0.0
     */
    public function register_settings() {
        // General settings
        register_setting('bassmah_settings', 'bassmah_task_categories');
        register_setting('bassmah_settings', 'bassmah_task_statuses');
        register_setting('bassmah_settings', 'bassmah_default_currency');
        register_setting('bassmah_settings', 'bassmah_default_working_days');
        
        // Email notification settings
        register_setting('bassmah_settings', 'bassmah_email_notifications');
        register_setting('bassmah_settings', 'bassmah_reminder_time');
    }

    /**
     * Display dashboard page
     *
     * @since    1.0.0
     */
    public function display_dashboard_page() {
        if (!current_user_can('bassmah_view_all_reports')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'bassmah-staff-reports'));
        }

        require_once plugin_dir_path(__FILE__) . 'views/dashboard.php';
    }

    /**
     * Display all reports page
     *
     * @since    1.0.0
     */
    public function display_all_reports_page() {
        if (!current_user_can('bassmah_view_all_reports')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'bassmah-staff-reports'));
        }

        require_once plugin_dir_path(__FILE__) . 'views/all-reports.php';
    }

    /**
     * Display salary settings page
     *
     * @since    1.0.0
     */
    public function display_salary_settings_page() {
        if (!current_user_can('bassmah_manage_salary_settings')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'bassmah-staff-reports'));
        }

        require_once plugin_dir_path(__FILE__) . 'views/salary-settings.php';
    }

    /**
     * Display working days page
     *
     * @since    1.0.0
     */
    public function display_working_days_page() {
        if (!current_user_can('bassmah_manage_working_days')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'bassmah-staff-reports'));
        }

        require_once plugin_dir_path(__FILE__) . 'views/working-days.php';
    }

    /**
     * Display staff management page
     *
     * @since    1.0.0
     */
    public function display_staff_management_page() {
        if (!current_user_can('bassmah_manage_staff')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'bassmah-staff-reports'));
        }

        require_once plugin_dir_path(__FILE__) . 'views/staff-management.php';
    }

    /**
     * Display settings page
     *
     * @since    1.0.0
     */
    public function display_settings_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'bassmah-staff-reports'));
        }

        require_once plugin_dir_path(__FILE__) . 'views/settings.php';
    }

    /**
     * Display my reports page (for staff)
     *
     * @since    1.0.0
     */
    public function display_my_reports_page() {
        if (!current_user_can('bassmah_view_own_reports')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'bassmah-staff-reports'));
        }

        require_once plugin_dir_path(__FILE__) . 'views/my-reports.php';
    }

    /**
     * Handle AJAX requests
     *
     * @since    1.0.0
     */
    public function handle_ajax_requests() {
        check_ajax_referer('bassmah_admin_nonce', 'nonce');

        $action = $_POST['action_type'] ?? '';

        switch ($action) {
            case 'save_salary_settings':
                $this->save_salary_settings();
                break;
            case 'export_reports':
                $this->export_reports();
                break;
            case 'get_manager_report_details':
                $this->get_manager_report_details();
                break;
            case 'update_report_status':
                $this->update_report_status();
                break;
            case 'export_manager_reports':
                $this->export_manager_reports();
                break;
            case 'export_salary_summary':
                $this->export_salary_summary();
                break;
            default:
                wp_send_json_error(__('Invalid action', 'bassmah-staff-reports'));
        }

        wp_die();
    }

    /**
     * Export reports to CSV
     *
     * @since    1.0.0
     */
    private function export_reports() {
        if (!current_user_can('bassmah_export_reports')) {
            wp_send_json_error(__('You do not have permission to export reports.', 'bassmah-staff-reports'));
        }

        $filters = $_POST['filters'] ?? array();
        $report_class = new Bassmah_Staff_Reports_Report();
        $reports = $report_class->get_reports($filters);

        $csv = "Date,Staff Name,Role,Status,Tasks,Comment\n";

        foreach ($reports as $report) {
            $user = get_userdata($report->user_id);
            $staff_name = $user ? $user->display_name : 'Unknown';
            $role = Bassmah_Staff_Reports_Roles::get_user_role_display($report->user_id);
            
            $tasks = array();
            if ($report->tasks) {
                foreach ($report->tasks as $task) {
                    $tasks[] = $task['task_description'] ?? '';
                }
            }
            $tasks_str = implode('; ', $tasks);

            $csv .= sprintf(
                "%s,%s,%s,%s,\"%s\",\"%s\"\n",
                $report->report_date,
                $staff_name,
                $role,
                $report->status,
                $tasks_str,
                $report->manager_comment
            );
        }

        wp_send_json_success(array('csv' => $csv));
    }

    /**
     * Export salary data to CSV
     *
     * @since    1.0.0
     */
    private function export_salary() {
        if (!current_user_can('bassmah_view_all_salary')) {
            wp_send_json_error(__('You do not have permission to export salary data.', 'bassmah-staff-reports'));
        }

        $month = $_POST['month'] ?? date('Y-m');
        $user_ids = $_POST['user_ids'] ?? array();

        $salary_class = new Bassmah_Staff_Reports_Salary();
        $csv = $salary_class->export_salary_csv($user_ids, $month);

        wp_send_json_success(array('csv' => $csv));
    }

    /**
     * Get manager report details
     */
    private function get_manager_report_details() {
        if (!current_user_can('bassmah_view_all_reports')) {
            wp_send_json_error(__('You do not have permission to view reports.', 'bassmah-staff-reports'));
        }

        $report_id = intval($_POST['report_id'] ?? 0);
        if (!$report_id) {
            wp_send_json_error(__('Invalid report ID.', 'bassmah-staff-reports'));
        }

        $report_class = new Bassmah_Staff_Reports_Report();
        $report = $report_class->get_report($report_id);

        if (!$report) {
            wp_send_json_error(__('Report not found.', 'bassmah-staff-reports'));
        }

        $user = get_userdata($report->user_id);
        $tasks = is_array($report->tasks) ? $report->tasks : json_decode($report->tasks, true);
        
        ob_start();
        ?>
        <div class="bassmah-report-details">
            <div class="bassmah-report-header">
                <h4><?php echo date_i18n('l, F j, Y', strtotime($report->report_date)); ?></h4>
                <span class="bassmah-status-badge bassmah-status-<?php echo $report->status; ?>">
                    <?php 
                    switch($report->status) {
                        case 'submitted':
                            _e('Submitted', 'bassmah-staff-reports');
                            break;
                        case 'approved':
                            _e('Approved', 'bassmah-staff-reports');
                            break;
                        case 'rejected':
                            _e('Rejected', 'bassmah-staff-reports');
                            break;
                        default:
                            echo esc_html($report->status);
                    }
                    ?>
                </span>
            </div>

            <div class="bassmah-report-meta">
                <p><strong><?php _e('Employee:', 'bassmah-staff-reports'); ?></strong> <?php echo esc_html($user->display_name); ?></p>
                <p><strong><?php _e('Email:', 'bassmah-staff-reports'); ?></strong> <?php echo esc_html($user->user_email); ?></p>
                <p><strong><?php _e('Role:', 'bassmah-staff-reports'); ?></strong> <?php echo esc_html(Bassmah_Staff_Reports_Roles::get_user_role_display($report->user_id)); ?></p>
                <p><strong><?php _e('Submitted:', 'bassmah-staff-reports'); ?></strong> <?php echo date_i18n('g:i A', strtotime($report->submission_time)); ?></p>
                <?php if ($report->ip_address): ?>
                    <p><strong><?php _e('IP Address:', 'bassmah-staff-reports'); ?></strong> <?php echo esc_html($report->ip_address); ?></p>
                <?php endif; ?>
            </div>

            <div class="bassmah-report-tasks">
                <h5><?php _e('Tasks Completed:', 'bassmah-staff-reports'); ?></h5>
                <?php if (!empty($tasks) && is_array($tasks)): ?>
                    <ul class="bassmah-tasks-list">
                        <?php foreach ($tasks as $index => $task): ?>
                            <li class="bassmah-task-item">
                                <div class="bassmah-task-description">
                                    <?php echo esc_html($task['task_description'] ?? ''); ?>
                                </div>
                                <?php if (!empty($task['task_category'])): ?>
                                    <span class="bassmah-task-category"><?php echo esc_html($task['task_category']); ?></span>
                                <?php endif; ?>
                                <?php if (!empty($task['task_status'])): ?>
                                    <span class="bassmah-task-status bassmah-task-<?php echo esc_attr($task['task_status']); ?>">
                                        <?php 
                                        switch($task['task_status']) {
                                            case 'completed':
                                                _e('Completed', 'bassmah-staff-reports');
                                                break;
                                            case 'in_progress':
                                                _e('In Progress', 'bassmah-staff-reports');
                                                break;
                                            case 'not_completed':
                                                _e('Not Completed', 'bassmah-staff-reports');
                                                break;
                                            default:
                                                echo esc_html($task['task_status']);
                                        }
                                        ?>
                                    </span>
                                <?php endif; ?>
                                <?php if (!empty($task['next_action'])): ?>
                                    <div class="bassmah-next-action">
                                        <strong><?php _e('Next Action:', 'bassmah-staff-reports'); ?></strong>
                                        <?php echo esc_html($task['next_action']); ?>
                                    </div>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p><?php _e('No tasks found.', 'bassmah-staff-reports'); ?></p>
                <?php endif; ?>
            </div>

            <?php if ($report->manager_comment): ?>
                <div class="bassmah-manager-comment">
                    <h5><?php _e('Manager Comment:', 'bassmah-staff-reports'); ?></h5>
                    <div class="bassmah-comment-content">
                        <?php echo wpautop(esc_html($report->manager_comment)); ?>
                    </div>
                </div>
            <?php endif; ?>

            <div class="bassmah-report-footer">
                <p class="bassmah-report-id">
                    <small><?php printf(__('Report ID: #%d', 'bassmah-staff-reports'), $report->id); ?></small>
                </p>
                <?php if ($report->status === 'submitted'): ?>
                    <div class="bassmah-manager-actions">
                        <button class="button button-primary" onclick="approveReport(<?php echo $report->id; ?>)">
                            <?php _e('Approve', 'bassmah-staff-reports'); ?>
                        </button>
                        <button class="button" onclick="rejectReport(<?php echo $report->id; ?>)">
                            <?php _e('Reject', 'bassmah-staff-reports'); ?>
                        </button>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
        $html = ob_get_clean();

        wp_send_json_success(array('html' => $html));
    }

    /**
     * Update report status
     */
    private function update_report_status() {
        if (!current_user_can('bassmah_comment_reports')) {
            wp_send_json_error(__('You do not have permission to update reports.', 'bassmah-staff-reports'));
        }

        $report_id = intval($_POST['report_id'] ?? 0);
        $status = sanitize_text_field($_POST['status'] ?? '');
        $comment = sanitize_textarea_field($_POST['comment'] ?? '');

        if (!$report_id || !in_array($status, array('approve', 'reject'))) {
            wp_send_json_error(__('Invalid parameters.', 'bassmah-staff-reports'));
        }

        $report_class = new Bassmah_Staff_Reports_Report();
        $result = $report_class->update_report_status($report_id, $status, $comment);

        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }

        wp_send_json_success(array('message' => __('Report updated successfully.', 'bassmah-staff-reports')));
    }

    /**
     * Export manager reports
     */
    private function export_manager_reports() {
        if (!current_user_can('bassmah_export_reports')) {
            wp_send_json_error(__('You do not have permission to export reports.', 'bassmah-staff-reports'));
        }

        $filters = array(
            'date_from' => $_GET['date_from'] ?? '',
            'date_to' => $_GET['date_to'] ?? '',
            'user_id' => intval($_GET['employee_id'] ?? 0),
            'status' => $_GET['status'] ?? ''
        );

        $report_class = new Bassmah_Staff_Reports_Report();
        $reports = $report_class->get_reports($filters);

        $filename = 'manager-reports-' . date('Y-m-d') . '.csv';
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        $output = fopen('php://output', 'w');
        
        // CSV headers
        fputcsv($output, array(
            __('Report Date', 'bassmah-staff-reports'),
            __('Employee Name', 'bassmah-staff-reports'),
            __('Email', 'bassmah-staff-reports'),
            __('Role', 'bassmah-staff-reports'),
            __('Status', 'bassmah-staff-reports'),
            __('Submission Time', 'bassmah-staff-reports'),
            __('Tasks', 'bassmah-staff-reports'),
            __('Manager Comment', 'bassmah-staff-reports')
        ));
        
        // CSV data
        foreach ($reports as $report) {
            $user = get_userdata($report->user_id);
            $tasks = is_array($report->tasks) ? $report->tasks : json_decode($report->tasks, true);
            $task_list = '';
            
            if (!empty($tasks) && is_array($tasks)) {
                $task_descriptions = array();
                foreach ($tasks as $task) {
                    $task_descriptions[] = $task['task_description'] ?? '';
                }
                $task_list = implode('; ', $task_descriptions);
            }
            
            fputcsv($output, array(
                $report->report_date,
                $user ? $user->display_name : 'Unknown',
                $user ? $user->user_email : 'Unknown',
                Bassmah_Staff_Reports_Roles::get_user_role_display($report->user_id),
                $report->status,
                $report->submission_time,
                $task_list,
                $report->manager_comment ?? ''
            ));
        }
        
        fclose($output);
        exit;
    }

    /**
     * Export salary summary
     */
    private function export_salary_summary() {
        if (!current_user_can('bassmah_view_all_salary')) {
            wp_send_json_error(__('You do not have permission to export salary data.', 'bassmah-staff-reports'));
        }

        $date_from = $_GET['date_from'] ?? date('Y-m-01');
        $date_to = $_GET['date_to'] ?? date('Y-m-d');
        $employee_id = intval($_GET['employee_id'] ?? 0);

        $salary_calculator = new Bassmah_Staff_Reports_Salary_Calculator();
        
        if ($employee_id > 0) {
            // Export for specific employee
            $summary = $salary_calculator->generate_salary_summary($employee_id, $date_from, $date_to);
        } else {
            // Export for all employees
            $staff_users = get_users(array(
                'role__in' => array('bassmah_staff', 'bassmah_manager'),
                'orderby' => 'display_name'
            ));
            
            $summary = array('monthly_breakdown' => array());
            
            foreach ($staff_users as $user) {
                $user_summary = $salary_calculator->generate_salary_summary($user->ID, $date_from, $date_to);
                foreach ($user_summary['monthly_breakdown'] as $month_data) {
                    $summary['monthly_breakdown'][] = array_merge($month_data, array(
                        'employee_name' => $user->display_name,
                        'employee_email' => $user->user_email
                    ));
                }
            }
        }

        $filename = 'salary-summary-' . date('Y-m-d') . '.csv';
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        $output = fopen('php://output', 'w');
        
        // CSV headers
        if ($employee_id > 0) {
            fputcsv($output, array(
                __('Month', 'bassmah-staff-reports'),
                __('Gross Salary', 'bassmah-staff-reports'),
                __('Working Days', 'bassmah-staff-reports'),
                __('Present Days', 'bassmah-staff-reports'),
                __('Absent Days', 'bassmah-staff-reports'),
                __('Daily Rate', 'bassmah-staff-reports'),
                __('Total Deduction', 'bassmah-staff-reports'),
                __('Net Salary', 'bassmah-staff-reports'),
                __('Currency', 'bassmah-staff-reports')
            ));
            
            // CSV data
            foreach ($summary['monthly_breakdown'] as $month_data) {
                $calc = $month_data['calculation'];
                fputcsv($output, array(
                    $month_data['month_display'],
                    $calc['monthly_salary'],
                    $calc['working_days'],
                    $calc['present_days'],
                    $calc['absent_days'],
                    $calc['daily_rate'],
                    $calc['total_deduction'],
                    $calc['net_salary'],
                    $calc['currency']
                ));
            }
        } else {
            fputcsv($output, array(
                __('Employee', 'bassmah-staff-reports'),
                __('Month', 'bassmah-staff-reports'),
                __('Gross Salary', 'bassmah-staff-reports'),
                __('Net Salary', 'bassmah-staff-reports'),
                __('Deductions', 'bassmah-staff-reports'),
                __('Currency', 'bassmah-staff-reports')
            ));
            
            foreach ($summary['monthly_breakdown'] as $month_data) {
                $calc = $month_data['calculation'];
                fputcsv($output, array(
                    $month_data['employee_name'],
                    $month_data['month_display'],
                    $calc['monthly_salary'],
                    $calc['net_salary'],
                    $calc['total_deduction'],
                    $calc['currency']
                ));
            }
        }
        
        fclose($output);
        exit;
    }
}
