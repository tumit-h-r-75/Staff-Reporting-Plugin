<?php
/**
 * REST API Endpoints Class
 * Handles all API endpoints for the plugin
 */

if (!defined('ABSPATH')) {
    exit;
}

class BSR_REST_API {
    
    public function __construct() {
        add_action('rest_api_init', [$this, 'register_routes']);
    }
    
    public function register_routes() {
        register_rest_route('bsr/v1', '/reports', [
            'methods' => 'POST',
            'callback' => [$this, 'submit_report'],
            'permission_callback' => [$this, 'check_submit_permission']
        ]);
        
        register_rest_route('bsr/v1', '/reports', [
            'methods' => 'GET',
            'callback' => [$this, 'get_reports'],
            'permission_callback' => [$this, 'check_view_permission']
        ]);
        
        register_rest_route('bsr/v1', '/reports/mine', [
            'methods' => 'GET',
            'callback' => [$this, 'get_my_reports'],
            'permission_callback' => [$this, 'check_staff_permission']
        ]);
        
        register_rest_route('bsr/v1', '/reports/(?P<id>\d+)', [
            'methods' => 'GET',
            'callback' => [$this, 'get_single_report'],
            'permission_callback' => [$this, 'check_report_access']
        ]);
        
        register_rest_route('bsr/v1', '/reports/(?P<id>\d+)/comment', [
            'methods' => 'PUT',
            'callback' => [$this, 'add_comment'],
            'permission_callback' => [$this, 'check_manager_permission']
        ]);
        
        register_rest_route('bsr/v1', '/reports/(?P<id>\d+)/status', [
            'methods' => 'PUT',
            'callback' => [$this, 'update_status'],
            'permission_callback' => [$this, 'check_manager_permission']
        ]);
        
        register_rest_route('bsr/v1', '/salary/me', [
            'methods' => 'GET',
            'callback' => [$this, 'get_my_salary'],
            'permission_callback' => [$this, 'check_staff_permission']
        ]);
        
        register_rest_route('bsr/v1', '/salary/(?P<user_id>\d+)', [
            'methods' => 'GET',
            'callback' => [$this, 'get_user_salary'],
            'permission_callback' => [$this, 'check_manager_permission']
        ]);
        
        register_rest_route('bsr/v1', '/export/reports', [
            'methods' => 'GET',
            'callback' => [$this, 'export_reports'],
            'permission_callback' => [$this, 'check_manager_permission']
        ]);
        
        register_rest_route('bsr/v1', '/working-days', [
            'methods' => 'GET',
            'callback' => [$this, 'get_working_days'],
            'permission_callback' => [$this, 'check_staff_permission']
        ]);
    }
    
    public function submit_report($request) {
        $user_id = get_current_user_id();
        $data = [
            'user_id' => $user_id,
            'report_date' => sanitize_text_field($request->get_param('report_date')),
            'tasks' => $request->get_param('tasks')
        ];
        
        $result = Basmah_Staff_Reports_Reports::create_report($data);
        
        if (is_wp_error($result)) {
            return new WP_Error(
                'submission_failed',
                $result->get_error_message(),
                ['status' => 400]
            );
        }
        
        return new WP_REST_Response([
            'success' => true,
            'message' => 'Report submitted successfully',
            'report_id' => $result
        ], 200);
    }
    
    public function get_reports($request) {
        $args = [
            'user_id' => $request->get_param('user_id'),
            'date_from' => $request->get_param('date_from'),
            'date_to' => $request->get_param('date_to'),
            'status' => $request->get_param('status'),
            'role' => $request->get_param('role'),
            'limit' => $request->get_param('limit') ?: 50,
            'offset' => $request->get_param('offset') ?: 0
        ];
        
        $reports = Basmah_Staff_Reports_Reports::get_reports($args);
        
        return new WP_REST_Response($reports, 200);
    }
    
    public function get_my_reports($request) {
        $user_id = get_current_user_id();
        $args = [
            'user_id' => $user_id,
            'date_from' => $request->get_param('date_from'),
            'date_to' => $request->get_param('date_to'),
            'status' => $request->get_param('status'),
            'limit' => $request->get_param('limit') ?: 50,
            'offset' => $request->get_param('offset') ?: 0
        ];
        
        $reports = Basmah_Staff_Reports_Reports::get_reports($args);
        
        return new WP_REST_Response($reports, 200);
    }
    
