<?php
/**
 * REST API endpoints for salary management.
 *
 * @since      1.0.0
 * @package    Bassmah_Staff_Reports
 * @author     Tumit <tumit@bassmah.ca>
 */
class Bassmah_Staff_Reports_REST_Salary {

    /**
     * Register REST API routes
     *
     * @since    1.0.0
     */
    public function register_routes() {
        register_rest_route('bassmah/v1', '/salary/me', array(
            array(
                'methods' => WP_REST_Server::READABLE,
                'callback' => array($this, 'get_my_salary'),
                'permission_callback' => array($this, 'get_my_salary_permissions_check'),
                'args' => array(
                    'month' => array(
                        'required' => false,
                        'type' => 'string',
                        'format' => 'Y-m',
                        'description' => __('Month in Y-m format (defaults to current month)', 'bassmah-staff-reports'),
                    ),
                ),
            ),
        ));

        register_rest_route('bassmah/v1', '/salary/(?P<user_id>\d+)', array(
            array(
                'methods' => WP_REST_Server::READABLE,
                'callback' => array($this, 'get_user_salary'),
                'permission_callback' => array($this, 'get_user_salary_permissions_check'),
                'args' => array(
                    'month' => array(
                        'required' => false,
                        'type' => 'string',
                        'format' => 'Y-m',
                        'description' => __('Month in Y-m format (defaults to current month)', 'bassmah-staff-reports'),
                    ),
                ),
            ),
            array(
                'methods' => WP_REST_Server::EDITABLE,
                'callback' => array($this, 'update_user_salary_settings'),
                'permission_callback' => array($this, 'update_user_salary_permissions_check'),
                'args' => array(
                    'monthly_salary' => array(
                        'required' => true,
                        'type' => 'number',
                        'minimum' => 0,
                        'description' => __('Monthly salary amount', 'bassmah-staff-reports'),
                    ),
                    'working_days_per_month' => array(
                        'required' => true,
                        'type' => 'integer',
                        'minimum' => 1,
                        'maximum' => 31,
                        'description' => __('Number of working days per month', 'bassmah-staff-reports'),
                    ),
                    'currency' => array(
                        'required' => false,
                        'type' => 'string',
                        'default' => 'CAD',
                        'description' => __('Currency code', 'bassmah-staff-reports'),
                    ),
                    'effective_from' => array(
                        'required' => true,
                        'type' => 'string',
                        'format' => 'date',
                        'description' => __('Effective date for salary settings', 'bassmah-staff-reports'),
                    ),
                ),
            ),
        ));

        register_rest_route('bassmah/v1', '/salary/(?P<user_id>\d+)/history', array(
            array(
                'methods' => WP_REST_Server::READABLE,
                'callback' => array($this, 'get_user_salary_history'),
                'permission_callback' => array($this, 'get_user_salary_permissions_check'),
                'args' => array(
                    'limit' => array(
                        'required' => false,
                        'type' => 'integer',
                        'default' => 12,
                        'minimum' => 1,
                        'maximum' => 24,
                        'description' => __('Number of months to retrieve', 'bassmah-staff-reports'),
                    ),
                ),
            ),
        ));

        register_rest_route('bassmah/v1', '/salary/dashboard/(?P<user_id>\d+)', array(
            array(
                'methods' => WP_REST_Server::READABLE,
                'callback' => array($this, 'get_user_dashboard_stats'),
                'permission_callback' => array($this, 'get_user_dashboard_permissions_check'),
            ),
        ));

        register_rest_route('bassmah/v1', '/salary/batch', array(
            array(
                'methods' => WP_REST_Server::READABLE,
                'callback' => array($this, 'get_batch_salary_summary'),
                'permission_callback' => array($this, 'get_batch_salary_permissions_check'),
                'args' => array(
                    'user_ids' => array(
                        'required' => true,
                        'type' => 'array',
                        'items' => array('type' => 'integer'),
                        'description' => __('Array of user IDs', 'bassmah-staff-reports'),
                    ),
                    'month' => array(
                        'required' => false,
                        'type' => 'string',
                        'format' => 'Y-m',
                        'description' => __('Month in Y-m format (defaults to current month)', 'bassmah-staff-reports'),
                    ),
                ),
            ),
        ));

        register_rest_route('bassmah/v1', '/salary/export', array(
            array(
                'methods' => WP_REST_Server::CREATABLE,
                'callback' => array($this, 'export_salary_csv'),
                'permission_callback' => array($this, 'export_salary_permissions_check'),
                'args' => array(
                    'user_ids' => array(
                        'required' => true,
                        'type' => 'array',
                        'items' => array('type' => 'integer'),
                        'description' => __('Array of user IDs', 'bassmah-staff-reports'),
                    ),
                    'month' => array(
                        'required' => false,
                        'type' => 'string',
                        'format' => 'Y-m',
                        'description' => __('Month in Y-m format (defaults to current month)', 'bassmah-staff-reports'),
                    ),
                ),
            ),
        ));
    }

