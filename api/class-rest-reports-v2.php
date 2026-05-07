<?php
/**
 * REST API Reports Controller v2 - Enhanced Reports API
 *
 * Comprehensive REST API endpoints for report management
 * with proper authentication, validation, and error handling.
 *
 * @since      1.0.0
 * @package    Bassmah_Staff_Reports
 * @author     Tumit <tumit@bassmah.ca>
 */
class Bassmah_Staff_Reports_REST_Reports_V2 {

    /**
     * Service container instance
     *
     * @since    1.0.0
     * @access   private
     * @var      Bassmah_Staff_Reports_Service_Container    $container    Service container
     */
    private $container;

    /**
     * Report service instance
     *
     * @since    1.0.0
     * @access   private
     * @var      Bassmah_Staff_Reports_Report_Service    $report_service    Report service
     */
    private $report_service;

    /**
     * Security manager instance
     *
     * @since    1.0.0
     * @access   private
     * @var      Bassmah_Staff_Reports_Security_Manager    $security    Security manager
     */
    private $security;

    /**
     * Constructor
     *
     * @since    1.0.0
     */
    public function __construct() {
        $this->container = Bassmah_Staff_Reports_Service_Container::get_instance();
        $this->report_service = $this->container->get('report');
        $this->security = $this->container->get('security');
    }

