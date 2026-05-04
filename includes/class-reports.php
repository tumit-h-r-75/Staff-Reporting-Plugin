<?php
class Basmah_Staff_Reports_Reports {
    public static function create_report($data) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'bsr_reports';
        
        return $wpdb->insert($table_name, array(
            'user_id' => $data['user_id'],
            'report_date' => $data['report_date'],
            'content' => $data['content'],
            'status' => 'pending',
            'created_at' => current_time('mysql'),
        ));
    }
    
    public static function get_reports($args = array()) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'bsr_reports';
        
        $query = "SELECT * FROM $table_name WHERE 1=1";
        
        if (isset($args['user_id'])) {
            $query .= $wpdb->prepare(" AND user_id = %d", $args['user_id']);
        }
        
        if (isset($args['status'])) {
            $query .= $wpdb->prepare(" AND status = %s", $args['status']);
        }
        
        $query .= " ORDER BY created_at DESC";
        
        return $wpdb->get_results($query);
    }
    
    public static function get_report($id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'bsr_reports';
        
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $id));
    }
    
    public static function update_report($id, $data) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'bsr_reports';
        
        return $wpdb->update($table_name, $data, array('id' => $id));
    }
}
