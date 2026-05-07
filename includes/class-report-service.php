<?php
/**
 * Report Service - Business Logic for Reports
 *
 * Handles all report-related business operations including
 * creation, validation, retrieval, and management.
 *
 * @since      1.0.0
 * @package    Bassmah_Staff_Reports
 * @author     Tumit <tumit@bassmah.ca>
 */
class Bassmah_Staff_Reports_Report_Service {

    /**
     * Database manager instance
     *
     * @since    1.0.0
     * @access   private
     * @var      Bassmah_Staff_Reports_Database_Manager    $database    Database manager
     */
    private $database;

    /**
     * Cache manager instance
     *
     * @since    1.0.0
     * @access   private
     * @var      Bassmah_Staff_Reports_Cache_Manager    $cache    Cache manager
     */
    private $cache;

    /**
     * Security manager instance
     *
     * @since    1.0.0
     * @access   private
     * @var      Bassmah_Staff_Reports_Security_Manager    $security    Security manager
     */
    private $security;

    /**
     * Validation manager instance
     *
     * @since    1.0.0
     * @access   private
     * @var      Bassmah_Staff_Reports_Validation_Manager    $validation    Validation manager
     */
    private $validation;

    /**
     * Constructor
     *
     * @since    1.0.0
     * @param    Bassmah_Staff_Reports_Database_Manager    $database    Database manager
     * @param    Bassmah_Staff_Reports_Cache_Manager       $cache       Cache manager
     * @param    Bassmah_Staff_Reports_Security_Manager    $security    Security manager
     * @param    Bassmah_Staff_Reports_Validation_Manager  $validation  Validation manager
     */
    public function __construct($database, $cache, $security, $validation) {
        $this->database = $database;
        $this->cache = $cache;
        $this->security = $security;
        $this->validation = $validation;
    }

    /**
     * Create a new report
     *
     * @since    1.0.0
     * @param    array    $data    Report data
     * @return   int|WP_Error
     */
    public function create_report($data) {
        // Validate input data
        $validation_result = $this->validation->validate_report($data);
        if (is_wp_error($validation_result)) {
            return $validation_result;
        }

        // Check permissions
        $permission_check = $this->security->check_capability('bassmah_submit_reports', $data['user_id']);
        if (is_wp_error($permission_check)) {
            return $permission_check;
        }

        // Check for duplicate report
        $existing = $this->get_report_by_date($data['user_id'], $data['report_date']);
        if ($existing) {
            return new WP_Error(
                'duplicate_report',
                __('A report has already been submitted for this date.', 'bassmah-staff-reports'),
                array('status' => 409)
            );
        }

        // Prepare report data
        $report_data = array(
            'user_id' => $data['user_id'],
            'report_date' => $data['report_date'],
            'submission_time' => current_time('mysql'),
            'status' => $data['status'] ?? 'submitted',
            'tasks_json' => wp_json_encode($data['tasks']),
            'ip_address' => $this->security->get_client_ip(),
            'user_agent' => $this->security->get_user_agent(),
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql')
        );

        // Insert report
        $this->database->start_transaction();
        
        try {
            $report_id = $this->database->insert('reports', $report_data);
            
            if (!$report_id) {
                $this->database->rollback();
                return new WP_Error(
                    'database_error',
                    __('Failed to create report.', 'bassmah-staff-reports'),
                    array('status' => 500)
                );
            }

            // Clear cache
            $this->cache->delete("user_reports_{$data['user_id']}", 'reports');
            $this->cache->delete("report_{$data['user_id']}_{$data['report_date']}", 'reports');

            $this->database->commit();

            // Trigger actions
            do_action('bassmah_report_created', $report_id, $report_data);
            do_action('bassmah_report_submitted', $report_id, $report_data);

            return $report_id;

        } catch (Exception $e) {
            $this->database->rollback();
            return new WP_Error(
                'exception',
                $e->getMessage(),
                array('status' => 500)
            );
        }
    }