    public function get_single_report($request) {
        $report_id = $request->get_param('id');
        $report = Basmah_Staff_Reports_Reports::get_report($report_id);
        
        if (!$report) {
            return new WP_Error(
                'not_found',
                'Report not found',
                ['status' => 404]
            );
        }
        
        return new WP_REST_Response($report, 200);
    }
    
    public function add_comment($request) {
        $report_id = $request->get_param('id');
        $comment = sanitize_textarea_field($request->get_param('comment'));
        
        $result = Basmah_Staff_Reports_Reports::update_report($report_id, [
            'manager_comment' => $comment
        ]);
        
        if ($result === false) {
            return new WP_Error(
                'update_failed',
                'Failed to add comment',
                ['status' => 500]
            );
        }
        
        return new WP_REST_Response([
            'success' => true,
            'message' => 'Comment added successfully'
        ], 200);
    }
    
    public function update_status($request) {
        $report_id = $request->get_param('id');
        $status = sanitize_text_field($request->get_param('status'));
        $comment = sanitize_textarea_field($request->get_param('comment'));
        
        if (!in_array($status, ['pending', 'approved', 'rejected'])) {
            return new WP_Error(
                'invalid_status',
                'Invalid status',
                ['status' => 400]
            );
        }
        
        $result = Basmah_Staff_Reports_Reports::update_report($report_id, [
            'status' => $status,
            'manager_comment' => $comment
        ]);
        
        if ($result === false) {
            return new WP_Error(
                'update_failed',
                'Failed to update status',
                ['status' => 500]
            );
        }
        
        return new WP_REST_Response([
            'success' => true,
            'message' => 'Status updated successfully'
        ], 200);
    }
    
    public function get_my_salary($request) {
        $user_id = get_current_user_id();
        $month = $request->get_param('month') ?: date('m');
        $year = $request->get_param('year') ?: date('Y');
        
        $salary_data = Basmah_Staff_Reports_Salary::calculate_salary($user_id, $month, $year);
        
        return new WP_REST_Response($salary_data, 200);
    }
    
    public function get_user_salary($request) {
        $user_id = $request->get_param('user_id');
        $month = $request->get_param('month') ?: date('m');
        $year = $request->get_param('year') ?: date('Y');
        
        $salary_data = Basmah_Staff_Reports_Salary::calculate_salary($user_id, $month, $year);
        
        return new WP_REST_Response($salary_data, 200);
    }
    
    public function export_reports($request) {
        $args = [
            'user_id' => $request->get_param('user_id'),
            'date_from' => $request->get_param('date_from'),
            'date_to' => $request->get_param('date_to'),
            'status' => $request->get_param('status'),
            'role' => $request->get_param('role')
        ];
        
        $reports = Basmah_Staff_Reports_Reports::get_reports($args);
        Basmah_Staff_Reports_Reports::export_to_csv($reports);
    }
    
    public function get_working_days($request) {
        $month = $request->get_param('month') ?: date('m');
        $year = $request->get_param('year') ?: date('Y');
        
        $working_days = Basmah_Staff_Reports_Working_Days::get_working_days($month, $year);
        
        return new WP_REST_Response($working_days, 200);
    }
    
    // Permission callbacks
    public function check_submit_permission() {
        return current_user_can('bsr_submit_report') || current_user_can('manage_options');
    }
    
    public function check_view_permission() {
        return current_user_can('bsr_view_all_reports') || current_user_can('manage_options');
    }
    
    public function check_staff_permission() {
        return current_user_can('bsr_submit_report');
    }
    
    public function check_manager_permission() {
        return current_user_can('bsr_view_all_reports') || current_user_can('manage_options');
    }
    
    public function check_report_access($request) {
        $user_id = get_current_user_id();
        $report_id = $request->get_param('id');
        $report = Basmah_Staff_Reports_Reports::get_report($report_id);
        
        // Allow access if user is owner, manager, or admin
        return ($report && ($report['user_id'] == $user_id || current_user_can('bsr_view_all_reports') || current_user_can('manage_options')));
    }
}
