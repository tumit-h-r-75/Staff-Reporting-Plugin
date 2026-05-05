<?php
class Basmah_Staff_Reports_API_Reports {
    public function __construct() {
        add_action('rest_api_init', array($this, 'register_routes'));
    }
    
    public function register_routes() {
        foreach (array('bsr/v1', 'bassmah/v1') as $namespace) {
            register_rest_route($namespace, '/reports', array(
                'methods' => 'GET',
                'callback' => array($this, 'get_reports'),
                'permission_callback' => array($this, 'check_permission'),
            ));

            register_rest_route($namespace, '/reports', array(
                'methods' => 'POST',
                'callback' => array($this, 'create_report'),
                'permission_callback' => array($this, 'check_permission'),
            ));

            register_rest_route($namespace, '/reports/mine', array(
                'methods' => 'GET',
                'callback' => array($this, 'get_my_reports'),
                'permission_callback' => array($this, 'check_permission'),
            ));
        }
    }
    
    public function check_permission($request) {
        if (!is_user_logged_in()) {
            return false;
        }

        // Check nonce for POST requests
        if ($request->get_method() === 'POST') {
            $nonce = $request->get_header('X-WP-Nonce');
            if (!$nonce || !wp_verify_nonce($nonce, 'wp_rest')) {
                return false;
            }
            return current_user_can('basmah_submit_report') || current_user_can('basmah_edit_reports') || current_user_can('manage_options');
        }

        if ($request->get_method() === 'GET') {
            return current_user_can('basmah_view_my_reports') || current_user_can('basmah_view_all_reports') || current_user_can('manage_options');
        }

        return false;
    }
    
    public function get_reports($request) {
        $args = array();

        if (current_user_can('basmah_view_all_reports') || current_user_can('manage_options')) {
            $user_id = $request->get_param('user_id');
            $status = $request->get_param('status');
            $date_from = $request->get_param('date_from');
            $date_to = $request->get_param('date_to');
            $role = $request->get_param('role');

            if (!empty($user_id)) {
                $args['user_id'] = intval($user_id);
            }
            if (!empty($status)) {
                $args['status'] = sanitize_text_field($status);
            }
            if (!empty($date_from)) {
                $args['date_from'] = sanitize_text_field($date_from);
            }
            if (!empty($date_to)) {
                $args['date_to'] = sanitize_text_field($date_to);
            }
            if (!empty($role)) {
                $role = sanitize_text_field($role);
                if (in_array($role, array('basmah_staff', 'basmah_manager'), true)) {
                    $args['role'] = $role;
                }
            }
        } else {
            $args['user_id'] = get_current_user_id();
        }

        $reports = Basmah_Staff_Reports_Reports::get_reports($args);
        return new WP_REST_Response($reports, 200);
    }
    
    public function create_report($request) {
        $params = $request->get_json_params();
        if (!is_array($params)) {
            return new WP_Error('bsr_invalid_payload', 'Invalid report payload.', array('status' => 400));
        }

        $user_id = get_current_user_id();
        if ((current_user_can('basmah_edit_reports') || current_user_can('manage_options')) && !empty($params['user_id'])) {
            $user_id = intval($params['user_id']);
        }

        $report_date = !empty($params['report_date']) ? sanitize_text_field($params['report_date']) : current_time('Y-m-d');
        if (Basmah_Staff_Reports_Reports::report_exists($user_id, $report_date)) {
            return new WP_Error('bsr_duplicate_report', 'A report already exists for this user and date.', array('status' => 409));
        }

        $tasks = isset($params['tasks']) && is_array($params['tasks']) ? $this->sanitize_tasks($params['tasks']) : array();
        if (empty($tasks)) {
            return new WP_Error('bsr_empty_tasks', 'At least one task is required.', array('status' => 400));
        }

        $result = Basmah_Staff_Reports_Reports::create_report(array(
            'user_id' => $user_id,
            'report_date' => $report_date,
            'tasks' => $tasks,
        ));

        if ($result) {
            // Send email notification to managers
            Basmah_Staff_Reports_Emails::notify_manager_on_report_submission($user_id, $report_date);
            return new WP_REST_Response(array('success' => true, 'message' => 'Report submitted successfully'), 201);
        }

        return new WP_REST_Response(array('success' => false, 'message' => 'Failed to submit report'), 500);
    }

    public function get_my_reports($request) {
        $reports = Basmah_Staff_Reports_Reports::get_reports(array(
            'user_id' => get_current_user_id(),
        ));

        return new WP_REST_Response($reports, 200);
    }

    private function sanitize_tasks($tasks) {
        $sanitized_tasks = array();

        foreach ($tasks as $task) {
            if (!is_array($task)) {
                continue;
            }

            $task_category = isset($task['task_category']) ? sanitize_text_field($task['task_category']) : '';
            $task_description = isset($task['task_description']) ? sanitize_textarea_field($task['task_description']) : '';
            $status = isset($task['status']) ? sanitize_text_field($task['status']) : '';
            $next_action = isset($task['next_action']) ? sanitize_textarea_field($task['next_action']) : '';

            if ($task_category === '' || $task_description === '' || $status === '' || $next_action === '') {
                continue;
            }

            $task_data = array(
                'task_category' => $task_category,
                'task_description' => $task_description,
                'status' => $status,
                'next_action' => $next_action,
            );

            if (!empty($task['manager_assigned_task'])) {
                $task_data['manager_assigned_task'] = sanitize_textarea_field($task['manager_assigned_task']);
            }
            if (!empty($task['additional_notes'])) {
                $task_data['additional_notes'] = sanitize_textarea_field($task['additional_notes']);
            }

            $sanitized_tasks[] = $task_data;
        }

        return $sanitized_tasks;
    }
}
