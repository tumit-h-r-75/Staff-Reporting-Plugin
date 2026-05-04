<?php
class Basmah_Staff_Reports_Admin {
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
    }
    
    public function add_admin_menu() {
        add_menu_page(
            'Basmah Staff Reports',
            'Staff Reports',
            'manage_options',
            'bassmah-staff-reports',
            array($this, 'render_dashboard'),
            'dashicons-chart-bar',
            25
        );
        
        add_submenu_page(
            'bassmah-staff-reports',
            'Dashboard',
            'Dashboard',
            'manage_options',
            'bassmah-staff-reports',
            array($this, 'render_dashboard')
        );
        
        add_submenu_page(
            'bassmah-staff-reports',
            'All Reports',
            'All Reports',
            'manage_options',
            'bsr-all-reports',
            array($this, 'render_all_reports')
        );
        
        add_submenu_page(
            'bassmah-staff-reports',
            'Staff List',
            'Staff List',
            'manage_options',
            'bsr-staff-list',
            array($this, 'render_staff_list')
        );
        
        add_submenu_page(
            'bassmah-staff-reports',
            'Salary Settings',
            'Salary Settings',
            'manage_options',
            'bsr-salary-settings',
            array($this, 'render_salary_settings')
        );
        
        add_submenu_page(
            'bassmah-staff-reports',
            'Working Days',
            'Working Days',
            'manage_options',
            'bsr-working-days',
            array($this, 'render_working_days')
        );
    }
    
    public function enqueue_admin_assets($hook) {
        wp_enqueue_style('bsr-admin-css', BASMAH_STAFF_REPORTS_PLUGIN_URL . 'admin/assets/admin.css');
        wp_enqueue_script('bsr-admin-js', BASMAH_STAFF_REPORTS_PLUGIN_URL . 'admin/assets/admin.js', array('jquery'), null, true);
    }
    
    public function render_dashboard() {
        require_once BASMAH_STAFF_REPORTS_PLUGIN_DIR . 'admin/views/dashboard.php';
    }
    
    public function render_all_reports() {
        require_once BASMAH_STAFF_REPORTS_PLUGIN_DIR . 'admin/views/all-reports.php';
    }
    
    public function render_single_report() {
        require_once BASMAH_STAFF_REPORTS_PLUGIN_DIR . 'admin/views/single-report.php';
    }
    
    public function render_salary_settings() {
        require_once BASMAH_STAFF_REPORTS_PLUGIN_DIR . 'admin/views/salary-settings.php';
    }
    
    public function render_working_days() {
        require_once BASMAH_STAFF_REPORTS_PLUGIN_DIR . 'admin/views/working-days.php';
    }
    
    public function render_staff_list() {
        require_once BASMAH_STAFF_REPORTS_PLUGIN_DIR . 'admin/views/staff-list.php';
    }
}

new Basmah_Staff_Reports_Admin();
