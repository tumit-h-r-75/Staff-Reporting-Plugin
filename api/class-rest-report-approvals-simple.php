<?php
/**
 * REST API Report Approvals Controller - Simplified Version
 *
 * @since      1.0.0
 * @package    Bassmah_Staff_Reports
 * @author     Tumit <tumit@bassmah.ca>
 */
class Bassmah_Staff_Reports_REST_Approvals {

    /**
     * Constructor
     *
     * @since    1.0.0
     */
    public function __construct() {
        // Simple constructor without dependencies
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
            'permission_callback' => array($this, 'manager_permission_check')
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
        
        if (!$report_id) {
            return new WP_REST_Response(
                array('message' => 'Invalid report ID'),
                400
            );
        }

        return new WP_REST_Response(
            array('message' => 'Report approved successfully'),
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
        
        if (!$report_id) {
            return new WP_REST_Response(
                array('message' => 'Invalid report ID'),
                400
            );
        }

        return new WP_REST_Response(
            array('message' => 'Report rejected successfully'),
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
                'You must be logged in to access this endpoint.',
                array('status' => 401)
            );
        }

        if (!current_user_can('manage_options')) {
            return new WP_Error(
                'rest_forbidden',
                'You do not have permission to manage report approvals.',
                array('status' => 403)
            );
        }

        return true;
    }
}
