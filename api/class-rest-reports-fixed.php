<?php
/**
 * REST API endpoints for reports.
 *
 * @since      1.0.0
 * @package    Bassmah_Staff_Reports
 * @author     Tumit <tumit@bassmah.ca>
 */
class Bassmah_Staff_Reports_REST_Reports {

    /**
     * Register REST API routes
     *
     * @since    1.0.0
     */
    public function register_routes() {
        register_rest_route('bassmah/v1', '/reports', array(
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => array($this, 'create_report'),
            'permission_callback' => array($this, 'create_report_permissions_check'),
            'args' => array(
                'tasks' => array(
                    'required' => true,
                    'type' => 'array',
                    'description' => __('Array of tasks for report', 'bassmah-staff-reports'),
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
        ));

        register_rest_route('bassmah/v1', '/reports', array(
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
        ));

        register_rest_route('bassmah/v1', '/reports/mine', array(
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
                'status' => array(
                    'required' => false,
                    'type' => 'string',
                    'enum' => array('draft', 'submitted', 'approved', 'rejected'),
                    'description' => __('Filter by status', 'bassmah-staff-reports'),
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
        ));

        register_rest_route('bassmah/v1', '/reports/(?P<id>\d+)', array(
            'methods' => WP_REST_Server::READABLE,
            'callback' => array($this, 'get_report'),
            'permission_callback' => array($this, 'get_report_permissions_check'),
            'args' => array(
                'id' => array(
                    'required' => true,
                    'type' => 'integer',
                    'description' => __('Report ID', 'bassmah-staff-reports'),
                ),
            ),
        ));

        register_rest_route('bassmah/v1', '/reports/(?P<id>\d+)', array(
            'methods' => WP_REST_Server::EDITABLE,
            'callback' => array($this, 'update_report'),
            'permission_callback' => array($this, 'update_report_permissions_check'),
            'args' => array(
                'id' => array(
                    'required' => true,
                    'type' => 'integer',
                    'description' => __('Report ID', 'bassmah-staff-reports'),
                ),
                'status' => array(
                    'required' => false,
                    'type' => 'string',
                    'enum' => array('draft', 'submitted', 'approved', 'rejected'),
                    'description' => __('Report status', 'bassmah-staff-reports'),
                ),
                'tasks' => array(
                    'required' => false,
                    'type' => 'array',
                    'description' => __('Array of tasks', 'bassmah-staff-reports'),
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

        register_rest_route('bassmah/v1', '/reports/(?P<id>\d+)', array(
            'methods' => WP_REST_Server::DELETABLE,
            'callback' => array($this, 'delete_report'),
            'permission_callback' => array($this, 'delete_report_permissions_check'),
            'args' => array(
                'id' => array(
                    'required' => true,
                    'type' => 'integer',
                    'description' => __('Report ID', 'bassmah-staff-reports'),
                ),
            ),
        ));

        register_rest_route('bassmah/v1', '/reports/today', array(
            'methods' => WP_REST_Server::READABLE,
            'callback' => array($this, 'get_today_reports'),
            'permission_callback' => array($this, 'get_reports_permissions_check'),
        ));

        register_rest_route('bassmah/v1', '/reports/statistics', array(
            'methods' => WP_REST_Server::READABLE,
            'callback' => array($this, 'get_statistics'),
            'permission_callback' => array($this, 'get_reports_permissions_check'),
            'args' => array(
                'date_from' => array(
                    'required' => false,
                    'type' => 'string',
                    'format' => 'date',
                    'description' => __('Start date for statistics', 'bassmah-staff-reports'),
                ),
                'date_to' => array(
                    'required' => false,
                    'type' => 'string',
                    'format' => 'date',
                    'description' => __('End date for statistics', 'bassmah-staff-reports'),
                ),
                'user_id' => array(
                    'required' => false,
                    'type' => 'integer',
                    'description' => __('Filter by user ID', 'bassmah-staff-reports'),
                ),
            ),
        ));
    }

    /**
     * Create a new report
     *
     * @since    1.0.0
     * @param    WP_REST_Request    $request    Full data about the request
     * @return   WP_REST_Response|WP_Error
     */
    public function create_report($request) {
        $user_id = get_current_user_id();
        $data = $request->get_params();

        // Validate nonce
        if (!wp_verify_nonce($request->get_param('_wpnonce'), 'bassmah_create_report')) {
            return new WP_Error(
                'invalid_nonce',
                __('Security check failed', 'bassmah-staff-reports'),
                array('status' => 403)
            );
        }

        // Validate user permissions
        if (!user_can($user_id, 'bassmah_submit_reports')) {
            return new WP_Error(
                'insufficient_permissions',
                __('You do not have permission to submit reports', 'bassmah-staff-reports'),
                array('status' => 403)
            );
        }

        // Validate required fields
        if (empty($data['tasks']) || !is_array($data['tasks'])) {
            return new WP_Error(
                'missing_tasks',
                __('Tasks are required', 'bassmah-staff-reports'),
                array('status' => 400)
            );
        }

        // Check for duplicate report
        $report_date = $data['report_date'] ?? current_time('Y-m-d');
        $existing_report = $this->get_report_by_date($user_id, $report_date);
        if ($existing_report) {
            return new WP_Error(
                'duplicate_report',
                __('A report has already been submitted for this date', 'bassmah-staff-reports'),
                array('status' => 409)
            );
        }

        // Create report
        $report_id = $this->create_report_in_database($user_id, $data, $report_date);
        
        if (is_wp_error($report_id)) {
            return $report_id;
        }

        return new WP_REST_Response(
            array(
                'success' => true,
                'data' => array(
                    'report_id' => $report_id,
                    'message' => __('Report created successfully', 'bassmah-staff-reports')
                )
            ),
            201
        );
    }

    /**
     * Get reports with filtering
     *
     * @since    1.0.0
     * @param    WP_REST_Request    $request    Full data about the request
     * @return   WP_REST_Response|WP_Error
     */
    public function get_reports($request) {
        $params = $request->get_params();

        // Validate user permissions
        if (!user_can(get_current_user_id(), 'bassmah_view_all_reports')) {
            return new WP_Error(
                'insufficient_permissions',
                __('You do not have permission to view all reports', 'bassmah-staff-reports'),
                array('status' => 403)
            );
        }

        $reports = $this->get_reports_from_database($params);
        
        return new WP_REST_Response(
            array(
                'success' => true,
                'data' => $reports
            ),
            200
        );
    }

    /**
     * Get current user's reports
     *
     * @since    1.0.0
     * @param    WP_REST_Request    $request    Full data about the request
     * @return   WP_REST_Response|WP_Error
     */
    public function get_my_reports($request) {
        $user_id = get_current_user_id();
        $params = $request->get_params();

        // Validate user permissions
        if (!user_can($user_id, 'bassmah_view_own_reports')) {
            return new WP_Error(
                'insufficient_permissions',
                __('You do not have permission to view your reports', 'bassmah-staff-reports'),
                array('status' => 403)
            );
        }

        $params['user_id'] = $user_id;
        $reports = $this->get_reports_from_database($params);
        
        return new WP_REST_Response(
            array(
                'success' => true,
                'data' => $reports
            ),
            200
        );
    }

    /**
     * Get a specific report
     *
     * @since    1.0.0
     * @param    WP_REST_Request    $request    Full data about the request
     * @return   WP_REST_Response|WP_Error
     */
    public function get_report($request) {
        $report_id = $request->get_param('id');
        $user_id = get_current_user_id();

        // Validate nonce
        if (!wp_verify_nonce($request->get_param('_wpnonce'), 'bassmah_get_report')) {
            return new WP_Error(
                'invalid_nonce',
                __('Security check failed', 'bassmah-staff-reports'),
                array('status' => 403)
            );
        }

        $report = $this->get_report_from_database($report_id);
        if (!$report) {
            return new WP_Error(
                'report_not_found',
                __('Report not found', 'bassmah-staff-reports'),
                array('status' => 404)
            );
        }

        // Check permissions
        if ($report->user_id !== $user_id && !user_can($user_id, 'bassmah_view_all_reports')) {
            return new WP_Error(
                'insufficient_permissions',
                __('You do not have permission to view this report', 'bassmah-staff-reports'),
                array('status' => 403)
            );
        }

        return new WP_REST_Response(
            array(
                'success' => true,
                'data' => $report
            ),
            200
        );
    }

    /**
     * Update a specific report
     *
     * @since    1.0.0
     * @param    WP_REST_Request    $request    Full data about the request
     * @return   WP_REST_Response|WP_Error
     */
    public function update_report($request) {
        $report_id = $request->get_param('id');
        $user_id = get_current_user_id();
        $data = $request->get_params();

        // Validate nonce
        if (!wp_verify_nonce($request->get_param('_wpnonce'), 'bassmah_update_report')) {
            return new WP_Error(
                'invalid_nonce',
                __('Security check failed', 'bassmah-staff-reports'),
                array('status' => 403)
            );
        }

        $report = $this->get_report_from_database($report_id);
        if (!$report) {
            return new WP_Error(
                'report_not_found',
                __('Report not found', 'bassmah-staff-reports'),
                array('status' => 404)
            );
        }

        // Check permissions
        if ($report->user_id !== $user_id && !user_can($user_id, 'bassmah_view_all_reports')) {
            return new WP_Error(
                'insufficient_permissions',
                __('You do not have permission to update this report', 'bassmah-staff-reports'),
                array('status' => 403)
            );
        }

        $result = $this->update_report_in_database($report_id, $data);
        
        if (is_wp_error($result)) {
            return $result;
        }

        return new WP_REST_Response(
            array(
                'success' => true,
                'data' => array(
                    'message' => __('Report updated successfully', 'bassmah-staff-reports')
                )
            ),
            200
        );
    }

    /**
     * Add comment to a report
     *
     * @since    1.0.0
     * @param    WP_REST_Request    $request    Full data about the request
     * @return   WP_REST_Response|WP_Error
     */
    public function add_comment($request) {
        $report_id = $request->get_param('id');
        $comment = $request->get_param('comment');
        $user_id = get_current_user_id();

        // Validate nonce
        if (!wp_verify_nonce($request->get_param('_wpnonce'), 'bassmah_add_comment')) {
            return new WP_Error(
                'invalid_nonce',
                __('Security check failed', 'bassmah-staff-reports'),
                array('status' => 403)
            );
        }

        // Validate user permissions
        if (!user_can($user_id, 'bassmah_comment_reports')) {
            return new WP_Error(
                'insufficient_permissions',
                __('You do not have permission to comment on reports', 'bassmah-staff-reports'),
                array('status' => 403)
            );
        }

        $report = $this->get_report_from_database($report_id);
        if (!$report) {
            return new WP_Error(
                'report_not_found',
                __('Report not found', 'bassmah-staff-reports'),
                array('status' => 404)
            );
        }

        $result = $this->add_comment_to_database($report_id, $comment, $user_id);
        
        if (is_wp_error($result)) {
            return $result;
        }

        return new WP_REST_Response(
            array(
                'success' => true,
                'data' => array(
                    'message' => __('Comment added successfully', 'bassmah-staff-reports')
                )
            ),
            200
        );
    }

    /**
     * Delete a report
     *
     * @since    1.0.0
     * @param    WP_REST_Request    $request    Full data about the request
     * @return   WP_REST_Response|WP_Error
     */
    public function delete_report($request) {
        $report_id = $request->get_param('id');
        $user_id = get_current_user_id();

        // Validate nonce
        if (!wp_verify_nonce($request->get_param('_wpnonce'), 'bassmah_delete_report')) {
            return new WP_Error(
                'invalid_nonce',
                __('Security check failed', 'bassmah-staff-reports'),
                array('status' => 403)
            );
        }

        $report = $this->get_report_from_database($report_id);
        if (!$report) {
            return new WP_Error(
                'report_not_found',
                __('Report not found', 'bassmah-staff-reports'),
                array('status' => 404)
            );
        }

        // Check permissions
        if ($report->user_id !== $user_id && !user_can($user_id, 'bassmah_view_all_reports')) {
            return new WP_Error(
                'insufficient_permissions',
                __('You do not have permission to delete this report', 'bassmah-staff-reports'),
                array('status' => 403)
            );
        }

        $result = $this->delete_report_from_database($report_id);
        
        if (is_wp_error($result)) {
            return $result;
        }

        return new WP_REST_Response(
            array(
                'success' => true,
                'data' => array(
                    'message' => __('Report deleted successfully', 'bassmah-staff-reports')
                )
            ),
            200
        );
    }

    /**
     * Get today's reports
     *
     * @since    1.0.0
     * @param    WP_REST_Request    $request    Full data about the request
     * @return   WP_REST_Response|WP_Error
     */
    public function get_today_reports($request) {
        $user_id = get_current_user_id();

        // Validate user permissions
        if (!user_can($user_id, 'bassmah_view_all_reports')) {
            return new WP_Error(
                'insufficient_permissions',
                __('You do not have permission to view all reports', 'bassmah-staff-reports'),
                array('status' => 403)
            );
        }

        $today = current_time('Y-m-d');
        $reports = $this->get_reports_from_database(array('date_from' => $today, 'date_to' => $today));
        
        return new WP_REST_Response(
            array(
                'success' => true,
                'data' => $reports
            ),
            200
        );
    }

    /**
     * Get report statistics
     *
     * @since    1.0.0
     * @param    WP_REST_Request    $request    Full data about the request
     * @return   WP_REST_Response|WP_Error
     */
    public function get_statistics($request) {
        $user_id = get_current_user_id();
        $params = $request->get_params();

        // Validate user permissions
        if (!user_can($user_id, 'bassmah_view_all_reports')) {
            return new WP_Error(
                'insufficient_permissions',
                __('You do not have permission to view report statistics', 'bassmah-staff-reports'),
                array('status' => 403)
            );
        }

        $statistics = $this->get_statistics_from_database($params);
        
        return new WP_REST_Response(
            array(
                'success' => true,
                'data' => $statistics
            ),
            200
        );
    }

    // Permission check methods
    public function create_report_permissions_check() {
        return is_user_logged_in() && user_can(get_current_user_id(), 'bassmah_submit_reports');
    }

    public function get_reports_permissions_check() {
        return is_user_logged_in() && user_can(get_current_user_id(), 'bassmah_view_all_reports');
    }

    public function get_my_reports_permissions_check() {
        return is_user_logged_in() && user_can(get_current_user_id(), 'bassmah_view_own_reports');
    }

    public function get_report_permissions_check() {
        return is_user_logged_in();
    }

    public function update_report_permissions_check() {
        return is_user_logged_in();
    }

    public function add_comment_permissions_check() {
        return is_user_logged_in() && user_can(get_current_user_id(), 'bassmah_comment_reports');
    }

    public function delete_report_permissions_check() {
        return is_user_logged_in();
    }

    // Database helper methods (simplified versions)
    private function create_report_in_database($user_id, $data, $report_date) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'bassmah_staff_reports';
        $tasks_json = wp_json_encode($data['tasks']);
        
        $result = $wpdb->insert(
            $table_name,
            array(
                'user_id' => $user_id,
                'report_date' => $report_date,
                'submission_time' => current_time('mysql'),
                'status' => $data['status'] ?? 'submitted',
                'tasks_json' => $tasks_json,
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
                'created_at' => current_time('mysql'),
                'updated_at' => current_time('mysql')
            ),
            array('%s', '%s', '%s', '%s', '%s', '%s', '%s')
        );
        
        if ($result === false) {
            return new WP_Error(
                'database_error',
                __('Failed to create report', 'bassmah-staff-reports'),
                array('status' => 500)
            );
        }
        
        return $wpdb->insert_id;
    }

    private function get_report_from_database($report_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'bassmah_staff_reports';
        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$table_name} WHERE id = %d",
                $report_id
            )
        );
    }

    private function get_report_by_date($user_id, $report_date) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'bassmah_staff_reports';
        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$table_name} WHERE user_id = %d AND report_date = %s",
                $user_id,
                $report_date
            )
        );
    }

    private function get_reports_from_database($params) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'bassmah_staff_reports';
        $where_conditions = array();
        $where_values = array();
        
        if (!empty($params['user_id'])) {
            $where_conditions[] = "user_id = %d";
            $where_values[] = $params['user_id'];
        }
        
        if (!empty($params['status'])) {
            $where_conditions[] = "status = %s";
            $where_values[] = $params['status'];
        }
        
        if (!empty($params['date_from'])) {
            $where_conditions[] = "report_date >= %s";
            $where_values[] = $params['date_from'];
        }
        
        if (!empty($params['date_to'])) {
            $where_conditions[] = "report_date <= %s";
            $where_values[] = $params['date_to'];
        }
        
        $where_clause = '';
        if (!empty($where_conditions)) {
            $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
        }
        
        $limit = '';
        if (!empty($params['limit'])) {
            $limit = 'LIMIT ' . intval($params['limit']);
        }
        
        $offset = '';
        if (!empty($params['offset'])) {
            $offset = 'OFFSET ' . intval($params['offset']);
        }
        
        $query = "SELECT * FROM {$table_name} {$where_clause} ORDER BY submission_time DESC {$limit} {$offset}";
        
        if (!empty($where_values)) {
            $query = $wpdb->prepare($query, $where_values);
        }
        
        return $wpdb->get_results($query);
    }

    private function update_report_in_database($report_id, $data) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'bassmah_staff_reports';
        $update_data = array('updated_at' => current_time('mysql'));
        
        if (isset($data['status'])) {
            $update_data['status'] = $data['status'];
        }
        
        if (isset($data['tasks'])) {
            $update_data['tasks_json'] = wp_json_encode($data['tasks']);
        }
        
        $result = $wpdb->update(
            $table_name,
            $update_data,
            array('id' => $report_id),
            array('%s', '%s', '%s')
        );
        
        if ($result === false) {
            return new WP_Error(
                'database_error',
                __('Failed to update report', 'bassmah-staff-reports'),
                array('status' => 500)
            );
        }
        
        return true;
    }

    private function add_comment_to_database($report_id, $comment, $user_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'bassmah_staff_reports';
        
        $result = $wpdb->update(
            $table_name,
            array(
                'manager_comment' => $comment,
                'manager_id' => $user_id,
                'updated_at' => current_time('mysql')
            ),
            array('id' => $report_id),
            array('%s', '%d', '%s', '%s')
        );
        
        if ($result === false) {
            return new WP_Error(
                'database_error',
                __('Failed to add comment', 'bassmah-staff-reports'),
                array('status' => 500)
            );
        }
        
        return true;
    }

    private function delete_report_from_database($report_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'bassmah_staff_reports';
        
        $result = $wpdb->delete(
            $table_name,
            array('id' => $report_id),
            array('%d')
        );
        
        if ($result === false) {
            return new WP_Error(
                'database_error',
                __('Failed to delete report', 'bassmah-staff-reports'),
                array('status' => 500)
            );
        }
        
        return true;
    }

    private function get_statistics_from_database($params) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'bassmah_staff_reports';
        $where_conditions = array();
        $where_values = array();
        
        if (!empty($params['date_from'])) {
            $where_conditions[] = "report_date >= %s";
            $where_values[] = $params['date_from'];
        }
        
        if (!empty($params['date_to'])) {
            $where_conditions[] = "report_date <= %s";
            $where_values[] = $params['date_to'];
        }
        
        if (!empty($params['user_id'])) {
            $where_conditions[] = "user_id = %d";
            $where_values[] = $params['user_id'];
        }
        
        $where_clause = '';
        if (!empty($where_conditions)) {
            $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
        }
        
        $query = "
            SELECT 
                COUNT(*) as total_reports,
                COUNT(DISTINCT user_id) as unique_users,
                COUNT(CASE WHEN status = 'submitted' THEN 1 END) as submitted_reports,
                COUNT(CASE WHEN status = 'approved' THEN 1 END) as approved_reports,
                COUNT(CASE WHEN status = 'rejected' THEN 1 END) as rejected_reports
            FROM {$table_name} {$where_clause}
        ";
        
        if (!empty($where_values)) {
            $query = $wpdb->prepare($query, $where_values);
        }
        
        return $wpdb->get_row($query);
    }
}
