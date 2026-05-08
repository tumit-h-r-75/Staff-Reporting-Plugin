<?php
/**
 * REST API Report Approvals Controller
 *
 * Handles manager approval/rejection of submitted reports
 * with proper authentication, validation, and audit logging.
 *
 * @since      1.0.0
 * @package    Bassmah_Staff_Reports
 * @author     Tumit <tumit@bassmah.ca>
 */
class Bassmah_Staff_Reports_REST_Approvals {

    /**
     * Report model used for approval actions.
     *
     * @var Bassmah_Staff_Reports_Report
     */
    private $report_service;

    /**
     * Constructor
     *
     * @since    1.0.0
     */
    public function __construct() {
        $this->report_service = new Bassmah_Staff_Reports_Report();
    }

    /**
     * Register REST API routes for approvals
     *
     * @since    1.0.0
     */
    public function register_routes() {
        // POST /reports/{id}/approve - Approve a report
        register_rest_route('bassmah/v2', '/reports/(?P<id>\d+)/approve', array(
            array(
                'methods' => WP_REST_Server::EDITABLE,
                'callback' => array($this, 'approve_report'),
                'permission_callback' => array($this, 'manager_permission_check')
            )
        ));

        // POST /reports/{id}/reject - Reject a report
        register_rest_route('bassmah/v2', '/reports/(?P<id>\d+)/reject', array(
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => array($this, 'reject_report'),
            'permission_callback' => array($this, 'manager_permission_check'),
            'args' => array(
                'id' => array(
                    'required' => true,
                    'type' => 'integer',
                    'description' => __('Report ID', 'bassmah-staff-reports'),
                    'validate_callback' => array($this, 'validate_report_id')
                ),
                'reason' => array(
                    'required' => true,
                    'type' => 'string',
                    'description' => __('Rejection reason', 'bassmah-staff-reports'),
                    'sanitize_callback' => 'sanitize_textarea_field',
                    'minLength' => 5
                )
            )
        ));

        // GET /reports/pending - Get pending reports for approval
        register_rest_route('bassmah/v2', '/reports/pending', array(
            'methods' => WP_REST_Server::READABLE,
            'callback' => array($this, 'get_pending_reports'),
            'permission_callback' => array($this, 'manager_permission_check'),
            'args' => array(
                'limit' => array(
                    'required' => false,
                    'type' => 'integer',
                    'minimum' => 1,
                    'maximum' => 100,
                    'description' => __('Number of reports to return', 'bassmah-staff-reports'),
                    'sanitize_callback' => 'absint'
                ),
                'offset' => array(
                    'required' => false,
                    'type' => 'integer',
                    'minimum' => 0,
                    'description' => __('Number of reports to skip', 'bassmah-staff-reports'),
                    'sanitize_callback' => 'absint'
                ),
                'user_id' => array(
                    'required' => false,
                    'type' => 'integer',
                    'description' => __('Filter by user ID', 'bassmah-staff-reports'),
                    'sanitize_callback' => 'absint',
                    'validate_callback' => array($this, 'validate_user_id')
                )
            )
        ));

        // GET /reports/approval-history - Get approval history
        register_rest_route('bassmah/v2', '/reports/(?P<id>\d+)/approval-history', array(
            'methods' => WP_REST_Server::READABLE,
            'callback' => array($this, 'get_approval_history'),
            'permission_callback' => array($this, 'manager_permission_check'),
            'args' => array(
                'id' => array(
                    'required' => true,
                    'type' => 'integer',
                    'description' => __('Report ID', 'bassmah-staff-reports'),
                    'validate_callback' => array($this, 'validate_report_id')
                )
            )
        ));
    }

