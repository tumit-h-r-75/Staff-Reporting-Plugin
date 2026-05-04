<?php

if (!defined('ABSPATH')) {
    exit;
}

class Bassmah_Staff_Reports {

    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->load_dependencies();
        $this->define_admin_hooks();
        $this->define_public_hooks();
        $this->define_api_hooks();
        $this->define_login_redirect_hook();
    }

    private function load_dependencies() {
        require_once BASMAH_STAFF_REPORTS_PLUGIN_DIR . 'includes/class-activator.php';
        require_once BASMAH_STAFF_REPORTS_PLUGIN_DIR . 'includes/class-deactivator.php';
        require_once BASMAH_STAFF_REPORTS_PLUGIN_DIR . 'includes/class-roles.php';
        require_once BASMAH_STAFF_REPORTS_PLUGIN_DIR . 'includes/class-reports.php';
        require_once BASMAH_STAFF_REPORTS_PLUGIN_DIR . 'includes/class-salary.php';
        require_once BASMAH_STAFF_REPORTS_PLUGIN_DIR . 'includes/class-working-days.php';
        require_once BASMAH_STAFF_REPORTS_PLUGIN_DIR . 'includes/class-security.php';
        require_once BASMAH_STAFF_REPORTS_PLUGIN_DIR . 'includes/class-export.php';
        require_once BASMAH_STAFF_REPORTS_PLUGIN_DIR . 'includes/class-helper.php';

        if (is_admin()) {
            require_once BASMAH_STAFF_REPORTS_PLUGIN_DIR . 'admin/class-admin.php';
        }

        require_once BASMAH_STAFF_REPORTS_PLUGIN_DIR . 'public/class-public.php';

        require_once BASMAH_STAFF_REPORTS_PLUGIN_DIR . 'api/class-api-reports.php';
        require_once BASMAH_STAFF_REPORTS_PLUGIN_DIR . 'api/class-api-salary.php';
        require_once BASMAH_STAFF_REPORTS_PLUGIN_DIR . 'api/class-api-auth.php';
    }

    private function define_admin_hooks() {
        if (is_admin()) {
            $plugin_admin = new Basmah_Staff_Reports_Admin();
        }
    }

    private function define_public_hooks() {
        $plugin_public = new Basmah_Staff_Reports_Public();
    }

    private function define_api_hooks() {
        new Basmah_Staff_Reports_API_Reports();
        new Basmah_Staff_Reports_API_Salary();
        new Basmah_Staff_Reports_API_Auth();
    }

    private function define_login_redirect_hook() {
        add_filter('login_redirect', array($this, 'handle_login_redirect'), 10, 3);
    }

    public function handle_login_redirect($redirect_to, $request, $user) {
        if (isset($user->roles) && is_array($user->roles)) {
            if (in_array('basmah_manager', $user->roles) || current_user_can('manage_options')) {
                return admin_url('admin.php?page=bsr-all-reports');
            } elseif (in_array('basmah_staff', $user->roles)) {
                return home_url('/dashboard');
            }
        }
        return $redirect_to;
    }

    public function run() {
    }
}
