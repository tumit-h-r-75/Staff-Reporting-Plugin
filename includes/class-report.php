<?php
/**
 * Handle report CRUD operations and business logic.
 *
 * @since      1.0.0
 * @package    Bassmah_Staff_Reports
 * @author     Tumit <tumit@bassmah.ca>
 */
class Bassmah_Staff_Reports_Report {

    /**
     * Table name for reports
     *
     * @var string
     */
    private $table_name;

    /**
     * Constructor
     */
    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'staff_reports';
    }

    /**
     * Submit a new report
     *
     * @param array $data
     * @return int|WP_Error Report ID on success, WP_Error on failure
     */
    public function submit_report($data) {
        global $wpdb;

        // Validate required fields
        $required_fields = array('user_id', 'report_date', 'tasks');
        foreach ($required_fields as $field) {
            if (empty($data[$field])) {
                return new WP_Error(
                    'missing_field',
                    sprintf(__('Missing required field: %s', 'bassmah-staff-reports'), $field),
                    array('status' => 400)
                );
            }
        }

        // Check if report already exists for this date
        $existing = $this->get_report_by_date($data['user_id'], $data['report_date']);
        if ($existing) {
            return new WP_Error(
                'report_exists',
                __('A report has already been submitted for this date.', 'bassmah-staff-reports'),
                array('status' => 409)
            );
        }

        // Prepare data for insertion
        $report_data = array(
            'user_id' => intval($data['user_id']),
            'report_date' => $data['report_date'],
            'submission_time' => current_time('mysql'),
            'status' => isset($data['status']) ? $data['status'] : 'submitted',
            'tasks_json' => wp_json_encode($data['tasks']),
            'ip_address' => $this->get_user_ip(),
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql')
        );

        // Debug: Log table name and data
        error_log('Bassmah Plugin: Attempting to insert into table: ' . $this->table_name);
        error_log('Bassmah Plugin: Report data: ' . print_r($report_data, true));
        error_log('Bassmah Plugin: WPDB last error before insert: ' . $wpdb->last_error);
        
        // Insert report
        $result = $wpdb->insert($this->table_name, $report_data, array('%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s'));

        // Debug: Log result
        error_log('Bassmah Plugin: Insert result: ' . ($result ? 'SUCCESS' : 'FAILED'));
        error_log('Bassmah Plugin: WPDB last error after insert: ' . $wpdb->last_error);
        error_log('Bassmah Plugin: WPDB insert ID: ' . $wpdb->insert_id);

        if ($result === false) {
            return new WP_Error(
                'db_error',
                __('Failed to save report to database. Error: ', 'bassmah-staff-reports') . $wpdb->last_error,
                array('status' => 500)
            );
        }

        $report_id = $wpdb->insert_id;
        error_log('Bassmah Plugin: Report inserted with ID: ' . $report_id);

        // Trigger notification
        $this->trigger_notification('report_submitted', $report_id);

        return $report_id;
    }

    /**
     * Update an existing report
     *
     * @param int $report_id
     * @param array $data
     * @return bool|WP_Error
     */
    public function update_report($report_id, $data) {
        global $wpdb;

        $report = $this->get_report($report_id);
        if (!$report) {
            return new WP_Error(
                'report_not_found',
                __('Report not found.', 'bassmah-staff-reports'),
                array('status' => 404)
            );
        }

        // Check permissions
        if (!Bassmah_Staff_Reports_Roles::can_view_user_reports($report->user_id)) {
            return new WP_Error(
                'permission_denied',
                __('You do not have permission to update this report.', 'bassmah-staff-reports'),
                array('status' => 403)
            );
        }

        $update_data = array('updated_at' => current_time('mysql'));

        if (isset($data['status'])) {
            $update_data['status'] = $data['status'];
        }

        if (isset($data['tasks'])) {
            $update_data['tasks_json'] = wp_json_encode($data['tasks']);
        }

        if (isset($data['manager_comment'])) {
            $update_data['manager_comment'] = $data['manager_comment'];
        }

        $result = $wpdb->update(
            $this->table_name,
            $update_data,
            array('id' => $report_id),
            array('%s', '%s', '%s', '%s'),
            array('%d')
        );

        if ($result === false) {
            return new WP_Error(
                'db_error',
                __('Failed to update report.', 'bassmah-staff-reports'),
                array('status' => 500)
            );
        }

        return true;
    }

    /**
     * Get a single report by ID
     *
     * @param int $report_id
     * @return object|null
     */
    public function get_report($report_id) {
        global $wpdb;

        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->table_name} WHERE id = %d",
            $report_id
        ));
    }

    /**
     * Get report by user and date
     *
     * @param int $user_id
     * @param string $date
     * @return object|null
     */
    public function get_report_by_date($user_id, $date) {
        global $wpdb;

        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->table_name} WHERE user_id = %d AND report_date = %s",
            $user_id,
            $date
        ));
    }

    /**
     * Get reports with filtering options
     *
     * @param array $args
     * @return array
     */
    public function get_reports($args = array()) {
        global $wpdb;

        $defaults = array(
            'user_id' => null,
            'status' => null,
            'date_from' => null,
            'date_to' => null,
            'limit' => 50,
            'offset' => 0,
            'orderby' => 'submission_time',
            'order' => 'DESC'
        );

        $args = wp_parse_args($args, $defaults);
        $where_conditions = array();
        $where_values = array();

        // Build WHERE conditions
        if ($args['user_id']) {
            $where_conditions[] = "user_id = %d";
            $where_values[] = $args['user_id'];
        }

        if ($args['status']) {
            $where_conditions[] = "status = %s";
            $where_values[] = $args['status'];
        }

        if ($args['date_from']) {
            $where_conditions[] = "report_date >= %s";
            $where_values[] = $args['date_from'];
        }

        if ($args['date_to']) {
            $where_conditions[] = "report_date <= %s";
            $where_values[] = $args['date_to'];
        }

        $where_clause = '';
        if (!empty($where_conditions)) {
            $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
        }

        // Build ORDER BY
        $orderby = sanitize_sql_orderby($args['orderby'] . ' ' . $args['order']);
        if (!$orderby) {
            $orderby = 'submission_time DESC';
        }

        // Build LIMIT
        $limit = '';
        if ($args['limit'] > 0) {
            $limit = $wpdb->prepare('LIMIT %d OFFSET %d', $args['limit'], $args['offset']);
        }

        $sql = "SELECT * FROM {$this->table_name} {$where_clause} ORDER BY {$orderby} {$limit}";

        if (!empty($where_values)) {
            $sql = $wpdb->prepare($sql, $where_values);
        }

        $reports = $wpdb->get_results($sql);

        // Parse JSON tasks for each report
        foreach ($reports as $report) {
            $report->tasks = json_decode($report->tasks_json, true);
        }

        return $reports;
    }

    /**
     * Get reports for current user
     *
     * @param array $args
     * @return array
     */
    public function get_my_reports($args = array()) {
        $args['user_id'] = get_current_user_id();
        return $this->get_reports($args);
    }

    /**
     * Get today's report for current user
     *
     * @return object|null
     */
    public function get_today_report() {
        $user_id = get_current_user_id();
        $today = current_time('Y-m-d');
        return $this->get_report_by_date($user_id, $today);
    }

    /**
     * Add comment to a report
     *
     * @param int $report_id
     * @param string $comment
     * @return bool|WP_Error
     */
    public function add_comment($report_id, $comment) {
        if (!Bassmah_Staff_Reports_Roles::can_comment_reports()) {
            return new WP_Error(
                'permission_denied',
                __('You do not have permission to comment on reports.', 'bassmah-staff-reports'),
                array('status' => 403)
            );
        }

        return $this->update_report($report_id, array('manager_comment' => $comment));
    }

    /**
     * Get report statistics
     *
     * @param array $args
     * @return array
     */
    public function get_statistics($args = array()) {
        global $wpdb;

        $defaults = array(
            'date_from' => date('Y-m-01'), // First day of current month
            'date_to' => date('Y-m-t'),   // Last day of current month
            'user_id' => null
        );

        $args = wp_parse_args($args, $defaults);

        $where_conditions = array("report_date BETWEEN %s AND %s");
        $where_values = array($args['date_from'], $args['date_to']);

        if ($args['user_id']) {
            $where_conditions[] = "user_id = %d";
            $where_values[] = $args['user_id'];
        }

        $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);

        $sql = "SELECT 
                    COUNT(*) as total_reports,
                    COUNT(DISTINCT user_id) as unique_users,
                    COUNT(CASE WHEN status = 'submitted' THEN 1 END) as submitted_reports,
                    COUNT(CASE WHEN status = 'approved' THEN 1 END) as approved_reports,
                    COUNT(CASE WHEN status = 'rejected' THEN 1 END) as rejected_reports
                FROM {$this->table_name} 
                {$where_clause}";

        if (!empty($where_values)) {
            $sql = $wpdb->prepare($sql, $where_values);
        }

        return $wpdb->get_row($sql, ARRAY_A);
    }

    /**
     * Get user's IP address
     *
     * @return string
     */
    private function get_user_ip() {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            return $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            return $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            return $_SERVER['REMOTE_ADDR'];
        }
    }

    /**
     * Trigger notification for report events
     *
     * @param string $event
     * @param int $report_id
     */
    private function trigger_notification($event, $report_id) {
        $email_notifications = get_option('bassmah_email_notifications', array());
        
        if (!isset($email_notifications[$event]) || !$email_notifications[$event]) {
            return;
        }

        $report = $this->get_report($report_id);
        if (!$report) {
            return;
        }

        switch ($event) {
            case 'report_submitted':
                $this->notify_manager_report_submitted($report);
                break;
            case 'manager_comment':
                $this->notify_staff_comment_added($report);
                break;
        }
    }

    /**
     * Notify manager when report is submitted
     *
     * @param object $report
     */
    private function notify_manager_report_submitted($report) {
        $managers = Bassmah_Staff_Reports_Roles::get_manager_users();
        
        foreach ($managers as $manager) {
            $subject = sprintf(
                __('New Daily Report Submitted by %s', 'bassmah-staff-reports'),
                get_userdata($report->user_id)->display_name
            );

            $message = sprintf(
                __('A new daily report has been submitted by %s for %s.', 'bassmah-staff-reports') . "\n\n" .
                __('View Report: %s', 'bassmah-staff-reports'),
                get_userdata($report->user_id)->display_name,
                $report->report_date,
                admin_url('admin.php?page=bassmah-reports&view=report&id=' . $report->id)
            );

            wp_mail($manager->user_email, $subject, $message);
        }
    }

    /**
     * Get count of reports for pagination
     *
     * @param array $args
     * @return int
     */
    public function get_my_reports_count($args = array()) {
        global $wpdb;
        
        $where = "WHERE 1=1";
        if (!empty($args['user_id'])) {
            $where .= $wpdb->prepare(" AND user_id = %d", $args['user_id']);
        }
        if (!empty($args['date_from'])) {
            $where .= $wpdb->prepare(" AND report_date >= %s", $args['date_from']);
        }
        if (!empty($args['date_to'])) {
            $where .= $wpdb->prepare(" AND report_date <= %s", $args['date_to']);
        }
        if (!empty($args['status'])) {
            $where .= $wpdb->prepare(" AND status = %s", $args['status']);
        }
        
        $count = $wpdb->get_var("SELECT COUNT(*) FROM {$this->table_name} $where");
        
        return intval($count);
    }

    /**
     * Notify staff when manager adds comment
     *
     * @param object $report
     */
    private function notify_staff_comment_added($report) {
        $user = get_userdata($report->user_id);
        if (!$user) {
            return;
        }

        $subject = __('Manager Comment on Your Report', 'bassmah-staff-reports');
        
        $message = sprintf(
            __('Your manager has added a comment to your report for %s.', 'bassmah-staff-reports') . "\n\n" .
            __('Comment: %s', 'bassmah-staff-reports') . "\n\n" .
            __('View Report: %s', 'bassmah-staff-reports'),
            $report->report_date,
            $report->manager_comment,
            admin_url('admin.php?page=bassmah-my-reports&view=report&id=' . $report->id)
        );

        wp_mail($user->user_email, $subject, $message);
    }
}
