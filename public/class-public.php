<?php
class Basmah_Staff_Reports_Public {
    public function __construct() {
        add_shortcode('bsr_staff_dashboard', array($this, 'render_staff_dashboard'));
        add_shortcode('bsr_report_form', array($this, 'render_report_form'));
        add_shortcode('bsr_my_reports', array($this, 'render_my_reports'));
        add_shortcode('bsr_salary_view', array($this, 'render_salary_view'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_public_assets'));
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
}

new Basmah_Staff_Reports_Public();