    /**
     * Get current user's salary information
     *
     * @since    1.0.0
     * @param    WP_REST_Request    $request    Full data about the request.
     * @return   WP_REST_Response|WP_Error
     */
    public function get_my_salary($request) {
        $user_id = get_current_user_id();
        $month = $request->get_param('month') ?: date('Y-m');

        $salary_class = new Bassmah_Staff_Reports_Salary();
        $salary_summary = $salary_class->calculate_monthly_salary($user_id, $month);

        if (is_wp_error($salary_summary)) {
            return $salary_summary;
        }

        return new WP_REST_Response($salary_summary, 200);
    }

    /**
     * Get a user's salary information
     *
     * @since    1.0.0
     * @param    WP_REST_Request    $request    Full data about the request.
     * @return   WP_REST_Response|WP_Error
     */
    public function get_user_salary($request) {
        $user_id = $request->get_param('user_id');
        $month = $request->get_param('month') ?: date('Y-m');

        $salary_class = new Bassmah_Staff_Reports_Salary();
        $salary_summary = $salary_class->calculate_monthly_salary($user_id, $month);

        if (is_wp_error($salary_summary)) {
            return $salary_summary;
        }

        return new WP_REST_Response($salary_summary, 200);
    }

    /**
     * Update user's salary settings
     *
     * @since    1.0.0
     * @param    WP_REST_Request    $request    Full data about the request.
     * @return   WP_REST_Response|WP_Error
     */
    public function update_user_salary_settings($request) {
        $user_id = $request->get_param('user_id');

        $salary_data = array(
            'monthly_salary' => $request->get_param('monthly_salary'),
            'working_days_per_month' => $request->get_param('working_days_per_month'),
            'currency' => $request->get_param('currency') ?: 'CAD',
            'effective_from' => $request->get_param('effective_from')
        );

        $salary_class = new Bassmah_Staff_Reports_Salary();
        $result = $salary_class->set_salary_settings($user_id, $salary_data);

        if (is_wp_error($result)) {
            return $result;
        }

        return new WP_REST_Response(
            array('message' => __('Salary settings updated successfully.', 'bassmah-staff-reports')),
            200
        );
    }

    /**
     * Get user's salary history
     *
     * @since    1.0.0
     * @param    WP_REST_Request    $request    Full data about the request.
     * @return   WP_REST_Response|WP_Error
     */
    public function get_user_salary_history($request) {
        $user_id = $request->get_param('user_id');
        $limit = $request->get_param('limit') ?: 12;

        $salary_class = new Bassmah_Staff_Reports_Salary();
        $history = $salary_class->get_salary_history($user_id, $limit);

        return new WP_REST_Response($history, 200);
    }

    /**
     * Get user's dashboard statistics
     *
     * @since    1.0.0
     * @param    WP_REST_Request    $request    Full data about the request.
     * @return   WP_REST_Response|WP_Error
     */
    public function get_user_dashboard_stats($request) {
        $user_id = $request->get_param('user_id');

        $salary_class = new Bassmah_Staff_Reports_Salary();
        $dashboard_stats = $salary_class->get_dashboard_stats($user_id);

        return new WP_REST_Response($dashboard_stats, 200);
    }

