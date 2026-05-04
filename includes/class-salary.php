<?php
class Basmah_Staff_Reports_Salary {
    public static function calculate_salary($user_id, $month, $year) {
        $working_days = Basmah_Staff_Reports_Working_Days::get_working_days($month, $year);
        $reports = Basmah_Staff_Reports_Reports::get_reports(array(
            'user_id' => $user_id,
        ));
        
        $settings = self::get_settings();
        
        $salary = 0;
        
        return $salary;
    }
    
    public static function get_settings() {
        $defaults = array(
            'base_salary' => 0,
            'daily_rate' => 0,
        );
        
        return wp_parse_args(get_option('bsr_salary_settings', array()), $defaults);
    }
    
    public static function save_settings($settings) {
        update_option('bsr_salary_settings', $settings);
    }
}
