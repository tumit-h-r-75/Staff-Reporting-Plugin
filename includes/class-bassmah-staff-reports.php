<?php
/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    Bassmah_Staff_Reports
 * @author     Tumit <tumit@bassmah.ca>
 */
if (!class_exists('Bassmah_Staff_Reports')) {
    class Bassmah_Staff_Reports {

    /**
     * The loader that's responsible for maintaining and registering all hooks that power
     * the plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      Bassmah_Staff_Reports_Loader    $loader    Maintains and registers all hooks for the plugin.
     */
    protected $loader;

    /**
     * The unique identifier of this plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string    $plugin_name    The string used to uniquely identify this plugin.
     */
    protected $plugin_name;

    /**
     * The current version of the plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string    $version    The current version of the plugin.
     */
    protected $version;

    /**
     * Define the core functionality of the plugin.
     *
     * Set the plugin name and the plugin version that can be used throughout the plugin.
     * Load the dependencies, define the locale, and set the hooks for the admin area and
     * the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function __construct() {
        if ( ! defined( 'BASSMAH_STAFF_REPORTS_VERSION' ) ) {
            define( 'BASSMAH_STAFF_REPORTS_VERSION', '1.0.0' );
        }
        $this->plugin_name = 'bassmah-staff-reports';
        $this->version = BASSMAH_STAFF_REPORTS_VERSION;

        $this->load_dependencies();
        $this->set_locale();
        $this->define_admin_hooks();
        $this->define_public_hooks();
        $this->define_api_hooks();
        $this->define_jwt_hooks();
        $this->define_cron_hooks();
    }

    /**
     * Load the required dependencies for this plugin.
     *
     * Include the following files that make up the plugin:
     *
     * - Bassmah_Staff_Reports_Loader. Orchestrates the hooks of the plugin.
     * - Bassmah_Staff_Reports_i18n. Defines internationalization functionality.
     * - Bassmah_Staff_Reports_Admin. Defines all hooks for the admin area.
     * - Bassmah_Staff_Reports_Public. Defines all hooks for the public side of the site.
     *
     * Create an instance of the loader which will be used to register the hooks
     * with WordPress.
     *
     * @since    1.0.0
     * @access   private
     */
    private function load_dependencies() {
        require_once BASSMAH_STAFF_REPORTS_PLUGIN_DIR . 'includes/class-loader.php';
        require_once BASSMAH_STAFF_REPORTS_PLUGIN_DIR . 'includes/class-i18n.php';
        require_once BASSMAH_STAFF_REPORTS_PLUGIN_DIR . 'includes/class-roles.php';
        require_once BASSMAH_STAFF_REPORTS_PLUGIN_DIR . 'includes/class-report.php';
        require_once BASSMAH_STAFF_REPORTS_PLUGIN_DIR . 'includes/class-salary.php';
        require_once BASSMAH_STAFF_REPORTS_PLUGIN_DIR . 'includes/class-salary-calculator.php';
        require_once BASSMAH_STAFF_REPORTS_PLUGIN_DIR . 'includes/class-jwt-auth.php';
        require_once BASSMAH_STAFF_REPORTS_PLUGIN_DIR . 'includes/class-service-container.php';
        require_once BASSMAH_STAFF_REPORTS_PLUGIN_DIR . 'includes/class-database-manager.php';
        require_once BASSMAH_STAFF_REPORTS_PLUGIN_DIR . 'includes/class-cache-manager.php';
        require_once BASSMAH_STAFF_REPORTS_PLUGIN_DIR . 'includes/class-security-manager.php';
        require_once BASSMAH_STAFF_REPORTS_PLUGIN_DIR . 'includes/class-validation-manager.php';
        require_once BASSMAH_STAFF_REPORTS_PLUGIN_DIR . 'includes/class-report-service.php';
        require_once BASSMAH_STAFF_REPORTS_PLUGIN_DIR . 'includes/class-salary-service.php';
        require_once BASSMAH_STAFF_REPORTS_PLUGIN_DIR . 'includes/class-user-service.php';
        require_once BASSMAH_STAFF_REPORTS_PLUGIN_DIR . 'includes/class-working-days-service.php';
        require_once BASSMAH_STAFF_REPORTS_PLUGIN_DIR . 'includes/class-exporter.php';
        require_once BASSMAH_STAFF_REPORTS_PLUGIN_DIR . 'includes/class-duplicate-check.php';
        require_once BASSMAH_STAFF_REPORTS_PLUGIN_DIR . 'includes/class-loading-component.php';
        require_once BASSMAH_STAFF_REPORTS_PLUGIN_DIR . 'admin/class-admin.php';
        require_once BASSMAH_STAFF_REPORTS_PLUGIN_DIR . 'public/class-public.php';
        require_once BASSMAH_STAFF_REPORTS_PLUGIN_DIR . 'api/class-rest-reports.php';
        require_once BASSMAH_STAFF_REPORTS_PLUGIN_DIR . 'api/class-rest-report-approvals.php';
        require_once BASSMAH_STAFF_REPORTS_PLUGIN_DIR . 'api/class-rest-salary.php';
        require_once BASSMAH_STAFF_REPORTS_PLUGIN_DIR . 'api/class-rest-export.php';
        require_once BASSMAH_STAFF_REPORTS_PLUGIN_DIR . 'api/class-rest-working-days.php';

        $this->loader = new Bassmah_Staff_Reports_Loader();
    }

    /**
     * Define the locale for internationalization.
     *
     * Uses the Bassmah_Staff_Reports_i18n class in order to set the domain and to register the hook
     * with WordPress.
     *
     * @since    1.0.0
     * @access   private
     */
    private function set_locale() {
        $plugin_i18n = new Bassmah_Staff_Reports_i18n();
        $this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );
    }

    /**
     * Register all of the hooks related to the admin area functionality
     * of the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_admin_hooks() {
        $plugin_admin = new Bassmah_Staff_Reports_Admin( $this->get_plugin_name(), $this->get_version() );

        $this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
        $this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );
        $this->loader->add_action( 'admin_menu', $plugin_admin, 'add_plugin_admin_menu' );
        $this->loader->add_action( 'admin_init', $plugin_admin, 'register_settings' );
        $this->loader->add_action( 'wp_ajax_bassmah_admin_ajax', $plugin_admin, 'handle_ajax_requests' );
    }

    /**
     * Register all of the hooks related to the public-facing functionality
     * of the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_public_hooks() {
        $plugin_public = new Bassmah_Staff_Reports_Public( $this->get_plugin_name(), $this->get_version() );

        $this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_styles' );
        $this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_scripts' );
        $this->loader->add_action( 'init', $plugin_public, 'register_shortcodes' );
        $this->loader->add_action( 'init', $plugin_public, 'register_ajax_actions' );
        
        // Email notification hooks for report approvals
        $this->loader->add_action( 'bassmah_report_approved', $plugin_public, 'notify_staff_report_approved', 10, 3 );
        $this->loader->add_action( 'bassmah_report_rejected', $plugin_public, 'notify_staff_report_rejected', 10, 3 );
        
        // Favicon functionality removed to prevent 404 errors
    }

    /**
     * Register all of the hooks related to the REST API functionality
     * of the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_api_hooks() {
        $rest_reports = new Bassmah_Staff_Reports_REST_Reports();
        $this->loader->add_action( 'rest_api_init', $rest_reports, 'register_routes' );

        $rest_approvals = new Bassmah_Staff_Reports_REST_Report_Approvals();
        $this->loader->add_action( 'rest_api_init', $rest_approvals, 'register_routes' );

        $rest_salary = new Bassmah_Staff_Reports_REST_Salary();
        $this->loader->add_action( 'rest_api_init', $rest_salary, 'register_routes' );

        $rest_export = new Bassmah_Staff_Reports_REST_Export();
        $this->loader->add_action( 'rest_api_init', $rest_export, 'register_routes' );
        
        $rest_working_days = new Bassmah_Staff_Reports_REST_Working_Days();
        $this->loader->add_action( 'rest_api_init', $rest_working_days, 'register_routes' );
    }

    /**
     * Register all of the hooks related to JWT authentication
     * of the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_jwt_hooks() {
        $jwt_auth = new Bassmah_Staff_Reports_JWT_Auth();
        $jwt_auth->define_jwt_hooks();
    }

    /**
     * Register all of the hooks related to cron jobs
     * of the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_cron_hooks() {
        // Schedule daily reminder if not already scheduled
        if (!wp_next_scheduled('bassmah_daily_reminder')) {
            $reminder_time = get_option('bassmah_reminder_time', '09:00');
            $time_parts = explode(':', $reminder_time);
            $hour = isset($time_parts[0]) ? (int)$time_parts[0] : 9;
            $minute = isset($time_parts[1]) ? (int)$time_parts[1] : 0;
            
            // Schedule for today at specified time
            $timestamp = strtotime('today ' . $hour . ':' . $minute . ':00');
            if ($timestamp < time()) {
                $timestamp = strtotime('tomorrow ' . $hour . ':' . $minute . ':00');
            }
            
            wp_schedule_event($timestamp, 'daily', 'bassmah_daily_reminder');
        }
        
        // Hook the reminder function
        $this->loader->add_action('bassmah_daily_reminder', $this, 'send_daily_reminder');
    }

    /**
     * Run the loader to execute all of the hooks with WordPress.
     *
     * @since    1.0.0
     */
    public function run() {
        $this->loader->run();
    }

    /**
     * The name of the plugin used to uniquely identify it within the context of
     * WordPress and to define internationalization functionality.
     *
     * @since     1.0.0
     * @return    string    The name of the plugin.
     */
    public function get_plugin_name() {
        return $this->plugin_name;
    }

    /**
     * Send daily reminder emails to staff
     *
     * @since    1.0.0
     */
    public function send_daily_reminder() {
        global $wpdb;
        
        // Get all staff users who haven't submitted today's report
        $table_reports = $wpdb->prefix . 'staff_reports';
        $today = current_time('Y-m-d');
        
        $query = $wpdb->prepare("
            SELECT u.ID, u.user_email, u.display_name
            FROM {$wpdb->users} u
            WHERE u.ID IN (
                SELECT user_id FROM {$wpdb->usermeta} 
                WHERE meta_key = '{$wpdb->prefix}capabilities' 
                AND meta_value LIKE '%\"bassmah_staff\"%'
            )
            AND u.ID NOT IN (
                SELECT DISTINCT user_id FROM $table_reports 
                WHERE report_date = %s 
                AND status IN ('submitted', 'approved')
            )
        ", $today);
        
        $staff_members = $wpdb->get_results($query);
        
        foreach ($staff_members as $staff) {
            $subject = __('Daily Report Reminder', 'bassmah-staff-reports');
            $message = sprintf(
                __('Hi %s,

This is a friendly reminder to submit your daily work report for today (%s).

Please log in to the system and submit your report before the end of the day.

Thank you,
Bassmah Team', 'bassmah-staff-reports'),
                $staff->display_name,
                $today
            );
            
            $headers = array('Content-Type: text/html; charset=UTF-8');
            wp_mail($staff->user_email, $subject, $message, $headers);
        }
    }

    /**
     * The reference to the class that orchestrates the hooks with the plugin.
     *
     * @since     1.0.0
     * @return    Bassmah_Staff_Reports_Loader    Orchestrates the hooks of the plugin.
     */
    public function get_loader() {
        return $this->loader;
    }

    /**
     * Add favicon to prevent 404 errors
     *
     * @since     1.0.0
     */
    public function add_favicon() {
        echo '<link rel="icon" type="image/png" href="' . plugin_dir_url(__FILE__) . 'assets/favicon.png' . '">';
    }

    /**
     * Retrieve the version number of the plugin.
     *
     * @since     1.0.0
     * @return    string    The version number of the plugin.
     */
    public function get_version() {
        return $this->version;
    }
}
}