    /**
     * Approve a report
     *
     * @since    1.0.0
     * @param    WP_REST_Request    $request    REST request
     * @return   WP_REST_Response
     */
    public function approve_report($request) {
        $report_id = intval($request['id']);
        $comment = $request->get_param('comment') ?? '';
        $manager_id = get_current_user_id();

        // Get report
        $report = $this->report_service->get_report($report_id);
        if (!$report) {
            return new WP_REST_Response(
                array(
                    'success' => false,
                    'message' => __('Report not found.', 'bassmah-staff-reports')
                ),
                404
            );
        }

        // Check if already approved or rejected
        if (in_array($report->status, array('approved', 'rejected'))) {
            return new WP_REST_Response(
                array(
                    'success' => false,
                    'message' => __('This report has already been reviewed.', 'bassmah-staff-reports'),
                    'current_status' => $report->status
                ),
                400
            );
        }

        // Approve the report
        $result = $this->report_service->update_report_status($report_id, 'approve', $comment);

        if (is_wp_error($result)) {
            return new WP_REST_Response(
                array(
                    'success' => false,
                    'message' => $result->get_error_message()
                ),
                400
            );
        }

        // Send notification to staff
        do_action('bassmah_report_approved', $report_id, $manager_id, $comment);

        return new WP_REST_Response(
            array(
                'success' => true,
                'message' => __('Report approved successfully.', 'bassmah-staff-reports'),
                'report_id' => $report_id,
                'status' => 'approved',
                'approved_at' => current_time('mysql'),
                'approved_by' => $manager_id
            ),
            200
        );
    }

    /**
     * Reject a report
     *
     * @since    1.0.0
     * @param    WP_REST_Request    $request    REST request
     * @return   WP_REST_Response
     */
    public function reject_report($request) {
        $report_id = intval($request['id']);
        $reason = $request->get_param('reason') ?? '';
        $manager_id = get_current_user_id();

        // Validate reason
        if (empty($reason)) {
            return new WP_REST_Response(
                array(
                    'success' => false,
                    'message' => __('Rejection reason is required.', 'bassmah-staff-reports')
                ),
                400
            );
        }

        // Get report
        $report = $this->report_service->get_report($report_id);
        if (!$report) {
            return new WP_REST_Response(
                array(
                    'success' => false,
                    'message' => __('Report not found.', 'bassmah-staff-reports')
                ),
                404
            );
        }

        // Check if already approved or rejected
        if (in_array($report->status, array('approved', 'rejected'))) {
            return new WP_REST_Response(
                array(
                    'success' => false,
                    'message' => __('This report has already been reviewed.', 'bassmah-staff-reports'),
                    'current_status' => $report->status
                ),
                400
            );
        }

        // Reject the report
        $result = $this->report_service->update_report_status($report_id, 'reject', $reason);

        if (is_wp_error($result)) {
            return new WP_REST_Response(
                array(
                    'success' => false,
                    'message' => $result->get_error_message()
                ),
                400
            );
        }

        // Send notification to staff
        do_action('bassmah_report_rejected', $report_id, $manager_id, $reason);

        return new WP_REST_Response(
            array(
                'success' => true,
                'message' => __('Report rejected successfully.', 'bassmah-staff-reports'),
                'report_id' => $report_id,
                'status' => 'rejected',
                'rejected_at' => current_time('mysql'),
                'rejected_by' => $manager_id,
                'rejection_reason' => $reason
            ),
            200
        );
    }

    /**
     * Get pending reports for approval
     *
     * @since    1.0.0
     * @param    WP_REST_Request    $request    REST request
     * @return   WP_REST_Response
     */
    public function get_pending_reports($request) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'staff_reports';
        $users_table = $wpdb->users;

        $limit = intval($request->get_param('limit')) ?: 20;
        $offset = intval($request->get_param('offset')) ?: 0;
        $user_id = $request->get_param('user_id');
        $user_id = (!empty($user_id)) ? intval($user_id) : 0;

        $where = "WHERE r.status = 'submitted'";
        $params = array();

        if ($user_id > 0) {
            $where .= " AND r.user_id = %d";
            $params[] = $user_id;
        }

        $query = "
            SELECT r.*, u.display_name, u.user_email 
            FROM {$table_name} r 
            LEFT JOIN {$users_table} u ON r.user_id = u.ID 
            {$where}
            ORDER BY r.submission_time ASC
            LIMIT %d OFFSET %d
        ";

        $params[] = $limit;
        $params[] = $offset;

