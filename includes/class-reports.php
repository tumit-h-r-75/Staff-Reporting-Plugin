<?php
class Basmah_Staff_Reports_Reports {
    public static function create_report($data) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'staff_reports';
        
        return $wpdb->insert($table_name, array(
            'user_id' => intval($data['user_id']),
            'report_date' => sanitize_text_field($data['report_date']),
            'tasks_json' => wp_json_encode($data['tasks']),
            'status' => 'pending',
            'manager_comment' => isset($data['manager_comment']) ? sanitize_textarea_field($data['manager_comment']) : null,
            'ip_address' => isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field($_SERVER['REMOTE_ADDR']) : '',
            'submission_time' => current_time('mysql'),
            'created_at' => current_time('mysql'),
        ), array('%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s'));
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
        
        return $wpdb->update($table_name, $update_data, array('id' => $id));
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
