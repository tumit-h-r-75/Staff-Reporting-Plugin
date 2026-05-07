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
            $this->plugin_name,
            plugin_dir_url(__FILE__) . 'css/public-style.css',
            array(),
            $this->version,
            'all'
        );

        // Add date picker CSS
        wp_enqueue_style('jquery-ui-datepicker');
    }

    /**
     * Register the JavaScript for the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function enqueue_scripts() {
        wp_enqueue_script(
            $this->plugin_name,
            plugin_dir_url(__FILE__) . 'js/public-scripts.js',
            array('jquery', 'jquery-ui-datepicker'),
            $this->version,
            false
        );

        // Localize script
        wp_localize_script($this->plugin_name, 'bassmah_public', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('bassmah_public_nonce'),
            'rest_url' => rest_url('bassmah/v1/'),
            'strings' => array(
                'confirm_submit' => __('Are you sure you want to submit this report?', 'bassmah-staff-reports'),
                'task_required' => __('Task description is required.', 'bassmah-staff-reports'),
                'loading' => __('Loading...', 'bassmah-staff-reports'),
                'report_submitted' => __('Report submitted successfully!', 'bassmah-staff-reports'),
                'error_occurred' => __('An error occurred. Please try again.', 'bassmah-staff-reports')
            )
        ));
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
        check_ajax_referer('bassmah_public_nonce', 'nonce');

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
}
