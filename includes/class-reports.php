<?php
class Basmah_Staff_Reports_Reports {
    public static function create_report($data) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'staff_reports';
        
        // Check for duplicate submission
        $existing = self::report_exists($data['user_id'], $data['report_date']);
        if ($existing) {
            return new WP_Error('duplicate', 'Report already submitted for this date.');
        }
        
        // Validate tasks data
        if (empty($data['tasks']) || !is_array($data['tasks'])) {
            return new WP_Error('invalid_tasks', 'Tasks data is required.');
        }
        
        // Validate each task
        foreach ($data['tasks'] as $task) {
            if (empty($task['task_category']) || empty($task['task_description'])) {
                return new WP_Error('invalid_task', 'Task category and description are required.');
            }
        }
        
        $result = $wpdb->insert($table_name, array(
            'user_id' => intval($data['user_id']),
            'report_date' => sanitize_text_field($data['report_date']),
            'tasks_json' => wp_json_encode($data['tasks']),
            'status' => 'pending',
            'manager_comment' => isset($data['manager_comment']) ? sanitize_textarea_field($data['manager_comment']) : null,
            'ip_address' => isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field($_SERVER['REMOTE_ADDR']) : '',
            'submission_time' => current_time('mysql'),
            'created_at' => current_time('mysql'),
        ), array('%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s'));
        
        return $result;
    }
    
    public static function get_reports($args = array()) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'staff_reports';
        $users_table = $wpdb->prefix . 'users';
        
        $query = "SELECT r.*, u.display_name, u.user_email 
                  FROM $table_name r 
                  JOIN $users_table u ON r.user_id = u.ID 
                  WHERE 1=1";
        
        $params = array();
        
        if (isset($args['user_id'])) {
            $query .= " AND r.user_id = %d";
            $params[] = $args['user_id'];
        }
        
        if (isset($args['status'])) {
            $query .= " AND r.status = %s";
            $params[] = $args['status'];
        }
        
        if (isset($args['date_from'])) {
            $query .= " AND r.report_date >= %s";
            $params[] = $args['date_from'];
        }
        
        if (isset($args['date_to'])) {
            $query .= " AND r.report_date <= %s";
            $params[] = $args['date_to'];
        }
        
        if (isset($args['role'])) {
            $user_meta_table = $wpdb->prefix . 'usermeta';
            $capabilities_key = $wpdb->prefix . 'capabilities';
            $query .= " AND EXISTS (
                SELECT 1 FROM $user_meta_table um
                WHERE um.user_id = u.ID
                AND um.meta_key = %s
                AND um.meta_value LIKE %s
            )";
            $params[] = $capabilities_key;
            $params[] = '%"' . $args['role'] . '"%';
        }
        
        $query .= " ORDER BY r.report_date DESC, r.created_at DESC";
        
        if (!empty($params)) {
            return $wpdb->get_results($wpdb->prepare($query, $params), ARRAY_A);
        }
        return $wpdb->get_results($query, ARRAY_A);
    }
    
    public static function get_report($id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'staff_reports';
        $users_table = $wpdb->prefix . 'users';
        
        return $wpdb->get_row($wpdb->prepare("
            SELECT r.*, u.display_name, u.user_email 
            FROM $table_name r 
            JOIN $users_table u ON r.user_id = u.ID 
            WHERE r.id = %d
        ", $id), ARRAY_A);
    }
    
    public static function update_report($id, $data) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'staff_reports';
        
        $update_data = array();
        if (isset($data['status'])) {
            $update_data['status'] = sanitize_text_field($data['status']);
        }
        if (isset($data['manager_comment'])) {
            $update_data['manager_comment'] = sanitize_textarea_field($data['manager_comment']);
        }
        if (isset($data['tasks_json'])) {
            $update_data['tasks_json'] = $data['tasks_json'];
        }
        $update_data['updated_at'] = current_time('mysql');
        
        $result = $wpdb->update($table_name, $update_data, array('id' => $id));
        
        if ($result !== false && isset($data['status'])) {
            // Notify staff member of status update
            $report = self::get_report($id);
            self::notify_staff_status_update($report, $data['status']);
        }
        
        return $result;
    }
    
    public static function delete_report($id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'staff_reports';
        
        return $wpdb->delete($table_name, array('id' => $id), array('%d'));
    }
    
    public static function get_report_count($args = array()) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'staff_reports';
        
        $query = "SELECT COUNT(*) FROM $table_name WHERE 1=1";
        $params = array();
        
        if (isset($args['user_id'])) {
            $query .= " AND user_id = %d";
            $params[] = $args['user_id'];
        }
        
        if (isset($args['status'])) {
            $query .= " AND status = %s";
            $params[] = $args['status'];
        }
        
        if (isset($args['month'])) {
            $query .= " AND MONTH(report_date) = %d";
            $params[] = $args['month'];
        }
        
        if (isset($args['year'])) {
            $query .= " AND YEAR(report_date) = %d";
            $params[] = $args['year'];
        }
        
        if (!empty($params)) {
            return $wpdb->get_var($wpdb->prepare($query, $params));
        }
        return $wpdb->get_var($query);
    }
    
    /**
     * Export reports to CSV
     */
    public static function export_to_csv($reports) {
        $filename = 'staff-reports-' . date('Y-m-d') . '.csv';
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        $output = fopen('php://output', 'w');
        
        // CSV Header
        fputcsv($output, [
            'Report ID',
            'Staff Name',
            'Email',
            'Report Date',
            'Status',
            'Tasks',
            'Manager Comment',
            'Submission Time'
        ]);
        
        foreach ($reports as $report) {
            $tasks = json_decode($report['tasks_json'], true);
            $task_descriptions = [];
            
            if (is_array($tasks)) {
                foreach ($tasks as $task) {
                    $task_descriptions[] = $task['task_category'] . ': ' . $task['task_description'];
                }
            }
            
            fputcsv($output, [
                $report['id'],
                $report['display_name'],
                $report['user_email'],
                $report['report_date'],
                $report['status'],
                implode('; ', $task_descriptions),
                $report['manager_comment'],
                $report['submission_time']
            ]);
        }
        
        fclose($output);
        exit;
    }
    
    /**
     * Send email notifications
     */
    public static function notify_managers_new_report($user_id, $report_id) {
        $managers = get_users(['role__in' => ['basmah_manager', 'administrator']]);
        $staff_user = get_userdata($user_id);
        $report = self::get_report($report_id);
        
        $subject = 'New Staff Report Submitted';
        $message = sprintf(
            "A new report has been submitted by %s on %s.\n\nReport ID: %d\nView details: %s",
            $staff_user->display_name,
            $report['report_date'],
            $report_id,
            admin_url('admin.php?page=bsr-reports&action=view&id=' . $report_id)
        );
        
        foreach ($managers as $manager) {
            wp_mail($manager->user_email, $subject, $message);
        }
    }
    
    /**
     * Notify staff member of status update
     */
    public static function notify_staff_status_update($report, $status) {
        $subject = 'Report Status Update';
        $message = sprintf(
            "Your report submitted on %s has been %s.\n\n%s",
            $report['report_date'],
            $status,
            $report['manager_comment'] ? "Manager Comment: " . $report['manager_comment'] : "No comment provided."
        );
        
        wp_mail($report['user_email'], $subject, $message);
    }
    
    public static function report_exists($user_id, $report_date) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'staff_reports';
        
        return $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table_name WHERE user_id = %d AND report_date = %s",
            $user_id,
            $report_date
        ));
    }
}