        if (!empty($params)) {
            $query = $wpdb->prepare($query, $params);
        } else {
            $query = $wpdb->prepare($query, array($limit, $offset));
        }

        $reports = $wpdb->get_results($query);

        // Get total count
        $count_query = "SELECT COUNT(*) FROM {$table_name} WHERE status = 'submitted'";
        if ($user_id > 0) {
            $count_query .= " AND user_id = %d";
            $total = $wpdb->get_var($wpdb->prepare($count_query, $user_id));
        } else {
            $total = $wpdb->get_var($count_query);
        }

        // Format reports
        $formatted_reports = array();
        foreach ($reports as $report) {
            $formatted_reports[] = array(
                'id' => intval($report->id),
                'user_id' => intval($report->user_id),
                'user_name' => $report->display_name,
                'user_email' => $report->user_email,
                'report_date' => $report->report_date,
                'submission_time' => $report->submission_time,
                'status' => $report->status,
                'tasks' => json_decode($report->tasks_json, true),
                'submitted_at' => $report->submission_time
            );
        }

        return new WP_REST_Response(
            array(
                'success' => true,
                'data' => $formatted_reports,
                'total' => intval($total),
                'limit' => $limit,
                'offset' => $offset,
                'pages' => ceil($total / $limit)
            ),
            200
        );
    }

    /**
     * Get approval history for a report
     *
     * @since    1.0.0
     * @param    WP_REST_Request    $request    REST request
     * @return   WP_REST_Response
     */
    public function get_approval_history($request) {
        $report_id = intval($request['id']);

        // Get report
        $report = $this->report_service->get_report($report_id);
        if (!$report) {
            return new WP_REST_Response(
                array(
                    'success' => false,
                    'message' => __('Report not found.', 'bassmah-staff-reports')
                ),
                404
            );
        }

        $history = array(
            'report_id' => intval($report->id),
            'current_status' => $report->status,
            'submitted_at' => $report->submission_time,
            'submitted_by' => intval($report->user_id)
        );

        // Add approval/rejection info
        if ($report->status === 'approved') {
            $history['approved_at'] = $report->updated_at;
            $history['approved_by'] = intval($report->manager_id);
            $history['manager_comment'] = $report->manager_comment;
        } elseif ($report->status === 'rejected') {
            $history['rejected_at'] = $report->updated_at;
            $history['rejected_by'] = intval($report->manager_id);
            $history['rejection_reason'] = $report->manager_comment;
        }

        return new WP_REST_Response(
            array(
                'success' => true,
                'data' => $history
            ),
            200
        );
    }

    /**
     * Check if current user is a manager (permission callback)
     *
     * @since    1.0.0
     * @return   bool|WP_Error
     */
    public function manager_permission_check() {
        if (!is_user_logged_in()) {
            return new WP_Error(
                'rest_not_logged_in',
                __('You must be logged in to access this endpoint.', 'bassmah-staff-reports'),
                array('status' => 401)
            );
        }

        if (!current_user_can('bassmah_manage_approvals') && !current_user_can('manage_options')) {
            return new WP_Error(
                'rest_forbidden',
                __('You do not have permission to manage report approvals.', 'bassmah-staff-reports'),
                array('status' => 403)
            );
        }

        return true;
    }

    /**
     * Validate report ID
     *
     * @since    1.0.0
     * @param    mixed    $value    Value to validate
     * @return   bool
     */
    public function validate_report_id($value) {
        return is_numeric($value) && intval($value) > 0;
    }

    /**
     * Validate user ID
     *
     * @since    1.0.0
     * @param    mixed    $value    Value to validate
     * @return   bool|WP_Error
     */
    public function validate_user_id($value) {
        // If empty, allow it (optional parameter)
        if (empty($value) || $value === null || $value === '') {
            return true;
        }
        // If provided, must be a positive integer
        if (!is_numeric($value) || intval($value) <= 0) {
            return new WP_Error('invalid_user_id', __('User ID must be a positive integer.', 'bassmah-staff-reports'));
        }
        return true;
    }
}