    /**
     * Get batch salary summary for multiple users
     *
     * @since    1.0.0
     * @param    WP_REST_Request    $request    Full data about the request.
     * @return   WP_REST_Response|WP_Error
     */
    public function get_batch_salary_summary($request) {
        $user_ids = $request->get_param('user_ids');
        $month = $request->get_param('month') ?: date('Y-m');

        $salary_class = new Bassmah_Staff_Reports_Salary();
        $summaries = $salary_class->get_batch_salary_summary($user_ids, $month);

        return new WP_REST_Response($summaries, 200);
    }

    /**
     * Export salary data to CSV
     *
     * @since    1.0.0
     * @param    WP_REST_Request    $request    Full data about the request.
     * @return   WP_REST_Response|WP_Error
     */
    public function export_salary_csv($request) {
        $user_ids = $request->get_param('user_ids');
        $month = $request->get_param('month') ?: date('Y-m');

        $salary_class = new Bassmah_Staff_Reports_Salary();
        $csv = $salary_class->export_salary_csv($user_ids, $month);

        $filename = sprintf('salary-export-%s.csv', $month);

        return new WP_REST_Response(
            array(
                'csv' => $csv,
                'filename' => $filename
            ),
            200
        );
    }

    /**
     * Check if a given request has permission to get own salary
     *
     * @since    1.0.0
     * @param    WP_REST_Request    $request    Full data about the request.
     * @return   WP_Error|bool
     */
    public function get_my_salary_permissions_check($request) {
        $login_check = Bassmah_Staff_Reports_Roles::require_login();
        if (is_wp_error($login_check)) {
            return $login_check;
        }

        return current_user_can('bassmah_view_own_salary');
    }

    /**
     * Check if a given request has permission to get user salary
     *
     * @since    1.0.0
     * @param    WP_REST_Request    $request    Full data about the request.
     * @return   WP_Error|bool
     */
    public function get_user_salary_permissions_check($request) {
        $login_check = Bassmah_Staff_Reports_Roles::require_login();
        if (is_wp_error($login_check)) {
            return $login_check;
        }

        $user_id = $request->get_param('user_id');
        return Bassmah_Staff_Reports_Roles::can_view_user_salary($user_id);
    }

    /**
     * Check if a given request has permission to update user salary
     *
     * @since    1.0.0
     * @param    WP_REST_Request    $request    Full data about the request.
     * @return   WP_Error|bool
     */
    public function update_user_salary_permissions_check($request) {
        $login_check = Bassmah_Staff_Reports_Roles::require_login();
        if (is_wp_error($login_check)) {
            return $login_check;
        }

        return current_user_can('bassmah_manage_salary_settings');
    }

    /**
     * Check if a given request has permission to get user dashboard
     *
     * @since    1.0.0
     * @param    WP_REST_Request    $request    Full data about the request.
     * @return   WP_Error|bool
     */
    public function get_user_dashboard_permissions_check($request) {
        $login_check = Bassmah_Staff_Reports_Roles::require_login();
        if (is_wp_error($login_check)) {
            return $login_check;
        }

        $user_id = $request->get_param('user_id');
        $current_user_id = get_current_user_id();

        // Can view own dashboard
        if ($user_id == $current_user_id) {
            return current_user_can('bassmah_view_own_salary');
        }

        // Managers can view any dashboard
        return current_user_can('bassmah_view_all_salary');
    }

    /**
     * Check if a given request has permission to get batch salary
     *
     * @since    1.0.0
     * @param    WP_REST_Request    $request    Full data about the request.
     * @return   WP_Error|bool
     */
    public function get_batch_salary_permissions_check($request) {
        $login_check = Bassmah_Staff_Reports_Roles::require_login();
        if (is_wp_error($login_check)) {
            return $login_check;
        }

        return current_user_can('bassmah_view_all_salary');
    }

    /**
     * Check if a given request has permission to export salary
     *
     * @since    1.0.0
     * @param    WP_REST_Request    $request    Full data about the request.
     * @return   WP_Error|bool
     */
    public function export_salary_permissions_check($request) {
        $login_check = Bassmah_Staff_Reports_Roles::require_login();
        if (is_wp_error($login_check)) {
            return $login_check;
        }

        return current_user_can('bassmah_export_reports');
    }
}