    /**
     * Get a single report
     *
     * @since    1.0.0
     * @param    int    $report_id    Report ID
     * @param    bool   $include_tasks  Whether to include tasks
     * @return   object|WP_Error
     */
    public function get_report($report_id, $include_tasks = true) {
        // Check cache first
        $cache_key = "report_{$report_id}";
        $cached_report = $this->cache->get($cache_key, 'reports');
        
        if ($cached_report) {
            return $cached_report;
        }

        // Get from database
        $report = $this->database->get_row(
            "SELECT * FROM {$this->database->get_table_name('reports')} WHERE id = %d",
            array($report_id)
        );

        if (!$report) {
            return new WP_Error(
                'report_not_found',
                __('Report not found.', 'bassmah-staff-reports'),
                array('status' => 404)
            );
        }

        // Check permissions
        $permission_check = $this->security->validate_resource_access($report_id, 'report');
        if (is_wp_error($permission_check)) {
            return $permission_check;
        }

        // Parse tasks if requested
        if ($include_tasks) {
            $report->tasks = json_decode($report->tasks_json, true);
        }

        // Cache the result
        $this->cache->set($cache_key, $report, 'reports', 3600);

        return $report;
    }

    /**
     * Get reports with filtering
     *
     * @since    1.0.0
     * @param    array    $args    Query arguments
     * @return   array|WP_Error
     */
    public function get_reports($args = array()) {
        // Sanitize and validate arguments
        $sanitized_args = $this->sanitize_query_args($args);

        // Build cache key
        $cache_key = 'reports_' . md5(serialize($sanitized_args));
        $cached_reports = $this->cache->get($cache_key, 'reports');
        
        if ($cached_reports) {
            return $cached_reports;
        }

        // Check permissions
        if (!empty($sanitized_args['user_id'])) {
            $permission_check = $this->security->validate_resource_access($sanitized_args['user_id'], 'report');
            if (is_wp_error($permission_check)) {
                return $permission_check;
            }
        } else {
            $permission_check = $this->security->check_capability('bassmah_view_all_reports');
            if (is_wp_error($permission_check)) {
                return $permission_check;
            }
        }

        // Build query
        $where_conditions = array();
        $query_params = array();

        if (!empty($sanitized_args['user_id'])) {
            $where_conditions[] = "user_id = %d";
            $query_params[] = $sanitized_args['user_id'];
        }

        if (!empty($sanitized_args['status'])) {
            $where_conditions[] = "status = %s";
            $query_params[] = $sanitized_args['status'];
        }

        if (!empty($sanitized_args['date_from'])) {
            $where_conditions[] = "report_date >= %s";
            $query_params[] = $sanitized_args['date_from'];
        }

        if (!empty($sanitized_args['date_to'])) {
            $where_conditions[] = "report_date <= %s";
            $query_params[] = $sanitized_args['date_to'];
        }

        $where_clause = '';
        if (!empty($where_conditions)) {
            $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
        }

        $order_by = $sanitized_args['orderby'] ?? 'submission_time';
        $order = $sanitized_args['order'] ?? 'DESC';
        $limit = intval($sanitized_args['limit'] ?? 50);
        $offset = intval($sanitized_args['offset'] ?? 0);

        $query = "
            SELECT * FROM {$this->database->get_table_name('reports')} 
            {$where_clause}
            ORDER BY {$order_by} {$order}
            LIMIT %d OFFSET %d
        ";

        $query_params[] = $limit;
        $query_params[] = $offset;

        $reports = $this->database->get_results($query, $query_params);

        // Parse tasks for each report
        foreach ($reports as $report) {
            $report->tasks = json_decode($report->tasks_json, true);
        }

        // Cache the results
        $this->cache->set($cache_key, $reports, 'reports', 1800);

        return $reports;
    }

    /**
     * Get user's reports
     *
     * @since    1.0.0
     * @param    int    $user_id    User ID
     * @param    array  $args      Additional arguments
     * @return   array|WP_Error
     */
    public function get_user_reports($user_id, $args = array()) {
        $args['user_id'] = $user_id;
        return $this->get_reports($args);
    }

    /**
     * Get report by user and date
     *
     * @since    1.0.0
     * @param    int     $user_id    User ID
     * @param    string  $date       Date (Y-m-d)
     * @return   object|null
     */
    public function get_report_by_date($user_id, $date) {
        $cache_key = "report_{$user_id}_{$date}";
        $cached_report = $this->cache->get($cache_key, 'reports');
        
        if ($cached_report) {
            return $cached_report;
        }

        $report = $this->database->get_row(
            "SELECT * FROM {$this->database->get_table_name('reports')} 
             WHERE user_id = %d AND report_date = %s",
            array($user_id, $date)
        );

        if ($report) {
            $report->tasks = json_decode($report->tasks_json, true);
            $this->cache->set($cache_key, $report, 'reports', 3600);
        }

        return $report;
    }

