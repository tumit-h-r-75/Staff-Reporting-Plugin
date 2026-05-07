<?php
/**
 * REST API Salary Controller v2 - Enhanced Salary API
 *
 * Comprehensive REST API endpoints for salary management
 * with proper authentication, validation, and error handling.
 *
 * @since      1.0.0
 * @package    Bassmah_Staff_Reports
 * @author     Tumit <tumit@bassmah.ca>
 */
class Bassmah_Staff_Reports_REST_Salary_V2 {

    /**
     * Service container instance
     *
     * @since    1.0.0
     * @access   private
     * @var      Bassmah_Staff_Reports_Service_Container    $container    Service container
     */
    private $container;

    /**
     * Salary service instance
     *
     * @since    1.0.0
     * @access   private
     * @var      Bassmah_Staff_Reports_Salary_Service    $salary_service    Salary service
     */
    private $salary_service;

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
        $this->salary_service = $this->container->get('salary');
        $this->security = $this->container->get('security');
    }

    /**
     * Register REST API routes
     *
     * @since    1.0.0
     */
    public function register_routes() {
        // GET /salary/me - Get current user's salary info
        register_rest_route('bassmah/v2', '/salary/me', array(
            'methods' => WP_REST_Server::READABLE,
            'callback' => array($this, 'get_my_salary'),
            'permission_callback' => array($this, 'get_my_salary_permissions_check'),
            'args' => array(
                'month' => array(
                    'required' => false,
                    'type' => 'string',
                    'format' => 'Y-m',
                    'description' => __('Month in Y-m format', 'bassmah-staff-reports'),
                    'validate_callback' => array($this, 'validate_month_format')
                )
            )
        ));

        // GET /salary/{user_id} - Get user's salary info
        register_rest_route('bassmah/v2', '/salary/(?P<user_id>\d+)', array(
            'methods' => WP_REST_Server::READABLE,
            'callback' => array($this, 'get_user_salary'),
            'permission_callback' => array($this, 'get_user_salary_permissions_check'),
            'args' => array(
                'user_id' => array(
                    'required' => true,
                    'type' => 'integer',
                    'description' => __('User ID', 'bassmah-staff-reports'),
                    'validate_callback' => array($this, 'validate_user_id')
                ),
                'month' => array(
                    'required' => false,
                    'type' => 'string',
                    'format' => 'Y-m',
                    'description' => __('Month in Y-m format', 'bassmah-staff-reports'),
                    'validate_callback' => array($this, 'validate_month_format')
                )
            )
        ));

        // PUT /salary/{user_id} - Update salary settings
        register_rest_route('bassmah/v2', '/salary/(?P<user_id>\d+)', array(
            'methods' => WP_REST_Server::EDITABLE,
            'callback' => array($this, 'update_user_salary'),
            'permission_callback' => array($this, 'update_user_salary_permissions_check'),
            'args' => array(
                'user_id' => array(
                    'required' => true,
                    'type' => 'integer',
                    'description' => __('User ID', 'bassmah-staff-reports'),
                    'validate_callback' => array($this, 'validate_user_id')
                ),
                'monthly_salary' => array(
                    'required' => true,
                    'type' => 'number',
                    'minimum' => 0,
                    'maximum' => 999999.99,
                    'description' => __('Monthly salary amount', 'bassmah-staff-reports'),
                    'validate_callback' => array($this, 'validate_monetary_amount')
                ),
                'working_days_per_month' => array(
                    'required' => true,
                    'type' => 'integer',
                    'minimum' => 1,
                    'maximum' => 31,
                    'description' => __('Working days per month', 'bassmah-staff-reports'),
                    'validate_callback' => array($this, 'validate_working_days')
                ),
                'currency' => array(
                    'required' => false,
                    'type' => 'string',
                    'enum' => array('CAD', 'USD', 'EUR', 'GBP'),
                    'description' => __('Currency code', 'bassmah-staff-reports'),
                    'validate_callback' => array($this, 'validate_currency')
                ),
                'effective_from' => array(
                    'required' => true,
                    'type' => 'string',
                    'format' => 'date',
                    'description' => __('Effective date', 'bassmah-staff-reports'),
                    'validate_callback' => array($this, 'validate_date_not_future')
                ),
                'effective_to' => array(
                    'required' => false,
                    'type' => 'string',
                    'format' => 'date',
                    'description' => __('End date for salary period', 'bassmah-staff-reports'),
                    'validate_callback' => array($this, 'validate_date')
                )
            )
        ));

        // GET /salary/{user_id}/history - Get salary history
        register_rest_route('bassmah/v2', '/salary/(?P<user_id>\d+)/history', array(
            'methods' => WP_REST_Server::READABLE,
            'callback' => array($this, 'get_salary_history'),
            'permission_callback' => array($this, 'get_salary_history_permissions_check'),
            'args' => array(
                'user_id' => array(
                    'required' => true,
                    'type' => 'integer',
                    'description' => __('User ID', 'bassmah-staff-reports'),
                    'validate_callback' => array($this, 'validate_user_id')
                ),
                'limit' => array(
                    'required' => false,
                    'type' => 'integer',
                    'minimum' => 1,
                    'maximum' => 24,
                    'description' => __('Number of months to retrieve', 'bassmah-staff-reports')
                )
            )
        ));

        // GET /salary/dashboard/{user_id} - Get dashboard statistics
        register_rest_route('bassmah/v2', '/salary/dashboard/(?P<user_id>\d+)', array(
            'methods' => WP_REST_Server::READABLE,
            'callback' => array($this, 'get_dashboard_statistics'),
            'permission_callback' => array($this, 'get_dashboard_permissions_check'),
            'args' => array(
                'user_id' => array(
                    'required' => true,
                    'type' => 'integer',
                    'description' => __('User ID', 'bassmah-staff-reports'),
                    'validate_callback' => array($this, 'validate_user_id')
                )
            )
        ));

        // GET /salary/batch - Get batch salary summary
        register_rest_route('bassmah/v2', '/salary/batch', array(
            'methods' => WP_REST_Server::READABLE,
            'callback' => array($this, 'get_batch_salary_summary'),
            'permission_callback' => array($this, 'get_batch_salary_permissions_check'),
            'args' => array(
                'user_ids' => array(
                    'required' => true,
                    'type' => 'array',
                    'items' => array('type' => 'integer'),
                    'description' => __('Array of user IDs', 'bassmah-staff-reports'),
                    'validate_callback' => array($this, 'validate_user_ids_array')
                ),
                'month' => array(
                    'required' => false,
                    'type' => 'string',
                    'format' => 'Y-m',
                    'description' => __('Month in Y-m format', 'bassmah-staff-reports'),
                    'validate_callback' => array($this, 'validate_month_format')
                )
            )
        ));

        // POST /salary/export - Export salary data
        register_rest_route('bassmah/v2', '/salary/export', array(
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => array($this, 'export_salary_data'),
            'permission_callback' => array($this, 'export_salary_permissions_check'),
            'args' => array(
                'user_ids' => array(
                    'required' => true,
                    'type' => 'array',
                    'items' => array('type' => 'integer'),
                    'description' => __('Array of user IDs', 'bassmah-staff-reports'),
                    'validate_callback' => array($this, 'validate_user_ids_array')
                ),
                'month' => array(
                    'required' => false,
                    'type' => 'string',
                    'format' => 'Y-m',
                    'description' => __('Month in Y-m format', 'bassmah-staff-reports'),
                    'validate_callback' => array($this, 'validate_month_format')
                ),
                'format' => array(
                    'required' => false,
                    'type' => 'string',
                    'enum' => array('csv', 'excel'),
                    'description' => __('Export format', 'bassmah-staff-reports'),
                    'validate_callback' => array($this, 'validate_export_format')
                )
            )
        ));

        // POST /salary/recalculate - Recalculate monthly salaries
        register_rest_route('bassmah/v2', '/salary/recalculate', array(
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => array($this, 'recalculate_monthly_salaries'),
            'permission_callback' => array($this, 'recalculate_salary_permissions_check'),
            'args' => array(
                'month' => array(
                    'required' => false,
                    'type' => 'string',
                    'format' => 'Y-m',
                    'description' => __('Month in Y-m format', 'bassmah-staff-reports'),
                    'validate_callback' => array($this, 'validate_month_format')
                )
            )
        ));
    }

    /**
     * Get current user's salary information
     *
     * @since    1.0.0
     * @param    WP_REST_Request    $request    Full data about the request
     * @return   WP_REST_Response|WP_Error
     */
    public function get_my_salary($request) {
        $user_id = get_current_user_id();
        $month = $request->get_param('month');

        try {
            $salary_summary = $this->salary_service->calculate_monthly_salary($user_id, $month);
            
            if (is_wp_error($salary_summary)) {
                return $this->handle_error($salary_summary);
            }

            return new WP_REST_Response(
                array(
                    'success' => true,
                    'data' => $salary_summary
                ),
                200
            );

        } catch (Exception $e) {
            return $this->handle_error($e);
        }
    }

    /**
     * Get user's salary information
     *
     * @since    1.0.0
     * @param    WP_REST_Request    $request    Full data about the request
     * @return   WP_REST_Response|WP_Error
     */
    public function get_user_salary($request) {
        $user_id = $request->get_param('user_id');
        $month = $request->get_param('month');

        try {
            $permission_check = $this->security->validate_resource_access($user_id, 'salary');
            if (is_wp_error($permission_check)) {
                return $permission_check;
            }

            $salary_summary = $this->salary_service->calculate_monthly_salary($user_id, $month);
            
            if (is_wp_error($salary_summary)) {
                return $this->handle_error($salary_summary);
            }

            return new WP_REST_Response(
                array(
                    'success' => true,
                    'data' => $salary_summary
                ),
                200
            );

        } catch (Exception $e) {
            return $this->handle_error($e);
        }
    }

    /**
     * Update user's salary settings
     *
     * @since    1.0.0
     * @param    WP_REST_Request    $request    Full data about the request
     * @return   WP_REST_Response|WP_Error
     */
    public function update_user_salary($request) {
        $user_id = $request->get_param('user_id');
        $salary_data = array(
            'user_id' => $user_id,
            'monthly_salary' => $request->get_param('monthly_salary'),
            'working_days_per_month' => $request->get_param('working_days_per_month'),
            'currency' => $request->get_param('currency'),
            'effective_from' => $request->get_param('effective_from'),
            'effective_to' => $request->get_param('effective_to')
        );

        try {
            $result = $this->salary_service->set_salary_settings($salary_data);
            
            if (is_wp_error($result)) {
                return $this->handle_error($result);
            }

            return new WP_REST_Response(
                array(
                    'success' => true,
                    'data' => array(
                        'message' => __('Salary settings updated successfully', 'bassmah-staff-reports'),
                        'settings_id' => $result
                    )
                ),
                200
            );

        } catch (Exception $e) {
            return $this->handle_error($e);
        }
    }

    /**
     * Get salary history for a user
     *
     * @since    1.0.0
     * @param    WP_REST_Request    $request    Full data about the request
     * @return   WP_REST_Response|WP_Error
     */
    public function get_salary_history($request) {
        $user_id = $request->get_param('user_id');
        $limit = $request->get_param('limit', 12);

        try {
            $history = $this->salary_service->get_salary_history($user_id, $limit);
            
            return new WP_REST_Response(
                array(
                    'success' => true,
                    'data' => $history
                ),
                200
            );

        } catch (Exception $e) {
            return $this->handle_error($e);
        }
    }

    /**
     * Get dashboard statistics for a user
     *
     * @since    1.0.0
     * @param    WP_REST_Request    $request    Full data about the request
     * @return   WP_REST_Response|WP_Error
     */
    public function get_dashboard_statistics($request) {
        $user_id = $request->get_param('user_id');

        try {
            $permission_check = $this->security->validate_resource_access($user_id, 'salary');
            if (is_wp_error($permission_check)) {
                return $permission_check;
            }

            $dashboard_stats = $this->salary_service->get_dashboard_stats($user_id);
            
            return new WP_REST_Response(
                array(
                    'success' => true,
                    'data' => $dashboard_stats
                ),
                200
            );

        } catch (Exception $e) {
            return $this->handle_error($e);
        }
    }

    /**
     * Get batch salary summary for multiple users
     *
     * @since    1.0.0
     * @param    WP_REST_Request    $request    Full data about the request
     * @return   WP_REST_Response|WP_Error
     */
    public function get_batch_salary_summary($request) {
        $user_ids = $request->get_param('user_ids');
        $month = $request->get_param('month');

        try {
            $summaries = $this->salary_service->get_batch_salary_summary($user_ids, $month);
            
            return new WP_REST_Response(
                array(
                    'success' => true,
                    'data' => $summaries
                ),
                200
            );

        } catch (Exception $e) {
            return $this->handle_error($e);
        }
    }

    /**
     * Export salary data
     *
     * @since    1.0.0
     * @param    WP_REST_Request    $request    Full data about the request
     * @return   WP_REST_Response|WP_Error
     */
    public function export_salary_data($request) {
        $user_ids = $request->get_param('user_ids');
        $month = $request->get_param('month');
        $format = $request->get_param('format', 'csv');

        try {
            $export_data = $this->salary_service->export_salary_data($user_ids, $month, $format);
            
            if (is_wp_error($export_data)) {
                return $this->handle_error($export_data);
            }

            $filename = sprintf('salary-export-%s.%s', $month, $format);
            
            return new WP_REST_Response(
                array(
                    'success' => true,
                    'data' => array(
                        'content' => $export_data,
                        'filename' => $filename,
                        'format' => $format
                    )
                ),
                200
            );

        } catch (Exception $e) {
            return $this->handle_error($e);
        }
    }

    /**
     * Recalculate monthly salaries
     *
     * @since    1.0.0
     * @param    WP_REST_Request    $request    Full data about the request
     * @return   WP_REST_Response|WP_Error
     */
    public function recalculate_monthly_salaries($request) {
        $month = $request->get_param('month');

        try {
            $result = $this->salary_service->recalculate_monthly_salaries($month);
            
            return new WP_REST_Response(
                array(
                    'success' => true,
                    'data' => $result,
                    'message' => sprintf(
                        __('Salary recalculation completed for %s', 'bassmah-staff-reports'),
                        $month
                    )
                ),
                200
            );

        } catch (Exception $e) {
            return $this->handle_error($e);
        }
    }

    /**
     * Permission check for get my salary
     *
     * @since    1.0.0
     * @return   bool|WP_Error
     */
    public function get_my_salary_permissions_check() {
        return $this->security->check_capability('bassmah_view_own_salary');
    }

    /**
     * Permission check for get user salary
     *
     * @since    1.0.0
     * @return   bool|WP_Error
     */
    public function get_user_salary_permissions_check($request) {
        $user_id = $request->get_param('user_id');
        return $this->security->validate_resource_access($user_id, 'salary');
    }

    /**
     * Permission check for update user salary
     *
     * @since    1.0.0
     * @return   bool|WP_Error
     */
    public function update_user_salary_permissions_check($request) {
        return $this->security->check_capability('bassmah_manage_salary_settings');
    }

    /**
     * Permission check for get salary history
     *
     * @since    1.0.0
     * @return   bool|WP_Error
     */
    public function get_salary_history_permissions_check($request) {
        $user_id = $request->get_param('user_id');
        return $this->security->validate_resource_access($user_id, 'salary');
    }

    /**
     * Permission check for get dashboard
     *
     * @since    1.0.0
     * @return   bool|WP_Error
     */
    public function get_dashboard_permissions_check($request) {
        $user_id = $request->get_param('user_id');
        return $this->security->validate_resource_access($user_id, 'salary');
    }

    /**
     * Permission check for get batch salary
     *
     * @since    1.0.0
     * @return   bool|WP_Error
     */
    public function get_batch_salary_permissions_check() {
        return $this->security->check_capability('bassmah_view_all_salary');
    }

    /**
     * Permission check for export salary
     *
     * @since    1.0.0
     * @return   bool|WP_Error
     */
    public function export_salary_permissions_check() {
        return $this->security->check_capability('bassmah_export_reports');
    }

    /**
     * Permission check for recalculate salary
     *
     * @since    1.0.0
     * @return   bool|WP_Error
     */
    public function recalculate_salary_permissions_check() {
        return $this->security->check_capability('bassmah_manage_salary_settings');
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
     * Validate month format
     *
     * @since    1.0.0
     * @param    mixed    $value    Value to validate
     * @param    WP_REST_Request    $request    Request object
     * @param    string    $param    Parameter name
     * @return   bool|WP_Error
     */
    public function validate_month_format($value, $request, $param) {
        if (!DateTime::createFromFormat('Y-m', $value)) {
            return new WP_Error(
                'invalid_month_format',
                __('Month must be in Y-m format', 'bassmah-staff-reports'),
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
     * Validate monetary amount
     *
     * @since    1.0.0
     * @param    mixed    $value    Value to validate
     * @param    WP_REST_Request    $request    Request object
     * @param    string    $param    Parameter name
     * @return   bool|WP_Error
     */
    public function validate_monetary_amount($value, $request, $param) {
        if (!is_numeric($value) || $value < 0) {
            return new WP_Error(
                'invalid_amount',
                __('Amount must be a positive number', 'bassmah-staff-reports'),
                array('status' => 400)
            );
        }
        return true;
    }

    /**
     * Validate working days
     *
     * @since    1.0.0
     * @param    mixed    $value    Value to validate
     * @param    WP_REST_Request    $request    Request object
     * @param    string    $param    Parameter name
     * @return   bool|WP_Error
     */
    public function validate_working_days($value, $request, $param) {
        if (!is_numeric($value) || $value < 1 || $value > 31) {
            return new WP_Error(
                'invalid_working_days',
                __('Working days must be between 1 and 31', 'bassmah-staff-reports'),
                array('status' => 400)
            );
        }
        return true;
    }

    /**
     * Validate currency
     *
     * @since    1.0.0
     * @param    mixed    $value    Value to validate
     * @param    WP_REST_Request    $request    Request object
     * @param    string    $param    Parameter name
     * @return   bool|WP_Error
     */
    public function validate_currency($value, $request, $param) {
        $valid_currencies = array('CAD', 'USD', 'EUR', 'GBP');
        
        if (!in_array($value, $valid_currencies)) {
            return new WP_Error(
                'invalid_currency',
                __('Invalid currency code', 'bassmah-staff-reports'),
                array('status' => 400)
            );
        }
        return true;
    }

    /**
     * Validate date not in future
     *
     * @since    1.0.0
     * @param    mixed    $value    Value to validate
     * @param    WP_REST_Request    $request    Request object
     * @param    string    $param    Parameter name
     * @return   bool|WP_Error
     */
    public function validate_date_not_future($value, $request, $param) {
        $date = new DateTime($value);
        $today = new DateTime();
        
        if ($date > $today) {
            return new WP_Error(
                'future_date',
                __('Date cannot be in the future', 'bassmah-staff-reports'),
                array('status' => 400)
            );
        }
        return true;
    }

    /**
     * Validate date
     *
     * @since    1.0.0
     * @param    mixed    $value    Value to validate
     * @param    WP_REST_Request    $request    Request object
     * @param    string    $param    Parameter name
     * @return   bool|WP_Error
     */
    public function validate_date($value, $request, $param) {
        $date = DateTime::createFromFormat('Y-m-d', $value);
        
        if (!$date || $date->format('Y-m-d') !== $value) {
            return new WP_Error(
                'invalid_date_format',
                __('Date must be in Y-m-d format', 'bassmah-staff-reports'),
                array('status' => 400)
            );
        }
        return true;
    }

    /**
     * Validate user IDs array
     *
     * @since    1.0.0
     * @param    mixed    $value    Value to validate
     * @param    WP_REST_Request    $request    Request object
     * @param    string    $param    Parameter name
     * @return   bool|WP_Error
     */
    public function validate_user_ids_array($value, $request, $param) {
        if (!is_array($value)) {
            return new WP_Error(
                'invalid_user_ids',
                __('User IDs must be an array', 'bassmah-staff-reports'),
                array('status' => 400)
            );
        }

        if (empty($value)) {
            return new WP_Error(
                'empty_user_ids',
                __('At least one user ID is required', 'bassmah-staff-reports'),
                array('status' => 400)
            );
        }

        foreach ($value as $user_id) {
            if (!is_numeric($user_id) || $user_id <= 0) {
                return new WP_Error(
                    'invalid_user_id_in_array',
                    __('All user IDs must be positive integers', 'bassmah-staff-reports'),
                    array('status' => 400)
                );
            }
        }

        return true;
    }

    /**
     * Validate export format
     *
     * @since    1.0.0
     * @param    mixed    $value    Value to validate
     * @param    WP_REST_Request    $request    Request object
     * @param    string    $param    Parameter name
     * @return   bool|WP_Error
     */
    public function validate_export_format($value, $request, $param) {
        $valid_formats = array('csv', 'excel');
        
        if (!in_array($value, $valid_formats)) {
            return new WP_Error(
                'invalid_export_format',
                __('Export format must be csv or excel', 'bassmah-staff-reports'),
                array('status' => 400)
            );
        }
        return true;
    }
}
