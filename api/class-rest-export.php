<?php
/**
 * REST API Export Controller
 *
 * Handles report export functionality via REST API.
 *
 * @since      1.0.0
 * @package    Bassmah_Staff_Reports
 * @author     Tumit <tumit@bassmah.ca>
 */

if (!class_exists('Bassmah_Staff_Reports_REST_Export')) {
    class Bassmah_Staff_Reports_REST_Export {

        /**
         * Register REST API routes for export
         *
         * @since    1.0.0
         */
        public function register_routes() {
            // POST /reports/export - Export reports
            register_rest_route('bassmah/v1', '/reports/export', array(
                array(
                    'methods' => WP_REST_Server::CREATABLE,
                    'callback' => array($this, 'export_reports'),
                    'permission_callback' => array($this, 'export_permission_check'),
                    'args' => array(
                        'format' => array(
                            'required' => false,
                            'type' => 'string',
                            'enum' => array('csv', 'excel'),
                            'description' => __('Export format', 'bassmah-staff-reports'),
                            'sanitize_callback' => 'sanitize_text_field'
                        ),
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
                        'user_id' => array(
                            'required' => false,
                            'type' => 'integer',
                            'description' => __('User ID', 'bassmah-staff-reports'),
                            'sanitize_callback' => 'intval'
                        ),
                        'status' => array(
                            'required' => false,
                            'type' => 'string',
                            'enum' => array('submitted', 'approved', 'rejected'),
                            'description' => __('Report status', 'bassmah-staff-reports'),
                            'sanitize_callback' => 'sanitize_text_field'
                        )
                    )
                )
            ));
        }

        /**
         * Check export permissions
         *
         * @since    1.0.0
         * @return    bool|WP_Error
         */
        public function export_permission_check() {
            $login_check = Bassmah_Staff_Reports_Roles::require_login();
            if (is_wp_error($login_check)) {
                return $login_check;
            }
            return current_user_can('bassmah_export_reports');
        }

        /**
         * Export reports endpoint
         *
         * @since    1.0.0
         * @param    WP_REST_Request    $request    REST request
         * @return   WP_REST_Response
         */
        public function export_reports($request) {
            $format = $request->get_param('format', 'csv');
            $filters = array(
                'date_from' => $request->get_param('date_from'),
                'date_to' => $request->get_param('date_to'),
                'user_id' => $request->get_param('user_id'),
                'status' => $request->get_param('status')
            );

            if (!class_exists('Bassmah_Staff_Reports_Exporter')) {
                require_once BASSMAH_STAFF_REPORTS_PLUGIN_DIR . 'includes/class-exporter.php';
            }

            $exporter = new Bassmah_Staff_Reports_Exporter();
            
            if ($format === 'excel') {
                $content = $exporter->export_reports_to_excel($filters);
                $filename = 'reports-' . date('Y-m-d') . '.xlsx';
                $content_type = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
            } else {
                $content = $exporter->export_reports_to_csv($filters);
                $filename = 'reports-' . date('Y-m-d') . '.csv';
                $content_type = 'text/csv';
            }

            // Set headers for download
            header('Content-Type: ' . $content_type);
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Cache-Control: no-cache, must-revalidate');
            header('Pragma: no-cache');
            header('Expires: 0');

            echo $content;
            exit;
        }
    }
}
