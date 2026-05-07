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
            case 'export_reports':
                $this->export_reports();
                break;
            case 'export_salary':
                $this->export_salary();
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
}