    /**
     * Register REST API routes
     *
     * @since    1.0.0
     */
    public function register_routes() {
        // POST /reports - Create new report
        register_rest_route('bassmah/v2', '/reports', array(
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => array($this, 'create_report'),
            'permission_callback' => array($this, 'create_report_permissions_check'),
            'args' => array(
                'tasks' => array(
                    'required' => true,
                    'type' => 'array',
                    'description' => __('Array of tasks for the report', 'bassmah-staff-reports'),
                    'validate_callback' => array($this, 'validate_tasks_array')
                ),
                'report_date' => array(
                    'required' => false,
                    'type' => 'string',
                    'format' => 'date',
                    'description' => __('Report date in Y-m-d format', 'bassmah-staff-reports'),
                    'validate_callback' => array($this, 'validate_report_date')
                ),
                'status' => array(
                    'required' => false,
                    'type' => 'string',
                    'enum' => array('draft', 'submitted', 'approved', 'rejected'),
                    'description' => __('Report status', 'bassmah-staff-reports'),
                    'validate_callback' => array($this, 'validate_status')
                )
            )
        ));

        // GET /reports - Get reports with filtering
        register_rest_route('bassmah/v2', '/reports', array(
            'methods' => WP_REST_Server::READABLE,
            'callback' => array($this, 'get_reports'),
            'permission_callback' => array($this, 'get_reports_permissions_check'),
            'args' => array(
                'user_id' => array(
                    'required' => false,
                    'type' => 'integer',
                    'description' => __('Filter by user ID', 'bassmah-staff-reports'),
                    'validate_callback' => array($this, 'validate_user_id')
                ),
                'status' => array(
                    'required' => false,
                    'type' => 'string',
                    'enum' => array('draft', 'submitted', 'approved', 'rejected'),
                    'description' => __('Filter by status', 'bassmah-staff-reports'),
                    'validate_callback' => array($this, 'validate_status')
                ),
                'date_from' => array(
                    'required' => false,
                    'type' => 'string',
                    'format' => 'date',
                    'description' => __('Filter by start date', 'bassmah-staff-reports'),
                    'validate_callback' => array($this, 'validate_date')
                ),
                'date_to' => array(
                    'required' => false,
                    'type' => 'string',
                    'format' => 'date',
                    'description' => __('Filter by end date', 'bassmah-staff-reports'),
                    'validate_callback' => array($this, 'validate_date')
                ),
                'limit' => array(
                    'required' => false,
                    'type' => 'integer',
                    'minimum' => 1,
                    'maximum' => 100,
                    'description' => __('Number of reports to return', 'bassmah-staff-reports')
                ),
                'offset' => array(
                    'required' => false,
                    'type' => 'integer',
                    'minimum' => 0,
                    'description' => __('Number of reports to skip', 'bassmah-staff-reports')
                ),
                'orderby' => array(
                    'required' => false,
                    'type' => 'string',
                    'enum' => array('id', 'report_date', 'submission_time', 'updated_at'),
                    'description' => __('Sort field', 'bassmah-staff-reports')
                ),
                'order' => array(
                    'required' => false,
                    'type' => 'string',
                    'enum' => array('asc', 'desc'),
                    'description' => __('Sort order', 'bassmah-staff-reports')
                )
            )
        ));

        // GET /reports/mine - Get current user's reports
        register_rest_route('bassmah/v2', '/reports/mine', array(
            'methods' => WP_REST_Server::READABLE,
            'callback' => array($this, 'get_my_reports'),
            'permission_callback' => array($this, 'get_my_reports_permissions_check'),
            'args' => array(
                'date_from' => array(
                    'required' => false,
                    'type' => 'string',
                    'format' => 'date',
                    'description' => __('Filter by start date', 'bassmah-staff-reports'),
                    'validate_callback' => array($this, 'validate_date')
                ),
                'date_to' => array(
                    'required' => false,
                    'type' => 'string',
                    'format' => 'date',
                    'description' => __('Filter by end date', 'bassmah-staff-reports'),
                    'validate_callback' => array($this, 'validate_date')
                ),
                'status' => array(
                    'required' => false,
                    'type' => 'string',
                    'enum' => array('draft', 'submitted', 'approved', 'rejected'),
                    'description' => __('Filter by status', 'bassmah-staff-reports'),
                    'validate_callback' => array($this, 'validate_status')
                ),
                'limit' => array(
                    'required' => false,
                    'type' => 'integer',
                    'minimum' => 1,
                    'maximum' => 100,
                    'description' => __('Number of reports to return', 'bassmah-staff-reports')
                ),
                'offset' => array(
                    'required' => false,
                    'type' => 'integer',
                    'minimum' => 0,
                    'description' => __('Number of reports to skip', 'bassmah-staff-reports')
                )
            )
        ));

        // GET /reports/{id} - Get specific report
        register_rest_route('bassmah/v2', '/reports/(?P<id>\d+)', array(
            'methods' => WP_REST_Server::READABLE,
            'callback' => array($this, 'get_report'),
            'permission_callback' => array($this, 'get_report_permissions_check'),
            'args' => array(
                'id' => array(
                    'required' => true,
                    'type' => 'integer',
                    'description' => __('Report ID', 'bassmah-staff-reports'),
                    'validate_callback' => array($this, 'validate_report_id')
                )
            )
        ));

        // PUT /reports/{id} - Update specific report
        register_rest_route('bassmah/v2', '/reports/(?P<id>\d+)', array(
            'methods' => WP_REST_Server::EDITABLE,
            'callback' => array($this, 'update_report'),
            'permission_callback' => array($this, 'update_report_permissions_check'),
            'args' => array(
                'id' => array(
                    'required' => true,
                    'type' => 'integer',
                    'description' => __('Report ID', 'bassmah-staff-reports'),
                    'validate_callback' => array($this, 'validate_report_id')
                ),
                'status' => array(
                    'required' => false,
                    'type' => 'string',
                    'enum' => array('draft', 'submitted', 'approved', 'rejected'),
                    'description' => __('Report status', 'bassmah-staff-reports'),
                    'validate_callback' => array($this, 'validate_status')
                ),
                'tasks' => array(
                    'required' => false,
                    'type' => 'array',
                    'description' => __('Array of tasks', 'bassmah-staff-reports'),
                    'validate_callback' => array($this, 'validate_tasks_array')
                )
            )
        ));

        // PUT /reports/{id}/comment - Add comment to report
        register_rest_route('bassmah/v2', '/reports/(?P<id>\d+)/comment', array(
            'methods' => WP_REST_Server::EDITABLE,
            'callback' => array($this, 'add_comment'),
            'permission_callback' => array($this, 'add_comment_permissions_check'),
            'args' => array(
                'id' => array(
                    'required' => true,
                    'type' => 'integer',
                    'description' => __('Report ID', 'bassmah-staff-reports'),
                    'validate_callback' => array($this, 'validate_report_id')
                ),
                'comment' => array(
                    'required' => true,
                    'type' => 'string',
                    'minLength' => 1,
                    'maxLength' => 1000,
                    'description' => __('Manager comment', 'bassmah-staff-reports'),
                    'sanitize_callback' => 'sanitize_textarea_field'
                )
            )
        ));

        // DELETE /reports/{id} - Delete report
        register_rest_route('bassmah/v2', '/reports/(?P<id>\d+)', array(
            'methods' => WP_REST_Server::DELETABLE,
            'callback' => array($this, 'delete_report'),
            'permission_callback' => array($this, 'delete_report_permissions_check'),
            'args' => array(
                'id' => array(
                    'required' => true,
                    'type' => 'integer',
                    'description' => __('Report ID', 'bassmah-staff-reports'),
                    'validate_callback' => array($this, 'validate_report_id')
                )
            )
        ));

        // GET /reports/today - Get today's reports
        register_rest_route('bassmah/v2', '/reports/today', array(
            'methods' => WP_REST_Server::READABLE,
            'callback' => array($this, 'get_today_reports'),
            'permission_callback' => array($this, 'get_reports_permissions_check')
        ));

        // GET /reports/statistics - Get report statistics
        register_rest_route('bassmah/v2', '/reports/statistics', array(
            'methods' => WP_REST_Server::READABLE,
            'callback' => array($this, 'get_statistics'),
            'permission_callback' => array($this, 'get_reports_permissions_check'),
            'args' => array(
                'date_from' => array(
                    'required' => false,
                    'type' => 'string',
                    'format' => 'date',
                    'description' => __('Start date for statistics', 'bassmah-staff-reports'),
                    'validate_callback' => array($this, 'validate_date')
                ),
                'date_to' => array(
                    'required' => false,
                    'type' => 'string',
                    'format' => 'date',
                    'description' => __('End date for statistics', 'bassmah-staff-reports'),
                    'validate_callback' => array($this, 'validate_date')
                ),
                'user_id' => array(
                    'required' => false,
                    'type' => 'integer',
                    'description' => __('Filter by user ID', 'bassmah-staff-reports'),
                    'validate_callback' => array($this, 'validate_user_id')
                )
            )
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

        try {
            $report_id = $this->report_service->create_report(array(
                'user_id' => $user_id,
                'report_date' => $data['report_date'] ?? current_time('Y-m-d'),
                'tasks' => $data['tasks'],
                'status' => $data['status'] ?? 'submitted'
            ));

            if (is_wp_error($report_id)) {
                return $this->handle_error($report_id);
            }

            $report = $this->report_service->get_report($report_id);
            
            return new WP_REST_Response(
                array(
                    'success' => true,
                    'data' => $this->prepare_report_for_response($report),
                    'message' => __('Report created successfully', 'bassmah-staff-reports')
                ),
                201
            );

        } catch (Exception $e) {
            return new WP_REST_Response(
                array(
                    'success' => false,
                    'error' => array(
                        'code' => 'creation_failed',
                        'message' => $e->getMessage()
                    )
                ),
                500
            );
        }
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
        $args = array();

        if (isset($params['user_id'])) {
            $args['user_id'] = $params['user_id'];
        }
        if (isset($params['status'])) {
            $args['status'] = $params['status'];
        }
        if (isset($params['date_from'])) {
            $args['date_from'] = $params['date_from'];
        }
        if (isset($params['date_to'])) {
            $args['date_to'] = $params['date_to'];
        }
        if (isset($params['limit'])) {
            $args['limit'] = $params['limit'];
        }
        if (isset($params['offset'])) {
            $args['offset'] = $params['offset'];
        }
        if (isset($params['orderby'])) {
            $args['orderby'] = $params['orderby'];
        }
        if (isset($params['order'])) {
            $args['order'] = $params['order'];
        }

        try {
            $reports = $this->report_service->get_reports($args);
            
            return new WP_REST_Response(
                array(
                    'success' => true,
                    'data' => array_map(array($this, 'prepare_report_for_response'), $reports),
                    'meta' => array(
                        'total' => count($reports),
                        'params' => $args
                    )
                ),
                200
            );

        } catch (Exception $e) {
            return $this->handle_error($e);
        }
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
        $args = array('user_id' => $user_id);

        if (isset($params['date_from'])) {
            $args['date_from'] = $params['date_from'];
        }
        if (isset($params['date_to'])) {
            $args['date_to'] = $params['date_to'];
        }
        if (isset($params['limit'])) {
            $args['limit'] = $params['limit'];
        }
        if (isset($params['offset'])) {
            $args['offset'] = $params['offset'];
        }

        try {
            $reports = $this->report_service->get_user_reports($user_id, $args);
            
            return new WP_REST_Response(
                array(
                    'success' => true,
                    'data' => array_map(array($this, 'prepare_report_for_response'), $reports),
                    'meta' => array(
                        'total' => count($reports),
                        'params' => $args
                    )
                ),
                200
            );

        } catch (Exception $e) {
            return $this->handle_error($e);
        }
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

        try {
            $report = $this->report_service->get_report($report_id);
            
            return new WP_REST_Response(
                array(
                    'success' => true,
                    'data' => $this->prepare_report_for_response($report)
                ),
                200
            );

        } catch (Exception $e) {
            return $this->handle_error($e);
        }
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
        $data = $request->get_params();

        try {
            $result = $this->report_service->update_report($report_id, $data);
            
            if (is_wp_error($result)) {
                return $this->handle_error($result);
            }

            $updated_report = $this->report_service->get_report($report_id);
            
            return new WP_REST_Response(
                array(
                    'success' => true,
                    'data' => $this->prepare_report_for_response($updated_report),
                    'message' => __('Report updated successfully', 'bassmah-staff-reports')
                ),
                200
            );

        } catch (Exception $e) {
            return $this->handle_error($e);
        }
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

        try {
            $result = $this->report_service->add_comment($report_id, $comment);
            
            if (is_wp_error($result)) {
                return $this->handle_error($result);
            }

            return new WP_REST_Response(
                array(
                    'success' => true,
                    'data' => array(
                        'report_id' => $report_id,
                        'comment' => $comment
                    ),
                    'message' => __('Comment added successfully', 'bassmah-staff-reports')
                ),
                200
            );

        } catch (Exception $e) {
            return $this->handle_error($e);
        }
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

        try {
            $result = $this->report_service->delete_report($report_id);
            
            if (is_wp_error($result)) {
                return $this->handle_error($result);
            }

            return new WP_REST_Response(
                array(
                    'success' => true,
                    'data' => array('report_id' => $report_id),
                    'message' => __('Report deleted successfully', 'bassmah-staff-reports')
                ),
                200
            );

        } catch (Exception $e) {
            return $this->handle_error($e);
        }
    }

    /**
     * Get today's reports
     *
     * @since    1.0.0
     * @param    WP_REST_Request    $request    Full data about the request
     * @return   WP_REST_Response|WP_Error
     */
    public function get_today_reports($request) {
        try {
            $reports = $this->report_service->get_today_reports();
            
            return new WP_REST_Response(
                array(
                    'success' => true,
                    'data' => array_map(array($this, 'prepare_report_for_response'), $reports),
                    'meta' => array(
                        'date' => current_time('Y-m-d'),
                        'total' => count($reports)
                    )
                ),
                200
            );

        } catch (Exception $e) {
            return $this->handle_error($e);
        }
    }

    /**
     * Get report statistics
     *
     * @since    1.0.0
     * @param    WP_REST_Request    $request    Full data about the request
     * @return   WP_REST_Response|WP_Error
     */
    public function get_statistics($request) {
        $params = $request->get_params();
        $args = array();

        if (isset($params['date_from'])) {
            $args['date_from'] = $params['date_from'];
        }
        if (isset($params['date_to'])) {
            $args['date_to'] = $params['date_to'];
        }
        if (isset($params['user_id'])) {
            $args['user_id'] = $params['user_id'];
        }

        try {
            $statistics = $this->report_service->get_statistics($args);
            
            return new WP_REST_Response(
                array(
                    'success' => true,
                    'data' => $statistics
                ),
                200
            );

        } catch (Exception $e) {
            return $this->handle_error($e);
        }
    }

    /**
     * Permission check for create report
     *
     * @since    1.0.0
     * @return   bool|WP_Error
     */
    public function create_report_permissions_check() {
        return $this->security->check_capability('bassmah_submit_reports');
    }

    /**
     * Permission check for get reports
     *
     * @since    1.0.0
     * @return   bool|WP_Error
     */
    public function get_reports_permissions_check() {
        return $this->security->check_capability('bassmah_view_all_reports');
    }

    /**
     * Permission check for get my reports
     *
     * @since    1.0.0
     * @return   bool|WP_Error
     */
    public function get_my_reports_permissions_check() {
        return $this->security->check_capability('bassmah_view_own_reports');
    }

    /**
     * Permission check for get report
     *
     * @since    1.0.0
     * @return   bool|WP_Error
     */
    public function get_report_permissions_check($request) {
        $report_id = $request->get_param('id');
        return $this->security->validate_resource_access($report_id, 'report');
    }

    /**
     * Permission check for update report
     *
     * @since    1.0.0
     * @return   bool|WP_Error
     */
    public function update_report_permissions_check($request) {
        $report_id = $request->get_param('id');
        return $this->security->validate_resource_access($report_id, 'report');
    }

    /**
     * Permission check for add comment
     *
     * @since    1.0.0
     * @return   bool|WP_Error
     */
    public function add_comment_permissions_check($request) {
        return $this->security->check_capability('bassmah_comment_reports');
    }

    /**
     * Permission check for delete report
     *
     * @since    1.0.0
     * @return   bool|WP_Error
     */
    public function delete_report_permissions_check($request) {
        $report_id = $request->get_param('id');
        return $this->security->validate_resource_access($report_id, 'report');
    }

    /**
     * Handle errors consistently
     *
     * @since    1.0.0
     * @param    mixed    $error    Error object or exception
     * @return   WP_REST_Response
     */
    private function handle_error($error) {
        if (is_wp_error($error)) {
            return new WP_REST_Response(
                array(
                    'success' => false,
                    'error' => array(
                        'code' => $error->get_error_code(),
                        'message' => $error->get_error_message()
                    )
                ),
                $error->get_error_data('status') ?: 400
            );
        } elseif ($error instanceof Exception) {
            return new WP_REST_Response(
                array(
                    'success' => false,
                    'error' => array(
                        'code' => 'exception',
                        'message' => $error->getMessage()
                    )
                ),
                500
            );
        }

        return new WP_REST_Response(
            array(
                'success' => false,
                'error' => array(
                    'code' => 'unknown_error',
                    'message' => __('An unknown error occurred', 'bassmah-staff-reports')
                )
            ),
            500
        );
    }

    /**
     * Prepare report data for API response
     *
     * @since    1.0.0
     * @param    object    $report    Report object
     * @return   array
     */
    private function prepare_report_for_response($report) {
        if (!$report) {
            return null;
        }

        $user = get_userdata($report->user_id);
        
        return array(
            'id' => $report->id,
            'user_id' => $report->user_id,
            'user_name' => $user ? $user->display_name : 'Unknown',
            'user_email' => $user ? $user->user_email : '',
            'user_role' => $this->get_user_role_display($report->user_id),
            'report_date' => $report->report_date,
            'submission_time' => $report->submission_time,
            'status' => $report->status,
            'tasks' => $report->tasks ?: array(),
            'manager_comment' => $report->manager_comment,
            'ip_address' => $report->ip_address,
            'created_at' => $report->created_at,
            'updated_at' => $report->updated_at
        );
    }

    /**
     * Get user role display name
     *
     * @since    1.0.0
     * @param    int    $user_id    User ID
     * @return   string
     */
    private function get_user_role_display($user_id) {
        $user = get_userdata($user_id);
        if (!$user) {
            return __('Unknown', 'bassmah-staff-reports');
        }

        if (in_array('bassmah_manager', $user->roles)) {
            return __('Manager', 'bassmah-staff-reports');
        } elseif (in_array('bassmah_staff', $user->roles)) {
            return __('Staff Member', 'bassmah-staff-reports');
        } elseif (in_array('administrator', $user->roles)) {
            return __('Administrator', 'bassmah-staff-reports');
        }

        return __('User', 'bassmah-staff-reports');
    }

    /**
     * Validate tasks array
     *
     * @since    1.0.0
     * @param    mixed    $value    Value to validate
     * @param    WP_REST_Request    $request    Request object
     * @param    string    $param    Parameter name
     * @return   bool|WP_Error
     */
    public function validate_tasks_array($value, $request, $param) {
        if (!is_array($value)) {
            return new WP_Error(
                'invalid_tasks',
                __('Tasks must be an array', 'bassmah-staff-reports'),
                array('status' => 400)
            );
        }

        if (empty($value)) {
            return new WP_Error(
                'empty_tasks',
                __('At least one task is required', 'bassmah-staff-reports'),
                array('status' => 400)
            );
        }

        foreach ($value as $index => $task) {
            if (!isset($task['task_description']) || empty($task['task_description'])) {
                return new WP_Error(
                    'missing_task_description',
                    sprintf(__('Task %d: Description is required', 'bassmah-staff-reports'), $index + 1),
                    array('status' => 400)
                );
            }

            if (!isset($task['next_action']) || empty($task['next_action'])) {
                return new WP_Error(
                    'missing_next_action',
                    sprintf(__('Task %d: Next action is required', 'bassmah-staff-reports'), $index + 1),
                    array('status' => 400)
                );
            }
        }

        return true;
    }

    /**
     * Validate report date
     *
     * @since    1.0.0
     * @param    mixed    $value    Value to validate
     * @param    WP_REST_Request    $request    Request object
     * @param    string    $param    Parameter name
     * @return   bool|WP_Error
     */
    public function validate_report_date($value, $request, $param) {
        $date = DateTime::createFromFormat('Y-m-d', $value);
        
        if (!$date || $date->format('Y-m-d') !== $value) {
            return new WP_Error(
                'invalid_date_format',
                __('Date must be in Y-m-d format', 'bassmah-staff-reports'),
                array('status' => 400)
            );
        }

        // Don't allow future dates for report submission
        if ($date > new DateTime()) {
            return new WP_Error(
                'future_date',
                __('Report date cannot be in the future', 'bassmah-staff-reports'),
                array('status' => 400)
            );
        }

        return true;
    }

    /**
     * Validate status
     *
     * @since    1.0.0
     * @param    mixed    $value    Value to validate
     * @param    WP_REST_Request    $request    Request object
     * @param    string    $param    Parameter name
     * @return   bool|WP_Error
     */
    public function validate_status($value, $request, $param) {
        $valid_statuses = array('draft', 'submitted', 'approved', 'rejected');
        
        if (!in_array($value, $valid_statuses)) {
            return new WP_Error(
                'invalid_status',
                __('Invalid status value', 'bassmah-staff-reports'),
                array('status' => 400)
            );
        }

        return true;
    }

    /**
     * Validate user ID
     *
     * @since    1.0.0
     * @param    mixed    $value    Value to validate
     * @param    WP_REST_Request    $request    Request object
     * @param    string    $param    Parameter name
     * @return   bool|WP_Error
     */
    public function validate_user_id($value, $request, $param) {
        if (!is_numeric($value) || $value <= 0) {
            return new WP_Error(
                'invalid_user_id',
                __('User ID must be a positive integer', 'bassmah-staff-reports'),
                array('status' => 400)
            );
        }

        return true;
    }

    /**
     * Validate report ID
     *
     * @since    1.0.0
     * @param    mixed    $value    Value to validate
     * @param    WP_REST_Request    $request    Request object
     * @param    string    $param    Parameter name
     * @return   bool|WP_Error
     */
    public function validate_report_id($value, $request, $param) {
        if (!is_numeric($value) || $value <= 0) {
            return new WP_Error(
                'invalid_report_id',
                __('Report ID must be a positive integer', 'bassmah-staff-reports'),
                array('status' => 400)
            );
        }

        return true;
    }
}
