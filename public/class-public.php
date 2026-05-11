<?php
/**
 * The public-facing functionality of the plugin.
 *
 * @since      1.0.0
 * @package    Bassmah_Staff_Reports
 * @author     Tumit <tumit@bassmah.ca>
 */
class Bassmah_Staff_Reports_Public {

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
     * @param    string    $plugin_name       The name of the plugin.
     * @param    string    $version    The version of this plugin.
     */
    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }

    /**
     * Register the stylesheets for the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function enqueue_styles() {
        wp_enqueue_style(
            BASSMAH_STAFF_REPORTS_PLUGIN_NAME,
            plugin_dir_url(__FILE__) . 'css/public-style.css',
            array(),
            BASSMAH_STAFF_REPORTS_VERSION,
            'all'
        );
    }

    /**
     * Register the JavaScript for the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function enqueue_scripts() {
        // Enqueue jQuery and jQuery UI
        wp_enqueue_script('jquery');
        wp_enqueue_script('jquery-ui-datepicker');
        wp_enqueue_style('jquery-ui-css', 'https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.min.css');
        
        // Enqueue JWT authentication script
        wp_enqueue_script(
            'bassmah-jwt-auth',
            plugin_dir_url(__FILE__) . 'js/jwt-auth.js',
            array('jquery'),
            BASSMAH_STAFF_REPORTS_VERSION,
            true
        );
        
        // Enqueue main public script
        wp_enqueue_script(
            BASSMAH_STAFF_REPORTS_PLUGIN_NAME,
            plugin_dir_url(__FILE__) . 'js/public-scripts.js',
            array('jquery', 'bassmah-jwt-auth'),
            BASSMAH_STAFF_REPORTS_VERSION,
            true
        );
        
        // Localize script with JWT support
        wp_localize_script(
            BASSMAH_STAFF_REPORTS_PLUGIN_NAME,
            'bassmah_public',
            array(
                'ajaxurl' => admin_url('admin-ajax.php'),
                'rest_url' => rest_url('bassmah/v1/'),
                'nonce' => wp_create_nonce('wp_rest'),
                'today_date' => current_time('Y-m-d'),
                'report_form_url' => home_url('/report/'),
                'strings' => array(
                    'confirm_delete' => __('Are you sure you want to delete this?', 'bassmah-staff-reports'),
                    'loading' => __('Loading...', 'bassmah-staff-reports'),
                    'error_occurred' => __('An error occurred. Please try again.', 'bassmah-staff-reports'),
                    'success' => __('Success!', 'bassmah-staff-reports'),
                    'auth_required' => __('Authentication required. Please log in.', 'bassmah-staff-reports'),
                    'token_expired' => __('Session expired. Please refresh the page.', 'bassmah-staff-reports'),
                    'report_submitted' => __('Report submitted successfully!', 'bassmah-staff-reports')
                )
            )
        );
        
        // Localize JWT auth script
        wp_localize_script(
            'bassmah-jwt-auth',
            'bassmahAuth',
            array(
                'ajaxurl' => admin_url('admin-ajax.php'),
                'is_logged_in' => is_user_logged_in(),
                'user_id' => get_current_user_id(),
                'strings' => array(
                    'token_error' => __('Authentication error. Please refresh the page.', 'bassmah-staff-reports'),
                    'network_error' => __('Network error. Please check your connection.', 'bassmah-staff-reports')
                )
            )
        );
    }

    /**
     * Register AJAX actions
     *
     * @since    1.0.0
     */
    public function register_ajax_actions() {
        add_action('wp_ajax_bassmah_get_report_details', array($this, 'handle_ajax_requests'));
        add_action('wp_ajax_nopriv_bassmah_get_report_details', array($this, 'handle_ajax_requests'));
        add_action('wp_ajax_bassmah_export_reports', array($this, 'handle_ajax_requests'));
        add_action('wp_ajax_nopriv_bassmah_export_reports', array($this, 'handle_ajax_requests'));
        add_action('wp_ajax_bassmah_export_salary_history', array($this, 'handle_ajax_requests'));
        add_action('wp_ajax_nopriv_bassmah_export_salary_history', array($this, 'handle_ajax_requests'));
        add_action('wp_ajax_bassmah_frontend_ajax', array($this, 'handle_ajax_requests'));
        add_action('wp_ajax_nopriv_bassmah_frontend_ajax', array($this, 'handle_ajax_requests'));
    }

    /**
     * Register shortcodes
     *
     * @since    1.0.0
     */
    public function register_shortcodes() {
        add_shortcode('bassmah_report_form', array($this, 'render_report_form'));
        add_shortcode('bassmah_staff_dashboard', array($this, 'render_staff_dashboard'));
        add_shortcode('bassmah_my_reports', array($this, 'render_my_reports'));
        add_shortcode('bassmah_role_dashboard', array($this, 'render_role_dashboard'));
    }

    /**
     * Render report form shortcode
     *
     * @since    1.0.0
     * @param    array    $atts    Shortcode attributes
     * @return   string
     */
    public function render_report_form($atts) {
        if (!is_user_logged_in()) {
            return '<p>' . __('Please log in to submit a report.', 'bassmah-staff-reports') . '</p>';
        }

        if (!current_user_can('bassmah_submit_reports')) {
            return '<p>' . __('You do not have permission to submit reports.', 'bassmah-staff-reports') . '</p>';
        }

        // Check if already submitted today
        $report_class = new Bassmah_Staff_Reports_Report();
        $today_report = $report_class->get_today_report();

        if ($today_report) {
            return $this->render_already_submitted_message($today_report);
        }

        ob_start();
        require_once plugin_dir_path(__FILE__) . 'views/report-form.php';
        return ob_get_clean();
    }

    /**
     * Render staff dashboard shortcode
     *
     * @since    1.0.0
     * @param    array    $atts    Shortcode attributes
     * @return   string
     */
    public function render_staff_dashboard($atts) {
        if (!is_user_logged_in()) {
            return '<p>' . __('Please log in to view your dashboard.', 'bassmah-staff-reports') . '</p>';
        }

        if (!current_user_can('bassmah_view_own_reports')) {
            return '<p>' . __('You do not have permission to view this dashboard.', 'bassmah-staff-reports') . '</p>';
        }

        ob_start();
        require_once plugin_dir_path(__FILE__) . 'views/staff-dashboard.php';
        return ob_get_clean();
    }

    /**
     * Render my reports shortcode
     *
     * @since    1.0.0
     * @param    array    $atts    Shortcode attributes
     * @return   string
     */
    public function render_my_reports($atts) {
        if (!is_user_logged_in()) {
            return '<p>' . __('Please log in to view your reports.', 'bassmah-staff-reports') . '</p>';
        }

        if (!current_user_can('bassmah_view_own_reports')) {
            return '<p>' . __('You do not have permission to view reports.', 'bassmah-staff-reports') . '</p>';
        }

        ob_start();
        require_once plugin_dir_path(__FILE__) . 'views/my-reports.php';
        return ob_get_clean();
    }

    /**
     * Render already submitted message
     *
     * @since    1.0.0
     * @param    object    $report    Today's report
     * @return   string
     */
    private function render_already_submitted_message($report) {
        $user = wp_get_current_user();
        $report_date = date_i18n(get_option('date_format'), strtotime($report->report_date));
        
        $html = '<div class="bassmah-notice bassmah-notice-success">';
        $html .= '<h3>' . __('Report Already Submitted', 'bassmah-staff-reports') . '</h3>';
        $html .= '<p>' . sprintf(
            __('Hello %s, you have already submitted your report for %s at %s.', 'bassmah-staff-reports'),
            esc_html($user->display_name),
            '<strong>' . $report_date . '</strong>',
            date_i18n(get_option('time_format'), strtotime($report->submission_time))
        ) . '</p>';
        
        if ($report->manager_comment) {
            $html .= '<div class="manager-comment">';
            $html .= '<h4>' . __('Manager Comment:', 'bassmah-staff-reports') . '</h4>';
            $html .= '<p>' . esc_html($report->manager_comment) . '</p>';
            $html .= '</div>';
        }
        
        $html .= '</div>';
        
        return $html;
    }

    /**
     * Handle AJAX requests from frontend
     *
     * @since    1.0.0
     */
    public function handle_ajax_requests() {
        // Fix: Add CORS headers for JWT
        if (!headers_sent()) {
            header('Access-Control-Allow-Origin: ' . get_site_url());
            header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
            header('Access-Control-Allow-Headers: Content-Type, Authorization');
            header('Access-Control-Allow-Credentials: false'); // JWT doesn't need cookies
        }
        
        // Handle preflight OPTIONS request
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            status_header(200);
            exit;
        }
        
        // Initialize JWT authentication
        $jwt_auth = new Bassmah_Staff_Reports_JWT_Auth();
        
        // Validate JWT token instead of nonce
        $token_validation = $jwt_auth->validate_jwt_middleware();
        
        if (is_wp_error($token_validation)) {
            wp_send_json_error($token_validation->get_error_message(), 401);
        }
        
        // Set current user from token
        if (isset($token_validation['data']['user_id'])) {
            wp_set_current_user($token_validation['data']['user_id']);
        }

        $action = $_POST['action_type'] ?? '';

        switch ($action) {
            case 'submit_report':
                $this->submit_report_frontend();
                break;
            case 'get_my_reports':
                $this->get_my_reports_frontend();
                break;
            case 'get_dashboard_data':
                $this->get_dashboard_data_frontend();
                break;
            case 'get_report_details':
                $this->get_report_details_frontend();
                break;
            case 'export_reports':
                $this->export_reports_frontend();
                break;
            case 'export_salary_history':
                $this->export_salary_history_frontend();
                break;
            default:
                wp_send_json_error(__('Invalid action', 'bassmah-staff-reports'));
        }

        wp_die();
    }

    /**
     * Handle report submission from frontend
     *
     * @since    1.0.0
     */
    private function submit_report_frontend() {
        if (!current_user_can('bassmah_submit_reports')) {
            wp_send_json_error(__('You do not have permission to submit reports.', 'bassmah-staff-reports'));
        }

        $tasks = $_POST['tasks'] ?? array();
        if (empty($tasks)) {
            wp_send_json_error(__('At least one task is required.', 'bassmah-staff-reports'));
        }

        // Validate tasks
        foreach ($tasks as $task) {
            if (empty($task['task_description'])) {
                wp_send_json_error(__('Task description is required for all tasks.', 'bassmah-staff-reports'));
            }
        }

        $report_data = array(
            'user_id' => get_current_user_id(),
            'report_date' => current_time('Y-m-d'),
            'tasks' => $tasks,
            'status' => 'submitted'
        );

        $report_class = new Bassmah_Staff_Reports_Report();
        $result = $report_class->submit_report($report_data);

        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }

        wp_send_json_success(array(
            'message' => __('Report submitted successfully!', 'bassmah-staff-reports'),
            'report_id' => $result
        ));
    }

    /**
     * Get user's reports for frontend display
     *
     * @since    1.0.0
     */
    private function get_my_reports_frontend() {
        if (!current_user_can('bassmah_view_own_reports')) {
            wp_send_json_error(__('You do not have permission to view reports.', 'bassmah-staff-reports'));
        }

        $page = intval($_POST['page'] ?? 1);
        $per_page = intval($_POST['per_page'] ?? 10);
        $date_from = $_POST['date_from'] ?? '';
        $date_to = $_POST['date_to'] ?? '';

        $args = array(
            'limit' => $per_page,
            'offset' => ($page - 1) * $per_page
        );

        if ($date_from) {
            $args['date_from'] = $date_from;
        }
        if ($date_to) {
            $args['date_to'] = $date_to;
        }

        $report_class = new Bassmah_Staff_Reports_Report();
        $reports = $report_class->get_my_reports($args);

        // Format reports for frontend
        $formatted_reports = array();
        foreach ($reports as $report) {
            $formatted_reports[] = array(
                'id' => $report->id,
                'report_date' => $report->report_date,
                'submission_time' => $report->submission_time,
                'status' => $report->status,
                'tasks' => $report->tasks,
                'manager_comment' => $report->manager_comment
            );
        }

        wp_send_json_success(array(
            'reports' => $formatted_reports,
            'page' => $page,
            'per_page' => $per_page
        ));
    }

    /**
     * Get dashboard data for frontend
     *
     * @since    1.0.0
     */
    private function get_dashboard_data_frontend() {
        if (!current_user_can('bassmah_view_own_reports')) {
            wp_send_json_error(__('You do not have permission to view dashboard.', 'bassmah-staff-reports'));
        }

        $user_id = get_current_user_id();
        $salary_class = new Bassmah_Staff_Reports_Salary();
        $dashboard_data = $salary_class->get_dashboard_stats($user_id);

        // Get recent reports
        $report_class = new Bassmah_Staff_Reports_Report();
        $recent_reports = $report_class->get_my_reports(array(
            'limit' => 5,
            'orderby' => 'report_date',
            'order' => 'DESC'
        ));

        $formatted_recent_reports = array();
        foreach ($recent_reports as $report) {
            $formatted_recent_reports[] = array(
                'id' => $report->id,
                'report_date' => $report->report_date,
                'status' => $report->status,
                'task_count' => count($report->tasks ?? array())
            );
        }

        wp_send_json_success(array(
            'dashboard' => $dashboard_data,
            'recent_reports' => $formatted_recent_reports
        ));
    }

    /**
     * Get report details for frontend display
     *
     * @since    1.0.0
     */
    private function get_report_details_frontend() {
        if (!current_user_can('bassmah_view_own_reports')) {
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

        // Check if user owns this report
        if ($report->user_id !== get_current_user_id() && !current_user_can('bassmah_view_all_reports')) {
            wp_send_json_error(__('You do not have permission to view this report.', 'bassmah-staff-reports'));
        }

        // Format tasks for display
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
                <p><strong><?php _e('Submitted:', 'bassmah-staff-reports'); ?></strong> <?php echo date_i18n('g:i A', strtotime($report->submission_time)); ?></p>
                <?php if ($report->ip_address): ?>
                    <p><strong><?php _e('IP Address:', 'bassmah-staff-reports'); ?></strong> <?php echo esc_html($report->ip_address); ?></p>
                <?php endif; ?>
            </div>

            <div class="bassmah-report-tasks">
                <h5><?php _e('Tasks Completed:', 'bassmah-staff-reports'); ?></h5>
                <?php if (!empty($tasks) && is_array($tasks)): ?>
                    <ul class="bassmah-tasks-list">
                        <?php foreach ($tasks as $task): ?>
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
            </div>
        </div>

        <style>
        .bassmah-report-details {
            max-width: 100%;
        }
        .bassmah-report-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #e9ecef;
        }
        .bassmah-report-meta p {
            margin: 5px 0;
            color: #6c757d;
        }
        .bassmah-tasks-list {
            list-style: none;
            padding: 0;
            margin: 15px 0;
        }
        .bassmah-task-item {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 6px;
            padding: 15px;
            margin-bottom: 10px;
        }
        .bassmah-task-description {
            font-weight: 500;
            margin-bottom: 8px;
        }
        .bassmah-task-category {
            background: #0073aa;
            color: white;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 11px;
            margin-right: 8px;
        }
        .bassmah-task-status {
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 11px;
        }
        .bassmah-task-completed {
            background: #d4edda;
            color: #155724;
        }
        .bassmah-task-in_progress {
            background: #fff3cd;
            color: #856404;
        }
        .bassmah-task-not_completed {
            background: #f8d7da;
            color: #721c24;
        }
        .bassmah-manager-comment {
            background: #e9ecef;
            border-radius: 6px;
            padding: 15px;
            margin-top: 20px;
        }
        .bassmah-comment-content {
            margin-top: 10px;
        }
        .bassmah-report-footer {
            margin-top: 20px;
            padding-top: 15px;
            border-top: 1px solid #e9ecef;
        }
        </style>
        <?php
        $html = ob_get_clean();

        wp_send_json_success(array('html' => $html));
    }

    /**
     * Export reports to CSV
     *
     * @since    1.0.0
     */
    private function export_reports_frontend() {
        if (!current_user_can('bassmah_view_own_reports')) {
            wp_send_json_error(__('You do not have permission to export reports.', 'bassmah-staff-reports'));
        }

        $date_from = $_GET['date_from'] ?? '';
        $date_to = $_GET['date_to'] ?? '';
        $status = $_GET['status'] ?? '';

        $args = array();
        if ($date_from) {
            $args['date_from'] = $date_from;
        }
        if ($date_to) {
            $args['date_to'] = $date_to;
        }
        if ($status) {
            $args['status'] = $status;
        }

        $report_class = new Bassmah_Staff_Reports_Report();
        $reports = $report_class->get_my_reports($args);

        if (empty($reports)) {
            wp_send_json_error(__('No reports found to export.', 'bassmah-staff-reports'));
        }

        // Generate CSV
        $filename = 'staff-reports-' . date('Y-m-d') . '.csv';
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        $output = fopen('php://output', 'w');
        
        // CSV headers
        fputcsv($output, array(
            __('Report Date', 'bassmah-staff-reports'),
            __('Submission Time', 'bassmah-staff-reports'),
            __('Status', 'bassmah-staff-reports'),
            __('Tasks', 'bassmah-staff-reports'),
            __('Manager Comment', 'bassmah-staff-reports')
        ));
        
        // CSV data
        foreach ($reports as $report) {
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
                $report->submission_time,
                $report->status,
                $task_list,
                $report->manager_comment ?? ''
            ));
        }
        
        fclose($output);
        exit;
    }

    /**
     * Render role-based dashboard shortcode
     *
     * @since    1.0.0
     * @param    array    $atts    Shortcode attributes
     * @return   string
     */
    public function render_role_dashboard($atts) {
        ob_start();
        include_once BASSMAH_STAFF_REPORTS_PLUGIN_DIR . 'public/views/role-dashboard.php';
        return ob_get_clean();
    }

    /**
     * Export salary history for frontend
     *
     * @since    1.0.0
     */
    private function export_salary_history_frontend() {
        if (!current_user_can('bassmah_view_own_salary')) {
            wp_send_json_error(__('You do not have permission to export salary data.', 'bassmah-staff-reports'));
        }

        $user_id = intval($_GET['user_id'] ?? get_current_user_id());
        $from_date = $_GET['from_date'] ?? date('Y-m-01');
        $to_date = $_GET['to_date'] ?? date('Y-m-d');

        $salary_calculator = new Bassmah_Staff_Reports_Salary_Calculator();
        $salary_calculator::export_salary_csv($user_id, $from_date, $to_date);
    }

    /**
     * Notify staff when report is approved
     *
     * @since    1.0.0
     * @param    int    $report_id    Report ID
     * @param    int    $manager_id   Manager ID
     * @param    string $comment      Manager comment
     */
    public function notify_staff_report_approved($report_id, $manager_id, $comment) {
        $report = get_post($report_id);
        if (!$report) return;

        $user = get_userdata($report->post_author);
        if (!$user) return;

        $manager = get_userdata($manager_id);
        $manager_name = $manager ? $manager->display_name : __('Manager', 'bassmah-staff-reports');

        $subject = __('Your Report Has Been Approved', 'bassmah-staff-reports');
        
        $message = sprintf(
            __('Hello %s,', 'bassmah-staff-reports') . "\n\n" .
            __('Your report for %s has been approved by %s.', 'bassmah-staff-reports') . "\n\n" .
            __('Comment: %s', 'bassmah-staff-reports') . "\n\n" .
            __('View Report: %s', 'bassmah-staff-reports'),
            $user->display_name,
            get_the_date(get_option('date_format'), $report_id),
            $manager_name,
            $comment,
            admin_url('admin.php?page=bassmah-my-reports&view=report&id=' . $report_id)
        );

        wp_mail($user->user_email, $subject, $message);
    }

    /**
     * Notify staff when report is rejected
     *
     * @since    1.0.0
     * @param    int    $report_id    Report ID
     * @param    int    $manager_id   Manager ID
     * @param    string $reason       Rejection reason
     */
    public function notify_staff_report_rejected($report_id, $manager_id, $reason) {
        $report = get_post($report_id);
        if (!$report) return;

        $user = get_userdata($report->post_author);
        if (!$user) return;

        $manager = get_userdata($manager_id);
        $manager_name = $manager ? $manager->display_name : __('Manager', 'bassmah-staff-reports');

        $subject = __('Your Report Has Been Rejected', 'bassmah-staff-reports');
        
        $message = sprintf(
            __('Hello %s,', 'bassmah-staff-reports') . "\n\n" .
            __('Your report for %s has been rejected by %s.', 'bassmah-staff-reports') . "\n\n" .
            __('Reason: %s', 'bassmah-staff-reports') . "\n\n" .
            __('Please review and resubmit your report. View Report: %s', 'bassmah-staff-reports'),
            $user->display_name,
            get_the_date(get_option('date_format'), $report_id),
            $manager_name,
            $reason,
            admin_url('admin.php?page=bassmah-my-reports&view=report&id=' . $report_id)
        );

        wp_mail($user->user_email, $subject, $message);
    }
}
