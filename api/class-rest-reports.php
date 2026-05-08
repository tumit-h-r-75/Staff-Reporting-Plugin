<?php
/**
 * REST API endpoints for reports.
 *
 * @since      1.0.0
 * @package    Bassmah_Staff_Reports
 * @author     Tumit <tumit@bassmah.ca>
 */
if (!class_exists('Bassmah_Staff_Reports_REST_Reports')) {
    class Bassmah_Staff_Reports_REST_Reports {

    /**
     * Register REST API routes
     *
     * @since    1.0.0
     */
    public function register_routes() {
        register_rest_route('bassmah/v1', '/reports', array(
            array(
                'methods' => WP_REST_Server::CREATABLE,
                'callback' => array($this, 'create_report'),
                'permission_callback' => array($this, 'create_report_permissions_check'),
                'args' => array(
                    'tasks' => array(
                        'required' => true,
                        'type' => 'array',
                        'description' => __('Array of tasks for the report', 'bassmah-staff-reports'),
                    ),
                    'report_date' => array(
                        'required' => false,
                        'type' => 'string',
                        'format' => 'date',
                        'description' => __('Report date (defaults to today)', 'bassmah-staff-reports'),
                    ),
                    'status' => array(
                        'required' => false,
                        'type' => 'string',
                        'enum' => array('draft', 'submitted'),
                        'description' => __('Report status', 'bassmah-staff-reports'),
                    ),
                ),
            ),
            array(
                'methods' => WP_REST_Server::READABLE,
                'callback' => array($this, 'get_reports'),
                'permission_callback' => array($this, 'get_reports_permissions_check'),
                'args' => array(
                    'user_id' => array(
                        'required' => false,
                        'type' => 'integer',
                        'description' => __('Filter by user ID', 'bassmah-staff-reports'),
                    ),
                    'status' => array(
                        'required' => false,
                        'type' => 'string',
                        'enum' => array('draft', 'submitted', 'approved', 'rejected'),
                        'description' => __('Filter by status', 'bassmah-staff-reports'),
                    ),
                    'date_from' => array(
                        'required' => false,
                        'type' => 'string',
                        'format' => 'date',
                        'description' => __('Filter by start date', 'bassmah-staff-reports'),
                    ),
                    'date_to' => array(
                        'required' => false,
                        'type' => 'string',
                        'format' => 'date',
                        'description' => __('Filter by end date', 'bassmah-staff-reports'),
                    ),
                    'limit' => array(
                        'required' => false,
                        'type' => 'integer',
                        'default' => 50,
                        'description' => __('Number of reports to return', 'bassmah-staff-reports'),
                    ),
                    'offset' => array(
                        'required' => false,
                        'type' => 'integer',
                        'default' => 0,
                        'description' => __('Number of reports to skip', 'bassmah-staff-reports'),
                    ),
                ),
            ),
        ));

        register_rest_route('bassmah/v1', '/reports/mine', array(
            array(
                'methods' => WP_REST_Server::READABLE,
                'callback' => array($this, 'get_my_reports'),
                'permission_callback' => array($this, 'get_my_reports_permissions_check'),
                'args' => array(
                    'date_from' => array(
                        'required' => false,
                        'type' => 'string',
                        'format' => 'date',
                        'description' => __('Filter by start date', 'bassmah-staff-reports'),
                    ),
                    'date_to' => array(
                        'required' => false,
                        'type' => 'string',
                        'format' => 'date',
                        'description' => __('Filter by end date', 'bassmah-staff-reports'),
                    ),
                    'limit' => array(
                        'required' => false,
                        'type' => 'integer',
                        'default' => 50,
                        'description' => __('Number of reports to return', 'bassmah-staff-reports'),
                    ),
                    'offset' => array(
                        'required' => false,
                        'type' => 'integer',
                        'default' => 0,
                        'description' => __('Number of reports to skip', 'bassmah-staff-reports'),
                    ),
                ),
            ),
        ));

        register_rest_route('bassmah/v1', '/reports/(?P<id>\d+)', array(
            array(
                'methods' => WP_REST_Server::READABLE,
                'callback' => array($this, 'get_report'),
                'permission_callback' => array($this, 'get_report_permissions_check'),
            ),
            array(
                'methods' => WP_REST_Server::EDITABLE,
                'callback' => array($this, 'update_report'),
                'permission_callback' => array($this, 'update_report_permissions_check'),
                'args' => array(
                    'status' => array(
                        'required' => false,
                        'type' => 'string',
                        'enum' => array('draft', 'submitted', 'approved', 'rejected'),
                        'description' => __('Report status', 'bassmah-staff-reports'),
                    ),
                    'tasks' => array(
                        'required' => false,
                        'type' => 'array',
                        'description' => __('Array of tasks for the report', 'bassmah-staff-reports'),
                    ),
                ),
            ),
        ));

        register_rest_route('bassmah/v1', '/reports/(?P<id>\d+)/comment', array(
            'methods' => WP_REST_Server::EDITABLE,
            'callback' => array($this, 'add_comment'),
            'permission_callback' => array($this, 'add_comment_permissions_check'),
            'args' => array(
                'id' => array(
                    'required' => true,
                    'type' => 'integer',
                    'description' => __('Report ID', 'bassmah-staff-reports'),
                ),
                'comment' => array(
                    'required' => true,
                    'type' => 'string',
                    'minLength' => 1,
                    'maxLength' => 1000,
                    'description' => __('Manager comment', 'bassmah-staff-reports'),
                    'sanitize_callback' => 'sanitize_textarea_field'
                ),
            ),
        ));

        register_rest_route('bassmah/v1', '/reports/today', array(
            array(
                'methods' => WP_REST_Server::READABLE,
                'callback' => array($this, 'get_today_report'),
                'permission_callback' => array($this, 'get_my_reports_permissions_check'),
            ),
        ));

        register_rest_route('bassmah/v1', '/reports/statistics', array(
            array(
                'methods' => WP_REST_Server::READABLE,
                'callback' => array($this, 'get_statistics'),
                'permission_callback' => array($this, 'get_reports_permissions_check'),
                'args' => array(
                    'date_from' => array(
                        'required' => false,
                        'type' => 'string',
                        'format' => 'date',
                        'description' => __('Filter by start date', 'bassmah-staff-reports'),
                    ),
                    'date_to' => array(
                        'required' => false,
                        'type' => 'string',
                        'format' => 'date',
                        'description' => __('Filter by end date', 'bassmah-staff-reports'),
                    ),
                    'user_id' => array(
                        'required' => false,
                        'type' => 'integer',
                        'description' => __('Filter by user ID', 'bassmah-staff-reports'),
                    ),
                ),
            ),
        ));
    }

    /**
     * Create a new report
     *
     * @since    1.0.0
     * @param    WP_REST_Request    $request    Full data about the request.
     * @return   WP_REST_Response|WP_Error
     */
    public function create_report($request) {
        $user_id = get_current_user_id();
        $tasks = $request->get_param('tasks');
        $report_date = $request->get_param('report_date') ?: current_time('Y-m-d');
        $status = $request->get_param('status') ?: 'submitted';

        // Validate tasks
        if (empty($tasks) || !is_array($tasks)) {
            return new WP_Error(
                'invalid_tasks',
                __('At least one task is required.', 'bassmah-staff-reports'),
                array('status' => 400)
            );
        }

        foreach ($tasks as $task) {
            if (empty($task['task_description'])) {
                return new WP_Error(
                    'invalid_task',
                    __('Task description is required for all tasks.', 'bassmah-staff-reports'),
                    array('status' => 400)
                );
            }
        }

        $report_data = array(
            'user_id' => $user_id,
            'report_date' => $report_date,
            'tasks' => $tasks,
            'status' => $status
        );

        $report_class = new Bassmah_Staff_Reports_Report();
        $result = $report_class->submit_report($report_data);

        if (is_wp_error($result)) {
            return $result;
        }

        $report = $report_class->get_report($result);
        $response_data = $this->prepare_report_for_response($report);

        return new WP_REST_Response($response_data, 201);
    }

    /**
     * Get reports
     *
     * @since    1.0.0
     * @param    WP_REST_Request    $request    Full data about the request.
     * @return   WP_REST_Response|WP_Error
     */
    public function get_reports($request) {
        $args = array(
            'user_id' => $request->get_param('user_id'),
            'status' => $request->get_param('status'),
            'date_from' => $request->get_param('date_from'),
            'date_to' => $request->get_param('date_to'),
            'limit' => $request->get_param('limit'),
            'offset' => $request->get_param('offset'),
            'orderby' => 'submission_time',
            'order' => 'DESC'
        );

        $report_class = new Bassmah_Staff_Reports_Report();
        $reports = $report_class->get_reports($args);

        $response_data = array();
        foreach ($reports as $report) {
            $response_data[] = $this->prepare_report_for_response($report);
        }

        return new WP_REST_Response($response_data, 200);
    }

    /**
     * Get current user's reports
     *
     * @since    1.0.0
     * @param    WP_REST_Request    $request    Full data about the request.
     * @return   WP_REST_Response|WP_Error
     */
    public function get_my_reports($request) {
        $args = array(
            'date_from' => $request->get_param('date_from'),
            'date_to' => $request->get_param('date_to'),
            'limit' => $request->get_param('limit'),
            'offset' => $request->get_param('offset'),
            'orderby' => 'submission_time',
            'order' => 'DESC'
        );

        $report_class = new Bassmah_Staff_Reports_Report();
        $reports = $report_class->get_my_reports($args);

        $response_data = array();
        foreach ($reports as $report) {
            $response_data[] = $this->prepare_report_for_response($report);
        }

        return new WP_REST_Response($response_data, 200);
    }

    /**
     * Get a single report
     *
     * @since    1.0.0
     * @param    WP_REST_Request    $request    Full data about the request.
     * @return   WP_REST_Response|WP_Error
     */
    public function get_report($request) {
        $report_id = $request->get_param('id');

        $report_class = new Bassmah_Staff_Reports_Report();
        $report = $report_class->get_report($report_id);

        if (!$report) {
            return new WP_Error(
                'report_not_found',
                __('Report not found.', 'bassmah-staff-reports'),
                array('status' => 404)
            );
        }

        $response_data = $this->prepare_report_for_response($report);

        return new WP_REST_Response($response_data, 200);
    }

    /**
     * Update a report
     *
     * @since    1.0.0
     * @param    WP_REST_Request    $request    Full data about the request.
     * @return   WP_REST_Response|WP_Error
     */
    public function update_report($request) {
        $report_id = $request->get_param('id');
        $status = $request->get_param('status');
        $tasks = $request->get_param('tasks');

        $update_data = array();
        if ($status) {
            $update_data['status'] = $status;
        }
        if ($tasks) {
            $update_data['tasks'] = $tasks;
        }

        $report_class = new Bassmah_Staff_Reports_Report();
        $result = $report_class->update_report($report_id, $update_data);

        if (is_wp_error($result)) {
            return $result;
        }

        $report = $report_class->get_report($report_id);
        $response_data = $this->prepare_report_for_response($report);

        return new WP_REST_Response($response_data, 200);
    }

    /**
     * Add comment to a report
     *
     * @since    1.0.0
     * @param    WP_REST_Request    $request    Full data about the request.
     * @return   WP_REST_Response|WP_Error
     */
    public function add_comment($request) {
        $report_id = $request->get_param('id');
        $comment = $request->get_param('comment');

        $report_class = new Bassmah_Staff_Reports_Report();
        $result = $report_class->add_comment($report_id, $comment);

        if (is_wp_error($result)) {
            return $result;
        }

        $report = $report_class->get_report($report_id);
        $response_data = $this->prepare_report_for_response($report);

        return new WP_REST_Response($response_data, 200);
    }

    /**
     * Get today's report for current user
     *
     * @since    1.0.0
     * @param    WP_REST_Request    $request    Full data about the request.
     * @return   WP_REST_Response|WP_Error
     */
    public function get_today_report($request) {
        $report_class = new Bassmah_Staff_Reports_Report();
        $report = $report_class->get_today_report();

        if (!$report) {
            return new WP_REST_Response(null, 200);
        }

        $response_data = $this->prepare_report_for_response($report);

        return new WP_REST_Response($response_data, 200);
    }

    /**
     * Get report statistics
     *
     * @since    1.0.0
     * @param    WP_REST_Request    $request    Full data about the request.
     * @return   WP_REST_Response|WP_Error
     */
    public function get_statistics($request) {
        $args = array(
            'date_from' => $request->get_param('date_from') ?: date('Y-m-01'),
            'date_to' => $request->get_param('date_to') ?: date('Y-m-t'),
            'user_id' => $request->get_param('user_id')
        );

        $report_class = new Bassmah_Staff_Reports_Report();
        $statistics = $report_class->get_statistics($args);

        return new WP_REST_Response($statistics, 200);
    }

    /**
     * Check if a given request has permission to create reports
     *
     * @since    1.0.0
     * @param    WP_REST_Request    $request    Full data about the request.
     * @return   WP_Error|bool
     */
    public function create_report_permissions_check($request) {
        $login_check = Bassmah_Staff_Reports_Roles::require_login();
        if (is_wp_error($login_check)) {
            return $login_check;
        }

        return current_user_can('bassmah_submit_reports');
    }

    /**
     * Check if a given request has permission to get reports
     *
     * @since    1.0.0
     * @param    WP_REST_Request    $request    Full data about the request.
     * @return   WP_Error|bool
     */
    public function get_reports_permissions_check($request) {
        $login_check = Bassmah_Staff_Reports_Roles::require_login();
        if (is_wp_error($login_check)) {
            return $login_check;
        }

        return current_user_can('bassmah_view_all_reports');
    }

    /**
     * Check if a given request has permission to get own reports
     *
     * @since    1.0.0
     * @param    WP_REST_Request    $request    Full data about the request.
     * @return   WP_Error|bool
     */
    public function get_my_reports_permissions_check($request) {
        $login_check = Bassmah_Staff_Reports_Roles::require_login();
        if (is_wp_error($login_check)) {
            return $login_check;
        }

        return current_user_can('bassmah_view_own_reports');
    }

    /**
     * Check if a given request has permission to get a specific report
     *
     * @since    1.0.0
     * @param    WP_REST_Request    $request    Full data about the request.
     * @return   WP_Error|bool
     */
    public function get_report_permissions_check($request) {
        $login_check = Bassmah_Staff_Reports_Roles::require_login();
        if (is_wp_error($login_check)) {
            return $login_check;
        }

        $report_id = $request->get_param('id');
        $report_class = new Bassmah_Staff_Reports_Report();
        $report = $report_class->get_report($report_id);

        if (!$report) {
            return new WP_Error(
                'report_not_found',
                __('Report not found.', 'bassmah-staff-reports'),
                array('status' => 404)
            );
        }

        return Bassmah_Staff_Reports_Roles::can_view_user_reports($report->user_id);
    }

    /**
     * Check if a given request has permission to update reports
     *
     * @since    1.0.0
     * @param    WP_REST_Request    $request    Full data about the request.
     * @return   WP_Error|bool
     */
    public function update_report_permissions_check($request) {
        $login_check = Bassmah_Staff_Reports_Roles::require_login();
        if (is_wp_error($login_check)) {
            return $login_check;
        }

        $report_id = $request->get_param('id');
        $report_class = new Bassmah_Staff_Reports_Report();
        $report = $report_class->get_report($report_id);

        if (!$report) {
            return new WP_Error(
                'report_not_found',
                __('Report not found.', 'bassmah-staff-reports'),
                array('status' => 404)
            );
        }

        // Users can update their own reports, managers can update any report
        if ($report->user_id === get_current_user_id()) {
            return current_user_can('bassmah_view_own_reports');
        }

        return current_user_can('bassmah_view_all_reports');
    }

    /**
     * Check if a given request has permission to add comments
     *
     * @since    1.0.0
     * @param    WP_REST_Request    $request    Full data about the request.
     * @return   WP_Error|bool
     */
    public function add_comment_permissions_check($request) {
        $login_check = Bassmah_Staff_Reports_Roles::require_login();
        if (is_wp_error($login_check)) {
            return $login_check;
        }

        return Bassmah_Staff_Reports_Roles::can_comment_reports();
    }

    /**
     * Prepare report data for API response
     *
     * @since    1.0.0
     * @param    object    $report    Report object.
     * @return   array
     */
    private function prepare_report_for_response($report) {
        $user = get_userdata($report->user_id);
        
        return array(
            'id' => $report->id,
            'user_id' => $report->user_id,
            'user_name' => $user ? $user->display_name : 'Unknown',
            'user_email' => $user ? $user->user_email : '',
            'user_role' => Bassmah_Staff_Reports_Roles::get_user_role_display($report->user_id),
            'report_date' => $report->report_date,
            'submission_time' => $report->submission_time,
            'status' => $report->status,
            'tasks' => json_decode($report->tasks_json, true),
            'manager_comment' => $report->manager_comment,
            'ip_address' => $report->ip_address,
            'created_at' => $report->created_at,
            'updated_at' => $report->updated_at,
        );
    }
}
}
}
