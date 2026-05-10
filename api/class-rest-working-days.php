<?php
/**
 * REST API Working Days Controller
 *
 * Handles working days management for holidays and business days
 * with proper authentication, validation, and audit logging.
 *
 * @since      1.0.0
 * @package    Bassmah_Staff_Reports
 * @author     Tumit <tumit@bassmah.ca>
 */

// Include Database Manager class
require_once plugin_dir_path(__FILE__) . '../includes/class-database-manager.php';

if (!class_exists('Bassmah_Staff_Reports_REST_Working_Days')) {
    class Bassmah_Staff_Reports_REST_Working_Days {

        /**
         * Database service for working days operations.
         *
         * @var Bassmah_Staff_Reports_Database_Manager
         */
        private $database_service;

        /**
         * Constructor
         */
        public function __construct() {
            $this->database_service = new Bassmah_Staff_Reports_Database_Manager();
        }

        /**
         * Register REST API routes
         *
         * @since    1.0.0
         */
        public function register_routes() {
            register_rest_route('bassmah/v1', '/working-days', array(
                array(
                    'methods' => WP_REST_Server::READABLE,
                    'callback' => array($this, 'get_working_days'),
                    'permission_callback' => array($this, 'get_working_days_permissions_check'),
                    'args' => array(
                        'date_from' => array(
                            'required' => false,
                            'type' => 'string',
                            'description' => __('Start date', 'bassmah-staff-reports'),
                            'sanitize_callback' => 'sanitize_text_field'
                        ),
                        'date_to' => array(
                            'required' => false,
                            'type' => 'string',
                            'description' => __('End date', 'bassmah-staff-reports'),
                            'sanitize_callback' => 'sanitize_text_field'
                        ),
                        'is_holiday' => array(
                            'required' => false,
                            'type' => 'boolean',
                            'description' => __('Filter by holiday status', 'bassmah-staff-reports')
                        )
                    )
                ),
                array(
                    'methods' => WP_REST_Server::CREATABLE,
                    'callback' => array($this, 'create_working_day'),
                    'permission_callback' => array($this, 'create_working_day_permissions_check'),
                    'args' => array(
                        'work_date' => array(
                            'required' => true,
                            'type' => 'string',
                            'description' => __('Work date', 'bassmah-staff-reports'),
                            'sanitize_callback' => 'sanitize_text_field'
                        ),
                        'is_holiday' => array(
                            'required' => true,
                            'type' => 'boolean',
                            'description' => __('Is holiday', 'bassmah-staff-reports')
                        ),
                        'holiday_name' => array(
                            'required' => false,
                            'type' => 'string',
                            'description' => __('Holiday name', 'bassmah-staff-reports'),
                            'sanitize_callback' => 'sanitize_text_field'
                        )
                    )
                )
            ));

            register_rest_route('bassmah/v1', '/working-days/(?P<id>\d+)', array(
                array(
                    'methods' => WP_REST_Server::EDITABLE,
                    'callback' => array($this, 'update_working_day'),
                    'permission_callback' => array($this, 'update_working_day_permissions_check'),
                    'args' => array(
                        'is_holiday' => array(
                            'required' => false,
                            'type' => 'boolean',
                            'description' => __('Is holiday', 'bassmah-staff-reports')
                        ),
                        'holiday_name' => array(
                            'required' => false,
                            'type' => 'string',
                            'description' => __('Holiday name', 'bassmah-staff-reports'),
                            'sanitize_callback' => 'sanitize_text_field'
                        )
                    )
                ),
                array(
                    'methods' => WP_REST_Server::DELETABLE,
                    'callback' => array($this, 'delete_working_day'),
                    'permission_callback' => array($this, 'delete_working_day_permissions_check')
                )
            ));
        }

        /**
         * Check permissions for getting working days
         *
         * @since    1.0.0
         * @param    WP_REST_Request    $request    Full data about the request.
         * @return   WP_Error|bool
         */
        public function get_working_days_permissions_check($request) {
            $login_check = Bassmah_Staff_Reports_Roles::require_login();
            if (is_wp_error($login_check)) {
                return $login_check;
            }
            return current_user_can('bassmah_view_all_reports');
        }

        /**
         * Check permissions for creating working day
         *
         * @since    1.0.0
         * @param    WP_REST_Request    $request    Full data about the request.
         * @return   WP_Error|bool
         */
        public function create_working_day_permissions_check($request) {
            $login_check = Bassmah_Staff_Reports_Roles::require_login();
            if (is_wp_error($login_check)) {
                return $login_check;
            }
            return current_user_can('bassmah_manage_settings');
        }

        /**
         * Check permissions for updating working day
         *
         * @since    1.0.0
         * @param    WP_REST_Request    $request    Full data about the request.
         * @return   WP_Error|bool
         */
        public function update_working_day_permissions_check($request) {
            return $this->create_working_day_permissions_check($request);
        }

        /**
         * Check permissions for deleting working day
         *
         * @since    1.0.0
         * @param    WP_REST_Request    $request    Full data about the request.
         * @return   WP_Error|bool
         */
        public function delete_working_day_permissions_check($request) {
            return $this->create_working_day_permissions_check($request);
        }

        /**
         * Get working days
         *
         * @since    1.0.0
         * @param    WP_REST_Request    $request    Full data about the request.
         * @return   WP_REST_Response|WP_Error
         */
        public function get_working_days($request) {
            $date_from = $request->get_param('date_from');
            $date_to = $request->get_param('date_to');
            $is_holiday = $request->get_param('is_holiday');

            $args = array();
            if ($date_from) {
                $args['date_from'] = $date_from;
            }
            if ($date_to) {
                $args['date_to'] = $date_to;
            }
            if ($is_holiday !== null) {
                $args['is_holiday'] = $is_holiday;
            }

            $working_days = $this->database_service->get_working_days($args);

            $response_data = array();
            foreach ($working_days as $day) {
                $response_data[] = array(
                    'id' => $day->id,
                    'work_date' => $day->work_date,
                    'is_holiday' => $day->is_holiday,
                    'holiday_name' => $day->holiday_name,
                    'created_by' => $day->created_by,
                    'created_at' => $day->created_at
                );
            }

            return new WP_REST_Response($response_data, 200);
        }

        /**
         * Create working day
         *
         * @since    1.0.0
         * @param    WP_REST_Request    $request    Full data about the request.
         * @return   WP_REST_Response|WP_Error
         */
        public function create_working_day($request) {
            $work_date = $request->get_param('work_date');
            $is_holiday = $request->get_param('is_holiday');
            $holiday_name = $request->get_param('holiday_name');

            // Validate date format
            if (!DateTime::createFromFormat('Y-m-d', $work_date)) {
                return new WP_Error(
                    'invalid_date',
                    __('Invalid date format. Use Y-m-d format.', 'bassmah-staff-reports'),
                    array('status' => 400)
                );
            }

            $data = array(
                'work_date' => $work_date,
                'is_holiday' => $is_holiday,
                'holiday_name' => $is_holiday ? $holiday_name : '',
                'created_by' => get_current_user_id()
            );

            $result = $this->database_service->create_working_day($data);

            if (is_wp_error($result)) {
                return $result;
            }

            $response_data = array(
                'id' => $result,
                'work_date' => $work_date,
                'is_holiday' => $is_holiday,
                'holiday_name' => $holiday_name,
                'created_by' => get_current_user_id(),
                'created_at' => current_time('mysql')
            );

            return new WP_REST_Response($response_data, 201);
        }

        /**
         * Update working day
         *
         * @since    1.0.0
         * @param    WP_REST_Request    $request    Full data about the request.
         * @return   WP_REST_Response|WP_Error
         */
        public function update_working_day($request) {
            $id = $request->get_param('id');
            $is_holiday = $request->get_param('is_holiday');
            $holiday_name = $request->get_param('holiday_name');

            $data = array();
            if ($is_holiday !== null) {
                $data['is_holiday'] = $is_holiday;
            }
            if ($holiday_name !== null) {
                $data['holiday_name'] = $holiday_name;
            }

            $result = $this->database_service->update_working_day($id, $data);

            if (is_wp_error($result)) {
                return $result;
            }

            // Get updated working day
            $working_day = $this->database_service->get_working_day($id);
            if (!$working_day) {
                return new WP_Error(
                    'working_day_not_found',
                    __('Working day not found.', 'bassmah-staff-reports'),
                    array('status' => 404)
                );
            }

            $response_data = array(
                'id' => $working_day->id,
                'work_date' => $working_day->work_date,
                'is_holiday' => $working_day->is_holiday,
                'holiday_name' => $working_day->holiday_name,
                'created_by' => $working_day->created_by,
                'created_at' => $working_day->created_at
            );

            return new WP_REST_Response($response_data, 200);
        }

        /**
         * Delete working day
         *
         * @since    1.0.0
         * @param    WP_REST_Request    $request    Full data about the request.
         * @return   WP_REST_Response|WP_Error
         */
        public function delete_working_day($request) {
            $id = $request->get_param('id');

            $result = $this->database_service->delete_working_day($id);

            if (is_wp_error($result)) {
                return $result;
            }

            return new WP_REST_Response(array('deleted' => true), 200);
        }
    }
}
