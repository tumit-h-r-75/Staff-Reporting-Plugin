<?php
class Basmah_Staff_Reports_Admin {
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        add_action('admin_init', array($this, 'handle_admin_actions'));
    }
    
    public function add_admin_menu() {
        add_menu_page(
            'Basmah Staff Reports',
            'Staff Reports',
            'basmah_view_all_reports',
            'bassmah-staff-reports',
            array($this, 'render_dashboard'),
            'dashicons-chart-bar',
            25
        );
        
        add_submenu_page(
            'bassmah-staff-reports',
            'Dashboard',
            'Dashboard',
            'basmah_view_all_reports',
            'bassmah-staff-reports',
            array($this, 'render_dashboard')
        );
        
        add_submenu_page(
            'bassmah-staff-reports',
            'All Reports',
            'All Reports',
            'basmah_view_all_reports',
            'bsr-all-reports',
            array($this, 'render_all_reports')
        );
        
        add_submenu_page(
            'bassmah-staff-reports',
            'Single Report',
            'Single Report',
            'basmah_edit_reports',
            'bsr-single-report',
            array($this, 'render_single_report')
        );
        
        add_submenu_page(
            'bassmah-staff-reports',
            'Staff List',
            'Staff List',
            'basmah_manage_staff',
            'bsr-staff-list',
            array($this, 'render_staff_list')
        );
        
        add_submenu_page(
            'bassmah-staff-reports',
            'Salary Settings',
            'Salary Settings',
            'basmah_manage_salaries',
            'bsr-salary-settings',
            array($this, 'render_salary_settings')
        );
        
        add_submenu_page(
            'bassmah-staff-reports',
            'Working Days & Holidays',
            'Working Days & Holidays',
            'basmah_manage_working_days',
            'bsr-working-days',
            array($this, 'render_working_days')
        );
    }
    
    public function enqueue_admin_assets($hook) {
        wp_enqueue_style('bsr-admin-css', BASMAH_STAFF_REPORTS_PLUGIN_URL . 'admin/assets/admin.css');
        wp_enqueue_script('bsr-admin-js', BASMAH_STAFF_REPORTS_PLUGIN_URL . 'admin/assets/admin.js', array('jquery'), null, true);
    }
    
    public function handle_admin_actions() {
        if (!current_user_can('basmah_view_all_reports') && !current_user_can('manage_options')) {
            return;
        }
        
        if (isset($_POST['bsr_save_comment'])) {
            $this->handle_save_comment();
        }
        
        if (isset($_POST['bsr_save_salary']) && current_user_can('basmah_manage_salaries')) {
            $this->handle_save_salary();
        }
        
        if (isset($_POST['bsr_add_holiday']) && current_user_can('basmah_manage_working_days')) {
            $this->handle_add_holiday();
        }
        
        if (isset($_GET['bsr_delete_holiday']) && current_user_can('basmah_manage_working_days')) {
            $this->handle_delete_holiday();
        }
        
        if (isset($_POST['bsr_approve_report']) && current_user_can('basmah_edit_reports')) {
            $this->handle_approve_report();
        }
        
        if (isset($_POST['bsr_reject_report']) && current_user_can('basmah_edit_reports')) {
            $this->handle_reject_report();
        }
        
        if (isset($_GET['bsr_export']) && current_user_can('basmah_export_reports')) {
            $this->handle_export();
        }
        
        if (isset($_GET['bsr_delete_report']) && current_user_can('basmah_edit_reports')) {
            $this->handle_delete_report();
        }
        
        if (isset($_POST['bsr_update_report']) && current_user_can('basmah_edit_reports')) {
            $this->handle_update_report();
        }
    }
    
    private function handle_save_comment() {
        if (!isset($_POST['bsr_comment_nonce']) || !wp_verify_nonce($_POST['bsr_comment_nonce'], 'bsr_save_comment')) {
            return;
        }
        
        $report_id = intval($_POST['report_id']);
        $comment = sanitize_textarea_field($_POST['manager_comment']);
        
        Basmah_Staff_Reports_Reports::update_report($report_id, array(
            'manager_comment' => $comment
        ));
        
        wp_redirect(add_query_arg('comment_saved', '1', admin_url('admin.php?page=bsr-single-report&report_id=' . $report_id)));
        exit;
    }
    
    private function handle_approve_report() {
        if (!isset($_POST['bsr_approve_nonce']) || !wp_verify_nonce($_POST['bsr_approve_nonce'], 'bsr_approve_report')) {
            return;
        }
        
        $report_id = intval($_POST['report_id']);
        
        Basmah_Staff_Reports_Reports::update_report($report_id, array(
            'status' => 'approved'
        ));
        
        wp_redirect(add_query_arg('report_approved', '1', admin_url('admin.php?page=bsr-single-report&report_id=' . $report_id)));
        exit;
    }
    
    private function handle_reject_report() {
        if (!isset($_POST['bsr_reject_nonce']) || !wp_verify_nonce($_POST['bsr_reject_nonce'], 'bsr_reject_report')) {
            return;
        }
        
        $report_id = intval($_POST['report_id']);
        $comment = isset($_POST['manager_comment']) ? sanitize_textarea_field($_POST['manager_comment']) : '';
        
        if (empty($comment)) {
            wp_redirect(add_query_arg('comment_required', '1', admin_url('admin.php?page=bsr-single-report&report_id=' . $report_id)));
            exit;
        }
        
        Basmah_Staff_Reports_Reports::update_report($report_id, array(
            'status' => 'rejected',
            'manager_comment' => $comment
        ));
        
        wp_redirect(add_query_arg('report_rejected', '1', admin_url('admin.php?page=bsr-single-report&report_id=' . $report_id)));
        exit;
    }
    
    private function handle_save_salary() {
        if (!isset($_POST['bsr_salary_nonce']) || !wp_verify_nonce($_POST['bsr_salary_nonce'], 'bsr_save_salary')) {
            return;
        }
        
        $user_id = intval($_POST['user_id']);
        
        Basmah_Staff_Reports_Salary::save_settings(array(
            'user_id' => $user_id,
            'monthly_salary' => floatval($_POST['monthly_salary']),
            'working_days_per_month' => intval($_POST['working_days_per_month']),
            'currency' => sanitize_text_field($_POST['currency'])
        ));
        
        wp_redirect(add_query_arg('salary_saved', '1', admin_url('admin.php?page=bsr-salary-settings')));
        exit;
    }
    
    private function handle_add_holiday() {
        if (!isset($_POST['bsr_holiday_nonce']) || !wp_verify_nonce($_POST['bsr_holiday_nonce'], 'bsr_add_holiday')) {
            return;
        }
        
        Basmah_Staff_Reports_Working_Days::add_day(array(
            'work_date' => sanitize_text_field($_POST['work_date']),
            'is_holiday' => 1,
            'holiday_name' => sanitize_text_field($_POST['holiday_name'])
        ));
        
        wp_redirect(add_query_arg('holiday_added', '1', admin_url('admin.php?page=bsr-working-days')));
        exit;
    }
    
    private function handle_delete_holiday() {
        check_admin_referer('bsr_delete_holiday');

        $id = intval($_GET['bsr_delete_holiday']);
        Basmah_Staff_Reports_Working_Days::delete_day($id);
        
        wp_redirect(add_query_arg('holiday_deleted', '1', admin_url('admin.php?page=bsr-working-days')));
        exit;
    }
    
    private function handle_export() {
        $args = array();
        
        if (isset($_GET['user_id']) && !empty($_GET['user_id'])) {
            $args['user_id'] = intval($_GET['user_id']);
        }
        
        if (isset($_GET['date_from']) && !empty($_GET['date_from'])) {
            $args['date_from'] = sanitize_text_field($_GET['date_from']);
        }
        
        if (isset($_GET['date_to']) && !empty($_GET['date_to'])) {
            $args['date_to'] = sanitize_text_field($_GET['date_to']);
        }

        if (isset($_GET['role']) && !empty($_GET['role'])) {
            $role = sanitize_text_field($_GET['role']);
            if (in_array($role, array('basmah_staff', 'basmah_manager'), true)) {
                $args['role'] = $role;
            }
        }
        
        $reports = Basmah_Staff_Reports_Reports::get_reports($args);
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename=staff-reports-' . date('Y-m-d') . '.csv');
        
        $output = fopen('php://output', 'w');
        fputcsv($output, array('ID', 'Employee Name', 'Date', 'Status', 'Tasks', 'Manager Comment', 'Submitted At'));
        
        foreach ($reports as $report) {
            $tasks = json_decode($report['tasks_json'], true);
            $task_summary = '';
            
            if ($tasks && is_array($tasks)) {
                foreach ($tasks as $task) {
                    $task_summary .= $task['task_category'] . ': ' . $task['task_description'] . ' | ';
                }
                $task_summary = rtrim($task_summary, ' | ');
            }
            
            fputcsv($output, array(
                $report['id'],
                $report['display_name'],
                $report['report_date'],
                $report['status'],
                $task_summary,
                $report['manager_comment'],
                $report['created_at']
            ));
        }
        
        fclose($output);
        exit;
    }
    
    private function handle_delete_report() {
        check_admin_referer('bsr_delete_report');

        $report_id = intval($_GET['bsr_delete_report']);
        Basmah_Staff_Reports_Reports::delete_report($report_id);
        wp_redirect(add_query_arg('report_deleted', '1', admin_url('admin.php?page=bsr-all-reports')));
        exit;
    }
    
    private function handle_update_report() {
        if (!isset($_POST['bsr_update_nonce']) || !wp_verify_nonce($_POST['bsr_update_nonce'], 'bsr_update_report')) {
            return;
        }
        
        $report_id = intval($_POST['report_id']);
        $tasks_json = isset($_POST['tasks_json']) ? $_POST['tasks_json'] : '';
        
        $data = array();
        if (!empty($tasks_json)) {
            $data['tasks_json'] = $tasks_json;
        }
        
        Basmah_Staff_Reports_Reports::update_report($report_id, $data);
        wp_redirect(add_query_arg('report_updated', '1', admin_url('admin.php?page=bsr-single-report&report_id=' . $report_id)));
        exit;
    }
    
    public function render_dashboard() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'staff_reports';
        $users_table = $wpdb->prefix . 'users';
        $today = current_time('Y-m-d');
        $current_month = date('m');
        $current_year = date('Y');
        
        $total_reports = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
        $today_reports = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table_name WHERE report_date = %s", $today));
        $monthly_reports = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table_name WHERE MONTH(report_date) = %d AND YEAR(report_date) = %d", $current_month, $current_year));
        $pending_reports = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE status = 'pending'");
        
        $recent_reports = $wpdb->get_results("
            SELECT r.*, u.display_name
            FROM $table_name r
            JOIN $users_table u ON r.user_id = u.ID
            ORDER BY r.created_at DESC
            LIMIT 5
        ", ARRAY_A);
        
        require_once BASMAH_STAFF_REPORTS_PLUGIN_DIR . 'admin/views/dashboard.php';
    }
    
    public function render_all_reports() {
        $args = array();
        
        if (isset($_GET['user_id']) && !empty($_GET['user_id'])) {
            $args['user_id'] = intval($_GET['user_id']);
        }
        
        if (isset($_GET['status']) && !empty($_GET['status'])) {
            $args['status'] = sanitize_text_field($_GET['status']);
        }
        
        if (isset($_GET['date_from']) && !empty($_GET['date_from'])) {
            $args['date_from'] = sanitize_text_field($_GET['date_from']);
        }
        
        if (isset($_GET['date_to']) && !empty($_GET['date_to'])) {
            $args['date_to'] = sanitize_text_field($_GET['date_to']);
        }

        if (isset($_GET['role']) && !empty($_GET['role'])) {
            $role = sanitize_text_field($_GET['role']);
            if (in_array($role, array('basmah_staff', 'basmah_manager'), true)) {
                $args['role'] = $role;
            }
        }
        
        $reports = Basmah_Staff_Reports_Reports::get_reports($args);
        $staff_users = get_users(array('role__in' => array('basmah_staff', 'basmah_manager')));
        
        require_once BASMAH_STAFF_REPORTS_PLUGIN_DIR . 'admin/views/all-reports.php';
    }
    
    public function render_single_report() {
        if (!isset($_GET['report_id'])) {
            echo '<div class="wrap"><h1>Report Details</h1><p>No report ID provided.</p></div>';
            return;
        }
        
        $report_id = intval($_GET['report_id']);
        $report = Basmah_Staff_Reports_Reports::get_report($report_id);
        
        if (!$report) {
            echo '<div class="wrap"><h1>Report Details</h1><p>Report not found.</p></div>';
            return;
        }
        
        $current_user = wp_get_current_user();
        $can_manage = current_user_can('manage_options') || current_user_can('basmah_view_all_reports');
        
        if (!$can_manage && $report['user_id'] != $current_user->ID) {
            echo '<div class="wrap"><h1>Report Details</h1><p>You do not have permission to view this report.</p></div>';
            return;
        }
        
        require_once BASMAH_STAFF_REPORTS_PLUGIN_DIR . 'admin/views/single-report.php';
    }
    
    public function render_salary_settings() {
        $staff_users = get_users(array('role__in' => array('basmah_staff', 'basmah_manager')));
        require_once BASMAH_STAFF_REPORTS_PLUGIN_DIR . 'admin/views/salary-settings.php';
    }
    
    public function render_working_days() {
        $current_month = isset($_GET['month']) ? intval($_GET['month']) : date('m');
        $current_year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');
        
        $days = Basmah_Staff_Reports_Working_Days::get_all_days($current_month, $current_year);
        require_once BASMAH_STAFF_REPORTS_PLUGIN_DIR . 'admin/views/working-days.php';
    }
    
    public function render_staff_list() {
        $staff_users = get_users(array(
            'role__in' => array('basmah_staff', 'basmah_manager'),
            'orderby' => 'display_name',
            'order' => 'ASC'
        ));
        
        require_once BASMAH_STAFF_REPORTS_PLUGIN_DIR . 'admin/views/staff-list.php';
    }
}
