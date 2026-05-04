<?php
class Basmah_Staff_Reports_Working_Days {
    public static function get_working_days($month, $year) {
        $working_days = array();
        $num_days = cal_days_in_month(CAL_GREGORIAN, $month, $year);
        
        for ($day = 1; $day <= $num_days; $day++) {
            $date = sprintf('%04d-%02d-%02d', $year, $month, $day);
            $weekday = date('w', strtotime($date));
            
            if ($weekday != 0 && $weekday != 6) {
                $working_days[] = $date;
            }
        }
        
        return $working_days;
    }
    
    public static function get_holidays($year) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'bsr_holidays';
        
        return $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_name WHERE YEAR(holiday_date) = %d", $year));
    }
}
