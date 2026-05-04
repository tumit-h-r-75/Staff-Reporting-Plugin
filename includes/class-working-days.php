<?php
class Basmah_Staff_Reports_Working_Days {
    public static function get_working_days($month, $year) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'staff_working_days';
        
        $start_date = sprintf('%04d-%02d-01', $year, $month);
        $end_date = sprintf('%04d-%02d-%02d', $year, $month, cal_days_in_month(CAL_GREGORIAN, $month, $year));
        
        $days = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_name WHERE work_date BETWEEN %s AND %s",
            $start_date,
            $end_date
        ), ARRAY_A);
        
        $working_days = array();
        $num_days = cal_days_in_month(CAL_GREGORIAN, $month, $year);
        
        for ($day = 1; $day <= $num_days; $day++) {
            $date = sprintf('%04d-%02d-%02d', $year, $month, $day);
            $is_working_day = true;
            
            $weekday = date('w', strtotime($date));
            if ($weekday == 0 || $weekday == 6) {
                $is_working_day = false;
            }
            
            foreach ($days as $db_day) {
                if ($db_day['work_date'] == $date) {
                    $is_working_day = !$db_day['is_holiday'];
                    break;
                }
            }
            
            if ($is_working_day) {
                $working_days[] = $date;
            }
        }
        
        return $working_days;
    }
    
    public static function get_working_days_count($month, $year) {
        return count(self::get_working_days($month, $year));
    }
    
    public static function get_all_days($month, $year) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'staff_working_days';
        
        $start_date = sprintf('%04d-%02d-01', $year, $month);
        $end_date = sprintf('%04d-%02d-%02d', $year, $month, cal_days_in_month(CAL_GREGORIAN, $month, $year));
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_name WHERE work_date BETWEEN %s AND %s ORDER BY work_date",
            $start_date,
            $end_date
        ), ARRAY_A);
    }
    
    public static function add_day($data) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'staff_working_days';
        
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table_name WHERE work_date = %s",
            $data['work_date']
        ));
        
        if ($existing) {
            return $wpdb->update($table_name, array(
                'is_holiday' => $data['is_holiday'],
                'holiday_name' => isset($data['holiday_name']) ? sanitize_text_field($data['holiday_name']) : null,
                'updated_at' => current_time('mysql')
            ), array('work_date' => $data['work_date']));
        }
        
        return $wpdb->insert($table_name, array(
            'work_date' => $data['work_date'],
            'is_holiday' => $data['is_holiday'],
            'holiday_name' => isset($data['holiday_name']) ? sanitize_text_field($data['holiday_name']) : null,
            'created_by' => get_current_user_id(),
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql')
        ));
    }
    
    public static function delete_day($id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'staff_working_days';
        
        return $wpdb->delete($table_name, array('id' => $id), array('%d'));
    }
    
    public static function is_working_day($date) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'staff_working_days';
        
        $db_day = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE work_date = %s",
            $date
        ), ARRAY_A);
        
        if ($db_day) {
            return !$db_day['is_holiday'];
        }
        
        $weekday = date('w', strtotime($date));
        return ($weekday != 0 && $weekday != 6);
    }
}