    /**
     * Update a report
     *
     * @since    1.0.0
     * @param    int    $report_id    Report ID
     * @param    array  $data         Update data
     * @return   bool|WP_Error
     */
    public function update_report($report_id, $data) {
        // Get existing report
        $existing_report = $this->get_report($report_id, false);
        if (is_wp_error($existing_report)) {
            return $existing_report;
        }

        // Check permissions
        $permission_check = $this->security->validate_resource_access($report_id, 'report');
        if (is_wp_error($permission_check)) {
            return $permission_check;
        }

        // Prepare update data
        $update_data = array('updated_at' => current_time('mysql'));

        if (isset($data['status'])) {
            $update_data['status'] = $data['status'];
        }

        if (isset($data['manager_comment'])) {
            $update_data['manager_comment'] = $data['manager_comment'];
            $update_data['manager_id'] = get_current_user_id();
        }

        if (isset($data['tasks'])) {
            $update_data['tasks_json'] = wp_json_encode($data['tasks']);
        }

        $this->database->start_transaction();
        
        try {
            $result = $this->database->update(
                'reports',
                $update_data,
                array('id' => $report_id)
            );

            if ($result === false) {
                $this->database->rollback();
                return new WP_Error(
                    'database_error',
                    __('Failed to update report.', 'bassmah-staff-reports'),
                    array('status' => 500)
                );
            }

            // Clear cache
            $this->cache->delete("report_{$report_id}", 'reports');
            $this->cache->delete("user_reports_{$existing_report->user_id}", 'reports');

            $this->database->commit();

            // Trigger actions
            do_action('bassmah_report_updated', $report_id, $update_data);

            return true;

        } catch (Exception $e) {
            $this->database->rollback();
            return new WP_Error(
                'exception',
                $e->getMessage(),
                array('status' => 500)
            );
        }
    }

    /**
     * Add comment to report
     *
     * @since    1.0.0
     * @param    int    $report_id    Report ID
     * @param    string $comment     Comment text
     * @return   bool|WP_Error
     */
    public function add_comment($report_id, $comment) {
        // Check permissions
        $permission_check = $this->security->check_capability('bassmah_comment_reports');
        if (is_wp_error($permission_check)) {
            return $permission_check;
        }

        // Validate comment
        if (empty(trim($comment))) {
            return new WP_Error(
                'empty_comment',
                __('Comment cannot be empty.', 'bassmah-staff-reports'),
                array('status' => 400)
            );
        }

        return $this->update_report($report_id, array(
            'manager_comment' => $comment
        ));
    }

    /**
     * Delete a report
     *
     * @since    1.0.0
     * @param    int    $report_id    Report ID
     * @return   bool|WP_Error
     */
    public function delete_report($report_id) {
        // Get existing report
        $existing_report = $this->get_report($report_id, false);
        if (is_wp_error($existing_report)) {
            return $existing_report;
        }

        // Check permissions
        $permission_check = $this->security->validate_resource_access($report_id, 'report');
        if (is_wp_error($permission_check)) {
            return $permission_check;
        }

        $this->database->start_transaction();
        
        try {
            $result = $this->database->delete(
                'reports',
                array('id' => $report_id)
            );

            if ($result === false) {
                $this->database->rollback();
                return new WP_Error(
                    'database_error',
                    __('Failed to delete report.', 'bassmah-staff-reports'),
                    array('status' => 500)
                );
            }

            // Clear cache
            $this->cache->delete("report_{$report_id}", 'reports');
            $this->cache->delete("user_reports_{$existing_report->user_id}", 'reports');

            $this->database->commit();

            // Trigger actions
            do_action('bassmah_report_deleted', $report_id, $existing_report);

            return true;

        } catch (Exception $e) {
            $this->database->rollback();
            return new WP_Error(
                'exception',
                $e->getMessage(),
                array('status' => 500)
            );
        }
    }

    /**
     * Get report statistics
     *
     * @since    1.0.0
     * @param    array    $args    Query arguments
     * @return   array|WP_Error
     */
    public function get_statistics($args = array()) {
        // Check permissions
        $permission_check = $this->security->check_capability('bassmah_view_all_reports');
        if (is_wp_error($permission_check)) {
            return $permission_check;
        }

        $date_from = $args['date_from'] ?? date('Y-m-01');
        $date_to = $args['date_to'] ?? date('Y-m-t');
        $user_id = $args['user_id'] ?? null;

        $where_conditions = array("report_date BETWEEN %s AND %s");
        $query_params = array($date_from, $date_to);

        if ($user_id) {
            $where_conditions[] = "user_id = %d";
            $query_params[] = $user_id;
        }

        $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);

        $query = "
            SELECT 
                COUNT(*) as total_reports,
                COUNT(DISTINCT user_id) as unique_users,
                COUNT(CASE WHEN status = 'submitted' THEN 1 END) as submitted_reports,
                COUNT(CASE WHEN status = 'approved' THEN 1 END) as approved_reports,
                COUNT(CASE WHEN status = 'rejected' THEN 1 END) as rejected_reports
            FROM {$this->database->get_table_name('reports')}
            {$where_clause}
        ";

        return $this->database->get_row($query, $query_params);
    }

    /**
     * Get today's reports
     *
     * @since    1.0.0
     * @return   array
     */
    public function get_today_reports() {
        $today = current_time('Y-m-d');
        
        return $this->get_reports(array(
            'date_from' => $today,
            'date_to' => $today,
            'orderby' => 'submission_time',
            'order' => 'DESC'
        ));
    }

    /**
     * Get missing reports for today
     *
     * @since    1.0.0
     * @return   array
     */
    public function get_missing_reports_today() {
        // Get all staff users
        $staff_users = get_users(array(
            'role__in' => array('bassmah_staff', 'subscriber'),
            'fields' => array('ID', 'display_name', 'user_email')
        ));

        $today = current_time('Y-m-d');
        $missing_users = array();

        foreach ($staff_users as $user) {
            $report = $this->get_report_by_date($user->ID, $today);
            if (!$report) {
                $missing_users[] = $user;
            }
        }

        return $missing_users;
    }

    /**
     * Sanitize query arguments
     *
     * @since    1.0.0
     * @param    array    $args    Raw arguments
     * @return   array
     */
    private function sanitize_query_args($args) {
        $sanitized = array();

        $allowed_args = array(
            'user_id' => 'intval',
            'status' => 'sanitize_text_field',
            'date_from' => 'sanitize_text_field',
            'date_to' => 'sanitize_text_field',
            'orderby' => 'sanitize_sql_orderby',
            'order' => 'sanitize_key',
            'limit' => 'intval',
            'offset' => 'intval'
        );

        foreach ($allowed_args as $arg => $sanitization_method) {
            if (isset($args[$arg])) {
                $sanitized[$arg] = call_user_func($sanitization_method, $args[$arg]);
            }
        }

        return $sanitized;
    }

    /**
     * Get report count for user
     *
     * @since    1.0.0
     * @param    int    $user_id    User ID
     * @return   int
     */
    public function get_user_report_count($user_id) {
        $cache_key = "report_count_{$user_id}";
        $cached_count = $this->cache->get($cache_key, 'reports');
        
        if ($cached_count !== null) {
            return $cached_count;
        }

        $count = $this->database->get_var(
            "SELECT COUNT(*) FROM {$this->database->get_table_name('reports')} WHERE user_id = %d",
            array($user_id)
        );

        $this->cache->set($cache_key, $count, 'reports', 3600);

        return intval($count);
    }

    /**
     * Get recent reports for dashboard
     *
     * @since    1.0.0
     * @param    int    $limit    Number of reports
     * @return   array
     */
    public function get_recent_reports($limit = 10) {
        return $this->get_reports(array(
            'limit' => $limit,
            'orderby' => 'submission_time',
            'order' => 'DESC'
        ));
    }

    /**
     * Approve a submitted report
     *
     * @since    1.0.0
     * @param    int     $report_id   Report ID
     * @param    int     $manager_id  Manager user ID
     * @param    string  $comment     Approval comment (optional)
     * @return   int|WP_Error
     */
    public function approve_report($report_id, $manager_id, $comment = '') {
        // Get the report
        $report = $this->get_report($report_id, false);
        if (is_wp_error($report)) {
            return $report;
        }

        // Check if report can be approved
        if ($report->status !== 'submitted') {
            return new WP_Error(
                'invalid_status',
                __('Only submitted reports can be approved.', 'bassmah-staff-reports'),
                array('status' => 400)
            );
        }

        // Prepare update data
        $update_data = array(
            'status' => 'approved',
            'manager_id' => $manager_id,
            'manager_comment' => !empty($comment) ? sanitize_textarea_field($comment) : null,
            'updated_at' => current_time('mysql')
        );

        // Update in database
        $this->database->start_transaction();
        
        try {
            $result = $this->database->update(
                'reports',
                $update_data,
                array('id' => $report_id)
            );

            if ($result === false) {
                $this->database->rollback();
                return new WP_Error(
                    'database_error',
                    __('Failed to approve report.', 'bassmah-staff-reports'),
                    array('status' => 500)
                );
            }

            // Clear cache
            $this->cache->delete("report_{$report_id}", 'reports');
            $this->cache->delete("user_reports_{$report->user_id}", 'reports');
            $this->cache->delete('pending_reports', 'reports');

            $this->database->commit();

            // Trigger action for approval
            do_action('bassmah_report_approved', $report_id, $manager_id, $update_data);

            return $report_id;

        } catch (Exception $e) {
            $this->database->rollback();
            return new WP_Error(
                'exception',
                $e->getMessage(),
                array('status' => 500)
            );
        }
    }

    /**
     * Reject a submitted report
     *
     * @since    1.0.0
     * @param    int     $report_id   Report ID
     * @param    int     $manager_id  Manager user ID
     * @param    string  $reason      Rejection reason (required)
     * @return   int|WP_Error
     */
    public function reject_report($report_id, $manager_id, $reason = '') {
        // Get the report
        $report = $this->get_report($report_id, false);
        if (is_wp_error($report)) {
            return $report;
        }

        // Check if report can be rejected
        if ($report->status !== 'submitted') {
            return new WP_Error(
                'invalid_status',
                __('Only submitted reports can be rejected.', 'bassmah-staff-reports'),
                array('status' => 400)
            );
        }

        // Validate reason
        if (empty($reason)) {
            return new WP_Error(
                'missing_reason',
                __('Rejection reason is required.', 'bassmah-staff-reports'),
                array('status' => 400)
            );
        }

        // Prepare update data
        $update_data = array(
            'status' => 'rejected',
            'manager_id' => $manager_id,
            'manager_comment' => sanitize_textarea_field($reason),
            'updated_at' => current_time('mysql')
        );

        // Update in database
        $this->database->start_transaction();
        
        try {
            $result = $this->database->update(
                'reports',
                $update_data,
                array('id' => $report_id)
            );

            if ($result === false) {
                $this->database->rollback();
                return new WP_Error(
                    'database_error',
                    __('Failed to reject report.', 'bassmah-staff-reports'),
                    array('status' => 500)
                );
            }

            // Clear cache
            $this->cache->delete("report_{$report_id}", 'reports');
            $this->cache->delete("user_reports_{$report->user_id}", 'reports');
            $this->cache->delete('pending_reports', 'reports');

            $this->database->commit();

            // Trigger action for rejection
            do_action('bassmah_report_rejected', $report_id, $manager_id, $update_data);

            return $report_id;

        } catch (Exception $e) {
            $this->database->rollback();
            return new WP_Error(
                'exception',
                $e->getMessage(),
                array('status' => 500)
            );
        }
    }

    /**
     * Get pending reports (submitted, awaiting approval)
     *
     * @since    1.0.0
     * @param    int    $limit   Number of reports
     * @param    int    $offset  Offset for pagination
     * @return   array
     */
    public function get_pending_reports($limit = 20, $offset = 0) {
        $cache_key = "pending_reports_{$limit}_{$offset}";
        $cached = $this->cache->get($cache_key, 'reports');
        
        if ($cached !== null) {
            return $cached;
        }

        $reports = $this->get_reports(array(
            'status' => 'submitted',
            'limit' => $limit,
            'offset' => $offset,
            'orderby' => 'submission_time',
            'order' => 'ASC'
        ));

        $this->cache->set($cache_key, $reports, 'reports', 600);

        return $reports;
    }

    /**
     * Get count of pending reports
     *
     * @since    1.0.0
     * @return   int
     */
    public function get_pending_report_count() {
        $cache_key = 'pending_report_count';
        $cached = $this->cache->get($cache_key, 'reports');
        
        if ($cached !== null) {
            return $cached;
        }

        $count = $this->database->get_var(
            "SELECT COUNT(*) FROM {$this->database->get_table_name('reports')} WHERE status = %s",
            array('submitted')
        );

        $this->cache->set($cache_key, $count, 'reports', 600);

        return intval($count);
    }
}
